<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$children = user_children();
$childId = query_int('child_id');
$type = query('type');
$from = query('from');
$to = query('to');
$view = query('view') === 'table' ? 'table' : 'timeline';

$where = ['c.user_id = ?'];
$params = [current_user_id()];
if ($childId) { $where[] = 'h.child_id = ?'; $params[] = $childId; }
if (isset(HEALTH_TYPES[$type])) { $where[] = 'h.record_type = ?'; $params[] = $type; } else { $type = ''; }
if ($from !== '' && valid_date($from)) { $where[] = 'h.event_date >= ?'; $params[] = $from; } else { $from = ''; }
if ($to !== '' && valid_date($to)) { $where[] = 'h.event_date <= ?'; $params[] = $to; } else { $to = ''; }
$w = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM health_records h JOIN children c ON c.id = h.child_id WHERE $w");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), 10);
$st = db()->prepare("SELECT h.*, c.full_name FROM health_records h JOIN children c ON c.id = h.child_id WHERE $w
                     ORDER BY h.event_date DESC, h.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($params);
$rows = $st->fetchAll();

$viewLink = function (string $v) {
    $p = $_GET;
    $p['view'] = $v;
    unset($p['page']);
    return '?' . http_build_query($p);
};

$pageTitle = 'Catatan Kesehatan';
$activeMenu = 'health';
require APP_ROOT . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="btn-group" role="group" aria-label="Tampilan">
        <a href="<?= e($viewLink('timeline')) ?>" class="btn btn-sm <?= $view === 'timeline' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fa-solid fa-timeline me-1"></i>Timeline</a>
        <a href="<?= e($viewLink('table')) ?>" class="btn btn-sm <?= $view === 'table' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fa-solid fa-table me-1"></i>Tabel</a>
    </div>
    <a href="<?= e(url('health/create.php' . ($childId ? '?child_id=' . $childId : ''))) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Catat Kesehatan</a>
</div>

<?php if (!$children): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-baby', 'Belum ada data anak', 'Tambahkan anak terlebih dahulu.', 'children/create.php', 'Tambah Anak') ?></div></div>
<?php else: ?>
<form class="card card-body filter-bar mb-3" method="get">
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small" for="f_child">Anak</label><select class="form-select" id="f_child" name="child_id"><?= child_options($children, $childId, true) ?></select></div>
        <div class="col-md-3"><label class="form-label small" for="f_type">Jenis</label>
            <select class="form-select" id="f_type" name="type"><option value="">Semua jenis</option>
                <?php foreach (HEALTH_TYPES as $k => $l): ?><option value="<?= e($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-6 col-md-2"><label class="form-label small" for="f_from">Dari</label><input type="date" class="form-control" id="f_from" name="from" value="<?= e($from) ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label small" for="f_to">Sampai</label><input type="date" class="form-control" id="f_to" name="to" value="<?= e($to) ?>"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i> Filter</button><a href="<?= e(url('health/index.php')) ?>" class="btn btn-light border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a></div>
    </div>
</form>

<div class="card">
<?php if (!$rows): ?>
    <div class="card-body"><?= empty_state('fa-heart-pulse', 'Belum ada catatan kesehatan', ($type || $from || $to) ? 'Tidak ada data yang cocok dengan filter.' : 'Catat keluhan, kunjungan, obat, atau alergi anak.', 'health/create.php' . ($childId ? '?child_id=' . $childId : ''), 'Catat Kesehatan') ?></div>
<?php elseif ($view === 'timeline'): ?>
    <div class="card-body">
        <div class="timeline">
        <?php foreach ($rows as $h): ?>
            <div class="timeline-item">
                <span class="timeline-dot"><i class="fa-solid <?= e(HEALTH_ICONS[$h['record_type']]) ?>"></i></span>
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <div class="fw-bold"><?= e(HEALTH_TYPES[$h['record_type']]) ?> <span class="text-muted fw-normal small">· <?= e($h['full_name']) ?></span></div>
                        <div class="small text-muted"><?= e(tgl_indo($h['event_date'], true)) ?><?= $h['temperature'] ? ' · Suhu ' . e(num($h['temperature'])) . '°C' : '' ?></div>
                    </div>
                    <div class="table-actions">
                        <a href="<?= e(url('health/edit.php?id=' . $h['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?= delete_button('health/delete.php', (int) $h['id'], 'Catatan kesehatan tanggal ' . tgl_indo($h['event_date']) . ' akan dihapus.') ?>
                    </div>
                </div>
                <div class="small mt-1">
                    <?php if ($h['symptoms']): ?><div><span class="text-muted">Keluhan:</span> <?= nl2br(e($h['symptoms'])) ?></div><?php endif; ?>
                    <?php if ($h['medicine_name']): ?><div><span class="text-muted">Obat:</span> <?= e($h['medicine_name']) ?><?= $h['medicine_dose'] ? ' — ' . e($h['medicine_dose']) : '' ?></div><?php endif; ?>
                    <?php if ($h['allergies']): ?><div class="text-danger"><span class="text-muted">Alergi:</span> <?= e($h['allergies']) ?></div><?php endif; ?>
                    <?php if ($h['facility_visit']): ?><div><span class="text-muted">Kunjungan:</span> <?= nl2br(e($h['facility_visit'])) ?></div><?php endif; ?>
                    <?php if ($h['notes']): ?><div><span class="text-muted">Catatan:</span> <?= nl2br(e($h['notes'])) ?></div><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Tanggal</th><th>Anak</th><th>Jenis</th><th>Keluhan</th><th class="text-end">Suhu</th><th>Obat</th><th>Alergi</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $h): ?>
                <tr>
                    <td class="text-nowrap"><?= e(tgl_indo($h['event_date'], false, true)) ?></td>
                    <td class="text-nowrap"><?= e($h['full_name']) ?></td>
                    <td class="small"><?= e(HEALTH_TYPES[$h['record_type']]) ?></td>
                    <td class="small"><?= e(mb_strimwidth((string) $h['symptoms'], 0, 60, '…')) ?></td>
                    <td class="text-end"><?= $h['temperature'] ? e(num($h['temperature'])) . '°C' : '-' ?></td>
                    <td class="small"><?= e($h['medicine_name'] ?: '-') ?></td>
                    <td class="small text-danger"><?= e($h['allergies'] ?: '') ?></td>
                    <td class="text-end table-actions">
                        <a href="<?= e(url('health/edit.php?id=' . $h['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?= delete_button('health/delete.php', (int) $h['id'], 'Catatan kesehatan tanggal ' . tgl_indo($h['event_date']) . ' akan dihapus.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php if ($rows): ?>
    <div class="card-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-muted"><?= $pg['total'] ?> catatan</span><?= pagination_links($pg) ?>
    </div>
<?php endif; ?>
</div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
