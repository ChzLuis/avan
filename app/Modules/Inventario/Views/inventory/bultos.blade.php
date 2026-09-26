<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="bultos()">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Bultos y despacho</h1>
                <p class="text-xs text-gray-500 mt-0.5">Escanea la caja al cargarla y al entregarla. El avance queda en el historial del pedido.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Inventario</a>
                @if($bultos->isNotEmpty())
                <a href="{{ route('inventory.bultos.etiquetas') }}" target="_blank"
                   class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-emerald-400 hover:text-emerald-700 font-semibold bg-white">Imprimir etiquetas</a>
                @endif
                <button @click="nuevo = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Crear bultos</button>
            </div>
        </div>
    </div>

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-2.5">
        <div class="flex gap-5 text-xs flex-wrap">
            <span class="text-gray-500">Preparados: <b class="text-gray-800">{{ $resumen['preparados'] }}</b></span>
            <span class="text-gray-500">En camino: <b class="text-blue-700">{{ $resumen['despachados'] }}</b></span>
            <span class="text-gray-500">Entregados: <b class="text-emerald-700">{{ $resumen['entregados'] }}</b></span>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-4">

            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            {{-- El muelle de carga: se escanea una caja tras otra sin recargar. --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Escanear bulto</label>
                <div class="flex gap-2 flex-wrap">
                    <input x-model="codigo" x-ref="cod" @keydown.enter.prevent="avanzar()"
                           placeholder="Código del bulto (BLT-…)"
                           class="flex-1 min-w-[180px] rounded-lg border-gray-300 font-mono" style="font-size:16px">
                    <input x-model="recibidoPor" placeholder="Quién recibe (al entregar)"
                           class="w-52 rounded-lg border-gray-300" style="font-size:16px">
                    <button @click="escanear()" class="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cámara</button>
                    <button @click="avanzar()" :disabled="!codigo || ocupado"
                            class="px-5 py-2 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40">Marcar</button>
                </div>
                <p class="text-xs mt-2" :class="msgTipo === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="msg"></p>
                <p class="text-[11px] text-gray-400 mt-1">Cada escaneo avanza un paso: preparado → despachado → entregado.</p>
            </div>

            <form method="GET" class="bg-white rounded-xl border border-gray-200 p-3 flex gap-2 flex-wrap">
                <input name="q" value="{{ request('q') }}" placeholder="Buscar por código"
                       class="flex-1 min-w-[160px] text-sm rounded-lg border-gray-300" style="font-size:16px">
                <select name="estado" class="text-sm rounded-lg border-gray-300" style="font-size:16px">
                    <option value="">Todos</option>
                    @foreach($estados as $k => $v)
                    <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white">Filtrar</button>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($bultos as $b)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-mono font-bold text-gray-800">{{ $b->codigo }}</div>
                                <div class="text-[11px] text-gray-400">{{ $b->descripcion }}</div>
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-600">
                                {{ $b->order->client_name ?? '—' }}
                                @if($b->order?->numero)<div class="text-[11px] text-gray-400">{{ $b->order->numero }}</div>@endif
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 whitespace-nowrap">
                                @if($b->entregado_at)
                                    Entregado {{ $b->entregado_at->format('d/m H:i') }}
                                    @if($b->recibido_por)<div class="text-[11px] text-gray-400">a {{ $b->recibido_por }}</div>@endif
                                @elseif($b->despachado_at)
                                    Salió {{ $b->despachado_at->format('d/m H:i') }}
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $b->colorEstado() }}">{{ $b->etiquetaEstado() }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="px-4 py-10 text-center text-gray-400 text-sm">
                            No hay bultos. Créalos desde un pedido y pega su etiqueta en cada caja.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div x-show="nuevo" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.55)" @click.self="nuevo = false">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3 bg-gray-900"><h3 class="text-white font-bold text-sm">Crear bultos</h3></div>
            <form method="POST" action="{{ route('inventory.bultos.store') }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pedido</label>
                    <select name="order_id" required class="w-full rounded-lg border-gray-300" style="font-size:16px">
                        @forelse($pedidos as $p)
                        <option value="{{ $p->id }}">{{ $p->numero ?? ('#'.$p->id) }} — {{ $p->client_name }}</option>
                        @empty
                        <option value="">No hay pedidos disponibles</option>
                        @endforelse
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">¿Cuántas cajas?</label>
                        <input type="number" name="cantidad" value="1" min="1" max="50" required
                               class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Peso por caja (kg)</label>
                        <input type="number" step="0.01" min="0" name="peso_kg"
                               class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción <span class="font-normal text-gray-400">(opcional)</span></label>
                    <input name="descripcion" maxlength="150" placeholder="Si lo dejas vacío: Bulto 1 de N"
                           class="w-full rounded-lg border-gray-300" style="font-size:16px">
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="nuevo = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
                    <button class="flex-1 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Crear</button>
                </div>
            </form>
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
function bultos() {
    return {
        nuevo: false, codigo: '', recibidoPor: '', msg: '', msgTipo: '', ocupado: false,
        camara: false, camMsg: '', _stream: null, _timer: null, _jsqr: null,

        async avanzar() {
            if (!this.codigo) return;
            this.ocupado = true;
            try {
                const res = await fetch('{{ route('inventory.bultos.avanzar') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ codigo: this.codigo.trim(), recibido_por: this.recibidoPor || null }),
                });
                const j = await res.json().catch(() => null);
                if (!res.ok || !j || !j.ok) {
                    this.msg = (j && j.error) ? j.error : 'No se pudo marcar (código ' + res.status + ').';
                    this.msgTipo = 'error';
                    return;
                }
                this.msg = j.bulto.codigo + ' → ' + j.bulto.etiqueta
                    + (j.pendientes_del_pedido > 0
                        ? '. Quedan ' + j.pendientes_del_pedido + ' bulto(s) de ese pedido.'
                        : '. Pedido completo.');
                this.msgTipo = '';
                this.codigo = '';
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
                this.camMsg = 'Apunta al código del bulto';
                const det = new BarcodeDetector({ formats: ['qr_code','code_128','code_39'] });
                this._timer = setInterval(async () => {
                    try { const c = await det.detect(this.$refs.video); if (c.length) this.leido(c[0].rawValue); } catch (e) {}
                }, 350);
            } else {
                await this.cargarJsQr();
                if (!this._jsqr) { this.camMsg = 'Este navegador no puede escanear. Escribe el código.'; return; }
                this.camMsg = 'Apunta al código del bulto';
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

        leido(v) { this.codigo = String(v).trim(); this.cerrarCamara(); this.avanzar(); },

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
