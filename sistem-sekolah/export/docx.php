<?php
// ============================================================
// EXPORT DOCX — Pure PHP DOCX generator (no Composer needed)
// Menggunakan ZipArchive + OpenXML
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/DocxHelper.php';

$modul = $_GET['modul'] ?? '';
$id    = (int)($_GET['id'] ?? 0);
$tahun = (int)($_GET['tahun'] ?? TAHUN_SEMASA);

$namaSekolah = getSetting('nama_sekolah');

// ============================================================
// OPR
// ============================================================
if ($modul === 'opr') {
    $opr = dbFetch("SELECT o.*, g.nama AS nama_guru FROM opr o
        LEFT JOIN guru g ON g.id=o.guru_id WHERE o.id=?", [$id]);
    if (!$opr) { die('Rekod tidak ditemui.'); }

    $doc = new DocxHelper();
    $doc->setTitle('Laporan Ringkas Aktiviti — ' . $opr['tajuk']);

    $doc->addHeading($namaSekolah, 1);
    $doc->addHeading('LAPORAN RINGKAS AKTIVITI (OPR)', 2);
    $doc->addParagraph('');

    // Info table
    $doc->addTable([
        ['Tajuk Aktiviti',           $opr['tajuk']],
        ['Tarikh',                   formatTarikh($opr['tarikh_aktiviti'])],
        ['Kategori',                 $opr['kategori']],
        ['Tempat',                   $opr['tempat'] ?? '-'],
        ['Pegawai Bertanggungjawab', $opr['pegawai_bertanggungjawab'] ?? '-'],
        ['Disediakan Oleh',          $opr['nama_guru'] ?? '-'],
    ]);

    $doc->addParagraph('');
    if ($opr['penerangan_umum']) {
        $doc->addHeading('Laporan Ringkas', 3);
        $doc->addParagraph($opr['penerangan_umum']);
    }
    if ($opr['penerangan_lanjut']) {
        $doc->addHeading('Penerangan Lanjut', 3);
        $doc->addParagraph($opr['penerangan_lanjut']);
    }
    if ($opr['impak']) {
        $doc->addHeading('Impak', 3);
        $doc->addParagraph($opr['impak']);
    }
    if ($opr['saranan']) {
        $doc->addHeading('Saranan / Cadangan', 3);
        $doc->addParagraph($opr['saranan']);
    }

    $doc->addParagraph('');
    $doc->addParagraph('Disediakan oleh: ________________________________');
    $doc->addParagraph('Tarikh: _______________________________________');
    $doc->addParagraph('');
    $doc->addParagraph('Disahkan oleh (Pengetua/GB): ________________________________');

    $namaFail = 'OPR_' . preg_replace('/[^a-zA-Z0-9]/', '_', $opr['tajuk']) . '_' . date('Ymd') . '.docx';
    $doc->download($namaFail);
}

// ============================================================
// ERPH
// ============================================================
elseif ($modul === 'erph') {
    $erph = dbFetch("SELECT e.*, g.nama AS nama_guru, k.nama_kelas, k.tingkatan
        FROM erph e LEFT JOIN guru g ON g.id=e.guru_id
        LEFT JOIN kelas k ON k.id=e.kelas_id WHERE e.id=?", [$id]);
    if (!$erph) { die('Rekod tidak ditemui.'); }

    $muridHadir = dbFetchAll("SELECT m.nama, m.jantina, m.no_ic
        FROM erph_murid em JOIN murid m ON m.id=em.murid_id
        WHERE em.erph_id=? AND em.hadir=1 ORDER BY m.nama", [$id]);

    $doc = new DocxHelper();
    $doc->setTitle('ERPH — ' . $erph['tajuk']);

    $doc->addHeading($namaSekolah, 1);
    $doc->addHeading('EVIDENS REKOD PENGAJARAN & HASIL (ERPH)', 2);
    $doc->addParagraph('');

    $doc->addTable([
        ['No. Rujukan',        $erph['no_rujukan'] ?? '-'],
        ['Tarikh',             formatTarikh($erph['tarikh'])],
        ['Guru',               $erph['nama_guru'] ?? '-'],
        ['Kelas',              $erph['tingkatan'] . ' ' . $erph['nama_kelas']],
        ['Mata Pelajaran',     $erph['nama_subjek'] ?? '-'],
        ['Bil. Murid Hadir',   $erph['bil_murid_hadir'] . ' orang'],
    ]);

    $doc->addParagraph('');
    $doc->addHeading('Standard Kurikulum (DSKP)', 3);
    $doc->addTable([
        ['Standard Kandungan',    $erph['standard_kandungan'] ?? '-'],
        ['Standard Pembelajaran', $erph['standard_pembelajaran'] ?? '-'],
        ['Standard Prestasi',     $erph['standard_prestasi'] ?? '-'],
    ]);

    $doc->addParagraph('');
    $doc->addHeading('Maklumat PdP', 3);
    $doc->addTable([
        ['Tajuk Pelajaran',    $erph['tajuk']],
        ['Evidens Pengajaran', $erph['evidens'] ?? '-'],
        ['Hasil Pembelajaran', $erph['hasil_pembelajaran'] ?? '-'],
        ['Pendekatan PdP',     $erph['pendekatan_pdp'] ?? '-'],
        ['Refleksi',           $erph['refleksi'] ?? '-'],
        ['Tindakan Susulan',   $erph['tindakan_susulan'] ?? '-'],
    ]);

    if ($muridHadir) {
        $doc->addParagraph('');
        $doc->addHeading('Senarai Murid Hadir (' . count($muridHadir) . ' orang)', 3);
        $rows = [['Bil', 'Nama Murid', 'Jantina', 'No. IC']];
        foreach ($muridHadir as $i => $m) {
            $rows[] = [$i + 1, $m['nama'], $m['jantina'], $m['no_ic'] ?? '-'];
        }
        $doc->addTable($rows, true); // true = has header row
    }

    $namaFail = 'ERPH_' . date('Ymd', strtotime($erph['tarikh'])) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $erph['tajuk']) . '.docx';
    $doc->download($namaFail);
}

// ============================================================
// SENARAI MURID
// ============================================================
elseif ($modul === 'murid') {
    $kelasId = (int)($_GET['kelas_id'] ?? 0);
    $params = [$tahun];
    $where = 'm.tahun=? AND m.status="aktif"';
    if ($kelasId) { $where .= ' AND m.kelas_id=?'; $params[] = $kelasId; }

    $muridList = dbFetchAll("SELECT m.*, k.nama_kelas, k.tingkatan
        FROM murid m LEFT JOIN kelas k ON k.id=m.kelas_id
        WHERE $where ORDER BY k.tingkatan, k.nama_kelas, m.nama", $params);

    $doc = new DocxHelper();
    $doc->setTitle('Senarai Murid Tahun ' . $tahun);
    $doc->addHeading($namaSekolah, 1);
    $doc->addHeading('SENARAI MURID TAHUN ' . $tahun, 2);
    $doc->addParagraph('Jumlah: ' . count($muridList) . ' orang');
    $doc->addParagraph('');

    $rows = [['Bil', 'Nama Murid', 'No. IC', 'Jantina', 'Kelas', 'Telefon IB']];
    foreach ($muridList as $i => $m) {
        $rows[] = [
            $i + 1,
            $m['nama'],
            $m['no_ic'] ?? '-',
            $m['jantina'],
            $m['tingkatan'] . ' ' . $m['nama_kelas'],
            $m['telefon_ibu_bapa'] ?? '-',
        ];
    }
    $doc->addTable($rows, true);
    $doc->download('Senarai_Murid_' . $tahun . '_' . date('Ymd') . '.docx');
}

else {
    die('Modul export tidak dikenali.');
}
