-- =====================================================================
-- Data referensi (bukan data pengguna)
-- =====================================================================

-- ---------------------------------------------------------------------
-- Jadwal imunisasi rutin program pemerintah (Kemenkes RI).
-- Sumber: program imunisasi rutin Kemenkes RI (Permenkes No. 12 Tahun 2017
-- tentang Penyelenggaraan Imunisasi) beserta penambahan antigen baru
-- PCV (nasional 2022), Rotavirus (nasional 2023) dan IPV dosis 2
-- (nasional 2023). Jadwal dapat diperbarui pemerintah; selalu cocokkan
-- dengan Buku KIA dan petugas kesehatan. Tabel ini hanya panduan tampilan,
-- BUKAN dasar untuk menyatakan imunisasi anak "lengkap".
-- ---------------------------------------------------------------------
INSERT INTO immunization_schedule (vaccine_name, dose_label, recommended_age_months, age_label, notes, source, sort_order) VALUES
('Hepatitis B (HB0)', 'Dosis lahir', 0, '0 bulan (< 24 jam)', 'Idealnya diberikan kurang dari 24 jam setelah lahir', 'Program imunisasi rutin Kemenkes RI', 10),
('BCG', 'Dosis 1', 1, '1 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 20),
('Polio tetes (OPV)', 'Dosis 1', 1, '1 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 30),
('DPT-HB-Hib', 'Dosis 1', 2, '2 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 40),
('Polio tetes (OPV)', 'Dosis 2', 2, '2 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 50),
('PCV', 'Dosis 1', 2, '2 bulan', 'Antigen baru, nasional sejak 2022', 'Program imunisasi rutin Kemenkes RI', 60),
('Rotavirus (RV)', 'Dosis 1', 2, '2 bulan', 'Antigen baru, nasional sejak 2023', 'Program imunisasi rutin Kemenkes RI', 70),
('DPT-HB-Hib', 'Dosis 2', 3, '3 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 80),
('Polio tetes (OPV)', 'Dosis 3', 3, '3 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 90),
('PCV', 'Dosis 2', 3, '3 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 100),
('Rotavirus (RV)', 'Dosis 2', 3, '3 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 110),
('DPT-HB-Hib', 'Dosis 3', 4, '4 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 120),
('Polio tetes (OPV)', 'Dosis 4', 4, '4 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 130),
('Polio suntik (IPV)', 'Dosis 1', 4, '4 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 140),
('Rotavirus (RV)', 'Dosis 3', 4, '4 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 150),
('Campak-Rubela (MR)', 'Dosis 1', 9, '9 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 160),
('Polio suntik (IPV)', 'Dosis 2', 9, '9 bulan', 'Nasional sejak 2023', 'Program imunisasi rutin Kemenkes RI', 170),
('PCV', 'Dosis 3 (lanjutan)', 12, '12 bulan', NULL, 'Program imunisasi rutin Kemenkes RI', 180),
('DPT-HB-Hib', 'Dosis lanjutan', 18, '18 bulan', 'Imunisasi lanjutan baduta', 'Program imunisasi rutin Kemenkes RI', 190),
('Campak-Rubela (MR)', 'Dosis lanjutan', 18, '18 bulan', 'Imunisasi lanjutan baduta', 'Program imunisasi rutin Kemenkes RI', 200);

-- ---------------------------------------------------------------------
-- Indikator perkembangan per kelompok usia.
-- Disarikan dari kelompok kemampuan dalam pedoman Stimulasi, Deteksi dan
-- Intervensi Dini Tumbuh Kembang (SDIDTK) Kemenkes RI yang juga dimuat
-- pada lembar perkembangan Buku KIA. Redaksi BUKAN kutipan verbatim,
-- sehingga is_official = 0 sampai dicocokkan dengan Buku KIA edisi
-- terbaru. Daftar ini BUKAN instrumen skrining klinis (bukan KPSP).
-- ---------------------------------------------------------------------
SET @src = 'Disarikan dari pedoman SDIDTK / Buku KIA Kemenkes RI (perlu verifikasi redaksi)';

INSERT INTO developmental_indicators (age_min_months, age_max_months, age_group_label, domain, description, source, is_official, sort_order) VALUES
-- 0-3 bulan
(0, 3, '0 - 3 bulan', 'motorik_kasar', 'Mengangkat kepala setinggi 45 derajat saat tengkurap', @src, 0, 101),
(0, 3, '0 - 3 bulan', 'motorik_kasar', 'Menggerakkan kepala dari kiri/kanan ke tengah', @src, 0, 102),
(0, 3, '0 - 3 bulan', 'motorik_halus', 'Melihat dan menatap wajah orang di dekatnya', @src, 0, 103),
(0, 3, '0 - 3 bulan', 'bahasa', 'Mengoceh spontan atau bereaksi dengan mengoceh', @src, 0, 104),
(0, 3, '0 - 3 bulan', 'bahasa', 'Bereaksi terkejut terhadap suara keras', @src, 0, 105),
(0, 3, '0 - 3 bulan', 'sosial', 'Membalas tersenyum ketika diajak bicara atau tersenyum', @src, 0, 106),
(0, 3, '0 - 3 bulan', 'sosial', 'Mengenal ibu melalui penglihatan, penciuman, pendengaran, dan sentuhan', @src, 0, 107),
-- 3-6 bulan
(3, 6, '3 - 6 bulan', 'motorik_kasar', 'Berbalik dari telungkup ke telentang', @src, 0, 201),
(3, 6, '3 - 6 bulan', 'motorik_kasar', 'Mengangkat kepala setinggi 90 derajat saat tengkurap', @src, 0, 202),
(3, 6, '3 - 6 bulan', 'motorik_kasar', 'Mempertahankan posisi kepala tetap tegak dan stabil', @src, 0, 203),
(3, 6, '3 - 6 bulan', 'motorik_halus', 'Menggenggam pensil atau mainan yang disentuhkan ke tangan', @src, 0, 204),
(3, 6, '3 - 6 bulan', 'motorik_halus', 'Meraih benda yang ada dalam jangkauannya', @src, 0, 205),
(3, 6, '3 - 6 bulan', 'bahasa', 'Menoleh ke arah sumber suara', @src, 0, 206),
(3, 6, '3 - 6 bulan', 'sosial', 'Tertawa keras dan bereaksi saat diajak bermain', @src, 0, 207),
-- 6-9 bulan
(6, 9, '6 - 9 bulan', 'motorik_kasar', 'Duduk sendiri tanpa disangga', @src, 0, 301),
(6, 9, '6 - 9 bulan', 'motorik_kasar', 'Belajar berdiri dengan berpegangan, kedua kaki menyangga sebagian berat badan', @src, 0, 302),
(6, 9, '6 - 9 bulan', 'motorik_halus', 'Memindahkan benda dari satu tangan ke tangan lain', @src, 0, 303),
(6, 9, '6 - 9 bulan', 'motorik_halus', 'Memungut benda kecil dengan beberapa jari', @src, 0, 304),
(6, 9, '6 - 9 bulan', 'bahasa', 'Mengeluarkan suara bersuku kata seperti "ma-ma", "da-da"', @src, 0, 305),
(6, 9, '6 - 9 bulan', 'sosial', 'Mencari mainan atau benda yang dijatuhkan', @src, 0, 306),
(6, 9, '6 - 9 bulan', 'sosial', 'Bermain tepuk tangan atau ciluk-ba', @src, 0, 307),
-- 9-12 bulan
(9, 12, '9 - 12 bulan', 'motorik_kasar', 'Berdiri sendiri tanpa berpegangan', @src, 0, 401),
(9, 12, '9 - 12 bulan', 'motorik_kasar', 'Berjalan dengan dituntun', @src, 0, 402),
(9, 12, '9 - 12 bulan', 'motorik_halus', 'Mengambil benda kecil dengan ibu jari dan telunjuk', @src, 0, 403),
(9, 12, '9 - 12 bulan', 'motorik_halus', 'Mempertemukan dua benda kecil yang dipegang di kedua tangan', @src, 0, 404),
(9, 12, '9 - 12 bulan', 'bahasa', 'Menyebut 2-3 suku kata yang sama tanpa arti', @src, 0, 405),
(9, 12, '9 - 12 bulan', 'sosial', 'Melambaikan tangan atau bermain "da-da"', @src, 0, 406),
(9, 12, '9 - 12 bulan', 'sosial', 'Mengenal anggota keluarga dan takut pada orang yang belum dikenal', @src, 0, 407),
-- 12-18 bulan
(12, 18, '12 - 18 bulan', 'motorik_kasar', 'Berjalan sendiri tanpa jatuh', @src, 0, 501),
(12, 18, '12 - 18 bulan', 'motorik_kasar', 'Membungkuk memungut mainan lalu berdiri kembali', @src, 0, 502),
(12, 18, '12 - 18 bulan', 'motorik_halus', 'Menumpuk dua buah kubus', @src, 0, 503),
(12, 18, '12 - 18 bulan', 'motorik_halus', 'Memasukkan kubus ke dalam kotak atau wadah', @src, 0, 504),
(12, 18, '12 - 18 bulan', 'bahasa', 'Mengucapkan 3-6 kata yang mempunyai arti', @src, 0, 505),
(12, 18, '12 - 18 bulan', 'sosial', 'Menunjuk apa yang diinginkan tanpa menangis atau merengek', @src, 0, 506),
(12, 18, '12 - 18 bulan', 'sosial', 'Memperlihatkan rasa cemburu atau bersaing', @src, 0, 507),
-- 18-24 bulan
(18, 24, '18 - 24 bulan', 'motorik_kasar', 'Berdiri sendiri, membungkuk, lalu berdiri lagi', @src, 0, 601),
(18, 24, '18 - 24 bulan', 'motorik_kasar', 'Berjalan mundur 5 langkah', @src, 0, 602),
(18, 24, '18 - 24 bulan', 'motorik_halus', 'Menumpuk empat buah kubus', @src, 0, 603),
(18, 24, '18 - 24 bulan', 'motorik_halus', 'Mencoret-coret dengan alat tulis', @src, 0, 604),
(18, 24, '18 - 24 bulan', 'bahasa', 'Menunjuk bagian tubuh dan menyebut namanya', @src, 0, 605),
(18, 24, '18 - 24 bulan', 'sosial', 'Memegang cangkir sendiri dan belajar makan-minum sendiri', @src, 0, 606),
(18, 24, '18 - 24 bulan', 'sosial', 'Meniru pekerjaan rumah tangga sederhana', @src, 0, 607),
-- 24-36 bulan
(24, 36, '24 - 36 bulan', 'motorik_kasar', 'Naik tangga sendiri', @src, 0, 701),
(24, 36, '24 - 36 bulan', 'motorik_kasar', 'Menendang bola kecil', @src, 0, 702),
(24, 36, '24 - 36 bulan', 'motorik_halus', 'Mencoret-coret pensil pada kertas', @src, 0, 703),
(24, 36, '24 - 36 bulan', 'bahasa', 'Berbicara dengan baik menggunakan 2 kata', @src, 0, 704),
(24, 36, '24 - 36 bulan', 'bahasa', 'Menunjuk satu atau lebih bagian tubuh ketika diminta', @src, 0, 705),
(24, 36, '24 - 36 bulan', 'sosial', 'Melihat gambar dan dapat menyebut dengan benar nama 2 benda atau lebih', @src, 0, 706),
(24, 36, '24 - 36 bulan', 'sosial', 'Makan nasi sendiri tanpa banyak tumpah', @src, 0, 707),
-- 36-48 bulan
(36, 48, '36 - 48 bulan', 'motorik_kasar', 'Berdiri dengan satu kaki selama 2 detik', @src, 0, 801),
(36, 48, '36 - 48 bulan', 'motorik_kasar', 'Melompat dengan kedua kaki diangkat', @src, 0, 802),
(36, 48, '36 - 48 bulan', 'motorik_halus', 'Menggambar garis lurus', @src, 0, 803),
(36, 48, '36 - 48 bulan', 'motorik_halus', 'Menumpuk 8 buah kubus', @src, 0, 804),
(36, 48, '36 - 48 bulan', 'bahasa', 'Menyebut nama, umur, dan tempat', @src, 0, 805),
(36, 48, '36 - 48 bulan', 'bahasa', 'Mengenal 2-4 warna', @src, 0, 806),
(36, 48, '36 - 48 bulan', 'sosial', 'Bermain dengan teman dan mengikuti aturan permainan', @src, 0, 807),
(36, 48, '36 - 48 bulan', 'sosial', 'Mengenakan sepatu sendiri dan mencuci tangan sendiri', @src, 0, 808),
-- 48-60 bulan
(48, 60, '48 - 60 bulan', 'motorik_kasar', 'Berdiri dengan satu kaki selama 6 detik', @src, 0, 901),
(48, 60, '48 - 60 bulan', 'motorik_kasar', 'Melompat-lompat dengan satu kaki', @src, 0, 902),
(48, 60, '48 - 60 bulan', 'motorik_halus', 'Menggambar tanda silang', @src, 0, 903),
(48, 60, '48 - 60 bulan', 'motorik_halus', 'Menggambar lingkaran', @src, 0, 904),
(48, 60, '48 - 60 bulan', 'motorik_halus', 'Menggambar orang dengan 3 bagian tubuh', @src, 0, 905),
(48, 60, '48 - 60 bulan', 'bahasa', 'Menjawab pertanyaan dengan kata-kata yang benar', @src, 0, 906),
(48, 60, '48 - 60 bulan', 'bahasa', 'Menyebut nama lengkap tanpa dibantu', @src, 0, 907),
(48, 60, '48 - 60 bulan', 'sosial', 'Berpakaian sendiri tanpa dibantu', @src, 0, 908),
(48, 60, '48 - 60 bulan', 'sosial', 'Bereaksi tenang dan tidak rewel saat ditinggal ibu', @src, 0, 909);
