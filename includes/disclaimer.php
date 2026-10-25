<?php
/**
 * Isi Disclaimer (Penafian) aplikasi.
 *
 * Bila isi diubah secara berarti, naikkan DISCLAIMER_VERSION dan
 * DISCLAIMER_DATE: semua pengguna akan diminta menyetujui ulang
 * saat membuka aplikasi berikutnya.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

const DISCLAIMER_VERSION = '1.0';
const DISCLAIMER_DATE = '2026-10-25';

/** Poin ringkas untuk jendela persetujuan & formulir pendaftaran */
function disclaimer_summary(): array
{
    return [
        ['fa-stethoscope', 'Alat bantu pemantauan, bukan diagnosis medis dan bukan pengganti dokter, bidan, atau tenaga kesehatan.'],
        ['fa-truck-medical', 'Dalam keadaan darurat, segera ke IGD terdekat atau hubungi 119.'],
        ['fa-pills', 'Aplikasi tidak memberikan resep atau menghitung dosis obat.'],
        ['fa-pen-to-square', 'Hasil bergantung pada ketepatan data yang Anda masukkan.'],
        ['fa-user-shield', 'Anda bertanggung jawab menjaga kerahasiaan akun dan perangkat yang dipakai.'],
    ];
}

/**
 * Bagian lengkap disclaimer: [judul, ikon, [paragraf/butir...]]
 * Butir yang diawali '- ' ditampilkan sebagai daftar.
 */
function disclaimer_sections(): array
{
    return [
        ['Tujuan aplikasi', 'fa-circle-info', [
            APP_NAME . ' adalah aplikasi pencatatan untuk membantu orangtua memantau pertumbuhan, perkembangan, imunisasi, dan riwayat kesehatan anak usia 0-5 tahun.',
            'Seluruh informasi, grafik, z-score, kategori, dan saran di dalam aplikasi bersifat informatif dan edukatif sebagai alat bantu pemantauan.',
        ]],
        ['Bukan diagnosis atau nasihat medis', 'fa-stethoscope', [
            'Aplikasi ini tidak memberikan diagnosis, tidak menetapkan status gizi, dan tidak menggantikan pemeriksaan, nasihat, atau perawatan oleh dokter, bidan, perawat, ahli gizi, maupun petugas posyandu dan puskesmas.',
            'Status gizi dan pertumbuhan anak hanya dapat ditetapkan oleh tenaga kesehatan berdasarkan beberapa indikator dan pemeriksaan langsung.',
            'Jangan menunda mencari pertolongan medis atau mengabaikan nasihat tenaga kesehatan karena informasi yang Anda lihat di aplikasi ini.',
        ]],
        ['Keadaan darurat', 'fa-truck-medical', [
            'Aplikasi ini tidak dirancang untuk keadaan darurat. Segera bawa anak ke IGD atau fasilitas kesehatan terdekat, atau hubungi layanan gawat darurat 119, bila anak mengalami antara lain:',
            '- kejang, tidak sadar, atau sangat sulit dibangunkan',
            '- sesak napas, napas cepat, atau tarikan dinding dada',
            '- demam (suhu 38°C atau lebih) pada bayi di bawah 3 bulan, atau demam disertai lemas berat pada usia berapa pun',
            '- tidak mau menyusu atau minum, muntah terus-menerus, atau tanda dehidrasi',
            '- kulit atau bibir tampak kebiruan, atau perdarahan yang tidak berhenti',
        ]],
        ['Standar pertumbuhan', 'fa-chart-line', [
            'Perhitungan z-score memakai WHO Child Growth Standards (2006) dengan metode LMS, dan kategorinya mengikuti Peraturan Menteri Kesehatan RI No. 2 Tahun 2020 tentang Standar Antropometri Anak.',
            'Tabel referensi berinterval bulanan sehingga nilai di antaranya diinterpolasi. Hasil dapat sedikit berbeda dari perhitungan resmi di fasilitas kesehatan, terutama bila cara atau alat ukur berbeda.',
        ]],
        ['Indikator perkembangan', 'fa-puzzle-piece', [
            'Daftar indikator perkembangan disarikan dari pedoman SDIDTK dan Buku KIA Kementerian Kesehatan RI, bukan kutipan resmi kata per kata, dan BUKAN instrumen skrining klinis seperti KPSP.',
            'Setiap anak berkembang dengan kecepatan yang berbeda. Status "Belum Dapat" tidak berarti anak mengalami keterlambatan atau gangguan perkembangan; diskusikan dengan tenaga kesehatan bila Anda khawatir.',
        ]],
        ['Imunisasi', 'fa-syringe', [
            'Panduan jadwal imunisasi disusun dari program imunisasi rutin pemerintah dan dapat berubah sesuai kebijakan Kementerian Kesehatan.',
            'Aplikasi tidak menyatakan bahwa imunisasi anak sudah lengkap. Buku KIA dan catatan fasilitas kesehatan tetap menjadi acuan resmi.',
        ]],
        ['Obat dan dosis', 'fa-pills', [
            'Aplikasi tidak meresepkan obat, tidak menyarankan obat, dan tidak menghitung dosis obat anak.',
            'Kolom obat dan dosis hanya untuk mencatat apa yang diberikan sesuai resep atau petunjuk tenaga kesehatan. Selalu ikuti petunjuk dokter, apoteker, atau label kemasan.',
        ]],
        ['Ketepatan data', 'fa-pen-to-square', [
            'Seluruh hasil bergantung pada ketepatan data yang dimasukkan, seperti tanggal lahir, tanggal pengukuran, berat, panjang atau tinggi badan, dan cara pengukuran.',
            'Kesalahan input dapat menghasilkan grafik, z-score, atau kategori yang keliru. Periksa kembali data Anda dan cocokkan dengan catatan posyandu atau Buku KIA.',
        ]],
        ['Privasi dan keamanan akun', 'fa-user-shield', [
            'Data anak dan kesehatan hanya dapat diakses oleh akun yang mencatatnya dan tidak ditampilkan kepada pengguna lain.',
            'Anda bertanggung jawab menjaga kerahasiaan kata sandi, keluar dari aplikasi di perangkat bersama, dan tidak membagikan laporan kepada pihak yang tidak berwenang.',
            'Pengelola aplikasi wajib memasang aplikasi dengan HTTPS dan menjaga keamanan server serta cadangan database.',
        ]],
        ['Batasan tanggung jawab', 'fa-scale-balanced', [
            'Aplikasi disediakan "sebagaimana adanya". Pengembang dan pengelola telah berupaya menjaga ketepatan perhitungan dan referensi, namun tidak menjamin aplikasi bebas dari kesalahan, gangguan, atau kehilangan data.',
            'Sejauh diizinkan hukum, pengembang dan pengelola tidak bertanggung jawab atas keputusan, tindakan, atau kerugian yang timbul dari penggunaan informasi di dalam aplikasi tanpa konsultasi dengan tenaga kesehatan.',
        ]],
        ['Perubahan disclaimer', 'fa-clock-rotate-left', [
            'Disclaimer ini dapat diperbarui sewaktu-waktu. Bila ada perubahan penting, Anda akan diminta membaca dan menyetujui versi terbaru sebelum melanjutkan penggunaan aplikasi.',
        ]],
    ];
}

/** Apakah pengguna perlu menyetujui (versi terbaru) disclaimer */
function user_needs_disclaimer(?array $user): bool
{
    if (!$user || !array_key_exists('disclaimer_version', $user)) {
        return false; // kolom belum dimigrasi: jangan memblokir aplikasi
    }
    return $user['disclaimer_version'] !== DISCLAIMER_VERSION;
}

/** Catat persetujuan pengguna */
function record_disclaimer_acceptance(int $userId): void
{
    db()->prepare('UPDATE users SET disclaimer_version = ?, disclaimer_accepted_at = NOW() WHERE id = ?')
        ->execute([DISCLAIMER_VERSION, $userId]);
}

/** Render isi lengkap disclaimer (HTML) */
function render_disclaimer_sections(): void
{
    foreach (disclaimer_sections() as $n => [$title, $icon, $paras]) {
        echo '<section class="dc-section" id="bagian-' . ($n + 1) . '">';
        echo '<h2 class="h6 dc-title"><span class="dc-num">' . ($n + 1) . '</span><i class="fa-solid ' . e($icon) . ' text-primary me-2"></i>' . e($title) . '</h2>';
        $inList = false;
        foreach ($paras as $p) {
            if (strpos($p, '- ') === 0) {
                if (!$inList) { echo '<ul class="dc-list">'; $inList = true; }
                echo '<li>' . e(substr($p, 2)) . '</li>';
                continue;
            }
            if ($inList) { echo '</ul>'; $inList = false; }
            echo '<p>' . e($p) . '</p>';
        }
        if ($inList) echo '</ul>';
        echo '</section>';
    }
}
