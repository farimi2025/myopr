<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin','super_admin','pentadbir']);

$tindakan = clean($_GET['tindakan'] ?? '');
$id       = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($tindakan, ['padam','arkib','pulih'])) {
    setFlash('danger', 'Parameter tidak sah.');
    redirect(BASE_URL . '/modules/pentadbir/index.php');
}

$pentadbir = dbFetch("SELECT id, nama, foto, status FROM pentadbir WHERE id=?", [$id]);
if (!$pentadbir) {
    setFlash('danger', 'Rekod pentadbir tidak dijumpai.');
    redirect(BASE_URL . '/modules/pentadbir/index.php');
}

switch ($tindakan) {
    case 'padam':
        if ($pentadbir['foto']) padamFail($pentadbir['foto']);
        dbQuery("DELETE FROM pentadbir WHERE id=?", [$id]);
        logAktiviti('padam', 'pentadbir', $id, "Padam pentadbir: {$pentadbir['nama']}");
        setFlash('success', "Rekod pentadbir <strong>{$pentadbir['nama']}</strong> berjaya dipadam.");
        break;

    case 'arkib':
        dbUpdate('pentadbir', ['status' => 'arkib'], 'id=?', [$id]);
        logAktiviti('arkib', 'pentadbir', $id, "Arkib pentadbir: {$pentadbir['nama']}");
        setFlash('success', "Pentadbir <strong>{$pentadbir['nama']}</strong> telah diarkibkan.");
        break;

    case 'pulih':
        dbUpdate('pentadbir', ['status' => 'aktif'], 'id=?', [$id]);
        logAktiviti('pulih', 'pentadbir', $id, "Pulih pentadbir: {$pentadbir['nama']}");
        setFlash('success', "Pentadbir <strong>{$pentadbir['nama']}</strong> telah dipulihkan ke status aktif.");
        break;
}

redirect(BASE_URL . '/modules/pentadbir/index.php');
?>
