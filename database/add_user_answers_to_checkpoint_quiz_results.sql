-- SQL Query to add user_answers column to checkpoint_quiz_results table
-- Run this in your XAMPP phpMyAdmin or MySQL command line
-- Make sure you're using the 'elearn_db' database

USE elearn_db;

-- Add user_answers column to store JSON of user's answers
ALTER TABLE `checkpoint_quiz_results` 
ADD COLUMN `user_answers` JSON NULL AFTER `percentage`;

