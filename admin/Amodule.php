
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
if (isset($_GET['delete']) && $_GET['delete'] === 'success') {
    echo '<div class="bg-green-100 text-green-800 px-3 py-2 rounded-md mb-3 border border-green-200 text-sm">Module deleted successfully.</div>';
}

if (!function_exists('ensureFinalQuizRetakeColumn')) {
    function ensureFinalQuizRetakeColumn(mysqli $connection): void
    {
        $columnResult = $connection->query("SHOW COLUMNS FROM final_quizzes LIKE 'allow_retake'");
        if ($columnResult && $columnResult->num_rows === 0) {
            $connection->query("ALTER TABLE final_quizzes ADD COLUMN allow_retake TINYINT(1) NOT NULL DEFAULT 0");
        }

        if ($columnResult instanceof mysqli_result) {
            $columnResult->free();
        }
    }
}

if (isset($_GET['retake'])) {
    if ($_GET['retake'] === 'enabled') {
        echo '<div class="bg-green-100 text-green-800 px-3 py-2 rounded-md mb-3 border border-green-200 text-sm">Final quiz retake enabled for this module.</div>';
    } elseif ($_GET['retake'] === 'disabled') {
        echo '<div class="bg-green-100 text-green-800 px-3 py-2 rounded-md mb-3 border border-green-200 text-sm">Final quiz retake disabled for this module.</div>';
    } elseif ($_GET['retake'] === 'error') {
        echo '<div class="bg-red-100 text-red-800 px-3 py-2 rounded-md mb-3 border border-red-200 text-sm">Unable to update the retake setting. Please try again.</div>';
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
    <link rel="stylesheet" href="/src/output.css">
    <script src="https://cdn.tiny.cloud/1/o0j2mtcyxt3em3izkzzn7aoowxxmhagvzxauvlur850p2647/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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

        <main id="main-content" class="main-content flex-1 p-3 transition-all duration-300">
  <nav class="bg-blue-600 text-white shadow-sm">
        <div class="container mx-auto px-3 py-2 flex justify-between items-center">
            <h1 class="text-lg font-bold">Module Management </h1>
            <div class="flex space-x-4">
                <button id="modules-tab" class="py-2 px-4 bg-blue-700 rounded-md font-medium" onclick="switchTab('modules')">Modules</button>
                <button id="module-parts-tab" class="py-2 px-4 hover:bg-blue-700 rounded-md font-medium" onclick="switchTab('module-parts')">Module Parts</button>
                <button id="final-quiz-tab" class="py-2 px-4 hover:bg-blue-700 rounded-md font-medium" onclick="switchTab('final-quiz')">Final Quiz</button>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-2 py-3">
                <!-- Modules Tab -->
                <div id="modules-section" class="tab-content">
                    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2">Add New Module</h2>
                    <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3">
                        <form id="add-module-form" class="space-y-3" method="POST" action="database/upload_module.php" enctype="multipart/form-data">
                            <input type="hidden" name="add_module" value="1">
                            <div>
                                <label for="module-title" class="block mb-2 font-medium text-gray-700">Module Title</label>
                                <input type="text" id="module-title" name="module_title" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label for="module-description" class="block mb-2 font-medium text-gray-700">Module Description</label>
                                <textarea id="module-description" name="module_description" rows="3" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required></textarea>
                            </div>
                            <div>
                                <label for="module-image" class="block mb-2 font-medium text-gray-700">Upload Module Picture (showcase only)</label>
                                <div class="flex items-center">
                                    <input type="file" id="module-image" name="module_image" class="hidden" accept="image/*">
                                    <label for="module-image" class="cursor-pointer bg-blue-50 text-blue-600 px-4 py-2 border border-blue-300 rounded-md hover:bg-blue-100">
                                        Choose File
                                    </label>
                                    <span id="file-name" class="ml-3 text-gray-500">No file chosen</span>
                                </div>
                                <div id="image-preview" class="mt-3 hidden">
                                    <!-- Image preview will appear here -->
                                </div>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                                Add Module
                            </button>
                        </form>
                    </div>
           
    
    <!-- Existing Modules Grid -->
    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2 mt-3">Existing Modules</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php
        $conn = getMysqliConnection();
        ensureFinalQuizRetakeColumn($conn);

        $result = $conn->query("SELECT m.*, fq.id AS final_quiz_id, fq.allow_retake 
                                FROM modules m
                                LEFT JOIN final_quizzes fq ON fq.module_id = m.id
                                ORDER BY m.created_at DESC");

        while ($row = $result->fetch_assoc()) {
            echo '<div class="bg-white border border-gray-200 shadow-sm rounded-md overflow-hidden">';
            if (!empty($row['image_path'])) {
                echo '<img src="' . htmlspecialchars($row['image_path']) . '" alt="Module Image" class="w-full h-40 object-cover border-b border-gray-200">';
            }
            echo '<div class="p-4">';
            echo '<h3 class="text-base font-bold mb-2 text-gray-900 border-b border-gray-200 pb-2">' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p class="text-sm text-gray-600 mb-3">' . htmlspecialchars($row['description']) . '</p>';
            echo '<div class="flex justify-end space-x-2 border-t border-gray-200 pt-3">';

            // Check the status of the module
            if ($row['status'] === 'published') {
                echo '<form method="POST" action="database/revoke_module.php" class="inline">
                        <input type="hidden" name="module_id" value="' . $row['id'] . '">
                        <button type="submit" class="px-2 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 text-xs font-medium border border-red-200">Revoke</button>
                      </form>';
            } else {
                echo '<form method="POST" action="database/publish_module.php" class="inline">
                        <input type="hidden" name="module_id" value="' . $row['id'] . '">
                        <button type="submit" class="px-2 py-1 bg-green-100 text-green-600 rounded-md hover:bg-green-200 text-xs font-medium border border-green-200">Upload</button>
                      </form>';
            }
            echo '<button 
                    class="px-2 py-1 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 open-edit-modal text-xs font-medium border border-blue-200" 
                    data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['title']) . '" 
                    data-description="' . htmlspecialchars($row['description']) . '" 
                    data-image="' . htmlspecialchars($row['image_path']) . '">
                    Edit
                  </button>';
            if (!empty($row['final_quiz_id'])) {
                $retakeEnabled = !empty($row['allow_retake']);
                $retakeButtonLabel = $retakeEnabled ? 'Disable Retake' : 'Enable Retake';
                $retakeButtonClasses = $retakeEnabled
                    ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                    : 'bg-green-100 text-green-600 hover:bg-green-200';

                echo '<form method="POST" action="database/toggle_final_retake.php" class="inline">';
                echo '<input type="hidden" name="module_id" value="' . htmlspecialchars($row['id']) . '">';
                echo '<input type="hidden" name="final_quiz_id" value="' . htmlspecialchars($row['final_quiz_id']) . '">';
                echo '<input type="hidden" name="enable" value="' . ($retakeEnabled ? '0' : '1') . '">';
                echo '<button type="submit" class="px-2 py-1 rounded-md ' . $retakeButtonClasses . ' text-xs font-medium border">' . $retakeButtonLabel . '</button>';
                echo '</form>';
            }
            echo '<form method="POST" action="database/delete_module.php" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this module? This will also delete all related module parts and quizzes.\');">
                    <input type="hidden" name="module_id" value="' . $row['id'] . '">
                    <button type="submit" class="px-2 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 text-xs font-medium border border-red-200">Delete</button>
                  </form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

        <!-- Edit Module Modal -->
<div id="edit-module-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md max-w-md w-full mx-4">
        <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center">
            <h3 class="text-base font-bold text-gray-900">Edit Module</h3>
            <button id="close-edit-modal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
                <form id="edit-module-form" class="p-4 space-y-3" method="POST" action="database/edit_module.php" enctype="multipart/form-data">
            <input type="hidden" id="edit-module-id" name="module_id" value="">
            <input type="hidden" id="existing-image-path" name="existing_image" value="">
            <div>
                <label for="edit-module-title" class="block mb-1 text-sm font-medium text-gray-700">Module Title</label>
                <input type="text" id="edit-module-title" name="module_title" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
            </div>
            <div>
                <label for="edit-module-description" class="block mb-2 font-medium text-gray-700">Module Description</label>
                <textarea id="edit-module-description" name="module_description" rows="3" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required></textarea>
            </div>
            <div>
                <label class="block mb-2 font-medium text-gray-700">Current Image</label>
                <div id="current-image-container" class="mb-2 h-32 bg-gray-100 flex items-center justify-center rounded border">
                    <img id="current-module-image" src="" alt="Current module image" class="max-h-full max-w-full object-contain hidden">
                    <span id="no-image-text" class="text-gray-500">No image uploaded</span>
                </div>
            </div>
                    <div>
                        <label for="edit-module-image" class="block mb-2 font-medium text-gray-700">Upload New Image (Optional)</label>
                        <div class="flex items-center">
                            <input type="file" id="edit-module-image" name="module_image" class="hidden" accept="image/*">
                            <label for="edit-module-image" class="cursor-pointer bg-blue-50 text-blue-600 px-4 py-2 border border-blue-300 rounded-md hover:bg-blue-100">
                                Choose File
                            </label>
                            <span id="edit-file-name" class="ml-3 text-gray-500">No file chosen</span>
                        </div>
                    </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" id="cancel-edit" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for the edit modal functionality -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Get modal elements
    const editModal = document.getElementById('edit-module-modal');
    const editModuleId = document.getElementById('edit-module-id');
    const editModuleTitle = document.getElementById('edit-module-title');
    const editModuleDescription = document.getElementById('edit-module-description');
    const existingImagePath = document.getElementById('existing-image-path');
    const currentImage = document.getElementById('current-module-image');
    const noImageText = document.getElementById('no-image-text');
     // Get the file input and file name span
    const editFileInput = document.getElementById('edit-module-image');
    const editFileName = document.getElementById('edit-file-name');

    // Add event listener for file input change
    editFileInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            // Update the file name span with the selected file name
            editFileName.textContent = file.name;
        } else {
            // Reset the file name span if no file is selected
            editFileName.textContent = 'No file chosen';
        }
    });
    // Add event listener to all edit buttons
    document.querySelectorAll('.open-edit-modal').forEach(button => {
        button.addEventListener('click', function () {
            // Get data from button
            const moduleId = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const imagePath = this.getAttribute('data-image');

            // Populate modal fields
            editModuleId.value = moduleId;
            editModuleTitle.value = title;
            editModuleDescription.value = description;
            existingImagePath.value = imagePath;

            // Handle image display
            if (imagePath && imagePath !== '') {
                currentImage.src = imagePath;
                currentImage.classList.remove('hidden');
                noImageText.classList.add('hidden');
            } else {
                currentImage.classList.add('hidden');
                noImageText.classList.remove('hidden');
            }

            // Show modal
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        });
    });

    // Close modal function
    function closeModal() {
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    // Add event listeners for closing modal
    document.getElementById('close-edit-modal').addEventListener('click', closeModal);
    document.getElementById('cancel-edit').addEventListener('click', closeModal);

    // Close modal if clicking outside of it
    editModal.addEventListener('click', function (e) {
        if (e.target === editModal) {
            closeModal();
        }
    });
});
</script>

 <!-- Module Parts Tab with Dynamic Sections -->
<div id="module-parts-section" class="tab-content hidden">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3">
        <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2">Add New Module Part</h2>
        <form id="add-module-part-form" class="space-y-3" method="POST" action="database/add_module_part.php" enctype="multipart/form-data">
            <div>
                <label for="select-module" class="block mb-2 font-medium text-gray-700">Select Module</label>
                <select id="select-module" name="module_id" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Select a Module --</option>
                    <?php
                    // Use centralized database connection
                    $conn_modules = getMysqliConnection();
                    // Only fetch published modules
                    $modules_result = $conn_modules->query("SELECT id, title FROM modules WHERE status = 'published' ORDER BY title");
                    
                    if ($modules_result && $modules_result->num_rows > 0) {
                        while ($module = $modules_result->fetch_assoc()) {
                            echo '<option value="' . $module['id'] . '">' . htmlspecialchars($module['title']) . '</option>';
                        }
                    }
                    $conn_modules->close();
                    ?>
                </select>
            </div>
            <div>
                <label for="part-title" class="block mb-2 font-medium text-gray-700">Module Part Title</label>
                <input type="text" id="part-title" name="part_title" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <!-- Module Part Sections Container -->
            <div id="module-sections-container" class="space-y-3 border-t border-gray-200 pt-3">
                <!-- Section 1 (default section) -->
                <div class="module-section bg-gray-50 border border-gray-200 p-3 rounded-md" data-section-id="1">
                    <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
                        <h3 class="text-sm font-bold text-gray-900">Section 1</h3>
                        <button type="button" class="remove-section px-2 py-1 text-red-600 hover:bg-red-100 rounded-md" disabled>
                            Remove
                        </button>
                    </div>
                    <div class="space-y-3 mt-2">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Module Sub-Title</label>
                            <input type="text" name="sections[1][subtitle]" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Content</label>
                        <textarea id="section-1-content" name="sections[1][content]" rows="5" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 tinymce-editor text-sm" required></textarea>
                    </div>
                   
                    <!-- remove the checkbox for sub-quiz -->
                        <!-- <div class="flex items-center">
                            <input type="checkbox" id="section-1-has-subquiz" name="sections[1][has_subquiz]" class="section-has-subquiz mr-2" data-section-id="1">
                            <label for="section-1-has-subquiz" class="text-gray-700">Add Sub-Quiz to this Section</label>
                        </div> -->
                        
                        <!-- Sub-Quiz Section - initially hidden -->
                        <div id="section-1-subquiz" class="section-subquiz hidden space-y-4 p-4 border border-blue-200 rounded-md bg-blue-50">
                            <h4 class="font-medium text-blue-800">Quiz Questions</h4>
                            
                            <div id="section-1-quiz-questions" class="quiz-questions-container">
                                <!-- Quiz questions will be added here dynamically -->
                            </div>
                            
                            <button type="button" class="add-question-btn px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none" data-section-id="1">
                                Add Question
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Checkpoint Quiz and Add Section Buttons -->
            <div class="flex justify-center items-center space-x-3 pt-3">
                <button type="button" id="add-checkpoint-quiz-btn" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-medium">
                    Add Checkpoint Quiz
                </button>
                <button type="button" id="add-section-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Add New Section
                </button>
            </div>
            
            <div class="pt-3 border-t border-gray-200">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                    Add Module Part
                </button>
            </div>
        </form>
    </div>
    
    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2 mt-3">Existing Module Parts</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php
        // Use centralized database connection
        $conn_parts = getMysqliConnection();
        // Modified query to get module parts with module info
        $query = "SELECT mp.*, m.title as module_title, 
                 (SELECT COUNT(*) FROM module_sections WHERE module_part_id = mp.id) as section_count
                 FROM module_parts mp 
                 JOIN modules m ON mp.module_id = m.id 
                 ORDER BY mp.created_at DESC";
                 
        $result = $conn_parts->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="bg-white border border-gray-200 shadow-sm rounded-md overflow-hidden">';
                echo '<div class="p-4">';
                echo '<span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-md border border-blue-200">' . htmlspecialchars($row['module_title']) . '</span>';
                echo '<h3 class="text-base font-bold mt-2 mb-2 text-gray-900 border-b border-gray-200 pb-2">' . htmlspecialchars($row['title']) . '</h3>';
                echo '<p class="text-sm text-gray-600 mb-3">' . $row['section_count'] . ' sections</p>';
                echo '<div class="flex justify-end space-x-2 border-t border-gray-200 pt-3">';
                echo '<button class="px-2 py-1 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 open-edit-part-modal text-xs font-medium border border-blue-200" 
                        data-id="' . $row['id'] . '" 
                        data-module-id="' . $row['module_id'] . '"
                        data-title="' . htmlspecialchars($row['title']) . '">Edit</button>';
                echo '<form method="POST" action="database/delete_module_part.php" class="inline">';
                echo '<input type="hidden" name="module_part_id" value="' . $row['id'] . '">';
                echo '<button type="submit" class="px-2 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 text-xs font-medium border border-red-200" 
                        onclick="return confirm(\'Are you sure you want to delete this module part?\')">Delete</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="col-span-full text-center py-8 text-gray-500">No module parts found. Create your first module part above.</div>';
        }
        $conn_parts->close();
        ?>
    </div>
    
    <!-- Existing Checkpoint Quizzes -->
    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2 mt-3">Existing Checkpoint Quizzes</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php
        // Use centralized database connection
        $conn_checkpoint_list = getMysqliConnection();
        // Query to get checkpoint quizzes with module part and module info
        $checkpoint_query = "SELECT cq.*, mp.title as part_title, m.title as module_title 
                           FROM checkpoint_quizzes cq 
                           JOIN module_parts mp ON cq.module_part_id = mp.id 
                           JOIN modules m ON mp.module_id = m.id 
                           ORDER BY cq.created_at DESC";
                 
        $checkpoint_result = $conn_checkpoint_list->query($checkpoint_query);

        if ($checkpoint_result && $checkpoint_result->num_rows > 0) {
            while ($checkpoint_row = $checkpoint_result->fetch_assoc()) {
                // Get question count
                $question_count_query = "SELECT COUNT(*) as count FROM checkpoint_quiz_questions WHERE checkpoint_quiz_id = " . (int)$checkpoint_row['id'];
                $question_count_result = $conn_checkpoint_list->query($question_count_query);
                $question_count = $question_count_result ? $question_count_result->fetch_assoc()['count'] : 0;
                
                echo '<div class="bg-white border border-gray-200 shadow-sm rounded-md overflow-hidden">';
                echo '<div class="p-4">';
                echo '<span class="text-xs bg-purple-100 text-purple-600 px-2 py-1 rounded-md border border-purple-200">' . htmlspecialchars($checkpoint_row['module_title']) . '</span>';
                echo '<p class="text-xs text-gray-500 mt-1">' . htmlspecialchars($checkpoint_row['part_title']) . '</p>';
                echo '<h3 class="text-base font-bold mt-2 mb-2 text-gray-900 border-b border-gray-200 pb-2">' . htmlspecialchars($checkpoint_row['quiz_title']) . '</h3>';
                echo '<p class="text-sm text-gray-600 mb-3">' . $question_count . ' questions</p>';
                echo '<div class="flex justify-end space-x-2 border-t border-gray-200 pt-3">';
                echo '<button class="px-2 py-1 bg-purple-100 text-purple-600 rounded-md hover:bg-purple-200 open-edit-checkpoint-quiz-modal text-xs font-medium border border-purple-200" 
                        data-id="' . $checkpoint_row['id'] . '" 
                        data-module-part-id="' . $checkpoint_row['module_part_id'] . '"
                        data-quiz-title="' . htmlspecialchars($checkpoint_row['quiz_title']) . '">Edit</button>';
                echo '<form method="POST" action="database/delete_checkpoint_quiz.php" class="inline">';
                echo '<input type="hidden" name="checkpoint_quiz_id" value="' . $checkpoint_row['id'] . '">';
                echo '<button type="submit" class="px-2 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 text-xs font-medium border border-red-200" 
                        onclick="return confirm(\'Are you sure you want to delete this checkpoint quiz?\')">Delete</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="col-span-full text-center py-8 text-gray-500">No checkpoint quizzes found. Create your first checkpoint quiz above.</div>';
        }
        $conn_checkpoint_list->close();
        ?>
    </div>
</div>

<!-- Checkpoint Quiz Modal -->
<div id="checkpoint-quiz-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
            <h3 class="text-base font-bold text-gray-900">Add Checkpoint Quiz</h3>
            <button id="close-checkpoint-quiz-modal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Scrollable Content -->
        <div class="overflow-y-auto flex-1 p-6">
            <form id="add-checkpoint-quiz-form" method="POST" action="database/add_checkpoint_quiz.php">
                <!-- Module Part Selection -->
                <div class="mb-4">
                    <label for="checkpoint-module-part" class="block mb-2 font-semibold text-gray-700">Select Module Part</label>
                    <select id="checkpoint-module-part" name="module_part_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                        <option value="">-- Select a Module Part --</option>
                        <?php
                        // Use centralized database connection
                        $conn_checkpoint = getMysqliConnection();
                        $parts_result = $conn_checkpoint->query("SELECT mp.id, mp.title, m.title as module_title 
                                                                 FROM module_parts mp 
                                                                 JOIN modules m ON mp.module_id = m.id 
                                                                 WHERE m.status = 'published'
                                                                 ORDER BY m.title, mp.title");
                        if ($parts_result && $parts_result->num_rows > 0) {
                            while ($part = $parts_result->fetch_assoc()) {
                                echo '<option value="' . $part['id'] . '">' . htmlspecialchars($part['module_title'] . ' - ' . $part['title']) . '</option>';
                            }
                        }
                        $conn_checkpoint->close();
                        ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">The checkpoint quiz will appear at the end of all sections in this module part.</p>
                </div>
                
                <!-- Quiz Title -->
                <div class="mb-4">
                    <label for="checkpoint-quiz-title" class="block mb-2 font-semibold text-gray-700">Quiz Title</label>
                    <input type="text" id="checkpoint-quiz-title" name="quiz_title" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Enter quiz title" required>
                </div>
                
                <!-- Questions Container -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-semibold text-gray-700">Quiz Questions</h4>
                        <button type="button" id="add-checkpoint-question-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                            + Add Question
                        </button>
                    </div>
                    <div id="checkpoint-questions-container" class="space-y-4">
                        <!-- Questions will be added here dynamically -->
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer Buttons -->
        <div class="border-t px-6 py-4 flex justify-end space-x-3 flex-shrink-0 bg-gray-50">
            <button type="button" id="cancel-checkpoint-quiz" class="px-6 py-2 bg-gray-300 text-gray-800 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                Cancel
            </button>
            <button type="submit" form="add-checkpoint-quiz-form" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                Save Checkpoint Quiz
            </button>
        </div>
    </div>
</div>

<!-- Edit Checkpoint Quiz Modal -->
<div id="edit-checkpoint-quiz-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
            <h3 class="text-base font-bold text-gray-900">Edit Checkpoint Quiz</h3>
            <button id="close-edit-checkpoint-quiz-modal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Scrollable Content -->
        <div class="overflow-y-auto flex-1 p-6">
            <form id="edit-checkpoint-quiz-form" method="POST" action="database/edit_checkpoint_quiz.php">
                <input type="hidden" id="edit-checkpoint-quiz-id" name="checkpoint_quiz_id" value="">
                <input type="hidden" id="edit-checkpoint-module-part-id" name="module_part_id" value="">
                
                <!-- Quiz Title -->
                <div class="mb-4">
                    <label for="edit-checkpoint-quiz-title" class="block mb-2 font-semibold text-gray-700">Quiz Title</label>
                    <input type="text" id="edit-checkpoint-quiz-title" name="quiz_title" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Enter quiz title" required>
                </div>
                
                <!-- Questions Container -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-semibold text-gray-700">Quiz Questions</h4>
                        <button type="button" id="add-edit-checkpoint-question-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                            + Add Question
                        </button>
                    </div>
                    <div id="edit-checkpoint-questions-container" class="space-y-4">
                        <!-- Questions will be loaded here dynamically -->
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer Buttons -->
        <div class="border-t px-6 py-4 flex justify-end space-x-3 flex-shrink-0 bg-gray-50">
            <button type="button" id="cancel-edit-checkpoint-quiz" class="px-6 py-2 bg-gray-300 text-gray-800 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                Cancel
            </button>
            <button type="submit" form="edit-checkpoint-quiz-form" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                Save Changes
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global counter for sections
    let sectionCounter = 1;
    
    // Global counter for questions (across all sections)
    let questionCounters = {
        1: 0 // Initialize counter for the first section
    };
    
    // Add Section Button
    const addSectionBtn = document.getElementById('add-section-btn');
    const moduleSectionsContainer = document.getElementById('module-sections-container');
    
    // Add event listener for adding new sections
    addSectionBtn.addEventListener('click', function() {
        sectionCounter++;
        
        // Initialize question counter for this new section
        questionCounters[sectionCounter] = 0;
        
         // Create new section element
    const newSection = document.createElement('div');
    newSection.className = 'module-section bg-gray-50 border border-gray-200 p-3 rounded-md';
    newSection.dataset.sectionId = sectionCounter;
    
    newSection.innerHTML = `
        <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
            <h3 class="text-sm font-bold text-gray-900">Section ${sectionCounter}</h3>
            <button type="button" class="remove-section px-2 py-1 text-red-600 hover:bg-red-100 rounded-md text-xs font-medium border border-red-200">
                Remove
            </button>
        </div>
        <div class="space-y-3 mt-2">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Module Sub-Title</label>
                <input type="text" name="sections[${sectionCounter}][subtitle]" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Content</label>
                <textarea id="section-${sectionCounter}-content" name="sections[${sectionCounter}][content]" rows="5" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 tinymce-editor text-sm" required></textarea>
            </div>
        </div>
        `;
        
        moduleSectionsContainer.appendChild(newSection);
         // Initialize TinyMCE for the new content textarea
         tinymce.init({
        selector: `#section-${sectionCounter}-content`,
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
        toolbar_mode: 'floating',
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
        height: 300,
        relative_urls: false,
        remove_script_host: false,
        images_upload_handler: function(blobInfo, progress) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                fetch('database/upload_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.location) {
                        resolve(data.location);
                    } else {
                        reject('Image upload failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    reject('Error uploading image: ' + error);
                });
            });
        },
        automatic_uploads: true,
        setup: function(editor) {
            editor.on('init', function() {
                editor.on('change', function() {
                    editor.save();
                });
            });
        }
    });
    
    // Add event listeners for the new section
    setupSectionEventListeners(newSection);
});
    
    
    // Function to set up event listeners for a section
    function setupSectionEventListeners(sectionElement) {
        // Remove Section Button
        const removeBtn = sectionElement.querySelector('.remove-section');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this section?')) {
                    const sectionId = sectionElement.dataset.sectionId;
                    // Remove question counter for this section
                    delete questionCounters[sectionId];
                    // Remove the section element
                    sectionElement.remove();
                }
            });
        }
        
        // Sub-Quiz Checkbox
        const subquizCheckbox = sectionElement.querySelector('.section-has-subquiz');
        if (subquizCheckbox) {
            const sectionId = subquizCheckbox.dataset.sectionId;
            const subquizSection = document.getElementById(`section-${sectionId}-subquiz`);
            
            subquizCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    subquizSection.classList.remove('hidden');
                    // Add at least one question by default if none exist
                    if (questionCounters[sectionId] === 0) {
                        addQuizQuestion(sectionId);
                    }
                } else {
                    subquizSection.classList.add('hidden');
                }
            });
        }
        
        // Add Question Button
        const addQuestionBtn = sectionElement.querySelector('.add-question-btn');
        if (addQuestionBtn) {
            const sectionId = addQuestionBtn.dataset.sectionId;
            
            addQuestionBtn.addEventListener('click', function() {
                addQuizQuestion(sectionId);
            });
        }
    }
    
    // Function to add a quiz question to a specific section
    function addQuizQuestion(sectionId) {
        questionCounters[sectionId]++;
        const questionCounter = questionCounters[sectionId];
        
        const questionsContainer = document.getElementById(`section-${sectionId}-quiz-questions`);
        const questionDiv = document.createElement('div');
        questionDiv.className = 'quiz-question p-3 mb-3 bg-white border border-gray-200 rounded-md';
        questionDiv.dataset.questionId = questionCounter;
        
        questionDiv.innerHTML = `
            <div class="flex justify-between mb-2">
                <h4 class="font-medium">Question ${questionCounter}</h4>
                <button type="button" class="remove-question px-2 py-1 text-red-600 hover:bg-red-100 rounded-md">
                    Remove
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Question Text</label>
                    <input type="text" name="sections[${sectionId}][quiz_questions][${questionCounter}][question_text]" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 1</label>
                        <input type="text" name="sections[${sectionId}][quiz_questions][${questionCounter}][option1]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 2</label>
                        <input type="text" name="sections[${sectionId}][quiz_questions][${questionCounter}][option2]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 3</label>
                        <input type="text" name="sections[${sectionId}][quiz_questions][${questionCounter}][option3]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 4</label>
                        <input type="text" name="sections[${sectionId}][quiz_questions][${questionCounter}][option4]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Correct Answer</label>
                    <select name="sections[${sectionId}][quiz_questions][${questionCounter}][correct_answer]" class="w-full px-3 py-2 border rounded-md" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
            </div>
        `;
        
        questionsContainer.appendChild(questionDiv);
        
        // Add event listener to the remove question button
        const removeBtn = questionDiv.querySelector('.remove-question');
        removeBtn.addEventListener('click', function() {
            questionDiv.remove();
            
            // Check if there are any questions left in this section
            if (questionsContainer.children.length === 0) {
                const subquizCheckbox = document.getElementById(`section-${sectionId}-has-subquiz`);
                const subquizSection = document.getElementById(`section-${sectionId}-subquiz`);
                
                // Uncheck the checkbox and hide the quiz section
                if (subquizCheckbox) {
                    subquizCheckbox.checked = false;
                }
                if (subquizSection) {
                    subquizSection.classList.add('hidden');
                }
            }
        });
    }
    
    // Set up event listeners for the default section
    setupSectionEventListeners(document.querySelector('.module-section'));
    
    // Form submission handler
    document.getElementById('add-module-part-form').addEventListener('submit', function(e) {

        tinymce.triggerSave();
        
        // Validate each section with a sub-quiz has at least one question
        const sections = document.querySelectorAll('.module-section');
        let isValid = true;
        
        sections.forEach(section => {
            const sectionId = section.dataset.sectionId;
            const hasSubquiz = section.querySelector(`.section-has-subquiz`).checked;
            const questionsContainer = document.getElementById(`section-${sectionId}-quiz-questions`);
            
            if (hasSubquiz && (!questionsContainer || questionsContainer.children.length === 0)) {
                isValid = false;
                alert(`Section ${sectionId} has a sub-quiz enabled but no questions. Please add at least one question or disable the sub-quiz.`);
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Checkpoint Quiz Modal Functionality
    const checkpointQuizModal = document.getElementById('checkpoint-quiz-modal');
    const addCheckpointQuizBtn = document.getElementById('add-checkpoint-quiz-btn');
    const closeCheckpointQuizModal = document.getElementById('close-checkpoint-quiz-modal');
    const cancelCheckpointQuiz = document.getElementById('cancel-checkpoint-quiz');
    const checkpointModulePart = document.getElementById('checkpoint-module-part');
    let checkpointQuestionCounter = 0;
    
    // Open modal
    if (addCheckpointQuizBtn) {
        addCheckpointQuizBtn.addEventListener('click', function() {
            checkpointQuizModal.classList.remove('hidden');
            checkpointQuizModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close modal
    function closeCheckpointModal() {
        checkpointQuizModal.classList.add('hidden');
        checkpointQuizModal.classList.remove('flex');
        document.body.style.overflow = 'auto';
        // Reset form
        document.getElementById('add-checkpoint-quiz-form').reset();
        document.getElementById('checkpoint-questions-container').innerHTML = '';
        checkpointQuestionCounter = 0;
        // Add first question back
        addCheckpointQuestion();
    }
    
    if (closeCheckpointQuizModal) {
        closeCheckpointQuizModal.addEventListener('click', closeCheckpointModal);
    }
    if (cancelCheckpointQuiz) {
        cancelCheckpointQuiz.addEventListener('click', closeCheckpointModal);
    }
    
    // Remove section selection functionality - checkpoint quiz is linked to module part only
    
    // Add question function
    function addCheckpointQuestion() {
        checkpointQuestionCounter++;
        const container = document.getElementById('checkpoint-questions-container');
        const questionDiv = document.createElement('div');
        questionDiv.className = 'checkpoint-question bg-gray-50 p-4 border border-gray-200 rounded-md';
        questionDiv.dataset.questionId = checkpointQuestionCounter;
        
        questionDiv.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-medium text-gray-900">Question ${checkpointQuestionCounter}</h5>
                <button type="button" class="remove-checkpoint-question px-2 py-1 text-red-600 hover:bg-red-100 rounded-md text-sm">
                    Remove
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Question Text</label>
                    <input type="text" name="questions[${checkpointQuestionCounter}][question_text]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 1</label>
                        <input type="text" name="questions[${checkpointQuestionCounter}][option1]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 2</label>
                        <input type="text" name="questions[${checkpointQuestionCounter}][option2]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 3</label>
                        <input type="text" name="questions[${checkpointQuestionCounter}][option3]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 4</label>
                        <input type="text" name="questions[${checkpointQuestionCounter}][option4]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Correct Answer</label>
                    <select name="questions[${checkpointQuestionCounter}][correct_answer]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
            </div>
        `;
        
        container.appendChild(questionDiv);
        
        // Add remove functionality
        const removeBtn = questionDiv.querySelector('.remove-checkpoint-question');
        removeBtn.addEventListener('click', function() {
            questionDiv.remove();
        });
    }
    
    // Add question button
    const addCheckpointQuestionBtn = document.getElementById('add-checkpoint-question-btn');
    if (addCheckpointQuestionBtn) {
        addCheckpointQuestionBtn.addEventListener('click', addCheckpointQuestion);
    }
    
    // Add first question by default
    addCheckpointQuestion();
    
    // Edit Checkpoint Quiz Modal Functionality
    const editCheckpointQuizModal = document.getElementById('edit-checkpoint-quiz-modal');
    const editCheckpointQuizId = document.getElementById('edit-checkpoint-quiz-id');
    const editCheckpointModulePartId = document.getElementById('edit-checkpoint-module-part-id');
    const editCheckpointQuizTitle = document.getElementById('edit-checkpoint-quiz-title');
    const editCheckpointQuestionsContainer = document.getElementById('edit-checkpoint-questions-container');
    const addEditCheckpointQuestionBtn = document.getElementById('add-edit-checkpoint-question-btn');
    let editCheckpointQuestionCounter = 0;
    
    // Open edit modal
    document.querySelectorAll('.open-edit-checkpoint-quiz-modal').forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.getAttribute('data-id');
            const modulePartId = this.getAttribute('data-module-part-id');
            const quizTitle = this.getAttribute('data-quiz-title');
            
            editCheckpointQuizId.value = quizId;
            editCheckpointModulePartId.value = modulePartId;
            editCheckpointQuizTitle.value = quizTitle;
            editCheckpointQuestionCounter = 0;
            editCheckpointQuestionsContainer.innerHTML = '';
            
            // Load existing questions
            loadCheckpointQuizQuestions(quizId);
            
            editCheckpointQuizModal.classList.remove('hidden');
            editCheckpointQuizModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Close edit modal
    function closeEditCheckpointModal() {
        editCheckpointQuizModal.classList.add('hidden');
        editCheckpointQuizModal.classList.remove('flex');
        document.body.style.overflow = 'auto';
        editCheckpointQuestionsContainer.innerHTML = '';
        editCheckpointQuestionCounter = 0;
    }
    
    document.getElementById('close-edit-checkpoint-quiz-modal').addEventListener('click', closeEditCheckpointModal);
    document.getElementById('cancel-edit-checkpoint-quiz').addEventListener('click', closeEditCheckpointModal);
    
    // Load checkpoint quiz questions
    function loadCheckpointQuizQuestions(quizId) {
        fetch(`database/get_checkpoint_quiz_questions.php?quiz_id=${quizId}`)
            .then(response => response.json())
            .then(questions => {
                questions.forEach((question, index) => {
                    addEditCheckpointQuestion(question);
                });
            })
            .catch(error => {
                console.error('Error loading questions:', error);
            });
    }
    
    // Add question to edit modal
    function addEditCheckpointQuestion(questionData = null) {
        editCheckpointQuestionCounter++;
        const questionId = questionData ? questionData.id : '';
        const container = editCheckpointQuestionsContainer;
        const questionDiv = document.createElement('div');
        questionDiv.className = 'checkpoint-question bg-gray-50 p-4 border border-gray-200 rounded-md';
        questionDiv.dataset.questionId = questionId || 'new-' + editCheckpointQuestionCounter;
        
        questionDiv.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-medium text-gray-900">Question ${editCheckpointQuestionCounter}</h5>
                <button type="button" class="remove-edit-checkpoint-question px-2 py-1 text-red-600 hover:bg-red-100 rounded-md text-sm">
                    Remove
                </button>
            </div>
            <div class="space-y-3">
                <input type="hidden" name="questions[${editCheckpointQuestionCounter}][id]" value="${questionId}">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Question Text</label>
                    <input type="text" name="questions[${editCheckpointQuestionCounter}][question_text]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${questionData ? questionData.question_text : ''}" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 1</label>
                        <input type="text" name="questions[${editCheckpointQuestionCounter}][option1]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${questionData ? questionData.option1 : ''}" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 2</label>
                        <input type="text" name="questions[${editCheckpointQuestionCounter}][option2]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${questionData ? questionData.option2 : ''}" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 3</label>
                        <input type="text" name="questions[${editCheckpointQuestionCounter}][option3]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${questionData ? questionData.option3 : ''}" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 4</label>
                        <input type="text" name="questions[${editCheckpointQuestionCounter}][option4]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${questionData ? questionData.option4 : ''}" required>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Correct Answer</label>
                    <select name="questions[${editCheckpointQuestionCounter}][correct_answer]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="1" ${questionData && questionData.correct_answer == 1 ? 'selected' : ''}>Option 1</option>
                        <option value="2" ${questionData && questionData.correct_answer == 2 ? 'selected' : ''}>Option 2</option>
                        <option value="3" ${questionData && questionData.correct_answer == 3 ? 'selected' : ''}>Option 3</option>
                        <option value="4" ${questionData && questionData.correct_answer == 4 ? 'selected' : ''}>Option 4</option>
                    </select>
                </div>
            </div>
        `;
        
        container.appendChild(questionDiv);
        
        // Add remove functionality
        const removeBtn = questionDiv.querySelector('.remove-edit-checkpoint-question');
        removeBtn.addEventListener('click', function() {
            questionDiv.remove();
        });
    }
    
    // Add question button for edit modal
    if (addEditCheckpointQuestionBtn) {
        addEditCheckpointQuestionBtn.addEventListener('click', function() {
            addEditCheckpointQuestion();
        });
    }
});
</script>

<!-- Module Parts Edit Modal -->
<div id="edit-module-part-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
            <h3 class="text-base font-bold text-gray-900">Edit Module Part</h3>
            <button id="close-edit-part-modal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Scrollable Content -->
        <div class="overflow-y-auto flex-1">
            <form id="edit-module-part-form" class="p-6 space-y-6" method="POST" action="database/edit_module_part.php">
                <input type="hidden" id="edit-part-id" name="module_part_id" value="">
                
                <!-- Module Selection -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label for="edit-part-module" class="block mb-2 font-semibold text-gray-700">Select Module</label>
                    <select id="edit-part-module" name="module_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <?php
                        $modules = $conn->query("SELECT id, title FROM modules ORDER BY title");
                        while ($module = $modules->fetch_assoc()) {
                            echo '<option value="' . $module['id'] . '">' . htmlspecialchars($module['title']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Part Title -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label for="edit-part-title" class="block mb-2 font-semibold text-gray-700">Module Part Title</label>
                    <input type="text" id="edit-part-title" name="part_title" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                
                <!-- Sections Header -->
                <div class="border-b-2 border-gray-300 pb-2">
                    <h4 class="text-lg font-semibold text-gray-800">Module Sections</h4>
                    <p class="text-sm text-gray-600 mt-1">Edit the content for each section below</p>
                </div>
                
                <!-- Sections Container -->
                <div id="edit-sections-container" class="space-y-6">
                    <!-- Sections will be loaded here -->
                </div>
                
                <!-- Add Section Button -->
                <div class="flex justify-center pt-4 border-t border-gray-200">
                    <button type="button" id="add-edit-section-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Add New Section
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer Buttons -->
        <div class="border-t px-6 py-4 flex justify-end space-x-3 flex-shrink-0 bg-gray-50">
            <button type="button" id="cancel-edit-part" class="px-6 py-2 bg-gray-300 text-gray-800 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                Cancel
            </button>
            <button type="submit" form="edit-module-part-form" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Save Changes
            </button>
        </div>
    </div>
</div>

<script>
// JavaScript for Edit Module Part Modal
// Counter for new sections in edit modal
let editSectionCounter = 0;

document.addEventListener('DOMContentLoaded', function () {
    // Get modal elements
    const editPartModal = document.getElementById('edit-module-part-modal');
    const editPartId = document.getElementById('edit-part-id');
    const editPartTitle = document.getElementById('edit-part-title');
    const editSectionsContainer = document.getElementById('edit-sections-container');
    const addEditSectionBtn = document.getElementById('add-edit-section-btn');

    // Add event listener to all edit buttons for module parts
    document.querySelectorAll('.open-edit-part-modal').forEach(button => {
        button.addEventListener('click', function () {
            // Get data from button
            const partId = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const imagePath = this.getAttribute('data-image');

            // Populate modal fields
            editPartId.value = partId;
            editPartTitle.value = title;

            // Reset counter when opening modal
            editSectionCounter = 0;

            // Load existing sections for this module part
            loadExistingSections(partId);

            // Show modal
            editPartModal.classList.remove('hidden');
            editPartModal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        });
    });

    // Add event listener for adding new sections in edit modal
    addEditSectionBtn.addEventListener('click', function() {
        addNewSectionToEditModal();
    });

    // Close modal function
    function closePartModal() {
        editPartModal.classList.add('hidden');
        editPartModal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    // Add event listeners for closing modal
    document.getElementById('close-edit-part-modal').addEventListener('click', closePartModal);
    document.getElementById('cancel-edit-part').addEventListener('click', closePartModal);

    // Close modal if clicking outside of it
    editPartModal.addEventListener('click', function (e) {
        if (e.target === editPartModal) {
            closePartModal();
        }
    });
});

// Function to add a new section to the edit modal
function addNewSectionToEditModal() {
    editSectionCounter++;
    const container = document.getElementById('edit-sections-container');
    const newSectionId = 'new-' + editSectionCounter;
    
    // Remove "No sections found" message if present
    const noSectionsMsg = container.querySelector('.text-center');
    if (noSectionsMsg) {
        noSectionsMsg.remove();
    }
    
    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'bg-white border-2 border-gray-200 rounded-lg p-5 hover:border-blue-300 transition';
    sectionDiv.dataset.sectionId = newSectionId;
    
    sectionDiv.innerHTML = `
        <div class="flex justify-between items-start mb-2 border-b border-gray-200 pb-2">
            <div>
                <h3 class="font-bold text-sm text-gray-900">New Section ${editSectionCounter}</h3>
                <p class="text-xs text-gray-500">New Section</p>
            </div>
            <button type="button" class="remove-section px-2 py-1 text-red-600 hover:bg-red-100 rounded-md font-medium text-xs border border-red-200">
                Remove
            </button>
        </div>
        
        <div class="space-y-3 mt-2">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Module Sub-Title</label>
                <input type="text" name="sections[${newSectionId}][subtitle]" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
            </div>
            
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Content</label>
                <textarea id="section-${newSectionId}-content" name="sections[${newSectionId}][content]" rows="6" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 tinymce-editor text-sm" required></textarea>
            </div>
        </div>
    `;
    
    container.appendChild(sectionDiv);
    
    // Initialize TinyMCE for the new section content
    tinymce.init({
        selector: `#section-${newSectionId}-content`,
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
        toolbar_mode: 'floating',
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
        height: 300,
        relative_urls: false,
        remove_script_host: false,
        images_upload_handler: function(blobInfo, progress) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                fetch('database/upload_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.location) {
                        resolve(data.location);
                    } else {
                        reject('Image upload failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    reject('Error uploading image: ' + error);
                });
            });
        },
        automatic_uploads: true,
        setup: function(editor) {
            editor.on('init', function() {
                editor.on('change', function() {
                    editor.save();
                });
            });
        }
    });
    
    // Set up remove button for this section
    const removeBtn = sectionDiv.querySelector('.remove-section');
    removeBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to remove this section?')) {
            // Remove TinyMCE instance before removing the element
            const editorId = `section-${newSectionId}-content`;
            if (tinymce.get(editorId)) {
                tinymce.get(editorId).remove();
            }
            sectionDiv.remove();
        }
    });
}

// Function to load existing sections for a module part into the edit modal
function loadExistingSections(partId) {
    const container = document.getElementById('edit-sections-container');
    container.innerHTML = ''; // Clear existing sections

    // Fetch sections from the server
    fetch('database/get_module_part_sections.php?part_id=' + partId)
        .then(response => response.json())
        .then(sections => {
            if (sections.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No sections found</div>';
                return;
            }

            sections.forEach((section, index) => {
                const sectionDiv = document.createElement('div');
                sectionDiv.className = 'bg-white border-2 border-gray-200 rounded-lg p-5 hover:border-blue-300 transition';
                sectionDiv.dataset.sectionId = section.id;

                sectionDiv.innerHTML = `
                    <div class="flex justify-between items-start mb-2 border-b border-gray-200 pb-2">
                        <div>
                            <h3 class="font-bold text-sm text-gray-900">Section ${section.id}</h3>
                            <p class="text-xs text-gray-500">ID: ${section.id}</p>
                        </div>
                        <button type="button" class="remove-section px-2 py-1 text-red-600 hover:bg-red-100 rounded-md font-medium text-xs border border-red-200">
                            Remove
                        </button>
                    </div>
                    
                    <div class="space-y-3 mt-2">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Module Sub-Title</label>
                            <input type="text" name="sections[${section.id}][subtitle]" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" value="${section.subtitle || ''}" required>
                        </div>
                        
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Content</label>
                            <textarea id="section-${section.id}-content" name="sections[${section.id}][content]" rows="6" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 tinymce-editor text-sm" required>${section.content || ''}</textarea>
                        </div>
                    </div>
                `;

                container.appendChild(sectionDiv);

                // Initialize TinyMCE for the loaded section content
                tinymce.init({
                    selector: `#section-${section.id}-content`,
                    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
                    toolbar_mode: 'floating',
                    toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
                    height: 300,
                    relative_urls: false,
                    remove_script_host: false,
                    images_upload_handler: function(blobInfo, progress) {
                        return new Promise((resolve, reject) => {
                            const formData = new FormData();
                            formData.append('file', blobInfo.blob(), blobInfo.filename());

                            fetch('database/upload_image.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.location) {
                                    resolve(data.location);
                                } else {
                                    reject('Image upload failed: ' + (data.error || 'Unknown error'));
                                }
                            })
                            .catch(error => {
                                reject('Error uploading image: ' + error);
                            });
                        });
                    },
                    automatic_uploads: true,
                    setup: function(editor) {
                        editor.on('init', function() {
                            editor.on('change', function() {
                                editor.save();
                            });
                        });
                    }
                });

                // Set up remove button for this section
                const removeBtn = sectionDiv.querySelector('.remove-section');
                removeBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to remove this section?')) {
                        // Remove TinyMCE instance before removing the element
                        const editorId = `section-${section.id}-content`;
                        if (tinymce.get(editorId)) {
                            tinymce.get(editorId).remove();
                        }
                        sectionDiv.remove();
                    }
                });
            });
        })
        .catch(error => console.error('Error loading sections:', error));
}
</script>

<!-- Final Quiz Tab -->
<div id="final-quiz-section" class="tab-content hidden">
    <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mb-3">
        <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2">Add Final Quiz</h2>
        <form id="add-final-quiz-form" class="space-y-3" method="POST" action="database/add_final_quiz.php">
            <div>
                <label for="quiz-module" class="block mb-2 font-medium text-gray-700">Select Module</label>
                <select id="quiz-module" name="module_id" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Select a Module --</option>
                    <?php
                    // Fetch modules from the database
                    $modules = $conn->query("SELECT id, title FROM modules ORDER BY title");
                    while ($module = $modules->fetch_assoc()) {
                        echo '<option value="' . $module['id'] . '">' . htmlspecialchars($module['title']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="quiz-title" class="block mb-2 font-medium text-gray-700">Quiz Title</label>
                <input type="text" id="quiz-title" name="quiz_title" class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div id="quiz-questions-container" class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Quiz Questions</h3>
                <!-- Questions will be added here dynamically -->
            </div>
            <div class="space-y-3 border-t border-gray-200 pt-3">
                <button type="button" id="add-quiz-question" class="px-3 py-1.5 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm font-medium border border-blue-200">
                    + Add Question
                </button>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                    Save Final Quiz
                </button>
            </div>
        </form>
    </div>

    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2 mt-3">Existing Final Quizzes</h2>
    <div id="existing-quizzes" class="space-y-4">
        <!-- Existing quizzes will be loaded here -->
    </div>
</div>

<!-- Edit Final Quiz Modal -->
<div id="edit-quiz-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white border border-gray-200 shadow-sm rounded-md max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
      <h3 class="text-base font-bold text-gray-900">Edit Final Quiz</h3>
      <button id="close-edit-quiz-modal" class="text-gray-500 hover:text-gray-700">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>
    
    <!-- Scrollable Content -->
    <div class="overflow-y-auto flex-1 p-6">
      <form id="edit-quiz-form" method="POST" action="database/edit_final_quiz.php">
        <input type="hidden" id="edit-quiz-id" name="quiz_id">

        <!-- Quiz Title -->
        <div class="mb-4">
          <label for="edit-quiz-title" class="block mb-2 font-semibold text-gray-700">Quiz Title</label>
          <input type="text" id="edit-quiz-title" name="quiz_title" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Questions Container -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-700">Quiz Questions</h4>
            <button type="button" id="add-edit-question" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
              + Add Question
            </button>
          </div>
          <div id="edit-quiz-questions-container" class="space-y-4">
            <!-- Questions dynamically loaded here -->
          </div>
        </div>
      </form>
    </div>
    
    <!-- Footer Buttons -->
    <div class="border-t px-6 py-4 flex justify-end space-x-3 flex-shrink-0 bg-gray-50">
      <button type="button" id="cancel-edit-quiz" class="px-6 py-2 bg-gray-300 text-gray-800 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
        Cancel
      </button>
      <button type="submit" form="edit-quiz-form" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
        Save Changes
      </button>
    </div>
  </div>
</div>


<script>
let questionCount = 0;

// Function to add new question
function addQuizQuestion() {
    questionCount++;
    const questionHTML = `
        <div class="quiz-question p-3 mb-3 bg-white border border-gray-200 rounded-md" data-question="${questionCount}">
            <div class="flex justify-between mb-2">
                <h4 class="font-medium">Question ${questionCount}</h4>
                <button type="button" class="delete-question px-2 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">
                    Remove
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Question Text</label>
                    <input type="text" name="questions[${questionCount}][text]" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 1</label>
                        <input type="text" name="questions[${questionCount}][options][]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 2</label>
                        <input type="text" name="questions[${questionCount}][options][]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 3</label>
                        <input type="text" name="questions[${questionCount}][options][]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Option 4</label>
                        <input type="text" name="questions[${questionCount}][options][]" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Correct Answer</label>
                    <select name="questions[${questionCount}][correct]" class="w-full px-3 py-2 border rounded-md" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    document.getElementById('quiz-questions-container').insertAdjacentHTML('beforeend', questionHTML);
    attachQuestionEventListeners(document.querySelector(`.quiz-question[data-question="${questionCount}"]`));
}

// Update the attachQuestionEventListeners function
function attachQuestionEventListeners(questionElement) {
    questionElement.querySelector('.delete-question').addEventListener('click', function() {
        if (document.querySelectorAll('.quiz-question').length > 1) {
            questionElement.remove();
        } else {
            alert('Quiz must have at least one question.');
        }
    });
}

// Add first question by default
document.addEventListener('DOMContentLoaded', function() {
    addQuizQuestion();
    
    // Add question button event listener
    document.getElementById('add-quiz-question').addEventListener('click', addQuizQuestion);

    // Form submission handler
    document.getElementById('add-final-quiz-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (validateQuizForm(this)) {
            this.submit();
        }
    });

    // Load existing quizzes
    loadExistingQuizzes();
});

// Form validation
function validateQuizForm(form) {
    if (!form.quiz_title.value.trim()) {
        alert('Please enter a quiz title');
        return false;
    }
    if (!form.module_id.value) {
        alert('Please select a module');
        return false;
    }
    const questions = form.querySelectorAll('.quiz-question');
    if (questions.length === 0) {
        alert('Please add at least one question');
        return false;
    }
    return true;
}

// Load existing quizzes
function loadExistingQuizzes() {
    fetch('database/get_final_quizzes.php')
        .then(response => response.json())
        .then(quizzes => {
            const container = document.getElementById('existing-quizzes');
            container.innerHTML = quizzes.length ? '' : '<p class="text-gray-500 text-center">No quizzes found</p>';
            
            quizzes.forEach(quiz => {
                container.innerHTML += `
                    <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4">
                        <div class="flex justify-between items-start border-b border-gray-200 pb-2 mb-3">
                            <div>
                                <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-md border border-blue-200">
                                    ${quiz.module_title}
                                </span>
                                <h3 class="text-base font-bold mt-2 mb-2 text-gray-900">${quiz.title}</h3>
                                <p class="text-sm text-gray-600">${quiz.question_count} questions</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="editQuiz(${quiz.id})" class="px-2 py-1 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 text-xs font-medium border border-blue-200">
                                    Edit
                                </button>
                                <button onclick="deleteQuiz(${quiz.id})" class="px-2 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 text-xs font-medium border border-red-200">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        })
        .catch(error => console.error('Error loading quizzes:', error));
}

// Delete quiz function 
function deleteQuiz(quizId) {
    if (confirm('Are you sure you want to delete this quiz? This action cannot be undone.')) {
        fetch(`database/delete_final_quizzes.php?id=${quizId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload quizzes list
                    loadExistingQuizzes();
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'bg-green-100 text-green-800 px-4 py-3 rounded mb-4';
                    alertDiv.textContent = 'Quiz deleted successfully';
                    const container = document.getElementById('final-quiz-section');
                    container.insertBefore(alertDiv, container.firstChild);
                    setTimeout(() => alertDiv.remove(), 3000);
                } else {
                    throw new Error(data.message || 'Failed to delete quiz');
                }
            })
            .catch(error => {
                alert('Error deleting quiz: ' + error.message);
            });
    }
}

// Edit Quiz Handler
function editQuiz(quizId) {
  const modal = document.getElementById('edit-quiz-modal');
  const titleInput = document.getElementById('edit-quiz-title');
  const quizIdInput = document.getElementById('edit-quiz-id');
  const questionsContainer = document.getElementById('edit-quiz-questions-container');
  const addQuestionBtn = document.getElementById('add-edit-question');

  questionsContainer.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Loading questions...</p></div>';

  fetch(`database/get_final_quiz_details.php?id=${quizId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) throw new Error(data.message);
      const quiz = data.quiz;
      const questions = data.questions;

      quizIdInput.value = quiz.id;
      titleInput.value = quiz.title;
      questionsContainer.innerHTML = '';

      questions.forEach((q, index) => {
        questionsContainer.appendChild(renderQuestion(q.id, q.question_text, q.option1, q.option2, q.option3, q.option4, q.correct_answer, index));
      });

      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';
    })
    .catch(err => alert('Error loading quiz: ' + err.message));

  addQuestionBtn.onclick = () => {
    const count = questionsContainer.children.length;
    questionsContainer.appendChild(renderQuestion('', '', '', '', '', '', 1, count));
  };

  // Build question block
  function renderQuestion(id, text, o1, o2, o3, o4, correct, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'quiz-question bg-gray-50 p-4 border border-gray-200 rounded-md space-y-3 relative';
    wrapper.innerHTML = `
      <div class="flex justify-between items-center mb-3">
        <h5 class="font-medium text-gray-900">Question ${index + 1}</h5>
        <button type="button" class="remove-question px-2 py-1 text-red-600 hover:bg-red-100 rounded-md text-sm font-medium border border-red-200">
          Remove
        </button>
      </div>
      <input type="hidden" name="questions[${index}][id]" value="${id || ''}">
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Question Text</label>
        <input type="text" name="questions[${index}][text]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="${text || ''}" required>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Option 1</label>
          <input type="text" name="questions[${index}][option1]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="${o1 || ''}" required>
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Option 2</label>
          <input type="text" name="questions[${index}][option2]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="${o2 || ''}" required>
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Option 3</label>
          <input type="text" name="questions[${index}][option3]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="${o3 || ''}" required>
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Option 4</label>
          <input type="text" name="questions[${index}][option4]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="${o4 || ''}" required>
        </div>
      </div>
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Correct Answer</label>
        <select name="questions[${index}][correct]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
          <option value="">-- Select Correct Option --</option>
          <option value="1" ${correct == 1 ? 'selected' : ''}>Option 1</option>
          <option value="2" ${correct == 2 ? 'selected' : ''}>Option 2</option>
          <option value="3" ${correct == 3 ? 'selected' : ''}>Option 3</option>
          <option value="4" ${correct == 4 ? 'selected' : ''}>Option 4</option>
        </select>
      </div>
    `;
    // Add removal
    wrapper.querySelector('.remove-question').onclick = () => wrapper.remove();
    return wrapper;
  }

  // Close modal actions
  const closeModal = () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
  };
  document.getElementById('close-edit-quiz-modal').onclick = closeModal;
  document.getElementById('cancel-edit-quiz').onclick = closeModal;
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

       // ✅ Fix: Correctly reindex questions without breaking nested keys
    document.getElementById('edit-quiz-form').addEventListener('submit', function() {
    const questions = this.querySelectorAll('.quiz-question');
    questions.forEach((qEl, i) => {
        qEl.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace(/questions\[\d+\]/, `questions[${i}]`);
        });
    });
    });


}


</script>
    <script>
// Tab switching function - must be in global scope
window.switchTab = function(tabName) {
    console.log('Switching to tab:', tabName); // Debug log
    
    // Hide all tab content
    const allTabs = document.querySelectorAll('.tab-content');
    allTabs.forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Show selected tab content
    const targetSection = document.getElementById(tabName + '-section');
    if (targetSection) {
        targetSection.classList.remove('hidden');
    } else {
        console.error('Tab section not found:', tabName + '-section');
        return;
    }
    
    // Update active tab styling
    // Restrict to the module tabs nav only so the top nav profile button is not affected
    const navButtons = document.querySelectorAll('nav.bg-blue-600 button');
    navButtons.forEach(button => {
        button.classList.remove('bg-blue-700');
        button.classList.add('hover:bg-blue-700');
    });
    
    const activeTabButton = document.getElementById(tabName + '-tab');
    if (activeTabButton) {
        activeTabButton.classList.add('bg-blue-700');
        activeTabButton.classList.remove('hover:bg-blue-700');
    }
    
    // Store the active tab in session storage
    sessionStorage.setItem('activeModuleTab', tabName);
};

// Initialize by showing the correct tab
document.addEventListener('DOMContentLoaded', function() {
    // Check URL parameters first
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    // Check session storage next
    const savedTab = sessionStorage.getItem('activeModuleTab');
    
    // Default to 'modules' if no saved tab
    const activeTab = tabParam || savedTab || 'modules';
    
    // Small delay to ensure all elements are rendered
    setTimeout(function() {
        if (typeof window.switchTab === 'function') {
            window.switchTab(activeTab);
        }
    }, 100);
    
    // If there's a status message in the URL, display it
    const status = urlParams.get('status') || urlParams.get('edit'); // Support both 'status' and 'edit' parameters
    const message = urlParams.get('message');
    
    if (status && message) {
        const alertClass = status === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `${alertClass} px-4 py-3 rounded-md mb-4 text-sm font-medium`;
        alertDiv.textContent = decodeURIComponent(message);
        
        // Insert the alert at the top of the active tab section
        const activeSection = document.getElementById(activeTab + '-section');
        if (activeSection) {
            activeSection.insertBefore(alertDiv, activeSection.firstChild);
            
            // Remove the alert after 5 seconds
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.5s';
                alertDiv.style.opacity = '0';
                setTimeout(() => {
                    alertDiv.remove();
                }, 500);
            }, 5000);
        }
    }
});
    </script>
</main>
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
        [dashboardItem, modulesItem, assessmentsItem,].forEach(item => {
            item.classList.remove('active');
        });
        
        // Highlight the active link based on the current page
        if (currentPage === 'Adashboard.php' || currentPage === '' || currentPage === '/') {
            dashboardItem.classList.add('active');
        } else if (currentPage === 'Amodule.php') {
            modulesItem.classList.add('active');
        } else if (currentPage === 'Amanagement.php') {
            assessmentsItem.classList.add('active');
        }else {
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
    

    // Initialize TinyMCE for all textareas with class 'tinymce-editor'
tinymce.init({
    selector: '#section-1-content',
    plugins: 'image advlist autolink lists link charmap preview anchor pagebreak',
    toolbar_mode: 'floating',
    toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
    height: 300,
    relative_urls: false,
    remove_script_host: false,
    images_upload_handler: function(blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            fetch('database/upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.location) {
                    resolve(data.location);
                } else {
                    reject('Image upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                reject('Error uploading image: ' + error);
            });
        });
    },
    automatic_uploads: true,
    setup: function(editor) {
        editor.on('init', function() {
            editor.on('change', function() {
                editor.save();
            });
        });
    }
});

    // Function to initialize TinyMCE for dynamically added editors
    function initTinyMCE(selector) {
        tinymce.init({
            selector: selector,
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
            toolbar_mode: 'floating',
            toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            height: 300,
            relative_urls: false,
            remove_script_host: false,
            images_upload_handler: function(blobInfo, progress) {
                return new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    fetch('database/upload_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.location) {
                            resolve(data.location);
                        } else {
                            reject('Image upload failed: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        reject('Error uploading image: ' + error);
                    });
                });
            },
            automatic_uploads: true,
            setup: function(editor) {
                editor.on('init', function() {
                    editor.on('change', function() {
                        editor.save();                 editor.save();
                    });
                });
            }
        });
    }
</script>
</body>
</html>