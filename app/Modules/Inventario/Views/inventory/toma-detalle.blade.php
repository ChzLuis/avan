<x-app-layout>
<x-slot name="slot">

@php
    $abierta = $conteo->estaAbierta();
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC"
     x-data="toma()" x-init="init()">

    {{-- ── Cabecera ── --}}
    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold text-gray-900 truncate">{{ $conteo->nombre }}</h1>
                    @if($abierta)
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">En curso</span>
                    @else
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Cerrada</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $conteo->category->name ?? 'Todo el almacén' }} ·
                    <span x-text="resumen.contadas"></span> de <span x-text="resumen.lineas"></span> contados
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.tomas') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Conteos</a>
                @if($abierta)
                <button @click="abrirCamara()" class="text-xs px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold">
                    Escanear
                </button>
                <button @click="confirmarCierre = true" class="text-xs px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-700 text-white font-bold">
                    Cerrar conteo
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Resumen ── --}}
    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-2.5">
        <div class="flex gap-5 text-xs flex-wrap">
            <span class="text-gray-500">Pendientes: <b class="text-gray-800" x-text="resumen.pendientes"></b></span>
            <span class="text-gray-500">Descuadran: <b class="text-amber-700" x-text="resumen.con_diferencia"></b></span>
            <span class="text-gray-500">Sobrante: <b class="text-emerald-700" x-text="'+' + resumen.sobrante"></b></span>
            <span class="text-gray-500">Faltante: <b class="text-red-700" x-text="'-' + resumen.faltante"></b></span>
        </div>
    </div>

    @if(session('success'))
    <div class="flex-shrink-0 mx-4 md:mx-6 mt-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="flex-shrink-0 mx-4 md:mx-6 mt-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-3">

            @if($abierta)
            {{-- Escribir el codigo a mano tiene que ser tan facil como escanear:
                 en un almacen con poca luz o una etiqueta rozada, la camara no
                 engancha y la gente abandona la herramienta. --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Código del producto</label>
                <div class="flex gap-2 flex-wrap">
                    <input x-model="codigo" @keydown.enter.prevent="$refs.cant.focus()" x-ref="cod"
                           placeholder="Escanea o escribe el SKU"
                           class="flex-1 min-w-[160px] rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                           style="font-size:16px">
                    <input x-model.number="cantidad" type="number" min="0" x-ref="cant"
                           @keydown.enter.prevent="anotar()" placeholder="Cantidad"
                           class="w-32 rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                           style="font-size:16px">
                    <button @click="anotar()" :disabled="!codigo || cantidad === '' || guardando"
                            class="px-5 py-2 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40">
                        Anotar
                    </button>
                </div>
                <p class="text-xs mt-2" :class="msgTipo === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="msg"></p>
            </div>
            @endif

            {{-- ── Lineas ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center gap-3 flex-wrap">
                    <input x-model="filtro" placeholder="Filtrar por nombre o SKU"
                           class="flex-1 min-w-[180px] text-sm rounded-lg border-gray-200" style="font-size:16px">
                    <label class="flex items-center gap-1.5 text-xs text-gray-600">
                        <input type="checkbox" x-model="soloPendientes" class="rounded border-gray-300 text-indigo-600">
                        Solo pendientes
                    </label>
                    <label class="flex items-center gap-1.5 text-xs text-gray-600">
                        <input type="checkbox" x-model="soloDiferencias" class="rounded border-gray-300 text-amber-600">
                        Solo diferencias
                    </label>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Producto</th>
                                <th class="px-3 py-2 text-right font-semibold">Sistema</th>
                                <th class="px-3 py-2 text-right font-semibold">Contado</th>
                                <th class="px-3 py-2 text-right font-semibold">Dif.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="it in visibles" :key="it.id">
                                <tr :class="it.contado === null ? '' : (it.diferencia === 0 ? 'bg-emerald-50/40' : 'bg-amber-50/50')">
                                    <td class="px-3 py-2">
                                        <div class="font-semibold text-gray-800" x-text="it.nombre"></div>
                                        <div class="text-[11px] text-gray-400 font-mono" x-text="it.sku"></div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-600" x-text="it.sistema"></td>
                                    <td class="px-3 py-2 text-right">
                                        @if($abierta)
                                        <input type="number" min="0" :value="it.contado"
                                               @change="anotarLinea(it, $event.target.value)"
                                               class="w-20 text-right rounded border-gray-200 py-1"
                                               style="font-size:16px">
                                        @else
                                        <span x-text="it.contado === null ? '—' : it.contado"></span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold"
                                        :class="it.contado === null ? 'text-gray-300' : (it.diferencia === 0 ? 'text-emerald-600' : (it.diferencia > 0 ? 'text-emerald-700' : 'text-red-600'))"
                                        x-text="it.contado === null ? '—' : (it.diferencia > 0 ? '+' + it.diferencia : it.diferencia)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Camara ── --}}
    <div x-show="camara" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.88)">
        <div class="w-full max-w-sm">
            <video x-ref="video" autoplay playsinline muted class="w-full rounded-xl bg-black" style="aspect-ratio:1"></video>
            <p class="text-center text-white text-sm mt-3" x-text="camMsg"></p>
            <button @click="cerrarCamara()" class="w-full mt-3 py-2.5 rounded-lg bg-white text-gray-800 font-bold text-sm">Cerrar</button>
        </div>
    </div>

    {{-- ── Confirmar cierre ── --}}
    <div x-show="confirmarCierre" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.55)" @click.self="confirmarCierre = false">
        <div class="bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-bold text-gray-900">¿Cerrar el conteo?</h3>
            <p class="text-sm text-gray-600 mt-2">
                Se ajustará el stock de los <b x-text="resumen.con_diferencia"></b> productos que descuadran,
                y cada ajuste quedará en el kardex.
            </p>
            <p class="text-xs text-gray-500 mt-2">
                Los <b x-text="resumen.pendientes"></b> sin contar se quedan como están: no haber llegado a
                un estante no significa que el producto no exista.
            </p>
            <div class="flex gap-2 mt-5">
                <button @click="confirmarCierre = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Seguir contando</button>
                <form method="POST" action="{{ route('inventory.tomas.cerrar', $conteo->id) }}" class="flex-1">
                    @csrf
                    <button class="w-full py-2.5 text-sm font-bold rounded-lg bg-gray-900 hover:bg-gray-700 text-white">Cerrar y ajustar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toma() {
    return {
        items: @js($items->map(fn ($i) => [
            'id' => $i->id,
            'nombre' => $i->product->name ?? '',
            'sku' => $i->product->sku ?: ($i->product->barcode ?? ''),
            'sistema' => $i->stock_sistema,
            'contado' => $i->contado,
            'diferencia' => $i->diferencia(),
        ])),
        resumen: @js($resumen),
        codigo: '', cantidad: '', msg: '', msgTipo: '', guardando: false,
        filtro: '', soloPendientes: false, soloDiferencias: false,
        camara: false, camMsg: '', _stream: null, _timer: null, _jsqr: null,

        init() {},

        get visibles() {
            let l = this.items;
            const f = this.filtro.trim().toLowerCase();
            if (f) l = l.filter(i => (i.nombre || '').toLowerCase().includes(f) || (i.sku || '').toLowerCase().includes(f));
            if (this.soloPendientes) l = l.filter(i => i.contado === null);
            if (this.soloDiferencias) l = l.filter(i => i.contado !== null && i.diferencia !== 0);
            return l.slice(0, 400);
        },

        anotar() {
            if (!this.codigo || this.cantidad === '') return;
            this.enviar({ codigo: this.codigo.trim(), cantidad: Number(this.cantidad) });
        },

        anotarLinea(it, valor) {
            if (valor === '') return;
            this.enviar({ item_id: it.id, cantidad: Number(valor) });
        },

        /* Toda escritura pasa por aqui y SIEMPRE mira `res.ok`: un 419 por
           sesion caducada devuelve HTML, y sin comprobarlo el conteo parecia
           guardarse cuando en realidad se perdia. */
        async enviar(datos) {
            this.guardando = true;
            try {
                const res = await fetch('{{ route('inventory.tomas.contar', $conteo->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(datos),
                });
                const j = await res.json().catch(() => null);
                if (!res.ok || !j || !j.ok) {
                    this.msg = (j && j.error) ? j.error : 'No se pudo guardar (código ' + res.status + ').';
                    this.msgTipo = 'error';
                    return;
                }
                const i = this.items.findIndex(x => x.id === j.item.id);
                if (i >= 0) {
                    this.items[i].contado = j.item.contado;
                    this.items[i].diferencia = j.item.diferencia;
                }
                this.resumen = j.resumen;
                const d = j.item.diferencia;
                this.msg = j.item.nombre + ': ' + j.item.contado
                    + (d === 0 ? ' — cuadra' : (d > 0 ? ' — sobran ' + d : ' — faltan ' + Math.abs(d)));
                this.msgTipo = d === 0 ? '' : 'error';
                this.codigo = ''; this.cantidad = '';
                this.$refs.cod && this.$refs.cod.focus();
            } catch (e) {
                this.msg = 'Sin conexión. Revisa la señal y vuelve a intentarlo.';
                this.msgTipo = 'error';
            } finally {
                this.guardando = false;
            }
        },

        /* Dos lectores: `BarcodeDetector` es nativo y rapido, pero NO existe
           en Safari, asi que en iPhone se carga jsQR. Sin esto, la mitad de
           los telefonos no puede escanear. */
        async abrirCamara() {
            this.camara = true;
            this.camMsg = 'Abriendo la cámara…';
            try {
                this._stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.$nextTick(() => { this.$refs.video.srcObject = this._stream; });
            } catch (e) {
                this.camMsg = 'No se pudo abrir la cámara. Da permiso o escribe el código a mano.';
                return;
            }
            if ('BarcodeDetector' in window) {
                this.camMsg = 'Apunta al código QR';
                const det = new BarcodeDetector({ formats: ['qr_code', 'ean_13', 'code_128', 'code_39', 'upc_a'] });
                this._timer = setInterval(async () => {
                    try {
                        const c = await det.detect(this.$refs.video);
                        if (c.length) this.leido(c[0].rawValue);
                    } catch (e) {}
                }, 350);
            } else {
                await this.cargarJsQr();
                if (!this._jsqr) { this.camMsg = 'Este navegador no puede escanear. Escribe el código a mano.'; return; }
                this.camMsg = 'Apunta al código QR';
                const lienzo = document.createElement('canvas');
                this._timer = setInterval(() => {
                    const v = this._refsVideo();
                    if (!v || !v.videoWidth) return;
                    lienzo.width = v.videoWidth; lienzo.height = v.videoHeight;
                    const ctx = lienzo.getContext('2d');
                    ctx.drawImage(v, 0, 0);
                    const d = ctx.getImageData(0, 0, lienzo.width, lienzo.height);
                    const r = this._jsqr(d.data, lienzo.width, lienzo.height);
                    if (r && r.data) this.leido(r.data);
                }, 350);
            }
        },

        _refsVideo() { return this.$refs.video; },

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

        leido(valor) {
            this.codigo = String(valor).trim();
            this.cerrarCamara();
            this.$nextTick(() => this.$refs.cant && this.$refs.cant.focus());
        },

        cerrarCamara() {
            if (this._timer) { clearInterval(this._timer); this._timer = null; }
            if (this._stream) { this._stream.getTracks().forEach(t => t.stop()); this._stream = null; }
            this.camara = false;
        },

        confirmarCierre: false,
    };
}
</script>

</x-slot>
</x-app-layout>
