<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$configPath = __DIR__ . '/../config.php';

// Create settings table if it doesn't exist
$pdo = get_db();
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(20) DEFAULT 'text',
            category VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Default settings
$defaultSettings = [
    // Site Configuration
    'site_name' => SITE_NAME,
    'site_tagline' => 'Music Producer & Artist',
    'currency' => CURRENCY,
    
    // Homepage Content
    'hero_title' => 'THE WORLD\'S LEADING BEAT MARKETPLACE.',
    'hero_subtitle' => 'The brand of choice for the next generation of musicians and beat makers. Discover premium beats, connect with talented producers, and elevate your sound.',
    'featured_section_title' => 'Featured Albums',
    'featured_section_subtitle' => 'Handpicked beats from our top producers',
    
    // Artist Information
    'artist_name' => 'Aurionix',
    'artist_bio' => 'Innovative music producer creating cutting-edge beats and atmospheric soundscapes.',
    'artist_location' => 'Worldwide',
    'artist_genre' => 'Electronic, Hip Hop, Ambient',
    
    // Social Media
    'instagram_url' => '',
    'twitter_url' => '',
    'youtube_url' => '',
    'spotify_artist_url' => '',
    'apple_music_artist_url' => '',
    'soundcloud_url' => '',
    'tiktok_url' => '',
    'facebook_url' => '',
    
    // SEO Settings
    'meta_description' => 'Aurionix - Music producer creating innovative beats and atmospheric soundscapes. Stream and purchase exclusive tracks.',
    'meta_keywords' => 'Aurionix, music producer, beats, electronic music, hip hop, ambient, soundscapes',
    
    // Contact Information
    'contact_email' => '',
    'business_email' => '',
    'booking_email' => '',
    
    // Payment Settings
    'stripe_public_key' => STRIPE_PUBLIC_KEY,
    'stripe_secret_key' => STRIPE_SECRET_KEY,
    'paypal_client_id' => PAYPAL_CLIENT_ID,
    'paypal_secret' => PAYPAL_SECRET,
    
    // Theme Settings
    'theme_primary_color' => '#6366f1',
    'theme_secondary_color' => '#8b5cf6',
    'theme_accent_color' => '#06b6d4',
    'enable_dark_mode' => '1',
    'custom_css' => '',
    
    // Features
    'enable_downloads' => '1',
    'enable_streaming' => '1',
    'enable_user_registration' => '1',
    'enable_comments' => '0',
    'enable_favorites' => '1',
    'maintenance_mode' => '0',
    
    // Analytics
    'google_analytics_id' => '',
    'facebook_pixel_id' => '',
    
    // Legal
    'privacy_policy_url' => '',
    'terms_of_service_url' => '',
    'copyright_text' => '© ' . date('Y') . ' Aurionix. All rights reserved.',
];

// Load current settings
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Handle error
}

// Merge with defaults
$settings = array_merge($defaultSettings, $currentSettings);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $errors = [];
    
    try {
        $pdo->beginTransaction();
        
        // Update config file settings
        $configUpdates = [
            'SITE_NAME' => $_POST['site_name'] ?? $settings['site_name'],
            'CURRENCY' => $_POST['currency'] ?? $settings['currency'],
            'STRIPE_PUBLIC_KEY' => $_POST['stripe_public_key'] ?? $settings['stripe_public_key'],
            'STRIPE_SECRET_KEY' => $_POST['stripe_secret_key'] ?? $settings['stripe_secret_key'],
            'PAYPAL_CLIENT_ID' => $_POST['paypal_client_id'] ?? $settings['paypal_client_id'],
            'PAYPAL_SECRET' => $_POST['paypal_secret'] ?? $settings['paypal_secret'],
        ];
        
        // Read and update config file
        $configContent = file_get_contents($configPath);
        foreach ($configUpdates as $key => $value) {
            $pattern = "/define\('\s*" . preg_quote($key, '/') . "\s*',\s*'(.*?)'\s*\);/";
            $replacement = "define('" . $key . "', '" . addslashes($value) . "');";
            $configContent = preg_replace($pattern, $replacement, $configContent);
        }
        file_put_contents($configPath, $configContent);
        
        // Update database settings
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
        
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $defaultSettings)) {
                $stmt->execute([$key, $value]);
                $settings[$key] = $value;
            }
        }
        
        $pdo->commit();
        $message = 'Settings saved successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error saving settings: ' . $e->getMessage();
        $messageType = 'error';
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings - Aurionix Admin</title>
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
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="settings.php" class="active">Settings</a></li>
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
      <h1>Settings</h1>
      <p class="page-subtitle">Customize your website appearance, content, and functionality</p>
    </div>

    <!-- Message Display -->
    <?php if ($message): ?>
      <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <span><?= $messageType === 'success' ? '✅' : '❌'; ?></span>
        <span><?= htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="settings.php" class="admin-form">
      <!-- Site Configuration -->
      <div class="form-section">
        <h3 class="form-section-title">🏠 Site Configuration</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="site_name">Site Name</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']); ?>" required />
          </div>
          
          <div class="form-field">
            <label for="site_tagline">Site Tagline</label>
            <input type="text" id="site_tagline" name="site_tagline" value="<?= htmlspecialchars($settings['site_tagline']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="currency">Currency</label>
            <select id="currency" name="currency">
              <option value="USD" <?= $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
              <option value="EUR" <?= $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
              <option value="GBP" <?= $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
              <option value="CAD" <?= $settings['currency'] === 'CAD' ? 'selected' : ''; ?>>CAD (C$)</option>
              <option value="AUD" <?= $settings['currency'] === 'AUD' ? 'selected' : ''; ?>>AUD (A$)</option>
            </select>
          </div>

          <div class="form-field">
            <label for="copyright_text">Copyright Text</label>
            <input type="text" id="copyright_text" name="copyright_text" value="<?= htmlspecialchars($settings['copyright_text']); ?>" />
          </div>
        </div>
      </div>

      <!-- Homepage Content -->
      <div class="form-section">
        <h3 class="form-section-title">🎨 Homepage Content</h3>
        
        <div class="form-field">
          <label for="hero_title">Hero Section Title</label>
          <input type="text" id="hero_title" name="hero_title" value="<?= htmlspecialchars($settings['hero_title']); ?>" maxlength="200" />
          <small style="color: var(--admin-text-muted);">Main headline that appears on your homepage</small>
        </div>
        
        <div class="form-field">
          <label for="hero_subtitle">Hero Section Subtitle</label>
          <textarea id="hero_subtitle" name="hero_subtitle" style="height: 80px;" maxlength="500"><?= htmlspecialchars($settings['hero_subtitle']); ?></textarea>
          <small style="color: var(--admin-text-muted);">Supporting text below the main headline</small>
        </div>
        
        <div class="form-grid">
          <div class="form-field">
            <label for="featured_section_title">Featured Section Title</label>
            <input type="text" id="featured_section_title" name="featured_section_title" value="<?= htmlspecialchars($settings['featured_section_title']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="featured_section_subtitle">Featured Section Subtitle</label>
            <input type="text" id="featured_section_subtitle" name="featured_section_subtitle" value="<?= htmlspecialchars($settings['featured_section_subtitle']); ?>" />
          </div>
        </div>
      </div>

      <!-- Artist Information -->
      <div class="form-section">
        <h3 class="form-section-title">🎤 Artist Information</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="artist_name">Artist Name</label>
            <input type="text" id="artist_name" name="artist_name" value="<?= htmlspecialchars($settings['artist_name']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="artist_location">Location</label>
            <input type="text" id="artist_location" name="artist_location" value="<?= htmlspecialchars($settings['artist_location']); ?>" placeholder="e.g., Los Angeles, CA" />
          </div>
          
          <div class="form-field" style="grid-column: 1 / -1;">
            <label for="artist_genre">Primary Genres</label>
            <input type="text" id="artist_genre" name="artist_genre" value="<?= htmlspecialchars($settings['artist_genre']); ?>" placeholder="e.g., Electronic, Hip Hop, Ambient" />
          </div>
        </div>
        
        <div class="form-field">
          <label for="artist_bio">Artist Biography</label>
          <textarea id="artist_bio" name="artist_bio" style="height: 120px;" maxlength="1000"><?= htmlspecialchars($settings['artist_bio']); ?></textarea>
          <small style="color: var(--admin-text-muted);">Tell your story - this will appear on your about page</small>
        </div>
      </div>

      <!-- Social Media -->
      <div class="form-section">
        <h3 class="form-section-title">📱 Social Media & Streaming</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="instagram_url">
              <span style="color: #E4405F;">📷</span> Instagram
            </label>
            <input type="url" id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url']); ?>" placeholder="https://instagram.com/yourhandle" />
          </div>
          
          <div class="form-field">
            <label for="twitter_url">
              <span style="color: #1DA1F2;">🐦</span> Twitter/X
            </label>
            <input type="url" id="twitter_url" name="twitter_url" value="<?= htmlspecialchars($settings['twitter_url']); ?>" placeholder="https://twitter.com/yourhandle" />
          </div>
          
          <div class="form-field">
            <label for="youtube_url">
              <span style="color: #FF0000;">📺</span> YouTube
            </label>
            <input type="url" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url']); ?>" placeholder="https://youtube.com/c/yourchannel" />
          </div>
          
          <div class="form-field">
            <label for="tiktok_url">
              <span style="color: #000000;">🎵</span> TikTok
            </label>
            <input type="url" id="tiktok_url" name="tiktok_url" value="<?= htmlspecialchars($settings['tiktok_url']); ?>" placeholder="https://tiktok.com/@yourhandle" />
          </div>
          
          <div class="form-field">
            <label for="spotify_artist_url">
              <span style="color: #1DB954;">🎶</span> Spotify Artist
            </label>
            <input type="url" id="spotify_artist_url" name="spotify_artist_url" value="<?= htmlspecialchars($settings['spotify_artist_url']); ?>" placeholder="https://open.spotify.com/artist/..." />
          </div>
          
          <div class="form-field">
            <label for="apple_music_artist_url">
              <span style="color: #FA57C1;">🎵</span> Apple Music Artist
            </label>
            <input type="url" id="apple_music_artist_url" name="apple_music_artist_url" value="<?= htmlspecialchars($settings['apple_music_artist_url']); ?>" placeholder="https://music.apple.com/artist/..." />
          </div>
          
          <div class="form-field">
            <label for="soundcloud_url">
              <span style="color: #FF5500;">☁️</span> SoundCloud
            </label>
            <input type="url" id="soundcloud_url" name="soundcloud_url" value="<?= htmlspecialchars($settings['soundcloud_url']); ?>" placeholder="https://soundcloud.com/yourprofile" />
          </div>
          
          <div class="form-field">
            <label for="facebook_url">
              <span style="color: #1877F2;">📘</span> Facebook
            </label>
            <input type="url" id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url']); ?>" placeholder="https://facebook.com/yourpage" />
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="form-section">
        <h3 class="form-section-title">📧 Contact Information</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="contact_email">General Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email']); ?>" placeholder="contact@yoursite.com" />
          </div>
          
          <div class="form-field">
            <label for="business_email">Business Inquiries</label>
            <input type="email" id="business_email" name="business_email" value="<?= htmlspecialchars($settings['business_email']); ?>" placeholder="business@yoursite.com" />
          </div>
          
          <div class="form-field">
            <label for="booking_email">Booking/Collaborations</label>
            <input type="email" id="booking_email" name="booking_email" value="<?= htmlspecialchars($settings['booking_email']); ?>" placeholder="booking@yoursite.com" />
          </div>
        </div>
      </div>

      <!-- SEO Settings -->
      <div class="form-section">
        <h3 class="form-section-title">🔍 SEO & Meta Tags</h3>
        <div class="form-field">
          <label for="meta_description">Meta Description</label>
          <textarea id="meta_description" name="meta_description" style="height: 80px;" maxlength="160"><?= htmlspecialchars($settings['meta_description']); ?></textarea>
          <small style="color: var(--admin-text-muted);">Brief description for search engines (max 160 characters)</small>
        </div>
        
        <div class="form-field">
          <label for="meta_keywords">Meta Keywords</label>
          <input type="text" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($settings['meta_keywords']); ?>" placeholder="keyword1, keyword2, keyword3" />
          <small style="color: var(--admin-text-muted);">Comma-separated keywords for SEO</small>
        </div>
      </div>

      <!-- Theme Customization -->
      <div class="form-section">
        <h3 class="form-section-title">🎨 Theme & Appearance</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="theme_primary_color">Primary Color</label>
            <input type="color" id="theme_primary_color" name="theme_primary_color" value="<?= htmlspecialchars($settings['theme_primary_color']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="theme_secondary_color">Secondary Color</label>
            <input type="color" id="theme_secondary_color" name="theme_secondary_color" value="<?= htmlspecialchars($settings['theme_secondary_color']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="theme_accent_color">Accent Color</label>
            <input type="color" id="theme_accent_color" name="theme_accent_color" value="<?= htmlspecialchars($settings['theme_accent_color']); ?>" />
          </div>
        </div>
        
        <div class="form-field">
          <label for="custom_css">Custom CSS</label>
          <textarea id="custom_css" name="custom_css" style="height: 120px; font-family: monospace;" placeholder="/* Add your custom CSS here */"><?= htmlspecialchars($settings['custom_css']); ?></textarea>
          <small style="color: var(--admin-text-muted);">Advanced: Add custom CSS to override default styles</small>
        </div>
      </div>

      <!-- Feature Settings -->
      <div class="form-section">
        <h3 class="form-section-title">⚡ Features & Functionality</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
          
          <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="enable_downloads" value="1" <?= $settings['enable_downloads'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>Enable Downloads</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Allow users to download purchased tracks</div>
              </div>
            </label>
          </div>
          
          <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="enable_streaming" value="1" <?= $settings['enable_streaming'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>Enable Streaming</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Allow preview streaming of tracks</div>
              </div>
            </label>
          </div>
          
          <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="enable_user_registration" value="1" <?= $settings['enable_user_registration'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>User Registration</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Allow new users to create accounts</div>
              </div>
            </label>
          </div>
          
          <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="enable_favorites" value="1" <?= $settings['enable_favorites'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>Favorites System</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Let users save favorite tracks</div>
              </div>
            </label>
          </div>
          
          <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="enable_comments" value="1" <?= $settings['enable_comments'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong>Comments System</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Allow comments on tracks and albums</div>
              </div>
            </label>
          </div>
          
          <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: var(--admin-border-radius); border: 1px solid rgba(239, 68, 68, 0.2);">
            <label style="display: flex; align-items: center; gap: 1rem;">
              <div class="toggle">
                <input type="checkbox" name="maintenance_mode" value="1" <?= $settings['maintenance_mode'] ? 'checked' : ''; ?> />
                <span class="toggle-slider"></span>
              </div>
              <div>
                <strong style="color: var(--admin-error);">Maintenance Mode</strong>
                <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Temporarily disable public access</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Payment Settings -->
      <div class="form-section">
        <h3 class="form-section-title">💳 Payment Configuration</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="stripe_public_key">Stripe Public Key</label>
            <input type="text" id="stripe_public_key" name="stripe_public_key" value="<?= htmlspecialchars($settings['stripe_public_key']); ?>" placeholder="pk_live_..." />
          </div>
          
          <div class="form-field">
            <label for="stripe_secret_key">Stripe Secret Key</label>
            <input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?= htmlspecialchars($settings['stripe_secret_key']); ?>" placeholder="sk_live_..." />
          </div>
          
          <div class="form-field">
            <label for="paypal_client_id">PayPal Client ID</label>
            <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?= htmlspecialchars($settings['paypal_client_id']); ?>" />
          </div>
          
          <div class="form-field">
            <label for="paypal_secret">PayPal Secret</label>
            <input type="password" id="paypal_secret" name="paypal_secret" value="<?= htmlspecialchars($settings['paypal_secret']); ?>" />
          </div>
        </div>
        <small style="color: var(--admin-text-muted); margin-top: 1rem; display: block;">
          💡 <strong>Tip:</strong> Use test keys during development, then switch to live keys for production
        </small>
      </div>

      <!-- Analytics -->
      <div class="form-section">
        <h3 class="form-section-title">📊 Analytics & Tracking</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="google_analytics_id">Google Analytics ID</label>
            <input type="text" id="google_analytics_id" name="google_analytics_id" value="<?= htmlspecialchars($settings['google_analytics_id']); ?>" placeholder="G-XXXXXXXXXX" />
          </div>
          
          <div class="form-field">
            <label for="facebook_pixel_id">Facebook Pixel ID</label>
            <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" value="<?= htmlspecialchars($settings['facebook_pixel_id']); ?>" placeholder="1234567890123456" />
          </div>
        </div>
      </div>

      <!-- Legal -->
      <div class="form-section">
        <h3 class="form-section-title">⚖️ Legal Pages</h3>
        <div class="form-grid">
          <div class="form-field">
            <label for="privacy_policy_url">Privacy Policy URL</label>
            <input type="url" id="privacy_policy_url" name="privacy_policy_url" value="<?= htmlspecialchars($settings['privacy_policy_url']); ?>" placeholder="https://yoursite.com/privacy" />
          </div>
          
          <div class="form-field">
            <label for="terms_of_service_url">Terms of Service URL</label>
            <input type="url" id="terms_of_service_url" name="terms_of_service_url" value="<?= htmlspecialchars($settings['terms_of_service_url']); ?>" placeholder="https://yoursite.com/terms" />
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="btn-group">
        <button type="submit" class="btn btn--primary btn--lg">
          💾 Save All Settings
        </button>
        <button type="button" onclick="previewChanges()" class="btn btn--secondary btn--lg">
          👁️ Preview Changes
        </button>
        <button type="button" onclick="resetToDefaults()" class="btn btn--warning btn--lg">
          🔄 Reset to Defaults
        </button>
      </div>
    </form>
  </main>

  <script>
    // Character counters
    document.addEventListener('DOMContentLoaded', function() {
      const textareas = document.querySelectorAll('textarea[maxlength]');
      textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.style.color = 'var(--admin-text-muted)';
        counter.style.fontSize = '0.75rem';
        counter.style.marginTop = '0.25rem';
        counter.style.display = 'block';
        
        const updateCounter = () => {
          const remaining = maxLength - textarea.value.length;
          counter.textContent = `${remaining} characters remaining`;
          counter.style.color = remaining < 20 ? 'var(--admin-error)' : 'var(--admin-text-muted)';
        };
        
        textarea.addEventListener('input', updateCounter);
        textarea.parentNode.appendChild(counter);
        updateCounter();
      });
    });

    // Color picker preview
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
      input.addEventListener('change', function() {
        // Could add live preview functionality here
        console.log(`Color changed: ${this.name} = ${this.value}`);
      });
    });

    // Preview changes
    function previewChanges() {
      const form = document.querySelector('form');
      const formData = new FormData(form);
      
      // Open preview in new tab
      window.open('/', '_blank');
      
      // Could implement live preview functionality
      alert('Preview will open in a new tab. Note: Some changes require saving to take effect.');
    }

    // Reset to defaults
    function resetToDefaults() {
      if (confirm('Are you sure you want to reset all settings to their default values? This cannot be undone.')) {
        // Reset form to default values
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea, select');
        
        // This would need to be implemented with the actual default values
        alert('Reset functionality would be implemented here with proper default values.');
      }
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const requiredFields = ['site_name'];
      let valid = true;
      
      requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
          alert(`Please fill in the ${fieldName.replace('_', ' ')} field.`);
          valid = false;
          field.focus();
          return false;
        }
      });
      
      if (!valid) {
        e.preventDefault();
      }
    });

    // Auto-save draft (optional)
    let saveTimeout;
    const formInputs = document.querySelectorAll('input, textarea, select');
    
    formInputs.forEach(input => {
      input.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        // Visual indicator that changes are pending
        document.querySelector('.btn--primary').textContent = '💾 Save Changes (Unsaved)';
        document.querySelector('.btn--primary').style.background = 'var(--admin-gradient-warning)';
        
        saveTimeout = setTimeout(() => {
          // Could implement auto-save functionality here
        }, 5000);
      });
    });

    // Reset button text after save
    document.querySelector('form').addEventListener('submit', function() {
      document.querySelector('.btn--primary').textContent = '💾 Saving...';
      document.querySelector('.btn--primary').disabled = true;
    });
  </script>
</body>
</html>