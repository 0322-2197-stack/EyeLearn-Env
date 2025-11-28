<?php
/**
 * Create Missing Users Table on Railway
 */

echo "Creating missing 'users' table on Railway...\n\n";

// Load environment variables
require_once __DIR__ . '/user/load_env.php';

// Create PDO connection directly
try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Railway database\n\n";
    
    // Create users table
    echo "Creating users table...\n";
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `first_name` varchar(50) NOT NULL,
      `last_name` varchar(50) NOT NULL,
      `email` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      `gender` enum('Male','Female','Other') NOT NULL,
      `section` varchar(50) DEFAULT NULL,
      `role` enum('admin','student') NOT NULL,
      `profile_img` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `camera_agreement_accepted` tinyint(1) DEFAULT 0,
      `camera_agreement_date` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($createTableSQL);
    echo "✓ Users table created successfully\n\n";
    
    // Insert default admin user
    echo "Inserting default admin user...\n";
    $insertAdminSQL = "
    INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `gender`, `section`, `role`, `profile_img`, `created_at`, `updated_at`, `camera_agreement_accepted`, `camera_agreement_date`) 
    VALUES (1, 'Super', 'Admin', 'admin@admin.eyelearn', '\$2y\$10\$5eql26ue0JmbvS6AAIQr/.pL8njF47sQ/.lDScg9/Gb..M.iZG1Ty', 'Male', NULL, 'admin', 'default.png', '2025-04-21 15:01:17', '2025-04-21 16:07:51', 0, NULL)
    ON DUPLICATE KEY UPDATE id=id;
    ";
    
    $pdo->exec($insertAdminSQL);
    echo "✓ Admin user inserted\n\n";
    
    // Insert student users
    echo "Inserting student users...\n";
    $insertStudentsSQL = "
    INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `gender`, `section`, `role`, `profile_img`, `created_at`, `updated_at`, `camera_agreement_accepted`, `camera_agreement_date`) 
    VALUES 
    (31, 'Mark Aljerick', 'De Castro', '0322-2068@lspu.edu.ph', '\$2y\$10\$7O.GmiH3CE9/4Rb9qOKtcutk7FWSfyTOq9X03r5sOb24Q2ltz86qW', 'Male', 'BSINFO-1A', 'student', NULL, '2025-11-23 14:28:37', '2025-11-28 01:53:36', 1, '2025-11-28 09:53:36'),
    (32, 'Vonn Annilov', 'Cabajes', '0322-2197@lspu.edu.ph', '\$2y\$10\$pNlcZOVSctPbzmIudYe3geVGl1aK7CcYGBVnAcFkdsWHXmCus4td2', 'Female', 'BSINFO-1A', 'student', NULL, '2025-11-24 06:22:55', '2025-11-24 06:23:03', 1, '2025-11-24 14:23:03')
    ON DUPLICATE KEY UPDATE id=id;
    ";
    
    $pdo->exec($insertStudentsSQL);
    echo "✓ Student users inserted\n\n";
    
    // Also create user_module_progress table
    echo "Creating user_module_progress table...\n";
    $createProgressTableSQL = "
    CREATE TABLE IF NOT EXISTS `user_module_progress` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `module_id` int(11) NOT NULL,
      `completed_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_sections`)),
      `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `completed_checkpoint_quizzes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_checkpoint_quizzes`)),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($createProgressTableSQL);
    echo "✓ user_module_progress table created\n\n";
    
    // Verify tables
    echo "Verifying tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('users', $tables)) {
        echo "✓ users table exists\n";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        echo "  Total users: $count\n";
    } else {
        echo "✗ users table STILL missing!\n";
    }
    
    if (in_array('user_module_progress', $tables)) {
        echo "✓ user_module_progress table exists\n";
    }
    
    echo "\n✅ All missing tables created successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
