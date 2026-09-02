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
</body>
</html>
