<?php
session_start();
header('Content-Type: application/json');

// Verify user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Create images directory if it doesn't exist
$base_dir = __DIR__ . '/../../images';
$uploads_dir = $base_dir . '/content';

// Ensure directory structure exists
if (!is_dir($base_dir)) {
    if (!mkdir($base_dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create images directory']);
        exit;
    }
}

if (!is_dir($uploads_dir)) {
    if (!mkdir($uploads_dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create uploads directory']);
        exit;
    }
}

// Handle file upload
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];

// Validate file error
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

// Validate file exists
if (!is_uploaded_file($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid upload']);
    exit;
}

// Validate MIME type using finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type: ' . $mime_type]);
    exit;
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024;
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File exceeds 5MB limit']);
    exit;
}

// Generate unique filename with original extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'img_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$filepath = $uploads_dir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Verify file was written
if (!file_exists($filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'File verification failed']);
    exit;
}

// Set proper permissions
chmod($filepath, 0644);

// Return absolute path from web root for consistent access
$web_path = '/capstone/images/content/' . $filename;

echo json_encode([
    'success' => true,
    'location' => $web_path
]);
exit;
?>
