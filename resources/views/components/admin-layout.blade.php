@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — BIXO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        body { font-family:'Inter',sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

<div class="min-h-screen flex" x-data="{ sidebarOpen: true }">

    {{-- SIDEBAR --}}
    <aside class="flex-shrink-0 flex flex-col bg-gray-900 border-r border-gray-800"
           :class="sidebarOpen ? 'w-64' : 'w-16'" style="transition:width .2s;">

        {{-- Logo --}}
        <div class="h-16 flex items-center px-4 border-b border-gray-800 gap-3 overflow-hidden">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div x-show="sidebarOpen" x-cloak class="overflow-hidden">
                <p class="text-white font-bold text-sm leading-none whitespace-nowrap">BIXO</p>
                <p class="text-gray-500 text-xs whitespace-nowrap">Super Admin</p>
            </div>
        </div>

        {{-- Nav items --}}
        <nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto">
            @php
                // Reestructuración 2026-08-30: el Control se agrupa según el
                // plan (Empresas / Licencias / Usuarios / Soporte / Imports /
                // Configuración), pero SOLO con lo que existe. Los bloques del
                // plan sin backend (Productos y planes, Feature flags,
                // Plataforma/health, Integraciones) son deuda registrada
                // (TD-024) — no se inventan CRUDs aquí (ADR-002).
                $navGrupos = [
                    ['titulo' => null, 'items' => [
                        ['route'=>'admin.dashboard','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6','label'=>'Inicio'],
                    ]],
                    ['titulo' => 'Empresas', 'items' => [
                        ['route'=>'admin.projects', 'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10','label'=>'Empresas / tenants'],
                        ['route'=>'admin.demos.index','icon'=>'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z','label'=>'Demos'],
                    ]],
                    ['titulo' => 'Licencias', 'items' => [
                        ['route'=>'admin.licenses','icon'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z','label'=>'Licencias y asientos'],
                    ]],
                    ['titulo' => 'Usuarios', 'items' => [
                        ['route'=>'admin.users','icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z','label'=>'Usuarios globales'],
                    ]],
                    ['titulo' => 'Soporte y auditoría', 'items' => [
                        ['route'=>'admin.audit','icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z','label'=>'Auditoría de accesos'],
                    ]],
                    ['titulo' => 'Imports', 'items' => [
                        ['route'=>'admin.imports','icon'=>'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10','label'=>'Cargas masivas'],
                    ]],
                    ['titulo' => 'Configuración', 'items' => [
                        ['route'=>'admin.settings','icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z','label'=>'Configuración global'],
                    ]],
                ];
            @endphp
            @foreach($navGrupos as $grupo)
            @if($grupo['titulo'])
            <p class="px-3 pt-4 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-600"
               x-show="sidebarOpen" x-cloak>{{ $grupo['titulo'] }}</p>
            @endif
            @foreach($grupo['items'] as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                      {{ $active ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0 {{ $active ? 'text-white' : 'text-gray-500 group-hover:text-white' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                </svg>
                <span x-show="sidebarOpen" x-cloak class="whitespace-nowrap">{{ $item['label'] }}</span>
            </a>
            @endforeach
            @endforeach
        </nav>

        {{-- Usuario --}}
        <div class="border-t border-gray-800 p-3">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div x-show="sidebarOpen" x-cloak class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Super Admin</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" x-show="sidebarOpen" x-cloak>
                    @csrf
                    <button type="submit" title="Salir" class="text-gray-500 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- CONTENIDO --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Topbar --}}
        <header class="h-16 bg-gray-900 border-b border-gray-800 flex items-center justify-between px-6 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen=!sidebarOpen" class="text-gray-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-sm font-semibold text-white">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                Sistema activo
            </div>
        </header>

        {{-- Alerts --}}
        @if(session('success'))
        <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
             class="mx-6 mt-4 bg-green-900/40 border border-green-700/50 text-green-300 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,5000)" x-cloak
             class="mx-6 mt-4 bg-red-900/40 border border-red-700/50 text-red-300 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- CHG-92608: Control de cierre de sesión por inactividad --}}
<script>
(function() {
    var LIMIT_S = 30 * 60, WARN_S = 3 * 60, WARN_AT = LIMIT_S - WARN_S;
    var LOGOUT  = {!! json_encode(route('admin.logout')) !!};
    var TOKEN   = function() { return document.querySelector('meta[name="csrf-token"]').content; };
    var elapsed = 0, phase = 'idle', tick = null, autoOut = null;
    var elWarn, elExpired, elCd, elBar;

    function buildModals() {
        elWarn = document.createElement('div');
        elWarn.setAttribute('style',
            'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
            'z-index:2147483646;background:rgba(0,0,0,.6);' +
            'align-items:center;justify-content:center;');
        elWarn.innerHTML =
            '<div style="background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px 32px;' +
            'width:380px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.5);">' +
              '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">' +
                '<div style="width:40px;height:40px;min-width:40px;border-radius:50%;background:#451a03;' +
                'display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1;">⏰</div>' +
                '<div>' +
                  '<p style="font-size:14px;font-weight:700;color:#f1f5f9;margin:0;">Sesión por expirar</p>' +
                  '<p style="font-size:12px;color:#94a3b8;margin:2px 0 0;">Tu sesión cerrará en ' +
                    '<strong id="adm-cd" style="color:#f59e0b;"></strong>' +
                  '</p>' +
                '</div>' +
              '</div>' +
              '<div style="background:#334155;border-radius:99px;height:4px;margin-bottom:20px;overflow:hidden;">' +
                '<div id="adm-bar" style="width:100%;background:#f59e0b;height:100%;border-radius:99px;transition:width 1s linear;"></div>' +
              '</div>' +
              '<div style="display:flex;gap:8px;">' +
                '<button onclick="admLogout()" style="flex:1;padding:9px;border-radius:9px;border:1px solid #334155;' +
                'background:transparent;color:#94a3b8;font-size:13px;font-weight:600;cursor:pointer;">Cerrar sesión</button>' +
                '<button onclick="admKeep()" style="flex:2;padding:9px;border-radius:9px;border:none;' +
                'background:#6366f1;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">Continuar trabajando</button>' +
              '</div>' +
            '</div>';
        document.body.appendChild(elWarn);
        elCd  = document.getElementById('adm-cd');
        elBar = document.getElementById('adm-bar');

        elExpired = document.createElement('div');
        elExpired.setAttribute('style',
            'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
            'z-index:2147483647;background:rgba(0,0,0,.8);' +
            'align-items:center;justify-content:center;');
        elExpired.innerHTML =
            '<div style="background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px;' +
            'width:360px;max-width:92vw;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.6);">' +
              '<div style="width:52px;height:52px;min-width:52px;border-radius:50%;background:#450a0a;' +
              'display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;line-height:1;">🔒</div>' +
              '<p style="font-size:16px;font-weight:700;color:#f1f5f9;margin:0 0 8px;">Sesión cerrada</p>' +
              '<p style="font-size:13px;color:#94a3b8;margin:0 0 24px;">Tu sesión expiró por inactividad.</p>' +
              '<button onclick="admLogout()" style="width:100%;padding:10px;border-radius:10px;border:none;' +
              'background:#6366f1;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">Iniciar sesión</button>' +
            '</div>';
        document.body.appendChild(elExpired);
    }

    function fmtCd(s) { var m=Math.floor(s/60),r=s%60; return m>0?m+' min '+r+' s':r+' s'; }

    function startTick() {
        clearInterval(tick);
        tick = setInterval(function() {
            elapsed++;
            if (elapsed >= LIMIT_S) {
                phase = 'expired'; clearInterval(tick);
                elWarn.style.display = 'none';
                elExpired.style.display = 'flex';
                autoOut = setTimeout(admLogout, 8000);
            } else if (elapsed >= WARN_AT) {
                if (phase !== 'warn') { phase = 'warn'; elWarn.style.display = 'flex'; }
                var cd = LIMIT_S - elapsed;
                if (elCd)  elCd.textContent = fmtCd(cd);
                if (elBar) elBar.style.width = Math.round(cd/WARN_S*100) + '%';
            }
        }, 1000);
    }

    window.admKeep = function() {
        fetch('/ping-session', { method:'POST', headers:{ 'X-CSRF-TOKEN': TOKEN(), 'Content-Type':'application/json' } }).catch(function(){});
        elapsed = 0; phase = 'idle';
        elWarn.style.display = 'none';
        if (elBar) elBar.style.width = '100%';
        startTick();
    };

    window.admLogout = function() {
        clearInterval(tick); clearTimeout(autoOut);
        var f = document.createElement('form'); f.method='POST'; f.action=LOGOUT;
        var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value=TOKEN();
        f.appendChild(t); document.body.appendChild(f); f.submit();
    };

    document.addEventListener('DOMContentLoaded', function() {
        buildModals();
        ['mousemove','keydown','click','scroll','touchstart'].forEach(function(ev) {
            document.addEventListener(ev, function() {
                if (phase === 'expired' || phase === 'warn') return;
                elapsed = 0;
            }, { passive: true });
        });
        startTick();
    });
})();
</script>
@include('partials.avisos')
</body>
</html>
