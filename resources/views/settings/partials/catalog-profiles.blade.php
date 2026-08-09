@php
    $cpEnabled = (string) $project->setting('catalog_profiles_enabled', '0') === '1';
    $cpPolicy  = (string) $project->setting('catalog_profile_orphan_policy', 'hide');
    $cpProfiles = $project->catalogProfiles()->orderBy('sort_order')->orderBy('name')->get();
    $cpCategories = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
@endphp

<section id="constructor-perfiles" class="constructor-panel store-navigation-builder" aria-labelledby="catalog-profiles-title">
    <div class="snb-heading">
        <div>
            <span>CATÁLOGOS SEGMENTADOS</span>
            <h2 id="catalog-profiles-title">Perfiles de catálogo</h2>
            <p>Muestra distintos productos e identidad según el público (Hombre/Mujer, Gamer/Oficina, Minorista/Mayorista…). Opcional: si está desactivado, tu tienda funciona igual que ahora.</p>
        </div>
    </div>

    {{-- Activar / política --}}
    <form method="POST" action="{{ route('settings.catalog-profiles.feature') }}" class="snb-card" style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap">
        @csrf
        <div>
            <strong style="display:block;color:#172033;font-size:14px">Estado: {{ $cpEnabled ? 'Activado' : 'Desactivado' }}</strong>
            <span style="color:#64748b;font-size:12px">Aplica sólo a {{ $project->name }}.</span>
        </div>
        <div class="snb-checks" style="margin:0;align-items:center">
            <input type="hidden" name="enabled" value="{{ $cpEnabled ? '0' : '1' }}">
            <label style="font-weight:600">Productos sin perfil:
                <select name="orphan_policy" style="min-height:38px;margin-left:6px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px">
                    <option value="hide" @selected($cpPolicy==='hide')>Ocultarlos</option>
                    <option value="show_all" @selected($cpPolicy==='show_all')>Mostrarlos en todos</option>
                </select>
            </label>
            <button class="snb-primary">{{ $cpEnabled ? 'Desactivar perfiles' : 'Activar perfiles' }}</button>
        </div>
    </form>

    @if($cpEnabled)
        {{-- Accesos rápidos --}}
        <div class="snb-card">
            <div class="snb-card-title"><div><h3>Crear rápido</h3><p>Crea un par de perfiles listos para editar. No limitan tu tienda.</p></div></div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach(['moda'=>'Hombre / Mujer','infantil'=>'Niño / Niña','tecnologia'=>'Gamer / Oficina','ventas'=>'Minorista / Mayorista'] as $preset=>$label)
                    <form method="POST" action="{{ route('settings.catalog-profiles.quick') }}">
                        @csrf<input type="hidden" name="preset" value="{{ $preset }}">
                        <button class="snb-primary" style="background:#eef2ff;color:#4338ca">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </div>

        {{-- Lista de perfiles existentes --}}
        <div class="snb-card">
            <div class="snb-card-title"><div><h3>Tus perfiles</h3><p>Edita nombre, identidad opcional y qué categorías incluye cada uno.</p></div><span>{{ $cpProfiles->count() }}</span></div>

            @forelse($cpProfiles as $profile)
                <details class="snb-menu-item" style="margin-bottom:10px">
                    <summary class="snb-menu-summary" style="grid-template-columns:1fr auto auto;list-style:none;cursor:pointer">
                        <div class="snb-menu-copy">
                            <strong>{{ $profile->name }}
                                @if($profile->is_default)<span style="color:#4338ca;font-size:10px"> · predeterminado</span>@endif
                                @unless($profile->is_enabled)<span style="color:#dc2626;font-size:10px"> · desactivado</span>@endunless
                            </strong>
                            <small>/{{ $project->slug }}/tienda/{{ $profile->slug }} · {{ $profile->categories()->count() }} categorías</small>
                        </div>
                        <span class="snb-device-pills"><span class="{{ $profile->show_in_menu ? '' : 'off' }}">menú</span></span>
                        <span style="padding:6px 9px;border-radius:7px;background:#eef2ff;color:#4338ca;font-size:11px;font-weight:700">Editar</span>
                    </summary>

                    <div class="snb-menu-editor">
                        <form method="POST" action="{{ route('settings.catalog-profiles.update', $profile->id) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <div class="snb-grid snb-grid-3">
                                <label><span>Nombre</span><input type="text" name="name" value="{{ $profile->name }}" required></label>
                                <label><span>Etiqueta en el menú</span><input type="text" name="menu_label" value="{{ $profile->menu_label }}"></label>
                                <label><span>Enlace (slug)</span><input type="text" name="slug" value="{{ $profile->slug }}" pattern="[a-z0-9-]+"></label>
                            </div>
                            <div class="snb-checks">
                                <label><input type="checkbox" name="is_enabled" value="1" @checked($profile->is_enabled)><span>Activo</span></label>
                                <label><input type="checkbox" name="show_in_menu" value="1" @checked($profile->show_in_menu)><span>Mostrar en el menú</span></label>
                                <label><input type="checkbox" name="is_default" value="1" @checked($profile->is_default)><span>Predeterminado</span></label>
                            </div>

                            <div class="snb-card-title" style="margin:16px 0 10px"><div><h3 style="font-size:14px">Identidad propia (opcional)</h3><p>Lo que dejes vacío hereda el diseño general de la tienda.</p></div></div>
                            <div class="snb-grid snb-grid-4">
                                @foreach(['primary_color'=>'Color principal','secondary_color'=>'Color secundario','header_bg_color'=>'Fondo encabezado','button_color'=>'Botones'] as $ck=>$cl)
                                    <label><span>{{ $cl }}</span><div class="snb-color"><input type="color" name="{{ $ck }}" value="{{ $profile->{$ck} ?: '#ffffff' }}"><code>{{ $profile->{$ck} ?: 'heredar' }}</code></div></label>
                                @endforeach
                            </div>
                            <div class="snb-grid snb-grid-3">
                                <label><span>Logo propio</span><input type="file" name="logo" accept="image/*"></label>
                                <label><span>Hero (escritorio)</span><input type="file" name="hero_desktop" accept="image/*"></label>
                                <label><span>Título del hero</span><input type="text" name="hero_title" value="{{ $profile->hero_title }}"></label>
                            </div>

                            <div class="snb-card-title" style="margin:16px 0 10px"><div><h3 style="font-size:14px">Categorías incluidas</h3><p>Marca qué categorías verá este perfil. Sin marcar ninguna, sólo aporta identidad.</p></div></div>
                            <div class="snb-checks" style="max-height:180px;overflow:auto">
                                @php $assigned = $profile->categories()->pluck('categories.id')->all(); @endphp
                                @foreach($cpCategories as $cat)
                                    <label><input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" @checked(in_array($cat->id,$assigned))><span>{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</span></label>
                                @endforeach
                            </div>

                            <div class="snb-actions">
                                <button type="submit" class="snb-primary">Guardar perfil</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('settings.catalog-profiles.destroy', $profile->id) }}" onsubmit="return confirm('¿Eliminar este perfil? No afecta productos ni stock.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="snb-primary snb-delete" style="margin-top:8px">Eliminar perfil</button>
                        </form>
                    </div>
                </details>
            @empty
                <div class="snb-empty">Aún no tienes perfiles. Usa “Crear rápido” o el formulario de abajo.</div>
            @endforelse
        </div>

        {{-- Crear nuevo --}}
        <div class="snb-card">
            <div class="snb-card-title"><div><h3>Nuevo perfil</h3></div></div>
            <form method="POST" action="{{ route('settings.catalog-profiles.store') }}">
                @csrf
                <div class="snb-grid snb-grid-3">
                    <label><span>Nombre</span><input type="text" name="name" placeholder="Ej. Mayorista" required></label>
                    <label><span>Etiqueta en el menú</span><input type="text" name="menu_label" placeholder="(opcional)"></label>
                    <label><span>Color principal (opcional)</span><div class="snb-color"><input type="color" name="primary_color" value="#ffffff"><code>heredar</code></div></label>
                </div>
                <div class="snb-actions"><button type="submit" class="snb-primary">Crear perfil</button></div>
            </form>
        </div>
    @endif
</section>
