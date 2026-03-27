<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// ── Validate CSRF ─────────────────────────────────────────────────────────────
// Support both GET csrf param and POST body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/murid/index.php');
    }
} else {
    // GET-based action with csrf in URL
    $csrfGet = $_GET['csrf'] ?? '';
    if (!hash_equals(csrfToken(), $csrfGet)) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/murid/index.php');
    }
}

$action = clean($_GET['action'] ?? $_POST['action'] ?? '');
$id     = (int)($_GET['id']     ?? $_POST['id']     ?? 0);

if ($id <= 0) {
    setFlash('danger', 'ID murid tidak sah.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

// ── Fetch record ──────────────────────────────────────────────────────────────
$murid = dbFetch("SELECT id, nama, status FROM murid WHERE id=? LIMIT 1", [$id]);
if (!$murid) {
    setFlash('danger', 'Rekod murid tidak dijumpai.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

// ── Handle actions ────────────────────────────────────────────────────────────
switch ($action) {

    case 'padam':
        // Delete all associated prestasi first
        dbQuery("DELETE FROM prestasi WHERE murid_id=?", [$id]);
        // Delete the student
        dbQuery("DELETE FROM murid WHERE id=?", [$id]);
        logAktiviti('padam', 'murid', $id, 'Padam murid: ' . $murid['nama']);
        setFlash('success', 'Rekod murid <strong>' . clean($murid['nama']) . '</strong> telah berjaya dipadamkan.');
        redirect(BASE_URL . '/modules/murid/index.php');
        break;

    case 'arkib':
        if ($murid['status'] === 'arkib') {
            setFlash('info', 'Murid <strong>' . clean($murid['nama']) . '</strong> sudah dalam status arkib.');
            redirect(BASE_URL . '/modules/murid/index.php');
        }
        dbUpdate('murid', ['status' => 'arkib'], 'id=?', [$id]);
        logAktiviti('arkib', 'murid', $id, 'Arkib murid: ' . $murid['nama']);
        setFlash('success', 'Murid <strong>' . clean($murid['nama']) . '</strong> telah berjaya diarkibkan.');
        // Redirect back to referrer or index
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref && strpos($ref, BASE_URL) === 0) {
            redirect($ref);
        }
        redirect(BASE_URL . '/modules/murid/index.php');
        break;

    case 'aktif':
        if ($murid['status'] === 'aktif') {
            setFlash('info', 'Murid <strong>' . clean($murid['nama']) . '</strong> sudah dalam status aktif.');
            redirect(BASE_URL . '/modules/murid/index.php');
        }
        dbUpdate('murid', ['status' => 'aktif'], 'id=?', [$id]);
        logAktiviti('aktif', 'murid', $id, 'Aktifkan semula murid: ' . $murid['nama']);
        setFlash('success', 'Murid <strong>' . clean($murid['nama']) . '</strong> telah berjaya diaktifkan semula.');
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref && strpos($ref, BASE_URL) === 0) {
            redirect($ref);
        }
        redirect(BASE_URL . '/modules/murid/index.php');
        break;

    case 'berpindah':
        dbUpdate('murid', ['status' => 'berpindah'], 'id=?', [$id]);
        logAktiviti('berpindah', 'murid', $id, 'Tandakan murid berpindah: ' . $murid['nama']);
        setFlash('success', 'Status murid <strong>' . clean($murid['nama']) . '</strong> dikemaskini kepada Berpindah.');
        redirect(BASE_URL . '/modules/murid/index.php');
        break;

    case 'tamat':
        dbUpdate('murid', ['status' => 'tamat'], 'id=?', [$id]);
        logAktiviti('tamat', 'murid', $id, 'Tandakan murid tamat: ' . $murid['nama']);
        setFlash('success', 'Status murid <strong>' . clean($murid['nama']) . '</strong> dikemaskini kepada Tamat.');
        redirect(BASE_URL . '/modules/murid/index.php');
        break;

    default:
        setFlash('danger', 'Tindakan tidak dikenali: ' . clean($action));
        redirect(BASE_URL . '/modules/murid/index.php');
}
