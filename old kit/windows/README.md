# Todo Management System - Windows Support

🪟 **Windows batch scripts for todo management automation**

## Quick Start

1. **📋 [Read the complete installation guide](WINDOWS_INSTALLATION_GUIDE.md)**
2. **⚡ Run setup:** Clone repo, install prerequisites, configure `.env`
3. **🚀 Test:** Double-click `check-todos.bat`

## Available Scripts

| Script | Description | Requirements |
|--------|-------------|--------------|
| **check-todos.bat** | Check open GitHub issues (todos) | GitHub CLI |
| **cleanup-weekly.bat** | Weekly cleanup and archiving | File system access |
| **run-analytics.bat** | Generate analytics dashboard | Python, GitHub CLI |

## Quick Setup

### 1. Install Prerequisites
```cmd
# Install using Windows Package Manager (winget)
winget install Git.Git
winget install Python.Python.3
winget install GitHub.cli
```

### 2. Clone and Setup
```cmd
git clone https://github.com/hebbarp/todo-management.git
cd todo-management
copy .env.example .env
```

### 3. Configure Environment
Edit `.env` file with your API keys:
```env
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
NEWS_API_KEY=your_newsapi_key
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password
```

### 4. Authenticate GitHub
```cmd
gh auth login
```

### 5. Test Installation
```cmd
cd windows
check-todos.bat
```

## Usage Examples

### Check Your Todos
```cmd
check-todos.bat
```
**Output:**
```
🔍 CHECKING OPEN TODOS
======================
📋 Open todos:
#22    OPEN    Create deployment guide    
#23    OPEN    Update documentation      

📊 Summary:
Total open todos: 2
```

### Weekly Cleanup
```cmd
cleanup-weekly.bat
```
**Features:**
- Archives old log files and text files
- Cleans Python cache (`__pycache__`, `*.pyc`)
- Removes temporary files (`*.tmp`, `*.temp`)
- Compresses old archives (30+ days)
- Creates cleanup log

### Generate Analytics
```cmd
run-analytics.bat
```
**Output:**
- HTML analytics report
- Charts and graphs
- Opens automatically in browser

## Python Scripts Integration

Run Python scripts from Windows:

```cmd
# Test credentials
python python\test_twilio_creds.py

# Send WhatsApp message  
python python\send_whatsapp_message.py 919742814697 "Hello!"
```

## File Structure

```
windows/
├── README.md                     # This file
├── WINDOWS_INSTALLATION_GUIDE.md # Complete setup guide
├── check-todos.bat              # GitHub issues checker
├── cleanup-weekly.bat           # Weekly cleanup script
└── run-analytics.bat            # Analytics dashboard
```

## Requirements

### Required Software
- **Windows 10/11** (Command Prompt or PowerShell)
- **Git for Windows** - Version control
- **Python 3.9+** - Script execution (with PATH configured)
- **GitHub CLI** - GitHub integration

### Optional Software  
- **Node.js** - For future JavaScript tools
- **PowerShell 7** - Enhanced scripting capabilities

### API Keys (in `.env` file)
- **Twilio** - SMS functionality
- **News API** - News fetching
- **Gmail App Password** - Email sending

## Troubleshooting

### Common Issues

**❌ "Python is not recognized"**
```cmd
# Check if Python is in PATH
python --version

# If not found, reinstall Python with "Add to PATH" checked
# Or manually add to PATH environment variable
```

**❌ "gh is not recognized"**
```cmd
# Install GitHub CLI
winget install GitHub.cli

# Restart Command Prompt
# Verify installation
gh --version
```

**❌ Scripts don't run**
- Right-click Command Prompt → "Run as Administrator"
- Check Windows Defender exclusions
- Verify file permissions

**❌ Module not found (Python)**
```cmd
# Install required packages
pip install requests twilio matplotlib pandas

# Or create virtual environment
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Getting Help

1. **📖 Read** [WINDOWS_INSTALLATION_GUIDE.md](WINDOWS_INSTALLATION_GUIDE.md)
2. **🔍 Check** error messages and prerequisites
3. **🐛 Report** issues: https://github.com/hebbarp/todo-management/issues

## Windows-Specific Features

- **Batch file integration** with Windows Command Prompt
- **Windows path handling** (backslashes)
- **PowerShell commands** for advanced operations
- **Windows package manager** (winget) support
- **Windows Task Scheduler** integration ready

## Security Notes

- Scripts require appropriate permissions
- API keys stored in `.env` file (excluded from git)
- Windows Defender may scan batch files
- Administrator privileges needed for some cleanup operations

---

**🚀 Ready to get started?** Follow the [installation guide](WINDOWS_INSTALLATION_GUIDE.md) for step-by-step setup instructions!