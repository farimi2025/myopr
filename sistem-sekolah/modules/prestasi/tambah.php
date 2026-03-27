<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Tambah Rekod Markah';

// ── Dropdown data ─────────────────────────────────────────────────────────────
$senaraSubjek = dbFetchAll(
    "SELECT id, kod, nama, CONCAT(kod,' - ',nama) AS label FROM subjek WHERE status='aktif' ORDER BY nama"
);
$senaraKelas  = dbFetchAll(
    "SELECT id, tingkatan, nama_kelas, CONCAT(tingkatan,' ',nama_kelas) AS label
     FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [TAHUN_SEMASA]
);
$jenisList = ['PT1','PT2','PT3','PAT','ULBS','PBS','UASA','PPT','Ujian Harian','Lain-lain'];

// Pre-fill murid_id from GET (when coming from lihat.php)
$preMuridId = (int)($_GET['murid_id'] ?? 0);
$preMurid   = null;
if ($preMuridId > 0) {
    $preMurid = dbFetch(
        "SELECT m.id, m.nama, m.no_pendaftaran, k.tingkatan, k.nama_kelas
         FROM murid m LEFT JOIN kelas k ON k.id=m.kelas_id
         WHERE m.id=? LIMIT 1",
        [$preMuridId]
    );
}

$errors   = [];
$input    = [];
$mode     = clean($_POST['mode'] ?? $_GET['mode'] ?? 'individu'); // individu | batch
$batchOk  = false;

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/prestasi/tambah.php');
    }

    $mode = clean($_POST['mode'] ?? 'individu');

    if ($mode === 'individu') {
        // ── INDIVIDU MODE ─────────────────────────────────────────────────
        $input['murid_id']        = (int)($_POST['murid_id']        ?? 0);
        $input['subjek_id']       = (int)($_POST['subjek_id']       ?? 0);
        $input['nama_subjek']     = trim($_POST['nama_subjek']      ?? '');
        $input['jenis_penilaian'] = trim($_POST['jenis_penilaian']  ?? '');
        $input['penggal']         = (int)($_POST['penggal']         ?? 1);
        $input['markah']          = (float)($_POST['markah']        ?? 0);
        $input['gred']            = trim($_POST['gred']             ?? '');
        $input['nilai_gred']      = (int)($_POST['nilai_gred']      ?? 0);
        $input['band']            = trim($_POST['band']             ?? '');
        $input['tahun']           = (int)($_POST['tahun']           ?? TAHUN_SEMASA);
        $input['catatan']         = trim($_POST['catatan']          ?? '');

        // Auto-fill nama_subjek from subjek table if blank
        if ($input['subjek_id'] > 0 && $input['nama_subjek'] === '') {
            $subj = dbFetch("SELECT nama FROM subjek WHERE id=?", [$input['subjek_id']]);
            if ($subj) $input['nama_subjek'] = $subj['nama'];
        }

        if ($input['murid_id'] <= 0)          $errors[] = 'Murid wajib dipilih.';
        if ($input['nama_subjek'] === '')       $errors[] = 'Subjek wajib dipilih atau diisi.';
        if ($input['jenis_penilaian'] === '')   $errors[] = 'Jenis penilaian wajib dipilih.';
        if ($input['markah'] < 0 || $input['markah'] > 100) $errors[] = 'Markah mestilah antara 0 – 100.';

        // Auto-calculate gred if not set
        if ($input['gred'] === '' && $input['markah'] >= 0) {
            $gredResult          = kiraMGred($input['markah']);
            $input['gred']       = $gredResult['gred'];
            $input['nilai_gred'] = $gredResult['nilai'];
        }

        if (empty($errors)) {
            dbInsert('prestasi', $input);
            logAktiviti('tambah', 'prestasi', $input['murid_id'],
                'Tambah markah ' . $input['nama_subjek'] . ' untuk murid ID ' . $input['murid_id']);
            setFlash('success', 'Rekod markah berjaya ditambah.');
            if ($preMuridId > 0) {
                redirect(BASE_URL . '/modules/murid/lihat.php?id=' . $preMuridId);
            }
            redirect(BASE_URL . '/modules/prestasi/index.php');
        }

    } elseif ($mode === 'batch') {
        // ── BATCH MODE ────────────────────────────────────────────────────
        $batchKelasId       = (int)($_POST['batch_kelas_id']       ?? 0);
        $batchSubjekId      = (int)($_POST['batch_subjek_id']      ?? 0);
        $batchNamaSubjek    = trim($_POST['batch_nama_subjek']      ?? '');
        $batchJenis         = trim($_POST['batch_jenis_penilaian']  ?? '');
        $batchPenggal       = (int)($_POST['batch_penggal']        ?? 1);
        $batchTahun         = (int)($_POST['batch_tahun']          ?? TAHUN_SEMASA);
        $batchMarkah        = $_POST['batch_markah']                ?? [];
        $batchMuridIds      = $_POST['batch_murid_id']              ?? [];

        // Auto-fill nama_subjek
        if ($batchSubjekId > 0 && $batchNamaSubjek === '') {
            $subj = dbFetch("SELECT nama FROM subjek WHERE id=?", [$batchSubjekId]);
            if ($subj) $batchNamaSubjek = $subj['nama'];
        }

        if ($batchKelasId <= 0)    $errors[] = 'Kelas wajib dipilih untuk kemasukan kumpulan.';
        if ($batchNamaSubjek === '') $errors[] = 'Subjek wajib dipilih untuk kemasukan kumpulan.';
        if ($batchJenis === '')      $errors[] = 'Jenis penilaian wajib dipilih.';
        if (empty($batchMuridIds))   $errors[] = 'Tiada murid untuk direkodkan.';

        if (empty($errors)) {
            $inserted = 0;
            foreach ($batchMuridIds as $mid) {
                $mid = (int)$mid;
                if ($mid <= 0) continue;
                $markah = isset($batchMarkah[$mid]) ? (float)$batchMarkah[$mid] : null;
                if ($markah === null || $markah < 0 || $markah > 100) continue;

                $gredResult = kiraMGred($markah);
                // Check if record already exists for same murid/subjek/jenis/penggal/tahun
                $existing = dbValue(
                    "SELECT id FROM prestasi WHERE murid_id=? AND (subjek_id=? OR nama_subjek=?) AND jenis_penilaian=? AND penggal=? AND tahun=?",
                    [$mid, $batchSubjekId ?: 0, $batchNamaSubjek, $batchJenis, $batchPenggal, $batchTahun]
                );
                if ($existing) {
                    dbUpdate('prestasi', [
                        'markah'     => $markah,
                        'gred'       => $gredResult['gred'],
                        'nilai_gred' => $gredResult['nilai'],
                    ], 'id=?', [$existing]);
                } else {
                    dbInsert('prestasi', [
                        'murid_id'        => $mid,
                        'subjek_id'       => $batchSubjekId ?: null,
                        'nama_subjek'     => $batchNamaSubjek,
                        'jenis_penilaian' => $batchJenis,
                        'penggal'         => $batchPenggal,
                        'markah'          => $markah,
                        'gred'            => $gredResult['gred'],
                        'nilai_gred'      => $gredResult['nilai'],
                        'band'            => '',
                        'tahun'           => $batchTahun,
                        'catatan'         => '',
                    ]);
                }
                $inserted++;
            }
            logAktiviti('tambah_batch', 'prestasi', $batchKelasId,
                "Kemasukan kumpulan $batchNamaSubjek - $batchJenis ($inserted rekod)");
            setFlash('success', "Kemasukan kumpulan berjaya. <strong>$inserted</strong> rekod telah disimpan.");
            redirect(BASE_URL . '/modules/prestasi/index.php');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Rekod Markah</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/prestasi/index.php">Prestasi</a></li>
                <li class="breadcrumb-item active">Tambah</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/prestasi/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?= showFlash() ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible d-flex align-items-start mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0"></i>
    <div>
        <strong>Sila betulkan ralat berikut:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
        </ul>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Mode Toggle -->
<div class="mb-4">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary <?= $mode === 'individu' ? 'active' : '' ?>" id="btnIndividu">
            <i class="bi bi-person me-1"></i>Individu
        </button>
        <button type="button" class="btn btn-outline-primary <?= $mode === 'batch' ? 'active' : '' ?>" id="btnBatch">
            <i class="bi bi-people me-1"></i>Kemasukan Kumpulan
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     INDIVIDU FORM
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="panelIndividu" class="<?= $mode === 'batch' ? 'd-none' : '' ?>">
<form id="mainForm" method="POST" action="" data-autosave="prestasi" data-rekod-id="0" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="mode" value="individu">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2 text-primary"></i>Maklumat Murid & Penilaian</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Murid select2 -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Murid <span class="text-danger">*</span></label>
                    <select name="murid_id" id="muridSelect" class="form-select" required>
                        <option value="">-- Cari & Pilih Murid --</option>
                        <?php if ($preMurid): ?>
                        <option value="<?= $preMurid['id'] ?>" selected>
                            <?= clean($preMurid['nama']) ?> (<?= clean($preMurid['no_pendaftaran']) ?>)
                            <?php if ($preMurid['tingkatan']): ?>
                                - <?= clean($preMurid['tingkatan']) ?> <?= clean($preMurid['nama_kelas']) ?>
                            <?php endif; ?>
                        </option>
                        <?php endif; ?>
                    </select>
                    <div class="form-text text-muted">Taip nama atau no. pendaftaran untuk mencari</div>
                </div>

                <!-- Tahun -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= (int)($input['tahun'] ?? TAHUN_SEMASA) ?>" min="2000" max="2099">
                </div>

                <!-- Penggal -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Penggal <span class="text-danger">*</span></label>
                    <select name="penggal" class="form-select" required>
                        <option value="1" <?= (int)($input['penggal'] ?? 1) === 1 ? 'selected' : '' ?>>Penggal 1</option>
                        <option value="2" <?= (int)($input['penggal'] ?? 1) === 2 ? 'selected' : '' ?>>Penggal 2</option>
                    </select>
                </div>

                <!-- Jenis Penilaian -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Jenis Penilaian <span class="text-danger">*</span></label>
                    <select name="jenis_penilaian" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach ($jenisList as $j): ?>
                        <option value="<?= $j ?>" <?= (($input['jenis_penilaian'] ?? '') === $j) ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subjek -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Subjek <span class="text-danger">*</span></label>
                    <select name="subjek_id" id="subjekSelect" class="form-select">
                        <option value="">-- Pilih Subjek --</option>
                        <?php foreach ($senaraSubjek as $s): ?>
                        <option value="<?= $s['id'] ?>"
                                data-nama="<?= clean($s['nama']) ?>"
                                <?= (int)($input['subjek_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= clean($s['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nama Subjek (auto-filled) -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Nama Subjek (simpan)</label>
                    <input type="text" name="nama_subjek" id="namaSubjek" class="form-control"
                           value="<?= clean($input['nama_subjek'] ?? '') ?>"
                           placeholder="Auto-isi atau taip manual">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-123 me-2 text-primary"></i>Keputusan</h6>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <!-- Markah -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Markah (0–100) <span class="text-danger">*</span></label>
                    <input type="number" name="markah" id="markahInput" class="form-control fw-bold fs-5"
                           value="<?= isset($input['markah']) ? (float)$input['markah'] : '' ?>"
                           min="0" max="100" step="0.5" placeholder="0 – 100" required>
                </div>

                <!-- Gred (auto-fill) -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Gred</label>
                    <input type="text" name="gred" id="gredInput" class="form-control fw-bold text-center fs-5"
                           value="<?= clean($input['gred'] ?? '') ?>"
                           placeholder="Auto" maxlength="3"
                           style="background:#f8f9fa">
                    <div class="form-text text-muted">Auto-kira atau ubah manual</div>
                </div>

                <!-- Nilai Gred -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Nilai Gred</label>
                    <input type="number" name="nilai_gred" id="nilaiGredInput" class="form-control text-center"
                           value="<?= (int)($input['nilai_gred'] ?? 0) ?>" min="1" max="12" placeholder="1–12">
                </div>

                <!-- Band -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Band (KSSR)</label>
                    <input type="text" name="band" class="form-control"
                           value="<?= clean($input['band'] ?? '') ?>" placeholder="1–6" maxlength="2">
                </div>

                <!-- Gred preview -->
                <div class="col-md-3">
                    <div id="gredPreview" class="p-3 rounded text-center" style="background:#f8f9fa;min-height:60px">
                        <div class="text-muted small">Pratonton Gred</div>
                        <div id="gredPreviewBadge" class="fw-bold fs-3 mt-1">—</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label class="form-label fw-semibold small">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"
                              placeholder="Catatan tambahan (jika ada)..."><?= clean($input['catatan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= $preMuridId ? BASE_URL . '/modules/murid/lihat.php?id=' . $preMuridId : BASE_URL . '/modules/prestasi/index.php' ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Markah
        </button>
    </div>
</form>
</div><!-- /panelIndividu -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     BATCH FORM
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="panelBatch" class="<?= $mode !== 'batch' ? 'd-none' : '' ?>">
<form method="POST" action="" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="mode" value="batch">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-people-fill me-2 text-primary"></i>Tetapan Kemasukan Kumpulan</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Kelas <span class="text-danger">*</span></label>
                    <select name="batch_kelas_id" id="batchKelas" class="form-select select2kelas">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($senaraKelas as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= clean($k['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Subjek <span class="text-danger">*</span></label>
                    <select name="batch_subjek_id" id="batchSubjek" class="form-select">
                        <option value="">-- Pilih Subjek --</option>
                        <?php foreach ($senaraSubjek as $s): ?>
                        <option value="<?= $s['id'] ?>" data-nama="<?= clean($s['nama']) ?>"><?= clean($s['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Nama Subjek (simpan)</label>
                    <input type="text" name="batch_nama_subjek" id="batchNamaSubjek" class="form-control"
                           placeholder="Auto-isi dari pilihan subjek">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Jenis Penilaian <span class="text-danger">*</span></label>
                    <select name="batch_jenis_penilaian" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($jenisList as $j): ?>
                        <option value="<?= $j ?>"><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Penggal</label>
                    <select name="batch_penggal" class="form-select">
                        <option value="1">Penggal 1</option>
                        <option value="2">Penggal 2</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="batch_tahun" class="form-control" value="<?= TAHUN_SEMASA ?>" min="2000" max="2099">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="btnLoadMurid" class="btn btn-outline-primary w-100">
                        <i class="bi bi-people me-1"></i>Muatkan Senarai Murid
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch table (loaded by JS) -->
    <div id="batchTableContainer"></div>

    <div id="batchSimpanContainer" class="d-none d-flex gap-2 justify-content-end mb-4">
        <a href="<?= BASE_URL ?>/modules/prestasi/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Semua Markah
        </button>
    </div>
</form>
</div><!-- /panelBatch -->

<?php
$extraScript = '<script>var BASE_URL = \'' . BASE_URL . '\';</script>' . <<<'JS'
<script>
// ── Grade calculation (mirrors PHP kiraMGred) ─────────────────────────────
function kiraMGredJS(markah) {
    markah = parseFloat(markah);
    if (isNaN(markah)) return {gred:'—', nilai:0, warna:'#9ca3af'};
    if (markah >= 90) return {gred:'A+', nilai:1,  warna:'#15803d'};
    if (markah >= 80) return {gred:'A',  nilai:2,  warna:'#16a34a'};
    if (markah >= 70) return {gred:'A-', nilai:3,  warna:'#22c55e'};
    if (markah >= 65) return {gred:'B+', nilai:4,  warna:'#0284c7'};
    if (markah >= 60) return {gred:'B',  nilai:5,  warna:'#0ea5e9'};
    if (markah >= 55) return {gred:'B-', nilai:6,  warna:'#38bdf8'};
    if (markah >= 50) return {gred:'C+', nilai:7,  warna:'#d97706'};
    if (markah >= 45) return {gred:'C',  nilai:8,  warna:'#f59e0b'};
    if (markah >= 40) return {gred:'C-', nilai:9,  warna:'#fbbf24'};
    if (markah >= 35) return {gred:'D',  nilai:10, warna:'#ea580c'};
    if (markah >= 30) return {gred:'E',  nilai:11, warna:'#dc2626'};
    return {gred:'G', nilai:12, warna:'#7f1d1d'};
}

$(function(){
    // ── Mode toggle ───────────────────────────────────────────────────────
    $('#btnIndividu').on('click', function(){
        $(this).addClass('active'); $('#btnBatch').removeClass('active');
        $('#panelIndividu').removeClass('d-none'); $('#panelBatch').addClass('d-none');
    });
    $('#btnBatch').on('click', function(){
        $(this).addClass('active'); $('#btnIndividu').removeClass('active');
        $('#panelBatch').removeClass('d-none'); $('#panelIndividu').addClass('d-none');
    });

    // ── Select2 for murid (AJAX live search) ──────────────────────────────
    $('#muridSelect').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari & Pilih Murid --',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: BASE_URL + '/ajax/cari_murid.php',
            dataType: 'json',
            delay: 300,
            data: function(p){ return { q: p.term }; },
            processResults: function(d){ return { results: d.results || [] }; },
            cache: true
        }
    });

    // ── Select2 for kelas (batch) ─────────────────────────────────────────
    $('.select2kelas').select2({ theme:'bootstrap-5', placeholder:'-- Pilih Kelas --', allowClear:true, width:'100%' });

    // ── Auto-fill gred when markah changes ───────────────────────────────
    $('#markahInput').on('input', function(){
        const g = kiraMGredJS($(this).val());
        $('#gredInput').val(g.gred !== '—' ? g.gred : '');
        $('#nilaiGredInput').val(g.nilai > 0 ? g.nilai : '');
        $('#gredPreviewBadge').text(g.gred).css('color', g.warna);
        $('#gredPreview').css('background', g.warna + '15');
    });

    // ── Subjek auto-fill nama ─────────────────────────────────────────────
    $('#subjekSelect').on('change', function(){
        const nama = $(this).find(':selected').data('nama') || '';
        $('#namaSubjek').val(nama);
    });
    $('#batchSubjek').on('change', function(){
        const nama = $(this).find(':selected').data('nama') || '';
        $('#batchNamaSubjek').val(nama);
    });

    // ── Load murid list for batch ─────────────────────────────────────────
    $('#btnLoadMurid').on('click', function(){
        const kelasId = $('#batchKelas').val();
        if (!kelasId) { Swal.fire('Sila pilih kelas dahulu.','','warning'); return; }

        $(this).html('<span class="spinner-border spinner-border-sm me-1"></span>Memuatkan...')
               .prop('disabled', true);

        $.get(BASE_URL + '/ajax/murid_kelas.php', {kelas_id: kelasId}, function(data){
            if (!data.murid || data.murid.length === 0) {
                $('#batchTableContainer').html('<div class="alert alert-info">Tiada murid dalam kelas ini.</div>');
                $('#batchSimpanContainer').addClass('d-none');
                return;
            }
            let html = `<div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2 text-primary"></i>
                    Senarai Murid — ${data.nama_kelas || ''} (${data.murid.length} murid)</h6>
                </div>
                <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>No. Pendaftaran</th>
                        <th>Nama Murid</th>
                        <th class="text-center" style="width:140px">Markah (0–100)</th>
                        <th class="text-center" style="width:80px">Gred</th>
                    </tr>
                </thead>
                <tbody>`;
            data.murid.forEach((m, i) => {
                html += `<tr>
                    <td class="ps-3 text-muted small">${i+1}</td>
                    <td><code class="small">${m.no_pendaftaran}</code>
                        <input type="hidden" name="batch_murid_id[]" value="${m.id}">
                    </td>
                    <td class="fw-semibold">${m.nama}</td>
                    <td class="text-center">
                        <input type="number" name="batch_markah[${m.id}]"
                               class="form-control form-control-sm text-center fw-bold batch-markah"
                               data-murid="${m.id}" min="0" max="100" step="0.5" placeholder="—">
                    </td>
                    <td class="text-center">
                        <span class="badge fw-bold px-2 batch-gred-badge" id="badge_${m.id}" style="background:#dee2e6;color:#333">—</span>
                    </td>
                </tr>`;
            });
            html += `</tbody></table></div></div>`;
            $('#batchTableContainer').html(html);
            $('#batchSimpanContainer').removeClass('d-none');

            // Live gred update
            $(document).on('input', '.batch-markah', function(){
                const mid = $(this).data('murid');
                const g   = kiraMGredJS($(this).val());
                $(`#badge_${mid}`).text(g.gred).css({background: g.warna+'22', color: g.warna, border:'1px solid '+g.warna+'55'});
            });
        }, 'json').fail(function(){
            $('#batchTableContainer').html('<div class="alert alert-danger">Gagal memuatkan senarai murid.</div>');
        }).always(function(){
            $('#btnLoadMurid').html('<i class="bi bi-people me-1"></i>Muatkan Senarai Murid').prop('disabled', false);
        });
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
