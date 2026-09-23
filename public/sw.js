self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(Promise.resolve());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request).catch(() => {
      if (event.request.mode === 'navigate') {
        return new Response(
          '<!DOCTYPE html><meta charset="utf-8"><title>Offline</title><body style="font-family:sans-serif;padding:24px"><h1>You are offline</h1><p>PL Pharma needs a connection to open this page.</p></body>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      }
      return Response.error();
    })
  );
});
