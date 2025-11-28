<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EyeLearn - AI-Enhanced E-Learning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../src/output.css">
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
            z-index: 50;
        }
        
        .profile-dropdown.show {
            display: block;
        }
        
        /* Responsive behavior */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 50;
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
                z-index: 40;
                display: none;
            }
            
            .backdrop.active {
                display: block;
            }
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
                    <span class="hidden md:inline-block font-medium text-gray-700"><?php echo htmlspecialchars($user_display_name); ?></span>
                </button>
                
                <div id="profile-dropdown" class="profile-dropdown">
                    <div class="p-4 border-b">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user_display_name); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_email); ?></p>
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
            <div class="container mx-auto px-2">
                <h1 class="text-2xl font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2">Assessment History</h1>
                
                <?php
                // Fetch all modules for dropdown filters (needed for both sections)
                $modules_query = "SELECT DISTINCT m.id, m.title FROM modules m ORDER BY m.title";
                $modules_result = $conn->query($modules_query);
                $all_modules = [];
                if ($modules_result) {
                    while ($module_row = $modules_result->fetch_assoc()) {
                        $all_modules[] = $module_row;
                    }
                }
                ?>
                
                <!-- Assessment History Table -->
                <div class="mt-3 bg-white border border-gray-200 shadow-sm rounded-md p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3 border-b border-gray-200 pb-2">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Module Completions</h2>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <!-- Module Filter -->
                            <div class="flex items-center gap-2">
                                <label for="completions-module-filter" class="text-sm font-medium text-gray-700">Module:</label>
                                <select id="completions-module-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="all">All Modules</option>
                                    <?php foreach ($all_modules as $module): ?>
                                        <option value="<?php echo htmlspecialchars($module['id']); ?>"><?php echo htmlspecialchars($module['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Items Per Page -->
                            <div class="flex items-center gap-2">
                                <label for="completions-items-per-page" class="text-sm font-medium text-gray-700">Show:</label>
                                <select id="completions-items-per-page" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div id="completions-pagination-info" class="mb-4 text-sm text-gray-600"></div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Module</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Module Completion Date</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Progress</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Current Score</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Percentage</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Total Focus Time</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Status</th>
                                </tr>
                            </thead>
                            <tbody id="completions-table-body">
                            <?php
                            // Check if module_completions table exists
                            $module_completions_exists = false;
                            $table_check = $conn->query("SHOW TABLES LIKE 'module_completions'");
                            if ($table_check && $table_check->num_rows > 0) {
                                $module_completions_exists = true;
                            }
                            
                            // Fetch module completions for the current user
                            if ($module_completions_exists) {
                                $assessment_query = "
                                    SELECT 
                                        mc.module_id,
                                        m.title AS module_name,
                                        mc.completion_date,
                                        mc.final_quiz_score AS current_score,
                                        COALESCE(SUM(eta.total_focus_time/3600), 0) AS total_focus_time,
                                        COALESCE((
                                            SELECT COUNT(fqq.id)
                                            FROM final_quiz_questions fqq
                                            INNER JOIN final_quizzes fq ON fqq.quiz_id = fq.id
                                            WHERE fq.module_id = mc.module_id
                                        ), 0) AS total_questions,
                                        ump.completed_sections,
                                        (SELECT COUNT(*) 
                                         FROM module_parts mp 
                                         JOIN module_sections ms ON mp.id = ms.module_part_id 
                                         WHERE mp.module_id = mc.module_id) AS total_sections
                                    FROM module_completions mc
                                    JOIN modules m ON mc.module_id = m.id
                                    LEFT JOIN eye_tracking_analytics eta 
                                        ON eta.user_id = mc.user_id 
                                        AND eta.module_id = mc.module_id
                                    LEFT JOIN user_module_progress ump
                                        ON ump.user_id = mc.user_id 
                                        AND ump.module_id = mc.module_id
                                    WHERE mc.user_id = ?
                                    GROUP BY 
                                        mc.module_id,
                                        m.title,
                                        mc.completion_date,
                                        mc.final_quiz_score,
                                        ump.completed_sections
                                    ORDER BY mc.completion_date DESC;
                                ";
                                
                                $stmt = $conn->prepare($assessment_query);
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                            } else {
                                // Table doesn't exist, set result to null
                                $result = null;
                            }
                            
                            $completions_data = [];
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // final_quiz_score should store raw score (number of correct answers), not percentage
                                    // Handle legacy data that might be stored as percentages (0-100)
                                    $raw_score = $row['current_score'] ?? 0;
                                    $total_questions = $row['total_questions'] ?? 0;
                                    
                                    // Convert legacy percentage data to raw score if needed
                                    // Detection: if score is <= 100 and treating it as raw score would give > 100%, it's likely a percentage
                                    if ($total_questions > 0 && $raw_score <= 100 && $raw_score > $total_questions) {
                                        // This is likely legacy percentage data (e.g., 70 stored instead of 7 out of 10)
                                        // Convert percentage to raw score
                                        $current_score = round(($raw_score / 100) * $total_questions);
                                    } else {
                                        // Score is already a raw score (number of correct answers)
                                        // This is the correct format going forward
                                        $current_score = $raw_score;
                                    }
                                    
                                    // Calculate percentage from raw score
                                    $percentage = $total_questions > 0 
                                        ? round(($current_score / $total_questions) * 100, 2) 
                                        : 0;
                                    
                                    $status = $total_questions > 0 && $percentage >= 70 ? 'Passed' : 'Failed';
                                    $status_color = $total_questions > 0 && $percentage >= 70 ? 'green' : 'red';

                                    // Calculate module progress from completed sections
                                    $completed_sections = json_decode($row['completed_sections'] ?? '[]', true);
                                    $completed_count = is_array($completed_sections) ? count($completed_sections) : 0;
                                    $total_sections = (int)($row['total_sections'] ?? 0);
                                    $module_progress = $total_sections > 0 
                                        ? round(($completed_count / $total_sections) * 100, 0) 
                                        : 0;
                                    $progress_display = $total_sections > 0 
                                        ? number_format($module_progress, 0) . '%' 
                                        : 'N/A';

                                    // Format focus time (if null or 0, show "N/A")
                                    $focus_time = isset($row['total_focus_time']) && $row['total_focus_time'] > 0
                                        ? number_format($row['total_focus_time'], 2) . ' hrs'
                                        : 'N/A';

                                    // Format score display - show total score (raw score out of total questions)
                                    $score_display = $total_questions > 0 
                                        ? htmlspecialchars($current_score) . '/' . htmlspecialchars($total_questions)
                                        : htmlspecialchars($current_score);

                                    // Format percentage display
                                    $percentage_display = $total_questions > 0 
                                        ? number_format($percentage, 2) . '%' 
                                        : 'N/A';

                                    // Store data for JavaScript
                                    $completions_data[] = [
                                        'module_id' => $row['module_id'],
                                        'module_name' => $row['module_name'],
                                        'completion_date' => $row['completion_date'],
                                        'progress_display' => $progress_display,
                                        'progress_value' => $module_progress,
                                        'score_display' => $score_display,
                                        'percentage_display' => $percentage_display,
                                        'focus_time' => $focus_time,
                                        'status' => $status,
                                        'status_color' => $status_color
                                    ];
                                }
                            }
                            if (isset($stmt)) {
                                $stmt->close();
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div id="completions-pagination" class="mt-4 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button id="completions-prev" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button id="completions-next" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Quiz History Section -->
                <div class="mt-3 bg-white border border-gray-200 shadow-sm rounded-md p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3 border-b border-gray-200 pb-2">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Quiz History</h2>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <!-- Module Filter -->
                            <div class="flex items-center gap-2">
                                <label for="quiz-module-filter" class="text-sm font-medium text-gray-700">Module:</label>
                                <select id="quiz-module-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="all">All Modules</option>
                                    <?php foreach ($all_modules as $module): ?>
                                        <option value="<?php echo htmlspecialchars($module['id']); ?>"><?php echo htmlspecialchars($module['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Items Per Page -->
                            <div class="flex items-center gap-2">
                                <label for="quiz-items-per-page" class="text-sm font-medium text-gray-700">Show:</label>
                                <select id="quiz-items-per-page" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div id="quiz-pagination-info" class="mb-4 text-sm text-gray-600"></div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Module</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Quiz Completion Date</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Score</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Percentage</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Attempts</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Status</th>
                                </tr>
                            </thead>
                            <tbody id="quiz-table-body">
                            <?php
                            // Check if retake_results table exists
                            $retake_table_exists = false;
                            $table_check = $conn->query("SHOW TABLES LIKE 'retake_results'");
                            if ($table_check && $table_check->num_rows > 0) {
                                $retake_table_exists = true;
                            }
                            
                            // Fetch all quiz attempts (original + retakes) for the current user
                            if ($retake_table_exists) {
                                $quiz_history_query = "
                                    SELECT 
                                        m.title AS module_name,
                                        qr.completion_date,
                                        qr.score,
                                        qr.module_id,
                                        qr.quiz_id,
                                        'original' AS attempt_type,
                                        COALESCE((
                                            SELECT COUNT(fqq.id)
                                            FROM final_quiz_questions fqq
                                            WHERE fqq.quiz_id = qr.quiz_id
                                        ), 0) AS total_questions,
                                        ump.completed_sections,
                                        (SELECT COUNT(*) 
                                         FROM module_parts mp 
                                         JOIN module_sections ms ON mp.id = ms.module_part_id 
                                         WHERE mp.module_id = qr.module_id) AS total_sections
                                    FROM quiz_results qr
                                    JOIN modules m ON qr.module_id = m.id
                                    LEFT JOIN user_module_progress ump
                                        ON ump.user_id = qr.user_id 
                                        AND ump.module_id = qr.module_id
                                    WHERE qr.user_id = ?
                                    
                                    UNION ALL
                                    
                                    SELECT 
                                        m.title AS module_name,
                                        rr.completion_date,
                                        rr.score,
                                        rr.module_id,
                                        rr.quiz_id,
                                        'retake' AS attempt_type,
                                        COALESCE((
                                            SELECT COUNT(fqq.id)
                                            FROM final_quiz_questions fqq
                                            WHERE fqq.quiz_id = rr.quiz_id
                                        ), 0) AS total_questions,
                                        ump.completed_sections,
                                        (SELECT COUNT(*) 
                                         FROM module_parts mp 
                                         JOIN module_sections ms ON mp.id = ms.module_part_id 
                                         WHERE mp.module_id = rr.module_id) AS total_sections
                                    FROM retake_results rr
                                    JOIN modules m ON rr.module_id = m.id
                                    LEFT JOIN user_module_progress ump
                                        ON ump.user_id = rr.user_id 
                                        AND ump.module_id = rr.module_id
                                    WHERE rr.user_id = ?
                                    
                                    ORDER BY module_id, quiz_id, completion_date ASC;
                                ";
                                
                                $stmt = $conn->prepare($quiz_history_query);
                                $stmt->bind_param('ii', $user_id, $user_id);
                            } else {
                                // Only fetch original quiz results if retake_results table doesn't exist
                                $quiz_history_query = "
                                    SELECT 
                                        m.title AS module_name,
                                        qr.completion_date,
                                        qr.score,
                                        qr.module_id,
                                        qr.quiz_id,
                                        'original' AS attempt_type,
                                        COALESCE((
                                            SELECT COUNT(fqq.id)
                                            FROM final_quiz_questions fqq
                                            WHERE fqq.quiz_id = qr.quiz_id
                                        ), 0) AS total_questions,
                                        ump.completed_sections,
                                        (SELECT COUNT(*) 
                                         FROM module_parts mp 
                                         JOIN module_sections ms ON mp.id = ms.module_part_id 
                                         WHERE mp.module_id = qr.module_id) AS total_sections
                                    FROM quiz_results qr
                                    JOIN modules m ON qr.module_id = m.id
                                    LEFT JOIN user_module_progress ump
                                        ON ump.user_id = qr.user_id 
                                        AND ump.module_id = qr.module_id
                                    WHERE qr.user_id = ?
                                    ORDER BY module_id, quiz_id, completion_date ASC;
                                ";
                                
                                $stmt = $conn->prepare($quiz_history_query);
                                $stmt->bind_param('i', $user_id);
                            }
                            
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            $quiz_data = [];
                            if ($result->num_rows > 0) {
                                $attempt_counts = array(); // Track attempt numbers per module/quiz
                                
                                while ($row = $result->fetch_assoc()) {
                                    $module_id = $row['module_id'];
                                    $quiz_id = $row['quiz_id'];
                                    $key = $module_id . '_' . $quiz_id;
                                    
                                    // Initialize or increment attempt counter
                                    if (!isset($attempt_counts[$key])) {
                                        $attempt_counts[$key] = 0;
                                    }
                                    $attempt_counts[$key]++;
                                    $attempt_number = $attempt_counts[$key];
                                    
                                    // Convert to ordinal (1st, 2nd, 3rd, etc.)
                                    $suffix = 'th';
                                    if ($attempt_number % 100 >= 11 && $attempt_number % 100 <= 13) {
                                        $suffix = 'th';
                                    } else {
                                        switch ($attempt_number % 10) {
                                            case 1:
                                                $suffix = 'st';
                                                break;
                                            case 2:
                                                $suffix = 'nd';
                                                break;
                                            case 3:
                                                $suffix = 'rd';
                                                break;
                                        }
                                    }
                                    $attempt_label = $attempt_number . $suffix;
                                    
                                    $score = $row['score'];
                                    $total_questions = $row['total_questions'] ?? 0;
                                    $status = $total_questions > 0 && ($score / $total_questions * 100) >= 70 ? 'Passed' : 'Failed';
                                    $status_color = $total_questions > 0 && ($score / $total_questions * 100) >= 70 ? 'green' : 'red';

                                    // Format score display
                                    $score_display = $total_questions > 0 
                                        ? htmlspecialchars($score) . '/' . htmlspecialchars($total_questions)
                                        : htmlspecialchars($score);

                                    // Calculate percentage
                                    $percentage = $total_questions > 0 
                                        ? round(($score / $total_questions) * 100, 2) 
                                        : 0;
                                    $percentage_display = $total_questions > 0 
                                        ? number_format($percentage, 2) . '%' 
                                        : 'N/A';

                                    // Store data for JavaScript
                                    $quiz_data[] = [
                                        'module_id' => $row['module_id'],
                                        'module_name' => $row['module_name'],
                                        'completion_date' => $row['completion_date'],
                                        'score_display' => $score_display,
                                        'percentage_display' => $percentage_display,
                                        'attempt_label' => $attempt_label,
                                        'status' => $status,
                                        'status_color' => $status_color
                                    ];
                                }
                            }
                            $stmt->close();
                            ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div id="quiz-pagination" class="mt-4 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button id="quiz-prev" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button id="quiz-next" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Checkpoint Quiz History Section -->
                <div class="mt-3 bg-white border border-gray-200 shadow-sm rounded-md p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3 border-b border-gray-200 pb-2">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Checkpoint Quiz History</h2>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <!-- Module Filter -->
                            <div class="flex items-center gap-2">
                                <label for="checkpoint-module-filter" class="text-sm font-medium text-gray-700">Module:</label>
                                <select id="checkpoint-module-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="all">All Modules</option>
                                    <?php foreach ($all_modules as $module): ?>
                                        <option value="<?php echo htmlspecialchars($module['id']); ?>"><?php echo htmlspecialchars($module['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Items Per Page -->
                            <div class="flex items-center gap-2">
                                <label for="checkpoint-items-per-page" class="text-sm font-medium text-gray-700">Show:</label>
                                <select id="checkpoint-items-per-page" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div id="checkpoint-pagination-info" class="mb-4 text-sm text-gray-600"></div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Module</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Checkpoint Quiz</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Completion Date</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Score</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Percentage</th>
                                    <th class="p-2 py-2 text-sm font-semibold text-gray-900">Status</th>
                                </tr>
                            </thead>
                            <tbody id="checkpoint-table-body">
                            <?php
                            // Check if checkpoint_quiz_results table exists
                            $checkpoint_table_exists = false;
                            $table_check = $conn->query("SHOW TABLES LIKE 'checkpoint_quiz_results'");
                            if ($table_check && $table_check->num_rows > 0) {
                                $checkpoint_table_exists = true;
                            }
                            
                            // Fetch checkpoint quiz results for the current user
                            $checkpoint_data = [];
                            if ($checkpoint_table_exists) {
                                $checkpoint_history_query = "
                                    SELECT 
                                        m.title AS module_name,
                                        cq.quiz_title AS checkpoint_quiz_title,
                                        cqr.completion_date,
                                        cqr.score,
                                        cqr.total_questions,
                                        cqr.percentage,
                                        cqr.module_id,
                                        cqr.checkpoint_quiz_id
                                    FROM checkpoint_quiz_results cqr
                                    JOIN modules m ON cqr.module_id = m.id
                                    JOIN checkpoint_quizzes cq ON cqr.checkpoint_quiz_id = cq.id
                                    WHERE cqr.user_id = ?
                                    ORDER BY cqr.completion_date DESC;
                                ";
                                
                                $stmt = $conn->prepare($checkpoint_history_query);
                                if ($stmt) {
                                    $stmt->bind_param('i', $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $score = $row['score'];
                                            $total_questions = $row['total_questions'] ?? 0;
                                            $percentage = floatval($row['percentage']);
                                            $status = $percentage >= 70 ? 'Passed' : 'Failed';
                                            $status_color = $percentage >= 70 ? 'green' : 'red';

                                            // Format score display
                                            $score_display = $total_questions > 0 
                                                ? htmlspecialchars($score) . '/' . htmlspecialchars($total_questions)
                                                : htmlspecialchars($score);

                                            // Format percentage display
                                            $percentage_display = number_format($percentage, 2) . '%';

                                            // Store data for JavaScript
                                            $checkpoint_data[] = [
                                                'module_id' => $row['module_id'],
                                                'module_name' => $row['module_name'],
                                                'checkpoint_quiz_title' => $row['checkpoint_quiz_title'],
                                                'completion_date' => $row['completion_date'],
                                                'score_display' => $score_display,
                                                'percentage_display' => $percentage_display,
                                                'percentage_value' => $percentage,
                                                'status' => $status,
                                                'status_color' => $status_color
                                            ];
                                        }
                                    }
                                    $stmt->close();
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div id="checkpoint-pagination" class="mt-4 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button id="checkpoint-prev" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button id="checkpoint-next" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
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

        // State management
        let isSidebarVisible = true;
        const isMobile = () => window.innerWidth < 768;
                
        // Navigation links
        const dashboardLink = document.getElementById('dashboard-link');
        const modulesLink = document.getElementById('modules-link');
        const assessmentsLink = document.getElementById('assessments-link');
        
        // Profile dropdown elements
        const profileToggle = document.getElementById('profile-toggle');
        const profileDropdown = document.getElementById('profile-dropdown');

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
        
        // Toggle profile dropdown on click
        profileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Add handler for logout link 
        document.querySelector('#profile-dropdown a[href*="logout"]').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = this.href;
        });
        
        // Close dropdown when clicking elsewhere on the page
        document.addEventListener('click', function(e) {
            if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
        
        // Function to handle active page styling
        function setActivePage() {
            const currentPage = window.location.pathname.split('/').pop();
            
            // Reset all links
            [dashboardItem, modulesItem, assessmentsItem].forEach(item => {
                item.classList.remove('active');
            });
            
            // Highlight active link based on current page
            if (currentPage === 'Sdashboard.php' || currentPage === '' || currentPage === '/') {
                dashboardItem.classList.add('active');
            } else if (currentPage === 'Smodule.php') {
                modulesItem.classList.add('active'); 
            } else if (currentPage === 'Sassessment.php') {
                assessmentsItem.classList.add('active');
            } else {
                dashboardItem.classList.add('active');
            }
        }

        // Set active page on load
        setActivePage();

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
                    const profileNameEl = document.getElementById('profile-name');
                    const profileAvatarEl = document.getElementById('profile-avatar');
                    if (profileNameEl) profileNameEl.textContent = fullName;
                    if (profileAvatarEl) profileAvatarEl.textContent = result.data.initials;
                    
                    closeProfileModal();
                    
                    // Show success notification
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

        // Logout handler
        function handleLogout(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        // Table Filtering and Pagination
        const completionsData = <?php echo json_encode($completions_data ?? []); ?>;
        const quizData = <?php echo json_encode($quiz_data ?? []); ?>;
        const checkpointData = <?php echo json_encode($checkpoint_data ?? []); ?>;

        // Completions Table Manager
        class TableManager {
            constructor(data, tableBodyId, moduleFilterId, itemsPerPageId, paginationInfoId, prevBtnId, nextBtnId) {
                this.data = data;
                this.filteredData = [...data];
                this.currentPage = 1;
                this.itemsPerPage = 10;
                this.tableBody = document.getElementById(tableBodyId);
                this.moduleFilter = document.getElementById(moduleFilterId);
                this.itemsPerPageSelect = document.getElementById(itemsPerPageId);
                this.paginationInfo = document.getElementById(paginationInfoId);
                this.prevBtn = document.getElementById(prevBtnId);
                this.nextBtn = document.getElementById(nextBtnId);
                
                this.init();
            }
            
            init() {
                this.moduleFilter.addEventListener('change', () => this.filter());
                this.itemsPerPageSelect.addEventListener('change', () => {
                    this.itemsPerPage = this.itemsPerPageSelect.value === 'all' ? Infinity : parseInt(this.itemsPerPageSelect.value);
                    this.currentPage = 1;
                    this.render();
                });
                this.prevBtn.addEventListener('click', () => this.prevPage());
                this.nextBtn.addEventListener('click', () => this.nextPage());
                
                this.render();
            }
            
            filter() {
                const selectedModule = this.moduleFilter.value;
                if (selectedModule === 'all') {
                    this.filteredData = [...this.data];
                } else {
                    this.filteredData = this.data.filter(item => item.module_id == selectedModule);
                }
                this.currentPage = 1;
                this.render();
            }
            
            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.render();
                }
            }
            
            nextPage() {
                const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.render();
                }
            }
            
            render() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                const end = start + this.itemsPerPage;
                const pageData = this.filteredData.slice(start, end);
                const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
                
                // Render table rows
                if (pageData.length === 0) {
                    const cols = this.tableBody.closest('table').querySelectorAll('thead th').length;
                    this.tableBody.innerHTML = `<tr><td colspan="${cols}" class="p-3 text-center text-gray-500">No data available</td></tr>`;
                } else {
                    this.tableBody.innerHTML = pageData.map(item => this.renderRow(item)).join('');
                }
                
                // Update pagination info
                const totalItems = this.filteredData.length;
                const startItem = totalItems === 0 ? 0 : start + 1;
                const endItem = Math.min(end, totalItems);
                this.paginationInfo.textContent = totalItems > 0 
                    ? `Showing ${startItem} to ${endItem} of ${totalItems} entries`
                    : 'No entries to display';
                
                // Update pagination buttons
                this.prevBtn.disabled = this.currentPage === 1;
                this.nextBtn.disabled = this.currentPage >= totalPages || this.itemsPerPage === Infinity;
            }
            
            renderRow(item) {
                // This will be overridden by subclasses
                return '';
            }
        }

        // Completions Table Manager
        class CompletionsTableManager extends TableManager {
            renderRow(item) {
                const statusClass = item.status_color === 'green' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800';
                const progressValue = item.progress_value || 0;
                const progressBarColor = progressValue >= 100 ? 'bg-green-500' : progressValue >= 50 ? 'bg-blue-500' : 'bg-yellow-500';
                return `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="p-2 py-2 font-medium text-sm">${this.escapeHtml(item.module_name)}</td>
                        <td class="p-2 py-2 text-sm">${this.formatDate(item.completion_date)}</td>
                        <td class="p-2 py-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 min-w-[60px]">
                                    <div class="${progressBarColor} h-2 rounded-full transition-all" style="width: ${Math.min(progressValue, 100)}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 min-w-[40px]">${this.escapeHtml(item.progress_display)}</span>
                            </div>
                        </td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.score_display)}</td>
                        <td class="p-2 py-2 font-medium text-sm">${this.escapeHtml(item.percentage_display)}</td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.focus_time)}</td>
                        <td class="p-2 py-2">
                            <span class="${statusClass} px-3 py-1 rounded-full text-sm font-medium">${this.escapeHtml(item.status)}</span>
                        </td>
                    </tr>
                `;
            }
            
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Quiz Table Manager
        class QuizTableManager extends TableManager {
            renderRow(item) {
                const statusClass = item.status_color === 'green' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800';
                
                // Extract percentage value from percentage_display (e.g., "85.50%" -> 85.50)
                const percentageValue = parseFloat(item.percentage_display.replace('%', '')) || 0;
                const progressBarColor = percentageValue >= 70 ? 'bg-green-500' : percentageValue >= 50 ? 'bg-yellow-500' : 'bg-red-500';
                
                return `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="p-2 py-2 font-medium text-sm">${this.escapeHtml(item.module_name)}</td>
                        <td class="p-2 py-2 text-sm">${this.formatDateTime(item.completion_date)}</td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.score_display)}</td>
                        <td class="p-2 py-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 min-w-[60px]">
                                    <div class="${progressBarColor} h-2 rounded-full transition-all" style="width: ${Math.min(percentageValue, 100)}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 min-w-[50px]">${this.escapeHtml(item.percentage_display)}</span>
                            </div>
                        </td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.attempt_label)}</td>
                        <td class="p-2 py-2">
                            <span class="${statusClass} px-3 py-1 rounded-full text-sm font-medium">${this.escapeHtml(item.status)}</span>
                        </td>
                    </tr>
                `;
            }
            
            formatDateTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + 
                       ', ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: true });
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Checkpoint Quiz Table Manager
        class CheckpointTableManager extends TableManager {
            renderRow(item) {
                const statusClass = item.status_color === 'green' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800';
                
                const percentageValue = item.percentage_value || parseFloat(item.percentage_display.replace('%', '')) || 0;
                const progressBarColor = percentageValue >= 70 ? 'bg-green-500' : percentageValue >= 50 ? 'bg-yellow-500' : 'bg-red-500';
                
                return `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="p-2 py-2 font-medium text-sm">${this.escapeHtml(item.module_name)}</td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.checkpoint_quiz_title)}</td>
                        <td class="p-2 py-2 text-sm">${this.formatDateTime(item.completion_date)}</td>
                        <td class="p-2 py-2 text-sm">${this.escapeHtml(item.score_display)}</td>
                        <td class="p-2 py-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 min-w-[60px]">
                                    <div class="${progressBarColor} h-2 rounded-full transition-all" style="width: ${Math.min(percentageValue, 100)}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 min-w-[50px]">${this.escapeHtml(item.percentage_display)}</span>
                            </div>
                        </td>
                        <td class="p-2 py-2">
                            <span class="${statusClass} px-3 py-1 rounded-full text-sm font-medium">${this.escapeHtml(item.status)}</span>
                        </td>
                    </tr>
                `;
            }
            
            formatDateTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + 
                       ', ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: true });
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Initialize table managers when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                new CompletionsTableManager(
                    completionsData,
                    'completions-table-body',
                    'completions-module-filter',
                    'completions-items-per-page',
                    'completions-pagination-info',
                    'completions-prev',
                    'completions-next'
                );
                
                new QuizTableManager(
                    quizData,
                    'quiz-table-body',
                    'quiz-module-filter',
                    'quiz-items-per-page',
                    'quiz-pagination-info',
                    'quiz-prev',
                    'quiz-next'
                );
                
                new CheckpointTableManager(
                    checkpointData,
                    'checkpoint-table-body',
                    'checkpoint-module-filter',
                    'checkpoint-items-per-page',
                    'checkpoint-pagination-info',
                    'checkpoint-prev',
                    'checkpoint-next'
                );
            });
        } else {
            new CompletionsTableManager(
                completionsData,
                'completions-table-body',
                'completions-module-filter',
                'completions-items-per-page',
                'completions-pagination-info',
                'completions-prev',
                'completions-next'
            );
            
            new QuizTableManager(
                quizData,
                'quiz-table-body',
                'quiz-module-filter',
                'quiz-items-per-page',
                'quiz-pagination-info',
                'quiz-prev',
                'quiz-next'
            );
            
            new CheckpointTableManager(
                checkpointData,
                'checkpoint-table-body',
                'checkpoint-module-filter',
                'checkpoint-items-per-page',
                'checkpoint-pagination-info',
                'checkpoint-prev',
                'checkpoint-next'
            );
        }
    </script>
</body>
</html>