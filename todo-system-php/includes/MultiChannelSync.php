<?php
require_once 'config.php';
require_once 'TodoManager.php';
require_once 'WhatsAppIntegration.php';
require_once 'EmailIntegration.php';
require_once 'GoogleSheetsIntegration.php';

/**
 * Multi-Channel Todo Synchronization
 * Coordinates todos across WhatsApp, Email, Google Sheets, and GitHub Issues
 */
class MultiChannelSync {
    private $todoManager;
    private $whatsAppIntegration;
    private $emailIntegration;
    private $sheetsIntegration;
    private $syncLogFile;
    
    public function __construct() {
        $this->todoManager = new TodoManager();
        $this->whatsAppIntegration = new WhatsAppIntegration();
        $this->emailIntegration = new EmailIntegration();
        $this->sheetsIntegration = new GoogleSheetsIntegration();
        $this->syncLogFile = Config::get('data_dir') . 'sync_log.json';
    }
    
    /**
     * Perform complete multi-channel synchronization
     */
    public function syncAllChannels() {
        Config::log("Starting multi-channel synchronization");
        
        $syncTimestamp = date('c');
        $syncResults = [
            'timestamp' => $syncTimestamp,
            'whatsapp_processed' => 0,
            'emails_processed' => 0,
            'sheets_synced' => 0,
            'github_created' => 0,
            'errors' => []
        ];
        
        try {
            // 1. Process emails for new todos
            Config::log("Processing emails...");
            $emailSuccess = $this->emailIntegration->processEmails();
            if ($emailSuccess) {
                $emailTodos = $this->todoManager->getTodos(null, 'email', 10);
                $syncResults['emails_processed'] = count($emailTodos);
            } else {
                $syncResults['errors'][] = "Email processing failed";
            }
            
            // 2. Sync with Google Sheets
            Config::log("Syncing Google Sheets...");
            try {
                $sheetsSynced = $this->sheetsIntegration->syncFromOtherSources(10);
                $syncResults['sheets_synced'] = $sheetsSynced;
            } catch (Exception $e) {
                $syncResults['errors'][] = "Sheets sync error: " . $e->getMessage();
            }
            
            // 3. Create GitHub issues for high-priority todos
            Config::log("Creating GitHub issues...");
            try {
                $githubCreated = $this->createGitHubIssuesFromChannels();
                $syncResults['github_created'] = $githubCreated;
            } catch (Exception $e) {
                $syncResults['errors'][] = "GitHub sync error: " . $e->getMessage();
            }
            
            // 4. Generate unified report
            Config::log("Generating unified report...");
            $reportFile = $this->generateUnifiedReport();
            
            // 5. Create emergency backup
            $backupFile = $this->createEmergencyBackup();
            
            // Update sync log
            $this->updateSyncLog($syncResults);
            
            // Print summary
            $this->printSyncSummary($syncResults, $reportFile, $backupFile);
            
            Config::log("Multi-channel sync completed successfully");
            return true;
            
        } catch (Exception $e) {
            Config::log("Sync failed: " . $e->getMessage(), 'ERROR');
            $syncResults['errors'][] = "General sync error: " . $e->getMessage();
            $this->updateSyncLog($syncResults);
            return false;
        }
    }
    
    /**
     * Create GitHub issues from high-priority channel todos
     */
    private function createGitHubIssuesFromChannels() {
        $priorityTodos = [];
        $createdCount = 0;
        
        // Get recent todos from all channels
        $recentTodos = $this->todoManager->getTodos('pending', null, 5);
        
        foreach ($recentTodos as $todo) {
            if ($todo['source'] !== 'manual') {
                $priorityTodos[] = [
                    'title' => "[{$todo['source']}] {$todo['description']}",
                    'body' => $this->formatGitHubIssueBody($todo),
                    'source' => $todo['source']
                ];
            }
        }
        
        // Create GitHub issues
        foreach ($priorityTodos as $todo) {
            try {
                $command = sprintf(
                    'gh issue create --repo hebbarp/todo-management --title %s --body %s --label %s 2>/dev/null',
                    escapeshellarg($todo['title']),
                    escapeshellarg($todo['body']),
                    escapeshellarg("source:{$todo['source']},status:pending")
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0) {
                    Config::log("Created GitHub issue: {$todo['title']}");
                    $createdCount++;
                } else {
                    Config::log("Could not create GitHub issue (gh CLI may not be available)", 'DEBUG');
                    break; // Don't try to create more if gh CLI is not available
                }
                
            } catch (Exception $e) {
                Config::log("Error creating GitHub issue: " . $e->getMessage(), 'DEBUG');
                break;
            }
        }
        
        return $createdCount;
    }
    
    /**
     * Format GitHub issue body
     */
    private function formatGitHubIssueBody($todo) {
        $body = "Created via {$todo['source']} integration\n";
        $body .= "Created: {$todo['created_at']}\n";
        $body .= "Priority: {$todo['priority']}\n\n";
        
        if (!empty($todo['source_data'])) {
            $sourceData = $todo['source_data'];
            
            if (isset($sourceData['phone_number'])) {
                $body .= "WhatsApp: {$sourceData['phone_number']}\n";
            }
            
            if (isset($sourceData['sender_email'])) {
                $body .= "Email: {$sourceData['sender_email']}\n";
            }
            
            if (isset($sourceData['email_subject'])) {
                $body .= "Subject: {$sourceData['email_subject']}\n";
            }
            
            if (isset($sourceData['notes'])) {
                $body .= "Notes: {$sourceData['notes']}\n";
            }
        }
        
        return $body;
    }
    
    /**
     * Generate unified report across all channels
     */
    private function generateUnifiedReport() {
        $reportTimestamp = date('Y-m-d_H-i-s');
        $reportFile = Config::get('backups_dir') . "unified_todo_report_$reportTimestamp.json";
        
        try {
            // Collect data from all sources
            $allTodos = $this->todoManager->getTodos();
            $whatsappTodos = $this->todoManager->getTodos(null, 'whatsapp');
            $emailTodos = $this->todoManager->getTodos(null, 'email');
            $sheetsTodos = $this->sheetsIntegration->getTodos();
            $stats = $this->todoManager->getStats();
            
            $unifiedReport = [
                'generated_at' => date('c'),
                'summary' => [
                    'total_todos' => count($allTodos),
                    'whatsapp_count' => count($whatsappTodos),
                    'email_count' => count($emailTodos),
                    'sheets_count' => count($sheetsTodos),
                    'pending_count' => $stats['pending'],
                    'completed_count' => $stats['completed'],
                    'completion_rate' => $stats['completion_rate']
                ],
                'todos' => [
                    'whatsapp' => array_slice($whatsappTodos, -10), // Last 10
                    'email' => array_slice($emailTodos, -10),       // Last 10
                    'sheets' => array_slice($sheetsTodos, -10)      // Last 10
                ],
                'sync_status' => $this->getSyncLog()
            ];
            
            // Save report
            file_put_contents($reportFile, json_encode($unifiedReport, JSON_PRETTY_PRINT));
            
            // Generate human-readable summary
            $this->generateHumanReadableSummary($unifiedReport, $reportTimestamp);
            
            Config::log("Unified report saved: $reportFile");
            return $reportFile;
            
        } catch (Exception $e) {
            Config::log("Error generating unified report: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Generate human-readable summary
     */
    private function generateHumanReadableSummary($report, $timestamp) {
        $summaryFile = Config::get('backups_dir') . "unified_summary_$timestamp.txt";
        
        $summary = "📋 UNIFIED TODO REPORT\n";
        $summary .= "================================\n";
        $summary .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $summary .= "📊 Summary:\n";
        $summary .= "• Total todos across all channels: {$report['summary']['total_todos']}\n";
        $summary .= "• WhatsApp todos: {$report['summary']['whatsapp_count']}\n";
        $summary .= "• Email todos: {$report['summary']['email_count']}\n";
        $summary .= "• Google Sheets todos: {$report['summary']['sheets_count']}\n";
        $summary .= "• Pending: {$report['summary']['pending_count']}\n";
        $summary .= "• Completed: {$report['summary']['completed_count']}\n";
        $summary .= "• Completion Rate: {$report['summary']['completion_rate']}%\n\n";
        
        $syncLog = $this->getSyncLog();
        $lastSync = $syncLog['last_sync'] ?? 'Never';
        $summary .= "🔄 Last sync: $lastSync\n\n";
        
        $summary .= "📱 Recent WhatsApp Todos:\n";
        foreach ($report['todos']['whatsapp'] as $todo) {
            $statusEmoji = $todo['status'] === 'completed' ? '✅' : '⏳';
            $summary .= "   $statusEmoji #{$todo['id']}: {$todo['description']}\n";
        }
        
        $summary .= "\n📧 Recent Email Todos:\n";
        foreach ($report['todos']['email'] as $todo) {
            $statusEmoji = $todo['status'] === 'completed' ? '✅' : '⏳';
            $summary .= "   $statusEmoji #{$todo['id']}: {$todo['description']}\n";
        }
        
        file_put_contents($summaryFile, $summary);
        echo $summary;
    }
    
    /**
     * Create emergency backup of all todos
     */
    private function createEmergencyBackup() {
        $backupTimestamp = date('Y-m-d_H-i-s');
        $backupFile = Config::get('backups_dir') . "emergency_backup_$backupTimestamp.json";
        
        try {
            $backupData = [
                'backup_created' => date('c'),
                'database_todos' => $this->todoManager->getTodos(),
                'database_stats' => $this->todoManager->getStats(),
                'sheets_data' => $this->sheetsIntegration->getTodos(),
                'sheets_stats' => $this->sheetsIntegration->getStats(),
                'sync_log' => $this->getSyncLog()
            ];
            
            file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
            
            Config::log("Emergency backup created: $backupFile");
            return $backupFile;
            
        } catch (Exception $e) {
            Config::log("Backup failed: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Update sync log
     */
    private function updateSyncLog($syncResults) {
        try {
            $syncLog = $this->getSyncLog();
            
            $syncLog['last_sync'] = $syncResults['timestamp'];
            
            // Add to history
            if (!isset($syncLog['sync_history'])) {
                $syncLog['sync_history'] = [];
            }
            
            $syncLog['sync_history'][] = $syncResults;
            
            // Keep only last 10 sync records
            if (count($syncLog['sync_history']) > 10) {
                $syncLog['sync_history'] = array_slice($syncLog['sync_history'], -10);
            }
            
            file_put_contents($this->syncLogFile, json_encode($syncLog, JSON_PRETTY_PRINT));
            
        } catch (Exception $e) {
            Config::log("Error updating sync log: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Get sync log
     */
    private function getSyncLog() {
        if (file_exists($this->syncLogFile)) {
            $content = file_get_contents($this->syncLogFile);
            return json_decode($content, true) ?? ['last_sync' => null, 'sync_history' => []];
        }
        
        return ['last_sync' => null, 'sync_history' => []];
    }
    
    /**
     * Print sync summary
     */
    private function printSyncSummary($syncResults, $reportFile, $backupFile) {
        echo "\n✅ Multi-channel sync completed!\n";
        echo "📊 Summary:\n";
        echo "   • Email todos: {$syncResults['emails_processed']}\n";
        echo "   • Sheets synced: {$syncResults['sheets_synced']}\n";
        echo "   • GitHub issues: {$syncResults['github_created']}\n";
        
        if (!empty($syncResults['errors'])) {
            echo "⚠️  Errors encountered: " . count($syncResults['errors']) . "\n";
            foreach ($syncResults['errors'] as $error) {
                echo "     • $error\n";
            }
        }
        
        echo "\n📊 Generated files:\n";
        if ($reportFile) {
            echo "   • Unified report: " . basename($reportFile) . "\n";
        }
        if ($backupFile) {
            echo "   • Emergency backup: " . basename($backupFile) . "\n";
        }
        
        echo "\n🎉 Sync process complete!\n\n";
    }
    
    /**
     * Send daily digest email
     */
    public function sendDailyDigest($recipientEmail) {
        try {
            $stats = $this->todoManager->getStats();
            $pendingTodos = $this->todoManager->getTodos('pending', null, 5);
            $completedToday = $this->getTodosCompletedToday();
            
            $digestSubject = "Daily Todo Digest - " . date('F d, Y');
            $digestBody = "📊 Your Daily Todo Digest\n\n";
            $digestBody .= "🔄 Multi-Channel Summary:\n";
            $digestBody .= "• Total active todos: {$stats['total']}\n";
            $digestBody .= "• Pending: {$stats['pending']}\n";
            $digestBody .= "• Completed: {$stats['completed']}\n";
            $digestBody .= "• Completion rate: {$stats['completion_rate']}%\n\n";
            
            $digestBody .= "📋 Recent Pending Tasks:\n";
            foreach ($pendingTodos as $todo) {
                $digestBody .= "   • {$todo['description']} (from {$todo['source']})\n";
            }
            
            $digestBody .= "\n✅ Completed Today (" . count($completedToday) . "):\n";
            foreach ($completedToday as $todo) {
                $digestBody .= "   • {$todo['description']}\n";
            }
            
            if (empty($completedToday)) {
                $digestBody .= "   • No todos completed today\n";
            }
            
            $syncLog = $this->getSyncLog();
            $lastSync = $syncLog['last_sync'] ?? 'Never';
            $digestBody .= "\n🔄 Last sync: $lastSync\n";
            $digestBody .= "\n💡 Reply to this email to add new todos to your list!";
            
            $success = $this->emailIntegration->sendStatusEmail(
                $recipientEmail,
                $digestSubject,
                $digestBody
            );
            
            return $success;
            
        } catch (Exception $e) {
            Config::log("Error sending daily digest: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Get todos completed today
     */
    private function getTodosCompletedToday() {
        try {
            $pdo = Config::getDatabaseConnection();
            $sql = "SELECT * FROM todos WHERE status = 'completed' AND DATE(completed_at) = DATE('now') ORDER BY completed_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($todos as &$todo) {
                $todo['source_data'] = json_decode($todo['source_data'], true);
            }
            
            return $todos;
        } catch (Exception $e) {
            Config::log("Error fetching today's completed todos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup($daysOld = 30) {
        $cleanupResults = [
            'todos_deleted' => 0,
            'emails_deleted' => 0,
            'logs_rotated' => false
        ];
        
        try {
            // Cleanup old completed todos
            $cleanupResults['todos_deleted'] = $this->todoManager->cleanup($daysOld);
            
            // Cleanup old processed emails
            $pdo = Config::getDatabaseConnection();
            $sql = "DELETE FROM processed_emails WHERE processed_at < DATE('now', '-$daysOld days')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cleanupResults['emails_deleted'] = $stmt->rowCount();
            
            // Rotate log files if they're too large
            $logFile = Config::get('logs_dir') . 'system.log';
            if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
                $archiveFile = Config::get('logs_dir') . 'system_' . date('Y-m-d') . '.log';
                rename($logFile, $archiveFile);
                $cleanupResults['logs_rotated'] = true;
            }
            
            Config::log("Cleanup completed: " . json_encode($cleanupResults));
            return $cleanupResults;
            
        } catch (Exception $e) {
            Config::log("Error during cleanup: " . $e->getMessage(), 'ERROR');
            return $cleanupResults;
        }
    }
}
?>