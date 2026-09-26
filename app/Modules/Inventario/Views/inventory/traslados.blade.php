<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="traslados()">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Mover entre almacenes</h1>
                <p class="text-xs text-gray-500 mt-0.5">Pasa mercadería de un sitio a otro. El stock total no cambia: solo cambia dónde está.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.ubicaciones') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Ubicaciones</a>
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">Inventario</a>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-4">

            @if($ubicaciones->count() < 2)
            <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                Para mover mercadería necesitas al menos dos ubicaciones.
                <a href="{{ route('inventory.ubicaciones') }}" class="font-bold underline">Crea otra</a>.
            </div>
            @endif

            {{-- El traslado en sí. Se escanea el producto, se eligen los dos
                 sitios y la cantidad: es el orden en que se hace en el almacén,
                 con el producto ya en la mano. --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Producto</label>
                        <div class="flex gap-2">
                            <input x-model="codigo" x-ref="cod" @keydown.enter.prevent="$refs.cant.focus()"
                                   placeholder="Escanea o escribe el SKU"
                                   class="flex-1 min-w-0 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                            <button type="button" @click="escanear()"
                                    class="px-3 py-2 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600 hover:border-indigo-400">Cámara</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
                        <select x-model="desde" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                            <option value="">Sin ubicación previa</option>
                            @foreach($ubicaciones->groupBy(fn ($u) => $u->sede->name ?? 'Sin local') as $local => $grupo)
                            <optgroup label="{{ $local }}">
                                @foreach($grupo as $u)
                                <option value="{{ $u->id }}">{{ $u->codigo }} · {{ $u->nombre }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
                        <select x-model="hasta" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                            <option value="">Elige destino</option>
                            @foreach($ubicaciones->groupBy(fn ($u) => $u->sede->name ?? 'Sin local') as $local => $grupo)
                            <optgroup label="{{ $local }}">
                                @foreach($grupo as $u)
                                <option value="{{ $u->id }}">{{ $u->codigo }} · {{ $u->nombre }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-4 gap-3 items-end mt-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Cantidad</label>
                        <input x-model.number="cantidad" x-ref="cant" type="number" min="1"
                               @keydown.enter.prevent="mover()"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo <span class="font-normal text-gray-400">(opcional)</span></label>
                        <input x-model="nota" maxlength="300" placeholder="Reposición de picking, se llenó el estante…"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    </div>
                    <button @click="mover()" :disabled="!listo || ocupado"
                            class="px-5 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                        Mover
                    </button>
                </div>

                <p class="text-xs mt-2.5" :class="msgTipo === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="msg"></p>

                {{-- Salió del local: eso ya no es acomodar, es transportar, y
                     SUNAT lo quiere documentado. Enterarse en un control de
                     carretera sale mucho más caro que leerlo aquí. --}}
                <div x-show="aviso" x-cloak class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-3.5 py-3">
                    <p class="text-sm text-amber-900" x-text="aviso"></p>
                    <a x-show="urlGuia" :href="urlGuia" target="_blank"
                       class="inline-block mt-2 text-xs font-bold text-amber-900 underline">Emitir la guía de remisión →</a>
                </div>
            </div>

            {{-- ── Historial ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 text-xs font-bold uppercase text-gray-500">
                    Últimos movimientos entre sitios
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @forelse($traslados as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-gray-800">{{ $t->product->name ?? '—' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $t->product->sku ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="font-mono text-xs text-gray-500">{{ $t->desde->codigo ?? 'sin sitio' }}</span>
                                    <span class="text-gray-300 mx-1">→</span>
                                    <span class="font-mono text-xs font-bold text-gray-800">{{ $t->hasta->codigo ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-800 whitespace-nowrap"
                                    style="font-variant-numeric:tabular-nums">{{ $t->cantidad }}</td>
                                <td class="px-3 py-2.5 text-right text-[11px] text-gray-400 whitespace-nowrap">
                                    {{ $t->created_at->format('d/m H:i') }}
                                    @if($t->user)<div>{{ $t->user->name }}</div>@endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">{{ $t->nota }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">
                                Todavía no se ha movido nada entre ubicaciones.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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
function traslados() {
    return {
        codigo: '', desde: '', hasta: '', cantidad: '', nota: '',
        msg: '', msgTipo: '', ocupado: false, aviso: '', urlGuia: '',
        camara: false, camMsg: '', _stream: null, _timer: null, _jsqr: null,

        get listo() {
            return this.codigo.trim() !== '' && this.hasta !== '' && Number(this.cantidad) > 0;
        },

        async mover() {
            if (!this.listo) return;
            this.ocupado = true;
            try {
                const res = await fetch('{{ route('inventory.ubicaciones.trasladar') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        codigo: this.codigo.trim(),
                        desde_id: this.desde || null,
                        hasta_id: this.hasta,
                        cantidad: Number(this.cantidad),
                        nota: this.nota || null,
                    }),
                });
                const j = await res.json().catch(() => null);
                if (!res.ok || !j || !j.ok) {
                    this.msg = (j && j.error) ? j.error : 'No se pudo mover (código ' + res.status + ').';
                    this.msgTipo = 'error';
                    return;
                }
                this.msg = j.mensaje;
                this.msgTipo = '';
                this.aviso = j.aviso || '';
                this.urlGuia = j.url_guia || '';
                this.codigo = ''; this.cantidad = ''; this.nota = '';
                this.$refs.cod && this.$refs.cod.focus();
                if (!j.entre_sedes) setTimeout(() => window.location.reload(), 1200);
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

        leido(v) {
            this.codigo = String(v).trim();
            this.cerrarCamara();
            this.$nextTick(() => this.$refs.cant && this.$refs.cant.focus());
        },

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
