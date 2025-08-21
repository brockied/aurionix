<?php
/**
 * CLICK LOGGING API - COMPLETE FILE
 * Logs streaming platform clicks for analytics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (!isset($input['album_id']) || !isset($input['platform'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Create analytics table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS analytics_clicks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT NOT NULL,
            track_id INT NULL,
            platform VARCHAR(100) NOT NULL,
            click_type ENUM('album', 'track') DEFAULT 'album',
            user_ip VARCHAR(45),
            user_agent TEXT,
            referrer VARCHAR(500),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_album_platform (album_id, platform),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Prepare data
    $album_id = (int)$input['album_id'];
    $track_id = isset($input['track_id']) ? (int)$input['track_id'] : null;
    $platform = substr($input['platform'], 0, 100); // Limit length
    $click_type = isset($input['type']) ? $input['type'] : 'album';
    
    // Get client information
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    
    // Handle forwarded IPs (if behind proxy)
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $user_ip = trim($forwarded_ips[0]);
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $user_ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    // Truncate long fields
    if ($user_agent && strlen($user_agent) > 1000) {
        $user_agent = substr($user_agent, 0, 1000);
    }
    if ($referrer && strlen($referrer) > 500) {
        $referrer = substr($referrer, 0, 500);
    }
    
    // Insert the click log
    $stmt = $pdo->prepare("
        INSERT INTO analytics_clicks 
        (album_id, track_id, platform, click_type, user_ip, user_agent, referrer) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $album_id,
        $track_id,
        $platform,
        $click_type,
        $user_ip,
        $user_agent,
        $referrer
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Click logged successfully',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Click logging error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>