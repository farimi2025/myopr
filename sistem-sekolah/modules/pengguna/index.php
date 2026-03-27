<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['super_admin', 'pentadbir']);

$pageTitle = 'Pengurusan Pengguna';

$currentUser = getCurrentUser();

// Stats
$totalAktif      = (int)dbValue("SELECT COUNT(*) FROM users WHERE status='aktif'");
$totalSuperAdmin = (int)dbValue("SELECT COUNT(*) FROM users WHERE peranan='super_admin'");
$totalPentadbir  = (int)dbValue("SELECT COUNT(*) FROM users WHERE peranan='pentadbir'");
$totalGuru       = (int)dbValue("SELECT COUNT(*) FROM users WHERE peranan='guru'");
$totalStaf       = (int)dbValue("SELECT COUNT(*) FROM users WHERE peranan='staf'");

// Filter
$filterPeranan = clean($_GET['peranan'] ?? '');
$filterStatus  = clean($_GET['status'] ?? '');
$filterCarian  = clean($_GET['carian'] ?? '');

$params = [];
$where  = ['1=1'];

if ($filterPeranan && in_array($filterPeranan, ['super_admin','pentadbir','guru','staf'])) {
    $where[]  = 'peranan=?';
    $params[] = $filterPeranan;
}
if ($filterStatus && in_array($filterStatus, ['aktif','arkib','tangguh'])) {
    $where[]  = 'status=?';
    $params[] = $filterStatus;
}
if ($filterCarian) {
    $where[]  = '(nama LIKE ? OR username LIKE ?)';
    $params[] = '%' . $filterCarian . '%';
    $params[] = '%' . $filterCarian . '%';
}

$whereStr = implode(' AND ', $where);
$senarai  = dbFetchAll("SELECT id, nama, username, email, peranan, status, foto, last_login
    FROM users WHERE $whereStr ORDER BY nama", $params);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>Pengurusan Pengguna</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item active">Pengguna Sistem</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/pengguna/tambah.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
    </a>
</div>

<?= showFlash() ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-person-check-fill text-success fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-success"><?= $totalAktif ?></div>
                    <div class="text-muted small">Pengguna Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                    <i class="bi bi-shield-fill text-danger fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-danger"><?= $totalSuperAdmin ?></div>
                    <div class="text-muted small">Super Admin</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-person-gear text-warning fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-warning"><?= $totalPentadbir ?></div>
                    <div class="text-muted small">Pentadbir</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-person-badge text-primary fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-primary"><?= $totalGuru ?></div>
                    <div class="text-muted small">Guru</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-person text-secondary fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-secondary"><?= $totalStaf ?></div>
                    <div class="text-muted small">Staf</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Peranan</label>
                <select name="peranan" class="form-select form-select-sm">
                    <option value="">-- Semua Peranan --</option>
                    <?php foreach (['super_admin'=>'Super Admin','pentadbir'=>'Pentadbir','guru'=>'Guru','staf'=>'Staf'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $filterPeranan === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    <?php foreach (['aktif'=>'Aktif','arkib'=>'Arkib','tangguh'=>'Tangguh'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Carian</label>
                <input type="text" name="carian" class="form-control form-control-sm"
                       value="<?= $filterCarian ?>" placeholder="Cari nama atau username...">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-1"></i>Senarai Pengguna Sistem</h6>
        <span class="badge bg-primary rounded-pill"><?= count($senarai) ?> rekod</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablePengguna" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>E-mel</th>
                        <th class="text-center">Peranan</th>
                        <th class="text-center">Status</th>
                        <th>Log Masuk Akhir</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($senarai)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tiada rekod pengguna dijumpai.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($senarai as $i => $u): ?>
                    <?php
                        $isSelf = ($u['id'] == $currentUser['id']);
                        $badgePeranan = match($u['peranan']) {
                            'super_admin' => 'danger',
                            'pentadbir'   => 'warning',
                            'guru'        => 'primary',
                            'staf'        => 'secondary',
                            default       => 'secondary'
                        };
                        $labelPeranan = match($u['peranan']) {
                            'super_admin' => 'Super Admin',
                            'pentadbir'   => 'Pentadbir',
                            'guru'        => 'Guru',
                            'staf'        => 'Staf',
                            default       => ucfirst($u['peranan'])
                        };
                    ?>
                    <tr>
                        <td class="px-3 text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= gambarUrl($u['foto']) ?>" alt="<?= clean($u['nama']) ?>"
                                     class="rounded-circle border"
                                     style="width:36px;height:36px;object-fit:cover;flex-shrink:0">
                                <div class="fw-semibold"><?= clean($u['nama']) ?></div>
                            </div>
                        </td>
                        <td class="text-muted small"><code><?= clean($u['username']) ?></code></td>
                        <td class="text-muted small"><?= clean($u['email'] ?: '-') ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $badgePeranan ?>"><?= $labelPeranan ?></span>
                        </td>
                        <td class="text-center"><?= badgeStatus($u['status']) ?></td>
                        <td class="text-muted small">
                            <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <?php if ($u['status'] === 'aktif'): ?>
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-aksi <?= $isSelf ? 'disabled' : '' ?>"
                                    data-url="aksi.php"
                                    data-tindakan="arkib"
                                    data-id="<?= $u['id'] ?>"
                                    data-confirm="Arkibkan pengguna <?= clean($u['nama']) ?>?"
                                    title="Arkib"
                                    <?= $isSelf ? 'disabled' : '' ?>>
                                    <i class="bi bi-archive"></i>
                                </button>
                                <?php else: ?>
                                <button type="button"
                                    class="btn btn-sm btn-outline-success btn-aksi <?= $isSelf ? 'disabled' : '' ?>"
                                    data-url="aksi.php"
                                    data-tindakan="aktif"
                                    data-id="<?= $u['id'] ?>"
                                    data-confirm="Aktifkan semula pengguna <?= clean($u['nama']) ?>?"
                                    title="Aktifkan"
                                    <?= $isSelf ? 'disabled' : '' ?>>
                                    <i class="bi bi-person-check"></i>
                                </button>
                                <?php endif; ?>

                                <button type="button"
                                    class="btn btn-sm btn-outline-info btn-reset-pw <?= $isSelf ? 'disabled' : '' ?>"
                                    data-id="<?= $u['id'] ?>"
                                    data-nama="<?= clean($u['nama']) ?>"
                                    title="Set Semula Kata Laluan"
                                    <?= $isSelf ? 'disabled' : '' ?>>
                                    <i class="bi bi-key"></i>
                                </button>

                                <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-aksi <?= $isSelf ? 'disabled' : '' ?>"
                                    data-url="aksi.php"
                                    data-tindakan="padam"
                                    data-id="<?= $u['id'] ?>"
                                    data-confirm="Padam pengguna <?= clean($u['nama']) ?>? Tindakan ini tidak boleh dibatalkan."
                                    title="Padam"
                                    <?= $isSelf ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white small text-muted">
        Jumlah rekod: <strong><?= count($senarai) ?></strong>
    </div>
</div>

<!-- Hidden form for POST actions -->
<form id="formAksi" method="POST" action="aksi.php" class="d-none">
    <?= csrfField() ?>
    <input type="hidden" name="tindakan" id="inputTindakan">
    <input type="hidden" name="id" id="inputId">
</form>

<?php $extraScript = <<<JS
<script>
$(function(){
    $('#tablePengguna').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ms.json',
            search: 'Cari:',
            lengthMenu: 'Papar _MENU_ rekod',
            info: 'Rekod _START_ hingga _END_ daripada _TOTAL_',
            paginate: { previous: 'Sebelum', next: 'Seterusnya' },
            zeroRecords: 'Tiada rekod dijumpai'
        },
        pageLength: 25,
        order: [[1,'asc']],
        columnDefs: [
            { orderable: false, targets: [0,7] },
            { searchable: false, targets: [0,5,6,7] }
        ]
    });

    // Tindakan arkib/aktif/padam
    $('.btn-aksi').on('click', function(){
        if ($(this).is(':disabled') || $(this).hasClass('disabled')) return;
        const tindakan = $(this).data('tindakan');
        const id       = $(this).data('id');
        const msg      = $(this).data('confirm') || 'Sahkan tindakan ini?';
        const icon     = tindakan === 'padam' ? 'warning' : 'question';
        const btnColor = tindakan === 'padam' ? '#dc3545' : '#0d6efd';

        Swal.fire({
            title: 'Sahkan Tindakan',
            text: msg,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, teruskan',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (r.isConfirmed) {
                $('#inputTindakan').val(tindakan);
                $('#inputId').val(id);
                $('#formAksi').submit();
            }
        });
    });

    // Set semula kata laluan
    $('.btn-reset-pw').on('click', function(){
        if ($(this).is(':disabled') || $(this).hasClass('disabled')) return;
        const id   = $(this).data('id');
        const nama = $(this).data('nama');
        Swal.fire({
            title: 'Set Semula Kata Laluan',
            html: 'Jana kata laluan rawak baharu untuk <strong>' + nama + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, jana sekarang',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (r.isConfirmed) {
                $('#inputTindakan').val('reset_password');
                $('#inputId').val(id);
                $('#formAksi').submit();
            }
        });
    });
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
