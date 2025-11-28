-- Railway Database Schema Update Instructions
-- =============================================

## Step 1: Run Migration on Railway

1. Open your Railway MySQL service
2. Go to the "Query" tab
3. Copy and paste the contents of:
   `database/migrations/002_update_session_type_enum.sql`
4. Click "Execute"

## Step 2: Verify the Update

Run this query to confirm the schema update:

```sql
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'eye_tracking_sessions' 
  AND COLUMN_NAME = 'session_type';
```

Expected result:
```
COLUMN_TYPE: enum('viewing','pause','resume','cv_tracking','enhanced_cv_tracking','test')
```

## Step 3: Test Database Write

Visit your deployed diagnostic endpoint:
```
https://your-railway-app.railway.app/railway_db_diagnostic.php
```

Look for:
- ✓ Test write successful
- ✓ Test record verified in database

## Code Changes Made (for compatibility while migration is pending):

1. **save_cv_eye_tracking.php** (Line 38)
   - Changed: `'cv_tracking'` → `'viewing'`
   
2. **save_cv_eye_tracking.php** (Line 54)
   - Changed query filter: `'cv_tracking'` → `'viewing'`

3. **save_enhanced_tracking.php** (Lines 85, 106)
   - Changed: `'enhanced_cv_tracking'` → `'viewing'`

4. **railway_db_diagnostic.php** (Line 121)
   - Changed: `'test'` → `'viewing'`

## Post-Migration (Optional):

After running the migration, you can update the code back to use specific session types if needed:
- `'cv_tracking'` for computer vision tracking
- `'enhanced_cv_tracking'` for enhanced tracking
- `'test'` for test data

However, using 'viewing' for all session types is simpler and works perfectly fine.
