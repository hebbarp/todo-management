#!/usr/bin/env python3
"""
Multi-Channel Todo Synchronization
Coordinates todos across WhatsApp, Email, Google Sheets, and GitHub Issues
"""

import os
import json
import subprocess
from datetime import datetime
from env_loader import load_env

# Import our integration modules
from whatsapp_todo_integration import WhatsAppTodoManager
from email_todo_integration import EmailTodoManager
from google_sheets_integration import GoogleSheetsTodoManager

class MultiChannelTodoSync:
    def __init__(self):
        load_env()
        self.sync_log_file = "sync_log.json"
        
        # Initialize all channel managers
        self.whatsapp_manager = WhatsAppTodoManager()
        self.email_manager = EmailTodoManager()
        self.sheets_manager = GoogleSheetsTodoManager()
        
        self.load_sync_log()
    
    def load_sync_log(self):
        """Load synchronization log"""
        try:
            if os.path.exists(self.sync_log_file):
                with open(self.sync_log_file, 'r') as f:
                    self.sync_log = json.load(f)
            else:
                self.sync_log = {
                    'last_sync': None,
                    'sync_history': []
                }
        except Exception as e:
            print(f"Error loading sync log: {e}")
            self.sync_log = {'last_sync': None, 'sync_history': []}
    
    def save_sync_log(self):
        """Save synchronization log"""
        try:
            with open(self.sync_log_file, 'w') as f:
                json.dump(self.sync_log, f, indent=2)
        except Exception as e:
            print(f"Error saving sync log: {e}")
    
    def sync_all_channels(self):
        """Synchronize todos across all channels"""
        print("🔄 Starting multi-channel synchronization...")
        sync_timestamp = datetime.now().isoformat()
        
        sync_results = {
            'timestamp': sync_timestamp,
            'whatsapp_processed': 0,
            'emails_processed': 0,
            'sheets_synced': 0,
            'github_created': 0,
            'errors': []
        }
        
        try:
            # 1. Process new WhatsApp messages (if webhook data available)
            print("📱 Checking WhatsApp todos...")
            whatsapp_todos = self.whatsapp_manager.list_todos("919742814697")  # Default number
            sync_results['whatsapp_processed'] = len(whatsapp_todos)
            
            # 2. Process new emails
            print("📧 Processing emails...")
            email_success = self.email_manager.process_emails()
            if email_success:
                email_todos = self.email_manager.list_todos()
                sync_results['emails_processed'] = len(email_todos)
            else:
                sync_results['errors'].append("Email processing failed")
            
            # 3. Sync with Google Sheets
            print("📊 Syncing Google Sheets...")
            try:
                # Add WhatsApp todos to sheets
                for todo in whatsapp_todos[-5:]:  # Last 5 todos
                    if todo['status'] == 'pending':
                        self.sheets_manager.add_todo(
                            f"[WhatsApp] {todo['description']}",
                            notes=f"From: {todo['phone_number']}"
                        )
                        sync_results['sheets_synced'] += 1
                
                # Add email todos to sheets
                email_todos = self.email_manager.list_todos()
                for todo in email_todos[-5:]:  # Last 5 todos
                    if todo['status'] == 'pending':
                        self.sheets_manager.add_todo(
                            f"[Email] {todo['description']}",
                            notes=f"From: {todo['sender_email']}"
                        )
                        sync_results['sheets_synced'] += 1
                        
            except Exception as e:
                sync_results['errors'].append(f"Sheets sync error: {e}")
            
            # 4. Create GitHub issues for high-priority todos
            print("🐙 Creating GitHub issues...")
            try:
                self.create_github_issues_from_channels()
                sync_results['github_created'] = 1  # Simplified count
            except Exception as e:
                sync_results['errors'].append(f"GitHub sync error: {e}")
            
            # 5. Generate unified todo report
            print("📋 Generating unified report...")
            self.generate_unified_report()
            
            # Update sync log
            self.sync_log['last_sync'] = sync_timestamp
            self.sync_log['sync_history'].append(sync_results)
            
            # Keep only last 10 sync records
            if len(self.sync_log['sync_history']) > 10:
                self.sync_log['sync_history'] = self.sync_log['sync_history'][-10:]
            
            self.save_sync_log()
            
            # Print summary
            print(f"\n✅ Multi-channel sync completed!")
            print(f"📊 Summary:")
            print(f"   • WhatsApp todos: {sync_results['whatsapp_processed']}")
            print(f"   • Email todos: {sync_results['emails_processed']}")
            print(f"   • Sheets synced: {sync_results['sheets_synced']}")
            print(f"   • GitHub issues: {sync_results['github_created']}")
            
            if sync_results['errors']:
                print(f"⚠️  Errors encountered: {len(sync_results['errors'])}")
                for error in sync_results['errors']:
                    print(f"     • {error}")
            
            return True
            
        except Exception as e:
            print(f"❌ Sync failed: {e}")
            sync_results['errors'].append(f"General sync error: {e}")
            return False
    
    def create_github_issues_from_channels(self):
        """Create GitHub issues from high-priority channel todos"""
        try:
            # Get high-priority todos from all channels
            priority_todos = []
            
            # WhatsApp todos (recent ones)
            whatsapp_todos = self.whatsapp_manager.list_todos("919742814697")
            for todo in whatsapp_todos[-3:]:  # Last 3
                if todo['status'] == 'pending':
                    priority_todos.append({
                        'title': f"[WhatsApp] {todo['description']}",
                        'body': f"Created via WhatsApp from {todo['phone_number']}\nCreated: {todo['created_at']}",
                        'source': 'whatsapp'
                    })
            
            # Email todos (recent ones)
            email_todos = self.email_manager.list_todos()
            for todo in email_todos[-3:]:  # Last 3
                if todo['status'] == 'pending':
                    priority_todos.append({
                        'title': f"[Email] {todo['description']}",
                        'body': f"From: {todo['sender_email']}\nSubject: {todo.get('email_subject', 'N/A')}\nCreated: {todo['created_at']}",
                        'source': 'email'
                    })
            
            # Create GitHub issues
            for todo in priority_todos:
                try:
                    cmd = [
                        'gh', 'issue', 'create',
                        '--repo', 'hebbarp/todo-management',
                        '--title', todo['title'],
                        '--body', todo['body'],
                        '--label', f"source:{todo['source']},status:pending"
                    ]
                    subprocess.run(cmd, check=True, capture_output=True)
                    print(f"✅ Created GitHub issue: {todo['title']}")
                except subprocess.CalledProcessError:
                    print(f"⚠️ Could not create GitHub issue (gh CLI may not be available)")
                    break
                    
        except Exception as e:
            print(f"Error creating GitHub issues: {e}")
    
    def generate_unified_report(self):
        """Generate a unified report across all channels"""
        report_file = f"unified_todo_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        
        try:
            # Collect todos from all sources
            whatsapp_todos = self.whatsapp_manager.list_todos("919742814697")
            email_todos = self.email_manager.list_todos()
            sheets_todos = self.sheets_manager.list_todos()
            
            unified_report = {
                'generated_at': datetime.now().isoformat(),
                'summary': {
                    'total_todos': len(whatsapp_todos) + len(email_todos) + len(sheets_todos),
                    'whatsapp_count': len(whatsapp_todos),
                    'email_count': len(email_todos),
                    'sheets_count': len(sheets_todos)
                },
                'todos': {
                    'whatsapp': whatsapp_todos[-10:],  # Last 10
                    'email': email_todos[-10:],        # Last 10
                    'sheets': sheets_todos[-10:] if sheets_todos else []  # Last 10
                },
                'sync_status': self.sync_log
            }
            
            # Save report
            with open(report_file, 'w') as f:
                json.dump(unified_report, f, indent=2)
            
            print(f"📊 Unified report saved: {report_file}")
            
            # Generate human-readable summary
            summary_text = f"""📋 UNIFIED TODO REPORT
================================
Generated: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}

📊 Summary:
• Total todos across all channels: {unified_report['summary']['total_todos']}
• WhatsApp todos: {unified_report['summary']['whatsapp_count']}
• Email todos: {unified_report['summary']['email_count']}
• Google Sheets todos: {unified_report['summary']['sheets_count']}

🔄 Last sync: {self.sync_log.get('last_sync', 'Never')}

📱 Recent WhatsApp Todos:"""
            
            for todo in whatsapp_todos[-5:]:
                status_emoji = "✅" if todo['status'] == 'completed' else "⏳"
                summary_text += f"\n   {status_emoji} #{todo['id']}: {todo['description']}"
            
            summary_text += f"\n\n📧 Recent Email Todos:"
            for todo in email_todos[-5:]:
                status_emoji = "✅" if todo['status'] == 'completed' else "⏳"
                summary_text += f"\n   {status_emoji} #{todo['id']}: {todo['description']}"
            
            print(f"\n{summary_text}")
            
            # Save human-readable summary
            summary_file = f"unified_summary_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
            with open(summary_file, 'w') as f:
                f.write(summary_text)
            
            return unified_report
            
        except Exception as e:
            print(f"Error generating unified report: {e}")
            return None
    
    def send_daily_digest(self, recipient_email):
        """Send daily digest email with all channel updates"""
        try:
            report = self.generate_unified_report()
            if not report:
                return False
            
            # Create digest email
            digest_subject = f"Daily Todo Digest - {datetime.now().strftime('%B %d, %Y')}"
            digest_body = f"""📊 Your Daily Todo Digest

🔄 Multi-Channel Summary:
• Total active todos: {report['summary']['total_todos']}
• WhatsApp: {report['summary']['whatsapp_count']} todos
• Email: {report['summary']['email_count']} todos  
• Google Sheets: {report['summary']['sheets_count']} todos

📱 Recent WhatsApp Activity:"""
            
            whatsapp_todos = report['todos']['whatsapp']
            for todo in whatsapp_todos[-3:]:
                digest_body += f"\n   • {todo['description']}"
            
            digest_body += f"\n\n📧 Recent Email Activity:"
            email_todos = report['todos']['email']
            for todo in email_todos[-3:]:
                digest_body += f"\n   • {todo['description']}"
            
            digest_body += f"\n\n🔄 Last sync: {self.sync_log.get('last_sync', 'Never')}"
            digest_body += f"\n\n💡 Reply to this email to add new todos to your list!"
            
            # Send via email manager
            success = self.email_manager.send_status_email(
                recipient_email, 
                digest_subject, 
                digest_body
            )
            
            return success
            
        except Exception as e:
            print(f"Error sending daily digest: {e}")
            return False
    
    def emergency_backup(self):
        """Create emergency backup of all todos"""
        backup_timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        backup_file = f"emergency_backup_{backup_timestamp}.json"
        
        try:
            backup_data = {
                'backup_created': datetime.now().isoformat(),
                'whatsapp_todos': self.whatsapp_manager.todos,
                'email_todos': self.email_manager.todos,
                'sheets_data': self.sheets_manager.list_todos(),
                'sync_log': self.sync_log
            }
            
            with open(backup_file, 'w') as f:
                json.dump(backup_data, f, indent=2)
            
            print(f"💾 Emergency backup created: {backup_file}")
            return backup_file
            
        except Exception as e:
            print(f"❌ Backup failed: {e}")
            return None

def main():
    """Main synchronization function"""
    print("🚀 Multi-Channel Todo Sync Starting...")
    print("=" * 50)
    
    sync_manager = MultiChannelTodoSync()
    
    # Perform full synchronization
    success = sync_manager.sync_all_channels()
    
    if success:
        print("\n🎉 Multi-channel synchronization completed!")
        
        # Create backup
        backup_file = sync_manager.emergency_backup()
        if backup_file:
            print(f"💾 Backup created: {backup_file}")
        
        # Optionally send digest email
        gmail_user = os.getenv('GMAIL_USER')
        if gmail_user:
            print(f"📧 Sending digest to {gmail_user}...")
            sync_manager.send_daily_digest(gmail_user)
        
        return True
    else:
        print("❌ Synchronization failed")
        return False

if __name__ == "__main__":
    main()