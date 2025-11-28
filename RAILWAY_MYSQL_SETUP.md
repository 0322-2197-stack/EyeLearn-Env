# Railway MySQL Setup - Step by Step

## ✅ Status: MySQL is Running!

Your MySQL 9.4.0 is successfully running on Railway. Now we need to:
1. Get connection details
2. Set environment variables in PHP app
3. Import database schema

---

## Step 1: Get MySQL Connection Details

1. Go to your Railway MySQL service
2. Click on the **Variables** tab
3. You should see these variables:

```
MYSQLHOST=<some-host>.railway.app
MYSQLPORT=<port-number>
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=<password>
```

**Copy these values - you'll need them!**

---

## Step 2: Set Environment Variables in PHP App

1. Go to your **PHP application service** (not MySQL)
2. Click **Variables** tab
3. **Add these variables** (map Railway's MySQL vars to your app's expected names):

```bash
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

**IMPORTANT:** Replace `MySQL` with the actual name of your MySQL service in Railway.

### Alternative (if service reference doesn't work):

Manually copy the values:
```bash
DB_HOST=<paste MYSQLHOST value>
DB_PORT=<paste MYSQLPORT value>
DB_NAME=railway
DB_USER=root
DB_PASS=<paste MYSQLPASSWORD value>
```

4. Click **Deploy** to redeploy with new variables

---

## Step 3: Import Database Schema

### Option A: Using Railway MySQL Data Tab (Recommended)

1. Go to your **MySQL service** in Railway
2. Click **Data** tab
3. Click **Query** button
4. Copy and paste the contents of `database/elearn_db.sql`
5. Click **Run** or **Execute**
6. Wait for completion (may take 1-2 minutes)

### Option B: Using Railway CLI

```bash
# Install Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link to your project
railway link

# Get into MySQL service
railway shell

# Inside MySQL shell:
mysql -u root -p$MYSQLPASSWORD railway < /path/to/elearn_db.sql
```

### Option C: Manual Table Creation

If the full SQL is too large, create core tables first:

```sql
-- Run this in Railway MySQL Query tab:

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `eye_tracking_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `total_time_seconds` int(11) DEFAULT 0,
  `focused_time_seconds` int(11) DEFAULT 0,
  `unfocused_time_seconds` int(11) DEFAULT 0,
  `session_type` enum('viewing','pause','resume') DEFAULT 'viewing',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session_data` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `module_id` (`module_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `eye_tracking_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `total_focus_time` int(11) DEFAULT 0,
  `total_focused_time` int(11) DEFAULT 0,
  `total_unfocused_time` int(11) DEFAULT 0,
  `focus_percentage` decimal(5,2) DEFAULT 0.00,
  `session_count` int(11) DEFAULT 0,
  `average_session_time` int(11) DEFAULT 0,
  `max_continuous_time` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_section_date` (`user_id`,`module_id`,`section_id`,`date`),
  KEY `user_id` (`user_id`),
  KEY `module_id` (`module_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Step 4: Verify Connection

1. Deploy your latest code to Railway
2. Visit: `https://your-app.railway.app/railway_db_diagnostic.php`
3. It will show:
   - ✓ Environment variables set
   - ✓ Database connection successful
   - ✓ Tables exist
   - ✓ Can write to database

---

## Step 5: Run Migration

After schema is imported:

1. Go to Railway MySQL → Data → Query
2. Run this migration:

```sql
-- Migrate existing data
UPDATE eye_tracking_analytics 
SET 
    total_focused_time = total_focus_time,
    total_unfocused_time = 0,
    focus_percentage = 100.00
WHERE 
    total_focus_time > 0 
    AND (total_focused_time IS NULL OR total_focused_time = 0);

-- Verify
SELECT 
    COUNT(*) as total_rows,
    SUM(total_focused_time) as sum_new_column
FROM eye_tracking_analytics;
```

---

## Step 6: Test the Application

1. Open your Railway app URL
2. Login/Register
3. Open a module
4. Use it for 1-2 minutes
5. Go back to Railway MySQL → Data tab
6. Click on `eye_tracking_sessions` table
7. You should see new records!

---

## Troubleshooting

### Issue: "Connection refused"

**Check:**
- Environment variables are set in **PHP app** (not MySQL)
- Variable names match: `DB_HOST`, `DB_NAME`, etc.
- MySQL service is running (green status)

**Fix:**
```bash
# Verify in PHP app variables:
DB_HOST=monorail.proxy.rlwy.net  # Example
DB_PORT=12345  # Example port
```

### Issue: "Table doesn't exist"

**Check:**
- Schema was imported successfully
- You're connected to the right database (`railway`)

**Fix:**
- Re-run schema import
- Check Railway MySQL Data tab for tables

### Issue: "Access denied"

**Check:**
- Password is correct
- User is `root`
- Database name is `railway`

**Fix:**
- Verify `MYSQLPASSWORD` value
- Make sure `DB_PASS` uses correct password

### Issue: "Unknown column"

**Check:**
- Schema is up to date
- Migration was run

**Fix:**
```sql
-- Add missing columns manually:
ALTER TABLE eye_tracking_sessions 
ADD COLUMN IF NOT EXISTS focused_time_seconds INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS unfocused_time_seconds INT(11) DEFAULT 0;
```

---

## Quick Verification Checklist

- [ ] MySQL service is running (green) in Railway
- [ ] MySQL connection details copied
- [ ] Environment variables set in PHP app
  - [ ] DB_HOST
  - [ ] DB_PORT
  - [ ] DB_NAME
  - [ ] DB_USER
  - [ ] DB_PASS
- [ ] PHP app redeployed with new env vars
- [ ] Database schema imported (elearn_db.sql)
- [ ] Tables exist in Railway MySQL Data tab
- [ ] Migration script run (001_consolidate_analytics_columns.sql)
- [ ] Diagnostic script shows all green ✓
- [ ] Test record appears in database after using app

---

## Next Steps

Once everything is connected:

1. **Test locally first**: Make sure it works on localhost
2. **Push to GitHub**: Commit all changes
3. **Railway auto-deploys**: It will pick up changes
4. **Monitor logs**: Check for errors during deployment
5. **Test live app**: Use the application
6. **Verify data**: Check Railway MySQL for new records

---

## Need Help?

Run the diagnostic and share the output:
```
https://your-app.railway.app/railway_db_diagnostic.php
```

This will show exactly what's missing or misconfigured.
