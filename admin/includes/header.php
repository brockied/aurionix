<?php
/**
 * ADMIN HEADER
 * Place this file as: admin/includes/header.php
 */
?>

<header class="main-header">
    <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="breadcrumb">
            <a href="dashboard.php">Admin</a>
            <span class="breadcrumb-separator">></span>
            <span class="breadcrumb-current"><?= ucfirst(str_replace('-', ' ', basename($_SERVER['PHP_SELF'], '.php'))) ?></span>
        </div>
    </div>
    
    <div class="header-center">
        <div class="search-container">
            <input type="text" class="header-search" placeholder="Search albums, tracks, settings..." id="globalSearch">
            <button class="search-btn">🔍</button>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-actions">
            <!-- Quick Add Button -->
            <div class="quick-add-dropdown">
                <button class="header-btn" id="quickAddBtn" title="Quick Add">
                    ➕
                </button>
                <div class="dropdown-menu" id="quickAddMenu">
                    <a href="albums.php?action=add" class="dropdown-item">
                        <span class="item-icon">🎵</span>
                        <span>Add Album</span>
                    </a>
                    <a href="tracks.php?action=add" class="dropdown-item">
                        <span class="item-icon">🎼</span>
                        <span>Add Track</span>
                    </a>
                    <a href="streaming-links.php?action=add" class="dropdown-item">
                        <span class="item-icon">🔗</span>
                        <span>Add Link</span>
                    </a>
                    <!-- Removed Upload Media link as requested -->
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="notifications-dropdown">
                <button class="header-btn" id="notificationsBtn" title="Notifications">
                    🔔
                    <span class="notification-badge">3</span>
                </button>
                <div class="dropdown-menu notifications-menu" id="notificationsMenu">
                    <div class="dropdown-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read">Mark all read</button>
                    </div>
                    
                    <div class="notifications-list">
                        <div class="notification-item unread">
                            <div class="notification-icon">🎵</div>
                            <div class="notification-content">
                                <h4>New album uploaded</h4>
                                <p>"Electronic Dreams" has been successfully processed</p>
                                <span class="notification-time">5 minutes ago</span>
                            </div>
                        </div>
                        
                        <div class="notification-item unread">
                            <div class="notification-icon">📊</div>
                            <div class="notification-content">
                                <h4>Analytics Report Ready</h4>
                                <p>Monthly streaming report is available for download</p>
                                <span class="notification-time">2 hours ago</span>
                            </div>
                        </div>
                        
                        <div class="notification-item">
                            <div class="notification-icon">🔗</div>
                            <div class="notification-content">
                                <h4>Streaming Link Updated</h4>
                                <p>Spotify link for "Night Vibes" has been updated</p>
                                <span class="notification-time">1 day ago</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dropdown-footer">
                        <a href="notifications.php">View all notifications</a>
                    </div>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="user-dropdown">
                <button class="user-menu-btn" id="userMenuBtn">
                    <div class="user-avatar">
                        <span><?= strtoupper(substr($_SESSION['email'], 0, 1)) ?></span>
                    </div>
                    <span class="user-name"><?= explode('@', $_SESSION['email'])[0] ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <div class="dropdown-menu user-menu" id="userMenu">
                    <div class="dropdown-header">
                        <div class="user-info-full">
                            <div class="user-avatar-large">
                                <span><?= strtoupper(substr($_SESSION['email'], 0, 1)) ?></span>
                            </div>
                            <div class="user-details-full">
                                <h3><?= explode('@', $_SESSION['email'])[0] ?></h3>
                                <p><?= $_SESSION['email'] ?></p>
                                <span class="user-role-badge">Administrator</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dropdown-section">
                        <a href="profile.php" class="dropdown-item">
                            <span class="item-icon">👤</span>
                            <span>Profile Settings</span>
                        </a>
                        <a href="preferences.php" class="dropdown-item">
                            <span class="item-icon">⚙️</span>
                            <span>Preferences</span>
                        </a>
                        <a href="activity.php" class="dropdown-item">
                            <span class="item-icon">📋</span>
                            <span>Activity Log</span>
                        </a>
                    </div>
                    
                    <div class="dropdown-separator"></div>
                    
                    <div class="dropdown-section">
                        <a href="../" target="_blank" class="dropdown-item">
                            <span class="item-icon">🌐</span>
                            <span>View Website</span>
                        </a>
                        <a href="help.php" class="dropdown-item">
                            <span class="item-icon">❓</span>
                            <span>Help Center</span>
                        </a>
                    </div>
                    
                    <div class="dropdown-separator"></div>
                    
                    <a href="logout.php" class="dropdown-item logout-item">
                        <span class="item-icon">🚪</span>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
/* Header Styles */
.main-header {
    position: fixed;
    top: 0;
    left: 280px;
    right: 0;
    height: 70px;
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    z-index: 900;
    display: flex;
    align-items: center;
    padding: 0 30px;
    transition: left 0.3s ease;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.mobile-menu-btn {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    gap: 4px;
}

.mobile-menu-btn span {
    width: 20px;
    height: 2px;
    background: rgba(255,255,255,0.7);
    transition: 0.3s;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.6);
    font-size: 0.9rem;
}

.breadcrumb a {
    color: #e94560;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: #f27121;
}

.breadcrumb-separator {
    color: rgba(255,255,255,0.4);
}

.breadcrumb-current {
    color: white;
    text-transform: capitalize;
}

.header-center {
    flex: 1;
    display: flex;
    justify-content: center;
    margin: 0 30px;
}

.search-container {
    position: relative;
    max-width: 500px;
    width: 100%;
}

.header-search {
    width: 100%;
    padding: 12px 50px 12px 20px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 25px;
    color: white;
    font-size: 14px;
    transition: all 0.3s ease;
}

.header-search:focus {
    outline: none;
    border-color: #e94560;
    background: rgba(255,255,255,0.15);
    box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
}

.header-search::placeholder {
    color: rgba(255,255,255,0.5);
}

.search-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    font-size: 16px;
    transition: color 0.3s ease;
}

.search-btn:hover {
    color: #e94560;
}

.header-right {
    display: flex;
    align-items: center;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.header-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #e94560;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    font-weight: 600;
}

.user-menu-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 25px;
    padding: 8px 15px 8px 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-menu-btn:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e94560, #f27121);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-name {
    font-weight: 500;
    text-transform: capitalize;
}

.dropdown-arrow {
    font-size: 10px;
    color: rgba(255,255,255,0.5);
    transition: transform 0.3s ease;
}

.user-menu-btn.active .dropdown-arrow {
    transform: rotate(180deg);
}

/* Dropdown Styles */
.quick-add-dropdown,
.notifications-dropdown,
.user-dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: rgba(26, 26, 46, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    margin-top: 10px;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.dropdown-header h3 {
    color: white;
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.mark-all-read {
    background: none;
    border: none;
    color: #e94560;
    cursor: pointer;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.mark-all-read:hover {
    color: #f27121;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: none;
    width: 100%;
    cursor: pointer;
}

.dropdown-item:hover {
    background: rgba(255,255,255,0.05);
    color: white;
}

.item-icon {
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.dropdown-separator {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 10px 0;
}

.dropdown-section {
    padding: 10px 0;
}

.logout-item {
    color: #ff6b7a !important;
}

.logout-item:hover {
    background: rgba(220, 53, 69, 0.1) !important;
}

/* User Info in Dropdown */
.user-info-full {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar-large {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e94560, #f27121);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.2rem;
}

.user-details-full h3 {
    color: white;
    font-size: 1rem;
    margin-bottom: 2px;
    text-transform: capitalize;
}

.user-details-full p {
    color: rgba(255,255,255,0.6);
    font-size: 0.8rem;
    margin-bottom: 5px;
}

.user-role-badge {
    background: rgba(233, 69, 96, 0.2);
    color: #e94560;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Notifications */
.notifications-menu {
    width: 350px;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.3s ease;
    position: relative;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 20px;
    width: 6px;
    height: 6px;
    background: #e94560;
    border-radius: 50%;
}

.notification-item:hover {
    background: rgba(255,255,255,0.03);
}

.notification-icon {
    font-size: 18px;
    margin-top: 2px;
}

.notification-content h4 {
    color: white;
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.notification-content p {
    color: rgba(255,255,255,0.6);
    font-size: 0.8rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.notification-time {
    color: rgba(255,255,255,0.4);
    font-size: 0.7rem;
}

.dropdown-footer {
    padding: 15px 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    text-align: center;
}

.dropdown-footer a {
    color: #e94560;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.dropdown-footer a:hover {
    color: #f27121;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .main-header {
        left: 0;
        padding: 0 20px;
    }
    
    .mobile-menu-btn {
        display: flex;
    }
    
    .header-center {
        margin: 0 15px;
    }
    
    .header-search {
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .breadcrumb {
        display: none;
    }
    
    .user-name {
        display: none;
    }
    
    .dropdown-menu {
        right: -10px;
        min-width: 280px;
    }
    
    .notifications-menu {
        width: 300px;
    }
}

@media (max-width: 480px) {
    .header-center {
        display: none;
    }
    
    .header-actions {
        gap: 10px;
    }
}
</style>

<!-- Admin helper script: provides global search and dropdown behaviours -->
<script src="admin-script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.quick-add-dropdown, .notifications-dropdown, .user-dropdown');
    
    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('button');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (btn && menu) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close all other dropdowns
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.querySelector('.dropdown-menu').classList.remove('show');
                        otherDropdown.querySelector('button').classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                menu.classList.toggle('show');
                btn.classList.toggle('active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.querySelector('.dropdown-menu').classList.remove('show');
            dropdown.querySelector('button').classList.remove('active');
        });
    });
    
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            // Implement search logic here
            if (query.length > 2) {
                // Show search suggestions
                console.log('Searching for:', query);
            }
        });
        
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                // Redirect to search results
                window.location.href = `search.php?q=${encodeURIComponent(e.target.value)}`;
            }
        });
    }
    
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            // Toggle sidebar on mobile
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        });
    }
    
    // Mark all notifications as read
    const markAllRead = document.querySelector('.mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            unreadItems.forEach(item => {
                item.classList.remove('unread');
            });
            
            // Update notification badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        });
    }
});
</script>