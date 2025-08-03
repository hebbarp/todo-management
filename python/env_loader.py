#!/usr/bin/env python3
"""
Environment variable loader utility
Loads variables from .env file in project root
"""

import os
import sys
from pathlib import Path

def load_env():
    """Load environment variables from .env file"""
    # Find .env file in project root (go up from current script location)
    current_dir = Path(__file__).parent
    project_root = current_dir.parent
    env_file = project_root / '.env'
    
    if not env_file.exists():
        print(f"Warning: .env file not found at {env_file}")
        return False
    
    try:
        with open(env_file, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    key, value = line.split('=', 1)
                    os.environ[key.strip()] = value.strip()
        return True
    except Exception as e:
        print(f"Error loading .env file: {e}")
        return False

def get_env_var(key, required=True):
    """Get environment variable with error handling"""
    value = os.getenv(key)
    if required and not value:
        print(f"❌ Error: Required environment variable '{key}' not found")
        if not os.getenv(key.split('_')[0] + '_ACCOUNT_SID'):  # Check if any related vars exist
            print("Please check your .env file and ensure all required variables are set")
        sys.exit(1)
    return value

if __name__ == "__main__":
    # Test the loader
    if load_env():
        print("✅ Environment variables loaded successfully")
        print("Available environment variables:")
        for key in sorted(os.environ.keys()):
            if any(secret in key.upper() for secret in ['API', 'TOKEN', 'PASSWORD', 'KEY', 'SID']):
                print(f"  {key}=***")
    else:
        print("❌ Failed to load environment variables")