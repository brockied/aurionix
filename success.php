<?php
require_once __DIR__ . '/config.php';
session_start();
// Optionally you can verify order id
$orderId = (int)($_GET['order_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Complete &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top:6rem;">
    <h1>Thank you for your purchase!</h1>
    <p>Your order has been processed successfully.</p>
    <?php if ($orderId): ?>
      <p>Your order reference: <strong>#<?= $orderId; ?></strong></p>
    <?php endif; ?>
    <p><a href="/">Return to home page</a></p>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>