# Railway Database Troubleshooting Guide

## Issue: Database Not Populating

### Quick Diagnostic Steps

1. **Access the diagnostic script**
   - Deploy your latest code to Railway
   - Visit: `https://your-app.railway.app/railway_db_diagnostic.php`
   - This will show you exactly what's wrong

### Common Issues & Solutions

#### Issue 1: Environment Variables Not Set

**Symptoms:**
- Database connection fails
- "Connection refused" errors in logs

**Solution:**
```
1. Go to Railway project → Settings → Variables
2. Add these variables:
   DB_HOST=${{MYSQLHOST}}
   DB_NAME=${{MYSQLDATABASE}}
   DB_USER=${{MYSQLUSER}}
   DB_PASS=${{MYSQLPASSWORD}}
   DB_PORT=${{MYSQLPORT}}
3. Redeploy the application
```

#### Issue 2: Table Doesn't Exist

**Symptoms:**
- "Table 'eye_tracking_sessions' doesn't exist" error
- Diagnostic shows table missing

**Solution:**
```
1. Go to Railway → MySQL service → Data tab
2. Click "Query"
3. Copy entire contents of database/elearn_db.sql
4. Paste and execute
5. Then run database/migrations/001_consolidate_analytics_columns.sql
```

#### Issue 3: Missing Columns

**Symptoms:**
- "Unknown column 'focused_time_seconds'" error
- Data saves but focused_time is 0

**Solution:**
```sql
-- Run this in Railway MySQL Query tab:
ALTER TABLE eye_tracking_sessions 
ADD COLUMN IF NOT EXISTS focused_time_seconds INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS unfocused_time_seconds INT(11) DEFAULT 0;
```

#### Issue 4: Wrong Database Connection

**Symptoms:**
- Local database has data
- Railway database is empty
- No errors in logs

**Solution:**
1. Check db_connection.php is using environment variables
2. Verify Railway env vars are set correctly
3. Deploy latest code to Railway
4. Check Railway deployment logs for connection info

#### Issue 5: PHP Session Not Working

**Symptoms:**
- User ID is always 1 or null
- "User not authenticated" errors

**Solution:**
```php
// For Railway, sessions might not persist properly
// Consider using a fallback user ID or token-based auth
// Check user/database/save_session_data.php line 29
```

### Step-by-Step Verification

#### Step 1: Check Database Connection
```bash
# In Railway logs, look for:
✓ Database connection successful
# OR
✗ Database connection failed: <error message>
```

#### Step 2: Verify Tables Exist
```sql
-- Run in Railway MySQL:
SHOW TABLES;

-- Should show:
-- eye_tracking_sessions
-- eye_tracking_analytics
-- users
-- modules
-- etc.
```

#### Step 3: Check Column Structure
```sql
-- Run in Railway MySQL:
SHOW COLUMNS FROM eye_tracking_sessions;

-- Must include:
-- focused_time_seconds
-- unfocused_time_seconds
```

#### Step 4: Test Insert Manually
```sql
-- Run in Railway MySQL:
INSERT INTO eye_tracking_sessions 
(user_id, module_id, section_id, total_time_seconds, 
 focused_time_seconds, unfocused_time_seconds, 
 session_type, created_at, last_updated) 
VALUES (1, 1, 1, 60, 30, 30, 'test', NOW(), NOW());

-- Then check:
SELECT * FROM eye_tracking_sessions WHERE session_type = 'test';
```

### Debugging Tips

#### Enable Detailed Logging

Add to save_session_data.php (temporarily):
```php
// At the top, after headers
error_log("=== SESSION SAVE ATTEMPT ===");
error_log("User ID: " . $user_id);
error_log("Module ID: " . ($input['module_id'] ?? 'NULL'));
error_log("Session Time: " . ($input['session_time'] ?? 'NULL'));
error_log("Focus Data: " . json_encode($input['focus_data'] ?? []));
```

Then check Railway deployment logs.

#### Check Browser Console

Open browser DevTools → Network tab:
1. Filter by "save_session_data"
2. Check Request Payload
3. Check Response
4. Look for errors

#### Verify Endpoint is Called

Add to cv-eye-tracking.js line 573 (after success):
```javascript
console.log('✅ Save response:', result);
console.log('📊 Sent data:', sessionData);
```

### Railway-Specific Checklist

- [ ] MySQL service is running (green status in Railway)
- [ ] Database name matches (usually "railway")
- [ ] Environment variables are set and referenced correctly
- [ ] Latest code is deployed
- [ ] Database schema/tables are created
- [ ] Columns exist: focused_time_seconds, unfocused_time_seconds
- [ ] No errors in Railway deployment logs
- [ ] Application can connect to MySQL (check diagnostic)
- [ ] Test insert works manually
- [ ] Browser console shows no errors

### Still Not Working?

1. **Check Railway MySQL Logs:**
   - Go to MySQL service → Logs
   - Look for connection attempts
   - Check for permission errors

2. **Verify Connection String:**
   ```php
   // In db_connection.php, temporarily add:
   error_log("DB Connection: " . getenv('DB_HOST') . ":" . getenv('DB_PORT'));
   error_log("Database: " . getenv('DB_NAME'));
   error_log("User: " . getenv('DB_USER'));
   ```

3. **Test with Simple Script:**
   Create test_railway_db.php:
   ```php
   <?php
   try {
       $pdo = new PDO(
           "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME'),
           getenv('DB_USER'),
           getenv('DB_PASS')
       );
       echo "✓ Direct PDO connection works!";
   } catch (Exception $e) {
       echo "✗ Connection failed: " . $e->getMessage();
   }
   ```

### Contact Support

If none of these work:
1. Run railway_db_diagnostic.php on Railway
2. Copy the full output
3. Check Railway MySQL service status
4. Review application logs for errors
5. Verify environment variables are correctly set
