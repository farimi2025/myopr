<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/kurikulum/index.php'); }
$d = dbFetch("SELECT * FROM kurikulum WHERE id=?", [$id]);
if (!$d) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/kurikulum/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('danger','Token tidak sah'); redirect(BASE_URL.'/modules/kurikulum/edit.php?id='.$id); }
    $data = [
        'mata_pelajaran'           => clean($_POST['mata_pelajaran']),
        'tingkatan'                => clean($_POST['tingkatan']),
        'bahagian'                 => clean($_POST['bahagian']),
        'tema'                     => clean($_POST['tema']),
        'unit'                     => (int)$_POST['unit'] ?: null,
        'tajuk_unit'               => clean($_POST['tajuk_unit']),
        'standard_kandungan_kod'   => clean($_POST['standard_kandungan_kod']),
        'standard_kandungan'       => clean($_POST['standard_kandungan']),
        'standard_pembelajaran_kod'=> clean($_POST['standard_pembelajaran_kod']),
        'standard_pembelajaran'    => clean($_POST['standard_pembelajaran']),
        'standard_prestasi'        => clean($_POST['standard_prestasi']),
        'huraian_sp'               => clean($_POST['huraian_sp']),
        'catatan'                  => clean($_POST['catatan']),
        'tahun_semakan'            => (int)$_POST['tahun_semakan'] ?: 2017,
    ];
    dbUpdate('kurikulum', $data, 'id=?', [$id]);
    logAktiviti('edit','kurikulum',$id,'Kemaskini standard: '.$data['mata_pelajaran']);
    setFlash('success','Standard kurikulum berjaya dikemaskini.');
    redirect(BASE_URL.'/modules/kurikulum/index.php?mata_pelajaran='.urlencode($data['mata_pelajaran']));
}

$mataPelajaranList = dbFetchAll("SELECT DISTINCT mata_pelajaran FROM kurikulum ORDER BY mata_pelajaran");
$subjekList = dbFetchAll("SELECT nama FROM subjek WHERE status='aktif' ORDER BY nama");

$pageTitle = 'Edit Standard DSKP';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div><h1><i class="bi bi-pencil text-warning me-2"></i>Edit Standard Kurikulum</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=BASE_URL?>/modules/kurikulum/index.php">DSKP 2017</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol></nav>
    </div>
</div>
<?= showFlash() ?>
<form method="POST" id="mainForm" data-autosave="kurikulum" data-rekod-id="<?=$id?>">
<?= csrfField() ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark"><i class="bi bi-pencil me-2"></i>Kemaskini Standard</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mata Pelajaran *</label>
                        <input type="text" class="form-control" name="mata_pelajaran" list="dlist_mp" required value="<?=clean($d['mata_pelajaran'])?>">
                        <datalist id="dlist_mp">
                            <?php foreach ($subjekList as $s): ?><option value="<?=clean($s['nama'])?>"><?php endforeach; ?>
                            <?php foreach ($mataPelajaranList as $m): ?><option value="<?=clean($m['mata_pelajaran'])?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tingkatan</label>
                        <select name="tingkatan" class="form-select">
                            <?php foreach (['','1','2','3','4','5','6','PPKI','Semua'] as $t): ?>
                            <option value="<?=$t?>" <?=$d['tingkatan']==$t?'selected':''?>><?=$t?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun Semakan</label>
                        <input type="number" class="form-control" name="tahun_semakan" value="<?=$d['tahun_semakan']?>" min="2000" max="2030">
                    </div>
                    <div class="col-md-4"><label class="form-label">Bahagian</label><input type="text" class="form-control" name="bahagian" value="<?=clean($d['bahagian']??'')?>"></div>
                    <div class="col-md-6"><label class="form-label">Tema</label><input type="text" class="form-control" name="tema" value="<?=clean($d['tema']??'')?>"></div>
                    <div class="col-md-2"><label class="form-label">Unit</label><input type="number" class="form-control" name="unit" value="<?=$d['unit']??''?>" min="1"></div>
                    <div class="col-12"><label class="form-label">Tajuk Unit</label><input type="text" class="form-control" name="tajuk_unit" value="<?=clean($d['tajuk_unit']??'')?>"></div>
                    <div class="col-md-3"><label class="form-label">Kod SK</label><input type="text" class="form-control font-monospace" name="standard_kandungan_kod" value="<?=clean($d['standard_kandungan_kod']??'')?>"></div>
                    <div class="col-md-9"><label class="form-label">Standard Kandungan *</label><textarea class="form-control" name="standard_kandungan" rows="3" required><?=clean($d['standard_kandungan']??'')?></textarea></div>
                    <div class="col-md-3"><label class="form-label">Kod SP</label><input type="text" class="form-control font-monospace" name="standard_pembelajaran_kod" value="<?=clean($d['standard_pembelajaran_kod']??'')?>"></div>
                    <div class="col-md-9"><label class="form-label">Standard Pembelajaran</label><textarea class="form-control" name="standard_pembelajaran" rows="3"><?=clean($d['standard_pembelajaran']??'')?></textarea></div>
                    <div class="col-md-3"><label class="form-label">Standard Prestasi</label>
                        <select name="standard_prestasi" class="form-select">
                            <option value="">--</option>
                            <?php foreach (['SP1','SP2','SP3','SP4','SP5','SP6'] as $sp): ?>
                            <option value="<?=$sp?>" <?=$d['standard_prestasi']==$sp?'selected':''?>><?=$sp?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-9"><label class="form-label">Huraian SP</label><textarea class="form-control" name="huraian_sp" rows="2"><?=clean($d['huraian_sp']??'')?></textarea></div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea class="form-control" name="catatan" rows="2"><?=clean($d['catatan']??'')?></textarea></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:70px">
            <div class="card-footer d-grid gap-2">
                <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                <a href="aksi.php?action=padam&id=<?=$id?>&csrf_token=<?=csrfToken()?>"
                   class="btn btn-outline-danger" data-confirm-delete="standard ini">
                   <i class="bi bi-trash me-1"></i>Padam</a>
            </div>
        </div>
    </div>
</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
