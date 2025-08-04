# 🔐 Security Documentation - Vibe Tasking Admin Panel

## 🛡️ Authentication System

### **Default Credentials:**
- **Username:** `admin`
- **Password:** `VibeTa$king2025!`

> **⚠️ IMPORTANT:** Change the default credentials in `auth.php` before production use!

## 🔒 Security Features

### **Authentication Protection:**
- ✅ **Session-based authentication** with secure token generation
- ✅ **Rate limiting** - 5 failed attempts per IP per 15 minutes
- ✅ **Session timeout** - 1 hour of inactivity
- ✅ **CSRF protection** for all forms
- ✅ **Activity logging** - All login attempts are logged
- ✅ **Secure password requirements** with special characters

### **Access Control:**
- ✅ **Protected endpoints** - Analytics only accessible after login
- ✅ **Direct URL protection** - view_registrations.php redirects to login
- ✅ **CLI access preserved** - Command line access still available
- ✅ **Automatic logout** on session timeout

### **Data Protection:**
- ✅ **SQL injection prevention** with prepared statements
- ✅ **XSS protection** with htmlspecialchars()
- ✅ **Input validation** for all form fields
- ✅ **Secure file permissions** for sensitive files

## 🌐 Access URLs

### **Public Access:**
- `http://localhost:8000/` - Main Vibe Tasking landing page
- `http://localhost:8000/register.php` - Registration API endpoint (POST only)

### **Protected Access (Login Required):**
- `http://localhost:8000/login.php` - Admin login page
- `http://localhost:8000/admin.php` - Secure admin dashboard
- `http://localhost:8000/view_registrations.php` - Redirects to login

### **CLI Access (No Authentication):**
```bash
php view_registrations.php    # Command line interface
php setup_db.php             # Database setup
```

## 📊 What's Protected

### **Analytics & Data:**
- Registration counts and statistics
- Workshop vs Talk to Us breakdown
- User search and filtering
- CSV data export
- Individual registration details

### **Management Features:**
- Search functionality across all registrations
- CSV export with all sensitive data
- Real-time analytics dashboard
- Session management and user info

## 🔑 Login Process

1. **Navigate to:** `http://localhost:8000/login.php`
2. **Enter credentials:**
   - Username: `admin`
   - Password: `VibeTa$king2025!`
3. **Access granted:** Redirects to secure admin dashboard
4. **Session active:** 1 hour of authenticated access

## 🚨 Security Monitoring

### **Activity Logging:**
- **Location:** `auth.log`
- **Tracks:** Login attempts, failures, logouts, session timeouts
- **Format:** `[timestamp] activity description`

### **Rate Limiting:**
- **Location:** `login_attempts.json`
- **Limits:** 5 attempts per IP per 15 minutes
- **Auto-cleanup:** Old attempts removed automatically

### **Example Log Entries:**
```
[2025-07-28 12:30:15] Successful login for user: admin
[2025-07-28 12:35:22] Failed login attempt for user: admin from IP: 127.0.0.1
[2025-07-28 13:30:15] User logged out: admin
```

## ⚙️ Configuration

### **Changing Credentials (auth.php):**
```php
define('ADMIN_USERNAME', 'your_username');
define('ADMIN_PASSWORD', 'Your$ecureP@ssw0rd!');
```

### **Session Settings:**
```php
define('SESSION_TIMEOUT', 3600); // 1 hour
```

### **Rate Limiting:**
```php
// 5 attempts per 15 minutes (900 seconds)
if ($attempts[$ip]['count'] >= 5) {
    if ($current_time - $data['last_attempt'] > 900) {
        // Reset attempts
    }
}
```

## 🔐 Production Security Checklist

- [ ] **Change default credentials** in `auth.php`
- [ ] **Use HTTPS** in production environment
- [ ] **Set proper file permissions** (644 for files, 755 for directories)
- [ ] **Move sensitive files** outside web root if possible
- [ ] **Regular backup** of registration data
- [ ] **Monitor access logs** regularly
- [ ] **Update PHP** to latest secure version
- [ ] **Use strong passwords** with special characters
- [ ] **Implement IP whitelisting** if needed
- [ ] **Enable PHP security features** (disable dangerous functions)

## 🛠️ File Permissions

### **Recommended Settings:**
```bash
chmod 644 *.php *.html *.md
chmod 755 .
chmod 600 auth.log login_attempts.json
chmod 644 workshop_registrations.db
```

### **Sensitive Files:**
- `auth.php` - Contains credentials
- `auth.log` - Login activity log
- `login_attempts.json` - Rate limiting data
- `workshop_registrations.db` - User data

## 🔍 Testing Security

### **Test Authentication:**
```bash
# Should redirect to login
curl -I http://localhost:8000/admin.php

# Should show login form
curl http://localhost:8000/login.php
```

### **Test Rate Limiting:**
- Try 6 failed login attempts rapidly
- Verify lockout message appears
- Wait 15 minutes and try again

### **Test Session Timeout:**
- Login and wait 1 hour
- Try to access admin panel
- Should redirect to login

## 📞 Security Support

### **If Compromised:**
1. **Immediately change** credentials in `auth.php`
2. **Clear sessions:** Delete session files or restart server
3. **Check logs:** Review `auth.log` for suspicious activity
4. **Reset rate limiting:** Delete `login_attempts.json`
5. **Update system:** Ensure latest security patches

### **Regular Maintenance:**
- **Weekly:** Review access logs
- **Monthly:** Rotate passwords
- **Quarterly:** Security audit
- **As needed:** Update system components

---

**🛡️ Security is a priority - Keep your credentials secure and monitor access regularly!**