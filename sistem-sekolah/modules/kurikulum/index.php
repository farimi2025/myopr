<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$tahun  = (int)($_GET['tahun'] ?? 2017);
$matP   = clean($_GET['mata_pelajaran'] ?? '');
$tingk  = clean($_GET['tingkatan'] ?? '');
$carian = clean($_GET['carian'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($matP)  { $where[] = 'mata_pelajaran=?'; $params[] = $matP; }
if ($tingk) { $where[] = 'tingkatan=?'; $params[] = $tingk; }
if ($carian){ $where[] = '(standard_kandungan LIKE ? OR standard_pembelajaran LIKE ? OR tajuk_unit LIKE ?)';
              $params = array_merge($params, ["%$carian%","%$carian%","%$carian%"]); }

$whereStr = implode(' AND ', $where);
$total  = (int)dbValue("SELECT COUNT(*) FROM kurikulum WHERE $whereStr", $params);
$offset = ($page - 1) * ROWS_PER_PAGE;
$rows   = dbFetchAll("SELECT * FROM kurikulum WHERE $whereStr ORDER BY mata_pelajaran, tingkatan, standard_kandungan_kod LIMIT ".ROWS_PER_PAGE." OFFSET $offset", $params);

// Dropdown mata pelajaran
$mataPelajaranList = dbFetchAll("SELECT DISTINCT mata_pelajaran FROM kurikulum ORDER BY mata_pelajaran");

$pageTitle = 'DSKP 2017 — Standard Kurikulum';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-book-fill text-primary me-2"></i>Data DSKP 2017</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=BASE_URL?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">DSKP 2017</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?=BASE_URL?>/modules/kurikulum/tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah Standard</a>
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImportCSV">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV</button>
    </div>
</div>

<?= showFlash() ?>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-semibold">Mata Pelajaran</label>
                <select name="mata_pelajaran" class="form-select form-select-sm select2">
                    <option value="">Semua</option>
                    <?php foreach ($mataPelajaranList as $m): ?>
                    <option value="<?=clean($m['mata_pelajaran'])?>" <?=$m['mata_pelajaran']==$matP?'selected':''?>>
                        <?=clean($m['mata_pelajaran'])?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small fw-semibold">Tingkatan</label>
                <select name="tingkatan" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (['1','2','3','4','5','6','PPKI'] as $t): ?>
                    <option value="<?=$t?>" <?=$t==$tingk?'selected':''?>><?=$t?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1 small fw-semibold">Carian</label>
                <input type="search" name="carian" class="form-control form-control-sm"
                    placeholder="Cari standard kandungan / pembelajaran..." value="<?=$carian?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Tapis</button>
                <a href="?" class="btn btn-outline-secondary btn-sm">Set Semula</a>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="d-flex gap-3 mb-3 flex-wrap">
    <span class="badge bg-primary fs-tiny px-3 py-2">Jumlah: <?=number_format($total)?> rekod</span>
    <?php if ($matP): ?><span class="badge bg-info text-dark px-3 py-2"><?=$matP?></span><?php endif; ?>
    <?php if ($tingk): ?><span class="badge bg-secondary px-3 py-2">Tingkatan <?=$tingk?></span><?php endif; ?>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>Senarai Standard Kurikulum DSKP Semakan 2017</span>
        <a href="<?=BASE_URL?>/export/pdf.php?modul=kurikulum&mata_pelajaran=<?=urlencode($matP)?>&tingkatan=<?=$tingk?>"
           class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>PDF</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Mata Pelajaran</th>
                    <th>Tingkatan</th>
                    <th>Kod SK</th>
                    <th style="max-width:250px">Standard Kandungan</th>
                    <th>Kod SP</th>
                    <th style="max-width:250px">Standard Pembelajaran</th>
                    <th>SP</th>
                    <th class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="fw-semibold"><?=clean($r['mata_pelajaran'])?></td>
                    <td><span class="badge bg-primary"><?=clean($r['tingkatan']??'-')?></span></td>
                    <td><code><?=clean($r['standard_kandungan_kod']??'-')?></code></td>
                    <td style="max-width:250px">
                        <span title="<?=clean($r['standard_kandungan']??'')?>" data-bs-toggle="tooltip">
                            <?=clean(mb_substr($r['standard_kandungan']??'',0,80))?>
                            <?=strlen($r['standard_kandungan']??'')>80?'...':''?>
                        </span>
                    </td>
                    <td><code><?=clean($r['standard_pembelajaran_kod']??'-')?></code></td>
                    <td style="max-width:250px">
                        <span title="<?=clean($r['standard_pembelajaran']??'')?>" data-bs-toggle="tooltip">
                            <?=clean(mb_substr($r['standard_pembelajaran']??'',0,80))?>
                            <?=strlen($r['standard_pembelajaran']??'')>80?'...':''?>
                        </span>
                    </td>
                    <td>
                        <?php if ($r['standard_prestasi']): ?>
                        <span class="badge bg-info text-dark"><?=clean($r['standard_prestasi'])?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="tambah.php?clone=<?=$r['id']?>" class="btn btn-outline-secondary"
                               data-bs-toggle="tooltip" title="Klon"><i class="bi bi-copy"></i></a>
                            <a href="edit.php?id=<?=$r['id']?>" class="btn btn-outline-warning">
                                <i class="bi bi-pencil"></i></a>
                            <a href="aksi.php?action=padam&id=<?=$r['id']?>&csrf_token=<?=csrfToken()?>"
                               class="btn btn-outline-danger btn-aksi-kurikulum"
                               data-confirm="Padam standard: <?=clean(mb_substr($r['standard_kandungan_kod']??'',0,20))?>?">
                                <i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>Tiada rekod dijumpai
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > ROWS_PER_PAGE): ?>
    <div class="card-footer">
        <?= pagination($total, $page, ROWS_PER_PAGE, '?mata_pelajaran='.urlencode($matP).'&tingkatan='.$tingk.'&carian='.urlencode($carian)) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="modalImportCSV" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import DSKP dari CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Format CSV yang diperlukan:</h6>
                    <p class="mb-1">Baris pertama mesti mengandungi header berikut:</p>
                    <code class="d-block bg-light p-2 rounded mt-2" style="font-size:0.75rem;">
                        mata_pelajaran, tingkatan, bahagian, tema, unit, tajuk_unit,<br>
                        standard_kandungan_kod, standard_kandungan,<br>
                        standard_pembelajaran_kod, standard_pembelajaran,<br>
                        standard_prestasi, huraian_sp, catatan, tahun_semakan
                    </code>
                </div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Fungsi import CSV akan dilaksanakan oleh pentadbir sistem. Sila hubungi pasukan teknikal untuk import data DSKP secara pukal melalui skrip import yang disediakan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php
$headerBtn = '<button class="btn btn-sm btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#modalImportCSV"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV</button>';
$extraScript = <<<JS
<script>
$(function(){
    $('.btn-aksi-kurikulum').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('confirm') || 'Padam rekod ini?';
        Swal.fire({ title: 'Sahkan Padam', text: msg, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, padam',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });
    // Tooltip
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
