<?php
// ============================================================
// EXPORT CSV/EXCEL — Rekod Prestasi / Markah
// Params: tahun, kelas_id (optional), murid_id (optional),
//         jenis_penilaian, penggal (optional), subjek_id (optional)
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$tahun          = (int)($_GET['tahun']           ?? TAHUN_SEMASA);
$kelasId        = (int)($_GET['kelas_id']        ?? 0);
$muridId        = (int)($_GET['murid_id']        ?? 0);
$jenisPenilaian = clean($_GET['jenis_penilaian'] ?? '');
$penggal        = (int)($_GET['penggal']         ?? 0);
$subjekId       = (int)($_GET['subjek_id']       ?? 0);

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = ['p.tahun = ?'];
$params = [$tahun];

if ($muridId > 0) {
    $where[]  = 'p.murid_id = ?';
    $params[] = $muridId;
}
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

$whereStr = 'WHERE ' . implode(' AND ', $where);

// ── Fetch data ────────────────────────────────────────────────────────────────
$rows = dbFetchAll(
    "SELECT
        m.nama         AS nama_murid,
        m.no_ic,
        k.tingkatan,
        k.nama_kelas,
        p.nama_subjek,
        p.jenis_penilaian,
        p.penggal,
        p.markah,
        p.gred,
        p.nilai_gred,
        p.band,
        p.tahun
     FROM prestasi p
     LEFT JOIN murid  m ON m.id = p.murid_id
     LEFT JOIN kelas  k ON k.id = m.kelas_id
     $whereStr
     ORDER BY k.tingkatan, k.nama_kelas, m.nama, p.nama_subjek",
    $params
);

// ── Build filename ────────────────────────────────────────────────────────────
$namaFail = 'prestasi';

if ($jenisPenilaian !== '') {
    $namaFail .= '_' . preg_replace('/[^A-Za-z0-9]/', '', $jenisPenilaian);
}

$namaFail .= '_' . $tahun;

if ($kelasId > 0) {
    $kelasInfo = dbFetch("SELECT tingkatan, nama_kelas FROM kelas WHERE id = ?", [$kelasId]);
    if ($kelasInfo) {
        $kelasSlug = preg_replace('/[^A-Za-z0-9]/', '',
            $kelasInfo['tingkatan'] . $kelasInfo['nama_kelas']);
        $namaFail .= '_' . $kelasSlug;
    }
}

if ($muridId > 0) {
    $muridInfo = dbFetch("SELECT nama FROM murid WHERE id = ?", [$muridId]);
    if ($muridInfo) {
        $muridSlug = preg_replace('/[^A-Za-z0-9]/', '', $muridInfo['nama']);
        $namaFail .= '_' . substr($muridSlug, 0, 20);
    }
}

$namaFail .= '_' . date('Ymd') . '.csv';

// ── Output headers ────────────────────────────────────────────────────────────
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $namaFail . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 supaya Excel baca dengan betul
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// ── Header baris CSV ──────────────────────────────────────────────────────────
fputcsv($output, [
    'Bil',
    'Nama Murid',
    'No IC',
    'Kelas',
    'Mata Pelajaran',
    'Jenis Penilaian',
    'Penggal',
    'Markah',
    'Gred',
    'Nilai GPA',
    'Band',
    'Tahun',
]);

// ── Data rows ─────────────────────────────────────────────────────────────────
$bil = 1;
foreach ($rows as $r) {
    $kelasLabel = trim(($r['tingkatan'] ?? '') . ' ' . ($r['nama_kelas'] ?? ''));
    fputcsv($output, [
        $bil++,
        $r['nama_murid']      ?? '',
        $r['no_ic']           ?? '',
        $kelasLabel           ?: '—',
        $r['nama_subjek']     ?? '',
        $r['jenis_penilaian'] ?? '',
        $r['penggal']         ?? '',
        $r['markah']          ?? '',
        $r['gred']            ?? '',
        $r['nilai_gred']      ?? '',
        $r['band']            ?? '',
        $r['tahun']           ?? '',
    ]);
}

fclose($output);

// ── Log aktiviti ──────────────────────────────────────────────────────────────
$keterangan = 'Export Excel prestasi'
    . ($jenisPenilaian ? ' [' . $jenisPenilaian . ']' : '')
    . ' tahun ' . $tahun
    . ($kelasId > 0 ? ' kelas_id=' . $kelasId : '')
    . ($muridId > 0 ? ' murid_id=' . $muridId : '')
    . ' (' . count($rows) . ' rekod)';

logAktiviti('export', 'prestasi', null, $keterangan);
