<?php
/*
 * Global audio player. This player persists across pages using
 * localStorage. When a user clicks on a track play button,
 * JavaScript can populate the player with the appropriate audio
 * source and metadata and store that state. The script
 * /assets/js/player.js manages the playback controls, progress and
 * track switching.
 */
?>
<div id="global-player" class="player" style="position:fixed;bottom:0;left:0;width:100%;z-index:999;">
  <div class="container player__controls">
    <div class="player__track-info">
      <img id="player-cover" src="/assets/images/default-cover.png" alt="Cover art" class="player__cover" />
      <div class="player__meta">
        <span id="player-title" class="player__title">Select a track to play</span>
        <span id="player-artist" class="player__artist"></span>
      </div>
    </div>
    <div class="player__buttons">
      <button id="player-prev" class="player__btn" aria-label="Previous track" type="button">⏮</button>
      <button id="player-play" class="player__btn player__btn--play" aria-label="Play/Pause" type="button">▶</button>
      <button id="player-next" class="player__btn" aria-label="Next track" type="button">⏭</button>
    </div>
    <div class="player__progress">
      <div class="player__progress-bar">
        <div id="player-progress-fill" class="player__progress-fill" style="width: 0%;"></div>
      </div>
    </div>
    <audio id="audio-element" preload="none"></audio>
  </div>
</div>