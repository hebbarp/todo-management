<?php
/**
 * Server Environment Debug Script
 * Use this to check what's different on your Digital Ocean server
 */

header('Content-Type: application/json');

// Basic environment info
$debug = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'current_dir' => __DIR__,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
];

// Check file permissions
$files_to_check = [
    'workshop_registrations.db',
    'registration.log',
    'setup_db.php',
    'register.php'
];

$debug['file_permissions'] = [];
foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    $debug['file_permissions'][$file] = [
        'exists' => file_exists($filepath),
        'readable' => is_readable($filepath),
        'writable' => is_writable($filepath),
        'permissions' => file_exists($filepath) ? substr(sprintf('%o', fileperms($filepath)), -4) : 'N/A'
    ];
}

// Check directory permissions
$debug['directory_permissions'] = [
    'current_dir' => [
        'readable' => is_readable(__DIR__),
        'writable' => is_writable(__DIR__),
        'permissions' => substr(sprintf('%o', fileperms(__DIR__)), -4)
    ]
];

// Check PHP extensions
$required_extensions = ['sqlite3', 'pdo', 'pdo_sqlite', 'json'];
$debug['php_extensions'] = [];
foreach ($required_extensions as $ext) {
    $debug['php_extensions'][$ext] = extension_loaded($ext);
}

// Test database connection
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/workshop_registrations.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test if registrations table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='registrations'");
    $table_exists = $stmt->fetch() !== false;
    
    $debug['database'] = [
        'connection' => 'success',
        'registrations_table_exists' => $table_exists
    ];
    
    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $debug['database']['record_count'] = $count;
    }
    
} catch (Exception $e) {
    $debug['database'] = [
        'connection' => 'failed',
        'error' => $e->getMessage()
    ];
}

// If this is a POST request, also debug the request data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug['post_debug'] = [
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'input_data' => file_get_contents('php://input'),
        'post_data' => $_POST,
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set'
    ];
}

// Check error logs
$error_log_paths = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    '/var/log/php_errors.log',
    ini_get('error_log')
];

$debug['error_logs'] = [];
foreach ($error_log_paths as $log_path) {
    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        $debug['error_logs'][$log_path] = 'accessible';
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>