<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
session_start();

// Initialise cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action  = $_GET['action'] ?? '';
$trackId = isset($_GET['track_id']) ? (int)$_GET['track_id'] : 0;

switch ($action) {
    case 'add':
        if ($trackId) {
            $_SESSION['cart'][$trackId] = 1; // quantity 1
        }
        header('Location: cart.php');
        exit;
    case 'remove':
        if ($trackId && isset($_SESSION['cart'][$trackId])) {
            unset($_SESSION['cart'][$trackId]);
        }
        header('Location: cart.php');
        exit;
    case 'clear':
        $_SESSION['cart'] = [];
        header('Location: cart.php');
        exit;
}

// Fetch cart items
$cartItems = [];
$total = 0.0;
if ($_SESSION['cart']) {
    $pdo = get_db();
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("SELECT t.id, t.title, t.price, a.title AS album_title, t.audio_file, a.cover FROM tracks t JOIN albums a ON t.album_id = a.id WHERE t.id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $cartItems[] = $row;
        $total += (float)$row['price'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cart &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top:6rem;">
    <h1>Your Cart</h1>
    <?php if (!$cartItems): ?>
      <p>Your cart is empty.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid var(--colour-card);">
            <th>Track</th>
            <th>Album</th>
            <th>Price</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cartItems as $item): ?>
          <tr style="border-bottom:1px solid var(--colour-card);">
            <td><?= htmlspecialchars($item['title']); ?></td>
            <td><?= htmlspecialchars($item['album_title']); ?></td>
            <td><?= format_price((float)$item['price']); ?></td>
            <td><a href="cart.php?action=remove&track_id=<?= urlencode($item['id']); ?>" class="btn btn--outline" style="padding:0.3rem 0.6rem;font-size:0.8rem;">Remove</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:1rem;text-align:right;">
        <strong>Total:</strong> <?= format_price($total); ?>
      </div>
      <div style="margin-top:1rem;text-align:right;">
        <a href="/checkout.php" class="btn btn--primary">Checkout</a>
        <a href="cart.php?action=clear" class="btn btn--outline">Clear Cart</a>
      </div>
    <?php endif; ?>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>