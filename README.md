# Pantau Balita

Aplikasi web untuk membantu orangtua memantau pertumbuhan, perkembangan, imunisasi, dan kesehatan anak usia 0–5 tahun.
Dibangun dengan **PHP Native (tanpa framework)**, **MySQL/MariaDB (PDO)**, **Bootstrap 5**, **Chart.js**, **SweetAlert2**, dan **Font Awesome**.

> Hasil aplikasi merupakan **alat bantu pemantauan, bukan diagnosis medis**. Aplikasi tidak menentukan diagnosis, tidak meresepkan obat, dan tidak menghitung dosis obat.

---

## 1. Kebutuhan

| Komponen | Versi minimal |
|---|---|
| PHP | 7.4 (diuji pada 8.3) dengan ekstensi `pdo_mysql`, `fileinfo`, `mbstring` |
| MySQL / MariaDB | MySQL 5.7+ / MariaDB 10.3+ (diuji pada MariaDB 10.11) |
| Web server | Apache (XAMPP/Laragon) atau hosting PHP standar |

Semua pustaka front-end (Bootstrap, Chart.js, SweetAlert2, Font Awesome) sudah disertakan di `assets/vendor/`, jadi aplikasi tetap berjalan tanpa internet. Hanya font Nunito yang dimuat dari Google Fonts; bila offline, otomatis memakai font sistem.

## 2. Instalasi di XAMPP

1. Ekstrak folder `pantau-balita` ke `C:\xampp\htdocs\pantau-balita`.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buat database (lihat bagian 4).
4. Periksa `config/database.php` — standar XAMPP: host `127.0.0.1`, user `root`, password kosong.
5. Buka `http://localhost/pantau-balita/`.

## 3. Instalasi di Laragon

1. Salin folder `pantau-balita` ke `C:\laragon\www\pantau-balita`.
2. Klik **Start All**. Laragon otomatis membuat alamat `http://pantau-balita.test`.
3. Buat database (lihat bagian 4) dan sesuaikan `config/database.php` bila perlu.
4. Buka `http://pantau-balita.test/` (atau `http://localhost/pantau-balita/`).

## 4. Membuat database & mengimpor SQL

**Lewat phpMyAdmin**
1. Buka `http://localhost/phpmyadmin` → **New** → nama `pantau_balita`, collation `utf8mb4_unicode_ci` → **Create**.
2. Pilih database `pantau_balita` → tab **Import** → pilih `database.sql` → **Go**.

**Lewat terminal**
```bash
mysql -u root -p -e "CREATE DATABASE pantau_balita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p pantau_balita < database.sql
```

`database.sql` berisi struktur 11 tabel beserta indeks dan foreign key, ditambah data referensi (jadwal imunisasi, indikator perkembangan, dan 976 baris parameter LMS standar WHO). **Tidak ada akun bawaan** — buat akun lewat halaman Daftar.

> Mengimpor ulang `database.sql` akan **menghapus seluruh data** (berisi `DROP TABLE`).

## 5. Menjalankan aplikasi

1. Buka alamat aplikasi → **Daftar gratis** → isi nama, email, dan kata sandi (min. 8 karakter, huruf + angka).
2. Tambah profil anak → catat pertumbuhan, perkembangan, imunisasi, kesehatan, dan pengingat.
3. Menu **Laporan** → pilih anak & periode → **Cetak / PDF** atau **Ekspor CSV**.

Untuk pengembangan cepat tanpa Apache:
```bash
php -S 127.0.0.1:8000 -t pantau-balita
```
(Server bawaan PHP tidak membaca `.htaccess`; gunakan hanya untuk uji lokal.)

## 6. PWA (pasang di HP) & skeleton loading

**Memasang aplikasi**
- Android/Chrome/Edge: buka aplikasi → tombol **Pasang Aplikasi** (kartu di dashboard pada HP, atau di sidebar desktop), atau menu browser → *Install app / Tambahkan ke layar utama*.
- iPhone/iPad (Safari): ketuk **Bagikan** → **Tambah ke Layar Utama**. Aplikasi menampilkan petunjuk ini saat tombol pasang ditekan.
- Setelah terpasang, aplikasi terbuka tanpa bilah browser, dengan pintasan *Catat Pertumbuhan*, *Catat Imunisasi*, dan *Pengingat* (tekan lama ikon).

**Syarat:** service worker hanya aktif di **HTTPS** atau **localhost**.
- XAMPP: `http://localhost/pantau-balita/` sudah bisa diuji.
- Laragon: domain `http://pantau-balita.test` tidak dianggap aman; aktifkan SSL Laragon (*Menu → Apache → SSL → Enabled*) lalu buka `https://pantau-balita.test`, atau gunakan `http://localhost/pantau-balita/`.
- Hosting publik: wajib HTTPS (sertifikat Let's Encrypt gratis di kebanyakan hosting).

**Cara kerja cache (privasi):**
- Yang disimpan di perangkat **hanya aset statis** (CSS, JS, ikon, font) dan halaman `offline.html`.
- Halaman PHP yang berisi data anak dan kesehatan **tidak pernah di-cache**, dan dikirim dengan header `Cache-Control: no-store`, sehingga tidak tersisa di perangkat bersama setelah logout.
- Saat offline: muncul banner status; navigasi menampilkan halaman offline; formulir tidak dikirim (isian tetap di layar) sampai koneksi kembali.
- Bila ada versi baru, muncul notifikasi *Versi baru aplikasi siap* dengan tombol muat ulang. Saat mengubah daftar aset precache di `sw.js`, naikkan `CACHE_VERSION`.

**Skeleton loading**
- Saat berpindah halaman, mengirim filter, atau menyimpan/menghapus data, isi halaman diganti kerangka berkilau yang meniru tata letak halaman tujuan (dashboard, daftar/tabel, formulir, grafik, detail anak), menggantikan spinner.
- Grafik menampilkan kerangka sampai Chart.js selesai menggambar; bila gagal dimuat, tampil pesan yang jelas.
- Foto anak menampilkan kerangka selama gambar dimuat.
- Animasi berkilau dimatikan otomatis untuk pengguna yang mengaktifkan *reduce motion*.

## 7. Change Log & versi aplikasi

- Menu **Change Log** (sidebar, atau *Lainnya* di HP) menampilkan riwayat setiap versi: tanggal, ringkasan, dan daftar perubahan bertanda **Baru**, **Peningkatan**, **Perbaikan**, atau **Keamanan**, dengan filter per jenis.
- Bila ada versi yang belum dilihat, muncul penanda **Baru** di menu (titik merah di *Lainnya* pada HP). Penanda hilang setelah halaman Change Log dibuka. Status "sudah dilihat" disimpan di browser masing-masing pengguna; pengguna baru tidak diberi penanda untuk versi yang sudah ada saat mereka mendaftar.
- Versi aplikasi tampil di sidebar dan bagian bawah halaman.

**Menambah versi baru:** buka `includes/changelog.php`, tambahkan entri di **paling atas** array:

```php
[
    'version' => '1.3.0',
    'date'    => '2026-11-01',
    'title'   => 'Judul singkat rilis',
    'summary' => 'Satu kalimat ringkasan.',
    'changes' => [
        ['baru', 'Deskripsi fitur baru.'],
        ['perbaikan', 'Deskripsi bug yang diperbaiki.'],
    ],
],
```

Nomor versi aplikasi otomatis mengikuti entri teratas, dan penanda **Baru** otomatis muncul bagi pengguna yang belum membukanya.

## 8. Disclaimer & persetujuan pengguna

- Menu **Disclaimer** (sidebar, tautan di halaman masuk, dan bagian bawah setiap halaman) berisi ringkasan dan 11 bagian ketentuan: tujuan aplikasi, bukan diagnosis/nasihat medis, keadaan darurat (tanda bahaya & nomor **119**), standar pertumbuhan, indikator perkembangan, imunisasi, obat & dosis, ketepatan data, privasi & keamanan akun, batasan tanggung jawab, dan perubahan disclaimer.
- Halaman ini dapat dibuka **tanpa login** dan dicetak.
- **Pendaftaran** wajib mencentang persetujuan disclaimer. Versi dan waktu persetujuan disimpan di tabel `users` (`disclaimer_version`, `disclaimer_accepted_at`).
- **Akun lama** (atau semua akun saat isi disclaimer diperbarui) melihat jendela persetujuan yang tidak dapat ditutup sampai disetujui, lalu diarahkan kembali ke halaman yang sedang dibuka.

**Memperbarui isi disclaimer:** ubah teks di `includes/disclaimer.php`. Bila perubahannya penting, naikkan `DISCLAIMER_VERSION` (mis. `1.0` → `1.1`) dan `DISCLAIMER_DATE`; semua pengguna akan diminta menyetujui ulang.

> Teks disclaimer adalah templat umum. Bila aplikasi dipublikasikan untuk masyarakat luas, sebaiknya ditinjau oleh penasihat hukum dan tenaga kesehatan.

### Upgrade dari versi sebelum 1.3.0

Instalasi baru cukup mengimpor `database.sql`. Untuk database yang sudah berisi data, jalankan **sekali**:

```bash
mysql -u root -p pantau_balita < database/migrations/001_disclaimer.sql
```

atau impor file tersebut lewat phpMyAdmin (tab **Import**). Aplikasi tetap berjalan bila migrasi belum dijalankan, tetapi persetujuan disclaimer belum tersimpan sampai migrasi dilakukan.

## 9. Konfigurasi

| File | Isi |
|---|---|
| `config/database.php` | Host, port, nama database, user, password (bisa juga lewat variabel lingkungan `PB_DB_HOST`, `PB_DB_NAME`, `PB_DB_USER`, `PB_DB_PASS`) |
| `config/config.php` | Nama aplikasi, zona waktu (default `Asia/Jakarta`), `BASE_URL_OVERRIDE` bila deteksi sub-folder keliru, batas unggahan foto (2 MB), mode debug, batas percobaan login |

**Hosting publik:** pastikan `APP_DEBUG` = `false`, gunakan user database khusus (bukan root) dengan password kuat, aktifkan HTTPS, lalu buka komentar blok redirect HTTPS di `.htaccess`. Folder `uploads/children/` harus dapat ditulis oleh web server.

## 10. Struktur folder

```
pantau-balita/
├── .htaccess                 # tutup listing folder & file sensitif (.sql, .md)
├── index.php                 # arahkan ke dashboard / login
├── dashboard.php
├── profile.php               # profil, ubah sandi, hapus akun
├── changelog.php             # menu Change Log
├── disclaimer.php            # halaman Disclaimer + simpan persetujuan
├── database.sql              # struktur + data referensi
├── manifest.webmanifest      # PWA: nama, ikon, warna, pintasan
├── sw.js                     # service worker (cache aset statis saja)
├── offline.html              # halaman saat tidak ada koneksi
├── README.md
├── config/                   # (.htaccess: deny all)
│   ├── config.php
│   └── database.php
├── includes/                 # (.htaccess: deny all)
│   ├── bootstrap.php         # sesi aman, header keamanan, error handler
│   ├── auth.php              # login/logout, timeout sesi, pembatasan login
│   ├── csrf.php
│   ├── functions.php         # helper, format tanggal Indonesia, usia, kepemilikan data, upload
│   ├── growth_standards.php  # z-score WHO (LMS) + kategori Permenkes 2/2020
│   ├── changelog.php         # data riwayat versi (sumber APP_VERSION)
│   ├── disclaimer.php        # isi & versi disclaimer
│   ├── disclaimer_modal.php  # jendela persetujuan untuk akun lama
│   ├── header.php, sidebar.php, footer.php
├── assets/
│   ├── css/style.css
│   ├── js/app.js (skeleton, konfirmasi), charts.js, pwa.js (SW, pasang, status koneksi)
│   ├── images/logo.svg, images/icons/ (ikon PWA 192/512, maskable, apple-touch)
│   └── vendor/               # Bootstrap, Chart.js, SweetAlert2, Font Awesome (+ lisensi)
├── auth/        register.php, login.php, logout.php
├── children/    index, create, edit, detail, delete, photo, _form
├── growth/      index, create, edit, delete, chart, _form, _assessment
├── development/ index, observe, history, delete, _common
├── immunization/index, create, edit, delete, _form
├── health/      index, create, edit, delete, _form
├── reminders/   index, create, edit, delete, toggle, _form
├── reports/     index, print, export (CSV), _data
├── uploads/children/         # foto anak (.htaccess: deny all, disajikan via children/photo.php)
└── database/
    ├── build/                # sumber pembentuk database.sql (skema, seed, data WHO)
    └── migrations/           # skrip upgrade database lama (001_disclaimer.sql)
```

## 11. Fitur yang sudah selesai

- **Akun orangtua**: daftar, login, logout (POST + CSRF), profil, ubah kata sandi, hapus akun beserta seluruh data.
- **Dashboard**: jumlah anak, kartu tiap anak (foto/avatar, usia otomatis, BB/PB-TB terakhir, tanggal ukur, imunisasi berikutnya), pengingat ≤ 14 hari & terlewat, imunisasi lewat jadwal, catatan kesehatan terbaru, 5 tombol cepat, kondisi data kosong.
- **Data anak**: CRUD, foto opsional (validasi MIME + ekstensi + ukuran, nama acak, hanya dapat dilihat pemilik), hapus dengan konfirmasi SweetAlert2 (seluruh catatan ikut terhapus).
- **Pertumbuhan**: CRUD pengukuran (BB, PB/TB + cara ukur, LK, LiLA), filter anak & rentang tanggal, pencarian, pagination, grafik menurut tanggal, kurva WHO (-3/-2/median/+2/+3 SD), tabel z-score tiap pengukuran.
- **Interpretasi WHO**: BB/U, PB/U-TB/U, BB/PB-BB/TB, IMT/U, LK/U dengan metode LMS resmi (termasuk penyesuaian WHO untuk |z| > 3 dan koreksi 0,7 cm cara ukur). Kategori sesuai **Permenkes RI No. 2 Tahun 2020**; LK/U hanya ditampilkan posisinya terhadap ±2 SD. Nilai tidak wajar ditandai sebagai kemungkinan salah input. Bila tabel WHO kosong, hanya grafik aktual yang tampil.
- **Perkembangan**: 9 kelompok usia × 4 bidang (66 indikator), observasi Sudah Dapat / Belum Dapat / Belum Diamati, catatan per indikator, ubah observasi lama, riwayat, ringkasan kemajuan per bidang, saran berdiskusi dengan tenaga kesehatan tanpa label "terlambat".
- **Imunisasi**: CRUD, status (diberikan/dijadwalkan/ditunda), sumber data (Buku KIA, kartu faskes, ingatan orangtua), jadwal mendatang, daftar lewat jadwal, opsi buat pengingat otomatis, panduan jadwal rutin dengan status "tercatat" — tanpa pernyataan "lengkap".
- **Catatan kesehatan**: CRUD, tampilan timeline & tabel, filter tanggal & jenis; dosis obat hanya disalin dari resep.
- **Pengingat**: CRUD, kategori, tanggal & jam opsional, tandai selesai, tampil di dashboard (tanpa klaim notifikasi WA/email/push).
- **Laporan**: filter periode, usia pada tanggal laporan, cetak rapi (CSS print / simpan PDF) berisi profil, pengukuran + z-score, grafik WHO, ringkasan perkembangan, imunisasi, kesehatan, alergi, pengingat; ekspor CSV (UTF-8 BOM, pemisah `;`, aman dari formula injection).
- **PWA**: dapat dipasang di Android/iOS/desktop, ikon & pintasan, halaman offline, banner status koneksi, pencegahan kirim formulir saat offline, notifikasi versi baru — tanpa menyimpan data pribadi di cache.
- **Disclaimer**: halaman ketentuan lengkap (publik & bisa dicetak), persetujuan wajib saat daftar, jendela persetujuan untuk akun lama dan saat versi disclaimer berubah, riwayat versi & waktu persetujuan per akun.
- **Change Log**: riwayat versi dengan filter jenis perubahan, penanda "Baru" untuk versi yang belum dilihat, nomor versi di sidebar & footer.
- **Skeleton loading**: kerangka halaman sesuai jenis halaman tujuan, kerangka grafik dan foto, pemulihan tombol saat kembali ke halaman sebelumnya.
- **Keamanan**: PDO prepared statements, `password_hash`/`password_verify`, `session_regenerate_id(true)`, cookie sesi HttpOnly/SameSite, timeout sesi 2 jam, CSRF di semua formulir yang mengubah data, `htmlspecialchars` di semua output, pemeriksaan kepemilikan di setiap query (anti-IDOR), pembatasan percobaan login, header keamanan, folder sensitif ditutup, pesan error tidak membocorkan kredensial.

## 12. Data referensi & hal yang perlu diverifikasi

| Data | Status | Tindakan yang disarankan |
|---|---|---|
| **Standar WHO (LMS)** | Disertakan. Diambil dari tabel resmi WHO Child Growth Standards 2006 melalui berkas tabel pustaka open-source *pygrowup* (UNICEF, BSD). Sampel diverifikasi (mis. BB/U laki-laki 0 bln: L=0,3487 M=3,3464 S=0,14602). | Tabel WHO berinterval bulanan sehingga usia di antaranya diinterpolasi linear; selisihnya sangat kecil dibanding tabel harian WHO. |
| **Indikator perkembangan** | Disarikan dari kelompok kemampuan pedoman SDIDTK / Buku KIA Kemenkes RI, **bukan kutipan verbatim** — ditandai `is_official = 0`. | Cocokkan redaksi dengan Buku KIA edisi terbaru, ubah di tabel `developmental_indicators`, lalu set `is_official = 1`. Daftar ini bukan instrumen skrining (bukan KPSP). |
| **Jadwal imunisasi** | Program imunisasi rutin Kemenkes (Permenkes 12/2017 + PCV 2022, Rotavirus & IPV2 2023). Hanya panduan tampilan. | Perbarui tabel `immunization_schedule` bila kebijakan pemerintah berubah. |
| **Notifikasi WA/email/push** | Belum dibuat. Pengingat hanya muncul di dashboard. | Perlu integrasi layanan pihak ketiga dan cron job. |
| **Reset kata sandi via email** | Belum dibuat (memerlukan konfigurasi SMTP). | Tambahkan bila server mendukung pengiriman email. |

## 13. Hasil pengujian dasar

Diuji pada PHP 8.3 + MariaDB 10.11 dengan skrip uji otomatis end-to-end (92 pemeriksaan, **semua lulus**), mencakup:

- Halaman privat tanpa login dialihkan; akses langsung `config/` dan `includes/` ditolak.
- Registrasi, email unik, validasi server, hash kata sandi, login/logout, email tidak peka huruf besar, pembatasan 5× gagal login.
- POST tanpa token CSRF ditolak dan tidak menyimpan data.
- XSS: nama `<script>` ditampilkan ter-escape.
- Unggah foto valid diterima dengan nama acak; berkas PHP berkedok `.jpg` ditolak; foto ikut terhapus saat anak dihapus.
- Usia: 31 Jan→29 Feb = 29 hari; 23 bulan 29 hari vs tepat 2 tahun; tampilan tahun + bulan di atas 24 bulan.
- Z-score: median = 0, −2 SD = −2,00, penyesuaian WHO di atas +3 SD = 4,00.
- CRUD semua modul, filter, pencarian, pagination, observasi ulang tanpa duplikat, pengingat otomatis imunisasi, imunisasi lewat jadwal.
- Laporan cetak & ekspor CSV.
- **Otorisasi**: pengguna lain tidak bisa melihat, mengubah, menghapus, atau menambah data ke anak milik akun lain dengan mengganti ID di URL/formulir (9 halaman + foto + POST hapus/tambah).
- Hapus anak menghapus seluruh catatan terkait (cascade).
- 18 halaman utama tampil tanpa warning/notice PHP; tampilan diperiksa di lebar HP (390 px) dan desktop.

Uji browser (Chromium) untuk PWA & skeleton, **semua lulus**:
- Manifest valid, 4 ikon tersedia, service worker aktif dengan scope root aplikasi dan mengendalikan halaman.
- 16 aset statis ter-precache; **tidak ada satu pun halaman PHP/data anak di Cache Storage**; halaman privat mengirim `no-store`.
- Mode offline: banner muncul, navigasi menampilkan halaman offline tanpa data anak, kembali normal saat online.
- Disclaimer (32 pemeriksaan): halaman publik dengan 11 bagian & nomor 119; daftar tanpa persetujuan ditolak; versi & waktu persetujuan tersimpan; akun lama melihat jendela persetujuan yang tidak bisa ditutup (Escape/klik luar) dan tombol aktif hanya setelah dicentang; kembali ke halaman asal; parameter `return` berbahaya (URL luar, `//`, `../`, `javascript:`) diabaikan; CSRF; versi baru meminta persetujuan ulang; aplikasi tetap berjalan pada database yang belum dimigrasi.
- Change Log (13 pemeriksaan): wajib login, 3 rilis tampil, versi saat ini ditandai, filter per jenis & pesan kosong, penanda "Baru" muncul untuk versi yang belum dilihat lalu hilang setelah dibuka, titik merah di menu HP.
- Skeleton tampil untuk 4 jenis halaman (daftar, dashboard, formulir, grafik) dan hilang setelah halaman termuat; kerangka grafik tampil sebelum Chart.js siap; pesan jelas bila Chart.js gagal dimuat.

## 14. Lisensi pustaka

Bootstrap (MIT), Chart.js (MIT), SweetAlert2 (MIT), Font Awesome Free (ikon CC BY 4.0, font SIL OFL 1.1, kode MIT). Berkas lisensi ada di masing-masing folder `assets/vendor/`. Data LMS WHO berasal dari WHO Child Growth Standards.
