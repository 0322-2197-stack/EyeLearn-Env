-- SQL Query to add 'section' column to users table
-- Run this query in phpMyAdmin or your MySQL client

ALTER TABLE `users` 
ADD COLUMN `section` VARCHAR(50) NULL AFTER `gender`;


