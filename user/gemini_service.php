<?php
// gemini_service.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Read POST body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}

// Required fields
$user_id = intval($input['user_id'] ?? 0);
$module_id = intval($input['module_id'] ?? 0);
$quiz_id = intval($input['quiz_id'] ?? 0);
$score = intval($input['score'] ?? 0);
$wrong_questions_input = $input['wrong_questions'] ?? []; // expected array of strings or objects
$module_title = $input['module_title'] ?? '';

// Basic validation
if (!$user_id || !$module_id || !$quiz_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user_id, module_id or quiz_id']);
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
try {
    $db_conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if section column exists
$check_section = $db_conn->query("SHOW COLUMNS FROM users LIKE 'section'");
$has_section = $check_section && $check_section->num_rows > 0;

if ($has_section) {
    $user_query = "SELECT first_name, last_name, section FROM users WHERE id = ?";
} else {
    $user_query = "SELECT first_name, last_name FROM users WHERE id = ?";
}

$user_stmt = $db_conn->prepare($user_query);
if (!$user_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare user query: ' . $db_conn->error]);
    $db_conn->close();
    exit;
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

$student_name = "Student";
$student_section = "";

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $first_name = trim($user_data['first_name'] ?? '');
    $last_name = trim($user_data['last_name'] ?? '');
    $student_name = trim($first_name . ' ' . $last_name);
    if (empty($student_name)) {
        $student_name = "Student";
    }
    $student_section = $has_section ? trim($user_data['section'] ?? '') : '';
}

$user_stmt->close();

// Calculate total questions and percentage for the quiz
$total_questions = 0;
$score_percentage = 0;
$total_questions_query = $db_conn->prepare("SELECT COUNT(*) as total FROM final_quiz_questions WHERE quiz_id = ?");
if ($total_questions_query) {
    $total_questions_query->bind_param("i", $quiz_id);
    $total_questions_query->execute();
    $total_result = $total_questions_query->get_result();
    if ($total_result && $total_result->num_rows > 0) {
        $total_data = $total_result->fetch_assoc();
        $total_questions = intval($total_data['total']);
        if ($total_questions > 0) {
            $score_percentage = round(($score / $total_questions) * 100, 2);
        }
    }
    $total_questions_query->close();
}

$db_conn->close();

// Load GEMINI API key from .env located in same directory
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '.env file not found at ' . $envPath]);
    exit;
}
$env = parse_ini_file($envPath);
$apiKey = $env['GEMINI_API_KEY'] ?? null;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'GEMINI_API_KEY missing in .env']);
    exit;
}

// Normalize wrong_questions into array of objects {question_text, correct_answer_text (optional)}
$wrongQuestionsNormalized = [];
if (is_array($wrong_questions_input)) {
    foreach ($wrong_questions_input as $item) {
        if (is_string($item)) {
            $wrongQuestionsNormalized[] = [
                'question_text' => $item,
                'correct_answer_text' => null
            ];
        } elseif (is_array($item) || is_object($item)) {
            $qtext = $item['question_text'] ?? $item->question_text ?? '';
            $cans = $item['correct_answer_text'] ?? $item->correct_answer ?? $item->correct_answer_text ?? null;
            $wrongQuestionsNormalized[] = [
                'question_text' => $qtext,
                'correct_answer_text' => $cans
            ];
        }
    }
}

// Build readable wrong questions block for prompt
$wrongBlockLines = [];
foreach ($wrongQuestionsNormalized as $idx => $q) {
    $num = $idx + 1;
    $line = "{$num}. " . trim($q['question_text']);
    if (!empty($q['correct_answer_text'])) {
        $line .= " (Correct: " . trim($q['correct_answer_text']) . ")";
    }
    $wrongBlockLines[] = $line;
}
$wrongBlock = count($wrongBlockLines) ? implode("\n", $wrongBlockLines) : "None listed.";

// Compose prompt for Gemini with personalized information and score fraction
$section_info = !empty($student_section) ? " from Section {$student_section}" : "";
$greeting = "Dear {$student_name}{$section_info},";

$prompt = "You are EyeLearn Mentor, a professional and encouraging educational tutor. {$greeting}\n\n"
    . "You have completed the final quiz for the module"
    . ($module_title ? " titled \"{$module_title}\"" : "")
    . " with a score of {$score}/{$total_questions} ({$score_percentage}%).\n\n"
    . "The following questions were answered incorrectly:\n\n{$wrongBlock}\n\n"
    . "Please provide personalized feedback that is formal yet easy to understand. Address the student by their name ({$student_name})"
    . (!empty($student_section) ? " and acknowledge their section ({$student_section})" : "")
    . " in your response. Your feedback should include:\n"
    . "- A professional greeting addressing the student by name" . (!empty($student_section) ? " and section" : "") . "\n"
    . "- A brief acknowledgment of their effort and participation\n"
    . "- Clear, easy-to-understand explanations or corrections for each incorrect answer (one or two sentences per item)\n"
    . "- One practical suggestion or micro-activity the student can do next, preferably with a YouTube video link related to the topics\n"
    . "- A closing statement that is encouraging and supportive\n\n"
    . "Guidelines:\n"
    . "- Keep the response between 150-300 words\n"
    . "- Use formal but accessible language that a student can easily understand\n"
    . "- Generate the feedback in HTML format with proper <p>, <ul>, <li>, <a href>, and <strong> tags instead of Markdown\n"
    . "- Use the title of the YouTube video or reference as the hyperlink text\n"
    . "- Only suggest real, existing YouTube videos - do not create fake or made-up links\n"
    . "- If unsure about a specific video, generate a YouTube search link that includes the video title and topic so the student can click it to search directly\n"
    . "- Maintain a professional, supportive tone throughout the feedback\n";

// Call Gemini 2.5 Flash
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($apiKey);

$payload = [
    "contents" => [
        ["parts" => [["text" => $prompt]]]
    ],
    // optionally tune parameters here (safety, temperature etc.) if needed
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlErr]);
    exit;
}

$result = json_decode($response, true);
if (!is_array($result)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Invalid response from Gemini', 'raw' => $response]);
    exit;
}

// Extract text safely
$aiFeedback = '';
if (!empty($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiFeedback = $result['candidates'][0]['content']['parts'][0]['text'];
} elseif (!empty($result['error']['message'])) {
    // Handle overloaded or error cases gracefully
    $aiFeedback = "⚠️ Sorry, the AI tutor was unavailable at the moment (" .
        htmlspecialchars($result['error']['message']) . 
        "). Please try again later — your quiz results were saved successfully.";
} else {
    $aiFeedback = "⚠️ AI service returned an unexpected response. Please retry later.";
}

// Use centralized database connection
if (!isset($db_conn)) {
    require_once __DIR__ . '/../database/db_connection.php';
    try {
        $db = getMysqliConnection();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB connect error: ' . $e->getMessage()]);
        exit;
    }
} else {
    $db = $db_conn; // Reuse existing connection
}

// Store wrong_questions as JSON (full text)
$wrongQuestionsJson = json_encode($wrongQuestionsNormalized, JSON_UNESCAPED_UNICODE);

/// Ensure only one feedback per user per module
// You must have this index once (run once in MySQL):
// ALTER TABLE ai_recommendations ADD UNIQUE KEY unique_user_module (user_id, module_id);

$stmt = $db->prepare("
    INSERT INTO ai_recommendations (user_id, module_id, quiz_id, score, wrong_questions, ai_feedback, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        quiz_id = VALUES(quiz_id),
        score = VALUES(score),
        wrong_questions = VALUES(wrong_questions),
        ai_feedback = VALUES(ai_feedback),
        created_at = NOW()
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $db->error]);
    $db->close();
    exit;
}
$stmt->bind_param("iiiiss", $user_id, $module_id, $quiz_id, $score, $wrongQuestionsJson, $aiFeedback);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    $db->close();
    exit;
}

// Return success (doesn't matter if insert or update)
echo json_encode([
    'success' => true,
    'ai_feedback' => $aiFeedback,
    'message' => 'Feedback saved successfully (inserted or updated)'
]);

$stmt->close();
$db->close();
exit;
?>
