<?php
/**
 * AURIONIX MAIN HOMEPAGE
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
                <a href="#albums" class="nav-link">Albums</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
            
            <div class="nav-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search tracks..." id="searchInput">
                    <button class="search-btn">🔍</button>
                </div>
                <div class="nav-icons">
                    <a href="#" class="nav-icon">❤️</a>
                    <a href="#" class="nav-icon">🛒</a>
                    <a href="#" class="nav-icon">👤</a>
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
                    <button class="btn btn-primary">Learn more</button>
                    <button class="btn btn-secondary">▶ Watch Video</button>
                </div>
            </div>
            
            <div class="hero-right">
                <div class="top-charts-widget">
                    <div class="widget-header">
                        <h3>TOP CHARTS</h3>
                        <select class="chart-filter">
                            <option>All Charts</option>
                            <option>Electronic</option>
                            <option>Hip Hop</option>
                        </select>
                    </div>
                    
                    <div class="chart-list">
                        <?php foreach (array_slice($featuredAlbums, 0, 4) as $index => $album): ?>
                        <div class="chart-item">
                            <div class="chart-position"><?= $index + 1 ?></div>
                            <div class="chart-cover">
                                <img src="<?= $album['cover_image'] ?: '/assets/default-cover.jpg' ?>" 
                                     alt="<?= htmlspecialchars($album['title']) ?>">
                                <div class="play-overlay">▶</div>
                            </div>
                            <div class="chart-info">
                                <h4><?= htmlspecialchars($album['title']) ?></h4>
                                <p><?= htmlspecialchars($artistName) ?></p>
                            </div>
                            <div class="chart-actions">
                                <span class="chart-plays">💖 <?= rand(100, 999) ?></span>
                                <span class="chart-shares">📤 <?= rand(10, 99) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Currently Playing Bar -->
        <div class="now-playing">
            <div class="now-playing-content">
                <div class="now-playing-left">
                    <div class="np-cover">
                        <img src="/assets/default-cover.jpg" alt="Current Track">
                    </div>
                    <div class="np-info">
                        <h4>Dear Momma</h4>
                        <p><?= htmlspecialchars($artistName) ?></p>
                    </div>
                    <div class="np-controls-mini">
                        <button>❤️</button>
                        <button>⭐</button>
                        <button>📤</button>
                    </div>
                </div>
                
                <div class="now-playing-center">
                    <div class="player-controls">
                        <button>🔀</button>
                        <button>⏮️</button>
                        <button class="play-main">▶️</button>
                        <button>⏭️</button>
                        <button>🔁</button>
                    </div>
                    <div class="progress-container">
                        <span class="time-current">0:00</span>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <span class="time-total">3:45</span>
                    </div>
                </div>
                
                <div class="now-playing-right">
                    <button>📥</button>
                    <button>📋</button>
                    <button>🔊</button>
                    <button>⋮</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Albums Section -->
    <section class="featured-section" id="albums">
        <div class="container">
            <div class="section-header">
                <h2>PROMOTED BEATS</h2>
                <div class="section-filter">
                    <select id="albumFilter">
                        <option value="all">All Charts</option>
                        <option value="electronic">Electronic</option>
                        <option value="hip-hop">Hip Hop</option>
                        <option value="ambient">Ambient</option>
                    </select>
                </div>
            </div>
            
            <div class="albums-grid">
                <?php foreach ($allAlbums as $album): ?>
                <div class="album-card" data-album-id="<?= $album['id'] ?>">
                    <div class="album-cover">
                        <img src="<?= $album['cover_image'] ?: '/assets/default-cover.jpg' ?>" 
                             alt="<?= htmlspecialchars($album['title']) ?>">
                        <div class="album-overlay">
                            <button class="play-btn">▶</button>
                            <div class="album-actions">
                                <button class="action-btn">❤️</button>
                                <button class="action-btn">📤</button>
                                <button class="action-btn">🛒</button>
                            </div>
                        </div>
                        <div class="album-controls">
                            <button>⏮️</button>
                            <button class="main-play">▶️</button>
                            <button>⏭️</button>
                        </div>
                    </div>
                    
                    <div class="album-info">
                        <h3><?= htmlspecialchars($album['title']) ?></h3>
                        <div class="album-meta">
                            <span class="rating">⭐⭐⭐⭐⭐</span>
                            <span class="price">$<?= rand(10, 50) ?></span>
                        </div>
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
                               title="Listen on <?= ucfirst(str_replace('-', ' ', $platform)) ?>">
                                <?= getIconForPlatform($platform) ?>
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <button class="btn btn-outline">View all</button>
            </div>
        </div>
    </section>

    <!-- Music Player Modal -->
    <div class="music-player-modal" id="musicPlayer">
        <div class="player-content">
            <div class="player-header">
                <h3>Now Playing</h3>
                <button class="close-player">&times;</button>
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
                    <button id="shuffleBtn">🔀</button>
                    <button id="prevBtn">⏮️</button>
                    <button id="playPauseBtn" class="play-pause-main">▶️</button>
                    <button id="nextBtn">⏭️</button>
                    <button id="repeatBtn">🔁</button>
                </div>
                
                <div class="player-progress">
                    <span id="currentTime">0:00</span>
                    <div class="progress-container">
                        <div class="progress-track">
                            <div class="progress-fill"></div>
                        </div>
                    </div>
                    <span id="totalTime">0:00</span>
                </div>
                
                <div class="player-volume">
                    <button>🔊</button>
                    <div class="volume-slider">
                        <input type="range" min="0" max="100" value="50">
                    </div>
                </div>
            </div>
            
            <div class="embed-container" id="embedContainer">
                <!-- Spotify/YouTube embeds will load here -->
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
            siteUrl: '<?= SITE_URL ?>'
        };
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
?>