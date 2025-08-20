<?php
/**
 * AURIONIX WEBSITE INSTALLER (UPDATED)
 * Place this file in your website's root directory (public_html/)
 * Run it once to set up the database and admin account
 * Delete this file after installation for security
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $host = $_POST['db_host'];
    $username = $_POST['db_username'];
    $password = $_POST['db_password'];
    $database = $_POST['db_name'];
    $admin_email = $_POST['admin_email'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
        $pdo->exec("USE `$database`");
        
        // Create all tables including analytics
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS albums (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            release_date DATE,
            cover_image VARCHAR(500),
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_featured (featured),
            INDEX idx_release_date (release_date)
        );

        CREATE TABLE IF NOT EXISTS tracks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            title VARCHAR(255) NOT NULL,
            duration VARCHAR(10),
            track_number INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
            INDEX idx_album_track (album_id, track_number)
        );

        CREATE TABLE IF NOT EXISTS streaming_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            platform VARCHAR(100) NOT NULL,
            url VARCHAR(500) NOT NULL,
            country_code VARCHAR(10) DEFAULT 'global',
            embed_code TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
            INDEX idx_album_platform (album_id, platform),
            INDEX idx_country (country_code)
        );

        CREATE TABLE IF NOT EXISTS streaming_clicks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            platform VARCHAR(100) NOT NULL,
            country_code VARCHAR(10) DEFAULT 'unknown',
            ip_address VARCHAR(45),
            user_agent TEXT,
            clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
            INDEX idx_album_platform (album_id, platform),
            INDEX idx_clicked_at (clicked_at),
            INDEX idx_country (country_code)
        );

        CREATE TABLE IF NOT EXISTS page_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_url VARCHAR(500) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            country_code VARCHAR(10) DEFAULT 'unknown',
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page_url (page_url),
            INDEX idx_viewed_at (viewed_at),
            INDEX idx_country (country_code)
        );

        CREATE TABLE IF NOT EXISTS popular_albums (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            total_clicks INT DEFAULT 0,
            total_views INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
            UNIQUE KEY unique_album (album_id)
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        ";
        
        $pdo->exec($sql);
        
        // Insert admin user
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$admin_email, $admin_password]);
        
        // Insert default settings (including analytics settings)
        $settings = [
            ['artist_name', 'Aurionix'],
            ['site_title', 'Aurionix - Official Music'],
            ['site_description', 'Official website of Aurionix - Electronic Music Artist'],
            ['hero_title', 'THE WORLD\'S LEADING BEAT MARKETPLACE'],
            ['hero_subtitle', 'The brand of choice for the next generation of musicians and beat makers.'],
            ['contact_email', ''],
            ['social_facebook', ''],
            ['social_twitter', ''],
            ['social_instagram', ''],
            ['social_youtube', ''],
            ['social_spotify', ''],
            ['social_soundcloud', ''],
            ['analytics_google', ''],
            ['analytics_facebook', ''],
            ['analytics_enabled', '1'],
            ['track_clicks', '1'],
            ['track_page_views', '1'],
            ['privacy_mode', '0'],
            ['data_retention_days', '365'],
            ['seo_keywords', 'electronic music, beats, producer, music artist'],
            ['footer_text', '© 2024 Aurionix. All rights reserved.'],
            ['maintenance_mode', '0']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        // Create config file
        $config_content = "<?php
// Database Configuration
define('DB_HOST', '$host');
define('DB_USERNAME', '$username');
define('DB_PASSWORD', '$password');
define('DB_NAME', '$database');

// Site Configuration
define('SITE_URL', 'https://' . \$_SERVER['HTTP_HOST']);
define('ADMIN_PATH', '/admin');
define('UPLOAD_PATH', '/uploads/');

// Security
define('SESSION_NAME', 'aurionix_session');
session_name(SESSION_NAME);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}

// Helper Functions
function getSetting(\$key, \$default = '') {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"SELECT setting_value FROM settings WHERE setting_key = ?\");
    \$stmt->execute([\$key]);
    \$result = \$stmt->fetch();
    return \$result ? \$result['setting_value'] : \$default;
}

function updateSetting(\$key, \$value) {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)\");
    return \$stmt->execute([\$key, \$value]);
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']) && isset(\$_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && \$_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function getUserCountry() {
    // Simple IP-based country detection
    \$ip = \$_SERVER['REMOTE_ADDR'];
    if (\$ip === '127.0.0.1' || \$ip === '::1') {
        return 'US'; // Default for localhost
    }
    
    // Use a free IP geolocation service
    \$country = @file_get_contents(\"http://ip-api.com/json/{\$ip}?fields=countryCode\");
    if (\$country) {
        \$data = json_decode(\$country, true);
        return \$data['countryCode'] ?? 'US';
    }
    return 'US'; // Default fallback
}

function getStreamingLink(\$albumId, \$platform, \$country = null) {
    global \$pdo;
    if (!\$country) \$country = getUserCountry();
    
    // Try to get country-specific link first
    \$stmt = \$pdo->prepare(\"SELECT * FROM streaming_links WHERE album_id = ? AND platform = ? AND (country_code = ? OR country_code = 'global') ORDER BY country_code = ? DESC LIMIT 1\");
    \$stmt->execute([\$albumId, \$platform, \$country, \$country]);
    return \$stmt->fetch();
}

function sanitizeInput(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

function generateSlug(\$text) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', \$text), '-'));
}

function formatDuration(\$seconds) {
    \$minutes = floor(\$seconds / 60);
    \$seconds = \$seconds % 60;
    return sprintf('%d:%02d', \$minutes, \$seconds);
}

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
?>";
        
        file_put_contents('config.php', $config_content);
        
        // Create uploads directories
        $upload_dirs = [
            'uploads/albums',
            'uploads/settings',
            'uploads/tracks'
        ];
        
        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Create a default .htaccess file for uploads
        $htaccess_content = "# Protect uploads directory
Options -Indexes
<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)\$\">
    Order allow,deny
    Deny from all
</Files>
";
        file_put_contents('uploads/.htaccess', $htaccess_content);
        
        $success = true;
        $message = "Installation completed successfully! 
                   <br><br>
                   <strong>✅ Database created with all tables including analytics</strong>
                   <br>✅ Admin user created
                   <br>✅ Default settings configured
                   <br>✅ Upload directories created
                   <br><br>
                   <a href='/admin' style='color: #e94560; text-decoration: none; font-weight: bold;'>👉 Go to Admin Panel</a>";
        
    } catch (Exception $e) {
        $success = false;
        $message = "Installation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurionix Website Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white;
        }
        .container { 
            background: rgba(255,255,255,0.1); 
            padding: 40px; 
            border-radius: 15px; 
            backdrop-filter: blur(10px);
            max-width: 500px; 
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        h1 { text-align: center; margin-bottom: 30px; color: #e94560; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; 
            padding: 12px; 
            border: none; 
            border-radius: 8px; 
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        input::placeholder { color: rgba(255,255,255,0.7); }
        .btn { 
            width: 100%; 
            padding: 15px; 
            background: linear-gradient(45deg, #e94560, #f27121);
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: bold;
        }
        .btn:hover { transform: translateY(-2px); }
        .message { 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center;
            line-height: 1.6;
        }
        .success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; }
        .error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; }
        .features {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .features h3 {
            margin-bottom: 15px;
            color: #e94560;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 5px 0;
            color: rgba(255,255,255,0.8);
        }
        .features li::before {
            content: "✅ ";
            margin-right: 8px;
        }
        .warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Aurionix Website Installer</h1>
        
        <?php if (isset($success)): ?>
            <div class="message <?= $success ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
            
            <?php if ($success): ?>
                <div class="warning">
                    <strong>⚠️ Security Note:</strong> Please delete this installer file (install.php) now for security.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!isset($success) || !$success): ?>
            <div class="features">
                <h3>What's Included</h3>
                <ul>
                    <li>Complete music website with admin panel</li>
                    <li>Album and track management</li>
                    <li>Streaming platform integration</li>
                    <li>Analytics and click tracking</li>
                    <li>Mobile-responsive design</li>
                    <li>SEO optimization</li>
                    <li>Security features</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Database Host:</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label>Database Username:</label>
                    <input type="text" name="db_username" required>
                </div>
                
                <div class="form-group">
                    <label>Database Password:</label>
                    <input type="password" name="db_password">
                </div>
                
                <div class="form-group">
                    <label>Database Name:</label>
                    <input type="text" name="db_name" value="aurionix_db" required>
                </div>
                
                <div class="form-group">
                    <label>Admin Email:</label>
                    <input type="email" name="admin_email" required>
                </div>
                
                <div class="form-group">
                    <label>Admin Password:</label>
                    <input type="password" name="admin_password" required>
                </div>
                
                <button type="submit" name="install" class="btn">🚀 Install Website</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>