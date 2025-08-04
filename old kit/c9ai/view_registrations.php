<?php
/**
 * Workshop Registration Viewer and Manager
 * View and manage workshop registrations from SQLite database
 */

// Include database setup functions
require_once 'setup_db.php';

// Check if running from command line or web
$isCliMode = php_sapi_name() === 'cli';

// If accessed via web, redirect to secure admin panel
if (!$isCliMode) {
    header('Location: login.php');
    exit();
}

if (!$isCliMode) {
    // Web interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Workshop Registration Manager</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                color: white;
            }
            .container {
                background: rgba(255,255,255,0.95);
                padding: 30px;
                border-radius: 15px;
                color: #333;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                color: #667eea;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }
            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                margin-bottom: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background: #667eea;
                color: white;
                font-weight: bold;
            }
            tr:hover {
                background-color: #f5f5f5;
            }
            .actions {
                margin: 20px 0;
                text-align: center;
            }
            .btn {
                background: #667eea;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin: 0 10px;
                text-decoration: none;
                display: inline-block;
            }
            .btn:hover {
                background: #5a6fd8;
            }
            .search-box {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 2px solid #667eea;
                border-radius: 5px;
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎯 Workshop Registration Manager</h1>
                <p>Manage and view workshop registrations</p>
            </div>
    <?php
}

/**
 * Get database connection
 */
function getConnection() {
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/workshop_registrations.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        if (php_sapi_name() === 'cli') {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        } else {
            echo "<div style='color: red;'>❌ Database error: " . $e->getMessage() . "</div>";
        }
        return null;
    }
}

/**
 * Get registration statistics
 */
function getStats($pdo) {
    try {
        // Total registrations
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Recent registrations (last 7 days)
        $stmt = $pdo->query("
            SELECT COUNT(*) as recent 
            FROM registrations 
            WHERE date(created_at) >= date('now', '-7 days')
        ");
        $recent = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
        
        // Registrations by position
        $stmt = $pdo->query("
            SELECT position, COUNT(*) as count 
            FROM registrations 
            GROUP BY position 
            ORDER BY count DESC
        ");
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Registrations by experience
        $stmt = $pdo->query("
            SELECT experience, COUNT(*) as count 
            FROM registrations 
            GROUP BY experience 
            ORDER BY count DESC
        ");
        $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Registrations by inquiry type
        $stmt = $pdo->query("
            SELECT inquiry_type, COUNT(*) as count 
            FROM registrations 
            GROUP BY inquiry_type 
            ORDER BY count DESC
        ");
        $inquiryTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total' => $total,
            'recent' => $recent,
            'positions' => $positions,
            'experiences' => $experiences,
            'inquiryTypes' => $inquiryTypes
        ];
        
    } catch (PDOException $e) {
        error_log("Stats error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all registrations
 */
function getAllRegistrations($pdo, $search = '') {
    try {
        $sql = "
            SELECT id, full_name, email, phone, company, position, experience, 
                   inquiry_type, registration_date, created_at
            FROM registrations
        ";
        
        if (!empty($search)) {
            $sql .= " WHERE 
                full_name LIKE :search OR 
                email LIKE :search OR 
                company LIKE :search OR 
                position LIKE :search
            ";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        
        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%');
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Registrations query error: " . $e->getMessage());
        return [];
    }
}

/**
 * Export registrations to CSV
 */
function exportToCSV($pdo) {
    try {
        $registrations = getAllRegistrations($pdo);
        
        if (empty($registrations)) {
            return false;
        }
        
        $filename = 'workshop_registrations_' . date('Ymd_His') . '.csv';
        $filepath = __DIR__ . '/' . $filename;
        
        $fp = fopen($filepath, 'w');
        
        // Write header
        fputcsv($fp, [
            'ID', 'Full Name', 'Email', 'Phone', 'Company', 
            'Position', 'Experience', 'Inquiry Type', 'Registration Date', 'Created At'
        ]);
        
        // Write data
        foreach ($registrations as $reg) {
            fputcsv($fp, [
                $reg['id'],
                $reg['full_name'],
                $reg['email'],
                $reg['phone'],
                $reg['company'],
                $reg['position'],
                $reg['experience'],
                $reg['inquiry_type'],
                $reg['registration_date'],
                $reg['created_at']
            ]);
        }
        
        fclose($fp);
        
        return $filename;
        
    } catch (Exception $e) {
        error_log("CSV export error: " . $e->getMessage());
        return false;
    }
}

// Main execution
$pdo = getConnection();

if (!$pdo) {
    if (!$isCliMode) {
        echo "</div></body></html>";
    }
    exit(1);
}

// Handle actions
$action = $_GET['action'] ?? '';
$search = $_GET['search'] ?? '';

if ($action === 'export' && !$isCliMode) {
    $filename = exportToCSV($pdo);
    if ($filename) {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile(__DIR__ . '/' . $filename);
        unlink(__DIR__ . '/' . $filename); // Clean up
        exit();
    }
}

// Get data
$stats = getStats($pdo);
$registrations = getAllRegistrations($pdo, $search);

if ($isCliMode) {
    // CLI Mode Output
    echo "🎯 Workshop Registration Manager\n";
    echo "================================\n\n";
    
    if ($stats) {
        echo "📊 Statistics:\n";
        echo "Total Registrations: {$stats['total']}\n";
        echo "Recent (7 days): {$stats['recent']}\n\n";
        
        echo "👔 By Position:\n";
        foreach ($stats['positions'] as $pos) {
            echo "  {$pos['position']}: {$pos['count']}\n";
        }
        
        echo "\n🎯 By Experience:\n";
        foreach ($stats['experiences'] as $exp) {
            echo "  {$exp['experience']}: {$exp['count']}\n";
        }
        
        echo "\n📋 By Inquiry Type:\n";
        foreach ($stats['inquiryTypes'] as $type) {
            $typeDisplay = $type['inquiry_type'] === 'workshop' ? 'Workshop' : 'Talk to Us';
            echo "  {$typeDisplay}: {$type['count']}\n";
        }
        echo "\n";
    }
    
    echo "📋 Registrations (" . count($registrations) . " total):\n";
    echo str_repeat("-", 120) . "\n";
    echo sprintf("%-3s %-20s %-25s %-20s %-15s %-10s %-12s %-10s\n", 
        'ID', 'Name', 'Email', 'Company', 'Position', 'Type', 'Experience', 'Date');
    echo str_repeat("-", 120) . "\n";
    
    foreach ($registrations as $reg) {
        $date = date('Y-m-d', strtotime($reg['created_at']));
        $type = $reg['inquiry_type'] === 'workshop' ? 'Workshop' : 'TalkToUs';
        echo sprintf("%-3s %-20s %-25s %-20s %-15s %-10s %-12s %-10s\n",
            $reg['id'],
            substr($reg['full_name'], 0, 19),
            substr($reg['email'], 0, 24),
            substr($reg['company'], 0, 19),
            substr($reg['position'], 0, 14),
            $type,
            $reg['experience'],
            $date
        );
    }
    
} else {
    // Web Mode Output
    if ($stats) {
        echo '<div class="stats">';
        echo '<div class="stat-card"><div class="stat-number">' . $stats['total'] . '</div><div>Total Registrations</div></div>';
        echo '<div class="stat-card"><div class="stat-number">' . $stats['recent'] . '</div><div>Recent (7 days)</div></div>';
        
        // Inquiry type breakdown
        $workshopCount = 0;
        $talktoUsCount = 0;
        foreach ($stats['inquiryTypes'] as $type) {
            if ($type['inquiry_type'] === 'workshop') $workshopCount = $type['count'];
            if ($type['inquiry_type'] === 'talktous') $talktoUsCount = $type['count'];
        }
        echo '<div class="stat-card"><div class="stat-number">' . $workshopCount . '</div><div>Workshop Registrations</div></div>';
        echo '<div class="stat-card"><div class="stat-number">' . $talktoUsCount . '</div><div>Talk to Us Inquiries</div></div>';
        echo '</div>';
    }
    
    echo '<div class="actions">';
    echo '<form method="GET" style="margin-bottom: 20px;">';
    echo '<input type="text" name="search" placeholder="Search registrations..." class="search-box" value="' . htmlspecialchars($search) . '">';
    echo '<button type="submit" class="btn">🔍 Search</button>';
    echo '</form>';
    echo '<a href="?action=export" class="btn">📥 Export CSV</a>';
    echo '<a href="?" class="btn">🔄 Refresh</a>';
    echo '</div>';
    
    if (!empty($registrations)) {
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Company</th><th>Position</th><th>Type</th><th>Experience</th><th>Date</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($registrations as $reg) {
            $date = date('M j, Y', strtotime($reg['created_at']));
            $typeDisplay = $reg['inquiry_type'] === 'workshop' ? '🎯 Workshop' : '💬 Talk to Us';
            echo '<tr>';
            echo '<td>' . $reg['id'] . '</td>';
            echo '<td>' . htmlspecialchars($reg['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($reg['email']) . '</td>';
            echo '<td>' . htmlspecialchars($reg['company']) . '</td>';
            echo '<td>' . htmlspecialchars($reg['position']) . '</td>';
            echo '<td>' . $typeDisplay . '</td>';
            echo '<td>' . htmlspecialchars($reg['experience']) . '</td>';
            echo '<td>' . $date . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p style="text-align: center; margin: 40px 0;">📝 No registrations found</p>';
    }
    
    echo '</div></body></html>';
}
?>