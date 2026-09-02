    <!-- Modal Dialog Kustom Universal -->
    <?php require_once __DIR__ . '/modal_custom.php'; ?>

    <!-- Helper Scripts -->
    <script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/js/app.js') ?>"></script>
    <script>
        function bukaModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.classList.add('flex');
            }
        }
        function tutupModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                el.classList.remove('flex');
            }
        }

        // Auto-Trigger Notifikasi Modern saat Redirect
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($_GET['error'])): ?>
                if (window.VeloceApp) {
                    VeloceApp.alert(<?= json_encode($_GET['error']) ?>, 'Pemberitahuan Sistem', 'error');
                }
            <?php elseif (!empty($_GET['msg'])): ?>
                if (window.VeloceApp) {
                    const msgMap = {
                        'stock_added': 'Stok berhasil ditambahkan ke Gudang Pusat!',
                        'do_transferred': 'Surat Delivery Order (DO) berhasil diproses!',
                        'returned': 'Retur barang rusak / expired berhasil dicatat!',
                        'deleted': 'Data berhasil dihapus dari sistem!',
                        'created': 'Data baru berhasil ditambahkan!',
                        'updated': 'Perubahan data berhasil diperbarui!'
                    };
                    const text = msgMap[<?= json_encode($_GET['msg']) ?>] || 'Operasi berhasil diproses!';
                    VeloceApp.showToast(text, 'success');
                }
            <?php endif; ?>
        });
    </script>

    <!-- Native Mobile Bottom Navigation Bar (Khusus Layar HP md:hidden) -->
    <nav id="mobile-admin-bottom-nav" class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 backdrop-blur-xl border-t border-white/10 px-2 py-1.5 flex items-center justify-around shadow-2xl">
        <a href="dashboard.php?page=analytics" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'analytics') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">📊</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Analisis</span>
        </a>

        <a href="dashboard.php?page=menu" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'menu') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">📋</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Produk</span>
        </a>

        <a href="dashboard.php?page=stok" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'stok') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">📦</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Stok</span>
        </a>

        <a href="dashboard.php?page=outlet" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'outlet') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">🏪</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Outlet</span>
        </a>

        <a href="dashboard.php?page=kasir" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'kasir') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">👥</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Kasir</span>
        </a>

        <a href="dashboard.php?page=retur" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition duration-150 <?= ($page === 'retur') ? 'text-rose-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
            <span class="text-lg">⚠️</span>
            <span class="text-[10px] mt-0.5 tracking-tight">Retur</span>
        </a>
    </nav>

    <!-- Registrasi Service Worker PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Admin PWA Service Worker terdaftar:', reg.scope))
                    .catch(err => console.log('Admin PWA Service Worker gagal:', err));
            });
        }
    </script>
</body>
</html>
