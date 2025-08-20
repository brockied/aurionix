<?php
/**
 * ALBUM MANAGEMENT PAGE
 * Place this file as: admin/albums.php
 */

require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $release_date = $_POST['release_date'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle file upload
        $cover_image = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/albums/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = generateSlug($title) . '-' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                $cover_image = '/uploads/albums/' . $filename;
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO albums (title, description, release_date, cover_image, featured) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $release_date, $cover_image, $featured]);
            $message = 'Album added successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to add album: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $release_date = $_POST['release_date'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Get current album data
        $stmt = $pdo->prepare("SELECT cover_image FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        $current_album = $stmt->fetch();
        $cover_image = $current_album['cover_image'];
        
        // Handle new file upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/albums/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = generateSlug($title) . '-' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                // Delete old image
                if ($cover_image && file_exists('.' . $cover_image)) {
                    unlink('.' . $cover_image);
                }
                $cover_image = '/uploads/albums/' . $filename;
            }
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE albums SET title = ?, description = ?, release_date = ?, cover_image = ?, featured = ? WHERE id = ?");
            $stmt->execute([$title, $description, $release_date, $cover_image, $featured, $id]);
            $message = 'Album updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to update album: ' . $e->getMessage();
        }
    }
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Get album data for cleanup
        $stmt = $pdo->prepare("SELECT cover_image FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        $album = $stmt->fetch();
        
        // Delete album
        $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete cover image
        if ($album && $album['cover_image'] && file_exists('.' . $album['cover_image'])) {
            unlink('.' . $album['cover_image']);
        }
        
        $message = 'Album deleted successfully!';
        $action = 'list';
    } catch (Exception $e) {
        $error = 'Failed to delete album: ' . $e->getMessage();
    }
}

// Get albums for listing
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'all';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "title LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($filter === 'featured') {
        $where_conditions[] = "featured = 1";
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT * FROM albums $where_clause ORDER BY $sort $order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $albums = $stmt->fetchAll();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM albums $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_albums = $count_stmt->fetch()['total'];
}

// Get single album for editing
if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    $edit_album = $stmt->fetch();
    
    if (!$edit_album) {
        $error = 'Album not found!';
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums - Aurionix Admin</title>
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
                <!-- Albums List View -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1>Albums</h1>
                            <p>Manage your music albums and tracks</p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=add" class="btn btn-primary">
                                ➕ Add Album
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="content-filters">
                    <div class="filter-left">
                        <div class="search-box">
                            <input type="text" 
                                   placeholder="Search albums..." 
                                   value="<?= htmlspecialchars($search) ?>"
                                   id="searchInput">
                            <button class="search-btn">🔍</button>
                        </div>
                        
                        <select class="filter-select" id="filterSelect">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Albums</option>
                            <option value="featured" <?= $filter === 'featured' ? 'selected' : '' ?>>Featured</option>
                        </select>
                    </div>
                    
                    <div class="filter-right">
                        <select class="sort-select" id="sortSelect">
                            <option value="created_at-DESC" <?= $sort === 'created_at' && $order === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                            <option value="created_at-ASC" <?= $sort === 'created_at' && $order === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="title-ASC" <?= $sort === 'title' && $order === 'ASC' ? 'selected' : '' ?>>Title A-Z</option>
                            <option value="title-DESC" <?= $sort === 'title' && $order === 'DESC' ? 'selected' : '' ?>>Title Z-A</option>
                            <option value="release_date-DESC" <?= $sort === 'release_date' && $order === 'DESC' ? 'selected' : '' ?>>Release Date (New)</option>
                            <option value="release_date-ASC" <?= $sort === 'release_date' && $order === 'ASC' ? 'selected' : '' ?>>Release Date (Old)</option>
                        </select>
                        
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid">⊞</button>
                            <button class="view-btn" data-view="list">☰</button>
                        </div>
                    </div>
                </div>
                
                <!-- Albums Count -->
                <div class="content-summary">
                    <span class="total-count"><?= $total_albums ?> albums found</span>
                </div>
                
                <!-- Albums Grid/List -->
                <?php if (empty($albums)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎵</div>
                        <h3>No albums found</h3>
                        <p>Get started by adding your first album</p>
                        <a href="?action=add" class="btn btn-primary">Add Your First Album</a>
                    </div>
                <?php else: ?>
                    <div class="albums-container" id="albumsContainer">
                        <div class="albums-grid" id="albumsGrid">
                            <?php foreach ($albums as $album): ?>
                                <div class="album-card">
                                    <div class="album-cover">
                                        <img src="<?= $album['cover_image'] ?: '../assets/default-cover.jpg' ?>" 
                                             alt="<?= htmlspecialchars($album['title']) ?>">
                                        <div class="album-overlay">
                                            <div class="album-actions">
                                                <a href="?action=edit&id=<?= $album['id'] ?>" 
                                                   class="btn-icon" 
                                                   title="Edit Album">✏️</a>
                                                <a href="tracks.php?album_id=<?= $album['id'] ?>" 
                                                   class="btn-icon" 
                                                   title="Manage Tracks">🎼</a>
                                                <a href="streaming-links.php?album_id=<?= $album['id'] ?>" 
                                                   class="btn-icon" 
                                                   title="Streaming Links">🔗</a>
                                                <a href="?action=delete&id=<?= $album['id'] ?>" 
                                                   class="btn-icon btn-danger" 
                                                   title="Delete Album"
                                                   onclick="return confirm('Are you sure you want to delete this album?')">🗑️</a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="album-info">
                                        <div class="album-header">
                                            <h3><?= htmlspecialchars($album['title']) ?></h3>
                                            <?php if ($album['featured']): ?>
                                                <span class="badge badge-featured">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="album-description">
                                            <?= htmlspecialchars(substr($album['description'], 0, 100)) ?>
                                            <?= strlen($album['description']) > 100 ? '...' : '' ?>
                                        </p>
                                        
                                        <div class="album-meta">
                                            <span class="release-date">
                                                📅 <?= date('M j, Y', strtotime($album['release_date'])) ?>
                                            </span>
                                            <span class="created-date">
                                                🕒 <?= date('M j, Y', strtotime($album['created_at'])) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="album-stats">
                                            <?php
                                            $stmt = $pdo->prepare("SELECT COUNT(*) as track_count FROM tracks WHERE album_id = ?");
                                            $stmt->execute([$album['id']]);
                                            $track_count = $stmt->fetch()['track_count'];
                                            
                                            $stmt = $pdo->prepare("SELECT COUNT(*) as link_count FROM streaming_links WHERE album_id = ?");
                                            $stmt->execute([$album['id']]);
                                            $link_count = $stmt->fetch()['link_count'];
                                            ?>
                                            <span class="stat-item">🎼 <?= $track_count ?> tracks</span>
                                            <span class="stat-item">🔗 <?= $link_count ?> links</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- List View (Hidden by default) -->
                        <div class="albums-list" id="albumsList" style="display: none;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Cover</th>
                                        <th>Title</th>
                                        <th>Release Date</th>
                                        <th>Tracks</th>
                                        <th>Links</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($albums as $album): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= $album['cover_image'] ?: '../assets/default-cover.jpg' ?>" 
                                                     alt="<?= htmlspecialchars($album['title']) ?>"
                                                     class="table-cover">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($album['title']) ?></strong>
                                                <br>
                                                <small><?= htmlspecialchars(substr($album['description'], 0, 50)) ?>...</small>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($album['release_date'])) ?></td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tracks WHERE album_id = ?");
                                                $stmt->execute([$album['id']]);
                                                echo $stmt->fetch()['count'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM streaming_links WHERE album_id = ?");
                                                $stmt->execute([$album['id']]);
                                                echo $stmt->fetch()['count'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($album['featured']): ?>
                                                    <span class="badge badge-featured">Featured</span>
                                                <?php else: ?>
                                                    <span class="badge">Standard</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="?action=edit&id=<?= $album['id'] ?>" 
                                                       class="btn-icon" title="Edit">✏️</a>
                                                    <a href="tracks.php?album_id=<?= $album['id'] ?>" 
                                                       class="btn-icon" title="Tracks">🎼</a>
                                                    <a href="streaming-links.php?album_id=<?= $album['id'] ?>" 
                                                       class="btn-icon" title="Links">🔗</a>
                                                    <a href="?action=delete&id=<?= $album['id'] ?>" 
                                                       class="btn-icon btn-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure?')">🗑️</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Album Form -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1><?= $action === 'add' ? 'Add Album' : 'Edit Album' ?></h1>
                            <p><?= $action === 'add' ? 'Create a new album entry' : 'Update album information' ?></p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=list" class="btn btn-secondary">
                                ← Back to Albums
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data" class="album-form">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $edit_album['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-left">
                                <div class="form-group">
                                    <label class="form-label">Album Title *</label>
                                    <input type="text" 
                                           name="title" 
                                           class="form-input" 
                                           placeholder="Enter album title"
                                           value="<?= htmlspecialchars($edit_album['title'] ?? '') ?>"
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" 
                                              class="form-textarea" 
                                              placeholder="Enter album description"><?= htmlspecialchars($edit_album['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Release Date *</label>
                                    <input type="date" 
                                           name="release_date" 
                                           class="form-input"
                                           value="<?= $edit_album['release_date'] ?? date('Y-m-d') ?>"
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" 
                                               name="featured"
                                               <?= isset($edit_album) && $edit_album['featured'] ? 'checked' : '' ?>>
                                        <span class="checkbox-mark"></span>
                                        Feature this album on homepage
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-right">
                                <div class="form-group">
                                    <label class="form-label">Album Cover</label>
                                    
                                    <?php if (isset($edit_album) && $edit_album['cover_image']): ?>
                                        <div class="current-cover">
                                            <img src="<?= $edit_album['cover_image'] ?>" 
                                                 alt="Current cover" 
                                                 class="cover-preview">
                                            <p>Current cover image</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <label for="cover_image" class="file-upload">
                                        <div class="file-upload-icon">📁</div>
                                        <div class="file-upload-text">
                                            <h4><?= isset($edit_album) ? 'Change Cover Image' : 'Upload Cover Image' ?></h4>
                                            <p>Drag & drop or click to select (JPG, PNG, WebP)</p>
                                        </div>
                                    </label>
                                    <input type="file" 
                                           id="cover_image" 
                                           name="cover_image" 
                                           class="form-file"
                                           accept="image/*">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= $action === 'add' ? '✅ Create Album' : '💾 Update Album' ?>
                            </button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Additional styles for albums page */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            width: 100%;
        }
        
        .content-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .filter-left,
        .filter-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 300px;
            padding: 10px 40px 10px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
        }
        
        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
        }
        
        .filter-select,
        .sort-select {
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
        }
        
        .view-toggle {
            display: flex;
            gap: 5px;
        }
        
        .view-btn {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-btn.active,
        .view-btn:hover {
            background: #e94560;
            border-color: #e94560;
            color: white;
        }
        
        .content-summary {
            margin-bottom: 25px;
            color: rgba(255,255,255,0.6);
        }
        
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .album-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .album-card:hover {
            transform: translateY(-5px);
            border-color: #e94560;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        
        .album-cover {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
        }
        
        .album-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .album-card:hover .album-cover img {
            transform: scale(1.05);
        }
        
        .album-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .album-card:hover .album-overlay {
            opacity: 1;
        }
        
        .album-actions {
            display: flex;
            gap: 10px;
        }
        
        .album-info {
            padding: 20px;
        }
        
        .album-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .album-header h3 {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .album-description {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        
        .album-meta,
        .album-stats {
            display: flex;
            gap: 15px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 10px;
        }
        
        .table-cover {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
        }
        
        .form-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .album-form {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .current-cover {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .cover-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        
        .checkbox-mark {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .form-checkbox input:checked + .checkbox-mark {
            background: #e94560;
            border-color: #e94560;
        }
        
        .form-checkbox input:checked + .checkbox-mark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .content-filters {
                flex-direction: column;
                gap: 20px;
            }
            
            .filter-left,
            .filter-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-box input {
                width: 200px;
            }
            
            .albums-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            // Implement real-time search if needed
        });
        
        // Filter functionality
        document.getElementById('filterSelect').addEventListener('change', function() {
            updateURL();
        });
        
        // Sort functionality
        document.getElementById('sortSelect').addEventListener('change', function() {
            updateURL();
        });
        
        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                if (view === 'grid') {
                    document.getElementById('albumsGrid').style.display = 'grid';
                    document.getElementById('albumsList').style.display = 'none';
                } else {
                    document.getElementById('albumsGrid').style.display = 'none';
                    document.getElementById('albumsList').style.display = 'block';
                }
            });
        });
        
        // File upload preview
        document.getElementById('cover_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = document.querySelector('.upload-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'upload-preview';
                        preview.innerHTML = '<img class="preview-img"><p>Selected image</p>';
                        document.querySelector('.file-upload').parentNode.appendChild(preview);
                    }
                    preview.querySelector('.preview-img').src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        function updateURL() {
            const search = document.getElementById('searchInput').value;
            const filter = document.getElementById('filterSelect').value;
            const sort = document.getElementById('sortSelect').value;
            
            const params = new URLSearchParams();
            params.set('action', 'list');
            if (search) params.set('search', search);
            if (filter !== 'all') params.set('filter', filter);
            if (sort) {
                const [sortField, order] = sort.split('-');
                params.set('sort', sortField);
                params.set('order', order);
            }
            
            window.location.href = '?' + params.toString();
        }
    </script>
</body>
</html>