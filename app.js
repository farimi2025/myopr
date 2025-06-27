// ==============================
// app.js myOPR (Bersih, Stabil, Mesra Guru)
// ==============================

// -- KONFIG GITHUB --
const GITHUB_TOKEN = "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";
const GITHUB_OWNER = "farimi2025";
const GITHUB_REPO = "myopr";
const GITHUB_BRANCH = "main";
const GITHUB_FILE = "opr_data.json";

// -- UTILITI --
const el = id => document.getElementById(id);

// Compress gambar supaya <80KB, lebar maks 320px
function compressImageTo80KB(fileOrBase64, cb) {
  const img = new Image();
  img.onload = function() {
    // Set lebar maksimum 320px, tinggi 240px
    const MAX_W = 320, MAX_H = 240;
    let w = img.width, h = img.height;
    if (w > MAX_W || h > MAX_H) {
      const scale = Math.min(MAX_W / w, MAX_H / h);
      w = Math.round(w * scale);
      h = Math.round(h * scale);
    }
    const canvas = document.createElement('canvas');
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(img, 0, 0, w, h);

    // Cuba compress beberapa kali untuk <80KB
    let quality = 0.6;
    let base64, blob;
    function tryCompress() {
      base64 = canvas.toDataURL('image/jpeg', quality);
      // Tukar base64 ke Blob untuk kira saiz
      fetch(base64).then(res => res.blob()).then(b => {
        if (b.size > 80000 && quality > 0.2) {
          quality -= 0.08; // Kurangkan kualiti, ulang
          tryCompress();
        } else {
          cb(base64);
        }
      });
    }
    tryCompress();
  };
  if (typeof fileOrBase64 === "string") {
    img.src = fileOrBase64;
  } else {
    const reader = new FileReader();
    reader.onload = e => img.src = e.target.result;
    reader.readAsDataURL(fileOrBase64);
  }
}


function escapeHTML(text) {
  if (!text) return "";
  return text.replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function tarikhHariIni() {
  const now = new Date();
  return now.toLocaleDateString('ms-MY');
}

function nowISO() {
  return new Date().toISOString();
}

function makeID() {
  return 'LPRN-' + Date.now().toString(36) + '-' + Math.floor(Math.random()*99999).toString(36);
}

// -- DATA --
let dataOPR = [];
let gambarArr = [];
let infoSekolah = JSON.parse(localStorage.getItem('infoSekolah')||'{}');
let guruAktif = localStorage.getItem('guruAktif') || '';

// -- UI Papan Atas --
const infoSekolahForm = el('infoSekolahForm');
const oprForm = el('oprForm');
const namaSekolahLabel = el('namaSekolahLabel');
const alamatSekolahLabel = el('alamatSekolahLabel');
const labelGuruAktif = el('labelGuruAktif');
const tukarGuruBtn = el('tukarGuruBtn');

function setInfoSekolahUI() {
  if (namaSekolahLabel) namaSekolahLabel.textContent = infoSekolah.nama || '';
  if (alamatSekolahLabel) alamatSekolahLabel.textContent = infoSekolah.alamat || '';
  if (labelGuruAktif) labelGuruAktif.textContent = guruAktif || '';
}

if (infoSekolah.nama && guruAktif) {
  infoSekolahForm && (infoSekolahForm.style.display = "none");
  oprForm && (oprForm.style.display = "block");
  el('senaraiLaporan') && (el('senaraiLaporan').style.display = "block");
  setInfoSekolahUI();
}

if (infoSekolahForm) {
  infoSekolahForm.onsubmit = e => {
    e.preventDefault();
    const namaSekolah = el('namaSekolah')?.value.trim();
    const alamatSekolah = el('alamatSekolah')?.value.trim();
    const namaGuru = el('namaGuru')?.value.trim();
    if (!namaSekolah || !alamatSekolah || !namaGuru) {
      alert("Sila isi semua medan yang diperlukan!");
      return;
    }
    infoSekolah = { nama: namaSekolah, alamat: alamatSekolah };
    guruAktif = namaGuru;
    localStorage.setItem('infoSekolah', JSON.stringify(infoSekolah));
    localStorage.setItem('guruAktif', guruAktif);
    setInfoSekolahUI();
    infoSekolahForm.style.display = "none";
    oprForm && (oprForm.style.display = "block");
    el('senaraiLaporan') && (el('senaraiLaporan').style.display = "block");
    el('guruPelapor') && (el('guruPelapor').value = guruAktif);
    paparkanSenaraiOPR();
  };
}

tukarGuruBtn && (tukarGuruBtn.onclick = () => {
  infoSekolahForm && (infoSekolahForm.style.display = "block");
  oprForm && (oprForm.style.display = "none");
  el('senaraiLaporan') && (el('senaraiLaporan').style.display = "none");
  el('namaGuru') && (el('namaGuru').value = '');
});

// -- GAMBAR: KAMERA & GALERI --
const ambilGambarBtn = el('ambilGambarBtn');
const uploadGambarBtn = el('uploadGambarBtn');
const gambarBuktiInput = el('gambarBukti');
const gambarPreview = el('gambarPreview');

ambilGambarBtn && (ambilGambarBtn.onclick = async function() {
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Peranti ini tidak menyokong kamera terus.');
      return;
    }
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    const video = document.createElement('video');
    video.srcObject = stream; video.play();
    const modal = document.createElement('div');
    Object.assign(modal.style, {
      position:'fixed', zIndex:99, left:0, top:0, width:'100vw', height:'100vh',
      background:'#111a', display:'flex', flexDirection:'column',
      justifyContent:'center', alignItems:'center'
    });
    modal.innerHTML = `
      <video autoplay playsinline style="max-width:94vw;max-height:60vh;border-radius:13px;box-shadow:0 2px 20px #333a"></video>
      <button id="snapFoto" style="margin-top:12px;padding:12px 24px;">📸 Ambil Gambar</button>
      <button id="tutupCam" style="margin-top:7px;">Tutup</button>`;
    modal.querySelector('video').srcObject = stream;
    document.body.appendChild(modal);
    modal.querySelector('#snapFoto').onclick = () => {
      if (gambarArr.length >= 3) { alert("Maksimum 3 gambar sahaja!"); return; }
      const canvas = document.createElement('canvas');
      canvas.width = video.videoWidth; canvas.height = video.videoHeight;
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      compressImageTo80KB(canvas.toDataURL('image/jpeg', 0.83), (base64) => {
        gambarArr.push(base64);
        paparkanPreviewGambar();
      });
      stream.getTracks().forEach(t => t.stop());
      document.body.removeChild(modal);
    };
    modal.querySelector('#tutupCam').onclick = () => {
      stream.getTracks().forEach(t => t.stop());
      document.body.removeChild(modal);
    };
  } catch (e) {
    alert("Tidak dapat akses kamera.");
  }
});

uploadGambarBtn && (uploadGambarBtn.onclick = () => gambarBuktiInput && gambarBuktiInput.click());

gambarBuktiInput && (gambarBuktiInput.onchange = function(e) {
  let files = Array.from(e.target.files || []);
  if (gambarArr.length + files.length > 3) {
    alert("Maksimum hanya 3 gambar dibenarkan.");
    files = files.slice(0, 3 - gambarArr.length);
  }
  files.forEach(file => {
    // Pastikan guna fungsi compressImageTo80KB, bukan compressImage
    compressImageTo80KB(file, (base64) => {
      if (gambarArr.length < 3) {
        gambarArr.push(base64);
        paparkanPreviewGambar();
      }
    });
  });
  gambarBuktiInput.value = '';
});


function paparkanPreviewGambar() {
  if (gambarPreview) {
    gambarPreview.innerHTML = gambarArr.map((src, i) =>
      `<div style="display:inline-block;position:relative;margin:2px;">
        <img src="${src}" style="width:58px;height:44px;border-radius:8px;object-fit:cover;">
        <button style="position:absolute;top:2px;right:2px;" onclick="padamGambar(${i})" type="button">❌</button>
      </div>`
    ).join('') + `<div style="font-size:0.93em;color:#1565c0;margin-top:3px;">${gambarArr.length} / 3 gambar</div>`;
  }
}
window.padamGambar = idx => { gambarArr.splice(idx, 1); paparkanPreviewGambar(); };

// -- SUARA KE TEKS --
const rekodSuaraBtn = el('rekodSuaraBtn');
const suaraStatus = el('suaraStatus');
rekodSuaraBtn && (rekodSuaraBtn.onclick = () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    suaraStatus && (suaraStatus.textContent = "Peranti ini tidak menyokong suara ke teks.");
    return;
  }
  const rec = new SpeechRecognition();
  rec.lang = "ms-MY"; rec.continuous = false; rec.interimResults = false;
  rekodSuaraBtn.disabled = true;
  suaraStatus && (suaraStatus.textContent = "Sila bercakap...");
  rec.start();
  rec.onresult = function(event) {
    const hasil = event.results[0][0].transcript;
    const butiran = el('butiranAktiviti');
    if (butiran) {
      butiran.value += (butiran.value ? "\n" : "") + hasil;
      suaraStatus && (suaraStatus.textContent = "Rakaman berjaya! Klik semula untuk tambah lagi");
      rekodSuaraBtn.disabled = false;
    }
  };
  rec.onerror = function() {
    suaraStatus && (suaraStatus.textContent = "Ralat suara. Cuba lagi.");
    rekodSuaraBtn.disabled = false;
  };
  rec.onend = function() {
    if (rekodSuaraBtn.disabled) {
      suaraStatus && (suaraStatus.textContent = "");
      rekodSuaraBtn.disabled = false;
    }
  };
});

// -- SIMPAN & EKSPORT/IMPORT --
oprForm && (oprForm.onsubmit = function(e) {
  e.preventDefault();
  // Validasi (ringkas)
  if (gambarArr.length < 1) {
    alert("Sekurang-kurangnya 1 gambar bukti mesti dimasukkan!");
    return;
  }
  if (gambarArr.length > 3) gambarArr = gambarArr.slice(0, 3);

  // Ambil semua nilai input
  const data = {
    id: window.editModeId || makeID(), // <-- Penting: id laporan baru ATAU id sedia ada (edit)
    disimpan_oleh: guruAktif,
    masa_simpan: nowISO(),
    namaSekolah: infoSekolah.nama,
    alamatSekolah: infoSekolah.alamat,
    namaProgram: el('namaProgram').value.trim(),
    tarikhMasa: el('tarikhMasa').value.trim(),
    tempat: el('tempat').value.trim(),
    objektif: el('objektif').value.trim(),
    butiranAktiviti: el('butiranAktiviti').value.trim(),
    impak: el('impak').value.trim() || '',
    intervensi: el('intervensi').value.trim() || '',
    guruPelapor: el('guruPelapor').value.trim() || '',
    pentadbir: el('pentadbir').value.trim() || '',
    gambar: gambarArr.slice(0, 3)
  };

  // Bezakan antara EDIT dan TAMBAH
  if (window.editModeId) {
    // EDIT: Cari dan ganti laporan sedia ada
    const idx = dataOPR.findIndex(x => x.id === window.editModeId);
    if (idx !== -1) dataOPR[idx] = data;
    window.editModeId = null; // Hapus mode edit selepas simpan
    alert("Laporan berjaya dikemaskini!");
  } else {
    // TAMBAH BARU
    dataOPR.unshift(data);
    alert("Laporan berjaya disimpan!");
  }

  // Simpan ke localStorage
  try {
    localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
  } catch (e) {
    alert("❌ Tidak dapat simpan ke penyimpanan tempatan!");
    return;
  }

  gambarArr = [];
  paparkanPreviewGambar();
  oprForm.reset();
  paparkanSenaraiOPR();
});

// RESET EDIT MODE: Letak SELEPAS kod di atas (bukan di dalam function)
oprForm && (oprForm.onreset = function() {
  window.editModeId = null;
});

el('exportBtn') && (el('exportBtn').onclick = function() {
  const blob = new Blob([JSON.stringify(dataOPR, null, 2)], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = "myopr-data.json";
  document.body.appendChild(a); a.click();
  setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
});

const importBtn = el('importBtn'), importFile = el('importFile');
importBtn && importFile && (importBtn.onclick = () => importFile.click());
importFile && (importFile.onchange = function(e) {
  const file = e.target.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = function(evt) {
    try {
      const dataBaru = JSON.parse(evt.target.result);
      if (Array.isArray(dataBaru)) {
        const idSet = new Set(dataOPR.map(x => x.id));
        let countBaru = 0;
        dataBaru.forEach(item => {
          if (!idSet.has(item.id)) { dataOPR.push(item); countBaru++; }
        });
        localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
        paparkanSenaraiOPR();
        alert('✅ Data berjaya digabung!\nLaporan baru: ' + countBaru + '\nJumlah terkini: ' + dataOPR.length);
      } else throw new Error();
    } catch { alert("❌ Fail tidak sah. Pilih fail eksport myOPR (.json) sahaja."); }
  };
  reader.readAsText(file); importFile.value = '';
});

// -- SENARAI SEMUA LAPORAN --
function paparkanSenaraiOPR() {
  let list = dataOPR;

  const listOPR = el('listOPR');
  if (!list.length) { listOPR.innerHTML = "<p>Tiada laporan disimpan.</p>"; return; }
  listOPR.innerHTML = list.map(item => `
    <div class="opr-item" style="cursor:pointer;padding:13px 18px;font-size:1.07em;border-left:4px solid #1565c0;margin-bottom:7px;"
         onclick="previewLaporan(dataOPR.find(x=>x.id==='${item.id}'))">
      <span style="color:#1565c0;font-weight:600;">${escapeHTML(item.namaProgram||'-')}</span>
    </div>
  `).join('');
}
function formatTarikh(t) {
  if (!t) return '-';
  try {
    const d = new Date(t);
    if (isNaN(d.getTime())) return '-';
    const hari = ["Ahad","Isnin","Selasa","Rabu","Khamis","Jumaat","Sabtu"];
    return hari[d.getDay()] + ", " + d.toLocaleDateString('ms-MY') + " " +
        d.toLocaleTimeString('ms-MY', { hour: '2-digit', minute: '2-digit' });
  } catch { return '-'; }
}

// -- MODAL GAMBAR BESAR --
window.bukaGambar = function(src) {
  const modalMedia = el('modalMedia'), mediaContent = el('mediaContent');
  if (modalMedia && mediaContent) {
    modalMedia.style.display = 'flex';
    mediaContent.innerHTML = `<img src="${src}" style="max-width:92vw;max-height:76vh;border-radius:12px;">`;
  }
};
el('tutupMediaBtn') && (el('tutupMediaBtn').onclick = () => {
  const modalMedia = el('modalMedia'); modalMedia && (modalMedia.style.display = 'none');
});

// -- PREVIEW MODAL & CETAK + BUTANG EDIT PADAM --
// ... [semua kod sedia ada, kekal] ...

// -- PREVIEW MODAL & CETAK + EDIT/PADAM (SEMUA BUTANG DALAM MODAL) --
window.previewLaporan = function(data) {
  window.laporanIdAktif = data.id; // Simpan id laporan yang sedang dipreview

  let html = `
    <div style="font-size:1.13rem;font-weight:700;">${escapeHTML(data.namaSekolah)}</div>
    <div style="font-size:0.98rem;color:#555;margin-bottom:5px;">${escapeHTML(data.alamatSekolah)}</div>
    <div style="font-size:1.09rem;font-weight:600;margin-bottom:5px;">
      LAPORAN: ${escapeHTML(data.namaProgram)}
    </div>
    <table style="width:100%;font-size:1.01rem;margin-bottom:7px;">
      <tr><td>Tarikh & Masa</td><td>:</td><td>${formatTarikh(data.tarikhMasa)}</td></tr>
      <tr><td>Tempat</td><td>:</td><td>${escapeHTML(data.tempat)}</td></tr>
    </table>
    <div style="margin-bottom:7px;"><b>Objektif:</b><br>${escapeHTML(data.objektif).replace(/\n/g,"<br>")}</div>
    <div style="margin-bottom:7px;"><b>Butiran:</b><br>${escapeHTML(data.butiranAktiviti).replace(/\n/g,"<br>")}</div>
    <div style="margin-bottom:7px;"><b>Impak/Pencapaian:</b><br>${escapeHTML(data.impak).replace(/\n/g,"<br>")}</div>
    <div style="margin-bottom:7px;"><b>Cadangan Intervensi:</b><br>${escapeHTML(data.intervensi).replace(/\n/g,"<br>")}</div>
    <div class="gambar-cetak">${(data.gambar||[]).map(src=>`<img src="${src}" alt="Gambar Bukti">`).join('')}</div>
    <table style="width:100%;margin:23px 0 18px 0;font-size:0.97em;"><tr>
      <td style="text-align:center;vertical-align:top;">
        <div>Disediakan oleh:</div>
        <div style="height:47px;"></div>
        <u>${escapeHTML(data.guruPelapor)}</u><br>
        <span>(Guru/Penyedia)</span>
        <div style="font-size:0.92em;margin-top:5px;">Tarikh: ${tarikhHariIni()}</div>
      </td>
      <td style="text-align:center;vertical-align:top;">
        <div>Disemak oleh:</div>
        <div style="height:47px;"></div>
        <u>${escapeHTML(data.pentadbir)}</u><br>
        <span>(PK/Guru Besar)</span>
        <div style="font-size:0.92em;margin-top:5px;">Tarikh: ${tarikhHariIni()}</div>
      </td>
      <td style="text-align:center;vertical-align:top;">
        <div>COP SEKOLAH</div>
        <div style="height:62px;display:flex;align-items:center;justify-content:center;">
          <svg width="64" height="64" viewBox="0 0 62 62">
            <circle cx="31" cy="31" r="28" stroke="#888" stroke-width="3" fill="none"/>
            <text x="31" y="36" text-anchor="middle" font-size="11" fill="#888" font-family="Arial">COP SEKOLAH</text>
          </svg>
        </div>
      </td>
    </tr></table>
  `;
  el('modalPreviewContent').innerHTML = html;
  el('modalPreview').style.display = "flex";
  el('modalPreviewActions').style.display = "block";

  // Attach event pada butang-modal sahaja
  el('btnCetakPDF').onclick = function() {
    let printWindow = window.open('', '', 'height=900,width=700');
    printWindow.document.write('<html><head><title>Cetak Laporan</title>');
    printWindow.document.write('<link rel="stylesheet" href="style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(el('modalPreviewContent').innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    setTimeout(function(){ printWindow.print(); }, 400);
  };
  el('editLaporanBtn').onclick = function() {
    if (!window.laporanIdAktif) return;
    const laporan = dataOPR.find(x => x.id === window.laporanIdAktif);
    if (!laporan) return;
    el('modalPreview').style.display = "none";
    el('modalPreviewActions').style.display = "none";
    oprForm && (oprForm.style.display = "block");
    el('namaProgram').value = laporan.namaProgram || "";
    el('tarikhMasa').value = laporan.tarikhMasa || "";
    el('tempat').value = laporan.tempat || "";
    el('objektif').value = laporan.objektif || "";
    el('butiranAktiviti').value = laporan.butiranAktiviti || "";
    el('impak').value = laporan.impak || "";
    el('intervensi').value = laporan.intervensi || "";
    el('guruPelapor').value = laporan.guruPelapor || "";
    el('pentadbir').value = laporan.pentadbir || "";
    gambarArr = (laporan.gambar || []).slice(0,3);
    paparkanPreviewGambar();
    window.editModeId = window.laporanIdAktif;
  };
  el('padamLaporanBtn').onclick = function() {
    if (!window.laporanIdAktif) return;
    if (!confirm("Padam laporan ini? Tindakan tidak boleh diundur.")) return;
    const idx = dataOPR.findIndex(x => x.id === window.laporanIdAktif);
    if (idx !== -1) {
      dataOPR.splice(idx, 1);
      localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
      el('modalPreview').style.display = "none";
      el('modalPreviewActions').style.display = "none";
      paparkanSenaraiOPR();
      alert("Laporan telah dipadam.");
    }
  };
  el('tutupPreviewBtn2').onclick = function() {
    el('modalPreview').style.display = "none";
    el('modalPreviewActions').style.display = "none";
  };
};
// ... [kod lain kekal] ...


el('tutupPreviewBtn2') && (el('tutupPreviewBtn2').onclick = function() {
  el('modalPreview').style.display = "none";
  el('modalPreviewActions').style.display = "none";
});
el('btnCetakPDF') && (el('btnCetakPDF').onclick = function() {
  let printWindow = window.open('', '', 'height=900,width=700');
  printWindow.document.write('<html><head><title>Cetak Laporan</title>');
  printWindow.document.write('<link rel="stylesheet" href="style.css">');
  printWindow.document.write('</head><body>');
  printWindow.document.write(el('modalPreviewContent').innerHTML);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  setTimeout(function(){ printWindow.print(); }, 400);
});

// -- SYNC GITHUB DUA HALA --
const githubBtn = el('githubBtn');
githubBtn && (githubBtn.onclick = syncGitHubTwoWay);
async function syncGitHubTwoWay() {
  const token = GITHUB_TOKEN;
  if (!token) { alert("Token GitHub tidak sah!"); return; }
  try {
    let url = `https://api.github.com/repos/${GITHUB_OWNER}/${GITHUB_REPO}/contents/${GITHUB_FILE}`;
    let githubData = [], sha = "";
    let getRes = await fetch(url, { headers: { "Authorization": "token " + token } });
    if (getRes.ok) {
      const fileData = await getRes.json();
      sha = fileData.sha;
      try {
        githubData = JSON.parse(decodeURIComponent(escape(atob(fileData.content.replace(/\n/g, '')))));
      } catch { githubData = []; }
    }
    let all = Array.isArray(githubData) ? githubData : [];
    const localIds = new Set(dataOPR.map(x => x.id));
    dataOPR.forEach(item => { if (!all.some(x => x.id === item.id)) all.push(item); });
    dataOPR = all;
    localStorage.setItem('dataOPR', JSON.stringify(dataOPR));
    paparkanSenaraiOPR();
    const content = btoa(unescape(encodeURIComponent(JSON.stringify(all, null, 2))));
    const body = { message: "Sync dua hala myOPR", content, branch: GITHUB_BRANCH, ...(sha && { sha }) };
    const putRes = await fetch(url, {
      method: "PUT",
      headers: { "Authorization": "token " + token, "Accept": "application/vnd.github.v3+json" },
      body: JSON.stringify(body)
    });
    if (putRes.ok) alert("✅ Data BERJAYA disync dua hala ke GitHub!");
    else {
      let d = await putRes.json();
      alert("❌ Gagal upload ke GitHub: " + (d && d.message ? d.message : "Ralat tidak diketahui"));
    }
  } catch (e) {
    alert("❌ Gagal sync ke GitHub: " + (e && e.message ? e.message : e));
  }
}

// -- BAKI AKSARA LIVE --
function setupBakiAksara(id, max) {
  const input = el(id), baki = el("baki_"+id);
  if(!input || !baki) return;
  const update = ()=>{ baki.textContent = "Baki aksara: " + (max - input.value.length); };
  input.addEventListener('input', update); update();
}
setupBakiAksara("objektif", 250);
setupBakiAksara("butiranAktiviti", 350);
setupBakiAksara("impak", 200);
setupBakiAksara("intervensi", 160);
setupBakiAksara("namaSekolah", 60);
setupBakiAksara("alamatSekolah", 120);
setupBakiAksara("namaGuru", 40);
setupBakiAksara("namaProgram", 50);
setupBakiAksara("tempat", 40);
setupBakiAksara("guruPelapor", 40);
setupBakiAksara("pentadbir", 40);

el('downloadAllPDFBtn') && (el('downloadAllPDFBtn').onclick = downloadSemuaLaporanPDF);

async function downloadSemuaLaporanPDF() {
  if (!dataOPR.length) {
    alert("Tiada laporan untuk dimuat turun.");
    return;
  }
  let htmlAll = dataOPR.map(data => `
    <div style="page-break-after:always;">
      <div style="font-size:1.13rem;font-weight:700;">${escapeHTML(data.namaSekolah)}</div>
      <div style="font-size:0.98rem;color:#555;margin-bottom:5px;">${escapeHTML(data.alamatSekolah)}</div>
      <div style="font-size:1.09rem;font-weight:600;margin-bottom:5px;">
        LAPORAN: ${escapeHTML(data.namaProgram)}
      </div>
      <table style="width:100%;font-size:1.01rem;margin-bottom:7px;">
        <tr><td>Tarikh & Masa</td><td>:</td><td>${formatTarikh(data.tarikhMasa)}</td></tr>
        <tr><td>Tempat</td><td>:</td><td>${escapeHTML(data.tempat)}</td></tr>
      </table>
      <div style="margin-bottom:7px;"><b>Objektif:</b><br>${escapeHTML(data.objektif).replace(/\n/g,"<br>")}</div>
      <div style="margin-bottom:7px;"><b>Butiran:</b><br>${escapeHTML(data.butiranAktiviti).replace(/\n/g,"<br>")}</div>
      <div style="margin-bottom:7px;"><b>Impak/Pencapaian:</b><br>${escapeHTML(data.impak).replace(/\n/g,"<br>")}</div>
      <div style="margin-bottom:7px;"><b>Cadangan Intervensi:</b><br>${escapeHTML(data.intervensi).replace(/\n/g,"<br>")}</div>
      <div class="gambar-cetak">${
        Array.isArray(data.gambar)
          ? data.gambar.map(src=>`<img src="${src}" alt="Gambar Bukti" style="max-width:30%;max-height:120px;border-radius:8px;border:1px solid #ccc;margin-right:4px;">`).join('')
          : ''
      }</div>
      <table style="width:100%;margin:23px 0 18px 0;font-size:0.97em;"><tr>
        <td style="text-align:center;vertical-align:top;">
          <div>Disediakan oleh:</div>
          <div style="height:47px;"></div>
          <u>${escapeHTML(data.guruPelapor)}</u><br>
          <span>(Guru/Penyedia)</span>
          <div style="font-size:0.92em;margin-top:5px;">Tarikh: ${tarikhHariIni()}</div>
        </td>
        <td style="text-align:center;vertical-align:top;">
          <div>Disemak oleh:</div>
          <div style="height:47px;"></div>
          <u>${escapeHTML(data.pentadbir)}</u><br>
          <span>(PK/Guru Besar)</span>
          <div style="font-size:0.92em;margin-top:5px;">Tarikh: ${tarikhHariIni()}</div>
        </td>
        <td style="text-align:center;vertical-align:top;">
          <div>COP SEKOLAH</div>
          <div style="height:62px;display:flex;align-items:center;justify-content:center;">
            <svg width="64" height="64" viewBox="0 0 62 62">
              <circle cx="31" cy="31" r="28" stroke="#888" stroke-width="3" fill="none"/>
              <text x="31" y="36" text-anchor="middle" font-size="11" fill="#888" font-family="Arial">COP SEKOLAH</text>
            </svg>
          </div>
        </td>
      </tr></table>
    </div>
  `).join('<div style="height:24px"></div>');

  let win = window.open('', '', 'height=900,width=700');
  win.document.write('<html><head><title>Semua Laporan OPR</title>');
  win.document.write('<link rel="stylesheet" href="style.css">');
  win.document.write('</head><body>');
  win.document.write(htmlAll);
  win.document.write('</body></html>');
  win.document.close();
  setTimeout(()=>{ win.print(); }, 700);
}


// -- INIT: PAPAR DATA --
window.addEventListener('DOMContentLoaded', () => {
  try {
    if (localStorage.getItem('dataOPR')) dataOPR = JSON.parse(localStorage.getItem('dataOPR')) || [];
  } catch { dataOPR = []; }
  paparkanSenaraiOPR();
});
