#!/bin/bash
# Check open GitHub issues (todos)

echo "🔍 CHECKING OPEN TODOS"
echo "======================"

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI (gh) is not installed"
    echo "Install with: brew install gh"
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "❌ Not authenticated with GitHub"
    echo "Run: gh auth login"
    exit 1
fi

# List open issues
echo "📋 Open todos:"
gh issue list --repo hebbarp/todo-management --state open

echo ""
echo "📊 Summary:"
OPEN_COUNT=$(gh issue list --repo hebbarp/todo-management --state open --json number | jq '. | length')
echo "Total open todos: $OPEN_COUNT"

if [ "$OPEN_COUNT" -gt 0 ]; then
    echo ""
    echo "💡 To execute todos automatically, run:"
    echo "   claude execute my open todos"
fi