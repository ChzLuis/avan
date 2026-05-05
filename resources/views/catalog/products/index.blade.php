<x-app-layout>
<x-slot name="slot">

@php
    $csrf     = csrf_token();
    $pid      = $project->id;
    $base     = url("/bixoadmin");
    $currency = $project->setting('currency', 'S/');

    $supplierList = $project->catalogLists()->where('type', 'proveedor')->with('values')->first();
    $suppliers    = $supplierList ? $supplierList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $locationList = $project->catalogLists()->where('type', 'ubicacion')->with('values')->first();
    $locations    = $locationList ? $locationList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $taxList  = $project->catalogLists()->where('type', 'impuesto')->with('values')->first();
    $taxes    = $taxList ? $taxList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

    $allCatalogs = $project->catalogLists()->where('is_active', true)->count();
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Catálogo de Productos</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="product-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('categories.index') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Categorías
        </a>
        <a href="{{ route('services.index') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Servicios
        </a>
        <button onclick="window.dispatchEvent(new Event('open-new-product'))"
                class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo producto
        </button>
        <div class="relative hidden sm:block" x-data="{ open: false }">
            <button @click="open=!open"
                    class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Carga Masiva
                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 class="absolute right-0 mt-1.5 w-52 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                <a href="{{ route('products.template') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Descargar plantilla Excel
                </a>
                <label class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importar archivo
                    <input type="file" accept=".csv,.txt,.xls,.xlsx" class="hidden" id="import-file"
                           @change="window.dispatchEvent(new CustomEvent('do-import-csv', { detail: { file: $event.target.files[0] } })); $event.target.value=''">
                </label>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="{{ route('products.export') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <p class="px-4 py-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Para marketplaces</p>
                <a href="{{ route('products.export.meli') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-yellow-500">ML</span>
                    Mercado Libre
                </a>
                <a href="{{ route('products.export.rappi') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-orange-500">R</span>
                    Rappi
                </a>
                <a href="{{ route('products.export.shopee') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="w-4 h-4 flex items-center justify-center text-xs font-bold text-red-500">S</span>
                    Shopee
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <p class="px-4 py-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Catálogo web</p>
                <a href="{{ route('products.export.static') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Exportar para GitHub Pages
                </a>
            </div>
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
        'wholesale_min_qty'=> $p->wholesale_min_qty ?? null,
        'wholesale_unit'   => $p->wholesale_unit ?? '',
        'cost'             => $p->cost !== null ? (float)$p->cost : null,
        'unit'             => $p->unit ?? '',
        'stock'            => (int)($p->stock ?? 0),
        'stock_min'        => (int)($p->stock_min ?? 0),
        'stock_max'        => (int)($p->stock_max ?? 0),
        'location'         => '',
        'supplier'         => '',
        'has_tax'          => false,
        'tax_rate'         => 18,
        'is_available'     => (bool)$p->is_available,
        'category_id'      => $p->category_id,
        'brand_catalog_id' => $p->brand_catalog_id,
        'category_name'    => $p->category?->name ?? '',
        'images'           => $p->images->map(fn($i) => ['id'=>$i->id,'url'=>$i->url,'is_main'=>$i->is_main])->values()->toArray(),
        'main_image'       => $p->mainImage?->url,
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
        importLog: { show: false, created: 0, updated: 0, skipped: 0, errors: [], reloadOnClose: false },

        get filtered() {
            return this.products.filter(p => {
                if (this.search && !p.name.toLowerCase().includes(this.search.toLowerCase())
                    && !(p.sku||'').toLowerCase().includes(this.search.toLowerCase())) return false;
                if (this.filterCat !== null) {
                    const cat = this.categories.find(c => c.id === this.filterCat);
                    const childIds = cat ? (cat.children||[]).map(s => s.id) : [];
                    if (p.category_id !== this.filterCat && !childIds.includes(p.category_id)) return false;
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

        get priceWithTax() {
            const p = parseFloat(this.form.price);
            if (!p || !this.form.has_tax) return null;
            return (p * (1 + (parseFloat(this.form.tax_rate)||18)/100)).toFixed(2);
        },

        get stockStatus() {
            const s = parseInt(this.form.stock) || 0;
            const min = parseInt(this.form.stock_min) || 0;
            if (s <= 0) return { color:'red', label:'Agotado sin stock' };
            if (min > 0 && s <= min) return { color:'amber', label:'Stock bajo: quedan ' + s + ' unidades' };
            return { color:'green', label:'En stock: ' + s + ' unidades disponibles' };
        },

        clearFilters() {
            this.search = ''; this.filterCat = null; this.filterStatus = ''; this.filterStock = false; this.filterLowStock = false;
        },

        select(p) {
            this.selected = p; this.creating = false; this.tab = 'info';
            this.form = { ...p };
        },

        openNew() {
            this.selected = null; this.creating = true; this.tab = 'info';
            this.form = {
                name:'', sku:'', barcode:'', description:'', notes:'',
                price:'', compare_price:'', wholesale_price:'', wholesale_min_qty:'', wholesale_unit:'', cost:'', unit:'',
                stock:0, stock_min:0, stock_max:0,
                location:'', supplier:'',
                has_tax:false, tax_rate:18,
                is_available:true, category_id:'', brand_catalog_id:''
            };
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
                if (idx > -1) this.products[idx] = data.product;
                this.selected = data.product;
                this.form = { ...data.product };
            }
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
                this.importLog = { show: true, created: data.created||0, updated: data.updated||0, skipped: data.skipped||0, errors: data.errors||[], reloadOnClose: true };
            } catch(e) {
                this.importing = false;
                this.importLog = { show: true, created: 0, updated: 0, skipped: 0, errors: ['Error de red al importar. Verifica que el archivo sea CSV o XLS valido.'] };
            }
        },

        init() {
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
        },
    }));
});
</script>
<div class="flex flex-1 overflow-hidden" x-data="productPage()">

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

    <div class="w-6 border-t border-gray-200 my-1"></div>

    {{-- Categorías --}}
    <div class="relative group">
        <button @click="filterCat = null"
                :class="filterCat===null ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">Todas las categorías</span>
    </div>
    @foreach($categories as $cat)
    <div class="relative group">
        <button @click="filterCat = filterCat==={{ $cat->id }} ? null : {{ $cat->id }}"
                :class="filterCat==={{ $cat->id }} ? 'bg-indigo-100 text-indigo-700 font-bold' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black transition">
            {{ mb_substr($cat->name, 0, 2) }}
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            {{ $cat->name }}
        </span>
    </div>
    @endforeach
</div>

{{-- ─── LISTA CENTRAL ─────────────────────────────────────────────── --}}
<div class="w-72 border-r border-gray-200 flex-col bg-white flex-shrink-0"
     :class="panel==='list' ? 'flex w-full md:w-72' : 'hidden md:flex md:w-72'">
    {{-- Búsqueda --}}
    <div class="p-3 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
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
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
        {{-- Nuevo --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition text-left"
                :class="creating ? 'bg-indigo-50 border-l-2 border-indigo-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-indigo-700">Nuevo producto</p>
                <p class="text-xs text-gray-400">Agregar al catálogo</p>
            </div>
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
                    <button @click="openNew(); panel='detail'"
                            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3 py-1.5 rounded-lg transition">
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
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left cursor-grab active:cursor-grabbing"
                    :class="[selected?.id===p.id ? 'bg-indigo-50 border-l-2 border-indigo-500' : '', dragId===p.id ? 'opacity-40' : '']">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs font-semibold text-indigo-700" x-text="'{{ $currency }} ' + parseFloat(p.price).toFixed(2)"></span>
                        <span x-show="p.sku" class="text-[10px] text-gray-400 font-mono truncate" x-text="p.sku"></span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span :class="p.is_available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md"
                          x-text="p.is_available ? 'Activo' : 'Inactivo'"></span>
                    <span x-show="(p.stock||0) <= 0"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-red-100 text-red-600">
                        Agotado
                    </span>
                    <span x-show="(p.stock||0) > 0 && (p.stock_min||0) > 0 && (p.stock||0) <= (p.stock_min||0)"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-700">
                        Stock bajo
                    </span>
                </div>
            </button>
        </template>

        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <p class="text-sm text-gray-400">Sin productos</p>
        </div>
    </div>
</div>

{{-- PANEL DETALLE --}}
<div class="flex-col overflow-hidden bg-white"
     :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

    <template x-if="!selected && !creating">
        <div class="flex-1 flex flex-col items-center justify-center text-center p-10 text-gray-300">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un producto</p>
            <p class="text-sm text-gray-300 mt-1">o crea uno nuevo</p>
        </div>
    </template>

    <template x-if="selected || creating">
        <div class="flex flex-col h-full">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center flex-shrink-0">
                <button @click="panel='list'" type="button"
                        class="md:hidden mr-3 flex-shrink-0 text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div>
                    <h2 class="font-semibold text-gray-800 text-base"
                        x-text="creating ? 'Nuevo producto' : (form.name || 'Sin nombre')"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="creating ? 'Completa la información del producto'
                           : ('ID #'+selected.id+(selected.category_name ? ' · '+selected.category_name : '')+(form.sku ? ' · SKU: '+form.sku : ''))"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Disponible</span>
                    <button @click="form.is_available = !form.is_available" type="button"
                            :class="form.is_available ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200">
                        <span :class="form.is_available ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 bg-white flex-shrink-0 overflow-x-auto">
                <button @click="tab='info'"
                        :class="tab==='info' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition">
                    Informacion
                </button>
                <button @click="tab='precios'"
                        :class="tab==='precios' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition">
                    Precios
                </button>
                <button @click="tab='inventario'"
                        :class="tab==='inventario' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition">
                    Inventario
                </button>
                <button @click="tab='imagenes'" x-show="!creating"
                        :class="tab==='imagenes' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition">
                    Imagenes
                </button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto">

                {{-- TAB: INFORMACION --}}
                <div x-show="tab==='info'" class="p-6 max-w-2xl space-y-5">

                    <div>
                        <label class="label">Nombre del producto *</label>
                        <input type="text" x-model="form.name" class="input" placeholder="Ej: Laptop Dell XPS 15, Camisa Oxford, Café 250g">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">SKU / Codigo interno</label>
                            <input type="text" x-model="form.sku" class="input font-mono text-sm" placeholder="LPT-001">
                            <p class="text-[10px] text-gray-400 mt-0.5">Identificador unico interno</p>
                        </div>
                        <div>
                            <label class="label">Codigo de barras</label>
                            <input type="text" x-model="form.barcode" class="input font-mono text-sm" placeholder="7501234567890">
                            <p class="text-[10px] text-gray-400 mt-0.5">EAN, UPC, QR, etc.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label flex items-center justify-between">
                                Categoria
                                <a href="{{ route('categories.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <select x-model.number="form.category_id" class="input">
                                <option value="">Sin categoría</option>
                                <template x-for="c in categories" :key="c.id">
                                    <template x-if="c.children && c.children.length > 0">
                                        <optgroup :label="c.name">
                                            <template x-for="s in c.children" :key="s.id">
                                                <option :value="s.id" x-text="'  └ ' + s.name"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                    <template x-if="!c.children || c.children.length === 0">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </template>
                            </select>
                            <p class="text-[10px] text-green-600 mt-0.5">&#10003; Viene del modulo de categorias</p>
                        </div>
                        <div>
                            <label class="label flex items-center justify-between">
                                Marca
                                <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <template x-if="brands.length > 0">
                                <div>
                                    <select x-model.number="form.brand_catalog_id" class="input">
                                        <option value="">Sin marca</option>
                                        <template x-for="b in brands" :key="b.id">
                                            <option :value="b.id" x-text="b.label"></option>
                                        </template>
                                    </select>
                                    <p class="text-[10px] text-green-600 mt-0.5">&#10003; Viene del catalogo de configuracion</p>
                                </div>
                            </template>
                            <template x-if="brands.length === 0">
                                <a href="{{ route('catalogs.index') }}"
                                   class="flex items-center gap-1.5 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition text-[11px] text-amber-700 font-medium mt-1">
                                    + Crear catalogo de marcas
                                </a>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="label">Descripcion publica</label>
                        <textarea x-model="form.description" class="input resize-none" rows="3"
                                  placeholder="Descripcion visible en el catalogo online para tus clientes..."></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5">Aparece en la pagina publica del catalogo</p>
                    </div>

                    <div>
                        <label class="label">Notas internas</label>
                        <textarea x-model="form.notes" class="input resize-none" rows="2"
                                  placeholder="Notas privadas: proveedor preferido, instrucciones..."></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5">Solo visible para tu equipo, no aparece en el catalogo</p>
                    </div>

                </div>

                {{-- TAB: PRECIOS --}}
                <div x-show="tab==='precios'" x-cloak class="p-6 max-w-2xl space-y-4">

                    {{-- SECCIÓN MINORISTA --}}
                    <div class="rounded-xl overflow-hidden border border-blue-200">
                        <div class="bg-blue-600 px-4 py-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <p class="text-xs font-bold text-white uppercase tracking-wider">Venta Minorista — cliente individual</p>
                        </div>
                        <div class="p-4 bg-blue-50 grid grid-cols-3 gap-4">
                            <div>
                                <label class="label">Unidad de medida</label>
                                <template x-if="units.length > 0">
                                    <select x-model="form.unit" class="input">
                                        <option value="">Seleccionar</option>
                                        <template x-for="u in units" :key="u">
                                            <option :value="u" x-text="u"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="units.length === 0">
                                    <input type="text" x-model="form.unit" class="input" placeholder="Ej: unidad, kg, litro">
                                </template>
                                <p class="text-[10px] text-gray-400 mt-0.5">Ej: unidad, kg, m2</p>
                            </div>
                            <div>
                                <label class="label">Precio unitario *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                    <input type="number" x-model="form.price" step="0.01" min="0" class="input pl-10" placeholder="0.00">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Precio final al cliente</p>
                            </div>
                            <div>
                                <label class="label">Precio anterior (tachado)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                    <input type="number" x-model="form.compare_price" step="0.01" min="0" class="input pl-10" placeholder="0.00">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Se muestra <s>tachado</s> como oferta</p>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN MAYORISTA --}}
                    <div class="rounded-xl overflow-hidden border border-green-200">
                        <div class="bg-green-700 px-4 py-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="text-xs font-bold text-white uppercase tracking-wider">Venta Mayorista — compra al por mayor</p>
                        </div>
                        <div class="p-4 bg-green-50 grid grid-cols-4 gap-4">
                            <div>
                                <label class="label">Unidad mayorista</label>
                                <input type="text" x-model="form.wholesale_unit" class="input" placeholder="Ej: caja, saco, docena">
                                <p class="text-[10px] text-gray-400 mt-0.5">Ej: caja x12, saco 25kg</p>
                            </div>
                            <div>
                                <label class="label">Precio x mayor</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                    <input type="number" x-model="form.wholesale_price" step="0.01" min="0" class="input pl-10" placeholder="0.00">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Precio al por mayor</p>
                            </div>
                            <div>
                                <label class="label">P. venta mayorista</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                    <input type="number" x-model="form.compare_price" step="0.01" min="0" class="input pl-10 bg-gray-100" placeholder="0.00" readonly>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Igual al precio anterior</p>
                            </div>
                            <div>
                                <label class="label">Cantidad mínima</label>
                                <input type="number" x-model="form.wholesale_min_qty" min="1" step="1" class="input" placeholder="Ej: 25">
                                <p class="text-[10px] text-gray-400 mt-0.5">Unidades para activar precio mayor</p>
                            </div>
                        </div>
                    </div>

                    {{-- COSTO --}}
                    <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Costo interno (privado)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="label">Costo de compra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-medium">{{ $currency }}</span>
                                    <input type="number" x-model="form.cost" step="0.01" min="0" class="input pl-10" placeholder="0.00">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Lo que te cuesta a ti — no se muestra al cliente</p>
                            </div>
                        </div>
                    </div>

                    {{-- Analisis de rentabilidad --}}
                    <div x-show="form.price || form.cost" class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide">Analisis de rentabilidad</p>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-white rounded-lg p-3 border border-gray-100">
                                <p class="text-[10px] text-gray-400 mb-1">Ganancia</p>
                                <p class="font-bold text-gray-800 text-sm"
                                   x-text="(form.price && form.cost) ? '{{ $currency }} ' + (parseFloat(form.price) - parseFloat(form.cost)).toFixed(2) : '—'"></p>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-100">
                                <p class="text-[10px] text-gray-400 mb-1">Margen</p>
                                <p class="font-bold text-sm"
                                   :class="margin >= 30 ? 'text-green-600' : margin >= 15 ? 'text-amber-500' : margin !== null ? 'text-red-500' : 'text-gray-400'"
                                   x-text="margin !== null ? margin + '%' : '—'"></p>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-100">
                                <p class="text-[10px] text-gray-400 mb-1">Descuento</p>
                                <p class="font-bold text-indigo-600 text-sm"
                                   x-text="(form.compare_price && parseFloat(form.compare_price) > parseFloat(form.price))
                                       ? '-' + (((parseFloat(form.compare_price)-parseFloat(form.price))/parseFloat(form.compare_price))*100).toFixed(0) + '%'
                                       : '—'"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Impuesto --}}
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Impuesto / IGV</p>
                                <p class="text-xs text-gray-400">El precio incluye impuesto?</p>
                            </div>
                            <button @click="form.has_tax = !form.has_tax"
                                    :class="form.has_tax ? 'bg-indigo-600' : 'bg-gray-200'"
                                    class="relative w-11 h-6 rounded-full transition-colors flex-shrink-0">
                                <span :class="form.has_tax ? 'translate-x-6' : 'translate-x-1'"
                                      class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform"></span>
                            </button>
                        </div>
                        <div x-show="form.has_tax" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label text-xs">Tasa de impuesto (%)</label>
                                <template x-if="taxes.length > 0">
                                    <select x-model.number="form.tax_rate" class="input text-sm">
                                        <template x-for="t in taxes" :key="t.label">
                                            <option :value="t.rate" x-text="t.label + ' (' + t.rate + '%)'"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="taxes.length === 0">
                                    <input type="number" x-model.number="form.tax_rate" class="input text-sm" placeholder="18" min="0" max="100">
                                </template>
                            </div>
                            <div class="flex flex-col justify-end">
                                <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-center">
                                    <p class="text-[10px] text-indigo-500">Precio con IGV</p>
                                    <p class="font-bold text-indigo-700 text-sm" x-text="priceWithTax ? '{{ $currency }} ' + priceWithTax : '—'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TAB: INVENTARIO --}}
                <div x-show="tab==='inventario'" x-cloak class="p-6 max-w-2xl space-y-5">

                    <div>
                        <label class="label">Stock actual</label>
                        <div class="flex items-center gap-3 mt-1">
                            <button @click="form.stock = Math.max(0, (parseInt(form.stock)||0) - 1)"
                                    class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg transition border border-gray-200">-</button>
                            <input type="number" x-model.number="form.stock" min="0"
                                   class="input text-center w-28 font-mono font-bold text-xl py-2">
                            <button @click="form.stock = (parseInt(form.stock)||0) + 1"
                                    class="w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center text-white font-bold text-lg transition">+</button>
                            <span class="text-sm text-gray-400" x-text="form.unit ? 'en ' + form.unit + 's' : 'unidades'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Stock minimo (alerta)</label>
                            <input type="number" x-model.number="form.stock_min" min="0" class="input" placeholder="5">
                            <p class="text-[10px] text-gray-400 mt-0.5">Alerta cuando baje de este nivel</p>
                        </div>
                        <div>
                            <label class="label">Stock maximo</label>
                            <input type="number" x-model.number="form.stock_max" min="0" class="input" placeholder="100">
                            <p class="text-[10px] text-gray-400 mt-0.5">Capacidad maxima de almacenaje</p>
                        </div>
                    </div>

                    {{-- Barra de stock --}}
                    <div x-show="form.stock_max > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span>0</span>
                            <span x-text="'Stock: ' + (form.stock||0) + ' / ' + (form.stock_max||0)"></span>
                            <span x-text="form.stock_max"></span>
                        </div>
                        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                            <div :style="'width:' + Math.min(100, ((form.stock||0) / (form.stock_max||1)) * 100) + '%'"
                                 :class="((form.stock||0)/(form.stock_max||1)) > 0.5 ? 'bg-green-500' : ((form.stock||0)/(form.stock_max||1)) > 0.2 ? 'bg-amber-400' : 'bg-red-500'"
                                 class="h-full rounded-full transition-all duration-300"></div>
                        </div>
                    </div>

                    {{-- Badge estado --}}
                    <div :class="{
                            'bg-red-50 border-red-200':     stockStatus.color === 'red',
                            'bg-amber-50 border-amber-200': stockStatus.color === 'amber',
                            'bg-green-50 border-green-200': stockStatus.color === 'green'
                         }"
                         class="rounded-xl p-4 border flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0"
                             :class="{
                                 'text-red-500':   stockStatus.color === 'red',
                                 'text-amber-500': stockStatus.color === 'amber',
                                 'text-green-500': stockStatus.color === 'green'
                             }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-semibold"
                           :class="{
                               'text-red-700':   stockStatus.color === 'red',
                               'text-amber-700': stockStatus.color === 'amber',
                               'text-green-700': stockStatus.color === 'green'
                           }"
                           x-text="stockStatus.label"></p>
                    </div>

                    {{-- Ubicacion + Proveedor --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label flex items-center justify-between">
                                Ubicacion en almacen
                                <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <template x-if="locations.length > 0">
                                <div>
                                    <select x-model="form.location" class="input">
                                        <option value="">Sin ubicacion</option>
                                        <template x-for="l in locations" :key="l">
                                            <option :value="l" x-text="l"></option>
                                        </template>
                                    </select>
                                    <p class="text-[10px] text-green-600 mt-0.5">&#10003; Viene del catalogo de configuracion</p>
                                </div>
                            </template>
                            <template x-if="locations.length === 0">
                                <input type="text" x-model="form.location" class="input font-mono text-sm" placeholder="Ej: A-12, Bodega 2">
                            </template>
                        </div>
                        <div>
                            <label class="label flex items-center justify-between">
                                Proveedor
                                <a href="{{ route('catalogs.index') }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <template x-if="suppliers.length > 0">
                                <div>
                                    <select x-model="form.supplier" class="input">
                                        <option value="">Sin proveedor</option>
                                        <template x-for="s in suppliers" :key="s">
                                            <option :value="s" x-text="s"></option>
                                        </template>
                                    </select>
                                    <p class="text-[10px] text-green-600 mt-0.5">&#10003; Viene del catalogo de configuracion</p>
                                </div>
                            </template>
                            <template x-if="suppliers.length === 0">
                                <input type="text" x-model="form.supplier" class="input text-sm" placeholder="Ej: Proveedor A">
                            </template>
                        </div>
                    </div>

                    @if($allCatalogs === 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">Configura tus catalogos</p>
                            <p class="text-xs text-blue-600 mt-0.5">
                                Crea listas de <strong>proveedores</strong>, <strong>ubicaciones</strong> e <strong>impuestos</strong> en
                                <a href="{{ route('catalogs.index') }}" class="underline font-semibold">Catalogos de configuracion</a>
                                y apareceran como desplegables aqui automaticamente.
                            </p>
                        </div>
                    </div>
                    @endif

                </div>

                {{-- TAB: IMAGENES --}}
                <div x-show="tab==='imagenes'" x-cloak class="p-5 space-y-4"
                     x-data="{ uploading: false, imgError: '' }">

                    {{-- Grid de imágenes actuales --}}
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        <template x-for="img in (selected?.images || [])" :key="img.id">
                            <div class="group relative aspect-square rounded-xl overflow-hidden border-2 transition"
                                 :class="img.is_main ? 'border-indigo-500' : 'border-gray-200'">
                                <img :src="img.url" class="w-full h-full object-cover">
                                {{-- Badge principal --}}
                                <div x-show="img.is_main"
                                     class="absolute top-1.5 left-1.5 bg-indigo-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md">
                                    Principal
                                </div>
                                {{-- Acciones hover --}}
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                    <button x-show="!img.is_main"
                                            @click="
                                                await fetch('{{ url('/bixoadmin/products') }}/' + selected.id + '/images/' + img.id + '/main', {
                                                    method:'PATCH', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                                                });
                                                selected.images.forEach(i => i.is_main = false);
                                                img.is_main = true;
                                            "
                                            class="bg-white text-indigo-700 text-[10px] font-bold px-2 py-1 rounded-lg hover:bg-indigo-50 transition"
                                            title="Marcar como principal">⭐</button>
                                    <button @click="
                                                if(!confirm('¿Eliminar imagen?')) return;
                                                await fetch('{{ url('/bixoadmin/products') }}/' + selected.id + '/images/' + img.id, {
                                                    method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                                                });
                                                selected.images = selected.images.filter(i => i.id !== img.id);
                                            "
                                            class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:bg-red-600 transition"
                                            title="Eliminar">✕</button>
                                </div>
                            </div>
                        </template>

                        {{-- Botón agregar --}}
                        <label class="aspect-square rounded-xl border-2 border-dashed border-gray-300 hover:border-indigo-400
                                      hover:bg-indigo-50 transition cursor-pointer flex flex-col items-center justify-center gap-1.5 group">
                            <svg class="w-7 h-7 text-gray-300 group-hover:text-indigo-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="text-[10px] text-gray-400 group-hover:text-indigo-500 font-medium">Agregar</span>
                            <input type="file" accept="image/*" multiple class="hidden"
                                   @change="
                                        uploading = true; imgError = '';
                                        const files = Array.from($event.target.files);
                                        for (const file of files) {
                                            const fd = new FormData();
                                            fd.append('image', file);
                                            fd.append('_token', '{{ csrf_token() }}');
                                            const res = await fetch('{{ url('/bixoadmin/products') }}/' + selected.id + '/images', { method:'POST', body: fd });
                                            const data = await res.json();
                                            if (data.ok) {
                                                if (!selected.images) selected.images = [];
                                                selected.images.push(data.image);
                                            } else { imgError = data.message || 'Error al subir imagen'; }
                                        }
                                        uploading = false;
                                        $event.target.value = '';
                                   ">
                        </label>
                    </div>

                    <div x-show="uploading" class="text-xs text-indigo-600 font-medium">⏳ Subiendo imagen...</div>
                    <div x-show="imgError" x-text="imgError" class="text-xs text-red-600"></div>

                    <p class="text-xs text-gray-400">
                        Clic en ⭐ para marcar como imagen principal · Pasa el cursor sobre una imagen para ver las opciones · Formatos: JPG, PNG, WebP · Máx 4MB por imagen
                    </p>
                </div>

            </div>{{-- /overflow-y-auto --}}

            {{-- Footer --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-gray-100 bg-white flex items-center justify-between">
                <div>
                    <button x-show="!creating && selected" @click="del()"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm text-red-600 border border-red-200 hover:bg-red-50 rounded-lg transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="selected=null; creating=false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg transition">
                        Cancelar
                    </button>
                    <button @click="save()"
                            :disabled="saving || !form.name || !form.price"
                            class="flex items-center gap-2 px-5 py-2 btn-primary text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
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
