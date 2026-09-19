// Service worker del CRM de BIXO: recibe las notificaciones push y abre la conversacion al tocarlas.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

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
