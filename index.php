<?php
// Aurionix homepage

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

// Fetch dynamic content
$featuredAlbums = get_featured_albums(6);
$topTracks      = get_top_tracks(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= SITE_NAME; ?> – Buy and sell beats and albums online." />
  <title><?= SITE_NAME; ?> &middot; Home</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <!-- Navigation -->
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main id="content">
    <!-- Hero section -->
    <section class="hero" style="background-image: url('/assets/images/hero-bg.png');">
      <div class="hero__overlay"></div>
      <div class="hero__content container">
        <div class="hero__text">
          <h1 class="hero__title">Discover and sell your music on <?= SITE_NAME; ?></h1>
          <p class="hero__subtitle">A modern marketplace for artists and producers. Stream, shop and connect with fans worldwide.</p>
          <div class="hero__buttons">
            <a href="/album_list.php" class="btn btn--primary btn--lg">Browse Albums</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
              <a href="/login.php" class="btn btn--outline btn--lg">Log In</a>
            <?php endif; ?>
          </div>
        </div>
        <aside class="hero__charts" aria-label="Top charts">
          <h2 class="charts__title">Top Charts</h2>
          <ul class="charts__list">
            <?php foreach ($topTracks as $track): ?>
              <li class="charts__item">
                <a href="/album.php?id=<?= urlencode($track['album_id']); ?>#track-<?= urlencode($track['id']); ?>" class="charts__link">
                  <img src="/uploads/albums/<?= htmlspecialchars($track['album_cover'] ?: 'default-cover.png'); ?>" alt="Cover for <?= htmlspecialchars($track['title']); ?>" class="charts__cover" />
                  <div class="charts__info">
                    <span class="charts__name"><?= htmlspecialchars($track['title']); ?></span>
                    <span class="charts__artist"><?= htmlspecialchars($track['album_title']); ?></span>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </aside>
      </div>
    </section>

    <!-- Player bar (persistent) -->
    <?php include __DIR__ . '/partials/player.php'; ?>

    <!-- Featured albums -->
    <section class="promoted">
      <div class="container">
        <h2 class="section-title">Featured Albums</h2>
        <div class="grid grid--promoted">
          <?php foreach ($featuredAlbums as $album): ?>
            <article class="card">
              <a href="/album.php?id=<?= urlencode($album['id']); ?>" class="card__link">
                <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" alt="Cover of <?= htmlspecialchars($album['title']); ?>" class="card__image" />
                <div class="card__body">
                  <h3 class="card__title"><?= htmlspecialchars($album['title']); ?></h3>
                  <p class="card__artist">Album</p>
                  <div class="card__rating" aria-label="Popularity rating">
                    <!-- calculate popularity by views on the album -->
                    <?php
                    // Rough rating based on total views; scale to 5 stars. We'll fetch view counts in PHP.
                    $pdo = get_db();
                    $stmt = $pdo->prepare('SELECT SUM(view_count) as total_views FROM views WHERE album_id = ?');
                    $stmt->execute([$album['id']]);
                    $viewsRow = $stmt->fetch();
                    $views = $viewsRow['total_views'] ?? 0;
                    $rating = min(5, round($views / 10));
                    for ($i = 0; $i < 5; $i++):
                        if ($i < $rating): ?>
                          <span class="star star--filled" aria-hidden="true">★</span>
                        <?php else: ?>
                          <span class="star star--empty" aria-hidden="true">☆</span>
                        <?php endif; endfor; ?>
                  </div>
                  <div class="card__price"><span class="price">&nbsp;</span></div>
                </div>
              </a>
              <div class="card__actions">
                <button class="card__btn" aria-label="Add to favourites">❤</button>
                <!-- Optionally you could add a button to go to album or listen preview -->
                <button class="card__btn" aria-label="Add to cart">🛒</button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="promoted__footer">
          <a href="/album_list.php" class="btn btn--secondary">View All Albums</a>
        </div>
      </div>
    </section>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="/assets/js/player.js"></script>
</body>
</html>