/**
 * notifikasi.js — Notification bell handler with read/unread tracking in LocalStorage
 * Include this file AFTER lucide is loaded, on every penghuni page.
 */

(function () {
    const API_URL = '../api/get_notifikasi.php';
    const REFRESH_INTERVAL = 30000; // 30s

    const TYPE_CONFIG = {
        warning: { bg: '#fff8e1', border: '#f59e0b', dotColor: '#f59e0b', icon: '⚠️' },
        info:    { bg: '#e0f2fe', border: '#38bdf8', dotColor: '#3b82f6', icon: 'ℹ️' },
        success: { bg: '#dcfce7', border: '#4ade80', dotColor: '#11a654', icon: '✅' },
    };

    // Helper: Get read notification keys from LocalStorage
    function getReadKeys() {
        try {
            const val = localStorage.getItem('read_notif_keys');
            return val ? JSON.parse(val) : [];
        } catch (e) {
            return [];
        }
    }

    // Helper: Add keys to read notifications in LocalStorage
    function markKeysAsRead(keys) {
        try {
            let readKeys = getReadKeys();
            let updated = [...new Set([...readKeys, ...keys])];
            // Keep maximum 100 entries to prevent localStorage bloating
            if (updated.length > 100) {
                updated = updated.slice(updated.length - 100);
            }
            localStorage.setItem('read_notif_keys', JSON.stringify(updated));
        } catch (e) {
            console.error('Error saving read keys to localStorage', e);
        }
    }

    // Toggle dropdown & mark current items as read
    window.toggleNotif = function (e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('notifDropdown');
        if (!dd) return;
        const isOpen = dd.style.display === 'block';
        dd.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            renderNotifikasi();
        }
    };

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        const wrapper = document.getElementById('notifWrapper');
        const dd      = document.getElementById('notifDropdown');
        if (wrapper && dd && !wrapper.contains(e.target)) {
            dd.style.display = 'none';
        }
    });

    async function fetchNotifikasi() {
        const res  = await fetch(API_URL, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) throw new Error('API error');
        return data.notifikasi || [];
    }

    async function renderNotifikasi() {
        const list    = document.getElementById('notifList');
        const badge   = document.getElementById('notifBadge');
        const countEl = document.getElementById('notifCount');
        if (!list) return;

        list.innerHTML = '<div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Memuat...</div>';

        try {
            const notifs = await fetchNotifikasi();
            const readKeys = getReadKeys();

            // All currently shown notification keys
            const currentKeys = notifs.map(n => n.key).filter(Boolean);

            // Mark them as read now that they are open/visible
            if (currentKeys.length > 0) {
                markKeysAsRead(currentKeys);
            }

            // Immediately clear/hide badge since user has viewed the dropdown
            if (badge) badge.style.display = 'none';
            if (countEl) countEl.textContent = '0 unread';

            // Empty state
            if (notifs.length === 0) {
                list.innerHTML = `
                    <div style="padding:32px 18px;text-align:center;">
                        <div style="font-size:36px;margin-bottom:10px;">🔔</div>
                        <div style="font-size:13px;color:#aaa;">Tidak ada notifikasi baru</div>
                    </div>`;
                return;
            }

            // Render items
            list.innerHTML = notifs.map(n => {
                const cfg   = TYPE_CONFIG[n.type] || TYPE_CONFIG.info;
                let rawLink = n.link || '';
                rawLink = rawLink.replace(/^penghuni\//, '');
                const href = rawLink ? rawLink : '#';
                
                // Highlight unread items with a slight background
                const isUnread = n.key && !readKeys.includes(n.key);
                const bgStyle = isUnread ? 'background-color: #f8fafc;' : '';

                return `
                <a href="${href}" style="display:block;padding:13px 18px;border-bottom:1px solid #f3f4f6;text-decoration:none;transition:background .15s;${bgStyle}"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='${isUnread ? '#f8fafc' : 'transparent'}'">
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:38px;height:38px;border-radius:10px;background:${cfg.bg};border:1px solid ${cfg.border};display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;position:relative;">
                            ${cfg.icon}
                            ${isUnread ? `<span style="position:absolute;top:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:#ef4444;border:1.5px solid #fff;"></span>` : ''}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:${isUnread ? '700' : '600'};color:${isUnread ? '#000' : '#333'};line-height:1.4;margin-bottom:3px;">${n.isi}</div>
                            <div style="font-size:11px;color:#94a3b8;">${n.waktu}</div>
                        </div>
                    </div>
                </a>`;
            }).join('');

        } catch (err) {
            console.error('[Notifikasi] Error:', err);
            list.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;font-size:13px;">Gagal memuat notifikasi.</div>';
        }
    }

    // Auto-update badge only (background)
    async function updateBadgeOnly() {
        const badge = document.getElementById('notifBadge');
        if (!badge) return;
        try {
            const notifs = await fetchNotifikasi();
            const readKeys = getReadKeys();
            
            // Count only keys NOT in readKeys list
            const unreadCount = notifs.filter(n => n.key && !readKeys.includes(n.key)).length;

            if (unreadCount > 0) {
                badge.textContent   = unreadCount > 9 ? '9+' : unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        } catch (_) {}
    }

    function init() {
        updateBadgeOnly();
        setInterval(updateBadgeOnly, REFRESH_INTERVAL);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
