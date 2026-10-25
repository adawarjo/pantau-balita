<?php
/**
 * Partial: tampilan hasil perbandingan dengan standar WHO.
 * Membutuhkan variabel $assessment (hasil assess_growth()).
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
?>
<?php if (empty($assessment['available'])): ?>
    <div class="info-box"><i class="fa-solid fa-circle-info me-1"></i><?= e($assessment['note'] ?? 'Klasifikasi tidak tersedia.') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Indikator</th><th class="text-end">Nilai</th><th class="text-end">Z-score</th><th>Kategori</th></tr></thead>
            <tbody>
            <?php foreach ($assessment['items'] as $it): ?>
                <tr>
                    <td><?= e($it['label']) ?></td>
                    <td class="text-end text-nowrap"><?= e(num($it['value'], 2)) ?> <?= e($it['unit']) ?></td>
                    <td class="text-end"><?= e(z_format($it['z'])) ?></td>
                    <td><span class="badge badge-soft-<?= e($it['color']) ?>"><?= e($it['category']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($assessment['adjusted_height'] !== null && isset($last) && abs($assessment['adjusted_height'] - (float) ($last['height'] ?? 0)) > 0.01): ?>
        <p class="small text-muted mb-2"><i class="fa-solid fa-ruler me-1"></i>Panjang/tinggi disesuaikan menjadi <?= e(num($assessment['adjusted_height'])) ?> cm karena cara ukur (berbaring/berdiri) berbeda dari ketentuan usia WHO.</p>
    <?php endif; ?>
    <div class="note-box mt-2">
        <i class="fa-solid fa-stethoscope me-1"></i><?= e(MEDICAL_DISCLAIMER) ?>
        Bila ada indikator di luar kategori normal, diskusikan dengan dokter, bidan, atau petugas posyandu/puskesmas.
        <div class="small mt-1 opacity-75">Referensi: <?= e(WHO_SOURCE_NOTE) ?></div>
    </div>
<?php endif; ?>
