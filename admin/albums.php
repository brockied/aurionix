<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();
$albums = $pdo->query('SELECT * FROM albums ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Albums &middot; <?= SITE_NAME; ?></title>
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
  <main class="admin-container">
    <h1>Albums</h1>
    <p><a href="album_form.php" class="btn btn--primary">Add New Album</a></p>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Featured</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($albums as $album): ?>
        <tr>
          <td><?= $album['id']; ?></td>
          <td><?= htmlspecialchars($album['title']); ?></td>
          <td><?= $album['featured'] ? 'Yes' : 'No'; ?></td>
          <td><a href="album_form.php?id=<?= $album['id']; ?>" class="btn btn--outline" style="padding:0.3rem 0.6rem;font-size:0.8rem;">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</body>
</html>