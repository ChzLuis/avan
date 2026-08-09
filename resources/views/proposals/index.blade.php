<x-app-layout>
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Propuestas comerciales</h2>
</x-slot>

<div x-data="propuestas()" class="p-4 md:p-6 max-w-6xl mx-auto">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Registra tus proformas y comparte un documento profesional con el cliente.</p>
        <button @click="abrirNueva()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl px-4 py-2.5 transition">
            + Nueva propuesta
        </button>
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse min-w-[760px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-[11px] uppercase tracking-wide">
                        <th class="text-left font-bold px-4 py-3">N°</th>
                        <th class="text-left font-bold px-4 py-3">Cliente</th>
                        <th class="text-left font-bold px-4 py-3">Rubro</th>
                        <th class="text-right font-bold px-4 py-3">Inversión</th>
                        <th class="text-center font-bold px-4 py-3">Estado</th>
                        <th class="text-left font-bold px-4 py-3">Fecha</th>
                        <th class="text-right font-bold px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $p)
                    <tr class="border-t border-gray-100 hover:bg-gray-50/60">
                        <td class="px-4 py-3 font-mono text-xs font-bold text-gray-700">{{ $p->number }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $p->client_name }}</p>
                            @if($p->business_name)<p class="text-xs text-gray-400">{{ $p->business_name }}</p>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $p->rubro ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">S/ {{ number_format($p->price, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $badge = ['borrador'=>'bg-gray-100 text-gray-600','enviada'=>'bg-blue-100 text-blue-700','aceptada'=>'bg-green-100 text-green-700','rechazada'=>'bg-red-100 text-red-600'][$p->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ ucfirst($p->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $p->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('proposal.publica', $p->token) }}" target="_blank"
                                   class="text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition">Ver</a>
                                <button @click="copiar('{{ route('proposal.publica', $p->token) }}')"
                                        class="text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 px-2.5 py-1.5 rounded-lg transition">Copiar link</button>
                                @if($p->client_phone)
                                <a href="https://wa.me/{{ preg_replace('/\D/','',$p->client_phone) }}?text={{ urlencode('Le comparto nuestra propuesta comercial: '.route('proposal.publica', $p->token)) }}"
                                   target="_blank" class="text-xs font-semibold bg-green-100 text-green-700 hover:bg-green-200 px-2.5 py-1.5 rounded-lg transition">WhatsApp</a>
                                @endif
                                <button @click="eliminar({{ $p->id }})"
                                        class="text-xs text-gray-300 hover:text-red-500 px-1.5 transition">✕</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-14">
                        <p class="text-sm">Aún no has registrado propuestas.</p>
                        <button @click="abrirNueva()" class="text-indigo-600 text-sm font-semibold mt-1">Crear la primera →</button>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ MODAL NUEVA PROPUESTA ═══ --}}
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-start justify-center overflow-y-auto p-4"
         @click.self="abierto=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-6">
            <div class="px-6 py-4 border-b flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <h2 class="font-bold text-gray-800">Nueva propuesta comercial</h2>
                <button @click="abierto=false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="p-6 space-y-5">
                {{-- Cliente --}}
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-2">Datos del cliente</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Nombre del contacto *</label>
                            <input x-model="f.client_name" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="Ej: Carlos Mendoza">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Nombre del negocio</label>
                            <input x-model="f.business_name" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="Ej: Distribuidora El Sol">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Rubro</label>
                            <input x-model="f.rubro" list="rubros" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="Ej: Abarrotes">
                            <datalist id="rubros">
                                <option>Abarrotes / Minimarket</option><option>Ferretería</option>
                                <option>Restaurante / Cafetería</option><option>Boutique / Ropa</option>
                                <option>Farmacia / Botica</option><option>Tecnología</option>
                                <option>Salón de belleza</option><option>Distribuidora</option>
                            </datalist>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Ciudad</label>
                            <input x-model="f.city" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="Ej: Huacho">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Teléfono / WhatsApp</label>
                            <input x-model="f.client_phone" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="51987654321">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Correo</label>
                            <input x-model="f.client_email" type="email" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="cliente@correo.com">
                        </div>
                    </div>
                </div>

                {{-- Inversión --}}
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-2">Inversión</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Desarrollo (S/)</label>
                            <input x-model.number="f.price" type="number" min="0" class="w-full mt-1 text-sm font-bold border border-gray-200 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Renovación anual</label>
                            <input x-model.number="f.price_renewal" type="number" min="0" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Productos incluidos</label>
                            <input x-model.number="f.products_included" type="number" min="0" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Validez (días)</label>
                            <input x-model.number="f.valid_days" type="number" min="1" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                {{-- Servicios adicionales --}}
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-2">Servicios adicionales (marca los que apliquen)</p>
                    <div class="space-y-1.5">
                        <template x-for="(ex,i) in catalogoExtras" :key="i">
                            <label class="flex items-center gap-3 border border-gray-100 rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" :value="i" x-model="seleccion" class="w-4 h-4 accent-indigo-600">
                                <span class="flex-1 text-sm text-gray-700" x-text="ex.nombre"></span>
                                <span class="text-sm font-bold text-gray-800">S/ <span x-text="ex.precio"></span></span>
                                <span class="text-[11px] text-gray-400" x-text="etiqueta(ex.periodo)"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Notas --}}
                <div>
                    <label class="text-xs font-semibold text-gray-600">Consideraciones / alcance específico (opcional)</label>
                    <textarea x-model="f.extra_notes" rows="3" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2"
                              placeholder="Ej: El presupuesto publicitario no está incluido."></textarea>
                </div>

                <p x-show="error" class="text-sm text-red-600 font-semibold" x-text="error"></p>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white rounded-b-2xl">
                <button @click="abierto=false" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button @click="guardar()" :disabled="cargando"
                        class="px-5 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg disabled:opacity-50">
                    <span x-text="cargando ? 'Creando…' : 'Crear propuesta'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function propuestas() {
    return {
        abierto: false, cargando: false, error: '',
        seleccion: [],
        catalogoExtras: [
            { nombre: 'Bot para recepción automática de pedidos', precio: 20, periodo: 'mensual' },
            { nombre: 'Asesoría personalizada (2 h) para campañas Meta Ads', precio: 50, periodo: 'sesion' },
            { nombre: 'Administración mensual de campañas en Facebook e Instagram', precio: 200, periodo: 'mensual' },
            { nombre: 'Módulo Institucional (Nosotros, Misión, Visión, Contacto)', precio: 150, periodo: 'unico' },
        ],
        f: {},
        etiqueta(p) { return { unico:'pago único', mensual:'mensual', sesion:'por sesión', anual:'anual' }[p] || p; },
        abrirNueva() {
            this.f = { client_name:'', business_name:'', rubro:'', city:'', client_phone:'', client_email:'',
                       price:490, price_renewal:100, products_included:200, valid_days:15, extra_notes:'' };
            this.seleccion = []; this.error = ''; this.abierto = true;
        },
        async guardar() {
            if (!this.f.client_name || !this.f.client_name.trim()) { this.error = 'Escribe el nombre del cliente.'; return; }
            this.cargando = true; this.error = '';
            const extras = this.seleccion.map(i => this.catalogoExtras[i]);
            try {
                const r = await fetch('{{ route('proposals.store', $project) }}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
                    body: JSON.stringify(Object.assign({}, this.f, { extras })),
                });
                const d = await r.json();
                if (d.url) { window.open(d.url, '_blank'); location.reload(); }
                else { this.error = 'No se pudo crear la propuesta.'; }
            } catch (e) { this.error = 'Error de conexión.'; }
            this.cargando = false;
        },
        async copiar(url) {
            try { await navigator.clipboard.writeText(url); alert('Enlace copiado'); }
            catch (e) { window.prompt('Copia el enlace:', url); }
        },
        async eliminar(id) {
            if (!confirm('¿Eliminar esta propuesta?')) return;
            await fetch('{{ url()->current() }}/' + id, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
            });
            location.reload();
        },
    }
}
</script>
</x-app-layout>
