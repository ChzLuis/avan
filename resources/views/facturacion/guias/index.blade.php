@php
    $storeUrl  = route('guias.store');
    $enviarUrl = route('guias.enviar', '__ID__');
@endphp

<x-app-layout>
<x-slot name="slot">

<div class="max-w-6xl mx-auto px-4 py-6" x-data="guiasPage()">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Guías de remisión</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                La factura dice qué se vendió; la guía dice cómo viajó. Serie
                <span class="font-semibold text-gray-700">{{ $serie }}</span>.
            </p>
        </div>
        <button @click="abrirNueva()"
                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">
            Nueva guía
        </button>
    </div>

    {{-- Sin nada todavía --}}
    @if($guias->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-sm font-semibold text-gray-600">Todavía no has emitido ninguna guía.</p>
            <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">
                Emítela antes de que salga el transporte: la guía tiene que viajar
                físicamente con la mercadería.
            </p>
        </div>
    @else
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold">Número</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Destinatario</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Traslado</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Peso</th>
                        <th class="px-4 py-2.5 text-left font-semibold">SUNAT</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($guias as $g)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $g->numero }}</p>
                            <p class="text-xs text-gray-400">{{ $g->fecha_traslado?->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ $g->destinatario_nombre }}</p>
                            <p class="text-xs text-gray-400">{{ $g->destinatario_doc_numero }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-700">{{ $g->motivoLegible() }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $g->esPublico() ? 'Transporte público' : 'Vehículo '.$g->vehiculo_placa }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700 whitespace-nowrap">
                            {{ rtrim(rtrim(number_format((float) $g->peso_total, 3, '.', ''), '0'), '.') }}
                            <span class="text-xs text-gray-400">{{ $g->peso_unidad }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                @class([
                                    'bg-green-100 text-green-700'   => $g->sunat_status === 'accepted',
                                    'bg-red-100 text-red-700'       => in_array($g->sunat_status, ['rejected', 'error'], true),
                                    'bg-yellow-100 text-yellow-700' => $g->sunat_status === 'pending',
                                    'bg-gray-100 text-gray-600'     => ! $g->sunat_status,
                                ])">{{ $g->estadoSunatLegible() }}</span>
                            @if($g->sunat_error)
                                <p class="text-xs text-red-600 mt-1 max-w-xs break-words">{{ $g->sunat_error }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('guias.pdf', $g->id) }}" target="_blank"
                               class="text-xs font-semibold text-gray-500 hover:text-gray-800 mr-3">
                                Imprimir
                            </a>
                            @if($g->sunat_status !== 'accepted')
                            <button @click="reenviar({{ $g->id }})"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                Enviar a SUNAT
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══ Nueva guía ═══════════════════════════════════════════════════ --}}
    <div x-show="abierta" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto">
        <div class="absolute inset-0" style="background:rgba(15,23,42,.5)" @click="abierta = false"></div>

        <div class="relative w-full max-w-3xl my-6 rounded-2xl bg-white shadow-2xl">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Nueva guía de remisión</h3>
                <p class="text-xs text-gray-500 mt-0.5">Se emitirá con la serie {{ $serie }} y se enviará a SUNAT.</p>
            </div>

            <div class="px-6 py-5 space-y-5">

                {{-- De dónde nace --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Comprobante que respalda el traslado <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <select x-model="form.invoice_id" @change="desdeVenta()" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Sin comprobante — traslado que no es una venta</option>
                        @foreach($comprobantes as $c)
                        <option value="{{ $c->id }}"
                                data-nombre="{{ $c->client_name }}"
                                data-doc="{{ $c->client_doc_number }}"
                                data-dir="{{ $c->client_address }}">
                            {{ $c->numero }} — {{ $c->client_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Destinatario --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Destinatario</label>
                        <input type="text" x-model="form.destinatario_nombre" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">RUC o DNI</label>
                        <input type="text" x-model="form.destinatario_doc_numero" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                {{-- Traslado --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo</label>
                        <select x-model="form.motivo_codigo" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($motivos as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha de traslado</label>
                        <input type="date" x-model="form.fecha_traslado" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Modalidad</label>
                        <select x-model="form.modalidad" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($modalidades as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Peso y bultos: lo primero que miran en un control --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Peso total</label>
                        <input type="number" step="0.001" min="0" x-model="form.peso_total" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unidad</label>
                        <select x-model="form.peso_unidad" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="KGM">Kilogramos</option>
                            <option value="TNE">Toneladas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Bultos</label>
                        <input type="number" min="0" x-model="form.bultos" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                {{-- Puntos --}}
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Punto de partida</label>
                        <input type="text" x-model="form.partida_direccion" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Dirección de donde sale">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Punto de llegada</label>
                        <input type="text" x-model="form.llegada_direccion" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Dirección de destino">
                    </div>
                </div>

                {{-- Quién lo lleva: una modalidad u otra, nunca las dos --}}
                <div x-show="form.modalidad === '01'" class="rounded-xl bg-gray-50 p-3 space-y-3">
                    <p class="text-xs font-semibold text-gray-600">Transportista</p>
                    <div class="grid md:grid-cols-3 gap-3">
                        <input type="text" x-model="form.transportista_ruc" class="rounded-lg border-gray-300 text-sm" placeholder="RUC">
                        <input type="text" x-model="form.transportista_razon_social" class="md:col-span-2 rounded-lg border-gray-300 text-sm" placeholder="Razón social">
                    </div>
                </div>

                <div x-show="form.modalidad === '02'" class="rounded-xl bg-gray-50 p-3 space-y-3">
                    <p class="text-xs font-semibold text-gray-600">Vehículo y conductor</p>
                    <div class="grid md:grid-cols-3 gap-3">
                        <input type="text" x-model="form.vehiculo_placa" class="rounded-lg border-gray-300 text-sm" placeholder="Placa">
                        <input type="text" x-model="form.conductor_doc_numero" class="rounded-lg border-gray-300 text-sm" placeholder="DNI del conductor">
                        <input type="text" x-model="form.conductor_licencia" class="rounded-lg border-gray-300 text-sm" placeholder="Licencia">
                        <input type="text" x-model="form.conductor_nombres" class="rounded-lg border-gray-300 text-sm" placeholder="Nombres">
                        <input type="text" x-model="form.conductor_apellidos" class="md:col-span-2 rounded-lg border-gray-300 text-sm" placeholder="Apellidos">
                    </div>
                </div>

                {{-- Qué viaja --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-600">Bienes que se trasladan</p>
                        <button @click="form.items.push({description:'', unit:'NIU', quantity:1})"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ Agregar línea</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, i) in form.items" :key="i">
                            <div class="grid grid-cols-12 gap-2">
                                <input type="text" x-model="item.description" class="col-span-5 rounded-lg border-gray-300 text-sm" placeholder="Descripción">
                                <select x-model="item.unit" class="col-span-4 rounded-lg border-gray-300 text-sm">
                                    @foreach($unidades as $codigo => $nombre)
                                    <option value="{{ $codigo }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.001" min="0" x-model="item.quantity" class="col-span-2 rounded-lg border-gray-300 text-sm">
                                <button @click="form.items.splice(i,1)" x-show="form.items.length > 1"
                                        class="col-span-1 text-gray-300 hover:text-red-500 text-lg leading-none">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="errores.length">
                    <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2">
                        <template x-for="e in errores" :key="e">
                            <p class="text-xs text-red-700" x-text="e"></p>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2 rounded-b-2xl">
                <button @click="abierta = false" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600">Cancelar</button>
                <button @click="emitir()" :disabled="enviando"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60">
                    <span x-text="enviando ? 'Emitiendo...' : 'Emitir y enviar a SUNAT'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function guiasPage() {
    return {
        abierta: false,
        enviando: false,
        errores: [],
        form: {},

        abrirNueva() {
            this.errores = [];
            this.form = {
                invoice_id: '',
                destinatario_nombre: '', destinatario_doc_numero: '',
                motivo_codigo: '01',
                fecha_traslado: new Date().toISOString().slice(0, 10),
                modalidad: '02',
                peso_total: '', peso_unidad: 'KGM', bultos: '',
                partida_direccion: @json($project->address ?? ''), llegada_direccion: '',
                transportista_ruc: '', transportista_razon_social: '',
                vehiculo_placa: '', conductor_doc_numero: '',
                conductor_nombres: '', conductor_apellidos: '', conductor_licencia: '',
                items: [{ description: '', unit: 'NIU', quantity: 1 }],
            };
            this.abierta = true;
        },

        /* Si el traslado nace de una venta, el destinatario, la direccion y
           LOS BIENES ya estan en el comprobante: volver a teclearlos solo
           introduce erratas. Las lineas llegan del endpoint de opciones con
           la unidad ya traducida al codigo de SUNAT. */
        async desdeVenta() {
            if (!this.form.invoice_id) return;

            const opcion = this.$el.querySelector(`option[value="${this.form.invoice_id}"]`);
            if (opcion) {
                this.form.destinatario_nombre     = opcion.dataset.nombre || this.form.destinatario_nombre;
                this.form.destinatario_doc_numero = opcion.dataset.doc    || this.form.destinatario_doc_numero;
                this.form.llegada_direccion       = opcion.dataset.dir    || this.form.llegada_direccion;
            }

            const res = await fetch(`{{ route('guias.opciones') }}?invoice_id=${this.form.invoice_id}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));

            if (data.desde_venta?.items?.length) {
                this.form.items = data.desde_venta.items.map(i => ({
                    description: i.description, unit: i.unit, quantity: i.quantity,
                }));
            }
        },

        async emitir() {
            if (this.enviando) return;
            this.enviando = true;
            this.errores  = [];

            const cuerpo = { ...this.form };
            if (!cuerpo.invoice_id) delete cuerpo.invoice_id;
            if (!cuerpo.bultos) delete cuerpo.bultos;

            const res = await fetch('{{ $storeUrl }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(cuerpo),
            });

            const data = await res.json().catch(() => ({}));
            this.enviando = false;

            if (!res.ok) {
                // Se muestran todos los motivos juntos: corregir de uno en uno
                // con el camion esperando no ayuda a nadie.
                this.errores = data.errors
                    ? Object.values(data.errors).flat()
                    : [data.message || 'No se pudo emitir la guía.'];
                return;
            }

            bxAviso(data.message || 'Guía emitida.', 'exito');
            setTimeout(() => location.reload(), 800);
        },

        async reenviar(id) {
            const res = await fetch('{{ $enviarUrl }}'.replace('__ID__', id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                bxAviso(data.message || 'No se pudo enviar.', 'error');
                return;
            }

            bxAviso(data.message || 'Enviando...', 'exito');
            setTimeout(() => location.reload(), 900);
        },
    };
}
</script>

</x-slot>
</x-app-layout>
