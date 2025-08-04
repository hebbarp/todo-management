<?php
/**
 * Workshop Registration Database Setup
 * Creates SQLite database for storing workshop registration details
 */

// Database configuration
define('DB_PATH', __DIR__ . '/workshop_registrations.db');

function createDatabase() {
    try {
        // Create SQLite database connection
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create registrations table
        $sql = "
            CREATE TABLE IF NOT EXISTS registrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT NOT NULL,
                company TEXT NOT NULL,
                position TEXT NOT NULL,
                experience TEXT NOT NULL,
                inquiry_type TEXT NOT NULL DEFAULT 'workshop',
                registration_date TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $pdo->exec($sql);
        
        // Add inquiry_type column if it doesn't exist (for existing databases)
        try {
            $pdo->exec("ALTER TABLE registrations ADD COLUMN inquiry_type TEXT NOT NULL DEFAULT 'workshop'");
        } catch (PDOException $e) {
            // Column already exists, ignore error
        }
        
        // Create indexes for better performance
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_email ON registrations(email)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_registration_date ON registrations(registration_date)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON registrations(created_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_inquiry_type ON registrations(inquiry_type)");
        
        echo "✅ Database created successfully: " . realpath(DB_PATH) . "\n";
        echo "📋 Table: registrations\n";
        echo "🔍 Indexes created on: email, registration_date, created_at\n";
        
        return $pdo;
        
    } catch (PDOException $e) {
        echo "❌ Database creation failed: " . $e->getMessage() . "\n";
        return null;
    }
}

function insertSampleData($pdo) {
    try {
        // Sample registration data
        $sampleData = [
            [
                'John Smith',
                'john.smith@company.com',
                '+1-555-0101',
                'TechCorp Inc',
                'CEO',
                'Intermediate',
                'workshop',
                date('c')
            ],
            [
                'Jane Doe',
                'jane.doe@startup.com',
                '+1-555-0102',
                'AI Startup',
                'CTO',
                'Advanced',
                'talktous',
                date('c')
            ],
            [
                'Robert Johnson',
                'robert.j@enterprise.com',
                '+1-555-0103',
                'Enterprise Solutions',
                'VP',
                'Beginner',
                'workshop',
                date('c')
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO registrations (full_name, email, phone, company, position, experience, inquiry_type, registration_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertedCount = 0;
        foreach ($sampleData as $data) {
            try {
                $stmt->execute($data);
                $insertedCount++;
            } catch (PDOException $e) {
                // Skip duplicates
                if ($e->getCode() != 23000) { // Not a constraint violation
                    echo "⚠️  Error inserting sample data: " . $e->getMessage() . "\n";
                }
            }
        }
        
        if ($insertedCount > 0) {
            echo "✅ Sample data inserted: {$insertedCount} registrations\n";
        } else {
            echo "⚠️  Sample data already exists\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error with sample data: " . $e->getMessage() . "\n";
    }
}

function viewRegistrations($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, full_name, email, company, position, created_at
            FROM registrations 
            ORDER BY created_at DESC
        ");
        
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($registrations)) {
            echo "📝 No registrations found\n";
            return;
        }
        
        echo "\n📊 Current Registrations (" . count($registrations) . " total):\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($registrations as $reg) {
            $createdAt = date('Y-m-d H:i:s', strtotime($reg['created_at']));
            echo "ID: {$reg['id']} | {$reg['full_name']} | {$reg['email']} | {$reg['company']} | {$reg['position']} | {$createdAt}\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error viewing registrations: " . $e->getMessage() . "\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "🎯 Setting up Workshop Registration Database...\n\n";
    
    // Create database and table
    $pdo = createDatabase();
    
    if ($pdo) {
        // Insert sample data
        insertSampleData($pdo);
        
        // View current registrations
        viewRegistrations($pdo);
        
        echo "\n🚀 Database setup complete!\n";
        echo "💡 Use register.php to handle form submissions\n";
        echo "🌐 Use view_registrations.php to manage registrations\n";
    }
} else {
    // If accessed via web, provide database connection
    function getDbConnection() {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
}
?>