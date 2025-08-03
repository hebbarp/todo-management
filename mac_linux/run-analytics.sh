#!/bin/bash
# Run analytics dashboard

echo "📊 RUNNING ANALYTICS DASHBOARD"
echo "==============================="

# Check if Python script exists
if [ ! -f "../python/analytics_dashboard.py" ]; then
    echo "❌ Analytics dashboard script not found"
    echo "Expected location: python/analytics_dashboard.py"
    exit 1
fi

# Create output directory
mkdir -p ../analytics_output

# Run analytics
echo "🔄 Generating analytics report..."
cd ../python
python3 analytics_dashboard.py

if [ $? -eq 0 ]; then
    echo "✅ Analytics report generated successfully!"
    echo "📂 Output location: analytics_output/"
    
    # Open the report if on macOS
    if [[ "$OSTYPE" == "darwin"* ]]; then
        if [ -f "../analytics_output/analytics_report.html" ]; then
            echo "🌐 Opening report in browser..."
            open ../analytics_output/analytics_report.html
        fi
    fi
else
    echo "❌ Analytics generation failed"
    exit 1
fi