<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'ID guru tidak sah.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

$guru = dbFetch("SELECT * FROM guru WHERE id=?", [$id]);
if (!$guru) {
    setFlash('danger', 'Rekod guru tidak dijumpai.');
    redirect(BASE_URL . '/modules/guru/index.php');
}

$pageTitle = 'Edit Guru - ' . $guru['nama'];
$errors    = [];
$old       = $guru;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah. Sila cuba lagi.');
        redirect(BASE_URL . '/modules/guru/edit.php?id=' . $id);
    }

    $old = $_POST;

    $nama        = clean($_POST['nama'] ?? '');
    if (empty($nama)) $errors['nama'] = 'Nama guru diperlukan.';

    $noIc        = clean($_POST['no_ic'] ?? '');
    $noPerkerja  = clean($_POST['no_pekerja'] ?? '');
    $jawatan     = clean($_POST['jawatan'] ?? '');
    $gred        = clean($_POST['gred'] ?? '');
    $opsyen      = clean($_POST['opsyen'] ?? '');
    $subjekUtama = clean($_POST['subjek_utama'] ?? '');
    $telefon     = clean($_POST['telefon'] ?? '');
    $email       = clean($_POST['email'] ?? '');
    $tarikhLahir = clean($_POST['tarikh_lahir'] ?? '');
    $jantina     = in_array($_POST['jantina'] ?? '', ['L','P']) ? $_POST['jantina'] : 'L';
    $bangsa      = clean($_POST['bangsa'] ?? '');
    $agama       = clean($_POST['agama'] ?? '');
    $alamat      = clean($_POST['alamat'] ?? '');
    $catatan     = clean($_POST['catatan'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['aktif','arkib','berhenti','bersara']) ? $_POST['status'] : 'aktif';

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format emel tidak sah.';
    }

    if ($noPerkerja) {
        $cek = dbValue("SELECT id FROM guru WHERE no_pekerja=? AND id!=?", [$noPerkerja, $id]);
        if ($cek) $errors['no_pekerja'] = 'No. pekerja telah digunakan oleh guru lain.';
    }

    $fotoLama = $guru['foto'];
    $foto     = $fotoLama;

    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadGambar($_FILES['foto'], 'foto');
        if (!$upload['berjaya']) {
            $errors['foto'] = $upload['mesej'];
        } else {
            $foto = $upload['fail'];
            if ($fotoLama) padamFail($fotoLama);
        }
    }

    if (isset($_POST['padam_foto']) && $_POST['padam_foto'] === '1') {
        if ($fotoLama) padamFail($fotoLama);
        $foto = '';
    }

    if (empty($errors)) {
        dbUpdate('guru', [
            'nama'         => $nama,
            'no_ic'        => $noIc,
            'no_pekerja'   => $noPerkerja,
            'jawatan'      => $jawatan,
            'gred'         => $gred,
            'opsyen'       => $opsyen,
            'subjek_utama' => $subjekUtama,
            'telefon'      => $telefon,
            'email'        => $email,
            'tarikh_lahir' => $tarikhLahir ?: null,
            'jantina'      => $jantina,
            'bangsa'       => $bangsa,
            'agama'        => $agama,
            'alamat'       => $alamat,
            'foto'         => $foto,
            'status'       => $status,
            'catatan'      => $catatan,
        ], 'id=?', [$id]);

        logAktiviti('kemaskini', 'guru', $id, "Kemaskini guru: $nama");
        setFlash('success', "Maklumat guru <strong>$nama</strong> berjaya dikemaskini.");
        redirect(BASE_URL . '/modules/guru/index.php');
    }

    $old['foto'] = $foto;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Guru</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Guru</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="lihat.php?id=<?= $id ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-eye me-1"></i>Lihat Profil
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?= showFlash() ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Sila betulkan ralat berikut:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
    <?= csrfField() ?>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Maklumat Peribadi -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Maklumat Peribadi</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Penuh <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($old['nama'] ?? '') ?>" placeholder="Nama penuh guru" required>
                            <?php if (isset($errors['nama'])): ?><div class="invalid-feedback"><?= $errors['nama'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Kad Pengenalan</label>
                            <input type="text" name="no_ic" class="form-control"
                                   value="<?= clean($old['no_ic'] ?? '') ?>" placeholder="XXXXXX-XX-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Pekerja</label>
                            <input type="text" name="no_pekerja" class="form-control <?= isset($errors['no_pekerja']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($old['no_pekerja'] ?? '') ?>" placeholder="Contoh: A012345">
                            <?php if (isset($errors['no_pekerja'])): ?><div class="invalid-feedback"><?= $errors['no_pekerja'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tarikh Lahir</label>
                            <input type="date" name="tarikh_lahir" class="form-control"
                                   value="<?= clean($old['tarikh_lahir'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jantina</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jantina" id="jL" value="L"
                                           <?= ($old['jantina'] ?? 'L') === 'L' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="jL">Lelaki</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jantina" id="jP" value="P"
                                           <?= ($old['jantina'] ?? '') === 'P' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="jP">Perempuan</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bangsa</label>
                            <select name="bangsa" class="form-select">
                                <option value="">-- Pilih Bangsa --</option>
                                <?php foreach (['Melayu','Cina','India','Iban','Kadazan','Bajau','Melanau','Murut','Lain-lain'] as $b): ?>
                                <option value="<?= $b ?>" <?= ($old['bangsa'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Agama</label>
                            <select name="agama" class="form-select">
                                <option value="">-- Pilih Agama --</option>
                                <?php foreach (['Islam','Kristian','Buddha','Hindu','Sikh','Lain-lain'] as $a): ?>
                                <option value="<?= $a ?>" <?= ($old['agama'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['aktif'=>'Aktif','arkib'=>'Arkib','berhenti'=>'Berhenti','bersara'=>'Bersara'] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($old['status'] ?? 'aktif') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"
                                      placeholder="Alamat tempat tinggal"><?= clean($old['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maklumat Perkhidmatan -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-briefcase-fill me-2"></i>Maklumat Perkhidmatan</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jawatan</label>
                            <input type="text" name="jawatan" class="form-control"
                                   value="<?= clean($old['jawatan'] ?? '') ?>" placeholder="Contoh: Guru Cemerlang">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gred</label>
                            <select name="gred" class="form-select">
                                <option value="">-- Pilih Gred --</option>
                                <?php foreach (['DGA29','DGA32','DGA34','DG41','DG44','DG48','DG52','DG54','DG56','DG58','JUSA C','JUSA B','JUSA A','VU07','VS07','VS26','VS38','VT07','VN07','Lain-lain'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($old['gred'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Opsyen (Pengkhususan)</label>
                            <input type="text" name="opsyen" class="form-control"
                                   value="<?= clean($old['opsyen'] ?? '') ?>" placeholder="Contoh: Matematik">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subjek Utama</label>
                            <input type="text" name="subjek_utama" class="form-control"
                                   value="<?= clean($old['subjek_utama'] ?? '') ?>" placeholder="Contoh: Matematik">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maklumat Hubungan -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-telephone-fill me-2"></i>Maklumat Hubungan</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Telefon</label>
                            <input type="text" name="telefon" class="form-control input-telefon"
                                   value="<?= clean($old['telefon'] ?? '') ?>" placeholder="01X-XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Emel</label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($old['email'] ?? '') ?>" placeholder="guru@email.com">
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= $errors['email'] ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan</h6>
                </div>
                <div class="card-body">
                    <textarea name="catatan" class="form-control" rows="3"
                              placeholder="Catatan tambahan (jika ada)"><?= clean($old['catatan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column: Photo -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0"><i class="bi bi-camera me-2"></i>Gambar Profil</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3 position-relative d-inline-block">
                        <img id="previewFoto" src="<?= gambarUrl($guru['foto']) ?>"
                             class="img-thumbnail rounded-circle"
                             style="width:150px;height:150px;object-fit:cover;" alt="Foto Guru">
                        <?php if ($guru['foto']): ?>
                        <label class="position-absolute bottom-0 end-0">
                            <input type="checkbox" name="padam_foto" value="1" id="padamFoto" class="d-none">
                            <span class="btn btn-danger btn-sm rounded-circle" title="Padam foto" id="btnPadamFoto">
                                <i class="bi bi-x-circle-fill"></i>
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="mb-1 small text-muted fw-semibold">Tukar Gambar (Pilihan)</div>
                    <input type="file" name="foto" id="foto" class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($errors['foto'])): ?><div class="invalid-feedback"><?= $errors['foto'] ?></div><?php endif; ?>
                    <div class="form-text">JPG, PNG atau WEBP. Maks 5MB.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning px-4">
            <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
        </button>
        <a href="index.php" class="btn btn-outline-secondary px-4">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
    </div>
</form>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('#foto').on('change', function(){
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => $('#previewFoto').attr('src', e.target.result);
            reader.readAsDataURL(file);
        }
    });
    $('.input-telefon').on('input', function(){
        this.value = this.value.replace(/\D/g,'');
    });
    $('#btnPadamFoto').on('click', function(){
        const cb = $('#padamFoto');
        if (cb.is(':checked')) {
            cb.prop('checked', false);
            $(this).removeClass('btn-success').addClass('btn-danger');
            $(this).html('<i class="bi bi-x-circle-fill"></i>');
        } else {
            cb.prop('checked', true);
            $(this).removeClass('btn-danger').addClass('btn-success');
            $(this).html('<i class="bi bi-check-circle-fill"></i>');
            $('#previewFoto').attr('src', '<?= BASE_URL ?>/assets/images/no-photo.png');
        }
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
