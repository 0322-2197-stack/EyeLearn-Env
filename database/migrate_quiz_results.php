<?php
/**
 * Migration script to fix quiz_results table structure
 * 
 * This script:
 * 1. Adds a percentage column to quiz_results and retake_results tables
 * 2. Converts existing percentage data in score column to actual scores
 * 3. Sets up automatic percentage calculation using triggers
 * 
 * Run this script once to migrate your database structure.
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'elearn_db';

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Starting migration...\n\n";

// Step 1: Add percentage column to quiz_results
echo "Step 1: Adding percentage column to quiz_results...\n";
$sql = "ALTER TABLE `quiz_results` 
        ADD COLUMN `percentage` DECIMAL(5,2) DEFAULT 0.00 AFTER `score`";
if ($conn->query($sql)) {
    echo "✓ Percentage column added to quiz_results\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "⚠ Percentage column already exists in quiz_results\n";
    } else {
        echo "✗ Error adding percentage column: " . $conn->error . "\n";
    }
}

// Step 2: Convert existing percentage data to actual scores
echo "\nStep 2: Converting existing percentage data to actual scores...\n";

// First, create a temporary column to store the original percentage
$sql = "ALTER TABLE `quiz_results` 
        ADD COLUMN `temp_percentage` DECIMAL(5,2) NULL";
if ($conn->query($sql)) {
    echo "✓ Temporary column created\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "⚠ Temporary column already exists, dropping it first...\n";
        $conn->query("ALTER TABLE `quiz_results` DROP COLUMN `temp_percentage`");
        $conn->query($sql);
    } else {
        echo "✗ Error creating temporary column: " . $conn->error . "\n";
    }
}

// Copy current score values to temp_percentage (only if they look like percentages)
// Only update rows where percentage is NULL or 0 (not yet migrated)
$sql = "UPDATE `quiz_results` 
        SET `temp_percentage` = `score`
        WHERE `score` <= 100 
          AND (`percentage` IS NULL OR `percentage` = 0)";
if ($conn->query($sql)) {
    echo "✓ Copied scores to temporary column\n";
} else {
    echo "✗ Error copying scores: " . $conn->error . "\n";
}

// Convert percentage to actual score based on total questions
// Only convert scores that are <= 100 (look like percentages) and haven't been converted yet
$sql = "UPDATE `quiz_results` qr
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
        END";
if ($conn->query($sql)) {
    echo "✓ Converted scores and calculated percentages\n";
} else {
    echo "✗ Error converting scores: " . $conn->error . "\n";
}

// Drop the temporary column
$sql = "ALTER TABLE `quiz_results` DROP COLUMN `temp_percentage`";
if ($conn->query($sql)) {
    echo "✓ Temporary column removed\n";
} else {
    echo "⚠ Could not remove temporary column: " . $conn->error . "\n";
}

// Step 3: Create triggers for quiz_results
echo "\nStep 3: Creating triggers for quiz_results...\n";

// Drop existing triggers if they exist
$conn->query("DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_insert`");
$conn->query("DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_update`");

// Create INSERT trigger
$sql = "CREATE TRIGGER `calculate_quiz_results_percentage_insert`
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
        END";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ INSERT trigger created\n";
} else {
    echo "✗ Error creating INSERT trigger: " . $conn->error . "\n";
}

// Create UPDATE trigger
$sql = "CREATE TRIGGER `calculate_quiz_results_percentage_update`
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
        END";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ UPDATE trigger created\n";
} else {
    echo "✗ Error creating UPDATE trigger: " . $conn->error . "\n";
}

// Step 4: Check if retake_results table exists and update it
echo "\nStep 4: Checking for retake_results table...\n";
$result = $conn->query("SHOW TABLES LIKE 'retake_results'");
if ($result && $result->num_rows > 0) {
    echo "✓ retake_results table found\n";
    
    // Add percentage column
    $sql = "ALTER TABLE `retake_results` 
            ADD COLUMN `percentage` DECIMAL(5,2) DEFAULT 0.00 AFTER `score`";
    if ($conn->query($sql)) {
        echo "✓ Percentage column added to retake_results\n";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "⚠ Percentage column already exists in retake_results\n";
        } else {
            echo "✗ Error adding percentage column: " . $conn->error . "\n";
        }
    }
    
    // Create temp column for retake_results
    $sql = "ALTER TABLE `retake_results` 
            ADD COLUMN `temp_percentage` DECIMAL(5,2) NULL";
    if ($conn->query($sql)) {
        echo "✓ Temporary column created for retake_results\n";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "⚠ Temporary column already exists in retake_results, dropping it first...\n";
            $conn->query("ALTER TABLE `retake_results` DROP COLUMN `temp_percentage`");
            $conn->query($sql);
        } else {
            echo "⚠ Could not create temporary column: " . $conn->error . "\n";
        }
    }
    
    // Copy scores to temp
    $sql = "UPDATE `retake_results` 
            SET `temp_percentage` = `score`
            WHERE `score` <= 100 
              AND (`percentage` IS NULL OR `percentage` = 0)";
    if ($conn->query($sql)) {
        echo "✓ Copied retake_results scores to temporary column\n";
    } else {
        echo "⚠ Could not copy retake_results scores: " . $conn->error . "\n";
    }
    
    // Convert existing scores
    $sql = "UPDATE `retake_results` rr
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
            END";
    if ($conn->query($sql)) {
        echo "✓ Converted retake_results scores\n";
    } else {
        echo "✗ Error converting retake_results scores: " . $conn->error . "\n";
    }
    
    // Drop temp column
    $sql = "ALTER TABLE `retake_results` DROP COLUMN `temp_percentage`";
    if ($conn->query($sql)) {
        echo "✓ Temporary column removed from retake_results\n";
    } else {
        echo "⚠ Could not remove temporary column: " . $conn->error . "\n";
    }
    
    // Create triggers for retake_results
    $conn->query("DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_insert`");
    $conn->query("DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_update`");
    
    $sql = "CREATE TRIGGER `calculate_retake_results_percentage_insert`
            BEFORE INSERT ON `retake_results`
            FOR EACH ROW
            BEGIN
                DECLARE total_questions INT DEFAULT 0;
                SELECT COUNT(*) INTO total_questions
                FROM `final_quiz_questions`
                WHERE `quiz_id` = NEW.quiz_id;
                IF total_questions > 0 THEN
                    SET NEW.percentage = ROUND((NEW.score / total_questions) * 100, 2);
                ELSE
                    SET NEW.percentage = 0;
                END IF;
            END";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "✓ INSERT trigger created for retake_results\n";
    } else {
        echo "✗ Error creating INSERT trigger: " . $conn->error . "\n";
    }
    
    $sql = "CREATE TRIGGER `calculate_retake_results_percentage_update`
            BEFORE UPDATE ON `retake_results`
            FOR EACH ROW
            BEGIN
                DECLARE total_questions INT DEFAULT 0;
                SELECT COUNT(*) INTO total_questions
                FROM `final_quiz_questions`
                WHERE `quiz_id` = NEW.quiz_id;
                IF total_questions > 0 THEN
                    SET NEW.percentage = ROUND((NEW.score / total_questions) * 100, 2);
                ELSE
                    SET NEW.percentage = 0;
                END IF;
            END";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "✓ UPDATE trigger created for retake_results\n";
    } else {
        echo "✗ Error creating UPDATE trigger: " . $conn->error . "\n";
    }
} else {
    echo "⚠ retake_results table does not exist, skipping\n";
}

// Step 5: Update module_completions table
echo "\nStep 5: Updating module_completions table...\n";
$result = $conn->query("SHOW TABLES LIKE 'module_completions'");
if ($result && $result->num_rows > 0) {
    echo "✓ module_completions table found\n";
    
    // Add percentage column
    $sql = "ALTER TABLE `module_completions` 
            ADD COLUMN `final_quiz_percentage` DECIMAL(5,2) DEFAULT 0.00 AFTER `final_quiz_score`";
    if ($conn->query($sql)) {
        echo "✓ Percentage column added to module_completions\n";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "⚠ Percentage column already exists in module_completions\n";
        } else {
            echo "✗ Error adding percentage column: " . $conn->error . "\n";
        }
    }
    
    // Convert existing scores (assuming they might be percentages)
    // Get the quiz_id from the most recent quiz_result for each module completion
    $sql = "UPDATE `module_completions` mc
            INNER JOIN (
                SELECT 
                    mc2.id,
                    COALESCE(
                        (SELECT quiz_id FROM quiz_results 
                         WHERE user_id = mc2.user_id AND module_id = mc2.module_id 
                         ORDER BY completion_date DESC LIMIT 1),
                        (SELECT quiz_id FROM retake_results 
                         WHERE user_id = mc2.user_id AND module_id = mc2.module_id 
                         ORDER BY completion_date DESC LIMIT 1),
                        (SELECT id FROM final_quizzes WHERE module_id = mc2.module_id LIMIT 1)
                    ) AS quiz_id
                FROM `module_completions` mc2
            ) AS quiz_data ON mc.id = quiz_data.id
            INNER JOIN (
                SELECT 
                    quiz_id,
                    COUNT(*) AS total_questions
                FROM `final_quiz_questions`
                GROUP BY quiz_id
            ) AS question_counts ON quiz_data.quiz_id = question_counts.quiz_id
            SET mc.final_quiz_score = CASE 
                WHEN mc.final_quiz_score <= 100 AND question_counts.total_questions > 0
                THEN ROUND((mc.final_quiz_score / 100) * question_counts.total_questions)
                ELSE mc.final_quiz_score
            END,
            mc.final_quiz_percentage = CASE
                WHEN question_counts.total_questions > 0 AND mc.final_quiz_score >= 0
                THEN LEAST(ROUND((mc.final_quiz_score / question_counts.total_questions) * 100, 2), 100.00)
                ELSE 0
            END";
    
    if ($conn->query($sql)) {
        echo "✓ Converted module_completions scores\n";
    } else {
        echo "⚠ Could not convert all scores (some may not have associated quizzes): " . $conn->error . "\n";
    }
    
    // Create triggers for module_completions
    $conn->query("DROP TRIGGER IF EXISTS `calculate_module_completions_percentage_insert`");
    $conn->query("DROP TRIGGER IF EXISTS `calculate_module_completions_percentage_update`");
    
    // For module_completions, we need to find the quiz_id from the module
    // This is more complex, so we'll use a trigger that looks up the quiz
    $sql = "CREATE TRIGGER `calculate_module_completions_percentage_insert`
            BEFORE INSERT ON `module_completions`
            FOR EACH ROW
            BEGIN
                DECLARE total_questions INT DEFAULT 0;
                DECLARE quiz_id_val INT DEFAULT NULL;
                
                -- Get the quiz_id for this module (from most recent quiz_result or final_quizzes)
                SELECT COALESCE(
                    (SELECT quiz_id FROM quiz_results 
                     WHERE user_id = NEW.user_id AND module_id = NEW.module_id 
                     ORDER BY completion_date DESC LIMIT 1),
                    (SELECT quiz_id FROM retake_results 
                     WHERE user_id = NEW.user_id AND module_id = NEW.module_id 
                     ORDER BY completion_date DESC LIMIT 1),
                    (SELECT id FROM final_quizzes WHERE module_id = NEW.module_id LIMIT 1)
                ) INTO quiz_id_val;
                
                IF quiz_id_val IS NOT NULL THEN
                    SELECT COUNT(*) INTO total_questions
                    FROM `final_quiz_questions`
                    WHERE `quiz_id` = quiz_id_val;
                    
                    IF total_questions > 0 AND NEW.final_quiz_score >= 0 THEN
                        SET NEW.final_quiz_percentage = LEAST(ROUND((NEW.final_quiz_score / total_questions) * 100, 2), 100.00);
                    ELSE
                        SET NEW.final_quiz_percentage = 0;
                    END IF;
                ELSE
                    SET NEW.final_quiz_percentage = 0;
                END IF;
            END";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "✓ INSERT trigger created for module_completions\n";
    } else {
        echo "✗ Error creating INSERT trigger: " . $conn->error . "\n";
    }
    
    $sql = "CREATE TRIGGER `calculate_module_completions_percentage_update`
            BEFORE UPDATE ON `module_completions`
            FOR EACH ROW
            BEGIN
                DECLARE total_questions INT DEFAULT 0;
                DECLARE quiz_id_val INT DEFAULT NULL;
                
                -- Get the quiz_id for this module
                SELECT COALESCE(
                    (SELECT quiz_id FROM quiz_results 
                     WHERE user_id = NEW.user_id AND module_id = NEW.module_id 
                     ORDER BY completion_date DESC LIMIT 1),
                    (SELECT quiz_id FROM retake_results 
                     WHERE user_id = NEW.user_id AND module_id = NEW.module_id 
                     ORDER BY completion_date DESC LIMIT 1),
                    (SELECT id FROM final_quizzes WHERE module_id = NEW.module_id LIMIT 1)
                ) INTO quiz_id_val;
                
                IF quiz_id_val IS NOT NULL THEN
                    SELECT COUNT(*) INTO total_questions
                    FROM `final_quiz_questions`
                    WHERE `quiz_id` = quiz_id_val;
                    
                    IF total_questions > 0 AND NEW.final_quiz_score >= 0 THEN
                        SET NEW.final_quiz_percentage = LEAST(ROUND((NEW.final_quiz_score / total_questions) * 100, 2), 100.00);
                    ELSE
                        SET NEW.final_quiz_percentage = 0;
                    END IF;
                ELSE
                    SET NEW.final_quiz_percentage = 0;
                END IF;
            END";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "✓ UPDATE trigger created for module_completions\n";
    } else {
        echo "✗ Error creating UPDATE trigger: " . $conn->error . "\n";
    }
} else {
    echo "⚠ module_completions table does not exist, skipping\n";
}

echo "\n✓ Migration completed!\n";
echo "\nNext steps:\n";
echo "1. Update your PHP code to send actual score (number of correct answers) instead of percentage\n";
echo "2. The percentage column will be automatically calculated by triggers\n";
echo "3. Fix the bug in Smodulepart.php line 555 - it should bind \$score, not \$quiz_id\n";

$conn->close();
?>

