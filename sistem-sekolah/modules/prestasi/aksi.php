<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// ── CSRF Validation ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/prestasi/index.php');
    }
} else {
    $csrfGet = $_GET['csrf'] ?? '';
    if (!hash_equals(csrfToken(), $csrfGet)) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/prestasi/index.php');
    }
}

$action   = clean($_GET['action']   ?? $_POST['action']   ?? '');
$id       = (int)($_GET['id']       ?? $_POST['id']       ?? 0);
$muridId  = (int)($_GET['murid_id'] ?? $_POST['murid_id'] ?? 0);
$redirectTo = clean($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if ($id <= 0) {
    setFlash('danger', 'ID rekod tidak sah.');
    redirect(BASE_URL . '/modules/prestasi/index.php');
}

// ── Fetch record ──────────────────────────────────────────────────────────────
$prestasi = dbFetch(
    "SELECT p.id, p.nama_subjek, p.murid_id, m.nama AS nama_murid
     FROM prestasi p
     LEFT JOIN murid m ON m.id = p.murid_id
     WHERE p.id = ? LIMIT 1",
    [$id]
);
if (!$prestasi) {
    setFlash('danger', 'Rekod prestasi tidak dijumpai.');
    redirect(BASE_URL . '/modules/prestasi/index.php');
}

// Use murid_id from record if not passed via GET
if ($muridId <= 0) {
    $muridId = (int)$prestasi['murid_id'];
}

// ── Determine redirect target ─────────────────────────────────────────────────
function getRedirectUrl(string $redirectTo, int $muridId): string {
    if ($redirectTo === 'murid' && $muridId > 0) {
        return BASE_URL . '/modules/murid/lihat.php?id=' . $muridId;
    }
    return BASE_URL . '/modules/prestasi/index.php';
}

// ── Handle actions ────────────────────────────────────────────────────────────
switch ($action) {

    case 'padam':
        dbQuery("DELETE FROM prestasi WHERE id=?", [$id]);
        logAktiviti('padam', 'prestasi', $id,
            'Padam rekod markah: ' . $prestasi['nama_subjek'] . ' - ' . ($prestasi['nama_murid'] ?? 'Murid'));
        setFlash('success',
            'Rekod markah <strong>' . clean($prestasi['nama_subjek']) . '</strong> telah dipadamkan.');
        redirect(getRedirectUrl($redirectTo, $muridId));
        break;

    case 'padam_semua_murid':
        // Delete all marks for a given student (bulk action)
        if ($muridId <= 0) {
            setFlash('danger', 'ID murid tidak sah.');
            redirect(BASE_URL . '/modules/prestasi/index.php');
        }
        $count = (int)dbValue("SELECT COUNT(*) FROM prestasi WHERE murid_id=?", [$muridId]);
        dbQuery("DELETE FROM prestasi WHERE murid_id=?", [$muridId]);
        logAktiviti('padam_semua', 'prestasi', $muridId,
            "Padam semua ($count) rekod markah untuk murid ID $muridId");
        setFlash('success', "Semua <strong>$count</strong> rekod markah murid telah dipadamkan.");
        redirect(getRedirectUrl('murid', $muridId));
        break;

    default:
        setFlash('danger', 'Tindakan tidak dikenali: ' . clean($action));
        redirect(BASE_URL . '/modules/prestasi/index.php');
}
