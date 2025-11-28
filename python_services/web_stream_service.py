"""
FastAPI-based eye tracking service that accepts browser-captured frames.

This service is designed for Railway where direct webcam access is not
possible. Frames are streamed from the browser, processed server-side,
and summarized results are pushed to the existing PHP endpoint.
"""

from __future__ import annotations
import base64
import json
import logging
import os
import time
import uuid
from typing import Any, Dict, Optional

import numpy as np
from fastapi import (
    BackgroundTasks,
    Depends,
    FastAPI,
    HTTPException,
    Request,
    WebSocket,
    WebSocketDisconnect,
)
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel, BaseSettings, Field, validator
from starlette.concurrency import run_in_threadpool

from python_services.eye_tracking_service import EyeTrackingService


class Settings(BaseSettings):
    """Runtime configuration wired to Railway environment variables."""

    app_name: str = "RailwayEyeTracking"
    camera_enabled: bool = Field(
        default=False,
        env="CAMERA_ENABLED",
        description="Leave False on Railway to avoid local webcam access.",
    )
    python_service_url: str = Field(
        default="https://railway-eye-service.up.railway.app",
        env="PYTHON_SERVICE_URL",
    )
    tracking_save_url: Optional[str] = Field(
        default=None,
        env="TRACKING_SAVE_URL",
        description="Existing PHP endpoint that stores tracking analytics.",
    )
    allowed_origins: str = Field(
        default="https://your-production-domain.com",
        env="ALLOWED_ORIGINS",
        description="Comma-separated list of origins permitted to call the API.",
    )
    max_frame_kb: int = Field(
        default=512,
        env="MAX_FRAME_KB",
        description="Reject frames larger than this many KB.",
    )
    max_client_fps: int = Field(
        default=10,
        env="MAX_CLIENT_FPS",
        description="Logical FPS budget enforced per client.",
    )
    model_name: str = Field(default="mediapipe-face-mesh", env="MODEL_NAME")
    browser_streaming_enabled: bool = Field(
        default=True,
        env="ENABLE_BROWSER_EYE_STREAMING",
        description="Disable to force clients to use local Flask service.",
    )

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"

    @validator("allowed_origins")
    def _normalize_origins(cls, value: str) -> str:  # type: ignore[override]
        return ",".join(origin.strip() for origin in value.split(",") if origin.strip())


settings = Settings()

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("web-stream-service")

browser_tracker = EyeTrackingService(
    tracking_save_url=settings.tracking_save_url,
    camera_enabled=False,
    auto_start_loop=False,
)

app = FastAPI(title=settings.app_name)

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.allowed_origins.split(","),
    allow_methods=["POST", "OPTIONS"],
    allow_headers=["*"],
    allow_credentials=False,
)


class FramePayload(BaseModel):
    """Incoming payload from the browser client."""

    frame_id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    user_id: Optional[str] = None
    module_id: Optional[str] = None
    section_id: Optional[str] = None
    timestamp: float = Field(default_factory=lambda: time.time())
    fps: Optional[float] = None
    frame_base64: str

    @validator("frame_base64")
    def validate_base64(cls, value: str) -> str:
        if not value.startswith("data:image/"):
            raise ValueError("Expected data URL with mime type")
        return value


def decode_frame(data_url: str) -> np.ndarray:
    """Convert a browser data URL (base64-encoded) into a numpy array image."""

    header, b64_data = data_url.split(",", 1)
    raw_bytes = base64.b64decode(b64_data)

    max_bytes = settings.max_frame_kb * 1024
    if len(raw_bytes) > max_bytes:
        raise HTTPException(status_code=413, detail="Frame exceeds size limit")

    np_arr = np.frombuffer(raw_bytes, dtype=np.uint8)
    try:
        import cv2

        image = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)
        if image is None:
            raise ValueError("cv2.imdecode returned None")
        return image
    except ImportError as exc:
        raise HTTPException(status_code=500, detail=f"cv2 missing: {exc}") from exc


async def process_frame(payload: FramePayload) -> Dict[str, Any]:
    """Decode frame, run the shared eye-tracking pipeline, and format response."""

    if not settings.browser_streaming_enabled:
        raise HTTPException(
            status_code=503,
            detail="Browser streaming disabled. Please fall back to the local Flask service.",
        )

    if not payload.user_id or not payload.module_id:
        raise HTTPException(status_code=400, detail="user_id and module_id are required.")

    image = await run_in_threadpool(decode_frame, payload.frame_base64)
    status, frame_data = await run_in_threadpool(
        browser_tracker.process_remote_frame,
        image,
        payload.user_id,
        payload.module_id,
        payload.section_id,
    )

    response = {
        "success": True,
        "frame_id": payload.frame_id,
        "user_id": payload.user_id,
        "module_id": payload.module_id,
        "section_id": payload.section_id,
        "timestamp": payload.timestamp,
        "status": status,
        "metrics": status.get("metrics"),
        "current_frame": frame_data,
    }
    return response


@app.post("/api/frames")
@app.post("/api/stream-frame")
async def ingest_frame(payload: FramePayload) -> JSONResponse:
    """HTTP endpoint hit by fetch() in the browser."""

    result = await process_frame(payload)
    return JSONResponse(content=result)


class WebSocketLimiter:
    """Simple token bucket per client to enforce FPS contracts."""

    def __init__(self, max_fps: int) -> None:
        self.max_fps = max_fps
        self.allowance = max_fps
        self.last_check = time.monotonic()

    def consume(self) -> bool:
        current = time.monotonic()
        time_passed = current - self.last_check
        self.last_check = current
        self.allowance += time_passed * self.max_fps
        if self.allowance > self.max_fps:
            self.allowance = self.max_fps
        if self.allowance < 1.0:
            return False
        self.allowance -= 1.0
        return True


async def websocket_settings(_: Request) -> WebSocketLimiter:
    return WebSocketLimiter(settings.max_client_fps)


@app.websocket("/ws/frames")
async def websocket_frames(websocket: WebSocket, limiter: WebSocketLimiter = Depends(websocket_settings)) -> None:
    """Optional WebSocket channel for smoother streaming."""

    await websocket.accept()
    try:
        while True:
            message = await websocket.receive_text()
            try:
                payload = FramePayload(**json.loads(message))
            except Exception as exc:  # noqa: BLE001
                await websocket.send_json({"error": f"Invalid payload: {exc}"})
                continue

            if not limiter.consume():
                await websocket.send_json({"error": "Rate limit exceeded"})
                continue

            result = await process_frame(payload)
            await websocket.send_json(result)
    except WebSocketDisconnect:
        logger.info("WebSocket disconnected")


@app.get("/healthz")
async def health() -> Dict[str, Any]:
    """Simple readiness check."""

    return {
        "status": "ok",
        "camera_enabled": settings.camera_enabled,
        "model": settings.model_name,
        "browser_streaming_enabled": settings.browser_streaming_enabled,
    }


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("web_stream_service:app", host="0.0.0.0", port=8000, reload=True)

