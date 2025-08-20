<?php
/**
 * CLICK TRACKING API
 * Place this file as: api/track-click.php
 */

require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $platform = $input['platform'] ?? null;
    $album_id = $input['album_id'] ?? null;
    $country = $input['country'] ?? 'unknown';
    
    if (!$platform) {
        throw new Exception('Platform is required');
    }
    
    // Get client information
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // If IP is local, try to get real IP
    if ($ip_address === '127.0.0.1' || $ip_address === '::1') {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                     $_SERVER['HTTP_X_REAL_IP'] ?? 
                     $_SERVER['HTTP_CLIENT_IP'] ?? 
                     $ip_address;
    }
    
    // Get country from IP if not provided
    if ($country === 'unknown') {
        $country = getUserCountryFromIP($ip_address);
    }
    
    // Check if streaming_clicks table exists, create if it doesn't
    try {
        $pdo->query("SELECT 1 FROM streaming_clicks LIMIT 1");
    } catch (Exception $e) {
        // Create the table
        $sql = "
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
        ";
        $pdo->exec($sql);
    }
    
    // Insert click record
    $stmt = $pdo->prepare("
        INSERT INTO streaming_clicks 
        (album_id, platform, country_code, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $album_id, 
        $platform, 
        $country, 
        $ip_address, 
        $user_agent
    ]);
    
    // Update popular albums table
    $stmt = $pdo->prepare("
        INSERT INTO popular_albums (album_id, total_clicks) 
        VALUES (?, 1) 
        ON DUPLICATE KEY UPDATE 
        total_clicks = total_clicks + 1
    ");
    $stmt->execute([$album_id]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Click tracked successfully',
        'data' => [
            'platform' => $platform,
            'album_id' => $album_id,
            'country' => $country,
            'timestamp' => date('c')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getUserCountryFromIP($ip) {
    // Skip local IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
        return 'US'; // Default for local development
    }
    
    // Try to get country from IP using a free service
    $country = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
    if ($country) {
        $data = json_decode($country, true);
        if (isset($data['countryCode']) && $data['countryCode']) {
            return $data['countryCode'];
        }
    }
    
    return 'unknown';
}
?>