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

// Add this right after database connection
// Check if last_accessed column exists before adding it
$check_column = $conn->query("SHOW COLUMNS FROM user_module_progress LIKE 'last_accessed'");
if (!$check_column || $check_column->num_rows == 0) {
    $table_check = "ALTER TABLE user_module_progress 
                    ADD COLUMN last_accessed 
                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                    ON UPDATE CURRENT_TIMESTAMP";
    $conn->query($table_check);
}

// Update the modules query
$modules_query = "
    SELECT 
        m.*,
        ump.completed_sections,
        ump.completed_checkpoint_quizzes,
        COALESCE(ump.last_accessed, m.created_at) as last_accessed,
        (SELECT COUNT(*) 
         FROM module_sections ms 
         JOIN module_parts mp ON ms.module_part_id = mp.id 
         WHERE mp.module_id = m.id) as total_sections,
        (SELECT COUNT(*) 
         FROM checkpoint_quizzes cq
         JOIN module_parts mp ON cq.module_part_id = mp.id 
         WHERE mp.module_id = m.id) as total_checkpoint_quizzes,
        (
            EXISTS (
                SELECT 1 
                FROM quiz_results qr 
                WHERE qr.user_id = ? 
                  AND qr.module_id = m.id
            ) 
            OR EXISTS (
                SELECT 1 
                FROM retake_results rr2 
                WHERE rr2.user_id = ? 
                  AND rr2.module_id = m.id
            )
        ) as has_completed_final_quiz
    FROM modules m
    LEFT JOIN user_module_progress ump ON m.id = ump.module_id AND ump.user_id = ?
    WHERE m.status = 'published'
    ORDER BY COALESCE(ump.last_accessed, m.created_at) DESC, m.created_at DESC
";

$stmt = $conn->prepare($modules_query);
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$in_progress_modules = [];
$completed_modules = [];
$available_modules = [];

while ($module = $result->fetch_assoc()) {
    // Calculate progress including both sections and checkpoint quizzes
    $completed_sections = json_decode($module['completed_sections'] ?? '[]', true);
    $completed_sections = is_array($completed_sections) ? $completed_sections : [];
    
    $completed_checkpoint_quizzes = json_decode($module['completed_checkpoint_quizzes'] ?? '[]', true);
    $completed_checkpoint_quizzes = is_array($completed_checkpoint_quizzes) ? $completed_checkpoint_quizzes : [];
    
    $total_sections = intval($module['total_sections'] ?? 0);
    $total_checkpoint_quizzes = intval($module['total_checkpoint_quizzes'] ?? 0);
    
    // Calculate total items and completed items
    $total_items = $total_sections + $total_checkpoint_quizzes;
    $completed_items = count($completed_sections) + count($completed_checkpoint_quizzes);
    
    // Calculate progress percentage (0-100)
    if ($total_items > 0) {
        $progress = round(($completed_items / $total_items) * 100);
        // Ensure progress is between 0 and 100
        $progress = max(0, min(100, $progress));
    } else {
        $progress = 0;
    }

    $module['has_completed_final_quiz'] = !empty($module['has_completed_final_quiz']);
    $module['quiz_status_text'] = $module['has_completed_final_quiz'] ? 'Quiz Completed' : 'Awaiting Quiz';
    $module['quiz_status_class'] = $module['has_completed_final_quiz'] ? 'text-green-600' : 'text-yellow-600';
    
    // Add progress info to module
    $module['progress'] = $progress;
    $module['completed_count'] = $completed_items;
    $module['total_count'] = $total_items;
    
    // Sort into appropriate array
    // Module is available if no progress record exists
    // Module is in progress if progress exists but is less than 100%
    // Module is completed if progress is 100% (all sections and checkpoint quizzes completed)
    if ($module['completed_sections'] !== null || $module['completed_checkpoint_quizzes'] !== null) {
        if ($progress >= 100) {
            $completed_modules[] = $module;
        } else {
            $in_progress_modules[] = $module;
        }
    } else {
        $available_modules[] = $module;
    }
}

// Handle module start action
if (isset($_POST['start_module'])) {
    $module_id = intval($_POST['module_id']);
    
    // Insert initial progress record with current timestamp
    $stmt = $conn->prepare("
        INSERT INTO user_module_progress 
            (user_id, module_id, completed_sections, last_accessed) 
        VALUES 
            (?, ?, '[]', NOW())
        ON DUPLICATE KEY UPDATE 
            last_accessed = NOW()
    ");
    
    $stmt->bind_param("ii", $user_id, $module_id);
    $stmt->execute();
    
    // Redirect to module content
    header("Location: Smodulepart.php?module_id=" . $module_id);
    exit();
}

// Add this function after database connection
function timeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    
    $diff = time() - strtotime($timestamp);
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = round($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EyeLearn - AI-Enhanced E-Learning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
  
            <!-- Enhanced CV Eye Tracking System -->
        <script src="js/cv-eye-tracking.js?service_reconnect_<?php echo time(); ?>"></script>
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
            pointer-events: auto;
        }        .profile-dropdown.show {
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
                
                <!-- Dropdown menu -->
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
    <!-- Live Feed Box (Hidden by default, shown when tracking) -->
    <div id="live-feed-container" style="position: fixed; top: 80px; right: 20px; z-index: 1000; background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); display: none;">
        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #3B82F6;">Live Camera Feed</h4>
        <img id="tracking-video" alt="Camera feed will appear here" style="width: 320px; height: 240px; background-color: #000; border-radius: 4px;">
        <button onclick="stopModuleTracking()" style="width: 100%; margin-top: 8px; padding: 4px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Stop Tracking</button>
    </div>
    
    <div class="container mx-auto px-2">
        <div class="flex justify-between items-center mb-3 border-b border-gray-200 pb-2">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Learning Modules</h1>
        </div>

        <!-- In Progress Section -->
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                In Progress
            </h2>
            
            <!-- Module Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php if (!empty($in_progress_modules)): ?>
                    <?php foreach ($in_progress_modules as $module): ?>
                        <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 hover:shadow-md transition-shadow duration-300">
                        <div class="flex justify-between items-start mb-2 gap-2 border-b border-gray-200 pb-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($module['title']); ?></h3>
                            <span class="text-sm font-medium <?php echo $module['quiz_status_class']; ?>">
                                <?php echo $module['quiz_status_text']; ?>
                            </span>
                        </div>
                            
                            <!-- Add module image display -->
                            <?php if (!empty($module['image_path'])): ?>
                                <div class="relative h-32 mb-3 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($module['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($module['title']); ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Progress</span>
                                    <span class="font-medium"><?php echo number_format($module['progress'], 0); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-primary h-1.5 rounded-full transition-all duration-300" style="width: <?php echo max(0, min(100, $module['progress'])); ?>%"></div>
                                </div>
                                <?php if (isset($module['completed_count']) && isset($module['total_count'])): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo $module['completed_count']; ?> of <?php echo $module['total_count']; ?> items completed
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-500 mb-3 border-t border-gray-200 pt-2">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Last accessed: <?php echo timeAgo($module['last_accessed']); ?></span>
                            </div>
                            <form action="Smodulepart.php" method="get" class="w-full">
                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                <button class="w-full bg-secondary text-white py-2.5 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center font-medium">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    </svg>
                                    Continue Learning
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full p-4 bg-gray-50 rounded-md text-center border border-gray-200">
                        <p class="text-sm text-gray-500">No modules in progress. Start learning by selecting a module below!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completed Modules Section -->
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Completed Modules
            </h2>
            
            <!-- Completed Module Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php if (!empty($completed_modules)): ?>
                    <?php foreach ($completed_modules as $module): ?>
                        <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 hover:shadow-md transition-shadow duration-300">
                        <div class="flex justify-between items-start mb-2 gap-2 border-b border-gray-200 pb-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($module['title']); ?></h3>
                            <span class="text-sm font-medium <?php echo $module['quiz_status_class']; ?>">
                                <?php echo $module['quiz_status_text']; ?>
                            </span>
                        </div>
                            
                            <!-- Add module image display -->
                            <?php if (!empty($module['image_path'])): ?>
                                <div class="relative h-32 mb-3 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($module['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($module['title']); ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Completed</span>
                                    <span class="font-medium text-green-600">100%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-green-600 h-1.5 rounded-full w-full"></div>
                                </div>
                            </div>
                            <div class="flex items-center text-sm text-gray-500 mb-3 border-t border-gray-200 pt-2">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Last accessed: <?php echo timeAgo($module['last_accessed']); ?></span>
                            </div>
                            <form action="Smodulepart.php" method="get" class="w-full">
                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                <button class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center font-medium">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Review Module
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full p-4 bg-gray-50 rounded-md text-center border border-gray-200">
                        <p class="text-sm text-gray-500">No completed modules yet. Keep learning!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Modules -->
        <div class="mb-4">
    <h2 class="text-lg font-semibold mb-3 text-gray-900 flex items-center border-b border-gray-200 pb-2">
        <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Available Modules
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($available_modules as $module): ?>
            <div class="bg-white border border-gray-200 shadow-sm rounded-md hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col">
                <?php if (!empty($module['image_path'])) : ?>
                    <div class="relative h-40 overflow-hidden border-b border-gray-200">
                        <img src="<?php echo htmlspecialchars($module['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($module['title']); ?>" 
                             class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                    </div>
                <?php endif; ?>
                
                <div class="p-4 flex flex-col flex-grow">
                    <div class="flex justify-between items-start mb-2 gap-2 border-b border-gray-200 pb-2">
                        <h3 class="text-base font-semibold text-gray-900 mb-2 line-clamp-2 flex-1">
                            <?php echo htmlspecialchars($module['title']); ?>
                        </h3>
                        <span class="text-sm font-medium <?php echo $module['quiz_status_class']; ?> flex-shrink-0">
                            <?php echo $module['quiz_status_text']; ?>
                        </span>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-3 line-clamp-3 flex-grow">
                        <?php echo htmlspecialchars($module['description']); ?>
                    </p>
                    
                    <form method="post" class="w-full">
                        <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                        <input type="hidden" name="start_module" value="1">
                        <button type="submit" class="w-full bg-secondary text-white py-2.5 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center font-medium group">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            </svg>
                            Start Learning
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
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

    <!-- Main Content -->
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
    const profileModalEl = document.getElementById('profileModal');

    const sidebarEl = document.getElementById('sidebar');
    const mainContentEl = document.getElementById('main-content');
    const backdropEl = document.getElementById('backdrop');

    // State management
    let isSidebarVisible = true;
    const isMobile = () => window.innerWidth < 768;

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
            if (!profileModalEl) {
                console.error('Profile modal element not found');
                alert('Profile modal not found. Please refresh the page.');
                return;
            }
            
            const modalContent = document.getElementById('profileModalContent');
            
            // Show modal first
            profileModalEl.classList.remove('hidden');
            if (profileDropdown) {
                profileDropdown.classList.remove('show');
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
            if (!profileModalEl) return;
            
            const modalContent = document.getElementById('profileModalContent');
            
            // Animate out
            if (modalContent) {
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
            }
            
            // Hide after animation
            setTimeout(() => {
                profileModalEl.classList.add('hidden');
            }, 200);
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
        if (profileModalEl) {
            profileModalEl.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeProfileModal();
                }
            });
        }

        // Add this function near your other JavaScript code
function handleLogout(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}
    </script>
</body>
</html>