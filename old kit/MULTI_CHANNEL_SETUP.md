# Multi-Channel Todo Management System Setup Guide

## Overview

This system allows you to manage todos across multiple channels:
- 📱 **WhatsApp**: Send todos via WhatsApp messages
- 📧 **Email**: Process emails to extract todos automatically
- 📊 **Google Sheets**: Manage todos in spreadsheet format
- 🐙 **GitHub Issues**: Technical todos via GitHub integration

## Quick Setup (5 Minutes)

### Step 1: Download and Extract
1. Download your platform-specific package from the website
2. Extract to a folder (e.g., `Desktop/todo-management`)

### Step 2: Install Dependencies
**Windows:**
```cmd
setup.bat
```

**macOS/Linux:**
```bash
./setup.sh
```

### Step 3: Configure API Keys
Edit `.env` file with your credentials:

```env
# Twilio (WhatsApp/SMS)
TWILIO_ACCOUNT_SID=your_twilio_sid_here
TWILIO_AUTH_TOKEN=your_twilio_token_here
TWILIO_PHONE_NUMBER=+1234567890

# News API (optional)
NEWS_API_KEY=your_news_api_key_here

# Gmail (Email processing)
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password_here
```

### Step 4: Test Installation
**Windows:**
```cmd
windows\check-todos.bat
```

**macOS/Linux:**
```bash
mac_linux/check-todos.sh
```

## Getting API Keys

### Twilio (WhatsApp & SMS)
1. Sign up at [https://twilio.com](https://twilio.com)
2. Get Account SID and Auth Token from Console Dashboard
3. Purchase a phone number or use the free trial number
4. For WhatsApp: Enable WhatsApp Sandbox in Console

### Gmail (Email Processing)
1. Enable 2-factor authentication on your Google account
2. Go to Google Account Settings → Security
3. Generate an "App Password" for this application
4. Use this App Password (not your regular password)

### News API (Optional)
1. Sign up at [https://newsapi.org](https://newsapi.org)
2. Get your free API key from the dashboard
3. Free tier allows 1,000 requests per day

## Channel Setup

### 📱 WhatsApp Integration

#### Basic Setup
1. Set up Twilio WhatsApp Sandbox:
   - Go to Twilio Console → WhatsApp → Sandbox
   - Send "join <sandbox-keyword>" to the Twilio number
   - Note your sandbox number

2. Test WhatsApp integration:
```bash
python3 python/whatsapp_todo_integration.py test
```

#### Webhook Setup (Advanced)
For real-time WhatsApp processing:

1. Install ngrok: `npm install -g ngrok`
2. Start webhook server:
```bash
python3 python/whatsapp_webhook_server.py
```
3. In another terminal: `ngrok http 8000`
4. Configure webhook URL in Twilio Console

#### WhatsApp Commands
- **Add todo**: "Add todo: Call investor meeting"
- **Complete todo**: "Complete 5" or "Done 3"
- **List todos**: "List todos" or "Show my todos"
- **Help**: "Help"

### 📧 Email Integration

#### Setup Gmail Processing
1. Configure Gmail credentials in `.env`
2. Test email integration:
```bash
python3 python/email_todo_integration.py test
```

#### Email Todo Formats
The system automatically extracts todos from:
- Numbered lists: "1. Review budget"
- Bullet points: "• Call client"
- Action phrases: "Please remember to..."
- Todo keywords: "Action item: Update website"

#### Processing Emails
Run email processing manually:
```bash
python3 python/email_todo_integration.py
```

Or set up automatic processing (see Automation section).

### 📊 Google Sheets Integration

#### CSV-Based (Default)
The system uses CSV files that simulate Google Sheets:
```bash
python3 python/google_sheets_integration.py
```

#### Real Google Sheets API (Advanced)
To use actual Google Sheets:

1. Create Google Cloud Project
2. Enable Google Sheets API
3. Download credentials.json
4. Install additional packages:
```bash
pip install google-api-python-client google-auth google-auth-oauthlib
```

### 🐙 GitHub Issues Integration

#### Setup GitHub CLI
**Windows:**
```cmd
winget install GitHub.cli
```

**macOS:**
```bash
brew install gh
```

**Linux:**
```bash
# Follow instructions at https://cli.github.com/
```

#### Authenticate
```bash
gh auth login
```

#### Test Integration
```bash
gh issue list --repo hebbarp/todo-management
```

## Multi-Channel Synchronization

### Automatic Sync
Run the complete multi-channel sync:

**Windows:**
```cmd
windows\multi-channel-sync.bat
```

**macOS/Linux:**
```bash
mac_linux/multi-channel-sync.sh
```

### What Sync Does
1. ✅ Processes new WhatsApp messages
2. ✅ Scans emails for new todos
3. ✅ Syncs todos to Google Sheets
4. ✅ Creates GitHub issues for priority todos
5. ✅ Generates unified reports
6. ✅ Creates emergency backups
7. ✅ Sends digest emails

### Sync Outputs
- `unified_todo_report_*.json`: Complete sync report
- `emergency_backup_*.json`: Full system backup
- `unified_summary_*.txt`: Human-readable summary

## Automation

### Daily Sync (Recommended)
Set up daily automatic synchronization:

**Windows Task Scheduler:**
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger: Daily at preferred time
4. Action: `C:\path\to\windows\multi-channel-sync.bat`

**macOS/Linux Cron:**
```bash
# Edit crontab
crontab -e

# Add daily sync at 9 AM
0 9 * * * /path/to/mac_linux/multi-channel-sync.sh
```

### Continuous Monitoring (Advanced)
For real-time processing:

1. **WhatsApp Webhook Server:**
```bash
python3 python/whatsapp_webhook_server.py
```

2. **Email Monitoring:**
```bash
# Set up email checking every 15 minutes
*/15 * * * * python3 /path/to/python/email_todo_integration.py
```

## Usage Examples

### Scenario 1: Executive Todo Management
1. **Morning**: Receive WhatsApp message: "Add todo: Board meeting prep"
2. **Afternoon**: Email from assistant with action items
3. **Evening**: Run sync to update Google Sheets
4. **Next day**: Check unified report for priorities

### Scenario 2: Development Team
1. **Sprint Planning**: Create GitHub issues from email requirements
2. **Daily Standups**: WhatsApp updates on task completion
3. **Weekly Review**: Google Sheets for progress tracking
4. **Release Planning**: Unified reports for stakeholders

### Scenario 3: Remote Work
1. **Mobile**: Add todos via WhatsApp when away from computer
2. **Email**: Process meeting notes automatically
3. **Sync**: Everything appears in your preferred tool
4. **Backup**: Never lose important tasks

## Troubleshooting

### Common Issues

#### "Python not found"
- **Windows**: Reinstall Python, check "Add to PATH"
- **macOS**: `brew install python3`
- **Linux**: Use package manager to install python3

#### "Authentication Failed" (Email)
- Use App Password, not regular password
- Enable 2-factor authentication first
- Double-check email/password in `.env`

#### "Twilio Error"
- Verify Account SID and Auth Token
- Check phone number format (+1234567890)
- Ensure sufficient Twilio credit

#### "GitHub CLI not found"
- Install GitHub CLI from official website
- Run `gh auth login` after installation
- Test with `gh --version`

### Getting Help

1. **Check logs**: Look for error messages in terminal output
2. **Test components**: Use individual test commands
3. **Verify credentials**: Double-check all API keys in `.env`
4. **GitHub Issues**: Report problems at the repository

## Security Best Practices

### API Key Security
- ✅ Use `.env` file for credentials
- ✅ Never commit `.env` to version control
- ✅ Use App Passwords for Gmail
- ✅ Regularly rotate API keys

### Email Security
- ✅ Use App Passwords instead of main password
- ✅ Enable 2-factor authentication
- ✅ Monitor login activity

### WhatsApp Security
- ✅ Use Twilio Sandbox for testing
- ✅ Verify webhook tokens
- ✅ Monitor message logs

## Advanced Features

### Custom Integrations
The system is designed to be extensible. You can add:
- Slack integration
- Microsoft Teams integration
- Notion integration
- Custom webhook endpoints

### Analytics and Reporting
- Daily/weekly todo completion rates
- Channel usage statistics
- Priority distribution analysis
- Time-based productivity metrics

### Enterprise Features
- Multiple user support
- Department-based routing
- Approval workflows
- Advanced analytics dashboard

## Support

### Quick Support
- 📧 Email: Check repository issues
- 🐙 GitHub: Create issue with detailed description
- 📱 WhatsApp: Test with provided examples

### Professional Support
For enterprise deployment or custom integrations, contact the development team through the GitHub repository.

---

## Next Steps

1. ✅ Complete basic setup
2. ✅ Test each channel individually
3. ✅ Run first multi-channel sync
4. 🚀 Set up daily automation
5. 📊 Monitor and optimize your workflow

**Happy automating!** 🎉