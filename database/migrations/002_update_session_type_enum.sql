-- Migration: Update session_type enum to support all tracking types
-- Run this in Railway MySQL Query tab

-- Add new enum values to support cv_tracking and enhanced tracking
ALTER TABLE `eye_tracking_sessions` 
MODIFY `session_type` enum('viewing','pause','resume','cv_tracking','enhanced_cv_tracking','test') DEFAULT 'viewing';

-- Verify the change
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'eye_tracking_sessions' 
  AND COLUMN_NAME = 'session_type';

-- Success message
SELECT 'Session type enum updated successfully!' as status;
