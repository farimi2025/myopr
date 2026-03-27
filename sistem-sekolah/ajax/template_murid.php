<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Set headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="template_import_murid.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 supaya Excel buka dengan betul
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Baris 1: Header kolum
fputcsv($output, [
    'nama',
    'no_ic',
    'jantina',
    'tarikh_lahir',
    'bangsa',
    'agama',
    'alamat',
    'poskod',
    'bandar',
    'negeri_murid',
    'telefon_ibu_bapa',
    'nama_ibu_bapa',
    'hubungan',
    'kelas_id',
    'catatan'
]);

// Baris 2-4: Contoh data
fputcsv($output, [
    'AHMAD BIN ALI',
    '100101-14-5432',
    'L',
    '2010-01-15',
    'Melayu',
    'Islam',
    'No. 12, Jalan Mawar, Taman Indah',
    '50000',
    'Kuala Lumpur',
    'Wilayah Persekutuan',
    '012-3456789',
    'ALI BIN HASSAN',
    'Bapa',
    '',
    ''
]);

fputcsv($output, [
    'SITI BINTI RAHMAN',
    '100505-10-1234',
    'P',
    '2010-05-20',
    'Melayu',
    'Islam',
    'No. 5, Jalan Cempaka, Taman Sejahtera',
    '41000',
    'Klang',
    'Selangor',
    '016-9876543',
    'RAHMAN BIN YUSOF',
    'Bapa',
    '',
    'Murid pindahan'
]);

fputcsv($output, [
    'LEE WEI MING',
    '100808-08-5678',
    'L',
    '2010-08-30',
    'Cina',
    'Buddha',
    'No. 88, Jalan Pusat, Taman Maju',
    '10000',
    'Georgetown',
    'Pulau Pinang',
    '04-1234567',
    'LEE CHENG HOCK',
    'Bapa',
    '',
    ''
]);

fclose($output);
exit;
