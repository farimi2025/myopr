<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin','super_admin','pentadbir']);

$pageTitle   = 'Arkib Tahunan';
$tahunSemasa = (int)(getSetting('tahun_semasa') ?: TAHUN_SEMASA);

// Handle archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_arkib'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/arkib.php');
    }

    $tahunArkib = (int)($_POST['tahun_arkib'] ?? 0);
    if ($tahunArkib < 2000 || $tahunArkib > 2100) {
        setFlash('danger', 'Tahun arkib tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/arkib.php');
    }

    // Confirmation check
    if (empty($_POST['sahkan_arkib'])) {
        setFlash('warning', 'Sila tanda kotak pengesahan sebelum meneruskan arkib.');
        redirect(BASE_URL . '/modules/pentadbir/arkib.php');
    }

    // Archive kelas for that year
    $kelasDiarkib = dbValue("SELECT COUNT(*) FROM kelas WHERE tahun=? AND status='aktif'", [$tahunArkib]);
    dbQuery("UPDATE kelas SET status='tidak aktif' WHERE tahun=? AND status='aktif'", [$tahunArkib]);

    // Archive guru_kelas_subjek for that year
    $tugasanDiarkib = dbValue("SELECT COUNT(*) FROM guru_kelas_subjek WHERE tahun=? AND status='aktif'", [$tahunArkib]);
    dbQuery("UPDATE guru_kelas_subjek SET status='arkib' WHERE tahun=? AND status='aktif'", [$tahunArkib]);

    logAktiviti('arkib', 'sistem', 0, "Arkib data tahun $tahunArkib: $kelasDiarkib kelas, $tugasanDiarkib tugasan");
    setFlash('success', "Data tahun <strong>$tahunArkib</strong> berjaya diarkibkan. "
        . "$kelasDiarkib kelas dan $tugasanDiarkib tugasan guru-subjek telah diarkibkan.");
    redirect(BASE_URL . '/modules/pentadbir/arkib.php');
}

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pulih_arkib'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/arkib.php');
    }

    $tahunPulih = (int)($_POST['tahun_pulih'] ?? 0);
    if ($tahunPulih < 2000 || $tahunPulih > 2100) {
        setFlash('danger', 'Tahun tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/arkib.php');
    }

    dbQuery("UPDATE kelas SET status='aktif' WHERE tahun=? AND status='tidak aktif'", [$tahunPulih]);
    dbQuery("UPDATE guru_kelas_subjek SET status='aktif' WHERE tahun=? AND status='arkib'", [$tahunPulih]);

    logAktiviti('pulih', 'sistem', 0, "Pulih arkib tahun $tahunPulih");
    setFlash('success', "Data tahun <strong>$tahunPulih</strong> berjaya dipulihkan.");
    redirect(BASE_URL . '/modules/pentadbir/arkib.php');
}

// Get stats for current year
$statsSekarang = [
    'guru'        => (int)dbValue("SELECT COUNT(*) FROM guru WHERE status='aktif'"),
    'pentadbir'   => (int)dbValue("SELECT COUNT(*) FROM pentadbir WHERE status='aktif'"),
    'kelas'       => (int)dbValue("SELECT COUNT(*) FROM kelas WHERE tahun=? AND status='aktif'", [$tahunSemasa]),
    'gks'         => (int)dbValue("SELECT COUNT(*) FROM guru_kelas_subjek WHERE tahun=? AND status='aktif'", [$tahunSemasa]),
    'kurikulum'   => (int)dbValue("SELECT COUNT(*) FROM kurikulum WHERE status='aktif'"),
];

// Get list of years with data
$tahunList = dbFetchAll("
    SELECT tahun,
           COUNT(*) AS bil_kelas,
           SUM(bil_murid) AS bil_murid,
           MAX(status) AS ada_aktif,
           SUM(CASE WHEN status='aktif' THEN 1 ELSE 0 END) AS bil_aktif,
           SUM(CASE WHEN status='tidak aktif' THEN 1 ELSE 0 END) AS bil_arkib
    FROM kelas
    GROUP BY tahun
    ORDER BY tahun DESC
");

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-archive me-2 text-primary"></i>Arkib Tahunan</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Pentadbir</a></li>
                <li class="breadcrumb-item active">Arkib Tahunan</li>
            </ol>
        </nav>
    </div>
</div>

<?= showFlash() ?>

<!-- Stats Current Year -->
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-primary"></i>Statistik Data Tahun <?= $tahunSemasa ?></h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-2">
                <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                    <div class="fs-3 fw-bold text-primary"><?= $statsSekarang['guru'] ?></div>
                    <div class="small text-muted">Guru Aktif</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                    <div class="fs-3 fw-bold text-success"><?= $statsSekarang['pentadbir'] ?></div>
                    <div class="small text-muted">Pentadbir</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                    <div class="fs-3 fw-bold text-info"><?= $statsSekarang['kelas'] ?></div>
                    <div class="small text-muted">Kelas Aktif</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                    <div class="fs-3 fw-bold text-warning"><?= $statsSekarang['gks'] ?></div>
                    <div class="small text-muted">Tugasan Guru</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-center p-3 bg-secondary bg-opacity-10 rounded">
                    <div class="fs-3 fw-bold text-secondary"><?= $statsSekarang['kurikulum'] ?></div>
                    <div class="small text-muted">Rekod DSKP</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Archive Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm border-top border-4 border-danger">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0"><i class="bi bi-archive me-2"></i>Buat Arkib Tahun</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Amaran:</strong> Tindakan arkib akan menukar status semua rekod kelas dan tugasan guru-subjek bagi tahun yang dipilih kepada "arkib". Data masih boleh dilihat tetapi tidak lagi aktif. Anda boleh memulihkan data pada bila-bila masa.
                </div>

                <form method="POST" novalidate>
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Tahun untuk Diarkibkan</label>
                        <select name="tahun_arkib" class="form-select" required>
                            <option value="">-- Pilih Tahun --</option>
                            <?php foreach ($tahunList as $t): ?>
                            <?php if ($t['bil_aktif'] > 0): ?>
                            <option value="<?= $t['tahun'] ?>"><?= $t['tahun'] ?> (<?= $t['bil_aktif'] ?> kelas aktif)</option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sahkan_arkib" value="1" id="sahkanArkib">
                            <label class="form-check-label text-danger fw-semibold" for="sahkanArkib">
                                Saya faham dan mengesahkan tindakan arkib ini.
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="buat_arkib" value="1" class="btn btn-danger w-100">
                        <i class="bi bi-archive me-1"></i>Arkibkan Data Tahun Dipilih
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Years List -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-primary"></i>Senarai Tahun &amp; Status Data</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tahunList)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada data kelas dijumpai.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Tahun</th>
                                <th class="text-center">Jumlah Kelas</th>
                                <th class="text-center">Kelas Aktif</th>
                                <th class="text-center">Kelas Arkib</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tahunList as $t): ?>
                            <tr class="<?= $t['tahun'] == $tahunSemasa ? 'table-primary' : '' ?>">
                                <td class="px-3 fw-bold">
                                    <?= clean($t['tahun']) ?>
                                    <?php if ($t['tahun'] == $tahunSemasa): ?>
                                    <span class="badge bg-primary ms-1">Semasa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $t['bil_kelas'] ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $t['bil_aktif'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $t['bil_arkib'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($t['bil_arkib'] > 0): ?>
                                    <form method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="tahun_pulih" value="<?= $t['tahun'] ?>">
                                        <button type="submit" name="pulih_arkib" value="1"
                                                class="btn btn-sm btn-outline-success btn-pulih"
                                                data-confirm="Pulihkan semua data arkib tahun <?= $t['tahun'] ?>?">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulih
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-check-circle text-success me-1"></i>Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('.btn-pulih').on('click', function(e){
        e.preventDefault();
        const msg  = $(this).data('confirm') || 'Pulihkan data ini?';
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Sahkan Pulihkan',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, pulihkan',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
