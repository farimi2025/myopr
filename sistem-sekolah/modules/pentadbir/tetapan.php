<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin','super_admin','pentadbir']);

$pageTitle = 'Tetapan Sistem';
$errors    = [];
$tab       = clean($_GET['tab'] ?? 'sekolah');

// Load current settings
$settings = [
    'nama_sekolah'   => getSetting('nama_sekolah'),
    'alamat_sekolah' => getSetting('alamat_sekolah'),
    'telefon_sekolah'=> getSetting('telefon_sekolah'),
    'email_sekolah'  => getSetting('email_sekolah'),
    'negeri'         => getSetting('negeri'),
    'daerah'         => getSetting('daerah'),
    'jenis_sekolah'  => getSetting('jenis_sekolah'),
    'tahun_semasa'   => getSetting('tahun_semasa') ?: TAHUN_SEMASA,
    'logo_sekolah'   => getSetting('logo_sekolah'),
];

// Helper to save a setting
function saveSetting(string $key, string $value): void {
    $existing = dbValue("SELECT id FROM settings WHERE kunci=?", [$key]);
    if ($existing) {
        dbUpdate('settings', ['nilai' => $value], 'kunci=?', [$key]);
    } else {
        dbInsert('settings', ['kunci' => $key, 'nilai' => $value]);
    }
}

// Handle school settings form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_sekolah'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/tetapan.php?tab=sekolah');
    }

    $namaSekolah    = clean($_POST['nama_sekolah'] ?? '');
    $alamatSekolah  = clean($_POST['alamat_sekolah'] ?? '');
    $telefonSekolah = clean($_POST['telefon_sekolah'] ?? '');
    $emailSekolah   = clean($_POST['email_sekolah'] ?? '');
    $negeri         = clean($_POST['negeri'] ?? '');
    $daerah         = clean($_POST['daerah'] ?? '');
    $jenisSekolah   = clean($_POST['jenis_sekolah'] ?? '');
    $tahunSemasa    = (int)($_POST['tahun_semasa'] ?? TAHUN_SEMASA);

    if (empty($namaSekolah)) $errors['nama_sekolah'] = 'Nama sekolah diperlukan.';
    if ($emailSekolah && !filter_var($emailSekolah, FILTER_VALIDATE_EMAIL)) $errors['email_sekolah'] = 'Format emel tidak sah.';

    // Handle logo upload
    $logoLama = $settings['logo_sekolah'];
    $logo     = $logoLama;

    if (!empty($_FILES['logo_sekolah']['name'])) {
        $upload = uploadGambar($_FILES['logo_sekolah'], 'logo');
        if (!$upload['berjaya']) {
            $errors['logo_sekolah'] = $upload['mesej'];
        } else {
            $logo = $upload['fail'];
            if ($logoLama) padamFail($logoLama);
        }
    }

    if (empty($errors)) {
        saveSetting('nama_sekolah',    $namaSekolah);
        saveSetting('alamat_sekolah',  $alamatSekolah);
        saveSetting('telefon_sekolah', $telefonSekolah);
        saveSetting('email_sekolah',   $emailSekolah);
        saveSetting('negeri',          $negeri);
        saveSetting('daerah',          $daerah);
        saveSetting('jenis_sekolah',   $jenisSekolah);
        saveSetting('tahun_semasa',    (string)$tahunSemasa);
        if ($logo !== $logoLama) saveSetting('logo_sekolah', $logo);

        logAktiviti('kemaskini', 'tetapan', 0, 'Kemaskini tetapan sekolah');
        setFlash('success', 'Tetapan sekolah berjaya disimpan.');
        redirect(BASE_URL . '/modules/pentadbir/tetapan.php?tab=sekolah');
    }

    $tab = 'sekolah';
    // Repopulate
    $settings = array_merge($settings, [
        'nama_sekolah'   => $namaSekolah,
        'alamat_sekolah' => $alamatSekolah,
        'telefon_sekolah'=> $telefonSekolah,
        'email_sekolah'  => $emailSekolah,
        'negeri'         => $negeri,
        'daerah'         => $daerah,
        'jenis_sekolah'  => $jenisSekolah,
        'tahun_semasa'   => $tahunSemasa,
    ]);
}

// Handle password change
$pwErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tukar_kata_laluan'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/tetapan.php?tab=kata_laluan');
    }

    $tab           = 'kata_laluan';
    $userId        = $_SESSION['user_id'];
    $kataSediaAda  = $_POST['kata_laluan_lama'] ?? '';
    $kataBaru      = $_POST['kata_laluan_baru'] ?? '';
    $kataBaru2     = $_POST['kata_laluan_baru2'] ?? '';

    $user = dbFetch("SELECT id, kata_laluan FROM pengguna WHERE id=?", [$userId]);

    if (!$user || !password_verify($kataSediaAda, $user['kata_laluan'])) {
        $pwErrors['kata_laluan_lama'] = 'Kata laluan semasa tidak tepat.';
    }
    if (strlen($kataBaru) < 8) {
        $pwErrors['kata_laluan_baru'] = 'Kata laluan baru mestilah sekurang-kurangnya 8 aksara.';
    }
    if ($kataBaru !== $kataBaru2) {
        $pwErrors['kata_laluan_baru2'] = 'Pengesahan kata laluan tidak sepadan.';
    }

    if (empty($pwErrors)) {
        $hash = password_hash($kataBaru, PASSWORD_DEFAULT);
        dbUpdate('pengguna', ['kata_laluan' => $hash], 'id=?', [$userId]);
        logAktiviti('kemaskini', 'pengguna', $userId, 'Tukar kata laluan');
        setFlash('success', 'Kata laluan berjaya ditukar.');
        redirect(BASE_URL . '/modules/pentadbir/tetapan.php?tab=kata_laluan');
    }
}

$senaraiNegeri = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang',
                   'Perak','Perlis','Pulau Pinang','Sabah','Sarawak','Selangor',
                   'Terengganu','W.P. Kuala Lumpur','W.P. Labuan','W.P. Putrajaya'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-gear me-2 text-primary"></i>Tetapan Sistem</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Tetapan</li>
            </ol>
        </nav>
    </div>
</div>

<?= showFlash() ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="tetapanTab">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'sekolah' ? 'active' : '' ?>" href="?tab=sekolah">
            <i class="bi bi-building me-1"></i>Maklumat Sekolah
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'kata_laluan' ? 'active' : '' ?>" href="?tab=kata_laluan">
            <i class="bi bi-lock me-1"></i>Tukar Kata Laluan
        </a>
    </li>
</ul>

<?php if ($tab === 'sekolah'): ?>
<!-- School Settings Tab -->
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
        <!-- Main Settings -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-building me-2"></i>Maklumat Sekolah</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Sekolah <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sekolah" class="form-control <?= isset($errors['nama_sekolah']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($settings['nama_sekolah']) ?>" placeholder="Nama penuh sekolah">
                            <?php if (isset($errors['nama_sekolah'])): ?><div class="invalid-feedback"><?= $errors['nama_sekolah'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat Sekolah</label>
                            <textarea name="alamat_sekolah" class="form-control" rows="3"
                                      placeholder="Alamat lengkap sekolah"><?= clean($settings['alamat_sekolah']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Negeri</label>
                            <select name="negeri" class="form-select">
                                <option value="">-- Pilih Negeri --</option>
                                <?php foreach ($senaraiNegeri as $n): ?>
                                <option value="<?= $n ?>" <?= $settings['negeri'] === $n ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Daerah</label>
                            <input type="text" name="daerah" class="form-control"
                                   value="<?= clean($settings['daerah']) ?>" placeholder="Nama daerah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Telefon Sekolah</label>
                            <input type="text" name="telefon_sekolah" class="form-control"
                                   value="<?= clean($settings['telefon_sekolah']) ?>" placeholder="0X-XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Emel Sekolah</label>
                            <input type="email" name="email_sekolah" class="form-control <?= isset($errors['email_sekolah']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($settings['email_sekolah']) ?>" placeholder="sekolah@moe.gov.my">
                            <?php if (isset($errors['email_sekolah'])): ?><div class="invalid-feedback"><?= $errors['email_sekolah'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Sekolah</label>
                            <select name="jenis_sekolah" class="form-select">
                                <option value="">-- Pilih Jenis --</option>
                                <?php foreach (['Sekolah Kebangsaan','Sekolah Jenis Kebangsaan Cina','Sekolah Jenis Kebangsaan Tamil','Sekolah Menengah Kebangsaan','Sekolah Menengah Agama','Sekolah Berasrama Penuh','Sekolah Teknik','Sekolah Vokasional','Lain-lain'] as $j): ?>
                                <option value="<?= $j ?>" <?= $settings['jenis_sekolah'] === $j ? 'selected' : '' ?>><?= $j ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Semasa</label>
                            <input type="number" name="tahun_semasa" class="form-control"
                                   value="<?= clean($settings['tahun_semasa']) ?>" min="2000" max="2100">
                            <div class="form-text">Tahun akademik yang sedang aktif dalam sistem.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0"><i class="bi bi-image me-2"></i>Logo Sekolah</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img id="previewLogo" src="<?= gambarUrl($settings['logo_sekolah']) ?>"
                             class="img-thumbnail" style="max-width:150px;max-height:150px;object-fit:contain;"
                             alt="Logo Sekolah">
                    </div>
                    <label class="form-label fw-semibold d-block">Muat Naik Logo</label>
                    <input type="file" name="logo_sekolah" id="logoSekolah"
                           class="form-control <?= isset($errors['logo_sekolah']) ? 'is-invalid' : '' ?>"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($errors['logo_sekolah'])): ?><div class="invalid-feedback"><?= $errors['logo_sekolah'] ?></div><?php endif; ?>
                    <div class="form-text">JPG, PNG atau WEBP. Maks 5MB.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" name="simpan_sekolah" value="1" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Simpan Tetapan
        </button>
    </div>
</form>

<?php elseif ($tab === 'kata_laluan'): ?>
<!-- Change Password Tab -->
<?php if (!empty($pwErrors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Sila betulkan ralat berikut:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($pwErrors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Tukar Kata Laluan Akaun</h6>
            </div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Laluan Semasa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="kata_laluan_lama" id="kl1"
                                   class="form-control <?= isset($pwErrors['kata_laluan_lama']) ? 'is-invalid' : '' ?>"
                                   placeholder="Masukkan kata laluan semasa" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('kl1')">
                                <i class="bi bi-eye" id="kl1Icon"></i>
                            </button>
                        </div>
                        <?php if (isset($pwErrors['kata_laluan_lama'])): ?><div class="text-danger small mt-1"><?= $pwErrors['kata_laluan_lama'] ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Laluan Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="kata_laluan_baru" id="kl2"
                                   class="form-control <?= isset($pwErrors['kata_laluan_baru']) ? 'is-invalid' : '' ?>"
                                   placeholder="Minimum 8 aksara" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('kl2')">
                                <i class="bi bi-eye" id="kl2Icon"></i>
                            </button>
                        </div>
                        <?php if (isset($pwErrors['kata_laluan_baru'])): ?><div class="text-danger small mt-1"><?= $pwErrors['kata_laluan_baru'] ?></div><?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Sahkan Kata Laluan Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="kata_laluan_baru2" id="kl3"
                                   class="form-control <?= isset($pwErrors['kata_laluan_baru2']) ? 'is-invalid' : '' ?>"
                                   placeholder="Ulang kata laluan baru" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('kl3')">
                                <i class="bi bi-eye" id="kl3Icon"></i>
                            </button>
                        </div>
                        <?php if (isset($pwErrors['kata_laluan_baru2'])): ?><div class="text-danger small mt-1"><?= $pwErrors['kata_laluan_baru2'] ?></div><?php endif; ?>
                    </div>

                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Selepas menukar kata laluan, anda perlu log masuk semula.
                    </div>

                    <button type="submit" name="tukar_kata_laluan" value="1" class="btn btn-danger w-100">
                        <i class="bi bi-lock me-1"></i>Tukar Kata Laluan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $extraScript = <<<JS
<script>
function togglePwd(id) {
    const input = document.getElementById(id);
    const icon  = document.getElementById(id + 'Icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

$(function(){
    $('#logoSekolah').on('change', function(){
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => $('#previewLogo').attr('src', e.target.result);
            reader.readAsDataURL(file);
        }
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
