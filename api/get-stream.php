<?php
/**
 * STREAMING LINKS API
 * Create a folder called 'api' in your root directory
 * Place this file as: api/get-stream.php
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

try {
    $album_id = $_GET['album_id'] ?? null;
    $platform = $_GET['platform'] ?? null;
    $country = $_GET['country'] ?? 'US';
    
    if (!$album_id) {
        throw new Exception('Album ID is required');
    }
    
    // If specific platform requested
    if ($platform) {
        $link = getStreamingLink($album_id, $platform, $country);
        
        if ($link) {
            echo json_encode([
                'success' => true,
                'data' => $link
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No streaming link found for this platform'
            ]);
        }
    } else {
        // Get all streaming links for the album
        $stmt = $pdo->prepare("
            SELECT * FROM streaming_links 
            WHERE album_id = ? AND (country_code = ? OR country_code = 'global') 
            ORDER BY country_code = ? DESC, platform
        ");
        $stmt->execute([$album_id, $country, $country]);
        $links = $stmt->fetchAll();
        
        // Group by platform, prioritizing country-specific links
        $grouped_links = [];
        foreach ($links as $link) {
            if (!isset($grouped_links[$link['platform']]) || $link['country_code'] === $country) {
                $grouped_links[$link['platform']] = $link;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_values($grouped_links),
            'country' => $country
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>