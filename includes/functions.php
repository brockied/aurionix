<?php
// ---------------------------------------------------------------
//  Common functions used throughout the Aurionix application
// ---------------------------------------------------------------

require_once __DIR__ . '/../config.php';

/**
 * Return a PDO instance connected to the configured database.
 *
 * @return PDO
 */
function get_db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

/**
 * Retrieve featured albums for homepage display.
 *
 * @param int $limit Number of albums to return.
 * @return array
 */
function get_featured_albums(int $limit = 6): array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE featured = 1 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Retrieve top chart tracks based on view counts in the last week.
 * If there is no data in the views table, fallback to most recently added tracks.
 *
 * @param int $limit Number of tracks to return.
 * @return array
 */
function get_top_tracks(int $limit = 5): array
{
    $pdo = get_db();
    // Attempt to get tracks based on views in the past 14 days
    $stmt = $pdo->prepare(
        'SELECT t.id, t.title, t.audio_file, a.title AS album_title, a.id AS album_id, a.cover AS album_cover, SUM(v.view_count) AS views
         FROM tracks t
         JOIN albums a ON t.album_id = a.id
         JOIN views v ON v.track_id = t.id
         WHERE v.view_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
         GROUP BY t.id
         ORDER BY views DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $tracks = $stmt->fetchAll();
    if (empty($tracks)) {
        // Fallback to latest tracks
        $stmt2 = $pdo->prepare('SELECT t.id, t.title, t.audio_file, a.title AS album_title, a.id AS album_id, a.cover AS album_cover FROM tracks t JOIN albums a ON t.album_id = a.id ORDER BY t.created_at DESC LIMIT ?');
        $stmt2->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt2->execute();
        $tracks = $stmt2->fetchAll();
    }
    return $tracks;
}

/**
 * Update view counter for a track and/or album. This function should be called
 * whenever a user views a track or album page.
 *
 * @param int|null $trackId Optional track ID.
 * @param int|null $albumId Optional album ID.
 */
function update_view(?int $trackId = null, ?int $albumId = null): void
{
    if (!$trackId && !$albumId) {
        return;
    }
    $pdo = get_db();
    $date = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT id, view_count FROM views WHERE track_id <=> ? AND album_id <=> ? AND view_date = ?');
    $stmt->execute([$trackId, $albumId, $date]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt2 = $pdo->prepare('UPDATE views SET view_count = view_count + 1 WHERE id = ?');
        $stmt2->execute([$row['id']]);
    } else {
        $stmt3 = $pdo->prepare('INSERT INTO views (track_id, album_id, view_date, view_count) VALUES (?, ?, ?, 1)');
        $stmt3->execute([$trackId, $albumId, $date]);
    }
}

/**
 * Format price with currency symbol defined in config.
 *
 * @param float $amount
 * @return string
 */
function format_price(float $amount): string
{
    return CURRENCY . ' ' . number_format($amount, 2);
}