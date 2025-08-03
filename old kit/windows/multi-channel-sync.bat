@echo off
REM Multi-Channel Todo Synchronization Script for Windows
REM Runs the complete multi-channel sync process

echo 🚀 MULTI-CHANNEL TODO SYNC
echo ===========================

echo 📅 Starting sync at %date% %time%

REM Check if Python is available
python --version >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Python not found. Please install Python first.
    pause
    exit /b 1
)

REM Navigate to python directory
cd /d "%~dp0\..\python"

echo 🔄 Synchronizing all channels...
echo.

REM Run the multi-channel sync
python multi_channel_sync.py

if %errorlevel% equ 0 (
    echo.
    echo ✅ Multi-channel sync completed successfully!
    echo.
    echo 📊 Check the generated reports:
    echo    • Unified report: unified_todo_report_*.json
    echo    • Emergency backup: emergency_backup_*.json
    echo    • Summary text: unified_summary_*.txt
    echo.
) else (
    echo.
    echo ❌ Sync encountered some issues. Check the output above.
    echo.
)

echo 📋 Quick status check...
echo.

REM Show current todos from WhatsApp
echo 📱 WhatsApp Todos:
python -c "from whatsapp_todo_integration import WhatsAppTodoManager; m=WhatsAppTodoManager(); todos=m.list_todos('919742814697'); print(f'   Pending: {len(todos)}') if todos else print('   No pending todos')"

REM Show current todos from Sheets
echo 📊 Google Sheets Todos:
python -c "from google_sheets_integration import GoogleSheetsTodoManager; m=GoogleSheetsTodoManager(); todos=m.list_todos('pending'); print(f'   Pending: {len(todos)}') if todos else print('   No pending todos')"

echo.
echo 🎉 Sync process complete!
echo.
pause