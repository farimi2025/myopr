<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['super_admin', 'pentadbir']);

$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'ID pengguna tidak sah.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

// Tidak boleh edit akaun sendiri dari halaman ini
if ($id === (int)$currentUser['id']) {
    setFlash('info', 'Untuk mengedit maklumat akaun sendiri, sila gunakan halaman profil.');
    redirect(BASE_URL . '/modules/profil/index.php');
}

$pengguna = dbFetch("SELECT * FROM users WHERE id=?", [$id]);
if (!$pengguna) {
    setFlash('danger', 'Rekod pengguna tidak dijumpai.');
    redirect(BASE_URL . '/modules/pengguna/index.php');
}

$pageTitle = 'Edit Pengguna - ' . $pengguna['nama'];
$errors    = [];
$old       = $pengguna;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah. Sila cuba lagi.');
        redirect(BASE_URL . '/modules/pengguna/edit.php?id=' . $id);
    }

    $old = $_POST;

    $nama     = clean($_POST['nama'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email    = clean($_POST['email'] ?? '');
    $peranan  = clean($_POST['peranan'] ?? '');
    $status   = clean($_POST['status'] ?? 'aktif');
    $password = $_POST['password'] ?? '';
    $passConf = $_POST['password_confirm'] ?? '';

    if (empty($nama)) {
        $errors['nama'] = 'Nama penuh diperlukan.';
    }

    if (empty($username)) {
        $errors['username'] = 'Username diperlukan.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh mengandungi huruf kecil, nombor dan underscore.';
    } else {
        $cekUsername = dbValue("SELECT id FROM users WHERE username=? AND id!=?", [$username, $id]);
        if ($cekUsername) $errors['username'] = 'Username telah digunakan oleh pengguna lain.';
    }

    if (empty($email)) {
        $errors['email'] = 'E-mel diperlukan.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format e-mel tidak sah.';
    } else {
        $cekEmail = dbValue("SELECT id FROM users WHERE email=? AND id!=?", [$email, $id]);
        if ($cekEmail) $errors['email'] = 'E-mel telah digunakan oleh pengguna lain.';
    }

    if (!in_array($peranan, ['super_admin','pentadbir','guru','staf'])) {
        $errors['peranan'] = 'Peranan tidak sah.';
    }

    if (!in_array($status, ['aktif','arkib','tangguh'])) {
        $status = 'aktif';
    }

    // Kata laluan: hanya validate jika diisi
    $updatePassword = false;
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = 'Kata laluan mestilah sekurang-kurangnya 8 aksara.';
        } elseif ($password !== $passConf) {
            $errors['password_confirm'] = 'Pengesahan kata laluan tidak sepadan.';
        } else {
            $updatePassword = true;
        }
    }

    // Handle foto
    $fotoLama = $pengguna['foto'];
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
        $data = [
            'nama'     => $nama,
            'username' => $username,
            'email'    => $email,
            'peranan'  => $peranan,
            'status'   => $status,
            'foto'     => $foto,
        ];

        if ($updatePassword) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        dbUpdate('users', $data, 'id=?', [$id]);
        logAktiviti('kemaskini', 'users', $id, "Kemaskini pengguna: $username");
        setFlash('success', "Maklumat pengguna <strong>$nama</strong> berjaya dikemaskini.");
        redirect(BASE_URL . '/modules/pengguna/index.php');
    }

    $old['foto'] = $foto;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Pengguna</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Pengguna Sistem</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
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

            <!-- Maklumat Akaun -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Maklumat Akaun</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Penuh <span class="text-danger">*</span></label>
                            <input type="text" name="nama"
                                   class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($old['nama'] ?? '') ?>"
                                   placeholder="Nama penuh pengguna" required>
                            <?php if (isset($errors['nama'])): ?>
                            <div class="invalid-feedback"><?= $errors['nama'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="text" name="username" id="username"
                                       class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                       value="<?= clean($old['username'] ?? '') ?>"
                                       placeholder="cth: ahmad.ali" required autocomplete="off">
                                <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div id="usernameStatus" class="form-text"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mel <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email"
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       value="<?= clean($old['email'] ?? '') ?>"
                                       placeholder="pengguna@email.com" required autocomplete="off">
                                <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div id="emailStatus" class="form-text"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Peranan <span class="text-danger">*</span></label>
                            <select name="peranan" class="form-select <?= isset($errors['peranan']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Pilih Peranan --</option>
                                <?php foreach (['super_admin'=>'Super Admin','pentadbir'=>'Pentadbir','guru'=>'Guru','staf'=>'Staf'] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($old['peranan'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['peranan'])): ?>
                            <div class="invalid-feedback"><?= $errors['peranan'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['aktif'=>'Aktif','arkib'=>'Arkib','tangguh'=>'Tangguh'] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($old['status'] ?? 'aktif') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tukar Kata Laluan (optional) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Tukar Kata Laluan</h6>
                    <span class="badge bg-secondary">Pilihan — kosongkan jika tidak mahu tukar</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kata Laluan Baru</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password"
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Kosongkan jika tidak tukar" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pw" data-target="password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Sekurang-kurangnya 8 aksara jika diisi.</div>
                        </div>

                        <div class="col-md-6" id="wrapPassConfirm">
                            <label class="form-label fw-semibold">Sahkan Kata Laluan Baru</label>
                            <div class="input-group">
                                <input type="password" name="password_confirm" id="password_confirm"
                                       class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                       placeholder="Ulang kata laluan baharu" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pw" data-target="password_confirm">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback"><?= $errors['password_confirm'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div id="passMatchStatus" class="form-text"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Foto -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-camera me-2"></i>Gambar Profil</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3 position-relative d-inline-block">
                        <img id="previewFoto" src="<?= gambarUrl($pengguna['foto']) ?>"
                             class="img-thumbnail rounded-circle"
                             style="width:150px;height:150px;object-fit:cover;" alt="Foto Pengguna">
                        <?php if ($pengguna['foto']): ?>
                        <label class="position-absolute bottom-0 end-0">
                            <input type="checkbox" name="padam_foto" value="1" id="padamFoto" class="d-none">
                            <span class="btn btn-danger btn-sm rounded-circle" title="Padam foto" id="btnPadamFoto">
                                <i class="bi bi-x-circle-fill"></i>
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="mb-1 small text-muted fw-semibold">Tukar Gambar (Pilihan)</div>
                    <input type="file" name="foto" id="foto"
                           class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($errors['foto'])): ?>
                    <div class="invalid-feedback"><?= $errors['foto'] ?></div>
                    <?php endif; ?>
                    <div class="form-text">JPG, PNG atau WEBP. Maks 5MB.</div>
                </div>
            </div>

            <!-- Info rekod -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-secondary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Maklumat Rekod</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-1"><span class="text-muted">ID Pengguna:</span> <strong>#<?= $pengguna['id'] ?></strong></div>
                    <div class="mb-1"><span class="text-muted">Log Masuk Akhir:</span><br>
                        <?= $pengguna['last_login'] ? date('d/m/Y H:i', strtotime($pengguna['last_login'])) : '<em>Belum pernah log masuk</em>' ?>
                    </div>
                    <div><span class="text-muted">Dicipta:</span><br>
                        <?= $pengguna['created_at'] ? date('d/m/Y H:i', strtotime($pengguna['created_at'])) : '-' ?>
                    </div>
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

<?php
$idPengguna  = $id;
$fotoUrlAsal = gambarUrl($pengguna['foto']);
$extraScript = <<<ENDSCRIPT
<script>
$(function(){
    // Photo preview
    $('#foto').on('change', function(){
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => $('#previewFoto').attr('src', e.target.result);
            reader.readAsDataURL(file);
        }
    });

    // Padam foto toggle
    $('#btnPadamFoto').on('click', function(){
        const cb = $('#padamFoto');
        if (cb.is(':checked')) {
            cb.prop('checked', false);
            $(this).removeClass('btn-success').addClass('btn-danger');
            $(this).html('<i class="bi bi-x-circle-fill"></i>');
            $('#previewFoto').attr('src', '$fotoUrlAsal');
        } else {
            cb.prop('checked', true);
            $(this).removeClass('btn-danger').addClass('btn-success');
            $(this).html('<i class="bi bi-check-circle-fill"></i> Akan Dipadam');
            $('#previewFoto').attr('src', '<?= BASE_URL ?>/assets/images/no-photo.png');
        }
    });

    // Toggle show/hide password
    $('.btn-toggle-pw').on('click', function(){
        const target = $(this).data('target');
        const input  = $('#' + target);
        const icon   = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Auto lowercase + no space for username
    $('#username').on('input', function(){
        this.value = this.value.toLowerCase().replace(/\s/g, '');
    });

    // Username uniqueness check (exclude self)
    $('#username').on('blur', function(){
        const val = $(this).val().trim();
        if (!val) return;
        $.get('<?= BASE_URL ?>/modules/pengguna/cek_unik.php', { jenis: 'username', nilai: val, kecuali: <?= $idPengguna ?> }, function(data){
            if (data.guna) {
                $('#usernameStatus').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Username telah digunakan.</span>');
                $('#username').addClass('is-invalid').removeClass('is-valid');
            } else {
                $('#usernameStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Username tersedia.</span>');
                $('#username').addClass('is-valid').removeClass('is-invalid');
            }
        }, 'json');
    });

    // Email uniqueness check (exclude self)
    $('#email').on('blur', function(){
        const val = $(this).val().trim();
        if (!val) return;
        $.get('<?= BASE_URL ?>/modules/pengguna/cek_unik.php', { jenis: 'email', nilai: val, kecuali: <?= $idPengguna ?> }, function(data){
            if (data.guna) {
                $('#emailStatus').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>E-mel telah digunakan.</span>');
                $('#email').addClass('is-invalid').removeClass('is-valid');
            } else {
                $('#emailStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>E-mel tersedia.</span>');
                $('#email').addClass('is-valid').removeClass('is-invalid');
            }
        }, 'json');
    });

    // Password confirm match (only when new password is entered)
    $('#password_confirm').on('input', function(){
        const pw  = $('#password').val();
        const pwc = $(this).val();
        if (!pw || !pwc) { $('#passMatchStatus').text(''); return; }
        if (pw === pwc) {
            $('#passMatchStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Kata laluan sepadan.</span>');
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $('#passMatchStatus').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Kata laluan tidak sepadan.</span>');
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
});
</script>
ENDSCRIPT;
include __DIR__ . '/../../includes/footer.php';
?>
