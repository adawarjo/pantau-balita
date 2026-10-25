<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$releases = app_changelog();
$typeMeta = CHANGE_TYPES;

// Hitung jumlah per jenis untuk tombol filter
$counts = array_fill_keys(array_keys($typeMeta), 0);
foreach ($releases as $r) {
    foreach ($r['changes'] as [$type]) {
        if (isset($counts[$type])) $counts[$type]++;
    }
}

$pageTitle = 'Change Log';
$activeMenu = 'changelog';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center">
<div class="col-xl-9">
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="stat-icon bg-soft-primary"><i class="fa-solid fa-code-branch"></i></span>
            <div class="flex-fill">
                <div class="fw-bold fs-5">Versi <?= e(APP_VERSION) ?></div>
                <div class="small text-muted">Diperbarui <?= e(tgl_indo($releases[0]['date'])) ?> · <?= count($releases) ?> rilis</div>
            </div>
            <span class="badge badge-soft-success"><i class="fa-solid fa-circle-check me-1"></i>Versi terbaru</span>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filter jenis perubahan">
        <button type="button" class="btn btn-sm btn-primary js-cl-filter" data-type="all" aria-pressed="true">Semua</button>
        <?php foreach ($typeMeta as $key => $m): if (!$counts[$key]) continue; ?>
            <button type="button" class="btn btn-sm btn-outline-primary js-cl-filter" data-type="<?= e($key) ?>" aria-pressed="false">
                <i class="fa-solid <?= e($m['icon']) ?> me-1"></i><?= e($m['label']) ?> <span class="opacity-75">(<?= $counts[$key] ?>)</span>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="cl-timeline">
        <?php foreach ($releases as $i => $r): ?>
        <section class="cl-release" id="v<?= e(str_replace('.', '-', $r['version'])) ?>">
            <span class="cl-dot<?= $i === 0 ? ' is-current' : '' ?>" aria-hidden="true"></span>
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <span class="cl-version">v<?= e($r['version']) ?></span>
                    <span class="fw-bold"><?= e($r['title']) ?></span>
                    <?php if ($i === 0): ?><span class="badge badge-soft-success">Saat ini</span><?php endif; ?>
                    <span class="ms-auto small text-muted fw-normal"><i class="fa-regular fa-calendar me-1"></i><?= e(tgl_indo($r['date'])) ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($r['summary'])): ?><p class="text-muted mb-3"><?= e($r['summary']) ?></p><?php endif; ?>
                    <ul class="cl-list list-unstyled mb-0">
                        <?php foreach ($r['changes'] as [$type, $text]): $m = $typeMeta[$type] ?? $typeMeta['peningkatan']; ?>
                            <li class="cl-item" data-type="<?= e($type) ?>">
                                <span class="badge badge-soft-<?= e($m['color']) ?> cl-badge"><i class="fa-solid <?= e($m['icon']) ?> me-1"></i><?= e($m['label']) ?></span>
                                <span><?= e($text) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="small text-muted mb-0 mt-2 d-none js-cl-empty">Tidak ada perubahan jenis ini pada versi ini.</p>
                </div>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
</div>
</div>
<?php
$pageScripts = <<<HTML
<script>
(function () {
    // Tandai versi terbaru sudah dilihat (menghapus penanda "Baru" di menu)
    try { localStorage.setItem('pb_seen_version', document.body.dataset.appVersion || ''); } catch (e) {}
    document.querySelectorAll('.js-cl-new').forEach(function (b) { b.classList.add('d-none'); });

    // Filter jenis perubahan
    var buttons = document.querySelectorAll('.js-cl-filter');
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.dataset.type;
            buttons.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('btn-primary', on);
                b.classList.toggle('btn-outline-primary', !on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            document.querySelectorAll('.cl-release').forEach(function (rel) {
                var visible = 0;
                rel.querySelectorAll('.cl-item').forEach(function (li) {
                    var show = type === 'all' || li.dataset.type === type;
                    li.classList.toggle('d-none', !show);
                    if (show) visible++;
                });
                rel.querySelector('.js-cl-empty').classList.toggle('d-none', visible > 0);
            });
        });
    });
})();
</script>
HTML;
require APP_ROOT . '/includes/footer.php';
