<?php
// ============================================================
// ERPH - TAMBAH REKOD PENGAJARAN & PEMBELAJARAN HARIAN
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Tambah ERPH';

// ---- Handle POST submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/erph/tambah.php');
    }

    $tindakan = $_POST['tindakan'] ?? 'simpan';

    // Kumpul data
    $data = [
        'no_rujukan'          => clean($_POST['no_rujukan'] ?? ''),
        'tajuk'               => clean($_POST['tajuk'] ?? ''),
        'tarikh'              => clean($_POST['tarikh'] ?? date('Y-m-d')),
        'masa_mula'           => clean($_POST['masa_mula'] ?? ''),
        'masa_tamat'          => clean($_POST['masa_tamat'] ?? ''),
        'guru_id'             => (int)($_POST['guru_id'] ?? $_SESSION['user_id']),
        'kelas_id'            => (int)($_POST['kelas_id'] ?? 0),
        'nama_kelas'          => clean($_POST['nama_kelas'] ?? ''),
        'subjek_id'           => (int)($_POST['subjek_id'] ?? 0),
        'nama_subjek'         => clean($_POST['nama_subjek'] ?? ''),
        'kurikulum_id'        => (int)($_POST['kurikulum_id'] ?? 0) ?: null,
        'standard_kandungan'  => clean($_POST['standard_kandungan'] ?? ''),
        'standard_pembelajaran' => clean($_POST['standard_pembelajaran'] ?? ''),
        'standard_prestasi'   => clean($_POST['standard_prestasi'] ?? ''),
        'evidens'             => clean($_POST['evidens'] ?? ''),
        'hasil_pembelajaran'  => clean($_POST['hasil_pembelajaran'] ?? ''),
        'pendekatan_pdp'      => clean($_POST['pendekatan_pdp'] ?? ''),
        'refleksi'            => clean($_POST['refleksi'] ?? ''),
        'tindakan_susulan'    => clean($_POST['tindakan_susulan'] ?? ''),
        'bil_murid_hadir'     => (int)($_POST['bil_murid_hadir'] ?? 0),
        'tahun'               => (int)($_POST['tahun'] ?? TAHUN_SEMASA),
        'status'              => in_array($_POST['status'] ?? '', ['draf','aktif','arkib']) ? $_POST['status'] : 'draf',
    ];

    // Validasi asas
    $errors = [];
    if (empty($data['tajuk']))  $errors[] = 'Tajuk wajib diisi.';
    if (empty($data['tarikh'])) $errors[] = 'Tarikh wajib diisi.';
    if (empty($data['no_rujukan'])) {
        $data['no_rujukan'] = janaNoRujukan('ERPH', 'erph');
    }

    // SP columns
    $spCols = ['SP1','SP2','SP3','SP4','SP5','SP6'];
    foreach ($spCols as $sp) {
        $data[$sp] = isset($_POST[$sp]) ? clean($_POST[$sp]) : null;
    }

    // Upload lampiran
    if (!empty($_FILES['fail_lampiran']['name'])) {
        $file = $_FILES['fail_lampiran'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Jenis fail lampiran tidak dibenarkan.';
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Saiz fail melebihi 5MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $namaFail = 'erph_' . uniqid() . '.' . $ext;
            $subfolder = UPLOAD_PATH . 'lampiran/';
            if (!is_dir($subfolder)) mkdir($subfolder, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $subfolder . $namaFail)) {
                $data['fail_lampiran'] = 'assets/uploads/lampiran/' . $namaFail;
            } else {
                $errors[] = 'Gagal muat naik lampiran.';
            }
        }
    }

    if ($errors) {
        setFlash('danger', implode('<br>', $errors));
        // Keep old POST data
        $_SESSION['form_old'] = $_POST;
        redirect(BASE_URL . '/modules/erph/tambah.php');
    }

    $data['created_at'] = date('Y-m-d H:i:s');

    try {
        $id = dbInsert('erph', $data);

        // Simpan kehadiran murid
        $muridHadir = $_POST['murid_hadir'] ?? [];
        $muridList  = $_POST['murid_ids'] ?? [];
        if ($muridList) {
            foreach ($muridList as $muridId) {
                dbInsert('erph_murid', [
                    'erph_id'  => $id,
                    'murid_id' => (int)$muridId,
                    'hadir'    => in_array($muridId, $muridHadir) ? 1 : 0,
                ]);
            }
            // Update bil_murid_hadir
            $bilHadir = count($muridHadir);
            dbUpdate('erph', ['bil_murid_hadir' => $bilHadir], 'id=?', [$id]);
        }

        logAktiviti('tambah', 'erph', (int)$id, 'Tambah ERPH: ' . $data['no_rujukan']);
        setFlash('success', 'ERPH berjaya ditambah. No. Rujukan: ' . $data['no_rujukan']);
        redirect(BASE_URL . '/modules/erph/lihat.php?id=' . $id);
    } catch (Exception $e) {
        setFlash('danger', 'Ralat sistem: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/erph/tambah.php');
    }
}

// ---- Restore old form data if redirect back ----
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

// ---- Dropdown data ----
$senaraiGuru   = getSenaraiGuru();
$senaraiKelas  = getSenaraiKelas();
$senaraiSubjek = getSenaraiSubjek();

$defaultGuruId = $_SESSION['user_id'] ?? 0;
$defaultNoRuj  = janaNoRujukan('ERPH', 'erph');

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Tambah ERPH</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/erph/index.php">ERPH</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol></nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/erph/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?= showFlash() ?>

<form method="POST" action="" enctype="multipart/form-data" id="formErph" data-autosave="erph">
    <?= csrfField() ?>
    <input type="hidden" name="tindakan" value="simpan">

    <div class="row g-3">
        <!-- LEFT COLUMN -->
        <div class="col-lg-8">

            <!-- SECTION 1: Maklumat Asas -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Bahagian 1: Maklumat Asas</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Rujukan</label>
                            <div class="input-group">
                                <input type="text" name="no_rujukan" id="no_rujukan" class="form-control"
                                       value="<?= clean($old['no_rujukan'] ?? $defaultNoRuj) ?>" placeholder="ERPH/2024/0001">
                                <button type="button" class="btn btn-outline-secondary" id="btnJanaNoRuj" title="Jana semula">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                            <div class="form-text">Klik butang untuk jana nombor automatik.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun</label>
                            <select name="tahun" class="form-select">
                                <?php for ($y = (int)TAHUN_SEMASA + 1; $y >= (int)TAHUN_SEMASA - 4; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($old['tahun'] ?? TAHUN_SEMASA) == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tajuk PdP <span class="text-danger">*</span></label>
                            <input type="text" name="tajuk" class="form-control" required
                                   value="<?= clean($old['tajuk'] ?? '') ?>" placeholder="Contoh: Pengenalan kepada Algebra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tarikh <span class="text-danger">*</span></label>
                            <input type="date" name="tarikh" class="form-control" required
                                   value="<?= clean($old['tarikh'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Masa Mula</label>
                            <input type="time" name="masa_mula" class="form-control"
                                   value="<?= clean($old['masa_mula'] ?? '07:30') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Masa Tamat</label>
                            <input type="time" name="masa_tamat" class="form-control"
                                   value="<?= clean($old['masa_tamat'] ?? '08:30') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Guru</label>
                            <select name="guru_id" id="guru_id" class="form-select select2">
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($senaraiGuru as $g): ?>
                                    <option value="<?= $g['id'] ?>"
                                        <?= ($old['guru_id'] ?? $defaultGuruId) == $g['id'] ? 'selected' : '' ?>>
                                        <?= clean($g['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas</label>
                            <select name="kelas_id" id="kelas_id" class="form-select select2">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($senaraiKelas as $k): ?>
                                    <option value="<?= $k['id'] ?>"
                                        <?= ($old['kelas_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
                                        <?= clean($k['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Kelas (manual)</label>
                            <input type="text" name="nama_kelas" id="nama_kelas" class="form-control"
                                   value="<?= clean($old['nama_kelas'] ?? '') ?>" placeholder="Auto-isi dari kelas">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subjek</label>
                            <select name="subjek_id" id="subjek_id" class="form-select select2">
                                <option value="">-- Pilih Subjek --</option>
                                <?php foreach ($senaraiSubjek as $s): ?>
                                    <option value="<?= $s['id'] ?>"
                                        <?= ($old['subjek_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                                        <?= clean($s['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nama Subjek (manual)</label>
                            <input type="text" name="nama_subjek" id="nama_subjek" class="form-control"
                                   value="<?= clean($old['nama_subjek'] ?? '') ?>" placeholder="Auto-isi dari subjek">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Standard Kurikulum -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-book me-2"></i>Bahagian 2: Standard Kurikulum (DSKP)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" id="mata_pelajaran" class="form-control"
                                   value="<?= clean($old['mata_pelajaran'] ?? '') ?>" placeholder="Auto-isi dari subjek">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tingkatan</label>
                            <input type="text" name="tingkatan" id="tingkatan" class="form-control"
                                   value="<?= clean($old['tingkatan'] ?? '') ?>" placeholder="Contoh: 4">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Carian Kurikulum (DSKP)</label>
                            <select name="kurikulum_id" id="kurikulum_id" class="form-select select2-kurikulum" style="width:100%">
                                <option value="">-- Taip untuk cari standard kandungan --</option>
                                <?php if (!empty($old['kurikulum_id'])): ?>
                                    <?php $kur = dbFetch("SELECT * FROM kurikulum WHERE id=?", [(int)$old['kurikulum_id']]); ?>
                                    <?php if ($kur): ?>
                                    <option value="<?= $kur['id'] ?>" selected>
                                        <?= clean($kur['standard_kandungan_kod'] . ' - ' . $kur['standard_kandungan']) ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Cari berdasarkan kod atau kandungan standard.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Standard Kandungan</label>
                            <textarea name="standard_kandungan" id="standard_kandungan" class="form-control" rows="2"
                                      placeholder="Auto-isi atau taip manual..."><?= clean($old['standard_kandungan'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Standard Pembelajaran</label>
                            <textarea name="standard_pembelajaran" id="standard_pembelajaran" class="form-control" rows="2"
                                      placeholder="Auto-isi atau taip manual..."><?= clean($old['standard_pembelajaran'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Standard Prestasi</label>
                            <select name="standard_prestasi" id="standard_prestasi" class="form-select">
                                <option value="">-- Pilih SP --</option>
                                <?php foreach (['SP1','SP2','SP3','SP4','SP5','SP6'] as $sp): ?>
                                    <option value="<?= $sp ?>" <?= ($old['standard_prestasi'] ?? '') === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold d-block">Nilai SP (tandakan semua yang berkaitan)</label>
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                <?php
                                $spColors = ['SP1'=>'secondary','SP2'=>'info','SP3'=>'primary','SP4'=>'success','SP5'=>'warning','SP6'=>'danger'];
                                foreach ($spColors as $sp => $col):
                                ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="<?= $sp ?>" id="chk<?= $sp ?>"
                                           value="<?= $sp ?>" <?= !empty($old[$sp]) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="chk<?= $sp ?>">
                                        <span class="badge bg-<?= $col ?>"><?= $sp ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Maklumat PdP -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i>Bahagian 3: Maklumat PdP</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Evidens Pembelajaran</label>
                            <textarea name="evidens" class="form-control" rows="2"
                                      placeholder="Contoh: Latihan dalam buku, ujian lisan, hasil kerja..."><?= clean($old['evidens'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Hasil Pembelajaran</label>
                            <textarea name="hasil_pembelajaran" class="form-control" rows="2"
                                      placeholder="Murid dapat..."><?= clean($old['hasil_pembelajaran'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Pendekatan PdP</label>
                            <textarea name="pendekatan_pdp" class="form-control" rows="2"
                                      placeholder="Contoh: Pembelajaran koperatif, inkuiri, kontekstual..."><?= clean($old['pendekatan_pdp'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Bilangan Murid Hadir</label>
                            <input type="number" name="bil_murid_hadir" id="bil_murid_hadir" class="form-control"
                                   value="<?= (int)($old['bil_murid_hadir'] ?? 0) ?>" min="0" placeholder="Auto-kira dari senarai">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Refleksi</label>
                            <textarea name="refleksi" class="form-control" rows="3"
                                      placeholder="Penilaian keberkesanan PdP, kekuatan dan kelemahan..."><?= clean($old['refleksi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tindakan Susulan</label>
                            <textarea name="tindakan_susulan" class="form-control" rows="2"
                                      placeholder="Langkah pemulihan, pengayaan atau tindakan lanjut..."><?= clean($old['tindakan_susulan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Senarai Murid -->
            <div class="card border-0 shadow-sm mb-3" id="cardMurid">
                <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-people me-2"></i>Bahagian 4: Kehadiran Murid</h6>
                    <small id="muridCount" class="badge bg-dark">0 murid</small>
                </div>
                <div class="card-body p-0" id="muridContainer">
                    <div class="text-center py-4 text-muted" id="muridPlaceholder">
                        <i class="bi bi-arrow-up-circle fs-3 d-block mb-2"></i>
                        Pilih kelas di atas untuk muat senarai murid.
                    </div>
                    <div id="muridTable" class="d-none">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                            <small class="text-muted">Tandakan murid yang <strong>hadir</strong></small>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-success" id="btnTandaSemua">Tandai Semua</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" id="btnBatalSemua">Batal Semua</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="tableMurid">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50"><input type="checkbox" id="chkAll" class="form-check-input"></th>
                                        <th>#</th>
                                        <th>Nama Murid</th>
                                        <th class="text-center">Jantina</th>
                                        <th class="text-center">Hadir</th>
                                    </tr>
                                </thead>
                                <tbody id="muridTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: Lampiran -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-secondary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Bahagian 5: Lampiran</h6>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold">Fail Lampiran</label>
                    <input type="file" name="fail_lampiran" class="form-control"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                    <div class="form-text">Format dibenarkan: PDF, DOC, DOCX, JPG, PNG, WEBP. Saiz maksimum: 5MB.</div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                <div class="card-header bg-dark text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-floppy me-2"></i>Simpan Rekod</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draf"  <?= ($old['status'] ?? 'draf') === 'draf'  ? 'selected' : '' ?>>Draf</option>
                            <option value="aktif" <?= ($old['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                        <div class="form-text">Draf = belum selesai. Aktif = sudah lengkap.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy2 me-1"></i>Simpan ERPH
                        </button>
                        <button type="submit" class="btn btn-outline-success" onclick="document.querySelector('[name=status]').value='aktif'">
                            <i class="bi bi-check-circle me-1"></i>Simpan & Aktifkan
                        </button>
                        <a href="<?= BASE_URL ?>/modules/erph/index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-white small text-muted">
                    <i class="bi bi-info-circle me-1"></i>Draf disimpan automatik setiap 30 saat.
                </div>
            </div>
        </div>
    </div><!-- end row -->
</form>

<?php
$extraScript = <<<JS
<script>
$(function(){
    // ---- Select2 init ----
    $('.select2').select2({ theme:'bootstrap-5', width:'100%' });

    // ---- Select2 AJAX untuk kurikulum ----
    $('#kurikulum_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        minimumInputLength: 2,
        placeholder: '-- Taip untuk cari standard kandungan --',
        ajax: {
            url: BASE_URL + '/ajax/search.php',
            data: function(params){ return { modul:'kurikulum', q: params.term }; },
            processResults: function(data){ return { results: data }; },
            delay: 300
        }
    });

    // ---- Auto-fill apabila kurikulum dipilih ----
    $('#kurikulum_id').on('change', function(){
        var id = $(this).val();
        if (!id) return;
        $.get(BASE_URL + '/ajax/search.php', { modul:'kurikulum_detail', id: id }, function(d){
            if (d && d.standard_kandungan) {
                $('#standard_kandungan').val(d.standard_kandungan);
                $('#standard_pembelajaran').val(d.standard_pembelajaran);
                var sp = d.standard_prestasi;
                if (sp) {
                    $('#standard_prestasi').val(sp);
                    // Tick checkbox
                    $('[id^=chkSP]').prop('checked', false);
                    $('#chk' + sp).prop('checked', true);
                }
            }
        }, 'json');
    });

    // ---- Auto-fill nama_kelas apabila kelas dipilih ----
    $('#kelas_id').on('change', function(){
        var label = $(this).find(':selected').text();
        if (label && label !== '-- Pilih Kelas --') {
            $('#nama_kelas').val(label);
        }
        loadMurid($(this).val());
    });

    // ---- Auto-fill nama_subjek apabila subjek dipilih ----
    $('#subjek_id').on('change', function(){
        var label = $(this).find(':selected').text();
        if (label && label !== '-- Pilih Subjek --') {
            var namaParts = label.split(' - ');
            $('#nama_subjek').val(namaParts.length > 1 ? namaParts.slice(1).join(' - ') : label);
            $('#mata_pelajaran').val(namaParts.length > 1 ? namaParts.slice(1).join(' - ') : label);
        }
    });

    // ---- Jana No Rujukan ----
    $('#btnJanaNoRuj').on('click', function(){
        $.get(BASE_URL + '/ajax/search.php', { modul:'no_rujukan', prefix:'ERPH', jadual:'erph' }, function(d){
            if (d && d.no_rujukan) $('#no_rujukan').val(d.no_rujukan);
        }, 'json');
    });

    // ---- Load senarai murid via AJAX ----
    function loadMurid(kelasId) {
        if (!kelasId) {
            $('#muridPlaceholder').removeClass('d-none');
            $('#muridTable').addClass('d-none');
            return;
        }
        $('#muridPlaceholder').html('<div class="spinner-border spinner-border-sm me-2"></div>Memuatkan senarai murid...')
            .removeClass('d-none');
        $.get(BASE_URL + '/ajax/search.php', { modul:'murid_kelas', kelas_id: kelasId }, function(data){
            $('#muridPlaceholder').addClass('d-none');
            $('#muridTable').removeClass('d-none');

            var html = '';
            $.each(data, function(i, m){
                html += '<tr>' +
                    '<td><input type="hidden" name="murid_ids[]" value="' + m.id + '">' +
                    '<input type="checkbox" class="form-check-input chk-murid" name="murid_hadir[]" value="' + m.id + '" checked></td>' +
                    '<td class="text-muted small">' + (i+1) + '</td>' +
                    '<td>' + m.nama + '</td>' +
                    '<td class="text-center"><span class="badge bg-' + (m.jantina=='L'?'info':'danger') + ' bg-opacity-75">' +
                        (m.jantina=='L'?'L':'P') + '</span></td>' +
                    '<td class="text-center"><i class="bi bi-check-circle-fill text-success chk-icon"></i></td>' +
                    '</tr>';
            });
            $('#muridTbody').html(html);
            $('#muridCount').text(data.length + ' murid');
            updateBilHadir();
        }, 'json').fail(function(){
            $('#muridPlaceholder').removeClass('d-none').html('<span class="text-danger">Gagal memuatkan murid.</span>');
            $('#muridTable').addClass('d-none');
        });
    }

    // ---- Update bil murid hadir ----
    function updateBilHadir() {
        var bil = $('.chk-murid:checked').length;
        $('#bil_murid_hadir').val(bil);
    }

    $(document).on('change', '.chk-murid', function(){
        var row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            row.find('.chk-icon').removeClass('bi-circle text-muted').addClass('bi-check-circle-fill text-success');
        } else {
            row.find('.chk-icon').removeClass('bi-check-circle-fill text-success').addClass('bi-circle text-muted');
        }
        updateBilHadir();
    });

    $('#chkAll').on('change', function(){
        $('.chk-murid').prop('checked', $(this).is(':checked')).trigger('change');
    });

    $('#btnTandaSemua').on('click', function(){
        $('.chk-murid').prop('checked', true).trigger('change');
    });
    $('#btnBatalSemua').on('click', function(){
        $('.chk-murid').prop('checked', false).trigger('change');
    });
});

// BASE_URL for JS
var BASE_URL = '<?= BASE_URL ?>';
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
