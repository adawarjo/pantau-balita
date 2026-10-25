<?php
/**
 * Riwayat perubahan aplikasi (Change Log).
 *
 * Tambahkan versi baru di PALING ATAS array. Entri pertama otomatis menjadi
 * versi aplikasi (APP_VERSION) yang tampil di sidebar & footer.
 *
 * Jenis perubahan (type):
 *   baru        = fitur baru
 *   peningkatan = penyempurnaan fitur yang sudah ada
 *   perbaikan   = perbaikan bug
 *   keamanan    = keamanan & privasi
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

return [
    [
        'version' => '1.3.0',
        'date'    => '2026-10-25',
        'title'   => 'Disclaimer',
        'summary' => 'Batasan penggunaan aplikasi kini dijelaskan lengkap dan disetujui setiap pengguna.',
        'changes' => [
            ['baru', 'Halaman Disclaimer berisi ringkasan dan ketentuan lengkap: bukan diagnosis medis, keadaan darurat (hubungi 119), standar pertumbuhan, indikator perkembangan, imunisasi, obat, ketepatan data, privasi, dan batasan tanggung jawab.'],
            ['baru', 'Persetujuan disclaimer wajib saat mendaftar akun baru.'],
            ['baru', 'Akun yang sudah ada diminta menyetujui disclaimer satu kali; permintaan muncul lagi bila isi disclaimer diperbarui.'],
            ['peningkatan', 'Tautan Disclaimer di menu, halaman masuk, dan bagian bawah setiap halaman.'],
            ['peningkatan', 'Disclaimer dapat dibaca tanpa login dan dicetak.'],
        ],
    ],
    [
        'version' => '1.2.0',
        'date'    => '2026-10-25',
        'title'   => 'Menu Change Log',
        'summary' => 'Riwayat pembaruan aplikasi kini dapat dilihat langsung dari menu.',
        'changes' => [
            ['baru', 'Menu Change Log berisi daftar perubahan setiap versi, lengkap dengan filter jenis perubahan.'],
            ['baru', 'Penanda "Baru" pada menu bila ada versi yang belum Anda lihat.'],
            ['peningkatan', 'Nomor versi aplikasi ditampilkan di sidebar dan bagian bawah halaman.'],
        ],
    ],
    [
        'version' => '1.1.0',
        'date'    => '2026-10-25',
        'title'   => 'Aplikasi bisa dipasang & skeleton loading',
        'summary' => 'Pantau Balita bisa dipasang di layar utama HP dan terasa lebih cepat saat berpindah halaman.',
        'changes' => [
            ['baru', 'Progressive Web App: pasang di Android, iPhone, dan desktop, lengkap dengan ikon dan pintasan Catat Pertumbuhan, Catat Imunisasi, dan Pengingat.'],
            ['baru', 'Halaman khusus saat offline dan banner status koneksi.'],
            ['baru', 'Skeleton loading menggantikan spinner: kerangka halaman sesuai tujuan (dashboard, daftar, formulir, grafik, detail anak).'],
            ['peningkatan', 'Grafik dan foto anak menampilkan kerangka selama dimuat; pesan jelas bila grafik gagal dimuat.'],
            ['peningkatan', 'Formulir tidak dikirim saat offline sehingga isian tidak hilang.'],
            ['peningkatan', 'Notifikasi saat versi baru aplikasi tersedia.'],
            ['keamanan', 'Hanya aset tampilan yang disimpan di perangkat; halaman berisi data anak tidak pernah di-cache dan dikirim dengan header no-store.'],
            ['perbaikan', 'Banner "offline" tidak lagi muncul di HP saat sedang online.'],
            ['perbaikan', 'Kotak grafik tidak lagi kosong tanpa keterangan bila pustaka grafik gagal dimuat.'],
        ],
    ],
    [
        'version' => '1.0.0',
        'date'    => '2026-10-25',
        'title'   => 'Rilis pertama',
        'summary' => 'Fitur inti pemantauan tumbuh kembang anak usia 0-5 tahun.',
        'changes' => [
            ['baru', 'Akun orangtua: daftar, masuk, profil, ubah kata sandi, dan hapus akun.'],
            ['baru', 'Dashboard ringkas berisi data tiap anak, pengingat, imunisasi berikutnya, dan catatan kesehatan terbaru.'],
            ['baru', 'Data anak dengan foto, usia dihitung otomatis dari tanggal lahir.'],
            ['baru', 'Pencatatan pertumbuhan dengan grafik dan perbandingan standar WHO (BB/U, PB/U-TB/U, BB/PB-BB/TB, IMT/U, LK/U) sesuai kategori Permenkes No. 2 Tahun 2020.'],
            ['baru', 'Observasi perkembangan per kelompok usia untuk 4 bidang kemampuan.'],
            ['baru', 'Catatan imunisasi, panduan jadwal imunisasi rutin, dan daftar imunisasi lewat jadwal.'],
            ['baru', 'Catatan kesehatan dalam tampilan timeline dan tabel.'],
            ['baru', 'Pengingat jadwal yang tampil di dashboard.'],
            ['baru', 'Laporan siap cetak/PDF untuk konsultasi dan ekspor CSV.'],
            ['keamanan', 'Perlindungan CSRF, XSS, SQL injection, pembatasan percobaan login, dan pemeriksaan kepemilikan data di setiap halaman.'],
        ],
    ],
];
