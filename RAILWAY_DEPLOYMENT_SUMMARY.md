# Railway Deployment Configuration Summary

## Overview
This document summarizes the changes made to prepare the e-learning platform (Python eye-tracking service + PHP backend) for Railway/PaaS deployment while maintaining full backward compatibility for local development.

## Changes Made

### Python Eye Tracking Service (`python_services/eye_tracking_service.py`)

#### 1. Environment-Based Configuration (Lines 23-31)
- Added `TRACKING_SAVE_URL` environment variable with default to local endpoint
- Added `CAMERA_ENABLED` flag (defaults to enabled for backward compatibility)
- Both use sensible local defaults when not set

#### 2. Camera Initialization (Lines 463-468)
- Modified `start_webcam()` to check `CAMERA_ENABLED` before attempting camera access
- When disabled, gracefully falls back to existing fallback tracker logic
- Prevents camera restart loops in containerized environments

#### 3. Configurable Backend URL (Line 936)
- Updated `save_tracking_data()` to use `TRACKING_SAVE_URL` instead of hardcoded URL
- Maintains existing timeout=5 and robust error handling

#### 4. PaaS-Ready Startup (Lines 1583-1588)
- Reads `PORT` from environment (defaults to 5000)
- Binds to `0.0.0.0` instead of `127.0.0.1` for Railway compatibility
- Still works locally when no PORT is set

#### 5. Logging Improvements
- Downgraded frequent frame processing logs to `logger.debug` level
- Keeps important warnings/errors at appropriate levels

#### 6. Documentation (Lines 1590-1616)
- Added dependency list and deployment notes as comments
- Included Railway deployment example

### PHP Backend

#### 1. Database Connection (`database/db_connection.php`)
- **Environment Variable Support** (Lines 15-21):
  - `DB_HOST` (default: 'localhost')
  - `DB_NAME` (default: 'elearn_db')
  - `DB_USER` (default: 'root')
  - `DB_PASS` (default: '')
  - `DB_PORT` (default: '3306')
- Updated both PDO and mysqli connections to use environment variables
- Maintains backward compatibility with existing `config_environment.php` approach
- Updated fallback configurations to include port support

#### 2. Save Endpoint (`user/database/save_enhanced_tracking.php`)
- Added comprehensive header comment explaining this is the entry point for Python service
- Documents Railway deployment requirements
- No functional changes - maintains existing API contract

#### 3. Health Check Endpoint (`health.php`)
- New endpoint for Railway health checks
- Returns HTTP 200 with JSON status
- Tests database connection and reports status
- Suitable for Railway health check configuration

## API Contracts Preserved

### Python Service Endpoints (Unchanged)
- `/status` - Returns HTTP 200 JSON
- `/api/start_tracking` - POST with user_id, module_id, section_id
- `/api/stop_tracking` - POST
- `/api/status` - GET returns comprehensive status
- `/api/frame` - GET returns current frame
- `/api/metrics` - GET returns detailed metrics
- `/api/health` - GET returns health status

### PHP Endpoints (Unchanged)
- `/user/database/save_enhanced_tracking.php` - POST with JSON payload
  - Expected fields: user_id, module_id, focused_time, unfocused_time, total_time, focus_percentage
  - Returns: success, record_id, data_saved

## Environment Variables

### Python Service
```bash
PORT=5000                                    # Server port (default: 5000)
TRACKING_SAVE_URL=http://...                 # PHP endpoint URL
CAMERA_ENABLED=1                             # 0/false to disable camera
```

### PHP Backend
```bash
DB_HOST=localhost                            # Database host
DB_NAME=elearn_db                           # Database name
DB_USER=root                                # Database user
DB_PASS=                                    # Database password
DB_PORT=3306                                # Database port
```

## Railway Health Check Configuration

### Python Service
- **Endpoint**: `/status` or `/api/health`
- **Method**: GET
- **Expected Response**: HTTP 200 with JSON `{"status": "ok", ...}`

### PHP Backend
- **Endpoint**: `/health.php`
- **Method**: GET
- **Expected Response**: HTTP 200 with JSON `{"status": "ok", ...}`

## Manual Testing Steps

### 1. Test Python Service (Local, Camera Disabled)
```bash
# Set environment variables
export CAMERA_ENABLED=0
export PORT=5000

# Start service
python python_services/eye_tracking_service.py

# Test endpoints
curl http://localhost:5000/status
curl http://localhost:5000/api/health
curl -X POST http://localhost:5000/api/start_tracking \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "module_id": 1}'
curl http://localhost:5000/api/metrics
```

### 2. Test PHP Backend
```bash
# Test health endpoint
curl http://localhost/capstone/health.php

# Test save endpoint (simulate Python service)
curl -X POST http://localhost/capstone/user/database/save_enhanced_tracking.php \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "module_id": 1,
    "focused_time": 5.0,
    "unfocused_time": 2.0,
    "total_time": 7.0,
    "focus_percentage": 71.4
  }'
```

### 3. Test Cross-Service Integration
```bash
# Set Python service to use PHP endpoint
export TRACKING_SAVE_URL=http://localhost/capstone/user/database/save_enhanced_tracking.php

# Start Python service and trigger a save
# Verify data appears in database
```

## Remaining Assumptions & Considerations

### 1. URL Configuration
- **Assumption**: The `TRACKING_SAVE_URL` in Python service must be set to the full URL of the PHP endpoint
- **Railway Consideration**: When deploying on Railway:
  - Python service and PHP backend may be on different services/domains
  - Set `TRACKING_SAVE_URL` to the Railway-provided PHP service URL
  - Example: `TRACKING_SAVE_URL=https://your-php-service.railway.app/user/database/save_enhanced_tracking.php`

### 2. Database Access
- **Assumption**: PHP backend can access MySQL database
- **Railway Consideration**: 
  - Use Railway's MySQL service or external database
  - Set `DB_HOST` to Railway database hostname (not localhost)
  - Ensure network connectivity between PHP service and database

### 3. File System
- **Assumption**: No file system writes in critical paths
- **Status**: ✅ No file system dependencies found in eye-tracking flow

### 4. Session Management
- **Assumption**: PHP sessions work in stateless/containerized environment
- **Railway Consideration**: 
  - Sessions may need Redis or database-backed storage
  - Current `session_start()` in `save_enhanced_tracking.php` may need adjustment for stateless deployment

### 5. CORS Configuration
- **Current**: `Access-Control-Allow-Origin: *` (allows all origins)
- **Railway Consideration**: 
  - For production, consider restricting to specific domains
  - Update CORS headers if frontend is on different domain

### 6. Absolute URLs
- **Status**: ✅ No hardcoded absolute URLs found in critical paths
- **Note**: Default `TRACKING_SAVE_URL` uses localhost, but this is overridden via environment variable

### 7. Camera Access
- **Railway Consideration**: 
  - Railway containers typically don't have camera access
  - Set `CAMERA_ENABLED=0` for Railway deployment
  - Service will use fallback tracker and continue functioning

## Deployment Checklist

### Python Service
- [ ] Set `PORT` environment variable (Railway auto-sets this)
- [ ] Set `TRACKING_SAVE_URL` to PHP service URL
- [ ] Set `CAMERA_ENABLED=0` for Railway (no camera access)
- [ ] Configure Railway health check: `/status` or `/api/health`
- [ ] Install dependencies: Flask, flask-cors, opencv-python, mediapipe, numpy, requests

### PHP Backend
- [ ] Set database environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`)
- [ ] Configure Railway health check: `/health.php`
- [ ] Ensure `save_enhanced_tracking.php` is accessible at configured URL
- [ ] Verify database connectivity from PHP service

### Cross-Service
- [ ] Verify Python service can reach PHP endpoint (test `TRACKING_SAVE_URL`)
- [ ] Test end-to-end: Python service → PHP endpoint → Database
- [ ] Monitor logs for connection errors

## Files Modified

1. `python_services/eye_tracking_service.py` - Environment config, camera handling, startup
2. `database/db_connection.php` - Environment variable support for DB config
3. `user/database/save_enhanced_tracking.php` - Added documentation comments
4. `health.php` - New health check endpoint

## Files Created

1. `health.php` - PHP health check endpoint
2. `RAILWAY_DEPLOYMENT_SUMMARY.md` - This document

## Backward Compatibility

✅ **All changes are backward compatible:**
- Default values match existing local development setup
- No API contracts changed
- No breaking changes to existing functionality
- Local development continues to work without environment variables

## Next Steps for Railway Deployment

1. Create Railway services for Python and PHP
2. Configure environment variables in Railway dashboard
3. Set up Railway MySQL service or connect external database
4. Configure health checks for both services
5. Test end-to-end functionality
6. Monitor logs and adjust as needed

