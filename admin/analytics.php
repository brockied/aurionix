<?php
/**
 * ANALYTICS DASHBOARD
 * Place this file as: admin/analytics.php
 */

require_once '../config.php';
requireAdmin();

// Check if analytics tables exist, create if they don't
try {
    $pdo->query("SELECT 1 FROM streaming_clicks LIMIT 1");
} catch (Exception $e) {
    // Create analytics tables
    $sql = "
    CREATE TABLE IF NOT EXISTS streaming_clicks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        album_id INT,
        platform VARCHAR(100) NOT NULL,
        country_code VARCHAR(10) DEFAULT 'unknown',
        ip_address VARCHAR(45),
        user_agent TEXT,
        clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
        INDEX idx_album_platform (album_id, platform),
        INDEX idx_clicked_at (clicked_at),
        INDEX idx_country (country_code)
    );

    CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_url VARCHAR(500) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        country_code VARCHAR(10) DEFAULT 'unknown',
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_page_url (page_url),
        INDEX idx_viewed_at (viewed_at),
        INDEX idx_country (country_code)
    );

    CREATE TABLE IF NOT EXISTS popular_albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        album_id INT,
        total_clicks INT DEFAULT 0,
        total_views INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
        UNIQUE KEY unique_album (album_id)
    );
    ";
    
    $pdo->exec($sql);
}

// Get date range from query parameters
$date_range = $_GET['range'] ?? '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// Get total streaming clicks
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM streaming_clicks WHERE clicked_at >= ?");
$stmt->execute([$start_date]);
$total_clicks = $stmt->fetch()['total'];

// Get clicks by platform
$stmt = $pdo->prepare("
    SELECT platform, COUNT(*) as clicks 
    FROM streaming_clicks 
    WHERE clicked_at >= ? 
    GROUP BY platform 
    ORDER BY clicks DESC
");
$stmt->execute([$start_date]);
$platform_clicks = $stmt->fetchAll();

// Get clicks by country
$stmt = $pdo->prepare("
    SELECT country_code, COUNT(*) as clicks 
    FROM streaming_clicks 
    WHERE clicked_at >= ? 
    GROUP BY country_code 
    ORDER BY clicks DESC 
    LIMIT 10
");
$stmt->execute([$start_date]);
$country_clicks = $stmt->fetchAll();

// Get daily clicks for chart
$stmt = $pdo->prepare("
    SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
    FROM streaming_clicks 
    WHERE clicked_at >= ? 
    GROUP BY DATE(clicked_at) 
    ORDER BY date
");
$stmt->execute([$start_date]);
$daily_clicks = $stmt->fetchAll();

// Get top albums by clicks
$stmt = $pdo->prepare("
    SELECT a.title, a.cover_image, COUNT(sc.id) as clicks 
    FROM albums a 
    LEFT JOIN streaming_clicks sc ON a.id = sc.album_id AND sc.clicked_at >= ?
    GROUP BY a.id, a.title, a.cover_image 
    ORDER BY clicks DESC 
    LIMIT 10
");
$stmt->execute([$start_date]);
$top_albums = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT sc.*, a.title as album_title, a.cover_image 
    FROM streaming_clicks sc 
    LEFT JOIN albums a ON sc.album_id = a.id 
    WHERE sc.clicked_at >= ? 
    ORDER BY sc.clicked_at DESC 
    LIMIT 20
");
$stmt->execute([$start_date]);
$recent_activity = $stmt->fetchAll();

// Calculate growth metrics
$prev_start_date = date('Y-m-d', strtotime("-" . ($date_range * 2) . " days"));
$prev_end_date = date('Y-m-d', strtotime("-{$date_range} days"));

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM streaming_clicks WHERE clicked_at BETWEEN ? AND ?");
$stmt->execute([$prev_start_date, $prev_end_date]);
$prev_clicks = $stmt->fetch()['total'];

$growth_rate = $prev_clicks > 0 ? (($total_clicks - $prev_clicks) / $prev_clicks) * 100 : 0;

// Platform mapping
$platform_names = [
    'spotify' => 'Spotify',
    'apple-music' => 'Apple Music',
    'youtube' => 'YouTube Music',
    'youtube-video' => 'YouTube Video',
    'soundcloud' => 'SoundCloud',
    'amazon-music' => 'Amazon Music',
    'tidal' => 'Tidal',
    'deezer' => 'Deezer',
    'bandcamp' => 'Bandcamp',
    'itunes' => 'iTunes'
];

$platform_icons = [
    'spotify' => '🎵',
    'apple-music' => '🍎', 
    'youtube' => '📺',
    'youtube-video' => '📹',
    'soundcloud' => '☁️',
    'amazon-music' => '📦',
    'tidal' => '🌊',
    'deezer' => '🎧',
    'bandcamp' => '💿',
    'itunes' => '🎶'
];

// Country names mapping
$country_names = [
    'US' => 'United States',
    'CA' => 'Canada',
    'GB' => 'United Kingdom',
    'DE' => 'Germany',
    'FR' => 'France',
    'AU' => 'Australia',
    'NL' => 'Netherlands',
    'SE' => 'Sweden',
    'NO' => 'Norway',
    'DK' => 'Denmark',
    'unknown' => 'Unknown'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Aurionix Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>Analytics Dashboard</h1>
                        <p>Track your music's performance and audience engagement</p>
                    </div>
                    <div class="header-actions">
                        <select id="dateRange" class="date-range-select">
                            <option value="7" <?= $date_range == '7' ? 'selected' : '' ?>>Last 7 days</option>
                            <option value="30" <?= $date_range == '30' ? 'selected' : '' ?>>Last 30 days</option>
                            <option value="90" <?= $date_range == '90' ? 'selected' : '' ?>>Last 90 days</option>
                            <option value="365" <?= $date_range == '365' ? 'selected' : '' ?>>Last year</option>
                        </select>
                        <button class="btn btn-outline" onclick="exportData()">📊 Export</button>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <h3><?= number_format($total_clicks) ?></h3>
                        <p>Total Clicks</p>
                    </div>
                    <div class="stat-trend <?= $growth_rate >= 0 ? 'positive' : 'negative' ?>">
                        <?= $growth_rate >= 0 ? '↗' : '↘' ?> <?= number_format(abs($growth_rate), 1) ?>%
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🌍</div>
                    <div class="stat-info">
                        <h3><?= count($country_clicks) ?></h3>
                        <p>Countries</p>
                    </div>
                    <div class="stat-trend neutral">📍 Global Reach</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎵</div>
                    <div class="stat-info">
                        <h3><?= count($platform_clicks) ?></h3>
                        <p>Platforms</p>
                    </div>
                    <div class="stat-trend positive">🔗 Multi-Platform</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h3><?= $total_clicks > 0 ? number_format($total_clicks / max(1, $date_range), 1) : '0' ?></h3>
                        <p>Avg. Daily Clicks</p>
                    </div>
                    <div class="stat-trend <?= $growth_rate >= 0 ? 'positive' : 'negative' ?>">
                        📊 Trending <?= $growth_rate >= 0 ? 'Up' : 'Down' ?>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Grid -->
            <div class="dashboard-grid">
                <!-- Clicks Chart -->
                <div class="dashboard-widget full-width">
                    <div class="widget-header">
                        <h2>Streaming Clicks Over Time</h2>
                        <div class="widget-actions">
                            <button class="btn-icon" onclick="refreshChart()" title="Refresh">🔄</button>
                        </div>
                    </div>
                    
                    <div class="widget-content">
                        <div class="chart-container">
                            <canvas id="clicksChart" width="100%" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Platform Distribution -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Platform Performance</h2>
                        <span class="widget-badge"><?= count($platform_clicks) ?> platforms</span>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (empty($platform_clicks)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🎵</div>
                                <h3>No click data yet</h3>
                                <p>Clicks will appear here once users interact with your streaming links</p>
                            </div>
                        <?php else: ?>
                            <div class="platform-stats">
                                <?php foreach ($platform_clicks as $platform): ?>
                                <div class="platform-item">
                                    <div class="platform-icon">
                                        <?= $platform_icons[$platform['platform']] ?? '🎵' ?>
                                    </div>
                                    <div class="platform-info">
                                        <h4><?= $platform_names[$platform['platform']] ?? ucfirst($platform['platform']) ?></h4>
                                        <p><?= number_format($platform['clicks']) ?> clicks</p>
                                    </div>
                                    <div class="platform-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= ($platform['clicks'] / max(1, $total_clicks)) * 100 ?>%"></div>
                                        </div>
                                        <span class="percentage"><?= number_format(($platform['clicks'] / max(1, $total_clicks)) * 100, 1) ?>%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Geographic Distribution -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Geographic Reach</h2>
                        <span class="widget-badge"><?= count($country_clicks) ?> countries</span>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (empty($country_clicks)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🌍</div>
                                <h3>No geographic data</h3>
                                <p>Country data will appear as users click your links</p>
                            </div>
                        <?php else: ?>
                            <div class="country-stats">
                                <?php foreach ($country_clicks as $country): ?>
                                <div class="country-item">
                                    <div class="country-info">
                                        <span class="country-flag"><?= getCountryFlag($country['country_code']) ?></span>
                                        <div class="country-details">
                                            <h4><?= $country_names[$country['country_code']] ?? $country['country_code'] ?></h4>
                                            <p><?= number_format($country['clicks']) ?> clicks</p>
                                        </div>
                                    </div>
                                    <div class="country-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= ($country['clicks'] / max(1, $total_clicks)) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top Performing Albums -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Top Albums</h2>
                        <a href="albums.php" class="btn btn-sm btn-outline">Manage</a>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (empty($top_albums) || $top_albums[0]['clicks'] == 0): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📀</div>
                                <h3>No album data</h3>
                                <p>Album performance will show here</p>
                                <a href="albums.php?action=add" class="btn btn-primary btn-sm">Add Album</a>
                            </div>
                        <?php else: ?>
                            <div class="albums-list">
                                <?php foreach (array_slice($top_albums, 0, 5) as $album): ?>
                                <div class="album-item">
                                    <div class="album-cover">
                                        <img src="<?= $album['cover_image'] ?: '../assets/default-cover.jpg' ?>" 
                                             alt="<?= htmlspecialchars($album['title']) ?>">
                                    </div>
                                    <div class="album-info">
                                        <h4><?= htmlspecialchars($album['title']) ?></h4>
                                        <p><?= number_format($album['clicks']) ?> clicks</p>
                                    </div>
                                    <div class="album-actions">
                                        <a href="albums.php?action=edit&id=<?= $album['id'] ?? '' ?>" 
                                           class="btn-icon" title="Edit">✏️</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="dashboard-widget full-width">
                    <div class="widget-header">
                        <h2>Recent Activity</h2>
                        <div class="activity-filters">
                            <button class="filter-btn active" data-filter="all">All</button>
                            <button class="filter-btn" data-filter="spotify">Spotify</button>
                            <button class="filter-btn" data-filter="apple-music">Apple Music</button>
                            <button class="filter-btn" data-filter="youtube">YouTube</button>
                        </div>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (empty($recent_activity)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📊</div>
                                <h3>No recent activity</h3>
                                <p>User interactions will appear here in real-time</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item" data-platform="<?= $activity['platform'] ?>">
                                    <div class="activity-icon">
                                        <?= $platform_icons[$activity['platform']] ?? '🎵' ?>
                                    </div>
                                    <div class="activity-content">
                                        <h4>
                                            <?= $platform_names[$activity['platform']] ?? ucfirst($activity['platform']) ?> click
                                            <?php if ($activity['album_title']): ?>
                                                on "<?= htmlspecialchars($activity['album_title']) ?>"
                                            <?php endif; ?>
                                        </h4>
                                        <p>
                                            From <?= $country_names[$activity['country_code']] ?? $activity['country_code'] ?>
                                            <?= getCountryFlag($activity['country_code']) ?>
                                        </p>
                                        <span class="activity-time">
                                            <?= date('M j, Y \a\t g:i A', strtotime($activity['clicked_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Analytics specific styles */
        .date-range-select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-size: 14px;
        }
        
        .widget-badge {
            background: rgba(233, 69, 96, 0.2);
            color: #e94560;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .platform-progress,
        .country-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 120px;
        }
        
        .percentage {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
            min-width: 35px;
            text-align: right;
        }
        
        .country-stats {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .country-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .country-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .country-flag {
            font-size: 1.5rem;
        }
        
        .country-details h4 {
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .country-details p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
        }
        
        .activity-item[data-platform] {
            transition: opacity 0.3s ease;
        }
        
        .activity-item.hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .platform-progress,
            .country-progress {
                min-width: 80px;
            }
        }
    </style>
    
    <script>
        // Initialize chart
        const ctx = document.getElementById('clicksChart').getContext('2d');
        const dailyData = <?= json_encode($daily_clicks) ?>;
        
        const clicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
                datasets: [{
                    label: 'Daily Clicks',
                    data: dailyData.map(d => d.clicks),
                    borderColor: '#e94560',
                    backgroundColor: 'rgba(233, 69, 96, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e94560',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        }
                    }
                }
            }
        });
        
        // Date range change
        document.getElementById('dateRange').addEventListener('change', function() {
            const range = this.value;
            window.location.href = `?range=${range}`;
        });
        
        // Activity filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter activity items
                const activities = document.querySelectorAll('.activity-item');
                activities.forEach(item => {
                    if (filter === 'all' || item.dataset.platform === filter) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });
        });
        
        function refreshChart() {
            location.reload();
        }
        
        function exportData() {
            // Create CSV data
            const data = dailyData.map(d => `${d.date},${d.clicks}`).join('\n');
            const csv = 'Date,Clicks\n' + data;
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `aurionix-analytics-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

<?php
function getCountryFlag($countryCode) {
    $flags = [
        'US' => '🇺🇸', 'CA' => '🇨🇦', 'GB' => '🇬🇧', 'DE' => '🇩🇪', 'FR' => '🇫🇷',
        'AU' => '🇦🇺', 'NL' => '🇳🇱', 'SE' => '🇸🇪', 'NO' => '🇳🇴', 'DK' => '🇩🇰',
        'BR' => '🇧🇷', 'MX' => '🇲🇽', 'JP' => '🇯🇵', 'KR' => '🇰🇷', 'IN' => '🇮🇳'
    ];
    return $flags[$countryCode] ?? '🌍';
}
?>