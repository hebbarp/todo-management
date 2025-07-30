@echo off
REM Run analytics dashboard

echo 📊 RUNNING ANALYTICS DASHBOARD
echo ===============================

REM Check if Python script exists
if not exist "..\python\analytics_dashboard.py" (
    echo ❌ Analytics dashboard script not found
    echo Expected location: python\analytics_dashboard.py
    echo.
    echo You may need to create this script first.
    pause
    exit /b 1
)

REM Check if Python is installed
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Python is not installed or not in PATH
    echo Download Python from: https://python.org
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

REM Create output directory
if not exist "..\analytics_output" (
    echo 📁 Creating analytics_output directory...
    mkdir "..\analytics_output"
)

REM Run analytics
echo 🔄 Generating analytics report...
cd ..\python

python analytics_dashboard.py

if %errorlevel% equ 0 (
    echo ✅ Analytics report generated successfully!
    echo 📂 Output location: analytics_output\
    
    REM Open the report if it exists
    if exist "..\analytics_output\analytics_report.html" (
        echo 🌐 Opening report in browser...
        start "" "..\analytics_output\analytics_report.html"
    )
    
    echo.
    echo 🎉 Analytics completed successfully!
) else (
    echo ❌ Analytics generation failed
    echo.
    echo Troubleshooting:
    echo - Check if required Python packages are installed
    echo - Verify your .env file is configured correctly
    echo - Check if GitHub CLI is authenticated
    echo.
    pause
    exit /b 1
)

REM Return to windows directory
cd ..\windows

echo.
echo Press any key to continue...
pause >nul