@echo off
REM Weekly cleanup script

echo 🧹 WEEKLY CLEANUP
echo ==================

REM Get current date in YYYY-MM format
for /f "tokens=1-3 delims=/" %%a in ('date /t') do (
    set MM=%%a
    set DD=%%b
    set YYYY=%%c
)
set ARCHIVE_DIR=archive\%YYYY%-%MM%

REM Create archive directory if it doesn't exist
if not exist "%ARCHIVE_DIR%" (
    echo 📁 Creating archive directory: %ARCHIVE_DIR%
    mkdir "%ARCHIVE_DIR%" 2>nul
)

REM Archive old log files
if exist *.log (
    echo 📦 Archiving log files...
    move *.log "%ARCHIVE_DIR%\" 2>nul
)

REM Archive old txt files (but keep important ones)
echo 📦 Archiving old text files...
for %%f in (*.txt) do (
    if not "%%f"=="README.txt" (
        move "%%f" "%ARCHIVE_DIR%\" 2>nul
    )
)

REM Clean up Python cache
echo 🧹 Cleaning Python cache...
for /d /r . %%d in (__pycache__) do (
    if exist "%%d" (
        rmdir /s /q "%%d" 2>nul
    )
)

REM Delete .pyc files
for /r . %%f in (*.pyc) do (
    if exist "%%f" (
        del /q "%%f" 2>nul
    )
)

REM Clean up temporary files
echo 🧹 Cleaning temporary files...
del /q *.tmp 2>nul
del /q *.temp 2>nul
del /q *~ 2>nul

REM Clean up Windows-specific temp files
del /q Thumbs.db 2>nul
del /q Desktop.ini 2>nul

REM Compress old archives (older than 30 days) - using PowerShell for date comparison
echo 🗜️ Compressing old archives...
powershell -Command "Get-ChildItem -Path 'archive' -Recurse -File -Name '*.txt' | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-30)} | ForEach-Object {Compress-Archive -Path $_.FullName -DestinationPath ($_.FullName + '.zip') -Force; Remove-Item $_.FullName}" 2>nul

REM Summary
echo.
echo ✅ Weekly cleanup completed!
echo 📅 %date% %time%

REM Log cleanup
echo %date% %time%: Weekly cleanup completed >> "%ARCHIVE_DIR%\cleanup.log"

echo.
echo Press any key to continue...
pause >nul