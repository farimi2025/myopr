<?php
// ============================================================
// FUNGSI PEMBANTU UMUM
// ============================================================

// Sanitize input
function clean(mixed $val): string {
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

// Redirect
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// Flash message (simpan & papar sekali)
function setFlash(string $jenis, string $mesej): void {
    $_SESSION['flash'] = ['jenis' => $jenis, 'mesej' => $mesej];
}

function getFlash(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

// Papar flash message sebagai Bootstrap alert
function showFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $icon = match($f['jenis']) {
        'success' => 'check-circle-fill',
        'danger'  => 'x-circle-fill',
        'warning' => 'exclamation-triangle-fill',
        default   => 'info-circle-fill'
    };
    return '<div class="alert alert-' . $f['jenis'] . ' alert-dismissible d-flex align-items-center mb-3 fade show" role="alert">
        <i class="bi bi-' . $icon . ' me-2 fs-5"></i>
        <div>' . clean($f['mesej']) . '</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Upload gambar
function uploadGambar(array $file, string $subfolder = 'gambar'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['berjaya' => false, 'mesej' => 'Ralat semasa muat naik fail.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['berjaya' => false, 'mesej' => 'Saiz fail melebihi 5MB.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['berjaya' => false, 'mesej' => 'Jenis fail tidak dibenarkan. Hanya JPG, PNG, WEBP.'];
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg', 'image/png' => 'png',
        'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg'
    };
    $namaFail = uniqid('img_', true) . '.' . $ext;
    $dest = UPLOAD_PATH . $subfolder . '/' . $namaFail;

    if (!is_dir(UPLOAD_PATH . $subfolder)) {
        mkdir(UPLOAD_PATH . $subfolder, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['berjaya' => false, 'mesej' => 'Gagal menyimpan fail.'];
    }

    // Resize gambar jika terlalu besar
    resizeImage($dest, 1200, 900);

    return ['berjaya' => true, 'fail' => 'assets/uploads/' . $subfolder . '/' . $namaFail];
}

// Resize gambar menggunakan GD
function resizeImage(string $path, int $maxW, int $maxH): void {
    if (!extension_loaded('gd')) return;
    [$w, $h, $type] = getimagesize($path);
    if ($w <= $maxW && $h <= $maxH) return;

    $ratio = min($maxW/$w, $maxH/$h);
    $newW = (int)($w * $ratio);
    $newH = (int)($h * $ratio);

    $src = match($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => imagecreatefrompng($path),
        IMAGETYPE_WEBP => imagecreatefromwebp($path),
        default => null
    };
    if (!$src) return;

    $dst = imagecreatetruecolor($newW, $newH);
    if ($type === IMAGETYPE_PNG) { imagealphablending($dst, false); imagesavealpha($dst, true); }
    imagecopyresampled($dst, $src, 0,0,0,0, $newW,$newH,$w,$h);

    match($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $path, 85),
        IMAGETYPE_PNG  => imagepng($dst, $path, 7),
        IMAGETYPE_WEBP => imagewebp($dst, $path, 85),
        default => null
    };
    imagedestroy($src);
    imagedestroy($dst);
}

// Padam fail
function padamFail(string $path): void {
    $fullPath = ROOT_PATH . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) unlink($fullPath);
}

// Nombor rujukan auto
function janaNoRujukan(string $prefix, string $jadual, string $lajur = 'no_rujukan'): string {
    $tahun = date('Y');
    $count = (int)dbValue("SELECT COUNT(*) FROM `$jadual` WHERE YEAR(created_at)=?", [$tahun]);
    return $prefix . '/' . $tahun . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// Pagination
function pagination(int $total, int $page, int $perPage, string $baseUrl): string {
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    // Sebelum
    $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">
        <a class="page-link" href="' . $baseUrl . '&page=' . ($page - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';

    // Nombor halaman
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    if ($end < $totalPages) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';

    // Selepas
    $html .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">
        <a class="page-link" href="' . $baseUrl . '&page=' . ($page + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// Badge status
function badgeStatus(string $status): string {
    $map = [
        'aktif'     => ['success', 'Aktif'],
        'arkib'     => ['secondary', 'Arkib'],
        'draf'      => ['warning', 'Draf'],
        'berpindah' => ['info', 'Berpindah'],
        'berhenti'  => ['danger', 'Berhenti'],
        'tamat'     => ['dark', 'Tamat'],
    ];
    $info = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $info[0] . '">' . $info[1] . '</span>';
}

// Papar gambar atau placeholder
function gambarUrl(?string $path, string $default = 'assets/images/no-photo.png'): string {
    if ($path && file_exists(ROOT_PATH . '/' . $path)) {
        return BASE_URL . '/' . $path;
    }
    return BASE_URL . '/' . $default;
}

// Escape untuk JavaScript
function jsEscape(string $str): string {
    return addslashes(htmlspecialchars_decode($str));
}

// CSRF token
function csrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrfToken(), $token);
}

// Ambil senarai kelas untuk dropdown
function getSenaraiKelas(int $tahun = 0): array {
    if (!$tahun) $tahun = (int)TAHUN_SEMASA;
    return dbFetchAll("SELECT id, CONCAT(tingkatan, ' ', nama_kelas) AS label
        FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas", [$tahun]);
}

// Ambil senarai guru untuk dropdown
function getSenaraiGuru(): array {
    return dbFetchAll("SELECT id, nama FROM guru WHERE status='aktif' ORDER BY nama");
}

// Ambil senarai subjek
function getSenaraiSubjek(): array {
    return dbFetchAll("SELECT id, CONCAT(kod,' - ',nama) AS label FROM subjek WHERE status='aktif' ORDER BY nama");
}
