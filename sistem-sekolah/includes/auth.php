<?php
// ============================================================
// PENGESAHAN SESI & AKSES
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Mulakan sesi
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

// Semak sama ada pengguna telah log masuk
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Semak peranan pengguna
function hasRole(string|array $roles): bool {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['user_peranan'] ?? '';
    if (is_string($roles)) return $userRole === $roles;
    return in_array($userRole, $roles);
}

// Paksa log masuk
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // Semak sama ada sesi masih sah
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        logoutUser();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Paksa peranan tertentu
function requireRole(string|array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: ' . BASE_URL . '/index.php?error=akses_ditolak');
        exit;
    }
}

// Dapatkan maklumat pengguna semasa
function getCurrentUser(): array {
    if (!isLoggedIn()) return [];
    return [
        'id'      => $_SESSION['user_id'],
        'nama'    => $_SESSION['user_nama'],
        'peranan' => $_SESSION['user_peranan'],
        'foto'    => $_SESSION['user_foto'] ?? '',
    ];
}

// Log masuk
function loginUser(string $username, string $password): array {
    $user = dbFetch("SELECT * FROM users WHERE (username=? OR email=?) AND status='aktif' LIMIT 1",
        [$username, $username]);

    if (!$user) return ['berjaya' => false, 'mesej' => 'Username atau kata laluan tidak sah.'];
    if (!password_verify($password, $user['password'])) {
        return ['berjaya' => false, 'mesej' => 'Username atau kata laluan tidak sah.'];
    }

    // Simpan ke sesi
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_nama']    = $user['nama'];
    $_SESSION['user_peranan'] = $user['peranan'];
    $_SESSION['user_foto']    = $user['foto'];
    $_SESSION['last_activity'] = time();

    // Kemas kini last_login
    dbQuery("UPDATE users SET last_login=NOW() WHERE id=?", [$user['id']]);

    // Log aktiviti
    logAktiviti('login', 'users', $user['id'], 'Log masuk sistem');

    $redirect = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/index.php');
    unset($_SESSION['redirect_after_login']);

    return ['berjaya' => true, 'redirect' => $redirect];
}

// Log keluar
function logoutUser(): void {
    if (isLoggedIn()) {
        logAktiviti('logout', 'users', $_SESSION['user_id'], 'Log keluar sistem');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'],
            $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// Rekod log aktiviti
function logAktiviti(string $tindakan, string $modul, ?int $rekodId, string $keterangan = ''): void {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        dbQuery("INSERT INTO aktiviti_log (user_id,tindakan,modul,rekod_id,keterangan,ip_address) VALUES (?,?,?,?,?,?)",
            [$userId, $tindakan, $modul, $rekodId, $keterangan, $ip]);
    } catch (Exception) { /* abaikan ralat log */ }
}

// Jalankan semak log masuk
requireLogin();

// Dapatkan tetapan sekolah
function getSetting(string $kunci): string {
    static $settings = null;
    if ($settings === null) {
        $rows = dbFetchAll("SELECT kunci, nilai FROM settings");
        $settings = array_column($rows, 'nilai', 'kunci');
    }
    return $settings[$kunci] ?? '';
}
