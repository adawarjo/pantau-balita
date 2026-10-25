<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$st = db()->prepare(
    'SELECT c.*,
        (SELECT COUNT(*) FROM growth_records g WHERE g.child_id = c.id) AS growth_count,
        (SELECT MAX(measured_at) FROM growth_records g WHERE g.child_id = c.id) AS last_measured
     FROM children c WHERE c.user_id = ? ORDER BY c.birth_date DESC, c.full_name'
);
$st->execute([current_user_id()]);
$children = $st->fetchAll();

$pageTitle = 'Data Anak';
$activeMenu = 'children';
require APP_ROOT . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($children) ?> anak terdaftar</p>
    <a href="<?= e(url('children/create.php')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Tambah Anak</a>
</div>

<?php if (!$children): ?>
    <div class="card"><div class="card-body">
        <?= empty_state('fa-baby', 'Belum ada data anak', 'Mulai dengan menambahkan profil anak Anda.', 'children/create.php', 'Tambah Anak') ?>
    </div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($children as $c): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex gap-3">
                <?= child_avatar($c) ?>
                <div class="flex-fill min-w-0">
                    <a class="fw-bold text-body text-decoration-none d-block text-truncate" href="<?= e(url('children/detail.php?id=' . $c['id'])) ?>"><?= e($c['full_name']) ?></a>
                    <div class="small text-muted"><?= e(gender_label($c['gender'])) ?> · <?= e(age_label($c['birth_date'])) ?></div>
                    <div class="small text-muted">Lahir <?= e(tgl_indo($c['birth_date'])) ?></div>
                    <div class="small mt-2">
                        <span class="badge badge-soft-info"><?= (int) $c['growth_count'] ?> pengukuran</span>
                        <?php if ($c['last_measured']): ?><span class="badge badge-soft-secondary">terakhir <?= e(tgl_indo($c['last_measured'], false, true)) ?></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2 justify-content-end">
                <a href="<?= e(url('children/detail.php?id=' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i> Detail</a>
                <a href="<?= e(url('children/edit.php?id=' . $c['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
                <?= delete_button('children/delete.php', (int) $c['id'], 'Profil ' . $c['full_name'] . ' dan SELURUH catatannya (pertumbuhan, perkembangan, imunisasi, kesehatan, pengingat) akan dihapus permanen.') ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
