#!/usr/bin/env python3
"""
WhatsApp Todo Integration
Processes WhatsApp messages to create and manage todos
"""

import os
import re
import json
import subprocess
from datetime import datetime
from env_loader import load_env

class WhatsAppTodoManager:
    def __init__(self):
        load_env()
        self.todo_file = "whatsapp_todos.json"
        self.load_todos()
    
    def load_todos(self):
        """Load existing todos from file"""
        try:
            if os.path.exists(self.todo_file):
                with open(self.todo_file, 'r') as f:
                    self.todos = json.load(f)
            else:
                self.todos = []
        except Exception as e:
            print(f"Error loading todos: {e}")
            self.todos = []
    
    def save_todos(self):
        """Save todos to file"""
        try:
            with open(self.todo_file, 'w') as f:
                json.dump(self.todos, f, indent=2)
        except Exception as e:
            print(f"Error saving todos: {e}")
    
    def parse_whatsapp_message(self, message, phone_number):
        """Parse WhatsApp message to extract todo actions"""
        message = message.strip().lower()
        phone_number = self.clean_phone_number(phone_number)
        
        # Patterns for different todo actions
        patterns = {
            'add': [
                r'add todo[:\s]+(.+)',
                r'new todo[:\s]+(.+)',
                r'create todo[:\s]+(.+)',
                r'todo[:\s]+(.+)',
                r'task[:\s]+(.+)',
            ],
            'complete': [
                r'complete[:\s]+(?:#)?(\d+)',
                r'done[:\s]+(?:#)?(\d+)',
                r'finished[:\s]+(?:#)?(\d+)',
                r'mark done[:\s]+(?:#)?(\d+)',
            ],
            'list': [
                r'list todos?',
                r'show todos?',
                r'my todos?',
                r'what are my todos?',
                r'pending todos?',
            ],
            'help': [
                r'help',
                r'how to use',
                r'commands',
                r'what can you do',
            ]
        }
        
        for action, pattern_list in patterns.items():
            for pattern in pattern_list:
                match = re.search(pattern, message)
                if match:
                    if action in ['add']:
                        return action, match.group(1).strip()
                    elif action in ['complete']:
                        return action, int(match.group(1))
                    else:
                        return action, None
        
        # If no pattern matches, treat as add todo
        if len(message) > 3:  # Avoid very short messages
            return 'add', message
        
        return 'unknown', None
    
    def clean_phone_number(self, phone_number):
        """Clean and standardize phone number"""
        # Remove all non-digits
        clean = re.sub(r'\D', '', phone_number)
        
        # Add country code if missing
        if len(clean) == 10:
            clean = '91' + clean  # Default to India
        elif len(clean) == 12 and clean.startswith('91'):
            pass  # Already has country code
        
        return clean
    
    def add_todo(self, description, phone_number):
        """Add a new todo"""
        todo_id = len(self.todos) + 1
        todo = {
            'id': todo_id,
            'description': description,
            'phone_number': phone_number,
            'status': 'pending',
            'created_at': datetime.now().isoformat(),
            'completed_at': None
        }
        
        self.todos.append(todo)
        self.save_todos()
        
        # Try to create GitHub issue if possible
        try:
            self.create_github_issue(description)
        except Exception as e:
            print(f"Could not create GitHub issue: {e}")
        
        return todo_id
    
    def complete_todo(self, todo_id):
        """Mark a todo as completed"""
        for todo in self.todos:
            if todo['id'] == todo_id and todo['status'] == 'pending':
                todo['status'] = 'completed'
                todo['completed_at'] = datetime.now().isoformat()
                self.save_todos()
                return True
        return False
    
    def list_todos(self, phone_number, status='pending'):
        """List todos for a specific phone number"""
        user_todos = [
            todo for todo in self.todos 
            if todo['phone_number'] == phone_number and todo['status'] == status
        ]
        return user_todos
    
    def create_github_issue(self, description):
        """Create GitHub issue for todo (if gh CLI is available)"""
        try:
            cmd = [
                'gh', 'issue', 'create',
                '--repo', 'hebbarp/todo-management',
                '--title', description,
                '--body', f'Created via WhatsApp integration at {datetime.now()}'
            ]
            subprocess.run(cmd, check=True, capture_output=True)
        except (subprocess.CalledProcessError, FileNotFoundError):
            pass  # gh CLI not available or not authenticated
    
    def process_message(self, message, phone_number):
        """Process a WhatsApp message and return response"""
        action, data = self.parse_whatsapp_message(message, phone_number)
        phone_number = self.clean_phone_number(phone_number)
        
        if action == 'add':
            todo_id = self.add_todo(data, phone_number)
            return f"✅ Todo #{todo_id} created: {data}"
        
        elif action == 'complete':
            if self.complete_todo(data):
                return f"🎉 Todo #{data} marked as completed!"
            else:
                return f"❌ Todo #{data} not found or already completed"
        
        elif action == 'list':
            todos = self.list_todos(phone_number)
            if todos:
                response = "📋 Your pending todos:\n"
                for todo in todos[-5:]:  # Show last 5
                    response += f"#{todo['id']}: {todo['description']}\n"
                return response.strip()
            else:
                return "🎉 No pending todos! You're all caught up!"
        
        elif action == 'help':
            return self.get_help_message()
        
        else:
            # Try to add as todo anyway
            todo_id = self.add_todo(message, phone_number)
            return f"✅ Todo #{todo_id} created: {message}"
    
    def get_help_message(self):
        """Return help message"""
        return """🤖 WhatsApp Todo Bot Commands:

📝 Add Todo:
• "Add todo: Call investor"
• "Task: Review budget"
• Or just send the task directly

✅ Complete Todo:
• "Complete 5" (for todo #5)
• "Done 3"
• "Finished 7"

📋 List Todos:
• "List todos"
• "Show my todos"
• "Pending todos"

❓ Help:
• "Help"
• "Commands"

Just send your message and I'll help manage your todos! 🚀"""

def simulate_whatsapp_message(message, phone_number="919742814697"):
    """Simulate receiving a WhatsApp message (for testing)"""
    manager = WhatsAppTodoManager()
    
    print(f"📱 WhatsApp Message Received")
    print(f"From: +{phone_number}")
    print(f"Message: {message}")
    print("-" * 40)
    
    response = manager.process_message(message, phone_number)
    
    print(f"🤖 Bot Response:")
    print(response)
    print("-" * 40)
    
    return response

def main():
    """Test the WhatsApp todo integration"""
    print("🧪 Testing WhatsApp Todo Integration")
    print("=" * 40)
    
    # Test cases
    test_messages = [
        "Add todo: Send quarterly report to board",
        "Task: Call investor meeting",
        "Review marketing budget for Q2",
        "List todos",
        "Complete 1",
        "Done 2", 
        "Show my todos",
        "Help"
    ]
    
    for message in test_messages:
        simulate_whatsapp_message(message)
        print()

if __name__ == "__main__":
    main()