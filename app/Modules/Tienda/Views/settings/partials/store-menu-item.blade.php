@php $typeLabel=$destinationLabels[$item->destination_type]??$item->destination_type; @endphp
<article class="snb-menu-item" data-menu-item="{{ $item->id }}" draggable="true" x-data="{open:false,type:@js($item->destination_type)}">
    <div class="snb-menu-summary">
        <button type="button" class="snb-drag" aria-label="Arrastrar {{ $item->label }}">⋮⋮</button>
        <div class="snb-menu-copy"><strong>{{ $item->label }}</strong><small>{{ $typeLabel }} · {{ $item->is_enabled ? 'Visible':'Oculto' }}</small></div>
        <div class="snb-device-pills"><span class="{{ $item->show_desktop?'':'off' }}">PC</span><span class="{{ $item->show_tablet?'':'off' }}">Tablet</span><span class="{{ $item->show_mobile?'':'off' }}">Celular</span></div>
        <button type="button" class="snb-primary" style="min-height:34px;padding:7px 10px" @click="open=!open" :aria-expanded="open">Editar</button>
    </div>
    <div class="snb-menu-editor" x-show="open" x-cloak>
        <form method="POST" action="{{ route('settings.storefront.menu.items.update',$item->id) }}">@csrf @method('PUT')
            <div class="snb-grid snb-grid-3">
                <label><span>Texto visible</span><input required name="label" maxlength="100" value="{{ $item->label }}"></label>
                <label><span>Destino</span><select name="destination_type" x-model="type">@foreach($destinationLabels as $key=>$label)<option value="{{ $key }}" @selected($item->destination_type===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span>Opción principal</span><select name="parent_id"><option value="">Sin superior</option>@foreach($roots->where('id','!=',$item->id) as $root)<option value="{{ $root->id }}" @selected($item->parent_id===$root->id)>{{ $root->label }}</option>@endforeach</select></label>
                <label x-show="['category','subcategory','page'].includes(type)"><span>Categoría o página</span><select name="destination_id" :disabled="!['category','subcategory','page'].includes(type)"><option value="">Selecciona</option><optgroup label="Categorías">@foreach($menuCategories->whereNull('parent_id') as $category)<option value="{{ $category->id }}" @selected($item->destination_id===$category->id)>{{ $category->name }}</option>@endforeach</optgroup><optgroup label="Subcategorías">@foreach($menuCategories->whereNotNull('parent_id') as $category)<option value="{{ $category->id }}" @selected($item->destination_id===$category->id)>{{ $category->parent?->name }} / {{ $category->name }}</option>@endforeach</optgroup><optgroup label="Páginas personalizadas">@foreach($pages as $page)<option value="{{ $page->id }}" @selected($item->destination_id===$page->id)>{{ $page->title }}</option>@endforeach</optgroup></select></label>
                <label x-show="type==='external'"><span>URL externa</span><input type="url" name="url" value="{{ $item->url }}" :disabled="type!=='external'"></label>
                <label><span>Abrir enlace</span><select name="target"><option value="_self" @selected($item->target==='_self')>Misma pestaña</option><option value="_blank" @selected($item->target==='_blank')>Otra pestaña</option></select></label>
            </div>
            <div class="snb-checks"><label><input type="checkbox" name="is_enabled" value="1" @checked($item->is_enabled)><span>Visible</span></label><label><input type="checkbox" name="show_desktop" value="1" @checked($item->show_desktop)><span>Computadora</span></label><label><input type="checkbox" name="show_tablet" value="1" @checked($item->show_tablet)><span>Tablet</span></label><label><input type="checkbox" name="show_mobile" value="1" @checked($item->show_mobile)><span>Celular</span></label></div>
            <div class="snb-actions"><button type="button" class="snb-primary snb-delete" onclick="bxConfirmar({ descripcion: '¿Eliminar esta opción del menú?', boton: 'Eliminar' }).then(ok => { if (ok) document.getElementById('delete-menu-item-{{ $item->id }}').submit(); })">Eliminar</button><button class="snb-primary">Guardar opción</button></div>
        </form>
        <form id="delete-menu-item-{{ $item->id }}" method="POST" action="{{ route('settings.storefront.menu.items.destroy',$item->id) }}" hidden>@csrf @method('DELETE')</form>
    </div>
</article>
