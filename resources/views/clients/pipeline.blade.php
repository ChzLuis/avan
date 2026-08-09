<x-portal-layout layout="panel" :project="$project" pageTitle="Pipeline de ventas">

@php $csrf = csrf_token(); @endphp

<div class="p-4 h-full flex flex-col overflow-hidden" x-data="pipelineBoard()">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-4 flex-shrink-0">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Pipeline de ventas</h1>
            <p class="text-xs text-gray-500">Leads capturados por el Copilot. Arrastra las tarjetas para cambiar de etapa.</p>
        </div>
        <a href="{{ route('clients') }}" class="text-sm text-indigo-600 hover:underline">Ver lista de clientes →</a>
    </div>

    {{-- Tablero Kanban --}}
    <div class="flex gap-3 flex-1 overflow-x-auto pb-2">
        @foreach($etapas as $key => $label)
            <div class="flex-shrink-0 w-64 bg-gray-50 rounded-xl border border-gray-200 flex flex-col"
                 @dragover.prevent
                 @drop="onDrop($event, '{{ $key }}')">
                <div class="px-3 py-2.5 border-b border-gray-100 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700">{{ $label }}</span>
                    <span class="text-xs font-bold text-gray-400 bg-white rounded-full px-2 py-0.5">{{ $porEtapa[$key]->count() }}</span>
                </div>
                <div class="p-2 space-y-2 overflow-y-auto flex-1" style="min-height:120px">
                    @forelse($porEtapa[$key] as $c)
                        @php
                            $temp = $c->lead_temp ?? 'nuevo';
                            $tc = ['caliente'=>['#fef2f2','#b91c1c','🔥'],'tibio'=>['#fffbeb','#b45309','🟡'],'frio'=>['#eff6ff','#1d4ed8','🔵']][$temp] ?? ['#f8fafc','#64748b','⚪'];
                        @endphp
                        <div class="bg-white rounded-lg border border-gray-200 p-2.5 cursor-grab hover:shadow-sm transition-shadow"
                             draggable="true"
                             @dragstart="dragId={{ $c->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="text-sm font-semibold text-gray-800 truncate">{{ $c->name }}</div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded flex-shrink-0"
                                      style="background:{{ $tc[0] }};color:{{ $tc[1] }}">{{ $tc[2] }} {{ $c->lead_score }}%</span>
                            </div>
                            @if($c->empresa)<div class="text-xs text-gray-500 truncate mt-0.5">{{ $c->empresa }}</div>@endif
                            @if($c->phone)<div class="text-[11px] text-gray-400 mt-0.5">{{ $c->phone }}</div>@endif
                            @if($c->producto_interes)<div class="text-[11px] text-indigo-600 mt-1 truncate">{{ $c->producto_interes }}</div>@endif
                            @if($c->monto_estimado)<div class="text-[11px] font-semibold text-emerald-700 mt-0.5">S/ {{ number_format($c->monto_estimado,2) }}</div>@endif
                        </div>
                    @empty
                        <div class="text-[11px] text-gray-300 text-center py-6">Sin leads</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
function pipelineBoard() {
    return {
        dragId: null,
        async onDrop(e, etapa) {
            if (!this.dragId) return;
            const id = this.dragId; this.dragId = null;
            try {
                const r = await fetch(`{{ url('clients') }}/${id}/stage`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}' },
                    body: JSON.stringify({ etapa }),
                });
                if (r.ok) location.reload();
                else alert('No se pudo mover el lead.');
            } catch (err) { alert('Error de conexión.'); }
        },
    };
}
</script>
</x-portal-layout>
