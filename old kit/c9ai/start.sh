#!/bin/bash

# C9AI Workshop Registration System Startup Script

echo "🎯 C9AI Workshop Registration System"
echo "====================================="
echo

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP to continue."
    exit 1
fi

# Check if database exists, create if not
if [ ! -f "workshop_registrations.db" ]; then
    echo "🔧 Setting up database..."
    php setup_db.php
    echo
fi

# Start the PHP development server
echo "🚀 Starting development server..."
echo "📱 Server will be available at: http://localhost:8000"
echo "🌐 Workshop presentation: http://localhost:8000"
echo "🛠️  Registration manager: http://localhost:8000/view_registrations.php"
echo
echo "⏹  Press Ctrl+C to stop the server"
echo "-----------------------------------"

# Start the server
php -S localhost:8000 server.php