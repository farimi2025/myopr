<?php
// Tentukan menu aktif berdasarkan path semasa
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';

function isMenuActive(string $path): string {
    global $currentPath;
    return str_contains($currentPath, $path) ? 'active' : '';
}
function isCollapseOpen(array $paths): string {
    global $currentPath;
    foreach ($paths as $p) if (str_contains($currentPath, $p)) return '';
    return 'collapsed';
}
function isCollapseShow(array $paths): string {
    global $currentPath;
    foreach ($paths as $p) if (str_contains($currentPath, $p)) return 'show';
    return '';
}
?>
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <!-- Dashboard -->
                <a class="nav-link <?= isMenuActive('/index.php') ?>" href="<?= BASE_URL ?>/index.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-speedometer2"></i></div>
                    Dashboard
                </a>

                <!-- BAHAGIAN MURID -->
                <div class="sb-sidenav-menu-heading">Data Murid</div>

                <a class="nav-link <?= isCollapseOpen(['/murid','/prestasi']) ?>" href="#colMurid"
                   data-bs-toggle="collapse" data-bs-target="#colMurid"
                   aria-expanded="<?= isCollapseShow(['/murid','/prestasi']) ? 'true' : 'false' ?>">
                    <div class="sb-nav-link-icon"><i class="bi bi-people-fill"></i></div>
                    Murid
                    <div class="sb-sidenav-collapse-arrow"><i class="bi bi-chevron-down"></i></div>
                </a>
                <div class="collapse <?= isCollapseShow(['/murid','/prestasi']) ?>" id="colMurid" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link <?= isMenuActive('/murid/index') ?>" href="<?= BASE_URL ?>/modules/murid/index.php">
                            <i class="bi bi-list-ul me-2"></i>Senarai Murid</a>
                        <a class="nav-link <?= isMenuActive('/murid/tambah') ?>" href="<?= BASE_URL ?>/modules/murid/tambah.php">
                            <i class="bi bi-person-plus me-2"></i>Tambah Murid</a>
                        <a class="nav-link <?= isMenuActive('/prestasi') ?>" href="<?= BASE_URL ?>/modules/prestasi/index.php">
                            <i class="bi bi-graph-up me-2"></i>Prestasi / Markah</a>
                    </nav>
                </div>

                <!-- BAHAGIAN GURU -->
                <div class="sb-sidenav-menu-heading">Data Guru</div>

                <a class="nav-link <?= isCollapseOpen(['/guru','/kelas']) ?>" href="#colGuru"
                   data-bs-toggle="collapse" data-bs-target="#colGuru"
                   aria-expanded="<?= isCollapseShow(['/guru','/kelas']) ? 'true' : 'false' ?>">
                    <div class="sb-nav-link-icon"><i class="bi bi-person-badge-fill"></i></div>
                    Guru & Kelas
                    <div class="sb-sidenav-collapse-arrow"><i class="bi bi-chevron-down"></i></div>
                </a>
                <div class="collapse <?= isCollapseShow(['/guru','/kelas']) ?>" id="colGuru" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link <?= isMenuActive('/guru/index') ?>" href="<?= BASE_URL ?>/modules/guru/index.php">
                            <i class="bi bi-list-ul me-2"></i>Senarai Guru</a>
                        <a class="nav-link <?= isMenuActive('/guru/tambah') ?>" href="<?= BASE_URL ?>/modules/guru/tambah.php">
                            <i class="bi bi-person-plus me-2"></i>Tambah Guru</a>
                        <a class="nav-link <?= isMenuActive('/kelas') ?>" href="<?= BASE_URL ?>/modules/kelas/index.php">
                            <i class="bi bi-grid me-2"></i>Kelas & Subjek</a>
                    </nav>
                </div>

                <!-- BAHAGIAN PENTADBIRAN -->
                <div class="sb-sidenav-menu-heading">Pentadbiran</div>

                <a class="nav-link <?= isMenuActive('/pentadbir/index') ?>" href="<?= BASE_URL ?>/modules/pentadbir/index.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-building"></i></div>
                    Pentadbir Sekolah
                </a>

                <!-- BAHAGIAN KURIKULUM -->
                <div class="sb-sidenav-menu-heading">Kurikulum & Pengajaran</div>

                <a class="nav-link <?= isMenuActive('/kurikulum') ?>" href="<?= BASE_URL ?>/modules/kurikulum/index.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-book-fill"></i></div>
                    DSKP 2017
                </a>

                <a class="nav-link <?= isMenuActive('/erph') ?>" href="<?= BASE_URL ?>/modules/erph/index.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-journal-text"></i></div>
                    ERPH
                </a>

                <!-- BAHAGIAN LAPORAN -->
                <div class="sb-sidenav-menu-heading">Laporan</div>

                <a class="nav-link <?= isMenuActive('/opr') ?>" href="<?= BASE_URL ?>/modules/opr/index.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                    One Page Report (OPR)
                </a>

                <!-- BAHAGIAN ADMIN SISTEM -->
                <?php if (hasRole(['super_admin','pentadbir'])): ?>
                <div class="sb-sidenav-menu-heading">Sistem</div>
                <a class="nav-link <?= isMenuActive('/pentadbir/tetapan') ?>" href="<?= BASE_URL ?>/modules/pentadbir/tetapan.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-gear-fill"></i></div>
                    Tetapan Sistem
                </a>
                <a class="nav-link <?= isMenuActive('/pentadbir/arkib') ?>" href="<?= BASE_URL ?>/modules/pentadbir/arkib.php">
                    <div class="sb-nav-link-icon"><i class="bi bi-archive-fill"></i></div>
                    Arkib Tahunan
                </a>
                <?php endif; ?>

            </div>
        </div>

        <!-- Footer sidebar -->
        <div class="sb-sidenav-footer">
            <div class="small fw-semibold text-truncate"><?= clean(getSetting('nama_sekolah')) ?></div>
            <div class="small opacity-75">v1.0 &copy; <?= date('Y') ?></div>
        </div>
    </nav>
</div>
