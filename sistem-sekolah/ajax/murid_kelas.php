<?php
// ============================================================
// AJAX: Dapatkan senarai murid dalam sesebuah kelas
// Used by: prestasi/tambah.php (batch mode)
// Returns: { nama_kelas, murid: [ {id, nama, no_pendaftaran}, ... ] }
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$kelasId = (int)($_GET['kelas_id'] ?? 0);

if ($kelasId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'kelas_id diperlukan']);
    exit;
}

$kelas = dbFetch("SELECT id, tingkatan, nama_kelas FROM kelas WHERE id=? LIMIT 1", [$kelasId]);
if (!$kelas) {
    http_response_code(404);
    echo json_encode(['error' => 'Kelas tidak dijumpai']);
    exit;
}

$murid = dbFetchAll(
    "SELECT id, nama, no_pendaftaran, jantina
     FROM murid
     WHERE kelas_id = ? AND status = 'aktif'
     ORDER BY nama",
    [$kelasId]
);

echo json_encode([
    'nama_kelas' => $kelas['tingkatan'] . ' ' . $kelas['nama_kelas'],
    'murid'      => $murid,
]);
