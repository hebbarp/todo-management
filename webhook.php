<?php
/**
 * WhatsApp Webhook Handler
 * Processes incoming WhatsApp messages from Twilio
 */

require_once 'php/WhatsAppIntegration.php';

// Handle the webhook request
$webhook = new WhatsAppWebhook();
$webhook->handleWebhook();
?>