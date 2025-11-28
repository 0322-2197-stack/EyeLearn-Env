"""
FastAPI-based Eye Tracking Service for E-Learning Platform
Features: Async support, WebSocket real-time updates, OpenAPI documentation
"""

import os
import time
import json
import base64
import logging
import asyncio
import threading
import uuid
from datetime import datetime
from typing import Optional, Dict, List
from contextlib import asynccontextmanager

from fastapi import FastAPI, WebSocket, WebSocketDisconnect, HTTPException, Depends, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, HTMLResponse
from fastapi.staticfiles import StaticFiles
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel, Field, validator
import uvicorn
import cv2
import numpy as np

# Import the existing eye tracking service
from eye_tracking_service import EyeTrackingService, NumpyEncoder

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Global eye tracker instance
eye_tracker: Optional[EyeTrackingService] = None
frame_processing_task: Optional[asyncio.Task] = None
websocket_broadcast_task: Optional[asyncio.Task] = None


# WebSocket Connection Manager
class ConnectionManager:
    """Manages WebSocket connections for real-time updates"""
    
    def __init__(self):
        self.active_connections: List[WebSocket] = []
        self.lock = asyncio.Lock()
    
    async def connect(self, websocket: WebSocket):
        """Accept and register a new WebSocket connection"""
        await websocket.accept()
        async with self.lock:
            self.active_connections.append(websocket)
        logger.info(f"WebSocket connected. Total connections: {len(self.active_connections)}")
    
    def disconnect(self, websocket: WebSocket):
        """Remove a WebSocket connection"""
        if websocket in self.active_connections:
            self.active_connections.remove(websocket)
        logger.info(f"WebSocket disconnected. Total connections: {len(self.active_connections)}")
    
    async def broadcast(self, message: dict):
        """Broadcast a message to all connected WebSocket clients"""
        if not self.active_connections:
            return
        
        disconnected = []
        async with self.lock:
            for connection in self.active_connections:
                try:
                    await connection.send_json(message)
                except Exception as e:
                    logger.error(f"Error broadcasting to WebSocket: {e}")
                    disconnected.append(connection)
        
        # Remove disconnected clients
        for conn in disconnected:
            self.disconnect(conn)


# Initialize connection manager
manager = ConnectionManager()


# Pydantic Models for Request/Response
class TrackingStartRequest(BaseModel):
    """Request model for starting eye tracking"""
    user_id: str = Field(..., description="User ID for tracking session")
    module_id: str = Field(..., description="Module ID being tracked")
    section_id: Optional[str] = Field(None, description="Optional section ID")


class TrackingStopRequest(BaseModel):
    """Request model for stopping eye tracking"""
    save_data: bool = Field(True, description="Whether to save tracking data before stopping")


class TrackingStatusResponse(BaseModel):
    """Response model for tracking status"""
    is_tracking: bool
    is_focused: bool
    user_id: Optional[str]
    module_id: Optional[str]
    section_id: Optional[str]
    session_duration: float
    focused_time: float
    unfocused_time: float
    tracking_state: str
    countdown_active: bool
    countdown_remaining: float


class HealthResponse(BaseModel):
    """Response model for health check"""
    status: str
    timestamp: str
    tracking: bool
    camera_enabled: bool
    service_version: str = "1.0.0"


class FramePayload(BaseModel):
    """Payload for browser-streamed frames"""
    frame_id: Optional[str] = Field(default_factory=lambda: str(uuid.uuid4()))
    user_id: Optional[str] = None
    module_id: Optional[str] = None
    section_id: Optional[str] = None
    timestamp: Optional[float] = Field(default_factory=lambda: time.time())
    fps: Optional[float] = None
    frame_base64: str
    
    @validator("frame_base64")
    def validate_base64(cls, value: str) -> str:
        if not value.startswith("data:image/"):
            raise ValueError("Expected data URL with mime type")
        return value


# Lifespan context manager for startup/shutdown
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Manage application lifespan - startup and shutdown"""
    global eye_tracker, frame_processing_task, websocket_broadcast_task
    
    # Startup
    logger.info("🚀 Starting FastAPI Eye Tracking Service...")
    try:
        # Initialize eye tracking service
        eye_tracker = EyeTrackingService(auto_start_loop=False)
        logger.info("✅ Eye tracking service initialized")
        
        # Start background tasks
        frame_processing_task = asyncio.create_task(process_frames_background())
        websocket_broadcast_task = asyncio.create_task(broadcast_websocket_updates())
        logger.info("✅ Background tasks started")
        
    except Exception as e:
        logger.error(f"❌ Error during startup: {e}")
        raise
    
    yield
    
    # Shutdown
    logger.info("🛑 Shutting down FastAPI Eye Tracking Service...")
    try:
        # Stop background tasks
        if frame_processing_task:
            frame_processing_task.cancel()
            try:
                await frame_processing_task
            except asyncio.CancelledError:
                pass
        
        if websocket_broadcast_task:
            websocket_broadcast_task.cancel()
            try:
                await websocket_broadcast_task
            except asyncio.CancelledError:
                pass
        
        # Stop tracking and cleanup
        if eye_tracker:
            eye_tracker.stop_tracking(save_data=False)
            if eye_tracker.webcam is not None:
                eye_tracker.webcam.release()
        
        logger.info("✅ Shutdown complete")
    except Exception as e:
        logger.error(f"❌ Error during shutdown: {e}")


# Initialize FastAPI app with lifespan
app = FastAPI(
    title="Eye Tracking API",
    description="A FastAPI service for real-time eye tracking with WebSocket support",
    version="1.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc",
    openapi_url="/api/openapi.json",
    lifespan=lifespan
)

# CORS configuration - read from environment or allow all
# Note: When allow_credentials=True, you cannot use allow_origins=["*"]
# So we either disable credentials or use specific origins
allowed_origins_env = os.environ.get("ALLOWED_ORIGINS", "*")
if allowed_origins_env == "*":
    # If wildcard, disable credentials (required for CORS spec)
    allow_origins = ["*"]
    allow_creds = False
else:
    # Parse comma-separated origins
    allow_origins = [origin.strip() for origin in allowed_origins_env.split(",") if origin.strip()]
    allow_creds = True

app.add_middleware(
    CORSMiddleware,
    allow_origins=allow_origins,
    allow_credentials=allow_creds,
    allow_methods=["*"],
    allow_headers=["*"],
    expose_headers=["*"],
)


# Background Tasks
async def process_frames_background():
    """
    Background task to monitor tracking status.
    Note: When auto_start_loop=True, EyeTrackingService handles frame processing
    in its own thread. This task just monitors and handles edge cases.
    """
    logger.info("📹 Frame processing monitor task started")
    while True:
        try:
            # The EyeTrackingService handles its own frame processing when auto_start_loop=True
            # This task just monitors the service health
            if eye_tracker:
                # Check if tracking is enabled but loop isn't running
                if (hasattr(eye_tracker, 'is_tracking_enabled') and 
                    eye_tracker.is_tracking_enabled and 
                    not eye_tracker.is_tracking and
                    not eye_tracker.countdown_active):
                    # Ensure tracking loop is running
                    if not hasattr(eye_tracker, 'tracking_thread') or not eye_tracker.tracking_thread.is_alive():
                        logger.warning("Tracking enabled but loop not running - this should be handled by EyeTrackingService")
            
            await asyncio.sleep(5)  # Check every 5 seconds
        except asyncio.CancelledError:
            logger.info("📹 Frame processing monitor task cancelled")
            break
        except Exception as e:
            logger.error(f"❌ Error in frame processing monitor: {e}")
            await asyncio.sleep(5)


async def broadcast_websocket_updates():
    """Background task to broadcast tracking updates via WebSocket"""
    logger.info("📡 WebSocket broadcast task started")
    while True:
        try:
            if eye_tracker and eye_tracker.is_tracking:
                status = {
                    "is_focused": bool(eye_tracker.is_focused),
                    "timestamp": datetime.utcnow().isoformat(),
                    "session_duration": eye_tracker.get_session_duration(),
                    "focused_time": float(eye_tracker.accumulated_focused_time),
                    "unfocused_time": float(eye_tracker.accumulated_unfocused_time),
                    "tracking_state": eye_tracker.tracking_state,
                    "countdown_active": eye_tracker.countdown_active,
                    "countdown_remaining": max(0, eye_tracker.countdown_duration - 
                                              (time.time() - eye_tracker.countdown_start_time)) 
                                         if eye_tracker.countdown_start_time else 0
                }
                await manager.broadcast(status)
            await asyncio.sleep(0.1)  # Update every 100ms
        except asyncio.CancelledError:
            logger.info("📡 WebSocket broadcast task cancelled")
            break
        except Exception as e:
            logger.error(f"❌ Error in WebSocket broadcast: {e}")
            await asyncio.sleep(1)


# Helper function to convert numpy types
def convert_numpy_types(obj):
    """Recursively convert NumPy types to native Python types"""
    if isinstance(obj, dict):
        return {k: convert_numpy_types(v) for k, v in obj.items()}
    elif isinstance(obj, list):
        return [convert_numpy_types(v) for v in obj]
    elif isinstance(obj, np.bool_):
        return bool(obj)
    elif isinstance(obj, np.integer):
        return int(obj)
    elif isinstance(obj, np.floating):
        return float(obj)
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    return obj


# API Endpoints
@app.get("/", tags=["Root"])
async def root():
    """Root endpoint"""
    return {
        "message": "Eye Tracking API is running",
        "version": "1.0.0",
        "docs": "/api/docs",
        "health": "/api/health"
    }


@app.get("/api/health", response_model=HealthResponse, tags=["Health"])
async def health_check():
    """Health check endpoint for monitoring and load balancers"""
    try:
        return HealthResponse(
            status="healthy",
            timestamp=datetime.utcnow().isoformat(),
            tracking=eye_tracker.is_tracking if eye_tracker else False,
            camera_enabled=eye_tracker.camera_enabled if eye_tracker else False,
            service_version="1.0.0"
        )
    except Exception as e:
        logger.error(f"Health check error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/tracking/start", tags=["Tracking"])
async def start_tracking(request: TrackingStartRequest):
    """Start eye tracking with the given parameters"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        # Check if already tracking for same user/module
        if (eye_tracker.is_tracking and 
            eye_tracker.current_user_id == request.user_id and 
            eye_tracker.current_module_id == request.module_id):
            return {
                "status": "already_tracking",
                "message": "Eye tracking is already active for this user/module",
                "user_id": request.user_id,
                "module_id": request.module_id
            }
        
        # Set tracking parameters
        eye_tracker.current_user_id = request.user_id
        eye_tracker.current_module_id = request.module_id
        eye_tracker.current_section_id = request.section_id
        
        # Start tracking (this will handle the tracking loop internally)
        if not eye_tracker.is_tracking:
            # Set auto_start_loop to True to enable camera-based tracking
            eye_tracker.auto_start_loop = True
            eye_tracker.start_tracking(
                request.user_id,
                request.module_id,
                request.section_id
            )
            logger.info(f"✅ Tracking started: user={request.user_id}, module={request.module_id}")
            return {
                "status": "tracking_started",
                "message": "Eye tracking started successfully",
                "user_id": request.user_id,
                "module_id": request.module_id,
                "section_id": request.section_id
            }
        else:
            return {
                "status": "already_tracking",
                "message": "Eye tracking is already active",
                "user_id": request.user_id,
                "module_id": request.module_id
            }
    except Exception as e:
        logger.error(f"Error starting tracking: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/tracking/stop", tags=["Tracking"])
async def stop_tracking(request: Optional[TrackingStopRequest] = None):
    """Stop eye tracking"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        if request is None:
            request = TrackingStopRequest()
        
        save_data = request.save_data
        eye_tracker.stop_tracking(save_data=save_data)
        
        logger.info(f"✅ Tracking stopped (save_data={save_data})")
        return {
            "status": "tracking_stopped",
            "message": "Eye tracking stopped successfully",
            "data_saved": save_data
        }
    except Exception as e:
        logger.error(f"Error stopping tracking: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/tracking/status", response_model=TrackingStatusResponse, tags=["Tracking"])
async def get_tracking_status():
    """Get current tracking status"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        countdown_remaining = 0
        if eye_tracker.countdown_start_time:
            countdown_remaining = max(0, eye_tracker.countdown_duration - 
                                     (time.time() - eye_tracker.countdown_start_time))
        
        return TrackingStatusResponse(
            is_tracking=eye_tracker.is_tracking,
            is_focused=bool(eye_tracker.is_focused),
            user_id=eye_tracker.current_user_id,
            module_id=eye_tracker.current_module_id,
            section_id=eye_tracker.current_section_id,
            session_duration=eye_tracker.get_session_duration(),
            focused_time=float(eye_tracker.accumulated_focused_time),
            unfocused_time=float(eye_tracker.accumulated_unfocused_time),
            tracking_state=eye_tracker.tracking_state,
            countdown_active=eye_tracker.countdown_active,
            countdown_remaining=countdown_remaining
        )
    except Exception as e:
        logger.error(f"Error getting tracking status: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/tracking/metrics", tags=["Tracking"])
async def get_tracking_metrics():
    """Get detailed tracking metrics"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        metrics = eye_tracker.get_detailed_metrics()
        return {
            "success": True,
            "metrics": convert_numpy_types(metrics),
            "timestamp": datetime.utcnow().isoformat()
        }
    except Exception as e:
        logger.error(f"Error getting metrics: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/frame", tags=["Video"])
async def get_current_frame():
    """Get current frame as base64-encoded image"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        frame_data = eye_tracker.get_current_frame_base64()
        
        return {
            "success": True,
            "hasFrame": frame_data is not None,
            "frameData": frame_data if frame_data else "",
            "timestamp": datetime.utcnow().isoformat()
        }
    except Exception as e:
        logger.error(f"Error getting frame: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/frames", tags=["Video"])
async def receive_browser_frame(payload: FramePayload):
    """Receive browser-streamed frame and process it for eye tracking"""
    try:
        if not eye_tracker:
            raise HTTPException(status_code=503, detail="Eye tracking service not initialized")
        
        # Decode base64 frame
        try:
            header, b64_data = payload.frame_base64.split(",", 1)
            raw_bytes = base64.b64decode(b64_data)
            np_arr = np.frombuffer(raw_bytes, dtype=np.uint8)
            frame = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)
            
            if frame is None:
                raise ValueError("Failed to decode image")
        except Exception as e:
            logger.error(f"Error decoding frame: {e}")
            raise HTTPException(status_code=400, detail=f"Invalid frame data: {str(e)}")
        
        # Process frame using eye tracker
        user_id = payload.user_id or eye_tracker.current_user_id
        module_id = payload.module_id or eye_tracker.current_module_id
        section_id = payload.section_id or eye_tracker.current_section_id
        
        if not user_id or not module_id:
            raise HTTPException(status_code=400, detail="user_id and module_id are required")
        
        # Process the frame
        status, frame_data = await run_in_threadpool(
            eye_tracker.process_remote_frame,
            frame,
            user_id,
            module_id,
            section_id
        )
        
        return {
            "success": True,
            "frame_id": payload.frame_id,
            "user_id": user_id,
            "module_id": module_id,
            "section_id": section_id,
            "timestamp": payload.timestamp or time.time(),
            "status": status,
            "metrics": status.get("metrics") if isinstance(status, dict) else None,
            "current_frame": frame_data
        }
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error processing browser frame: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.websocket("/ws/tracking")
async def websocket_endpoint(websocket: WebSocket):
    """WebSocket endpoint for real-time tracking updates"""
    await manager.connect(websocket)
    try:
        # Send initial status
        if eye_tracker:
            initial_status = {
                "is_focused": bool(eye_tracker.is_focused),
                "is_tracking": eye_tracker.is_tracking,
                "timestamp": datetime.utcnow().isoformat(),
                "session_duration": eye_tracker.get_session_duration(),
                "focused_time": float(eye_tracker.accumulated_focused_time),
                "unfocused_time": float(eye_tracker.accumulated_unfocused_time),
                "tracking_state": eye_tracker.tracking_state
            }
            await websocket.send_json(initial_status)
        
        # Keep connection alive and handle incoming messages
        while True:
            try:
                # Wait for any message from client (ping/pong or commands)
                data = await asyncio.wait_for(websocket.receive_text(), timeout=30.0)
                # Echo back or process command
                await websocket.send_json({"type": "pong", "data": data})
            except asyncio.TimeoutError:
                # Send heartbeat to keep connection alive
                await websocket.send_json({
                    "type": "heartbeat",
                    "timestamp": datetime.utcnow().isoformat()
                })
    except WebSocketDisconnect:
        manager.disconnect(websocket)
        logger.info("WebSocket client disconnected")
    except Exception as e:
        logger.error(f"WebSocket error: {e}")
        manager.disconnect(websocket)


# Legacy endpoint compatibility
@app.get("/status", tags=["Legacy"])
async def legacy_status():
    """Legacy status endpoint for backward compatibility"""
    return {"status": "ok", "service": "eye_tracking", "message": "running"}


@app.post("/api/start_tracking", tags=["Legacy"])
async def legacy_start_tracking(request: TrackingStartRequest):
    """Legacy endpoint for starting tracking"""
    return await start_tracking(request)


@app.post("/api/stop_tracking", tags=["Legacy"])
async def legacy_stop_tracking():
    """Legacy endpoint for stopping tracking"""
    return await stop_tracking(TrackingStopRequest())


@app.get("/api/status", tags=["Legacy"])
async def legacy_get_status():
    """Legacy endpoint for getting status"""
    try:
        if not eye_tracker:
            return {"success": False, "error": "Service not initialized"}
        
        status = eye_tracker.get_status()
        frame_data = eye_tracker.get_current_frame_base64()
        if frame_data:
            status['current_frame'] = frame_data
        
        return {
            "success": True,
            "status": convert_numpy_types(status)
        }
    except Exception as e:
        logger.error(f"Error in legacy status endpoint: {e}")
        return {"success": False, "error": str(e)}


if __name__ == "__main__":
    # Get port from environment or default to 8000
    PORT = int(os.environ.get("PORT", 8000))
    HOST = os.environ.get("HOST", "0.0.0.0")
    
    logger.info(f"🚀 Starting FastAPI Eye Tracking Service on {HOST}:{PORT}")
    uvicorn.run(
        "fastapi_service:app",
        host=HOST,
        port=PORT,
        reload=False,  # Set to True for development
        log_level="info"
    )

