# Railway FastAPI Configuration Guide

## Required Railway Dashboard Settings

### 1. Environment Variables
Go to your Railway service → **Variables** tab and set:

| Variable | Value | Required | Notes |
|----------|-------|----------|-------|
| `PORT` | (auto-set) | ✅ | Automatically set by Railway - **DO NOT override** |
| `CAMERA_ENABLED` | `0` | ✅ | **Must be 0** for Railway (no physical camera) |
| `TRACKING_SAVE_URL` | `https://your-domain.com/user/database/save_enhanced_tracking.php` | ✅ | Your PHP backend endpoint |
| `ALLOWED_ORIGINS` | `https://your-domain.com` | ⚠️ | Comma-separated list of allowed origins |
| `PYTHON_SERVICE_URL` | `https://your-railway-service.up.railway.app` | ⚠️ | Your Railway service URL (for frontend) |

### 2. Service Root Directory
**Important**: Railway needs to know where your service is located.

**Option A: If Railway service root is the repository root:**
- The `python.Dockerfile` path in `railway.json` is correct
- No changes needed

**Option B: If Railway service root is `python_services` folder:**
- Update `railway.json` dockerfilePath to: `"../python.Dockerfile"`
- OR move `python.Dockerfile` to `python_services/` directory

### 3. Build Settings
In Railway Dashboard → **Settings** → **Build**:

- **Builder**: Should be `DOCKERFILE` (from railway.json)
- **Dockerfile Path**: `python.Dockerfile` (or adjust based on service root)
- **Build Command**: (auto-detected from Dockerfile)

### 4. Deploy Settings
In Railway Dashboard → **Settings** → **Deploy**:

- **Start Command**: Should be automatically set from `railway.json`:
  ```
  uvicorn fastapi_service:app --host 0.0.0.0 --port $PORT --workers 1
  ```
- **Restart Policy**: `ON_FAILURE` (from railway.json)
- **Max Retries**: `10` (from railway.json)

### 5. Network/Port Settings
- Railway automatically assigns a port via `$PORT` environment variable
- Your service will be accessible at: `https://your-service-name.up.railway.app`
- **No manual port configuration needed**

## Quick Setup Checklist

- [ ] Set `CAMERA_ENABLED=0` in Railway Variables
- [ ] Set `TRACKING_SAVE_URL` to your PHP endpoint
- [ ] Set `ALLOWED_ORIGINS` to your frontend domain
- [ ] Verify service root directory matches Dockerfile path
- [ ] Deploy and check logs for any errors
- [ ] Test health endpoint: `https://your-service.up.railway.app/api/health`
- [ ] Test API docs: `https://your-service.up.railway.app/api/docs`

## Testing After Deployment

1. **Health Check**:
   ```bash
   curl https://your-service.up.railway.app/api/health
   ```

2. **API Documentation**:
   Visit: `https://your-service.up.railway.app/api/docs`

3. **WebSocket Test**:
   Connect to: `wss://your-service.up.railway.app/ws/tracking`

## Troubleshooting

### Service won't start
- Check Railway logs for errors
- Verify all environment variables are set
- Ensure Dockerfile path is correct

### Import errors
- Verify `python_services` is the working directory
- Check that all files are in the correct location

### Port binding errors
- Railway sets `$PORT` automatically - don't hardcode it
- Ensure uvicorn command uses `--port $PORT`

### Camera errors
- Set `CAMERA_ENABLED=0` for Railway deployment
- Service will use browser streaming instead

