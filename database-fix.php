<?php
/**
 * DATABASE FIX SCRIPT
 * Save as database-fix.php in your root directory
 * Run it once, then DELETE it for security
 */

require_once 'config.php';

echo "<h1>Database Fix Script</h1>";

try {
    // Add missing columns to tracks table if they don't exist
    $columns_to_add = [
        'audio_file' => "ALTER TABLE tracks ADD COLUMN audio_file VARCHAR(500) AFTER track_number",
        'file_size' => "ALTER TABLE tracks ADD COLUMN file_size INT DEFAULT 0 AFTER audio_file"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM tracks LIKE ?");
        $stmt->execute([$column]);
        
        if ($stmt->rowCount() == 0) {
            echo "<p>Adding column '$column' to tracks table...</p>";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Added column '$column'</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Column '$column' already exists</p>";
        }
    }
    
    // Fix any image paths that might be broken
    echo "<h2>Fixing Image Paths</h2>";
    
    // Get all albums with cover images
    $stmt = $pdo->query("SELECT id, title, cover_image FROM albums WHERE cover_image IS NOT NULL AND cover_image != ''");
    $albums = $stmt->fetchAll();
    
    foreach ($albums as $album) {
        $old_path = $album['cover_image'];
        $new_path = $old_path;
        
        // Remove leading slashes
        $new_path = ltrim($new_path, '/');
        
        // Ensure it starts with uploads/
        if (!str_starts_with($new_path, 'uploads/')) {
            $new_path = 'uploads/albums/' . basename($new_path);
        }
        
        if ($old_path !== $new_path) {
            $stmt = $pdo->prepare("UPDATE albums SET cover_image = ? WHERE id = ?");
            $stmt->execute([$new_path, $album['id']]);
            echo "<p style='color: orange;'>🔧 Fixed path for '{$album['title']}': '$old_path' → '$new_path'</p>";
        } else {
            echo "<p style='color: green;'>✅ Path OK for '{$album['title']}'</p>";
        }
    }
    
    // Create upload directories if they don't exist
    echo "<h2>Checking Upload Directories</h2>";
    
    $dirs = ['uploads', 'uploads/albums', 'uploads/tracks', 'uploads/settings'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>✅ Created directory: $dir</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create directory: $dir</p>";
            }
        } else {
            $writable = is_writable($dir);
            echo "<p style='color: " . ($writable ? 'green' : 'red') . ";'>" . ($writable ? '✅' : '❌') . " Directory exists and is " . ($writable ? 'writable' : 'not writable') . ": $dir</p>";
        }
    }
    
    // Create .htaccess for uploads security
    $htaccess_path = 'uploads/.htaccess';
    if (!file_exists($htaccess_path)) {
        $htaccess_content = "# Protect uploads directory
Options -Indexes
<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)\$\">
    Order allow,deny
    Deny from all
</Files>
";
        if (file_put_contents($htaccess_path, $htaccess_content)) {
            echo "<p style='color: green;'>✅ Created security .htaccess in uploads directory</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Security .htaccess already exists</p>";
    }
    
    echo "<h2 style='color: green;'>✅ Database Fix Complete!</h2>";
    echo "<p><strong>Now delete this file (database-fix.php) for security!</strong></p>";
    echo "<p><a href='admin/albums.php' style='background: #e94560; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Albums Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>