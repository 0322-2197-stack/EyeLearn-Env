<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    $moduleId = $_POST['module_id'];
    $title = $_POST['module_title'];
    $description = $_POST['module_description'];
    $imagePath = $_POST['existing_image']; // Default to the existing image

    // Handle file upload if a new image is provided
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
        $fullPath = $uploadDir . $filename;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['module_image']['tmp_name'], $fullPath)) {
            // Use absolute path from web root for consistency
            $imagePath = '/capstone/modulephotoshow/' . $filename;

            // Set proper permissions
            chmod($fullPath, 0644);

            // Delete old image if it exists and is different
            if (!empty($_POST['existing_image']) && $_POST['existing_image'] !== $imagePath) {
                $oldPath = __DIR__ . '/../../' . ltrim($_POST['existing_image'], '/');
                if (file_exists($oldPath) && strpos($oldPath, 'modulephotoshow') !== false) {
                    @unlink($oldPath);
                }
            }
        } else {
            $_SESSION['error'] = "Error uploading file to modulephotoshow";
            header("Location: ../Amodule.php");
            exit;
        }
    }

    // Use centralized database connection
    require_once __DIR__ . '/../../database/db_connection.php';
    try {
        $conn = getMysqliConnection();
    } catch (Exception $e) {
        $_SESSION['error'] = "Connection failed: " . $e->getMessage();
        header("Location: ../Amodule.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE modules SET title = ?, description = ?, image_path = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Prepare failed: " . $conn->error;
        header("Location: ../Amodule.php");
        exit;
    }

    $stmt->bind_param("sssi", $title, $description, $imagePath, $moduleId);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Module updated successfully!";
        header("Location: ../Amodule.php?update=success");
    } else {
        $_SESSION['error'] = "Error updating module: " . $stmt->error;
        header("Location: ../Amodule.php");
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../Amodule.php");
    exit;
}
?>