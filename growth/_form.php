<?php
/**
 * Formulir & validasi pengukuran pertumbuhan.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function growth_defaults(int $childId = 0): array
{
    return ['child_id' => $childId, 'measured_at' => today(), 'weight' => '', 'height' => '', 'measure_method' => '', 'head_circ' => '', 'muac' => '', 'notes' => ''];
}

/** @return array [$data, $errors, $clean, $child] */
function growth_validate(): array
{
    $d = [
        'child_id'       => post_int('child_id'),
        'measured_at'    => input('measured_at'),
        'weight'         => input('weight'),
        'height'         => input('height'),
        'measure_method' => input('measure_method'),
        'head_circ'      => input('head_circ'),
        'muac'           => input('muac'),
        'notes'          => input('notes'),
    ];
    $err = [];
    $child = find_child($d['child_id']);
    if (!$child) {
        $err['child_id'] = 'Pilih anak.';
    }
    if (!valid_date($d['measured_at'])) {
        $err['measured_at'] = 'Tanggal pengukuran tidak valid.';
    } elseif ($d['measured_at'] > today()) {
        $err['measured_at'] = 'Tanggal pengukuran tidak boleh di masa depan.';
    } elseif ($child && $d['measured_at'] < $child['birth_date']) {
        $err['measured_at'] = 'Tanggal pengukuran tidak boleh sebelum tanggal lahir (' . tgl_indo($child['birth_date']) . ').';
    }
    $w = parse_decimal($d['weight'], 0.5, 40);
    if ($w === null || $w === false) {
        $err['weight'] = 'Berat badan wajib diisi, 0,5 - 40 kg.';
    }
    $h = parse_decimal($d['height'], 30, 130);
    if ($h === null || $h === false) {
        $err['height'] = 'Panjang/tinggi badan wajib diisi, 30 - 130 cm.';
    }
    if (!in_array($d['measure_method'], ['length', 'height'], true)) {
        $err['measure_method'] = 'Pilih cara pengukuran.';
    }
    $hc = parse_decimal($d['head_circ'], 25, 60);
    if ($hc === false) {
        $err['head_circ'] = 'Lingkar kepala 25 - 60 cm.';
    }
    $muac = parse_decimal($d['muac'], 7, 30);
    if ($muac === false) {
        $err['muac'] = 'LiLA 7 - 30 cm.';
    }
    if (!str_len_ok($d['notes'], 2000)) {
        $err['notes'] = 'Catatan maksimal 2000 karakter.';
    }
    $clean = [
        'child_id' => $d['child_id'], 'measured_at' => $d['measured_at'], 'weight' => $w, 'height' => $h,
        'measure_method' => $d['measure_method'], 'head_circ' => $hc, 'muac' => $muac, 'notes' => null_if_empty($d['notes']),
    ];
    return [$d, $err, $clean, $child];
}

function growth_form(array $d, array $err, array $children, ?int $recordId = null): void
{
    $birthMap = [];
    foreach ($children as $c) {
        $birthMap[$c['id']] = $c['birth_date'];
    }
    ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($recordId): ?><input type="hidden" name="id" value="<?= (int) $recordId ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="child_id">Anak</label>
                <select class="form-select<?= invalid($err, 'child_id') ?>" id="child_id" name="child_id" required data-birth-map="<?= e(json_encode($birthMap)) ?>">
                    <option value="">— Pilih anak —</option>
                    <?= child_options($children, (int) $d['child_id']) ?>
                </select>
                <?= field_error($err, 'child_id') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="measured_at">Tanggal pengukuran</label>
                <input type="date" class="form-control<?= invalid($err, 'measured_at') ?>" id="measured_at" name="measured_at" value="<?= e($d['measured_at']) ?>" max="<?= e(today()) ?>" required>
                <?= field_error($err, 'measured_at') ?>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label required" for="weight">Berat badan (kg)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'weight') ?>" id="weight" name="weight" value="<?= e($d['weight']) ?>" placeholder="8,45" required>
                <?= field_error($err, 'weight') ?>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label required" for="height">Panjang / tinggi (cm)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'height') ?>" id="height" name="height" value="<?= e($d['height']) ?>" placeholder="70,5" required>
                <?= field_error($err, 'height') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="measure_method">Cara ukur</label>
                <select class="form-select<?= invalid($err, 'measure_method') ?>" id="measure_method" name="measure_method" <?= $d['measure_method'] ? 'data-keep="1"' : '' ?>>
                    <option value="length" <?= $d['measure_method'] === 'length' ? 'selected' : '' ?>>Berbaring (panjang badan)</option>
                    <option value="height" <?= $d['measure_method'] === 'height' ? 'selected' : '' ?>>Berdiri (tinggi badan)</option>
                </select>
                <div class="form-text">Anjuran: berbaring di bawah 2 tahun, berdiri mulai 2 tahun.</div>
                <?= field_error($err, 'measure_method') ?>
            </div>
            <div class="col-6">
                <label class="form-label" for="head_circ">Lingkar kepala (cm)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'head_circ') ?>" id="head_circ" name="head_circ" value="<?= e($d['head_circ']) ?>" placeholder="opsional">
                <?= field_error($err, 'head_circ') ?>
            </div>
            <div class="col-6">
                <label class="form-label" for="muac">Lingkar lengan atas / LiLA (cm)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'muac') ?>" id="muac" name="muac" value="<?= e($d['muac']) ?>" placeholder="opsional">
                <?= field_error($err, 'muac') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Catatan</label>
                <textarea class="form-control<?= invalid($err, 'notes') ?>" id="notes" name="notes" rows="2" maxlength="2000" placeholder="Contoh: ditimbang di posyandu"><?= e($d['notes']) ?></textarea>
                <?= field_error($err, 'notes') ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
            <a href="<?= e(url('growth/index.php')) ?>" class="btn btn-light border">Batal</a>
        </div>
    </form>
    <?php
}
