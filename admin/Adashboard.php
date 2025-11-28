<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();

// Fetch dashboard data
try {
    // 1. Get total students count
    $studentCountQuery = "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'";
    $result = $conn->query($studentCountQuery);
    $totalStudents = $result->fetch_assoc()['total_students'];
    
    // 2. Get total active modules count
    $moduleCountQuery = "SELECT COUNT(*) as total_modules FROM modules WHERE status = 'published'";
    $result = $conn->query($moduleCountQuery);
    $totalModules = $result->fetch_assoc()['total_modules'];
    
    // 3. Calculate completion rate
    $progressQuery = "SELECT 
        COUNT(DISTINCT up.user_id) as users_with_progress,
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students
        FROM user_progress up 
        WHERE up.completion_percentage > 0";
    $result = $conn->query($progressQuery);
    $progressData = $result->fetch_assoc();
    $completionRate = $progressData['total_students'] > 0 ? 
        ($progressData['users_with_progress'] / $progressData['total_students']) * 100 : 0;
    
    // 4. Calculate average score
    $avgScoreQuery = "SELECT AVG(completion_percentage) as avg_score FROM user_progress WHERE completion_percentage > 0";
    $result = $conn->query($avgScoreQuery);
    $avgScore = $result->fetch_assoc();
    $averageScore = $avgScore['avg_score'] ?? 0;
    
    // 5. Get growth stats for this month vs last month
    $growthQuery = "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_students_30d,
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_students_prev_30d";
    $result = $conn->query($growthQuery);
    $growthData = $result->fetch_assoc();
    
    $studentGrowth = 0;
    if ($growthData['new_students_prev_30d'] > 0) {
        $studentGrowth = (($growthData['new_students_30d'] - $growthData['new_students_prev_30d']) / $growthData['new_students_prev_30d']) * 100;
    } elseif ($growthData['new_students_30d'] > 0) {
        $studentGrowth = 100;
    }
    
} catch (Exception $e) {
    // Fallback to default values if there's an error
    $totalStudents = 0;
    $totalModules = 0;
    $completionRate = 0;
    $averageScore = 0;
    $studentGrowth = 0;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EyeLearn - AI-Enhanced E-Learning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/src/output.css">
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
            width: 64px;
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
        

            <!-- Profile dropdown -->
            <div class="profile-container relative">
                <button id="profile-toggle" class="flex items-center space-x-2 focus:outline-none">
                    <div class="bg-primary rounded-full w-8 h-8 flex items-center justify-center text-white font-medium text-sm">
                        A
                    </div>
                    <span class="hidden md:inline-block font-medium text-gray-700">Admin</span>
                </button>
                
                <!-- Dropdown menu -->
                <div id="profile-dropdown" class="profile-dropdown">
                    <div class="p-4 border-b">
                        <p class="font-medium text-gray-800">Admin</p>
                        <p class="text-sm text-gray-500">admin@admin.eyelearn</p>
                    </div>
                    <div class="p-2">
                        <a href="../logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded">Logout</a>
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
                        <a href="Adashboard.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="dashboard-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Modules -->
                    <li class="nav-item relative" id="modules-item">
                        <div class="nav-indicator"></div>
                        <a href="Amodule.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="modules-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Modules</span>
                        </a>
                    </li>
                    
                    <!-- Student Performance Overview -->
                    <li class="nav-item relative" id="assessments-item">
                        <div class="nav-indicator"></div>
                        <a href="Amanagement.php" class="h-14 flex items-center px-5 text-gray-700 hover:bg-gray-50 transition duration-150" id="assessments-link">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span class="font-medium ml-4 nav-text">Student Performance Overview</span>
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
               
                
        <!-- Main Content Area -->
        <main id="main-content" class="main-content flex-1 p-3 transition-all duration-300">
            <!-- Page-specific content will go here -->
          <!-- Dashboard Main Content -->
<div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3">
    <h2 class="text-lg font-bold mb-2 text-gray-900 border-b border-gray-200 pb-2">Dashboard Overview</h2>
    <p class="text-sm text-gray-600 mb-3">Welcome to your E-Learning analytics dashboard. Review student performance, engagement metrics, and learning patterns.</p>
    
    <!-- Dashboard Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3 border-t border-gray-200 pt-3">
        <!-- Total Students Card -->
        <div class="bg-blue-50 rounded-md p-3 border border-blue-200">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Students</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($totalStudents); ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center">
                <?php if ($studentGrowth >= 0): ?>
                <span class="text-green-500 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                    <?php echo number_format(abs($studentGrowth), 1); ?>%
                </span>
                <?php else: ?>
                <span class="text-red-500 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <?php echo number_format(abs($studentGrowth), 1); ?>%
                </span>
                <?php endif; ?>
                <span class="text-gray-500 text-sm ml-2">vs last month</span>
            </div>
        </div>

        <!-- Course Completion Rate -->
        <div class="bg-green-50 rounded-md p-3 border border-green-200">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Completion Rate</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($completionRate, 1); ?>%</h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center">
                <span class="text-blue-500 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                <span class="text-gray-500 text-sm ml-2">of enrolled students</span>
            </div>
        </div>

        <!-- Average Score -->
        <div class="bg-purple-50 rounded-md p-3 border border-purple-200">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Average Score</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($averageScore, 1); ?>%</h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center">
                <span class="text-blue-500 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                <span class="text-gray-500 text-sm ml-2">across all modules</span>
            </div>
        </div>

        <!-- Active Modules -->
        <div class="bg-amber-50 rounded-md p-3 border border-amber-200">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Active Modules</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($totalModules); ?></h3>
                </div>
                <div class="bg-amber-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center">
                <span class="text-blue-500 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                <span class="text-gray-500 text-sm ml-2">published modules</span>
            </div>
        </div>
    </div>

    <!-- Gender Distribution & Gaze Tracking -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <!-- Gender Distribution Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Student Gender Distribution</h3>
            <div class="flex items-center justify-center h-64">
                <canvas id="genderChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600" id="male-percentage">Male (0.0%)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-pink-500 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600" id="female-percentage">Female (0.0%)</span>
                </div>
            </div>
        </div>

        <!-- Gaze Tracking Analysis -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Gaze Tracking Analysis by Gender</h3>
            <div class="flex items-center justify-center h-64">
                <canvas id="gazeTrackingChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Male Focus Time</p>
                    <p class="text-xl font-bold text-primary" id="male-focus-time">Loading...</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Female Focus Time</p>
                    <p class="text-xl font-bold text-pink-500" id="female-focus-time">Loading...</p>
                </div>
            </div>
        </div>
     
        <!-- Total Time Completed Module -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Total Time Completed Module by Gender</h3>
            <div class="flex items-center justify-center h-64">
                <canvas id="timeToCompleteChart"></canvas>
            </div>
            <!-- Total Time Summary -->
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Male Average Time</p>
                    <p class="text-xl font-bold text-blue-500" id="male-total-time">Loading...</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Female Average Time</p>
                    <p class="text-xl font-bold text-pink-500" id="female-total-time">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Average Final Quiz Score by Gender -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Average Final Quiz Score by Gender</h3>
                    <p class="text-sm text-gray-500">Average percentage score by gender for selected module</p>
                </div>
                <select id="avg-score-module-filter" class="mt-3 sm:mt-0 w-full sm:w-40 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white cursor-pointer shadow-sm hover:border-gray-400">
                    <option value="all">All Modules</option>
                    <?php
                    // Get available modules for filter
                    $moduleQueryForAvgScore = "SELECT id, title FROM modules WHERE status = 'published' ORDER BY title";
                    $moduleResultForAvgScore = $conn->query($moduleQueryForAvgScore);
                    if ($moduleResultForAvgScore) {
                        while ($module = $moduleResultForAvgScore->fetch_assoc()) {
                            echo "<option value='{$module['id']}'>{$module['title']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="flex items-center justify-center h-64">
                <canvas id="avgScoreByGenderChart"></canvas>
            </div>
        </div>

        <!-- Checkpoint Quiz Results by Gender -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Checkpoint Quiz Results by Gender</h3>
            <p class="text-sm text-gray-500 mb-4">Bar graph showing how many male vs female students answered each checkpoint question correctly</p>
            <div class="flex items-center justify-center" style="height: 360px;">
                <canvas id="checkpointQuizResultsChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Male Students</p>
                    <p class="text-xs text-gray-500 mt-1">Correct: <span id="male-checkpoint-correct" class="font-semibold text-green-600">0</span></p>
                    <p class="text-xs text-gray-500">Wrong: <span id="male-checkpoint-wrong" class="font-semibold text-red-600">0</span></p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm font-medium text-gray-700">Female Students</p>
                    <p class="text-xs text-gray-500 mt-1">Correct: <span id="female-checkpoint-correct" class="font-semibold text-green-600">0</span></p>
                    <p class="text-xs text-gray-500">Wrong: <span id="female-checkpoint-wrong" class="font-semibold text-red-600">0</span></p>
                </div>
            </div>
        </div>

        <!-- Focus Time vs Quiz Score Correlation -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 col-span-2 overflow-hidden">
            <div class="p-6 pb-5 bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 border-b border-gray-100">
                <div class="flex flex-col lg:flex-row gap-4 lg:gap-8 lg:items-center">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-500">Focus vs Performance</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1">Focus Time & Quiz Score Correlation</h3>
                        <p class="text-sm text-gray-600 mt-1">See how sustained focus sessions influence quiz scores for every module.</p>
                        <div class="mt-4 grid grid-cols-2 gap-3 max-w-lg">
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                Male distribution
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                Female distribution
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <span class="w-2.5 h-0.5 bg-blue-500"></span>
                                Male trendline
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <span class="w-2.5 h-0.5 bg-pink-500"></span>
                                Female trendline
                            </div>
                        </div>
                    </div>
                    <div class="w-full lg:w-auto">
                        <label for="correlation-module-filter" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Filter by module</label>
                        <select id="correlation-module-filter" class="mt-2 w-full lg:w-52 border border-gray-300 rounded-xl py-2.5 px-3 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value="all">All Modules</option>
                            <?php
                            // Get available modules for filter
                            $moduleQueryForFilter = "SELECT id, title FROM modules WHERE status = 'published' ORDER BY title";
                            $moduleResultForFilter = $conn->query($moduleQueryForFilter);
                            if ($moduleResultForFilter) {
                                while ($module = $moduleResultForFilter->fetch_assoc()) {
                                    echo "<option value='{$module['id']}'>{$module['title']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="relative h-[380px] bg-gray-50 border border-gray-100 rounded-xl">
                            <canvas id="focusScoreCorrelationChart" class="p-2"></canvas>
                            <div id="correlation-no-data" class="hidden absolute inset-0 items-center justify-center bg-white/70 rounded-xl">
                                <div class="text-center">
                                    <p class="text-gray-700 font-semibold">No data available</p>
                                    <p class="text-sm text-gray-500">Try selecting a different module or date range.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="correlation-stats" class="space-y-4">
                        <!-- Overall Correlation -->
                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Overall</p>
                            <div class="flex items-center justify-between mt-2">
                                <span id="overall-correlation-value" class="text-3xl font-bold text-gray-900">--</span>
                                <span id="overall-correlation-type" class="text-sm font-medium px-2 py-1 rounded"></span>
                            </div>
                            <p id="overall-correlation-count" class="text-xs text-gray-500 mt-3">-- data points</p>
                        </div>
                        <!-- Male Correlation -->
                        <div class="rounded-xl border border-blue-200 p-4 bg-blue-50/60">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Male students</p>
                            <div class="flex items-center justify-between mt-2">
                                <span id="male-correlation-value" class="text-3xl font-bold text-blue-700">--</span>
                                <span id="male-correlation-type" class="text-sm font-medium px-2 py-1 rounded"></span>
                            </div>
                            <p id="male-correlation-count" class="text-xs text-gray-600 mt-3">-- data points</p>
                        </div>
                        <!-- Female Correlation -->
                        <div class="rounded-xl border border-pink-200 p-4 bg-pink-50/70">
                            <p class="text-xs font-semibold text-pink-600 uppercase tracking-wide">Female students</p>
                            <div class="flex items-center justify-between mt-2">
                                <span id="female-correlation-value" class="text-3xl font-bold text-pink-700">--</span>
                                <span id="female-correlation-type" class="text-sm font-medium px-2 py-1 rounded"></span>
                            </div>
                            <p id="female-correlation-count" class="text-xs text-gray-600 mt-3">-- data points</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-gray-100 p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-lg">FT</div>
                        <div>
                            <p class="text-xs uppercase font-semibold text-gray-500 tracking-wide">Focus range</p>
                            <p class="text-sm text-gray-700">30 min to 2 hrs sessions considered</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-semibold text-lg">QS</div>
                        <div>
                            <p class="text-xs uppercase font-semibold text-gray-500 tracking-wide">Quiz scores</p>
                            <p class="text-sm text-gray-700">Final quiz attempt per student</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-semibold text-lg">DP</div>
                        <div>
                            <p class="text-xs uppercase font-semibold text-gray-500 tracking-wide">Data points</p>
                            <p class="text-sm text-gray-700">Shown per module filter</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- Student Performance & Progress -->
    <div class="grid grid-cols-1 gap-3 mb-3">
        <!-- Student Data Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <div class="mb-4 sm:mb-0">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Student Performance Data</h3>
                    <p class="text-sm text-gray-600">View and analyze student performance metrics</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-4 sm:mt-0 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-initial sm:w-56">
                        <input 
                            type="text" 
                            id="student-search-input"
                            placeholder="Search students..." 
                            class="w-full border border-gray-300 rounded-lg py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        >
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <select 
                        id="student-performance-module-filter" 
                        class="flex-1 sm:flex-initial sm:w-40 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white cursor-pointer"
                    >
                        <option value="all">All Modules</option>
                        <?php
                        // Get available modules for filter
                        $moduleQuery = "SELECT id, title FROM modules WHERE status = 'published' ORDER BY title";
                        $moduleResult = $conn->query($moduleQuery);
                        while ($module = $moduleResult->fetch_assoc()) {
                            echo "<option value='{$module['id']}'>{$module['title']}</option>";
                        }
                        ?>
                    </select>
                    <select 
                        id="student-performance-section-filter" 
                        class="flex-1 sm:flex-initial sm:w-36 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white cursor-pointer"
                    >
                        <option value="all">All Sections</option>
                        <option value="BSINFO-1A">BSINFO-1A</option>
                        <option value="BSINFO-1B">BSINFO-1B</option>
                        <option value="BSINFO-1C">BSINFO-1C</option>
                    </select>
                    <select 
                        id="student-items-per-page" 
                        class="flex-1 sm:flex-initial sm:w-32 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white cursor-pointer"
                    >
                        <option value="10">Show 10</option>
                        <option value="25" selected>Show 25</option>
                        <option value="50">Show 50</option>
                        <option value="100">Show 100</option>
                        <option value="0">Show All</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Student ID</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Section</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Gender</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Quiz Score</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Progress</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Focus Time</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Total Sessions</th>
                        </tr>
                    </thead>
                    <tbody id="student-performance-tbody" class="bg-white divide-y divide-gray-200">
                        <?php
                        // Get student performance data with improved focus time filtering
                        // Default: show all modules aggregated - show ALL students
                        // Check if section column exists
                        $check_section = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
                        $has_section = $check_section && $check_section->num_rows > 0;
                        
                        if ($has_section) {
                            $studentQuery = "SELECT 
                                u.id,
                                u.first_name,
                                u.last_name,
                                u.email,
                                u.gender,
                                u.section,
                                COALESCE(AVG(up.completion_percentage), NULL) as avg_completion,
                                COALESCE(AVG(qr.score), NULL) as avg_quiz_score,
                                COALESCE(AVG(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN ets.total_time_seconds ELSE NULL END), NULL) as avg_focus_time_seconds,
                                COUNT(DISTINCT ets.id) as total_sessions,
                                COUNT(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                                FROM users u
                                LEFT JOIN user_progress up ON u.id = up.user_id
                                LEFT JOIN quiz_results qr ON u.id = qr.user_id
                                LEFT JOIN eye_tracking_sessions ets ON u.id = ets.user_id
                                WHERE u.role = 'student'
                                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender, u.section
                                ORDER BY u.id ASC";
                        } else {
                            $studentQuery = "SELECT 
                                u.id,
                                u.first_name,
                                u.last_name,
                                u.email,
                                u.gender,
                                NULL as section,
                                COALESCE(AVG(up.completion_percentage), NULL) as avg_completion,
                                COALESCE(AVG(qr.score), NULL) as avg_quiz_score,
                                COALESCE(AVG(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN ets.total_time_seconds ELSE NULL END), NULL) as avg_focus_time_seconds,
                                COUNT(DISTINCT ets.id) as total_sessions,
                                COUNT(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                                FROM users u
                                LEFT JOIN user_progress up ON u.id = up.user_id
                                LEFT JOIN quiz_results qr ON u.id = qr.user_id
                                LEFT JOIN eye_tracking_sessions ets ON u.id = ets.user_id
                                WHERE u.role = 'student'
                                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender
                                ORDER BY u.id ASC";
                        }
                        
                        $studentResult = $conn->query($studentQuery);
                        
                        if ($studentResult && $studentResult->num_rows > 0) {
                            while ($student = $studentResult->fetch_assoc()) {
                                $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
                                $hasCompletionData = $student['avg_completion'] !== null;
                                $hasQuizData = $student['avg_quiz_score'] !== null;
                                $hasFocusTimeData = isset($student['avg_focus_time_seconds']) && $student['avg_focus_time_seconds'] !== null;
                                
                                // Ensure progress is always a percentage (0-100), capped at 100%
                                $avgCompletion = $hasCompletionData ? min(100.0, max(0.0, round($student['avg_completion'], 1))) : null;
                                $avgFocusTimeSeconds = $hasFocusTimeData ? $student['avg_focus_time_seconds'] : null;
                                $avgFocusTimeMinutes = $hasFocusTimeData && $avgFocusTimeSeconds > 0 ? round($avgFocusTimeSeconds / 60, 1) : null;
                                
                                // Calculate Average Focus Time Per Session = Total Focus Time / Total Valid Sessions
                                $avgFocusTimePerSessionMinutes = null;
                                if ($hasFocusTimeData && $avgFocusTimeSeconds > 0 && $student['valid_sessions'] > 0) {
                                    $avgFocusTimePerSessionMinutes = round(($avgFocusTimeSeconds / 60) / $student['valid_sessions'], 1);
                                }
                                
                                $gender = $student['gender'] ?: 'Not specified';
                                $totalSessions = $student['total_sessions'];
                                $validSessions = $student['valid_sessions'];
                                // For initial load (all modules), quiz score is percentage
                                // Note: This will be updated when module filter changes via AJAX
                                $avgQuizScore = $hasQuizData ? round($student['avg_quiz_score'], 1) : null;
                                $isQuizPercentage = true; // Initial load shows all modules (percentage)
                                
                                echo "<tr class='hover:bg-gray-50 transition-colors duration-150'>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800'>ST-{$student['id']}</span>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<div class='flex items-center'>";
                                echo "<div class='h-10 w-10 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm'>{$initials}</div>";
                                echo "<div class='ml-3'>";
                                echo "<div class='text-sm font-semibold text-gray-900'>{$student['first_name']} {$student['last_name']}</div>";
                                echo "<div class='text-sm text-gray-500'>{$student['email']}</div>";
                                echo "</div></div></td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                $section = isset($student['section']) && $student['section'] ? $student['section'] : 'Not specified';
                                echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800'>{$section}</span>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                $genderBadgeColor = $gender === 'Male' ? 'bg-blue-100 text-blue-800' : ($gender === 'Female' ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-800');
                                echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$genderBadgeColor}'>{$gender}</span>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                if ($hasQuizData && $avgQuizScore !== null) {
                                    // For all modules view, show percentage with color coding
                                    $scoreColor = $avgQuizScore >= 80 ? 'text-green-600' : ($avgQuizScore >= 60 ? 'text-yellow-600' : 'text-red-600');
                                    echo "<div class='flex items-center'>";
                                    echo "<span class='text-sm font-semibold {$scoreColor}'>{$avgQuizScore}%</span>";
                                    echo "</div>";
                                } else {
                                    echo "<span class='inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500'>No valid data</span>";
                                }
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                if ($hasCompletionData && $avgCompletion !== null) {
                                    echo "<div class='flex items-center space-x-2'>";
                                    echo "<div class='flex-1'>";
                                    echo "<div class='flex justify-between text-xs mb-1'>";
                                    echo "<span class='font-medium text-gray-700'>{$avgCompletion}%</span>";
                                    echo "</div>";
                                    echo "<div class='w-full bg-gray-200 rounded-full h-2 overflow-hidden'>";
                                    $progressColor = $avgCompletion >= 80 ? 'bg-green-500' : ($avgCompletion >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                                    echo "<div class='{$progressColor} h-2 rounded-full transition-all duration-300' style='width: {$avgCompletion}%'></div>";
                                    echo "</div></div></div>";
                                } else {
                                    echo "<span class='inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500'>No valid data</span>";
                                }
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                if ($hasFocusTimeData && $avgFocusTimeMinutes !== null && $avgFocusTimeMinutes > 0) {
                                    echo "<div class='text-sm font-medium text-gray-900'>{$avgFocusTimeMinutes} min</div>";
                                    if ($avgFocusTimePerSessionMinutes !== null && $avgFocusTimePerSessionMinutes > 0) {
                                        echo "<div class='text-xs font-medium text-blue-600 mt-0.5'>{$avgFocusTimePerSessionMinutes} min/session</div>";
                                    }
                                    echo "<div class='text-xs text-gray-500 mt-0.5'>{$validSessions} valid sessions</div>";
                                } else {
                                    echo "<span class='inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500'>No valid data</span>";
                                }
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<div class='text-sm font-medium text-gray-900'>{$totalSessions}</div>";
                                echo "<div class='text-xs text-gray-500 mt-0.5'>{$validSessions} valid</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='px-6 py-12 text-center'>";
                            echo "<div class='flex flex-col items-center justify-center'>";
                            echo "<svg class='w-12 h-12 text-gray-400 mb-3' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>";
                            echo "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'></path>";
                            echo "</svg>";
                            echo "<p class='text-gray-500 font-medium'>No student data available</p>";
                            echo "<p class='text-sm text-gray-400 mt-1'>Try selecting a different module or check back later</p>";
                            echo "</div></td></tr>";
                        }
                        
                        // Get total count for pagination
                        $countQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
                        $countResult = $conn->query($countQuery);
                        $totalCount = $countResult->fetch_assoc()['total'];
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600 mb-4 sm:mb-0" id="student-count-display">
                    <span class="font-medium text-gray-900"><?php echo $totalCount; ?></span>
                    <span class="text-gray-500"> students</span>
                </div>
                <div class="flex items-center space-x-2" id="student-pagination">
                    <button id="student-prev-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </button>
                    <div id="student-page-numbers" class="flex items-center space-x-1">
                        <button class="student-page-btn px-4 py-2 text-sm font-semibold text-white bg-primary border border-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors" data-page="1">
                            1
                        </button>
                    </div>
                    <button id="student-next-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-trendline@2.0.1/src/chartjs-plugin-trendline.min.js"></script>
<script>
    // Fetch real dashboard data
    async function fetchDashboardData() {
        try {
            const response = await fetch('database/get_dashboard_data.php');
            const data = await response.json();
            
            if (data.error) {
                console.error('Error fetching dashboard data:', data.error);
                return null;
            }
            
            return data;
        } catch (error) {
            console.error('Failed to fetch dashboard data:', error);
            return null;
        }
    }

    // Global chart instances
    let genderChart, gazeChart, timeToCompleteChart, avgScoreByGenderChart, focusScoreCorrelationChart, checkpointQuizResultsChart;

    // Global dashboard data
    let globalDashboardData = null;
    
    // Student performance data management
    let allStudentsData = [];
    let filteredStudentsData = [];
    let currentPage = 1;
    let itemsPerPage = 25;
    let currentSearchTerm = '';
    let currentSectionFilter = 'all';

    // Variable to store the timestamp of the last data update
    let lastUpdateTimestamp = null;

    // Initialize charts with real data
    async function initializeCharts() {
        const dashboardData = await fetchDashboardData();
        
        if (!dashboardData) {
            // Fallback to static data if API fails
            initializeStaticCharts();
            return;
        }

        globalDashboardData = dashboardData;

        // Store the last update timestamp from the initial data load
        lastUpdateTimestamp = dashboardData.last_update;

        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderData = dashboardData.gender_distribution || [];
        
        let maleCount = 0;
        let femaleCount = 0;
        let malePercentage = 0;
        let femalePercentage = 0;
        
        genderData.forEach(item => {
            if (item.gender === 'Male') {
                maleCount = item.count;
                malePercentage = parseFloat(item.percentage);
            }
            if (item.gender === 'Female') {
                femaleCount = item.count;
                femalePercentage = parseFloat(item.percentage);
            }
        });
        
        // If no gender data, show equal distribution
        if (malePercentage === 0 && femalePercentage === 0) {
            malePercentage = 50;
            femalePercentage = 50;
        }
        
        // Update legend with real percentages
        document.getElementById('male-percentage').textContent = `Male (${malePercentage.toFixed(1)}%)`;
        document.getElementById('female-percentage').textContent = `Female (${femalePercentage.toFixed(1)}%)`;
        
        genderChart = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [maleCount || 0, femaleCount || 0],
                    backgroundColor: [
                        '#3B82F6',  // Blue for Male
                        '#EC4899'   // Pink for Female
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
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
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} students (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Gaze Tracking Chart
        const gazeCtx = document.getElementById('gazeTrackingChart').getContext('2d');
        const moduleAnalytics = dashboardData.module_analytics || [];
        
        // Process module analytics data for chart
        const modules = [...new Set(moduleAnalytics.map(item => item.module_name))];
        const maleData = [];
        const femaleData = [];
        
        modules.forEach(module => {
            const maleItem = moduleAnalytics.find(item => item.module_name === module && item.gender === 'Male');
            const femaleItem = moduleAnalytics.find(item => item.module_name === module && item.gender === 'Female');
            
            maleData.push(maleItem ? parseFloat(maleItem.avg_time_minutes || 0) : 0);
            femaleData.push(femaleItem ? parseFloat(femaleItem.avg_time_minutes || 0) : 0);
        });
        
        // If no module data, use sample topics with fallback data
        const chartLabels = modules.length > 0 ? modules : ['Module 1', 'Module 2', 'Module 3', 'Module 4'];
        const chartMaleData = maleData.length > 0 ? maleData : [20.4, 15.2, 18.5, 18.7];
        const chartFemaleData = femaleData.length > 0 ? femaleData : [22.3, 21.8, 20.1, 26.2];
        
        gazeChart = new Chart(gazeCtx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Male Students',
                        data: chartMaleData,
                        backgroundColor: '#3B82F6',
                        borderWidth: 0
                    },
                    {
                        label: 'Female Students',
                        data: chartFemaleData,
                        backgroundColor: '#EC4899',
                        borderWidth: 0
                    }
                ]
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
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: true,
                            font: {
                                size: 10
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: 4,
                            // Add a callback to shorten long labels
                            callback: function(value, index, values) {
                                const label = this.getLabelForValue(value);
                                if (label.length > 15) {
                                    return label.substring(0, 12) + '...';
                                }
                                return label;
                            }
                        },
                        grid: {
                            display: false,
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        }
                    },
                    y: {
                        grid: {
                            borderDash: [2, 4],
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
                            text: 'Average Focus Time (minutes)',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: {
                                top: 4,
                                bottom: 4
                            }
                        },
                        min: 0,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 11
                            },
                            padding: 8,
                            boxWidth: 12
                        }
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
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                return `${label}: ${value.toFixed(1)} minutes avg`;
                            }
                        }
                    }
                }
            }
        });

        // Total Time Completed Module by Gender
            const timeCtx = document.getElementById('timeToCompleteChart').getContext('2d');

        // Use total_time_by_gender for the chart (instead of average)
        const totalTimeData = dashboardData.total_time_by_gender || [];
        const timeData = dashboardData.time_to_complete_by_gender || [];

        if (timeData.length > 0 && totalTimeData.length > 0) {
            const modulesTime = [...new Set(timeData.map(item => item.module_name))];

            // Now use total times (aggregated from total_time_by_gender)
            const maleTotal = totalTimeData.find(item => item.gender === 'Male')?.total_time_minutes || 0;
            const femaleTotal = totalTimeData.find(item => item.gender === 'Female')?.total_time_minutes || 0;

            // Chart will now display total time (not average)
            timeToCompleteChart = new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: modulesTime,
                    datasets: [
                        {
                            label: 'Male',
                            data: modulesTime.map(module => timeData.find(d => d.module_name === module && d.gender === 'Male')?.avg_completion_time_minutes || 0),
                            backgroundColor: '#3B82F6'
                        },
                        {
                            label: 'Female',
                            data: modulesTime.map(module => timeData.find(d => d.module_name === module && d.gender === 'Female')?.avg_completion_time_minutes || 0),
                            backgroundColor: '#EC4899'
                        }
                    ]
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
                            display: true, 
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11
                                },
                                padding: 8,
                                boxWidth: 12
                            }
                        },
                        title: {
                            display: true,
                            text: 'Total Total Time Completed Modules by Gender',
                            font: {
                                size: 12
                            }
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
                            borderWidth: 1,
                            callbacks: {
                                label: function (context) {
                                    const gender = context.label;
                                    const value = context.parsed.y;
                                    return `${gender}: ${value.toFixed(1)} minutes total`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
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
                                text: 'Minutes (Total)',
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
                        },
                        x: {
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0,
                                autoSkip: true,
                                font: {
                                    size: 10
                                },
                                color: 'rgb(107, 114, 128)',
                                padding: 4,
                                callback: function(value, index, values) {
                                    const label = this.getLabelForValue(value);
                                    if (label.length > 15) {
                                        return label.substring(0, 12) + '...';
                                    }
                                    return label;
                                }
                            },
                            grid: {
                                display: false,
                                color: 'rgba(229, 231, 235, 0.5)',
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            // Below the chart, display average times (moved from chart)
            const maleAvg = timeData
                .filter(item => item.gender === 'Male' && item.avg_completion_time_minutes)
                .reduce((sum, i) => sum + parseFloat(i.avg_completion_time_minutes), 0) / (timeData.filter(i => i.gender === 'Male' && i.avg_completion_time_minutes).length || 1);

            const femaleAvg = timeData
                .filter(item => item.gender === 'Female' && item.avg_completion_time_minutes)
                .reduce((sum, i) => sum + parseFloat(i.avg_completion_time_minutes), 0) / (timeData.filter(i => i.gender === 'Female' && i.avg_completion_time_minutes).length || 1);

            const maleTotalElement = document.getElementById('male-total-time');
            const femaleTotalElement = document.getElementById('female-total-time');

            if (maleTotalElement) maleTotalElement.textContent = `${maleAvg.toFixed(1)} min avg`;
            if (femaleTotalElement) femaleTotalElement.textContent = `${femaleAvg.toFixed(1)} min avg`;
        }


            // Average Final Quiz Score by Gender - will be rendered via renderAvgScoreChart function
            renderAvgScoreChart('all');

        // Checkpoint Quiz Results by Gender Chart (per question, same layout style as gaze chart)
        try {
            const checkpointCanvas = document.getElementById('checkpointQuizResultsChart');
            if (!checkpointCanvas) {
                console.warn('Checkpoint quiz chart canvas not found');
            } else {
                const checkpointCtx = checkpointCanvas.getContext('2d');
                const checkpointData = dashboardData.checkpoint_quiz_results_by_gender || [];

                // Debug logging
                console.log('Checkpoint quiz data received:', checkpointData);
                console.log('Data length:', checkpointData.length);
                console.log('Full dashboard data:', dashboardData);

                if (checkpointQuizResultsChart) {
                    checkpointQuizResultsChart.destroy();
                }

                // Use real data if available, otherwise show empty state
                const hasData = Array.isArray(checkpointData) && checkpointData.length > 0;
                
                const questionLabels = hasData
                    ? checkpointData.map((item, index) => {
                        const questionText = item.question_text || `Question ${index + 1}`;
                        return questionText.length > 22 ? `${questionText.substring(0, 20)}...` : questionText;
                    })
                    : ['No data available'];

                const maleCorrectData = hasData
                    ? checkpointData.map(item => parseInt(item.male_correct) || 0)
                    : [0];
                const femaleCorrectData = hasData
                    ? checkpointData.map(item => parseInt(item.female_correct) || 0)
                    : [0];

                const maleWrongTotal = hasData
                    ? checkpointData.reduce((sum, item) => sum + (parseInt(item.male_wrong) || 0), 0)
                    : 0;
                const femaleWrongTotal = hasData
                    ? checkpointData.reduce((sum, item) => sum + (parseInt(item.female_wrong) || 0), 0)
                    : 0;
                const maleCorrectTotal = hasData
                    ? maleCorrectData.reduce((sum, value) => sum + value, 0)
                    : 0;
                const femaleCorrectTotal = hasData
                    ? femaleCorrectData.reduce((sum, value) => sum + value, 0)
                    : 0;

                // Update summary cards
                const maleCorrectEl = document.getElementById('male-checkpoint-correct');
                const maleWrongEl = document.getElementById('male-checkpoint-wrong');
                const femaleCorrectEl = document.getElementById('female-checkpoint-correct');
                const femaleWrongEl = document.getElementById('female-checkpoint-wrong');
                
                if (maleCorrectEl) maleCorrectEl.textContent = maleCorrectTotal;
                if (maleWrongEl) maleWrongEl.textContent = maleWrongTotal;
                if (femaleCorrectEl) femaleCorrectEl.textContent = femaleCorrectTotal;
                if (femaleWrongEl) femaleWrongEl.textContent = femaleWrongTotal;

                checkpointQuizResultsChart = new Chart(checkpointCtx, {
                    type: 'bar',
                    data: {
                        labels: questionLabels,
                        datasets: [
                            {
                                label: 'Male Students',
                                data: maleCorrectData,
                                backgroundColor: '#3B82F6',
                                borderWidth: 0,
                                barThickness: 'flex'
                            },
                            {
                                label: 'Female Students',
                                data: femaleCorrectData,
                                backgroundColor: '#EC4899',
                                borderWidth: 0,
                                barThickness: 'flex'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: { top: 8, bottom: 8, left: 8, right: 8 }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    color: 'rgba(229, 231, 235, 0.5)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 10 },
                                    color: 'rgb(107, 114, 128)',
                                    padding: 4,
                                    callback: function(value) {
                                        const label = this.getLabelForValue(value);
                                        return label.length > 12 ? `${label.substring(0, 10)}...` : label;
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    borderDash: [2, 4],
                                    color: 'rgba(229, 231, 235, 0.5)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 10 },
                                    color: 'rgb(107, 114, 128)',
                                    padding: 4,
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Correct Answers',
                                    font: { size: 11, weight: '500' },
                                    color: 'rgb(107, 114, 128)',
                                    padding: { top: 4, bottom: 4 }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { size: 11 },
                                    padding: 8,
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 8,
                                titleFont: { size: 12 },
                                bodyFont: { size: 11 },
                                borderColor: 'rgb(229, 231, 235)',
                                borderWidth: 1,
                                callbacks: {
                                    title: function(context) {
                                        if (!checkpointData.length) {
                                            return context[0].label;
                                        }
                                        const originalQuestion = checkpointData[context[0].dataIndex];
                                        return originalQuestion?.question_text || context[0].label;
                                    },
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        return `${label}: ${context.parsed.y || 0} correct`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error rendering checkpoint quiz results chart:', error);
        }

        // Update focus time summary with real data
        const focusTimeData = dashboardData.focus_time_by_gender || [];
        const maleAvgTime = focusTimeData.find(item => item.gender === 'Male')?.avg_focus_time_minutes || 18.2;
        const femaleAvgTime = focusTimeData.find(item => item.gender === 'Female')?.avg_focus_time_minutes || 22.6;
        
        // Update the focus time display in the DOM with specific IDs
        const maleFocusElement = document.getElementById('male-focus-time');
        const femaleFocusElement = document.getElementById('female-focus-time');
        
        if (maleFocusElement) {
            maleFocusElement.textContent = `${parseFloat(maleAvgTime).toFixed(1)} min avg`;
        }
        if (femaleFocusElement) {
            femaleFocusElement.textContent = `${parseFloat(femaleAvgTime).toFixed(1)} min avg`;
        }
        
        // Render the correlation chart with the fetched data (default: all modules)
        renderCorrelationChart('all');

        console.log('Charts and focus time initialized with real data');
    }

    // Function to format correlation type badge
    function formatCorrelationType(corrValue, corrType) {
        if (corrValue === null || corrType === 'insufficient_data') {
            return '<span class="bg-gray-200 text-gray-600">Insufficient Data</span>';
        }
        
        let badgeClass = '';
        let badgeText = '';
        
        if (corrType === 'positive') {
            badgeClass = 'bg-green-100 text-green-700';
            badgeText = 'Positive';
        } else if (corrType === 'negative') {
            badgeClass = 'bg-red-100 text-red-700';
            badgeText = 'Negative';
        } else {
            badgeClass = 'bg-yellow-100 text-yellow-700';
            badgeText = 'No Correlation';
        }
        
        return `<span class="${badgeClass} px-2 py-1 rounded text-xs font-medium">${badgeText}</span>`;
    }

    // Function to update correlation statistics display
    function updateCorrelationStats(pearsonData) {
        if (!pearsonData) {
            // Reset all displays
            document.getElementById('overall-correlation-value').textContent = '--';
            document.getElementById('overall-correlation-type').innerHTML = '';
            document.getElementById('overall-correlation-count').textContent = '-- data points';
            document.getElementById('male-correlation-value').textContent = '--';
            document.getElementById('male-correlation-type').innerHTML = '';
            document.getElementById('male-correlation-count').textContent = '-- data points';
            document.getElementById('female-correlation-value').textContent = '--';
            document.getElementById('female-correlation-type').innerHTML = '';
            document.getElementById('female-correlation-count').textContent = '-- data points';
            return;
        }

        // Overall correlation
        const overallValue = pearsonData.overall !== null ? pearsonData.overall.toFixed(3) : '--';
        document.getElementById('overall-correlation-value').textContent = overallValue;
        document.getElementById('overall-correlation-type').innerHTML = formatCorrelationType(pearsonData.overall, pearsonData.overall_type);
        document.getElementById('overall-correlation-count').textContent = `${pearsonData.overall_count || 0} data points`;

        // Male correlation
        const maleValue = pearsonData.male !== null ? pearsonData.male.toFixed(3) : '--';
        document.getElementById('male-correlation-value').textContent = maleValue;
        document.getElementById('male-correlation-type').innerHTML = formatCorrelationType(pearsonData.male, pearsonData.male_type);
        document.getElementById('male-correlation-count').textContent = `${pearsonData.male_count || 0} data points`;

        // Female correlation
        const femaleValue = pearsonData.female !== null ? pearsonData.female.toFixed(3) : '--';
        document.getElementById('female-correlation-value').textContent = femaleValue;
        document.getElementById('female-correlation-type').innerHTML = formatCorrelationType(pearsonData.female, pearsonData.female_type);
        document.getElementById('female-correlation-count').textContent = `${pearsonData.female_count || 0} data points`;
    }

    async function renderCorrelationChart(moduleId = 'all') {
        const correlationCtx = document.getElementById('focusScoreCorrelationChart').getContext('2d');
        const noDataEl = document.getElementById('correlation-no-data');

        // Fetch fresh data for the selected module
        try {
            const url = moduleId === 'all' 
                ? 'database/get_dashboard_data.php'
                : `database/get_dashboard_data.php?module_id=${moduleId}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                console.error('Error fetching correlation data:', data.error);
                correlationCtx.canvas.style.display = 'none';
                noDataEl.classList.remove('hidden');
                noDataEl.classList.add('flex');
                updateCorrelationStats(null);
                return;
            }

            let correlationData = data.focus_score_correlation || [];
            const pearsonData = data.focus_score_pearson || null;

            // Update correlation statistics
            updateCorrelationStats(pearsonData);

            if (focusScoreCorrelationChart) {
                focusScoreCorrelationChart.destroy();
            }

            if (correlationData.length > 0) {
                // Show chart, hide no-data message
                correlationCtx.canvas.style.display = 'block';
                noDataEl.classList.add('hidden');
                noDataEl.classList.remove('flex');

                // Process data with module information for better tooltips
                const maleData = correlationData
                    .filter(d => d.gender === 'Male' && d.focus_time_minutes > 0)
                    .map(d => ({
                        x: parseFloat(d.focus_time_minutes),
                        y: parseFloat(d.quiz_score),
                        module: d.module_name || 'Unknown Module'
                    }));

                const femaleData = correlationData
                    .filter(d => d.gender === 'Female' && d.focus_time_minutes > 0)
                    .map(d => ({
                        x: parseFloat(d.focus_time_minutes),
                        y: parseFloat(d.quiz_score),
                        module: d.module_name || 'Unknown Module'
                    }));

                // Calculate data ranges for better axis scaling
                const allDataPoints = [...maleData, ...femaleData];
                const xValues = allDataPoints.map(d => d.x);
                const yValues = allDataPoints.map(d => d.y);
                const xMin = Math.min(...xValues);
                const xMax = Math.max(...xValues);
                const yMin = Math.min(...yValues);
                const yMax = Math.max(...yValues);
                
                // Add padding to axis ranges (10% padding)
                const xRange = xMax - xMin;
                const yRange = yMax - yMin;
                const xPadding = xRange * 0.1 || 1;
                const yPadding = yRange * 0.1 || 5;
                
                // Determine point size based on data count (larger points for fewer data points)
                const totalPoints = maleData.length + femaleData.length;
                const baseRadius = totalPoints <= 5 ? 8 : (totalPoints <= 10 ? 6 : 4);
                const hoverRadius = baseRadius + 3;

                focusScoreCorrelationChart = new Chart(correlationCtx, {
                    type: 'scatter',
                    data: {
                        datasets: [
                            {
                                label: 'Male Students',
                                data: maleData,
                                backgroundColor: 'rgba(59, 130, 246, 0.75)', // More opaque for better visibility
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 2,
                                pointRadius: baseRadius,
                                hoverRadius: hoverRadius,
                                pointHoverBackgroundColor: 'rgba(59, 130, 246, 1)',
                                pointHoverBorderColor: '#fff',
                                pointHoverBorderWidth: 2,
                                trendlineLinear: { 
                                    style: "rgba(59, 130, 246, 0.6)", 
                                    lineStyle: "dashed", 
                                    width: 2,
                                    showR2: false
                                }
                            },
                            {
                                label: 'Female Students',
                                data: femaleData,
                                backgroundColor: 'rgba(236, 72, 153, 0.75)', // More opaque for better visibility
                                borderColor: 'rgba(236, 72, 153, 1)',
                                borderWidth: 2,
                                pointRadius: baseRadius,
                                hoverRadius: hoverRadius,
                                pointHoverBackgroundColor: 'rgba(236, 72, 153, 1)',
                                pointHoverBorderColor: '#fff',
                                pointHoverBorderWidth: 2,
                                trendlineLinear: { 
                                    style: "rgba(236, 72, 153, 0.6)", 
                                    lineStyle: "dashed", 
                                    width: 2,
                                    showR2: false
                                }
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'nearest'
                        },
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
                                position: 'bottom',
                                labels: { 
                                    usePointStyle: true, 
                                    boxWidth: 8,
                                    font: {
                                        size: 11
                                    },
                                    padding: 8
                                }
                            },
                            title: {
                                display: false, // Title is now handled in HTML
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgb(229, 231, 235)',
                                borderWidth: 1,
                                padding: 10,
                                titleFont: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 11
                                },
                                displayColors: true,
                                callbacks: {
                                    title: function(context) {
                                        const point = context[0];
                                        const dataPoint = point.raw;
                                        return dataPoint.module || 'Student Data';
                                    },
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const score = context.parsed.y.toFixed(1);
                                        const time = context.parsed.x.toFixed(1);
                                        return [
                                            `${label}`,
                                            `Quiz Score: ${score}%`,
                                            `Focus Time: ${time} min`
                                        ];
                                    },
                                    labelColor: function(context) {
                                        return {
                                            borderColor: context.dataset.borderColor,
                                            backgroundColor: context.dataset.backgroundColor,
                                            borderWidth: 2,
                                            borderRadius: 2
                                        };
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { 
                                type: 'linear', 
                                position: 'bottom',
                                min: Math.max(0, xMin - xPadding),
                                max: xMax + xPadding,
                                grid: { 
                                    color: 'rgba(229, 231, 235, 0.5)',
                                    drawBorder: false,
                                    drawOnChartArea: true
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    },
                                    color: 'rgb(107, 114, 128)',
                                    padding: 4,
                                    stepSize: xRange > 20 ? 5 : (xRange > 10 ? 2 : 1),
                                    callback: function(value) {
                                        return value.toFixed(1);
                                    }
                                },
                                title: { 
                                    display: true, 
                                    text: 'Total Focus Time (minutes)',
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
                            },
                            y: { 
                                beginAtZero: false,
                                min: Math.max(0, yMin - yPadding),
                                max: Math.min(100, yMax + yPadding),
                                grid: { 
                                    color: 'rgba(229, 231, 235, 0.5)',
                                    drawBorder: false,
                                    drawOnChartArea: true
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    },
                                    color: 'rgb(107, 114, 128)',
                                    padding: 4,
                                    stepSize: yRange > 50 ? 10 : (yRange > 20 ? 5 : 2),
                                    callback: function(value) {
                                        return value.toFixed(0) + '%';
                                    }
                                },
                                title: { 
                                    display: true, 
                                    text: 'Quiz Score (%)',
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
            } else {
                // Hide chart, show no-data message
                correlationCtx.canvas.style.display = 'none';
                noDataEl.classList.remove('hidden');
                noDataEl.classList.add('flex');
            }
        } catch (error) {
            console.error('Error rendering correlation chart:', error);
            correlationCtx.canvas.style.display = 'none';
            noDataEl.classList.remove('hidden');
            noDataEl.classList.add('flex');
            updateCorrelationStats(null);
        }
    }

    // Function to render/update Average Final Quiz Score by Gender chart
    async function renderAvgScoreChart(moduleId = 'all') {
        const scoreCtx = document.getElementById('avgScoreByGenderChart').getContext('2d');

        // Fetch fresh data for the selected module
        try {
            const url = moduleId === 'all' 
                ? 'database/get_dashboard_data.php'
                : `database/get_dashboard_data.php?avg_score_module_id=${moduleId}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                console.error('Error fetching avg score data:', data.error);
                return;
            }

            const scoreData = data.avg_score_by_gender || [];

            // Destroy existing chart if it exists
            if (avgScoreByGenderChart) {
                avgScoreByGenderChart.destroy();
            }

            if (scoreData.length > 0) {
                const genders = scoreData.map(item => item.gender);
                const scores = scoreData.map(item => item.avg_score);

                avgScoreByGenderChart = new Chart(scoreCtx, {
                    type: 'bar',
                    data: {
                        labels: genders,
                        datasets: [{
                            label: 'Average Final Quiz Score',
                            data: scores,
                            backgroundColor: genders.map(g => g === 'Male' ? '#3B82F6' : '#EC4899')
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
                            legend: { display: false },
                            title: {
                                display: false // Title is now handled in HTML
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
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label;
                                        const value = context.parsed.y || 0;
                                        const record = scoreData.find(
                                            item => item.gender === label
                                        );
                                        const studentCount = record ? record.student_count : 0;
                                        const studentLabel = studentCount === 1 ? 'student' : 'students';
                                        return `${label}: ${value.toFixed(1)}% average (from ${studentCount} ${studentLabel})`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
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
                                max: 100,
                                grid: {
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
                                    text: 'Average Score (%)',
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
            } else {
                // Show empty chart or message if no data
                avgScoreByGenderChart = new Chart(scoreCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Male', 'Female'],
                        datasets: [{
                            label: 'Average Final Quiz Score',
                            data: [0, 0],
                            backgroundColor: ['#3B82F6', '#EC4899']
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
                            legend: { display: false },
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
                                borderWidth: 1,
                                callbacks: {
                                    label: function() {
                                        return 'No data available';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
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
                                max: 100,
                                grid: {
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
                                    text: 'Average Score (%)',
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
        } catch (error) {
            console.error('Error rendering avg score chart:', error);
        }
    }

    // Fallback function for static charts if API fails
    function initializeStaticCharts() {
        // Gender Distribution Chart
        genderChart = new Chart(document.getElementById('genderChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [50, 50],
                    backgroundColor: [
                        '#3B82F6',
                        '#EC4899'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
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
                }
            }
        });

        // Gaze Tracking Chart
        gazeChart = new Chart(document.getElementById('gazeTrackingChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Topic 1', 'Topic 2', 'Topic 3', 'Topic 4'],
                datasets: [
                    {
                        label: 'Male Students',
                        data: [20.4, 15.2, 18.5, 18.7],
                        backgroundColor: '#3B82F6',
                        borderWidth: 0
                    },
                    {
                        label: 'Female Students',
                        data: [22.3, 21.8, 20.1, 26.2],
                        backgroundColor: '#EC4899',
                        borderWidth: 0
                    }
                ]
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
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0, // Prevent labels from slanting
                            minRotation: 0, // Prevent labels from slanting
                            autoSkip: true, // Allow Chart.js to automatically skip labels if they overlap
                            font: {
                                size: 10
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: 4
                        },
                        grid: {
                            display: false,
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        }
                    },
                    y: {
                        grid: {
                            borderDash: [2, 4],
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
                            text: 'Average Focus Time (minutes)',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: 'rgb(107, 114, 128)',
                            padding: {
                                top: 4,
                                bottom: 4
                            }
                        },
                        min: 0
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 11
                            },
                            padding: 8,
                            boxWidth: 12
                        }
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
                }
            }
        });
        
        // Update focus time with static fallback data
        const maleFocusElement = document.getElementById('male-focus-time');
        const femaleFocusElement = document.getElementById('female-focus-time');
        
        if (maleFocusElement) {
            maleFocusElement.textContent = '18.2 min avg';
        }
        if (femaleFocusElement) {
            femaleFocusElement.textContent = '22.6 min avg';
        }
        
        // Checkpoint Quiz Results by Gender Chart (static fallback)
        const checkpointCtx = document.getElementById('checkpointQuizResultsChart');
        if (checkpointCtx) {
            checkpointQuizResultsChart = new Chart(checkpointCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Question 1', 'Question 2', 'Question 3', 'Question 4'],
                    datasets: [
                        {
                            label: 'Male Students',
                            data: [4, 6, 3, 5],
                            backgroundColor: '#3B82F6',
                            borderWidth: 0
                        },
                        {
                            label: 'Female Students',
                            data: [5, 4, 6, 7],
                            backgroundColor: '#EC4899',
                            borderWidth: 0
                        }
                    ]
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
                    scales: {
                        x: {
                            grid: {
                                display: false,
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
                                borderDash: [2, 4],
                                color: 'rgba(229, 231, 235, 0.5)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                                color: 'rgb(107, 114, 128)',
                                padding: 4,
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Correct Answers',
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
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11
                                },
                                padding: 8,
                                boxWidth: 12
                            }
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
                    }
                }
            });
            
            // Update summary text with fallback values
            document.getElementById('male-checkpoint-correct').textContent = '18';
            document.getElementById('male-checkpoint-wrong').textContent = '0';
            document.getElementById('female-checkpoint-correct').textContent = '22';
            document.getElementById('female-checkpoint-wrong').textContent = '0';
        }
    }

    // Function to render a single student row
    function renderStudentRow(student) {
        const hasCompletionData = student.avg_completion !== null && student.avg_completion !== undefined;
        const hasQuizData = student.avg_quiz_score !== null && student.avg_quiz_score !== undefined;
        const hasFocusTimeData = student.avg_focus_time_minutes !== null && student.avg_focus_time_minutes !== undefined && student.avg_focus_time_minutes > 0;
        
        // Ensure progress is always a percentage (0-100), capped at 100%
        const avgCompletion = hasCompletionData ? Math.min(100.0, Math.max(0.0, parseFloat(student.avg_completion || 0))) : null;
        const avgCompletionFormatted = avgCompletion !== null ? parseFloat(avgCompletion.toFixed(1)) : null;
        const avgQuizScore = hasQuizData ? student.avg_quiz_score : null;
        const isQuizPercentage = student.is_quiz_percentage !== undefined ? student.is_quiz_percentage : false;
        const quizScoreDisplay = student.quiz_score_display || null; // Use formatted display value if available
        const avgFocusTimeMinutes = hasFocusTimeData ? student.avg_focus_time_minutes : null;
        // Average Focus Time Per Session = Total Focus Time / Total Valid Sessions
        const avgFocusTimePerSessionMinutes = student.avg_focus_time_per_session_minutes !== null && student.avg_focus_time_per_session_minutes !== undefined ? student.avg_focus_time_per_session_minutes : null;
        const totalSessions = student.total_sessions || 0;
        const validSessions = student.valid_sessions || 0;
        const gender = student.gender || 'Not specified';
        
        // Determine colors based on scores (only if data exists and it's a percentage)
        // For fractions, don't apply color coding
        const scoreColor = hasQuizData && isQuizPercentage ? (avgQuizScore >= 80 ? 'text-green-600' : (avgQuizScore >= 60 ? 'text-yellow-600' : 'text-red-600')) : 'text-gray-900';
        const progressColor = hasCompletionData && avgCompletionFormatted !== null ? (avgCompletionFormatted >= 80 ? 'bg-green-500' : (avgCompletionFormatted >= 60 ? 'bg-yellow-500' : 'bg-red-500')) : '';
        const genderBadgeColor = gender === 'Male' ? 'bg-blue-100 text-blue-800' : (gender === 'Female' ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-800');
        
        const section = student.section || 'Not specified';
        
        return `<tr class="hover:bg-gray-50 transition-colors duration-150">
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">ST-${student.id}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">${student.initials}</div>
                    <div class="ml-3">
                        <div class="text-sm font-semibold text-gray-900">${student.name}</div>
                        <div class="text-sm text-gray-500">${student.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">${section}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${genderBadgeColor}">${gender}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${quizScoreDisplay !== null && quizScoreDisplay !== undefined
                    ? `<div class="flex items-center"><span class="text-sm font-semibold ${scoreColor}">${quizScoreDisplay}</span></div>`
                    : '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">No valid data</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${hasCompletionData && avgCompletionFormatted !== null
                    ? `<div class="flex items-center space-x-2">
                        <div class="flex-1">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700">${avgCompletionFormatted}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="${progressColor} h-2 rounded-full transition-all duration-300" style="width: ${avgCompletionFormatted}%"></div>
                            </div>
                        </div>
                    </div>`
                    : '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">No valid data</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${hasFocusTimeData && avgFocusTimeMinutes !== null && avgFocusTimeMinutes > 0
                    ? `<div class="text-sm font-medium text-gray-900">${avgFocusTimeMinutes} min</div>${avgFocusTimePerSessionMinutes !== null && avgFocusTimePerSessionMinutes > 0 ? `<div class="text-xs font-medium text-blue-600 mt-0.5">${avgFocusTimePerSessionMinutes} min/session</div>` : ''}<div class="text-xs text-gray-500 mt-0.5">${validSessions} valid sessions</div>`
                    : '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">No valid data</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${totalSessions}</div>
                <div class="text-xs text-gray-500 mt-0.5">${validSessions} valid</div>
            </td>
        </tr>`;
    }
    
    // Function to filter students based on search term and section
    function filterStudents(students, searchTerm, sectionFilter) {
        let filtered = students;
        
        // Filter by section
        if (sectionFilter && sectionFilter !== 'all') {
            filtered = filtered.filter(student => {
                const section = (student.section || '').trim();
                return section === sectionFilter;
            });
        }
        
        // Filter by search term
        if (searchTerm) {
            const term = searchTerm.toLowerCase().trim();
            filtered = filtered.filter(student => {
                const name = (student.name || '').toLowerCase();
                const email = (student.email || '').toLowerCase();
                const id = `st-${student.id}`.toLowerCase();
                const gender = (student.gender || '').toLowerCase();
                const section = (student.section || '').toLowerCase();
                
                return name.includes(term) || email.includes(term) || id.includes(term) || gender.includes(term) || section.includes(term);
            });
        }
        
        // Sort by student ID in ascending order
        return filtered.sort((a, b) => (a.id || 0) - (b.id || 0));
    }
    
    // Function to render pagination
    function renderPagination(totalItems, currentPageNum, itemsPerPageNum) {
        const totalPages = itemsPerPageNum === 0 ? 1 : Math.ceil(totalItems / itemsPerPageNum);
        const pageNumbers = document.getElementById('student-page-numbers');
        const prevBtn = document.getElementById('student-prev-btn');
        const nextBtn = document.getElementById('student-next-btn');
        
        if (!pageNumbers || !prevBtn || !nextBtn) return;
        
        // Update prev/next buttons
        prevBtn.disabled = currentPageNum <= 1;
        nextBtn.disabled = currentPageNum >= totalPages || totalPages === 0;
        
        // Clear existing page numbers
        pageNumbers.innerHTML = '';
        
        // Generate page numbers (show max 5 pages)
        let startPage = Math.max(1, currentPageNum - 2);
        let endPage = Math.min(totalPages, currentPageNum + 2);
        
        if (endPage - startPage < 4) {
            if (startPage === 1) {
                endPage = Math.min(5, totalPages);
            } else {
                startPage = Math.max(1, totalPages - 4);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = `student-page-btn px-4 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors ${
                i === currentPageNum 
                    ? 'text-white bg-primary border-primary hover:bg-blue-600 font-semibold' 
                    : 'text-gray-700 bg-white'
            }`;
            btn.textContent = i;
            btn.dataset.page = i;
            btn.addEventListener('click', () => {
                currentPage = i;
                renderTable();
            });
            pageNumbers.appendChild(btn);
        }
    }
    
    // Function to render the table with pagination and search
    function renderTable() {
        const tbody = document.getElementById('student-performance-tbody');
        if (!tbody) return;
        
        // Filter students based on search and section
        filteredStudentsData = filterStudents(allStudentsData, currentSearchTerm, currentSectionFilter);
        
        // Calculate pagination
        const totalItems = filteredStudentsData.length;
        const startIndex = itemsPerPage === 0 ? 0 : (currentPage - 1) * itemsPerPage;
        const endIndex = itemsPerPage === 0 ? totalItems : startIndex + itemsPerPage;
        const studentsToShow = filteredStudentsData.slice(startIndex, endIndex);
        
        // Render table rows
        if (studentsToShow.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">${currentSearchTerm || currentSectionFilter !== 'all' ? 'No students found' : 'No student data available'}</p>
                    <p class="text-sm text-gray-400 mt-1">${currentSearchTerm || currentSectionFilter !== 'all' ? 'Try a different search term or section filter' : 'Try selecting a different module or check back later'}</p>
                </div>
            </td></tr>`;
        } else {
            let html = '';
            studentsToShow.forEach(student => {
                html += renderStudentRow(student);
            });
            tbody.innerHTML = html;
        }
        
        // Update count display
        const countDisplay = document.getElementById('student-count-display');
        if (countDisplay) {
            const showing = itemsPerPage === 0 ? totalItems : Math.min(endIndex, totalItems);
            const start = totalItems === 0 ? 0 : startIndex + 1;
            countDisplay.innerHTML = `
                <span class="font-medium text-gray-900">${showing === 0 ? 0 : start}-${showing}</span>
                <span class="text-gray-500"> of </span>
                <span class="font-medium text-gray-900">${totalItems}</span>
                <span class="text-gray-500"> students</span>
            `;
        }
        
        // Render pagination
        renderPagination(totalItems, currentPage, itemsPerPage);
    }
    
    // Function to update student performance table based on module filter
    async function updateStudentPerformanceTable(moduleId) {
        const tbody = document.getElementById('student-performance-tbody');
        if (!tbody) return;
        
        // Show loading state
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>';
        
        try {
            const url = moduleId === 'all' 
                ? 'database/get_dashboard_data.php'
                : `database/get_dashboard_data.php?student_performance_module_id=${moduleId}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
                return;
            }
            
            // Store all students data
            allStudentsData = data.student_performance || [];
            
            // Reset to first page and clear search
            currentPage = 1;
            currentSearchTerm = '';
            const searchInput = document.getElementById('student-search-input');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Render table
            renderTable();
        } catch (error) {
            console.error('Error updating student performance table:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
        }
    }

    // Initialize charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        
        // Initialize student performance table with default "All Modules"
        updateStudentPerformanceTable('all');
        
        // Add event listener for the correlation chart dropdown
        const correlationFilter = document.getElementById('correlation-module-filter');
        if (correlationFilter) {
            correlationFilter.addEventListener('change', function() {
                renderCorrelationChart(this.value);
            });
        }
        
        // Add event listener for the average score by gender module filter dropdown
        const avgScoreFilter = document.getElementById('avg-score-module-filter');
        if (avgScoreFilter) {
            avgScoreFilter.addEventListener('change', function() {
                renderAvgScoreChart(this.value);
            });
        }
        
        // Add event listener for the student performance module filter dropdown
        const studentPerformanceFilter = document.getElementById('student-performance-module-filter');
        if (studentPerformanceFilter) {
            studentPerformanceFilter.addEventListener('change', function() {
                updateStudentPerformanceTable(this.value);
            });
        }
        
        // Add event listener for the student performance section filter dropdown
        const studentSectionFilter = document.getElementById('student-performance-section-filter');
        if (studentSectionFilter) {
            studentSectionFilter.addEventListener('change', function() {
                currentSectionFilter = this.value;
                currentPage = 1; // Reset to first page on filter change
                renderTable();
            });
        }
        
        // Add search functionality
        const searchInput = document.getElementById('student-search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                currentSearchTerm = this.value;
                
                searchTimeout = setTimeout(() => {
                    currentPage = 1; // Reset to first page on search
                    renderTable();
                }, 300);
            });
        }
        
        // Add items per page selector
        const itemsPerPageSelect = document.getElementById('student-items-per-page');
        if (itemsPerPageSelect) {
            itemsPerPageSelect.addEventListener('change', function() {
                itemsPerPage = parseInt(this.value) || 0;
                currentPage = 1; // Reset to first page
                renderTable();
            });
        }
        
        // Add pagination button handlers
        const prevBtn = document.getElementById('student-prev-btn');
        const nextBtn = document.getElementById('student-next-btn');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                const totalPages = itemsPerPage === 0 ? 1 : Math.ceil(filteredStudentsData.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });
        }

        // Check for new data every 10 seconds
        setInterval(checkForUpdates, 10000);
    });

    // Function to check if new data is available on the server
    async function checkForUpdates() {
        try {
            const response = await fetch('check_updates.php');
            const updateData = await response.json();

            if (updateData.error) {
                console.error('Update check failed:', updateData.error);
                return;
            }

            // If the server's last update time is newer than our current one, refresh the charts
            if (updateData.last_update && updateData.last_update > lastUpdateTimestamp) {
                console.log('New data detected. Refreshing dashboard...');
                await refreshDashboardData();
            }
        } catch (error) {
            console.error('Failed to check for updates:', error);
        }
    }

    // Function to render all charts with provided data (for window resize)
    function renderAllCharts(dashboardData) {
        if (!dashboardData) {
            console.warn('No dashboard data provided to renderAllCharts');
            return;
        }

        // Update global data
        globalDashboardData = dashboardData;

        // Resize existing charts (Chart.js handles responsive resizing automatically)
        if (genderChart) genderChart.resize();
        if (gazeChart) gazeChart.resize();
        if (timeToCompleteChart) timeToCompleteChart.resize();
        if (avgScoreByGenderChart) avgScoreByGenderChart.resize();
        if (focusScoreCorrelationChart) focusScoreCorrelationChart.resize();
        if (checkpointQuizResultsChart) checkpointQuizResultsChart.resize();
    }

    // Function to refresh all dashboard data and re-render charts
    async function refreshDashboardData() {
        const dashboardData = await fetchDashboardData();
        
        if (!dashboardData) {
            console.log('Failed to fetch updated dashboard data');
            return;
        }

        globalDashboardData = dashboardData;

        // Update the timestamp with the latest one from the new data
        lastUpdateTimestamp = dashboardData.last_update;

        // Destroy existing charts before re-rendering
        if (genderChart) genderChart.destroy();
        if (gazeChart) gazeChart.destroy();
        if (timeToCompleteChart) timeToCompleteChart.destroy();
        if (avgScoreByGenderChart) avgScoreByGenderChart.destroy();
        if (focusScoreCorrelationChart) focusScoreCorrelationChart.destroy();
        if (checkpointQuizResultsChart) checkpointQuizResultsChart.destroy();

        // Re-render all charts with new data
        // Note: This re-uses the logic from initializeCharts.
        // For simplicity, we are calling the main init function again.
        // A more advanced implementation might have separate update functions for each chart.
        await initializeCharts();
        
        // Re-render correlation chart with current filter selection
        const correlationFilter = document.getElementById('correlation-module-filter');
        if (correlationFilter) {
            await renderCorrelationChart(correlationFilter.value);
        }
        
        // Re-render avg score chart with current filter selection
        const avgScoreFilter = document.getElementById('avg-score-module-filter');
        if (avgScoreFilter) {
            await renderAvgScoreChart(avgScoreFilter.value);
        }
        
        console.log('Dashboard data refreshed at:', new Date().toLocaleTimeString());    
    }

    // Debounced resize handler to re-render charts
    let resizeTimeout;
    window.addEventListener('resize', () => {
        // Clear the previous timeout
        clearTimeout(resizeTimeout);
        // Set a new timeout to run after a short delay
        resizeTimeout = setTimeout(() => {
            console.log('Window resized. Re-rendering charts...');
            // Re-render charts using existing global data instead of re-fetching
            if (globalDashboardData) {
                renderAllCharts(globalDashboardData);
            } else {
                // Fallback if data was never fetched
                initializeStaticCharts();
            }
        }, 250); // 250ms delay
    });
</script>
        </main>
    </div>
    
    <script>
    // DOM Elements
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const toggleSidebarBtn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const navTexts = document.querySelectorAll('.nav-text');
    const backdrop = document.getElementById('backdrop');
    
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
        if (currentPage === 'Adashboard.php' || currentPage === '' || currentPage === '/') {
            dashboardItem.classList.add('active');
        } else if (currentPage === 'Amodule.php') {
            modulesItem.classList.add('active');
        } else if (currentPage === 'Amanagement.php') {
            assessmentsItem.classList.add('active');
        } else {
            // Default to dashboard if no match
            dashboardItem.classList.add('active');
        }
    }
    
    // Toggle sidebar collapse
    toggleSidebarBtn.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-collapsed');
        mainContent.classList.toggle('main-content-collapsed');
        if (sidebar.classList.contains('sidebar-collapsed')) {
            navTexts.forEach(text => text.classList.add('hidden'));
        } else {
            setTimeout(() => {
                navTexts.forEach(text => text.classList.remove('hidden'));
            }, 150); // Small delay for better animation
        }
    });
    
    // Check screen width and apply responsive design
    function checkScreenSize() {
        if (window.innerWidth < 768) {
            sidebar.classList.remove('sidebar-collapsed'); // Always keep full width for mobile
            mainContent.classList.remove('main-content-collapsed');
            sidebar.classList.remove('mobile-visible');
            backdrop.classList.remove('active');
        }
    }
    
    // Run on load and on resize
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize();
    
    // Call the function to set the active page on load
    setActivePage();
    
    // Mobile menu toggle
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-visible');
        backdrop.classList.toggle('active');
    });
    
    // Close sidebar when clicking on backdrop
    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('mobile-visible');
        backdrop.classList.remove('active');
    });
    
    // Make dashboard active by default
    if (!dashboardItem.classList.contains('active') && 
        !modulesItem.classList.contains('active') && 
        !assessmentsItem.classList.contains('active')) {
        dashboardItem.classList.add('active');
    }
</script>
</body>
</html>