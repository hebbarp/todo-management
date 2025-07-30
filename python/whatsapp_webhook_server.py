#!/usr/bin/env python3
"""
WhatsApp Webhook Server
Receives WhatsApp messages via webhook and processes todos
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import urllib.parse
from whatsapp_todo_integration import WhatsAppTodoManager
from env_loader import load_env

class WhatsAppWebhookHandler(BaseHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        self.todo_manager = WhatsAppTodoManager()
        super().__init__(*args, **kwargs)
    
    def do_GET(self):
        """Handle webhook verification (required by WhatsApp)"""
        parsed_path = urllib.parse.urlparse(self.path)
        query_params = urllib.parse.parse_qs(parsed_path.query)
        
        # WhatsApp webhook verification
        if 'hub.verify_token' in query_params and 'hub.challenge' in query_params:
            verify_token = query_params['hub.verify_token'][0]
            challenge = query_params['hub.challenge'][0]
            
            # Check if verify token matches (set in your webhook config)
            expected_token = "todo_management_webhook_token"  # Change this!
            
            if verify_token == expected_token:
                self.send_response(200)
                self.send_header('Content-type', 'text/plain')
                self.end_headers()
                self.wfile.write(challenge.encode())
                print(f"✅ Webhook verified successfully")
            else:
                self.send_response(403)
                self.end_headers()
                print(f"❌ Webhook verification failed")
        else:
            # Health check endpoint
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            response = {
                'status': 'running',
                'service': 'WhatsApp Todo Webhook',
                'version': '1.0'
            }
            self.wfile.write(json.dumps(response).encode())
    
    def do_POST(self):
        """Handle incoming WhatsApp messages"""
        try:
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            
            # Parse webhook payload
            webhook_data = json.loads(post_data.decode('utf-8'))
            
            print(f"📱 Received webhook data: {json.dumps(webhook_data, indent=2)}")
            
            # Process WhatsApp Business API webhook format
            if 'entry' in webhook_data:
                for entry in webhook_data['entry']:
                    if 'changes' in entry:
                        for change in entry['changes']:
                            if change.get('field') == 'messages':
                                self.process_messages(change.get('value', {}))
            
            # Send success response
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'status': 'success'}).encode())
            
        except Exception as e:
            print(f"❌ Error processing webhook: {e}")
            self.send_response(500)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'error': str(e)}).encode())
    
    def process_messages(self, messages_data):
        """Process incoming messages from WhatsApp"""
        if 'messages' not in messages_data:
            return
        
        for message in messages_data['messages']:
            try:
                # Extract message details
                phone_number = message.get('from', '')
                message_type = message.get('type', '')
                
                if message_type == 'text':
                    text_content = message.get('text', {}).get('body', '')
                    
                    print(f"📱 Processing message from {phone_number}: {text_content}")
                    
                    # Process the todo
                    response = self.todo_manager.process_message(text_content, phone_number)
                    
                    print(f"🤖 Generated response: {response}")
                    
                    # In a real implementation, you would send the response back via WhatsApp API
                    # For now, we just log it
                    self.log_response(phone_number, response)
                    
            except Exception as e:
                print(f"❌ Error processing message: {e}")
    
    def log_response(self, phone_number, response):
        """Log the response (in real implementation, send via WhatsApp API)"""
        print(f"📤 Would send to {phone_number}: {response}")
        
        # TODO: Implement actual WhatsApp message sending
        # This would require WhatsApp Business API credentials
        # For now, we simulate the response
        
        with open('whatsapp_responses.log', 'a') as f:
            f.write(f"{phone_number}: {response}\n")

def start_webhook_server(port=8000):
    """Start the webhook server"""
    load_env()
    
    server = HTTPServer(('localhost', port), WhatsAppWebhookHandler)
    
    print(f"🚀 WhatsApp Webhook Server starting...")
    print(f"📡 Listening on: http://localhost:{port}")
    print(f"🔗 Webhook URL: http://localhost:{port}/webhook")
    print(f"💡 For production, use a service like ngrok to expose this publicly")
    print(f"⚠️  Remember to configure your webhook verify token!")
    print("-" * 60)
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n🛑 Shutting down webhook server...")
        server.server_close()

def test_webhook():
    """Test the webhook with sample data"""
    print("🧪 Testing webhook with sample WhatsApp message...")
    
    sample_webhook_data = {
        "entry": [{
            "id": "ENTRY_ID",
            "changes": [{
                "value": {
                    "messages": [{
                        "from": "919742814697",
                        "id": "MESSAGE_ID",
                        "timestamp": "1234567890",
                        "text": {
                            "body": "Add todo: Send quarterly report"
                        },
                        "type": "text"
                    }]
                },
                "field": "messages"
            }]
        }]
    }
    
    # Simulate processing
    handler = WhatsAppWebhookHandler(None, None, None)
    handler.process_messages(sample_webhook_data['entry'][0]['changes'][0]['value'])

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == 'test':
        test_webhook()
    else:
        start_webhook_server()