// Service worker del CRM de BIXO: recibe las notificaciones push y abre la conversacion al tocarlas.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

// Chrome pide un manejador de red para considerar la app instalable: red primero y, sin
// conexion, un aviso simple (el CRM necesita internet igual).
self.addEventListener('fetch', (e) => {
    if (e.request.mode !== 'navigate') return;
    // Solo se muestra "Sin conexion" cuando de verdad no hay red: un fallo puntual del
    // servidor o un corte momentaneo se reintenta una vez y, si persiste, el navegador
    // muestra su propio error (recargar lo resuelve).
    e.respondWith(fetch(e.request).catch(async (err) => {
        if (self.navigator && self.navigator.onLine !== false) {
            try { return await fetch(e.request); } catch (e2) { throw err; }
        }
        return new Response(
        '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        + '<body style="font-family:sans-serif;background:#26233b;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center">'
        + '<div><div style="font-size:42px">📡</div><h2>Sin conexión</h2><p>Conéctate a internet y vuelve a abrir BIXO CRM.</p></div></body>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }));
});

self.addEventListener('push', (e) => {
    let d = {};
    try { d = e.data ? e.data.json() : {}; } catch (err) { d = { cuerpo: e.data ? e.data.text() : '' }; }
    e.waitUntil(self.registration.showNotification(d.titulo || 'BIXO CRM', {
        body: d.cuerpo || 'Mensaje nuevo',
        icon: '/img/pwa/icono-192.png',
        badge: '/img/pwa/badge-96.png',
        tag: d.tag || 'bx',
        renotify: true,
        vibrate: [120, 60, 120],
        data: { url: d.url || '/bixocrm' },
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const url = (e.notification.data && e.notification.data.url) || '/bixocrm';
    e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((lista) => {
        for (const c of lista) {
            if (c.url.includes('/bixocrm') && 'focus' in c) { c.navigate(url); return c.focus(); }
        }
        return self.clients.openWindow(url);
    }));
});
