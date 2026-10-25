<?php
/**
 * Ekspor laporan ke CSV (pemisah titik koma, UTF-8 BOM agar terbaca baik di Excel Indonesia).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/growth_standards.php';
require_login();
require __DIR__ . '/_data.php';

[$children, $child, $from, $to] = report_params();
$data = report_data($child, $from, $to);

/** Cegah CSV/formula injection di Excel */
function csv_cell($v): string
{
    $v = (string) ($v ?? '');
    if ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($v)) {
        $v = "'" . $v;
    }
    return $v;
}

function csv_row($fh, array $row): void
{
    fputcsv($fh, array_map('csv_cell', $row), ';', '"', '');
}

function dec($v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    $v = (string) $v;
    if (strpos($v, '.') !== false) {
        $v = rtrim(rtrim($v, '0'), '.');
    }
    return str_replace('.', ',', $v);
}

$slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($child['full_name']));
$filename = 'laporan-' . trim($slug, '-') . '-' . $to . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

csv_row($out, ['LAPORAN TUMBUH KEMBANG ANAK - ' . APP_NAME]);
csv_row($out, ['Nama anak', $child['full_name']]);
csv_row($out, ['Jenis kelamin', gender_label($child['gender'])]);
csv_row($out, ['Tanggal lahir', tgl_indo($child['birth_date'])]);
csv_row($out, ['Periode', tgl_indo($from) . ' s.d. ' . tgl_indo($to)]);
csv_row($out, ['Usia pada tanggal laporan', age_label($child['birth_date'], $to)]);
csv_row($out, ['Alergi yang dicatat', implode('; ', $data['allergies'])]);
csv_row($out, []);

csv_row($out, ['PENGUKURAN PERTUMBUHAN']);
csv_row($out, ['Tanggal', 'Usia', 'Berat (kg)', 'Panjang/Tinggi (cm)', 'Cara ukur', 'Lingkar kepala (cm)', 'LiLA (cm)', 'Z BB/U', 'Kategori BB/U', 'Z PB/U-TB/U', 'Kategori PB/U-TB/U', 'Z BB/PB-BB/TB', 'Kategori BB/PB-BB/TB', 'Z IMT/U', 'Kategori IMT/U', 'Catatan']);
foreach ($data['growth'] as $r) {
    $a = assess_growth($child, $r);
    $m = [];
    foreach ($a['items'] as $it) $m[$it['code']] = $it;
    $z = fn($c) => isset($m[$c]) && $m[$c]['z'] !== null ? number_format($m[$c]['z'], 2, ',', '') : '';
    $k = fn($c) => $m[$c]['category'] ?? '';
    csv_row($out, [$r['measured_at'], age_label($child['birth_date'], $r['measured_at']), dec($r['weight']), dec($r['height']),
        $r['measure_method'] === 'height' ? 'Berdiri' : 'Berbaring', dec($r['head_circ']), dec($r['muac']),
        $z('bbu'), $k('bbu'), $z('pbu'), $k('pbu'), $z('bbpb'), $k('bbpb'), $z('imtu'), $k('imtu'), $r['notes']]);
}
csv_row($out, []);

csv_row($out, ['RINGKASAN PERKEMBANGAN (observasi orangtua, bukan skrining klinis)']);
csv_row($out, ['Kelompok usia', 'Bidang', 'Sudah dapat', 'Belum dapat', 'Belum diamati', 'Total', 'Observasi terakhir']);
foreach ($data['dev'] as $d) {
    csv_row($out, [$d['age_group_label'], DOMAIN_LABELS[$d['domain']], $d['sudah'], $d['belum'], $d['belum_diamati'], $d['total'], $d['last_date']]);
}
csv_row($out, []);

csv_row($out, ['IMUNISASI (catatan orangtua)']);
csv_row($out, ['Tanggal pemberian', 'Vaksin', 'Dosis', 'Status', 'Tempat', 'Nomor batch', 'Reaksi/catatan', 'Tanggal berikutnya', 'Sumber data']);
foreach ($data['imm'] as $i) {
    csv_row($out, [$i['given_at'], $i['vaccine_name'], $i['dose'], IMM_STATUS[$i['status']], $i['location'], $i['batch_number'], $i['reaction_notes'], $i['next_date'], IMM_SOURCE[$i['record_source']]]);
}
csv_row($out, []);

csv_row($out, ['CATATAN KESEHATAN']);
csv_row($out, ['Tanggal', 'Jenis', 'Keluhan', 'Suhu (C)', 'Obat', 'Dosis (sesuai resep)', 'Alergi', 'Kunjungan faskes', 'Catatan']);
foreach ($data['health'] as $h) {
    csv_row($out, [$h['event_date'], HEALTH_TYPES[$h['record_type']], $h['symptoms'], dec($h['temperature']), $h['medicine_name'], $h['medicine_dose'], $h['allergies'], $h['facility_visit'], $h['notes']]);
}
csv_row($out, []);

csv_row($out, ['PENGINGAT']);
csv_row($out, ['Tanggal', 'Jam', 'Judul', 'Kategori', 'Status', 'Catatan']);
foreach ($data['reminders'] as $r) {
    csv_row($out, [$r['due_date'], $r['due_time'] ? substr($r['due_time'], 0, 5) : '', $r['title'], REMINDER_CATEGORIES[$r['category']], $r['is_done'] ? 'Selesai' : 'Belum selesai', $r['notes']]);
}
csv_row($out, []);
csv_row($out, ['Hasil aplikasi merupakan alat bantu pemantauan, bukan diagnosis medis.']);
fclose($out);
