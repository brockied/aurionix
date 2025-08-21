<?php
require_once '../config.php';

$track_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT t.*, a.title as album_title, a.cover_image as album_cover
                           FROM tracks t 
                           LEFT JOIN albums a ON t.album_id = a.id
                           WHERE t.id = ?");
    $stmt->execute([$track_id]);
    $track = $stmt->fetch();
    
    if ($track) {
        // Get streaming links for this track
        $stmt = $pdo->prepare("SELECT platform, url FROM track_streaming_links WHERE track_id = ? AND is_active = 1 ORDER BY display_order");
        $stmt->execute([$track_id]);
        $track['streaming_links'] = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $track]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Track not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>