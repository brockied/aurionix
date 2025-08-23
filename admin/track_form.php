<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();
$trackId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get all albums for dropdown
$albums = $pdo->query('SELECT id, title, cover FROM albums ORDER BY title')->fetchAll();

if (empty($albums)) {
    $message = 'You need to create at least one album before adding tracks.';
    $needsAlbum = true;
} else {
    $needsAlbum = false;
}

$track = [
    'id'          => 0,
    'album_id'    => $albums ? $albums[0]['id'] : 0,
    'track_number'=> 1,
    'title'       => '',
    'description' => '',
    'audio_file'  => '',
    'price'       => 0.99,
    'spotify_url' => '',
    'apple_url'   => '',
    'other_url'   => '',
    'genre'       => '',
    'duration'    => '',
    'bpm'         => '',
    'key_signature' => '',
    'explicit'    => 0
];

if ($trackId) {
    $stmt = $pdo->prepare('SELECT * FROM tracks WHERE id = ?');
    $stmt->execute([$trackId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $track = $existing;
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$needsAlbum) {
    $album_id     = (int)($_POST['album_id'] ?? 0);
    $track_number = (int)($_POST['track_number'] ?? 1);
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = (float)($_POST['price'] ?? 0);
    $spotify_url  = trim($_POST['spotify_url'] ?? '');
    $apple_url    = trim($_POST['apple_url'] ?? '');
    $other_url    = trim($_POST['other_url'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $duration     = trim($_POST['duration'] ?? '');
    $bpm          = (int)($_POST['bpm'] ?? 0);
    $key_signature = trim($_POST['key_signature'] ?? '');
    $explicit     = isset($_POST['explicit']) ? 1 : 0;
    $audioName    = $track['audio_file'];

    // Validation
    if (!$title) {
        $errors[] = 'Track title is required.';
    }
    if (!$album_id) {
        $errors[] = 'You must select an album.';
    }
    if ($track_number < 1) {
        $errors[] = 'Track number must be at least 1.';
    }
    if ($price < 0 || $price > 999.99) {
        $errors[] = 'Price must be between 0 and 999.99.';
    }

    // Validate duration format (MM:SS)
    if ($duration && !preg_match('/^\d{1,2}:\d{2}$/', $duration)) {
        $errors[] = 'Duration must be in MM:SS format (e.g., 3:45).';
    }

    // Validate BPM
    if ($bpm && ($bpm < 60 || $bpm > 200)) {
        $errors[] = 'BPM should be between 60 and 200.';
    }

    // Handle audio file upload
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['audio_file']['tmp_name'];
        $originalName = $_FILES['audio_file']['name'];
        $fileSize = $_FILES['audio_file']['size'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['mp3', 'wav', 'flac', 'ogg', 'm4a', 'aac'];
        if (!in_array($ext, $allowedTypes)) {
            $errors[] = 'Invalid audio format. Allowed: MP3, WAV, FLAC, OGG, M4A, AAC';
        }
        
        // Validate file size (50MB max)
        if ($fileSize > 50 * 1024 * 1024) {
            $errors[] = 'Audio file too large. Maximum size: 50MB';
        }
        
        if (empty($errors)) {
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/tracks/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $newName = uniqid('track_') . '.' . $ext;
            $target = $uploadDir . $newName;
            
            if (move_uploaded_file($tmpName, $target)) {
                // Delete old file if exists and not default
                if ($track['audio_file'] && file_exists($uploadDir . $track['audio_file'])) {
                    unlink($uploadDir . $track['audio_file']);
                }
                $audioName = $newName;
            } else {
                $errors[] = 'Failed to upload audio file.';
            }
        }
    } elseif (!$trackId && !$track['audio_file']) {
        $errors[] = 'Audio file is required for new tracks.';
    }

    if (!$errors) {
        try {
            // Check if additional columns exist, add them if not
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM tracks LIKE 'genre'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE tracks ADD COLUMN genre VARCHAR(100) DEFAULT ''");
                    $pdo->exec("ALTER TABLE tracks ADD COLUMN duration VARCHAR(10) DEFAULT ''");
                    $pdo->exec("ALTER TABLE tracks ADD COLUMN bpm INT DEFAULT 0");
                    $pdo->exec("ALTER TABLE tracks ADD COLUMN key_signature VARCHAR(10) DEFAULT ''");
                    $pdo->exec("ALTER TABLE tracks ADD COLUMN explicit TINYINT(1) DEFAULT 0");
                }
            } catch (Exception $e) {
                // Columns might already exist
            }

            if ($trackId) {
                // Update existing track
                $stmt = $pdo->prepare('
                    UPDATE tracks 
                    SET album_id=?, track_number=?, title=?, description=?, audio_file=?, price=?, 
                        spotify_url=?, apple_url=?, other_url=?, genre=?, duration=?, bpm=?, 
                        key_signature=?, explicit=?
                    WHERE id=?
                ');
                $stmt->execute([
                    $album_id, $track_number, $title, $description, $audioName, $price,
                    $spotify_url, $apple_url, $other_url, $genre, $duration, $bpm,
                    $key_signature, $explicit, $trackId
                ]);
                $success = 'Track updated successfully!';
            } else {
                // Create new track
                $stmt = $pdo->prepare('
                    INSERT INTO tracks 
                    (album_id, track_number, title, description, audio_file, price, spotify_url, apple_url, other_url, genre, duration, bpm, key_signature, explicit) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ');
                $stmt->execute([
                    $album_id, $track_number, $title, $description, $audioName, $price,
                    $spotify_url, $apple_url, $other_url, $genre, $duration, $bpm,
                    $key_signature, $explicit
                ]);
                $trackId = $pdo->lastInsertId();
                $success = 'Track created successfully!';
            }
            
            // Update track array with new values
            $track = array_merge($track, [
                'album_id'     => $album_id,
                'track_number' => $track_number,
                'title'        => $title,
                'description'  => $description,
                'audio_file'   => $audioName,
                'price'        => $price,
                'spotify_url'  => $spotify_url,
                'apple_url'    => $apple_url,
                'other_url'    => $other_url,
                'genre'        => $genre,
                'duration'     => $duration,
                'bpm'          => $bpm,
                'key_signature' => $key_signature,
                'explicit'     => $explicit
            ]);
            
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Preserve entered values on error
    if ($errors) {
        $track = array_merge($track, [
            'album_id'     => $album_id,
            'track_number' => $track_number,
            'title'        => $title,
            'description'  => $description,
            'audio_file'   => $audioName,
            'price'        => $price,
            'spotify_url'  => $spotify_url,
            'apple_url'    => $apple_url,
            'other_url'    => $other_url,
            'genre'        => $genre,
            'duration'     => $duration,
            'bpm'          => $bpm,
            'key_signature' => $key_signature,
            'explicit'     => $explicit
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $trackId ? 'Edit Track' : 'Add Track'; ?> - Aurionix Admin</title>
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
      <h1><?= $trackId ? 'Edit Track' : 'Add Track'; ?></h1>
      <p class="page-subtitle"><?= $trackId ? 'Update your track information and audio file' : 'Upload a new track to your music collection'; ?></p>
    </div>

    <?php if ($needsAlbum): ?>
      <div class="alert alert-warning">
        <span>⚠️</span>
        <div>
          <strong>No Albums Found</strong>
          <p style="margin: 0.5rem 0 0; color: var(--admin-text-muted);">
            You need to create at least one album before you can add tracks.
          </p>
          <a href="album_form.php" class="btn btn--primary" style="margin-top: 1rem;">
            ✨ Create Your First Album
          </a>
        </div>
      </div>
    <?php else: ?>

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

    <form method="post" enctype="multipart/form-data" action="track_form.php<?= $trackId ? '?id=' . $trackId : ''; ?>" class="admin-form">
      <!-- Basic Information Section -->
      <div class="form-section">
        <h3 class="form-section-title">🎵 Basic Information</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="album_id">Album *</label>
            <select id="album_id" name="album_id" required onchange="updateAlbumPreview()">
              <?php foreach ($albums as $album): ?>
                <option value="<?= $album['id']; ?>" data-cover="<?= htmlspecialchars($album['cover']); ?>" 
                        <?= $album['id'] == $track['album_id'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($album['title']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="track_number">Track Number *</label>
            <input type="number" id="track_number" name="track_number" min="1" max="99" 
                   value="<?= (int)$track['track_number']; ?>" required />
          </div>

          <div class="form-field" style="grid-column: 1 / -1;">
            <label for="title">Track Title *</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($track['title']); ?>" 
                   required placeholder="Enter track title" maxlength="255" />
          </div>
        </div>

        <div class="form-field">
          <label for="description">Description</label>
          <textarea id="description" name="description" 
                    placeholder="Describe your track, its story, or inspiration..."
                    maxlength="1000"><?= htmlspecialchars($track['description']); ?></textarea>
          <small style="color: var(--admin-text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
            Characters remaining: <span id="char-count"><?= 1000 - strlen($track['description']); ?></span>/1000
          </small>
        </div>
      </div>

      <!-- Audio File Section -->
      <div class="form-section">
        <h3 class="form-section-title">🎧 Audio File</h3>
        
        <?php if ($track['audio_file']): ?>
          <div style="background: var(--admin-bg-secondary); padding: 1rem; border-radius: var(--admin-border-radius); margin-bottom: 1rem;">
            <h4 style="margin: 0 0 0.5rem; color: var(--admin-text);">Current Audio File</h4>
            <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.875rem;">
              📁 <?= htmlspecialchars($track['audio_file']); ?>
            </p>
            <audio controls style="width: 100%; margin-top: 0.5rem;">
              <source src="/uploads/tracks/<?= htmlspecialchars($track['audio_file']); ?>" type="audio/mpeg">
              Your browser does not support the audio element.
            </audio>
          </div>
        <?php endif; ?>

        <div class="file-upload" id="audio-upload">
          <input type="file" name="audio_file" accept="audio/*" id="audio-input" />
          <div class="file-upload-content">
            <div class="file-upload-icon">🎵</div>
            <div class="file-upload-text">
              <strong><?= $track['audio_file'] ? 'Replace audio file' : 'Upload audio file'; ?></strong> or drag and drop
            </div>
            <div class="file-upload-subtext">
              Supports: MP3, WAV, FLAC, OGG, M4A, AAC (Max: 50MB)
            </div>
          </div>
        </div>
      </div>

      <!-- Track Details Section -->
      <div class="form-section">
        <h3 class="form-section-title">📊 Track Details</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="price">Price (<?= CURRENCY; ?>) *</label>
            <input type="number" id="price" name="price" step="0.01" min="0" max="999.99"
                   value="<?= htmlspecialchars($track['price']); ?>" required placeholder="0.99" />
          </div>

          <div class="form-field">
            <label for="genre">Genre</label>
            <select id="genre" name="genre">
              <option value="">Select Genre</option>
              <option value="Hip Hop" <?= $track['genre'] === 'Hip Hop' ? 'selected' : ''; ?>>Hip Hop</option>
              <option value="R&B" <?= $track['genre'] === 'R&B' ? 'selected' : ''; ?>>R&B</option>
              <option value="Pop" <?= $track['genre'] === 'Pop' ? 'selected' : ''; ?>>Pop</option>
              <option value="Electronic" <?= $track['genre'] === 'Electronic' ? 'selected' : ''; ?>>Electronic</option>
              <option value="Rock" <?= $track['genre'] === 'Rock' ? 'selected' : ''; ?>>Rock</option>
              <option value="Jazz" <?= $track['genre'] === 'Jazz' ? 'selected' : ''; ?>>Jazz</option>
              <option value="Trap" <?= $track['genre'] === 'Trap' ? 'selected' : ''; ?>>Trap</option>
              <option value="Lo-fi" <?= $track['genre'] === 'Lo-fi' ? 'selected' : ''; ?>>Lo-fi</option>
              <option value="Ambient" <?= $track['genre'] === 'Ambient' ? 'selected' : ''; ?>>Ambient</option>
              <option value="Other" <?= $track['genre'] === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <div class="form-field">
            <label for="duration">Duration (MM:SS)</label>
            <input type="text" id="duration" name="duration" pattern="\d{1,2}:\d{2}"
                   value="<?= htmlspecialchars($track['duration']); ?>" placeholder="3:45" />
          </div>

          <div class="form-field">
            <label for="bpm">BPM (Beats Per Minute)</label>
            <input type="number" id="bpm" name="bpm" min="60" max="200"
                   value="<?= (int)$track['bpm'] ?: ''; ?>" placeholder="120" />
          </div>

          <div class="form-field">
            <label for="key_signature">Key Signature</label>
            <select id="key_signature" name="key_signature">
              <option value="">Select Key</option>
              <option value="C Major" <?= $track['key_signature'] === 'C Major' ? 'selected' : ''; ?>>C Major</option>
              <option value="C# Major" <?= $track['key_signature'] === 'C# Major' ? 'selected' : ''; ?>>C# Major</option>
              <option value="D Major" <?= $track['key_signature'] === 'D Major' ? 'selected' : ''; ?>>D Major</option>
              <option value="Eb Major" <?= $track['key_signature'] === 'Eb Major' ? 'selected' : ''; ?>>Eb Major</option>
              <option value="E Major" <?= $track['key_signature'] === 'E Major' ? 'selected' : ''; ?>>E Major</option>
              <option value="F Major" <?= $track['key_signature'] === 'F Major' ? 'selected' : ''; ?>>F Major</option>
              <option value="F# Major" <?= $track['key_signature'] === 'F# Major' ? 'selected' : ''; ?>>F# Major</option>
              <option value="G Major" <?= $track['key_signature'] === 'G Major' ? 'selected' : ''; ?>>G Major</option>
              <option value="Ab Major" <?= $track['key_signature'] === 'Ab Major' ? 'selected' : ''; ?>>Ab Major</option>
              <option value="A Major" <?= $track['key_signature'] === 'A Major' ? 'selected' : ''; ?>>A Major</option>
              <option value="Bb Major" <?= $track['key_signature'] === 'Bb Major' ? 'selected' : ''; ?>>Bb Major</option>
              <option value="B Major" <?= $track['key_signature'] === 'B Major' ? 'selected' : ''; ?>>B Major</option>
              <!-- Minor keys -->
              <option value="A Minor" <?= $track['key_signature'] === 'A Minor' ? 'selected' : ''; ?>>A Minor</option>
              <option value="A# Minor" <?= $track['key_signature'] === 'A# Minor' ? 'selected' : ''; ?>>A# Minor</option>
              <option value="B Minor" <?= $track['key_signature'] === 'B Minor' ? 'selected' : ''; ?>>B Minor</option>
              <option value="C Minor" <?= $track['key_signature'] === 'C Minor' ? 'selected' : ''; ?>>C Minor</option>
              <option value="C# Minor" <?= $track['key_signature'] === 'C# Minor' ? 'selected' : ''; ?>>C# Minor</option>
              <option value="D Minor" <?= $track['key_signature'] === 'D Minor' ? 'selected' : ''; ?>>D Minor</option>
              <option value="D# Minor" <?= $track['key_signature'] === 'D# Minor' ? 'selected' : ''; ?>>D# Minor</option>
              <option value="E Minor" <?= $track['key_signature'] === 'E Minor' ? 'selected' : ''; ?>>E Minor</option>
              <option value="F Minor" <?= $track['key_signature'] === 'F Minor' ? 'selected' : ''; ?>>F Minor</option>
              <option value="F# Minor" <?= $track['key_signature'] === 'F# Minor' ? 'selected' : ''; ?>>F# Minor</option>
              <option value="G Minor" <?= $track['key_signature'] === 'G Minor' ? 'selected' : ''; ?>>G Minor</option>
              <option value="G# Minor" <?= $track['key_signature'] === 'G# Minor' ? 'selected' : ''; ?>>G# Minor</option>
            </select>
          </div>

          <div class="form-field">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="explicit" value="1" <?= $track['explicit'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>Explicit Content</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem; margin-top: 0.25rem;">
                  Mark if track contains explicit lyrics
                </div>
              </div>
            </label>
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
                   value="<?= htmlspecialchars($track['spotify_url']); ?>"
                   placeholder="https://open.spotify.com/track/..." />
          </div>

          <div class="form-field">
            <label for="apple_url">
              <span style="color: #FA57C1;">🎵</span> Apple Music URL
            </label>
            <input type="url" id="apple_url" name="apple_url" 
                   value="<?= htmlspecialchars($track['apple_url']); ?>"
                   placeholder="https://music.apple.com/song/..." />
          </div>

          <div class="form-field" style="grid-column: 1 / -1;">
            <label for="other_url">🌐 Other Platform URL</label>
            <input type="url" id="other_url" name="other_url" 
                   value="<?= htmlspecialchars($track['other_url']); ?>"
                   placeholder="https://soundcloud.com/your-track..." />
            <small style="color: var(--admin-text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
              YouTube, SoundCloud, Bandcamp, or any other streaming platform
            </small>
          </div>
        </div>
      </div>

      <!-- Album Preview -->
      <div id="album-preview" class="form-section" style="display: none;">
        <h3 class="form-section-title">💿 Album Preview</h3>
        <div style="display: flex; align-items: center; gap: 1rem; background: var(--admin-bg-secondary); padding: 1rem; border-radius: var(--admin-border-radius);">
          <img id="album-cover" style="width: 80px; height: 80px; border-radius: var(--admin-border-radius); object-fit: cover;" />
          <div>
            <h4 id="album-title" style="margin: 0; color: var(--admin-text);"></h4>
            <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.875rem;">This track will be added to this album</p>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="btn-group">
        <button type="submit" class="btn btn--primary btn--lg">
          <?= $trackId ? '💾 Update Track' : '🎵 Create Track'; ?>
        </button>
        <a href="tracks.php" class="btn btn--outline btn--lg">
          ❌ Cancel
        </a>
        <?php if ($trackId): ?>
          <a href="/album.php?id=<?= $track['album_id']; ?>#track-<?= $trackId; ?>" target="_blank" class="btn btn--secondary btn--lg">
            👁️ Preview Track
          </a>
        <?php endif; ?>
      </div>
    </form>

    <?php endif; ?>
  </main>

  <script>
    // Character counter for description
    const descTextarea = document.getElementById('description');
    const charCount = document.getElementById('char-count');
    
    if (descTextarea && charCount) {
      descTextarea.addEventListener('input', function() {
        const remaining = 1000 - this.value.length;
        charCount.textContent = remaining;
        charCount.style.color = remaining < 100 ? 'var(--admin-error)' : 'var(--admin-text-muted)';
      });
    }

    // Update album preview
    function updateAlbumPreview() {
      const albumSelect = document.getElementById('album_id');
      const selectedOption = albumSelect.options[albumSelect.selectedIndex];
      const albumPreview = document.getElementById('album-preview');
      const albumCover = document.getElementById('album-cover');
      const albumTitle = document.getElementById('album-title');
      
      if (selectedOption && albumPreview && albumCover && albumTitle) {
        const cover = selectedOption.getAttribute('data-cover');
        albumCover.src = cover ? `/uploads/albums/${cover}` : '/assets/images/default-cover.png';
        albumTitle.textContent = selectedOption.text;
        albumPreview.style.display = 'block';
      }
    }

    // Initialize album preview
    document.addEventListener('DOMContentLoaded', function() {
      updateAlbumPreview();
    });

    // Audio file drag and drop
    const audioUpload = document.getElementById('audio-upload');
    const audioInput = document.getElementById('audio-input');

    if (audioUpload && audioInput) {
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        audioUpload.addEventListener(eventName, preventDefaults, false);
      });

      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }

      ['dragenter', 'dragover'].forEach(eventName => {
        audioUpload.addEventListener(eventName, highlight, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        audioUpload.addEventListener(eventName, unhighlight, false);
      });

      function highlight(e) {
        audioUpload.style.borderColor = 'var(--admin-primary)';
        audioUpload.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
      }

      function unhighlight(e) {
        audioUpload.style.borderColor = 'var(--admin-border)';
        audioUpload.style.backgroundColor = 'var(--admin-bg-secondary)';
      }

      audioUpload.addEventListener('drop', handleDrop, false);

      function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
          audioInput.files = files;
          validateAudioFile(files[0]);
        }
      }

      // Validate audio file
      audioInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          validateAudioFile(file);
        }
      });

      function validateAudioFile(file) {
        // Check file size
        if (file.size > 50 * 1024 * 1024) {
          alert('File size too large. Maximum size is 50MB.');
          audioInput.value = '';
          return;
        }

        // Check file type
        const allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/flac', 'audio/ogg', 'audio/x-m4a', 'audio/aac'];
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(mp3|wav|flac|ogg|m4a|aac)$/i)) {
          alert('Invalid file type. Please use MP3, WAV, FLAC, OGG, M4A, or AAC.');
          audioInput.value = '';
          return;
        }

        // Show file info
        const fileName = file.name;
        const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        console.log('Audio file selected:', fileName, fileSize);
      }
    }

    // Duration format validation
    const durationInput = document.getElementById('duration');
    if (durationInput) {
      durationInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d:]/g, '');
        
        // Auto-format as MM:SS
        if (value.length === 3 && value.indexOf(':') === -1) {
          value = value.substring(0, 1) + ':' + value.substring(1);
        } else if (value.length === 4 && value.indexOf(':') === -1) {
          value = value.substring(0, 2) + ':' + value.substring(2);
        }
        
        e.target.value = value;
      });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const albumId = document.getElementById('album_id').value;
        const price = parseFloat(document.getElementById('price').value);

        if (!title) {
          alert('Please enter a track title.');
          e.preventDefault();
          return;
        }

        if (!albumId) {
          alert('Please select an album.');
          e.preventDefault();
          return;
        }

        if (isNaN(price) || price < 0) {
          alert('Please enter a valid price.');
          e.preventDefault();
          return;
        }

        // Check if audio file is provided for new tracks
        <?php if (!$trackId): ?>
        const audioFile = document.getElementById('audio-input').files[0];
        if (!audioFile && !'<?= $track['audio_file']; ?>') {
          alert('Please upload an audio file.');
          e.preventDefault();
          return;
        }
        <?php endif; ?>
      });
    }
  </script>
</body>
</html>