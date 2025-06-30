// ==========================
// APP.JS OPR SEKOLAH VERSI MESRA GURU & STABIL
// ==========================

const el = (id) => document.getElementById(id);

const simpanBtn = el("simpanBtn");
const downloadBtn = el("downloadBtn");
const cetakBtn = el("cetakBtn");
const darkModeBtn = el("darkModeBtn");

const oprForm = el("oprForm");
const carianInput = el("carianInput");
const listOPR = el("listOPR");

const ambilGambarBtn = el("ambilGambarBtn");
const uploadGambarBtn = el("uploadGambarBtn");
const gambarBuktiInput = el("gambarBukti");
const gambarPreview = el("gambarPreview");
const padamGambarBtn = el("padamGambarBtn");
const statusKamera = el("statusKamera");

const formSekolah = el("formSekolah");
const namaSekolahInput = el("namaSekolah");
const alamatSekolahInput = el("alamatSekolah");
const logoSekolahInput = el("logoSekolah");
const previewLogoDiv = el("previewLogo");
const guruPelaporInput = el("guruPelapor");
const guruPengesahInput = el("guruPengesah");

// =============== DATA ===============
let dataOPR = [];
let gambarBuktiBase64 = []; // max 4 gambar
const MAKS_GAMBAR = 4;

// =============== LOCAL STORAGE KELOLA DATA SEKOLAH & GURU ===============
function simpanDataSekolah() {
  const dataSekolah = {
    namaSekolah: namaSekolahInput.value.trim(),
    alamatSekolah: alamatSekolahInput.value.trim(),
    logoSekolah: previewLogoDiv.querySelector("img")?.src || "",
    guruPelapor: guruPelaporInput.value.trim(),
    guruPengesah: guruPengesahInput.value.trim(),
  };
  localStorage.setItem("dataSekolah", JSON.stringify(dataSekolah));
  alert("Maklumat sekolah dan guru telah disimpan!");
}

function muatDataSekolah() {
  try {
    const dataSekolah = JSON.parse(localStorage.getItem("dataSekolah")) || {};
    namaSekolahInput.value = dataSekolah.namaSekolah || "";
    alamatSekolahInput.value = dataSekolah.alamatSekolah || "";
    guruPelaporInput.value = dataSekolah.guruPelapor || "";
    guruPengesahInput.value = dataSekolah.guruPengesah || "";
    if (dataSekolah.logoSekolah) {
      previewLogoDiv.innerHTML = `<img src="${dataSekolah.logoSekolah}" alt="Logo Sekolah" style="max-width:100px;max-height:100px;border-radius:8px;">`;
    }
  } catch {
    // fail silently
  }
}

formSekolah.addEventListener("submit", (e) => {
  e.preventDefault();
  simpanDataSekolah();
});

// Logo Sekolah preview & convert ke base64
logoSekolahInput.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (evt) => {
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement("canvas");
      const MAX_DIM = 200;
      let w = img.width;
      let h = img.height;
      if (w > h) {
        if (w > MAX_DIM) {
          h = Math.round((h * MAX_DIM) / w);
          w = MAX_DIM;
        }
      } else {
        if (h > MAX_DIM) {
          w = Math.round((w * MAX_DIM) / h);
          h = MAX_DIM;
        }
      }
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, w, h);
      const base64 = canvas.toDataURL("image/png");
      previewLogoDiv.innerHTML = `<img src="${base64}" alt="Logo Sekolah" style="max-width:100px;max-height:100px;border-radius:8px;">`;
      logoSekolahInput.value = "";
    };
    img.src = evt.target.result;
  };
  reader.readAsDataURL(file);
});

// =============== GAMBAR BUKTI ===============

function resizeImageFile(file, callback) {
  const reader = new FileReader();
  reader.onload = (evt) => {
    const img = new Image();
    img.onload = () => {
      resizeImageCanvas(img, callback);
    };
    img.src = evt.target.result;
  };
  reader.readAsDataURL(file);
}
function resizeImageCanvas(img, callback) {
  const MAX_W = 900,
    MAX_H = 900;
  let w = img.width,
    h = img.height;
  if (w > MAX_W || h > MAX_H) {
    const scale = Math.min(MAX_W / w, MAX_H / h);
    w = Math.round(w * scale);
    h = Math.round(h * scale);
  }
  const canvas = document.createElement("canvas");
  canvas.width = w;
  canvas.height = h;
  canvas.getContext("2d").drawImage(img, 0, 0, w, h);

  let quality = 0.85;
  let base64 = canvas.toDataURL("image/png", quality);
  function tryCompress() {
    if (base64.length / 1.37 > 300 * 1024 && quality > 0.5) {
      quality -= 0.05;
      base64 = canvas.toDataURL("image/png", quality);
      setTimeout(tryCompress, 0);
    } else {
      callback(base64);
    }
  }
  tryCompress();
}

ambilGambarBtn.addEventListener("click", () => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    statusKamera.textContent = "Kamera tidak disokong oleh peranti ini.";
    return;
  }
  bukaKameraDanAmbilGambar();
});
uploadGambarBtn.addEventListener("click", () => {
  gambarBuktiInput.click();
});
gambarBuktiInput.addEventListener("change", (e) => {
  const files = e.target.files;
  if (!files.length) return;
  [...files].forEach((file) => {
    if (gambarBuktiBase64.length >= MAKS_GAMBAR) return;
    resizeImageFile(file, (base64) => {
      gambarBuktiBase64.push(base64);
      paparkanPreviewGambar();
    });
  });
  gambarBuktiInput.value = "";
});

function bukaKameraDanAmbilGambar() {
  const kameraPopup = document.createElement("div");
  kameraPopup.className = "kamera-popup";
  kameraPopup.innerHTML = `
        <video id="videoKamera" autoplay playsinline></video>
        <button id="snapBtn">📸 Ambil Foto</button>
        <button id="tutupKameraBtn">Tutup</button>
    `;
  document.body.appendChild(kameraPopup);
  const video = kameraPopup.querySelector("#videoKamera");
  const snapBtn = kameraPopup.querySelector("#snapBtn");
  const tutupKameraBtn = kameraPopup.querySelector("#tutupKameraBtn");
  let stream;
  navigator.mediaDevices
    .getUserMedia({ video: { facingMode: "environment" } })
    .then((s) => {
      stream = s;
      video.srcObject = stream;
    })
    .catch(() => {
      statusKamera.textContent = "Tidak dapat akses kamera. Sila benarkan akses.";
      document.body.removeChild(kameraPopup);
    });
  snapBtn.addEventListener("click", () => {
    if (gambarBuktiBase64.length >= MAKS_GAMBAR) {
      alert("Hanya maksimum 4 gambar sahaja.");
      if (stream) stream.getTracks().forEach((track) => track.stop());
      document.body.removeChild(kameraPopup);
      return;
    }
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0, canvas.width, canvas.height);
    resizeImageCanvas(canvas, (base64) => {
      gambarBuktiBase64.push(base64);
      paparkanPreviewGambar();
    });
    if (stream) stream.getTracks().forEach((track) => track.stop());
    document.body.removeChild(kameraPopup);
  });
  tutupKameraBtn.addEventListener("click", () => {
    if (stream) stream.getTracks().forEach((track) => track.stop());
    document.body.removeChild(kameraPopup);
  });
}
function paparkanPreviewGambar() {
  gambarPreview.innerHTML = gambarBuktiBase64
    .map(
      (src, i) => `
        <div style="display:inline-block;position:relative;margin:2px;">
            <img src="${src}" style="max-width:85px;max-height:85px;border-radius:8px;cursor:pointer;" onclick="window.open('${src}', '_blank')">
            <button style="position:absolute;top:2px;right:2px;" onclick="padamGambar(${i})" type="button">❌</button>
        </div>`
    )
    .join("");
  padamGambarBtn.style.display = gambarBuktiBase64.length ? "inline-block" : "none";
}
window.padamGambar = function (idx) {
  gambarBuktiBase64.splice(idx, 1);
  paparkanPreviewGambar();
};
padamGambarBtn.addEventListener("click", () => {
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
});

// =============== SIMPAN, MUAT & PAPAR DATA OPR ===============

function simpanDataLocal() {
  localStorage.setItem("dataOPR", JSON.stringify(dataOPR));
}

function muatDataLocal() {
  try {
    const data = JSON.parse(localStorage.getItem("dataOPR"));
    if (Array.isArray(data)) dataOPR = data;
  } catch {
    dataOPR = [];
  }
}

function paparkanSenaraiOPR() {
  let list = dataOPR;
  const q = (carianInput.value || "").toLowerCase();

  if (q) {
    list = list.filter((item) =>
      Object.values(item)
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  if (list.length === 0) {
    listOPR.innerHTML = "<p>Tiada laporan ditemui.</p>";
    return;
  }
  listOPR.innerHTML = list
    .map(
      (item) => `
    <div class="opr-item">
      <span class="opr-tarikh">${formatTarikh(item.tarikhMasa)}</span>
      <div class="opr-tajuk">${escapeHTML(item.namaProgram)}</div>
      <div class="opr-guru">
        Tempat: ${escapeHTML(item.tempat)}${
        item.sasaran ? " | Sasaran: " + escapeHTML(item.sasaran) : ""
      }
      </div>
      <div class="opr-ringkasan">
        <b>Objektif:</b> ${escapeHTML(item.objektif)}<br>
        <b>Butiran:</b> ${escapeHTML(item.butiranAktiviti)}<br>
        <b>Pencapaian:</b> ${escapeHTML(item.pencapaian)}<br>
        <b>Kekuatan:</b> ${escapeHTML(item.kekuatan)}<br>
        <b>Kelemahan:</b> ${escapeHTML(item.kelemahan)}<br>
        <b>Cadangan Intervensi:</b> ${escapeHTML(item.intervensi)}<br>
        <b>Impak:</b> ${escapeHTML(item.impak)}<br>
        <b>Kos/Peralatan:</b> ${escapeHTML(item.kos)}<br>
        ${
          item.penaja
            ? `<div><b>Penaja:</b> ${escapeHTML(item.penaja)}</div>`
            : ""
        }
        ${
          item.anjuran
            ? `<div><b>Anjuran:</b> ${escapeHTML(item.anjuran)}</div>`
            : ""
        }
      </div>
      ${
        Array.isArray(item.gambar) && item.gambar.length
          ? item.gambar
              .map(
                (gm) =>
                  `<img src="${gm}" alt="Gambar Bukti" style="max-width:100px;margin:2px;border-radius:8px;cursor:pointer;" onclick="window.open('${gm}', '_blank')">`
              )
              .join("")
          : ""
      }
      <div class="opr-guru">
        <i>Penyedia: ${escapeHTML(item.penyedia)}${
        item.penyemak ? ", Penyemak: " + escapeHTML(item.penyemak) : ""
      }${item.pengesah ? ", Pengesah: " + escapeHTML(item.pengesah) : ""}</i>
      </div>
      <div style="margin-top:8px;">
        <button onclick="editLaporan(${item.id})" type="button">✏️ Edit</button>
        <button onclick="padamLaporan(${item.id})" type="button">🗑️ Padam</button>
      </div>
    </div>
  `
    )
    .join("");
}

carianInput.addEventListener("input", paparkanSenaraiOPR);

// Fungsi Edit & Padam Laporan
window.editLaporan = function (id) {
  const laporan = dataOPR.find((item) => item.id === id);
  if (!laporan) return alert("Laporan tidak ditemui.");
  // Isi semula borang laporan
  el("namaProgram").value = laporan.namaProgram;
  el("tarikhMasa").value = laporan.tarikhMasa;
  el("tempat").value = laporan.tempat;
  el("anjuran").value = laporan.anjuran;
  el("penaja").value = laporan.penaja;
  el("sasaran").value = laporan.sasaran;
  el("objektif").value = laporan.objektif;
  el("butiranAktiviti").value = laporan.butiranAktiviti;
  el("pencapaian").value = laporan.pencapaian;
  el("kekuatan").value = laporan.kekuatan;
  el("kelemahan").value = laporan.kelemahan;
  el("intervensi").value = laporan.intervensi;
  el("impak").value = laporan.impak;
  el("kos").value = laporan.kos;
  el("penyedia").value = laporan.penyedia;
  el("penyemak").value = laporan.penyemak;
  el("pengesah").value = laporan.pengesah;

  gambarBuktiBase64 = [...laporan.gambar];
  paparkanPreviewGambar();

  // Tukar button simpan untuk kemaskini
  simpanBtn.textContent = "Kemaskini Laporan";
  simpanBtn.dataset.editingId = id;
};

window.padamLaporan = function (id) {
  if (!confirm("Adakah anda pasti mahu padam laporan ini?")) return;
  dataOPR = dataOPR.filter((item) => item.id !== id);
  simpanDataLocal();
  paparkanSenaraiOPR();
  alert("Laporan berjaya dipadam.");
};

// Simpan Laporan baru / kemaskini
simpanBtn.addEventListener("click", () => {
  const data = {
    namaProgram: el("namaProgram").value.trim(),
    tarikhMasa: el("tarikhMasa").value,
    tempat: el("tempat").value.trim(),
    anjuran: el("anjuran").value.trim(),
    penaja: el("penaja").value.trim(),
    sasaran: el("sasaran").value.trim(),
    objektif: el("objektif").value.trim(),
    butiranAktiviti: el("butiranAktiviti").value.trim(),
    pencapaian: el("pencapaian").value.trim(),
    kekuatan: el("kekuatan").value.trim(),
    kelemahan: el("kelemahan").value.trim(),
    intervensi: el("intervensi").value.trim(),
    impak: el("impak").value.trim(),
    kos: el("kos").value.trim(),
    penyedia: el("penyedia").value.trim(),
    penyemak: el("penyemak").value.trim(),
    pengesah: el("pengesah").value.trim(),
    gambar: gambarBuktiBase64.slice(0),
  };

  if (simpanBtn.dataset.editingId) {
    // kemaskini
    const id = parseInt(simpanBtn.dataset.editingId);
    const idx = dataOPR.findIndex((item) => item.id === id);
    if (idx > -1) {
      data.id = id;
      dataOPR[idx] = data;
      alert("Laporan berjaya dikemaskini.");
    }
    delete simpanBtn.dataset.editingId;
    simpanBtn.textContent = "Simpan Laporan";
  } else {
    // baru
    data.id = Date.now();
    dataOPR.unshift(data);
    alert("Laporan berjaya disimpan.");
  }

  simpanDataLocal();
  paparkanSenaraiOPR();
  oprForm.reset();
  gambarBuktiBase64 = [];
  paparkanPreviewGambar();
});

// Download semua data OPR sebagai JSON
downloadBtn.addEventListener("click", () => {
  const blob = new Blob([JSON.stringify(dataOPR, null, 2)], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "opr_data.json";
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 100);
});

// Cetak / simpan PDF (gunakan modul pdf-generator.js)
cetakBtn.addEventListener("click", () => {
  if (dataOPR.length === 0) {
    alert("Tiada laporan untuk dicetak.");
    return;
  }
  // Cetak semua laporan satu persatu
  dataOPR.forEach((laporan, idx) => {
    generatePDF(laporan, idx === dataOPR.length - 1);
  });
});

// Dark mode toggle
darkModeBtn.addEventListener("click", () => {
  const isDark = document.body.classList.toggle("dark");
  darkModeBtn.classList.toggle("active", isDark);
  localStorage.setItem("darkMode", isDark ? "true" : "false");
});
window.addEventListener("DOMContentLoaded", () => {
  if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark");
    darkModeBtn.classList.add("active");
  }
  muatDataLocal();
  muatDataSekolah();
  paparkanSenaraiOPR();
  paparkanPreviewGambar();
});

// Utility functions
function formatTarikh(t) {
  if (!t) return "-";
  try {
    const d = new Date(t);
    const hari = ["Ahad", "Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu"];
    return (
      hari[d.getDay()] +
      ", " +
      d.toLocaleDateString("ms-MY") +
      " " +
      d.toLocaleTimeString("ms-MY", { hour: "2-digit", minute: "2-digit" })
    );
  } catch {
    return t;
  }
}

function escapeHTML(text) {
  if (!text) return "";
  return text.replace(/[&<>"']/g, (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]));
}
