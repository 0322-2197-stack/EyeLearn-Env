-- Migration script to fix quiz_results table structure
-- Changes:
-- 1. Add percentage column to store percentage score
-- 2. Convert existing percentage data in score column to actual scores
-- 3. Set up automatic percentage calculation using triggers

-- Step 1: Check if percentage column exists, if not add it
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'quiz_results' 
    AND column_name = 'percentage'
);

SET @sql_add_col = IF(@col_exists = 0,
    'ALTER TABLE `quiz_results` ADD COLUMN `percentage` DECIMAL(5,2) DEFAULT 0.00 AFTER `score`',
    'SELECT "percentage column already exists, skipping" AS message'
);

PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

-- Step 2: Convert existing percentage data to actual scores
-- This assumes current score values are percentages (0-100)
-- We need to convert them to actual number of correct answers
-- Only convert if scores look like percentages (<= 100) and percentage column is 0 or NULL

-- First, create a temporary column to store the original percentage
SET @temp_col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'quiz_results' 
    AND column_name = 'temp_percentage'
);

SET @sql_temp_col = IF(@temp_col_exists = 0,
    'ALTER TABLE `quiz_results` ADD COLUMN `temp_percentage` DECIMAL(5,2) NULL',
    'SELECT "temp_percentage column already exists" AS message'
);

PREPARE stmt_temp_col FROM @sql_temp_col;
EXECUTE stmt_temp_col;
DEALLOCATE PREPARE stmt_temp_col;

-- Copy current score values to temp_percentage (only if they look like percentages)
-- Only update rows where percentage is NULL or 0 (not yet migrated)
UPDATE `quiz_results` 
SET `temp_percentage` = `score`
WHERE `score` <= 100 
  AND (`percentage` IS NULL OR `percentage` = 0);

-- Now convert percentage to actual score based on total questions
-- Only convert scores that are <= 100 (look like percentages) and haven't been converted yet
UPDATE `quiz_results` qr
INNER JOIN (
    SELECT 
        qr2.id,
        qr2.score AS old_score,
        qr2.temp_percentage,
        COUNT(fqq.id) AS total_questions
    FROM `quiz_results` qr2
    LEFT JOIN `final_quiz_questions` fqq ON qr2.quiz_id = fqq.quiz_id
    WHERE qr2.score <= 100 AND (qr2.percentage IS NULL OR qr2.percentage = 0)
    GROUP BY qr2.id, qr2.score, qr2.temp_percentage
) AS quiz_data ON qr.id = quiz_data.id
SET qr.score = CASE 
    WHEN quiz_data.total_questions > 0 AND quiz_data.old_score <= 100
    THEN ROUND((quiz_data.old_score / 100) * quiz_data.total_questions)
    ELSE qr.score
END,
qr.percentage = CASE
    WHEN quiz_data.total_questions > 0 AND quiz_data.temp_percentage IS NOT NULL
    THEN LEAST(ROUND(quiz_data.temp_percentage, 2), 100.00)
    WHEN quiz_data.total_questions > 0 AND quiz_data.total_questions > 0
    THEN LEAST(ROUND((qr.score / quiz_data.total_questions) * 100, 2), 100.00)
    ELSE 0
END;

-- Step 3: Drop the temporary column
SET @sql_drop_temp = IF(@temp_col_exists = 0,
    'ALTER TABLE `quiz_results` DROP COLUMN `temp_percentage`',
    'SELECT "temp_percentage column does not exist" AS message'
);

PREPARE stmt_drop_temp FROM @sql_drop_temp;
EXECUTE stmt_drop_temp;
DEALLOCATE PREPARE stmt_drop_temp;

-- Step 4: Create trigger to automatically calculate percentage on INSERT
DELIMITER $$

DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_insert`$$

CREATE TRIGGER `calculate_quiz_results_percentage_insert`
BEFORE INSERT ON `quiz_results`
FOR EACH ROW
BEGIN
    DECLARE total_questions INT DEFAULT 0;
    
    SELECT COUNT(*) INTO total_questions
    FROM `final_quiz_questions`
    WHERE `quiz_id` = NEW.quiz_id;
    
    IF total_questions > 0 AND NEW.score >= 0 THEN
        SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
    ELSE
        SET NEW.percentage = 0;
    END IF;
END$$

-- Step 5: Create trigger to automatically calculate percentage on UPDATE
DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_update`$$

CREATE TRIGGER `calculate_quiz_results_percentage_update`
BEFORE UPDATE ON `quiz_results`
FOR EACH ROW
BEGIN
    DECLARE total_questions INT DEFAULT 0;
    
    SELECT COUNT(*) INTO total_questions
    FROM `final_quiz_questions`
    WHERE `quiz_id` = NEW.quiz_id;
    
    IF total_questions > 0 AND NEW.score >= 0 THEN
        SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
    ELSE
        SET NEW.percentage = 0;
    END IF;
END$$

DELIMITER ;

-- Step 6: Also update retake_results table if it exists
-- Check if retake_results table exists and has similar structure
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'retake_results'
);

-- Check if percentage column exists in retake_results
SET @retake_col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'retake_results' 
    AND column_name = 'percentage'
);

SET @sql = IF(@table_exists > 0 AND @retake_col_exists = 0,
    'ALTER TABLE `retake_results` 
     ADD COLUMN `percentage` DECIMAL(5,2) DEFAULT 0.00 AFTER `score`',
    IF(@table_exists > 0,
        'SELECT "retake_results percentage column already exists, skipping" AS message',
        'SELECT "retake_results table does not exist, skipping" AS message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If retake_results exists, also convert its scores and add triggers
-- Only convert scores that look like percentages (<= 100) and haven't been converted yet
SET @retake_temp_col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'retake_results' 
    AND column_name = 'temp_percentage'
);

SET @sql2 = IF(@table_exists > 0 AND @retake_temp_col_exists = 0,
    'ALTER TABLE `retake_results` ADD COLUMN `temp_percentage` DECIMAL(5,2) NULL',
    IF(@table_exists > 0,
        'SELECT "retake_results temp_percentage column already exists" AS message',
        'SELECT "retake_results table does not exist, skipping temp column" AS message'
    )
);

PREPARE stmt2_temp FROM @sql2;
EXECUTE stmt2_temp;
DEALLOCATE PREPARE stmt2_temp;

SET @sql2_update = IF(@table_exists > 0,
    'UPDATE `retake_results` 
     SET `temp_percentage` = `score`
     WHERE `score` <= 100 
       AND (`percentage` IS NULL OR `percentage` = 0)',
    'SELECT "retake_results table does not exist, skipping temp update" AS message'
);

PREPARE stmt2_update FROM @sql2_update;
EXECUTE stmt2_update;
DEALLOCATE PREPARE stmt2_update;

SET @sql2_convert = IF(@table_exists > 0,
    'UPDATE `retake_results` rr
     INNER JOIN (
         SELECT 
             rr2.id,
             rr2.score AS old_score,
             rr2.temp_percentage,
             COUNT(fqq.id) AS total_questions
         FROM `retake_results` rr2
         LEFT JOIN `final_quiz_questions` fqq ON rr2.quiz_id = fqq.quiz_id
         WHERE rr2.score <= 100 AND (rr2.percentage IS NULL OR rr2.percentage = 0)
         GROUP BY rr2.id, rr2.score, rr2.temp_percentage
     ) AS quiz_data ON rr.id = quiz_data.id
     SET rr.score = CASE 
         WHEN quiz_data.total_questions > 0 AND quiz_data.old_score <= 100
         THEN ROUND((quiz_data.old_score / 100) * quiz_data.total_questions)
         ELSE rr.score
     END,
     rr.percentage = CASE
         WHEN quiz_data.total_questions > 0 AND quiz_data.temp_percentage IS NOT NULL
         THEN LEAST(ROUND(quiz_data.temp_percentage, 2), 100.00)
         WHEN quiz_data.total_questions > 0 AND quiz_data.total_questions > 0
         THEN LEAST(ROUND((rr.score / quiz_data.total_questions) * 100, 2), 100.00)
         ELSE 0
     END',
    'SELECT "retake_results table does not exist, skipping conversion" AS message'
);

PREPARE stmt2_convert FROM @sql2_convert;
EXECUTE stmt2_convert;
DEALLOCATE PREPARE stmt2_convert;

-- Drop temp column from retake_results
SET @sql2_drop = IF(@table_exists > 0 AND @retake_temp_col_exists = 0,
    'ALTER TABLE `retake_results` DROP COLUMN `temp_percentage`',
    IF(@table_exists > 0,
        'SELECT "retake_results temp_percentage column does not exist" AS message',
        'SELECT "retake_results table does not exist, skipping temp drop" AS message'
    )
);

PREPARE stmt2_drop FROM @sql2_drop;
EXECUTE stmt2_drop;
DEALLOCATE PREPARE stmt2_drop;

-- Create triggers for retake_results if table exists
SET @sql3 = IF(@table_exists > 0,
    'DELIMITER $$
     DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_insert`$$
     CREATE TRIGGER `calculate_retake_results_percentage_insert`
     BEFORE INSERT ON `retake_results`
     FOR EACH ROW
     BEGIN
         DECLARE total_questions INT DEFAULT 0;
         SELECT COUNT(*) INTO total_questions
         FROM `final_quiz_questions`
         WHERE `quiz_id` = NEW.quiz_id;
         IF total_questions > 0 AND NEW.score >= 0 THEN
             SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
         ELSE
             SET NEW.percentage = 0;
         END IF;
     END$$
     DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_update`$$
     CREATE TRIGGER `calculate_retake_results_percentage_update`
     BEFORE UPDATE ON `retake_results`
     FOR EACH ROW
     BEGIN
         DECLARE total_questions INT DEFAULT 0;
         SELECT COUNT(*) INTO total_questions
         FROM `final_quiz_questions`
         WHERE `quiz_id` = NEW.quiz_id;
         IF total_questions > 0 AND NEW.score >= 0 THEN
             SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
         ELSE
             SET NEW.percentage = 0;
         END IF;
     END$$
     DELIMITER ;',
    'SELECT "retake_results table does not exist, skipping triggers" AS message'
);

PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

