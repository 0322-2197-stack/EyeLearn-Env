<?php
session_start();

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();

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

ensureFinalQuizRetakeColumn($conn);

$conn->query("CREATE TABLE IF NOT EXISTS final_quiz_retakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    quiz_id INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) NOT NULL DEFAULT 0,
    used_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_retake_lookup (user_id, module_id, quiz_id, used)
)");

$conn->query("CREATE TABLE IF NOT EXISTS retake_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_retake_results_user (user_id, module_id, quiz_id)
)");

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT first_name, last_name, email FROM users WHERE id = ?";
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

// Get selected module ID from URL and validate it
$selected_module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : null;

// Validate module_id
if (!$selected_module_id || $selected_module_id <= 0) {
    $_SESSION['error'] = "Invalid module selected.";
    header("Location: Smodule.php");
    exit;
}

// Handle retake requests before loading heavy data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_retake'])) {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($contentType, 'application/json') === false) {
        $requestedQuizId = intval($_POST['final_quiz_id'] ?? 0);
        if ($requestedQuizId > 0) {
            $quizValidation = $conn->prepare("SELECT allow_retake FROM final_quizzes WHERE id = ? AND module_id = ?");
            $quizValidation->bind_param("ii", $requestedQuizId, $selected_module_id);
            $quizValidation->execute();
            $quizResult = $quizValidation->get_result();
            $quizRow = $quizResult ? $quizResult->fetch_assoc() : null;
            $quizValidation->close();

            if ($quizRow && intval($quizRow['allow_retake']) === 1) {
                $pendingStmt = $conn->prepare("SELECT id FROM final_quiz_retakes WHERE user_id = ? AND module_id = ? AND quiz_id = ? AND used = 0 LIMIT 1");
                $pendingStmt->bind_param("iii", $user_id, $selected_module_id, $requestedQuizId);
                $pendingStmt->execute();
                $pendingResult = $pendingStmt->get_result();
                $pendingExists = $pendingResult && $pendingResult->num_rows > 0;
                $pendingStmt->close();

                if (!$pendingExists) {
                    $insertRetake = $conn->prepare("INSERT INTO final_quiz_retakes (user_id, module_id, quiz_id) VALUES (?, ?, ?)");
                    $insertRetake->bind_param("iii", $user_id, $selected_module_id, $requestedQuizId);
                    if ($insertRetake->execute()) {
                        $redirectStatus = 'requested';
                    } else {
                        $redirectStatus = 'error';
                    }
                    $insertRetake->close();
                } else {
                    $redirectStatus = 'exists';
                }
            } else {
                $redirectStatus = 'error';
            }
        } else {
            $redirectStatus = 'error';
        }

        $redirectStatus = $redirectStatus ?? 'error';
        $targetQuizId = $requestedQuizId ?: $selected_quiz_id;
        header("Location: " . $_SERVER['PHP_SELF'] . "?module_id={$selected_module_id}&final_quiz={$targetQuizId}&retake={$redirectStatus}");
        exit;
    }
}

// Get all modules with their parts and sections in one efficient query
$modules_query = "
    SELECT 
        m.id AS module_id, m.title AS module_title,
        mp.id AS part_id, mp.title AS part_title,
        ms.id AS section_id, ms.subtitle AS section_title, ms.content AS section_content,
        ms.has_quiz
    FROM modules m
    LEFT JOIN module_parts mp ON m.id = mp.module_id
    LEFT JOIN module_sections ms ON mp.id = ms.module_part_id
    WHERE m.id = ?
    ORDER BY m.id, mp.id, ms.id
";

$stmt = $conn->prepare($modules_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind the parameter
$stmt->bind_param("i", $selected_module_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$modules_result = $stmt->get_result();
if (!$modules_result) {
    die("Get result failed: " . $stmt->error);
}

// Organize data into hierarchical structure
$modules = [];
$current_module = null;
$current_part = null;

// For sequential navigation
$all_sections = [];
$current_section_index = -1;

while ($row = $modules_result->fetch_assoc()) {
    // New module
    if ($current_module !== $row['module_id']) {
        $current_module = $row['module_id'];
        $modules[$current_module] = [
            'id' => $row['module_id'],
            'title' => $row['module_title'],
            'parts' => []
        ];
        $current_part = null;
    }
    
    // New part within current module
    if ($row['part_id'] && $current_part !== $row['part_id']) {
        $current_part = $row['part_id'];
        $modules[$current_module]['parts'][$current_part] = [
            'id' => $row['part_id'],
            'title' => $row['part_title'],
            'sections' => []
        ];
    }
    
    // Add section if exists
    if ($row['section_id']) {
        $section = [
            'id' => $row['section_id'],
            'title' => $row['section_title'],
            'content' => $row['section_content'],
            'has_quiz' => $row['has_quiz'],
            'part_id' => $current_part
        ];
        
        $modules[$current_module]['parts'][$current_part]['sections'][$row['section_id']] = $section;
        
        // Add to flat list of all sections for navigation
        $all_sections[] = $section;
    }
}

// Add checkpoint quizzes as sections at the end of each module part
// Query all checkpoint quizzes for this module's parts
$checkpoint_quizzes_query = "
    SELECT cq.id, cq.quiz_title, cq.module_part_id, mp.id AS part_id
    FROM checkpoint_quizzes cq
    INNER JOIN module_parts mp ON cq.module_part_id = mp.id
    WHERE mp.module_id = ?
    ORDER BY mp.id, cq.id
";
$checkpoint_stmt = $conn->prepare($checkpoint_quizzes_query);
if ($checkpoint_stmt) {
    $checkpoint_stmt->bind_param("i", $selected_module_id);
    $checkpoint_stmt->execute();
    $checkpoint_result = $checkpoint_stmt->get_result();
    
    // Store checkpoint quizzes by part_id
    $checkpoint_quizzes_by_part = [];
    while ($checkpoint_row = $checkpoint_result->fetch_assoc()) {
        $part_id = $checkpoint_row['part_id'];
        if (!isset($checkpoint_quizzes_by_part[$part_id])) {
            $checkpoint_quizzes_by_part[$part_id] = [];
        }
        $checkpoint_quizzes_by_part[$part_id][] = $checkpoint_row;
    }
    $checkpoint_stmt->close();
    
    // Add checkpoint quizzes as sections at the end of each part
    foreach ($modules as $module_id => $module) {
        foreach ($module['parts'] as $part_id => $part) {
            if (isset($checkpoint_quizzes_by_part[$part_id])) {
                foreach ($checkpoint_quizzes_by_part[$part_id] as $checkpoint_quiz_data) {
                    // Create a special section for checkpoint quiz
                    // Use negative ID or special format to distinguish from regular sections
                    $checkpoint_section = [
                        'id' => 'checkpoint_' . $checkpoint_quiz_data['id'],
                        'title' => $checkpoint_quiz_data['quiz_title'],
                        'content' => '', // Checkpoint quizzes don't have content
                        'has_quiz' => false, // It IS a quiz, not a section with quiz
                        'part_id' => $part_id,
                        'is_checkpoint_quiz' => true,
                        'checkpoint_quiz_id' => $checkpoint_quiz_data['id'],
                        'module_part_id' => $checkpoint_quiz_data['module_part_id']
                    ];
                    
                    // Add to part's sections
                    $modules[$module_id]['parts'][$part_id]['sections']['checkpoint_' . $checkpoint_quiz_data['id']] = $checkpoint_section;
                    
                    // Add to flat list of all sections for navigation
                    $all_sections[] = $checkpoint_section;
                }
            }
        }
    }
}

// Get selected section ID from URL
$selected_section_id = isset($_GET['section_id']) ? $_GET['section_id'] : null;
// Handle checkpoint quiz selection from URL (backward compatibility)
if (isset($_GET['checkpoint_quiz']) && !$selected_section_id) {
    $selected_checkpoint_quiz_id = intval($_GET['checkpoint_quiz']);
    $selected_section_id = 'checkpoint_' . $selected_checkpoint_quiz_id;
}

// Get selected section content
$selected_section = null;
$prev_section = null;
$next_section = null;

// Find current section in flat list
for ($i = 0; $i < count($all_sections); $i++) {
    // Handle both integer IDs and checkpoint quiz IDs (strings like 'checkpoint_123')
    if ($all_sections[$i]['id'] == $selected_section_id || 
        (string)$all_sections[$i]['id'] === (string)$selected_section_id) {
        $selected_section = $all_sections[$i];
        $current_section_index = $i;
        break;
    }
}

// Determine previous and next sections
if ($current_section_index > 0) {
    $prev_section = $all_sections[$current_section_index - 1];
}
if ($current_section_index < count($all_sections) - 1) {
    $next_section = $all_sections[$current_section_index + 1];
}

// If no section selected but we have modules, select first available section
if (!$selected_section && !empty($all_sections)) {
    $selected_section = $all_sections[0];
    $selected_section_id = $selected_section['id'];
    $current_section_index = 0;
    
    // Set next section if there are more sections
    if (count($all_sections) > 1) {
        $next_section = $all_sections[1];
    }
}

// Get quiz questions if viewing a section with quiz
$quiz_questions = [];
if ($selected_section && $selected_section['has_quiz']) {
    $quiz_stmt = $conn->prepare("
        SELECT id, question_text, option1, option2, option3, option4, correct_answer 
        FROM section_quiz_questions 
        WHERE section_id = ?
    ");
    // FROM section_quiz_questions
    $quiz_stmt->bind_param("i", $selected_section['id']);
    $quiz_stmt->execute();
    $quiz_result = $quiz_stmt->get_result();
    
    if ($quiz_result && $quiz_result->num_rows > 0) {
        while ($question = $quiz_result->fetch_assoc()) {
            $quiz_questions[] = $question;
        }
    }
    $quiz_stmt->close();
}

// Get selected quiz ID from URL if it exists
$selected_quiz_id = isset($_GET['final_quiz']) ? intval($_GET['final_quiz']) : null;
$retake_flash_status = isset($_GET['retake']) ? $_GET['retake'] : null;

// Get checkpoint quiz ID from URL if it exists (backward compatibility)
$selected_checkpoint_quiz_id = isset($_GET['checkpoint_quiz']) ? intval($_GET['checkpoint_quiz']) : null;

// Also check if selected section is a checkpoint quiz
if ($selected_section && isset($selected_section['is_checkpoint_quiz']) && $selected_section['is_checkpoint_quiz']) {
    $selected_checkpoint_quiz_id = $selected_section['checkpoint_quiz_id'];
}

// Get checkpoint quiz data if selected
$checkpoint_quiz = null;
$checkpoint_quiz_questions = [];
$checkpoint_quiz_completed = false;
$checkpoint_quiz_score = null;
$checkpoint_quiz_percentage = null;
$checkpoint_module_part_id = null;

if ($selected_checkpoint_quiz_id) {
    $checkpoint_stmt = $conn->prepare("
        SELECT id, quiz_title, module_part_id 
        FROM checkpoint_quizzes 
        WHERE id = ?
        LIMIT 1
    ");
    $checkpoint_stmt->bind_param("i", $selected_checkpoint_quiz_id);
    $checkpoint_stmt->execute();
    $checkpoint_result = $checkpoint_stmt->get_result();
    
    if ($checkpoint_result && $checkpoint_result->num_rows > 0) {
        $checkpoint_quiz = $checkpoint_result->fetch_assoc();
        $checkpoint_module_part_id = $checkpoint_quiz['module_part_id'];
        
        // Get checkpoint quiz questions
        $checkpoint_questions_stmt = $conn->prepare("
            SELECT id, question_text, option1, option2, option3, option4, correct_answer 
            FROM checkpoint_quiz_questions 
            WHERE checkpoint_quiz_id = ?
            ORDER BY question_order ASC
        ");
        $checkpoint_questions_stmt->bind_param("i", $checkpoint_quiz['id']);
        $checkpoint_questions_stmt->execute();
        $checkpoint_questions_result = $checkpoint_questions_stmt->get_result();
        
        if ($checkpoint_questions_result && $checkpoint_questions_result->num_rows > 0) {
            while ($question = $checkpoint_questions_result->fetch_assoc()) {
                $checkpoint_quiz_questions[] = $question;
            }
        }
        $checkpoint_questions_stmt->close();
        
        // Check if checkpoint quiz is already completed
        $checkpoint_completion_stmt = $conn->prepare("
            SELECT score, total_questions, percentage, completion_date, user_answers 
            FROM checkpoint_quiz_results 
            WHERE user_id = ? AND checkpoint_quiz_id = ? 
            ORDER BY completion_date DESC 
            LIMIT 1
        ");
        $checkpoint_user_answers = null;
        if ($checkpoint_completion_stmt) {
            $checkpoint_completion_stmt->bind_param("ii", $user_id, $checkpoint_quiz['id']);
            $checkpoint_completion_stmt->execute();
            $checkpoint_completion_result = $checkpoint_completion_stmt->get_result();
            if ($checkpoint_completion_result && $checkpoint_completion_result->num_rows > 0) {
                $checkpoint_quiz_completed = true;
                $completion_data = $checkpoint_completion_result->fetch_assoc();
                $checkpoint_quiz_score = intval($completion_data['score']);
                $checkpoint_quiz_percentage = floatval($completion_data['percentage']);
                // Get user answers if available
                if (!empty($completion_data['user_answers'])) {
                    $checkpoint_user_answers = json_decode($completion_data['user_answers'], true);
                }
            }
            $checkpoint_completion_stmt->close();
        }
    }
    $checkpoint_stmt->close();
}

// Get final quiz questions if final quiz is selected (needed before score calculation)
$final_quiz_questions = [];
if ($selected_quiz_id) {
    $final_quiz_query = "
        SELECT id, question_text, option1, option2, option3, option4, correct_answer 
        FROM final_quiz_questions 
        WHERE quiz_id = ?
        ORDER BY id
    ";
    $quiz_stmt = $conn->prepare($final_quiz_query);
    $quiz_stmt->bind_param("i", $selected_quiz_id);
    $quiz_stmt->execute();
    $quiz_result = $quiz_stmt->get_result();
    
    if ($quiz_result && $quiz_result->num_rows > 0) {
        while ($question = $quiz_result->fetch_assoc()) {
            $final_quiz_questions[] = $question;
        }
    }
    $quiz_stmt->close();
}

// Check if quiz is already completed (check both original and retake results)
// Get the most recent score from either quiz_results or retake_results
$quiz_completed = false;
$quiz_score = null;
$quiz_total_questions = 0;
$quiz_percentage = 0;
if ($selected_quiz_id) {
    // Get most recent score from both tables using UNION
    $completion_check = $conn->prepare("
        SELECT score, completion_date, 'original' as source FROM quiz_results 
        WHERE user_id = ? AND quiz_id = ?
        UNION ALL
        SELECT score, completion_date, 'retake' as source FROM retake_results 
        WHERE user_id = ? AND quiz_id = ?
        ORDER BY completion_date DESC LIMIT 1
    ");
    $completion_check->bind_param("iiii", $user_id, $selected_quiz_id, $user_id, $selected_quiz_id);
    $completion_check->execute();
    $completion_result = $completion_check->get_result();
    if ($completion_result->num_rows > 0) {
        $quiz_completed = true;
        $latest_result = $completion_result->fetch_assoc();
        $quiz_score = $latest_result['score'];
    }
    $completion_check->close();
    
    // Calculate total questions and percentage
    $quiz_total_questions = count($final_quiz_questions);
    if ($quiz_total_questions > 0 && $quiz_score !== null) {
        $quiz_percentage = round(($quiz_score / $quiz_total_questions) * 100, 1);
    }
}

// Get final quiz for this module
$final_quiz_query = "SELECT id, title, allow_retake FROM final_quizzes WHERE module_id = ?";
$quiz_stmt = $conn->prepare($final_quiz_query);
$quiz_stmt->bind_param("i", $selected_module_id);
$quiz_stmt->execute();
$final_quiz = $quiz_stmt->get_result()->fetch_assoc();
$quiz_stmt->close();
$final_quiz_allows_retake = $final_quiz ? (int)($final_quiz['allow_retake'] ?? 0) : 0;
$pending_retake_id = null;
$has_pending_retake = false;
$has_completed_retake_attempt = false;
if ($selected_quiz_id && $final_quiz && $final_quiz_allows_retake === 1) {
    // Check for pending (unused) retakes
    $retakeStmt = $conn->prepare("SELECT id FROM final_quiz_retakes WHERE user_id = ? AND module_id = ? AND quiz_id = ? AND used = 0 ORDER BY requested_at DESC LIMIT 1");
    $retakeStmt->bind_param("iii", $user_id, $selected_module_id, $selected_quiz_id);
    $retakeStmt->execute();
    $retakeResult = $retakeStmt->get_result();
    if ($retakeResult && $retakeResult->num_rows > 0) {
        $pending_retake_id = $retakeResult->fetch_assoc()['id'];
        $has_pending_retake = true;
    }
    $retakeStmt->close();

    // Check if user has completed any retake attempts (check retake_results table)
    $retakeHistoryStmt = $conn->prepare("SELECT 1 FROM retake_results WHERE user_id = ? AND module_id = ? AND quiz_id = ? LIMIT 1");
    if ($retakeHistoryStmt) {
        $retakeHistoryStmt->bind_param("iii", $user_id, $selected_module_id, $selected_quiz_id);
        $retakeHistoryStmt->execute();
        $retakeHistoryResult = $retakeHistoryStmt->get_result();
        $has_retake_results = $retakeHistoryResult && $retakeHistoryResult->num_rows > 0;
        $retakeHistoryStmt->close();
    } else {
        $has_retake_results = false;
    }
    
    // Check if user has any used retakes (completed retakes in final_quiz_retakes table)
    $usedRetakeStmt = $conn->prepare("SELECT 1 FROM final_quiz_retakes WHERE user_id = ? AND module_id = ? AND quiz_id = ? AND used = 1 LIMIT 1");
    if ($usedRetakeStmt) {
        $usedRetakeStmt->bind_param("iii", $user_id, $selected_module_id, $selected_quiz_id);
        $usedRetakeStmt->execute();
        $usedRetakeResult = $usedRetakeStmt->get_result();
        $has_used_retakes = $usedRetakeResult && $usedRetakeResult->num_rows > 0;
        $usedRetakeStmt->close();
    } else {
        $has_used_retakes = false;
    }
    
    // User has completed a retake if they have retake results OR used retakes
    $has_completed_retake_attempt = $has_retake_results || $has_used_retakes;
}
$quiz_was_completed = $quiz_completed;
if ($has_pending_retake) {
    $quiz_completed = false;
}
$canRetakeFinalQuiz = ($final_quiz_allows_retake === 1);
// Show "Retake Again" if user has completed at least one retake attempt OR has a pending retake
$retake_button_label = ($has_completed_retake_attempt || $has_pending_retake) ? 'Retake Again' : 'Retake Quiz';

// If retakes are enabled and quiz is completed, always show overview by default
// Only show quiz form if user explicitly wants to start a retake (has pending retake AND wants to use it)
// Check if user wants to start the retake via URL parameter
$start_retake = isset($_GET['start_retake']) && $_GET['start_retake'] == '1';
// Always show overview if quiz is completed, unless user explicitly wants to start a pending retake
$shouldShowQuizOverview = $quiz_was_completed && !($has_pending_retake && $start_retake);

// Calculate completion percentage (placeholder)
$completion_percentage = 60;

// Close database connection
$is_module_completed = false;

// Check if this is the last section and all previous sections are completed
if ($selected_section && $current_section_index === count($all_sections) - 1) {
    // For now just set it to true since we don't have section completion tracking yet
    // TODO: Add proper section completion tracking
    $is_module_completed = true;
}

// Get user's progress for this module
try {
    $progress_query = "SELECT completed_sections FROM user_module_progress
                      WHERE user_id = ? AND module_id = ?";
    $stmt = $conn->prepare($progress_query);
    $stmt->bind_param("ii", $user_id, $selected_module_id);
    $stmt->execute();
    $progress_result = $stmt->get_result();

    // After getting user's progress, update this section
    $completed_sections = [];
    if ($progress_result->num_rows > 0) {
        $progress_data = $progress_result->fetch_assoc();
        $completed_sections = json_decode($progress_data['completed_sections'], true) ?? [];
    } else {
        // Create progress record if doesn't exist
        $stmt = $conn->prepare("INSERT INTO user_module_progress (user_id, module_id, completed_sections) VALUES (?, ?, '[]')");
        $stmt->bind_param("ii", $user_id, $selected_module_id);
        $stmt->execute();
    }

    // Handle section completion
    if (isset($_POST['section_completed'])) {
        if (!in_array($selected_section_id, $completed_sections)) {
            $completed_sections[] = $selected_section_id;
            $completed_json = json_encode($completed_sections);
            
            $update_stmt = $conn->prepare("UPDATE user_module_progress 
                                         SET completed_sections = ? 
                                         WHERE user_id = ? AND module_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("sii", $completed_json, $user_id, $selected_module_id);
                $update_stmt->execute();
                
                // Redirect to same page to show quiz
                header("Location: " . $_SERVER['PHP_SELF'] . "?module_id=" . $selected_module_id . "&section_id=" . $selected_section_id);
                exit;
            }
        }
    }

    // Update these variables
    $is_section_completed = in_array($selected_section_id, $completed_sections);
    $can_access_quiz = $is_section_completed;
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, set default values
    $completed_sections = [];
}

// Update quiz access check logic
$is_section_completed = in_array($selected_section_id, $completed_sections);
$can_access_quiz = $is_section_completed || isset($_POST['section_completed']);

// In the section completion POST handler section, add logging
if (isset($_POST['section_completed'])) {
    error_log("Section completion requested for section ID: " . $selected_section_id);
    error_log("Current completed sections: " . json_encode($completed_sections));
}

// Update the section completion check logic - place this after getting user progress
$is_section_completed = in_array($selected_section_id, $completed_sections);
$needs_completion = $selected_section && !$is_section_completed;
$can_access_quiz = $selected_section && ($is_section_completed || isset($_POST['section_completed']));

// Calculate completion percentage with error handling
// Exclude checkpoint quizzes from progress calculation - they are optional assessments
$total_sections = 0;
$completed_count = 0;

// Count only regular sections (exclude checkpoint quizzes from progress)
foreach ($all_sections as $section) {
    // Skip checkpoint quizzes in progress calculation
    if (isset($section['is_checkpoint_quiz']) && $section['is_checkpoint_quiz']) {
        continue; // Don't count checkpoint quizzes in progress
    }
    
    // Count regular sections only
    $total_sections++;
    
    // Check if regular section is completed
    $is_completed = in_array($section['id'], $completed_sections);
    
    if ($is_completed) {
        $completed_count++;
    }
}

$completion_percentage = $total_sections > 0 ? round(($completed_count / $total_sections) * 100) : 0;

// Ensure percentage doesn't exceed 100%
if ($completion_percentage > 100) {
    $completion_percentage = 100;
}

// Check if module is completed (all sections completed and exactly 100%)
$is_module_completed = ($completed_count === $total_sections && $completion_percentage >= 100);

// Add AJAX endpoint for updating progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_completed'])) {
    $section_id = intval($_POST['section_id'] ?? 0);
    $response = ['success' => false, 'completion' => 0];

    if ($section_id && !in_array($section_id, $completed_sections)) {
        $completed_sections[] = $section_id;
        $completed_json = json_encode($completed_sections);

        $stmt = $conn->prepare("UPDATE user_module_progress 
                                SET completed_sections = ? 
                                WHERE user_id = ? AND module_id = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $completed_json, $user_id, $selected_module_id);
            if ($stmt->execute()) {
                // Recalculate completion excluding checkpoint quizzes
                $regular_total = 0;
                $regular_completed = 0;
                foreach ($all_sections as $section) {
                    // Skip checkpoint quizzes
                    if (isset($section['is_checkpoint_quiz']) && $section['is_checkpoint_quiz']) {
                        continue;
                    }
                    $regular_total++;
                    if (in_array($section['id'], $completed_sections)) {
                        $regular_completed++;
                    }
                }
                $response['success'] = true;
                $response['completion'] = $regular_total > 0 ? round(($regular_completed / $regular_total) * 100) : 0;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;  // **do not redirect**
}

// After database connection, add/update these table checks
$create_table_sql = "CREATE TABLE IF NOT EXISTS user_module_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    completed_sections JSON,
    UNIQUE KEY user_module (user_id, module_id)
)";
$conn->query($create_table_sql);

// Add after other table creations
$conn->query("CREATE TABLE IF NOT EXISTS module_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    final_quiz_score INT,
    UNIQUE KEY unique_completion (user_id, module_id)
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Start output buffering to prevent any accidental output
        ob_start();
        
        // Set JSON header only for AJAX requests
        header('Content-Type: application/json');
        
        $content = trim(file_get_contents("php://input"));
        $data = json_decode($content, true);
        
        // Check if JSON parsing failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Invalid JSON data: ' . json_last_error_msg(),
                'received_content' => substr($content, 0, 200)
            ]);
            exit;
        }
        
        if (isset($data['action']) && $data['action'] === 'submit_final_quiz') {
            try {
                $score = intval($data['score']);
                $quiz_id = intval($data['quiz_id']);
                // $module_title = $data['module_id'];
                $module_title = $data['module_title'];
                
                $retakeIdPayload = isset($data['retake_id']) ? intval($data['retake_id']) : 0;
                $isRetakeAttempt = $retakeIdPayload > 0;

                // First, save the quiz or retake result
                $resultInsertSql = $isRetakeAttempt
                    ? "INSERT INTO retake_results (user_id, module_id, quiz_id, score, completion_date) VALUES (?, ?, ?, ?, NOW())"
                    : "INSERT INTO quiz_results (user_id, module_id, quiz_id, score, completion_date) VALUES (?, ?, ?, ?, NOW())";

                $resultStmt = $conn->prepare($resultInsertSql);
                if (!$resultStmt) {
                    throw new Exception("Failed to prepare final quiz result statement: " . $conn->error);
                }
                $resultStmt->bind_param("iiii", $user_id, $selected_module_id, $quiz_id, $score);
                if (!$resultStmt->execute()) {
                    throw new Exception("Failed to save quiz result: " . $resultStmt->error);
                }
                $resultStmt->close();

                // Then update module completion
                $completionStmt = $conn->prepare("
                    INSERT INTO module_completions (user_id, module_id, final_quiz_score) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        final_quiz_score = ?,
                        completion_date = NOW()
                ");
                
                if (!$completionStmt) {
                    throw new Exception("Failed to prepare module completion statement: " . $conn->error);
                }
                
                // Bind actual score (number of correct answers), not quiz_id
                $completionStmt->bind_param("iiii", $user_id, $selected_module_id, $score, $score);
                
                if ($completionStmt->execute()) {
                    if ($isRetakeAttempt) {
                        $retakeUpdate = $conn->prepare("UPDATE final_quiz_retakes SET used = 1, used_at = NOW() WHERE id = ? AND user_id = ?");
                        if ($retakeUpdate) {
                            $retakeUpdate->bind_param("ii", $retakeIdPayload, $user_id);
                            $retakeUpdate->execute();
                            $retakeUpdate->close();
                        }
                    }
                    ob_end_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quiz submitted successfully',
                        'score' => $score
                    ]);
                } else {
                    throw new Exception("Failed to execute statement: " . $completionStmt->error);
                }
                $completionStmt->close();
            } catch (Exception $e) {
                ob_end_clean();
                error_log("Quiz submission error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            ob_end_flush();
            exit;
        }
        
        // Handle checkpoint quiz submission
        if (isset($data['action']) && $data['action'] === 'submit_checkpoint_quiz') {
            try {
                // Check database connection
                if (!isset($conn) || !$conn || $conn->connect_error) {
                    throw new Exception("Database connection error. Please try again.");
                }
                
                // Ensure we have user_id and module_id
                if (!isset($user_id) || !$user_id) {
                    throw new Exception("User not authenticated. Please log in again.");
                }
                
                // Get module_id - prioritize from POST data, fallback to GET parameter
                $post_module_id = isset($data['module_id']) ? intval($data['module_id']) : null;
                $current_module_id = $post_module_id ?? $selected_module_id;
                
                if (!$current_module_id || $current_module_id <= 0) {
                    throw new Exception("Module ID is required. Please refresh the page and try again.");
                }
                
                // Validate all required fields exist and have values
                if (!isset($data['checkpoint_quiz_id']) || !isset($data['module_part_id']) || 
                    !isset($data['score']) || !isset($data['total_questions'])) {
                    $missing = [];
                    if (!isset($data['checkpoint_quiz_id'])) $missing[] = 'checkpoint_quiz_id';
                    if (!isset($data['module_part_id'])) $missing[] = 'module_part_id';
                    if (!isset($data['score'])) $missing[] = 'score';
                    if (!isset($data['total_questions'])) $missing[] = 'total_questions';
                    throw new Exception("Missing required data: " . implode(', ', $missing));
                }
                
                $checkpoint_quiz_id = intval($data['checkpoint_quiz_id']);
                $module_part_id = intval($data['module_part_id']);
                $score = intval($data['score']);
                $total_questions = intval($data['total_questions']);
                
                // Validate score ranges
                if ($score < 0 || $total_questions <= 0 || $score > $total_questions) {
                    throw new Exception("Invalid score data. Score must be between 0 and total_questions.");
                }
                
                $percentage = $total_questions > 0 ? round(($score / $total_questions) * 100 * 10) / 10 : 0;
                
                if ($checkpoint_quiz_id <= 0 || $module_part_id <= 0) {
                    throw new Exception("Invalid quiz data provided. checkpoint_quiz_id: $checkpoint_quiz_id, module_part_id: $module_part_id");
                }
                
                // Verify checkpoint quiz exists and belongs to correct module
                $verifyQuiz = $conn->prepare("
                    SELECT cq.id FROM checkpoint_quizzes cq
                    INNER JOIN module_parts mp ON cq.module_part_id = mp.id
                    WHERE cq.id = ? AND mp.module_id = ?
                    LIMIT 1
                ");
                if (!$verifyQuiz) {
                    throw new Exception("Failed to prepare verification query: " . $conn->error);
                }
                
                $verifyQuiz->bind_param("ii", $checkpoint_quiz_id, $current_module_id);
                if (!$verifyQuiz->execute()) {
                    throw new Exception("Failed to verify quiz: " . $verifyQuiz->error);
                }
                
                $verifyResult = $verifyQuiz->get_result();
                if (!$verifyResult || $verifyResult->num_rows === 0) {
                    throw new Exception("Checkpoint quiz not found or does not belong to this module.");
                }
                $verifyQuiz->close();
                
                // Create checkpoint_quiz_results table if it doesn't exist
                $createTableSql = "CREATE TABLE IF NOT EXISTS checkpoint_quiz_results (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    module_id INT NOT NULL,
                    checkpoint_quiz_id INT NOT NULL,
                    module_part_id INT NOT NULL,
                    score INT NOT NULL,
                    total_questions INT NOT NULL,
                    percentage DECIMAL(5,2) NOT NULL,
                    user_answers JSON NULL,
                    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_checkpoint_results_user (user_id, module_id, checkpoint_quiz_id)
                )";
                
                if (!$conn->query($createTableSql)) {
                    throw new Exception("Failed to create checkpoint_quiz_results table: " . $conn->error);
                }
                
                // Add user_answers column if it doesn't exist (for existing tables)
                $checkColumn = $conn->query("SHOW COLUMNS FROM checkpoint_quiz_results LIKE 'user_answers'");
                if ($checkColumn->num_rows == 0) {
                    $conn->query("ALTER TABLE checkpoint_quiz_results ADD COLUMN user_answers JSON NULL AFTER percentage");
                }
                
                // Check if result already exists to prevent duplicate submissions
                $checkExisting = $conn->prepare("SELECT id FROM checkpoint_quiz_results WHERE user_id = ? AND checkpoint_quiz_id = ? LIMIT 1");
                if (!$checkExisting) {
                    throw new Exception("Failed to prepare check existing: " . $conn->error);
                }
                
                $checkExisting->bind_param("ii", $user_id, $checkpoint_quiz_id);
                if (!$checkExisting->execute()) {
                    throw new Exception("Failed to check existing results: " . $checkExisting->error);
                }
                
                $existingResult = $checkExisting->get_result();
                $checkExisting->close();
                
                if ($existingResult && $existingResult->num_rows > 0) {
                    // Result already exists, just return success (idempotent)
                    ob_end_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Checkpoint quiz already submitted',
                        'score' => $score,
                        'total_questions' => $total_questions,
                        'percentage' => $percentage,
                        'already_submitted' => true
                    ]);
                    exit;
                }
                
                // Get user answers from request
                $user_answers_json = null;
                if (isset($data['user_answers']) && is_array($data['user_answers'])) {
                    $user_answers_json = json_encode($data['user_answers']);
                }
                
                // Save checkpoint quiz result
                $resultStmt = $conn->prepare("
                    INSERT INTO checkpoint_quiz_results 
                    (user_id, module_id, checkpoint_quiz_id, module_part_id, score, total_questions, percentage, user_answers, completion_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if (!$resultStmt) {
                    throw new Exception("Failed to prepare checkpoint quiz result statement: " . $conn->error);
                }
                
                // Bind with correct types: i=int, i=int, i=int, i=int, i=int, i=int, d=double, s=string
                $resultStmt->bind_param("iiiiiids", $user_id, $current_module_id, $checkpoint_quiz_id, $module_part_id, $score, $total_questions, $percentage, $user_answers_json);
                
                if (!$resultStmt->execute()) {
                    throw new Exception("Failed to save checkpoint quiz result: " . $resultStmt->error);
                }
                
                $resultStmt->close();
                
                // Mark checkpoint quiz section as complete in user_module_progress
                $checkpoint_section_id = 'checkpoint_' . $checkpoint_quiz_id;
                
                // Get current progress
                $progressStmt = $conn->prepare("SELECT completed_sections FROM user_module_progress WHERE user_id = ? AND module_id = ?");
                if (!$progressStmt) {
                    throw new Exception("Failed to prepare progress statement: " . $conn->error);
                }
                
                $progressStmt->bind_param("ii", $user_id, $current_module_id);
                if (!$progressStmt->execute()) {
                    throw new Exception("Failed to execute progress statement: " . $progressStmt->error);
                }
                
                $progressResult = $progressStmt->get_result();
                
                $completed_sections = [];
                if ($progressResult && $progressResult->num_rows > 0) {
                    $progressData = $progressResult->fetch_assoc();
                    $completed_sections = json_decode($progressData['completed_sections'], true) ?? [];
                }
                $progressStmt->close();
                
                // Add checkpoint section to completed sections if not already there
                if (!in_array($checkpoint_section_id, $completed_sections)) {
                    $completed_sections[] = $checkpoint_section_id;
                    $completed_json = json_encode($completed_sections);
                    
                    // Update or insert progress
                    $updateStmt = $conn->prepare("
                        INSERT INTO user_module_progress (user_id, module_id, completed_sections) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE completed_sections = ?
                    ");
                    
                    if (!$updateStmt) {
                        throw new Exception("Failed to prepare update statement: " . $conn->error);
                    }
                    
                    $updateStmt->bind_param("iiss", $user_id, $current_module_id, $completed_json, $completed_json);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Failed to update progress: " . $updateStmt->error);
                    }
                    $updateStmt->close();
                }
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Checkpoint quiz submitted successfully',
                    'score' => $score,
                    'total_questions' => $total_questions,
                    'percentage' => $percentage
                ]);
            } catch (Exception $e) {
                ob_end_clean();
                error_log("Checkpoint quiz submission error: " . $e->getMessage());
                error_log("Checkpoint quiz submission data: " . json_encode($data));
                error_log("User ID: " . (isset($user_id) ? $user_id : 'NOT SET'));
                error_log("Module ID from GET: " . (isset($selected_module_id) ? $selected_module_id : 'NOT SET'));
                error_log("Module ID from POST: " . (isset($data['module_id']) ? $data['module_id'] : 'NOT SET'));
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            ob_end_flush();
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EyeLearn - Module View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../src/output.css">
    <script>
        const currentCompletionPercentage = <?php echo json_encode($completion_percentage); ?>; //Tofu: pass completion percentage
    </script>
        <script src="js/cv-eye-tracking.js?canvas_debug_<?php echo time(); ?>"></script>
    <script>
        // Checkpoint Quiz Handler - Define early so it's available for inline onclick
        <?php if (($selected_checkpoint_quiz_id && isset($checkpoint_quiz)) || ($selected_section && isset($selected_section['is_checkpoint_quiz']) && $selected_section['is_checkpoint_quiz'] && isset($checkpoint_quiz))): ?>
        // Define checkpoint quiz variables early
        const checkpointCorrectAnswers = <?php 
            $checkpointCorrectAnswers = [];
            if (!empty($checkpoint_quiz_questions)) {
                foreach ($checkpoint_quiz_questions as $q) {
                    $checkpointCorrectAnswers[$q['id']] = intval($q['correct_answer']);
                }
            }
            echo json_encode($checkpointCorrectAnswers);
        ?>;
        const checkpointQuizId = <?php echo isset($checkpoint_quiz) && $checkpoint_quiz ? intval($checkpoint_quiz['id']) : 'null'; ?>;
        const checkpointModulePartId = <?php echo isset($checkpoint_module_part_id) && $checkpoint_module_part_id ? intval($checkpoint_module_part_id) : 'null'; ?>;
        const moduleId = <?php echo isset($selected_module_id) && $selected_module_id ? intval($selected_module_id) : 'null'; ?>;
        
        // Main checkpoint quiz submission function - defined early in head
        function submitCheckpointQuiz() {
            const submitCheckpointQuizBtn = document.getElementById('submit-checkpoint-quiz');
            const checkpointQuizForm = document.getElementById('checkpoint-quiz-form');
            const checkpointQuizResults = document.getElementById('checkpoint-quiz-results');
            
            console.log('submitCheckpointQuiz called (from head)');
            console.log('Checkpoint quiz elements:', {
                button: submitCheckpointQuizBtn,
                form: checkpointQuizForm,
                results: checkpointQuizResults,
                buttonDisabled: submitCheckpointQuizBtn ? submitCheckpointQuizBtn.disabled : 'button not found'
            });
            
            if (!submitCheckpointQuizBtn) {
                console.error('Submit checkpoint quiz button not found!');
                alert('Error: Submit button not found. Please refresh the page.');
                return;
            }
            
            if (submitCheckpointQuizBtn.disabled) {
                console.warn('Button is disabled, cannot submit');
                return;
            }
            
            if (!checkpointQuizForm) {
                console.error('Checkpoint quiz form not found!');
                alert('Error: Quiz form not found. Please refresh the page.');
                return;
            }
            
            console.log('Checkpoint quiz initialized. Button disabled:', submitCheckpointQuizBtn.disabled);
            console.log('Checkpoint correct answers:', checkpointCorrectAnswers);
            
            const questions = document.querySelectorAll('#checkpoint-quiz-form .checkpoint-question');
            let correctAnswers = 0;
            let totalQuestions = questions.length;
            let answeredQuestions = 0;
            
            console.log('Total questions found:', totalQuestions);
            
            // Check if all questions are answered
            questions.forEach(question => {
                const questionId = parseInt(question.dataset.questionId);
                const selectedOption = question.querySelector(`input[name="checkpoint_question_${questionId}"]:checked`);
                if (selectedOption) {
                    answeredQuestions++;
                }
            });
            
            if (answeredQuestions < totalQuestions) {
                alert(`Please answer all questions before submitting. You have answered ${answeredQuestions} out of ${totalQuestions} questions.`);
                return;
            }
            
            // Calculate score and collect user answers
            const userAnswers = {};
            questions.forEach(question => {
                const questionId = parseInt(question.dataset.questionId);
                const selectedOption = question.querySelector(`input[name="checkpoint_question_${questionId}"]:checked`);
                
                if (selectedOption) {
                    const selectedValue = parseInt(selectedOption.value);
                    userAnswers[questionId] = selectedValue;
                    const correctAnswer = checkpointCorrectAnswers[questionId];
                    
                    if (correctAnswer && selectedValue === correctAnswer) {
                        correctAnswers++;
                    }
                }
            });
            
            const percentage = totalQuestions > 0 ? Math.round((correctAnswers / totalQuestions) * 100) : 0;
            
            console.log('Checkpoint quiz IDs:', {
                quizId: checkpointQuizId,
                modulePartId: checkpointModulePartId,
                moduleId: moduleId
            });
            
            if (!checkpointQuizId || !checkpointModulePartId || !moduleId) {
                alert('Error: Missing quiz information. Please refresh the page and try again.');
                console.error('Missing quiz information:', {
                    checkpointQuizId: checkpointQuizId,
                    checkpointModulePartId: checkpointModulePartId,
                    moduleId: moduleId
                });
                return;
            }
            
            // Disable submit button
            submitCheckpointQuizBtn.disabled = true;
            submitCheckpointQuizBtn.textContent = 'Submitting...';
            
            // Prepare data to send
            const submitData = {
                action: 'submit_checkpoint_quiz',
                checkpoint_quiz_id: checkpointQuizId,
                module_part_id: checkpointModulePartId,
                module_id: moduleId,
                score: correctAnswers,
                total_questions: totalQuestions,
                percentage: percentage,
                user_answers: userAnswers
            };
            
            console.log('Submitting checkpoint quiz:', submitData);
            
            // Send results to server
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(submitData)
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Checkpoint quiz response:', data);
                if (data.success) {
                    // Reload immediately to show checkpoint quiz overview
                    window.location.reload();
                } else {
                    alert('Error submitting checkpoint quiz: ' + (data.error || 'Unknown error'));
                    submitCheckpointQuizBtn.disabled = false;
                    submitCheckpointQuizBtn.textContent = 'Submit Checkpoint Quiz';
                }
            })
            .catch(error => {
                console.error('Checkpoint quiz submission error:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                alert('Error submitting checkpoint quiz: ' + error.message + '. Please check the browser console for more details.');
                submitCheckpointQuizBtn.disabled = false;
                submitCheckpointQuizBtn.textContent = 'Submit Checkpoint Quiz';
            });
        }
        
        // Handler function for inline onclick
        function handleCheckpointQuizSubmit(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            console.log('handleCheckpointQuizSubmit called (inline onclick - from head)');
            submitCheckpointQuiz();
        }
        <?php else: ?>
        // Placeholder when checkpoint quiz is not active
        function handleCheckpointQuizSubmit(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            console.warn('handleCheckpointQuizSubmit called but checkpoint quiz is not active');
        }
        function submitCheckpointQuiz() {
            console.warn('submitCheckpointQuiz called but checkpoint quiz is not active');
        }
        <?php endif; ?>
    </script>
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
         /* Ensure TinyMCE content displays properly */
         .module-content img {
            max-width: 100%;
            height: auto;
        }
        .module-content ul, .module-content ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .module-content h1, .module-content h2, .module-content h3, .module-content h4 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .module-content h1 { font-size: 1.8rem; }
        .module-content h2 { font-size: 1.5rem; }
        .module-content h3 { font-size: 1.3rem; }
        .module-content h4 { font-size: 1.1rem; }
        .module-content p {
            margin-bottom: 1rem;
        }
        .module-content a {
            color: #3B82F6;
            text-decoration: underline;
        }
        /* Sidebar styling */
        .sidebar {
            width: 280px;
            background-color: white;
            transition: all 0.3s ease;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
        }
        
        .sidebar-collapsed {
            width: 0;
            overflow: hidden;
        }
        
        /* Module sidebar specific */
        .module-item {
            border-bottom: 1px solid #e5e7eb;
        }

        .module-header {
            padding: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .module-header:hover {
            background-color: #f8fafc;
        }

        .part-item {
            border-bottom: 1px solid #f3f4f6;
        }

        .part-item:last-child {
            border-bottom: none;
        }

        .part-header {
            transition: all 0.2s ease;
        }

        .part-sections {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .part-header[aria-expanded="true"] + .part-sections {
            max-height: 2000px;
        }

        .section-item {
            text-decoration: none;
            color: #374151;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .section-item:hover {
            background-color: #f3f4f6;
        }

        .section-item.active {
            background-color: #eff6ff;
            border-left-color: #3b82f6;
            color: #1e40af;
        }

        .section-item.checkpoint-section {
            position: relative;
        }

        .section-item.checkpoint-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #a855f7, #9333ea);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .section-item.checkpoint-section:hover::before,
        .section-item.checkpoint-section.active::before {
            opacity: 1;
        }

        .section-item.completed .check-icon {
            color: #10b981;
        }

        .chevron {
            transition: transform 0.3s ease;
        }

        .chevron.rotated,
        .part-header[aria-expanded="true"] .chevron {
            transform: rotate(90deg);
        }
        
        /* Content area */
        .main-content {
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content-collapsed {
            margin-left: 0;
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
        
        /* Quiz styling */
        .quiz-container {
            background-color: #f0f9ff;
            border-left: 4px solid #3B82F6;
            margin-top: 2rem;
        }
        
        .quiz-option {
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .quiz-option:hover {
            border-color: #3B82F6;
            background-color: #f0f7ff;
        }
        
        .quiz-option.selected {
            border-color: #3B82F6;
            background-color: #e0f2fe;
        }
        
        /* Navigation buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
        }
        
        .nav-button {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .nav-button.prev {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        .nav-button.next {
            background-color: #3B82F6;
            color: white;
        }
        
        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Additional sidebar improvements */
        .section-item.active {
            border-left-width: 3px;
        }
        
        .check-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }
        
        /* Responsive behavior */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                height: 100%;
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
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
            
            .nav-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-button {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* IBM Skill Build like progress */
        .progress-container {
            position: relative;
            height: 4px;
            background-color: #e5e7eb;
            margin-top: 8px;
        }
        
        .progress-bar {
            position: absolute;
            height: 100%;
            background-color: #0f62fe;
            transition: width 0.3s ease;
        }

        /* Quiz specific styles */
        .quiz-container {
            background-color: #f8fafc;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .quiz-question {
            background-color: white;
            transition: all 0.2s ease;
        }

        .quiz-option {
            background-color: white;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .quiz-option:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .quiz-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .quiz-option input[type="radio"] {
            cursor: pointer;
        }

        .quiz-option label {
            cursor: pointer;
            user-select: none;
        }

        /* Quiz results styling */
        #quiz-results {
            transition: all 0.3s ease;
        }

        #quiz-results.bg-green-50 {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Semi-circular gauge styles */
        .score-gauge-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 2.5rem 0;
            padding: 0 1rem;
        }
        
        .score-gauge {
            position: relative;
            width: 400px;
            height: 200px;
            margin-bottom: 1.5rem;
        }
        
        .score-gauge-arc {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .score-gauge-arc svg {
            width: 100%;
            height: 100%;
        }
        
        .score-gauge-arc-background {
            fill: none;
            stroke: #e5e7eb;
            stroke-width: 24;
            stroke-linecap: round;
        }
        
        .score-gauge-arc-fill {
            fill: none;
            stroke: #10b981;
            stroke-width: 24;
            stroke-linecap: round;
            stroke-dasharray: 314.16;
            stroke-dashoffset: 314.16;
            transition: stroke-dashoffset 1.5s ease-in-out;
        }
        
        .score-gauge-arc-fill.passed {
            stroke: #10b981;
        }
        
        .score-gauge-arc-fill.failed {
            stroke: #ef4444;
        }
        
        .score-gauge-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -30%);
            font-size: 4rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }
        
        .score-text {
            text-align: center;
            color: #1f2937;
            font-size: 1.125rem;
            margin-bottom: 0.75rem;
            font-weight: 400;
        }
        
        .score-details {
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1.25rem;
            font-weight: 400;
        }
        
        .pass-status {
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .pass-status.passed {
            color: #10b981;
        }
        
        .pass-status.failed {
            color: #ef4444;
        }
        
        @media (max-width: 640px) {
            .score-gauge {
                width: 300px;
                height: 150px;
            }
            
            .score-gauge-percentage {
                font-size: 3rem;
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

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- Sidebar ---
                const sidebarEl = document.getElementById('sidebar');
                const mainContentEl = document.getElementById('main-content');
                const backdropEl = document.getElementById('backdrop');
                const toggleSidebarBtn = document.getElementById('toggle-sidebar');
                let currentOpenHeader = null;

                let isSidebarVisible = true;
                const isMobile = () => window.innerWidth < 768;

                // Restore sidebar state
                const savedState = localStorage.getItem('sidebarVisible');
                if (savedState !== null && !isMobile()) {
                    isSidebarVisible = savedState === 'true';
                    sidebarEl.classList.toggle('sidebar-collapsed', !isSidebarVisible);
                    mainContentEl.classList.toggle('main-content-collapsed', !isSidebarVisible);
                }

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

                toggleSidebarBtn.addEventListener('click', toggleSidebar);
                backdropEl.addEventListener('click', () => {
                    sidebarEl.classList.remove('mobile-visible');
                    backdropEl.classList.remove('active');
                });

                window.addEventListener('resize', () => {
                    if (!isMobile()) {
                        sidebarEl.classList.remove('mobile-visible');
                        backdropEl.classList.remove('active');

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

                // --- Part Header Toggle ---
                function togglePartSection(header, force = null) {
                    const partItem = header.closest('.part-item');
                    const sectionsContainer = partItem.querySelector('.part-sections');
                    const chevron = header.querySelector('.chevron');
                    if (!sectionsContainer) return;

                    const isVisible = getComputedStyle(sectionsContainer).display !== 'none';
                    const shouldShow = force !== null ? force : !isVisible;

                    if (shouldShow) {
                        sectionsContainer.style.display = 'block';
                        sectionsContainer.style.height = '0px';
                        sectionsContainer.style.overflow = 'hidden';
                        const targetHeight = sectionsContainer.scrollHeight + 'px';
                        requestAnimationFrame(() => {
                            sectionsContainer.style.transition = 'height 0.3s ease';
                            sectionsContainer.style.height = targetHeight;
                        });
                        setTimeout(() => {
                            sectionsContainer.style.height = '';
                            sectionsContainer.style.overflow = '';
                            sectionsContainer.style.transition = '';
                        }, 200);
                        chevron?.classList.add('rotated');
                        header.setAttribute('aria-expanded', 'true');
                    } else {
                        const currentHeight = sectionsContainer.scrollHeight + 'px';
                        sectionsContainer.style.height = currentHeight;
                        sectionsContainer.style.overflow = 'hidden';
                        requestAnimationFrame(() => {
                            sectionsContainer.style.transition = 'height 0.3s ease';
                            sectionsContainer.style.height = '0px';
                        });
                        setTimeout(() => {
                            sectionsContainer.style.display = 'none';
                            sectionsContainer.style.height = '';
                            sectionsContainer.style.overflow = '';
                            sectionsContainer.style.transition = '';
                        }, 200);
                        chevron?.classList.remove('rotated');
                        header.setAttribute('aria-expanded', 'false');
                    }
                }

                        // Initialize part headers
                        document.querySelectorAll('.part-header').forEach((header, index) => {
                        const partItem = header.closest('.part-item');
                        const sectionsContainer = partItem.querySelector('.part-sections');

                        // Start collapsed by default
                        if (sectionsContainer) {
                            sectionsContainer.style.display = 'none';
                        }

                        header.setAttribute('role', 'button');
                        header.setAttribute('aria-expanded', 'false');
                        header.style.cursor = 'pointer';

                        header.addEventListener('click', () => {
                            // Close all other sections
                            document.querySelectorAll('.part-sections').forEach(section => {
                                if (section !== sectionsContainer) {
                                    section.style.display = 'none';
                                    section.previousElementSibling?.classList.remove('rotated');
                                    section.previousElementSibling?.setAttribute('aria-expanded', 'false');
                                }
                            });

                            // Toggle clicked section
                            togglePartSection(header);
                        });
                    });

                // Open current section if PHP variables are set
                <?php if (!empty($selected_section['part_id'])): ?>
                const currentPartId = <?php echo json_encode($selected_section['part_id']); ?>;
                const currentPartHeader = document.querySelector(`.part-header[data-part-id="${currentPartId}"]`);
                if (currentPartHeader) togglePartSection(currentPartHeader, true);
                <?php endif; ?>

                // --- Final Quiz Link ---
                const finalQuizLink = document.querySelector('a[href*="final_quiz"]');
                <?php if (isset($is_module_completed)): ?>
                if (finalQuizLink) {
                    const isModuleCompleted = <?php echo json_encode($is_module_completed); ?>;
                    finalQuizLink.style.opacity = isModuleCompleted ? '1' : '0.5';
                    finalQuizLink.style.pointerEvents = isModuleCompleted ? 'auto' : 'none';
                    if (!isModuleCompleted) {
                        const tooltipDiv = document.createElement('div');
                        tooltipDiv.className = 'text-sm text-gray-500 mt-1';
                        tooltipDiv.textContent = 'Complete all sections to unlock';
                        finalQuizLink.parentNode.appendChild(tooltipDiv);
                    }
                }
                <?php endif; ?>
            });
        </script>
        
        <!-- Right side - Notifications and Profile -->
        <div class="flex items-center space-x-4">
            <!-- Back to Dashboard -->
            <a href="Sdashboard.php" class="text-gray-700 hover:text-primary flex items-center mr-2 bg-transparent border-none cursor-pointer">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden md:inline">Back to Dashboard</span>
            </a>
            
            <!-- Profile dropdown -->
            <div class="profile-container relative">
                <button id="profile-toggle" class="flex items-center space-x-2 focus:outline-none">
                    <div class="bg-primary rounded-full w-8 h-8 flex items-center justify-center text-white font-medium text-sm">
                        <?php echo $initials; ?>
                    </div>
                <div id="profile-dropdown" class="profile-dropdown">
                    <div class="p-4 border-b">
                        <p class="font-medium text-gray-800"><?php echo $user_display_name; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $user_email; ?></p>
                    </div>
                    <div class="p-2">
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Your Profile</a>
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Settings</a>
                        <a href="../logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="flex min-h-screen pt-16">
        <!-- Mobile backdrop -->
        <div id="backdrop" class="backdrop"></div>
        
        <!-- Module Sidebar -->
        <div id="sidebar" class="sidebar fixed left-0 top-16 h-full shadow-lg z-40 flex flex-col transition-all duration-300 ease-in-out">
            <div class="p-3 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900 mb-2"><?php echo !empty($modules) ? htmlspecialchars(reset($modules)['title']) : 'Learning Content'; ?></h2>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs font-medium text-gray-500">Progress</span>
                    <span class="text-xs font-medium text-gray-700"><?php echo $completion_percentage; ?>%</span>
                </div>
                <div class="progress-container mt-1">
                    <div class="progress-bar" style="width: <?php echo $completion_percentage; ?>%"></div>
                </div>
            </div>

            <div class="overflow-y-auto flex-1">
                <?php foreach ($modules as $module): ?>
                <div class="module-item">
                    <?php foreach ($module['parts'] as $part): ?>
                    <div class="part-item">
                        <div class="part-header p-3 bg-gray-100 flex items-center justify-between cursor-pointer hover:bg-gray-200 transition-colors" data-part-id="<?php echo $part['id']; ?>">
                            <h4 class="font-medium text-gray-700 flex-1"><?php echo htmlspecialchars($part['title']); ?></h4>
                            <svg class="chevron w-5 h-5 text-gray-600 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>

                        <div class="part-sections bg-gray-50">
                            <?php foreach ($part['sections'] as $section): 
                                // Check if this is a checkpoint quiz section
                                $is_checkpoint = isset($section['is_checkpoint_quiz']) && $section['is_checkpoint_quiz'];
                                
                                // Determine if section is completed
                                $section_completed = false;
                                if ($is_checkpoint && isset($section['checkpoint_quiz_id'])) {
                                    // Check checkpoint quiz completion
                                    $checkpoint_id = $section['checkpoint_quiz_id'];
                                    $check_completion = $conn->prepare("SELECT 1 FROM checkpoint_quiz_results WHERE user_id = ? AND checkpoint_quiz_id = ? LIMIT 1");
                                    if ($check_completion) {
                                        $check_completion->bind_param("ii", $user_id, $checkpoint_id);
                                        $check_completion->execute();
                                        $check_result = $check_completion->get_result();
                                        $section_completed = $check_result && $check_result->num_rows > 0;
                                        $check_completion->close();
                                    }
                                } else {
                                    // Regular section completion
                                    $section_completed = in_array($section['id'], $completed_sections);
                                }
                                
                                // Compare section IDs (handle both string and int)
                                $is_active = (string)$selected_section_id === (string)$section['id'];
                            ?>
                            <a href="?module_id=<?php echo $selected_module_id; ?>&section_id=<?php echo urlencode($section['id']); ?>" 
                               class="section-item flex items-center px-4 py-2.5 hover:bg-gray-100 transition-colors <?php echo $is_active ? 'active bg-blue-50' : ''; ?> <?php echo $is_checkpoint ? 'checkpoint-section' : ''; ?>">
                                <span class="check-icon mr-3 shrink-0 <?php echo $section_completed ? 'text-green-500' : 'text-gray-400'; ?>">
                                    <?php if ($is_checkpoint): ?>
                                        <!-- Checkpoint Quiz Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                        </svg>
                                    <?php else: ?>
                                        <!-- Regular Section Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    <?php endif; ?>
                                </span>
                                <span class="text-sm text-gray-700 flex-1 truncate <?php echo $is_active ? 'font-medium text-blue-700' : ''; ?>"><?php echo htmlspecialchars($section['title']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Final Quiz Section -->
                    <?php if ($final_quiz): ?>
                    <div class="border-t border-gray-200 mt-2">
                        <?php if ($is_module_completed): ?>
                        <a href="?module_id=<?php echo $selected_module_id; ?>&final_quiz=<?php echo $final_quiz['id']; ?>" 
                           class="flex items-center px-4 py-3 hover:bg-gray-100 transition-colors <?php echo ($selected_quiz_id == $final_quiz['id']) ? 'active bg-blue-50' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-800">Final Quiz: <?php echo htmlspecialchars($final_quiz['title']); ?></span>
                        </a>
                        <?php else: ?>
                        <div class="flex items-center px-4 py-3 opacity-50 cursor-not-allowed" title="Complete all sections (<?php echo $completion_percentage; ?>%) to unlock">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-500">Final Quiz: <?php echo htmlspecialchars($final_quiz['title']); ?> (Locked)</span>
                        </div>
                        <p class="text-xs text-gray-500 px-4 pb-2">Complete all sections to unlock</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <main id="main-content" class="main-content flex-1 p-3 transition-all duration-300 mt-16">
    <!-- Live Feed Box (Hidden by default, shown when tracking) -->
    <div id="live-feed-container" style="position: fixed; top: 80px; right: 20px; z-index: 1000; background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); display: none;">
        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #3B82F6;">Live Camera Feed</h4>
        <img id="tracking-video" alt="Camera feed will appear here" style="width: 320px; height: 240px; background-color: #000; border-radius: 4px; display: block; object-fit: contain;">
        <button onclick="stopModuleTracking()" style="width: 100%; margin-top: 8px; padding: 4px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Stop Tracking</button>
    </div>
        
        <?php if ($selected_quiz_id): ?>
            <?php
                $finalQuizTitle = $final_quiz ? htmlspecialchars($final_quiz['title']) : 'Final Quiz';
                $retakeModeActive = $canRetakeFinalQuiz && $has_pending_retake;
            ?>
            <div class="max-w-4xl mx-auto px-2">
                <div class="quiz-container bg-white border border-gray-200 shadow-sm rounded-md p-4">
                    <div class="mb-3 border-b border-gray-200 pb-2">
                        <p class="text-xs font-semibold text-primary uppercase tracking-wide">Final Quiz</p>
                        <h2 id="final-quiz-header" class="text-lg font-bold text-gray-900 mb-2">Final Quiz: <?php echo $finalQuizTitle; ?></h2>
                        <p id="final-quiz-subheading" class="text-sm font-semibold text-green-700 uppercase tracking-wide mt-1 <?php echo $shouldShowQuizOverview ? '' : 'hidden'; ?>">
                            Quiz Overview
                        </p>
                    </div>

                    <?php if ($retake_flash_status === 'requested'): ?>
                        <div id="retake-status-alert" class="mb-4 px-4 py-3 rounded bg-blue-100 text-blue-900">
                            Retake enabled. Scroll down to start your new attempt.
                        </div>
                    <?php elseif ($retake_flash_status === 'exists'): ?>
                        <div id="retake-status-alert" class="mb-4 px-4 py-3 rounded bg-yellow-100 text-yellow-900">
                            You already have an active retake available. Complete it to request another.
                        </div>
                    <?php elseif ($retake_flash_status === 'error'): ?>
                        <div id="retake-status-alert" class="mb-4 px-4 py-3 rounded bg-red-100 text-red-800">
                            Unable to start a retake right now. Please try again later.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($shouldShowQuizOverview): ?>
                        <div id="quiz-overview-card" class="text-center p-4 bg-green-50 border border-green-200 rounded-md shadow-sm mt-3">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round, stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-green-900 mb-2">Quiz Already Completed</h3>
                            
                            <!-- Score Display Section with Progress Bar -->
                            <div class="mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Score: <?php echo number_format($quiz_percentage, 1); ?>%</span>
                                    <span class="text-sm font-medium text-gray-700"><?php echo isset($quiz_score) ? $quiz_score : 0; ?> / <?php echo $quiz_total_questions; ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full <?php echo ($quiz_percentage >= 70) ? 'bg-green-600' : 'bg-red-600'; ?>" style="width: <?php echo $quiz_percentage; ?>%"></div>
                                </div>
                                <p class="text-sm mt-2 <?php echo (isset($quiz_percentage) && $quiz_percentage >= 70) ? 'text-green-700 font-medium' : 'text-red-700 font-medium'; ?>">
                                    <?php if (isset($quiz_percentage) && $quiz_percentage >= 70): ?>
                                        You have passed the exam.
                                    <?php else: ?>
                                        You have not passed the exam.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <p class="text-sm text-green-800 mb-3">Thank you for completing this Module.</p>
                            <?php if ($canRetakeFinalQuiz): ?>
                                <p class="text-sm text-blue-800 mb-3">Retakes are enabled for this module. Click the button below to start another attempt.</p>
                            <?php endif; ?>
                            <?php if ($has_pending_retake): ?>
                                <div class="mb-3 px-3 py-2 rounded-md bg-blue-100 text-blue-900 border border-blue-200">
                                    <p class="text-sm font-semibold">You have a pending retake available.</p>
                                    <p class="text-xs mt-1">Click "Start Retake" below to begin your retake attempt.</p>
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-wrap justify-center gap-2 mt-3 border-t border-green-200 pt-3">
                                <a href="Sassessment.php" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    View Assessment History
                                </a>
                                <a href="Sdashboard.php" class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    Return to Dashboard
                                </a>
                                <button id="review-quiz-btn-php" class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                    Review Quiz
                                </button>
                                <?php if ($has_pending_retake && $final_quiz): ?>
                                    <a href="?module_id=<?php echo $selected_module_id; ?>&final_quiz=<?php echo $final_quiz['id']; ?>&start_retake=1" class="inline-flex items-center justify-center px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                                        Start Retake
                                    </a>
                                <?php elseif ($canRetakeFinalQuiz && $final_quiz): ?>
                                <form method="POST" class="inline-block retake-request-form">
                                    <input type="hidden" name="request_retake" value="1">
                                    <input type="hidden" name="final_quiz_id" value="<?php echo $final_quiz['id']; ?>">
                                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                        <?php echo htmlspecialchars($retake_button_label); ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($retakeModeActive && $quiz_was_completed): ?>
                            <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <h3 class="text-sm font-semibold text-blue-900 mb-1">Retake Enabled</h3>
                                <p class="text-sm text-blue-800">You're currently retaking this quiz. Your previous score was <?php echo isset($quiz_score) ? $quiz_score : 0; ?>/<?php echo $quiz_total_questions; ?> (<?php echo $quiz_percentage; ?>%).</p>
                            </div>
                        <?php endif; ?>
                        <!-- Original quiz form code -->
                        <form id="final-quiz-form" class="space-y-3 mt-3">
                            <?php foreach ($final_quiz_questions as $index => $question): ?>
                            <div class="quiz-question bg-white border border-gray-200 rounded-md p-3" data-question-id="<?php echo $question['id']; ?>">
                                <h3 class="font-medium text-base mb-2 text-gray-900"><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?></h3>
                                
                                <div class="space-y-2">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="quiz-option flex items-center p-2 rounded-md cursor-pointer border border-gray-200 hover:bg-gray-50" data-option="<?php echo $i; ?>">
                                        <input type="radio" 
                                               id="fq<?php echo $question['id']; ?>_opt<?php echo $i; ?>" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               value="<?php echo $i; ?>" 
                                               class="mr-3 h-4 w-4 text-blue-600">
                                        <label for="fq<?php echo $question['id']; ?>_opt<?php echo $i; ?>" class="flex-1 cursor-pointer">
                                            <?php echo htmlspecialchars($question['option' . $i]); ?>
                                        </label>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-3 border-t border-gray-200 pt-3">
                                <button type="button" id="submit-final-quiz" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                                    Submit Final Quiz
                            </button>
                            </div>
                        </form>
                        <div id="final-quiz-results" class="hidden mt-3"></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif (($selected_checkpoint_quiz_id && $checkpoint_quiz) || ($selected_section && isset($selected_section['is_checkpoint_quiz']) && $selected_section['is_checkpoint_quiz'] && $checkpoint_quiz)): ?>
            <!-- Checkpoint Quiz Display -->
            <div class="max-w-4xl mx-auto px-2">
                <div class="bg-purple-50 border-2 border-purple-200 shadow-sm rounded-md p-4 mb-4">
                    <h2 class="text-lg font-bold mb-3 text-purple-900 border-b border-purple-300 pb-2"><?php echo htmlspecialchars($checkpoint_quiz['quiz_title']); ?></h2>
                    
                    <?php if ($checkpoint_quiz_completed): ?>
                        <!-- Show previous results if quiz is already completed -->
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                            <h3 class="text-lg font-bold mb-2 text-gray-900">Checkpoint Quiz Results</h3>
                            <p class="text-base mb-2">You scored <strong><?php echo $checkpoint_quiz_score; ?></strong> out of <strong><?php echo count($checkpoint_quiz_questions); ?></strong> questions.</p>
                            <p class="text-base mb-2">Percentage: <strong><?php echo number_format($checkpoint_quiz_percentage, 1); ?>%</strong></p>
                            <div class="mt-3">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-purple-600 h-2.5 rounded-full" style="width: <?php echo $checkpoint_quiz_percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quiz Review Section -->
                        <?php if (!empty($checkpoint_user_answers)): ?>
                        <div class="mt-4 p-4 bg-white border-2 border-purple-300 rounded-md">
                            <h3 class="text-lg font-bold mb-4 text-purple-900 border-b border-purple-200 pb-2">Quiz Review</h3>
                            <p class="text-sm text-gray-600 mb-4">Review your answers and see the correct solutions below.</p>
                            
                            <div class="space-y-4">
                                <?php foreach ($checkpoint_quiz_questions as $index => $question): 
                                    $question_id = $question['id'];
                                    $user_answer = isset($checkpoint_user_answers[$question_id]) ? intval($checkpoint_user_answers[$question_id]) : null;
                                    $correct_answer = intval($question['correct_answer']);
                                    $is_correct = $user_answer === $correct_answer;
                                ?>
                                <div class="checkpoint-review-question bg-gray-50 p-4 rounded-md border <?php echo $is_correct ? 'border-green-300' : 'border-red-300'; ?>">
                                    <div class="flex items-start justify-between mb-3">
                                        <h4 class="font-semibold text-base text-gray-900 flex-1">
                                            <?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?>
                                        </h4>
                                        <span class="ml-3 px-3 py-1 rounded-full text-xs font-semibold <?php echo $is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $is_correct ? '✓ Correct' : '✗ Incorrect'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <?php for ($i = 1; $i <= 4; $i++): 
                                            $is_user_answer = ($user_answer === $i);
                                            $is_correct_option = ($correct_answer === $i);
                                            $option_class = '';
                                            if ($is_correct_option) {
                                                $option_class = 'bg-green-100 border-green-400 border-2';
                                            } elseif ($is_user_answer && !$is_correct) {
                                                $option_class = 'bg-red-100 border-red-400 border-2';
                                            } else {
                                                $option_class = 'bg-white border-gray-200';
                                            }
                                        ?>
                                        <div class="quiz-review-option flex items-center p-3 rounded-md border <?php echo $option_class; ?>">
                                            <div class="flex items-center flex-1">
                                                <?php if ($is_correct_option): ?>
                                                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    <span class="font-semibold text-green-800 mr-2">Correct Answer:</span>
                                                <?php elseif ($is_user_answer && !$is_correct): ?>
                                                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <span class="font-semibold text-red-800 mr-2">Your Answer:</span>
                                                <?php else: ?>
                                                    <div class="w-5 h-5 mr-2"></div>
                                                <?php endif; ?>
                                                <span class="<?php echo ($is_correct_option || $is_user_answer) ? 'font-medium' : ''; ?>">
                                                    <?php echo htmlspecialchars($question['option' . $i]); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                    
                    <form id="checkpoint-quiz-form" class="space-y-3 mt-3">
                        <?php foreach ($checkpoint_quiz_questions as $index => $question): ?>
                        <div class="checkpoint-question bg-white p-3 rounded-md border border-purple-200" data-question-id="<?php echo $question['id']; ?>">
                            <h3 class="font-medium text-base mb-2 text-gray-900"><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?></h3>
                            <div class="space-y-2">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="quiz-option flex items-center p-2 rounded-md cursor-pointer border border-gray-200 hover:bg-gray-50">
                                    <input type="radio" 
                                           id="cpq<?php echo $question['id']; ?>_opt<?php echo $i; ?>" 
                                           name="checkpoint_question_<?php echo $question['id']; ?>" 
                                           value="<?php echo $i; ?>" 
                                           class="mr-3">
                                    <label for="cpq<?php echo $question['id']; ?>_opt<?php echo $i; ?>" class="flex-1 cursor-pointer">
                                        <?php echo htmlspecialchars($question['option' . $i]); ?>
                                    </label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3 border-t border-purple-200 pt-3">
                            <button type="button" id="submit-checkpoint-quiz" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-medium cursor-pointer transition-all duration-200" <?php echo $checkpoint_quiz_completed ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'onclick="handleCheckpointQuizSubmit(event)"'; ?>>
                                <?php echo $checkpoint_quiz_completed ? 'Quiz Already Completed' : 'Submit Checkpoint Quiz'; ?>
                            </button>
                        </div>
                    </form>
                    <div id="checkpoint-quiz-results" class="hidden mt-3"></div>
                    <?php endif; ?>
                </div>
                
                <!-- Navigation buttons for checkpoint quiz -->
                <div class="nav-buttons mt-4">
                    <?php if ($prev_section): ?>
                    <a href="?module_id=<?php echo $selected_module_id; ?>&section_id=<?php echo urlencode($prev_section['id']); ?>" class="nav-button prev">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous: <?php echo htmlspecialchars($prev_section['title']); ?>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    
                    <?php if ($next_section): ?>
                    <a href="?module_id=<?php echo $selected_module_id; ?>&section_id=<?php echo urlencode($next_section['id']); ?>" class="nav-button next">
                        Next: <?php echo htmlspecialchars($next_section['title']); ?>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <?php elseif ($is_module_completed && $final_quiz): ?>
                    <a href="?module_id=<?php echo $selected_module_id; ?>&final_quiz=<?php echo $final_quiz['id']; ?>" 
                       class="nav-button next">
                        Next: Final Quiz
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($selected_section && !isset($selected_section['is_checkpoint_quiz'])): ?>
            <div class="max-w-4xl mx-auto px-2">
                <!-- Section content -->
                <div class="module-content prose max-w-none">
                    <?php echo $selected_section['content']; ?>
                </div>

                <!-- Quiz section - show only after completion -->
                <?php if ($selected_section['has_quiz'] && $is_section_completed): ?>
                <div id="quiz" class="quiz-container mt-3 bg-white border border-gray-200 shadow-sm rounded-md p-4">
                    <h2 class="text-lg font-bold mb-3 text-gray-900 border-b border-gray-200 pb-2">Section Quiz</h2>
                    <?php if (!empty($quiz_questions)): ?>
                    <form id="quiz-form" class="space-y-3 mt-3">
                        <?php foreach ($quiz_questions as $index => $question): ?>
                        <div class="quiz-question bg-white p-3 rounded-md border border-gray-200" data-question-id="<?php echo $question['id']; ?>">
                            <h3 class="font-medium text-base mb-2 text-gray-900"><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?></h3>
                            <div class="space-y-2">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="quiz-option flex items-center p-2 rounded-md cursor-pointer border border-gray-200 hover:bg-gray-50" data-option="<?php echo $i; ?>">
                                    <input type="radio" 
                                           id="q<?php echo $question['id']; ?>_opt<?php echo $i; ?>" 
                                           name="question_<?php echo $question['id']; ?>" 
                                           value="<?php echo $i; ?>" 
                                           class="mr-3">
                                    <label for="q<?php echo $question['id']; ?>_opt<?php echo $i; ?>" class="flex-1 cursor-pointer">
                                        <?php echo htmlspecialchars($question['option' . $i]); ?>
                                    </label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3 border-t border-gray-200 pt-3">
                            <button type="button" id="submit-quiz" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                Submit Quiz
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <p class="text-sm text-gray-600">No quiz questions available for this section.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Navigation buttons -->
                <div class="nav-buttons">

                    <?php if ($prev_section): ?>
                    <a href="?module_id=<?php echo $selected_module_id; ?>&section_id=<?php echo urlencode($prev_section['id']); ?>" class="nav-button prev">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous: <?php echo htmlspecialchars($prev_section['title']); ?>
                    </a>
                    <?php else: ?>
                    <div></div> <!-- Empty div to maintain flex spacing -->
                    <?php endif; ?>
                        
                    <?php if ($next_section || (!$is_section_completed && $selected_section && !isset($selected_section['is_checkpoint_quiz']))): ?>
                        <button type="button" 
                                class="nav-button next" 
                                id="next-section-btn"
                                data-next-url="<?php echo $next_section ? '?module_id='.$selected_module_id.'&section_id='.urlencode($next_section['id']) : ''; ?>"
                                data-section-id="<?php echo htmlspecialchars($selected_section_id); ?>"
                                data-has-quiz="<?php echo (isset($selected_section['has_quiz']) && $selected_section['has_quiz']) ? '1' : '0'; ?>"
                                data-completed="<?php echo $is_section_completed ? '1' : '0'; ?>">
                                <!-- data-final-quiz-url="< ?php echo ($is_module_completed && $final_quiz) ? '?module_id='.$selected_module_id.'&final_quiz='.$final_quiz['id'] : ''; ?>"> -->
                            <?php 
                            if ($next_section && $is_section_completed) {
                                echo 'Next: '.htmlspecialchars($next_section['title']);
                            } else {
                                echo 'Mark as Complete';
                                if (isset($selected_section['has_quiz']) && $selected_section['has_quiz']) {
                                    echo ' & View Quiz';
                                }
                            }
                            ?>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                    <?php elseif ($is_module_completed && $final_quiz): ?>
                        <a href="?module_id=<?php echo $selected_module_id; ?>&final_quiz=<?php echo $final_quiz['id']; ?>" 
                        class="nav-button next">
                            Take Final Quiz
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 12h14"></path>
                            </svg>
                        </a>
                    <?php endif; ?> 
                    
                        <script>
                           // Replace the Progress tracking functionality section
                            document.addEventListener('DOMContentLoaded', () => {
                                const nextBtn = document.getElementById('next-section-btn');

                                if (!nextBtn) return;

                                nextBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    console.log("Button clicked!");

                                    const sectionId = this.dataset.sectionId;
                                    const nextUrl = this.dataset.nextUrl;
                                    // const finalQuizUrl = this.dataset.finalQuizUrl;
                                    let isCompleted = this.dataset.completed === '1';

                                    if (!sectionId || isCompleted) {
                                        // Already completed → just navigate
                                        window.location.href = nextUrl;
                                        return;
                                    }

                                    // Disable button while processing
                                    this.disabled = true;
                                    this.style.opacity = '0.5';

                                    // Send completion to server
                                    fetch(window.location.href, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `section_completed=1&section_id=${sectionId}`
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        // After marking complete, navigate to next section or final quiz
                                        window.location.href = nextUrl; 
                                    })
                                .catch(err => {
                                    console.error('Error completing section:', err);
                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                    // Still navigate even if request fails
                                    window.location.href = nextUrl;
                                });
                            });
                        });
                        </script>
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center h-full text-center p-4">
                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <h2 class="text-lg font-bold text-gray-900 mb-2">No Section Selected</h2>
                <p class="text-sm text-gray-600">Please select a section from the sidebar to view content.</p>
            </div>
        <?php endif; ?>
        </main>
    </div>
    
    <script>
    // Initialize DOM elements


// Initialize sidebar navigation
(function() {
    // Add styles for smooth transitions
    const styleSheet = document.createElement('style');
    styleSheet.textContent = `
        .part-header {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .part-header:hover {
            background-color: #f3f4f6;
        }
        .part-sections {
            overflow: hidden;
            transition: height 0.3s ease;
        }
        .chevron {
            transition: transform 0.3s ease;
        }
        .section-item {
            padding: 8px 24px 8px 40px;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: #4b5563;
        }
        .section-item:hover {
            background-color: #e5e7eb;
        }
        .section-item.active {
            background-color: #eff6ff;
            color: #2563eb;
        }
        .check-icon {
            transition: color 0.2s ease;
        }
    `;
    document.head.appendChild(styleSheet);

    // Initialize part sections
    console.log('Initializing part sections...');
})();

// Handle final quiz active state
<?php if ($selected_quiz_id): ?>
const finalQuizLink = document.querySelector('a[href*="final_quiz"]');
if (finalQuizLink) {
    // Remove active class from all sections
    document.querySelectorAll('.section-item').forEach(section => {
        section.classList.remove('active');
    });
    // Add active class to final quiz link
    finalQuizLink.classList.add('active');
}
<?php endif; ?>

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

// Quiz functionality
if (document.getElementById('submit-quiz')) {
    const quizOptions = document.querySelectorAll('#quiz-form .quiz-option');
    const submitQuizBtn = document.getElementById('submit-quiz');
    const quizResults = document.getElementById('quiz-results');
    
    // Make entire option clickable
    quizOptions.forEach(option => {
        option.addEventListener('click', () => {
            const radioInput = option.querySelector('input[type="radio"]');
            radioInput.checked = true;
            
            // Remove selected class from all options in the same question
            const questionDiv = option.closest('.quiz-question');
            const options = questionDiv.querySelectorAll('.quiz-option');
            options.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to this option
            option.classList.add('selected');
        });
    });
    
    // Submit quiz button functionality
    submitQuizBtn.addEventListener('click', () => {
        const questions = document.querySelectorAll('#quiz-form .quiz-question');
        let correctAnswers = 0;
        let totalQuestions = questions.length;
        
        questions.forEach(question => {
            const questionId = question.dataset.questionId;
            const selectedOption = document.querySelector(`input[name="question_${questionId}"]:checked`);
            
            if (selectedOption) {
                const correctOption = <?php 
                    // Output JavaScript object with correct answers
                    $correctAnswers = [];
                    if (!empty($quiz_questions)) {
                        foreach ($quiz_questions as $q) {
                            $correctAnswers[$q['id']] = intval($q['correct_answer']);
                        }
                    }
                    echo json_encode($correctAnswers);
                ?>;
                
                const selectedValue = parseInt(selectedOption.value);
                if (selectedValue === correctOption[questionId]) {
                    correctAnswers++;
                    
                    // Highlight correct answer
                    const optionDiv = selectedOption.closest('.quiz-option');
                    optionDiv.classList.add('bg-green-100', 'border-green-500');
                } else {
                    // Highlight incorrect answer
                    const optionDiv = selectedOption.closest('.quiz-option');
                    optionDiv.classList.add('bg-red-100', 'border-red-500');
                    
                    // Highlight the correct answer
                    const correctOptionDiv = question.querySelector(`.quiz-option[data-option="${correctOption[questionId]}"] label`);
                    if (correctOptionDiv) {
                        correctOptionDiv.classList.add('bg-green-100', 'border-green-500');
                    }
                }
            }
        });
        
        // Display results
        const percentageScore = Math.round((correctAnswers / totalQuestions) * 100);
        quizResults.innerHTML = `
            <div class="text-lg font-medium mb-2">Quiz Results</div>
            <p>You answered ${correctAnswers} out of ${totalQuestions} questions correctly.</p>
            <p class="font-bold mt-2">Score: ${percentageScore}%</p>
        `;
        
        // Add appropriate class based on score
        if (percentageScore >= 80) {
            quizResults.classList.add('bg-green-50', 'text-green-800', 'border-green-500');
        } else if (percentageScore >= 60) {
            quizResults.classList.add('bg-yellow-50', 'text-yellow-800', 'border-yellow-500');
        } else {
            quizResults.classList.add('bg-red-50', 'text-red-800', 'border-red-500');
        }
        
        quizResults.classList.remove('hidden');
        
        // Disable form after submission
        submitQuizBtn.disabled = true;
        submitQuizBtn.classList.add('opacity-50', 'cursor-not-allowed');
        
        // Disable all radio buttons
        const allRadios = document.querySelectorAll('#quiz-form input[type="radio"]');
        allRadios.forEach(radio => {
            radio.disabled = true;
        });
        
        // Remove clickable style from options
        quizOptions.forEach(option => {
            option.classList.remove('cursor-pointer');
            option.classList.add('cursor-default');
        });
    });
}

const finalQuizMeta = {
    canRetake: <?php echo $canRetakeFinalQuiz ? 'true' : 'false'; ?>,
    finalQuizId: <?php echo $final_quiz ? intval($final_quiz['id']) : 'null'; ?>,
    retakeButtonLabel: <?php echo json_encode($retake_button_label); ?>,
    hasRetakeOverview: <?php echo $shouldShowQuizOverview ? 'true' : 'false'; ?>,
    latestScore: <?php echo ($quiz_score !== null) ? intval($quiz_score) : 'null'; ?>,
    hasPendingRetake: <?php echo $has_pending_retake ? 'true' : 'false'; ?>,
    moduleId: <?php echo $selected_module_id; ?>
};

const finalQuizFormEl = document.getElementById('final-quiz-form');
let finalQuizResultsEl = document.getElementById('final-quiz-results');
const finalQuizSubheadingEl = document.getElementById('final-quiz-subheading');

if (!finalQuizResultsEl && finalQuizFormEl) {
    finalQuizResultsEl = document.createElement('div');
    finalQuizResultsEl.id = 'final-quiz-results';
    finalQuizResultsEl.className = 'hidden mt-3';
    finalQuizFormEl.insertAdjacentElement('afterend', finalQuizResultsEl);
}

const buildQuizOverviewCard = (score, buttonLabel) => {
    // Calculate total questions and percentage
    const totalQuestions = <?php echo count($final_quiz_questions); ?>;
    const percentage = totalQuestions > 0 ? Math.round((score / totalQuestions) * 100 * 10) / 10 : 0;
    const statusMessage = percentage >= 70
        ? 'Congratulations! You passed this quiz.'
        : 'Keep practicing to improve this score.';
    
    const pendingRetakeAlert = finalQuizMeta.hasPendingRetake
        ? `
            <div class="mb-4 px-4 py-3 rounded bg-blue-100 text-blue-900 border border-blue-200">
                <p class="font-semibold">You have a pending retake available.</p>
                <p class="text-sm mt-1">Click "Start Retake" below to begin your retake attempt.</p>
            </div>
        `
        : '';
    
    const startRetakeButtonHtml = (finalQuizMeta.hasPendingRetake && finalQuizMeta.finalQuizId)
        ? `
            <a href="?module_id=${finalQuizMeta.moduleId}&final_quiz=${finalQuizMeta.finalQuizId}&start_retake=1" class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                Start Retake
            </a>
        `
        : '';
    
    const retakeButtonHtml = (!finalQuizMeta.hasPendingRetake && finalQuizMeta.canRetake && finalQuizMeta.finalQuizId)
        ? `
            <form method="POST" class="inline-block retake-request-form">
                <input type="hidden" name="request_retake" value="1">
                <input type="hidden" name="final_quiz_id" value="${finalQuizMeta.finalQuizId}">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    ${buttonLabel}
                </button>
            </form>
        `
        : '';

    const reviewButtonHtml = `
        <button id="review-quiz-btn" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Review Quiz
        </button>
    `;

    return `
        <div id="quiz-overview-content" class="text-center p-4 bg-green-50 border border-green-200 rounded-md shadow-sm">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-green-900 mb-2">Quiz Already Completed</h3>
            
            <!-- Score Display Section with Progress Bar -->
            <div class="mb-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Score: ${percentage}%</span>
                    <span class="text-sm font-medium text-gray-700">${score} / ${totalQuestions}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="h-3 rounded-full ${percentage >= 70 ? 'bg-green-600' : 'bg-red-600'}" style="width: ${percentage}%"></div>
                </div>
                <p class="text-sm mt-2 ${percentage >= 70 ? 'text-green-700 font-medium' : 'text-red-700 font-medium'}">
                    ${percentage >= 70 
                        ? 'You have passed the exam.' 
                        : 'You have not passed the exam.'}
                </p>
            </div>
            <p class="text-sm text-green-800 mb-3">Thank you for completing this Module.</p>
            ${finalQuizMeta.canRetake ? '<p class="text-sm text-blue-800 mb-3">Retakes are enabled for this module. Click the button below to start another attempt.</p>' : ''}
            ${pendingRetakeAlert}
            <div class="flex flex-wrap justify-center gap-2 mt-3 border-t border-green-200 pt-3">
                <a href="Sassessment.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                    View Assessment History
                </a>
                <a href="Sdashboard.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                    Return to Dashboard
                </a>
                ${reviewButtonHtml}
                ${startRetakeButtonHtml}
                ${retakeButtonHtml}
            </div>
        </div>
    `;
};

const switchToQuizOverview = (score, buttonLabel) => {
    if (!finalQuizResultsEl) return;
    const statusAlert = document.getElementById('retake-status-alert');
    if (statusAlert) statusAlert.remove();
    finalQuizSubheadingEl?.classList.remove('hidden');
    finalQuizResultsEl.innerHTML = buildQuizOverviewCard(score, buttonLabel);
    finalQuizResultsEl.classList.remove('hidden');
    if (finalQuizFormEl) {
        finalQuizFormEl.style.display = 'none';
    }
    
    // Add event listener for review button
    const reviewBtn = document.getElementById('review-quiz-btn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', showQuizReview);
    }
};

const buildQuizReviewView = () => {
    const quizQuestions = <?php echo json_encode($final_quiz_questions); ?>;
    const correctAnswers = <?php echo json_encode(array_column($final_quiz_questions, 'correct_answer', 'id')); ?>;
    
    // Get stored answers from sessionStorage
    const storedAnswers = JSON.parse(sessionStorage.getItem('quiz_answers') || '{}');
    
    let reviewHtml = `
        <div id="quiz-review-content" class="space-y-3">
            <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Quiz Review</h3>
                <button id="back-to-overview-btn" class="px-3 py-1.5 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm font-medium">
                    Back to Overview
                </button>
            </div>
    `;
    
    quizQuestions.forEach((question, index) => {
        const questionId = question.id;
        const selectedAnswer = storedAnswers[questionId] ? parseInt(storedAnswers[questionId]) : null;
        const correctAnswer = parseInt(correctAnswers[questionId]);
        const isCorrect = selectedAnswer === correctAnswer;
        
        reviewHtml += `
            <div class="quiz-review-question bg-white p-4 rounded-md border-2 ${isCorrect ? 'border-green-500' : 'border-red-500'} shadow-sm">
                <div class="flex items-start justify-between mb-2">
                    <h4 class="text-lg font-semibold text-gray-800">Question ${index + 1}</h4>
                    <div class="flex items-center ${isCorrect ? 'text-green-600' : 'text-red-600'}">
                        ${isCorrect ? `
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-semibold">Correct</span>
                        ` : `
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="font-semibold">Incorrect</span>
                        `}
                    </div>
                </div>
                <p class="text-gray-700 mb-4 font-medium">${question.question_text}</p>
                <div class="space-y-2">
        `;
        
        // Display all options - only highlight the user's selected answer
        for (let i = 1; i <= 4; i++) {
            const optionText = question['option' + i];
            const isSelected = selectedAnswer === i;
            const isCorrectOption = correctAnswer === i;
            
            let optionClass = 'p-3 rounded-md border-2 ';
            let iconHtml = '';
            
            if (isSelected && isCorrectOption) {
                // User selected the correct answer
                optionClass += 'bg-green-100 border-green-500';
                iconHtml = '<svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            } else if (isSelected && !isCorrectOption) {
                // User selected an incorrect answer
                optionClass += 'bg-red-100 border-red-500';
                iconHtml = '<svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            } else {
                // Not selected - show as normal option without revealing if it's correct
                optionClass += 'bg-gray-50 border-gray-200';
            }
            
            reviewHtml += `
                <div class="${optionClass} flex items-center">
                    ${iconHtml}
                    <span class="${isSelected ? 'font-semibold' : ''} ${isSelected && !isCorrectOption ? 'text-red-700' : isSelected && isCorrectOption ? 'text-green-700' : 'text-gray-700'}">
                        ${optionText}
                        ${isSelected ? ' <span class="text-xs">(Your Answer)</span>' : ''}
                    </span>
                </div>
            `;
        }
        
        reviewHtml += `
                </div>
            </div>
        `;
    });
    
    reviewHtml += `</div>`;
    
    return reviewHtml;
};

const showQuizReview = () => {
    const reviewHtml = buildQuizReviewView();
    
    // Check if we're showing review from PHP-generated overview or JavaScript-generated overview
    const phpOverviewCard = document.getElementById('quiz-overview-card');
    const jsResultsEl = finalQuizResultsEl;
    let isPhpGenerated = false;
    
    if (phpOverviewCard) {
        // Replace PHP-generated overview card with review
        phpOverviewCard.innerHTML = reviewHtml;
        phpOverviewCard.id = 'quiz-review-container';
        isPhpGenerated = true;
    } else if (jsResultsEl) {
        // Replace JavaScript-generated results with review
        jsResultsEl.innerHTML = reviewHtml;
        jsResultsEl.classList.remove('hidden');
    } else {
        console.error('Could not find container for quiz review');
        return;
    }
    
    // Add event listener for back button
    const backBtn = document.getElementById('back-to-overview-btn');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            if (isPhpGenerated) {
                // PHP-generated: reload page
                window.location.reload();
            } else {
                // JS-generated: switch back to overview
                const score = finalQuizMeta.latestScore ?? 0;
                const buttonLabel = finalQuizMeta.retakeButtonLabel || 'Retake Quiz';
                switchToQuizOverview(score, buttonLabel);
            }
        });
    }
};

if (finalQuizMeta.hasRetakeOverview && finalQuizFormEl && finalQuizResultsEl) {
    switchToQuizOverview(finalQuizMeta.latestScore ?? 0, finalQuizMeta.retakeButtonLabel || 'Retake Quiz');
}

// Checkpoint Quiz Handler - Functions are already defined in the head section
// Only redefine if they don't exist (fallback for edge cases)
if (typeof handleCheckpointQuizSubmit === 'undefined') {
    function handleCheckpointQuizSubmit(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        console.log('handleCheckpointQuizSubmit called (fallback definition)');
        if (typeof submitCheckpointQuiz === 'function') {
            submitCheckpointQuiz();
        } else {
            console.error('submitCheckpointQuiz not available');
        }
    }
}

// Main checkpoint quiz submission function - only define if not already defined in head
if (typeof submitCheckpointQuiz === 'undefined') {
    function submitCheckpointQuiz() {
    const submitCheckpointQuizBtn = document.getElementById('submit-checkpoint-quiz');
    const checkpointQuizForm = document.getElementById('checkpoint-quiz-form');
    const checkpointQuizResults = document.getElementById('checkpoint-quiz-results');
    
    console.log('submitCheckpointQuiz called');
    console.log('Checkpoint quiz elements:', {
        button: submitCheckpointQuizBtn,
        form: checkpointQuizForm,
        results: checkpointQuizResults,
        buttonDisabled: submitCheckpointQuizBtn ? submitCheckpointQuizBtn.disabled : 'button not found'
    });
    
    if (!submitCheckpointQuizBtn) {
        console.error('Submit checkpoint quiz button not found!');
        alert('Error: Submit button not found. Please refresh the page.');
        return;
    }
    
    if (submitCheckpointQuizBtn.disabled) {
        console.warn('Button is disabled, cannot submit');
        return;
    }
    
    if (!checkpointQuizForm) {
        console.error('Checkpoint quiz form not found!');
        alert('Error: Quiz form not found. Please refresh the page.');
        return;
    }
    
    // Get correct answers from PHP
    const checkpointCorrectAnswers = <?php 
        $checkpointCorrectAnswers = [];
        if (!empty($checkpoint_quiz_questions)) {
            foreach ($checkpoint_quiz_questions as $q) {
                $checkpointCorrectAnswers[$q['id']] = intval($q['correct_answer']);
            }
        }
        echo json_encode($checkpointCorrectAnswers);
    ?>;
    
    console.log('Checkpoint quiz initialized. Button disabled:', submitCheckpointQuizBtn.disabled);
    console.log('Checkpoint correct answers:', checkpointCorrectAnswers);
    
    const questions = document.querySelectorAll('#checkpoint-quiz-form .checkpoint-question');
    let correctAnswers = 0;
    let totalQuestions = questions.length;
    let answeredQuestions = 0;
    
    console.log('Total questions found:', totalQuestions);
    
    // Check if all questions are answered
    questions.forEach(question => {
        const questionId = parseInt(question.dataset.questionId);
        const selectedOption = question.querySelector(`input[name="checkpoint_question_${questionId}"]:checked`);
        if (selectedOption) {
            answeredQuestions++;
        }
    });
    
    if (answeredQuestions < totalQuestions) {
        alert(`Please answer all questions before submitting. You have answered ${answeredQuestions} out of ${totalQuestions} questions.`);
        return;
    }
    
    // Calculate score
    questions.forEach(question => {
        const questionId = parseInt(question.dataset.questionId);
        const selectedOption = question.querySelector(`input[name="checkpoint_question_${questionId}"]:checked`);
        
        if (selectedOption) {
            const selectedValue = parseInt(selectedOption.value);
            const correctAnswer = checkpointCorrectAnswers[questionId];
            
            if (correctAnswer && selectedValue === correctAnswer) {
                correctAnswers++;
            }
        }
    });
    
    const percentage = totalQuestions > 0 ? Math.round((correctAnswers / totalQuestions) * 100) : 0;
    
    // Get checkpoint quiz ID and module part ID from PHP
    const checkpointQuizId = <?php echo isset($checkpoint_quiz) && $checkpoint_quiz ? intval($checkpoint_quiz['id']) : 'null'; ?>;
    const checkpointModulePartId = <?php echo isset($checkpoint_module_part_id) && $checkpoint_module_part_id ? intval($checkpoint_module_part_id) : 'null'; ?>;
    const moduleId = <?php echo isset($selected_module_id) && $selected_module_id ? intval($selected_module_id) : 'null'; ?>;
    
    console.log('Checkpoint quiz IDs:', {
        quizId: checkpointQuizId,
        modulePartId: checkpointModulePartId,
        moduleId: moduleId
    });
    
    if (!checkpointQuizId || !checkpointModulePartId || !moduleId) {
        alert('Error: Missing quiz information. Please refresh the page and try again.');
        console.error('Missing quiz information:', {
            checkpointQuizId: checkpointQuizId,
            checkpointModulePartId: checkpointModulePartId,
            moduleId: moduleId
        });
        return;
    }
    
    // Disable submit button
    submitCheckpointQuizBtn.disabled = true;
    submitCheckpointQuizBtn.textContent = 'Submitting...';
    
    // Prepare data to send
    const submitData = {
        action: 'submit_checkpoint_quiz',
        checkpoint_quiz_id: checkpointQuizId,
        module_part_id: checkpointModulePartId,
        module_id: moduleId,
        score: correctAnswers,
        total_questions: totalQuestions,
        percentage: percentage
    };
    
    console.log('Submitting checkpoint quiz:', submitData);
    
    // Send results to server
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(submitData)
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
        // Check content type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Checkpoint quiz response:', data);
        if (data.success) {
            // Show results
            if (checkpointQuizResults) {
                checkpointQuizResults.classList.remove('hidden');
                checkpointQuizResults.innerHTML = `
                    <div class="bg-white border border-green-300 rounded-md p-4">
                        <h3 class="text-lg font-bold mb-2 text-gray-900">Checkpoint Quiz Results</h3>
                        <p class="text-base mb-2">You scored <strong>${correctAnswers}</strong> out of <strong>${totalQuestions}</strong> questions.</p>
                        <p class="text-base mb-2">Percentage: <strong>${percentage}%</strong></p>
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-purple-600 h-2.5 rounded-full" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                        <p class="text-sm text-green-600 mt-3 font-medium">✓ Results saved successfully!</p>
                    </div>
                `;
            }
            
            submitCheckpointQuizBtn.textContent = 'Quiz Submitted';
            submitCheckpointQuizBtn.disabled = true;
            submitCheckpointQuizBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Disable all radio buttons
            const allRadios = document.querySelectorAll('#checkpoint-quiz-form input[type="radio"]');
            allRadios.forEach(radio => {
                radio.disabled = true;
            });
            
            // Reload after 2 seconds to show updated completion status
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alert('Error submitting checkpoint quiz: ' + (data.error || 'Unknown error'));
            submitCheckpointQuizBtn.disabled = false;
            submitCheckpointQuizBtn.textContent = 'Submit Checkpoint Quiz';
        }
    })
    .catch(error => {
        console.error('Checkpoint quiz submission error:', error);
        console.error('Error details:', {
            message: error.message,
            stack: error.stack
        });
        alert('Error submitting checkpoint quiz: ' + error.message + '. Please check the browser console for more details.');
        submitCheckpointQuizBtn.disabled = false;
        submitCheckpointQuizBtn.textContent = 'Submit Checkpoint Quiz';
    });
    }
}

// Attach event listener using multiple methods for reliability
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Setting up checkpoint quiz handler');
    const submitCheckpointQuizBtn = document.getElementById('submit-checkpoint-quiz');
    const checkpointQuizForm = document.getElementById('checkpoint-quiz-form');
    
    if (submitCheckpointQuizBtn && checkpointQuizForm) {
        // Prevent form submission
        checkpointQuizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Form submit prevented');
            return false;
        });
        
        // Add click event listener (in addition to inline onclick)
        submitCheckpointQuizBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Checkpoint quiz submit button clicked! (addEventListener)');
            submitCheckpointQuiz();
        });
        
        console.log('Checkpoint quiz event listener attached successfully');
    } else {
        console.warn('Checkpoint quiz elements not found on DOMContentLoaded:', {
            button: submitCheckpointQuizBtn,
            form: checkpointQuizForm
        });
    }
});

// Also try on window load as fallback
window.addEventListener('load', function() {
    console.log('Window load - Checking checkpoint quiz button');
    const submitCheckpointQuizBtn = document.getElementById('submit-checkpoint-quiz');
    if (submitCheckpointQuizBtn && !submitCheckpointQuizBtn.onclick) {
        console.log('Re-attaching checkpoint quiz handler on window load');
        submitCheckpointQuizBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Checkpoint quiz submit button clicked! (window load handler)');
            submitCheckpointQuiz();
        });
    }
});

// Event delegation as ultimate fallback
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'submit-checkpoint-quiz') {
        console.log('Checkpoint quiz button clicked via event delegation');
        e.preventDefault();
        e.stopPropagation();
        submitCheckpointQuiz();
    }
});

// Add event listener for PHP-generated review button
document.addEventListener('DOMContentLoaded', function() {
    const reviewBtnPhp = document.getElementById('review-quiz-btn-php');
    if (reviewBtnPhp) {
        reviewBtnPhp.addEventListener('click', function() {
            // Check if answers are available in sessionStorage
            const storedAnswers = sessionStorage.getItem('quiz_answers');
            if (!storedAnswers || storedAnswers === '{}') {
                alert('Review is only available for quizzes completed in this session. Please complete the quiz again to review your answers.');
                return;
            }
            showQuizReview();
        });
    }
});

// Final Quiz functionality
if (finalQuizFormEl && finalQuizResultsEl) {
    const form = finalQuizFormEl;
    const submitBtn = document.getElementById('submit-final-quiz');
    const results = finalQuizResultsEl;
    const activeRetakeId = <?php echo json_encode($pending_retake_id); ?>;
    let hasRetakeHistory = <?php echo $has_completed_retake_attempt ? 'true' : 'false'; ?>;

    // Handle quiz option clicks
    form.querySelectorAll('.quiz-option').forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        
            const questionDiv = this.closest('.quiz-question');
            questionDiv.querySelectorAll('.quiz-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');
        });
    });

    // Handle quiz submission
    submitBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        // Validate all questions answered
        const questions = form.querySelectorAll('.quiz-question');
        let answered = 0;
        let correct = 0;
        const total = questions.length;
        
        // Get correct answers
        const correctAnswers = <?php echo json_encode(array_column($final_quiz_questions, 'correct_answer', 'id')); ?>;
        
        // Store user's answers for review
        const userAnswers = {};
        
        questions.forEach(question => {
            const selected = question.querySelector('input[type="radio"]:checked');
            const questionId = question.dataset.questionId;
            
            if (selected) {
                answered++;
                const selectedValue = parseInt(selected.value);
                userAnswers[questionId] = selectedValue;
                
                if (selectedValue === correctAnswers[questionId]) {
                    correct++;
                }
            }

            // Add'tl code for checking
            else
                console.log("Error checking quiz");

        });
        
        // Store answers in sessionStorage for review
        sessionStorage.setItem('quiz_answers', JSON.stringify(userAnswers));
        
        if (answered < total) {
            alert(`Please answer all questions (${answered}/${total} answered)`);
            return;
        }
        
        // Calculate score - send actual number of correct answers, not percentage
        // The percentage will be automatically calculated by database triggers
        const score = correct; // Actual number of correct answers
        
        try {
            // Save score to database
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'submit_final_quiz',
                    score: score, // This is now the actual score (number of correct answers)
                    quiz_id: <?php echo $selected_quiz_id; ?>,
                    module_title: <?php echo json_encode($final_quiz['title'] ?? ''); ?>,
                    retake_id: activeRetakeId
                    // module_title: < ?php echo json_encode($final_quiz['title'] ?? ''); ?>
                })
            });

            if (!response.ok) throw new Error('Failed to save score');
  
            // --- Build wrongQuestions array for Gemini feedback ---
                    const wrongQuestions = [];
                    questions.forEach(question => {
                        const questionId = question.dataset.questionId;
                        const selected = question.querySelector('input[type="radio"]:checked');
                        const correctAnswers = <?php echo json_encode(array_column($final_quiz_questions, 'correct_answer', 'id')); ?>;
                        const correctIndex = correctAnswers[questionId];

                        if (selected && parseInt(selected.value) !== correctIndex) {
                            const qText = question.querySelector('h3').innerText.trim();
                            const correctOptionEl = question.querySelector(`.quiz-option[data-option="${correctIndex}"] label`);
                            const correctText = correctOptionEl ? correctOptionEl.innerText.trim() : null;

                            wrongQuestions.push({
                                question_text: qText,
                                correct_answer_text: correctText
                            });
                        }
                    });

                    // --- Call Gemini service to generate feedback ---
                   if (score < 70) {
                    console.log("🧠 Low score detected (" + score + "%) — sending to Gemini for feedback...");

                    fetch('gemini_service.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: <?php echo $user_id; ?>,
                            module_id: <?php echo $selected_module_id; ?>,
                            quiz_id: <?php echo $selected_quiz_id; ?>,
                            score: score,
                            module_title: <?php echo json_encode($final_quiz['title'] ?? ''); ?>,
                            wrong_questions: wrongQuestions
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ Gemini feedback saved successfully');
                            console.log(data.ai_feedback);
                        } else {
                            console.warn('⚠️ Gemini feedback failed:', data.error);
                        }
                    })
                    .catch(err => console.error('Error calling Gemini service:', err));
                    } else {
                         console.log("🎉 Good score (" + score + "%) — skipping Gemini feedback.");
                    }
        //end Gemini section

            // Show completion message
            const isRetakeSubmission = activeRetakeId !== null && activeRetakeId !== undefined;
            const retakeButtonText = (isRetakeSubmission || hasRetakeHistory) ? 'Retake Again' : (finalQuizMeta.retakeButtonLabel || 'Retake Quiz');
            const statusAlert = document.getElementById('retake-status-alert');
            if (statusAlert) {
                statusAlert.remove();
            }

            if (isRetakeSubmission) {
                finalQuizMeta.hasRetakeOverview = true;
                finalQuizMeta.latestScore = score;
                finalQuizMeta.retakeButtonLabel = retakeButtonText;
                switchToQuizOverview(score, retakeButtonText);
                hasRetakeHistory = true;
            } else {
                finalQuizMeta.hasRetakeOverview = true;
                finalQuizMeta.latestScore = score;
                finalQuizMeta.retakeButtonLabel = retakeButtonText;
                switchToQuizOverview(score, retakeButtonText);
            }

            results.classList.remove('hidden');
            form.style.display = 'none';

        } catch (error) {
            console.error('Error:', error);
            alert('Error saving your score. Please try again.');
        }
    });
}

document.querySelectorAll('form.retake-request-form').forEach(formEl => {
    formEl.addEventListener('submit', () => {
        const alert = document.getElementById('retake-status-alert');
        if (alert) {
            alert.remove();
        }
    });
});

// Add styles to fix progress bar appearance
const additionalStyles = `
    .progress-container {
        overflow: hidden;
        border-radius: 2px;
    }
    .progress-bar {
        transition: width 0.5s ease-in-out;
    }
    .section-item {
        position: relative;
    }
    .section-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background-color: #3B82F6;
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// Initialize eye tracking for module content
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Module content page loaded - initializing eye tracking...');
    
    // Get module info from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const moduleId = urlParams.get('module_id') || 'unknown_module';
    const sectionId = urlParams.get('section_id') || 'unknown_section';
    
    // Get module title from page
    const moduleTitle = document.querySelector('.text-2xl.font-bold')?.textContent || 'Module Content';
    
    console.log(`📚 Module: ${moduleTitle}`);
    console.log(`🆔 Module ID: ${moduleId}`);
    console.log(`📄 Section ID: ${sectionId}`);
    
    // Simple live feed function that mimics the working test file
    let moduleFrameUpdateInterval;
    const MODULE_API_BASE = 'http://127.0.0.1:5000/api';
    
    async function updateModuleVideoFrame() {
        try {
            const response = await fetch(`${MODULE_API_BASE}/frame`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.hasFrame && data.frameData) {
                    // Try to find the video element (support multiple possible IDs)
                    const videoElement = document.getElementById('tracking-video') || 
                                       document.querySelector('#tracking-video') ||
                                       document.querySelector('img[alt*="camera"]') ||
                                       document.querySelector('img[alt*="Camera"]');
                    
                    if (videoElement) {
                        // Ensure the image is visible
                        videoElement.style.display = 'block';
                        videoElement.style.opacity = '1';
                        
                        // Set up load/error handlers
                        videoElement.onload = () => {
                            videoElement.style.opacity = '1';
                            if (Math.random() < 0.05) { // 5% chance to avoid spam
                                console.log('🖼️ Video frame loaded successfully');
                            }
                        };
                        
                        videoElement.onerror = () => {
                            console.warn('⚠️ Frame load error - invalid image data');
                            videoElement.style.opacity = '0.5';
                        };
                        
                        // Update the image source
                        videoElement.src = data.frameData;
                        
                        // Log success occasionally (same as test)
                        if (Math.random() < 0.1) { // 10% chance
                            console.log(`✅ Module part frame updated (${data.frameData.substring(0, 50)}...)`);
                            console.log(`📺 Video element found and updated:`, videoElement);
                        }
                    } else {
                        console.error('❌ Could not find video element with any of the expected selectors');
                        console.log('🔍 Available elements:', {
                            'tracking-video': document.getElementById('tracking-video'),
                            'all_imgs': document.querySelectorAll('img').length,
                            'live-feed-container': document.getElementById('live-feed-container')
                        });
                    }
                } else {
                    if (Math.random() < 0.1) { // 10% chance to avoid spam
                        console.log(`⚠️ Module part no frame data: hasFrame=${data.hasFrame}, camera_available=${data.camera_available}, tracking_state=${data.tracking_state}`);
                    }
                }
            } else {
                console.log(`❌ Module part frame request failed: ${response.status}`);
            }
        } catch (error) {
            console.log(`❌ Module part frame update error: ${error.message}`);
        }
    }
    
    async function startModuleTracking() {
        try {
            console.log('🚀 Starting module part eye tracking...');
            
            // Prepare tracking data with required parameters
            const trackingData = {
                user_id: <?php echo json_encode($user_id); ?>,
                module_id: <?php echo json_encode($selected_module_id); ?>,
                section_id: <?php echo json_encode($selected_section_id ?? null); ?>
            };
            
            console.log('📤 Sending tracking data:', trackingData);
            
            // Validate that we have the required data
            if (!trackingData.user_id || !trackingData.module_id) {
                console.error('❌ Missing required tracking data:', trackingData);
                return;
            }
            
            const response = await fetch(`${MODULE_API_BASE}/start_tracking`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(trackingData)
            });
            
            const responseData = await response.json();
            console.log('📨 Server response:', responseData);
            
            if (response.ok && responseData.success) {
                console.log('✅ Module part tracking started successfully');
                
                // Show the live feed container
                const feedContainer = document.getElementById('live-feed-container');
                if (feedContainer) {
                    feedContainer.style.display = 'block';
                    feedContainer.style.visibility = 'visible';
                    feedContainer.style.opacity = '1';
                    console.log('📺 Live feed container shown');
                } else {
                    console.error('❌ Live feed container not found!');
                }
                
                // Ensure video element is visible
                const videoElement = document.getElementById('tracking-video');
                if (videoElement) {
                    videoElement.style.display = 'block';
                    videoElement.style.visibility = 'visible';
                    videoElement.style.opacity = '1';
                    console.log('📹 Video element made visible');
                }
                
                // Start updating the video frame
                moduleFrameUpdateInterval = setInterval(updateModuleVideoFrame, 1000 / 15); // 15 FPS
            } else {
                console.error('❌ Failed to start module part tracking:', responseData.error || response.statusText);
            }
        } catch (error) {
            console.error('❌ Error starting module part tracking:', error);
        }
    }
    
    function stopModuleTracking() {
        console.log('🛑 Stopping module part eye tracking...');
        
        // Clear the frame update interval
        if (moduleFrameUpdateInterval) {
            clearInterval(moduleFrameUpdateInterval);
            moduleFrameUpdateInterval = null;
        }
        
        // Hide the live feed container
        const feedContainer = document.getElementById('live-feed-container');
        if (feedContainer) {
            feedContainer.style.display = 'none';
        }
        
        // Stop tracking on the server
        fetch(`${MODULE_API_BASE}/stop_tracking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        }).then(response => response.json())
          .then(data => {
              console.log('✅ Tracking stopped:', data);
          })
          .catch(error => {
              console.error('❌ Error stopping tracking:', error);
          });
    }
    
    // Start tracking when the module content is loaded
    startModuleTracking();
});
    </script>
</body>
</html>