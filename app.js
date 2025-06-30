// ==========================
// APP.JS OPR SEKOLAH VERSI MESRA GURU & KEMASKINI
// ==========================

const el = (id) => document.getElementById(id);

const simpanBtn = el("simpanBtn");
const downloadBtn = el("downloadBtn");
const cetakBtn = el("cetakBtn");

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

const editDataSekolahBtn = el("editDataSekolahBtn");
const dataSekolahSection = el("dataSekolah");

let dataOPR = [];
let gambarBuktiBase64 = [];
const MAKS_GAMBAR = 4;

// Fungsi toggle paparan maklumat sekolah & guru
function toggleDataSekolahSection(show) {
  if (show) {
    dataSekolahSection.style.display = "block";
    editDataSekolahBtn.style.display = "none";
  } else {
    dataSekolahSection.style.display = "none";
    editDataSekolahBtn.style.display = "inline-block";
  }
}

// Simpan maklumat sekolah & guru ke localStorage
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
  toggleDataSekolahSection(false);
}

function muatDataSekolah() {
  try {
    const dataSekolah = JSON.parse(localStorage.getItem("dataSekolah")) || {};
    if (Object.keys(dataSekolah).length === 0) {
      toggleDataSekolahSection(true);
      return;
    }
    namaSekolahInput.value = dataSekolah.namaSekolah || "";
    alamatSekolahInput.value = dataSekolah.alamatSekolah || "";
    guruPelaporInput.value = dataSekolah.guruPelapor || "";
    guruPengesahInput.value = dataSekolah.guruPengesah || "";
    if (dataSekolah.logoSekolah) {
      previewLogoDiv.innerHTML = `<img src="${dataSekolah.logoSekolah}" alt="Logo Sekolah" style="max-width:100px;max-height:100px;border-radius:8px;">`;
    }
    toggleDataSekolahSection(false);
  } catch {
    toggleDataSekolahSection(true);
  }
}

formSekolah.addEventListener("submit", (e) => {
  e.preventDefault();
  simpanDataSekolah();
});

editDataSekolahBtn.addEventListener("click", () => {
  toggleDataSekolahSection(true);
});

// Logo Sekolah preview & resize base64
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

// Gambar bukti & resize sama seperti sebelumnya
// (fungsi resizeImageFile, resizeImageCanvas, bukaKameraDanAmbilGambar, paparkanPreviewGambar, padamGambar sama seperti sebelumnya)
// ... [COPY fungsi lengkap dari kod sebelumnya, tidak diulang di sini untuk ringkas]

// Simpan & paparkan data laporan sama seperti sebelum ini
// ... [COPY fungsi lengkap dari kod sebelumnya, tidak diulang di sini untuk ringkas]

// Load data laporan & maklumat sekolah pada load DOM
window.addEventListener("DOMContentLoaded", () => {
  muatDataSekolah();
  muatDataLocal();
  paparkanSenaraiOPR();
  paparkanPreviewGambar();
});

// Buang kod dark mode sepenuhnya

// Fungsi utility formatTarikh dan escapeHTML sama seperti sebelum ini
// ... [COPY fungsi lengkap dari kod sebelumnya]

