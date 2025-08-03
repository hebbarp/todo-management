@echo off
rem Run analytics dashboard

echo "📊 RUNNING ANALYTICS DASHBOARD"
echo "==============================="

rem Check if Python script exists
if not exist "..\python\analytics_dashboard.py" (
    echo "❌ Analytics dashboard script not found"
    echo "Expected location: python\analytics_dashboard.py"
    exit /b 1
)

rem Create output directory
mkdir "..\analytics_output" 2>nul

rem Run analytics
echo "🔄 Generating analytics report..."
cd ..\python
python analytics_dashboard.py

if %errorlevel% equ 0 (
    echo "✅ Analytics report generated successfully!"
    echo "📂 Output location: analytics_output\"
    
    rem Open the report
    if exist "..\analytics_output\analytics_report.html" (
        echo "🌐 Opening report in browser..."
        start "" "..\analytics_output\analytics_report.html"
    )
) else (
    echo "❌ Analytics generation failed"
    exit /b 1
)

