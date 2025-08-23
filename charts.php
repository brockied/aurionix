<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

$pdo = get_db();
// Get top 10 tracks by views in last 30 days
$stmt = $pdo->prepare(
    'SELECT t.title, SUM(v.view_count) AS views
     FROM tracks t
     JOIN views v ON v.track_id = t.id
     WHERE v.view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY t.id
     ORDER BY views DESC
     LIMIT 10'
);
$stmt->execute();
$data = $stmt->fetchAll();

// Prepare arrays
$labels = [];
$views  = [];
foreach ($data as $row) {
    $labels[] = $row['title'];
    $views[]  = (int) $row['views'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Charts &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top:6rem;">
    <h1>Charts</h1>
    <h2>Top Tracks (Last 30 Days)</h2>
    <canvas id="viewsChart" width="400" height="200"></canvas>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('viewsChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($labels); ?>,
          datasets: [{
            label: 'Views',
            data: <?= json_encode($views); ?>,
            backgroundColor: 'rgba(234,0,108,0.6)',
            borderColor: 'rgba(234,0,108,1)',
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