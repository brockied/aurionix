<?php
/**
 * UPDATED ADMIN SIDEBAR NAVIGATION (No Tracks Menu)
 * Replace: admin/includes/sidebar.php
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
                    <span class="nav-text">Albums & Tracks</span>
                    <span class="nav-badge" id="albumsCount">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM albums");
                            echo $stmt->fetch()['count'];
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </span>
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
            
            <li class="nav-item">
                <a href="media.php" class="nav-link <?= $current_page === 'media' ? 'active' : '' ?>">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Media Files</span>
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
    padding: 5px;
}

.sidebar-toggle span {
    width: 20px;
    height: 2px;
    background: white;
    margin: 2px 0;
    transition: 0.3s;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 8px;
    padding: 0 20px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background: rgba(255,255,255,0.05);
    color: white;
}

.nav-link.active {
    background: linear-gradient(135deg, #e94560, #f27121);
    color: white;
    box-shadow: 0 5px 15px rgba(233, 69, 96, 0.3);
}

.nav-icon {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.nav-text {
    font-weight: 500;
}

.nav-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: auto;
    font-weight: 600;
}

.nav-link.active .nav-badge {
    background: rgba(255,255,255,0.3);
}

.nav-separator {
    margin: 20px 0 10px 0;
    padding: 0 40px;
}

.nav-separator span {
    color: rgba(255,255,255,0.4);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-footer {
    padding: 20px;
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
    display: flex;
    flex-direction: column;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: capitalize;
}

.user-role {
    color: rgba(255,255,255,0.6);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.footer-actions {
    display: flex;
    gap: 8px;
}

.footer-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.footer-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
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