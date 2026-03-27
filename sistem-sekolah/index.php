<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';
$tahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);

// ---- Ambil statistik ----
$statMurid    = (int)dbValue("SELECT COUNT(*) FROM murid WHERE tahun=? AND status='aktif'", [$tahun]);
$statL        = (int)dbValue("SELECT COUNT(*) FROM murid WHERE tahun=? AND status='aktif' AND jantina='L'", [$tahun]);
$statP        = (int)dbValue("SELECT COUNT(*) FROM murid WHERE tahun=? AND status='aktif' AND jantina='P'", [$tahun]);
$statGuru     = (int)dbValue("SELECT COUNT(*) FROM guru WHERE status='aktif'");
$statKelas    = (int)dbValue("SELECT COUNT(*) FROM kelas WHERE tahun=? AND status='aktif'", [$tahun]);
$statERPH     = (int)dbValue("SELECT COUNT(*) FROM erph WHERE tahun=? AND status='aktif'", [$tahun]);
$statOPR      = (int)dbValue("SELECT COUNT(*) FROM opr WHERE tahun=? AND status='aktif'", [$tahun]);
$statPentadbir= (int)dbValue("SELECT COUNT(*) FROM pentadbir WHERE status='aktif'");

// Murid mengikut kelas
$muridKelas = dbFetchAll("SELECT k.nama_kelas, k.tingkatan,
    SUM(CASE WHEN m.jantina='L' THEN 1 ELSE 0 END) AS lelaki,
    SUM(CASE WHEN m.jantina='P' THEN 1 ELSE 0 END) AS perempuan,
    COUNT(m.id) AS jumlah
    FROM kelas k LEFT JOIN murid m ON m.kelas_id=k.id AND m.status='aktif'
    WHERE k.tahun=? AND k.status='aktif'
    GROUP BY k.id ORDER BY k.tingkatan, k.nama_kelas", [$tahun]);

// Prestasi terkini
$prestasiTerkini = dbFetchAll("SELECT p.*, mu.nama AS nama_murid,
    k.nama_kelas, k.tingkatan
    FROM prestasi p
    JOIN murid mu ON mu.id=p.murid_id
    JOIN kelas k ON k.id=mu.kelas_id
    WHERE p.tahun=? ORDER BY p.created_at DESC LIMIT 8", [$tahun]);

// OPR terbaru
$oprTerbaru = dbFetchAll("SELECT o.*, g.nama AS nama_guru
    FROM opr o LEFT JOIN guru g ON g.id=o.guru_id
    WHERE o.tahun=? ORDER BY o.created_at DESC LIMIT 5", [$tahun]);

// ERPH terbaru
$erphTerbaru = dbFetchAll("SELECT e.*, g.nama AS nama_guru, k.nama_kelas, k.tingkatan
    FROM erph e LEFT JOIN guru g ON g.id=e.guru_id
    LEFT JOIN kelas k ON k.id=e.kelas_id
    WHERE e.tahun=? ORDER BY e.tarikh DESC LIMIT 5", [$tahun]);

// Chart - Murid mengikut tingkatan
$chartMuridTingkatan = dbFetchAll("SELECT k.tingkatan, COUNT(m.id) AS jumlah
    FROM kelas k LEFT JOIN murid m ON m.kelas_id=k.id AND m.status='aktif'
    WHERE k.tahun=? AND k.status='aktif'
    GROUP BY k.tingkatan ORDER BY k.tingkatan", [$tahun]);

// Chart - Prestasi gred
$chartGred = dbFetchAll("SELECT gred, COUNT(*) AS jumlah
    FROM prestasi WHERE tahun=? AND gred IS NOT NULL AND gred != ''
    GROUP BY gred ORDER BY FIELD(gred,'A+','A','A-','B+','B','B-','C+','C','C-','D','E','G')", [$tahun]);

// ERPH 12 bulan terakhir
$chartERPH = dbFetchAll("SELECT MONTH(tarikh) AS bulan, COUNT(*) AS jumlah
    FROM erph WHERE tahun=? GROUP BY MONTH(tarikh) ORDER BY bulan", [$tahun]);
$erphByBulan = array_fill(1, 12, 0);
foreach ($chartERPH as $row) $erphByBulan[(int)$row['bulan']] = (int)$row['jumlah'];

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Laman Utama</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-2">
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <span class="badge bg-primary px-3 py-2">
            <i class="bi bi-calendar3 me-1"></i>Tahun <?= $tahun ?>
        </span>
    </div>
</div>

<!-- STAT CARDS ROW 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-blue h-100">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-num"><?= number_format($statMurid) ?></div>
            <div class="stat-label">Jumlah Murid</div>
            <div class="stat-sub"><i class="bi bi-gender-male me-1"></i><?= $statL ?> L &nbsp;
                <i class="bi bi-gender-female me-1"></i><?= $statP ?> P</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-teal h-100">
            <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-num"><?= number_format($statGuru) ?></div>
            <div class="stat-label">Jumlah Guru</div>
            <div class="stat-sub"><i class="bi bi-building me-1"></i><?= $statPentadbir ?> pentadbir</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green h-100">
            <div class="stat-icon"><i class="bi bi-grid-fill"></i></div>
            <div class="stat-num"><?= number_format($statKelas) ?></div>
            <div class="stat-label">Jumlah Kelas</div>
            <div class="stat-sub">Tahun <?= $tahun ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-orange h-100">
            <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
            <div class="stat-num"><?= number_format($statERPH) ?></div>
            <div class="stat-label">Rekod ERPH</div>
            <div class="stat-sub"><i class="bi bi-file-text me-1"></i><?= $statOPR ?> OPR</div>
        </div>
    </div>
</div>

<!-- CHARTS ROW -->
<div class="row g-3 mb-4">
    <!-- Chart Murid mengikut Tingkatan -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart-fill text-primary me-2"></i>Murid Mengikut Tingkatan</span>
                <span class="badge bg-primary"><?= $tahun ?></span>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartTingkatan"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Gred Prestasi -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-pie-chart-fill text-success me-2"></i>Agihan Gred Penilaian</span>
                <a href="modules/prestasi/index.php" class="btn btn-sm btn-outline-primary">Lihat</a>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartGred"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart ERPH Bulanan -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-activity text-warning me-2"></i>Rekod ERPH Bulanan</span>
                <a href="modules/erph/index.php" class="btn btn-sm btn-outline-primary">Lihat</a>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartERPH"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABLE ROW -->
<div class="row g-3 mb-4">
    <!-- Murid mengikut Kelas -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table text-primary me-2"></i>Bilangan Murid Mengikut Kelas</span>
                <a href="modules/murid/index.php" class="btn btn-sm btn-outline-primary">Semua Murid</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tingkatan</th>
                                <th>Kelas</th>
                                <th class="text-center"><i class="bi bi-gender-male text-primary"></i> L</th>
                                <th class="text-center"><i class="bi bi-gender-female text-danger"></i> P</th>
                                <th class="text-center fw-bold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($muridKelas): ?>
                            <?php foreach ($muridKelas as $k): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= clean($k['tingkatan']) ?></span></td>
                                <td><?= clean($k['nama_kelas']) ?></td>
                                <td class="text-center"><?= $k['lelaki'] ?></td>
                                <td class="text-center"><?= $k['perempuan'] ?></td>
                                <td class="text-center fw-semibold"><?= $k['jumlah'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted">Tiada data kelas</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if ($muridKelas): ?>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2">JUMLAH KESELURUHAN</td>
                                <td class="text-center"><?= array_sum(array_column($muridKelas, 'lelaki')) ?></td>
                                <td class="text-center"><?= array_sum(array_column($muridKelas, 'perempuan')) ?></td>
                                <td class="text-center"><?= array_sum(array_column($muridKelas, 'jumlah')) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Recent OPR -->
    <div class="col-md-5">
        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Tindakan Pantas</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="modules/murid/tambah.php" class="btn btn-outline-primary w-100 d-flex align-items-center gap-2 justify-content-center">
                            <i class="bi bi-person-plus fs-5"></i><span>Tambah Murid</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="modules/guru/tambah.php" class="btn btn-outline-teal w-100 d-flex align-items-center gap-2 justify-content-center" style="color:#0891b2;border-color:#0891b2">
                            <i class="bi bi-person-badge fs-5"></i><span>Tambah Guru</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="modules/erph/tambah.php" class="btn btn-outline-warning w-100 d-flex align-items-center gap-2 justify-content-center">
                            <i class="bi bi-journal-plus fs-5"></i><span>Buat ERPH</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="modules/opr/tambah.php" class="btn btn-outline-success w-100 d-flex align-items-center gap-2 justify-content-center">
                            <i class="bi bi-file-earmark-plus fs-5"></i><span>Buat OPR</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="modules/prestasi/tambah.php" class="btn btn-outline-info w-100 d-flex align-items-center gap-2 justify-content-center">
                            <i class="bi bi-graph-up-arrow fs-5"></i><span>Rekod Markah</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="modules/kelas/index.php" class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2 justify-content-center">
                            <i class="bi bi-grid fs-5"></i><span>Urus Kelas</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPR Terbaru -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text text-success me-2"></i>OPR Terbaru</span>
                <a href="modules/opr/index.php" class="btn btn-sm btn-outline-success">Semua</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($oprTerbaru): ?>
                <?php foreach ($oprTerbaru as $opr): ?>
                <a href="modules/opr/lihat.php?id=<?= $opr['id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold small text-truncate" style="max-width:200px"><?= clean($opr['tajuk']) ?></span>
                        <?= badgeStatus($opr['status']) ?>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i><?= formatTarikh($opr['tarikh_aktiviti']) ?>
                        <?php if ($opr['nama_guru']): ?>
                        &nbsp;|&nbsp;<i class="bi bi-person me-1"></i><?= clean($opr['nama_guru']) ?>
                        <?php endif; ?>
                    </small>
                </a>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="list-group-item text-center text-muted py-3">Tiada rekod OPR</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ERPH Terbaru -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text text-warning me-2"></i>ERPH Terbaru</span>
                <a href="modules/erph/index.php" class="btn btn-sm btn-outline-warning">Semua ERPH</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Tajuk</th>
                            <th>Guru</th>
                            <th>Kelas</th>
                            <th>Subjek</th>
                            <th>SP</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($erphTerbaru): ?>
                        <?php foreach ($erphTerbaru as $e): ?>
                        <tr>
                            <td><?= formatTarikh($e['tarikh'], 'd/m/Y') ?></td>
                            <td class="fw-semibold"><?= clean($e['tajuk']) ?></td>
                            <td><?= clean($e['nama_guru'] ?? '-') ?></td>
                            <td><?= clean($e['tingkatan'] ?? '') ?> <?= clean($e['nama_kelas'] ?? '-') ?></td>
                            <td><?= clean($e['nama_subjek'] ?? '-') ?></td>
                            <td><span class="badge bg-info text-dark"><?= clean($e['standard_prestasi'] ?? '-') ?></span></td>
                            <td><?= badgeStatus($e['status']) ?></td>
                            <td>
                                <a href="modules/erph/lihat.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary py-0">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="8" class="text-center py-3 text-muted">Tiada rekod ERPH</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart data
$tingkatanLabel = array_column($chartMuridTingkatan, 'tingkatan');
$tingkatanData  = array_column($chartMuridTingkatan, 'jumlah');
$gredLabel = array_column($chartGred, 'gred');
$gredData  = array_column($chartGred, 'jumlah');
$bulanLabel = ['Jan','Feb','Mac','Apr','Mei','Jun','Jul','Ogos','Sep','Okt','Nov','Dis'];
$erphData   = array_values($erphByBulan);

$extraScript = '<script>
// Chart 1 - Murid mengikut Tingkatan
new Chart(document.getElementById("chartTingkatan"), {
    type: "bar",
    data: {
        labels: ' . json_encode($tingkatanLabel) . ',
        datasets: [{
            label: "Bilangan Murid",
            data: ' . json_encode($tingkatanData) . ',
            backgroundColor: ["#2563eb","#0891b2","#16a34a","#d97706","#7c3aed"],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// Chart 2 - Gred
const gredColors = {"A+":"#15803d","A":"#16a34a","A-":"#22c55e","B+":"#0284c7","B":"#0ea5e9","B-":"#38bdf8","C+":"#d97706","C":"#f59e0b","C-":"#fbbf24","D":"#ea580c","E":"#dc2626","G":"#7f1d1d"};
const gredLabels = ' . json_encode($gredLabel) . ';
new Chart(document.getElementById("chartGred"), {
    type: "doughnut",
    data: {
        labels: gredLabels,
        datasets: [{
            data: ' . json_encode($gredData) . ',
            backgroundColor: gredLabels.map(g => gredColors[g] || "#9ca3af"),
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: "right", labels: { boxWidth: 12, font: { size: 11 } } } }
    }
});

// Chart 3 - ERPH Bulanan
new Chart(document.getElementById("chartERPH"), {
    type: "line",
    data: {
        labels: ' . json_encode($bulanLabel) . ',
        datasets: [{
            label: "ERPH",
            data: ' . json_encode($erphData) . ',
            borderColor: "#d97706",
            backgroundColor: "rgba(217,119,6,.1)",
            tension: 0.4,
            fill: true,
            pointBackgroundColor: "#d97706",
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>';

include __DIR__ . '/includes/footer.php';
