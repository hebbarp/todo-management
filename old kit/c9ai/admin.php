<?php
/**
 * Secure Admin Dashboard
 * Protected analytics and registration management interface
 */

require_once 'auth.php';
requireAuth(); // Protect this page

// Include database setup functions
require_once 'setup_db.php';

/**
 * Get database connection
 */
function getConnection() {
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/workshop_registrations.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
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

$currentUser = getCurrentUser();
$stats = getStats(getConnection());
$search = $_GET['search'] ?? '';
$registrations = getAllRegistrations(getConnection(), $search);

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: login.php');
    exit();
}

// Handle CSV export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $filename = exportToCSV(getConnection());
    if ($filename) {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile(__DIR__ . '/' . $filename);
        unlink(__DIR__ . '/' . $filename); // Clean up
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vibe Tasking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #333;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 600;
        }

        .actions {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .actions h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .search-export {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-export {
            background: #00b894;
        }

        .btn-export:hover {
            background: #00a085;
        }

        .data-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9ff;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .section-header h3 {
            color: #333;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9ff;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9ff;
        }

        .type-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .type-workshop {
            background: #e8f4fd;
            color: #0984e3;
        }

        .type-talktous {
            background: #e8f5e8;
            color: #00b894;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .security-footer {
            margin-top: 3rem;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            font-size: 0.9rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .search-export {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
            }

            .stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <div class="logo">🎯 VIBE TASKING - Admin Dashboard</div>
            <div class="user-info">
                <div class="user-details">
                    <div><strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></div>
                    <div>Logged in: <?php echo date('M j, H:i', $currentUser['login_time']); ?></div>
                </div>
                <a href="?logout=1" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1 class="dashboard-title">📊 Registration Analytics & Management</h1>

        <?php if ($stats): ?>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent']; ?></div>
                <div class="stat-label">Recent (7 days)</div>
            </div>
            <?php
            $workshopCount = 0;
            $talktoUsCount = 0;
            foreach ($stats['inquiryTypes'] as $type) {
                if ($type['inquiry_type'] === 'workshop') $workshopCount = $type['count'];
                if ($type['inquiry_type'] === 'talktous') $talktoUsCount = $type['count'];
            }
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $workshopCount; ?></div>
                <div class="stat-label">Workshop Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $talktoUsCount; ?></div>
                <div class="stat-label">Talk to Us Inquiries</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="actions">
            <h3>🔍 Search & Export</h3>
            <div class="search-export">
                <form method="GET" style="flex: 1; display: flex; gap: 1rem;">
                    <input type="text" name="search" placeholder="Search by name, email, company, or position..." 
                           class="search-box" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
                <a href="?action=export" class="btn btn-export">📥 Export CSV</a>
            </div>
            <?php if ($search): ?>
                <p style="margin-top: 1rem; color: #666;">
                    Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    <a href="admin.php" style="margin-left: 1rem; color: #667eea;">Clear search</a>
                </p>
            <?php endif; ?>
        </div>

        <div class="data-section">
            <div class="section-header">
                <h3>📋 Registration Data (<?php echo count($registrations); ?> records)</h3>
            </div>

            <?php if (!empty($registrations)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Position</th>
                        <th>Type</th>
                        <th>Experience</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                    <tr>
                        <td><?php echo $reg['id']; ?></td>
                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo htmlspecialchars($reg['company']); ?></td>
                        <td><?php echo htmlspecialchars($reg['position']); ?></td>
                        <td>
                            <?php if ($reg['inquiry_type'] === 'workshop'): ?>
                                <span class="type-badge type-workshop">🎯 Workshop</span>
                            <?php else: ?>
                                <span class="type-badge type-talktous">💬 Talk to Us</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($reg['experience']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                <h3>No registrations found</h3>
                <p><?php echo $search ? 'Try adjusting your search terms.' : 'No registrations have been submitted yet.'; ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="security-footer">
            <div><strong>🔒 Secure Access</strong></div>
            <div>Session expires: <?php echo date('H:i:s', $currentUser['last_activity'] + SESSION_TIMEOUT); ?> | 
                 Last activity: <?php echo date('H:i:s', $currentUser['last_activity']); ?></div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; opacity: 0.7;">
                All activities are logged • Authorized personnel only • Data is confidential
            </div>
        </div>
    </div>
</body>
</html>