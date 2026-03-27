<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'ID guru tidak sah.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

$guru = dbFetch("SELECT * FROM guru WHERE id=?", [$id]);
if (!$guru) {
    setFlash('danger', 'Rekod guru tidak dijumpai.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

// Get kelas & subjek assignments
$tugasan = dbFetchAll("
    SELECT gks.id, gks.nama_subjek, gks.waktu_seminggu, gks.tahun, gks.status,
           k.nama_kelas, k.tingkatan, k.aliran,
           s.kod AS subjek_kod
    FROM guru_kelas_subjek gks
    JOIN kelas k ON k.id = gks.kelas_id
    LEFT JOIN subjek s ON s.id = gks.subjek_id
    WHERE gks.guru_id = ?
    ORDER BY gks.tahun DESC, k.tingkatan, k.nama_kelas
", [$id]);

// Count stats
$jumlahKelas   = count(array_unique(array_column($tugasan, 'kelas_id') ?: []));
$jumlahWaktu   = array_sum(array_column($tugasan, 'waktu_seminggu'));
$jumlahSubjek  = count(array_unique(array_column($tugasan, 'nama_subjek') ?: []));

$pageTitle = 'Profil Guru - ' . $guru['nama'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-circle me-2 text-info"></i>Profil Guru</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Guru</a></li>
                <li class="breadcrumb-item active"><?= clean($guru['nama']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?= showFlash() ?>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-4">
                <img src="<?= gambarUrl($guru['foto']) ?>"
                     class="rounded-circle mb-3 border border-3 border-primary"
                     style="width:130px;height:130px;object-fit:cover;" alt="Foto <?= clean($guru['nama']) ?>">
                <h5 class="fw-bold mb-1"><?= clean($guru['nama']) ?></h5>
                <div class="text-muted mb-2"><?= clean($guru['jawatan'] ?: 'Guru') ?></div>
                <div class="mb-3"><?= badgeStatus($guru['status']) ?></div>
                <?php if ($guru['gred']): ?>
                <span class="badge bg-info text-dark fs-6 px-3"><?= clean($guru['gred']) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light">
                <div class="row text-center g-0">
                    <div class="col-4 border-end">
                        <div class="fw-bold text-primary"><?= count($tugasan) ?></div>
                        <div class="small text-muted">Tugasan</div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="fw-bold text-success"><?= $jumlahWaktu ?></div>
                        <div class="small text-muted">Waktu/Mng</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-warning"><?= $jumlahSubjek ?></div>
                        <div class="small text-muted">Subjek</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-telephone me-2 text-primary"></i>Hubungan</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-phone me-1"></i>Telefon</span>
                    <span class="fw-semibold small"><?= clean($guru['telefon'] ?: '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-envelope me-1"></i>Emel</span>
                    <span class="fw-semibold small"><?= clean($guru['email'] ?: '-') ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Details -->
    <div class="col-lg-8">
        <!-- Personal Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Maklumat Peribadi</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">No. Kad Pengenalan</label>
                        <div class="fw-semibold"><?= clean($guru['no_ic'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">No. Pekerja</label>
                        <div class="fw-semibold"><?= clean($guru['no_pekerja'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Tarikh Lahir</label>
                        <div class="fw-semibold"><?= $guru['tarikh_lahir'] ? formatTarikh($guru['tarikh_lahir']) : '-' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Jantina</label>
                        <div class="fw-semibold"><?= $guru['jantina'] === 'L' ? 'Lelaki' : 'Perempuan' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Bangsa</label>
                        <div class="fw-semibold"><?= clean($guru['bangsa'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Agama</label>
                        <div class="fw-semibold"><?= clean($guru['agama'] ?: '-') ?></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Alamat</label>
                        <div class="fw-semibold"><?= nl2br(clean($guru['alamat'] ?: '-')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perkhidmatan Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="bi bi-briefcase-fill me-2"></i>Maklumat Perkhidmatan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Jawatan</label>
                        <div class="fw-semibold"><?= clean($guru['jawatan'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Gred</label>
                        <div class="fw-semibold"><?= clean($guru['gred'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Opsyen</label>
                        <div class="fw-semibold"><?= clean($guru['opsyen'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Subjek Utama</label>
                        <div class="fw-semibold"><?= clean($guru['subjek_utama'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($guru['catatan']): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(clean($guru['catatan'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tugasan Kelas & Subjek -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-journals me-2"></i>Tugasan Kelas & Subjek</h6>
                <span class="badge bg-dark"><?= count($tugasan) ?> rekod</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tugasan)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada tugasan kelas/subjek ditetapkan.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">#</th>
                                <th>Kelas</th>
                                <th>Tingkatan</th>
                                <th>Subjek</th>
                                <th class="text-center">Waktu/Minggu</th>
                                <th class="text-center">Tahun</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tugasan as $i => $t): ?>
                            <tr>
                                <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= clean($t['nama_kelas']) ?></td>
                                <td><?= clean($t['tingkatan']) ?></td>
                                <td>
                                    <?php if ($t['subjek_kod']): ?>
                                    <span class="badge bg-light text-dark border me-1"><?= clean($t['subjek_kod']) ?></span>
                                    <?php endif; ?>
                                    <?= clean($t['nama_subjek']) ?>
                                </td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?= $t['waktu_seminggu'] ?></span></td>
                                <td class="text-center"><?= clean($t['tahun']) ?></td>
                                <td class="text-center"><?= badgeStatus($t['status'] ?? 'aktif') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold px-3">Jumlah Waktu:</td>
                                <td class="text-center fw-bold text-primary"><?= $jumlahWaktu ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $extraScript = <<<JS
<style>
@media print {
    .no-print { display: none !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
