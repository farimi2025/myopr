<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$tindakan = clean($_GET['tindakan'] ?? '');
$id       = (int)($_GET['id'] ?? 0);
$kelasId  = (int)($_GET['kelas_id'] ?? 0);

$tindakanSah = ['padam', 'arkib', 'aktif', 'padam_tugasan'];

if (!$id || !in_array($tindakan, $tindakanSah)) {
    setFlash('danger', 'Parameter tidak sah.');
    redirect(BASE_URL . '/modules/kelas/index.php');
}

switch ($tindakan) {
    case 'padam':
        $kelas = dbFetch("SELECT id, nama_kelas FROM kelas WHERE id=?", [$id]);
        if (!$kelas) {
            setFlash('danger', 'Rekod kelas tidak dijumpai.');
            redirect(BASE_URL . '/modules/kelas/index.php');
        }
        // Delete related guru_kelas_subjek
        dbQuery("DELETE FROM guru_kelas_subjek WHERE kelas_id=?", [$id]);
        // Delete kelas
        dbQuery("DELETE FROM kelas WHERE id=?", [$id]);
        logAktiviti('padam', 'kelas', $id, "Padam kelas: {$kelas['nama_kelas']}");
        setFlash('success', "Kelas <strong>{$kelas['nama_kelas']}</strong> berjaya dipadam.");
        redirect(BASE_URL . '/modules/kelas/index.php');
        break;

    case 'arkib':
        $kelas = dbFetch("SELECT id, nama_kelas FROM kelas WHERE id=?", [$id]);
        if (!$kelas) {
            setFlash('danger', 'Rekod kelas tidak dijumpai.');
            redirect(BASE_URL . '/modules/kelas/index.php');
        }
        dbUpdate('kelas', ['status' => 'tidak aktif'], 'id=?', [$id]);
        logAktiviti('arkib', 'kelas', $id, "Arkib kelas: {$kelas['nama_kelas']}");
        setFlash('success', "Kelas <strong>{$kelas['nama_kelas']}</strong> telah diarkibkan.");
        redirect(BASE_URL . '/modules/kelas/index.php');
        break;

    case 'aktif':
        $kelas = dbFetch("SELECT id, nama_kelas FROM kelas WHERE id=?", [$id]);
        if (!$kelas) {
            setFlash('danger', 'Rekod kelas tidak dijumpai.');
            redirect(BASE_URL . '/modules/kelas/index.php');
        }
        dbUpdate('kelas', ['status' => 'aktif'], 'id=?', [$id]);
        logAktiviti('kemaskini', 'kelas', $id, "Aktifkan kelas: {$kelas['nama_kelas']}");
        setFlash('success', "Kelas <strong>{$kelas['nama_kelas']}</strong> telah diaktifkan semula.");
        redirect(BASE_URL . '/modules/kelas/index.php');
        break;

    case 'padam_tugasan':
        $tugasan = dbFetch("SELECT id, nama_subjek, kelas_id FROM guru_kelas_subjek WHERE id=?", [$id]);
        if (!$tugasan) {
            setFlash('danger', 'Rekod tugasan tidak dijumpai.');
            $redirect = $kelasId ? BASE_URL . '/modules/kelas/guru_subjek.php?kelas_id=' . $kelasId : BASE_URL . '/modules/kelas/index.php';
            redirect($redirect);
        }
        $targetKelasId = $tugasan['kelas_id'];
        dbQuery("DELETE FROM guru_kelas_subjek WHERE id=?", [$id]);
        logAktiviti('padam', 'guru_kelas_subjek', $id, "Padam tugasan: {$tugasan['nama_subjek']}");
        setFlash('success', "Tugasan <strong>{$tugasan['nama_subjek']}</strong> berjaya dipadam.");
        redirect(BASE_URL . '/modules/kelas/guru_subjek.php?kelas_id=' . $targetKelasId);
        break;
}

redirect(BASE_URL . '/modules/kelas/index.php');
?>
