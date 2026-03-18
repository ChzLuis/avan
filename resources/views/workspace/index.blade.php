<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Negocios — AVAN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="min-h-screen">
    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">A</span>
            </div>
            <span class="font-bold text-gray-800 text-lg">AVAN</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button class="text-sm text-gray-500 hover:text-gray-800">Salir</button>
            </form>
        </div>
    </header>

    {{-- Contenido --}}
    <main class="max-w-5xl mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mis Negocios</h1>
                <p class="text-gray-500 text-sm mt-1">Selecciona un negocio para administrarlo</p>
            </div>
            <a href="{{ route('projects.create') }}"
               class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo negocio
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($projects->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Aún no tienes negocios</h2>
            <p class="text-gray-500 text-sm mb-6">Crea tu primer negocio para empezar a usar AVAN</p>
            <a href="{{ route('projects.create') }}" class="btn-primary">Crear mi primer negocio</a>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($projects as $project)
            <div class="bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all group">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            @if($project->logo_url)
                                <img src="{{ $project->logo_url }}" class="w-12 h-12 rounded-xl object-cover" alt="{{ $project->name }}">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                                    {{ strtoupper(substr($project->name, 0, 2)) }}
                                </div>
                            @endif
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $project->name }}</h3>
                                <span class="text-xs text-gray-400">{{ $project->category ?? 'Negocio' }}</span>
                            </div>
                        </div>
                        <span class="{{ $project->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $project->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    @if($project->description)
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $project->description }}</p>
                    @endif
                    <div class="flex gap-2">
                        <a href="{{ route('dashboard', ['project' => $project->id]) }}"
                           class="btn-primary text-sm flex-1 text-center">
                            Abrir panel
                        </a>
                        <a href="{{ route('projects.edit', $project) }}"
                           class="btn-secondary text-sm px-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-2 flex gap-4 text-xs text-gray-400">
                    <span>{{ $project->products()->count() }} productos</span>
                    <span>{{ $project->orders()->count() }} pedidos</span>
                    <span>{{ $project->clients()->count() }} clientes</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </main>
</div>

</body>
</html>
