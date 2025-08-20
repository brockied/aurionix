<?php
/**
 * SITE SETTINGS MANAGEMENT
 * Place this file as: admin/settings.php
 */

require_once '../config.php';
requireAdmin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings_to_update = [
            'artist_name',
            'site_title',
            'site_description',
            'hero_title',
            'hero_subtitle',
            'contact_email',
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_youtube',
            'social_spotify',
            'social_soundcloud',
            'analytics_google',
            'analytics_facebook',
            'seo_keywords',
            'footer_text',
            'maintenance_mode'
        ];
        
        foreach ($settings_to_update as $setting) {
            $value = $_POST[$setting] ?? '';
            updateSetting($setting, $value);
        }
        
        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/settings/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo-' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_path)) {
                updateSetting('site_logo', '/uploads/settings/' . $filename);
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/settings/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
            $filename = 'favicon-' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_path)) {
                updateSetting('site_favicon', '/uploads/settings/' . $filename);
                
                // Copy to root as favicon.ico if it's an ico file
                if ($file_extension === 'ico') {
                    copy($upload_path, '../favicon.ico');
                }
            }
        }
        
        $message = 'Settings updated successfully!';
        
    } catch (Exception $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Get current settings
$current_settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Helper function to get setting value
function getSettingValue($key, $default = '') {
    global $current_settings;
    return $current_settings[$key] ?? $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Aurionix Admin</title>
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
            <div class="page-header">
                <h1>Site Settings</h1>
                <p>Configure your website's appearance and functionality</p>
            </div>
            
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
            
            <form method="POST" enctype="multipart/form-data" class="settings-form">
                <div class="settings-tabs">
                    <div class="tab-buttons">
                        <button type="button" class="tab-btn active" data-tab="general">🌐 General</button>
                        <button type="button" class="tab-btn" data-tab="branding">🎨 Branding</button>
                        <button type="button" class="tab-btn" data-tab="social">📱 Social Media</button>
                        <button type="button" class="tab-btn" data-tab="seo">🔍 SEO</button>
                        <button type="button" class="tab-btn" data-tab="analytics">📊 Analytics</button>
                        <button type="button" class="tab-btn" data-tab="advanced">⚙️ Advanced</button>
                    </div>
                    
                    <!-- General Settings Tab -->
                    <div class="tab-content active" data-tab="general">
                        <div class="settings-section">
                            <h2>General Settings</h2>
                            <p>Basic website information and configuration</p>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Artist Name *</label>
                                    <input type="text" 
                                           name="artist_name" 
                                           class="form-input" 
                                           placeholder="Aurionix"
                                           value="<?= htmlspecialchars(getSettingValue('artist_name', 'Aurionix')) ?>"
                                           required>
                                    <small class="form-help">Your stage/artist name displayed throughout the site</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Site Title *</label>
                                    <input type="text" 
                                           name="site_title" 
                                           class="form-input" 
                                           placeholder="Aurionix - Official Music"
                                           value="<?= htmlspecialchars(getSettingValue('site_title', 'Aurionix - Official Music')) ?>"
                                           required>
                                    <small class="form-help">Appears in browser tabs and search results</small>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Site Description</label>
                                    <textarea name="site_description" 
                                              class="form-textarea" 
                                              placeholder="Official website of Aurionix - Electronic Music Artist"
                                              rows="3"><?= htmlspecialchars(getSettingValue('site_description', 'Official website of Aurionix - Electronic Music Artist')) ?></textarea>
                                    <small class="form-help">Brief description for search engines and social media</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" 
                                           name="contact_email" 
                                           class="form-input" 
                                           placeholder="contact@aurionix.com"
                                           value="<?= htmlspecialchars(getSettingValue('contact_email', '')) ?>">
                                    <small class="form-help">Primary contact email for inquiries</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" 
                                               name="maintenance_mode"
                                               value="1"
                                               <?= getSettingValue('maintenance_mode') ? 'checked' : '' ?>>
                                        <span class="checkbox-mark"></span>
                                        Maintenance Mode
                                    </label>
                                    <small class="form-help">Temporarily disable public access to the website</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Branding Tab -->
                    <div class="tab-content" data-tab="branding">
                        <div class="settings-section">
                            <h2>Branding & Appearance</h2>
                            <p>Customize your website's visual identity</p>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Hero Section Title</label>
                                    <input type="text" 
                                           name="hero_title" 
                                           class="form-input" 
                                           placeholder="THE WORLD'S LEADING BEAT MARKETPLACE"
                                           value="<?= htmlspecialchars(getSettingValue('hero_title', 'THE WORLD\'S LEADING BEAT MARKETPLACE')) ?>">
                                    <small class="form-help">Main heading on your homepage</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Hero Section Subtitle</label>
                                    <textarea name="hero_subtitle" 
                                              class="form-textarea" 
                                              placeholder="The brand of choice for the next generation of musicians and beat makers."
                                              rows="3"><?= htmlspecialchars(getSettingValue('hero_subtitle', 'The brand of choice for the next generation of musicians and beat makers.')) ?></textarea>
                                    <small class="form-help">Supporting text below the main heading</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Site Logo</label>
                                    <?php if (getSettingValue('site_logo')): ?>
                                        <div class="current-file">
                                            <img src="<?= getSettingValue('site_logo') ?>" 
                                                 alt="Current logo" 
                                                 class="logo-preview">
                                            <p>Current logo</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" 
                                           name="site_logo" 
                                           class="form-input" 
                                           accept="image/*">
                                    <small class="form-help">Upload a new logo (PNG, JPG, SVG recommended)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Favicon</label>
                                    <?php if (getSettingValue('site_favicon')): ?>
                                        <div class="current-file">
                                            <img src="<?= getSettingValue('site_favicon') ?>" 
                                                 alt="Current favicon" 
                                                 class="favicon-preview">
                                            <p>Current favicon</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" 
                                           name="site_favicon" 
                                           class="form-input" 
                                           accept="image/x-icon,image/png">
                                    <small class="form-help">Upload a favicon (16x16 or 32x32 ICO/PNG)</small>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Footer Text</label>
                                    <input type="text" 
                                           name="footer_text" 
                                           class="form-input" 
                                           placeholder="© 2024 Aurionix. All rights reserved."
                                           value="<?= htmlspecialchars(getSettingValue('footer_text', '© 2024 Aurionix. All rights reserved.')) ?>">
                                    <small class="form-help">Text displayed in the website footer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media Tab -->
                    <div class="tab-content" data-tab="social">
                        <div class="settings-section">
                            <h2>Social Media Links</h2>
                            <p>Connect your social media profiles</p>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">📘 Facebook</label>
                                    <input type="url" 
                                           name="social_facebook" 
                                           class="form-input" 
                                           placeholder="https://facebook.com/yourpage"
                                           value="<?= htmlspecialchars(getSettingValue('social_facebook', '')) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">🐦 Twitter / X</label>
                                    <input type="url" 
                                           name="social_twitter" 
                                           class="form-input" 
                                           placeholder="https://twitter.com/yourusername"
                                           value="<?= htmlspecialchars(getSettingValue('social_twitter', '')) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">📷 Instagram</label>
                                    <input type="url" 
                                           name="social_instagram" 
                                           class="form-input" 
                                           placeholder="https://instagram.com/yourusername"
                                           value="<?= htmlspecialchars(getSettingValue('social_instagram', '')) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">📺 YouTube</label>
                                    <input type="url" 
                                           name="social_youtube" 
                                           class="form-input" 
                                           placeholder="https://youtube.com/c/yourchannel"
                                           value="<?= htmlspecialchars(getSettingValue('social_youtube', '')) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">🎵 Spotify</label>
                                    <input type="url" 
                                           name="social_spotify" 
                                           class="form-input" 
                                           placeholder="https://open.spotify.com/artist/..."
                                           value="<?= htmlspecialchars(getSettingValue('social_spotify', '')) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">☁️ SoundCloud</label>
                                    <input type="url" 
                                           name="social_soundcloud" 
                                           class="form-input" 
                                           placeholder="https://soundcloud.com/yourusername"
                                           value="<?= htmlspecialchars(getSettingValue('social_soundcloud', '')) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Tab -->
                    <div class="tab-content" data-tab="seo">
                        <div class="settings-section">
                            <h2>SEO Settings</h2>
                            <p>Optimize your website for search engines</p>
                            
                            <div class="form-group">
                                <label class="form-label">Meta Keywords</label>
                                <textarea name="seo_keywords" 
                                          class="form-textarea" 
                                          placeholder="electronic music, beats, producer, aurionix, music artist"
                                          rows="3"><?= htmlspecialchars(getSettingValue('seo_keywords', '')) ?></textarea>
                                <small class="form-help">Comma-separated keywords that describe your music and brand</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analytics Tab -->
                    <div class="tab-content" data-tab="analytics">
                        <div class="settings-section">
                            <h2>Analytics & Tracking</h2>
                            <p>Track visitor behavior and performance</p>
                            
                            <div class="form-group">
                                <label class="form-label">Google Analytics ID</label>
                                <input type="text" 
                                       name="analytics_google" 
                                       class="form-input" 
                                       placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X"
                                       value="<?= htmlspecialchars(getSettingValue('analytics_google', '')) ?>">
                                <small class="form-help">Your Google Analytics tracking ID</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Facebook Pixel ID</label>
                                <input type="text" 
                                       name="analytics_facebook" 
                                       class="form-input" 
                                       placeholder="123456789012345"
                                       value="<?= htmlspecialchars(getSettingValue('analytics_facebook', '')) ?>">
                                <small class="form-help">Your Facebook Pixel ID for tracking conversions</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div class="tab-content" data-tab="advanced">
                        <div class="settings-section">
                            <h2>Advanced Settings</h2>
                            <p>Technical configuration options</p>
                            
                            <div class="alert alert-warning">
                                ⚠️ <strong>Warning:</strong> These settings affect core functionality. Only modify if you understand the implications.
                            </div>
                            
                            <div class="settings-info">
                                <h3>Database Information</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Database Host:</label>
                                        <span><?= DB_HOST ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Database Name:</label>
                                        <span><?= DB_NAME ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Site URL:</label>
                                        <span><?= SITE_URL ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Save Settings
                    </button>
                    <a href="../" target="_blank" class="btn btn-outline">
                        🌐 Preview Site
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        /* Settings page specific styles */
        .settings-form {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .settings-tabs {
            display: flex;
            flex-direction: column;
        }
        
        .tab-buttons {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            overflow-x: auto;
        }
        
        .tab-btn {
            padding: 20px 25px;
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 500;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .tab-btn.active {
            background: rgba(233, 69, 96, 0.1);
            color: #e94560;
            border-bottom: 2px solid #e94560;
        }
        
        .tab-content {
            display: none;
            padding: 30px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-section h2 {
            margin-bottom: 10px;
            color: white;
        }
        
        .settings-section p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .current-file {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .favicon-preview {
            width: 32px;
            height: 32px;
            margin-bottom: 10px;
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .settings-info {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 20px;
        }
        
        .settings-info h3 {
            margin-bottom: 15px;
            color: #e94560;
        }
        
        .info-grid {
            display: grid;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .info-item label {
            font-weight: 500;
            color: rgba(255,255,255,0.8);
        }
        
        .info-item span {
            color: #e94560;
            font-family: monospace;
        }
        
        .form-actions {
            padding: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.02);
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .tab-buttons {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid rgba(255,255,255,0.05);
            }
            
            .tab-btn.active {
                border-bottom: 1px solid #e94560;
                border-right: 3px solid #e94560;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
    
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.querySelector(`[data-tab="${targetTab}"].tab-content`).classList.add('active');
                });
            });
        });
        
        // Form validation
        document.querySelector('.settings-form').addEventListener('submit', function(e) {
            const artistName = document.querySelector('input[name="artist_name"]').value.trim();
            const siteTitle = document.querySelector('input[name="site_title"]').value.trim();
            
            if (!artistName || !siteTitle) {
                e.preventDefault();
                alert('Artist Name and Site Title are required fields.');
                return false;
            }
        });
        
        // URL validation for social media fields
        document.querySelectorAll('input[type="url"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !this.value.startsWith('http')) {
                    this.value = 'https://' + this.value;
                }
            });
        });
    </script>
</body>
</html>