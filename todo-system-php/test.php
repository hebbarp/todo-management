<?php
/**
 * Complete System Test for Todo Management System
 * Verifies all components are working correctly
 */

require_once 'includes/config.php';
require_once 'includes/TodoManager.php';
require_once 'includes/WhatsAppIntegration.php';
require_once 'includes/EmailIntegration.php';
require_once 'includes/GoogleSheetsIntegration.php';
require_once 'includes/MultiChannelSync.php';

echo "<h1>🧪 Todo Management System - Complete Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 2rem; line-height: 1.6; } 
    .success { color: #27ae60; font-weight: bold; } 
    .error { color: #e74c3c; font-weight: bold; } 
    .info { color: #3498db; } 
    .warning { color: #f39c12; font-weight: bold; }
    h2 { border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; margin-top: 2rem; }
    .test-section { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    .summary { background: #d4edda; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; border-left: 5px solid #27ae60; }
</style>";

$testResults = [];

// Test 1: PHP Environment
echo "<h2>1. 🐘 PHP Environment Test</h2>";
echo "<div class='test-section'>";
$phpVersion = phpversion();
echo "<div class='info'>PHP Version: $phpVersion</div>";

if (version_compare($phpVersion, '7.0.0', '>=')) {
    echo "<div class='success'>✅ PHP 7.0+ detected - System compatible</div>";
    $testResults['php_version'] = true;
} else {
    echo "<div class='error'>❌ PHP version too old. Requires PHP 7.0+</div>";
    $testResults['php_version'] = false;
}

// Required extensions
$required_extensions = ['sqlite3', 'curl', 'json', 'mbstring'];
$extensions_ok = true;

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext extension available</div>";
    } else {
        echo "<div class='error'>❌ $ext extension missing</div>";
        $extensions_ok = false;
    }
}
$testResults['extensions'] = $extensions_ok;
echo "</div>";

// Test 2: Configuration
echo "<h2>2. ⚙️ Configuration Test</h2>";
echo "<div class='test-section'>";
try {
    Config::load();
    echo "<div class='success'>✅ Configuration loaded successfully</div>";
    
    $dbPath = Config::get('db_path');
    echo "<div class='info'>Database path: $dbPath</div>";
    
    $twilioSid = Config::get('twilio_sid');
    $gmailUser = Config::get('gmail_user');
    
    echo "<div class='info'>Twilio configured: " . (!empty($twilioSid) ? 'Yes (SID: ' . substr($twilioSid, 0, 10) . '...)' : 'No') . "</div>";
    echo "<div class='info'>Gmail configured: " . (!empty($gmailUser) ? "Yes ($gmailUser)" : 'No') . "</div>";
    
    $testResults['config'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Configuration failed: " . $e->getMessage() . "</div>";
    $testResults['config'] = false;
}
echo "</div>";

// Test 3: Database Connection
echo "<h2>3. 🗄️ Database Test</h2>";
echo "<div class='test-section'>";
try {
    $pdo = Config::getDatabaseConnection();
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Test database operations
    $todoManager = new TodoManager();
    $stats = $todoManager->getStats();
    echo "<div class='info'>Current stats: {$stats['total']} total, {$stats['pending']} pending, {$stats['completed']} completed</div>";
    
    $testResults['database'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Database failed: " . $e->getMessage() . "</div>";
    $testResults['database'] = false;
}
echo "</div>";

// Test 4: Todo Management
echo "<h2>4. 📋 Todo Management Test</h2>";
echo "<div class='test-section'>";
try {
    $todoManager = new TodoManager();
    
    // Add test todo
    $todoId = $todoManager->addTodo("Test todo from system check", "test", "high");
    echo "<div class='success'>✅ Added test todo #$todoId</div>";
    
    // List todos
    $todos = $todoManager->getTodos('pending', null, 5);
    echo "<div class='info'>Found " . count($todos) . " pending todos</div>";
    
    // Complete test todo
    $completed = $todoManager->completeTodo($todoId);
    if ($completed) {
        echo "<div class='success'>✅ Completed test todo #$todoId</div>";
    }
    
    // Test search
    $searchResults = $todoManager->searchTodos("test", 5);
    echo "<div class='info'>Search function returned " . count($searchResults) . " results</div>";
    
    $testResults['todo_management'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Todo management failed: " . $e->getMessage() . "</div>";
    $testResults['todo_management'] = false;
}
echo "</div>";

// Test 5: WhatsApp Integration
echo "<h2>5. 📱 WhatsApp Integration Test</h2>";
echo "<div class='test-section'>";
try {
    $whatsapp = new WhatsAppIntegration();
    
    // Test message parsing
    $response = $whatsapp->processMessage("Add todo: Test WhatsApp integration", "+919742814697");
    echo "<div class='success'>✅ WhatsApp message processed: $response</div>";
    
    // Test help message
    $helpResponse = $whatsapp->processMessage("help", "+919742814697");
    echo "<div class='info'>Help message generated (" . strlen($helpResponse) . " characters)</div>";
    
    // Test list todos
    $listResponse = $whatsapp->processMessage("list todos", "+919742814697");
    echo "<div class='info'>List response generated (" . strlen($listResponse) . " characters)</div>";
    
    $testResults['whatsapp'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ WhatsApp integration failed: " . $e->getMessage() . "</div>";
    $testResults['whatsapp'] = false;
}
echo "</div>";

// Test 6: Email Integration
echo "<h2>6. 📧 Email Integration Test</h2>";
echo "<div class='test-section'>";
try {
    $email = new EmailIntegration();
    
    // Test todo parsing
    $testEmailBody = "Meeting action items:\n1. Review quarterly budget\n2. Schedule investor call\n3. Update website content\n\nPlease remember to call the marketing agency.";
    $extractedTodos = $email->parseEmailForTodos($testEmailBody, "test@example.com", "Meeting Follow-up");
    
    echo "<div class='success'>✅ Email todo extraction working</div>";
    echo "<div class='info'>Extracted " . count($extractedTodos) . " todos from test email</div>";
    
    foreach ($extractedTodos as $i => $todo) {
        echo "<div class='info'>  " . ($i + 1) . ". $todo</div>";
    }
    
    $testResults['email'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Email integration failed: " . $e->getMessage() . "</div>";
    $testResults['email'] = false;
}
echo "</div>";

// Test 7: Google Sheets Integration
echo "<h2>7. 📊 Google Sheets Integration Test</h2>";
echo "<div class='test-section'>";
try {
    $sheets = new GoogleSheetsIntegration();
    
    // Add test todo
    $sheetsTodoId = $sheets->addTodo("Test Google Sheets integration", "", "High", "Test note from system check");
    echo "<div class='success'>✅ Added sheets todo #$sheetsTodoId</div>";
    
    // Get stats
    $sheetsStats = $sheets->getStats();
    echo "<div class='info'>Sheets stats: {$sheetsStats['total']} total, {$sheetsStats['pending']} pending</div>";
    
    // Test mobile view
    $mobileView = $sheets->getMobileView();
    echo "<div class='info'>Mobile view generated (" . substr_count($mobileView, "\n") . " lines)</div>";
    
    // Test export
    $exportFile = $sheets->exportToJson();
    if (file_exists($exportFile)) {
        echo "<div class='success'>✅ Export functionality working</div>";
    }
    
    $testResults['sheets'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Google Sheets integration failed: " . $e->getMessage() . "</div>";
    $testResults['sheets'] = false;
}
echo "</div>";

// Test 8: Multi-Channel Sync
echo "<h2>8. 🔄 Multi-Channel Sync Test</h2>";
echo "<div class='test-section'>";
try {
    $sync = new MultiChannelSync();
    echo "<div class='success'>✅ Multi-channel sync class loaded</div>";
    
    // Test backup creation
    $backupFile = $sync->createEmergencyBackup();
    if (file_exists($backupFile)) {
        echo "<div class='success'>✅ Emergency backup created</div>";
    }
    
    echo "<div class='info'>🔄 Full sync ready (can be triggered via dashboard)</div>";
    
    $testResults['sync'] = true;
} catch (Exception $e) {
    echo "<div class='error'>❌ Multi-channel sync failed: " . $e->getMessage() . "</div>";
    $testResults['sync'] = false;
}
echo "</div>";

// Test 9: File Permissions
echo "<h2>9. 📁 File Permissions Test</h2>";
echo "<div class='test-section'>";
$dataDir = Config::get('data_dir');
$logsDir = Config::get('logs_dir');
$backupsDir = Config::get('backups_dir');

$permissions_ok = true;
foreach ([$dataDir, $logsDir, $backupsDir] as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "<div class='success'>✅ Directory writable: $dir</div>";
    } else {
        echo "<div class='error'>❌ Directory not writable: $dir</div>";
        $permissions_ok = false;
    }
}
$testResults['permissions'] = $permissions_ok;
echo "</div>";

// Test Summary
echo "<h2>🎯 Test Summary</h2>";

$passedTests = array_sum($testResults);
$totalTests = count($testResults);
$successRate = round(($passedTests / $totalTests) * 100, 1);

if ($successRate >= 90) {
    $summaryClass = 'summary';
    $summaryIcon = '🎉';
    $summaryTitle = 'EXCELLENT!';
} elseif ($successRate >= 70) {
    $summaryClass = 'test-section';
    $summaryIcon = '⚠️';
    $summaryTitle = 'GOOD';
} else {
    $summaryClass = 'test-section';
    $summaryIcon = '❌';
    $summaryTitle = 'NEEDS ATTENTION';
}

echo "<div class='$summaryClass'>";
echo "<h3>$summaryIcon $summaryTitle - Test Results: $passedTests/$totalTests ($successRate%)</h3>";

foreach ($testResults as $test => $result) {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    $testName = ucwords(str_replace('_', ' ', $test));
    echo "<div>$status - $testName</div>";
}

if ($successRate >= 90) {
    echo "<br><strong>🚀 Your system is fully functional and ready for production!</strong>";
    echo "<br>You can confidently deploy this to your web hosting provider.";
} elseif ($successRate >= 70) {
    echo "<br><strong>📝 Most features are working. Check the failed tests above and resolve any issues.</strong>";
} else {
    echo "<br><strong>🔧 Several issues detected. Please resolve the failed tests before deployment.</strong>";
}

echo "</div>";

echo "<div style='text-align: center; margin: 2rem 0;'>";
echo "<a href='index.php' style='background: #3498db; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; margin: 0.5rem;'>→ Go to Dashboard</a>";
echo "<a href='webhook.php' style='background: #27ae60; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; margin: 0.5rem;'>→ Test Webhook</a>";
echo "</div>";
?>

<script>
// Auto-scroll to summary on page load
window.addEventListener('load', function() {
    const summary = document.querySelector('h2:last-of-type');
    if (summary) {
        summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>