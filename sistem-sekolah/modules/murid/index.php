<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Senarai Murid';

// ── Filter parameters ────────────────────────────────────────────────────────
$cari       = clean($_GET['cari']    ?? '');
$kelasId    = (int)($_GET['kelas_id'] ?? 0);
$jantina    = clean($_GET['jantina']  ?? '');
$status     = clean($_GET['status']   ?? 'aktif');
$tahunCari  = (int)($_GET['tahun']    ?? TAHUN_SEMASA);

// ── Build query ──────────────────────────────────────────────────────────────
$where  = ['m.tahun = ?'];
$params = [$tahunCari];

if ($cari !== '') {
    $where[]  = '(m.nama LIKE ? OR m.no_pendaftaran LIKE ? OR m.no_ic LIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
if ($kelasId > 0) {
    $where[]  = 'm.kelas_id = ?';
    $params[] = $kelasId;
}
if ($jantina !== '') {
    $where[]  = 'm.jantina = ?';
    $params[] = $jantina;
}
if ($status !== '') {
    $where[]  = 'm.status = ?';
    $params[] = $status;
} else {
    // Tunjuk semua kecuali yang tidak relevan
    $where[]  = "m.status IN ('aktif','arkib','berpindah','tamat')";
}

$whereStr = 'WHERE ' . implode(' AND ', $where);

// ── Stats ────────────────────────────────────────────────────────────────────
$statParams = [$tahunCari];
$statRow    = dbFetch("SELECT
    COUNT(*) AS jumlah,
    SUM(jantina='L') AS lelaki,
    SUM(jantina='P') AS perempuan,
    SUM(status='arkib') AS arkib,
    SUM(status='aktif') AS aktif_count
    FROM murid WHERE tahun=?", $statParams);

// ── Fetch students ───────────────────────────────────────────────────────────
$sql = "SELECT m.id, m.no_pendaftaran, m.nama, m.no_ic, m.jantina, m.tarikh_lahir,
               m.status, m.kelas_id,
               k.nama_kelas, k.tingkatan
        FROM murid m
        LEFT JOIN kelas k ON k.id = m.kelas_id
        $whereStr
        ORDER BY k.tingkatan, k.nama_kelas, m.nama";

$muridList = dbFetchAll($sql, $params);

// ── Dropdown data ────────────────────────────────────────────────────────────
$senaraKelas = dbFetchAll(
    "SELECT id, CONCAT(tingkatan,' ',nama_kelas) AS label FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [$tahunCari]
);

$tahunList = dbFetchAll("SELECT DISTINCT tahun FROM murid ORDER BY tahun DESC");

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Senarai Murid</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Murid</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/export/murid_pdf.php?<?= http_build_query(['tahun'=>$tahunCari,'kelas_id'=>$kelasId,'jantina'=>$jantina,'status'=>$status,'cari'=>$cari]) ?>"
           class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>Export PDF
        </a>
        <a href="<?= BASE_URL ?>/export/murid_excel.php?<?= http_build_query(['tahun'=>$tahunCari,'kelas_id'=>$kelasId,'jantina'=>$jantina,'status'=>$status,'cari'=>$cari]) ?>"
           class="btn btn-outline-success btn-sm" target="_blank">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <a href="<?= BASE_URL ?>/export/kad_murid.php?<?= http_build_query(['kelas_id'=>$kelasId,'tahun'=>$tahunCari]) ?>"
           class="btn btn-outline-primary btn-sm" target="_blank" title="Cetak Kad Murid">
            <i class="bi bi-person-badge me-1"></i>Kad Murid
        </a>
        <a href="<?= BASE_URL ?>/modules/murid/import.php" class="btn btn-outline-info btn-sm">
            <i class="bi bi-upload me-1"></i>Import CSV
        </a>
        <a href="<?= BASE_URL ?>/modules/murid/tambah.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Tambah Murid
        </a>
    </div>
</div>

<?= showFlash() ?>

<!-- Stats Bar -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-flex">
                    <i class="bi bi-people-fill fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Jumlah Murid</div>
                    <div class="fw-bold fs-4"><?= number_format((int)($statRow['jumlah'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 d-flex">
                    <i class="bi bi-gender-male fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Lelaki</div>
                    <div class="fw-bold fs-4"><?= number_format((int)($statRow['lelaki'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-flex">
                    <i class="bi bi-gender-female fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Perempuan</div>
                    <div class="fw-bold fs-4"><?= number_format((int)($statRow['perempuan'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3 d-flex">
                    <i class="bi bi-archive-fill fs-4 text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small">Arkib</div>
                    <div class="fw-bold fs-4"><?= number_format((int)($statRow['arkib'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Carian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="cari" class="form-control" placeholder="Nama / No. Pendaftaran / IC" value="<?= clean($cari) ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php foreach ($tahunList as $t): ?>
                        <option value="<?= $t['tahun'] ?>" <?= $t['tahun'] == $tahunCari ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($tahunList)): ?>
                        <option value="<?= TAHUN_SEMASA ?>"><?= TAHUN_SEMASA ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Kelas</label>
                <select name="kelas_id" class="form-select form-select-sm">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($senaraKelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasId ? 'selected' : '' ?>>
                            <?= clean($k['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Jantina</label>
                <select name="jantina" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <option value="L" <?= $jantina === 'L' ? 'selected' : '' ?>>Lelaki</option>
                    <option value="P" <?= $jantina === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <option value="aktif"     <?= $status === 'aktif'     ? 'selected' : '' ?>>Aktif</option>
                    <option value="arkib"     <?= $status === 'arkib'     ? 'selected' : '' ?>>Arkib</option>
                    <option value="berpindah" <?= $status === 'berpindah' ? 'selected' : '' ?>>Berpindah</option>
                    <option value="tamat"     <?= $status === 'tamat'     ? 'selected' : '' ?>>Tamat</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary btn-sm flex-fill">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>
            Rekod Murid
            <span class="badge bg-primary ms-2"><?= count($muridList) ?></span>
        </h6>
        <span class="text-muted small">Tahun <?= $tahunCari ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="muridTable" class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:40px">#</th>
                        <th>No. Pendaftaran</th>
                        <th>Nama Murid</th>
                        <th>Jantina</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th class="text-center" style="width:160px">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($muridList)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Tiada rekod murid dijumpai.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($muridList as $i => $m): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <code class="small"><?= clean($m['no_pendaftaran']) ?></code>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $m['id'] ?>"
                                       class="fw-semibold text-decoration-none">
                                        <?= clean($m['nama']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($m['jantina'] === 'L'): ?>
                                        <span class="badge bg-primary"><i class="bi bi-gender-male me-1"></i>Lelaki</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color:#e91e8c;"><i class="bi bi-gender-female me-1"></i>Perempuan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($m['kelas_id']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= clean($m['tingkatan']) ?> <?= clean($m['nama_kelas']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= badgeStatus($m['status']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $m['id'] ?>"
                                           class="btn btn-outline-info" title="Lihat Profil">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/murid/edit.php?id=<?= $m['id'] ?>"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($m['status'] === 'aktif'): ?>
                                            <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=arkib&id=<?= $m['id'] ?>&csrf=<?= csrfToken() ?>"
                                               class="btn btn-outline-secondary btn-arkib" title="Arkib"
                                               data-nama="<?= clean($m['nama']) ?>">
                                                <i class="bi bi-archive"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=aktif&id=<?= $m['id'] ?>&csrf=<?= csrfToken() ?>"
                                               class="btn btn-outline-success btn-aktif" title="Aktifkan"
                                               data-nama="<?= clean($m['nama']) ?>">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=padam&id=<?= $m['id'] ?>&csrf=<?= csrfToken() ?>"
                                           class="btn btn-outline-danger btn-padam" title="Padam"
                                           data-nama="<?= clean($m['nama']) ?>">
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
    <?php if (!empty($muridList)): ?>
    <div class="card-footer bg-white text-muted small py-2 px-3">
        Menunjukkan <?= count($muridList) ?> rekod &mdash; Tahun <?= $tahunCari ?>
    </div>
    <?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
<script>
$(function () {
    // DataTable
    $('#muridTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ms.json',
            emptyTable: 'Tiada rekod',
            zeroRecords: 'Tiada rekod dijumpai',
        },
        pageLength: 25,
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] },
            { searchable: false, targets: [0, 3, 5, 6] }
        ],
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row align-items-center mt-2"<"col-md-6"i><"col-md-6"p>>',
    });

    // Confirm arkib
    $(document).on('click', '.btn-arkib', function(e){
        e.preventDefault();
        const url  = $(this).attr('href');
        const nama = $(this).data('nama');
        Swal.fire({
            title: 'Arkib Murid?',
            html: `Murid <strong>${nama}</strong> akan ditetapkan sebagai arkib.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Arkib',
        }).then(r => { if (r.isConfirmed) window.location = url; });
    });

    // Confirm aktif
    $(document).on('click', '.btn-aktif', function(e){
        e.preventDefault();
        const url  = $(this).attr('href');
        const nama = $(this).data('nama');
        Swal.fire({
            title: 'Aktifkan Murid?',
            html: `Murid <strong>${nama}</strong> akan diaktifkan semula.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Aktifkan',
        }).then(r => { if (r.isConfirmed) window.location = url; });
    });

    // Confirm padam
    $(document).on('click', '.btn-padam', function(e){
        e.preventDefault();
        const url  = $(this).attr('href');
        const nama = $(this).data('nama');
        Swal.fire({
            title: 'Padam Murid?',
            html: `Rekod murid <strong>${nama}</strong> akan <span class="text-danger fw-bold">dipadamkan kekal</span>. Tindakan ini tidak boleh dibatalkan.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Padam',
        }).then(r => { if (r.isConfirmed) window.location = url; });
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
