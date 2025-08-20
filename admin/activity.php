<?php
/**
 * ACTIVITY LOG PAGE
 *
 * This page displays a simple log of streaming clicks recorded in the
 * `streaming_clicks` table.  It is linked from the user menu in the
 * admin header.  The logs are ordered by most recent and limited to
 * the latest 200 entries for performance.  If the analytics tables
 * haven't been created yet, the log will simply be empty.
 */

require_once '../config.php';
requireAdmin();

// Fetch recent activity from streaming_clicks
$activities = [];
try {
    $stmt = $pdo->prepare("SELECT sc.*, a.title AS album_title
                            FROM streaming_clicks sc
                            LEFT JOIN albums a ON sc.album_id = a.id
                            ORDER BY sc.clicked_at DESC
                            LIMIT 200");
    $stmt->execute();
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    // Table may not exist yet; ignore
    $activities = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Aurionix Admin</title>
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
                <h1>Activity Log</h1>
                <p>Recent streaming clicks recorded on your site</p>
            </div>

            <?php if (empty($activities)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No activity found</h3>
                    <p>No clicks have been recorded yet.  Once your fans start
                    clicking on streaming links, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="activity-log">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Album</th>
                                <th>Platform</th>
                                <th>Country</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i', strtotime($activity['clicked_at'])) ?></td>
                                    <td><?= htmlspecialchars($activity['album_title'] ?: 'Unknown') ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $activity['platform']))) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($activity['country_code'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>