<?php
/**
 * Digital Ocean Server Diagnostics
 * Upload this file to your server to diagnose the issue
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔧 Server Diagnostics - Digital Ocean</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .command { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Digital Ocean Server Diagnostics</h1>
        <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>

        <h2>1. PHP Configuration</h2>
        <table>
            <tr><th>Setting</th><th>Value</th><th>Status</th></tr>
            <tr>
                <td>PHP Version</td>
                <td><?php echo PHP_VERSION; ?></td>
                <td><?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '<span style="color: green;">✅ OK</span>' : '<span style="color: red;">❌ Too Old</span>'; ?></td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                <td>ℹ️ Info</td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td>
                <td>ℹ️ Info</td>
            </tr>
        </table>

        <h2>2. Required PHP Extensions</h2>
        <?php
        $extensions = [
            'sqlite3' => 'SQLite3 database support',
            'pdo' => 'PDO database abstraction',
            'pdo_sqlite' => 'PDO SQLite driver',
            'json' => 'JSON processing',
            'curl' => 'HTTP requests (optional)'
        ];

        $missing_extensions = [];
        foreach ($extensions as $ext => $description) {
            $loaded = extension_loaded($ext);
            echo "<div class='" . ($loaded ? 'success' : 'error') . "'>";
            echo ($loaded ? '✅' : '❌') . " <strong>$ext</strong>: $description " . ($loaded ? '(Loaded)' : '(Missing)');
            echo "</div>";
            
            if (!$loaded) {
                $missing_extensions[] = $ext;
            }
        }
        ?>

        <?php if (!empty($missing_extensions)): ?>
        <h2>3. ⚠️ Missing Extensions - Installation Commands</h2>
        <div class="warning">
            <strong>Missing extensions detected!</strong> Run these commands on your Digital Ocean server:
        </div>

        <h3>For Ubuntu/Debian:</h3>
        <div class="command">
sudo apt update<br>
<?php foreach ($missing_extensions as $ext): ?>
sudo apt install php-<?php echo $ext; ?><br>
<?php endforeach; ?>
sudo systemctl restart apache2   # or: sudo systemctl restart nginx
        </div>

        <h3>For CentOS/RHEL:</h3>
        <div class="command">
sudo yum update<br>
<?php foreach ($missing_extensions as $ext): ?>
sudo yum install php-<?php echo $ext; ?><br>
<?php endforeach; ?>
sudo systemctl restart httpd     # or: sudo systemctl restart nginx
        </div>
        <?php endif; ?>

        <h2>4. File System Check</h2>
        <?php
        $files_to_check = [
            'workshop_registrations.db' => 'Database file',
            'register.php' => 'Registration handler',
            'setup_db.php' => 'Database setup script',
            'index.html' => 'Main page'
        ];

        foreach ($files_to_check as $file => $description) {
            $path = __DIR__ . '/' . $file;
            $exists = file_exists($path);
            $readable = $exists ? is_readable($path) : false;
            $writable = $exists ? is_writable($path) : false;
            $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
            
            echo "<div class='" . ($exists ? 'success' : 'error') . "'>";
            echo ($exists ? '✅' : '❌') . " <strong>$file</strong>: $description<br>";
            if ($exists) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;Permissions: $perms | Readable: " . ($readable ? 'Yes' : 'No') . " | Writable: " . ($writable ? 'Yes' : 'No');
            }
            echo "</div>";
        }
        ?>

        <h2>5. Database Connection Test</h2>
        <?php
        try {
            $db_path = __DIR__ . '/workshop_registrations.db';
            
            if (!file_exists($db_path)) {
                echo "<div class='warning'>⚠️ Database file doesn't exist. Run setup_db.php first.</div>";
                echo "<div class='command'>php setup_db.php</div>";
            } else {
                $pdo = new PDO('sqlite:' . $db_path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Test table exists
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='registrations'");
                $table_exists = $stmt->fetch() !== false;
                
                if ($table_exists) {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo "<div class='success'>✅ Database connection successful! Found $count registrations.</div>";
                } else {
                    echo "<div class='error'>❌ Database exists but 'registrations' table is missing!</div>";
                    echo "<div class='command'>php setup_db.php</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>

        <h2>6. Directory Permissions</h2>
        <?php
        $current_dir = __DIR__;
        $readable = is_readable($current_dir);
        $writable = is_writable($current_dir);
        $perms = substr(sprintf('%o', fileperms($current_dir)), -4);
        
        echo "<div class='" . ($readable && $writable ? 'success' : 'warning') . "'>";
        echo "📁 Current directory: $current_dir<br>";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Permissions: $perms | Readable: " . ($readable ? 'Yes' : 'No') . " | Writable: " . ($writable ? 'Yes' : 'No');
        echo "</div>";
        
        if (!$writable) {
            echo "<div class='warning'>⚠️ Directory not writable! Fix with:</div>";
            echo "<div class='command'>sudo chown -R www-data:www-data " . dirname($current_dir) . "<br>sudo chmod -R 755 " . dirname($current_dir) . "</div>";
        }
        ?>

        <h2>7. Error Log Check</h2>
        <?php
        $error_logs = [
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log',
            '/var/log/php_errors.log',
            ini_get('error_log')
        ];
        
        $found_logs = [];
        foreach ($error_logs as $log) {
            if ($log && file_exists($log) && is_readable($log)) {
                $found_logs[] = $log;
            }
        }
        
        if (empty($found_logs)) {
            echo "<div class='warning'>⚠️ No accessible error logs found.</div>";
        } else {
            echo "<div class='info'>📋 Check these error logs for details:</div>";
            foreach ($found_logs as $log) {
                echo "<div class='command'>sudo tail -20 $log</div>";
            }
        }
        ?>

        <h2>8. Quick Fix Summary</h2>
        <div class="info">
            <strong>Most likely fix for your issue:</strong><br><br>
            1️⃣ Install SQLite3 extension:<br>
            <div class="command">sudo apt install php-sqlite3 php-pdo-sqlite</div><br>
            
            2️⃣ Restart web server:<br>
            <div class="command">sudo systemctl restart apache2</div><br>
            
            3️⃣ Initialize database:<br>
            <div class="command">php setup_db.php</div><br>
            
            4️⃣ Fix permissions if needed:<br>
            <div class="command">sudo chown -R www-data:www-data /path/to/your/app<br>sudo chmod 644 workshop_registrations.db</div>
        </div>

        <h2>9. Test Registration</h2>
        <div class="info">
            After fixing the issues above, test registration with:
            <div class="command">curl -X POST -H "Content-Type: application/json" -d '{"fullName":"Test User","email":"test@example.com","phone":"123-456-7890","company":"Test Corp","position":"CEO","inquiryType":"workshop"}' https://your-domain.com/register.php</div>
        </div>
    </div>
</body>
</html>