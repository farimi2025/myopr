// ==========================
// APP.JS OPR SEKOLAH VERSI KEMASKINI & NAIK TARAF
// ==========================

const el = id => document.getElementById(id);

// Elemen DOM
const simpanBtn = el('simpanBtn');
const importBtn = el('importBtn');
const downloadBtn = el('downloadBtn');
const cetakBtn = el('cetakBtn');
const downloadPdfBtn = el('downloadPdfBtn');
const darkModeBtn = el('darkModeBtn');

const oprForm = el('oprForm');
const carianInput = el('carianInput');
const listOPR = el('listOPR');
const filterBulan = el('filterBulan');
const filterGuru = el('filterGuru');

const ambilGambarBtn = el('ambilGambarBtn');
const uploadGambarBtn = el('uploadGambarBtn');
const gambarBuktiInput = el('gambarBukti');
const gambarPreview = el('gambarPreview');
const padamGambarBtn = el('padamGambarBtn');
const statusKamera = el('statusKamera');

const rekodSuaraBtn = el('rekodSuaraBtn');
const statusRekod = el('statusRekod');
const importFile = el('importFile');

let dataOPR = [];
let gambarBuktiBase64 = []; // max 3 gambar

// Maklumat sekolah & guru (auto isi)
const sekolahInfo = {
  penolongKanan: 'Pn. Aisyah Binti Ahmad',
  guruBesar: 'En. Zulkifli Bin Hassan',
};

// ==================== INIT ====================
window.addEventListener('DOMContentLoaded', () => {
  try {
    if (localStorage.getItem('dataOPR')) {
      dataOPR = JSON.parse(localStorage.getItem('dataOPR')) || [];
    }
  } catch {
    dataOPR = [];
  }

  // Auto isi penyemak & pengesah
  el('penyemak').value = sekolahInfo.penolongKanan;
  el('pengesah').value = sekolahInfo.guruBesar;

  paparkanSenaraiOPR();
  periksaKamera();
  periksaMikrofon();

  if (localStorage.getItem('darkMode') === 'true') aktifkanDarkMode(true);
});

// =============== Kamera & Mikrofon ===============
let kameraSedia = false,
  mikrofonSedia = false;

function periksaKamera() {
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.enumerateDevices().then(devices => {
      kameraSedia = devices.some(d => d.kind === 'videoinput');
      statusKamera.textContent = kameraSedia ? '' : 'Peranti ini tidak ada kamera.';
      ambilGambarBtn.disabled = !kameraSedia;
    });
  } else {
    statusKamera.textContent = 'Peranti ini tidak menyokong kamera.';
    ambilGambarBtn.disabled = true;
  }
}
function periksaMikrofon() {
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.enumerateDevices().then(devices => {
      mikrofonSedia = devices.some(d => d.kind === 'audioinput');
      if (!mikrofonSedia) {
        rekodSuaraBtn.disabled = true;
        statusRekod.textContent = 'Peranti ini tidak ada mikrofon.';
      }
    });
  } else {
    rekodSuaraBtn.disabled = true;
    statusRekod.textContent = 'Peranti ini tidak menyokong mikrofon.';
  }
}

// =============== Ambil / Upload Gambar ===============
const HAD_SAIZ = 350 * 1024; // 350KB max
const MAKS_GAMBAR = 3;

ambilGambarBtn.addEventListener('click', () => {
  if (!kameraSedia) {
    statusKamera.textContent = 'Kamera tiada atau tidak boleh diakses.';
    return;
  }
  bukaKameraDanAmbilGambar();
});
uploadGambarBtn.addEventListener('click', () => {
  gambarBuktiInput.click();
});
gambarBuktiInput.addEventListener('change', e => {
  const files = e.target.files;
  if (!files.length) return;
  [...files].forEach(file => {
    if (gambarBuktiBase64.length >= MAKS_GAMBAR) return;
    resizeImageFile(file, base64 => {
      gambarBuktiBase64.push(base64);
      paparkanPreviewGambar();
    });
  });
  gambarBuktiInput.value = '';
});
function bukaKameraDanAmbilGambar() {
  const kameraPopup = document.createElement('div');
  kameraPopup.className = 'kamera-popup';
  kameraPopup.style =
    'position:fixed;top:0;left:0;width:100vw;height:100vh;background:#000c;display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:9999;';
  kameraPopup.innerHTML = `
        <video id="videoKamera" autoplay playsinline style="max-width:90vw;max-height:60vh;border-radius:12px;"></video>
        <div style="margin-top:12px;">
          <button id="snapBtn" style="font-size:1.2rem;padding:8px 16px;margin-right:10px;cursor:pointer;">📸 Ambil Foto</button>
          <button id="tutupKameraBtn" style="font-size:1.2rem;padding:8px 16px;cursor:pointer;">Tutup</button>
        </div>
    `;
  document.body.appendChild(kameraPopup);
  const video = kameraPopup.querySelector('#videoKamera');
  const snapBtn = kameraPopup.querySelector('#snapBtn');
  const tutupKameraBtn = kameraPopup.querySelector('#tutupKameraBtn');
  let stream;
  navigator.mediaDevices
    .getUserMedia({ video: { facingMode: 'environment' } })
    .then(s => {
      stream = s;
      video.srcObject = stream;
    })
    .catch(() => {
      statusKamera.textContent = 'Tidak dapat akses kamera. Sila benarkan akses.';
      document.body.removeChild(kameraPopup);
    });
  snapBtn.addEventListener('click', () => {
    if (gambarBuktiBase64.length >= MAKS_GAMBAR) {
      alert('Hanya maksimum 3 gambar sahaja.');
      if (stream) stream.getTracks().forEach(track => track.stop());
      document.body.removeChild(kameraPopup);
      return;
    }
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    resizeImageCanvas(canvas, base64 => {
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
  gambarPreview.innerHTML = gambarBuktiBase64
    .map(
      (src, i) => `<div style="display:inline-block;position:relative;margin:2px;">
            <a href="${src}" target="_blank" rel="noopener noreferrer" title="Buka gambar penuh">
              <img src="${src}" style="max-width:85px;max-height:85px;border-radius:8px;cursor:pointer;">
            </a>
            <button style="position:absolute;top:2px;right:2px;" onclick="padamGambar(${i})" type="button" aria-label="Padam gambar">❌</button>
        </div>`
    )
    .join('');
  padamGambarBtn.style.display = gambarBuktiBase64.length ? 'inline-block' : 'none';
}
window.padamGambar = function (idx) {
  gambarBuktiBase64.splice(idx, 1);
  paparkanPreviewGambar();
};
padamGambarBtn.addEventListener('click', () => {
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
});
// Resize & compress
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
  const MAX_W = 900,
    MAX_H = 900;
  let w = img.width,
    h = img.height;
  if (w > MAX_W || h > MAX_H) {
    const scale = Math.min(MAX_W / w, MAX_H / h);
    w = Math.round(w * scale);
    h = Math.round(h * scale);
  }
  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  canvas.getContext('2d').drawImage(img, 0, 0, w, h);
  return canvas;
}
function resizeImageCanvas(canvas, callback) {
  let quality = 0.85;
  let base64 = canvas.toDataURL('image/jpeg', quality);
  function tryCompress() {
    if (base64.length / 1.37 > HAD_SAIZ && quality > 0.5) {
      quality -= 0.05;
      base64 = canvas.toDataURL('image/jpeg', quality);
      setTimeout(tryCompress, 0);
    } else {
      callback(base64);
    }
  }
  tryCompress();
}

// =============== Suara ke Teks ===============
rekodSuaraBtn.addEventListener('click', () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition || !mikrofonSedia) {
    statusRekod.textContent = 'Peranti ini tidak menyokong suara ke teks atau tiada mikrofon.';
    return;
  }
  const rec = new SpeechRecognition();
  rec.lang = 'ms-MY';
  rec.continuous = false;
  rec.interimResults = false;
  statusRekod.textContent = 'Sila bercakap... (rakaman aktif)';
  rekodSuaraBtn.disabled = true;
  rec.start();
  rec.onresult = function (event) {
    let hasil = event.results[0][0].transcript;
    el('butiranAktiviti').value += (el('butiranAktiviti').value ? '\n' : '') + hasil;
    statusRekod.textContent = 'Rakaman berjaya!';
    rekodSuaraBtn.disabled = false;
  };
  rec.onerror = function () {
    statusRekod.textContent = 'Ralat suara. Sila cuba lagi.';
    rekodSuaraBtn.disabled = false;
  };
  rec.onend = function () {
    statusRekod.textContent = '';
    rekodSuaraBtn.disabled = false;
  };
});

// =============== Simpan, Papar Senarai, Import, Export ===============
simpanBtn.addEventListener('click', () => {
  if (!oprForm.reportValidity()) return;

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
    id: el('oprForm').dataset.editingId ? Number(el('oprForm').dataset.editingId) : Date.now(),
  };

  if (el('oprForm').dataset.editingId) {
    // Edit existing
    const idx = dataOPR.findIndex(x => x.id === data.id);
    if (idx >= 0) {
      dataOPR[idx] = data;
    }
    delete el('oprForm').dataset.editingId;
  } else {
    // New entry
    dataOPR.unshift(data);
  }

  simpanDataLocal();
  paparkanSenaraiOPR();

  oprForm.reset();
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
  padamGambarBtn.style.display = 'none';

  alert('Laporan berjaya disimpan!');
});

function simpanDataLocal() {
  localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
}

function paparkanSenaraiOPR() {
  let list = dataOPR;
  const q = (carianInput.value || '').toLowerCase();

  if (q) {
    list = list.filter(item =>
      Object.values(item)
        .join(' ')
        .toLowerCase()
        .includes(q)
    );
  }
  if (filterBulan && filterBulan.value) {
    list = list.filter(item => {
      const bulan = (item.tarikhMasa || '').slice(5, 7);
      return bulan === filterBulan.value;
    });
  }
  if (filterGuru && filterGuru.value) {
    list = list.filter(item => item.penyedia === filterGuru.value);
  }

  // Update dropdown guru ikut senarai
  const semuaGuru = [...new Set(dataOPR.map(item => item.penyedia).filter(Boolean))].sort();
  filterGuru.innerHTML =
    '<option value="">Semua Guru</option>' +
    semuaGuru.map(guru => `<option value="${escapeHTML(guru)}">${escapeHTML(guru)}</option>`).join('');
  filterGuru.value = filterGuru.value || '';

  if (list.length === 0) {
    listOPR.innerHTML = '<p>Tiada laporan ditemui.</p>';
    return;
  }

  listOPR.innerHTML = list
    .map(
      item => `
      <div class="opr-item" data-id="${item.id}">
        <div class="opr-tajuk" tabindex="0" role="button" aria-pressed="false" onclick="previewLaporan(${item.id})">${escapeHTML(item.namaProgram)}</div>
        <div class="opr-tarikh">${formatTarikh(item.tarikhMasa)}</div>
        <div class="opr-btn-group">
          <button onclick="previewLaporan(${item.id})" aria-label="Preview laporan ${escapeHTML(item.namaProgram)}">Preview</button>
          <button onclick="editLaporan(${item.id})" aria-label="Edit laporan ${escapeHTML(item.namaProgram)}">Edit</button>
          <button onclick="padamLaporan(${item.id})" aria-label="Padam laporan ${escapeHTML(item.namaProgram)}">Padam</button>
        </div>
      </div>
    `
    )
    .join('');
}

carianInput.addEventListener('input', paparkanSenaraiOPR);
filterBulan.addEventListener('change', paparkanSenaraiOPR);
filterGuru.addEventListener('change', paparkanSenaraiOPR);

// =============== Fungsi Preview, Edit, Padam ===============
function previewLaporan(id) {
  const laporan = dataOPR.find(x => x.id === id);
  if (!laporan) return alert('Laporan tidak ditemui.');

  // Buat modal preview laporan cetak
  bukaPreviewModal(laporan);
}

function editLaporan(id) {
  const laporan = dataOPR.find(x => x.id === id);
  if (!laporan) return alert('Laporan tidak ditemui.');

  // Isi borang dengan data
  el('namaProgram').value = laporan.namaProgram;
  el('tarikhMasa').value = laporan.tarikhMasa;
  el('tempat').value = laporan.tempat;
  el('anjuran').value = laporan.anjuran;
  el('penaja').value = laporan.penaja;
  el('sasaran').value = laporan.sasaran;
  el('objektif').value = laporan.objektif;
  el('butiranAktiviti').value = laporan.butiranAktiviti;
  el('pencapaian').value = laporan.pencapaian;
  el('kekuatan').value = laporan.kekuatan;
  el('kelemahan').value = laporan.kelemahan;
  el('intervensi').value = laporan.intervensi;
  el('impak').value = laporan.impak;
  el('kos').value = laporan.kos;
  el('penyedia').value = laporan.penyedia;
  el('penyemak').value = laporan.penyemak;
  el('pengesah').value = laporan.pengesah;

  gambarBuktiBase64 = laporan.gambar.slice();
  paparkanPreviewGambar();
  padamGambarBtn.style.display = gambarBuktiBase64.length ? 'inline-block' : 'none';

  oprForm.dataset.editingId = laporan.id;

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function padamLaporan(id) {
  if (!confirm('Padam laporan ini? Tindakan tidak boleh dibatalkan.')) return;

  dataOPR = dataOPR.filter(x => x.id !== id);
  simpanDataLocal();
  paparkanSenaraiOPR();

  alert('Laporan berjaya dipadam.');
}

// =============== Format Tarikh Masa =================
function formatTarikh(t) {
  if (!t) return '-';
  try {
    const d = new Date(t);
    const hari = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
    return (
      hari[d.getDay()] +
      ', ' +
      d.toLocaleDateString('ms-MY') +
      ' ' +
      d.toLocaleTimeString('ms-MY', { hour: '2-digit', minute: '2-digit' })
    );
  } catch {
    return t;
  }
}

// =============== Escape HTML =================
function escapeHTML(text) {
  if (!text) return '';
  return text.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}

// =============== Dark Mode =================
darkModeBtn.addEventListener('click', () => {
  aktifkanDarkMode();
});
function aktifkanDarkMode(force) {
  const isDark = force !== undefined ? force : !document.body.classList.contains('dark');
  if (isDark) {
    document.body.classList.add('dark');
    darkModeBtn.classList.add('active');
  } else {
    document.body.classList.remove('dark');
    darkModeBtn.classList.remove('active');
  }
  localStorage.setItem('darkMode', isDark ? 'true' : 'false');
}

// =============== CETAK & DOWNLOAD PDF ===============

// Cetak Laporan: guna window.print() cetak preview
cetakBtn.addEventListener('click', () => {
  const editingId = oprForm.dataset.editingId;
  if (editingId) {
    // Jika dalam mod edit, preview borang simpan sebagai laporan cetak
    const laporan = dataOPR.find(x => x.id === Number(editingId));
    if (laporan) {
      bukaPreviewModal(laporan, true);
    } else {
      alert('Tiada laporan untuk cetak.');
    }
  } else {
    window.print();
  }
});

// Download PDF: guna html2pdf.js
downloadPdfBtn.addEventListener('click', () => {
  const editingId = oprForm.dataset.editingId;
  if (editingId) {
    const laporan = dataOPR.find(x => x.id === Number(editingId));
    if (laporan) {
      generatePDFfromData(laporan);
    } else {
      alert('Tiada laporan untuk dimuat turun.');
    }
  } else {
    alert('Sila pilih laporan dengan edit dahulu untuk muat turun PDF.');
  }
});

// Generate PDF dari data laporan
function generatePDFfromData(laporan) {
  // Kita buatkan elemen div tersembunyi yang lengkap laporan dengan kelas cetak, kemudian convert
  let div = document.createElement('div');
  div.style.position = 'fixed';
  div.style.top = '-9999px';
  div.className = 'opr-laporan-print';
  div.innerHTML = buatHTMLLaporan(laporan);
  document.body.appendChild(div);

  // html2pdf.js options
  const opt = {
    margin: [10, 10, 15, 10], // mm top,right,bottom,left
    filename: `Laporan_OPR_${laporan.namaProgram.replace(/[^a-z0-9]/gi, '_')}_${laporan.id}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
  };

  // Pastikan library html2pdf sudah dimuatkan sebelum panggil ini!
  html2pdf().set(opt).from(div).save().then(() => {
    document.body.removeChild(div);
  });
}

// =============== Modal Preview Laporan Cetak =================
function bukaPreviewModal(laporan, untukCetak = false) {
  // Jika modal sudah ada, buang dulu
  const existing = document.getElementById('previewModalOPR');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'previewModalOPR';
  modal.style =
    'position:fixed;top:0;left:0;width:100vw;height:100vh;background:#000a;display:flex;justify-content:center;align-items:center;z-index:10000;overflow:auto;padding:20px;';

  // Container laporan
  const kontena = document.createElement('div');
  kontena.style =
    'background:#fff;padding:24px;border-radius:12px;max-width:800px;min-width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.25);position:relative;';

  kontena.innerHTML = buatHTMLLaporan(laporan);

  // Butang tutup
  const btnTutup = document.createElement('button');
  btnTutup.textContent = '✖ Tutup';
  btnTutup.style =
    'position:absolute;top:12px;right:12px;background:#ea4335;color:#fff;border:none;border-radius:8px;padding:6px 12px;cursor:pointer;font-size:1rem;';
  btnTutup.onclick = () => modal.remove();

  kontena.appendChild(btnTutup);
  modal.appendChild(kontena);
  document.body.appendChild(modal);

  if (untukCetak) {
    setTimeout(() => {
      window.print();
    }, 500);
  }
}

// Buat HTML lengkap laporan untuk preview & cetak
function buatHTMLLaporan(d) {
  // Sembunyikan field kosong dengan data-kosong="true"
  const cekKosong = text => (!text || text.trim() === '' ? 'data-kosong="true"' : '');

  // Gambar bukti
  let gambarHTML = '';
  if (Array.isArray(d.gambar) && d.gambar.length > 0) {
    gambarHTML = `<div class="opr-gambar-preview">` + d.gambar
      .map(
        src => `<img src="${src}" alt="Gambar Bukti Laporan" />`
      )
      .join('') + `</div>`;
  }

  // Tarikh hari ini untuk cop bulat
  const now = new Date();
  const tarikhStr = now.toLocaleDateString('ms-MY', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });

  return `
    <div class="opr-laporan-print">

      <div class="letterhead">
        <img src="assets/logo_sekolah.png" alt="Logo Sekolah" class="logo" />
        <div class="judul-sekolah">SEKOLAH KEBANGSAAN SOKOR</div>
      </div>

      <div class="opr-field" ${cekKosong(d.namaProgram)}>
        <label>Nama Program/Aktiviti:</label> <div>${escapeHTML(d.namaProgram)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.tarikhMasa)}>
        <label>Tarikh & Masa:</label> <div>${formatTarikh(d.tarikhMasa)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.tempat)}>
        <label>Tempat:</label> <div>${escapeHTML(d.tempat)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.anjuran)}>
        <label>Anjuran:</label> <div>${escapeHTML(d.anjuran)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.penaja)}>
        <label>Penaja:</label> <div>${escapeHTML(d.penaja)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.sasaran)}>
        <label>Sasaran:</label> <div>${escapeHTML(d.sasaran)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.objektif)}>
        <label>Objektif:</label> <div>${escapeHTML(d.objektif)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.butiranAktiviti)}>
        <label>Butiran Aktiviti:</label> <div>${escapeHTML(d.butiranAktiviti)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.pencapaian)}>
        <label>Pencapaian:</label> <div>${escapeHTML(d.pencapaian)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.kekuatan)}>
        <label>Kekuatan:</label> <div>${escapeHTML(d.kekuatan)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.kelemahan)}>
        <label>Kelemahan:</label> <div>${escapeHTML(d.kelemahan)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.intervensi)}>
        <label>Cadangan Intervensi:</label> <div>${escapeHTML(d.intervensi)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.impak)}>
        <label>Impak Program:</label> <div>${escapeHTML(d.impak)}</div>
      </div>
      <div class="opr-field" ${cekKosong(d.kos)}>
        <label>Kos / Peralatan / Bahan:</label> <div>${escapeHTML(d.kos)}</div>
      </div>

      ${gambarHTML}

      <div class="tandatangan-cop">
        <div>
          <div class="ruang-tandatangan"></div>
          <div class="ruang-label">Nama Pelapor: ${escapeHTML(d.penyedia)}</div>
        </div>
        <div>
          <div class="ruang-tandatangan"></div>
          <div class="ruang-label">Nama Penyemak: ${escapeHTML(d.penyemak)}</div>
        </div>
        <div>
          <div class="ruang-tandatangan"></div>
          <div class="ruang-label">Nama Pengesah: ${escapeHTML(d.pengesah)}</div>
        </div>
      </div>

      <div class="tandatangan-cop" style="justify-content:center; margin-top: 10px;">
        <div class="cop-bulat">
          <img src="assets/cop_bulat.png" alt="Cop Bulat Sekolah" />
          <div class="tarikh">${tarikhStr}</div>
        </div>
      </div>

      <div style="margin-top: 30px; font-size: 0.85rem; text-align: center; color: #666;">
        Hakcipta GB Mie &copy; 2025. Dikod dengan penuh kasih sayang dan hormat kepada cikgu-cikgu SK Sokor.
      </div>
    </div>
  `;
}

// ==================== Auto Eksport JSON (Backup) ====================
function autoEksportJSON() {
  const blob = new Blob([JSON.stringify(dataOPR, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'opr_data.json';
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 100);
}
downloadBtn.addEventListener('click', autoEksportJSON);

importBtn.addEventListener('click', () => {
  importFile.click();
});
importFile.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function (evt) {
    try {
      const dataBaru = JSON.parse(evt.target.result);
      if (Array.isArray(dataBaru)) {
        const idSet = new Set(dataOPR.map(x => x.id));
        let countBaru = 0;
        dataBaru.forEach(item => {
          if (!idSet.has(item.id)) {
            dataOPR.push(item);
            countBaru++;
          }
        });
        simpanDataLocal();
        paparkanSenaraiOPR();
        alert(
          'Data berjaya digabung!\nLaporan baru dimasukkan: ' +
            countBaru +
            '\nJumlah laporan terkini: ' +
            dataOPR.length
        );
      } else throw new Error();
    } catch {
      alert('Fail tidak sah. Pilih fail eksport OPR (.json) sahaja.');
    }
  };
  reader.readAsText(file);
  importFile.value = '';
});
