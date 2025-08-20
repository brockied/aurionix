/**
 * AURIONIX WEBSITE JAVASCRIPT
 * Create a folder called 'js' in your root directory
 * Place this file as: js/script.js
 */

class AurionixPlayer {
    constructor() {
        this.currentTrack = null;
        this.isPlaying = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 0.5;
        this.playlist = [];
        this.currentIndex = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializePlayer();
        this.loadPlaylist();
    }
    
    bindEvents() {
        // Navigation
        document.addEventListener('DOMContentLoaded', () => {
            this.initNavigation();
            this.initSearch();
            this.initAlbumFilters();
            this.initMusicPlayer();
            this.initStreamingLinks();
        });
        
        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                mobileToggle.classList.toggle('active');
            });
        }
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Window resize handler
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        // Scroll handler for navbar
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });
    }
    
    initNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });
    }
    
    initSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.querySelector('.search-btn');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
            
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleSearch(e.target.value);
                }
            });
        }
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.handleSearch(searchInput.value);
            });
        }
    }
    
    handleSearch(query) {
        if (query.length < 2) return;
        
        const albums = document.querySelectorAll('.album-card');
        const searchTerm = query.toLowerCase();
        
        albums.forEach(album => {
            const title = album.querySelector('h3').textContent.toLowerCase();
            const isMatch = title.includes(searchTerm);
            
            album.style.display = isMatch ? 'block' : 'none';
            
            if (isMatch) {
                album.style.animation = 'fadeIn 0.3s ease';
            }
        });
    }
    
    initAlbumFilters() {
        const filterSelect = document.getElementById('albumFilter');
        
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.filterAlbums(e.target.value);
            });
        }
    }
    
    filterAlbums(genre) {
        const albums = document.querySelectorAll('.album-card');
        
        albums.forEach(album => {
            if (genre === 'all') {
                album.style.display = 'block';
            } else {
                // In a real implementation, you'd have genre data attributes
                // For now, we'll show all albums
                album.style.display = 'block';
            }
        });
    }
    
    initMusicPlayer() {
        // Play buttons in album cards
        document.querySelectorAll('.play-btn, .main-play').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const albumCard = btn.closest('.album-card');
                if (albumCard) {
                    this.playAlbum(albumCard);
                }
            });
        });
        
        // Chart item play buttons
        document.querySelectorAll('.chart-item').forEach(item => {
            item.addEventListener('click', () => {
                this.playChartItem(item);
            });
        });
        
        // Player controls
        this.initPlayerControls();
    }
    
    initPlayerControls() {
        // Main player controls
        const playPauseBtn = document.getElementById('playPauseBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const shuffleBtn = document.getElementById('shuffleBtn');
        const repeatBtn = document.getElementById('repeatBtn');
        
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => {
                this.togglePlayPause();
            });
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.previousTrack();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.nextTrack();
            });
        }
        
        if (shuffleBtn) {
            shuffleBtn.addEventListener('click', () => {
                this.toggleShuffle();
            });
        }
        
        if (repeatBtn) {
            repeatBtn.addEventListener('click', () => {
                this.toggleRepeat();
            });
        }
        
        // Now playing bar controls
        const nowPlayingControls = document.querySelectorAll('.now-playing .player-controls button');
        nowPlayingControls.forEach((btn, index) => {
            btn.addEventListener('click', () => {
                switch(index) {
                    case 0: this.toggleShuffle(); break;
                    case 1: this.previousTrack(); break;
                    case 2: this.togglePlayPause(); break;
                    case 3: this.nextTrack(); break;
                    case 4: this.toggleRepeat(); break;
                }
            });
        });
        
        // Progress bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.addEventListener('click', (e) => {
                this.seekTo(e);
            });
        }
        
        // Volume control
        const volumeSlider = document.querySelector('.volume-slider input');
        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                this.setVolume(e.target.value / 100);
            });
        }
        
        // Close player modal
        const closePlayer = document.querySelector('.close-player');
        if (closePlayer) {
            closePlayer.addEventListener('click', () => {
                this.closePlayerModal();
            });
        }
    }
    
    initStreamingLinks() {
        document.querySelectorAll('.streaming-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleStreamingClick(btn);
            });
        });
    }
    
    handleStreamingClick(btn) {
        const platform = btn.className.match(/streaming-(\w+)/)?.[1];
        const albumCard = btn.closest('.album-card');
        const albumId = albumCard?.dataset.albumId;
        
        if (platform && albumId) {
            // Track click for analytics
            this.trackStreamingClick(platform, albumId);
            
            // Handle different platforms
            switch(platform) {
                case 'spotify':
                    this.openSpotify(btn.href, albumId);
                    break;
                case 'youtube':
                    this.openYouTube(btn.href, albumId);
                    break;
                default:
                    window.open(btn.href, '_blank');
            }
        }
    }
    
    openSpotify(url, albumId) {
        // Try to open in Spotify app first, then web
        const spotifyApp = url.replace('https://open.spotify.com', 'spotify');
        window.location.href = spotifyApp;
        
        // Fallback to web after 2 seconds
        setTimeout(() => {
            window.open(url, '_blank');
        }, 2000);
    }
    
    openYouTube(url, albumId) {
        // Load YouTube embed in player modal
        this.loadYouTubeEmbed(url, albumId);
    }
    
    loadYouTubeEmbed(url, albumId) {
        const embedContainer = document.getElementById('embedContainer');
        const videoId = this.extractYouTubeId(url);
        
        if (embedContainer && videoId) {
            embedContainer.innerHTML = `
                <iframe width="100%" height="315" 
                        src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            `;
            
            this.showPlayerModal();
        }
    }
    
    extractYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }
    
    playAlbum(albumCard) {
        const albumTitle = albumCard.querySelector('h3').textContent;
        const albumCover = albumCard.querySelector('img').src;
        const albumId = albumCard.dataset.albumId;
        
        this.currentTrack = {
            title: albumTitle,
            artist: window.SITE_CONFIG?.artistName || 'Aurionix',
            cover: albumCover,
            albumId: albumId
        };
        
        this.updatePlayerUI();
        this.showNowPlaying();
        this.showPlayerModal();
        
        // Load streaming embed if available
        this.loadBestAvailableStream(albumId);
    }
    
    playChartItem(item) {
        const title = item.querySelector('h4').textContent;
        const artist = item.querySelector('p').textContent;
        const cover = item.querySelector('img').src;
        
        this.currentTrack = {
            title: title,
            artist: artist,
            cover: cover
        };
        
        this.updatePlayerUI();
        this.showNowPlaying();
    }
    
    loadBestAvailableStream(albumId) {
        // In a real implementation, this would fetch the best available stream
        // based on user's country and platform preferences
        fetch(`/api/get-stream.php?album_id=${albumId}&country=${window.SITE_CONFIG?.userCountry || 'US'}`)
            .then(response => response.json())
            .then(data => {
                if (data.embed_code) {
                    document.getElementById('embedContainer').innerHTML = data.embed_code;
                }
            })
            .catch(error => {
                console.error('Error loading stream:', error);
            });
    }
    
    updatePlayerUI() {
        if (!this.currentTrack) return;
        
        // Update player modal
        const playerTitle = document.getElementById('playerTitle');
        const playerArtist = document.getElementById('playerArtist');
        const playerArtwork = document.getElementById('playerArtwork');
        
        if (playerTitle) playerTitle.textContent = this.currentTrack.title;
        if (playerArtist) playerArtist.textContent = this.currentTrack.artist;
        if (playerArtwork) playerArtwork.src = this.currentTrack.cover;
        
        // Update now playing bar
        const npTitle = document.querySelector('.np-info h4');
        const npArtist = document.querySelector('.np-info p');
        const npCover = document.querySelector('.np-cover img');
        
        if (npTitle) npTitle.textContent = this.currentTrack.title;
        if (npArtist) npArtist.textContent = this.currentTrack.artist;
        if (npCover) npCover.src = this.currentTrack.cover;
    }
    
    showNowPlaying() {
        const nowPlaying = document.querySelector('.now-playing');
        if (nowPlaying) {
            nowPlaying.classList.add('active');
        }
    }
    
    hideNowPlaying() {
        const nowPlaying = document.querySelector('.now-playing');
        if (nowPlaying) {
            nowPlaying.classList.remove('active');
        }
    }
    
    showPlayerModal() {
        const modal = document.getElementById('musicPlayer');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closePlayerModal() {
        const modal = document.getElementById('musicPlayer');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
    
    togglePlayPause() {
        this.isPlaying = !this.isPlaying;
        
        const playBtns = document.querySelectorAll('.play-main, .play-pause-main');
        playBtns.forEach(btn => {
            btn.textContent = this.isPlaying ? '⏸️' : '▶️';
        });
        
        if (this.isPlaying) {
            this.startProgressUpdate();
        } else {
            this.stopProgressUpdate();
        }
    }
    
    previousTrack() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.loadTrack(this.playlist[this.currentIndex]);
        }
    }
    
    nextTrack() {
        if (this.currentIndex < this.playlist.length - 1) {
            this.currentIndex++;
            this.loadTrack(this.playlist[this.currentIndex]);
        }
    }
    
    toggleShuffle() {
        // Implement shuffle logic
        const shuffleBtn = document.getElementById('shuffleBtn');
        if (shuffleBtn) {
            shuffleBtn.classList.toggle('active');
        }
    }
    
    toggleRepeat() {
        // Implement repeat logic
        const repeatBtn = document.getElementById('repeatBtn');
        if (repeatBtn) {
            repeatBtn.classList.toggle('active');
        }
    }
    
    seekTo(e) {
        const progressBar = e.currentTarget;
        const rect = progressBar.getBoundingClientRect();
        const percentage = (e.clientX - rect.left) / rect.width;
        
        this.currentTime = percentage * this.duration;
        this.updateProgress();
    }
    
    setVolume(volume) {
        this.volume = volume;
        // Update actual audio volume if audio element exists
    }
    
    startProgressUpdate() {
        this.progressInterval = setInterval(() => {
            if (this.isPlaying && this.duration > 0) {
                this.currentTime += 1;
                if (this.currentTime >= this.duration) {
                    this.nextTrack();
                }
                this.updateProgress();
            }
        }, 1000);
    }
    
    stopProgressUpdate() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
    }
    
    updateProgress() {
        const percentage = (this.currentTime / this.duration) * 100;
        
        document.querySelectorAll('.progress-fill').forEach(fill => {
            fill.style.width = `${percentage}%`;
        });
        
        document.querySelectorAll('.time-current').forEach(time => {
            time.textContent = this.formatTime(this.currentTime);
        });
        
        document.querySelectorAll('.time-total').forEach(time => {
            time.textContent = this.formatTime(this.duration);
        });
    }
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    loadPlaylist() {
        // Load playlist from albums on page
        const albumCards = document.querySelectorAll('.album-card');
        this.playlist = Array.from(albumCards).map((card, index) => ({
            id: index,
            title: card.querySelector('h3').textContent,
            artist: window.SITE_CONFIG?.artistName || 'Aurionix',
            cover: card.querySelector('img').src,
            albumId: card.dataset.albumId
        }));
    }
    
    loadTrack(track) {
        this.currentTrack = track;
        this.updatePlayerUI();
        // Load actual audio/embed for the track
    }
    
    initializePlayer() {
        // Set default duration for demo
        this.duration = 225; // 3:45
        this.updateProgress();
    }
    
    handleResize() {
        // Handle responsive behavior
        const width = window.innerWidth;
        
        if (width <= 768) {
            this.optimizeForMobile();
        } else {
            this.optimizeForDesktop();
        }
    }
    
    optimizeForMobile() {
        // Mobile-specific optimizations
        const searchBox = document.querySelector('.search-box');
        if (searchBox) {
            searchBox.style.display = 'none';
        }
    }
    
    optimizeForDesktop() {
        // Desktop-specific optimizations
        const searchBox = document.querySelector('.search-box');
        if (searchBox) {
            searchBox.style.display = 'flex';
        }
    }
    
    handleScroll() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(10, 10, 10, 0.98)';
        } else {
            navbar.style.background = 'rgba(10, 10, 10, 0.95)';
        }
    }
    
    trackStreamingClick(platform, albumId) {
        // Send analytics data
        fetch('/api/track-click.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                platform: platform,
                album_id: albumId,
                country: window.SITE_CONFIG?.userCountry || 'unknown',
                timestamp: new Date().toISOString()
            })
        }).catch(error => {
            console.error('Analytics error:', error);
        });
    }
}

// Animation utilities
class AnimationUtils {
    static fadeIn(element, duration = 300) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        let start = performance.now();
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            element.style.opacity = progress.toString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    static slideDown(element, duration = 300) {
        element.style.height = '0';
        element.style.overflow = 'hidden';
        element.style.display = 'block';
        
        const targetHeight = element.scrollHeight;
        let start = performance.now();
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            element.style.height = `${targetHeight * progress}px`;
            
            if (progress >= 1) {
                element.style.height = 'auto';
                element.style.overflow = 'visible';
            } else {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Initialize the application
const aurionixPlayer = new AurionixPlayer();

// Add some CSS animations dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
    
    .pulse {
        animation: pulse 2s infinite;
    }
`;
document.head.appendChild(style);

// Service Worker registration (optional, for PWA features)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}