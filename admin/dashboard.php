<?php
/**
 * ADMIN DASHBOARD
 * Place this file as: admin/dashboard.php
 */

require_once '../config.php';
requireAdmin();

// Get statistics
$stats = [];

// Total albums
$stmt = $pdo->query("SELECT COUNT(*) as count FROM albums");
$stats['total_albums'] = $stmt->fetch()['count'];

// Featured albums
$stmt = $pdo->query("SELECT COUNT(*) as count FROM albums WHERE featured = 1");
$stats['featured_albums'] = $stmt->fetch()['count'];

// Total streaming links
$stmt = $pdo->query("SELECT COUNT(*) as count FROM streaming_links");
$stats['streaming_links'] = $stmt->fetch()['count'];

// Recent albums
$stmt = $pdo->query("SELECT * FROM albums ORDER BY created_at DESC LIMIT 5");
$recent_albums = $stmt->fetchAll();

// Platform statistics
$stmt = $pdo->query("SELECT platform, COUNT(*) as count FROM streaming_links GROUP BY platform ORDER BY count DESC");
$platform_stats = $stmt->fetchAll();

// Monthly clicks (dummy data for demo)
$monthly_clicks = [
    ['month' => 'Jan', 'clicks' => 1250],
    ['month' => 'Feb', 'clicks' => 1890],
    ['month' => 'Mar', 'clicks' => 2340],
    ['month' => 'Apr', 'clicks' => 1970],
    ['month' => 'May', 'clicks' => 2650],
    ['month' => 'Jun', 'clicks' => 3120]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aurionix Admin</title>
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
                <h1>Dashboard</h1>
                <p>Welcome back! Here's what's happening with your music.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎵</div>
                    <div class="stat-info">
                        <h3><?= $stats['total_albums'] ?></h3>
                        <p>Total Albums</p>
                    </div>
                    <div class="stat-trend positive">↗ +5%</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-info">
                        <h3><?= $stats['featured_albums'] ?></h3>
                        <p>Featured Albums</p>
                    </div>
                    <div class="stat-trend positive">↗ +12%</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔗</div>
                    <div class="stat-info">
                        <h3><?= $stats['streaming_links'] ?></h3>
                        <p>Streaming Links</p>
                    </div>
                    <div class="stat-trend neutral">→ 0%</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>15.2K</h3>
                        <p>Monthly Plays</p>
                    </div>
                    <div class="stat-trend positive">↗ +28%</div>
                </div>
            </div>
            
            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Albums -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Recent Albums</h2>
                        <a href="albums.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (empty($recent_albums)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📀</div>
                                <h3>No albums yet</h3>
                                <p>Add your first album to get started</p>
                                <a href="albums.php?action=add" class="btn btn-primary">Add Album</a>
                            </div>
                        <?php else: ?>
                            <div class="albums-list">
                                <?php foreach ($recent_albums as $album): ?>
                                <div class="album-item">
                                    <div class="album-cover">
                                        <img src="<?= $album['cover_image'] ?: '../assets/default-cover.jpg' ?>" 
                                             alt="<?= htmlspecialchars($album['title']) ?>">
                                    </div>
                                    <div class="album-info">
                                        <h4><?= htmlspecialchars($album['title']) ?></h4>
                                        <p><?= date('M j, Y', strtotime($album['release_date'])) ?></p>
                                    </div>
                                    <div class="album-status">
                                        <?php if ($album['featured']): ?>
                                            <span class="badge badge-featured">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="album-actions">
                                        <a href="albums.php?action=edit&id=<?= $album['id'] ?>" class="btn-icon" title="Edit">✏️</a>
                                        <a href="albums.php?action=delete&id=<?= $album['id'] ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure?')">🗑️</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Analytics Chart -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Monthly Clicks</h2>
                        <select class="period-select">
                            <option>Last 6 months</option>
                            <option>Last 12 months</option>
                            <option>This year</option>
                        </select>
                    </div>
                    
                    <div class="widget-content">
                        <div class="chart-container">
                            <canvas id="clicksChart" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="chart-summary">
                            <div class="chart-stat">
                                <span class="stat-value">2,650</span>
                                <span class="stat-label">This Month</span>
                            </div>
                            <div class="chart-stat">
                                <span class="stat-value">+18%</span>
                                <span class="stat-label">vs Last Month</span>
                            </div>
                            <div class="chart-stat">
                                <span class="stat-value">13.2K</span>
                                <span class="stat-label">Total 6 Months</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Platform Statistics -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Platform Distribution</h2>
                        <div class="widget-actions">
                            <button class="btn-icon" title="Refresh">🔄</button>
                        </div>
                    </div>
                    
                    <div class="widget-content">
                        <div class="platform-stats">
                            <?php foreach ($platform_stats as $platform): ?>
                            <div class="platform-item">
                                <div class="platform-icon">
                                    <?= getPlatformIcon($platform['platform']) ?>
                                </div>
                                <div class="platform-info">
                                    <h4><?= ucfirst(str_replace('-', ' ', $platform['platform'])) ?></h4>
                                    <p><?= $platform['count'] ?> links</p>
                                </div>
                                <div class="platform-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= ($platform['count'] / $stats['streaming_links']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h2>Quick Actions</h2>
                    </div>
                    
                    <div class="widget-content">
                        <div class="quick-actions">
                            <a href="albums.php?action=add" class="quick-action">
                                <div class="action-icon">➕</div>
                                <h3>Add Album</h3>
                                <p>Upload a new album with tracks</p>
                            </a>
                            
                            <a href="streaming-links.php" class="quick-action">
                                <div class="action-icon">🔗</div>
                                <h3>Manage Links</h3>
                                <p>Add streaming platform links</p>
                            </a>
                            
                            <a href="settings.php" class="quick-action">
                                <div class="action-icon">⚙️</div>
                                <h3>Settings</h3>
                                <p>Configure site preferences</p>
                            </a>
                            
                            <a href="../" target="_blank" class="quick-action">
                                <div class="action-icon">🌐</div>
                                <h3>View Site</h3>
                                <p>See your public website</p>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="dashboard-widget full-width">
                    <div class="widget-header">
                        <h2>Recent Activity</h2>
                        <div class="activity-filters">
                            <button class="filter-btn active">All</button>
                            <button class="filter-btn">Albums</button>
                            <button class="filter-btn">Clicks</button>
                            <button class="filter-btn">Settings</button>
                        </div>
                    </div>
                    
                    <div class="widget-content">
                        <div class="activity-timeline">
                            <div class="activity-item">
                                <div class="activity-icon">🎵</div>
                                <div class="activity-content">
                                    <h4>New album "Electronic Dreams" added</h4>
                                    <p>Album was successfully uploaded with 8 tracks</p>
                                    <span class="activity-time">2 hours ago</span>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon">🔗</div>
                                <div class="activity-content">
                                    <h4>Spotify link added for "Night Vibes"</h4>
                                    <p>Streaming link configured for US market</p>
                                    <span class="activity-time">5 hours ago</span>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon">📊</div>
                                <div class="activity-content">
                                    <h4>Monthly analytics report generated</h4>
                                    <p>156% increase in streaming clicks this month</p>
                                    <span class="activity-time">1 day ago</span>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon">⚙️</div>
                                <div class="activity-content">
                                    <h4>Site settings updated</h4>
                                    <p>Hero section title and description modified</p>
                                    <span class="activity-time">2 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="admin-script.js"></script>
    
    <script>
        // Initialize clicks chart
        const ctx = document.getElementById('clicksChart').getContext('2d');
        const clicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthly_clicks, 'month')) ?>,
                datasets: [{
                    label: 'Clicks',
                    data: <?= json_encode(array_column($monthly_clicks, 'clicks')) ?>,
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
                            display: false
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
        
        // Activity filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter activity items (implement filtering logic here)
                const filter = this.textContent.toLowerCase();
                // Add filtering logic based on filter value
            });
        });
    </script>
</body>
</html>

<?php
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