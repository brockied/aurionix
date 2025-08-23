/*
 * Player script for Aurionix
 *
 * This lightweight player manages playback of audio tracks across
 * multiple pages. It saves the current track to localStorage so
 * that the selected song persists as visitors navigate around the
 * site. To start playback of a track from anywhere on the site,
 * call the global function setTrack({title, artist, cover, src});
 */

(function() {
  const audioEl       = document.getElementById('audio-element');
  const coverEl       = document.getElementById('player-cover');
  const titleEl       = document.getElementById('player-title');
  const artistEl      = document.getElementById('player-artist');
  const playBtn       = document.getElementById('player-play');
  const prevBtn       = document.getElementById('player-prev');
  const nextBtn       = document.getElementById('player-next');
  const progressFill  = document.getElementById('player-progress-fill');

  let currentTrack = null;
  let isPlaying    = false;

  // Load last played track from storage
  const stored = localStorage.getItem('aurionix_current_track');
  if (stored) {
    try {
      const data = JSON.parse(stored);
      if (data.src) {
        setTrack(data, false);
      }
    } catch (e) {
      console.error('Failed to parse stored track', e);
    }
  }

  // Public function to set and play a track
  /**
   * Populate the global player with new track data and optionally start
   * playback. When swapping to a new audio source it is important to
   * reset the element by calling `load()` before playing; otherwise
   * certain browsers will ignore autoplay for dynamic src changes.
   *
   * @param {Object} track  Track metadata {title, artist, cover, src}
   * @param {Boolean} autoPlay Whether to immediately begin playback
   */
  window.setTrack = function(track, autoPlay = true) {
    if (!track || !track.src) return;
    currentTrack = track;
    // Persist selection across page loads
    localStorage.setItem('aurionix_current_track', JSON.stringify(track));
    // Update player UI elements
    audioEl.src = track.src;
    // Calling load() ensures the new source is properly initialised
    audioEl.load();
    coverEl.src = track.cover || '/assets/images/default-cover.png';
    titleEl.textContent = track.title || 'Unknown Title';
    artistEl.textContent = track.artist || '';
    if (autoPlay) {
      // Attempt to play; catch silently if autoplay is blocked
      audioEl.play().catch(() => {});
    }
    updateButtonState();
  };

  // Play/pause toggle
  function togglePlay() {
    if (!audioEl.src) return;
    if (audioEl.paused) {
      audioEl.play().catch(() => {});
      isPlaying = true;
    } else {
      audioEl.pause();
      isPlaying = false;
    }
    updateButtonState();
  }

  // Update play button icon
  function updateButtonState() {
    playBtn.textContent = audioEl.paused ? '▶' : '❚❚';
  }

  // Update progress bar
  function updateProgress() {
    if (!audioEl.duration) return;
    const percent = (audioEl.currentTime / audioEl.duration) * 100;
    progressFill.style.width = percent + '%';
  }

  // Event listeners
  playBtn.addEventListener('click', togglePlay);
  audioEl.addEventListener('timeupdate', updateProgress);
  audioEl.addEventListener('play', updateButtonState);
  audioEl.addEventListener('pause', updateButtonState);
  // Placeholder for prev/next actions
  prevBtn.addEventListener('click', () => {
    // Implementation of playlist navigation can be added here
  });
  nextBtn.addEventListener('click', () => {
    // Implementation of playlist navigation can be added here
  });
})();