<?php
// ============================================================
// LAPORAN PERBANDINGAN PRESTASI
// Tab 1: Antara Kelas | Tab 2: Trend Penggal | Tab 3: Ranking Murid
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle = 'Laporan Perbandingan Prestasi';

// ── Parameter Filter ──────────────────────────────────────────────────────────
$tahun          = (int)($_GET['tahun']           ?? TAHUN_SEMASA);
$jenisPenilaian = clean($_GET['jenis_penilaian'] ?? 'PT1');
$subjekTerpilih = clean($_GET['nama_subjek']     ?? '');
$kelasTrend     = (int)($_GET['kelas_trend']     ?? 0);
$kelasRanking   = (int)($_GET['kelas_ranking']   ?? 0);
$jenisPenRanking = clean($_GET['jenis_pen_ranking'] ?? '');
$tabAktif       = clean($_GET['tab']             ?? 'kelas');

// ── Dropdown data ─────────────────────────────────────────────────────────────
$tahunList       = dbFetchAll("SELECT DISTINCT tahun FROM murid ORDER BY tahun DESC");
$senaraKelas     = dbFetchAll(
    "SELECT id, CONCAT(tingkatan,' ',nama_kelas) AS label
     FROM kelas WHERE tahun = ? AND status = 'aktif'
     ORDER BY tingkatan, nama_kelas",
    [$tahun]
);
$senaraSubjek    = dbFetchAll(
    "SELECT DISTINCT nama_subjek FROM prestasi WHERE tahun = ? ORDER BY nama_subjek",
    [$tahun]
);
$jenisPenilaianList = ['PT1','PT2','PT3','PAT','Percubaan','Lain-lain'];

// ── TAB 1: Perbandingan Antara Kelas ─────────────────────────────────────────
$dataKelas = [];
if ($jenisPenilaian) {
    $sqlKelas = "SELECT k.tingkatan, k.nama_kelas, k.id AS kelas_id,
        COUNT(DISTINCT p.murid_id) AS bil_murid,
        ROUND(AVG(p.markah), 2) AS purata_markah,
        ROUND(AVG(p.nilai_gred), 2) AS purata_gpa,
        ROUND(SUM(CASE WHEN p.markah >= 50 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) AS peratus_lulus
    FROM kelas k
    JOIN murid m ON m.kelas_id = k.id AND m.status = 'aktif'
    JOIN prestasi p ON p.murid_id = m.id AND p.tahun = ? AND p.jenis_penilaian = ?";

    $paramsKelas = [$tahun, $jenisPenilaian];

    if ($subjekTerpilih !== '') {
        $sqlKelas .= " AND p.nama_subjek = ?";
        $paramsKelas[] = $subjekTerpilih;
    }

    $sqlKelas .= " WHERE k.tahun = ? GROUP BY k.id ORDER BY purata_markah DESC";
    $paramsKelas[] = $tahun;

    $dataKelas = dbFetchAll($sqlKelas, $paramsKelas);
}

// Tentukan kelas terbaik dan terendah
$markahTertinggi = !empty($dataKelas) ? (float)$dataKelas[0]['purata_markah'] : 0;
$markahTerendah  = !empty($dataKelas) ? (float)end($dataKelas)['purata_markah'] : 0;

// ── TAB 2: Trend Antara Penggal ───────────────────────────────────────────────
$dataTrend = [];
$subjekTrend = [];
if ($kelasTrend > 0) {
    // Ambil semua subjek dalam kelas ini
    $sqlSubjekTrend = "SELECT DISTINCT p.nama_subjek
        FROM prestasi p
        JOIN murid m ON m.id = p.murid_id
        WHERE m.kelas_id = ? AND p.tahun = ? AND m.status = 'aktif'
        ORDER BY p.nama_subjek";
    $subjekTrend = dbFetchAll($sqlSubjekTrend, [$kelasTrend, $tahun]);

    // Ambil purata per subjek per penggal
    $sqlTrend = "SELECT p.nama_subjek, p.jenis_penilaian,
        ROUND(AVG(p.markah), 2) AS purata
        FROM prestasi p
        JOIN murid m ON m.id = p.murid_id
        WHERE m.kelas_id = ? AND p.tahun = ? AND m.status = 'aktif'";
    $paramsTrend = [$kelasTrend, $tahun];

    if ($subjekTerpilih !== '') {
        $sqlTrend .= " AND p.nama_subjek = ?";
        $paramsTrend[] = $subjekTerpilih;
    }
    $sqlTrend .= " GROUP BY p.nama_subjek, p.jenis_penilaian ORDER BY p.nama_subjek, p.jenis_penilaian";

    $rawTrend = dbFetchAll($sqlTrend, $paramsTrend);

    // Susun semula: [subjek][jenis_penilaian] = purata
    foreach ($rawTrend as $row) {
        $dataTrend[$row['nama_subjek']][$row['jenis_penilaian']] = (float)$row['purata'];
    }
}

// ── TAB 3: Ranking Murid ──────────────────────────────────────────────────────
$dataRanking = [];
if ($kelasRanking > 0 && $jenisPenRanking !== '') {
    $sqlRanking = "SELECT m.nama, m.no_pendaftaran,
        COUNT(p.id) AS bil_subjek,
        ROUND(SUM(p.markah), 2) AS jumlah_markah,
        ROUND(AVG(p.markah), 2) AS purata_markah,
        ROUND(AVG(p.nilai_gred), 2) AS purata_gpa,
        (SELECT p2.gred FROM prestasi p2 WHERE p2.murid_id = m.id
            AND p2.tahun = ? AND p2.jenis_penilaian = ?
            GROUP BY p2.gred ORDER BY COUNT(*) DESC LIMIT 1) AS gred_dominan
    FROM murid m
    JOIN prestasi p ON p.murid_id = m.id
        AND p.tahun = ? AND p.jenis_penilaian = ?
    WHERE m.kelas_id = ? AND m.status = 'aktif'
    GROUP BY m.id
    ORDER BY purata_markah DESC";

    $dataRanking = dbFetchAll($sqlRanking, [
        $tahun, $jenisPenRanking,
        $tahun, $jenisPenRanking,
        $kelasRanking
    ]);
}

// ── Sedia data Chart.js ───────────────────────────────────────────────────────
// Tab 1: Bar chart kelas
$chartKelasLabel  = array_map(fn($r) => $r['tingkatan'] . ' ' . $r['nama_kelas'], $dataKelas);
$chartKelasMarkah = array_map(fn($r) => (float)$r['purata_markah'], $dataKelas);
$chartKelasWarna  = array_map(function($r) use ($markahTertinggi, $markahTerendah) {
    $m = (float)$r['purata_markah'];
    if ($m === $markahTertinggi && $markahTertinggi > 0) return 'rgba(34,197,94,0.8)';
    if ($m === $markahTerendah && $markahTerendah > 0 && count([]) !== 1) return 'rgba(239,68,68,0.8)';
    return 'rgba(59,130,246,0.7)';
}, $dataKelas);
// Warna betul — kira semula
$chartKelasWarnaFinal = [];
foreach ($dataKelas as $idx => $row) {
    $m = (float)$row['purata_markah'];
    if ($idx === 0) {
        $chartKelasWarnaFinal[] = 'rgba(34,197,94,0.85)'; // hijau — terbaik
    } elseif ($idx === count($dataKelas) - 1 && count($dataKelas) > 1) {
        $chartKelasWarnaFinal[] = 'rgba(239,68,68,0.85)'; // merah — paling rendah
    } else {
        $chartKelasWarnaFinal[] = 'rgba(59,130,246,0.75)'; // biru
    }
}

// Tab 2: Line chart trend
$penggalUrutan = ['PT1','PT2','PT3','PAT','Percubaan'];
$chartTrendDatasets = [];
$warnaTrend = ['#3b82f6','#f59e0b','#10b981','#ef4444','#8b5cf6','#f97316','#14b8a6','#ec4899'];
$wIdx = 0;
foreach ($dataTrend as $subjek => $penggalData) {
    $titik = [];
    foreach ($penggalUrutan as $pg) {
        $titik[] = $penggalData[$pg] ?? null;
    }
    $warna = $warnaTrend[$wIdx % count($warnaTrend)];
    $chartTrendDatasets[] = [
        'label'           => $subjek,
        'data'            => $titik,
        'borderColor'     => $warna,
        'backgroundColor' => $warna . '20',
        'tension'         => 0.3,
        'fill'            => false,
        'pointRadius'     => 5,
        'spanGaps'        => true,
    ];
    $wIdx++;
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Laporan Perbandingan Prestasi</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Laporan Prestasi</li>
            </ol>
        </nav>
    </div>
</div>

<!-- ── FILTER UTAMA ──────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= clean($tabAktif) ?>">

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php foreach ($tahunList as $t): ?>
                        <option value="<?= $t['tahun'] ?>" <?= $t['tahun'] == $tahun ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($tahunList)): ?>
                        <option value="<?= TAHUN_SEMASA ?>"><?= TAHUN_SEMASA ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Jenis Penilaian</label>
                <select name="jenis_penilaian" class="form-select form-select-sm">
                    <?php foreach ($jenisPenilaianList as $jp): ?>
                        <option value="<?= $jp ?>" <?= $jp === $jenisPenilaian ? 'selected' : '' ?>><?= $jp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold mb-1">Subjek <small class="text-muted">(pilihan)</small></label>
                <select name="nama_subjek" class="form-select form-select-sm">
                    <option value="">-- Semua Subjek --</option>
                    <?php foreach ($senaraSubjek as $s): ?>
                        <option value="<?= clean($s['nama_subjek']) ?>" <?= $s['nama_subjek'] === $subjekTerpilih ? 'selected' : '' ?>>
                            <?= clean($s['nama_subjek']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Kelas (Trend)</label>
                <select name="kelas_trend" class="form-select form-select-sm">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($senaraKelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasTrend ? 'selected' : '' ?>>
                            <?= clean($k['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3 d-flex gap-1">
                <div class="flex-fill">
                    <label class="form-label small fw-semibold mb-1">Kelas (Ranking)</label>
                    <select name="kelas_ranking" class="form-select form-select-sm">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($senaraKelas as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasRanking ? 'selected' : '' ?>>
                                <?= clean($k['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1">Jenis</label>
                    <select name="jenis_pen_ranking" class="form-select form-select-sm">
                        <option value="">--</option>
                        <?php foreach ($jenisPenilaianList as $jp): ?>
                            <option value="<?= $jp ?>" <?= $jp === $jenisPenRanking ? 'selected' : '' ?>><?= $jp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Tapis
                </button>
                <a href="<?= BASE_URL ?>/modules/prestasi/laporan.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── TAB NAVIGATION ─────────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-0" id="laporanTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tabAktif === 'kelas' ? 'active' : '' ?>"
                id="tab-kelas" data-bs-toggle="tab" data-bs-target="#panel-kelas"
                type="button" role="tab" onclick="setTab('kelas')">
            <i class="bi bi-diagram-3 me-1"></i>Antara Kelas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tabAktif === 'trend' ? 'active' : '' ?>"
                id="tab-trend" data-bs-toggle="tab" data-bs-target="#panel-trend"
                type="button" role="tab" onclick="setTab('trend')">
            <i class="bi bi-graph-up-arrow me-1"></i>Trend Penggal
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tabAktif === 'ranking' ? 'active' : '' ?>"
                id="tab-ranking" data-bs-toggle="tab" data-bs-target="#panel-ranking"
                type="button" role="tab" onclick="setTab('ranking')">
            <i class="bi bi-trophy me-1"></i>Ranking Murid
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm p-4" id="laporanTabContent">

    <!-- ══════════════════════════════════════════════════════════
         TAB 1: PERBANDINGAN ANTARA KELAS
    ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade <?= $tabAktif === 'kelas' ? 'show active' : '' ?>" id="panel-kelas" role="tabpanel">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-diagram-3 me-2 text-primary"></i>
                Perbandingan Antara Kelas — <?= clean($jenisPenilaian) ?>, Tahun <?= $tahun ?>
                <?php if ($subjekTerpilih): ?>
                    <span class="badge bg-info ms-2"><?= clean($subjekTerpilih) ?></span>
                <?php endif; ?>
            </h6>
            <?php if (!empty($dataKelas)): ?>
            <span class="badge bg-primary"><?= count($dataKelas) ?> kelas</span>
            <?php endif; ?>
        </div>

        <?php if (empty($dataKelas)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Tiada data dijumpai. Sila semak filter.
        </div>
        <?php else: ?>

        <!-- Bar Chart -->
        <div class="mb-4" style="max-height: 320px;">
            <canvas id="chartKelas"></canvas>
        </div>

        <!-- Jadual -->
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ranking</th>
                        <th>Kelas</th>
                        <th class="text-center">Bil. Murid</th>
                        <th class="text-center">Purata Markah</th>
                        <th class="text-center">GPA</th>
                        <th class="text-center">% Lulus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataKelas as $rank => $row):
                        $isTop    = $rank === 0;
                        $isBottom = $rank === count($dataKelas) - 1 && count($dataKelas) > 1;
                        $rowClass = $isTop ? 'table-success' : ($isBottom ? 'table-danger' : '');
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="text-center fw-bold">
                            <?php if ($isTop): ?>
                                <span class="badge bg-success">#<?= $rank + 1 ?> Terbaik</span>
                            <?php elseif ($isBottom): ?>
                                <span class="badge bg-danger">#<?= $rank + 1 ?></span>
                            <?php else: ?>
                                <span class="text-muted">#<?= $rank + 1 ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= clean($row['tingkatan'] . ' ' . $row['nama_kelas']) ?></td>
                        <td class="text-center"><?= (int)$row['bil_murid'] ?></td>
                        <td class="text-center fw-bold"><?= number_format((float)$row['purata_markah'], 1) ?></td>
                        <td class="text-center"><?= number_format((float)$row['purata_gpa'], 2) ?></td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <div class="progress flex-fill" style="height:10px;min-width:60px">
                                    <div class="progress-bar <?= $isTop ? 'bg-success' : ($isBottom ? 'bg-danger' : 'bg-primary') ?>"
                                         style="width:<?= min(100, (float)$row['peratus_lulus']) ?>%"></div>
                                </div>
                                <span class="small fw-semibold"><?= number_format((float)$row['peratus_lulus'], 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB 2: TREND ANTARA PENGGAL
    ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade <?= $tabAktif === 'trend' ? 'show active' : '' ?>" id="panel-trend" role="tabpanel">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-graph-up-arrow me-2 text-primary"></i>
                Trend Prestasi Antara Penggal
                <?php if ($kelasTrend > 0): ?>
                    — Kelas <?= collect_kelas_label($senaraKelas, $kelasTrend) ?>
                <?php endif; ?>
            </h6>
        </div>

        <?php if ($kelasTrend <= 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Sila pilih <strong>Kelas (Trend)</strong> dalam filter di atas dan klik <strong>Tapis</strong>.
        </div>

        <?php elseif (empty($dataTrend)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Tiada data dijumpai untuk kelas ini.
        </div>

        <?php else: ?>

        <!-- Line Chart -->
        <div class="mb-4" style="max-height: 350px;">
            <canvas id="chartTrend"></canvas>
        </div>

        <!-- Jadual Trend -->
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mata Pelajaran</th>
                        <?php foreach ($penggalUrutan as $pg): ?>
                            <th class="text-center"><?= clean($pg) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataTrend as $subjek => $penggalData):
                        // Kira trend: bandingkan penggal pertama dan akhir yang ada data
                        $nilaiAda = array_filter(array_map(fn($pg) => $penggalData[$pg] ?? null, $penggalUrutan), fn($v) => $v !== null);
                        $trendArah = '';
                        $trendWarna = 'secondary';
                        if (count($nilaiAda) >= 2) {
                            $awal = reset($nilaiAda);
                            $akhir = end($nilaiAda);
                            if ($akhir > $awal) { $trendArah = '&#8593;'; $trendWarna = 'success'; }
                            elseif ($akhir < $awal) { $trendArah = '&#8595;'; $trendWarna = 'danger'; }
                            else { $trendArah = '&#8594;'; $trendWarna = 'secondary'; }
                        }
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= clean($subjek) ?></td>
                        <?php foreach ($penggalUrutan as $pg): ?>
                            <td class="text-center">
                                <?php if (isset($penggalData[$pg])): ?>
                                    <?= number_format($penggalData[$pg], 1) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center">
                            <?php if ($trendArah): ?>
                                <span class="badge bg-<?= $trendWarna ?> fs-6"><?= $trendArah ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB 3: RANKING MURID
    ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade <?= $tabAktif === 'ranking' ? 'show active' : '' ?>" id="panel-ranking" role="tabpanel">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-trophy me-2 text-primary"></i>
                Ranking Murid
                <?php if ($kelasRanking > 0): ?>
                    — Kelas <?= collect_kelas_label($senaraKelas, $kelasRanking) ?>
                    <?php if ($jenisPenRanking): ?> | <?= clean($jenisPenRanking) ?><?php endif; ?>
                <?php endif; ?>
            </h6>
            <?php if (!empty($dataRanking)): ?>
                <span class="badge bg-primary"><?= count($dataRanking) ?> murid</span>
            <?php endif; ?>
        </div>

        <?php if ($kelasRanking <= 0 || $jenisPenRanking === ''): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Sila pilih <strong>Kelas (Ranking)</strong> dan <strong>Jenis Penilaian</strong> di bawah Ranking dalam filter di atas, kemudian klik <strong>Tapis</strong>.
        </div>

        <?php elseif (empty($dataRanking)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Tiada data dijumpai.
        </div>

        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px" class="text-center">Ranking</th>
                        <th>Nama Murid</th>
                        <th>No. Pendaftaran</th>
                        <th class="text-center">Bil. Subjek</th>
                        <th class="text-center">Jumlah Markah</th>
                        <th class="text-center">Purata</th>
                        <th class="text-center">GPA</th>
                        <th class="text-center">Gred Dominan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataRanking as $rank => $murid):
                        $gredWarna = GRED_WARNA[$murid['gred_dominan'] ?? ''] ?? '#6b7280';
                    ?>
                    <tr class="<?= $rank === 0 ? 'table-warning' : ($rank === 1 ? 'table-secondary' : ($rank === 2 ? 'table-light' : '')) ?>">
                        <td class="text-center">
                            <?php if ($rank === 0): ?>
                                <span class="badge fw-bold px-2 py-1" style="background:#f59e0b;color:#fff;" title="Emas">
                                    &#127941; #1
                                </span>
                            <?php elseif ($rank === 1): ?>
                                <span class="badge fw-bold px-2 py-1" style="background:#94a3b8;color:#fff;" title="Perak">
                                    &#129352; #2
                                </span>
                            <?php elseif ($rank === 2): ?>
                                <span class="badge fw-bold px-2 py-1" style="background:#cd7f32;color:#fff;" title="Gangsa">
                                    &#129353; #3
                                </span>
                            <?php else: ?>
                                <span class="text-muted fw-semibold">#<?= $rank + 1 ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= clean($murid['nama']) ?></td>
                        <td><code class="small"><?= clean($murid['no_pendaftaran']) ?></code></td>
                        <td class="text-center"><?= (int)$murid['bil_subjek'] ?></td>
                        <td class="text-center fw-bold"><?= number_format((float)$murid['jumlah_markah'], 1) ?></td>
                        <td class="text-center fw-bold"><?= number_format((float)$murid['purata_markah'], 1) ?></td>
                        <td class="text-center"><?= number_format((float)$murid['purata_gpa'], 2) ?></td>
                        <td class="text-center">
                            <?php if ($murid['gred_dominan']): ?>
                                <span class="badge fw-bold px-2 py-1"
                                      style="background-color:<?= $gredWarna ?>;color:#fff">
                                    <?= clean($murid['gred_dominan']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.tab-content -->

<?php
// Helper: cari label kelas dari senarai
function collect_kelas_label(array $list, int $id): string {
    foreach ($list as $item) {
        if ((int)$item['id'] === $id) return htmlspecialchars($item['label']);
    }
    return '#' . $id;
}
?>

<?php
$extraScript = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Simpan tab aktif dalam URL tanpa reload
function setTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
}

document.addEventListener('DOMContentLoaded', function () {

    // ── Chart Tab 1: Bar Antara Kelas ─────────────────────────────────────
    const canvasKelas = document.getElementById('chartKelas');
    if (canvasKelas) {
        new Chart(canvasKelas, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartKelasLabel) ?>,
                datasets: [{
                    label: 'Purata Markah',
                    data: <?= json_encode($chartKelasMarkah) ?>,
                    backgroundColor: <?= json_encode($chartKelasWarnaFinal) ?>,
                    borderRadius: 4,
                    borderWidth: 1,
                    borderColor: <?= json_encode(array_map(fn($c) => str_replace('0.85)','1)',$c), $chartKelasWarnaFinal)) ?>,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' Purata: ' + ctx.parsed.y.toFixed(1)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: <?= !empty($chartKelasMarkah) ? max(0, min($chartKelasMarkah) - 10) : 0 ?>,
                        max: 100,
                        title: { display: true, text: 'Markah' }
                    },
                    x: {
                        title: { display: true, text: 'Kelas' }
                    }
                }
            }
        });
    }

    // ── Chart Tab 2: Line Trend Penggal ───────────────────────────────────
    const canvasTrend = document.getElementById('chartTrend');
    if (canvasTrend) {
        const datasets = <?= json_encode($chartTrendDatasets) ?>;
        if (datasets.length > 0) {
            new Chart(canvasTrend, {
                type: 'line',
                data: {
                    labels: <?= json_encode($penggalUrutan) ?>,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.dataset.label + ': ' + (ctx.parsed.y !== null ? ctx.parsed.y.toFixed(1) : '—')
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 0,
                            max: 100,
                            title: { display: true, text: 'Purata Markah' }
                        },
                        x: {
                            title: { display: true, text: 'Penilaian' }
                        }
                    }
                }
            });
        }
    }
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
