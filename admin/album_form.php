<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo    = get_db();
$albumId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
// Initial values
$album   = [
    'id'          => 0,
    'title'       => '',
    'description' => '',
    'cover'       => 'default-cover.png',
    'featured'    => 0,
    'spotify_url' => '',
    'apple_url'   => '',
    'other_url'   => ''
];
if ($albumId) {
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
    $stmt->execute([$albumId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $album = $existing;
    }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $spotify_url = trim($_POST['spotify_url'] ?? '');
    $apple_url   = trim($_POST['apple_url'] ?? '');
    $other_url   = trim($_POST['other_url'] ?? '');
    $coverName   = $album['cover'];
    if (!$title) {
        $errors[] = 'Title is required.';
    }
    // Handle cover upload
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['cover']['tmp_name'];
        $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $newName = uniqid('cover_') . '.' . $ext;
            $target  = __DIR__ . '/../uploads/albums/' . $newName;
            if (move_uploaded_file($tmpName, $target)) {
                $coverName = $newName;
            }
        } else {
            $errors[] = 'Invalid cover image format.';
        }
    }
    if (!$errors) {
        if ($albumId) {
            $stmt = $pdo->prepare('UPDATE albums SET title=?, description=?, cover=?, featured=?, spotify_url=?, apple_url=?, other_url=? WHERE id=?');
            $stmt->execute([$title, $description, $coverName, $featured, $spotify_url, $apple_url, $other_url, $albumId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO albums (title, description, cover, featured, spotify_url, apple_url, other_url) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$title, $description, $coverName, $featured, $spotify_url, $apple_url, $other_url]);
        }
        header('Location: albums.php');
        exit;
    }
    // Preserve entered values
    $album = array_merge($album, [
        'title'       => $title,
        'description' => $description,
        'cover'       => $coverName,
        'featured'    => $featured,
        'spotify_url' => $spotify_url,
        'apple_url'   => $apple_url,
        'other_url'   => $other_url
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $albumId ? 'Edit Album' : 'Add Album'; ?> &middot; <?= SITE_NAME; ?></title>
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
        <li><a href="albums.php" class="active">Albums</a></li>
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>
  <main class="admin-container" style="max-width:700px;">
    <h1><?= $albumId ? 'Edit Album' : 'Add Album'; ?></h1>
    <?php if ($errors): ?>
      <div class="error" style="color:#f88; margin-bottom:1rem;">
        <?php foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>'; ?>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="album_form.php<?= $albumId ? '?id=' . $albumId : ''; ?>">
      <label>Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($album['title']); ?>" required />
      <label>Description</label>
      <textarea name="description" style="width:100%;height:120px;padding:0.5rem;border-radius:4px;border:1px solid var(--colour-card);background:var(--colour-bg-dark);color:var(--colour-text);"><?= htmlspecialchars($album['description']); ?></textarea>
      <label>Cover Image</label>
      <?php if ($album['cover'] && $album['cover'] !== 'default-cover.png'): ?>
        <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" alt="current cover" style="width:100px;height:100px;object-fit:cover;border-radius:4px;display:block;margin-bottom:0.5rem;" />
      <?php endif; ?>
      <input type="file" name="cover" accept="image/*" />
      <label><input type="checkbox" name="featured" value="1" <?= $album['featured'] ? 'checked' : ''; ?>> Featured</label>
      <label>Spotify URL</label>
      <input type="text" name="spotify_url" value="<?= htmlspecialchars($album['spotify_url']); ?>" />
      <label>Apple Music URL</label>
      <input type="text" name="apple_url" value="<?= htmlspecialchars($album['apple_url']); ?>" />
      <label>Other Store URL</label>
      <input type="text" name="other_url" value="<?= htmlspecialchars($album['other_url']); ?>" />
      <button type="submit" class="btn btn--primary" style="margin-top:1rem;">Save</button>
      <a href="albums.php" class="btn btn--outline" style="margin-top:1rem;">Cancel</a>
    </form>
  </main>
</body>
</html>