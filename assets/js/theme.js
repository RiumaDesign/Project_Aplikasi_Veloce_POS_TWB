/**
 * Veloce POS - Theme Manager (Dual Dark & Light Mode)
 * File: assets/js/theme.js
 */

(function() {
    // 1. Ambil tema tersimpan atau default ke 'dark'
    function getStoredTheme() {
        try {
            return localStorage.getItem('veloce_theme') || 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    // 2. Fungsi Utama Terapkan Tema ke DOM
    window.applyTheme = function(theme) {
        const root = document.documentElement;
        root.setAttribute('data-theme', theme);

        if (theme === 'light') {
            root.classList.remove('dark');
            root.classList.add('light');
            if (document.body) {
                document.body.classList.add('light-theme');
                document.body.style.backgroundColor = '#f1f5f9';
                document.body.style.color = '#0f172a';
            }
        } else {
            root.classList.remove('light');
            root.classList.add('dark');
            if (document.body) {
                document.body.classList.remove('light-theme');
                document.body.style.backgroundColor = '';
                document.body.style.color = '';
            }
        }

        try {
            localStorage.setItem('veloce_theme', theme);
        } catch (e) {
            console.warn('Gagal menyimpan preferensi tema:', e);
        }

        updateThemeUI(theme);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
    };

    // 3. Fungsi Toggle Global
    window.toggleTheme = function() {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        const target = (current === 'dark') ? 'light' : 'dark';
        window.applyTheme(target);
    };

    // 4. Update Tampilan Segmented Pill & Icon Toggle
    function updateThemeUI(theme) {
        // A. Segmented Pill Switchers
        const darkPills = document.querySelectorAll('.theme-pill-dark');
        const lightPills = document.querySelectorAll('.theme-pill-light');

        if (theme === 'light') {
            darkPills.forEach(el => {
                el.className = 'theme-pill-dark flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition text-slate-500 hover:text-slate-900';
            });
            lightPills.forEach(el => {
                el.className = 'theme-pill-light flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition bg-amber-400 text-slate-950 shadow-md font-black';
            });
        } else {
            darkPills.forEach(el => {
                el.className = 'theme-pill-dark flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition bg-blue-600 text-white shadow-md font-black';
            });
            lightPills.forEach(el => {
                el.className = 'theme-pill-light flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition text-slate-400 hover:text-white';
            });
        }

        // B. Fallback Label & Icon
        const themeLabels = document.querySelectorAll('.theme-toggle-label');
        const themeIcons = document.querySelectorAll('.theme-toggle-icon');
        themeLabels.forEach(el => {
            el.textContent = (theme === 'dark') ? 'Mode Gelap' : 'Mode Terang';
        });
        themeIcons.forEach(el => {
            el.textContent = (theme === 'dark') ? '🌙' : '☀️';
        });
    }

    // 5. Inisialisasi Segera (sebelum render body untuk cegah flicker)
    const initialTheme = getStoredTheme();
    document.documentElement.setAttribute('data-theme', initialTheme);
    if (initialTheme === 'light') {
        document.documentElement.classList.add('light');
    } else {
        document.documentElement.classList.add('dark');
    }

    // 6. Sinkronisasi UI setelah elemen siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.applyTheme(initialTheme);
        });
    } else {
        window.applyTheme(initialTheme);
    }
})();
