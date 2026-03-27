<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// Mulakan sesi
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Jika sudah log masuk, redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$ralat  = '';
$berjaya = false;

// Semak token — mesti ada dan belum tamat
$user = null;
if ($token) {
    $user = dbFetch(
        "SELECT id, nama, username FROM users WHERE token_reset=? AND token_reset_tamat > NOW() AND status='aktif' LIMIT 1",
        [$token]
    );
}

if (!$token || !$user) {
    $ralat = 'Pautan reset kata laluan tidak sah atau telah tamat tempoh. Sila minta semula.';
}

// Proses kemaskini kata laluan
if (!$ralat && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kataLaluan    = $_POST['kata_laluan'] ?? '';
    $sahKataLaluan = $_POST['sah_kata_laluan'] ?? '';

    if (strlen($kataLaluan) < 8) {
        $ralat = 'Kata laluan mesti sekurang-kurangnya 8 aksara.';
    } elseif ($kataLaluan !== $sahKataLaluan) {
        $ralat = 'Kata laluan tidak sepadan. Sila semak semula.';
    } else {
        $hashed = password_hash($kataLaluan, PASSWORD_DEFAULT);
        dbQuery(
            "UPDATE users SET password=?, token_reset=NULL, token_reset_tamat=NULL WHERE id=?",
            [$hashed, $user['id']]
        );
        $_SESSION['flash'] = ['jenis' => 'success', 'mesej' => 'Kata laluan berjaya ditetapkan semula. Sila log masuk dengan kata laluan baharu.'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

$namSekolah = dbValue("SELECT nilai FROM settings WHERE kunci='nama_sekolah'") ?? 'Sistem Pengurusan Sekolah';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Laluan | <?= htmlspecialchars($namSekolah) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #0891b2 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            width: 420px; max-width: 95vw;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            color: #fff;
        }
        .login-header .school-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto .75rem;
            font-size: 2rem;
        }
        .login-header h1 { font-size: 1.1rem; font-weight: 700; margin: 0 0 .25rem; }
        .login-header p { font-size: .8rem; opacity: .8; margin: 0; }
        .login-body { padding: 2rem; }
        .form-control {
            border-radius: .6rem; border-color: #e2e8f0;
            padding: .65rem .9rem; font-size: .9rem;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 .2rem rgba(37,99,235,.15); }
        .input-group-text { background: #f8fafc; border-color: #e2e8f0; border-radius: .6rem 0 0 .6rem; }
        .btn-reset {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none; border-radius: .6rem;
            font-weight: 600; font-size: .95rem;
            padding: .7rem; letter-spacing: .02em;
            transition: opacity .2s, transform .2s;
        }
        .btn-reset:hover { opacity: .9; transform: translateY(-1px); }
        .login-footer { text-align: center; font-size: .78rem; color: #94a3b8; padding: 0 2rem 1.5rem; }
        .alert { border-radius: .6rem; font-size: .875rem; }
        .password-toggle { cursor: pointer; background: #f8fafc; border-left: none !important; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="school-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h1><?= htmlspecialchars($namSekolah) ?></h1>
        <p>Reset Kata Laluan</p>
    </div>
    <div class="login-body">
        <h2 class="fs-5 fw-semibold text-center text-dark mb-4">Tetapkan Kata Laluan Baharu</h2>

        <?php if ($ralat): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill flex-shrink-0"></i>
            <div><?= htmlspecialchars($ralat) ?></div>
        </div>
        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>/lupa_kata_laluan.php" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman lupa kata laluan
            </a>
        </div>
        <?php else: ?>

        <p class="text-muted small text-center mb-4">
            Menetapkan semula kata laluan untuk akaun: <strong><?= htmlspecialchars($user['username']) ?></strong>
        </p>

        <form method="POST" action="<?= BASE_URL ?>/reset_kata_laluan.php?token=<?= htmlspecialchars($token) ?>" autocomplete="off">
            <div class="mb-3">
                <label class="form-label fw-semibold">Kata Laluan Baharu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control" name="kata_laluan" id="pwd1"
                           placeholder="Sekurang-kurangnya 8 aksara" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary password-toggle"
                            onclick="togglePwd('pwd1','icon1')">
                        <i class="bi bi-eye" id="icon1"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Sahkan Kata Laluan</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill text-muted"></i></span>
                    <input type="password" class="form-control" name="sah_kata_laluan" id="pwd2"
                           placeholder="Masukkan semula kata laluan" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary password-toggle"
                            onclick="togglePwd('pwd2','icon2')">
                        <i class="bi bi-eye" id="icon2"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-reset btn-primary w-100 text-white">
                <i class="bi bi-check2-circle me-2"></i>Tetapkan Kata Laluan
            </button>
        </form>

        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>/login.php" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman log masuk
            </a>
        </div>
    </div>
    <div class="login-footer">
        <i class="bi bi-shield-lock me-1"></i>
        Kata laluan mesti sekurang-kurangnya 8 aksara.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
