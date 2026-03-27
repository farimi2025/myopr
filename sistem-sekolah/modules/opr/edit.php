<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','ID tidak sah'); redirect(BASE_URL.'/modules/opr/index.php'); }
$opr = dbFetch("SELECT * FROM opr WHERE id=?", [$id]);
if (!$opr) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/opr/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('danger','Token tidak sah'); redirect(BASE_URL.'/modules/opr/edit.php?id='.$id); }
    $data = [
        'no_rujukan'              => clean($_POST['no_rujukan']) ?: $opr['no_rujukan'],
        'tajuk'                   => clean($_POST['tajuk']),
        'tarikh_aktiviti'         => clean($_POST['tarikh_aktiviti']),
        'kategori'                => clean($_POST['kategori']),
        'tempat'                  => clean($_POST['tempat']),
        'pegawai_bertanggungjawab'=> clean($_POST['pegawai_bertanggungjawab']),
        'penerangan_umum'         => clean($_POST['penerangan_umum']),
        'penerangan_lanjut'       => clean($_POST['penerangan_lanjut']),
        'impak'                   => clean($_POST['impak']),
        'saranan'                 => clean($_POST['saranan']),
        'guru_id'                 => (int)$_POST['guru_id'] ?: null,
        'status'                  => clean($_POST['status']),
        'gambar1_kapsyen'         => clean($_POST['gambar1_kapsyen'] ?? ''),
        'gambar2_kapsyen'         => clean($_POST['gambar2_kapsyen'] ?? ''),
        'gambar3_kapsyen'         => clean($_POST['gambar3_kapsyen'] ?? ''),
    ];

    // Upload gambar baru
    foreach ([1,2,3] as $n) {
        $key = "gambar$n";
        if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            $upload = uploadGambar($_FILES[$key], 'gambar');
            if ($upload['berjaya']) {
                if ($opr[$key]) padamFail($opr[$key]); // Padam gambar lama
                $data[$key] = $upload['fail'];
            }
        } elseif (!empty($_POST["hapus_gambar$n"])) {
            if ($opr[$key]) padamFail($opr[$key]);
            $data[$key] = null;
        }
    }

    dbUpdate('opr', $data, 'id=?', [$id]);
    logAktiviti('edit','opr',$id,'Kemaskini OPR: '.$data['tajuk']);
    setFlash('success','OPR berjaya dikemaskini.');
    redirect(BASE_URL.'/modules/opr/lihat.php?id='.$id);
}

$senaraiGuru = getSenaraiGuru();
$pageTitle = 'Edit OPR';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-pencil text-warning me-2"></i>Edit OPR — <?=clean($opr['tajuk'])?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=BASE_URL?>/modules/opr/index.php">OPR</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol></nav>
    </div>
    <a href="lihat.php?id=<?=$id?>" class="btn btn-outline-success">
        <i class="bi bi-eye me-1"></i>Lihat OPR</a>
</div>
<?= showFlash() ?>

<form method="POST" id="mainForm" data-autosave="opr" data-rekod-id="<?=$id?>" enctype="multipart/form-data">
<?= csrfField() ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark"><i class="bi bi-pencil me-2"></i>Kemaskini Maklumat</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No. Rujukan</label>
                        <input type="text" class="form-control" name="no_rujukan" value="<?=clean($opr['no_rujukan']??'')?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tarikh Aktiviti *</label>
                        <input type="date" class="form-control" name="tarikh_aktiviti" value="<?=$opr['tarikh_aktiviti']?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <?php foreach (['Akademik','Ko-Kurikulum','Sukan','Kebajikan','Pembangunan Staf','Ibu Bapa','Lain-lain'] as $k): ?>
                            <option value="<?=$k?>" <?=$k==$opr['kategori']?'selected':''?>><?=$k?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tajuk *</label>
                        <input type="text" class="form-control form-control-lg" name="tajuk" required value="<?=clean($opr['tajuk'])?>">
                    </div>
                    <div class="col-md-6"><label class="form-label">Tempat</label>
                        <input type="text" class="form-control" name="tempat" value="<?=clean($opr['tempat']??'')?>"></div>
                    <div class="col-md-6"><label class="form-label">Pegawai Bertanggungjawab</label>
                        <input type="text" class="form-control" name="pegawai_bertanggungjawab" value="<?=clean($opr['pegawai_bertanggungjawab']??'')?>"></div>
                    <div class="col-md-6">
                        <label class="form-label">Guru</label>
                        <select name="guru_id" class="form-select select2">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($senaraiGuru as $g): ?>
                            <option value="<?=$g['id']?>" <?=$g['id']==$opr['guru_id']?'selected':''?>><?=clean($g['nama'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draf" <?=$opr['status']=='draf'?'selected':''?>>Draf</option>
                            <option value="aktif" <?=$opr['status']=='aktif'?'selected':''?>>Aktif</option>
                            <option value="arkib" <?=$opr['status']=='arkib'?'selected':''?>>Arkib</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-file-text me-2"></i>Laporan</div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Laporan Ringkas</label>
                    <textarea class="form-control" name="penerangan_umum" rows="4"><?=clean($opr['penerangan_umum']??'')?></textarea></div>
                <div class="mb-3"><label class="form-label">Penerangan Lanjut</label>
                    <textarea class="form-control" name="penerangan_lanjut" rows="6"><?=clean($opr['penerangan_lanjut']??'')?></textarea></div>
                <div class="mb-3"><label class="form-label">Impak</label>
                    <textarea class="form-control" name="impak" rows="3"><?=clean($opr['impak']??'')?></textarea></div>
                <div><label class="form-label">Saranan</label>
                    <textarea class="form-control" name="saranan" rows="3"><?=clean($opr['saranan']??'')?></textarea></div>
            </div>
        </div>

        <!-- Gambar -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-images me-2"></i>Gambar</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ([1,2,3] as $n): ?>
                    <div class="col-md-4">
                        <?php $curImg = $opr["gambar$n"]; ?>
                        <div class="opr-photo-box mb-2" onclick="document.getElementById('gambar<?=$n?>').click()">
                            <?php if ($curImg && file_exists(ROOT_PATH.'/'.$curImg)): ?>
                            <img id="prev<?=$n?>" src="<?=BASE_URL.'/'.$curImg?>" style="width:100%;height:100%;object-fit:cover">
                            <div class="upload-hint py-4 d-none"><i class="bi bi-camera fs-2 d-block"></i><small>Tukar gambar</small></div>
                            <?php else: ?>
                            <img id="prev<?=$n?>" src="" style="display:none;width:100%;height:100%;object-fit:cover">
                            <div class="upload-hint py-4"><i class="bi bi-camera fs-2 d-block"></i><small>Klik untuk muat naik</small></div>
                            <?php endif; ?>
                            <input type="file" id="gambar<?=$n?>" name="gambar<?=$n?>" class="input-gambar d-none"
                                   accept="image/*" data-preview="prev<?=$n?>">
                        </div>
                        <?php if ($curImg): ?>
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input" name="hapus_gambar<?=$n?>" id="hapus<?=$n?>" value="1">
                            <label class="form-check-label small text-danger" for="hapus<?=$n?>">Padam gambar ini</label>
                        </div>
                        <?php endif; ?>
                        <input type="text" class="form-control form-control-sm" name="gambar<?=$n?>_kapsyen"
                               placeholder="Kapsyen..." value="<?=clean($opr["gambar{$n}_kapsyen"]??'')?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card sticky-top" style="top:70px">
            <div class="card-footer d-grid gap-2">
                <button type="submit" class="btn btn-warning btn-lg">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="lihat.php?id=<?=$id?>" class="btn btn-success">
                    <i class="bi bi-eye me-1"></i>Lihat OPR</a>
                <a href="aksi.php?action=padam&id=<?=$id?>&csrf_token=<?=csrfToken()?>"
                   class="btn btn-outline-danger" data-confirm-delete="<?=clean($opr['tajuk'])?>">
                   <i class="bi bi-trash me-1"></i>Padam</a>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
