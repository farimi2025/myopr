<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Edit Rekod Markah';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'ID rekod tidak sah.');
    redirect(BASE_URL . '/modules/prestasi/index.php');
}

$prestasi = dbFetch(
    "SELECT p.*, m.nama AS nama_murid, m.no_pendaftaran, m.id AS mid,
            k.tingkatan, k.nama_kelas
     FROM prestasi p
     LEFT JOIN murid m ON m.id = p.murid_id
     LEFT JOIN kelas k ON k.id = m.kelas_id
     WHERE p.id = ? LIMIT 1",
    [$id]
);
if (!$prestasi) {
    setFlash('danger', 'Rekod prestasi tidak dijumpai.');
    redirect(BASE_URL . '/modules/prestasi/index.php');
}

$errors = [];
$input  = $prestasi;

// ── Dropdown data ─────────────────────────────────────────────────────────────
$senaraSubjek = dbFetchAll(
    "SELECT id, kod, nama, CONCAT(kod,' - ',nama) AS label FROM subjek WHERE status='aktif' ORDER BY nama"
);
$jenisList = ['PT1','PT2','PT3','PAT','ULBS','PBS','UASA','PPT','Ujian Harian','Lain-lain'];

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/prestasi/edit.php?id=' . $id);
    }

    $input['subjek_id']       = (int)($_POST['subjek_id']       ?? 0) ?: null;
    $input['nama_subjek']     = trim($_POST['nama_subjek']       ?? '');
    $input['jenis_penilaian'] = trim($_POST['jenis_penilaian']   ?? '');
    $input['penggal']         = (int)($_POST['penggal']          ?? 1);
    $input['markah']          = (float)($_POST['markah']         ?? 0);
    $input['gred']            = trim($_POST['gred']              ?? '');
    $input['nilai_gred']      = (int)($_POST['nilai_gred']       ?? 0);
    $input['band']            = trim($_POST['band']              ?? '');
    $input['tahun']           = (int)($_POST['tahun']            ?? TAHUN_SEMASA);
    $input['catatan']         = trim($_POST['catatan']           ?? '');

    // Auto-fill nama_subjek if blank
    if ($input['subjek_id'] > 0 && $input['nama_subjek'] === '') {
        $subj = dbFetch("SELECT nama FROM subjek WHERE id=?", [$input['subjek_id']]);
        if ($subj) $input['nama_subjek'] = $subj['nama'];
    }

    if ($input['nama_subjek'] === '')       $errors[] = 'Nama subjek wajib diisi.';
    if ($input['jenis_penilaian'] === '')   $errors[] = 'Jenis penilaian wajib dipilih.';
    if ($input['markah'] < 0 || $input['markah'] > 100) $errors[] = 'Markah mestilah antara 0 – 100.';

    // Auto-calculate gred if empty
    if ($input['gred'] === '') {
        $gredResult          = kiraMGred($input['markah']);
        $input['gred']       = $gredResult['gred'];
        $input['nilai_gred'] = $gredResult['nilai'];
    }

    if (empty($errors)) {
        dbUpdate('prestasi', [
            'subjek_id'       => $input['subjek_id'],
            'nama_subjek'     => $input['nama_subjek'],
            'jenis_penilaian' => $input['jenis_penilaian'],
            'penggal'         => $input['penggal'],
            'markah'          => $input['markah'],
            'gred'            => $input['gred'],
            'nilai_gred'      => $input['nilai_gred'],
            'band'            => $input['band'],
            'tahun'           => $input['tahun'],
            'catatan'         => $input['catatan'],
        ], 'id=?', [$id]);

        logAktiviti('kemaskini', 'prestasi', $id,
            'Kemaskini markah ' . $input['nama_subjek'] . ' murid: ' . $prestasi['nama_murid']);
        setFlash('success', 'Rekod markah <strong>' . clean($input['nama_subjek']) . '</strong> berjaya dikemaskini.');

        // Redirect back to student profile if available
        if ($prestasi['mid']) {
            redirect(BASE_URL . '/modules/murid/lihat.php?id=' . $prestasi['mid']);
        }
        redirect(BASE_URL . '/modules/prestasi/index.php');
    }
}

$gredWarna = constant('GRED_WARNA')[$input['gred']] ?? '#6b7280';

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Rekod Markah</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/prestasi/index.php">Prestasi</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/prestasi/aksi.php?action=padam&id=<?= $id ?>&csrf=<?= csrfToken() ?><?= $prestasi['mid'] ? '&redirect=murid&murid_id='.$prestasi['mid'] : '' ?>"
           class="btn btn-outline-danger btn-sm btn-padam"
           data-nama="<?= clean($input['nama_subjek']) ?>">
            <i class="bi bi-trash me-1"></i>Padam
        </a>
        <?php if ($prestasi['mid']): ?>
        <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $prestasi['mid'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Profil
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/modules/prestasi/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <?php endif; ?>
    </div>
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

<!-- Student Info Banner -->
<div class="alert alert-light border d-flex align-items-center gap-3 py-2 mb-4">
    <i class="bi bi-person-circle text-primary fs-4"></i>
    <div>
        <strong><?= clean($prestasi['nama_murid'] ?? '—') ?></strong>
        <span class="text-muted small ms-2"><?= clean($prestasi['no_pendaftaran'] ?? '') ?></span>
        <?php if ($prestasi['tingkatan']): ?>
        <span class="badge bg-light text-dark border ms-2">
            <?= clean($prestasi['tingkatan']) ?> <?= clean($prestasi['nama_kelas']) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="ms-auto text-center">
        <span class="badge fw-bold px-3 py-2 fs-5" style="background-color:<?= $gredWarna ?>;color:#fff">
            <?= clean($prestasi['gred']) ?>
        </span>
        <div class="text-muted small mt-1"><?= number_format((float)$prestasi['markah'], 1) ?> markah</div>
    </div>
</div>

<form id="mainForm" method="POST" action="" data-autosave="prestasi" data-rekod-id="<?= $id ?>" novalidate>
    <?= csrfField() ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-book me-2 text-primary"></i>Maklumat Penilaian</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Subjek</label>
                    <select name="subjek_id" id="subjekSelect" class="form-select">
                        <option value="">-- Pilih Subjek --</option>
                        <?php foreach ($senaraSubjek as $s): ?>
                        <option value="<?= $s['id'] ?>"
                                data-nama="<?= clean($s['nama']) ?>"
                                <?= (int)$input['subjek_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= clean($s['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Nama Subjek (disimpan) <span class="text-danger">*</span></label>
                    <input type="text" name="nama_subjek" id="namaSubjek" class="form-control"
                           value="<?= clean($input['nama_subjek']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Jenis Penilaian <span class="text-danger">*</span></label>
                    <select name="jenis_penilaian" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($jenisList as $j): ?>
                        <option value="<?= $j ?>" <?= $input['jenis_penilaian'] === $j ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Penggal</label>
                    <select name="penggal" class="form-select">
                        <option value="1" <?= (int)$input['penggal'] === 1 ? 'selected' : '' ?>>Penggal 1</option>
                        <option value="2" <?= (int)$input['penggal'] === 2 ? 'selected' : '' ?>>Penggal 2</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= (int)$input['tahun'] ?>" min="2000" max="2099">
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
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Markah (0–100) <span class="text-danger">*</span></label>
                    <input type="number" name="markah" id="markahInput" class="form-control fw-bold fs-5"
                           value="<?= number_format((float)$input['markah'], 1) ?>"
                           min="0" max="100" step="0.5" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Gred</label>
                    <input type="text" name="gred" id="gredInput" class="form-control fw-bold text-center fs-5"
                           value="<?= clean($input['gred']) ?>" maxlength="3" style="background:#f8f9fa">
                    <div class="form-text text-muted">Auto-kira atau ubah</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Nilai Gred</label>
                    <input type="number" name="nilai_gred" id="nilaiGredInput" class="form-control text-center"
                           value="<?= (int)$input['nilai_gred'] ?>" min="1" max="12">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Band (KSSR)</label>
                    <input type="text" name="band" class="form-control"
                           value="<?= clean($input['band']) ?>" maxlength="2" placeholder="1–6">
                </div>
                <div class="col-md-3">
                    <div id="gredPreview" class="p-3 rounded text-center"
                         style="background:<?= $gredWarna ?>15;min-height:60px">
                        <div class="text-muted small">Pratonton Gred</div>
                        <div id="gredPreviewBadge" class="fw-bold fs-3 mt-1"
                             style="color:<?= $gredWarna ?>"><?= clean($input['gred']) ?></div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"
                              placeholder="Catatan tambahan..."><?= clean($input['catatan']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <?php if ($prestasi['mid']): ?>
        <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $prestasi['mid'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/modules/prestasi/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
    </div>
</form>

<?php
$extraScript = <<<'JS'
<script>
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
    $('#markahInput').on('input', function(){
        const g = kiraMGredJS($(this).val());
        $('#gredInput').val(g.gred !== '—' ? g.gred : '');
        $('#nilaiGredInput').val(g.nilai > 0 ? g.nilai : '');
        $('#gredPreviewBadge').text(g.gred).css('color', g.warna);
        $('#gredPreview').css('background', g.warna + '15');
    });

    $('#subjekSelect').on('change', function(){
        const nama = $(this).find(':selected').data('nama') || '';
        if (nama) $('#namaSubjek').val(nama);
    });

    // Confirm padam
    $('.btn-padam').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({
            title: 'Padam Rekod Markah?',
            html: 'Rekod markah <strong>'+nama+'</strong> akan dipadamkan.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Padam'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
