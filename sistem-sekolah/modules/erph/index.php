<?php
// ============================================================
// ERPH - SENARAI REKOD PENGAJARAN & PEMBELAJARAN HARIAN
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Senarai ERPH';

// ---- Ambil parameter penapisan ----
$tahun    = (int)($_GET['tahun']    ?? TAHUN_SEMASA);
$guruId   = (int)($_GET['guru_id'] ?? 0);
$kelasId  = (int)($_GET['kelas_id'] ?? 0);
$bulan    = (int)($_GET['bulan']   ?? 0);
$status   = $_GET['status'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = ROWS_PER_PAGE;

// ---- Bina WHERE clause ----
$where  = ['e.tahun = ?'];
$params = [$tahun];

if ($guruId)  { $where[] = 'e.guru_id = ?';  $params[] = $guruId; }
if ($kelasId) { $where[] = 'e.kelas_id = ?'; $params[] = $kelasId; }
if ($bulan)   { $where[] = 'MONTH(e.tarikh) = ?'; $params[] = $bulan; }
if ($status !== '') { $where[] = 'e.status = ?'; $params[] = $status; }

// Guru bukan admin hanya boleh lihat rekod sendiri
if (!hasRole(['admin', 'pengetua', 'penolong_kanan'])) {
    $where[] = 'e.guru_id = ?';
    $params[] = $_SESSION['user_id'];
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ---- Kiraan ----
$total      = (int)dbValue("SELECT COUNT(*) FROM erph e $whereSql", $params);
$totalDraf  = (int)dbValue("SELECT COUNT(*) FROM erph e WHERE e.tahun=? AND e.status='draf'", [$tahun]);
$totalAktif = (int)dbValue("SELECT COUNT(*) FROM erph e WHERE e.tahun=? AND e.status='aktif'", [$tahun]);
$totalArkib = (int)dbValue("SELECT COUNT(*) FROM erph e WHERE e.tahun=? AND e.status='arkib'", [$tahun]);
$totalAll   = (int)dbValue("SELECT COUNT(*) FROM erph e WHERE e.tahun=?", [$tahun]);

// ---- Data utama ----
$offset = ($page - 1) * $perPage;
$paramsLimit = array_merge($params, [$perPage, $offset]);
$senarai = dbFetchAll(
    "SELECT e.*, g.nama AS guru_nama
     FROM erph e
     LEFT JOIN guru g ON g.id = e.guru_id
     $whereSql
     ORDER BY e.tarikh DESC, e.id DESC
     LIMIT ? OFFSET ?",
    $paramsLimit
);

// ---- Dropdown data ----
$senaraiGuru  = getSenaraiGuru();
$senaraiKelas = getSenaraiKelas($tahun);

// ---- URL pagination ----
$baseUrl = BASE_URL . '/modules/erph/index.php?tahun=' . $tahun
    . '&guru_id=' . $guruId . '&kelas_id=' . $kelasId
    . '&bulan=' . $bulan . '&status=' . urlencode($status);

// ---- Badge SP ----
function badgeSP(string $sp): string {
    $map = [
        'SP1' => 'secondary', 'SP2' => 'info',    'SP3' => 'primary',
        'SP4' => 'success',   'SP5' => 'warning',  'SP6' => 'danger',
    ];
    $cls = $map[$sp] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . clean($sp) . '</span>';
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Senarai ERPH</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
            <li class="breadcrumb-item active">ERPH</li>
        </ol></nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/erph/tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Tambah ERPH
    </a>
</div>

<?= showFlash() ?>

<!-- STATS BAR -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-2"><i class="bi bi-journals fs-4 text-primary"></i></div>
                <div><div class="small text-muted">Jumlah ERPH</div><div class="fw-bold fs-5"><?= $totalAll ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-2"><i class="bi bi-pencil-square fs-4 text-warning"></i></div>
                <div><div class="small text-muted">Draf</div><div class="fw-bold fs-5"><?= $totalDraf ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-2"><i class="bi bi-check-circle fs-4 text-success"></i></div>
                <div><div class="small text-muted">Aktif</div><div class="fw-bold fs-5"><?= $totalAktif ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-2"><i class="bi bi-archive fs-4 text-secondary"></i></div>
                <div><div class="small text-muted">Arkib</div><div class="fw-bold fs-5"><?= $totalArkib ?></div></div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php for ($y = (int)TAHUN_SEMASA + 1; $y >= (int)TAHUN_SEMASA - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php
                    $namaBulan = ['','Jan','Feb','Mac','Apr','Mei','Jun','Jul','Ogos','Sep','Okt','Nov','Dis'];
                    for ($m = 1; $m <= 12; $m++):
                    ?>
                        <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= $namaBulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if (hasRole(['admin','pengetua','penolong_kanan'])): ?>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Guru</label>
                <select name="guru_id" class="form-select form-select-sm">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($senaraiGuru as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= $guruId == $g['id'] ? 'selected' : '' ?>><?= clean($g['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Kelas</label>
                <select name="kelas_id" class="form-select form-select-sm">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($senaraiKelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $kelasId == $k['id'] ? 'selected' : '' ?>><?= clean($k['label']) ?></option>
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
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search me-1"></i>Cari</button>
                <a href="<?= BASE_URL ?>/modules/erph/index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($senarai)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                <p class="mb-0">Tiada rekod ERPH dijumpai.</p>
                <a href="<?= BASE_URL ?>/modules/erph/tambah.php" class="btn btn-sm btn-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i>Tambah ERPH Pertama
                </a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>No. Rujukan</th>
                        <th>Tarikh</th>
                        <th>Tajuk / Subjek</th>
                        <th>Guru</th>
                        <th>Kelas</th>
                        <th>Standard Prestasi</th>
                        <th class="text-center">Murid</th>
                        <th>Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($senarai as $i => $row): ?>
                    <tr>
                        <td class="ps-3 text-muted"><?= $offset + $i + 1 ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/erph/lihat.php?id=<?= $row['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= clean($row['no_rujukan']) ?>
                            </a>
                        </td>
                        <td class="text-nowrap"><?= formatTarikh($row['tarikh']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= clean($row['tajuk']) ?></div>
                            <div class="text-muted small"><?= clean($row['nama_subjek']) ?></div>
                        </td>
                        <td><?= clean($row['guru_nama'] ?? '-') ?></td>
                        <td class="text-nowrap">
                            <?php
                            $tingkatan = '';
                            if ($row['kelas_id']) {
                                $kInfo = dbFetch("SELECT tingkatan FROM kelas WHERE id=?", [$row['kelas_id']]);
                                if ($kInfo) $tingkatan = $kInfo['tingkatan'] . ' ';
                            }
                            echo clean($tingkatan . $row['nama_kelas']);
                            ?>
                        </td>
                        <td>
                            <?php
                            $spVals = ['SP1','SP2','SP3','SP4','SP5','SP6'];
                            $spBadges = [];
                            foreach ($spVals as $sp) {
                                if (!empty($row[$sp])) {
                                    $spBadges[] = badgeSP($sp);
                                }
                            }
                            // Also check standard_prestasi field
                            if (empty($spBadges) && !empty($row['standard_prestasi'])) {
                                $spBadges[] = badgeSP($row['standard_prestasi']);
                            }
                            echo implode(' ', $spBadges) ?: '<span class="text-muted">-</span>';
                            ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark"><?= (int)$row['bil_murid_hadir'] ?></span>
                        </td>
                        <td><?= badgeStatus($row['status']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>/modules/erph/lihat.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary py-0" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/modules/erph/edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-secondary py-0" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($row['status'] !== 'arkib'): ?>
                            <a href="<?= BASE_URL ?>/modules/erph/aksi.php?tindakan=arkib&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                               class="btn btn-sm btn-outline-warning py-0" title="Arkib"
                               onclick="return confirm('Arkibkan rekod ini?')">
                                <i class="bi bi-archive"></i>
                            </a>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/modules/erph/aksi.php?tindakan=pulih&id=<?= $row['id'] ?>&csrf=<?= csrfToken() ?>"
                               class="btn btn-sm btn-outline-info py-0" title="Pulih">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/export/pdf.php?modul=erph&id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-danger py-0" title="PDF" target="_blank">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                title="Padam"
                                onclick="confirmPadam(<?= $row['id'] ?>, '<?= jsEscape($row['no_rujukan']) ?>')">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted">Jumlah: <?= $total ?> rekod</small>
            <?= pagination($total, $page, $perPage, $baseUrl) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Padam -->
<form id="formPadam" method="POST" action="<?= BASE_URL ?>/modules/erph/aksi.php">
    <?= csrfField() ?>
    <input type="hidden" name="tindakan" value="padam">
    <input type="hidden" name="id" id="pamDamId">
</form>

<?php
$extraScript = <<<'JS'
<script>
function confirmPadam(id, noRujukan) {
    Swal.fire({
        title: 'Padam ERPH?',
        html: 'Anda akan memadamkan rekod <strong>' + noRujukan + '</strong>.<br>Tindakan ini tidak boleh dibuat asal.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Batal',
        confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Ya, Padam'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('pamDamId').value = id;
            document.getElementById('formPadam').submit();
        }
    });
}
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
