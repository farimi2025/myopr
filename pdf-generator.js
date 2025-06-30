// Modul generate PDF untuk laporan menggunakan jsPDF
// Pastikan anda sudah letak library jsPDF di dalam projek (boleh gunakan CDN atau bundle)
// Di sini hanya fungsi contoh generatePDF dari data laporan disediakan

function generatePDF(laporan, cetakAkhir = false) {
  if (!window.jspdf) {
    alert("Library jsPDF tidak ditemui!");
    return;
  }
  const doc = new jspdf.jsPDF();

  const margin = 15;
  let y = margin;

  doc.setFontSize(16);
  doc.text(laporan.namaProgram || "-", margin, y);
  y += 10;
  doc.setFontSize(10);
  doc.text("Tarikh & Masa: " + (laporan.tarikhMasa || "-"), margin, y);
  y += 7;
  doc.text("Tempat: " + (laporan.tempat || "-"), margin, y);
  y += 7;

  doc.setFontSize(12);
  y += 5;
  const addSection = (title, content) => {
    doc.setFont(undefined, "bold");
    doc.text(title + ":", margin, y);
    y += 6;
    doc.setFont(undefined, "normal");
    const splitText = doc.splitTextToSize(content || "-", 180);
    doc.text(splitText, margin, y);
    y += splitText.length * 6;
    y += 5;
  };

  addSection("Objektif", laporan.objektif);
  addSection("Butiran Aktiviti", laporan.butiranAktiviti);
  addSection("Pencapaian", laporan.pencapaian);
  addSection("Kekuatan", laporan.kekuatan);
  addSection("Kelemahan", laporan.kelemahan);
  addSection("Cadangan Intervensi", laporan.intervensi);
  addSection("Impak Program", laporan.impak);
  addSection("Kos/Peralatan", laporan.kos);
  addSection("Anjuran", laporan.anjuran);
  addSection("Penaja", laporan.penaja);

  y += 10;
  doc.setFontSize(10);
  doc.text(
    `Penyedia: ${laporan.penyedia || "-"} | Penyemak: ${laporan.penyemak || "-"} | Pengesah: ${laporan.pengesah || "-"}`,
    margin,
    y
  );

  // Jika ada gambar, letak satu-satu bawah teks
  if (Array.isArray(laporan.gambar)) {
    let xImg = margin;
    let yImg = y + 10;
    const imgSize = 40;
    laporan.gambar.forEach((base64Img, idx) => {
      if (yImg + imgSize > 280) {
        doc.addPage();
        yImg = margin;
      }
      doc.addImage(base64Img, "PNG", xImg, yImg, imgSize, imgSize);
      xImg += imgSize + 5;
      if (xImg + imgSize > 200) {
        xImg = margin;
        yImg += imgSize + 5;
      }
    });
  }

  if (cetakAkhir) {
    doc.save(`Laporan-${laporan.namaProgram || "opr"}.pdf`);
  } else {
    doc.output("dataurlnewwindow");
  }
}
