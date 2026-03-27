<?php
// ============================================================
// ERPH - TINDAKAN: PADAM / ARKIB / PULIH
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Hanya terima GET (dengan CSRF dalam query string) atau POST
$tindakan = clean($_REQUEST['tindakan'] ?? '');
$id       = (int)($_REQUEST['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'ID rekod tidak sah.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

// Verify CSRF - GET requests use ?csrf=token, POST uses form field
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/erph/index.php');
    }
} else {
    $csrf = clean($_GET['csrf'] ?? '');
    if (!hash_equals(csrfToken(), $csrf)) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/erph/index.php');
    }
}

// Ambil rekod
$erph = dbFetch("SELECT * FROM erph WHERE id=?", [$id]);
if (!$erph) {
    setFlash('danger', 'Rekod ERPH tidak dijumpai.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

// Semak akses
if (!hasRole(['admin','pengetua','penolong_kanan']) && $erph['guru_id'] != $_SESSION['user_id']) {
    setFlash('danger', 'Anda tidak mempunyai akses untuk tindakan ini.');
    redirect(BASE_URL . '/modules/erph/index.php');
}

switch ($tindakan) {

    case 'padam':
        // Padam fail lampiran jika ada
        if ($erph['fail_lampiran']) {
            padamFail($erph['fail_lampiran']);
        }
        // Padam kehadiran murid
        dbQuery("DELETE FROM erph_murid WHERE erph_id=?", [$id]);
        // Padam rekod utama
        dbQuery("DELETE FROM erph WHERE id=?", [$id]);

        logAktiviti('padam', 'erph', $id, 'Padam ERPH: ' . $erph['no_rujukan']);
        setFlash('success', 'Rekod ERPH <strong>' . clean($erph['no_rujukan']) . '</strong> berjaya dipadamkan.');
        redirect(BASE_URL . '/modules/erph/index.php');
        break;

    case 'arkib':
        if ($erph['status'] === 'arkib') {
            setFlash('info', 'Rekod sudah berada dalam status arkib.');
            redirect(BASE_URL . '/modules/erph/index.php');
        }
        dbUpdate('erph', ['status' => 'arkib', 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        logAktiviti('arkib', 'erph', $id, 'Arkib ERPH: ' . $erph['no_rujukan']);
        setFlash('success', 'Rekod ERPH <strong>' . clean($erph['no_rujukan']) . '</strong> telah diarkibkan.');
        redirect(BASE_URL . '/modules/erph/index.php');
        break;

    case 'pulih':
        if ($erph['status'] !== 'arkib') {
            setFlash('info', 'Rekod tidak dalam status arkib.');
            redirect(BASE_URL . '/modules/erph/index.php');
        }
        dbUpdate('erph', ['status' => 'aktif', 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        logAktiviti('pulih', 'erph', $id, 'Pulih ERPH: ' . $erph['no_rujukan']);
        setFlash('success', 'Rekod ERPH <strong>' . clean($erph['no_rujukan']) . '</strong> berjaya dipulihkan.');
        redirect(BASE_URL . '/modules/erph/index.php');
        break;

    case 'aktifkan':
        dbUpdate('erph', ['status' => 'aktif', 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        logAktiviti('aktifkan', 'erph', $id, 'Aktifkan ERPH: ' . $erph['no_rujukan']);
        setFlash('success', 'Rekod ERPH berjaya diaktifkan.');
        redirect(BASE_URL . '/modules/erph/lihat.php?id=' . $id);
        break;

    default:
        setFlash('danger', 'Tindakan tidak dikenali: ' . clean($tindakan));
        redirect(BASE_URL . '/modules/erph/index.php');
}
