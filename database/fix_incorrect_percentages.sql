-- Fix script to correct incorrect percentages (like 999.99) in quiz_results and retake_results
-- This script recalculates percentages based on actual scores and total questions

-- Fix quiz_results table
UPDATE `quiz_results` qr
INNER JOIN (
    SELECT 
        qr2.id,
        qr2.score,
        COUNT(fqq.id) AS total_questions
    FROM `quiz_results` qr2
    LEFT JOIN `final_quiz_questions` fqq ON qr2.quiz_id = fqq.quiz_id
    GROUP BY qr2.id, qr2.score
) AS quiz_data ON qr.id = quiz_data.id
SET qr.percentage = CASE
    WHEN quiz_data.total_questions > 0 AND qr.score >= 0 AND qr.score <= quiz_data.total_questions
    THEN LEAST(ROUND((qr.score / quiz_data.total_questions) * 100, 2), 100.00)
    WHEN qr.percentage > 100 OR qr.percentage < 0
    THEN 0
    ELSE qr.percentage
END
WHERE qr.percentage > 100 OR qr.percentage < 0 OR qr.percentage IS NULL;

-- Fix retake_results table (if it exists)
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'retake_results'
);

SET @sql_fix_retake = IF(@table_exists > 0,
    'UPDATE `retake_results` rr
     INNER JOIN (
         SELECT 
             rr2.id,
             rr2.score,
             COUNT(fqq.id) AS total_questions
         FROM `retake_results` rr2
         LEFT JOIN `final_quiz_questions` fqq ON rr2.quiz_id = fqq.quiz_id
         GROUP BY rr2.id, rr2.score
     ) AS quiz_data ON rr.id = quiz_data.id
     SET rr.percentage = CASE
         WHEN quiz_data.total_questions > 0 AND rr.score >= 0 AND rr.score <= quiz_data.total_questions
         THEN LEAST(ROUND((rr.score / quiz_data.total_questions) * 100, 2), 100.00)
         WHEN rr.percentage > 100 OR rr.percentage < 0
         THEN 0
         ELSE rr.percentage
     END
     WHERE rr.percentage > 100 OR rr.percentage < 0 OR rr.percentage IS NULL',
    'SELECT "retake_results table does not exist, skipping" AS message'
);

PREPARE stmt_fix_retake FROM @sql_fix_retake;
EXECUTE stmt_fix_retake;
DEALLOCATE PREPARE stmt_fix_retake;


