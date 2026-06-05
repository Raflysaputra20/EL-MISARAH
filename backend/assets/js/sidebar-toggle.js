/**
 * Dashboard Sidebar Toggle - Shared JS
 * Handles mobile sidebar open/close for both Penghuni and Admin dashboards
 */
(function() {
    'use strict';

    function getSidebar() {
        return document.querySelector('.sidebar, .sb, .admin-sidebar');
    }

    function getBackdrop() {
        return document.getElementById('sidebarBackdrop');
    }

    // Open sidebar
    window.openMobileSidebar = function() {
        const sidebar = getSidebar();
        let backdrop = getBackdrop();
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'sidebarBackdrop';
            backdrop.className = 'sidebar-backdrop';
            backdrop.addEventListener('click', closeMobileSidebar);
            document.body.appendChild(backdrop);
        }
        backdrop.style.display = 'block';
        if (sidebar) sidebar.classList.add('mobile-open');
        // Small delay for transition
        requestAnimationFrame(function() {
            backdrop.classList.add('show');
        });
        document.body.classList.add('sidebar-open');
    };

    // Close sidebar
    window.closeMobileSidebar = function() {
        const sidebar = getSidebar();
        const backdrop = getBackdrop();
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        // Remove backdrop after transition
        setTimeout(function() {
            if (backdrop && !backdrop.classList.contains('show')) {
                backdrop.style.display = 'none';
            }
        }, 300);
    };

    // Toggle sidebar
    window.toggleMobileSidebar = function() {
        const sidebar = getSidebar();
        if (sidebar && sidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    };

    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    // Close sidebar when clicking a link (mobile navigation)
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = getSidebar();
        if (sidebar) {
            sidebar.querySelectorAll('.sidebar-link, .sb-link, .sidebar-menu a, .sb-menu a, .admin-sidebar a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        const href = link.getAttribute('href');
                        if (!href || href === '#' || href.startsWith('javascript:')) {
                            closeMobileSidebar();
                        } else {
                            closeMobileSidebar();
                        }
                    }
                });
            });
        }

        // Create backdrop element on load
        if (!getBackdrop()) {
            const backdrop = document.createElement('div');
            backdrop.id = 'sidebarBackdrop';
            backdrop.className = 'sidebar-backdrop';
            backdrop.addEventListener('click', closeMobileSidebar);
            document.body.appendChild(backdrop);
        }
    });

    // Handle window resize - close sidebar when resizing to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            const sidebar = getSidebar();
            const backdrop = getBackdrop();
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.style.display = '';
            }
            document.body.classList.remove('sidebar-open');
        }
    });
})();
