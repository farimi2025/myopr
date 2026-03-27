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

$ralat = '';
$timeout = !empty($_GET['timeout']);

// Proses log masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $ralat = 'Sila masukkan username dan kata laluan.';
    } else {
        require_once __DIR__ . '/includes/auth.php';
        // Override requireLogin dalam auth.php (kita sedang di halaman login)
        // Semak manual
        $user = dbFetch("SELECT * FROM users WHERE (username=? OR email=?) AND status='aktif' LIMIT 1",
            [$username, $username]);
        if (!$user || !password_verify($password, $user['password'])) {
            $ralat = 'Username atau kata laluan tidak sah. Sila cuba lagi.';
        } else {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_nama']     = $user['nama'];
            $_SESSION['user_peranan']  = $user['peranan'];
            $_SESSION['user_foto']     = $user['foto'];
            $_SESSION['last_activity'] = time();
            dbQuery("UPDATE users SET last_login=NOW() WHERE id=?", [$user['id']]);
            $redirect = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/index.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$namSekolah = dbValue("SELECT nilai FROM settings WHERE kunci='nama_sekolah'") ?? 'Sistem Pengurusan Sekolah';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk | <?= htmlspecialchars($namSekolah) ?></title>
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
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none; border-radius: .6rem;
            font-weight: 600; font-size: .95rem;
            padding: .7rem; letter-spacing: .02em;
            transition: opacity .2s, transform .2s;
        }
        .btn-login:hover { opacity: .9; transform: translateY(-1px); }
        .login-footer { text-align: center; font-size: .78rem; color: #94a3b8; padding: 0 2rem 1.5rem; }
        .password-toggle { cursor: pointer; background: #f8fafc; border-left: none !important; }
        .alert { border-radius: .6rem; font-size: .875rem; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="school-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <h1><?= htmlspecialchars($namSekolah) ?></h1>
        <p>Sistem Pengurusan Sekolah</p>
    </div>
    <div class="login-body">
        <h2 class="fs-5 fw-700 text-center text-dark mb-4">Log Masuk</h2>

        <?php if ($timeout): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-clock-history"></i>
            Sesi anda telah tamat. Sila log masuk semula.
        </div>
        <?php endif; ?>

        <?php if ($ralat): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($ralat) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <div class="mb-3">
                <label class="form-label fw-semibold">Username / Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Masukkan username" required autofocus autocomplete="username">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Kata Laluan</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control" name="password" id="password"
                           placeholder="Masukkan kata laluan" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary password-toggle"
                            onclick="togglePwd()">
                        <i class="bi bi-eye" id="pwdIcon"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ingat" id="ingat">
                    <label class="form-check-label small" for="ingat">Ingat saya</label>
                </div>
            </div>
            <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>Log Masuk
            </button>
        </form>
    </div>
    <div class="login-footer">
        <i class="bi bi-shield-lock me-1"></i>
        Sistem ini dilindungi kata laluan. Hanya pengguna yang diberi kebenaran sahaja boleh log masuk.
        <br><span class="mt-1 d-block">Hubungi pentadbir jika anda terlupa kata laluan.</span>
    </div>
</div>

<script>
function togglePwd() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('pwdIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
