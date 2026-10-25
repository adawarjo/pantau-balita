<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$uid = current_user_id();
$pdo = db();
$children = user_children();

// Data ringkas per anak
$lastGrowth = $pdo->prepare('SELECT measured_at, weight, height, measure_method FROM growth_records WHERE child_id = ? ORDER BY measured_at DESC, id DESC LIMIT 1');
$nextImm = $pdo->prepare(
    'SELECT vaccine_name, dose, next_date FROM immunization_records
     WHERE child_id = ? AND next_date IS NOT NULL AND next_date >= CURDATE()
     ORDER BY next_date ASC LIMIT 1'
);
foreach ($children as &$c) {
    $lastGrowth->execute([$c['id']]);
    $c['growth'] = $lastGrowth->fetch() ?: null;
    $nextImm->execute([$c['id']]);
    $c['next_imm'] = $nextImm->fetch() ?: null;
}
unset($c);

// Pengingat mendekati (14 hari ke depan) & terlewat, belum selesai
$st = $pdo->prepare(
    'SELECT r.*, c.full_name AS child_name FROM reminders r
     JOIN children c ON c.id = r.child_id
     WHERE r.user_id = ? AND c.user_id = ? AND r.is_done = 0 AND r.due_date <= (CURDATE() + INTERVAL 14 DAY)
     ORDER BY r.due_date ASC, r.due_time ASC LIMIT 8'
);
$st->execute([$uid, $uid]);
$reminders = $st->fetchAll();

// Imunisasi yang tanggal berikutnya sudah lewat (belum ada catatan pemberian sesudahnya)
$st = $pdo->prepare(
    'SELECT i.id, i.vaccine_name, i.dose, i.next_date, c.full_name AS child_name, c.id AS child_id
     FROM immunization_records i JOIN children c ON c.id = i.child_id
     WHERE c.user_id = ? AND i.next_date IS NOT NULL AND i.next_date < CURDATE()
       AND NOT EXISTS (
           SELECT 1 FROM immunization_records j
           WHERE j.child_id = i.child_id AND j.status = "diberikan" AND j.given_at >= i.next_date
             AND j.vaccine_name = i.vaccine_name
       )
     ORDER BY i.next_date DESC LIMIT 5'
);
$st->execute([$uid]);
$overdueImm = $st->fetchAll();

// Catatan kesehatan terbaru
$st = $pdo->prepare(
    'SELECT h.id, h.event_date, h.record_type, h.symptoms, h.temperature, c.full_name AS child_name
     FROM health_records h JOIN children c ON c.id = h.child_id
     WHERE c.user_id = ? ORDER BY h.event_date DESC, h.id DESC LIMIT 4'
);
$st->execute([$uid]);
$health = $st->fetchAll();

// Statistik
$st = $pdo->prepare('SELECT COUNT(*) FROM growth_records g JOIN children c ON c.id = g.child_id WHERE c.user_id = ? AND g.measured_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")');
$st->execute([$uid]);
$growthThisMonth = (int) $st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM reminders r JOIN children c ON c.id = r.child_id WHERE r.user_id = ? AND c.user_id = ? AND r.is_done = 0 AND r.due_date < CURDATE()');
$st->execute([$uid, $uid]);
$overdueReminders = (int) $st->fetchColumn();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require APP_ROOT . '/includes/header.php';
?>
<div class="mb-3">
    <h2 class="h4 mb-1">Halo, <?= e(explode(' ', current_user()['full_name'])[0]) ?> 👋</h2>
    <p class="text-muted mb-0"><?= e(tgl_indo(today(), true)) ?></p>
</div>

<!-- Ajakan pasang aplikasi (muncul hanya bila browser mendukung) -->
<div class="card mb-3 js-install-app d-none d-lg-none" role="button">
    <div class="card-body d-flex align-items-center gap-3 py-3">
        <img src="<?= e(url('assets/images/icons/icon-192.png')) ?>" alt="" width="44" height="44" class="rounded-3">
        <div class="flex-fill">
            <div class="fw-bold">Pasang Pantau Balita di HP</div>
            <div class="small text-muted">Buka lebih cepat langsung dari layar utama.</div>
        </div>
        <span class="btn btn-sm btn-primary">Pasang</span>
    </div>
</div>

<!-- Statistik -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card"><span class="stat-icon bg-soft-primary"><i class="fa-solid fa-children"></i></span><div><div class="stat-value"><?= count($children) ?></div><div class="stat-label">Anak terdaftar</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><span class="stat-icon bg-soft-sky"><i class="fa-solid fa-weight-scale"></i></span><div><div class="stat-value"><?= $growthThisMonth ?></div><div class="stat-label">Pengukuran bulan ini</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><span class="stat-icon bg-soft-peach"><i class="fa-solid fa-bell"></i></span><div><div class="stat-value"><?= count($reminders) ?></div><div class="stat-label">Pengingat ≤ 14 hari</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><span class="stat-icon bg-soft-rose"><i class="fa-solid fa-clock-rotate-left"></i></span><div><div class="stat-value"><?= $overdueReminders + count($overdueImm) ?></div><div class="stat-label">Terlewat</div></div></div></div>
</div>

<!-- Tombol cepat -->
<div class="quick-actions mb-4">
    <a class="quick-action" href="<?= e(url('children/create.php')) ?>"><i class="fa-solid fa-user-plus bg-soft-primary"></i>Tambah Anak</a>
    <a class="quick-action" href="<?= e(url('growth/create.php')) ?>"><i class="fa-solid fa-ruler-vertical bg-soft-sky"></i>Catat Pertumbuhan</a>
    <a class="quick-action" href="<?= e(url('development/observe.php')) ?>"><i class="fa-solid fa-puzzle-piece bg-soft-peach"></i>Catat Perkembangan</a>
    <a class="quick-action" href="<?= e(url('immunization/create.php')) ?>"><i class="fa-solid fa-syringe bg-soft-rose"></i>Catat Imunisasi</a>
    <a class="quick-action" href="<?= e(url('health/create.php')) ?>"><i class="fa-solid fa-notes-medical bg-soft-primary"></i>Catat Kesehatan</a>
</div>

<!-- Daftar anak -->
<div class="d-flex align-items-center justify-content-between mb-2">
    <h3 class="section-title">Anak Saya</h3>
    <?php if ($children): ?><a href="<?= e(url('children/index.php')) ?>" class="small fw-bold">Kelola</a><?php endif; ?>
</div>
<?php if (!$children): ?>
    <div class="card mb-4"><div class="card-body">
        <?= empty_state('fa-baby', 'Belum ada data anak', 'Tambahkan profil anak pertama Anda untuk mulai mencatat pertumbuhan, perkembangan, dan imunisasi.', 'children/create.php', 'Tambah Anak') ?>
    </div></div>
<?php else: ?>
<div class="row g-3 mb-4">
    <?php foreach ($children as $c): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card child-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?= child_avatar($c) ?>
                    <div class="min-w-0">
                        <a href="<?= e(url('children/detail.php?id=' . $c['id'])) ?>" class="fw-bold text-decoration-none text-body d-block text-truncate"><?= e($c['full_name']) ?></a>
                        <div class="small text-muted"><?= e(gender_label($c['gender'])) ?> · <?= e(age_label($c['birth_date'])) ?></div>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><div class="metric"><div class="metric-label">Berat</div><div class="metric-value"><?= $c['growth'] ? e(num($c['growth']['weight'], 2)) . ' kg' : '-' ?></div></div></div>
                    <div class="col-6"><div class="metric"><div class="metric-label"><?= ($c['growth']['measure_method'] ?? 'length') === 'height' ? 'Tinggi' : 'Panjang' ?></div><div class="metric-value"><?= $c['growth'] ? e(num($c['growth']['height'])) . ' cm' : '-' ?></div></div></div>
                </div>
                <div class="small text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i>Ukur terakhir: <?= $c['growth'] ? e(tgl_indo($c['growth']['measured_at'])) : 'belum ada' ?></div>
                <div class="small text-muted"><i class="fa-solid fa-syringe me-1"></i>Imunisasi berikutnya:
                    <?php if ($c['next_imm']): ?>
                        <strong class="text-body"><?= e($c['next_imm']['vaccine_name']) ?></strong> · <?= e(tgl_indo($c['next_imm']['next_date'])) ?>
                    <?php else: ?>belum dicatat<?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <a href="<?= e(url('growth/create.php?child_id=' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="fa-solid fa-plus"></i> Ukur</a>
                <a href="<?= e(url('growth/chart.php?child_id=' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="fa-solid fa-chart-line"></i> Grafik</a>
                <a href="<?= e(url('children/detail.php?id=' . $c['id'])) ?>" class="btn btn-sm btn-outline-secondary flex-fill">Detail</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-bell me-2 text-primary"></i>Pengingat Mendatang</span>
                <a href="<?= e(url('reminders/create.php')) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-plus"></i></a>
            </div>
            <div class="card-body py-2">
                <?php if (!$reminders && !$overdueImm): ?>
                    <?= empty_state('fa-bell-slash', 'Tidak ada pengingat dekat', 'Pengingat untuk 14 hari ke depan akan tampil di sini.') ?>
                <?php endif; ?>
                <?php foreach ($overdueImm as $o): ?>
                    <div class="list-item">
                        <span class="list-icon bg-soft-rose"><i class="fa-solid fa-syringe"></i></span>
                        <div class="flex-fill">
                            <div class="fw-bold"><?= e($o['vaccine_name']) ?> <span class="badge badge-soft-danger ms-1">Lewat jadwal</span></div>
                            <div class="small text-muted"><?= e($o['child_name']) ?> · dijadwalkan <?= e(tgl_indo($o['next_date'])) ?> (<?= e(relative_day_label($o['next_date'])) ?>)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($reminders as $r): $late = $r['due_date'] < today(); ?>
                    <div class="list-item">
                        <span class="list-icon <?= $late ? 'bg-soft-rose' : 'bg-soft-peach' ?>"><i class="fa-solid <?= e(REMINDER_ICONS[$r['category']]) ?>"></i></span>
                        <div class="flex-fill">
                            <div class="fw-bold"><?= e($r['title']) ?> <?php if ($late): ?><span class="badge badge-soft-danger ms-1">Terlewat</span><?php endif; ?></div>
                            <div class="small text-muted"><?= e($r['child_name']) ?> · <?= e(tgl_indo($r['due_date'])) ?><?= $r['due_time'] ? ' ' . e(substr($r['due_time'], 0, 5)) : '' ?> (<?= e(relative_day_label($r['due_date'])) ?>)</div>
                        </div>
                        <form method="post" action="<?= e(url('reminders/toggle.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <input type="hidden" name="back" value="dashboard">
                            <button class="btn btn-sm btn-outline-success" title="Tandai selesai" aria-label="Tandai selesai"><i class="fa-solid fa-check"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-notes-medical me-2 text-primary"></i>Catatan Kesehatan Terbaru</span>
                <a href="<?= e(url('health/index.php')) ?>" class="small fw-bold">Semua</a>
            </div>
            <div class="card-body py-2">
                <?php if (!$health): ?>
                    <?= empty_state('fa-heart-pulse', 'Belum ada catatan kesehatan', 'Catat keluhan, kunjungan dokter, atau alergi anak di sini.') ?>
                <?php endif; ?>
                <?php foreach ($health as $h): ?>
                    <div class="list-item">
                        <span class="list-icon bg-soft-primary"><i class="fa-solid <?= e(HEALTH_ICONS[$h['record_type']]) ?>"></i></span>
                        <div class="flex-fill min-w-0">
                            <div class="fw-bold"><?= e(HEALTH_TYPES[$h['record_type']]) ?> <span class="text-muted fw-normal small">· <?= e($h['child_name']) ?></span></div>
                            <div class="small text-muted text-truncate"><?= e(tgl_indo($h['event_date'])) ?><?= $h['temperature'] ? ' · ' . e(num($h['temperature'])) . '°C' : '' ?><?= $h['symptoms'] ? ' · ' . e(mb_strimwidth($h['symptoms'], 0, 70, '…')) : '' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
