<?php
// admin/database/get_sections.php - Get all unique sections from students
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Check if section column exists
    $hasSection = false;
    $columnsResult = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
    if ($columnsResult && $columnsResult->num_rows > 0) {
        $hasSection = true;
    }
    
    $sections = [];
    
    if ($hasSection) {
        // Get all unique sections from users table where role is student
        $query = "SELECT DISTINCT section 
                  FROM users 
                  WHERE role = 'student' AND section IS NOT NULL AND section != '' 
                  ORDER BY section ASC";
        
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row['section'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'sections' => $sections,
        'has_section_column' => $hasSection
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

