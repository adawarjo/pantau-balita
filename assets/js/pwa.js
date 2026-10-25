/* Pantau Balita — Progressive Web App: service worker, tombol pasang, status koneksi */
(function () {
    'use strict';

    const base = (window.APP_BASE || '').replace(/\/$/, '');

    // ---- Daftarkan service worker (hanya HTTPS atau localhost) ----
    if ('serviceWorker' in navigator && (window.isSecureContext || location.hostname === 'localhost')) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register(base + '/sw.js', { scope: base + '/' }).then((reg) => {
                // Versi baru tersedia saat aplikasi sudah berjalan
                reg.addEventListener('updatefound', () => {
                    const sw = reg.installing;
                    if (!sw) return;
                    sw.addEventListener('statechange', () => {
                        if (sw.state === 'installed' && navigator.serviceWorker.controller && window.Swal) {
                            Swal.fire({ toast: true, position: 'bottom', icon: 'info', title: 'Versi baru aplikasi siap', text: 'Muat ulang untuk memperbarui.',
                                showConfirmButton: true, confirmButtonText: 'Muat ulang', confirmButtonColor: '#2a9d8f', timer: 15000 })
                                .then((r) => { if (r.isConfirmed) location.reload(); });
                        }
                    });
                });
            }).catch((err) => console.warn('Service worker gagal didaftarkan:', err));
        });
    }

    // ---- Tombol "Pasang Aplikasi" ----
    let deferredPrompt = null;
    const installBtns = () => document.querySelectorAll('.js-install-app');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

    function showInstall(show) {
        installBtns().forEach((b) => b.classList.toggle('d-none', !show));
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstall(true);
    });

    // iOS Safari tidak punya prompt otomatis: tampilkan petunjuk
    if (isIOS && !isStandalone) {
        showInstall(true);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-install-app');
        if (!btn) return;
        e.preventDefault();
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (choice.outcome === 'accepted') showInstall(false);
        } else if (isIOS && window.Swal) {
            Swal.fire({
                title: 'Pasang di iPhone/iPad',
                html: 'Ketuk tombol <strong>Bagikan</strong> <i class="fa-solid fa-arrow-up-from-bracket"></i> di Safari, lalu pilih <strong>Tambah ke Layar Utama</strong>.',
                icon: 'info', confirmButtonColor: '#2a9d8f'
            });
        }
    });

    window.addEventListener('appinstalled', () => {
        showInstall(false);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Aplikasi berhasil dipasang', showConfirmButton: false, timer: 3000 });
    });

    if (isStandalone) {
        document.documentElement.classList.add('is-standalone');
        showInstall(false);
    }

    // ---- Banner status koneksi ----
    const banner = document.createElement('div');
    banner.className = 'offline-banner';
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.innerHTML = '<i class="fa-solid fa-wifi me-2"></i><span>Anda sedang offline. Perubahan tidak dapat disimpan sampai koneksi kembali.</span>';
    document.body.appendChild(banner);

    function updateOnline() {
        const offline = !navigator.onLine;
        banner.classList.toggle('show', offline);
        document.body.classList.toggle('is-offline', offline);
    }
    window.addEventListener('online', () => {
        updateOnline();
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Koneksi kembali', showConfirmButton: false, timer: 2000 });
    });
    window.addEventListener('offline', updateOnline);
    updateOnline();

    // Cegah kirim formulir saat offline (data tidak akan tersimpan)
    document.addEventListener('submit', (e) => {
        const f = e.target;
        if (!navigator.onLine && f.method && f.method.toLowerCase() === 'post') {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (window.Swal) Swal.fire({ icon: 'warning', title: 'Tidak ada koneksi', text: 'Isian Anda tetap di halaman ini. Kirim ulang setelah koneksi kembali.', confirmButtonColor: '#2a9d8f' });
        }
    }, true);
})();
