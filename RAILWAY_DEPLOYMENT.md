# Railway Deployment Guide for EyeLearn

## Prerequisites
1. GitHub repository with your code
2. Railway account (https://railway.app)
3. MySQL database service on Railway

## Step 1: Prepare Your Repository

Ensure these files are in your repository:
- ✅ `Dockerfile` (✓ Updated for Railway PORT)
- ✅ `railway.toml` (✓ Configuration file)
- ✅ `database/db_connection.php` (✓ Uses environment variables)
- ✅ `database/migrations/run_manually.sql` (✓ Migration script)

## Step 2: Create Railway Project

1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your EyeLearn repository
5. Railway will automatically detect the Dockerfile

## Step 3: Add MySQL Database

1. In your Railway project, click "+ New"
2. Select "Database" → "Add MySQL"
3. Railway will create a MySQL instance
4. Note the connection details (they'll be in environment variables)

## Step 4: Configure Environment Variables

In Railway project settings, add these variables:

### Database Variables (Auto-populated by Railway MySQL)
- `MYSQLHOST` - Railway provides this
- `MYSQLPORT` - Railway provides this  
- `MYSQLUSER` - Railway provides this
- `MYSQLPASSWORD` - Railway provides this
- `MYSQLDATABASE` - Railway provides this

### Map to Your App's Expected Variables
Add these variable references:
```
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_USER=${{MYSQLUSER}}
DB_PASS=${{MYSQLPASSWORD}}
DB_NAME=${{MYSQLDATABASE}}
```

### Optional: Python Service URL
If you deployed the Python eye-tracking service separately:
```
PYTHON_SERVICE_URL=<your-python-service-url>
```

## Step 5: Run Database Migration

### Option A: Railway Dashboard
1. Go to your MySQL service in Railway
2. Click "Data" tab
3. Click "Query"
4. Copy and paste contents of `database/migrations/run_manually.sql`
5. Execute

### Option B: Using Railway CLI
```bash
# Install Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link to your project
railway link

# Run migration
railway run mysql -h $MYSQLHOST -P $MYSQLPORT -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < database/migrations/run_manually.sql
```

## Step 6: Import Full Schema

After migration, import the complete schema:

1. In Railway MySQL Data → Query tab
2. Copy contents of `database/elearn_db.sql`
3. Paste and execute (may need to do in sections)

OR use Railway CLI:
```bash
railway run mysql -h $MYSQLHOST -P $MYSQLPORT -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < database/elearn_db.sql
```

## Step 7: Deploy

1. Railway will automatically deploy when you push to GitHub
2. Or click "Deploy" in Railway dashboard
3. Wait for build to complete
4. Check deployment logs for any errors

## Step 8: Verify Deployment

### Test Database Connection
Visit: `https://your-app.railway.app/test_schema_sync.php`

Should show:
```
✓ Database connection successful
✓ All systems are synchronized
```

### Test Application
1. Visit your Railway URL
2. Login/Register
3. Test eye tracking features
4. Verify data is saving to database

## Environment Variables Reference

Your `db_connection.php` reads these variables:
- `DB_HOST` - Database hostname
- `DB_NAME` - Database name  
- `DB_USER` - Database username
- `DB_PASS` - Database password
- `DB_PORT` - Database port

Railway's MySQL service provides:
- `MYSQLHOST`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLPORT`

You need to map Railway's variables to your app's expected names (shown in Step 4).

## Troubleshooting

### Issue: "Database connection failed"
- Check that environment variables are set correctly
- Verify Railway MySQL service is running
- Check deployment logs for connection errors

### Issue: "PORT not found"
- Dockerfile now handles dynamic PORT automatically
- Railway sets PORT environment variable
- Apache will listen on Railway's assigned port

### Issue: "Migration failed"
- Ensure MySQL service is fully started
- Check that migration SQL syntax is correct
- Try running migration in smaller chunks

### Issue: "Eye tracking not working"
- Verify Python service is deployed separately
- Set PYTHON_SERVICE_URL environment variable
- Check CORS settings in Python service

## Post-Deployment

### Monitor Your Application
- Check Railway logs for errors
- Monitor database usage
- Set up notifications for failures

### Update Deployment
Pushes to your GitHub main branch will automatically trigger redeployment.

## Cost Considerations

Railway free tier includes:
- $5 free credit per month
- Scales automatically
- Pay only for what you use

MySQL database will be the primary cost factor based on:
- Storage used
- Query volume
- Uptime

## Support

If issues persist:
1. Check Railway documentation: https://docs.railway.app
2. Review deployment logs in Railway dashboard
3. Test database connectivity using Railway's built-in tools
