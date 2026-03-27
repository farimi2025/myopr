<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['super_admin', 'pentadbir']);

header('Content-Type: application/json');

$jenis = clean($_GET['jenis'] ?? '');
$nilai = trim($_GET['nilai'] ?? '');
$kecuali = (int)($_GET['kecuali'] ?? 0);

if (!in_array($jenis, ['username','email']) || empty($nilai)) {
    echo json_encode(['guna' => false]);
    exit;
}

if ($jenis === 'username') {
    if ($kecuali) {
        $guna = (bool)dbValue("SELECT id FROM users WHERE username=? AND id!=?", [$nilai, $kecuali]);
    } else {
        $guna = (bool)dbValue("SELECT id FROM users WHERE username=?", [$nilai]);
    }
} else {
    if ($kecuali) {
        $guna = (bool)dbValue("SELECT id FROM users WHERE email=? AND id!=?", [$nilai, $kecuali]);
    } else {
        $guna = (bool)dbValue("SELECT id FROM users WHERE email=?", [$nilai]);
    }
}

echo json_encode(['guna' => $guna]);
