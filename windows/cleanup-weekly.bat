@echo off
rem Weekly cleanup script

echo "💤 WEEKLY CLEANUP"
echo "=================="

rem Create archive directory if it doesn't exist
for /f "tokens=1-3 delims=/ " %%a in ('date /t') do (
    set "archive_dir=archive\%%c-%%a"
)
mkdir "%archive_dir%" 2>nul

rem Archive old log files
if exist *.log (
    echo "📦 Archiving log files..."
    move /Y *.log "%archive_dir%\" >nul 2>nul
)

rem Archive old txt files (but keep important ones)
echo "📦 Archiving old text files..."
for %%F in (*.txt) do (
    if /I not "%%F"=="README.txt" (
        move /Y "%%F" "%archive_dir%\" >nul 2>nul
    )
)

rem Clean up Python cache
echo "💤 Cleaning Python cache..."
for /d /r . %%d in (__pycache__) do (
    if exist "%%d" rmdir /s /q "%%d"
)
del /s /q *.pyc >nul 2>nul

rem Clean up temporary files
echo "💤 Cleaning temporary files..."
del /s /q *.tmp >nul 2>nul
del /s /q *.temp >nul 2>nul
del /s /q *~ >nul 2>nul

rem Compress old archives (older than 30 days)
echo "📄  Compressing old archives..."
forfiles /p archive /s /m *.txt /d -30 /c "cmd /c gzip @path"

rem Summary
echo ""
echo "✅ Weekly cleanup completed!"
echo "📅 %date%"

rem Log cleanup
echo %date%: Weekly cleanup completed >> archive\cleanup.log
