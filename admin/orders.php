<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}
$pdo = get_db();
$orders = $pdo->query('SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orders &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
  <header class="admin-header">
    <div class="navbar__logo"><a href="dashboard.php"><span class="logo-text">Admin</span></a></div>
    <nav class="navbar__links">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="albums.php">Albums</a></li>
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php" class="active">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>
  <main class="admin-container">
    <h1>Orders</h1>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Method</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= $order['id']; ?></td>
          <td><?= htmlspecialchars($order['username']); ?></td>
          <td><?= format_price((float)$order['total']); ?></td>
          <td><?= htmlspecialchars($order['payment_method']); ?></td>
          <td><?= htmlspecialchars($order['payment_status']); ?></td>
          <td><?= htmlspecialchars($order['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
</body>
</html>