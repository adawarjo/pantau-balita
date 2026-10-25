/* Pantau Balita — interaksi umum */
(function () {
    'use strict';

    /* =====================================================
     * Skeleton loading
     * Saat berpindah halaman, isi konten diganti kerangka abu-abu
     * berkilau yang meniru tata letak halaman tujuan.
     * ===================================================== */
    const content = document.querySelector('.layout-app .content');
    let skeletonTimer = null;
    let safetyTimer = null;

    const line = (w, cls) => '<div class="sk sk-line ' + (cls || '') + '" style="width:' + w + '"></div>';
    const block = (h, cls) => '<div class="sk sk-block ' + (cls || '') + '" style="height:' + h + 'px"></div>';
    const card = (inner, cls) => '<div class="card sk-card ' + (cls || '') + '"><div class="card-body">' + inner + '</div></div>';
    const repeat = (n, fn) => Array.from({ length: n }, (_, i) => fn(i)).join('');

    const statRow = () => '<div class="row g-3 mb-3">' + repeat(4, () =>
        '<div class="col-6 col-md-3">' + card('<div class="d-flex align-items-center gap-3"><div class="sk sk-icon"></div><div class="flex-fill">' + line('40%', 'sk-lg') + line('75%') + '</div></div>') + '</div>') + '</div>';

    const listRows = (n) => repeat(n, (i) =>
        '<div class="sk-row"><div class="sk sk-icon sk-icon-sm"></div><div class="flex-fill">' + line((55 + (i * 13) % 35) + '%') + line((30 + (i * 17) % 30) + '%', 'sk-sm') + '</div><div class="sk sk-btn"></div></div>');

    const templates = {
        dashboard: () =>
            line('180px', 'sk-xl mb-2') + line('130px', 'mb-4') + statRow() +
            '<div class="sk-quick mb-4">' + repeat(5, () => '<div class="sk sk-tile"></div>') + '</div>' +
            '<div class="row g-3 mb-3">' + repeat(3, () => '<div class="col-md-6 col-xl-4">' +
                card('<div class="d-flex gap-3 align-items-center mb-3"><div class="sk sk-avatar"></div><div class="flex-fill">' + line('70%') + line('45%', 'sk-sm') + '</div></div>' +
                     '<div class="row g-2 mb-2"><div class="col-6">' + block(52) + '</div><div class="col-6">' + block(52) + '</div></div>' + line('80%', 'sk-sm') + line('65%', 'sk-sm')) + '</div>') + '</div>',
        list: () =>
            '<div class="d-flex justify-content-between mb-3">' + line('220px') + '<div class="sk sk-btn sk-btn-lg"></div></div>' +
            card('<div class="row g-2">' + repeat(4, () => '<div class="col-6 col-md-3">' + line('40%', 'sk-sm') + block(38) + '</div>') + '</div>', 'mb-3') +
            card(listRows(6)),
        form: () =>
            '<div class="row justify-content-center"><div class="col-xl-9">' +
            card('<div class="row g-3">' + repeat(6, (i) => '<div class="' + (i < 2 ? 'col-md-6' : 'col-md-4') + '">' + line('35%', 'sk-sm') + block(42) + '</div>') +
                 '<div class="col-12">' + line('25%', 'sk-sm') + block(72) + '</div></div><div class="d-flex gap-2 mt-4"><div class="sk sk-btn sk-btn-lg"></div><div class="sk sk-btn"></div></div>') +
            '</div></div>',
        chart: () =>
            '<div class="d-flex gap-2 mb-3">' + line('60px') + '<div class="sk sk-btn sk-btn-lg"></div></div>' +
            card(line('45%', 'mb-3') + repeat(5, () => '<div class="d-flex gap-3 mb-2">' + line('40%') + line('15%') + line('20%') + '</div>'), 'mb-3') +
            '<div class="row g-3">' + repeat(2, () => '<div class="col-lg-6">' + card(line('40%', 'mb-3') + '<div class="sk sk-chart"></div>') + '</div>') + '</div>',
        changelog: () =>
            '<div class="row justify-content-center"><div class="col-xl-9">' +
            card('<div class="d-flex gap-3 align-items-center"><div class="sk sk-icon"></div><div class="flex-fill">' + line('30%', 'sk-lg') + line('45%', 'sk-sm') + '</div></div>', 'mb-3') +
            '<div class="d-flex gap-2 mb-3">' + repeat(4, () => '<div class="sk sk-btn"></div>') + '</div>' +
            repeat(2, () => card(line('50%', 'mb-3') + repeat(4, (i) => '<div class="d-flex gap-2 mb-2"><div class="sk sk-btn"></div>' + line((60 + i * 9) + '%') + '</div>'), 'mb-3')) +
            '</div></div>',
        detail: () =>
            '<div class="row g-3"><div class="col-lg-4">' + card('<div class="text-center"><div class="sk sk-avatar sk-avatar-lg mx-auto mb-3"></div>' + line('60%', 'mx-auto') + line('40%', 'mx-auto sk-sm') + line('50%', 'mx-auto sk-lg mt-3') + '</div>' + repeat(3, () => line('100%', 'mt-3'))) + '</div>' +
            '<div class="col-lg-8"><div class="sk-quick mb-3">' + repeat(4, () => '<div class="sk sk-tile"></div>') + '</div>' + card(line('35%', 'mb-3') + '<div class="row g-2 mb-3">' + repeat(4, () => '<div class="col-6 col-md-3">' + block(56) + '</div>') + '</div>' + listRows(3)) + '</div></div>'
    };

    function skeletonType(url) {
        let path = '';
        try { path = new URL(url, location.href).pathname; } catch (e) { path = String(url); }
        if (/dashboard\.php$|\/$|index\.php$/.test(path) && !/\/(children|growth|development|immunization|health|reminders|reports)\//.test(path)) return 'dashboard';
        if (/(create|edit|observe)\.php$|profile\.php$/.test(path)) return 'form';
        if (/(chart|print)\.php$/.test(path)) return 'chart';
        if (/changelog\.php$/.test(path)) return 'changelog';
        if (/detail\.php$/.test(path)) return 'detail';
        return 'list';
    }

    function showSkeleton(url) {
        if (!content || document.body.classList.contains('is-navigating')) return;
        clearTimeout(skeletonTimer);
        // Jeda singkat agar halaman yang cepat dimuat tidak berkedip
        skeletonTimer = setTimeout(() => {
            const wrap = document.createElement('div');
            wrap.className = 'skeleton-screen';
            wrap.setAttribute('aria-hidden', 'true');
            wrap.innerHTML = (templates[skeletonType(url || location.href)] || templates.list)();
            content.prepend(wrap);
            document.body.classList.add('is-navigating');
            content.setAttribute('aria-busy', 'true');
            window.scrollTo({ top: 0 });
        }, 120);
        // Pengaman: unduhan file atau navigasi yang batal tidak membuat skeleton tertahan
        clearTimeout(safetyTimer);
        safetyTimer = setTimeout(hideSkeleton, 12000);
    }

    function hideSkeleton() {
        clearTimeout(skeletonTimer);
        clearTimeout(safetyTimer);
        document.body.classList.remove('is-navigating');
        if (content) {
            content.removeAttribute('aria-busy');
            content.querySelectorAll('.skeleton-screen').forEach((el) => el.remove());
        }
        // Pulihkan tombol yang sempat dinonaktifkan (kembali via tombol Back)
        document.querySelectorAll('button[data-original]').forEach((b) => {
            b.disabled = false;
            b.innerHTML = b.dataset.original;
            delete b.dataset.original;
        });
    }
    window.addEventListener('pageshow', hideSkeleton);
    window.PB = { showSkeleton, hideSkeleton };

    // Klik tautan internal -> skeleton halaman tujuan
    document.addEventListener('click', (ev) => {
        if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
        const a = ev.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download') || a.dataset.noSkeleton !== undefined) return;
        if (a.dataset.bsToggle) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin) return;
        if (/export\.php$|photo\.php$/.test(url.pathname)) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
        showSkeleton(url.href);
    });

    const showLoader = (url) => showSkeleton(url);

    // ---- Notifikasi hasil operasi (flash) ----
    if (window.APP_FLASH && window.Swal) {
        const f = window.APP_FLASH;
        const icon = { success: 'success', error: 'error', info: 'info', warning: 'warning' }[f.type] || 'info';
        if (icon === 'success' || icon === 'info') {
            // Jangan menutupi jendela persetujuan disclaimer
            if (document.getElementById('disclaimerModal')) { /* dilewati */ } else
            Swal.fire({ toast: true, position: 'top-end', icon, title: f.message, showConfirmButton: false, timer: 3500, timerProgressBar: true });
        } else {
            Swal.fire({ icon, title: icon === 'error' ? 'Gagal' : 'Perhatian', text: f.message, confirmButtonColor: '#2a9d8f' });
        }
    }

    // ---- Konfirmasi hapus dengan SweetAlert2 ----
    document.addEventListener('submit', function (ev) {
        const form = ev.target;
        if (form.classList.contains('js-confirm-delete') && !form.dataset.confirmed) {
            ev.preventDefault();
            const msg = form.dataset.message || 'Data yang dihapus tidak dapat dikembalikan.';
            if (!window.Swal) { if (confirm(msg)) { form.dataset.confirmed = '1'; form.submit(); } return; }
            Swal.fire({
                title: 'Hapus data?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d64545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true
            }).then(r => {
                if (r.isConfirmed) { form.dataset.confirmed = '1'; showLoader(location.href); form.submit(); }
            });
            return;
        }
        if (ev.defaultPrevented) return;
        // Formulir filter (GET) -> skeleton halaman yang sama
        if ((!form.method || form.method.toLowerCase() === 'get') && !form.classList.contains('no-loader')) {
            showSkeleton(form.action || location.href);
            return;
        }
        // Tampilkan loading & cegah kirim ganda
        if (form.method && form.method.toLowerCase() === 'post' && !form.classList.contains('no-loader')) {
            const btn = form.querySelector('button[type="submit"]:not([formaction])');
            if (btn && !btn.disabled) {
                btn.dataset.original = btn.innerHTML;
                setTimeout(() => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                }, 0);
            }
            showLoader(form.action || location.href);
        }
    });

    // ---- Filter otomatis saat select berubah ----
    document.querySelectorAll('.js-auto-submit').forEach(el => {
        el.addEventListener('change', () => { showLoader(el.form.action || location.href); el.form.submit(); });
    });

    // ---- Pratinjau foto ----
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', () => {
            const target = document.querySelector(input.dataset.preview);
            const file = input.files && input.files[0];
            if (!target || !file) return;
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({ icon: 'warning', title: 'Ukuran terlalu besar', text: 'Maksimal 2 MB.' });
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => { target.src = e.target.result; target.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        });
    });

    // ---- Foto anak: hentikan kerangka setelah gambar termuat ----
    document.querySelectorAll('img.avatar').forEach((img) => {
        const done = () => img.classList.add('is-loaded');
        if (img.complete && img.naturalWidth) done();
        else { img.addEventListener('load', done); img.addEventListener('error', done); }
    });

    // ---- Penanda "Baru" untuk Change Log (versi belum dilihat) ----
    try {
        const ver = document.body.dataset.appVersion;
        const seen = localStorage.getItem('pb_seen_version');
        if (ver && seen !== ver && !/changelog\.php$/.test(location.pathname)) {
            // Pengguna baru: anggap sudah melihat versi saat ini, penanda muncul pada rilis berikutnya
            if (seen === null) localStorage.setItem('pb_seen_version', ver);
            else document.querySelectorAll('.js-cl-new').forEach((b) => b.classList.remove('d-none'));
        }
    } catch (e) { /* localStorage tidak tersedia */ }

    // ---- Tombol cetak ----
    document.querySelectorAll('.js-print').forEach(btn => btn.addEventListener('click', () => window.print()));

    // ---- Pilih semua status observasi ----
    document.querySelectorAll('[data-set-all]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.setAll;
            document.querySelectorAll('input.obs-radio[value="' + val + '"]').forEach(r => { r.checked = true; });
        });
    });

    // ---- Sesuaikan cara ukur otomatis berdasarkan usia ----
    const childSel = document.querySelector('[data-birth-map]');
    const dateInp = document.getElementById('measured_at');
    const methodSel = document.getElementById('measure_method');
    if (childSel && dateInp && methodSel && !methodSel.dataset.touched) {
        let map = {};
        try { map = JSON.parse(childSel.dataset.birthMap); } catch (e) { map = {}; }
        const update = () => {
            if (methodSel.dataset.touched) return;
            const b = map[childSel.value];
            if (!b || !dateInp.value) return;
            const months = (new Date(dateInp.value) - new Date(b)) / (1000 * 3600 * 24 * 30.4375);
            methodSel.value = months < 24 ? 'length' : 'height';
        };
        methodSel.addEventListener('change', () => { methodSel.dataset.touched = '1'; });
        childSel.addEventListener('change', update);
        dateInp.addEventListener('change', update);
        if (!methodSel.dataset.keep) update();
    }
})();
