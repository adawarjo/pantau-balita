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

// Sesi observasi = kombinasi tanggal + kelompok usia
$st = db()->prepare(
    'SELECT o.observed_at, i.age_min_months, i.age_group_label,
            SUM(o.status = "sudah") AS sudah, SUM(o.status = "belum") AS belum,
            SUM(o.status = "belum_diamati") AS belum_diamati, COUNT(*) AS total
     FROM developmental_observations o
     JOIN developmental_indicators i ON i.id = o.indicator_id
     WHERE o.child_id = ?
     GROUP BY o.observed_at, i.age_min_months, i.age_group_label
     ORDER BY o.observed_at DESC, i.age_min_months DESC'
);
$st->execute([$childId]);
$sessions = $st->fetchAll();
$pg = paginate(count($sessions), 10);
$sessions = array_slice($sessions, $pg['offset'], $pg['per_page']);

// Detail per sesi
$detail = db()->prepare(
    'SELECT o.status, o.notes, i.description, i.domain FROM developmental_observations o
     JOIN developmental_indicators i ON i.id = o.indicator_id
     WHERE o.child_id = ? AND o.observed_at = ? AND i.age_min_months = ?
     ORDER BY i.sort_order'
);

$pageTitle = 'Riwayat Observasi';
$activeMenu = 'development';
require APP_ROOT . '/includes/header.php';
?>
<form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <label for="child_id" class="fw-bold">Anak:</label>
    <select id="child_id" name="child_id" class="form-select w-auto js-auto-submit"><?= child_options($children, $childId) ?></select>
    <a href="<?= e(url('development/observe.php?child_id=' . $childId)) ?>" class="btn btn-primary ms-auto"><i class="fa-solid fa-plus me-1"></i> Observasi Baru</a>
</form>

<?php if (!$sessions): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-clipboard-list', 'Belum ada riwayat observasi', 'Mulai mengamati kemampuan ' . $child['full_name'] . ' sesuai usianya.', 'development/observe.php?child_id=' . $childId, 'Mulai Observasi') ?></div></div>
<?php else: ?>
    <div class="accordion" id="obsAcc">
    <?php foreach ($sessions as $n => $s):
        $detail->execute([$childId, $s['observed_at'], $s['age_min_months']]);
        $rows = $detail->fetchAll();
        $pct = $s['total'] ? round($s['sudah'] / $s['total'] * 100) : 0;
        $aid = 'o' . $n; ?>
        <div class="accordion-item mb-2 border rounded-4 overflow-hidden">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $aid ?>">
                    <div class="flex-fill">
                        <div class="fw-bold"><?= e(tgl_indo($s['observed_at'])) ?> <span class="text-muted fw-normal small">· indikator <?= e($s['age_group_label']) ?> · usia <?= e(age_label($child['birth_date'], $s['observed_at'])) ?></span></div>
                        <div class="small mt-1">
                            <span class="badge badge-soft-success"><?= (int) $s['sudah'] ?> sudah</span>
                            <span class="badge badge-soft-warning"><?= (int) $s['belum'] ?> belum</span>
                            <span class="badge badge-soft-secondary"><?= (int) $s['belum_diamati'] ?> belum diamati</span>
                            <span class="text-muted ms-1"><?= $pct ?>%</span>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="<?= $aid ?>" class="accordion-collapse collapse" data-bs-parent="#obsAcc">
                <div class="accordion-body">
                    <?php foreach ($rows as $r): ?>
                        <div class="list-item">
                            <span class="list-icon bg-soft-primary"><i class="fa-solid <?= e(DOMAIN_ICONS[$r['domain']]) ?>"></i></span>
                            <div class="flex-fill"><?= e($r['description']) ?><?php if ($r['notes']): ?><div class="small text-muted"><?= e($r['notes']) ?></div><?php endif; ?></div>
                            <?= obs_badge($r['status']) ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex gap-2 justify-content-end mt-2">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('development/observe.php?child_id=' . $childId . '&group=' . $s['age_min_months'] . '&date=' . $s['observed_at'])) ?>"><i class="fa-solid fa-pen"></i> Ubah</a>
                        <form method="post" action="<?= e(url('development/delete.php')) ?>" class="js-confirm-delete" data-message="Observasi tanggal <?= e(tgl_indo($s['observed_at'])) ?> (<?= e($s['age_group_label']) ?>) akan dihapus.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="child_id" value="<?= $childId ?>">
                            <input type="hidden" name="observed_at" value="<?= e($s['observed_at']) ?>">
                            <input type="hidden" name="group" value="<?= (int) $s['age_min_months'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="mt-3"><?= pagination_links($pg) ?></div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
