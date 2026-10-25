<?php
/**
 * Formulir & validasi anak (dipakai create.php dan edit.php).
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function child_form_defaults(): array
{
    return ['full_name' => '', 'gender' => '', 'birth_place' => '', 'birth_date' => '', 'birth_weight' => '', 'birth_length' => '', 'birth_history' => '', 'notes' => '', 'photo' => null];
}

/** @return array [array $data, array $errors, array $clean] */
function child_validate(): array
{
    $d = [
        'full_name'     => input('full_name'),
        'gender'        => input('gender'),
        'birth_place'   => input('birth_place'),
        'birth_date'    => input('birth_date'),
        'birth_weight'  => input('birth_weight'),
        'birth_length'  => input('birth_length'),
        'birth_history' => input('birth_history'),
        'notes'         => input('notes'),
    ];
    $err = [];
    if (mb_strlen($d['full_name']) < 2 || !str_len_ok($d['full_name'], 100)) {
        $err['full_name'] = 'Nama anak 2-100 karakter.';
    }
    if (!in_array($d['gender'], ['L', 'P'], true)) {
        $err['gender'] = 'Pilih jenis kelamin.';
    }
    if (!str_len_ok($d['birth_place'], 100)) {
        $err['birth_place'] = 'Maksimal 100 karakter.';
    }
    if (!valid_date($d['birth_date'])) {
        $err['birth_date'] = 'Tanggal lahir tidak valid.';
    } elseif ($d['birth_date'] > today()) {
        $err['birth_date'] = 'Tanggal lahir tidak boleh di masa depan.';
    } elseif ($d['birth_date'] < date('Y-m-d', strtotime('-7 years'))) {
        $err['birth_date'] = 'Aplikasi ini ditujukan untuk anak usia 0-5 tahun.';
    }
    $bw = parse_decimal($d['birth_weight'], 0.3, 7);
    if ($bw === false) {
        $err['birth_weight'] = 'Berat lahir 0,3 - 7 kg (gunakan titik atau koma).';
    }
    $bl = parse_decimal($d['birth_length'], 20, 65);
    if ($bl === false) {
        $err['birth_length'] = 'Panjang lahir 20 - 65 cm.';
    }
    if (!str_len_ok($d['birth_history'], 5000) || !str_len_ok($d['notes'], 5000)) {
        $err['notes'] = 'Catatan terlalu panjang (maks. 5000 karakter).';
    }
    $clean = [
        'full_name'     => $d['full_name'],
        'gender'        => $d['gender'],
        'birth_place'   => null_if_empty($d['birth_place']),
        'birth_date'    => $d['birth_date'],
        'birth_weight'  => $bw ?: null,
        'birth_length'  => $bl ?: null,
        'birth_history' => null_if_empty($d['birth_history']),
        'notes'         => null_if_empty($d['notes']),
    ];
    return [$d, $err, $clean];
}

function child_form(array $d, array $err, ?array $child = null): void
{
    ?>
    <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <?php if ($child): ?><input type="hidden" name="id" value="<?= (int) $child['id'] ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-12 d-flex align-items-center gap-3">
                <?php if ($child && $child['photo']): ?>
                    <img id="photoPreview" src="<?= e(url('children/photo.php?id=' . (int) $child['id'])) ?>" class="avatar avatar-lg" alt="Foto anak">
                <?php else: ?>
                    <img id="photoPreview" src="" class="avatar avatar-lg d-none" alt="Pratinjau foto">
                <?php endif; ?>
                <div class="flex-fill">
                    <label for="photo" class="form-label">Foto anak <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="file" class="form-control<?= invalid($err, 'photo') ?>" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="#photoPreview">
                    <div class="form-text">JPG, PNG, atau WEBP, maksimal 2 MB.</div>
                    <?= field_error($err, 'photo') ?>
                    <?php if ($child && $child['photo']): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo">
                            <label class="form-check-label small" for="remove_photo">Hapus foto saat ini</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-8">
                <label for="full_name" class="form-label required">Nama lengkap</label>
                <input class="form-control<?= invalid($err, 'full_name') ?>" id="full_name" name="full_name" value="<?= e($d['full_name']) ?>" maxlength="100" required>
                <?= field_error($err, 'full_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label required d-block">Jenis kelamin</label>
                <div class="btn-group w-100" role="group" aria-label="Jenis kelamin">
                    <input type="radio" class="btn-check" name="gender" id="g_l" value="L" <?= $d['gender'] === 'L' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="g_l"><i class="fa-solid fa-mars me-1"></i>Laki-laki</label>
                    <input type="radio" class="btn-check" name="gender" id="g_p" value="P" <?= $d['gender'] === 'P' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="g_p"><i class="fa-solid fa-venus me-1"></i>Perempuan</label>
                </div>
                <?= field_error($err, 'gender') ?>
            </div>
            <div class="col-md-6">
                <label for="birth_place" class="form-label">Tempat lahir</label>
                <input class="form-control<?= invalid($err, 'birth_place') ?>" id="birth_place" name="birth_place" value="<?= e($d['birth_place']) ?>" maxlength="100">
                <?= field_error($err, 'birth_place') ?>
            </div>
            <div class="col-md-6">
                <label for="birth_date" class="form-label required">Tanggal lahir</label>
                <input type="date" class="form-control<?= invalid($err, 'birth_date') ?>" id="birth_date" name="birth_date" value="<?= e($d['birth_date']) ?>" max="<?= e(today()) ?>" required>
                <div class="form-text">Usia dihitung otomatis dari tanggal lahir.</div>
                <?= field_error($err, 'birth_date') ?>
            </div>
            <div class="col-6">
                <label for="birth_weight" class="form-label">Berat lahir (kg)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'birth_weight') ?>" id="birth_weight" name="birth_weight" value="<?= e($d['birth_weight']) ?>" placeholder="3,2">
                <?= field_error($err, 'birth_weight') ?>
            </div>
            <div class="col-6">
                <label for="birth_length" class="form-label">Panjang lahir (cm)</label>
                <input type="text" inputmode="decimal" class="form-control<?= invalid($err, 'birth_length') ?>" id="birth_length" name="birth_length" value="<?= e($d['birth_length']) ?>" placeholder="49">
                <?= field_error($err, 'birth_length') ?>
            </div>
            <div class="col-md-6">
                <label for="birth_history" class="form-label">Riwayat kelahiran / catatan awal</label>
                <textarea class="form-control" id="birth_history" name="birth_history" rows="3" maxlength="5000" placeholder="Contoh: lahir normal di puskesmas, usia kehamilan 39 minggu"><?= e($d['birth_history']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label for="notes" class="form-label">Catatan tambahan</label>
                <textarea class="form-control<?= invalid($err, 'notes') ?>" id="notes" name="notes" rows="3" maxlength="5000"><?= e($d['notes']) ?></textarea>
                <?= field_error($err, 'notes') ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
            <a href="<?= e(url($child ? 'children/detail.php?id=' . (int) $child['id'] : 'children/index.php')) ?>" class="btn btn-light border">Batal</a>
        </div>
    </form>
    <?php
}
