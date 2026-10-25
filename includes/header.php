<?php
/**
 * Kepala halaman. Variabel opsional sebelum include:
 *   $pageTitle  (string)  judul halaman
 *   $activeMenu (string)  kunci menu aktif
 *   $layout     (string)  'app' (default, dengan sidebar) | 'auth' | 'print'
 *   $useChart   (bool)    muat Chart.js
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
$pageTitle  = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$layout     = $layout ?? 'app';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2a9d8f">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <meta name="description" content="Catatan tumbuh kembang, imunisasi, dan kesehatan anak usia 0-5 tahun.">
    <link rel="icon" href="<?= e(asset('images/logo.svg')) ?>" type="image/svg+xml">
    <link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('assets/images/icons/apple-touch-icon.png')) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('vendor/fontawesome/css/all.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
    <?php if (!empty($useChart)): ?>
    <script src="<?= e(asset('vendor/chartjs/chart.umd.min.js')) ?>"></script>
    <?php endif; ?>
</head>
<body class="layout-<?= e($layout) ?>" data-app-version="<?= e(APP_VERSION) ?>">
<?php if ($layout === 'app'): ?>
<div class="app-shell">
    <?php require APP_ROOT . '/includes/sidebar.php'; ?>
    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Buka menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
            <div class="dropdown ms-auto">
                <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-circle-user"></i>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['user_name'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="fa-solid fa-user-gear me-2"></i>Profil & Kata Sandi</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= e(url('auth/logout.php')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>
        <main class="content">
<?php endif; ?>
