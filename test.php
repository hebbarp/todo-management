<?php
/**
 * Test Script for Todo Management System
 * Verifies all components are working correctly
 */

require_once 'php/config.php';
require_once 'php/TodoManager.php';
require_once 'php/WhatsAppIntegration.php';
require_once 'php/EmailIntegration.php';
require_once 'php/GoogleSheetsIntegration.php';
require_once 'php/MultiChannelSync.php';

echo "<h1>🧪 Todo Management System - PHP Test Suite</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 2rem; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Test 1: Configuration
echo "<h2>1. Configuration Test</h2>";
try {
    Config::load();
    echo "<div class='success'>✅ Configuration loaded successfully</div>";
    
    $dbPath = Config::get('db_path');
    echo "<div class='info'>📄 Database path: $dbPath</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Configuration failed: " . $e->getMessage() . "</div>";
}

// Test 2: Database Connection
echo "<h2>2. Database Test</h2>";
try {
    $pdo = Config::getDatabaseConnection();
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Test database operations
    $todoManager = new TodoManager();
    $stats = $todoManager->getStats();
    echo "<div class='info'>📊 Current stats: {$stats['total']} total, {$stats['pending']} pending, {$stats['completed']} completed</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database failed: " . $e->getMessage() . "</div>";
}

// Test 3: Todo Management
echo "<h2>3. Todo Management Test</h2>";
try {
    $todoManager = new TodoManager();
    
    // Add test todo
    $todoId = $todoManager->addTodo("Test todo from PHP system", "test", "high");
    echo "<div class='success'>✅ Added test todo #$todoId</div>";
    
    // List todos
    $todos = $todoManager->getTodos('pending', null, 5);
    echo "<div class='info'>📋 Found " . count($todos) . " pending todos</div>";
    
    // Complete test todo
    $completed = $todoManager->completeTodo($todoId);
    if ($completed) {
        echo "<div class='success'>✅ Completed test todo #$todoId</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Todo management failed: " . $e->getMessage() . "</div>";
}

// Test 4: WhatsApp Integration
echo "<h2>4. WhatsApp Integration Test</h2>";
try {
    $whatsapp = new WhatsAppIntegration();
    
    // Test message parsing
    $response = $whatsapp->processMessage("Add todo: Test WhatsApp integration", "+919742814697");
    echo "<div class='success'>✅ WhatsApp message processed: $response</div>";
    
    // Check configuration
    $twilioSid = Config::get('twilio_sid');
    if (!empty($twilioSid)) {
        echo "<div class='info'>🔧 Twilio configured (SID: " . substr($twilioSid, 0, 10) . "...)</div>";
    } else {
        echo "<div class='info'>⚠️ Twilio not configured - WhatsApp sending disabled</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ WhatsApp integration failed: " . $e->getMessage() . "</div>";
}

// Test 5: Email Integration
echo "<h2>5. Email Integration Test</h2>";
try {
    $email = new EmailIntegration();
    
    // Check configuration
    $gmailUser = Config::get('gmail_user');
    if (!empty($gmailUser)) {
        echo "<div class='info'>🔧 Gmail configured: $gmailUser</div>";
        echo "<div class='info'>⚠️ Email processing requires valid Gmail credentials</div>";
    } else {
        echo "<div class='info'>⚠️ Gmail not configured - Email processing disabled</div>";
    }
    
    echo "<div class='success'>✅ Email integration class loaded</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Email integration failed: " . $e->getMessage() . "</div>";
}

// Test 6: Google Sheets Integration
echo "<h2>6. Google Sheets Integration Test</h2>";
try {
    $sheets = new GoogleSheetsIntegration();
    
    // Add test todo
    $sheetsTodoId = $sheets->addTodo("Test Google Sheets integration", "", "High", "Test note");
    echo "<div class='success'>✅ Added sheets todo #$sheetsTodoId</div>";
    
    // Get stats
    $sheetsStats = $sheets->getStats();
    echo "<div class='info'>📊 Sheets stats: {$sheetsStats['total']} total, {$sheetsStats['pending']} pending</div>";
    
    // Test mobile view
    $mobileView = $sheets->getMobileView();
    echo "<div class='info'>📱 Mobile view generated (" . substr_count($mobileView, "\n") . " lines)</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Google Sheets integration failed: " . $e->getMessage() . "</div>";
}

// Test 7: Multi-Channel Sync
echo "<h2>7. Multi-Channel Sync Test</h2>";
try {
    $sync = new MultiChannelSync();
    echo "<div class='success'>✅ Multi-channel sync class loaded</div>";
    
    // Test sync (without actually running it)
    echo "<div class='info'>🔄 Sync system ready (run manually via dashboard)</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Multi-channel sync failed: " . $e->getMessage() . "</div>";
}

// Test 8: File Permissions
echo "<h2>8. File Permissions Test</h2>";
$dataDir = Config::get('data_dir');
$logsDir = Config::get('logs_dir');
$backupsDir = Config::get('backups_dir');

foreach ([$dataDir, $logsDir, $backupsDir] as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "<div class='success'>✅ Directory writable: $dir</div>";
    } else {
        echo "<div class='error'>❌ Directory not writable: $dir</div>";
    }
}

// Test 9: PHP Environment
echo "<h2>9. PHP Environment Test</h2>";
echo "<div class='info'>🐘 PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>📦 SQLite: " . (extension_loaded('sqlite3') ? '✅ Available' : '❌ Not available') . "</div>";
echo "<div class='info'>🌐 cURL: " . (extension_loaded('curl') ? '✅ Available' : '❌ Not available') . "</div>";
echo "<div class='info'>📧 Mail: " . (function_exists('mail') ? '✅ Available' : '❌ Not available') . "</div>";
echo "<div class='info'>🗂️ JSON: " . (extension_loaded('json') ? '✅ Available' : '❌ Not available') . "</div>";

echo "<h2>🎉 Test Complete!</h2>";
echo "<p>If most items show green checkmarks (✅), your system is working correctly.</p>";
echo "<p><a href='index.php'>→ Go to Main Dashboard</a></p>";
?>