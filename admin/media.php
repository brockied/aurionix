<?php
/**
 * MEDIA MANAGEMENT PAGE
 * Place this file as: admin/media.php
 */

require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    $upload_type = $_POST['upload_type'] ?? 'album';
    
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media_file'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_types = [
            'album' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'audio' => ['mp3', 'wav', 'flac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'txt']
        ];
        
        if (!in_array($file_ext, $allowed_types[$upload_type])) {
            $error = 'Invalid file type for ' . $upload_type . ' upload.';
        } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
            $error = 'File size too large. Maximum 10MB allowed.';
        } else {
            // Create upload directory
            $upload_dir = "../uploads/{$upload_type}s/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $new_filename = time() . '_' . sanitizeFileName($file_name);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Store in database
                $stmt = $pdo->prepare("
                    INSERT INTO media_files (filename, original_name, file_path, file_type, file_size, upload_type) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $new_filename,
                    $file_name,
                    "/uploads/{$upload_type}s/" . $new_filename,
                    $file_ext,
                    $file_size,
                    $upload_type
                ]);
                
                $message = 'File uploaded successfully!';
            } else {
                $error = 'Failed to upload file.';
            }
        }
    } else {
        $error = 'No file selected or upload error.';
    }
}

// Handle file deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Get file info
        $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch();
        
        if ($file) {
            // Delete the physical file.  The stored file_path begins with
            // '/uploads/...', which is relative to the website root.  Build
            // the real filesystem path by prepending '..' and trimming the
            // leading slash.  Using '.' would incorrectly point to the admin
            // directory.
            $physicalPath = '..' . ltrim($file['file_path'], '/');
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM media_files WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'File deleted successfully!';
        } else {
            $error = 'File not found.';
        }
    } catch (Exception $e) {
        $error = 'Failed to delete file: ' . $e->getMessage();
    }
    
    $action = 'list';
}

// Create media_files table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_size INT NOT NULL,
            upload_type ENUM('album', 'audio', 'document') DEFAULT 'album',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_upload_type (upload_type),
            INDEX idx_uploaded_at (uploaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Get media files for listing
$filter_type = $_GET['type'] ?? '';
$where_conditions = [];
$params = [];

if ($filter_type) {
    $where_conditions[] = "upload_type = ?";
    $params[] = $filter_type;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("SELECT * FROM media_files $where_clause ORDER BY uploaded_at DESC");
$stmt->execute($params);
$media_files = $stmt->fetchAll();

// Get storage stats
$stmt = $pdo->query("
    SELECT 
        upload_type,
        COUNT(*) as file_count,
        SUM(file_size) as total_size
    FROM media_files 
    GROUP BY upload_type
");
$storage_stats = $stmt->fetchAll();

$total_files = array_sum(array_column($storage_stats, 'file_count'));
$total_size = array_sum(array_column($storage_stats, 'total_size'));

function sanitizeFileName($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getFileIcon($file_type) {
    $icons = [
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️',
        'mp3' => '🎵', 'wav' => '🎵', 'flac' => '🎵', 'm4a' => '🎵',
        'pdf' => '📄', 'doc' => '📄', 'docx' => '📄', 'txt' => '📄'
    ];
    return $icons[$file_type] ?? '📎';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media - Aurionix Admin</title>
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
            
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>Media Library</h1>
                        <p>Manage your uploaded files and media assets</p>
                    </div>
                    <div class="header-actions">
                        <button onclick="showUploadModal()" class="btn btn-primary">
                            📁 Upload Files
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Storage Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📁</div>
                    <div class="stat-info">
                        <h3><?= $total_files ?></h3>
                        <p>Total Files</p>
                    </div>
                    <div class="stat-trend neutral">→ Files</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-info">
                        <h3><?= formatFileSize($total_size) ?></h3>
                        <p>Storage Used</p>
                    </div>
                    <div class="stat-trend neutral">→ Space</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-info">
                        <h3><?= array_sum(array_filter(array_column($storage_stats, 'file_count'), function($stat, $key) use ($storage_stats) {
                            return $storage_stats[$key]['upload_type'] === 'album';
                        }, ARRAY_FILTER_USE_BOTH)) ?? 0 ?></h3>
                        <p>Images</p>
                    </div>
                    <div class="stat-trend positive">↗ Visual</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎵</div>
                    <div class="stat-info">
                        <h3><?= array_sum(array_filter(array_column($storage_stats, 'file_count'), function($stat, $key) use ($storage_stats) {
                            return $storage_stats[$key]['upload_type'] === 'audio';
                        }, ARRAY_FILTER_USE_BOTH)) ?? 0 ?></h3>
                        <p>Audio Files</p>
                    </div>
                    <div class="stat-trend positive">↗ Audio</div>
                </div>
            </div>
            
            <!-- File Filters -->
            <div class="content-filters">
                <div class="filter-left">
                    <select class="filter-select" id="typeFilter" onchange="filterByType()">
                        <option value="">All Files</option>
                        <option value="album" <?= $filter_type === 'album' ? 'selected' : '' ?>>Album Covers</option>
                        <option value="audio" <?= $filter_type === 'audio' ? 'selected' : '' ?>>Audio Files</option>
                        <option value="document" <?= $filter_type === 'document' ? 'selected' : '' ?>>Documents</option>
                    </select>
                </div>
                
                <div class="filter-right">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" onclick="switchView('grid')">⊞</button>
                        <button class="view-btn" data-view="list" onclick="switchView('list')">☰</button>
                    </div>
                </div>
            </div>
            
            <!-- Media Files -->
            <?php if (empty($media_files)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <h3>No files uploaded</h3>
                    <p>Upload your first media file to get started</p>
                    <button onclick="showUploadModal()" class="btn btn-primary">Upload Files</button>
                </div>
            <?php else: ?>
                <!-- Grid View -->
                <div class="media-grid" id="mediaGrid">
                    <?php foreach ($media_files as $file): ?>
                        <div class="media-card" data-type="<?= $file['upload_type'] ?>">
                            <div class="media-preview">
                                <?php if (in_array($file['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <img src="<?= $file['file_path'] ?>" alt="<?= htmlspecialchars($file['original_name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="file-icon">
                                        <?= getFileIcon($file['file_type']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="media-overlay">
                                    <div class="media-actions">
                                        <button onclick="viewFile('<?= $file['file_path'] ?>', '<?= $file['file_type'] ?>')" class="btn-icon" title="View">👁️</button>
                                        <button onclick="copyPath('<?= $file['file_path'] ?>')" class="btn-icon" title="Copy Path">📋</button>
                                        <a href="<?= $file['file_path'] ?>" download class="btn-icon" title="Download">📥</a>
                                        <button onclick="deleteFile(<?= $file['id'] ?>)" class="btn-icon btn-danger" title="Delete">🗑️</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="media-info">
                                <h4 title="<?= htmlspecialchars($file['original_name']) ?>">
                                    <?= htmlspecialchars(strlen($file['original_name']) > 20 ? substr($file['original_name'], 0, 20) . '...' : $file['original_name']) ?>
                                </h4>
                                <p><?= formatFileSize($file['file_size']) ?></p>
                                <p class="upload-date"><?= date('M j, Y', strtotime($file['uploaded_at'])) ?></p>
                                <span class="file-type-badge"><?= strtoupper($file['file_type']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- List View -->
                <div class="media-list" id="mediaList" style="display: none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>File Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($media_files as $file): ?>
                                <tr>
                                    <td>
                                        <div class="table-preview">
                                            <?php if (in_array($file['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                <img src="<?= $file['file_path'] ?>" alt="Preview">
                                            <?php else: ?>
                                                <span class="file-icon"><?= getFileIcon($file['file_type']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($file['original_name']) ?></strong>
                                        <br>
                                        <small class="file-path"><?= htmlspecialchars($file['file_path']) ?></small>
                                    </td>
                                    <td>
                                        <span class="file-type-badge"><?= strtoupper($file['file_type']) ?></span>
                                        <br>
                                        <small><?= ucfirst($file['upload_type']) ?></small>
                                    </td>
                                    <td><?= formatFileSize($file['file_size']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($file['uploaded_at'])) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button onclick="viewFile('<?= $file['file_path'] ?>', '<?= $file['file_type'] ?>')" class="btn-icon" title="View">👁️</button>
                                            <button onclick="copyPath('<?= $file['file_path'] ?>')" class="btn-icon" title="Copy">📋</button>
                                            <a href="<?= $file['file_path'] ?>" download class="btn-icon" title="Download">📥</a>
                                            <button onclick="deleteFile(<?= $file['id'] ?>)" class="btn-icon btn-danger" title="Delete">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Files</h3>
                <button onclick="hideUploadModal()" class="close-btn">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" action="?action=upload" class="upload-form">
                <div class="form-group">
                    <label class="form-label">Upload Type</label>
                    <select name="upload_type" class="form-select" required>
                        <option value="album">Album Cover (JPG, PNG, WebP)</option>
                        <option value="audio">Audio File (MP3, WAV, FLAC)</option>
                        <option value="document">Document (PDF, DOC, TXT)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <input type="file" name="media_file" class="form-input" required accept="image/*,audio/*,.pdf,.doc,.docx,.txt">
                    <small class="form-help">Maximum file size: 10MB</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">📁 Upload File</button>
                    <button type="button" onclick="hideUploadModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- File Viewer Modal -->
    <div class="modal" id="viewerModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>File Viewer</h3>
                <button onclick="hideViewerModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body" id="fileViewer">
                <!-- File content will be loaded here -->
            </div>
        </div>
    </div>
    
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .media-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .media-card:hover {
            transform: translateY(-5px);
            border-color: #e94560;
        }
        
        .media-preview {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
        }
        
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 3rem;
            background: rgba(255,255,255,0.05);
        }
        
        .media-overlay {
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
        
        .media-card:hover .media-overlay {
            opacity: 1;
        }
        
        .media-actions {
            display: flex;
            gap: 10px;
        }
        
        .media-info {
            padding: 15px;
        }
        
        .media-info h4 {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .media-info p {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            margin-bottom: 3px;
        }
        
        .upload-date {
            font-size: 0.7rem !important;
        }
        
        .file-type-badge {
            background: rgba(233, 69, 96, 0.2);
            color: #e94560;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .table-preview {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
        }
        
        .table-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-path {
            color: rgba(255,255,255,0.5);
            font-family: monospace;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-large {
            max-width: 800px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
        }
    </style>
    
    <script>
        function showUploadModal() {
            document.getElementById('uploadModal').classList.add('show');
        }
        
        function hideUploadModal() {
            document.getElementById('uploadModal').classList.remove('show');
        }
        
        function hideViewerModal() {
            document.getElementById('viewerModal').classList.remove('show');
        }
        
        function filterByType() {
            const type = document.getElementById('typeFilter').value;
            const url = new URL(window.location);
            
            if (type) {
                url.searchParams.set('type', type);
            } else {
                url.searchParams.delete('type');
            }
            
            window.location.href = url.toString();
        }
        
        function switchView(view) {
            const gridView = document.getElementById('mediaGrid');
            const listView = document.getElementById('mediaList');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            if (view === 'grid') {
                gridView.style.display = 'grid';
                listView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                listView.style.display = 'block';
            }
        }
        
        function viewFile(path, type) {
            const viewer = document.getElementById('fileViewer');
            const modal = document.getElementById('viewerModal');
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(type)) {
                viewer.innerHTML = `<img src="${path}" style="max-width: 100%; height: auto; border-radius: 8px;">`;
            } else if (['mp3', 'wav', 'm4a'].includes(type)) {
                viewer.innerHTML = `<audio controls style="width: 100%;"><source src="${path}" type="audio/${type}">Your browser does not support audio.</audio>`;
            } else {
                viewer.innerHTML = `<p>File preview not available. <a href="${path}" target="_blank" style="color: #e94560;">Open in new tab</a></p>`;
            }
            
            modal.classList.add('show');
        }
        
        function copyPath(path) {
            navigator.clipboard.writeText(path).then(() => {
                alert('File path copied to clipboard!');
            });
        }
        
        function deleteFile(id) {
            if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                window.location.href = `?action=delete&id=${id}`;
            }
        }
        
        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>