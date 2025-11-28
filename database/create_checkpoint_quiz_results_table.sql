-- SQL Query to create checkpoint_quiz_results table
-- Run this in your XAMPP phpMyAdmin or MySQL command line
-- Make sure you're using the 'elearn_db' database

USE elearn_db;

-- Create checkpoint_quiz_results table if it doesn't exist
CREATE TABLE IF NOT EXISTS `checkpoint_quiz_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `checkpoint_quiz_id` int(11) NOT NULL,
  `module_part_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `completion_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_checkpoint_results_user` (`user_id`, `module_id`, `checkpoint_quiz_id`),
  KEY `idx_user_module_part` (`user_id`, `module_part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





