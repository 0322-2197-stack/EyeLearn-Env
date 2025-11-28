<?php
// Use centralized database connection
require_once __DIR__ . '/database/db_connection.php';

try {
    $pdo = getPDOConnection();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if user exists
function userExists($email, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Function to register new user (only for students)
function registerUser($firstName, $lastName, $email, $password, $gender, $section, $pdo) {
    // Validate email format - only allow student emails
    if (preg_match('/@admin\.eyelearn$/', $email)) {
        return false; // Prevent registration of admin emails
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, gender, section, role, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, 'student', NOW())");
    return $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $gender, $section]);
}


// Function to authenticate user with email format validation
function authenticateUser($email, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role, gender, camera_agreement_accepted FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

// Function to createusersession 
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}


// Initialize or resume session
session_start();
?>