<x-app-layout>
<x-slot name="slot">
<div class="flex flex-col md:flex-row flex-1 overflow-hidden">

{{-- PANEL 2: MENÚ CONFIGURACIÓN --}}
<div class="md:list-panel flex-shrink-0 flex flex-row md:flex-col overflow-x-auto border-b md:border-b-0 md:border-r border-gray-200 bg-white" style="min-width:0">
    <div class="px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0 hidden md:block">
        <h2 class="text-sm font-semibold text-gray-700">Configuración</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>

    <div class="overflow-y-auto flex-1 flex flex-row md:flex-col">
        @php
            $currentTab = request('tab', 'general');
            $menuItems = [
                ['key'=>'general', 'label'=>'General',        'desc'=>'Datos del negocio',
                 'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ['key'=>'public',  'label'=>'Página pública', 'desc'=>'URL y catálogo online',
                 'icon'=>'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        @endphp

        @foreach($menuItems as $item)
        <a href="{{ route('settings', ['project'=>$project->id]) }}?tab={{ $item['key'] }}"
           class="list-item flex-shrink-0 md:flex-shrink {{ $currentTab === $item['key'] ? 'selected' : '' }}">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $currentTab === $item['key'] ? 'bg-indigo-600' : 'bg-gray-100' }}">
                <svg class="w-4 h-4 {{ $currentTab === $item['key'] ? 'text-white' : 'text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $item['label'] }}</p>
                <p class="text-xs text-gray-400">{{ $item['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>

{{-- PANEL 3: DETALLE --}}
<div class="detail-panel">
    @php $tab = request('tab', 'general'); @endphp

    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" x-cloak
         class="m-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- GENERAL --}}
    @if($tab === 'general')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Información general</h2>
            <p class="text-xs text-gray-400 mt-0.5">Datos básicos visibles en tu catálogo público</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.update', ['project'=>$project->id]) }}" class="space-y-4 max-w-lg">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Nombre del negocio *</label>
                        <input type="text" name="name" class="input" value="{{ old('name', $project->name) }}" required>
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">URL pública (slug)</label>
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl overflow-hidden focus-within:border-indigo-400 transition">
                            <span class="px-3 py-2.5 text-xs text-gray-400 border-r border-gray-200 whitespace-nowrap hidden sm:block">/p/</span>
                            <input type="text" name="slug" class="flex-1 bg-transparent px-3 py-2.5 text-sm outline-none font-mono"
                                   value="{{ old('slug', $project->slug) }}"
                                   pattern="[a-z0-9\-]+" title="Solo letras minúsculas, números y guiones">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Se actualiza automáticamente al cambiar el nombre, o puedes escribirlo manualmente.</p>
                    </div>
                    <div>
                        <label class="label">RUC / NIF</label>
                        <input type="text" name="ruc" class="input" value="{{ old('ruc', $project->setting('ruc')) }}" placeholder="20601234567">
                    </div>
                    <div>
                        <label class="label">Email de contacto</label>
                        <input type="email" name="email" class="input" value="{{ old('email', $project->setting('email')) }}" placeholder="contacto@empresa.com">
                    </div>
                    <div>
                        <label class="label">Categoría / Rubro</label>
                        <input type="text" name="category" class="input" value="{{ old('category', $project->category) }}" placeholder="Tienda, Restaurante...">
                    </div>
                    <div>
                        <label class="label">Teléfono</label>
                        <input type="text" name="phone" class="input" value="{{ old('phone', $project->phone) }}" placeholder="+51 999 000 000">
                    </div>
                    <div>
                        <label class="label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="input" value="{{ old('whatsapp', $project->whatsapp) }}" placeholder="+51 999 000 000">
                    </div>
                    <div>
                        <label class="label">Dirección</label>
                        <input type="text" name="address" class="input" value="{{ old('address', $project->address) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Descripción</label>
                        <textarea name="description" class="input resize-none" rows="3">{{ old('description', $project->description) }}</textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Configuración regional</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">País</label>
                            <select name="country" class="input">
                                @php $country = $project->setting('country', 'Perú'); @endphp
                                @foreach(['Perú','Colombia','México','Argentina','Chile','Ecuador','Bolivia','Venezuela','Uruguay','Paraguay'] as $c)
                                <option value="{{ $c }}" {{ $country===$c?'selected':'' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Moneda</label>
                            <select name="currency" class="input">
                                @php $currency = $project->setting('currency', 'PEN'); @endphp
                                @foreach(['PEN'=>'Soles (PEN)','USD'=>'Dólares (USD)','COP'=>'Pesos COP','MXN'=>'Pesos MXN','ARS'=>'Pesos ARS','CLP'=>'Pesos CLP'] as $code => $label)
                                <option value="{{ $code }}" {{ $currency===$code?'selected':'' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                    <a href="{{ url('/p/'.$project->slug) }}" target="_blank" class="btn-secondary text-sm">Ver página pública ↗</a>
                </div>
                <p class="text-xs text-gray-400 pt-2">v1.0.0</p>
            </form>
        </div>
    </div>
    @endif

    {{-- PÁGINA PÚBLICA --}}
    @if($tab === 'public')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Página pública</h2>
            <p class="text-xs text-gray-400 mt-0.5">Tu catálogo visible para clientes sin cuenta</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-lg space-y-5">
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-5">
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">Tu URL pública</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm text-indigo-800 bg-white border border-indigo-200 rounded-lg px-3 py-2 truncate font-mono">
                            {{ url('/p/' . $project->slug) }}
                        </code>
                        <a href="{{ url('/p/' . $project->slug) }}" target="_blank"
                           class="btn-primary text-xs px-3 py-2 whitespace-nowrap flex-shrink-0">Abrir ↗</a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800">{{ $project->products()->where('is_available', true)->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Productos publicados</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800">{{ $project->services()->where('is_available', true)->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Servicios publicados</p>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
                    <p class="text-sm font-semibold text-gray-700">¿Qué pueden hacer tus clientes?</p>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ver productos y servicios del catálogo
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Hacer pedidos con carrito de compras
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Reservar citas directamente
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Contactar por WhatsApp
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

</div>
</x-slot>
</x-app-layout>
