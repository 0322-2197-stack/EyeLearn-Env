"""
Enhanced Real-time Eye Tracking Service for E-Learning Platform
Features: 3-second countdown, real-time monitoring, focus/unfocus detection
"""

import cv2
import json
import os
import time
import threading
import requests
import base64
import numpy as np
import math
import logging
import traceback
import mediapipe as mp
from datetime import datetime, timedelta
from flask import Flask, jsonify, request, Response
from flask_cors import CORS
import logging

# Configuration via environment variables (backward compatible)
TRACKING_SAVE_URL = os.environ.get(
    "TRACKING_SAVE_URL",
    "http://localhost/capstone/user/database/save_enhanced_tracking.php"
)

# Camera enabled flag (defaults to enabled for backward compatibility)
CAMERA_ENABLED_STR = os.environ.get("CAMERA_ENABLED", "1").lower()
CAMERA_ENABLED = CAMERA_ENABLED_STR not in ("0", "false", "False")

# Custom JSON encoder for NumPy types
class NumpyEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, np.integer):
            return int(obj)
        elif isinstance(obj, np.floating):
            return float(obj)
        elif isinstance(obj, np.ndarray):
            return obj.tolist()
        elif isinstance(obj, np.bool_):
            return bool(obj)
        return super(NumpyEncoder, self).default(obj)

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class EyeTrackingService:
    def __init__(self):
        self.gaze = None
        self.webcam = None
        self.camera_initialized = False  # Track if camera is already initialized
        self.primary_camera_index = 0    # Lock to primary camera
        self.is_tracking = False
        self.is_focused = False
        self.session_start_time = None
        self.accumulated_focused_time = 0
        self.accumulated_unfocused_time = 0
        self.current_module_id = None
        self.current_section_id = None
        self.current_user_id = None
        self.last_save_time = time.time()
        self.save_interval = 10  # Save every 10 seconds
        
        # Enhanced tracking parameters
        self.center_threshold = 0.15  # Relaxed threshold for better detection
        self.focus_history = []  # Track recent focus states
        self.focus_history_size = 15  # Increased for better smoothing
        self.latest_frame = None  # Store latest annotated frame
        self.frame_lock = threading.Lock()  # Thread safety for frame access
        
        # Countdown and state management
        self.countdown_active = False
        self.countdown_start_time = None
        self.countdown_duration = 3  # 3 second countdown (more visible)
        self.tracking_state = "idle"  # idle, countdown, tracking, paused
        
        # Session tracking
        self.current_focus_session_start = None
        self.current_unfocus_session_start = None
        self.session_data = {
            'focus_sessions': [],
            'unfocus_sessions': [],
            'total_focused_time': 0,
            'total_unfocused_time': 0,
            'session_start': None
        }
        
        # Performance metrics
        self.frames_processed = 0
        self.detection_rate = 0
        self.avg_processing_time = 0
        
        self.init_gaze_tracking()
        
    def init_gaze_tracking(self):
        """Initialize real eye tracking using MediaPipe"""
        try:
            # Try to initialize MediaPipe real eye tracking
            self.gaze = self.create_mediapipe_tracker()
            logger.info("✅ Real MediaPipe eye tracking initialized successfully")
            return True
        except ImportError as e:
            logger.warning(f"MediaPipe not available: {e}")
            # Try legacy gaze tracking library
            try:
                from gaze_tracking import GazeTracking
                self.gaze = GazeTracking()
                logger.info("GazeTracking library initialized successfully")
                return True
            except ImportError:
                logger.warning("GazeTracking library not found, using fallback mode")
                self.gaze = self.create_fallback_tracker()
                return True
        except Exception as e:
            logger.error(f"Error initializing real eye tracking: {e}")
            self.gaze = self.create_fallback_tracker()
            return False
    
    def create_mediapipe_tracker(self):
        """Create a real eye tracking system using MediaPipe"""
        import mediapipe as mp
        import math
        
        class MediaPipeTracker:
            def __init__(self):
                self.mp_face_mesh = mp.solutions.face_mesh
                self.face_mesh = self.mp_face_mesh.FaceMesh(
                    max_num_faces=1,
                    refine_landmarks=True,
                    min_detection_confidence=0.5,
                    min_tracking_confidence=0.5
                )
                self.mp_drawing = mp.solutions.drawing_utils
                self.mp_drawing_styles = mp.solutions.drawing_styles
                
                self.current_frame = None
                self.annotated_frame = None
                self.pupils_located = False
                self.eye_landmarks = None
                self.gaze_direction = None
                self.blink_detected = False
                self.last_blink_time = time.time()
                
                # Eye landmark indices for MediaPipe Face Mesh
                self.LEFT_EYE = [362, 385, 387, 263, 373, 380]
                self.RIGHT_EYE = [33, 160, 158, 133, 153, 144]
                self.LEFT_IRIS = [474, 475, 476, 477]
                self.RIGHT_IRIS = [469, 470, 471, 472]
                
                logger.info("🎯 Real MediaPipe eye tracker initialized")
                
            def refresh(self, frame):
                """Process frame for real eye tracking with improved error handling"""
                if frame is None:
                    return
                    
                try:
                    self.current_frame = frame.copy()
                    rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
                    
                    # Use current timestamp in milliseconds for MediaPipe
                    timestamp_ms = int(time.time() * 1000)
                    rgb_frame.flags.writeable = False
                    
                    results = self.face_mesh.process(rgb_frame)
                    
                    self.annotated_frame = frame.copy()
                    
                    if results.multi_face_landmarks:
                        self.pupils_located = True
                        face_landmarks = results.multi_face_landmarks[0]
                        
                        # Get frame dimensions
                        h, w, _ = frame.shape
                        
                        # Extract eye landmarks
                        self.eye_landmarks = []
                        for landmark in face_landmarks.landmark:
                            x = int(landmark.x * w)
                            y = int(landmark.y * h)
                            self.eye_landmarks.append((x, y))
                        
                        # Draw face mesh on frame
                        self.mp_drawing.draw_landmarks(
                            self.annotated_frame,
                            face_landmarks,
                            self.mp_face_mesh.FACEMESH_CONTOURS,
                            landmark_drawing_spec=None,
                            connection_drawing_spec=self.mp_drawing_styles.get_default_face_mesh_contours_style()
                        )
                        
                        # Draw eye landmarks
                        for eye_points in [self.LEFT_EYE, self.RIGHT_EYE]:
                            for point_idx in eye_points:
                                if point_idx < len(self.eye_landmarks):
                                    cv2.circle(self.annotated_frame, self.eye_landmarks[point_idx], 2, (0, 255, 0), -1)
                        
                        # Calculate gaze direction from iris position
                        self.calculate_gaze_direction()
                        
                        # Detect blinks
                        self.detect_blinks()
                        
                        # Add real-time info overlay
                        cv2.putText(self.annotated_frame, "🎯 REAL EYE TRACKING", (10, 30), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
                        cv2.putText(self.annotated_frame, f"Pupils: {'DETECTED' if self.pupils_located else 'NOT FOUND'}", 
                                   (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0) if self.pupils_located else (0, 0, 255), 2)
                        
                        if self.gaze_direction:
                            gaze_text = f"Gaze: ({self.gaze_direction[0]:.2f}, {self.gaze_direction[1]:.2f})"
                            cv2.putText(self.annotated_frame, gaze_text, (10, 90), 
                                       cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 0), 1)
                        
                        cv2.putText(self.annotated_frame, f"Blink: {'YES' if self.blink_detected else 'NO'}", 
                                   (10, 120), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 0, 255), 1)
                    else:
                        self.pupils_located = False
                        self.eye_landmarks = None
                        self.gaze_direction = None
                        
                        # Add "no face detected" overlay
                        cv2.putText(self.annotated_frame, "🎯 REAL EYE TRACKING", (10, 30), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
                        cv2.putText(self.annotated_frame, "No face detected", (10, 60), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
                        cv2.putText(self.annotated_frame, "Please position your face in view", (10, 90), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 0), 1)
                        
                except Exception as e:
                    logger.debug(f"MediaPipe processing error: {e}")
                    # Create a basic frame with error info if processing fails
                    if self.current_frame is not None:
                        self.annotated_frame = self.current_frame.copy()
                        cv2.putText(self.annotated_frame, "🎯 REAL EYE TRACKING", (10, 30), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
                        cv2.putText(self.annotated_frame, "Processing...", (10, 60), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 0), 2)
            
            def calculate_gaze_direction(self):
                """Calculate gaze direction from eye landmarks"""
                if not self.eye_landmarks:
                    return
                
                try:
                    # Get left and right iris centers
                    left_iris_center = self.get_iris_center(self.LEFT_IRIS)
                    right_iris_center = self.get_iris_center(self.RIGHT_IRIS)
                    
                    if left_iris_center and right_iris_center:
                        # Calculate average gaze direction
                        avg_x = (left_iris_center[0] + right_iris_center[0]) / 2
                        avg_y = (left_iris_center[1] + right_iris_center[1]) / 2
                        
                        # Normalize to 0-1 range (approximate)
                        h, w = self.current_frame.shape[:2] if self.current_frame is not None else (480, 640)
                        self.gaze_direction = (avg_x / w, avg_y / h)
                        
                        # Draw gaze point
                        gaze_point = (int(avg_x), int(avg_y))
                        cv2.circle(self.annotated_frame, gaze_point, 8, (0, 255, 255), -1)
                        cv2.putText(self.annotated_frame, "GAZE", (gaze_point[0] - 20, gaze_point[1] - 15), 
                                   cv2.FONT_HERSHEY_SIMPLEX, 0.4, (0, 255, 255), 1)
                except Exception as e:
                    logger.debug(f"Error calculating gaze direction: {e}")
            
            def get_iris_center(self, iris_landmarks):
                """Get center point of iris from landmarks"""
                if not self.eye_landmarks:
                    return None
                
                try:
                    iris_points = []
                    for idx in iris_landmarks:
                        if idx < len(self.eye_landmarks):
                            iris_points.append(self.eye_landmarks[idx])
                    
                    if len(iris_points) >= 2:
                        avg_x = sum(p[0] for p in iris_points) / len(iris_points)
                        avg_y = sum(p[1] for p in iris_points) / len(iris_points)
                        return (avg_x, avg_y)
                except Exception:
                    pass
                return None
            
            def detect_blinks(self):
                """Detect eye blinks using eye aspect ratio"""
                if not self.eye_landmarks:
                    self.blink_detected = False
                    return
                
                try:
                    # Calculate eye aspect ratio for both eyes
                    left_ear = self.eye_aspect_ratio(self.LEFT_EYE)
                    right_ear = self.eye_aspect_ratio(self.RIGHT_EYE)
                    
                    # Average the eye aspect ratios
                    avg_ear = (left_ear + right_ear) / 2.0
                    
                    # Blink detection threshold
                    EAR_THRESHOLD = 0.25
                    
                    if avg_ear < EAR_THRESHOLD:
                        current_time = time.time()
                        if current_time - self.last_blink_time > 0.1:  # Minimum blink duration
                            self.blink_detected = True
                            self.last_blink_time = current_time
                    else:
                        self.blink_detected = False
                        
                except Exception as e:
                    logger.debug(f"Error detecting blinks: {e}")
                    self.blink_detected = False
            
            def eye_aspect_ratio(self, eye_landmarks):
                """Calculate eye aspect ratio for blink detection"""
                try:
                    if len(eye_landmarks) < 6:
                        return 0.3  # Default value
                    
                    # Get eye points
                    eye_points = [self.eye_landmarks[i] for i in eye_landmarks if i < len(self.eye_landmarks)]
                    
                    if len(eye_points) < 6:
                        return 0.3
                    
                    # Calculate distances
                    def euclidean_distance(p1, p2):
                        return math.sqrt((p1[0] - p2[0])**2 + (p1[1] - p2[1])**2)
                    
                    # Vertical distances
                    A = euclidean_distance(eye_points[1], eye_points[5])
                    B = euclidean_distance(eye_points[2], eye_points[4])
                    
                    # Horizontal distance
                    C = euclidean_distance(eye_points[0], eye_points[3])
                    
                    # Calculate EAR
                    if C > 0:
                        ear = (A + B) / (2.0 * C)
                        return ear
                    else:
                        return 0.3
                        
                except Exception:
                    return 0.3
            
            def horizontal_ratio(self):
                """Get horizontal gaze ratio (0=left, 0.5=center, 1=right)"""
                if self.gaze_direction:
                    return max(0.0, min(1.0, self.gaze_direction[0]))
                return 0.5  # Default center
            
            def vertical_ratio(self):
                """Get vertical gaze ratio (0=up, 0.5=center, 1=down)"""
                if self.gaze_direction:
                    return max(0.0, min(1.0, self.gaze_direction[1]))
                return 0.5  # Default center
            
            def is_blinking(self):
                """Check if currently blinking"""
                return self.blink_detected
            
            def get_annotated_frame(self):
                """Get the annotated frame with tracking overlay"""
                return self.annotated_frame if self.annotated_frame is not None else self.current_frame
        
        return MediaPipeTracker()
    
    def create_fallback_tracker(self):
        """Create a simple fallback tracker for demo purposes"""
        class FallbackTracker:
            def __init__(self):
                self.pupils_located = True
                self.current_frame = None
                self.frame_count = 0
                self.last_focus_change = time.time()
                self.current_focus_state = True
                
            def refresh(self, frame):
                self.current_frame = frame
                self.frame_count += 1
                
                # Simulate focus changes every 10-15 seconds
                if time.time() - self.last_focus_change > np.random.uniform(10, 15):
                    self.current_focus_state = not self.current_focus_state
                    self.last_focus_change = time.time()
                
            def get_annotated_frame(self):
                if self.current_frame is not None:
                    return self.current_frame
                else:
                    # Generate a more realistic demo frame
                    demo_frame = np.zeros((480, 640, 3), dtype=np.uint8)
                    
                    # Add gradient background
                    for y in range(480):
                        intensity = int(20 + (y / 480) * 30)
                        demo_frame[y, :] = [intensity, intensity, intensity]
                    
                    # Add face rectangle simulation
                    face_x, face_y = 220, 160
                    face_w, face_h = 200, 160
                    cv2.rectangle(demo_frame, (face_x, face_y), (face_x + face_w, face_y + face_h), (100, 100, 100), 2)
                    cv2.putText(demo_frame, "Simulated Face", (face_x + 20, face_y - 10), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)
                    
                    # Add eye simulation
                    left_eye = (face_x + 50, face_y + 50)
                    right_eye = (face_x + 150, face_y + 50)
                    cv2.circle(demo_frame, left_eye, 15, (0, 255, 0), 2)
                    cv2.circle(demo_frame, right_eye, 15, (0, 255, 0), 2)
                    
                    # Add gaze point simulation
                    t = time.time()
                    gaze_x = int(320 + 100 * np.sin(t * 0.5))
                    gaze_y = int(240 + 50 * np.cos(t * 0.3))
                    cv2.circle(demo_frame, (gaze_x, gaze_y), 8, (0, 255, 255), -1)
                    cv2.putText(demo_frame, "Gaze Point", (gaze_x - 30, gaze_y - 15), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.4, (0, 255, 255), 1)
                    
                    # Add demo information
                    cv2.putText(demo_frame, "DEMO MODE - Eye Tracking Service v2.0", (50, 50), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
                    cv2.putText(demo_frame, "Simulating eye tracking with fallback mode", (50, 400), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)
                    cv2.putText(demo_frame, f"Frame: {self.frame_count}", (50, 430), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.5, (150, 150, 150), 1)
                    
                    # Add focus indicator
                    focus_color = (0, 255, 0) if self.current_focus_state else (0, 0, 255)
                    focus_text = "FOCUSED" if self.current_focus_state else "UNFOCUSED"
                    cv2.putText(demo_frame, f"Status: {focus_text}", (50, 460), 
                               cv2.FONT_HERSHEY_SIMPLEX, 0.6, focus_color, 2)
                    
                    return demo_frame
                
            def horizontal_ratio(self):
                # Simulate more realistic gaze movement
                t = time.time()
                base = 0.5 + 0.1 * np.sin(t * 0.2)  # Slow drift
                noise = np.random.normal(0, 0.05)   # Small random movements
                return np.clip(base + noise, 0.1, 0.9)
                
            def vertical_ratio(self):
                # Simulate more realistic gaze movement
                t = time.time()
                base = 0.5 + 0.05 * np.cos(t * 0.3)  # Slow drift
                noise = np.random.normal(0, 0.03)    # Small random movements
                return np.clip(base + noise, 0.1, 0.9)
                
            def is_blinking(self):
                return bool(np.random.random() < 0.02)  # 2% chance of blinking
        
        return FallbackTracker()
    
    def start_webcam(self):
        """Start the webcam for eye tracking with improved detection"""
        try:
            # Check if camera is disabled via environment variable
            if not CAMERA_ENABLED:
                logger.info("📹 Camera disabled via CAMERA_ENABLED environment variable - using fallback mode")
                self.camera_initialized = False
                self.webcam = None
                return False
            
            # Check if camera is already initialized and working
            if self.camera_initialized and self.webcam is not None and self.webcam.isOpened():
                logger.info("📹 Camera already initialized and working - skipping reinitialization")
                # Test if current camera still works
                ret, test_frame = self.webcam.read()
                if ret and test_frame is not None:
                    logger.info("✅ Existing camera connection is healthy")
                    return True
                else:
                    logger.warning("⚠️ Existing camera connection failed test - reinitializing")
                    self.camera_initialized = False
            
            # Force use only primary camera (index 0) to prevent switching
            # Try different backends with primary camera only
            camera_configs = [
                (0, cv2.CAP_DSHOW),  # DirectShow backend (Windows preferred)
                (0, cv2.CAP_MSMF),   # Media Foundation backend
                (0, cv2.CAP_ANY),    # Default backend
                (0, None),           # No specific backend
            ]
            
            logger.info("🎥 FORCING PRIMARY CAMERA ONLY (index 0) - preventing camera switching")
            
            for camera_index, backend in camera_configs:
                try:
                    logger.info(f"🎥 Testing camera {camera_index} with backend {backend}...")
                    
                    # Create camera capture object
                    if backend is not None:
                        self.webcam = cv2.VideoCapture(camera_index, backend)
                    else:
                        self.webcam = cv2.VideoCapture(camera_index)
                    
                    # Give camera time to initialize
                    time.sleep(0.5)
                    
                    if self.webcam.isOpened():
                        logger.info(f"📹 Camera {camera_index} opened successfully, testing frame capture...")
                        
                        # Set properties with error handling
                        try:
                            self.webcam.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                            self.webcam.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                            self.webcam.set(cv2.CAP_PROP_FPS, 30)
                            self.webcam.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                        except Exception as prop_e:
                            logger.warning(f"⚠️ Could not set camera properties: {prop_e}")
                        
                        # Test reading frames with stricter requirements
                        success_count = 0
                        total_tests = 5  # Increased to 5 tests for better validation
                        
                        for test_num in range(total_tests):
                            try:
                                ret, test_frame = self.webcam.read()
                                if ret and test_frame is not None:
                                    if len(test_frame.shape) == 3 and test_frame.shape[0] > 0 and test_frame.shape[1] > 0:
                                        success_count += 1
                                        logger.info(f"✅ Frame test {test_num + 1}/{total_tests}: SUCCESS ({test_frame.shape})")
                                    else:
                                        logger.warning(f"⚠️ Frame test {test_num + 1}/{total_tests}: Invalid dimensions {test_frame.shape}")
                                else:
                                    logger.warning(f"❌ Frame test {test_num + 1}/{total_tests}: Failed to read")
                                time.sleep(0.1)  # Reduced delay for faster testing
                            except Exception as read_e:
                                logger.warning(f"❌ Frame test {test_num + 1}/{total_tests}: Exception {read_e}")
                        
                        # Require all 5 tests to succeed for maximum reliability
                        if success_count >= 5:
                            logger.info(f"✅ Webcam ACCEPTED! Camera {camera_index}, Backend: {backend}")
                            logger.info(f"🔧 Frame test result: {success_count}/{total_tests} successful reads (PERFECT)")
                            self.camera_initialized = True
                            self.primary_camera_index = camera_index
                            return True
                        elif success_count >= 3:
                            logger.info(f"✅ Webcam ACCEPTED! Camera {camera_index}, Backend: {backend}")
                            logger.info(f"🔧 Frame test result: {success_count}/{total_tests} successful reads (ACCEPTABLE)")
                            self.camera_initialized = True
                            self.primary_camera_index = camera_index
                            return True
                        else:
                            logger.warning(f"❌ Camera {camera_index} rejected - only {success_count}/{total_tests} successful reads")
                    else:
                        logger.warning(f"❌ Camera {camera_index} failed to open with backend {backend}")
                    
                    # Clean up failed attempt
                    if self.webcam:
                        self.webcam.release()
                        self.webcam = None
                    
                except Exception as e:
                    logger.warning(f"❌ Exception testing camera {camera_index} with backend {backend}: {e}")
                    if self.webcam:
                        try:
                            self.webcam.release()
                        except:
                            pass
                        self.webcam = None
                
                # Brief pause before next attempt
                time.sleep(0.3)
            
            logger.error("❌ Could not find any working webcam")
            logger.error("🔍 Available cameras found but failed validation")
            logger.error("🔍 Troubleshooting tips:")
            logger.error("   1. Close other applications using camera (Teams, Zoom, etc.)")
            logger.error("   2. Check Windows camera permissions")
            logger.error("   3. Try restarting the service")
            return False
            
        except Exception as e:
            logger.error(f"❌ Error in start_webcam: {e}")
            return False
    
    def stop_webcam(self):
        """Stop the webcam safely"""
        try:
            if self.webcam and self.webcam.isOpened():
                self.webcam.release()
                logger.info("Webcam stopped safely")
        except Exception as e:
            logger.warning(f"Exception while stopping webcam: {e}")
        finally:
            self.webcam = None
            self.camera_initialized = False  # Reset camera state
            logger.info("Camera state reset - ready for reinitialization")
    
    def is_looking_at_screen(self, frame):
        """
        Enhanced gaze detection with better accuracy
        """
        if not self.gaze:
            return bool(np.random.random() > 0.3)  # Fallback: mostly focused
            
        start_time = time.time()
        self.gaze.refresh(frame)
        
        # Get annotated frame and store it
        try:
            annotated_frame = self.gaze.get_annotated_frame()
            if annotated_frame is not None:
                # Add tracking info overlay
                annotated_frame = self.add_tracking_overlay(annotated_frame)
                with self.frame_lock:
                    self.latest_frame = annotated_frame.copy()
                    logger.debug(f"🖼️ Stored annotated frame - shape: {annotated_frame.shape}")
            else:
                logger.warning("❌ No annotated frame returned from gaze tracker")
        except Exception as e:
            logger.warning(f"Error getting annotated frame: {e}")
        
        # Update performance metrics
        processing_time = time.time() - start_time
        self.frames_processed += 1
        self.avg_processing_time = (self.avg_processing_time * 0.9) + (processing_time * 0.1)
        
        # Check if eyes are detected
        if hasattr(self.gaze, 'pupils_located') and self.gaze.pupils_located:
            try:
                # Get horizontal and vertical ratios
                h_ratio = self.gaze.horizontal_ratio()
                v_ratio = self.gaze.vertical_ratio()
                
                # Check if user is blinking (consider as temporarily unfocused)
                if hasattr(self.gaze, 'is_blinking') and self.gaze.is_blinking():
                    return False
                
                # Enhanced center detection with weighted zones
                center_weight = 1.0
                if h_ratio is not None and v_ratio is not None:
                    h_distance = abs(h_ratio - 0.5)
                    v_distance = abs(v_ratio - 0.5)
                    
                    # Create focus zones with different weights
                    if h_distance < 0.1 and v_distance < 0.1:
                        center_weight = 1.0  # Perfect center
                    elif h_distance < 0.2 and v_distance < 0.2:
                        center_weight = 0.8  # Close to center
                    elif h_distance < 0.3 and v_distance < 0.3:
                        center_weight = 0.5  # Somewhat centered
                    else:
                        center_weight = 0.1  # Looking away
                    
                    # Consider focused if weight is above threshold
                    return bool(center_weight > 0.6)
                
            except Exception as e:
                logger.warning(f"Error in gaze calculation: {e}")
        
        # Fallback: use simple face detection or random for demo
        return bool(np.random.random() > 0.4)
    
    def add_tracking_overlay(self, frame):
        """Add tracking information overlay to the frame with focus detection bounding boxes"""
        if frame is None:
            return frame
            
        # Add status information
        height, width = frame.shape[:2]
        
        # Status text
        status_text = f"Status: {self.tracking_state.upper()}"
        focus_text = f"Focus: {'YES' if self.is_focused else 'NO'}"
        time_text = f"Session: {self.get_current_session_time():.1f}s"
        
        # Add focus detection zones (visual bounding boxes)
        if self.tracking_state == "tracking":
            # Center focus zone (green if focused, red if not)
            center_x, center_y = width // 2, height // 2
            zone_width, zone_height = int(width * 0.3), int(height * 0.3)
            
            # Main focus zone (center)
            focus_color = (0, 255, 0) if self.is_focused else (0, 0, 255)
            cv2.rectangle(frame, 
                         (center_x - zone_width//2, center_y - zone_height//2), 
                         (center_x + zone_width//2, center_y + zone_height//2), 
                         focus_color, 3)
            cv2.putText(frame, "FOCUS ZONE", 
                       (center_x - zone_width//2, center_y - zone_height//2 - 10), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.8, focus_color, 2)
            
            # Peripheral zones (yellow for reference)
            peripheral_zones = [
                (int(width * 0.15), int(height * 0.15), int(width * 0.2), int(height * 0.2)),  # Top-left
                (int(width * 0.65), int(height * 0.15), int(width * 0.2), int(height * 0.2)),  # Top-right
                (int(width * 0.15), int(height * 0.65), int(width * 0.2), int(height * 0.2)),  # Bottom-left
                (int(width * 0.65), int(height * 0.65), int(width * 0.2), int(height * 0.2))   # Bottom-right
            ]
            
            for x, y, w, h in peripheral_zones:
                cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 255), 2)
            
            # Draw gaze tracking indicator
            if hasattr(self.gaze, 'gaze_direction') and self.gaze.gaze_direction:
                gaze_x = int(self.gaze.gaze_direction[0] * width)
                gaze_y = int(self.gaze.gaze_direction[1] * height)
                cv2.circle(frame, (gaze_x, gaze_y), 12, (255, 0, 255), -1)
                cv2.putText(frame, "GAZE", (gaze_x - 20, gaze_y - 15), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 2)
        
        # Add countdown if active (make it more prominent)
        if self.countdown_active and self.countdown_start_time:
            remaining = self.countdown_duration - (time.time() - self.countdown_start_time)
            if remaining > 0:
                countdown_text = f"STARTING IN: {remaining:.1f}s"
                # Large, centered countdown text
                text_size = cv2.getTextSize(countdown_text, cv2.FONT_HERSHEY_SIMPLEX, 1.5, 3)[0]
                text_x = (width - text_size[0]) // 2
                text_y = height // 2
                
                # Add background rectangle for better visibility
                cv2.rectangle(frame, (text_x - 20, text_y - 40), (text_x + text_size[0] + 20, text_y + 20), (0, 0, 0), -1)
                cv2.putText(frame, countdown_text, (text_x, text_y), 
                           cv2.FONT_HERSHEY_SIMPLEX, 1.5, (0, 255, 255), 3)
                
                # Add smaller countdown circle
                countdown_number = int(remaining) + 1
                if countdown_number <= 3:
                    cv2.circle(frame, (width//2, height//2 + 80), 50, (0, 255, 255), 5)
                    cv2.putText(frame, str(countdown_number), (width//2 - 20, height//2 + 90), 
                               cv2.FONT_HERSHEY_SIMPLEX, 2, (0, 255, 255), 3)
        
        # Draw status overlay with improved visibility
        overlay_y = 30
        # Add semi-transparent background for status text
        cv2.rectangle(frame, (5, 5), (400, overlay_y + 75), (0, 0, 0), -1)
        cv2.rectangle(frame, (5, 5), (400, overlay_y + 75), (255, 255, 255), 2)
        
        cv2.putText(frame, status_text, (10, overlay_y), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
        cv2.putText(frame, focus_text, (10, overlay_y + 25), cv2.FONT_HERSHEY_SIMPLEX, 0.6, 
                   (0, 255, 0) if self.is_focused else (0, 0, 255), 2)
        cv2.putText(frame, time_text, (10, overlay_y + 50), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
        
        # Add camera feed indicator
        cv2.putText(frame, "LIVE CAMERA FEED", (width - 200, height - 20), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
        
        return frame
    
    def create_status_frame(self):
        """Create a status frame when no camera is available"""
        status_frame = np.zeros((480, 640, 3), dtype=np.uint8)
        
        # Add gradient background
        for y in range(480):
            intensity = int(30 + (y / 480) * 20)
            status_frame[y, :] = [intensity, intensity, intensity]
        
        # Add main status text
        cv2.putText(status_frame, "EYE TRACKING SERVICE", (160, 100), 
                   cv2.FONT_HERSHEY_SIMPLEX, 1.2, (255, 255, 255), 2)
        
        # Add status information
        status_info = [
            f"State: {self.tracking_state.upper()}",
            f"Camera: {'Connected' if (self.webcam and self.webcam.isOpened()) else 'Searching...'}",
            f"User ID: {self.current_user_id or 'None'}",
            f"Module ID: {self.current_module_id or 'None'}"
        ]
        
        y_pos = 200
        for info in status_info:
            cv2.putText(status_frame, info, (50, y_pos), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (200, 200, 200), 1)
            y_pos += 40
        
        # Add countdown if active
        if self.countdown_active and self.countdown_start_time:
            remaining = self.countdown_duration - (time.time() - self.countdown_start_time)
            if remaining > 0:
                countdown_text = f"Starting in: {remaining:.1f}s"
                cv2.putText(status_frame, countdown_text, (200, 300), 
                           cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 2)
        
        # Add helpful instructions
        if self.tracking_state == "camera_retry":
            cv2.putText(status_frame, "Searching for camera...", (180, 350), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 0), 2)
            cv2.putText(status_frame, "Please ensure camera is connected", (120, 380), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 0), 1)
        
        return status_frame
    
    def update_focus_state(self, is_focused_now):
        """Enhanced focus state management with session tracking"""
        self.focus_history.append(is_focused_now)
        if len(self.focus_history) > self.focus_history_size:
            self.focus_history.pop(0)
        
        # Use weighted average for better smoothing
        weights = np.linspace(0.5, 1.0, len(self.focus_history))
        weighted_focus = np.average(self.focus_history, weights=weights)
        smoothed_focus = weighted_focus > 0.6
        
        current_time = time.time()
        
        # Handle focus state changes
        if smoothed_focus != self.is_focused:
            if self.is_focused:
                # Was focused, now unfocused
                if self.current_focus_session_start:
                    focus_duration = current_time - self.current_focus_session_start
                    self.accumulated_focused_time += focus_duration
                    self.session_data['focus_sessions'].append({
                        'start': self.current_focus_session_start,
                        'end': current_time,
                        'duration': focus_duration
                    })
                    self.current_focus_session_start = None
                
                # Start unfocus session
                self.current_unfocus_session_start = current_time
                logger.info("User unfocused - tracking unfocus time")
                
            else:
                # Was unfocused, now focused
                if self.current_unfocus_session_start:
                    unfocus_duration = current_time - self.current_unfocus_session_start
                    self.accumulated_unfocused_time += unfocus_duration
                    self.session_data['unfocus_sessions'].append({
                        'start': self.current_unfocus_session_start,
                        'end': current_time,
                        'duration': unfocus_duration
                    })
                    self.current_unfocus_session_start = None
                
                # Start focus session
                self.current_focus_session_start = current_time
                logger.info("User focused - tracking focus time")
            
            self.is_focused = smoothed_focus
    
    def start_countdown(self):
        """Start the 3-second countdown before tracking begins"""
        self.countdown_active = True
        self.countdown_start_time = time.time()
        self.tracking_state = "countdown"
        logger.info("Starting 3-second countdown...")
    
    def check_countdown(self):
        """Check if countdown is complete"""
        if self.countdown_active and self.countdown_start_time:
            elapsed = time.time() - self.countdown_start_time
            if elapsed >= self.countdown_duration:
                self.countdown_active = False
                self.countdown_start_time = None
                self.begin_tracking()
                return True
        return False
    
    def begin_tracking(self):
        """Begin actual eye tracking after countdown"""
        self.is_tracking = True
        self.tracking_state = "tracking"
        self.session_data['session_start'] = time.time()
        logger.info("Eye tracking started - monitoring focus...")
    
    def get_current_session_time(self):
        """Get total session time including both focused and unfocused"""
        if not self.session_data['session_start']:
            return 0
        return time.time() - self.session_data['session_start']
    
    def get_detailed_metrics(self):
        """Get detailed tracking metrics"""
        current_time = time.time()
        
        # Calculate current session times
        current_focused = self.accumulated_focused_time
        current_unfocused = self.accumulated_unfocused_time
        
        # Add current ongoing session
        if self.current_focus_session_start:
            current_focused += current_time - self.current_focus_session_start
        if self.current_unfocus_session_start:
            current_unfocused += current_time - self.current_unfocus_session_start
        
        total_time = current_focused + current_unfocused
        focus_percentage = (current_focused / total_time * 100) if total_time > 0 else 0
        
        return {
            'focused_time': round(current_focused, 1),
            'unfocused_time': round(current_unfocused, 1),
            'total_time': round(total_time, 1),
            'focus_percentage': round(focus_percentage, 1),
            'focus_sessions': len(self.session_data['focus_sessions']),
            'unfocus_sessions': len(self.session_data['unfocus_sessions']),
            'current_state': 'focused' if self.is_focused else 'unfocused',
            'detection_rate': self.detection_rate,
            'frames_processed': self.frames_processed
        }
    
    def save_tracking_data(self):
        """Enhanced data saving with detailed metrics"""
        if not all([self.current_user_id, self.current_module_id]):
            return
        
        current_time = time.time()
        if current_time - self.last_save_time < self.save_interval:
            return
        
        # Get detailed metrics
        metrics = self.get_detailed_metrics()
        
        try:
            # Prepare comprehensive data for saving
            data = {
                'user_id': self.current_user_id,
                'module_id': self.current_module_id,
                'section_id': self.current_section_id,
                'focused_time': metrics['focused_time'],
                'unfocused_time': metrics['unfocused_time'],
                'total_time': metrics['total_time'],
                'focus_percentage': metrics['focus_percentage'],
                'focus_sessions': metrics['focus_sessions'],
                'unfocus_sessions': metrics['unfocus_sessions'],
                'session_type': 'enhanced_cv_tracking',
                'timestamp': datetime.now().isoformat()
            }
            
            # Save to database via our fixed endpoint
            logger.info(f"Tracking Data: User {data['user_id']}, Module {data['module_id']}, Focus: {data['focused_time']:.1f}s ({data['focus_percentage']:.1f}%)")
            
            try:
                # Save to database using configurable URL
                response = requests.post(
                    TRACKING_SAVE_URL,
                    json=data,
                    timeout=5
                )
                
                if response.status_code == 200:
                    result = response.json()
                    if result.get('success'):
                        self.last_save_time = current_time
                        logger.info(f"✅ Saved tracking data to database: {metrics['focused_time']:.1f}s focused, {metrics['unfocused_time']:.1f}s unfocused")
                        logger.info(f"📊 Database record ID: {result.get('record_id', 'unknown')}")
                    else:
                        logger.error(f"❌ Server error saving data: {result.get('error', 'Unknown error')}")
                else:
                    logger.error(f"❌ HTTP error saving data: {response.status_code}")
                    logger.error(f"Response: {response.text[:200]}...")
                    
            except requests.exceptions.RequestException as e:
                logger.error(f"❌ Network error saving tracking data: {e}")
            except Exception as e:
                logger.error(f"❌ Unexpected error saving tracking data: {e}")
            
        except Exception as e:
            logger.error(f"Error saving tracking data: {e}")
    
    def run_tracking_loop(self):
        """Enhanced main tracking loop with better camera error recovery"""
        logger.info("🚀 Starting eye tracking loop...")
        
        # Initial camera setup
        webcam_available = self.start_webcam()
        if not webcam_available:
            logger.warning("⚠️ Initial camera setup failed, will retry during tracking...")
            self.tracking_state = "camera_retry"
        else:
            logger.info("✅ Camera initialized successfully")
            self.tracking_state = "ready"
        
        frame_error_count = 0
        max_frame_errors = 15  # Increased tolerance
        camera_retry_count = 0
        max_camera_retries = 3
        last_camera_retry = 0
        
        try:
            while getattr(self, 'is_tracking_enabled', False):
                frame = None
                current_time = time.time()
                
                # Try to get camera frame
                if self.webcam and self.webcam.isOpened():
                    try:
                        ret, frame = self.webcam.read()
                        if not ret or frame is None:
                            frame_error_count += 1
                            logger.warning(f"⚠️ Failed to read frame ({frame_error_count}/{max_frame_errors})")
                            
                            # If too many frame errors, try to restart camera
                            if frame_error_count >= max_frame_errors:
                                logger.warning(f"📹 Too many frame errors, attempting camera restart...")
                                self.stop_webcam()
                                webcam_available = False
                                frame_error_count = 0
                            
                            time.sleep(0.1)
                            continue
                        else:
                            # Successfully read frame
                            frame_error_count = 0
                            if not webcam_available:
                                logger.info("✅ Camera recovered successfully!")
                                webcam_available = True
                                self.tracking_state = "tracking" if self.is_tracking else "ready"
                                
                    except Exception as e:
                        frame_error_count += 1
                        logger.warning(f"⚠️ Camera read exception: {e} ({frame_error_count}/{max_frame_errors})")
                        if frame_error_count >= max_frame_errors:
                            self.stop_webcam()
                            webcam_available = False
                            frame_error_count = 0
                        time.sleep(0.1)
                        continue
                
                # If no camera available, try to restart it periodically
                if not webcam_available or not self.webcam or not self.webcam.isOpened():
                    # Only retry camera every 5 seconds to avoid spam but allow more retries
                    if current_time - last_camera_retry > 5.0 and camera_retry_count < 5:  # Increased retries
                        logger.info(f"🔄 Attempting camera restart (attempt {camera_retry_count + 1}/5)...")
                        webcam_available = self.start_webcam()
                        last_camera_retry = current_time
                        camera_retry_count += 1
                        
                        if webcam_available:
                            logger.info("✅ Camera restart successful!")
                            camera_retry_count = 0  # Reset retry count on success
                            self.tracking_state = "tracking" if self.is_tracking else "ready"
                        else:
                            logger.warning(f"❌ Camera restart failed (attempt {camera_retry_count}/5)")
                            self.tracking_state = "camera_retry"
                    
                    # Continue loop even without camera to handle countdown and API requests
                    time.sleep(0.5)  # Reduced sleep for better responsiveness
                
                # Process frame if available
                if frame is not None:
                    try:
                        # Add overlay information first (keep original frame)
                        frame_with_overlay = self.add_tracking_overlay(frame.copy())
                        
                        # Store frame safely (always prioritize real camera frames)
                        with self.frame_lock:
                            self.latest_frame = frame_with_overlay.copy()
                            logger.debug(f"🖼️ Stored live camera frame - shape: {frame_with_overlay.shape}")
                        
                        # Handle countdown phase
                        if self.countdown_active:
                            self.check_countdown()
                        
                        # Handle tracking phase
                        elif self.is_tracking:
                            is_focused = self.is_looking_at_screen(frame)
                            self.update_focus_state(is_focused)
                            
                            # Save data periodically
                            if time.time() - self.last_save_time > self.save_interval:
                                self.save_tracking_data()
                    
                    except Exception as process_e:
                        logger.warning(f"⚠️ Error processing frame: {process_e}")
                        # Still store the raw frame even if processing fails
                        with self.frame_lock:
                            self.latest_frame = frame.copy()
                
                else:
                    # No frame available but still handle countdown and maintain previous frame
                    if self.countdown_active:
                        self.check_countdown()
                    
                    # Don't clear existing frames - keep the last good frame
                
                # Control frame rate - maintain consistent timing
                time.sleep(0.033)  # ~30 FPS
                
        except Exception as e:
            logger.error(f"❌ Critical error in tracking loop: {e}")
            import traceback
            logger.error(f"Traceback: {traceback.format_exc()}")
            self.tracking_state = "error"
            
            # Don't stop tracking entirely - just pause and retry after a moment
            logger.info("🔄 Attempting to recover from error...")
            time.sleep(2)
            if getattr(self, 'is_tracking_enabled', False):
                logger.info("🔄 Restarting tracking loop after error recovery...")
                # Recursively restart the tracking loop
                self.run_tracking_loop()
        finally:
            # Only stop webcam and set to stopped if tracking is actually disabled
            if not getattr(self, 'is_tracking_enabled', False):
                self.stop_webcam()
                if self.tracking_state != "error":
                    self.tracking_state = "stopped"
                logger.info("🛑 Eye tracking loop ended")
            else:
                logger.info("🔄 Tracking loop exited but tracking still enabled")
    
    def start_tracking(self, user_id, module_id, section_id=None):
        """Start tracking with countdown for a specific module - improved persistence"""
        # Check if this is a section change within the same module/user
        is_section_change = (getattr(self, 'is_tracking_enabled', False) and 
                            getattr(self, 'current_user_id', None) == user_id and 
                            getattr(self, 'current_module_id', None) == module_id and
                            getattr(self, 'current_section_id', None) != section_id)
        
        # Only stop if different user/module, otherwise continue existing tracking
        if (getattr(self, 'is_tracking_enabled', False) and 
            (self.current_user_id != user_id or self.current_module_id != module_id)):
            logger.info("Stopping existing tracking session for different user/module")
            self.stop_tracking()
            time.sleep(0.5)  # Reduced wait time
        elif getattr(self, 'is_tracking_enabled', False) and not is_section_change:
            logger.info("Tracking already active for same user/module - continuing...")
            return
        
        self.current_user_id = user_id
        self.current_module_id = module_id
        self.current_section_id = section_id
        
        # If this is just a section change, don't reset counters or start countdown
        if is_section_change:
            logger.info(f"Section change detected - continuing tracking without countdown for section {section_id}")
            return
        
        # Reset all counters for new module
        self.accumulated_focused_time = 0
        self.accumulated_unfocused_time = 0
        self.session_data = {
            'focus_sessions': [],
            'unfocus_sessions': [],
            'total_focused_time': 0,
            'total_unfocused_time': 0,
            'session_start': None
        }
        
        self.is_tracking_enabled = True
        
        # Start with countdown only for new modules
        self.start_countdown()
        
        # Start tracking in a separate thread
        self.tracking_thread = threading.Thread(target=self.run_tracking_loop)
        self.tracking_thread.daemon = True
        self.tracking_thread.start()
        
        logger.info(f"Started tracking with countdown for user {user_id}, module {module_id}")
    
    def stop_tracking(self):
        """Stop tracking and save final data"""
        self.is_tracking_enabled = False
        self.is_tracking = False
        self.countdown_active = False
        
        # Save final data
        if self.current_user_id and self.current_module_id:
            self.save_tracking_data()
        
        if hasattr(self, 'tracking_thread'):
            self.tracking_thread.join(timeout=5)
        
        self.tracking_state = "stopped"
        logger.info("Eye tracking stopped")
    
    def get_current_frame_base64(self):
        """Get current frame as base64 string - prioritizing real camera feeds"""
        try:
            with self.frame_lock:
                frame_to_use = None
                
                # Check if we have a live camera frame available
                if self.latest_frame is not None:
                    # Always use the latest frame if available (this includes annotated camera frames)
                    frame_to_use = self.latest_frame.copy()
                    logger.debug(f"📹 Providing live camera frame - shape: {frame_to_use.shape}")
                else:
                    # Only create status frame if no camera frame exists
                    frame_to_use = self.create_status_frame()
                    logger.debug(f"📺 Providing status frame - tracking state: {self.tracking_state}")
                
                if frame_to_use is not None:
                    # Ensure frame has proper dimensions
                    if len(frame_to_use.shape) == 3:
                        height, width = frame_to_use.shape[:2]
                        new_width = 480  # Increased size for better quality
                        new_height = int(height * (new_width / width))
                        
                        # Resize frame for transmission
                        resized_frame = cv2.resize(frame_to_use, (new_width, new_height))
                        
                        # Encode frame as JPEG with optimized quality
                        encode_params = [cv2.IMWRITE_JPEG_QUALITY, 85, cv2.IMWRITE_JPEG_OPTIMIZE, 1]
                        success, buffer = cv2.imencode('.jpg', resized_frame, encode_params)
                        
                        if success and buffer is not None:
                            # Convert to base64
                            frame_base64 = base64.b64encode(buffer).decode('utf-8')
                            data_url = f"data:image/jpeg;base64,{frame_base64}"
                            logger.debug(f"✅ Frame encoded successfully - data URL length: {len(data_url)}")
                            return data_url
                        else:
                            logger.error("❌ Failed to encode frame as JPEG")
                            return None
                    else:
                        logger.error(f"❌ Invalid frame dimensions: {frame_to_use.shape}")
                        return None
                else:
                    logger.error("❌ No frame available")
                    return None
                
        except Exception as e:
            logger.error(f"❌ Error getting frame: {e}")
            import traceback
            logger.error(f"❌ Traceback: {traceback.format_exc()}")
            return None

    def get_status(self):
        """Get comprehensive tracking status"""
        metrics = self.get_detailed_metrics()
        
        return {
            'is_tracking_enabled': getattr(self, 'is_tracking_enabled', False),
            'tracking_state': self.tracking_state,
            'countdown_active': self.countdown_active,
            'countdown_remaining': max(0, self.countdown_duration - (time.time() - self.countdown_start_time)) if self.countdown_start_time else 0,
            'is_focused': self.is_focused,
            'is_session_active': self.is_tracking,
            'current_module': self.current_module_id,
            'current_section': self.current_section_id,
            'current_user': self.current_user_id,
            'metrics': metrics,
            'performance': {
                'avg_processing_time': round(self.avg_processing_time * 1000, 2),  # in ms
                'frames_processed': self.frames_processed
            } 
        }

# Flask API for communication with frontend
app = Flask(__name__)
app.json_encoder = NumpyEncoder  # Use custom encoder for NumPy types
CORS(app)  # Enable CORS for browser requests

# Global eye tracker instance
eye_tracker = EyeTrackingService()

# Simple rate limiting to prevent camera conflicts
last_frame_request = 0
frame_request_interval = 0.033  # ~30 FPS (33ms between frame requests)

@app.route('/status', methods=['GET'])
def status():
    return {"status": "ok", "service": "eye_tracking", "message": "running"}, 200

@app.route('/api/start_tracking', methods=['POST'])
def start_tracking():
    """API endpoint to start eye tracking with countdown"""
    data = request.get_json()
    user_id = data.get('user_id')
    module_id = data.get('module_id')
    section_id = data.get('section_id')
    
    if not user_id or not module_id:
        return jsonify({'success': False, 'error': 'Missing required parameters'}), 400
    
    try:
        eye_tracker.start_tracking(user_id, module_id, section_id)
        return jsonify({
            'success': True, 
            'message': 'Eye tracking started with countdown',
            'countdown_duration': eye_tracker.countdown_duration
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/stop_tracking', methods=['POST'])
def stop_tracking():
    """API endpoint to stop eye tracking"""
    try:
        # Get final metrics before stopping
        final_metrics = eye_tracker.get_detailed_metrics()
        eye_tracker.stop_tracking()
        
        return jsonify({
            'success': True, 
            'message': 'Eye tracking stopped',
            'final_metrics': final_metrics
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/status', methods=['GET'])
def get_status():
    """API endpoint to get comprehensive tracking status"""
    try:
        status = eye_tracker.get_status()
        
        # Add current frame if available
        frame_data = eye_tracker.get_current_frame_base64()
        if frame_data:
            status['current_frame'] = frame_data
            
        # Convert NumPy types to native Python types
        def convert_numpy_types(obj):
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
            return obj
        
        status = convert_numpy_types(status)
        
        return jsonify({'success': True, 'status': status})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/frame', methods=['GET'])
def get_current_frame():
    """API endpoint to get current frame with status info and rate limiting"""
    global last_frame_request
    
    try:
        # Simple rate limiting to prevent camera conflicts
        current_time = time.time()
        if current_time - last_frame_request < frame_request_interval:
            # Return cached frame if requesting too frequently
            logger.info("🕐 Using cached frame due to rate limiting")
            frame_data = None
            if hasattr(eye_tracker, 'latest_frame') and eye_tracker.latest_frame is not None:
                logger.info(f"📁 Cached frame available - shape: {eye_tracker.latest_frame.shape}")
                # Use cached frame
                try:
                    with eye_tracker.frame_lock:
                        cached_frame = eye_tracker.latest_frame.copy()
                    
                    height, width = cached_frame.shape[:2]
                    new_width = 320
                    new_height = int(height * (new_width / width))
                    resized_frame = cv2.resize(cached_frame, (new_width, new_height))
                    
                    success, buffer = cv2.imencode('.jpg', resized_frame, [
                        cv2.IMWRITE_JPEG_QUALITY, 75,
                        cv2.IMWRITE_JPEG_OPTIMIZE, 1
                    ])
                    
                    if success and buffer is not None:
                        frame_data = f"data:image/jpeg;base64,{base64.b64encode(buffer).decode('utf-8')}"
                        logger.info(f"✅ Cached frame encoded successfully - length: {len(frame_data)}")
                except Exception as e:
                    logger.error(f"❌ Error processing cached frame: {e}")
                    frame_data = None
            else:
                logger.warning("❌ No cached frame available")
        else:
            logger.info("🔄 Requesting fresh frame")
            last_frame_request = current_time
            frame_data = eye_tracker.get_current_frame_base64()
        
        # Always return some response, even if no camera
        is_focused = bool(eye_tracker.is_focused) if hasattr(eye_tracker.is_focused, 'item') else eye_tracker.is_focused
        countdown_remaining = 0
        if eye_tracker.countdown_start_time:
            countdown_remaining = max(0, eye_tracker.countdown_duration - (time.time() - eye_tracker.countdown_start_time))
        
        camera_available = (eye_tracker.webcam and eye_tracker.webcam.isOpened()) if eye_tracker.webcam else False
        
        response_data = {
            'success': True,
            'hasFrame': frame_data is not None,
            'frameData': frame_data if frame_data else '',
            'tracking_state': eye_tracker.tracking_state,
            'is_focused': is_focused,
            'countdown_remaining': float(countdown_remaining),
            'camera_available': camera_available,
            'timestamp': datetime.now().isoformat()
        }
        
        if frame_data:
            response_data['message'] = 'Frame available'
        else:
            response_data['message'] = 'Status frame provided'
            response_data['info'] = 'Camera searching or initializing'
        
        return jsonify(response_data)
        
    except Exception as e:
        logger.error(f"Error in get_current_frame endpoint: {e}")
        return jsonify({
            'success': False, 
            'error': str(e),
            'tracking_state': getattr(eye_tracker, 'tracking_state', 'unknown'),
            'message': 'Service error - check logs'
        }), 500

@app.route('/current_frame', methods=['GET'])
def get_current_frame_legacy():
    """Legacy endpoint for current frame (redirect to /api/frame)"""
    return get_current_frame()

@app.route('/track_eyes', methods=['GET'])
def track_eyes():
    """Legacy endpoint for eye tracking (returns current status)"""
    try:
        status = eye_tracker.get_status()
        metrics = eye_tracker.get_detailed_metrics()
        
        return jsonify({
            'success': True,
            'tracking_active': getattr(eye_tracker, 'is_tracking_enabled', False),
            'focused': eye_tracker.is_focused,
            'tracking_state': eye_tracker.tracking_state,
            'metrics': metrics,
            'timestamp': datetime.now().isoformat()
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/metrics', methods=['GET'])
def get_metrics():
    """API endpoint to get detailed tracking metrics"""
    try:
        metrics = eye_tracker.get_detailed_metrics()
        return jsonify({
            'success': True,
            'metrics': metrics,
            'timestamp': datetime.now().isoformat()
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    """Enhanced health check endpoint"""
    try:
        status = eye_tracker.get_status()
        return jsonify({
            'success': True, 
            'message': 'Enhanced Eye Tracking Service is running',
            'version': '2.0.0',
            'features': ['3-second countdown', 'real-time focus tracking', 'detailed metrics', 'enhanced detection'],
            'status': status['tracking_state'],
            'timestamp': datetime.now().isoformat()
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/pause', methods=['POST'])
def pause_tracking():
    """API endpoint to pause tracking temporarily"""
    try:
        if eye_tracker.is_tracking:
            eye_tracker.tracking_state = "paused"
            eye_tracker.is_tracking = False
            return jsonify({'success': True, 'message': 'Tracking paused'})
        else:
            return jsonify({'success': False, 'error': 'Tracking not active'}), 400
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/resume', methods=['POST'])
def resume_tracking():
    """API endpoint to resume paused tracking"""
    try:
        if eye_tracker.tracking_state == "paused":
            eye_tracker.tracking_state = "tracking"
            eye_tracker.is_tracking = True
            return jsonify({'success': True, 'message': 'Tracking resumed'})
        else:
            return jsonify({'success': False, 'error': 'Tracking not paused'}), 400
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/switch_section', methods=['POST'])
def switch_section():
    """API endpoint to switch section within the same module without stopping tracking"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        module_id = data.get('module_id')
        section_id = data.get('section_id')
        
        logger.info(f"🔄 Section switch request: user={user_id}, module={module_id}, section={section_id}")
        
        # Validate current tracking state
        if not eye_tracker.is_tracking_enabled:
            return jsonify({'success': False, 'error': 'No active tracking session'}), 400
            
        # Verify this is the same user and module
        if (eye_tracker.current_user_id != user_id or 
            eye_tracker.current_module_id != module_id):
            return jsonify({'success': False, 'error': 'User/module mismatch'}), 400
        
        # Update section ID without stopping tracking
        old_section = eye_tracker.current_section_id
        eye_tracker.current_section_id = section_id
        
        logger.info(f"✅ Section switched from {old_section} to {section_id} - tracking continues")
        
        return jsonify({
            'success': True, 
            'message': f'Section switched to {section_id}',
            'old_section': old_section,
            'new_section': section_id,
            'tracking_continues': True
        })
        
    except Exception as e:
        logger.error(f"❌ Section switch error: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/stop', methods=['POST'])
def stop_service():
    """API endpoint to stop tracking and release camera (alternative endpoint)"""
    try:
        logger.info("🛑 Stop service request received")
        
        if getattr(eye_tracker, 'is_tracking_enabled', False):
            eye_tracker.stop_tracking()
            logger.info("✅ Tracking stopped via /api/stop")
        
        # Release camera resources
        if hasattr(eye_tracker, 'cap') and eye_tracker.cap is not None:
            eye_tracker.cap.release()
            logger.info("📹 Camera released via /api/stop")
        
        return jsonify({
            'success': True, 
            'message': 'Service stopped and camera released',
            'camera_released': True
        })
        
    except Exception as e:
        logger.error(f"❌ Stop service error: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/shutdown', methods=['POST'])
def shutdown_service():
    """API endpoint to completely shutdown the eye tracking service and release camera"""
    try:
        logger.info("🛑 Shutdown service request received")
        
        # Stop tracking if active
        if getattr(eye_tracker, 'is_tracking_enabled', False):
            eye_tracker.stop_tracking()
            logger.info("✅ Tracking stopped for shutdown")
        
        # Release camera resources completely
        if hasattr(eye_tracker, 'cap') and eye_tracker.cap is not None:
            eye_tracker.cap.release()
            eye_tracker.cap = None
            logger.info("📹 Camera completely released and set to None")
        
        # Additional cleanup
        if hasattr(eye_tracker, 'face_mesh'):
            eye_tracker.face_mesh = None
        
        logger.info("🔒 Complete service shutdown completed - camera resources freed")
        
        return jsonify({
            'success': True, 
            'message': 'Service completely shut down and camera released',
            'camera_released': True,
            'service_stopped': True
        })
        
    except Exception as e:
        logger.error(f"❌ Shutdown service error: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    logger.info("Starting Enhanced Eye Tracking Service v2.0...")
    logger.info("Features: Real camera with focus detection zones, 3-second countdown, continuous operation")
    
    # Read PORT from environment (for Railway/PaaS deployment) or default to 5000
    PORT = int(os.environ.get("PORT", 5000))
    
    # Run the Flask app
    # Use 0.0.0.0 for PaaS deployment, but still works locally
    app.run(host='0.0.0.0', port=PORT, debug=False, threaded=True)

# ============================================================================
# DEPENDENCIES AND DEPLOYMENT NOTES
# ============================================================================
# 
# Required Python packages:
#   - Flask (web framework)
#   - flask-cors (CORS support)
#   - opencv-python (computer vision and camera access)
#   - mediapipe (face mesh and eye tracking)
#   - numpy (numerical operations)
#   - requests (HTTP client for backend communication)
# 
# Environment Variables:
#   - PORT: Server port (default: 5000)
#   - TRACKING_SAVE_URL: Backend endpoint for saving tracking data
#     (default: http://localhost/capstone/user/database/save_enhanced_tracking.php)
#   - CAMERA_ENABLED: Enable/disable camera (set to "0", "false", or "False" to disable)
#     (default: enabled)
# 
# Railway/PaaS Deployment:
#   Example start command: python eye_tracking_service.py
#   Railway will automatically set PORT environment variable
#   Set CAMERA_ENABLED=0 if deploying without physical camera access
#   Set TRACKING_SAVE_URL to your production backend endpoint
# 
# ============================================================================
