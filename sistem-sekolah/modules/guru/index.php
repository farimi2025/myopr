<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Senarai Guru';

$filterStatus = clean($_GET['status'] ?? 'aktif');

// Stats
$totalGuru    = (int)dbValue("SELECT COUNT(*) FROM guru WHERE status != 'berhenti' AND status != 'bersara'");
$totalLelaki  = (int)dbValue("SELECT COUNT(*) FROM guru WHERE jantina='L' AND status='aktif'");
$totalPerempuan = (int)dbValue("SELECT COUNT(*) FROM guru WHERE jantina='P' AND status='aktif'");
$totalAktif   = (int)dbValue("SELECT COUNT(*) FROM guru WHERE status='aktif'");

// Build query based on filter
if ($filterStatus === 'semua') {
    $senarai = dbFetchAll("SELECT id, nama, no_pekerja, jawatan, gred, opsyen, subjek_utama, telefon, jantina, status
        FROM guru ORDER BY nama");
} else {
    $senarai = dbFetchAll("SELECT id, nama, no_pekerja, jawatan, gred, opsyen, subjek_utama, telefon, jantina, status
        FROM guru WHERE status=? ORDER BY nama", [$filterStatus]);
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-badge me-2 text-primary"></i>Senarai Guru</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Guru</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/guru/tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Tambah Guru
    </a>
</div>

<?= showFlash() ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-people-fill text-primary fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-primary"><?= $totalAktif ?></div>
                    <div class="text-muted small">Guru Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="bi bi-gender-male text-info fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-info"><?= $totalLelaki ?></div>
                    <div class="text-muted small">Guru Lelaki</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                    <i class="bi bi-gender-female text-danger fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-danger"><?= $totalPerempuan ?></div>
                    <div class="text-muted small">Guru Perempuan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-archive text-secondary fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-secondary"><?= $totalGuru ?></div>
                    <div class="text-muted small">Jumlah Guru</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-1"></i>Rekod Guru</h6>
        <div class="d-flex gap-2">
            <a href="?status=aktif" class="btn btn-sm <?= $filterStatus === 'aktif' ? 'btn-success' : 'btn-outline-success' ?>">
                <i class="bi bi-check-circle me-1"></i>Aktif
            </a>
            <a href="?status=arkib" class="btn btn-sm <?= $filterStatus === 'arkib' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-archive me-1"></i>Arkib
            </a>
            <a href="?status=semua" class="btn btn-sm <?= $filterStatus === 'semua' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="bi bi-list me-1"></i>Semua
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableGuru" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Nama Guru</th>
                        <th>No. Pekerja</th>
                        <th>Jawatan</th>
                        <th>Gred</th>
                        <th>Opsyen / Subjek Utama</th>
                        <th>Telefon</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($senarai)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada rekod guru dijumpai.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($senarai as $i => $g): ?>
                    <tr>
                        <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                     style="width:34px;height:34px;font-size:.8rem;flex-shrink:0">
                                    <?= strtoupper(substr($g['nama'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= clean($g['nama']) ?></div>
                                    <div class="text-muted small"><?= $g['jantina'] === 'L' ? 'Lelaki' : 'Perempuan' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?= clean($g['no_pekerja'] ?: '-') ?></td>
                        <td><?= clean($g['jawatan'] ?: '-') ?></td>
                        <td><span class="badge bg-info text-dark"><?= clean($g['gred'] ?: '-') ?></span></td>
                        <td class="text-muted small"><?= clean($g['opsyen'] ?: $g['subjek_utama'] ?: '-') ?></td>
                        <td class="text-muted small"><?= clean($g['telefon'] ?: '-') ?></td>
                        <td class="text-center"><?= badgeStatus($g['status']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="lihat.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-info" title="Lihat Profil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($g['status'] === 'aktif'): ?>
                                <a href="aksi.php?tindakan=arkib&id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary btn-aksi"
                                   data-confirm="Arkibkan guru <?= clean($g['nama']) ?>?" title="Arkib">
                                    <i class="bi bi-archive"></i>
                                </a>
                                <?php else: ?>
                                <a href="aksi.php?tindakan=pulih&id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-success btn-aksi"
                                   data-confirm="Pulihkan guru <?= clean($g['nama']) ?>?" title="Pulih">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                                <?php endif; ?>
                                <a href="aksi.php?tindakan=padam&id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-danger btn-aksi"
                                   data-confirm="Padam guru <?= clean($g['nama']) ?>? Tindakan ini tidak boleh dibatalkan." title="Padam">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white small text-muted">
        Jumlah rekod: <strong><?= count($senarai) ?></strong>
    </div>
</div>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('#tableGuru').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ms.json',
            search: 'Cari:',
            lengthMenu: 'Papar _MENU_ rekod',
            info: 'Rekod _START_ hingga _END_ daripada _TOTAL_',
            paginate: { previous: 'Sebelum', next: 'Seterusnya' },
            zeroRecords: 'Tiada rekod dijumpai'
        },
        pageLength: 25,
        order: [[1,'asc']],
        columnDefs: [
            { orderable: false, targets: [0,8] },
            { searchable: false, targets: [0,7,8] }
        ]
    });

    // Confirm before action
    $('.btn-aksi').on('click', function(e){
        e.preventDefault();
        const url  = $(this).attr('href');
        const msg  = $(this).data('confirm') || 'Sahkan tindakan ini?';
        Swal.fire({
            title: 'Sahkan Tindakan',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, teruskan',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
