<?php
// File: /api/cron_update.php
// Run this file every hour via cron job to automatically update albums

require_once 'config.php';
require_once 'spotify_api.php';

echo "Starting Spotify album update...\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";

try {
    $spotify = new SpotifyAPI($pdo, SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET, SPOTIFY_ARTIST_ID);
    $success = $spotify->updateAlbums();
    
    if ($success) {
        echo "✓ Albums updated successfully!\n";
        
        // Get album count
        $albums = $spotify->getAlbums();
        echo "✓ Total albums in database: " . count($albums) . "\n";
        
        // Show latest albums
        echo "Latest albums:\n";
        foreach (array_slice($albums, 0, 3) as $album) {
            echo "  - {$album['name']} ({$album['release_date']})\n";
        }
        
    } else {
        echo "✗ Failed to update albums\n";
        
        // Check last update status
        $lastUpdate = $spotify->getLastUpdate();
        if ($lastUpdate) {
            echo "Last update attempt: {$lastUpdate['updated_at']} - Status: {$lastUpdate['status']}\n";
            if ($lastUpdate['error_message']) {
                echo "Error: {$lastUpdate['error_message']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Update completed.\n";
echo "---\n";

// Optional: Send email notification on failure
// if (!$success) {
//     mail('your-email@domain.com', 'Spotify Update Failed', 'Album update failed at ' . date('Y-m-d H:i:s'));
// }
?>