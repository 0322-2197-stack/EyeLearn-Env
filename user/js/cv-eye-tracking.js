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

class CVEyeTrackingSystem {
    constructor(moduleId, sectionId = null) {
        this.moduleId = moduleId;
        this.sectionId = sectionId;
        this.isConnected = false;
        this.isTracking = false;
        this.dormantMode = false; // New dormant mode flag
        this.pythonServiceUrl = 'http://127.0.0.1:5000';
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
            isCurrentlyFocused: false
        };
        
        this.metrics = {
            focused_time: 0,
            unfocused_time: 0,
            total_time: 0,
            focus_percentage: 0
        };
        
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

    async init() {
        console.log(`🎯 Initializing Enhanced CV Eye Tracking System v2.5... (Instance: ${this.instanceId})`);
        console.log('Features: Instant activation, seamless transitions, crash-resistant switching');
        
        // Clean up any existing intervals before starting new ones
        this.cleanupAllIntervals();
        
        // Additional safety: Clean up any stale DOM elements
        this.cleanupInterface();
        
        // Check if Python service is running (with quick timeout for speed)
        await this.checkServiceHealth(true); // true = quick check
        
        if (this.isConnected) {
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
        } else {
            this.showServiceError();
        }
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
    
    async attemptReconnection() {
        if (this.reconnectionAttempts >= this.maxReconnectionAttempts) {
            console.warn('🚫 Max reconnection attempts reached, stopping automatic reconnection');
            return;
        }
        
        this.reconnectionAttempts++;
        console.log(`🔄 Reconnection attempt ${this.reconnectionAttempts}/${this.maxReconnectionAttempts}`);
        
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
    
    async saveSessionData() {
        if (!this.isTracking || this.isTransitioning) {
            return; // Don't save during transitions or when not tracking
        }

        try {
            const sessionData = {
                module_id: this.moduleId,
                section_id: this.sectionId,
                session_time: Math.floor(this.timers.sessionTime || 0),
                completion_percentage: typeof currentCompletionPercentage !== 'undefined'
                ? currentCompletionPercentage
                : 0,
                focus_data: {
                    focused_time: Math.floor(this.timers.focusedTime || 0),
                    unfocused_time: Math.floor(this.timers.unfocusedTime || 0),
                    focus_percentage: this.calculateFocusPercentage(),
                    total_time: Math.floor(this.timers.sessionTime || 0)
                }
            };

            const response = await fetch('database/save_session_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(sessionData)
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    console.log('💾 Session data saved to dashboard successfully');
                } else {
                    console.warn('⚠️ Failed to save session data:', result.error);
                }
            } else {
                console.warn('⚠️ Dashboard data save HTTP error:', response.status);
            }
        } catch (error) {
            console.warn('⚠️ Error saving session data to dashboard:', error);
        }
    }
    
    calculateFocusPercentage() {
        const totalActiveTime = this.timers.focusedTime + this.timers.unfocusedTime;
        return totalActiveTime > 0 ? Math.round((this.timers.focusedTime / totalActiveTime) * 100) : 0;
    }
    
    hasCountdownBeenShownForModule() {
        // Check sessionStorage to see if countdown was shown for this module in this session
        const sessionKey = `eyetracking_countdown_${this.moduleId}`;
        return sessionStorage.getItem(sessionKey) === 'shown';
    }

    markCountdownShownForModule() {
        // Mark that countdown has been shown for this module in this session
        const sessionKey = `eyetracking_countdown_${this.moduleId}`;
        sessionStorage.setItem(sessionKey, 'shown');
    }
    
    initializeTimers() {
        console.log('⏱️ Initializing timer system...');
        this.timers.sessionStart = Date.now();
        this.timers.sessionTime = 0;
        this.timers.focusedTime = 0;
        this.timers.unfocusedTime = 0;
        this.timers.isCurrentlyFocused = false;
        
        // Start the timer update loop
        this.startTimerUpdates();
    }
    
    startTimerUpdates() {
        // Update timers every 100ms for smooth display
        this.timerInterval = setInterval(() => {
            this.updateTimers();
        }, 100);
    }
    
    updateTimers() {
        if (!this.timers.sessionStart) return;
        
        const now = Date.now();
        this.timers.sessionTime = Math.floor((now - this.timers.sessionStart) / 1000);
        
        // Update focus/unfocus timers based on current state
        if (this.timers.isCurrentlyFocused && this.timers.currentFocusStart) {
            const additionalFocusTime = Math.floor((now - this.timers.currentFocusStart) / 1000);
            this.timers.focusedTime = this.timers.baseFocusedTime + additionalFocusTime;
        } else if (!this.timers.isCurrentlyFocused && this.timers.currentUnfocusStart) {
            const additionalUnfocusTime = Math.floor((now - this.timers.currentUnfocusStart) / 1000);
            this.timers.unfocusedTime = this.timers.baseUnfocusedTime + additionalUnfocusTime;
        }
        
        // Update the display
        this.updateTimerDisplay();
    }
    
    updateTimerDisplay() {
        // Update session time
        const sessionTimeElement = document.getElementById('session-time');
        if (sessionTimeElement) {
            sessionTimeElement.textContent = this.timers.sessionTime;
        }
        
        // Update focused time
        const focusTimeElement = document.getElementById('focus-time');
        if (focusTimeElement) {
            focusTimeElement.textContent = this.timers.focusedTime;
        }
        
        // Update unfocused time
        const unfocusTimeElement = document.getElementById('unfocus-time');
        if (unfocusTimeElement) {
            unfocusTimeElement.textContent = this.timers.unfocusedTime;
        }
        
        // Update focus percentage
        const focusPercentageElement = document.getElementById('focus-percentage');
        if (focusPercentageElement) {
            const totalActiveTime = this.timers.focusedTime + this.timers.unfocusedTime;
            const percentage = totalActiveTime > 0 ? Math.round((this.timers.focusedTime / totalActiveTime) * 100) : 0;
            focusPercentageElement.textContent = percentage;
        }
        
        // Update focus status indicator
        const focusStatus = document.getElementById('focus-status');
        const trackingIndicator = document.getElementById('tracking-indicator');
        
        if (focusStatus && trackingIndicator) {
            if (this.timers.isCurrentlyFocused) {
                focusStatus.textContent = 'Focused';
                focusStatus.className = 'text-green-400';
                trackingIndicator.className = 'w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5';
            } else {
                focusStatus.textContent = 'Unfocused';
                focusStatus.className = 'text-red-400';
                trackingIndicator.className = 'w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5';
            }
        }
    }
    
    handleFocusChange(isFocused) {
        const now = Date.now();
        
        if (isFocused && !this.timers.isCurrentlyFocused) {
            // User just became focused
            console.log('👁️ User became focused');
            
            // End unfocus period if active
            if (this.timers.currentUnfocusStart) {
                const unfocusDuration = Math.floor((now - this.timers.currentUnfocusStart) / 1000);
                this.timers.baseUnfocusedTime = (this.timers.baseUnfocusedTime || 0) + unfocusDuration;
                this.timers.currentUnfocusStart = null;
            }
            
            // Start focus period
            this.timers.currentFocusStart = now;
            this.timers.baseFocusedTime = this.timers.focusedTime;
            this.timers.isCurrentlyFocused = true;
            
        } else if (!isFocused && this.timers.isCurrentlyFocused) {
            // User just became unfocused
            console.log('👁️ User became unfocused');
            
            // End focus period if active
            if (this.timers.currentFocusStart) {
                const focusDuration = Math.floor((now - this.timers.currentFocusStart) / 1000);
                this.timers.baseFocusedTime = (this.timers.baseFocusedTime || 0) + focusDuration;
                this.timers.currentFocusStart = null;
            }
            
            // Start unfocus period
            this.timers.currentUnfocusStart = now;
            this.timers.baseUnfocusedTime = this.timers.unfocusedTime;
            this.timers.isCurrentlyFocused = false;
        }
    }

    async checkServiceHealth(quickCheck = false) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), quickCheck ? 2000 : 5000); // 2s for quick, 5s for normal
            
            const response = await fetch(`${this.pythonServiceUrl}/api/health`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (response.ok) {
                const data = await response.json();
                this.isConnected = data.success;
                if (data.version && !quickCheck) {
                    console.log(`✅ Connected to Enhanced Eye Tracking Service ${data.version}`);
                    console.log(`📋 Available features:`, data.features);
                }
                if (!quickCheck) console.log('✅ Python eye tracking service is running');
                return true;
            } else {
                console.log('❌ Python service responded with error');
                this.isConnected = false;
                return false;
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log(`❌ Service health check timeout (${quickCheck ? '2s' : '5s'})`);
            } else {
                console.log('❌ Cannot connect to Python eye tracking service:', error);
            }
            this.isConnected = false;
            return false;
        }
    }

    async startTracking() {
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
        
        if (this.statusUpdateInterval) {
            clearInterval(this.statusUpdateInterval);
            this.statusUpdateInterval = null;
        }
        
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
        
        if (this.videoUpdateInterval) {
            clearInterval(this.videoUpdateInterval);
            this.videoUpdateInterval = null;
        }
        
        if (this.videoWatchdog) {
            clearInterval(this.videoWatchdog);
            this.videoWatchdog = null;
        }
        
        if (this.fullscreenVideoInterval) {
            clearInterval(this.fullscreenVideoInterval);
            this.fullscreenVideoInterval = null;
        }
        
        if (this.healthMonitorInterval) {
            clearInterval(this.healthMonitorInterval);
            this.healthMonitorInterval = null;
        }
        
        if (this.dataSaveInterval) {
            clearInterval(this.dataSaveInterval);
            this.dataSaveInterval = null;
        }
        
        console.log('✅ All intervals cleaned up');
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

    async getCurrentUserId() {
        // Try to get user ID from the page or make an API call
        // This is a simplified version - you may need to adjust based on your session management
        try {
            const response = await fetch('database/get_current_user.php');
            if (response.ok) {
                const data = await response.json();
                return data.user_id;
            }
        } catch (error) {
            console.log('Could not get user ID, using fallback');
        }
        
        // Fallback: try to extract from global variables if available
        if (typeof window.currentUserId !== 'undefined') {
            return window.currentUserId;
        }
        
        // Default fallback (not ideal for production)
        return 1;
    }

    setupStatusUpdates() {
        // Check status every 2 seconds
        this.statusUpdateInterval = setInterval(async () => {
            await this.updateStatus();
        }, 2000);
    }

    async updateStatus() {
        if (!this.isConnected) {
            return;
        }

        try {
            const response = await fetch(`${this.pythonServiceUrl}/api/status`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateTrackingDisplay(data.status);
                    
                    // Handle focus changes
                    if (data.status && typeof data.status.is_focused !== 'undefined') {
                        this.handleFocusChange(data.status.is_focused);
                    }
                }
            } else {
                // Service not responding properly - reconnect
                this.handleServiceDisconnection();
            }
        } catch (error) {
            // Network error - service might be down
            this.handleServiceDisconnection();
        }
    }

    handleServiceDisconnection() {
        if (this.isConnected && !this.isTransitioning) {
            console.warn('🔌 Eye tracking service disconnected - initiating recovery...');
            this.isConnected = false;
            
            // Temporarily pause video updates to prevent connection spam
            this.stopVideoUpdates();
            
            // Don't stop video updates immediately - let health monitor handle reconnection
            console.log('🔄 Health monitor will attempt automatic reconnection');
            
            // Reset reconnection attempts counter for fresh start
            this.reconnectionAttempts = 0;
            
            // Restart video updates after a brief pause (to prevent spam)
            setTimeout(() => {
                if (this.isConnected && !this.isTransitioning) {
                    console.log('🔄 Restarting video updates after reconnection pause');
                    this.startVideoUpdates();
                }
            }, 2000); // 2 second pause
        }
    }

    displayTrackingInterface() {
        // Create the compact interface
        const trackingContainer = document.createElement('div');
        trackingContainer.id = 'cv-eye-tracking-interface';
        trackingContainer.innerHTML = `
            <div class="fixed top-20 right-4 bg-black text-white shadow-2xl rounded-lg border border-gray-600 z-50" style="width: 180px; font-family: system-ui;">
                <!-- Header with red dot and "Eye Tracking" -->
                <div class="px-2 py-1.5 border-b border-gray-600">
                    <div class="flex items-center">
                        <div id="tracking-indicator" class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></div>
                        <span class="text-xs font-medium">Eye Tracking</span>
                    </div>
                </div>
                
                <!-- Focus status line -->
                <div class="px-2 py-1 border-b border-gray-600">
                    <div class="flex items-center text-xs">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></div>
                        <span id="focus-status">Focused</span>
                    </div>
                </div>
                
                <!-- Metrics -->
                <div class="px-2 py-1.5 text-xs space-y-0.5 border-b border-gray-600">
                    <div>Focus: <span id="focus-time" class="text-green-400">0</span>s</div>
                    <div>Session: <span id="session-time" class="text-white">0</span>s</div>
                    <div>Focused: <span id="focus-percentage" class="text-white">0</span>%</div>
                    <div>Unfocused: <span id="unfocus-time" class="text-white">0</span>s</div>
                </div>
                
                <!-- Live Feed label -->
                <div class="px-2 py-1 text-xs text-gray-300 border-b border-gray-600">
                    Live Feed
                </div>
                
                <!-- Video feed container -->
                <div class="relative bg-black">
                    <img id="tracking-video" 
                         style="width: 100%; height: 100px; display: block; background: #000;"
                         class="rounded-b-lg"
                         alt="Live camera feed">
                </div>
            </div>
        `;
        
        document.body.appendChild(trackingContainer);
        
        // Verify the interface was created correctly
        setTimeout(() => {
            const videoElement = document.getElementById('tracking-video');
            console.log('🔍 Interface verification:', {
                container: !!document.getElementById('cv-eye-tracking-interface'),
                videoElement: !!videoElement,
                videoElementVisible: videoElement ? window.getComputedStyle(videoElement).display !== 'none' : false,
                videoElementType: videoElement ? videoElement.tagName : null
            });
        }, 100);
        
        // Start video updates immediately
        this.startVideoUpdates();
        
        console.log('📺 Eye tracking interface displayed - exact format from image');
    }

    startVideoUpdates() {
        console.log('🎬 Starting video updates...');
        
        if (this.videoUpdateInterval) {
            console.log('⚠️ Video already running, clearing previous interval');
            clearInterval(this.videoUpdateInterval);
        }
        
        // Use SAME frequency as working test
        this.videoUpdateInterval = setInterval(async () => {
            await this.updateVideoFrame();
        }, 100); // Update every 100ms for 10 FPS - EXACT same as test
        
        console.log('✅ Video update interval started (100ms = 10 FPS - same as working test)');
        
        // Add a watchdog to ensure video keeps running
        this.startVideoWatchdog();
    }

    startVideoWatchdog() {
        // Simple watchdog - just check if element exists (like test file simplicity)
        this.videoWatchdog = setInterval(() => {
            const videoElement = document.getElementById('tracking-video');
            if (videoElement && this.isConnected) {
                // Only check if element is still in DOM
                if (!videoElement.parentNode) {
                    console.warn('⚠️ Video element lost - reinitializing...');
                    this.stopVideoUpdates();
                    this.startVideoUpdates();
                }
            }
        }, 5000); // Check every 5 seconds - less aggressive
    }

    stopVideoUpdates() {
        if (this.videoUpdateInterval) {
            clearInterval(this.videoUpdateInterval);
            this.videoUpdateInterval = null;
        }
        
        if (this.videoWatchdog) {
            clearInterval(this.videoWatchdog);
            this.videoWatchdog = null;
        }
    }

    // SIMPLIFIED updateVideoFrame method - WITH CONNECTION ERROR HANDLING
    async updateVideoFrame() {
        if (!this.isConnected || this.isTransitioning) {
            return; // Skip updates during transitions or when disconnected
        }

        try {
            const response = await fetch(`${this.pythonServiceUrl}/api/frame`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                // Use the EXACT same logic as the working test
                if (data.hasFrame && data.frameData) {
                    const videoElement = document.getElementById('tracking-video');
                    if (videoElement) {
                        videoElement.src = data.frameData;
                        
                        // Update frame tracking (simplified)
                        this.frameCount++;
                        this.lastFrameTime = Date.now();
                        this.consecutiveFrameFailures = 0;
                        
                        // Log success occasionally - same as test
                        if (Math.random() < 0.1) { // 10% chance like test
                            console.log(`✅ Frame updated (${data.frameData.length} chars)`);
                        }
                    }
                } else {
                    this.consecutiveFrameFailures++;
                    if (Math.random() < 0.05) { // Reduced logging to 5% to avoid spam
                        console.log(`⚠️ No frame data: hasFrame=${data.hasFrame}`);
                    }
                }
            } else {
                // Handle HTTP errors more gracefully
                this.consecutiveFrameFailures++;
                if (response.status === 503 || response.status === 502) {
                    // Service temporarily unavailable - don't spam logs
                    if (Math.random() < 0.01) { // Only log 1% of the time
                        console.log(`⚠️ Service temporarily unavailable: ${response.status}`);
                    }
                } else {
                    if (Math.random() < 0.1) { // 10% chance for other errors
                        console.log(`❌ Frame request failed: ${response.status}`);
                    }
                }
                
                // If too many consecutive failures, mark as disconnected
                if (this.consecutiveFrameFailures > 10) {
                    console.warn('🔌 Too many frame failures, marking as disconnected');
                    this.handleServiceDisconnection();
                }
            }
        } catch (error) {
            this.consecutiveFrameFailures++;
            
            // Handle connection errors more gracefully
            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                // Connection refused - service is down
                if (Math.random() < 0.01) { // Only log 1% of connection errors to avoid spam
                    console.log(`⚠️ Service connection lost (attempt ${this.consecutiveFrameFailures})`);
                }
                
                // If too many consecutive failures, trigger reconnection
                if (this.consecutiveFrameFailures > 5) {
                    console.warn('🔌 Multiple connection failures, triggering service health check');
                    this.handleServiceDisconnection();
                }
            } else {
                // Other errors - log occasionally
                if (Math.random() < 0.1) {
                    console.log(`❌ Frame update error: ${error.message}`);
                }
            }
        }
    }

    updateTrackingDisplay(status) {
        const indicator = document.getElementById('tracking-indicator');
        const focusStatus = document.getElementById('focus-status');
        const focusTime = document.getElementById('focus-time');
        const sessionTime = document.getElementById('session-time');
        const focusPercentage = document.getElementById('focus-percentage');
        const unfocusTime = document.getElementById('unfocus-time');
        
        if (!indicator || !focusStatus || !focusTime || !sessionTime || !focusPercentage || !unfocusTime) {
            return;
        }

        // Update tracking state and metrics
        if (status.metrics) {
            this.metrics = status.metrics;
        }
        
        this.trackingState = status.tracking_state || 'idle';

        // Update indicator color based on tracking state
        if (status.countdown_active) {
            indicator.className = 'w-2 h-2 rounded-full bg-blue-500 mr-2 animate-pulse';
        } else if (status.is_tracking_enabled && status.tracking_state === 'tracking') {
            indicator.className = status.is_focused ? 
                'w-2 h-2 rounded-full bg-green-500 mr-2' : 
                'w-2 h-2 rounded-full bg-red-500 mr-2';
        } else {
            indicator.className = 'w-2 h-2 rounded-full bg-gray-500 mr-2';
        }

        // Update focus status (matches the image exactly)
        focusStatus.textContent = status.is_focused ? 'Focused' : 'Unfocused';
        
        // Update all metrics to match the image format
        if (status.metrics) {
            focusTime.textContent = Math.floor(status.metrics.focused_time || 0);
            sessionTime.textContent = Math.floor(status.metrics.total_time || 0);
            focusPercentage.textContent = Math.floor(status.metrics.focus_percentage || 0);
            unfocusTime.textContent = Math.floor(status.metrics.unfocused_time || 0);
        }
    }

    startFullscreenVideoUpdates() {
        console.log('🖥️ Starting fullscreen video updates...');
        
        if (this.fullscreenVideoInterval) {
            clearInterval(this.fullscreenVideoInterval);
        }
        
        this.fullscreenVideoInterval = setInterval(async () => {
            await this.updateFullscreenVideoFrame();
        }, 100); // Faster updates for fullscreen (100ms)
        
        console.log('✅ Fullscreen video update interval started (100ms)');
    }

    stopFullscreenVideoUpdates() {
        if (this.fullscreenVideoInterval) {
            clearInterval(this.fullscreenVideoInterval);
            this.fullscreenVideoInterval = null;
            console.log('⏹️ Fullscreen video updates stopped');
        }
    }

    async updateFullscreenVideoFrame() {
        if (!this.isConnected || this.isTransitioning) {
            return; // Skip updates during transitions or when disconnected
        }
        
        try {
            const response = await fetch(`${this.pythonServiceUrl}/api/frame?_=${Date.now()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                // Use the same simple approach as the test file
                if (data.hasFrame && data.frameData) {
                    const fullscreenVideoElement = document.getElementById('fullscreen-video-feed');
                    if (fullscreenVideoElement) {
                        fullscreenVideoElement.src = data.frameData;
                    }
                    
                    // Also update the small video if it's visible
                    const videoElement = document.getElementById('tracking-video');
                    if (videoElement) {
                        videoElement.src = data.frameData;
                    }
                }
            } else {
                // Handle HTTP errors silently during transitions
                if (Math.random() < 0.01 && !this.isTransitioning) { // Only log 1% of errors
                    console.log(`⚠️ Fullscreen frame request failed: ${response.status}`);
                }
            }
        } catch (error) {
            // Handle connection errors silently during transitions
            if (Math.random() < 0.01 && !this.isTransitioning) { // Only log 1% of connection errors
                console.warn('⚠️ Failed to fetch fullscreen video frame:', error.message);
            }
        }
    }

    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    showCameraError() {
        const errorContainer = document.createElement('div');
        errorContainer.innerHTML = `
            <div class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 max-w-md z-50">
                <div class="flex items-center mb-2">
                    <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                    <h3 class="text-sm font-semibold text-red-700">Camera Required</h3>
                </div>
                
                <div class="text-xs text-red-600 space-y-1">
                    <p>📷 Eye tracking requires camera access</p>
                    <p>Please check:</p>
                    <ol class="list-decimal list-inside ml-2 space-y-1">
                        <li>Camera is connected and working</li>
                        <li>No other apps are using the camera</li>
                        <li>Camera permissions are enabled</li>
                        <li>Try unplugging and reconnecting camera</li>
                    </ol>
                    <p class="text-blue-600 mt-2">🔧 Check Windows Device Manager for camera issues</p>
                </div>
                
                <button onclick="this.parentElement.remove()" class="mt-2 text-xs text-red-600 hover:text-red-800">
                    Dismiss
                </button>
            </div>
        `;
        
        document.body.appendChild(errorContainer);
        
        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            if (errorContainer.parentNode) {
                errorContainer.remove();
            }
        }, 10000);
    }

    showServiceError() {
        const errorContainer = document.createElement('div');
        errorContainer.innerHTML = `
            <div class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 max-w-sm z-50">
                <div class="flex items-center mb-2">
                    <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                    <h3 class="text-sm font-semibold text-red-700">Enhanced Eye Tracking Service</h3>
                </div>
                
                <div class="text-xs text-red-600 space-y-1">
                    <p>❌ Enhanced Python service not running</p>
                    <p>To enable enhanced CV eye tracking:</p>
                    <ol class="list-decimal list-inside ml-2 space-y-1">
                        <li>Install Python dependencies (opencv, numpy, flask)</li>
                        <li>Run enhanced eye_tracking_service.py from python_services/</li>
                        <li>Service should start on http://127.0.0.1:5000</li>
                        <li>Refresh this page</li>
                    </ol>
                    <p class="text-blue-600 mt-2">🎯 Features: 3s countdown, real-time focus tracking, detailed metrics</p>
                </div>
                
                <button onclick="this.parentElement.remove()" class="mt-2 text-xs text-red-600 hover:text-red-800">
                    Dismiss
                </button>
            </div>
        `;
        
        document.body.appendChild(errorContainer);
        
        // Auto-dismiss after 15 seconds (longer since it's enhanced info)
        setTimeout(() => {
            if (errorContainer.parentNode) {
                errorContainer.remove();
            }
        }, 15000);
    }

    // Public method to get current stats
    getStats() {
        return {
            isConnected: this.isConnected,
            isTracking: this.isTracking,
            totalTime: this.totalTime,
            moduleId: this.moduleId,
            sectionId: this.sectionId
        };
    }
    
    // Method to completely stop the service - FOR COURSE EXIT
    async stopService() {
        console.log('🛑 Stopping eye tracking service completely (course exit)...');
        
        this.isTransitioning = true; // Prevent any new operations
        
        // Save final session data before stopping
        await this.saveSessionData();
        
        try {
            // Stop tracking on the service with final metrics
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
                            
                            // Show final metrics for course exit
                            if (data.final_metrics) {
                                console.log('📊 Final course metrics:', data.final_metrics);
                                this.showFinalMetrics(data.final_metrics);
                            }
                        }
                    }
                } catch (error) {
                    console.warn('⚠️ Error stopping service:', error);
                }
            }
            
            // CRITICAL: Shut down the camera service completely
            if (this.isConnected) {
                try {
                    console.log('📹 Shutting down camera service...');
                    
                    // Show immediate notification that camera is being shut down
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
                        } else {
                            console.warn('⚠️ Camera shutdown response not successful:', shutdownData);
                        }
                    } else {
                        console.warn('⚠️ Camera shutdown HTTP error:', shutdownResponse.status);
                    }
                } catch (shutdownError) {
                    console.warn('⚠️ Error shutting down camera service:', shutdownError);
                    
                    // Fallback: Try alternative shutdown endpoint
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
            
            // Force disconnect from service
            this.isConnected = false;
            this.isTracking = false;
            this.countdownActive = false;
            
            // Clean up all intervals and monitoring
            this.cleanupAllIntervals();
            this.stopHealthMonitoring();
            this.stopDataSaving();
            
            // Clean up interface elements
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
                    const pythonServiceUrl = 'http://127.0.0.1:5000';
                    
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
                    const pythonServiceUrl = 'http://127.0.0.1:5000';
                    
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
}

// Initialize CV eye tracking when DOM is loaded - ULTRA ROBUST INITIALIZATION
document.addEventListener('DOMContentLoaded', async function() {
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
window.stopEyeTrackingService = function() {
    console.log('🚪 Global stop function called...');
    if (window.cvEyeTracker) {
        CVEyeTrackingSystem.handleCourseExit();
    } else {
        console.log('ℹ️ No active eye tracker to stop');
    }
};

// Global function to check if eye tracking is active
window.isEyeTrackingActive = function() {
    return !!(window.cvEyeTracker && window.cvEyeTracker.isTracking);
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CVEyeTrackingSystem;
}
