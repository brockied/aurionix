<?php
/**
 * PROFILE SETTINGS PAGE
 *
 * This page allows administrators to view their account details and update
 * their password.  Email addresses cannot be changed here to avoid
 * accidental lockouts; create a new user instead if you need a different
 * login.  Passwords are hashed using PHP's password_hash().
 */

require_once '../config.php';
requireAdmin();

$message = '';
$error   = '';

// Fetch the current user's details.  Selecting all columns avoids referencing
// columns that may not exist in some database schemas (e.g. last_login).  We
// then explicitly assign a default for last_login if it isn't present.
$userId = $_SESSION['user_id'];
$stmt   = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    // If the user record can't be found, initialize an empty array to prevent
    // undefined index notices later on.  The page will display blanks.
    $user = [
        'email'      => '',
        'role'       => '',
        'created_at' => null,
        'last_login' => null,
    ];
}
// Ensure last_login key exists even if the column is missing
if (!isset($user['last_login'])) {
    $user['last_login'] = null;
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (!$current_password || !$new_password || !$confirm_password) {
        $error = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hashed = $stmt->fetch()['password'] ?? '';
        if (!password_verify($current_password, $hashed)) {
            $error = 'Current password is incorrect.';
        } else {
            // Update password
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $update  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$newHash, $userId]);
            $message = 'Password updated successfully.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Aurionix Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Profile Settings</h1>
                <p>Manage your account details and update your password</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="profile-section" style="max-width: 600px;">
                <h3>Account Details</h3>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                <p><strong>Last Login:</strong> <?= $user['last_login'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($user['last_login']))) : 'N/A' ?></p>
                <p><strong>Member Since:</strong> <?= $user['created_at'] ? htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))) : 'N/A' ?></p>
            </div>
            <hr style="margin: 40px 0; border-color: rgba(255,255,255,0.1);">
            <div class="password-section" style="max-width: 600px;">
                <h3>Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password *</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password *</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔒 Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>