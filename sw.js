/*
 * Pantau Balita — Service Worker
 *
 * Prinsip privasi: halaman PHP berisi data anak & kesehatan TIDAK pernah
 * disimpan di cache perangkat. Yang di-cache hanya aset statis (CSS, JS,
 * font, ikon) dan halaman offline. Saat tidak ada koneksi, navigasi
 * menampilkan offline.html.
 *
 * Naikkan CACHE_VERSION setiap kali daftar PRECACHE diubah.
 */
const CACHE_VERSION = 'pb-v1';
const STATIC_CACHE = CACHE_VERSION + '-static';
const FONT_CACHE = CACHE_VERSION + '-fonts';

const PRECACHE = [
    'offline.html',
    'manifest.webmanifest',
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/js/charts.js',
    'assets/js/pwa.js',
    'assets/images/logo.svg',
    'assets/images/icons/icon-192.png',
    'assets/images/icons/icon-512.png',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/bootstrap/bootstrap.bundle.min.js',
    'assets/vendor/fontawesome/css/all.min.css',
    'assets/vendor/fontawesome/webfonts/fa-solid-900.woff2',
    'assets/vendor/fontawesome/webfonts/fa-regular-400.woff2',
    'assets/vendor/sweetalert2/sweetalert2.all.min.js',
    'assets/vendor/chartjs/chart.umd.min.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE.map((p) => new Request(p, { cache: 'reload' }))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(CACHE_VERSION)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

const scopeUrl = new URL(self.registration.scope);
const isStaticAsset = (url) =>
    (url.origin === scopeUrl.origin && url.pathname.startsWith(scopeUrl.pathname + 'assets/')) ||
    url.href === new URL('manifest.webmanifest', scopeUrl).href;

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') {
        return; // POST (simpan/hapus data) selalu langsung ke server
    }
    const url = new URL(req.url);

    // 1) Navigasi halaman: selalu dari jaringan, cadangan halaman offline
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('offline.html'))
        );
        return;
    }

    // 2) Aset statis: cache dulu, perbarui dari jaringan bila belum ada
    if (isStaticAsset(url)) {
        event.respondWith((async () => {
            const cache = await caches.open(STATIC_CACHE);
            const exact = await cache.match(req);
            if (exact) return exact;
            try {
                const res = await fetch(req);
                if (res.ok) cache.put(req, res.clone());
                return res;
            } catch (e) {
                // Versi lama aset (beda ?v=) lebih baik daripada gagal saat offline
                const any = await cache.match(req, { ignoreSearch: true });
                if (any) return any;
                throw e;
            }
        })());
        return;
    }

    // 3) Google Fonts: stale-while-revalidate
    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith((async () => {
            const cache = await caches.open(FONT_CACHE);
            const cached = await cache.match(req);
            const network = fetch(req).then((res) => {
                if (res && (res.ok || res.type === 'opaque')) cache.put(req, res.clone());
                return res;
            }).catch(() => cached);
            return cached || network;
        })());
        return;
    }
    // 4) Lainnya (foto anak, ekspor CSV, dsb.): tidak di-cache, langsung jaringan
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
});
