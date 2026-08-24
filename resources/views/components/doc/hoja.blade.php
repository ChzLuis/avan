{{--
    La hoja de todo documento comercial de BIXO.

    Es el lenguaje visual compartido: la franja corporativa, la tipografía, la
    retícula y el pie. Factura, boleta, nota y guía se montan sobre esta hoja
    para que cualquier documento del sistema se reconozca como de la misma
    familia, cambien los campos que cambien.

    CONSTRUIDA PARA A4, no adaptada: las medidas son de imprenta (mm y pt), la
    página imprimible es de 275 mm de alto (297 − márgenes de @page) y el pie
    se ancla al fondo real de la última página — un documento corto no deja
    medio folio vacío debajo del contenido. La cabecera de la tabla se repite
    en cada página y ningún bloque de cierre se parte en dos.

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
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $titulo }}</title>
<style>
  :root { --pri: {{ $pri }}; --acc: {{ $acc }}; }
  * { box-sizing: border-box; margin: 0; padding: 0; }

  /* Medidas de imprenta: la página aporta 10 mm arriba y 12 mm abajo en CADA
     hoja (nada toca el borde del papel en los saltos); los laterales los pone
     la propia hoja, que mide 210 mm exactos. */
  @page { size: A4; margin: 10mm 0 12mm; }

  body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; font-size: 9pt; color: #1E293B; background: #fff;
         -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  .hoja { width: 210mm; margin: 0 auto; padding: 0 15mm; position: relative; }

  /* El contenido reserva el sitio del pie, que vive anclado al fondo. */
  .contenido { padding-bottom: 24mm; }

  /* La franja corporativa: fina, dos tiempos, sin estridencia. */
  .franja { display: flex; height: 1.8mm; margin: 0 -15mm 9mm; }
  .franja span:first-child { flex: 1; background: var(--pri); }
  .franja span:last-child  { width: 38mm; background: var(--acc); }

  /* ── Encabezado ─────────────────────────────────────────────── */
  .enc { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm; margin-bottom: 9mm; }
  .enc-emisor { display: flex; gap: 6mm; align-items: flex-start; min-width: 0; }
  .enc-logo { max-height: 24mm; max-width: 52mm; object-fit: contain; flex-shrink: 0; }
  .enc-nombre { font-size: 13pt; font-weight: 800; color: #0F172A; letter-spacing: -.2pt; line-height: 1.24; }
  .enc-detalle { font-size: 8.5pt; color: #64748B; margin-top: 2mm; line-height: 1.65; }

  /* ── Tarjeta del comprobante ────────────────────────────────── */
  .tarjeta { border: .5mm solid var(--pri); border-radius: 3mm; min-width: 66mm; max-width: 74mm;
             overflow: hidden; text-align: center; flex-shrink: 0; }
  .tarjeta-cinta { height: 1.6mm; background: var(--acc); }
  .tarjeta-cuerpo { padding: 4mm 6mm 4.5mm; }
  .tarjeta-ruc { font-size: 8.5pt; font-weight: 600; color: #475569; letter-spacing: .2pt; }
  .tarjeta-tipo { font-size: 8.5pt; font-weight: 800; color: var(--pri); letter-spacing: .5pt; line-height: 1.4; }
  .tarjeta-numero { font-size: 16.5pt; font-weight: 800; color: #0F172A; letter-spacing: -.3pt;
                    font-variant-numeric: tabular-nums; margin: 1.4mm 0 1mm; }
  .tarjeta-fecha { font-size: 7.5pt; color: #94A3B8; margin-top: 1.4mm; }

  .badge { display: inline-block; margin-top: 2.2mm; padding: 1.1mm 4mm; border-radius: 6mm;
           font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: .5pt; }
  .badge-verde  { background: #DCFCE7; color: #166534; }
  .badge-ambar  { background: #FEF3C7; color: #92400E; }
  .badge-rojo   { background: #FEE2E2; color: #991B1B; }
  .badge-gris   { background: #F1F5F9; color: #475569; }

  /* ── Sección con etiqueta corporativa ("FACTURADO A", etc.) ─── */
  .secc { border-left: 1.1mm solid var(--acc); padding: .8mm 0 1.4mm 5mm; margin-bottom: 7mm; }
  .secc-etiqueta { font-size: 7pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1pt; color: var(--pri); margin-bottom: 2mm; }
  .secc-titulo { font-size: 12pt; font-weight: 800; color: #0F172A; line-height: 1.35; overflow-wrap: anywhere; }
  .secc-detalle { font-size: 8.5pt; color: #475569; margin-top: 1.6mm; line-height: 1.75; }

  /* ── Datos complementarios: solo los que existen ─────────────── */
  .datos { display: flex; flex-wrap: wrap; gap: 3mm 12mm; margin: -2mm 0 7mm 6mm; }
  .dato-etq { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .8pt; color: #94A3B8; }
  .dato-val { font-size: 8.5pt; font-weight: 600; color: #1E293B; margin-top: .6mm; }

  /* ── Tabla de bienes/servicios ───────────────────────────────── */
  table.items { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 7mm; }
  table.items thead { display: table-header-group; }   /* se repite en cada página */
  table.items thead tr { background: var(--pri); }
  table.items thead th { background: var(--pri); color: #fff; padding: 3.2mm 3.4mm;
      font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: .7pt; text-align: right;
      border-top: .8mm solid var(--acc); }
  table.items thead th:first-child { text-align: left; }
  table.items tbody tr { page-break-inside: avoid; }
  table.items tbody td { padding: 3.2mm 3.4mm; font-size: 8.5pt; color: #475569; text-align: right;
      font-variant-numeric: tabular-nums; vertical-align: top; border-bottom: .3mm solid #F1F5F9; }
  table.items tbody td.desc { text-align: left; font-weight: 600; color: #0F172A; overflow-wrap: anywhere; }
  table.items tbody td.total-linea { font-weight: 700; color: #0F172A; }

  /* ── Cierre: totales ─────────────────────────────────────────── */
  .cierre { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm;
            margin-bottom: 6mm; page-break-inside: avoid; }
  .cierre-izq { flex: 1; font-size: 8.5pt; color: #64748B; line-height: 1.9; padding-top: 1mm; }
  .cierre-izq strong { color: #334155; font-weight: 600; }

  .totales { min-width: 76mm; }
  .totales-fila { display: flex; justify-content: space-between; padding: 2.2mm 5mm; font-size: 8.5pt; color: #475569; }
  .totales-fila span:last-child { font-variant-numeric: tabular-nums; font-weight: 600; color: #1E293B; }
  .total-final { display: flex; justify-content: space-between; align-items: center; margin-top: 2.4mm;
      background: var(--pri); color: #fff; border-radius: 2.5mm; padding: 4mm 5mm;
      border-left: 1.6mm solid var(--acc); page-break-inside: avoid; }
  .total-final .etq { font-size: 8.5pt; font-weight: 700; letter-spacing: 1.1pt; }
  .total-final .importe { font-size: 14pt; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.2pt; }

  /* ── El importe en letras, como banda propia ─────────────────── */
  .en-letras { background: #F8FAFC; border-top: .3mm solid #E2E8F0; border-bottom: .3mm solid #E2E8F0;
      padding: 3mm 5mm; margin-bottom: 7mm; font-size: 8pt; font-weight: 600; color: #334155;
      letter-spacing: .3pt; page-break-inside: avoid; }

  .obs { border-left: 1.1mm solid #E2E8F0; padding: 2.4mm 4.5mm; margin-bottom: 6mm;
      font-size: 8pt; color: #64748B; line-height: 1.65; page-break-inside: avoid; }
  .obs strong { color: #334155; }

  /* ── Referencia de nota: qué modifica y por qué ──────────────── */
  .nota-ref { display: flex; gap: 12mm; border: .3mm solid #FDE68A; background: #FFFBEB; border-radius: 2.5mm;
      padding: 3.5mm 5mm; margin-bottom: 7mm; page-break-inside: avoid; }
  .nota-ref .dato-etq { color: #B45309; }
  .nota-ref .dato-val { color: #78350F; }

  /* ── Módulo de validación: QR + hash + estado SUNAT ──────────── */
  .validacion { display: flex; gap: 7mm; align-items: flex-start; border: .3mm solid #E2E8F0;
      border-radius: 3mm; padding: 5.5mm 6mm; page-break-inside: avoid; }
  .validacion-qr { width: 30mm; height: 30mm; flex-shrink: 0; }
  .validacion-titulo { font-size: 7.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1pt;
      color: var(--pri); margin-bottom: 2.4mm; }
  .validacion-titulo::before { content: ''; display: inline-block; width: 2.4mm; height: 2.4mm; border-radius: .8mm;
      background: var(--acc); margin-right: 2.5mm; vertical-align: baseline; }
  .validacion-texto { font-size: 8pt; color: #64748B; line-height: 1.75; }
  .validacion-hash { font-family: 'Consolas', 'Courier New', monospace; font-size: 7.5pt; color: #475569;
      word-break: break-all; margin: 1.4mm 0; }

  /* ── Pie: anclado al fondo real de la última página ──────────── */
  .pie-zona { position: absolute; bottom: 0; left: 15mm; right: 15mm; }
  .pie { border-top: .3mm solid #E2E8F0; padding-top: 4mm; display: flex; justify-content: space-between;
      align-items: flex-end; }
  .pie-emisor { font-size: 7.5pt; color: #94A3B8; line-height: 1.6; }
  .pie-marca { text-align: right; }
  .pie-marca .b { font-size: 8pt; font-weight: 700; color: #94A3B8; letter-spacing: .2pt; }
  .pie-marca .b .by { font-weight: 400; color: #CBD5E1; }
  .pie-marca .c { font-size: 6.5pt; color: #CBD5E1; font-style: italic; margin-top: .8mm; }
  .pie-franja { display: flex; height: 1.2mm; margin: 4mm -15mm 0; }
  .pie-franja span:first-child { flex: 1; background: var(--pri); }
  .pie-franja span:last-child  { width: 26mm; background: var(--acc); }

  /* El cierre fiscal es un bloque: si no cabe entero, salta entero. El
     navegador lo parte igualmente si midiera mas que una pagina. */
  .bloque-cierre { page-break-inside: avoid; }

  .anulado { position: fixed; top: 42%; left: 0; right: 0; text-align: center; font-size: 52pt; font-weight: 900;
      color: rgba(220, 38, 38, .13); transform: rotate(-18deg); letter-spacing: 10px; pointer-events: none; z-index: 5; }

  @media print {
    .no-print { display: none !important; }
  }
  @media screen {
    body { background: #EEF1F5; }
    .hoja { background: #fff; margin: 18px auto; box-shadow: 0 4px 24px rgba(15, 23, 42, .12); }
  }
</style>
</head>
<body>
@if($anulado)<div class="anulado">ANULADO</div>@endif
<div class="hoja" id="hoja">

  <div class="contenido">
    <div class="franja"><span></span><span></span></div>

    <div class="no-print" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:6mm;">
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
  </div>

  <div class="pie-zona">
    <div class="pie">
      <div class="pie-emisor">{{ $pieEmisor ?? '' }}</div>
      <div class="pie-marca">
        <div class="b">BIXO<span style="font-size:5pt;vertical-align:super;">®</span> <span class="by">by</span> Eskala</div>
        <div class="c">© {{ date('Y') }} Eskala Group · BIXO®</div>
      </div>
    </div>
    <div class="pie-franja"><span></span><span></span></div>
  </div>

</div>

<script>
/* El pie se ancla al FONDO de la última página, tenga el documento una hoja o
   cinco: la hoja se estira hasta el múltiplo exacto de la altura imprimible
   (275 mm = 297 − márgenes de @page). Sin esto, un documento corto termina a
   media hoja y el resto queda en blanco — la maqueta "pequeña dentro del A4".
   Es geometría, no transform: nada se escala. */
(function () {
  var MM = 96 / 25.4;                    // 1 mm en px CSS (96 dpi)
  var PAGINA = 275 * MM;                 // alto imprimible por página
  var hoja = document.getElementById('hoja');

  function anclar() {
    hoja.style.minHeight = '';
    var alto = hoja.scrollHeight;
    var paginas = Math.max(1, Math.ceil((alto - 2) / PAGINA));
    hoja.style.minHeight = (paginas * PAGINA - 2) + 'px';
  }

  window.addEventListener('load', anclar);
  window.addEventListener('beforeprint', anclar);
  anclar();
})();
</script>
</body>
</html>
