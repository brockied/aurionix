<?php
// File: /api/config.php
// Database configuration - UPDATE THESE WITH YOUR DETAILS

define('DB_HOST', 'localhost');
define('DB_USER', 'aurioni1_davbro');   
define('DB_PASS', '2RkVAsArXpociLJB');   
define('DB_NAME', 'aurioni1_aurionix');  // Fixed the typo (was urioni1_aurionix)

// Spotify configuration
define('SPOTIFY_CLIENT_ID', 'ed6da01599894925b96d21ba8584d723');
define('SPOTIFY_CLIENT_SECRET', '7a28fcf3de3b44ef97113e02ff8b4703');
define('SPOTIFY_ARTIST_ID', '21oBxlODSuqsevw1VqiViY');

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>