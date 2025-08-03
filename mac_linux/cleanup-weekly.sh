#!/bin/bash
# Weekly cleanup script

echo "🧹 WEEKLY CLEANUP"
echo "=================="

# Create archive directory if it doesn't exist
mkdir -p archive/$(date +%Y-%m)

# Archive old log files
if [ -f *.log ]; then
    echo "📦 Archiving log files..."
    mv *.log archive/$(date +%Y-%m)/ 2>/dev/null || true
fi

# Archive old txt files (but keep important ones)
echo "📦 Archiving old text files..."
find . -maxdepth 1 -name "*.txt" -not -name "README.txt" -exec mv {} archive/$(date +%Y-%m)/ \; 2>/dev/null || true

# Clean up Python cache
echo "🧹 Cleaning Python cache..."
find . -type d -name "__pycache__" -exec rm -rf {} + 2>/dev/null || true
find . -name "*.pyc" -delete 2>/dev/null || true

# Clean up temporary files
echo "🧹 Cleaning temporary files..."
find . -name "*.tmp" -delete 2>/dev/null || true
find . -name "*.temp" -delete 2>/dev/null || true
find . -name "*~" -delete 2>/dev/null || true

# Compress old archives (older than 30 days)
echo "🗜️  Compressing old archives..."
find archive/ -type f -mtime +30 -name "*.txt" -exec gzip {} \; 2>/dev/null || true

# Summary
echo ""
echo "✅ Weekly cleanup completed!"
echo "📅 $(date)"

# Log cleanup
echo "$(date): Weekly cleanup completed" >> archive/cleanup.log