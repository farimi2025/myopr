<?php
// ============================================================
// KAD MURID — Cetak 2 kad per muka surat A4
// Params: ?murid_id=X  ATAU  ?kelas_id=X&tahun=Y
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$muridId = (int)($_GET['murid_id'] ?? 0);
$kelasId = (int)($_GET['kelas_id'] ?? 0);
$tahun   = (int)($_GET['tahun']    ?? TAHUN_SEMASA);

$namaSekolah  = getSetting('nama_sekolah');
$alamatSekolah = getSetting('alamat_sekolah');
$logoSekolah  = getSetting('logo_sekolah');

// ── Ambil senarai murid ────────────────────────────────────────────────────
if ($muridId > 0) {
    $muridList = dbFetchAll(
        "SELECT m.*, k.nama_kelas, k.tingkatan
         FROM murid m
         LEFT JOIN kelas k ON k.id = m.kelas_id
         WHERE m.id = ? LIMIT 1",
        [$muridId]
    );
} elseif ($kelasId > 0) {
    $muridList = dbFetchAll(
        "SELECT m.*, k.nama_kelas, k.tingkatan
         FROM murid m
         LEFT JOIN kelas k ON k.id = m.kelas_id
         WHERE m.kelas_id = ? AND m.tahun = ? AND m.status = 'aktif'
         ORDER BY m.nama",
        [$kelasId, $tahun]
    );
} else {
    echo '<p style="padding:20px;color:red">Parameter tidak sah. Sila berikan murid_id atau kelas_id.</p>';
    exit;
}

if (empty($muridList)) {
    echo '<p style="padding:20px;color:red">Tiada rekod murid dijumpai.</p>';
    exit;
}

// Log aktiviti
$logKet = $muridId > 0
    ? 'Cetak kad murid ID ' . $muridId
    : 'Cetak kad murid kelas ID ' . $kelasId . ' tahun ' . $tahun;
logAktiviti('cetak', 'kad_murid', $muridId ?: $kelasId, $logKet);

?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Kad Murid — <?= htmlspecialchars($namaSekolah) ?></title>
<style>
    @page { size: A4; margin: 10mm; }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: "Segoe UI", Arial, sans-serif;
        font-size: 9pt;
        color: #111;
        background: #fff;
    }

    .btn-print {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 12pt;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(37,99,235,.4);
        z-index: 999;
    }

    @media print {
        .no-print { display: none !important; }
        body { margin: 0; }
    }

    /* Grid: 2 kad per baris */
    .kad-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
    }

    /* Setiap kad */
    .kad {
        border: 2px solid #1d4ed8;
        border-radius: 8px;
        padding: 10px;
        page-break-inside: avoid;
        min-height: 120px;
        background: #fff;
        overflow: hidden;
    }

    /* Header kad biru */
    .kad-header {
        background: #1d4ed8;
        color: #fff;
        padding: 6px 10px;
        border-radius: 4px 4px 0 0;
        margin: -10px -10px 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .kad-header-logo {
        width: 32px;
        height: 32px;
        object-fit: contain;
        background: #fff;
        border-radius: 3px;
        flex-shrink: 0;
    }

    .kad-header-logo-placeholder {
        width: 32px;
        height: 32px;
        background: rgba(255,255,255,0.25);
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .kad-header-text {
        flex: 1;
        line-height: 1.2;
    }

    .kad-header-text strong {
        font-size: 9pt;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .kad-header-text span {
        font-size: 7.5pt;
        opacity: 0.85;
    }

    /* Badan kad: foto + info */
    .kad-body {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    /* Foto murid */
    .kad-foto {
        width: 70px;
        height: 80px;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 4px;
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .kad-foto-placeholder {
        width: 70px;
        height: 80px;
        border: 1px solid #ddd;
        border-radius: 4px;
        flex-shrink: 0;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #93c5fd;
        font-size: 28pt;
    }

    /* Maklumat murid */
    .kad-info {
        flex: 1;
        font-size: 9pt;
    }

    .kad-nama {
        font-size: 10pt;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .kad-baris {
        margin-bottom: 2px;
        line-height: 1.4;
        color: #374151;
    }

    .kad-baris .label {
        color: #6b7280;
        font-size: 8pt;
    }

    /* Footer hubungi */
    .kad-footer {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed #bfdbfe;
        font-size: 8pt;
        color: #374151;
    }

    .kad-footer .telefon {
        color: #1d4ed8;
        font-weight: 600;
    }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>

<div class="kad-grid">
<?php foreach ($muridList as $murid):
    $fotoUrl = gambarUrl($murid['gambar'] ?? null);
    $fotoFail = $murid['gambar'] ?? null;
    $adaFoto  = $fotoFail && file_exists(ROOT_PATH . '/' . $fotoFail);

    $jantina  = $murid['jantina'] === 'L' ? 'Lelaki' : 'Perempuan';
    $kelas    = trim(($murid['tingkatan'] ?? '') . ' ' . ($murid['nama_kelas'] ?? ''));
    $telefon  = $murid['telefon_ibu_bapa'] ?? '';
    $hubungan = $murid['hubungan'] ?? 'Ibu Bapa';
    $sesi     = $murid['tahun'] ?? $tahun;
?>
<div class="kad">
    <!-- Header -->
    <div class="kad-header">
        <?php if ($logoSekolah && file_exists(ROOT_PATH . '/' . $logoSekolah)): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($logoSekolah) ?>" class="kad-header-logo" alt="Logo">
        <?php else: ?>
            <div class="kad-header-logo-placeholder">&#127891;</div>
        <?php endif; ?>
        <div class="kad-header-text">
            <strong title="<?= htmlspecialchars($namaSekolah) ?>"><?= htmlspecialchars($namaSekolah) ?></strong>
            <span>Kad Murid</span>
        </div>
    </div>

    <!-- Badan -->
    <div class="kad-body">
        <!-- Foto -->
        <?php if ($adaFoto): ?>
            <img src="<?= htmlspecialchars($fotoUrl) ?>" class="kad-foto" alt="Foto <?= htmlspecialchars($murid['nama']) ?>">
        <?php else: ?>
            <div class="kad-foto-placeholder">&#128100;</div>
        <?php endif; ?>

        <!-- Maklumat -->
        <div class="kad-info">
            <div class="kad-nama"><?= htmlspecialchars($murid['nama']) ?></div>

            <?php if ($murid['no_ic']): ?>
            <div class="kad-baris">
                <span class="label">No. IC: </span><?= htmlspecialchars($murid['no_ic']) ?>
            </div>
            <?php endif; ?>

            <?php if ($kelas): ?>
            <div class="kad-baris">
                <span class="label">Kelas: </span>
                <?php if ($murid['tingkatan']): ?>Tingkatan <?php endif; ?><?= htmlspecialchars($kelas) ?>
            </div>
            <?php endif; ?>

            <div class="kad-baris">
                <span class="label">No. Pendaftaran: </span><?= htmlspecialchars($murid['no_pendaftaran']) ?>
            </div>

            <div class="kad-baris">
                <span class="label">Sesi: </span><?= htmlspecialchars((string)$sesi) ?>
                &nbsp;&nbsp;
                <span class="label">Jantina: </span><?= htmlspecialchars($jantina) ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php if ($telefon): ?>
    <div class="kad-footer">
        Hubungi: <span class="telefon"><?= htmlspecialchars($telefon) ?></span>
        (<?= htmlspecialchars($hubungan) ?>)
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

</body>
</html>
