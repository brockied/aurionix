<?php
/**
 * AURIONIX MAIN HOMEPAGE - FIXED
 * Place in root directory (public_html/)
 */

require_once 'config.php';

// Get featured albums
$stmt = $pdo->prepare("SELECT * FROM albums WHERE featured = 1 ORDER BY release_date DESC LIMIT 6");
$stmt->execute();
$featuredAlbums = $stmt->fetchAll();

// Get all albums for promoted section
$stmt = $pdo->prepare("SELECT * FROM albums ORDER BY release_date DESC LIMIT 12");
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
                <!-- Search Box - Now functional -->
                <div class="search-box">
                    <input type="text" placeholder="Search music..." id="searchInput">
                    <button class="search-btn" id="searchBtn">🔍</button>
                </div>
                
                <!-- Social Media Links -->
                <div class="nav-icons">
                    <?php if($socialLinks['spotify']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['spotify']) ?>" target="_blank" class="nav-icon" title="Listen on Spotify">🎵</a>
                    <?php endif; ?>
                    
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
                        <button class="btn btn-secondary" onclick="scrollToContact()">📧 Get in Touch</button>
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
                                <img src="<?= $album['cover_image'] ? '/' . $album['cover_image'] : '/assets/default-cover.jpg' ?>" 
                                     alt="<?= htmlspecialchars($album['title']) ?>">
                                <div class="play-overlay">▶</div>
                            </div>
                            <div class="chart-info">
                                <h4><?= htmlspecialchars($album['title']) ?></h4>
                                <p><?= htmlspecialchars($artistName) ?></p>
                            </div>
                            <div class="chart-actions">
                                <span class="chart-date"><?= date('M Y', strtotime($album['release_date'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Currently Playing Bar - Hidden by default -->
        <div class="now-playing" id="nowPlaying">
            <div class="now-playing-content">
                <div class="now-playing-left">
                    <div class="np-cover">
                        <img src="/assets/default-cover.jpg" alt="Current Track" id="npCover">
                    </div>
                    <div class="np-info">
                        <h4 id="npTitle">Select a track</h4>
                        <p id="npArtist"><?= htmlspecialchars($artistName) ?></p>
                    </div>
                    <div class="np-controls-mini">
                        <!-- Removed favorites button per user request -->
                        <button id="npShare" title="Share track">📤</button>
                    </div>
                </div>
                
                <div class="now-playing-center">
                    <div class="player-controls">
                        <button id="npPrev">⏮️</button>
                        <button class="play-main" id="npPlayPause">▶️</button>
                        <button id="npNext">⏭️</button>
                    </div>
                    <div class="progress-container">
                        <span class="time-current" id="npCurrentTime">0:00</span>
                        <div class="progress-bar" id="npProgressBar">
                            <div class="progress-fill" id="npProgressFill"></div>
                        </div>
                        <span class="time-total" id="npTotalTime">0:00</span>
                    </div>
                </div>
                
                <div class="now-playing-right">
                    <button id="npDownload" title="Download/Stream">📥</button>
                    <button id="npPlaylist" title="View all tracks">📋</button>
                    <button id="npVolume" title="Volume">🔊</button>
                    <button id="npClose" title="Close player">✕</button>
                </div>
            </div>
        </div>
    </section>

    <!-- About and Contact sections removed in favour of dedicated pages -->

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
                        <option value="popular">Most Popular</option>
                    </select>
                </div>
            </div>
            
            <div class="albums-grid" id="albumsGrid">
                <?php foreach ($allAlbums as $album): ?>
                <div class="album-card" data-album-id="<?= $album['id'] ?>" data-featured="<?= $album['featured'] ? 'true' : 'false' ?>">
                    <div class="album-cover">
                        <img src="<?= $album['cover_image'] ? '/' . $album['cover_image'] : '/assets/default-cover.jpg' ?>" 
                             alt="<?= htmlspecialchars($album['title']) ?>">
                        <div class="album-overlay">
                            <button class="play-btn" onclick="playAlbum(<?= $album['id'] ?>)">▶</button>
                            <div class="album-actions">
                                <!-- Removed heart/favorite button per user request -->
                                <button class="action-btn" onclick="shareAlbum(<?= $album['id'] ?>)" title="Share">📤</button>
                                <button class="action-btn" onclick="showTrackList(<?= $album['id'] ?>)" title="View tracks">🎼</button>
                            </div>
                        </div>
                        <div class="album-controls">
                            <button onclick="previousTrack()">⏮️</button>
                            <button class="main-play" onclick="playAlbum(<?= $album['id'] ?>)">▶️</button>
                            <button onclick="nextTrack()">⏭️</button>
                        </div>
                    </div>
                    
                    <div class="album-info">
                        <h3><?= htmlspecialchars($album['title']) ?></h3>
                        <div class="album-meta">
                            <span class="release-date">📅 <?= date('M j, Y', strtotime($album['release_date'])) ?></span>
                            <?php if($album['featured']): ?>
                                <span class="featured-badge">⭐ Featured</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($album['description']): ?>
                            <p class="album-description"><?= htmlspecialchars(substr($album['description'], 0, 100)) ?><?= strlen($album['description']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                        
                        <div class="streaming-links">
                            <?php
                            $platforms = ['spotify', 'apple-music', 'youtube', 'soundcloud'];
                            foreach ($platforms as $platform):
                                $link = getStreamingLink($album['id'], $platform, $userCountry);
                                if ($link):
                            ?>
                            <a href="<?= htmlspecialchars($link['url']) ?>" 
                               target="_blank" 
                               class="streaming-btn streaming-<?= $platform ?>"
                               title="Listen on <?= ucfirst(str_replace('-', ' ', $platform)) ?>"
                               onclick="trackClick('<?= $platform ?>', <?= $album['id'] ?>)">
                                <?= getIconForPlatform($platform) ?>
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if(empty($allAlbums)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎵</div>
                    <h3>Music Coming Soon</h3>
                    <p>New releases are on the way! Follow us on social media for updates.</p>
                    <div class="social-links">
                        <?php foreach($socialLinks as $platform => $url): ?>
                            <?php if($url): ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="btn btn-outline">
                                    <?= getSocialIcon($platform) ?> <?= ucfirst($platform) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Simple Footer -->
            <div class="section-footer">
                <div style="text-align: center; margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <p style="color: rgba(255,255,255,0.6); margin-bottom: 20px;">
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

    <!-- Music Player Modal -->
    <div class="music-player-modal" id="musicPlayer">
        <div class="player-content">
            <div class="player-header">
                <h3>Now Playing</h3>
                <button class="close-player" onclick="closePlayer()">&times;</button>
            </div>
            
            <div class="player-main">
                <div class="player-artwork">
                    <img id="playerArtwork" src="/assets/default-cover.jpg" alt="Album Art">
                </div>
                
                <div class="player-info">
                    <h2 id="playerTitle">Track Title</h2>
                    <p id="playerArtist"><?= htmlspecialchars($artistName) ?></p>
                </div>
                
                <div class="player-controls-full">
                    <button onclick="previousTrack()">⏮️</button>
                    <button id="mainPlayPause" class="play-pause-main">▶️</button>
                    <button onclick="nextTrack()">⏭️</button>
                </div>
            </div>
            
            <div class="embed-container" id="embedContainer">
                <!-- Spotify/YouTube embeds will load here -->
            </div>
            
            <div class="streaming-options" id="streamingOptions">
                <h4>Listen on your favorite platform:</h4>
                <div class="platform-links" id="platformLinks">
                    <!-- Platform links will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/js/script.js"></script>
    
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
            
            if (query.length === 0) {
                albums.forEach(album => album.style.display = 'block');
                return;
            }
            
            albums.forEach(album => {
                const title = album.querySelector('h3').textContent.toLowerCase();
                const description = album.querySelector('.album-description')?.textContent.toLowerCase() || '';
                
                if (title.includes(query) || description.includes(query)) {
                    album.style.display = 'block';
                    album.style.animation = 'fadeIn 0.3s ease';
                } else {
                    album.style.display = 'none';
                }
            });
        }

        // Album filtering
        document.getElementById('albumFilter').addEventListener('change', function(e) {
            const filter = e.target.value;
            const albums = document.querySelectorAll('.album-card');
            
            albums.forEach(album => {
                const featured = album.dataset.featured === 'true';
                const releaseDate = new Date(album.querySelector('.release-date').textContent.replace('📅 ', ''));
                const isRecent = (Date.now() - releaseDate.getTime()) < (90 * 24 * 60 * 60 * 1000); // 90 days
                
                let show = true;
                switch(filter) {
                    case 'featured':
                        show = featured;
                        break;
                    case 'latest':
                        show = isRecent;
                        break;
                    case 'popular':
                        show = featured; // Using featured as proxy for popular
                        break;
                }
                
                album.style.display = show ? 'block' : 'none';
            });
        });

        // Track streaming clicks
        function trackClick(platform, albumId) {
            fetch('/api/track-click.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    platform: platform,
                    album_id: albumId,
                    country: window.SITE_CONFIG.userCountry
                })
            }).catch(e => console.log('Analytics tracking failed:', e));
        }

        // Player functions
        function playAlbum(albumId) {
            const albumCard = document.querySelector(`[data-album-id="${albumId}"]`);
            const title = albumCard.querySelector('h3').textContent;
            const cover = albumCard.querySelector('img').src;
            
            // Update now playing bar
            document.getElementById('npTitle').textContent = title;
            document.getElementById('npCover').src = cover;
            document.getElementById('nowPlaying').classList.add('active');
            
            // Show player modal
            document.getElementById('musicPlayer').classList.add('active');
            document.getElementById('playerTitle').textContent = title;
            document.getElementById('playerArtwork').src = cover;
            
            // Load streaming options
            loadStreamingOptions(albumId);
        }

        function loadStreamingOptions(albumId) {
            fetch(`/api/get-stream.php?album_id=${albumId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const platformLinks = document.getElementById('platformLinks');
                        platformLinks.innerHTML = '';
                        
                        data.data.forEach(link => {
                            const platformLink = document.createElement('a');
                            platformLink.href = link.url;
                            platformLink.target = '_blank';
                            platformLink.className = 'platform-link';
                            platformLink.onclick = () => trackClick(link.platform, albumId);
                            platformLink.innerHTML = `
                                ${getIconForPlatform(link.platform)} 
                                ${link.platform.replace('-', ' ')}
                            `;
                            platformLinks.appendChild(platformLink);
                        });
                    }
                })
                .catch(e => console.error('Failed to load streaming options:', e));
        }

        function closePlayer() {
            document.getElementById('musicPlayer').classList.remove('active');
        }

        function getIconForPlatform(platform) {
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

        // Placeholder functions for other interactions
        function toggleFavorite(albumId) {
            console.log('Favorite toggled for album:', albumId);
        }

        function shareAlbum(albumId) {
            if (navigator.share) {
                navigator.share({
                    title: document.querySelector(`[data-album-id="${albumId}"] h3`).textContent,
                    text: 'Check out this music!',
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }

        function showTrackList(albumId) {
            console.log('Show track list for album:', albumId);
        }

        function previousTrack() {
            console.log('Previous track');
        }

        function nextTrack() {
            console.log('Next track');
        }

        function scrollToContact() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Close now playing bar
        document.getElementById('npClose').addEventListener('click', function() {
            document.getElementById('nowPlaying').classList.remove('active');
        });
    </script>
</body>
</html>

<?php
function getIconForPlatform($platform) {
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
?>