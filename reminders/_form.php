<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function reminder_defaults(int $childId = 0): array
{
    return ['child_id' => $childId, 'title' => '', 'category' => 'pertumbuhan', 'due_date' => date('Y-m-d', strtotime('+30 days')), 'due_time' => '', 'notes' => ''];
}

function reminder_validate(): array
{
    $d = [
        'child_id' => post_int('child_id'),
        'title'    => input('title'),
        'category' => input('category'),
        'due_date' => input('due_date'),
        'due_time' => input('due_time'),
        'notes'    => input('notes'),
    ];
    $err = [];
    $child = find_child($d['child_id']);
    if (!$child) $err['child_id'] = 'Pilih anak.';
    if (mb_strlen($d['title']) < 3 || !str_len_ok($d['title'], 150)) $err['title'] = 'Judul 3-150 karakter.';
    if (!isset(REMINDER_CATEGORIES[$d['category']])) $err['category'] = 'Pilih kategori.';
    if (!valid_date($d['due_date'])) $err['due_date'] = 'Tanggal tidak valid.';
    if ($d['due_time'] !== '' && !valid_time($d['due_time'])) $err['due_time'] = 'Format jam tidak valid.';
    if (!str_len_ok($d['notes'], 2000)) $err['notes'] = 'Maksimal 2000 karakter.';
    $clean = $d;
    $clean['due_time'] = null_if_empty($d['due_time']);
    $clean['notes'] = null_if_empty($d['notes']);
    return [$d, $err, $clean, $child];
}

function reminder_form(array $d, array $err, array $children, ?int $id = null): void
{
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
                <label class="form-label required" for="category">Kategori</label>
                <select class="form-select<?= invalid($err, 'category') ?>" id="category" name="category">
                    <?php foreach (REMINDER_CATEGORIES as $k => $l): ?><option value="<?= e($k) ?>" <?= $d['category'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select><?= field_error($err, 'category') ?>
            </div>
            <div class="col-12">
                <label class="form-label required" for="title">Judul</label>
                <input class="form-control<?= invalid($err, 'title') ?>" id="title" name="title" value="<?= e($d['title']) ?>" maxlength="150" required placeholder="mis. Timbang di posyandu">
                <?= field_error($err, 'title') ?>
            </div>
            <div class="col-6">
                <label class="form-label required" for="due_date">Tanggal</label>
                <input type="date" class="form-control<?= invalid($err, 'due_date') ?>" id="due_date" name="due_date" value="<?= e($d['due_date']) ?>" required>
                <?= field_error($err, 'due_date') ?>
            </div>
            <div class="col-6">
                <label class="form-label" for="due_time">Jam <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="time" class="form-control<?= invalid($err, 'due_time') ?>" id="due_time" name="due_time" value="<?= e($d['due_time'] ? substr($d['due_time'], 0, 5) : '') ?>">
                <?= field_error($err, 'due_time') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Catatan</label>
                <textarea class="form-control<?= invalid($err, 'notes') ?>" id="notes" name="notes" rows="2" maxlength="2000"><?= e($d['notes']) ?></textarea>
                <?= field_error($err, 'notes') ?>
            </div>
        </div>
        <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Pengingat ditampilkan di dashboard setelah Anda login (14 hari sebelum tanggal). Aplikasi belum mengirim notifikasi WhatsApp, email, atau push.</p>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
            <a href="<?= e(url('reminders/index.php')) ?>" class="btn btn-light border">Batal</a>
        </div>
    </form>
    <?php
}
