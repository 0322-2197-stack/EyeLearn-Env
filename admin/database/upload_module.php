<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    $_SESSION['error'] = "Database connection failed: " . $e->getMessage();
    header("Location: ../Amodule.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_module'])) {
    // Validate required fields
    if (empty($_POST['module_title']) || empty($_POST['module_description'])) {
        $_SESSION['error'] = "Module title and description are required";
        header("Location: ../Amodule.php");
        exit;
    }

    // Get form data
    $title = $conn->real_escape_string(trim($_POST['module_title']));
    $description = $conn->real_escape_string(trim($_POST['module_description']));
    $imagePath = '';
    $status = 'draft';

    // Handle file upload
    if (isset($_FILES['module_image']) && $_FILES['module_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../modulephotoshow/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error'] = "Failed to create modulephotoshow directory";
                header("Location: ../Amodule.php");
                exit;
            }
        }

        // Validate file using finfo for MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $_FILES['module_image']['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = "Only JPG, PNG, GIF, or WEBP images are allowed";
            header("Location: ../Amodule.php");
            exit;
        }

        // Generate unique filename with proper sanitization
        $fileExt = strtolower(pathinfo($_FILES['module_image']['name'], PATHINFO_EXTENSION));
        $filename = 'module_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
        $targetPath = $uploadDir . $filename;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['module_image']['tmp_name'], $targetPath)) {
            // Use absolute path from web root
            $imagePath = '/capstone/modulephotoshow/' . $filename;
            
            // Set proper permissions
            chmod($targetPath, 0644);
        } else {
            $_SESSION['error'] = "Error uploading file to modulephotoshow";
            header("Location: ../Amodule.php");
            exit;
        }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO modules (title, description, image_path, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) {
        $_SESSION['error'] = "Prepare failed: " . $conn->error;
        header("Location: ../Amodule.php");
        exit;
    }
    
    $stmt->bind_param("ssss", $title, $description, $imagePath, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Module added successfully!";
    } else {
        $_SESSION['error'] = "Error adding module: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();

    header("Location: ../Amodule.php");
    exit();
}

header("Location: ../Amodule.php");
exit();
?>