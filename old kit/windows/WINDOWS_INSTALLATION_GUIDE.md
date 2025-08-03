# Windows Installation Guide

Complete setup guide for the Todo Management System on Windows.

## Prerequisites

### 1. Install Git for Windows

**Option A: Download from Website**
1. Go to https://git-scm.com/download/win
2. Download and run the installer
3. During installation, select "Git from the command line and also from 3rd-party software"
4. Choose "Use the OpenSSL library"
5. Select "Checkout Windows-style, commit Unix-style line endings"

**Option B: Using Package Manager**
```cmd
winget install Git.Git
```

### 2. Install Python

**Option A: Download from Website**
1. Go to https://python.org/downloads/
2. Download Python 3.9+ (latest recommended)
3. **IMPORTANT**: Check "Add Python to PATH" during installation
4. Choose "Install for all users"

**Option B: Using Package Manager**
```cmd
winget install Python.Python.3
```

**Verify Installation:**
```cmd
python --version
pip --version
```

### 3. Install GitHub CLI

**Option A: Download from Website**
1. Go to https://cli.github.com/
2. Download the Windows installer
3. Run the installer

**Option B: Using Package Manager**
```cmd
winget install GitHub.cli
```

**Verify Installation:**
```cmd
gh --version
```

### 4. Install Node.js (Optional, for future JavaScript tools)

**Option A: Download from Website**
1. Go to https://nodejs.org/
2. Download the LTS version
3. Run the installer

**Option B: Using Package Manager**
```cmd
winget install OpenJS.NodeJS
```

## Project Setup

### 1. Clone the Repository

```cmd
git clone https://github.com/hebbarp/todo-management.git
cd todo-management
```

### 2. Create Environment File

1. Copy the example environment file:
```cmd
copy .env.example .env
```

2. Edit `.env` file with your actual credentials:
```
# Twilio Configuration
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here

# News API Configuration  
NEWS_API_KEY=your_news_api_key_here

# Gmail Configuration
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_16_char_app_password
```

### 3. Install Python Dependencies

```cmd
pip install -r requirements.txt
```

If `requirements.txt` doesn't exist, install common packages:
```cmd
pip install requests twilio matplotlib pandas
```

### 4. Authenticate with GitHub

```cmd
gh auth login
```

Follow the prompts to authenticate with your GitHub account.

### 5. Test the Installation

Run the todo checker:
```cmd
cd windows
check-todos.bat
```

## Configuration

### Environment Variables

Create or edit `.env` file in the project root with your API keys and credentials:

```env
# Required for Twilio SMS functionality
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_32_character_auth_token

# Required for news fetching
NEWS_API_KEY=your_newsapi_org_key

# Required for email functionality
GMAIL_USER=your_gmail@gmail.com
GMAIL_APP_PASSWORD=your_16_char_app_password
```

### Getting API Keys

**Twilio (for SMS):**
1. Sign up at https://twilio.com
2. Go to Console Dashboard
3. Copy Account SID and Auth Token

**News API (for news fetching):**
1. Sign up at https://newsapi.org
2. Get your free API key from the dashboard

**Gmail App Password (for email):**
1. Enable 2-factor authentication on your Google account
2. Go to Google Account settings
3. Generate an App Password for "Mail"

## Usage

### Running Scripts

Double-click any `.bat` file or run from Command Prompt:

```cmd
# Check open todos
check-todos.bat

# Run weekly cleanup
cleanup-weekly.bat

# Generate analytics report
run-analytics.bat
```

### Python Scripts

```cmd
# Test Twilio credentials
python python\test_twilio_creds.py

# Send WhatsApp message
python python\send_whatsapp_message.py 919742814697 "Hello from Windows!"
```

## Troubleshooting

### Common Issues

**"Python is not recognized"**
- Reinstall Python and check "Add Python to PATH"
- Or manually add Python to PATH environment variable

**"gh is not recognized"**
- Install GitHub CLI
- Restart Command Prompt after installation

**Scripts don't run**
- Right-click Command Prompt and "Run as Administrator"
- Check if Windows Defender is blocking the scripts

**Module not found errors**
- Install missing Python packages: `pip install package_name`
- Use virtual environment if needed

### Getting Help

1. Check the error messages carefully
2. Ensure all prerequisites are installed
3. Verify your `.env` file is configured correctly
4. Make sure you're authenticated with GitHub CLI

## Windows-Specific Notes

- Use backslashes (`\`) for file paths in batch scripts
- Some features may require PowerShell for advanced operations
- Windows Defender may flag batch scripts - add exclusions if needed
- Use Command Prompt or PowerShell as Administrator for full functionality

## Next Steps

After successful installation:

1. Test all batch scripts
2. Configure your API keys in `.env`
3. Run `check-todos.bat` to see your GitHub issues
4. Set up scheduled tasks for automated cleanup
5. Explore the Python scripts for automation

---

**Need help?** Open an issue on GitHub: https://github.com/hebbarp/todo-management/issues