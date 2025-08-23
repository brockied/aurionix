<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

$albumId = (int)($_GET['id'] ?? 0);
if (!$albumId) {
    header('Location: album_list.php');
    exit;
}

$pdo = get_db();
// Fetch album
$stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
$stmt->execute([$albumId]);
$album = $stmt->fetch();
if (!$album) {
    header('Location: album_list.php');
    exit;
}
// Update album view count
update_view(null, $albumId);

// Fetch tracks
$stmt = $pdo->prepare('SELECT * FROM tracks WHERE album_id = ? ORDER BY track_number ASC');
$stmt->execute([$albumId]);
$tracks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($album['title']); ?> &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top: 6rem;">
    <div class="album-header">
      <div class="album-cover">
        <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" alt="<?= htmlspecialchars($album['title']); ?> cover" />
        <?php if ($album['spotify_url'] || $album['apple_url'] || $album['other_url']): ?>
          <div class="album-links" style="margin-top:1rem;">
            <?php if ($album['spotify_url']): ?>
              <a href="<?= htmlspecialchars($album['spotify_url']); ?>" target="_blank" class="btn btn--secondary" style="margin-bottom:0.5rem; display:block;">Listen on Spotify</a>
            <?php endif; ?>
            <?php if ($album['apple_url']): ?>
              <a href="<?= htmlspecialchars($album['apple_url']); ?>" target="_blank" class="btn btn--secondary" style="margin-bottom:0.5rem; display:block;">Listen on Apple Music</a>
            <?php endif; ?>
            <?php if ($album['other_url']): ?>
              <a href="<?= htmlspecialchars($album['other_url']); ?>" target="_blank" class="btn btn--secondary" style="margin-bottom:0.5rem; display:block;">Other Store</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="album-info">
        <h1><?= htmlspecialchars($album['title']); ?></h1>
        <p><?= nl2br(htmlspecialchars($album['description'])); ?></p>
        <h2>Tracks</h2>
        <div class="track-list">
          <?php foreach ($tracks as $track): ?>
          <div class="track-item">
            <div class="track-info">
              <span class="track-number"><?= htmlspecialchars($track['track_number']); ?>.</span>
              <span class="track-title"><?= htmlspecialchars($track['title']); ?></span>
            </div>
            <div class="track-actions">
              <span class="track-price"><?= format_price((float)$track['price']); ?></span>
              <button class="btn btn--secondary btn--sm" onclick="setTrack({title:'<?= htmlspecialchars($track['title'], ENT_QUOTES); ?>', artist:'<?= htmlspecialchars($album['title'], ENT_QUOTES); ?>', cover:'/uploads/albums/<?= htmlspecialchars($album['cover']); ?>', src:'/uploads/tracks/<?= htmlspecialchars($track['audio_file']); ?>'}); updateTrackView(<?= $track['id']; ?>);">Play</button>
              <a href="/cart.php?action=add&track_id=<?= urlencode($track['id']); ?>" class="btn btn--outline btn--sm">Cart</a>
              <?php if ($track['spotify_url']): ?>
                <a href="<?= htmlspecialchars($track['spotify_url']); ?>" target="_blank" title="Spotify" class="track-ext">&#9835;</a>
              <?php endif; ?>
              <?php if ($track['apple_url']): ?>
                <a href="<?= htmlspecialchars($track['apple_url']); ?>" target="_blank" title="Apple Music" class="track-ext">&#63743;</a>
              <?php endif; ?>
              <?php if ($track['other_url']): ?>
                <a href="<?= htmlspecialchars($track['other_url']); ?>" target="_blank" title="Other" class="track-ext">↗</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="/assets/js/player.js"></script>
  <script>
    function updateTrackView(trackId) {
      fetch('/track_view.php?id=' + trackId).catch(() => {});
    }
  </script>
</body>
</html>