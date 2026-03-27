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

$mesej      = '';
$jenisAlert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username) {
        $user = dbFetch("SELECT id, username FROM users WHERE username=? AND status='aktif' LIMIT 1", [$username]);

        if ($user) {
            // Jana token reset 32 aksara
            $token = bin2hex(random_bytes(16));
            // Simpan token + masa tamat 1 jam
            dbQuery(
                "UPDATE users SET token_reset=?, token_reset_tamat=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id=?",
                [$token, $user['id']]
            );
            // Tunjuk 8 aksara pertama token sahaja
            $kodPendek = strtoupper(substr($token, 0, 8));
        } else {
            // Jangan dedah sama ada username wujud — tetap jana mesej yang sama
            $kodPendek = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        $mesej      = "Sila hubungi pentadbir sistem dengan kod ini: <strong class=\"font-monospace fs-5\">{$kodPendek}</strong>";
        $jenisAlert = 'info';
    } else {
        $mesej      = 'Sila masukkan username anda.';
        $jenisAlert = 'warning';
    }
}

$namSekolah = dbValue("SELECT nilai FROM settings WHERE kunci='nama_sekolah'") ?? 'Sistem Pengurusan Sekolah';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Laluan | <?= htmlspecialchars($namSekolah) ?></title>
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
        .btn-hantar {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none; border-radius: .6rem;
            font-weight: 600; font-size: .95rem;
            padding: .7rem; letter-spacing: .02em;
            transition: opacity .2s, transform .2s;
        }
        .btn-hantar:hover { opacity: .9; transform: translateY(-1px); }
        .login-footer { text-align: center; font-size: .78rem; color: #94a3b8; padding: 0 2rem 1.5rem; }
        .alert { border-radius: .6rem; font-size: .875rem; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="school-icon"><i class="bi bi-key-fill"></i></div>
        <h1><?= htmlspecialchars($namSekolah) ?></h1>
        <p>Lupa Kata Laluan</p>
    </div>
    <div class="login-body">
        <h2 class="fs-5 fw-semibold text-center text-dark mb-1">Set Semula Kata Laluan</h2>
        <p class="text-muted small text-center mb-4">Masukkan username anda untuk mendapatkan kod reset.</p>

        <?php if ($mesej): ?>
        <div class="alert alert-<?= htmlspecialchars($jenisAlert) ?> d-flex align-items-start gap-2">
            <?php if ($jenisAlert === 'info'): ?>
            <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
            <?php else: ?>
            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
            <?php endif; ?>
            <div><?= $mesej ?></div>
        </div>
        <?php endif; ?>

        <?php if (!$mesej || $jenisAlert !== 'info'): ?>
        <form method="POST" autocomplete="off">
            <div class="mb-4">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn btn-hantar btn-primary w-100 text-white">
                <i class="bi bi-send me-2"></i>Dapatkan Kod Reset
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
        Hubungi pentadbir sistem dengan kod yang diberikan untuk mendapatkan URL reset kata laluan.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
