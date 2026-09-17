@php
    $fontOptions = ['Inter','Poppins','Montserrat','Lato','Nunito','Jost','Raleway'];
    $destinationLabels = [
        'home'=>'Inicio','shop'=>'Tienda o catálogo','products'=>'Productos','category'=>'Categoría',
        'subcategory'=>'Subcategoría','brands'=>'Marcas','promotions'=>'Promociones','catalog_pdf'=>'Catálogo PDF','about'=>'Nosotros','contact'=>'Contacto','blog'=>'Blog',
        'page'=>'Página personalizada','external'=>'Enlace externo',
    ];
    $roots = $storeMenu->rootItems;
@endphp

<section id="constructor-navegacion" class="constructor-panel constructor-panel--navegacion store-navigation-builder" aria-labelledby="navigation-builder-title">
    <div class="snb-heading">
        <div><span>ENCABEZADO COMPARTIDO</span><h2 id="navigation-builder-title">Encabezado y menú</h2><p>Esta navegación se comparte entre Inicio, Tienda y todas las páginas públicas.</p></div>
        <a href="{{ route('settings.experience.preview') }}" target="_blank">Vista previa ↗</a>
    </div>
    @if(session('success'))
        <div class="snb-alert snb-alert--success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="snb-alert snb-alert--error" role="alert">
            <strong>No pudimos guardar la opción.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('settings.storefront.publish') }}" class="snb-card" style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap">@csrf<div><strong style="display:block;color:#172033;font-size:14px">Estado público: {{ $project->setting('storefront_structure_v2','0')==='1'?'Nueva estructura activa':'Vista previa' }}</strong><span style="color:#64748b;font-size:12px">La activación se aplica únicamente a {{ $project->name }}.</span></div><div class="snb-checks" style="margin:0"><label><input type="checkbox" name="confirm" value="1"><span>Revisé la vista previa</span></label><input type="hidden" name="enabled" value="{{ $project->setting('storefront_structure_v2','0')==='1'?'0':'1' }}"><button class="snb-primary">{{ $project->setting('storefront_structure_v2','0')==='1'?'Volver a estructura anterior':'Publicar nueva estructura' }}</button></div></form>

    <form method="POST" action="{{ route('settings.storefront.header') }}" enctype="multipart/form-data" class="snb-card">
        @csrf
        <div class="snb-card-title"><div><h3>Diseño del encabezado</h3><p>Colores, tipografía, altura y comportamiento por dispositivo.</p></div></div>
        <div class="snb-grid snb-grid-4">
            @foreach(['header_bg_color'=>'Fondo','header_text_color'=>'Texto','header_hover_color'=>'Al pasar el cursor','header_active_color'=>'Opción activa'] as $key=>$label)
                <label><span>{{ $label }}</span><div class="snb-color"><input type="color" name="{{ $key }}" value="{{ $headerSettings[$key] }}"><code>{{ $headerSettings[$key] }}</code></div></label>
            @endforeach
        </div>
        <div class="snb-grid snb-grid-3">
            <label><span>Tipografía del menú</span><select name="header_font">@foreach($fontOptions as $font)<option @selected($headerSettings['header_font']===$font)>{{ $font }}</option>@endforeach</select><small>Se aplica al menú en todas las páginas.</small></label>
            <label><span>Tamaño del texto</span><input type="number" name="header_font_size" min="12" max="20" value="{{ $headerSettings['header_font_size'] }}"><small>Entre 12 y 20 píxeles.</small></label>
            <label><span>Altura del encabezado</span><input type="number" name="header_height" min="56" max="120" value="{{ $headerSettings['header_height'] }}"><small>Entre 56 y 120 píxeles.</small></label>
            <label><span>Logo del encabezado</span><input type="file" name="header_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small>PNG, JPG, WebP o SVG; máximo 4 MB.</small></label>
            <label><span>Altura del logo</span><input type="number" name="header_logo_height" min="28" max="80" value="{{ $headerSettings['header_logo_height'] }}"></label>
            <label><span>Diseño en tablet</span><select name="header_tablet_style"><option value="drawer" @selected($headerSettings['header_tablet_style']==='drawer')>Menú desplegable</option><option value="desktop" @selected($headerSettings['header_tablet_style']==='desktop')>Menú completo</option></select></label>
            <label><span>Diseño en celular</span><select name="header_mobile_style"><option value="drawer" @selected($headerSettings['header_mobile_style']==='drawer')>Panel lateral</option><option value="compact" @selected($headerSettings['header_mobile_style']==='compact')>Panel compacto</option></select></label>
        </div>
        <div class="snb-checks">
            @foreach(['header_sticky'=>'Fijar al desplazarse','header_show_search'=>'Mostrar búsqueda','header_show_contact'=>'Mostrar contacto','header_show_cart'=>'Mostrar carrito'] as $key=>$label)
                <label><input type="checkbox" name="{{ $key }}" value="1" @checked($headerSettings[$key]==='1')><span>{{ $label }}</span></label>
            @endforeach
        </div>
        <div class="snb-actions"><button type="submit" class="snb-primary">Guardar encabezado</button></div>
    </form>

    {{-- Barra superior (topbar) personalizable --}}
    <form method="POST" action="{{ route('settings.design.update') }}" class="snb-card">
        @csrf
        <div class="snb-card-title"><div><h3>Barra superior</h3><p>El mensaje sobre el encabezado: texto, colores, tamaño, alineación y ancho.</p></div></div>
        <div class="snb-grid snb-grid-3">
            <label style="grid-column:span 2"><span>Texto del mensaje</span><input type="text" name="announcement_text" value="{{ $project->setting('announcement_text','') }}" placeholder="Compra online y ahorra tiempo y dinero"></label>
            <label><span>Alineación</span><select name="announcement_align">@foreach(['center'=>'Centro','left'=>'Izquierda','right'=>'Derecha'] as $v=>$l)<option value="{{ $v }}" @selected(($project->setting('announcement_align','center'))===$v)>{{ $l }}</option>@endforeach</select></label>
        </div>
        <div class="snb-grid snb-grid-4">
            <label><span>Fondo</span><div class="snb-color"><input type="color" name="announcement_bg" value="{{ $project->setting('announcement_bg','#2563eb') }}"><code>{{ $project->setting('announcement_bg','#2563eb') }}</code></div></label>
            <label><span>Color del texto</span><div class="snb-color"><input type="color" name="announcement_color" value="{{ $project->setting('announcement_color','#ffffff') }}"><code>{{ $project->setting('announcement_color','#ffffff') }}</code></div></label>
            <label><span>Tamaño de letra</span><input type="number" name="announcement_font_size" min="10" max="20" value="{{ $project->setting('announcement_font_size','12') }}"><small>Entre 10 y 20 px.</small></label>
            <label><span>Ancho</span><select name="announcement_full_width"><option value="0" @selected(($project->setting('announcement_full_width','0'))==='0')>Contenedor</option><option value="1" @selected(($project->setting('announcement_full_width','0'))==='1')>Completo</option></select></label>
        </div>
        <div class="snb-checks">
            <label><input type="checkbox" name="announcement_show" value="1" @checked(($project->setting('announcement_show','1'))!=='0')><span>Mostrar barra superior</span></label>
        </div>
        <div class="snb-actions"><button type="submit" class="snb-primary">Guardar barra superior</button></div>
    </form>

    <div class="snb-card">
        <div class="snb-card-title"><div><h3>Opciones del menú</h3><p>Arrastra para ordenar. Para crear un submenú, edita una opción y elige su opción principal.</p></div><span>{{ $storeMenu->items->count() }} opciones</span></div>
        <div class="snb-menu-list" data-menu-sort-list data-parent="">
            @forelse($roots as $item)
                @include('settings.partials.store-menu-item', ['item'=>$item])
                @if($item->children->isNotEmpty())
                    <div class="snb-submenu" data-menu-sort-list data-parent="{{ $item->id }}">@foreach($item->children as $child)@include('settings.partials.store-menu-item', ['item'=>$child])@endforeach</div>
                @endif
            @empty<div class="snb-empty">Aún no hay opciones en el menú.</div>@endforelse
        </div>
        <p class="snb-order-status" data-menu-order-status aria-live="polite"></p>
    </div>

    <form method="POST" action="{{ route('settings.storefront.menu.items.store') }}" class="snb-card" x-data="{type:@js(old('destination_type', 'home'))}">
        @csrf
        <div class="snb-card-title"><div><h3>Agregar opción</h3><p>El texto visible siempre puede modificarse.</p></div></div>
        <div class="snb-grid snb-grid-3">
            <label><span>Texto visible</span><input required name="label" maxlength="100" value="{{ old('label') }}" placeholder="Ej. Compra online"></label>
            <label><span>Destino</span><select name="destination_type" x-model="type">@foreach($destinationLabels as $key=>$label)<option value="{{ $key }}" @selected(old('destination_type', 'home')===$key)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Opción principal</span><select name="parent_id"><option value="">Sin superior</option>@foreach($roots as $root)<option value="{{ $root->id }}" @selected((string)old('parent_id')===(string)$root->id)>{{ $root->label }}</option>@endforeach</select><small>Déjalo vacío para crear una opción principal.</small></label>
            <label x-show="['category','subcategory','page'].includes(type)"><span>Categoría o página</span><select name="destination_id" :disabled="!['category','subcategory','page'].includes(type)"><option value="">Selecciona</option><optgroup label="Categorías">@foreach($menuCategories->whereNull('parent_id') as $category)<option value="{{ $category->id }}" @selected((string)old('destination_id')===(string)$category->id)>{{ $category->name }}</option>@endforeach</optgroup><optgroup label="Subcategorías">@foreach($menuCategories->whereNotNull('parent_id') as $category)<option value="{{ $category->id }}" @selected((string)old('destination_id')===(string)$category->id)>{{ $category->parent?->name }} / {{ $category->name }}</option>@endforeach</optgroup><optgroup label="Páginas personalizadas">@foreach($pages as $page)<option value="{{ $page->id }}" @selected((string)old('destination_id')===(string)$page->id)>{{ $page->title }}</option>@endforeach</optgroup></select></label>
            <label x-show="type==='external'"><span>URL externa</span><input type="url" name="url" value="{{ old('url') }}" :disabled="type!=='external'" placeholder="https://ejemplo.com"><small>Solo enlaces http o https.</small></label>
            <label><span>Abrir enlace</span><select name="target"><option value="_self" @selected(old('target', '_self')==='_self')>En la misma pestaña</option><option value="_blank" @selected(old('target')==='_blank')>En otra pestaña</option></select></label>
        </div>
        <div class="snb-checks"><label><input type="checkbox" name="is_enabled" value="1" checked><span>Visible</span></label><label><input type="checkbox" name="show_desktop" value="1" checked><span>Computadora</span></label><label><input type="checkbox" name="show_tablet" value="1" checked><span>Tablet</span></label><label><input type="checkbox" name="show_mobile" value="1" checked><span>Celular</span></label></div>
        <div class="snb-actions"><button type="submit" class="snb-primary">Agregar al menú</button></div>
    </form>
</section>

<style>
.constructor-panel--navegacion{order:2;width:100%;max-width:1100px!important}.constructor-panel--marca{order:3!important}.constructor-panel--inicio{order:4!important}.constructor-panel--portada{order:5!important}.constructor-panel--catalogo{order:6!important}.constructor-panel--paginas{order:7!important}.constructor-panel--checkout{order:8!important}.constructor-panel--sistema{order:9!important}
.store-navigation-builder{display:grid;gap:18px}.snb-heading,.snb-card-title{display:flex;align-items:flex-start;justify-content:space-between;gap:18px}.snb-heading>div>span{color:#4f46e5;font-size:11px;font-weight:800;letter-spacing:.1em}.snb-heading h2{margin:4px 0;font-size:25px;color:#172033}.snb-heading p,.snb-card-title p{margin:0;color:#64748b;font-size:13px}.snb-heading>a{padding:10px 14px;border:1px solid #c7d2fe;border-radius:9px;color:#4338ca;background:#fff;font-size:12px;font-weight:700}.snb-alert{padding:13px 15px;border:1px solid;border-radius:10px;font-size:12px;line-height:1.5}.snb-alert ul{margin:5px 0 0;padding-left:18px}.snb-alert--success{border-color:#a7f3d0;background:#ecfdf5;color:#047857}.snb-alert--error{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.snb-card{padding:22px;border:1px solid #e2e8f0;border-radius:15px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.035)}.snb-card-title{margin-bottom:18px}.snb-card-title h3{margin:0 0 4px;color:#172033;font-size:16px}.snb-card-title>span{padding:5px 9px;border-radius:99px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:700}.snb-grid{display:grid;gap:15px}.snb-grid-4{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px}.snb-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.snb-grid label>span{display:block;margin-bottom:6px;color:#334155;font-size:12px;font-weight:700}.snb-grid input:not([type=color]),.snb-grid select{width:100%;min-height:43px;padding:9px 11px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#172033;font-size:13px}.snb-grid input:focus,.snb-grid select:focus{border-color:#6366f1;outline:3px solid #e0e7ff}.snb-grid small{display:block;margin-top:5px;color:#94a3b8;font-size:11px}.snb-color{display:flex;align-items:center;gap:9px;min-height:44px;padding:5px 9px;border:1px solid #cbd5e1;border-radius:9px}.snb-color input{width:32px;height:30px;padding:0;border:0;background:none}.snb-color code{font-size:12px;color:#475569}.snb-checks{display:flex;flex-wrap:wrap;gap:9px 18px;margin-top:18px}.snb-checks label{display:flex;min-height:34px;align-items:center;gap:8px;color:#475569;font-size:12px;font-weight:600}.snb-checks input{width:17px;height:17px}.snb-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.snb-primary{min-height:43px;padding:10px 17px;border:0;border-radius:9px;background:#4f46e5;color:#fff;font-size:12px;font-weight:800;cursor:pointer}.snb-primary:hover{background:#4338ca}.snb-menu-list,.snb-submenu{display:grid;gap:9px}.snb-submenu{margin:0 0 4px 38px;padding-left:14px;border-left:2px solid #e0e7ff}.snb-menu-item{border:1px solid #e2e8f0;border-radius:11px;background:#fff;overflow:hidden}.snb-menu-summary{display:grid;grid-template-columns:auto 1fr auto auto;align-items:center;gap:11px;min-height:56px;padding:9px 12px}.snb-drag{display:grid;width:34px;height:34px;place-items:center;border:0;border-radius:7px;background:#f8fafc;color:#94a3b8;cursor:grab}.snb-menu-copy strong{display:block;color:#172033;font-size:13px}.snb-menu-copy small{color:#64748b;font-size:11px}.snb-device-pills{display:flex;gap:4px}.snb-device-pills span{padding:4px 6px;border-radius:6px;background:#f1f5f9;color:#64748b;font-size:9px}.snb-device-pills .off{opacity:.35;text-decoration:line-through}.snb-menu-summary summary{padding:7px 9px;border-radius:7px;background:#eef2ff;color:#4338ca;font-size:11px;font-weight:700;cursor:pointer;list-style:none}.snb-menu-editor{padding:17px;border-top:1px solid #e2e8f0;background:#f8fafc}.snb-delete{margin-right:auto;background:#fff;color:#dc2626;border:1px solid #fecaca}.snb-menu-item.is-dragging{opacity:.45}.snb-menu-item.is-drag-over{border-color:#6366f1;box-shadow:0 0 0 3px #e0e7ff}.snb-order-status{min-height:18px;margin:10px 0 0;color:#047857;font-size:11px}.snb-empty{padding:26px;text-align:center;color:#64748b;border:1px dashed #cbd5e1;border-radius:10px}
@media(max-width:1023px){.snb-grid-4,.snb-grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}.snb-device-pills{display:none}}
@media(max-width:640px){.snb-card{padding:16px}.snb-heading{align-items:stretch;flex-direction:column}.snb-heading>a{text-align:center}.snb-grid-4,.snb-grid-3{grid-template-columns:1fr}.snb-menu-summary{grid-template-columns:auto 1fr auto}.snb-menu-summary details{grid-column:1/-1}.snb-menu-summary summary{text-align:center}.snb-submenu{margin-left:18px}.snb-actions{display:grid;grid-template-columns:1fr}.snb-primary{width:100%}}
</style>

<script>
document.addEventListener('DOMContentLoaded',()=>{
  const status=document.querySelector('[data-menu-order-status]');let dragged=null;const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
  const saveOrder=async()=>{const items=[];document.querySelectorAll('[data-menu-sort-list]').forEach(list=>{[...list.children].filter(el=>el.matches('[data-menu-item]')).forEach((el,index)=>items.push({id:Number(el.dataset.menuItem),parent_id:list.dataset.parent||null,sort_order:(index+1)*10}))});if(status)status.textContent='Guardando orden…';try{const response=await fetch(@js(route('settings.storefront.menu.reorder')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({items})});if(!response.ok)throw new Error();if(status)status.textContent='Orden guardado.'}catch(e){if(status)status.textContent='No se pudo guardar el orden.'}};
  document.querySelectorAll('[data-menu-item]').forEach(item=>{item.addEventListener('dragstart',e=>{if(e.target.closest('input,select,button,a,summary')){e.preventDefault();return}dragged=item;item.classList.add('is-dragging')});item.addEventListener('dragend',()=>{item.classList.remove('is-dragging');dragged=null});item.addEventListener('dragover',e=>{if(dragged&&dragged.parentElement===item.parentElement){e.preventDefault();item.classList.add('is-drag-over')}});item.addEventListener('dragleave',()=>item.classList.remove('is-drag-over'));item.addEventListener('drop',e=>{e.preventDefault();item.classList.remove('is-drag-over');if(!dragged||dragged===item||dragged.parentElement!==item.parentElement)return;const box=item.getBoundingClientRect();item.parentElement.insertBefore(dragged,e.clientY<box.top+box.height/2?item:item.nextSibling);saveOrder()})});
});
</script>
