<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();

// Handle individual delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $albumId = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Get album and tracks info for file deletion
        $albumStmt = $pdo->prepare('SELECT cover FROM albums WHERE id = ?');
        $albumStmt->execute([$albumId]);
        $album = $albumStmt->fetch();
        
        $trackStmt = $pdo->prepare('SELECT audio_file FROM tracks WHERE album_id = ?');
        $trackStmt->execute([$albumId]);
        $tracks = $trackStmt->fetchAll();
        
        // Delete the album (this will CASCADE delete tracks, views, etc.)
        $deleteStmt = $pdo->prepare('DELETE FROM albums WHERE id = ?');
        $deleteStmt->execute([$albumId]);
        
        // Delete files after successful database deletion
        foreach ($tracks as $track) {
            if ($track['audio_file'] && file_exists(__DIR__ . '/../uploads/tracks/' . $track['audio_file'])) {
                unlink(__DIR__ . '/../uploads/tracks/' . $track['audio_file']);
            }
        }
        
        if ($album['cover'] && $album['cover'] !== 'default-cover.png' && file_exists(__DIR__ . '/../uploads/albums/' . $album['cover'])) {
            unlink(__DIR__ . '/../uploads/albums/' . $album['cover']);
        }
        
        $pdo->commit();
        $message = 'Album deleted successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error deleting album: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_albums'] ?? [];
    $message = '';
    $messageType = 'success';
    
    if (!empty($selected)) {
        try {
            $pdo->beginTransaction();
            
            switch ($action) {
                case 'feature':
                    $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE albums SET featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $message = count($selected) . ' album(s) featured successfully!';
                    break;
                    
                case 'unfeature':
                    $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE albums SET featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $message = count($selected) . ' album(s) unfeatured successfully!';
                    break;
                    
                case 'delete':
                    // Get all files to delete
                    $filesToDelete = [];
                    
                    foreach ($selected as $albumId) {
                        // Get album cover
                        $albumStmt = $pdo->prepare('SELECT cover FROM albums WHERE id = ?');
                        $albumStmt->execute([$albumId]);
                        $album = $albumStmt->fetch();
                        
                        if ($album['cover'] && $album['cover'] !== 'default-cover.png') {
                            $filesToDelete[] = __DIR__ . '/../uploads/albums/' . $album['cover'];
                        }
                        
                        // Get track files
                        $trackStmt = $pdo->prepare('SELECT audio_file FROM tracks WHERE album_id = ?');
                        $trackStmt->execute([$albumId]);
                        $tracks = $trackStmt->fetchAll();
                        
                        foreach ($tracks as $track) {
                            if ($track['audio_file']) {
                                $filesToDelete[] = __DIR__ . '/../uploads/tracks/' . $track['audio_file'];
                            }
                        }
                    }
                    
                    // Delete from database (CASCADE will handle related records)
                    $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM albums WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    
                    // Delete files after successful database deletion
                    foreach ($filesToDelete as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $message = count($selected) . ' album(s) deleted successfully!';
                    break;
                    
                default:
                    $message = 'Invalid action selected.';
                    $messageType = 'error';
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error performing bulk action: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = 'No albums selected.';
        $messageType = 'error';
    }
}

// Filter and search parameters
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch ($filter) {
    case 'featured':
        $whereConditions[] = "featured = 1";
        break;
    case 'not_featured':
        $whereConditions[] = "featured = 0";
        break;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Sort options
switch ($sort) {
    case 'newest':
        $orderBy = 'ORDER BY created_at DESC';
        break;
    case 'oldest':
        $orderBy = 'ORDER BY created_at ASC';
        break;
    case 'title_asc':
        $orderBy = 'ORDER BY title ASC';
        break;
    case 'title_desc':
        $orderBy = 'ORDER BY title DESC';
        break;
    default:
        $orderBy = 'ORDER BY created_at DESC';
}

// Get albums with stats
$query = "
    SELECT 
        a.*,
        COUNT(DISTINCT t.id) as track_count,
        COALESCE(SUM(v.view_count), 0) as total_views,
        COALESCE(COUNT(DISTINCT oi.id), 0) as total_sales,
        COALESCE(SUM(oi.price), 0) as total_revenue
    FROM albums a
    LEFT JOIN tracks t ON a.id = t.album_id
    LEFT JOIN views v ON v.album_id = a.id
    LEFT JOIN order_items oi ON oi.track_id = t.id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    $whereClause
    GROUP BY a.id
    $orderBy
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$albums = $stmt->fetchAll();

// Get summary stats
$totalAlbums = $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
$featuredAlbums = $pdo->query('SELECT COUNT(*) FROM albums WHERE featured = 1')->fetchColumn();
$totalTracks = $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Albums - Aurionix Admin</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
  <!-- Enhanced Admin Header -->
  <header class="admin-header">
    <div class="navbar__logo">
      <a href="/admin/dashboard.php">
        <span class="logo-text">Aurionix Admin</span>
      </a>
    </div>
    <nav class="navbar__links">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="albums.php" class="active">Albums</a></li>
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
    <div class="admin-user">
      <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
      <span><?= htmlspecialchars($_SESSION['username']); ?></span>
    </div>
  </header>

  <main class="admin-container">
    <div class="page-header">
      <div>
        <h1>Albums</h1>
        <p class="page-subtitle">Manage your music albums and collections</p>
      </div>
      <a href="album_form.php" class="btn btn--primary btn--lg">
        ✨ Add New Album
      </a>
    </div>

    <!-- Message Display -->
    <?php if (isset($message)): ?>
      <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <span><?= $messageType === 'success' ? '✅' : '❌'; ?></span>
        <span><?= htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
      <div class="stats-card">
        <div class="stats-card-icon primary">💿</div>
        <h3>Total Albums</h3>
        <div class="stats-number"><?= number_format($totalAlbums); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon warning">⭐</div>
        <h3>Featured</h3>
        <div class="stats-number"><?= number_format($featuredAlbums); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon secondary">🎵</div>
        <h3>Total Tracks</h3>
        <div class="stats-number"><?= number_format($totalTracks); ?></div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="admin-table-container" style="margin-bottom: 2rem;">
      <div class="admin-table-header">
        <h3 class="admin-table-title">Filter & Search</h3>
      </div>
      
      <div style="padding: 1.5rem;">
        <form method="get" action="albums.php" class="filter-form">
          <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: end;">
            <div class="form-field" style="margin: 0;">
              <label for="search">Search Albums</label>
              <input type="text" id="search" name="search" value="<?= htmlspecialchars($search); ?>" 
                     placeholder="Search by title or description..." />
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="filter">Filter by</label>
              <select id="filter" name="filter">
                <option value="all" <?= $filter === 'all' ? 'selected' : ''; ?>>All Albums</option>
                <option value="featured" <?= $filter === 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                <option value="not_featured" <?= $filter === 'not_featured' ? 'selected' : ''; ?>>Not Featured</option>
              </select>
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="sort">Sort by</label>
              <select id="sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
              </select>
            </div>
            
            <button type="submit" class="btn btn--primary">🔍 Search</button>
          </div>
        </form>
        
        <?php if ($search || $filter !== 'all'): ?>
          <div style="margin-top: 1rem;">
            <a href="albums.php" class="btn btn--outline btn--sm">❌ Clear Filters</a>
            <span style="color: var(--admin-text-muted); margin-left: 1rem;">
              Showing <?= count($albums); ?> of <?= $totalAlbums; ?> albums
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Albums Table -->
    <form method="post" action="albums.php" id="bulk-form">
      <div class="admin-table-container">
        <div class="admin-table-header">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <h3 class="admin-table-title">Your Albums</h3>
            <div class="bulk-actions" style="display: none;">
              <select name="bulk_action" class="bulk-action-select" style="padding: 0.5rem;">
                <option value="">Bulk Actions</option>
                <option value="feature">Feature Selected</option>
                <option value="unfeature">Unfeature Selected</option>
                <option value="delete" style="color: var(--admin-error);">Delete Selected</option>
              </select>
              <button type="submit" class="btn btn--secondary btn--sm" onclick="return confirmBulkAction();">
                Apply
              </button>
            </div>
          </div>
          
          <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="toggleSelectAll()" class="btn btn--outline btn--sm">
              Select All
            </button>
            <span class="selected-count" style="color: var(--admin-text-muted); font-size: 0.875rem; align-self: center;">
              0 selected
            </span>
          </div>
        </div>

        <?php if (empty($albums)): ?>
          <div style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💿</div>
            <h3>No Albums Found</h3>
            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
              <?php if ($search || $filter !== 'all'): ?>
                No albums match your search criteria. Try adjusting your filters.
              <?php else: ?>
                You haven't created any albums yet. Start by adding your first album!
              <?php endif; ?>
            </p>
            <?php if (!$search && $filter === 'all'): ?>
              <a href="album_form.php" class="btn btn--primary">✨ Create Your First Album</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th width="40">
                  <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes(this)" />
                </th>
                <th width="80">Cover</th>
                <th>Album Details</th>
                <th width="100">Tracks</th>
                <th width="100">Views</th>
                <th width="100">Sales</th>
                <th width="120">Revenue</th>
                <th width="100">Status</th>
                <th width="180">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($albums as $album): ?>
              <tr>
                <td>
                  <input type="checkbox" name="selected_albums[]" value="<?= $album['id']; ?>" 
                         class="album-checkbox" onchange="updateBulkActions()" />
                </td>
                <td>
                  <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" 
                       alt="<?= htmlspecialchars($album['title']); ?>" 
                       style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--admin-border-radius);" />
                </td>
                <td>
                  <div>
                    <h4 style="margin: 0 0 0.25rem; font-weight: 600;">
                      <?= htmlspecialchars($album['title']); ?>
                      <?php if ($album['featured']): ?>
                        <span class="status-badge featured">⭐ Featured</span>
                      <?php endif; ?>
                    </h4>
                    <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.875rem; line-height: 1.4;">
                      <?= htmlspecialchars(substr($album['description'], 0, 100)); ?>
                      <?= strlen($album['description']) > 100 ? '...' : ''; ?>
                    </p>
                    <small style="color: var(--admin-text-light); font-size: 0.75rem;">
                      Created: <?= date('M j, Y', strtotime($album['created_at'])); ?>
                    </small>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; font-size: 1.25rem;">
                      <?= number_format($album['track_count']); ?>
                    </div>
                    <small style="color: var(--admin-text-muted);">tracks</small>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; font-size: 1.25rem;">
                      <?= number_format($album['total_views']); ?>
                    </div>
                    <small style="color: var(--admin-text-muted);">views</small>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; font-size: 1.25rem;">
                      <?= number_format($album['total_sales']); ?>
                    </div>
                    <small style="color: var(--admin-text-muted);">sales</small>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; font-size: 1.25rem; color: var(--admin-success);">
                      <?= format_price((float)$album['total_revenue']); ?>
                    </div>
                    <small style="color: var(--admin-text-muted);">revenue</small>
                  </div>
                </td>
                <td>
                  <?php if ($album['featured']): ?>
                    <span class="status-badge featured">Featured</span>
                  <?php else: ?>
                    <span class="status-badge inactive">Regular</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                    <a href="album_form.php?id=<?= $album['id']; ?>" 
                       class="btn btn--outline btn--sm" title="Edit Album">
                      ✏️
                    </a>
                    <a href="/album.php?id=<?= $album['id']; ?>" target="_blank" 
                       class="btn btn--secondary btn--sm" title="View Album">
                      👁️
                    </a>
                    <a href="track_form.php?album_id=<?= $album['id']; ?>" 
                       class="btn btn--success btn--sm" title="Add Track">
                      ➕
                    </a>
                    <button type="button" onclick="deleteAlbum(<?= $album['id']; ?>, '<?= htmlspecialchars($album['title'], ENT_QUOTES); ?>')" 
                            class="btn btn--sm delete-btn" title="Delete Album">
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </form>

    <?php if (!empty($albums)): ?>
      <div style="text-align: center; margin-top: 2rem; color: var(--admin-text-muted);">
        Showing all <?= count($albums); ?> album(s)
      </div>
    <?php endif; ?>
  </main>

  <script>
    // Bulk actions functionality
    function toggleAllCheckboxes(selectAllCheckbox) {
      const checkboxes = document.querySelectorAll('.album-checkbox');
      checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
      });
      updateBulkActions();
    }

    function toggleSelectAll() {
      const selectAllCheckbox = document.getElementById('select-all');
      selectAllCheckbox.checked = !selectAllCheckbox.checked;
      toggleAllCheckboxes(selectAllCheckbox);
    }

    function updateBulkActions() {
      const checkboxes = document.querySelectorAll('.album-checkbox:checked');
      const bulkActions = document.querySelector('.bulk-actions');
      const selectedCount = document.querySelector('.selected-count');
      
      if (checkboxes.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = `${checkboxes.length} selected`;
      } else {
        bulkActions.style.display = 'none';
        selectedCount.textContent = '0 selected';
      }
      
      // Update select all checkbox state
      const allCheckboxes = document.querySelectorAll('.album-checkbox');
      const selectAllCheckbox = document.getElementById('select-all');
      
      if (checkboxes.length === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
      } else if (checkboxes.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
      } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
      }
    }

    function confirmBulkAction() {
      const action = document.querySelector('.bulk-action-select').value;
      const checkboxes = document.querySelectorAll('.album-checkbox:checked');
      
      if (!action) {
        alert('Please select an action.');
        return false;
      }
      
      if (checkboxes.length === 0) {
        alert('Please select at least one album.');
        return false;
      }
      
      let message = '';
      switch (action) {
        case 'feature':
          message = `Feature ${checkboxes.length} album(s)?`;
          break;
        case 'unfeature':
          message = `Remove featured status from ${checkboxes.length} album(s)?`;
          break;
        case 'delete':
          message = `⚠️ DELETE ${checkboxes.length} album(s)?\n\nThis will permanently delete:\n- Album(s) and all metadata\n- All tracks in the album(s)\n- All audio files\n- All view statistics\n- Order history will be preserved\n\nThis action CANNOT BE UNDONE!`;
          break;
      }
      
      return confirm(message);
    }

    function deleteAlbum(albumId, albumTitle) {
      const message = `⚠️ DELETE "${albumTitle}"?\n\nThis will permanently delete:\n- The album and all metadata\n- All tracks in this album\n- All audio files\n- All view statistics\n- Order history will be preserved\n\nThis action CANNOT BE UNDONE!`;
      
      if (confirm(message)) {
        // Show loading state
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '⏳';
        deleteBtn.disabled = true;
        
        window.location.href = `albums.php?delete=${albumId}`;
      }
    }

    // Auto-submit search form on filter/sort change
    document.getElementById('filter').addEventListener('change', function() {
      document.querySelector('.filter-form').submit();
    });

    document.getElementById('sort').addEventListener('change', function() {
      document.querySelector('.filter-form').submit();
    });

    // Search input debounce
    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        if (this.value.length >= 2 || this.value.length === 0) {
          document.querySelector('.filter-form').submit();
        }
      }, 500);
    });

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      updateBulkActions();
    });
  </script>

  <style>
    .filter-form {
      margin: 0;
    }
    
    .bulk-actions {
      align-items: center;
      gap: 0.5rem;
    }
    
    .bulk-action-select {
      background: var(--admin-bg-secondary);
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-border-radius);
      color: var(--admin-text);
      font-size: 0.875rem;
    }
    
    .admin-table td {
      vertical-align: middle;
    }
    
    .admin-table img {
      transition: transform 0.2s ease;
    }
    
    .admin-table img:hover {
      transform: scale(1.1);
    }
    
    #select-all:indeterminate {
      opacity: 0.5;
    }
    
    .btn--success {
      background: var(--admin-gradient-success);
      color: white;
    }
    
    .delete-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
    }
    
    .delete-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    @media (max-width: 768px) {
      .filter-form > div {
        grid-template-columns: 1fr;
      }
      
      .admin-table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
      }
      
      .bulk-actions {
        justify-content: space-between;
      }
    }
  </style>
</body>
</html>