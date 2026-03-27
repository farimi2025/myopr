<?php
// ============================================================
// WIZARD PEMASANGAN — Sistem Pengurusan Sekolah
// Akses sekali sahaja: http://domain.com/sistem-sekolah/install/install.php
// PADAM fail ini selepas pemasangan selesai
// ============================================================

define('INSTALL_OK', true);
define('VERSION', '1.0');

$step = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$mesej = '';
$ralat = '';

// ---- Langkah 2: Test sambungan DB ----
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $dbName = trim($_POST['db_name'] ?? 'sekolah_db');

    try {
        $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");

        // Run SQL schema
        $sql = file_get_contents(__DIR__ . '/setup.sql');
        // Split by ; but ignore inside strings
        $statements = preg_split('/;(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt) {
                try { $pdo->exec($stmt); } catch (Exception) { /* abaikan CREATE IF NOT EXISTS errors */ }
            }
        }

        // Save config
        $configContent = '<?php' . "\n" .
            "define('DB_HOST', '" . addslashes($dbHost) . "');\n" .
            "define('DB_USER', '" . addslashes($dbUser) . "');\n" .
            "define('DB_PASS', '" . addslashes($dbPass) . "');\n" .
            "define('DB_NAME', '" . addslashes($dbName) . "');\n" .
            "define('DB_CHARSET', 'utf8mb4');\n";

        // Tulis database.php (bahagian credentials sahaja, fungsi kekal)
        $dbFile = dirname(__DIR__) . '/config/database.php';
        $existing = file_get_contents($dbFile);
        $existing = preg_replace(
            "/define\('DB_HOST'.+?define\('DB_CHARSET'.+?;/s",
            rtrim($configContent),
            $existing
        );
        file_put_contents($dbFile, $existing);

        $_SESSION['install_db'] = ['host'=>$dbHost,'user'=>$dbUser,'pass'=>$dbPass,'name'=>$dbName];
        $step = 3;
        $mesej = 'Database berjaya diwujudkan dan skema telah diimport!';
    } catch (PDOException $e) {
        $ralat = 'Gagal sambung database: ' . $e->getMessage();
        $step = 2;
    }
}

// ---- Langkah 3: Tetapan sekolah & admin ----
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaSekolah = trim($_POST['nama_sekolah'] ?? '');
    $adminUser   = trim($_POST['admin_user'] ?? 'admin');
    $adminPass   = trim($_POST['admin_pass'] ?? '');
    $adminEmail  = trim($_POST['admin_email'] ?? '');

    if (!$namaSekolah || !$adminPass) {
        $ralat = 'Sila isi nama sekolah dan kata laluan admin.';
        $step = 3;
    } else {
        try {
            $db = $_SESSION['install_db'] ?? [];
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Update settings
            $settings = [
                'nama_sekolah' => $namaSekolah,
                'alamat_sekolah' => $_POST['alamat_sekolah'] ?? '',
                'telefon_sekolah' => $_POST['telefon_sekolah'] ?? '',
                'email_sekolah' => $_POST['email_sekolah'] ?? '',
                'negeri' => $_POST['negeri'] ?? '',
                'daerah' => $_POST['daerah'] ?? '',
                'jenis_sekolah' => $_POST['jenis_sekolah'] ?? 'SMK',
                'tahun_semasa' => date('Y'),
            ];
            foreach ($settings as $k => $v) {
                $pdo->prepare("INSERT INTO settings (kunci,nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=?")->execute([$k,$v,$v]);
            }

            // Update admin user
            $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET username=?, email=?, password=?, nama=? WHERE id=1")
                ->execute([$adminUser, $adminEmail, $hash, 'Super Administrator']);

            $step = 5;
            $mesej = 'Pemasangan selesai!';
        } catch (Exception $e) {
            $ralat = 'Ralat: ' . $e->getMessage();
            $step = 3;
        }
    }
}

session_start();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemasangan Sistem Pengurusan Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#1e3a8a,#1d4ed8 60%,#0891b2); min-height:100vh; font-family:'Segoe UI',sans-serif; }
        .install-card { max-width:600px; margin:40px auto; background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; }
        .install-header { background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; padding:2rem; text-align:center; }
        .step-indicator { display:flex; justify-content:center; gap:8px; margin-top:1rem; }
        .step-dot { width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,.3); }
        .step-dot.active { background:#fff; }
        .step-dot.done { background:rgba(255,255,255,.7); }
        .install-body { padding:2rem; }
        .form-control { border-radius:.6rem; border-color:#e2e8f0; }
        .btn-install { background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;border-radius:.6rem;font-weight:600; }
    </style>
</head>
<body>
<div class="install-card">
    <div class="install-header">
        <div style="font-size:3rem;margin-bottom:.5rem">🏫</div>
        <h1 style="font-size:1.3rem;font-weight:700;margin:0">Sistem Pengurusan Sekolah</h1>
        <p style="opacity:.8;font-size:.9rem;margin:.25rem 0 0">Wizard Pemasangan v<?= VERSION ?></p>
        <div class="step-indicator">
            <?php for ($i=1;$i<=5;$i++): ?>
            <div class="step-dot <?= $i<$step?'done':($i==$step?'active':'') ?>"></div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="install-body">
        <?php if ($mesej): ?>
        <div class="alert alert-success d-flex gap-2 align-items-center"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($mesej) ?></div>
        <?php endif; ?>
        <?php if ($ralat): ?>
        <div class="alert alert-danger d-flex gap-2 align-items-center"><i class="bi bi-x-circle-fill"></i><?= htmlspecialchars($ralat) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <h2 class="fs-5 fw-bold mb-3">Langkah 1: Semakan Keperluan</h2>
        <?php
        $checks = [
            'PHP 8.0+' => version_compare(PHP_VERSION, '8.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'ZipArchive' => extension_loaded('zip'),
            'GD Library' => extension_loaded('gd'),
            'Folder uploads boleh tulis' => is_writable(dirname(__DIR__) . '/assets/uploads'),
            'Folder config boleh tulis' => is_writable(dirname(__DIR__) . '/config'),
        ];
        $allOk = true;
        ?>
        <ul class="list-group mb-4">
            <?php foreach ($checks as $label => $ok): ?>
            <?php if (!$ok) $allOk = false; ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($label) ?>
                <span class="badge bg-<?= $ok?'success':'danger' ?>"><?= $ok?'OK':'GAGAL' ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$allOk): ?>
        <div class="alert alert-warning">Beberapa keperluan tidak dipenuhi. Sila hubungi pentadbir server.</div>
        <?php endif; ?>
        <form method="GET"><input type="hidden" name="step" value="2">
        <button type="submit" class="btn btn-install btn-primary w-100" <?= !$allOk?'disabled':'' ?>>
            <i class="bi bi-arrow-right me-2"></i>Seterusnya: Konfigurasi Database
        </button></form>

        <?php elseif ($step === 2): ?>
        <h2 class="fs-5 fw-bold mb-3">Langkah 2: Konfigurasi Database</h2>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <div class="mb-3">
                <label class="form-label fw-semibold">Host Database</label>
                <input type="text" class="form-control" name="db_host" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username MySQL</label>
                <input type="text" class="form-control" name="db_user" placeholder="root" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password MySQL</label>
                <input type="password" class="form-control" name="db_pass" placeholder="(kosong jika tiada)">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Database</label>
                <input type="text" class="form-control" name="db_name" value="sekolah_db" required>
                <div class="form-text">Database akan diwujudkan secara automatik jika belum ada.</div>
            </div>
            <button type="submit" class="btn btn-install btn-primary w-100">
                <i class="bi bi-database me-2"></i>Sambung & Import Database
            </button>
        </form>

        <?php elseif ($step === 3): ?>
        <h2 class="fs-5 fw-bold mb-3">Langkah 3: Maklumat Sekolah & Admin</h2>
        <form method="POST">
            <input type="hidden" name="step" value="4">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Sekolah *</label>
                <input type="text" class="form-control" name="nama_sekolah" placeholder="SMK..." required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jenis Sekolah</label>
                <select class="form-select" name="jenis_sekolah">
                    <option>SK</option><option>SMK</option><option>SJK(C)</option>
                    <option>SJK(T)</option><option>SAR</option><option>SMKA</option>
                    <option>SMAN</option><option>SBP</option><option>Lain-lain</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Alamat Sekolah</label>
                <textarea class="form-control" name="alamat_sekolah" rows="2"></textarea>
            </div>
            <div class="row g-2 mb-3">
                <div class="col"><label class="form-label fw-semibold">Negeri</label>
                    <input type="text" class="form-control" name="negeri"></div>
                <div class="col"><label class="form-label fw-semibold">Daerah</label>
                    <input type="text" class="form-control" name="daerah"></div>
            </div>
            <hr><h6 class="fw-bold text-primary">Akaun Admin Sistem</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username Admin *</label>
                <input type="text" class="form-control" name="admin_user" value="admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kata Laluan Admin *</label>
                <input type="password" class="form-control" name="admin_pass" placeholder="Min. 8 aksara" required minlength="8">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Admin</label>
                <input type="email" class="form-control" name="admin_email">
            </div>
            <button type="submit" class="btn btn-install btn-primary w-100">
                <i class="bi bi-check2-circle me-2"></i>Selesaikan Pemasangan
            </button>
        </form>

        <?php elseif ($step === 5): ?>
        <div class="text-center py-3">
            <div style="font-size:4rem">🎉</div>
            <h2 class="fs-4 fw-bold text-success mt-2">Pemasangan Selesai!</h2>
            <p class="text-muted">Sistem Pengurusan Sekolah telah berjaya dipasang.</p>
            <div class="alert alert-warning text-start mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>PENTING:</strong> Sila <strong>PADAM</strong> fail ini
                (<code>install/install.php</code>) segera untuk keselamatan!
            </div>
            <a href="../index.php" class="btn btn-success btn-lg mt-2">
                <i class="bi bi-house me-2"></i>Pergi ke Sistem
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
