<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BIXO CRM — Elige tu negocio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
<div class="min-h-screen flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-2xl">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center" style="background:#25d366;">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider" style="color:#25d366">BIXO CRM</p>
                <h1 class="text-xl font-bold text-gray-900">¿A qué negocio entras?</h1>
            </div>
        </div>

        <p class="text-sm text-gray-500 mb-4">Tienes acceso a varios negocios. Solo abren el CRM los que lo tienen contratado; los demás se activan desde BIXO Control.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($negocios as $n)
                @if($n['crm'])
                <form method="POST" action="{{ route('bixocrm.elegir.post') }}">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $n['id'] }}">
                    <button type="submit" class="w-full text-left p-4 rounded-2xl bg-white border border-gray-200 hover:border-green-400 hover:shadow-sm transition">
                        <p class="font-semibold text-gray-900">{{ $n['name'] }}</p>
                        <p class="text-xs text-gray-500">/{{ $n['slug'] }}</p>
                        <span class="inline-block mt-2 text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#166534">BIXO CRM</span>
                    </button>
                </form>
                @else
                <div class="p-4 rounded-2xl bg-gray-100 border border-gray-200 opacity-70">
                    <p class="font-semibold text-gray-700">{{ $n['name'] }}</p>
                    <p class="text-xs text-gray-500">/{{ $n['slug'] }}</p>
                    <span class="inline-block mt-2 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-200 text-gray-600">Sin CRM contratado</span>
                </div>
                @endif
            @endforeach
        </div>

        <form method="POST" action="{{ route('bixocrm.logout') }}" class="mt-6 text-center">
            @csrf
            <button class="text-xs text-gray-500 underline">Salir</button>
        </form>
    </div>
</div>
</body>
</html>
