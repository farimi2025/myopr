<?php
// ============================================================
// PROFIL PENGGUNA — kemaskini maklumat & kata laluan
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle = 'Profil Saya';
$userId    = (int)$_SESSION['user_id'];
$errors    = [];
$tab       = clean($_GET['tab'] ?? 'profil');

// Ambil data semasa
$user = dbFetch("SELECT * FROM users WHERE id=?", [$userId]);
if (!$user) {
    setFlash('danger', 'Rekod pengguna tidak ditemui.');
    redirect(BASE_URL . '/index.php');
}

// ── POST: kemaskini profil ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_profil'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/profil.php?tab=profil');
    }

    $nama  = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');

    if (empty($nama))  $errors['nama']  = 'Nama wajib diisi.';
    if (empty($email)) $errors['email'] = 'E-mel wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format e-mel tidak sah.';

    // Semak duplikat e-mel (kecuali rekod sendiri)
    if (empty($errors['email'])) {
        $dup = dbValue("SELECT id FROM users WHERE email=? AND id!=?", [$email, $userId]);
        if ($dup) $errors['email'] = 'E-mel ini telah digunakan oleh pengguna lain.';
    }

    // Upload foto
    $fotoLama = $user['foto'];
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
    if (isset($_POST['padam_foto']) && $fotoLama) {
        padamFail($fotoLama);
        $foto = null;
    }

    if (empty($errors)) {
        dbQuery("UPDATE users SET nama=?, email=?, foto=? WHERE id=?",
            [$nama, $email, $foto, $userId]);
        // Kemaskini sesi
        $_SESSION['user_nama'] = $nama;
        $_SESSION['user_foto'] = $foto;
        logAktiviti('kemaskini', 'users', $userId, 'Kemaskini profil pengguna');
        setFlash('success', 'Profil berjaya dikemaskini.');
        redirect(BASE_URL . '/modules/pentadbir/profil.php?tab=profil');
    }
}

// ── POST: tukar kata laluan ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tukar_kata_laluan'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/pentadbir/profil.php?tab=katalaluan');
    }

    $tab         = 'katalaluan';
    $semasa      = $_POST['kata_laluan_semasa'] ?? '';
    $baru        = $_POST['kata_laluan_baru'] ?? '';
    $sahkan      = $_POST['kata_laluan_sahkan'] ?? '';

    if (!password_verify($semasa, $user['password'])) {
        $errors['semasa'] = 'Kata laluan semasa tidak betul.';
    }
    if (strlen($baru) < 8) {
        $errors['baru'] = 'Kata laluan baru mestilah sekurang-kurangnya 8 aksara.';
    }
    if ($baru !== $sahkan) {
        $errors['sahkan'] = 'Pengesahan kata laluan tidak sepadan.';
    }

    if (empty($errors)) {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        dbQuery("UPDATE users SET password=? WHERE id=?", [$hash, $userId]);
        logAktiviti('tukar_kata_laluan', 'users', $userId, 'Tukar kata laluan');
        setFlash('success', 'Kata laluan berjaya ditukar.');
        redirect(BASE_URL . '/modules/pentadbir/profil.php?tab=katalaluan');
    }
}

// Muat semula rekod selepas kemungkinan kemaskini
$user = dbFetch("SELECT * FROM users WHERE id=?", [$userId]);

// Kiraan aktiviti log
$jumlahAktiviti = (int)dbValue("SELECT COUNT(*) FROM aktiviti_log WHERE user_id=?", [$userId]);
$aktivitiTerkini = dbFetchAll("SELECT * FROM aktiviti_log WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$userId]);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>Profil Saya</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Profil Saya</li>
            </ol>
        </nav>
    </div>
</div>

<?= showFlash() ?>

<div class="row g-4">
    <!-- Kad profil kiri -->
    <div class="col-lg-3">
        <div class="card shadow-sm text-center">
            <div class="card-body py-4">
                <?php $fotoUrl = gambarUrl($user['foto']); ?>
                <img src="<?= $fotoUrl ?>" alt="Foto Profil"
                     class="rounded-circle mb-3 border border-3 border-primary"
                     style="width:100px;height:100px;object-fit:cover">
                <h5 class="mb-1"><?= clean($user['nama']) ?></h5>
                <span class="badge bg-primary"><?= ucfirst(str_replace('_',' ', $user['peranan'])) ?></span>
                <hr>
                <div class="text-start text-muted small">
                    <p class="mb-1"><i class="bi bi-person me-2"></i><?= clean($user['username']) ?></p>
                    <p class="mb-1"><i class="bi bi-envelope me-2"></i><?= clean($user['email'] ?? '-') ?></p>
                    <p class="mb-1"><i class="bi bi-clock me-2"></i>Log masuk terakhir:<br>
                        <span class="ms-3"><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Tiada rekod' ?></span>
                    </p>
                    <p class="mb-0"><i class="bi bi-activity me-2"></i><?= number_format($jumlahAktiviti) ?> aktiviti direkodkan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bahagian kanan: tab -->
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <ul class="nav nav-tabs card-header-tabs" id="profilTab">
                    <li class="nav-item">
                        <a class="nav-link <?= $tab === 'profil' ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/modules/pentadbir/profil.php?tab=profil">
                            <i class="bi bi-person me-1"></i>Maklumat Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab === 'katalaluan' ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/modules/pentadbir/profil.php?tab=katalaluan">
                            <i class="bi bi-key me-1"></i>Tukar Kata Laluan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab === 'aktiviti' ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/modules/pentadbir/profil.php?tab=aktiviti">
                            <i class="bi bi-list-ul me-1"></i>Log Aktiviti
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">

                <!-- Tab: Maklumat Profil -->
                <?php if ($tab === 'profil'): ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Penuh <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($_POST['nama'] ?? $user['nama']) ?>" required>
                            <?php if (isset($errors['nama'])): ?>
                                <div class="invalid-feedback"><?= $errors['nama'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= clean($user['username']) ?>" readonly>
                            <small class="text-muted">Username tidak boleh ditukar.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mel <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= clean($_POST['email'] ?? $user['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Peranan</label>
                            <input type="text" class="form-control bg-light"
                                   value="<?= ucfirst(str_replace('_',' ', $user['peranan'])) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto Profil</label>
                            <?php if ($user['foto']): ?>
                            <div class="mb-2">
                                <img src="<?= gambarUrl($user['foto']) ?>" alt="Foto"
                                     class="rounded border" style="height:80px;object-fit:cover">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="padam_foto" id="padamFoto" value="1">
                                    <label class="form-check-label text-danger small" for="padamFoto">Padam foto semasa</label>
                                </div>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="foto" class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                                   accept="image/jpeg,image/png,image/webp">
                            <?php if (isset($errors['foto'])): ?>
                                <div class="invalid-feedback"><?= $errors['foto'] ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Muat naik JPG/PNG/WEBP, maks. 5MB.</small>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="simpan_profil" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>

                <!-- Tab: Tukar Kata Laluan -->
                <?php elseif ($tab === 'katalaluan'): ?>
                <form method="post" autocomplete="off">
                    <?= csrfField() ?>
                    <div class="row g-3" style="max-width:480px">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kata Laluan Semasa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="kata_laluan_semasa" id="kls"
                                       class="form-control <?= isset($errors['semasa']) ? 'is-invalid' : '' ?>"
                                       required autocomplete="current-password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="kls">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($errors['semasa'])): ?>
                                    <div class="invalid-feedback"><?= $errors['semasa'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kata Laluan Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="kata_laluan_baru" id="klb"
                                       class="form-control <?= isset($errors['baru']) ? 'is-invalid' : '' ?>"
                                       required autocomplete="new-password" minlength="8">
                                <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="klb">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($errors['baru'])): ?>
                                    <div class="invalid-feedback"><?= $errors['baru'] ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Sekurang-kurangnya 8 aksara.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Sahkan Kata Laluan Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="kata_laluan_sahkan" id="kls2"
                                       class="form-control <?= isset($errors['sahkan']) ? 'is-invalid' : '' ?>"
                                       required autocomplete="new-password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="kls2">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($errors['sahkan'])): ?>
                                    <div class="invalid-feedback"><?= $errors['sahkan'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="tukar_kata_laluan" class="btn btn-warning px-4">
                            <i class="bi bi-key me-1"></i>Tukar Kata Laluan
                        </button>
                    </div>
                </form>

                <!-- Tab: Log Aktiviti -->
                <?php elseif ($tab === 'aktiviti'): ?>
                <h6 class="mb-3">10 Aktiviti Terkini</h6>
                <?php if ($aktivitiTerkini): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tarikh/Masa</th>
                                <th>Tindakan</th>
                                <th>Modul</th>
                                <th>Keterangan</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aktivitiTerkini as $log): ?>
                            <tr>
                                <td class="text-nowrap small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeMap = [
                                        'login'  => 'success', 'logout' => 'secondary',
                                        'tambah' => 'primary', 'kemaskini' => 'info',
                                        'padam'  => 'danger',  'tukar_kata_laluan' => 'warning',
                                    ];
                                    $warna = $badgeMap[$log['tindakan']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $warna ?>"><?= clean($log['tindakan']) ?></span>
                                </td>
                                <td class="small"><?= clean($log['modul'] ?? '-') ?></td>
                                <td class="small"><?= clean($log['keterangan'] ?? '-') ?></td>
                                <td class="small text-muted"><?= clean($log['ip_address'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">Tiada rekod aktiviti.</p>
                <?php endif; ?>
                <?php endif; ?>

            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div><!-- /.col -->
</div><!-- /.row -->

<script>
document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
