<x-admin-layout title="Dashboard">

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @php
            $cards = [
                ['label'=>'Proyectos totales',  'value'=>$stats['total_projects'],  'color'=>'indigo', 'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['label'=>'Proyectos activos',  'value'=>$stats['active_projects'], 'color'=>'green',  'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label'=>'Usuarios totales',   'value'=>$stats['total_users'],     'color'=>'blue',   'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['label'=>'Super Admins',       'value'=>$stats['superadmins'],     'color'=>'purple', 'icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ['label'=>'Órdenes totales',    'value'=>$stats['total_orders'],    'color'=>'orange', 'icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['label'=>'Facturas emitidas',  'value'=>$stats['total_invoices'],  'color'=>'teal',   'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ];
            $colorMap = [
                'indigo'=>'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                'green' =>'bg-green-500/10  text-green-400  border-green-500/20',
                'blue'  =>'bg-blue-500/10   text-blue-400   border-blue-500/20',
                'purple'=>'bg-purple-500/10 text-purple-400 border-purple-500/20',
                'orange'=>'bg-orange-500/10 text-orange-400 border-orange-500/20',
                'teal'  =>'bg-teal-500/10   text-teal-400   border-teal-500/20',
            ];
        @endphp

        @foreach($cards as $card)
        <div class="rounded-2xl p-4 border" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center {{ $colorMap[$card['color']] }} border">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-white">{{ number_format($card['value']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Tablas recientes --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Proyectos recientes --}}
        <div class="rounded-2xl border overflow-hidden" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:rgba(255,255,255,0.08);">
                <h3 class="text-sm font-semibold text-white">Proyectos recientes</h3>
                <a href="{{ route('admin.projects') }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver todos →</a>
            </div>
            <div class="divide-y" style="divide-color:rgba(255,255,255,0.05);">
                @forelse($recentProjects as $p)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white">{{ $p->name }}</p>
                        <p class="text-xs text-gray-500">{{ $p->owner->name ?? '—' }} · /p/{{ $p->slug }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                 {{ $p->is_active ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                        {{ $p->is_active ? 'Activo' : 'Suspendido' }}
                    </span>
                </div>
                @empty
                <p class="px-5 py-4 text-sm text-gray-600">Sin proyectos aún.</p>
                @endforelse
            </div>
        </div>

        {{-- Usuarios recientes --}}
        <div class="rounded-2xl border overflow-hidden" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:rgba(255,255,255,0.08);">
                <h3 class="text-sm font-semibold text-white">Usuarios recientes</h3>
                <a href="{{ route('admin.users') }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver todos →</a>
            </div>
            <div class="divide-y" style="divide-color:rgba(255,255,255,0.05);">
                @forelse($recentUsers as $u)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                         style="background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff;">
                        {{ strtoupper(substr($u->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $u->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $u->email }}</p>
                    </div>
                    @if($u->is_superadmin)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/15 text-purple-400 font-medium">Admin</span>
                    @endif
                </div>
                @empty
                <p class="px-5 py-4 text-sm text-gray-600">Sin usuarios aún.</p>
                @endforelse
            </div>
        </div>
    </div>

</x-admin-layout>
