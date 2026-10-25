<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_common.php';

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$childId = query_int('child_id') ?: (int) $children[0]['id'];
$child = require_child($childId);
$age = age_parts($child['birth_date']);
$groups = dev_age_groups();
$currentGroup = dev_group_for_age($groups, $age['total_months']);
$group = isset($_GET['group']) ? dev_find_group($groups, query_int('group')) : null;
$group = $group ?: $currentGroup;

$indicators = $group ? dev_indicators((int) $group['age_min_months'], (int) $group['age_max_months']) : [];
$latest = dev_latest_status($childId, array_map(fn($i) => (int) $i['id'], $indicators));

// Ringkasan per bidang
$summary = [];
foreach (DOMAIN_LABELS as $k => $label) {
    $summary[$k] = ['total' => 0, 'sudah' => 0, 'belum' => 0, 'belum_diamati' => 0];
}
$hasBelum = false;
foreach ($indicators as $i) {
    $s = $latest[$i['id']]['status'] ?? 'belum_diamati';
    $summary[$i['domain']]['total']++;
    $summary[$i['domain']][$s]++;
    if ($s === 'belum') $hasBelum = true;
}

// Observasi terakhir
$st = db()->prepare('SELECT MAX(observed_at) FROM developmental_observations WHERE child_id = ?');
$st->execute([$childId]);
$lastObs = $st->fetchColumn();

$pageTitle = 'Perkembangan';
$activeMenu = 'development';
require APP_ROOT . '/includes/header.php';
?>
<form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <label for="child_id" class="fw-bold">Anak:</label>
    <select id="child_id" name="child_id" class="form-select w-auto js-auto-submit"><?= child_options($children, $childId) ?></select>
    <span class="text-muted small">Usia <?= e(age_label($child['birth_date'])) ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= e(url('development/history.php?child_id=' . $childId)) ?>" class="btn btn-outline-primary"><i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat</a>
        <?php if ($group): ?>
        <a href="<?= e(url('development/observe.php?child_id=' . $childId . '&group=' . $group['age_min_months'])) ?>" class="btn btn-primary"><i class="fa-solid fa-clipboard-check me-1"></i> Observasi</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($age['total_months'] >= 60): ?>
    <div class="info-box mb-3"><i class="fa-solid fa-circle-info me-1"></i>Anak sudah berusia di atas 5 tahun. Daftar indikator aplikasi ini hanya mencakup usia 0-60 bulan.</div>
<?php endif; ?>

<?php if (!$groups): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-list-check', 'Daftar indikator belum tersedia', 'Impor data indikator perkembangan dari database.sql.') ?></div></div>
<?php else: ?>
<div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Kelompok usia">
    <?php foreach ($groups as $g): $isCur = $currentGroup && $g['age_min_months'] === $currentGroup['age_min_months']; ?>
        <a href="?child_id=<?= $childId ?>&group=<?= (int) $g['age_min_months'] ?>" class="btn btn-sm <?= $g['age_min_months'] === $group['age_min_months'] ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= e($g['age_group_label']) ?><?= $isCur ? ' <i class="fa-solid fa-star small" title="Sesuai usia saat ini"></i>' : '' ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($summary as $k => $s): if (!$s['total']) continue; $pct = round($s['sudah'] / $s['total'] * 100); ?>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2"><span class="list-icon bg-soft-primary"><i class="fa-solid <?= e(DOMAIN_ICONS[$k]) ?>"></i></span><span class="fw-bold small"><?= e(DOMAIN_LABELS[$k]) ?></span></div>
            <div class="progress mb-1" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
            <div class="small text-muted"><?= $s['sudah'] ?>/<?= $s['total'] ?> sudah dapat<?= $s['belum'] ? ' · ' . $s['belum'] . ' belum' : '' ?></div>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($hasBelum): ?>
<div class="note-box mb-3">
    <i class="fa-solid fa-comments me-1"></i>Ada kemampuan yang ditandai <strong>Belum Dapat</strong>. Lanjutkan stimulasi sesuai usia dan
    diskusikan dengan dokter, bidan, atau petugas posyandu/puskesmas saat kunjungan berikutnya, terutama bila kemampuan tersebut belum muncul dalam beberapa minggu.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <span>Indikator usia <?= e($group['age_group_label']) ?></span>
        <span class="small text-muted fw-normal">Observasi terakhir: <?= $lastObs ? e(tgl_indo($lastObs)) : 'belum ada' ?></span>
    </div>
    <div class="card-body">
        <?php foreach (DOMAIN_LABELS as $dk => $dl):
            $items = array_filter($indicators, fn($i) => $i['domain'] === $dk);
            if (!$items) continue; ?>
            <h3 class="h6 mt-2 mb-2"><i class="fa-solid <?= e(DOMAIN_ICONS[$dk]) ?> me-2 text-primary"></i><?= e($dl) ?></h3>
            <?php foreach ($items as $i): $l = $latest[$i['id']] ?? null; ?>
                <div class="obs-item d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <div class="flex-fill">
                        <div><?= e($i['description']) ?></div>
                        <?php if ($l): ?><div class="small text-muted">Diamati <?= e(tgl_indo($l['observed_at'])) ?><?= $l['notes'] ? ' · ' . e($l['notes']) : '' ?></div><?php endif; ?>
                    </div>
                    <?= obs_badge($l['status'] ?? 'belum_diamati') ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <div class="card-body border-top small text-muted">
        <i class="fa-solid fa-circle-info me-1"></i><?= e(DEV_DISCLAIMER) ?>
        <div class="mt-1">Sumber indikator: <?= e($indicators[0]['source'] ?? '-') ?>.</div>
    </div>
</div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
