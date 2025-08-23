<?php
/*
 * Aurionix Installer
 *
 * This installer guides you through setting up your music store.
 * It accepts your database credentials and site settings, creates
 * necessary tables, writes a configuration file and inserts an
 * administrator account. Running the installer multiple times
 * after configuration has been created will simply display a
 * message that the script is already installed.
 */

if (file_exists(__DIR__ . '/config.php')) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Installation Complete</title>';
    echo '<style>body{font-family:sans-serif;background:#12002f;color:#f5f5f5;text-align:center;padding:2rem;}a{color:#ff8e8e;text-decoration:none;}</style>';
    echo '<h1>Installation Complete</h1>';
    echo '<p>A configuration file already exists. To reinstall, delete config.php.</p>';
    echo '<p><a href="index.php">Go to your site</a></p>';
    echo '</body></html>';
    exit;
}

$errors = [];
$message = '';

function render_form($values = [])
{
    // Output the installation form.
    $defaults = [
        'db_host'     => 'localhost',
        'db_user'     => 'root',
        'db_pass'     => '',
        'db_name'     => 'aurionix',
        'site_name'   => 'Aurionix',
        'site_url'    => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
        'currency'    => 'GBP',
        'admin_user'  => 'admin',
        'admin_pass'  => '',
        'admin_email' => '',
    ];
    $values = array_merge($defaults, $values);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Aurionix Installation</title>
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #12002f;
                color: #f5f5f5;
                padding: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .install-box {
                background-color: #1f0433;
                padding: 2rem;
                border-radius: 8px;
                width: 100%;
                max-width: 600px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.5);
            }
            h1 { margin-top: 0; color: #ea006c; text-align:center; }
            label { display:block; margin: 0.5rem 0 0.2rem; }
            input[type=text], input[type=password], input[type=email] {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #4b005e;
                border-radius: 4px;
                background-color: #25073f;
                color: #f5f5f5;
            }
            .btn {
                margin-top: 1rem;
                padding: 0.75rem 1.5rem;
                background-color: #ea006c;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
                width: 100%;
            }
            .error { color: #f88; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <form class="install-box" method="post" action="install.php">
            <h1>Install Aurionix</h1>
            <?php global $errors, $message; ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <label>Database Host</label>
            <input type="text" name="db_host" value="<?php echo htmlspecialchars($values['db_host']); ?>" required>
            <label>Database User</label>
            <input type="text" name="db_user" value="<?php echo htmlspecialchars($values['db_user']); ?>" required>
            <label>Database Password</label>
            <input type="password" name="db_pass" value="<?php echo htmlspecialchars($values['db_pass']); ?>">
            <label>Database Name</label>
            <input type="text" name="db_name" value="<?php echo htmlspecialchars($values['db_name']); ?>" required>

            <label>Site Name</label>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($values['site_name']); ?>" required>
            <label>Site URL (no trailing slash)</label>
            <input type="text" name="site_url" value="<?php echo htmlspecialchars($values['site_url']); ?>" required>
            <label>Currency (e.g. GBP, USD)</label>
            <input type="text" name="currency" value="<?php echo htmlspecialchars($values['currency']); ?>" required>

            <label>Admin Username</label>
            <input type="text" name="admin_user" value="<?php echo htmlspecialchars($values['admin_user']); ?>" required>
            <label>Admin Password</label>
            <input type="password" name="admin_pass" required>
            <label>Admin Email</label>
            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($values['admin_email']); ?>" required>
            <button type="submit" class="btn">Install</button>
        </form>
    </body>
    </html>
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host     = trim($_POST['db_host'] ?? '');
    $db_user     = trim($_POST['db_user'] ?? '');
    $db_pass     = trim($_POST['db_pass'] ?? '');
    $db_name     = trim($_POST['db_name'] ?? '');
    $site_name   = trim($_POST['site_name'] ?? '');
    $site_url    = trim($_POST['site_url'] ?? '');
    $currency    = trim($_POST['currency'] ?? '');
    $admin_user  = trim($_POST['admin_user'] ?? '');
    $admin_pass  = trim($_POST['admin_pass'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');

    // Validate
    if (!$db_host || !$db_user || !$db_name) {
        $errors[] = 'Please fill in all database fields.';
    }
    if (!$site_name || !$site_url || !$currency) {
        $errors[] = 'Please fill in all site information fields.';
    }
    if (!$admin_user || !$admin_pass || !$admin_email) {
        $errors[] = 'Please fill in all admin fields.';
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email is invalid.';
    }
    if (!$errors) {
        // Attempt DB connection
        try {
            $dsn = "mysql:host=$db_host;charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");

            // Create tables
            $sql = [
                "CREATE TABLE IF NOT EXISTS users (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "username VARCHAR(100) NOT NULL UNIQUE,\n"
                . "email VARCHAR(255) NOT NULL,\n"
                . "password VARCHAR(255) NOT NULL,\n"
                . "is_admin TINYINT(1) DEFAULT 0,\n"
                . "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n"
                . ") ENGINE=InnoDB;",

                "CREATE TABLE IF NOT EXISTS albums (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "title VARCHAR(255) NOT NULL,\n"
                . "description TEXT,\n"
                . "cover VARCHAR(255) DEFAULT 'default-cover.png',\n"
                . "featured TINYINT(1) DEFAULT 0,\n"
                . "spotify_url VARCHAR(255),\n"
                . "apple_url VARCHAR(255),\n"
                . "other_url VARCHAR(255),\n"
                . "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n"
                . ") ENGINE=InnoDB;",

                "CREATE TABLE IF NOT EXISTS tracks (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "album_id INT,\n"
                . "title VARCHAR(255) NOT NULL,\n"
                . "description TEXT,\n"
                . "audio_file VARCHAR(255),\n"
                . "price DECIMAL(10,2) DEFAULT 0.00,\n"
                . "track_number INT,\n"
                . "spotify_url VARCHAR(255),\n"
                . "apple_url VARCHAR(255),\n"
                . "other_url VARCHAR(255),\n"
                . "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
                . "FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE\n"
                . ") ENGINE=InnoDB;",

                "CREATE TABLE IF NOT EXISTS orders (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "user_id INT,\n"
                . "total DECIMAL(10,2) NOT NULL,\n"
                . "payment_method VARCHAR(50),\n"
                . "payment_status VARCHAR(50) DEFAULT 'pending',\n"
                . "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
                . "FOREIGN KEY (user_id) REFERENCES users(id)\n"
                . ") ENGINE=InnoDB;",

                "CREATE TABLE IF NOT EXISTS order_items (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "order_id INT,\n"
                . "track_id INT,\n"
                . "price DECIMAL(10,2) NOT NULL,\n"
                . "FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,\n"
                . "FOREIGN KEY (track_id) REFERENCES tracks(id)\n"
                . ") ENGINE=InnoDB;",

                "CREATE TABLE IF NOT EXISTS views (\n"
                . "id INT AUTO_INCREMENT PRIMARY KEY,\n"
                . "track_id INT,\n"
                . "album_id INT,\n"
                . "view_date DATE,\n"
                . "view_count INT DEFAULT 1,\n"
                . "UNIQUE KEY unique_view (track_id, album_id, view_date)\n"
                . ") ENGINE=InnoDB;"
            ];

            foreach ($sql as $query) {
                $pdo->exec($query);
            }

            // Insert admin user
            $hashed = password_hash($admin_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$admin_user, $admin_email, $hashed]);

            // Write config.php
            $configContent = "<?php\n"
                . "define('DB_HOST', '" . addslashes($db_host) . "');\n"
                . "define('DB_USER', '" . addslashes($db_user) . "');\n"
                . "define('DB_PASS', '" . addslashes($db_pass) . "');\n"
                . "define('DB_NAME', '" . addslashes($db_name) . "');\n"
                . "define('SITE_NAME', '" . addslashes($site_name) . "');\n"
                . "define('SITE_URL', '" . addslashes(rtrim($site_url, '/')) . "');\n"
                . "define('CURRENCY', '" . addslashes($currency) . "');\n"
                . "define('STRIPE_PUBLIC_KEY', '');\n"
                . "define('STRIPE_SECRET_KEY', '');\n"
                . "define('PAYPAL_CLIENT_ID', '');\n"
                . "define('PAYPAL_SECRET', '');\n"
                . "define('DEBUG_MODE', false);\n";

            file_put_contents(__DIR__ . '/config.php', $configContent);

            // Create uploads directories
            @mkdir(__DIR__ . '/uploads', 0775);
            @mkdir(__DIR__ . '/uploads/albums', 0775, true);
            @mkdir(__DIR__ . '/uploads/tracks', 0775, true);

            $message = 'Installation successful! You can now log into the admin panel.';
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Installation Complete</title>';
            echo '<style>body{font-family:sans-serif;background:#12002f;color:#f5f5f5;text-align:center;padding:2rem;}a{color:#ff8e8e;text-decoration:none;}</style>';
            echo '<h1>Installation Complete</h1>';
            echo '<p>Congratulations! The installation was successful.</p>';
            echo '<p><a href="admin/index.php">Go to Admin Panel</a> &middot; <a href="index.php">Visit your site</a></p>';
            echo '</body></html>';
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    render_form($_POST);
} else {
    render_form();
}