<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Rekod Prestasi / Markah';

// ── Filter parameters ─────────────────────────────────────────────────────────
$tahunCari       = (int)($_GET['tahun']           ?? TAHUN_SEMASA);
$kelasId         = (int)($_GET['kelas_id']        ?? 0);
$jenisPenilaian  = clean($_GET['jenis_penilaian'] ?? '');
$subjekId        = (int)($_GET['subjek_id']       ?? 0);
$penggal         = (int)($_GET['penggal']         ?? 0);
$cari            = clean($_GET['cari']            ?? '');

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = ['p.tahun = ?'];
$params = [$tahunCari];

if ($kelasId > 0) {
    $where[]  = 'm.kelas_id = ?';
    $params[] = $kelasId;
}
if ($jenisPenilaian !== '') {
    $where[]  = 'p.jenis_penilaian = ?';
    $params[] = $jenisPenilaian;
}
if ($subjekId > 0) {
    $where[]  = 'p.subjek_id = ?';
    $params[] = $subjekId;
}
if ($penggal > 0) {
    $where[]  = 'p.penggal = ?';
    $params[] = $penggal;
}
if ($cari !== '') {
    $where[]  = '(m.nama LIKE ? OR m.no_pendaftaran LIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$whereStr = 'WHERE ' . implode(' AND ', $where);

// ── Stats ─────────────────────────────────────────────────────────────────────
$statRow = dbFetch(
    "SELECT COUNT(*) AS jumlah,
            ROUND(AVG(p.markah),1) AS avg_markah,
            SUM(p.gred LIKE 'A%') AS gred_a,
            SUM(p.gred LIKE 'B%') AS gred_b,
            SUM(p.gred LIKE 'C%') AS gred_c,
            SUM(p.gred LIKE 'D%' OR p.gred='E' OR p.gred='G') AS gred_rendah
     FROM prestasi p
     LEFT JOIN murid m ON m.id = p.murid_id
     $whereStr",
    $params
);

// ── Fetch records ─────────────────────────────────────────────────────────────
$sql = "SELECT p.id, p.markah, p.gred, p.nilai_gred, p.band, p.jenis_penilaian, p.penggal,
               p.nama_subjek, p.tahun, p.catatan,
               m.nama AS nama_murid, m.no_pendaftaran,
               k.nama_kelas, k.tingkatan,
               s.kod AS subjek_kod
        FROM prestasi p
        LEFT JOIN murid   m ON m.id = p.murid_id
        LEFT JOIN kelas   k ON k.id = m.kelas_id
        LEFT JOIN subjek  s ON s.id = p.subjek_id
        $whereStr
        ORDER BY k.tingkatan, k.nama_kelas, m.nama, p.nama_subjek";

$prestasiList = dbFetchAll($sql, $params);

// ── Dropdown data ─────────────────────────────────────────────────────────────
$senaraKelas   = dbFetchAll(
    "SELECT id, CONCAT(tingkatan,' ',nama_kelas) AS label FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [$tahunCari]
);
$senaraSubjek  = getSenaraiSubjek();
$tahunList     = dbFetchAll("SELECT DISTINCT tahun FROM prestasi ORDER BY tahun DESC");
$jenisList     = ['PT1','PT2','PT3','PAT','ULBS','PBS','UASA','PPT','Ujian Harian','Lain-lain'];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Rekod Prestasi / Markah</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Prestasi</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/export/prestasi_pdf.php?<?= http_build_query(['tahun'=>$tahunCari,'kelas_id'=>$kelasId,'jenis_penilaian'=>$jenisPenilaian,'subjek_id'=>$subjekId,'penggal'=>$penggal]) ?>"
           class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>Export PDF
        </a>
        <a href="<?= BASE_URL ?>/export/prestasi_excel.php?<?= http_build_query(['tahun'=>$tahunCari,'kelas_id'=>$kelasId,'jenis_penilaian'=>$jenisPenilaian,'subjek_id'=>$subjekId,'penggal'=>$penggal]) ?>"
           class="btn btn-outline-success btn-sm" target="_blank">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <a href="<?= BASE_URL ?>/modules/prestasi/tambah.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Tambah Markah
        </a>
    </div>
</div>

<?= showFlash() ?>

<!-- Stats Bar -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4 text-primary"><?= number_format((int)($statRow['jumlah'] ?? 0)) ?></div>
                <div class="text-muted small">Jumlah Rekod</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4 text-info"><?= $statRow['avg_markah'] ?? '—' ?></div>
                <div class="text-muted small">Purata Markah</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4" style="color:#16a34a"><?= number_format((int)($statRow['gred_a'] ?? 0)) ?></div>
                <div class="text-muted small">Gred A</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4" style="color:#0284c7"><?= number_format((int)($statRow['gred_b'] ?? 0)) ?></div>
                <div class="text-muted small">Gred B</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4" style="color:#d97706"><?= number_format((int)($statRow['gred_c'] ?? 0)) ?></div>
                <div class="text-muted small">Gred C</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="fw-bold fs-4 text-danger"><?= number_format((int)($statRow['gred_rendah'] ?? 0)) ?></div>
                <div class="text-muted small">D/E/G</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Carian Murid</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="cari" class="form-control" placeholder="Nama / No. Pendaftaran" value="<?= clean($cari) ?>">
                </div>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php if (empty($tahunList)): ?>
                        <option value="<?= TAHUN_SEMASA ?>"><?= TAHUN_SEMASA ?></option>
                    <?php else: ?>
                        <?php foreach ($tahunList as $t): ?>
                        <option value="<?= $t['tahun'] ?>" <?= $t['tahun'] == $tahunCari ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Kelas</label>
                <select name="kelas_id" class="form-select form-select-sm">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($senaraKelas as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasId ? 'selected' : '' ?>><?= clean($k['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Jenis Penilaian</label>
                <select name="jenis_penilaian" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php foreach ($jenisList as $j): ?>
                    <option value="<?= $j ?>" <?= $jenisPenilaian === $j ? 'selected' : '' ?>><?= $j ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Subjek</label>
                <select name="subjek_id" class="form-select form-select-sm">
                    <option value="">-- Semua Subjek --</option>
                    <?php foreach ($senaraSubjek as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $subjekId ? 'selected' : '' ?>><?= clean($s['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1">Penggal</label>
                <select name="penggal" class="form-select form-select-sm">
                    <option value="">--</option>
                    <option value="1" <?= $penggal === 1 ? 'selected' : '' ?>>1</option>
                    <option value="2" <?= $penggal === 2 ? 'selected' : '' ?>>2</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel"></i></button>
                <a href="<?= BASE_URL ?>/modules/prestasi/index.php" class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>Senarai Rekod Markah
            <span class="badge bg-primary ms-2"><?= count($prestasiList) ?></span>
        </h6>
        <span class="text-muted small">Tahun <?= $tahunCari ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="prestasiTable" class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:40px">#</th>
                        <th>Murid</th>
                        <th>Kelas</th>
                        <th>Subjek</th>
                        <th>Jenis Penilaian</th>
                        <th class="text-center">Penggal</th>
                        <th class="text-center">Markah</th>
                        <th class="text-center">Gred</th>
                        <th class="text-center">Tahun</th>
                        <th class="text-center" style="width:120px">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prestasiList)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tiada rekod dijumpai. Cuba ubah penapis.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($prestasiList as $i => $p):
                        $gredWarna = constant('GRED_WARNA')[$p['gred']] ?? '#6b7280';
                    ?>
                    <tr>
                        <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $p['id'] ?>"
                               class="fw-semibold text-decoration-none small">
                                <?= clean($p['nama_murid'] ?? '—') ?>
                            </a>
                            <div class="text-muted" style="font-size:.75rem"><?= clean($p['no_pendaftaran'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if ($p['tingkatan']): ?>
                            <span class="badge bg-light text-dark border small">
                                <?= clean($p['tingkatan']) ?> <?= clean($p['nama_kelas']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($p['subjek_kod']): ?>
                            <span class="text-muted me-1"><?= clean($p['subjek_kod']) ?></span>
                            <?php endif; ?>
                            <?= clean($p['nama_subjek']) ?>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info small">
                                <?= clean($p['jenis_penilaian']) ?>
                            </span>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-light text-dark border">Penggal <?= (int)$p['penggal'] ?></span>
                        </td>
                        <td class="text-center fw-semibold"><?= number_format((float)$p['markah'], 1) ?></td>
                        <td class="text-center">
                            <span class="badge fw-bold px-2" style="background-color:<?= $gredWarna ?>;color:#fff">
                                <?= clean($p['gred']) ?>
                            </span>
                        </td>
                        <td class="text-center small"><?= clean($p['tahun']) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/modules/prestasi/edit.php?id=<?= $p['id'] ?>"
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/modules/prestasi/aksi.php?action=padam&id=<?= $p['id'] ?>&csrf=<?= csrfToken() ?>"
                                   class="btn btn-outline-danger btn-padam" title="Padam"
                                   data-nama="<?= clean($p['nama_subjek']) ?> - <?= clean($p['nama_murid'] ?? '') ?>">
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
    <?php if (!empty($prestasiList)): ?>
    <div class="card-footer bg-white text-muted small py-2 px-3">
        Menunjukkan <?= count($prestasiList) ?> rekod &bull; Purata Markah: <strong><?= $statRow['avg_markah'] ?? '—' ?></strong>
    </div>
    <?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
<script>
$(function(){
    $('#prestasiTable').DataTable({
        language: {
            emptyTable: 'Tiada rekod',
            zeroRecords: 'Tiada rekod dijumpai',
        },
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [9] },
            { searchable: false, targets: [0,5,6,7,8,9] }
        ],
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row align-items-center mt-2"<"col-md-6"i><"col-md-6"p>>',
    });

    $(document).on('click', '.btn-padam', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({
            title: 'Padam Rekod Markah?',
            html: 'Rekod <strong>'+nama+'</strong> akan dipadamkan.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Padam'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
