/**
 * Veloce POS - Frontend Core Helper & Custom Dialog System
 * File: assets/js/app.js
 */

window.VeloceApp = {
    formatRupiah: function(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    },

    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColors = {
            success: 'bg-emerald-600 text-white shadow-emerald-600/30',
            error: 'bg-rose-600 text-white shadow-rose-600/30',
            warning: 'bg-amber-500 text-white shadow-amber-500/30',
            info: 'bg-blue-600 text-white shadow-blue-600/30'
        };
        toast.className = `fixed bottom-6 right-6 z-[99999] px-5 py-3 rounded-2xl shadow-xl text-xs font-bold transition-all duration-300 transform translate-y-2 opacity-0 flex items-center gap-2 ${bgColors[type] || bgColors.info} border border-white/20`;
        toast.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    /**
     * Modal Dialog Konfirmasi Modern (Pengganti confirm() native)
     */
    confirm: function(options = {}) {
        return new Promise((resolve) => {
            const config = {
                title: options.title || 'Konfirmasi Tindakan',
                message: options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                icon: options.icon || '❓',
                type: options.type || 'info', // 'danger', 'warning', 'info', 'success'
                confirmText: options.confirmText || 'Ya, Lanjutkan',
                cancelText: options.cancelText || 'Batal',
                ...options
            };

            // Dapatkan atau buat kontainer modal
            let backdrop = document.getElementById('twb-custom-modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'twb-custom-modal-backdrop';
                backdrop.className = 'twb-modal-backdrop';
                backdrop.innerHTML = `
                    <div id="twb-custom-modal-card" class="twb-modal-card">
                        <div id="twb-modal-glow-line" class="twb-modal-glow-line"></div>
                        <div id="twb-modal-icon-container" class="twb-modal-icon-container">
                            <span id="twb-modal-icon-text">🚪</span>
                        </div>
                        <h3 id="twb-modal-heading" class="twb-modal-heading"></h3>
                        <p id="twb-modal-subtext" class="twb-modal-subtext"></p>
                        <div id="twb-modal-btn-grid" class="twb-modal-btn-grid">
                            <button id="twb-modal-btn-no" type="button" class="twb-modal-btn-no">Batal</button>
                            <button id="twb-modal-btn-yes" type="button" class="twb-modal-btn-yes">
                                <span id="twb-modal-btn-yes-label">Ya</span>
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(backdrop);
            }

            const card = document.getElementById('twb-custom-modal-card');
            const glowLine = document.getElementById('twb-modal-glow-line');
            const iconContainer = document.getElementById('twb-modal-icon-container');
            const iconText = document.getElementById('twb-modal-icon-text');
            const heading = document.getElementById('twb-modal-heading');
            const subtext = document.getElementById('twb-modal-subtext');
            const btnNo = document.getElementById('twb-modal-btn-no');
            const btnYes = document.getElementById('twb-modal-btn-yes');
            const btnYesLabel = document.getElementById('twb-modal-btn-yes-label');
            const btnGrid = document.getElementById('twb-modal-btn-grid');

            // Set Konten
            if (iconText) iconText.textContent = config.icon;
            if (heading) heading.textContent = config.title;
            if (subtext) subtext.textContent = config.message;
            if (btnYesLabel) btnYesLabel.textContent = config.confirmText;
            if (btnNo) {
                btnNo.textContent = config.cancelText;
                btnNo.style.display = 'block';
            }
            if (btnGrid) {
                btnGrid.style.gridTemplateColumns = '1fr 1fr';
            }

            // Styling berdasarkan tipe
            if (glowLine && iconContainer && btnYes) {
                if (config.type === 'danger') {
                    glowLine.style.background = 'linear-gradient(90deg, #f43f5e, #ef4444)';
                    iconContainer.style.background = 'rgba(244, 63, 94, 0.12)';
                    iconContainer.style.borderColor = 'rgba(244, 63, 94, 0.25)';
                    btnYes.style.background = '#e11d48';
                    btnYes.style.boxShadow = '0 4px 14px rgba(225, 29, 72, 0.4)';
                } else if (config.type === 'warning') {
                    glowLine.style.background = 'linear-gradient(90deg, #f59e0b, #eab308)';
                    iconContainer.style.background = 'rgba(245, 158, 11, 0.12)';
                    iconContainer.style.borderColor = 'rgba(245, 158, 11, 0.25)';
                    btnYes.style.background = '#d97706';
                    btnYes.style.boxShadow = '0 4px 14px rgba(217, 119, 6, 0.4)';
                } else if (config.type === 'success') {
                    glowLine.style.background = 'linear-gradient(90deg, #10b981, #14b8a6)';
                    iconContainer.style.background = 'rgba(16, 185, 129, 0.12)';
                    iconContainer.style.borderColor = 'rgba(16, 185, 129, 0.25)';
                    btnYes.style.background = '#059669';
                    btnYes.style.boxShadow = '0 4px 14px rgba(5, 150, 105, 0.4)';
                } else {
                    glowLine.style.background = 'linear-gradient(90deg, #3b82f6, #6366f1)';
                    iconContainer.style.background = 'rgba(59, 130, 246, 0.12)';
                    iconContainer.style.borderColor = 'rgba(59, 130, 246, 0.25)';
                    btnYes.style.background = '#2563eb';
                    btnYes.style.boxShadow = '0 4px 14px rgba(37, 99, 235, 0.4)';
                }
            }

            // Fungsi Tutup
            function closeModal() {
                backdrop.classList.remove('is-active');
                backdrop.style.display = 'none';
                document.removeEventListener('keydown', handleKey);
            }

            function handleKey(e) {
                if (e.key === 'Escape') {
                    closeModal();
                    if (typeof config.onCancel === 'function') config.onCancel();
                    resolve(false);
                }
            }
            document.addEventListener('keydown', handleKey);

            backdrop.onclick = function(e) {
                if (e.target === backdrop) {
                    closeModal();
                    if (typeof config.onCancel === 'function') config.onCancel();
                    resolve(false);
                }
            };

            btnNo.onclick = function() {
                closeModal();
                if (typeof config.onCancel === 'function') config.onCancel();
                resolve(false);
            };

            btnYes.onclick = function() {
                closeModal();
                if (typeof config.onConfirm === 'function') config.onConfirm();
                resolve(true);
            };

            // TAMPILKAN MODAL
            backdrop.classList.add('is-active');
            backdrop.style.display = 'flex';
            setTimeout(() => {
                if (btnYes) btnYes.focus();
            }, 30);
        });
    },

    /**
     * Modal Dialog Alert Modern (Pengganti alert() native)
     */
    alert: function(message, title = 'Pemberitahuan', type = 'info') {
        return new Promise((resolve) => {
            const icons = {
                error: '⚠️',
                warning: '⚠️',
                success: '✅',
                info: 'ℹ️'
            };
            VeloceApp.confirm({
                title: title,
                message: message,
                icon: icons[type] || 'ℹ️',
                type: (type === 'error') ? 'danger' : type,
                confirmText: 'Mengerti ✓',
                cancelText: ''
            }).then(() => {
                resolve();
            });

            // Sembunyikan tombol batal untuk mode alert tunggal
            const btnNo = document.getElementById('twb-modal-btn-no');
            const btnGrid = document.getElementById('twb-modal-btn-grid');
            if (btnNo) btnNo.style.display = 'none';
            if (btnGrid) btnGrid.style.gridTemplateColumns = '1fr';
        });
    }
};

/**
 * HELPER UNIVERSAL GLOBAL UNTUK ONCLICK / ONSUBMIT (100% BEBAS EROR)
 */

// 1. Konfirmasi Logout Admin Khusus
window.confirmLogoutAdmin = function(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
        event.stopPropagation();
    }
    VeloceApp.confirm({
        title: 'Keluar Sesi Admin Khusus?',
        message: 'Apakah Anda yakin ingin mengakhiri sesi Admin Khusus saat ini?',
        icon: '🚪',
        type: 'danger',
        confirmText: 'Ya, Keluar Sesi',
        cancelText: 'Tetap di Sini'
    }).then((confirmed) => {
        if (confirmed) {
            window.location.href = 'dashboard.php?action=logout';
        }
    });
    return false;
};

// 2. Konfirmasi Logout Kasir POS
window.confirmLogoutKasir = function(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
        event.stopPropagation();
    }
    VeloceApp.confirm({
        title: 'Keluar Sesi Kasir?',
        message: 'Pastikan seluruh pembayaran transaksi telah selesai sebelum keluar.',
        icon: '🚪',
        type: 'danger',
        confirmText: 'Ya, Keluar Kasir',
        cancelText: 'Lanjut Transaksi'
    }).then((confirmed) => {
        if (confirmed) {
            window.location.href = 'logout.php';
        }
    });
    return false;
};

// 3. Konfirmasi Submit Form Hapus / Aksi
window.confirmSubmitForm = function(event, form, options = {}) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
        event.stopPropagation();
    }
    VeloceApp.confirm({
        title: options.title || 'Konfirmasi Hapus',
        message: options.message || 'Apakah Anda yakin ingin menghapus data ini?',
        icon: options.icon || '🗑️',
        type: options.type || 'danger',
        confirmText: options.confirmText || 'Ya, Hapus',
        cancelText: 'Batal'
    }).then((confirmed) => {
        if (confirmed && form) {
            form.submit();
        }
    });
    return false;
};

// 4. Delegasi Event Terpusat (Menjamin klik pada tombol selalu tertangkap)
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        // Tangkap tombol logout sidebar secara khusus
        const logoutAdminBtn = e.target.closest('.sidebar-logout-btn');
        if (logoutAdminBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.confirmLogoutAdmin(e);
            return;
        }

        // Tangkap elemen dengan data-confirm
        const trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;

        e.preventDefault();
        e.stopPropagation();

        const message = trigger.getAttribute('data-confirm');
        const title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
        const type = trigger.getAttribute('data-confirm-type') || 'danger';
        const icon = trigger.getAttribute('data-confirm-icon') || (type === 'danger' ? '🗑️' : '❓');
        const confirmText = trigger.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';

        VeloceApp.confirm({
            title: title,
            message: message,
            icon: icon,
            type: type,
            confirmText: confirmText
        }).then((confirmed) => {
            if (confirmed) {
                if (trigger.tagName === 'A') {
                    window.location.href = trigger.href;
                } else if (trigger.form) {
                    trigger.form.submit();
                } else if (trigger.closest('form')) {
                    trigger.closest('form').submit();
                }
            }
        });
    }, true);
});


