<?php
// Create downloadable package on-the-fly
if (isset($_GET['download'])) {
    $packageType = $_GET['download'];
    
    if (!in_array($packageType, ['complete', 'basic', 'hosting'])) {
        header('HTTP/1.0 404 Not Found');
        exit('Package not found');
    }
    
    // Create temporary zip file
    $zipFile = tempnam(sys_get_temp_dir(), 'todo_management_') . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        exit("Cannot create zip file");
    }
    
    // Add files based on package type
    switch ($packageType) {
        case 'complete':
            addCompletePackage($zip);
            $filename = 'todo-management-php-complete.zip';
            break;
        case 'basic':
            addBasicPackage($zip);
            $filename = 'todo-management-php-basic.zip';
            break;
        case 'hosting':
            addHostingPackage($zip);
            $filename = 'todo-management-php-hosting.zip';
            break;
    }
    
    $zip->close();
    
    // Send file to browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipFile));
    
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

function addCompletePackage($zip) {
    // Add all PHP files
    addDirectoryToZip($zip, 'php/', 'php/');
    
    // Add configuration files
    $zip->addFile('.env.example', '.env.example');
    $zip->addFile('README.md', 'README.md');
    
    // Add documentation
    addDirectoryToZip($zip, 'docs/', 'docs/');
    
    // Add setup files
    addSetupFiles($zip);
    
    // Add main files
    $zip->addFile('index.php', 'index.php');
    $zip->addFile('download.php', 'download.php');
    
    // Add webhook handler
    $zip->addFromString('webhook.php', getWebhookHandler());
    
    // Add cron job examples
    $zip->addFromString('cron/sync.php', getCronSyncFile());
    $zip->addFromString('cron/crontab.example', getCrontabExample());
}

function addBasicPackage($zip) {
    // Core PHP files only
    $zip->addFile('php/config.php', 'php/config.php');
    $zip->addFile('php/TodoManager.php', 'php/TodoManager.php');
    $zip->addFile('php/WhatsAppIntegration.php', 'php/WhatsAppIntegration.php');
    $zip->addFile('php/EmailIntegration.php', 'php/EmailIntegration.php');
    $zip->addFile('php/GoogleSheetsIntegration.php', 'php/GoogleSheetsIntegration.php');
    $zip->addFile('php/MultiChannelSync.php', 'php/MultiChannelSync.php');
    
    $zip->addFile('.env.example', '.env.example');
    $zip->addFile('index.php', 'index.php');
    
    // Basic setup
    $zip->addFromString('setup.txt', getBasicSetupInstructions());
}

function addHostingPackage($zip) {
    // Optimized for web hosting
    addCompletePackage($zip);
    
    // Add hosting-specific files
    $zip->addFromString('.htaccess', getHtaccessFile());
    $zip->addFromString('hosting-setup.md', getHostingInstructions());
    $zip->addFromString('test-hosting.php', getHostingTestFile());
}

function addDirectoryToZip($zip, $source, $destination) {
    if (is_dir($source)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $destination . $iterator->getSubPathName();
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }
    }
}

function addSetupFiles($zip) {
    $zip->addFromString('SETUP.md', getSetupInstructions());
    $zip->addFromString('API_KEYS.md', getApiKeysGuide());
}

function getWebhookHandler() {
    return '<?php
require_once "php/WhatsAppIntegration.php";

$webhook = new WhatsAppWebhook();
$webhook->handleWebhook();
?>';
}

function getCronSyncFile() {
    return '<?php
require_once __DIR__ . "/../php/MultiChannelSync.php";

$sync = new MultiChannelSync();
$sync->syncAllChannels();

// Optional: Send daily digest
$gmailUser = Config::get("gmail_user");
if ($gmailUser) {
    $sync->sendDailyDigest($gmailUser);
}
?>';
}

function getCrontabExample() {
    return '# Todo Management System Cron Jobs
# Add these to your crontab with: crontab -e

# Sync all channels every 30 minutes
*/30 * * * * /usr/bin/php /path/to/your/site/cron/sync.php

# Daily digest email at 9 AM
0 9 * * * /usr/bin/php /path/to/your/site/cron/sync.php

# Weekly cleanup on Sundays at 2 AM
0 2 * * 0 /usr/bin/php /path/to/your/site/cron/cleanup.php';
}

function getHtaccessFile() {
    return 'RewriteEngine On

# Redirect webhook requests
RewriteRule ^webhook/?$ webhook.php [L]

# API endpoints
RewriteRule ^api/(.+)$ index.php?api=$1 [L,QSA]

# Block access to sensitive files
<Files ".env">
    Require all denied
</Files>

<Files "*.log">
    Require all denied
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>';
}

function getHostingInstructions() {
    return '# Hosting Setup Instructions

## Quick Upload Guide

1. **Upload files** to your web hosting via cPanel File Manager or FTP
2. **Create .env file** from .env.example with your API keys
3. **Set permissions** on data/, logs/, and backups/ folders to 755
4. **Test installation** by visiting your-domain.com/test-hosting.php

## Shared Hosting Requirements

- PHP 7.4 or higher
- SQLite support (enabled by default)
- cURL support (for API calls)
- Mail function (for email features)

## cPanel Setup

1. Upload zip file to public_html folder
2. Extract using cPanel File Manager
3. Edit .env file with your API keys
4. Set up cron jobs in cPanel for automation

## API Key Configuration

Edit .env file:
```
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
GMAIL_USER=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password
```

## Testing

Visit: your-domain.com/test-hosting.php
This will verify all components are working correctly.

## Security

- .htaccess file blocks access to .env and log files
- Database is SQLite (no MySQL setup required)
- All sensitive data is in environment variables

## Support

Check the main documentation or create an issue on GitHub.';
}

function getHostingTestFile() {
    return '<?php
require_once "php/config.php";

echo "<h1>Todo Management System - Hosting Test</h1>";

// Test PHP version
echo "<h2>PHP Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "SQLite: " . (extension_loaded("sqlite3") ? "✅ Available" : "❌ Not available") . "<br>";
echo "cURL: " . (extension_loaded("curl") ? "✅ Available" : "❌ Not available") . "<br>";
echo "Mail: " . (function_exists("mail") ? "✅ Available" : "❌ Not available") . "<br>";

// Test configuration
echo "<h2>Configuration</h2>";
try {
    Config::load();
    echo "Configuration: ✅ Loaded successfully<br>";
    
    $pdo = Config::getDatabaseConnection();
    echo "Database: ✅ Connected successfully<br>";
    
    $todoManager = new TodoManager();
    $stats = $todoManager->getStats();
    echo "Todo Manager: ✅ Working (Total todos: {$stats[\"total\"]})<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test API keys
echo "<h2>API Keys</h2>";
echo "Twilio: " . (!empty(Config::get("twilio_sid")) ? "✅ Configured" : "❌ Not configured") . "<br>";
echo "Gmail: " . (!empty(Config::get("gmail_user")) ? "✅ Configured" : "❌ Not configured") . "<br>";

echo "<br><strong>✅ If all items show green checkmarks, your system is ready!</strong>";
echo "<br><a href=\"index.php\">→ Go to Main Dashboard</a>";
?>';
}

function getSetupInstructions() {
    return '# Todo Management System - PHP Setup

## Quick Start (5 Minutes)

1. **Upload to Web Hosting**
   - Upload all files to your web hosting account
   - Extract to your domain folder (public_html or www)

2. **Configure Environment**
   - Rename `.env.example` to `.env`
   - Edit `.env` with your API keys (see API_KEYS.md)

3. **Test Installation**
   - Visit: your-domain.com/test-hosting.php
   - Verify all components are working

4. **Start Using**
   - Visit: your-domain.com
   - Your todo system is ready!

## Features

- 📱 WhatsApp integration via Twilio
- 📧 Email processing with IMAP
- 📊 Google Sheets sync (CSV-based)
- 🌐 Web dashboard for management
- 🔄 Multi-channel synchronization
- 📊 Analytics and reporting

## Hosting Requirements

- PHP 7.4+ with SQLite, cURL, and Mail support
- Standard shared hosting works perfectly
- No database setup required (uses SQLite)

## Security

- Environment variables for API keys
- .htaccess protection for sensitive files
- SQLite database with proper permissions

## Support

- Documentation: Check included docs/
- Issues: GitHub repository
- Hosting: Works on any standard PHP hosting';
}

function getApiKeysGuide() {
    return file_get_contents('API_KEYS_GUIDE.md');
}

function getBasicSetupInstructions() {
    return 'BASIC SETUP INSTRUCTIONS

1. Upload all files to your web server
2. Create .env file from .env.example
3. Edit .env with your API keys:
   - TWILIO_ACCOUNT_SID=your_twilio_sid
   - TWILIO_AUTH_TOKEN=your_twilio_token
   - GMAIL_USER=your_email
   - GMAIL_APP_PASSWORD=your_app_password
4. Visit index.php to start using the system

For detailed setup guide, download the complete package.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download - Todo Management System PHP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .package-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .package-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .package-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .package-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .package-features {
            text-align: left;
            margin-bottom: 2rem;
        }
        .package-features li {
            margin-bottom: 0.5rem;
            color: #555;
        }
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Download PHP Todo System</h1>
            <p>Choose the package that best fits your needs</p>
        </div>
        
        <div class="package-grid">
            <div class="package-card">
                <div class="package-icon">🚀</div>
                <h2 class="package-title">Complete Package</h2>
                <p class="package-description">
                    Everything you need for a full todo management system with all integrations and documentation.
                </p>
                <ul class="package-features">
                    <li>✅ All PHP modules and integrations</li>
                    <li>✅ Web dashboard and API</li>
                    <li>✅ Webhook handlers</li>
                    <li>✅ Cron job examples</li>
                    <li>✅ Complete documentation</li>
                    <li>✅ Setup and configuration guides</li>
                </ul>
                <a href="?download=complete" class="download-btn">Download Complete (~50KB)</a>
            </div>
            
            <div class="package-card">
                <div class="package-icon">⚡</div>
                <h2 class="package-title">Basic Package</h2>
                <p class="package-description">
                    Core functionality only. Perfect if you want to customize or integrate into existing systems.
                </p>
                <ul class="package-features">
                    <li>✅ Core PHP classes</li>
                    <li>✅ WhatsApp, Email, Sheets integration</li>
                    <li>✅ Simple dashboard</li>
                    <li>✅ Basic setup instructions</li>
                    <li>❌ No webhook handlers</li>
                    <li>❌ No cron examples</li>
                </ul>
                <a href="?download=basic" class="download-btn">Download Basic (~25KB)</a>
            </div>
            
            <div class="package-card">
                <div class="package-icon">🌐</div>
                <h2 class="package-title">Hosting Optimized</h2>
                <p class="package-description">
                    Specially prepared for shared hosting with additional configuration files and testing tools.
                </p>
                <ul class="package-features">
                    <li>✅ Everything from Complete package</li>
                    <li>✅ .htaccess configuration</li>
                    <li>✅ Hosting compatibility test</li>
                    <li>✅ cPanel setup guide</li>
                    <li>✅ Security optimizations</li>
                    <li>✅ Upload and deploy scripts</li>
                </ul>
                <a href="?download=hosting" class="download-btn">Download Hosting (~55KB)</a>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 15px; color: white; text-align: center; backdrop-filter: blur(10px);">
            <h3>🎯 Why Choose PHP Edition?</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div>
                    <strong>🌐 Easy Hosting</strong><br>
                    Works on any shared hosting provider with PHP support
                </div>
                <div>
                    <strong>🗄️ No Database Setup</strong><br>
                    Uses SQLite - no MySQL configuration required
                </div>
                <div>
                    <strong>🔧 Simple Installation</strong><br>
                    Upload files, edit .env, and you're ready to go
                </div>
                <div>
                    <strong>🔒 Secure by Default</strong><br>
                    Environment variables and .htaccess protection
                </div>
            </div>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>