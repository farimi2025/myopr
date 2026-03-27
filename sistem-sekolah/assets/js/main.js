/* ============================================================
   SISTEM PENGURUSAN SEKOLAH — Main JavaScript
   ============================================================ */

'use strict';

// ---- Global Config ----
const APP = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.content || '',
    autosaveInterval: 30000,
    autosaveTimer: null,
};

// ---- Document Ready ----
$(function () {
    initDataTables();
    initSelect2();
    initAutoSave();
    initDeleteConfirm();
    initTooltips();
    initFormAutoFill();
    initImagePreview();
    initMarkahGred();
});

// ---- DataTables ----
function initDataTables() {
    if (!$.fn.DataTable) return;
    $('.data-table').each(function () {
        if ($.fn.DataTable.isDataTable(this)) return;
        $(this).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ms.json',
                emptyTable: 'Tiada rekod dijumpai',
                zeroRecords: 'Tiada rekod sepadan ditemui',
                info: 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ rekod',
                infoEmpty: '0 rekod',
                infoFiltered: '(ditapis daripada _MAX_ rekod)',
                lengthMenu: 'Papar _MENU_ rekod',
                search: 'Cari:',
                paginate: { first: '«', last: '»', next: '›', previous: '‹' }
            },
            pageLength: 25,
            responsive: true,
            order: [],
            dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        });
    });
}

// ---- Select2 ----
function initSelect2() {
    if (!$.fn.select2) return;
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: '-- Pilih --',
    });
    // Select2 with AJAX search
    $('.select2-ajax').each(function () {
        const url = $(this).data('url');
        const minLen = parseInt($(this).data('min-length') || 2);
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumInputLength: minLen,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 300,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
            },
            placeholder: '-- Taip untuk cari --',
        });
    });
}

// ---- Auto Save (AJAX) ----
function initAutoSave() {
    const form = document.getElementById('mainForm');
    if (!form || !form.dataset.autosave) return;

    const modul = form.dataset.autosave;
    const rekodId = form.dataset.rekodId || 0;

    function doAutoSave() {
        const formData = new FormData(form);
        formData.append('modul', modul);
        formData.append('rekod_id', rekodId);
        formData.append('action', 'autosave');

        showAutosaveBar('Menyimpan draf...');

        fetch(APP.baseUrl + '/ajax/autosave.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('[name=csrf_token]')?.value || '' },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) showAutosaveBar('Draf disimpan ✓', 'success');
            else showAutosaveBar('Gagal menyimpan', 'danger');
            setTimeout(() => hideAutosaveBar(), 2500);
        })
        .catch(() => { hideAutosaveBar(); });
    }

    // Trigger autosave on input change (debounced)
    let saveTimeout;
    form.addEventListener('input', () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(doAutoSave, 5000);
    });

    // Interval autosave
    APP.autosaveTimer = setInterval(doAutoSave, APP.autosaveInterval);
}

function showAutosaveBar(msg, type = 'info') {
    const bar = document.getElementById('autosaveBar');
    const msgEl = document.getElementById('autosaveMsg');
    if (!bar) return;
    msgEl.textContent = msg;
    bar.className = `position-fixed bottom-0 start-50 translate-middle-x mb-3`;
    bar.querySelector('.alert').className = `alert alert-${type} py-2 px-3 mb-0 d-flex align-items-center gap-2 shadow`;
    bar.classList.remove('d-none');
}

function hideAutosaveBar() {
    document.getElementById('autosaveBar')?.classList.add('d-none');
}

// ---- Delete Confirm ----
function initDeleteConfirm() {
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.href || this.dataset.url;
            const nama = this.dataset.confirmDelete || 'rekod ini';
            Swal.fire({
                title: 'Padam Rekod?',
                html: `Adakah anda pasti mahu memadam <strong>${nama}</strong>?<br><small class="text-muted">Tindakan ini tidak boleh dibatalkan.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="bi bi-trash me-1"></i>Ya, Padam',
                cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });
}

// ---- Tooltips ----
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
}

// ---- Smart Form AutoFill ----
function initFormAutoFill() {
    // Auto-fill gred berdasarkan markah
    document.querySelectorAll('.input-markah').forEach(input => {
        input.addEventListener('input', function () {
            const markah = parseFloat(this.value);
            const gredField = document.querySelector(this.dataset.gredTarget || '#gred');
            if (gredField && !isNaN(markah)) {
                gredField.value = kiraMGred(markah);
            }
        });
    });

    // Auto-uppercase IC
    document.querySelectorAll('.input-ic').forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9-]/g, '');
        });
    });

    // Auto-format telefon
    document.querySelectorAll('.input-telefon').forEach(input => {
        input.addEventListener('blur', function () {
            let v = this.value.replace(/[^0-9]/g, '');
            if (v.startsWith('60')) v = '0' + v.slice(2);
            if (v.length >= 9) {
                this.value = v.startsWith('011') ? v.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2 $3') :
                             v.replace(/(\d{2,3})(\d{3,4})(\d{4})/, '$1-$2 $3');
            }
        });
    });
}

// ---- Image Preview ----
function initImagePreview() {
    document.querySelectorAll('.input-gambar').forEach(input => {
        input.addEventListener('change', function () {
            const target = document.getElementById(this.dataset.preview);
            if (!target || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                if (target.tagName === 'IMG') {
                    target.src = e.target.result;
                    target.style.display = 'block';
                } else {
                    target.style.backgroundImage = `url(${e.target.result})`;
                }
                target.closest('.opr-photo-box')?.querySelector('.upload-hint')?.classList.add('d-none');
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    // Click box to trigger file input
    document.querySelectorAll('.opr-photo-box').forEach(box => {
        box.addEventListener('click', () => {
            box.querySelector('input[type=file]')?.click();
        });
    });
}

// ---- Kira Gred ----
function kiraMGred(markah) {
    if (markah >= 90) return 'A+';
    if (markah >= 80) return 'A';
    if (markah >= 70) return 'A-';
    if (markah >= 65) return 'B+';
    if (markah >= 60) return 'B';
    if (markah >= 55) return 'B-';
    if (markah >= 50) return 'C+';
    if (markah >= 45) return 'C';
    if (markah >= 40) return 'C-';
    if (markah >= 35) return 'D';
    if (markah >= 30) return 'E';
    return 'G';
}

function initMarkahGred() {
    // Live update gred field when markah changes
    $(document).on('input', '#markah', function () {
        const val = parseFloat($(this).val());
        if (!isNaN(val) && val >= 0 && val <= 100) {
            $('#gred').val(kiraMGred(val));
        }
    });
}

// ---- Toast Notification ----
function showToast(mesej, jenis = 'success', tajuk = '') {
    const id = 'toast_' + Date.now();
    const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const html = `<div id="${id}" class="toast align-items-center text-bg-${jenis} border-0 shadow" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-${icons[jenis] || 'info-circle-fill'}"></i> ${mesej}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;
    $('#toastContainer').append(html);
    new bootstrap.Toast(document.getElementById(id), { delay: 4000 }).show();
    document.getElementById(id).addEventListener('hidden.bs.toast', () => document.getElementById(id)?.remove());
}

// ---- Print page ----
function printPage() {
    window.print();
}

// ---- Archive/Restore AJAX ----
function arkibRekod(url, nama) {
    Swal.fire({
        title: 'Arkib Rekod?',
        html: `Arkibkan <strong>${nama}</strong>? Rekod boleh dipulihkan kemudian.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6b7280',
        cancelButtonColor: '#2563eb',
        confirmButtonText: '<i class="bi bi-archive me-1"></i>Arkib',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) window.location.href = url;
    });
}

// ---- Export helpers ----
function exportPDF(url) {
    window.open(url, '_blank');
}

function exportDOCX(url) {
    window.location.href = url;
}
