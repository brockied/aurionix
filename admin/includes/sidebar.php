<?php
/**
 * ADMIN SIDEBAR NAVIGATION
 * Create folder: admin/includes/
 * Place this file as: admin/includes/sidebar.php
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="logo-icon">🎵</span>
            <span class="logo-text">Aurionix</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="albums.php" class="nav-link <?= $current_page === 'albums' ? 'active' : '' ?>">
                    <span class="nav-icon">🎵</span>
                    <span class="nav-text">Albums</span>
                    <span class="nav-badge" id="albumsCount">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM albums");
                        echo $stmt->fetch()['count'];
                        ?>
                    </span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="tracks.php" class="nav-link <?= $current_page === 'tracks' ? 'active' : '' ?>">
                    <span class="nav-icon">🎼</span>
                    <span class="nav-text">Tracks</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="streaming-links.php" class="nav-link <?= $current_page === 'streaming-links' ? 'active' : '' ?>">
                    <span class="nav-icon">🔗</span>
                    <span class="nav-text">Streaming Links</span>
                </a>
            </li>
            
                        <li class="nav-item">
                <a href="analytics.php" class="nav-link <?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Analytics</span>
                </a>
            </li>

            <li class="nav-separator">
                <span>System</span>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page === 'settings' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="users.php" class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Users</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <span><?= strtoupper(substr($_SESSION['email'], 0, 1)) ?></span>
            </div>
            <div class="user-details">
                <span class="user-name"><?= explode('@', $_SESSION['email'])[0] ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        
        <div class="footer-actions">
            <a href="../" target="_blank" class="footer-btn" title="View Website">🌐</a>
            <a href="profile.php" class="footer-btn" title="Profile">👤</a>
            <a href="logout.php" class="footer-btn" title="Logout">🚪</a>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 280px;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    border-right: 1px solid rgba(255,255,255,0.1);
    z-index: 1000;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 25px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    font-size: 2rem;
    background: linear-gradient(135deg, #e94560, #f27121);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
}

.sidebar-toggle {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    gap: 4px;
}

.sidebar-toggle span {
    width: 20px;
    height: 2px;
    background: rgba(255,255,255,0.6);
    transition: 0.3s;
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
}

.nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    margin-bottom: 5px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 25px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 500;
}

.nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.nav-link.active {
    color: #e94560;
    background: rgba(233, 69, 96, 0.1);
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(135deg, #e94560, #f27121);
}

.nav-icon {
    font-size: 1.2rem;
    margin-right: 12px;
    width: 20px;
    text-align: center;
}

.nav-text {
    flex: 1;
}

.nav-badge {
    background: #e94560;
    color: white;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.nav-separator {
    padding: 20px 25px 10px;
    color: rgba(255,255,255,0.4);
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
}

.sidebar-footer {
    padding: 25px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e94560, #f27121);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
}

.user-details {
    flex: 1;
}

.user-name {
    display: block;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: capitalize;
}

.user-role {
    display: block;
    color: rgba(255,255,255,0.5);
    font-size: 0.8rem;
}

.footer-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.footer-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.footer-btn:hover {
    background: #e94560;
    color: white;
    transform: translateY(-2px);
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: flex;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

/* Custom Scrollbar for Sidebar */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Close sidebar on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }
    });
});
</script>