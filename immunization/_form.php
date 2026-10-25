<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function imm_defaults(int $childId = 0): array
{
    return ['child_id' => $childId, 'vaccine_name' => '', 'dose' => '', 'status' => 'diberikan', 'given_at' => today(), 'location' => '',
        'batch_number' => '', 'reaction_notes' => '', 'next_date' => '', 'record_source' => 'buku_kia', 'make_reminder' => ''];
}

function imm_validate(): array
{
    $d = [
        'child_id'       => post_int('child_id'),
        'vaccine_name'   => input('vaccine_name'),
        'dose'           => input('dose'),
        'status'         => input('status'),
        'given_at'       => input('given_at'),
        'location'       => input('location'),
        'batch_number'   => input('batch_number'),
        'reaction_notes' => input('reaction_notes'),
        'next_date'      => input('next_date'),
        'record_source'  => input('record_source'),
        'make_reminder'  => input('make_reminder'),
    ];
    $err = [];
    $child = find_child($d['child_id']);
    if (!$child) $err['child_id'] = 'Pilih anak.';
    if (mb_strlen($d['vaccine_name']) < 2 || !str_len_ok($d['vaccine_name'], 60)) $err['vaccine_name'] = 'Nama vaksin 2-60 karakter.';
    if (!str_len_ok($d['dose'], 30)) $err['dose'] = 'Maksimal 30 karakter.';
    if (!isset(IMM_STATUS[$d['status']])) $err['status'] = 'Pilih status.';
    if (!isset(IMM_SOURCE[$d['record_source']])) $err['record_source'] = 'Pilih sumber data.';

    if ($d['given_at'] !== '') {
        if (!valid_date($d['given_at'])) $err['given_at'] = 'Tanggal tidak valid.';
        elseif ($d['given_at'] > today()) $err['given_at'] = 'Tanggal pemberian tidak boleh di masa depan.';
        elseif ($child && $d['given_at'] < $child['birth_date']) $err['given_at'] = 'Tanggal pemberian sebelum tanggal lahir.';
    } elseif ($d['status'] === 'diberikan') {
        $err['given_at'] = 'Tanggal pemberian wajib diisi bila status sudah diberikan.';
    }
    if ($d['next_date'] !== '') {
        if (!valid_date($d['next_date'])) $err['next_date'] = 'Tanggal tidak valid.';
        elseif ($d['given_at'] !== '' && valid_date($d['given_at']) && $d['next_date'] <= $d['given_at']) $err['next_date'] = 'Tanggal berikutnya harus setelah tanggal pemberian.';
    } elseif ($d['status'] === 'dijadwalkan') {
        $err['next_date'] = 'Isi tanggal jadwal imunisasi.';
    }
    if ($d['status'] === 'dijadwalkan' && $d['given_at'] !== '') {
        $err['given_at'] = 'Kosongkan tanggal pemberian untuk imunisasi yang masih dijadwalkan.';
    }
    if (!str_len_ok($d['location'], 120)) $err['location'] = 'Maksimal 120 karakter.';
    if (!str_len_ok($d['batch_number'], 60)) $err['batch_number'] = 'Maksimal 60 karakter.';
    if (!str_len_ok($d['reaction_notes'], 2000)) $err['reaction_notes'] = 'Maksimal 2000 karakter.';

    $clean = [
        'child_id' => $d['child_id'], 'vaccine_name' => $d['vaccine_name'], 'dose' => null_if_empty($d['dose']), 'status' => $d['status'],
        'given_at' => null_if_empty($d['given_at']), 'location' => null_if_empty($d['location']), 'batch_number' => null_if_empty($d['batch_number']),
        'reaction_notes' => null_if_empty($d['reaction_notes']), 'next_date' => null_if_empty($d['next_date']), 'record_source' => $d['record_source'],
    ];
    return [$d, $err, $clean, $child];
}

/** Buat pengingat imunisasi dari tanggal berikutnya */
function imm_create_reminder(array $clean, array $child): void
{
    if (!$clean['next_date'] || $clean['next_date'] < today()) {
        return;
    }
    $title = 'Imunisasi ' . $clean['vaccine_name'] . ($clean['status'] === 'dijadwalkan' && $clean['dose'] ? ' ' . $clean['dose'] : '');
    $st = db()->prepare('SELECT 1 FROM reminders WHERE user_id = ? AND child_id = ? AND category = "imunisasi" AND due_date = ? AND title = ?');
    $st->execute([current_user_id(), $child['id'], $clean['next_date'], $title]);
    if ($st->fetchColumn()) {
        return;
    }
    db()->prepare('INSERT INTO reminders (user_id, child_id, title, category, due_date, notes) VALUES (?, ?, ?, "imunisasi", ?, ?)')
        ->execute([current_user_id(), $child['id'], mb_substr($title, 0, 150), $clean['next_date'], 'Dibuat otomatis dari catatan imunisasi.']);
}

function imm_form(array $d, array $err, array $children, ?int $id = null): void
{
    $vaccines = db()->query('SELECT DISTINCT vaccine_name FROM immunization_schedule ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN);
    ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="child_id">Anak</label>
                <select class="form-select<?= invalid($err, 'child_id') ?>" id="child_id" name="child_id" required>
                    <option value="">— Pilih anak —</option><?= child_options($children, (int) $d['child_id']) ?>
                </select><?= field_error($err, 'child_id') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="status">Status</label>
                <select class="form-select<?= invalid($err, 'status') ?>" id="status" name="status">
                    <?php foreach (IMM_STATUS as $k => $l): ?><option value="<?= e($k) ?>" <?= $d['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select><?= field_error($err, 'status') ?>
            </div>
            <div class="col-md-8">
                <label class="form-label required" for="vaccine_name">Nama vaksin</label>
                <input class="form-control<?= invalid($err, 'vaccine_name') ?>" id="vaccine_name" name="vaccine_name" list="vaccineList" value="<?= e($d['vaccine_name']) ?>" maxlength="60" required placeholder="Ketik atau pilih">
                <datalist id="vaccineList"><?php foreach ($vaccines as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?></datalist>
                <div class="form-text">Boleh diisi vaksin lain (mis. vaksin pilihan/anjuran dokter).</div>
                <?= field_error($err, 'vaccine_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="dose">Dosis</label>
                <input class="form-control<?= invalid($err, 'dose') ?>" id="dose" name="dose" list="doseList" value="<?= e($d['dose']) ?>" maxlength="30" placeholder="mis. Dosis 1">
                <datalist id="doseList"><option value="Dosis lahir"><option value="Dosis 1"><option value="Dosis 2"><option value="Dosis 3"><option value="Dosis 4"><option value="Dosis lanjutan"></datalist>
                <?= field_error($err, 'dose') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="given_at">Tanggal pemberian</label>
                <input type="date" class="form-control<?= invalid($err, 'given_at') ?>" id="given_at" name="given_at" value="<?= e($d['given_at']) ?>" max="<?= e(today()) ?>">
                <div class="form-text">Wajib bila status "Sudah diberikan". Kosongkan bila masih dijadwalkan.</div>
                <?= field_error($err, 'given_at') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="next_date">Tanggal imunisasi berikutnya / jadwal</label>
                <input type="date" class="form-control<?= invalid($err, 'next_date') ?>" id="next_date" name="next_date" value="<?= e($d['next_date']) ?>">
                <div class="form-text">Isi sesuai jadwal dari petugas kesehatan / Buku KIA.</div>
                <?= field_error($err, 'next_date') ?>
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="make_reminder" name="make_reminder" <?= $d['make_reminder'] ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="make_reminder">Buat pengingat di dashboard untuk tanggal ini</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="location">Tempat pemberian</label>
                <input class="form-control<?= invalid($err, 'location') ?>" id="location" name="location" value="<?= e($d['location']) ?>" maxlength="120" placeholder="mis. Posyandu Melati, Puskesmas ...">
                <?= field_error($err, 'location') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="batch_number">Nomor batch</label>
                <input class="form-control<?= invalid($err, 'batch_number') ?>" id="batch_number" name="batch_number" value="<?= e($d['batch_number']) ?>" maxlength="60">
                <?= field_error($err, 'batch_number') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="record_source">Sumber data</label>
                <select class="form-select<?= invalid($err, 'record_source') ?>" id="record_source" name="record_source">
                    <?php foreach (IMM_SOURCE as $k => $l): ?><option value="<?= e($k) ?>" <?= $d['record_source'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">Menandai apakah catatan sudah dicocokkan dengan dokumen resmi.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="reaction_notes">Catatan / reaksi setelah imunisasi</label>
                <textarea class="form-control<?= invalid($err, 'reaction_notes') ?>" id="reaction_notes" name="reaction_notes" rows="2" maxlength="2000" placeholder="mis. demam ringan malam hari"><?= e($d['reaction_notes']) ?></textarea>
                <?= field_error($err, 'reaction_notes') ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
            <a href="<?= e(url('immunization/index.php')) ?>" class="btn btn-light border">Batal</a>
        </div>
    </form>
    <?php
}
