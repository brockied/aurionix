<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // Save return URL to session
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$pdo = get_db();
// Fetch cart items
$cartItems = [];
$total = 0.0;
if ($_SESSION['cart']) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("SELECT t.id, t.title, t.price FROM tracks t WHERE t.id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) {
        $total += (float)$item['price'];
    }
}

// Handle order completion (mocked)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Insert order into database
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total, payment_method, payment_status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], $total, 'manual', 'paid']);
    $orderId = $pdo->lastInsertId();
    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, track_id, price) VALUES (?, ?, ?)');
    foreach ($cartItems as $item) {
        $stmtItem->execute([$orderId, $item['id'], $item['price']]);
    }
    $pdo->commit();
    // Clear cart
    $_SESSION['cart'] = [];
    // Redirect to success page
    header('Location: success.php?order_id=' . $orderId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top:6rem;">
    <h1>Checkout</h1>
    <h2>Order Summary</h2>
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="border-bottom:1px solid var(--colour-card);">
          <th>Track</th>
          <th>Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cartItems as $item): ?>
        <tr style="border-bottom:1px solid var(--colour-card);">
          <td><?= htmlspecialchars($item['title']); ?></td>
          <td><?= format_price((float)$item['price']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:1rem;text-align:right;">
      <strong>Total:</strong> <?= format_price($total); ?>
    </div>
    <p style="margin-top:1rem;">Note: Payment integration via Stripe and PayPal is configurable in the admin panel. For this demo, clicking the button below will process your order immediately.</p>
    <form method="post" action="checkout.php">
      <button type="submit" class="btn btn--primary">Complete Order</button>
    </form>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>