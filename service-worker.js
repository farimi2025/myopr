// ==============================
// SERVICE WORKER: OPR SEKOLAH
// ==============================

const CACHE_NAME = 'opr-sekolah-v2';
const FILES_TO_CACHE = [
  './',
  './index.html',
  './style.css',
  './app.js',
  './manifest.json',
  './icon-192.png',
  './icon-512.png'
  // Tambah lagi fail jika perlu
];

// Install SW & cache semua fail
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(FILES_TO_CACHE))
      .then(self.skipWaiting())
  );
});

// Activate SW & buang cache lama
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

// Intercept fetch (offline first)
self.addEventListener('fetch', event => {
  // Hanya cache GET
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request)
        .then(res => {
          // Simpan salinan baru ke cache
          return caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, res.clone());
            return res;
          });
        })
        .catch(() => {
          // Jika offline dan tiada cache, fallback ke root
          if (event.request.destination === 'document') {
            return caches.match('./');
          }
        })
      )
  );
});
