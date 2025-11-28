-- Railway MySQL Minimal Schema
-- Copy and paste this into Railway MySQL Query tab

-- Core Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modules Table
CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eye Tracking Sessions (MAIN TABLE for data collection)
CREATE TABLE IF NOT EXISTS `eye_tracking_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `total_time_seconds` int(11) DEFAULT 0,
  `focused_time_seconds` int(11) DEFAULT 0,
  `unfocused_time_seconds` int(11) DEFAULT 0,
  `session_type` enum('viewing','pause','resume','cv_tracking','test') DEFAULT 'viewing',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session_data` text,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eye Tracking Analytics (for dashboard)
CREATE TABLE IF NOT EXISTS `eye_tracking_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `total_focus_time` int(11) DEFAULT 0,
  `total_focused_time` int(11) DEFAULT 0,
  `total_unfocused_time` int(11) DEFAULT 0,
  `focus_percentage` decimal(5,2) DEFAULT 0.00,
  `session_count` int(11) DEFAULT 0,
  `average_session_time` int(11) DEFAULT 0,
  `max_continuous_time` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_section_date` (`user_id`,`module_id`,`section_id`,`date`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Progress
CREATE TABLE IF NOT EXISTS `user_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module` (`user_id`,`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a test user (password: password123)
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `gender`) 
VALUES (1, 'Test', 'User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Male')
ON DUPLICATE KEY UPDATE id=id;

-- Insert sample modules
INSERT INTO `modules` (`id`, `title`, `description`) VALUES
(1, 'Introduction to Programming', 'Learn the basics of programming'),
(22, 'Web Development Basics', 'HTML, CSS, and JavaScript fundamentals')
ON DUPLICATE KEY UPDATE id=id;

-- Success message
SELECT 'Schema created successfully!' as status, 
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()) as table_count;
