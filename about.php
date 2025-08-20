<?php
/**
 * ABOUT PAGE
 *
 * A standalone page detailing information about the artist or project.  It
 * uses the same navigation and footer style as the rest of the site and
 * pulls site settings from the database to personalise the content.  You
 * can update the text below or fetch it from a setting in the database
 * if preferred.
 */
require_once 'config.php';

$artistName  = getSetting('artist_name', 'Aurionix');
$siteTitle   = getSetting('site_title', 'Aurionix - Official Music');
$socialLinks = [
    'spotify'    => getSetting('social_spotify', ''),
    'youtube'    => getSetting('social_youtube', ''),
    'soundcloud' => getSetting('social_soundcloud', ''),
    'instagram'  => getSetting('social_instagram', ''),
    'twitter'    => getSetting('social_twitter', ''),
    'facebook'   => getSetting('social_facebook', '')
];
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About – <?= htmlspecialchars($artistName) ?></title>
    <meta name="description" content="Learn more about <?= htmlspecialchars($artistName) ?>.">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span class="logo-icon">🎵</span>
                <span class="logo-text"><?= htmlspecialchars($artistName) ?></span>
            </div>
            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="/#albums" class="nav-link">Music</a>
                <a href="/about.php" class="nav-link active">About</a>
                <a href="/contact.php" class="nav-link">Contact</a>
            </div>
            <div class="nav-actions">
                <!-- Preserve the same structure as the homepage header for alignment -->
                <div class="search-box" style="display:none;"></div>
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
            <div class="mobile-menu-toggle"><span></span><span></span><span></span></div>
        </div>
    </nav>

    <section style="padding:80px 20px;">
        <div class="container" style="max-width:800px;margin:0 auto;color:rgba(255,255,255,0.9);">
            <h1 style="margin-bottom:20px; font-size:2.5rem;">About <?= htmlspecialchars($artistName) ?></h1>
            <p style="line-height:1.7;">
                <?= htmlspecialchars($artistName) ?> is an independent music project dedicated to creating immersive
                electronic soundscapes. This website showcases a curated collection of releases,
                from singles to full-length albums. Explore the catalogue, listen on your favourite
                streaming platform and follow along for the latest news and releases. The project
                draws inspiration from classic synthesiser sounds, modern production techniques and
                an obsession with melody.
            </p>
            <p style="margin-top:20px; line-height:1.7;">
                Whether you’re here to discover new music, revisit old favourites or just learn more
                about the journey, thank you for being part of this creative adventure. We hope the
                beats resonate with you and become part of your own soundtrack.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer style="padding:40px 20px; border-top:1px solid rgba(255,255,255,0.1);">
        <div class="container" style="max-width:800px; margin:0 auto; text-align:center; color:rgba(255,255,255,0.6);">
            <p style="margin-bottom:10px;">© <?= date('Y') ?> <?= htmlspecialchars($artistName) ?>. All rights reserved.</p>
            <div style="display:flex; justify-content:center; gap:20px;">
                <?php foreach ($socialLinks as $platform => $url): ?>
                    <?php if ($url): ?>
                        <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="color:rgba(255,255,255,0.6); text-decoration:none;">
                            <?= getSocialIcon($platform) ?> <?= ucfirst($platform) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </footer>

    <script src="/js/script.js"></script>
</body>
</html>