<x-admin-layout title="Cargas masivas">

    <div class="mb-6">
        <h2 class="text-lg font-bold text-white">Cargas masivas</h2>
        <p class="text-sm text-gray-500 mt-0.5">Importa datos desde archivos CSV a cualquier proyecto</p>
    </div>

    @php
        $importCards = [
            [
                'type'    => 'products',
                'title'   => 'Productos',
                'desc'    => 'Importa tu catálogo de productos',
                'route'   => 'admin.imports.products',
                'fields'  => 'nombre, precio, stock, descripción, SKU',
                'color'   => 'indigo',
                'icon'    => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            ],
            [
                'type'    => 'clients',
                'title'   => 'Clientes',
                'desc'    => 'Importa tu base de clientes',
                'route'   => 'admin.imports.clients',
                'fields'  => 'nombre, teléfono, email, notas',
                'color'   => 'blue',
                'icon'    => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            ],
            [
                'type'    => 'employees',
                'title'   => 'Empleados',
                'desc'    => 'Importa tu equipo de trabajo',
                'route'   => 'admin.imports.employees',
                'fields'  => 'nombre, rol, área, teléfono, email',
                'color'   => 'green',
                'icon'    => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
        ];
        $colorMap = [
            'indigo' => ['bg'=>'bg-indigo-500/10','text'=>'text-indigo-400','border'=>'border-indigo-500/20'],
            'blue'   => ['bg'=>'bg-blue-500/10',  'text'=>'text-blue-400',  'border'=>'border-blue-500/20'],
            'green'  => ['bg'=>'bg-green-500/10', 'text'=>'text-green-400', 'border'=>'border-green-500/20'],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @foreach($importCards as $card)
        @php $c = $colorMap[$card['color']]; @endphp
        <div class="rounded-2xl border p-6" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $c['bg'] }} {{ $c['border'] }} border">
                    <svg class="w-5 h-5 {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">{{ $card['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $card['desc'] }}</p>
                </div>
            </div>

            {{-- Campos --}}
            <div class="mb-4 p-3 rounded-xl" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);">
                <p class="text-xs text-gray-500 mb-1 font-medium">Columnas del CSV:</p>
                <p class="text-xs text-gray-400 font-mono">{{ $card['fields'] }}</p>
            </div>

            {{-- Descargar plantilla --}}
            <a href="{{ route('admin.imports.template', $card['type']) }}"
               class="flex items-center gap-2 text-xs {{ $c['text'] }} hover:opacity-80 transition-opacity mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Descargar plantilla CSV
            </a>

            {{-- Formulario upload --}}
            <form method="POST" action="{{ route($card['route']) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Proyecto destino</label>
                    <select name="project_id" required
                            class="w-full px-3 py-2 rounded-lg text-sm text-gray-300 outline-none transition-colors"
                            style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);">
                        <option value="">Seleccionar proyecto...</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Archivo CSV</label>
                    <input type="file" name="file" accept=".csv,.txt" required
                           class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                  file:text-xs file:font-medium file:text-white file:cursor-pointer
                                  file:bg-indigo-600 hover:file:bg-indigo-700 file:transition-colors">
                </div>
                <button type="submit"
                        class="w-full py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    Importar {{ $card['title'] }}
                </button>
            </form>
        </div>
        @endforeach
    </div>

    {{-- Instrucciones --}}
    <div class="mt-6 rounded-2xl border p-5" style="background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.06);">
        <h3 class="text-sm font-semibold text-white mb-3">Instrucciones</h3>
        <ul class="space-y-2 text-sm text-gray-500">
            <li class="flex items-start gap-2">
                <span class="text-indigo-400 flex-shrink-0">1.</span>
                Descarga la plantilla CSV del tipo de dato que quieres importar.
            </li>
            <li class="flex items-start gap-2">
                <span class="text-indigo-400 flex-shrink-0">2.</span>
                Abre el archivo en Excel o Google Sheets y llena los datos desde la fila 2 (la fila 1 son los encabezados, no la borres).
            </li>
            <li class="flex items-start gap-2">
                <span class="text-indigo-400 flex-shrink-0">3.</span>
                Guarda como CSV (separado por comas) y súbelo seleccionando el proyecto destino.
            </li>
            <li class="flex items-start gap-2">
                <span class="text-indigo-400 flex-shrink-0">4.</span>
                Las filas con nombre vacío serán ignoradas automáticamente.
            </li>
        </ul>
    </div>

</x-admin-layout>
