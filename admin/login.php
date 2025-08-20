<?php
/**
 * ADMIN LOGIN PAGE
 * Create a folder called 'admin' in your root directory
 * Place this file as: admin/login.php
 */

require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
                sleep(1); // Prevent brute force attacks
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Aurionix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .login-container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 50px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #e94560, #f27121);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #e94560;
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e94560, #f27121);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(233, 69, 96, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b7a;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-to-site a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-to-site a:hover {
            color: #e94560;
        }
        
        .security-note {
            background: rgba(233, 193, 30, 0.1);
            border: 1px solid rgba(233, 193, 30, 0.3);
            color: #ffd93d;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.5);
            cursor: pointer;
            font-size: 18px;
        }
        
        .password-group {
            position: relative;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                margin: 20px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🎵</div>
            <h1 class="login-title">Admin Panel</h1>
            <p class="login-subtitle">Aurionix Music Management</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-input" 
                       placeholder="admin@aurionix.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-group">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Enter your password"
                           required>
                    <button type="button" class="show-password" onclick="togglePassword()">👁️</button>
                </div>
            </div>
            
            <button type="submit" class="login-btn" id="loginBtn">
                Sign In
            </button>
        </form>
        
        <div class="security-note">
            🔒 This is a secure admin area. All login attempts are logged.
        </div>
        
        <div class="back-to-site">
            <a href="../">← Back to Website</a>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.show-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<span class="spinner"></span>Signing in...';
            btn.classList.add('loading');
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
        
        // Add enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && document.activeElement.type !== 'submit') {
                document.getElementById('loginForm').submit();
            }
        });
        
        // Security: Clear form on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>