<?php
// File: /api/spotify_api.php
// Spotify API handler class

class SpotifyAPI {
    private $pdo;
    private $clientId;
    private $clientSecret;
    private $artistId;
    
    public function __construct($pdo, $clientId, $clientSecret, $artistId) {
        $this->pdo = $pdo;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->artistId = $artistId;
    }
    
    // Get access token from database or fetch new one
    private function getAccessToken() {
        // Check if we have a valid token in database
        $stmt = $this->pdo->prepare("SELECT token, expires_at FROM spotify_tokens WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && strtotime($result['expires_at']) > time()) {
            return $result['token'];
        }
        
        // Get new token from Spotify
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['access_token'])) {
                $token = $data['access_token'];
                $expiresAt = date('Y-m-d H:i:s', time() + $data['expires_in']);
                
                // Store token in database
                $stmt = $this->pdo->prepare("
                    INSERT INTO spotify_tokens (id, token, expires_at) 
                    VALUES (1, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                ");
                $stmt->execute([$token, $expiresAt, $token, $expiresAt]);
                
                return $token;
            }
        }
        
        return null;
    }
    
    // Fetch albums from Spotify and store in database
    public function updateAlbums() {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/artists/{$this->artistId}/albums?include_groups=album,single&market=US&limit=20");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['items'])) {
                // Clear existing albums
                $this->pdo->prepare("DELETE FROM albums")->execute();
                
                // Insert new albums
                $stmt = $this->pdo->prepare("
                    INSERT INTO albums (spotify_id, name, release_date, total_tracks, cover_image, spotify_url, album_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['items'] as $album) {
                    $coverImage = isset($album['images'][0]['url']) ? $album['images'][0]['url'] : null;
                    
                    $stmt->execute([
                        $album['id'],
                        $album['name'],
                        $album['release_date'],
                        $album['total_tracks'],
                        $coverImage,
                        $album['external_urls']['spotify'],
                        $album['album_type']
                    ]);
                }
                
                // Log successful update
                $logStmt = $this->pdo->prepare("
                    INSERT INTO update_log (updated_at, status, album_count) 
                    VALUES (NOW(), 'success', ?)
                ");
                $logStmt->execute([count($data['items'])]);
                
                return true;
            }
        }
        
        // Log failed update
        $logStmt = $this->pdo->prepare("
            INSERT INTO update_log (updated_at, status, error_message) 
            VALUES (NOW(), 'failed', ?)
        ");
        $logStmt->execute(['HTTP Code: ' . $httpCode]);
        
        return false;
    }
    
    // Get albums from database
    public function getAlbums() {
        $stmt = $this->pdo->prepare("
            SELECT spotify_id, name, release_date, total_tracks, cover_image, spotify_url, album_type 
            FROM albums 
            ORDER BY release_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get update status
    public function getLastUpdate() {
        $stmt = $this->pdo->prepare("
            SELECT updated_at, status, album_count, error_message 
            FROM update_log 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Fetch the artist's top tracks directly from the Spotify API.
     *
     * Spotify exposes a "top-tracks" endpoint which returns the most
     * popular tracks for a given artist in a specified market.  This method
     * retrieves those tracks using the same access token mechanism as the
     * album calls.  It does not store the results in the database; instead
     * it returns the decoded API response so the caller can decide how to
     * present the data.  If the request fails or no tracks are returned
     * the method returns an empty array.
     *
     * @param string $market The market to use when requesting top tracks (e.g. "US").
     * @return array List of track objects or an empty array on failure.
     */
    public function getTopTracks($market = 'US') {
        $token = $this->getAccessToken();
        if (!$token) {
            return [];
        }
        $url = "https://api.spotify.com/v1/artists/{$this->artistId}/top-tracks?market=" . urlencode($market);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['tracks'] ?? [];
        }
        return [];
    }
}
?>