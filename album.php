<?php
/**
 * INDIVIDUAL ALBUM PAGE
 *
 * This page displays details for a single album, including its cover
 * artwork, description, release information, track listing and available
 * streaming platforms.  Visitors can click on a platform icon to either
 * open the album on that service in a new tab or, if an embed code is
 * provided, load the embedded player directly on the page.  The design
 * borrows from the main site to maintain a cohesive aesthetic.
 */

require_once 'config.php';

// Fetch album ID from query string
$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$albumId) {
    // Invalid or missing album ID
    http_response_code(404);
    echo '<p style="color:white;text-align:center;padding:50px;">Album not found.</p>';
    exit;
}

// Fetch album details
$stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
$stmt->execute([$albumId]);
$album = $stmt->fetch();
if (!$album) {
    http_response_code(404);
    echo '<p style="color:white;text-align:center;padding:50px;">Album not found.</p>';
    exit;
}

// Fetch tracks for this album
$stmt = $pdo->prepare('SELECT * FROM tracks WHERE album_id = ? ORDER BY track_number, id');
$stmt->execute([$albumId]);
$tracks = $stmt->fetchAll();

// Fetch streaming links.  We prioritise country‑specific links if available.
$country = getUserCountry();
$stmt = $pdo->prepare('SELECT * FROM streaming_links WHERE album_id = ? AND (country_code = ? OR country_code = "global") ORDER BY country_code = ? DESC, platform');
$stmt->execute([$albumId, $country, $country]);
$linksRaw = $stmt->fetchAll();

// Group links by platform, preferring the country‑specific variant
$streamingLinks = [];
foreach ($linksRaw as $link) {
    $platform = $link['platform'];
    if (!isset($streamingLinks[$platform]) || $link['country_code'] === $country) {
        $streamingLinks[$platform] = $link;
    }
}

// Determine an initial embed code if available
$initialEmbed = '';
foreach ($streamingLinks as $link) {
    if (!empty($link['embed_code'])) {
        $initialEmbed = $link['embed_code'];
        break;
    }
}

// Helper functions from index.php reused here
function getIconForPlatform($platform) {
    $icons = [
        'spotify'      => '🎵',
        'apple-music'  => '🍎',
        'youtube'      => '📺',
        'youtube-video'=> '📹',
        'soundcloud'   => '☁️',
        'amazon-music' => '📦',
        'tidal'        => '🌊',
        'deezer'       => '🎧',
        'bandcamp'     => '💿',
        'itunes'       => '🎶'
    ];
    return $icons[$platform] ?? '🎵';
}

function getSocialIcon($platform) {
    $icons = [
        'spotify'    => '🎵',
        'youtube'    => '📺',
        'soundcloud' => '☁️',
        'instagram'  => '📷',
        'twitter'    => '🐦',
        'facebook'   => '📘'
    ];
    return $icons[$platform] ?? '🔗';
}

// Fetch site settings for nav
$artistName    = getSetting('artist_name', 'Aurionix');
$siteTitle     = getSetting('site_title', 'Aurionix - Official Music');
$socialLinks   = [
    'spotify'    => getSetting('social_spotify', ''),
    'youtube'    => getSetting('social_youtube', ''),
    'soundcloud' => getSetting('social_soundcloud', ''),
    'instagram'  => getSetting('social_instagram', ''),
    'twitter'    => getSetting('social_twitter', ''),
    'facebook'   => getSetting('social_facebook', '')
];

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['title']) ?> – <?= htmlspecialchars($artistName) ?></title>
    <meta name="description" content="Listen to <?= htmlspecialchars($album['title']) ?> by <?= htmlspecialchars($artistName) ?>">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation (similar to homepage) -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span class="logo-icon">🎵</span>
                <span class="logo-text"><?= htmlspecialchars($artistName) ?></span>
            </div>
            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="/#albums" class="nav-link">Music</a>
                <a href="/#about" class="nav-link">About</a>
                <a href="/#contact" class="nav-link">Contact</a>
            </div>
            <div class="nav-actions">
                <div class="nav-icons">
                    <?php foreach ($socialLinks as $platform => $url): ?>
                        <?php if ($url): ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="nav-icon" title="<?= ucfirst($platform) ?>">
                                <?= getSocialIcon($platform) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mobile-menu-toggle">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <!-- Album hero section -->
    <section class="album-hero" style="position: relative; padding: 80px 0; background: url('<?= $album['cover_image'] ? '/' . $album['cover_image'] : '/assets/default-cover.jpg' ?>') center/cover no-repeat;">
        <div style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6);"></div>
        <div class="container" style="position: relative; z-index: 2; display:flex; align-items:center; justify-content: center; flex-direction: column; text-align:center;">
            <h1 style="font-size:2.5rem; color:white; margin-bottom:10px;"><?= htmlspecialchars($album['title']) ?></h1>
            <p style="color:rgba(255,255,255,0.8); margin-bottom:15px;">
                Released <?= date('F j, Y', strtotime($album['release_date'])) ?>
            </p>
            <?php if ($album['featured']): ?>
                <span style="background:#e94560; color:white; padding:5px 10px; border-radius:5px; font-size:0.8rem;">Featured</span>
            <?php endif; ?>
        </div>
    </section>

    <!-- Album details -->
    <section class="album-details" style="padding:40px 20px;">
        <div class="container" style="max-width:800px; margin:0 auto; color:rgba(255,255,255,0.9);">
            <?php if ($album['description']): ?>
                <p style="margin-bottom:30px; line-height:1.6;"><?= nl2br(htmlspecialchars($album['description'])) ?></p>
            <?php endif; ?>

            <!-- Streaming options -->
            <?php if (!empty($streamingLinks)): ?>
                <div class="streaming-options" style="margin-bottom:30px;">
                    <h3 style="margin-bottom:10px;">Listen on your favourite platform:</h3>
                    <div class="platform-links" style="display:flex; flex-wrap:wrap; gap:15px;">
                        <?php foreach ($streamingLinks as $platform => $link): ?>
                            <a href="<?= htmlspecialchars($link['url']) ?>" class="platform-link" data-embed="<?= htmlspecialchars($link['embed_code']) ?>" target="_blank" style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.1); padding:10px 15px; border-radius:8px; color:white; text-decoration:none; font-size:0.9rem;">
                                <span><?= getIconForPlatform($platform) ?></span>
                                <span><?= ucfirst(str_replace('-', ' ', $platform)) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Embedded player container -->
            <div id="embedContainer" style="margin-bottom:40px;">
                <?php if ($initialEmbed): ?>
                    <?= $initialEmbed ?>
                <?php endif; ?>
            </div>

            <!-- Track listing -->
            <?php if (!empty($tracks)): ?>
                <div class="track-list">
                    <h3 style="margin-bottom:15px;">Track List</h3>
                    <ol style="list-style:none; padding-left:0;">
                        <?php foreach ($tracks as $track): ?>
                            <li style="padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between;">
                                <div>
                                    <strong><?= htmlspecialchars($track['track_number']) ?>.</strong> <?= htmlspecialchars($track['title']) ?>
                                </div>
                                <?php if (!empty($track['duration'])): ?>
                                    <span style="color:rgba(255,255,255,0.7); font-size:0.9rem;"><?= htmlspecialchars($track['duration']) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php else: ?>
                <p>No tracks found for this album.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Simple footer -->
    <footer style="padding:40px 20px; border-top:1px solid rgba(255,255,255,0.1);">
        <div class="container" style="max-width:800px; margin:0 auto; text-align:center; color:rgba(255,255,255,0.6);">
            <p style="margin-bottom:10px;">© <?= date('Y') ?> <?= htmlspecialchars($artistName) ?>. All rights reserved.</p>
            <div style="display:flex; justify-content:center; gap:20px;">
                <?php foreach ($socialLinks as $platform => $url): ?>
                    <?php if ($url): ?>
                        <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="color:rgba(255,255,255,0.6); text-decoration:none;"><?= getSocialIcon($platform) ?> <?= ucfirst($platform) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </footer>

    <!-- Load main script for navigation and search behaviour -->
    <script src="/js/script.js"></script>
    <script>
    // When clicking a platform link on this page, load its embed into the
    // embedContainer if an embed code is available; otherwise open the
    // link in a new tab.  We attach the listener after the page has
    // loaded to ensure the elements exist.
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.platform-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const embed = this.dataset.embed;
                const embedContainer = document.getElementById('embedContainer');
                if (embed && embed.trim() !== '') {
                    e.preventDefault();
                    embedContainer.innerHTML = embed;
                    // Scroll to the embed container for convenience
                    embedContainer.scrollIntoView({behavior:'smooth', block:'center'});
                }
            });
        });
    });
    </script>
</body>
</html>