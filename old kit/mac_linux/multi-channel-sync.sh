#!/bin/bash
# Multi-Channel Todo Synchronization Script for macOS/Linux
# Runs the complete multi-channel sync process

echo "🚀 MULTI-CHANNEL TODO SYNC"
echo "==========================="

echo "📅 Starting sync at $(date)"

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 not found. Please install Python 3.9+ first."
    exit 1
fi

# Navigate to python directory
cd "$(dirname "$0")/../python" || exit 1

echo "🔄 Synchronizing all channels..."
echo

# Run the multi-channel sync
python3 multi_channel_sync.py

if [ $? -eq 0 ]; then
    echo
    echo "✅ Multi-channel sync completed successfully!"
    echo
    echo "📊 Check the generated reports:"
    echo "   • Unified report: unified_todo_report_*.json"
    echo "   • Emergency backup: emergency_backup_*.json"
    echo "   • Summary text: unified_summary_*.txt"
    echo
else
    echo
    echo "❌ Sync encountered some issues. Check the output above."
    echo
fi

echo "📋 Quick status check..."
echo

# Show current todos from WhatsApp
echo "📱 WhatsApp Todos:"
python3 -c "from whatsapp_todo_integration import WhatsAppTodoManager; m=WhatsAppTodoManager(); todos=m.list_todos('919742814697'); print(f'   Pending: {len(todos)}') if todos else print('   No pending todos')" 2>/dev/null || echo "   Status check failed"

# Show current todos from Sheets
echo "📊 Google Sheets Todos:"
python3 -c "from google_sheets_integration import GoogleSheetsTodoManager; m=GoogleSheetsTodoManager(); todos=m.list_todos('pending'); print(f'   Pending: {len(todos)}') if todos else print('   No pending todos')" 2>/dev/null || echo "   Status check failed"

echo
echo "🎉 Sync process complete!"
echo