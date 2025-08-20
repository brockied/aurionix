<?php
/**
 * STREAMING LINKS MANAGEMENT
 * Place this file as: admin/streaming-links.php
 */

require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $album_id = $_POST['album_id'];
        $platform = $_POST['platform'];
        $url = $_POST['url'];
        $country_code = $_POST['country_code'] ?: 'global';
        $embed_code = $_POST['embed_code'] ?? '';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO streaming_links (album_id, platform, url, country_code, embed_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$album_id, $platform, $url, $country_code, $embed_code]);
            $message = 'Streaming link added successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to add streaming link: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $album_id = $_POST['album_id'];
        $platform = $_POST['platform'];
        $url = $_POST['url'];
        $country_code = $_POST['country_code'] ?: 'global';
        $embed_code = $_POST['embed_code'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE streaming_links SET album_id = ?, platform = ?, url = ?, country_code = ?, embed_code = ? WHERE id = ?");
            $stmt->execute([$album_id, $platform, $url, $country_code, $embed_code, $id]);
            $message = 'Streaming link updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Failed to update streaming link: ' . $e->getMessage();
        }
    }
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM streaming_links WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Streaming link deleted successfully!';
        $action = 'list';
    } catch (Exception $e) {
        $error = 'Failed to delete streaming link: ' . $e->getMessage();
    }
}

// Get streaming links for listing
if ($action === 'list') {
    $album_filter = $_GET['album_id'] ?? '';
    $platform_filter = $_GET['platform'] ?? '';
    $country_filter = $_GET['country'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($album_filter) {
        $where_conditions[] = "sl.album_id = ?";
        $params[] = $album_filter;
    }
    
    if ($platform_filter) {
        $where_conditions[] = "sl.platform = ?";
        $params[] = $platform_filter;
    }
    
    if ($country_filter) {
        $where_conditions[] = "sl.country_code = ?";
        $params[] = $country_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT sl.*, a.title as album_title FROM streaming_links sl 
            LEFT JOIN albums a ON sl.album_id = a.id 
            $where_clause 
            ORDER BY a.title, sl.platform, sl.country_code";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $streaming_links = $stmt->fetchAll();
}

// Get albums for dropdowns
$stmt = $pdo->query("SELECT id, title FROM albums ORDER BY title");
$albums = $stmt->fetchAll();

// Get single link for editing
if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM streaming_links WHERE id = ?");
    $stmt->execute([$id]);
    $edit_link = $stmt->fetch();
    
    if (!$edit_link) {
        $error = 'Streaming link not found!';
        $action = 'list';
    }
}

// Supported platforms
$platforms = [
    'spotify' => ['name' => 'Spotify', 'icon' => '🎵'],
    'apple-music' => ['name' => 'Apple Music', 'icon' => '🍎'],
    'youtube' => ['name' => 'YouTube Music', 'icon' => '📺'],
    'youtube-video' => ['name' => 'YouTube Video', 'icon' => '📹'],
    'soundcloud' => ['name' => 'SoundCloud', 'icon' => '☁️'],
    'amazon-music' => ['name' => 'Amazon Music', 'icon' => '📦'],
    'tidal' => ['name' => 'Tidal', 'icon' => '🌊'],
    'deezer' => ['name' => 'Deezer', 'icon' => '🎧'],
    'bandcamp' => ['name' => 'Bandcamp', 'icon' => '💿'],
    'itunes' => ['name' => 'iTunes', 'icon' => '🎶']
];

// Common countries
$countries = [
    'global' => 'Global (All Countries)',
    'US' => 'United States',
    'CA' => 'Canada',
    'GB' => 'United Kingdom',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'ES' => 'Spain',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'SE' => 'Sweden',
    'NO' => 'Norway',
    'DK' => 'Denmark',
    'FI' => 'Finland',
    'BR' => 'Brazil',
    'MX' => 'Mexico',
    'JP' => 'Japan',
    'KR' => 'South Korea',
    'IN' => 'India'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Links - Aurionix Admin</title>
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
                <!-- Streaming Links List View -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1>Streaming Links</h1>
                            <p>Manage streaming platform links for your albums</p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=add" class="btn btn-primary">
                                🔗 Add Streaming Link
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="content-filters">
                    <div class="filter-left">
                        <select class="filter-select" id="albumFilter">
                            <option value="">All Albums</option>
                            <?php foreach ($albums as $album): ?>
                                <option value="<?= $album['id'] ?>" <?= $album_filter == $album['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($album['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select class="filter-select" id="platformFilter">
                            <option value="">All Platforms</option>
                            <?php foreach ($platforms as $key => $platform): ?>
                                <option value="<?= $key ?>" <?= $platform_filter === $key ? 'selected' : '' ?>>
                                    <?= $platform['icon'] ?> <?= $platform['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select class="filter-select" id="countryFilter">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?= $code ?>" <?= $country_filter === $code ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-right">
                        <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </div>
                
                <!-- Streaming Links Table -->
                <?php if (empty($streaming_links)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔗</div>
                        <h3>No streaming links found</h3>
                        <p>Add streaming platform links to make your music discoverable</p>
                        <a href="?action=add" class="btn btn-primary">Add Your First Link</a>
                    </div>
                <?php else: ?>
                    <div class="links-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Album</th>
                                    <th>Platform</th>
                                    <th>Country</th>
                                    <th>URL</th>
                                    <th>Embed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($streaming_links as $link): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($link['album_title']) ?></strong>
                                        </td>
                                        <td>
                                            <div class="platform-cell">
                                                <span class="platform-icon">
                                                    <?= $platforms[$link['platform']]['icon'] ?? '🎵' ?>
                                                </span>
                                                <span><?= $platforms[$link['platform']]['name'] ?? ucfirst($link['platform']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="country-badge">
                                                <?= $countries[$link['country_code']] ?? $link['country_code'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($link['url']) ?>" 
                                               target="_blank" 
                                               class="link-url" 
                                               title="<?= htmlspecialchars($link['url']) ?>">
                                                <?= substr($link['url'], 0, 50) ?>...
                                                <span class="external-icon">🔗</span>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($link['embed_code']): ?>
                                                <span class="embed-indicator">✅ Available</span>
                                            <?php else: ?>
                                                <span class="embed-indicator no-embed">❌ None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?action=edit&id=<?= $link['id'] ?>" 
                                                   class="btn-icon" title="Edit">✏️</a>
                                                <a href="<?= htmlspecialchars($link['url']) ?>" 
                                                   target="_blank" 
                                                   class="btn-icon" title="Test Link">🔗</a>
                                                <a href="?action=delete&id=<?= $link['id'] ?>" 
                                                   class="btn-icon btn-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this streaming link?')">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Streaming Link Form -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1><?= $action === 'add' ? 'Add Streaming Link' : 'Edit Streaming Link' ?></h1>
                            <p><?= $action === 'add' ? 'Add a new streaming platform link' : 'Update streaming link information' ?></p>
                        </div>
                        <div class="header-actions">
                            <a href="?action=list" class="btn btn-secondary">
                                ← Back to Links
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="form-container">
                    <form method="POST" class="streaming-form">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $edit_link['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-left">
                                <div class="form-group">
                                    <label class="form-label">Album *</label>
                                    <select name="album_id" class="form-select" required>
                                        <option value="">Select an album</option>
                                        <?php foreach ($albums as $album): ?>
                                            <option value="<?= $album['id'] ?>" 
                                                    <?= isset($edit_link) && $edit_link['album_id'] == $album['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($album['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Platform *</label>
                                    <select name="platform" class="form-select" required id="platformSelect">
                                        <option value="">Select a platform</option>
                                        <?php foreach ($platforms as $key => $platform): ?>
                                            <option value="<?= $key ?>" 
                                                    <?= isset($edit_link) && $edit_link['platform'] === $key ? 'selected' : '' ?>>
                                                <?= $platform['icon'] ?> <?= $platform['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Country/Region</label>
                                    <select name="country_code" class="form-select">
                                        <?php foreach ($countries as $code => $name): ?>
                                            <option value="<?= $code ?>" 
                                                    <?= isset($edit_link) && $edit_link['country_code'] === $code ? 'selected' : ($code === 'global' ? 'selected' : '') ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-help">Leave as "Global" for worldwide availability</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Streaming URL *</label>
                                    <input type="url" 
                                           name="url" 
                                           class="form-input" 
                                           placeholder="https://open.spotify.com/album/..."
                                           value="<?= htmlspecialchars($edit_link['url'] ?? '') ?>"
                                           required>
                                    <small class="form-help">Full URL to your album/track on the streaming platform</small>
                                </div>
                            </div>
                            
                            <div class="form-right">
                                <div class="form-group">
                                    <label class="form-label">Embed Code (Optional)</label>
                                    <textarea name="embed_code" 
                                              class="form-textarea" 
                                              placeholder="<iframe src=&quot;...&quot;></iframe>"
                                              rows="8"><?= htmlspecialchars($edit_link['embed_code'] ?? '') ?></textarea>
                                    <small class="form-help">HTML embed code for playing music directly on your site</small>
                                </div>
                                
                                <!-- Platform-specific help -->
                                <div class="platform-help" id="platformHelp">
                                    <h4>Platform Integration Guide</h4>
                                    <div class="help-content" data-platform="spotify">
                                        <h5>🎵 Spotify Integration</h5>
                                        <p><strong>URL Format:</strong> https://open.spotify.com/album/[album-id]</p>
                                        <p><strong>Embed:</strong> Use Spotify's embed generator to get iframe code</p>
                                        <p><strong>Example:</strong> https://open.spotify.com/album/4aawyAB9vmqN3uQ7FjRGTy</p>
                                    </div>
                                    
                                    <div class="help-content" data-platform="apple-music">
                                        <h5>🍎 Apple Music Integration</h5>
                                        <p><strong>URL Format:</strong> https://music.apple.com/[country]/album/[name]/[id]</p>
                                        <p><strong>Embed:</strong> Use Apple Music embed generator</p>
                                        <p><strong>Example:</strong> https://music.apple.com/us/album/album-name/1234567890</p>
                                    </div>
                                    
                                    <div class="help-content" data-platform="youtube">
                                        <h5>📺 YouTube Music Integration</h5>
                                        <p><strong>URL Format:</strong> https://music.youtube.com/playlist?list=[playlist-id]</p>
                                        <p><strong>Embed:</strong> Use YouTube's embed player</p>
                                        <p><strong>Example:</strong> https://music.youtube.com/playlist?list=OLAK5uy_lKhF...</p>
                                    </div>
                                    
                                    <div class="help-content" data-platform="soundcloud">
                                        <h5>☁️ SoundCloud Integration</h5>
                                        <p><strong>URL Format:</strong> https://soundcloud.com/[artist]/sets/[album-name]</p>
                                        <p><strong>Embed:</strong> Click "Share" > "Embed" to get iframe code</p>
                                        <p><strong>Example:</strong> https://soundcloud.com/artist-name/sets/album-name</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= $action === 'add' ? '✅ Add Streaming Link' : '💾 Update Link' ?>
                            </button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            <?php if (isset($edit_link)): ?>
                                <a href="<?= htmlspecialchars($edit_link['url']) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline">🔗 Test Link</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Additional styles for streaming links page */
        .platform-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .platform-icon {
            font-size: 1.2rem;
        }
        
        .country-badge {
            background: rgba(233, 69, 96, 0.1);
            color: #e94560;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .link-url {
            color: #4a9eff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }
        
        .link-url:hover {
            color: #66b3ff;
        }
        
        .external-icon {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .embed-indicator {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .embed-indicator.no-embed {
            color: rgba(255,255,255,0.5);
        }
        
        .streaming-form {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
        }
        
        .form-help {
            display: block;
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .platform-help {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .platform-help h4 {
            margin-bottom: 15px;
            color: #e94560;
        }
        
        .help-content {
            display: none;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            border-left: 3px solid #e94560;
        }
        
        .help-content.active {
            display: block;
        }
        
        .help-content h5 {
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .help-content p {
            margin-bottom: 8px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .help-content strong {
            color: #e94560;
        }
        
        @media (max-width: 768px) {
            .content-filters {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-left {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
        // Filter functionality
        document.getElementById('albumFilter').addEventListener('change', updateFilters);
        document.getElementById('platformFilter').addEventListener('change', updateFilters);
        document.getElementById('countryFilter').addEventListener('change', updateFilters);
        
        function updateFilters() {
            const album = document.getElementById('albumFilter').value;
            const platform = document.getElementById('platformFilter').value;
            const country = document.getElementById('countryFilter').value;
            
            const params = new URLSearchParams();
            params.set('action', 'list');
            if (album) params.set('album_id', album);
            if (platform) params.set('platform', platform);
            if (country) params.set('country', country);
            
            window.location.href = '?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = '?action=list';
        }
        
        // Platform help
        document.addEventListener('DOMContentLoaded', function() {
            const platformSelect = document.getElementById('platformSelect');
            if (platformSelect) {
                platformSelect.addEventListener('change', function() {
                    const platform = this.value;
                    
                    // Hide all help content
                    document.querySelectorAll('.help-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show relevant help content
                    if (platform) {
                        const helpContent = document.querySelector(`[data-platform="${platform}"]`);
                        if (helpContent) {
                            helpContent.classList.add('active');
                        }
                    }
                });
                
                // Show help for initially selected platform
                if (platformSelect.value) {
                    platformSelect.dispatchEvent(new Event('change'));
                }
            }
        });
        
        // URL validation and formatting
        document.querySelector('input[name="url"]').addEventListener('blur', function() {
            const url = this.value.trim();
            const platform = document.getElementById('platformSelect').value;
            
            if (url && platform) {
                // Auto-detect and suggest corrections
                if (platform === 'spotify' && !url.includes('open.spotify.com')) {
                    this.style.borderColor = '#f27121';
                } else if (platform === 'apple-music' && !url.includes('music.apple.com')) {
                    this.style.borderColor = '#f27121';
                } else {
                    this.style.borderColor = '';
                }
            }
        });
    </script>
</body>
</html>