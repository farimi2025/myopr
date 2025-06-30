// Modul generate DOCX untuk laporan menggunakan docx.js
// Pastikan docx.js library sudah dimasukkan dalam projek (CDN atau bundle)
// Fungsi contoh generateDOCX dari data laporan

async function generateDOCX(laporan) {
  const { Document, Packer, Paragraph, TextRun } = docx;

  const doc = new Document();

  const addSection = (title, content) =>
    new Paragraph({
      children: [
        new TextRun({ text: title + ":", bold: true }),
        new TextRun({ text: "\n" + (content || "-"), break: 1 }),
      ],
    });

  doc.addSection({
    children: [
      new Paragraph({
        text: laporan.namaProgram || "-",
        heading: docx.HeadingLevel.HEADING_1,
      }),
      new Paragraph(`Tarikh & Masa: ${laporan.tarikhMasa || "-"}`),
      new Paragraph(`Tempat: ${laporan.tempat || "-"}`),
      addSection("Objektif", laporan.objektif),
      addSection("Butiran Aktiviti", laporan.butiranAktiviti),
      addSection("Pencapaian", laporan.pencapaian),
      addSection("Kekuatan", laporan.kekuatan),
      addSection("Kelemahan", laporan.kelemahan),
      addSection("Cadangan Intervensi", laporan.intervensi),
      addSection("Impak Program", laporan.impak),
      addSection("Kos/Peralatan", laporan.kos),
      addSection("Anjuran", laporan.anjuran),
      addSection("Penaja", laporan.penaja),
      new Paragraph(
        `Penyedia: ${laporan.penyedia || "-"} | Penyemak: ${
          laporan.penyemak || "-"
        } | Pengesah: ${laporan.pengesah || "-"}`
      ),
    ],
  });

  const packer = new Packer();

  const buffer = await packer.toBlob(doc);
  const url = URL.createObjectURL(buffer);
  const a = document.createElement("a");
  a.href = url;
  a.download = `Laporan-${laporan.namaProgram || "opr"}.docx`;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 100);
}
