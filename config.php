<?php
/**
 * AURIONIX WEBSITE CONFIGURATION - DEBUG VERSION
 * Place in root directory (public_html/)
 */

// Enable error reporting FIRST (before anything else)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Configuration - UPDATE THESE WITH YOUR ACTUAL VALUES
define('DB_HOST', 'localhost'); // Usually 'localhost'
define('DB_USERNAME', 'aurioni1_davbro'); // Replace with your database username
define('DB_PASSWORD', '2RkVAsArXpociLJB'); // Replace with your database password
define('DB_NAME', 'aurioni1_aurionix'); // Replace with your actual database name

// Site Configuration
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('ADMIN_PATH', '/admin');
define('UPLOAD_PATH', '/uploads/');

// Security
define('SESSION_NAME', 'aurionix_session');
session_name(SESSION_NAME);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Test basic PHP functionality
echo "<!-- PHP is working - Config loaded -->\n";

// Database Connection with better error handling
try {
    echo "<!-- Attempting database connection -->\n";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<!-- Database connected successfully -->\n";
} catch(PDOException $e) {
    // More detailed error information
    echo "<!-- Database connection failed -->\n";
    echo "<div style='background:red;color:white;padding:20px;margin:20px;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Username:</strong> " . DB_USERNAME . "</p>";
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Check if your database credentials are correct</li>";
    echo "<li>Ensure the database exists</li>";
    echo "<li>Verify the database user has proper permissions</li>";
    echo "<li>Check if MySQL/MariaDB is running</li>";
    echo "<li>Run the install.php file if you haven't set up the database yet</li>";
    echo "</ul>";
    echo "</div>";
    die();
}

// Helper Functions
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(Exception $e) {
        error_log("getSetting error: " . $e->getMessage());
        return $default;
    }
}

function updateSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch(Exception $e) {
        error_log("updateSetting error: " . $e->getMessage());
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function getUserCountry() {
    // Simplified version to avoid external API calls during debugging
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
        return 'US'; // Default for localhost/local networks
    }
    
    // Try external API but don't fail if it doesn't work
    try {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $country = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $context);
        if ($country) {
            $data = json_decode($country, true);
            return $data['countryCode'] ?? 'US';
        }
    } catch(Exception $e) {
        error_log("getUserCountry error: " . $e->getMessage());
    }
    return 'US'; // Default fallback
}

function getStreamingLink($albumId, $platform, $country = null) {
    global $pdo;
    try {
        if (!$country) $country = getUserCountry();
        
        $stmt = $pdo->prepare("SELECT * FROM streaming_links WHERE album_id = ? AND platform = ? AND (country_code = ? OR country_code = 'global') ORDER BY country_code = ? DESC LIMIT 1");
        $stmt->execute([$albumId, $platform, $country, $country]);
        return $stmt->fetch();
    } catch(Exception $e) {
        error_log("getStreamingLink error: " . $e->getMessage());
        return false;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
}

function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $seconds);
}

// Set timezone
date_default_timezone_set('UTC');

echo "<!-- Config.php loaded successfully -->\n";
?>