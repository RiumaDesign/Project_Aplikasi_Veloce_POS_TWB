<?php
/**
 * Top Navigation Bar with Real-time Notification Center
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once dirname(__DIR__, 2) . '/controllers/NotificationController.php';
$notifController = new NotificationController($conn);
$notifController->syncLiveAlerts();
$unreadCount = $notifController->getUnreadCount();
$initialNotifs = $notifController->getNotifications(15);

// Map judul halaman untuk breadcrumb
$pageTitles = [
    'analytics' => '📊 Grafik & Analisis Omzet',
    'menu'      => '📋 Kelola Produk',
    'stok'      => '📦 Kelola Stok Barang',
    'kasir'     => '👥 Kelola Petugas Kasir',
    'outlet'    => '🏪 Kelola Outlet & Mesin VM',
    'retur'     => '⚠️ Retur & Barang Rusak'
];
$currentPageTitle = $pageTitles[$page ?? 'analytics'] ?? 'Dashboard Admin';
?>

<!-- TOPBAR UTILITY HEADER -->
<header id="admin-topbar" class="w-full bg-slate-950/80 backdrop-blur-xl border-b border-white/10 sticky top-0 z-20 px-4 md:px-8 py-2.5 transition-colors duration-300">
    <div class="w-full flex items-center justify-between gap-3.5">
        
        <!-- Mobile Native App Header Brand (Khusus HP md:hidden) -->
        <div class="flex md:hidden items-center gap-2.5 min-w-0">
            <div class="h-8 px-1.5 bg-white rounded-xl flex items-center justify-center shadow-md border border-white/20 shrink-0">
                <img src="assets/images/logo_twb.png" alt="Logo TWB" class="h-5 w-auto object-contain">
            </div>
            <div class="overflow-hidden">
                <span class="text-xs font-black text-white truncate block"><?= $currentPageTitle ?></span>
            </div>
        </div>

        <div class="flex items-center gap-2.5 md:gap-3.5 ml-auto">
        
        <!-- Badge Status Real-time Online -->
        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Sistem Real-time</span>
        </div>

        <!-- NOTIFIKASI LONCENG & DROPDOWN -->
        <div class="relative" id="notif-dropdown-wrapper">
            <button type="button" id="btn-notif-toggle" onclick="toggleNotifDropdown()" class="relative p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 transition flex items-center justify-center focus:outline-none" title="Pusat Notifikasi & Peringatan Admin">
                <!-- Bell Icon SVG -->
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                
                <!-- Lencana Jumlah Belum Dibaca (Pulsing Badge) -->
                <span id="notif-badge-counter" class="<?= ($unreadCount > 0) ? 'flex' : 'hidden' ?> absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-black rounded-full items-center justify-center border-2 border-slate-950 shadow-lg shadow-rose-500/50 animate-bounce">
                    <?= ($unreadCount > 99) ? '99+' : $unreadCount ?>
                </span>
            </button>

            <!-- DROPDOWN PANEL NOTIFIKASI (SOLID ZERO-BLEED THROUGH) -->
            <div id="notif-dropdown-menu" class="hidden absolute right-0 mt-3 w-80 sm:w-96 rounded-3xl border shadow-2xl overflow-hidden z-50 transition-all duration-200">
                
                <!-- Header Dropdown -->
                <div class="notif-header p-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🔔</span>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-white">Notifikasi Admin</h4>
                            <p class="text-[10px] text-slate-400">Peringatan stok, transaksi & retur</p>
                        </div>
                    </div>
                    <button type="button" onclick="markAllNotifsRead()" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 hover:underline transition">
                        Tandai Semua Dibaca
                    </button>
                </div>

                <!-- Filter Kategori Tabs -->
                <div class="notif-tabs-bar flex items-center px-2 py-1.5 gap-1 overflow-x-auto text-[10px] font-bold text-slate-400">
                    <button type="button" onclick="filterNotifCategory('all')" class="notif-tab-btn px-2.5 py-1 rounded-lg bg-blue-600 text-white transition active-tab" data-cat="all">Semua</button>
                    <button type="button" onclick="filterNotifCategory('stok_kritis')" class="notif-tab-btn px-2.5 py-1 rounded-lg hover:bg-white/5 transition" data-cat="stok_kritis">⚠️ Stok Kritis</button>
                    <button type="button" onclick="filterNotifCategory('retur_barang')" class="notif-tab-btn px-2.5 py-1 rounded-lg hover:bg-white/5 transition" data-cat="retur_barang">📦 Retur</button>
                    <button type="button" onclick="filterNotifCategory('sistem')" class="notif-tab-btn px-2.5 py-1 rounded-lg hover:bg-white/5 transition" data-cat="sistem">⚙️ Sistem</button>
                </div>

                <!-- List Kontainer Notifikasi -->
                <div id="notif-items-list" class="max-h-80 overflow-y-auto divide-y divide-white/5">
                    <?php if (empty($initialNotifs)): ?>
                        <div class="p-8 text-center text-slate-400">
                            <span class="text-2xl block mb-1">🎉</span>
                            <span class="text-xs font-bold text-slate-300">Belum ada notifikasi baru</span>
                            <p class="text-[10px] text-slate-500 mt-0.5">Semua persediaan dan transaksi terpantau aman.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($initialNotifs as $nt): 
                            $isUnread = (intval($nt['is_read']) === 0);
                            $typeStyle = match($nt['type']) {
                                'danger'  => 'border-rose-500',
                                'warning' => 'border-amber-500',
                                'success' => 'border-emerald-500',
                                default   => 'border-blue-500'
                            };
                            $icon = match($nt['category']) {
                                'stok_kritis'   => ($nt['type'] === 'danger' ? '🔴' : '⚡'),
                                'transaksi_baru'=> '🛒',
                                'retur_barang'  => '📦',
                                'mutasi_stok'   => '🚚',
                                default         => '🔔'
                            };
                        ?>
                            <div class="notif-item p-3.5 transition flex items-start gap-3 relative cursor-pointer border-l-4 <?= $typeStyle ?> <?= $isUnread ? 'font-semibold' : 'opacity-75' ?>" 
                                 data-id="<?= $nt['id'] ?>" 
                                 data-cat="<?= $nt['category'] ?>"
                                 onclick="handleNotifClick(<?= $nt['id'] ?>, '<?= htmlspecialchars($nt['link_url'] ?? '') ?>')">
                                <span class="text-lg shrink-0 mt-0.5"><?= $icon ?></span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <span class="text-xs font-bold text-white truncate"><?= htmlspecialchars($nt['title']) ?></span>
                                        <span class="text-[9px] text-slate-400 whitespace-nowrap shrink-0"><?= $nt['time_ago'] ?></span>
                                    </div>
                                    <p class="text-[11px] text-slate-300 line-clamp-2 leading-relaxed"><?= htmlspecialchars($nt['message']) ?></p>
                                    <?php if (!empty($nt['link_url'])): ?>
                                        <span class="text-[10px] text-blue-400 font-bold hover:underline inline-block mt-1">Periksa Detail ➔</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isUnread): ?>
                                    <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0 mt-1.5" title="Belum dibaca"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Footer Dropdown -->
                <div class="notif-footer p-2.5 text-center">
                    <a href="dashboard.php?page=stok" class="text-[11px] font-bold text-slate-400 hover:text-white transition">
                        Lihat Manajemen Stok Seluruh Outlet ➔
                    </a>
                </div>
            </div>
        </div>

        <!-- Avatar Profil Admin Khusus & Tombol Cepat -->
        <div class="flex items-center gap-2 pl-2 border-l border-white/10">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-black text-xs shadow-md border border-white/20 shrink-0">
                AK
            </div>
            <div class="hidden sm:block text-left">
                <span class="text-xs font-bold text-white block leading-tight">Admin Khusus</span>
                <span class="text-[9px] text-blue-400 font-mono block">Superadmin TWB</span>
            </div>

            <!-- Tombol Cepat Kasir & Logout -->
            <div class="flex items-center gap-1.5 ml-1">
                <a href="index.php" class="p-1.5 sm:px-2.5 sm:py-1.5 rounded-xl bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white border border-blue-500/30 transition text-xs font-bold flex items-center gap-1" title="Buka Terminal Kasir POS">
                    <span>🛒</span> <span class="hidden sm:inline text-[11px]">Kasir</span>
                </a>
                <a href="dashboard.php?action=logout" class="p-1.5 sm:p-2 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 transition text-xs font-bold flex items-center justify-center" title="Keluar / Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </div>

    </div>
</header>

<!-- JAVASCRIPT LOGIC PUSAT NOTIFIKASI REAL-TIME -->
<script>
let notifDropdownOpen = false;

function toggleNotifDropdown() {
    const menu = document.getElementById('notif-dropdown-menu');
    if (!menu) return;
    notifDropdownOpen = !notifDropdownOpen;
    if (notifDropdownOpen) {
        menu.classList.remove('hidden');
        // Refresh live data saat dropdown dibuka
        fetchLiveNotifications();
    } else {
        menu.classList.add('hidden');
    }
}

// Tutup dropdown jika klik di luar area
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notif-dropdown-wrapper');
    const menu = document.getElementById('notif-dropdown-menu');
    if (wrapper && !wrapper.contains(e.target) && menu && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
        notifDropdownOpen = false;
    }
});

// Filter Kategori Tab
function filterNotifCategory(category) {
    document.querySelectorAll('.notif-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-cat') === category) {
            btn.classList.add('bg-blue-600', 'text-white', 'active-tab');
            btn.classList.remove('hover:bg-white/5');
        } else {
            btn.classList.remove('bg-blue-600', 'text-white', 'active-tab');
            btn.classList.add('hover:bg-white/5');
        }
    });

    const items = document.querySelectorAll('.notif-item');
    items.forEach(it => {
        if (category === 'all' || it.getAttribute('data-cat') === category) {
            it.style.display = 'flex';
        } else {
            it.style.display = 'none';
        }
    });
}

// Klik Notifikasi: Tandai Dibaca & Navigasi
function handleNotifClick(id, linkUrl) {
    fetch('api.php?action=mark_notification_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            updateBadgeCounter(res.data.unread_count);
        }
        if (linkUrl && linkUrl !== '') {
            window.location.href = linkUrl;
        }
    })
    .catch(() => {
        if (linkUrl && linkUrl !== '') {
            window.location.href = linkUrl;
        }
    });
}

// Tandai Semua Notifikasi Sudah Dibaca
function markAllNotifsRead() {
    fetch('api.php?action=mark_all_notifications_read', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            updateBadgeCounter(0);
            document.querySelectorAll('.notif-item').forEach(it => {
                it.classList.remove('font-semibold');
                it.classList.add('opacity-75');
                const dot = it.querySelector('.bg-blue-400');
                if (dot) dot.remove();
            });
            if (typeof showCustomToast === 'function') {
                showCustomToast('Seluruh notifikasi telah ditandai dibaca.', 'success');
            }
        }
    });
}

// Perbarui Tampilan Angka Badge Lonceng
function updateBadgeCounter(count) {
    const badge = document.getElementById('notif-badge-counter');
    if (!badge) return;
    if (count > 0) {
        badge.innerText = (count > 99) ? '99+' : count;
        badge.classList.remove('hidden');
        badge.classList.add('flex');
    } else {
        badge.innerText = '0';
        badge.classList.add('hidden');
        badge.classList.remove('flex');
    }
}

// Ambil data notifikasi real-time dari API
function fetchLiveNotifications() {
    fetch('api.php?action=notifications&limit=20')
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success' && res.data) {
            updateBadgeCounter(res.data.unread_count);
            renderNotificationItems(res.data.notifications);
        }
    })
    .catch(err => console.log('Polling notifikasi silent pass:', err));
}

// Render DOM Notifikasi
function renderNotificationItems(notifs) {
    const list = document.getElementById('notif-items-list');
    if (!list) return;

    if (!notifs || notifs.length === 0) {
        list.innerHTML = `
            <div class="p-8 text-center text-slate-400">
                <span class="text-2xl block mb-1">🎉</span>
                <span class="text-xs font-bold text-slate-300">Belum ada notifikasi baru</span>
                <p class="text-[10px] text-slate-500 mt-0.5">Semua persediaan dan transaksi terpantau aman.</p>
            </div>
        `;
        return;
    }

    let html = '';
    notifs.forEach(nt => {
        const isUnread = (parseInt(nt.is_read) === 0);
        let typeStyle = 'border-blue-500';
        if (nt.type === 'danger') typeStyle = 'border-rose-500';
        else if (nt.type === 'warning') typeStyle = 'border-amber-500';
        else if (nt.type === 'success') typeStyle = 'border-emerald-500';

        let icon = '🔔';
        if (nt.category === 'stok_kritis') icon = (nt.type === 'danger' ? '🔴' : '⚡');
        else if (nt.category === 'transaksi_baru') icon = '🛒';
        else if (nt.category === 'retur_barang') icon = '📦';
        else if (nt.category === 'mutasi_stok') icon = '🚚';

        html += `
            <div class="notif-item p-3.5 transition flex items-start gap-3 relative cursor-pointer border-l-4 ${typeStyle} ${isUnread ? 'font-semibold' : 'opacity-75'}" 
                 data-id="${nt.id}" 
                 data-cat="${nt.category}"
                 onclick="handleNotifClick(${nt.id}, '${nt.link_url || ''}')">
                <span class="text-lg shrink-0 mt-0.5">${icon}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1 mb-0.5">
                        <span class="text-xs font-bold text-white truncate">${escapeHtml(nt.title)}</span>
                        <span class="text-[9px] text-slate-400 whitespace-nowrap shrink-0">${nt.time_ago}</span>
                    </div>
                    <p class="text-[11px] text-slate-300 line-clamp-2 leading-relaxed">${escapeHtml(nt.message)}</p>
                    ${nt.link_url ? '<span class="text-[10px] text-blue-400 font-bold hover:underline inline-block mt-1">Periksa Detail ➔</span>' : ''}
                </div>
                ${isUnread ? '<span class="w-2 h-2 rounded-full bg-blue-400 shrink-0 mt-1.5" title="Belum dibaca"></span>' : ''}
            </div>
        `;
    });

    list.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Polling otomatis setiap 30 detik untuk mendeteksi peringatan baru
setInterval(fetchLiveNotifications, 30000);
</script>
