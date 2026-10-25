<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$preselect = query_int('child_id');
if ($preselect && !find_child($preselect)) {
    $preselect = 0;
}
$data = growth_defaults($preselect ?: (count($children) === 1 ? (int) $children[0]['id'] : 0));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean, $child] = growth_validate();
    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO growth_records (child_id, measured_at, weight, height, measure_method, head_circ, muac, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$clean['child_id'], $clean['measured_at'], $clean['weight'], $clean['height'], $clean['measure_method'], $clean['head_circ'], $clean['muac'], $clean['notes']]);
        set_flash('success', 'Pengukuran ' . $child['full_name'] . ' berhasil disimpan.');
        redirect('growth/chart.php?child_id=' . $clean['child_id']);
    }
}

$pageTitle = 'Catat Pertumbuhan';
$activeMenu = 'growth';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php growth_form($data, $errors, $children); ?></div></div>
    <p class="small text-muted mt-3"><i class="fa-solid fa-lightbulb me-1"></i>Tips: timbang dengan pakaian seminimal mungkin dan catat hasil dari posyandu/puskesmas agar data konsisten.</p>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
