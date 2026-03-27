-- ============================================================
-- SISTEM PENGURUSAN SEKOLAH
-- Database Setup SQL
-- Versi 1.0 | 2025
-- ============================================================

CREATE DATABASE IF NOT EXISTS sekolah_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sekolah_db;

-- ------------------------------------------------------------
-- JADUAL: settings (tetapan sistem)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kunci VARCHAR(100) UNIQUE NOT NULL,
    nilai TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (kunci, nilai) VALUES
('nama_sekolah', 'Sekolah Menengah Kebangsaan Contoh'),
('alamat_sekolah', 'Jalan Contoh, 12345 Bandar Contoh, Negeri'),
('telefon_sekolah', '03-12345678'),
('email_sekolah', 'smk.contoh@moe.gov.my'),
('negeri', 'Selangor'),
('daerah', 'Petaling'),
('jenis_sekolah', 'SMK'),
('tahun_semasa', YEAR(NOW())),
('logo_sekolah', ''),
('versi_sistem', '1.0');

-- ------------------------------------------------------------
-- JADUAL: users (pengguna sistem)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    peranan ENUM('super_admin','pentadbir','guru','staf') NOT NULL DEFAULT 'guru',
    status ENUM('aktif','arkib','tangguh') DEFAULT 'aktif',
    foto VARCHAR(255),
    last_login TIMESTAMP NULL,
    token_reset VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default super admin (password: Admin@1234)
INSERT INTO users (nama, username, email, password, peranan) VALUES
('Super Administrator', 'admin', 'admin@sekolah.edu.my', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- ------------------------------------------------------------
-- JADUAL: kelas (kelas sekolah)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) NOT NULL,
    tingkatan VARCHAR(10) NOT NULL,
    aliran VARCHAR(50),
    guru_kelas_id INT,
    bil_murid INT DEFAULT 0,
    tahun YEAR NOT NULL,
    status ENUM('aktif','arkib') DEFAULT 'aktif',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- JADUAL: murid (data pelajar)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS murid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pendaftaran VARCHAR(50),
    nama VARCHAR(255) NOT NULL,
    no_ic VARCHAR(20),
    jantina ENUM('L','P') NOT NULL,
    tarikh_lahir DATE,
    kelas_id INT,
    tahun YEAR NOT NULL,
    bangsa ENUM('Melayu','Cina','India','Bumiputera Sabah','Bumiputera Sarawak','Lain-lain') DEFAULT 'Melayu',
    agama VARCHAR(50),
    alamat TEXT,
    poskod VARCHAR(10),
    bandar VARCHAR(100),
    negeri_murid VARCHAR(100),
    telefon_ibu_bapa VARCHAR(20),
    nama_ibu_bapa VARCHAR(255),
    hubungan VARCHAR(50),
    status ENUM('aktif','arkib','berpindah','tamat') DEFAULT 'aktif',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kelas (kelas_id),
    INDEX idx_tahun (tahun),
    INDEX idx_jantina (jantina)
);

-- ------------------------------------------------------------
-- JADUAL: subjek (senarai mata pelajaran)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(20),
    nama VARCHAR(100) NOT NULL,
    nama_pendek VARCHAR(30),
    kategori ENUM('Teras','Elektif','Tambahan','Bahasa','Agama','Vokasional') DEFAULT 'Teras',
    tingkatan VARCHAR(20),
    status ENUM('aktif','tidak_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO subjek (kod, nama, nama_pendek, kategori, tingkatan) VALUES
('BM', 'Bahasa Melayu', 'BM', 'Teras', '1,2,3,4,5'),
('BI', 'Bahasa Inggeris', 'BI', 'Teras', '1,2,3,4,5'),
('MAT', 'Matematik', 'MAT', 'Teras', '1,2,3,4,5'),
('SEJ', 'Sejarah', 'SEJ', 'Teras', '1,2,3,4,5'),
('GEO', 'Geografi', 'GEO', 'Teras', '1,2,3'),
('SN', 'Sains', 'SN', 'Teras', '1,2,3'),
('PI', 'Pendidikan Islam', 'PI', 'Teras', '1,2,3,4,5'),
('PM', 'Pendidikan Moral', 'PM', 'Teras', '1,2,3,4,5'),
('PJ', 'Pendidikan Jasmani', 'PJ', 'Teras', '1,2,3,4,5'),
('KH', 'Kemahiran Hidup Bersepadu', 'KH', 'Teras', '1,2,3'),
('RBT', 'Reka Bentuk dan Teknologi', 'RBT', 'Teras', '1,2,3'),
('ERT', 'Ekonomi Rumah Tangga', 'ERT', 'Elektif', '4,5'),
('ADD MAT', 'Matematik Tambahan', 'A.MAT', 'Elektif', '4,5'),
('FIZ', 'Fizik', 'FIZ', 'Elektif', '4,5'),
('KIM', 'Kimia', 'KIM', 'Elektif', '4,5'),
('BIO', 'Biologi', 'BIO', 'Elektif', '4,5'),
('EKO', 'Ekonomi', 'EKO', 'Elektif', '4,5'),
('PA', 'Perdagangan', 'PA', 'Elektif', '4,5'),
('PAI', 'Pendidikan Al-Quran & As-Sunnah', 'PAI', 'Agama', '1,2,3,4,5'),
('BC', 'Bahasa Cina', 'BC', 'Bahasa', '1,2,3,4,5'),
('BT', 'Bahasa Tamil', 'BT', 'Bahasa', '1,2,3,4,5');

-- ------------------------------------------------------------
-- JADUAL: guru (data guru)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    no_ic VARCHAR(20),
    no_pekerja VARCHAR(50),
    jawatan VARCHAR(100),
    gred VARCHAR(20),
    opsyen VARCHAR(100),
    subjek_utama VARCHAR(100),
    telefon VARCHAR(20),
    email VARCHAR(255),
    tarikh_lahir DATE,
    jantina ENUM('L','P'),
    bangsa VARCHAR(50),
    agama VARCHAR(50),
    alamat TEXT,
    user_id INT,
    status ENUM('aktif','arkib','berhenti','bersara') DEFAULT 'aktif',
    foto VARCHAR(255),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);

-- ------------------------------------------------------------
-- JADUAL: pentadbir (data pentadbir sekolah)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pentadbir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    no_ic VARCHAR(20),
    jawatan ENUM('Guru Besar','Pengetua','Penolong Kanan 1','Penolong Kanan HEM','Penolong Kanan Kokurikulum','Penolong Kanan Petang','Guru Senior','Lain-lain') NOT NULL,
    no_pekerja VARCHAR(50),
    gred VARCHAR(20),
    telefon VARCHAR(20),
    email VARCHAR(255),
    jantina ENUM('L','P'),
    tarikh_mula DATE,
    user_id INT,
    status ENUM('aktif','arkib') DEFAULT 'aktif',
    foto VARCHAR(255),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- JADUAL: guru_kelas_subjek (guru mengajar kelas & subjek)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guru_kelas_subjek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    kelas_id INT NOT NULL,
    subjek_id INT,
    nama_subjek VARCHAR(100),
    waktu_seminggu INT DEFAULT 0,
    tahun YEAR NOT NULL,
    status ENUM('aktif','arkib') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_guru (guru_id),
    INDEX idx_kelas (kelas_id),
    INDEX idx_tahun (tahun)
);

-- ------------------------------------------------------------
-- JADUAL: prestasi (markah & pencapaian murid)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prestasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    murid_id INT NOT NULL,
    subjek_id INT,
    nama_subjek VARCHAR(100),
    jenis_penilaian ENUM('PT1','PT2','PT3','PAT','ULBS','PBS','UASA','PPT','Ujian Harian','Lain-lain') DEFAULT 'PT1',
    penggal TINYINT,
    markah DECIMAL(5,2),
    gred VARCHAR(5),
    nilai_gred TINYINT,
    band VARCHAR(5),
    tahun YEAR NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_murid (murid_id),
    INDEX idx_tahun (tahun),
    INDEX idx_penilaian (jenis_penilaian)
);

-- ------------------------------------------------------------
-- JADUAL: kurikulum (DSKP Semakan 2017)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kurikulum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mata_pelajaran VARCHAR(100) NOT NULL,
    tingkatan VARCHAR(10),
    bahagian VARCHAR(100),
    tema VARCHAR(255),
    unit INT,
    tajuk_unit VARCHAR(255),
    standard_kandungan_kod VARCHAR(50),
    standard_kandungan TEXT,
    standard_pembelajaran_kod VARCHAR(50),
    standard_pembelajaran TEXT,
    standard_prestasi TEXT,
    huraian_sp TEXT,
    catatan TEXT,
    tahun_semakan YEAR DEFAULT 2017,
    status ENUM('aktif','arkib') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_matapelajaran (mata_pelajaran),
    INDEX idx_tingkatan (tingkatan)
);

-- ------------------------------------------------------------
-- JADUAL: erph (Evidens Rekod Pengajaran & Hasil)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS erph (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rujukan VARCHAR(50),
    tajuk VARCHAR(255) NOT NULL,
    tarikh DATE NOT NULL,
    masa_mula TIME,
    masa_tamat TIME,
    guru_id INT,
    kelas_id INT,
    nama_kelas VARCHAR(100),
    subjek_id INT,
    nama_subjek VARCHAR(100),
    kurikulum_id INT,
    standard_kandungan TEXT,
    standard_pembelajaran TEXT,
    standard_prestasi VARCHAR(10),
    evidens TEXT,
    hasil_pembelajaran TEXT,
    pendekatan_pdp TEXT,
    refleksi TEXT,
    tindakan_susulan TEXT,
    bil_murid_hadir INT DEFAULT 0,
    fail_lampiran VARCHAR(255),
    tahun YEAR NOT NULL,
    status ENUM('aktif','draf','arkib') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guru (guru_id),
    INDEX idx_kelas (kelas_id),
    INDEX idx_tarikh (tarikh),
    INDEX idx_tahun (tahun)
);

-- ERPH murid (many-to-many)
CREATE TABLE IF NOT EXISTS erph_murid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erph_id INT NOT NULL,
    murid_id INT NOT NULL,
    hadir TINYINT(1) DEFAULT 1,
    INDEX idx_erph (erph_id),
    INDEX idx_murid (murid_id)
);

-- ------------------------------------------------------------
-- JADUAL: opr (One Page Report)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS opr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rujukan VARCHAR(50),
    tajuk VARCHAR(255) NOT NULL,
    tarikh_aktiviti DATE NOT NULL,
    kategori ENUM('Akademik','Ko-Kurikulum','Sukan','Kebajikan','Pembangunan Staf','Ibu Bapa','Lain-lain') DEFAULT 'Akademik',
    tempat VARCHAR(255),
    pegawai_bertanggungjawab VARCHAR(255),
    penerangan_umum TEXT,
    penerangan_lanjut TEXT,
    impak TEXT,
    saranan TEXT,
    gambar1 VARCHAR(255),
    gambar1_kapsyen VARCHAR(255),
    gambar2 VARCHAR(255),
    gambar2_kapsyen VARCHAR(255),
    gambar3 VARCHAR(255),
    gambar3_kapsyen VARCHAR(255),
    guru_id INT,
    status ENUM('draf','aktif','arkib') DEFAULT 'draf',
    tahun YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guru (guru_id),
    INDEX idx_tarikh (tarikh_aktiviti),
    INDEX idx_tahun (tahun),
    INDEX idx_status (status)
);

-- ------------------------------------------------------------
-- JADUAL: autosave (simpan draf automatik)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS autosave (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    modul VARCHAR(50) NOT NULL,
    rekod_id INT DEFAULT 0,
    data JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_autosave (user_id, modul, rekod_id)
);

-- ------------------------------------------------------------
-- JADUAL: aktiviti_log (log aktiviti pengguna)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aktiviti_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tindakan VARCHAR(50),
    modul VARCHAR(50),
    rekod_id INT,
    keterangan TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_modul (modul)
);

-- ------------------------------------------------------------
-- JADUAL: arkib_log (rekod arkib tahunan)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS arkib_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun YEAR NOT NULL,
    modul VARCHAR(50),
    bil_rekod INT DEFAULT 0,
    tarikh_arkib TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    nota TEXT
);

-- ============================================================
-- TAMAT SETUP
-- ============================================================
