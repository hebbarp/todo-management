<?php
/**
 * WhatsApp Message Sending Test
 * Tests actual WhatsApp message sending via Twilio API
 */

require_once 'includes/config.php';
require_once 'includes/WhatsAppIntegration.php';

echo "<h1>📱 WhatsApp Message Sending Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 2rem; line-height: 1.6; } 
    .success { color: #27ae60; font-weight: bold; } 
    .error { color: #e74c3c; font-weight: bold; } 
    .info { color: #3498db; } 
    .warning { color: #f39c12; font-weight: bold; }
    h2 { border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; margin-top: 2rem; }
    .test-section { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
    input, textarea { width: 100%; padding: 0.5rem; margin: 0.5rem 0; border: 1px solid #ccc; border-radius: 4px; }
    button { background: #3498db; color: white; padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #2980b9; }
</style>";

$whatsapp = new WhatsAppIntegration();

// Check if form was submitted for sending test message
if ($_POST['action'] === 'send_test' && !empty($_POST['phone']) && !empty($_POST['message'])) {
    echo "<h2>📤 Sending Test Message</h2>";
    echo "<div class='test-section'>";
    
    $phoneNumber = $_POST['phone'];
    $message = $_POST['message'];
    
    echo "<div class='info'>📱 Sending to: $phoneNumber</div>";
    echo "<div class='info'>💬 Message: $message</div>";
    
    try {
        $result = $whatsapp->sendMessage($phoneNumber, $message);
        
        if ($result) {
            echo "<div class='success'>✅ Message sent successfully!</div>";
            echo "<div class='info'>Check your WhatsApp for the message.</div>";
        } else {
            echo "<div class='error'>❌ Failed to send message. Check logs for details.</div>";
            echo "<div class='warning'>⚠️ Make sure your Twilio credentials are correct and account is active.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// Check configuration status
echo "<h2>🔧 Configuration Status</h2>";
echo "<div class='test-section'>";

$twilioSid = Config::get('twilio_sid');
$twilioToken = Config::get('twilio_token');
$twilioPhone = Config::get('twilio_phone');

$configOk = true;

if (empty($twilioSid) || $twilioSid === 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
    echo "<div class='error'>❌ Twilio SID not configured or using placeholder</div>";
    $configOk = false;
} else {
    echo "<div class='success'>✅ Twilio SID configured</div>";
}

if (empty($twilioToken) || $twilioToken === 'your_twilio_auth_token_here') {
    echo "<div class='error'>❌ Twilio Auth Token not configured or using placeholder</div>";
    $configOk = false;
} else {
    echo "<div class='success'>✅ Twilio Auth Token configured</div>";
}

if (empty($twilioPhone) || $twilioPhone === '+1234567890') {
    echo "<div class='error'>❌ Twilio Phone Number not configured or using placeholder</div>";
    $configOk = false;
} else {
    echo "<div class='success'>✅ Twilio Phone Number configured: $twilioPhone</div>";
}

echo "</div>";

if ($configOk) {
    echo "<h2>📤 Send Test WhatsApp Message</h2>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='send_test'>";
    echo "<label>📱 Phone Number (with country code, e.g., +919876543210):</label>";
    echo "<input type='text' name='phone' placeholder='+919876543210' required>";
    echo "<label>💬 Test Message:</label>";
    echo "<textarea name='message' rows='3' placeholder='Test message from PHP WhatsApp integration!' required>🤖 Hello! This is a test message from your PHP Todo Management System. WhatsApp integration is working! 🚀</textarea>";
    echo "<button type='submit'>Send Test Message</button>";
    echo "</form>";
    
    echo "<div class='warning'>⚠️ Make sure the phone number has WhatsApp and is approved in your Twilio Sandbox (for testing).</div>";
} else {
    echo "<h2>⚙️ Configuration Required</h2>";
    echo "<div class='test-section'>";
    echo "<div class='error'>❌ WhatsApp sending is not available because Twilio is not configured.</div>";
    echo "<div class='info'>To enable WhatsApp messaging:</div>";
    echo "<ol>";
    echo "<li>Get Twilio credentials from <a href='https://console.twilio.com' target='_blank'>https://console.twilio.com</a></li>";
    echo "<li>Edit the .env file with your real credentials</li>";
    echo "<li>Set up WhatsApp Sandbox in Twilio Console</li>";
    echo "<li>Return here to test messaging</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>📝 Testing Notes</h2>";
echo "<div class='test-section'>";
echo "<div class='info'>• For testing, use Twilio WhatsApp Sandbox (free)</div>";
echo "<div class='info'>• Production requires WhatsApp Business API approval</div>";
echo "<div class='info'>• Phone numbers must be in international format (+country code)</div>";
echo "<div class='info'>• Check logs/system.log for detailed error messages</div>";
echo "</div>";
?>