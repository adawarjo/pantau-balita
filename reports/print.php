<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/growth_standards.php';
require_login();
require __DIR__ . '/_data.php';

[$children, $child, $from, $to] = report_params();
$data = report_data($child, $from, $to);
$payload = growth_chart_payload($child, $data['growth']);
$user = current_user();
$lastGrowth = $data['growth'] ? end($data['growth']) : null;

$pageTitle = 'Laporan ' . $child['full_name'];
$layout = 'print';
$useChart = true;
require APP_ROOT . '/includes/header.php';
?>
<div class="content" style="max-width: 960px;">
    <div class="no-print d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <a href="<?= e(url('reports/index.php?' . http_build_query(['child_id' => $child['id'], 'from' => $from, 'to' => $to]))) ?>" class="btn btn-light border"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
        <button type="button" class="btn btn-primary js-print"><i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF</button>
    </div>

    <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="40" height="40">
            <div>
                <h1 class="h5 mb-0">Laporan Tumbuh Kembang Anak</h1>
                <div class="small text-muted"><?= e(APP_NAME) ?> · dicetak <?= e(datetime_indo(date('Y-m-d H:i:s'))) ?></div>
            </div>
        </div>
        <div class="text-end small">
            <div>Periode: <strong><?= e(tgl_indo($from)) ?> – <?= e(tgl_indo($to)) ?></strong></div>
            <div>Orangtua: <?= e($user['full_name']) ?></div>
        </div>
    </div>

    <!-- Profil -->
    <div class="card mb-3">
        <div class="card-header">1. Profil Anak</div>
        <div class="card-body">
            <div class="row small g-2">
                <div class="col-6 col-md-4"><span class="text-muted">Nama</span><div class="fw-bold"><?= e($child['full_name']) ?></div></div>
                <div class="col-6 col-md-4"><span class="text-muted">Jenis kelamin</span><div class="fw-bold"><?= e(gender_label($child['gender'])) ?></div></div>
                <div class="col-6 col-md-4"><span class="text-muted">Tempat, tanggal lahir</span><div class="fw-bold"><?= e(($child['birth_place'] ? $child['birth_place'] . ', ' : '') . tgl_indo($child['birth_date'])) ?></div></div>
                <div class="col-6 col-md-4"><span class="text-muted">Usia pada tanggal laporan (<?= e(tgl_indo($to)) ?>)</span><div class="fw-bold"><?= e(age_label($child['birth_date'], $to)) ?></div></div>
                <div class="col-6 col-md-4"><span class="text-muted">Berat / panjang lahir</span><div class="fw-bold"><?= $child['birth_weight'] ? e(num($child['birth_weight'], 2)) . ' kg' : '-' ?> / <?= $child['birth_length'] ? e(num($child['birth_length'])) . ' cm' : '-' ?></div></div>
                <div class="col-6 col-md-4"><span class="text-muted">Alergi yang dicatat</span><div class="fw-bold text-danger"><?= $data['allergies'] ? e(implode('; ', $data['allergies'])) : '<span class="text-body">Tidak ada catatan</span>' ?></div></div>
                <?php if ($child['birth_history']): ?><div class="col-12"><span class="text-muted">Riwayat kelahiran</span><div><?= nl2br(e($child['birth_history'])) ?></div></div><?php endif; ?>
                <?php if ($child['notes']): ?><div class="col-12"><span class="text-muted">Catatan</span><div><?= nl2br(e($child['notes'])) ?></div></div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pertumbuhan -->
    <div class="card mb-3">
        <div class="card-header">2. Riwayat Pengukuran Pertumbuhan</div>
        <?php if (!$data['growth']): ?>
            <div class="card-body small text-muted">Tidak ada pengukuran pada periode ini.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead><tr><th>Tanggal</th><th>Usia</th><th class="text-end">BB (kg)</th><th class="text-end">PB/TB (cm)</th><th class="text-end">LK (cm)</th><th class="text-end">LiLA</th><th>BB/U</th><th>PB/U-TB/U</th><th>BB/PB-BB/TB</th><th>IMT/U</th></tr></thead>
                <tbody>
                <?php foreach ($data['growth'] as $r):
                    $a = assess_growth($child, $r);
                    $m = [];
                    foreach ($a['items'] as $it) $m[$it['code']] = $it; ?>
                    <tr>
                        <td class="text-nowrap"><?= e(tgl_indo($r['measured_at'], false, true)) ?></td>
                        <td class="text-nowrap"><?= e(age_label($child['birth_date'], $r['measured_at'])) ?></td>
                        <td class="text-end"><?= e(num($r['weight'], 2)) ?></td>
                        <td class="text-end"><?= e(num($r['height'])) ?> <?= $r['measure_method'] === 'height' ? 'TB' : 'PB' ?></td>
                        <td class="text-end"><?= e(num($r['head_circ'])) ?></td>
                        <td class="text-end"><?= e(num($r['muac'])) ?></td>
                        <?php foreach (['bbu', 'pbu', 'bbpb', 'imtu'] as $c): ?>
                            <td class="text-nowrap"><?= isset($m[$c]) ? e(z_format($m[$c]['z'])) . '<div class="text-muted" style="font-size:.85em">' . e($m[$c]['category']) . '</div>' : '-' ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body small text-muted pt-1"><?= e(MEDICAL_DISCLAIMER) ?> Referensi: <?= e(WHO_SOURCE_NOTE) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($data['growth']): ?>
    <div class="card mb-3">
        <div class="card-header">3. Grafik Pertumbuhan</div>
        <div class="card-body">
            <div class="row g-2">
                <?php if ($payload['who']): ?>
                    <div class="col-6"><div class="small fw-bold mb-1">Berat menurut umur (kurva WHO)</div><div class="chart-box"><canvas id="wWeight"></canvas></div></div>
                    <div class="col-6"><div class="small fw-bold mb-1">Panjang/tinggi menurut umur (kurva WHO)</div><div class="chart-box"><canvas id="wHeight"></canvas></div></div>
                    <?php if ($payload['byAge']['head']): ?><div class="col-6"><div class="small fw-bold mb-1">Lingkar kepala menurut umur (kurva WHO)</div><div class="chart-box"><canvas id="wHead"></canvas></div></div><?php endif; ?>
                <?php else: ?>
                    <div class="col-6"><div class="small fw-bold mb-1">Berat badan</div><div class="chart-box"><canvas id="cWeight"></canvas></div></div>
                    <div class="col-6"><div class="small fw-bold mb-1">Panjang/tinggi badan</div><div class="chart-box"><canvas id="cHeight"></canvas></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Perkembangan -->
    <div class="card mb-3">
        <div class="card-header">4. Ringkasan Perkembangan (observasi orangtua)</div>
        <?php if (!$data['dev']): ?>
            <div class="card-body small text-muted">Tidak ada observasi perkembangan pada periode ini.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead><tr><th>Kelompok usia</th><th>Bidang</th><th class="text-end">Sudah dapat</th><th class="text-end">Belum dapat</th><th class="text-end">Belum diamati</th><th>Observasi terakhir</th></tr></thead>
                <tbody>
                <?php foreach ($data['dev'] as $d): ?>
                    <tr><td><?= e($d['age_group_label']) ?></td><td><?= e(DOMAIN_LABELS[$d['domain']]) ?></td><td class="text-end"><?= (int) $d['sudah'] ?>/<?= (int) $d['total'] ?></td><td class="text-end"><?= (int) $d['belum'] ?></td><td class="text-end"><?= (int) $d['belum_diamati'] ?></td><td><?= e(tgl_indo($d['last_date'], false, true)) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body small pt-1">
            <?php if ($data['dev_belum']): ?>
                <div class="fw-bold mb-1">Kemampuan yang ditandai "Belum Dapat" (untuk didiskusikan dengan tenaga kesehatan):</div>
                <ul class="mb-2">
                    <?php foreach ($data['dev_belum'] as $b): ?><li><?= e($b['description']) ?> <span class="text-muted">(<?= e($b['age_group_label']) ?>, diamati <?= e(tgl_indo($b['observed_at'], false, true)) ?>)</span></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="text-muted">Indikator disarikan dari pedoman SDIDTK/Buku KIA dan diisi oleh orangtua; bukan hasil skrining klinis.</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Imunisasi -->
    <div class="card mb-3">
        <div class="card-header">5. Riwayat Imunisasi yang Dicatat</div>
        <?php if (!$data['imm']): ?>
            <div class="card-body small text-muted">Tidak ada catatan imunisasi pada periode ini.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead><tr><th>Tanggal</th><th>Vaksin</th><th>Dosis</th><th>Status</th><th>Tempat</th><th>Batch</th><th>Reaksi/catatan</th><th>Berikutnya</th><th>Sumber data</th></tr></thead>
                <tbody>
                <?php foreach ($data['imm'] as $i): ?>
                    <tr><td class="text-nowrap"><?= e(tgl_indo($i['given_at'], false, true)) ?></td><td><?= e($i['vaccine_name']) ?></td><td><?= e($i['dose'] ?: '-') ?></td><td><?= e(IMM_STATUS[$i['status']]) ?></td>
                        <td><?= e($i['location'] ?: '-') ?></td><td><?= e($i['batch_number'] ?: '-') ?></td><td><?= e($i['reaction_notes'] ?: '-') ?></td><td class="text-nowrap"><?= e(tgl_indo($i['next_date'], false, true)) ?></td><td><?= e(IMM_SOURCE[$i['record_source']]) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body small text-muted pt-1">Daftar ini adalah catatan orangtua dan tidak menyatakan kelengkapan imunisasi. Cocokkan dengan Buku KIA.</div>
        <?php endif; ?>
    </div>

    <!-- Kesehatan -->
    <div class="card mb-3">
        <div class="card-header">6. Riwayat Kesehatan</div>
        <?php if (!$data['health']): ?>
            <div class="card-body small text-muted">Tidak ada catatan kesehatan pada periode ini.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Keluhan</th><th>Suhu</th><th>Obat & dosis (sesuai resep)</th><th>Kunjungan / catatan</th></tr></thead>
                <tbody>
                <?php foreach ($data['health'] as $h): ?>
                    <tr><td class="text-nowrap"><?= e(tgl_indo($h['event_date'], false, true)) ?></td><td><?= e(HEALTH_TYPES[$h['record_type']]) ?></td><td><?= nl2br(e($h['symptoms'] ?: '-')) ?><?= $h['allergies'] ? '<div class="text-danger">Alergi: ' . e($h['allergies']) . '</div>' : '' ?></td>
                        <td class="text-nowrap"><?= $h['temperature'] ? e(num($h['temperature'])) . '°C' : '-' ?></td><td><?= e($h['medicine_name'] ?: '-') ?><?= $h['medicine_dose'] ? ' — ' . e($h['medicine_dose']) : '' ?></td>
                        <td><?= nl2br(e(trim(($h['facility_visit'] ?? '') . "\n" . ($h['notes'] ?? '')) ?: '-')) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pengingat -->
    <div class="card mb-3">
        <div class="card-header">7. Pengingat Terkait</div>
        <?php if (!$data['reminders']): ?>
            <div class="card-body small text-muted">Tidak ada pengingat.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead><tr><th>Tanggal</th><th>Judul</th><th>Kategori</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($data['reminders'] as $r): ?>
                    <tr><td class="text-nowrap"><?= e(tgl_indo($r['due_date'], false, true)) ?></td><td><?= e($r['title']) ?></td><td><?= e(REMINDER_CATEGORIES[$r['category']]) ?></td><td><?= $r['is_done'] ? 'Selesai' : ($r['due_date'] < today() ? 'Terlewat' : 'Belum selesai') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="small text-muted border-top pt-2">
        Catatan untuk tenaga kesehatan: laporan ini disusun dari data yang dimasukkan orangtua melalui aplikasi <?= e(APP_NAME) ?> dan merupakan alat bantu pemantauan, bukan diagnosis medis.
    </div>
</div>
<?php
$pageScripts = '<script src="' . e(asset('js/charts.js')) . '"></script>'
    . '<script>Chart.defaults.animation = false; Chart.defaults.font.size = 10; renderGrowthCharts(' . json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP) . ');'
    . 'window.addEventListener("beforeprint", () => { for (const id in Chart.instances) Chart.instances[id].resize(); });</script>';
require APP_ROOT . '/includes/footer.php';
