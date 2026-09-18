<x-portal-layout layout="comercial" :project="$project" pageTitle="Top Productos">
<div style="display:flex;flex-direction:column;height:100%;overflow:auto;background:#F8F9FB;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Top Productos Vendidos</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Ranking por ingresos en el período</p>
    </div>
    <a href="?desde={{ $desde }}&hasta={{ $hasta }}&export=csv"
       style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:#16A34A;color:#fff;border-radius:9px;font-size:12px;font-weight:600;text-decoration:none;">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<form method="GET" style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:#fff;border-bottom:1px solid #E5E8EF;flex-shrink:0;">
    <label style="font-size:11px;color:#9CA3AF;">Desde</label>
    <input type="date" name="desde" value="{{ $desde }}"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;min-height:44px;">
    <label style="font-size:11px;color:#9CA3AF;">Hasta</label>
    <input type="date" name="hasta" value="{{ $hasta }}"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;min-height:44px;">
    <button type="submit"
            style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;">
        Filtrar
    </button>
</form>

<div style="padding:16px;">
    @if($top->isEmpty())
    <div style="text-align:center;padding:48px 20px;color:#9CA3AF;font-size:13px;">Sin datos en el período seleccionado.</div>
    @else
    @php $totalIngresos = $top->sum('ingresos'); @endphp
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <thead>
                <tr style="background:#F8F9FB;border-bottom:1px solid #E5E8EF;">
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">#</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Producto</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Unidades</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Ingresos</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">% del total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top as $i => $row)
                @php $pct = $totalIngresos > 0 ? round($row->ingresos / $totalIngresos * 100, 1) : 0; @endphp
                <tr style="border-bottom:1px solid #F3F4F6;"
                    onmouseover="this.style.background='#F8F9FB'" onmouseout="this.style.background='#fff'">
                    <td style="padding:10px 14px;color:#9CA3AF;font-family:monospace;font-size:11px;">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#111827;">{{ $row->name }}</td>
                    <td style="padding:10px 14px;text-align:right;color:#374151;">{{ number_format($row->cantidad) }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#111827;">S/ {{ number_format($row->ingresos, 2) }}</td>
                    <td style="padding:10px 14px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                            <div style="width:80px;height:5px;background:#E5E8EF;border-radius:99px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;background:#2563EB;border-radius:99px;"></div>
                            </div>
                            <span style="font-size:11px;color:#6B7280;width:36px;text-align:right;">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8F9FB;border-top:1px solid #E5E8EF;">
                    <td colspan="2" style="padding:10px 14px;font-size:11px;font-weight:700;color:#6B7280;">TOTAL</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#111827;">{{ number_format($top->sum('cantidad')) }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#111827;">S/ {{ number_format($totalIngresos, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
</div>
</x-portal-layout>
