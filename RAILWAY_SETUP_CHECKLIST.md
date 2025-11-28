# Railway Setup - Copy & Paste Checklist

## What I Need You To Do (Very Simple Steps)

I can't access your Railway account, but I'll guide you through each step with **exact commands to copy/paste**.

---

## ✅ STEP 1: Set Environment Variables in Railway PHP App

### Action Required:
1. Open Railway dashboard: https://railway.app
2. Click on your **PHP application** service (the one running your code, NOT MySQL)
3. Click the **Variables** tab
4. Click **+ New Variable** button
5. Add these **5 variables** one by one:

#### Copy these EXACTLY:

**Variable 1:**
```
Name:  DB_HOST
Value: ${{MySQL.MYSQLHOST}}
```

**Variable 2:**
```
Name:  DB_PORT
Value: ${{MySQL.MYSQLPORT}}
```

**Variable 3:**
```
Name:  DB_NAME
Value: ${{MySQL.MYSQLDATABASE}}
```

**Variable 4:**
```
Name:  DB_USER
Value: ${{MySQL.MYSQLUSER}}
```

**Variable 5:**
```
Name:  DB_PASS
Value: ${{MySQL.MYSQLPASSWORD}}
```

6. Click **Deploy** button to redeploy

**⏱️ Wait for deployment to finish (2-3 minutes)**

---

## ✅ STEP 2: Import Database Schema

### Action Required:
1. In Railway, click on your **MySQL** service
2. Click **Data** tab
3. Click **Query** button
4. I'll create a simplified schema for you to paste below

### What to paste in Railway MySQL Query:

I'll create a file called `railway_minimal_schema.sql` that you can copy from.

**After pasting:**
- Click **Run** or **Execute**
- Wait for "Success" message

---

## ✅ STEP 3: Verify Everything Works

### Action Required:
1. Find your Railway app URL (something like: `https://yourapp.railway.app`)
2. Add `/railway_db_diagnostic.php` to the end
3. Visit that URL in your browser
4. Take a screenshot and share it with me

**Example:** `https://eyeleyearn-env-production-up.railway.app/railway_db_diagnostic.php`

---

## 🆘 If You Get Stuck

**Problem:** Service reference `${{MySQL.MYSQLHOST}}` doesn't work

**Solution:** Use manual values instead:
1. Go to MySQL service → Variables tab
2. Copy the actual values
3. Use them directly:

```
DB_HOST=mysql.railway.internal
DB_PORT=3306  
DB_NAME=railway
DB_USER=root
DB_PASS=<paste actual password>
```

---

## What Happens Next?

Once you complete these 3 steps:
1. ✅ Your PHP app can connect to MySQL
2. ✅ Database tables are created
3. ✅ Data will start saving automatically

---

## Timeline

- **Step 1:** 2 minutes (add variables)
- **Step 2:** 2 minutes (import schema)
- **Step 3:** 30 seconds (verify)
- **Total:** ~5 minutes

---

## Ready to Start?

**Start with Step 1** - I'll wait for you to tell me when you're done, then we'll move to Step 2!
