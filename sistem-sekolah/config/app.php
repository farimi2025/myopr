<?php
// ============================================================
// KONFIGURASI APLIKASI
// ============================================================

// Path
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', getBaseUrl());

// Sesi
define('SESSION_NAME', 'sekolah_session');
define('SESSION_LIFETIME', 7200); // 2 jam

// Upload
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination
define('ROWS_PER_PAGE', 25);

// Autosave
define('AUTOSAVE_INTERVAL', 30000); // 30 saat (ms)

// Tahun semasa
define('TAHUN_SEMASA', date('Y'));

// Warna tema gred
define('GRED_WARNA', [
    'A+'  => '#15803d', 'A'   => '#16a34a', 'A-'  => '#22c55e',
    'B+'  => '#0284c7', 'B'   => '#0ea5e9', 'B-'  => '#38bdf8',
    'C+'  => '#d97706', 'C'   => '#f59e0b', 'C-'  => '#fbbf24',
    'D'   => '#ea580c', 'E'   => '#dc2626', 'G'   => '#7f1d1d',
    'TH'  => '#6b7280', '-'   => '#9ca3af',
]);

// Pengiraan gred (SPM style)
function kiraMGred(float $markah): array {
    if ($markah >= 90) return ['gred' => 'A+', 'nilai' => 1];
    if ($markah >= 80) return ['gred' => 'A',  'nilai' => 2];
    if ($markah >= 70) return ['gred' => 'A-', 'nilai' => 3];
    if ($markah >= 65) return ['gred' => 'B+', 'nilai' => 4];
    if ($markah >= 60) return ['gred' => 'B',  'nilai' => 5];
    if ($markah >= 55) return ['gred' => 'B-', 'nilai' => 6];
    if ($markah >= 50) return ['gred' => 'C+', 'nilai' => 7];
    if ($markah >= 45) return ['gred' => 'C',  'nilai' => 8];
    if ($markah >= 40) return ['gred' => 'C-', 'nilai' => 9];
    if ($markah >= 35) return ['gred' => 'D',  'nilai' => 10];
    if ($markah >= 30) return ['gred' => 'E',  'nilai' => 11];
    return ['gred' => 'G', 'nilai' => 12];
}

// Dapatkan base URL secara automatik
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    // Normalize untuk sistem-sekolah folder
    $path = rtrim(str_replace('/sistem-sekolah', '', $script), '/') . '/sistem-sekolah';
    return $protocol . '://' . $host . $path;
}

// Format tarikh Melayu
function formatTarikh(string $tarikh, string $format = 'd M Y'): string {
    if (!$tarikh || $tarikh === '0000-00-00') return '-';
    $bulan = [
        '01'=>'Jan','02'=>'Feb','03'=>'Mac','04'=>'Apr',
        '05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Ogos',
        '09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Dis'
    ];
    $d = date_create($tarikh);
    if (!$d) return $tarikh;
    $result = date($format, $d->getTimestamp());
    foreach ($bulan as $en => $ms) {
        $result = str_replace(date('M', mktime(0,0,0,(int)$en,1)), $ms, $result);
    }
    return $result;
}
