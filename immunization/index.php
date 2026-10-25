<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$uid = current_user_id();
$children = user_children();
$childId = query_int('child_id');
$child = $childId ? require_child($childId) : null;

$cond = 'c.user_id = ?' . ($childId ? ' AND i.child_id = ?' : '');
$params = $childId ? [$uid, $childId] : [$uid];

// Lewat jadwal
$st = db()->prepare(
    "SELECT i.*, c.full_name FROM immunization_records i JOIN children c ON c.id = i.child_id
     WHERE $cond AND i.next_date IS NOT NULL AND i.next_date < CURDATE()
       AND NOT EXISTS (SELECT 1 FROM immunization_records j WHERE j.child_id = i.child_id AND j.vaccine_name = i.vaccine_name
                       AND j.status = 'diberikan' AND j.given_at >= i.next_date)
     ORDER BY i.next_date"
);
$st->execute($params);
$overdue = $st->fetchAll();

// Mendatang
$st = db()->prepare("SELECT i.*, c.full_name FROM immunization_records i JOIN children c ON c.id = i.child_id
                     WHERE $cond AND i.next_date >= CURDATE() ORDER BY i.next_date LIMIT 10");
$st->execute($params);
$upcoming = $st->fetchAll();

// Riwayat
$st = db()->prepare("SELECT COUNT(*) FROM immunization_records i JOIN children c ON c.id = i.child_id WHERE $cond");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), 15);
$st = db()->prepare("SELECT i.*, c.full_name FROM immunization_records i JOIN children c ON c.id = i.child_id WHERE $cond
                     ORDER BY COALESCE(i.given_at, i.next_date) DESC, i.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($params);
$records = $st->fetchAll();

// Jadwal referensi (hanya bila satu anak dipilih)
$schedule = [];
$given = [];
if ($child) {
    $schedule = db()->query('SELECT * FROM immunization_schedule ORDER BY sort_order')->fetchAll();
    $st = db()->prepare("SELECT vaccine_name, dose, given_at FROM immunization_records WHERE child_id = ? AND status = 'diberikan'");
    $st->execute([$childId]);
    foreach ($st->fetchAll() as $g) {
        $given[mb_strtolower($g['vaccine_name'] . '|' . $g['dose'])] = $g['given_at'];
    }
    $ageMonths = age_parts($child['birth_date'])['total_months'];
}

$pageTitle = 'Imunisasi';
$activeMenu = 'immunization';
require APP_ROOT . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form method="get" class="d-flex gap-2 align-items-center">
        <label for="child_id" class="fw-bold">Anak:</label>
        <select id="child_id" name="child_id" class="form-select w-auto js-auto-submit"><?= child_options($children, $childId, true) ?></select>
    </form>
    <a href="<?= e(url('immunization/create.php' . ($childId ? '?child_id=' . $childId : ''))) ?>" class="btn btn-primary ms-auto"><i class="fa-solid fa-plus me-1"></i> Catat Imunisasi</a>
</div>

<?php if (!$children): ?>
    <div class="card"><div class="card-body"><?= empty_state('fa-baby', 'Belum ada data anak', 'Tambahkan anak terlebih dahulu.', 'children/create.php', 'Tambah Anak') ?></div></div>
<?php else: ?>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-danger"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lewat dari Jadwal yang Dicatat</div>
            <div class="card-body py-2">
                <?php if (!$overdue): ?><p class="text-muted small my-2">Tidak ada jadwal tercatat yang terlewat.</p><?php endif; ?>
                <?php foreach ($overdue as $o): ?>
                    <div class="list-item">
                        <span class="list-icon bg-soft-rose"><i class="fa-solid fa-syringe"></i></span>
                        <div class="flex-fill">
                            <div class="fw-bold"><?= e($o['vaccine_name']) ?> <span class="fw-normal text-muted small"><?= e($o['dose']) ?></span></div>
                            <div class="small text-muted"><?= e($o['full_name']) ?> · jadwal <?= e(tgl_indo($o['next_date'])) ?> (<?= e(relative_day_label($o['next_date'])) ?>)</div>
                        </div>
                        <a href="<?= e(url('immunization/create.php?child_id=' . $o['child_id'] . '&vaccine=' . urlencode($o['vaccine_name']))) ?>" class="btn btn-sm btn-outline-primary">Catat</a>
                    </div>
                <?php endforeach; ?>
                <?php if ($overdue): ?><p class="small text-muted mt-2 mb-1">Segera konsultasikan dengan petugas kesehatan mengenai imunisasi kejar.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Jadwal Mendatang</div>
            <div class="card-body py-2">
                <?php if (!$upcoming): ?><p class="text-muted small my-2">Belum ada tanggal imunisasi berikutnya yang dicatat.</p><?php endif; ?>
                <?php foreach ($upcoming as $u): ?>
                    <div class="list-item">
                        <span class="list-icon bg-soft-peach"><i class="fa-solid fa-calendar-day"></i></span>
                        <div class="flex-fill">
                            <div class="fw-bold"><?= e($u['vaccine_name']) ?> <span class="fw-normal text-muted small"><?= $u['status'] === 'dijadwalkan' ? e($u['dose']) : 'dosis berikutnya' ?></span></div>
                            <div class="small text-muted"><?= e($u['full_name']) ?> · <?= e(tgl_indo($u['next_date'])) ?> (<?= e(relative_day_label($u['next_date'])) ?>)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-list me-2 text-primary"></i>Riwayat Imunisasi yang Dicatat</div>
    <?php if (!$records): ?>
        <div class="card-body"><?= empty_state('fa-syringe', 'Belum ada catatan imunisasi', 'Salin catatan dari Buku KIA agar riwayat imunisasi tersimpan rapi.', 'immunization/create.php' . ($childId ? '?child_id=' . $childId : ''), 'Catat Imunisasi') ?></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Tanggal</th><?= $childId ? '' : '<th>Anak</th>' ?><th>Vaksin</th><th>Dosis</th><th>Status</th><th>Tempat</th><th>Berikutnya</th><th>Sumber</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td class="text-nowrap"><?= e(tgl_indo($r['given_at'], false, true)) ?></td>
                    <?= $childId ? '' : '<td class="text-nowrap">' . e($r['full_name']) . '</td>' ?>
                    <td class="fw-bold"><?= e($r['vaccine_name']) ?><?php if ($r['reaction_notes']): ?> <i class="fa-solid fa-comment-medical text-muted" title="<?= e($r['reaction_notes']) ?>"></i><?php endif; ?></td>
                    <td class="small"><?= e($r['dose'] ?: '-') ?></td>
                    <td><span class="badge badge-soft-<?= ['diberikan' => 'success', 'dijadwalkan' => 'info', 'ditunda' => 'warning'][$r['status']] ?>"><?= e(IMM_STATUS[$r['status']]) ?></span></td>
                    <td class="small"><?= e($r['location'] ?: '-') ?></td>
                    <td class="small text-nowrap"><?= e(tgl_indo($r['next_date'], false, true)) ?></td>
                    <td class="small"><?php if ($r['record_source'] === 'ingatan_orangtua'): ?><span class="badge badge-soft-warning">Belum diverifikasi</span><?php else: ?><span class="text-muted"><?= e(IMM_SOURCE[$r['record_source']]) ?></span><?php endif; ?></td>
                    <td class="text-end table-actions">
                        <a href="<?= e(url('immunization/edit.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?= delete_button('immunization/delete.php', (int) $r['id'], 'Catatan imunisasi ' . $r['vaccine_name'] . ' milik ' . $r['full_name'] . ' akan dihapus.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top"><?= pagination_links($pg) ?></div>
    <?php endif; ?>
</div>

<?php if ($child): ?>
<div class="card">
    <div class="card-header"><i class="fa-solid fa-book-medical me-2 text-primary"></i>Panduan Jadwal Imunisasi Rutin (Program Pemerintah)</div>
    <div class="card-body pb-0">
        <div class="note-box mb-3">
            <i class="fa-solid fa-circle-info me-1"></i>Tabel ini hanya panduan untuk membantu mencatat. Status "Tercatat" berarti orangtua sudah memasukkan catatan dengan nama vaksin dan dosis yang sama,
            <strong>bukan</strong> pernyataan bahwa imunisasi anak lengkap. Jadwal dapat berubah sesuai kebijakan Kementerian Kesehatan;
            selalu cocokkan dengan Buku KIA dan petugas kesehatan. Sumber: <?= e($schedule[0]['source'] ?? '-') ?>.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Usia anjuran</th><th>Vaksin</th><th>Dosis</th><th>Status di aplikasi</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($schedule as $s):
                $key = mb_strtolower($s['vaccine_name'] . '|' . $s['dose_label']);
                $isGiven = isset($given[$key]);
                $due = $ageMonths >= $s['recommended_age_months']; ?>
                <tr>
                    <td class="text-nowrap small"><?= e($s['age_label']) ?></td>
                    <td><?= e($s['vaccine_name']) ?><?php if ($s['notes']): ?><div class="small text-muted"><?= e($s['notes']) ?></div><?php endif; ?></td>
                    <td class="small"><?= e($s['dose_label']) ?></td>
                    <td>
                        <?php if ($isGiven): ?><span class="badge badge-soft-success">Tercatat · <?= e(tgl_indo($given[$key], false, true)) ?></span>
                        <?php elseif ($due): ?><span class="badge badge-soft-warning">Belum tercatat</span>
                        <?php else: ?><span class="badge badge-soft-secondary">Belum waktunya</span><?php endif; ?>
                    </td>
                    <td class="text-end"><?php if (!$isGiven): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('immunization/create.php?child_id=' . $childId . '&vaccine=' . urlencode($s['vaccine_name']) . '&dose=' . urlencode($s['dose_label']))) ?>">Catat</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <p class="small text-muted"><i class="fa-solid fa-lightbulb me-1"></i>Pilih satu anak untuk melihat panduan jadwal imunisasi rutin.</p>
<?php endif; ?>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
