# Todo Management System - PHP Edition

🚀 **Complete multi-channel todo management system built in PHP for easy hosting**

## ✨ Features

- 📱 **WhatsApp Integration** - Send todos via WhatsApp messages
- 📧 **Email Processing** - Auto-extract todos from emails
- 📊 **Google Sheets Sync** - Manage todos in spreadsheet format
- 🌐 **Web Dashboard** - Professional interface with real-time stats
- 🔄 **Multi-Channel Sync** - Coordinates todos across all platforms
- 🗄️ **SQLite Database** - No MySQL setup required
- 🔒 **Secure Configuration** - Environment-based API keys

## 🚀 Quick Setup (2 Minutes)

### 1. Upload Files
Upload all files to your web hosting provider (cPanel, FTP, etc.)

### 2. Configure Environment
1. Rename `.env.example` to `.env`
2. Edit `.env` with your API keys:
```env
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password
```

### 3. Test Installation
Visit: `your-domain.com/test.php`

### 4. Start Using
Visit: `your-domain.com` and start managing todos!

## 📋 Requirements

- **PHP 7.0+** (tested up to PHP 8.4)
- **SQLite support** (enabled by default)
- **cURL extension** (for API calls)
- **Standard shared hosting** (no special configuration needed)

## 🗂️ File Structure

```
todo-system-php/
├── index.php              # Main dashboard
├── test.php               # System testing
├── webhook.php            # WhatsApp webhook handler
├── .env.example           # Configuration template
├── .htaccess             # Apache security & routing
├── README.md             # This file
└── includes/             # Core PHP classes
    ├── config.php        # Configuration & database
    ├── TodoManager.php   # Core todo operations
    ├── WhatsAppIntegration.php
    ├── EmailIntegration.php
    ├── GoogleSheetsIntegration.php
    └── MultiChannelSync.php
```

## 🔧 API Endpoints

- `GET /api/stats` - Get todo statistics
- `GET /api/todos` - List todos (with optional filters)
- `POST /api/sync` - Trigger multi-channel sync
- `POST /webhook` - WhatsApp webhook endpoint

## 📱 WhatsApp Commands

Send these messages to your WhatsApp number:

- **Add Todo**: "Add todo: Call investor meeting"
- **Complete Todo**: "Complete 5" or "Done 3"
- **List Todos**: "List todos" or "Show my todos"
- **Help**: "Help" or "Commands"

## 📧 Email Integration

The system automatically extracts todos from emails containing:
- Numbered lists (1. Review budget)
- Bullet points (• Call client)
- Action phrases ("Please remember to...")
- Todo keywords ("Action item: Update website")

## 🌐 Hosting Compatibility

Tested and works on:
- ✅ **Shared Hosting** (GoDaddy, Bluehost, etc.)
- ✅ **VPS/Cloud** (DigitalOcean, AWS, etc.)
- ✅ **Local Development** (XAMPP, MAMP, etc.)

## 🔒 Security Features

- Environment variable configuration
- `.htaccess` protection for sensitive files
- SQL injection prevention with PDO
- XSS protection with input sanitization
- Secure headers and content type policies

## 🧪 Testing

Run comprehensive tests:
```
your-domain.com/test.php
```

This will verify:
- PHP compatibility
- Database connection
- All integrations
- File permissions
- Security settings

## 📞 Support

- **Documentation**: Check included files
- **Issues**: [GitHub Repository](https://github.com/hebbarp/todo-management)
- **Hosting**: Works on any standard PHP hosting

## 🚀 Production Deployment

1. **Upload** all files to your hosting provider
2. **Configure** `.env` with real API credentials  
3. **Test** via `test.php` to verify everything works
4. **Secure** by ensuring `.env` is not web-accessible
5. **Monitor** logs in the `logs/` directory

## 🎯 Next Steps

1. Set up your API keys (Twilio, Gmail)
2. Configure WhatsApp webhook URL in Twilio Console
3. Set up cron jobs for automatic sync (optional)
4. Customize the interface to match your branding

---

**Built with ❤️ for easy hosting and powerful automation**

*PHP Edition - No dependencies, just upload and go!*