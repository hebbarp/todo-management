<?php
require_once 'config.php';
require_once 'TodoManager.php';

/**
 * Email Integration for Todo Management
 * Processes emails to extract and manage todos
 */
class EmailIntegration {
    private $todoManager;
    private $gmailUser;
    private $gmailPassword;
    private $imapServer;
    private $smtpServer;
    
    public function __construct() {
        $this->todoManager = new TodoManager();
        $this->gmailUser = Config::get('gmail_user');
        $this->gmailPassword = Config::get('gmail_password');
        $this->imapServer = 'imap.gmail.com';
        $this->smtpServer = 'smtp.gmail.com';
    }
    
    /**
     * Process new emails for todos
     */
    public function processEmails($folder = 'INBOX', $limit = 10) {
        if (empty($this->gmailUser) || empty($this->gmailPassword)) {
            Config::log("Gmail credentials not configured", 'ERROR');
            return false;
        }
        
        try {
            $mailbox = $this->connectToImap($folder);
            
            // Search for unread emails
            $emails = imap_search($mailbox, 'UNSEEN');
            
            if (!$emails) {
                Config::log("No new emails to process");
                imap_close($mailbox);
                return true;
            }
            
            $processedCount = 0;
            $todosCreated = 0;
            
            // Process emails (limit to avoid overwhelming)
            $emailsToProcess = array_slice($emails, -$limit);
            
            foreach ($emailsToProcess as $emailNumber) {
                if ($this->isEmailProcessed($emailNumber)) {
                    continue;
                }
                
                $header = imap_headerinfo($mailbox, $emailNumber);
                $body = $this->getEmailBody($mailbox, $emailNumber);
                
                $sender = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : 'unknown';
                $subject = isset($header->subject) ? $header->subject : 'No Subject';
                
                // Parse for todos
                $extractedTodos = $this->parseEmailForTodos($body, $sender, $subject);
                
                // Add todos to system
                foreach ($extractedTodos as $todoDesc) {
                    $sourceData = [
                        'sender_email' => $sender,
                        'email_subject' => $subject,
                        'channel' => 'email'
                    ];
                    
                    $todoId = $this->todoManager->addTodo($todoDesc, 'email', 'medium', $sourceData);
                    $todosCreated++;
                }
                
                // Mark email as processed
                $this->markEmailAsProcessed($emailNumber);
                $processedCount++;
                
                Config::log("Processed email from $sender: " . count($extractedTodos) . " todos found");
            }
            
            imap_close($mailbox);
            
            Config::log("Email processing complete: Processed $processedCount emails, Created $todosCreated todos");
            return true;
            
        } catch (Exception $e) {
            Config::log("Error processing emails: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Connect to IMAP server
     */
    private function connectToImap($folder = 'INBOX') {
        $mailbox = "{{$this->imapServer}:993/imap/ssl}$folder";
        
        $connection = imap_open($mailbox, $this->gmailUser, $this->gmailPassword);
        
        if (!$connection) {
            throw new Exception("Failed to connect to Gmail IMAP: " . imap_last_error());
        }
        
        return $connection;
    }
    
    /**
     * Get email body content
     */
    private function getEmailBody($mailbox, $emailNumber) {
        $structure = imap_fetchstructure($mailbox, $emailNumber);
        
        if (isset($structure->parts)) {
            // Multipart email
            foreach ($structure->parts as $partNumber => $part) {
                if ($part->subtype === 'PLAIN') {
                    $body = imap_fetchbody($mailbox, $emailNumber, $partNumber + 1);
                    
                    // Decode if needed
                    if ($part->encoding === 3) { // BASE64
                        $body = base64_decode($body);
                    } elseif ($part->encoding === 4) { // QUOTED-PRINTABLE
                        $body = quoted_printable_decode($body);
                    }
                    
                    return $body;
                }
            }
        } else {
            // Single part email
            $body = imap_fetchbody($mailbox, $emailNumber, 1);
            
            if ($structure->encoding === 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding === 4) {
                $body = quoted_printable_decode($body);
            }
            
            return $body;
        }
        
        return '';
    }
    
    /**
     * Parse email content to extract todo items
     */
    private function parseEmailForTodos($emailBody, $senderEmail, $subject = '') {
        $todos = [];
        
        // Common todo patterns in emails
        $patterns = [
            '/todo[:\s]*(.+?)(?:\n|$)/i',
            '/task[:\s]*(.+?)(?:\n|$)/i',
            '/action item[:\s]*(.+?)(?:\n|$)/i',
            '/please[:\s]*(.+?)(?:\n|$)/i',
            '/reminder[:\s]*(.+?)(?:\n|$)/i',
            '/follow up[:\s]*(.+?)(?:\n|$)/i',
            '/need to[:\s]*(.+?)(?:\n|$)/i',
            '/remember to[:\s]*(.+?)(?:\n|$)/i',
        ];
        
        // Look for numbered lists
        if (preg_match_all('/(\d+)\.\s*(.+?)(?:\n|$)/i', $emailBody, $matches)) {
            foreach ($matches[2] as $item) {
                $item = trim($item);
                if (strlen($item) > 5) { // Avoid very short items
                    $todos[] = $item;
                }
            }
        }
        
        // Look for bullet points
        if (preg_match_all('/[•\-\*]\s*(.+?)(?:\n|$)/i', $emailBody, $matches)) {
            foreach ($matches[1] as $item) {
                $item = trim($item);
                if (strlen($item) > 5) {
                    $todos[] = $item;
                }
            }
        }
        
        // Look for specific todo patterns
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $emailBody, $matches)) {
                foreach ($matches[1] as $match) {
                    $match = trim($match);
                    if (strlen($match) > 5) {
                        $todos[] = $match;
                    }
                }
            }
        }
        
        // If subject line looks like a todo and no todos found in body
        if (empty($todos) && strlen($emailBody) < 100) {
            // Use subject as todo if it seems actionable
            if (strlen($subject) > 5 && !empty($subject) && $subject !== 'No Subject') {
                $todos[] = $subject;
            }
        }
        
        // Remove duplicates and clean up
        $todos = array_unique($todos);
        $cleanedTodos = [];
        
        foreach ($todos as $todo) {
            $todo = trim($todo);
            $todo = preg_replace('/\s+/', ' ', $todo); // Normalize whitespace
            if (strlen($todo) > 5) {
                $cleanedTodos[] = $todo;
            }
        }
        
        return $cleanedTodos;
    }
    
    /**
     * Check if email has been processed already
     */
    private function isEmailProcessed($emailId) {
        try {
            $pdo = Config::getDatabaseConnection();
            $sql = "SELECT COUNT(*) FROM processed_emails WHERE email_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$emailId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            Config::log("Error checking processed email: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Mark email as processed
     */
    private function markEmailAsProcessed($emailId) {
        try {
            $pdo = Config::getDatabaseConnection();
            $sql = "INSERT OR IGNORE INTO processed_emails (email_id) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$emailId]);
        } catch (Exception $e) {
            Config::log("Error marking email as processed: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Send status email
     */
    public function sendStatusEmail($toEmail, $subject, $message) {
        if (empty($this->gmailUser) || empty($this->gmailPassword)) {
            Config::log("Gmail credentials not configured for sending", 'ERROR');
            return false;
        }
        
        try {
            $headers = [
                'From: ' . $this->gmailUser,
                'Reply-To: ' . $this->gmailUser,
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8'
            ];
            
            $htmlMessage = $this->formatEmailMessage($message);
            $fullSubject = "[Todo System] $subject";
            
            // Use PHP's mail() function (requires mail server configuration)
            // For production, consider using PHPMailer or similar library
            $success = mail($toEmail, $fullSubject, $htmlMessage, implode("\r\n", $headers));
            
            if ($success) {
                Config::log("Status email sent to $toEmail");
                return true;
            } else {
                Config::log("Failed to send status email to $toEmail", 'ERROR');
                return false;
            }
            
        } catch (Exception $e) {
            Config::log("Error sending email: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Format email message as HTML
     */
    private function formatEmailMessage($message) {
        $html = '
        <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
                        🤖 Todo Management System
                    </h2>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        ' . nl2br(htmlspecialchars($message)) . '
                    </div>
                    
                    <div style="margin-top: 30px; padding: 15px; background: #e8f4f8; border-radius: 5px;">
                        <p style="margin: 0; font-size: 14px; color: #666;">
                            This is an automated message from your Todo Management System.
                            <br>Reply to this email to add new todos to your list.
                        </p>
                    </div>
                </div>
            </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Send daily summary email
     */
    public function sendDailySummary($toEmail) {
        $stats = $this->todoManager->getStats();
        $pendingTodos = $this->todoManager->getTodos('pending', null, 10);
        $completedToday = $this->getTodosCompletedToday();
        
        $summary = "📊 Daily Todo Summary - " . date('F d, Y') . "\n\n";
        $summary .= "⏳ Pending Todos ({$stats['pending']}):\n";
        
        foreach ($pendingTodos as $todo) {
            $summary .= "• #{$todo['id']}: {$todo['description']}\n";
        }
        
        if ($stats['pending'] > 10) {
            $remaining = $stats['pending'] - 10;
            $summary .= "... and $remaining more\n";
        }
        
        $summary .= "\n✅ Completed Today (" . count($completedToday) . "):\n";
        
        foreach ($completedToday as $todo) {
            $summary .= "• #{$todo['id']}: {$todo['description']}\n";
        }
        
        if (empty($completedToday)) {
            $summary .= "• No todos completed today\n";
        }
        
        $summary .= "\n📈 Progress: " . count($completedToday) . " completed, {$stats['pending']} pending";
        $summary .= "\n📊 Overall completion rate: {$stats['completion_rate']}%";
        
        return $this->sendStatusEmail($toEmail, "Daily Todo Summary", $summary);
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
}
?>