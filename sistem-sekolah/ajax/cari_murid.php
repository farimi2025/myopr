<?php
// ============================================================
// AJAX: Cari murid untuk Select2 live search (prestasi/tambah.php)
// Returns: { results: [ {id, text}, ... ] }
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

$sql = "SELECT m.id,
        CONCAT(m.nama, ' (', m.no_pendaftaran, ')',
               IF(k.tingkatan IS NOT NULL, CONCAT(' — ', k.tingkatan, ' ', k.nama_kelas), '')) AS text
        FROM murid m
        LEFT JOIN kelas k ON k.id = m.kelas_id
        WHERE m.status = 'aktif'
          AND (m.nama LIKE ? OR m.no_pendaftaran LIKE ? OR m.no_ic LIKE ?)
        ORDER BY m.nama
        LIMIT 40";

$rows = dbFetchAll($sql, ["%$q%", "%$q%", "%$q%"]);

echo json_encode(['results' => $rows]);
