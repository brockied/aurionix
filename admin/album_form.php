<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo    = get_db();
$albumId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Initial values
$album   = [
    'id'          => 0,
    'title'       => '',
    'description' => '',
    'cover'       => 'default-cover.png',
    'featured'    => 0,
    'spotify_url' => '',
    'apple_url'   => '',
    'other_url'   => '',
    'release_date' => date('Y-m-d'),
    'genre'       => '',
    'price'       => 0.00
];

if ($albumId) {
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
    $stmt->execute([$albumId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $album = $existing;
        if (!$album['release_date']) {
            $album['release_date'] = date('Y-m-d', strtotime($album['created_at']));
        }
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $spotify_url = trim($_POST['spotify_url'] ?? '');
    $apple_url   = trim($_POST['apple_url'] ?? '');
    $other_url   = trim($_POST['other_url'] ?? '');
    $release_date = trim($_POST['release_date'] ?? '');
    $genre       = trim($_POST['genre'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $coverName   = $album['cover'];

    // Validation
    if (!$title) {
        $errors[] = 'Album title is required.';
    }
    if (!$description) {
        $errors[] = 'Album description is required.';
    }
    if (!$release_date) {
        $errors[] = 'Release date is required.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $release_date)) {
        $errors[] = 'Invalid release date format.';
    }

    // Handle cover upload
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['cover']['tmp_name'];
        $originalName = $_FILES['cover']['name'];
        $fileSize = $_FILES['cover']['size'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedTypes)) {
            $errors[] = 'Invalid cover image format. Allowed: JPG, PNG, WebP';
        }
        
        // Validate file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = 'Cover image too large. Maximum size: 5MB';
        }
        
        if (empty($errors)) {
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/albums/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $newName = uniqid('album_cover_') . '.' . $ext;
            $target  = $uploadDir . $newName;
            
            // Move uploaded file
            if (move_uploaded_file($tmpName, $target)) {
                // Delete old cover if not default
                if ($album['cover'] && $album['cover'] !== 'default-cover.png' && file_exists($uploadDir . $album['cover'])) {
                    unlink($uploadDir . $album['cover']);
                }
                $coverName = $newName;
            } else {
                $errors[] = 'Failed to upload cover image.';
            }
        }
    }

    if (!$errors) {
        try {
            if ($albumId) {
                // Update existing album
                $stmt = $pdo->prepare('
                    UPDATE albums 
                    SET title=?, description=?, cover=?, featured=?, spotify_url=?, apple_url=?, other_url=?, release_date=?, genre=?, price=?
                    WHERE id=?
                ');
                $stmt->execute([$title, $description, $coverName, $featured, $spotify_url, $apple_url, $other_url, $release_date, $genre, $price, $albumId]);
                $success = 'Album updated successfully!';
            } else {
                // Create new album
                $stmt = $pdo->prepare('
                    INSERT INTO albums (title, description, cover, featured, spotify_url, apple_url, other_url, release_date, genre, price) 
                    VALUES (?,?,?,?,?,?,?,?,?,?)
                ');
                $stmt->execute([$title, $description, $coverName, $featured, $spotify_url, $apple_url, $other_url, $release_date, $genre, $price]);
                $albumId = $pdo->lastInsertId();
                $success = 'Album created successfully!';
            }
            
            // Update album array with new values
            $album = array_merge($album, [
                'title'       => $title,
                'description' => $description,
                'cover'       => $coverName,
                'featured'    => $featured,
                'spotify_url' => $spotify_url,
                'apple_url'   => $apple_url,
                'other_url'   => $other_url,
                'release_date' => $release_date,
                'genre'       => $genre,
                'price'       => $price
            ]);
            
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Preserve entered values on error
    if ($errors) {
        $album = array_merge($album, [
            'title'       => $title,
            'description' => $description,
            'cover'       => $coverName,
            'featured'    => $featured,
            'spotify_url' => $spotify_url,
            'apple_url'   => $apple_url,
            'other_url'   => $other_url,
            'release_date' => $release_date,
            'genre'       => $genre,
            'price'       => $price
        ]);
    }
}

// Check if album has been created but needs additional columns
if ($albumId) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM albums LIKE 'genre'");
        if (!$stmt->fetch()) {
            // Add missing columns
            $pdo->exec("ALTER TABLE albums ADD COLUMN genre VARCHAR(100) DEFAULT ''");
            $pdo->exec("ALTER TABLE albums ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
            $pdo->exec("ALTER TABLE albums ADD COLUMN release_date DATE");
        }
    } catch (Exception $e) {
        // Column might already exist
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $albumId ? 'Edit Album' : 'Add Album'; ?> - Aurionix Admin</title>
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
      <h1><?= $albumId ? 'Edit Album' : 'Add Album'; ?></h1>
      <p class="page-subtitle"><?= $albumId ? 'Update your album information and settings' : 'Create a new album for your music collection'; ?></p>
    </div>

    <!-- Success Message -->
    <?php if ($success): ?>
      <div class="alert alert-success">
        <span>✅</span>
        <span><?= htmlspecialchars($success); ?></span>
      </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if ($errors): ?>
      <div class="alert alert-error">
        <span>❌</span>
        <div>
          <strong>Please fix the following errors:</strong>
          <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="album_form.php<?= $albumId ? '?id=' . $albumId : ''; ?>" class="admin-form">
      <!-- Basic Information Section -->
      <div class="form-section">
        <h3 class="form-section-title">📀 Basic Information</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="title">Album Title *</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($album['title']); ?>" required 
                   placeholder="Enter album title" maxlength="255" />
          </div>

          <div class="form-field">
            <label for="genre">Genre</label>
            <select id="genre" name="genre">
              <option value="">Select Genre</option>
              <option value="Hip Hop" <?= $album['genre'] === 'Hip Hop' ? 'selected' : ''; ?>>Hip Hop</option>
              <option value="R&B" <?= $album['genre'] === 'R&B' ? 'selected' : ''; ?>>R&B</option>
              <option value="Pop" <?= $album['genre'] === 'Pop' ? 'selected' : ''; ?>>Pop</option>
              <option value="Electronic" <?= $album['genre'] === 'Electronic' ? 'selected' : ''; ?>>Electronic</option>
              <option value="Rock" <?= $album['genre'] === 'Rock' ? 'selected' : ''; ?>>Rock</option>
              <option value="Jazz" <?= $album['genre'] === 'Jazz' ? 'selected' : ''; ?>>Jazz</option>
              <option value="Classical" <?= $album['genre'] === 'Classical' ? 'selected' : ''; ?>>Classical</option>
              <option value="Alternative" <?= $album['genre'] === 'Alternative' ? 'selected' : ''; ?>>Alternative</option>
              <option value="Indie" <?= $album['genre'] === 'Indie' ? 'selected' : ''; ?>>Indie</option>
              <option value="Other" <?= $album['genre'] === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <div class="form-field">
            <label for="release_date">Release Date *</label>
            <input type="date" id="release_date" name="release_date" 
                   value="<?= htmlspecialchars($album['release_date']); ?>" required />
          </div>

          <div class="form-field">
            <label for="price">Album Price (<?= CURRENCY; ?>)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" max="999.99"
                   value="<?= htmlspecialchars($album['price']); ?>" placeholder="0.00" />
          </div>
        </div>

        <div class="form-field">
          <label for="description">Description *</label>
          <textarea id="description" name="description" required 
                    placeholder="Describe your album, its inspiration, and what listeners can expect..."
                    maxlength="2000"><?= htmlspecialchars($album['description']); ?></textarea>
          <small style="color: var(--admin-text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
            Characters remaining: <span id="char-count"><?= 2000 - strlen($album['description']); ?></span>/2000
          </small>
        </div>
      </div>

      <!-- Cover Image Section -->
      <div class="form-section">
        <h3 class="form-section-title">🖼️ Cover Image</h3>
        
        <?php if ($album['cover'] && $album['cover'] !== 'default-cover.png'): ?>
          <div class="image-preview">
            <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" 
                 alt="Current album cover" id="current-cover" />
            <p style="color: var(--admin-text-muted); font-size: 0.875rem; margin-top: 0.5rem;">Current cover image</p>
          </div>
        <?php endif; ?>

        <div class="file-upload" id="cover-upload">
          <input type="file" name="cover" accept="image/*" id="cover-input" />
          <div class="file-upload-content">
            <div class="file-upload-icon">📸</div>
            <div class="file-upload-text">
              <strong>Click to upload album cover</strong> or drag and drop
            </div>
            <div class="file-upload-subtext">
              Supports: JPG, PNG, WebP (Max: 5MB, Recommended: 1000x1000px)
            </div>
          </div>
        </div>

        <div id="upload-preview" style="display: none;">
          <img id="preview-image" style="max-width: 200px; border-radius: var(--admin-border-radius); margin-top: 1rem;" />
        </div>
      </div>

      <!-- Settings Section -->
      <div class="form-section">
        <h3 class="form-section-title">⚙️ Album Settings</h3>
        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
          <label class="toggle">
            <input type="checkbox" name="featured" value="1" <?= $album['featured'] ? 'checked' : ''; ?> />
            <span class="toggle-slider"></span>
          </label>
          <div>
            <strong>Featured Album</strong>
            <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.875rem;">
              Display this album prominently on the homepage
            </p>
          </div>
        </div>
      </div>

      <!-- Streaming Links Section -->
      <div class="form-section">
        <h3 class="form-section-title">🔗 Streaming Platform Links</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="spotify_url">
              <span style="color: #1DB954;">🎵</span> Spotify URL
            </label>
            <input type="url" id="spotify_url" name="spotify_url" 
                   value="<?= htmlspecialchars($album['spotify_url']); ?>"
                   placeholder="https://open.spotify.com/album/..." />
          </div>

          <div class="form-field">
            <label for="apple_url">
              <span style="color: #FA57C1;">🎵</span> Apple Music URL
            </label>
            <input type="url" id="apple_url" name="apple_url" 
                   value="<?= htmlspecialchars($album['apple_url']); ?>"
                   placeholder="https://music.apple.com/album/..." />
          </div>

          <div class="form-field" style="grid-column: 1 / -1;">
            <label for="other_url">🌐 Other Platform URL</label>
            <input type="url" id="other_url" name="other_url" 
                   value="<?= htmlspecialchars($album['other_url']); ?>"
                   placeholder="https://your-other-platform.com/album/..." />
            <small style="color: var(--admin-text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
              YouTube, SoundCloud, Bandcamp, or any other streaming platform
            </small>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="btn-group">
        <button type="submit" class="btn btn--primary btn--lg">
          <?= $albumId ? '💾 Update Album' : '✨ Create Album'; ?>
        </button>
        <a href="albums.php" class="btn btn--outline btn--lg">
          ❌ Cancel
        </a>
        <?php if ($albumId): ?>
          <a href="/album.php?id=<?= $albumId; ?>" target="_blank" class="btn btn--secondary btn--lg">
            👁️ Preview Album
          </a>
        <?php endif; ?>
      </div>
    </form>
  </main>

  <script>
    // Character counter for description
    const descTextarea = document.getElementById('description');
    const charCount = document.getElementById('char-count');
    
    descTextarea.addEventListener('input', function() {
      const remaining = 2000 - this.value.length;
      charCount.textContent = remaining;
      charCount.style.color = remaining < 100 ? 'var(--admin-error)' : 'var(--admin-text-muted)';
    });

    // Image preview functionality
    const coverInput = document.getElementById('cover-input');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const coverUpload = document.getElementById('cover-upload');

    coverInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        // Validate file size
        if (file.size > 5 * 1024 * 1024) {
          alert('File size too large. Maximum size is 5MB.');
          this.value = '';
          return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
          alert('Invalid file type. Please use JPG, PNG, or WebP.');
          this.value = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
          previewImage.src = e.target.result;
          uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      coverUpload.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
      coverUpload.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      coverUpload.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
      coverUpload.style.borderColor = 'var(--admin-primary)';
      coverUpload.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
    }

    function unhighlight(e) {
      coverUpload.style.borderColor = 'var(--admin-border)';
      coverUpload.style.backgroundColor = 'var(--admin-bg-secondary)';
    }

    coverUpload.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      
      if (files.length > 0) {
        coverInput.files = files;
        const event = new Event('change', { bubbles: true });
        coverInput.dispatchEvent(event);
      }
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const title = document.getElementById('title').value.trim();
      const description = document.getElementById('description').value.trim();
      const releaseDate = document.getElementById('release_date').value;

      if (!title) {
        alert('Please enter an album title.');
        e.preventDefault();
        return;
      }

      if (!description) {
        alert('Please enter an album description.');
        e.preventDefault();
        return;
      }

      if (!releaseDate) {
        alert('Please select a release date.');
        e.preventDefault();
        return;
      }
    });

    // Auto-save draft functionality (optional)
    let saveTimeout;
    const formInputs = document.querySelectorAll('input, textarea, select');
    
    formInputs.forEach(input => {
      input.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
          // Could implement auto-save to localStorage here
        }, 2000);
      });
    });
  </script>
</body>
</html>