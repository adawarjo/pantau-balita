<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function health_defaults(int $childId = 0): array
{
    return ['child_id' => $childId, 'event_date' => today(), 'record_type' => 'sakit', 'symptoms' => '', 'temperature' => '',
        'medicine_name' => '', 'medicine_dose' => '', 'allergies' => '', 'facility_visit' => '', 'notes' => ''];
}

function health_validate(): array
{
    $d = [];
    foreach (array_keys(health_defaults()) as $k) {
        $d[$k] = $k === 'child_id' ? post_int('child_id') : input($k);
    }
    $err = [];
    $child = find_child($d['child_id']);
    if (!$child) $err['child_id'] = 'Pilih anak.';
    if (!valid_date($d['event_date'])) $err['event_date'] = 'Tanggal tidak valid.';
    elseif ($d['event_date'] > today()) $err['event_date'] = 'Tanggal tidak boleh di masa depan.';
    elseif ($child && $d['event_date'] < $child['birth_date']) $err['event_date'] = 'Tanggal sebelum tanggal lahir anak.';
    if (!isset(HEALTH_TYPES[$d['record_type']])) $err['record_type'] = 'Pilih jenis catatan.';
    $t = parse_decimal($d['temperature'], 34, 43);
    if ($t === false) $err['temperature'] = 'Suhu 34 - 43 °C (mis. 38,2).';
    if (!str_len_ok($d['medicine_name'], 120)) $err['medicine_name'] = 'Maksimal 120 karakter.';
    if (!str_len_ok($d['medicine_dose'], 120)) $err['medicine_dose'] = 'Maksimal 120 karakter.';
    if (!str_len_ok($d['allergies'], 255)) $err['allergies'] = 'Maksimal 255 karakter.';
    foreach (['symptoms', 'facility_visit', 'notes'] as $k) {
        if (!str_len_ok($d[$k], 5000)) $err[$k] = 'Maksimal 5000 karakter.';
    }
    if ($d['symptoms'] === '' && $d['notes'] === '' && $d['facility_visit'] === '' && $d['allergies'] === '' && $d['medicine_name'] === '') {
        $err['symptoms'] = 'Isi minimal salah satu: keluhan, obat, alergi, kunjungan, atau catatan.';
    }
    $clean = $d;
    foreach (['symptoms', 'medicine_name', 'medicine_dose', 'allergies', 'facility_visit', 'notes'] as $k) {
        $clean[$k] = null_if_empty($d[$k]);
    }
    $clean['temperature'] = $t ?: null;
    return [$d, $err, $clean, $child];
}

function health_form(array $d, array $err, array $children, ?int $id = null): void
{
    ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required" for="child_id">Anak</label>
                <select class="form-select<?= invalid($err, 'child_id') ?>" id="child_id" name="child_id" required>
                    <option value="">— Pilih anak —</option><?= child_options($children, (int) $d['child_id']) ?>
                </select><?= field_error($err, 'child_id') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="event_date">Tanggal kejadian</label>
                <input type="date" class="form-control<?= invalid($err, 'event_date') ?>" id="event_date" name="event_date" value="<?= e($d['event_date']) ?>" max="<?= e(today()) ?>" required>
                <?= field_error($err, 'event_date') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="record_type">Jenis catatan</label>
                <select class="form-select<?= invalid($err, 'record_type') ?>" id="record_type" name="record_type">
                    <?php foreach (HEALTH_TYPES as $k => $l): ?><option value="<?= e($k) ?>" <?= $d['record_type'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select><?= field_error($err, 'record_type') ?>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="symptoms">Keluhan / gejala</label>
                <textarea class="form-control<?= invalid($err, 'symptoms') ?>" id="symptoms" name="symptoms" rows="2" maxlength="5000" placeholder="mis. batuk pilek sejak 2 hari"><?= e($d['symptoms']) ?></textarea>
                <?= field_error($err, 'symptoms') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="temperature">Suhu tubuh (°C)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'temperature') ?>" id="temperature" name="temperature" value="<?= e($d['temperature']) ?>" placeholder="bila diukur">
                <?= field_error($err, 'temperature') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="medicine_name">Nama obat yang diberikan</label>
                <input class="form-control<?= invalid($err, 'medicine_name') ?>" id="medicine_name" name="medicine_name" value="<?= e($d['medicine_name']) ?>" maxlength="120">
                <?= field_error($err, 'medicine_name') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="medicine_dose">Dosis sesuai resep / petunjuk tenaga kesehatan</label>
                <input class="form-control<?= invalid($err, 'medicine_dose') ?>" id="medicine_dose" name="medicine_dose" value="<?= e($d['medicine_dose']) ?>" maxlength="120" placeholder="salin persis dari resep / label">
                <div class="form-text">Aplikasi tidak menghitung atau menyarankan dosis obat.</div>
                <?= field_error($err, 'medicine_dose') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="allergies">Alergi yang diketahui</label>
                <input class="form-control<?= invalid($err, 'allergies') ?>" id="allergies" name="allergies" value="<?= e($d['allergies']) ?>" maxlength="255" placeholder="mis. alergi susu sapi">
                <?= field_error($err, 'allergies') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="facility_visit">Kunjungan ke fasilitas kesehatan</label>
                <textarea class="form-control<?= invalid($err, 'facility_visit') ?>" id="facility_visit" name="facility_visit" rows="2" maxlength="5000" placeholder="nama faskes, dokter/bidan, hasil pemeriksaan"><?= e($d['facility_visit']) ?></textarea>
                <?= field_error($err, 'facility_visit') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Catatan tambahan</label>
                <textarea class="form-control<?= invalid($err, 'notes') ?>" id="notes" name="notes" rows="2" maxlength="5000"><?= e($d['notes']) ?></textarea>
                <?= field_error($err, 'notes') ?>
            </div>
        </div>
        <div class="note-box mt-3 small"><i class="fa-solid fa-triangle-exclamation me-1"></i>Catatan ini untuk dokumentasi. Aplikasi tidak memberikan diagnosis atau resep. Bila anak demam tinggi, kejang, sesak napas, lemas, atau tampak sangat sakit, segera bawa ke fasilitas kesehatan.</div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
            <a href="<?= e(url('health/index.php')) ?>" class="btn btn-light border">Batal</a>
        </div>
    </form>
    <?php
}
