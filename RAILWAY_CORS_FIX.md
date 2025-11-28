# Railway CORS Error - Quick Fix

## Problem
Your Python and PHP services can't communicate due to CORS blocking.

## Solution - Add These Environment Variables

### Python Service Variables (Railway Dashboard)

Add these to your **Python eye tracking service**:

```bash
CAMERA_ENABLED=false
ALLOWED_ORIGINS=https://eyelearn-mvc-production-2e23-up.railway.app
TRACKING_SAVE_URL=https://eyelearn-mvc-production-2e23-up.railway.app/user/database/save_enhanced_tracking.php
```

Replace `eyelearn-mvc-production-2e23-up.railway.app` with your actual PHP app Railway URL.

### PHP Service Variables (Railway Dashboard)

Add this to your **PHP app service**:

```bash
PYTHON_SERVICE_URL=https://cv-eye-tracking.up.railway.app
```

Replace with your actual Python service Railway URL.

## How to Find Your Railway URLs

1. **Python Service URL**:
   - Open Python service in Railway
   - Go to **Settings** → **Networking** → **Public Networking**
   - Copy the domain (e.g., `cv-eye-tracking.up.railway.app`)

2. **PHP Service URL**:
   - Open PHP service in Railway
   - Go to **Settings** → **Networking** → **Public Networking**  
   - Copy the domain (e.g., `eyelearn-mvc-production-2e23-up.railway.app`)

## Why This Fixes It

- `ALLOWED_ORIGINS` - Tells Python service to accept requests from your PHP app domain
- `TRACKING_SAVE_URL` - Tells Python where to save eye tracking data
- `PYTHON_SERVICE_URL` - Tells PHP where the eye tracking service is

## After Adding Variables

1. Railway will **auto-redeploy** both services
2. Wait for deployments to complete
3. Check logs - CORS errors should be gone
4. Test the module page - eye tracking should work

## Still Getting Errors?

Check that:
- [ ] Both services are deployed and running
- [ ] URLs include `https://` prefix
- [ ] URLs don't have trailing slashes
- [ ] Services can reach each other (both on Railway network)
