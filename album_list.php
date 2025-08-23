<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

$pdo = get_db();
$searchTerm = trim($_GET['q'] ?? '');
if ($searchTerm) {
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE title LIKE ? ORDER BY created_at DESC');
    $stmt->execute(['%' . $searchTerm . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM albums ORDER BY created_at DESC');
}
$albums = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Albums &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top: 6rem;">
    <h1>Albums</h1>
    <form method="get" action="album_list.php" style="margin-bottom:1.5rem;">
      <input type="text" name="q" placeholder="Search albums..." value="<?= htmlspecialchars($searchTerm); ?>" style="padding:0.5rem; border-radius:4px; border:none; width:60%; max-width:300px;">
      <button type="submit" class="btn btn--secondary">Search</button>
    </form>
    <div class="grid grid--albums">
      <?php foreach ($albums as $album): ?>
        <article class="card">
          <a href="/album.php?id=<?= urlencode($album['id']); ?>" class="card__link">
            <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" alt="<?= htmlspecialchars($album['title']); ?> cover" class="card__image">
            <div class="card__body">
              <h3 class="card__title"><?= htmlspecialchars($album['title']); ?></h3>
              <p class="card__artist">Album</p>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>