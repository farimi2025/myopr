<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Edit Maklumat Murid';

// ── Get record ────────────────────────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'ID murid tidak sah.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

$murid = dbFetch("SELECT * FROM murid WHERE id=? LIMIT 1", [$id]);
if (!$murid) {
    setFlash('danger', 'Rekod murid tidak dijumpai.');
    redirect(BASE_URL . '/modules/murid/index.php');
}

$errors = [];
$input  = $murid; // pre-fill from DB

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/murid/edit.php?id=' . $id);
    }

    $input['no_pendaftaran']   = trim($_POST['no_pendaftaran']   ?? '');
    $input['nama']             = trim($_POST['nama']             ?? '');
    $input['no_ic']            = trim($_POST['no_ic']            ?? '');
    $input['jantina']          = trim($_POST['jantina']          ?? '');
    $input['tarikh_lahir']     = trim($_POST['tarikh_lahir']     ?? '');
    $input['kelas_id']         = (int)($_POST['kelas_id']        ?? 0) ?: null;
    $input['tahun']            = (int)($_POST['tahun']           ?? TAHUN_SEMASA);
    $input['bangsa']           = trim($_POST['bangsa']           ?? '');
    $input['agama']            = trim($_POST['agama']            ?? '');
    $input['alamat']           = trim($_POST['alamat']           ?? '');
    $input['poskod']           = trim($_POST['poskod']           ?? '');
    $input['bandar']           = trim($_POST['bandar']           ?? '');
    $input['negeri_murid']     = trim($_POST['negeri_murid']     ?? '');
    $input['telefon_ibu_bapa'] = trim($_POST['telefon_ibu_bapa'] ?? '');
    $input['nama_ibu_bapa']    = trim($_POST['nama_ibu_bapa']    ?? '');
    $input['hubungan']         = trim($_POST['hubungan']         ?? '');
    $input['status']           = trim($_POST['status']           ?? 'aktif');
    $input['catatan']          = trim($_POST['catatan']          ?? '');

    // Validation
    if ($input['no_pendaftaran'] === '') $errors[] = 'No. Pendaftaran wajib diisi.';
    if ($input['nama'] === '')            $errors[] = 'Nama murid wajib diisi.';
    if (!in_array($input['jantina'], ['L','P'])) $errors[] = 'Jantina wajib dipilih.';
    if ($input['tarikh_lahir'] === '')    $errors[] = 'Tarikh lahir wajib diisi.';

    // Check duplicate no_pendaftaran (exclude self)
    if ($input['no_pendaftaran'] !== '') {
        $dup = dbValue("SELECT id FROM murid WHERE no_pendaftaran=? AND id != ? LIMIT 1",
            [$input['no_pendaftaran'], $id]);
        if ($dup) $errors[] = 'No. Pendaftaran <strong>' . clean($input['no_pendaftaran']) . '</strong> sudah digunakan oleh rekod lain.';
    }

    if (empty($errors)) {
        $updateData = [
            'no_pendaftaran'   => $input['no_pendaftaran'],
            'nama'             => $input['nama'],
            'no_ic'            => $input['no_ic'],
            'jantina'          => $input['jantina'],
            'tarikh_lahir'     => $input['tarikh_lahir'],
            'kelas_id'         => $input['kelas_id'],
            'tahun'            => $input['tahun'],
            'bangsa'           => $input['bangsa'],
            'agama'            => $input['agama'],
            'alamat'           => $input['alamat'],
            'poskod'           => $input['poskod'],
            'bandar'           => $input['bandar'],
            'negeri_murid'     => $input['negeri_murid'],
            'telefon_ibu_bapa' => $input['telefon_ibu_bapa'],
            'nama_ibu_bapa'    => $input['nama_ibu_bapa'],
            'hubungan'         => $input['hubungan'],
            'status'           => $input['status'],
            'catatan'          => $input['catatan'],
        ];
        dbUpdate('murid', $updateData, 'id=?', [$id]);
        logAktiviti('kemaskini', 'murid', $id, 'Kemaskini murid: ' . $input['nama']);
        setFlash('success', 'Rekod murid <strong>' . clean($input['nama']) . '</strong> berjaya dikemaskini.');
        redirect(BASE_URL . '/modules/murid/index.php');
    }
}

// ── Dropdown data ─────────────────────────────────────────────────────────────
$senaraKelas = dbFetchAll(
    "SELECT id, tingkatan, nama_kelas, CONCAT(tingkatan,' ',nama_kelas) AS label
     FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [(int)$input['tahun']]
);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Maklumat Murid</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/murid/index.php">Murid</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $id ?>"><?= clean($murid['nama']) ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <!-- Arkib / Aktif toggle -->
        <?php if ($murid['status'] === 'aktif'): ?>
        <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=arkib&id=<?= $id ?>&csrf=<?= csrfToken() ?>"
           class="btn btn-outline-secondary btn-sm btn-arkib" data-nama="<?= clean($murid['nama']) ?>">
            <i class="bi bi-archive me-1"></i>Arkib
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=aktif&id=<?= $id ?>&csrf=<?= csrfToken() ?>"
           class="btn btn-outline-success btn-sm btn-aktif" data-nama="<?= clean($murid['nama']) ?>">
            <i class="bi bi-check-circle me-1"></i>Aktifkan
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/murid/aksi.php?action=padam&id=<?= $id ?>&csrf=<?= csrfToken() ?>"
           class="btn btn-outline-danger btn-sm btn-padam" data-nama="<?= clean($murid['nama']) ?>">
            <i class="bi bi-trash me-1"></i>Padam
        </a>
        <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
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

<!-- Status badge -->
<div class="alert alert-light border d-flex align-items-center gap-2 py-2 mb-4">
    <i class="bi bi-person-circle text-primary fs-5"></i>
    <strong><?= clean($murid['nama']) ?></strong>
    <span class="ms-1"><?= badgeStatus($murid['status']) ?></span>
    <span class="text-muted small ms-auto">ID: <?= $id ?> &bull; No. Pend: <code><?= clean($murid['no_pendaftaran']) ?></code></span>
</div>

<form id="mainForm" method="POST" action="" data-autosave="murid" data-rekod-id="<?= $id ?>" novalidate>
    <?= csrfField() ?>

    <!-- ── MAKLUMAT PERIBADI ─────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2 text-primary"></i>Maklumat Peribadi</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Pendaftaran <span class="text-danger">*</span></label>
                    <input type="text" name="no_pendaftaran" class="form-control"
                           value="<?= clean($input['no_pendaftaran']) ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold small">Nama Penuh <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" style="text-transform:uppercase"
                           value="<?= clean($input['nama']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Kad Pengenalan</label>
                    <input type="text" name="no_ic" class="form-control input-ic"
                           value="<?= clean($input['no_ic']) ?>" placeholder="XXXXXX-XX-XXXX" maxlength="14">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Jantina <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jantina" id="jantinaL" value="L"
                                <?= $input['jantina'] === 'L' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="jantinaL">
                                <i class="bi bi-gender-male text-primary me-1"></i>Lelaki
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jantina" id="jantinaP" value="P"
                                <?= $input['jantina'] === 'P' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="jantinaP">
                                <i class="bi bi-gender-female me-1" style="color:#e91e8c"></i>Perempuan
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Tarikh Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="tarikh_lahir" class="form-control"
                           value="<?= clean($input['tarikh_lahir']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Bangsa</label>
                    <select name="bangsa" class="form-select">
                        <option value="">-- Pilih Bangsa --</option>
                        <?php foreach (['Melayu','Cina','India','Iban','Kadazan','Bajau','Murut','Bidayuh','Melanau','Orang Asli','Lain-lain'] as $b): ?>
                        <option value="<?= $b ?>" <?= $input['bangsa'] === $b ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Agama</label>
                    <input type="text" name="agama" class="form-control"
                           value="<?= clean($input['agama']) ?>" placeholder="Islam, Kristian, Buddha...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Kelas</label>
                    <select name="kelas_id" class="form-select select2kelas">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($senaraKelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= (int)$input['kelas_id'] === (int)$k['id'] ? 'selected' : '' ?>>
                            <?= clean($k['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= (int)$input['tahun'] ?>" min="2000" max="2099">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif"     <?= $input['status'] === 'aktif'     ? 'selected' : '' ?>>Aktif</option>
                        <option value="arkib"     <?= $input['status'] === 'arkib'     ? 'selected' : '' ?>>Arkib</option>
                        <option value="berpindah" <?= $input['status'] === 'berpindah' ? 'selected' : '' ?>>Berpindah</option>
                        <option value="tamat"     <?= $input['status'] === 'tamat'     ? 'selected' : '' ?>>Tamat</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MAKLUMAT ALAMAT ───────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-house-door me-2 text-primary"></i>Maklumat Alamat</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small">Alamat Rumah</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= clean($input['alamat']) ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Poskod</label>
                    <input type="text" name="poskod" class="form-control"
                           value="<?= clean($input['poskod']) ?>" maxlength="5">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Bandar</label>
                    <input type="text" name="bandar" class="form-control" value="<?= clean($input['bandar']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Negeri</label>
                    <input type="text" name="negeri_murid" class="form-control" value="<?= clean($input['negeri_murid']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── IBU BAPA / PENJAGA ────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Maklumat Ibu Bapa / Penjaga</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Nama Ibu Bapa / Penjaga</label>
                    <input type="text" name="nama_ibu_bapa" class="form-control"
                           value="<?= clean($input['nama_ibu_bapa']) ?>" style="text-transform:uppercase">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Hubungan</label>
                    <select name="hubungan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Bapa','Ibu','Datuk','Nenek','Abang','Kakak','Pakcik','Makcik','Penjaga'] as $h): ?>
                        <option value="<?= $h ?>" <?= $input['hubungan'] === $h ? 'selected' : '' ?>><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Telefon</label>
                    <input type="tel" name="telefon_ibu_bapa" class="form-control input-telefon"
                           value="<?= clean($input['telefon_ibu_bapa']) ?>" placeholder="01X-XXXXXXXX">
                </div>
            </div>
        </div>
    </div>

    <!-- ── CATATAN ────────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-journal-text me-2 text-primary"></i>Catatan</h6>
        </div>
        <div class="card-body">
            <textarea name="catatan" class="form-control" rows="3"
                      placeholder="Catatan tambahan..."><?= clean($input['catatan']) ?></textarea>
        </div>
    </div>

    <!-- ── Buttons ─────────────────────────────────────────────────────── -->
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= BASE_URL ?>/modules/murid/lihat.php?id=<?= $id ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
    </div>
</form>

<?php
$extraScript = <<<JS
<script>
$(function(){
    $('.select2kelas').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Kelas --',
        allowClear: true,
        width: '100%'
    });

    $(document).on('input', '.input-ic', function(){
        let val = $(this).val().replace(/\D/g,'');
        if (val.length > 12) val = val.substr(0,12);
        let formatted = val;
        if (val.length > 6)  formatted = val.substr(0,6) + '-' + val.substr(6);
        if (val.length > 8)  formatted = val.substr(0,6) + '-' + val.substr(6,2) + '-' + val.substr(8);
        $(this).val(formatted);
    });

    $('[name="nama"], [name="nama_ibu_bapa"]').on('blur', function(){
        $(this).val($(this).val().toUpperCase());
    });

    // Confirm arkib
    $('.btn-arkib').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({ title:'Arkib Murid?', html:'Murid <strong>'+nama+'</strong> akan ditetapkan sebagai arkib.',
            icon:'warning', showCancelButton:true, confirmButtonColor:'#6c757d',
            cancelButtonText:'Batal', confirmButtonText:'Ya, Arkib'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });

    // Confirm aktif
    $('.btn-aktif').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({ title:'Aktifkan Murid?', html:'Murid <strong>'+nama+'</strong> akan diaktifkan semula.',
            icon:'question', showCancelButton:true, confirmButtonColor:'#198754',
            cancelButtonText:'Batal', confirmButtonText:'Ya, Aktifkan'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });

    // Confirm padam
    $('.btn-padam').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href'), nama = $(this).data('nama');
        Swal.fire({ title:'Padam Murid?',
            html:'Rekod murid <strong>'+nama+'</strong> akan <span class="text-danger fw-bold">dipadamkan kekal</span>. Tindakan ini tidak boleh dibatalkan.',
            icon:'error', showCancelButton:true, confirmButtonColor:'#dc3545',
            cancelButtonText:'Batal', confirmButtonText:'Ya, Padam'
        }).then(r=>{ if(r.isConfirmed) window.location=url; });
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
