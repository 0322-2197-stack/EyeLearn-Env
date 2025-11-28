-- Run this script manually in phpMyAdmin or MySQL Workbench
-- Or use the command: mysql -u root elearn_db < this_file.sql

USE elearn_db;

-- Step 1: Migrate existing data from old column to new column
UPDATE eye_tracking_analytics 
SET 
    total_focused_time = total_focus_time,
    total_unfocused_time = 0,
    focus_percentage = 100.00
WHERE 
    total_focus_time > 0 
    AND (total_focused_time IS NULL OR total_focused_time = 0);

-- Step 2: Verify migration
SELECT 
    'Migration Results' as info,
    COUNT(*) as total_rows,
    SUM(CASE WHEN total_focus_time > 0 THEN 1 ELSE 0 END) as rows_with_old_data,
    SUM(CASE WHEN total_focused_time > 0 THEN 1 ELSE 0 END) as rows_with_new_data,
    SUM(total_focus_time) as sum_old_column,
    SUM(total_focused_time) as sum_new_column
FROM eye_tracking_analytics;
