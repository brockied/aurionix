<?php
/**
 * ALBUM INFO API - COMPLETE FILE
 * Returns album information for the audio player
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Album ID required']);
    exit;
}

$album_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, cover_image, release_date, 
               featured, play_type, slug
        FROM albums 
        WHERE id = ?
    ");
    $stmt->execute([$album_id]);
    $album = $stmt->fetch();
    
    if (!$album) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Album not found']);
        exit;
    }
    
    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'id' => (int)$album['id'],
            'title' => $album['title'],
            'description' => $album['description'],
            'cover_image' => $album['cover_image'],
            'release_date' => $album['release_date'],
            'featured' => (bool)($album['featured'] ?? false),
            'play_type' => $album['play_type'] ?? 'full',
            'slug' => $album['slug'] ?? null
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Album Info API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>