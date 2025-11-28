-- ============================================================================
-- Migration: Cleanup Deprecated Column (Future - Optional)
-- Purpose: Remove deprecated total_focus_time column after confirming stability
-- Date: To be executed 30+ days after 001_consolidate_analytics_columns.sql
-- ============================================================================
-- 
-- WARNING: Only run this after verifying:
-- 1. All PHP code is using total_focused_time
-- 2. All dashboards and reports show correct data
-- 3. No errors in production logs for 30+ days
-- 4. Database backup is recent
-- 
-- WHAT THIS DOES:
-- 1. Drops the deprecated total_focus_time column
-- ============================================================================

USE elearn_db;

-- Verify before dropping: Ensure new columns are being used
SELECT 
    COUNT(*) as total_rows,
    SUM(CASE WHEN total_focused_time > 0 THEN 1 ELSE 0 END) as active_new_column,
    MAX(updated_at) as last_update
FROM eye_tracking_analytics;

-- If the above shows active data in total_focused_time, proceed with drop

-- Step 1: Drop the deprecated column
-- UNCOMMENT THE LINE BELOW WHEN READY TO EXECUTE
-- ALTER TABLE eye_tracking_analytics DROP COLUMN total_focus_time;

-- Step 2: Verify the column is removed
-- SHOW COLUMNS FROM eye_tracking_analytics;

-- ============================================================================
-- Migration Status: FUTURE / OPTIONAL
-- Execute only after confirming stability of new column structure
-- ============================================================================
