# Installation Guide

## Upload Methods

### Method 1: cPanel File Manager (Recommended)
1. Login to your hosting cPanel
2. Open "File Manager"
3. Navigate to `public_html` (or your domain folder)
4. Upload the zip file
5. Extract it using File Manager
6. Rename `.env.example` to `.env`
7. Edit `.env` with your API keys

### Method 2: FTP Upload
1. Use an FTP client (FileZilla, WinSCP, etc.)
2. Connect to your hosting account
3. Upload all files to your web directory
4. Set permissions: folders 755, files 644
5. Configure `.env` file

### Method 3: Git Clone (Advanced)
```bash
git clone https://github.com/hebbarp/todo-management.git
cd todo-management/todo-system-php
cp .env.example .env
# Edit .env with your credentials
```

## Directory Permissions

Set these permissions after upload:
- **Folders**: 755 (data/, logs/, backups/, includes/)
- **Files**: 644 (all .php, .md, .htaccess files)
- **.env file**: 600 (read/write for owner only)

## Database Setup

**No manual setup required!** The system uses SQLite and creates the database automatically on first run.

## API Keys Setup

Edit `.env` file with your credentials:

```env
# Get from https://console.twilio.com
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_token_here
TWILIO_PHONE_NUMBER=+1234567890

# Use Gmail App Password
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password
```

## Testing Installation

1. Visit: `your-domain.com/test.php`
2. Check all green checkmarks ✅
3. Fix any red errors ❌
4. Once all tests pass, visit `your-domain.com`

## Common Issues

### "Configuration failed"
- Check `.env` file exists and is readable
- Verify file permissions (644 for .env)

### "Database connection failed"
- Ensure `data/` directory is writable (755)
- Check PHP has SQLite support

### "WhatsApp not working"
- Verify Twilio credentials in `.env`
- Check Twilio console for account status
- Ensure webhook URL is configured

### "Email processing failed"
- Use Gmail App Password, not regular password
- Enable 2-factor authentication first
- Check Gmail IMAP is enabled

## Security Checklist

- [ ] `.env` file is not web-accessible
- [ ] `.htaccess` file is uploaded and working  
- [ ] Directory permissions are set correctly
- [ ] Test endpoint returns green checkmarks
- [ ] No sensitive data in error logs

## Performance Optimization

- Enable gzip compression (in .htaccess)
- Set appropriate cache headers
- Use PHP OPcache if available
- Monitor log file sizes

## Backup Strategy

The system creates automatic backups in `backups/` directory:
- Daily sync backups
- Emergency backups before major operations
- JSON exports of all data

Consider additional backups of:
- `.env` configuration file
- `data/` directory (contains SQLite database)
- Custom modifications you've made

## Updates

To update the system:
1. Backup your `.env` and `data/` directory
2. Upload new files (don't overwrite `.env`)
3. Run `test.php` to verify compatibility
4. Check changelog for any breaking changes

## Support

If you encounter issues:
1. Run `test.php` and check results
2. Check error logs in `logs/` directory
3. Verify API credentials are correct
4. Contact your hosting provider for server issues