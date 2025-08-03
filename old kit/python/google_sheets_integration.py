#!/usr/bin/env python3
"""
Google Sheets Todo Integration
Syncs todos with Google Sheets for mobile-friendly todo management
"""

import os
import json
from datetime import datetime
from env_loader import load_env

# Note: This is a simplified version using CSV for demonstration
# For actual Google Sheets API integration, you would need:
# 1. Google Sheets API credentials
# 2. google-api-python-client library
# 3. OAuth2 authentication setup

class GoogleSheetsTodoManager:
    def __init__(self, sheet_file="google_sheets_todos.csv"):
        load_env()
        self.sheet_file = sheet_file
        self.initialize_sheet()
    
    def initialize_sheet(self):
        """Initialize the CSV file that simulates Google Sheets"""
        if not os.path.exists(self.sheet_file):
            with open(self.sheet_file, 'w') as f:
                # Create header row
                f.write("ID,Todo Item,Status,Date Added,Due Date,Priority,Notes\n")
    
    def add_todo(self, description, due_date="", priority="Medium", notes=""):
        """Add a new todo to the sheet"""
        todo_id = self.get_next_id()
        date_added = datetime.now().strftime("%Y-%m-%d")
        
        with open(self.sheet_file, 'a') as f:
            f.write(f"{todo_id},{description},Pending,{date_added},{due_date},{priority},{notes}\n")
        
        print(f"✅ Added todo #{todo_id}: {description}")
        return todo_id
    
    def get_next_id(self):
        """Get the next available ID"""
        try:
            with open(self.sheet_file, 'r') as f:
                lines = f.readlines()
                if len(lines) <= 1:  # Only header or empty
                    return 1
                # Get the last ID and increment
                last_line = lines[-1].strip()
                if last_line:
                    last_id = int(last_line.split(',')[0])
                    return last_id + 1
        except (FileNotFoundError, ValueError, IndexError):
            pass
        return 1
    
    def complete_todo(self, todo_id):
        """Mark a todo as completed"""
        return self.update_todo_status(todo_id, "Completed")
    
    def update_todo_status(self, todo_id, new_status):
        """Update the status of a todo"""
        try:
            with open(self.sheet_file, 'r') as f:
                lines = f.readlines()
            
            updated = False
            for i, line in enumerate(lines):
                if i == 0:  # Skip header
                    continue
                
                parts = line.strip().split(',')
                if len(parts) >= 3 and parts[0] == str(todo_id):
                    parts[2] = new_status  # Update status column
                    lines[i] = ','.join(parts) + '\n'
                    updated = True
                    break
            
            if updated:
                with open(self.sheet_file, 'w') as f:
                    f.writelines(lines)
                print(f"✅ Updated todo #{todo_id} status to: {new_status}")
                return True
            else:
                print(f"❌ Todo #{todo_id} not found")
                return False
                
        except Exception as e:
            print(f"❌ Error updating todo: {e}")
            return False
    
    def list_todos(self, status_filter=None):
        """List todos with optional status filter"""
        try:
            todos = []
            with open(self.sheet_file, 'r') as f:
                lines = f.readlines()
            
            for i, line in enumerate(lines):
                if i == 0:  # Skip header
                    continue
                
                parts = line.strip().split(',')
                if len(parts) >= 6:
                    todo = {
                        'id': parts[0],
                        'description': parts[1],
                        'status': parts[2],
                        'date_added': parts[3],
                        'due_date': parts[4],
                        'priority': parts[5],
                        'notes': parts[6] if len(parts) > 6 else ""
                    }
                    
                    if status_filter is None or todo['status'].lower() == status_filter.lower():
                        todos.append(todo)
            
            return todos
            
        except Exception as e:
            print(f"❌ Error listing todos: {e}")
            return []
    
    def sync_from_other_sources(self, whatsapp_todos=None, github_issues=None):
        """Sync todos from other sources (WhatsApp, GitHub, etc.)"""
        synced_count = 0
        
        # Sync from WhatsApp todos
        if whatsapp_todos:
            for todo in whatsapp_todos:
                if todo['status'] == 'pending':
                    self.add_todo(
                        description=f"[WhatsApp] {todo['description']}",
                        notes=f"From WhatsApp: {todo['phone_number']}"
                    )
                    synced_count += 1
        
        # Sync from GitHub issues (would require GitHub API)
        if github_issues:
            for issue in github_issues:
                self.add_todo(
                    description=f"[GitHub] {issue['title']}",
                    notes=f"Issue #{issue['number']}"
                )
                synced_count += 1
        
        print(f"📊 Synced {synced_count} todos from other sources")
        return synced_count
    
    def export_to_json(self, filename="sheets_todos_export.json"):
        """Export todos to JSON format"""
        todos = self.list_todos()
        
        with open(filename, 'w') as f:
            json.dump(todos, f, indent=2)
        
        print(f"📤 Exported {len(todos)} todos to {filename}")
        return filename
    
    def generate_summary_report(self):
        """Generate a summary report of todos"""
        all_todos = self.list_todos()
        pending_todos = self.list_todos("pending")
        completed_todos = self.list_todos("completed")
        
        # Priority breakdown
        priority_count = {}
        for todo in all_todos:
            priority = todo['priority']
            priority_count[priority] = priority_count.get(priority, 0) + 1
        
        report = f"""📊 GOOGLE SHEETS TODO SUMMARY REPORT
================================
Generated: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}

📋 Overview:
• Total Todos: {len(all_todos)}
• Pending: {len(pending_todos)}
• Completed: {len(completed_todos)}
• Completion Rate: {(len(completed_todos)/len(all_todos)*100):.1f}% ({len(completed_todos)}/{len(all_todos)})

🎯 Priority Breakdown:
"""
        
        for priority, count in priority_count.items():
            report += f"• {priority}: {count}\n"
        
        report += f"\n📅 Recent Pending Todos:\n"
        for todo in pending_todos[-5:]:  # Last 5 pending
            report += f"• #{todo['id']}: {todo['description']}\n"
        
        print(report)
        return report
    
    def mobile_friendly_view(self):
        """Generate a mobile-friendly view of todos"""
        pending_todos = self.list_todos("pending")
        
        if not pending_todos:
            return "🎉 No pending todos! You're all caught up!"
        
        mobile_view = "📱 YOUR TODOS (Mobile View)\n"
        mobile_view += "=" * 30 + "\n\n"
        
        for i, todo in enumerate(pending_todos[:10], 1):  # Show top 10
            status_emoji = "⏳" if todo['status'] == "Pending" else "✅"
            priority_emoji = {"High": "🔥", "Medium": "📋", "Low": "📝"}.get(todo['priority'], "📋")
            
            mobile_view += f"{priority_emoji} {status_emoji} #{todo['id']}\n"
            mobile_view += f"   {todo['description']}\n"
            
            if todo['due_date']:
                mobile_view += f"   📅 Due: {todo['due_date']}\n"
            
            mobile_view += "\n"
        
        if len(pending_todos) > 10:
            mobile_view += f"... and {len(pending_todos) - 10} more todos\n"
        
        return mobile_view

# Actual Google Sheets API Integration (commented out - requires setup)
"""
To use real Google Sheets API, you would need:

1. Install dependencies:
   pip install google-api-python-client google-auth google-auth-oauthlib

2. Set up Google Cloud Project and enable Sheets API

3. Download credentials.json file

4. Use this code structure:

from googleapiclient.discovery import build
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request

class RealGoogleSheetsManager:
    def __init__(self, spreadsheet_id, range_name="Sheet1!A:G"):
        self.spreadsheet_id = spreadsheet_id
        self.range_name = range_name
        self.service = self.authenticate()
    
    def authenticate(self):
        creds = None
        # Authentication logic here
        service = build('sheets', 'v4', credentials=creds)
        return service
    
    def read_sheet(self):
        result = self.service.spreadsheets().values().get(
            spreadsheetId=self.spreadsheet_id,
            range=self.range_name
        ).execute()
        return result.get('values', [])
    
    def write_to_sheet(self, values):
        body = {'values': values}
        result = self.service.spreadsheets().values().append(
            spreadsheetId=self.spreadsheet_id,
            range=self.range_name,
            valueInputOption='RAW',
            body=body
        ).execute()
        return result
"""

def main():
    """Test the Google Sheets integration"""
    print("🧪 Testing Google Sheets Todo Integration")
    print("=" * 40)
    
    manager = GoogleSheetsTodoManager()
    
    # Add some test todos
    manager.add_todo("Review quarterly budget", "2025-02-15", "High", "Finance review")
    manager.add_todo("Call investor meeting", "2025-02-10", "High", "Important call")
    manager.add_todo("Update website content", "2025-02-20", "Medium", "Marketing task")
    manager.add_todo("Plan team outing", "2025-02-25", "Low", "HR task")
    
    print("\n📋 All Todos:")
    todos = manager.list_todos()
    for todo in todos:
        print(f"  #{todo['id']}: {todo['description']} ({todo['status']})")
    
    # Complete a todo
    print(f"\n✅ Completing todo #2...")
    manager.complete_todo(2)
    
    # Show mobile view
    print(f"\n{manager.mobile_friendly_view()}")
    
    # Generate report
    print(f"\n{manager.generate_summary_report()}")
    
    # Export to JSON
    manager.export_to_json()

if __name__ == "__main__":
    main()