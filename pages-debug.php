<?php
/**
 * AURIONIX PAGES DIAGNOSTIC
 * Save as: pages-debug.php in your root directory
 * DELETE after debugging!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Aurionix Pages Diagnostic</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #1a1a2e; color: white; }
        .section { border: 1px solid #444; padding: 15px; margin: 10px 0; border-radius: 8px; background: rgba(255,255,255,0.05); }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .file-test { margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 4px; }
        .btn { background: #e94560; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: rgba(233, 69, 96, 0.2); }
    </style>
</head>
<body>

<h1>🔍 Aurionix Pages Diagnostic</h1>

<?php
// Test all main pages
$pages_to_test = [
    // Main pages
    'index.php' => 'Homepage',
    'config.php' => 'Configuration',
    'install.php' => 'Installer',
    '.htaccess' => 'URL Rewriting Rules',
    
    // CSS/JS
    'css/style.css' => 'Main Stylesheet',
    'js/script.js' => 'Main JavaScript',
    
    // Admin pages
    'admin/login.php' => 'Admin Login',
    'admin/dashboard.php' => 'Admin Dashboard', 
    'admin/albums.php' => 'Albums Management',
    'admin/settings.php' => 'Settings Management',
    'admin/streaming-links.php' => 'Streaming Links',
    'admin/logout.php' => 'Admin Logout',
    
    // Admin includes
    'admin/includes/sidebar.php' => 'Admin Sidebar',
    'admin/includes/header.php' => 'Admin Header',
    'admin/admin-style.css' => 'Admin Stylesheet',
    
    // API files
    'api/get-stream.php' => 'Streaming API',
];

echo "<div class='section'>";
echo "<h2>📁 File Existence Check</h2>";
echo "<table>";
echo "<tr><th>File</th><th>Status</th><th>Size</th><th>Permissions</th><th>Action</th></tr>";

foreach($pages_to_test as $file => $description) {
    echo "<tr>";
    echo "<td><strong>$description</strong><br><small>$file</small></td>";
    
    if(file_exists($file)) {
        $size = filesize($file);
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file);
        
        echo "<td class='success'>✅ EXISTS</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td>$perms " . ($readable ? "(readable)" : "(not readable)") . "</td>";
        
        if(pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            echo "<td><a href='$file' target='_blank' class='btn'>Test Page</a></td>";
        } else {
            echo "<td><a href='$file' target='_blank' class='btn'>View File</a></td>";
        }
    } else {
        echo "<td class='error'>❌ MISSING</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "<td class='error'>File not found</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test admin directory structure
echo "<div class='section'>";
echo "<h2>🏗️ Directory Structure</h2>";
$directories = [
    'admin' => 'Admin Panel',
    'admin/includes' => 'Admin Includes',
    'css' => 'Stylesheets',
    'js' => 'JavaScript',
    'api' => 'API Scripts',
    'uploads' => 'Upload Directory',
    'uploads/albums' => 'Album Uploads',
    'uploads/settings' => 'Settings Uploads'
];

foreach($directories as $dir => $desc) {
    if(is_dir($dir)) {
        $writable = is_writable($dir);
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p class='success'>✅ <strong>$desc</strong> ($dir) - Permissions: $perms " . ($writable ? "(writable)" : "(not writable)") . "</p>";
    } else {
        echo "<p class='error'>❌ <strong>$desc</strong> ($dir) - Directory missing</p>";
        // Try to create it
        if(mkdir($dir, 0755, true)) {
            echo "<p class='success'>✅ Created directory: $dir</p>";
        } else {
            echo "<p class='error'>❌ Failed to create directory: $dir</p>";
        }
    }
}
echo "</div>";

// Test specific page functionality
echo "<div class='section'>";
echo "<h2>🧪 Page Functionality Tests</h2>";

// Test admin login page
echo "<h3>Admin Login Test</h3>";
if(file_exists('admin/login.php')) {
    try {
        ob_start();
        $old_get = $_GET;
        $old_post = $_POST;
        $_GET = $_POST = []; // Clear to avoid conflicts
        
        include 'admin/login.php';
        $output = ob_get_clean();
        
        $_GET = $old_get;
        $_POST = $old_post;
        
        if(strlen($output) > 100) {
            echo "<p class='success'>✅ Admin login page loads correctly (" . strlen($output) . " bytes output)</p>";
        } else {
            echo "<p class='warning'>⚠️ Admin login page loads but output seems short</p>";
        }
    } catch(Exception $e) {
        echo "<p class='error'>❌ Admin login error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Admin login page missing</p>";
}

// Test config loading
echo "<h3>Configuration Test</h3>";
try {
    if(defined('DB_HOST')) {
        echo "<p class='success'>✅ Config already loaded</p>";
    } else {
        require_once 'config.php';
        echo "<p class='success'>✅ Config loads successfully</p>";
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ Config loading error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test .htaccess rules
echo "<div class='section'>";
echo "<h2>🔄 URL Rewriting Test</h2>";
if(file_exists('.htaccess')) {
    $htaccess = file_get_contents('.htaccess');
    echo "<p class='success'>✅ .htaccess file exists (" . strlen($htaccess) . " bytes)</p>";
    
    // Check for key rules
    if(strpos($htaccess, 'RewriteEngine On') !== false) {
        echo "<p class='success'>✅ URL rewriting enabled</p>";
    } else {
        echo "<p class='error'>❌ URL rewriting not enabled</p>";
    }
    
    if(strpos($htaccess, 'admin/') !== false) {
        echo "<p class='success'>✅ Admin routes configured</p>";
    } else {
        echo "<p class='warning'>⚠️ Admin routes may not be configured</p>";
    }
} else {
    echo "<p class='warning'>⚠️ .htaccess file missing - creating basic one...</p>";
    
    $basic_htaccess = '# Basic .htaccess for Aurionix
RewriteEngine On

# Admin area protection
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Hide sensitive files
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css application/javascript text/html
</IfModule>
';
    
    if(file_put_contents('.htaccess', $basic_htaccess)) {
        echo "<p class='success'>✅ Created basic .htaccess file</p>";
    } else {
        echo "<p class='error'>❌ Could not create .htaccess file</p>";
    }
}
echo "</div>";

// Common issues and solutions
echo "<div class='section'>";
echo "<h2>🔧 Common Issues & Solutions</h2>";

if(!file_exists('admin/login.php')) {
    echo "<div class='error'>";
    echo "<h3>❌ Admin Pages Missing</h3>";
    echo "<p><strong>Problem:</strong> Admin directory or files are missing</p>";
    echo "<p><strong>Solution:</strong> Re-upload the admin folder with all its files</p>";
    echo "</div>";
}

if(!file_exists('css/style.css')) {
    echo "<div class='error'>";
    echo "<h3>❌ CSS Files Missing</h3>";
    echo "<p><strong>Problem:</strong> Stylesheets are missing</p>";
    echo "<p><strong>Solution:</strong> Re-upload the css folder</p>";
    echo "</div>";
}

if(!is_writable('uploads')) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Upload Directory Issues</h3>";
    echo "<p><strong>Problem:</strong> Cannot write to uploads directory</p>";
    echo "<p><strong>Solution:</strong> Set uploads directory permissions to 755</p>";
    echo "</div>";
}

echo "</div>";

// Quick fix tools
echo "<div class='section'>";
echo "<h2>🚀 Quick Fix Tools</h2>";

echo "<h3>Missing Files Creator</h3>";
echo "<p>Click these buttons to create missing essential files:</p>";

// Create missing directories button
if(!is_dir('uploads')) {
    echo "<a href='?create_uploads=1' class='btn'>📁 Create Uploads Directory</a>";
}

if(!is_dir('api')) {
    echo "<a href='?create_api=1' class='btn'>🔌 Create API Directory</a>";
}

echo "<br><br>";
echo "<h3>Test Links</h3>";
echo "<a href='index.php' class='btn' target='_blank'>🏠 Test Homepage</a>";
echo "<a href='admin/login.php' class='btn' target='_blank'>🔐 Test Admin Login</a>";
echo "<a href='install.php' class='btn' target='_blank'>🚀 Run Installer</a>";

// Handle quick fixes
if(isset($_GET['create_uploads'])) {
    if(mkdir('uploads', 0755, true) && mkdir('uploads/albums', 0755, true) && mkdir('uploads/settings', 0755, true)) {
        echo "<script>alert('Upload directories created successfully!'); window.location.href = 'pages-debug.php';</script>";
    }
}

if(isset($_GET['create_api'])) {
    if(mkdir('api', 0755, true)) {
        echo "<script>alert('API directory created successfully!'); window.location.href = 'pages-debug.php';</script>";
    }
}

echo "</div>";

// Server info
echo "<div class='section'>";
echo "<h2>🌐 Server Information</h2>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>URL Rewriting:</strong> " . (apache_get_modules && in_array('mod_rewrite', apache_get_modules()) ? 'Enabled' : 'Unknown') . "</p>";
echo "</div>";

?>

<div class='section'>
    <h2>📋 Action Plan</h2>
    <ol>
        <li><strong>Upload missing files</strong> - Re-upload any files marked as missing above</li>
        <li><strong>Fix permissions</strong> - Set directories to 755, files to 644</li>
        <li><strong>Test admin access</strong> - Try logging into admin panel</li>
        <li><strong>Check individual pages</strong> - Click the "Test Page" buttons above</li>
        <li><strong>Clear browser cache</strong> - Hard refresh (Ctrl+F5) on problem pages</li>
    </ol>
    
    <h3>🆘 Still Having Issues?</h3>
    <p>If pages still don't work:</p>
    <ul>
        <li>Check your hosting control panel error logs</li>
        <li>Ensure PHP 7.4+ is enabled</li>
        <li>Verify all files uploaded correctly</li>
        <li>Contact your hosting provider if needed</li>
    </ul>
</div>

<p style="color: #f44336; font-weight: bold;">⚠️ Remember to delete this pages-debug.php file when done!</p>

</body>
</html>