#!/usr/bin/env python3
"""
Create downloadable packages for different platforms
"""

import os
import shutil
import zipfile
from pathlib import Path

def create_package(platform, source_scripts_dir, package_name):
    """Create a platform-specific package"""
    
    # Create temporary package directory
    package_dir = Path(f"temp_{platform}_package")
    if package_dir.exists():
        shutil.rmtree(package_dir)
    
    package_dir.mkdir()
    
    # Copy core files
    core_files = [
        ('.env.example', '.env.example'),
        ('requirements.txt', 'requirements.txt'),
        ('README.md', 'README.md'),
    ]
    
    for src, dest in core_files:
        if Path(src).exists():
            shutil.copy2(src, package_dir / dest)
    
    # Copy platform-specific scripts
    scripts_dest = package_dir / "scripts"
    if Path(source_scripts_dir).exists():
        shutil.copytree(source_scripts_dir, scripts_dest)
    
    # Copy Python scripts
    python_dest = package_dir / "python"
    if Path("python").exists():
        shutil.copytree("python", python_dest)
    
    # Copy templates
    templates_dest = package_dir / "templates"  
    if Path("templates").exists():
        shutil.copytree("templates", templates_dest)
    
    # Create setup script
    create_setup_script(package_dir, platform)
    
    # Create platform-specific documentation
    create_platform_docs(package_dir, platform)
    
    # Create zip package
    zip_path = Path("website/packages") / f"{package_name}.zip"
    zip_path.parent.mkdir(parents=True, exist_ok=True)
    
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for file_path in package_dir.rglob('*'):
            if file_path.is_file():
                arcname = file_path.relative_to(package_dir)
                zipf.write(file_path, arcname)
    
    # Cleanup
    shutil.rmtree(package_dir)
    
    print(f"✅ Created {package_name}.zip ({zip_path.stat().st_size / 1024:.1f} KB)")

def create_setup_script(package_dir, platform):
    """Create platform-specific setup script"""
    
    if platform == "windows":
        setup_content = '''@echo off
echo 🚀 TODO MANAGEMENT SYSTEM SETUP
echo ================================

echo 📁 Setting up directory structure...
if not exist "logs" mkdir logs
if not exist "archive" mkdir archive

echo 📋 Checking prerequisites...

REM Check Python
python --version >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Python not found. Please install Python 3.9+ from python.org
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

REM Check pip
pip --version >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ pip not found. Please reinstall Python with pip included
    pause
    exit /b 1
)

echo ✅ Python found

REM Install requirements
if exist requirements.txt (
    echo 📦 Installing Python requirements...
    pip install -r requirements.txt
    if %errorlevel% neq 0 (
        echo ⚠️ Some packages failed to install. Check the error messages above.
    ) else (
        echo ✅ Python packages installed
    )
)

REM Copy environment template
if exist .env.example (
    if not exist .env (
        copy .env.example .env
        echo 📝 Created .env file from template
        echo ⚠️ IMPORTANT: Edit .env file with your API keys before using the system
    )
)

echo.
echo 🎉 Setup completed!
echo.
echo Next steps:
echo 1. Edit .env file with your API keys
echo 2. Run: cd scripts
echo 3. Run: check-todos.bat
echo.
pause
'''
        
        setup_file = package_dir / "setup.bat"
        
    else:  # macOS/Linux
        setup_content = '''#!/bin/bash
echo "🚀 TODO MANAGEMENT SYSTEM SETUP"
echo "================================"

echo "📁 Setting up directory structure..."
mkdir -p logs archive

echo "📋 Checking prerequisites..."

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 not found. Please install Python 3.9+"
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "Install with: brew install python3"
    else
        echo "Install with your package manager (apt, yum, etc.)"
    fi
    exit 1
fi

# Check pip
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 not found. Please install pip"
    exit 1
fi

echo "✅ Python found"

# Install requirements
if [ -f requirements.txt ]; then
    echo "📦 Installing Python requirements..."
    pip3 install -r requirements.txt
    if [ $? -eq 0 ]; then
        echo "✅ Python packages installed"
    else
        echo "⚠️ Some packages failed to install. Check the error messages above."
    fi
fi

# Copy environment template  
if [ -f .env.example ] && [ ! -f .env ]; then
    cp .env.example .env
    echo "📝 Created .env file from template"
    echo "⚠️ IMPORTANT: Edit .env file with your API keys before using the system"
fi

# Make scripts executable
if [ -d scripts ]; then
    chmod +x scripts/*.sh
    echo "✅ Made scripts executable"
fi

echo ""
echo "🎉 Setup completed!"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your API keys"
echo "2. cd scripts"
echo "3. ./check-todos.sh"
echo ""
'''
        
        setup_file = package_dir / "setup.sh"
    
    setup_file.write_text(setup_content)
    if platform != "windows":
        setup_file.chmod(0o755)

def create_platform_docs(package_dir, platform):
    """Create platform-specific documentation"""
    
    docs_dir = package_dir / "docs"
    docs_dir.mkdir()
    
    # Quick start guide
    quick_start = f"""# Quick Start Guide - {platform.title()}

## Welcome to Todo Management System!

This package contains everything you need to start automating your tasks with AI.

## What's Included

- **Scripts**: Automation scripts for your platform
- **Python Tools**: Messaging, analytics, and integration tools  
- **Templates**: Email templates, web pages, and examples
- **Documentation**: Setup guides and troubleshooting

## 5-Minute Setup

### Step 1: Run Setup
{"Double-click `setup.bat`" if platform == "windows" else "Run `./setup.sh` in Terminal"}

### Step 2: Configure API Keys
Edit `.env` file with your credentials:
```
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
NEWS_API_KEY=your_news_api_key
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password
```

### Step 3: Test Installation
{"Run `scripts\\check-todos.bat`" if platform == "windows" else "Run `scripts/check-todos.sh`"}

## Getting API Keys

### Twilio (SMS/WhatsApp)
1. Sign up at https://twilio.com
2. Get Account SID and Auth Token from Console

### News API (News fetching)  
1. Sign up at https://newsapi.org
2. Get free API key from dashboard

### Gmail (Email automation)
1. Enable 2-factor authentication
2. Generate App Password in Google Account settings

## Usage Examples

### Check Your Todos
{"```cmd\\nscripts\\check-todos.bat\\n```" if platform == "windows" else "```bash\\nscripts/check-todos.sh\\n```"}

### Send WhatsApp Message
{"```cmd\\npython python\\send_whatsapp_message.py 919742814697 \"Hello!\"\\n```" if platform == "windows" else "```bash\\npython3 python/send_whatsapp_message.py 919742814697 \"Hello!\"\\n```"}

### Weekly Cleanup
{"```cmd\\nscripts\\cleanup-weekly.bat\\n```" if platform == "windows" else "```bash\\nscripts/cleanup-weekly.sh\\n```"}

## Need Help?

- Check `README.md` for detailed documentation
- Visit: https://github.com/hebbarp/todo-management
- Report issues: https://github.com/hebbarp/todo-management/issues

## Next Steps

1. ✅ Complete setup
2. ✅ Test basic functionality  
3. 🚀 Start automating your tasks!
4. 📱 Set up WhatsApp/Email integration
5. 📊 Explore analytics and reporting

Happy automating! 🎉
"""
    
    (docs_dir / "QUICK_START.md").write_text(quick_start)
    
    # Troubleshooting guide
    troubleshooting = f"""# Troubleshooting Guide - {platform.title()}

## Common Issues

### "Python not found" Error
**Problem:** System can't find Python
**Solution:** 
{"- Reinstall Python from python.org\\n- Check 'Add Python to PATH' during installation\\n- Restart Command Prompt after installation" if platform == "windows" else "- Install Python 3.9+: `brew install python3` (macOS) or use your package manager\\n- Ensure python3 is in PATH\\n- Try `python3 --version`"}

### "pip not found" Error  
**Problem:** pip package manager missing
**Solution:**
{"- Reinstall Python (pip is included)\\n- Or download get-pip.py and run: python get-pip.py" if platform == "windows" else "- Install pip: `python3 -m ensurepip --upgrade`\\n- Or use package manager to install python3-pip"}

### "Module not found" Error
**Problem:** Required Python packages not installed
**Solution:**
{"```cmd\\npip install -r requirements.txt\\n```" if platform == "windows" else "```bash\\npip3 install -r requirements.txt\\n```"}

### Scripts Don't Run
**Problem:** Permission or execution issues
**Solution:**
{"- Right-click Command Prompt → 'Run as Administrator'\\n- Check Windows Defender is not blocking scripts\\n- Ensure .bat files are not corrupted" if platform == "windows" else "- Make scripts executable: `chmod +x scripts/*.sh`\\n- Check file permissions\\n- Try running with: `bash script_name.sh`"}

### API Connection Errors
**Problem:** Can't connect to Twilio, Gmail, etc.
**Solution:**
- Check your `.env` file has correct API keys
- Verify API keys are valid and active
- Check internet connection
- Test credentials with test scripts

### GitHub CLI Issues
**Problem:** gh commands don't work
**Solution:**
{"- Install GitHub CLI from https://cli.github.com/\\n- Restart Command Prompt\\n- Run: gh auth login" if platform == "windows" else "- Install GitHub CLI: `brew install gh` (macOS) or package manager\\n- Run: gh auth login\\n- Check: gh --version"}

## Getting Help

### Check System Status
{"```cmd\\npython python\\test_twilio_creds.py\\n```" if platform == "windows" else "```bash\\npython3 python/test_twilio_creds.py\\n```"}

### Verify Installation
{"```cmd\\nscripts\\check-todos.bat\\n```" if platform == "windows" else "```bash\\nscripts/check-todos.sh\\n```"}

### Reset Configuration
1. Delete `.env` file
2. {"Run `setup.bat` again" if platform == "windows" else "Run `./setup.sh` again"}
3. Reconfigure your API keys

## Still Need Help?

1. Check error messages carefully
2. Search GitHub issues: https://github.com/hebbarp/todo-management/issues  
3. Create new issue with:
   - Your operating system
   - Error message (full text)
   - Steps you tried
   - Screenshots if helpful

We're here to help! 🚀
"""
    
    (docs_dir / "TROUBLESHOOTING.md").write_text(troubleshooting)

def main():
    """Create all platform packages"""
    
    print("📦 Creating downloadable packages...")
    
    # Create packages directory
    Path("website/packages").mkdir(parents=True, exist_ok=True)
    
    # Create Windows package
    create_package("windows", "windows", "todo-management-windows")
    
    # Create macOS package  
    create_package("macos", "mac_linux", "todo-management-macos")
    
    # Create Linux package
    create_package("linux", "mac_linux", "todo-management-linux")
    
    print("🎉 All packages created successfully!")
    print("📁 Find them in: website/packages/")

if __name__ == "__main__":
    main()