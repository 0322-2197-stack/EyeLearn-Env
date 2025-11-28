<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();

// Get user information
$user_id = $_SESSION['user_id'];
// Check if section column exists
$check_section = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
$has_section = $check_section && $check_section->num_rows > 0;

if ($has_section) {
    $sql = "SELECT first_name, last_name, email, gender, section FROM users WHERE id = ?";
} else {
    $sql = "SELECT first_name, last_name, email, gender FROM users WHERE id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    $user_display_name = $user['first_name'] . ' ' . $user['last_name'];
    $user_email = $user['email'];
    $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
} else {
    // Fallback if user not found
    $user_display_name = "User Student";
    $user_email = "student@example.com";
    $initials = "US";
}

// Add this near the top after database connection
require_once 'dashboard_analytics.php';

// Get analytics data
$focus_data = getWeeklyFocusScore($conn, $user_id);
$comprehension_data = getComprehensionLevel($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EyeLearn - AI-Enhanced E-Learning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="src/output.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#3B82F6',
                        'secondary': '#10B981',
                        'background': '#F9FAFB',
                        'ibm-blue': '#0f62fe'
                    }
                }
            }
        }
    </script>
    <style>
        /* Sidebar styling */
        .sidebar {
            width: 240px;
            background-color: white;
            transition: width 0.3s ease;
        }
        
        .sidebar-collapsed {
            /* width: 64px; */
            width: 0;
            overflow: hidden;
        }
        
        /* Active indicator */
        .nav-indicator {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #3B82F6;
            opacity: 0;
        }
        
        .nav-item.active .nav-indicator {
            opacity: 1;
        }
        
        .nav-item.active {
            background-color: #F0F7FF;
        }
        
        /* Content area */
        .main-content {
            margin-left: 240px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content-collapsed {
            margin-left: 64px;
        }
        
        /* Profile dropdown */
        .profile-dropdown {
            display: none;
            position: absolute;
            right: 1rem;
            top: 4.5rem;
            width: 240px;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            /* z-index: 50; */
        }
        
        .profile-dropdown.show {
            display: block;
        }
        
        /* Responsive behavior */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                /* z-index: 50; */
                height: 100%;
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .backdrop {
                background-color: rgba(0, 0, 0, 0.5);
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                /* z-index: 40; */
                display: none;
            }
            
            .backdrop.active {
                display: block;
            }
        }

        /* 🔹 AI Feedback Formatting */
            #ai-feedback-text ul {
                list-style-type: disc;
                padding-left: 1.5rem;
                margin-top: 0.5rem;
                margin-bottom: 0.5rem;
            }

            #ai-feedback-text ol {
                list-style-type: decimal;
                padding-left: 1.5rem;
                margin-top: 0.5rem;
                margin-bottom: 0.5rem;
            }

            #ai-feedback-text li {
                margin-bottom: 0.25rem;
            }

            #ai-feedback-text a {
                color: #2563eb; /* Tailwind blue-600 */
                font-weight: 500;
                text-decoration: underline;
                transition: color 0.2s ease;
            }

            #ai-feedback-text a:hover {
                color: #1e40af; /* Tailwind blue-800 */
            }

        /* Profile Modal Animations */
        #profileModal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #profileModal:not(.hidden) {
            opacity: 1;
            animation: fadeIn 0.3s ease-out;
        }

        #profileModalContent {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Enhanced form input focus effects */
        #profileForm input:focus,
        #profileForm select:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

    </style>
</head>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 h-16 bg-white shadow-md z-30 flex items-center justify-between px-4">
        <!-- Left side - Menu toggle and title -->
        <div class="flex items-center">
            <button id="toggle-sidebar" class="text-gray-500 hover:text-gray-700 p-2 mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-xl font-bold text-primary">EyeLearn</h1>
        </div>
        
        <!-- Right side - Notifications and Profile -->
        <div class="flex items-center space-x-4">
            
            <!-- Profile dropdown -->
            <div class="profile-container relative">
                <button id="profile-toggle" class="flex items-center space-x-2 focus:outline-none">
                    <div class="bg-primary rounded-full w-8 h-8 flex items-center justify-center text-white font-medium text-sm">
                        <?php echo $initials; ?>
                    </div>
                    <span class="hidden md:inline-block font-medium text-gray-700"><?php echo $user_display_name; ?></span>
                </button>
                
                <!-- Dropdown menu -->
                <div id="profile-dropdown" class="profile-dropdown">
                    <div class="p-4 border-b">
                        <p class="font-medium text-gray-800"><?php echo $user_display_name; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $user_email; ?></p>
                    </div>
                    <div class="p-2">
                        <a href="#" onclick="openProfileModal()" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Your Profile</a>
                        <a href="../logout.php" onclick="handleLogout(event)" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="flex min-h-screen pt-16">
        <!-- Mobile backdrop -->
        <div id="backdrop" class="backdrop"></div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar fixed left-0 top-16 h-full shadow-lg z-40 flex flex-col transition-all duration-300 ease-in-out">
            <!-- Navigation -->
            <nav class="mt-6 flex-1">
                <ul>
                    <!-- Dashboard -->
                    <li class="nav-item relative" id="dashboard-item">
                        <div class="nav-indicator"></div>
                        <a href="Sdashboard.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="dashboard-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Modules -->
                    <li class="nav-item relative" id="modules-item">
                        <div class="nav-indicator"></div>
                        <a href="Smodule.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="modules-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Modules</span>
                        </a>
                    </li>
                    
                    <!-- Assessments -->
                    <li class="nav-item relative" id="assessments-item">
                        <div class="nav-indicator"></div>
                        <a href="Sassessment.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="assessments-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Assessment History</span>
                        </a>
                    </li>
                    
                </ul>
            </nav>
        </div>
        
        <!-- Mobile Header -->
        <div class="md:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 flex items-center justify-between p-4">
            <button id="mobile-menu-toggle" class="text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-xl font-bold text-primary">EyeLearn</h1>
            <div class="w-6 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </div>
        </div>
                    
        <main id="main-content" class="main-content flex-1 p-3 transition-all duration-300">
        <div class="p-3">
                <!-- Welcome Section -->
                <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Welcome, <?php echo htmlspecialchars($user_display_name); ?>!</h2>
                        <div class="flex items-center bg-blue-50 text-primary px-3 py-1 rounded-full">
                            <i class="fas fa-eye mr-2"></i>
                            <span class="text-sm font-medium">Eye tracking active</span>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-3 text-sm">Your personalized learning journey continues. Based on your eye tracking data, we've tailored your content to optimize your comprehension.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 border-t border-gray-200 pt-3">
                        <div class="bg-blue-50 p-3 rounded-md border border-blue-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Weekly Focus Score</p>
                                    <p class="text-2xl font-bold text-primary"><?php echo $focus_data['current_score']; ?>%</p>
                                </div>
                                <i class="fas fa-eye text-primary text-xl"></i>
                            </div>
                            <div class="mt-2">
                                <?php
                                $focus_difference = $focus_data['current_score'] - $focus_data['previous_score'];
                                $focus_class = $focus_difference >= 0 ? 'text-green-600' : 'text-red-600';
                                $focus_arrow = $focus_difference >= 0 ? '▲' : '▼';
                                ?>
                                <div class="text-xs <?php echo $focus_class; ?>">
                                    <?php echo $focus_arrow . ' ' . abs($focus_difference) . '% from last week'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-md border border-green-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Comprehension Level</p>
                                    <p class="text-2xl font-bold text-secondary"><?php echo $comprehension_data['level']; ?></p>
                                </div>
                                <i class="fas fa-brain text-secondary text-xl"></i>
                            </div>
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-secondary h-2 rounded-full" style="width: <?php echo $comprehension_data['percentage']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                       
                    </div>
                </div>


                <!-- Eye-Tracking Analytics -->
                <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3" id="analytics-section">
                    <div class="flex items-center justify-between mb-2 border-b border-gray-200 pb-2">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Your Learning Analytics</h3>
                        <div class="flex items-center space-x-2">
                            <div id="live-indicator" class="hidden">
                                <span class="flex items-center text-xs text-green-600">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                    Live Data
                                </span>
                            </div>
                            <button onclick="refreshAnalytics()" class="text-primary hover:underline text-sm">🔄 Refresh Data</button>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="analytics-loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                        <p class="text-gray-500">Loading your analytics...</p>
                    </div>
                    
                    <!-- Analytics Content -->
                    <div id="analytics-content" class="hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                            <!-- Focus Trends Chart -->
                            <div class="border border-gray-200 rounded-md p-3">
                                <div class="mb-2">
                                    <h4 class="font-medium text-gray-900 mb-2">Your Focus Trends (Last 7 Days)</h4>
                                    <div class="h-48 bg-gray-50 rounded-md flex items-center justify-center border border-gray-200">
                                        <canvas id="focusChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-md border border-blue-100 mt-2" id="analytics-insight">
                                    <p class="text-gray-700 text-sm">
                                        <i class="fas fa-lightbulb text-primary mr-2"></i>
                                        <span id="insight-text">Loading personalized insights...</span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Real-time Metrics -->
                            <div class="border border-gray-200 rounded-md p-3">
                                <div class="mb-2">
                                    <h4 class="font-medium text-gray-900 mb-2">Study Performance Metrics</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="bg-gray-50 p-2 rounded-md border border-gray-200">
                                            <div class="text-sm text-gray-500">Total Study Time</div>
                                            <div class="text-lg font-bold text-gray-800" id="total-study-time">0 hrs</div>
                                            <div class="text-xs text-blue-600" id="study-time-change">Eye tracking enabled</div>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded-md border border-gray-200">
                                            <div class="text-sm text-gray-500">Focus Efficiency</div>
                                            <div class="text-lg font-bold text-gray-800" id="focus-efficiency">0%</div>
                                            <div class="text-xs text-green-600" id="efficiency-trend">Calculating...</div>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded-md border border-gray-200">
                                            <div class="text-sm text-gray-500">Avg Session</div>
                                            <div class="text-lg font-bold text-gray-800" id="avg-session">0 min</div>
                                            <div class="text-xs text-green-600" id="session-quality">Good sessions</div>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded-md border border-gray-200">
                                            <div class="text-sm text-gray-500">Modules Studied</div>
                                            <div class="text-lg font-bold text-gray-800" id="modules-studied">0</div>
                                            <div class="text-xs text-orange-600" id="module-progress">Keep learning!</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 border-t border-gray-200 pt-3">
                                    <h4 class="font-medium text-gray-900 mb-2">AI Learning Insights</h4>
                                    <ul class="space-y-1 divide-y divide-gray-200" id="learning-insights">
                                        <li class="flex items-start">
                                            <i class="fas fa-spinner fa-spin text-blue-500 mt-1 mr-2"></i>
                                            <span class="text-sm text-gray-700">Analyzing your learning patterns...</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Module Performance Section -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <h4 class="font-medium text-gray-900 mb-2">Module Performance Overview</h4>
                            <!-- Combined layout for Module Performance + AI Feedback -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                        <!-- Module Performance Overview -->
                                        <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4">
                                            <h2 class="text-lg font-semibold text-gray-900 mb-2">Module Performance Overview</h2>
                                            <div id="module-performance" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <!-- Existing module performance cards will populate here -->
                                            </div>
                                        </div>

                                        <!-- AI Mentor Feedback Panel -->
                                        <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 relative">
                                            <h2 class="text-lg font-semibold text-gray-900 mb-2">AI Mentor Feedback</h2>

                                            <?php
                                            // Get only the latest attempt per module (from both original results and retake results)
                                            // This query groups by module_id and gets the most recent attempt for each module
                                            $quiz_results_query = $conn->query("
                                                SELECT 
                                                    latest.id,
                                                    latest.quiz_id,
                                                    latest.module_id,
                                                    latest.score,
                                                    latest.completion_date,
                                                    latest.source,
                                                    m.title as module_title,
                                                    fq.title as quiz_title,
                                                    (SELECT COUNT(*) FROM final_quiz_questions WHERE quiz_id = latest.quiz_id) as total_questions,
                                                    ar.ai_feedback,
                                                    ar.created_at as feedback_created_at
                                                FROM (
                                                    SELECT 
                                                        result.id,
                                                        result.quiz_id,
                                                        result.module_id,
                                                        result.score,
                                                        result.completion_date,
                                                        result.source
                                                    FROM (
                                                        SELECT 
                                                            id,
                                                            quiz_id,
                                                            module_id,
                                                            score,
                                                            completion_date,
                                                            'original' as source
                                                        FROM quiz_results
                                                        WHERE user_id = $user_id
                                                        
                                                        UNION ALL
                                                        
                                                        SELECT 
                                                            id,
                                                            quiz_id,
                                                            module_id,
                                                            score,
                                                            completion_date,
                                                            'retake' as source
                                                        FROM retake_results
                                                        WHERE user_id = $user_id
                                                    ) as result
                                                    INNER JOIN (
                                                        SELECT 
                                                            module_id,
                                                            MAX(completion_date) as max_date
                                                        FROM (
                                                            SELECT module_id, completion_date FROM quiz_results WHERE user_id = $user_id
                                                            UNION ALL
                                                            SELECT module_id, completion_date FROM retake_results WHERE user_id = $user_id
                                                        ) as all_results
                                                        GROUP BY module_id
                                                    ) as latest_per_module
                                                    ON result.module_id = latest_per_module.module_id 
                                                    AND result.completion_date = latest_per_module.max_date
                                                ) as latest
                                                LEFT JOIN modules m ON latest.module_id = m.id
                                                LEFT JOIN final_quizzes fq ON latest.quiz_id = fq.id
                                                LEFT JOIN ai_recommendations ar ON ar.user_id = $user_id
                                                    AND ar.module_id = latest.module_id
                                                    AND ar.quiz_id = latest.quiz_id
                                                ORDER BY latest.completion_date DESC
                                            ");

                                            $quiz_results = [];
                                            $latest_quiz_result_id = null;
                                            $latest_feedback = null;
                                            $latest_score = null;
                                            $latest_total = null;

                                            if ($quiz_results_query && $quiz_results_query->num_rows > 0) {
                                                while ($row = $quiz_results_query->fetch_assoc()) {
                                                    $quiz_results[] = $row;
                                                    if ($latest_quiz_result_id === null) {
                                                        $latest_quiz_result_id = $row['id'];
                                                        $latest_score = intval($row['score']);
                                                        $latest_total = intval($row['total_questions']);
                                                        $latest_feedback = $row['ai_feedback'];
                                                    }
                                                }
                                            }
                                            ?>

                                            <!-- Dynamic Dropdown for Quiz Selection -->
                                            <?php if (count($quiz_results) > 0): ?>
                                            <div class="mb-3">
                                                <label for="quiz-select-dropdown" class="block text-sm font-medium text-gray-700 mb-1">Select Quiz:</label>
                                                <select id="quiz-select-dropdown" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    <?php foreach ($quiz_results as $qr): 
                                                        $score_fraction = $qr['total_questions'] > 0 ? "{$qr['score']}/{$qr['total_questions']}" : "{$qr['score']}";
                                                        $completion_date = new DateTime($qr['completion_date']);
                                                        $date = $completion_date->format('M d, Y');
                                                        $time = $completion_date->format('h:i A');
                                                        $quiz_title = !empty($qr['quiz_title']) ? $qr['quiz_title'] : 'Quiz';
                                                        $source = $qr['source'] ?? 'original';
                                                        $attempt_label = ($source === 'retake') ? ' (Retake)' : '';
                                                    ?>
                                                        <option value="<?php echo $qr['id']; ?>" 
                                                                data-quiz-id="<?php echo $qr['quiz_id']; ?>"
                                                                data-module-id="<?php echo $qr['module_id']; ?>"
                                                                data-score="<?php echo $qr['score']; ?>"
                                                                data-total="<?php echo $qr['total_questions']; ?>"
                                                                data-feedback="<?php echo htmlspecialchars($qr['ai_feedback'] ?? '', ENT_QUOTES); ?>"
                                                                data-quiz-title="<?php echo htmlspecialchars($quiz_title, ENT_QUOTES); ?>"
                                                                data-source="<?php echo $source; ?>"
                                                                <?php echo $qr['id'] == $latest_quiz_result_id ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($quiz_title); ?><?php echo $attempt_label; ?> - Score: <?php echo $score_fraction; ?> - <?php echo $date; ?> <?php echo $time; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>

                                            <div id="ai-feedback-container" class="mt-4">
                                                <?php if ($latest_feedback && $latest_total !== null): 
                                                    $score_fraction = "{$latest_score}/{$latest_total}";
                                                    $score_percentage = $latest_total > 0 ? round(($latest_score / $latest_total) * 100, 2) : 0;
                                                ?>
                                                    <div 
                                                        id="ai-feedback-card"
                                                        class="relative bg-gray-50 p-3 rounded-md border border-gray-200 cursor-pointer transition-all duration-500 ease-in-out overflow-hidden max-h-32 hover:shadow-sm"
                                                    >
                                                       <div id="ai-feedback-text" class="space-y-1 text-sm text-gray-600">
                                                            <?php echo $latest_feedback; ?>
                                                            </div>

                                                        <!-- Gradient fade + hint -->
                                                        <div id="ai-feedback-hint" class="absolute bottom-0 left-0 w-full text-center hover:bg-green-300 py-1 text-sm shadow-sm bg-green-100 text-green-800">
                                                            <span>Click to expand</span>
                                                        </div>

                                                        <!-- Close button -->
                                                     <button 
                                                        id="ai-feedback-close"
                                                        class="hidden absolute top-2 right-2 bg-white text-gray-600 border border-gray-200 hover:text-red-500 hover:border-red-300 hover:bg-red-50 hover:rotate-90 transition-all duration-300 rounded-full w-8 h-8 flex items-center justify-center shadow-sm"
                                                        title="Close"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>

                                                    </div>
                                                <?php elseif ($latest_score !== null && $latest_total !== null): 
                                                    $score_percentage = $latest_total > 0 ? round(($latest_score / $latest_total) * 100, 2) : 0;
                                                    $score_fraction = "{$latest_score}/{$latest_total}";
                                                ?>
                                                    <?php if ($score_percentage < 70): ?>
                                                        <div class="text-center text-gray-600">
                                                            <p class="mb-4">Your score was <strong><?php echo $score_fraction; ?></strong> (<?php echo $score_percentage; ?>%) — let's generate personalized study tips!</p>
                                                            <button id="generate-ai-btn"
                                                                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                                                                data-quiz-id="<?php echo $quiz_results[0]['quiz_id'] ?? ''; ?>"
                                                                data-module-id="<?php echo $quiz_results[0]['module_id'] ?? ''; ?>"
                                                                data-score="<?php echo $latest_score; ?>"
                                                                data-module-title="<?php echo htmlspecialchars($quiz_results[0]['module_title'] ?? ''); ?>">
                                                                Generate AI Feedback
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-center text-green-700">
                                                            <p class="mb-2 text-lg font-semibold">🎉 Excellent work!</p>
                                                            <p>You scored <strong><?php echo $score_fraction; ?></strong> (<?php echo $score_percentage; ?>%). Keep up the great progress!</p>
                                                            <p class="text-sm text-gray-500 mt-2">No AI feedback needed — your performance is on track!</p>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <p class="text-gray-500 text-center">No AI feedback available yet. Complete a quiz to see insights here.</p>
                                                <?php endif; ?>
                                            </div>


                                            <div id="ai-feedback-loader" class="hidden absolute inset-0 bg-white/80 flex items-center justify-center rounded-lg">
                                                <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- JS to handle Dynamic Dropdown and AI Feedback generation -->
                                        <script>
                                        document.addEventListener('DOMContentLoaded', () => {
                                            const dropdown = document.getElementById('quiz-select-dropdown');
                                            const container = document.getElementById('ai-feedback-container');
                                            const loader = document.getElementById('ai-feedback-loader');
                                            const btn = document.getElementById('generate-ai-btn');

                                            // Function to update feedback display based on selected quiz
                                            function updateFeedbackDisplay(selectedOption) {
                                                if (!selectedOption) return;
                                                
                                                const score = parseInt(selectedOption.dataset.score);
                                                const total = parseInt(selectedOption.dataset.total);
                                                const feedback = selectedOption.dataset.feedback;
                                                const quizTitle = selectedOption.dataset.quizTitle || 'Quiz';
                                                const scorePercentage = total > 0 ? Math.round((score / total) * 100 * 100) / 100 : 0;
                                                const scoreFraction = `${score}/${total}`;

                                                if (feedback && feedback.trim() !== '') {
                                                    // Show existing feedback
                                                    container.innerHTML = `
                                                        <div 
                                                            id="ai-feedback-card"
                                                            class="relative bg-gray-50 p-3 rounded-md border border-gray-200 cursor-pointer transition-all duration-500 ease-in-out overflow-hidden max-h-32 hover:shadow-sm"
                                                        >
                                                            <div id="ai-feedback-text" class="space-y-1 text-sm text-gray-600">
                                                                ${feedback}
                                                            </div>
                                                            <div id="ai-feedback-hint" class="absolute bottom-0 left-0 w-full text-center hover:bg-green-300 py-1 text-sm shadow-sm bg-green-100 text-green-800">
                                                                <span>Click to expand</span>
                                                            </div>
                                                            <button 
                                                                id="ai-feedback-close"
                                                                class="hidden absolute top-2 right-2 bg-white text-gray-600 border border-gray-200 hover:text-red-500 hover:border-red-300 hover:bg-red-50 hover:rotate-90 transition-all duration-300 rounded-full w-8 h-8 flex items-center justify-center shadow-sm"
                                                                title="Close"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    `;
                                                    initFeedbackCard();
                                                } else if (scorePercentage < 70) {
                                                    // Show generate button for low scores
                                                    // Get module title from the dropdown option text or fetch it
                                                    const optionText = selectedOption.textContent;
                                                    const moduleTitle = optionText.split(' - ')[0] || '';
                                                    
                                                    container.innerHTML = `
                                                        <div class="text-center text-gray-600">
                                                            <p class="mb-4">Your score was <strong>${scoreFraction}</strong> (${scorePercentage}%) — let's generate personalized study tips!</p>
                                                            <button id="generate-ai-btn"
                                                                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                                                                data-quiz-id="${selectedOption.dataset.quizId}"
                                                                data-module-id="${selectedOption.dataset.moduleId}"
                                                                data-score="${score}"
                                                                data-quiz-title="${quizTitle}"
                                                                data-module-title="${moduleTitle}">
                                                                Generate AI Feedback
                                                            </button>
                                                        </div>
                                                    `;
                                                    attachGenerateButtonListener();
                                                } else {
                                                    // Show success message for high scores
                                                    container.innerHTML = `
                                                        <div class="text-center text-green-700">
                                                            <p class="mb-2 text-lg font-semibold">🎉 Excellent work!</p>
                                                            <p>You scored <strong>${scoreFraction}</strong> (${scorePercentage}%). Keep up the great progress!</p>
                                                            <p class="text-sm text-gray-500 mt-2">No AI feedback needed — your performance is on track!</p>
                                                        </div>
                                                    `;
                                                }
                                            }

                                            // Handle dropdown change
                                            if (dropdown) {
                                                dropdown.addEventListener('change', function() {
                                                    const selectedOption = this.options[this.selectedIndex];
                                                    updateFeedbackDisplay(selectedOption);
                                                });
                                            }

                                            // Function to initialize feedback card interactions
                                            function initFeedbackCard() {
                                                const card = document.getElementById('ai-feedback-card');
                                                const hint = document.getElementById('ai-feedback-hint');
                                                const closeBtn = document.getElementById('ai-feedback-close');
                                                const text = document.getElementById('ai-feedback-text');
                                                
                                                if (!card) return;
                                                
                                                let isExpanded = false;
                                                
                                                card.addEventListener('click', function(e) {
                                                    if (e.target.closest('#ai-feedback-close')) return;
                                                    
                                                    isExpanded = !isExpanded;
                                                    if (isExpanded) {
                                                        card.classList.remove('max-h-32');
                                                        card.classList.add('max-h-none');
                                                        hint.classList.add('hidden');
                                                        if (closeBtn) closeBtn.classList.remove('hidden');
                                                    } else {
                                                        card.classList.add('max-h-32');
                                                        card.classList.remove('max-h-none');
                                                        hint.classList.remove('hidden');
                                                        if (closeBtn) closeBtn.classList.add('hidden');
                                                    }
                                                });
                                                
                                                if (closeBtn) {
                                                    closeBtn.addEventListener('click', function(e) {
                                                        e.stopPropagation();
                                                        isExpanded = false;
                                                        card.classList.add('max-h-32');
                                                        card.classList.remove('max-h-none');
                                                        hint.classList.remove('hidden');
                                                        closeBtn.classList.add('hidden');
                                                    });
                                                }
                                            }

                                            // Function to attach generate button listener
                                            function attachGenerateButtonListener() {
                                                const generateBtn = document.getElementById('generate-ai-btn');
                                                if (!generateBtn) return;

                                                generateBtn.addEventListener('click', async () => {
                                                    loader.classList.remove('hidden');
                                                    generateBtn.disabled = true;
                                                    generateBtn.textContent = 'Generating...';

                                                    try {
                                                        const quizId = generateBtn.dataset.quizId;
                                                        const moduleId = generateBtn.dataset.moduleId;
                                                        const score = parseInt(generateBtn.dataset.score);
                                                        const quizTitle = generateBtn.dataset.quizTitle || 'Quiz';

                                                        // Fetch wrong questions and module info for this quiz
                                                        const wrongRes = await fetch(`get_quiz_feedback.php?quiz_result_id=${dropdown.value}`);
                                                        const wrongData = await wrongRes.json();
                                                        
                                                        let wrongQuestions = [];
                                                        let moduleTitle = generateBtn.dataset.moduleTitle || '';
                                                        
                                                        if (wrongData.success && wrongData.quiz) {
                                                            if (wrongData.quiz.wrong_questions) {
                                                                wrongQuestions = wrongData.quiz.wrong_questions;
                                                            }
                                                            // Use module_title from API if available
                                                            if (wrongData.quiz.module_title) {
                                                                moduleTitle = wrongData.quiz.module_title;
                                                            }
                                                        }

                                                        // Call gemini service - use quiz title as module_title for better context
                                                        const aiRes = await fetch('gemini_service.php', {
                                                            method: 'POST',
                                                            headers: { 'Content-Type': 'application/json' },
                                                            body: JSON.stringify({
                                                                user_id: <?php echo $user_id; ?>,
                                                                module_id: parseInt(moduleId),
                                                                quiz_id: parseInt(quizId),
                                                                score: score,
                                                                wrong_questions: wrongQuestions,
                                                                module_title: quizTitle // Use quiz title instead of module title
                                                            })
                                                        });

                                                        const aiData = await aiRes.json();

                                                        if (aiData.success && aiData.ai_feedback) {
                                                            const isUnavailable = aiData.ai_feedback.includes('unavailable') || 
                                                                                  aiData.ai_feedback.includes('⚠️') ||
                                                                                  aiData.ai_feedback.includes('retry') ||
                                                                                  aiData.ai_feedback.includes('AI service returned');
                                                            
                                                            if (isUnavailable) {
                                                                container.innerHTML = `
                                                                    <div class="text-center text-gray-600">
                                                                        <p class="mb-4 text-red-600">${aiData.ai_feedback}</p>
                                                                        <button id="generate-ai-btn"
                                                                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                                                                            data-quiz-id="${quizId}"
                                                                            data-module-id="${moduleId}"
                                                                            data-score="${score}"
                                                                            data-quiz-title="${quizTitle}">
                                                                            🔄 Generate Prompt Again
                                                                        </button>
                                                                    </div>
                                                                `;
                                                                attachGenerateButtonListener();
                                                            } else {
                                                                container.innerHTML = `
                                                                    <div 
                                                                        id="ai-feedback-card"
                                                                        class="relative bg-gray-50 p-4 rounded-md cursor-pointer transition-all duration-500 ease-in-out overflow-hidden max-h-32 hover:shadow-md"
                                                                    >
                                                                        <div id="ai-feedback-text" class="space-y-1 text-sm text-gray-600">
                                                                            ${aiData.ai_feedback}
                                                                        </div>
                                                                        <div id="ai-feedback-hint" class="absolute bottom-0 left-0 w-full text-center py-1 text-sm shadow-sm bg-green-100 text-green-800">
                                                                            <span>Click to expand</span>
                                                                        </div>
                                                                        <button 
                                                                            id="ai-feedback-close"
                                                                            class="hidden absolute top-2 right-2 bg-white text-gray-600 border border-gray-200 hover:text-red-500 hover:border-red-300 hover:bg-red-50 hover:rotate-90 transition-all duration-300 rounded-full w-8 h-8 flex items-center justify-center shadow-sm"
                                                                            title="Close"
                                                                        >
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                `;
                                                                container.style.display = "block";
                                                                initFeedbackCard();
                                                                // Update dropdown option with new feedback
                                                                const selectedOption = dropdown.options[dropdown.selectedIndex];
                                                                selectedOption.dataset.feedback = aiData.ai_feedback;
                                                            }
                                                        } else {
                                                            const errorMessage = aiData.error || 'Failed to generate feedback.';
                                                            container.innerHTML = `
                                                                <div class="text-center text-gray-600">
                                                                    <p class="mb-4 text-red-600">⚠️ ${errorMessage}</p>
                                                                    <button id="generate-ai-btn"
                                                                        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                                                                        data-quiz-id="${quizId}"
                                                                        data-module-id="${moduleId}"
                                                                        data-score="${score}"
                                                                        data-quiz-title="${quizTitle}">
                                                                        🔄 Generate Prompt Again
                                                                    </button>
                                                                </div>
                                                            `;
                                                            attachGenerateButtonListener();
                                                        }
                                                    } catch (err) {
                                                        console.error('AI feedback error:', err);
                                                        container.innerHTML = `
                                                            <div class="text-center text-gray-600">
                                                                <p class="mb-4 text-red-600">⚠️ Error: ${err.message}</p>
                                                                <button id="generate-ai-btn"
                                                                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                                                                    data-quiz-id="${generateBtn.dataset.quizId}"
                                                                    data-module-id="${generateBtn.dataset.moduleId}"
                                                                    data-score="${generateBtn.dataset.score}"
                                                                    data-quiz-title="${generateBtn.dataset.quizTitle || 'Quiz'}">
                                                                    🔄 Generate Prompt Again
                                                                </button>
                                                            </div>
                                                        `;
                                                        attachGenerateButtonListener();
                                                    } finally {
                                                        loader.classList.add('hidden');
                                                        if (generateBtn) {
                                                            generateBtn.disabled = false;
                                                            generateBtn.textContent = 'Generate AI Feedback';
                                                        }
                                                    }
                                                });
                                            }

                                            // Initialize on page load
                                            if (btn) {
                                                attachGenerateButtonListener();
                                            }
                                            
                                            // Initialize feedback card if it exists
                                            initFeedbackCard();

                                            btn.addEventListener('click', async () => {
                                                loader.classList.remove('hidden');
                                                btn.disabled = true;
                                                btn.textContent = 'Generating...';

                                                try {
                                                    // 1️⃣ Fetch latest quiz details from your DB
                                                    // We'll get the most recent quiz with score < 70
                                                    const res = await fetch('get_latest_quiz.php');
                                                    const quizData = await res.json();

                                                    if (!quizData.success || !quizData.quiz) {
                                                        throw new Error('No recent quiz data found');
                                                    }

                                                    const { user_id, module_id, quiz_id, score, wrong_questions, module_title } = quizData.quiz;

                                                    // 2️⃣ Call your existing gemini_service.php with the same payload format as Smodulepart.php
                                                    const aiRes = await fetch('gemini_service.php', {
                                                        method: 'POST',
                                                        headers: { 'Content-Type': 'application/json' },
                                                        body: JSON.stringify({
                                                            user_id,
                                                            module_id,
                                                            quiz_id,
                                                            score,
                                                            wrong_questions,
                                                            module_title
                                                        })
                                                    });

                                                    const aiData = await aiRes.json();
                                                    console.log('AI response:', aiData);

                                                    // 3️⃣ Update UI
                                                    if (aiData.success && aiData.ai_feedback) {
                                                        // Inject the full feedback card (same structure you use on page load)
                                                        container.innerHTML = `
                                                            <div 
                                                                id="ai-feedback-card"
                                                                class="relative bg-gray-50 p-4 rounded-md cursor-pointer transition-all duration-500 ease-in-out overflow-hidden max-h-32 hover:shadow-md"
                                                            >
                                                                <div id="ai-feedback-text" class="space-y-1 text-sm text-gray-600">
                                                                    ${aiData.ai_feedback}
                                                                </div>

                                                                <div id="ai-feedback-hint" class="absolute bottom-0 left-0 w-full text-center py-1 text-sm shadow-sm bg-green-100 text-green-800">
                                                                    <span>Click to expand</span>
                                                                </div>

                                                                <button 
                                                                    id="ai-feedback-close"
                                                                    class="hidden absolute top-2 right-2 bg-white text-gray-600 border border-gray-200 hover:text-red-500 hover:border-red-300 hover:bg-red-50 hover:rotate-90 transition-all duration-300 rounded-full w-8 h-8 flex items-center justify-center shadow-sm"
                                                                    title="Close"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        `;

                                                        // 🔥 Make sure the feedback card becomes visible *immediately* after generation
                                                        container.style.display = "block";
                                                        container.scrollIntoView({ behavior: "smooth", block: "center" });

                                                        // 🔄 Reinitialize expand/collapse logic (since this card is newly added)
                                                        initFeedbackCard();
                                                    }

                                                    
                                                    else {
                                                        container.innerHTML = `<p class="text-red-600 text-center">⚠️ ${aiData.error || 'Failed to generate feedback.'}</p>`;
                                                    }
                                                } catch (err) {
                                                    console.error('AI feedback error:', err);
                                                    container.innerHTML = `<p class="text-red-600 text-center">⚠️ Error: ${err.message}</p>`;
                                                } finally {
                                                    loader.classList.add('hidden');
                                                    btn.disabled = false;
                                                    btn.textContent = 'Generate AI Feedback';
                                                }
                                            });
                                        });
                                        </script>
                                       
                                       <!-- JS for expanding/collapsing AI feedback card -->
                                        <script>
                                        // Define a reusable function to initialize the click-to-expand behavior
                                            function initFeedbackCard() {
                                                const card = document.getElementById('ai-feedback-card');
                                                const text = document.getElementById('ai-feedback-text');
                                                const closeBtn = document.getElementById('ai-feedback-close');
                                                const hint = document.getElementById('ai-feedback-hint');

                                                if (!card) return; // do nothing if element doesn't exist yet

                                                let expanded = false;
                                                const collapsedHeight = 128; // Tailwind max-h-32

                                                const expandCard = () => {
                                                    expanded = true;
                                                    const expandedHeight = text.scrollHeight + 40;
                                                    card.style.transition = 'max-height 0.6s ease';
                                                    card.style.maxHeight = `${expandedHeight}px`;
                                                    card.classList.add('overflow-y-auto', 'shadow-lg');
                                                    hint.classList.add('hidden');
                                                    closeBtn.classList.remove('hidden');
                                                };

                                                const collapseCard = () => {
                                                    expanded = false;
                                                    card.style.transition = 'max-height 0.6s ease';
                                                    card.style.maxHeight = `${collapsedHeight}px`;
                                                    setTimeout(() => {
                                                        card.classList.remove('overflow-y-auto', 'shadow-lg');
                                                        hint.classList.remove('hidden');
                                                        closeBtn.classList.add('hidden');
                                                        card.scrollTo({ top: 0, behavior: 'smooth' });
                                                    }, 600);
                                                };

                                                card.addEventListener('click', () => {
                                                    if (!expanded) expandCard();
                                                });

                                                closeBtn.addEventListener('click', (e) => {
                                                    e.stopPropagation();
                                                    collapseCard();
                                                });
                                            }

                                            // Run this once when the page first loads
                                            document.addEventListener('DOMContentLoaded', initFeedbackCard);

                                        </script>


                        </div>
                    </div>
                    
                    <!-- Error State -->
                    <div id="analytics-error" class="hidden text-center py-8">
                        <div class="text-red-500 mb-2">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <p class="text-gray-600 mb-2">Unable to load analytics data</p>
                        <button onclick="loadAnalytics()" class="text-primary hover:underline text-sm">Try Again</button>
                    </div>
                </div>

              
            </div>
           </main>
           <!-- Profile Modal -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
    <div class="relative mx-auto w-full max-w-md bg-white rounded-xl shadow-2xl transform transition-all duration-300 scale-95" id="profileModalContent">
        <!-- Close Button -->
        <button onclick="closeProfileModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors duration-200 z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Profile Header with Gradient Background -->
        <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 rounded-t-xl p-6 text-center text-white">
            <div class="relative inline-block">
                <div id="profile-avatar" class="w-24 h-24 bg-white bg-opacity-20 backdrop-blur-sm rounded-full flex items-center justify-center text-white text-3xl font-bold mb-3 shadow-lg border-4 border-white border-opacity-30">
                    <?php echo $initials; ?>
                </div>
                <div class="absolute bottom-0 right-0 bg-white rounded-full p-2 shadow-md">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <h3 id="profile-name" class="text-xl font-bold mb-1"><?php echo htmlspecialchars($user_display_name); ?></h3>
            <div class="flex items-center justify-center gap-2 text-blue-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <p id="profile-email" class="text-sm"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>

        <!-- Profile Form -->
        <form id="profileForm" class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-gray-700 text-sm font-semibold" for="firstName">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        First Name
                    </label>
                    <input type="text" id="firstName" name="first_name" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>"
                           class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-white">
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-gray-700 text-sm font-semibold" for="lastName">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Last Name
                    </label>
                    <input type="text" id="lastName" name="last_name" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>"
                           class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-white">
                </div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-gray-700 text-sm font-semibold" for="gender">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Gender
                </label>
                <select id="gender" name="gender" 
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-white cursor-pointer">
                    <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-gray-700 text-sm font-semibold" for="section">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Section
                </label>
                <select id="section" name="section" 
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-white cursor-pointer">
                    <option value="">Select Section</option>
                    <option value="BSINFO-1A" <?php echo (isset($user['section']) && $user['section'] === 'BSINFO-1A') ? 'selected' : ''; ?>>BSINFO-1A</option>
                    <option value="BSINFO-1B" <?php echo (isset($user['section']) && $user['section'] === 'BSINFO-1B') ? 'selected' : ''; ?>>BSINFO-1B</option>
                    <option value="BSINFO-1C" <?php echo (isset($user['section']) && $user['section'] === 'BSINFO-1C') ? 'selected' : ''; ?>>BSINFO-1C</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeProfileModal()"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 font-medium transition-all duration-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // DOM Elements
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const toggleSidebarBtn = document.getElementById('toggle-sidebar');
    const sidebarEl = document.getElementById('sidebar');
    const mainContentEl = document.getElementById('main-content');
    const navTexts = document.querySelectorAll('.nav-text');
    const backdropEl = document.getElementById('backdrop');
    
    // Navigation items
    const dashboardItem = document.getElementById('dashboard-item');
    const modulesItem = document.getElementById('modules-item');
    const assessmentsItem = document.getElementById('assessments-item');
        
    // Navigation links
    const dashboardLink = document.getElementById('dashboard-link');
    const modulesLink = document.getElementById('modules-link');
    const assessmentsLink = document.getElementById('assessments-link');
    
    // Profile dropdown elements
    const profileToggle = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');

    // State management
    let isSidebarVisible = true;
    const isMobile = () => window.innerWidth < 768;
    
    // Toggle profile dropdown on click
    profileToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking elsewhere on the page
    document.addEventListener('click', function(e) {
        if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
        }
    });
    
    // Function to handle active page styling
    function setActivePage() {
        const currentPage = window.location.pathname.split('/').pop(); // Get the current page file name
        
        // Reset all links
        [dashboardItem, modulesItem, assessmentsItem].forEach(item => {
            item.classList.remove('active');
        });
        
        // Highlight the active link based on the current page
        if (currentPage === 'Sdashboard.php' || currentPage === '' || currentPage === '/') {
            dashboardItem.classList.add('active');
        } else if (currentPage === 'Smodule.php') {
            modulesItem.classList.add('active');
        } else if (currentPage === 'Sassessment.php') {
            assessmentsItem.classList.add('active');
        } else {
            // Default to dashboard if no match
            dashboardItem.classList.add('active');
        }
    }

    // Call the function to set the active page on load
    setActivePage();

    // Analytics functionality
    let focusChart = null;
    let analyticsUpdateInterval = null;

    async function loadAnalytics() {
        const loadingElement = document.getElementById('analytics-loading');
        const contentElement = document.getElementById('analytics-content');
        const errorElement = document.getElementById('analytics-error');

        // Show loading state only on first load
        if (!contentElement.classList.contains('hidden')) {
            // Subsequent loads - show subtle loading indicator
            showSubtleLoading();
        } else {
            // First load - show full loading
            loadingElement.classList.remove('hidden');
            contentElement.classList.add('hidden');
            errorElement.classList.add('hidden');
        }

        try {
            const response = await fetch('database/get_analytics_data.php?t=' + Date.now());
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Failed to load analytics data');
            }

            // Update metrics display
            updateMetricsDisplay(result.data);
            
            // Create focus trends chart
            createFocusChart(result.data.focus_trends);
            
            // Update module performance
            updateModulePerformance(result.data.module_performance);
            
            // Update insights
            updateInsights(result.data);

            // Update real-time status
            updateRealtimeStatus(result.data.realtime_data);

            // Show content
            loadingElement.classList.add('hidden');
            contentElement.classList.remove('hidden');
            hideSubtleLoading();

        } catch (error) {
            console.error('Error loading analytics:', error);
            loadingElement.classList.add('hidden');
            if (contentElement.classList.contains('hidden')) {
                errorElement.classList.remove('hidden');
            }
            hideSubtleLoading();
        }
    }

    function showSubtleLoading() {
        // Add subtle loading indicator for real-time updates
        const refreshBtn = document.querySelector('button[onclick="refreshAnalytics()"]');
        if (refreshBtn) {
            refreshBtn.innerHTML = '🔄 <span class="animate-spin inline-block">⟳</span> Updating...';
        }
    }

    function hideSubtleLoading() {
        const refreshBtn = document.querySelector('button[onclick="refreshAnalytics()"]');
        if (refreshBtn) {
            refreshBtn.innerHTML = '🔄 Refresh Data';
        }
    }

    function updateRealtimeStatus(realtimeData) {
        if (!realtimeData) return;

        // Update last updated timestamp
        const lastUpdated = new Date(realtimeData.last_updated).toLocaleTimeString();
        
        // Show/hide live indicator
        const liveIndicator = document.getElementById('live-indicator');
        if (liveIndicator) {
            if (realtimeData.is_currently_studying) {
                liveIndicator.classList.remove('hidden');
            } else {
                liveIndicator.classList.add('hidden');
            }
        }
        
        // Add real-time status indicator
        let statusIndicator = document.getElementById('realtime-status');
        if (!statusIndicator) {
            statusIndicator = document.createElement('div');
            statusIndicator.id = 'realtime-status';
            statusIndicator.className = 'text-xs text-gray-500 mt-2 p-2 bg-gray-50 rounded';
            document.getElementById('analytics-content').appendChild(statusIndicator);
        }

        const isStudying = realtimeData.is_currently_studying;
        const activeModule = realtimeData.active_module;

        statusIndicator.innerHTML = `
            <div class="flex items-center justify-between">
                <span>
                    ${isStudying ? 
                        `<span class="text-green-600">📚 Currently studying${activeModule ? ': ' + activeModule : ''}</span>` : 
                        '<span class="text-gray-400">💤 No active session</span>'
                    }
                </span>
                <span class="text-xs text-gray-400">Updated: ${lastUpdated}</span>
            </div>
        `;
    }

    function startRealtimeUpdates() {
        // Update analytics every 30 seconds for real-time data
        if (analyticsUpdateInterval) {
            clearInterval(analyticsUpdateInterval);
        }
        
        analyticsUpdateInterval = setInterval(() => {
            loadAnalytics();
        }, 30000); // 30 seconds
        
        console.log('📊 Real-time analytics updates started (30s interval)');
    }

    function stopRealtimeUpdates() {
        if (analyticsUpdateInterval) {
            clearInterval(analyticsUpdateInterval);
            analyticsUpdateInterval = null;
            console.log('📊 Real-time analytics updates stopped');
        }
    }

    function updateMetricsDisplay(data) {
        const stats = data.overall_stats;
        
        document.getElementById('total-study-time').textContent = `${stats.total_study_time_hours} hrs`;
        document.getElementById('focus-efficiency').textContent = `${stats.focus_efficiency_percent}%`;
        document.getElementById('avg-session').textContent = `${stats.avg_session_minutes} min`;
        document.getElementById('modules-studied').textContent = stats.modules_studied;

        // Update trend indicators
        if (stats.focus_efficiency_percent > 75) {
            document.getElementById('efficiency-trend').textContent = '✨ Excellent focus!';
            document.getElementById('efficiency-trend').className = 'text-xs text-green-600';
        } else if (stats.focus_efficiency_percent > 50) {
            document.getElementById('efficiency-trend').textContent = '📈 Good progress';
            document.getElementById('efficiency-trend').className = 'text-xs text-blue-600';
        } else {
            document.getElementById('efficiency-trend').textContent = '💡 Room for improvement';
            document.getElementById('efficiency-trend').className = 'text-xs text-orange-600';
        }

        if (stats.avg_session_minutes > 20) {
            document.getElementById('session-quality').textContent = 'Great sessions!';
        } else if (stats.avg_session_minutes > 10) {
            document.getElementById('session-quality').textContent = 'Good duration';
        } else {
            document.getElementById('session-quality').textContent = 'Try longer sessions';
        }

        if (stats.total_sessions > 0) {
            const studyTimeChange = document.getElementById('study-time-change');
            studyTimeChange.textContent = `${stats.total_sessions} sessions completed`;
        }
    }

    function createFocusChart(trendsData) {
        const ctx = document.getElementById('focusChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (focusChart) {
            focusChart.destroy();
        }

        // Prepare data for chart
        const labels = trendsData.map(item => {
            const date = new Date(item.study_date);
            return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        });
        
        const focusTimes = trendsData.map(item => Math.round(item.daily_focus_time / 60)); // Convert to minutes
        const sessionCounts = trendsData.map(item => item.daily_sessions);

        focusChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Focus Time (minutes)',
                    data: focusTimes,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 8,
                        bottom: 8,
                        left: 8,
                        right: 8
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 8,
                        titleFont: {
                            size: 12
                        },
                        bodyFont: {
                            size: 11
                        },
                        borderColor: 'rgb(229, 231, 235)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: 4
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: 4
                        },
                        title: {
                            display: true,
                            text: 'Minutes',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: {
                                top: 4,
                                bottom: 4
                            }
                        }
                    }
                }
            }
        });
    }

    function updateModulePerformance(moduleData) {
        const container = document.getElementById('module-performance');
        if (!container) return;

        container.innerHTML = '';

        if (moduleData.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-3 text-sm text-gray-500 border border-gray-200 rounded-md p-3">
                    <i class="fas fa-book-open text-lg mb-2"></i>
                    <p>Start studying modules to see your performance data here!</p>
                </div>
            `;
            return;
        }

        // Show top 3 modules
        const topModules = moduleData.slice(0, 3);
        
        topModules.forEach((module, index) => {
            const hours = Math.round(module.total_time / 3600 * 10) / 10;
            const lastStudied = new Date(module.last_studied).toLocaleDateString();
            
            const moduleCard = document.createElement('div');
            moduleCard.className = 'bg-gray-50 border border-gray-200 p-3 rounded-md';
            moduleCard.innerHTML = `
                <div class="flex items-center justify-between mb-2 border-b border-gray-200 pb-2">
                    <h5 class="text-sm font-medium text-gray-900 truncate">${module.module_title}</h5>
                    <span class="text-xs px-2 py-0.5 rounded-md border ${index === 0 ? 'bg-green-100 text-green-800 border-green-200' : index === 1 ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-purple-100 text-purple-800 border-purple-200'}">
                        #${index + 1}
                    </span>
                </div>
                <div class="space-y-1 text-xs text-gray-600 mt-2">
                    <div>📚 ${hours}h studied</div>
                    <div>🎯 ${module.session_count} sessions</div>
                    <div>📅 Last: ${lastStudied}</div>
                </div>
            `;
            container.appendChild(moduleCard);
        });
    }

    function updateInsights(data) {
        const insights = data.insights;
        const overallStats = data.overall_stats;
        const insightsContainer = document.getElementById('learning-insights');
        
        if (!insightsContainer) return;

        let insightsList = [];

        // Focus efficiency insight
        if (overallStats.focus_efficiency_percent > 80) {
            insightsList.push({
                icon: 'fas fa-trophy text-green-500',
                text: 'Excellent focus levels! You maintain attention very well during study sessions.'
            });
        } else if (overallStats.focus_efficiency_percent > 60) {
            insightsList.push({
                icon: 'fas fa-target text-blue-500',
                text: 'Good focus patterns. Consider eliminating distractions to improve further.'
            });
        } else {
            insightsList.push({
                icon: 'fas fa-lightbulb text-orange-500',
                text: 'Try shorter study sessions with breaks to improve concentration.'
            });
        }

        // Session length insight
        if (overallStats.avg_session_minutes > 30) {
            insightsList.push({
                icon: 'fas fa-clock text-blue-500',
                text: 'You prefer longer study sessions. Make sure to take regular breaks.'
            });
        } else if (overallStats.avg_session_minutes < 10) {
            insightsList.push({
                icon: 'fas fa-fast-forward text-purple-500',
                text: 'Quick study sessions can be effective. Try gradually increasing duration.'
            });
        }

        // Study consistency
        if (data.focus_trends && data.focus_trends.length > 0) {
            const avgDailyTime = data.focus_trends.reduce((sum, day) => sum + day.daily_focus_time, 0) / data.focus_trends.length;
            if (avgDailyTime > 1800) { // 30 minutes
                insightsList.push({
                    icon: 'fas fa-calendar-check text-green-500',
                    text: 'Great daily study consistency! Keep up the regular learning habit.'
                });
            }
        }

        // Custom insight from backend
        if (insights.improvement_suggestion) {
            insightsList.push({
                icon: 'fas fa-brain text-purple-500',
                text: insights.improvement_suggestion
            });
        }

        // Update insights display
        insightsContainer.innerHTML = insightsList.map(insight => `
            <li class="flex items-start">
                <i class="${insight.icon} mt-1 mr-2"></i>
                <span class="text-sm text-gray-700">${insight.text}</span>
            </li>
        `).join('');

        // Update main insight text
        const insightTextElement = document.getElementById('insight-text');
        if (insightTextElement && insightsList.length > 0) {
            insightTextElement.textContent = insightsList[0].text;
        }
    }

    function refreshAnalytics() {
        loadAnalytics();
    }

    // Profile modal functions
function openProfileModal() {
    const modal = document.getElementById('profileModal');
    const modalContent = document.getElementById('profileModalContent');
    const dropdown = document.getElementById('profile-dropdown');
    
    if (!modal) {
        console.error('Profile modal not found');
        alert('Profile modal not found. Please refresh the page.');
        return;
    }
    
    // Show modal first
    modal.classList.remove('hidden');
    if (dropdown) {
        dropdown.classList.remove('show');
    }
    
    // Trigger animation
    setTimeout(() => {
        if (modalContent) {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }
    }, 10);
    
    // Then try to load current user data into the modal
    try {
        loadProfileData();
    } catch (error) {
        console.error('Error loading profile data:', error);
        // Don't prevent modal from showing if data loading fails
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    const modalContent = document.getElementById('profileModalContent');
    
    if (modal) {
        // Animate out
        if (modalContent) {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
        }
        
        // Hide after animation
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }
}

// Function to load and display current profile data
function loadProfileData() {
    try {
        const firstNameEl = document.getElementById('firstName');
        const lastNameEl = document.getElementById('lastName');
        
        if (!firstNameEl || !lastNameEl) {
            console.warn('Profile form fields not found');
            return;
        }
        
        const firstName = firstNameEl.value || '';
        const lastName = lastNameEl.value || '';
        
        if (!firstName || !lastName) {
            return;
        }
        
        // Update displayed name and initials
        const fullName = `${firstName} ${lastName}`;
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        
        const profileNameEl = document.getElementById('profile-name');
        const profileAvatarEl = document.getElementById('profile-avatar');
        
        if (profileNameEl) {
            profileNameEl.textContent = fullName;
        }
        if (profileAvatarEl) {
            profileAvatarEl.textContent = initials;
        }
    } catch (error) {
        console.error('Error in loadProfileData:', error);
    }
}

document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('profile_update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update UI with new values
            const fullName = `${formData.get('first_name')} ${formData.get('last_name')}`;
            
            // Update navbar profile toggle
            const profileToggleName = document.querySelector('#profile-toggle .font-medium');
            const profileToggleAvatar = document.querySelector('#profile-toggle .bg-primary');
            if (profileToggleName) profileToggleName.textContent = fullName;
            if (profileToggleAvatar) profileToggleAvatar.textContent = result.data.initials;
            
            // Update profile dropdown
            const dropdownName = document.querySelector('#profile-dropdown .font-medium');
            if (dropdownName) dropdownName.textContent = fullName;
            
            // Update modal header elements
            document.getElementById('profile-name').textContent = fullName;
            document.getElementById('profile-avatar').textContent = result.data.initials;
            
            // Update welcome section if it exists
            const welcomeHeader = document.querySelector('h2.text-xl');
            if (welcomeHeader && welcomeHeader.textContent.includes('Welcome')) {
                welcomeHeader.textContent = `Welcome, ${fullName}!`;
            }
            
            closeProfileModal();
            
            // Show success notification
            // You can replace this with a nicer notification UI
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg';
            notification.textContent = 'Profile updated successfully!';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
            
        } else {
            throw new Error(result.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while updating profile');
    }
});

// Update the profile link click handler
document.querySelector('#profile-dropdown a[href="#"]').addEventListener('click', function(e) {
    e.preventDefault();
    openProfileModal();
});

// Close modal when clicking outside
document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProfileModal();
    }
});

// Add this function near your other JavaScript code
function handleLogout(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}
    // Load analytics when page loads and start real-time updates
    document.addEventListener('DOMContentLoaded', function() {
        loadAnalytics();
        startRealtimeUpdates();
    });

    // Stop real-time updates when leaving the page
    window.addEventListener('beforeunload', function() {
        stopRealtimeUpdates();
    });

    // Also stop updates when page becomes hidden (mobile/tab switching)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopRealtimeUpdates();
        } else {
            loadAnalytics(); // Refresh data when coming back
            startRealtimeUpdates();
        }
    });

        // Sidebar toggle function
    function toggleSidebar() {
        if (isMobile()) {
            sidebarEl.classList.toggle('mobile-visible');
            backdropEl.classList.toggle('active');
        } else {
            isSidebarVisible = !isSidebarVisible;
            sidebarEl.classList.toggle('sidebar-collapsed', !isSidebarVisible);
            mainContentEl.classList.toggle('main-content-collapsed', !isSidebarVisible);
            localStorage.setItem('sidebarVisible', isSidebarVisible);
        }
    }

    // Event listeners
    toggleSidebarBtn.addEventListener('click', toggleSidebar);
    backdropEl.addEventListener('click', () => {
        sidebarEl.classList.remove('mobile-visible');
        backdropEl.classList.remove('active');
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            sidebarEl.classList.remove('mobile-visible');
            backdropEl.classList.remove('active');
            
            // Restore desktop state
            const savedState = localStorage.getItem('sidebarVisible');
            if (savedState !== null) {
                isSidebarVisible = savedState === 'true';
                sidebarEl.classList.toggle('sidebar-collapsed', !isSidebarVisible);
                mainContentEl.classList.toggle('main-content-collapsed', !isSidebarVisible);
            }
        } else {
            sidebarEl.classList.remove('sidebar-collapsed');
            mainContentEl.classList.remove('main-content-collapsed');
        }
    });
</script>
</body>
</html>