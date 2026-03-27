<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$tindakan = clean($_GET['tindakan'] ?? '');
$id       = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($tindakan, ['padam','arkib','pulih','berhenti','bersara'])) {
    setFlash('danger', 'Parameter tidak sah.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

$guru = dbFetch("SELECT id, nama, foto, status FROM guru WHERE id=?", [$id]);
if (!$guru) {
    setFlash('danger', 'Rekod guru tidak dijumpai.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

switch ($tindakan) {
    case 'padam':
        // Delete related guru_kelas_subjek first
        dbQuery("DELETE FROM guru_kelas_subjek WHERE guru_id=?", [$id]);
        // Delete photo
        if ($guru['foto']) padamFail($guru['foto']);
        // Delete guru record
        dbQuery("DELETE FROM guru WHERE id=?", [$id]);
        logAktiviti('padam', 'guru', $id, "Padam guru: {$guru['nama']}");
        setFlash('success', "Rekod guru <strong>{$guru['nama']}</strong> berjaya dipadam.");
        break;

    case 'arkib':
        dbUpdate('guru', ['status' => 'arkib'], 'id=?', [$id]);
        logAktiviti('arkib', 'guru', $id, "Arkib guru: {$guru['nama']}");
        setFlash('success', "Guru <strong>{$guru['nama']}</strong> telah diarkibkan.");
        break;

    case 'pulih':
        dbUpdate('guru', ['status' => 'aktif'], 'id=?', [$id]);
        logAktiviti('pulih', 'guru', $id, "Pulih guru: {$guru['nama']}");
        setFlash('success', "Guru <strong>{$guru['nama']}</strong> telah dipulihkan ke status aktif.");
        break;

    case 'berhenti':
        dbUpdate('guru', ['status' => 'berhenti'], 'id=?', [$id]);
        logAktiviti('kemaskini', 'guru', $id, "Status guru ditukar ke berhenti: {$guru['nama']}");
        setFlash('info', "Guru <strong>{$guru['nama']}</strong> ditandakan sebagai berhenti.");
        break;

    case 'bersara':
        dbUpdate('guru', ['status' => 'bersara'], 'id=?', [$id]);
        logAktiviti('kemaskini', 'guru', $id, "Status guru ditukar ke bersara: {$guru['nama']}");
        setFlash('info', "Guru <strong>{$guru['nama']}</strong> ditandakan sebagai bersara.");
        break;
}

redirect(BASE_URL . '/modules/guru/index.php');
?>
