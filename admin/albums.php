<?php
require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Create upload directories if they don't exist
$upload_dirs = ['../uploads', '../uploads/albums', '../uploads/tracks'];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $album_id = $_POST['album_id'] ?? null;
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $release_date = $_POST['release_date'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle cover image upload
        $cover_image = '';
        if ($album_id) {
            // Get existing cover
            $stmt = $pdo->prepare("SELECT cover_image FROM albums WHERE id = ?");
            $stmt->execute([$album_id]);
            $existing = $stmt->fetch();
            $cover_image = $existing['cover_image'] ?? '';
        }
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'album-' . time() . '.' . $ext;
                $upload_path = '../uploads/albums/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old cover if exists
                    if ($cover_image && file_exists('../' . ltrim($cover_image, '/'))) {
                        @unlink('../' . ltrim($cover_image, '/'));
                    }
                    $cover_image = 'uploads/albums/' . $filename;
                } else {
                    $error = 'Failed to upload cover image';
                }
            } else {
                $error = 'Invalid image format. Use JPG, PNG, or WebP';
            }
        }
        
        if (!$error) {
            try {
                if ($album_id) {
                    // Update album
                    $stmt = $pdo->prepare("UPDATE albums SET title = ?, description = ?, release_date = ?, cover_image = ?, featured = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $release_date, $cover_image, $featured, $album_id]);
                    $message = 'Album updated successfully!';
                } else {
                    // Create album
                    $stmt = $pdo->prepare("INSERT INTO albums (title, description, release_date, cover_image, featured) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $release_date, $cover_image, $featured]);
                    $album_id = $pdo->lastInsertId();
                    $message = 'Album created successfully!';
                }
                
                // Handle tracks
                if (isset($_POST['tracks']) && is_array($_POST['tracks'])) {
                    foreach ($_POST['tracks'] as $index => $track_data) {
                        if (empty($track_data['title'])) continue;
                        
                        $track_id = $track_data['id'] ?? null;
                        $track_title = sanitizeInput($track_data['title']);
                        $track_number = (int)$track_data['track_number'];
                        
                        // Handle audio file upload
                        $audio_file = '';
                        $file_size = 0;
                        
                        if ($track_id) {
                            // Get existing audio file
                            $stmt = $pdo->prepare("SELECT audio_file, file_size FROM tracks WHERE id = ?");
                            $stmt->execute([$track_id]);
                            $existing = $stmt->fetch();
                            $audio_file = $existing['audio_file'] ?? '';
                            $file_size = $existing['file_size'] ?? 0;
                        }
                        
                        if (isset($_FILES['track_audio'][$index]) && $_FILES['track_audio'][$index]['error'] === UPLOAD_ERR_OK) {
                            $audio_upload = $_FILES['track_audio'][$index];
                            $ext = strtolower(pathinfo($audio_upload['name'], PATHINFO_EXTENSION));
                            $allowed_audio = ['mp3', 'wav', 'flac', 'm4a'];
                            
                            if (in_array($ext, $allowed_audio) && $audio_upload['size'] <= 52428800) { // 50MB
                                $audio_filename = 'track-' . time() . '-' . $index . '.' . $ext;
                                $audio_path = '../uploads/tracks/' . $audio_filename;
                                
                                if (move_uploaded_file($audio_upload['tmp_name'], $audio_path)) {
                                    // Delete old audio file
                                    if ($audio_file && file_exists('../' . ltrim($audio_file, '/'))) {
                                        @unlink('../' . ltrim($audio_file, '/'));
                                    }
                                    $audio_file = 'uploads/tracks/' . $audio_filename;
                                    $file_size = $audio_upload['size'];
                                }
                            }
                        }
                        
                        if ($track_id) {
                            // Update track
                            $stmt = $pdo->prepare("UPDATE tracks SET title = ?, track_number = ?, audio_file = ?, file_size = ? WHERE id = ?");
                            $stmt->execute([$track_title, $track_number, $audio_file, $file_size, $track_id]);
                        } else {
                            // Create track
                            $stmt = $pdo->prepare("INSERT INTO tracks (album_id, title, track_number, audio_file, file_size) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$album_id, $track_title, $track_number, $audio_file, $file_size]);
                        }
                    }
                }
                
                $action = 'list';
                
            } catch (Exception $e) {
                $error = 'Failed to save album: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        $album = $stmt->fetch();
        
        if ($album) {
            // Delete tracks and files
            $stmt = $pdo->prepare("SELECT audio_file FROM tracks WHERE album_id = ?");
            $stmt->execute([$id]);
            $tracks = $stmt->fetchAll();
            
            foreach ($tracks as $track) {
                if ($track['audio_file'] && file_exists('../' . ltrim($track['audio_file'], '/'))) {
                    @unlink('../' . ltrim($track['audio_file'], '/'));
                }
            }
            
            // Delete album
            $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete cover image
            if ($album['cover_image'] && file_exists('../' . ltrim($album['cover_image'], '/'))) {
                @unlink('../' . ltrim($album['cover_image'], '/'));
            }
            
            $message = 'Album deleted successfully!';
        }
        $action = 'list';
    } catch (Exception $e) {
        $error = 'Failed to delete album: ' . $e->getMessage();
    }
}

// Get albums for listing
if ($action === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT a.*, COUNT(t.id) as total_tracks
            FROM albums a 
            LEFT JOIN tracks t ON a.id = t.album_id
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ");
        $albums = $stmt->fetchAll();
    } catch (Exception $e) {
        $albums = [];
        $error = 'Error loading albums: ' . $e->getMessage();
    }
}

// Get single album for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        $edit_album = $stmt->fetch();
        
        if (!$edit_album) {
            $error = 'Album not found!';
            $action = 'list';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM tracks WHERE album_id = ? ORDER BY track_number, title");
            $stmt->execute([$id]);
            $edit_tracks = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error = 'Error loading album: ' . $e->getMessage();
        $action = 'list';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .album-card {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .album-cover {
            width: 100%;
            height: 200px;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .album-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-cover {
            font-size: 3rem;
            color: rgba(255,255,255,0.3);
        }
        .album-info {
            padding: 20px;
        }
        .album-info h3 {
            margin: 0 0 10px 0;
            color: white;
        }
        .album-meta {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
            margin: 5px 0;
        }
        .album-actions {
            padding: 0 20px 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary {
            background: #e94560;
            color: white;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .form-container {
            background: rgba(255,255,255,0.05);
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: white;
            box-sizing: border-box;
        }
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        .file-upload {
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover {
            border-color: #e94560;
        }
        .file-upload input {
            display: none;
        }
        .current-cover img {
            max-width: 100%;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .track-item {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .track-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .page-header h1 {
            margin: 0;
            color: white;
        }
        @media (max-width: 768px) {
            .albums-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .track-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="dashboard-content">
            <?php if ($action === 'list'): ?>
                <div class="page-header">
                    <h1>Albums</h1>
                    <a href="?action=add" class="btn btn-primary">+ Add Album</a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (empty($albums)): ?>
                    <div class="form-container">
                        <div style="text-align: center; padding: 50px;">
                            <h3>No albums yet</h3>
                            <p>Create your first album to get started!</p>
                            <a href="?action=add" class="btn btn-primary">+ Add Album</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="albums-grid">
                        <?php foreach ($albums as $album): ?>
                            <div class="album-card">
                                <div class="album-cover">
                                    <?php if ($album['cover_image']): ?>
                                        <img src="<?= getImageUrl($album['cover_image']) ?>" alt="<?= htmlspecialchars($album['title']) ?>">
                                    <?php else: ?>
                                        <div class="no-cover">🎵</div>
                                    <?php endif; ?>
                                </div>
                                <div class="album-info">
                                    <h3><?= htmlspecialchars($album['title']) ?></h3>
                                    <div class="album-meta"><?= $album['total_tracks'] ?> tracks</div>
                                    <div class="album-meta"><?= date('M j, Y', strtotime($album['release_date'])) ?></div>
                                    <?php if ($album['featured']): ?>
                                        <div class="album-meta" style="color: #e94560;">⭐ Featured</div>
                                    <?php endif; ?>
                                </div>
                                <div class="album-actions">
                                    <a href="?action=edit&id=<?= $album['id'] ?>" class="btn btn-secondary">✏️ Edit</a>
                                    <a href="?action=delete&id=<?= $album['id'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Delete this album and all its tracks?')">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <div class="page-header">
                    <h1><?= $action === 'add' ? 'Add Album' : 'Edit Album' ?></h1>
                    <a href="?action=list" class="btn btn-secondary">← Back to Albums</a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="album_id" value="<?= $edit_album['id'] ?? '' ?>">
                        
                        <div class="form-grid">
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Album Title *</label>
                                    <input type="text" name="title" class="form-input" 
                                           value="<?= htmlspecialchars($edit_album['title'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Release Date *</label>
                                    <input type="date" name="release_date" class="form-input" 
                                           value="<?= $edit_album['release_date'] ?? date('Y-m-d') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-textarea" rows="4"><?= htmlspecialchars($edit_album['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="featured" <?= isset($edit_album) && $edit_album['featured'] ? 'checked' : '' ?>>
                                        ⭐ Feature this album on homepage
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Album Cover</label>
                                    
                                    <?php if (isset($edit_album) && $edit_album['cover_image']): ?>
                                        <div class="current-cover">
                                            <img src="<?= getImageUrl($edit_album['cover_image']) ?>" alt="Current cover">
                                            <p style="color: rgba(255,255,255,0.7); margin: 10px 0;">Current cover</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <label for="cover_image" class="file-upload">
                                        <div style="font-size: 2rem; margin-bottom: 10px;">🖼️</div>
                                        <h4><?= isset($edit_album) ? 'Change Cover' : 'Upload Cover' ?></h4>
                                        <p>JPG, PNG, or WebP<br>Recommended: 1000x1000px</p>
                                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Album Tracks</h3>
                        <div id="tracks-container">
                            <?php if (isset($edit_tracks) && count($edit_tracks) > 0): ?>
                                <?php foreach ($edit_tracks as $index => $track): ?>
                                    <div class="track-item">
                                        <h4>Track <?= $index + 1 ?></h4>
                                        <input type="hidden" name="tracks[<?= $index ?>][id]" value="<?= $track['id'] ?>">
                                        
                                        <div class="track-grid">
                                            <div class="form-group">
                                                <label class="form-label">Track Title *</label>
                                                <input type="text" name="tracks[<?= $index ?>][title]" class="form-input" 
                                                       value="<?= htmlspecialchars($track['title']) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Track Number *</label>
                                                <input type="number" name="tracks[<?= $index ?>][track_number]" class="form-input" 
                                                       value="<?= $track['track_number'] ?>" min="1" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Audio File</label>
                                            <?php if (!empty($track['audio_file'])): ?>
                                                <p style="color: #4CAF50;">✅ Current: <?= basename($track['audio_file']) ?> (<?= formatFileSize($track['file_size'] ?? 0) ?>)</p>
                                            <?php endif; ?>
                                            <input type="file" name="track_audio[<?= $index ?>]" class="form-input" accept="audio/*">
                                            <small>MP3, WAV, FLAC, or M4A - Max 50MB</small>
                                        </div>
                                        
                                        <button type="button" class="btn btn-danger" onclick="removeTrack(this)">Remove Track</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="addTrack()">+ Add Track</button>
                        
                        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <button type="submit" class="btn btn-primary">
                                <?= $action === 'add' ? '🎤 Create Album' : '💾 Save Changes' ?>
                            </button>
                            <a href="?action=list" class="btn btn-secondary" style="margin-left: 15px;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let trackCount = <?= isset($edit_tracks) ? count($edit_tracks) : 0 ?>;
        
        function addTrack() {
            const container = document.getElementById('tracks-container');
            const trackDiv = document.createElement('div');
            trackDiv.className = 'track-item';
            trackDiv.innerHTML = `
                <h4>Track ${trackCount + 1}</h4>
                <div class="track-grid">
                    <div class="form-group">
                        <label class="form-label">Track Title *</label>
                        <input type="text" name="tracks[${trackCount}][title]" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Track Number *</label>
                        <input type="number" name="tracks[${trackCount}][track_number]" class="form-input" 
                               value="${trackCount + 1}" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Audio File</label>
                    <input type="file" name="track_audio[${trackCount}]" class="form-input" accept="audio/*">
                    <small>MP3, WAV, FLAC, or M4A - Max 50MB</small>
                </div>
                <button type="button" class="btn btn-danger" onclick="removeTrack(this)">Remove Track</button>
            `;
            container.appendChild(trackDiv);
            trackCount++;
        }
        
        function removeTrack(button) {
            if (confirm('Remove this track?')) {
                button.parentElement.remove();
            }
        }
    </script>
</body>
</html>