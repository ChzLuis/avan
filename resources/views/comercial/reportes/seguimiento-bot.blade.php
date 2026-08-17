<x-portal-layout layout="comercial" :project="$project" pageTitle="Seguimiento Bot">
<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;background:#F8F9FB;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;flex-shrink:0;">
    <div>
        <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Seguimiento de pedidos Bot</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Estado individual de cada participante</p>
    </div>
    <form method="GET" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <input type="date" name="desde" value="{{ $desde }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;min-height:44px;">
        <span style="font-size:11px;color:#9CA3AF;">→</span>
        <input type="date" name="hasta" value="{{ $hasta }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;min-height:44px;">
        <select name="status" style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;background:#fff;font-family:inherit;">
            <option value="">Todos</option>
            <option value="pendiente" @selected($status==='pendiente')>Pendiente</option>
            <option value="pagado"    @selected($status==='pagado')>Pagado</option>
            <option value="enviado"   @selected($status==='enviado')>Enviado</option>
            <option value="cancelado" @selected($status==='cancelado')>Cancelado</option>
        </select>
        <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Nombre, DNI o celular"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 12px;outline:none;font-family:inherit;width:180px;">
        <button type="submit" style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;min-height:44px;">Buscar</button>
    </form>
</div>

{{-- Stats rápidas --}}
<div style="padding:8px 20px;background:#fff;border-bottom:1px solid #E5E8EF;display:flex;align-items:center;gap:16px;font-size:11px;flex-shrink:0;">
    <span style="color:#6B7280;">{{ $ventas->count() }} resultado(s)</span>
    <span style="color:#D97706;">{{ $ventas->where('status','pendiente')->count() }} pendientes</span>
    <span style="color:#2563EB;">{{ $ventas->where('status','pagado')->count() }} pagados</span>
    <span style="color:#16A34A;">{{ $ventas->where('status','enviado')->count() }} enviados</span>
    @if($tiempoPromedio)
    <span style="color:#7C3AED;">⏱ Tiempo promedio: {{ round($tiempoPromedio, 1) }}h</span>
    @endif
    <a href="{{ route('bixosales.reportes.ventas') }}"
       style="margin-left:auto;color:#2563EB;font-size:11px;text-decoration:none;"
       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
       ← Volver al resumen
    </a>
</div>

{{-- Tabla --}}
<div style="flex:1;overflow:auto;padding:16px;">
    @if($ventas->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#9CA3AF;">
        <p style="font-size:36px;margin-bottom:10px;">📭</p>
        <p style="font-size:13px;">No hay pedidos con esos filtros</p>
    </div>
    @else
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <thead>
                <tr style="background:#F8F9FB;border-bottom:1px solid #E5E8EF;">
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Participante</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Plan</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Monto</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Tickets</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Fecha</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Estado</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Comprobante</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $v)
                @php
                $statusStyle = match($v->status) {
                    'pendiente' => 'background:#FFFBEB;color:#D97706;border-color:#FDE68A;',
                    'pagado'    => 'background:#DBEAFE;color:#2563EB;border-color:#BFDBFE;',
                    'enviado'   => 'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;',
                    'cancelado' => 'background:#FEE2E2;color:#EF4444;border-color:#FECACA;',
                    default     => 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
                };
                $statusLabel = match($v->status) {
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado',
                    'enviado'   => 'Enviado ✓', 'cancelado' => 'Cancelado', default => $v->status,
                };
                @endphp
                <tr style="border-bottom:1px solid #F3F4F6;"
                    onmouseover="this.style.background='#F8F9FB'" onmouseout="this.style.background='#fff'">
                    <td style="padding:10px 14px;">
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $v->nombre ?? '—' }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">DNI: {{ $v->dni ?? '—' }} · {{ $v->wa_number }}</p>
                        @if($v->ciudad)
                        <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">📍 {{ $v->ciudad }}</p>
                        @endif
                    </td>
                    <td style="padding:10px 14px;font-weight:600;color:#7C3AED;">{{ $v->plan_nombre }}</td>
                    <td style="padding:10px 14px;font-weight:700;color:#111827;">S/ {{ number_format($v->monto, 2) }}</td>
                    <td style="padding:10px 14px;color:#374151;">
                        {{ $v->tickets }}
                        @if($v->ticket_numbers)
                        <br><span style="color:#6366F1;font-size:10px;">#{{ implode(', #', array_map(fn($n) => str_pad($n,5,'0',STR_PAD_LEFT), $v->ticket_numbers)) }}</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px;color:#9CA3AF;white-space:nowrap;">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 14px;">
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;border:1px solid;{{ $statusStyle }}">{{ $statusLabel }}</span>
                    </td>
                    <td style="padding:10px 14px;">
                        @if($v->payment_proof)
                        <a href="{{ asset($v->payment_proof) }}" target="_blank"
                           style="font-size:11px;color:#6366F1;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            🧾 Ver
                        </a>
                        @else
                        <span style="color:#D1D5DB;">—</span>
                        @endif
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
