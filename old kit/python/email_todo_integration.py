#!/usr/bin/env python3
"""
Email Todo Integration
Processes emails to create and manage todos via IMAP/SMTP
"""

import os
import re
import json
import imaplib
import smtplib
import email
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
from env_loader import load_env

class EmailTodoManager:
    def __init__(self):
        load_env()
        self.gmail_user = os.getenv('GMAIL_USER')
        self.gmail_password = os.getenv('GMAIL_APP_PASSWORD')
        self.todo_file = "email_todos.json"
        self.processed_emails_file = "processed_emails.json"
        self.load_todos()
        self.load_processed_emails()
    
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
    
    def load_processed_emails(self):
        """Load list of processed email IDs"""
        try:
            if os.path.exists(self.processed_emails_file):
                with open(self.processed_emails_file, 'r') as f:
                    self.processed_emails = json.load(f)
            else:
                self.processed_emails = []
        except Exception as e:
            print(f"Error loading processed emails: {e}")
            self.processed_emails = []
    
    def save_processed_emails(self):
        """Save processed email IDs"""
        try:
            with open(self.processed_emails_file, 'w') as f:
                json.dump(self.processed_emails, f, indent=2)
        except Exception as e:
            print(f"Error saving processed emails: {e}")
    
    def connect_to_gmail(self):
        """Connect to Gmail IMAP server"""
        try:
            mail = imaplib.IMAP4_SSL('imap.gmail.com')
            mail.login(self.gmail_user, self.gmail_password)
            return mail
        except Exception as e:
            print(f"❌ Error connecting to Gmail: {e}")
            return None
    
    def parse_email_for_todos(self, email_body, sender_email):
        """Parse email content to extract todo items"""
        todos = []
        
        # Common todo patterns in emails
        patterns = [
            r'todo[:\s]*(.+?)(?:\n|$)',
            r'task[:\s]*(.+?)(?:\n|$)',
            r'action item[:\s]*(.+?)(?:\n|$)',
            r'please[:\s]*(.+?)(?:\n|$)',
            r'reminder[:\s]*(.+?)(?:\n|$)',
            r'follow up[:\s]*(.+?)(?:\n|$)',
            r'need to[:\s]*(.+?)(?:\n|$)',
            r'remember to[:\s]*(.+?)(?:\n|$)',
        ]
        
        # Look for numbered lists
        numbered_pattern = r'(\d+)\.\s*(.+?)(?:\n|$)'
        numbered_matches = re.findall(numbered_pattern, email_body, re.IGNORECASE | re.MULTILINE)
        
        for _, item in numbered_matches:
            if len(item.strip()) > 5:  # Avoid very short items
                todos.append(item.strip())
        
        # Look for bullet points
        bullet_pattern = r'[•\-\*]\s*(.+?)(?:\n|$)'
        bullet_matches = re.findall(bullet_pattern, email_body, re.MULTILINE)
        
        for item in bullet_matches:
            if len(item.strip()) > 5:
                todos.append(item.strip())
        
        # Look for specific todo patterns
        for pattern in patterns:
            matches = re.findall(pattern, email_body, re.IGNORECASE | re.MULTILINE)
            for match in matches:
                if len(match.strip()) > 5:
                    todos.append(match.strip())
        
        # If subject line looks like a todo and no todos found in body
        if not todos and len(email_body.strip()) < 100:  # Short email
            # Use subject as todo if it seems actionable
            return [email_body.strip()]
        
        return list(set(todos))  # Remove duplicates
    
    def add_todo(self, description, sender_email, email_subject="", priority="Medium"):
        """Add a new todo from email"""
        todo_id = len(self.todos) + 1
        todo = {
            'id': todo_id,
            'description': description,
            'sender_email': sender_email,
            'email_subject': email_subject,
            'status': 'pending',
            'priority': priority,
            'created_at': datetime.now().isoformat(),
            'completed_at': None,
            'source': 'email'
        }
        
        self.todos.append(todo)
        self.save_todos()
        
        print(f"✅ Added email todo #{todo_id}: {description}")
        return todo_id
    
    def process_emails(self, folder='INBOX', limit=10):
        """Process new emails for todos"""
        mail = self.connect_to_gmail()
        if not mail:
            return False
        
        try:
            mail.select(folder)
            
            # Search for unread emails
            status, messages = mail.search(None, 'UNSEEN')
            email_ids = messages[0].split()
            
            if not email_ids:
                print("📧 No new emails to process")
                return True
            
            processed_count = 0
            todos_created = 0
            
            # Process emails (limit to avoid overwhelming)
            for email_id in email_ids[-limit:]:
                email_id_str = email_id.decode()
                
                # Skip if already processed
                if email_id_str in self.processed_emails:
                    continue
                
                status, msg_data = mail.fetch(email_id, '(RFC822)')
                
                for response_part in msg_data:
                    if isinstance(response_part, tuple):
                        msg = email.message_from_bytes(response_part[1])
                        
                        sender = msg['From']
                        subject = msg['Subject'] or "No Subject"
                        
                        # Get email body
                        body = self.get_email_body(msg)
                        
                        # Parse for todos
                        extracted_todos = self.parse_email_for_todos(body, sender)
                        
                        # Add todos to system
                        for todo_desc in extracted_todos:
                            todo_id = self.add_todo(
                                description=todo_desc,
                                sender_email=sender,
                                email_subject=subject
                            )
                            todos_created += 1
                        
                        # Mark email as processed
                        self.processed_emails.append(email_id_str)
                        processed_count += 1
                        
                        print(f"📧 Processed email from {sender}: {len(extracted_todos)} todos found")
            
            self.save_processed_emails()
            
            print(f"📊 Email processing complete:")
            print(f"   • Processed {processed_count} emails")
            print(f"   • Created {todos_created} todos")
            
            return True
            
        except Exception as e:
            print(f"❌ Error processing emails: {e}")
            return False
        finally:
            mail.close()
            mail.logout()
    
    def get_email_body(self, msg):
        """Extract plain text body from email message"""
        body = ""
        
        if msg.is_multipart():
            for part in msg.walk():
                content_type = part.get_content_type()
                content_disposition = str(part.get("Content-Disposition"))
                
                if content_type == "text/plain" and "attachment" not in content_disposition:
                    try:
                        body = part.get_payload(decode=True).decode()
                        break
                    except:
                        continue
        else:
            try:
                body = msg.get_payload(decode=True).decode()
            except:
                body = str(msg.get_payload())
        
        return body
    
    def send_status_email(self, to_email, subject, message):
        """Send status update email"""
        try:
            msg = MIMEMultipart()
            msg['From'] = self.gmail_user
            msg['To'] = to_email
            msg['Subject'] = f"[Todo System] {subject}"
            
            # Create HTML message
            html_body = f"""
            <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
                            🤖 Todo Management System
                        </h2>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            {message.replace(chr(10), '<br>')}
                        </div>
                        
                        <div style="margin-top: 30px; padding: 15px; background: #e8f4f8; border-radius: 5px;">
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                This is an automated message from your Todo Management System.
                                <br>Reply to this email to add new todos to your list.
                            </p>
                        </div>
                    </div>
                </body>
            </html>
            """
            
            msg.attach(MIMEText(html_body, 'html'))
            
            server = smtplib.SMTP('smtp.gmail.com', 587)
            server.starttls()
            server.login(self.gmail_user, self.gmail_password)
            
            text = msg.as_string()
            server.sendmail(self.gmail_user, to_email, text)
            server.quit()
            
            print(f"📤 Status email sent to {to_email}")
            return True
            
        except Exception as e:
            print(f"❌ Error sending email: {e}")
            return False
    
    def send_daily_summary(self, to_email):
        """Send daily todo summary via email"""
        pending_todos = [todo for todo in self.todos if todo['status'] == 'pending']
        completed_today = [
            todo for todo in self.todos 
            if todo['status'] == 'completed' and 
            todo['completed_at'] and 
            datetime.fromisoformat(todo['completed_at']).date() == datetime.now().date()
        ]
        
        summary = f"""📊 Daily Todo Summary - {datetime.now().strftime("%B %d, %Y")}

⏳ Pending Todos ({len(pending_todos)}):
"""
        
        for todo in pending_todos[-10:]:  # Show last 10
            summary += f"• #{todo['id']}: {todo['description']}\n"
        
        if len(pending_todos) > 10:
            summary += f"... and {len(pending_todos) - 10} more\n"
        
        summary += f"\n✅ Completed Today ({len(completed_today)}):\n"
        
        for todo in completed_today:
            summary += f"• #{todo['id']}: {todo['description']}\n"
        
        if not completed_today:
            summary += "• No todos completed today\n"
        
        summary += f"\n📈 Progress: {len(completed_today)} completed, {len(pending_todos)} pending"
        
        return self.send_status_email(to_email, "Daily Todo Summary", summary)
    
    def list_todos(self, status='pending'):
        """List todos with optional status filter"""
        return [todo for todo in self.todos if todo['status'] == status]
    
    def complete_todo(self, todo_id):
        """Mark a todo as completed"""
        for todo in self.todos:
            if todo['id'] == todo_id and todo['status'] == 'pending':
                todo['status'] = 'completed'
                todo['completed_at'] = datetime.now().isoformat()
                self.save_todos()
                
                # Send completion notification to sender
                self.send_status_email(
                    todo['sender_email'],
                    f"Todo Completed: #{todo_id}",
                    f"✅ Todo has been marked as completed:\n\n{todo['description']}"
                )
                
                return True
        return False
    
    def sync_with_other_sources(self, whatsapp_todos=None, sheets_todos=None):
        """Sync email todos with other sources"""
        synced_count = 0
        
        # This would sync email todos to other systems
        # For now, we just track the sync
        print(f"📊 Email todos ready for sync: {len(self.todos)}")
        return synced_count

def test_email_integration():
    """Test the email integration"""
    print("🧪 Testing Email Todo Integration")
    print("=" * 40)
    
    manager = EmailTodoManager()
    
    # Test email parsing
    test_email_body = """
    Hi team,
    
    Here are the action items from our meeting:
    
    1. Review the quarterly budget
    2. Schedule investor call
    3. Update website content
    
    Also, please remember to:
    • Call the marketing agency
    • Send project timeline
    
    Thanks!
    """
    
    todos = manager.parse_email_for_todos(test_email_body, "test@example.com")
    print(f"📧 Extracted {len(todos)} todos from test email:")
    for i, todo in enumerate(todos, 1):
        print(f"  {i}. {todo}")
    
    # Add test todos
    for todo in todos:
        manager.add_todo(todo, "test@example.com", "Test Meeting Action Items")
    
    print(f"\n📋 Current todos: {len(manager.list_todos())}")
    
    # Complete a todo
    if manager.todos:
        manager.complete_todo(1)
        print("✅ Marked todo #1 as completed")

def main():
    """Main email processing function"""
    print("📧 Starting Email Todo Processing")
    print("=" * 40)
    
    manager = EmailTodoManager()
    
    if not manager.gmail_user or not manager.gmail_password:
        print("❌ Gmail credentials not configured in .env file")
        print("Please set GMAIL_USER and GMAIL_APP_PASSWORD")
        return False
    
    # Process new emails
    success = manager.process_emails()
    
    if success:
        print("\n📊 Email processing completed successfully")
        
        # Show summary
        pending = manager.list_todos('pending')
        completed = manager.list_todos('completed')
        
        print(f"📋 Current status:")
        print(f"   • Pending todos: {len(pending)}")
        print(f"   • Completed todos: {len(completed)}")
        
        return True
    else:
        print("❌ Email processing failed")
        return False

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == 'test':
        test_email_integration()
    else:
        main()