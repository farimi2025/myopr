<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['super_admin', 'pentadbir']);

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

if (!verifyCsrf()) {
    setFlash('danger', 'Token keselamatan tidak sah. Sila cuba lagi.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

$currentUser = getCurrentUser();
$tindakan    = clean($_POST['tindakan'] ?? '');
$id          = (int)($_POST['id'] ?? 0);

if (!$id || !in_array($tindakan, ['padam','arkib','aktif','reset_password'])) {
    setFlash('danger', 'Parameter tidak sah.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

// Tidak boleh lakukan tindakan ke atas akaun sendiri
if ($id === (int)$currentUser['id']) {
    setFlash('danger', 'Anda tidak boleh melakukan tindakan ini ke atas akaun sendiri.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

$pengguna = dbFetch("SELECT id, nama, username, foto, status FROM users WHERE id=?", [$id]);
if (!$pengguna) {
    setFlash('danger', 'Rekod pengguna tidak dijumpai.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

switch ($tindakan) {

    case 'padam':
        // Padam foto jika ada
        if ($pengguna['foto']) padamFail($pengguna['foto']);
        // Padam rekod
        dbQuery("DELETE FROM users WHERE id=?", [$id]);
        logAktiviti('padam', 'users', $id, "Padam pengguna: {$pengguna['username']}");
        setFlash('success', "Rekod pengguna <strong>{$pengguna['nama']}</strong> berjaya dipadam.");
        break;

    case 'arkib':
        dbUpdate('users', ['status' => 'arkib'], 'id=?', [$id]);
        logAktiviti('arkib', 'users', $id, "Arkib pengguna: {$pengguna['username']}");
        setFlash('success', "Pengguna <strong>{$pengguna['nama']}</strong> telah diarkibkan.");
        break;

    case 'aktif':
        dbUpdate('users', ['status' => 'aktif'], 'id=?', [$id]);
        logAktiviti('aktif', 'users', $id, "Aktifkan semula pengguna: {$pengguna['username']}");
        setFlash('success', "Pengguna <strong>{$pengguna['nama']}</strong> telah diaktifkan semula.");
        break;

    case 'reset_password':
        // Jana kata laluan rawak 10 aksara
        $chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $newPass  = '';
        for ($i = 0; $i < 10; $i++) {
            $newPass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        dbUpdate('users', ['password' => $hashed, 'token_reset' => null], 'id=?', [$id]);
        logAktiviti('reset_password', 'users', $id, "Set semula kata laluan untuk: {$pengguna['username']}");
        setFlash('info', "Kata laluan baharu untuk <strong>{$pengguna['nama']}</strong> ialah: <code class=\"user-select-all\">$newPass</code> — Sila maklumkan kepada pengguna dan minta ditukar segera.");
        break;
}

redirect(BASE_URL . '/modules/pengguna/index.php');
?>
