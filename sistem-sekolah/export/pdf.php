<?php
// ============================================================
// EXPORT PDF — menggunakan HTML print / mPDF fallback
// Generate printable HTML for browser PDF save or direct print
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$modul = $_GET['modul'] ?? '';
$id    = (int)($_GET['id'] ?? 0);
$tahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);
$kelas = (int)($_GET['kelas_id'] ?? 0);

$namaSekolah = getSetting('nama_sekolah');
$alamatSekolah = getSetting('alamat_sekolah');

// Helper: Print wrapper
function pdfHeader(string $tajuk): void {
    global $namaSekolah, $alamatSekolah;
    echo '<!DOCTYPE html><html lang="ms"><head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($tajuk) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 18mm 15mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 11pt; color: #111; }
        .header-sekolah { text-align: center; border-bottom: 3px double #1d4ed8; padding-bottom: 10px; margin-bottom: 16px; }
        .header-sekolah h1 { font-size: 14pt; color: #1d4ed8; font-weight: 700; margin: 0; }
        .header-sekolah p { font-size: 9pt; color: #555; margin: 2px 0 0; }
        .doc-title { text-align: center; font-size: 13pt; font-weight: 700; margin: 10px 0 16px;
            text-transform: uppercase; letter-spacing: .03em; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        th { background: #eff6ff; color: #1e40af; font-weight: 600; }
        tr:nth-child(even) td { background: #f8fafc; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
        .info-item { display: flex; gap: 8px; }
        .info-label { font-weight: 600; min-width: 160px; color: #374151; }
        .info-value { flex: 1; }
        .section-title { font-weight: 700; font-size: 11pt; color: #1d4ed8; border-left: 4px solid #2563eb;
            padding-left: 8px; margin: 14px 0 8px; }
        .badge-sp { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 9pt;
            font-weight: 600; background: #dbeafe; color: #1e40af; }
        .photo-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 10px 0; }
        .photo-box { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .photo-box img { width: 100%; height: 140px; object-fit: cover; display: block; }
        .photo-caption { font-size: 9pt; color: #555; text-align: center; padding: 4px; background: #f8fafc; }
        .sign-row { display: flex; justify-content: space-between; margin-top: 30px; }
        .sign-box { text-align: center; width: 220px; }
        .sign-line { border-top: 1px solid #374151; margin-top: 40px; padding-top: 4px; font-size: 9pt; }
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

function pdfFooter(): void {
    echo '<div style="margin-top:20px;text-align:center;font-size:8pt;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:8px">
        Dicetak pada ' . date('d/m/Y H:i') . ' &mdash; Sistem Pengurusan Sekolah
    </div></body></html>';
}

// ============================================================
// OPR — One Page Report
// ============================================================
if ($modul === 'opr') {
    $opr = dbFetch("SELECT o.*, g.nama AS nama_guru FROM opr o
        LEFT JOIN guru g ON g.id=o.guru_id WHERE o.id=?", [$id]);
    if (!$opr) { echo 'Rekod tidak ditemui.'; exit; }

    pdfHeader('LAPORAN RINGKAS AKTIVITI (OPR)');
    ?>
    <div class="info-grid">
        <div class="info-item"><span class="info-label">Tajuk Aktiviti</span><span class="info-value"><?= htmlspecialchars($opr['tajuk']) ?></span></div>
        <div class="info-item"><span class="info-label">Tarikh</span><span class="info-value"><?= formatTarikh($opr['tarikh_aktiviti']) ?></span></div>
        <div class="info-item"><span class="info-label">Kategori</span><span class="info-value"><?= htmlspecialchars($opr['kategori']) ?></span></div>
        <div class="info-item"><span class="info-label">Tempat</span><span class="info-value"><?= htmlspecialchars($opr['tempat'] ?? '-') ?></span></div>
        <div class="info-item"><span class="info-label">Pegawai Bertanggungjawab</span><span class="info-value"><?= htmlspecialchars($opr['pegawai_bertanggungjawab'] ?? '-') ?></span></div>
        <div class="info-item"><span class="info-label">Disediakan Oleh</span><span class="info-value"><?= htmlspecialchars($opr['nama_guru'] ?? '-') ?></span></div>
    </div>

    <?php if ($opr['penerangan_umum']): ?>
    <div class="section-title">Laporan Ringkas</div>
    <p><?= nl2br(htmlspecialchars($opr['penerangan_umum'])) ?></p>
    <?php endif; ?>

    <?php if ($opr['penerangan_lanjut']): ?>
    <div class="section-title">Penerangan Lanjut</div>
    <p><?= nl2br(htmlspecialchars($opr['penerangan_lanjut'])) ?></p>
    <?php endif; ?>

    <?php if ($opr['impak']): ?>
    <div class="section-title">Impak</div>
    <p><?= nl2br(htmlspecialchars($opr['impak'])) ?></p>
    <?php endif; ?>

    <?php if ($opr['saranan']): ?>
    <div class="section-title">Saranan / Cadangan</div>
    <p><?= nl2br(htmlspecialchars($opr['saranan'])) ?></p>
    <?php endif; ?>

    <?php
    $hasPhoto = $opr['gambar1'] || $opr['gambar2'] || $opr['gambar3'];
    if ($hasPhoto): ?>
    <div class="section-title">Gambar Aktiviti</div>
    <div class="photo-row">
        <?php foreach ([1,2,3] as $n):
            $img = $opr["gambar$n"];
            $kap = $opr["gambar{$n}_kapsyen"] ?? '';
        ?>
        <div class="photo-box">
            <?php if ($img && file_exists(ROOT_PATH . '/' . $img)): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($img) ?>" alt="Gambar <?= $n ?>">
            <?php else: ?>
            <div style="height:140px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:24pt">&#128247;</div>
            <?php endif; ?>
            <div class="photo-caption"><?= htmlspecialchars($kap ?: 'Gambar ' . $n) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line">Disediakan Oleh<br><strong><?= htmlspecialchars($opr['pegawai_bertanggungjawab'] ?? '_______________') ?></strong></div>
        </div>
        <div class="sign-box">
            <div class="sign-line">Disahkan Oleh<br><strong>Pengetua / Guru Besar</strong></div>
        </div>
    </div>
    <?php
    pdfFooter();
}

// ============================================================
// ERPH
// ============================================================
elseif ($modul === 'erph') {
    $erph = dbFetch("SELECT e.*, g.nama AS nama_guru, k.nama_kelas, k.tingkatan
        FROM erph e
        LEFT JOIN guru g ON g.id=e.guru_id
        LEFT JOIN kelas k ON k.id=e.kelas_id
        WHERE e.id=?", [$id]);
    if (!$erph) { echo 'Rekod tidak ditemui.'; exit; }

    $muridHadir = dbFetchAll("SELECT m.nama, m.jantina, m.no_ic
        FROM erph_murid em JOIN murid m ON m.id=em.murid_id
        WHERE em.erph_id=? AND em.hadir=1 ORDER BY m.nama", [$id]);

    pdfHeader('EVIDENS REKOD PENGAJARAN & HASIL (ERPH)');
    ?>
    <div class="info-grid">
        <div class="info-item"><span class="info-label">No. Rujukan</span><span class="info-value"><?= htmlspecialchars($erph['no_rujukan'] ?? '-') ?></span></div>
        <div class="info-item"><span class="info-label">Tarikh</span><span class="info-value"><?= formatTarikh($erph['tarikh']) ?><?= $erph['masa_mula'] ? ' | ' . substr($erph['masa_mula'],0,5) . ' - ' . substr($erph['masa_tamat'],0,5) : '' ?></span></div>
        <div class="info-item"><span class="info-label">Guru</span><span class="info-value"><?= htmlspecialchars($erph['nama_guru'] ?? '-') ?></span></div>
        <div class="info-item"><span class="info-label">Kelas</span><span class="info-value"><?= htmlspecialchars($erph['tingkatan'] . ' ' . $erph['nama_kelas']) ?></span></div>
        <div class="info-item"><span class="info-label">Mata Pelajaran</span><span class="info-value"><?= htmlspecialchars($erph['nama_subjek'] ?? '-') ?></span></div>
        <div class="info-item"><span class="info-label">Bil. Murid Hadir</span><span class="info-value"><?= $erph['bil_murid_hadir'] ?> orang</span></div>
    </div>

    <div class="section-title">Standard Kurikulum (DSKP)</div>
    <table>
        <tr><th width="200">Standard Kandungan</th><td><?= nl2br(htmlspecialchars($erph['standard_kandungan'] ?? '-')) ?></td></tr>
        <tr><th>Standard Pembelajaran</th><td><?= nl2br(htmlspecialchars($erph['standard_pembelajaran'] ?? '-')) ?></td></tr>
        <tr><th>Standard Prestasi</th><td><span class="badge-sp"><?= htmlspecialchars($erph['standard_prestasi'] ?? '-') ?></span></td></tr>
    </table>

    <div class="section-title">Maklumat PdP</div>
    <table>
        <tr><th width="200">Tajuk Pelajaran</th><td><?= htmlspecialchars($erph['tajuk']) ?></td></tr>
        <tr><th>Evidens Pengajaran</th><td><?= nl2br(htmlspecialchars($erph['evidens'] ?? '-')) ?></td></tr>
        <tr><th>Hasil Pembelajaran</th><td><?= nl2br(htmlspecialchars($erph['hasil_pembelajaran'] ?? '-')) ?></td></tr>
        <tr><th>Pendekatan PdP</th><td><?= nl2br(htmlspecialchars($erph['pendekatan_pdp'] ?? '-')) ?></td></tr>
        <tr><th>Refleksi</th><td><?= nl2br(htmlspecialchars($erph['refleksi'] ?? '-')) ?></td></tr>
        <tr><th>Tindakan Susulan</th><td><?= nl2br(htmlspecialchars($erph['tindakan_susulan'] ?? '-')) ?></td></tr>
    </table>

    <?php if ($muridHadir): ?>
    <div class="section-title">Senarai Murid Hadir (<?= count($muridHadir) ?> orang)</div>
    <table>
        <thead><tr><th>Bil</th><th>Nama Murid</th><th>Jantina</th><th>No. IC</th></tr></thead>
        <tbody>
            <?php foreach ($muridHadir as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($m['nama']) ?></td>
                <td><?= $m['jantina'] ?></td>
                <td><?= htmlspecialchars($m['no_ic'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line">Disediakan Oleh<br><strong><?= htmlspecialchars($erph['nama_guru'] ?? '_______________') ?></strong></div>
        </div>
        <div class="sign-box">
            <div class="sign-line">Disahkan Oleh<br><strong>Pengetua / Guru Besar</strong></div>
        </div>
    </div>
    <?php
    pdfFooter();
}

// ============================================================
// SENARAI MURID
// ============================================================
elseif ($modul === 'murid') {
    $kelasInfo = $kelas ? dbFetch("SELECT nama_kelas, tingkatan FROM kelas WHERE id=?", [$kelas]) : null;
    $whereClause = "m.tahun=?";
    $params = [$tahun];
    if ($kelas) { $whereClause .= " AND m.kelas_id=?"; $params[] = $kelas; }
    $muridList = dbFetchAll("SELECT m.*, k.nama_kelas, k.tingkatan
        FROM murid m LEFT JOIN kelas k ON k.id=m.kelas_id
        WHERE $whereClause AND m.status='aktif' ORDER BY k.tingkatan, k.nama_kelas, m.nama", $params);

    $tajuk = 'SENARAI MURID' . ($kelasInfo ? ' — ' . $kelasInfo['tingkatan'] . ' ' . $kelasInfo['nama_kelas'] : '') . ' TAHUN ' . $tahun;
    pdfHeader($tajuk);
    ?>
    <p style="font-size:9pt;color:#666;margin-bottom:10px">Jumlah: <?= count($muridList) ?> orang |
        Lelaki: <?= count(array_filter($muridList, fn($m) => $m['jantina'] === 'L')) ?> |
        Perempuan: <?= count(array_filter($muridList, fn($m) => $m['jantina'] === 'P')) ?>
    </p>
    <table>
        <thead>
            <tr><th>Bil</th><th>Nama Murid</th><th>No. IC</th><th>Jantina</th><th>Kelas</th><th>Telefon IB</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($muridList as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($m['nama']) ?></td>
                <td><?= htmlspecialchars($m['no_ic'] ?? '-') ?></td>
                <td><?= $m['jantina'] ?></td>
                <td><?= htmlspecialchars($m['tingkatan'] . ' ' . $m['nama_kelas']) ?></td>
                <td><?= htmlspecialchars($m['telefon_ibu_bapa'] ?? '-') ?></td>
                <td><?= $m['status'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    pdfFooter();
}

// ============================================================
// SENARAI GURU
// ============================================================
elseif ($modul === 'guru') {
    $guruList = dbFetchAll("SELECT * FROM guru WHERE status='aktif' ORDER BY nama");
    pdfHeader('SENARAI GURU & STAF');
    ?>
    <p style="font-size:9pt;color:#666;margin-bottom:10px">Jumlah: <?= count($guruList) ?> orang</p>
    <table>
        <thead>
            <tr><th>Bil</th><th>Nama</th><th>No. Pekerja</th><th>Jawatan/Gred</th><th>Opsyen</th><th>Telefon</th></tr>
        </thead>
        <tbody>
            <?php foreach ($guruList as $i => $g): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($g['nama']) ?></td>
                <td><?= htmlspecialchars($g['no_pekerja'] ?? '-') ?></td>
                <td><?= htmlspecialchars(($g['jawatan'] ?? '') . ' ' . ($g['gred'] ?? '')) ?></td>
                <td><?= htmlspecialchars($g['opsyen'] ?? '-') ?></td>
                <td><?= htmlspecialchars($g['telefon'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    pdfFooter();
}

else {
    echo '<p style="padding:20px;color:red">Modul export tidak dikenali: ' . htmlspecialchars($modul) . '</p>';
}
