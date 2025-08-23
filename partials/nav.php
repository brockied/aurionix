<?php
/*
 * Navigation bar
 */
?>
<header class="navbar">
  <div class="navbar__logo">
    <a href="/">
      <span class="logo-text"><?= SITE_NAME; ?></span>
    </a>
  </div>
  <nav class="navbar__links" aria-label="Primary navigation">
    <ul>
      <li><a href="/"<?= ($_SERVER['SCRIPT_NAME'] === '/index.php') ? ' class="active"' : ''; ?>>Home</a></li>
      <li><a href="/album_list.php"<?= ($_SERVER['SCRIPT_NAME'] === '/album_list.php') ? ' class="active"' : ''; ?>>Albums</a></li>
      <li><a href="/charts.php"<?= ($_SERVER['SCRIPT_NAME'] === '/charts.php') ? ' class="active"' : ''; ?>>Charts</a></li>
      <li><a href="/cart.php"<?= ($_SERVER['SCRIPT_NAME'] === '/cart.php') ? ' class="active"' : ''; ?>>Cart</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="/logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="/login.php">Login</a></li>
        <li><a href="/register.php">Register</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <div class="navbar__actions">
    <?php if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] ?? 0): ?>
      <a href="/admin/dashboard.php" class="btn btn--secondary">Admin</a>
    <?php endif; ?>
  </div>
</header>