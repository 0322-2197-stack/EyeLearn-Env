# Railway Configuration for Eye Tracking Service

## 📋 What You Need to Do in Railway

### 1. Environment Variables (REQUIRED)

In your **Python service** on Railway, add these environment variables:

1. Go to your Python service in Railway dashboard
2. Click **Variables** tab
3. Add the following:

```bash
CAMERA_ENABLED=false
```

**Optional variables:**
```bash
# If you need to set specific origins for CORS
ALLOWED_ORIGINS=https://your-php-app.railway.app

# If your PHP app needs to save tracking data to a specific URL
# TRACKING_SAVE_URL=https://your-php-app.railway.app/user/database/save_enhanced_tracking.php
```

### 2. Link Python Service to PHP App

In your **PHP service** on Railway, add this environment variable:

```bash
PYTHON_SERVICE_URL=https://your-python-service.railway.app
```

Replace `your-python-service.railway.app` with your actual Python service Railway URL.

**How to find your Python service URL:**
1. Open Python service in Railway
2. Go to **Settings** → **Networking**
3. Copy the public URL (e.g., `https://capstone-python-production.up.railway.app`)

### 3. Deploy

1. **Commit and push** the code changes to your repository
2. Railway will **automatically redeploy** both services
3. Check the **deployment logs** to verify:
   - Python service: Look for `Eye tracking service initialized (camera_enabled=False)`
   - PHP service: No camera errors should appear

## ✅ Verification Checklist

After deployment, verify:

- [ ] Python service starts without camera initialization errors
- [ ] PHP app loads module pages correctly
- [ ] Browser prompts for camera permission (client-side)
- [ ] Eye tracking widget appears on module pages
- [ ] Focus/unfocus detection works
- [ ] Data saves to database correctly

## 🐛 Troubleshooting

### Python service fails to start
- Check Railway logs for errors
- Ensure `CAMERA_ENABLED=false` is set
- Verify `requirements.txt` includes all dependencies

### Eye tracking not working in browser
- Check browser console (F12) for errors
- Verify `PYTHON_SERVICE_URL` is set correctly in PHP service
- Ensure both services are on HTTPS (Railway provides this)
- Check that browser has camera permissions

### CORS errors
- Set `ALLOWED_ORIGINS` to your PHP app URL
- Or use `ALLOWED_ORIGINS=*` for development (not recommended for production)

## 📝 Files Modified

The following files were updated to support Railway deployment:

1. **python_services/fastapi_service.py** - Disabled server camera by default
2. **python_services/eye_tracking_service.py** - Changed CAMERA_ENABLED default to false
3. **python_services/nixpacks.toml** - Added CAMERA_ENABLED=false variable
4. **python_services/.env.railway** - Environment variable template (reference only)
5. **config.php** - Added Python service URL auto-detection

## 🎯 How It Works Now

```
┌─────────────────┐
│  User Browser   │
│  (Has Camera)   │
└────────┬────────┘
         │ getUserMedia()
         │ Capture frames @ 7 FPS
         │
         ▼
┌─────────────────────────┐
│   JavaScript            │
│   cv-eye-tracking.js    │
└────────┬────────────────┘
         │ POST /api/frames
         │ Send base64 frames
         │
         ▼
┌─────────────────────────┐
│   FastAPI on Railway    │
│   (No Camera Access)    │
│   - MediaPipe processing│
│   - Gaze detection      │
│   - Focus tracking      │
└────────┬────────────────┘
         │ Return metrics
         │
         ▼
┌─────────────────────────┐
│   Browser UI Updates    │
│   - Focus status        │
│   - Session time        │
│   - Video feed          │
└─────────────────────────┘
```

## 🔐 Security Notes

- Camera access happens **only in the user's browser** (client-side)
- Frames are sent over HTTPS to Railway
- No camera hardware needed on server
- User must grant camera permission in browser

## 📱 Browser Requirements

- Modern browser with `getUserMedia()` API support:
  - Chrome 53+
  - Firefox 36+
  - Edge 12+
  - Safari 11+
- HTTPS connection (Railway provides this automatically)
- Camera permission granted by user
