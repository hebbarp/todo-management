<?php
/**
 * Configuration file for Todo Management System
 * Load environment variables and set up database connections
 */

class Config {
    private static $config = null;
    
    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        // Load .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue; // Skip comments
                if (strpos($line, '=') === false) continue; // Skip invalid lines
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'"); // Remove quotes and whitespace
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$config = [
            // Twilio Configuration
            'twilio_sid' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
            'twilio_token' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
            'twilio_phone' => $_ENV['TWILIO_PHONE_NUMBER'] ?? '',
            
            // Gmail Configuration
            'gmail_user' => $_ENV['GMAIL_USER'] ?? '',
            'gmail_password' => $_ENV['GMAIL_APP_PASSWORD'] ?? '',
            
            // News API Configuration
            'news_api_key' => $_ENV['NEWS_API_KEY'] ?? '',
            
            // Database Configuration (SQLite for simplicity)
            'db_path' => __DIR__ . '/../data/todos.sqlite',
            
            // File paths
            'data_dir' => __DIR__ . '/../data/',
            'logs_dir' => __DIR__ . '/../logs/',
            'backups_dir' => __DIR__ . '/../backups/',
        ];
        
        // Ensure directories exist
        foreach (['data_dir', 'logs_dir', 'backups_dir'] as $dir_key) {
            $dir = self::$config[$dir_key];
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        $config = self::load();
        return $config[$key] ?? $default;
    }
    
    public static function getDatabaseConnection() {
        $dbPath = self::get('db_path');
        
        try {
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            self::initializeDatabase($pdo);
            
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    private static function initializeDatabase($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS todos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            description TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            priority TEXT DEFAULT 'medium',
            source TEXT DEFAULT 'manual',
            source_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL
        );
        
        CREATE TABLE IF NOT EXISTS sync_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sync_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            channel TEXT,
            action TEXT,
            details TEXT,
            success INTEGER DEFAULT 1
        );
        
        CREATE TABLE IF NOT EXISTS processed_emails (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email_id TEXT UNIQUE,
            processed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        $pdo->exec($sql);
    }
    
    public static function log($message, $level = 'INFO') {
        $logFile = self::get('logs_dir') . 'system.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>