<?php
require_once 'includes/config.php';
require_once 'includes/TodoManager.php';
require_once 'includes/MultiChannelSync.php';

// Initialize configuration
Config::load();

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    try {
        $todoManager = new TodoManager();
        
        switch ($_GET['api']) {
            case 'stats':
                echo json_encode($todoManager->getStats());
                break;
                
            case 'todos':
                $status = $_GET['status'] ?? null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                echo json_encode($todoManager->getTodos($status, null, $limit));
                break;
                
            case 'sync':
                $sync = new MultiChannelSync();
                $result = $sync->syncAllChannels();
                echo json_encode(['success' => $result]);
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit;
}

// Get stats for display
try {
    $todoManager = new TodoManager();
    $stats = $todoManager->getStats();
    $recentTodos = $todoManager->getTodos('pending', null, 5);
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'completion_rate' => 0];
    $recentTodos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Management System - PHP Edition</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .todo-item {
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .todo-item:last-child {
            margin-bottom: 0;
        }
        
        .todo-id {
            font-weight: bold;
            color: #3498db;
        }
        
        .todo-source {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 10px;
            color: white;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .cta-section {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }
        
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .sync-btn {
            background: #27ae60;
            border: none;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
        }
        
        .sync-btn:hover {
            background: #229954;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Todo Management System</h1>
            <p><strong>PHP Edition</strong> - Easy to host, powerful automation across WhatsApp, Email, and Google Sheets</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Todos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="stat-label">Success Rate</div>
            </div>
        </div>
        
        <div class="main-grid">
            <div class="card">
                <h2>📋 Recent Todos</h2>
                <?php if (!empty($recentTodos)): ?>
                    <?php foreach ($recentTodos as $todo): ?>
                        <div class="todo-item">
                            <span class="todo-id">#<?php echo $todo['id']; ?></span>
                            <?php echo htmlspecialchars($todo['description']); ?>
                            <div class="todo-source">
                                📱 From: <?php echo ucfirst($todo['source']); ?> 
                                | ⏰ <?php echo date('M j, Y', strtotime($todo['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>🎉 No pending todos! You're all caught up!</p>
                <?php endif; ?>
                
                <button class="sync-btn" onclick="syncChannels()">🔄 Sync All Channels</button>
            </div>
            
            <div class="card">
                <h2>📊 System Status</h2>
                <div style="margin-bottom: 1rem;">
                    <strong>Database:</strong> 
                    <span style="color: #27ae60;">✅ Connected</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>WhatsApp:</strong> 
                    <span style="color: <?php echo !empty(Config::get('twilio_sid')) ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo !empty(Config::get('twilio_sid')) ? '✅ Configured' : '❌ Not configured'; ?>
                    </span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Email:</strong> 
                    <span style="color: <?php echo !empty(Config::get('gmail_user')) ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo !empty(Config::get('gmail_user')) ? '✅ Configured' : '❌ Not configured'; ?>
                    </span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Google Sheets:</strong> 
                    <span style="color: #27ae60;">✅ CSV Ready</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>PHP Version:</strong> 
                    <span style="color: #27ae60;"><?php echo phpversion(); ?></span>
                </div>
                
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <strong>💡 Quick Setup:</strong><br>
                    1. Edit <code>.env</code> file with your API keys<br>
                    2. Upload to your web hosting<br>
                    3. Start managing todos via multiple channels!<br>
                </div>
            </div>
        </div>
        
        <div class="feature-grid">
            <div class="feature">
                <div class="feature-icon">📱</div>
                <h3>WhatsApp Integration</h3>
                <p>Send todos via WhatsApp messages with automatic parsing and responses</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📧</div>
                <h3>Email Processing</h3>
                <p>Automatically extract todos from emails with intelligent content parsing</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Google Sheets Sync</h3>
                <p>Manage todos in familiar spreadsheet format with cross-platform sync</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🌐</div>
                <h3>Easy Hosting</h3>
                <p>PHP-based system works on any standard web hosting provider (PHP 7.0+)</p>
            </div>
        </div>
        
        <div class="cta-section">
            <h2>🎯 Ready to Deploy?</h2>
            <p style="margin-bottom: 2rem; color: #666;">This is your complete todo management system. Upload to any PHP hosting provider!</p>
            
            <a href="test.php" class="download-btn">🧪 Test System</a>
            <a href="docs/" class="download-btn">📚 Documentation</a>
            <a href="https://github.com/hebbarp/todo-management" class="download-btn">🐙 GitHub</a>
        </div>
        
        <div style="text-align: center; color: white; opacity: 0.8; margin-top: 2rem;">
            <p>&copy; 2025 Todo Management System | PHP <?php echo phpversion(); ?> Edition</p>
            <p>Built for easy hosting and powerful automation</p>
        </div>
    </div>
    
    <script>
        async function syncChannels() {
            const btn = document.querySelector('.sync-btn');
            const originalText = btn.textContent;
            
            btn.textContent = '🔄 Syncing...';
            btn.disabled = true;
            
            try {
                const response = await fetch('?api=sync');
                const result = await response.json();
                
                if (result.success) {
                    btn.textContent = '✅ Sync Complete!';
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    btn.textContent = '❌ Sync Failed';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }, 3000);
                }
            } catch (error) {
                btn.textContent = '❌ Error';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 3000);
            }
        }
        
        // Auto-refresh stats every 30 seconds
        setInterval(async () => {
            try {
                const response = await fetch('?api=stats');
                const stats = await response.json();
                
                document.querySelector('.stat-card:nth-child(1) .stat-number').textContent = stats.total;
                document.querySelector('.stat-card:nth-child(2) .stat-number').textContent = stats.pending;
                document.querySelector('.stat-card:nth-child(3) .stat-number').textContent = stats.completed;
                document.querySelector('.stat-card:nth-child(4) .stat-number').textContent = stats.completion_rate + '%';
            } catch (error) {
                console.log('Stats update failed:', error);
            }
        }, 30000);
    </script>
</body>
</html>