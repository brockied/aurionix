<?php
/**
 * PREFERENCES PAGE
 *
 * The preferences page allows administrators to toggle various site
 * behaviours such as analytics collection and privacy mode.  Settings are
 * stored in the existing `settings` table using the updateSetting() helper
 * from config.php.  Feel free to extend this page with additional
 * preference options in the future.
 */

require_once '../config.php';
requireAdmin();

$message = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve values from form, cast to 0/1 where appropriate
        $analytics_enabled = isset($_POST['analytics_enabled']) ? '1' : '0';
        $privacy_mode      = isset($_POST['privacy_mode']) ? '1' : '0';
        $data_retention    = intval($_POST['data_retention_days'] ?? 365);

        updateSetting('analytics_enabled', $analytics_enabled);
        updateSetting('privacy_mode', $privacy_mode);
        updateSetting('data_retention_days', $data_retention);

        $message = 'Preferences saved successfully.';
    } catch (Exception $e) {
        $error = 'Failed to save preferences: ' . $e->getMessage();
    }
}

// Fetch current settings
$analytics_enabled = getSetting('analytics_enabled', '1');
$privacy_mode      = getSetting('privacy_mode', '0');
$data_retention    = getSetting('data_retention_days', '365');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferences - Aurionix Admin</title>
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
                <h1>Preferences</h1>
                <p>Manage analytics and privacy settings for your site</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="settings-form" style="max-width: 600px;">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="analytics_enabled" value="1" <?= $analytics_enabled ? 'checked' : '' ?>>
                        <span class="checkbox-mark"></span>
                        Enable analytics collection
                    </label>
                    <small class="form-help">When enabled, streaming clicks and page views will be recorded.</small>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="privacy_mode" value="1" <?= $privacy_mode ? 'checked' : '' ?>>
                        <span class="checkbox-mark"></span>
                        Enable privacy mode
                    </label>
                    <small class="form-help">When privacy mode is enabled, IP addresses are anonymised in the database.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Data retention (days)</label>
                    <input type="number" name="data_retention_days" min="1" class="form-input" value="<?= htmlspecialchars($data_retention) ?>">
                    <small class="form-help">Specify how long analytics data is kept before being purged.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Preferences</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>