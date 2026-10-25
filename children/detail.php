<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/growth_standards.php';
require_login();

$child = require_child(query_int('id'));
$cid = (int) $child['id'];
$pdo = db();

$st = $pdo->prepare('SELECT * FROM growth_records WHERE child_id = ? ORDER BY measured_at DESC, id DESC LIMIT 1');
$st->execute([$cid]);
$last = $st->fetch() ?: null;
$assessment = $last ? assess_growth($child, $last) : null;

$counts = [];
foreach (['growth_records' => 'growth', 'developmental_observations' => 'dev', 'immunization_records' => 'imm', 'health_records' => 'health'] as $table => $k) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE child_id = ?");
    $s->execute([$cid]);
    $counts[$k] = (int) $s->fetchColumn();
}

$st = $pdo->prepare('SELECT * FROM immunization_records WHERE child_id = ? ORDER BY COALESCE(given_at, next_date) DESC LIMIT 5');
$st->execute([$cid]);
$imms = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM health_records WHERE child_id = ? ORDER BY event_date DESC LIMIT 5');
$st->execute([$cid]);
$healths = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM reminders WHERE child_id = ? AND user_id = ? AND is_done = 0 ORDER BY due_date LIMIT 5');
$st->execute([$cid, current_user_id()]);
$rems = $st->fetchAll();

$st = $pdo->prepare("SELECT allergies FROM health_records WHERE child_id = ? AND allergies IS NOT NULL AND allergies <> '' ORDER BY event_date DESC");
$st->execute([$cid]);
$allergies = array_unique($st->fetchAll(PDO::FETCH_COLUMN));

$pageTitle = $child['full_name'];
$activeMenu = 'children';
require APP_ROOT . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="mb-3"><?= child_avatar($child, 'lg') ?></div>
                <h2 class="h5 mb-1"><?= e($child['full_name']) ?></h2>
                <div class="text-muted"><?= e(gender_label($child['gender'])) ?></div>
                <div class="fs-5 fw-bold text-primary mt-2"><?= e(age_label($child['birth_date'])) ?></div>
                <div class="small text-muted">usia per <?= e(tgl_indo(today())) ?></div>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tempat, tgl lahir</span><span class="text-end"><?= e(($child['birth_place'] ? $child['birth_place'] . ', ' : '') . tgl_indo($child['birth_date'])) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Berat lahir</span><span><?= $child['birth_weight'] ? e(num($child['birth_weight'], 2)) . ' kg' : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Panjang lahir</span><span><?= $child['birth_length'] ? e(num($child['birth_length'])) . ' cm' : '-' ?></span></li>
                <?php if ($allergies): ?>
                <li class="list-group-item"><span class="text-muted d-block">Alergi yang dicatat</span><span class="text-danger fw-bold"><?= e(implode('; ', $allergies)) ?></span></li>
                <?php endif; ?>
            </ul>
            <?php if ($child['birth_history'] || $child['notes']): ?>
            <div class="card-body small border-top">
                <?php if ($child['birth_history']): ?><div class="mb-2"><div class="text-muted fw-bold">Riwayat kelahiran</div><?= nl2br(e($child['birth_history'])) ?></div><?php endif; ?>
                <?php if ($child['notes']): ?><div><div class="text-muted fw-bold">Catatan</div><?= nl2br(e($child['notes'])) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="card-footer bg-transparent d-flex gap-2">
                <a href="<?= e(url('children/edit.php?id=' . $cid)) ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="fa-solid fa-pen"></i> Edit</a>
                <a href="<?= e(url('reports/index.php?child_id=' . $cid)) ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="fa-solid fa-file-medical"></i> Laporan</a>
                <?= delete_button('children/delete.php', $cid, 'Profil ' . $child['full_name'] . ' dan SELURUH catatannya akan dihapus permanen.') ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="quick-actions mb-3">
            <a class="quick-action" href="<?= e(url('growth/create.php?child_id=' . $cid)) ?>"><i class="fa-solid fa-ruler-vertical bg-soft-sky"></i>Pertumbuhan <span class="text-muted small"><?= $counts['growth'] ?> catatan</span></a>
            <a class="quick-action" href="<?= e(url('development/index.php?child_id=' . $cid)) ?>"><i class="fa-solid fa-puzzle-piece bg-soft-peach"></i>Perkembangan <span class="text-muted small"><?= $counts['dev'] ?> observasi</span></a>
            <a class="quick-action" href="<?= e(url('immunization/index.php?child_id=' . $cid)) ?>"><i class="fa-solid fa-syringe bg-soft-rose"></i>Imunisasi <span class="text-muted small"><?= $counts['imm'] ?> catatan</span></a>
            <a class="quick-action" href="<?= e(url('health/index.php?child_id=' . $cid)) ?>"><i class="fa-solid fa-notes-medical bg-soft-primary"></i>Kesehatan <span class="text-muted small"><?= $counts['health'] ?> catatan</span></a>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-weight-scale me-2 text-primary"></i>Pengukuran Terakhir</span>
                <a href="<?= e(url('growth/chart.php?child_id=' . $cid)) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-chart-line me-1"></i>Grafik</a>
            </div>
            <div class="card-body">
                <?php if (!$last): ?>
                    <?= empty_state('fa-ruler-combined', 'Belum ada pengukuran', 'Catat berat dan panjang/tinggi badan anak.', 'growth/create.php?child_id=' . $cid, 'Catat Pertumbuhan') ?>
                <?php else: ?>
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-6 col-md-3"><div class="child-card"><div class="metric"><div class="metric-label">Berat</div><div class="metric-value"><?= e(num($last['weight'], 2)) ?> kg</div></div></div></div>
                        <div class="col-6 col-md-3"><div class="child-card"><div class="metric"><div class="metric-label"><?= $last['measure_method'] === 'height' ? 'Tinggi' : 'Panjang' ?></div><div class="metric-value"><?= e(num($last['height'])) ?> cm</div></div></div></div>
                        <div class="col-6 col-md-3"><div class="child-card"><div class="metric"><div class="metric-label">Lingkar kepala</div><div class="metric-value"><?= $last['head_circ'] ? e(num($last['head_circ'])) . ' cm' : '-' ?></div></div></div></div>
                        <div class="col-6 col-md-3"><div class="child-card"><div class="metric"><div class="metric-label">LiLA</div><div class="metric-value"><?= $last['muac'] ? e(num($last['muac'])) . ' cm' : '-' ?></div></div></div></div>
                    </div>
                    <p class="small text-muted mb-2">Diukur <?= e(tgl_indo($last['measured_at'])) ?> pada usia <?= e(age_label($child['birth_date'], $last['measured_at'])) ?>.</p>
                    <?php require APP_ROOT . '/growth/_assessment.php'; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa-solid fa-syringe me-2 text-primary"></i>Imunisasi Terbaru</div>
                    <div class="card-body py-2">
                        <?php if (!$imms): ?><p class="text-muted small my-2">Belum ada catatan imunisasi.</p><?php endif; ?>
                        <?php foreach ($imms as $i): ?>
                            <div class="list-item"><div>
                                <div class="fw-bold"><?= e($i['vaccine_name']) ?> <span class="text-muted fw-normal small"><?= e($i['dose']) ?></span></div>
                                <div class="small text-muted"><?= e(IMM_STATUS[$i['status']]) ?> · <?= e(tgl_indo($i['given_at'] ?: $i['next_date'])) ?></div>
                            </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa-solid fa-bell me-2 text-primary"></i>Pengingat Aktif</div>
                    <div class="card-body py-2">
                        <?php if (!$rems): ?><p class="text-muted small my-2">Tidak ada pengingat aktif.</p><?php endif; ?>
                        <?php foreach ($rems as $r): ?>
                            <div class="list-item"><div>
                                <div class="fw-bold"><?= e($r['title']) ?></div>
                                <div class="small text-muted"><?= e(tgl_indo($r['due_date'])) ?> · <?= e(relative_day_label($r['due_date'])) ?></div>
                            </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><i class="fa-solid fa-notes-medical me-2 text-primary"></i>Catatan Kesehatan Terbaru</div>
                    <div class="card-body py-2">
                        <?php if (!$healths): ?><p class="text-muted small my-2">Belum ada catatan kesehatan.</p><?php endif; ?>
                        <?php foreach ($healths as $h): ?>
                            <div class="list-item">
                                <span class="list-icon bg-soft-primary"><i class="fa-solid <?= e(HEALTH_ICONS[$h['record_type']]) ?>"></i></span>
                                <div><div class="fw-bold"><?= e(HEALTH_TYPES[$h['record_type']]) ?> · <span class="fw-normal"><?= e(tgl_indo($h['event_date'])) ?></span></div>
                                <div class="small text-muted"><?= e(mb_strimwidth((string) $h['symptoms'], 0, 120, '…')) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
