<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$configPath = __DIR__ . '/../config.php';

// Load current values
$current = [
    'SITE_NAME'         => SITE_NAME,
    'CURRENCY'          => CURRENCY,
    'STRIPE_PUBLIC_KEY' => STRIPE_PUBLIC_KEY,
    'STRIPE_SECRET_KEY' => STRIPE_SECRET_KEY,
    'PAYPAL_CLIENT_ID'  => PAYPAL_CLIENT_ID,
    'PAYPAL_SECRET'     => PAYPAL_SECRET,
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and update config file
    $updates = [
        'SITE_NAME'         => trim($_POST['site_name'] ?? $current['SITE_NAME']),
        'CURRENCY'          => trim($_POST['currency'] ?? $current['CURRENCY']),
        'STRIPE_PUBLIC_KEY' => trim($_POST['stripe_public'] ?? $current['STRIPE_PUBLIC_KEY']),
        'STRIPE_SECRET_KEY' => trim($_POST['stripe_secret'] ?? $current['STRIPE_SECRET_KEY']),
        'PAYPAL_CLIENT_ID'  => trim($_POST['paypal_client'] ?? $current['PAYPAL_CLIENT_ID']),
        'PAYPAL_SECRET'     => trim($_POST['paypal_secret'] ?? $current['PAYPAL_SECRET']),
    ];
    // Read config file
    $configContent = file_get_contents($configPath);
    foreach ($updates as $key => $value) {
        // simple replacement with regex
        $pattern = "/define\('\s*" . preg_quote($key, '/') . "\s*',\s*'(.*?)'\s*\);/";
        $replacement = "define('" . $key . "', '" . addslashes($value) . "');";
        $configContent = preg_replace($pattern, $replacement, $configContent);
    }
    // Write back
    file_put_contents($configPath, $configContent);
    $message = 'Settings saved successfully.';
    // Update current constants for use in this request
    foreach ($updates as $k => $v) {
        $current[$k] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings &middot; <?= SITE_NAME; ?></title>
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
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php" class="active">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>
  <main class="admin-container" style="max-width:700px;">
    <h1>Settings</h1>
    <?php if ($message): ?>
      <p style="color:#8f8;"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="post" action="settings.php">
      <label>Site Name</label>
      <input type="text" name="site_name" value="<?= htmlspecialchars($current['SITE_NAME']); ?>" required />
      <label>Currency</label>
      <input type="text" name="currency" value="<?= htmlspecialchars($current['CURRENCY']); ?>" required />
      <label>Stripe Public Key</label>
      <input type="text" name="stripe_public" value="<?= htmlspecialchars($current['STRIPE_PUBLIC_KEY']); ?>" />
      <label>Stripe Secret Key</label>
      <input type="text" name="stripe_secret" value="<?= htmlspecialchars($current['STRIPE_SECRET_KEY']); ?>" />
      <label>PayPal Client ID</label>
      <input type="text" name="paypal_client" value="<?= htmlspecialchars($current['PAYPAL_CLIENT_ID']); ?>" />
      <label>PayPal Secret</label>
      <input type="text" name="paypal_secret" value="<?= htmlspecialchars($current['PAYPAL_SECRET']); ?>" />
      <button type="submit" class="btn btn--primary" style="margin-top:1rem;">Save Settings</button>
    </form>
  </main>
</body>
</html>