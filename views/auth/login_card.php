<!-- ======================================================================== -->
<!-- KARTU LOGIN INTERAKTIF DUAL-MODE (KASIR & ADMIN) — VELOCE POS TWB       -->
<!-- ======================================================================== -->
<div id="twb-login-card" class="w-full max-w-lg rounded-3xl p-6 sm:p-8 relative z-10 shadow-2xl transition-all duration-300">
    
    <!-- Logo & Header Branding -->
    <div class="text-center mb-5">
        <div class="inline-flex items-center justify-center p-3 rounded-2xl shadow-md border border-slate-200 dark:border-white/10 mb-2.5" style="background-color: #ffffff;">
            <img src="assets/images/logo_twb.png" alt="Logo Resmi TWB" class="h-11 w-auto object-contain mx-auto">
        </div>
        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center justify-center gap-1.5">
            TWB <span class="text-blue-600 dark:text-blue-400">POS</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-bold">Veloce</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Sistem Kasir & Vending Machine PT Taman Wisata Borobudur</p>
    </div>

    <!-- Peringatan Error (Jika Ada) -->
    <?php if (!empty($error)): ?>
        <div class="p-3.5 rounded-2xl mb-4.5 flex items-center gap-2.5 text-xs font-semibold shadow-sm animate-shake" style="background-color: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b;">
            <span class="text-base">⚠️</span> 
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Segmented Tab Switcher (Swap Kasir vs Admin) -->
    <div class="grid grid-cols-2 p-1.5 rounded-2xl mb-5 border transition-all" style="background-color: #f1f5f9; border-color: #cbd5e1;" id="twb-tab-wrapper">
        <button type="button" onclick="switchLoginTab('kasir')" id="tab-btn-kasir" class="py-2.5 px-3 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-sm">
            <span>🛒</span>
            <span>Petugas Kasir</span>
        </button>
        <button type="button" onclick="switchLoginTab('admin')" id="tab-btn-admin" class="py-2.5 px-3 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-2 cursor-pointer">
            <span>🛡️</span>
            <span>Administrator</span>
        </button>
    </div>

    <!-- ==================================================================== -->
    <!-- 1. FORM KHUSUS PETUGAS KASIR (QUICK PROFILE SELECTOR)                -->
    <!-- ==================================================================== -->
    <form method="POST" id="form-login-kasir" class="space-y-4">
        <input type="hidden" name="login_type" value="kasir">
        <input type="hidden" name="username" id="input-kasir-username" value="<?= htmlspecialchars($kasir_list[0]['nama'] ?? 'Andi Wijaya') ?>">

        <!-- Bagian Pilihan Profil Kasir Cepat -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    Pilih Petugas Kasir:
                </label>
                <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400">Klik nama Anda</span>
            </div>

            <!-- Grid Kartu Profil Kasir Cepat -->
            <div class="grid grid-cols-3 gap-2.5 mb-2.5" id="kasir-cards-grid">
                <?php foreach ($kasir_list as $idx => $k): 
                    $initials = '';
                    $parts = explode(' ', trim($k['nama']));
                    foreach ($parts as $p) {
                        if (!empty($p)) $initials .= strtoupper($p[0]);
                    }
                    $initials = substr($initials, 0, 2);
                    $isActive = ($idx === 0);
                ?>
                    <button type="button" onclick="selectKasirCard('<?= htmlspecialchars($k['nama']) ?>', this)" 
                            class="kasir-profile-card p-2.5 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center gap-1.5 <?= $isActive ? 'active-kasir-card' : '' ?>"
                            data-name="<?= htmlspecialchars($k['nama']) ?>">
                        <!-- Avatar Inisial Bulat -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-xs shadow-sm avatar-circle">
                            <?= $initials ?>
                        </div>
                        <span class="text-xs font-extrabold block truncate w-full kasir-name-label">
                            <?= htmlspecialchars($k['nama']) ?>
                        </span>
                        <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider kasir-role-badge">
                            Kasir
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Dropdown Sinkron (Fallback / Tambahan) -->
            <div class="relative">
                <select id="select-kasir-dropdown" onchange="selectKasirFromDropdown(this.value)" 
                        class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/15 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                    <?php foreach ($kasir_list as $k): ?>
                        <option value="<?= htmlspecialchars($k['nama']) ?>">
                            Petugas: <?= htmlspecialchars($k['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Input Kata Sandi Kasir -->
        <div>
            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Kata Sandi Kasir:
            </label>
            <div class="relative">
                <input type="password" name="password" id="input-kasir-pass" required placeholder="Masukkan kata sandi..." 
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/15 rounded-xl pl-4 pr-10 py-2.5 text-xs font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 shadow-sm transition">
                <button type="button" onclick="togglePasswordVisibility('input-kasir-pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1">
                    👁️
                </button>
            </div>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-medium">*Default password kasir: <span class="font-mono font-bold text-blue-600 dark:text-blue-400">kasir123</span></p>
        </div>

        <!-- Dropdown Lokasi Terminal / Outlet -->
        <div>
            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Lokasi Terminal / Outlet POS:
            </label>
            <select name="outlet_id" id="select-outlet-kasir" onchange="rememberTerminal(this.value)" 
                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/15 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 dark:text-white focus:outline-none focus:border-blue-500 shadow-sm transition">
                <?php foreach ($outlets_list as $loc): ?>
                    <option value="<?= $loc['id'] ?>">
                        [<?= $loc['code'] ?>] <?= htmlspecialchars($loc['name']) ?> (<?= strtoupper($loc['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Sistem akan mengingat terminal yang sering Anda gunakan di perangkat ini.</span>
        </div>

        <!-- Tombol Masuk Kasir -->
        <button type="submit" class="w-full py-3 rounded-2xl text-xs font-black transition-all shadow-md cursor-pointer flex items-center justify-center gap-2" style="background-color: #2563eb; color: #ffffff;">
            <span>Buka Kasir POS</span>
            <span>➔</span>
        </button>
    </form>

    <!-- ==================================================================== -->
    <!-- 2. FORM KHUSUS ADMINISTRATOR (MANAJEMEN & LAPORAN)                   -->
    <!-- ==================================================================== -->
    <form method="POST" id="form-login-admin" class="space-y-4 hidden">
        <input type="hidden" name="login_type" value="admin">

        <div class="p-3 rounded-2xl flex items-center gap-2.5 text-xs font-semibold" style="background-color: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af;">
            <span class="text-base">🛡️</span>
            <span>Panel Khusus Manajer, Keuangan & Supervisor Multi-Outlet</span>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Nama Pengguna / Akun Admin:
            </label>
            <input type="text" name="username" id="input-admin-username" placeholder="Masukkan username admin (contoh: Admin)" 
                   class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/15 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 shadow-sm transition">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Kata Sandi Administrator:
            </label>
            <div class="relative">
                <input type="password" name="password" id="input-admin-pass" placeholder="••••••••" 
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/15 rounded-xl pl-4 pr-10 py-2.5 text-xs font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 shadow-sm transition">
                <button type="button" onclick="togglePasswordVisibility('input-admin-pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1">
                    👁️
                </button>
            </div>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">*Akun default: <span class="font-mono font-bold text-blue-600 dark:text-blue-400">admin</span> / <span class="font-mono font-bold text-blue-600 dark:text-blue-400">admin123</span></p>
        </div>

        <button type="submit" class="w-full py-3 rounded-2xl text-xs font-black transition-all shadow-md cursor-pointer flex items-center justify-center gap-2" style="background-color: #4338ca; color: #ffffff;">
            <span>Masuk Panel Admin & Laporan</span>
            <span>➔</span>
        </button>
    </form>

</div>

<!-- ======================================================================== -->
<!-- CSS SOLID & STYLING KARTU LOGIN                                           -->
<!-- ======================================================================== -->
<style>
/* TEMA TERANG: SOLID PUTIH BERSIH */
html[data-theme="light"] #twb-login-card,
html.light #twb-login-card,
body.light-theme #twb-login-card,
html:not(.dark) #twb-login-card {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #0f172a !important;
}

/* TEMA GELAP: SOLID DARK SLATE */
html.dark #twb-login-card,
html[data-theme="dark"] #twb-login-card {
    background-color: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #ffffff !important;
}

/* KARTU PROFIL KASIR (NORMAL) */
.kasir-profile-card {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    color: #334155;
}
.kasir-profile-card .avatar-circle {
    background-color: #e2e8f0;
    color: #1e293b;
}
.kasir-profile-card .kasir-role-badge {
    background-color: #e2e8f0;
    color: #475569;
}
.kasir-profile-card:hover {
    border-color: #93c5fd;
    background-color: #eff6ff;
}

/* KARTU PROFIL KASIR (AKTIF / TERPILIH) */
.kasir-profile-card.active-kasir-card {
    background-color: #eff6ff !important;
    border-color: #3b82f6 !important;
    border-width: 2px !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}
.kasir-profile-card.active-kasir-card .avatar-circle {
    background-color: #2563eb !important;
    color: #ffffff !important;
}
.kasir-profile-card.active-kasir-card .kasir-name-label {
    color: #1d4ed8 !important;
    font-weight: 900 !important;
}
.kasir-profile-card.active-kasir-card .kasir-role-badge {
    background-color: #dbeafe !important;
    color: #1e40af !important;
}

/* DARK MODE OVERRIDES PROFIL KASIR */
html.dark .kasir-profile-card,
html[data-theme="dark"] .kasir-profile-card {
    background-color: #1e293b;
    border-color: #334155;
    color: #e2e8f0;
}
html.dark .kasir-profile-card .avatar-circle,
html[data-theme="dark"] .kasir-profile-card .avatar-circle {
    background-color: #334155;
    color: #f8fafc;
}
html.dark .kasir-profile-card .kasir-role-badge,
html[data-theme="dark"] .kasir-profile-card .kasir-role-badge {
    background-color: #334155;
    color: #94a3b8;
}
html.dark .kasir-profile-card.active-kasir-card,
html[data-theme="dark"] .kasir-profile-card.active-kasir-card {
    background-color: #1e3a8a !important;
    border-color: #60a5fa !important;
}
html.dark .kasir-profile-card.active-kasir-card .kasir-name-label,
html[data-theme="dark"] .kasir-profile-card.active-kasir-card .kasir-name-label {
    color: #ffffff !important;
}
</style>

<!-- ======================================================================== -->
<!-- JAVASCRIPT INTERAKSI TAB & QUICK SELECT KASIR                           -->
<!-- ======================================================================== -->
<script>
/**
 * Switch antara Tab Petugas Kasir dan Administrator
 */
function switchLoginTab(type) {
    const btnKasir = document.getElementById('tab-btn-kasir');
    const btnAdmin = document.getElementById('tab-btn-admin');
    const formKasir = document.getElementById('form-login-kasir');
    const formAdmin = document.getElementById('form-login-admin');

    if (type === 'admin') {
        formKasir.classList.add('hidden');
        formAdmin.classList.remove('hidden');

        // Style Tab Admin Aktif
        btnAdmin.style.backgroundColor = '#ffffff';
        btnAdmin.style.color = '#4338ca';
        btnAdmin.classList.add('shadow-sm');

        btnKasir.style.backgroundColor = 'transparent';
        btnKasir.style.color = '#64748b';
        btnKasir.classList.remove('shadow-sm');

        document.getElementById('input-admin-username').focus();
    } else {
        formAdmin.classList.add('hidden');
        formKasir.classList.remove('hidden');

        // Style Tab Kasir Aktif
        btnKasir.style.backgroundColor = '#ffffff';
        btnKasir.style.color = '#2563eb';
        btnKasir.classList.add('shadow-sm');

        btnAdmin.style.backgroundColor = 'transparent';
        btnAdmin.style.color = '#64748b';
        btnAdmin.classList.remove('shadow-sm');

        document.getElementById('input-kasir-pass').focus();
    }
}

/**
 * Pilih Profil Kasir saat Kartu Avatar Diklik
 */
function selectKasirCard(namaKasir, element) {
    // 1. Update hidden username input
    document.getElementById('input-kasir-username').value = namaKasir;

    // 2. Update sinkronisasi dropdown
    const selectDropdown = document.getElementById('select-kasir-dropdown');
    if (selectDropdown) selectDropdown.value = namaKasir;

    // 3. Highlight kartu yang aktif
    document.querySelectorAll('.kasir-profile-card').forEach(c => c.classList.remove('active-kasir-card'));
    if (element) {
        element.classList.add('active-kasir-card');
    }

    // 4. Fokus langsung ke input password untuk pengetikan cepat
    const passInput = document.getElementById('input-kasir-pass');
    passInput.focus();
}

/**
 * Sinkronisasi saat kasir dipilih dari dropdown
 */
function selectKasirFromDropdown(namaKasir) {
    document.getElementById('input-kasir-username').value = namaKasir;

    document.querySelectorAll('.kasir-profile-card').forEach(c => {
        if (c.getAttribute('data-name') === namaKasir) {
            c.classList.add('active-kasir-card');
        } else {
            c.classList.remove('active-kasir-card');
        }
    });

    document.getElementById('input-kasir-pass').focus();
}

/**
 * Toggle tampilkan / sembunyikan password
 */
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🔒';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

/**
 * Ingat lokasi terminal yang dipilih di localStorage
 */
function rememberTerminal(outletId) {
    try {
        localStorage.setItem('veloce_last_outlet', outletId);
    } catch(e){}
}

// Inisialisasi awal saat halaman termuat
document.addEventListener('DOMContentLoaded', () => {
    // Set tab kasir aktif secara default
    switchLoginTab('<?= htmlspecialchars($login_type) ?>');

    // Pulihkan terminal terakhir jika ada di localStorage
    try {
        const lastOutlet = localStorage.getItem('veloce_last_outlet');
        const outletSelect = document.getElementById('select-outlet-kasir');
        if (lastOutlet && outletSelect) {
            outletSelect.value = lastOutlet;
        }
    } catch(e){}
});
</script>
