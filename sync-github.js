// sync-github.js
// Fungsi syncWithGitHub sync data laporan dari repo GitHub

async function syncWithGitHub() {
  // Contoh fetch data json dari GitHub repo public
  const url = 'https://raw.githubusercontent.com/farimi2025/myopr/main/opr_data.json';
  const response = await fetch(url);
  if (!response.ok) throw new Error('Gagal dapatkan data dari GitHub');
  const data = await response.json();
  // Gabungkan data dengan localStorage dataOPR
  const existingData = JSON.parse(localStorage.getItem('dataOPR')) || [];
  const existingIds = new Set(existingData.map(d => d.id));
  data.forEach(item => {
    if (!existingIds.has(item.id)) {
      existingData.push(item);
    }
  });
  localStorage.setItem('dataOPR', JSON.stringify(existingData));
}
