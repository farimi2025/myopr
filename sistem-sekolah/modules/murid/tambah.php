<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Tambah Murid Baharu';

// ── POST handler ─────────────────────────────────────────────────────────────
$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/murid/tambah.php');
    }

    // Collect & sanitise
    $input['no_pendaftaran']  = trim($_POST['no_pendaftaran']  ?? '');
    $input['nama']            = trim($_POST['nama']            ?? '');
    $input['no_ic']           = trim($_POST['no_ic']           ?? '');
    $input['jantina']         = trim($_POST['jantina']         ?? '');
    $input['tarikh_lahir']    = trim($_POST['tarikh_lahir']    ?? '');
    $input['kelas_id']        = (int)($_POST['kelas_id']       ?? 0);
    $input['tahun']           = (int)($_POST['tahun']          ?? TAHUN_SEMASA);
    $input['bangsa']          = trim($_POST['bangsa']          ?? '');
    $input['agama']           = trim($_POST['agama']           ?? '');
    $input['alamat']          = trim($_POST['alamat']          ?? '');
    $input['poskod']          = trim($_POST['poskod']          ?? '');
    $input['bandar']          = trim($_POST['bandar']          ?? '');
    $input['negeri_murid']    = trim($_POST['negeri_murid']    ?? '');
    $input['telefon_ibu_bapa']= trim($_POST['telefon_ibu_bapa']?? '');
    $input['nama_ibu_bapa']   = trim($_POST['nama_ibu_bapa']   ?? '');
    $input['hubungan']        = trim($_POST['hubungan']        ?? '');
    $input['catatan']         = trim($_POST['catatan']         ?? '');
    $input['status']          = 'aktif';

    // Validation
    if ($input['no_pendaftaran'] === '') $errors[] = 'No. Pendaftaran wajib diisi.';
    if ($input['nama'] === '')            $errors[] = 'Nama murid wajib diisi.';
    if (!in_array($input['jantina'], ['L','P'])) $errors[] = 'Jantina wajib dipilih.';
    if ($input['tarikh_lahir'] === '')    $errors[] = 'Tarikh lahir wajib diisi.';

    // Check duplicate no_pendaftaran
    if ($input['no_pendaftaran'] !== '') {
        $dup = dbValue("SELECT id FROM murid WHERE no_pendaftaran=? LIMIT 1", [$input['no_pendaftaran']]);
        if ($dup) $errors[] = 'No. Pendaftaran <strong>' . clean($input['no_pendaftaran']) . '</strong> sudah wujud.';
    }

    if (empty($errors)) {
        $newId = dbInsert('murid', $input);
        logAktiviti('tambah', 'murid', (int)$newId, 'Tambah murid: ' . $input['nama']);
        setFlash('success', 'Rekod murid <strong>' . clean($input['nama']) . '</strong> berjaya ditambah.');
        redirect(BASE_URL . '/modules/murid/index.php');
    }
}

// ── Dropdown data ─────────────────────────────────────────────────────────────
$senaraKelas = dbFetchAll(
    "SELECT id, tingkatan, nama_kelas, CONCAT(tingkatan,' ',nama_kelas) AS label
     FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [TAHUN_SEMASA]
);

// Auto-generate no_pendaftaran hint
$tahunPend  = date('Y');
$bilanganSk = (int)dbValue("SELECT COUNT(*) FROM murid WHERE tahun=?", [$tahunPend]);
$cadanganNo = 'MRD/' . $tahunPend . '/' . str_pad($bilanganSk + 1, 4, '0', STR_PAD_LEFT);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Murid Baharu</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/murid/index.php">Murid</a></li>
                <li class="breadcrumb-item active">Tambah</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary btn-sm">
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
            <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form id="mainForm" method="POST" action="" data-autosave="murid" data-rekod-id="0" novalidate>
    <?= csrfField() ?>

    <!-- ── MAKLUMAT PERIBADI ────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2 text-primary"></i>Maklumat Peribadi</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- No. Pendaftaran -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Pendaftaran <span class="text-danger">*</span></label>
                    <input type="text" name="no_pendaftaran" class="form-control <?= in_array_str('No. Pendaftaran', $errors) ? 'is-invalid' : '' ?>"
                           value="<?= clean($input['no_pendaftaran'] ?? '') ?>"
                           placeholder="<?= clean($cadanganNo) ?>" required>
                    <div class="form-text text-muted">Cadangan: <code><?= clean($cadanganNo) ?></code></div>
                </div>

                <!-- Nama -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold small">Nama Penuh <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" style="text-transform:uppercase"
                           value="<?= clean($input['nama'] ?? '') ?>" placeholder="Nama penuh seperti dalam IC" required>
                </div>

                <!-- No IC -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Kad Pengenalan</label>
                    <input type="text" name="no_ic" class="form-control input-ic"
                           value="<?= clean($input['no_ic'] ?? '') ?>"
                           placeholder="XXXXXX-XX-XXXX" maxlength="14">
                    <div class="form-text text-muted">Format: 810101-14-5432</div>
                </div>

                <!-- Jantina -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Jantina <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jantina" id="jantinaL" value="L"
                                <?= (($input['jantina'] ?? '') === 'L') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="jantinaL">
                                <i class="bi bi-gender-male text-primary me-1"></i>Lelaki
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jantina" id="jantinaP" value="P"
                                <?= (($input['jantina'] ?? '') === 'P') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="jantinaP">
                                <i class="bi bi-gender-female me-1" style="color:#e91e8c"></i>Perempuan
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tarikh Lahir -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Tarikh Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="tarikh_lahir" class="form-control"
                           value="<?= clean($input['tarikh_lahir'] ?? '') ?>" required>
                </div>

                <!-- Bangsa -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Bangsa</label>
                    <select name="bangsa" class="form-select">
                        <option value="">-- Pilih Bangsa --</option>
                        <?php
                        $senaraiBangsa = ['Melayu','Cina','India','Iban','Kadazan','Bajau','Murut','Bidayuh','Melanau','Orang Asli','Lain-lain'];
                        foreach ($senaraiBangsa as $b):
                        ?>
                        <option value="<?= $b ?>" <?= (($input['bangsa'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Agama -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Agama</label>
                    <input type="text" name="agama" class="form-control"
                           value="<?= clean($input['agama'] ?? '') ?>"
                           placeholder="cth: Islam, Kristian, Buddha...">
                </div>

                <!-- Kelas -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Kelas</label>
                    <select name="kelas_id" id="kelasSelect" class="form-select select2kelas">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($senaraKelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= (int)($input['kelas_id'] ?? 0) === (int)$k['id'] ? 'selected' : '' ?>>
                            <?= clean($k['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tahun -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= (int)($input['tahun'] ?? TAHUN_SEMASA) ?>"
                           min="2000" max="2099">
                </div>
            </div>
        </div>
    </div>

    <!-- ── MAKLUMAT ALAMAT ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-house-door me-2 text-primary"></i>Maklumat Alamat</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small">Alamat Rumah</label>
                    <textarea name="alamat" class="form-control" rows="2"
                              placeholder="No. rumah, Jalan, Taman..."><?= clean($input['alamat'] ?? '') ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Poskod</label>
                    <input type="text" name="poskod" class="form-control"
                           value="<?= clean($input['poskod'] ?? '') ?>" placeholder="50000" maxlength="5">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Bandar</label>
                    <input type="text" name="bandar" class="form-control"
                           value="<?= clean($input['bandar'] ?? '') ?>" placeholder="Kuala Lumpur">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Negeri</label>
                    <input type="text" name="negeri_murid" class="form-control"
                           value="<?= clean($input['negeri_murid'] ?? '') ?>" placeholder="Selangor">
                </div>
            </div>
        </div>
    </div>

    <!-- ── MAKLUMAT IBU BAPA / PENJAGA ─────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Maklumat Ibu Bapa / Penjaga</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Nama Ibu Bapa / Penjaga</label>
                    <input type="text" name="nama_ibu_bapa" class="form-control"
                           value="<?= clean($input['nama_ibu_bapa'] ?? '') ?>" style="text-transform:uppercase">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Hubungan</label>
                    <select name="hubungan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Bapa','Ibu','Datuk','Nenek','Abang','Kakak','Pakcik','Makcik','Penjaga'] as $h): ?>
                        <option value="<?= $h ?>" <?= (($input['hubungan'] ?? '') === $h) ? 'selected' : '' ?>><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Telefon</label>
                    <input type="tel" name="telefon_ibu_bapa" class="form-control input-telefon"
                           value="<?= clean($input['telefon_ibu_bapa'] ?? '') ?>"
                           placeholder="01X-XXXXXXXX">
                </div>
            </div>
        </div>
    </div>

    <!-- ── CATATAN ─────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-journal-text me-2 text-primary"></i>Catatan</h6>
        </div>
        <div class="card-body">
            <textarea name="catatan" class="form-control" rows="3"
                      placeholder="Catatan tambahan mengenai murid (jika ada)..."><?= clean($input['catatan'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- ── Buttons ─────────────────────────────────────────────────────── -->
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
        <button type="reset" class="btn btn-outline-warning">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Set Semula
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Rekod
        </button>
    </div>
</form>

<?php
$extraScript = <<<'JS'
<script>
$(function(){
    // Select2 for kelas
    $('.select2kelas').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Kelas --',
        allowClear: true,
        width: '100%'
    });

    // Auto-format IC: XXXXXX-XX-XXXX
    $(document).on('input', '.input-ic', function(){
        let val = $(this).val().replace(/\D/g,'');
        if (val.length > 12) val = val.substr(0,12);
        let formatted = val;
        if (val.length > 6)  formatted = val.substr(0,6) + '-' + val.substr(6);
        if (val.length > 8)  formatted = val.substr(0,6) + '-' + val.substr(6,2) + '-' + val.substr(8);
        $(this).val(formatted);
    });

    // Uppercase nama
    $('[name="nama"], [name="nama_ibu_bapa"]').on('blur', function(){
        $(this).val($(this).val().toUpperCase());
    });
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
