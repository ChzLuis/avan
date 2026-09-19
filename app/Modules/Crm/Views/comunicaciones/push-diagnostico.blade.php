@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Diagnóstico de notificaciones')
@section('content')
<div class="max-w-lg mx-auto p-4" x-data="diagPush()">
    <h1 class="text-base font-bold text-gray-900 mb-1">Diagnóstico de notificaciones push</h1>
    <p class="text-xs text-gray-500 mb-4">Toca el botón y envíale a soporte una captura del resultado.</p>
    <button @click="correr()" :disabled="corriendo" class="px-4 py-2 rounded-xl text-sm font-semibold text-white disabled:opacity-60" style="background:#25d366">Probar en este dispositivo</button>
    <div class="mt-4 space-y-1.5 text-xs font-mono">
        <template x-for="(l, i) in lineas" :key="i">
            <div class="px-3 py-1.5 rounded-lg" :class="l.ok === true ? 'bg-green-50 text-green-800' : (l.ok === false ? 'bg-red-50 text-red-800' : 'bg-gray-50 text-gray-700')" x-text="l.t"></div>
        </template>
    </div>
</div>
<script>
function diagPush() {
    return {
        corriendo: false, lineas: [],
        log(t, ok = null) { this.lineas.push({ t, ok }); },
        async correr() {
            this.corriendo = true; this.lineas = [];
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            this.log('Navegador: ' + navigator.userAgent);
            this.log('Modo app instalada: ' + (window.matchMedia('(display-mode: standalone)').matches ? 'sí' : 'no'));
            this.log('HTTPS: ' + location.protocol, location.protocol === 'https:');
            this.log('Notification API: ' + ('Notification' in window), 'Notification' in window);
            this.log('Service worker API: ' + ('serviceWorker' in navigator), 'serviceWorker' in navigator);
            this.log('PushManager API: ' + ('PushManager' in window), 'PushManager' in window);
            if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) { this.corriendo = false; return; }
            this.log('Permiso de notificaciones: ' + Notification.permission, Notification.permission === 'granted');
            if (Notification.permission !== 'granted') {
                const p = await Notification.requestPermission().catch(e => 'error ' + e);
                this.log('Permiso tras pedirlo: ' + p, p === 'granted');
                if (p !== 'granted') { this.corriendo = false; return; }
            }
            try {
                const reg = await navigator.serviceWorker.register('/sw.js');
                this.log('Service worker registrado, alcance: ' + reg.scope, true);
                await navigator.serviceWorker.ready;
                this.log('Service worker activo', true);
                const r = await fetch('/bixocrm/push/clave', { headers: { Accept: 'application/json' } });
                const { clave } = await r.json();
                this.log('Clave del servidor: ' + (clave ? clave.slice(0, 12) + '… (' + clave.length + ' caracteres)' : 'NO LLEGÓ'), !!clave);
                const raw = Uint8Array.from(atob(clave.replace(/-/g, '+').replace(/_/g, '/').padEnd(clave.length + (4 - clave.length % 4) % 4, '=')), c => c.charCodeAt(0));
                this.log('Clave decodificada: ' + raw.length + ' bytes, empieza en 0x' + raw[0].toString(16), raw.length === 65 && raw[0] === 4);
                let sub = await reg.pushManager.getSubscription();
                this.log('Suscripción previa: ' + (sub ? 'sí' : 'no'));
                if (sub) { await sub.unsubscribe(); this.log('Suscripción previa eliminada para reintentar'); }
                const t0 = Date.now();
                try {
                    sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: raw });
                    this.log('Suscripción creada en ' + (Date.now() - t0) + ' ms: ' + sub.endpoint.slice(0, 48) + '…', true);
                } catch (e) {
                    this.log('FALLO al suscribir (' + (Date.now() - t0) + ' ms): ' + e.name + ' – ' + e.message, false);
                    this.log('Pistas: este error lo da el navegador al hablar con el servicio de push de Google. Prueba con datos móviles en vez de wifi, apaga VPN / DNS privado / bloqueadores, y revisa que Google Play Services esté actualizado.');
                    this.corriendo = false; return;
                }
                const g = await fetch('/bixocrm/push/suscribir', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(sub.toJSON()) });
                this.log('Guardada en el servidor: HTTP ' + g.status, g.ok);
                const pr = await fetch('/bixocrm/push/probar', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
                const d = await pr.json().catch(() => ({}));
                this.log('Notificación de prueba enviada: ' + JSON.stringify(d), !!d.ok);
                this.log('Si no apareció la notificación en unos segundos, revisa Ajustes → Aplicaciones → Chrome → Notificaciones y "No molestar".');
            } catch (e) {
                this.log('Error: ' + (e && e.message ? e.message : e), false);
            }
            this.corriendo = false;
        },
    };
}
</script>
@endsection
