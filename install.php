<?php
/**
 * AURIONIX WEBSITE INSTALLER
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
        
        // Create tables
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS albums (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            release_date DATE,
            cover_image VARCHAR(500),
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS tracks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            title VARCHAR(255) NOT NULL,
            duration VARCHAR(10),
            track_number INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS streaming_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT,
            platform VARCHAR(100) NOT NULL,
            url VARCHAR(500) NOT NULL,
            country_code VARCHAR(10) DEFAULT 'global',
            embed_code TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
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
        
        // Insert default settings
        $settings = [
            ['artist_name', 'Aurionix'],
            ['site_title', 'Aurionix - Official Music'],
            ['site_description', 'Official website of Aurionix - Electronic Music Artist'],
            ['hero_title', 'THE WORLD\'S LEADING BEAT MARKETPLACE'],
            ['hero_subtitle', 'The brand of choice for the next generation of musicians and beat makers.']
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

// Security
define('SESSION_NAME', 'aurionix_session');
session_name(SESSION_NAME);
session_start();

// Database Connection
try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDO Exception \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
?>";
        
        file_put_contents('config.php', $config_content);
        
        $success = true;
        $message = "Installation completed successfully! Admin panel: <a href='/admin'>Login here</a>";
        
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
        }
        .success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; }
        .error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Aurionix Website Installer</h1>
        
        <?php if (isset($success)): ?>
            <div class="message <?= $success ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($success) || !$success): ?>
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
            
            <button type="submit" name="install" class="btn">Install Website</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>