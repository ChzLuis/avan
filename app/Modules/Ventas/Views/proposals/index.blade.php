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
                                {{-- EDITAR: la ruta `update` existia desde el principio,
                                     pero no habia por donde llamarla: corregir un precio
                                     o el telefono obligaba a borrar y rehacer la
                                     propuesta, y el enlace ya compartido moria. --}}
                                <button @click='editar(@json($p))'
                                        class="text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg transition">Editar</button>
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
                <h2 class="font-bold text-gray-800" x-text="editandoId ? 'Editar propuesta comercial' : 'Nueva propuesta comercial'">Nueva propuesta comercial</h2>
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
                            {{-- Al escribir el rubro se rellenan apertura, motivo y
                                 la demo de ESE giro. Solo si estan vacios: nunca pisa
                                 lo que ya se escribio a mano. --}}
                            <input x-model="f.rubro" list="rubros" @change="rellenarPorRubro()"
                                   class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" placeholder="Ej: Abarrotes">
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

                {{-- Demostracion y plan: lo que hace que el cliente decida.
                     Ver la tienda de SU rubro antes de leer el precio es lo
                     que mejor cierra; y una propuesta sin plan recomendado
                     hace que el cliente postergue. --}}
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-2">Demostración y plan</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold text-gray-600">Enlace de la demo de su rubro</label>
                            <input x-model="f.demo_url" list="demos" type="url"
                                   class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2"
                                   placeholder="https://arindg.com/demofarma">
                            <datalist id="demos">
                                @foreach($demos as $d)
                                <option value="{{ $d['url'] }}">{{ $d['nombre'] }} — {{ $d['etiqueta'] }}</option>
                                @endforeach
                            </datalist>
                            <p class="text-[11px] text-gray-400 mt-1">Mandar la demo de otro rubro delata una propuesta copiada.</p>
                        </div>

                        {{-- Demos adicionales: van como muestra de trabajo, debajo de
                             la principal. Se marcan con casilla para no tener que
                             copiar direcciones a mano. --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-gray-600">
                                Otras tiendas que adjuntamos como ejemplo
                                <span class="font-normal text-gray-400">(opcional)</span>
                            </label>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-52 overflow-y-auto border border-gray-200 rounded-lg p-2.5">
                                @foreach($demos as $d)
                                <label class="flex items-center gap-2 text-xs text-gray-700 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" value="{{ $d['url'] }}"
                                           :checked="(f.demos_extra||[]).includes('{{ $d['url'] }}')"
                                           :disabled="f.demo_url === '{{ $d['url'] }}'"
                                           @change="alternarDemo('{{ $d['url'] }}')"
                                           class="rounded border-gray-300">
                                    <span :class="f.demo_url === '{{ $d['url'] }}' ? 'text-gray-300 line-through' : ''">
                                        {{ $d['nombre'] }}
                                        {{-- Un cliente real pesa mas que una demo: se
                                             distingue para elegirlo primero. --}}
                                        <span class="{{ ($d['real'] ?? false) ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">· {{ $d['etiqueta'] }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            {{-- Enlaces que no estan en la lista (una tienda recien
                                 entregada, un catalogo puntual). --}}
                            <div class="mt-2 flex gap-1.5">
                                <input type="url" x-model="urlManual" @keydown.enter.prevent="agregarUrlManual()"
                                       placeholder="https://otra-tienda.com  (pegar y añadir)"
                                       class="flex-1 text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                <button type="button" @click="agregarUrlManual()"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Añadir</button>
                            </div>
                            <p x-show="errorUrl" x-cloak class="text-[11px] text-red-600 mt-1" x-text="errorUrl"></p>

                            {{-- Las añadidas a mano se listan aparte, con su boton de quitar. --}}
                            <template x-if="urlsManuales().length">
                                <div class="mt-2 space-y-1">
                                    <template x-for="u in urlsManuales()" :key="u">
                                        <div class="flex items-center gap-2 text-xs bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5">
                                            <span class="flex-1 truncate text-gray-700" x-text="u.replace(/^https?:\/\//,'')"></span>
                                            <button type="button" @click="alternarDemo(u)" class="text-gray-400 hover:text-red-600" title="Quitar">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <p class="text-[11px] text-gray-400 mt-1">
                                La demo principal se muestra destacada arriba; estas van como referencia.
                                <span x-show="(f.demos_extra||[]).length >= 6" class="text-amber-600 font-semibold">Máximo 6.</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Plan que recomendamos</label>
                            <select x-model="f.plan_recomendado" @change="f.price = {start:490, pro:590, business:690}[f.plan_recomendado] || f.price"
                                    class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2">
                                <option value="start">Start — S/ 490</option>
                                <option value="pro">Pro — S/ 590</option>
                                <option value="business">Business — S/ 690</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Por qué ese plan</label>
                            <input x-model="f.plan_motivo"
                                   class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2"
                                   placeholder="Con 130 productos, el Start se le queda corto">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold text-gray-600">Párrafo de apertura</label>
                            <textarea x-model="f.apertura" rows="3"
                                      class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2"
                                      placeholder="Algo que viste de ESE negocio: 'atiende los pedidos por WhatsApp mandando las fotos una por una'"></textarea>
                            <p class="text-[11px] text-gray-400 mt-1">Si este texto le sirve a otro cliente, todavía no está listo.</p>
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
                    <span x-text="cargando ? (editandoId ? 'Guardando…' : 'Creando…') : (editandoId ? 'Guardar cambios' : 'Crear propuesta')"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function propuestas() {
    return {
        abierto: false, cargando: false, error: '',
        // null = propuesta nueva; con id = se esta corrigiendo esa.
        editandoId: null,
        seleccion: [],
        catalogoExtras: [
            { nombre: 'Bot para recepción automática de pedidos', precio: 20, periodo: 'mensual' },
            { nombre: 'Asesoría personalizada (2 h) para campañas Meta Ads', precio: 50, periodo: 'sesion' },
            { nombre: 'Administración mensual de campañas en Facebook e Instagram', precio: 200, periodo: 'mensual' },
            { nombre: 'Módulo Institucional (Nosotros, Misión, Visión, Contacto)', precio: 150, periodo: 'unico' },
        ],
        f: {},
        // Textos y demos por rubro (los sirve el servidor desde DemosPorRubro).
        textos: @js($textos),
        catalogoDemos: @js($demos),
        etiqueta(p) { return { unico:'pago único', mensual:'mensual', sesion:'por sesión', anual:'anual' }[p] || p; },
        abrirNueva() {
            this.f = { client_name:'', business_name:'', rubro:'', city:'', client_phone:'', client_email:'',
                       demo_url:'', demos_extra:[], plan_recomendado:'pro', plan_motivo:'', apertura:'',
                       price:590, price_renewal:100, products_included:200, valid_days:15, extra_notes:'' };
            this.seleccion = []; this.error = ''; this.abierto = true;
            this.editandoId = null;
        },
        /**
         * Abrir una propuesta YA creada para corregirla.
         *
         * Se reutiliza el mismo formulario: otra pantalla distinta para lo
         * mismo se desincroniza a la primera. `editandoId` decide despues si
         * se crea (POST) o se corrige (PUT), y el token del enlace no cambia:
         * lo que ya se mando por WhatsApp sigue funcionando.
         */
        editar(p) {
            this.f = {
                client_name: p.client_name || '', business_name: p.business_name || '',
                rubro: p.rubro || '', city: p.city || '',
                client_phone: p.client_phone || '', client_email: p.client_email || '',
                demo_url: p.demo_url || '', demos_extra: p.demos_extra || [],
                plan_recomendado: p.plan_recomendado || 'pro', plan_motivo: p.plan_motivo || '',
                apertura: p.apertura || '',
                // Vienen como texto decimal ("590.00"): sin Number el campo
                // numerico los rechaza y el precio se manda vacio.
                price: Number(p.price) || 0,
                price_renewal: Number(p.price_renewal) || 0,
                products_included: Number(p.products_included) || 0,
                valid_days: Number(p.valid_days) || 15,
                extra_notes: p.extra_notes || '',
                status: p.status || 'borrador',
            };
            // Las casillas de extras guardan indices del catalogo, no textos.
            const guardados = p.extras || [];
            this.seleccion = this.catalogoExtras
                .map((e, i) => guardados.includes(e) ? i : null)
                .filter(i => i !== null);
            this.error = ''; this.editandoId = p.id; this.abierto = true;
        },
        /* El rubro se escribe a mano ("Botica", "Pollería"): se empareja por
           pistas y sin tildes, igual que en el servidor. */
        claveRubro() {
            const t = (this.f.rubro || '').toLowerCase()
                .replace(/[áàä]/g,'a').replace(/[éèë]/g,'e').replace(/[íìï]/g,'i')
                .replace(/[óòö]/g,'o').replace(/[úùü]/g,'u').replace(/ñ/g,'n');
            if (!t) return null;
            const pistas = {
                farmacia:['farmacia','botica','droguer'], veterinaria:['veterinar','mascota','pet'],
                minimarket:['minimarket','market','abarrote','bodega'],
                restaurante:['restaur','polleri','pollo','menu','comida','cevicher'],
                licoreria:['licor','vino','cervez','destilad'],
                moda:['moda','ropa','boutique','jean','denim','calzado','zapat'],
                ferreteria:['ferreter','electric','construc'],
            };
            for (const [clave, lista] of Object.entries(pistas)) {
                if (lista.some(p => t.includes(p))) return clave;
            }
            return null;
        },
        /* Rellena lo que este VACIO. Nunca pisa texto ya escrito. */
        rellenarPorRubro() {
            const k = this.claveRubro();
            if (!k) return;
            if (!this.f.apertura) this.f.apertura = this.textos.aperturas[k] || '';
            if (!this.f.plan_motivo) this.f.plan_motivo = this.textos.motivos[k] || '';
            if (!this.f.demo_url) {
                const d = this.catalogoDemos.find(x => x.rubro === k);
                if (d) this.f.demo_url = d.url;
            }
        },
        alternarDemo(url) {
            if (!Array.isArray(this.f.demos_extra)) this.f.demos_extra = [];
            const i = this.f.demos_extra.indexOf(url);
            if (i >= 0) { this.f.demos_extra.splice(i, 1); return; }
            // El servidor rechaza mas de 6: avisar aqui en vez de fallar al guardar.
            if (this.f.demos_extra.length >= 6) { this.errorUrl = 'Máximo 6 tiendas de ejemplo.'; return; }
            this.errorUrl = '';
            this.f.demos_extra.push(url);
        },

        // ── Enlaces escritos a mano (los que no estan en la lista) ──
        urlManual: '',
        errorUrl: '',
        /** Las elegidas que NO vienen del catalogo: se listan aparte para poder quitarlas. */
        urlsManuales() {
            const conocidas = (this.catalogoDemos || []).map(d => d.url);
            return (this.f.demos_extra || []).filter(u => !conocidas.includes(u));
        },
        agregarUrlManual() {
            let u = (this.urlManual || '').trim();
            if (!u) return;
            if (!/^https?:\/\//i.test(u)) u = 'https://' + u;   // pegar sin https es lo normal
            try { new URL(u); } catch (e) { this.errorUrl = 'Esa dirección no es válida.'; return; }
            if ((this.f.demos_extra || []).includes(u) || this.f.demo_url === u) {
                this.errorUrl = 'Esa tienda ya está en la lista.'; return;
            }
            this.errorUrl = '';
            this.alternarDemo(u);
            if (!this.errorUrl) this.urlManual = '';
        },
        async guardar() {
            if (!this.f.client_name || !this.f.client_name.trim()) { this.error = 'Escribe el nombre del cliente.'; return; }
            this.cargando = true; this.error = '';
            const extras = this.seleccion.map(i => this.catalogoExtras[i]);
            // Editando: PUT sobre la propuesta; nueva: POST. El token del
            // enlace no cambia al editar, asi que lo ya compartido sigue vivo.
            const editando = !! this.editandoId;
            const url = editando
                ? '{{ url()->current() }}/' + this.editandoId
                : '{{ route('proposals.store', $project) }}';
            try {
                const r = await fetch(url, {
                    method: editando ? 'PUT' : 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
                    body: JSON.stringify(Object.assign({}, this.f, { extras })),
                });
                /* Mirar res.ok: un 422 traia los motivos en el cuerpo y sin
                   leerlos el usuario veia un exito falso y perdia lo escrito,
                   que es como se rompio el guardado del editor de productos. */
                const d = await r.json().catch(() => ({}));
                if (! r.ok) {
                    this.error = d.message
                        || Object.values(d.errors || {}).flat()[0]
                        || (editando ? 'No se pudo guardar el cambio.' : 'No se pudo crear la propuesta.');
                    this.cargando = false;
                    return;
                }
                // Al editar no se reabre la pestaña: quien corrige un precio
                // no quiere una ventana nueva cada vez.
                if (! editando && d.url) window.open(d.url, '_blank');
                location.reload();
            } catch (e) { this.error = 'Error de conexión.'; }
            this.cargando = false;
        },
        async copiar(url) {
            try { await navigator.clipboard.writeText(url); bxAviso('Enlace copiado', 'exito'); }
            catch (e) {
                await bxConfirmar({
                    titulo: 'Copia el enlace', descripcion: 'Tu navegador no permitió copiarlo solo. Selecciónalo y cópialo.',
                    boton: 'Listo', tono: 'principal', cancelar: '', entrada: { etiqueta: 'Enlace', valor: url, soloLectura: true },
                });
            }
        },
        async eliminar(id) {
            if (! await bxConfirmar({ descripcion: '¿Eliminar esta propuesta?' })) return;
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
