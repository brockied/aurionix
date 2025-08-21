<?php
require_once '../config.php';

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'error' => 'Query too short']);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("SELECT a.*, 
                              (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count
                           FROM albums a 
                           WHERE a.title LIKE ? OR a.description LIKE ?
                           ORDER BY a.featured DESC, a.release_date DESC 
                           LIMIT ?");
    $stmt->execute([$searchTerm, $searchTerm, $limit]);
    $albums = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'albums' => $albums]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>