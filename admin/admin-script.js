/*
 * Admin dashboard helper script
 *
 * This script provides a handful of small behaviours for the admin
 * interface.  It includes a global search handler which redirects
 * queries to the albums management page, and basic dropdown toggles
 * for the quick-add, notification and user menus.  Attach this
 * script at the bottom of your admin pages (see dashboard.php).
 */

document.addEventListener('DOMContentLoaded', () => {
    /* Global search functionality
     * When a user presses Enter inside the top right search box or
     * clicks the adjacent search button, redirect them to the albums
     * page with the search query appended.  You can extend this
     * behaviour to search tracks or settings by implementing a
     * dedicated search endpoint.
     */
    const globalSearch = document.getElementById('globalSearch');
    const searchBtn = document.querySelector('.header-center .search-btn');

    const performSearch = () => {
        if (!globalSearch) return;
        const query = globalSearch.value.trim();
        if (query.length < 1) return;
        window.location.href = `albums.php?search=${encodeURIComponent(query)}`;
    };

    if (globalSearch) {
        globalSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    if (searchBtn) {
        searchBtn.addEventListener('click', () => performSearch());
    }

    /* Dropdown toggles
     * Toggle the visibility of the quick-add, notifications and user menus.
     * Clicking outside of a dropdown will close it.
     */
    const toggles = [
        { btnId: 'quickAddBtn', menuId: 'quickAddMenu' },
        { btnId: 'notificationsBtn', menuId: 'notificationsMenu' },
        { btnId: 'userMenuBtn', menuId: 'userMenu' }
    ];

    toggles.forEach(({ btnId, menuId }) => {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        if (!btn || !menu) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('open');
            // Close other menus
            toggles.forEach(({ menuId: otherId }) => {
                if (otherId !== menuId) {
                    const otherMenu = document.getElementById(otherId);
                    if (otherMenu) otherMenu.classList.remove('open');
                }
            });
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        toggles.forEach(({ menuId, btnId }) => {
            const menu = document.getElementById(menuId);
            const btn = document.getElementById(btnId);
            if (menu && menu.classList.contains('open')) {
                // If click isn't inside menu or its button, close
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    menu.classList.remove('open');
                }
            }
        });
    });

    /* File upload preview
     * The custom album cover upload uses a hidden file input and a visible label
     * with descriptive text.  Update the label to show the selected file name
     * when a file is chosen so admins know their selection has registered.
     */
    const fileInputs = document.querySelectorAll('.form-file');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const container = this.closest('.form-group');
            if (!container) return;
            const file = this.files && this.files[0];
            // Find the text element inside the associated file-upload label
            const label = container.querySelector('.file-upload-text h4');
            if (file && label) {
                label.textContent = `Selected: ${file.name}`;
            }
        });
    });
});