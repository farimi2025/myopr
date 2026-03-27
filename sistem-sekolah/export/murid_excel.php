<?php
// ============================================================
// EXPORT EXCEL (CSV) — SENARAI MURID
// Muat turun fail CSV yang boleh dibuka dengan Microsoft Excel
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$tahun   = (int)($_GET['tahun']    ?? TAHUN_SEMASA);
$kelasId = (int)($_GET['kelas_id'] ?? 0);
$jantina = clean($_GET['jantina']  ?? '');
$status  = clean($_GET['status']   ?? '');
$cari    = clean($_GET['cari']     ?? '');

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
    "SELECT m.no_pendaftaran, m.nama, m.no_ic, m.jantina, m.tarikh_lahir,
            m.bangsa, m.agama, m.alamat, m.telefon_ibu_bapa,
            m.nama_ibu_bapa, m.hubungan, m.email_ibu_bapa,
            m.status, m.tahun,
            k.tingkatan, k.nama_kelas
     FROM murid m
     LEFT JOIN kelas k ON k.id = m.kelas_id
     WHERE $whereStr
     ORDER BY k.tingkatan, k.nama_kelas, m.nama",
    $params
);

// Nama fail
$namaFail = 'senarai_murid_' . $tahun;
if ($kelasId) {
    $kelas = dbFetch("SELECT tingkatan, nama_kelas FROM kelas WHERE id=?", [$kelasId]);
    if ($kelas) $namaFail .= '_' . $kelas['tingkatan'] . $kelas['nama_kelas'];
}
$namaFail = preg_replace('/\s+/', '_', $namaFail) . '_' . date('Ymd') . '.csv';

// Header HTTP untuk muat turun
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $namaFail . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM untuk Excel membuka dengan betul (UTF-8)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Baris maklumat
fputcsv($output, ['SENARAI MURID — TAHUN ' . $tahun], ',');
fputcsv($output, ['Dicetak pada: ' . date('d/m/Y H:i')], ',');
fputcsv($output, ['Jumlah rekod: ' . count($muridList)], ',');
fputcsv($output, []); // baris kosong

// Pengepala jadual
fputcsv($output, [
    'Bil',
    'No. Pendaftaran',
    'Nama Murid',
    'No. IC',
    'Jantina',
    'Tarikh Lahir',
    'Bangsa',
    'Agama',
    'Kelas',
    'Tingkatan',
    'Alamat',
    'Nama IB/PJ',
    'Hubungan',
    'Telefon IB/PJ',
    'E-mel IB/PJ',
    'Status',
    'Tahun',
]);

// Data murid
foreach ($muridList as $i => $m) {
    fputcsv($output, [
        $i + 1,
        $m['no_pendaftaran'] ?? '',
        $m['nama'],
        $m['no_ic'] ?? '',
        $m['jantina'] === 'L' ? 'Lelaki' : 'Perempuan',
        $m['tarikh_lahir'] ? date('d/m/Y', strtotime($m['tarikh_lahir'])) : '',
        $m['bangsa'] ?? '',
        $m['agama'] ?? '',
        ($m['tingkatan'] ?? '') . ' ' . ($m['nama_kelas'] ?? ''),
        $m['tingkatan'] ?? '',
        $m['alamat'] ?? '',
        $m['nama_ibu_bapa'] ?? '',
        $m['hubungan'] ?? '',
        $m['telefon_ibu_bapa'] ?? '',
        $m['email_ibu_bapa'] ?? '',
        ucfirst($m['status']),
        $m['tahun'],
    ]);
}

fclose($output);

logAktiviti('export', 'murid', null, 'Export senarai murid Excel tahun ' . $tahun);
