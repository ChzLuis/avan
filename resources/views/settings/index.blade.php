<x-app-layout>
<x-slot name="slot">

@php
    $s    = request('s', 'general');
    $selP = $project;
    $isOwnerOrSuper = auth()->user()?->is_superadmin || ($project && $project->owner_id === auth()->id());
@endphp

{{-- Editor de Flujo: pantalla completa e independiente (fuera del x-data de settings
     para evitar conflictos de scope con 'selected', 'projects', etc.) --}}
@if($s === 'flujo')
    @include('settings.partials.flow-editor', ['selP' => $selP])
@else

<div class="flex flex-col h-full w-full overflow-hidden"
     x-data="{
         projects: {{ Illuminate\Support\Js::from($projects->map(fn($p) => [
             'id'        => $p->id,
             'name'      => $p->name,
             'category'  => $p->category ?? '',
             'is_active' => $p->is_active,
             'slug'      => $p->slug ?? '',
             'logo_url'  => $p->logo_url ?? '',
         ])) }},

         selected: {{ $selP->id }},
         creating: false,
         tab: '{{ $s }}',
         saving: false,

         form: {
             name: '', category: '', description: '',
             phone: '', whatsapp: '', address: '',
             currency: 'PEN', country: 'PE',
             email: '', ruc: '',
             slug: '',
             facebook_url: '', instagram_url: '', tiktok_url: '',
             youtube_url: '', twitter_url: '', linkedin_url: '',
             seo_title: '', seo_description: '', seo_keywords: '',
         },

         get filteredProjects() {
             const q = this.search.toLowerCase();
             let list = this.projects;
             if (this.filterStatus === 'activo')   list = list.filter(p => p.is_active);
             if (this.filterStatus === 'inactivo') list = list.filter(p => !p.is_active);
             if (q) list = list.filter(p => p.name.toLowerCase().includes(q));
             return list;
         },

         mv: '{{ request()->has('p') ? 'detail' : 'list' }}',
         search: '',
         filterStatus: '{{ request('filter','') }}',

         selectProject(id) {
             this.selected = id;
             this.creating = false;
             this.tab = 'datos';
             // recarga para obtener settings del servidor
             window.location.href = '{{ route('settings') }}?p=' + id + '&s=datos';
         },

         openNew() {
             this.creating = true;
             this.selected = null;
             this.tab = 'datos';
             this.form = {
                 name: '', category: '', description: '',
                 phone: '', whatsapp: '', address: '',
                 currency: 'PEN', country: 'PE',
                 email: '', ruc: '', slug: '',
                 facebook_url: '', instagram_url: '', tiktok_url: '',
                 youtube_url: '', twitter_url: '', linkedin_url: '',
                 seo_title: '', seo_description: '', seo_keywords: '',
             };
         },

         async createProject() {
             if (!this.form.name.trim()) { alert('El nombre es requerido'); return; }
             this.saving = true;
             const res = await fetch('{{ route('projects.store') }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     'Accept': 'application/json',
                 },
                 body: JSON.stringify(this.form),
             });
             const data = await res.json();
             this.saving = false;
             if (data.project) {
                 this.projects.push({ ...data.project, logo_url: '' });
                 // navegar al nuevo proyecto con settings completas
                 window.location.href = '{{ route('settings') }}?p=' + data.project.id + '&s=datos&_new=1';
             }
         },
     }">

<style>
/* ===== BX — capa visual de la pantalla Negocios (autocontenida) ===== */
.bx-top{display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1.5rem; border-bottom:1px solid #E6EDEC; background:#fff; flex-shrink:0;}
.bx-top h1{font-size:1.05rem; font-weight:700; color:#0A1826; margin:0; letter-spacing:-.01em;}
.bx-top p{font-size:.76rem; color:#7C8B92; margin:.15rem 0 0;}
.bx-new{display:inline-flex; align-items:center; gap:.45rem; padding:.55rem 1rem; border:0; border-radius:10px; cursor:pointer;
    font-size:.85rem; font-weight:600; color:#04231C; background:linear-gradient(135deg,#17B890,#0FA8A0);
    box-shadow:0 10px 22px -12px rgba(23,184,144,.8); transition:filter .15s, transform .12s;}
.bx-new:hover{filter:brightness(1.04);} .bx-new:active{transform:translateY(1px);}

.bx-side{border-right:1px solid #E6EDEC; background:#fff; flex-shrink:0;}
.bx-tools{padding:.75rem .75rem .5rem; border-bottom:1px solid #EFF4F3;}
.bx-search{display:flex; align-items:center; gap:.5rem; background:#F4F9F8; border:1px solid #E2ECEA; border-radius:10px; padding:.5rem .7rem;}
.bx-search:focus-within{border-color:#17B890; box-shadow:0 0 0 3px rgba(23,184,144,.14); background:#fff;}
.bx-search input{background:transparent; border:0; outline:none; font-size:.85rem; color:#0A1826; flex:1; min-width:0;}
.bx-search input::placeholder{color:#9DACB2;}
.bx-filters{display:flex; gap:.3rem; margin-top:.6rem;}
.bx-chip{flex:1; display:inline-flex; align-items:center; justify-content:center; gap:.3rem; padding:.35rem .4rem; border:1px solid transparent;
    border-radius:8px; background:#F1F6F5; color:#66787F; font-size:.74rem; font-weight:600; cursor:pointer; transition:background .15s, color .15s;}
.bx-chip:hover{background:#E7F1EF; color:#33474F;}
.bx-chip.on{background:#0A1826; color:#fff;}
.bx-chip b{font-weight:700; opacity:.65; font-variant-numeric:tabular-nums;}
.bx-chip.on b{opacity:.8;}

.bx-item{width:100%; display:flex; align-items:center; gap:.7rem; padding:.65rem .9rem; background:none; border:0; border-left:3px solid transparent; cursor:pointer; text-align:left; transition:background .12s;}
.bx-item:hover{background:#F7FBFA;}
.bx-item.on{background:#EFFAF6; border-left-color:#17B890;}
.bx-av{width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.72rem; font-weight:700; background:#EEF3F2; color:#66787F; overflow:hidden;}
.bx-item.on .bx-av{background:#17B890; color:#fff;}
.bx-av img{width:100%; height:100%; object-fit:cover;}
.bx-tx{flex:1; min-width:0;}
.bx-tx .n{font-size:.86rem; font-weight:600; color:#16242B; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
.bx-tx .s{font-size:.72rem; color:#8B9AA1; margin:.1rem 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-variant-numeric:tabular-nums;}
.bx-dot{width:7px; height:7px; border-radius:50%; flex-shrink:0;}
.bx-dot.ok{background:#17B890; box-shadow:0 0 0 3px rgba(23,184,144,.16);}
.bx-dot.off{background:#CBD5D3;}
.bx-add{width:100%; display:flex; align-items:center; gap:.7rem; padding:.65rem .9rem; background:none; border:0; border-left:3px solid transparent; cursor:pointer; text-align:left;}
.bx-add:hover{background:#F0FAF7;}
.bx-add.on{background:#EFFAF6; border-left-color:#17B890;}
.bx-add .ic{width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; background:#E3F6EF; color:#0FA8A0; flex-shrink:0;}
.bx-add .n{font-size:.86rem; font-weight:600; color:#0FA8A0; margin:0;}
.bx-add .s{font-size:.72rem; color:#8B9AA1; margin:.1rem 0 0;}

/* --- Header del negocio --- */
.bx-head{display:flex; align-items:center; gap:.85rem; padding:1rem 1.5rem; border-bottom:1px solid #E6EDEC; flex-shrink:0; flex-wrap:wrap;}
.bx-head-av{width:42px; height:42px; border-radius:11px; background:#0A1826; color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.85rem; flex-shrink:0; overflow:hidden;}
.bx-head-av img{width:100%; height:100%; object-fit:cover;}
.bx-head-tx{flex:1; min-width:0;}
.bx-head-tx h2{font-size:1rem; font-weight:700; color:#0A1826; margin:0; letter-spacing:-.01em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
.bx-head-tx p{font-size:.76rem; color:#8B9AA1; margin:.18rem 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
.bx-mono{font-variant-numeric:tabular-nums;}
.bx-sep{margin:0 .35rem; opacity:.6;}
.bx-domain{display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .65rem; border-radius:8px; text-decoration:none;
    background:#F1F6F5; border:1px solid #E2ECEA; color:#38505A; font-size:.76rem; font-weight:600; max-width:16rem; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;}
.bx-domain:hover{border-color:#17B890; color:#0FA8A0; background:#EFFAF6;}
.bx-badge{display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; flex-shrink:0;}
.bx-badge i{width:6px; height:6px; border-radius:50%; display:block;}
.bx-badge.ok{background:#E6F7F1; color:#0B7A5E;} .bx-badge.ok i{background:#17B890;}
.bx-badge.off{background:#F1F4F4; color:#7C8B92;} .bx-badge.off i{background:#B6C3C2;}

/* --- Tabs --- */
.bx-tabs{display:flex; gap:.2rem; padding:0 1.5rem; border-bottom:1px solid #E6EDEC; background:#fff; flex-shrink:0; overflow-x:auto;}
.bx-tab{padding:.7rem .85rem; font-size:.85rem; color:#7C8B92; text-decoration:none; white-space:nowrap; border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .15s, border-color .15s;}
.bx-tab:hover{color:#16242B;}
.bx-tab.on{color:#0A1826; font-weight:700; border-bottom-color:#17B890;}

/* --- Formulario por secciones --- */
.bx-form{max-width:62rem; display:flex; flex-direction:column; gap:1.1rem;}
.bx-sec{border:1px solid #E6EDEC; border-radius:14px; background:#fff; padding:1.1rem 1.25rem 1.25rem;}
.bx-sec-h{margin-bottom:.9rem; padding-bottom:.7rem; border-bottom:1px solid #F0F5F4;}
.bx-sec-h h3{font-size:.9rem; font-weight:700; color:#0A1826; margin:0; display:flex; align-items:center; gap:.5rem;}
.bx-sec-h p{font-size:.76rem; color:#8B9AA1; margin:.2rem 0 0;}
.bx-tag{font-size:.62rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:#EDF1F7; color:#5B6B84; padding:.15rem .4rem; border-radius:5px;}
.bx-actions{display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; padding-bottom:.5rem;}
</style>

{{-- TOP BAR (oculto en la pantalla dedicada de Flujo de estados) --}}
@if($s !== 'flujo')
<div class="bx-top">
    <div>
        <h1>Negocios</h1>
        <p x-text="projects.length + (projects.length === 1 ? ' negocio registrado' : ' negocios registrados')"></p>
    </div>
    @if($isOwnerOrSuper)
    <button @click="openNew()" class="bx-new">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo negocio
    </button>
    @endif
</div>
@endif

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden" {{-- mv is inherited from outer x-data --}}
     x-init="{{ !$isOwnerOrSuper ? 'mv = \'detail\'' : '' }}">

{{-- ─── LISTA CENTRAL (oculta por completo en pantalla de Flujo de estados) ─── --}}
@if($s !== 'flujo')
<div class="bx-side"
     :class="mv === 'detail' ? 'hidden md:flex md:flex-col md:w-80' : 'flex flex-col w-full md:w-80'">

    {{-- Búsqueda + filtros de estado (antes eran iconos sueltos sin etiqueta) --}}
    <div class="bx-tools flex-shrink-0">
        <div class="bx-search">
            <svg width="15" height="15" style="color:#9DACB2; flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Buscar negocio...">
            <button x-show="search" @click="search=''" style="background:none;border:0;cursor:pointer;color:#9DACB2;display:flex">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @if($isOwnerOrSuper)
        <div class="bx-filters">
            <button class="bx-chip" :class="filterStatus==='' ? 'on' : ''" @click="filterStatus=''">
                Todos <b x-text="projects.length"></b>
            </button>
            <button class="bx-chip" :class="filterStatus==='activo' ? 'on' : ''" @click="filterStatus='activo'">
                Activos <b x-text="projects.filter(p =&gt; p.is_active).length"></b>
            </button>
            <button class="bx-chip" :class="filterStatus==='inactivo' ? 'on' : ''" @click="filterStatus='inactivo'">
                Pausados <b x-text="projects.filter(p =&gt; !p.is_active).length"></b>
            </button>
        </div>
        @endif
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto">

        {{-- Nuevo --}}
        @if($isOwnerOrSuper)
        <button @click="openNew(); mv = 'detail'" class="bx-add" :class="creating ? 'on' : ''">
            <span class="ic">
                <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </span>
            <span class="bx-tx"><p class="n">Nuevo negocio</p><p class="s">Crear desde cero</p></span>
        </button>
        @endif

        <template x-for="p in filteredProjects" :key="p.id">
            <button @click="selectProject(p.id); mv = 'detail'" class="bx-item"
                    :class="!creating && selected === p.id ? 'on' : ''">
                <span class="bx-av">
                    <img x-show="p.logo_url" :src="p.logo_url" alt="">
                    <span x-show="!p.logo_url" x-text="p.name.substring(0,2).toUpperCase()"></span>
                </span>
                <span class="bx-tx">
                    <p class="n" x-text="p.name"></p>
                    <p class="s" x-text="'/' + (p.slug || '') + (p.category ? ' · ' + p.category : '')"></p>
                </span>
                <span class="bx-dot" :class="p.is_active ? 'ok' : 'off'"
                      :title="p.is_active ? 'Activo' : 'Pausado'"></span>
            </button>
        </template>

        <div x-show="filteredProjects.length === 0" style="padding:2.5rem 1rem; text-align:center; font-size:.84rem; color:#9DACB2;">
            Sin resultados
        </div>
    </div>
</div>
@endif

{{-- ─── PANEL DETALLE ──────────────────────────────────────────────── --}}
<div class="{{ $isOwnerOrSuper ? 'overflow-hidden bg-white' : 'overflow-hidden bg-gray-50 flex-1' }}"
     :class="mv === 'detail' ? 'flex flex-col flex-1' : 'hidden md:flex md:flex-col md:flex-1'">
@if(!$isOwnerOrSuper)<div class="w-full max-w-3xl mx-auto bg-white shadow-sm flex flex-col flex-1 overflow-y-auto">@endif

    {{-- Botón volver (solo mobile) --}}
    <button @click="mv = 'list'" type="button"
            class="md:hidden flex items-center gap-2 px-4 py-3 text-sm text-indigo-600 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a negocios
    </button>

    {{-- Estado vacío --}}
    <template x-if="!creating && !selected">
        <div class="flex-1 flex flex-col items-center justify-center text-center p-10 text-gray-300">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un negocio</p>
            <p class="text-sm text-gray-300 mt-1">o crea uno nuevo</p>
        </div>
    </template>

    {{-- ══ CREAR NUEVO ══ --}}
    <template x-if="creating">
        <div class="flex flex-col h-full">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3 flex-shrink-0">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800 text-base">Nuevo negocio</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Completa la información básica</p>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 bg-white flex-shrink-0">
                <button @click="tab='datos'"
                        :class="tab==='datos' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-transparent'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition border-b-2">Datos</button>
                <button @click="tab='adicionales'"
                        :class="tab==='adicionales' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700 border-transparent'"
                        class="px-4 py-3 text-sm whitespace-nowrap transition border-b-2">Adicionales</button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto p-6">

                {{-- TAB: Datos --}}
                <div x-show="tab==='datos'" class="space-y-4 max-w-2xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="label">Nombre del negocio <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.name" class="input mt-1" placeholder="Mi Tienda, Barbería Juan...">
                        </div>
                        <div>
                            <label class="label">Categoría / Rubro</label>
                            <select x-model="form.category" class="input mt-1">
                                <option value="">— Selecciona tu rubro —</option>
                                <option value="restaurante">🍽️ Restaurante / Cafetería</option>
                                <option value="peluqueria">✂️ Peluquería / Spa / Salón</option>
                                <option value="clinica">🏥 Clínica / Consultorio</option>
                                <option value="retail">🛍️ Tienda / Retail</option>
                                <option value="whatsapp">🤖 Negocio por WhatsApp</option>
                                <option value="farmacia">💊 Farmacia / Botica</option>
                                <option value="veterinaria">🐾 Veterinaria / Pet Shop</option>
                                <option value="taller">🔧 Taller Mecánico / Técnico</option>
                                <option value="gimnasio">🏋️ Gimnasio / Fitness</option>
                                <option value="inmobiliaria">🏘️ Inmobiliaria / Alquileres</option>
                                <option value="educacion">📚 Educación / Academia</option>
                                <option value="otro">🏢 Otro tipo de negocio</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Moneda</label>
                            <select x-model="form.currency" class="input mt-1">
                                <option value="PEN">S/ Sol peruano</option>
                                <option value="USD">$ Dólar</option>
                                <option value="COP">$ Peso colombiano</option>
                                <option value="MXN">$ Peso mexicano</option>
                                <option value="ARS">$ Peso argentino</option>
                                <option value="CLP">$ Peso chileno</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">País</label>
                            <select x-model="form.country" class="input mt-1">
                                <option value="PE">Perú</option>
                                <option value="CO">Colombia</option>
                                <option value="MX">México</option>
                                <option value="AR">Argentina</option>
                                <option value="CL">Chile</option>
                                <option value="EC">Ecuador</option>
                                <option value="BO">Bolivia</option>
                                <option value="UY">Uruguay</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Teléfono</label>
                            <input type="text" x-model="form.phone" class="input mt-1" placeholder="+51 999 999 999">
                        </div>
                        <div>
                            <label class="label">WhatsApp</label>
                            <input type="text" x-model="form.whatsapp" class="input mt-1" placeholder="51999999999">
                        </div>
                        <div>
                            <label class="label">Email de contacto</label>
                            <input type="email" x-model="form.email" class="input mt-1" placeholder="contacto@negocio.com">
                        </div>
                        <div>
                            <label class="label">RUC</label>
                            <input type="text" x-model="form.ruc" class="input mt-1" placeholder="20123456789">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Dirección</label>
                            <input type="text" x-model="form.address" class="input mt-1" placeholder="Av. Principal 123, Lima">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Descripción</label>
                            <textarea x-model="form.description" class="input mt-1" rows="3" placeholder="Descripción breve del negocio"></textarea>
                        </div>
                    </div>
                </div>

                {{-- TAB: Adicionales (Redes) --}}
                <div x-show="tab==='adicionales'" class="space-y-3 max-w-2xl">
                    <p class="text-sm text-gray-500 mb-4">Opcional. Puedes completar esto después.</p>
                    <template x-for="field in [
                        { key:'facebook_url',  label:'Facebook',    color:'#1877F2', ph:'https://facebook.com/tunegocio' },
                        { key:'instagram_url', label:'Instagram',   color:'#E1306C', ph:'https://instagram.com/tunegocio' },
                        { key:'tiktok_url',    label:'TikTok',      color:'#000000', ph:'https://tiktok.com/@tunegocio' },
                        { key:'youtube_url',   label:'YouTube',     color:'#FF0000', ph:'https://youtube.com/@tunegocio' },
                        { key:'twitter_url',   label:'X / Twitter', color:'#000000', ph:'https://x.com/tunegocio' },
                        { key:'linkedin_url',  label:'LinkedIn',    color:'#0A66C2', ph:'https://linkedin.com/company/...' },
                    ]" :key="field.key">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background:'+field.color"></span>
                            <label class="label w-28 flex-shrink-0 !mb-0" x-text="field.label"></label>
                            <input type="url" x-model="form[field.key]" class="input flex-1 !mt-0" :placeholder="field.ph">
                        </div>
                    </template>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3 flex-shrink-0">
                <button @click="createProject()" :disabled="saving"
                        class="btn-primary disabled:opacity-60">
                    <span x-text="saving ? 'Creando...' : 'Crear negocio'"></span>
                </button>
                <button @click="creating=false; selected={{ $selP->id }}"
                        class="btn-secondary">
                    Cancelar
                </button>
            </div>
        </div>
    </template>

    {{-- ══ EDITAR EXISTENTE ══ --}}
    <template x-if="!creating && selected">
        <div class="flex flex-col h-full">

            {{-- Header del panel (oculto en Flujo de estados: ya hay encabezado propio) --}}
            @if($s !== 'flujo')
            <div class="bx-head">
                <div class="bx-head-av">
                    @if($selP->logo_url)
                        <img src="{{ $selP->logo_url }}" alt="">
                    @else
                        {{ strtoupper(substr($selP->name, 0, 2)) }}
                    @endif
                </div>
                <div class="bx-head-tx">
                    <h2>{{ $selP->name }}</h2>
                    <p>
                        <span class="bx-mono">{{ $selP->slug ? '/'.$selP->slug : 'Sin slug' }}</span>
                        @if($selP->category)<span class="bx-sep">·</span>{{ $selP->category }}@endif
                    </p>
                </div>
                @if($selP->custom_domain)
                <a href="https://{{ $selP->custom_domain }}" target="_blank" rel="noopener" class="bx-domain" title="Abrir tienda">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5M10.172 13.828a4 4 0 010-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5"/>
                    </svg>
                    {{ $selP->custom_domain }}
                </a>
                @endif
                <span class="bx-badge {{ $selP->is_active ? 'ok' : 'off' }}">
                    <i></i>{{ $selP->is_active ? 'Activo' : 'Pausado' }}
                </span>
            </div>
            @endif

            {{-- Tabs (ocultos en la pantalla dedicada de Flujo de estados) --}}
            <div class="bx-tabs {{ $s === 'flujo' ? 'hidden' : '' }}">
                @php
                // 4 pestañas agrupadas por lo que hace el usuario. Cada una
                // acepta ademas sus claves antiguas para no romper enlaces
                // guardados ni los redirect del controlador (?s=datos, ?s=envio...).
                $tabs = [
                    ['k'=>'general',     'l'=>'General',     'alias'=>['datos','adicionales']],
                    ['k'=>'venta',       'l'=>'Venta',       'alias'=>['envio','cupones']],
                    ['k'=>'facturacion', 'l'=>'Facturación', 'alias'=>[]],
                    ['k'=>'whatsapp',    'l'=>'WhatsApp',    'alias'=>[]],
                ];
                @endphp
                @foreach($tabs as $tab)
                <a href="{{ route('settings') }}?p={{ $selP->id }}&s={{ $tab['k'] }}"
                   class="bx-tab {{ ($s === $tab['k'] || in_array($s, $tab['alias'])) ? 'on' : '' }}">{{ $tab['l'] }}</a>
                @endforeach
            </div>

            @if(session('success'))
            <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3500)" x-cloak
                 class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2 flex-shrink-0">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            {{-- Contenido scrollable (sin padding/scroll en el editor de flujo, que es full-screen) --}}
            <div class="flex-1 {{ $s === 'flujo' ? 'overflow-hidden' : 'overflow-y-auto p-6' }}">

            {{-- TAB: Datos --}}
            @if(in_array($s, ['general','datos']))
            <form method="POST" action="{{ route('settings.update') }}" class="bx-form">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selP->id }}">
                <input type="hidden" name="_tab" value="general">

                <div class="bx-sec">
                    <div class="bx-sec-h"><h3>Identidad</h3><p>Cómo se llama el negocio y cómo se le encuentra.</p></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Nombre del negocio <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="input mt-1" required value="{{ old('name', $selP->name) }}">
                    </div>
                    <div>
                        <label class="label">Slug (URL pública)</label>
                        <div class="mt-1 flex items-center rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                            <span class="px-3 text-xs text-gray-400 border-r border-gray-200 py-2.5 bg-white whitespace-nowrap">/</span>
                            <input type="text" name="slug" class="flex-1 px-3 py-2.5 text-sm bg-gray-50 focus:outline-none border-0 min-w-0"
                                   placeholder="mi-negocio" value="{{ old('slug', $selP->slug) }}">
                        </div>
                    </div>
                    <div>
                        <label class="label">Categoría / Rubro</label>
                        <select name="category" class="input mt-1">
                            <option value="">— Selecciona tu rubro —</option>
                            @foreach([
                                'restaurante'  => '🍽️ Restaurante / Cafetería',
                                'peluqueria'   => '✂️ Peluquería / Spa / Salón',
                                'clinica'      => '🏥 Clínica / Consultorio',
                                'retail'       => '🛍️ Tienda / Retail',
                                'whatsapp'     => '🤖 Negocio por WhatsApp',
                                'farmacia'     => '💊 Farmacia / Botica',
                                'veterinaria'  => '🐾 Veterinaria / Pet Shop',
                                'taller'       => '🔧 Taller Mecánico / Técnico',
                                'gimnasio'     => '🏋️ Gimnasio / Fitness',
                                'inmobiliaria' => '🏘️ Inmobiliaria / Alquileres',
                                'educacion'    => '📚 Educación / Academia',
                                'otro'         => '🏢 Otro tipo de negocio',
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ old('category', $selP->category) === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Descripción</label>
                        <textarea name="description" class="input mt-1" rows="2"
                                  placeholder="Descripción breve del negocio">{{ old('description', $selP->description) }}</textarea>
                    </div>
                </div>
                </div>

                @if(auth()->user()->is_superadmin ?? false)
                <div class="bx-sec">
                    <div class="bx-sec-h">
                        <h3>Dominio propio <span class="bx-tag">Superadmin</span></h3>
                        <p>La dirección con la que el cliente publica su tienda.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="label">Dominio personalizado</label>
                        <div class="mt-1 flex items-center rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                            <span class="px-3 text-xs text-gray-400 border-r border-gray-200 py-2.5 bg-white whitespace-nowrap">https://</span>
                            <input type="text" name="custom_domain" class="flex-1 px-3 py-2.5 text-sm bg-gray-50 focus:outline-none border-0 min-w-0"
                                   placeholder="gestion.sunegocio.com" value="{{ old('custom_domain', $selP->custom_domain) }}">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">El cliente apunta su DNS a este servidor. El certificado se emite solo.</p>
                        {{-- Estado real de la publicación. Antes esto fallaba en
                             absoluto silencio: el panel decía "guardado" pasara lo
                             que pasara y el cliente no tenía forma de saber si su
                             dominio estaba sirviendo, esperando al DNS o roto. --}}
                        @if($selP->custom_domain)
                            @php
                                $dEstado = $selP->domain_status;
                                $dMapa = [
                                    'listo'          => ['Funcionando con certificado', 'bg-emerald-50 text-emerald-700 border-emerald-200', '●'],
                                    'dns-pendiente'  => ['Esperando a que el DNS apunte aquí', 'bg-amber-50 text-amber-700 border-amber-200', '◌'],
                                    'cert-pendiente' => ['Emitiendo el certificado', 'bg-amber-50 text-amber-700 border-amber-200', '◌'],
                                    'error'          => ['Con problemas — se reintenta solo', 'bg-red-50 text-red-700 border-red-200', '▲'],
                                ];
                                [$dTxt, $dCls, $dIco] = $dMapa[$dEstado] ?? ['Sin comprobar todavía', 'bg-gray-50 text-gray-500 border-gray-200', '○'];
                            @endphp
                            <div class="mt-2 flex items-center gap-2 text-xs px-2.5 py-1.5 rounded-lg border {{ $dCls }}">
                                <span>{{ $dIco }}</span>
                                <span class="font-semibold">{{ $dTxt }}</span>
                                @if($selP->domain_checked_at)
                                    <span class="opacity-70">· comprobado {{ \Carbon\Carbon::parse($selP->domain_checked_at)->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if($dEstado === 'dns-pendiente')
                                <p class="text-xs text-gray-400 mt-1">Crea un registro <b>A</b> con el nombre del subdominio apuntando a <b>2.24.200.91</b>. Se reintenta cada 10 minutos, no hace falta que vuelvas a guardar.</p>
                            @endif
                        @endif
                    </div>
                    </div>
                </div>
                @endif

                <div class="bx-sec">
                    <div class="bx-sec-h"><h3>Modalidades de venta</h3><p>Enciende solo lo que este negocio necesita; el catálogo se adapta.</p></div>
                    <div class="grid grid-cols-1 gap-4">

                    {{-- ═══ Modalidades de venta ═══
                         Hasta ahora TODA tienda cargaba con los campos de precio
                         mayorista, cantidad mínima y unidad en cada producto,
                         vendiera al por mayor o no. Aquí se decide una vez y el
                         catálogo se adapta. Se sembraron encendidos en los
                         proyectos que ya tenían precios mayoristas cargados, así
                         que ninguna tienda existente cambió. --}}
                    <div class="md:col-span-2">
                        <label class="label">Modalidades de venta</label>
                        <div class="mt-2 space-y-3 rounded-xl border border-gray-200 p-4 bg-gray-50">
                            <div class="flex items-start gap-3">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="hidden" name="feature_mayorista" value="0">
                                    <input type="checkbox" name="feature_mayorista" value="1" class="sr-only peer"
                                           {{ $selP->setting('feature_mayorista') == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm">
                                    <b class="font-medium text-gray-700">Venta mayorista</b>
                                    <span class="block text-xs text-gray-500">Añade precio por volumen, cantidad mínima y unidad a cada producto, y muestra el bloque de precios doble en la tienda. Apágalo si solo vendes al detalle.</span>
                                </span>
                            </div>
                            <div class="flex items-start gap-3">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="hidden" name="feature_revendedores" value="0">
                                    <input type="checkbox" name="feature_revendedores" value="1" class="sr-only peer"
                                           {{ $selP->setting('feature_revendedores') == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm">
                                    <b class="font-medium text-gray-700">Revendedores</b>
                                    <span class="block text-xs text-gray-500">Habilita precios propios por revendedor y su catálogo compartible. Apagado, esos campos no aparecen en ningún producto.</span>
                                </span>
                            </div>
                            <div class="flex items-start gap-3">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="hidden" name="feature_variantes" value="0">
                                    <input type="checkbox" name="feature_variantes" value="1" class="sr-only peer"
                                           {{ $selP->setting('feature_variantes') == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm">
                                    <b class="font-medium text-gray-700">Tallas / variantes</b>
                                    <span class="block text-xs text-gray-500">Para ropa, calzado o productos que se venden en varias presentaciones. Apagado, el campo no aparece en ningún producto. Si ya tienes productos con tallas cargadas, se muestra igual para que puedas editarlas.</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    </div>
                </div>

                <div class="bx-sec">
                    <div class="bx-sec-h"><h3>Contacto</h3><p>Los datos con los que el cliente te escribe o te ubica.</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Teléfono</label>
                        <input type="text" name="phone" class="input mt-1" placeholder="+51 999 999 999"
                               value="{{ old('phone', $selP->phone) }}">
                    </div>
                    <div>
                        <label class="label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="input mt-1" placeholder="51999999999"
                               value="{{ old('whatsapp', $selP->whatsapp) }}">
                    </div>
                    <div>
                        <label class="label">Email de contacto</label>
                        <input type="email" name="email" class="input mt-1" placeholder="contacto@negocio.com"
                               value="{{ old('email', $selP->setting('email')) }}">
                    </div>
                    <div>
                        <label class="label">Dirección</label>
                        <input type="text" name="address" class="input mt-1" placeholder="Av. Principal 123, Lima"
                               value="{{ old('address', $selP->address) }}">
                    </div>
                    </div>
                </div>

                <div class="bx-sec">
                    <div class="bx-sec-h"><h3>Fiscal y regional</h3><p>Moneda, país y datos tributarios del negocio.</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Moneda</label>
                        <select name="currency" class="input mt-1">
                            @foreach(['PEN'=>'S/ Sol peruano','USD'=>'$ Dólar','COP'=>'$ Peso colombiano','MXN'=>'$ Peso mexicano','ARS'=>'$ Peso argentino','CLP'=>'$ Peso chileno'] as $code => $label)
                            <option value="{{ $code }}" {{ $selP->setting('currency','PEN') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">País</label>
                        <select name="country" class="input mt-1">
                            @foreach(['PE'=>'Perú','CO'=>'Colombia','MX'=>'México','AR'=>'Argentina','CL'=>'Chile','EC'=>'Ecuador','BO'=>'Bolivia','UY'=>'Uruguay'] as $code => $cname)
                            <option value="{{ $code }}" {{ $selP->setting('country','PE') === $code ? 'selected' : '' }}>{{ $cname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">RUC</label>
                        <input type="text" name="ruc" class="input mt-1" placeholder="20123456789"
                               value="{{ old('ruc', $selP->setting('ruc')) }}">
                    </div>
                    </div>
                </div>

                <div class="bx-actions">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                    <button type="button"
                            onclick="if(confirm('¿Eliminar este negocio? Esta acción no se puede deshacer.')) { fetch('/projects/{{ $selP->id }}', { method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(r => r.ok ? window.location.href='/bixoadmin' : alert('Error al eliminar')); }"
                            class="text-sm text-red-600 border border-red-200 hover:bg-red-50 px-4 py-2 rounded-lg transition font-medium">
                        🗑 Eliminar negocio
                    </button>
                </div>
            </form>
            @endif

            {{-- TAB: Adicionales (Redes) --}}
            @if(in_array($s, ['general','adicionales']))
            <form method="POST" action="{{ route('settings.update') }}" class="bx-form">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selP->id }}">
                <input type="hidden" name="_tab" value="general">
                <div class="bx-sec">
                <div class="bx-sec-h"><h3>Redes sociales</h3><p>Aparecen en el catálogo público de la tienda.</p></div>
                @php
                $socialFields = [
                    ['key'=>'facebook_url',  'label'=>'Facebook',    'placeholder'=>'https://facebook.com/tunegocio',   'color'=>'#1877F2'],
                    ['key'=>'instagram_url', 'label'=>'Instagram',   'placeholder'=>'https://instagram.com/tunegocio',  'color'=>'#E1306C'],
                    ['key'=>'tiktok_url',    'label'=>'TikTok',      'placeholder'=>'https://tiktok.com/@tunegocio',    'color'=>'#000000'],
                    ['key'=>'youtube_url',   'label'=>'YouTube',     'placeholder'=>'https://youtube.com/@tunegocio',   'color'=>'#FF0000'],
                    ['key'=>'twitter_url',   'label'=>'X / Twitter', 'placeholder'=>'https://x.com/tunegocio',          'color'=>'#000000'],
                    ['key'=>'linkedin_url',  'label'=>'LinkedIn',    'placeholder'=>'https://linkedin.com/company/...', 'color'=>'#0A66C2'],
                ];
                @endphp
                <div class="space-y-3">
                @foreach($socialFields as $field)
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $field['color'] }}"></span>
                    <label class="label w-28 flex-shrink-0 !mb-0">{{ $field['label'] }}</label>
                    <input type="url" name="{{ $field['key'] }}" class="input flex-1 !mt-0"
                           placeholder="{{ $field['placeholder'] }}"
                           value="{{ old($field['key'], $selP->setting($field['key'])) }}">
                </div>
                @endforeach
                </div>
                </div>
                <div class="bx-actions">
                    <button type="submit" class="btn-primary">Guardar redes</button>
                </div>
            </form>
            @endif

            {{-- TAB: SEO --}}
            @if($s === 'seo')
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-5 max-w-2xl">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selP->id }}">
                <input type="hidden" name="_tab" value="general">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    <p class="font-semibold mb-1">¿Por qué es importante?</p>
                    <p class="text-xs text-blue-600 leading-relaxed">
                        Cuando alguien busca "<em>{{ $selP->name }} productos</em>" en Google,
                        un buen SEO hace que tu catálogo aparezca en los primeros resultados.
                    </p>
                </div>
                <div>
                    <label class="label">Título SEO</label>
                    <input type="text" name="seo_title" class="input mt-1"
                           placeholder="Ej: {{ $selP->name }} — Catálogo Online"
                           value="{{ old('seo_title', $selP->setting('seo_title')) }}" maxlength="70">
                    <p class="text-xs text-gray-400 mt-1">Máximo 60–70 caracteres.</p>
                </div>
                <div>
                    <label class="label">Meta description</label>
                    <textarea name="seo_description" class="input mt-1" rows="3"
                              placeholder="Ej: Explora el catálogo de {{ $selP->name }}. Encuentra productos y haz tu pedido en línea."
                              maxlength="160">{{ old('seo_description', $selP->setting('seo_description')) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Máximo 155–160 caracteres.</p>
                </div>
                <div>
                    <label class="label">Palabras clave</label>
                    <input type="text" name="seo_keywords" class="input mt-1"
                           placeholder="Ej: tienda online, {{ $selP->name }}, Lima"
                           value="{{ old('seo_keywords', $selP->setting('seo_keywords')) }}">
                    <p class="text-xs text-gray-400 mt-1">Separadas por coma.</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-xs text-green-700">
                    <p class="font-semibold text-green-800 mb-2">Incluido automáticamente</p>
                    <ul class="pl-4 list-disc space-y-1 leading-relaxed">
                        <li>Alt text en cada imagen de producto</li>
                        <li>Schema.org Product (Google Shopping)</li>
                        <li>Open Graph para WhatsApp, Facebook, Twitter</li>
                        <li>JSON-LD para Rich Results</li>
                    </ul>
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar SEO</button>
                </div>
            </form>
            @endif

            {{-- TAB: ENVÍO --}}
            @if(in_array($s, ['venta','envio']))
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-5 max-w-2xl">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selP->id }}">
                <input type="hidden" name="_tab" value="venta">
                <p class="text-sm text-gray-500">Configura si cobras envío a tus clientes y si requieres dirección de entrega.</p>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="shipping_enabled" value="0">
                        <input type="checkbox" name="shipping_enabled" value="1" class="sr-only peer"
                               {{ $selP->setting('shipping_enabled') == '1' ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700">Cobrar costo de envío</span>
                </div>

                <div>
                    <label class="label">Costo de envío (S/)</label>
                    <input type="number" name="shipping_cost" class="input mt-1 w-40" step="0.01" min="0"
                           placeholder="Ej: 5.00"
                           value="{{ old('shipping_cost', $selP->setting('shipping_cost')) }}">
                    <p class="text-xs text-gray-400 mt-1">Monto fijo que se suma al total del pedido.</p>
                </div>

                <div>
                    <label class="label">Envío gratis a partir de (S/)</label>
                    <input type="number" name="shipping_free_from" class="input mt-1 w-40" step="0.01" min="0"
                           placeholder="Ej: 100.00 (dejar vacío para no aplicar)"
                           value="{{ old('shipping_free_from', $selP->setting('shipping_free_from')) }}">
                    <p class="text-xs text-gray-400 mt-1">Si el subtotal llega a este monto, el envío es gratis. Dejar vacío para desactivar.</p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="require_address" value="0">
                        <input type="checkbox" name="require_address" value="1" class="sr-only peer"
                               {{ $selP->setting('require_address') == '1' ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700">Pedir dirección de entrega en el checkout</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar configuración de envío</button>
                </div>
            </form>
            @endif

            {{-- TAB: CUPONES --}}
            @if(in_array($s, ['venta','cupones']))
            @php $coupons = $selP->coupons()->orderByDesc('created_at')->get(); @endphp
            <div x-data="{
                coupons: {{ Illuminate\Support\Js::from($coupons->map(fn($c) => [
                    'id'         => $c->id,
                    'code'       => $c->code,
                    'type'       => $c->type,
                    'value'      => (float)$c->value,
                    'min_order'  => (float)$c->min_order,
                    'max_uses'   => $c->max_uses,
                    'uses_count' => $c->uses_count,
                    'expires_at' => $c->expires_at?->format('Y-m-d'),
                    'is_active'  => $c->is_active,
                ])) }},
                form: { code:'', type:'percent', value:'', min_order:'', max_uses:'', expires_at:'' },
                saving: false, err: '',
                async save() {
                    this.saving = true; this.err = '';
                    const res = await fetch('{{ route('coupons.store') }}', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                        body: JSON.stringify(this.form)
                    });
                    const d = await res.json();
                    this.saving = false;
                    if (d.ok) {
                        const idx = this.coupons.findIndex(c => c.id === d.coupon.id);
                        if (idx >= 0) this.coupons[idx] = d.coupon; else this.coupons.unshift(d.coupon);
                        this.form = { code:'', type:'percent', value:'', min_order:'', max_uses:'', expires_at:'' };
                    } else { this.err = d.message || 'Error al guardar.'; }
                },
                async toggle(id) {
                    const res = await fetch('/bixoadmin/coupons/'+id+'/toggle', {
                        method: 'PATCH',
                        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'}
                    });
                    const d = await res.json();
                    if (d.ok) { const c = this.coupons.find(c => c.id === id); if (c) c.is_active = d.is_active; }
                },
                async remove(id) {
                    if (!confirm('¿Eliminar este cupón?')) return;
                    await fetch('/bixoadmin/coupons/'+id, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'}
                    });
                    this.coupons = this.coupons.filter(c => c.id !== id);
                }
            }" class="space-y-5 max-w-2xl">
                <p class="text-sm text-gray-500">Crea códigos de descuento para tus clientes.</p>

                {{-- Formulario nuevo cupón --}}
                <div class="border border-gray-200 rounded-lg p-4 space-y-3 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700">Nuevo cupón</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Código</label>
                            <input x-model="form.code" type="text" class="input mt-1 uppercase" placeholder="VERANO20" style="text-transform:uppercase">
                        </div>
                        <div>
                            <label class="label">Tipo</label>
                            <select x-model="form.type" class="input mt-1">
                                <option value="percent">% Porcentaje</option>
                                <option value="fixed">S/ Monto fijo</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Valor</label>
                            <input x-model="form.value" type="number" step="0.01" min="0.01" class="input mt-1"
                                   :placeholder="form.type==='percent' ? 'Ej: 20 (para 20%)' : 'Ej: 15.00'">
                        </div>
                        <div>
                            <label class="label">Pedido mínimo (S/)</label>
                            <input x-model="form.min_order" type="number" step="0.01" min="0" class="input mt-1" placeholder="0 = sin mínimo">
                        </div>
                        <div>
                            <label class="label">Usos máximos</label>
                            <input x-model="form.max_uses" type="number" min="1" class="input mt-1" placeholder="Dejar vacío = ilimitado">
                        </div>
                        <div>
                            <label class="label">Expira el</label>
                            <input x-model="form.expires_at" type="date" class="input mt-1">
                        </div>
                    </div>
                    <div x-show="err" class="text-xs text-red-500" x-text="err"></div>
                    <button @click="save" :disabled="saving" type="button" class="btn-primary text-sm">
                        <span x-text="saving ? 'Guardando…' : 'Crear cupón'"></span>
                    </button>
                </div>

                {{-- Lista de cupones --}}
                <div x-show="coupons.length === 0" class="text-sm text-gray-400 py-2">No hay cupones aún.</div>
                <div class="space-y-2">
                    <template x-for="c in coupons" :key="c.id">
                        <div class="border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3 bg-white">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-bold text-sm" x-text="c.code"></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                                          x-text="c.is_active ? 'Activo' : 'Pausado'"></span>
                                    <span class="text-xs text-indigo-600 font-medium"
                                          x-text="c.type==='percent' ? c.value+'%' : 'S/ '+c.value.toFixed(2)"></span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <span x-show="c.min_order > 0">Mínimo S/ <span x-text="c.min_order.toFixed(2)"></span> · </span>
                                    <span x-text="c.uses_count"></span> usos
                                    <span x-show="c.max_uses"> / <span x-text="c.max_uses"></span></span>
                                    <span x-show="c.expires_at"> · Vence <span x-text="c.expires_at"></span></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button @click="toggle(c.id)" type="button"
                                        class="text-xs px-2 py-1 rounded border border-gray-200 hover:bg-gray-50"
                                        x-text="c.is_active ? 'Pausar' : 'Activar'"></button>
                                <button @click="remove(c.id)" type="button"
                                        class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            @endif

            {{-- TAB: FACTURACIÓN --}}
            @if($s === 'facturacion')
            <div class="max-w-2xl mx-auto py-6 px-6 space-y-6">

                {{-- Encabezado --}}
                <div class="flex items-center gap-3 pb-2 border-b border-gray-200">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Facturación Electrónica SUNAT</h3>
                        <p class="text-xs text-gray-500">Elige tu proveedor —Nubefact o APIsPERU— y configura sus credenciales para emitir facturas y boletas electrónicas</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $selP->id }}">
                    <input type="hidden" name="_tab" value="facturacion">

                    {{-- Datos fiscales del emisor --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Datos del Emisor</p>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
                                <input type="text" name="ruc" maxlength="11" class="input"
                                       placeholder="20123456789"
                                       value="{{ old('ruc', $selP->setting('ruc')) }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                                <input type="text" name="razon_social" class="input"
                                       placeholder="Mi Empresa S.A.C."
                                       value="{{ old('razon_social', $selP->setting('razon_social', $selP->name)) }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Serie Factura</label>
                                <input type="text" name="serie_factura" maxlength="4" class="input"
                                       placeholder="F001"
                                       value="{{ old('serie_factura', $selP->setting('serie_factura', 'F001')) }}">
                                <p class="text-xs text-gray-400 mt-1">Para facturas (RUC) · Nubefact demo: FFF1</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Serie Boleta</label>
                                <input type="text" name="serie_boleta" maxlength="4" class="input"
                                       placeholder="B001"
                                       value="{{ old('serie_boleta', $selP->setting('serie_boleta', 'B001')) }}">
                                <p class="text-xs text-gray-400 mt-1">Para boletas (DNI) · Nubefact demo: BBB1</p>
                            </div>
                        </div>
                    </div>

                    {{-- Proveedor de facturación electrónica + credenciales (un solo x-data) --}}
                    {{-- Sin proveedor elegido no se preselecciona ninguno: la eleccion es del negocio. --}}
                    @php $billingProvider = $selP->setting('billing_provider', ''); @endphp
                    <div x-data="{ prov: '{{ $billingProvider }}' }" class="space-y-4">

                        <div class="bg-indigo-50 rounded-xl p-4 space-y-3 border border-indigo-100">
                            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Proveedor de emisión a SUNAT</p>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer bg-white"
                                       :class="prov==='nubefact' ? 'border-indigo-500 ring-1 ring-indigo-300' : 'border-gray-200'">
                                    <input type="radio" name="billing_provider" value="nubefact" x-model="prov">
                                    <span class="text-sm font-medium text-gray-800">Nubefact</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer bg-white"
                                       :class="prov==='apisperu' ? 'border-indigo-500 ring-1 ring-indigo-300' : 'border-gray-200'">
                                    <input type="radio" name="billing_provider" value="apisperu" x-model="prov">
                                    <span class="text-sm font-medium text-gray-800">APIsPERU</span>
                                </label>
                            </div>
                            <p class="text-[11px] text-indigo-500">Elige qué servicio usará el botón "Enviar a SUNAT".</p>
                        </div>

                        {{-- Credenciales APIsPERU --}}
                        <div class="bg-gray-50 rounded-xl p-4 space-y-4" x-show="prov==='apisperu'" x-cloak>
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Credenciales APIsPERU (Facturación)</p>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $selP->setting('apisperu_token') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $selP->setting('apisperu_token') ? 'Configurado' : 'Sin configurar' }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Token de empresa (JWT)</label>
                                <input type="text" name="apisperu_token" class="input font-mono text-xs"
                                       placeholder="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
                                       value="{{ old('apisperu_token', $selP->setting('apisperu_token')) }}">
                                <p class="text-xs text-gray-400 mt-1">Del panel de tu empresa en <strong>facturacion.apisperu.com</strong> (incluye tu certificado).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ubigeo del emisor</label>
                                <input type="text" name="apisperu_ubigeo" class="input font-mono text-xs" maxlength="6"
                                       placeholder="150101"
                                       value="{{ old('apisperu_ubigeo', $selP->setting('apisperu_ubigeo', '150101')) }}">
                                <p class="text-xs text-gray-400 mt-1">Código de 6 dígitos (Lima-Lima-Lima = 150101).</p>
                            </div>
                        </div>

                    {{-- Credenciales Nubefact --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-4" x-show="prov==='nubefact'" x-cloak>
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Credenciales Nubefact</p>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $selP->setting('nubefact_token') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $selP->setting('nubefact_token') ? 'Configurado' : 'Sin configurar' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL (Ruta)</label>
                            <input type="text" name="nubefact_url" class="input font-mono text-xs"
                                   placeholder="https://api.nubefact.com/api/v1/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                   value="{{ old('nubefact_url', $selP->setting('nubefact_url')) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Token</label>
                            <input type="text" name="nubefact_token" class="input font-mono text-xs"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                   value="{{ old('nubefact_token', $selP->setting('nubefact_token')) }}">
                        </div>

                        <div class="flex items-start gap-2 text-xs text-blue-700 bg-blue-50 rounded-lg p-3">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Encuentra tu URL y Token en <strong>nubefact.com → Configuración → API</strong>. En modo demo puedes emitir comprobantes de prueba sin costo.</span>
                        </div>
                    </div>

                    {{-- Consulta automática de RUC/DNI — independiente del proveedor de emisión --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Consulta automática de RUC/DNI</p>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $selP->setting('apiperu_token') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $selP->setting('apiperu_token') ? 'Configurado' : 'Sin configurar' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Token APIPERU</label>
                            <input type="text" name="apiperu_token" class="input font-mono text-xs"
                                   placeholder="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
                                   value="{{ old('apiperu_token', $selP->setting('apiperu_token')) }}">
                            <p class="text-xs text-gray-400 mt-1">Autocompleta la razón social y dirección del cliente al escribir su RUC/DNI. Regístrate gratis en <strong>apiperu.dev</strong> y copia tu token JWT. Funciona con cualquier proveedor de emisión.</p>
                        </div>
                    </div>

                    </div>{{-- /x-data proveedor --}}

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary px-6">Guardar configuración</button>
                    </div>
                </form>
            </div>
            @endif

            {{-- TAB: WHATSAPP --}}
            @if($s === 'whatsapp')
            @php $waCanales = \App\Models\WaCanal::where('project_id', $selP->id)->get(); @endphp
            <div x-data="{
                canales: {{ Illuminate\Support\Js::from($waCanales->map(fn($c) => [
                    'id'                 => $c->id,
                    'nombre'             => $c->nombre,
                    'telefono'           => $c->telefono ?? '',
                    'phone_number_id'    => $c->phone_number_id ?? '',
                    'access_token'       => '',
                    'verify_token'       => $c->verify_token ?? '',
                    'mensaje_bienvenida' => $c->mensaje_bienvenida ?? '',
                    'activo'             => $c->activo,
                ])) }},
                form: { id:null, nombre:'', telefono:'', phone_number_id:'', access_token:'', verify_token:'', mensaje_bienvenida:'' },
                showForm: false,
                saving: false,
                err: '',
                edit(c) {
                    this.form = { id:c.id, nombre:c.nombre, telefono:c.telefono, phone_number_id:c.phone_number_id, access_token:'', verify_token:c.verify_token, mensaje_bienvenida:c.mensaje_bienvenida };
                    this.showForm = true;
                },
                newCanal() {
                    this.form = { id:null, nombre:'', telefono:'', phone_number_id:'', access_token:'', verify_token:'', mensaje_bienvenida:'' };
                    this.showForm = true;
                },
                async save() {
                    if (!this.form.nombre) { this.err = 'El nombre es requerido.'; return; }
                    this.saving = true; this.err = '';
                    try {
                        const res = await fetch('{{ route('settings.canales.store') }}', {
                            method: 'POST',
                            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            body: JSON.stringify(this.form)
                        });
                        if (!res.ok) { this.err = 'Error del servidor: ' + res.status; this.saving = false; return; }
                        const d = await res.json();
                        this.saving = false;
                        if (d.ok) {
                            const c = d.canal;
                            const idx = this.canales.findIndex(x => x.id === c.id);
                            if (idx >= 0) this.canales[idx] = c; else this.canales.unshift(c);
                            this.showForm = false;
                        } else { this.err = d.message || 'Error al guardar.'; }
                    } catch(e) { this.saving = false; this.err = 'Error de conexión: ' + e.message; }
                },
                async remove(id) {
                    if (!confirm('¿Eliminar este canal?')) return;
                    await fetch('{{ url('/bixoadmin/settings/canales') }}/'+id, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'}
                    });
                    this.canales = this.canales.filter(c => c.id !== id);
                }
            }" class="space-y-5 max-w-2xl">

                {{-- Bot WhatsApp (whatsapp-web.js) --}}
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="#25D366">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.824L0 24l6.335-1.502A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.885 0-3.652-.49-5.187-1.346l-.372-.214-3.762.892.924-3.67-.234-.388A9.954 9.954 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        <p class="text-sm font-semibold text-white">Bot WhatsApp (QR)</p>
                    </div>
                    <p class="text-xs text-gray-400 mb-3 leading-relaxed">
                        Registra el número de teléfono que usa el bot (el que escaneó el QR). El bot detectará automáticamente este proyecto cuando reciba mensajes.
                    </p>
                    <form method="POST" action="{{ route('settings.update') }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="section" value="wa_phone">
                        <input type="text" name="wa_phone"
                               value="{{ old('wa_phone', $selP->wa_phone) }}"
                               placeholder="51955354646"
                               class="flex-1 bg-gray-800 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-green-500">
                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                            Guardar
                        </button>
                    </form>
                    @if($selP->wa_phone)
                    <p class="text-xs text-green-400 mt-2">✓ Bot vinculado al número {{ $selP->wa_phone }}</p>
                    @endif
                </div>

                {{-- Botón Consultar por WA en catálogo --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Botón "Consultar por WA" en catálogo</p>
                            <p class="text-xs text-gray-500 mt-0.5">Muestra u oculta el botón verde de WhatsApp en las tarjetas de productos</p>
                        </div>
                        <form method="POST" action="{{ route('settings.update') }}">
                            @csrf
                            <input type="hidden" name="section" value="show_wa_button">
                            <input type="hidden" name="show_wa_button" value="0">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_wa_button" value="1"
                                       {{ $selP->setting('show_wa_button', '1') === '1' ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                            </label>
                        </form>
                    </div>
                </div>

                {{-- Info banner --}}
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">
                    <p class="font-semibold mb-1">Canal de WhatsApp Business</p>
                    <p class="text-xs text-green-700 leading-relaxed">
                        Conecta tu número de WhatsApp Business para recibir mensajes de clientes directamente en BixoChat.
                        Necesitas un número de teléfono activo en <strong>Meta Business API</strong>.
                    </p>
                </div>

                {{-- Webhook info --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-600 space-y-1">
                    <p class="font-semibold text-gray-700 mb-2">URL de Webhook para Meta</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-white border border-gray-200 rounded px-3 py-2 font-mono text-xs text-gray-800 break-all">
                            {{ url('/wa/webhook/'.$selP->slug) }}
                        </code>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ url('/wa/webhook/'.$selP->slug) }}')"
                                class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 text-xs whitespace-nowrap">
                            Copiar
                        </button>
                    </div>
                    <p class="text-gray-400 mt-1">Usa esta URL en la configuración de tu app en Meta Developers → Webhooks.</p>
                </div>

                {{-- Botón nuevo canal --}}
                <div x-show="!showForm">
                    <button @click="newCanal()" type="button"
                            class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar canal
                    </button>
                </div>

                {{-- Formulario canal --}}
                <div x-show="showForm" class="border border-gray-200 rounded-xl p-5 bg-gray-50 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-700" x-text="form.id ? 'Editar canal' : 'Nuevo canal'"></h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="label">Nombre del canal <span class="text-red-500">*</span></label>
                            <input x-model="form.nombre" type="text" class="input mt-1" placeholder="Ej: Atención al cliente">
                        </div>
                        <div>
                            <label class="label">Número de teléfono</label>
                            <input x-model="form.telefono" type="text" class="input mt-1" placeholder="51999999999">
                        </div>
                        <div>
                            <label class="label">Phone Number ID</label>
                            <input x-model="form.phone_number_id" type="text" class="input mt-1" placeholder="Meta Phone Number ID">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Access Token</label>
                            <input x-model="form.access_token" type="password" class="input mt-1"
                                   :placeholder="form.id ? '(dejar vacío para no cambiar)' : 'Token de acceso permanente'">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Verify Token</label>
                            <input x-model="form.verify_token" type="text" class="input mt-1"
                                   placeholder="Token de verificación del webhook (elige uno seguro)">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Mensaje de bienvenida</label>
                            <textarea x-model="form.mensaje_bienvenida" class="input mt-1" rows="3"
                                      placeholder="Hola 👋 Gracias por contactarnos. ¿En qué te podemos ayudar?"></textarea>
                        </div>
                    </div>

                    <div x-show="err" class="text-xs text-red-500" x-text="err"></div>

                    <div class="flex items-center gap-3">
                        <button @click="save()" :disabled="saving" type="button"
                                class="btn-primary disabled:opacity-60 text-sm">
                            <span x-text="saving ? 'Guardando…' : (form.id ? 'Actualizar canal' : 'Crear canal')"></span>
                        </button>
                        <button @click="showForm=false" type="button" class="btn-secondary text-sm">Cancelar</button>
                    </div>
                </div>

                {{-- Lista de canales --}}
                <div x-show="canales.length === 0 && !showForm" class="text-sm text-gray-400 py-2">
                    No hay canales configurados aún.
                </div>
                <div class="space-y-3">
                    <template x-for="c in canales" :key="c.id">
                        <div class="border border-gray-200 rounded-xl px-4 py-3 flex items-start gap-3 bg-white">
                            <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-800" x-text="c.nombre"></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          :class="c.activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                                          x-text="c.activo ? 'Activo' : 'Inactivo'"></span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <span x-show="c.telefono">+<span x-text="c.telefono"></span> · </span>
                                    <span x-show="c.phone_number_id" class="font-mono" x-text="'ID: '+c.phone_number_id"></span>
                                    <span x-show="!c.phone_number_id" class="text-orange-500">Sin configurar</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button @click="edit(c)" type="button"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">
                                    Editar
                                </button>
                                <button @click="remove(c.id)" type="button"
                                        class="text-xs text-red-500 hover:text-red-700 px-2 py-1.5">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Link al panel BixoChat --}}
                <div class="pt-2 border-t border-gray-100">
                    <a href="{{ route('bixocrm.bandeja') }}"
                       class="inline-flex items-center gap-2 text-sm text-green-700 hover:text-green-900 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Abrir bandeja de mensajes (BixoChat)
                    </a>
                </div>

            </div>
            @endif

            </div>{{-- /overflow-y-auto --}}
        </div>
    </template>

@if(!$isOwnerOrSuper)</div>@endif
</div>{{-- /panel detalle --}}
</div>{{-- /body --}}
</div>{{-- /flex col --}}

@endif{{-- /$s !== 'flujo' --}}

</x-slot>
</x-app-layout>
