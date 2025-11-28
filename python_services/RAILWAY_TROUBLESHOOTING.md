# Railway Deployment Troubleshooting Guide

## "Failed to snapshot repository" Error

This is a **Railway infrastructure issue**, not a code problem. Your code is correctly pushed to GitHub.

### Immediate Solutions

#### 1. Wait and Retry (Most Common Fix)
- **Wait 5-10 minutes** for Railway's systems to recover
- Go to Railway Dashboard → Your Service
- Click **Redeploy** or trigger a new deployment

#### 2. Reconnect GitHub Repository
1. Railway Dashboard → Your Service → **Settings**
2. Scroll to **Source** section
3. Click **Disconnect** (if connected)
4. Click **Connect GitHub** and reconnect your repository
5. Select branch: `main`
6. Set **Root Directory**: `python_services`
7. Save and redeploy

#### 3. Check Railway Status
- Visit: https://status.railway.app
- Check for ongoing incidents or maintenance

#### 4. Verify GitHub Repository Access
- Ensure repository is **public**, OR
- Railway has proper **permissions** to access your private repo
- Check GitHub → Settings → Applications → Railway permissions

#### 5. Manual Deployment Trigger
Create a small change to trigger deployment:
```bash
git commit --allow-empty -m "Trigger Railway deployment"
git push origin main
```

### Verify Your Configuration

#### Railway Service Settings
1. **Root Directory**: Should be `python_services`
2. **Dockerfile Path**: Should be `Dockerfile` (relative to root directory)
3. **Start Command**: `python3 -m uvicorn fastapi_service:app --host 0.0.0.0 --port $PORT --workers 1`

#### Environment Variables (Required)
- `CAMERA_ENABLED=0`
- `TRACKING_SAVE_URL` (your PHP endpoint)
- `ALLOWED_ORIGINS` (your frontend domain)
- `PORT` (auto-set by Railway - don't override)

### Alternative: Use Railway CLI

If web interface continues to fail:

1. Install Railway CLI:
   ```bash
   npm i -g @railway/cli
   ```

2. Login:
   ```bash
   railway login
   ```

3. Link and deploy:
   ```bash
   cd python_services
   railway link
   railway up
   ```

### Check Build Logs

If deployment starts but fails:
1. Railway Dashboard → Your Service → **Deployments**
2. Click on the latest deployment
3. Check **Build Logs** for errors
4. Check **Runtime Logs** for startup issues

### Common Issues After Snapshot Succeeds

#### Issue: "python3 not found"
- **Solution**: Already fixed in Dockerfile - uses `/bin/bash -c` with python3

#### Issue: "uvicorn not found"
- **Solution**: Already fixed - uses `python3 -m uvicorn`

#### Issue: "Wrong Dockerfile"
- **Solution**: Set Root Directory to `python_services` in Railway settings

### Still Having Issues?

1. **Check Railway Support**: https://railway.app/help
2. **GitHub Issues**: Check if repository has any access restrictions
3. **Try Different Branch**: Create a `deploy` branch and try deploying that
4. **Contact Railway Support**: If issue persists for >30 minutes

### Current Configuration Status

✅ All code changes pushed to GitHub
✅ Dockerfile configured correctly
✅ railway.json configured correctly
✅ Requirements.txt includes FastAPI dependencies
✅ Start script uses python3 correctly

**Next Step**: Wait 5-10 minutes, then retry deployment in Railway Dashboard.

