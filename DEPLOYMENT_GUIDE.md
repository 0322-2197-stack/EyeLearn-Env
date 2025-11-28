# Deployment Guide

## Overview
This guide covers deployment for both **local development** and **production**.

## Local Development (Windows)

### Option 1: Run Python Service in Docker (Recommended for deployment testing)
```bash
docker-compose up -d
```
**Note:** On Windows, Docker may have limited camera access. If camera doesn't work, use Option 2.

### Option 2: Run Python Service on Host (For camera access)
If camera doesn't work in Docker on Windows:
1. Stop the Docker Python service:
   ```bash
   docker-compose stop python_service
   ```
2. Run Python service on host:
   ```bash
   # Install dependencies first (one time)
   pip install -r python_services/requirements.txt
   
   # Run the service
   run_python_service.bat
   ```
3. Keep other services in Docker:
   ```bash
   docker-compose up -d db web phpmyadmin
   ```

## Production Deployment

### Linux Server Deployment
1. **Enable camera access in Docker:**
   ```yaml
   # In docker-compose.yml, uncomment:
   devices:
     - /dev/video0:/dev/video0
   ```

2. **Deploy with Docker Compose:**
   ```bash
   docker-compose up -d
   ```

3. **Verify services:**
   ```bash
   docker-compose ps
   docker-compose logs python_service
   ```

### Cloud/Remote Server Deployment
For servers without direct camera access:
- The Python service will run in fallback mode
- Eye tracking will use simulated/demo mode
- All other features work normally

### Environment Variables
You can control camera behavior:
```bash
# Disable camera (for testing or servers without cameras)
CAMERA_ENABLED=false docker-compose up -d

# Or in docker-compose.yml:
environment:
  - CAMERA_ENABLED=false
```

## Service Architecture

```
┌─────────────────┐
│   Web Browser   │
└────────┬────────┘
         │
    ┌────┴────┐
    │   Web   │ (Port 80)
    │ Service │
    └────┬────┘
         │
    ┌────┴─────────────────┐
    │                       │
┌───▼───┐            ┌─────▼─────┐
│  DB   │            │  Python   │
│       │            │  Service  │
│(Port  │            │ (Port 5000)│
│ 3306) │            └───────────┘
└───────┘
```

## Troubleshooting

### Camera Not Working in Docker (Windows)
- **Solution:** Run Python service on host using `run_python_service.bat`
- This is a Windows Docker limitation, not a code issue

### Camera Not Working in Docker (Linux)
- Check camera device: `ls -l /dev/video*`
- Uncomment device mapping in docker-compose.yml
- Ensure user has camera permissions

### Service Won't Start
- Check logs: `docker-compose logs python_service`
- Verify Python dependencies are installed
- Check port 5000 is not in use

## Production Checklist

- [ ] All services start successfully
- [ ] Database initializes with correct schema
- [ ] Python service can access camera (if needed)
- [ ] Web service connects to database
- [ ] All API endpoints respond correctly
- [ ] Environment variables are set correctly

