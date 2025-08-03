<?php
require_once 'config.php';
require_once 'TodoManager.php';

/**
 * Google Sheets Integration for Todo Management
 * Syncs todos with Google Sheets (CSV-based for simplicity)
 */
class GoogleSheetsIntegration {
    private $todoManager;
    private $csvFile;
    
    public function __construct($csvFile = null) {
        $this->todoManager = new TodoManager();
        $this->csvFile = $csvFile ?? Config::get('data_dir') . 'google_sheets_todos.csv';
        $this->initializeCSV();
    }
    
    /**
     * Initialize CSV file with headers
     */
    private function initializeCSV() {
        if (!file_exists($this->csvFile)) {
            $headers = ['ID', 'Todo Item', 'Status', 'Date Added', 'Due Date', 'Priority', 'Notes', 'Source'];
            
            $fp = fopen($this->csvFile, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
            
            Config::log("Initialized Google Sheets CSV file: {$this->csvFile}");
        }
    }
    
    /**
     * Add todo to Google Sheets (CSV)
     */
    public function addTodo($description, $dueDate = '', $priority = 'Medium', $notes = '', $source = 'sheets') {
        $todoId = $this->getNextId();
        $dateAdded = date('Y-m-d');
        
        $row = [
            $todoId,
            $description,
            'Pending',
            $dateAdded,
            $dueDate,
            $priority,
            $notes,
            $source
        ];
        
        $fp = fopen($this->csvFile, 'a');
        fputcsv($fp, $row);
        fclose($fp);
        
        // Also add to main database
        $sourceData = [
            'due_date' => $dueDate,
            'notes' => $notes,
            'channel' => 'sheets'
        ];
        
        $dbTodoId = $this->todoManager->addTodo($description, 'sheets', strtolower($priority), $sourceData);
        
        Config::log("Added todo to Google Sheets: #$todoId - $description");
        return $todoId;
    }
    
    /**
     * Get next available ID from CSV
     */
    private function getNextId() {
        if (!file_exists($this->csvFile)) {
            return 1;
        }
        
        $lines = file($this->csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (count($lines) <= 1) { // Only header or empty
            return 1;
        }
        
        // Get the last line and extract ID
        $lastLine = end($lines);
        $data = str_getcsv($lastLine);
        
        if (!empty($data[0]) && is_numeric($data[0])) {
            return intval($data[0]) + 1;
        }
        
        return 1;
    }
    
    /**
     * Update todo status in CSV
     */
    public function updateTodoStatus($todoId, $newStatus) {
        if (!file_exists($this->csvFile)) {
            return false;
        }
        
        $lines = file($this->csvFile, FILE_IGNORE_NEW_LINES);
        $updated = false;
        
        foreach ($lines as $index => $line) {
            if ($index === 0) continue; // Skip header
            
            $data = str_getcsv($line);
            if (!empty($data[0]) && $data[0] == $todoId) {
                $data[2] = $newStatus; // Update status column
                $lines[$index] = $this->arrayToCsv($data);
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents($this->csvFile, implode("\n", $lines) . "\n");
            Config::log("Updated todo #$todoId status to: $newStatus");
            
            // Also update in main database
            if (strtolower($newStatus) === 'completed') {
                $this->todoManager->completeTodo($todoId);
            }
            
            return true;
        }
        
        Config::log("Todo #$todoId not found in Google Sheets", 'WARNING');
        return false;
    }
    
    /**
     * Complete a todo
     */
    public function completeTodo($todoId) {
        return $this->updateTodoStatus($todoId, 'Completed');
    }
    
    /**
     * Get todos from CSV with optional status filter
     */
    public function getTodos($statusFilter = null) {
        if (!file_exists($this->csvFile)) {
            return [];
        }
        
        $todos = [];
        $lines = file($this->csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $index => $line) {
            if ($index === 0) continue; // Skip header
            
            $data = str_getcsv($line);
            if (count($data) >= 6) {
                $todo = [
                    'id' => $data[0],
                    'description' => $data[1],
                    'status' => $data[2],
                    'date_added' => $data[3],
                    'due_date' => $data[4] ?? '',
                    'priority' => $data[5] ?? 'Medium',
                    'notes' => $data[6] ?? '',
                    'source' => $data[7] ?? 'sheets'
                ];
                
                if ($statusFilter === null || strcasecmp($todo['status'], $statusFilter) === 0) {
                    $todos[] = $todo;
                }
            }
        }
        
        return $todos;
    }
    
    /**
     * Sync from other sources (WhatsApp, Email, etc.)
     */
    public function syncFromOtherSources($limit = 10) {
        $syncedCount = 0;
        
        // Get recent todos from database that aren't from sheets
        $recentTodos = $this->todoManager->getTodos('pending', null, $limit);
        
        foreach ($recentTodos as $todo) {
            if ($todo['source'] !== 'sheets') {
                // Check if already in CSV
                if (!$this->todoExistsInCSV($todo['description'])) {
                    $notes = "From {$todo['source']}";
                    if (!empty($todo['source_data'])) {
                        $sourceData = $todo['source_data'];
                        if (isset($sourceData['phone_number'])) {
                            $notes .= " ({$sourceData['phone_number']})";
                        } elseif (isset($sourceData['sender_email'])) {
                            $notes .= " ({$sourceData['sender_email']})";
                        }
                    }
                    
                    $this->addTodoToCSVOnly($todo['description'], $todo['priority'], $notes, $todo['source']);
                    $syncedCount++;
                }
            }
        }
        
        Config::log("Synced $syncedCount todos to Google Sheets");
        return $syncedCount;
    }
    
    /**
     * Check if todo exists in CSV (to avoid duplicates)
     */
    private function todoExistsInCSV($description) {
        $todos = $this->getTodos();
        foreach ($todos as $todo) {
            if (strcasecmp($todo['description'], $description) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Add todo to CSV only (for syncing)
     */
    private function addTodoToCSVOnly($description, $priority, $notes, $source) {
        $todoId = $this->getNextId();
        $dateAdded = date('Y-m-d');
        
        $row = [
            $todoId,
            $description,
            'Pending',
            $dateAdded,
            '', // due_date
            ucfirst($priority),
            $notes,
            $source
        ];
        
        $fp = fopen($this->csvFile, 'a');
        fputcsv($fp, $row);
        fclose($fp);
    }
    
    /**
     * Export todos to JSON
     */
    public function exportToJson($filename = null) {
        if ($filename === null) {
            $filename = 'sheets_todos_export_' . date('Y-m-d_H-i-s') . '.json';
        }
        
        $todos = $this->getTodos();
        $stats = $this->getStats();
        
        $export = [
            'exported_at' => date('c'),
            'source' => 'google_sheets_csv',
            'stats' => $stats,
            'todos' => $todos
        ];
        
        $filepath = Config::get('backups_dir') . $filename;
        file_put_contents($filepath, json_encode($export, JSON_PRETTY_PRINT));
        
        Config::log("Exported Google Sheets todos to $filename");
        return $filepath;
    }
    
    /**
     * Generate summary statistics
     */
    public function getStats() {
        $todos = $this->getTodos();
        $pending = $this->getTodos('pending');
        $completed = $this->getTodos('completed');
        
        $priorityCount = [];
        foreach ($todos as $todo) {
            $priority = $todo['priority'];
            $priorityCount[$priority] = ($priorityCount[$priority] ?? 0) + 1;
        }
        
        return [
            'total' => count($todos),
            'pending' => count($pending),
            'completed' => count($completed),
            'completion_rate' => count($todos) > 0 ? round((count($completed) / count($todos)) * 100, 1) : 0,
            'priority_breakdown' => $priorityCount
        ];
    }
    
    /**
     * Generate summary report
     */
    public function generateSummaryReport() {
        $stats = $this->getStats();
        $pendingTodos = $this->getTodos('pending');
        
        $report = "📊 GOOGLE SHEETS TODO SUMMARY REPORT\n";
        $report .= "================================\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $report .= "📋 Overview:\n";
        $report .= "• Total Todos: {$stats['total']}\n";
        $report .= "• Pending: {$stats['pending']}\n";
        $report .= "• Completed: {$stats['completed']}\n";
        $report .= "• Completion Rate: {$stats['completion_rate']}% ({$stats['completed']}/{$stats['total']})\n\n";
        
        $report .= "🎯 Priority Breakdown:\n";
        foreach ($stats['priority_breakdown'] as $priority => $count) {
            $report .= "• $priority: $count\n";
        }
        
        $report .= "\n📅 Recent Pending Todos:\n";
        $recentPending = array_slice($pendingTodos, -5); // Last 5 pending
        foreach ($recentPending as $todo) {
            $report .= "• #{$todo['id']}: {$todo['description']}\n";
        }
        
        return $report;
    }
    
    /**
     * Generate mobile-friendly view
     */
    public function getMobileView() {
        $pendingTodos = $this->getTodos('pending');
        
        if (empty($pendingTodos)) {
            return "🎉 No pending todos! You're all caught up!";
        }
        
        $mobileView = "📱 YOUR TODOS (Mobile View)\n";
        $mobileView .= str_repeat("=", 30) . "\n\n";
        
        $displayTodos = array_slice($pendingTodos, 0, 10); // Show top 10
        
        foreach ($displayTodos as $todo) {
            $statusEmoji = $todo['status'] === 'Pending' ? '⏳' : '✅';
            $priorityEmoji = $this->getPriorityEmoji($todo['priority']);
            
            $mobileView .= "$priorityEmoji $statusEmoji #{$todo['id']}\n";
            $mobileView .= "   {$todo['description']}\n";
            
            if (!empty($todo['due_date'])) {
                $mobileView .= "   📅 Due: {$todo['due_date']}\n";
            }
            
            $mobileView .= "\n";
        }
        
        if (count($pendingTodos) > 10) {
            $remaining = count($pendingTodos) - 10;
            $mobileView .= "... and $remaining more todos\n";
        }
        
        return $mobileView;
    }
    
    /**
     * Get priority emoji
     */
    private function getPriorityEmoji($priority) {
        switch (strtolower($priority)) {
            case 'high': return '🔥';
            case 'medium': return '📋';
            case 'low': return '📝';
            default: return '📋';
        }
    }
    
    /**
     * Convert array to CSV string
     */
    private function arrayToCsv($array) {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $array);
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return rtrim($csv, "\n");
    }
    
    /**
     * Import todos from CSV file
     */
    public function importFromCSV($csvFilePath) {
        if (!file_exists($csvFilePath)) {
            Config::log("CSV file not found: $csvFilePath", 'ERROR');
            return false;
        }
        
        $importedCount = 0;
        $fp = fopen($csvFilePath, 'r');
        
        // Skip header
        fgetcsv($fp);
        
        while (($data = fgetcsv($fp)) !== false) {
            if (count($data) >= 2) {
                $description = $data[1];
                $priority = $data[5] ?? 'Medium';
                $notes = $data[6] ?? '';
                $dueDate = $data[4] ?? '';
                
                $this->addTodo($description, $dueDate, $priority, $notes, 'import');
                $importedCount++;
            }
        }
        
        fclose($fp);
        Config::log("Imported $importedCount todos from CSV");
        
        return $importedCount;
    }
}

/**
 * Real Google Sheets API Integration (for advanced users)
 * This is a placeholder for actual Google Sheets API implementation
 */
class RealGoogleSheetsIntegration {
    /* 
    To implement real Google Sheets API integration:
    
    1. Install Google API Client Library:
       composer require google/apiclient
    
    2. Set up Google Cloud Project and enable Sheets API
    
    3. Download credentials.json file
    
    4. Implement OAuth2 authentication
    
    5. Use Google_Service_Sheets to interact with sheets
    
    Example code structure:
    
    private function authenticateGoogleSheets() {
        $client = new Google_Client();
        $client->setApplicationName('Todo Management System');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        
        // Handle authentication flow
        return new Google_Service_Sheets($client);
    }
    
    private function readSheet($spreadsheetId, $range) {
        $service = $this->authenticateGoogleSheets();
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        return $response->getValues();
    }
    
    private function writeToSheet($spreadsheetId, $range, $values) {
        $service = $this->authenticateGoogleSheets();
        $body = new Google_Service_Sheets_ValueRange(['values' => $values]);
        $params = ['valueInputOption' => 'RAW'];
        
        return $service->spreadsheets_values->append(
            $spreadsheetId, $range, $body, $params
        );
    }
    */
}
?>