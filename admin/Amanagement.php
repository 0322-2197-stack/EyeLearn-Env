<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
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
        
        /* Modal overlay styling */
        #studentModal {
            backdrop-filter: blur(2px);
            z-index: 9999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #studentModal.hidden {
            display: none !important;
        }
        
        #studentModal:not(.hidden) {
            display: flex !important;
        }
        
        /* Modal content scrolling */
        #studentModal .bg-white {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        
        #studentModal .bg-white::-webkit-scrollbar {
            width: 8px;
        }
        
        #studentModal .bg-white::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        
        #studentModal .bg-white::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        
        #studentModal .bg-white::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* Disable pointer events on elements behind modal */
        body.modal-open {
            overflow: hidden;
        }
        
        body.modal-open #sidebar {
            pointer-events: none !important;
            user-select: none !important;
            filter: brightness(0.7) blur(2px) !important;
        }
        
        body.modal-open .main-content {
            pointer-events: none !important;
            user-select: none !important;
            filter: brightness(0.7) blur(2px) !important;
        }
        
        body.modal-open #sidebar .nav-item {
            pointer-events: none !important;
        }
        
        body.modal-open #sidebar .nav-item:hover {
            background-color: transparent !important;
        }
        
        /* Completely disable all hover effects when modal is open */
        body.modal-open * {
            pointer-events: none !important;
        }
        
        body.modal-open #studentModal,
        body.modal-open #studentModal * {
            pointer-events: auto !important;
        }
        
        /* Override any existing hover states */
        body.modal-open .nav-item,
        body.modal-open .nav-item:hover,
        body.modal-open .nav-item:focus,
        body.modal-open .nav-item:active {
            background-color: transparent !important;
            color: inherit !important;
            transform: none !important;
            box-shadow: none !important;
        }
        
        body.modal-open .nav-item .nav-indicator {
            opacity: 0 !important;
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
        
        /* Table styling */
        .student-table-container {
            overflow-x: auto;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            max-width: 100%;
        }
        
        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            table-layout: auto;
        }
        
        .student-table thead {
            background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .student-table thead th {
            padding: 0.75rem 0.875rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }
        
        .student-table tbody tr {
            transition: all 0.15s ease-in-out;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .student-table tbody tr:hover {
            background-color: #f9fafb;
            transform: scale(1.001);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        
        .student-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .student-table tbody td {
            padding: 0.75rem 0.875rem;
            vertical-align: middle;
            color: #1f2937;
            font-size: 0.8125rem;
        }
        
        .student-table .avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        
        .student-table .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.6875rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .student-table .progress-container {
            min-width: 90px;
            max-width: 110px;
        }
        
        .student-table .progress-bar {
            height: 0.375rem;
            border-radius: 9999px;
            overflow: hidden;
            background-color: #e5e7eb;
        }
        
        .student-table .progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }
        
        .student-table .action-button {
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .student-table .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .student-table .action-button:active {
            transform: translateY(0);
        }
        
        .student-table .focus-time-display {
            min-width: 110px;
            max-width: 130px;
        }
        
        .student-table .sessions-display {
            min-width: 80px;
            max-width: 100px;
        }
        
        .student-table .today-focus-display {
            min-width: 120px;
            max-width: 140px;
        }
        
        .student-table th:nth-child(1),
        .student-table td:nth-child(1) {
            min-width: 160px;
            max-width: 200px;
        }
        
        .student-table th:nth-child(2),
        .student-table td:nth-child(2) {
            min-width: 100px;
            max-width: 120px;
        }
        
        .student-table th:nth-child(3),
        .student-table td:nth-child(3) {
            min-width: 80px;
            max-width: 100px;
        }
        
        .student-table th:nth-child(4),
        .student-table td:nth-child(4) {
            min-width: 150px;
            max-width: 200px;
        }
        
        .student-table th:nth-child(9),
        .student-table td:nth-child(9) {
            min-width: 100px;
            max-width: 120px;
        }
        
        .student-table .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6b7280;
        }
        
        .student-table .empty-state svg {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            opacity: 0.5;
        }
        
        /* Responsive behavior */
        @media (max-width: 1024px) {
            .student-table th:nth-child(1),
            .student-table td:nth-child(1) {
                min-width: 140px;
                max-width: 180px;
            }
            
            .student-table .focus-time-display,
            .student-table .sessions-display,
            .student-table .today-focus-display {
                min-width: 90px;
                max-width: 110px;
            }
        }
        
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
            
            .student-table thead th,
            .student-table tbody td {
                padding: 0.5rem 0.375rem;
                font-size: 0.75rem;
            }
            
            .student-table .avatar {
                width: 1.75rem;
                height: 1.75rem;
                font-size: 0.6875rem;
            }
            
            .student-table .action-button {
                padding: 0.25rem 0.5rem;
                font-size: 0.6875rem;
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

            <!-- Student Details Modal -->
            <div id="studentModal" class="fixed inset-0 hidden z-[9999]" style="background-color: rgba(0, 0, 0, 0.75); display: none;">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative" onclick="event.stopPropagation()">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-6 sticky top-0 bg-white z-10 pb-4 border-b">
                                <h2 class="text-2xl font-bold text-gray-800" id="modalStudentName">Student Details</h2>
                                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full p-2 transition-colors text-2xl font-bold w-10 h-10 flex items-center justify-center" title="Close">
                                    &times;
                                </button>
                            </div>
                            
                            <!-- Student Info -->
                            <div class="mb-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-lg mb-2">Student Information</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Student ID</p>
                                            <p class="font-medium" id="modalStudentId">-</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Email</p>
                                            <p class="font-medium" id="modalStudentEmail">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Today's Performance -->
                            <div class="mb-6">
                                <h3 class="font-semibold text-lg mb-4">Today's Performance</h3>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-green-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-green-700">Focused Time</p>
                                        <p class="text-2xl font-bold text-green-600" id="modalTodayFocused">0 min</p>
                                    </div>
                                    <div class="bg-red-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-red-700">Unfocused Time</p>
                                        <p class="text-2xl font-bold text-red-600" id="modalTodayUnfocused">0 min</p>
                                    </div>
                                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-blue-700">Focus Percentage</p>
                                        <p class="text-2xl font-bold text-blue-600" id="modalTodayPercent">0%</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="bg-gray-200 rounded-full h-3">
                                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" id="modalTodayBar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Weekly Performance -->
                            <div class="mb-6">
                                <h3 class="font-semibold text-lg mb-4">Weekly Performance</h3>
                                <div class="grid grid-cols-4 gap-4">
                                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-indigo-700">Total Focus</p>
                                        <p class="text-xl font-bold text-indigo-600" id="modalWeeklyFocused">0 min</p>
                                    </div>
                                    <div class="bg-orange-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-orange-700">Total Sessions</p>
                                        <p class="text-xl font-bold text-orange-600" id="modalWeeklySessions">0</p>
                                    </div>
                                    <div class="bg-emerald-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-emerald-700">Focus %</p>
                                        <p class="text-xl font-bold text-emerald-600" id="modalWeeklyPercent">0%</p>
                                    </div>
                                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                                        <p class="text-sm font-medium text-purple-700">Active Days</p>
                                        <p class="text-xl font-bold text-purple-600" id="modalActiveDays">0</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Insights -->
                            <div class="mb-6">
                                <h3 class="font-semibold text-lg mb-4">Insights</h3>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <ul class="space-y-2" id="modalInsights">
                                        <li>Loading insights...</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Quiz History -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="font-semibold text-lg">Quiz History</h3>
                                    <select id="quizModuleFilter" class="p-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">All Modules</option>
                                    </select>
                                </div>
                                <div id="quizHistoryContainer" class="space-y-3">
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                                        <p class="text-gray-500">Loading quiz history...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
   
         <!-- Main Content -->
         <main id="main-content" class="main-content flex-1 p-4 transition-all duration-300">
            <div class="container mx-auto max-w-full">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-primary">Student Performance Overview</h1>
                </div>
                
                <!-- Search and Filter -->
                <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 relative">
                            <input 
                                type="text" 
                                id="searchInput" 
                                placeholder="Search by name, email, or student ID..." 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm"
                            >
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <select 
                            id="moduleFilter" 
                            class="w-full sm:w-auto sm:min-w-[200px] px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm bg-white cursor-pointer"
                        >
                            <option value="">All Modules</option>
                        </select>
                        <select 
                            id="sectionFilter" 
                            class="w-full sm:w-auto sm:min-w-[180px] px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm bg-white cursor-pointer"
                        >
                            <option value="">All Sections</option>
                        </select>
                    </div>
                </div>

                <!-- Student Table -->
                <div class="student-table-container bg-white">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Gender</th>
                                <th>Email</th>
                                <th>Progress</th>
                                <th>Focus Time</th>
                                <th>Total Sessions</th>
                                <th>Today's Focus</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="mt-2 text-sm font-medium">Loading students...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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

    // Student Management Functionality
    let allStudents = [];
    let allQuizHistory = []; // Store all quiz history for filtering

    // Load modules for filter dropdown
    async function loadModules() {
        try {
            const response = await fetch('database/get_modules_for_filter.php');
            const data = await response.json();
            
            if (data.success) {
                const moduleFilter = document.getElementById('moduleFilter');
                const currentValue = moduleFilter.value;
                
                // Clear existing options except "All Modules"
                while (moduleFilter.children.length > 1) {
                    moduleFilter.removeChild(moduleFilter.lastChild);
                }
                
                // Add modules to dropdown
                data.modules.forEach(module => {
                    const option = document.createElement('option');
                    option.value = module.id;
                    option.textContent = module.title;
                    moduleFilter.appendChild(option);
                });
                
                // Restore previous selection if it still exists
                if (currentValue) {
                    moduleFilter.value = currentValue;
                }
            } else {
                console.error('Error loading modules:', data.error);
            }
        } catch (error) {
            console.error('Error loading modules:', error);
        }
    }

    // Load sections for filter dropdown
    async function loadSections() {
        try {
            const response = await fetch('database/get_sections.php');
            const data = await response.json();
            
            if (data.success && data.has_section_column) {
                const sectionFilter = document.getElementById('sectionFilter');
                const currentValue = sectionFilter.value;
                
                // Clear existing options except "All Sections"
                while (sectionFilter.children.length > 1) {
                    sectionFilter.removeChild(sectionFilter.lastChild);
                }
                
                // Add sections to dropdown
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionFilter.appendChild(option);
                });
                
                // Restore previous selection if it still exists
                if (currentValue) {
                    sectionFilter.value = currentValue;
                }
            } else {
                console.error('Error loading sections:', data.error || 'Section column not available');
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    }

    async function loadStudents() {
        try {
            const searchInput = document.getElementById('searchInput');
            const moduleFilter = document.getElementById('moduleFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            
            const search = searchInput ? searchInput.value.trim() : '';
            const moduleId = moduleFilter ? moduleFilter.value : '';
            const section = sectionFilter ? sectionFilter.value : '';
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (moduleId) params.append('module_id', moduleId);
            if (section) params.append('section', section);
            
            const response = await fetch(`database/students_minimal.php?${params.toString()}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch students');
            }
            
            const data = await response.json();
            
            if (data.success) {
                allStudents = data.students;
                displayStudents(allStudents);
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Error loading students:', error);
            alert('Error loading students: ' + error.message);
        }
    }

    function displayStudents(students) {
        const tbody = document.querySelector('table tbody');
        if (!tbody) return;

        if (students.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="mt-2 text-sm font-medium text-gray-600">No students found</p>
                            <p class="mt-1 text-xs text-gray-500">Try adjusting your search or filter criteria</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = students.map(student => {
            // Ensure progress is capped at 100%
            const progress = Math.min(100, Math.max(0, parseFloat(student.progress) || 0));
            const progressColor = progress >= 80 ? 'bg-green-500' : (progress >= 60 ? 'bg-yellow-500' : 'bg-red-500');
            const genderBadgeColor = student.gender === 'Male' ? 'bg-blue-100 text-blue-800' : (student.gender === 'Female' ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-800');
            
            return `
            <tr>
                <td>
                    <div class="flex items-center space-x-2">
                        <div class="avatar bg-gradient-to-br from-primary to-blue-600 text-white shadow-sm">
                            ${getInitials(student.name)}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 truncate text-sm">${student.name}</p>
                            <p class="text-xs text-gray-500">${student.student_id}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-indigo-100 text-indigo-800">${student.section || 'N/A'}</span>
                </td>
                <td>
                    <span class="badge ${genderBadgeColor}">${student.gender || 'N/A'}</span>
                </td>
                <td>
                    <p class="text-xs text-gray-700 truncate" title="${student.email}">${student.email}</p>
                </td>
                <td>
                    <div class="progress-container">
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-xs font-semibold text-gray-700">${progress}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill ${progressColor}" style="width: ${progress}%"></div>
                        </div>
                    </div>
                </td>
                <td class="focus-time-display">
                    ${student.avg_focus_time_minutes !== null && student.avg_focus_time_minutes !== undefined && student.avg_focus_time_minutes > 0
                        ? `<div class="space-y-0.5">
                            <div class="text-xs font-semibold text-gray-900">${student.avg_focus_time_minutes} min</div>
                            ${student.avg_focus_time_per_session_minutes !== null && student.avg_focus_time_per_session_minutes !== undefined && student.avg_focus_time_per_session_minutes > 0 
                                ? `<div class="text-xs font-medium text-blue-600">${student.avg_focus_time_per_session_minutes}/session</div>` 
                                : ''}
                            <div class="text-xs text-gray-500">${student.valid_sessions || 0} valid</div>
                           </div>`
                        : '<span class="badge bg-gray-100 text-gray-500">No data</span>'}
                </td>
                <td class="sessions-display">
                    <div class="space-y-0.5">
                        <div class="text-xs font-semibold text-gray-900">${student.total_sessions_overall || 0}</div>
                        <div class="text-xs text-gray-500">${student.valid_sessions || 0} valid</div>
                    </div>
                </td>
                <td class="today-focus-display">
                    <div class="space-y-1">
                        <div class="flex items-center justify-center space-x-1.5">
                            <span class="text-sm font-bold text-green-600">${student.today_focus_time}m</span>
                            <span class="text-xs text-gray-400">/</span>
                            <span class="text-sm font-bold text-red-500">${student.today_unfocus_time}m</span>
                        </div>
                        <div class="progress-bar bg-red-100">
                            <div class="progress-fill bg-green-500" style="width: ${student.today_focus_percentage}%"></div>
                        </div>
                        <p class="text-xs text-center text-gray-600">${student.today_focus_percentage}%</p>
                    </div>
                </td>
                <td>
                    <button onclick="viewStudent(${student.id})" class="action-button bg-primary text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        View
                    </button>
                </td>
            </tr>
        `;
        }).join('');
    }

    function getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }

    function viewStudent(studentId) {
        openModal(studentId);
    }

    async function openModal(studentId) {
        const modal = document.getElementById('studentModal');
        const sidebar = document.getElementById('sidebar');
        
        // Reset modal content to default/loading state
        resetModalContent();
        
        // Add classes for complete isolation
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        sidebar.style.pointerEvents = 'none';
        sidebar.style.userSelect = 'none';
        
        // Disable all navigation items
        const navItems = sidebar.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.style.pointerEvents = 'none';
            item.style.userSelect = 'none';
        });
        
        // Show loading state
        document.getElementById('modalStudentName').textContent = 'Loading...';
        document.getElementById('modalStudentId').textContent = '-';
        document.getElementById('modalStudentEmail').textContent = '-';
        
        // Reset all performance metrics
        document.getElementById('modalTodayFocused').textContent = '0 min';
        document.getElementById('modalTodayUnfocused').textContent = '0 min';
        document.getElementById('modalTodayPercent').textContent = '0%';
        document.getElementById('modalTodayBar').style.width = '0%';
        document.getElementById('modalWeeklyFocused').textContent = '0 min';
        document.getElementById('modalWeeklySessions').textContent = '0';
        document.getElementById('modalWeeklyPercent').textContent = '0%';
        document.getElementById('modalActiveDays').textContent = '0';
        document.getElementById('modalInsights').innerHTML = '<li>Loading insights...</li>';
        
        // Show loading state for quiz history
        document.getElementById('quizHistoryContainer').innerHTML = `
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <p class="text-gray-500">Loading quiz history...</p>
            </div>
        `;
        
        try {
            const response = await fetch(`database/student_details_minimal.php?student_id=${studentId}`);
            const data = await response.json();
            
            if (data.success) {
                updateModalData(data);
                loadQuizHistory(studentId);
            } else {
                throw new Error(data.error || 'Failed to load student details');
            }
        } catch (error) {
            console.error('Error loading student details:', error);
            document.getElementById('modalStudentName').textContent = 'Error Loading Details';
            document.getElementById('modalInsights').innerHTML = `<li class="text-red-600">Error: ${error.message}</li>`;
            document.getElementById('quizHistoryContainer').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-600">Failed to load quiz history</p>
                </div>
            `;
        }
    }
    
    function resetModalContent() {
        // Reset all modal fields to default values
        document.getElementById('modalStudentName').textContent = 'Student Details';
        document.getElementById('modalStudentId').textContent = '-';
        document.getElementById('modalStudentEmail').textContent = '-';
        document.getElementById('modalTodayFocused').textContent = '0 min';
        document.getElementById('modalTodayUnfocused').textContent = '0 min';
        document.getElementById('modalTodayPercent').textContent = '0%';
        document.getElementById('modalTodayBar').style.width = '0%';
        document.getElementById('modalWeeklyFocused').textContent = '0 min';
        document.getElementById('modalWeeklySessions').textContent = '0';
        document.getElementById('modalWeeklyPercent').textContent = '0%';
        document.getElementById('modalActiveDays').textContent = '0';
        document.getElementById('modalInsights').innerHTML = '<li>Loading insights...</li>';
        document.getElementById('quizHistoryContainer').innerHTML = `
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <p class="text-gray-500">Loading quiz history...</p>
            </div>
        `;
        
        // Reset quiz history filter
        const filterDropdown = document.getElementById('quizModuleFilter');
        if (filterDropdown) {
            filterDropdown.value = '';
            // Clear module options except "All Modules"
            while (filterDropdown.children.length > 1) {
                filterDropdown.removeChild(filterDropdown.lastChild);
            }
        }
        allQuizHistory = [];
    }

    function updateModalData(data) {
        const { student, today, weekly, insights } = data;
        
        // Update student info
        document.getElementById('modalStudentName').textContent = student.name;
        document.getElementById('modalStudentId').textContent = student.student_id;
        document.getElementById('modalStudentEmail').textContent = student.email;
        
        // Update today's data
        document.getElementById('modalTodayFocused').textContent = today.focused_time + ' min';
        document.getElementById('modalTodayUnfocused').textContent = today.unfocused_time + ' min';
        document.getElementById('modalTodayPercent').textContent = today.focus_percentage + '%';
        document.getElementById('modalTodayBar').style.width = today.focus_percentage + '%';
        
        // Update weekly data
        document.getElementById('modalWeeklyFocused').textContent = weekly.focused_time + ' min';
        document.getElementById('modalWeeklySessions').textContent = weekly.total_sessions;
        document.getElementById('modalWeeklyPercent').textContent = weekly.focus_percentage + '%';
        document.getElementById('modalActiveDays').textContent = weekly.active_days;
        
        // Update insights
        const insightsList = document.getElementById('modalInsights');
        insightsList.innerHTML = insights.map(insight => `<li>${insight}</li>`).join('');
    }

    async function loadQuizHistory(studentId) {
        try {
            const response = await fetch(`database/get_quiz_history.php?student_id=${studentId}`);
            const data = await response.json();
            
            if (data.success) {
                allQuizHistory = data.quiz_history; // Store all quiz history
                populateModuleFilter(data.quiz_history);
                filterQuizHistory();
            } else {
                console.error('Error loading quiz history:', data.error);
                allQuizHistory = [];
                displayQuizHistory([]);
            }
        } catch (error) {
            console.error('Error loading quiz history:', error);
            allQuizHistory = [];
            displayQuizHistory([]);
        }
    }

    function populateModuleFilter(quizHistory) {
        const filterDropdown = document.getElementById('quizModuleFilter');
        if (!filterDropdown) return;

        // Clear existing options except "All Modules"
        while (filterDropdown.children.length > 1) {
            filterDropdown.removeChild(filterDropdown.lastChild);
        }

        // Extract unique modules
        const uniqueModules = [];
        const moduleMap = new Map();

        quizHistory.forEach(quiz => {
            if (!moduleMap.has(quiz.module_id)) {
                moduleMap.set(quiz.module_id, quiz.module_name);
                uniqueModules.push({
                    id: quiz.module_id,
                    name: quiz.module_name
                });
            }
        });

        // Sort modules alphabetically by name
        uniqueModules.sort((a, b) => a.name.localeCompare(b.name));

        // Add modules to dropdown
        uniqueModules.forEach(module => {
            const option = document.createElement('option');
            option.value = module.id;
            option.textContent = module.name;
            filterDropdown.appendChild(option);
        });
    }

    function filterQuizHistory() {
        const filterDropdown = document.getElementById('quizModuleFilter');
        if (!filterDropdown) {
            displayQuizHistory(allQuizHistory);
            return;
        }

        const selectedModuleId = filterDropdown.value;

        if (!selectedModuleId) {
            // Show all quiz history
            displayQuizHistory(allQuizHistory);
        } else {
            // Filter by module ID
            const filteredHistory = allQuizHistory.filter(quiz => quiz.module_id == selectedModuleId);
            displayQuizHistory(filteredHistory);
        }
    }

    function getOrdinalLabel(number) {
        if (!number) return '';
        const suffix = number % 100 >= 11 && number % 100 <= 13 ? 'th' :
                      number % 10 === 1 ? 'st' :
                      number % 10 === 2 ? 'nd' :
                      number % 10 === 3 ? 'rd' : 'th';
        return number + suffix;
    }

    function displayQuizHistory(quizHistory) {
        const container = document.getElementById('quizHistoryContainer');
        
        if (!container) {
            console.error('Quiz history container not found');
            return;
        }
        
        if (!quizHistory || quizHistory.length === 0) {
            container.innerHTML = `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <p class="text-gray-500">No quiz history available</p>
                </div>
            `;
            return;
        }
        
        const statusColors = {
            'Passed': 'bg-green-100 text-green-800 border-green-300',
            'Failed': 'bg-red-100 text-red-800 border-red-300'
        };
        
        container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="w-full border-collapse bg-white rounded-lg shadow-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left text-sm font-semibold text-gray-700 border-b">Module Name</th>
                            <th class="p-3 text-left text-sm font-semibold text-gray-700 border-b">Completion Date</th>
                            <th class="p-3 text-center text-sm font-semibold text-gray-700 border-b">Score</th>
                            <th class="p-3 text-center text-sm font-semibold text-gray-700 border-b">Attempts</th>
                            <th class="p-3 text-left text-sm font-semibold text-gray-700 border-b">Status</th>
                            <th class="p-3 text-center text-sm font-semibold text-gray-700 border-b">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${quizHistory.map(quiz => {
                            const statusColor = statusColors[quiz.status] || statusColors['Failed'];
                            const completionDate = new Date(quiz.completion_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const percentageColor = quiz.percentage >= 70 ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                            
                            return `
                                <tr class="border-b hover:bg-gray-50 transition-colors">
                                    <td class="p-3 text-sm text-gray-800 font-medium">${quiz.module_name}</td>
                                    <td class="p-3 text-sm text-gray-700">${completionDate}</td>
                                    <td class="p-3 text-center text-sm text-gray-700">${quiz.score} / ${quiz.total_questions}</td>
                                    <td class="p-3 text-center text-sm text-gray-700">${quiz.attempt_label || getOrdinalLabel(quiz.attempts)}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor} border inline-block">
                                            ${quiz.status}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center text-sm ${percentageColor}">${quiz.percentage}%</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function closeModal() {
        const modal = document.getElementById('studentModal');
        const sidebar = document.getElementById('sidebar');
        
        // Hide modal
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        
        // Re-enable sidebar interactions
        sidebar.style.pointerEvents = '';
        sidebar.style.userSelect = '';
        
        // Re-enable all navigation items
        const navItems = sidebar.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.style.pointerEvents = '';
            item.style.userSelect = '';
        });
        
        // Reset modal content after a short delay to allow animation
        setTimeout(() => {
            resetModalContent();
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('studentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('studentModal').classList.contains('hidden')) {
            closeModal();
        }
    });

    // Set up search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const moduleFilter = document.getElementById('moduleFilter');
    const sectionFilter = document.getElementById('sectionFilter');
    const quizModuleFilter = document.getElementById('quizModuleFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', loadStudents);
    }
    
    if (moduleFilter) {
        moduleFilter.addEventListener('change', loadStudents);
    }
    
    if (sectionFilter) {
        sectionFilter.addEventListener('change', loadStudents);
    }

    // Set up quiz history module filter
    if (quizModuleFilter) {
        quizModuleFilter.addEventListener('change', filterQuizHistory);
    }

    // Load modules, sections, and students on page load
    loadModules();
    loadSections();
    loadStudents();
</script>
</body>
</html>