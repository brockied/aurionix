<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();

// If admin logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['is_admin'] ?? 0)) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username && $password) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_admin = 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = 1;
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid admin credentials.';
        }
    } else {
        $errors[] = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
  <main class="admin-container" style="max-width:500px;">
    <h1>Admin Login</h1>
    <?php if ($errors): ?>
      <div class="error" style="color:#f88; margin-bottom:1rem;">
        <?php foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>'; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="index.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required />
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />
      <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1rem;">Login</button>
    </form>
    <p style="margin-top:1rem;"><a href="/">Return to site</a></p>
  </main>
</body>
</html>