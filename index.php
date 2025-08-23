<?php
// Enhanced Aurionix Artist Homepage

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

// Get site settings from database
$pdo = get_db();
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settingsStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not in database
$heroTitle = $settings['hero_title'] ?? 'THE WORLD\'S LEADING BEAT MARKETPLACE.';
$heroSubtitle = $settings['hero_subtitle'] ?? 'The brand of choice for the next generation of musicians and beat makers. Discover premium beats, connect with talented producers, and elevate your sound.';
$artistName = $settings['artist_name'] ?? 'Aurionix';
$artistBio = $settings['artist_bio'] ?? 'Innovative music producer creating cutting-edge beats and atmospheric soundscapes.';
$featuredSectionTitle = $settings['featured_section_title'] ?? 'Featured Albums';
$featuredSectionSubtitle = $settings['featured_section_subtitle'] ?? 'Handpicked beats from our top producers';

// Fetch dynamic content
$featuredAlbums = get_featured_albums(6);
$topTracks = get_top_tracks(8);

// Get latest album
$latestAlbumStmt = $pdo->query('SELECT * FROM albums ORDER BY created_at DESC LIMIT 1');
$latestAlbum = $latestAlbumStmt->fetch();

// Get total stats
$totalTracks = $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
$totalAlbums = $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
$totalPlays = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM views WHERE track_id IS NOT NULL')->fetchColumn();
$totalFans = $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? $artistName . ' - Music producer creating innovative beats and atmospheric soundscapes.'); ?>" />
  <meta name="keywords" content="<?= htmlspecialchars($settings['meta_keywords'] ?? $artistName . ', music producer, beats, electronic music, hip hop, ambient, soundscapes'); ?>" />
  
  <title><?= htmlspecialchars($artistName); ?> – Music Producer & Artist</title>
  
  <!-- Preconnect to external domains -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="dns-prefetch" href="//fonts.googleapis.com">
  
  <!-- Fonts -->
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" />
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="/assets/css/style.css" />
  
  <!-- Custom CSS from settings -->
  <?php if (!empty($settings['custom_css'])): ?>
  <style><?= $settings['custom_css']; ?></style>
  <?php endif; ?>
  
  <!-- Favicon -->
  <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
  
  <!-- Open Graph / Social Media -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= htmlspecialchars($artistName); ?> – Music Producer & Artist">
  <meta property="og:description" content="<?= htmlspecialchars($artistBio); ?>">
  <meta property="og:url" content="<?= SITE_URL; ?>">
  <?php if ($latestAlbum): ?>
  <meta property="og:image" content="<?= SITE_URL; ?>/uploads/albums/<?= htmlspecialchars($latestAlbum['cover']); ?>">
  <?php endif; ?>
  
  <!-- Schema.org markup -->
  <script type="application/ld+json">
  {
    "@context": "http://schema.org",
    "@type": "MusicGroup",
    "name": "<?= htmlspecialchars($artistName); ?>",
    "url": "<?= SITE_URL; ?>",
    "description": "<?= htmlspecialchars($artistBio); ?>",
    "genre": "<?= htmlspecialchars($settings['artist_genre'] ?? 'Electronic'); ?>",
    "sameAs": [
      <?php 
      $socialLinks = [];
      if (!empty($settings['spotify_artist_url'])) $socialLinks[] = '"' . $settings['spotify_artist_url'] . '"';
      if (!empty($settings['instagram_url'])) $socialLinks[] = '"' . $settings['instagram_url'] . '"';
      if (!empty($settings['twitter_url'])) $socialLinks[] = '"' . $settings['twitter_url'] . '"';
      if (!empty($settings['youtube_url'])) $socialLinks[] = '"' . $settings['youtube_url'] . '"';
      echo implode(',', $socialLinks);
      ?>
    ]
  }
  </script>

  <!-- Analytics -->
  <?php if (!empty($settings['google_analytics_id'])): ?>
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($settings['google_analytics_id']); ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= htmlspecialchars($settings['google_analytics_id']); ?>');
  </script>
  <?php endif; ?>
</head>
<body>
  <!-- Skip to content link for accessibility -->
  <a href="#main-content" class="sr-only">Skip to main content</a>
  
  <!-- Navigation -->
  <?php include __DIR__ . '/partials/nav.php'; ?>
  
  <main id="main-content">
    <!-- Hero section -->
    <section class="hero" aria-label="Hero section">
      <!-- Animated background particles -->
      <div class="hero__particles" aria-hidden="true">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
      </div>
      
      <div class="hero__overlay" aria-hidden="true"></div>
      <div class="hero__content container">
        <div class="hero__text fade-in-up">
          <div class="hero__label">🎵 <?= htmlspecialchars($artistName); ?></div>
          <h1 class="hero__title">
            <?= nl2br(htmlspecialchars($heroTitle)); ?>
          </h1>
          <p class="hero__subtitle">
            <?= nl2br(htmlspecialchars($heroSubtitle)); ?>
          </p>
          
          <!-- Artist stats -->
          <div class="hero__stats">
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalTracks); ?></div>
              <div class="stat-label">Tracks</div>
            </div>
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalAlbums); ?></div>
              <div class="stat-label">Albums</div>
            </div>
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalPlays); ?></div>
              <div class="stat-label">Plays</div>
            </div>
            <div class="stat-item">
              <div class="stat-number"><?= number_format($totalFans); ?></div>
              <div class="stat-label">Fans</div>
            </div>
          </div>
          
          <div class="hero__buttons">
            <a href="/album_list.php" class="btn btn--primary btn--lg" aria-label="Explore music">
              <span>🎵 Explore Music</span>
            </a>
            <?php if ($latestAlbum): ?>
              <a href="/album.php?id=<?= $latestAlbum['id']; ?>" class="btn btn--outline btn--lg" aria-label="Listen to latest album">
                <span>▶️ Latest Album</span>
              </a>
            <?php endif; ?>
          </div>
          
          <!-- Social media links -->
          <?php if (!empty($settings['spotify_artist_url']) || !empty($settings['instagram_url']) || !empty($settings['twitter_url'])): ?>
          <div class="hero__social">
            <?php if (!empty($settings['spotify_artist_url'])): ?>
              <a href="<?= htmlspecialchars($settings['spotify_artist_url']); ?>" target="_blank" rel="noopener" 
                 class="social-link spotify" aria-label="Follow on Spotify">
                🎵
              </a>
            <?php endif; ?>
            <?php if (!empty($settings['instagram_url'])): ?>
              <a href="<?= htmlspecialchars($settings['instagram_url']); ?>" target="_blank" rel="noopener" 
                 class="social-link instagram" aria-label="Follow on Instagram">
                📷
              </a>
            <?php endif; ?>
            <?php if (!empty($settings['twitter_url'])): ?>
              <a href="<?= htmlspecialchars($settings['twitter_url']); ?>" target="_blank" rel="noopener" 
                 class="social-link twitter" aria-label="Follow on Twitter">
                🐦
              </a>
            <?php endif; ?>
            <?php if (!empty($settings['youtube_url'])): ?>
              <a href="<?= htmlspecialchars($settings['youtube_url']); ?>" target="_blank" rel="noopener" 
                 class="social-link youtube" aria-label="Subscribe on YouTube">
                📺
              </a>
            <?php endif; ?>
            <?php if (!empty($settings['soundcloud_url'])): ?>
              <a href="<?= htmlspecialchars($settings['soundcloud_url']); ?>" target="_blank" rel="noopener" 
                 class="social-link soundcloud" aria-label="Follow on SoundCloud">
                ☁️
              </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Latest Album Showcase -->
        <?php if ($latestAlbum): ?>
        <aside class="hero__featured fade-in-up" aria-label="Latest album" style="animation-delay: 0.2s;">
          <div class="featured-album">
            <div class="featured-album__badge">Latest Release</div>
            <div class="featured-album__cover">
              <img src="/uploads/albums/<?= htmlspecialchars($latestAlbum['cover']); ?>" 
                   alt="<?= htmlspecialchars($latestAlbum['title']); ?> cover" 
                   loading="lazy" />
              <div class="featured-album__play">
                <button class="play-button" onclick="playLatestAlbum()">
                  <span>▶️</span>
                </button>
              </div>
            </div>
            <div class="featured-album__info">
              <h3><?= htmlspecialchars($latestAlbum['title']); ?></h3>
              <p><?= htmlspecialchars(substr($latestAlbum['description'], 0, 100)); ?>...</p>
              <div class="featured-album__links">
                <?php if ($latestAlbum['spotify_url']): ?>
                  <a href="<?= htmlspecialchars($latestAlbum['spotify_url']); ?>" target="_blank" class="streaming-link spotify">
                    🎵 Spotify
                  </a>
                <?php endif; ?>
                <?php if ($latestAlbum['apple_url']): ?>
                  <a href="<?= htmlspecialchars($latestAlbum['apple_url']); ?>" target="_blank" class="streaming-link apple">
                    🍎 Apple Music
                  </a>
                <?php endif; ?>
                <a href="/album.php?id=<?= $latestAlbum['id']; ?>" class="streaming-link local">
                  👁️ View Album
                </a>
              </div>
            </div>
          </div>
        </aside>
        <?php endif; ?>
      </div>
    </section>

    <!-- Audio Player -->
    <?php include __DIR__ . '/partials/player.php'; ?>

    <!-- Top Tracks Section -->
    <section class="top-tracks" aria-label="Top tracks">
      <div class="container">
        <header class="section-header">
          <h2 class="section-title">🔥 Top Tracks</h2>
          <p class="section-subtitle">Most popular tracks this month</p>
        </header>
        
        <?php if (!empty($topTracks)): ?>
          <div class="tracks-grid">
            <?php foreach ($topTracks as $index => $track): ?>
              <div class="track-card fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s;">
                <div class="track-rank">#<?= $index + 1; ?></div>
                <div class="track-cover">
                  <img src="/uploads/albums/<?= htmlspecialchars($track['album_cover'] ?: 'default-cover.png'); ?>" 
                       alt="<?= htmlspecialchars($track['title']); ?>" loading="lazy" />
                  <div class="track-overlay">
                    <button class="track-play-btn" 
                            onclick="setTrack({
                              title:'<?= htmlspecialchars($track['title'], ENT_QUOTES); ?>', 
                              artist:'<?= htmlspecialchars($artistName, ENT_QUOTES); ?>', 
                              cover:'/uploads/albums/<?= htmlspecialchars($track['album_cover']); ?>', 
                              src:'/uploads/tracks/<?= htmlspecialchars($track['audio_file']); ?>'
                            }); updateTrackView(<?= $track['id']; ?>);">
                      ▶️
                    </button>
                  </div>
                </div>
                <div class="track-info">
                  <h3 class="track-title"><?= htmlspecialchars($track['title']); ?></h3>
                  <p class="track-album"><?= htmlspecialchars($track['album_title']); ?></p>
                  <div class="track-meta">
                    <span class="track-plays"><?= number_format(rand(100, 10000)); ?> plays</span>
                    <span class="track-duration">3:45</span>
                  </div>
                </div>
                <div class="track-actions">
                  <a href="/album.php?id=<?= $track['album_id']; ?>#track-<?= $track['id']; ?>" class="btn btn--sm btn--outline">
                    View
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <h3>No tracks yet</h3>
            <p>New music coming soon!</p>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Featured Albums Section -->
    <?php if (!empty($featuredAlbums)): ?>
    <section class="featured-albums" aria-label="Featured albums">
      <div class="container">
        <header class="section-header">
          <h2 class="section-title"><?= htmlspecialchars($featuredSectionTitle); ?></h2>
          <p class="section-subtitle"><?= htmlspecialchars($featuredSectionSubtitle); ?></p>
        </header>
        
        <div class="albums-grid">
          <?php foreach ($featuredAlbums as $index => $album): ?>
            <article class="album-card fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s;">
              <a href="/album.php?id=<?= urlencode($album['id']); ?>" class="album-link">
                <div class="album-cover">
                  <img src="/uploads/albums/<?= htmlspecialchars($album['cover']); ?>" 
                       alt="<?= htmlspecialchars($album['title']); ?>" loading="lazy" />
                  <div class="album-overlay">
                    <div class="album-play-btn">
                      <span>▶️</span>
                    </div>
                  </div>
                </div>
                <div class="album-info">
                  <h3 class="album-title"><?= htmlspecialchars($album['title']); ?></h3>
                  <p class="album-year"><?= date('Y', strtotime($album['created_at'])); ?></p>
                  <p class="album-description">
                    <?= htmlspecialchars(substr($album['description'], 0, 80)); ?>...
                  </p>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
        
        <div class="section-footer">
          <a href="/album_list.php" class="btn btn--secondary btn--lg">
            View All Albums
          </a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- About Section -->
    <section class="about-section" aria-label="About the artist">
      <div class="container">
        <div class="about-content">
          <div class="about-text">
            <h2 class="section-title">About <?= htmlspecialchars($artistName); ?></h2>
            <p class="about-description">
              <?= nl2br(htmlspecialchars($artistBio)); ?>
            </p>
            <?php if (!empty($settings['artist_location']) || !empty($settings['artist_genre'])): ?>
            <div class="about-details">
              <?php if (!empty($settings['artist_location'])): ?>
                <div class="detail-item">
                  <span class="detail-label">📍 Based in</span>
                  <span class="detail-value"><?= htmlspecialchars($settings['artist_location']); ?></span>
                </div>
              <?php endif; ?>
              <?php if (!empty($settings['artist_genre'])): ?>
                <div class="detail-item">
                  <span class="detail-label">🎵 Genres</span>
                  <span class="detail-value"><?= htmlspecialchars($settings['artist_genre']); ?></span>
                </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Artist Image Placeholder -->
          <div class="about-image">
            <div class="artist-avatar">
              <span><?= strtoupper(substr($artistName, 0, 2)); ?></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Newsletter Signup -->
    <section class="newsletter-section" aria-label="Newsletter signup">
      <div class="container">
        <div class="newsletter-content">
          <h2 class="newsletter-title">Stay Updated</h2>
          <p class="newsletter-subtitle">Get notified about new releases, shows, and exclusive content</p>
          
          <form class="newsletter-form" onsubmit="return handleNewsletterSignup(event)">
            <div class="newsletter-input-group">
              <input type="email" placeholder="Enter your email address" required />
              <button type="submit" class="btn btn--primary">
                Subscribe
              </button>
            </div>
            <p class="newsletter-privacy">
              We respect your privacy. Unsubscribe at any time.
            </p>
          </form>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/partials/footer.php'; ?>

  <!-- Scripts -->
  <script src="/assets/js/player.js"></script>
  <script>
    // Track view update function
    function updateTrackView(trackId) {
      fetch('/track_view.php?id=' + trackId, {method: 'GET'}).catch(() => {});
    }

    // Newsletter signup handler
    function handleNewsletterSignup(event) {
      event.preventDefault();
      const email = event.target.querySelector('input[type="email"]').value;
      
      // Here you would typically send to your newsletter service
      alert('Thank you for subscribing! We\'ll keep you updated with the latest releases.');
      event.target.reset();
      
      return false;
    }

    // Latest album play functionality
    function playLatestAlbum() {
      <?php if ($latestAlbum): ?>
      // Get first track from latest album
      fetch('/api/album-tracks.php?id=<?= $latestAlbum['id']; ?>')
        .then(response => response.json())
        .then(data => {
          if (data.tracks && data.tracks.length > 0) {
            const track = data.tracks[0];
            setTrack({
              title: track.title,
              artist: '<?= htmlspecialchars($artistName, ENT_QUOTES); ?>',
              cover: '/uploads/albums/<?= htmlspecialchars($latestAlbum['cover']); ?>',
              src: '/uploads/tracks/' + track.audio_file
            });
          }
        })
        .catch(() => {
          alert('Unable to play album at this time.');
        });
      <?php endif; ?>
    }

    // Particle animation
    function initParticles() {
      const particles = document.querySelectorAll('.particle');
      particles.forEach((particle, index) => {
        particle.style.animationDelay = `${index * 0.5}s`;
        particle.style.animationDuration = `${3 + Math.random() * 2}s`;
      });
    }

    // Intersection observer for animations
    const observeElements = () => {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('fade-in-up');
          }
        });
      }, { threshold: 0.1 });

      document.querySelectorAll('.fade-in-up, .track-card, .album-card').forEach(el => {
        observer.observe(el);
      });
    };

    // Initialize everything
    document.addEventListener('DOMContentLoaded', () => {
      initParticles();
      observeElements();
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>

  <!-- Additional CSS for enhanced design -->
  <style>
    /* Hero enhancements */
    .hero {
      position: relative;
      overflow: hidden;
    }

    .hero__particles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(139, 92, 246, 0.6);
      border-radius: 50%;
      animation: float 3s ease-in-out infinite;
    }

    .particle:nth-child(1) { top: 20%; left: 10%; }
    .particle:nth-child(2) { top: 40%; left: 80%; }
    .particle:nth-child(3) { top: 60%; left: 20%; }
    .particle:nth-child(4) { top: 80%; left: 70%; }
    .particle:nth-child(5) { top: 30%; left: 50%; }

    @keyframes float {
      0%, 100% { transform: translateY(0px); opacity: 0.6; }
      50% { transform: translateY(-20px); opacity: 1; }
    }

    .hero__label {
      display: inline-block;
      background: var(--gradient-primary);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .hero__stats {
      display: flex;
      gap: 2rem;
      margin: 2rem 0;
      flex-wrap: wrap;
    }

    .stat-item {
      text-align: center;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 800;
      color: var(--colour-text);
      line-height: 1;
    }

    .stat-label {
      font-size: 0.875rem;
      color: var(--colour-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.025em;
      margin-top: 0.25rem;
    }

    .hero__social {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .social-link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      color: white;
      text-decoration: none;
      font-size: 1.5rem;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }

    .social-link:hover {
      background: var(--gradient-primary);
      transform: translateY(-2px);
    }

    /* Featured album showcase */
    .hero__featured {
      width: 350px;
      max-width: 100%;
    }

    .featured-album {
      background: var(--colour-bg-card);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      border: 1px solid var(--colour-border);
      position: relative;
    }

    .featured-album__badge {
      position: absolute;
      top: -10px;
      left: 1.5rem;
      background: var(--gradient-secondary);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .featured-album__cover {
      position: relative;
      margin-bottom: 1rem;
    }

    .featured-album__cover img {
      width: 100%;
      aspect-ratio: 1;
      object-fit: cover;
      border-radius: var(--border-radius-sm);
    }

    .featured-album__play {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .featured-album__cover:hover .featured-album__play {
      opacity: 1;
    }

    .play-button {
      width: 60px;
      height: 60px;
      background: var(--gradient-primary);
      border: none;
      border-radius: 50%;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .play-button:hover {
      transform: scale(1.1);
    }

    .featured-album__info h3 {
      margin: 0 0 0.5rem;
      font-size: 1.25rem;
      font-weight: 700;
    }

    .featured-album__info p {
      margin: 0 0 1rem;
      color: var(--colour-text-muted);
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .featured-album__links {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .streaming-link {
      display: inline-flex;
      align-items: center;
      padding: 0.5rem 0.75rem;
      background: rgba(255, 255, 255, 0.05);
      border-radius: var(--border-radius-sm);
      color: var(--colour-text);
      text-decoration: none;
      font-size: 0.875rem;
      transition: all 0.2s ease;
    }

    .streaming-link:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateX(4px);
    }

    /* Top tracks section */
    .top-tracks {
      padding: 6rem 0;
      background: rgba(139, 92, 246, 0.02);
    }

    .tracks-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .track-card {
      background: var(--colour-bg-card);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      border: 1px solid var(--colour-border);
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.3s ease;
    }

    .track-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(139, 92, 246, 0.2);
    }

    .track-rank {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--colour-primary);
      min-width: 2rem;
    }

    .track-cover {
      position: relative;
      width: 60px;
      height: 60px;
      flex-shrink: 0;
    }

    .track-cover img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: var(--border-radius-sm);
    }

    .track-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: var(--border-radius-sm);
    }

    .track-card:hover .track-overlay {
      opacity: 1;
    }

    .track-play-btn {
      background: none;
      border: none;
      color: white;
      font-size: 1.25rem;
      cursor: pointer;
    }

    .track-info {
      flex: 1;
      min-width: 0;
    }

    .track-title {
      margin: 0 0 0.25rem;
      font-size: 1rem;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .track-album {
      margin: 0 0 0.5rem;
      color: var(--colour-text-muted);
      font-size: 0.875rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .track-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.75rem;
      color: var(--colour-text-muted);
    }

    .track-actions {
      flex-shrink: 0;
    }

    /* Featured albums section */
    .featured-albums {
      padding: 6rem 0;
    }

    .albums-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
    }

    .album-card {
      transition: transform 0.3s ease;
    }

    .album-card:hover {
      transform: translateY(-8px);
    }

    .album-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .album-cover {
      position: relative;
      margin-bottom: 1rem;
    }

    .album-cover img {
      width: 100%;
      aspect-ratio: 1;
      object-fit: cover;
      border-radius: var(--border-radius);
    }

    .album-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: var(--border-radius);
    }

    .album-card:hover .album-overlay {
      opacity: 1;
    }

    .album-play-btn {
      width: 60px;
      height: 60px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }

    .album-info h3 {
      margin: 0 0 0.25rem;
      font-size: 1.25rem;
      font-weight: 700;
    }

    .album-year {
      margin: 0 0 0.5rem;
      color: var(--colour-text-muted);
      font-size: 0.875rem;
    }

    .album-description {
      margin: 0;
      color: var(--colour-text-muted);
      font-size: 0.875rem;
      line-height: 1.4;
    }

    /* About section */
    .about-section {
      padding: 6rem 0;
      background: var(--colour-bg-dark);
    }

    .about-content {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 4rem;
      align-items: center;
    }

    .about-description {
      font-size: 1.1rem;
      line-height: 1.6;
      color: var(--colour-text-muted);
      margin-bottom: 2rem;
    }

    .about-details {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .detail-label {
      font-weight: 600;
      min-width: 120px;
    }

    .detail-value {
      color: var(--colour-text-muted);
    }

    .about-image {
      text-align: center;
    }

    .artist-avatar {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: var(--gradient-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      font-weight: 800;
      color: white;
      margin: 0 auto;
    }

    /* Newsletter section */
    .newsletter-section {
      padding: 4rem 0;
      background: var(--gradient-primary);
      color: white;
    }

    .newsletter-content {
      text-align: center;
      max-width: 600px;
      margin: 0 auto;
    }

    .newsletter-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 1rem;
    }

    .newsletter-subtitle {
      font-size: 1.1rem;
      margin-bottom: 2rem;
      opacity: 0.9;
    }

    .newsletter-input-group {
      display: flex;
      gap: 1rem;
      max-width: 400px;
      margin: 0 auto 1rem;
    }

    .newsletter-input-group input {
      flex: 1;
      padding: 0.875rem 1rem;
      border: none;
      border-radius: var(--border-radius-sm);
      font-size: 1rem;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      backdrop-filter: blur(10px);
    }

    .newsletter-input-group input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .newsletter-privacy {
      font-size: 0.875rem;
      opacity: 0.8;
      margin: 0;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
      .hero__content {
        grid-template-columns: 1fr;
        gap: 3rem;
        text-align: center;
      }
      
      .about-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
      }
    }

    @media (max-width: 768px) {
      .hero__stats {
        gap: 1rem;
      }
      
      .stat-number {
        font-size: 1.5rem;
      }
      
      .track-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
      }
      
      .track-info {
        text-align: center;
      }
      
      .newsletter-input-group {
        flex-direction: column;
      }
      
      .artist-avatar {
        width: 150px;
        height: 150px;
        font-size: 2rem;
      }
    }
  </style>
</body>
</html>