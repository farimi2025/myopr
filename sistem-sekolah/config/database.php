<?php
// ============================================================
// KONFIGURASI DATABASE
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Tukar kepada username MySQL anda
define('DB_PASS', '');            // Tukar kepada password MySQL anda
define('DB_NAME', 'sekolah_db');
define('DB_CHARSET', 'utf8mb4');

// Sambungkan ke database
function dbConnect(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;border:1px solid #dc2626;border-radius:8px;margin:20px;">
                <h3 style="color:#dc2626;margin:0 0 10px">Ralat Sambungan Database</h3>
                <p style="margin:0;color:#7f1d1d">Sila semak tetapan database dalam <code>config/database.php</code></p>
                <p style="margin:5px 0 0;color:#991b1b;font-size:0.9em">Ralat: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }
    return $pdo;
}

// Shortcut: jalankan query
function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = dbConnect()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Ambil satu baris
function dbFetch(string $sql, array $params = []): array|false {
    return dbQuery($sql, $params)->fetch();
}

// Ambil semua baris
function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

// Ambil nilai tunggal
function dbValue(string $sql, array $params = []): mixed {
    $row = dbFetch($sql, $params);
    return $row ? reset($row) : null;
}

// Insert dan return ID
function dbInsert(string $table, array $data): string {
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
    $vals = implode(',', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO `$table` ($cols) VALUES ($vals)", array_values($data));
    return dbConnect()->lastInsertId();
}

// Update
function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int {
    $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    $stmt = dbQuery("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
    return $stmt->rowCount();
}
