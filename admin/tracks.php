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
    $trackId = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Get track info for file deletion
        $trackStmt = $pdo->prepare('SELECT audio_file, title FROM tracks WHERE id = ?');
        $trackStmt->execute([$trackId]);
        $track = $trackStmt->fetch();
        
        if (!$track) {
            throw new Exception('Track not found.');
        }
        
        // Delete the track (foreign keys will handle order_items and views)
        $deleteStmt = $pdo->prepare('DELETE FROM tracks WHERE id = ?');
        $deleteStmt->execute([$trackId]);
        
        // Delete audio file after successful database deletion
        if ($track['audio_file'] && file_exists(__DIR__ . '/../uploads/tracks/' . $track['audio_file'])) {
            unlink(__DIR__ . '/../uploads/tracks/' . $track['audio_file']);
        }
        
        $pdo->commit();
        $message = 'Track "' . htmlspecialchars($track['title']) . '" deleted successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error deleting track: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_tracks'] ?? [];
    $message = '';
    $messageType = 'success';
    
    if (!empty($selected)) {
        try {
            $pdo->beginTransaction();
            
            switch ($action) {
                case 'update_price':
                    $newPrice = (float)($_POST['bulk_price'] ?? 0);
                    if ($newPrice >= 0 && $newPrice <= 999.99) {
                        $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                        $stmt = $pdo->prepare("UPDATE tracks SET price = ? WHERE id IN ($placeholders)");
                        $stmt->execute(array_merge([$newPrice], $selected));
                        $message = count($selected) . ' track(s) price updated to ' . format_price($newPrice) . '!';
                    } else {
                        $message = 'Invalid price. Must be between 0 and 999.99.';
                        $messageType = 'error';
                    }
                    break;
                    
                case 'delete':
                    // Get all files to delete
                    $filesToDelete = [];
                    $trackTitles = [];
                    
                    foreach ($selected as $trackId) {
                        $trackStmt = $pdo->prepare('SELECT audio_file, title FROM tracks WHERE id = ?');
                        $trackStmt->execute([$trackId]);
                        $track = $trackStmt->fetch();
                        
                        if ($track) {
                            if ($track['audio_file']) {
                                $filesToDelete[] = __DIR__ . '/../uploads/tracks/' . $track['audio_file'];
                            }
                            $trackTitles[] = $track['title'];
                        }
                    }
                    
                    // Delete from database (foreign keys will handle related records)
                    $placeholders = str_repeat('?,', count($selected) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM tracks WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    
                    // Delete files after successful database deletion
                    foreach ($filesToDelete as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $message = count($selected) . ' track(s) deleted successfully!';
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
        $message = 'No tracks selected.';
        $messageType = 'error';
    }
}

// Filter and search parameters
$search = trim($_GET['search'] ?? '');
$albumFilter = $_GET['album'] ?? 'all';
$priceFilter = $_GET['price_range'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Get albums for filter dropdown
$albumsStmt = $pdo->query('SELECT id, title FROM albums ORDER BY title');
$availableAlbums = $albumsStmt->fetchAll();

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(t.title LIKE ? OR t.description LIKE ? OR a.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($albumFilter !== 'all' && is_numeric($albumFilter)) {
    $whereConditions[] = "t.album_id = ?";
    $params[] = $albumFilter;
}

switch ($priceFilter) {
    case 'free':
        $whereConditions[] = "t.price = 0";
        break;
    case 'under_5':
        $whereConditions[] = "t.price > 0 AND t.price < 5";
        break;
    case 'over_5':
        $whereConditions[] = "t.price >= 5";
        break;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Sort options
switch ($sort) {
    case 'newest':
        $orderBy = 'ORDER BY t.created_at DESC';
        break;
    case 'oldest':
        $orderBy = 'ORDER BY t.created_at ASC';
        break;
    case 'title_asc':
        $orderBy = 'ORDER BY t.title ASC';
        break;
    case 'title_desc':
        $orderBy = 'ORDER BY t.title DESC';
        break;
    case 'price_asc':
        $orderBy = 'ORDER BY t.price ASC';
        break;
    case 'price_desc':
        $orderBy = 'ORDER BY t.price DESC';
        break;
    case 'track_number':
        $orderBy = 'ORDER BY a.title ASC, t.track_number ASC';
        break;
    default:
        $orderBy = 'ORDER BY t.created_at DESC';
}

// Get tracks with stats
$query = "
    SELECT 
        t.*,
        a.title as album_title,
        a.cover as album_cover,
        COALESCE(SUM(v.view_count), 0) as total_plays,
        COALESCE(COUNT(DISTINCT oi.id), 0) as total_sales,
        COALESCE(SUM(oi.price), 0) as total_revenue
    FROM tracks t
    LEFT JOIN albums a ON t.album_id = a.id
    LEFT JOIN views v ON v.track_id = t.id
    LEFT JOIN order_items oi ON oi.track_id = t.id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    $whereClause
    GROUP BY t.id
    $orderBy
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tracks = $stmt->fetchAll();

// Get summary stats
$totalTracks = $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
$totalRevenue = $pdo->query('SELECT COALESCE(SUM(oi.price), 0) FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE o.payment_status = "paid"')->fetchColumn();
$totalPlays = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM views WHERE track_id IS NOT NULL')->fetchColumn();
$avgPrice = $pdo->query('SELECT AVG(price) FROM tracks WHERE price > 0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tracks - Aurionix Admin</title>
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
        <li><a href="albums.php">Albums</a></li>
        <li><a href="tracks.php" class="active">Tracks</a></li>
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
        <h1>Tracks</h1>
        <p class="page-subtitle">Manage your music tracks and audio files</p>
      </div>
      <div style="display: flex; gap: 1rem;">
        <a href="album_form.php" class="btn btn--outline btn--lg">
          💿 New Album
        </a>
        <a href="track_form.php" class="btn btn--primary btn--lg">
          🎵 Add New Track
        </a>
      </div>
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
        <div class="stats-card-icon primary">🎵</div>
        <h3>Total Tracks</h3>
        <div class="stats-number"><?= number_format($totalTracks); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon success">💰</div>
        <h3>Total Revenue</h3>
        <div class="stats-number"><?= format_price((float)$totalRevenue); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon warning">▶️</div>
        <h3>Total Plays</h3>
        <div class="stats-number"><?= number_format($totalPlays); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon secondary">💵</div>
        <h3>Average Price</h3>
        <div class="stats-number"><?= format_price((float)$avgPrice); ?></div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="admin-table-container" style="margin-bottom: 2rem;">
      <div class="admin-table-header">
        <h3 class="admin-table-title">Filter & Search</h3>
      </div>
      
      <div style="padding: 1.5rem;">
        <form method="get" action="tracks.php" class="filter-form">
          <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-field" style="margin: 0;">
              <label for="search">Search Tracks</label>
              <input type="text" id="search" name="search" value="<?= htmlspecialchars($search); ?>" 
                     placeholder="Search by track title, description, or album..." />
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="album">Album</label>
              <select id="album" name="album">
                <option value="all" <?= $albumFilter === 'all' ? 'selected' : ''; ?>>All Albums</option>
                <?php foreach ($availableAlbums as $album): ?>
                  <option value="<?= $album['id']; ?>" <?= $albumFilter == $album['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($album['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="price_range">Price Range</label>
              <select id="price_range" name="price_range">
                <option value="all" <?= $priceFilter === 'all' ? 'selected' : ''; ?>>All Prices</option>
                <option value="free" <?= $priceFilter === 'free' ? 'selected' : ''; ?>>Free</option>
                <option value="under_5" <?= $priceFilter === 'under_5' ? 'selected' : ''; ?>>Under <?= CURRENCY; ?>5</option>
                <option value="over_5" <?= $priceFilter === 'over_5' ? 'selected' : ''; ?>><?= CURRENCY; ?>5+</option>
              </select>
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="sort">Sort by</label>
              <select id="sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="track_number" <?= $sort === 'track_number' ? 'selected' : ''; ?>>By Album & Track #</option>
              </select>
            </div>
            
            <button type="submit" class="btn btn--primary">🔍 Search</button>
          </div>
        </form>
        
        <?php if ($search || $albumFilter !== 'all' || $priceFilter !== 'all'): ?>
          <div style="margin-top: 1rem;">
            <a href="tracks.php" class="btn btn--outline btn--sm">❌ Clear Filters</a>
            <span style="color: var(--admin-text-muted); margin-left: 1rem;">
              Showing <?= count($tracks); ?> of <?= $totalTracks; ?> tracks
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tracks Table -->
    <form method="post" action="tracks.php" id="bulk-form">
      <div class="admin-table-container">
        <div class="admin-table-header">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <h3 class="admin-table-title">Your Tracks</h3>
            <div class="bulk-actions" style="display: none;">
              <select name="bulk_action" class="bulk-action-select" style="padding: 0.5rem;" onchange="toggleBulkPriceInput(this.value)">
                <option value="">Bulk Actions</option>
                <option value="update_price">Update Price</option>
                <option value="delete" style="color: var(--admin-error);">Delete Selected</option>
              </select>
              <input type="number" name="bulk_price" step="0.01" min="0" max="999.99" placeholder="New price" 
                     class="bulk-price-input" style="display: none; padding: 0.5rem; width: 100px;" />
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

        <?php if (empty($tracks)): ?>
          <div style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎵</div>
            <h3>No Tracks Found</h3>
            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
              <?php if ($search || $albumFilter !== 'all' || $priceFilter !== 'all'): ?>
                No tracks match your search criteria. Try adjusting your filters.
              <?php elseif (empty($availableAlbums)): ?>
                You need to create an album first before adding tracks.
              <?php else: ?>
                You haven't added any tracks yet. Start by uploading your first track!
              <?php endif; ?>
            </p>
            <?php if (empty($availableAlbums)): ?>
              <a href="album_form.php" class="btn btn--primary">💿 Create Your First Album</a>
            <?php elseif (!$search && $albumFilter === 'all' && $priceFilter === 'all'): ?>
              <a href="track_form.php" class="btn btn--primary">🎵 Upload Your First Track</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th width="40">
                  <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes(this)" />
                </th>
                <th width="60">#</th>
                <th width="60">Cover</th>
                <th>Track Details</th>
                <th width="120">Album</th>
                <th width="80">Price</th>
                <th width="80">Plays</th>
                <th width="80">Sales</th>
                <th width="100">Revenue</th>
                <th width="200">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tracks as $track): ?>
              <tr>
                <td>
                  <input type="checkbox" name="selected_tracks[]" value="<?= $track['id']; ?>" 
                         class="track-checkbox" onchange="updateBulkActions()" />
                </td>
                <td>
                  <div style="text-align: center; font-weight: 600; color: var(--admin-primary);">
                    <?= $track['track_number'] ?: '-'; ?>
                  </div>
                </td>
                <td>
                  <img src="/uploads/albums/<?= htmlspecialchars($track['album_cover'] ?: 'default-cover.png'); ?>" 
                       alt="<?= htmlspecialchars($track['album_title']); ?>" 
                       style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--admin-border-radius);" />
                </td>
                <td>
                  <div>
                    <h4 style="margin: 0 0 0.25rem; font-weight: 600;">
                      <?= htmlspecialchars($track['title']); ?>
                      <?php if ($track['explicit'] ?? false): ?>
                        <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: var(--admin-error);">🅴</span>
                      <?php endif; ?>
                    </h4>
                    <?php if ($track['description']): ?>
                      <p style="margin: 0 0 0.25rem; color: var(--admin-text-muted); font-size: 0.875rem; line-height: 1.4;">
                        <?= htmlspecialchars(substr($track['description'], 0, 80)); ?>
                        <?= strlen($track['description']) > 80 ? '...' : ''; ?>
                      </p>
                    <?php endif; ?>
                    <div style="display: flex; gap: 1rem; font-size: 0.75rem; color: var(--admin-text-light);">
                      <?php if ($track['duration'] ?? ''): ?>
                        <span>⏱️ <?= htmlspecialchars($track['duration']); ?></span>
                      <?php endif; ?>
                      <?php if ($track['bpm'] ?? 0): ?>
                        <span>🎵 <?= $track['bpm']; ?> BPM</span>
                      <?php endif; ?>
                      <?php if ($track['key_signature'] ?? ''): ?>
                        <span>🎼 <?= htmlspecialchars($track['key_signature']); ?></span>
                      <?php endif; ?>
                    </div>
                    <small style="color: var(--admin-text-light); font-size: 0.75rem;">
                      Created: <?= date('M j, Y', strtotime($track['created_at'])); ?>
                    </small>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 500; font-size: 0.875rem;">
                      <?= htmlspecialchars($track['album_title']); ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; font-size: 1.1rem; color: <?= $track['price'] > 0 ? 'var(--admin-success)' : 'var(--admin-text-muted)'; ?>">
                      <?= $track['price'] > 0 ? format_price((float)$track['price']) : 'Free'; ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600;">
                      <?= number_format($track['total_plays']); ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600;">
                      <?= number_format($track['total_sales']); ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="text-align: center;">
                    <div style="font-weight: 600; color: var(--admin-success);">
                      <?= format_price((float)$track['total_revenue']); ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                    <?php if ($track['audio_file']): ?>
                      <button type="button" onclick="playTrack('<?= htmlspecialchars($track['audio_file']); ?>', '<?= htmlspecialchars($track['title'], ENT_QUOTES); ?>')" 
                              class="btn btn--primary btn--sm" title="Play Track">
                        ▶️
                      </button>
                    <?php endif; ?>
                    <a href="track_form.php?id=<?= $track['id']; ?>" 
                       class="btn btn--outline btn--sm" title="Edit Track">
                      ✏️
                    </a>
                    <a href="/album.php?id=<?= $track['album_id']; ?>#track-<?= $track['id']; ?>" target="_blank" 
                       class="btn btn--secondary btn--sm" title="View Track">
                      👁️
                    </a>
                    <?php if ($track['audio_file']): ?>
                      <button type="button" onclick="downloadTrack('<?= htmlspecialchars($track['audio_file']); ?>', '<?= htmlspecialchars($track['title'], ENT_QUOTES); ?>')" 
                              class="btn btn--success btn--sm" title="Download">
                        💾
                      </button>
                    <?php endif; ?>
                    <button type="button" onclick="deleteTrack(<?= $track['id']; ?>, '<?= htmlspecialchars($track['title'], ENT_QUOTES); ?>')" 
                            class="btn btn--sm delete-btn" title="Delete Track">
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

    <?php if (!empty($tracks)): ?>
      <div style="text-align: center; margin-top: 2rem; color: var(--admin-text-muted);">
        Showing all <?= count($tracks); ?> track(s)
      </div>
    <?php endif; ?>
  </main>

  <!-- Audio Player Modal -->
  <div id="audio-modal" class="audio-modal" style="display: none;">
    <div class="audio-modal-content">
      <div class="audio-modal-header">
        <h3 id="audio-modal-title">Track Preview</h3>
        <button onclick="closeAudioModal()" class="btn btn--outline btn--sm">✕</button>
      </div>
      <audio id="audio-player" controls style="width: 100%; margin: 1rem 0;"></audio>
      <div style="text-align: center;">
        <button onclick="closeAudioModal()" class="btn btn--outline">Close</button>
      </div>
    </div>
  </div>

  <script>
    // Audio player functionality
    function playTrack(audioFile, trackTitle) {
      const modal = document.getElementById('audio-modal');
      const player = document.getElementById('audio-player');
      const title = document.getElementById('audio-modal-title');
      
      title.textContent = `Playing: ${trackTitle}`;
      player.src = `/uploads/tracks/${audioFile}`;
      modal.style.display = 'flex';
      player.play().catch(console.error);
    }

    function closeAudioModal() {
      const modal = document.getElementById('audio-modal');
      const player = document.getElementById('audio-player');
      
      player.pause();
      player.src = '';
      modal.style.display = 'none';
    }

    function downloadTrack(audioFile, trackTitle) {
      const link = document.createElement('a');
      link.href = `/uploads/tracks/${audioFile}`;
      link.download = `${trackTitle}.${audioFile.split('.').pop()}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    // Delete functionality
    function deleteTrack(trackId, trackTitle) {
      const message = `⚠️ DELETE "${trackTitle}"?\n\nThis will permanently delete:\n- The track and all metadata\n- The audio file\n- All view statistics\n- Order history will be preserved\n\nThis action CANNOT BE UNDONE!`;
      
      if (confirm(message)) {
        // Show loading state
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '⏳';
        deleteBtn.disabled = true;
        
        window.location.href = `tracks.php?delete=${trackId}`;
      }
    }

    // Bulk actions functionality
    function toggleAllCheckboxes(selectAllCheckbox) {
      const checkboxes = document.querySelectorAll('.track-checkbox');
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
      const checkboxes = document.querySelectorAll('.track-checkbox:checked');
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
      const allCheckboxes = document.querySelectorAll('.track-checkbox');
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

    function toggleBulkPriceInput(action) {
      const priceInput = document.querySelector('.bulk-price-input');
      if (action === 'update_price') {
        priceInput.style.display = 'block';
        priceInput.required = true;
      } else {
        priceInput.style.display = 'none';
        priceInput.required = false;
      }
    }

    function confirmBulkAction() {
      const action = document.querySelector('.bulk-action-select').value;
      const checkboxes = document.querySelectorAll('.track-checkbox:checked');
      
      if (!action) {
        alert('Please select an action.');
        return false;
      }
      
      if (checkboxes.length === 0) {
        alert('Please select at least one track.');
        return false;
      }
      
      let message = '';
      switch (action) {
        case 'update_price':
          const newPrice = document.querySelector('.bulk-price-input').value;
          if (!newPrice || isNaN(newPrice) || parseFloat(newPrice) < 0) {
            alert('Please enter a valid price.');
            return false;
          }
          message = `Update price to ${newPrice} for ${checkboxes.length} track(s)?`;
          break;
        case 'delete':
          message = `⚠️ DELETE ${checkboxes.length} track(s)?\n\nThis will permanently delete:\n- All selected tracks and metadata\n- All audio files\n- All view statistics\n- Order history will be preserved\n\nThis action CANNOT BE UNDONE!`;
          break;
      }
      
      return confirm(message);
    }

    // Auto-submit search form on filter/sort change
    ['album', 'price_range', 'sort'].forEach(id => {
      document.getElementById(id).addEventListener('change', function() {
        document.querySelector('.filter-form').submit();
      });
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

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
      const modal = document.getElementById('audio-modal');
      if (e.target === modal) {
        closeAudioModal();
      }
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
    
    .bulk-action-select,
    .bulk-price-input {
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

    /* Audio Modal */
    .audio-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
    }

    .audio-modal-content {
      background: var(--admin-bg-card);
      border-radius: var(--admin-border-radius-lg);
      padding: 2rem;
      width: 90%;
      max-width: 500px;
      border: 1px solid var(--admin-border);
    }

    .audio-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .audio-modal-header h3 {
      margin: 0;
      color: var(--admin-text);
    }
    
    @media (max-width: 1200px) {
      .filter-form > div {
        grid-template-columns: 1fr 1fr auto;
      }
      
      .filter-form .form-field:nth-child(3),
      .filter-form .form-field:nth-child(4) {
        grid-column: span 2;
      }
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

      .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
      }

      .page-header > div:last-child {
        display: flex;
        gap: 0.5rem;
      }
    }
  </style>
</body>
</html>