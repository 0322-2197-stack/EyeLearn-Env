# Browser-to-Railway Eye Tracking Pipeline

This guide explains how to capture frames in the browser, stream them to a FastAPI service on Railway, and forward the resulting gaze metrics back to the existing PHP endpoint.

---

## Architecture Diagram

```
 ┌───────────────┐        HTTPS/WebSocket        ┌────────────────────┐
 │  Browser SPA  │  ───────────────────────────▶ │  Railway FastAPI   │
 │  (getUserMedia│                               │  web_stream_service│
 │  + JS client) │ ◀────── JSON responses ────── │  (no webcam access)│
 └──────┬────────┘                                └─────────┬─────────┘
        │  POST /ws                                     async POST
        │                                                (non-blocking)
        ▼
┌─────────────────────┐                         ┌──────────────────────────┐
│ PHP Tracking API    │◀─────────────────────── │ Eye model inference loop │
│ (TRACKING_SAVE_URL) │                         │ (MediaPipe/custom model)│
└─────────────────────┘                         └──────────────────────────┘

* Railway never touches a physical camera. The browser owns all capture duties.
* Results travel browser ➜ FastAPI ➜ PHP analytics ➜ database.
```

---

## Frontend Responsibilities

1. Request camera access with `navigator.mediaDevices.getUserMedia()`.
2. Draw frames onto a hidden `<canvas>` to encode JPEG Base64 blobs at 5–10 FPS.
3. Send each frame to `POST /api/frames` (or `WS /ws/frames`) with metadata: `user_id`, `module_id`, etc.
4. Handle denied permissions, missing devices, and backend timeouts with user-friendly messages + retry backoff.
5. Never store frames locally unless feature flagged.

See `web_eye_client.html` for a full reference implementation.

---

## Python Backend (FastAPI)

Located at `python_services/web_stream_service.py`.

Key features:

- Enforces `CAMERA_ENABLED=False` by default so Railway never tries to open a webcam.
- Accepts Base64 frames via REST or WebSocket, validates payload size, and decodes using `opencv-python-headless`.
- Reuses the same `EyeTrackingService` class from the legacy Flask workflow, so gaze detection, blink logic, and metric payloads are identical between local and browser-streamed modes.
- Asynchronously forwards each result to the PHP endpoint defined by `TRACKING_SAVE_URL`.
- Applies strict CORS (comma-separated `ALLOWED_ORIGINS`) and rejects oversized frames (`MAX_FRAME_KB`) to mitigate abuse.
- Offers `/healthz` for Railway health checks.

---

## Communication Protocol

| Property              | REST (`/api/frames`)                   | WebSocket (`/ws/frames`)                         |
|-----------------------|----------------------------------------|--------------------------------------------------|
| Payload               | JSON with Base64 `data:image/jpeg`     | Same JSON, sent as text frames                   |
| Acknowledgement       | Immediate JSON response per frame      | Response message per frame                       |
| Rate Limiting         | Browser controlled interval (5–10 FPS) | Server-side token bucket enforced per client     |
| Error Handling        | HTTP codes + retry/backoff             | JSON error message, client decides to retry      |
| Fallback Behaviour    | Client caches status, exponential backoff, resume when backend is ready |

---

## Environment Variables

Current production `.env` (`python_services/env.example`):

```
PYTHON_SERVICE_URL=https://resilient-magic-production-2e23.up.railway.app
TRACKING_SAVE_URL=https://eyelearn-env-production.up.railway.app/user/database/save_enhanced_tracking.php
CAMERA_ENABLED=0
ALLOWED_ORIGINS=https://eyelearn-env-production.up.railway.app
MAX_FRAME_KB=512
MAX_CLIENT_FPS=10
MODEL_NAME=mediapipe-face-mesh
ENABLE_BROWSER_EYE_STREAMING=1
```

Sample `.env.local` for the browser build (if bundling):

```
VITE_PYTHON_SERVICE_URL=https://railway-eye-service.up.railway.app
VITE_MAX_CAPTURE_FPS=7
```

---

## Railway Deployment Steps (Python Service)

1. **Install dependencies locally**  
   ```bash
   cd python_services
   pip install -r requirements_railway.txt
   ```
2. **Update `railway.json`** (if needed) to point to `python_services/web_stream_service.py`.
3. **Set Railway variables** (Dashboard → Variables):  
   - `PYTHON_SERVICE_URL` set to the generated Railway domain.  
   - `TRACKING_SAVE_URL` set to your PHP endpoint.  
   - `CAMERA_ENABLED=0`.  
   - `ALLOWED_ORIGINS=https://elearn.mydomain.com`.
4. **Deploy**  
   - `railway up` (CLI) or click "Deploy" in the Railway dashboard.  
   - Ensure build uses `python_services/requirements_railway.txt`.
5. **Verify**  
   - Hit `https://<railway-app>.up.railway.app/healthz`.  
   - Stream from `web_eye_client.html` and check logs in Railway + PHP endpoint.

---

## Connecting Frontend ↔ Backend

1. Host `web_eye_client.html` (or integrate the JS into your SPA).
2. Configure `PYTHON_SERVICE_URL` (env or inline constant) to your Railway domain.
3. Serve over HTTPS so browsers allow `getUserMedia()`.
4. During course launch, bootstrap the JS client with user/module metadata:
   ```js
   window.currentUserId = "<?php echo $userId; ?>";
   window.currentModuleId = "<?php echo $moduleId; ?>";
   ```
5. Start streaming; monitor responses for `attention_score`, `gaze_vector`, etc.  
   Backend will simultaneously POST the same JSON to `TRACKING_SAVE_URL`, allowing PHP to persist results.

---

## Production Notes

- **Security:**  
  - Enforce HTTPS everywhere.  
  - Keep `MAX_FRAME_KB` reasonable (≤512 KB) and set an nginx/uvicorn body size limit.  
  - Strip or hash any PII before logging; disable verbose logs in production.
- **Performance:**  
  - Keep FPS ≤10; higher frame rates increase bandwidth/CPU without stronger signals.  
  - Use WebSocket for lower latency when you need continuous updates; fall back to REST on failure.
- **Storage controls:**  
  - Only persist derived metrics. Add a feature flag if you must store frames for debugging, and default it off.

---

## Next Steps

1. (Done) FastAPI now calls the same `EyeTrackingService` core used by the Flask app, so no further model swaps are required unless you change the legacy pipeline itself.
2. Wire the JS example into your PHP/Laravel/Vite front-end build.
3. Add automated tests hitting `/api/frames` with fixture images to guard against regressions.
4. Configure Railway alerts (deployment & runtime) plus PHP endpoint monitoring to catch outages quickly.

