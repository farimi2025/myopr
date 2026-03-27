<?php
// ============================================================
// EXPORT PDF — SENARAI MURID
// Wrapper yang menghantar ke export/pdf.php?modul=murid
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$tahun    = (int)($_GET['tahun']    ?? TAHUN_SEMASA);
$kelasId  = (int)($_GET['kelas_id'] ?? 0);
$jantina  = clean($_GET['jantina']  ?? '');
$status   = clean($_GET['status']   ?? 'aktif');
$cari     = clean($_GET['cari']     ?? '');

$namaSekolah  = getSetting('nama_sekolah');
$alamatSekolah = getSetting('alamat_sekolah');

// Bina WHERE clause
$where  = ['m.tahun = ?'];
$params = [$tahun];

if ($kelasId) { $where[] = 'm.kelas_id = ?'; $params[] = $kelasId; }
if ($jantina) { $where[] = 'm.jantina = ?';  $params[] = $jantina; }
if ($status !== '')  { $where[] = 'm.status = ?';   $params[] = $status; }
if ($cari !== '') {
    $where[]  = '(m.nama LIKE ? OR m.no_ic LIKE ? OR m.no_pendaftaran LIKE ?)';
    $params[] = "%$cari%"; $params[] = "%$cari%"; $params[] = "%$cari%";
}

$whereStr = implode(' AND ', $where);
$muridList = dbFetchAll(
    "SELECT m.*, k.nama_kelas, k.tingkatan
     FROM murid m
     LEFT JOIN kelas k ON k.id = m.kelas_id
     WHERE $whereStr
     ORDER BY k.tingkatan, k.nama_kelas, m.nama",
    $params
);

$kelasInfo = $kelasId ? dbFetch("SELECT nama_kelas, tingkatan FROM kelas WHERE id=?", [$kelasId]) : null;
$tajuk = 'SENARAI MURID'
    . ($kelasInfo ? ' — ' . $kelasInfo['tingkatan'] . ' ' . $kelasInfo['nama_kelas'] : '')
    . ' TAHUN ' . $tahun;

$jumlahL = count(array_filter($muridList, fn($m) => $m['jantina'] === 'L'));
$jumlahP = count(array_filter($muridList, fn($m) => $m['jantina'] === 'P'));
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($tajuk) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    @page { size: A4 landscape; margin: 14mm 12mm; }
    body  { font-family: "Segoe UI", Arial, sans-serif; font-size: 10.5pt; color: #111; }
    .header-sekolah { text-align: center; border-bottom: 3px double #1d4ed8; padding-bottom: 8px; margin-bottom: 14px; }
    .header-sekolah h1 { font-size: 13pt; color: #1d4ed8; font-weight: 700; margin: 0; }
    .header-sekolah p  { font-size: 8.5pt; color: #555; margin: 2px 0 0; }
    .doc-title { text-align: center; font-size: 12pt; font-weight: 700; margin: 8px 0 14px; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
    th, td { border: 1px solid #cbd5e1; padding: 5px 7px; }
    th { background: #eff6ff; color: #1e40af; font-weight: 600; }
    tr:nth-child(even) td { background: #f8fafc; }
    .stat-row { display: flex; gap: 16px; margin-bottom: 10px; font-size: 9.5pt; color: #374151; }
    .btn-print { position: fixed; bottom: 20px; right: 20px; background: #2563eb; color: #fff;
        border: none; border-radius: 8px; padding: 10px 20px; font-size: 11pt; cursor: pointer;
        box-shadow: 0 4px 12px rgba(37,99,235,.4); z-index: 999; }
    @media print { .no-print { display: none !important; } body { margin: 0; } }
</style>
</head>
<body>
<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>

<div class="header-sekolah">
    <h1><?= htmlspecialchars($namaSekolah) ?></h1>
    <p><?= htmlspecialchars($alamatSekolah) ?></p>
</div>
<div class="doc-title"><?= htmlspecialchars($tajuk) ?></div>

<div class="stat-row">
    <span><strong>Jumlah:</strong> <?= count($muridList) ?> orang</span>
    <span><strong>Lelaki:</strong> <?= $jumlahL ?></span>
    <span><strong>Perempuan:</strong> <?= $jumlahP ?></span>
    <?php if ($jantina): ?><span><strong>Jantina:</strong> <?= $jantina === 'L' ? 'Lelaki' : 'Perempuan' ?></span><?php endif; ?>
    <?php if ($cari): ?><span><strong>Carian:</strong> "<?= htmlspecialchars($cari) ?>"</span><?php endif; ?>
    <span style="margin-left:auto;color:#9ca3af">Dicetak: <?= date('d/m/Y H:i') ?></span>
</div>

<table>
    <thead>
        <tr>
            <th width="30">Bil</th>
            <th>Nama Murid</th>
            <th>No. Pendaftaran</th>
            <th>No. IC</th>
            <th width="50">Jantina</th>
            <th>Kelas</th>
            <th>Bangsa</th>
            <th>Telefon IB</th>
            <th width="65">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($muridList): ?>
        <?php foreach ($muridList as $i => $m): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($m['nama']) ?></td>
            <td><?= htmlspecialchars($m['no_pendaftaran'] ?? '-') ?></td>
            <td><?= htmlspecialchars($m['no_ic'] ?? '-') ?></td>
            <td class="text-center"><?= $m['jantina'] === 'L' ? 'L' : 'P' ?></td>
            <td><?= htmlspecialchars(($m['tingkatan'] ?? '') . ' ' . ($m['nama_kelas'] ?? '')) ?></td>
            <td><?= htmlspecialchars($m['bangsa'] ?? '-') ?></td>
            <td><?= htmlspecialchars($m['telefon_ibu_bapa'] ?? '-') ?></td>
            <td><?= htmlspecialchars(ucfirst($m['status'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="9" class="text-center text-muted py-3">Tiada rekod murid ditemui.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div style="margin-top:18px;display:flex;justify-content:space-between;font-size:9pt;color:#374151">
    <div style="text-align:center;width:220px">
        <div style="border-top:1px solid #374151;margin-top:40px;padding-top:4px">
            Disediakan Oleh<br><strong>___________________________</strong>
        </div>
    </div>
    <div style="text-align:center;width:220px">
        <div style="border-top:1px solid #374151;margin-top:40px;padding-top:4px">
            Disahkan Oleh<br><strong>Pengetua / Guru Besar</strong>
        </div>
    </div>
</div>

<div style="margin-top:12px;text-align:center;font-size:7.5pt;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:6px">
    Dicetak pada <?= date('d/m/Y H:i') ?> &mdash; Sistem Pengurusan Sekolah
</div>
</body>
</html>
