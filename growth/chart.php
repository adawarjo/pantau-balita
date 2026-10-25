<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/growth_standards.php';
require_login();

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$childId = query_int('child_id') ?: (int) $children[0]['id'];
$child = require_child($childId);

$st = db()->prepare('SELECT * FROM growth_records WHERE child_id = ? ORDER BY measured_at ASC, id ASC');
$st->execute([$childId]);
$records = $st->fetchAll();
$payload = growth_chart_payload($child, $records);

$last = $records ? end($records) : null;
$assessment = $last ? assess_growth($child, $last) : null;

$pageTitle = 'Grafik Pertumbuhan';
$activeMenu = 'growth';
$useChart = true;
require APP_ROOT . '/includes/header.php';
?>
<form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <label for="child_id" class="fw-bold">Anak:</label>
    <select id="child_id" name="child_id" class="form-select w-auto js-auto-submit"><?= child_options($children, $childId) ?></select>
    <span class="text-muted small"><?= e(gender_label($child['gender'])) ?> · <?= e(age_label($child['birth_date'])) ?></span>
    <a href="<?= e(url('growth/create.php?child_id=' . $childId)) ?>" class="btn btn-primary ms-auto"><i class="fa-solid fa-plus me-1"></i> Catat</a>
</form>

<?php if (!$records): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-chart-line', 'Belum ada data untuk grafik', 'Catat pengukuran pertama ' . $child['full_name'] . ' agar grafik dapat ditampilkan.', 'growth/create.php?child_id=' . $childId, 'Catat Pertumbuhan') ?></div></div>
<?php else: ?>

<?php if ($assessment): ?>
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-scale-balanced me-2 text-primary"></i>Perbandingan Pengukuran Terakhir dengan Standar WHO
        <span class="text-muted fw-normal small">(<?= e(tgl_indo($last['measured_at'])) ?>, usia <?= e(age_label($child['birth_date'], $last['measured_at'])) ?>)</span></div>
    <div class="card-body"><?php require __DIR__ . '/_assessment.php'; ?></div>
</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 gap-2" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabDate" type="button" role="tab">Menurut Tanggal</button></li>
    <?php if ($payload['who']): ?>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabWho" type="button" role="tab">Kurva Standar WHO</button></li>
    <?php endif; ?>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="tabDate" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6"><div class="card"><div class="card-header">Berat Badan</div><div class="card-body"><div class="chart-box"><canvas id="cWeight"></canvas></div></div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-header">Panjang / Tinggi Badan</div><div class="card-body"><div class="chart-box"><canvas id="cHeight"></canvas></div></div></div></div>
            <?php if (array_filter($payload['byDate']['head'], fn($v) => $v !== null)): ?>
            <div class="col-lg-6"><div class="card"><div class="card-header">Lingkar Kepala</div><div class="card-body"><div class="chart-box"><canvas id="cHead"></canvas></div></div></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($payload['who']): ?>
    <div class="tab-pane fade" id="tabWho" role="tabpanel">
        <div class="info-box mb-3"><i class="fa-solid fa-circle-info me-1"></i>Garis putus-putus adalah batas -3, -2, +2, +3 SD dan garis hijau adalah median standar WHO untuk <?= e(mb_strtolower(gender_label($child['gender']))) ?>. Titik berwarna adalah pengukuran anak.</div>
        <div class="row g-3">
            <div class="col-lg-6"><div class="card"><div class="card-header">Berat menurut Umur (BB/U)</div><div class="card-body"><div class="chart-box"><canvas id="wWeight"></canvas></div></div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-header">Panjang/Tinggi menurut Umur (PB/U, TB/U)</div><div class="card-body"><div class="chart-box"><canvas id="wHeight"></canvas></div></div></div></div>
            <?php if ($payload['byAge']['head']): ?>
            <div class="col-lg-6"><div class="card"><div class="card-header">Lingkar Kepala menurut Umur (LK/U)</div><div class="card-body"><div class="chart-box"><canvas id="wHead"></canvas></div></div></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card mt-3">
    <div class="card-header">Riwayat Z-score per Pengukuran</div>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Tanggal</th><th>Usia</th><th class="text-end">BB</th><th class="text-end">PB/TB</th><th>BB/U</th><th>PB/U - TB/U</th><th>BB/PB - BB/TB</th><th>IMT/U</th><th>LK/U</th></tr></thead>
            <tbody>
            <?php foreach (array_reverse($records) as $r):
                $a = assess_growth($child, $r);
                $map = [];
                foreach ($a['items'] as $it) $map[$it['code']] = $it;
            ?>
                <tr>
                    <td class="text-nowrap"><?= e(tgl_indo($r['measured_at'], false, true)) ?></td>
                    <td class="text-nowrap small"><?= e(age_label($child['birth_date'], $r['measured_at'])) ?></td>
                    <td class="text-end"><?= e(num($r['weight'], 2)) ?></td>
                    <td class="text-end"><?= e(num($r['height'])) ?></td>
                    <?php foreach (['bbu', 'pbu', 'bbpb', 'imtu', 'lku'] as $code): ?>
                        <td class="small text-nowrap"><?php if (isset($map[$code])): ?><span class="badge badge-soft-<?= e($map[$code]['color']) ?>" title="<?= e($map[$code]['category']) ?>"><?= e(z_format($map[$code]['z'])) ?></span><?php else: ?>-<?php endif; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body small text-muted border-top">Arahkan kursor ke nilai untuk melihat kategori. <?= e(MEDICAL_DISCLAIMER) ?></div>
</div>
<?php endif; ?>

<?php
$pageScripts = '<script src="' . e(asset('js/charts.js')) . '"></script>'
    . '<script>renderGrowthCharts(' . json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP) . ');</script>';
require APP_ROOT . '/includes/footer.php';
