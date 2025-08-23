<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();

// Enhanced stats with growth calculations
$totalAlbums = $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
$totalTracks = $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsers  = $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();

// Calculate growth percentages (last 30 days vs previous 30 days)
$albumGrowth = calculateGrowth('albums');
$trackGrowth = calculateGrowth('tracks');
$orderGrowth = calculateGrowth('orders');
$userGrowth = calculateGrowth('users');

// Total revenue and growth
$totalRevenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = "paid"')->fetchColumn();
$revenueGrowth = calculateRevenueGrowth();

// Total plays
$totalPlays = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM views WHERE track_id IS NOT NULL')->fetchColumn();
$playsGrowth = calculatePlaysGrowth();

// Recent activity
$recentOrders = $pdo->query('
    SELECT o.*, u.username, COUNT(oi.track_id) as track_count
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC 
    LIMIT 5
')->fetchAll();

$recentUsers = $pdo->query('
    SELECT username, email, created_at 
    FROM users 
    WHERE is_admin = 0 
    ORDER BY created_at DESC 
    LIMIT 5
')->fetchAll();

// Enhanced charts data
// Revenue by month (last 12 months)
$revenueStmt = $pdo->prepare('
    SELECT 
        DATE_FORMAT(created_at, "%Y-%m") AS month,
        DATE_FORMAT(created_at, "%M %Y") AS month_name,
        COALESCE(SUM(total), 0) AS revenue,
        COUNT(*) as order_count
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND payment_status = "paid"
    GROUP BY month, month_name
    ORDER BY month ASC
');
$revenueStmt->execute();
$revenueData = $revenueStmt->fetchAll();

// Top tracks by plays (last 30 days)
$topTracksStmt = $pdo->prepare('
    SELECT 
        t.title,
        a.title as album_title,
        COALESCE(SUM(v.view_count), 0) as plays,
        t.price
    FROM tracks t
    LEFT JOIN albums a ON t.album_id = a.id
    LEFT JOIN views v ON v.track_id = t.id AND v.view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY t.id
    ORDER BY plays DESC
    LIMIT 10
');
$topTracksStmt->execute();
$topTracksData = $topTracksStmt->fetchAll();

// Plays by day (last 30 days)
$playsStmt = $pdo->prepare('
    SELECT 
        view_date,
        SUM(view_count) as total_plays
    FROM views 
    WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND track_id IS NOT NULL
    GROUP BY view_date
    ORDER BY view_date ASC
');
$playsStmt->execute();
$playsData = $playsStmt->fetchAll();

// Album performance
$albumStatsStmt = $pdo->prepare('
    SELECT 
        a.title,
        a.featured,
        COUNT(t.id) as track_count,
        COALESCE(SUM(v.view_count), 0) as total_plays,
        COALESCE(SUM(oi.price), 0) as total_revenue
    FROM albums a
    LEFT JOIN tracks t ON a.id = t.album_id
    LEFT JOIN views v ON v.album_id = a.id
    LEFT JOIN order_items oi ON oi.track_id = t.id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = "paid"
    GROUP BY a.id
    ORDER BY total_plays DESC
    LIMIT 8
');
$albumStatsStmt->execute();
$albumStats = $albumStatsStmt->fetchAll();

// Helper functions
function calculateGrowth($table) {
    global $pdo;
    $current = $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    $previous = $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

function calculateRevenueGrowth() {
    global $pdo;
    $current = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = "paid"')->fetchColumn();
    $previous = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = "paid"')->fetchColumn();
    
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

function calculatePlaysGrowth() {
    global $pdo;
    $current = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND track_id IS NOT NULL')->fetchColumn();
    $previous = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND view_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND track_id IS NOT NULL')->fetchColumn();
    
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Aurionix Admin</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<body>
  <!-- Enhanced Admin Header -->
  <header class="admin-header">
    <div class="navbar__logo">
      <a href="/admin/dashboard.php">
        <span class="logo-text">Aurionix Admin</span>
      </a>
    </div>
    <nav class="navbar__links">
      <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="albums.php">Albums</a></li>
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
    <div class="admin-user">
      <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
      <span><?= htmlspecialchars($_SESSION['username']); ?></span>
    </div>
  </header>

  <main class="admin-container">
    <div class="page-header">
      <h1>Dashboard</h1>
      <p class="page-subtitle">Welcome back! Here's what's happening with your music.</p>
    </div>

    <!-- Enhanced Stats Grid -->
    <div class="stats-grid">
      <div class="stats-card fade-in">
        <div class="stats-card-icon primary">💿</div>
        <h3>Total Albums</h3>
        <div class="stats-number"><?= number_format($totalAlbums); ?></div>
        <div class="stats-change <?= $albumGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $albumGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($albumGrowth); ?>% this month</span>
        </div>
      </div>

      <div class="stats-card fade-in" style="animation-delay: 0.1s;">
        <div class="stats-card-icon secondary">🎵</div>
        <h3>Total Tracks</h3>
        <div class="stats-number"><?= number_format($totalTracks); ?></div>
        <div class="stats-change <?= $trackGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $trackGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($trackGrowth); ?>% this month</span>
        </div>
      </div>

      <div class="stats-card fade-in" style="animation-delay: 0.2s;">
        <div class="stats-card-icon success">💰</div>
        <h3>Total Revenue</h3>
        <div class="stats-number"><?= format_price((float)$totalRevenue); ?></div>
        <div class="stats-change <?= $revenueGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $revenueGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($revenueGrowth); ?>% this month</span>
        </div>
      </div>

      <div class="stats-card fade-in" style="animation-delay: 0.3s;">
        <div class="stats-card-icon warning">▶️</div>
        <h3>Total Plays</h3>
        <div class="stats-number"><?= number_format($totalPlays); ?></div>
        <div class="stats-change <?= $playsGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $playsGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($playsGrowth); ?>% this month</span>
        </div>
      </div>

      <div class="stats-card fade-in" style="animation-delay: 0.4s;">
        <div class="stats-card-icon primary">👥</div>
        <h3>Total Fans</h3>
        <div class="stats-number"><?= number_format($totalUsers); ?></div>
        <div class="stats-change <?= $userGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $userGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($userGrowth); ?>% this month</span>
        </div>
      </div>

      <div class="stats-card fade-in" style="animation-delay: 0.5s;">
        <div class="stats-card-icon secondary">🛒</div>
        <h3>Total Orders</h3>
        <div class="stats-number"><?= number_format($totalOrders); ?></div>
        <div class="stats-change <?= $orderGrowth >= 0 ? 'positive' : 'negative'; ?>">
          <span><?= $orderGrowth >= 0 ? '↗' : '↘' ?></span>
          <span><?= abs($orderGrowth); ?>% this month</span>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
      <!-- Revenue Chart -->
      <div class="chart-container">
        <div class="chart-header">
          <h3 class="chart-title">Revenue Overview</h3>
          <p class="chart-subtitle">Monthly revenue for the last 12 months</p>
        </div>
        <canvas id="revenueChart" height="300"></canvas>
      </div>

      <!-- Top Tracks -->
      <div class="chart-container">
        <div class="chart-header">
          <h3 class="chart-title">🔥 Trending Tracks</h3>
          <p class="chart-subtitle">Most played this month</p>
        </div>
        <div class="top-tracks-list">
          <?php foreach (array_slice($topTracksData, 0, 5) as $index => $track): ?>
          <div class="top-track-item">
            <div class="track-rank">#<?= $index + 1; ?></div>
            <div class="track-info">
              <div class="track-name"><?= htmlspecialchars($track['title']); ?></div>
              <div class="track-album"><?= htmlspecialchars($track['album_title'] ?: 'Single'); ?></div>
            </div>
            <div class="track-plays"><?= number_format($track['plays']); ?> plays</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Second Row Charts -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
      <!-- Plays Chart -->
      <div class="chart-container">
        <div class="chart-header">
          <h3 class="chart-title">Daily Plays</h3>
          <p class="chart-subtitle">Track plays over the last 30 days</p>
        </div>
        <canvas id="playsChart" height="250"></canvas>
      </div>

      <!-- Album Performance -->
      <div class="chart-container">
        <div class="chart-header">
          <h3 class="chart-title">Album Performance</h3>
          <p class="chart-subtitle">Plays by album</p>
        </div>
        <canvas id="albumChart" height="250"></canvas>
      </div>
    </div>

    <!-- Recent Activity -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
      <!-- Recent Orders -->
      <div class="admin-table-container">
        <div class="admin-table-header">
          <h3 class="admin-table-title">Recent Orders</h3>
          <a href="orders.php" class="btn btn--sm btn--outline">View All</a>
        </div>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Items</th>
              <th>Total</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><?= htmlspecialchars($order['username'] ?: 'Guest'); ?></td>
              <td><?= $order['track_count']; ?> tracks</td>
              <td><?= format_price((float)$order['total']); ?></td>
              <td><?= date('M j', strtotime($order['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Recent Users -->
      <div class="admin-table-container">
        <div class="admin-table-header">
          <h3 class="admin-table-title">New Fans</h3>
          <span class="status-badge active">+<?= $userGrowth; ?>% this month</span>
        </div>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentUsers as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['username']); ?></td>
              <td><?= htmlspecialchars($user['email']); ?></td>
              <td><?= date('M j', strtotime($user['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    // Chart.js configuration
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.1)';
    Chart.defaults.backgroundColor = 'rgba(99, 102, 241, 0.1)';

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($revenueData, 'month_name')); ?>,
        datasets: [{
          label: 'Revenue (<?= CURRENCY; ?>)',
          data: <?= json_encode(array_column($revenueData, 'revenue')); ?>,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#6366f1',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 6,
          pointHoverRadius: 8
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
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, 0.1)'
            },
            ticks: {
              callback: function(value) {
                return '<?= CURRENCY; ?>' + value.toLocaleString();
              }
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        elements: {
          point: {
            hoverBackgroundColor: '#8b5cf6'
          }
        }
      }
    });

    // Plays Chart
    const playsCtx = document.getElementById('playsChart').getContext('2d');
    new Chart(playsCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_map(function($item) { return date('M j', strtotime($item['view_date'])); }, $playsData)); ?>,
        datasets: [{
          label: 'Plays',
          data: <?= json_encode(array_column($playsData, 'total_plays')); ?>,
          backgroundColor: 'rgba(139, 92, 246, 0.8)',
          borderColor: '#8b5cf6',
          borderWidth: 0,
          borderRadius: 4,
          borderSkipped: false,
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
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, 0.1)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    // Album Performance Chart
    const albumCtx = document.getElementById('albumChart').getContext('2d');
    new Chart(albumCtx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_column($albumStats, 'title')); ?>,
        datasets: [{
          data: <?= json_encode(array_column($albumStats, 'total_plays')); ?>,
          backgroundColor: [
            '#6366f1', '#8b5cf6', '#06b6d4', '#10b981',
            '#f59e0b', '#ef4444', '#ec4899', '#84cc16'
          ],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              font: {
                size: 12
              }
            }
          }
        }
      }
    });

    // Animation on scroll
    const observeElements = () => {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
          }
        });
      }, { threshold: 0.1 });

      document.querySelectorAll('.stats-card, .chart-container').forEach(el => {
        observer.observe(el);
      });
    };

    document.addEventListener('DOMContentLoaded', observeElements);
  </script>

  <style>
    .top-tracks-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      max-height: 300px;
      overflow-y: auto;
    }

    .top-track-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: var(--admin-bg-secondary);
      border-radius: var(--admin-border-radius);
      transition: all 0.2s ease;
    }

    .top-track-item:hover {
      background: rgba(99, 102, 241, 0.1);
      transform: translateX(4px);
    }

    .track-rank {
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--admin-primary);
      min-width: 2rem;
      text-align: center;
    }

    .track-info {
      flex: 1;
      min-width: 0;
    }

    .track-name {
      font-weight: 600;
      color: var(--admin-text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .track-album {
      font-size: 0.875rem;
      color: var(--admin-text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .track-plays {
      font-weight: 600;
      color: var(--admin-text-light);
      font-size: 0.875rem;
      white-space: nowrap;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(3, 1fr);
      }
      
      div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr;
      }
      
      div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 640px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</body>
</html>