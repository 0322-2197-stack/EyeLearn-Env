@echo off
REM Run Python eye tracking service on Windows host (outside Docker)
REM This allows direct camera access which Docker can't provide on Windows

echo Starting Python Eye Tracking Service on host...
echo.
echo Make sure you have Python installed and dependencies installed:
echo   pip install -r python_services/requirements.txt
echo.

cd /d %~dp0
python python_services/eye_tracking_service.py

pause

