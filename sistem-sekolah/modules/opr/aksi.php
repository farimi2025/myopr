<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$token  = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';

if (!$id || !hash_equals(csrfToken(), $token)) {
    setFlash('danger', 'Permintaan tidak sah.');
    redirect(BASE_URL . '/modules/opr/index.php');
}

$opr = dbFetch("SELECT * FROM opr WHERE id=?", [$id]);
if (!$opr) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/opr/index.php'); }

switch ($action) {
    case 'padam':
        // Padam gambar-gambar
        foreach ([1,2,3] as $n) {
            if ($opr["gambar$n"]) padamFail($opr["gambar$n"]);
        }
        dbQuery("DELETE FROM opr WHERE id=?", [$id]);
        logAktiviti('padam','opr',$id,'Padam OPR: '.$opr['tajuk']);
        setFlash('success','OPR berjaya dipadam.');
        break;
    case 'arkib':
        dbUpdate('opr', ['status'=>'arkib'], 'id=?', [$id]);
        logAktiviti('arkib','opr',$id,'Arkib OPR: '.$opr['tajuk']);
        setFlash('success','OPR berjaya diarkib.');
        break;
    case 'aktif':
        dbUpdate('opr', ['status'=>'aktif'], 'id=?', [$id]);
        setFlash('success','OPR berjaya diaktifkan semula.');
        break;
    case 'draf':
        dbUpdate('opr', ['status'=>'draf'], 'id=?', [$id]);
        setFlash('info','OPR ditukar kepada draf.');
        break;
    default:
        setFlash('danger','Tindakan tidak dikenali.');
}

redirect(BASE_URL . '/modules/opr/index.php');
