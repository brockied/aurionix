<?php
require_once '../config.php';

$album_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT a.*, 
                              (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count
                           FROM albums a 
                           WHERE a.id = ?");
    $stmt->execute([$album_id]);
    $album = $stmt->fetch();
    
    if ($album) {
        echo json_encode(['success' => true, 'data' => $album]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Album not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>