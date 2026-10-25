<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_common.php';

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$src = is_post() ? $_POST : $_GET;
$childId = (isset($src['child_id']) && ctype_digit((string) $src['child_id'])) ? (int) $src['child_id'] : (int) $children[0]['id'];
$child = require_child($childId);

$groups = dev_age_groups();
if (!$groups) {
    set_flash('error', 'Daftar indikator perkembangan belum tersedia.');
    redirect('development/index.php');
}
$age = age_parts($child['birth_date']);
$groupMin = (isset($src['group']) && ctype_digit((string) $src['group'])) ? (int) $src['group'] : -1;
$group = dev_find_group($groups, $groupMin) ?: dev_group_for_age($groups, $age['total_months']);
$indicators = dev_indicators((int) $group['age_min_months'], (int) $group['age_max_months']);
$indicatorIds = array_map(fn($i) => (int) $i['id'], $indicators);

$observedAt = is_string($src['date'] ?? null) && valid_date($src['date']) ? $src['date'] : today();
$errors = [];

if (is_post()) {
    csrf_verify();
    $observedAt = input('observed_at');
    if (!valid_date($observedAt)) {
        $errors['observed_at'] = 'Tanggal observasi tidak valid.';
    } elseif ($observedAt > today()) {
        $errors['observed_at'] = 'Tanggal observasi tidak boleh di masa depan.';
    } elseif ($observedAt < $child['birth_date']) {
        $errors['observed_at'] = 'Tanggal observasi tidak boleh sebelum tanggal lahir.';
    }
    $statuses = $_POST['status'] ?? [];
    $notes = $_POST['note'] ?? [];
    if (!is_array($statuses) || !is_array($notes)) {
        $errors['status'] = 'Data observasi tidak valid.';
        $statuses = [];
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO developmental_observations (child_id, indicator_id, observed_at, status, notes)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)'
            );
            $saved = 0;
            foreach ($indicatorIds as $iid) {
                $status = $statuses[$iid] ?? 'belum_diamati';
                if (!is_string($status) || !isset(OBS_STATUS[$status])) {
                    $status = 'belum_diamati';
                }
                $note = is_string($notes[$iid] ?? null) ? mb_substr(trim($notes[$iid]), 0, 500) : '';
                $ins->execute([$childId, $iid, $observedAt, $status, null_if_empty($note)]);
                $saved++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        set_flash('success', "Observasi {$saved} indikator untuk {$child['full_name']} tersimpan.");
        redirect('development/index.php?child_id=' . $childId . '&group=' . $group['age_min_months']);
    }
}

// Muat nilai yang sudah ada pada tanggal ini (mode edit)
$existing = [];
if ($indicatorIds) {
    $in = implode(',', array_fill(0, count($indicatorIds), '?'));
    $st = db()->prepare("SELECT indicator_id, status, notes FROM developmental_observations WHERE child_id = ? AND observed_at = ? AND indicator_id IN ($in)");
    $st->execute(array_merge([$childId, $observedAt], $indicatorIds));
    foreach ($st->fetchAll() as $r) {
        $existing[(int) $r['indicator_id']] = $r;
    }
}
if (is_post()) {
    foreach ($indicatorIds as $iid) {
        $existing[$iid] = ['status' => $_POST['status'][$iid] ?? 'belum_diamati', 'notes' => $_POST['note'][$iid] ?? ''];
    }
}

$pageTitle = 'Observasi Perkembangan';
$activeMenu = 'development';
require APP_ROOT . '/includes/header.php';
?>
<form method="get" class="card card-body mb-3 filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small" for="child_id">Anak</label><select class="form-select js-auto-submit" id="child_id" name="child_id"><?= child_options($children, $childId) ?></select></div>
        <div class="col-md-4"><label class="form-label small" for="group">Kelompok usia</label>
            <select class="form-select js-auto-submit" id="group" name="group">
                <?php foreach ($groups as $g): ?><option value="<?= (int) $g['age_min_months'] ?>" <?= $g['age_min_months'] === $group['age_min_months'] ? 'selected' : '' ?>><?= e($g['age_group_label']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label small" for="date">Tanggal (untuk mengubah observasi lama)</label><input type="date" class="form-control js-auto-submit" id="date" name="date" value="<?= e($observedAt) ?>" max="<?= e(today()) ?>"></div>
    </div>
</form>

<form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="child_id" value="<?= $childId ?>">
    <input type="hidden" name="group" value="<?= (int) $group['age_min_months'] ?>">
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span><?= e($child['full_name']) ?> · usia <?= e(age_label($child['birth_date'], $observedAt)) ?> · indikator <?= e($group['age_group_label']) ?></span>
            <div class="d-flex align-items-center gap-2">
                <label for="observed_at" class="small fw-bold mb-0">Tanggal observasi</label>
                <input type="date" class="form-control form-control-sm w-auto<?= invalid($errors, 'observed_at') ?>" id="observed_at" name="observed_at" value="<?= e($observedAt) ?>" max="<?= e(today()) ?>" required>
            </div>
        </div>
        <div class="card-body">
            <?= field_error($errors, 'observed_at') ?>
            <?php if ($existing && !is_post()): ?>
                <div class="info-box mb-3"><i class="fa-solid fa-pen me-1"></i>Sudah ada observasi pada tanggal ini. Menyimpan akan memperbarui data tersebut.</div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2 mb-3 small">
                <span class="text-muted">Tandai semua:</span>
                <?php foreach (OBS_STATUS as $k => $l): ?><button type="button" class="btn btn-sm btn-light border" data-set-all="<?= e($k) ?>"><?= e($l) ?></button><?php endforeach; ?>
            </div>
            <?php foreach (DOMAIN_LABELS as $dk => $dl):
                $items = array_filter($indicators, fn($i) => $i['domain'] === $dk);
                if (!$items) continue; ?>
                <h3 class="h6 mt-3 mb-2"><i class="fa-solid <?= e(DOMAIN_ICONS[$dk]) ?> me-2 text-primary"></i><?= e($dl) ?></h3>
                <?php foreach ($items as $i): $iid = (int) $i['id']; $cur = $existing[$iid]['status'] ?? 'belum_diamati'; ?>
                <div class="obs-item">
                    <div class="mb-2"><?= e($i['description']) ?></div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="d-flex obs-options flex-wrap gap-1" role="group" aria-label="Status">
                            <?php foreach (OBS_STATUS as $k => $l): ?>
                                <input type="radio" class="btn-check obs-radio" name="status[<?= $iid ?>]" id="s<?= $iid . $k ?>" value="<?= e($k) ?>" <?= $cur === $k ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-obs-<?= e($k) ?>" for="s<?= $iid . $k ?>"><?= e($l) ?></label>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" class="form-control form-control-sm flex-fill" style="min-width:180px" name="note[<?= $iid ?>]" maxlength="500" placeholder="Catatan (opsional)" value="<?= e($existing[$iid]['notes'] ?? '') ?>" aria-label="Catatan">
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <div class="card-footer bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span class="small text-muted"><?= e(DEV_DISCLAIMER) ?></span>
            <div class="d-flex gap-2">
                <a href="<?= e(url('development/index.php?child_id=' . $childId)) ?>" class="btn btn-light border">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Observasi</button>
            </div>
        </div>
    </div>
</form>
<?php require APP_ROOT . '/includes/footer.php'; ?>
