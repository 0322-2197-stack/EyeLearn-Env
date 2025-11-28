<?php
session_start();
header('Content-Type: application/json');

// Connect to DB
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Get the latest quiz result for each quiz_id with module information and total questions
$query = "
    SELECT 
        qr.id,
        qr.quiz_id,
        qr.module_id,
        qr.score,
        qr.completion_date,
        m.title AS module_title,
        fq.title AS quiz_title,
        COUNT(fqq.id) AS total_questions
    FROM quiz_results qr
    JOIN modules m ON qr.module_id = m.id
    LEFT JOIN final_quizzes fq ON qr.quiz_id = fq.id
    LEFT JOIN final_quiz_questions fqq ON qr.quiz_id = fqq.quiz_id
    INNER JOIN (
        SELECT quiz_id, MAX(completion_date) AS max_date
        FROM quiz_results
        WHERE user_id = ?
        GROUP BY quiz_id
    ) latest ON qr.quiz_id = latest.quiz_id AND qr.completion_date = latest.max_date
    WHERE qr.user_id = ?
    GROUP BY qr.id, qr.quiz_id, qr.module_id, qr.score, qr.completion_date, m.title, fq.title
    ORDER BY qr.completion_date DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$quizzes = [];
while ($row = $result->fetch_assoc()) {
    $quizzes[] = [
        'id' => $row['id'],
        'quiz_id' => $row['quiz_id'],
        'module_id' => $row['module_id'],
        'module_title' => $row['module_title'],
        'quiz_title' => $row['quiz_title'] ?: 'Final Quiz',
        'score' => intval($row['score']),
        'total_questions' => intval($row['total_questions']),
        'completion_date' => $row['completion_date']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'quizzes' => $quizzes
]);
?>

