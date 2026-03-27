<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','ID tidak sah'); redirect(BASE_URL.'/modules/opr/index.php'); }

$opr = dbFetch("SELECT o.*, g.nama AS nama_guru FROM opr o
    LEFT JOIN guru g ON g.id=o.guru_id WHERE o.id=?", [$id]);
if (!$opr) { setFlash('danger','Rekod tidak ditemui'); redirect(BASE_URL.'/modules/opr/index.php'); }

$namaSekolah = getSetting('nama_sekolah');
$alamatSekolah = getSetting('alamat_sekolah');

$pageTitle = 'OPR: ' . $opr['tajuk'];
$extraHead = '<style>
@media print {
    .no-print { display: none !important; }
    .opr-document { padding: 0; margin: 0; }
    body { background: white; }
    #layoutSidenav_nav, .sb-topnav, footer, .page-header, .breadcrumb { display: none !important; }
    #layoutSidenav_content { margin: 0 !important; }
    .container-fluid { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #e5e7eb !important; page-break-inside: avoid; }
}
.opr-document { max-width: 900px; margin: 0 auto; }
.opr-header { text-align: center; padding: 1.5rem; border-bottom: 3px double #2563eb; margin-bottom: 1.5rem; }
.opr-header h2 { color: #1d4ed8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.info-table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.info-table td { padding: .4rem .6rem; font-size: .875rem; }
.info-table td:first-child { font-weight: 600; width: 35%; color: #374151; }
.section-title { font-weight: 700; color: #1d4ed8; border-left: 4px solid #2563eb; padding-left: .6rem;
    margin: 1.25rem 0 .5rem; font-size: .95rem; text-transform: uppercase; letter-spacing: .03em; }
.photo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: .75rem 0; }
.photo-item { border: 1px solid #e2e8f0; border-radius: .5rem; overflow: hidden; }
.photo-item img { width: 100%; height: 180px; object-fit: cover; display: block; }
.photo-item .caption { padding: .4rem; font-size: .8rem; text-align: center; background: #f8fafc; color: #555; }
.sign-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; }
.sign-box { text-align: center; }
.sign-line { border-top: 1px solid #374151; margin-top: 60px; padding-top: .5rem; font-size: .875rem; }
</style>';

include __DIR__ . '/../../includes/header.php';
?>
<?= showFlash() ?>

<!-- Action buttons -->
<div class="d-flex gap-2 mb-3 no-print flex-wrap">
    <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Cetak</button>
    <a href="<?=BASE_URL?>/export/pdf.php?modul=opr&id=<?=$id?>" target="_blank" class="btn btn-outline-danger">
        <i class="bi bi-file-pdf me-1"></i>Muat Turun PDF</a>
    <a href="<?=BASE_URL?>/export/docx.php?modul=opr&id=<?=$id?>" class="btn btn-outline-primary">
        <i class="bi bi-file-word me-1"></i>Muat Turun DOCX</a>
    <a href="edit.php?id=<?=$id?>" class="btn btn-warning">
        <i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="index.php" class="btn btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left me-1"></i>Kembali</a>
    <?= badgeStatus($opr['status']) ?>
</div>

<!-- OPR DOCUMENT -->
<div class="opr-document">
    <div class="card">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="opr-header">
                <div class="fw-semibold" style="font-size:1.1rem;color:#1d4ed8"><?=clean($namaSekolah)?></div>
                <div class="text-muted small mb-2"><?=clean($alamatSekolah)?></div>
                <h2>Laporan Ringkas Aktiviti<br><small style="font-size:.85em">One Page Report (OPR)</small></h2>
            </div>

            <!-- Info Grid -->
            <table class="info-table">
                <tr>
                    <td>Tajuk Aktiviti / Program</td>
                    <td><?=clean($opr['tajuk'])?></td>
                    <td class="fw-semibold" style="width:20%;color:#374151">Tarikh</td>
                    <td><?=formatTarikh($opr['tarikh_aktiviti'])?></td>
                </tr>
                <tr>
                    <td>Kategori</td>
                    <td><span class="badge bg-info text-dark"><?=clean($opr['kategori'])?></span></td>
                    <td class="fw-semibold">No. Rujukan</td>
                    <td><?=clean($opr['no_rujukan']??'-')?></td>
                </tr>
                <tr>
                    <td>Tempat</td>
                    <td><?=clean($opr['tempat']??'-')?></td>
                    <td class="fw-semibold">Pegawai Bertanggungjawab</td>
                    <td><?=clean($opr['pegawai_bertanggungjawab']??'-')?></td>
                </tr>
                <tr>
                    <td>Disediakan Oleh</td>
                    <td colspan="3"><?=clean($opr['nama_guru']??'-')?></td>
                </tr>
            </table>

            <?php if ($opr['penerangan_umum']): ?>
            <div class="section-title">Laporan Ringkas</div>
            <p style="font-size:.9rem;line-height:1.8;text-align:justify"><?=nl2br(clean($opr['penerangan_umum']))?></p>
            <?php endif; ?>

            <?php if ($opr['penerangan_lanjut']): ?>
            <div class="section-title">Penerangan Lanjut</div>
            <p style="font-size:.9rem;line-height:1.8;text-align:justify"><?=nl2br(clean($opr['penerangan_lanjut']))?></p>
            <?php endif; ?>

            <?php if ($opr['impak']): ?>
            <div class="section-title">Impak Aktiviti</div>
            <p style="font-size:.9rem;line-height:1.8;text-align:justify"><?=nl2br(clean($opr['impak']))?></p>
            <?php endif; ?>

            <?php if ($opr['saranan']): ?>
            <div class="section-title">Saranan &amp; Cadangan Penambahbaikan</div>
            <p style="font-size:.9rem;line-height:1.8"><?=nl2br(clean($opr['saranan']))?></p>
            <?php endif; ?>

            <!-- Photos -->
            <?php $hasPhoto = $opr['gambar1'] || $opr['gambar2'] || $opr['gambar3']; ?>
            <?php if ($hasPhoto): ?>
            <div class="section-title">Gambar Aktiviti</div>
            <div class="photo-grid">
                <?php foreach ([1,2,3] as $n): ?>
                <div class="photo-item">
                    <?php $img = $opr["gambar$n"]; ?>
                    <?php if ($img && file_exists(ROOT_PATH.'/'.$img)): ?>
                    <img src="<?=BASE_URL.'/'.$img?>" alt="Gambar <?=$n?>">
                    <?php else: ?>
                    <div style="height:180px;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-image text-muted fs-1 opacity-25"></i>
                    </div>
                    <?php endif; ?>
                    <div class="caption"><?=clean($opr["gambar{$n}_kapsyen"] ?: 'Gambar '.$n)?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Signature -->
            <div class="sign-grid no-print" style="margin-top:2rem">
                <div class="sign-box">
                    <div class="sign-line">
                        Disediakan Oleh<br>
                        <strong><?=clean($opr['pegawai_bertanggungjawab']??'_______________')?></strong><br>
                        <small><?=formatTarikh($opr['tarikh_aktiviti'])?></small>
                    </div>
                </div>
                <div class="sign-box">
                    <div class="sign-line">
                        Disahkan Oleh<br>
                        <strong>Pengetua / Guru Besar</strong><br>
                        <small>Tarikh: _______________</small>
                    </div>
                </div>
            </div>
            <div class="sign-grid d-none d-print-grid" style="display:none">
                <div class="sign-box"><div class="sign-line">Disediakan Oleh<br>
                    <strong><?=clean($opr['pegawai_bertanggungjawab']??'_______________')?></strong></div></div>
                <div class="sign-box"><div class="sign-line">Disahkan Oleh<br>
                    <strong>Pengetua / Guru Besar</strong></div></div>
            </div>

            <div class="text-center mt-3 text-muted" style="font-size:.75rem;border-top:1px solid #e5e7eb;padding-top:.5rem">
                Laporan ini dijana oleh Sistem Pengurusan Sekolah pada <?=date('d/m/Y H:i')?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
