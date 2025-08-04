<?php
/**
 * Admin Login Page
 * Secure login for accessing analytics and registration data
 */

require_once 'auth.php';

$error = '';
$rateLimited = false;

// If already authenticated, redirect to admin panel
if (isAuthenticated()) {
    header('Location: admin.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    if (!checkRateLimit()) {
        $rateLimited = true;
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            $error = 'Invalid request. Please try again.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                if (authenticate($username, $password)) {
                    clearFailedAttempts();
                    header('Location: admin.php');
                    exit();
                } else {
                    recordFailedAttempt();
                    $error = 'Invalid username or password.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Vibe Tasking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error {
            background: #ffe6e6;
            color: #d63031;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #d63031;
            text-align: left;
        }

        .security-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9ff;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #666;
            text-align: left;
        }

        .security-icon {
            color: #667eea;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">VIBE TASKING</div>
        <div class="subtitle">Admin Panel Access</div>

        <?php if ($error): ?>
            <div class="error">
                <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       <?php echo $rateLimited ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       <?php echo $rateLimited ? 'disabled' : ''; ?>>
            </div>

            <button type="submit" class="login-btn" <?php echo $rateLimited ? 'disabled' : ''; ?>>
                🔐 Login to Admin Panel
            </button>
        </form>

        <div class="security-notice">
            <div><span class="security-icon">🔒</span> <strong>Security Notice:</strong></div>
            <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.8rem;">
                <li>All login attempts are logged</li>
                <li>Rate limiting: 5 attempts per 15 minutes</li>
                <li>Session timeout: 1 hour</li>
                <li>Authorized personnel only</li>
            </ul>
        </div>
    </div>

    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();

        // Handle form submission properly
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement.tagName !== 'BUTTON') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Add form submission debug
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submitting...');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password');
                return false;
            }
            
            // Show submitting state
            const submitBtn = document.querySelector('.login-btn');
            submitBtn.textContent = '🔄 Logging in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>