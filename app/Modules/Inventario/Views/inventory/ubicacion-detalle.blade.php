<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="ubic()">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-bold text-gray-900 font-mono">{{ $ubicacion->codigo }}</h1>
                    <span class="text-gray-700">{{ $ubicacion->nombre }}</span>
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $ubicacion->etiquetaTipo() }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($ubicacion->zona) Zona {{ $ubicacion->zona }} · @endif
                    {{ $productos->count() }} productos · {{ $activos->count() }} activos
                </p>
            </div>
            <a href="{{ route('inventory.ubicaciones') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Ubicaciones</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-4xl mx-auto space-y-4">

            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Guardar un producto aquí</label>
                <div class="flex gap-2 flex-wrap">
                    <input x-model="codigo" x-ref="cod" @keydown.enter.prevent="guardar()"
                           placeholder="Escanea o escribe el SKU"
                           class="flex-1 min-w-[160px] rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    <input x-model.number="cantidad" type="number" min="0" placeholder="Cantidad"
                           class="w-32 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    <button @click="escanear()" class="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cámara</button>
                    <button @click="guardar()" :disabled="!codigo || ocupado"
                            class="px-5 py-2 text-sm font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-40">Guardar</button>
                </div>
                <p class="text-xs mt-2" :class="msgTipo === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="msg"></p>
                <p class="text-[11px] text-gray-400 mt-1">La cantidad aquí es orientativa: el stock real lo sigue llevando el inventario.</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 text-xs font-bold uppercase text-gray-500">Productos guardados</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($productos as $p)
                        <tr>
                            <td class="px-4 py-2.5">
                                <div class="font-semibold text-gray-800">{{ $p->name }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $p->sku }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-right text-xs text-gray-500 whitespace-nowrap">
                                @if($p->pivot->cantidad !== null)
                                    {{ $p->pivot->cantidad }} aquí
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right text-xs text-gray-400 whitespace-nowrap">
                                {{ $p->stock !== null ? $p->stock.' en total' : '' }}
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <form method="POST" action="{{ route('inventory.ubicaciones.quitar', $ubicacion->id) }}"
                                      onsubmit="return confirm('¿Quitar este producto de la ubicación? No cambia el stock.')">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                                    <button class="text-xs text-gray-300 hover:text-red-500 px-2">Quitar</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Nada guardado todavía en esta ubicación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activos->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 text-xs font-bold uppercase text-gray-500">Activos en esta ubicación</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach($activos as $a)
                        <tr>
                            <td class="px-4 py-2.5">
                                <a href="{{ route('inventory.activos.show', $a->id) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $a->nombre }}</a>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $a->codigo }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $a->colorEstado() }}">{{ $a->etiquetaEstado() }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-right text-xs text-gray-500">{{ $a->responsable }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>
    </div>

    <div x-show="camara" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.88)">
        <div class="w-full max-w-sm">
            <video x-ref="video" autoplay playsinline muted class="w-full rounded-xl bg-black" style="aspect-ratio:1"></video>
            <p class="text-center text-white text-sm mt-3" x-text="camMsg"></p>
            <button @click="cerrarCamara()" class="w-full mt-3 py-2.5 rounded-lg bg-white text-gray-800 font-bold text-sm">Cerrar</button>
        </div>
    </div>
</div>

<script>
function ubic() {
    return {
        codigo: '', cantidad: '', msg: '', msgTipo: '', ocupado: false,
        camara: false, camMsg: '', _stream: null, _timer: null, _jsqr: null,

        async guardar() {
            if (!this.codigo) return;
            this.ocupado = true;
            try {
                const res = await fetch('{{ route('inventory.ubicaciones.asignar', $ubicacion->id) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ codigo: this.codigo.trim(), cantidad: this.cantidad === '' ? null : Number(this.cantidad) }),
                });
                const j = await res.json().catch(() => null);
                if (!res.ok || !j || !j.ok) {
                    this.msg = (j && j.error) ? j.error : 'No se pudo guardar (código ' + res.status + ').';
                    this.msgTipo = 'error';
                    return;
                }
                this.msg = j.producto.nombre + ' guardado aquí. Recarga para verlo en la lista.';
                this.msgTipo = '';
                this.codigo = ''; this.cantidad = '';
                this.$refs.cod && this.$refs.cod.focus();
            } catch (e) {
                this.msg = 'Sin conexión. Revisa la señal.';
                this.msgTipo = 'error';
            } finally { this.ocupado = false; }
        },

        async escanear() {
            this.camara = true;
            this.camMsg = 'Abriendo la cámara…';
            try {
                this._stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.$nextTick(() => { this.$refs.video.srcObject = this._stream; });
            } catch (e) { this.camMsg = 'No se pudo abrir la cámara. Escribe el código a mano.'; return; }

            if ('BarcodeDetector' in window) {
                this.camMsg = 'Apunta al código';
                const det = new BarcodeDetector({ formats: ['qr_code','ean_13','code_128','code_39','upc_a'] });
                this._timer = setInterval(async () => {
                    try { const c = await det.detect(this.$refs.video); if (c.length) this.leido(c[0].rawValue); } catch (e) {}
                }, 350);
            } else {
                await this.cargarJsQr();
                if (!this._jsqr) { this.camMsg = 'Este navegador no puede escanear. Escribe el código.'; return; }
                this.camMsg = 'Apunta al código';
                const l = document.createElement('canvas');
                this._timer = setInterval(() => {
                    const v = this.$refs.video;
                    if (!v || !v.videoWidth) return;
                    l.width = v.videoWidth; l.height = v.videoHeight;
                    const x = l.getContext('2d'); x.drawImage(v, 0, 0);
                    const r = this._jsqr(x.getImageData(0, 0, l.width, l.height).data, l.width, l.height);
                    if (r && r.data) this.leido(r.data);
                }, 350);
            }
        },

        cargarJsQr() {
            return new Promise(resolve => {
                if (window.jsQR) { this._jsqr = window.jsQR; return resolve(); }
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                s.onload = () => { this._jsqr = window.jsQR; resolve(); };
                s.onerror = () => resolve();
                document.head.appendChild(s);
            });
        },

        leido(v) { this.codigo = String(v).trim(); this.cerrarCamara(); },

        cerrarCamara() {
            if (this._timer) { clearInterval(this._timer); this._timer = null; }
            if (this._stream) { this._stream.getTracks().forEach(t => t.stop()); this._stream = null; }
            this.camara = false;
        },
    };
}
</script>

</x-slot>
</x-app-layout>
