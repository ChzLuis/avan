{{--
    LECTOR DE COMPROBANTES — interfaz.

    Tres pasos en una sola ventana: subir, esperar y revisar. No es un flujo
    de emisión paralelo: al confirmar, rellena el formulario de siempre y a
    partir de ahí manda el proceso normal, con su revisión y su envío a SUNAT.

    En móvil el botón principal abre la cámara directamente (`capture`), que
    es como se usa esto de verdad: con el papel en la mano y el cliente
    delante.
--}}
<div x-show="lector.abierto" x-cloak
     class="fixed inset-0 z-50 flex items-end md:items-center justify-center"
     @keydown.escape.window="lector.abierto = false">

    <div class="absolute inset-0" style="background:rgba(15,23,42,.55)" @click="lector.abierto = false"></div>

    <div class="relative w-full md:max-w-2xl bg-white md:rounded-2xl rounded-t-2xl shadow-2xl
                max-h-[92vh] flex flex-col">

        {{-- Cabecera --}}
        <div class="px-5 py-3 border-b flex items-center justify-between flex-shrink-0" style="border-color:#e5e7eb;">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Leer comprobante</h3>
                <p class="text-xs text-gray-500 mt-0.5" x-text="lector.subtitulo()"></p>
            </div>
            <button @click="lector.abierto = false" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Cerrar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">

            {{-- ── PASO 1: subir ────────────────────────────────────────── --}}
            <template x-if="lector.paso === 'subir'">
                <div class="space-y-3">
                    {{-- Zona de arrastre: en escritorio es el camino natural;
                         en móvil queda debajo de los botones grandes. --}}
                    <label class="hidden md:flex flex-col items-center justify-center gap-2 py-10 px-4
                                  border-2 border-dashed rounded-xl cursor-pointer transition-colors"
                           :style="lector.arrastrando
                                ? 'border-color:#6366f1; background:#eef2ff'
                                : 'border-color:#d1d5db; background:#f9fafb'"
                           @dragover.prevent="lector.arrastrando = true"
                           @dragleave.prevent="lector.arrastrando = false"
                           @drop.prevent="lector.arrastrando = false; lector.elegir($event.dataTransfer.files[0])">
                        <svg class="w-9 h-9 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5 7.5 12M12 7.5V21"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Arrastra el comprobante aquí</span>
                        <span class="text-xs text-gray-400">o haz clic para elegir un archivo</span>
                        <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp,application/pdf"
                               @change="lector.elegir($event.target.files[0])">
                    </label>

                    {{-- Móvil primero: cámara arriba y a pantalla completa. --}}
                    <label class="md:hidden flex items-center justify-center gap-3 w-full py-5 rounded-xl
                                  bg-indigo-600 text-white font-bold text-base active:bg-indigo-700 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                        </svg>
                        Escanear comprobante
                        <input type="file" class="hidden" accept="image/*" capture="environment"
                               @change="lector.elegir($event.target.files[0])">
                    </label>

                    <div class="md:hidden grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-2 py-3.5 rounded-xl border border-gray-300
                                      text-sm font-semibold text-gray-700 active:bg-gray-50 cursor-pointer">
                            Galería
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp"
                                   @change="lector.elegir($event.target.files[0])">
                        </label>
                        <label class="flex items-center justify-center gap-2 py-3.5 rounded-xl border border-gray-300
                                      text-sm font-semibold text-gray-700 active:bg-gray-50 cursor-pointer">
                            Archivo o PDF
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp,application/pdf"
                                   @change="lector.elegir($event.target.files[0])">
                        </label>
                    </div>

                    <p class="text-xs text-gray-400 text-center">
                        JPG, PNG, WEBP o PDF · hasta 12 MB
                    </p>
                </div>
            </template>

            {{-- ── PASO 2: confirmar el archivo ─────────────────────────── --}}
            <template x-if="lector.paso === 'previa'">
                <div class="space-y-3">
                    <div class="rounded-xl overflow-hidden border" style="border-color:#e5e7eb;">
                        <template x-if="lector.previa">
                            <img :src="lector.previa" alt="Comprobante" class="w-full max-h-72 object-contain bg-gray-50">
                        </template>
                        <template x-if="!lector.previa">
                            <div class="py-10 text-center bg-gray-50">
                                <p class="text-sm font-semibold text-gray-700" x-text="lector.nombre"></p>
                                <p class="text-xs text-gray-400 mt-1">Documento PDF</p>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <button @click="lector.reiniciar()" class="btn-secondary text-sm flex-1">Cambiar</button>
                        <button @click="lector.analizar()" class="btn-primary text-sm flex-1">Analizar comprobante</button>
                    </div>
                </div>
            </template>

            {{-- ── PASO 3: leyendo ──────────────────────────────────────── --}}
            <template x-if="lector.paso === 'analizando'">
                <div class="py-14 text-center">
                    <svg class="w-10 h-10 mx-auto text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-90" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-800 mt-4">Analizando comprobante...</p>
                    <p class="text-xs text-gray-400 mt-1">Suele tardar unos segundos.</p>
                </div>
            </template>

            {{-- ── PASO 4: revisar ──────────────────────────────────────── --}}
            <template x-if="lector.paso === 'revisar'">
                <div class="space-y-4">

                    {{-- Ya importado antes: se avisa, no se bloquea. --}}
                    <template x-if="lector.duplicado">
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5">
                            <p class="text-xs font-semibold text-amber-800" x-text="lector.duplicado.mensaje"></p>
                            <p class="text-xs text-amber-700 mt-0.5">
                                Emitido el <span x-text="lector.duplicado.fecha"></span>
                                por S/ <span x-text="lector.duplicado.total.toFixed(2)"></span>.
                            </p>
                        </div>
                    </template>

                    <template x-if="lector.avisos.length">
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 space-y-1">
                            <template x-for="a in lector.avisos" :key="a">
                                <p class="text-xs text-amber-800" x-text="a"></p>
                            </template>
                        </div>
                    </template>

                    {{-- Identidad del comprobante --}}
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Comprobante detectado</p>
                        <p class="text-sm font-bold text-gray-900">
                            <span x-text="lector.datos.tipo === 'factura' ? 'Factura' : 'Boleta'"></span>
                            <span x-text="lector.numeroDoc()"></span>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="lector.datos.fecha_emision || 'Sin fecha legible'"></p>
                    </div>

                    {{-- Cliente --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Cliente</p>
                        <div class="rounded-xl border px-3 py-2.5" style="border-color:#e5e7eb;">
                            <p class="text-sm font-semibold text-gray-900"
                               x-text="lector.datos.cliente.razon_social || 'Sin nombre legible'"></p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span x-text="lector.datos.cliente.tipo_doc || 'Doc.'"></span>:
                                <span x-text="lector.datos.cliente.numero_doc || '—'"></span>
                            </p>
                            <p class="text-xs text-emerald-600 mt-1" x-show="lector.datos.cliente.conocido">
                                Ya es cliente tuyo: usamos sus datos registrados.
                            </p>
                        </div>
                    </div>

                    {{-- Productos y su coincidencia con el catálogo --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            Productos (<span x-text="lector.datos.items.length"></span>)
                        </p>
                        <div class="space-y-2">
                            <template x-for="(item, i) in lector.datos.items" :key="i">
                                <div class="rounded-xl border px-3 py-2.5" style="border-color:#e5e7eb;">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-gray-400">En el papel:</p>
                                            <p class="text-sm text-gray-700 truncate" x-text="item.descripcion_origen"></p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-sm font-semibold text-gray-900">
                                                <span x-text="item.cantidad"></span> ×
                                                <span x-text="Number(item.precio_unitario).toFixed(2)"></span>
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Producto del catálogo --}}
                                    <div class="mt-2 pt-2 border-t" style="border-color:#f3f4f6;">
                                        <template x-if="lector.elegido(i)">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs text-emerald-700 font-semibold truncate">
                                                    ✓ <span x-text="lector.elegido(i).nombre"></span>
                                                </p>
                                                <button @click="lector.quitar(i)"
                                                        class="text-xs text-gray-400 hover:text-gray-600 flex-shrink-0">Quitar</button>
                                            </div>
                                        </template>

                                        <template x-if="!lector.elegido(i) && item.sugerencias.length">
                                            <div class="space-y-1">
                                                <p class="text-xs text-gray-400">¿Es alguno de estos?</p>
                                                <template x-for="s in item.sugerencias" :key="s.id">
                                                    <button @click="lector.usar(i, s)"
                                                            class="w-full flex items-center justify-between gap-2 text-left px-2 py-1.5
                                                                   rounded-lg hover:bg-indigo-50 transition-colors">
                                                        <span class="text-xs text-gray-700 truncate" x-text="s.nombre"></span>
                                                        <span class="text-xs text-gray-400 flex-shrink-0"
                                                              x-text="Math.round(s.confianza * 100) + '%'"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="!lector.elegido(i) && !item.sugerencias.length">
                                            <p class="text-xs text-gray-400">
                                                No encontramos este producto en tu catálogo. Se emitirá como descripción libre.
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Totales --}}
                    <div class="rounded-xl bg-gray-50 p-3 space-y-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Subtotal</span>
                            <span x-text="lector.moneda(lector.datos.totales.subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>IGV</span>
                            <span x-text="lector.moneda(lector.datos.totales.igv)"></span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t" style="border-color:#e5e7eb;">
                            <span>Total</span>
                            <span x-text="lector.moneda(lector.datos.totales.total)"></span>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ── Error ───────────────────────────────────────────────── --}}
            <template x-if="lector.paso === 'error'">
                <div class="py-10 text-center">
                    <p class="text-sm font-semibold text-gray-800" x-text="lector.error"></p>
                    <button @click="lector.reiniciar()" class="btn-secondary text-sm mt-4">Probar con otra foto</button>
                </div>
            </template>
        </div>

        {{-- Pie --}}
        <div class="px-5 py-3 border-t bg-gray-50 flex gap-2 flex-shrink-0 md:rounded-b-2xl"
             style="border-color:#e5e7eb; padding-bottom: calc(12px + env(safe-area-inset-bottom));"
             x-show="lector.paso === 'revisar'">
            <button @click="lector.abierto = false" class="btn-secondary text-sm">Cancelar</button>
            <button @click="lector.aplicar()" class="btn-primary text-sm flex-1" :disabled="lector.aplicando">
                <span x-text="lector.aplicando ? 'Cargando...' : 'Usar estos datos'"></span>
            </button>
        </div>
    </div>
</div>
