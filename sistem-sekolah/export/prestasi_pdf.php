<?php
// ============================================================
// EXPORT PDF — Slip Markah Murid / Kelas
// Mode 1: ?murid_id=X&tahun=Y&jenis_penilaian=PT1
// Mode 2: ?kelas_id=X&tahun=Y&jenis_penilaian=PT1
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$muridId        = (int)($_GET['murid_id']       ?? 0);
$kelasId        = (int)($_GET['kelas_id']        ?? 0);
$tahun          = (int)($_GET['tahun']           ?? TAHUN_SEMASA);
$jenisPenilaian = clean($_GET['jenis_penilaian'] ?? '');
$penggal        = (int)($_GET['penggal']         ?? 0);

$namaSekolah   = getSetting('nama_sekolah');
$alamatSekolah = getSetting('alamat_sekolah');

// ── Validate: perlu murid_id atau kelas_id ────────────────────────────────────
if ($muridId <= 0 && $kelasId <= 0) {
    echo '<p style="padding:20px;color:red">Parameter murid_id atau kelas_id diperlukan.</p>';
    exit;
}

// ── Helper: cetak header HTML ─────────────────────────────────────────────────
function pdfSlipHeader(string $tajuk): void {
    global $namaSekolah, $alamatSekolah;
    echo '<!DOCTYPE html><html lang="ms"><head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($tajuk) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 12mm 15mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 10.5pt; color: #111; }
        .header-sekolah { text-align: center; border-bottom: 3px double #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
        .header-sekolah h1 { font-size: 14pt; color: #1d4ed8; font-weight: 700; margin: 0; }
        .header-sekolah p { font-size: 9pt; color: #555; margin: 2px 0 0; }
        .doc-title { text-align: center; font-size: 12pt; font-weight: 700; margin: 8px 0 14px;
            text-transform: uppercase; letter-spacing: .03em; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 12px;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
        .info-item { display: flex; gap: 6px; font-size: 10pt; }
        .info-label { font-weight: 600; min-width: 140px; color: #374151; }
        .info-value { flex: 1; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        th { background: #eff6ff; color: #1e40af; font-weight: 600; }
        tr:nth-child(even) td { background: #f8fafc; }
        .stats-row { display: flex; gap: 16px; margin: 12px 0; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 120px; border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 8px 12px; text-align: center; background: #f8fafc; }
        .stat-val { font-size: 16pt; font-weight: 700; color: #1d4ed8; }
        .stat-lbl { font-size: 8.5pt; color: #6b7280; }
        .sign-row { display: flex; justify-content: space-between; margin-top: 28px; }
        .sign-box { text-align: center; width: 200px; }
        .sign-line { border-top: 1px solid #374151; margin-top: 40px; padding-top: 4px; font-size: 9pt; }
        .section-title { font-weight: 700; font-size: 10.5pt; color: #1d4ed8;
            border-left: 4px solid #2563eb; padding-left: 8px; margin: 12px 0 8px; }
        .page-break { page-break-after: always; }
        .gred-badge { display: inline-block; padding: 2px 8px; border-radius: 10px;
            font-weight: 700; font-size: 9.5pt; color: #fff; }
        .footer-doc { margin-top: 16px; text-align: center; font-size: 8pt; color: #9ca3af;
            border-top: 1px solid #e5e7eb; padding-top: 6px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        .btn-print { position: fixed; bottom: 20px; right: 20px; background: #2563eb; color: #fff;
            border: none; border-radius: 8px; padding: 10px 20px; font-size: 12pt; cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,.4); z-index: 999; }
    </style>
    </head><body>
    <button class="btn-print no-print" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
    <div class="header-sekolah">
        <h1>' . htmlspecialchars($namaSekolah) . '</h1>
        <p>' . htmlspecialchars($alamatSekolah) . '</p>
    </div>
    <div class="doc-title">' . htmlspecialchars($tajuk) . '</div>';
}

// ── Helper: cetak satu slip murid ─────────────────────────────────────────────
function cetakSlipMurid(array $murid, array $rekodList, string $jenisPenilaian, bool $isLast = false): void {
    $kelasLabel = trim(($murid['tingkatan'] ?? '') . ' ' . ($murid['nama_kelas'] ?? ''));

    $jumlahMarkah = 0;
    $jumlahGpa    = 0.0;
    $bilanganA    = 0;
    $bilanganLulus = 0;
    $bilanganGagal = 0;
    $count        = count($rekodList);

    foreach ($rekodList as $r) {
        $jumlahMarkah += (float)$r['markah'];
        $jumlahGpa    += (float)$r['nilai_gred'];
        if (strpos($r['gred'], 'A') === 0) {
            $bilanganA++;
        }
        if ((float)$r['markah'] >= 40) {
            $bilanganLulus++;
        } else {
            $bilanganGagal++;
        }
    }

    $purataMarkah = $count > 0 ? round($jumlahMarkah / $count, 2) : 0;
    $gpaKseluruhan = $count > 0 ? round($jumlahGpa / $count, 2) : 0;

    $gredWarnaPeta = [
        'A+' => '#15803d', 'A'  => '#16a34a', 'A-' => '#22c55e',
        'B+' => '#0284c7', 'B'  => '#0ea5e9', 'B-' => '#38bdf8',
        'C+' => '#d97706', 'C'  => '#f59e0b', 'C-' => '#fbbf24',
        'D'  => '#dc2626', 'E'  => '#b91c1c', 'G'  => '#6b7280',
    ];
    ?>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Nama Murid</span>
            <span class="info-value"><?= htmlspecialchars($murid['nama']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">No. Kad Pengenalan</span>
            <span class="info-value"><?= htmlspecialchars($murid['no_ic'] ?? '—') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">No. Pendaftaran</span>
            <span class="info-value"><?= htmlspecialchars($murid['no_pendaftaran'] ?? '—') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Kelas</span>
            <span class="info-value"><?= htmlspecialchars($kelasLabel ?: '—') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Jenis Penilaian</span>
            <span class="info-value"><?= htmlspecialchars($jenisPenilaian ?: 'Semua') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Tahun</span>
            <span class="info-value"><?= htmlspecialchars((string)($murid['tahun'] ?? '—')) ?></span>
        </div>
    </div>

    <div class="section-title">Keputusan Peperiksaan</div>

    <?php if (empty($rekodList)): ?>
    <p style="color:#6b7280;font-style:italic;padding:10px 0">Tiada rekod markah dijumpai untuk penilaian ini.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:40px">Bil</th>
                <th>Mata Pelajaran</th>
                <th class="text-center" style="width:90px">Markah</th>
                <th class="text-center" style="width:70px">Gred</th>
                <th class="text-center" style="width:80px">Nilai GPA</th>
                <th class="text-center" style="width:60px">Band</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rekodList as $i => $r):
                $warna = $gredWarnaPeta[$r['gred']] ?? '#6b7280';
            ?>
            <tr>
                <td class="text-center text-muted small"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['nama_subjek']) ?></td>
                <td class="text-center fw-semibold"><?= number_format((float)$r['markah'], 1) ?></td>
                <td class="text-center">
                    <span class="gred-badge" style="background-color:<?= $warna ?>">
                        <?= htmlspecialchars($r['gred']) ?>
                    </span>
                </td>
                <td class="text-center"><?= number_format((float)$r['nilai_gred'], 2) ?></td>
                <td class="text-center"><?= htmlspecialchars($r['band'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-val"><?= number_format($purataMarkah, 1) ?></div>
            <div class="stat-lbl">Purata Markah</div>
        </div>
        <div class="stat-box">
            <div class="stat-val"><?= number_format($gpaKseluruhan, 2) ?></div>
            <div class="stat-lbl">GPA Keseluruhan</div>
        </div>
        <div class="stat-box">
            <div class="stat-val" style="color:#16a34a"><?= $bilanganA ?></div>
            <div class="stat-lbl">Bilangan A</div>
        </div>
        <div class="stat-box">
            <div class="stat-val" style="color:#0284c7"><?= $bilanganLulus ?></div>
            <div class="stat-lbl">Lulus</div>
        </div>
        <div class="stat-box">
            <div class="stat-val" style="color:#dc2626"><?= $bilanganGagal ?></div>
            <div class="stat-lbl">Gagal</div>
        </div>
        <div class="stat-box">
            <div class="stat-val"><?= $count ?></div>
            <div class="stat-lbl">Jml. Subjek</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line">
                Guru Kelas<br>
                <strong><?= htmlspecialchars($kelasLabel ?: '_______________') ?></strong>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-line">
                Pengetua / Guru Besar<br>
                <strong><?= htmlspecialchars($namaSekolah) ?></strong>
            </div>
        </div>
    </div>

    <div class="footer-doc">
        Dicetak pada <?= date('d/m/Y H:i') ?> &mdash; Sistem Pengurusan Sekolah
    </div>

    <?php if (!$isLast): ?>
    <div class="page-break"></div>
    <?php endif;
}

// ── Helper: ambil rekod prestasi seorang murid ────────────────────────────────
function getPrestasiMurid(int $muridId, int $tahun, string $jenisPenilaian, int $penggal): array {
    $where  = ['p.murid_id = ?', 'p.tahun = ?'];
    $params = [$muridId, $tahun];

    if ($jenisPenilaian !== '') {
        $where[]  = 'p.jenis_penilaian = ?';
        $params[] = $jenisPenilaian;
    }
    if ($penggal > 0) {
        $where[]  = 'p.penggal = ?';
        $params[] = $penggal;
    }

    $whereStr = 'WHERE ' . implode(' AND ', $where);
    return dbFetchAll(
        "SELECT p.*, s.kod AS subjek_kod
         FROM prestasi p
         LEFT JOIN subjek s ON s.id = p.subjek_id
         $whereStr
         ORDER BY p.nama_subjek",
        $params
    );
}

// ── Mode 1: Slip seorang murid ────────────────────────────────────────────────
if ($muridId > 0) {
    $murid = dbFetch(
        "SELECT m.*, k.nama_kelas, k.tingkatan
         FROM murid m
         LEFT JOIN kelas k ON k.id = m.kelas_id
         WHERE m.id = ?",
        [$muridId]
    );
    if (!$murid) {
        echo '<p style="padding:20px;color:red">Rekod murid tidak dijumpai.</p>';
        exit;
    }

    $rekodList = getPrestasiMurid($muridId, $tahun, $jenisPenilaian, $penggal);

    $tajukSlip = 'SLIP MARKAH MURID'
        . ($jenisPenilaian ? ' — ' . $jenisPenilaian : '')
        . ' TAHUN ' . $tahun;

    pdfSlipHeader($tajukSlip);
    cetakSlipMurid($murid, $rekodList, $jenisPenilaian, true);
    echo '</body></html>';
    exit;
}

// ── Mode 2: Slip semua murid dalam kelas ─────────────────────────────────────
if ($kelasId > 0) {
    $kelasInfo = dbFetch(
        "SELECT id, nama_kelas, tingkatan FROM kelas WHERE id = ?",
        [$kelasId]
    );
    if (!$kelasInfo) {
        echo '<p style="padding:20px;color:red">Rekod kelas tidak dijumpai.</p>';
        exit;
    }

    $muridList = dbFetchAll(
        "SELECT m.*, k.nama_kelas, k.tingkatan
         FROM murid m
         LEFT JOIN kelas k ON k.id = m.kelas_id
         WHERE m.kelas_id = ? AND m.tahun = ? AND m.status = 'aktif'
         ORDER BY m.nama",
        [$kelasId, $tahun]
    );

    if (empty($muridList)) {
        echo '<p style="padding:20px;color:#d97706">Tiada murid aktif dijumpai dalam kelas ini.</p>';
        exit;
    }

    $kelasLabel = $kelasInfo['tingkatan'] . ' ' . $kelasInfo['nama_kelas'];
    $tajukSlip  = 'SLIP MARKAH KELAS ' . strtoupper($kelasLabel)
        . ($jenisPenilaian ? ' — ' . $jenisPenilaian : '')
        . ' TAHUN ' . $tahun;

    pdfSlipHeader($tajukSlip);

    $total = count($muridList);
    foreach ($muridList as $idx => $murid) {
        $rekodList = getPrestasiMurid((int)$murid['id'], $tahun, $jenisPenilaian, $penggal);
        $isLast    = ($idx === $total - 1);
        cetakSlipMurid($murid, $rekodList, $jenisPenilaian, $isLast);
    }

    echo '</body></html>';
    exit;
}
