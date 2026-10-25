<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/growth_standards.php';
require_login();
require __DIR__ . '/_data.php';

[$children, $child, $from, $to] = report_params();
$data = report_data($child, $from, $to);
$qs = http_build_query(['child_id' => $child['id'], 'from' => $from, 'to' => $to]);

$pageTitle = 'Laporan';
$activeMenu = 'reports';
require APP_ROOT . '/includes/header.php';
?>
<form method="get" class="card card-body filter-bar mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small" for="child_id">Anak</label><select class="form-select" id="child_id" name="child_id"><?= child_options($children, (int) $child['id']) ?></select></div>
        <div class="col-6 col-md-3"><label class="form-label small" for="from">Periode dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small" for="to">Sampai (tanggal laporan)</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>" max="<?= e(today()) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i> Tampilkan</button></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-2">
        <span class="small text-muted align-self-center">Pilihan cepat:</span>
        <?php foreach (['3 bulan' => '-3 months', '6 bulan' => '-6 months', '1 tahun' => '-1 year'] as $l => $m): ?>
            <a class="btn btn-sm btn-light border" href="?<?= e(http_build_query(['child_id' => $child['id'], 'from' => max($child['birth_date'], date('Y-m-d', strtotime($m))), 'to' => today()])) ?>"><?= e($l) ?> terakhir</a>
        <?php endforeach; ?>
        <a class="btn btn-sm btn-light border" href="?<?= e(http_build_query(['child_id' => $child['id'], 'from' => $child['birth_date'], 'to' => today()])) ?>">Sejak lahir</a>
    </div>
</form>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <?= child_avatar($child) ?>
        <div class="flex-fill">
            <div class="fw-bold fs-5"><?= e($child['full_name']) ?></div>
            <div class="small text-muted">Periode <?= e(tgl_indo($from)) ?> s.d. <?= e(tgl_indo($to)) ?> · usia pada tanggal laporan: <strong><?= e(age_label($child['birth_date'], $to)) ?></strong></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('reports/print.php?' . $qs)) ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-print me-1"></i> Cetak / PDF</a>
            <a href="<?= e(url('reports/export.php?' . $qs)) ?>" class="btn btn-outline-primary" data-no-skeleton download><i class="fa-solid fa-file-csv me-1"></i> Ekspor CSV</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php
    $boxes = [
        ['fa-weight-scale', 'bg-soft-sky', count($data['growth']), 'Pengukuran'],
        ['fa-puzzle-piece', 'bg-soft-peach', array_sum(array_column($data['dev'], 'total')), 'Indikator diamati'],
        ['fa-syringe', 'bg-soft-rose', count($data['imm']), 'Catatan imunisasi'],
        ['fa-notes-medical', 'bg-soft-primary', count($data['health']), 'Catatan kesehatan'],
    ];
    foreach ($boxes as [$icon, $bg, $val, $label]): ?>
        <div class="col-6 col-md-3"><div class="card stat-card"><span class="stat-icon <?= $bg ?>"><i class="fa-solid <?= $icon ?>"></i></span><div><div class="stat-value"><?= (int) $val ?></div><div class="stat-label"><?= e($label) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card mt-3">
    <div class="card-header">Isi laporan</div>
    <div class="card-body small">
        <p class="mb-2">Laporan cetak memuat: profil anak, usia pada tanggal laporan, riwayat pengukuran beserta z-score WHO, grafik pertumbuhan,
            ringkasan perkembangan, riwayat imunisasi, riwayat kesehatan, alergi yang dicatat, dan pengingat yang relevan.</p>
        <p class="mb-0 text-muted">Tips: pada jendela cetak pilih "Simpan sebagai PDF" untuk menyimpan berkas, lalu bawa saat konsultasi dengan dokter atau bidan.</p>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
