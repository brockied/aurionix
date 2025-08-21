<?php
/**
 * ALBUM PAGE - COMPLETE FILE
 */

require_once 'config.php';

// Get album by slug or ID
$album = null;
if (isset($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT * FROM albums WHERE slug = ?");
    $stmt->execute([$_GET['slug']]);
    $album = $stmt->fetch();
} elseif (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $album = $stmt->fetch();
}

if (!$album) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get site settings
$artistName = getSetting('artist_name', 'Artist Name');
$socialLinks = [
    'spotify' => getSetting('social_spotify', ''),
    'youtube' => getSetting('social_youtube', ''),
    'instagram' => getSetting('social_instagram', ''),
    'twitter' => getSetting('social_twitter', ''),
    'facebook' => getSetting('social_facebook', ''),
    'soundcloud' => getSetting('social_soundcloud', '')
];

// Get tracks
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               CASE WHEN t.audio_file IS NOT NULL AND t.audio_file != '' THEN 1 ELSE 0 END as has_audio
        FROM tracks t 
        WHERE t.album_id = ? 
        ORDER BY t.track_number ASC, t.title ASC
    ");
    $stmt->execute([$album['id']]);
    $tracks = $stmt->fetchAll();
} catch (Exception $e) {
    $tracks = [];
}

// Get album streaming links
$album_streaming_links = [];
try {
    $stmt = $pdo->prepare("
        SELECT platform, url, embed_code 
        FROM album_streaming_links 
        WHERE album_id = ? AND is_active = 1 
        ORDER BY display_order ASC
    ");
    $stmt->execute([$album['id']]);
    $album_streaming_links = $stmt->fetchAll();
} catch (Exception $e) {
    $album_streaming_links = [];
}

// Helper function for cover image path
function getAlbumCoverPath($cover_image) {
    if (empty($cover_image)) {
        return '/assets/default-cover.jpg';
    }
    
    // Ensure proper path formatting
    $path = '/' . ltrim($cover_image, '/');
    
    // Check if file exists
    if (file_exists('.' . $path)) {
        return $path;
    }
    
    return '/assets/default-cover.jpg';
}

// Helper function for platform icons
function getPlatformIcon($platform) {
    $icons = [
        'spotify' => '🎵',
        'apple-music' => '🍎',
        'youtube' => '📺',
        'soundcloud' => '☁️',
        'amazon-music' => '📦',
        'tidal' => '🌊',
        'bandcamp' => '🎪'
    ];
    return $icons[$platform] ?? '🎵';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['title']) ?> - <?= htmlspecialchars($artistName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($album['description'] ?: 'Listen to ' . $album['title'] . ' by ' . $artistName) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($album['title']) ?> - <?= htmlspecialchars($artistName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($album['description'] ?: 'Listen to ' . $album['title'] . ' by ' . $artistName) ?>">
    <meta property="og:image" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . getAlbumCoverPath($album['cover_image']) ?>">
    <meta property="og:type" content="music.album">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        /* Album page specific styles */
        .album-hero {
            padding: 120px 0 80px;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #2a1a1a 100%);
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
            background: url('<?= getAlbumCoverPath($album['cover_image']) ?>') center/cover;
            opacity: 0.1;
            filter: blur(20px);
        }
        
        .album-hero-content {
            position: relative;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            align-items: center;
        }
        
        .album-artwork {
            position: relative;
        }
        
        .album-artwork img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            aspect-ratio: 1;
            object-fit: cover;
        }
        
        .album-details h1 {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin: 0 0 15px 0;
            line-height: 1.1;
        }
        
        .artist-name {
            font-size: 1.3rem;
            color: #e94560;
            font-weight: 600;
            margin-bottom: 25px;
        }
        
        .album-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
        }
        
        .album-description {
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .album-streaming-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .album-streaming-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .album-streaming-btn:hover {
            background: #e94560;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(233,69,96,0.3);
            color: white;
            text-decoration: none;
        }
        
        /* Tracks Section */
        .tracks-section {
            padding: 80px 0;
            background: rgba(255,255,255,0.02);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .tracks-list {
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .track-item {
            display: grid;
            grid-template-columns: 50px 1fr 100px auto;
            gap: 20px;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            align-items: center;
            cursor: pointer;
        }
        
        .track-item:last-child {
            border-bottom: none;
        }
        
        .track-item:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .track-number {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e94560;
            text-align: center;
        }
        
        .track-info h4 {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        
        .track-info p {
            color: rgba(255,255,255,0.7);
            margin: 0;
            font-size: 0.9rem;
        }
        
        .track-duration {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
            text-align: center;
        }
        
        .track-streaming-links {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .track-streaming-link {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .track-streaming-link:hover {
            background: #e94560;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        /* Footer Player */
        .now-playing-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(26,26,26,0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 15px 20px;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .now-playing-bar.active {
            transform: translateY(0);
        }
        
        .now-playing-content {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 20px;
        }
        
        .now-playing-left {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 200px;
        }
        
        .now-playing-left img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .now-playing-info h4 {
            color: white;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .now-playing-info p {
            color: rgba(255,255,255,0.7);
            margin: 0;
            font-size: 0.8rem;
        }
        
        .now-playing-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .player-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .player-controls button {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .player-controls button:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .play-main {
            background: #e94560 !important;
            width: 40px;
            height: 40px;
            font-size: 1rem !important;
        }
        
        .play-main:hover {
            background: #d63850 !important;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            position: relative;
            cursor: pointer;
        }
        
        .progress-fill {
            height: 100%;
            background: #e94560;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .time-current,
        .time-total {
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
            min-width: 35px;
        }
        
        .now-playing-right {
            min-width: 50px;
            display: flex;
            justify-content: flex-end;
        }
        
        #npClose {
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }
        
        #npClose:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        #embedContainer {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .album-hero-content {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
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
            
            .now-playing-content {
                flex-direction: column;
                gap: 10px;
            }
            
            .now-playing-center {
                order: -1;
                width: 100%;
            }
        }
    </style>
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
                <img src="<?= getAlbumCoverPath($album['cover_image']) ?>" 
                     alt="<?= htmlspecialchars($album['title']) ?>"
                     onerror="this.src='/assets/default-cover.jpg'">
            </div>
            
            <div class="album-details">
                <h1><?= htmlspecialchars($album['title']) ?></h1>
                <div class="artist-name">by <?= htmlspecialchars($artistName) ?></div>
                
                <div class="album-meta">
                    <div class="meta-item">
                        <span>📅</span>
                        <span><?= date('F j, Y', strtotime($album['release_date'])) ?></span>
                    </div>
                    <?php if (count($tracks) > 0): ?>
                        <div class="meta-item">
                            <span>🎼</span>
                            <span><?= count($tracks) ?> tracks</span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span><?= ($album['play_type'] ?? 'full') === 'clip' ? '🎵' : '▶️' ?></span>
                        <span><?= ($album['play_type'] ?? 'full') === 'clip' ? 'Preview Mode' : 'Full Tracks' ?></span>
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
    <?php if ($tracks): ?>
        <section class="tracks-section">
            <div class="container">
                <div class="section-header">
                    <h2>Track Listing</h2>
                    <p>Explore each track from this album</p>
                </div>
                
                <div class="tracks-list">
                    <?php foreach ($tracks as $index => $track): ?>
                        <div class="track-item" onclick="playTrack(<?= $index ?>)">
                            <div class="track-number"><?= $track['track_number'] ?: ($index + 1) ?></div>
                            
                            <div class="track-info">
                                <h4><?= htmlspecialchars($track['title']) ?></h4>
                                <?php if ($track['duration']): ?>
                                    <p>Duration: <?= htmlspecialchars($track['duration']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($track['featured'])): ?>
                                    <p style="color: #e94560;">⭐ Featured Track</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($track['duration']): ?>
                                <div class="track-duration"><?= htmlspecialchars($track['duration']) ?></div>
                            <?php endif; ?>
                            
                            <div class="track-streaming-links" onclick="event.stopPropagation()">
                                <?php
                                // Get track-specific streaming links
                                try {
                                    $stmt = $pdo->prepare("SELECT platform, url FROM track_streaming_links WHERE track_id = ? AND is_active = 1 ORDER BY display_order");
                                    $stmt->execute([$track['id']]);
                                    $track_links = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                } catch (Exception $e) {
                                    $track_links = [];
                                }
                                
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

    <!-- Footer Player -->
    <section id="nowPlaying" class="now-playing-bar" style="transform: translateY(100%);">
        <div class="now-playing-content">
            <div class="now-playing-left">
                <img id="npCover" src="<?= getAlbumCoverPath($album['cover_image']) ?>" alt="Now Playing">
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
                <button id="npClose" title="Close player" onclick="closeFooterPlayer()">✕</button>
            </div>
        </div>
        
        <div id="embedContainer">
            <!-- Spotify/YouTube embeds will load here -->
        </div>
    </section>

    <!-- Footer -->
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
                    
                    <div class="social-links">
                        <?php foreach ($socialLinks as $platform => $url): ?>
                            <?php if ($url): ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="social-link" title="<?= ucfirst($platform) ?>">
                                    <?= getSocialIcon($platform) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Album page functionality
        let currentTrackIndex = 0;
        let isPlaying = false;
        let currentAudio = null;
        const albumTracks = <?= json_encode($tracks) ?>;
        
        // Log streaming clicks for analytics
        function logStreamingClick(albumId, platform, type = 'album', trackId = null) {
            fetch('/api/log-click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    album_id: albumId,
                    platform: platform,
                    type: type,
                    track_id: trackId,
                    timestamp: new Date().toISOString()
                })
            }).catch(e => console.log('Analytics logging failed:', e));
        }
        
        // Player controls
        function playTrack(index) {
            if (albumTracks.length === 0) return;
            
            currentTrackIndex = index;
            const track = albumTracks[currentTrackIndex];
            
            // Update now playing info
            document.getElementById('npTitle').textContent = track.title;
            document.getElementById('npArtist').textContent = '<?= htmlspecialchars($artistName) ?>';
            
            // Show player
            const player = document.getElementById('nowPlaying');
            player.style.transform = 'translateY(0)';
            player.classList.add('active');
            
            // Update play button
            document.getElementById('mainPlayPause').textContent = '⏸️';
            isPlaying = true;
            
            // If there's an audio file, try to play it
            if (track.has_audio && track.audio_file) {
                if (currentAudio) {
                    currentAudio.pause();
                }
                
                currentAudio = new Audio('/' + track.audio_file);
                currentAudio.play().catch(e => {
                    console.log('Audio playback failed:', e);
                });
                
                // Update progress bar
                currentAudio.addEventListener('timeupdate', updateProgress);
                currentAudio.addEventListener('ended', nextTrack);
            }
        }
        
        function togglePlayPause() {
            if (currentAudio) {
                if (isPlaying) {
                    currentAudio.pause();
                    document.getElementById('mainPlayPause').textContent = '▶️';
                } else {
                    currentAudio.play();
                    document.getElementById('mainPlayPause').textContent = '⏸️';
                }
                isPlaying = !isPlaying;
            }
        }
        
        function previousTrack() {
            if (currentTrackIndex > 0) {
                playTrack(currentTrackIndex - 1);
            }
        }
        
        function nextTrack() {
            if (currentTrackIndex < albumTracks.length - 1) {
                playTrack(currentTrackIndex + 1);
            }
        }
        
        function updateProgress() {
            if (currentAudio) {
                const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
                document.querySelector('.progress-fill').style.width = progress + '%';
                
                document.querySelector('.time-current').textContent = formatTime(currentAudio.currentTime);
                document.querySelector('.time-total').textContent = formatTime(currentAudio.duration);
            }
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
        
        function closeFooterPlayer() {
            const player = document.getElementById('nowPlaying');
            player.style.transform = 'translateY(100%)';
            player.classList.remove('active');
            
            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
            }
            
            isPlaying = false;
        }
        
        // Event listeners
        document.getElementById('mainPlayPause').addEventListener('click', togglePlayPause);
        
        // Progress bar click
        document.querySelector('.progress-bar').addEventListener('click', function(e) {
            if (currentAudio) {
                const rect = this.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                currentAudio.currentTime = percent * currentAudio.duration;
            }
        });
    </script>
</body>
</html>