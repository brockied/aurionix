<?php
require_once '../config.php';

try {
    // Get featured albums
    $stmt = $pdo->prepare("SELECT a.*, 
                              (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count
                           FROM albums a 
                           WHERE a.featured = 1 
                           ORDER BY a.release_date DESC 
                           LIMIT 6");
    $stmt->execute();
    $featured_albums = $stmt->fetchAll();
    
    // Get featured tracks
    $stmt = $pdo->prepare("SELECT t.*, a.title as album_title, a.cover_image as album_cover
                           FROM tracks t 
                           LEFT JOIN albums a ON t.album_id = a.id
                           WHERE t.featured = 1 
                           ORDER BY t.created_at DESC 
                           LIMIT 6");
    $stmt->execute();
    $featured_tracks = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'featured_albums' => $featured_albums,
        'featured_tracks' => $featured_tracks
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>