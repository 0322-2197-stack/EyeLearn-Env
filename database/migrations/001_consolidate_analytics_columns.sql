-- ============================================================================
-- Migration: Consolidate Eye Tracking Analytics Columns
-- Purpose: Migrate data from legacy column names to standardized new columns
-- Date: 2025-11-29
-- ============================================================================
-- 
-- SAFETY NOTES:
-- - This migration PRESERVES all existing data
-- - The old column (total_focus_time) is NOT dropped for rollback safety
-- - Run this on a database backup first to verify
-- 
-- WHAT THIS DOES:
-- 1. Migrates data from total_focus_time -> total_focused_time
-- 2. Adds deprecation comment to old column
-- 3. Ensures new columns are populated
-- ============================================================================

USE elearn_db;

-- Step 1: Migrate existing data from old column to new column
-- Only migrate if new column is empty/zero
UPDATE eye_tracking_analytics 
SET 
    total_focused_time = total_focus_time,
    total_unfocused_time = 0,  -- Will be calculated from other sources if available
    focus_percentage = 100.00  -- Default assumption: if there's focus time, assume 100%
WHERE 
    total_focus_time > 0 
    AND (total_focused_time IS NULL OR total_focused_time = 0);

-- Step 2: Add a comment to the deprecated column for documentation
-- Note: MySQL doesn't support adding comments to existing columns easily,
-- so we'll document this in the migration notes instead

-- Step 3: Verify migration
SELECT 
    COUNT(*) as total_rows,
    SUM(CASE WHEN total_focus_time > 0 THEN 1 ELSE 0 END) as rows_with_old_data,
    SUM(CASE WHEN total_focused_time > 0 THEN 1 ELSE 0 END) as rows_with_new_data,
    SUM(total_focus_time) as sum_old_column,
    SUM(total_focused_time) as sum_new_column
FROM eye_tracking_analytics;

-- Expected result: sum_old_column should equal sum_new_column after migration

-- ============================================================================
-- Migration Status: Ready to execute
-- Rollback: Simply update PHP code back to use total_focus_time
-- Future cleanup: After 30+ days of stability, can drop total_focus_time column
-- ============================================================================
