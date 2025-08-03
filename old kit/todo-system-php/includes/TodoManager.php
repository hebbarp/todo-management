<?php
require_once 'config.php';

/**
 * Core Todo Management Class
 * Handles CRUD operations for todos across all channels
 */
class TodoManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Config::getDatabaseConnection();
    }
    
    /**
     * Add a new todo
     */
    public function addTodo($description, $source = 'manual', $priority = 'medium', $sourceData = null) {
        try {
            $sql = "INSERT INTO todos (description, source, priority, source_data) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$description, $source, $priority, json_encode($sourceData)]);
            
            $todoId = $this->pdo->lastInsertId();
            Config::log("Added todo #$todoId: $description (source: $source)");
            
            return $todoId;
        } catch (PDOException $e) {
            Config::log("Error adding todo: " . $e->getMessage(), 'ERROR');
            throw new Exception("Failed to add todo");
        }
    }
    
    /**
     * Get todos with optional filtering
     */
    public function getTodos($status = null, $source = null, $limit = null) {
        try {
            $sql = "SELECT * FROM todos WHERE 1=1";
            $params = [];
            
            if ($status !== null) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($source !== null) {
                $sql .= " AND source = ?";
                $params[] = $source;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse source_data JSON
            foreach ($todos as &$todo) {
                $todo['source_data'] = json_decode($todo['source_data'], true);
            }
            
            return $todos;
        } catch (PDOException $e) {
            Config::log("Error fetching todos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Complete a todo
     */
    public function completeTodo($todoId) {
        try {
            $sql = "UPDATE todos SET status = 'completed', completed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$todoId]);
            
            if ($stmt->rowCount() > 0) {
                Config::log("Completed todo #$todoId");
                return true;
            } else {
                Config::log("Todo #$todoId not found or already completed", 'WARNING');
                return false;
            }
        } catch (PDOException $e) {
            Config::log("Error completing todo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Delete a todo
     */
    public function deleteTodo($todoId) {
        try {
            $sql = "DELETE FROM todos WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$todoId]);
            
            if ($stmt->rowCount() > 0) {
                Config::log("Deleted todo #$todoId");
                return true;
            } else {
                Config::log("Todo #$todoId not found", 'WARNING');
                return false;
            }
        } catch (PDOException $e) {
            Config::log("Error deleting todo: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Get todo statistics
     */
    public function getStats() {
        try {
            $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN DATE(created_at) = DATE('now') THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN DATE(created_at) >= DATE('now', '-7 days') THEN 1 ELSE 0 END) as this_week
            FROM todos
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate completion rate
            $stats['completion_rate'] = $stats['total'] > 0 ? 
                round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
            
            return $stats;
        } catch (PDOException $e) {
            Config::log("Error fetching stats: " . $e->getMessage(), 'ERROR');
            return [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'today' => 0,
                'this_week' => 0,
                'completion_rate' => 0
            ];
        }
    }
    
    /**
     * Search todos
     */
    public function searchTodos($query, $limit = 20) {
        try {
            $sql = "SELECT * FROM todos WHERE description LIKE ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["%$query%", $limit]);
            
            $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($todos as &$todo) {
                $todo['source_data'] = json_decode($todo['source_data'], true);
            }
            
            return $todos;
        } catch (PDOException $e) {
            Config::log("Error searching todos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Export todos to JSON
     */
    public function exportToJson($filename = null) {
        if ($filename === null) {
            $filename = 'todos_export_' . date('Y-m-d_H-i-s') . '.json';
        }
        
        $todos = $this->getTodos();
        $stats = $this->getStats();
        
        $export = [
            'exported_at' => date('c'),
            'stats' => $stats,
            'todos' => $todos
        ];
        
        $filepath = Config::get('backups_dir') . $filename;
        file_put_contents($filepath, json_encode($export, JSON_PRETTY_PRINT));
        
        Config::log("Exported todos to $filename");
        return $filepath;
    }
    
    /**
     * Generate mobile-friendly view
     */
    public function getMobileView($limit = 10) {
        $pendingTodos = $this->getTodos('pending', null, $limit);
        
        if (empty($pendingTodos)) {
            return "🎉 No pending todos! You're all caught up!";
        }
        
        $view = "📱 YOUR TODOS (Mobile View)\n";
        $view .= str_repeat("=", 30) . "\n\n";
        
        foreach ($pendingTodos as $i => $todo) {
            $priorityEmoji = $this->getPriorityEmoji($todo['priority']);
            $statusEmoji = "⏳";
            
            $view .= "$priorityEmoji $statusEmoji #{$todo['id']}\n";
            $view .= "   {$todo['description']}\n";
            
            if ($todo['source'] !== 'manual') {
                $view .= "   📱 Source: {$todo['source']}\n";
            }
            
            $view .= "\n";
        }
        
        if (count($pendingTodos) >= $limit) {
            $totalPending = $this->getStats()['pending'];
            $remaining = $totalPending - $limit;
            if ($remaining > 0) {
                $view .= "... and $remaining more todos\n";
            }
        }
        
        return $view;
    }
    
    private function getPriorityEmoji($priority) {
        switch (strtolower($priority)) {
            case 'high': return '🔥';
            case 'medium': return '📋';
            case 'low': return '📝';
            default: return '📋';
        }
    }
    
    /**
     * Clean up old completed todos
     */
    public function cleanup($daysOld = 30) {
        try {
            $sql = "DELETE FROM todos WHERE status = 'completed' AND completed_at < DATE('now', '-$daysOld days')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $deletedCount = $stmt->rowCount();
            Config::log("Cleaned up $deletedCount old completed todos");
            
            return $deletedCount;
        } catch (PDOException $e) {
            Config::log("Error during cleanup: " . $e->getMessage(), 'ERROR');
            return 0;
        }
    }
}
?>