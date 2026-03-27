<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pageTitle = 'Import CSV Murid';

// ── Data untuk form ───────────────────────────────────────────────────────────
$senaraKelas = dbFetchAll(
    "SELECT id, CONCAT(tingkatan,' ',nama_kelas) AS label FROM kelas WHERE tahun=? AND status='aktif' ORDER BY tingkatan, nama_kelas",
    [TAHUN_SEMASA]
);

// ── Keputusan import ──────────────────────────────────────────────────────────
$importDone    = false;
$jumlahBerjaya = 0;
$jumlahGagal   = 0;
$senaraRalat   = [];
$errors        = [];

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('danger', 'Token CSRF tidak sah. Sila cuba semula.');
        redirect(BASE_URL . '/modules/murid/import.php');
    }

    $tahunImport   = (int)($_POST['tahun']     ?? TAHUN_SEMASA);
    $kelasOverride = (int)($_POST['kelas_id']  ?? 0);
    $langkauRalat  = isset($_POST['langkau_ralat']);

    // Semak fail diupload
    if (empty($_FILES['fail_csv']['tmp_name']) || $_FILES['fail_csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Sila pilih fail CSV untuk diimport.';
    } else {
        $tmpPath  = $_FILES['fail_csv']['tmp_name'];
        $origName = $_FILES['fail_csv']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $errors[] = 'Fail mesti dalam format CSV (.csv).';
        } else {
            // Baca fail CSV
            $handle = fopen($tmpPath, 'r');
            if ($handle === false) {
                $errors[] = 'Gagal membaca fail CSV. Sila cuba lagi.';
            } else {
                $noBarisRaw   = 0;
                $noBarisDiproses = 0;
                $batchNoIc    = []; // jejak no_ic dalam batch ini
                $hentiBulk    = false;

                while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                    $noBarisRaw++;

                    // Baris 1 = header, langkau
                    if ($noBarisRaw === 1) {
                        continue;
                    }

                    // Langkau baris kosong
                    if (empty(array_filter($row, fn($v) => trim($v) !== ''))) {
                        continue;
                    }

                    $noBarisDiproses++;
                    $noBarisCSV = $noBarisRaw; // nombor baris sebenar dalam fail

                    // Petakan kolum (berdasarkan template)
                    $nama            = trim($row[0]  ?? '');
                    $noIc            = trim($row[1]  ?? '');
                    $jantina         = strtoupper(trim($row[2] ?? ''));
                    $tarikhLahir     = trim($row[3]  ?? '');
                    $bangsa          = trim($row[4]  ?? '');
                    $agama           = trim($row[5]  ?? '');
                    $alamat          = trim($row[6]  ?? '');
                    $poskod          = trim($row[7]  ?? '');
                    $bandar          = trim($row[8]  ?? '');
                    $negeriMurid     = trim($row[9]  ?? '');
                    $telefonIbuBapa  = trim($row[10] ?? '');
                    $namaIbuBapa     = trim($row[11] ?? '');
                    $hubungan        = trim($row[12] ?? '');
                    $kelasIdCsv      = (int)trim($row[13] ?? 0);
                    $catatan         = trim($row[14] ?? '');

                    $ralaтBaris = [];

                    // ── Validasi ──────────────────────────────────────────
                    if ($nama === '') {
                        $ralaтBaris[] = 'Nama wajib diisi.';
                    }

                    if (!in_array($jantina, ['L', 'P'])) {
                        $ralaтBaris[] = 'Jantina mesti L (Lelaki) atau P (Perempuan). Nilai: "' . htmlspecialchars($jantina) . '"';
                    }

                    if ($tarikhLahir !== '') {
                        $dt = \DateTime::createFromFormat('Y-m-d', $tarikhLahir);
                        if (!$dt || $dt->format('Y-m-d') !== $tarikhLahir) {
                            $ralaтBaris[] = 'Format tarikh_lahir mesti YYYY-MM-DD. Nilai: "' . htmlspecialchars($tarikhLahir) . '"';
                            $tarikhLahir = null;
                        }
                    } else {
                        $tarikhLahir = null;
                    }

                    // Semak duplikat no_ic dalam batch
                    if ($noIc !== '') {
                        if (isset($batchNoIc[$noIc])) {
                            $ralaтBaris[] = 'No. IC "' . htmlspecialchars($noIc) . '" sudah wujud dalam fail CSV ini (baris ' . $batchNoIc[$noIc] . ').';
                        } else {
                            // Semak duplikat dalam DB
                            $dupDb = dbValue("SELECT id FROM murid WHERE no_ic = ? LIMIT 1", [$noIc]);
                            if ($dupDb) {
                                $ralaтBaris[] = 'No. IC "' . htmlspecialchars($noIc) . '" sudah wujud dalam pangkalan data.';
                            } else {
                                $batchNoIc[$noIc] = $noBarisCSV;
                            }
                        }
                    }

                    // Bangsa – semak nilai yang dibenarkan
                    $bangsaBenar = ['Melayu','Cina','India','Bumiputera Sabah','Bumiputera Sarawak','Lain-lain'];
                    if ($bangsa !== '' && !in_array($bangsa, $bangsaBenar)) {
                        // Bukan ralat keras, tapi simpan sebagai 'Lain-lain'
                        $bangsa = 'Lain-lain';
                    }

                    // Jika ada ralat
                    if (!empty($ralaтBaris)) {
                        $senaraRalat[] = [
                            'baris'  => $noBarisCSV,
                            'nama'   => $nama !== '' ? $nama : '(kosong)',
                            'sebab'  => $ralaтBaris,
                        ];
                        $jumlahGagal++;

                        if (!$langkauRalat) {
                            $hentiBulk = true;
                            break;
                        }
                        continue;
                    }

                    // ── Tentukan kelas_id ─────────────────────────────────
                    // Keutamaan: override dari form > dari CSV
                    $kelasIdFinal = $kelasOverride > 0 ? $kelasOverride : ($kelasIdCsv > 0 ? $kelasIdCsv : null);

                    // ── Auto-generate no_pendaftaran ──────────────────────
                    $bilanganSk = (int)dbValue("SELECT COUNT(*) FROM murid WHERE tahun=?", [$tahunImport]);
                    $noPendaftaran = 'MRD/' . $tahunImport . '/' . str_pad($bilanganSk + 1, 4, '0', STR_PAD_LEFT);

                    // Pastikan no_pendaftaran unik (langkah keselamatan)
                    $cubaan = 0;
                    while (dbValue("SELECT id FROM murid WHERE no_pendaftaran=? LIMIT 1", [$noPendaftaran])) {
                        $cubaan++;
                        $noPendaftaran = 'MRD/' . $tahunImport . '/' . str_pad($bilanganSk + 1 + $cubaan, 4, '0', STR_PAD_LEFT);
                    }

                    // ── Insert ke DB ──────────────────────────────────────
                    $dataInsert = [
                        'no_pendaftaran'   => $noPendaftaran,
                        'nama'             => strtoupper($nama),
                        'no_ic'            => $noIc !== '' ? $noIc : null,
                        'jantina'          => $jantina,
                        'tarikh_lahir'     => $tarikhLahir,
                        'kelas_id'         => $kelasIdFinal,
                        'tahun'            => $tahunImport,
                        'bangsa'           => $bangsa !== '' ? $bangsa : null,
                        'agama'            => $agama !== '' ? $agama : null,
                        'alamat'           => $alamat !== '' ? $alamat : null,
                        'poskod'           => $poskod !== '' ? $poskod : null,
                        'bandar'           => $bandar !== '' ? $bandar : null,
                        'negeri_murid'     => $negeriMurid !== '' ? $negeriMurid : null,
                        'telefon_ibu_bapa' => $telefonIbuBapa !== '' ? $telefonIbuBapa : null,
                        'nama_ibu_bapa'    => $namaIbuBapa !== '' ? strtoupper($namaIbuBapa) : null,
                        'hubungan'         => $hubungan !== '' ? $hubungan : null,
                        'status'           => 'aktif',
                        'catatan'          => $catatan !== '' ? $catatan : null,
                    ];

                    try {
                        dbInsert('murid', $dataInsert);
                        $jumlahBerjaya++;
                    } catch (\Exception $e) {
                        $senaraRalat[] = [
                            'baris'  => $noBarisCSV,
                            'nama'   => $nama,
                            'sebab'  => ['Gagal insert ke pangkalan data: ' . $e->getMessage()],
                        ];
                        $jumlahGagal++;
                        if (!$langkauRalat) {
                            $hentiBulk = true;
                            break;
                        }
                    }
                }

                fclose($handle);

                // Log aktiviti
                if ($jumlahBerjaya > 0) {
                    logAktiviti('import', 'murid', null, "Import CSV murid: {$jumlahBerjaya} rekod berjaya, {$jumlahGagal} gagal.");
                }

                if ($hentiBulk && $jumlahGagal > 0) {
                    $errors[] = 'Import dihentikan pada baris ' . ($senaraRalat[0]['baris'] ?? '-') . ' kerana terdapat ralat. ' . $jumlahBerjaya . ' rekod telah diimport sebelum dihentikan.';
                }

                $importDone = true;
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-upload text-primary me-2"></i>Import CSV Murid</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Utama</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/murid/index.php">Murid</a></li>
                <li class="breadcrumb-item active">Import CSV</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?= showFlash() ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible d-flex align-items-start mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0"></i>
    <div>
        <strong>Ralat:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SEKSYEN 1: Muat Turun Template                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-arrow-down me-2 text-success"></i>Langkah 1: Muat Turun Template CSV</h6>
    </div>
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <p class="mb-2 fw-semibold small">Format kolum dalam template:</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kolum</th>
                                <th>Keterangan</th>
                                <th>Wajib</th>
                                <th>Format / Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>nama</code></td><td>Nama penuh murid</td><td><span class="badge bg-danger">Ya</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>no_ic</code></td><td>No. Kad Pengenalan</td><td><span class="badge bg-secondary">Tidak</span></td><td>XXXXXX-XX-XXXX</td></tr>
                            <tr><td><code>jantina</code></td><td>Jantina</td><td><span class="badge bg-danger">Ya</span></td><td><code>L</code> = Lelaki, <code>P</code> = Perempuan</td></tr>
                            <tr><td><code>tarikh_lahir</code></td><td>Tarikh lahir</td><td><span class="badge bg-secondary">Tidak</span></td><td>YYYY-MM-DD (cth: 2010-01-15)</td></tr>
                            <tr><td><code>bangsa</code></td><td>Bangsa</td><td><span class="badge bg-secondary">Tidak</span></td><td>Melayu / Cina / India / Bumiputera Sabah / Bumiputera Sarawak / Lain-lain</td></tr>
                            <tr><td><code>agama</code></td><td>Agama</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>alamat</code></td><td>Alamat</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>poskod</code></td><td>Poskod</td><td><span class="badge bg-secondary">Tidak</span></td><td>5 digit</td></tr>
                            <tr><td><code>bandar</code></td><td>Bandar</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>negeri_murid</code></td><td>Negeri</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>telefon_ibu_bapa</code></td><td>No. telefon penjaga</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>nama_ibu_bapa</code></td><td>Nama ibu bapa / penjaga</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                            <tr><td><code>hubungan</code></td><td>Hubungan dengan murid</td><td><span class="badge bg-secondary">Tidak</span></td><td>Bapa / Ibu / Penjaga dll.</td></tr>
                            <tr><td><code>kelas_id</code></td><td>ID kelas dari sistem</td><td><span class="badge bg-secondary">Tidak</span></td><td>Nombor ID kelas (boleh override dengan pilihan di bawah)</td></tr>
                            <tr><td><code>catatan</code></td><td>Catatan tambahan</td><td><span class="badge bg-secondary">Tidak</span></td><td>Teks bebas</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0 small py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>No. Pendaftaran</strong> akan dijana secara automatik dalam format <code>MRD/TAHUN/NNNN</code>.
                    Baris pertama fail CSV adalah baris header dan akan dilangkau semasa import.
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-4 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                    <i class="bi bi-file-earmark-spreadsheet display-4 text-success mb-3 d-block"></i>
                    <p class="small text-muted mb-3">Muat turun fail template CSV dengan data contoh untuk panduan format yang betul.</p>
                    <a href="<?= BASE_URL ?>/ajax/template_murid.php"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-download me-1"></i>Muat Turun Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SEKSYEN 2: Form Upload                                                    -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-cloud-upload me-2 text-primary"></i>Langkah 2: Muat Naik Fail CSV</h6>
    </div>
    <div class="card-body">
        <form id="formImport" method="POST" action="" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <div class="row g-3">

                <!-- Fail CSV -->
                <div class="col-md-12">
                    <label class="form-label fw-semibold small">Fail CSV <span class="text-danger">*</span></label>
                    <input type="file" name="fail_csv" id="failCsv" class="form-control"
                           accept=".csv,text/csv" required>
                    <div class="form-text text-muted">Hanya fail .csv diterima. Saiz maksimum: <?= ini_get('upload_max_filesize') ?>.</div>
                </div>

                <!-- Tahun -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                           value="<?= TAHUN_SEMASA ?>" min="2000" max="2099">
                    <div class="form-text text-muted">Tahun rekod murid akan disimpan.</div>
                </div>

                <!-- Kelas Override -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Override Kelas (pilihan)</label>
                    <select name="kelas_id" id="selectKelas" class="form-select select2kelas">
                        <option value="">-- Guna kelas dari CSV --</option>
                        <?php foreach ($senaraKelas as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= clean($k['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted">Jika dipilih, semua murid akan dimasukkan ke kelas ini (mengatasi nilai dalam CSV).</div>
                </div>

                <!-- Pilihan ralat -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Tetapan Ralat</label>
                    <div class="mt-2">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="langkau_ralat" id="radioLangkau"
                                   value="1" checked>
                            <label class="form-check-label small" for="radioLangkau">
                                <i class="bi bi-skip-forward text-warning me-1"></i>
                                Langkau baris yang ada ralat &amp; teruskan import
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="langkau_ralat" id="radioHenti"
                                   value="">
                            <label class="form-check-label small" for="radioHenti">
                                <i class="bi bi-stop-circle text-danger me-1"></i>
                                Berhenti jika ada ralat pada mana-mana baris
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview area -->
            <div id="previewArea" class="mt-4" style="display:none;">
                <hr>
                <h6 class="fw-semibold small mb-2"><i class="bi bi-eye me-1 text-info"></i>Pratonton (5 baris pertama)</h6>
                <div class="table-responsive">
                    <table id="previewTable" class="table table-bordered table-sm small mb-0">
                        <thead class="table-light" id="previewHead"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
                <p class="small text-muted mt-1 mb-0" id="previewMsg"></p>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="button" id="btnPratonton" class="btn btn-outline-info btn-sm" disabled>
                    <i class="bi bi-eye me-1"></i>Pratonton
                </button>
                <button type="submit" id="btnImport" class="btn btn-primary btn-sm">
                    <i class="bi bi-upload me-1"></i>Import Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SEKSYEN 3: Hasil Import (papar selepas POST)                              -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<?php if ($importDone): ?>
<div class="card border-0 shadow-sm mb-4" id="hasilImport">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Hasil Import</h6>
        <a href="<?= BASE_URL ?>/modules/murid/index.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people-fill me-1"></i>Lihat Senarai Murid
        </a>
    </div>
    <div class="card-body">

        <!-- Ringkasan -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 bg-success bg-opacity-10 h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle bg-success bg-opacity-20 p-3 d-flex">
                            <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Rekod Berjaya Diimport</div>
                            <div class="fw-bold fs-2 text-success"><?= number_format($jumlahBerjaya) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 <?= $jumlahGagal > 0 ? 'bg-danger bg-opacity-10' : 'bg-secondary bg-opacity-10' ?> h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle <?= $jumlahGagal > 0 ? 'bg-danger bg-opacity-20' : 'bg-secondary bg-opacity-20' ?> p-3 d-flex">
                            <i class="bi bi-x-circle-fill fs-3 <?= $jumlahGagal > 0 ? 'text-danger' : 'text-secondary' ?>"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Rekod Gagal / Dilangkau</div>
                            <div class="fw-bold fs-2 <?= $jumlahGagal > 0 ? 'text-danger' : 'text-secondary' ?>"><?= number_format($jumlahGagal) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($jumlahBerjaya > 0): ?>
        <div class="alert alert-success mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong><?= number_format($jumlahBerjaya) ?> rekod murid</strong> berjaya diimport ke dalam sistem untuk tahun <?= (int)($_POST['tahun'] ?? TAHUN_SEMASA) ?>.
        </div>
        <?php endif; ?>

        <!-- Jadual Ralat -->
        <?php if (!empty($senaraRalat)): ?>
        <h6 class="fw-semibold small mb-2 text-danger">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>Senarai Ralat Per Baris
        </h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm small align-middle mb-0">
                <thead class="table-danger">
                    <tr>
                        <th style="width:80px">No. Baris</th>
                        <th>Nama</th>
                        <th>Sebab Kegagalan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($senaraRalat as $r): ?>
                    <tr>
                        <td class="text-center fw-semibold"><?= (int)$r['baris'] ?></td>
                        <td><?= clean($r['nama']) ?></td>
                        <td>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($r['sebab'] as $s): ?>
                                    <li><?= $s ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($jumlahBerjaya === 0 && $jumlahGagal === 0): ?>
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Tiada rekod diproses. Sila pastikan fail CSV mengandungi data (bukan hanya baris header).
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
$(function () {
    // Select2 untuk kelas
    $('.select2kelas').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Guna kelas dari CSV --',
        allowClear: true,
        width: '100%'
    });

    const failInput   = $('#failCsv');
    const btnPratonton = $('#btnPratonton');
    const previewArea  = $('#previewArea');

    // Aktifkan butang pratonton bila fail dipilih
    failInput.on('change', function () {
        const file = this.files[0];
        if (file && file.name.toLowerCase().endsWith('.csv')) {
            btnPratonton.prop('disabled', false);
        } else {
            btnPratonton.prop('disabled', true);
            previewArea.hide();
        }
    });

    // Pratonton 5 baris pertama fail CSV menggunakan FileReader
    btnPratonton.on('click', function () {
        const file = failInput[0].files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            const lines = e.target.result.split('\n').filter(l => l.trim() !== '');
            if (lines.length === 0) {
                previewArea.hide();
                return;
            }

            // Parse CSV baris secara mudah (tidak handle quotes kompleks)
            function parseCSVLine(line) {
                const result = [];
                let current = '';
                let inQuotes = false;
                for (let i = 0; i < line.length; i++) {
                    const ch = line[i];
                    if (ch === '"') {
                        inQuotes = !inQuotes;
                    } else if (ch === ',' && !inQuotes) {
                        result.push(current.trim());
                        current = '';
                    } else {
                        current += ch;
                    }
                }
                result.push(current.trim());
                return result;
            }

            // Header
            const headers = parseCSVLine(lines[0]);
            let theadHtml = '<tr>';
            headers.forEach(h => { theadHtml += `<th>${$('<div>').text(h).html()}</th>`; });
            theadHtml += '</tr>';
            $('#previewHead').html(theadHtml);

            // Data (max 5 baris selepas header)
            let tbodyHtml = '';
            const maxRows = Math.min(lines.length - 1, 5);
            for (let i = 1; i <= maxRows; i++) {
                const cols = parseCSVLine(lines[i]);
                tbodyHtml += '<tr>';
                cols.forEach(c => { tbodyHtml += `<td>${$('<div>').text(c).html()}</td>`; });
                tbodyHtml += '</tr>';
            }
            $('#previewBody').html(tbodyHtml);

            const jumlahData = lines.length - 1;
            $('#previewMsg').text(
                `Menunjukkan ${maxRows} daripada ${jumlahData} baris data (tidak termasuk header).`
            );
            previewArea.show();
        };
        reader.readAsText(file, 'UTF-8');
    });

    // Confirm sebelum import
    $('#formImport').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const fail = failInput[0].files[0];
        if (!fail) {
            Swal.fire({
                icon: 'warning',
                title: 'Tiada Fail',
                text: 'Sila pilih fail CSV terlebih dahulu.',
            });
            return;
        }
        Swal.fire({
            title: 'Sahkan Import?',
            html: `Fail <strong>${$('<div>').text(fail.name).html()}</strong> akan diimport. Proses ini tidak boleh dibatalkan.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonText: 'Batal',
            confirmButtonText: '<i class="bi bi-upload me-1"></i>Ya, Import',
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });

    // Scroll ke hasil import jika ada
    const hasil = document.getElementById('hasilImport');
    if (hasil) {
        hasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
