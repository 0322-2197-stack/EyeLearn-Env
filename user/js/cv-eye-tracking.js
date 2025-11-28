/**
 * Enhanced Computer Vision Eye Tracking System v2.6
 * Features: Instant activation, seamless transitions, crash-resistant operation
 * OPTIMIZATIONS:
 * - Instant service startup: All services start immediately in parallel
 * - Seamless transitions: Module switching preserves service connection
 * - Quick health checks: 2-second timeout for faster initialization
 * - Connection preservation: Service stays alive during transitions
 * - Parallel processing: All initialization tasks run simultaneously
 * - Zero-delay activation: No waiting periods between services
 * - Error-resistant video updates: Graceful handling of connection issues
 * - Transition-aware: Video updates pause during module/section changes
 * - Interval cleanup: Prevents accumulation of background processes
 * - Crash prevention: Multiple fallback strategies and timeout handling
 * - Singleton management: Proper cleanup and fresh instance creation
 * - Nuclear reset: Complete cleanup when normal methods fail
 */

const DEFAULT_PYTHON_SERVICE_URL = 'http://127.0.0.1:5000';
const ENABLE_BROWSER_EYE_STREAMING = typeof window !== 'undefined'
    ? window.ENABLE_BROWSER_EYE_STREAMING !== false
    : true;
const DEFAULT_BROWSER_CAPTURE_FPS = typeof window !== 'undefined' && Number(window.BROWSER_EYE_FPS) > 0
    ? Number(window.BROWSER_EYE_FPS)
    : 7;
const FOCUS_ATTENTION_THRESHOLD = typeof window !== 'undefined' && typeof window.EYE_FOCUS_THRESHOLD === 'number'
    ? window.EYE_FOCUS_THRESHOLD
    : 0.5;

function getGlobalPythonServiceUrl() {
    if (typeof window !== 'undefined') {
        const candidate = window.PYTHON_SERVICE_URL || window.pythonServiceUrl;
        if (typeof candidate === 'string' && candidate.trim().length > 0) {
            return candidate.replace(/\/$/, '');
        }
    }
    return DEFAULT_PYTHON_SERVICE_URL;
}

class CVEyeTrackingSystem {
    constructor(moduleId, sectionId = null) {
        this.moduleId = moduleId;
        this.sectionId = sectionId;
        this.isConnected = false;
        this.isTracking = false;
        this.dormantMode = false; // New dormant mode flag
        this.pythonServiceUrl = getGlobalPythonServiceUrl();
        this.checkInterval = null;
        this.statusUpdateInterval = null;
        this.videoUpdateInterval = null;
        this.fullscreenVideoInterval = null;
        this.totalTime = 0;
        this.lastStatusUpdate = 0;
        this.countdownActive = false;
        this.trackingState = 'idle';
        this.countdownShownForModule = false;
        this.cameraErrorShown = false; // Prevent multiple camera error dialogs
        this.healthMonitorInterval = null; // Health monitoring interval
        this.reconnectionAttempts = 0; // Track reconnection attempts
        this.maxReconnectionAttempts = 5; // Max reconnection attempts before giving up
        this.isTransitioning = false; // Flag to indicate module/section transitions
        this.instanceId = Date.now() + '_' + Math.random().toString(36).substr(2, 9); // Unique instance ID
        this.dataSaveInterval = null; // For periodic data saving to dashboard

        // Frame continuity tracking
        this.lastFrameTime = 0;
        this.frameCount = 0;
        this.consecutiveFrameFailures = 0;

        // Enhanced timer system
        this.timers = {
            sessionStart: null,
            sessionTime: 0,
            focusedTime: 0,
            unfocusedTime: 0,
            currentFocusStart: null,
            currentUnfocusStart: null,
            isCurrentlyFocused: false,
            baseFocusedTime: 0,
            baseUnfocusedTime: 0
        };

        this.metrics = {
            focused_time: 0,
            unfocused_time: 0,
            total_time: 0,
            focus_percentage: 0
        };

        // Browser streaming configuration
        this.browserStreamingEnabled = ENABLE_BROWSER_EYE_STREAMING &&
            typeof navigator !== 'undefined' &&
            navigator.mediaDevices &&
            typeof navigator.mediaDevices.getUserMedia === 'function';
        this.captureFps = DEFAULT_BROWSER_CAPTURE_FPS;
        this.frameIntervalMs = Math.max(150, Math.round(1000 / this.captureFps));
        this.cameraStream = null;
        this.captureCanvas = null;
        this.captureCtx = null;
        this.frameIntervalId = null;
        this.frameBackoffTimeout = null;
        this.frameBackoffMs = 0;
        this.latestBackendMetrics = null;
        this.currentUserId = null;

        console.log(`🆕 CVEyeTrackingSystem instance created: ${this.instanceId}`);

        // Only initialize if not in dormant mode
        if (moduleId !== 'dormant_mode') {
            // Small delay to ensure clean initialization
            setTimeout(() => {
                this.init();
            }, 100);
        } else {
            this.dormantMode = true;
            console.log('🛌 Eye tracking initialized in dormant mode');
        }
    }

    getWidgetVideoElement() {
        if (typeof document === 'undefined') {
            return null;
        }
        const scopedVideo = document.querySelector('#cv-eye-tracking-interface #tracking-video');
        if (scopedVideo) {
            return scopedVideo;
        }
        return document.getElementById('tracking-video');
    }

    getCaptureVideoElement() {
        if (typeof document === 'undefined') {
            return null;
        }
        return document.getElementById('tracking-video-source') || this.getWidgetVideoElement();
    }

    async init() {
        console.log(`🎯 Initializing Enhanced CV Eye Tracking System v2.6... (Instance: ${this.instanceId})`);
        console.log('Features: Instant activation, seamless transitions, crash-resistant switching');

        if (this.browserStreamingEnabled) {
            try {
                await this.initializeBrowserStreamingMode();
                return;
            } catch (streamError) {
                console.warn('⚠️ Browser-based streaming failed, falling back to legacy service:', streamError);
                this.browserStreamingEnabled = false;
                this.stopBrowserCamera();
                this.stopLocalFrameStreaming();
            }
        }

        // Clean up any existing intervals before starting new ones
        this.cleanupAllIntervals();

        // Additional safety: Clean up any stale DOM elements
        this.cleanupInterface();

        // Check if Python service is running (with quick timeout for speed)
        await this.checkServiceHealth(true); // true = quick check

        if (this.isConnected) {
            // Load previous session data
            try {
                const previousSession = await this.fetchSessionData();
                if (previousSession) {
                    console.log('📊 Restored previous session metrics');
                }
            } catch (error) {
                console.warn('⚠️ Could not restore previous session:', error);
            }

            // Check if countdown should be shown (only for new modules)
            const shouldShowCountdown = !this.hasCountdownBeenShownForModule();

            if (shouldShowCountdown) {
                console.log('🎬 New module - instant startup with countdown UI');

                // Mark countdown as shown and start everything immediately
                this.markCountdownShownForModule();

                // Start ALL services immediately in parallel (no delays)
                const startupPromises = [
                    this.startTracking(),
                    this.setupStatusUpdates(),
                    this.displayTrackingInterface(),
                    this.initializeTimers()
                ];

                // Show countdown UI immediately while services start
                this.showCountdownNotification();

                // Wait for all services to be ready
                await Promise.all(startupPromises);

                console.log('⚡ All services started instantly during countdown');
            } else {
                console.log('📝 Section/module change - instant activation');

                // Start everything immediately in parallel
                await Promise.all([
                    this.startTracking(),
                    this.setupStatusUpdates(),
                    this.displayTrackingInterface(),
                    this.initializeTimers()
                ]);

                console.log('⚡ Eye tracking activated instantly (no countdown)');
            }

            // Start health monitoring (only if not already running)
            if (!this.healthMonitorInterval) {
                this.startHealthMonitoring();
            }

            // Start periodic data saving to dashboard
            this.startDataSaving();
            this.startMetricsSaving();
        } else {
            this.showServiceError();
        }
    }

    async initializeBrowserStreamingMode() {
        console.log('🌐 Using browser-based eye tracking stream');

        // Clean up any existing resources
        this.cleanupAllIntervals();
        this.cleanupInterface();

        await this.checkServiceHealth(true);

        this.displayTrackingInterface({ useLocalVideo: false });
        this.initializeTimers();
        this.handleFocusChange(false);
        this.isTracking = true;

        // Resolve user context once for payloads
        try {
            this.currentUserId = await this.getCurrentUserId();
        } catch (userError) {
            console.warn('⚠️ Could not resolve user ID, defaulting to 1', userError);
            this.currentUserId = 1;
        }

        await this.startBrowserCamera();
        this.startLocalFrameStreaming();
        this.startDataSaving();
        this.startHealthMonitoring();
    }

    setupStatusUpdates() {
        if (this.browserStreamingEnabled) {
            return;
        }

        // Check status every 2 seconds
        this.statusUpdateInterval = setInterval(async () => {
            await this.updateStatus();
        }, 2000);
    }

    startHealthMonitoring() {
        // Monitor service health every 10 seconds
        this.healthMonitorInterval = setInterval(async () => {
            if (!this.isConnected) {
                console.log('🔍 Health monitor: Service disconnected, attempting reconnection...');
                await this.attemptReconnection();
            }
        }, 10000);

        console.log('💓 Health monitoring started - checking every 10 seconds');
    }

    async startBrowserCamera() {
        if (!this.browserStreamingEnabled) {
            throw new Error('Browser streaming disabled');
        }

        // Enhanced browser compatibility check
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const errorMsg = 'Modern camera API not available. Please use a modern browser (Chrome, Edge, Firefox).';
            console.error('❌', errorMsg);
            if (!this.cameraErrorShown) {
                this.cameraErrorShown = true;
                this.showCameraError('BROWSER_NOT_SUPPORTED', errorMsg);
            }
            throw new Error(errorMsg);
        }

        console.log('📹 Requesting camera access...');

        try {
            // Request camera with optimal settings for eye tracking
            this.cameraStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 360 },
                    frameRate: { ideal: this.captureFps, max: this.captureFps },
                    facingMode: 'user' // Prefer front-facing camera
                },
                audio: false
            });

            console.log('✅ Camera access granted');

            // Get the actual video track settings
            const videoTrack = this.cameraStream.getVideoTracks()[0];
            if (videoTrack) {
                const settings = videoTrack.getSettings();
                console.log(`📹 Camera settings: ${settings.width}x${settings.height} @ ${settings.frameRate}fps`);
            }

            const videoElement = this.getCaptureVideoElement();
            if (videoElement) {
                videoElement.srcObject = this.cameraStream;
                videoElement.muted = true;
                videoElement.playsInline = true;
                videoElement.autoplay = true;

                // Enhanced video element event handling
                videoElement.onloadedmetadata = () => {
                    console.log('📹 Video metadata loaded');
                    if (typeof videoElement.play === 'function') {
                        videoElement.play().catch((playError) => {
                            console.warn('⚠️ Video autoplay prevented:', playError.message);
                        });
                    }
                };

                videoElement.onloadeddata = () => {
                    console.log('✅ Video stream ready');
                };

                // Attempt initial play
                if (typeof videoElement.play === 'function') {
                    const playPromise = videoElement.play();
                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch((playError) => {
                            console.warn('⚠️ Initial play prevented:', playError.message);
                        });
                    }
                }
            }

            // Create canvas for frame capture with optimized settings
            this.captureCanvas = document.createElement('canvas');
            this.captureCtx = this.captureCanvas.getContext('2d', {
                willReadFrequently: true,
                alpha: false // Disable alpha for better performance
            });

            console.log(`✅ Browser camera initialized successfully @ ${this.captureFps} FPS`);
        } catch (error) {
            console.error('❌ Camera access error:', error);

            // Provide specific error messages based on error type
            let errorType = 'UNKNOWN';
            let errorMessage = error.message;

            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                errorType = 'PERMISSION_DENIED';
                errorMessage = 'Camera permission denied. Please allow camera access in your browser settings.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                errorType = 'NO_CAMERA';
                errorMessage = 'No camera found. Please connect a webcam and try again.';
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                errorType = 'CAMERA_IN_USE';
                errorMessage = 'Camera is already in use by another application. Please close other apps using the camera.';
            } else if (error.name === 'OverconstrainedError') {
                errorType = 'UNSUPPORTED_CONSTRAINTS';
                errorMessage = 'Camera does not support requested settings. Trying with default settings...';
            } else if (error.name === 'TypeError') {
                errorType = 'INVALID_CONSTRAINTS';
                errorMessage = 'Invalid camera configuration.';
            }

            if (!this.cameraErrorShown) {
                this.cameraErrorShown = true;
                this.showCameraError(errorType, errorMessage);
            }

            throw error;
        }
    }

    stopBrowserCamera() {
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach(track => track.stop());
            this.cameraStream = null;
        }
        this.captureCanvas = null;
        this.captureCtx = null;
    }

    startLocalFrameStreaming() {
        if (!this.browserStreamingEnabled) {
            return;
        }

        this.stopLocalFrameStreaming();
        this.frameBackoffMs = 0;

        this.frameIntervalId = setInterval(() => {
            this.sendFrameToBackend();
        }, this.frameIntervalMs);

        console.log(`📡 Started browser frame streaming every ${this.frameIntervalMs}ms`);
    }

    stopLocalFrameStreaming() {
        if (this.frameIntervalId) {
            clearInterval(this.frameIntervalId);
            this.frameIntervalId = null;
        }
        if (this.frameBackoffTimeout) {
            clearTimeout(this.frameBackoffTimeout);
            this.frameBackoffTimeout = null;
        }
    }

    async sendFrameToBackend() {
        if (!this.browserStreamingEnabled || !this.cameraStream || !this.captureCtx || this.isTransitioning) {
            return;
        }

        const videoElement = this.getCaptureVideoElement();
        if (!videoElement || videoElement.readyState < 2) {
            return;
        }

        const width = videoElement.videoWidth || 640;
        const height = videoElement.videoHeight || 360;

        this.captureCanvas.width = width;
        this.captureCanvas.height = height;
        this.captureCtx.drawImage(videoElement, 0, 0, width, height);
        const frameData = this.captureCanvas.toDataURL('image/jpeg', 0.7);

        const payload = {
            frame_base64: frameData,
            user_id: String(this.currentUserId || 1),
            module_id: String(this.moduleId),
            section_id: this.sectionId ? String(this.sectionId) : null,
            fps: this.captureFps
        };

        try {
            const response = await fetch(`${this.pythonServiceUrl}/api/frames`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            this.handleFrameResult(result);
            this.frameBackoffMs = 0;
        } catch (error) {
            this.handleFrameSendError(error);
        }
    }

    handleFrameResult(result) {
        this.isConnected = true;
        this.latestBackendMetrics = result.metrics || null;
        if (result.metrics) {
            this.metrics = result.metrics;
        }

        // Update video display with processed frame
        if (result.current_frame) {
            const videoElement = this.getWidgetVideoElement();
            if (videoElement) {
                if (videoElement.tagName === 'IMG') {
                    videoElement.src = result.current_frame;
                    console.log('📹 Frame image loaded successfully');
                } else {
                    console.warn('Video element is not an IMG tag:', videoElement.tagName);
                }
            } else {
                console.warn('⚠️ Video element not found');
            }
        } else {
            // Log occasionally if no frame is received
            if (Math.random() < 0.1) {
                console.log('ℹ️ No processed frame in response');
            }
        }

        const statusPayload = result.status || {};
        const metrics = this.latestBackendMetrics || {};

        let attentionScore = typeof metrics.attention_score === 'number'
            ? metrics.attention_score
            : null;

        if (attentionScore === null && typeof metrics.focus_percentage === 'number') {
            attentionScore = metrics.focus_percentage / 100;
        }

        let isFocused;
        if (typeof statusPayload.is_focused === 'boolean') {
            isFocused = statusPayload.is_focused;
        } else if (attentionScore !== null) {
            isFocused = attentionScore >= FOCUS_ATTENTION_THRESHOLD;
        } else {
            isFocused = this.timers.isCurrentlyFocused;
        }

        if (attentionScore === null) {
            attentionScore = isFocused ? 1 : 0;
        }

        this.metrics.attention_score = attentionScore;

        if (this.timers.isCurrentlyFocused !== isFocused) {
            this.handleFocusChange(isFocused);
        }

        this.updateTimerDisplay();
    }

    handleFrameSendError(error) {
        console.warn('⚠️ Frame send error:', error?.message || error);
        this.isConnected = false;

        if (this.frameIntervalId) {
            clearInterval(this.frameIntervalId);
            this.frameIntervalId = null;
        }

        this.frameBackoffMs = this.frameBackoffMs === 0 ? 2000 : Math.min(this.frameBackoffMs * 2, 15000);
        if (this.frameBackoffTimeout) {
            clearTimeout(this.frameBackoffTimeout);
        }

        this.frameBackoffTimeout = setTimeout(() => {
            if (!this.cameraStream) {
                return;
            }
            console.log('🔄 Retrying frame streaming after backoff');
            this.startLocalFrameStreaming();
        }, this.frameBackoffMs);
    }

    async attemptReconnection() {
        if (this.reconnectionAttempts >= this.maxReconnectionAttempts) {
            console.warn('🚫 Max reconnection attempts reached, stopping automatic reconnection');
            return;
        }

        this.reconnectionAttempts++;
        console.log(`🔄 Reconnection attempt ${this.reconnectionAttempts}/${this.maxReconnectionAttempts}`);

        if (this.browserStreamingEnabled) {
            await this.checkServiceHealth(true);
            if (this.isConnected && this.cameraStream && !this.frameIntervalId) {
                this.startLocalFrameStreaming();
            }
            return;
        }

        // Clean up intervals before reconnection to prevent accumulation
        this.cleanupAllIntervals();

        await this.checkServiceHealth(true); // Quick check

        if (this.isConnected) {
            console.log('✅ Service reconnected successfully!');
            this.reconnectionAttempts = 0; // Reset counter on successful reconnection

            // Restart tracking if it was active
            if (this.isTracking) {
                console.log('🔄 Restarting tracking after reconnection...');
                try {
                    await this.startTracking();
                    this.setupStatusUpdates();
                    this.startVideoUpdates();
                } catch (restartError) {
                    console.warn('⚠️ Error restarting after reconnection:', restartError);
                }
            }
        } else {
            console.warn(`❌ Reconnection attempt ${this.reconnectionAttempts} failed`);
        }
    }

    stopHealthMonitoring() {
        if (this.healthMonitorInterval) {
            clearInterval(this.healthMonitorInterval);
            this.healthMonitorInterval = null;
            console.log('💓 Health monitoring stopped');
        }
    }

    startDataSaving() {
        // Save session data to dashboard every 60 seconds
        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
        }

        this.dataSaveInterval = setInterval(async () => {
            await this.saveSessionData();
        }, 60000); // 60 seconds

        console.log('💾 Dashboard data saving started (60s interval)');
    }

    stopDataSaving() {
        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
            this.dataSaveInterval = null;
            console.log('💾 Dashboard data saving stopped');
        }
    }

    // Enhanced database connection methods
    async getCurrentUserId() {
        try {
            // First try session storage
            const cachedUserId = sessionStorage.getItem('eyetracking_user_id');
            if (cachedUserId) {
                return parseInt(cachedUserId);
            }

            // Try to get from API endpoint
            const response = await fetch('api/get_current_user.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                cache: 'no-cache'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.user_id) {
                    // Cache for session
                    sessionStorage.setItem('eyetracking_user_id', data.user_id);
                    return parseInt(data.user_id);
                }
            }
        } catch (error) {
            console.warn('⚠️ Could not fetch user ID from API:', error);
        }

        // Fallback: try global variable
        if (typeof window.currentUserId !== 'undefined' && window.currentUserId) {
            return parseInt(window.currentUserId);
        }

        // Last resort: try to extract from page
        try {
            const userIdElement = document.querySelector('[data-user-id], #user-id, [class*="user-id"]');
            if (userIdElement) {
                const userId = parseInt(userIdElement.textContent || userIdElement.value);
                if (!isNaN(userId)) {
                    sessionStorage.setItem('eyetracking_user_id', userId);
                    return userId;
                }
            }
        } catch (e) {
            console.warn('⚠️ Could not extract user ID from page:', e);
        }

        // Default fallback
        console.warn('⚠️ Using default user ID 1');
        return 1;
    }

    async saveSessionData() {
        if (!this.isTracking || this.isTransitioning) {
            return;
        }

        try {
            const sessionData = {
                module_id: this.moduleId,
                section_id: this.sectionId,
                session_time: Math.floor(this.timers.sessionTime || 0),
                completion_percentage: typeof window.currentCompletionPercentage !== 'undefined'
                    ? window.currentCompletionPercentage
                    : 0,
                focus_data: {
                    focused_time: Math.floor(this.timers.focusedTime || 0),
                    unfocused_time: Math.floor(this.timers.unfocusedTime || 0),
                    focus_percentage: this.calculateFocusPercentage(),
                    total_time: Math.floor(this.timers.sessionTime || 0)
                }
            };

            // Try multiple database endpoints
            const endpoints = [
                'api/save_eye_tracking_session.php',
                'database/save_eye_tracking_session.php',
                'api/sessions/save',
            ];

            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(sessionData)
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            console.log(`💾 Session data saved via ${endpoint}`);
                            return true;
                        }
                    }
                } catch (error) {
                    // Try next endpoint
                    continue;
                }
            }

            console.warn('⚠️ Could not save session data to any endpoint');
            return false;

        } catch (error) {
            console.warn('⚠️ Error saving session data:', error);
            return false;
        }
    }

    // New method: Save real-time metrics to database
    async saveRealtimeMetrics() {
        if (!this.isTracking || this.isTransitioning) {
            return;
        }

        try {
            const metricsData = {
                user_id: this.currentUserId,
                module_id: this.moduleId,
                section_id: this.sectionId,
                timestamp: new Date().toISOString(),
                metrics: {
                    attention_score: this.metrics.attention_score || 0,
                    focused_time: Math.floor(this.timers.focusedTime || 0),
                    unfocused_time: Math.floor(this.timers.unfocusedTime || 0),
                    focus_percentage: this.calculateFocusPercentage(),
                    session_time: Math.floor(this.timers.sessionTime || 0),
                    is_focused: this.timers.isCurrentlyFocused
                }
            };

            const response = await fetch('api/save_eye_metrics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(metricsData)
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    if (Math.random() < 0.05) { // Log 5% of saves
                        console.log('📊 Real-time metrics saved');
                    }
                    return true;
                }
            }
        } catch (error) {
            if (Math.random() < 0.05) { // Log 5% of errors
                console.warn('⚠️ Error saving real-time metrics:', error);
            }
        }

        return false;
    }

    // New method: Fetch initial session data from database
    async fetchSessionData() {
        try {
            const response = await fetch(`api/get_session_data.php?module_id=${this.moduleId}&section_id=${this.sectionId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.session) {
                    // Restore session data if available
                    this.timers.sessionTime = data.session.session_time || 0;
                    this.timers.focusedTime = data.session.focused_time || 0;
                    this.timers.unfocusedTime = data.session.unfocused_time || 0;
                    console.log('📊 Previous session data loaded');
                    return data.session;
                }
            }
        } catch (error) {
            console.warn('⚠️ Could not fetch previous session data:', error);
        }

        return null;
    }

    // New method: Initialize real-time metrics saving
    startMetricsSaving() {
        // Save metrics every 15 seconds
        if (this.metricsSaveInterval) {
            clearInterval(this.metricsSaveInterval);
        }

        this.metricsSaveInterval = setInterval(async () => {
            await this.saveRealtimeMetrics();
        }, 15000); // 15 seconds

        console.log('📊 Real-time metrics saving started (15s interval)');
    }

    stopMetricsSaving() {
        if (this.metricsSaveInterval) {
            clearInterval(this.metricsSaveInterval);
            this.metricsSaveInterval = null;
            console.log('📊 Real-time metrics saving stopped');
        }
    }

    async initializeBrowserStreamingMode() {
        console.log('🌐 Using browser-based eye tracking stream');

        // Clean up any existing resources
        this.cleanupAllIntervals();
        this.cleanupInterface();

        await this.checkServiceHealth(true);

        this.displayTrackingInterface({ useLocalVideo: false });
        this.initializeTimers();
        this.handleFocusChange(false);
        this.isTracking = true;

        // Resolve user context once for payloads
        try {
            this.currentUserId = await this.getCurrentUserId();
        } catch (userError) {
            console.warn('⚠️ Could not resolve user ID, defaulting to 1', userError);
            this.currentUserId = 1;
        }

        await this.startBrowserCamera();
        this.startLocalFrameStreaming();
        this.startDataSaving();
        this.startHealthMonitoring();
    }

    async init() {
        console.log(`🎯 Initializing Enhanced CV Eye Tracking System v2.6... (Instance: ${this.instanceId})`);
        console.log('Features: Instant activation, seamless transitions, crash-resistant switching');

        if (this.browserStreamingEnabled) {
            try {
                await this.initializeBrowserStreamingMode();
                return;
            } catch (streamError) {
                console.warn('⚠️ Browser-based streaming failed, falling back to legacy service:', streamError);
                this.browserStreamingEnabled = false;
                this.stopBrowserCamera();
                this.stopLocalFrameStreaming();
            }
        }

        // Clean up any existing intervals before starting new ones
        this.cleanupAllIntervals();

        // Additional safety: Clean up any stale DOM elements
        this.cleanupInterface();

        // Check if Python service is running (with quick timeout for speed)
        await this.checkServiceHealth(true); // true = quick check

        if (this.isConnected) {
            // Load previous session data
            try {
                const previousSession = await this.fetchSessionData();
                if (previousSession) {
                    console.log('📊 Restored previous session metrics');
                }
            } catch (error) {
                console.warn('⚠️ Could not restore previous session:', error);
            }

            // Check if countdown should be shown (only for new modules)
            const shouldShowCountdown = !this.hasCountdownBeenShownForModule();

            if (shouldShowCountdown) {
                console.log('🎬 New module - instant startup with countdown UI');

                // Mark countdown as shown and start everything immediately
                this.markCountdownShownForModule();

                // Start ALL services immediately in parallel (no delays)
                const startupPromises = [
                    this.startTracking(),
                    this.setupStatusUpdates(),
                    this.displayTrackingInterface(),
                    this.initializeTimers()
                ];

                // Show countdown UI immediately while services start
                this.showCountdownNotification();

                // Wait for all services to be ready
                await Promise.all(startupPromises);

                console.log('⚡ All services started instantly during countdown');
            } else {
                console.log('📝 Section/module change - instant activation');

                // Start everything immediately in parallel
                await Promise.all([
                    this.startTracking(),
                    this.setupStatusUpdates(),
                    this.displayTrackingInterface(),
                    this.initializeTimers()
                ]);

                console.log('⚡ Eye tracking activated instantly (no countdown)');
            }

            // Start health monitoring (only if not already running)
            if (!this.healthMonitorInterval) {
                this.startHealthMonitoring();
            }

            // Start periodic data saving to dashboard
            this.startDataSaving();
            this.startMetricsSaving();
        } else {
            this.showServiceError();
        }
    }

    async sendFrameToBackend() {
        if (!this.browserStreamingEnabled || !this.cameraStream || !this.captureCtx || this.isTransitioning) {
            return;
        }

        const videoElement = this.getCaptureVideoElement();
        if (!videoElement || videoElement.readyState < 2) {
            return;
        }

        const width = videoElement.videoWidth || 640;
        const height = videoElement.videoHeight || 360;

        this.captureCanvas.width = width;
        this.captureCanvas.height = height;
        this.captureCtx.drawImage(videoElement, 0, 0, width, height);
        const frameData = this.captureCanvas.toDataURL('image/jpeg', 0.7);

        const payload = {
            frame_base64: frameData,
            user_id: String(this.currentUserId || 1),
            module_id: String(this.moduleId),
            section_id: this.sectionId ? String(this.sectionId) : null,
            fps: this.captureFps
        };

        try {
            const response = await fetch(`${this.pythonServiceUrl}/api/frames`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            this.handleFrameResult(result);
            this.frameBackoffMs = 0;
        } catch (error) {
            this.handleFrameSendError(error);
        }
    }

    handleFrameResult(result) {
        this.isConnected = true;
        this.latestBackendMetrics = result.metrics || null;
        if (result.metrics) {
            this.metrics = result.metrics;
        }

        // Update video display with processed frame
        if (result.current_frame) {
            const videoElement = this.getWidgetVideoElement();
            if (videoElement) {
                if (videoElement.tagName === 'IMG') {
                    videoElement.src = result.current_frame;
                    console.log('📹 Frame image loaded successfully');
                } else {
                    console.warn('Video element is not an IMG tag:', videoElement.tagName);
                }
            } else {
                console.warn('⚠️ Video element not found');
            }
        } else {
            // Log occasionally if no frame is received
            if (Math.random() < 0.1) {
                console.log('ℹ️ No processed frame in response');
            }
        }

        const statusPayload = result.status || {};
        const metrics = this.latestBackendMetrics || {};

        let attentionScore = typeof metrics.attention_score === 'number'
            ? metrics.attention_score
            : null;

        if (attentionScore === null && typeof metrics.focus_percentage === 'number') {
            attentionScore = metrics.focus_percentage / 100;
        }

        let isFocused;
        if (typeof statusPayload.is_focused === 'boolean') {
            isFocused = statusPayload.is_focused;
        } else if (attentionScore !== null) {
            isFocused = attentionScore >= FOCUS_ATTENTION_THRESHOLD;
        } else {
            isFocused = this.timers.isCurrentlyFocused;
        }

        if (attentionScore === null) {
            attentionScore = isFocused ? 1 : 0;
        }

        this.metrics.attention_score = attentionScore;

        if (this.timers.isCurrentlyFocused !== isFocused) {
            this.handleFocusChange(isFocused);
        }

        this.updateTimerDisplay();
    }

    handleFrameSendError(error) {
        console.warn('⚠️ Frame send error:', error?.message || error);
        this.isConnected = false;

        if (this.frameIntervalId) {
            clearInterval(this.frameIntervalId);
            this.frameIntervalId = null;
        }

        this.frameBackoffMs = this.frameBackoffMs === 0 ? 2000 : Math.min(this.frameBackoffMs * 2, 15000);
        if (this.frameBackoffTimeout) {
            clearTimeout(this.frameBackoffTimeout);
        }

        this.frameBackoffTimeout = setTimeout(() => {
            if (!this.cameraStream) {
                return;
            }
            console.log('🔄 Retrying frame streaming after backoff');
            this.startLocalFrameStreaming();
        }, this.frameBackoffMs);
    }

    async attemptReconnection() {
        if (this.reconnectionAttempts >= this.maxReconnectionAttempts) {
            console.warn('🚫 Max reconnection attempts reached, stopping automatic reconnection');
            return;
        }

        this.reconnectionAttempts++;
        console.log(`🔄 Reconnection attempt ${this.reconnectionAttempts}/${this.maxReconnectionAttempts}`);

        if (this.browserStreamingEnabled) {
            await this.checkServiceHealth(true);
            if (this.isConnected && this.cameraStream && !this.frameIntervalId) {
                this.startLocalFrameStreaming();
            }
            return;
        }

        // Clean up intervals before reconnection to prevent accumulation
        this.cleanupAllIntervals();

        await this.checkServiceHealth(true); // Quick check

        if (this.isConnected) {
            console.log('✅ Service reconnected successfully!');
            this.reconnectionAttempts = 0; // Reset counter on successful reconnection

            // Restart tracking if it was active
            if (this.isTracking) {
                console.log('🔄 Restarting tracking after reconnection...');
                try {
                    await this.startTracking();
                    this.setupStatusUpdates();
                    this.startVideoUpdates();
                } catch (restartError) {
                    console.warn('⚠️ Error restarting after reconnection:', restartError);
                }
            }
        } else {
            console.warn(`❌ Reconnection attempt ${this.reconnectionAttempts} failed`);
        }
    }

    stopHealthMonitoring() {
        if (this.healthMonitorInterval) {
            clearInterval(this.healthMonitorInterval);
            this.healthMonitorInterval = null;
            console.log('💓 Health monitoring stopped');
        }
    }

    startDataSaving() {
        // Save session data to dashboard every 60 seconds
        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
        }

        this.dataSaveInterval = setInterval(async () => {
            await this.saveSessionData();
        }, 60000); // 60 seconds

        console.log('💾 Dashboard data saving started (60s interval)');
    }

    stopDataSaving() {
        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
            this.dataSaveInterval = null;
            console.log('💾 Dashboard data saving stopped');
        }
    }

    // New method: Save real-time metrics to database
    async saveRealtimeMetrics() {
        if (!this.isTracking || this.isTransitioning) {
            return;
        }

        try {
            const metricsData = {
                user_id: this.currentUserId,
                module_id: this.moduleId,
                section_id: this.sectionId,
                timestamp: new Date().toISOString(),
                metrics: {
                    attention_score: this.metrics.attention_score || 0,
                    focused_time: Math.floor(this.timers.focusedTime || 0),
                    unfocused_time: Math.floor(this.timers.unfocusedTime || 0),
                    focus_percentage: this.calculateFocusPercentage(),
                    session_time: Math.floor(this.timers.sessionTime || 0),
                    is_focused: this.timers.isCurrentlyFocused
                }
            };

            const response = await fetch('api/save_eye_metrics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(metricsData)
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    if (Math.random() < 0.05) { // Log 5% of saves
                        console.log('📊 Real-time metrics saved');
                    }
                    return true;
                }
            }
        } catch (error) {
            if (Math.random() < 0.05) { // Log 5% of errors
                console.warn('⚠️ Error saving real-time metrics:', error);
            }
        }

        return false;
    }

    // New method: Fetch initial session data from database
    async fetchSessionData() {
        try {
            const response = await fetch(`api/get_session_data.php?module_id=${this.moduleId}&section_id=${this.sectionId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.session) {
                    // Restore session data if available
                    this.timers.sessionTime = data.session.session_time || 0;
                    this.timers.focusedTime = data.session.focused_time || 0;
                    this.timers.unfocusedTime = data.session.unfocused_time || 0;
                    console.log('📊 Previous session data loaded');
                    return data.session;
                }
            }
        } catch (error) {
            console.warn('⚠️ Could not fetch previous session data:', error);
        }

        return null;
    }

    // New method: Initialize real-time metrics saving
    startMetricsSaving() {
        // Save metrics every 15 seconds
        if (this.metricsSaveInterval) {
            clearInterval(this.metricsSaveInterval);
        }

        this.metricsSaveInterval = setInterval(async () => {
            await this.saveRealtimeMetrics();
        }, 15000); // 15 seconds

        console.log('📊 Real-time metrics saving started (15s interval)');
    }

    stopMetricsSaving() {
        if (this.metricsSaveInterval) {
            clearInterval(this.metricsSaveInterval);
            this.metricsSaveInterval = null;
            console.log('📊 Real-time metrics saving stopped');
        }
    }

    async startTracking() {
        if (this.browserStreamingEnabled) {
            this.isTracking = true;
            return true;
        }

        if (!this.isConnected) {
            console.log('Cannot start tracking - service not connected');
            return false;
        }

        try {
            // Get user ID from session
            const userId = await this.getCurrentUserId();

            const response = await fetch(`${this.pythonServiceUrl}/api/start_tracking`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    module_id: this.moduleId,
                    section_id: this.sectionId
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.isTracking = true;
                    this.countdownActive = true;
                    console.log(`🎯 Enhanced eye tracking started with ${data.countdown_duration}s countdown`);
                    return true;
                } else {
                    console.error('Failed to start tracking:', data.error);
                    return false;
                }
            } else {
                console.error('HTTP error starting tracking:', response.status);
                return false;
            }
        } catch (error) {
            console.error('Error starting eye tracking:', error);
            return false;
        }
    }

    showCountdownNotification() {
        // Create compact centered countdown overlay - services start during countdown
        const countdownOverlay = document.createElement('div');
        countdownOverlay.id = 'eye-tracking-countdown';
        countdownOverlay.className = 'fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50';
        countdownOverlay.innerHTML = `
            <div class="bg-gray-800 text-white rounded-lg shadow-2xl p-6 text-center" style="width: 220px; height: 220px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                <!-- Header -->
                <div class="flex items-center mb-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-1.5 animate-pulse"></div>
                    <span class="text-xs font-medium">CV Eye Tracking</span>
                </div>
                
                <!-- Rocket Icon Container -->
                <div class="mb-4">
                    <div id="countdown-number" class="text-4xl font-bold mb-1">3</div>
                    <div id="rocket-icon" class="text-3xl hidden">🚀</div>
                </div>
                
                <!-- Status Text -->
                <div class="text-xs text-blue-300" id="countdown-status">
                    Initializing...
                </div>
            </div>
        `;
        document.body.appendChild(countdownOverlay);

        // Start countdown sequence: 3, 2, 1, rocket (services loading during countdown)
        let secondsRemaining = 3;
        const countdownNumber = document.getElementById('countdown-number');
        const rocketIcon = document.getElementById('rocket-icon');
        const statusText = document.getElementById('countdown-status');

        // Update countdown immediately for initial display
        countdownNumber.textContent = secondsRemaining;
        statusText.textContent = `Starting in ${secondsRemaining}...`;

        const countdownInterval = setInterval(() => {
            secondsRemaining--;

            if (secondsRemaining > 0) {
                // Update the display for remaining seconds
                countdownNumber.textContent = secondsRemaining;
                statusText.textContent = `Starting in ${secondsRemaining}...`;
                console.log(`⏱️ Countdown: ${secondsRemaining} seconds remaining (services loading...)`);
            } else {
                // Show rocket and launch message - services should be ready now
                console.log('🚀 Countdown complete - services fully operational!');
                countdownNumber.classList.add('hidden');
                rocketIcon.classList.remove('hidden');
                rocketIcon.classList.add('animate-bounce');
                statusText.textContent = 'Eye Tracking Active! 🚀';

                clearInterval(countdownInterval);

                // Remove countdown overlay after rocket shows
                setTimeout(() => {
                    if (countdownOverlay && countdownOverlay.parentNode) {
                        countdownOverlay.remove();
                    }
                }, 1000); // Keep rocket visible for 1 second
            }
        }, 1000); // 1 second intervals
    }

    async stopTracking() {
        console.log('🛑 Stopping eye tracking...');

        if (this.browserStreamingEnabled) {
            this.stopLocalFrameStreaming();
            this.stopBrowserCamera();
            this.isTracking = false;
            this.cleanupInterface();
            this.stopDataSaving();
            this.stopHealthMonitoring();
            return;
        }

        // Set transitioning flag to prevent video update errors
        if (!this.isTransitioning) {
            this.isTransitioning = true;
        }

        // Don't force disconnect - let service continue running for seamless transitions
        const wasConnected = this.isConnected;

        // Try to stop tracking on the service (but don't force disconnect)
        if (this.isConnected && this.isTracking) {
            try {
                const response = await fetch(`${this.pythonServiceUrl}/api/stop_tracking`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.log('⏹️ Enhanced eye tracking stopped on service');

                        // Display final metrics only if this was a true stop (not a transition)
                        if (data.final_metrics && !this.isTransitioning) {
                            console.log('📊 Final session metrics:', data.final_metrics);
                            this.showFinalMetrics(data.final_metrics);
                        }
                    }
                }
            } catch (error) {
                console.warn('⚠️ Error stopping tracking on service:', error);
            }
        }

        // Clean up local state but preserve connection for seamless transitions
        this.isTracking = false;
        this.countdownActive = false;
        // Don't force disconnect - keep connection alive: this.isConnected = false;

        // Clear all intervals immediately to prevent connection errors
        if (this.statusUpdateInterval) {
            clearInterval(this.statusUpdateInterval);
            this.statusUpdateInterval = null;
        }

        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }

        // Stop video updates immediately to prevent errors
        this.stopVideoUpdates();

        if (this.fullscreenVideoInterval) {
            clearInterval(this.fullscreenVideoInterval);
            this.fullscreenVideoInterval = null;
        }

        // Only stop health monitoring if this is a full shutdown
        if (!this.isTransitioning) {
            this.stopHealthMonitoring();
            this.stopDataSaving();
        }

        // Clean up interface elements
        this.cleanupInterface();

        console.log('✅ Eye tracking stopped and cleaned up (connection preserved for transitions)');

        // Reset transitioning flag after cleanup
        setTimeout(() => {
            this.isTransitioning = false;
        }, 1000); // 1 second delay to ensure clean transition
    }

    cleanupInterface() {
        // Remove tracking interface
        const trackingInterface = document.getElementById('cv-eye-tracking-interface');
        if (trackingInterface) {
            trackingInterface.remove();
            console.log('🗑️ Tracking interface removed');
        }

        // Remove any countdown overlay
        const countdownOverlay = document.getElementById('eye-tracking-countdown');
        if (countdownOverlay) {
            countdownOverlay.remove();
            console.log('🗑️ Countdown overlay removed');
        }

        // Remove any error notifications
        const errorNotifications = document.querySelectorAll('[class*="eye-tracking-error"]');
        errorNotifications.forEach(notification => {
            notification.remove();
        });
    }

    // New method to clean up all intervals - prevents accumulation
    cleanupAllIntervals() {
        console.log('🧹 Cleaning up all intervals to prevent accumulation...');

        // Track what we're cleaning up to prevent race conditions
        const cleanupActions = [];

        if (this.statusUpdateInterval) {
            clearInterval(this.statusUpdateInterval);
            this.statusUpdateInterval = null;
            cleanupActions.push('statusUpdate');
        }

        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
            cleanupActions.push('timer');
        }

        if (this.videoUpdateInterval) {
            clearInterval(this.videoUpdateInterval);
            this.videoUpdateInterval = null;
            cleanupActions.push('videoUpdate');
        }

        if (this.videoWatchdog) {
            clearInterval(this.videoWatchdog);
            this.videoWatchdog = null;
            cleanupActions.push('videoWatchdog');
        }

        if (this.fullscreenVideoInterval) {
            clearInterval(this.fullscreenVideoInterval);
            this.fullscreenVideoInterval = null;
            cleanupActions.push('fullscreenVideo');
        }

        if (this.healthMonitorInterval) {
            clearInterval(this.healthMonitorInterval);
            this.healthMonitorInterval = null;
            cleanupActions.push('healthMonitor');
        }

        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
            this.dataSaveInterval = null;
            cleanupActions.push('dataSave');
        }

        if (this.metricsSaveInterval) {
            clearInterval(this.metricsSaveInterval);
            this.metricsSaveInterval = null;
            cleanupActions.push('metricsSave');
        }

        if (this.browserStreamingEnabled) {
            try {
                this.stopLocalFrameStreaming();
                cleanupActions.push('localFrameStreaming');
            } catch (error) {
                console.warn('⚠️ Error stopping local frame streaming:', error);
            }

            try {
                this.stopBrowserCamera();
                cleanupActions.push('browserCamera');
            } catch (error) {
                console.warn('⚠️ Error stopping browser camera:', error);
            }
        }

        if (cleanupActions.length > 0) {
            console.log(`✅ Cleaned up intervals: ${cleanupActions.join(', ')}`);
        } else {
            console.log('ℹ️ No active intervals to clean up');
        }
    }

    showFinalMetrics(metrics) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm';
        notification.innerHTML = `
            <div class="text-sm">
                <div class="font-semibold mb-2">📊 Session Complete!</div>
                <div class="space-y-1 text-xs">
                    <div>Focus Time: ${metrics.focused_time}s</div>
                    <div>Total Time: ${metrics.total_time}s</div>
                    <div>Focus Rate: ${metrics.focus_percentage}%</div>
                </div>
            </div>
        `;
        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    showCameraShutdownNotification() {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-blue-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm';
        notification.innerHTML = `
            <div class="text-sm">
                <div class="font-semibold mb-2">📹 Camera Shutting Down</div>
                <div class="space-y-1 text-xs">
                    <div>🔒 Camera access released</div>
                    <div>💾 Session data saved</div>
                    <div>✅ Service stopped completely</div>
                </div>
            </div>
        `;
        document.body.appendChild(notification);

        // Auto remove after 4 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    async stopService() {
        console.log('🛑 Stopping eye tracking service completely (course exit)...');

        this.isTransitioning = true;

        // Save final metrics before stopping
        await this.saveRealtimeMetrics();
        await this.saveSessionData();

        try {
            if (this.isConnected && this.isTracking) {
                try {
                    const response = await fetch(`${this.pythonServiceUrl}/api/stop_tracking`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            console.log('⏹️ Eye tracking stopped on service (course exit)');

                            if (data.final_metrics) {
                                console.log('📊 Final course metrics:', data.final_metrics);
                                this.showFinalMetrics(data.final_metrics);
                            }
                        }
                    }
                } catch (error) {
                    console.warn('⚠️ Error stopping tracking on service:', error);
                }
            }

            if (this.isConnected) {
                try {
                    console.log('📹 Shutting down camera service...');
                    this.showCameraShutdownNotification();

                    const shutdownResponse = await fetch(`${this.pythonServiceUrl}/api/shutdown`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    if (shutdownResponse.ok) {
                        const shutdownData = await shutdownResponse.json();
                        if (shutdownData.success) {
                            console.log('📹 Camera service shut down successfully');
                        }
                    }
                } catch (shutdownError) {
                    console.warn('⚠️ Error shutting down camera service:', shutdownError);

                    try {
                        console.log('📹 Trying alternative camera shutdown...');
                        await fetch(`${this.pythonServiceUrl}/api/stop`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });
                        console.log('📹 Alternative camera shutdown attempted');
                    } catch (fallbackError) {
                        console.warn('⚠️ Alternative shutdown also failed:', fallbackError);
                    }
                }
            }

            this.isConnected = false;
            this.isTracking = false;
            this.countdownActive = false;

            if (this.browserStreamingEnabled) {
                this.stopLocalFrameStreaming();
                this.stopBrowserCamera();
            }

            this.cleanupAllIntervals();
            this.stopHealthMonitoring();
            this.stopDataSaving();
            this.stopMetricsSaving();
            this.cleanupInterface();

            console.log('✅ Eye tracking service and camera completely stopped');

        } catch (error) {
            console.error('❌ Error during service stop:', error);
        } finally {
            this.isTransitioning = false;
        }
    }

    // Static method to handle course exit - COMPLETE SERVICE SHUTDOWN
    static async handleCourseExit() {
        console.log('🚪 Handling course exit - stopping all eye tracking services...');

        try {
            if (window.cvEyeTracker) {
                // Stop the service completely
                await window.cvEyeTracker.stopService();

                // Clear the global reference
                window.cvEyeTracker = null;

                console.log('✅ Course exit completed - all services stopped');
            } else {
                console.log('ℹ️ No active eye tracker to stop');

                // Even if no tracker exists, try to shutdown any running camera service
                try {
                    console.log('📹 Emergency camera shutdown (no active tracker)...');
                    const pythonServiceUrl = getGlobalPythonServiceUrl();

                    // Try multiple shutdown endpoints
                    const shutdownPromises = [
                        fetch(`${pythonServiceUrl}/api/shutdown`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        }).catch(() => null),
                        fetch(`${pythonServiceUrl}/api/stop`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        }).catch(() => null),
                        fetch(`${pythonServiceUrl}/api/stop_tracking`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        }).catch(() => null)
                    ];

                    await Promise.all(shutdownPromises);
                    console.log('📹 Emergency camera shutdown completed');
                } catch (emergencyError) {
                    console.warn('⚠️ Emergency camera shutdown failed:', emergencyError);
                }
            }
        } catch (error) {
            console.error('❌ Error during course exit:', error);

            // Force cleanup even if there are errors
            try {
                if (window.cvEyeTracker) {
                    window.cvEyeTracker.cleanupAllIntervals();
                    window.cvEyeTracker.cleanupInterface();
                    window.cvEyeTracker = null;
                }

                // Emergency camera shutdown in force cleanup
                try {
                    console.log('📹 Force emergency camera shutdown...');
                    const pythonServiceUrl = getGlobalPythonServiceUrl();

                    // Multiple shutdown attempts
                    await Promise.all([
                        fetch(`${pythonServiceUrl}/api/shutdown`, { method: 'POST' }).catch(() => null),
                        fetch(`${pythonServiceUrl}/api/stop`, { method: 'POST' }).catch(() => null)
                    ]);

                    console.log('📹 Force camera shutdown completed');
                } catch (forceShutdownError) {
                    console.warn('⚠️ Force camera shutdown failed:', forceShutdownError);
                }

                // Remove any remaining UI elements
                document.querySelectorAll('[id*="eye-tracking"], [id*="cv-eye-tracking"], [id*="tracking-"]').forEach(el => {
                    el.remove();
                });

                console.log('✅ Force cleanup completed for course exit');
            } catch (forceError) {
                console.error('❌ Even force cleanup failed:', forceError);
            }
        }
    }

    // Method to switch to a new section within the same module - ENHANCED CRASH PREVENTION
    async switchSection(newSectionId) {
        console.log(`🔄 Switching from section ${this.sectionId} to section ${newSectionId}`);
        const oldSectionId = this.sectionId;

        // Set transitioning flag immediately to prevent conflicts
        this.isTransitioning = true;

        // Always update section ID first to prevent state inconsistency
        this.sectionId = newSectionId;

        // If service is not connected, just update section ID and return
        if (!this.isConnected) {
            console.log(`🔄 Service not connected, updated section ID to ${newSectionId}`);
            this.isTransitioning = false;
            return;
        }

        // If same section, no action needed
        if (oldSectionId === newSectionId) {
            console.log(`🔄 Same section (${newSectionId}), no action needed`);
            this.isTransitioning = false;
            return;
        }

        try {
            // Pause video updates during section switch to prevent crashes
            const wasVideoRunning = !!this.videoUpdateInterval;
            if (wasVideoRunning) {
                this.stopVideoUpdates();
                console.log('⏸️ Video updates paused for section switch');
            }

            // Multiple fallback strategies for robust section switching
            let switchSuccess = false;

            // Strategy 1: Try API switch_section endpoint
            if (this.isTracking) {
                try {
                    const userId = await this.getCurrentUserId();

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

                    const response = await fetch(`${this.pythonServiceUrl}/api/switch_section`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            module_id: this.moduleId,
                            section_id: newSectionId
                        }),
                        signal: controller.signal
                    });

                    clearTimeout(timeoutId);

                    if (response.ok) {
                        const result = await response.json();

                        if (result.success) {
                            console.log(`✅ Section switched via API: ${oldSectionId} → ${newSectionId}`);
                            switchSuccess = true;
                        } else {
                            console.warn('⚠️ API switch_section returned failure:', result.error);
                        }
                    } else {
                        console.warn(`⚠️ API switch_section HTTP error: ${response.status}`);
                    }

                } catch (error) {
                    if (error.name === 'AbortError') {
                        console.warn('⚠️ API switch_section timeout');
                    } else {
                        console.warn('⚠️ API switch_section network error:', error.message);
                    }
                }
            }

            // Strategy 2: If API failed, try graceful restart with timeout
            if (!switchSuccess && this.isConnected) {
                console.log('🔄 API switch failed, attempting graceful restart...');

                try {
                    const userId = await this.getCurrentUserId();

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

                    const response = await fetch(`${this.pythonServiceUrl}/api/start_tracking`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            module_id: this.moduleId,
                            section_id: newSectionId
                        }),
                        signal: controller.signal
                    });

                    clearTimeout(timeoutId);

                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            console.log(`✅ Section switched via restart: ${oldSectionId} → ${newSectionId}`);
                            switchSuccess = true;
                        }
                    }

                } catch (error) {
                    if (error.name === 'AbortError') {
                        console.warn('⚠️ Graceful restart timeout');
                    } else {
                        console.warn('⚠️ Graceful restart failed:', error.message);
                    }
                }
            }

            // Strategy 3: If all else fails, ensure service health
            if (!switchSuccess) {
                console.log('🔄 All switch strategies failed, checking service health...');

                // Check if service is still alive with timeout
                await this.checkServiceHealth(true); // Quick check

                if (this.isConnected) {
                    // Service is alive but switch failed - log and continue
                    console.log(`✅ Service healthy, section updated to ${newSectionId} (switch may have worked silently)`);
                } else {
                    // Service is down - mark as disconnected but don't crash
                    console.warn('❌ Service appears down, will attempt reconnection later');
                    this.isConnected = false;
                }
            }

            // Resume video updates if they were running
            if (wasVideoRunning && this.isConnected) {
                // Brief delay to ensure service is stable
                setTimeout(() => {
                    if (!this.isTransitioning && this.isConnected) {
                        this.startVideoUpdates();
                        console.log('▶️ Video updates resumed after section switch');
                    }
                }, 1000);
            }

        } catch (error) {
            console.error('❌ Critical error during section switch:', error);
            // Don't crash - just log and continue
        } finally {
            // Always clear transitioning flag after a delay
            setTimeout(() => {
                this.isTransitioning = false;
            }, 2000); // 2 second delay to ensure stability
        }

        console.log(`🔄 Section switch completed: ${oldSectionId} → ${newSectionId} (success: ${switchSuccess || 'partial'})`);
    }

    // Seamless transition method for module switching - ULTRA CRASH PREVENTION
    async seamlessTransition(newModuleId, newSectionId) {
        console.log(`⚡ Starting seamless transition: ${this.moduleId}→${newModuleId}, section: ${newSectionId}`);

        this.isTransitioning = true;

        try {
            // Force cleanup of all intervals to prevent accumulation
            this.cleanupAllIntervals();

            // Stop current tracking but preserve connection
            if (this.isTracking) {
                // Don't await - just fire and forget to prevent hanging
                this.stopTracking().catch(error => {
                    console.warn('⚠️ Error stopping tracking during transition:', error);
                });
            }

            // Brief pause to ensure clean state
            await new Promise(resolve => setTimeout(resolve, 800));

            // Update IDs immediately
            this.moduleId = newModuleId;
            this.sectionId = newSectionId;

            // Reset state flags
            this.consecutiveFrameFailures = 0;
            this.reconnectionAttempts = 0;

            // Start new tracking with error handling
            try {
                await this.init();
                console.log('⚡ Seamless transition completed successfully');
            } catch (initError) {
                console.error('❌ Error during init in seamless transition:', initError);

                // Fallback: Try simple restart
                setTimeout(async () => {
                    try {
                        console.log('🔄 Attempting fallback initialization...');
                        await this.checkServiceHealth(true);
                        if (this.isConnected) {
                            await this.startTracking();
                            this.displayTrackingInterface();
                        }
                    } catch (fallbackError) {
                        console.error('❌ Fallback initialization also failed:', fallbackError);
                    }
                }, 1000);
            }

        } catch (error) {
            console.error('❌ Error during seamless transition:', error);

            // Don't throw - just log and continue with basic state
            console.log('🔄 Attempting recovery with basic state...');
            this.moduleId = newModuleId;
            this.sectionId = newSectionId;
            this.isTracking = false;
            this.isConnected = false;

        } finally {
            // Ensure transitioning flag is cleared with multiple timeouts for safety
            setTimeout(() => {
                this.isTransitioning = false;
            }, 2000);

            // Backup cleanup in case first one fails
            setTimeout(() => {
                if (this.isTransitioning) {
                    console.log('🔄 Backup: Clearing transitioning flag');
                    this.isTransitioning = false;
                }
            }, 5000);
        }
    }

    // Static method to handle section changes across page navigations - ULTRA ROBUST MODULE SWITCHING
    static async handleSectionChange(moduleId, newSectionId) {
        console.log(`🔄 Static section change handler: module ${moduleId}, section ${newSectionId}`);

        try {
            // Force cleanup of any existing tracker first
            if (window.cvEyeTracker) {
                console.log('🧹 Cleaning up existing tracker before creating new one...');

                try {
                    // Force immediate cleanup
                    window.cvEyeTracker.isTransitioning = true;
                    window.cvEyeTracker.cleanupAllIntervals();

                    // Stop tracking without waiting
                    if (window.cvEyeTracker.isTracking) {
                        window.cvEyeTracker.stopTracking().catch(error => {
                            console.warn('⚠️ Error during forced cleanup:', error);
                        });
                    }

                    // Remove interface immediately
                    const existingInterface = document.getElementById('cv-eye-tracking-interface');
                    if (existingInterface) {
                        existingInterface.remove();
                    }

                    // Clear countdown overlays
                    const countdownOverlay = document.getElementById('eye-tracking-countdown');
                    if (countdownOverlay) {
                        countdownOverlay.remove();
                    }

                } catch (cleanupError) {
                    console.warn('⚠️ Error during existing tracker cleanup:', cleanupError);
                }

                // Clear the global reference
                window.cvEyeTracker = null;
            }

            // Brief pause to ensure complete cleanup
            await new Promise(resolve => setTimeout(resolve, 500));

            // Create completely new tracker instance
            console.log(`🆕 Creating fresh tracker for module: ${moduleId}, section: ${newSectionId}`);
            window.cvEyeTracker = new CVEyeTrackingSystem(moduleId, newSectionId);

            console.log('✅ Fresh tracker created successfully');

        } catch (error) {
            console.error('❌ Error in static section change handler:', error);

            // Nuclear option: Force complete reset
            try {
                console.log('🔄 Nuclear reset: Force complete cleanup and restart...');

                // Clear all possible intervals globally
                for (let i = 1; i < 9999; i++) window.clearInterval(i);

                // Remove all eye tracking related elements
                document.querySelectorAll('[id*="eye-tracking"], [id*="cv-eye-tracking"], [id*="tracking-"]').forEach(el => {
                    el.remove();
                });

                // Clear global reference
                window.cvEyeTracker = null;

                // Brief pause
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Create new tracker
                window.cvEyeTracker = new CVEyeTrackingSystem(moduleId, newSectionId);
                console.log('✅ Nuclear reset completed successfully');

            } catch (nuclearError) {
                console.error('❌ Even nuclear reset failed:', nuclearError);
                // At this point, just ensure we have basic state
                window.cvEyeTracker = null;
            }
        }
    }

    // Add missing methods that were referenced but not defined
    showServiceError() {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm';
        notification.innerHTML = `
            <div class="text-sm">
                <div class="font-semibold mb-2">⚠️ Service Error</div>
                <div class="text-xs">Python eye tracking service is not running. Please start the service.</div>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    showCameraError(errorType, errorMessage) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-orange-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm';
        notification.innerHTML = `
            <div class="text-sm">
                <div class="font-semibold mb-2">📹 Camera Error: ${errorType}</div>
                <div class="text-xs">${errorMessage}</div>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Initialize CV eye tracking when DOM is loaded - ULTRA ROBUST INITIALIZATION
document.addEventListener('DOMContentLoaded', async function () {
    try {
        // Force cleanup any existing tracker first
        if (window.cvEyeTracker) {
            console.log('🧹 DOM Ready: Cleaning up existing tracker...');
            try {
                window.cvEyeTracker.cleanupAllIntervals();
                window.cvEyeTracker.cleanupInterface();
            } catch (cleanupError) {
                console.warn('⚠️ Error cleaning up existing tracker:', cleanupError);
            }
            window.cvEyeTracker = null;
        }

        // Extract module and section IDs from URL or page data
        const urlParams = new URLSearchParams(window.location.search);
        const moduleId = urlParams.get('module_id');
        const sectionId = urlParams.get('section_id');

        if (moduleId) {
            const moduleIdInt = parseInt(moduleId);
            const sectionIdInt = sectionId ? parseInt(sectionId) : null;

            console.log(`⚡ DOM ready - processing module: ${moduleIdInt}, section: ${sectionIdInt}`);

            // Always create fresh tracker on DOM ready (no reuse)
            console.log(`🆕 Creating fresh tracker for module: ${moduleIdInt}, section: ${sectionIdInt}`);

            try {
                window.cvEyeTracker = new CVEyeTrackingSystem(moduleIdInt, sectionIdInt);
                console.log('⚡ Fresh tracker created successfully');
            } catch (error) {
                console.error('❌ Failed to create tracker:', error);

                // Retry after brief delay
                setTimeout(() => {
                    try {
                        console.log('🔄 Retrying tracker creation...');
                        window.cvEyeTracker = new CVEyeTrackingSystem(moduleIdInt, sectionIdInt);
                        console.log('⚡ Retry tracker created successfully');
                    } catch (retryError) {
                        console.error('❌ Retry also failed:', retryError);
                    }
                }, 1000);
            }

            console.log('⚡ CV Eye tracking system ready for module:', moduleId, 'section:', sectionId);

            // Handle page unload with error handling (only add once)
            if (!window.eyeTrackingUnloadHandlerAdded) {
                window.addEventListener('beforeunload', () => {
                    try {
                        if (window.cvEyeTracker) {
                            window.cvEyeTracker.isTransitioning = true; // Prevent final metrics
                            window.cvEyeTracker.cleanupAllIntervals();
                            window.cvEyeTracker.cleanupInterface();
                        }
                    } catch (error) {
                        console.warn('⚠️ Error during cleanup on page unload:', error);
                    }
                });

                // Also handle visibility change for better cleanup
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden && window.cvEyeTracker) {
                        try {
                            console.log('🔄 Page hidden, pausing video updates...');
                            window.cvEyeTracker.stopVideoUpdates();
                        } catch (error) {
                            console.warn('⚠️ Error pausing on visibility change:', error);
                        }
                    } else if (!document.hidden && window.cvEyeTracker && window.cvEyeTracker.isConnected) {
                        try {
                            console.log('🔄 Page visible, resuming video updates...');
                            setTimeout(() => {
                                if (window.cvEyeTracker && !window.cvEyeTracker.isTransitioning) {
                                    window.cvEyeTracker.startVideoUpdates();
                                }
                            }, 500);
                        } catch (error) {
                            console.warn('⚠️ Error resuming on visibility change:', error);
                        }
                    }
                });

                // Handle course exit button clicks
                document.addEventListener('click', (event) => {
                    // Check if the clicked element is a course exit button
                    const target = event.target;

                    // Skip if this is a manual exit button (handled by its own onclick)
                    if (target.closest('[data-manual-exit="true"]')) {
                        return;
                    }

                    // Common patterns for course exit buttons
                    const isExitButton = (
                        // Direct link checks
                        (target.tagName === 'A' && (
                            target.href?.includes('Sdashboard.php') ||
                            target.href?.includes('dashboard') ||
                            target.href?.includes('courses')
                        )) ||
                        // Parent link checks (for nested elements like icons and text)
                        target.closest('a[href*="Sdashboard.php"]') ||
                        target.closest('a[href*="dashboard"]') ||
                        target.closest('a[href*="courses"]') ||
                        // Text content checks
                        target.textContent?.toLowerCase().includes('dashboard') ||
                        target.textContent?.toLowerCase().includes('exit') ||
                        target.textContent?.toLowerCase().includes('leave') ||
                        target.textContent?.toLowerCase().includes('quit') ||
                        // Class and ID checks
                        target.classList.contains('exit-course') ||
                        target.classList.contains('leave-course') ||
                        target.classList.contains('back-to-dashboard') ||
                        target.id === 'dashboard-link' ||
                        target.id === 'exit-course' ||
                        target.id === 'course-exit' ||
                        // Button onclick checks
                        target.closest('button[onclick*="exit"]') ||
                        target.closest('button[onclick*="leave"]') ||
                        target.closest('button[onclick*="dashboard"]')
                    );

                    if (isExitButton) {
                        console.log('🚪 Course exit button detected, stopping eye tracking...');
                        // Use setTimeout to ensure the click event completes first
                        setTimeout(() => {
                            CVEyeTrackingSystem.handleCourseExit();
                        }, 100);
                    }
                });

                window.eyeTrackingUnloadHandlerAdded = true;
            }
        } else {
            console.log('ℹ️ No module ID found in URL parameters');
        }
    } catch (error) {
        console.error('❌ Error initializing CV eye tracking:', error);
    }
});

// Global function to stop eye tracking service (can be called from anywhere)
window.stopEyeTrackingService = function () {
    console.log('🚪 Global stop function called...');
    if (window.cvEyeTracker) {
        CVEyeTrackingSystem.handleCourseExit();
    } else {
        console.log('ℹ️ No active eye tracker to stop');
    }
};

// Global function to check if eye tracking is active
window.isEyeTrackingActive = function () {
    return !!(window.cvEyeTracker && window.cvEyeTracker.isTracking);
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CVEyeTrackingSystem;
}
