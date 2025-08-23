<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$trackId = (int)($_GET['id'] ?? 0);
if ($trackId) {
    update_view($trackId, null);
    http_response_code(204); // No content
} else {
    http_response_code(400);
}
exit;