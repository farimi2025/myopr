<?php
// ============================================================
// ERPH - LIHAT REKOD PENGAJARAN & PEMBELAJARAN HARIAN
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'ID rekod tidak sah.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

$erph = dbFetch("SELECT e.*, g.nama AS guru_nama, g.jawatan AS guru_jawatan,
    k.tingkatan AS kelas_tingkatan
    FROM erph e
    LEFT JOIN guru g ON g.id = e.guru_id
    LEFT JOIN kelas k ON k.id = e.kelas_id
    WHERE e.id = ?", [$id]);

if (!$erph) {
    setFlash('danger', 'Rekod ERPH tidak dijumpai.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

// Semak akses
if (!hasRole(['admin','pengetua','penolong_kanan']) && $erph['guru_id'] != $_SESSION['user_id']) {
    setFlash('danger', 'Anda tidak mempunyai akses untuk melihat rekod ini.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

$pageTitle = 'ERPH: ' . $erph['no_rujukan'];

// Senarai murid
$muridList = dbFetchAll(
    "SELECT em.hadir, m.nama, m.jantina, m.id AS murid_id
     FROM erph_murid em
     JOIN murid m ON m.id = em.murid_id
     WHERE em.erph_id = ?
     ORDER BY m.nama", [$id]);

$bilHadir  = count(array_filter($muridList, fn($m) => $m['hadir']));
$bilGhaib  = count(array_filter($muridList, fn($m) => !$m['hadir']));

// SP badge helper
function badgeSPLihat(string $sp): string {
    $map = ['SP1'=>'secondary','SP2'=>'info','SP3'=>'primary','SP4'=>'success','SP5'=>'warning','SP6'=>'danger'];
    $cls = $map[$sp] ?? 'secondary';
    return '<span class="badge bg-' . $cls . ' fs-6 px-3 py-2">' . clean($sp) . '</span>';
}

$sekolahNama = getSetting('nama_sekolah');
$sekolahAlamat = getSetting('alamat_sekolah');

include __DIR__ . '/../../includes/header.php';
?>

<!-- Print styles -->
<style>
@media print {
    .no-print, .sb-topnav, #layoutSidenav_nav, footer, .breadcrumb { display: none !important; }
    #layoutSidenav_content { margin: 0 !important; }
    main { padding: 0 !important; }
    .container-fluid { padding: 0 !important; }
    .print-page { padding: 20px; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    body { background: white !important; }
    .badge { border: 1px solid #aaa; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Rekod ERPH</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/erph/index.php">ERPH</a></li>
            <li class="breadcrumb-item active"><?= clean($erph['no_rujukan']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>
        <a href="<?= BASE_URL ?>/export/pdf.php?modul=erph&id=<?= $id ?>" class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="<?= BASE_URL ?>/modules/erph/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="<?= BASE_URL ?>/modules/erph/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?= showFlash() ?>

<div class="print-page">

    <!-- Header Print -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php $logo = getSetting('logo_sekolah'); ?>
                    <?php if ($logo): ?>
                    <img src="<?= gambarUrl($logo) ?>" alt="Logo" style="height:70px;width:auto">
                    <?php else: ?>
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:70px;height:70px;font-size:1.5rem">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col text-center">
                    <h5 class="fw-bold mb-0 text-primary"><?= clean($sekolahNama) ?></h5>
                    <div class="text-muted small"><?= clean($sekolahAlamat) ?></div>
                    <div class="mt-2">
                        <span class="badge bg-primary fs-6 px-4 py-2">
                            REKOD PENGAJARAN &amp; PEMBELAJARAN HARIAN (ERPH)
                        </span>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <?= badgeStatus($erph['status']) ?>
                    <div class="small text-muted mt-1"><?= clean($erph['no_rujukan']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maklumat Asas -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Maklumat Asas</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border-start border-primary border-3">
                        <div class="text-muted small">Tajuk PdP</div>
                        <div class="fw-bold fs-5"><?= clean($erph['tajuk']) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">No. Rujukan</div>
                    <div class="fw-semibold"><?= clean($erph['no_rujukan']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Tarikh</div>
                    <div class="fw-semibold"><i class="bi bi-calendar3 me-1 text-primary"></i><?= formatTarikh($erph['tarikh']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Masa</div>
                    <div class="fw-semibold">
                        <i class="bi bi-clock me-1 text-primary"></i>
                        <?= clean($erph['masa_mula'] ?: '-') ?> - <?= clean($erph['masa_tamat'] ?: '-') ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Tahun</div>
                    <div class="fw-semibold"><?= clean($erph['tahun']) ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Guru</div>
                    <div class="fw-semibold"><i class="bi bi-person me-1 text-primary"></i><?= clean($erph['guru_nama'] ?: '-') ?></div>
                    <?php if ($erph['guru_jawatan']): ?>
                    <div class="text-muted small"><?= clean($erph['guru_jawatan']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Kelas</div>
                    <div class="fw-semibold">
                        <i class="bi bi-building me-1 text-primary"></i>
                        <?= clean(($erph['kelas_tingkatan'] ? $erph['kelas_tingkatan'] . ' ' : '') . $erph['nama_kelas']) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Subjek</div>
                    <div class="fw-semibold"><i class="bi bi-book me-1 text-primary"></i><?= clean($erph['nama_subjek'] ?: '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Standard Kurikulum -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white py-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-book me-2"></i>Standard Kurikulum (DSKP)</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($erph['standard_kandungan']): ?>
                <div class="col-12">
                    <div class="text-muted small fw-semibold mb-1">Standard Kandungan</div>
                    <div class="p-3 bg-light rounded"><?= nl2br(clean($erph['standard_kandungan'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($erph['standard_pembelajaran']): ?>
                <div class="col-12">
                    <div class="text-muted small fw-semibold mb-1">Standard Pembelajaran</div>
                    <div class="p-3 bg-light rounded"><?= nl2br(clean($erph['standard_pembelajaran'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="text-muted small fw-semibold mb-2">Standard Prestasi</div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <?php
                        $spColors = ['SP1'=>'secondary','SP2'=>'info','SP3'=>'primary','SP4'=>'success','SP5'=>'warning','SP6'=>'danger'];
                        $hasSP = false;
                        foreach ($spColors as $sp => $col):
                            if (!empty($erph[$sp])):
                                $hasSP = true;
                        ?>
                        <span class="badge bg-<?= $col ?> fs-6 px-3 py-2"><?= $sp ?></span>
                        <?php endif; endforeach; ?>
                        <?php if (!$hasSP && $erph['standard_prestasi']): ?>
                        <?= badgeSPLihat($erph['standard_prestasi']) ?>
                        <?php elseif (!$hasSP): ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maklumat PdP -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white py-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-mortarboard me-2"></i>Maklumat PdP</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php $pdpFields = [
                    'hasil_pembelajaran' => 'Hasil Pembelajaran',
                    'pendekatan_pdp'     => 'Pendekatan PdP',
                    'evidens'            => 'Evidens Pembelajaran',
                    'refleksi'           => 'Refleksi',
                    'tindakan_susulan'   => 'Tindakan Susulan',
                ]; ?>
                <?php foreach ($pdpFields as $field => $label): ?>
                <?php if ($erph[$field]): ?>
                <div class="col-md-6">
                    <div class="text-muted small fw-semibold mb-1"><?= $label ?></div>
                    <div class="p-3 bg-light rounded" style="min-height:60px"><?= nl2br(clean($erph[$field])) ?></div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                <div class="col-12">
                    <div class="d-inline-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 rounded-3">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                        <div>
                            <div class="text-muted small">Bilangan Murid Hadir</div>
                            <div class="fw-bold fs-4 text-primary"><?= (int)$erph['bil_murid_hadir'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Senarai Murid -->
    <?php if (!empty($muridList)): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Kehadiran Murid</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-success">Hadir: <?= $bilHadir ?></span>
                <span class="badge bg-danger">Tidak Hadir: <?= $bilGhaib ?></span>
                <span class="badge bg-dark">Jumlah: <?= count($muridList) ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Nama Murid</th>
                            <th class="text-center">Jantina</th>
                            <th class="text-center">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($muridList as $i => $m): ?>
                        <tr class="<?= !$m['hadir'] ? 'table-danger' : '' ?>">
                            <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= clean($m['nama']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $m['jantina'] === 'L' ? 'info' : 'danger' ?> bg-opacity-75">
                                    <?= $m['jantina'] === 'L' ? 'Lelaki' : 'Perempuan' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($m['hadir']): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="text-success ms-1 small fw-semibold">Hadir</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                    <span class="text-danger ms-1 small fw-semibold">Tidak Hadir</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lampiran -->
    <?php if ($erph['fail_lampiran']): ?>
    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-header bg-secondary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Lampiran</h6>
        </div>
        <div class="card-body">
            <a href="<?= BASE_URL . '/' . clean($erph['fail_lampiran']) ?>" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-arrow-down me-2"></i><?= basename(clean($erph['fail_lampiran'])) ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Signature Line (for print) -->
    <div class="row mt-5 g-4">
        <div class="col-md-6">
            <div class="border-top border-dark pt-2 mt-4">
                <div class="fw-semibold"><?= clean($erph['guru_nama'] ?: '____________________') ?></div>
                <div class="text-muted small"><?= clean($erph['guru_jawatan'] ?: 'Guru') ?></div>
                <div class="text-muted small">Tarikh: <?= formatTarikh($erph['tarikh']) ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border-top border-dark pt-2 mt-4">
                <div class="fw-semibold">____________________</div>
                <div class="text-muted small">Pengetua / Penolong Kanan</div>
                <div class="text-muted small">Tarikh: ___________</div>
            </div>
        </div>
    </div>
</div><!-- end print-page -->

<!-- Action bar (bottom, no-print) -->
<div class="d-flex justify-content-center gap-3 mt-4 no-print">
    <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Cetak
    </button>
    <a href="<?= BASE_URL ?>/export/pdf.php?modul=erph&id=<?= $id ?>" class="btn btn-danger" target="_blank">
        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </a>
    <a href="<?= BASE_URL ?>/modules/erph/edit.php?id=<?= $id ?>" class="btn btn-warning">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <a href="<?= BASE_URL ?>/modules/erph/index.php" class="btn btn-outline-primary">
        <i class="bi bi-list me-1"></i>Senarai ERPH
    </a>
</div>

<?php
$extraScript = '';
include __DIR__ . '/../../includes/footer.php';
?>
