<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle = 'Tambah Kelas';
$errors    = [];
$old       = ['tahun' => TAHUN_SEMASA];

$senaraiGuru = getSenaraiGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Token keselamatan tidak sah. Sila cuba lagi.');
        redirect(BASE_URL . '/modules/kelas/tambah.php');
    }

    $old = $_POST;

    $namaKelas   = clean($_POST['nama_kelas'] ?? '');
    $tingkatan   = clean($_POST['tingkatan'] ?? '');
    $aliran      = clean($_POST['aliran'] ?? '');
    $guruKelasId = (int)($_POST['guru_kelas_id'] ?? 0);
    $tahun       = (int)($_POST['tahun'] ?? TAHUN_SEMASA);
    $bilMurid    = (int)($_POST['bil_murid'] ?? 0);
    $catatan     = clean($_POST['catatan'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['aktif','tidak aktif']) ? $_POST['status'] : 'aktif';

    if (empty($namaKelas)) $errors['nama_kelas'] = 'Nama kelas diperlukan.';
    if (empty($tingkatan)) $errors['tingkatan']  = 'Tingkatan diperlukan.';
    if ($tahun < 2000 || $tahun > 2100) $errors['tahun'] = 'Tahun tidak sah.';

    // Check duplicate
    if ($namaKelas && $tahun) {
        $cek = dbValue("SELECT id FROM kelas WHERE nama_kelas=? AND tahun=?", [$namaKelas, $tahun]);
        if ($cek) $errors['nama_kelas'] = 'Nama kelas sudah wujud untuk tahun ' . $tahun . '.';
    }

    if (empty($errors)) {
        $id = dbInsert('kelas', [
            'nama_kelas'   => $namaKelas,
            'tingkatan'    => $tingkatan,
            'aliran'       => $aliran,
            'guru_kelas_id'=> $guruKelasId ?: null,
            'bil_murid'    => $bilMurid,
            'tahun'        => $tahun,
            'status'       => $status,
            'catatan'      => $catatan,
        ]);
        logAktiviti('tambah', 'kelas', (int)$id, "Tambah kelas: $namaKelas ($tahun)");
        setFlash('success', "Kelas <strong>$namaKelas</strong> berjaya ditambah.");
        redirect(BASE_URL . '/modules/kelas/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Kelas</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="index.php">Kelas</a></li>
                <li class="breadcrumb-item active">Tambah</li>
            </ol>
        </nav>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?= showFlash() ?>

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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-building me-2"></i>Maklumat Kelas</h6>
    </div>
    <div class="card-body">
        <form method="POST" novalidate>
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Kelas <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kelas" class="form-control <?= isset($errors['nama_kelas']) ? 'is-invalid' : '' ?>"
                           value="<?= clean($old['nama_kelas'] ?? '') ?>" placeholder="Contoh: 1 Arif / 4 Sains">
                    <?php if (isset($errors['nama_kelas'])): ?><div class="invalid-feedback"><?= $errors['nama_kelas'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tingkatan <span class="text-danger">*</span></label>
                    <select name="tingkatan" class="form-select <?= isset($errors['tingkatan']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Pilih Tingkatan --</option>
                        <?php foreach (['1','2','3','4','5','6','PPKI'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($old['tingkatan'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tingkatan'])): ?><div class="invalid-feedback"><?= $errors['tingkatan'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Aliran</label>
                    <select name="aliran" class="form-select">
                        <option value="">-- Pilih Aliran --</option>
                        <?php foreach (['Sains','Sastera','Teknik','Vokasional','Agama','PPKI','Lain-lain'] as $a): ?>
                        <option value="<?= $a ?>" <?= ($old['aliran'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Guru Kelas</label>
                    <select name="guru_kelas_id" class="form-select select2-guru">
                        <option value="">-- Pilih Guru Kelas --</option>
                        <?php foreach ($senaraiGuru as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($old['guru_kelas_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                            <?= clean($g['nama']) ?><?= $g['no_pekerja'] ? ' (' . clean($g['no_pekerja']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun <span class="text-danger">*</span></label>
                    <input type="number" name="tahun" class="form-control <?= isset($errors['tahun']) ? 'is-invalid' : '' ?>"
                           value="<?= clean($old['tahun'] ?? TAHUN_SEMASA) ?>" min="2000" max="2100">
                    <?php if (isset($errors['tahun'])): ?><div class="invalid-feedback"><?= $errors['tahun'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Bilangan Murid</label>
                    <input type="number" name="bil_murid" class="form-control"
                           value="<?= clean($old['bil_murid'] ?? '0') ?>" min="0" max="60">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= ($old['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="tidak aktif" <?= ($old['status'] ?? '') === 'tidak aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3"
                              placeholder="Catatan tambahan (jika ada)"><?= clean($old['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle me-1"></i>Simpan Kelas
                </button>
                <a href="index.php" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('.select2-guru').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Guru Kelas --',
        allowClear: true
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
