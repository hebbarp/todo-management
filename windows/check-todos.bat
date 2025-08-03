@echo off
rem Check open GitHub issues (todos)

echo "🔍 CHECKING OPEN TODOS"
echo "======================="

rem Check if gh CLI is installed
where gh >nul 2>nul
if %errorlevel% neq 0 (
    echo "❌ GitHub CLI (gh) is not installed"
    echo "Install from: https://cli.github.com/"
    exit /b 1
)

rem Check if authenticated
gh auth status >nul 2>nul
if %errorlevel% neq 0 (
    echo "❌ Not authenticated with GitHub"
    echo "Run: gh auth login"
    exit /b 1
)

rem List open issues
echo "📋 Open todos:"
gh issue list --repo Shreyasjainr/todo-management --state open

echo ""
echo "📊 Summary:"
for /f "delims=" %%i in ('gh issue list --repo Shreyasjainr/todo-management --state open --json number ^| jq ". | length"') do set OPEN_COUNT=%%i
echo "Total open todos: %OPEN_COUNT%"

if %OPEN_COUNT% gtr 0 (
    echo ""
    echo "💡 To execute todos automatically, run:"
    echo "   gemini execute my open todos"
)