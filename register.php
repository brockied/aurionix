<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');
    if (!$username || !$email || !$password) {
        $errors[] = 'Please fill in all fields.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!$errors) {
        $pdo = get_db();
        // Check if username exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $pdo->prepare('INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)');
            $stmt2->execute([$username, $email, $hash]);
            // Auto login
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = 0;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register &middot; <?= SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="container" style="padding-top: 6rem; max-width: 500px;">
    <h1>Register</h1>
    <?php if ($errors): ?>
      <div class="error" style="color: #f88; margin-bottom: 1rem;">
        <?php foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>'; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="register.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required />
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required />
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />
      <label for="confirm">Confirm Password</label>
      <input type="password" id="confirm" name="confirm" required />
      <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1rem;">Register</button>
    </form>
    <p style="margin-top:1rem;">Already have an account? <a href="login.php">Log in</a>.</p>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>