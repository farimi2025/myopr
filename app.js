// ==========================
// app.js - Kod Utama Web App PWA OPR Sekolah
// ==========================

// Dapatkan elemen DOM dengan mudah
const el = id => document.getElementById(id);

// --- Elemen utama ---
const simpanBtn = el('simpanBtn');
const downloadBtn = el('downloadBtn');
const cetakBtn = el('cetakBtn');
const downloadPdfBtn = el('downloadPdfBtn');
const downloadDocxBtn = el('downloadDocxBtn');
const syncGithubBtn = el('syncGithubBtn');
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

const bukaModalSekolahBtn = el('bukaModalSekolahBtn');
const modalSekolah = el('modalSekolah');
const tutupModalSekolahBtn = el('tutupModalSekolahBtn');
const resetModalSekolahBtn = el('resetModalSekolahBtn');

const formSekolahGuru = el('formSekolahGuru');
const namaSekolahInput = el('namaSekolah');
const alamatSekolahInput = el('alamatSekolah');
const logoSekolahInput = el('logoSekolah');
const logoPreview = el('logoPreview');
const guruPelaporInput = el('guruPelapor');
const guruPenyemakInput = el('guruPenyemak');
const guruPengesahInput = el('guruPengesah');

// --- Data simpanan ---
let dataOPR = [];
let gambarBuktiBase64 = [];

let dataSekolahGuru = {
  namaSekolah: '',
  alamatSekolah: '',
  logoSekolahBase64: '',
  guruPelapor: '',
  guruPenyemak: '',
  guruPengesah: ''
};

// --- Utility fungsi ---
function escapeHTML(text) {
  if (!text) return '';
  return text.replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[m]));
}
function formatTarikh(t) {
  if (!t) return '-';
  try {
    const d = new Date(t);
    const hari = ["Ahad", "Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu"];
    return hari[d.getDay()] + ", " + d.toLocaleDateString('ms-MY') + " " +
      d.toLocaleTimeString('ms-MY', { hour: '2-digit', minute: '2-digit' });
  } catch { return t; }
}

// --- Load & Simpan data sekolah/guru ---
function loadDataSekolahGuru() {
  try {
    const s = localStorage.getItem('dataSekolahGuru');
    if (s) dataSekolahGuru = JSON.parse(s);
  } catch { dataSekolahGuru = {...dataSekolahGuru}; }
}
function simpanDataSekolahGuru() {
  localStorage.setItem('dataSekolahGuru', JSON.stringify(dataSekolahGuru));
}
function isiFormSekolahGuru() {
  namaSekolahInput.value = dataSekolahGuru.namaSekolah || '';
  alamatSekolahInput.value = dataSekolahGuru.alamatSekolah || '';
  guruPelaporInput.value = dataSekolahGuru.guruPelapor || '';
  guruPenyemakInput.value = dataSekolahGuru.guruPenyemak || '';
  guruPengesahInput.value = dataSekolahGuru.guruPengesah || '';
  if (dataSekolahGuru.logoSekolahBase64) {
    logoPreview.innerHTML = `<img src="${dataSekolahGuru.logoSekolahBase64}" alt="Logo Sekolah" style="max-width:120px; max-height:120px; border-radius:12px;">`;
  } else {
    logoPreview.innerHTML = '';
  }
}

// --- Load & Simpan data OPR ---
function loadDataOPR() {
  try {
    const d = localStorage.getItem('dataOPR');
    if (d) dataOPR = JSON.parse(d);
  } catch { dataOPR = []; }
}
function simpanDataOPR() {
  localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
}

// --- Papar senarai laporan ---
function paparkanSenaraiOPR() {
  let list = dataOPR.slice();
  const q = (carianInput.value || '').toLowerCase();

  if (q) {
    list = list.filter(item =>
      Object.values(item).join(' ').toLowerCase().includes(q)
    );
  }
  if (filterBulan.value) {
    list = list.filter(item => (item.tarikhMasa || '').slice(5, 7) === filterBulan.value);
  }
  if (filterGuru.value) {
    list = list.filter(item => item.penyedia === filterGuru.value);
  }

  const semuaGuru = [...new Set(dataOPR.map(x => x.penyedia).filter(Boolean))].sort();
  filterGuru.innerHTML = '<option value="">Semua Guru</option>' +
    semuaGuru.map(g => `<option value="${escapeHTML(g)}">${escapeHTML(g)}</option>`).join('');
  filterGuru.value = filterGuru.value || '';

  if (list.length === 0) {
    listOPR.innerHTML = '<p>Tiada laporan ditemui.</p>';
    return;
  }

  listOPR.innerHTML = list.map(item => `
    <div class="opr-item" data-id="${item.id}">
      <span class="opr-tarikh">${formatTarikh(item.tarikhMasa)}</span>
      <div class="opr-tajuk">${escapeHTML(item.namaProgram)}</div>
      <div class="opr-guru">Tempat: ${escapeHTML(item.tempat)}${item.sasaran ? ' | Sasaran: ' + escapeHTML(item.sasaran) : ''}</div>
      <div class="opr-ringkasan">
        <b>Objektif:</b> ${escapeHTML(item.objektif)}<br>
        <b>Butiran:</b> ${escapeHTML(item.butiranAktiviti)}<br>
        <b>Pencapaian:</b> ${escapeHTML(item.pencapaian)}<br>
        <b>Kekuatan:</b> ${escapeHTML(item.kekuatan)}<br>
        <b>Kelemahan:</b> ${escapeHTML(item.kelemahan)}<br>
        <b>Cadangan Intervensi:</b> ${escapeHTML(item.intervensi)}<br>
        <b>Impak:</b> ${escapeHTML(item.impak)}<br>
        <b>Kos/Peralatan:</b> ${escapeHTML(item.kos)}<br>
        ${item.penaja ? `<div><b>Penaja:</b> ${escapeHTML(item.penaja)}</div>` : ''}
        ${item.anjuran ? `<div><b>Anjuran:</b> ${escapeHTML(item.anjuran)}</div>` : ''}
      </div>
      ${(Array.isArray(item.gambar) && item.gambar.length) ? item.gambar.map((gm, i) => `
        <img src="${gm}" alt="Gambar Bukti" style="max-width:85px; max-height:85px; border-radius:8px; margin:2px; cursor:pointer;" onclick="bukaImejTabBaru('${encodeURIComponent(gm)}')" title="Klik untuk buka imej penuh">
      `).join('') : ''}
      <div class="opr-guru" style="margin-top:8px;">
        <i>Penyedia: ${escapeHTML(item.penyedia)}${item.penyemak ? ', Penyemak: ' + escapeHTML(item.penyemak) : ''}${item.pengesah ? ', Pengesah: ' + escapeHTML(item.pengesah) : ''}</i>
      </div>
    </div>
  `).join('');
}

// --- Buka imej penuh dalam tab baru ---
window.bukaImejTabBaru = function(base64StrEncoded) {
  const base64Str = decodeURIComponent(base64StrEncoded);
  const w = window.open('');
  if (!w) return alert('Sila benarkan popup untuk membuka imej.');
  const html = `<img src="${base64Str}" style="max-width:100%; height:auto;" /><br><a href="${base64Str}" download="gambar_bukti.png">Muat Turun Gambar</a>`;
  w.document.write(html);
  w.document.title = 'Imej Bukti';
}

// --- Kamera & upload gambar ---
let kameraSedia = false;

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
    if (gambarBuktiBase64.length >= 3) return alert('Hanya maksimum 3 gambar sahaja.');
    resizeImageFile(file, base64 => {
      gambarBuktiBase64.push(base64);
      paparkanPreviewGambar();
    });
  });
  gambarBuktiInput.value = '';
});
padamGambarBtn.addEventListener('click', () => {
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
});
window.padamGambar = function(idx) {
  gambarBuktiBase64.splice(idx, 1);
  paparkanPreviewGambar();
};
function paparkanPreviewGambar() {
  gambarPreview.innerHTML = gambarBuktiBase64.map((src, i) =>
    `<div style="display:inline-block;position:relative;margin:2px;">
      <img src="${src}" style="max-width:85px;max-height:85px;border-radius:8px;cursor:pointer;" onclick="bukaImejTabBaru('${encodeURIComponent(src)}')" title="Klik untuk buka imej penuh" />
      <button style="position:absolute;top:2px;right:2px;" onclick="padamGambar(${i})" type="button" aria-label="Padam Gambar">❌</button>
    </div>`
  ).join('');
  padamGambarBtn.style.display = gambarBuktiBase64.length ? 'inline-block' : 'none';
}

// --- Resize dan compress gambar ---
const MAX_W = 900, MAX_H = 900, MAX_SIZE = 350 * 1024;
function resizeImageFile(file, callback) {
  const reader = new FileReader();
  reader.onload = evt => {
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement('canvas');
      let w = img.width;
      let h = img.height;
      if (w > MAX_W || h > MAX_H) {
        const scale = Math.min(MAX_W / w, MAX_H / h);
        w = Math.round(w * scale);
        h = Math.round(h * scale);
      }
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);

      let quality = 0.85;
      function compress() {
        const base64 = canvas.toDataURL('image/png', quality);
        if (base64.length * (3 / 4) > MAX_SIZE && quality > 0.5) {
          quality -= 0.05;
          setTimeout(compress, 10);
        } else {
          callback(base64);
        }
      }
      compress();
    };
    img.src = evt.target.result;
  };
  reader.readAsDataURL(file);
}

// --- Fungsi buka kamera dan ambil gambar ---
function bukaKameraDanAmbilGambar() {
  const kameraPopup = document.createElement('div');
  kameraPopup.className = 'kamera-popup';
  kameraPopup.style = `
    position:fixed;top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.8);
    display:flex;flex-direction:column;
    justify-content:center;align-items:center;
    z-index:10000;
  `;
  kameraPopup.innerHTML = `
    <video id="videoKamera" autoplay playsinline style="max-width:100%;border-radius:12px;"></video>
    <div style="margin-top:12px;">
      <button id="snapBtn" style="margin-right:12px;padding:8px 20px;font-size:1.1rem;">📸 Ambil Foto</button>
      <button id="tutupKameraBtn" style="padding:8px 20px;font-size:1.1rem;">Tutup</button>
    </div>
  `;
  document.body.appendChild(kameraPopup);

  const video = kameraPopup.querySelector('#videoKamera');
  const snapBtn = kameraPopup.querySelector('#snapBtn');
  const tutupKameraBtn = kameraPopup.querySelector('#tutupKameraBtn');
  let stream;

  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(s => {
      stream = s;
      video.srcObject = stream;
    })
    .catch(() => {
      statusKamera.textContent = "Tidak dapat akses kamera. Sila benarkan akses.";
      document.body.removeChild(kameraPopup);
    });

  snapBtn.addEventListener('click', () => {
    if (gambarBuktiBase64.length >= 3) {
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
function resizeImageCanvas(canvas, callback) {
  let quality = 0.85;
  let base64 = canvas.toDataURL('image/png', quality);
  function tryCompress() {
    if (base64.length * (3 / 4) > MAX_SIZE && quality > 0.5) {
      quality -= 0.05;
      base64 = canvas.toDataURL('image/png', quality);
      setTimeout(tryCompress, 10);
    } else {
      callback(base64);
    }
  }
  tryCompress();
}

// --- Simpan laporan ---
simpanBtn.addEventListener('click', () => {
  if (!oprForm.checkValidity()) {
    alert('Sila lengkapkan semua maklumat wajib sebelum simpan.');
    return;
  }

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
  simpanDataOPR();
  paparkanSenaraiOPR();

  oprForm.reset();
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
  padamGambarBtn.style.display = 'none';

  alert('Laporan berjaya disimpan!');
});

// --- Download semua data JSON ---
downloadBtn.addEventListener('click', () => {
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
});

// --- Cetak preview ---
cetakBtn.addEventListener('click', () => {
  window.print();
});

// --- Export PDF ---
downloadPdfBtn.addEventListener('click', () => {
  if (dataOPR.length === 0) {
    alert('Tiada laporan untuk dijana PDF.');
    return;
  }
  // Panggil fungsi generatePDF dari pdf-generator.js
  generatePDF(dataOPR[0]);
});

// --- Export DOCX ---
downloadDocxBtn.addEventListener('click', () => {
  if (dataOPR.length === 0) {
    alert('Tiada laporan untuk dijana DOCX.');
    return;
  }
  // Panggil fungsi generateDOCX dari docx-generator.js
  generateDOCX(dataOPR[0]);
});

// --- Sync GitHub ---
syncGithubBtn.addEventListener('click', async () => {
  try {
    await syncWithGitHub();
    alert('Sinkronisasi dengan GitHub berjaya!');
    loadDataOPR();
    paparkanSenaraiOPR();
  } catch (err) {
    alert('Sinkronisasi gagal: ' + err.message);
  }
});

// --- Modal Maklumat Sekolah & Guru ---
bukaModalSekolahBtn.addEventListener('click', () => {
  isiFormSekolahGuru();
  modalSekolah.style.display = 'flex';
});
tutupModalSekolahBtn.addEventListener('click', () => {
  modalSekolah.style.display = 'none';
});
resetModalSekolahBtn.addEventListener('click', () => {
  if (confirm('Padam semua maklumat sekolah & guru? Tindakan ini tidak boleh dibatalkan.')) {
    dataSekolahGuru = {
      namaSekolah: '',
      alamatSekolah: '',
      logoSekolahBase64: '',
      guruPelapor: '',
      guruPenyemak: '',
      guruPengesah: ''
    };
    simpanDataSekolahGuru();
    isiFormSekolahGuru();
  }
});

// --- Simpan maklumat sekolah & guru dari modal ---
formSekolahGuru.addEventListener('submit', e => {
  e.preventDefault();
  dataSekolahGuru.namaSekolah = namaSekolahInput.value.trim();
  dataSekolahGuru.alamatSekolah = alamatSekolahInput.value.trim();
  dataSekolahGuru.guruPelapor = guruPelaporInput.value.trim();
  dataSekolahGuru.guruPenyemak = guruPenyemakInput.value.trim();
  dataSekolahGuru.guruPengesah = guruPengesahInput.value.trim();
  simpanDataSekolahGuru();
  alert('Maklumat sekolah & guru berjaya disimpan!');
  modalSekolah.style.display = 'none';

  // Auto isi nama pelapor, penyemak, pengesah dalam borang laporan
  el('penyedia').value = dataSekolahGuru.guruPelapor;
  el('penyemak').value = dataSekolahGuru.guruPenyemak;
  el('pengesah').value = dataSekolahGuru.guruPengesah;
});

// --- Preview logo sekolah dalam modal ---
logoSekolahInput.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) {
    logoPreview.innerHTML = '';
    dataSekolahGuru.logoSekolahBase64 = '';
    simpanDataSekolahGuru();
    return;
  }
  if (!file.type.startsWith('image/')) {
    alert('Sila pilih fail imej PNG atau JPEG sahaja.');
    logoSekolahInput.value = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = evt => {
    dataSekolahGuru.logoSekolahBase64 = evt.target.result;
    logoPreview.innerHTML = `<img src="${dataSekolahGuru.logoSekolahBase64}" alt="Logo Sekolah" style="max-width:120px; max-height:120px; border-radius:12px;">`;
    simpanDataSekolahGuru();
  };
  reader.readAsDataURL(file);
});

// --- Dark mode toggle ---
darkModeBtn.addEventListener('click', () => {
  const isDark = document.body.classList.toggle('dark');
  darkModeBtn.classList.toggle('active', isDark);
  localStorage.setItem('darkMode', isDark ? 'true' : 'false');
});

// --- Init aplikasi ---
window.addEventListener('DOMContentLoaded', () => {
  loadDataOPR();
  loadDataSekolahGuru();

  paparkanSenaraiOPR();
  isiFormSekolahGuru();
  periksaKamera();

  el('penyedia').value = dataSekolahGuru.guruPelapor || '';
  el('penyemak').value = dataSekolahGuru.guruPenyemak || '';
  el('pengesah').value = dataSekolahGuru.guruPengesah || '';

  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark');
    darkModeBtn.classList.add('active');
  }
});

// --- Events carian & filter ---
carianInput.addEventListener('input', paparkanSenaraiOPR);
filterBulan.addEventListener('change', paparkanSenaraiOPR);
filterGuru.addEventListener('change', paparkanSenaraiOPR);
