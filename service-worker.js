// ==============================
// SERVICE WORKER: OPR SEKOLAH
// ==============================

const CACHE_NAME = 'opr-sekolah-v3';
const FILES_TO_CACHE = [
  './',
  './index.html',
  './style.css',
  './app.js',
  './manifest.json',
  './icon-192.png',
  './icon-512.png'
  // Tambah lagi fail jika ada fail baru
];

// Install: Cache semua fail utama
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(FILES_TO_CACHE))
      .then(() => self.skipWaiting())
  );
});

// Activate: Buang cache lama
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keyList =>
      Promise.all(
        keyList.map(key => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      )
    )
  );
  self.clients.claim();
});

// Fetch: Offline first (cache dulu, baru network)
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) return response;
        return fetch(event.request)
          .then(res => {
            // Simpan salinan baru ke cache
            return caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, res.clone());
              return res;
            });
          })
          .catch(() => {
            // Jika offline dan tiada cache, fallback ke root
            if (event.request.destination === 'document')
              return caches.match('./');
          });
      })
  );
});
