# API Keys Setup Guide

This guide helps you obtain and configure all the API keys needed for the Todo Management System.

## Required API Keys

### 🔧 Essential (Core Functionality)
- **Twilio**: WhatsApp and SMS integration
- **Gmail**: Email processing and notifications

### 📈 Optional (Enhanced Features)
- **News API**: News integration for todos
- **GitHub**: Issue management (uses GitHub CLI)

---

## Twilio Setup (WhatsApp & SMS)

### Step 1: Create Twilio Account
1. Go to [https://twilio.com](https://twilio.com)
2. Click "Sign up" and create a free account
3. Verify your email and phone number

### Step 2: Get Account Credentials
1. Go to Twilio Console Dashboard
2. Find "Account Info" section
3. Copy your **Account SID** and **Auth Token**

### Step 3: Get Phone Number
**Free Trial:**
- Use the provided trial number
- Can only send to verified numbers

**Paid Account:**
1. Go to Phone Numbers → Manage → Buy a number
2. Choose a number with SMS and Voice capabilities
3. Note the number in +1234567890 format

### Step 4: Enable WhatsApp (Optional)
1. Go to Messaging → WhatsApp → Sandbox
2. Follow the setup instructions
3. Send "join <your-sandbox-keyword>" to the Twilio WhatsApp number
4. Save the WhatsApp-enabled number

### Step 5: Configure .env
```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
```

### Testing Twilio
```bash
python3 python/test_twilio_creds.py
```

---

## Gmail Setup (Email Processing)

### Step 1: Enable 2-Factor Authentication
1. Go to [Google Account Settings](https://myaccount.google.com/)
2. Security → 2-Step Verification
3. Follow the setup process

### Step 2: Generate App Password
1. Go to Security → 2-Step Verification
2. Scroll down to "App passwords"
3. Click "Generate app password"
4. Select "Mail" and your device
5. Copy the 16-character password

### Step 3: Configure .env
```env
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=abcd efgh ijkl mnop
```

### Important Notes
- ✅ Use the App Password, NOT your regular password
- ✅ Keep spaces in the app password
- ✅ Enable "Less secure app access" if prompted
- ✅ Use your full Gmail address

### Testing Gmail
```bash
python3 python/email_todo_integration.py test
```

---

## News API Setup (Optional)

### Step 1: Create Account
1. Go to [https://newsapi.org](https://newsapi.org)
2. Click "Get API Key"
3. Sign up with email

### Step 2: Get API Key
1. Verify your email
2. Go to your dashboard
3. Copy your API key

### Step 3: Configure .env
```env
NEWS_API_KEY=your_news_api_key_here
```

### API Limits
- **Free**: 1,000 requests/day
- **Developer**: 500 requests/day, limited to localhost
- **Business**: Paid plans for production use

### Testing News API
```bash
python3 python/yahoo_finance_news.py
```

---

## GitHub CLI Setup (Optional)

### Step 1: Install GitHub CLI

**Windows:**
```cmd
winget install GitHub.cli
```

**macOS:**
```bash
brew install gh
```

**Linux (Ubuntu/Debian):**
```bash
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
sudo apt update
sudo apt install gh
```

### Step 2: Authenticate
```bash
gh auth login
```

Follow the prompts:
1. Choose "GitHub.com"
2. Choose "HTTPS"
3. Authenticate via web browser
4. Choose your preferred text editor

### Step 3: Test Installation
```bash
gh --version
gh repo view hebbarp/todo-management
```

### No Additional .env Configuration Needed
GitHub CLI handles authentication automatically after login.

---

## Complete .env File Template

Create a `.env` file in your project root with these values:

```env
# ==============================================
# TODO MANAGEMENT SYSTEM - API CONFIGURATION
# ==============================================

# Twilio (Required for WhatsApp/SMS)
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_twilio_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890

# Gmail (Required for Email Processing)
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=abcd efgh ijkl mnop

# News API (Optional - for news integration)
NEWS_API_KEY=your_news_api_key_here

# ==============================================
# NOTES:
# - Never commit this file to version control
# - Use actual values, not placeholder text
# - Keep app password spaces for Gmail
# - Twilio phone number needs + prefix
# ==============================================
```

---

## Security Checklist

### ✅ Before Using
- [ ] All API keys are real values (not placeholders)
- [ ] `.env` file is in project root directory
- [ ] `.env` file is added to `.gitignore`
- [ ] Gmail 2-factor authentication is enabled
- [ ] Twilio account is verified

### ✅ Best Practices
- [ ] Use App Passwords for Gmail (never main password)
- [ ] Regularly rotate API keys (quarterly)
- [ ] Monitor API usage and billing
- [ ] Keep credentials secure and private
- [ ] Use different credentials for production vs testing

### ✅ Testing
- [ ] Twilio test passes: `python3 python/test_twilio_creds.py`
- [ ] Email test passes: `python3 python/email_todo_integration.py test`
- [ ] WhatsApp test passes: `python3 python/whatsapp_todo_integration.py test`
- [ ] GitHub CLI works: `gh --version`

---

## Troubleshooting API Issues

### Twilio Errors

**"Authentication Error"**
- Double-check Account SID and Auth Token
- Ensure no extra spaces in credentials
- Verify account is active (not suspended)

**"Invalid Phone Number"**
- Use international format: +1234567890
- Include country code
- For WhatsApp, use WhatsApp-enabled number

**"Insufficient Funds"**
- Add credit to Twilio account
- Check current balance in Console

### Gmail Errors

**"Authentication Failed"**
- Use App Password, not regular password
- Ensure 2-factor authentication is enabled
- Check if "Less secure apps" needs to be enabled

**"Connection Error"**
- Check internet connection
- Verify Gmail IMAP is enabled
- Try different SMTP settings if needed

### News API Errors

**"Rate Limit Exceeded"**
- You've hit the daily limit (1,000 for free)
- Wait until tomorrow or upgrade plan
- Reduce frequency of news requests

**"Invalid API Key"**
- Double-check API key from dashboard
- Ensure no extra spaces or characters
- Regenerate key if needed

### GitHub CLI Errors

**"Not authenticated"**
- Run `gh auth login`
- Follow authentication process
- Check with `gh auth status`

**"Repository not found"**
- Verify repository exists and is accessible
- Check your GitHub permissions
- Use full repository path: `owner/repo`

---

## Getting Help

### Quick Diagnostics
Run the system's built-in diagnostics:
```bash
python3 python/test_twilio_creds.py
python3 python/email_todo_integration.py test
gh auth status
```

### Common Solutions
1. **Restart your terminal** after installing new tools
2. **Check .env file location** (must be in project root)
3. **Verify internet connection** for API calls
4. **Update credentials** if they've been changed

### Support Resources
- 📧 **Twilio**: [Twilio Support Center](https://support.twilio.com/)
- 📧 **Gmail**: [Gmail Help Center](https://support.google.com/gmail/)
- 📧 **News API**: [News API Documentation](https://newsapi.org/docs)
- 📧 **GitHub**: [GitHub CLI Documentation](https://cli.github.com/manual/)

### Getting Professional Help
If you need assistance with setup:
1. Create an issue in the GitHub repository
2. Include error messages and system information
3. Describe what you've already tried
4. Mention which APIs are working/not working

---

**Ready to get started?** Follow the setup steps for each service you want to use, then run the complete system test! 🚀