<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo  = get_db();
$trackId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
// Retrieve all albums for dropdown
$albums = $pdo->query('SELECT id, title FROM albums ORDER BY title')->fetchAll();

$track = [
    'id'          => 0,
    'album_id'    => $albums ? $albums[0]['id'] : 0,
    'track_number'=> 1,
    'title'       => '',
    'description' => '',
    'audio_file'  => '',
    'price'       => 0.00,
    'spotify_url' => '',
    'apple_url'   => '',
    'other_url'   => ''
];
if ($trackId) {
    $stmt = $pdo->prepare('SELECT * FROM tracks WHERE id = ?');
    $stmt->execute([$trackId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $track = $existing;
    }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $album_id    = (int)($_POST['album_id'] ?? 0);
    $track_number= (int)($_POST['track_number'] ?? 1);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $spotify_url = trim($_POST['spotify_url'] ?? '');
    $apple_url   = trim($_POST['apple_url'] ?? '');
    $other_url   = trim($_POST['other_url'] ?? '');
    $audioName   = $track['audio_file'];
    if (!$title) {
        $errors[] = 'Title is required.';
    }
    if (!$album_id) {
        $errors[] = 'You must select an album.';
    }
    // Handle audio upload
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['audio_file']['tmp_name'];
        $ext     = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['mp3', 'wav', 'flac', 'ogg'])) {
            $newName = uniqid('track_') . '.' . $ext;
            $target  = __DIR__ . '/../uploads/tracks/' . $newName;
            if (move_uploaded_file($tmpName, $target)) {
                $audioName = $newName;
            }
        } else {
            $errors[] = 'Invalid audio format. Allowed: mp3, wav, flac, ogg.';
        }
    }
    if (!$errors) {
        if ($trackId) {
            $stmt = $pdo->prepare('UPDATE tracks SET album_id=?, track_number=?, title=?, description=?, audio_file=?, price=?, spotify_url=?, apple_url=?, other_url=? WHERE id=?');
            $stmt->execute([$album_id, $track_number, $title, $description, $audioName, $price, $spotify_url, $apple_url, $other_url, $trackId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO tracks (album_id, track_number, title, description, audio_file, price, spotify_url, apple_url, other_url) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$album_id, $track_number, $title, $description, $audioName, $price, $spotify_url, $apple_url, $other_url]);
        }
        header('Location: tracks.php');
        exit;
    }
    // update track with posted values to display
    $track = array_merge($track, [
        'album_id'     => $album_id,
        'track_number' => $track_number,
        'title'        => $title,
        'description'  => $description,
        'audio_file'   => $audioName,
        'price'        => $price,
        'spotify_url'  => $spotify_url,
        'apple_url'    => $apple_url,
        'other_url'    => $other_url
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $trackId ? 'Edit Track' : 'Add Track'; ?> &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
  <header class="admin-header">
    <div class="navbar__logo"><a href="dashboard.php"><span class="logo-text">Admin</span></a></div>
    <nav class="navbar__links">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="albums.php">Albums</a></li>
        <li><a href="tracks.php" class="active">Tracks</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>
  <main class="admin-container" style="max-width:700px;">
    <h1><?= $trackId ? 'Edit Track' : 'Add Track'; ?></h1>
    <?php if ($errors): ?>
      <div class="error" style="color:#f88; margin-bottom:1rem;">
        <?php foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>'; ?>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="track_form.php<?= $trackId ? '?id=' . $trackId : ''; ?>">
      <label>Album</label>
      <select name="album_id" required style="width:100%;padding:0.5rem;border-radius:4px;border:1px solid var(--colour-card);background:var(--colour-bg-dark);color:var(--colour-text);">
        <?php foreach ($albums as $album): ?>
          <option value="<?= $album['id']; ?>" <?= $album['id'] == $track['album_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($album['title']); ?></option>
        <?php endforeach; ?>
      </select>
      <label>Track Number</label>
      <input type="number" name="track_number" min="1" value="<?= (int)$track['track_number']; ?>" required />
      <label>Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($track['title']); ?>" required />
      <label>Description</label>
      <textarea name="description" style="width:100%;height:100px;padding:0.5rem;border-radius:4px;border:1px solid var(--colour-card);background:var(--colour-bg-dark);color:var(--colour-text);"><?= htmlspecialchars($track['description']); ?></textarea>
      <label>Audio File <?= $track['audio_file'] ? '(leave blank to keep existing)' : ''; ?></label>
      <input type="file" name="audio_file" accept="audio/*" />
      <label>Price (<?= CURRENCY; ?>)</label>
      <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($track['price']); ?>" required />
      <label>Spotify URL</label>
      <input type="text" name="spotify_url" value="<?= htmlspecialchars($track['spotify_url']); ?>" />
      <label>Apple Music URL</label>
      <input type="text" name="apple_url" value="<?= htmlspecialchars($track['apple_url']); ?>" />
      <label>Other Store URL</label>
      <input type="text" name="other_url" value="<?= htmlspecialchars($track['other_url']); ?>" />
      <button type="submit" class="btn btn--primary" style="margin-top:1rem;">Save</button>
      <a href="tracks.php" class="btn btn--outline" style="margin-top:1rem;">Cancel</a>
    </form>
  </main>
</body>
</html>