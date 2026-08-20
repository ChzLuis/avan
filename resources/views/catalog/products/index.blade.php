<x-app-layout>
<x-slot name="slot">

@php
    $csrf     = csrf_token();
    $pid      = $project->id;
    $base     = url("/bixoadmin");
    $currency = $project->setting('currency_symbol') ?: $project->setting('currency', 'S/');

    $supplierList = $project->catalogLists()->where('type', 'proveedor')->with('values')->first();
    $suppliers    = $supplierList ? $supplierList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $locationList = $project->catalogLists()->where('type', 'ubicacion')->with('values')->first();
    $locations    = $locationList ? $locationList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $taxList  = $project->catalogLists()->where('type', 'impuesto')->with('values')->first();
    $taxes    = $taxList ? $taxList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $allCatalogs = $project->catalogLists()->where('is_active', true)->count();

    // Hueco a la izquierda de los campos de dinero, según el símbolo configurado:
    // "S/" necesita menos que "PEN" o "US$". 12px del margen + ancho + respiro.
    $curPad = 12 + (mb_strlen(trim($currency)) * 9) + 8;
@endphp

{{-- ── Sistema visual del editor de productos ──────────────────────────────
     Clases con prefijo .pe- (product editor), con alcance limitado a esta
     página. NO se tocan .input/.label/.btn-primary/etc. globales de
     app.css: se usan en 30+ vistas del panel y cambiar su aspecto ahí
     afectaría a toda la app, no solo al catálogo. --}}
<style>
    .pe-shell { background: #F8FAFC; }
    .pe-card { background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px; }
    .pe-input, .pe-shell select.pe-input, textarea.pe-input {
        width: 100%; height: 42px; padding: 0 12px; font-size: 13.5px; line-height: 1.2;
        background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 9px; color: #111827;
        transition: border-color .15s ease, box-shadow .15s ease; font-family: inherit;
    }
    textarea.pe-input { height: auto; padding: 10px 12px; resize: vertical; }
    .pe-input:focus, textarea.pe-input:focus { outline: none; border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
    .pe-input::placeholder { color: #9CA3AF; }
    .pe-input:disabled, .pe-input[readonly] { background: #F3F4F6; color: #6B7280; }
    /* Inputs de dinero: el simbolo va posicionado absoluto encima del input.
       .pe-input pisa el pl-10 de Tailwind (mismo peso, se carga despues), asi
       que el numero quedaba DEBAJO del simbolo. Dos clases > una: gana esta.
       El hueco se calcula segun el simbolo real: "S/" ocupa menos que "PEN". */
    .pe-input.pl-10 { padding-left: var(--cur-pad, 2.5rem); }
    .pe-label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .pe-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; line-height: 1.4; }
    .pe-hint-ok { font-size: 11px; color: #059669; margin-top: 4px; }
    .pe-section-title { font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .05em; }
    /* Las miniaturas no pueden empujar su columna mas alto que la foto principal:
       con quince imagenes la pestana crecia sin fin. */
    @media (min-width: 768px) { .pe-thumbs { max-height: 32rem; overflow-y: auto; } }
    /* Las acciones de una imagen aparecen tambien al llegar con el teclado, no
       solo al pasar el raton por encima. */
    .group:focus-within .pe-acts { opacity: 1; }
    /* Con una sola imagen, el boton de agregar ocupa el alto que le sobra a la
       columna en vez de quedarse enano al lado de una foto grande. */
    .pe-add { flex: 1 1 auto; min-height: 5rem; }
    .pe-btn { height: 42px; padding: 0 16px; border-radius: 9px; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: background-color .15s ease, border-color .15s ease, color .15s ease; white-space: nowrap; }
    .pe-btn-sm { height: 36px; padding: 0 12px; font-size: 12.5px; }
    .pe-btn-primary { background: #4F46E5; color: #fff; }
    .pe-btn-primary:hover { background: #4338CA; }
    .pe-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
    .pe-btn-secondary { background: #FFFFFF; color: #374151; border: 1px solid #E5E7EB; }
    .pe-btn-secondary:hover { background: #F9FAFB; border-color: #D1D5DB; }
    /* Acción comercial destacada (compartir catálogo): resalta sobre las
       secundarias sin competir con la primaria "Nuevo producto". */
    .pe-btn-soft { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
    .pe-btn-soft:hover { background: #E0E7FF; border-color: #A5B4FC; }
    .pe-btn-icon { width: 42px; padding: 0; }

    /* ── Editor de descripción con formato básico ── */
    .pe-editor { border: 1px solid #E5E7EB; border-radius: 9px; overflow: hidden; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
    .pe-editor:focus-within { border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
    .pe-editor-bar { display: flex; align-items: center; gap: 2px; padding: 5px 6px; background: #F9FAFB; border-bottom: 1px solid #EEF0F3; flex-wrap: wrap; }
    .pe-tool { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: #4B5563; font-size: 13px; line-height: 1; transition: background-color .12s ease, color .12s ease; }
    .pe-tool:hover { background: #E5E7EB; color: #111827; }
    .pe-tool-sep { width: 1px; height: 18px; background: #E5E7EB; margin: 0 4px; }
    .pe-editor-body { min-height: 132px; max-height: 320px; overflow-y: auto; padding: 10px 12px; font-size: 13.5px; line-height: 1.6; color: #111827; outline: none; }
    .pe-editor-body:empty::before { content: attr(data-placeholder); color: #9CA3AF; }
    .pe-editor-body ul { list-style: disc; padding-left: 20px; margin: 4px 0; }
    .pe-editor-body ol { list-style: decimal; padding-left: 22px; margin: 4px 0; }
    .pe-editor-body li { margin: 1px 0; }
    .pe-editor-body b, .pe-editor-body strong { font-weight: 700; }
    .pe-btn-ghost-danger { background: transparent; color: #DC2626; }
    .pe-btn-ghost-danger:hover { background: #FEF2F2; }
    .pe-chip { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; background: #EEF2FF; color: #4338CA; border-radius: 999px; font-size: 12.5px; font-weight: 600; }
    .pe-chip button { color: #818CF8; line-height: 1; }
    .pe-chip button:hover { color: #4338CA; }
    .pe-chip-input { border: 1px dashed #C7D2FE; border-radius: 999px; padding: 5px 12px; font-size: 12.5px; color: #4338CA; background: #fff; min-width: 96px; }
    .pe-chip-input:focus { outline: none; border-color: #6366F1; border-style: solid; }
    .pe-chip-input::placeholder { color: #A5B4FC; }
</style>

<div class="flex flex-col h-full w-full overflow-hidden pe-shell" x-data="productPage()" style="--cur-pad: {{ $curPad }}px">

{{-- TOP BAR --}}
<div class="flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0"
     :class="panel==='detail' ? 'hidden md:flex' : 'flex'">
    <div class="flex items-center gap-2.5">
        <h1 class="text-lg font-semibold text-gray-800">Catálogo de Productos</h1>
        <span class="inline-flex items-center h-5 px-2 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold" id="product-count-label">Cargando...</span>
    </div>
    <div class="flex items-center gap-2 flex-wrap sm:justify-end">
        <a href="{{ route('categories.index') }}"
           class="pe-btn pe-btn-secondary">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Categorías
        </a>
        {{-- Acción comercial: el catálogo que se le manda al cliente va a un solo
             clic, no escondido en el menú técnico de importar/exportar. --}}
        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-catalog-pdf'))" class="pe-btn pe-btn-soft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Catálogo PDF
        </button>
        <button onclick="window.dispatchEvent(new Event('open-new-product'))" class="pe-btn pe-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo producto
        </button>
        <div class="relative" x-data="{ open: false }">
            <button @click="open=!open" class="pe-btn pe-btn-secondary pe-btn-icon" title="Importar, exportar y más" aria-label="Más acciones">
                <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>
                </svg>
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-50">
                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Importar</p>
                <a href="{{ route('products.template') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Descargar plantilla Excel
                </a>
                <label class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importar archivo
                    <input type="file" accept=".csv,.txt,.xls,.xlsx" class="hidden" id="import-file"
                           @change="window.dispatchEvent(new CustomEvent('do-import-csv', { detail: { file: $event.target.files[0] } })); $event.target.value=''">
                </label>

                <div class="border-t border-gray-100 my-1.5"></div>
                <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Exportar datos</p>
                <a href="{{ route('products.export') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Excel — todo el catálogo
                </a>
                @if(auth()->user()?->is_superadmin)
                <a href="{{ route('products.export.meli') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-yellow-500">ML</span>
                    Mercado Libre
                </a>
                <a href="{{ route('products.export.rappi') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-orange-500">R</span>
                    Rappi
                </a>
                <a href="{{ route('products.export.shopee') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-red-500">S</span>
                    Shopee
                </a>
                <a href="{{ route('products.export.static') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Catálogo web (GitHub Pages)
                </a>

                <div class="border-t border-gray-100 my-1.5"></div>
                <div class="mx-2 mb-1 rounded-lg bg-red-50 border border-red-100 py-1">
                    <p class="px-3 pt-1 pb-1.5 text-[10px] font-bold text-red-400 uppercase tracking-wider">Administración</p>
                    {{-- El componente productPage() vive más abajo (otro scope Alpine):
                         se invoca por evento global para que el clic siempre llegue. --}}
                    <button type="button" @click="open = false; window.dispatchEvent(new CustomEvent('purge-catalog'))"
                            class="w-full flex items-center gap-2.5 px-3 py-1.5 text-sm text-red-600 hover:bg-red-100 rounded-md text-left">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Vaciar catálogo completo…
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal: Catálogo PDF (alcance + precios) --}}
<div x-data="{ show: false, scope: 'all', prices: 'retail', layout: 'grid3', cover: true,
     /* Cuántos productos entran en el PDF. Para perfiles el alcance lo resuelve
        el servidor (incluye asignaciones directas), así que ahí no se estima. */
     get pdfCount() {
         const avail = this.products.filter(p => p.is_available);
         if (this.scope === 'all') return avail.length;
         if (this.scope.startsWith('c:')) {
             const id = this.scope.slice(2);
             const cat = this.categories.find(c => String(c.id) === id);
             const ids = [id, ...((cat?.children) || []).map(s => String(s.id))];
             return avail.filter(p => ids.includes(String(p.category_id))).length;
         }
         return null;
     } }"
     @open-catalog-pdf.window="show = true"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.5)"
     @click.self="show = false" @keydown.escape.window="show = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        {{-- Cabecera con color: da identidad al documento que se va a generar --}}
        <div class="px-5 py-4 flex items-start gap-3" style="background:linear-gradient(135deg,#4F46E5,#7C3AED)">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.18)">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-white font-bold text-base leading-tight">Catálogo en PDF</h3>
                <p class="text-xs mt-0.5 leading-snug" style="color:rgba(255,255,255,.82)">
                    Con fotos y precios, listo para enviar a tus clientes por WhatsApp o imprimir.
                </p>
            </div>
        </div>

        <div class="p-5 space-y-4 overflow-y-auto" style="max-height:66vh">

        {{-- Qué hace, en una frase: el usuario no debería adivinarlo --}}
        <p class="text-[12.5px] text-gray-500 leading-relaxed bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5">
            Arma un documento con tus productos —<strong class="text-gray-700">foto, nombre y precio</strong>— ordenados por categoría.
            Se abre en una pestaña nueva para que lo <strong class="text-gray-700">guardes como PDF</strong>, lo imprimas o se lo envíes a un cliente.
        </p>

        <div>
            <label class="pe-label">Qué incluir</label>
            <select x-model="scope" class="pe-input">
                <option value="all">Todo el catálogo</option>
                @if($pdfProfiles->isNotEmpty())
                <optgroup label="Por perfil">
                    @foreach($pdfProfiles as $pf)
                    <option value="p:{{ $pf->id }}">{{ $pf->name }}</option>
                    @endforeach
                </optgroup>
                @endif
                @if($categories->isNotEmpty())
                <optgroup label="Por categoría">
                    @foreach($categories as $cat)
                    <option value="c:{{ $cat->id }}">{{ $cat->name }}</option>
                        @foreach($cat->children as $sub)
                        <option value="c:{{ $sub->id }}">— {{ $sub->name }}</option>
                        @endforeach
                    @endforeach
                </optgroup>
                @endif
            </select>
            <p class="pe-hint" x-show="pdfCount === null">Al elegir una categoría raíz se incluyen sus subcategorías.</p>
            <div x-show="pdfCount !== null" x-cloak
                 class="mt-2 flex items-center gap-2 rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2">
                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs font-semibold text-indigo-700">
                    <span x-text="pdfCount"></span> producto<span x-show="pdfCount !== 1">s</span> entrarán en el catálogo
                </span>
            </div>
            <div x-show="pdfCount !== null && pdfCount > 300" x-cloak
                 class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-[11px] text-amber-800 leading-relaxed">
                ⚠️ Son muchos productos: el navegador puede tardar en cargar todas las fotos.
                Te conviene generar un catálogo por categoría.
            </div>
        </div>
        <div>
            <label class="pe-label">Precios a mostrar</label>
            <select x-model="prices" class="pe-input">
                <option value="retail">Precio de venta (minorista)</option>
                <option value="wholesale">Precio mayorista</option>
                <option value="none">Sin precios</option>
            </select>
        </div>
        <div>
            <label class="pe-label">Presentación</label>
            {{-- Miniaturas reales de la grilla: el usuario ve lo que va a obtener,
                 en vez de adivinar con caracteres de texto. --}}
            <div class="grid grid-cols-3 gap-2">
                @foreach([['grid2','Detallado',2,4,'13px'],['grid3','Estándar',3,6,'10px'],['grid4','Compacto',4,8,'8px']] as [$key,$label,$cols,$blocks,$h])
                <button type="button" @click="layout='{{ $key }}'"
                        :class="layout==='{{ $key }}' ? 'border-indigo-600 ring-2 ring-indigo-200' : 'border-gray-200 hover:border-indigo-300'"
                        class="rounded-xl border bg-white p-2.5 transition text-center">
                    <span class="block" style="display:grid;grid-template-columns:repeat({{ $cols }},1fr);gap:3px">
                        @for($i = 0; $i < $blocks; $i++)
                        <i :style="'display:block;border-radius:2px;height:{{ $h }};background:' + (layout==='{{ $key }}' ? '#6366F1' : '#D8DEE7')"></i>
                        @endfor
                    </span>
                    <span class="block text-[11px] font-bold mt-2"
                          :class="layout==='{{ $key }}' ? 'text-indigo-700' : 'text-gray-500'">{{ $label }}</span>
                </button>
                @endforeach
            </div>
            <p class="pe-hint" x-text="layout==='grid2' ? 'Fotos grandes con descripción del producto.' : (layout==='grid4' ? 'Más productos por hoja, ahorra papel.' : '3 productos por fila — recomendado.')"></p>
        </div>
        <label class="flex items-center gap-2.5 cursor-pointer">
            <button type="button" @click="cover = !cover" role="switch" :aria-checked="cover"
                    :class="cover ? 'bg-indigo-600' : 'bg-gray-200'"
                    class="relative w-10 h-5 rounded-full transition-colors flex-shrink-0">
                <span :class="cover ? 'translate-x-5' : 'translate-x-0.5'"
                      class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform block"></span>
            </button>
            <span>
                <span class="block text-sm font-medium text-gray-700">Incluir portada</span>
                <span class="block text-xs text-gray-400">Con tu logo, índice y código QR de la tienda.</span>
            </span>
        </label>
        </div>{{-- /cuerpo --}}

        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2">
            <button type="button" @click="show=false" class="pe-btn pe-btn-secondary flex-1">Cancelar</button>
            <button type="button" class="pe-btn pe-btn-primary flex-1"
                    @click="let u = '{{ route('products.catalog.pdf') }}?prices=' + prices + '&layout=' + layout + '&cover=' + (cover ? 1 : 0);
                            if (scope.startsWith('c:')) u += '&category_id=' + scope.slice(2);
                            if (scope.startsWith('p:')) u += '&profile_id=' + scope.slice(2);
                            window.open(u, '_blank'); show = false;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Generar catálogo
            </button>
        </div>
    </div>
</div>

{{-- BODY --}}
<script>
window.__productPageData = {
    products:   {!! Illuminate\Support\Js::from($products->map(fn($p) => [
        'id'               => $p->id,
        'name'             => $p->name,
        'sku'              => $p->sku ?? '',
        'barcode'          => $p->barcode ?? '',
        'description'      => $p->description ?? '',
        'notes'            => $p->notes ?? '',
        'price'            => (float)$p->price,
        'compare_price'    => $p->compare_price !== null ? (float)$p->compare_price : null,
        'wholesale_price'  => $p->wholesale_price !== null ? (float)$p->wholesale_price : null,
        'sizes'            => implode(', ', $p->sizes),
        'wholesale_min_qty'=> $p->wholesale_min_qty ?? null,
        'wholesale_unit'   => $p->wholesale_unit ?? '',
        'cost'             => $p->cost !== null ? (float)$p->cost : null,
        'unit'             => $p->unit ?? '',
        'stock'            => (int)($p->stock ?? 0),
        'stock_min'        => (int)($p->stock_min ?? 0),
        'stock_max'        => (int)($p->stock_max ?? 0),
        'location'         => '',
        'supplier'         => '',
        'has_tax'          => (bool)$p->has_tax,
        'tax_rate'         => $p->tax_rate !== null ? (float)$p->tax_rate : 18,
        'is_available'     => (bool)$p->is_available,
        'category_id'      => $p->category_id ? (string)$p->category_id : '',
        'brand_catalog_id' => $p->brand_catalog_id,
        'category_name'    => $p->category?->name ?? '',
        'images'           => $p->images->map(fn($i) => ['id'=>$i->id,'url'=>$i->url,'is_main'=>$i->is_main])->values()->toArray(),
        'main_image'       => $p->main_image_url,
    ])) !!},
    categories: {!! Illuminate\Support\Js::from($categories) !!},
    brands:     {!! Illuminate\Support\Js::from($brands->map(fn($b) => ['id'=>$b->id,'label'=>$b->label])) !!},
    units:      {!! Illuminate\Support\Js::from($units->map(fn($u) => $u->label)) !!},
    suppliers:  {!! Illuminate\Support\Js::from($suppliers->map(fn($s) => $s->label)) !!},
    locations:  {!! Illuminate\Support\Js::from($locations->map(fn($l) => $l->label)) !!},
    taxes:      {!! Illuminate\Support\Js::from($taxes->map(fn($t) => ['label'=>$t->label,'rate'=>18])) !!},
    hasCatalogs: {{ $allCatalogs > 0 ? 'true' : 'false' }},
    baseUrl:    '{{ $base }}',
    csrf:       '{{ $csrf }}',
};
document.addEventListener('alpine:init', () => {

    /* Editor de descripción: contenteditable + execCommand. Se eligió así para
       no depender de ninguna librería externa (funciona sin internet y sin
       tocar el build). Escribe en form.description del scope padre. */
    Alpine.data('descEditor', () => ({
        init() {
            this.$refs.ed.innerHTML = this.form.description || '';
            // Al cambiar de producto, form es un objeto nuevo: recargar el editor.
            // Se compara antes de escribir para no mover el cursor mientras se teclea.
            this.$watch('form.description', (v) => {
                const actual = v || '';
                if (this.$refs.ed.innerHTML !== actual) this.$refs.ed.innerHTML = actual;
            });
        },
        cmd(accion) {
            this.$refs.ed.focus();
            document.execCommand(accion, false, null);
            this.sync();
        },
        sync() {
            this.form.description = this.$refs.ed.innerHTML;
        },
        // Pegar desde Word/web arrastra estilos y fuentes ajenas: se pega en limpio.
        pegarSinFormato(e) {
            const texto = (e.clipboardData || window.clipboardData).getData('text');
            document.execCommand('insertText', false, texto);
            this.sync();
        },
    }));

    Alpine.data('productPage', () => ({
        products:   window.__productPageData.products,
        categories: window.__productPageData.categories,
        brands:     window.__productPageData.brands,
        units:      window.__productPageData.units,
        suppliers:  window.__productPageData.suppliers,
        locations:  window.__productPageData.locations,
        taxes:      window.__productPageData.taxes,
        hasCatalogs: window.__productPageData.hasCatalogs,
        baseUrl:    window.__productPageData.baseUrl,
        csrf:       window.__productPageData.csrf,
        search: '',
        filterCat: null,
        filterNoCat: false,
        filterStatus: '',
        filterStock: false,
        filterLowStock: false,
        panel: 'list',
        selected: null,
        creating: false,
        tab: 'info',
        saving: false,
        importing: false,
        form: {},
        originalForm: null,
        importLog: { show: false, created: 0, updated: 0, skipped: 0, errors: [], warnings: [], reloadOnClose: false },
        bulkIds: [],
        bulkRunning: false,

        get bulkActive() { return this.bulkIds.length > 0; },

        toggleBulk(id) {
            const i = this.bulkIds.indexOf(id);
            if (i > -1) this.bulkIds.splice(i, 1); else this.bulkIds.push(id);
        },
        toggleBulkAll() {
            const visible = this.filtered.map(p => p.id);
            const allSelected = visible.length > 0 && visible.every(id => this.bulkIds.includes(id));
            this.bulkIds = allSelected ? [] : visible;
        },
        clearBulk() { this.bulkIds = []; },

        async runBulk(action, extra = {}, customMsg = null) {
            if (!this.bulkIds.length || this.bulkRunning) return;
            // Cada acción explica QUÉ hace, no solo su nombre: el usuario tiene que
            // poder decidir sin adivinar la consecuencia.
            const acciones = {
                available:    { verbo: 'marcar como disponibles',         titulo: 'Marcar como disponibles',
                                efecto: 'Se mostrarán en tu tienda y tus clientes podrán comprarlos.' },
                unavailable:  { verbo: 'marcar como NO disponibles',      titulo: 'Ocultar de la tienda',
                                efecto: 'Dejarán de aparecer en tu tienda. No se borran: puedes volver a activarlos cuando quieras.' },
                stock_on:     { verbo: 'activar el control de stock',     titulo: 'Activar control de stock',
                                efecto: 'Se descontará stock automáticamente en cada venta. Los que ya lo tenían activo conservan su cantidad actual.' },
                stock_off:    { verbo: 'desactivar el control de stock',  titulo: 'Desactivar control de stock',
                                efecto: 'Se mostrarán siempre como disponibles y no se descontará stock al vender. Útil para servicios o productos sin inventario.' },
                tax_on:       { verbo: 'aplicar IGV',                     titulo: 'Aplicar IGV',
                                efecto: 'En la tienda se avisará que el precio ya incluye IGV. El precio NO cambia.' },
                tax_off:      { verbo: 'quitar el aviso de IGV',          titulo: 'Quitar IGV',
                                efecto: 'Se dejará de mostrar "Incluye IGV" junto al precio. El precio NO cambia.' },
                set_category: { verbo: 'cambiar de categoría',            titulo: 'Cambiar categoría',
                                efecto: 'Se moverán a la categoría elegida. Cambia cómo se agrupan en tu tienda y en el catálogo PDF.' },
                price_adjust: { verbo: 'ajustar el precio',               titulo: 'Ajustar precios',
                                efecto: 'Se recalcula el precio de venta de cada producto. Ningún precio quedará por debajo de 0.01.' },
                delete:       { verbo: 'ELIMINAR',                        titulo: 'Eliminar productos',
                                efecto: 'Se borrarán junto con sus fotos. Esta acción NO se puede deshacer.' },
            };
            const a = acciones[action] || { verbo: action, titulo: 'Confirmar acción masiva', efecto: '' };
            const linea = customMsg || `Vas a ${a.verbo} en ${this.bulkIds.length} producto(s).`;

            const ok = await window.__confirm({
                title: a.titulo,
                msg: a.efecto ? linea + '\n\n' + a.efecto : linea,
                confirmLabel: action === 'delete' ? 'Sí, eliminar' : 'Sí, aplicar',
                // El rojo se reserva para lo que no se puede deshacer; el resto
                // son cambios reversibles y no deben dar sensación de peligro.
                confirmClass: action === 'delete'
                    ? 'bg-red-600 hover:bg-red-700 text-white'
                    : 'bg-indigo-600 hover:bg-indigo-700 text-white',
            });
            if (!ok) return;

            this.bulkRunning = true;
            const res = await fetch(this.baseUrl + '/products/bulk-action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ ids: this.bulkIds, action, ...extra }),
            });
            const data = await res.json();
            this.bulkRunning = false;
            if (!res.ok) {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: (data && data.message) || 'No se pudo aplicar la acción.', type: 'danger' } }));
                return;
            }

            if (action === 'delete') {
                this.products = this.products.filter(p => !this.bulkIds.includes(p.id));
                if (this.selected && this.bulkIds.includes(this.selected.id)) { this.selected = null; this.creating = false; }
            } else {
                this.products.forEach(p => {
                    if (!this.bulkIds.includes(p.id)) return;
                    if (action === 'available')   p.is_available = true;
                    if (action === 'unavailable') p.is_available = false;
                    if (action === 'stock_on' && (p.stock === null || p.stock === undefined)) p.stock = 0;
                    if (action === 'stock_off') p.stock = null;
                    if (action === 'tax_on')  { p.has_tax = true; if (extra.tax_rate) p.tax_rate = extra.tax_rate; }
                    if (action === 'tax_off') p.has_tax = false;
                    if (action === 'set_category') {
                        p.category_id = extra.category_id ? String(extra.category_id) : '';
                        const cat = this.categories.find(c => String(c.id) === p.category_id)
                            || this.categories.flatMap(c => c.children||[]).find(s => String(s.id) === p.category_id);
                        p.category_name = cat ? cat.name : '';
                    }
                    if (action === 'price_adjust') {
                        const cur = parseFloat(p.price) || 0;
                        const next = extra.price_mode === 'pct' ? cur * (1 + extra.price_delta/100) : cur + extra.price_delta;
                        p.price = Math.max(0.01, next).toFixed(2);
                    }
                });
                if (this.selected && this.bulkIds.includes(this.selected.id)) { this.select(this.selected); }
            }
            window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: `Aplicado a ${data.count} producto(s).`, type: 'success' } }));
            this.clearBulk();
        },

        get hasChanges() {
            return this.originalForm !== null && JSON.stringify(this.form) !== this.originalForm;
        },

        get filtered() {
            return this.products.filter(p => {
                if (this.search && !p.name.toLowerCase().includes(this.search.toLowerCase())
                    && !(p.sku||'').toLowerCase().includes(this.search.toLowerCase())) return false;
                if (this.filterNoCat && p.category_id) return false;
                if (this.filterCat !== null) {
                    const cat = this.categories.find(c => String(c.id) === String(this.filterCat));
                    const childIds = cat ? (cat.children||[]).map(s => String(s.id)) : [];
                    if (String(p.category_id) !== String(this.filterCat) && !childIds.includes(String(p.category_id))) return false;
                }
                if (this.filterStatus === 'active'   && !p.is_available) return false;
                if (this.filterStatus === 'inactive' &&  p.is_available) return false;
                if (this.filterStock && (p.stock||0) > 0) return false;
                if (this.filterLowStock && !((p.stock||0) > 0 && (p.stock_min||0) > 0 && (p.stock||0) <= (p.stock_min||0))) return false;
                return true;
            });
        },

        get margin() {
            const p = parseFloat(this.form.price), c = parseFloat(this.form.cost);
            if (!p || !c || isNaN(p) || isNaN(c)) return null;
            return (((p - c) / p) * 100).toFixed(1);
        },

        // El precio que se ingresa arriba es siempre el precio final al cliente
        // (asi lo exige la ley en Peru: el precio mostrado ya incluye IGV).
        // "has_tax" solo decide si la tienda le informa al cliente que ese
        // precio incluye IGV; estos dos numeros son el desglose para mostrarlo.
        get taxBase() {
            const p = parseFloat(this.form.price);
            if (!p || !this.form.has_tax) return null;
            return (p / (1 + (parseFloat(this.form.tax_rate)||18)/100)).toFixed(2);
        },
        get taxAmount() {
            const p = parseFloat(this.form.price), base = parseFloat(this.taxBase);
            if (!p || isNaN(base)) return null;
            return (p - base).toFixed(2);
        },

        get stockStatus() {
            const s = parseInt(this.form.stock) || 0;
            const min = parseInt(this.form.stock_min) || 0;
            if (s <= 0) return { color:'red', label:'Agotado sin stock' };
            if (min > 0 && s <= min) return { color:'amber', label:'Stock bajo: quedan ' + s + ' unidades' };
            return { color:'green', label:'En stock: ' + s + ' unidades disponibles' };
        },

        clearFilters() {
            this.search = ''; this.filterCat = null; this.filterNoCat = false; this.filterStatus = ''; this.filterStock = false; this.filterLowStock = false;
        },

        select(p) {
            this.selected = p; this.creating = false; this.tab = 'info';
            this.form = { ...p, category_id: p.category_id ? String(p.category_id) : '' };
            this.originalForm = JSON.stringify(this.form);
        },

        openNew() {
            this.selected = null; this.creating = true; this.tab = 'info';
            this.form = {
                name:'', sku:'', barcode:'', description:'', notes:'',
                price:'', price_suggested:'', price_min:'', price_max:'', compare_price:'', wholesale_price:'', wholesale_min_qty:'', wholesale_unit:'', cost:'', unit:'', sizes:'',
                stock:0, stock_min:0, stock_max:0,
                location:'', supplier:'',
                has_tax:false, tax_rate:18,
                is_available:true, category_id:'', brand_catalog_id:''
            };
            this.originalForm = JSON.stringify(this.form);
        },

        async save() {
            this.saving = true;
            const url    = this.creating ? this.baseUrl + '/products' : this.baseUrl + '/products/' + this.selected.id;
            const method = this.creating ? 'POST' : 'PUT';
            const res    = await fetch(url, {
                method,
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept':'application/json' },
                body: JSON.stringify(this.form)
            });
            const data = await res.json();
            const wasCreating = this.creating;
            if (this.creating) {
                this.products.push(data.product);
                this.select(data.product);
            } else {
                const idx = this.products.findIndex(p => p.id === data.product.id);
                if (idx > -1) this.products.splice(idx, 1, { ...data.product });
                this.selected = { ...data.product };
                this.form = { ...data.product };
            }
            this.originalForm = JSON.stringify(this.form);
            this.creating = false;
            this.saving = false;
            document.getElementById('product-count-label').textContent = this.filtered.length + ' producto' + (this.filtered.length !== 1 ? 's' : '');
            window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: wasCreating ? 'Producto creado' : 'Cambios guardados', type: 'success' } }));
        },

        async del() {
            const ok = await window.__confirm({
                title: 'Eliminar producto',
                msg: 'Eliminar "' + this.selected.name + '"? Esta accion no se puede deshacer.',
                confirmLabel: 'Si, eliminar',
            });
            if (!ok) return;
            await fetch(this.baseUrl + '/products/' + this.selected.id, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept':'application/json' }
            });
            window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Producto eliminado', type: 'warning' } }));
            this.products = this.products.filter(p => p.id !== this.selected.id);
            this.selected = null; this.creating = false;
        },

        duplicating: false,
        async duplicate() {
            if (this.duplicating || !this.selected) return;
            this.duplicating = true;
            const res = await fetch(this.baseUrl + '/products/' + this.selected.id + '/duplicate', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
            });
            this.duplicating = false;
            if (!res.ok) {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'No se pudo duplicar el producto.', type: 'danger' } }));
                return;
            }
            const data = await res.json();
            this.products.push(data.product);
            this.select(data.product);
            this.panel = 'detail';
            document.getElementById('product-count-label').textContent = this.filtered.length + ' producto' + (this.filtered.length !== 1 ? 's' : '');
            window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Producto duplicado', type: 'success' } }));
        },

        async purgeAll() {
            const count = this.products.length;
            const ok = await window.__confirm({
                title: 'Vaciar catálogo completo',
                msg: 'Vas a eliminar los ' + count + ' producto(s) de este negocio, sus imágenes y vínculos (combos, reseñas, perfiles de catálogo). Esta acción NO se puede deshacer.',
                confirmLabel: 'Entiendo, continuar',
            });
            if (!ok) return;
            const typed = await bxConfirmar({
                titulo: 'Escribe el identificador para confirmar',
                descripcion: 'Para vaciar el catálogo, escribe exactamente el identificador del negocio: {{ $project->slug }}',
                boton: 'Vaciar catálogo',
                entrada: { etiqueta: 'Identificador del negocio', marcador: '{{ $project->slug }}', debeCoincidir: '{{ $project->slug }}' },
            });
            if (typed !== '{{ $project->slug }}') {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Cancelado: el texto no coincidió.', type: 'warning' } }));
                return;
            }
            const res = await fetch(this.baseUrl + '/products/purge-all', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ confirm_slug: typed }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: data.message || 'No se pudo vaciar el catálogo.', type: 'error' } }));
                return;
            }
            this.products = [];
            this.selected = null; this.creating = false;
            document.getElementById('product-count-label').textContent = '0 productos';
            window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Catálogo vaciado: ' + data.deleted + ' producto(s) eliminado(s).', type: 'warning' } }));
        },

        dragId: null,

        dragStart(id) { this.dragId = id; },
        dragOver(id) {
            if (this.dragId === null || this.dragId === id) return;
            const from = this.products.findIndex(p => p.id === this.dragId);
            const to   = this.products.findIndex(p => p.id === id);
            if (from === -1 || to === -1) return;
            const arr = [...this.products];
            arr.splice(to, 0, arr.splice(from, 1)[0]);
            this.products = arr;
        },
        async dragEnd() {
            if (this.dragId === null) return;
            this.dragId = null;
            await fetch(this.baseUrl + '/products/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ ids: this.products.map(p => p.id) }),
            });
        },

        async importCSV(file) {
            if (!file) return;
            this.importing = true;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', this.csrf);
            try {
                const res  = await fetch(this.baseUrl + '/products/import', { method:'POST', headers:{'Accept':'application/json'}, body: fd });
                const data = await res.json();
                this.importing = false;
                this.importLog = { show: true, created: data.created||0, updated: data.updated||0, skipped: data.skipped||0, errors: data.errors||[], warnings: data.warnings||[], reloadOnClose: true };
            } catch(e) {
                this.importing = false;
                this.importLog = { show: true, created: 0, updated: 0, skipped: 0, errors: ['Error de red al importar. Verifica que el archivo sea CSV o XLS valido.'] };
            }
        },

        init() {
            // El botón "Vaciar catálogo" vive en el menú superior (otro scope
            // Alpine): llega por este evento global.
            window.addEventListener('purge-catalog', () => this.purgeAll());
            this.$nextTick(() => {
                const el = document.getElementById('product-count-label');
                if (el) el.textContent = this.filtered.length + ' producto' + (this.filtered.length !== 1 ? 's' : '');
            });
            this.$watch('filtered', v => {
                const el = document.getElementById('product-count-label');
                if (el) el.textContent = v.length + ' producto' + (v.length !== 1 ? 's' : '');
            });
            window.addEventListener('open-new-product', () => { this.openNew(); this.panel = 'detail'; });
            window.addEventListener('do-import-csv', (e) => { this.importCSV(e.detail.file); });

            // Enlaces profundos. El editor vive solo dentro de esta página, así que
            // sin esto no había manera de enlazar a un producto concreto desde
            // fuera (Kardex, Constructor). Antes se usaban products.edit y
            // products.create, que apuntaban a métodos inexistentes y daban 500.
            const params = new URLSearchParams(window.location.search);
            const editId = parseInt(params.get('edit'), 10);
            if (editId) {
                const p = this.products.find(x => x.id === editId);
                if (p) { this.select(p); this.panel = 'detail'; }
            } else if (params.get('new')) {
                this.openNew(); this.panel = 'detail';
            }
        },
    }));
});
</script>
<div class="flex flex-1 overflow-hidden">

{{-- ─── PANEL FILTROS (56px) ─────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">
    {{-- Por disponibilidad --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? 'active' : (filterStatus==='active' ? 'inactive' : '')"
                :class="filterStatus!=='' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterStatus===''"><span>Todos los estados</span></template>
            <template x-if="filterStatus==='active'"><span>Solo activos</span></template>
            <template x-if="filterStatus==='inactive'"><span>Solo inactivos</span></template>
        </span>
    </div>

    {{-- Sin stock --}}
    <div class="relative group">
        <button @click="filterStock = !filterStock; if(filterStock) filterLowStock=false"
                :class="filterStock ? 'bg-red-100 text-red-600' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10"
              x-text="filterStock ? 'Mostrando agotados' : 'Filtrar agotados'"></span>
    </div>

    {{-- Stock bajo --}}
    <div class="relative group">
        <button @click="filterLowStock = !filterLowStock; if(filterLowStock) filterStock=false"
                :class="filterLowStock ? 'bg-amber-100 text-amber-600' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10"
              x-text="filterLowStock ? 'Mostrando stock bajo' : 'Filtrar stock bajo'"></span>
    </div>

    {{-- Reset --}}
    <div class="relative group">
        <button @click="clearFilters()"
                :class="(search||filterCat!==null||filterStatus!==''||filterStock||filterLowStock) ? 'bg-amber-100 text-amber-600' : 'text-gray-300'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">Limpiar filtros</span>
    </div>

</div>

{{-- ─── LISTA CENTRAL ─────────────────────────────────────────────── --}}
<div class="w-full border-r border-gray-200 flex-col bg-white flex-shrink-0"
     :style="window.innerWidth >= 1600 ? 'width:420px;flex-shrink:0' : (window.innerWidth >= 768 ? 'width:360px;flex-shrink:0' : '')"
     :class="panel==='list' ? 'flex' : 'hidden md:flex'">
    {{-- Búsqueda --}}
    <div class="px-3 pt-3 pb-2 border-b border-gray-200 flex-shrink-0 space-y-2">
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 h-10">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Buscar producto o SKU..."
                   class="bg-transparent text-sm outline-none flex-1 min-w-0 text-gray-700 placeholder-gray-400">
            <button x-show="search" @click="search=''" class="text-gray-400 hover:text-gray-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        {{-- Dropdown categorías --}}
        <div class="relative" x-data="{ openCat: false }">
            <button @click="openCat=!openCat" @click.outside="openCat=false"
                    :class="(filterCat!==null||filterNoCat) ? 'bg-indigo-50 text-indigo-700 border-indigo-300' : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'"
                    class="w-full flex items-center gap-2 px-3 h-10 text-sm font-medium border rounded-lg transition">
                <svg class="w-3.5 h-3.5 flex-shrink-0" :class="(filterCat!==null||filterNoCat)?'text-indigo-500':'text-orange-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="flex-1 text-left truncate" x-text="filterNoCat ? 'Sin categoría' : (filterCat!==null ? (categories.find(c=>c.id===filterCat)?.name || categories.flatMap(c=>c.children||[]).find(s=>s.id===filterCat)?.name || 'Categoría') : 'Todas las categorías')"></span>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform" :class="openCat?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openCat" x-cloak
                 class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-30 max-h-56 overflow-y-auto">
                <button @click="filterCat=null; filterNoCat=false; openCat=false"
                        :class="filterCat===null&&!filterNoCat ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        class="w-full text-left px-3 py-2 text-sm">Todas las categorías</button>
                <button @click="filterNoCat=true; filterCat=null; openCat=false"
                        :class="filterNoCat ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        class="w-full text-left px-3 py-2 text-sm border-t border-gray-100">Sin categoría</button>
                <template x-for="cat in categories" :key="cat.id">
                    <div>
                        <button @click="filterCat=cat.id; filterNoCat=false; openCat=false"
                                :class="filterCat===cat.id ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full text-left px-3 py-2 text-sm border-t border-gray-100 truncate flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span x-text="cat.name"></span>
                        </button>
                        <template x-for="sub in (cat.children||[])" :key="sub.id">
                            <button @click="filterCat=sub.id; filterNoCat=false; openCat=false"
                                    :class="filterCat===sub.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:bg-gray-50'"
                                    class="w-full text-left pl-7 pr-3 py-1.5 text-xs border-t border-gray-50 truncate flex items-center gap-1.5">
                                <svg class="w-2.5 h-2.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span x-text="sub.name"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Barra de acciones masivas --}}
    <div x-show="bulkActive" x-cloak class="px-3 py-2 bg-indigo-50 border-b border-indigo-100 flex-shrink-0 flex items-center justify-between gap-2">
        <span class="text-xs font-semibold text-indigo-700 flex-shrink-0" x-text="bulkIds.length + ' seleccionado' + (bulkIds.length!==1?'s':'')"></span>
        <div class="flex items-center gap-1.5">
            <div class="relative" x-data="{ openBulk: false, view: 'menu', catPick: '', priceMode: 'pct', priceDelta: '' }"
                 @click.outside="openBulk=false">
                <button type="button" @click="openBulk=!openBulk; view='menu'" :disabled="bulkRunning"
                        class="pe-btn pe-btn-sm pe-btn-secondary">
                    <svg x-show="bulkRunning" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="bulkRunning ? 'Aplicando...' : 'Acciones'"></span>
                    <svg x-show="!bulkRunning" class="w-3 h-3 transition-transform" :class="openBulk ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Menú principal --}}
                <div x-show="openBulk && view==='menu'" x-cloak
                     class="absolute right-0 mt-1.5 w-60 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-50">
                    <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Disponibilidad</p>
                    <button type="button" @click="runBulk('available'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Marcar disponible</button>
                    <button type="button" @click="runBulk('unavailable'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Marcar no disponible</button>
                    <div class="border-t border-gray-100 my-1.5"></div>
                    <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Control de stock</p>
                    <button type="button" @click="runBulk('stock_on'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Activar control de stock</button>
                    <button type="button" @click="runBulk('stock_off'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Desactivar control de stock</button>
                    <div class="border-t border-gray-100 my-1.5"></div>
                    <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">IGV</p>
                    <button type="button" @click="runBulk('tax_on', { tax_rate: 18 }); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Aplicar IGV (18%)</button>
                    <button type="button" @click="runBulk('tax_off'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Quitar IGV</button>
                    <div class="border-t border-gray-100 my-1.5"></div>
                    <p class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Catálogo</p>
                    <button type="button" @click="view='category'; catPick=''" class="w-full flex items-center justify-between gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">
                        <span>Cambiar categoría</span>
                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button type="button" @click="view='price'; priceDelta=''" class="w-full flex items-center justify-between gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">
                        <span>Ajustar precio</span>
                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <a :href="baseUrl + '/products/export?' + bulkIds.map(id => 'ids[]=' + id).join('&')" @click="openBulk=false"
                       class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 text-left">Exportar seleccionados (Excel)</a>
                    <div class="border-t border-gray-100 my-1.5"></div>
                    <button type="button" @click="runBulk('delete'); openBulk=false" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 text-left">Eliminar seleccionados</button>
                </div>

                {{-- Sub-panel: cambiar categoría --}}
                <div x-show="openBulk && view==='category'" x-cloak
                     class="absolute right-0 mt-1.5 w-64 bg-white rounded-xl shadow-lg border border-gray-200 p-3 z-50">
                    <button type="button" @click="view='menu'" class="flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 mb-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver
                    </button>
                    <label class="pe-label text-xs">Nueva categoría</label>
                    <select x-model="catPick" class="pe-input text-sm">
                        <option value="">Sin categoría</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <optgroup :label="cat.name">
                                <option :value="cat.id" x-text="cat.name"></option>
                                <template x-for="sub in (cat.children||[])" :key="sub.id">
                                    <option :value="sub.id" x-text="'— ' + sub.name"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                    <button type="button"
                            @click="runBulk('set_category', { category_id: catPick || null }, `Vas a cambiar la categoría de ${bulkIds.length} producto(s).`); openBulk=false"
                            class="pe-btn pe-btn-sm pe-btn-primary w-full mt-3">Aplicar</button>
                </div>

                {{-- Sub-panel: ajustar precio --}}
                <div x-show="openBulk && view==='price'" x-cloak
                     class="absolute right-0 mt-1.5 w-64 bg-white rounded-xl shadow-lg border border-gray-200 p-3 z-50">
                    <button type="button" @click="view='menu'" class="flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 mb-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver
                    </button>
                    <label class="pe-label text-xs">Tipo de ajuste</label>
                    <div class="grid grid-cols-2 gap-1.5 mb-2">
                        <button type="button" @click="priceMode='pct'" :class="priceMode==='pct' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-500'" class="h-8 rounded-lg border text-xs font-semibold">Porcentaje (%)</button>
                        <button type="button" @click="priceMode='fixed'" :class="priceMode==='fixed' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-500'" class="h-8 rounded-lg border text-xs font-semibold">Monto fijo</button>
                    </div>
                    <label class="pe-label text-xs">Monto (usa negativo para bajar)</label>
                    <input type="number" x-model.number="priceDelta" step="0.01" class="pe-input text-sm" :placeholder="priceMode==='pct' ? 'Ej: 10 o -10' : 'Ej: 5 o -5'">
                    <p class="pe-hint">El precio nunca queda por debajo de {{ $currency }} 0.01</p>
                    <button type="button" :disabled="priceDelta === ''"
                            @click="runBulk('price_adjust', { price_mode: priceMode, price_delta: priceDelta }, `Vas a ajustar el precio de ${bulkIds.length} producto(s) en ${priceDelta}${priceMode==='pct'?'%':' {{ $currency }}'}.`); openBulk=false"
                            class="pe-btn pe-btn-sm pe-btn-primary w-full mt-3">Aplicar</button>
                </div>
            </div>
            <button type="button" @click="clearBulk()" class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0" aria-label="Cancelar selección">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto py-1.5 px-1.5 space-y-0.5">
        {{-- Sin fila "+ Nuevo producto" aquí: el botón de la barra superior está
             justo encima y nunca se desplaza, así que duplicarlo solo robaba un
             renglón permanente a la lista. --}}

        {{-- Seleccionar todos (acciones masivas) --}}
        <button x-show="filtered.length > 0" @click="toggleBulkAll()"
                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-400 hover:text-gray-600">
            <span class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0"
                  :class="filtered.length > 0 && filtered.every(p => bulkIds.includes(p.id)) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'">
                <svg x-show="filtered.length > 0 && filtered.every(p => bulkIds.includes(p.id))" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span x-text="bulkActive ? bulkIds.length + ' seleccionado' + (bulkIds.length!==1?'s':'') : 'Seleccionar todos'"></span>
        </button>

        {{-- Estado vacío --}}
        <div x-show="filtered.length === 0 && !creating" class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <template x-if="search || filterCat !== null || filterStatus !== ''">
                <div>
                    <p class="text-sm font-medium text-gray-700">Sin resultados</p>
                    <p class="text-xs text-gray-400 mt-1">Prueba con otros filtros</p>
                    <button @click="clearFilters()" class="mt-3 text-xs text-indigo-600 font-medium hover:underline">Limpiar filtros</button>
                </div>
            </template>
            <template x-if="!search && filterCat === null && filterStatus === ''">
                <div>
                    <p class="text-sm font-medium text-gray-700">Sin productos aún</p>
                    <p class="text-xs text-gray-400 mt-1 mb-3">Crea tu primer producto o importa desde Excel</p>
                    {{-- Solo en móvil: en escritorio los atajos del panel derecho ya cubren esto. --}}
                    <button @click="openNew(); panel='detail'"
                            class="md:hidden text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3 py-1.5 rounded-lg transition">
                        + Crear producto
                    </button>
                </div>
            </template>
        </div>

        <template x-for="p in filtered" :key="p.id">
            <button @click="select(p); panel='detail'"
                    draggable="true"
                    @dragstart="dragStart(p.id)"
                    @dragover.prevent="dragOver(p.id)"
                    @dragend="dragEnd()"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition text-left cursor-grab active:cursor-grabbing"
                    :class="[selected?.id===p.id ? 'bg-indigo-50 ring-1 ring-indigo-200' : '', dragId===p.id ? 'opacity-40' : '']">
                <span role="checkbox" :aria-checked="bulkIds.includes(p.id)" @click.stop="toggleBulk(p.id)"
                      class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0"
                      :class="bulkIds.includes(p.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 hover:border-indigo-400'">
                    <svg x-show="bulkIds.includes(p.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <template x-if="p.main_image">
                        <img :src="p.main_image" class="w-9 h-9 object-cover rounded-lg" loading="lazy">
                    </template>
                    <template x-if="!p.main_image">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></p>
                        <span :class="p.is_available ? 'text-green-600' : 'text-gray-400'"
                              class="text-[11px] font-semibold flex-shrink-0"
                              x-text="p.is_available ? 'Activo' : 'Inactivo'"></span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-gray-500" x-text="'{{ $currency }} ' + parseFloat(p.price).toFixed(2)"></span>
                        <span x-show="p.sku" class="text-xs text-gray-300">·</span>
                        <span x-show="p.sku" class="text-xs text-gray-400 font-mono truncate" x-text="p.sku"></span>
                        <span x-show="(p.stock||0) <= 0"
                              class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-red-100 text-red-600 flex-shrink-0">Agotado</span>
                        <span x-show="(p.stock||0) > 0 && (p.stock_min||0) > 0 && (p.stock||0) <= (p.stock_min||0)"
                              class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-700 flex-shrink-0">Stock bajo</span>
                    </div>
                </div>
            </button>
        </template>

    </div>
</div>

{{-- PANEL DETALLE --}}
<div class="flex-col overflow-hidden bg-white min-w-0"
     :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

    {{-- Sin selección: en vez de un vacío gris enorme, atajos a lo que más se
         hace desde aquí. --}}
    <template x-if="!selected && !creating">
        <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <p class="text-lg font-bold text-gray-700" x-text="products.length ? 'Selecciona un producto' : 'Tu catálogo está vacío'"></p>
            <p class="text-sm text-gray-400 mt-1" x-text="products.length ? 'Elige uno de la lista para ver y editar sus datos.' : 'Empieza cargando tus productos:'"></p>

            {{-- Los atajos solo cuando NO hay catálogo: con productos cargados
                 duplicarían los botones de la barra superior. --}}
            <div x-show="products.length === 0" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-7 w-full max-w-lg">
                <button type="button" @click="openNew(); panel='detail'"
                        class="group rounded-xl border border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40 transition p-4 text-center">
                    <svg class="w-5 h-5 mx-auto text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="block text-xs font-bold text-gray-700 mt-2">Nuevo producto</span>
                    <span class="block text-[11px] text-gray-400 mt-0.5">Crear uno a mano</span>
                </button>
                <button type="button" onclick="document.getElementById('import-file').click()"
                        class="group rounded-xl border border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40 transition p-4 text-center">
                    <svg class="w-5 h-5 mx-auto text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span class="block text-xs font-bold text-gray-700 mt-2">Importar Excel</span>
                    <span class="block text-[11px] text-gray-400 mt-0.5">Cargar muchos de golpe</span>
                </button>
                <button type="button" @click="window.dispatchEvent(new CustomEvent('open-catalog-pdf'))"
                        class="group rounded-xl border border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40 transition p-4 text-center">
                    <svg class="w-5 h-5 mx-auto text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="block text-xs font-bold text-gray-700 mt-2">Catálogo PDF</span>
                    <span class="block text-[11px] text-gray-400 mt-0.5">Enviar a tus clientes</span>
                </button>
            </div>
        </div>
    </template>

    <template x-if="selected || creating">
        <div class="flex flex-col h-full">

            {{-- Header --}}
            <div class="px-6 py-3.5 border-b border-gray-200 flex items-center gap-3 flex-shrink-0 bg-white">
                <button @click="panel='list'" type="button"
                        class="md:hidden flex-shrink-0 text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="w-11 h-11 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <template x-if="form.main_image"><img :src="form.main_image" class="w-11 h-11 object-cover"></template>
                    <template x-if="!form.main_image">
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </template>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-gray-900 text-[15px] truncate"
                        x-text="creating ? 'Nuevo producto' : (form.name || 'Sin nombre')"></h2>
                    <p class="text-xs text-gray-400 mt-0.5 truncate"
                       x-text="creating ? 'Completa la información del producto'
                           : ('ID #'+selected.id+(form.sku ? ' · SKU: '+form.sku : '')+(selected.category_name ? ' · '+selected.category_name : ''))"></p>
                </div>

                <div class="flex items-center gap-2.5 flex-shrink-0">
                    <span class="text-xs font-medium text-gray-500 hidden sm:inline">Disponible</span>
                    <button @click="form.is_available = !form.is_available" type="button"
                            :class="form.is_available ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200 flex-shrink-0">
                        <span :class="form.is_available ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 bg-white flex-shrink-0 overflow-x-auto gap-1">
                <button @click="tab='info'"
                        :class="tab==='info' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                        class="px-3.5 py-3 text-sm whitespace-nowrap transition">
                    Información
                </button>
                <button @click="tab='precios'"
                        :class="tab==='precios' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                        class="px-3.5 py-3 text-sm whitespace-nowrap transition">
                    Precios
                </button>
                <button @click="tab='inventario'"
                        :class="tab==='inventario' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                        class="px-3.5 py-3 text-sm whitespace-nowrap transition">
                    @php
                        $invTabLabel = match($project->category ?? 'default') {
                            'restaurante','cafeteria' => 'Disponibilidad',
                            'peluqueria','salon_belleza' => 'Disponibilidad',
                            'clinica','veterinaria' => 'Disponibilidad',
                            'gimnasio' => 'Cupos',
                            'taller' => 'Stock / Repuestos',
                            default => 'Inventario',
                        };
                    @endphp
                    {{ $invTabLabel }}
                </button>
                <button @click="tab='imagenes'" x-show="!creating"
                        :class="tab==='imagenes' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                        class="px-3.5 py-3 text-sm whitespace-nowrap transition">
                    Imágenes
                </button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto">

                {{-- TAB: INFORMACION --}}
                @php
                    $infoLabels = match($project->category ?? 'default') {
                        'restaurante','cafeteria' => [
                            'nombre'  => 'Nombre del plato *',
                            'nombre_ph' => 'Ej: Lomo saltado, Pollo a la brasa, Ceviche',
                            'sku'     => 'Código de plato',
                            'sku_ph'  => 'PLATO-001',
                            'barcode' => false,     // ocultar código de barras
                        ],
                        'peluqueria','salon_belleza' => [
                            'nombre'  => 'Nombre del servicio *',
                            'nombre_ph' => 'Ej: Corte de cabello, Tinte completo, Keratina',
                            'sku'     => 'Código interno',
                            'sku_ph'  => 'SRV-001',
                            'barcode' => false,
                        ],
                        'clinica' => [
                            'nombre'  => 'Nombre del tratamiento *',
                            'nombre_ph' => 'Ej: Consulta general, Limpieza dental, Radiografía',
                            'sku'     => 'Código de servicio',
                            'sku_ph'  => 'CONS-001',
                            'barcode' => false,
                        ],
                        'veterinaria' => [
                            'nombre'  => 'Nombre del producto / servicio *',
                            'nombre_ph' => 'Ej: Vacuna antirrábica, Desparasitante, Consulta',
                            'sku'     => 'Código interno',
                            'sku_ph'  => 'VET-001',
                            'barcode' => true,
                        ],
                        'gimnasio' => [
                            'nombre'  => 'Nombre del plan / clase *',
                            'nombre_ph' => 'Ej: Membresía mensual, Clase de spinning, Yoga',
                            'sku'     => 'Código de plan',
                            'sku_ph'  => 'GYM-001',
                            'barcode' => false,
                        ],
                        'educacion' => [
                            'nombre'  => 'Nombre del curso / material *',
                            'nombre_ph' => 'Ej: Curso de inglés básico, Manual de matemáticas',
                            'sku'     => 'Código de curso',
                            'sku_ph'  => 'CRS-001',
                            'barcode' => false,
                        ],
                        default => [
                            'nombre'  => 'Nombre del producto *',
                            'nombre_ph' => 'Ej: Laptop Dell XPS 15, Camisa Oxford, Café 250g',
                            'sku'     => 'SKU / Código interno',
                            'sku_ph'  => 'LPT-001',
                            'barcode' => true,
                        ],
                    };
                @endphp
                <div x-show="tab==='info'" x-cloak class="p-6 max-w-3xl space-y-4">

                    {{-- INFORMACIÓN BÁSICA --}}
                    <div class="pe-card p-5 space-y-4">
                        <p class="pe-section-title">Información básica</p>
                        <div>
                            <label class="pe-label">{{ $infoLabels['nombre'] }}</label>
                            <input type="text" x-model="form.name" class="pe-input" placeholder="{{ $infoLabels['nombre_ph'] }}">
                        </div>
                        <div class="{{ $infoLabels['barcode'] ? 'grid grid-cols-2 gap-4' : '' }}">
                            <div>
                                <label class="pe-label">{{ $infoLabels['sku'] }}</label>
                                <input type="text" x-model="form.sku" class="pe-input font-mono text-sm" placeholder="{{ $infoLabels['sku_ph'] }}">
                                <p class="pe-hint">Identificador único interno</p>
                            </div>
                            @if($infoLabels['barcode'])
                            <div>
                                <label class="pe-label">Código de barras</label>
                                <input type="text" x-model="form.barcode" class="pe-input font-mono text-sm" placeholder="7501234567890">
                                <p class="pe-hint">EAN, UPC, QR, etc.</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $infoCat = $project->category ?? 'default';

                        // Que el campo aparezca lo decide el negocio en Configuración
                        // ($usaVariantes): el rubro no alcanza, porque casi todas las
                        // tiendas son "retail" y muy pocas venden ropa. El rubro solo
                        // elige CÓMO se llama el campo cuando sí está activo.
                        $variantes = ! $usaVariantes ? null : match(true) {
                            in_array($infoCat, ['restaurante','cafeteria'])
                                => ['lbl' => 'Presentaciones', 'ph' => 'Ej: Personal, Familiar',
                                    'hint' => 'Escribe una presentación y presiona Enter o coma. El cliente elegirá una al pedir.'],
                            in_array($infoCat, ['farmacia','veterinaria'])
                                => ['lbl' => 'Presentaciones', 'ph' => 'Ej: 100 ml, 500 mg',
                                    'hint' => 'Escribe una presentación y presiona Enter o coma. El cliente elegirá una al comprar.'],
                            default
                                => ['lbl' => 'Tallas / variantes', 'ph' => 'Ej: S, M, L, 38, 40',
                                    'hint' => 'Escribe una talla y presiona Enter o coma. El cliente elegirá una al comprar (ideal ropa y calzado).'],
                        };
                    @endphp

                    @php
                        $infoExtra = match(true) {
                            in_array($infoCat, ['restaurante','cafeteria']) => [
                                'cat_lbl'    => 'Categoría del menú',
                                'marca'      => false,
                                'desc_lbl'   => 'Descripción del plato',
                                'desc_ph'    => 'Ingredientes, preparación, alérgenos...',
                                'notas_lbl'  => 'Notas de cocina (internas)',
                                'notas_ph'   => 'Ej: sin TACC, picante medio, no congelar...',
                            ],
                            in_array($infoCat, ['peluqueria','salon_belleza']) => [
                                'cat_lbl'    => 'Tipo de servicio',
                                'marca'      => false,
                                'desc_lbl'   => 'Descripción del servicio',
                                'desc_ph'    => 'Qué incluye, resultado esperado, tiempo...',
                                'notas_lbl'  => 'Notas del estilista (internas)',
                                'notas_ph'   => 'Ej: requiere cabello limpio, usar guantes...',
                            ],
                            $infoCat === 'clinica' => [
                                'cat_lbl'    => 'Especialidad',
                                'marca'      => false,
                                'desc_lbl'   => 'Descripción del tratamiento',
                                'desc_ph'    => 'En qué consiste, beneficios, duración...',
                                'notas_lbl'  => 'Indicaciones médicas (internas)',
                                'notas_ph'   => 'Ej: requiere ayuno, traer exámenes previos...',
                            ],
                            $infoCat === 'veterinaria' => [
                                'cat_lbl'    => 'Categoría',
                                'marca'      => true,
                                'desc_lbl'   => 'Descripción',
                                'desc_ph'    => 'Para qué sirve, especie indicada, dosis...',
                                'notas_lbl'  => 'Notas internas',
                                'notas_ph'   => 'Ej: refrigerar, solo bajo prescripción...',
                            ],
                            $infoCat === 'gimnasio' => [
                                'cat_lbl'    => 'Tipo de plan',
                                'marca'      => false,
                                'desc_lbl'   => 'Descripción del plan / clase',
                                'desc_ph'    => 'Qué incluye, nivel requerido, beneficios...',
                                'notas_lbl'  => 'Notas internas',
                                'notas_ph'   => 'Ej: requiere evaluación previa, solo adultos...',
                            ],
                            $infoCat === 'educacion' => [
                                'cat_lbl'    => 'Área / Programa',
                                'marca'      => false,
                                'desc_lbl'   => 'Descripción del curso',
                                'desc_ph'    => 'Contenido, nivel, certificación, duración...',
                                'notas_lbl'  => 'Notas para el equipo docente',
                                'notas_ph'   => 'Ej: requiere laptop, modalidad híbrida...',
                            ],
                            default => [
                                'cat_lbl'    => 'Categoría',
                                'marca'      => true,
                                'desc_lbl'   => 'Descripción pública',
                                'desc_ph'    => 'Descripción visible en el catálogo online para tus clientes...',
                                'notas_lbl'  => 'Notas internas',
                                'notas_ph'   => 'Notas privadas: proveedor preferido, instrucciones...',
                            ],
                        };
                    @endphp
                    {{-- CLASIFICACIÓN --}}
                    <div class="pe-card p-5 space-y-4">
                        <p class="pe-section-title">Clasificación</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="pe-label flex items-center justify-between">
                                    {{ $infoExtra['cat_lbl'] }}
                                    <a href="{{ route('categories.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                                </label>
                                <select class="pe-input" x-model="form.category_id">
                                    <option value="">Sin categoría</option>
                                    <template x-for="c in categories" :key="c.id">
                                        <template x-if="c.children && c.children.length > 0">
                                            <optgroup :label="c.name">
                                                <option :value="String(c.id)" x-text="c.name"></option>
                                                <template x-for="s in c.children" :key="s.id">
                                                    <option :value="String(s.id)" x-text="'  └ ' + s.name"></option>
                                                </template>
                                            </optgroup>
                                        </template>
                                        <template x-if="!c.children || c.children.length === 0">
                                            <option :value="String(c.id)" x-text="c.name"></option>
                                        </template>
                                    </template>
                                </select>
                                <p class="pe-hint-ok">&#10003; Viene del módulo de categorías</p>
                            </div>
                            @if($infoExtra['marca'])
                            <div>
                                <label class="pe-label flex items-center justify-between">
                                    Marca
                                    <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                                </label>
                                <template x-if="brands.length > 0">
                                    <div>
                                        <select x-model.number="form.brand_catalog_id" class="pe-input">
                                            <option value="">Sin marca</option>
                                            <template x-for="b in brands" :key="b.id">
                                                <option :value="b.id" x-text="b.label"></option>
                                            </template>
                                        </select>
                                        <p class="pe-hint-ok">&#10003; Viene del catálogo de configuración</p>
                                    </div>
                                </template>
                                <template x-if="brands.length === 0">
                                    <a href="{{ route('catalogs.index') }}"
                                       class="flex items-center gap-1.5 px-3 h-[42px] bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition text-[11px] text-amber-700 font-medium">
                                        + Crear catálogo de marcas
                                    </a>
                                </template>
                            </div>
                            @endif
                        </div>

                        {{-- Tallas / presentaciones — chips. En rubros donde no aplica el
                             campo no se muestra, salvo que el producto YA tenga valores
                             cargados: si no, quedarían huérfanos e imposibles de borrar. --}}
                        <div x-data="{
                                get chips() { return (form.sizes||'').split(',').map(s=>s.trim()).filter(Boolean); },
                                newChip: '',
                                addChip() {
                                    const v = this.newChip.trim();
                                    if (!v) return;
                                    const arr = this.chips;
                                    if (!arr.includes(v)) arr.push(v);
                                    form.sizes = arr.join(', ');
                                    this.newChip = '';
                                },
                                removeChip(v) { form.sizes = this.chips.filter(c => c !== v).join(', '); },
                             }"
                             @if($variantes === null) x-show="chips.length > 0" x-cloak @endif>
                            <label class="pe-label">{{ $variantes['lbl'] ?? 'Variantes' }} <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <template x-for="c in chips" :key="c">
                                    <span class="pe-chip">
                                        <span x-text="c"></span>
                                        <button type="button" @click="removeChip(c)">&times;</button>
                                    </span>
                                </template>
                                <input type="text" x-model="newChip" placeholder="{{ $variantes['ph'] ?? '+ Agregar' }}"
                                       @keydown.enter.prevent="addChip()"
                                       @keydown.,.prevent="addChip()"
                                       @blur="addChip()"
                                       class="pe-chip-input">
                            </div>
                            <p class="pe-hint">{{ $variantes['hint'] ?? 'Opciones que el cliente elige al comprar. Este rubro no suele usarlas.' }}</p>
                        </div>
                    </div>

                    {{-- DESCRIPCIÓN PÚBLICA --}}
                    <div class="pe-card p-5">
                        <p class="pe-section-title mb-3">{{ $infoExtra['desc_lbl'] }}</p>

                        {{-- Editor con formato básico. Guarda HTML limpio (solo negrita,
                             cursiva, subrayado y listas) que la tienda pinta tal cual. --}}
                        <div class="pe-editor" x-data="descEditor()">
                            <div class="pe-editor-bar">
                                <button type="button" class="pe-tool" @click="cmd('bold')" title="Negrita (Ctrl+B)"><b>B</b></button>
                                <button type="button" class="pe-tool" @click="cmd('italic')" title="Cursiva (Ctrl+I)"><i>I</i></button>
                                <button type="button" class="pe-tool" @click="cmd('underline')" title="Subrayado (Ctrl+U)"><u>U</u></button>
                                <span class="pe-tool-sep"></span>
                                <button type="button" class="pe-tool" @click="cmd('insertUnorderedList')" title="Lista con viñetas">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="3.5" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="3.5" cy="18" r="1.4" fill="currentColor" stroke="none"/></svg>
                                </button>
                                <button type="button" class="pe-tool" @click="cmd('insertOrderedList')" title="Lista numerada">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13M3 6h1.5M3 12h2M3 18h2"/></svg>
                                </button>
                                <span class="pe-tool-sep"></span>
                                <button type="button" class="pe-tool" @click="cmd('removeFormat')" title="Quitar formato">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 5h12M9 5l-2 14M15 5l-1 7M14 17l5 4M19 17l-5 4"/></svg>
                                </button>
                            </div>
                            <div class="pe-editor-body" contenteditable="true" x-ref="ed"
                                 data-placeholder="{{ $infoExtra['desc_ph'] }}"
                                 @input="sync()" @blur="sync()"
                                 @paste.prevent="pegarSinFormato($event)"></div>
                        </div>
                        <p class="pe-hint">Aparece en la página pública del catálogo. Puedes resaltar texto y usar listas.</p>
                    </div>

                    {{-- NOTAS INTERNAS — menor peso visual --}}
                    <div class="rounded-xl border border-dashed border-gray-200 p-4">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-2.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>
                            {{ $infoExtra['notas_lbl'] }}
                        </p>
                        <textarea x-model="form.notes" class="pe-input bg-gray-50" rows="2"
                                  placeholder="{{ $infoExtra['notas_ph'] }}"></textarea>
                        <p class="pe-hint">Solo es visible para el equipo y no aparece en el catálogo.</p>
                    </div>

                </div>

                {{-- TAB: PRECIOS (adaptativo por rubro) --}}
                @php
                    $pCat = $project->category ?? 'default';
                    // Rubros donde NO hay venta mayorista
                    $noWholesale = in_array($pCat, ['restaurante','cafeteria','peluqueria','salon_belleza','clinica','veterinaria','gimnasio','educacion']);
                    // Y ahora también un interruptor propio del proyecto: había
                    // tiendas que no venden al por mayor cargando con campos de
                    // precio mayorista, cantidad mínima y unidad en cada producto.
                    // Se siembra encendido en los proyectos que YA tienen precios
                    // mayoristas cargados, así ninguna tienda existente cambia.
                    $mayoristaActivo = (string) ($project->setting('feature_mayorista') ?? '1') === '1';
                    if (!$mayoristaActivo) $noWholesale = true;
                    // Configuración de etiquetas de precio
                    $priceConfig = match(true) {
                        in_array($pCat, ['restaurante','cafeteria']) => [
                            'header'      => '🍽️ Precio del plato',
                            'header_bg'   => 'bg-orange-600',
                            'header_border'=> 'border-orange-200',
                            'body_bg'     => 'bg-orange-50',
                            'precio_lbl'  => 'Precio del plato *',
                            'precio_hint' => 'Precio que ve el cliente',
                            'tachado_lbl' => 'Precio sin descuento',
                            'unidad_lbl'  => 'Porción / presentación',
                            'unidad_ph'   => 'Ej: plato, 1/2 pollo, ración',
                            'costo_lbl'   => 'Costo de ingredientes',
                            'costo_hint'  => 'Costo de producción del plato',
                        ],
                        in_array($pCat, ['peluqueria','salon_belleza']) => [
                            'header'      => '✂️ Precio del servicio',
                            'header_bg'   => 'bg-pink-600',
                            'header_border'=> 'border-pink-200',
                            'body_bg'     => 'bg-pink-50',
                            'precio_lbl'  => 'Precio del servicio *',
                            'precio_hint' => 'Precio por sesión',
                            'tachado_lbl' => 'Precio normal (sin promo)',
                            'unidad_lbl'  => 'Presentación',
                            'unidad_ph'   => 'Ej: por sesión, por hora',
                            'costo_lbl'   => 'Costo de materiales',
                            'costo_hint'  => 'Productos usados en el servicio',
                        ],
                        $pCat === 'clinica' => [
                            'header'      => '🏥 Precio de consulta / tratamiento',
                            'header_bg'   => 'bg-blue-700',
                            'header_border'=> 'border-blue-200',
                            'body_bg'     => 'bg-blue-50',
                            'precio_lbl'  => 'Precio de la consulta *',
                            'precio_hint' => 'Precio por atención',
                            'tachado_lbl' => 'Precio normal (sin convenio)',
                            'unidad_lbl'  => 'Modalidad',
                            'unidad_ph'   => 'Ej: presencial, virtual, domicilio',
                            'costo_lbl'   => 'Costo operativo',
                            'costo_hint'  => 'Insumos / tiempo del médico',
                        ],
                        $pCat === 'veterinaria' => [
                            'header'      => '🐾 Precio del producto / servicio',
                            'header_bg'   => 'bg-teal-600',
                            'header_border'=> 'border-teal-200',
                            'body_bg'     => 'bg-teal-50',
                            'precio_lbl'  => 'Precio de venta *',
                            'precio_hint' => 'Precio al dueño de mascota',
                            'tachado_lbl' => 'Precio sin descuento',
                            'unidad_lbl'  => 'Presentación',
                            'unidad_ph'   => 'Ej: dosis, frasco, consulta',
                            'costo_lbl'   => 'Costo de compra',
                            'costo_hint'  => 'Lo que te cuesta a ti',
                        ],
                        $pCat === 'gimnasio' => [
                            'header'      => '💪 Precio del plan / clase',
                            'header_bg'   => 'bg-green-700',
                            'header_border'=> 'border-green-200',
                            'body_bg'     => 'bg-green-50',
                            'precio_lbl'  => 'Precio del plan *',
                            'precio_hint' => 'Precio que paga el miembro',
                            'tachado_lbl' => 'Precio normal (sin promo)',
                            'unidad_lbl'  => 'Duración / modalidad',
                            'unidad_ph'   => 'Ej: mensual, trimestral, por clase',
                            'costo_lbl'   => 'Costo operativo',
                            'costo_hint'  => 'Costo estimado por miembro',
                        ],
                        $pCat === 'educacion' => [
                            'header'      => '📚 Precio del curso / material',
                            'header_bg'   => 'bg-indigo-700',
                            'header_border'=> 'border-indigo-200',
                            'body_bg'     => 'bg-indigo-50',
                            'precio_lbl'  => 'Precio de matrícula *',
                            'precio_hint' => 'Precio que paga el alumno',
                            'tachado_lbl' => 'Precio normal (sin beca)',
                            'unidad_lbl'  => 'Modalidad',
                            'unidad_ph'   => 'Ej: mensual, ciclo, presencial',
                            'costo_lbl'   => 'Costo del material',
                            'costo_hint'  => 'Costo de producción',
                        ],
                        default => [
                            'header'      => 'Venta minorista — cliente individual',
                            'header_bg'   => 'bg-indigo-600',
                            'header_border'=> 'border-indigo-200',
                            'body_bg'     => 'bg-white',
                            'precio_lbl'  => 'Precio unitario *',
                            'precio_hint' => 'Precio final al cliente',
                            'tachado_lbl' => 'Precio anterior (tachado)',
                            'unidad_lbl'  => 'Unidad de medida',
                            'unidad_ph'   => 'Ej: unidad, kg, m2',
                            'costo_lbl'   => 'Costo de compra',
                            'costo_hint'  => 'Lo que te cuesta a ti — no se muestra al cliente',
                        ],
                    };
                @endphp
                <div x-show="tab==='precios'" x-cloak class="p-6 max-w-3xl space-y-4">

                    {{-- SECCIÓN PRECIO PRINCIPAL --}}
                    <div class="rounded-xl overflow-hidden border {{ $priceConfig['header_border'] }} shadow-sm">
                        <div class="{{ $priceConfig['header_bg'] }} px-4 py-2.5 flex items-center gap-2">
                            <p class="text-[13px] font-bold text-white tracking-wide">{{ $priceConfig['header'] }}</p>
                        </div>
                        @php
                            // Lo del negocio primero, las presentaciones de su rubro
                            // despues y la tabla oficial de medidas al final.
                            $gruposUnidad = \App\Support\UnidadesMedida::paraNegocio($pCat, $units->pluck('label')->all());
                        @endphp
                        <div class="p-4 {{ $priceConfig['body_bg'] }} grid grid-cols-3 gap-4">
                            <div>
                                <label class="pe-label">{{ $priceConfig['unidad_lbl'] }}</label>
                                {{-- Se escribe y filtra, como el desplegable de un ERP; lo
                                     que no este en la lista se puede teclear igual. --}}
                                <input type="text" x-model="form.unit" list="pe-unidades" autocomplete="off"
                                       class="pe-input" placeholder="{{ $priceConfig['unidad_ph'] }}">
                                <datalist id="pe-unidades">
                                    @foreach($gruposUnidad as $grupo => $unidades)
                                    @foreach($unidades as $u)
                                    <option value="{{ $u }}">{{ $grupo }}</option>
                                    @endforeach
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="pe-label">{{ $priceConfig['precio_lbl'] }}</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">{{ $priceConfig['precio_hint'] }}</p>
                            </div>
                            <div>
                                <label class="pe-label">{{ $priceConfig['tachado_lbl'] }}</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.compare_price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">Se muestra <s>tachado</s> como oferta</p>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN REVENDEDORES — límites de precio para el POS "Vender fácil".
                         Solo si el proyecto tiene el módulo activado en Configuración; si no,
                         el bloque no aparece en ningún producto (Ajustes → Revendedores). --}}
                    @if((string) ($project->setting('feature_revendedores') ?? '0') === '1')
                    <div class="rounded-xl overflow-hidden border border-amber-200">
                        <div class="bg-amber-500 px-4 py-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <p class="text-xs font-bold text-white uppercase tracking-wider">Revendedores — límites de precio</p>
                        </div>
                        <div class="p-4 bg-amber-50 grid grid-cols-3 gap-4">
                            <div>
                                <label class="pe-label">Precio sugerido</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.price_suggested" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">El precio que verá el revendedor por defecto</p>
                            </div>
                            <div>
                                <label class="pe-label">Precio mínimo 🔒</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.price_min" step="0.01" min="0" class="pe-input pl-10" placeholder="Sin límite">
                                </div>
                                <p class="pe-hint">Nunca podrá vender por debajo de esto</p>
                            </div>
                            <div>
                                <label class="pe-label">Precio máximo</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.price_max" step="0.01" min="0" class="pe-input pl-10" placeholder="Sin límite">
                                </div>
                                <p class="pe-hint">Tope superior (opcional)</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- SECCIÓN MAYORISTA (solo para rubros que aplica) --}}
                    @if(!$noWholesale)
                    <div class="rounded-xl overflow-hidden border border-green-200">
                        <div class="bg-green-700 px-4 py-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="text-xs font-bold text-white uppercase tracking-wider">Venta Mayorista — compra al por mayor</p>
                        </div>
                        <div class="p-4 bg-green-50 grid grid-cols-4 gap-4">
                            <div>
                                <label class="pe-label">Unidad mayorista</label>
                                {{-- Al elegir la unidad se completa sola la cantidad minima
                                     (media docena = 6, docena = 12, par = 2). Antes se podia
                                     guardar "docena" con minimo 1 o 3, lo que se contradecia. --}}
                                <select x-model="form.wholesale_unit" class="pe-input"
                                        @change="const m={'unidad':1,'par':2,'media docena':6,'docena':12};
                                                 if(m[form.wholesale_unit]) form.wholesale_min_qty=m[form.wholesale_unit];">
                                    <option value="">Sin especificar</option>
                                    <optgroup label="Completan la cantidad mínima">
                                        <option value="unidad">Unidad (1)</option>
                                        <option value="par">Par (2)</option>
                                        <option value="media docena">Media docena (6)</option>
                                        <option value="docena">Docena (12)</option>
                                    </optgroup>
                                    <optgroup label="Cantidad libre">
                                        <option value="caja">Caja</option>
                                        <option value="paquete">Paquete</option>
                                        <option value="saco">Saco</option>
                                    </optgroup>
                                    {{-- La misma tabla de medidas del campo Unidad: quien
                                         vende al por mayor por millar o por tonelada tambien
                                         tiene que poder decirlo. --}}
                                    <optgroup label="Unidades de medida">
                                        @foreach(\App\Support\UnidadesMedida::todas() as $u)
                                        <option value="{{ $u }}">{{ $u }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <p class="pe-hint">Al elegirla se completa la cantidad mínima</p>
                            </div>
                            <div>
                                <label class="pe-label">Precio x mayor</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.wholesale_price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">Precio al por mayor</p>
                            </div>
                            <div>
                                <label class="pe-label">P. venta mayorista</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.compare_price" step="0.01" min="0" class="pe-input pl-10 bg-gray-100" placeholder="0.00" readonly>
                                </div>
                                <p class="pe-hint">Igual al precio anterior</p>
                            </div>
                            <div>
                                <label class="pe-label">Cantidad mínima</label>
                                <input type="number" x-model="form.wholesale_min_qty" min="1" step="1" class="pe-input" placeholder="Ej: 25">
                                <p class="pe-hint">Unidades para activar precio mayor</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- PRECIO POR PLANES (Gimnasio) --}}
                    @if($pCat === 'gimnasio')
                    <div class="rounded-xl overflow-hidden border border-green-200">
                        <div class="bg-green-600 px-4 py-2">
                            <p class="text-xs font-bold text-white uppercase tracking-wider">💰 Precios por duración</p>
                        </div>
                        <div class="p-4 bg-green-50 grid grid-cols-3 gap-4">
                            <div>
                                <label class="pe-label">Precio mensual</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                            </div>
                            <div>
                                <label class="pe-label">Precio trimestral</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.wholesale_price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">Precio x 3 meses</p>
                            </div>
                            <div>
                                <label class="pe-label">Precio anual</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-0 bottom-0 flex items-center text-gray-400 text-sm font-medium pointer-events-none">{{ $currency }}</span>
                                    <input type="number" x-model="form.compare_price" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                                </div>
                                <p class="pe-hint">Precio x 12 meses</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- COSTO INTERNO + RENTABILIDAD — lado a lado --}}
                    <div class="grid grid-cols-2 gap-4 items-stretch">
                        <div class="pe-card p-4 bg-gray-50/60">
                            <p class="pe-section-title mb-3 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Costo interno (privado)
                            </p>
                            <label class="pe-label">{{ $priceConfig['costo_lbl'] }}</label>
                            <div class="relative">
                                <span class="absolute left-3 inset-y-0 flex items-center text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                <input type="number" x-model="form.cost" step="0.01" min="0" class="pe-input pl-10" placeholder="0.00">
                            </div>
                            <p class="pe-hint">{{ $priceConfig['costo_hint'] }}</p>
                        </div>

                        <div class="pe-card p-4">
                            <p class="pe-section-title mb-3">Análisis de rentabilidad</p>
                            <template x-if="form.price && form.cost">
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                        <p class="text-[10px] text-gray-400 mb-1">Ganancia</p>
                                        <p class="font-bold text-gray-800 text-sm"
                                           x-text="'{{ $currency }} ' + (parseFloat(form.price) - parseFloat(form.cost)).toFixed(2)"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                        <p class="text-[10px] text-gray-400 mb-1">Margen</p>
                                        <p class="font-bold text-sm"
                                           :class="margin >= 30 ? 'text-green-600' : margin >= 15 ? 'text-amber-500' : 'text-red-500'"
                                           x-text="margin + '%'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                        <p class="text-[10px] text-gray-400 mb-1">Descuento</p>
                                        <p class="font-bold text-indigo-600 text-sm"
                                           x-text="(form.compare_price && parseFloat(form.compare_price) > parseFloat(form.price))
                                               ? '-' + (((parseFloat(form.compare_price)-parseFloat(form.price))/parseFloat(form.compare_price))*100).toFixed(0) + '%'
                                               : '—'"></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!(form.price && form.cost)">
                                <p class="text-xs text-gray-400 leading-relaxed">Agrega el costo de compra para calcular la rentabilidad.</p>
                            </template>
                        </div>
                    </div>

                    {{-- IGV en la tienda --}}
                    <div class="pe-card px-4 py-3.5">
                        <p class="pe-section-title mb-1">IGV en la tienda</p>
                        <p class="text-xs text-gray-400 leading-relaxed mb-3">
                            El precio que pusiste arriba (<strong class="text-gray-500">{{ $currency }} <span x-text="form.price || '0.00'"></span></strong>) es siempre lo que paga el cliente.
                            Esto solo decide si la tienda le avisa que ese precio ya incluye IGV.
                        </p>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="form.has_tax = true"
                                    :class="form.has_tax ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'"
                                    class="h-10 rounded-lg border text-sm font-semibold transition-colors">
                                Sí, incluye IGV
                            </button>
                            <button type="button" @click="form.has_tax = false"
                                    :class="!form.has_tax ? 'bg-gray-700 border-gray-700 text-white' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'"
                                    class="h-10 rounded-lg border text-sm font-semibold transition-colors">
                                No aplica
                            </button>
                        </div>

                        <div x-show="form.has_tax" x-collapse class="mt-3 pt-3 border-t border-gray-100 space-y-3">
                            <div>
                                <label class="pe-label text-xs">Tasa de IGV</label>
                                <template x-if="taxes.length > 0">
                                    <select x-model.number="form.tax_rate" class="pe-input text-sm">
                                        <template x-for="t in taxes" :key="t.label">
                                            <option :value="t.rate" x-text="t.label + ' (' + t.rate + '%)'"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="taxes.length === 0">
                                    <input type="number" x-model.number="form.tax_rate" class="pe-input text-sm" placeholder="18" min="0" max="100">
                                </template>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-center">
                                    <p class="text-[10px] text-gray-400">Precio sin IGV</p>
                                    <p class="font-bold text-gray-700 text-sm" x-text="taxBase ? '{{ $currency }} ' + taxBase : '—'"></p>
                                </div>
                                <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-center">
                                    <p class="text-[10px] text-indigo-500">IGV incluido</p>
                                    <p class="font-bold text-indigo-700 text-sm" x-text="taxAmount ? '{{ $currency }} ' + taxAmount : '—'"></p>
                                </div>
                            </div>

                            <p class="text-[11px] text-gray-400 leading-relaxed">
                                En la tienda, junto al precio, se mostrará: <span class="text-gray-500 font-medium">"Precio incluye IGV (<span x-text="form.tax_rate || 18"></span>%)"</span>
                            </p>
                        </div>
                    </div>

                </div>

                {{-- TAB: INVENTARIO / DISPONIBILIDAD (adaptativo por rubro) --}}
                @php
                    $cat = $project->category ?? 'default';
                    $isRestaurant  = in_array($cat, ['restaurante','cafeteria']);
                    $isService     = in_array($cat, ['peluqueria','salon_belleza','clinica','veterinaria']);
                    $isGym         = $cat === 'gimnasio';
                    $isStock       = in_array($cat, ['retail','farmacia','taller','default','otro','inmobiliaria','educacion','whatsapp']);
                @endphp
                <div x-show="tab==='inventario'" x-cloak class="p-6 max-w-3xl space-y-4">

                @if($isRestaurant)
                    {{-- ══ RESTAURANTE / CAFETERÍA: Disponibilidad diaria ══ --}}
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-start gap-3">
                        <span class="text-2xl">🍽️</span>
                        <div>
                            <p class="text-sm font-bold text-orange-800">Disponibilidad diaria del plato</p>
                            <p class="text-xs text-orange-600 mt-0.5">Controla cuántas porciones preparas por día. Cuando se agoten, el plato se marca como no disponible.</p>
                        </div>
                    </div>

                    <div>
                        <label class="pe-label">Porciones disponibles hoy</label>
                        <div class="flex items-center gap-3 mt-1">
                            <button @click="form.stock = Math.max(0, (parseInt(form.stock)||0) - 1)"
                                    class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg transition border border-gray-200">-</button>
                            <input type="number" x-model.number="form.stock" min="0"
                                   class="pe-input text-center w-28 font-mono font-bold text-xl py-2">
                            <button @click="form.stock = (parseInt(form.stock)||0) + 1"
                                    class="w-10 h-10 rounded-xl bg-orange-500 hover:bg-orange-600 flex items-center justify-center text-white font-bold text-lg transition">+</button>
                            <span class="text-sm text-gray-400">porciones</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">Actualiza cada día antes de abrir. En 0 = plato agotado.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="pe-label">Producción máxima diaria</label>
                            <input type="number" x-model.number="form.stock_max" min="0" class="pe-input" placeholder="20">
                            <p class="pe-hint">Máximo que puedes preparar en un día</p>
                        </div>
                        <div>
                            <label class="pe-label">Alerta en (porciones)</label>
                            <input type="number" x-model.number="form.stock_min" min="0" class="pe-input" placeholder="3">
                            <p class="pe-hint">Avisa cuando quedan pocas porciones</p>
                        </div>
                    </div>

                    {{-- Barra visual --}}
                    <div x-show="form.stock_max > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span>Agotado</span>
                            <span x-text="(form.stock||0) + ' / ' + (form.stock_max||0) + ' porciones'"></span>
                            <span x-text="'Máx: ' + form.stock_max"></span>
                        </div>
                        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                            <div :style="'width:' + Math.min(100, ((form.stock||0) / (form.stock_max||1)) * 100) + '%'"
                                 :class="((form.stock||0)/(form.stock_max||1)) > 0.4 ? 'bg-orange-500' : ((form.stock||0)/(form.stock_max||1)) > 0.15 ? 'bg-amber-400' : 'bg-red-500'"
                                 class="h-full rounded-full transition-all duration-300"></div>
                        </div>
                    </div>

                    <div>
                        <label class="pe-label">Tiempo de preparación</label>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model="form.notes" min="0" class="pe-input w-28" placeholder="15">
                            <span class="text-sm text-gray-500">minutos</span>
                        </div>
                        <p class="pe-hint">Se muestra al cliente al hacer el pedido</p>
                    </div>

                    <div>
                        <label class="pe-label">Proveedor / Ingrediente principal</label>
                        <template x-if="suppliers.length > 0">
                            <select x-model="form.supplier" class="pe-input">
                                <option value="">Sin especificar</option>
                                <template x-for="s in suppliers" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="suppliers.length === 0">
                            <input type="text" x-model="form.supplier" class="pe-input text-sm" placeholder="Ej: Mercado Central, Proveedor cárnico">
                        </template>
                    </div>

                @elseif($isService)
                    {{-- ══ SERVICIOS (Peluquería, Clínica, Veterinaria): Cupos / Sesiones ══ --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                        <span class="text-2xl">📅</span>
                        <div>
                            <p class="text-sm font-bold text-blue-800">Control de cupos por servicio</p>
                            <p class="text-xs text-blue-600 mt-0.5">Define cuántas sesiones o atenciones simultáneas puedes ofrecer. Se conecta con tu agenda de citas.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="pe-label">Cupos disponibles</label>
                            <div class="flex items-center gap-2 mt-1">
                                <button @click="form.stock = Math.max(0, (parseInt(form.stock)||0) - 1)"
                                        class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 font-bold transition border border-gray-200">-</button>
                                <input type="number" x-model.number="form.stock" min="0" class="pe-input text-center w-20 font-mono font-bold text-lg">
                                <button @click="form.stock = (parseInt(form.stock)||0) + 1"
                                        class="w-9 h-9 rounded-lg bg-blue-600 hover:bg-blue-700 flex items-center justify-center text-white font-bold transition">+</button>
                            </div>
                            <p class="pe-hint">Cupos actuales disponibles</p>
                        </div>
                        <div>
                            <label class="pe-label">Cupos máximos por día</label>
                            <input type="number" x-model.number="form.stock_max" min="0" class="pe-input" placeholder="8">
                            <p class="pe-hint">Máximo de atenciones por día</p>
                        </div>
                    </div>

                    <div>
                        <label class="pe-label">Duración del servicio</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="5" step="5" class="pe-input w-28" placeholder="30">
                            <span class="text-sm text-gray-500">minutos por sesión</span>
                        </div>
                        <p class="pe-hint">Ayuda a calcular disponibilidad en la agenda</p>
                    </div>

                    <div>
                        <label class="pe-label">Profesional asignado</label>
                        <template x-if="suppliers.length > 0">
                            <select x-model="form.supplier" class="pe-input">
                                <option value="">Cualquier profesional</option>
                                <template x-for="s in suppliers" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="suppliers.length === 0">
                            <input type="text" x-model="form.supplier" class="pe-input text-sm" placeholder="Ej: Dra. García, Estilista María">
                        </template>
                        <p class="pe-hint">Gestiona profesionales desde Catálogos</p>
                    </div>

                @elseif($isGym)
                    {{-- ══ GIMNASIO: Cupos por clase / plan ══ --}}
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
                        <span class="text-2xl">💪</span>
                        <div>
                            <p class="text-sm font-bold text-green-800">Cupos y capacidad</p>
                            <p class="text-xs text-green-600 mt-0.5">Para clases: define el aforo máximo. Para membresías: define cuántas vendiste vs el límite.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="pe-label">Cupos disponibles</label>
                            <div class="flex items-center gap-2 mt-1">
                                <button @click="form.stock = Math.max(0, (parseInt(form.stock)||0) - 1)"
                                        class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold transition border border-gray-200">-</button>
                                <input type="number" x-model.number="form.stock" min="0" class="pe-input text-center w-20 font-mono font-bold text-lg">
                                <button @click="form.stock = (parseInt(form.stock)||0) + 1"
                                        class="w-9 h-9 rounded-lg bg-green-600 hover:bg-green-700 flex items-center justify-center text-white font-bold transition">+</button>
                            </div>
                        </div>
                        <div>
                            <label class="pe-label">Capacidad máxima</label>
                            <input type="number" x-model.number="form.stock_max" min="0" class="pe-input" placeholder="20">
                            <p class="pe-hint">Aforo máximo del local / clase</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="pe-label">Instructor asignado</label>
                            <template x-if="suppliers.length > 0">
                                <select x-model="form.supplier" class="pe-input">
                                    <option value="">Sin asignar</option>
                                    <template x-for="s in suppliers" :key="s"><option :value="s" x-text="s"></option></template>
                                </select>
                            </template>
                            <template x-if="suppliers.length === 0">
                                <input type="text" x-model="form.supplier" class="pe-input text-sm" placeholder="Ej: Carlos Pérez">
                            </template>
                        </div>
                        <div>
                            <label class="pe-label">Sala / Área</label>
                            <template x-if="locations.length > 0">
                                <select x-model="form.location" class="pe-input">
                                    <option value="">Sin asignar</option>
                                    <template x-for="l in locations" :key="l"><option :value="l" x-text="l"></option></template>
                                </select>
                            </template>
                            <template x-if="locations.length === 0">
                                <input type="text" x-model="form.location" class="pe-input text-sm" placeholder="Ej: Sala principal, Piscina">
                            </template>
                        </div>
                    </div>

                    {{-- Barra capacidad --}}
                    <div x-show="form.stock_max > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span>Disponibles: <strong x-text="form.stock||0"></strong></span>
                            <span x-text="'Ocupados: ' + Math.max(0,(form.stock_max||0)-(form.stock||0)) + ' / ' + (form.stock_max||0)"></span>
                        </div>
                        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                            <div :style="'width:' + Math.min(100, (Math.max(0,(form.stock_max||0)-(form.stock||0)) / (form.stock_max||1)) * 100) + '%'"
                                 :class="((form.stock||0)/(form.stock_max||1)) > 0.3 ? 'bg-green-500' : 'bg-amber-400'"
                                 class="h-full rounded-full transition-all duration-300"></div>
                        </div>
                    </div>

                @else
                    {{-- ══ STOCK FÍSICO (Retail, Farmacia, Taller, Default) ══ --}}

                    {{-- CONTROL DE STOCK --}}
                    <div class="pe-card p-4">
                        <label class="flex items-center justify-between gap-3 cursor-pointer">
                            <span>
                                <span class="block text-sm font-semibold text-gray-700">Controlar stock de este producto</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Si lo apagas, el producto siempre se muestra disponible y no se descuenta al vender.</span>
                            </span>
                            <button type="button" role="switch"
                                    :aria-checked="form.stock !== null && form.stock !== undefined"
                                    @click="form.stock = (form.stock !== null && form.stock !== undefined) ? null : (form.stock ?? 0)"
                                    :class="(form.stock !== null && form.stock !== undefined) ? 'bg-indigo-600' : 'bg-gray-300'"
                                    class="relative w-10 h-5 rounded-full transition-colors flex-shrink-0">
                                <span :class="(form.stock !== null && form.stock !== undefined) ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                            </button>
                        </label>

                        <template x-if="form.stock !== null && form.stock !== undefined">
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-4">
                            <div>
                                <label class="pe-label">Stock actual</label>
                                <div class="flex items-center gap-3">
                                    <button @click="form.stock = Math.max(0, (parseInt(form.stock)||0) - 1)"
                                            class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg transition border border-gray-200 flex-shrink-0">−</button>
                                    <input type="number" x-model.number="form.stock" min="0"
                                           class="pe-input text-center w-24 font-mono font-bold text-xl h-11">
                                    <button @click="form.stock = (parseInt(form.stock)||0) + 1"
                                            class="w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center text-white font-bold text-lg transition flex-shrink-0">+</button>
                                    <span class="text-sm text-gray-400" x-text="form.unit ? 'en ' + form.unit + 's' : 'unidades'"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="pe-label">Stock mínimo <span class="text-gray-400 font-normal normal-case">(alerta)</span></label>
                                    <input type="number" x-model.number="form.stock_min" min="0" class="pe-input" placeholder="5">
                                    <p class="pe-hint">Alerta cuando baje de este nivel</p>
                                </div>
                                <div>
                                    <label class="pe-label">Stock máximo</label>
                                    <input type="number" x-model.number="form.stock_max" min="0" class="pe-input" placeholder="100">
                                    <p class="pe-hint">Capacidad máxima de almacenaje</p>
                                </div>
                            </div>

                            {{-- Barra de stock --}}
                            <div x-show="form.stock_max > 0">
                                <div class="flex justify-between text-[11px] text-gray-400 mb-1.5">
                                    <span x-text="'Stock: ' + (form.stock||0) + ' / ' + (form.stock_max||0)"></span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div :style="'width:' + Math.min(100, ((form.stock||0) / (form.stock_max||1)) * 100) + '%'"
                                         :class="((form.stock||0)/(form.stock_max||1)) > 0.5 ? 'bg-green-500' : ((form.stock||0)/(form.stock_max||1)) > 0.2 ? 'bg-amber-400' : 'bg-red-500'"
                                         class="h-full rounded-full transition-all duration-300"></div>
                                </div>
                            </div>

                            {{-- Estado del stock --}}
                            <div :class="{
                                    'bg-red-50 border-red-100':     stockStatus.color === 'red',
                                    'bg-amber-50 border-amber-100': stockStatus.color === 'amber',
                                    'bg-green-50 border-green-100': stockStatus.color === 'green'
                                 }"
                                 class="rounded-lg px-3.5 py-2.5 border flex items-center gap-2.5">
                                <svg class="w-4 h-4 flex-shrink-0"
                                     :class="{ 'text-red-500': stockStatus.color==='red', 'text-amber-500': stockStatus.color==='amber', 'text-green-500': stockStatus.color==='green' }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-[13px] font-semibold"
                                   :class="{ 'text-red-700': stockStatus.color==='red', 'text-amber-700': stockStatus.color==='amber', 'text-green-700': stockStatus.color==='green' }"
                                   x-text="stockStatus.label"></p>
                            </div>
                        </div>
                        </template>
                    </div>

                    {{-- ABASTECIMIENTO --}}
                    <div class="pe-card p-4">
                        <p class="pe-section-title mb-3">Abastecimiento</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="pe-label flex items-center justify-between">
                                    Ubicación en almacén
                                    <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                                </label>
                                <template x-if="locations.length > 0">
                                    <div>
                                        <select x-model="form.location" class="pe-input">
                                            <option value="">Sin ubicación</option>
                                            <template x-for="l in locations" :key="l">
                                                <option :value="l" x-text="l"></option>
                                            </template>
                                        </select>
                                        <p class="pe-hint-ok">&#10003; Viene del catálogo de configuración</p>
                                    </div>
                                </template>
                                <template x-if="locations.length === 0">
                                    <input type="text" x-model="form.location" class="pe-input font-mono text-sm" placeholder="Ej: A-12, Bodega 2">
                                </template>
                            </div>
                            <div>
                                <label class="pe-label flex items-center justify-between">
                                    Proveedor
                                    <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                                </label>
                                <template x-if="suppliers.length > 0">
                                    <div>
                                        <select x-model="form.supplier" class="pe-input">
                                            <option value="">Sin proveedor</option>
                                            <template x-for="s in suppliers" :key="s">
                                                <option :value="s" x-text="s"></option>
                                            </template>
                                        </select>
                                        <p class="pe-hint-ok">&#10003; Viene del catálogo de configuración</p>
                                    </div>
                                </template>
                                <template x-if="suppliers.length === 0">
                                    <input type="text" x-model="form.supplier" class="pe-input text-sm" placeholder="Ej: Proveedor A">
                                </template>
                            </div>
                        </div>
                    </div>

                    @if($allCatalogs === 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">Configura tus catálogos</p>
                            <p class="text-xs text-blue-600 mt-0.5">
                                Crea listas de <strong>proveedores</strong>, <strong>ubicaciones</strong> e <strong>impuestos</strong> en
                                <a href="{{ route('catalogs.index') }}" class="underline font-semibold">Catálogos de configuración</a>
                                y aparecerán como desplegables aquí automáticamente.
                            </p>
                        </div>
                    </div>
                    @endif

                @endif

                </div>

                {{-- TAB: IMAGENES --}}
                <div x-show="tab==='imagenes'" x-cloak class="p-6 max-w-5xl space-y-4"
                     x-data="{
                        uploading: false, imgError: '', replacingId: null,
                        async uploadFiles(files) {
                            this.uploading = true; this.imgError = '';
                            for (const file of files) {
                                const fd = new FormData();
                                fd.append('image', file);
                                fd.append('_token', '{{ csrf_token() }}');
                                const res = await fetch('{{ url('/bixoadmin/products') }}/' + this.selected.id + '/images', { method:'POST', body: fd });
                                const data = await res.json();
                                if (data.ok) {
                                    if (!this.selected.images) this.selected.images = [];
                                    this.selected.images.push(data.image);
                                } else { this.imgError = data.message || 'Error al subir imagen'; }
                            }
                            this.uploading = false;
                        },
                        async makeMain(img) {
                            await fetch('{{ url('/bixoadmin/products') }}/' + this.selected.id + '/images/' + img.id + '/main', {
                                method:'PATCH', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                            });
                            this.selected.images.forEach(i => i.is_main = false);
                            img.is_main = true;
                        },
                        async removeImage(img) {
                            const ok = await window.__confirm({ title:'Eliminar imagen', msg:'¿Eliminar esta imagen del producto?', confirmLabel:'Sí, eliminar' });
                            if (!ok) return;
                            await fetch('{{ url('/bixoadmin/products') }}/' + this.selected.id + '/images/' + img.id, {
                                method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                            });
                            this.selected.images = this.selected.images.filter(i => i.id !== img.id);
                        },
                        async replaceImage(img, file) {
                            if (!file) return;
                            const wasMain = img.is_main;
                            await this.uploadFiles([file]);
                            const fresh = this.selected.images[this.selected.images.length - 1];
                            if (wasMain && fresh) await this.makeMain(fresh);
                            await fetch('{{ url('/bixoadmin/products') }}/' + this.selected.id + '/images/' + img.id, {
                                method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                            });
                            this.selected.images = this.selected.images.filter(i => i.id !== img.id);
                        },
                     }">

                    @php
                        $imgActionBtn = 'w-7 h-7 rounded-lg bg-white/95 hover:bg-white text-gray-700 flex items-center justify-center transition shadow-sm';
                    @endphp

                    {{-- Encabezado: cuantas hay y para que sirve el orden --}}
                    <div class="flex items-end justify-between gap-3 flex-wrap">
                        <div>
                            <p class="pe-section-title">Imágenes del producto</p>
                            <p class="text-xs text-gray-400 mt-0.5"
                               x-text="(selected?.images || []).length
                                        ? (selected.images.length === 1
                                            ? 'Una imagen. La principal es la que ve el cliente en la tienda.'
                                            : selected.images.length + ' imágenes. La principal encabeza la ficha; las demás siguen este orden.')
                                        : 'Todavía no hay ninguna imagen.'"></p>
                        </div>
                        <span x-show="uploading" x-cloak class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Subiendo...
                        </span>
                    </div>

                    {{-- La principal manda: ocupa dos tercios. Las demas y el boton de
                         agregar viven a su lado, no debajo, para que el hueco vacio
                         deje de pesar visualmente tanto como el producto. --}}
                    <div class="grid gap-3 md:grid-cols-3">

                        {{-- PRINCIPAL --}}
                        <div :class="(selected?.images || []).length ? 'md:col-span-2' : 'md:col-span-3'">
                            <template x-if="selected?.images?.find(i => i.is_main) || selected?.images?.[0]">
                                <div class="group relative aspect-square rounded-xl overflow-hidden pe-card bg-slate-50 focus-within:ring-2 focus-within:ring-indigo-400">
                                    {{-- `contain` y no `cover`: aqui se revisa la foto, y una
                                         que sale recortada es justo la que hay que poder ver
                                         entera para decidir cambiarla. --}}
                                    <img :src="(selected.images.find(i => i.is_main) || selected.images[0]).url"
                                         class="w-full h-full object-contain" alt="Imagen principal del producto">
                                    <div class="absolute top-2.5 left-2.5 bg-indigo-600 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">Principal</div>
                                    <div class="absolute inset-0 pe-acts bg-black/45 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1.5"
                                         x-data="{ get main() { return selected.images.find(i => i.is_main) || selected.images[0]; } }">
                                        <button @click="window.open(main.url, '_blank')" :class="'{{ $imgActionBtn }}'" title="Ver a tamaño real">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <label :class="'{{ $imgActionBtn }} cursor-pointer'" title="Reemplazar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <input type="file" accept="image/*" class="hidden" @change="replaceImage(main, $event.target.files[0]); $event.target.value=''">
                                        </label>
                                        <button @click="removeImage(main)" :class="'{{ $imgActionBtn }} hover:text-red-600'" title="Eliminar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Sin ninguna imagen la zona de subida ocupa el sitio de la
                                 principal, que es donde el ojo la busca. --}}
                            <template x-if="!(selected?.images?.length)">
                                <label class="aspect-square rounded-xl border-2 border-dashed transition cursor-pointer
                                              flex flex-col items-center justify-center gap-2 group"
                                       :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50'"
                                       x-data="imageDropzone(files => uploadFiles(files))"
                                       @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                       @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center transition"
                                         :class="isDragging ? 'bg-indigo-500' : 'bg-gray-100 group-hover:bg-indigo-100'">
                                        <svg class="w-5 h-5 transition" :class="isDragging ? 'text-white' : 'text-gray-400 group-hover:text-indigo-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold" :class="isDragging ? 'text-indigo-600' : 'text-gray-600 group-hover:text-indigo-600'"
                                          x-text="isDragging ? 'Suelta aquí' : 'Agrega la primera imagen'"></span>
                                    <span class="text-xs text-gray-400">Arrástrala aquí o haz clic para elegirla</span>
                                    <input type="file" accept="image/*" multiple class="hidden"
                                           @change="uploadFiles(Array.from($event.target.files)); $event.target.value = ''">
                                </label>
                            </template>
                        </div>

                        {{-- LAS DEMAS, Y DEBAJO EL BOTON DE AGREGAR --}}
                        <div x-show="(selected?.images || []).length" class="flex flex-col gap-2.5 pe-thumbs">
                            <div class="grid grid-cols-3 md:grid-cols-2 gap-2.5">
                                <template x-for="(img, i) in (selected.images || []).filter(x => !x.is_main)" :key="img.id">
                                    <div class="group relative aspect-square rounded-lg overflow-hidden border border-gray-200 bg-slate-50">
                                        <img :src="img.url" class="w-full h-full object-contain" :alt="'Imagen ' + (i + 2) + ' del producto'">
                                        {{-- El numero dice en que orden las vera el cliente. --}}
                                        <span class="absolute top-1 left-1 w-4 h-4 rounded bg-white/90 text-[9px] font-bold text-gray-500 flex items-center justify-center"
                                              x-text="i + 2"></span>
                                        <div class="absolute inset-0 pe-acts bg-black/45 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                                            <button @click="makeMain(img)" class="w-6 h-6 rounded-md bg-white/95 hover:bg-white flex items-center justify-center" title="Hacer principal">
                                                <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.539 1.118l-3.37-2.448a1 1 0 00-1.176 0l-3.37 2.448c-.783.57-1.838-.196-1.538-1.118l1.286-3.957a1 1 0 00-.363-1.118l-3.37-2.448c-.782-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.285-3.958z"/></svg>
                                            </button>
                                            <button @click="removeImage(img)" class="w-6 h-6 rounded-md bg-white/95 hover:bg-white text-red-600 flex items-center justify-center" title="Eliminar">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <label class="pe-add rounded-lg border-2 border-dashed transition cursor-pointer py-4 px-2
                                          flex flex-col items-center justify-center gap-1 group"
                                   :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50'"
                                   x-data="imageDropzone(files => uploadFiles(files))"
                                   @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                   @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center transition"
                                     :class="isDragging ? 'bg-indigo-500' : 'bg-gray-100 group-hover:bg-indigo-100'">
                                    <svg class="w-3.5 h-3.5 transition" :class="isDragging ? 'text-white' : 'text-gray-400 group-hover:text-indigo-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                                <span class="text-[11px] font-semibold text-center leading-tight" :class="isDragging ? 'text-indigo-600' : 'text-gray-500 group-hover:text-indigo-600'"
                                      x-text="isDragging ? 'Suelta aquí' : 'Agregar o arrastrar'"></span>
                                <input type="file" accept="image/*" multiple class="hidden"
                                       @change="uploadFiles(Array.from($event.target.files)); $event.target.value = ''">
                            </label>
                        </div>
                    </div>

                    <div x-show="imgError" x-text="imgError" class="text-xs text-red-600"></div>

                    <p class="text-xs text-gray-400 flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong class="text-gray-500">Recomendado:</strong> imágenes cuadradas de 800×800&nbsp;px o superiores, con el producto centrado. Formatos JPG, PNG, WebP · Máx. 4&nbsp;MB por imagen.</span>
                    </p>
                </div>

            </div>{{-- /overflow-y-auto --}}

            {{-- Footer --}}
            <div class="flex-shrink-0 px-4 sm:px-6 py-3 sm:py-3.5 border-t border-gray-100 bg-white flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <button x-show="!creating && selected" @click="del()"
                            class="pe-btn pe-btn-sm pe-btn-ghost-danger">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar
                    </button>
                    <button x-show="!creating && selected" @click="duplicate()" :disabled="duplicating"
                            class="pe-btn pe-btn-sm pe-btn-secondary">
                        <svg x-show="!duplicating" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="duplicating" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span x-text="duplicating ? 'Duplicando...' : 'Duplicar'"></span>
                    </button>
                    <span x-show="hasChanges" x-cloak class="flex items-center gap-1.5 text-xs font-medium text-amber-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Cambios sin guardar
                    </span>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <button @click="selected=null; creating=false" class="pe-btn pe-btn-secondary">
                        Cancelar
                    </button>
                    <button @click="save()"
                            :disabled="saving || !form.name || !form.price"
                            class="pe-btn pe-btn-primary">
                        <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (creating ? 'Crear producto' : 'Guardar cambios')"></span>
                    </button>
                </div>
            </div>

        </div>
    </template>

{{-- ── Modal log de importación ────────────────────────────────────────────── --}}
<div x-show="importLog.show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="importLog.show=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-xl">📋</span>
                <h3 class="font-semibold text-gray-800">Resultado de importación</h3>
            </div>
            <button @click="importLog.show=false" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Stats --}}
        <div class="px-6 py-4 grid grid-cols-3 gap-3">
            <div class="rounded-xl bg-green-50 border border-green-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-green-700" x-text="importLog.created"></div>
                <div class="text-xs text-green-600 mt-0.5">Creados</div>
            </div>
            <div class="rounded-xl bg-blue-50 border border-blue-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-blue-700" x-text="importLog.updated"></div>
                <div class="text-xs text-blue-600 mt-0.5">Actualizados</div>
            </div>
            <div class="rounded-xl bg-red-50 border border-red-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-red-700" x-text="importLog.errors.length"></div>
                <div class="text-xs text-red-600 mt-0.5">Errores</div>
            </div>
        </div>
        {{-- Sin cambios --}}
        <div x-show="importLog.created===0 && importLog.updated===0 && importLog.errors.length===0"
             class="px-6 pb-4 text-sm text-gray-500 text-center">
            No se encontraron filas para importar.
        </div>
        {{-- Warnings (categorías no encontradas) --}}
        <div x-show="importLog.warnings && importLog.warnings.length > 0" class="px-6 pb-3">
            <ul class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 space-y-1.5">
                <template x-for="(w, i) in importLog.warnings" :key="i">
                    <li class="text-xs text-yellow-800 flex gap-2">
                        <span class="flex-shrink-0">⚠️</span>
                        <span x-text="w"></span>
                    </li>
                </template>
            </ul>
        </div>
        {{-- Lista de errores --}}
        <div x-show="importLog.errors.length > 0" class="px-6 pb-4">
            <p class="text-xs font-semibold text-red-600 mb-2">Detalle de errores:</p>
            <ul class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 space-y-1.5 max-h-48 overflow-y-auto">
                <template x-for="(err, i) in importLog.errors" :key="i">
                    <li class="text-xs text-red-700 flex gap-2">
                        <span class="text-red-400 flex-shrink-0">•</span>
                        <span x-text="err"></span>
                    </li>
                </template>
            </ul>
        </div>
        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
            <button @click="location.reload()"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                Entendido
            </button>
        </div>
    </div>
</div>{{-- /modal --}}

</div>{{-- /detalle --}}
</div>{{-- /body --}}

</div>{{-- /page --}}
</x-slot>
</x-app-layout>
