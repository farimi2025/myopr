<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$kelasId = (int)($_GET['kelas_id'] ?? 0);
if (!$kelasId) {
    setFlash('danger', 'ID kelas tidak sah.');
    redirect(BASE_URL . '/modules/kelas/index.php');
}

$kelas = dbFetch("
    SELECT k.*, g.nama AS nama_guru_kelas
    FROM kelas k
    LEFT JOIN guru g ON g.id = k.guru_kelas_id
    WHERE k.id=?
", [$kelasId]);

if (!$kelas) {
    setFlash('danger', 'Rekod kelas tidak dijumpai.');
    redirect(BASE_URL . '/modules/kelas/index.php');
}

$pageTitle   = 'Guru & Subjek - ' . $kelas['nama_kelas'];
$errors      = [];
$old         = [];
$senaraiGuru = getSenaraiGuru();
$senaraiSubjek = getSenaraiSubjek();

// Handle add assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tugasan'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah.');
        redirect(BASE_URL . '/modules/kelas/guru_subjek.php?kelas_id=' . $kelasId);
    }

    $old = $_POST;

    $guruId       = (int)($_POST['guru_id'] ?? 0);
    $subjekId     = (int)($_POST['subjek_id'] ?? 0);
    $namaSubjek   = clean($_POST['nama_subjek'] ?? '');
    $waktuSeminggu = (int)($_POST['waktu_seminggu'] ?? 0);
    $tahun        = (int)($_POST['tahun'] ?? TAHUN_SEMASA);
    $status       = 'aktif';

    if (!$guruId) $errors['guru_id'] = 'Sila pilih guru.';
    if (empty($namaSubjek)) $errors['nama_subjek'] = 'Nama subjek diperlukan.';
    if ($waktuSeminggu < 1) $errors['waktu_seminggu'] = 'Waktu seminggu mestilah sekurang-kurangnya 1.';

    // Check duplicate
    if ($guruId && $namaSubjek && $tahun) {
        $cek = dbValue(
            "SELECT id FROM guru_kelas_subjek WHERE guru_id=? AND kelas_id=? AND nama_subjek=? AND tahun=?",
            [$guruId, $kelasId, $namaSubjek, $tahun]
        );
        if ($cek) $errors['nama_subjek'] = 'Tugasan ini sudah wujud untuk tahun yang sama.';
    }

    if (empty($errors)) {
        dbInsert('guru_kelas_subjek', [
            'guru_id'        => $guruId,
            'kelas_id'       => $kelasId,
            'subjek_id'      => $subjekId ?: null,
            'nama_subjek'    => $namaSubjek,
            'waktu_seminggu' => $waktuSeminggu,
            'tahun'          => $tahun,
            'status'         => $status,
        ]);
        logAktiviti('tambah', 'guru_kelas_subjek', $kelasId, "Tambah tugasan: guru $guruId, subjek $namaSubjek di kelas {$kelas['nama_kelas']}");
        setFlash('success', "Tugasan berjaya ditambah.");
        redirect(BASE_URL . '/modules/kelas/guru_subjek.php?kelas_id=' . $kelasId);
    }
}

// Get current assignments
$tugasan = dbFetchAll("
    SELECT gks.*, g.nama AS nama_guru, g.no_pekerja,
           s.kod AS subjek_kod
    FROM guru_kelas_subjek gks
    JOIN guru g ON g.id = gks.guru_id
    LEFT JOIN subjek s ON s.id = gks.subjek_id
    WHERE gks.kelas_id = ?
    ORDER BY gks.tahun DESC, g.nama, gks.nama_subjek
", [$kelasId]);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-journals me-2 text-info"></i>Urus Guru & Subjek Kelas</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Kelas</a></li>
                <li class="breadcrumb-item active"><?= clean($kelas['nama_kelas']) ?> - Guru & Subjek</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= $kelasId ?>" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Kelas
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?= showFlash() ?>

<!-- Class Info Banner -->
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="text-muted small">Nama Kelas</div>
                <div class="fw-bold fs-5 text-primary"><?= clean($kelas['nama_kelas']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Tingkatan</div>
                <div class="fw-semibold"><?= clean($kelas['tingkatan']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Aliran</div>
                <div class="fw-semibold"><?= clean($kelas['aliran'] ?: '-') ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Guru Kelas</div>
                <div class="fw-semibold"><?= clean($kelas['nama_guru_kelas'] ?: 'Tiada') ?></div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Tahun</div>
                <div class="fw-semibold"><?= clean($kelas['tahun']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Sila betulkan ralat berikut:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Current Assignments -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check me-2 text-primary"></i>Senarai Tugasan Semasa</h6>
                <span class="badge bg-primary"><?= count($tugasan) ?> rekod</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tableTugasan" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">#</th>
                                <th>Guru</th>
                                <th>Subjek</th>
                                <th class="text-center">Waktu/Mng</th>
                                <th class="text-center">Tahun</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tugasan)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada tugasan ditetapkan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($tugasan as $i => $t): ?>
                            <tr>
                                <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= clean($t['nama_guru']) ?></div>
                                    <div class="text-muted small"><?= clean($t['no_pekerja'] ?: '') ?></div>
                                </td>
                                <td>
                                    <?php if ($t['subjek_kod']): ?>
                                    <span class="badge bg-light text-dark border me-1"><?= clean($t['subjek_kod']) ?></span>
                                    <?php endif; ?>
                                    <?= clean($t['nama_subjek']) ?>
                                </td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?= $t['waktu_seminggu'] ?></span></td>
                                <td class="text-center"><?= clean($t['tahun']) ?></td>
                                <td class="text-center">
                                    <a href="aksi.php?tindakan=padam_tugasan&id=<?= $t['id'] ?>&kelas_id=<?= $kelasId ?>"
                                       class="btn btn-sm btn-outline-danger btn-aksi"
                                       data-confirm="Padam tugasan <?= clean($t['nama_subjek']) ?> untuk <?= clean($t['nama_guru']) ?>?"
                                       title="Padam">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($tugasan)): ?>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-semibold px-3">Jumlah Waktu:</td>
                                <td class="text-center fw-bold text-primary"><?= array_sum(array_column($tugasan, 'waktu_seminggu')) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Assignment Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Tugasan</h6>
            </div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="tambah_tugasan" value="1">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Guru <span class="text-danger">*</span></label>
                        <select name="guru_id" class="form-select select2-guru <?= isset($errors['guru_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($senaraiGuru as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= ($old['guru_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                                <?= clean($g['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['guru_id'])): ?><div class="invalid-feedback d-block"><?= $errors['guru_id'] ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subjek (daripada senarai)</label>
                        <select name="subjek_id" class="form-select select2-subjek" id="pilihSubjek">
                            <option value="">-- Pilih Subjek (pilihan) --</option>
                            <?php foreach ($senaraiSubjek as $s): ?>
                            <option value="<?= $s['id'] ?>" data-nama="<?= clean($s['nama']) ?>"
                                    <?= ($old['subjek_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                <?= clean($s['kod'] ? $s['kod'] . ' - ' . $s['nama'] : $s['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pilih untuk isi nama subjek secara automatik.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Subjek <span class="text-danger">*</span></label>
                        <input type="text" name="nama_subjek" id="namaSubjek"
                               class="form-control <?= isset($errors['nama_subjek']) ? 'is-invalid' : '' ?>"
                               value="<?= clean($old['nama_subjek'] ?? '') ?>"
                               placeholder="Contoh: Matematik">
                        <?php if (isset($errors['nama_subjek'])): ?><div class="invalid-feedback"><?= $errors['nama_subjek'] ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Waktu Seminggu <span class="text-danger">*</span></label>
                        <input type="number" name="waktu_seminggu"
                               class="form-control <?= isset($errors['waktu_seminggu']) ? 'is-invalid' : '' ?>"
                               value="<?= clean($old['waktu_seminggu'] ?? '5') ?>" min="1" max="40">
                        <?php if (isset($errors['waktu_seminggu'])): ?><div class="invalid-feedback"><?= $errors['waktu_seminggu'] ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tahun</label>
                        <input type="number" name="tahun" class="form-control"
                               value="<?= clean($old['tahun'] ?? $kelas['tahun']) ?>" min="2000" max="2100">
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Tugasan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('.select2-guru').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Guru --', allowClear: true });
    $('.select2-subjek').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Subjek (pilihan) --', allowClear: true });

    // Auto-fill nama subjek when subjek is selected
    $('#pilihSubjek').on('change', function(){
        const selected = $(this).find('option:selected');
        const nama = selected.data('nama') || '';
        if (nama) {
            $('#namaSubjek').val(nama);
        }
    });

    $('#tableTugasan').DataTable({
        language: { search: 'Cari:', zeroRecords: 'Tiada rekod', info: 'Rekod _START_ - _END_ daripada _TOTAL_',
                    paginate: { previous: 'Sebelum', next: 'Seterusnya' } },
        pageLength: 15,
        columnDefs: [{ orderable: false, targets: [0,5] }]
    });

    $('.btn-aksi').on('click', function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('confirm') || 'Sahkan padam tugasan ini?';
        Swal.fire({
            title: 'Sahkan Padam',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, padam',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
