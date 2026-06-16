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
        // Initialize notification dropdown
        initNotifications();
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

    // === NOTIFICATION SYSTEM ===
    function getApiUrl() {
        const path = window.location.pathname;
        const backendIndex = path.indexOf('/backend/');
        if (backendIndex === -1) return '';
        
        const subpath = path.substring(backendIndex + 9);
        const segments = subpath.split('/');
        const depth = segments.length - 1;
        
        let prefix = '';
        for (let i = 0; i < depth; i++) {
            prefix += '../';
        }
        return prefix + 'api/get_notifikasi.php';
    }

    function getBackendPathPrefix() {
        const path = window.location.pathname;
        const backendIndex = path.indexOf('/backend/');
        if (backendIndex === -1) return '';
        
        const subpath = path.substring(backendIndex + 9);
        const segments = subpath.split('/');
        const depth = segments.length - 1;
        
        let prefix = '';
        for (let i = 0; i < depth; i++) {
            prefix += '../';
        }
        return prefix;
    }

    function injectNotifStyles() {
        if (document.getElementById('notifStyles')) return;
        const style = document.createElement('style');
        style.id = 'notifStyles';
        style.innerHTML = `
            .notif-dropdown-wrapper {
                position: relative;
                display: inline-flex;
                align-items: center;
            }
            .notif-btn,
            .notification-btn {
                background: none !important;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
                color: #1f2937 !important;
                cursor: pointer;
                padding: 6px !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: relative;
                transition: background 0.15s;
            }
            .notif-btn:hover,
            .notification-btn:hover {
                background: rgba(0,0,0,0.06) !important;
            }
            .notif-dropdown-menu {
                position: absolute;
                right: 0;
                top: calc(100% + 8px);
                width: 320px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px -5px rgba(0,0,0,0.15), 0 4px 12px -4px rgba(0,0,0,0.1);
                border: 1px solid #f3f4f6;
                display: none;
                z-index: 9999;
                overflow: hidden;
                text-align: left;
            }
            .notif-dropdown-menu.show {
                display: block;
                animation: notifSlideDown 0.2s ease forwards;
            }
            @keyframes notifSlideDown {
                from { opacity: 0; transform: translateY(-8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .notif-dropdown-header {
                padding: 14px 16px;
                border-bottom: 1px solid #f3f4f6;
                font-weight: 700;
                font-size: 13.5px;
                color: #1f2937;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .notif-count-badge {
                background: #fee2e2;
                color: #ef4444;
                font-size: 11px;
                padding: 2px 8px;
                border-radius: 20px;
                font-weight: 600;
            }
            .notif-dropdown-body {
                max-height: 280px;
                overflow-y: auto;
            }
            .notif-item {
                display: flex;
                padding: 12px 16px;
                border-bottom: 1px solid #f9fafb;
                transition: background 0.15s;
                text-decoration: none !important;
                color: inherit !important;
                align-items: start;
                gap: 12px;
            }
            .notif-item:hover { background: #f3f4f6; }
            .notif-item:last-child { border-bottom: none; }
            .notif-dot {
                width: 8px; height: 8px;
                border-radius: 50%;
                margin-top: 5px;
                flex-shrink: 0;
            }
            .notif-dot.warning { background: #f59e0b; }
            .notif-dot.info    { background: #3b82f6; }
            .notif-dot.success { background: #10b981; }
            .notif-content { flex: 1; }
            .notif-text {
                font-size: 12px; font-weight: 500;
                color: #374151; line-height: 1.4;
            }
            .notif-time {
                font-size: 11px; color: #9ca3af; margin-top: 2px;
            }
            .notif-empty {
                padding: 28px 16px;
                text-align: center;
                color: #9ca3af;
                font-size: 12.5px;
            }
            .notif-badge-icon {
                position: absolute;
                top: 1px; right: 1px;
                width: 8px; height: 8px;
                background: #ef4444;
                border-radius: 50%;
                border: 2px solid white;
                pointer-events: none;
            }
        `;
        document.head.appendChild(style);
    }

    function initNotifications() {
        const topbarRight = document.querySelector('.topbar-right');
        if (!topbarRight) return;

        injectNotifStyles();

        // 1. Create or wrap notifications button
        let wrapper = topbarRight.querySelector('.notif-dropdown-wrapper');
        let notifBtn = topbarRight.querySelector('.notif-btn, .notification-btn');
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'notif-dropdown-wrapper';
            
            if (notifBtn) {
                // Wrap existing button
                const parent = notifBtn.parentNode;
                parent.replaceChild(wrapper, notifBtn);
                wrapper.appendChild(notifBtn);
                // Ensure it has relative pos
                notifBtn.style.position = 'relative';
            } else {
                // Create new button and prepend to topbar-right
                notifBtn = document.createElement('button');
                notifBtn.className = 'notif-btn';
                notifBtn.type = 'button';
                notifBtn.innerHTML = '<i data-lucide="bell" style="width:20px;height:20px;"></i>';
                wrapper.appendChild(notifBtn);
                topbarRight.insertBefore(wrapper, topbarRight.firstChild);
                
                // Re-init lucide icons if library is loaded
                if (window.lucide) {
                    window.lucide.createIcons();
                } else {
                    window.addEventListener('load', function() {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                }
            }
        }

        // 2. Create dropdown menu element
        let dropdownMenu = wrapper.querySelector('.notif-dropdown-menu');
        if (!dropdownMenu) {
            dropdownMenu = document.createElement('div');
            dropdownMenu.className = 'notif-dropdown-menu';
            wrapper.appendChild(dropdownMenu);
        }

        // 3. Toggle dropdown visibility
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        // 4. Load notifications via AJAX
        const apiUrl = getApiUrl();
        const prefix = getBackendPathPrefix();
        
        if (apiUrl) {
            fetch(apiUrl)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.notifikasi) {
                        const notifs = data.notifikasi;
                        
                        // Update badge
                        let badge = notifBtn.querySelector('.notif-badge-icon');
                        if (notifs.length > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'notif-badge-icon';
                                notifBtn.appendChild(badge);
                            }
                        } else {
                            if (badge) badge.remove();
                        }
                        
                        // Render dropdown contents
                        let html = `
                            <div class="notif-dropdown-header">
                                <span>Notifikasi</span>
                                ${notifs.length > 0 ? `<span class="notif-count-badge">${notifs.length}</span>` : ''}
                            </div>
                            <div class="notif-dropdown-body">
                        `;
                        
                        if (notifs.length === 0) {
                            html += `<div class="notif-empty">Tidak ada notifikasi baru</div>`;
                        } else {
                            notifs.forEach(n => {
                                const linkUrl = prefix + n.link;
                                html += `
                                    <a href="${linkUrl}" class="notif-item">
                                        <div class="notif-dot ${n.type || 'info'}"></div>
                                        <div class="notif-content">
                                            <div class="notif-text">${n.isi}</div>
                                            <div class="notif-time">${n.waktu}</div>
                                        </div>
                                    </a>
                                `;
                            });
                        }
                        
                        html += `</div>`;
                        dropdownMenu.innerHTML = html;
                    }
                })
                .catch(err => console.error('Error fetching notifications:', err));
        }
    }
})();
