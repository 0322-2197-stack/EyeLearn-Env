@echo off
echo ================================================
echo  Railway Deployment Preparation for EyeLearn
echo ================================================
echo.

cd /d c:\xampp\htdocs\capstone\python_services

echo [1/3] Adding Railway configuration files to git...
git add railway.json nixpacks.toml Procfile runtime.txt .railwayignore

echo.
echo [2/3] Committing changes...
git commit -m "Add Railway deployment configuration for Python eye tracking service"

echo.
echo [3/3] Pushing to GitHub...
git push origin main

echo.
echo ================================================
echo  ✅ Files pushed to GitHub!
echo ================================================
echo.
echo NEXT STEPS:
echo 1. Go to Railway Dashboard: https://railway.app/dashboard
echo 2. Click "+ New Service" in your existing EyeLearn project
echo 3. Select "GitHub Repo" and choose your repository
echo 4. Set Root Directory to: python_services
echo 5. Add these environment variables to the Python service:
echo    - PORT=5000
echo    - CAMERA_ENABLED=0
echo    - TRACKING_SAVE_URL=https://your-php-service.railway.app/user/database/save_enhanced_tracking.php
echo 6. Deploy!
echo.
echo After deployment, add this to your PHP service:
echo    - EYE_TRACKING_SERVICE_URL=https://your-python-service.up.railway.app
echo.
pause
