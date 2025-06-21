// ====================
// APP.JS OPR SEKOLAH
// ====================

// Semua elemen utama borang & paparan
const el = id => document.getElementById(id);

const oprForm = el('oprForm');
const carianInput = el('carianInput');
const listOPR = el('listOPR');

const ambilGambarBtn = el('ambilGambarBtn');
const uploadGambarBtn = el('uploadGambarBtn');
const gambarBuktiInput = el('gambarBukti');
const gambarPreview = el('gambarPreview');
const padamGambarBtn = el('padamGambarBtn');
const statusKamera = el('statusKamera');

const rekodSuaraBtn = el('rekodSuaraBtn');
const statusRekod = el('statusRekod');

const eksportBtn = el('eksportBtn');
const importBtn = el('importBtn');
const importFile = el('importFile');
const cetakBtn = el('cetakBtn');
const darkModeBtn = el('darkModeBtn');
const syncDownBtn = document.getElementById('syncDownBtn');
const syncUpBtn = document.getElementById('syncUpBtn');

let gambarBase64 = null, dataOPR = [];

// ====== Cek Kamera & Mikrofon ======
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

// ====== INIT & LOAD ======
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('dataOPR')) {
        try { dataOPR = JSON.parse(localStorage.getItem('dataOPR')) || []; } catch { dataOPR = []; }
    }
    paparkanSenaraiOPR();
    periksaKamera(); periksaMikrofon();
    if (localStorage.getItem('darkMode') === 'true') aktifkanDarkMode(true);
});

// ====== Gambar: Kamera & Upload ======
ambilGambarBtn.addEventListener('click', () => {
    if (!kameraSedia) { statusKamera.textContent = "Kamera tiada atau tidak boleh diakses."; return; }
    bukaKameraDanAmbilGambar();
});
uploadGambarBtn.addEventListener('click', () => {
    gambarBuktiInput.click();
});
gambarBuktiInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = evt => {
            gambarBase64 = evt.target.result;
            paparkanPreviewGambar(gambarBase64);
        };
        reader.readAsDataURL(file);
    }
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
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        gambarBase64 = canvas.toDataURL('image/jpeg', 0.85);
        paparkanPreviewGambar(gambarBase64);
        if (stream) stream.getTracks().forEach(track => track.stop());
        document.body.removeChild(kameraPopup);
    });
    tutupKameraBtn.addEventListener('click', () => {
        if (stream) stream.getTracks().forEach(track => track.stop());
        document.body.removeChild(kameraPopup);
    });
}
function paparkanPreviewGambar(base64) {
    gambarPreview.innerHTML = `<img src="${base64}" alt="Gambar Bukti" style="max-width:100%;border-radius:10px;margin-top:5px;">`;
    padamGambarBtn.style.display = 'inline-block';
}
padamGambarBtn.addEventListener('click', () => {
    gambarPreview.innerHTML = '';
    gambarBase64 = null;
    padamGambarBtn.style.display = 'none';
    gambarBuktiInput.value = '';
});

// ====== Suara ke Teks ======
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

// ====== Simpan Laporan ======
oprForm.addEventListener('submit', function(e){
    e.preventDefault();
    const data = {
        namaProgram: el('namaProgram').value.trim(),
        tarikhMasa: el('tarikhMasa').value,
        tempat: el('tempat').value.trim(),
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
        gambar: gambarBase64,
        id: Date.now()
    };
    dataOPR.unshift(data);
    simpanDataLocal();
    paparkanSenaraiOPR();
    oprForm.reset();
    gambarPreview.innerHTML = '';
    gambarBase64 = null;
    padamGambarBtn.style.display = 'none';
    alert('Laporan berjaya disimpan! Terima kasih cikgu.');
});

// Simpan ke localStorage
function simpanDataLocal() {
    localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
}

// ====== Paparan, Carian, Cetak ======
function paparkanSenaraiOPR() {
    let list = dataOPR;
    const q = (carianInput.value || "").toLowerCase();
    if (q) {
        list = list.filter(item =>
            Object.values(item).join(" ").toLowerCase().includes(q)
        );
    }
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
            <b>Kos/Peralatan:</b> ${escapeHTML(item.kos)}
        </div>
        ${item.gambar ? `<img src="${item.gambar}" alt="Gambar Bukti">` : ''}
        <div class="opr-guru">
            <i>Penyedia: ${escapeHTML(item.penyedia)}${item.penyemak ? ", Penyemak: " + escapeHTML(item.penyemak) : ""}${item.pengesah ? ", Pengesah: " + escapeHTML(item.pengesah) : ""}</i>
        </div>
        <button class="opr-btn" onclick="padamLaporan(${item.id})">Padam</button>
    </div>
    `).join('');
}
window.padamLaporan = function(id) {
    if (confirm('Padam laporan ini?')) {
        dataOPR = dataOPR.filter(item => item.id !== id);
        simpanDataLocal(); paparkanSenaraiOPR();
    }
};
carianInput.addEventListener('input', paparkanSenaraiOPR);

// Format tarikh gaya Malaysia
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

// ====== Eksport / Import Data ======
eksportBtn.addEventListener('click', () => {
    const blob = new Blob([JSON.stringify(dataOPR, null, 2)], {type:"application/json"});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = "opr_sekolah.json";
    document.body.appendChild(a); a.click();
    setTimeout(()=>{document.body.removeChild(a);URL.revokeObjectURL(url);},100);
});
importBtn.addEventListener('click', () => { importFile.click(); });
importFile.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(evt) {
        try {
            const dataBaru = JSON.parse(evt.target.result);
            if (Array.isArray(dataBaru)) {
                if (confirm("Gabung dengan data sedia ada? (OK = gabung, Batal = ganti semua)")) {
                    const idSet = new Set(dataOPR.map(x=>x.id));
                    dataBaru.forEach(item => { if(!idSet.has(item.id)) dataOPR.push(item); });
                } else {
                    dataOPR = dataBaru;
                }
                simpanDataLocal(); paparkanSenaraiOPR();
                alert('Data berjaya diimport!');
            } else throw new Error();
        } catch {
            alert("Fail tidak sah. Pilih fail eksport OPR (.json) sahaja.");
        }
    };
    reader.readAsText(file);
    importFile.value = '';
});

// ====== Cetak/Simpan PDF (One Page) ======
cetakBtn.addEventListener('click', () => {
    window.print();
});

// ====== Dark Mode ======
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


// SYNC DOWN: Muat turun data pusat dari repo GitHub cikgu
syncDownBtn.addEventListener('click', () => {
    const url = "https://raw.githubusercontent.com/farimi2025/myopr/main/opr_data.json";
    fetch(url).then(res => {
        if (!res.ok) throw new Error('Network response not ok');
        return res.json();
    }).then(json => {
        if (confirm("Data pusat akan dimasukkan/gabung ke dalam senarai OPR cikgu. Teruskan?")) {
            const idSet = new Set(dataOPR.map(x => x.id));
            json.forEach(item => { if (!idSet.has(item.id)) dataOPR.push(item); });
            simpanDataLocal(); paparkanSenaraiOPR();
            alert('Data pusat berjaya diselaraskan.');
        }
    }).catch(() => {
        alert('Tidak dapat akses data pusat. Pastikan URL/fail betul atau internet ok.');
    });
});

// SYNC UP: Eksport untuk admin (fail .json untuk upload ke GitHub secara manual)
syncUpBtn.addEventListener('click', () => {
    alert("Untuk sync up, cikgu eksport data dahulu, kemudian admin upload ke GitHub (fail opr_data.json).");
    eksportBtn.click();
});
