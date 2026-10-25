<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$children = user_children();
$childId = query_int('child_id');
$from = query('from');
$to = query('to');
$q = query('q');

$where = ['c.user_id = ?'];
$params = [current_user_id()];
if ($childId) {
    $where[] = 'g.child_id = ?';
    $params[] = $childId;
}
if ($from !== '' && valid_date($from)) {
    $where[] = 'g.measured_at >= ?';
    $params[] = $from;
} else {
    $from = '';
}
if ($to !== '' && valid_date($to)) {
    $where[] = 'g.measured_at <= ?';
    $params[] = $to;
} else {
    $to = '';
}
if ($q !== '') {
    $where[] = '(g.notes LIKE ? OR c.full_name LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM growth_records g JOIN children c ON c.id = g.child_id WHERE $whereSql");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), 10);

$st = db()->prepare(
    "SELECT g.*, c.full_name, c.birth_date, c.gender FROM growth_records g JOIN children c ON c.id = g.child_id
     WHERE $whereSql ORDER BY g.measured_at DESC, g.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$st->execute($params);
$rows = $st->fetchAll();

$pageTitle = 'Pertumbuhan';
$activeMenu = 'growth';
require APP_ROOT . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Riwayat pengukuran berat, panjang/tinggi, dan lingkar kepala.</p>
    <div class="d-flex gap-2">
        <?php if ($childId): ?>
            <a href="<?= e(url('growth/chart.php?child_id=' . $childId)) ?>" class="btn btn-outline-primary"><i class="fa-solid fa-chart-line me-1"></i> Grafik</a>
        <?php elseif ($children): ?>
            <a href="<?= e(url('growth/chart.php')) ?>" class="btn btn-outline-primary"><i class="fa-solid fa-chart-line me-1"></i> Grafik</a>
        <?php endif; ?>
        <a href="<?= e(url('growth/create.php' . ($childId ? '?child_id=' . $childId : ''))) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Catat</a>
    </div>
</div>

<?php if (!$children): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-baby', 'Belum ada data anak', 'Tambahkan anak sebelum mencatat pertumbuhan.', 'children/create.php', 'Tambah Anak') ?></div></div>
<?php else: ?>
<form class="card card-body filter-bar mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small" for="f_child">Anak</label><select class="form-select js-auto-submit" id="f_child" name="child_id"><?= child_options($children, $childId, true) ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label small" for="f_from">Dari</label><input type="date" class="form-control" id="f_from" name="from" value="<?= e($from) ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label small" for="f_to">Sampai</label><input type="date" class="form-control" id="f_to" name="to" value="<?= e($to) ?>"></div>
        <div class="col-md-3"><label class="form-label small" for="f_q">Cari catatan</label><input type="search" class="form-control" id="f_q" name="q" value="<?= e($q) ?>" placeholder="mis. posyandu"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a href="<?= e(url('growth/index.php')) ?>" class="btn btn-light border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a></div>
    </div>
</form>

<div class="card">
    <?php if (!$rows): ?>
        <div class="card-body"><?= empty_state('fa-ruler-combined', 'Belum ada pengukuran', $q || $from || $to ? 'Tidak ada data yang cocok dengan filter.' : 'Catat pengukuran pertama anak Anda.', 'growth/create.php' . ($childId ? '?child_id=' . $childId : ''), 'Catat Pertumbuhan') ?></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Tanggal</th><th>Anak</th><th>Usia saat ukur</th><th class="text-end">BB (kg)</th><th class="text-end">PB/TB (cm)</th><th class="text-end">LK (cm)</th><th class="text-end">LiLA (cm)</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="text-nowrap"><?= e(tgl_indo($r['measured_at'], false, true)) ?></td>
                    <td class="text-nowrap"><?= e($r['full_name']) ?></td>
                    <td class="text-nowrap small"><?= e(age_label($r['birth_date'], $r['measured_at'])) ?></td>
                    <td class="text-end fw-bold"><?= e(num($r['weight'], 2)) ?></td>
                    <td class="text-end"><?= e(num($r['height'])) ?> <span class="text-muted small"><?= $r['measure_method'] === 'height' ? 'TB' : 'PB' ?></span></td>
                    <td class="text-end"><?= e(num($r['head_circ'])) ?></td>
                    <td class="text-end"><?= e(num($r['muac'])) ?></td>
                    <td class="small text-muted"><?= e(mb_strimwidth((string) $r['notes'], 0, 40, '…')) ?></td>
                    <td class="text-end table-actions">
                        <a href="<?= e(url('growth/edit.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?= delete_button('growth/delete.php', (int) $r['id'], 'Pengukuran ' . $r['full_name'] . ' tanggal ' . tgl_indo($r['measured_at']) . ' akan dihapus.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 border-top">
        <span class="small text-muted">Menampilkan <?= count($rows) ?> dari <?= $pg['total'] ?> data</span>
        <?= pagination_links($pg) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
