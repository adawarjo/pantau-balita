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
$data = health_defaults(($pre && find_child($pre)) ? $pre : (count($children) === 1 ? (int) $children[0]['id'] : 0));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $c] = health_validate();
    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO health_records (child_id, event_date, record_type, symptoms, temperature, medicine_name, medicine_dose, allergies, facility_visit, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$c['child_id'], $c['event_date'], $c['record_type'], $c['symptoms'], $c['temperature'], $c['medicine_name'],
            $c['medicine_dose'], $c['allergies'], $c['facility_visit'], $c['notes']]);
        set_flash('success', 'Catatan kesehatan tersimpan.');
        redirect('health/index.php?child_id=' . $c['child_id']);
    }
}

$pageTitle = 'Catat Kesehatan';
$activeMenu = 'health';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php health_form($data, $errors, $children); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
