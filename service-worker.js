const CACHE_NAME = "opr-sekolah-v2";
const FILES_TO_CACHE = [
  "./",
  "./index.html",
  "./style.css",
  "./app.js",
  "./manifest.json",
  "./icon-192.png",
  "./icon-512.png",
  "./pdf-generator.js",
  "./docx-generator.js",
  "./indexeddb-helper.js",
  "./email-share.js",
  "./sync-github.js"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(FILES_TO_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keyList) =>
      Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      )
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;

  event.respondWith(
    caches.match(event.request).then((response) => {
      return (
        response ||
        fetch(event.request)
          .then((res) => {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, res.clone());
            });
            return res;
          })
          .catch(() => {
            if (event.request.destination === "document") {
              return caches.match("./");
            }
          })
      );
    })
  );
});
