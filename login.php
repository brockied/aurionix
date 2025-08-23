<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username && $password) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (int) $user['is_admin'];
            if ($user['is_admin']) {
                header('Location: /admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    } else {
        $errors[] = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top: 6rem; max-width: 500px;">
    <h1>Login</h1>
    <?php if ($errors): ?>
      <div class="error" style="color: #f88; margin-bottom: 1rem;">
        <?php foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>'; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required />
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />
      <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1rem;">Log In</button>
    </form>
    <p style="margin-top:1rem;">Don't have an account? <a href="register.php">Register here</a>.</p>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>