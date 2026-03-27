<?php
// ============================================================
// AJAX: Auto-save draf borang
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mesej' => 'Method tidak sah']);
    exit;
}

// Semak CSRF
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!hash_equals(csrfToken(), $token)) {
    echo json_encode(['ok' => false, 'mesej' => 'Token tidak sah']);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$modul   = preg_replace('/[^a-z_]/', '', strtolower($_POST['modul'] ?? ''));
$rekodId = (int)($_POST['rekod_id'] ?? 0);

if (!$modul) {
    echo json_encode(['ok' => false, 'mesej' => 'Modul tidak dinyatakan']);
    exit;
}

// Kumpul semua data form (kecuali fail dan csrf)
$skip = ['csrf_token', 'action', 'modul', 'rekod_id', 'MAX_FILE_SIZE'];
$data = [];
foreach ($_POST as $k => $v) {
    if (!in_array($k, $skip)) {
        $data[$k] = is_array($v) ? $v : trim($v);
    }
}

try {
    // Upsert autosave
    $pdo = dbConnect();
    $stmt = $pdo->prepare("INSERT INTO autosave (user_id, modul, rekod_id, data) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE data=VALUES(data), updated_at=NOW()");
    $stmt->execute([$userId, $modul, $rekodId, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['ok' => true, 'masa' => date('H:i:s')]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mesej' => 'Gagal simpan: ' . $e->getMessage()]);
}
