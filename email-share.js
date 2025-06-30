// Modul kongsi laporan via mailto, WhatsApp, Telegram, Google Drive

function kongsiViaMailto(laporan) {
  const subject = encodeURIComponent("Laporan OPR: " + laporan.namaProgram);
  const body = encodeURIComponent(`
Nama Program: ${laporan.namaProgram}
Tarikh & Masa: ${laporan.tarikhMasa}
Tempat: ${laporan.tempat}
Objektif: ${laporan.objektif}
Butiran Aktiviti: ${laporan.butiranAktiviti}
Pencapaian: ${laporan.pencapaian}
Kekuatan: ${laporan.kekuatan}
Kelemahan: ${laporan.kelemahan}
Cadangan Intervensi: ${laporan.intervensi}
Impak Program: ${laporan.impak}
Kos/Peralatan: ${laporan.kos}
Penyedia: ${laporan.penyedia}
Penyemak: ${laporan.penyemak}
Pengesah: ${laporan.pengesah}
  `);
  const mailtoLink = `mailto:?subject=${subject}&body=${body}`;
  window.location.href = mailtoLink;
}

function kongsiViaWhatsApp(laporan) {
  const text = encodeURIComponent(
    `Laporan OPR: ${laporan.namaProgram}\nTarikh & Masa: ${laporan.tarikhMasa}\nTempat: ${laporan.tempat}`
  );
  const url = `https://wa.me/?text=${text}`;
  window.open(url, "_blank");
}

function kongsiViaTelegram(laporan) {
  const text = encodeURIComponent(
    `Laporan OPR: ${laporan.namaProgram}\nTarikh & Masa: ${laporan.tarikhMasa}\nTempat: ${laporan.tempat}`
  );
  const url = `https://t.me/share/url?url=&text=${text}`;
  window.open(url, "_blank");
}
