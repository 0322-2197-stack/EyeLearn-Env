-- SQL Query to create missing tables for Module Management
-- Run this in your XAMPP phpMyAdmin or MySQL command line
-- Make sure you're using the 'elearn_db' database

USE elearn_db;

-- Create checkpoint_quizzes table if it doesn't exist
CREATE TABLE IF NOT EXISTS `checkpoint_quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_part_id` int(11) NOT NULL,
  `quiz_title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_part_quiz` (`module_part_id`),
  KEY `idx_module_part_id` (`module_part_id`),
  CONSTRAINT `fk_checkpoint_quizzes_module_part` FOREIGN KEY (`module_part_id`) REFERENCES `module_parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create checkpoint_quiz_questions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `checkpoint_quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checkpoint_quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_answer` int(11) NOT NULL,
  `question_order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checkpoint_quiz_id` (`checkpoint_quiz_id`),
  CONSTRAINT `fk_checkpoint_quiz_questions_quiz` FOREIGN KEY (`checkpoint_quiz_id`) REFERENCES `checkpoint_quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: The following ALTER statements should only be run if the columns don't exist
-- Check your database structure first before running these

-- Add allow_retake column to final_quizzes (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM final_quizzes LIKE 'allow_retake';
-- If no results, then run:
-- ALTER TABLE `final_quizzes` ADD COLUMN `allow_retake` tinyint(1) NOT NULL DEFAULT 0;

-- Add status column to modules (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM modules LIKE 'status';
-- If no results, then run:
-- ALTER TABLE `modules` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'draft';

-- Add image_path column to modules (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM modules LIKE 'image_path';
-- If no results, then run:
-- ALTER TABLE `modules` ADD COLUMN `image_path` varchar(500) DEFAULT NULL;

-- Add created_at column to modules (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM modules LIKE 'created_at';
-- If no results, then run:
-- ALTER TABLE `modules` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();

-- Add created_at column to module_parts (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM module_parts LIKE 'created_at';
-- If no results, then run:
-- ALTER TABLE `module_parts` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();

-- Add created_at column to final_quizzes (only if it doesn't exist)
-- Check first: SHOW COLUMNS FROM final_quizzes LIKE 'created_at';
-- If no results, then run:
-- ALTER TABLE `final_quizzes` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();
