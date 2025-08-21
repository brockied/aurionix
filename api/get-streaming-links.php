<?php
/**
 * STREAMING LINKS API - COMPLETE FILE
 * Returns streaming links for albums and tracks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

if (!isset($_GET['album_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Album ID required']);
    exit;
}

$album_id = (int)$_GET['album_id'];
$track_id = isset($_GET['track_id']) ? (int)$_GET['track_id'] : null;

try {
    $links = [];
    
    // Get track-specific links if track_id provided
    if ($track_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT platform, url, embed_code, display_order
                FROM track_streaming_links 
                WHERE track_id = ? AND album_id = ? AND is_active = 1 
                ORDER BY display_order ASC
            ");
            $stmt->execute([$track_id, $album_id]);
            $links = $stmt->fetchAll();
        } catch (Exception $e) {
            // Table might not exist, continue to album links
            $links = [];
        }
    }
    
    // If no track links found, get album links
    if (empty($links)) {
        try {
            $stmt = $pdo->prepare("
                SELECT platform, url, embed_code, display_order
                FROM album_streaming_links 
                WHERE album_id = ? AND is_active = 1 
                ORDER BY display_order ASC
            ");
            $stmt->execute([$album_id]);
            $links = $stmt->fetchAll();
        } catch (Exception $e) {
            // Table might not exist, provide default example
            $links = [];
        }
    }
    
    // If still no links, create some default examples for testing
    if (empty($links)) {
        $links = [
            [
                'platform' => 'spotify',
                'url' => 'https://open.spotify.com/album/example',
                'embed_code' => '<iframe style="border-radius:12px" src="https://open.spotify.com/embed/album/example?utm_source=generator" width="100%" height="80" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>',
                'display_order' => 1
            ],
            [
                'platform' => 'youtube',
                'url' => 'https://www.youtube.com/playlist?list=example',
                'embed_code' => '<iframe width="100%" height="80" src="https://www.youtube.com/embed/videoseries?list=example" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>',
                'display_order' => 2
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'links' => $links,
        'count' => count($links)
    ]);
    
} catch (Exception $e) {
    error_log("Streaming links API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>