<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    header("Location: ../api/auth/login.php");
    exit;
}

$userId   = $_SESSION["user_id"];
$namaUser = $_SESSION["nama"] ?? "Penghuni";

// Get user profile photo
try {
    $stmtUser = $conn->prepare("SELECT status, foto FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $userData = null; }
$userFoto   = $userData['foto'] ?? null;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Anda - Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css?v=1.2">
    <style>
        .notification-btn, .notif-btn { background:none !important; border:none !important; outline:none !important; box-shadow:none !important; cursor:pointer; padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#1f2937; transition:background .15s; }
        .notification-btn:hover, .notif-btn:hover { background:rgba(0,0,0,0.06) !important; }

        :root { --green: #11a654; --bg: #f4f6f8; --dark: #1f2937; --gray: #64748b; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--dark); margin: 0; }
        
        .sidebar { width: 240px; height: 100vh; background: var(--green); position: fixed; top: 0; left: 0; z-index: 1000; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
        .sidebar-brand { padding: 30px 25px; font-size: 22px; font-weight: 800; color: white; }
        .sidebar-menu { list-style: none; padding: 0 15px; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 12px 18px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 12px; transition: 0.2s; }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-link.active { background: white; color: var(--green); font-weight: 700; }
        .sidebar-icon { width: 18px; height: 18px; }
        .sidebar-footer { padding: 20px 15px 25px; }
        .btn-keluar { display: inline-flex; align-items: center; gap: 8px; background: white; color: var(--dark); text-decoration: none; padding: 10px 22px; border-radius: 30px; font-weight: 700; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        .main { margin-left: 240px; min-height: 100vh; }
        .topbar { height: 68px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #475569; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 700; font-size: 13.5px; color: var(--dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: var(--gray); font-weight: 500; }
        .content { padding: 25px 30px; }

        .card-box { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .page-notif-item { display: flex; gap: 16px; padding: 18px; border-radius: 14px; background: #f8fafc; border: 1px solid #f1f5f9; margin-bottom: 15px; text-decoration: none; color: inherit; transition: all 0.2s ease; }
        .page-notif-item:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.04); background: #f1f5f9; border-color: #e2e8f0; }
        .page-notif-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; position: relative; }
        .page-notif-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .page-notif-time { font-size: 12px; color: #64748b; }
        
        .clear-btn { font-size: 13px; font-weight: 600; color: var(--green); border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .clear-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>

<aside class="sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">Elmi Sarah</span>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="notifikasi.php" class="sidebar-link active"><i data-lucide="bell" class="sidebar-icon"></i> Notifikasi</a></li>
        <li class="sidebar-item"><a href="pembayaran.php" class="sidebar-link"><i data-lucide="credit-card" class="sidebar-icon"></i> Pembayaran</a></li>
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link"><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link"><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link"><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link"><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link"><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px; height:24px;"></i></button>
            <h2 style="font-size: 18px; font-weight: 800; margin:0;">Notifikasi Anda</h2>
        </div>
        <div class="topbar-right">
            <div id="notifWrapper" style="position:relative;display:inline-block;">
                <button id="notifBell" class="notification-btn" onclick="toggleNotif(event)" aria-label="Notifikasi" style="position:relative;">
                    <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                    <span id="notifBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:999px;align-items:center;justify-content:center;padding:0 3px;line-height:17px;text-align:center;">0</span>
                </button>
                <!-- DROPDOWN NOTIFIKASI -->
                <div id="notifDropdown" style="display:none;position:absolute;right:0;top:52px;width:330px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,0.14);z-index:9999;overflow:hidden;">
                    <div style="padding:14px 18px 10px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-weight:700;font-size:14px;color:#111;">🔔 Notifikasi</span>
                        <span id="notifCount" style="font-size:11px;color:#888;">Memuat...</span>
                    </div>
                    <div id="notifList" style="max-height:300px;overflow-y:auto;">
                        <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Memuat notifikasi...</div>
                    </div>
                </div>
            </div>
            <div class="user-profile">
                <a href="profil.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px;">
                    <div class="avatar">
                        <?php if (isset($userFoto) && $userFoto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($namaUser ?? 'P', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaUser) ?></span>
                        <span class="user-role">Penghuni Kos</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="card-title mb-0"><i data-lucide="bell" style="color:var(--green)"></i> Semua Notifikasi</div>
                <button onclick="clearAllRead()" class="clear-btn"><i data-lucide="check-check" style="width:16px;"></i> Tandai Semua Sudah Dibaca</button>
            </div>

            <!-- Info pagination -->
            <div id="paginInfo" style="font-size:13px; color:#64748b; margin-bottom:14px; display:none;">
                Menampilkan <strong id="paginFrom">0</strong>–<strong id="paginTo">0</strong> dari <strong id="paginTotal">0</strong> notifikasi
            </div>

            <!-- List notifikasi -->
            <div id="fullNotifList">
                <div style="padding:40px; text-align:center; color:#aaa;">Memuat notifikasi...</div>
            </div>

            <!-- Pagination controls -->
            <div id="paginControls" style="display:none; margin-top:20px;">
                <nav>
                    <ul class="pagination justify-content-center mb-0" id="paginPages" style="flex-wrap:wrap; gap:4px;"></ul>
                </nav>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script src="../assets/js/notifikasi.js"></script>
<script>
    lucide.createIcons();

    const PER_PAGE   = 10;
    let allNotifs    = [];
    let currentPage  = 1;

    const typeConfigFull = {
        warning: { bg: '#fff8e1', border: '#f59e0b', icon: '⚠️' },
        info:    { bg: '#e0f2fe', border: '#38bdf8', icon: 'ℹ️' },
        success: { bg: '#dcfce7', border: '#4ade80', icon: '✅' },
        danger:  { bg: '#fee2e2', border: '#f87171', icon: '❗' },
    };

    function getReadKeys() {
        try {
            const val = localStorage.getItem('read_notif_keys');
            return val ? JSON.parse(val) : [];
        } catch(e) { return []; }
    }

    // ─── Render satu halaman ─────────────────────────────────────────────────
    function renderPage(page) {
        currentPage = page;
        const container  = document.getElementById('fullNotifList');
        const paginInfo  = document.getElementById('paginInfo');
        const paginCtrl  = document.getElementById('paginControls');

        if (allNotifs.length === 0) {
            container.innerHTML = `
                <div style="padding:60px 20px; text-align:center;">
                    <div style="font-size:48px; margin-bottom:12px;">🔔</div>
                    <h4 style="font-weight:700; color:#475569;">Tidak ada notifikasi</h4>
                    <p style="color:#94a3b8; font-size:14px; margin-bottom:0;">Semua status transaksi dan pengumuman kost Anda akan muncul di sini.</p>
                </div>`;
            paginInfo.style.display = 'none';
            paginCtrl.style.display = 'none';
            return;
        }

        const totalPages = Math.ceil(allNotifs.length / PER_PAGE);
        const start      = (page - 1) * PER_PAGE;
        const end        = Math.min(start + PER_PAGE, allNotifs.length);
        const pageNotifs = allNotifs.slice(start, end);
        const readKeys   = getReadKeys();

        // Info teks
        paginInfo.style.display = 'block';
        document.getElementById('paginFrom').textContent  = start + 1;
        document.getElementById('paginTo').textContent    = end;
        document.getElementById('paginTotal').textContent = allNotifs.length;

        // Render items
        container.innerHTML = pageNotifs.map(n => {
            const cfg = typeConfigFull[n.type] || typeConfigFull.info;
            let rawLink = n.link || '';
            rawLink = rawLink.replace(/^penghuni\//, '');
            const href = rawLink ? rawLink : '#';

            const isUnread    = n.key && !readKeys.includes(n.key);
            const borderStyle = isUnread ? 'border-left: 4px solid var(--green);' : '';
            const bgStyle     = isUnread ? 'background-color: #f8fafc;' : 'background-color: #fff;';

            return `
            <a href="${href}" onclick="markAsReadSingle('${n.key}')" class="page-notif-item" style="${bgStyle} ${borderStyle}">
                <div class="page-notif-icon" style="background:${cfg.bg}; border:1px solid ${cfg.border};">
                    ${cfg.icon}
                    ${isUnread ? `<span style="position:absolute; top:-2px; right:-2px; width:10px; height:10px; border-radius:50%; background:#ef4444; border:2px solid #fff;"></span>` : ''}
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="page-notif-title" style="${isUnread ? 'font-weight:700; color:#000;' : ''}">${n.isi}</div>
                    <div class="page-notif-time">${n.waktu} ${isUnread ? '<span class="badge bg-success ms-2" style="font-size:10px;">Baru</span>' : ''}</div>
                </div>
                <div style="display:flex; align-items:center; color:#94a3b8;">
                    <i data-lucide="chevron-right" style="width:20px; height:20px;"></i>
                </div>
            </a>`;
        }).join('');

        lucide.createIcons();

        // ─── Pagination controls ────────────────────────────────────────────
        if (totalPages <= 1) {
            paginCtrl.style.display = 'none';
            return;
        }

        paginCtrl.style.display = 'block';
        const ul = document.getElementById('paginPages');

        // Bangun tombol halaman
        let paginHtml = '';

        // Prev
        paginHtml += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="renderPage(${page - 1})" style="border-radius:8px; font-size:13px; font-weight:600;">
                <i data-lucide="chevron-left" style="width:14px;height:14px;display:inline;vertical-align:middle;"></i> Prev
            </button>
        </li>`;

        // Halaman — tampilkan maksimal 7 angka, dengan ellipsis
        const maxButtons = 7;
        let pages = [];

        if (totalPages <= maxButtons) {
            // Tampilkan semua
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            // Selalu tampilkan halaman 1
            pages.push(1);

            let start = Math.max(2, page - 2);
            let end   = Math.min(totalPages - 1, page + 2);

            if (start > 2)              pages.push('...');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < totalPages - 1)   pages.push('...');

            // Selalu tampilkan halaman terakhir
            pages.push(totalPages);
        }

        pages.forEach(p => {
            if (p === '...') {
                paginHtml += `<li class="page-item disabled"><span class="page-link" style="font-size:13px;">…</span></li>`;
            } else {
                const active = p === page ? 'active' : '';
                const activeStyle = p === page ? 'background:var(--green); border-color:var(--green);' : 'font-size:13px;';
                paginHtml += `<li class="page-item ${active}">
                    <button class="page-link" onclick="renderPage(${p})" style="${activeStyle} border-radius:8px; font-weight:600;">${p}</button>
                </li>`;
            }
        });

        // Next
        paginHtml += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="renderPage(${page + 1})" style="border-radius:8px; font-size:13px; font-weight:600;">
                Next <i data-lucide="chevron-right" style="width:14px;height:14px;display:inline;vertical-align:middle;"></i>
            </button>
        </li>`;

        ul.innerHTML = paginHtml;
        lucide.createIcons();

        // Scroll ke atas list
        document.getElementById('fullNotifList').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─── Fetch semua notifikasi lalu render halaman 1 ─────────────────────────
    async function loadFullNotifications() {
        const container = document.getElementById('fullNotifList');

        try {
            const res  = await fetch('../api/get_notifikasi.php');
            const data = await res.json();
            if (!data.success) {
                container.innerHTML = '<div style="padding:40px; text-align:center; color:#ef4444;">Gagal mengambil data notifikasi.</div>';
                return;
            }
            allNotifs = data.notifikasi || [];
            renderPage(1);
        } catch (err) {
            console.error(err);
            container.innerHTML = '<div style="padding:40px; text-align:center; color:#ef4444;">Gagal mengambil data notifikasi.</div>';
        }
    }

    window.markAsReadSingle = function(key) {
        if (!key) return;
        try {
            let readKeys = getReadKeys();
            if (!readKeys.includes(key)) {
                readKeys.push(key);
                localStorage.setItem('read_notif_keys', JSON.stringify(readKeys));
            }
        } catch(e){}
    }

    window.clearAllRead = async function() {
        try {
            const res  = await fetch('../api/get_notifikasi.php');
            const data = await res.json();
            if (data.success && data.notifikasi) {
                const keys    = data.notifikasi.map(n => n.key).filter(Boolean);
                let readKeys  = getReadKeys();
                const updated = [...new Set([...readKeys, ...keys])];
                localStorage.setItem('read_notif_keys', JSON.stringify(updated));

                // Re-render halaman saat ini
                renderPage(currentPage);

                // Update badge header
                const badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
            }
        } catch(e){}
    }

    document.addEventListener('DOMContentLoaded', loadFullNotifications);
</script>
</body>
</html>

