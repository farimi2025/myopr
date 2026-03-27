<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Profil Murid';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'ID murid tidak sah.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

$murid = dbFetch(
    "SELECT m.*, k.nama_kelas, k.tingkatan, k.aliran
     FROM murid m
     LEFT JOIN kelas k ON k.id = m.kelas_id
     WHERE m.id = ? LIMIT 1",
    [$id]
);
if (!$murid) {
    setFlash('danger', 'Rekod murid tidak dijumpai.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

// Akademik history
$prestasi = dbFetchAll(
    "SELECT p.*, s.kod AS subjek_kod
     FROM prestasi p
     LEFT JOIN subjek s ON s.id = p.subjek_id
     WHERE p.murid_id = ?
     ORDER BY p.tahun DESC, p.penggal, p.jenis_penilaian, p.nama_subjek",
    [$id]
);

$pageTitle = 'Profil: ' . $murid['nama'];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-circle text-primary me-2"></i>Profil Murid</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/murid/index.php">Murid</a></li>
                <li class="breadcrumb-item active"><?= clean($murid['nama']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="<?= BASE_URL ?>/export/prestasi_pdf.php?murid_id=<?= $id ?>&tahun=<?= $murid['tahun'] ?? TAHUN_SEMASA ?>"
           class="btn btn-outline-danger btn-sm" target="_blank" title="Cetak Slip Markah">
            <i class="bi bi-file-pdf me-1"></i>Slip Markah
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>
        <a href="<?= BASE_URL ?>/modules/murid/edit.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=padam&id=<?= $id ?>&csrf=<?= csrfToken() ?>"
           class="btn btn-outline-danger btn-sm btn-padam" data-nama="<?= clean($murid['nama']) ?>">
            <i class="bi bi-trash me-1"></i>Padam
        </a>
        <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?= showFlash() ?>

<div class="row g-4">
    <!-- ── LEFT COLUMN: Profile Card ────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center mb-4">
            <div class="card-body py-4">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                         style="width:80px;height:80px">
                        <?php if ($murid['jantina'] === 'L'): ?>
                            <i class="bi bi-person-fill text-primary" style="font-size:2.5rem"></i>
                        <?php else: ?>
                            <i class="bi bi-person-fill" style="font-size:2.5rem;color:#e91e8c"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <h5 class="fw-bold mb-1"><?= clean($murid['nama']) ?></h5>
                <div class="mb-2">
                    <?php if ($murid['jantina'] === 'L'): ?>
                        <span class="badge bg-primary"><i class="bi bi-gender-male me-1"></i>Lelaki</span>
                    <?php else: ?>
                        <span class="badge" style="background-color:#e91e8c"><i class="bi bi-gender-female me-1"></i>Perempuan</span>
                    <?php endif; ?>
                    <?= badgeStatus($murid['status']) ?>
                </div>
                <div class="text-muted small">
                    <code><?= clean($murid['no_pendaftaran']) ?></code>
                </div>
            </div>
            <div class="card-footer bg-white py-2">
                <?php if ($murid['kelas_id']): ?>
                <span class="badge bg-light text-dark border fs-6 px-3 py-2">
                    <i class="bi bi-building me-1 text-primary"></i>
                    <?= clean($murid['tingkatan']) ?> <?= clean($murid['nama_kelas']) ?>
                    <?php if ($murid['aliran']): ?>
                        <small class="text-muted">(<?= clean($murid['aliran']) ?>)</small>
                    <?php endif; ?>
                </span>
                <?php else: ?>
                <span class="text-muted small">Tiada kelas ditetapkan</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-info-circle me-2 text-primary"></i>Maklumat Ringkas</h6>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between py-2">
                    <span class="text-muted">No. IC</span>
                    <span class="fw-semibold"><?= $murid['no_ic'] ? clean($murid['no_ic']) : '<span class="text-muted">—</span>' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between py-2">
                    <span class="text-muted">Tarikh Lahir</span>
                    <span class="fw-semibold"><?= $murid['tarikh_lahir'] ? formatTarikh($murid['tarikh_lahir']) : '—' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between py-2">
                    <span class="text-muted">Bangsa</span>
                    <span><?= clean($murid['bangsa'] ?: '—') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between py-2">
                    <span class="text-muted">Agama</span>
                    <span><?= clean($murid['agama'] ?: '—') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between py-2">
                    <span class="text-muted">Tahun</span>
                    <span><?= clean($murid['tahun']) ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- ── RIGHT COLUMN: Details ─────────────────────────────────────────── -->
    <div class="col-md-8">

        <!-- Maklumat Peribadi -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2 text-primary"></i>Maklumat Peribadi</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small d-block">No. Pendaftaran</label>
                        <strong><code><?= clean($murid['no_pendaftaran']) ?></code></strong>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small d-block">No. Kad Pengenalan</label>
                        <strong><?= clean($murid['no_ic'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Nama Penuh</label>
                        <strong><?= clean($murid['nama']) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Jantina</label>
                        <?php if ($murid['jantina'] === 'L'): ?>
                            <span class="badge bg-primary">Lelaki</span>
                        <?php else: ?>
                            <span class="badge" style="background-color:#e91e8c">Perempuan</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Status</label>
                        <?= badgeStatus($murid['status']) ?>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small d-block">Tarikh Lahir</label>
                        <strong><?= $murid['tarikh_lahir'] ? formatTarikh($murid['tarikh_lahir']) : '—' ?></strong>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small d-block">Bangsa</label>
                        <strong><?= clean($murid['bangsa'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small d-block">Agama</label>
                        <strong><?= clean($murid['agama'] ?: '—') ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alamat -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-house-door me-2 text-primary"></i>Alamat</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="text-muted small d-block">Alamat</label>
                        <strong><?= nl2br(clean($murid['alamat'] ?: '—')) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Poskod</label>
                        <strong><?= clean($murid['poskod'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small d-block">Bandar</label>
                        <strong><?= clean($murid['bandar'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-5">
                        <label class="text-muted small d-block">Negeri</label>
                        <strong><?= clean($murid['negeri_murid'] ?: '—') ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ibu Bapa / Penjaga -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Ibu Bapa / Penjaga</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="text-muted small d-block">Nama</label>
                        <strong><?= clean($murid['nama_ibu_bapa'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Hubungan</label>
                        <strong><?= clean($murid['hubungan'] ?: '—') ?></strong>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small d-block">No. Telefon</label>
                        <strong>
                            <?php if ($murid['telefon_ibu_bapa']): ?>
                                <a href="tel:<?= clean($murid['telefon_ibu_bapa']) ?>" class="text-decoration-none">
                                    <i class="bi bi-telephone me-1"></i><?= clean($murid['telefon_ibu_bapa']) ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($murid['catatan']): ?>
        <!-- Catatan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-journal-text me-2 text-primary"></i>Catatan</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(clean($murid['catatan'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── REKOD AKADEMIK ─────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mt-2">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-bar-chart-line me-2 text-primary"></i>Rekod Akademik
            <span class="badge bg-primary ms-1"><?= count($prestasi) ?></span>
        </h6>
        <a href="<?= BASE_URL ?>/modules/prestasi/tambah.php?murid_id=<?= $id ?>"
           class="btn btn-primary btn-sm no-print">
            <i class="bi bi-plus-circle me-1"></i>Tambah Rekod Markah
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($prestasi)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Tiada rekod akademik dijumpai.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table id="prestasiTable" class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Tahun</th>
                        <th>Penggal</th>
                        <th>Subjek</th>
                        <th>Jenis Penilaian</th>
                        <th class="text-center">Markah</th>
                        <th class="text-center">Gred</th>
                        <th class="text-center no-print">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestasi as $i => $p):
                        $gredWarna = constant('GRED_WARNA')[$p['gred']] ?? '#6b7280';
                    ?>
                    <tr>
                        <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                        <td><?= clean($p['tahun']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">Penggal <?= (int)$p['penggal'] ?></span>
                        </td>
                        <td>
                            <?php if ($p['subjek_kod']): ?>
                                <span class="badge bg-light text-dark border me-1"><?= clean($p['subjek_kod']) ?></span>
                            <?php endif; ?>
                            <?= clean($p['nama_subjek']) ?>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                <?= clean($p['jenis_penilaian']) ?>
                            </span>
                        </td>
                        <td class="text-center fw-semibold"><?= number_format((float)$p['markah'], 1) ?></td>
                        <td class="text-center">
                            <span class="badge fw-bold px-2 py-1" style="background-color:<?= $gredWarna ?>;color:#fff">
                                <?= clean($p['gred']) ?>
                            </span>
                        </td>
                        <td class="text-center no-print">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/modules/prestasi/edit.php?id=<?= $p['id'] ?>"
                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/modules/prestasi/aksi.php?action=padam&id=<?= $p['id'] ?>&csrf=<?= csrfToken() ?>&redirect=murid&murid_id=<?= $id ?>"
                                   class="btn btn-outline-danger btn-sm btn-padam-prestasi" title="Padam"
                                   data-nama="<?= clean($p['nama_subjek']) ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
$(function(){
    // DataTable for prestasi
    if ($('#prestasiTable tbody tr').length > 1) {
        $('#prestasiTable').DataTable({
            language: {
                emptyTable: 'Tiada rekod',
                zeroRecords: 'Tiada rekod dijumpai',
            },
            pageLength: 15,
            order: [[1, 'desc'], [2, 'asc']],
            columnDefs: [
                { orderable: false, targets: [7] }
            ],
            dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row align-items-center mt-2"<"col-md-6"i><"col-md-6"p>>',
        });
    }

    // Confirm padam murid
    $('.btn-padam').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({
            title: 'Padam Murid?',
            html: 'Rekod murid <strong>'+nama+'</strong> akan <span class="text-danger fw-bold">dipadamkan kekal</span>.',
            icon: 'error', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Padam'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });

    // Confirm padam prestasi
    $('.btn-padam-prestasi').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({
            title: 'Padam Rekod Markah?',
            html: 'Rekod markah <strong>'+nama+'</strong> akan dipadamkan.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Padam'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });
});
</script>
<style>
@media print {
    .no-print { display: none !important; }
    .sb-topnav, #layoutSidenav_nav, footer, .breadcrumb { display: none !important; }
    #layoutSidenav_content { margin-left: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    body { font-size: 12px; }
}
</style>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
