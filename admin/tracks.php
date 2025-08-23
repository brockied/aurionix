<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}
$pdo = get_db();
$tracks = $pdo->query('SELECT t.*, a.title AS album_title FROM tracks t JOIN albums a ON t.album_id = a.id ORDER BY a.title, t.track_number')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Tracks &middot; <?= SITE_NAME; ?></title>
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
  <main class="admin-container">
    <h1>Tracks</h1>
    <p><a href="track_form.php" class="btn btn--primary">Add New Track</a></p>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Album</th>
          <th>Track #</th>
          <th>Title</th>
          <th>Price</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tracks as $track): ?>
        <tr>
          <td><?= $track['id']; ?></td>
          <td><?= htmlspecialchars($track['album_title']); ?></td>
          <td><?= htmlspecialchars($track['track_number']); ?></td>
          <td><?= htmlspecialchars($track['title']); ?></td>
          <td><?= format_price((float)$track['price']); ?></td>
          <td><a href="track_form.php?id=<?= $track['id']; ?>" class="btn btn--outline" style="padding:0.3rem 0.6rem;font-size:0.8rem;">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</body>
</html>