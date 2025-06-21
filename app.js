// ==========================
// APP.JS OPR SEKOLAH VERSI MINIMUM & STABIL
// ==========================

const el = id => document.getElementById(id);

const simpanBtn      = el('simpanBtn');
const importBtn      = el('importBtn');
const downloadBtn    = el('downloadBtn');
const cetakBtn       = el('cetakBtn');
const darkModeBtn    = el('darkModeBtn');

const oprForm        = el('oprForm');
const carianInput    = el('carianInput');
const listOPR        = el('listOPR');
const filterBulan    = el('filterBulan');
const filterGuru     = el('filterGuru');

const ambilGambarBtn = el('ambilGambarBtn');
const uploadGambarBtn= el('uploadGambarBtn');
const gambarBuktiInput= el('gambarBukti');
const gambarPreview  = el('gambarPreview');
const padamGambarBtn = el('padamGambarBtn');
const statusKamera   = el('statusKamera');

const rekodSuaraBtn  = el('rekodSuaraBtn');
const statusRekod    = el('statusRekod');
const importFile     = el('importFile');

// =============== DATA ===============
let dataOPR = [];
let gambarBuktiBase64 = []; // max 3 gambar

// =============== Kamera & Mikrofon ===============
let kameraSedia = false, mikrofonSedia = false;

function periksaKamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.enumerateDevices().then(devices=>{
            kameraSedia = devices.some(d=>d.kind==="videoinput");
            statusKamera.textContent = kameraSedia ? "" : "Peranti ini tidak ada kamera.";
            ambilGambarBtn.disabled = !kameraSedia;
        });
    } else {
        statusKamera.textContent = "Peranti ini tidak menyokong kamera.";
        ambilGambarBtn.disabled = true;
    }
}
function periksaMikrofon() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.enumerateDevices().then(devices=>{
            mikrofonSedia = devices.some(d=>d.kind==="audioinput");
            if(!mikrofonSedia) {
                rekodSuaraBtn.disabled = true;
                statusRekod.textContent = "Peranti ini tidak ada mikrofon.";
            }
        });
    } else {
        rekodSuaraBtn.disabled = true;
        statusRekod.textContent = "Peranti ini tidak menyokong mikrofon.";
    }
}

// =============== INIT ===============
window.addEventListener('DOMContentLoaded', () => {
    try {
        if (localStorage.getItem('dataOPR')) {
            dataOPR = JSON.parse(localStorage.getItem('dataOPR')) || [];
        }
    } catch { dataOPR = []; }
    paparkanSenaraiOPR();
    periksaKamera(); periksaMikrofon();
    if (localStorage.getItem('darkMode') === 'true') aktifkanDarkMode(true);
});

// =============== GAMBAR: Auto Resize/Compress (max 3) ===============
const HAD_SAIZ = 350 * 1024; // 350KB maksimum
const MAKS_GAMBAR = 3;

ambilGambarBtn.addEventListener('click', () => {
    if (!kameraSedia) { statusKamera.textContent = "Kamera tiada atau tidak boleh diakses."; return; }
    bukaKameraDanAmbilGambar();
});
uploadGambarBtn.addEventListener('click', () => {
    gambarBuktiInput.click();
});
gambarBuktiInput.addEventListener('change', (e) => {
    const files = e.target.files;
    if (!files.length) return;
    [...files].forEach(file => {
        if (gambarBuktiBase64.length >= MAKS_GAMBAR) return;
        resizeImageFile(file, (base64) => {
            gambarBuktiBase64.push(base64);
            paparkanPreviewGambar();
        });
    });
    gambarBuktiInput.value = '';
});
function bukaKameraDanAmbilGambar() {
    const kameraPopup = document.createElement('div');
    kameraPopup.className = 'kamera-popup';
    kameraPopup.innerHTML = `
        <video id="videoKamera" autoplay playsinline></video>
        <button id="snapBtn">📸 Ambil Foto</button>
        <button id="tutupKameraBtn">Tutup</button>
    `;
    document.body.appendChild(kameraPopup);
    const video = kameraPopup.querySelector('#videoKamera');
    const snapBtn = kameraPopup.querySelector('#snapBtn');
    const tutupKameraBtn = kameraPopup.querySelector('#tutupKameraBtn');
    let stream;
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(s => {
            stream = s; video.srcObject = stream;
        }).catch(err => {
            statusKamera.textContent = "Tidak dapat akses kamera. Sila benarkan akses.";
            document.body.removeChild(kameraPopup);
        });
    snapBtn.addEventListener('click', () => {
        if (gambarBuktiBase64.length >= MAKS_GAMBAR) {
            alert("Hanya maksimum 3 gambar sahaja.");
            if (stream) stream.getTracks().forEach(track => track.stop());
            document.body.removeChild(kameraPopup);
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        resizeImageCanvas(canvas, (base64) => {
            gambarBuktiBase64.push(base64);
            paparkanPreviewGambar();
        });
        if (stream) stream.getTracks().forEach(track => track.stop());
        document.body.removeChild(kameraPopup);
    });
    tutupKameraBtn.addEventListener('click', () => {
        if (stream) stream.getTracks().forEach(track => track.stop());
        document.body.removeChild(kameraPopup);
    });
}
function paparkanPreviewGambar() {
    gambarPreview.innerHTML = gambarBuktiBase64.map((src,i) =>
        `<div style="display:inline-block;position:relative;margin:2px;">
            <img src="${src}" style="max-width:85px;max-height:85px;border-radius:8px;">
            <button style="position:absolute;top:2px;right:2px;" onclick="padamGambar(${i})" type="button">❌</button>
        </div>`
    ).join('');
    padamGambarBtn.style.display = gambarBuktiBase64.length ? 'inline-block' : 'none';
}
window.padamGambar = function(idx) {
    gambarBuktiBase64.splice(idx,1);
    paparkanPreviewGambar();
}
padamGambarBtn.addEventListener('click', () => {
    gambarBuktiBase64 = [];
    paparkanPreviewGambar();
});

// --- FUNGSI RESIZE & COMPRESS UTAMA ---
function resizeImageFile(file, callback) {
    const reader = new FileReader();
    reader.onload = evt => {
        const img = new window.Image();
        img.onload = function () {
            resizeImageCanvas(imgToCanvas(img), callback);
        };
        img.src = evt.target.result;
    };
    reader.readAsDataURL(file);
}
function imgToCanvas(img) {
    // Saiz maksimum lebar/tinggi
    const MAX_W = 900, MAX_H = 900;
    let w = img.width, h = img.height;
    if (w > MAX_W || h > MAX_H) {
        const scale = Math.min(MAX_W/w, MAX_H/h);
        w = Math.round(w * scale); h = Math.round(h * scale);
    }
    const canvas = document.createElement('canvas');
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
    return canvas;
}
function resizeImageCanvas(canvas, callback) {
    let quality = 0.85;
    let base64 = canvas.toDataURL('image/jpeg', quality);
    function tryCompress() {
        if (base64.length/1.37 > HAD_SAIZ && quality > 0.5) {
            quality -= 0.05;
            base64 = canvas.toDataURL('image/jpeg', quality);
            setTimeout(tryCompress, 0);
        } else {
            callback(base64);
        }
    }
    tryCompress();
}

// =============== SUARA ke TEKS ===============
rekodSuaraBtn.addEventListener('click', () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition || !mikrofonSedia) {
        statusRekod.textContent = "Peranti ini tidak menyokong suara ke teks atau tiada mikrofon.";
        return;
    }
    const rec = new SpeechRecognition();
    rec.lang = "ms-MY";
    rec.continuous = false;
    rec.interimResults = false;
    statusRekod.textContent = "Sila bercakap... (rakaman aktif)";
    rekodSuaraBtn.disabled = true;
    rec.start();
    rec.onresult = function(event) {
        let hasil = event.results[0][0].transcript;
        el('butiranAktiviti').value += (el('butiranAktiviti').value ? "\n" : "") + hasil;
        statusRekod.textContent = "Rakaman berjaya!";
        rekodSuaraBtn.disabled = false;
    };
    rec.onerror = function() {
        statusRekod.textContent = "Ralat suara. Sila cuba lagi.";
        rekodSuaraBtn.disabled = false;
    };
    rec.onend = function() {
        statusRekod.textContent = "";
        rekodSuaraBtn.disabled = false;
    };
});

// =============== SIMPAN, AUTO EXPORT, IMPORT, DOWNLOAD ===============
simpanBtn.addEventListener('click', function(){
    const data = {
        namaProgram: el('namaProgram').value.trim(),
        tarikhMasa: el('tarikhMasa').value,
        tempat: el('tempat').value.trim(),
        anjuran: el('anjuran').value.trim(),
        penaja: el('penaja').value.trim(),
        sasaran: el('sasaran').value.trim(),
        objektif: el('objektif').value.trim(),
        butiranAktiviti: el('butiranAktiviti').value.trim(),
        pencapaian: el('pencapaian').value.trim(),
        kekuatan: el('kekuatan').value.trim(),
        kelemahan: el('kelemahan').value.trim(),
        intervensi: el('intervensi').value.trim(),
        impak: el('impak').value.trim(),
        kos: el('kos').value.trim(),
        penyedia: el('penyedia').value.trim(),
        penyemak: el('penyemak').value.trim(),
        pengesah: el('pengesah').value.trim(),
        gambar: gambarBuktiBase64.slice(0),
        id: Date.now()
    };
    dataOPR.unshift(data);
    simpanDataLocal();
    paparkanSenaraiOPR();
    oprForm.reset();
    gambarBuktiBase64 = [];
    paparkanPreviewGambar();
    padamGambarBtn.style.display = 'none';
    autoEksportJSON();
    alert('Laporan berjaya disimpan, dieksport dan disimpan tempatan!');
});
function simpanDataLocal() {
    localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
}
function autoEksportJSON() {
    const blob = new Blob([JSON.stringify(dataOPR, null, 2)], {type:"application/json"});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = "opr_data.json";
    document.body.appendChild(a); a.click();
    setTimeout(()=>{document.body.removeChild(a);URL.revokeObjectURL(url);},100);
}
downloadBtn.addEventListener('click', autoEksportJSON);

importBtn.addEventListener('click', () => { importFile.click(); });
importFile.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(evt) {
        try {
            const dataBaru = JSON.parse(evt.target.result);
            if (Array.isArray(dataBaru)) {
                const idSet = new Set(dataOPR.map(x=>x.id));
                let countBaru = 0;
                dataBaru.forEach(item => { if(!idSet.has(item.id)) { dataOPR.push(item); countBaru++; } });
                simpanDataLocal(); paparkanSenaraiOPR();
                alert('Data berjaya digabung!\nLaporan baru dimasukkan: ' + countBaru + '\nJumlah laporan terkini: ' + dataOPR.length);
            } else throw new Error();
        } catch {
            alert("Fail tidak sah. Pilih fail eksport OPR (.json) sahaja.");
        }
    };
    reader.readAsText(file);
    importFile.value = '';
});

// =============== CETAK / PDF ===============
cetakBtn.addEventListener('click', () => {
    window.print();
});

// =============== DARK MODE ===============
darkModeBtn.addEventListener('click', () => {
    aktifkanDarkMode();
});
function aktifkanDarkMode(force) {
    const isDark = force !== undefined ? force : !document.body.classList.contains('dark');
    if (isDark) {
        document.body.classList.add('dark'); darkModeBtn.classList.add('active');
    } else {
        document.body.classList.remove('dark'); darkModeBtn.classList.remove('active');
    }
    localStorage.setItem('darkMode', isDark ? 'true' : 'false');
}

// =============== Paparan, Carian, Filter, Preview Senarai ===============
function paparkanSenaraiOPR() {
    let list = dataOPR;
    const q = (carianInput.value || "").toLowerCase();

    if (q) {
        list = list.filter(item =>
            Object.values(item).join(" ").toLowerCase().includes(q)
        );
    }
    // Filter bulan
    if (filterBulan && filterBulan.value) {
        list = list.filter(item => {
            const bulan = (item.tarikhMasa || "").slice(5, 7);
            return bulan === filterBulan.value;
        });
    }
    // Filter guru
    if (filterGuru && filterGuru.value) {
        list = list.filter(item => item.penyedia === filterGuru.value);
    }

    // Update dropdown guru ikut senarai
    const semuaGuru = [...new Set(dataOPR.map(item => item.penyedia).filter(Boolean))].sort();
    filterGuru.innerHTML = '<option value="">Semua Guru</option>' +
        semuaGuru.map(guru => `<option value="${escapeHTML(guru)}">${escapeHTML(guru)}</option>`).join('');
    filterGuru.value = filterGuru.value || '';

    if (list.length === 0) {
        listOPR.innerHTML = "<p>Tiada laporan ditemui.</p>";
        return;
    }

    listOPR.innerHTML = list.map(item => `
        <div class="opr-item">
            <span class="opr-tarikh">${formatTarikh(item.tarikhMasa)}</span>
            <div class="opr-tajuk">${escapeHTML(item.namaProgram)}</div>
            <div class="opr-guru">Tempat: ${escapeHTML(item.tempat)}${item.sasaran ? " | Sasaran: " + escapeHTML(item.sasaran) : ""}</div>
            <div class="opr-ringkasan">
                <b>Objektif:</b> ${escapeHTML(item.objektif)}<br>
                <b>Butiran:</b> ${escapeHTML(item.butiranAktiviti)}<br>
                <b>Pencapaian:</b> ${escapeHTML(item.pencapaian)}<br>
                <b>Kekuatan:</b> ${escapeHTML(item.kekuatan)}<br>
                <b>Kelemahan:</b> ${escapeHTML(item.kelemahan)}<br>
                <b>Cadangan Intervensi:</b> ${escapeHTML(item.intervensi)}<br>
                <b>Impak:</b> ${escapeHTML(item.impak)}<br>
                <b>Kos/Peralatan:</b> ${escapeHTML(item.kos)}<br>
                ${item.penaja ? `<div><b>Penaja:</b> ${escapeHTML(item.penaja)}</div>` : ""}
                ${item.anjuran ? `<div><b>Anjuran:</b> ${escapeHTML(item.anjuran)}</div>` : ""}
            </div>
            ${(Array.isArray(item.gambar) && item.gambar.length) ? item.gambar.map(gm=>`<img src="${gm}" alt="Gambar Bukti" style="max-width:100px;margin:2px;border-radius:8px;">`).join('') : ''}
            <div class="opr-guru">
                <i>Penyedia: ${escapeHTML(item.penyedia)}${item.penyemak ? ", Penyemak: " + escapeHTML(item.penyemak) : ""}${item.pengesah ? ", Pengesah: " + escapeHTML(item.pengesah) : ""}</i>
            </div>
        </div>
    `).join('');
}
carianInput.addEventListener('input', paparkanSenaraiOPR);
filterBulan.addEventListener('change', paparkanSenaraiOPR);
filterGuru.addEventListener('change', paparkanSenaraiOPR);

function formatTarikh(t) {
    if (!t) return '-';
    try {
        const d = new Date(t);
        const hari = ["Ahad","Isnin","Selasa","Rabu","Khamis","Jumaat","Sabtu"];
        return hari[d.getDay()]+", "+d.toLocaleDateString('ms-MY') + " " +
            d.toLocaleTimeString('ms-MY', { hour: '2-digit', minute: '2-digit' });
    } catch { return t; }
}
function escapeHTML(text) {
    if (!text) return "";
    return text.replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
}
