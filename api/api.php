<?php
// File: /api/api.php
// Main API endpoint for your website

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Replace * with your domain in production
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';
require_once 'spotify_api.php';

// Get the action from URL parameter
$action = $_GET['action'] ?? '';

try {
    $spotify = new SpotifyAPI($pdo, SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET, SPOTIFY_ARTIST_ID);
    
    switch ($action) {
        case 'albums':
            // Get albums from database
            $albums = $spotify->getAlbums();
            echo json_encode([
                'success' => true, 
                'albums' => $albums,
                'count' => count($albums)
            ]);
            break;
            
        case 'update':
            // Update albums from Spotify (you might want to restrict this)
            $success = $spotify->updateAlbums();
            echo json_encode([
                'success' => $success, 
                'message' => $success ? 'Albums updated successfully' : 'Failed to update albums'
            ]);
            break;
            
        case 'status':
            // Get last update status
            $lastUpdate = $spotify->getLastUpdate();
            $albums = $spotify->getAlbums();
            echo json_encode([
                'success' => true,
                'last_update' => $lastUpdate,
                'album_count' => count($albums)
            ]);
            break;
            
        case 'health':
            // Health check
            echo json_encode([
                'success' => true,
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => phpversion()
            ]);
            break;

        case 'toptracks':
            // Get the artist's top tracks from Spotify
            $tracks = $spotify->getTopTracks();
            echo json_encode([
                'success' => true,
                'tracks' => $tracks,
                'count' => count($tracks)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action. Available actions: albums, update, status, health, toptracks'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>