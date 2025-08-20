<?php
/**
 * CONTACT PAGE
 *
 * A standalone page providing visitors with a way to get in touch.  It
 * reuses the same navigation and footer as the rest of the site for
 * consistency.  Contact details (such as email) are pulled from the
 * settings table when available.  You can update the default values via
 * the admin settings page or modify the markup here as needed.
 */
require_once 'config.php';

$artistName  = getSetting('artist_name', 'Aurionix');
$siteTitle   = getSetting('site_title', 'Aurionix - Official Music');
$contactEmail = getSetting('contact_email', 'info@example.com');
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
    <title>Contact – <?= htmlspecialchars($artistName) ?></title>
    <meta name="description" content="Get in touch with <?= htmlspecialchars($artistName) ?>.">
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
                <a href="/about.php" class="nav-link">About</a>
                <a href="/contact.php" class="nav-link active">Contact</a>
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

    <!-- Contact Content -->
    <section style="padding:80px 20px;">
        <div class="container" style="max-width:800px;margin:0 auto;color:rgba(255,255,255,0.9);">
            <h1 style="margin-bottom:20px; font-size:2.5rem;">Contact <?= htmlspecialchars($artistName) ?></h1>
            <p style="line-height:1.7;">
                For booking, collaborations or general enquiries, please feel free to reach out via the email
                address below. We aim to respond to messages as quickly as possible. You can also connect on
                social media using the links in the navigation bar and footer.
            </p>
            <ul style="margin-top:20px; line-height:1.7; list-style: none; padding-left: 0;">
                <li><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:#3fa9f5; text-decoration:underline;"><?= htmlspecialchars($contactEmail) ?></a></li>
                <li style="margin-top:10px;">Alternatively, send a direct message through any of the linked social platforms.</li>
            </ul>
        </div>
    </section>

    <!-- Footer -->
    <footer style="padding:40px 20px; border-top:1px solid rgba(255,255,255,0.1);">
        <div class="container" style="max-width:800px; margin:0 auto; text-align:center; color:rgba(255,255,255,0.6);">
            <p style="margin-bottom:10px;">© <?= date('Y') ?> <?= htmlspecialchars($artistName) ?>. All rights reserved.</p>
            <div style="display:flex; justify-content:center; gap:20px; flex-wrap: wrap;">
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