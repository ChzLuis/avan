<x-portal-layout layout="comercial" :project="$project" pageTitle="Pedidos Web">

@php
$stLabels = [
    'pending'    => 'Pendiente',
    'processing' => 'Procesando',
    'on-hold'    => 'En espera',
    'completed'  => 'Completado',
    'cancelled'  => 'Cancelado',
    'refunded'   => 'Reembolsado',
    'failed'     => 'Fallido',
];
$stStyle = [
    'pending'    => 'background:#FEF3C7;color:#B45309;border-color:#FDE68A;',
    'processing' => 'background:#DBEAFE;color:#1D4ED8;border-color:#BFDBFE;',
    'on-hold'    => 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
    'completed'  => 'background:#DCFCE7;color:#15803D;border-color:#BBF7D0;',
    'cancelled'  => 'background:#FEE2E2;color:#DC2626;border-color:#FECACA;',
    'refunded'   => 'background:#FEF3C7;color:#92400E;border-color:#FDE68A;',
    'failed'     => 'background:#FEE2E2;color:#991B1B;border-color:#FECACA;',
];
@endphp

<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;">

{{-- ── Header ── --}}
<div style="padding:12px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">

    {{-- Fila 1: título + KPIs --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div>
            <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Pedidos Web</h1>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">WooCommerce · {{ $project->name }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Vendido</p>
                <p style="font-size:16px;font-weight:800;color:#15803D;margin:0;">S/ {{ number_format($totalVentas,2) }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Pedidos</p>
                <p style="font-size:16px;font-weight:800;color:#111827;margin:0;">{{ $totalPedidos }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#B45309;margin:0;text-transform:uppercase;letter-spacing:.04em;">Pendientes</p>
                <p style="font-size:16px;font-weight:800;color:#D97706;margin:0;">{{ $pendientes }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#15803D;margin:0;text-transform:uppercase;letter-spacing:.04em;">Completados</p>
                <p style="font-size:14px;font-weight:700;color:#15803D;margin:0;">{{ $completados }}</p>
            </div>
            {{-- Botón sincronizar --}}
            <button onclick="sincronizar()" id="btn-sync"
                    style="display:flex;align-items:center;gap:5px;font-size:11px;background:#F3F4F6;color:#374151;padding:6px 12px;border-radius:8px;border:1px solid #E5E8EF;cursor:pointer;font-weight:600;">
                🔄 Sincronizar
            </button>
        </div>
    </div>

    {{-- Fila 2: Tabs estado --}}
    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;">
        @php
        $tabs = [
            ['',           'Todos',       '#6B7280','#F3F4F6'],
            ['pending',    'Pendiente',   '#B45309','#FEF3C7'],
            ['processing', 'Procesando',  '#1D4ED8','#EFF6FF'],
            ['completed',  'Completado',  '#15803D','#F0FDF4'],
            ['cancelled',  'Cancelado',   '#DC2626','#FFF1F2'],
            ['on-hold',    'En espera',   '#6B7280','#F9FAFB'],
        ];
        @endphp
        @foreach($tabs as [$key,$label,$color,$bg])
        <a href="{{ request()->fullUrlWithQuery(['status'=>$key,'desde'=>$desde,'hasta'=>$hasta,'buscar'=>$buscar]) }}"
           style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:99px;text-decoration:none;display:flex;align-items:center;gap:5px;
                  {{ $status===$key ? 'background:#1D4ED8;color:#fff;border:1px solid #1D4ED8;' : 'background:#fff;color:#6B7280;border:1px solid #E5E8EF;' }}">
            {{ $label }}
            <span style="font-size:10px;font-weight:800;padding:1px 5px;border-radius:99px;background:{{ $bg }};color:{{ $color }};">
                {{ $cnts->get($key, $key==='' ? $totalPedidos : 0) }}
            </span>
        </a>
        @endforeach
    </div>

    {{-- Fila 3: Filtros --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <form method="GET" action="{{ route('bixosales.woo.orders') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="date" name="desde" value="{{ $desde }}"
                   style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 10px;outline:none;font-family:inherit;color:#374151;">
            <span style="color:#9CA3AF;font-size:12px;">→</span>
            <input type="date" name="hasta" value="{{ $hasta }}"
                   style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 10px;outline:none;font-family:inherit;color:#374151;">
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Nombre, teléfono o email..."
                   style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 12px;outline:none;width:220px;font-family:inherit;color:#374151;">
            <button type="submit"
                    style="font-size:12px;background:#2563EB;color:#fff;padding:5px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
                Buscar
            </button>
            @if($buscar || $status)
            <a href="{{ route('bixosales.woo.orders',['desde'=>$desde,'hasta'=>$hasta]) }}"
               style="font-size:11px;color:#9CA3AF;text-decoration:none;">✕ Limpiar</a>
            @endif
        </form>
    </div>
</div>

{{-- ── Lista ── --}}
<div style="flex:1;overflow-y:auto;padding:12px;background:#F8F9FB;">
    @if($orders->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#9CA3AF;">
        <div style="font-size:40px;margin-bottom:10px;">🛒</div>
        <p style="font-size:13px;">No hay pedidos en este período</p>
        <button onclick="sincronizar()" style="margin-top:10px;font-size:12px;background:#2563EB;color:#fff;padding:6px 16px;border-radius:8px;border:none;cursor:pointer;">
            🔄 Sincronizar ahora
        </button>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:4px;">
    @foreach($orders as $o)
    @php
        $sty = $stStyle[$o->status] ?? 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;';
        $lbl = $stLabels[$o->status] ?? $o->status;
        $items = is_array($o->line_items) ? $o->line_items : [];
        $itemNombre = $items[0]['name'] ?? '—';
        $itemNombre = strip_tags($itemNombre);
    @endphp
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;
                transition:box-shadow .12s,border-color .12s;"
         onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.07)';this.style.borderColor='#2563EB';"
         onmouseout="this.style.boxShadow='none';this.style.borderColor='#E5E8EF';">

        {{-- Número --}}
        <div style="width:36px;height:36px;border-radius:8px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span style="font-size:11px;font-weight:800;color:#2563EB;">#{{ $o->order_number }}</span>
        </div>

        {{-- Info --}}
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:#111827;">{{ $o->client_name ?? '—' }}</span>
                @if($o->client_phone)
                <span style="font-size:11px;color:#9CA3AF;">· {{ $o->client_phone }}</span>
                @endif
                @if($o->client_email)
                <span style="font-size:11px;color:#9CA3AF;">· {{ $o->client_email }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap;">
                <span style="font-size:11px;color:#7C3AED;font-weight:600;">{{ $itemNombre }}</span>
                @if(count($items) > 1)
                <span style="font-size:10px;color:#9CA3AF;">+{{ count($items)-1 }} más</span>
                @endif
                <span style="font-size:11px;font-weight:700;color:#111827;">S/ {{ number_format($o->total,2) }}</span>
                @if($o->payment_method_title)
                <span style="font-size:10px;color:#6B7280;">· {{ $o->payment_method_title }}</span>
                @endif
                <span style="font-size:10px;color:#9CA3AF;">
                    {{ \Carbon\Carbon::parse($o->woo_created_at)->timezone('America/Lima')->locale('es')->diffForHumans() }}
                </span>
            </div>
        </div>

        {{-- Badge estado --}}
        <span style="font-size:10px;font-weight:600;padding:3px 10px;border-radius:99px;border:1px solid;white-space:nowrap;{{ $sty }}">
            {{ $lbl }}
        </span>

        {{-- Botón WA si tiene teléfono --}}
        @if($o->client_phone)
        @php $wa = preg_replace('/\D/','',$o->client_phone); @endphp
        <a href="https://wa.me/{{ $wa }}" target="_blank"
           style="font-size:18px;text-decoration:none;opacity:.7;" title="Abrir WhatsApp"
           onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.7'">💬</a>
        @endif
    </div>
    @endforeach
    </div>
    <p style="text-align:center;font-size:11px;color:#9CA3AF;margin-top:12px;">
        {{ $orders->count() }} pedidos · última sync hace
        <span id="last-sync">—</span>
    </p>
    @endif
</div>
</div>

<script>
const csrf = '{{ csrf_token() }}';

async function sincronizar() {
    const btn = document.getElementById('btn-sync');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Sincronizando...'; }
    try {
        const r = await fetch('{{ route("woo.sync") }}', { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
        const d = await r.json();
        if (d.ok) {
            if (btn) btn.innerHTML = '✓ ' + d.synced + ' sincronizados';
            setTimeout(() => location.reload(), 1000);
        } else {
            if (btn) { btn.disabled = false; btn.innerHTML = '🔄 Sincronizar'; }
            bxAviso('Error al sincronizar', 'error');
        }
    } catch(e) {
        if (btn) { btn.disabled = false; btn.innerHTML = '🔄 Sincronizar'; }
    }
}

// Mostrar última sincronización
fetch('{{ route("woo.stats") }}').then(r=>r.json()).then(d => {
    const el = document.getElementById('last-sync');
    if (el && d.last_sync) {
        const diff = Math.round((Date.now() - new Date(d.last_sync)) / 60000);
        el.textContent = diff < 1 ? 'menos de 1 min' : diff + ' min';
    }
});
</script>
</x-portal-layout>
