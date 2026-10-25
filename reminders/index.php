<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$uid = current_user_id();
$children = user_children();
$childId = query_int('child_id');
$status = query('status', 'active');
if (!in_array($status, ['active', 'done', 'all'], true)) {
    $status = 'active';
}

$where = ['r.user_id = ?', 'c.user_id = ?'];
$params = [$uid, $uid];
if ($childId) { $where[] = 'r.child_id = ?'; $params[] = $childId; }
if ($status === 'active') $where[] = 'r.is_done = 0';
if ($status === 'done') $where[] = 'r.is_done = 1';
$w = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM reminders r JOIN children c ON c.id = r.child_id WHERE $w");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), 15);
$order = $status === 'done' ? 'r.done_at DESC' : 'r.is_done ASC, r.due_date ASC, r.due_time ASC';
$st = db()->prepare("SELECT r.*, c.full_name FROM reminders r JOIN children c ON c.id = r.child_id WHERE $w ORDER BY $order LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($params);
$rows = $st->fetchAll();

$pageTitle = 'Pengingat';
$activeMenu = 'reminders';
require APP_ROOT . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form method="get" class="d-flex flex-wrap gap-2">
        <select name="child_id" class="form-select w-auto js-auto-submit" aria-label="Anak"><?= child_options($children, $childId, true) ?></select>
        <select name="status" class="form-select w-auto js-auto-submit" aria-label="Status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Belum selesai</option>
            <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Selesai</option>
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option>
        </select>
    </form>
    <a href="<?= e(url('reminders/create.php' . ($childId ? '?child_id=' . $childId : ''))) ?>" class="btn btn-primary ms-auto"><i class="fa-solid fa-plus me-1"></i> Tambah Pengingat</a>
</div>

<?php if (!$children): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-baby', 'Belum ada data anak', 'Tambahkan anak terlebih dahulu.', 'children/create.php', 'Tambah Anak') ?></div></div>
<?php else: ?>
<div class="card">
    <div class="card-body py-2">
        <?php if (!$rows): ?>
            <?= empty_state('fa-bell', 'Tidak ada pengingat', 'Buat pengingat untuk jadwal timbang, imunisasi, atau pemeriksaan.', 'reminders/create.php', 'Tambah Pengingat') ?>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $late = !$r['is_done'] && $r['due_date'] < today();
            $soon = !$r['is_done'] && !$late && days_until($r['due_date']) <= 7; ?>
            <div class="list-item align-items-center">
                <span class="list-icon <?= $r['is_done'] ? 'bg-soft-primary' : ($late ? 'bg-soft-rose' : 'bg-soft-peach') ?>"><i class="fa-solid <?= e(REMINDER_ICONS[$r['category']]) ?>"></i></span>
                <div class="flex-fill min-w-0">
                    <div class="fw-bold <?= $r['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= e($r['title']) ?>
                        <?php if ($late): ?><span class="badge badge-soft-danger ms-1">Terlewat</span><?php elseif ($soon): ?><span class="badge badge-soft-warning ms-1">Segera</span><?php elseif ($r['is_done']): ?><span class="badge badge-soft-success ms-1">Selesai</span><?php endif; ?>
                    </div>
                    <div class="small text-muted"><?= e($r['full_name']) ?> · <?= e(REMINDER_CATEGORIES[$r['category']]) ?> · <?= e(tgl_indo($r['due_date'], true)) ?><?= $r['due_time'] ? ' ' . e(substr($r['due_time'], 0, 5)) : '' ?><?= !$r['is_done'] ? ' (' . e(relative_day_label($r['due_date'])) . ')' : '' ?></div>
                    <?php if ($r['notes']): ?><div class="small"><?= e($r['notes']) ?></div><?php endif; ?>
                </div>
                <div class="table-actions d-flex gap-1">
                    <form method="post" action="<?= e(url('reminders/toggle.php')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-sm <?= $r['is_done'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= $r['is_done'] ? 'Aktifkan lagi' : 'Tandai selesai' ?>" aria-label="Ubah status"><i class="fa-solid <?= $r['is_done'] ? 'fa-rotate-left' : 'fa-check' ?>"></i></button>
                    </form>
                    <a href="<?= e(url('reminders/edit.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a>
                    <?= delete_button('reminders/delete.php', (int) $r['id'], 'Pengingat "' . $r['title'] . '" akan dihapus.') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($rows): ?><div class="card-body border-top"><?= pagination_links($pg) ?></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
