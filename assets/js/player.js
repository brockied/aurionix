/*
 * Enhanced Player script for Aurionix
 * 
 * Modern audio player with advanced features including:
 * - Persistent playback across pages
 * - Volume control
 * - Shuffle and repeat modes
 * - Playlist management
 * - Keyboard shortcuts
 * - Progress seeking
 * - Fade in/out effects
 */

(function() {
  'use strict';

  // DOM elements
  const audioEl = document.getElementById('audio-element');
  const playerEl = document.getElementById('global-player');
  const coverEl = document.getElementById('player-cover');
  const titleEl = document.getElementById('player-title');
  const artistEl = document.getElementById('player-artist');
  const playBtn = document.getElementById('player-play');
  const prevBtn = document.getElementById('player-prev');
  const nextBtn = document.getElementById('player-next');
  const progressBar = document.querySelector('.player__progress-bar');
  const progressFill = document.getElementById('player-progress-fill');

  // Player state
  let currentTrack = null;
  let playlist = [];
  let currentIndex = 0;
  let isPlaying = false;
  let isShuffled = false;
  let repeatMode = 'none'; // 'none', 'one', 'all'
  let volume = 1.0;
  let isMuted = false;
  let isDragging = false;

  // Storage keys
  const STORAGE_KEY = 'aurionix_player_state';
  const PLAYLIST_KEY = 'aurionix_playlist';

  // Initialize player
  function init() {
    loadPlayerState();
    setupEventListeners();
    setupKeyboardShortcuts();
    addVolumeControl();
    addShuffleRepeatControls();
    hidePlayerInitially();
  }

  // Hide player initially if no track is loaded
  function hidePlayerInitially() {
    if (!currentTrack) {
      playerEl.style.transform = 'translateY(100%)';
      playerEl.style.opacity = '0';
    }
  }

  // Show player with animation
  function showPlayer() {
    playerEl.style.transform = 'translateY(0)';
    playerEl.style.opacity = '1';
    playerEl.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
  }

  // Load player state from localStorage
  function loadPlayerState() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      const playlistStored = localStorage.getItem(PLAYLIST_KEY);
      
      if (stored) {
        const data = JSON.parse(stored);
        if (data.currentTrack && data.currentTrack.src) {
          currentTrack = data.currentTrack;
          currentIndex = data.currentIndex || 0;
          volume = data.volume || 1.0;
          isMuted = data.isMuted || false;
          repeatMode = data.repeatMode || 'none';
          isShuffled = data.isShuffled || false;
          
          setTrack(currentTrack, false);
          setVolume(volume);
          if (isMuted) toggleMute();
        }
      }

      if (playlistStored) {
        playlist = JSON.parse(playlistStored);
      }
    } catch (e) {
      console.error('Failed to parse stored player state', e);
    }
  }

  // Save player state to localStorage
  function savePlayerState() {
    try {
      const state = {
        currentTrack,
        currentIndex,
        volume,
        isMuted,
        repeatMode,
        isShuffled
      };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      localStorage.setItem(PLAYLIST_KEY, JSON.stringify(playlist));
    } catch (e) {
      console.error('Failed to save player state', e);
    }
  }

  // Public function to set and play a track
  window.setTrack = function(track, autoPlay = true) {
    if (!track || !track.src) return;
    
    currentTrack = track;
    
    // Add to playlist if not already there
    const existingIndex = playlist.findIndex(t => t.src === track.src);
    if (existingIndex === -1) {
      playlist.unshift(track);
      currentIndex = 0;
    } else {
      currentIndex = existingIndex;
    }

    // Update UI
    updatePlayerUI();
    
    // Setup audio
    audioEl.src = track.src;
    audioEl.load();
    
    if (autoPlay) {
      playTrack();
    }

    showPlayer();
    savePlayerState();
  };

  // Update player UI elements
  function updatePlayerUI() {
    if (!currentTrack) return;
    
    coverEl.src = currentTrack.cover || '/assets/images/default-cover.png';
    coverEl.alt = `Cover for ${currentTrack.title}`;
    titleEl.textContent = currentTrack.title || 'Unknown Title';
    artistEl.textContent = currentTrack.artist || 'Unknown Artist';
    
    updateButtonStates();
  }

  // Play current track
  function playTrack() {
    if (!audioEl.src) return;
    
    const playPromise = audioEl.play();
    if (playPromise) {
      playPromise
        .then(() => {
          isPlaying = true;
          updateButtonStates();
          fadeIn();
        })
        .catch(error => {
          console.log('Playback failed:', error);
          isPlaying = false;
          updateButtonStates();
        });
    }
  }

  // Pause current track
  function pauseTrack() {
    audioEl.pause();
    isPlaying = false;
    updateButtonStates();
  }

  // Toggle play/pause
  function togglePlay() {
    if (audioEl.paused) {
      playTrack();
    } else {
      pauseTrack();
    }
  }

  // Play next track
  function playNext() {
    if (playlist.length <= 1) return;

    if (isShuffled) {
      currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
      currentIndex = (currentIndex + 1) % playlist.length;
    }
    
    currentTrack = playlist[currentIndex];
    setTrack(currentTrack, true);
  }

  // Play previous track
  function playPrevious() {
    if (playlist.length <= 1) return;

    if (audioEl.currentTime > 3) {
      // If more than 3 seconds played, restart current track
      audioEl.currentTime = 0;
      return;
    }

    if (isShuffled) {
      currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
      currentIndex = currentIndex === 0 ? playlist.length - 1 : currentIndex - 1;
    }
    
    currentTrack = playlist[currentIndex];
    setTrack(currentTrack, true);
  }

  // Update button states
  function updateButtonStates() {
    playBtn.innerHTML = isPlaying ? '❚❚' : '▶';
    playBtn.setAttribute('aria-label', isPlaying ? 'Pause' : 'Play');
    
    // Update repeat button if exists
    const repeatBtn = document.getElementById('player-repeat');
    if (repeatBtn) {
      repeatBtn.className = `player__btn ${repeatMode !== 'none' ? 'active' : ''}`;
      repeatBtn.innerHTML = repeatMode === 'one' ? '🔂' : '🔁';
    }

    // Update shuffle button if exists
    const shuffleBtn = document.getElementById('player-shuffle');
    if (shuffleBtn) {
      shuffleBtn.className = `player__btn ${isShuffled ? 'active' : ''}`;
    }
  }

  // Update progress bar
  function updateProgress() {
    if (!audioEl.duration || isDragging) return;
    
    const percent = (audioEl.currentTime / audioEl.duration) * 100;
    progressFill.style.width = `${percent}%`;
    
    // Update time display if elements exist
    const currentTimeEl = document.getElementById('current-time');
    const totalTimeEl = document.getElementById('total-time');
    
    if (currentTimeEl) {
      currentTimeEl.textContent = formatTime(audioEl.currentTime);
    }
    if (totalTimeEl) {
      totalTimeEl.textContent = formatTime(audioEl.duration);
    }
  }

  // Format time for display
  function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  // Handle progress bar click/drag
  function handleProgressInteraction(e) {
    if (!audioEl.duration) return;
    
    const rect = progressBar.getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    const newTime = percent * audioEl.duration;
    
    audioEl.currentTime = Math.max(0, Math.min(newTime, audioEl.duration));
    updateProgress();
  }

  // Volume control
  function setVolume(newVolume) {
    volume = Math.max(0, Math.min(1, newVolume));
    audioEl.volume = isMuted ? 0 : volume;
    
    const volumeSlider = document.getElementById('volume-slider');
    if (volumeSlider) {
      volumeSlider.value = volume * 100;
    }
    
    updateVolumeIcon();
    savePlayerState();
  }

  // Toggle mute
  function toggleMute() {
    isMuted = !isMuted;
    audioEl.volume = isMuted ? 0 : volume;
    updateVolumeIcon();
    savePlayerState();
  }

  // Update volume icon
  function updateVolumeIcon() {
    const volumeBtn = document.getElementById('volume-btn');
    if (volumeBtn) {
      if (isMuted || volume === 0) {
        volumeBtn.innerHTML = '🔇';
      } else if (volume < 0.5) {
        volumeBtn.innerHTML = '🔉';
      } else {
        volumeBtn.innerHTML = '🔊';
      }
    }
  }

  // Toggle repeat mode
  function toggleRepeat() {
    const modes = ['none', 'all', 'one'];
    const currentModeIndex = modes.indexOf(repeatMode);
    repeatMode = modes[(currentModeIndex + 1) % modes.length];
    updateButtonStates();
    savePlayerState();
  }

  // Toggle shuffle
  function toggleShuffle() {
    isShuffled = !isShuffled;
    updateButtonStates();
    savePlayerState();
  }

  // Fade in effect
  function fadeIn() {
    if (audioEl.volume < volume && !isMuted) {
      audioEl.volume = Math.min(audioEl.volume + 0.1, volume);
      setTimeout(fadeIn, 50);
    }
  }

  // Fade out effect
  function fadeOut(callback) {
    if (audioEl.volume > 0.1) {
      audioEl.volume = Math.max(audioEl.volume - 0.1, 0);
      setTimeout(() => fadeOut(callback), 50);
    } else {
      audioEl.volume = 0;
      if (callback) callback();
    }
  }

  // Add volume control to player
  function addVolumeControl() {
    const volumeContainer = document.createElement('div');
    volumeContainer.className = 'player__volume';
    volumeContainer.innerHTML = `
      <button id="volume-btn" class="player__btn" aria-label="Toggle mute">🔊</button>
      <input id="volume-slider" type="range" min="0" max="100" value="100" class="volume-slider" aria-label="Volume">
    `;

    // Insert before progress section
    const progressSection = document.querySelector('.player__progress');
    progressSection.parentNode.insertBefore(volumeContainer, progressSection);

    // Event listeners
    document.getElementById('volume-btn').addEventListener('click', toggleMute);
    document.getElementById('volume-slider').addEventListener('input', (e) => {
      setVolume(e.target.value / 100);
    });
  }

  // Add shuffle and repeat controls
  function addShuffleRepeatControls() {
    const shuffleBtn = document.createElement('button');
    shuffleBtn.id = 'player-shuffle';
    shuffleBtn.className = 'player__btn';
    shuffleBtn.innerHTML = '🔀';
    shuffleBtn.setAttribute('aria-label', 'Toggle shuffle');
    shuffleBtn.addEventListener('click', toggleShuffle);

    const repeatBtn = document.createElement('button');
    repeatBtn.id = 'player-repeat';
    repeatBtn.className = 'player__btn';
    repeatBtn.innerHTML = '🔁';
    repeatBtn.setAttribute('aria-label', 'Toggle repeat');
    repeatBtn.addEventListener('click', toggleRepeat);

    // Insert controls
    const buttonsContainer = document.querySelector('.player__buttons');
    buttonsContainer.appendChild(shuffleBtn);
    buttonsContainer.appendChild(repeatBtn);
  }

  // Setup event listeners
  function setupEventListeners() {
    // Basic controls
    playBtn.addEventListener('click', togglePlay);
    prevBtn.addEventListener('click', playPrevious);
    nextBtn.addEventListener('click', playNext);

    // Audio events
    audioEl.addEventListener('timeupdate', updateProgress);
    audioEl.addEventListener('loadedmetadata', updateProgress);
    audioEl.addEventListener('play', () => {
      isPlaying = true;
      updateButtonStates();
    });
    audioEl.addEventListener('pause', () => {
      isPlaying = false;
      updateButtonStates();
    });
    audioEl.addEventListener('ended', handleTrackEnd);

    // Progress bar interaction
    progressBar.addEventListener('click', handleProgressInteraction);
    progressBar.addEventListener('mousedown', (e) => {
      isDragging = true;
      handleProgressInteraction(e);
    });
    
    document.addEventListener('mousemove', (e) => {
      if (isDragging) {
        handleProgressInteraction(e);
      }
    });
    
    document.addEventListener('mouseup', () => {
      isDragging = false;
    });

    // Touch events for mobile
    progressBar.addEventListener('touchstart', (e) => {
      isDragging = true;
      const touch = e.touches[0];
      const rect = progressBar.getBoundingClientRect();
      const percent = (touch.clientX - rect.left) / rect.width;
      const newTime = percent * audioEl.duration;
      if (audioEl.duration) {
        audioEl.currentTime = Math.max(0, Math.min(newTime, audioEl.duration));
      }
    });

    progressBar.addEventListener('touchmove', (e) => {
      e.preventDefault();
      if (isDragging) {
        const touch = e.touches[0];
        const rect = progressBar.getBoundingClientRect();
        const percent = (touch.clientX - rect.left) / rect.width;
        const newTime = percent * audioEl.duration;
        if (audioEl.duration) {
          audioEl.currentTime = Math.max(0, Math.min(newTime, audioEl.duration));
        }
      }
    });

    progressBar.addEventListener('touchend', () => {
      isDragging = false;
    });

    // Window events
    window.addEventListener('beforeunload', savePlayerState);
    
    // Visibility API for pause/resume
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && isPlaying) {
        // Optionally pause when tab is hidden
        // pauseTrack();
      }
    });
  }

  // Handle track end
  function handleTrackEnd() {
    switch (repeatMode) {
      case 'one':
        audioEl.currentTime = 0;
        playTrack();
        break;
      case 'all':
        playNext();
        break;
      default:
        if (currentIndex < playlist.length - 1) {
          playNext();
        } else {
          isPlaying = false;
          updateButtonStates();
        }
    }
  }

  // Setup keyboard shortcuts
  function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Don't trigger shortcuts when typing in inputs
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
      }

      switch (e.code) {
        case 'Space':
          e.preventDefault();
          togglePlay();
          break;
        case 'ArrowLeft':
          e.preventDefault();
          if (audioEl.currentTime > 5) {
            audioEl.currentTime -= 5;
          }
          break;
        case 'ArrowRight':
          e.preventDefault();
          if (audioEl.currentTime < audioEl.duration - 5) {
            audioEl.currentTime += 5;
          }
          break;
        case 'ArrowUp':
          e.preventDefault();
          setVolume(volume + 0.1);
          break;
        case 'ArrowDown':
          e.preventDefault();
          setVolume(volume - 0.1);
          break;
        case 'KeyM':
          e.preventDefault();
          toggleMute();
          break;
        case 'KeyN':
          e.preventDefault();
          playNext();
          break;
        case 'KeyP':
          e.preventDefault();
          playPrevious();
          break;
      }
    });
  }

  // Public API
  window.audioPlayer = {
    play: playTrack,
    pause: pauseTrack,
    toggle: togglePlay,
    next: playNext,
    previous: playPrevious,
    setVolume: setVolume,
    toggleMute: toggleMute,
    setRepeat: (mode) => {
      repeatMode = mode;
      updateButtonStates();
      savePlayerState();
    },
    setShuffle: (enabled) => {
      isShuffled = enabled;
      updateButtonStates();
      savePlayerState();
    },
    getCurrentTrack: () => currentTrack,
    getPlaylist: () => playlist,
    isPlaying: () => isPlaying
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();

// Additional CSS for new controls
const playerStyles = `
<style>
.player__volume {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 1rem;
}

.volume-slider {
  width: 80px;
  height: 4px;
  background: var(--colour-border);
  outline: none;
  border-radius: 2px;
  -webkit-appearance: none;
}

.volume-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  background: var(--colour-primary);
  border-radius: 50%;
  cursor: pointer;
}

.volume-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: var(--colour-primary);
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

.player__btn.active {
  color: var(--colour-primary);
  background: rgba(139, 92, 246, 0.1);
}

.player__progress-bar {
  cursor: pointer;
  position: relative;
}

.player__progress-bar:hover {
  height: 8px;
  margin: -2px 0;
}

@media (max-width: 768px) {
  .player__volume {
    margin: 0 0.5rem;
  }
  
  .volume-slider {
    width: 60px;
  }
  
  .player__buttons {
    gap: 0.5rem;
  }
  
  .player__btn {
    font-size: 1.2rem;
  }
}
</style>
`;

// Inject additional styles
document.head.insertAdjacentHTML('beforeend', playerStyles);