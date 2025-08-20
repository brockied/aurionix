<?php
/**
 * TRACK MANAGEMENT PAGE
 * Place this file as: admin/tracks.php
 */

require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$album_id = $_GET['album_id'] ?? '';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $album_id = $_POST['album_id'];
        $title = sanitizeInput($_POST['title']);
        $duration = $_POST['duration'] ?? '';
        $track_number = (int)$_POST['track_number'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tracks (album_id, title, duration, track_number) VALUES (?, ?, ?, ?)");
            $stmt->execute([$album_id, $title, $duration, $track_number]);
            $message = 'Track added successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to add track: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $album_id = $_POST['album_id'];
        $title = sanitizeInput($_POST['title']);
        $duration = $_POST['duration'] ?? '';
        $track_number = (int)$_POST['track_number'];
        
        try {
            $stmt = $pdo->prepare("UPDATE tracks SET album_id = ?, title = ?, duration = ?, track_number = ? WHERE id = ?");
            $stmt->execute([$album_id, $title, $duration, $track_number, $id]);
            $message = 'Track updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to update track: ' . $e->getMessage();
        }
    }
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tracks WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Track deleted successfully!';
        $action = 'list';
    } catch (Exception $e) {
        $error = 'Failed to delete track: ' . $e->getMessage();
    }
}

// Get tracks for listing
if ($action === 'list') {
    $where_conditions = [];
    $params = [];
    
    if ($album_id) {
        $where_conditions[] = "t.album_id = ?";
        $params[] = $album_id;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT t.*, a.title as album_title FROM tracks t 
            LEFT JOIN albums a ON t.album_id = a.id 
            $where_clause 
            ORDER BY a.title, t.track_number, t.title";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tracks = $stmt->fetchAll();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM tracks t $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_tracks = $count_stmt->fetch()['total'];
}

// Get albums for dropdowns
$stmt = $pdo->query("SELECT id, title FROM albums ORDER BY title");
$albums = $stmt->fetchAll();

// Get single track for editing
if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tracks WHERE id = ?");
    $stmt->execute([$id]);
    $edit_track = $stmt->fetch();
    
    if (!$edit_track) {
        $error = 'Track not found!';
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracks - Aurionix Admin</title>
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
            <?php if ($message): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <!-- Tracks List View -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1>Tracks</h1>
                            <p>Manage individual tracks for your albums</p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=add<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-primary">
                                🎼 Add Track
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="content-filters">
                    <div class="filter-left">
                        <select class="filter-select" id="albumFilter" onchange="filterByAlbum()">
                            <option value="">All Albums</option>
                            <?php foreach ($albums as $album): ?>
                                <option value="<?= $album['id'] ?>" <?= $album_id == $album['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($album['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-right">
                        <span class="total-count"><?= $total_tracks ?> tracks found</span>
                    </div>
                </div>
                
                <!-- Tracks Table -->
                <?php if (empty($tracks)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎼</div>
                        <h3>No tracks found</h3>
                        <p>Add tracks to your albums to organize your music better</p>
                        <?php if ($album_id): ?>
                            <a href="?action=add&album_id=<?= $album_id ?>" class="btn btn-primary">Add Track to This Album</a>
                        <?php else: ?>
                            <a href="?action=add" class="btn btn-primary">Add Your First Track</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="tracks-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Track Title</th>
                                    <th>Album</th>
                                    <th>Duration</th>
                                    <th>Track Number</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tracks as $index => $track): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($track['title']) ?></strong>
                                        </td>
                                        <td>
                                            <a href="?album_id=<?= $track['album_id'] ?>" class="album-link">
                                                <?= htmlspecialchars($track['album_title']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= $track['duration'] ? htmlspecialchars($track['duration']) : '—' ?>
                                        </td>
                                        <td>
                                            <span class="track-number"><?= $track['track_number'] ?: '—' ?></span>
                                        </td>
                                        <td>
                                            <span class="date-created">
                                                <?= date('M j, Y', strtotime($track['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?action=edit&id=<?= $track['id'] ?>" 
                                                   class="btn-icon" title="Edit Track">✏️</a>
                                                <a href="?action=delete&id=<?= $track['id'] ?>" 
                                                   class="btn-icon btn-danger" 
                                                   title="Delete Track"
                                                   onclick="return confirm('Are you sure you want to delete this track?')">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Track Form -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1><?= $action === 'add' ? 'Add Track' : 'Edit Track' ?></h1>
                            <p><?= $action === 'add' ? 'Add a new track to an album' : 'Update track information' ?></p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=list<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-secondary">
                                ← Back to Tracks
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="form-container">
                    <form method="POST" class="track-form">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $edit_track['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Album *</label>
                                <select name="album_id" class="form-select" required>
                                    <option value="">Select an album</option>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?= $album['id'] ?>" 
                                                <?php 
                                                $selected_album = '';
                                                if ($action === 'edit' && isset($edit_track)) {
                                                    $selected_album = $edit_track['album_id'];
                                                } elseif ($album_id) {
                                                    $selected_album = $album_id;
                                                }
                                                echo $selected_album == $album['id'] ? 'selected' : '';
                                                ?>>
                                            <?= htmlspecialchars($album['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-help">Choose which album this track belongs to</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Track Title *</label>
                                <input type="text" 
                                       name="title" 
                                       class="form-input" 
                                       placeholder="Enter track title"
                                       value="<?= htmlspecialchars($edit_track['title'] ?? '') ?>"
                                       required>
                                <small class="form-help">The name of the individual track</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" 
                                       name="duration" 
                                       class="form-input" 
                                       placeholder="3:45"
                                       value="<?= htmlspecialchars($edit_track['duration'] ?? '') ?>"
                                       pattern="[0-9]{1,2}:[0-9]{2}">
                                <small class="form-help">Track length in MM:SS format (e.g., 3:45)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Track Number</label>
                                <input type="number" 
                                       name="track_number" 
                                       class="form-input" 
                                       placeholder="1"
                                       value="<?= $edit_track['track_number'] ?? '' ?>"
                                       min="1" max="99">
                                <small class="form-help">Position of this track in the album</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= $action === 'add' ? '✅ Add Track' : '💾 Update Track' ?>
                            </button>
                            <a href="?action=list<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .album-link {
            color: #4a9eff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .album-link:hover {
            color: #66b3ff;
        }
        
        .track-number {
            background: rgba(233, 69, 96, 0.1);
            color: #e94560;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .date-created {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
        }
        
        .track-form {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-help {
            display: block;
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
    
    <script>
        function filterByAlbum() {
            const albumId = document.getElementById('albumFilter').value;
            const url = new URL(window.location);
            
            if (albumId) {
                url.searchParams.set('album_id', albumId);
            } else {
                url.searchParams.delete('album_id');
            }
            
            window.location.href = url.toString();
        }
        
        // Auto-calculate next track number
        document.addEventListener('DOMContentLoaded', function() {
            const albumSelect = document.querySelector('select[name="album_id"]');
            const trackNumberInput = document.querySelector('input[name="track_number"]');
            
            if (albumSelect && trackNumberInput && !trackNumberInput.value) {
                albumSelect.addEventListener('change', function() {
                    if (this.value) {
                        // In a real implementation, you'd fetch the highest track number via AJAX
                        // For now, we'll just suggest track number 1
                        if (!trackNumberInput.value) {
                            trackNumberInput.value = 1;
                        }
                    }
                });
            }
        });
        
        // Duration validation
        document.querySelector('input[name="duration"]').addEventListener('input', function() {
            const value = this.value;
            const pattern = /^[0-9]{1,2}:[0-9]{2}$/;
            
            if (value && !pattern.test(value)) {
                this.setCustomValidity('Please enter duration in MM:SS format (e.g., 3:45)');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>