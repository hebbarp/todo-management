<?php
require_once 'config.php';
require_once 'TodoManager.php';

/**
 * WhatsApp Integration for Todo Management
 * Handles WhatsApp messages via Twilio API
 */
class WhatsAppIntegration {
    private $todoManager;
    private $twilioSid;
    private $twilioToken;
    private $twilioPhone;
    
    public function __construct() {
        $this->todoManager = new TodoManager();
        $this->twilioSid = Config::get('twilio_sid');
        $this->twilioToken = Config::get('twilio_token');
        $this->twilioPhone = Config::get('twilio_phone');
    }
    
    /**
     * Process incoming WhatsApp message
     */
    public function processMessage($message, $phoneNumber) {
        $message = trim($message);
        $phoneNumber = $this->cleanPhoneNumber($phoneNumber);
        
        Config::log("Processing WhatsApp message from $phoneNumber: $message");
        
        $action = $this->parseMessage($message);
        
        switch ($action['type']) {
            case 'add':
                return $this->handleAddTodo($action['data'], $phoneNumber);
            
            case 'complete':
                return $this->handleCompleteTodo($action['data']);
            
            case 'list':
                return $this->handleListTodos($phoneNumber);
            
            case 'help':
                return $this->getHelpMessage();
            
            default:
                // If no pattern matches, treat as add todo
                if (strlen($message) > 3) {
                    return $this->handleAddTodo($message, $phoneNumber);
                } else {
                    return $this->getHelpMessage();
                }
        }
    }
    
    /**
     * Parse WhatsApp message to determine action
     */
    private function parseMessage($message) {
        $message = strtolower($message);
        
        // Patterns for different actions
        $patterns = [
            'add' => [
                '/add todo[:\s]+(.+)/i',
                '/new todo[:\s]+(.+)/i',
                '/create todo[:\s]+(.+)/i',
                '/todo[:\s]+(.+)/i',
                '/task[:\s]+(.+)/i',
            ],
            'complete' => [
                '/complete[:\s]+#?(\d+)/i',
                '/done[:\s]+#?(\d+)/i',
                '/finished[:\s]+#?(\d+)/i',
                '/mark done[:\s]+#?(\d+)/i',
            ],
            'list' => [
                '/list todos?/i',
                '/show todos?/i',
                '/my todos?/i',
                '/what are my todos?/i',
                '/pending todos?/i',
            ],
            'help' => [
                '/help/i',
                '/how to use/i',
                '/commands/i',
                '/what can you do/i',
            ]
        ];
        
        foreach ($patterns as $actionType => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match($pattern, $message, $matches)) {
                    if ($actionType === 'add') {
                        return ['type' => 'add', 'data' => trim($matches[1])];
                    } elseif ($actionType === 'complete') {
                        return ['type' => 'complete', 'data' => intval($matches[1])];
                    } else {
                        return ['type' => $actionType, 'data' => null];
                    }
                }
            }
        }
        
        return ['type' => 'unknown', 'data' => null];
    }
    
    /**
     * Handle adding a new todo
     */
    private function handleAddTodo($description, $phoneNumber) {
        $sourceData = [
            'phone_number' => $phoneNumber,
            'channel' => 'whatsapp'
        ];
        
        $todoId = $this->todoManager->addTodo($description, 'whatsapp', 'medium', $sourceData);
        
        // Try to create GitHub issue if available
        $this->createGitHubIssue($description);
        
        return "✅ Todo #$todoId created: $description";
    }
    
    /**
     * Handle completing a todo
     */
    private function handleCompleteTodo($todoId) {
        $success = $this->todoManager->completeTodo($todoId);
        
        if ($success) {
            return "🎉 Todo #$todoId marked as completed!";
        } else {
            return "❌ Todo #$todoId not found or already completed";
        }
    }
    
    /**
     * Handle listing todos
     */
    private function handleListTodos($phoneNumber) {
        $todos = $this->todoManager->getTodos('pending', null, 5); // Last 5 pending
        
        if (empty($todos)) {
            return "🎉 No pending todos! You're all caught up!";
        }
        
        $response = "📋 Your pending todos:\n";
        foreach ($todos as $todo) {
            $response .= "#{$todo['id']}: {$todo['description']}\n";
        }
        
        return trim($response);
    }
    
    /**
     * Get help message
     */
    private function getHelpMessage() {
        return "🤖 WhatsApp Todo Bot Commands:\n\n" .
               "📝 Add Todo:\n" .
               "• \"Add todo: Call investor\"\n" .
               "• \"Task: Review budget\"\n" .
               "• Or just send the task directly\n\n" .
               "✅ Complete Todo:\n" .
               "• \"Complete 5\" (for todo #5)\n" .
               "• \"Done 3\"\n" .
               "• \"Finished 7\"\n\n" .
               "📋 List Todos:\n" .
               "• \"List todos\"\n" .
               "• \"Show my todos\"\n" .
               "• \"Pending todos\"\n\n" .
               "❓ Help:\n" .
               "• \"Help\"\n" .
               "• \"Commands\"\n\n" .
               "Just send your message and I'll help manage your todos! 🚀";
    }
    
    /**
     * Send WhatsApp message via Twilio
     */
    public function sendMessage($to, $message) {
        if (empty($this->twilioSid) || empty($this->twilioToken)) {
            Config::log("Twilio credentials not configured", 'ERROR');
            return false;
        }
        
        $to = $this->cleanPhoneNumber($to);
        $from = $this->twilioPhone;
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$this->twilioSid/Messages.json";
        
        $data = [
            'From' => "whatsapp:$from",
            'To' => "whatsapp:$to",
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->twilioSid:$this->twilioToken");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            Config::log("WhatsApp message sent to $to");
            return true;
        } else {
            Config::log("Failed to send WhatsApp message to $to: $response", 'ERROR');
            return false;
        }
    }
    
    /**
     * Clean and standardize phone number
     */
    private function cleanPhoneNumber($phoneNumber) {
        // Remove all non-digits
        $clean = preg_replace('/\D/', '', $phoneNumber);
        
        // Add country code if missing (default to India +91)
        if (strlen($clean) == 10) {
            $clean = '91' . $clean;
        } elseif (strlen($clean) == 12 && substr($clean, 0, 2) === '91') {
            // Already has country code
        } elseif (strlen($clean) == 11 && substr($clean, 0, 1) === '1') {
            // US number
        }
        
        return '+' . $clean;
    }
    
    /**
     * Create GitHub issue (if gh CLI is available)
     */
    private function createGitHubIssue($description) {
        try {
            $title = "[WhatsApp] $description";
            $body = "Created via WhatsApp integration at " . date('Y-m-d H:i:s');
            
            $command = sprintf(
                'gh issue create --repo hebbarp/todo-management --title %s --body %s 2>/dev/null',
                escapeshellarg($title),
                escapeshellarg($body)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                Config::log("Created GitHub issue: $title");
            }
        } catch (Exception $e) {
            // Silently fail if GitHub CLI is not available
            Config::log("Could not create GitHub issue: " . $e->getMessage(), 'DEBUG');
        }
    }
}

/**
 * Webhook handler for incoming WhatsApp messages
 */
class WhatsAppWebhook {
    private $whatsapp;
    
    public function __construct() {
        $this->whatsapp = new WhatsAppIntegration();
    }
    
    /**
     * Handle incoming webhook
     */
    public function handleWebhook() {
        // Verify webhook (if using verification token)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->handleVerification();
            return;
        }
        
        // Process incoming messages
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleIncomingMessage();
            return;
        }
        
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
    /**
     * Handle webhook verification
     */
    private function handleVerification() {
        $verifyToken = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';
        
        $expectedToken = 'todo_management_webhook_token'; // Change this!
        
        if ($verifyToken === $expectedToken) {
            echo $challenge;
            Config::log("Webhook verified successfully");
        } else {
            http_response_code(403);
            echo 'Verification failed';
            Config::log("Webhook verification failed", 'WARNING');
        }
    }
    
    /**
     * Handle incoming WhatsApp message
     */
    private function handleIncomingMessage() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            Config::log("Received webhook data: " . $input, 'DEBUG');
            
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if ($change['field'] === 'messages') {
                                $this->processMessages($change['value']);
                            }
                        }
                    }
                }
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (Exception $e) {
            Config::log("Webhook error: " . $e->getMessage(), 'ERROR');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Process messages from webhook data
     */
    private function processMessages($messagesData) {
        if (!isset($messagesData['messages'])) {
            return;
        }
        
        foreach ($messagesData['messages'] as $message) {
            try {
                $phoneNumber = $message['from'] ?? '';
                $messageType = $message['type'] ?? '';
                
                if ($messageType === 'text') {
                    $textContent = $message['text']['body'] ?? '';
                    
                    Config::log("Processing message from $phoneNumber: $textContent");
                    
                    $response = $this->whatsapp->processMessage($textContent, $phoneNumber);
                    
                    Config::log("Generated response: $response");
                    
                    // In a real implementation, you might want to send the response back
                    // For now, we just log it
                    $this->logResponse($phoneNumber, $response);
                }
            } catch (Exception $e) {
                Config::log("Error processing message: " . $e->getMessage(), 'ERROR');
            }
        }
    }
    
    /**
     * Log response (in real implementation, send via WhatsApp API)
     */
    private function logResponse($phoneNumber, $response) {
        $logFile = Config::get('logs_dir') . 'whatsapp_responses.log';
        $logEntry = date('Y-m-d H:i:s') . " - $phoneNumber: $response" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        Config::log("Would send to $phoneNumber: $response");
    }
}
?>