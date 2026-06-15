<x-portal-layout layout="comercial" :project="$project" pageTitle="Reporte de Ventas">
<div style="display:flex;flex-direction:column;height:100%;overflow:auto;background:#F8F9FB;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Reporte de Ventas</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Todos los pedidos con filtros por período, canal y vendedor</p>
    </div>
    <a href="?desde={{ $desde }}&hasta={{ $hasta }}&canal={{ $canal }}&vendedor={{ $vendedor }}&estado={{ $estado }}&export=csv"
       style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:#16A34A;color:#fff;border-radius:9px;font-size:12px;font-weight:600;text-decoration:none;">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 16px;background:#fff;border-bottom:1px solid #E5E8EF;flex-shrink:0;">
    <label style="font-size:11px;color:#9CA3AF;">Desde</label>
    <input type="date" name="desde" value="{{ $desde }}"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
    <label style="font-size:11px;color:#9CA3AF;">Hasta</label>
    <input type="date" name="hasta" value="{{ $hasta }}"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
    <select name="canal" style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;background:#fff;font-family:inherit;">
        <option value="">Todos los canales</option>
        <option value="web" @selected($canal==='web')>Web</option>
        <option value="pos" @selected($canal==='pos')>POS</option>
        <option value="whatsapp" @selected($canal==='whatsapp')>WhatsApp</option>
    </select>
    <select name="estado" style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;background:#fff;font-family:inherit;">
        <option value="">Todos los estados</option>
        <option value="pending" @selected($estado==='pending')>Pendiente</option>
        <option value="process" @selected($estado==='process')>En proceso</option>
        <option value="done" @selected($estado==='done')>Completado</option>
        <option value="cancelled" @selected($estado==='cancelled')>Cancelado</option>
    </select>
    @if($empleados->count())
    <select name="vendedor" style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;background:#fff;font-family:inherit;">
        <option value="">Todos los vendedores</option>
        @foreach($empleados as $emp)
        <option value="{{ $emp->id }}" @selected((string)$vendedor===(string)$emp->id)>{{ $emp->name }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit"
            style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;">
        Filtrar
    </button>
</form>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px;flex-shrink:0;">
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Total pedidos</p>
        <p style="font-size:24px;font-weight:800;color:#111827;margin:0;">{{ number_format($totales['count']) }}</p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Ingresos</p>
        <p style="font-size:24px;font-weight:800;color:#16A34A;margin:0;">S/ {{ number_format($totales['ingresos'], 2) }}</p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-left:4px solid #16A34A;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;display:flex;align-items:center;gap:4px;">
            <svg style="width:12px;height:12px;color:#16A34A;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
        </p>
        <p style="font-size:24px;font-weight:800;color:#16A34A;margin:0;">{{ number_format($totales['whatsapp']) }}</p>
        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">S/ {{ number_format($totales['ingresos_wa'], 2) }}</p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Cancelados</p>
        <p style="font-size:24px;font-weight:800;color:#EF4444;margin:0;">{{ number_format($totales['cancelados']) }}</p>
    </div>
</div>

{{-- Tabla --}}
<div style="padding:0 16px 16px;">
    @if($ordenes->isEmpty())
    <div style="text-align:center;padding:48px 20px;color:#9CA3AF;font-size:13px;">Sin pedidos en el período seleccionado.</div>
    @else
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <thead>
                <tr style="background:#F8F9FB;border-bottom:1px solid #E5E8EF;">
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Fecha</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Cliente</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Canal</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Productos</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Total</th>
                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordenes as $orden)
                @php
                    $statusStyle = match($orden->status) {
                        'pending'   => 'background:#FFFBEB;color:#D97706;border-color:#FDE68A;',
                        'process'   => 'background:#DBEAFE;color:#2563EB;border-color:#BFDBFE;',
                        'done'      => 'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;',
                        'cancelled' => 'background:#FEE2E2;color:#EF4444;border-color:#FECACA;',
                        default     => 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
                    };
                    $statusLabel = match($orden->status) {
                        'pending'   => 'Pendiente',
                        'process'   => 'En proceso',
                        'done'      => 'Completado',
                        'cancelled' => 'Cancelado',
                        default     => ucfirst($orden->status),
                    };
                    $isWa = $orden->sales_channel === 'whatsapp';
                    $waStatusLabels = ['pending'=>'Pend. pago','pago_recibido'=>'Pago recibido','entregado'=>'Entregado','problema'=>'Problema'];
                @endphp
                <tr style="{{ $isWa ? 'background:#F0FDF4;' : '' }}border-bottom:1px solid #F3F4F6;"
                    onmouseover="this.style.background='#F8F9FB'" onmouseout="this.style.background='{{ $isWa ? '#F0FDF4' : '#fff' }}'">
                    <td style="padding:10px 14px;color:#9CA3AF;white-space:nowrap;">{{ $orden->created_at->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 14px;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $orden->client_name }}</p>
                        @if($isWa && $orden->wa_number)
                        <p style="font-size:11px;color:#16A34A;margin:2px 0 0;">{{ $orden->wa_number }}</p>
                        @endif
                    </td>
                    <td style="padding:10px 14px;">
                        @if($isWa)
                        <span style="font-size:10px;font-weight:600;background:#DCFCE7;color:#16A34A;padding:2px 8px;border-radius:99px;border:1px solid #BBF7D0;">WA</span>
                        @if($orden->wa_status)
                        <p style="font-size:10px;color:#9CA3AF;margin:2px 0 0;">{{ $waStatusLabels[$orden->wa_status] ?? $orden->wa_status }}</p>
                        @endif
                        @elseif($orden->sales_channel === 'pos')
                        <span style="font-size:10px;font-weight:600;background:#F5F3FF;color:#7C3AED;padding:2px 8px;border-radius:99px;border:1px solid #DDD6FE;">POS</span>
                        @elseif($orden->sales_channel === 'web')
                        <span style="font-size:10px;font-weight:600;background:#DBEAFE;color:#2563EB;padding:2px 8px;border-radius:99px;border:1px solid #BFDBFE;">Web</span>
                        @else
                        <span style="font-size:11px;color:#9CA3AF;">{{ $orden->sales_channel ?? '-' }}</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px;font-size:11px;color:#6B7280;">
                        @if($orden->items && $orden->items->count())
                        <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:2px;">
                            @foreach($orden->items->take(3) as $item)
                            <li>{{ $item->quantity }}× {{ Str::limit($item->name, 28) }}</li>
                            @endforeach
                            @if($orden->items->count() > 3)
                            <li style="color:#9CA3AF;">+{{ $orden->items->count() - 3 }} más</li>
                            @endif
                        </ul>
                        @else
                        <span style="color:#D1D5DB;">—</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#111827;white-space:nowrap;">S/ {{ number_format($orden->total, 2) }}</td>
                    <td style="padding:10px 14px;text-align:center;">
                        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;border:1px solid;{{ $statusStyle }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>
</x-portal-layout>
