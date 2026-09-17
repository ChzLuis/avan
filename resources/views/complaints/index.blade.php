<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Libro de Reclamaciones">

<style>
    .lr-wrap { padding: 20px; max-width: 1180px; margin: 0 auto; }
    .lr-card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; }
    .lr-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; }
    .lr-title { font-size:20px; font-weight:800; color:#111827; margin:0; }
    .lr-sub { font-size:13px; color:#6B7280; margin:4px 0 0; }
    .lr-link { font-size:12px; font-weight:600; color:#4F46E5; text-decoration:none; }
    .lr-link:hover { text-decoration:underline; }

    /* Resumen: lo primero es cuantos faltan por atender. */
    .lr-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; margin-bottom:16px; }
    .lr-kpi { background:#fff; border:1px solid #E5E7EB; border-radius:10px; padding:12px 14px; }
    .lr-kpi b { display:block; font-size:22px; font-weight:800; color:#111827; line-height:1.2; font-variant-numeric:tabular-nums; }
    .lr-kpi span { font-size:12px; color:#6B7280; }
    .lr-kpi.is-alerta { border-color:#FCA5A5; background:#FEF2F2; }
    .lr-kpi.is-alerta b { color:#B91C1C; }

    .lr-filtros { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
    .lr-filtros input, .lr-filtros select {
        height:38px; padding:0 10px; border:1px solid #D1D5DB; border-radius:8px;
        font-size:13px; color:#111827; background:#fff; min-width:0;
    }
    .lr-filtros input { flex:1 1 200px; }
    .lr-btn { height:38px; padding:0 16px; border-radius:8px; border:0; background:#4F46E5; color:#fff; font-size:13px; font-weight:700; cursor:pointer; }
    .lr-btn-plano { background:#fff; color:#374151; border:1px solid #D1D5DB; text-decoration:none; display:inline-flex; align-items:center; }

    .lr-tabla { width:100%; border-collapse:collapse; font-size:13px; }
    .lr-tabla th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#6B7280; padding:10px 12px; border-bottom:1px solid #E5E7EB; white-space:nowrap; }
    .lr-tabla td { padding:12px; border-bottom:1px solid #F3F4F6; vertical-align:top; }
    .lr-tabla tr:last-child td { border-bottom:0; }
    .lr-codigo { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:12px; font-weight:700; color:#111827; }
    .lr-quien { font-weight:600; color:#111827; }
    .lr-dato { font-size:12px; color:#6B7280; }
    .lr-detalle { max-width:340px; color:#374151; }

    .lr-pill { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; white-space:nowrap; }
    .lr-tipo-reclamo { background:#FEE2E2; color:#991B1B; }
    .lr-tipo-queja   { background:#FEF3C7; color:#92400E; }
    .lr-e-received  { background:#DBEAFE; color:#1E40AF; }
    .lr-e-in_review { background:#FEF3C7; color:#92400E; }
    .lr-e-resolved  { background:#DCFCE7; color:#166534; }
    .lr-e-closed    { background:#F3F4F6; color:#4B5563; }

    .lr-plazo { font-size:11px; font-weight:700; margin-top:4px; }
    .lr-plazo.vencido { color:#B91C1C; }
    .lr-plazo.pronto  { color:#B45309; }
    .lr-plazo.holgado { color:#6B7280; }

    .lr-estado-form { display:flex; gap:6px; align-items:center; }
    .lr-estado-form select { height:32px; padding:0 8px; border:1px solid #D1D5DB; border-radius:6px; font-size:12px; background:#fff; }
    .lr-estado-form button { height:32px; padding:0 10px; border:0; border-radius:6px; background:#EEF2FF; color:#4338CA; font-size:12px; font-weight:700; cursor:pointer; }

    .lr-vacio { padding:44px 20px; text-align:center; color:#6B7280; }
    .lr-vacio b { display:block; color:#111827; font-size:15px; margin-bottom:6px; }

    /* En movil la tabla se lee de lado; que scrollee ella, no la pagina. */
    .lr-scroll { overflow-x:auto; }
    @media (max-width:640px) {
        .lr-wrap { padding:14px; }
        .lr-detalle { max-width:220px; }
    }
</style>

<div class="lr-wrap">
    @if(session('success'))
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:9px;background:#ECFDF5;color:#065F46;font-size:13px;font-weight:600">{{ session('success') }}</div>
    @endif

    <div class="lr-head">
        <div>
            <h1 class="lr-title">Libro de Reclamaciones</h1>
            <p class="lr-sub">
                Lo que registran tus clientes en tu tienda. INDECOPI da {{ \App\Http\Controllers\ComplaintController::PLAZO_HABILES }} días hábiles para responder cada uno.
            </p>
        </div>
        <a class="lr-link" href="{{ route('public.complaints.short', $project->slug) }}" target="_blank" rel="noopener">Ver el formulario público ↗</a>
    </div>

    @php
        $sinAtender = ($conteos['received'] ?? 0) + ($conteos['in_review'] ?? 0);
    @endphp
    <div class="lr-kpis">
        <div class="lr-kpi"><b>{{ $total }}</b><span>Registrados</span></div>
        <div class="lr-kpi {{ $sinAtender ? 'is-alerta' : '' }}"><b>{{ $sinAtender }}</b><span>Por atender</span></div>
        <div class="lr-kpi"><b>{{ $conteos['resolved'] ?? 0 }}</b><span>Resueltos</span></div>
        <div class="lr-kpi"><b>{{ $conteos['closed'] ?? 0 }}</b><span>Cerrados</span></div>
    </div>

    <form class="lr-filtros" method="get">
        <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar por código, nombre, documento o correo">
        <select name="estado">
            <option value="">Todos los estados</option>
            @foreach($estados as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected($filtros['estado'] === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <select name="tipo">
            <option value="">Reclamos y quejas</option>
            <option value="reclamo" @selected($filtros['tipo'] === 'reclamo')>Solo reclamos</option>
            <option value="queja" @selected($filtros['tipo'] === 'queja')>Solo quejas</option>
        </select>
        <button class="lr-btn" type="submit">Filtrar</button>
        @if($filtros['q'] || $filtros['estado'] || $filtros['tipo'])
            <a class="lr-btn lr-btn-plano" href="{{ route('complaints.index') }}">Limpiar</a>
        @endif
    </form>

    <div class="lr-card">
        @if($complaints->isEmpty())
            <div class="lr-vacio">
                <b>{{ $total ? 'Ningún registro con esos filtros' : 'Todavía no hay reclamos ni quejas' }}</b>
                {{ $total
                    ? 'Prueba quitando los filtros.'
                    : 'Cuando un cliente registre uno en tu tienda aparecerá aquí, y también te llegará por correo.' }}
            </div>
        @else
        <div class="lr-scroll">
        <table class="lr-tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Consumidor</th>
                    <th>Tipo</th>
                    <th>Qué pasó</th>
                    <th>Registrado</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($complaints as $c)
                @php
                    $vence = \App\Http\Controllers\ComplaintController::vence($c);
                    $abierto = in_array($c->status, ['received', 'in_review'], true);
                    $dias = (int) now()->startOfDay()->diffInDays($vence->copy()->startOfDay(), false);
                @endphp
                <tr>
                    <td>
                        <div class="lr-codigo">{{ $c->code }}</div>
                        @if($c->amount)<div class="lr-dato">S/ {{ number_format((float) $c->amount, 2) }}</div>@endif
                    </td>
                    <td>
                        <div class="lr-quien">{{ $c->consumer_name }}</div>
                        <div class="lr-dato">{{ $c->document_type }} {{ $c->document_number }}</div>
                        <div class="lr-dato">{{ $c->email }}@if($c->phone) · {{ $c->phone }}@endif</div>
                    </td>
                    <td><span class="lr-pill lr-tipo-{{ $c->type }}">{{ ucfirst($c->type) }}</span></td>
                    <td class="lr-detalle">
                        <div class="lr-dato" style="font-weight:600;color:#374151">{{ $c->product_or_service }}</div>
                        <div style="margin-top:3px">{{ \Illuminate\Support\Str::limit($c->detail, 130) }}</div>
                        <div class="lr-dato" style="margin-top:4px"><b>Pide:</b> {{ \Illuminate\Support\Str::limit($c->request, 90) }}</div>
                    </td>
                    <td>
                        <div class="lr-dato">{{ $c->created_at->format('d/m/Y H:i') }}</div>
                        @if($abierto)
                            @if($dias < 0)
                                <div class="lr-plazo vencido">Venció hace {{ abs($dias) }} d</div>
                            @elseif($dias <= 3)
                                <div class="lr-plazo pronto">Vence en {{ $dias }} d</div>
                            @else
                                <div class="lr-plazo holgado">Vence {{ $vence->format('d/m') }}</div>
                            @endif
                        @endif
                    </td>
                    <td>
                        @can('settings.negocio')
                        <form class="lr-estado-form" method="post" action="{{ route('complaints.status', $c) }}">
                            @csrf @method('patch')
                            <select name="status">
                                @foreach($estados as $clave => $etiqueta)
                                    <option value="{{ $clave }}" @selected($c->status === $clave)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <button type="submit">Guardar</button>
                        </form>
                        @else
                            <span class="lr-pill lr-e-{{ $c->status }}">{{ $estados[$c->status] ?? $c->status }}</span>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    @if($complaints->hasPages())
        <div style="margin-top:16px">{{ $complaints->links() }}</div>
    @endif
</div>

</x-portal-layout>
