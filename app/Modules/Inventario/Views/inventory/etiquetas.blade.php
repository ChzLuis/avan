<x-app-layout>
<x-slot name="slot">

<style>
    /* La vista previa se dibuja en milimetros, las mismas unidades que la
       hoja impresa, para que lo que se ve aqui sea lo que sale de la
       impresora. Es la unica forma de decidir si un nombre largo entra. */
    .bx-prev {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px;
        background:
            linear-gradient(45deg, #f1f5f9 25%, transparent 25%, transparent 75%, #f1f5f9 75%),
            linear-gradient(45deg, #f1f5f9 25%, #fff 25%, #fff 75%, #f1f5f9 75%);
        background-size: 14px 14px;
        background-position: 0 0, 7px 7px;
        border-radius: 10px;
    }
    .bx-et {
        background: #fff;
        color: #000;
        overflow: hidden;
        padding: 1.5mm;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .18), 0 6px 16px rgba(15, 23, 42, .10);
        font-family: -apple-system, "Segoe UI", Arial, sans-serif;
    }
    .bx-et.lat { display: flex; align-items: center; gap: 1.5mm; }
    .bx-et.pre { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .bx-et svg { display: block; }
    .bx-et .nom { font-weight: 700; line-height: 1.15; overflow: hidden;
                  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .bx-et .cod, .bx-et .pre-num { font-variant-numeric: tabular-nums; }
    .bx-et .cod { font-family: Consolas, monospace; font-weight: 700; }
    .bx-et .meta { color: #555; }

    /* Respuesta fisica al pulsar: sin esto el boton se siente muerto. */
    .bx-accion { transition: transform .15s ease, filter .15s ease; }
    .bx-accion:active:not(:disabled) { transform: translateY(1px); }
    .bx-campo:focus-visible { outline: 2px solid #059669; outline-offset: 2px; }
    .bx-num { font-variant-numeric: tabular-nums; }
</style>

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC"
     x-data="etiquetas()">

    {{-- ── Cabecera ── --}}
    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-gray-900">Etiquetas con código QR</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Imprime y pega el código en cada producto o estante. Después se escanea con la cámara para contar.
                </p>
            </div>
            <a href="{{ route('inventory.index') }}"
               class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Volver al inventario</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-6xl mx-auto space-y-4">

            @if($sinCodigo > 0)
            {{-- Sin codigo no hay QR posible. Se avisa antes de imprimir, no
                 despues de gastar una hoja de adhesivos. --}}
            <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                <b>{{ $sinCodigo }}</b> {{ $sinCodigo === 1 ? 'producto no tiene' : 'productos no tienen' }} SKU ni código de barras,
                así que no {{ $sinCodigo === 1 ? 'puede' : 'pueden' }} llevar etiqueta.
                Ponles un SKU en el catálogo y vuelve aquí.
            </div>
            @endif

            {{-- ── Filtros ── --}}
            <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Buscar</label>
                    <input name="q" value="{{ request('q') }}" placeholder="Nombre o SKU"
                           class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div class="min-w-[180px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría</label>
                    <select name="categoria" class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Todas</option>
                        @foreach($categorias as $c)
                        <option value="{{ $c->id }}" @selected(request('categoria') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white hover:bg-gray-700">Filtrar</button>
            </form>

            {{-- ── Opciones de impresion ── --}}
            <form method="POST" action="{{ route('inventory.etiquetas.imprimir') }}" target="_blank"
                  class="bg-white rounded-xl border border-gray-200 p-4">
                @csrf
                <div class="grid lg:grid-cols-5 gap-5 mb-5">

                    <div class="lg:col-span-3 space-y-4">
                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-2">La hoja</h2>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex-1 min-w-[210px]">
                                    <span class="block text-xs font-semibold text-gray-600 mb-1">Formato</span>
                                    <select name="formato" x-model="formato"
                                            class="bx-campo w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                        @foreach($formatos as $clave => $f)
                                        <option value="{{ $clave }}">{{ $f['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="w-24">
                                    <span class="block text-xs font-semibold text-gray-600 mb-1">Copias</span>
                                    <input type="number" name="copias" value="1" min="1" max="20" x-model.number="copiasNum"
                                           class="bx-campo bx-num w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                </label>
                            </div>
                        </section>

                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-2">El código</h2>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex-1 min-w-[210px]">
                                    <span class="block text-xs font-semibold text-gray-600 mb-1">Tipo</span>
                                    <select name="tipo_codigo" x-model="tipoCodigo"
                                            class="bx-campo w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="qr">QR · lo lee el celular</option>
                                        <option value="barras">Barras · lo lee el lector láser</option>
                                        <option value="ninguno">Sin código</option>
                                    </select>
                                </label>
                                <label class="flex-1 min-w-[190px]">
                                    <span class="block text-xs font-semibold text-gray-600 mb-1">Disposición</span>
                                    <select name="diseno" x-model="diseno"
                                            class="bx-campo w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="lateral">Código al costado</option>
                                        <option value="precio">Precio grande, tipo tienda</option>
                                    </select>
                                </label>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5" x-show="tipoCodigo === 'barras'">
                                El lector láser de una caja registradora no lee QR; para eso sirven las barras.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-2">Qué se imprime</h2>
                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                @foreach([
                                    'codigo' => 'Código / SKU',
                                    'nombre' => 'Nombre',
                                    'precio' => 'Precio',
                                    'unidad' => 'Unidad',
                                    'categoria' => 'Categoría',
                                    'marca' => 'Marca',
                                ] as $clave => $texto)
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="campos[]" value="{{ $clave }}" x-model="campos"
                                           class="bx-campo rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    {{ $texto }}
                                </label>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2">
                                En una etiqueta pequeña, marcar de todo aprieta el texto. El código y el precio son lo que se lee de lejos.
                            </p>
                        </section>
                    </div>

                    {{-- Vista previa a tamaño real, con el código de verdad y un
                         producto del propio catálogo. --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-baseline justify-between mb-2">
                            <h2 class="text-xs font-bold uppercase tracking-wide text-gray-400">Tamaño real</h2>
                            <span class="text-[11px] text-gray-400" x-text="medidas[formato].ancho + ' × ' + medidas[formato].alto + ' mm'"></span>
                        </div>
                        <div class="bx-prev">
                            <div class="bx-et" :class="diseno === 'precio' ? 'pre' : 'lat'"
                                 :style="'width:' + medidas[formato].ancho + 'mm;height:' + medidas[formato].alto + 'mm'">

                                <template x-if="diseno === 'precio'">
                                    <div class="w-full">
                                        <div x-show="campos.includes('codigo')" class="cod"
                                             :style="'font-size:' + (alta ? 13 : 10) + 'pt;line-height:1'">{{ $muestra['codigo'] }}</div>
                                        <div x-show="campos.includes('precio')" class="pre-num font-extrabold"
                                             :style="'font-size:' + (alta ? 15 : 11) + 'pt;line-height:1.1'">{{ $muestra['moneda'] }} {{ number_format($muestra['precio'], 2) }}</div>
                                        <div x-show="tipoCodigo !== 'ninguno'" class="flex justify-center" style="margin-top:.8mm">
                                            <span x-show="tipoCodigo === 'qr'" :style="'width:' + (alta ? 13 : 9) + 'mm'">{!! $muestra['qr'] !!}</span>
                                            <span x-show="tipoCodigo === 'barras'" :style="'width:92%;height:' + (alta ? 9 : 6) + 'mm'">{!! $muestra['barras'] !!}</span>
                                        </div>
                                        <div x-show="campos.includes('nombre')" class="nom uppercase"
                                             :style="'font-size:' + (alta ? 5.5 : 4.5) + 'pt;margin-top:.7mm'">{{ $muestra['nombre'] }}</div>
                                        <div x-show="verMeta" class="meta" :style="'font-size:' + (alta ? 5 : 4) + 'pt'" x-text="meta"></div>
                                    </div>
                                </template>

                                <template x-if="diseno === 'lateral'">
                                    <div class="flex items-center gap-1.5 w-full h-full">
                                        <span x-show="tipoCodigo !== 'ninguno'" class="flex-shrink-0">
                                            <span x-show="tipoCodigo === 'qr'" :style="'display:block;width:' + Math.min(medidas[formato].alto - 3, 22) + 'mm'">{!! $muestra['qr'] !!}</span>
                                            <span x-show="tipoCodigo === 'barras'" :style="'display:block;width:' + Math.min(medidas[formato].ancho * .42, 26) + 'mm'">{!! $muestra['barras'] !!}</span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span x-show="campos.includes('nombre')" class="nom block"
                                                  :style="'font-size:' + (alta ? 7 : 5.5) + 'pt'">{{ $muestra['nombre'] }}</span>
                                            <span x-show="campos.includes('codigo')" class="cod block"
                                                  :style="'font-size:' + (alta ? 7.5 : 6) + 'pt;margin-top:.8mm'">{{ $muestra['codigo'] }}</span>
                                            <span x-show="campos.includes('precio')" class="pre-num block font-bold"
                                                  :style="'font-size:' + (alta ? 7.5 : 6) + 'pt;margin-top:.5mm'">{{ $muestra['moneda'] }} {{ number_format($muestra['precio'], 2) }}<span x-show="campos.includes('unidad')"> / {{ $muestra['unidad'] ?: 'und' }}</span></span>
                                            <span x-show="verMeta" class="meta block" :style="'font-size:' + (alta ? 5.5 : 4.5) + 'pt'" x-text="meta"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <button type="submit" :disabled="!puedeImprimir"
                                class="bx-accion w-full mt-3 px-5 py-3 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-show="!puedeImprimir">Elige productos para imprimir</span>
                            <span x-show="puedeImprimir" x-cloak>
                                Imprimir <span class="bx-num" x-text="cuantasEtiquetas"></span> etiquetas
                            </span>
                        </button>
                    </div>
                </div>

                {{-- ── Productos ── --}}
                <div class="flex items-center gap-3 mb-2 text-xs flex-wrap">
                    <button type="button" @click="todos()" class="font-semibold text-emerald-700 hover:underline">Seleccionar los de la lista</button>
                    <button type="button" @click="ninguno()" class="font-semibold text-gray-500 hover:underline">Quitar selección</button>
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500 bx-num">
                        <span x-text="seleccion.length"></span> de {{ $productos->count() }} en pantalla
                        @if($total > $productos->count())
                        <span class="text-gray-400">(hay {{ number_format($total) }} con este filtro)</span>
                        @endif
                    </span>
                </div>

                @if($total > $productos->count())
                {{-- Con un catálogo grande no tiene sentido marcar mil casillas:
                     se manda el filtro y el servidor resuelve la lista. --}}
                <label class="flex items-start gap-2 mb-3 p-3 rounded-lg bg-amber-50 border border-amber-200 cursor-pointer">
                    <input type="checkbox" name="todos" value="1" x-model="todoFiltrado"
                           class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm text-amber-900">
                        Imprimir <b>los {{ number_format($total) }}</b> productos que coinciden con el filtro,
                        no solo los {{ $productos->count() }} de la lista.
                        <span class="block text-[11px] text-amber-700 mt-0.5">
                            Se envía el filtro, no la selección. Máximo {{ number_format(App\Modules\Inventario\Controllers\EtiquetaController::MAX_ETIQUETAS) }} etiquetas por hoja (productos × copias).
                        </span>
                    </span>
                </label>
                {{-- El filtro viaja con el formulario de impresión para que el
                     servidor reconstruya exactamente la misma lista. --}}
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="categoria" value="{{ request('categoria') }}">
                @endif

                <div class="border border-gray-200 rounded-lg overflow-hidden max-h-[420px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @forelse($productos as $p)
                            @php $codigo = $p->sku ?: $p->barcode; @endphp
                            <tr class="hover:bg-gray-50 {{ blank($codigo) ? 'opacity-40' : '' }}">
                                <td class="px-3 py-2 w-10">
                                    <input type="checkbox" name="ids[]" value="{{ $p->id }}"
                                           x-model="seleccion" @if(blank($codigo)) disabled @endif
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                </td>
                                <td class="px-2 py-2">
                                    <div class="font-semibold text-gray-800">{{ $p->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $p->category->name ?? 'Sin categoría' }}</div>
                                </td>
                                <td class="px-2 py-2 text-xs font-mono text-gray-600">
                                    {{ $codigo ?: '— sin código —' }}
                                </td>
                                <td class="px-3 py-2 text-right text-xs text-gray-500 whitespace-nowrap">
                                    {{ $p->stock !== null ? $p->stock.' und' : '' }}
                                </td>
                            </tr>
                            @empty
                            <tr><td class="px-4 py-8 text-center text-gray-400 text-sm">No hay productos con ese filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
function etiquetas() {
    return {
        seleccion: [],
        campos: ['codigo', 'nombre', 'precio'],
        todoFiltrado: false,
        totalFiltrado: {{ (int) $total }},
        /* Con "todo lo filtrado" no se cuentan las casillas marcadas sino los
           productos que el servidor va a resolver. */
        get cuantasEtiquetas() {
            const base = this.todoFiltrado ? this.totalFiltrado : this.seleccion.length;
            return base * (this.copiasNum || 1);
        },
        get puedeImprimir() { return this.todoFiltrado || this.seleccion.length > 0; },
        tipoCodigo: 'qr',
        diseno: 'lateral',
        formato: 'a4-24',
        copiasNum: 1,
        medidas: @js(collect($formatos)->map(fn ($f) => ['ancho' => $f['ancho'], 'alto' => $f['alto']])),
        /* Por debajo de 25 mm de alto no cabe el mismo cuerpo de letra: la
           vista previa tiene que encogerlo igual que lo hace la hoja. */
        get alta() { return this.medidas[this.formato].alto >= 25; },
        get verMeta() { return this.campos.includes('categoria') || this.campos.includes('marca'); },
        get meta() {
            const m = [];
            if (this.campos.includes('categoria')) m.push(@js($muestra['categoria'] ?? ''));
            if (this.campos.includes('marca')) m.push(@js($muestra['marca'] ?? ''));
            return m.filter(Boolean).join(' · ');
        },
        {{-- Solo entran los que tienen codigo: el resto va `disabled` en la
             tabla, asi que marcarlos no tendria efecto al imprimir. --}}
        conCodigo: @js($productos->filter(fn ($p) => filled($p->sku ?: $p->barcode))->pluck('id')->map(fn ($i) => (string) $i)->values()),
        todos() { this.seleccion = [...this.conCodigo]; },
        ninguno() { this.seleccion = []; },
    };
}
</script>

</x-slot>
</x-app-layout>
