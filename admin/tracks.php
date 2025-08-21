<?php
/**
 * ENHANCED ADMIN TRACKS PAGE
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
        $play_type = $_POST['play_type'] ?? 'full';
        $preview_start_time = (int)$_POST['preview_start_time'] ?? 0;
        $preview_duration = (int)$_POST['preview_duration'] ?? 30;
        $lyrics = sanitizeInput($_POST['lyrics'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Generate slug
        $slug = generateSlug($title);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tracks (album_id, title, slug, duration, track_number, play_type, preview_start_time, preview_duration, lyrics, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$album_id, $title, $slug, $duration, $track_number, $play_type, $preview_start_time, $preview_duration, $lyrics, $featured]);
            $track_id = $pdo->lastInsertId();
            
            // Handle track-specific streaming links
            $platforms = ['spotify', 'apple-music', 'youtube', 'soundcloud', 'amazon-music', 'tidal'];
            foreach ($platforms as $platform) {
                $url = $_POST["streaming_{$platform}"] ?? '';
                if (!empty($url)) {
                    $stmt = $pdo->prepare("INSERT INTO track_streaming_links (track_id, album_id, platform, url, display_order) VALUES (?, ?, ?, ?, ?)");
                    $display_order = array_search($platform, $platforms) + 1;
                    $stmt->execute([$track_id, $album_id, $platform, $url, $display_order]);
                }
            }
            
            // Update album track count
            updateAlbumTrackCount($pdo, $album_id);
            
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
        $play_type = $_POST['play_type'] ?? 'full';
        $preview_start_time = (int)$_POST['preview_start_time'] ?? 0;
        $preview_duration = (int)$_POST['preview_duration'] ?? 30;
        $lyrics = sanitizeInput($_POST['lyrics'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Generate new slug if title changed
        $slug = generateSlug($title);
        
        try {
            $stmt = $pdo->prepare("UPDATE tracks SET album_id = ?, title = ?, slug = ?, duration = ?, track_number = ?, play_type = ?, preview_start_time = ?, preview_duration = ?, lyrics = ?, featured = ? WHERE id = ?");
            $stmt->execute([$album_id, $title, $slug, $duration, $track_number, $play_type, $preview_start_time, $preview_duration, $lyrics, $featured, $id]);
            
            // Update track-specific streaming links
            $stmt = $pdo->prepare("DELETE FROM track_streaming_links WHERE track_id = ?");
            $stmt->execute([$id]);
            
            $platforms = ['spotify', 'apple-music', 'youtube', 'soundcloud', 'amazon-music', 'tidal'];
            foreach ($platforms as $platform) {
                $url = $_POST["streaming_{$platform}"] ?? '';
                if (!empty($url)) {
                    $stmt = $pdo->prepare("INSERT INTO track_streaming_links (track_id, album_id, platform, url, display_order) VALUES (?, ?, ?, ?, ?)");
                    $display_order = array_search($platform, $platforms) + 1;
                    $stmt->execute([$id, $album_id, $platform, $url, $display_order]);
                }
            }
            
            // Update album track count
            updateAlbumTrackCount($pdo, $album_id);
            
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
        // Get album_id before deletion for updating count
        $stmt = $pdo->prepare("SELECT album_id FROM tracks WHERE id = ?");
        $stmt->execute([$id]);
        $track = $stmt->fetch();
        $track_album_id = $track['album_id'];
        
        $stmt = $pdo->prepare("DELETE FROM tracks WHERE id = ?");
        $stmt->execute([$id]);
        
        // Update album track count
        updateAlbumTrackCount($pdo, $track_album_id);
        
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
    
    $sql = "SELECT t.*, a.title as album_title, a.play_type as album_play_type,
                   (SELECT COUNT(*) FROM track_streaming_links tsl WHERE tsl.track_id = t.id) as streaming_links_count
            FROM tracks t 
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
$stmt = $pdo->query("SELECT id, title, play_type FROM albums ORDER BY title");
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
    } else {
        // Get existing streaming links
        $stmt = $pdo->prepare("SELECT platform, url FROM track_streaming_links WHERE track_id = ? ORDER BY display_order");
        $stmt->execute([$id]);
        $existing_links = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

// Get album info if adding to specific album
if ($action === 'add' && $album_id) {
    $stmt = $pdo->prepare("SELECT title, play_type FROM albums WHERE id = ?");
    $stmt->execute([$album_id]);
    $current_album = $stmt->fetch();
    
    // Get next track number
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(track_number), 0) + 1 as next_track_number FROM tracks WHERE album_id = ?");
    $stmt->execute([$album_id]);
    $next_track_number = $stmt->fetch()['next_track_number'];
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function updateAlbumTrackCount($pdo, $album_id) {
    $stmt = $pdo->prepare("UPDATE albums SET total_tracks = (SELECT COUNT(*) FROM tracks WHERE album_id = ?) WHERE id = ?");
    $stmt->execute([$album_id, $album_id]);
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
    <style>
        .track-card {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .track-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .track-info h3 {
            color: white;
            margin-bottom: 5px;
        }
        .track-meta {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
        }
        .track-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .track-actions {
            display: flex;
            gap: 10px;
        }
        .streaming-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .play-settings {
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .duration-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
    </style>
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
                            <h1>Tracks <?= $album_id ? "for " . htmlspecialchars($current_album['title'] ?? 'Album') : '' ?></h1>
                            <p>Manage individual tracks and their streaming links</p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=add<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-primary">
                                🎼 Add Track
                            </a>
                            <?php if ($album_id): ?>
                                <a href="albums.php?action=edit&id=<?= $album_id ?>" class="btn btn-secondary">
                                    🎵 Edit Album
                                </a>
                            <?php endif; ?>
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
                
                <!-- Tracks List -->
                <?php if (empty($tracks)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎼</div>
                        <h3>No tracks found</h3>
                        <p>Add tracks to your albums to organize your music</p>
                        <?php if ($album_id): ?>
                            <a href="?action=add&album_id=<?= $album_id ?>" class="btn btn-primary">Add Track to This Album</a>
                        <?php else: ?>
                            <a href="?action=add" class="btn btn-primary">Add Your First Track</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="tracks-container">
                        <?php foreach ($tracks as $track): ?>
                            <div class="track-card">
                                <div class="track-header">
                                    <div class="track-info">
                                        <h3><?= $track['track_number'] ?>. <?= htmlspecialchars($track['title']) ?></h3>
                                        <div class="track-meta">
                                            Album: <strong><?= htmlspecialchars($track['album_title']) ?></strong>
                                            <?php if ($track['duration']): ?>
                                                • Duration: <?= htmlspecialchars($track['duration']) ?>
                                            <?php endif; ?>
                                            • Created: <?= date('M j, Y', strtotime($track['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="track-badges">
                                        <?php if ($track['featured']): ?>
                                            <span class="badge badge-featured">Featured</span>
                                        <?php endif; ?>
                                        <span class="badge badge-<?= $track['play_type'] ?>"><?= ucfirst($track['play_type']) ?></span>
                                        <span class="badge"><?= $track['streaming_links_count'] ?> links</span>
                                    </div>
                                </div>
                                
                                <?php if ($track['lyrics']): ?>
                                    <div class="track-lyrics" style="margin-bottom: 15px;">
                                        <small style="color: rgba(255,255,255,0.6);">
                                            Lyrics: <?= htmlspecialchars(substr($track['lyrics'], 0, 100)) ?>...
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="track-actions">
                                    <a href="?action=edit&id=<?= $track['id'] ?>" class="btn btn-sm">Edit</a>
                                    <a href="/track.php?slug=<?= $track['slug'] ?>" class="btn btn-sm" target="_blank">View</a>
                                    <a href="?action=delete&id=<?= $track['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this track?')">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Track Form -->
                <div class="page-header">
                    <h1><?= $action === 'add' ? 'Add New Track' : 'Edit Track' ?></h1>
                    <a href="?action=list<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-secondary">← Back to Tracks</a>
                </div>
                
                <form method="POST" class="admin-form">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $edit_track['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-left">
                            <div class="form-group">
                                <label class="form-label">Album *</label>
                                <select name="album_id" class="form-select" required <?= $album_id ? 'onchange="updateAlbumDefaults(this)"' : '' ?>>
                                    <option value="">Select Album</option>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?= $album['id'] ?>" 
                                                data-play-type="<?= $album['play_type'] ?>"
                                                <?= ($album_id == $album['id'] || (isset($edit_track) && $edit_track['album_id'] == $album['id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($album['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Track Title *</label>
                                <input type="text" 
                                       name="title" 
                                       class="form-input" 
                                       value="<?= htmlspecialchars($edit_track['title'] ?? '') ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Track Number *</label>
                                <input type="number" 
                                       name="track_number" 
                                       class="form-input" 
                                       value="<?= $edit_track['track_number'] ?? $next_track_number ?? 1 ?>"
                                       min="1"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" 
                                       name="duration" 
                                       class="form-input" 
                                       placeholder="e.g., 3:45"
                                       value="<?= htmlspecialchars($edit_track['duration'] ?? '') ?>">
                                <small class="form-help">Format: MM:SS (e.g., 3:45)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="checkbox" 
                                           name="featured"
                                           <?= isset($edit_track) && $edit_track['featured'] ? 'checked' : '' ?>>
                                    <span class="checkbox-mark"></span>
                                    Feature this track
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-right">
                            <!-- Play Settings -->
                            <div class="play-settings">
                                <label class="form-label">Play Type</label>
                                <div class="radio-group">
                                    <div class="radio-item">
                                        <input type="radio" name="play_type" value="full" id="play_full" 
                                               <?= !isset($edit_track) || $edit_track['play_type'] === 'full' ? 'checked' : '' ?>>
                                        <label for="play_full">Full Track</label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" name="play_type" value="clip" id="play_clip"
                                               <?= isset($edit_track) && $edit_track['play_type'] === 'clip' ? 'checked' : '' ?>>
                                        <label for="play_clip">Clip Preview</label>
                                    </div>
                                </div>
                                
                                <div class="duration-inputs" id="clipSettings" style="<?= isset($edit_track) && $edit_track['play_type'] === 'clip' ? '' : 'display: none;' ?>">
                                    <div class="form-group">
                                        <label class="form-label">Start Time (seconds)</label>
                                        <input type="number" 
                                               name="preview_start_time" 
                                               class="form-input" 
                                               value="<?= $edit_track['preview_start_time'] ?? 0 ?>"
                                               min="0">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Duration (seconds)</label>
                                        <input type="number" 
                                               name="preview_duration" 
                                               class="form-input" 
                                               value="<?= $edit_track['preview_duration'] ?? 30 ?>"
                                               min="10" max="90">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Lyrics (Optional)</label>
                                <textarea name="lyrics" 
                                          class="form-textarea" 
                                          rows="6"
                                          placeholder="Enter song lyrics..."><?= htmlspecialchars($edit_track['lyrics'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Track-Specific Streaming Links -->
                    <div class="streaming-links-section">
                        <h3>🔗 Track-Specific Streaming Links</h3>
                        <p class="form-help">Add specific streaming links for this track (optional - album links will be used if these are empty)</p>
                        
                        <div class="streaming-links-grid">
                            <?php 
                            $platforms = [
                                'spotify' => ['icon' => '🎵', 'name' => 'Spotify'],
                                'apple-music' => ['icon' => '🍎', 'name' => 'Apple Music'],
                                'youtube' => ['icon' => '📺', 'name' => 'YouTube'],
                                'soundcloud' => ['icon' => '☁️', 'name' => 'SoundCloud'],
                                'amazon-music' => ['icon' => '📦', 'name' => 'Amazon Music'],
                                'tidal' => ['icon' => '🌊', 'name' => 'Tidal']
                            ];
                            
                            foreach ($platforms as $platform => $info): 
                                $existing_url = $existing_links[$platform] ?? '';
                            ?>
                                <div class="streaming-platform">
                                    <span class="platform-icon"><?= $info['icon'] ?></span>
                                    <div class="platform-inputs">
                                        <input type="url" 
                                               name="streaming_<?= $platform ?>" 
                                               placeholder="<?= $info['name'] ?> track URL"
                                               value="<?= htmlspecialchars($existing_url) ?>"
                                               class="form-input">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $action === 'add' ? '🎼 Add Track' : '💾 Update Track' ?>
                        </button>
                        <a href="?action=list<?= $album_id ? '&album_id=' . $album_id : '' ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Show/hide clip settings based on play type
        document.querySelectorAll('input[name="play_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const clipSettings = document.getElementById('clipSettings');
                if (this.value === 'clip') {
                    clipSettings.style.display = 'grid';
                } else {
                    clipSettings.style.display = 'none';
                }
            });
        });
        
        // Update album defaults when album is selected
        function updateAlbumDefaults(select) {
            const selectedOption = select.options[select.selectedIndex];
            const playType = selectedOption.dataset.playType;
            
            if (playType) {
                document.querySelector(`input[name="play_type"][value="${playType}"]`).checked = true;
                document.querySelector(`input[name="play_type"][value="${playType}"]`).dispatchEvent(new Event('change'));
            }
        }
        
        // Filter by album
        function filterByAlbum() {
            const albumId = document.getElementById('albumFilter').value;
            const currentUrl = new URL(window.location);
            if (albumId) {
                currentUrl.searchParams.set('album_id', albumId);
            } else {
                currentUrl.searchParams.delete('album_id');
            }
            window.location.href = currentUrl.toString();
        }
    </script>
</body>
</html>