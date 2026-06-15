<x-portal-layout layout="comercial" :project="$project" pageTitle="Reporte · Ventas Bot">
<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;background:#F8F9FB;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;flex-shrink:0;">
    <div>
        <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Ventas por WhatsApp Bot</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Resumen de ingresos y pedidos del bot</p>
    </div>
    <form method="GET" style="display:flex;align-items:center;gap:6px;">
        <input type="date" name="desde" value="{{ $desde }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
        <span style="font-size:11px;color:#9CA3AF;">→</span>
        <input type="date" name="hasta" value="{{ $hasta }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
        <button type="submit" style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;">Filtrar</button>
    </form>
</div>

<div style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:16px;">

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;">
        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;text-align:center;">
            <p style="font-size:22px;font-weight:800;color:#111827;margin:0;">{{ $totales->total ?? 0 }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">Total pedidos</p>
        </div>
        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;text-align:center;">
            <p style="font-size:22px;font-weight:800;color:#16A34A;margin:0;">S/ {{ number_format($totales->ingresos ?? 0, 2) }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">Ingresos</p>
        </div>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:14px;text-align:center;">
            <p style="font-size:22px;font-weight:800;color:#D97706;margin:0;">{{ $totales->pendientes ?? 0 }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">Pendientes</p>
        </div>
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:14px;text-align:center;">
            <p style="font-size:22px;font-weight:800;color:#2563EB;margin:0;">{{ $totales->pagados ?? 0 }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">Pagados</p>
        </div>
        <div style="background:#DCFCE7;border:1px solid #BBF7D0;border-radius:12px;padding:14px;text-align:center;">
            <p style="font-size:22px;font-weight:800;color:#16A34A;margin:0;">{{ $totales->enviados ?? 0 }}</p>
            <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;">Enviados</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

        {{-- Por plan --}}
        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #E5E8EF;">
                <h2 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Ventas por plan</h2>
            </div>
            @forelse($porPlan as $plan)
            @php $pct = ($totales->total ?? 0) > 0 ? round($plan->total / $totales->total * 100) : 0; @endphp
            <div style="padding:12px 16px;border-bottom:1px solid #F3F4F6;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:13px;font-weight:600;color:#111827;">{{ $plan->plan_nombre ?? '—' }}</span>
                    <span style="font-size:13px;font-weight:700;color:#16A34A;">S/ {{ number_format($plan->ingresos, 2) }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;background:#E5E8EF;border-radius:99px;height:5px;">
                        <div style="background:#2563EB;height:5px;border-radius:99px;width:{{ $pct }}%;"></div>
                    </div>
                    <span style="font-size:10px;color:#9CA3AF;white-space:nowrap;">{{ $plan->total }} · {{ $pct }}%</span>
                </div>
                <div style="display:flex;gap:12px;margin-top:5px;">
                    <span style="font-size:10px;color:#D97706;">{{ $plan->pendientes }} pendientes</span>
                    <span style="font-size:10px;color:#2563EB;">{{ $plan->pagados }} pagados</span>
                    <span style="font-size:10px;color:#16A34A;">{{ $plan->enviados }} enviados</span>
                </div>
            </div>
            @empty
            <div style="padding:32px 20px;text-align:center;color:#9CA3AF;font-size:13px;">Sin datos en este período</div>
            @endforelse
        </div>

        {{-- Por día --}}
        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #E5E8EF;">
                <h2 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Pedidos por día</h2>
            </div>
            @if($porDia->isNotEmpty())
            @php $maxDia = $porDia->max('total'); @endphp
            <div style="padding:12px 16px;display:flex;flex-direction:column;gap:7px;">
                @foreach($porDia as $dia)
                @php $pctDia = $maxDia > 0 ? round($dia->total / $maxDia * 100) : 0; @endphp
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;color:#6B7280;width:72px;flex-shrink:0;">{{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }}</span>
                    <div style="flex:1;background:#E5E8EF;border-radius:99px;height:5px;">
                        <div style="background:#7C3AED;height:5px;border-radius:99px;width:{{ $pctDia }}%;"></div>
                    </div>
                    <span style="font-size:11px;font-weight:600;color:#374151;width:20px;text-align:right;">{{ $dia->total }}</span>
                    <span style="font-size:11px;color:#16A34A;width:72px;text-align:right;">S/ {{ number_format($dia->ingresos, 2) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div style="padding:32px 20px;text-align:center;color:#9CA3AF;font-size:13px;">Sin datos en este período</div>
            @endif
        </div>

    </div>
</div>
</div>
</x-portal-layout>
