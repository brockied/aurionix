<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();
// Quick stats
$totalAlbums = $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
$totalTracks = $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsers  = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

// Sales chart: revenue per month for last 6 months
$salesStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, SUM(total) AS revenue
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month
     ORDER BY month ASC'
);
$salesStmt->execute();
$salesData = $salesStmt->fetchAll();
$salesLabels = [];
$salesValues = [];
foreach ($salesData as $row) {
    $salesLabels[] = $row['month'];
    $salesValues[] = (float)$row['revenue'];
}

// Views chart: top 5 albums by views
$viewsStmt = $pdo->prepare(
    'SELECT a.title, SUM(v.view_count) AS views
     FROM albums a
     JOIN views v ON v.album_id = a.id
     GROUP BY a.id
     ORDER BY views DESC
     LIMIT 5'
);
$viewsStmt->execute();
$viewsData = $viewsStmt->fetchAll();
$viewsLabels = [];
$viewsValues = [];
foreach ($viewsData as $row) {
    $viewsLabels[] = $row['title'];
    $viewsValues[] = (int)$row['views'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header class="admin-header">
    <div class="navbar__logo"><a href="/admin/dashboard.php"><span class="logo-text">Admin</span></a></div>
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
  </header>
  <main class="admin-container">
    <h1>Dashboard</h1>
    <div class="stats-grid">
      <div class="stats-card">
        <h3>Total Albums</h3>
        <p><?= $totalAlbums; ?></p>
      </div>
      <div class="stats-card">
        <h3>Total Tracks</h3>
        <p><?= $totalTracks; ?></p>
      </div>
      <div class="stats-card">
        <h3>Orders</h3>
        <p><?= $totalOrders; ?></p>
      </div>
      <div class="stats-card">
        <h3>Users</h3>
        <p><?= $totalUsers; ?></p>
      </div>
    </div>
    <div style="margin-top:2rem;">
      <h2>Revenue (Last 6 Months)</h2>
      <canvas id="salesChart" height="200"></canvas>
    </div>
    <div style="margin-top:2rem;">
      <h2>Top Albums by Views</h2>
      <canvas id="viewsChart" height="200"></canvas>
    </div>
  </main>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const salesCtx = document.getElementById('salesChart').getContext('2d');
      new Chart(salesCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($salesLabels); ?>,
          datasets: [{
            label: 'Revenue (<?= CURRENCY; ?>)',
            data: <?= json_encode($salesValues); ?>,
            borderColor: 'rgba(234,0,108,1)',
            backgroundColor: 'rgba(234,0,108,0.2)',
            tension: 0.3
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
      const viewsCtx = document.getElementById('viewsChart').getContext('2d');
      new Chart(viewsCtx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($viewsLabels); ?>,
          datasets: [{
            label: 'Views',
            data: <?= json_encode($viewsValues); ?>,
            backgroundColor: 'rgba(126,0,192,0.6)',
            borderColor: 'rgba(126,0,192,1)',
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    });
  </script>
</body>
</html>