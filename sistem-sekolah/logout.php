<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Log aktiviti log keluar
if (!empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/includes/functions.php';
        $userId = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        dbQuery("INSERT INTO aktiviti_log (user_id,tindakan,modul,keterangan,ip_address) VALUES (?,?,?,?,?)",
            [$userId, 'logout', 'users', 'Log keluar sistem', $ip]);
    } catch (Exception) {}
}

// Hapus sesi
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
