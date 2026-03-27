<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistem Pengurusan Sekolah' ?> | <?= clean(getSetting('nama_sekolah')) ?></title>
    <meta name="description" content="Sistem Pengurusan Sekolah - Rekod Murid, Guru & Kurikulum">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <?= $extraHead ?? '' ?>
</head>
<body class="sb-nav-fixed">
<!-- TOP NAVBAR -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
    <!-- Brand -->
    <a class="navbar-brand ps-3 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
        <i class="bi bi-mortarboard-fill fs-4"></i>
        <span class="d-none d-md-inline fw-semibold" style="font-size:.9rem;line-height:1.2">
            <?= clean(getSetting('nama_sekolah')) ?>
        </span>
    </a>

    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 ms-2" id="sidebarToggle" type="button">
        <i class="bi bi-list fs-4 text-white"></i>
    </button>

    <!-- Navbar Right -->
    <ul class="navbar-nav ms-auto me-3 align-items-center">
        <!-- Tahun -->
        <li class="nav-item me-2 d-none d-md-block">
            <span class="badge bg-white text-primary fw-semibold px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i>Tahun <?= TAHUN_SEMASA ?>
            </span>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                <div class="avatar-sm bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     style="width:32px;height:32px;font-size:.85rem">
                    <?= strtoupper(substr($_SESSION['user_nama'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="d-none d-lg-inline text-white" style="font-size:.9rem">
                    <?= clean($_SESSION['user_nama'] ?? '') ?>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header">
                    <i class="bi bi-person-circle me-1"></i><?= clean($_SESSION['user_nama'] ?? '') ?>
                    <br><small class="text-muted"><?= ucfirst(str_replace('_',' ',$_SESSION['user_peranan'] ?? '')) ?></small>
                </h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/pentadbir/profil.php">
                    <i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/pentadbir/tetapan.php">
                    <i class="bi bi-gear me-2"></i>Tetapan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Log Keluar</a></li>
            </ul>
        </li>
    </ul>
</nav>

<div id="layoutSidenav">
<?php include __DIR__ . '/sidebar.php'; ?>
<div id="layoutSidenav_content">
<main>
<div class="container-fluid px-4 py-3">
