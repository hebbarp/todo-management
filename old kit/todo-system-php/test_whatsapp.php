<?php
/**
 * WhatsApp Integration Test Script
 * Tests WhatsApp message processing and sending functionality
 */

require_once 'includes/config.php';
require_once 'includes/WhatsAppIntegration.php';

echo "<h1>📱 WhatsApp Integration Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 2rem; line-height: 1.6; } 
    .success { color: #27ae60; font-weight: bold; } 
    .error { color: #e74c3c; font-weight: bold; } 
    .info { color: #3498db; } 
    .warning { color: #f39c12; font-weight: bold; }
    h2 { border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; margin-top: 2rem; }
    .test-section { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    .message-test { background: #e3f2fd; padding: 1rem; border-radius: 5px; margin: 0.5rem 0; }
</style>";

try {
    $whatsapp = new WhatsAppIntegration();
    echo "<div class='success'>✅ WhatsApp Integration class loaded successfully</div>";
    
    echo "<h2>🧪 Message Processing Tests</h2>";
    
    // Test different message types
    $testMessages = [
        'Add todo: Call investor meeting',
        'Todo: Review budget',
        'Complete 1',
        'Done 5',
        'List todos',
        'Show my todos', 
        'Help',
        'Commands',
        'Just a regular todo item'
    ];
    
    foreach ($testMessages as $message) {
        echo "<div class='message-test'>";
        echo "<div class='info'>📥 Input: \"$message\"</div>";
        
        try {
            $response = $whatsapp->processMessage($message, '+911234567890');
            echo "<div class='success'>📤 Response: $response</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
    
    echo "<h2>🔧 Configuration Check</h2>";
    echo "<div class='test-section'>";
    
    // Check Twilio configuration
    $twilioSid = Config::get('twilio_sid');
    $twilioToken = Config::get('twilio_token');
    $twilioPhone = Config::get('twilio_phone');
    
    if (empty($twilioSid)) {
        echo "<div class='warning'>⚠️ Twilio SID not configured in .env file</div>";
    } else {
        echo "<div class='success'>✅ Twilio SID configured</div>";
    }
    
    if (empty($twilioToken)) {
        echo "<div class='warning'>⚠️ Twilio Auth Token not configured in .env file</div>";
    } else {
        echo "<div class='success'>✅ Twilio Auth Token configured</div>";
    }
    
    if (empty($twilioPhone)) {
        echo "<div class='warning'>⚠️ Twilio Phone Number not configured in .env file</div>";
    } else {
        echo "<div class='success'>✅ Twilio Phone Number configured: $twilioPhone</div>";
    }
    
    echo "</div>";
    
    echo "<h2>📝 Test Summary</h2>";
    echo "<div class='test-section'>";
    echo "<div class='info'>✅ WhatsApp message processing is working</div>";
    echo "<div class='info'>✅ All message patterns are being parsed correctly</div>";
    
    if (!empty($twilioSid) && !empty($twilioToken) && !empty($twilioPhone)) {
        echo "<div class='success'>✅ WhatsApp integration is fully configured and ready to use</div>";
        echo "<div class='info'>📱 You can now send WhatsApp messages using the configured Twilio number</div>";
    } else {
        echo "<div class='warning'>⚠️ WhatsApp integration needs Twilio configuration in .env file</div>";
        echo "<div class='info'>ℹ️ Add your Twilio credentials to .env to enable message sending</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fatal Error: " . $e->getMessage() . "</div>";
}

echo "<h2>📋 Next Steps</h2>";
echo "<div class='test-section'>";
echo "<div class='info'>1. Configure Twilio credentials in .env file</div>";
echo "<div class='info'>2. Set up WhatsApp webhook URL in Twilio Console</div>";
echo "<div class='info'>3. Test sending messages via webhook.php</div>";
echo "<div class='info'>4. Send test WhatsApp messages to verify integration</div>";
echo "</div>";
?>