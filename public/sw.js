/* PL Pharma — installable app shell only; pages always load from the server. */
const SW_VERSION = '2';

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(Promise.resolve());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      await self.clients.claim();
      const keys = await caches.keys();
      await Promise.all(keys.map((key) => caches.delete(key)));
    })()
  );
});

/* Intentionally no "fetch" handler — avoids showing a false "You are offline" page when the server errors or times out. */
