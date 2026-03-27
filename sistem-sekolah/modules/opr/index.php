<?php
// ============================================================
// OPR - SENARAI ONE PAGE REPORT AKTIVITI
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Senarai OPR';

// ---- Parameter ----
$tahun    = (int)($_GET['tahun']    ?? TAHUN_SEMASA);
$kategori = clean($_GET['kategori'] ?? '');
$status   = clean($_GET['status']   ?? '');
$carian   = clean($_GET['carian']   ?? '');
$view     = clean($_GET['view']     ?? 'grid'); // grid | table
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = ($view === 'grid') ? 12 : ROWS_PER_PAGE;

// ---- WHERE ----
$where  = ['o.tahun = ?'];
$params = [$tahun];
if ($kategori !== '') { $where[] = 'o.kategori = ?';   $params[] = $kategori; }
if ($status   !== '') { $where[] = 'o.status = ?';     $params[] = $status; }
if ($carian   !== '') {
    $where[]  = '(o.tajuk LIKE ? OR o.penerangan_umum LIKE ? OR o.tempat LIKE ?)';
    $params[] = "%$carian%"; $params[] = "%$carian%"; $params[] = "%$carian%";
}

// Guru biasa hanya lihat rekod sendiri
if (!hasRole(['admin','pengetua','penolong_kanan'])) {
    $where[] = 'o.guru_id = ?';
    $params[] = $_SESSION['user_id'];
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ---- Stats (based on tahun only, not filtered) ----
$statAll   = (int)dbValue("SELECT COUNT(*) FROM opr WHERE tahun=?", [$tahun]);
$statDraf  = (int)dbValue("SELECT COUNT(*) FROM opr WHERE tahun=? AND status='draf'",  [$tahun]);
$statAktif = (int)dbValue("SELECT COUNT(*) FROM opr WHERE tahun=? AND status='aktif'", [$tahun]);
$statArkib = (int)dbValue("SELECT COUNT(*) FROM opr WHERE tahun=? AND status='arkib'", [$tahun]);

// ---- Data ----
$total  = (int)dbValue("SELECT COUNT(*) FROM opr o $whereSql", $params);
$offset = ($page - 1) * $perPage;
$paramsLimit = array_merge($params, [$perPage, $offset]);
$senarai = dbFetchAll(
    "SELECT o.*, g.nama AS guru_nama
     FROM opr o
     LEFT JOIN guru g ON g.id = o.guru_id
     $whereSql
     ORDER BY o.tarikh_aktiviti DESC, o.id DESC
     LIMIT ? OFFSET ?",
    $paramsLimit
);

$kategoriList = ['Akademik','Ko-Kurikulum','Sukan','Kebajikan','Pembangunan Staf','Ibu Bapa','Lain-lain'];

$baseUrl = BASE_URL . '/modules/opr/index.php?tahun=' . $tahun
    . '&kategori=' . urlencode($kategori)
    . '&status='   . urlencode($status)
    . '&carian='   . urlencode($carian)
    . '&view='     . $view;

function badgeKategoriOpr(string $k): string {
    $map = [
        'Akademik'         => 'primary',
        'Ko-Kurikulum'     => 'success',
        'Sukan'            => 'info',
        'Kebajikan'        => 'warning',
        'Pembangunan Staf' => 'secondary',
        'Ibu Bapa'         => 'danger',
        'Lain-lain'        => 'dark',
    ];
    $cls = $map[$k] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . clean($k) . '</span>';
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-richtext me-2 text-success"></i>One Page Report (OPR)</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
            <li class="breadcrumb-item active">OPR</li>
        </ol></nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/opr/tambah.php" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i>Tambah OPR Baru
    </a>
</div>

<?= showFlash() ?>

<!-- STATS -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                    <i class="bi bi-file-earmark-richtext fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="small text-muted">Jumlah OPR</div>
                    <div class="fw-bold fs-4 text-primary"><?= $statAll ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                    <i class="bi bi-pencil-square fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="small text-muted">Draf</div>
                    <div class="fw-bold fs-4 text-warning"><?= $statDraf ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-2">
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                </div>
                <div>
                    <div class="small text-muted">Aktif</div>
                    <div class="fw-bold fs-4 text-success"><?= $statAktif ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-2">
                    <i class="bi bi-archive fs-4 text-secondary"></i>
                </div>
                <div>
                    <div class="small text-muted">Arkib</div>
                    <div class="fw-bold fs-4 text-secondary"><?= $statArkib ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="view" value="<?= clean($view) ?>">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php for ($y = (int)TAHUN_SEMASA + 1; $y >= (int)TAHUN_SEMASA - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <option value="draf"  <?= $status === 'draf'  ? 'selected' : '' ?>>Draf</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="arkib" <?= $status === 'arkib' ? 'selected' : '' ?>>Arkib</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Cari Tajuk</label>
                <input type="search" name="carian" class="form-control form-control-sm"
                       placeholder="Tajuk, tempat..." value="<?= clean($carian) ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-1 align-items-end">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                <a href="<?= BASE_URL ?>/modules/opr/index.php?view=<?= $view ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                <div class="btn-group btn-group-sm ms-auto">
                    <a href="?<?= http_build_query(array_merge($_GET, ['view'=>'grid'])) ?>"
                       class="btn <?= $view === 'grid' ? 'btn-primary' : 'btn-outline-primary' ?>" title="Grid">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['view'=>'table'])) ?>"
                       class="btn <?= $view === 'table' ? 'btn-primary' : 'btn-outline-primary' ?>" title="Jadual">
                        <i class="bi bi-table"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($senarai)): ?>

<div class="text-center py-5 text-muted">
    <i class="bi bi-file-earmark-x fs-1 d-block mb-3 opacity-50"></i>
    <p class="mb-2 fs-5">Tiada rekod OPR dijumpai.</p>
    <?php if ($carian || $kategori || $status): ?>
        <a href="<?= BASE_URL ?>/modules/opr/index.php?tahun=<?= $tahun ?>&view=<?= $view ?>" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-x-lg me-1"></i>Padam Filter
        </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/modules/opr/tambah.php" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Tambah OPR Pertama
    </a>
</div>

<?php elseif ($view === 'grid'): ?>

<!-- ================================================================ -->
<!-- GRID / CARD VIEW -->
<!-- ================================================================ -->
<div class="row g-3 mb-3">
    <?php foreach ($senarai as $row): ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="transition:transform .2s ease,box-shadow .2s ease">
            <!-- Gambar Thumbnail -->
            <div class="position-relative overflow-hidden" style="height:185px;background:#f8f9fa">
                <?php if ($row['gambar1'] && file_exists(ROOT_PATH . '/' . $row['gambar1'])): ?>
                    <img src="<?= BASE_URL . '/' . clean($row['gambar1']) ?>"
                         alt="<?= clean($row['tajuk']) ?>"
                         class="w-100 h-100"
                         style="object-fit:cover;transition:transform .3s ease">
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-image fs-1 mb-1 opacity-25"></i>
                        <small class="opacity-50">Tiada Gambar</small>
                    </div>
                <?php endif; ?>
                <!-- Kategori badge top-left -->
                <div class="position-absolute top-0 start-0 p-2">
                    <?= badgeKategoriOpr($row['kategori']) ?>
                </div>
                <!-- Status badge top-right -->
                <div class="position-absolute top-0 end-0 p-2">
                    <?= badgeStatus($row['status']) ?>
                </div>
                <!-- Photo count bottom-right -->
                <?php
                $nGambar = (bool)$row['gambar1'] + (bool)$row['gambar2'] + (bool)$row['gambar3'];
                if ($nGambar > 0):
                ?>
                <div class="position-absolute bottom-0 end-0 p-1">
                    <span class="badge bg-dark bg-opacity-75">
                        <i class="bi bi-images me-1"></i><?= $nGambar ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="card-body pb-2 pt-3">
                <h6 class="card-title fw-bold mb-1 lh-sm" style="font-size:.925rem">
                    <a href="<?= BASE_URL ?>/modules/opr/lihat.php?id=<?= $row['id'] ?>"
                       class="text-decoration-none text-dark">
                        <?= clean($row['tajuk']) ?>
                    </a>
                </h6>
                <div class="small text-muted mb-1">
                    <i class="bi bi-calendar3 me-1"></i><?= formatTarikh($row['tarikh_aktiviti']) ?>
                    <?php if ($row['tempat']): ?>
                    <span class="mx-1">&bull;</span>
                    <i class="bi bi-geo-alt me-1"></i><?= clean($row['tempat']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($row['guru_nama']): ?>
                <div class="small text-muted">
                    <i class="bi bi-person me-1"></i><?= clean($row['guru_nama']) ?>
                </div>
                <?php endif; ?>
                <?php if ($row['penerangan_umum']): ?>
                <p class="small text-muted mt-2 mb-0" style="
                    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                    <?= clean($row['penerangan_umum']) ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="card-footer bg-white border-top pt-2 pb-2">
                <div class="d-flex justify-content-between align-items-center gap-1">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= BASE_URL ?>/modules/opr/lihat.php?id=<?= $row['id'] ?>"
                           class="btn btn-outline-success" title="Lihat">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/modules/opr/edit.php?id=<?= $row['id'] ?>"
                           class="btn btn-outline-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/export/pdf.php?modul=opr&id=<?= $row['id'] ?>"
                           class="btn btn-outline-danger" title="Export PDF" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-padam"
                                data-id="<?= $row['id'] ?>"
                                data-tajuk="<?= jsEscape($row['tajuk']) ?>" title="Padam">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                    <?php if ($row['status'] !== 'arkib'): ?>
                    <a href="<?= BASE_URL ?>/modules/opr/aksi.php?tindakan=arkib&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                       class="btn btn-sm btn-outline-secondary" title="Arkib"
                       onclick="return confirm('Arkibkan OPR ini?')">
                        <i class="bi bi-archive"></i>
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/modules/opr/aksi.php?tindakan=pulih&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                       class="btn btn-sm btn-outline-success" title="Pulih"
                       onclick="return confirm('Pulihkan OPR ini?')">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<!-- ================================================================ -->
<!-- TABLE VIEW -->
<!-- ================================================================ -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Gambar</th>
                        <th>No. Rujukan</th>
                        <th>Tarikh</th>
                        <th>Tajuk</th>
                        <th>Kategori</th>
                        <th>Guru</th>
                        <th class="text-center">Gambar</th>
                        <th>Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($senarai as $i => $row): ?>
                    <tr>
                        <td class="ps-3 text-muted"><?= $offset + $i + 1 ?></td>
                        <td>
                            <?php if ($row['gambar1'] && file_exists(ROOT_PATH . '/' . $row['gambar1'])): ?>
                            <img src="<?= BASE_URL . '/' . clean($row['gambar1']) ?>"
                                 alt="" style="width:50px;height:40px;object-fit:cover;border-radius:4px">
                            <?php else: ?>
                            <div class="bg-light text-muted d-flex align-items-center justify-content-center"
                                 style="width:50px;height:40px;border-radius:4px">
                                <i class="bi bi-image"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/opr/lihat.php?id=<?= $row['id'] ?>"
                               class="fw-semibold text-decoration-none small">
                                <?= clean($row['no_rujukan'] ?: '-') ?>
                            </a>
                        </td>
                        <td class="text-nowrap"><?= formatTarikh($row['tarikh_aktiviti']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($row['tajuk']) ?></div>
                            <?php if ($row['tempat']): ?>
                            <div class="text-muted small">
                                <i class="bi bi-geo-alt me-1"></i><?= clean($row['tempat']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeKategoriOpr($row['kategori']) ?></td>
                        <td><?= clean($row['guru_nama'] ?: '-') ?></td>
                        <td class="text-center">
                            <?php
                            $nG = (bool)$row['gambar1'] + (bool)$row['gambar2'] + (bool)$row['gambar3'];
                            echo '<span class="badge bg-' . ($nG > 0 ? 'info' : 'secondary') . '">'
                               . $nG . '/3</span>';
                            ?>
                        </td>
                        <td><?= badgeStatus($row['status']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>/modules/opr/lihat.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-success py-0" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/modules/opr/edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-warning py-0" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/export/pdf.php?modul=opr&id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-danger py-0" title="PDF" target="_blank">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/export/docx.php?modul=opr&id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary py-0" title="DOCX" target="_blank">
                                <i class="bi bi-file-earmark-word"></i>
                            </a>
                            <?php if ($row['status'] !== 'arkib'): ?>
                            <a href="<?= BASE_URL ?>/modules/opr/aksi.php?tindakan=arkib&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                               class="btn btn-sm btn-outline-secondary py-0" title="Arkib"
                               onclick="return confirm('Arkibkan OPR ini?')">
                                <i class="bi bi-archive"></i>
                            </a>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/modules/opr/aksi.php?tindakan=pulih&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                               class="btn btn-sm btn-outline-success py-0" title="Pulih">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-padam"
                                    data-id="<?= $row['id'] ?>"
                                    data-tajuk="<?= jsEscape($row['tajuk']) ?>" title="Padam">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-2 mb-3">
    <small class="text-muted">
        Menunjukkan <?= min($offset + 1, $total) ?>-<?= min($offset + $perPage, $total) ?> daripada <?= $total ?> rekod
    </small>
    <?= pagination($total, $page, $perPage, $baseUrl) ?>
</div>

<!-- Hidden delete form -->
<form id="formPadam" method="POST" action="<?= BASE_URL ?>/modules/opr/aksi.php">
    <?= csrfField() ?>
    <input type="hidden" name="tindakan" value="padam">
    <input type="hidden" name="id" id="padamId">
</form>

<style>
.card:hover .card-img-top, .card:hover .position-relative img {
    transform: scale(1.04);
}
</style>

<?php
$extraScript = <<<'JS'
<script>
$(function(){
    // Confirm padam
    $('.btn-padam').on('click', function(){
        var id    = $(this).data('id');
        var tajuk = $(this).data('tajuk');
        Swal.fire({
            title: 'Padam OPR?',
            html: 'Anda akan memadamkan:<br><strong>' + tajuk + '</strong><br><br>Gambar yang dimuat naik turut akan dipadam. Tindakan ini tidak boleh dibuat asal.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Batal',
            confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Ya, Padam'
        }).then(function(r){
            if (r.isConfirmed) {
                $('#padamId').val(id);
                $('#formPadam').submit();
            }
        });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
