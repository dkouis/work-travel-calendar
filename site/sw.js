/* Work & Travel Calendar — lets the app be installed and open without internet.
   The calendar data itself is never cached here (api.php always goes to the server). */
const CACHE = 'wtc-shell-v2';
const SHELL = ['./', 'index.php', 'manifest.webmanifest', 'icon.svg', 'icon-192.png', 'icon-512.png'];
self.addEventListener('install', e => { e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)).then(() => self.skipWaiting())); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== CACHE).map(k => caches.delete(k)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', e => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET' || u.origin !== location.origin || u.pathname.endsWith('api.php')) return;
  e.respondWith(fetch(e.request).then(r => {
    if (r.ok && (e.request.mode === 'navigate' || SHELL.some(s => u.pathname.endsWith(s.replace('./', ''))))) { const c = r.clone(); caches.open(CACHE).then(ca => ca.put(e.request, c)); }
    return r;
  }).catch(() => caches.match(e.request).then(m => m || caches.match('index.php'))));
});
