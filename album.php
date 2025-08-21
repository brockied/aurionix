<?php
/**
 * PUBLIC ALBUM VIEWING PAGE
 * Place this file as: album.php (in root directory)
 */

require_once 'config.php';

// Get album slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get album details
$stmt = $pdo->prepare("SELECT * FROM albums WHERE slug = ?");
$stmt->execute([$slug]);
$album = $stmt->fetch();

if (!$album) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get tracks for this album
$stmt = $pdo->prepare("SELECT * FROM tracks WHERE album_id = ? ORDER BY track_number, title");
$stmt->execute([$album['id']]);
$tracks = $stmt->fetchAll();

// Get album streaming links
$stmt = $pdo->prepare("SELECT platform, url, embed_code FROM album_streaming_links WHERE album_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->execute([$album['id']]);
$album_streaming_links = $stmt->fetchAll();

// Get track streaming links for all tracks
$track_streaming_links = [];
if (!empty($tracks)) {
    $track_ids = array_column($tracks, 'id');
    $placeholders = str_repeat('?,', count($track_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT track_id, platform, url FROM track_streaming_links WHERE track_id IN ($placeholders) AND is_active = 1 ORDER BY display_order");
    $stmt->execute($track_ids);
    $links = $stmt->fetchAll();
    
    foreach ($links as $link) {
        $track_streaming_links[$link['track_id']][$link['platform']] = $link['url'];
    }
}

// Get site settings
$artistName = getSetting('artist_name', 'Aurionix');
$siteTitle = getSetting('site_title', 'Aurionix - Official Music');

// Get social media links
$socialLinks = [
    'spotify' => getSetting('social_spotify', ''),
    'youtube' => getSetting('social_youtube', ''),
    'soundcloud' => getSetting('social_soundcloud', ''),
    'instagram' => getSetting('social_instagram', ''),
    'twitter' => getSetting('social_twitter', ''),
    'facebook' => getSetting('social_facebook', '')
];

function getSocialIcon($platform) {
    $icons = [
        'spotify' => '🎵',
        'youtube' => '📺',
        'soundcloud' => '☁️',
        'instagram' => '📷',
        'twitter' => '🐦',
        'facebook' => '📘'
    ];
    return $icons[$platform] ?? '🔗';
}

function getPlatformIcon($platform) {
    $icons = [
        'spotify' => '🎵',
        'apple-music' => '🍎',
        'youtube' => '📺',
        'soundcloud' => '☁️',
        'amazon-music' => '📦',
        'tidal' => '🌊'
    ];
    return $icons[$platform] ?? '🎵';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['title']) ?> – <?= htmlspecialchars($artistName) ?></title>
    <meta name="description" content="Listen to <?= htmlspecialchars($album['title']) ?> by <?= htmlspecialchars($artistName) ?>. <?= htmlspecialchars($album['description']) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($album['title']) ?> – <?= htmlspecialchars($artistName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($album['description']) ?>">
    <meta property="og:image" content="<?= SITE_URL ?>/<?= ltrim($album['cover_image'], '/') ?>">
    <meta property="og:type" content="music.album">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .album-hero {
            padding: 100px 0 60px;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            position: relative;
            overflow: hidden;
        }
        
        .album-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : '/assets/default-cover.jpg' ?>') center/cover;
            opacity: 0.1;
            filter: blur(10px);
            z-index: 1;
        }
        
        .album-hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .album-artwork {
            width: 100%;
            max-width: 300px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            transition: transform 0.3s ease;
        }
        
        .album-artwork:hover {
            transform: scale(1.05);
        }
        
        .album-artwork img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .album-details h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #e94560, #f27121);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .album-details .artist-name {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 20px;
        }
        
        .album-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
        }
        
        .album-description {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .album-streaming-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .album-streaming-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .album-streaming-btn:hover {
            background: #e94560;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(233, 69, 96, 0.3);
        }
        
        .tracks-section {
            padding: 60px 0;
            background: rgba(255,255,255,0.02);
        }
        
        .tracks-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .tracks-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .tracks-header h2 {
            color: #e94560;
            font-size: 2rem;
        }
        
        .play-all-btn {
            background: linear-gradient(135deg, #e94560, #f27121);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .play-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(233, 69, 96, 0.3);
        }
        
        .tracks-list {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .track-item {
            display: grid;
            grid-template-columns: 40px 1fr auto auto;
            gap: 20px;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        
        .track-item:last-child {
            border-bottom: none;
        }
        
        .track-item:hover {
            background: rgba(255,255,255,0.03);
            margin: 0 -15px;
            padding-left: 15px;
            padding-right: 15px;
            border-radius: 8px;
        }
        
        .track-number {
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            text-align: center;
        }
        
        .track-info h3 {
            color: white;
            margin-bottom: 5px;
        }
        
        .track-duration {
            color: rgba(255,255,255,0.5);
            font-family: 'Courier New', monospace;
        }
        
        .track-streaming-links {
            display: flex;
            gap: 8px;
        }
        
        .track-streaming-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .track-streaming-link:hover {
            background: #e94560;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .album-hero-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 30px;
            }
            
            .album-details h1 {
                font-size: 2rem;
            }
            
            .track-item {
                grid-template-columns: 30px 1fr auto;
                gap: 15px;
            }
            
            .track-duration {
                display: none;
            }
            
            .track-streaming-links {
                grid-column: 1 / -1;
                margin-top: 10px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation (same as homepage) -->
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
                <a href="/contact.php" class="nav-link">Contact</a>
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

    <!-- Album Hero Section -->
    <section class="album-hero">
        <div class="album-hero-content">
            <div class="album-artwork">
                <img src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : '/assets/default-cover.jpg' ?>" 
                     alt="<?= htmlspecialchars($album['title']) ?>">
            </div>
            
            <div class="album-details">
                <h1><?= htmlspecialchars($album['title']) ?></h1>
                <div class="artist-name">by <?= htmlspecialchars($artistName) ?></div>
                
                <div class="album-meta">
                    <div class="meta-item">
                        <span>📅</span>
                        <span><?= date('F j, Y', strtotime($album['release_date'])) ?></span>
                    </div>
                    <?php if ($album['total_tracks']): ?>
                        <div class="meta-item">
                            <span>🎼</span>
                            <span><?= $album['total_tracks'] ?> tracks</span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span><?= $album['play_type'] === 'clip' ? '🎵' : '▶️' ?></span>
                        <span><?= $album['play_type'] === 'clip' ? 'Preview Mode' : 'Full Tracks' ?></span>
                    </div>
                </div>
                
                <?php if ($album['description']): ?>
                    <div class="album-description">
                        <?= nl2br(htmlspecialchars($album['description'])) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Album Streaming Links -->
                <?php if (!empty($album_streaming_links)): ?>
                    <div class="album-streaming-links">
                        <?php foreach ($album_streaming_links as $link): ?>
                            <a href="<?= htmlspecialchars($link['url']) ?>" 
                               target="_blank" 
                               class="album-streaming-btn"
                               onclick="logStreamingClick(<?= $album['id'] ?>, '<?= $link['platform'] ?>', 'album')">
                                <span><?= getPlatformIcon($link['platform']) ?></span>
                                <span>Listen on <?= ucwords(str_replace('-', ' ', $link['platform'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Tracks Section -->
    <?php if (!empty($tracks)): ?>
        <section class="tracks-section">
            <div class="tracks-container">
                <div class="tracks-header">
                    <h2>Track List</h2>
                    <button class="play-all-btn" onclick="playAlbum(<?= $album['id'] ?>)">
                        ▶️ Play Album
                    </button>
                </div>
                
                <div class="tracks-list">
                    <?php foreach ($tracks as $track): ?>
                        <div class="track-item" data-track-id="<?= $track['id'] ?>">
                            <div class="track-number">
                                <?= $track['track_number'] ?>
                            </div>
                            
                            <div class="track-info">
                                <h3><?= htmlspecialchars($track['title']) ?></h3>
                                <?php if ($track['play_type'] === 'clip'): ?>
                                    <small style="color: rgba(255,255,255,0.5);">
                                        Preview: <?= $track['preview_duration'] ?>s starting at <?= $track['preview_start_time'] ?>s
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($track['duration']): ?>
                                <div class="track-duration">
                                    <?= htmlspecialchars($track['duration']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="track-streaming-links">
                                <?php 
                                // Show track-specific links if available, otherwise fall back to album links
                                $track_links = $track_streaming_links[$track['id']] ?? [];
                                
                                if (!empty($track_links)) {
                                    // Use track-specific links
                                    foreach ($track_links as $platform => $url): ?>
                                        <a href="<?= htmlspecialchars($url) ?>" 
                                           target="_blank" 
                                           class="track-streaming-link"
                                           title="Listen on <?= ucwords(str_replace('-', ' ', $platform)) ?>"
                                           onclick="logStreamingClick(<?= $album['id'] ?>, '<?= $platform ?>', 'track', <?= $track['id'] ?>)">
                                            <?= getPlatformIcon($platform) ?>
                                        </a>
                                    <?php endforeach;
                                } else {
                                    // Fall back to album links
                                    foreach ($album_streaming_links as $link): ?>
                                        <a href="<?= htmlspecialchars($link['url']) ?>" 
                                           target="_blank" 
                                           class="track-streaming-link"
                                           title="Listen on <?= ucwords(str_replace('-', ' ', $link['platform'])) ?>"
                                           onclick="logStreamingClick(<?= $album['id'] ?>, '<?= $link['platform'] ?>', 'album')">
                                            <?= getPlatformIcon($link['platform']) ?>
                                        </a>
                                    <?php endforeach;
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer Player (same as homepage) -->
    <section id="nowPlaying" class="now-playing-bar" style="transform: translateY(100%);">
        <div class="now-playing-content">
            <div class="now-playing-left">
                <img id="npCover" src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : '/assets/default-cover.jpg' ?>" alt="Now Playing">
                <div class="now-playing-info">
                    <h4 id="npTitle"><?= htmlspecialchars($album['title']) ?></h4>
                    <p id="npArtist"><?= htmlspecialchars($artistName) ?></p>
                </div>
            </div>
            
            <div class="now-playing-center">
                <div class="player-controls">
                    <button onclick="previousTrack()">⏮️</button>
                    <button id="mainPlayPause" class="play-main">▶️</button>
                    <button onclick="nextTrack()">⏭️</button>
                </div>
                
                <div class="progress-container">
                    <span class="time-current">0:00</span>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="time-total">0:00</span>
                </div>
            </div>
            
            <div class="now-playing-right">
                <button id="npClose" title="Close player">✕</button>
            </div>
        </div>
        
        <div id="embedContainer">
            <!-- Spotify/YouTube embeds will load here -->
        </div>
    </section>

    <!-- Footer (same as homepage) -->
    <section class="footer">
        <div class="container">
            <div class="footer-content">
                <div style="text-align: center;">
                    <h3 style="color: #e94560; margin-bottom: 20px;">
                        <?= htmlspecialchars($artistName) ?>
                    </h3>
                    
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 30px;">
                        © <?= date('Y') ?> <?= htmlspecialchars($artistName) ?>. All rights reserved.
                    </p>
                    
                    <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px;">
                        <?php foreach($socialLinks as $platform => $url): ?>
                            <?php if($url): ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" 
                                   style="color: rgba(255,255,255,0.6); text-decoration: none; transition: color 0.3s ease;" 
                                   onmouseover="this.style.color='#e94560'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                                    <?= getSocialIcon($platform) ?> <?= ucfirst($platform) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.9rem;">
                        🎵 Made with passion for music lovers everywhere
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script>
        // Log streaming clicks for analytics
        function logStreamingClick(albumId, platform, linkType = 'album', trackId = null) {
            fetch('/api/log-click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    album_id: albumId,
                    track_id: trackId,
                    platform: platform,
                    link_type: linkType,
                    timestamp: new Date().toISOString()
                })
            }).catch(e => console.log('Analytics logging failed:', e));
        }

        // Play album functionality
        function playAlbum(albumId) {
            const footerPlayer = document.getElementById('nowPlaying');
            footerPlayer.style.transform = 'translateY(0)';
            
            // Try to find Spotify embed from album links
            fetch(`/api/get-streaming-links.php?album_id=${albumId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.links.length > 0) {
                        const spotifyLink = data.links.find(link => link.platform === 'spotify' && link.embed_code);
                        if (spotifyLink && spotifyLink.embed_code) {
                            document.getElementById('embedContainer').innerHTML = spotifyLink.embed_code;
                        }
                    }
                })
                .catch(e => console.log('Failed to load streaming links:', e));
        }

        // Placeholder functions
        function previousTrack() { console.log('Previous track'); }
        function nextTrack() { console.log('Next track'); }

        // Close player
        document.getElementById('npClose').addEventListener('click', function() {
            document.getElementById('nowPlaying').style.transform = 'translateY(100%)';
        });
    </script>
</body>
</html>