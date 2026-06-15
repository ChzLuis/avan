<x-portal-layout layout="comercial" :project="$project" pageTitle="Rentabilidad">
<div style="display:flex;flex-direction:column;height:100%;overflow:auto;background:#F8F9FB;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Rentabilidad de Productos</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Ganancia neta, margen y costo por producto</p>
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
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
    <label style="font-size:11px;color:#9CA3AF;">Hasta</label>
    <input type="date" name="hasta" value="{{ $hasta }}"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
    <button type="submit"
            style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;">
        Filtrar
    </button>
</form>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px;flex-shrink:0;">
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Ingresos brutos</p>
        <p style="font-size:24px;font-weight:800;color:#111827;margin:0;">S/ {{ number_format($totalIngresos, 2) }}</p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Costo total</p>
        <p style="font-size:24px;font-weight:800;color:#F97316;margin:0;">S/ {{ number_format($totalCosto, 2) }}</p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-left:4px solid #16A34A;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Ganancia neta</p>
        <p style="font-size:24px;font-weight:800;margin:0;color:{{ $totalGanancia >= 0 ? '#16A34A' : '#EF4444' }};">
            S/ {{ number_format($totalGanancia, 2) }}
        </p>
    </div>
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:11px;color:#9CA3AF;margin:0 0 6px;">Margen global</p>
        <p style="font-size:24px;font-weight:800;margin:0;color:{{ $margenGlobal >= 0 ? '#2563EB' : '#EF4444' }};">
            {{ $margenGlobal }}%
        </p>
        @if($sinCosto > 0)
        <p style="font-size:11px;color:#D97706;margin:4px 0 0;">{{ $sinCosto }} ítems sin costo</p>
        @endif
    </div>
</div>

{{-- Gráfico por día --}}
@if($porDia->count() > 1)
@php $maxVal = max(1, $porDia->max(fn($d) => abs($d['ganancia']))); @endphp
<div style="padding:0 16px 12px;flex-shrink:0;">
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">Ganancia neta por día</p>
        <div style="display:flex;align-items:flex-end;gap:3px;height:70px;">
            @foreach($porDia as $dia)
            @php
                $pct = round(abs($dia['ganancia']) / $maxVal * 100);
                $isNeg = $dia['ganancia'] < 0;
            @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;position:relative;min-width:0;"
                 title="{{ $dia['fecha'] }}: S/ {{ number_format($dia['ganancia'],2) }}">
                <div style="width:100%;border-radius:3px 3px 0 0;background:{{ $isNeg ? '#FCA5A5' : '#86EFAC' }};height:{{ max(4,$pct) }}%;transition:height .2s;"></div>
                <span style="font-size:8px;color:#9CA3AF;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:100%;text-align:center;">{{ $dia['fecha'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Tabla --}}
<div style="padding:0 16px 16px;">
    @if($porProducto->isEmpty())
    <div style="text-align:center;padding:48px 20px;color:#9CA3AF;font-size:13px;">
        Sin datos en el período. Asegúrate de que los productos tengan costo registrado.
    </div>
    @else

    @if($sinCosto > 0)
    <div style="display:flex;align-items:flex-start;gap:8px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:12px;color:#D97706;">
        <svg style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span>Algunos productos no tienen costo registrado — su ganancia aparece igual a los ingresos.
            <a href="{{ route('bixosales.catalog.products.index') }}" style="text-decoration:underline;font-weight:600;">Actualizar costos en Catálogo</a>
        </span>
    </div>
    @endif

    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <thead>
                <tr style="background:#F8F9FB;border-bottom:1px solid #E5E8EF;">
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Producto</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Vendidos</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Ingresos</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Costo unit.</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Costo total</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Ganancia neta</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Margen</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;width:100px;">Barra</th>
                </tr>
            </thead>
            <tbody>
                @php $maxGanancia = max(1, $porProducto->max(fn($p) => abs($p['ganancia']))); @endphp
                @foreach($porProducto as $prod)
                @php
                    $isNeg = $prod['ganancia'] < 0;
                    $barPct = round(abs($prod['ganancia']) / $maxGanancia * 100);
                    $sinCostoFlag = $prod['costo_unit'] == 0;
                    $margenStyle = $isNeg
                        ? 'background:#FEE2E2;color:#EF4444;border-color:#FECACA;'
                        : ($prod['margen'] >= 30 ? 'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;' : 'background:#FFFBEB;color:#D97706;border-color:#FDE68A;');
                @endphp
                <tr style="border-bottom:1px solid #F3F4F6;"
                    onmouseover="this.style.background='#F8F9FB'" onmouseout="this.style.background='#fff'">
                    <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#111827;">
                        {{ $prod['nombre'] }}
                        @if($sinCostoFlag)
                        <span style="font-size:11px;color:#D97706;" title="Sin costo registrado"> ⚠</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:right;color:#374151;">{{ $prod['vendidos'] }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:600;color:#111827;">S/ {{ number_format($prod['ingresos'], 2) }}</td>
                    <td style="padding:10px 14px;text-align:right;color:#9CA3AF;">
                        @if($sinCostoFlag)
                        <span style="color:#FCD34D;font-size:11px;">—</span>
                        @else
                        S/ {{ number_format($prod['costo_unit'], 2) }}
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:right;color:#F97316;">S/ {{ number_format($prod['costo_total'], 2) }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:{{ $isNeg ? '#EF4444' : '#16A34A' }};">
                        {{ $isNeg ? '-' : '+' }}S/ {{ number_format(abs($prod['ganancia']), 2) }}
                    </td>
                    <td style="padding:10px 14px;text-align:right;">
                        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;border:1px solid;{{ $margenStyle }}">
                            {{ $prod['margen'] }}%
                        </span>
                    </td>
                    <td style="padding:10px 14px;">
                        <div style="width:100%;height:5px;background:#E5E8EF;border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:{{ $barPct }}%;background:{{ $isNeg ? '#FCA5A5' : '#4ADE80' }};border-radius:99px;"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8F9FB;border-top:2px solid #E5E8EF;">
                    <td style="padding:10px 14px;font-weight:700;color:#6B7280;font-size:11px;">TOTAL</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#374151;">{{ $porProducto->sum('vendidos') }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#111827;">S/ {{ number_format($totalIngresos, 2) }}</td>
                    <td></td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#F97316;">S/ {{ number_format($totalCosto, 2) }}</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;color:{{ $totalGanancia >= 0 ? '#16A34A' : '#EF4444' }};">
                        S/ {{ number_format($totalGanancia, 2) }}
                    </td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:{{ $margenGlobal >= 0 ? '#2563EB' : '#EF4444' }};">{{ $margenGlobal }}%</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

</div>
</x-portal-layout>
