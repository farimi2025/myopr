<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle   = 'Pengurusan Kelas';
$filterTahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);

// Build list of years for filter
$tahunList = dbFetchAll("SELECT DISTINCT tahun FROM kelas ORDER BY tahun DESC");

$senarai = dbFetchAll("
    SELECT k.id, k.nama_kelas, k.tingkatan, k.aliran, k.bil_murid, k.tahun, k.status,
           g.nama AS nama_guru_kelas
    FROM kelas k
    LEFT JOIN guru g ON g.id = k.guru_kelas_id
    WHERE k.tahun = ?
    ORDER BY k.tingkatan, k.nama_kelas
", [$filterTahun]);

$totalKelas  = count($senarai);
$totalMurid  = array_sum(array_column($senarai, 'bil_murid'));
$totalAktif  = count(array_filter($senarai, fn($k) => $k['status'] === 'aktif'));

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-building me-2 text-primary"></i>Pengurusan Kelas</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Kelas</li>
            </ol>
        </nav>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Tambah Kelas
    </a>
</div>

<?= showFlash() ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-building text-primary fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-primary"><?= $totalKelas ?></div>
                    <div class="text-muted small">Jumlah Kelas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-people text-success fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-success"><?= $totalMurid ?></div>
                    <div class="text-muted small">Jumlah Murid</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="bi bi-check-circle text-info fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-info"><?= $totalAktif ?></div>
                    <div class="text-muted small">Kelas Aktif</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-1"></i>Senarai Kelas Tahun <?= $filterTahun ?></h6>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 small fw-semibold">Tahun:</label>
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($tahunList as $t): ?>
                <option value="<?= $t['tahun'] ?>" <?= $t['tahun'] == $filterTahun ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
                <?php endforeach; ?>
                <?php if (empty($tahunList)): ?>
                <option value="<?= TAHUN_SEMASA ?>" selected><?= TAHUN_SEMASA ?></option>
                <?php endif; ?>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableKelas" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Tingkatan</th>
                        <th>Nama Kelas</th>
                        <th>Aliran</th>
                        <th>Guru Kelas</th>
                        <th class="text-center">Bil. Murid</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($senarai)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada rekod kelas untuk tahun <?= $filterTahun ?>.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($senarai as $i => $k): ?>
                    <tr>
                        <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                        <td><span class="badge bg-primary">Tingkatan <?= clean($k['tingkatan']) ?></span></td>
                        <td class="fw-semibold"><?= clean($k['nama_kelas']) ?></td>
                        <td class="text-muted small"><?= clean($k['aliran'] ?: '-') ?></td>
                        <td>
                            <?php if ($k['nama_guru_kelas']): ?>
                            <i class="bi bi-person-check text-success me-1"></i><?= clean($k['nama_guru_kelas']) ?>
                            <?php else: ?>
                            <span class="text-muted small"><i class="bi bi-dash"></i> Tiada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= (int)$k['bil_murid'] ?></span>
                        </td>
                        <td class="text-center"><?= badgeStatus($k['status']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="guru_subjek.php?kelas_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-info" title="Urus Guru & Subjek">
                                    <i class="bi bi-journals"></i>
                                </a>
                                <a href="edit.php?id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="aksi.php?tindakan=padam&id=<?= $k['id'] ?>"
                                   class="btn btn-sm btn-outline-danger btn-aksi"
                                   data-confirm="Padam kelas <?= clean($k['nama_kelas']) ?>? Semua tugasan guru-subjek juga akan dipadam."
                                   title="Padam">
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
        Jumlah rekod: <strong><?= count($senarai) ?></strong> &mdash; Jumlah murid: <strong><?= $totalMurid ?></strong>
    </div>
</div>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('#tableKelas').DataTable({
        language: {
            search: 'Cari:',
            lengthMenu: 'Papar _MENU_ rekod',
            info: 'Rekod _START_ hingga _END_ daripada _TOTAL_',
            paginate: { previous: 'Sebelum', next: 'Seterusnya' },
            zeroRecords: 'Tiada rekod dijumpai'
        },
        pageLength: 25,
        order: [[1,'asc'],[2,'asc']],
        columnDefs: [{ orderable: false, targets: [0,7] }]
    });

    $('.btn-aksi').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('confirm') || 'Sahkan tindakan ini?';
        Swal.fire({
            title: 'Sahkan Tindakan',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, padam',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
