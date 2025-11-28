# Quiz Results Table Migration Guide

## Overview
This migration fixes the logical error in the `quiz_results` table structure. Previously, the `score` column was storing percentage values (0-100), but it should store the actual number of correct answers. A new `percentage` column has been added that automatically calculates the percentage based on the total questions.

## Changes Made

### Database Structure
1. **Added `percentage` column** to `quiz_results` table (DECIMAL(5,2))
2. **Added `percentage` column** to `retake_results` table (if it exists)
3. **Added `final_quiz_percentage` column** to `module_completions` table (DECIMAL(5,2))
4. **Created triggers** to automatically calculate percentage on INSERT and UPDATE for all tables
5. **Converted existing data** from percentage format to actual scores
6. **Fixed bug** in `Smodulepart.php` where `quiz_id` was incorrectly bound to `final_quiz_score`

### Improvements (Latest Version)
1. **Idempotent migration** - Can be run multiple times safely without causing errors
2. **Smart conversion** - Only converts scores that look like percentages (<= 100) and haven't been converted yet
3. **Percentage safeguards** - Uses `LEAST()` function to cap percentages at 100.00 maximum
4. **Better error handling** - Checks for existing columns before adding them
5. **Preserves original values** - Uses temp_percentage to store original percentage before conversion

### Code Changes
1. **Updated frontend JavaScript** in `Smodulepart.php` to send actual score (number of correct answers) instead of percentage
2. The percentage is now automatically calculated by database triggers

## How to Run the Migration

### Option 1: Using PHP Script (Recommended)
1. Open your terminal/command prompt
2. Navigate to the `database` folder
3. Run the PHP migration script:
   ```bash
   php migrate_quiz_results.php
   ```

### Option 2: Using SQL Script
1. Open phpMyAdmin or your MySQL client
2. Select the `elearn_db` database
3. Import and execute `migrate_quiz_results_score.sql`

**Note:** The SQL script has some limitations with triggers in prepared statements. The PHP script is recommended.

## What the Migration Does

1. **Adds percentage columns** to:
   - `quiz_results` table (`percentage`)
   - `retake_results` table (`percentage`) - if it exists
   - `module_completions` table (`final_quiz_percentage`) - if it exists
2. **Converts existing data:**
   - If existing scores are ≤ 100, they are treated as percentages
   - Converts percentage to actual score: `actual_score = (percentage / 100) * total_questions`
   - Calculates and stores the percentage in the new column
3. **Creates triggers** that automatically calculate percentage whenever a score is inserted or updated:
   - `calculate_quiz_results_percentage_insert`
   - `calculate_quiz_results_percentage_update`
   - `calculate_retake_results_percentage_insert` (if retake_results exists)
   - `calculate_retake_results_percentage_update` (if retake_results exists)
   - `calculate_module_completions_percentage_insert` (if module_completions exists)
   - `calculate_module_completions_percentage_update` (if module_completions exists)

## After Migration

### Database Structure
- **quiz_results & retake_results:**
  - `score` column: Stores the actual number of correct answers (INT)
  - `percentage` column: Automatically calculated percentage (DECIMAL(5,2))
    - Formula: `(score / total_questions) * 100`
    - Automatically updated by triggers

- **module_completions:**
  - `final_quiz_score` column: Stores the actual number of correct answers (INT)
  - `final_quiz_percentage` column: Automatically calculated percentage (DECIMAL(5,2))
    - Formula: `(final_quiz_score / total_questions) * 100`
    - Automatically updated by triggers

### Code Behavior
- Frontend now sends actual score (number of correct answers)
- Backend receives and stores actual score
- Percentage is automatically calculated by database triggers
- No code changes needed for percentage calculation

## Verification

After running the migration, verify:

1. **Check table structure:**
   ```sql
   DESCRIBE quiz_results;
   ```
   You should see the `percentage` column after `score`.

2. **Check triggers:**
   ```sql
   SHOW TRIGGERS LIKE 'quiz_results';
   ```
   You should see two triggers for INSERT and UPDATE.

3. **Test with a new quiz submission:**
   - Submit a quiz with, for example, 8 correct answers out of 10
   - Check the database:
     - In `quiz_results`: `score` should be `8`, `percentage` should be `80.00`
     - In `module_completions`: `final_quiz_score` should be `8`, `final_quiz_percentage` should be `80.00`

## Rollback (if needed)

If you need to rollback the migration:

```sql
-- Remove triggers
DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_insert`;
DROP TRIGGER IF EXISTS `calculate_quiz_results_percentage_update`;
DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_insert`;
DROP TRIGGER IF EXISTS `calculate_retake_results_percentage_update`;
DROP TRIGGER IF EXISTS `calculate_module_completions_percentage_insert`;
DROP TRIGGER IF EXISTS `calculate_module_completions_percentage_update`;

-- Remove percentage columns
ALTER TABLE `quiz_results` DROP COLUMN `percentage`;
ALTER TABLE `retake_results` DROP COLUMN `percentage` IF EXISTS;
ALTER TABLE `module_completions` DROP COLUMN `final_quiz_percentage` IF EXISTS;
```

**Note:** Rolling back will lose the percentage data. You may want to export it first if needed.

## Fixing Incorrect Percentages

If you've already run the migration and have incorrect percentages (like 999.99), you can fix them by running:

```bash
# Using SQL script
mysql -u root -p elearn_db < database/fix_incorrect_percentages.sql
```

Or import `fix_incorrect_percentages.sql` through phpMyAdmin.

This script will:
- Recalculate percentages based on actual scores and total questions
- Fix any percentages that are > 100 or < 0
- Cap all percentages at 100.00 maximum

## Troubleshooting

### Error: "Duplicate column name"
- The migration has already been run
- The column already exists, which is fine
- The migration script now checks for existing columns before adding them

### Error: "Table doesn't exist"
- If `retake_results` doesn't exist, the migration will skip it
- This is normal if you haven't created that table yet

### Existing scores are wrong after migration
- If your existing scores were already in the correct format (not percentages), you may need to manually adjust them
- Check a few records to verify the conversion worked correctly

### Percentage shows 999.99 or other incorrect values
- This can happen if the migration was run multiple times incorrectly
- Run the `fix_incorrect_percentages.sql` script to correct all percentages
- The updated migration script now prevents this issue by:
  - Only converting scores that look like percentages (<= 100)
  - Only updating rows where percentage is NULL or 0 (not yet migrated)
  - Using the original percentage value stored in temp_percentage
  - Capping percentages at 100.00 using LEAST() function

## Support

If you encounter any issues during migration, check:
1. Database connection settings in `migrate_quiz_results.php`
2. User permissions (needs ALTER, CREATE TRIGGER permissions)
3. MySQL version (triggers require MySQL 5.0.2+)

