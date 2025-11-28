@echo off
REM Migration Script Runner for Windows
REM This script runs the database migration using MySQL command line

echo ======================================
echo Running Database Migration
echo ======================================
echo.

REM Check if MySQL is accessible
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: MySQL command not found in PATH
    echo Please ensure MySQL is installed and added to PATH
    echo Or run this from XAMPP shell: mysql -u root elearn_db ^< database\migrations\001_consolidate_analytics_columns.sql
    pause
    exit /b 1
)

echo Step 1: Running migration script...
mysql -u root elearn_db < database\migrations\001_consolidate_analytics_columns.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ======================================
    echo Migration completed successfully!
    echo ======================================
    echo.
    echo Next steps:
    echo 1. Check the output above for verification results
    echo 2. Test eye tracking data collection
    echo 3. Verify dashboard displays
    echo.
) else (
    echo.
    echo ======================================
    echo Migration failed!
    echo ======================================
    echo Please check the error messages above
    echo.
)

pause
