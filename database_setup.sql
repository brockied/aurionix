-- File: database_setup.sql
-- Run this SQL in your MySQL database (via phpMyAdmin, command line, etc.)

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS aurionix_music CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aurionix_music;

-- Table to store Spotify access tokens
CREATE TABLE IF NOT EXISTS spotify_tokens (
    id INT PRIMARY KEY,
    token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table to store albums
CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spotify_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(500) NOT NULL,
    release_date DATE,
    total_tracks INT,
    cover_image TEXT,
    spotify_url TEXT,
    album_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_spotify_id (spotify_id),
    INDEX idx_release_date (release_date),
    INDEX idx_album_type (album_type)
);

-- Table to store individual tracks (optional - for future use)
CREATE TABLE IF NOT EXISTS tracks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT,
    spotify_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(500) NOT NULL,
    track_number INT,
    duration_ms INT,
    preview_url TEXT,
    spotify_url TEXT,
    explicit BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    INDEX idx_album_id (album_id),
    INDEX idx_track_number (track_number)
);

-- Table to log update attempts
CREATE TABLE IF NOT EXISTS update_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') NOT NULL,
    album_count INT DEFAULT 0,
    error_message TEXT,
    
    INDEX idx_updated_at (updated_at),
    INDEX idx_status (status)
);

-- Insert a test entry to verify setup
INSERT IGNORE INTO update_log (status, album_count, error_message) 
VALUES ('success', 0, 'Database setup completed');

-- Show tables to confirm setup
SHOW TABLES;

-- Show table structures
DESCRIBE spotify_tokens;
DESCRIBE albums;
DESCRIBE tracks;
DESCRIBE update_log;