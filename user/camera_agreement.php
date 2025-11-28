<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../loginpage.php');
    exit;
}

// Note: Camera agreement will show every time user logs in
// Remove the check that skips the agreement page

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_agreement'])) {
        $_SESSION['camera_agreement_accepted'] = true;
        
        // Use centralized database connection
        require_once __DIR__ . '/../database/db_connection.php';
        try {
            $conn = getMysqliConnection();
            $user_id = $_SESSION['user_id'];
            $sql = "UPDATE users SET camera_agreement_accepted = 1, camera_agreement_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
        } catch (Exception $e) {
            // Log error but continue
            error_log("Failed to update camera agreement: " . $e->getMessage());
        }
        
        header('Location: Sdashboard.php');
        exit;
    } elseif (isset($_POST['decline_agreement'])) {
        // User declined - logout and redirect to login
        session_destroy();
        header('Location: ../loginpage.php?error=camera_declined');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Agreement - EyeLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#3B82F6',
                        'secondary': '#10B981',
                        'background': '#F9FAFB'
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .page-bg {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 50%, #EC4899 100%);
        }
    </style>
</head>
<body class="page-bg min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-20 w-20 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Camera Permission Agreement</h2>
                <p class="text-lg text-gray-600">Enhanced Eye Tracking for Better Learning</p>
            </div>

            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="space-y-6">
                    <!-- Main Description -->
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Improve Your Learning Experience</h3>
                        <p class="text-gray-600 text-lg">Our eye tracking technology helps monitor your engagement and provides personalized learning insights.</p>
                    </div>

                    <!-- Feature Grid -->
                    <div class="grid md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-green-500 rounded-full p-2 mr-3">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Focus Tracking</h4>
                            </div>
                            <p class="text-gray-600">Monitor attention levels during learning sessions</p>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-500 rounded-full p-2 mr-3">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Learning Analytics</h4>
                            </div>
                            <p class="text-gray-600">Detailed insights about your study patterns</p>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-purple-500 rounded-full p-2 mr-3">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Personalized Feedback</h4>
                            </div>
                            <p class="text-gray-600">Recommendations based on engagement data</p>
                        </div>

                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-orange-500 rounded-full p-2 mr-3">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Privacy Protected</h4>
                            </div>
                            <p class="text-gray-600">All data is encrypted and used only for learning</p>
                        </div>
                    </div>

                    <!-- Permission Agreement -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Permission Agreement</h4>
                        <p class="text-gray-700 mb-4">By checking the box below, you agree to:</p>
                        <ul class="text-gray-700 space-y-2 mb-4">
                            <li class="flex items-start">
                                <span class="text-primary mr-2">•</span>
                                <span>Allow this application to access your camera for eye tracking purposes</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-primary mr-2">•</span>
                                <span>The collection of gaze and attention data during learning sessions</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-primary mr-2">•</span>
                                <span>Use of this data to improve your learning experience and provide analytics</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-primary mr-2">•</span>
                                <span>Storage of anonymized engagement data for educational insights</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-primary mr-2">•</span>
                                <span>Automatic eye tracking when browsing learning modules</span>
                            </li>
                        </ul>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h5 class="font-medium text-gray-900 mb-2">Important Notes:</h5>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• No video recordings are stored - only gaze direction and focus data</li>
                                <li>• You can disable eye tracking in your account settings at any time</li>
                                <li>• Data is encrypted and used exclusively for educational purposes</li>
                                <li>• Your privacy is protected and data is not shared with third parties</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Camera Test -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Camera Test</h4>
                        <div id="camera-status" class="text-gray-600 mb-4">Testing camera access...</div>
                        <div class="relative bg-gray-200 rounded-lg overflow-hidden" style="height: 200px;">
                            <video id="camera-preview" class="w-full h-full object-cover" autoplay muted style="display: none;"></video>
                            <div id="camera-placeholder" class="flex items-center justify-center h-full text-gray-500">
                                <svg class="h-12 w-12 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span>Camera preview will appear here</span>
                            </div>
                        </div>
                        <div id="camera-success" class="mt-4 text-green-600 font-medium" style="display: none;">
                            ✅ Camera access successful!
                        </div>
                    </div>

                    <!-- Camera Status Indicator -->
                    <div class="flex justify-center mb-6">
                        <div id="camera-working-indicator" class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-500 rounded-lg border-2 border-gray-300" style="display: none;">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span id="camera-status-text">Testing camera...</span>
                        </div>
                    </div>

                    <!-- Agreement Form -->
                    <form method="POST" class="space-y-6">
                        <div class="flex items-start">
                            <input type="checkbox" id="understand_agreement" class="mt-1 h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary" required>
                            <label for="understand_agreement" class="ml-3 text-sm text-gray-700">
                                I agree to the camera permission and eye tracking terms <span class="text-red-500">*</span>
                            </label>
                        </div>

                        <div class="flex justify-center pt-4">
                            <button type="submit" name="accept_agreement" id="continue-btn" disabled
                                    class="inline-flex justify-center items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gray-400 cursor-not-allowed transition-colors">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                Continue to Dashboard
                            </button>
                        </div>
                    </form>

                    <div class="text-center text-xs text-gray-500 border-t pt-4">
                        <p>By continuing, you agree to our Privacy Policy and Terms of Service.</p>
                        <p class="mt-1">For technical support, contact: <a href="mailto:support@eyellearn.com" class="text-primary hover:underline">support@eyellearn.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cameraStream = null;

        // Test camera availability and show preview
        document.addEventListener('DOMContentLoaded', function() {
            // Show camera indicator
            const indicator = document.getElementById('camera-working-indicator');
            indicator.style.display = 'inline-flex';
            
            testCameraAccess();
            
            // Enable continue button when checkbox is checked and camera works
            const checkbox = document.getElementById('understand_agreement');
            const continueBtn = document.getElementById('continue-btn');
            
            checkbox.addEventListener('change', function() {
                if (this.checked && cameraStream) {
                    continueBtn.disabled = false;
                    continueBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    continueBtn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'focus:ring-blue-500');
                } else {
                    continueBtn.disabled = true;
                    continueBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                    continueBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'focus:ring-blue-500');
                }
            });
        });

        async function testCameraAccess() {
            const statusEl = document.getElementById('camera-status');
            const videoEl = document.getElementById('camera-preview');
            const placeholderEl = document.getElementById('camera-placeholder');
            const successEl = document.getElementById('camera-success');
            const indicator = document.getElementById('camera-working-indicator');
            const statusText = document.getElementById('camera-status-text');

            try {
                statusEl.textContent = 'Requesting camera permission...';
                statusText.textContent = 'Requesting camera permission...';
                
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 480 } 
                });
                
                cameraStream = stream;
                videoEl.srcObject = stream;
                
                videoEl.onloadedmetadata = () => {
                    placeholderEl.style.display = 'none';
                    videoEl.style.display = 'block';
                    statusEl.textContent = 'Camera is working properly!';
                    successEl.style.display = 'block';
                    
                    // Update camera indicator to show success
                    indicator.classList.remove('bg-gray-100', 'text-gray-500', 'border-gray-300');
                    indicator.classList.add('bg-green-100', 'text-green-600', 'border-green-300');
                    statusText.innerHTML = `
                        <svg class="h-4 w-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Camera Working
                    `;
                };
                
            } catch (error) {
                console.error('Camera access error:', error);
                statusEl.innerHTML = `
                    <span class="text-red-600">⚠️ Camera access denied or unavailable</span><br>
                    <span class="text-sm text-gray-500">Please allow camera access and refresh the page</span>
                `;
                
                // Update camera indicator to show error
                indicator.classList.remove('bg-gray-100', 'text-gray-500', 'border-gray-300');
                indicator.classList.add('bg-red-100', 'text-red-600', 'border-red-300');
                statusText.innerHTML = `
                    <svg class="h-4 w-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Camera Access Denied
                `;
                
                // Show warning if camera access fails
                const warning = document.createElement('div');
                warning.className = 'bg-red-50 border border-red-200 rounded-lg p-4 mt-4';
                warning.innerHTML = `
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div class="text-sm text-red-800">
                            <p class="font-medium">Camera Permission Required</p>
                            <p class="mt-1">Eye tracking requires camera access. Please refresh the page and allow camera permission to continue.</p>
                        </div>
                    </div>
                `;
                statusEl.appendChild(warning);
            }
        }

        // Clean up camera stream when leaving page
        window.addEventListener('beforeunload', function() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
