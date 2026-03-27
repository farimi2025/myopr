<?php
// ============================================================
// BANTUAN & SOALAN LAZIM (FAQ)
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle = 'Bantuan & FAQ';
$namaSekolah = getSetting('nama_sekolah');

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-question-circle me-2 text-info"></i>Bantuan & Soalan Lazim</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Bantuan</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Carian -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="cariFaq" class="form-control" placeholder="Cari soalan atau jawapan...">
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Modul-modul -->
    <div class="col-lg-8">

        <!-- Modul Murid -->
        <div class="card shadow-sm mb-4 faq-section">
            <div class="card-header bg-primary bg-opacity-10 border-0">
                <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Modul Murid</h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="faqMurid">
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Bagaimana cara mendaftarkan murid baharu?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqMurid">
                            <div class="accordion-body text-muted small">
                                Pergi ke <strong>Modul Murid &rarr; Tambah Murid</strong>. Isi semua maklumat yang diperlukan termasuk nama, no. IC, kelas dan maklumat ibu bapa. Nombor pendaftaran akan dijana secara automatik (format: MRD/TAHUN/NNNN).
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Bagaimana menandakan murid yang berpindah atau tamat belajar?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqMurid">
                            <div class="accordion-body text-muted small">
                                Dalam halaman senarai murid, klik butang <strong>Edit</strong> pada rekod murid berkenaan. Tukar status kepada <em>Berpindah</em> atau <em>Tamat</em>. Rekod tidak akan dipadam, hanya status berubah.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Bolehkah saya eksport senarai murid?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqMurid">
                            <div class="accordion-body text-muted small">
                                Ya. Dalam halaman senarai murid terdapat butang <strong>Export PDF</strong> dan <strong>Export Excel</strong>. PDF akan dibuka dalam tab baru untuk cetak. Excel akan memuat turun fail CSV yang boleh dibuka dengan Microsoft Excel.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modul Prestasi -->
        <div class="card shadow-sm mb-4 faq-section">
            <div class="card-header bg-success bg-opacity-10 border-0">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-success"></i>Modul Prestasi</h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="faqPrestasi">
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Apakah perbezaan antara mod Individu dan mod Batch?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqPrestasi">
                            <div class="accordion-body text-muted small">
                                <strong>Mod Individu</strong>: Masukkan markah seorang murid untuk satu mata pelajaran.<br>
                                <strong>Mod Batch</strong>: Pilih kelas, kemudian masukkan markah semua murid dalam kelas tersebut untuk satu mata pelajaran sekaligus. Lebih cekap untuk kemasukan markah peperiksaan.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Bagaimana gred dikira secara automatik?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqPrestasi">
                            <div class="accordion-body text-muted small">
                                Gred dikira berdasarkan markah mengikut skala SPM Malaysia:<br>
                                <span class="badge bg-success">A+</span> 90-100 &nbsp;
                                <span class="badge bg-success">A</span> 80-89 &nbsp;
                                <span class="badge bg-success">A-</span> 75-79 &nbsp;
                                <span class="badge bg-primary">B+</span> 70-74 &nbsp;
                                <span class="badge bg-primary">B</span> 65-69 &nbsp;
                                <span class="badge bg-info text-dark">C+</span> 60-64 &nbsp;
                                <span class="badge bg-info text-dark">C</span> 55-59 &nbsp;
                                <span class="badge bg-warning text-dark">D</span> 50-54 &nbsp;
                                <span class="badge bg-danger">E</span> 45-49 &nbsp;
                                <span class="badge bg-dark">G</span> 0-44
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modul ERPH -->
        <div class="card shadow-sm mb-4 faq-section">
            <div class="card-header bg-warning bg-opacity-10 border-0">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2 text-warning"></i>Modul ERPH</h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="faqErph">
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Apakah itu ERPH?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqErph">
                            <div class="accordion-body text-muted small">
                                ERPH (Evidens Rekod Pengajaran &amp; Hasil) adalah rekod harian pengajaran dan pembelajaran yang perlu diisi oleh guru. Ia merangkumi standard kurikulum (DSKP), evidens pengajaran, hasil pembelajaran dan refleksi guru.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                Bolehkah guru biasa melihat ERPH guru lain?
                            </button>
                        </h2>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqErph">
                            <div class="accordion-body text-muted small">
                                Tidak. Guru biasa hanya boleh melihat dan mengedit ERPH milik mereka sendiri. Hanya pengguna dengan peranan <strong>Admin</strong>, <strong>Pengetua</strong> atau <strong>Penolong Kanan</strong> boleh melihat semua rekod ERPH.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tetapan & Akaun -->
        <div class="card shadow-sm mb-4 faq-section">
            <div class="card-header bg-secondary bg-opacity-10 border-0">
                <h5 class="mb-0"><i class="bi bi-gear me-2 text-secondary"></i>Tetapan & Akaun</h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="faqTetapan">
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                Bagaimana menukar kata laluan?
                            </button>
                        </h2>
                        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqTetapan">
                            <div class="accordion-body text-muted small">
                                Klik nama pengguna di penjuru kanan atas &rarr; <strong>Profil Saya</strong> &rarr; tab <strong>Tukar Kata Laluan</strong>. Masukkan kata laluan semasa dan kata laluan baru (minimum 8 aksara).
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                                Apakah fungsi Arkib Tahunan?
                            </button>
                        </h2>
                        <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqTetapan">
                            <div class="accordion-body text-muted small">
                                Arkib Tahunan (<strong>Pentadbir &rarr; Arkib</strong>) membolehkan anda mengarkibkan semua rekod (murid, guru, ERPH, OPR) untuk tahun tertentu. Rekod yang diarkibkan masih boleh dilihat tetapi tidak boleh diedit. Fungsi ini biasanya dijalankan pada akhir tahun persekolahan.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item faq-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                                Sesi saya tamat secara automatik, mengapa?
                            </button>
                        </h2>
                        <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqTetapan">
                            <div class="accordion-body text-muted small">
                                Sistem akan log keluar pengguna secara automatik selepas 2 jam tidak aktif sebagai langkah keselamatan. Log masuk semula untuk meneruskan kerja anda.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.col-lg-8 -->

    <!-- Sidebar maklumat -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4 bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Maklumat Sistem</h5>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><i class="bi bi-building me-2 opacity-75"></i><?= clean($namaSekolah) ?></li>
                    <li class="mb-2"><i class="bi bi-code-slash me-2 opacity-75"></i>Sistem Pengurusan Sekolah v1.0</li>
                    <li class="mb-2"><i class="bi bi-calendar me-2 opacity-75"></i>Tahun Semasa: <?= TAHUN_SEMASA ?></li>
                    <li><i class="bi bi-shield-check me-2 opacity-75"></i>Sesi tamat selepas 2 jam tidak aktif</li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Pautan Pantas
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= BASE_URL ?>/modules/murid/index.php" class="list-group-item list-group-item-action small">
                    <i class="bi bi-people me-2 text-primary"></i>Senarai Murid
                </a>
                <a href="<?= BASE_URL ?>/modules/guru/index.php" class="list-group-item list-group-item-action small">
                    <i class="bi bi-person-badge me-2 text-success"></i>Senarai Guru
                </a>
                <a href="<?= BASE_URL ?>/modules/prestasi/tambah.php" class="list-group-item list-group-item-action small">
                    <i class="bi bi-plus-circle me-2 text-info"></i>Tambah Markah
                </a>
                <a href="<?= BASE_URL ?>/modules/erph/tambah.php" class="list-group-item list-group-item-action small">
                    <i class="bi bi-journal-plus me-2 text-warning"></i>Tambah ERPH
                </a>
                <a href="<?= BASE_URL ?>/modules/pentadbir/tetapan.php" class="list-group-item list-group-item-action small">
                    <i class="bi bi-gear me-2 text-secondary"></i>Tetapan Sistem
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning bg-opacity-10 border-bottom-0 fw-semibold">
                <i class="bi bi-keyboard me-2 text-warning"></i>Pintasan Papan Kekunci
            </div>
            <div class="card-body small">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td><kbd>Ctrl</kbd>+<kbd>S</kbd></td><td>Simpan autosave draf</td></tr>
                        <tr><td><kbd>Esc</kbd></td><td>Tutup modal/dialog</td></tr>
                        <tr><td><kbd>Alt</kbd>+<kbd>N</kbd></td><td>Tambah rekod baru</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Cari FAQ
document.getElementById('cariFaq').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });
    document.querySelectorAll('.faq-section').forEach(section => {
        const visible = [...section.querySelectorAll('.faq-item')].some(i => i.style.display !== 'none');
        section.style.display = visible ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
