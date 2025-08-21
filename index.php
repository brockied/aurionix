<?php
/**
 * ENHANCED HOMEPAGE WITH ALBUM INTEGRATION
 * Place this file as: index.php (in root directory)
 */

require_once 'config.php';

// Get featured albums
$stmt = $pdo->prepare("SELECT a.*, 
                          (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count
                       FROM albums a 
                       WHERE a.featured = 1 
                       ORDER BY a.release_date DESC 
                       LIMIT 6");
$stmt->execute();
$featuredAlbums = $stmt->fetchAll();

// Get all albums for promoted section
$stmt = $pdo->prepare("SELECT a.*, 
                          (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count,
                          (SELECT COUNT(*) FROM album_streaming_links asl WHERE asl.album_id = a.id AND asl.is_active = 1) as streaming_links_count
                       FROM albums a 
                       ORDER BY a.release_date DESC 
                       LIMIT 12");
$stmt->execute();
$allAlbums = $stmt->fetchAll();

// Get site settings
$artistName = getSetting('artist_name', 'Aurionix');
$siteTitle = getSetting('site_title', 'Aurionix - Official Music');
$siteDescription = getSetting('site_description', 'Official website of Aurionix - Electronic Music Artist');
$heroTitle = getSetting('hero_title', 'THE WORLD\'S LEADING BEAT MARKETPLACE');
$heroSubtitle = getSetting('hero_subtitle', 'The brand of choice for the next generation of musicians and beat makers.');

// Get user's country for streaming links
$userCountry = getUserCountry();

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
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta name="keywords" content="aurionix, electronic music, beats, producer, artist">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Enhanced Album Cards */
        .album-card {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.4s ease;
            position: relative;
        }

        .album-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border-color: rgba(233, 69, 96, 0.3);
        }

        .album-cover {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1;
        }

        .album-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .album-card:hover .album-cover img {
            transform: scale(1.1);
        }

        .album-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .album-card:hover .album-overlay {
            opacity: 1;
        }

        .main-play-btn {
            background: linear-gradient(135deg, #e94560, #f27121);
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-play-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(233, 69, 96, 0.4);
        }

        .album-quick-actions {
            display: flex;
            gap: 10px;
        }

        .quick-action-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .quick-action-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .album-info {
            padding: 20px;
        }

        .album-info h3 {
            color: white;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .album-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .album-stats {
            display: flex;
            gap: 10px;
        }

        .stat-badge {
            background: rgba(233, 69, 96, 0.1);
            color: #e94560;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .album-description {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .album-streaming-preview {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .streaming-icon {
            width: 28px;
            height: 28px;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .streaming-icon:hover {
            background: #e94560;
            transform: scale(1.1);
        }

        /* Enhanced Footer Player */
        #nowPlaying {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 15px 20px;
            transform: translateY(100%);
            transition: transform 0.4s ease;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        #nowPlaying.active {
            transform: translateY(0);
        }

        .now-playing-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .now-playing-left {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 250px;
        }

        #npCover {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .now-playing-info h4 {
            color: white;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .now-playing-info p {
            color: rgba(255,255,255,0.6);
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
            justify-content: center;
            gap: 15px;
        }

        .player-controls button {
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 50%;
        }

        .player-controls button:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .play-main {
            background: #e94560 !important;
            color: white !important;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .time-current,
        .time-total {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
            min-width: 35px;
        }

        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #e94560, #f27121);
            width: 30%;
            transition: width 0.1s ease;
        }

        .now-playing-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        #npClose {
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            font-size: 20px;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        #npClose:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        #embedContainer {
            margin-top: 15px;
            border-radius: 10px;
            overflow: hidden;
            max-height: 300px;
        }

        #embedContainer iframe {
            width: 100%;
            height: 300px;
            border: none;
        }

        @media (max-width: 768px) {
            .now-playing-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .now-playing-left {
                min-width: auto;
                width: 100%;
                justify-content: center;
            }
            
            .now-playing-center {
                width: 100%;
            }
            
            .now-playing-right {
                width: 100%;
                justify-content: center;
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
                <a href="#home" class="nav-link active">Home</a>
                <a href="#albums" class="nav-link">Music</a>
                <a href="/about.php" class="nav-link">About</a>
                <a href="/contact.php" class="nav-link">Contact</a>
            </div>
            
            <div class="nav-actions">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" placeholder="Search music..." id="searchInput">
                    <button class="search-btn" id="searchBtn">🔍</button>
                </div>
                
                <!-- Social Media Links -->
                <div class="nav-icons">
                    <?php if($socialLinks['youtube']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['youtube']) ?>" target="_blank" class="nav-icon" title="YouTube Channel">📺</a>
                    <?php endif; ?>
                    
                    <?php if($socialLinks['soundcloud']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['soundcloud']) ?>" target="_blank" class="nav-icon" title="SoundCloud">☁️</a>
                    <?php endif; ?>
                    
                    <?php if($socialLinks['instagram']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['instagram']) ?>" target="_blank" class="nav-icon" title="Instagram">📷</a>
                    <?php endif; ?>
                    
                    <!-- Admin access (hidden) -->
                    <a href="admin/dashboard.php" class="nav-icon admin-link" title="Admin Dashboard" style="opacity: 0.3;">⚙️</a>
                </div>
            </div>
            
            <div class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-background">
            <div class="hero-overlay"></div>
            <div class="geometric-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <h1 class="hero-title"><?= htmlspecialchars($heroTitle) ?></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($heroSubtitle) ?></p>
                
                <div class="hero-buttons">
                    <?php if($socialLinks['spotify']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['spotify']) ?>" target="_blank" class="btn btn-primary">Listen on Spotify</a>
                    <?php endif; ?>
                    
                    <?php if($socialLinks['youtube']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['youtube']) ?>" target="_blank" class="btn btn-secondary">▶ Watch Videos</a>
                    <?php endif; ?>
                    
                    <?php if(!$socialLinks['spotify'] && !$socialLinks['youtube']): ?>
                        <a href="#albums" class="btn btn-primary">🎵 Explore Music</a>
                        <button class="btn btn-secondary" onclick="window.open('/contact.php', '_self')">📧 Get in Touch</button>
                    <?php endif; ?>
                </div>
                
                <!-- Social Media Bar -->
                <div class="social-links">
                    <?php foreach($socialLinks as $platform => $url): ?>
                        <?php if($url): ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="social-link" title="<?= ucfirst($platform) ?>">
                                <?= getSocialIcon($platform) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="hero-right">
                <div class="top-charts-widget">
                    <div class="widget-header">
                        <h3>LATEST RELEASES</h3>
                        <select class="chart-filter" id="releaseFilter">
                            <option>All Releases</option>
                            <option>Featured</option>
                            <option>Latest</option>
                        </select>
                    </div>
                    
                    <div class="chart-list">
                        <?php foreach (array_slice($featuredAlbums, 0, 4) as $index => $album): ?>
                        <div class="chart-item" data-album-id="<?= $album['id'] ?>">
                            <div class="chart-position"><?= $index + 1 ?></div>
                            <div class="chart-cover">
                                <img src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : '/assets/default-cover.jpg' ?>" 
                                     alt="<?= htmlspecialchars($album['title']) ?>">
                            </div>
                            <div class="chart-info">
                                <h4><?= htmlspecialchars($album['title']) ?></h4>
                                <p><?= htmlspecialchars($artistName) ?></p>
                            </div>
                            <button class="chart-play" onclick="playAlbum(<?= $album['id'] ?>)">▶</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Now Playing Bar (Footer Player) -->
    <section id="nowPlaying" class="now-playing-bar">
        <div class="now-playing-content">
            <div class="now-playing-left">
                <img id="npCover" src="/assets/default-cover.jpg" alt="Now Playing">
                <div class="now-playing-info">
                    <h4 id="npTitle">Select a track</h4>
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
        
        <!-- Embed Container -->
        <div id="embedContainer">
            <!-- Spotify/YouTube embeds will load here -->
        </div>
    </section>

    <!-- Featured Albums Section -->
    <section class="featured-section" id="albums">
        <div class="container">
            <div class="section-header">
                <h2>MUSIC COLLECTION</h2>
                <div class="section-filter">
                    <select id="albumFilter">
                        <option value="all">All Music</option>
                        <option value="featured">Featured</option>
                        <option value="latest">Latest Releases</option>
                    </select>
                </div>
            </div>
            
            <div class="albums-grid" id="albumsGrid">
                <?php foreach ($allAlbums as $album): ?>
                <div class="album-card" data-album-id="<?= $album['id'] ?>" data-featured="<?= $album['featured'] ? 'true' : 'false' ?>">
                    <div class="album-cover">
                        <img src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : '/assets/default-cover.jpg' ?>" 
                             alt="<?= htmlspecialchars($album['title']) ?>">
                        <div class="album-overlay">
                            <button class="main-play-btn" onclick="playAlbum(<?= $album['id'] ?>)" title="Play Album">
                                ▶
                            </button>
                            <div class="album-quick-actions">
                                <a href="/album.php?slug=<?= $album['slug'] ?>" class="quick-action-btn" title="View Tracks">
                                    🎼 Tracks
                                </a>
                                <button class="quick-action-btn" onclick="shareAlbum(<?= $album['id'] ?>)" title="Share">
                                    📤 Share
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="album-info">
                        <h3><?= htmlspecialchars($album['title']) ?></h3>
                        
                        <div class="album-meta">
                            <span class="release-date"><?= date('M j, Y', strtotime($album['release_date'])) ?></span>
                            <div class="album-stats">
                                <?php if($album['featured']): ?>
                                    <span class="stat-badge">Featured</span>
                                <?php endif; ?>
                                <span class="stat-badge"><?= $album['track_count'] ?> tracks</span>
                                <span class="stat-badge"><?= ucfirst($album['play_type']) ?></span>
                            </div>
                        </div>
                        
                        <?php if($album['description']): ?>
                            <p class="album-description"><?= htmlspecialchars(substr($album['description'], 0, 100)) ?><?= strlen($album['description']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                        
                        <div class="album-streaming-preview" id="streaming-<?= $album['id'] ?>">
                            <!-- Streaming icons will be populated here via JavaScript -->
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
                    
                    <!-- Social Links Footer -->
                    <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px;">
                        <?php foreach($socialLinks as $platform => $url): ?>
                            <?php if($url): ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; transition: color 0.3s ease;" 
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
        // Pass PHP data to JavaScript
        window.SITE_CONFIG = {
            userCountry: '<?= $userCountry ?>',
            artistName: '<?= htmlspecialchars($artistName) ?>',
            siteUrl: '<?= SITE_URL ?>',
            socialLinks: <?= json_encode($socialLinks) ?>
        };

        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            searchAlbums(query);
        });

        document.getElementById('searchBtn').addEventListener('click', function() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            searchAlbums(query);
        });

        function searchAlbums(query) {
            const albums = document.querySelectorAll('.album-card');
            let foundResults = false;
            
            albums.forEach(album => {
                const title = album.querySelector('h3').textContent.toLowerCase();
                const description = album.querySelector('.album-description')?.textContent.toLowerCase() || '';
                
                if (title.includes(query) || description.includes(query) || query === '') {
                    album.style.display = 'block';
                    if (query !== '') foundResults = true;
                } else {
                    album.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            let noResultsMsg = document.getElementById('noResultsMessage');
            if (!foundResults && query !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noResultsMessage';
                    noResultsMsg.className = 'no-results';
                    noResultsMsg.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">🔍</div>
                            <h3>No albums found</h3>
                            <p>Try adjusting your search terms</p>
                        </div>
                    `;
                    document.querySelector('.albums-grid').appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }

        // Album filtering
        document.getElementById('albumFilter').addEventListener('change', function() {
            const filter = this.value;
            const albums = document.querySelectorAll('.album-card');
            
            albums.forEach(album => {
                const isFeatured = album.dataset.featured === 'true';
                
                switch(filter) {
                    case 'featured':
                        album.style.display = isFeatured ? 'block' : 'none';
                        break;
                    case 'latest':
                        const index = Array.from(albums).indexOf(album);
                        album.style.display = index < 6 ? 'block' : 'none';
                        break;
                    case 'all':
                    default:
                        album.style.display = 'block';
                        break;
                }
            });
        });

        // Enhanced album play functionality
        function playAlbum(albumId) {
            console.log('Playing album:', albumId);
            
            // Show the footer audio player
            showFooterPlayer(albumId);
            
            // Fetch streaming links and play
            fetch(`/api/get-streaming-links.php?album_id=${albumId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.links.length > 0) {
                        // Find Spotify link with embed first
                        const spotifyLink = data.links.find(link => link.platform === 'spotify' && link.embed_code);
                        
                        if (spotifyLink && spotifyLink.embed_code) {
                            // Show Spotify embed in footer player
                            showEmbedInFooter(spotifyLink.embed_code);
                        } else {
                            // Fallback to first available link
                            const preferredLink = data.links[0];
                            window.open(preferredLink.url, '_blank');
                        }
                        
                        // Log the click for analytics
                        logStreamingClick(albumId, spotifyLink?.platform || data.links[0].platform);
                    } else {
                        alert('No streaming links available for this album.');
                    }
                })
                .catch(e => {
                    console.error('Failed to load streaming links:', e);
                    alert('Unable to play album. Please try again.');
                });
        }

        // Show footer audio player
        function showFooterPlayer(albumId) {
            const footerPlayer = document.getElementById('nowPlaying');
            if (footerPlayer) {
                footerPlayer.classList.add('active');
                
                // Update footer player with album info
                fetch(`/api/get-album-info.php?id=${albumId}`)
                    .then(response => response.json())
                    .then(album => {
                        if (album.success) {
                            document.getElementById('npTitle').textContent = album.data.title;
                            document.getElementById('npArtist').textContent = '<?= htmlspecialchars($artistName) ?>';
                            document.getElementById('npCover').src = album.data.cover_image ? '/' + album.data.cover_image : '/assets/default-cover.jpg';
                        }
                    });
            }
        }

        // Show embed in footer player
        function showEmbedInFooter(embedCode) {
            const embedContainer = document.getElementById('embedContainer');
            if (embedContainer) {
                embedContainer.innerHTML = embedCode;
            }
        }

        // Log streaming clicks for analytics
        function logStreamingClick(albumId, platform) {
            fetch('/api/log-click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    album_id: albumId,
                    platform: platform,
                    timestamp: new Date().toISOString()
                })
            }).catch(e => console.log('Analytics logging failed:', e));
        }

        // Share album function
        function shareAlbum(albumId) {
            const albumCard = document.querySelector(`[data-album-id="${albumId}"]`);
            const albumTitle = albumCard.querySelector('h3').textContent;
            
            if (navigator.share) {
                navigator.share({
                    title: albumTitle,
                    text: `Check out "${albumTitle}" by <?= htmlspecialchars($artistName) ?>`,
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }

        // Placeholder functions for audio controls
        function previousTrack() {
            console.log('Previous track');
        }

        function nextTrack() {
            console.log('Next track');
        }

        // Close now playing bar
        document.getElementById('npClose').addEventListener('click', function() {
            document.getElementById('nowPlaying').classList.remove('active');
        });

        // Load streaming links for albums dynamically
        document.addEventListener('DOMContentLoaded', function() {
            const albums = document.querySelectorAll('.album-card');
            
            albums.forEach(album => {
                const albumId = album.dataset.albumId;
                const streamingContainer = album.querySelector('.album-streaming-preview');
                
                if (streamingContainer) {
                    fetch(`/api/get-streaming-links.php?album_id=${albumId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.links.length > 0) {
                                const iconsHTML = data.links.slice(0, 4).map(link => 
                                    `<span class="streaming-icon" title="${link.platform}">
                                        ${getPlatformIcon(link.platform)}
                                    </span>`
                                ).join('');
                                streamingContainer.innerHTML = iconsHTML;
                            }
                        })
                        .catch(e => console.log('Failed to load streaming links for album', albumId));
                }
            });
        });

        function getPlatformIcon(platform) {
            const icons = {
                'spotify': '🎵',
                'apple-music': '🍎',
                'youtube': '📺',
                'soundcloud': '☁️',
                'amazon-music': '📦',
                'tidal': '🌊'
            };
            return icons[platform] || '🎵';
        }
    </script>
</body>
</html>