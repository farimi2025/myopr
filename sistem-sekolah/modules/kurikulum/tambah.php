<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

// Klon rekod sedia ada?
$clone = null;
if (!empty($_GET['clone'])) {
    $clone = dbFetch("SELECT * FROM kurikulum WHERE id=?", [(int)$_GET['clone']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('danger','Token tidak sah'); redirect(BASE_URL.'/modules/kurikulum/tambah.php'); }
    $data = [
        'mata_pelajaran'          => clean($_POST['mata_pelajaran']),
        'tingkatan'               => clean($_POST['tingkatan']),
        'bahagian'                => clean($_POST['bahagian']),
        'tema'                    => clean($_POST['tema']),
        'unit'                    => (int)$_POST['unit'] ?: null,
        'tajuk_unit'              => clean($_POST['tajuk_unit']),
        'standard_kandungan_kod'  => clean($_POST['standard_kandungan_kod']),
        'standard_kandungan'      => clean($_POST['standard_kandungan']),
        'standard_pembelajaran_kod'=> clean($_POST['standard_pembelajaran_kod']),
        'standard_pembelajaran'   => clean($_POST['standard_pembelajaran']),
        'standard_prestasi'       => clean($_POST['standard_prestasi']),
        'huraian_sp'              => clean($_POST['huraian_sp']),
        'catatan'                 => clean($_POST['catatan']),
        'tahun_semakan'           => (int)$_POST['tahun_semakan'] ?: 2017,
    ];
    if (!$data['mata_pelajaran'] || !$data['standard_kandungan']) {
        setFlash('danger','Sila isi mata pelajaran dan standard kandungan.');
    } else {
        $newId = dbInsert('kurikulum', $data);
        logAktiviti('tambah','kurikulum',(int)$newId,'Tambah standard kurikulum: '.$data['mata_pelajaran']);
        setFlash('success','Standard kurikulum berjaya ditambah.');
        if (!empty($_POST['tambah_lagi'])) redirect(BASE_URL.'/modules/kurikulum/tambah.php?mata_pelajaran='.urlencode($data['mata_pelajaran']).'&tingkatan='.$data['tingkatan']);
        redirect(BASE_URL.'/modules/kurikulum/index.php');
    }
}

// Subjek list for datalist
$mataPelajaranList = dbFetchAll("SELECT DISTINCT mata_pelajaran FROM kurikulum ORDER BY mata_pelajaran");
$subjekList = dbFetchAll("SELECT nama FROM subjek WHERE status='aktif' ORDER BY nama");

$d = $clone ?? []; // pre-fill if cloning
$pageTitle = 'Tambah Standard DSKP';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-book-half text-success me-2"></i>Tambah Standard Kurikulum DSKP 2017</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=BASE_URL?>/modules/kurikulum/index.php">DSKP 2017</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol></nav>
    </div>
</div>
<?= showFlash() ?>
<?php if ($clone): ?>
<div class="alert alert-info"><i class="bi bi-copy me-2"></i>Mengklon rekod dari: <strong><?=clean($clone['mata_pelajaran'])?></strong></div>
<?php endif; ?>

<form method="POST" id="mainForm" data-autosave="kurikulum" data-rekod-id="0">
<?= csrfField() ?>
<div class="row g-3">
    <div class="col-lg-8">
        <!-- Maklumat Asas -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Maklumat Asas</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mata Pelajaran *</label>
                        <input type="text" class="form-control" name="mata_pelajaran" list="dlist_mp" required
                            value="<?=clean($d['mata_pelajaran']??$_GET['mata_pelajaran']??'')?>">
                        <datalist id="dlist_mp">
                            <?php foreach ($subjekList as $s): ?>
                            <option value="<?=clean($s['nama'])?>">
                            <?php endforeach; ?>
                            <?php foreach ($mataPelajaranList as $m): ?>
                            <option value="<?=clean($m['mata_pelajaran'])?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tingkatan</label>
                        <select name="tingkatan" class="form-select">
                            <option value="">-- Pilih --</option>
                            <?php foreach (['1','2','3','4','5','6','PPKI','Semua'] as $t): ?>
                            <option value="<?=$t?>" <?=($d['tingkatan']??$_GET['tingkatan']??'')==$t?'selected':''?>><?=$t?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun Semakan</label>
                        <input type="number" class="form-control" name="tahun_semakan"
                            value="<?=$d['tahun_semakan']??2017?>" min="2000" max="2030">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bahagian / Tema Besar</label>
                        <input type="text" class="form-control" name="bahagian" value="<?=clean($d['bahagian']??'')?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tema</label>
                        <input type="text" class="form-control" name="tema" value="<?=clean($d['tema']??'')?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit</label>
                        <input type="number" class="form-control" name="unit" value="<?=$d['unit']??''?>" min="1">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tajuk Unit</label>
                        <input type="text" class="form-control" name="tajuk_unit" value="<?=clean($d['tajuk_unit']??'')?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Standard -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-layers me-2"></i>Standard Kurikulum</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Kod Standard Kandungan</label>
                        <input type="text" class="form-control font-monospace" name="standard_kandungan_kod"
                            placeholder="cth: 1.1" value="<?=clean($d['standard_kandungan_kod']??'')?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Standard Kandungan (SK) *</label>
                        <textarea class="form-control" name="standard_kandungan" rows="3" required><?=clean($d['standard_kandungan']??'')?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kod Standard Pembelajaran</label>
                        <input type="text" class="form-control font-monospace" name="standard_pembelajaran_kod"
                            placeholder="cth: 1.1.1" value="<?=clean($d['standard_pembelajaran_kod']??'')?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Standard Pembelajaran (SP)</label>
                        <textarea class="form-control" name="standard_pembelajaran" rows="3"><?=clean($d['standard_pembelajaran']??'')?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Standard Prestasi</label>
                        <select name="standard_prestasi" class="form-select">
                            <option value="">-- Pilih --</option>
                            <?php foreach (['SP1','SP2','SP3','SP4','SP5','SP6'] as $sp): ?>
                            <option value="<?=$sp?>" <?=($d['standard_prestasi']??'')==$sp?'selected':''?>><?=$sp?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Huraian Standard Prestasi</label>
                        <textarea class="form-control" name="huraian_sp" rows="2"><?=clean($d['huraian_sp']??'')?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="catatan" rows="2"><?=clean($d['catatan']??'')?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:70px">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Panduan</div>
            <div class="card-body small text-muted">
                <p><strong>Standard Kandungan (SK)</strong><br>Pernyataan pengetahuan, kemahiran atau nilai yang perlu dikuasai oleh murid.</p>
                <p><strong>Standard Pembelajaran (SP)</strong><br>Pernyataan lebih spesifik tentang perkara yang murid perlu tahu atau boleh buat.</p>
                <p class="mb-0"><strong>Standard Prestasi (SP1-SP6)</strong><br>Penanda rujukan pencapaian murid dalam sesuatu tempoh pembelajaran.</p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-save me-1"></i>Simpan Standard</button>
                <button type="submit" name="tambah_lagi" value="1" class="btn btn-outline-success w-100 mb-2">
                    <i class="bi bi-plus me-1"></i>Simpan & Tambah Lagi</button>
                <a href="index.php" class="btn btn-outline-secondary w-100">Batal</a>
            </div>
        </div>
    </div>
</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
