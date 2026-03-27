</div><!-- container-fluid -->
</main>

<!-- FOOTER -->
<footer class="py-3 mt-auto bg-white border-top">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between small text-muted">
            <div>&copy; <?= date('Y') ?> <?= clean(getSetting('nama_sekolah')) ?> &mdash; Sistem Pengurusan Sekolah v1.0</div>
            <div><a href="<?= BASE_URL ?>/modules/pentadbir/bantuan.php" class="text-muted text-decoration-none">
                <i class="bi bi-question-circle me-1"></i>Bantuan</a></div>
        </div>
    </div>
</footer>

</div><!-- layoutSidenav_content -->
</div><!-- layoutSidenav -->

<!-- MODAL AUTOSAVE STATUS -->
<div id="autosaveBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index:1055">
    <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center gap-2 shadow">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <span id="autosaveMsg">Menyimpan draf...</span>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100" id="toastContainer"></div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<?= $extraScript ?? '' ?>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.body.classList.toggle('sb-sidenav-toggled');
    localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
});
if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
    document.body.classList.add('sb-sidenav-toggled');
}
</script>
</body>
</html>
