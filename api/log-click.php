<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$album_id = $input['album_id'] ?? 0;
$track_id = $input['track_id'] ?? null;
$platform = $input['platform'] ?? '';
$link_type = $input['link_type'] ?? 'album';

if ($album_id && $platform) {
    try {
        $stmt = $pdo->prepare("INSERT INTO streaming_clicks (album_id, track_id, platform, link_type, country_code, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $album_id, 
            $track_id,
            $platform, 
            $link_type,
            $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'US',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
?>