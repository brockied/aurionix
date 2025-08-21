<?php
// =============================================================================
// ENHANCED API FILES - Create these in /api/ directory
// =============================================================================

// File: api/get-streaming-links.php
<?php
require_once '../config.php';

$album_id = $_GET['album_id'] ?? 0;

try {
    // Get album streaming links
    $stmt = $pdo->prepare("SELECT platform, url, embed_code, 'album' as link_type 
                           FROM album_streaming_links 
                           WHERE album_id = ? AND is_active = 1 
                           ORDER BY display_order");
    $stmt->execute([$album_id]);
    $album_links = $stmt->fetchAll();
    
    // Get track streaming links for this album
    $stmt = $pdo->prepare("SELECT tsl.platform, tsl.url, 'track' as link_type, tsl.track_id
                           FROM track_streaming_links tsl
                           JOIN tracks t ON tsl.track_id = t.id
                           WHERE t.album_id = ? AND tsl.is_active = 1
                           ORDER BY t.track_number, tsl.display_order");
    $stmt->execute([$album_id]);
    $track_links = $stmt->fetchAll();
    
    // Combine and return
    $all_links = array_merge($album_links, $track_links);
    
    echo json_encode([
        'success' => true, 
        'links' => $album_links, // Primary album links for now
        'track_links' => $track_links,
        'all_links' => $all_links
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

// File: api/get-album-info.php  
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

// File: api/get-album-tracks.php
<?php
require_once '../config.php';

$album_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT t.*, 
                              (SELECT COUNT(*) FROM track_streaming_links tsl WHERE tsl.track_id = t.id) as streaming_links_count
                           FROM tracks t 
                           WHERE t.album_id = ? 
                           ORDER BY t.track_number, t.title");
    $stmt->execute([$album_id]);
    $tracks = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'tracks' => $tracks]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'tracks' => [], 'error' => $e->getMessage()]);
}
?>