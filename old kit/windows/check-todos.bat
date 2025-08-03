@echo off
REM Check open GitHub issues (todos)

echo 🔍 CHECKING OPEN TODOS
echo ======================

REM Check if gh CLI is installed
where gh >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ GitHub CLI (gh) is not installed
    echo Download from: https://cli.github.com/
    echo Or install with: winget install GitHub.cli
    pause
    exit /b 1
)

REM Check if authenticated
gh auth status >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Not authenticated with GitHub
    echo Run: gh auth login
    pause
    exit /b 1
)

REM List open issues
echo 📋 Open todos:
gh issue list --repo hebbarp/todo-management --state open

echo.
echo 📊 Summary:
REM Count open issues (Windows doesn't have jq by default, so we'll use a simpler approach)
for /f %%i in ('gh issue list --repo hebbarp/todo-management --state open ^| find /c /v ""') do set OPEN_COUNT=%%i

echo Total open todos: %OPEN_COUNT%

if %OPEN_COUNT% gtr 0 (
    echo.
    echo 💡 To execute todos automatically, run:
    echo    claude execute my open todos
)

echo.
echo ✅ Todo check completed!
pause