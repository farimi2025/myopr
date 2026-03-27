<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('danger','Token tidak sah'); redirect(BASE_URL.'/modules/opr/tambah.php'); }

    $data = [
        'no_rujukan'             => clean($_POST['no_rujukan']) ?: janaNoRujukan('OPR','opr'),
        'tajuk'                  => clean($_POST['tajuk']),
        'tarikh_aktiviti'        => clean($_POST['tarikh_aktiviti']),
        'kategori'               => clean($_POST['kategori']),
        'tempat'                 => clean($_POST['tempat']),
        'pegawai_bertanggungjawab'=> clean($_POST['pegawai_bertanggungjawab']),
        'penerangan_umum'        => clean($_POST['penerangan_umum']),
        'penerangan_lanjut'      => clean($_POST['penerangan_lanjut']),
        'impak'                  => clean($_POST['impak']),
        'saranan'                => clean($_POST['saranan']),
        'guru_id'                => (int)$_POST['guru_id'] ?: null,
        'status'                 => clean($_POST['status']),
        'tahun'                  => (int)date('Y', strtotime(clean($_POST['tarikh_aktiviti']))) ?: TAHUN_SEMASA,
        'gambar1_kapsyen'        => clean($_POST['gambar1_kapsyen'] ?? ''),
        'gambar2_kapsyen'        => clean($_POST['gambar2_kapsyen'] ?? ''),
        'gambar3_kapsyen'        => clean($_POST['gambar3_kapsyen'] ?? ''),
    ];

    if (!$data['tajuk'] || !$data['tarikh_aktiviti']) {
        setFlash('danger','Sila isi tajuk dan tarikh aktiviti.');
    } else {
        // Upload gambar
        foreach ([1,2,3] as $n) {
            $key = "gambar$n";
            if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $upload = uploadGambar($_FILES[$key], 'gambar');
                if ($upload['berjaya']) $data[$key] = $upload['fail'];
                else setFlash('warning', "Gambar $n: " . $upload['mesej']);
            }
        }

        $id = dbInsert('opr', $data);
        // Padam autosave
        dbQuery("DELETE FROM autosave WHERE user_id=? AND modul='opr' AND rekod_id=0",
            [$_SESSION['user_id']]);
        logAktiviti('tambah','opr',(int)$id,'Tambah OPR: '.$data['tajuk']);
        setFlash('success','OPR berjaya disimpan! No. Rujukan: '.$data['no_rujukan']);
        redirect(BASE_URL.'/modules/opr/lihat.php?id='.$id);
    }
}

$senaraiGuru = getSenaraiGuru();
$noRujukan = janaNoRujukan('OPR','opr');

// Autosave data
$autosave = dbFetch("SELECT data FROM autosave WHERE user_id=? AND modul='opr' AND rekod_id=0", [$_SESSION['user_id']]);
$as = $autosave ? json_decode($autosave['data'], true) : [];

$pageTitle = 'Buat OPR Baru';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-file-earmark-plus text-success me-2"></i>Buat One Page Report (OPR)</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=BASE_URL?>/modules/opr/index.php">OPR</a></li>
            <li class="breadcrumb-item active">Buat Baru</li>
        </ol></nav>
    </div>
</div>
<?= showFlash() ?>
<?php if ($as): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
    <i class="bi bi-clock-history fs-5"></i>
    <div><strong>Draf dijumpai</strong> — disimpan pada <?=date('d/m/Y H:i', strtotime($autosave['updated_at'] ?? 'now'))?>
    <button type="button" class="btn btn-sm btn-info ms-3" id="btnLoadDraft">
        <i class="bi bi-cloud-download me-1"></i>Muat Draf</button>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="btnDismissDraft">Abaikan</button>
    </div>
</div>
<?php endif; ?>

<form method="POST" id="mainForm" data-autosave="opr" data-rekod-id="0" enctype="multipart/form-data">
<?= csrfField() ?>
<div class="row g-3">
    <!-- KIRI: Borang Utama -->
    <div class="col-lg-8">
        <!-- Maklumat Asas -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-success"></i>Maklumat Aktiviti</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No. Rujukan</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="no_rujukan" id="no_rujukan"
                                value="<?=clean($as['no_rujukan'] ?? $noRujukan)?>" placeholder="Auto-jana">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('no_rujukan').value='<?=$noRujukan?>'">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tarikh Aktiviti *</label>
                        <input type="date" class="form-control" name="tarikh_aktiviti" required
                            value="<?=clean($as['tarikh_aktiviti'] ?? date('Y-m-d'))?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <?php foreach (['Akademik','Ko-Kurikulum','Sukan','Kebajikan','Pembangunan Staf','Ibu Bapa','Lain-lain'] as $k): ?>
                            <option value="<?=$k?>" <?=($as['kategori']??'')===$k?'selected':''?>><?=$k?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tajuk Aktiviti / Program *</label>
                        <input type="text" class="form-control form-control-lg" name="tajuk" required
                            placeholder="Masukkan tajuk aktiviti/program..."
                            value="<?=clean($as['tajuk']??'')?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tempat</label>
                        <input type="text" class="form-control" name="tempat"
                            value="<?=clean($as['tempat']??'')?>" placeholder="Dewan, Padang, Bilik...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pegawai Bertanggungjawab</label>
                        <input type="text" class="form-control" name="pegawai_bertanggungjawab"
                            value="<?=clean($as['pegawai_bertanggungjawab']??$_SESSION['user_nama']??'')?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Guru / Penyedia Laporan</label>
                        <select name="guru_id" class="form-select select2">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($senaraiGuru as $g): ?>
                            <option value="<?=$g['id']?>" <?=($as['guru_id']??0)==$g['id']?'selected':''?>><?=clean($g['nama'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draf" <?=($as['status']??'draf')=='draf'?'selected':''?>>
                                Draf (simpan sementara)</option>
                            <option value="aktif" <?=($as['status']??'')==='aktif'?'selected':''?>>
                                Aktif (selesai)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laporan -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-file-text me-2 text-primary"></i>Laporan</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Laporan Ringkas <small class="text-muted">(Rumusan aktiviti, 2-4 ayat)</small></label>
                    <textarea class="form-control" name="penerangan_umum" rows="4"
                        placeholder="Tuliskan ringkasan aktiviti..."><?=clean($as['penerangan_umum']??'')?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Penerangan Lanjut <small class="text-muted">(Huraian terperinci)</small></label>
                    <textarea class="form-control" name="penerangan_lanjut" rows="6"
                        placeholder="Huraian terperinci aktiviti yang dijalankan..."><?=clean($as['penerangan_lanjut']??'')?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Impak <small class="text-muted">(Impak kepada murid/sekolah)</small></label>
                    <textarea class="form-control" name="impak" rows="3"
                        placeholder="Huraikan impak positif aktiviti..."><?=clean($as['impak']??'')?></textarea>
                </div>
                <div>
                    <label class="form-label">Saranan / Cadangan Penambahbaikan</label>
                    <textarea class="form-control" name="saranan" rows="3"
                        placeholder="Cadangan untuk penambahbaikan masa hadapan..."><?=clean($as['saranan']??'')?></textarea>
                </div>
            </div>
        </div>

        <!-- Gambar -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-images me-2 text-warning"></i>Gambar Aktiviti (Maks. 3 gambar)</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ([1,2,3] as $n): ?>
                    <div class="col-md-4">
                        <div class="opr-photo-box mb-2" onclick="document.getElementById('gambar<?=$n?>').click()">
                            <img id="prev<?=$n?>" src=""
                                 style="display:none;width:100%;height:100%;object-fit:cover">
                            <div class="upload-hint py-4">
                                <i class="bi bi-camera fs-2 d-block"></i>
                                <small>Klik untuk muat naik</small>
                                <small class="d-block text-muted fs-tiny">Gambar <?=$n?></small>
                            </div>
                            <input type="file" id="gambar<?=$n?>" name="gambar<?=$n?>"
                                   class="input-gambar d-none" accept="image/*"
                                   data-preview="prev<?=$n?>">
                        </div>
                        <input type="text" class="form-control form-control-sm"
                               name="gambar<?=$n?>_kapsyen"
                               placeholder="Kapsyen gambar <?=$n?>..."
                               value="<?=clean($as["gambar{$n}_kapsyen"]??'')?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>
                    Format: JPG, PNG, WEBP. Maks: 5MB setiap gambar. Gambar akan dioptimumkan secara automatik.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN: Preview & Action -->
    <div class="col-lg-4">
        <!-- Auto-save indicator -->
        <div class="card mb-3">
            <div class="card-body py-2 text-center">
                <small class="text-muted" id="autosaveInfo">
                    <i class="bi bi-cloud me-1"></i>Auto-simpan aktif (setiap 30 saat)
                </small>
            </div>
        </div>
        <!-- Tips -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Tips OPR</div>
            <div class="card-body small">
                <ul class="ps-3 mb-0">
                    <li class="mb-2"><strong>Laporan Ringkas:</strong> 2-4 ayat rumusan aktiviti</li>
                    <li class="mb-2"><strong>Penerangan Lanjut:</strong> Huraian lengkap termasuk tarikh, masa, peserta</li>
                    <li class="mb-2"><strong>Impak:</strong> Manfaat kepada murid dan sekolah</li>
                    <li class="mb-2"><strong>Gambar:</strong> Pilih gambar yang menggambarkan aktiviti dengan jelas</li>
                    <li><strong>Kapsyen:</strong> Tulis kapsyen yang bermakna untuk setiap gambar</li>
                </ul>
            </div>
        </div>
        <!-- Action buttons (sticky) -->
        <div class="card sticky-top" style="top:70px">
            <div class="card-footer d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-save me-2"></i>Simpan OPR</button>
                <button type="button" class="btn btn-outline-primary" onclick="manualAutoSave()">
                    <i class="bi bi-cloud-upload me-1"></i>Simpan Draf Sekarang</button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Batal</a>
            </div>
        </div>
    </div>
</div>
</form>
<?php
$autosaveJson = htmlspecialchars(json_encode($as), ENT_QUOTES);
$extraScript = <<<JS
<script>
// Load autosave draft
const draftData = $autosaveJson;

document.getElementById('btnLoadDraft')?.addEventListener('click', function() {
    if (!draftData || Object.keys(draftData).length === 0) return;
    Object.entries(draftData).forEach(([k, v]) => {
        const el = document.querySelector(`[name="${k}"]`);
        if (el && typeof v === 'string') el.value = v;
    });
    this.closest('.alert')?.remove();
    showToast('Draf berjaya dimuat!', 'success');
});

document.getElementById('btnDismissDraft')?.addEventListener('click', function() {
    this.closest('.alert')?.remove();
});

function manualAutoSave() {
    const form = document.getElementById('mainForm');
    const formData = new FormData(form);
    formData.append('modul', 'opr');
    formData.append('rekod_id', '0');
    showAutosaveBar('Menyimpan draf...');
    fetch('<?=BASE_URL?>/ajax/autosave.php', {
        method: 'POST',
        headers: {'X-CSRF-Token': document.querySelector('[name=csrf_token]').value},
        body: formData
    }).then(r => r.json()).then(d => {
        showAutosaveBar(d.ok ? 'Draf disimpan ✓' : 'Gagal simpan', d.ok ? 'success' : 'danger');
        setTimeout(() => hideAutosaveBar(), 2500);
    });
}
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
