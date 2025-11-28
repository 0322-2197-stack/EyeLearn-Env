# Railway Configuration Fix

## Problem
Railway is using the root `Dockerfile` (PHP/Apache) instead of `python_services/Dockerfile` (Python/FastAPI).

## Solution: Configure Service Root in Railway Dashboard

### Step 1: Set Service Root Directory
1. Go to Railway Dashboard → Your Python Service
2. Click on **Settings** tab
3. Scroll to **Root Directory** section
4. Set Root Directory to: `python_services`
5. Click **Save**

### Step 2: Verify Dockerfile Path
After setting the root directory, Railway should automatically detect:
- `python_services/Dockerfile` (Python/FastAPI)
- `python_services/railway.json` (configuration)

### Step 3: Redeploy
1. Go to **Deployments** tab
2. Click **Redeploy** or push a new commit

## Alternative: If You Can't Change Root Directory

If Railway service root must be the repository root, update `railway.json`:

```json
{
    "build": {
        "builder": "DOCKERFILE",
        "dockerfilePath": "python_services/Dockerfile"
    }
}
```

But **recommended solution** is to set Root Directory to `python_services` in Railway dashboard.

