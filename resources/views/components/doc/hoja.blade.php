{{--
    La hoja de todo documento comercial de BIXO.

    Es el lenguaje visual compartido: la franja corporativa, la tipografía, la
    retícula y el pie. Factura, boleta, nota y guía se montan sobre esta hoja
    para que cualquier documento del sistema se reconozca como de la misma
    familia, cambien los campos que cambien.

    El "motor PDF" es el navegador (imprimir → guardar como PDF), así que el
    CSS puede ser moderno, pero la paginación se gobierna aquí: la cabecera de
    tabla se repite en cada página y ningún bloque de cierre se parte en dos.

    Colores: el PRINCIPAL es un azul marino sobrio salvo que el negocio
    configure `doc_color_primario` — un documento fiscal no puede heredar sin
    filtro la paleta de la tienda, que puede ser chillona. El ACENTO sí sale de
    la marca (`doc_color_acento`, o el `primary_color` de la tienda): así GABDE
    firma en azul marino con su naranja, y cada negocio con el suyo.
--}}
@props(['project', 'titulo' => 'Documento', 'anulado' => false])

@php
    $pri = trim((string) $project->setting('doc_color_primario')) ?: '#1E3A5F';
    $acc = trim((string) $project->setting('doc_color_acento'))
        ?: (trim((string) $project->setting('primary_color')) ?: '#F97316');

    $logo = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logo = $logo ? (str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'))) : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $titulo }}</title>
<style>
  :root { --pri: {{ $pri }}; --acc: {{ $acc }}; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; font-size: 11px; color: #1E293B; background: #fff;
         -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .hoja { max-width: 760px; margin: 0 auto; padding: 0 44px 30px; }

  /* La franja corporativa: fina, dos tiempos, sin estridencia. */
  .franja { display: flex; height: 5px; margin: 0 -44px 26px; }
  .franja span:first-child { flex: 1; background: var(--pri); }
  .franja span:last-child  { width: 140px; background: var(--acc); }

  /* ── Encabezado ─────────────────────────────────────────────── */
  .enc { display: flex; justify-content: space-between; align-items: flex-start; gap: 28px; margin-bottom: 26px; }
  .enc-emisor { display: flex; gap: 16px; align-items: flex-start; min-width: 0; }
  .enc-logo { max-height: 66px; max-width: 168px; object-fit: contain; flex-shrink: 0; }
  .enc-nombre { font-size: 19px; font-weight: 800; color: #0F172A; letter-spacing: -.3px; line-height: 1.22; }
  .enc-detalle { font-size: 10.5px; color: #64748B; margin-top: 5px; line-height: 1.65; }

  /* ── Tarjeta del comprobante ────────────────────────────────── */
  .tarjeta { border: 1.5px solid var(--pri); border-radius: 10px; min-width: 235px; max-width: 260px;
             overflow: hidden; text-align: center; flex-shrink: 0; }
  .tarjeta-cinta { height: 4px; background: var(--acc); }
  .tarjeta-cuerpo { padding: 11px 18px 13px; }
  .tarjeta-ruc { font-size: 10.5px; font-weight: 600; color: #475569; letter-spacing: .3px; }
  .tarjeta-tipo { font-size: 11px; font-weight: 800; color: var(--pri); letter-spacing: .6px; margin: 5px 0 3px; line-height: 1.35; }
  .tarjeta-numero { font-size: 21px; font-weight: 800; color: #0F172A; letter-spacing: -.4px; font-variant-numeric: tabular-nums; }
  .tarjeta-fecha { font-size: 9.5px; color: #94A3B8; margin-top: 5px; }

  .badge { display: inline-block; margin-top: 7px; padding: 3px 12px; border-radius: 20px;
           font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
  .badge-verde  { background: #DCFCE7; color: #166534; }
  .badge-ambar  { background: #FEF3C7; color: #92400E; }
  .badge-rojo   { background: #FEE2E2; color: #991B1B; }
  .badge-gris   { background: #F1F5F9; color: #475569; }

  /* ── Sección con etiqueta corporativa ("FACTURADO A", etc.) ─── */
  .secc { border-left: 3px solid var(--acc); padding: 2px 0 4px 15px; margin-bottom: 20px; }
  .secc-etiqueta { font-size: 8.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.1px; color: var(--pri); margin-bottom: 6px; }
  .secc-titulo { font-size: 14.5px; font-weight: 800; color: #0F172A; line-height: 1.35; overflow-wrap: anywhere; }
  .secc-detalle { font-size: 10.5px; color: #475569; margin-top: 4px; line-height: 1.75; }

  /* ── Datos complementarios: solo los que existen ─────────────── */
  .datos { display: flex; flex-wrap: wrap; gap: 8px 34px; margin: -6px 0 20px 18px; }
  .dato-etq { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .9px; color: #94A3B8; }
  .dato-val { font-size: 10.5px; font-weight: 600; color: #1E293B; margin-top: 1px; }

  /* ── Tabla de bienes/servicios ───────────────────────────────── */
  table.items { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 18px; }
  table.items thead { display: table-header-group; }   /* se repite en cada página */
  table.items thead tr { background: var(--pri); }
  table.items thead th { background: var(--pri); color: #fff; padding: 8px 11px;
      font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; text-align: right;
      border-top: 2px solid var(--acc); }
  table.items thead th:first-child { text-align: left; }
  table.items tbody tr { border-bottom: 1px solid #F1F5F9; page-break-inside: avoid; }
  table.items tbody td { padding: 9px 11px; font-size: 10.5px; color: #475569; text-align: right;
      font-variant-numeric: tabular-nums; vertical-align: top; }
  table.items tbody td.desc { text-align: left; font-weight: 600; color: #0F172A; overflow-wrap: anywhere; }
  table.items tbody td.total-linea { font-weight: 700; color: #0F172A; }

  /* ── Cierre: totales ─────────────────────────────────────────── */
  .cierre { display: flex; justify-content: space-between; align-items: flex-start; gap: 28px;
            margin-bottom: 16px; page-break-inside: avoid; }
  .cierre-izq { flex: 1; font-size: 10.5px; color: #64748B; line-height: 1.9; padding-top: 2px; }
  .cierre-izq strong { color: #334155; font-weight: 600; }

  .totales { min-width: 250px; }
  .totales-fila { display: flex; justify-content: space-between; padding: 6px 14px; font-size: 10.5px; color: #475569; }
  .totales-fila span:last-child { font-variant-numeric: tabular-nums; font-weight: 600; color: #1E293B; }
  .total-final { display: flex; justify-content: space-between; align-items: center; margin-top: 7px;
      background: var(--pri); color: #fff; border-radius: 8px; padding: 11px 14px;
      border-left: 4px solid var(--acc); page-break-inside: avoid; }
  .total-final .etq { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; }
  .total-final .importe { font-size: 17px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.3px; }

  /* ── El importe en letras, como banda propia ─────────────────── */
  .en-letras { background: #F8FAFC; border-top: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0;
      padding: 8px 14px; margin-bottom: 18px; font-size: 10px; font-weight: 600; color: #334155;
      letter-spacing: .4px; page-break-inside: avoid; }

  .obs { border-left: 3px solid #E2E8F0; padding: 7px 13px; margin-bottom: 16px;
      font-size: 10px; color: #64748B; line-height: 1.65; page-break-inside: avoid; }
  .obs strong { color: #334155; }

  /* ── Referencia de nota: qué modifica y por qué ──────────────── */
  .nota-ref { display: flex; gap: 30px; border: 1px solid #FDE68A; background: #FFFBEB; border-radius: 8px;
      padding: 10px 15px; margin-bottom: 18px; page-break-inside: avoid; }
  .nota-ref .dato-etq { color: #B45309; }
  .nota-ref .dato-val { color: #78350F; }

  /* ── Módulo de validación: QR + hash + estado SUNAT ──────────── */
  .validacion { display: flex; gap: 18px; align-items: flex-start; border: 1px solid #E2E8F0;
      border-radius: 10px; padding: 14px 16px; margin-bottom: 22px; page-break-inside: avoid; }
  .validacion-qr { width: 96px; height: 96px; flex-shrink: 0; }
  .validacion-titulo { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.1px;
      color: var(--pri); margin-bottom: 6px; }
  .validacion-titulo::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 2px;
      background: var(--acc); margin-right: 7px; vertical-align: baseline; }
  .validacion-texto { font-size: 9.5px; color: #64748B; line-height: 1.7; }
  .validacion-hash { font-family: 'Consolas', 'Courier New', monospace; font-size: 9px; color: #475569;
      word-break: break-all; margin: 3px 0; }

  /* ── Pie ─────────────────────────────────────────────────────── */
  .pie { border-top: 1px solid #E2E8F0; padding-top: 13px; display: flex; justify-content: space-between;
      align-items: flex-end; page-break-inside: avoid; }
  .pie-emisor { font-size: 9.5px; color: #94A3B8; line-height: 1.6; }
  .pie-marca { text-align: right; }
  .pie-marca .b { font-size: 10px; font-weight: 700; color: #94A3B8; letter-spacing: .3px; }
  .pie-marca .b .by { font-weight: 400; color: #CBD5E1; }
  .pie-marca .c { font-size: 8.5px; color: #CBD5E1; font-style: italic; margin-top: 2px; }
  .pie-franja { display: flex; height: 3px; margin: 14px -44px 0; }
  .pie-franja span:first-child { flex: 1; background: var(--pri); }
  .pie-franja span:last-child  { width: 90px; background: var(--acc); }

  .anulado { position: fixed; top: 42%; left: 0; right: 0; text-align: center; font-size: 74px; font-weight: 900;
      color: rgba(220, 38, 38, .13); transform: rotate(-18deg); letter-spacing: 10px; pointer-events: none; z-index: 5; }

  @media print {
    .no-print { display: none !important; }
    @page { margin: 11mm 0; size: A4; }
    .hoja { max-width: none; }
  }
</style>
</head>
<body>
@if($anulado)<div class="anulado">ANULADO</div>@endif
<div class="hoja">

  <div class="franja"><span></span><span></span></div>

  <div class="no-print" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:18px;">
    <button onclick="window.print()"
            style="background:var(--pri);color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;">
      Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()"
            style="background:#fff;color:#475569;border:1px solid #E2E8F0;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:12px;">
      Cerrar
    </button>
  </div>

  {{ $slot }}

  <div class="pie">
    <div class="pie-emisor">{{ $pieEmisor ?? '' }}</div>
    <div class="pie-marca">
      <div class="b">BIXO<span style="font-size:7px;vertical-align:super;">®</span> <span class="by">by</span> Eskala</div>
      <div class="c">© {{ date('Y') }} Eskala Group · BIXO®</div>
    </div>
  </div>
  <div class="pie-franja"><span></span><span></span></div>

</div>
</body>
</html>
