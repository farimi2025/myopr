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
        // Jana token reset 32 aksara dengan masa tamat 24 jam
        $tokenReset = bin2hex(random_bytes(16));
        dbQuery(
            "UPDATE users SET token_reset=?, token_reset_tamat=DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id=?",
            [$tokenReset, $id]
        );
        $resetUrl = BASE_URL . '/reset_kata_laluan.php?token=' . $tokenReset;
        logAktiviti('reset_password', 'users', $id, "Jana token reset kata laluan untuk: {$pengguna['username']}");
        setFlash('info', "URL reset kata laluan untuk <strong>{$pengguna['nama']}</strong> ({$pengguna['username']}): <br><a href=\"{$resetUrl}\" class=\"user-select-all text-break\">{$resetUrl}</a><br><small class=\"text-muted\">Pautan ini sah selama 24 jam.</small>");
        break;
}

redirect(BASE_URL . '/modules/pengguna/index.php');
?>
