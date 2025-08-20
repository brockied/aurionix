<?php
/**
 * USER MANAGEMENT PAGE
 *
 * This page provides administrators with the ability to view, add,
 * edit and delete user accounts.  It reuses the styling and layout of
 * the other admin pages to maintain a consistent experience.  Only
 * administrators can access this page via requireAdmin().  When
 * adding or editing users, passwords are securely hashed using
 * password_hash() and email addresses are validated to prevent
 * duplicates.  The currently logged‑in user cannot delete their own
 * account to avoid accidental lockout.
 */

require_once '../config.php';
requireAdmin();

// Determine the requested action (list, add, edit, delete)
$action  = $_GET['action'] ?? 'list';
$message = '';
$error   = '';

// Handle form submissions for adding and editing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $email    = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $role     = $_POST['role'] ?? 'user';

        // Basic validation
        if (!$email || !$password || !$confirm) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            try {
                // Check for existing user with same email
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'A user with this email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
                    $stmt->execute([$email, $hash, $role]);
                    $message = 'User added successfully!';
                    $action  = 'list';
                }
            } catch (Exception $e) {
                $error = 'Failed to add user: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $email   = sanitizeInput($_POST['email'] ?? '');
        $role    = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$id) {
            $error = 'Invalid user.';
        } elseif (!$email) {
            $error = 'Email cannot be empty.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check for duplicate email (excluding current user)
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    $error = 'Another user with this email already exists.';
                } else {
                    // Update user details
                    $updateSql = 'UPDATE users SET email = ?, role = ?';
                    $params    = [$email, $role];
                    // If password provided and matches confirmation, update it
                    if ($password || $confirm) {
                        if ($password !== $confirm) {
                            throw new Exception('Passwords do not match.');
                        }
                        $updateSql .= ', password = ?';
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $updateSql .= ' WHERE id = ?';
                    $params[] = $id;
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute($params);
                    $message = 'User updated successfully!';
                    $action  = 'list';
                }
            } catch (Exception $e) {
                $error = 'Failed to update user: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete action via GET
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id === (int)$_SESSION['user_id']) {
        $error  = 'You cannot delete your own account.';
        $action = 'list';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'User deleted successfully!';
            $action  = 'list';
        } catch (Exception $e) {
            $error  = 'Failed to delete user: ' . $e->getMessage();
            $action = 'list';
        }
    }
}

// When listing users, fetch all columns.  Selecting * avoids referencing
// columns that may not exist (such as last_login in older schemas).  After
// fetching, ensure the keys we rely on exist.
$users = [];
if ($action === 'list') {
    try {
        // Order by id to avoid relying on created_at for sorting, since
        // older schemas may not include that column.
        $stmt  = $pdo->query('SELECT * FROM users ORDER BY id DESC');
        $users = $stmt->fetchAll();
        foreach ($users as &$u) {
            if (!isset($u['last_login'])) {
                $u['last_login'] = null;
            }
            if (!isset($u['created_at'])) {
                $u['created_at'] = null;
            }
        }
    } catch (Exception $e) {
        $error = 'Failed to retrieve users: ' . $e->getMessage();
    }
}

// If editing and not submitting, fetch user details
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $edit_user = $stmt->fetch();
        if (!$edit_user) {
            $error  = 'User not found.';
            $action = 'list';
        }
    } else {
        $error  = 'Invalid user.';
        $action = 'list';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Aurionix Admin</title>
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
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>Users</h1>
                        <p>Manage administrator and user accounts</p>
                    </div>
                    <div class="header-actions">
                        <?php if ($action === 'list'): ?>
                            <a href="?action=add" class="btn btn-primary">👤 Add User</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No users found</h3>
                        <p>Create your first user account to get started</p>
                        <a href="?action=add" class="btn btn-primary">Add User</a>
                    </div>
                <?php else: ?>
                    <div class="users-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                                        <td><?= $u['last_login'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($u['last_login']))) : 'Never' ?></td>
                                        <td><?= $u['created_at'] ? htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))) : 'N/A' ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?action=edit&id=<?= $u['id'] ?>" class="btn-icon" title="Edit">✏️</a>
                                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                    <a href="?action=delete&id=<?= $u['id'] ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">🗑️</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <div class="form-container" style="max-width: 600px;">
                    <form method="POST" class="settings-form">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" required
                                   value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-input" required>
                                <option value="admin" <?= isset($edit_user) && $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="user"  <?= isset($edit_user) && $edit_user['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password <?= $action === 'edit' ? '(leave blank to keep unchanged)' : '*' ?></label>
                            <input type="password" name="password" class="form-input" <?= $action === 'add' ? 'required' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password <?= $action === 'edit' ? '(leave blank to keep unchanged)' : '*' ?></label>
                            <input type="password" name="confirm_password" class="form-input" <?= $action === 'add' ? 'required' : '' ?>>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 <?= $action === 'add' ? 'Create User' : 'Update User' ?></button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>