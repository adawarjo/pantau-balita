<?php
/**
 * Jendela persetujuan disclaimer (wajib) untuk pengguna yang belum
 * menyetujui versi terbaru. Dimuat oleh footer.php pada layout aplikasi.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
// Halaman saat ini (relatif terhadap aplikasi) untuk kembali setelah setuju
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$reqQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$rel = ltrim(substr($reqPath, strlen(base_url())), '/');
$returnTo = ($rel !== '' ? $rel : 'dashboard.php') . ($reqQuery ? '?' . $reqQuery : '');
?>
<div class="modal fade" id="disclaimerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="disclaimerModalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="post" action="<?= e(url('disclaimer.php')) ?>" class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="disclaimerModalTitle"><i class="fa-solid fa-file-shield text-primary me-2"></i>Mohon baca sebelum melanjutkan</h2>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Disclaimer versi <?= e(DISCLAIMER_VERSION) ?> (<?= e(tgl_indo(DISCLAIMER_DATE)) ?>)</p>
                <?php foreach (disclaimer_summary() as [$icon, $text]): ?>
                    <div class="list-item"><span class="list-icon bg-soft-primary"><i class="fa-solid <?= e($icon) ?>"></i></span><div class="align-self-center"><?= e($text) ?></div></div>
                <?php endforeach; ?>
                <p class="small mt-2 mb-3"><a href="<?= e(url('disclaimer.php?return=' . urlencode($returnTo))) ?>" class="fw-bold">Baca disclaimer lengkap</a></p>
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($returnTo) ?>">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="agree" value="1" id="dcAgree" required>
                    <label class="form-check-label fw-bold" for="dcAgree">Saya telah membaca dan memahami disclaimer ini, dan akan berkonsultasi dengan tenaga kesehatan untuk keputusan medis.</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="dcSubmit" disabled><i class="fa-solid fa-check me-1"></i> Setuju dan lanjutkan</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('disclaimerModal');
    if (!el || !window.bootstrap) return;
    var cb = document.getElementById('dcAgree'), btn = document.getElementById('dcSubmit');
    cb.addEventListener('change', function () { btn.disabled = !cb.checked; });
    new bootstrap.Modal(el).show();
});
</script>
