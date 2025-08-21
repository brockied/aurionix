<?php
/**
 * ENHANCED HOMEPAGE WITH TRENDING CHARTS WIDGET
 * Fixed getSocialIcon() function and added performance tracking
 */

require_once 'config.php';
// Helper function for displaying cover images correctly
function displayCoverImage($coverImagePath, $albumTitle = '', $size = 'medium') {
    if (empty($coverImagePath)) {
        // Return placeholder if no image
        return '<div class="no-cover-placeholder">🎵</div>';
    }
    
    // Clean the path
    $imagePath = ltrim($coverImagePath, '/');
    
    // Ensure it starts with uploads/
    if (!str_starts_with($imagePath, 'uploads/')) {
        $imagePath = 'uploads/' . $imagePath;
    }
    
    // Build the full URL
    $imageUrl = '/' . $imagePath;
    
    // Check if file exists
    if (!file_exists($imagePath)) {
        return '<div class="no-cover-placeholder">🎵</div>';
    }
    
    $sizeClass = 'cover-' . $size;
    return '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($albumTitle) . '" class="' . $sizeClass . '" loading="lazy">';
}

// REPLACE YOUR ALBUM DISPLAY CODE WITH THIS:
?>

<!-- In your album grid section, replace the cover image code with: -->
<div class="album-cover">
    <?= displayCoverImage($album['cover_image'], $album['title'], 'large') ?>
</div>

<!-- For the audio player, replace the cover image code with: -->
<div class="player-cover">
    <?= displayCoverImage($currentTrack['cover_image'], $currentTrack['album_title'], 'small') ?>
</div>

<style>
/* Add these CSS styles to your main style.css */
.cover-small {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.cover-medium {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.cover-large {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 10px;
}

.no-cover-placeholder {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.4);
    font-size: 2rem;
    border-radius: 8px;
    width: 100%;
    height: 100%;
}

/* Ensure images load properly */
img[src=""], img:not([src]) {
    display: none;
}
</style>
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
                          (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) as track_count
                       FROM albums a 
                       ORDER BY a.release_date DESC 
                       LIMIT 12");
$stmt->execute();
$allAlbums = $stmt->fetchAll();

// Get trending tracks data with performance metrics
$trendingTracks = [];
try {
    // Get tracks with their click data from the last 7 days vs previous 7 days
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.title,
            t.album_id,
            t.track_number,
            a.title as album_title,
            a.cover_image,
            COALESCE(current_week.clicks, 0) as current_clicks,
            COALESCE(previous_week.clicks, 0) as previous_clicks,
            CASE 
                WHEN COALESCE(previous_week.clicks, 0) = 0 AND COALESCE(current_week.clicks, 0) > 0 THEN 'new'
                WHEN COALESCE(previous_week.clicks, 0) = 0 THEN 'stable'
                WHEN COALESCE(current_week.clicks, 0) > COALESCE(previous_week.clicks, 0) THEN 'up'
                WHEN COALESCE(current_week.clicks, 0) < COALESCE(previous_week.clicks, 0) THEN 'down'
                ELSE 'stable'
            END as trend,
            CASE 
                WHEN COALESCE(previous_week.clicks, 0) = 0 AND COALESCE(current_week.clicks, 0) > 0 THEN 100
                WHEN COALESCE(previous_week.clicks, 0) = 0 THEN 0
                ELSE ROUND(((COALESCE(current_week.clicks, 0) - COALESCE(previous_week.clicks, 0)) / COALESCE(previous_week.clicks, 1)) * 100, 1)
            END as change_percent
        FROM tracks t
        JOIN albums a ON t.album_id = a.id
        LEFT JOIN (
            SELECT album_id, COUNT(*) as clicks
            FROM streaming_clicks 
            WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY album_id
        ) current_week ON t.album_id = current_week.album_id
        LEFT JOIN (
            SELECT album_id, COUNT(*) as clicks
            FROM streaming_clicks 
            WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
            AND clicked_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY album_id
        ) previous_week ON t.album_id = previous_week.album_id
        ORDER BY current_clicks DESC, t.track_number ASC
        LIMIT 10
    ");
    $stmt->execute();
    $trendingTracks = $stmt->fetchAll();
} catch (Exception $e) {
    // If analytics tables don't exist, fall back to recent tracks
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.title,
            t.album_id,
            t.track_number,
            a.title as album_title,
            a.cover_image,
            0 as current_clicks,
            0 as previous_clicks,
            'stable' as trend,
            0 as change_percent
        FROM tracks t
        JOIN albums a ON t.album_id = a.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $trendingTracks = $stmt->fetchAll();
}

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

// Social icon function (FIXED)
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

// Trend icon function for the charts widget
function getTrendIcon($trend) {
    switch ($trend) {
        case 'up': return '📈';
        case 'down': return '📉';
        case 'new': return '🆕';
        default: return '➖';
    }
}

function getTrendColor($trend) {
    switch ($trend) {
        case 'up': return '#4CAF50';
        case 'down': return '#f44336';
        case 'new': return '#2196F3';
        default: return '#757575';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta name="keywords" content="music, electronic, beats, producer, <?= htmlspecialchars($artistName) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Enhanced Charts Widget Styles */
        .trending-charts-widget {
            background: linear-gradient(145deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .widget-header h3 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .widget-badge {
            background: linear-gradient(45deg, #e94560, #ff6b96);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .chart-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 500px;
            overflow-y: auto;
        }

        .chart-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .chart-item:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(233, 69, 96, 0.3);
            transform: translateY(-1px);
        }

        .chart-position {
            background: linear-gradient(45deg, #e94560, #ff6b96);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .chart-cover {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .chart-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .chart-info {
            flex: 1;
            min-width: 0;
        }

        .chart-track {
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0 0 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-album {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-trend {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            flex-shrink: 0;
        }

        .trend-icon {
            font-size: 1.2rem;
        }

        .trend-change {
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .trend-up { color: #4CAF50; }
        .trend-down { color: #f44336; }
        .trend-new { color: #2196F3; }
        .trend-stable { color: #757575; }

        .chart-plays {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            text-align: center;
        }

        /* Enhanced Hero Section */
        .hero-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 60px;
            align-items: start;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }
            
            .trending-charts-widget {
                max-width: 500px;
                margin: 0 auto;
                position: static;
            }
        }

        @media (max-width: 768px) {
            .chart-item {
                gap: 10px;
                padding: 10px;
            }
            
            .chart-cover {
                width: 35px;
                height: 35px;
            }
            
            .chart-track {
                font-size: 0.85rem;
            }
            
            .chart-album {
                font-size: 0.75rem;
            }
        }

        /* Scrollbar for chart list */
        .chart-list::-webkit-scrollbar {
            width: 4px;
        }

        .chart-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
        }

        .chart-list::-webkit-scrollbar-thumb {
            background: #e94560;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="#home" class="brand-link">
                    <span class="brand-icon">🎵</span>
                    <span class="brand-text"><?= htmlspecialchars($artistName) ?></span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#albums" class="nav-link">Albums</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
                
                <div class="nav-icons">
                    <?php if($socialLinks['spotify']): ?>
                        <a href="<?= htmlspecialchars($socialLinks['spotify']) ?>" target="_blank" class="nav-icon" title="Spotify">🎵</a>
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
                <!-- Trending Charts Widget -->
                <div class="trending-charts-widget">
                    <div class="widget-header">
                        <h3>📊 Trending Now</h3>
                        <span class="widget-badge"><?= count($trendingTracks) ?> tracks</span>
                    </div>
                    
                    <div class="chart-list">
                        <?php if (empty($trendingTracks)): ?>
                            <div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.6);">
                                <div style="font-size: 2rem; margin-bottom: 10px;">🎵</div>
                                <h4 style="margin-bottom: 5px;">No tracks yet</h4>
                                <p style="font-size: 0.9rem;">Upload some music to see trending data</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($trendingTracks as $index => $track): ?>
                            <div class="chart-item" data-track-id="<?= $track['id'] ?>">
                                <div class="chart-position"><?= $index + 1 ?></div>
                                <div class="chart-cover">
                                    <img src="<?= $track['cover_image'] ? '/' . ltrim($track['cover_image'], '/') : 'https://via.placeholder.com/40x40/1a1a2e/e94560?text=' . ($index + 1) ?>" 
                                         alt="<?= htmlspecialchars($track['album_title']) ?>">
                                </div>
                                <div class="chart-info">
                                    <h4 class="chart-track"><?= htmlspecialchars($track['title']) ?></h4>
                                    <p class="chart-album"><?= htmlspecialchars($track['album_title']) ?></p>
                                </div>
                                <div class="chart-trend">
                                    <div class="trend-icon"><?= getTrendIcon($track['trend']) ?></div>
                                    <?php if ($track['trend'] !== 'stable'): ?>
                                        <div class="trend-change trend-<?= $track['trend'] ?>">
                                            <?php if ($track['trend'] === 'new'): ?>
                                                NEW
                                            <?php elseif ($track['change_percent'] > 0): ?>
                                                +<?= $track['change_percent'] ?>%
                                            <?php else: ?>
                                                <?= $track['change_percent'] ?>%
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="chart-plays">
                                    <?= $track['current_clicks'] ?> plays
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Albums Section -->
    <section class="albums-section" id="albums">
        <div class="container">
            <div class="section-header">
                <h2>Featured Albums</h2>
                <p>Discover the latest releases and fan favorites</p>
            </div>
            
            <?php if (empty($featuredAlbums)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎵</div>
                    <h3>No featured albums yet</h3>
                    <p>Check back soon for new releases</p>
                </div>
            <?php else: ?>
                <div class="albums-grid">
                    <?php foreach ($featuredAlbums as $album): ?>
                    <div class="album-card" data-album-id="<?= $album['id'] ?>">
                        <div class="album-cover">
                            <img src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : 'https://via.placeholder.com/300x300/1a1a2e/e94560?text=No+Cover' ?>" 
                                 alt="<?= htmlspecialchars($album['title']) ?>">
                            <div class="album-overlay">
                                <div class="album-actions">
                                    <button class="play-btn" onclick="playAlbum(<?= $album['id'] ?>)">▶️</button>
                                    <button class="info-btn" onclick="showAlbumInfo(<?= $album['id'] ?>)">ℹ️</button>
                                </div>
                            </div>
                        </div>
                        <div class="album-info">
                            <h3><?= htmlspecialchars($album['title']) ?></h3>
                            <p class="album-meta">
                                <?= $album['release_date'] ? date('M Y', strtotime($album['release_date'])) : 'No Date' ?>
                                • <?= $album['track_count'] ?> tracks
                            </p>
                            <?php if ($album['description']): ?>
                                <p class="album-description"><?= strlen($album['description']) > 100 ? substr(htmlspecialchars($album['description']), 0, 100) . '...' : htmlspecialchars($album['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="album-streaming-preview" id="streaming-<?= $album['id'] ?>">
                                <!-- Streaming icons will be populated here via JavaScript -->
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- All Albums Section -->
    <section class="all-albums-section">
        <div class="container">
            <div class="section-header">
                <h2>All Releases</h2>
                <p>Complete discography and catalog</p>
            </div>
            
            <div class="albums-grid">
                <?php foreach ($allAlbums as $album): ?>
                <div class="album-card" data-album-id="<?= $album['id'] ?>">
                    <div class="album-cover">
                        <img src="<?= $album['cover_image'] ? '/' . ltrim($album['cover_image'], '/') : 'https://via.placeholder.com/300x300/1a1a2e/e94560?text=No+Cover' ?>" 
                             alt="<?= htmlspecialchars($album['title']) ?>">
                        <div class="album-overlay">
                            <div class="album-actions">
                                <button class="play-btn" onclick="playAlbum(<?= $album['id'] ?>)">▶️</button>
                                <button class="info-btn" onclick="showAlbumInfo(<?= $album['id'] ?>)">ℹ️</button>
                            </div>
                        </div>
                    </div>
                    <div class="album-info">
                        <h3><?= htmlspecialchars($album['title']) ?></h3>
                        <p class="album-meta">
                            <?= $album['release_date'] ? date('M Y', strtotime($album['release_date'])) : 'No Date' ?>
                            • <?= $album['track_count'] ?> tracks
                        </p>
                        <?php if ($album['description']): ?>
                            <p class="album-description"><?= strlen($album['description']) > 100 ? substr(htmlspecialchars($album['description']), 0, 100) . '...' : htmlspecialchars($album['description']) ?></p>
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
            siteUrl: '<?= SITE_URL ?>'
        };

        // Enhanced functionality
        function playAlbum(albumId) {
            console.log('Playing album:', albumId);
            // Add your streaming/audio player logic here
        }

        function showAlbumInfo(albumId) {
            console.log('Showing info for album:', albumId);
            // Add album details modal logic here
        }

        // Track clicks on chart items
        document.querySelectorAll('.chart-item').forEach(item => {
            item.addEventListener('click', function() {
                const trackId = this.dataset.trackId;
                console.log('Chart item clicked:', trackId);
                // Add track interaction logic here
            });
        });

        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(10, 10, 10, 0.98)';
            } else {
                navbar.style.background = 'rgba(10, 10, 10, 0.95)';
            }
        });

        console.log('Aurionix homepage loaded successfully! 🎵');
    </script>
</body>
</html>