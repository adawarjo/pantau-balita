<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$pre = query_int('child_id');
$data = imm_defaults(($pre && find_child($pre)) ? $pre : (count($children) === 1 ? (int) $children[0]['id'] : 0));
// Isi cepat dari jadwal referensi
if (query('vaccine') !== '') {
    $data['vaccine_name'] = mb_substr(query('vaccine'), 0, 60);
    $data['dose'] = mb_substr(query('dose'), 0, 30);
}
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean, $child] = imm_validate();
    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO immunization_records (child_id, vaccine_name, dose, status, given_at, location, batch_number, reaction_notes, next_date, record_source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$clean['child_id'], $clean['vaccine_name'], $clean['dose'], $clean['status'], $clean['given_at'], $clean['location'],
            $clean['batch_number'], $clean['reaction_notes'], $clean['next_date'], $clean['record_source']]);
        if ($data['make_reminder']) {
            imm_create_reminder($clean, $child);
        }
        set_flash('success', 'Catatan imunisasi ' . $clean['vaccine_name'] . ' tersimpan.');
        redirect('immunization/index.php?child_id=' . $clean['child_id']);
    }
}

$pageTitle = 'Catat Imunisasi';
$activeMenu = 'immunization';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php imm_form($data, $errors, $children); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
