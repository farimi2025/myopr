<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$token  = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';

if (!$id || !hash_equals(csrfToken(), $token)) {
    setFlash('danger', 'Permintaan tidak sah.');
    redirect(BASE_URL . '/modules/kurikulum/index.php');
}

$rekod = dbFetch("SELECT * FROM kurikulum WHERE id=?", [$id]);
if (!$rekod) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/kurikulum/index.php'); }

switch ($action) {
    case 'padam':
        dbQuery("DELETE FROM kurikulum WHERE id=?", [$id]);
        logAktiviti('padam','kurikulum',$id,'Padam standard: '.$rekod['mata_pelajaran']);
        setFlash('success','Standard kurikulum berjaya dipadam.');
        break;
    case 'arkib':
        dbUpdate('kurikulum', ['status'=>'arkib'], 'id=?', [$id]);
        logAktiviti('arkib','kurikulum',$id,'Arkib standard');
        setFlash('success','Standard berjaya diarkib.');
        break;
    case 'aktif':
        dbUpdate('kurikulum', ['status'=>'aktif'], 'id=?', [$id]);
        setFlash('success','Standard berjaya diaktifkan semula.');
        break;
    default:
        setFlash('danger','Tindakan tidak dikenali.');
}
redirect(BASE_URL . '/modules/kurikulum/index.php');
