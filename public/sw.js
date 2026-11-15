/* Service worker de FoodMatch.
   - Guarda en el celular los archivos estáticos (CSS, íconos, fotos) para cargar más rápido.
   - Las páginas NO se guardan (tienen datos de la cuenta); si no hay Internet se muestra un aviso. */
const CACHE = 'foodmatch-v2';
const PRE = ['/css/foodmatch.css', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', function (e) {
    e.waitUntil(caches.open(CACHE).then(function (c) { return c.addAll(PRE); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys()
            .then(function (ks) { return Promise.all(ks.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); })); })
            .then(function () { return self.clients.claim(); })
    );
});

const SIN_CONEXION = '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    + '<title>FoodMatch · Sin conexión</title></head><body style="font-family:system-ui,sans-serif;text-align:center;padding:60px 24px;color:#f6f4ff;background:#0c0a1d">'
    + '<div style="font-size:56px">📡</div><h2>Sin conexión</h2><p style="color:#a9a5c8">Revisa tu Internet y vuelve a intentarlo.</p>'
    + '<button onclick="location.reload()" style="padding:12px 22px;border:0;border-radius:10px;background:linear-gradient(135deg,#ff5a5f,#ffc145);color:#fff;font-size:15px">Reintentar</button></body></html>';

self.addEventListener('fetch', function (e) {
    const req = e.request;
    if (req.method !== 'GET') { return; }
    const url = new URL(req.url);
    if (url.origin !== location.origin) { return; } // mapas y Leaflet los maneja el navegador

    const estatico = /^\/(css|icons|storage)\//.test(url.pathname);

    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(function () {
            return new Response(SIN_CONEXION, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
        }));
        return;
    }
    if (estatico) {
        e.respondWith(
            fetch(req).then(function (res) {
                if (res.ok) { const copia = res.clone(); caches.open(CACHE).then(function (c) { c.put(req, copia); }); }
                return res;
            }).catch(function () { return caches.match(req); })
        );
    }
});
