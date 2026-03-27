<?php
// ============================================================
// AJAX: Live search untuk Select2 dan autocomplete
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$modul = $_GET['modul'] ?? '';
$q     = trim($_GET['q'] ?? '');
$extra = $_GET['extra'] ?? '';

$results = [];

switch ($modul) {
    // Cari murid (untuk prestasi, erph)
    case 'murid':
        $sql = "SELECT m.id, CONCAT(m.nama, ' [', k.tingkatan, ' ', k.nama_kelas, ']') AS text
            FROM murid m LEFT JOIN kelas k ON k.id=m.kelas_id
            WHERE m.status='aktif' AND m.nama LIKE ? ORDER BY m.nama LIMIT 30";
        $results = dbFetchAll($sql, ['%' . $q . '%']);
        break;

    // Murid mengikut kelas (untuk erph - senarai hadir)
    case 'murid_kelas':
        $kelasId = (int)($_GET['kelas_id'] ?? 0);
        if ($kelasId) {
            $sql = "SELECT m.id, m.nama, m.jantina, m.no_ic
                FROM murid m WHERE m.kelas_id=? AND m.status='aktif' ORDER BY m.nama";
            $murid = dbFetchAll($sql, [$kelasId]);
            // Return as HTML table rows for direct insertion
            header('Content-Type: application/json');
            $rows = '';
            foreach ($murid as $m) {
                $rows .= '<tr>
                    <td><input type="checkbox" class="form-check-input" name="murid_hadir[]" value="' . $m['id'] . '" checked></td>
                    <td>' . htmlspecialchars($m['nama']) . '</td>
                    <td><span class="badge bg-' . ($m['jantina'] === 'L' ? 'primary' : 'danger') . '">' . $m['jantina'] . '</span></td>
                    <td><small class="text-muted">' . htmlspecialchars($m['no_ic'] ?? '-') . '</small></td>
                </tr>';
            }
            echo json_encode(['ok' => true, 'html' => $rows, 'count' => count($murid)]);
            exit;
        }
        break;

    // Cari guru (untuk kelas, erph)
    case 'guru':
        $sql = "SELECT id, CONCAT(nama, IFNULL(CONCAT(' [', jawatan, ']'), '')) AS text
            FROM guru WHERE status='aktif' AND nama LIKE ? ORDER BY nama LIMIT 30";
        $results = dbFetchAll($sql, ['%' . $q . '%']);
        break;

    // Cari kelas
    case 'kelas':
        $tahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);
        $sql = "SELECT id, CONCAT(tingkatan, ' ', nama_kelas, ' (', tahun, ')') AS text
            FROM kelas WHERE tahun=? AND status='aktif' AND nama_kelas LIKE ?
            ORDER BY tingkatan, nama_kelas LIMIT 30";
        $results = dbFetchAll($sql, [$tahun, '%' . $q . '%']);
        break;

    // Cari standard kurikulum (DSKP) untuk ERPH
    case 'kurikulum':
        $mataPelajaran = $_GET['mata_pelajaran'] ?? '';
        $tingkatan     = $_GET['tingkatan'] ?? '';
        $where = 'status = "aktif"';
        $params = [];
        if ($mataPelajaran) { $where .= ' AND mata_pelajaran = ?'; $params[] = $mataPelajaran; }
        if ($tingkatan)     { $where .= ' AND tingkatan = ?';     $params[] = $tingkatan; }
        if ($q)             { $where .= ' AND (standard_kandungan LIKE ? OR standard_pembelajaran LIKE ? OR standard_kandungan_kod LIKE ?)';
                              $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; }
        $params[] = 50;
        $sql = "SELECT id,
            CONCAT(IFNULL(standard_kandungan_kod,''),' - ',LEFT(standard_kandungan,60),
                '  |  ', IFNULL(standard_pembelajaran_kod,''), ' SP', IFNULL(standard_prestasi,'')) AS text,
            standard_kandungan, standard_pembelajaran, standard_prestasi,
            standard_kandungan_kod, standard_pembelajaran_kod
            FROM kurikulum WHERE $where ORDER BY standard_kandungan_kod LIMIT ?";
        $rows = dbFetchAll($sql, $params);
        // Format untuk Select2
        foreach ($rows as $r) {
            $results[] = [
                'id'   => $r['id'],
                'text' => $r['text'],
                'sk'   => $r['standard_kandungan'],
                'sp'   => $r['standard_pembelajaran'],
                'spp'  => $r['standard_prestasi'],
                'sk_kod' => $r['standard_kandungan_kod'],
                'sp_kod' => $r['standard_pembelajaran_kod'],
            ];
        }
        echo json_encode($results);
        exit;

    // Cari subjek
    case 'subjek':
        $sql = "SELECT id, CONCAT(kod, ' - ', nama) AS text
            FROM subjek WHERE status='aktif' AND (kod LIKE ? OR nama LIKE ?) ORDER BY nama LIMIT 30";
        $results = dbFetchAll($sql, ['%'.$q.'%', '%'.$q.'%']);
        break;

    // Maklumat kelas (untuk auto-fill nama_kelas)
    case 'info_kelas':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $kelas = dbFetch("SELECT nama_kelas, tingkatan FROM kelas WHERE id=?", [$id]);
            echo json_encode($kelas ?: []);
            exit;
        }
        break;

    // Maklumat subjek (untuk auto-fill nama_subjek, mata_pelajaran)
    case 'info_subjek':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $subj = dbFetch("SELECT id, kod, nama FROM subjek WHERE id=?", [$id]);
            echo json_encode($subj ?: []);
            exit;
        }
        break;

    // Statistik dashboard (AJAX refresh)
    case 'stats':
        $tahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);
        echo json_encode([
            'murid' => dbValue("SELECT COUNT(*) FROM murid WHERE tahun=? AND status='aktif'", [$tahun]),
            'guru'  => dbValue("SELECT COUNT(*) FROM guru WHERE status='aktif'"),
            'kelas' => dbValue("SELECT COUNT(*) FROM kelas WHERE tahun=? AND status='aktif'", [$tahun]),
            'erph'  => dbValue("SELECT COUNT(*) FROM erph WHERE tahun=? AND status='aktif'", [$tahun]),
            'opr'   => dbValue("SELECT COUNT(*) FROM opr WHERE tahun=? AND status='aktif'", [$tahun]),
        ]);
        exit;
}

echo json_encode($results);
