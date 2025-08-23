<?php
/**
 * Database Migration Script
 * Run this ONCE to fix foreign key constraints for proper deletion
 * 
 * This script should be run from your web browser: /migrate.php
 * After running successfully, you can delete this file.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow this to run if you're an admin
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    die('Access denied. Admin login required.');
}

$pdo = get_db();
$errors = [];
$success = [];

try {
    echo "<h2>Database Migration - Fixing Foreign Key Constraints</h2>";
    echo "<p>Starting migration...</p>";
    
    // Drop existing foreign key constraints
    echo "<p>Step 1: Dropping existing foreign key constraints...</p>";
    
    try {
        // Get existing foreign keys
        $fkQuery = "
            SELECT CONSTRAINT_NAME, TABLE_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_SCHEMA = ? 
            AND REFERENCED_TABLE_NAME IN ('albums', 'tracks', 'orders')
            AND TABLE_NAME IN ('tracks', 'order_items', 'views')
        ";
        $fkStmt = $pdo->prepare($fkQuery);
        $fkStmt->execute([DB_NAME]);
        $foreignKeys = $fkStmt->fetchAll();
        
        foreach ($foreignKeys as $fk) {
            try {
                $dropSql = "ALTER TABLE {$fk['TABLE_NAME']} DROP FOREIGN KEY {$fk['CONSTRAINT_NAME']}";
                $pdo->exec($dropSql);
                echo "<p>✓ Dropped foreign key: {$fk['CONSTRAINT_NAME']} from {$fk['TABLE_NAME']}</p>";
            } catch (Exception $e) {
                echo "<p>⚠ Could not drop {$fk['CONSTRAINT_NAME']}: {$e->getMessage()}</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>⚠ Could not get foreign keys: {$e->getMessage()}</p>";
    }
    
    // Step 2: Add proper foreign key constraints
    echo "<p>Step 2: Adding proper foreign key constraints...</p>";
    
    // Tracks table - allow cascade delete when album is deleted
    try {
        $pdo->exec("ALTER TABLE tracks ADD CONSTRAINT fk_tracks_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: tracks -> albums (CASCADE)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Tracks foreign key: {$e->getMessage()}</p>";
    }
    
    // Order items table - set track_id to NULL when track is deleted (preserve order history)
    try {
        $pdo->exec("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE SET NULL ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: order_items -> tracks (SET NULL)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Order items track foreign key: {$e->getMessage()}</p>";
    }
    
    // Order items table - cascade delete when order is deleted
    try {
        $pdo->exec("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: order_items -> orders (CASCADE)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Order items order foreign key: {$e->getMessage()}</p>";
    }
    
    // Views table - cascade delete when track is deleted
    try {
        $pdo->exec("ALTER TABLE views ADD CONSTRAINT fk_views_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: views -> tracks (CASCADE)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Views track foreign key: {$e->getMessage()}</p>";
    }
    
    // Views table - cascade delete when album is deleted
    try {
        $pdo->exec("ALTER TABLE views ADD CONSTRAINT fk_views_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: views -> albums (CASCADE)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Views album foreign key: {$e->getMessage()}</p>";
    }
    
    // Orders table - set user_id to NULL when user is deleted (preserve order history)
    try {
        $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");
        echo "<p>✓ Added foreign key: orders -> users (SET NULL)</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Orders user foreign key: {$e->getMessage()}</p>";
    }
    
    // Step 3: Update table columns to allow NULL values where needed
    echo "<p>Step 3: Updating table columns...</p>";
    
    try {
        $pdo->exec("ALTER TABLE order_items MODIFY track_id INT NULL");
        echo "<p>✓ Updated order_items.track_id to allow NULL</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Update order_items.track_id: {$e->getMessage()}</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE orders MODIFY user_id INT NULL");
        echo "<p>✓ Updated orders.user_id to allow NULL</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Update orders.user_id: {$e->getMessage()}</p>";
    }
    
    // Step 4: Add any missing columns that might be needed
    echo "<p>Step 4: Adding missing columns...</p>";
    
    // Check if albums table needs additional columns
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM albums LIKE 'release_date'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE albums ADD COLUMN release_date DATE DEFAULT NULL");
            $pdo->exec("ALTER TABLE albums ADD COLUMN genre VARCHAR(100) DEFAULT ''");
            $pdo->exec("ALTER TABLE albums ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
            echo "<p>✓ Added missing columns to albums table</p>";
        }
    } catch (Exception $e) {
        echo "<p>⚠ Albums columns: {$e->getMessage()}</p>";
    }
    
    // Check if tracks table needs additional columns
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM tracks LIKE 'genre'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE tracks ADD COLUMN genre VARCHAR(100) DEFAULT ''");
            $pdo->exec("ALTER TABLE tracks ADD COLUMN duration VARCHAR(10) DEFAULT ''");
            $pdo->exec("ALTER TABLE tracks ADD COLUMN bpm INT DEFAULT 0");
            $pdo->exec("ALTER TABLE tracks ADD COLUMN key_signature VARCHAR(10) DEFAULT ''");
            $pdo->exec("ALTER TABLE tracks ADD COLUMN explicit TINYINT(1) DEFAULT 0");
            echo "<p>✓ Added missing columns to tracks table</p>";
        }
    } catch (Exception $e) {
        echo "<p>⚠ Tracks columns: {$e->getMessage()}</p>";
    }
    
    echo "<h3 style='color: green;'>✅ Migration completed successfully!</h3>";
    echo "<p><strong>You can now delete albums and tracks properly.</strong></p>";
    echo "<p><a href='/admin/dashboard.php'>Go to Admin Dashboard</a></p>";
    echo "<hr>";
    echo "<p><em>You can safely delete this migrate.php file now.</em></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Migration failed!</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>