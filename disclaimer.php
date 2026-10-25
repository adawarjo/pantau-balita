<?php
/**
 * Halaman Disclaimer (Penafian).
 * Dapat dibuka tanpa login (mis. dari halaman pendaftaran).
 * POST action=accept mencatat persetujuan pengguna yang login.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$loggedIn = is_logged_in() && current_user() !== null;

/** Hanya izinkan kembali ke halaman internal aplikasi */
function safe_return(string $ret): string
{
    $ret = ltrim($ret, '/');
    return (preg_match('#^[a-z0-9_\-/]+\.php(\?[A-Za-z0-9_=&%\-.]*)?$#i', $ret) && strpos($ret, '..') === false) ? $ret : 'dashboard.php';
}

if (is_post()) {
    if (!$loggedIn) {
        redirect('auth/login.php');
    }
    csrf_verify();
    if (input('agree') !== '1') {
        set_flash('warning', 'Centang pernyataan persetujuan untuk melanjutkan.');
        redirect('disclaimer.php?return=' . urlencode(safe_return(input('return'))));
    }
    record_disclaimer_acceptance(current_user_id());
    set_flash('success', 'Terima kasih. Persetujuan disclaimer telah dicatat.');
    redirect(safe_return(input('return')));
}

if ($loggedIn && !headers_sent()) {
    header('Cache-Control: no-store, private');
}
$user = $loggedIn ? current_user() : null;
$needs = user_needs_disclaimer($user);
$return = safe_return(query('return', 'dashboard.php'));

$pageTitle = 'Disclaimer';
$activeMenu = 'disclaimer';
$layout = $loggedIn ? 'app' : 'auth';
require APP_ROOT . '/includes/header.php';
?>
<?php if (!$loggedIn): ?><div class="auth-wrap align-items-start py-4"><div class="w-100" style="max-width: 860px;"><?php endif; ?>

<div class="row justify-content-center"><div class="col-xl-9">
    <?php if (!$loggedIn): ?>
        <div class="d-flex align-items-center gap-2 mb-3">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="40" height="40">
            <div class="fw-bold fs-5"><?= e(APP_NAME) ?></div>
            <a href="<?= e(url('auth/register.php')) ?>" class="btn btn-sm btn-light border ms-auto"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
        </div>
    <?php endif; ?>

    <div class="card mb-3 dc-hero">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <span class="stat-icon bg-soft-peach"><i class="fa-solid fa-file-shield"></i></span>
            <div class="flex-fill">
                <h1 class="h4 mb-1">Disclaimer (Penafian)</h1>
                <div class="small text-muted">Versi <?= e(DISCLAIMER_VERSION) ?> · berlaku sejak <?= e(tgl_indo(DISCLAIMER_DATE)) ?></div>
            </div>
            <?php if ($loggedIn && !$needs && !empty($user['disclaimer_accepted_at'])): ?>
                <span class="badge badge-soft-success"><i class="fa-solid fa-circle-check me-1"></i>Disetujui <?= e(datetime_indo($user['disclaimer_accepted_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-danger d-flex gap-3 align-items-start dc-emergency" role="note">
        <i class="fa-solid fa-truck-medical fs-4 mt-1"></i>
        <div><strong>Keadaan darurat?</strong> Jangan menunggu dan jangan mengandalkan aplikasi ini. Segera ke IGD terdekat atau hubungi <a href="tel:119" class="alert-link">119</a>.</div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-list-check me-2 text-primary"></i>Ringkasan</div>
        <div class="card-body py-2">
            <?php foreach (disclaimer_summary() as [$icon, $text]): ?>
                <div class="list-item"><span class="list-icon bg-soft-primary"><i class="fa-solid <?= e($icon) ?>"></i></span><div class="align-self-center"><?= e($text) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Ketentuan lengkap</div>
        <div class="card-body dc-body">
            <?php render_disclaimer_sections(); ?>
        </div>
    </div>

    <?php if ($loggedIn && $needs): ?>
    <form method="post" class="card border-primary-subtle mb-3 no-print" id="setuju">
        <div class="card-body">
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" required>
                <label class="form-check-label fw-bold" for="agree">Saya telah membaca dan memahami disclaimer ini, dan akan berkonsultasi dengan tenaga kesehatan untuk keputusan medis.</label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i> Setuju dan lanjutkan</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-2 no-print">
        <button type="button" class="btn btn-light border js-print"><i class="fa-solid fa-print me-1"></i> Cetak</button>
    </div>
</div></div>

<?php if (!$loggedIn): ?></div></div><?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
