<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle  = 'Senarai Pentadbir';
$layoutView = clean($_GET['view'] ?? 'card'); // card or table

$senarai = dbFetchAll("SELECT * FROM pentadbir ORDER BY jawatan, nama");

$jawatanWarna = [
    'Guru Besar'                     => 'primary',
    'Pengetua'                       => 'primary',
    'Penolong Kanan 1'               => 'success',
    'Penolong Kanan HEM'             => 'info',
    'Penolong Kanan Kokurikulum'     => 'warning',
    'Penolong Kanan Petang'          => 'secondary',
    'Guru Senior'                    => 'dark',
    'Lain-lain'                      => 'light',
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Senarai Pentadbir</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Pentadbir</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <!-- Layout Toggle -->
        <div class="btn-group btn-group-sm" role="group">
            <a href="?view=card" class="btn <?= $layoutView === 'card' ? 'btn-primary' : 'btn-outline-primary' ?>" title="Paparan Kad">
                <i class="bi bi-grid-3x2-gap"></i>
            </a>
            <a href="?view=table" class="btn <?= $layoutView === 'table' ? 'btn-primary' : 'btn-outline-primary' ?>" title="Paparan Jadual">
                <i class="bi bi-table"></i>
            </a>
        </div>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Tambah Pentadbir
        </a>
    </div>
</div>

<?= showFlash() ?>

<?php if ($layoutView === 'card'): ?>
<!-- Card View -->
<div class="row g-4">
    <?php if (empty($senarai)): ?>
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada rekod pentadbir dijumpai.
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($senarai as $p): ?>
    <?php $warna = $jawatanWarna[$p['jawatan']] ?? 'secondary'; ?>
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center pb-0">
                <div class="position-relative d-inline-block mb-3">
                    <img src="<?= gambarUrl($p['foto']) ?>"
                         class="rounded-circle border border-3 border-<?= $warna ?>"
                         style="width:90px;height:90px;object-fit:cover;" alt="<?= clean($p['nama']) ?>">
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-<?= $p['status'] === 'aktif' ? 'success' : 'secondary' ?>"
                          style="font-size:0.65rem;">
                        <?= $p['status'] === 'aktif' ? 'Aktif' : 'Arkib' ?>
                    </span>
                </div>
                <h6 class="fw-bold mb-1"><?= clean($p['nama']) ?></h6>
                <span class="badge bg-<?= $warna ?> mb-2"><?= clean($p['jawatan']) ?></span>
                <div class="text-muted small mb-1">
                    <?php if ($p['gred']): ?>
                    <span class="badge bg-light text-dark border"><?= clean($p['gred']) ?></span>
                    <?php endif; ?>
                    <?php if ($p['no_pekerja']): ?>
                    <span class="ms-1"><?= clean($p['no_pekerja']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-light mt-2">
                <div class="small text-center mb-2">
                    <?php if ($p['telefon']): ?>
                    <div><i class="bi bi-phone me-1 text-muted"></i><?= clean($p['telefon']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['email']): ?>
                    <div><i class="bi bi-envelope me-1 text-muted"></i><?= clean($p['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-center gap-1">
                    <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php if ($p['status'] === 'aktif'): ?>
                    <a href="aksi.php?tindakan=arkib&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary btn-aksi"
                       data-confirm="Arkibkan pentadbir <?= clean($p['nama']) ?>?" title="Arkib">
                        <i class="bi bi-archive"></i>
                    </a>
                    <?php else: ?>
                    <a href="aksi.php?tindakan=pulih&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success btn-aksi"
                       data-confirm="Pulihkan pentadbir <?= clean($p['nama']) ?>?" title="Pulih">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                    <?php endif; ?>
                    <a href="aksi.php?tindakan=padam&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-aksi"
                       data-confirm="Padam pentadbir <?= clean($p['nama']) ?>?" title="Padam">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Table View -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-1"></i>Rekod Pentadbir</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablePentadbir" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Nama</th>
                        <th>Jawatan</th>
                        <th>Gred</th>
                        <th>No. Pekerja</th>
                        <th>Telefon</th>
                        <th>Emel</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($senarai)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada rekod.</td></tr>
                    <?php else: ?>
                    <?php foreach ($senarai as $i => $p): ?>
                    <tr>
                        <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= gambarUrl($p['foto']) ?>"
                                     class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
                                <span class="fw-semibold"><?= clean($p['nama']) ?></span>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= $jawatanWarna[$p['jawatan']] ?? 'secondary' ?>"><?= clean($p['jawatan']) ?></span></td>
                        <td><?= clean($p['gred'] ?: '-') ?></td>
                        <td class="text-muted small"><?= clean($p['no_pekerja'] ?: '-') ?></td>
                        <td class="text-muted small"><?= clean($p['telefon'] ?: '-') ?></td>
                        <td class="text-muted small"><?= clean($p['email'] ?: '-') ?></td>
                        <td class="text-center"><?= badgeStatus($p['status']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($p['status'] === 'aktif'): ?>
                                <a href="aksi.php?tindakan=arkib&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary btn-aksi"
                                   data-confirm="Arkibkan pentadbir <?= clean($p['nama']) ?>?">
                                    <i class="bi bi-archive"></i>
                                </a>
                                <?php else: ?>
                                <a href="aksi.php?tindakan=pulih&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success btn-aksi"
                                   data-confirm="Pulihkan pentadbir <?= clean($p['nama']) ?>?">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                                <?php endif; ?>
                                <a href="aksi.php?tindakan=padam&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-aksi"
                                   data-confirm="Padam pentadbir <?= clean($p['nama']) ?>?">
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
    <div class="card-footer bg-white small text-muted">Jumlah rekod: <strong><?= count($senarai) ?></strong></div>
</div>
<?php endif; ?>

<?php $extraScript = <<<JS
<script>
$(function(){
    if ($('#tablePentadbir').length) {
        $('#tablePentadbir').DataTable({
            language: { search: 'Cari:', zeroRecords: 'Tiada rekod',
                        info: 'Rekod _START_ - _END_ daripada _TOTAL_',
                        paginate: { previous: 'Sebelum', next: 'Seterusnya' } },
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: [0,8] }]
        });
    }
    $('.btn-aksi').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('confirm') || 'Sahkan tindakan ini?';
        Swal.fire({
            title: 'Sahkan Tindakan', text: msg, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, teruskan',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
