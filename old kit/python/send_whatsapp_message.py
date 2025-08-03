#!/usr/bin/env python3
"""
WhatsApp Message Sender
Opens WhatsApp Web with pre-filled message
"""

import sys
import webbrowser
import urllib.parse
from env_loader import load_env

def send_whatsapp_web(phone_number, message):
    """
    Open WhatsApp Web with pre-filled message
    
    Args:
        phone_number (str): Phone number with country code (e.g., "919742814697")
        message (str): Message to send
    """
    # Ensure phone number format
    if not phone_number.startswith('+'):
        if phone_number.startswith('91') and len(phone_number) == 12:
            phone_number = f"+{phone_number}"
        else:
            phone_number = f"+91{phone_number}"
    
    # Remove + for WhatsApp Web URL
    clean_number = phone_number.replace('+', '')
    
    # Encode message for URL
    encoded_message = urllib.parse.quote(message)
    
    # WhatsApp Web URL
    whatsapp_url = f"https://web.whatsapp.com/send?phone={clean_number}&text={encoded_message}"
    
    print("📱 WHATSAPP MESSAGE SENDER")
    print("=" * 50)
    print(f"📞 To: {phone_number}")
    print("🌐 Opening WhatsApp Web...")
    print("\n📝 Message preview:")
    print("-" * 40)
    print(message)
    print("-" * 40)
    print("\n✅ WhatsApp Web will open in your browser")
    print("📋 Message will be pre-filled - just click Send!")
    print("⚠️  Make sure you're logged into WhatsApp Web")
    
    # Open WhatsApp Web
    webbrowser.open(whatsapp_url)
    return True

def main():
    """Main function"""
    load_env()
    
    if len(sys.argv) < 3:
        print("Usage: python3 send_whatsapp_message.py <phone_number> <message>")
        print("Example: python3 send_whatsapp_message.py 919742814697 'Hello from Python!'")
        sys.exit(1)
    
    phone_number = sys.argv[1]
    message = sys.argv[2]
    
    success = send_whatsapp_web(phone_number, message)
    
    if success:
        print("\n🎉 WhatsApp Web opened successfully!")
    else:
        print("\n❌ Failed to open WhatsApp Web")
        sys.exit(1)

if __name__ == "__main__":
    main()