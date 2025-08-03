#!/usr/bin/env python3
"""
Test Twilio credentials from environment variables
"""

from env_loader import load_env, get_env_var

def test_credentials():
    """Test Twilio credentials from environment"""
    load_env()
    
    # Get credentials from environment
    account_sid = get_env_var('TWILIO_ACCOUNT_SID')
    auth_token = get_env_var('TWILIO_AUTH_TOKEN')
    
    print("🔧 TWILIO CREDENTIALS TEST")
    print("=" * 50)
    
    try:
        from twilio.rest import Client
        
        client = Client(account_sid, auth_token)
        
        # Test by getting account info
        account = client.api.accounts(account_sid).fetch()
        print(f"✅ Account verified: {account.friendly_name}")
        print(f"📊 Status: {account.status}")
        print(f"🆔 Account SID: {account_sid[:8]}...")
        
        # Test by listing phone numbers
        try:
            phone_numbers = client.incoming_phone_numbers.list(limit=5)
            print(f"📱 Available phone numbers: {len(phone_numbers)}")
            for number in phone_numbers[:3]:  # Show max 3
                print(f"   - {number.phone_number}")
        except Exception as e:
            print(f"⚠️  Could not fetch phone numbers: {e}")
            
        return True
        
    except ImportError:
        print("❌ Twilio library not installed")
        print("Install with: pip install twilio")
        return False
    except Exception as e:
        print(f"❌ Credential test failed: {e}")
        print("\nTroubleshooting:")
        print("- Check your Twilio credentials are correct")
        print("- Verify your .env file has TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN")
        print("- Ensure your Twilio account is active")
        return False

if __name__ == "__main__":
    success = test_credentials()
    if success:
        print("\n🎉 Twilio credentials are working!")
    else:
        print("\n❌ Twilio credentials test failed")
        exit(1)