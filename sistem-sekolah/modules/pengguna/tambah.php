<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['super_admin', 'pentadbir']);

$pageTitle = 'Tambah Pengguna';
$errors    = [];
$old       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah. Sila cuba lagi.');
        redirect(BASE_URL . '/modules/pengguna/tambah.php');
    }

    $old = $_POST;

    // Validation
    $nama     = clean($_POST['nama'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email    = clean($_POST['email'] ?? '');
    $peranan  = clean($_POST['peranan'] ?? '');
    $password = $_POST['password'] ?? '';
    $passConf = $_POST['password_confirm'] ?? '';
    $status   = clean($_POST['status'] ?? 'aktif');

    if (empty($nama)) {
        $errors['nama'] = 'Nama penuh diperlukan.';
    }

    if (empty($username)) {
        $errors['username'] = 'Username diperlukan.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh mengandungi huruf kecil, nombor dan underscore.';
    } else {
        $cekUsername = dbValue("SELECT id FROM users WHERE username=?", [$username]);
        if ($cekUsername) $errors['username'] = 'Username telah digunakan. Sila pilih yang lain.';
    }

    if (empty($email)) {
        $errors['email'] = 'E-mel diperlukan.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format e-mel tidak sah.';
    } else {
        $cekEmail = dbValue("SELECT id FROM users WHERE email=?", [$email]);
        if ($cekEmail) $errors['email'] = 'E-mel telah digunakan oleh pengguna lain.';
    }

    if (!in_array($peranan, ['super_admin','pentadbir','guru','staf'])) {
        $errors['peranan'] = 'Peranan tidak sah.';
    }

    if (empty($password)) {
        $errors['password'] = 'Kata laluan diperlukan.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Kata laluan mestilah sekurang-kurangnya 8 aksara.';
    } elseif ($password !== $passConf) {
        $errors['password_confirm'] = 'Pengesahan kata laluan tidak sepadan.';
    }

    if (!in_array($status, ['aktif','arkib','tangguh'])) {
        $status = 'aktif';
    }

    // Upload foto
    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadGambar($_FILES['foto'], 'foto');
        if (!$upload['berjaya']) {
            $errors['foto'] = $upload['mesej'];
        } else {
            $foto = $upload['fail'];
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $id = dbInsert('users', [
            'nama'     => $nama,
            'username' => $username,
            'email'    => $email,
            'password' => $hashedPassword,
            'peranan'  => $peranan,
            'status'   => $status,
            'foto'     => $foto,
        ]);

        logAktiviti('tambah', 'users', (int)$id, "Tambah pengguna: $username");
        setFlash('success', "Pengguna <strong>$nama</strong> (@$username) berjaya ditambah.");
        redirect(BASE_URL . '/modules/pengguna/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-plus me-2 text-primary"></i>Tambah Pengguna</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Pengguna Sistem</a></li>
                <li class="breadcrumb-item active">Tambah</li>
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

<form method="POST" enctype="multipart/form-data" id="formTambahPengguna" novalidate>
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
                            <input type="text" name="nama" id="nama"
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

            <!-- Kata Laluan -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Kata Laluan</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kata Laluan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password"
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Min 8 aksara" required minlength="8" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pw" data-target="password">
                                    <i class="bi bi-eye" id="iconPassword"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Sekurang-kurangnya 8 aksara.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sahkan Kata Laluan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirm" id="password_confirm"
                                       class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                       placeholder="Ulang kata laluan" required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pw" data-target="password_confirm">
                                    <i class="bi bi-eye" id="iconPasswordConfirm"></i>
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
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0"><i class="bi bi-camera me-2"></i>Gambar Profil</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img id="previewFoto" src="<?= BASE_URL ?>/assets/images/no-photo.png"
                             class="img-thumbnail rounded-circle"
                             style="width:150px;height:150px;object-fit:cover;" alt="Foto Pengguna">
                    </div>
                    <label class="form-label fw-semibold d-block">Muat Naik Foto (Pilihan)</label>
                    <input type="file" name="foto" id="foto"
                           class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($errors['foto'])): ?>
                    <div class="invalid-feedback"><?= $errors['foto'] ?></div>
                    <?php endif; ?>
                    <div class="form-text">JPG, PNG atau WEBP. Maks 5MB.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Simpan Pengguna
        </button>
        <button type="reset" class="btn btn-outline-secondary px-4" id="btnReset">
            <i class="bi bi-arrow-clockwise me-1"></i>Set Semula
        </button>
        <a href="index.php" class="btn btn-outline-danger px-4">
            <i class="bi bi-x-circle me-1"></i>Batal
        </a>
    </div>
</form>

<?php $extraScript = <<<'ENDSCRIPT'
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

    // Reset preview
    $('#btnReset').on('click', function(){
        setTimeout(function(){
            $('#previewFoto').attr('src', BASE_URL + '/assets/images/no-photo.png');
            $('#usernameStatus').text('');
            $('#emailStatus').text('');
            $('#passMatchStatus').text('');
        }, 50);
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

    // Username uniqueness check on blur
    $('#username').on('blur', function(){
        const val = $(this).val().trim();
        if (!val) return;
        $.get('<?= BASE_URL ?>/modules/pengguna/cek_unik.php', { jenis: 'username', nilai: val }, function(data){
            if (data.guna) {
                $('#usernameStatus').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Username telah digunakan.</span>');
                $('#username').addClass('is-invalid').removeClass('is-valid');
            } else {
                $('#usernameStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Username tersedia.</span>');
                $('#username').addClass('is-valid').removeClass('is-invalid');
            }
        }, 'json');
    });

    // Email uniqueness check on blur
    $('#email').on('blur', function(){
        const val = $(this).val().trim();
        if (!val) return;
        $.get('<?= BASE_URL ?>/modules/pengguna/cek_unik.php', { jenis: 'email', nilai: val }, function(data){
            if (data.guna) {
                $('#emailStatus').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>E-mel telah digunakan.</span>');
                $('#email').addClass('is-invalid').removeClass('is-valid');
            } else {
                $('#emailStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>E-mel tersedia.</span>');
                $('#email').addClass('is-valid').removeClass('is-invalid');
            }
        }, 'json');
    });

    // Password confirm match
    $('#password_confirm').on('input', function(){
        const pw  = $('#password').val();
        const pwc = $(this).val();
        if (!pwc) { $('#passMatchStatus').text(''); return; }
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
