<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
$menu = [
    'dashboard'    => ['Dashboard', 'fa-house', 'dashboard.php'],
    'children'     => ['Data Anak', 'fa-children', 'children/index.php'],
    'growth'       => ['Pertumbuhan', 'fa-weight-scale', 'growth/index.php'],
    'development'  => ['Perkembangan', 'fa-puzzle-piece', 'development/index.php'],
    'immunization' => ['Imunisasi', 'fa-syringe', 'immunization/index.php'],
    'health'       => ['Catatan Kesehatan', 'fa-notes-medical', 'health/index.php'],
    'reminders'    => ['Pengingat', 'fa-bell', 'reminders/index.php'],
    'reports'      => ['Laporan', 'fa-file-medical', 'reports/index.php'],
];
?>
<aside class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebar" aria-label="Menu utama">
    <div class="sidebar-brand">
        <a href="<?= e(url('dashboard.php')) ?>" class="brand">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="34" height="34">
            <span><?= e(APP_NAME) ?></span>
        </a>
        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup menu"></button>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menu as $key => [$label, $icon, $href]): ?>
            <a href="<?= e(url($href)) ?>" class="nav-item<?= $activeMenu === $key ? ' active' : '' ?>"<?= $activeMenu === $key ? ' aria-current="page"' : '' ?>>
                <i class="fa-solid <?= e($icon) ?> fa-fw"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
        <hr>
        <a href="<?= e(url('profile.php')) ?>" class="nav-item<?= $activeMenu === 'profile' ? ' active' : '' ?>">
            <i class="fa-solid fa-user-gear fa-fw"></i><span>Profil Saya</span>
        </a>
        <a href="<?= e(url('changelog.php')) ?>" class="nav-item<?= $activeMenu === 'changelog' ? ' active' : '' ?>"<?= $activeMenu === 'changelog' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-clock-rotate-left fa-fw"></i><span>Change Log</span>
            <span class="badge bg-danger ms-auto js-cl-new d-none">Baru</span>
        </a>
        <a href="<?= e(url('disclaimer.php')) ?>" class="nav-item<?= $activeMenu === 'disclaimer' ? ' active' : '' ?>"<?= $activeMenu === 'disclaimer' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-file-shield fa-fw"></i><span>Disclaimer</span>
        </a>
    </nav>
    <div class="px-3">
        <button type="button" class="btn btn-install w-100 js-install-app d-none"><i class="fa-solid fa-mobile-screen-button me-2"></i>Pasang Aplikasi</button>
    </div>
    <div class="sidebar-note small">
        <i class="fa-solid fa-circle-info me-1"></i>
        Alat bantu pemantauan, bukan pengganti pemeriksaan tenaga kesehatan.
        <a href="<?= e(url('disclaimer.php')) ?>" class="d-block mt-1 fw-bold" style="color:inherit">Baca disclaimer</a>
    </div>
    <a href="<?= e(url('changelog.php')) ?>" class="sidebar-version small">Versi <?= e(APP_VERSION) ?></a>
</aside>

<!-- Navigasi bawah untuk HP -->
<nav class="bottom-nav d-lg-none" aria-label="Navigasi cepat">
    <a href="<?= e(url('dashboard.php')) ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i><span>Beranda</span></a>
    <a href="<?= e(url('growth/index.php')) ?>" class="<?= $activeMenu === 'growth' ? 'active' : '' ?>"><i class="fa-solid fa-weight-scale"></i><span>Tumbuh</span></a>
    <a href="<?= e(url('development/index.php')) ?>" class="<?= $activeMenu === 'development' ? 'active' : '' ?>"><i class="fa-solid fa-puzzle-piece"></i><span>Kembang</span></a>
    <a href="<?= e(url('immunization/index.php')) ?>" class="<?= $activeMenu === 'immunization' ? 'active' : '' ?>"><i class="fa-solid fa-syringe"></i><span>Imunisasi</span></a>
    <a href="#sidebar" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar"><i class="fa-solid fa-ellipsis"></i><span>Lainnya</span><span class="nav-dot js-cl-new d-none" aria-label="Ada pembaruan"></span></a>
</nav>
