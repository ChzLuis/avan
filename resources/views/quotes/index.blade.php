@php
    $esComercial   = ($portalLayout ?? 'panel') === 'comercial';
    $quotesApiBase = $esComercial
        ? route('bixosales.cotizaciones')
        : route('quotes');
    $currency = $project->setting('currency_symbol', 'S/');
    // URL de conversion generada POR NOMBRE DE RUTA, nunca concatenando: en
    // BixoSales existe la gemela 'convertir-pedido' (dual A|B) y en el panel la
    // ruta historica. El {id} se sustituye en el cliente.
    $urlConvertirPlantilla = $esComercial
        ? route('bixosales.cotizaciones.convertir_pedido', ['quote' => '__ID__'])
        : route('quotes.convert', ['quote' => '__ID__']);
    // Ruta de detalle para el deep link (historial del navegador).
    $urlDetallePlantilla = $esComercial
        ? route('bixosales.cotizaciones.show', ['quote' => '__ID__'])
        : route('quotes.show', ['quote' => '__ID__']);
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Cotizaciones">

<style>
/* ══ Contrato visual de Cotizaciones ═══════════════════════════════════
   Los valores viven aqui y NO dentro de cada componente: asi el ritmo es
   uno solo y un ajuste no obliga a perseguir treinta reglas sueltas. Se
   reutilizan los tokens del panel (--texto, --borde…) donde ya existen. */
.q-wrap {
  --q-bg:#F7F8FC; --q-card:#FFFFFF;
  --q-tx:#111827; --q-tx2:#64748B; --q-tx3:#94A3B8;
  --q-bd:#E5E7EB; --q-bd-soft:#EEF0F4;
  --q-pri:#4F46E5; --q-pri-hover:#4338CA; --q-pri-soft:#EEF2FF;
  --q-ok:#16A34A;  --q-ok-soft:#ECFDF3;
  --q-warn:#D97706; --q-warn-soft:#FFF7E6;
  --q-dan:#DC2626; --q-dan-soft:#FEF2F2;
  --q-conv:#7C3AED; --q-conv-soft:#F3E8FF;

  --q-t-xs:12px; --q-t-sm:13px; --q-t-base:14px; --q-t-md:15px;
  --q-t-lg:18px; --q-t-xl:22px; --q-t-2xl:26px;

  --q-s1:4px; --q-s2:8px; --q-s3:12px; --q-s4:16px; --q-s5:20px; --q-s6:24px; --q-s8:32px;
  --q-r-sm:6px; --q-r-md:8px; --q-r-lg:12px; --q-r-xl:14px;

  --q-lista:328px; --q-panel:310px;
  --q-sombra:0 1px 2px rgba(15,23,42,.03);
  --q-trans:color 150ms ease, background-color 150ms ease, border-color 150ms ease, box-shadow 150ms ease;
}

/* ── Reset & Base ── */
/* `flex:1` y `width:100%`: en el panel, el contenedor padre es un flex, y sin
   esto .q-wrap se queda con el ancho de su contenido (548px de 1680) dejando
   media pantalla en blanco a la derecha. En el portal comercial el padre no
   es flex y por eso alli no se notaba. */
.q-wrap { display:flex; flex:1; width:100%; min-width:0; height:calc(100vh - 56px); overflow:hidden; background:var(--q-bg); font-family:inherit; }

/* ── Sidebar lista ── */
.q-sidebar { width:var(--q-lista); min-width:var(--q-lista); max-width:var(--q-lista); flex-shrink:0; display:flex; flex-direction:column; background:var(--q-card); border-right:1px solid var(--q-bd); }
.q-sidebar-titulo { padding:var(--q-s4) var(--q-s3) var(--q-s2); display:flex; align-items:center; justify-content:space-between; gap:var(--q-s2); }
.q-sidebar-titulo h2 { font-size:var(--q-t-md); font-weight:700; color:var(--q-tx); margin:0; letter-spacing:-.01em; }
.q-sidebar-head { padding:0 var(--q-s3) var(--q-s2); display:flex; gap:var(--q-s2); align-items:center; }
.q-filtros-btn { width:38px; height:38px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--q-bd); border-radius:var(--q-r-md); background:var(--q-card); color:var(--q-tx2); cursor:pointer; transition:var(--q-trans); }
.q-filtros-btn:hover { background:#F8FAFC; }
.q-filtros-btn svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.75; stroke-linecap:round; }
.q-search { flex:1; height:38px; border:1px solid var(--q-bd); border-radius:var(--q-r-md); padding:0 var(--q-s3) 0 32px; font-size:var(--q-t-sm); outline:none; transition:var(--q-trans); background:var(--q-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%239ca3af' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 8px center; }
.q-search:focus { border-color:var(--q-pri); box-shadow:0 0 0 3px rgba(79,70,229,.10); }
.q-btn-new { width:32px; height:32px; padding:0; font-size:18px; line-height:1; display:inline-flex; align-items:center; justify-content:center; background:var(--q-pri); color:#fff; border:none; border-radius:var(--q-r-md); font-size:var(--q-t-xs); font-weight:600; cursor:pointer; white-space:nowrap; transition:var(--q-trans); }
.q-btn-new:hover { background:var(--q-pri-hover); }
.q-filters { display:flex; flex-wrap:wrap; gap:6px; padding:0 var(--q-s3) var(--q-s3); }

.q-filter { height:32px; padding:0 10px; border:none; border-radius:999px; font-size:var(--q-t-xs); font-weight:500; cursor:pointer; background:#F8FAFC; color:var(--q-tx2); white-space:nowrap; flex-shrink:0; transition:var(--q-trans); }
.q-filter:hover { background:var(--q-bd-soft); }
.q-filter.active { background:var(--q-pri-soft); color:var(--q-pri); box-shadow:inset 0 0 0 1px #C7D2FE; }
.q-list { overflow-y:auto; flex:1; }
/* Fila de la lista: cliente, codigo+fecha, monto y estado. Nada mas.
   Sin borde por fila —eran 40 lineas horizontales— : separa el espacio, y
   la seleccion se marca con fondo y una barra a la izquierda. */
.q-item { min-height:78px; padding:var(--q-s3) var(--q-s4); cursor:pointer; display:flex; align-items:flex-start; gap:var(--q-s2); border-left:3px solid transparent; border-bottom:1px solid var(--q-bd-soft); transition:var(--q-trans); }
.q-item-fila1 { display:flex; align-items:baseline; justify-content:space-between; gap:var(--q-s2); }
.q-item-fila2 { display:flex; align-items:center; justify-content:space-between; gap:var(--q-s2); margin-top:3px; }
.q-item-cliente { font-size:var(--q-t-sm); color:var(--q-tx2); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.q-item-fecha { font-size:var(--q-t-xs); color:var(--q-tx3); margin-top:3px; }

.q-item:hover { background:#FAFBFE; }
.q-item.active { background:#F3F4FF; border-left-color:#6366F1; border-radius:0 var(--q-r-md) var(--q-r-md) 0; }
.q-item-check { margin-top:3px; width:14px; height:14px; border-radius:4px; border:1.5px solid #d1d5db; flex-shrink:0; display:flex; align-items:center; justify-content:center; cursor:pointer; opacity:0; transition:opacity .12s; }
.q-item:hover .q-item-check, .q-item-check.q-check-visible, .q-item-check:focus-visible { opacity:1; }
@media (pointer:coarse) { .q-item-check { opacity:1; } }
.q-item-body { flex:1; min-width:0; }
.q-item-name { font-size:var(--q-t-sm); font-weight:600; color:var(--q-tx); display:flex; align-items:center; gap:var(--q-s1); min-width:0; }
.q-item-name > span:last-child { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.q-item-punto { width:7px; height:7px; border-radius:50%; background:#ef4444; flex-shrink:0; }
.q-item-meta { font-size:var(--q-t-xs); color:var(--q-tx3); margin-top:3px; display:flex; gap:var(--q-s1); align-items:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.q-item-num { display:inline-flex; align-items:center; gap:var(--q-s1); font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); font-variant-numeric:tabular-nums; flex-shrink:0; }
.q-item-der { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.q-lista-cuenta { padding:0 var(--q-s3) var(--q-s2); font-size:var(--q-t-xs); color:var(--q-tx3); font-weight:600; }
.q-item-total { font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); flex-shrink:0; font-variant-numeric:tabular-nums; }

/* Plegado de la lista: solo en escritorio. */
@media(min-width:1024px){
  .q-sidebar.q-lista-plegada { display:none !important; }
}
.q-rail-oculto { display:none !important; }

/* Plegado de la lista */
.q-colapsar { flex-shrink:0; display:flex; align-items:center; justify-content:center; gap:6px; padding:11px; border:none; border-top:1px solid #f3f4f6; background:#fff; color:#6b7280; font-size:12px; font-weight:600; cursor:pointer; }
.q-colapsar:hover { background:#f8f9fb; color:#374151; }
.q-rail { flex-shrink:0; width:26px; border:none; border-right:1px solid #e5e7eb; background:#fff; color:#9ca3af; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.q-rail:hover { background:#f3f4f6; color:#4338ca; }

/* ── Badges estado ── */
.qbadge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; text-transform:uppercase; letter-spacing:.3px; }
.qbadge-draft    { background:#f3f4f6; color:#5b6270; }
.qbadge-sent     { background:#dbeafe; color:#1d4ed8; }
.qbadge-accepted { background:#dcfce7; color:#15803d; }
.qbadge-rejected { background:#fee2e2; color:#b91c1c; }
.qbadge-converted { background:#ede9fe; color:#6d28d9; }

/* ── Badges pago (solo aplica a cotizaciones aceptadas) ── */
.pbadge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; text-transform:uppercase; letter-spacing:.3px; }
.pbadge-pending  { background:#fef3c7; color:#b45309; }
.pbadge-partial  { background:#fef9c3; color:#a16207; }
.pbadge-paid     { background:#dcfce7; color:#15803d; }
.pbadge-refunded { background:#fee2e2; color:#b91c1c; }

/* ── Zona central ── */
/* 'Volver a la lista' es de movil: en escritorio la lista esta al lado. */
.q-volver { display:none !important; }
@media(max-width:1023px){ .q-volver { display:flex !important; } }
.q-main { flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0; }

/* ══ Cabecera del documento ══════════════════════════════════════════
   Una accion primaria (enviar), una secundaria fuerte (WhatsApp) y el resto
   plegado en "Mas". Antes eran seis botones apilados con el mismo peso. */
/* Cabecera del documento: fila 1 titulo + acciones, fila 2 cliente/fecha +
   estado. Una sola declaracion para las dos. */
.q-main-head {
  display:flex; flex-wrap:wrap; align-items:center; gap:var(--q-s3) var(--q-s4);
  padding:18px var(--q-s5) var(--q-s4); background:var(--q-card); flex-shrink:0;
  border-bottom:1px solid var(--q-bd);
}
.q-head-id { flex:1 1 240px; min-width:0; }
.q-main-title { font-size:32px; font-weight:750; letter-spacing:-.025em; color:var(--q-tx); line-height:1.1; }
.q-head-meta { display:flex; flex-wrap:wrap; align-items:center; gap:var(--q-s1) var(--q-s3); margin-top:var(--q-s2); }
.q-head-chip { display:inline-flex; align-items:center; gap:var(--q-s1); font-size:var(--q-t-sm); color:var(--q-tx2); }
.q-head-chip.q-chip-cliente { font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); }
/* Pedido generado: referencia compacta, no franja. */
.q-head-ped { display:inline-flex; align-items:center; gap:var(--q-s2); font-size:var(--q-t-sm); color:var(--q-tx2); }
.q-ped-link { font-weight:600; color:var(--q-pri); text-decoration:none; font-variant-numeric:tabular-nums; }
.q-ped-link:hover { text-decoration:underline; }
.q-ico-xs { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
.q-ico-sm { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }

.q-head-acciones { display:flex; align-items:center; gap:var(--q-s2); flex-wrap:wrap; }
.q-cta {
  display:inline-flex; align-items:center; gap:var(--q-s2); height:42px; padding:0 18px;
  border:none; border-radius:var(--q-r-md); font-size:var(--q-t-sm); font-weight:600; cursor:pointer;
  background:var(--q-pri); color:#fff; transition:var(--q-trans);
}
.q-cta:hover:not(:disabled) { background:var(--q-pri-hover); }
.q-cta:disabled { opacity:.55; cursor:not-allowed; }
.q-cta-wa { background:#16a34a; }
.q-cta-wa:hover { background:#15803d; }
.q-cta-ghost { height:42px; padding:0 var(--q-s4); background:var(--q-card); color:var(--q-tx); border:1px solid var(--q-bd); font-weight:500; }
.q-cta-ghost:hover { background:#F8FAFC; border-color:#D8DCE4; }
.q-menu {
  position:absolute; top:calc(100% + 6px); right:0; width:220px; background:var(--q-card);
  border:1px solid var(--q-bd); border-radius:10px; padding:6px;
  box-shadow:0 10px 30px rgba(15,23,42,.08), 0 2px 8px rgba(15,23,42,.04);
  z-index:200; max-height:min(70vh, 520px); overflow-y:auto;
}
/* Si no cabe por debajo, se abre hacia arriba (lo decide el componente). */
.q-menu.q-menu-arriba { top:auto; bottom:calc(100% + 6px); }
.q-menu button, .q-menu a {
  display:flex; align-items:center; width:100%; min-height:36px; text-align:left;
  padding:var(--q-s2) 10px; border:none; background:none; border-radius:7px;
  font-size:12.5px; font-weight:500; color:var(--q-tx); cursor:pointer;
  text-decoration:none; transition:var(--q-trans);
}
.q-menu button:hover, .q-menu a:hover { background:#F8FAFC; }
.q-menu button:disabled { opacity:.5; cursor:not-allowed; }

/* Estado compacto: un desplegable, no una franja de botones */
.q-head-sub {
  display:flex; align-items:center; gap:var(--q-s5); flex-wrap:wrap;
  padding:0; background:transparent; flex-shrink:0; margin-left:auto;
}
/* La cabecera cierra con una linea; el bloque entero es una sola pieza. */
.q-head-fila2 { display:flex; align-items:center; gap:var(--q-s4); flex-wrap:wrap; width:100%; margin-top:var(--q-s2); }
.q-estado-wrap { display:inline-flex; align-items:center; gap:8px; }
.q-estado-label { font-size:var(--q-t-sm); color:var(--q-tx2); font-weight:500; }
.q-estado-select {
  display:inline-flex; align-items:center;
  height:36px; padding:0 32px 0 12px; border-radius:var(--q-r-md); font-size:var(--q-t-sm); font-weight:600;
  border:1px solid; cursor:pointer; appearance:none;
  background-image:url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='m19.5 8.25-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 9px center; background-size:12px;
}
.q-estado-select:disabled { cursor:not-allowed; opacity:.7; }
.q-estado-draft    { background-color:var(--q-warn-soft); color:var(--q-warn); border-color:#FDE68A; }
.q-estado-sent     { background-color:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.q-estado-accepted { background-color:var(--q-ok-soft); color:var(--q-ok); border-color:#BBF7D0; }
.q-estado-rejected { background-color:var(--q-dan-soft); color:var(--q-dan); border-color:#FECACA; }
.q-estado {
  display:inline-flex; align-items:center; height:32px; padding:0 12px; border-radius:8px;
  font-size:12.5px; font-weight:700;
}
.q-estado-converted { background:var(--q-conv-soft); color:var(--q-conv); border-color:#DDD6FE; background-image:none; padding-right:11px; }
.q-guardado { display:inline-flex; align-items:center; gap:var(--q-s1); font-size:var(--q-t-xs); color:var(--q-tx2); font-weight:500; margin-left:auto; }
.q-guardado svg { color:var(--q-ok); }


/* ── Tabla de productos ── */
.q-table { width:100%; border-collapse:collapse; }
.q-table th { height:38px; font-size:11px; font-weight:600; color:var(--q-tx3); padding:0 var(--q-s3); background:#FAFBFC; border-bottom:1px solid var(--q-bd-soft); border-top:1px solid var(--q-bd-soft); text-align:left; text-transform:none; letter-spacing:0; }
.q-table th.r, .q-table td.r { text-align:right; }
.q-table th.c, .q-table td.c { text-align:center; }
.q-table tr:last-child td { border-bottom:none; }
.q-table tr:hover td { background:#fafafa; }
.q-td-input { border:1px solid transparent; border-radius:6px; padding:5px 8px; font-size:13px; width:100%; background:transparent; outline:none; color:#111827; transition:border .15s,background .15s; min-width:0; }
.q-td-input:focus { border-color:#6366f1; background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-td-input.desc { min-width:180px; }
.q-td-input.num  { width:72px; text-align:right; }
.q-td-sub { font-size:var(--q-t-sm); font-weight:700; color:var(--q-tx); white-space:nowrap; text-align:right; }

/* Tabla de LECTURA: importes ya formateados, sin cajas de formulario.
   Antes el precio se leia como '60.00' dentro de un input; ahora dice
   'S/ 60.00', que es como se habla de dinero. */
.q-tabla-lectura td { height:58px; padding:var(--q-s2) 14px; font-size:var(--q-t-sm); white-space:nowrap; color:var(--q-tx2); font-variant-numeric:tabular-nums; border-bottom:1px solid var(--q-bd-soft); }
.q-tabla-lectura .q-td-desc { font-size:13.5px; font-weight:600; color:var(--q-tx); line-height:1.35; }
.q-td-desc-pct { color:var(--q-tx3); }
/* Miniatura del catalogo. Cuando la linea no es un producto (un servicio
   escrito a mano) va un marcador neutro: la columna no se descuadra y no
   se inventa una imagen. */
.q-prod { display:flex; align-items:center; gap:var(--q-s3); min-width:0; }
.q-prod-img { width:42px; height:42px; border-radius:7px; object-fit:contain; background:#F8FAFC; border:1px solid var(--q-bd-soft); flex-shrink:0; }
.q-prod-vacia { display:flex; align-items:center; justify-content:center; color:#CBD5E1; }
.q-prod-vacia svg { width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
.q-prod-txt { display:flex; flex-direction:column; gap:2px; min-width:0; white-space:normal; }
.q-prod-sub { font-size:11.5px; color:var(--q-tx2); }
.q-td-acciones { width:40px; text-align:center; padding-left:0 !important; padding-right:4px !important; }
.q-fila-mas { width:28px; height:28px; border:none; background:none; border-radius:var(--q-r-sm); color:#CBD5E1; cursor:pointer; transition:var(--q-trans); display:inline-flex; align-items:center; justify-content:center; }
.q-fila-mas svg { width:16px; height:16px; fill:currentColor; }
.q-fila-mas:hover { background:var(--q-bd-soft); color:var(--q-tx); }
.q-tabla-lectura tr:last-child td { border-bottom:none; }
.q-tabla-lectura tr:hover td { background:#FCFDFF; }
.q-tabla-vacia { text-align:center; color:var(--q-tx3); font-size:var(--q-t-sm); height:auto !important; padding:28px var(--q-s3) !important; }
.q-tabla-pie { min-height:46px; display:flex; align-items:center; justify-content:space-between; gap:var(--q-s3); padding:0 14px; font-size:var(--q-t-sm); color:var(--q-tx2); border-top:1px solid var(--q-bd-soft); }
.q-pie-sub { color:var(--q-tx2); }
.q-tabla-pie strong { font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); margin-left:var(--q-s2); font-variant-numeric:tabular-nums; }
.q-add-row { width:100%; border:none; background:none; padding:8px 12px; font-size:12px; color:#6366f1; cursor:pointer; text-align:left; display:flex; align-items:center; gap:6px; }
.q-add-row:hover { background:#f5f3ff; }

/* ── Buscador catálogo ── */
.q-agregar-wrap { position:relative; }
.q-agregar { display:inline-flex; align-items:center; gap:var(--q-s1); height:32px; padding:0 var(--q-s2); border:none; border-radius:var(--q-r-sm); background:transparent; color:var(--q-pri); font-size:var(--q-t-sm); font-weight:600; cursor:pointer; transition:var(--q-trans); }
.q-agregar:hover { background:var(--q-pri-soft); }
.q-catalog-pop { position:absolute; top:calc(100% + 6px); right:0; width:340px; max-width:78vw; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 12px 32px rgba(15,23,42,.14); z-index:200; padding:10px; }
.q-catalog-vacio { font-size:12px; color:#9ca3af; margin:0; padding:10px 4px; }
.q-catalog-manual { width:100%; margin-top:6px; border:none; border-top:1px solid #f3f4f6; background:none; padding:8px 4px 2px; font-size:12.5px; font-weight:600; color:#4f46e5; cursor:pointer; text-align:left; }
.q-catalog-wrap { position:relative; }
.q-catalog-input { border:1.5px dashed #c7d2fe; border-radius:8px; padding:7px 12px 7px 32px; font-size:12px; width:100%; outline:none; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%236366f1' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; color:#374151; }
.q-catalog-input { border-style:solid; border-color:#e5e7eb; }
.q-catalog-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-catalog-drop { margin-top:8px; max-height:240px; overflow-y:auto; }
.q-catalog-item { display:flex; align-items:center; justify-content:space-between; padding:9px 10px; cursor:pointer; border-radius:8px; gap:8px; }
.q-catalog-item:hover { background:#f5f3ff; }

.q-catalog-name { font-size:12px; font-weight:500; color:#111827; }
.q-catalog-sku  { font-size:10px; color:var(--texto-debil, #5b6270); }
.q-catalog-price{ font-size:12px; font-weight:700; color:#6366f1; flex-shrink:0; }

/* ── Datos cliente ── */
.q-client-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.q-field label { font-size:11px; font-weight:600; color:#5b6270; display:block; margin-bottom:3px; }
.q-field input, .q-field select, .q-field textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:7px 10px; font-size:13px; outline:none; color:#111827; background:#fff; transition:border .15s; }
.q-field input:focus, .q-field select:focus, .q-field textarea:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-section { background:var(--q-card); border-radius:var(--q-r-lg); border:1px solid var(--q-bd); box-shadow:var(--q-sombra); overflow:hidden; }
.q-section-head { padding:var(--q-s4) 18px var(--q-s3); min-height:auto; display:flex; align-items:center; justify-content:space-between; gap:var(--q-s3); }
.q-section-title { display:inline-flex; align-items:center; gap:var(--q-s2); font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); }
.q-section-body { padding:0 18px 18px; }

/* Tarjeta de LECTURA: los datos se leen como texto, no dentro de inputs. */
.q-lectura { display:grid; grid-template-columns:minmax(180px,1.2fr) minmax(140px,.8fr) minmax(180px,1fr) minmax(140px,.8fr); gap:var(--q-s4) var(--q-s6); }
@media (min-width:1280px) and (max-width:1599px){ .q-lectura { grid-template-columns:repeat(3, minmax(0,1fr)); } }
@media (max-width:1279px){ .q-lectura { grid-template-columns:repeat(2, minmax(0,1fr)); } }
.q-lectura-item { min-width:0; }
.q-lectura-item p { overflow-wrap:anywhere; }
.q-lectura-item label { display:block; font-size:var(--q-t-xs); font-weight:500; color:var(--q-tx3); margin-bottom:var(--q-s1); }
.q-lectura-item p { font-size:var(--q-t-base); color:var(--q-tx); margin:0; word-break:break-word; font-weight:600; line-height:1.45; }
.q-editar { display:inline-flex; align-items:center; gap:var(--q-s1); border:none; background:none; color:var(--q-pri); border-radius:var(--q-r-sm); padding:var(--q-s1) var(--q-s2); font-size:var(--q-t-xs); font-weight:600; cursor:pointer; transition:var(--q-trans); }
.q-editar:hover { background:var(--q-pri-soft); }

/* ── Panel derecho ── */
.q-panel { width:var(--q-panel); min-width:var(--q-panel); flex-shrink:0; display:flex; flex-direction:column; gap:var(--q-s3); padding:var(--q-s3); overflow-y:auto; background:transparent; align-self:flex-start; position:sticky; top:0; max-height:100%; }

/* Tarjeta del panel: Resumen, Siguiente paso y Actividad comparten caja para
   que la columna derecha se lea como una sola pieza y no como tres widgets. */
.q-resumen, .q-sec, .q-actividad { background:var(--q-card); border-radius:var(--q-r-lg); border:1px solid var(--q-bd); box-shadow:var(--q-sombra); padding:18px; }
.q-resumen-head { display:flex; align-items:center; gap:var(--q-s2); font-size:var(--q-t-base); font-weight:700; color:var(--q-tx); margin-bottom:var(--q-s3); }
.q-resumen-fila { display:flex; justify-content:space-between; align-items:baseline; gap:var(--q-s2); font-size:var(--q-t-sm); color:var(--q-tx2); padding:var(--q-s1) 0; font-variant-numeric:tabular-nums; }
.q-resumen-fila > span:last-child { color:var(--q-tx); font-weight:500; }
.q-resumen-fila .q-desc { color:#b45309; font-weight:600; }
/* El Total es la cifra por la que se decide: tipografia mayor y linea que lo
   separa del desglose. */
.q-resumen-total { display:flex; align-items:baseline; justify-content:space-between; gap:var(--q-s3); padding-top:var(--q-s4); margin-top:var(--q-s4); border-top:1px solid var(--q-bd); }
.q-resumen-total span { font-size:var(--q-t-md); font-weight:700; color:var(--q-tx); }
.q-resumen-total strong { font-size:30px; font-weight:750; color:var(--q-pri); letter-spacing:-.025em; line-height:1.1; font-variant-numeric:tabular-nums; }
.q-resumen-nota { font-size:var(--q-t-xs); color:var(--q-tx3); margin:var(--q-s2) 0 0; }
.q-detalle-btn { display:flex; align-items:center; justify-content:center; gap:var(--q-s2); width:100%; height:38px; margin-top:var(--q-s4); border:1px solid var(--q-bd); border-radius:var(--q-r-md); background:var(--q-card); color:var(--q-pri); font-size:var(--q-t-sm); font-weight:600; cursor:pointer; transition:var(--q-trans); }
.q-detalle-btn:hover { background:var(--q-pri-soft); border-color:#C7D2FE; }
.q-detalle-btn svg { transition:transform 150ms ease; }
.q-detalle { margin-top:var(--q-s3); padding-top:var(--q-s3); border-top:1px solid var(--q-bd-soft); display:flex; flex-direction:column; gap:var(--q-s2); }
.q-detalle-fila { display:flex; justify-content:space-between; gap:var(--q-s2); font-size:var(--q-t-xs); color:var(--q-tx2); }
.q-detalle-fila > span:first-child { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.q-detalle-fila > span:last-child { font-variant-numeric:tabular-nums; color:var(--q-tx); font-weight:600; flex-shrink:0; }

/* Acciones secundarias: presentes, pero sin peso de boton primario. */
.q-sec { padding:var(--q-s2); display:flex; flex-direction:column; gap:2px; }
.q-sec-item { display:flex; align-items:center; gap:10px; width:100%; min-height:36px; padding:var(--q-s2) 10px; border:none; border-radius:var(--q-r-md); background:transparent; font-size:var(--q-t-sm); font-weight:500; color:#374151; text-align:left; cursor:pointer; text-decoration:none; transition:background .15s; }
.q-sec-item:hover:not(:disabled) { background:#F8FAFC; }
.q-sec-item:disabled { opacity:.5; cursor:not-allowed; }
.q-sec-item svg { flex-shrink:0; color:#9ca3af; }
.q-sec-danger { color:#b91c1c; }
.q-sec-danger:hover { background:#fee2e2; }
.q-sec-danger svg { color:#dc2626; }

/* Actividad: hechos reales de `order_events`, en orden inverso. */
.q-actividad-vacio { font-size:var(--q-t-xs); color:var(--q-tx3); margin:0; }
.q-timeline { list-style:none; margin:0; padding:0; }
.q-act-item { position:relative; display:flex; gap:var(--q-s2); align-items:flex-start; padding:0 0 18px 24px; }
.q-act-item:last-child { padding-bottom:0; }
/* El punto y la linea se dibujan sobre el propio item: asi la linea nace
   del punto y no de un hueco de flex. */
/* Punto de color segun el hecho: creado, enviado, visto por el cliente,
   guardado. El color lo decide el evento, no el orden en la lista. */
.q-act-item::before {
  content:''; position:absolute; left:4px; top:4px; width:9px; height:9px;
  border-radius:50%; background:#C7D2FE; z-index:1; box-shadow:0 0 0 3px var(--q-card);
}
.q-act-item:not(:last-child)::after {
  content:''; position:absolute; left:8px; top:16px; bottom:-2px; width:1px; background:var(--q-bd);
}
.q-act-cliente::before   { background:#2563EB; }
.q-act-exito::before     { background:var(--q-ok); }
.q-act-convertida::before{ background:var(--q-conv); }
.q-act-punto { display:none; }
.q-act-item:first-child .q-act-punto { background:#4f46e5; }
.q-act-cuerpo { flex:1; min-width:0; }
.q-act-titulo { font-size:12.5px; font-weight:600; color:var(--q-tx); margin:0; line-height:1.35; }
.q-act-detalle { font-size:11.5px; color:var(--q-tx2); margin:var(--q-s1) 0 0; line-height:1.4; }
.q-act-quien { color:var(--q-tx3); }
.q-act-hora { font-size:11px; color:var(--q-tx3); white-space:nowrap; flex-shrink:0; margin-top:1px; }
.q-act-mas { width:100%; margin-top:var(--q-s3); border:none; border-top:1px solid var(--q-bd-soft); background:none; padding:var(--q-s3) 0 0; font-size:12.5px; font-weight:600; color:var(--q-pri); cursor:pointer; }
.q-menu-titulo { font-size:10.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--q-tx3); margin:var(--q-s2) 10px 3px; }
.q-menu-titulo:first-child { margin-top:2px; }
.q-notas { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.q-lectura-item p.q-notas, .q-lectura-item p[x-ref='notas'] { font-size:var(--q-t-sm); line-height:1.45; font-weight:500; }
.q-vermas { border:none; background:none; padding:4px 0 0; font-size:12.5px; font-weight:600; color:#4f46e5; cursor:pointer; }

.q-actions { display:flex; flex-direction:column; gap:var(--tactil-gap, 8px); }
.q-btn { border:none; border-radius:8px; padding:9px 14px; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px; justify-content:center; transition:opacity .15s,background .15s; width:100%; }
.q-btn:disabled { opacity:.5; cursor:not-allowed; }
.q-btn-primary  { background:#6366f1; color:#fff; }
.q-btn-primary:hover:not(:disabled) { background:#4f46e5; }
.q-btn-green    { background:#25d366; color:#fff; }
.q-btn-green:hover:not(:disabled)  { background:#1fb855; }
.q-btn-outline  { background:#fff; color:#374151; border:1px solid #e5e7eb; }
.q-btn-outline:hover { background:#f3f4f6; }
.q-btn-danger   { background:#fff; color:#b91c1c; border:1px solid #fee2e2; }
.q-btn-danger:hover { background:#fee2e2; }


/* ── Edicion en el sitio ──────────────────────────────────────────────
   El campo se lee como texto y se escribe donde esta. El borde aparece al
   apuntarlo o al enfocarlo: asi la tarjeta no parece un formulario pero
   tampoco esconde que se puede editar. */
.q-inline input, .q-inline select, .q-inline textarea {
  width:100%; border:1px solid transparent; border-radius:var(--q-r-sm);
  background:transparent; padding:5px 8px; margin:-5px -8px;
  font:inherit; font-size:var(--q-t-base); font-weight:600; color:var(--q-tx);
  transition:var(--q-trans); appearance:none;
}
.q-inline textarea { font-weight:500; font-size:var(--q-t-sm); line-height:1.45; resize:none; overflow:hidden; }
.q-inline input:hover:not([readonly]), .q-inline select:hover:not(:disabled), .q-inline textarea:hover:not([readonly]) {
  border-color:var(--q-bd); background:#FBFCFE;
}
.q-inline input:focus, .q-inline select:focus, .q-inline textarea:focus {
  outline:none; border-color:var(--q-pri); background:var(--q-card);
  box-shadow:0 0 0 3px rgba(79,70,229,.10);
}
.q-inline input::placeholder, .q-inline textarea::placeholder { color:var(--q-tx3); font-weight:400; }
/* Documento cerrado: ni borde, ni cursor de texto, ni sombra de campo. */
.q-inline-off input, .q-inline-off select, .q-inline-off textarea { cursor:default; }
.q-inline-off input:hover, .q-inline-off select:hover, .q-inline-off textarea:hover { border-color:transparent; background:transparent; }
.q-inline-nota { font-size:var(--q-t-xs); font-weight:600; color:var(--q-tx3); }

/* Celdas de la grilla de productos */
.q-cel { width:100%; border:1px solid transparent; border-radius:var(--q-r-sm); background:transparent; padding:6px 8px; font:inherit; transition:var(--q-trans); }
.q-cel-desc { font-size:13.5px; font-weight:600; color:var(--q-tx); }
.q-cel-num  { font-size:var(--q-t-sm); color:var(--q-tx2); text-align:right; font-variant-numeric:tabular-nums; -moz-appearance:textfield; }
.q-cel-num::-webkit-outer-spin-button, .q-cel-num::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.q-table td.c .q-cel-num { text-align:center; }
.q-grid:not(.q-inline-off) .q-cel:hover:not([readonly]) { border-color:var(--q-bd); background:#FBFCFE; }
.q-cel:focus { outline:none; border-color:var(--q-pri); background:var(--q-card); box-shadow:0 0 0 3px rgba(79,70,229,.10); }
.q-cel::placeholder { color:var(--q-tx3); font-weight:400; }
/* La fila en blanco del final: presente pero sin peso hasta que se escribe. */
.q-fila-nueva td { color:var(--q-tx3); }
.q-fila-nueva .q-prod-img { opacity:.45; }

/* Sugerencias del catalogo bajo la celda */
.q-sugerencias { position:absolute; z-index:60; top:calc(100% + 4px); left:0; min-width:min(280px, 100%); max-width:min(420px, calc(100vw - 32px)); background:var(--q-card); border:1px solid var(--q-bd); border-radius:10px; box-shadow:0 10px 30px rgba(15,23,42,.10); padding:4px; max-height:230px; overflow-y:auto; }
.q-sugerencia { display:flex; align-items:center; justify-content:space-between; gap:var(--q-s3); width:100%; padding:8px 10px; border:none; background:none; border-radius:7px; font-size:var(--q-t-sm); color:var(--q-tx); text-align:left; cursor:pointer; }
.q-sugerencia:hover { background:var(--q-pri-soft); }
.q-sug-precio { color:var(--q-pri); font-weight:700; flex-shrink:0; font-variant-numeric:tabular-nums; }
.q-cliente-ficha { display:inline-flex; align-items:center; gap:4px; margin-top:4px; font-size:11px; font-weight:600; color:var(--q-ok); }
.q-prod-txt { position:relative; }

.q-btn-peligro { background:var(--q-dan) !important; }
.q-btn-peligro:hover:not(:disabled) { background:#B91C1C !important; }

/* ── Empty state ── */
.q-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:#d1d5db; }

/* ── Mobile: una vista a la vez (lista O detalle) y el panel de acciones
      apilado DEBAJO del detalle — antes estaba display:none y desde el
      celular no se podía guardar, cobrar ni exportar una cotización. ── */
@media(max-width:1023px){
  .q-wrap { height:auto; min-height:calc(100vh - 56px); flex-direction:column; overflow:visible; }
  /* En tablet y movil la lista es la pantalla anterior, no una columna: el
     plegado no tiene sentido y el rail estorbaria. */
  /* `width:100%` no bastaba: `min-width` y `max-width` seguian clavados en
     los 328 px de escritorio, asi que en un movil de 412 la lista se quedaba
     corta y dejaba una franja vacia a la derecha. */
  .q-sidebar { width:100%; min-width:0; max-width:none; border-right:none; }
  .q-colapsar, .q-rail { display:none; }
  .q-main-head { padding:16px 16px 12px; }
  .q-head-sub { padding:0 16px 14px; }
  .q-main-body { padding:16px 16px 24px; }
  .q-main-title { font-size:26px; }
  .q-guardado { margin-left:0; }
  .q-catalog-pop { right:auto; left:0; width:min(340px, 88vw); }
  .q-main { min-height:0; }
  .q-main-body { overflow:visible; }
  .q-main-head { flex-wrap:wrap; }
  /* Orden en movil: cabecera, acciones, RESUMEN, y luego el documento.
     `display:contents` sube los hijos de .q-main a la columna principal, y
     asi el panel puede colocarse entre la cabecera y el contenido sin
     duplicar una sola linea de marcado. */
  /* `display:contents` sube los hijos a la columna principal, pero con
     !important tambien ganaba al ocultamiento: la clase `hidden` de la zona
     central y el `display:none` que Alpine escribe en el panel derecho dejaban
     de surtir efecto. Resultado en el movil: la lista y el detalle se veian a
     la vez, el Resumen salia en S/ 0.00 sin cotizacion elegida y "Eliminar"
     no hacia nada porque no habia ninguna seleccionada. Se excluye el estado
     oculto para que reordenar no impida esconder. */
  .q-main:not(.hidden), .q-doc { display:contents !important; }
  .q-main-head { order:1; }
  .q-head-sub  { order:2; }
  .q-volver    { order:0; }
  /* El panel tambien se disuelve para que sus tres tarjetas se coloquen
     por separado: Resumen arriba (es lo que se consulta) y Actividad al
     final, detras del documento. */
  .q-panel:not([style*="display:none"]):not([style*="display: none"]) { display:contents !important; }
  .q-resumen   { order:3; margin:0 16px; }
  .q-main-body { order:4; }
  .q-acciones-barra, .q-empty { order:5; }
  .q-sec       { order:6; margin:0 16px; }
  .q-actividad { order:7; margin:0 16px 24px; }
  .q-client-grid { grid-template-columns:1fr; }
}

/* ══════════════ F1c ══════════════════════════════════════════════════════
   Correcciones medidas en producción, no impresiones:
   · la tarjeta CLIENTE se montaba 14 px sobre la última fila de productos;
   · la tabla medía 581 px dentro de un contenedor de 292 (289 px de scroll
     interno) en un móvil de 390;
   · 57 de 69 controles quedaban por debajo de 44 px de alto.
   Se usan los tokens de app.css; nada de hex nuevos hardcodeados.          */

/* 1. Fin del solape: las tarjetas crecen con su contenido. El overflow
      oculto recortaba la caja y la siguiente sección se dibujaba encima. */
.q-section    { overflow:visible; }
.q-table-wrap { overflow:visible; }
.q-main-body  { gap:var(--esp-4, 16px); }

/* 2. Objetivos táctiles: 44×44 reales con separación, en TODO control
      independiente (no solo en los principales). */
/* El stepper de cuatro pasos se retiro: decia UNA cosa —en que estado esta—
   y para decirla ocupaba una franja entera. Ahora lo dice el desplegable de
   la cabecera. Los objetivos tactiles pasan a los controles que SI existen. */
.q-estado-select, .q-editar, .q-sec-item, .q-cta, .q-cta-wa, .q-cta-ghost { min-height:36px; }
@media (pointer:coarse) {
  .q-estado-select, .q-editar, .q-sec-item, .q-cta, .q-cta-wa, .q-cta-ghost { min-height:44px; }
}
/* En pantalla tactil el chip crece hasta el objetivo de 44 px; con raton
   se queda en los 32 px del contrato visual. */
@media (pointer:coarse) { .q-filter { min-height:44px; } }
.q-item        { min-height:56px; }

/* 3. Foco visible SIEMPRE (no solo con teclado en navegadores viejos). */
.q-wrap :is(button, a, input, select, textarea, [tabindex]):focus-visible {
  outline:2px solid var(--foco, #4338ca); outline-offset:2px; border-radius:6px;
}

/* 4. Estado del documento y conversión */
.q-badge-convertida { display:inline-flex; align-items:center; gap:6px; min-height:32px; padding:0 12px;
  border-radius:999px; background:var(--acento-suave, #e0e7ff); color:var(--acento-fuerte, #4338ca);
  font-size:11.5px; font-weight:800; }
.q-acciones-barra { display:flex; flex-wrap:wrap; align-items:center; gap:var(--esp-2, 8px);
  padding:var(--esp-2, 8px) 20px; border-bottom:1px solid var(--borde, #e5e7eb); background:var(--superficie, #fff); }
.q-btn-convertir { display:inline-flex; align-items:center; gap:6px; min-height:44px; padding:0 16px;
  border:none; border-radius:10px; background:var(--acento, #6366f1); color:#fff;
  font-size:13px; font-weight:700; cursor:pointer; }
.q-btn-convertir:hover { background:var(--acento-oscuro, #4f46e5); }
.q-rel { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--texto-debil, #5b6270); }
.q-rel-link { color:var(--acento-fuerte, #4338ca); font-weight:700; text-decoration:underline; text-underline-offset:2px; }
.q-aviso-ok, .q-aviso-error { display:flex; align-items:center; gap:10px; flex:1 1 100%;
  min-height:40px; padding:var(--esp-2, 8px) 12px; border-radius:8px; font-size:12.5px; font-weight:600; }
.q-aviso-ok    { background:var(--exito-suave, #dcfce7); color:var(--exito-fuerte, #15803d); }
.q-aviso-error { background:var(--peligro-suave, #fef2f2); color:var(--peligro-fuerte, #b91c1c); }
.q-aviso-cerrar { margin-left:auto; min-width:44px; min-height:44px; border:none; background:none;
  color:inherit; cursor:pointer; font-size:13px; }
.q-ico { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; vertical-align:-2px; margin-right:6px; }

/* 5. Modal de conversión */
.q-modal-fondo { position:fixed; inset:0; z-index:var(--z-modal, 60); display:flex; align-items:center; justify-content:center;
  padding:16px; background:rgba(17,24,39,.55); }
.q-modal { width:100%; max-width:440px; background:var(--superficie, #fff); border-radius:14px;
  padding:20px; box-shadow:0 20px 40px rgba(0,0,0,.18); }
.q-modal-titulo { margin:0 0 8px; font-size:16px; font-weight:800; color:var(--texto, #111827); }
.q-modal-desc   { margin:0 0 18px; font-size:13px; line-height:1.55; color:var(--texto-debil, #4b5563); }
.q-modal-botones { display:flex; justify-content:flex-end; gap:var(--esp-2, 8px); }
.q-btn-primario, .q-btn-secundario { min-height:44px; padding:0 16px; border-radius:10px;
  font-size:13px; font-weight:700; cursor:pointer; }
.q-btn-primario   { border:none; background:var(--acento, #6366f1); color:#fff; }
.q-btn-secundario { border:1.5px solid var(--borde, #e5e7eb); background:var(--superficie, #fff); color:var(--texto-debil, #4b5563); }
.q-btn-primario[disabled], .q-btn-secundario[disabled] { opacity:.6; cursor:progress; }

/* 6. Móvil ≤640 px: cada línea deja de ser fila y pasa a FICHA.
      Cero scroll interno y sin ocultar ninguna columna: el descuento y el
      subtotal son justo lo que se revisa. */
@media (max-width:1023px) {
  .q-table, .q-table tbody, .q-table tr, .q-table td { display:block; width:100%; }
  .q-table thead { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
  .q-table tr { border:1px solid var(--borde, #e5e7eb); border-radius:12px; padding:10px 12px;
                margin:0 0 var(--esp-2, 8px); background:var(--superficie, #fff); }
  .q-table td { display:flex; align-items:center; justify-content:space-between; gap:12px;
                border:none; padding:5px 0; text-align:left !important; }
  .q-table td::before { content:attr(data-label); flex:0 0 auto; font-size:11px; font-weight:700;
                        color:var(--texto-debil, #5b6270); text-transform:uppercase; letter-spacing:.03em; }
  .q-table td[data-label=""]::before { content:none; }
  .q-table td .q-td-input { max-width:60%; min-height:44px; }
  .q-table td .q-td-sub { font-size:15px; font-weight:800; }
  .q-table-wrap { overflow-x:visible; }
  .q-acciones-barra { padding:var(--esp-2, 8px) 12px; }
  .q-modal { max-width:none; }
}


/* 8. Ajustes finos medidos en el navegador, no supuestos. */
/* 66. La linea de producto en estrecho: nombre, 'cant x precio' y el
   subtotal destacado. Cinco filas etiqueta/valor gastaban 300 px por
   producto para decir lo mismo. */
@media (max-width:1023px) {
  .q-tabla-lectura tr { padding:12px 14px; }
  .q-tabla-lectura td { height:auto; padding:2px 0; border-bottom:none; }
  .q-tabla-lectura .q-table td[data-label="Producto"] { display:block !important; padding-bottom:6px; }
  .q-tabla-lectura td[data-label="Producto"]::before { display:none; }
  .q-tabla-lectura .q-table td[data-label="Cant."],
  .q-tabla-lectura .q-table td[data-label="Precio"],
  .q-tabla-lectura .q-table td[data-label="Desc."] { display:inline-flex !important; width:auto !important; gap:6px; padding:0 14px 0 0; justify-content:flex-start; }
  .q-tabla-lectura td[data-label="Cant."]::before,
  .q-tabla-lectura td[data-label="Precio"]::before,
  .q-tabla-lectura td[data-label="Desc."]::before { font-size:11px; font-weight:500; color:var(--q-tx3); text-transform:none; }
  .q-tabla-lectura .q-table td[data-label="Subtotal"] { margin-top:8px; padding-top:8px; border-top:1px solid var(--q-bd-soft); }
  .q-tabla-lectura .q-td-acciones { position:absolute; top:10px; right:10px; width:auto; }
  .q-tabla-lectura tr { position:relative; }
  .q-tabla-pie { padding:10px 2px 0; }
}

@media (max-width:1023px) {
  .q-table td { flex-wrap:wrap; }
  .q-table td::before { flex:1 1 auto; min-width:0; }
  .q-table td .q-td-input { flex:0 0 auto; max-width:100%; width:auto; min-width:0; }
  .q-table-wrap, .q-section, .q-main-body { min-width:0; max-width:100%; overflow-x:clip; }
}
@media (max-width:360px) {
  .q-table td { flex-direction:column; align-items:stretch; gap:4px; }
  .q-table td .q-td-input { width:100%; max-width:100%; }
}

/* ZOOM AL ENFOCAR EN MOVIL.
   Chrome en Android amplia la pagina al tocar cualquier campo por debajo de
   16px, y deja la pantalla descuadrada sin forma comoda de volver. Los campos
   de aqui estaban a 13px. Es el mismo remedio que ya llevan el Constructor y
   la consulta de comprobantes: 16px reales solo en movil, que en escritorio
   no hace falta. */
@media (max-width:767px) {
  .q-search,
  .q-field input, .q-field select, .q-field textarea,
  .q-table td .q-td-input,
  .q-modal input, .q-modal select, .q-modal textarea { font-size:16px !important; }
}

/* 9. Objetivo tactil 44x44 en TODO control del modulo (no solo los nuevos):
      medido con Playwright control por control, no deducido del CSS. */
.q-wrap :is(button, [role=button], a.q-btn, select) { min-height:44px; }
.q-wrap input:not([type=checkbox]):not([type=radio]), .q-wrap select, .q-wrap textarea { min-height:44px; }
.q-btn-new, .q-add-row { min-height:44px; padding:0 14px; }
.q-td-input { min-height:44px; }
.q-item { min-height:56px; }
/* Botones de icono: area tactil completa aunque el glifo sea pequeño. */
.q-wrap button:has(> svg:only-child), .q-icon-btn { min-width:44px; min-height:44px; }
/* Separacion minima entre controles contiguos. */
.q-section-head, .q-acciones-barra { gap:var(--tactil-gap, 8px); }

/* 10. Controles de icono: el glifo es pequeño, el area tactil no.
       Medido: el boton de eliminar linea daba 20x44. */
.q-icon-btn { display:inline-flex; align-items:center; justify-content:center;
  min-width:44px; min-height:44px; border:none; background:none; cursor:pointer;
  color:var(--texto-debil, var(--texto-debil, #5b6270)); font-size:15px; border-radius:8px; }
.q-icon-btn:hover { color:var(--peligro-fuerte, #b91c1c); background:var(--peligro-suave, #fef2f2); }

/* 11. La pila de acciones del panel: 6 px medidos entre botones -> 8 px. */
.q-panel .q-btn + .q-btn, .q-panel > template + template .q-btn { margin-top:var(--tactil-gap, 8px); }
.q-panel .q-btn { margin-bottom:0; }


/* 12. Separaciones medidas en el navegador (no supuestas):
       .q-actions daba 6 px entre botones y la cabecera de seccion 6 px
       respecto al primer control de la tabla. */
.q-actions { gap:var(--tactil-gap, 8px); }
.q-section-head { padding-bottom:var(--tactil-gap, 8px); }
.q-section-head + * { margin-top:var(--esp-1, 4px); }
.q-add-row { margin-top:var(--tactil-gap, 8px); }

/* 13. Maestro-detalle REAL en movil. El mecanismo (panel==='detail' añade
       'hidden') existia pero no funcionaba: este <style> va despues de
       app.css, y .q-sidebar{display:flex} empataba en especificidad con el
       .hidden de Tailwind ganando por orden. Con dos clases se desempata.
       Medido en la captura f1c-390: la lista seguia ocupando la pantalla. */
@media (max-width:1023px) {
  .q-sidebar.hidden, .q-main.hidden { display:none; }
}

/* 14. Estrecho: la cabecera no puede empujar el titulo fuera de pantalla.
       Las acciones bajan a su propia linea y el estado se queda arriba, que
       es el dato que se consulta de un vistazo. */
@media (max-width:640px) {
  .q-head-acciones { width:100%; justify-content:flex-start; flex-wrap:wrap; }
  .q-head-sub { gap:10px; }
  .q-cta, .q-cta-wa { flex:1; justify-content:center; }
  .q-lectura { grid-template-columns:1fr; }
}

/* 15. Portatil de 1366 y 1440: el documento manda. El panel derecho cede
       ancho antes que la zona central, y la lista se estrecha un poco. */
@media (min-width:1200px) and (max-width:1599px) {
  .q-wrap { --q-lista:246px; --q-panel:248px; }
}
/* 1024-1199: no caben las tres columnas sin ahogar el documento, asi que la
   lista arranca plegada y queda a un clic en el rail. */
@media (min-width:1024px) and (max-width:1199px) {
  .q-wrap { --q-lista:240px; --q-panel:240px; }
  .q-main-title { font-size:26px; }
}
@media (min-width:1024px) and (max-width:1439px) {
  .q-item-total { font-size:12.5px; }
  .q-item-name  { font-size:13px; }
  .q-filter { padding:4px 9px; font-size:11px; }
  .q-panel   { width:268px; }
  .q-main-title { font-size:28px; }
  .q-resumen-total strong { font-size:25px; }
}

/* 7. Sin animación para quien la ha desactivado en su sistema. */
@media (prefers-reduced-motion: reduce) {
  .q-wrap *, .q-modal, .q-modal-fondo { transition:none !important; animation:none !important; }
}
</style>

<div class="q-wrap" x-init="montarHistorial(); setInterval(() => ahora = Date.now(), 30000)" x-data="{
    quotes: {{ Js::from($quotes->map(fn($q) => [
        'id'               => $q->id,
        // Numero de documento del negocio (COT-00001). El accesor cae al id
        // solo para las filas anteriores a la numeracion.
        'numero'           => $q->etiqueta,
        // Autor del documento. Sin sesion (portal publico) no hay autor: la
        // pidio el cliente.
        'autor'            => $q->autor?->name,
        'client_name'      => $q->client_name,
        'client_phone'     => $q->client_phone ?? '',
        'client_email'     => $q->client_email ?? '',
        'client_doc_type'  => $q->client_doc_type ?? '',
        'client_doc_number'=> $q->client_doc_number ?? '',
        'client_address'   => $q->client_address ?? '',
        // Canonico normalizado por QuoteStatus: las 3 filas legacy 'borrador'
        // y el default español 'pendiente' se leen bien sin migrar nada.
        'status'           => \App\Support\QuoteStatus::comercial($q->status),
        'payment_status'   => \App\Support\QuoteStatus::pago($q->payment_status),
        'pill_comercial'   => \App\Support\QuoteStatus::comercialPresentacion($q->status),
        'pill_pago'        => \App\Support\QuoteStatus::pagoPresentacion($q->payment_status),
        'vencida'          => \App\Support\QuoteStatus::vencida($q->status, $q->valid_until),
        // Strings canonicos, no floats: cualquier (float) aqui reintroduce
        // binario en el importe antes siquiera de pintarlo.
        'paid_amount'      => $q->paid_amount !== null ? \App\Support\LineMath::canon((string) $q->paid_amount) : null,
        'payment_proof_url'=> $q->payment_proof_url ?? '',
        'payment_proof_at' => $q->payment_proof_at?->format('d/m/Y H:i') ?? '',
        'reject_reason'    => $q->reject_reason ?? '',
        'rejected_at'      => $q->rejected_at?->format('d/m/Y H:i') ?? '',
        'seen_at'          => $q->seen_at?->toIso8601String() ?? '',
        'updated_at'       => $q->updated_at?->toIso8601String() ?? '',
        'client_id'        => $q->client_id,
        // String canonico (F1c): un float aqui reintroduce binario justo
        // antes de pintar/exportar. El JS suma en BigInt sobre este string.
        'total'            => \App\Support\LineMath::canon((string) $q->total),
        'notes'            => $q->notes ?? '',
        'valid_until'      => $q->valid_until?->format('Y-m-d') ?? '',
        'payment_method'   => $q->payment_method ?? '',
        'payment_condition'=> $q->payment_condition ?? '',
        'created_at'       => $q->created_at->format('d/m/Y'),
        // Fecha corta para la lista ('13 ago'): junto al codigo del
        // documento, la fecha completa no cabe sin partir la linea.
        'fecha_corta'      => $q->created_at->locale('es')->isoFormat('D MMM'),
        'token'            => $q->token ?? '',
        // Relacion 1:0..1 con el pedido (FK poblada en F1b). Es la primera
        // vez que la trazabilidad puede verse en pantalla.
        'order_id'         => $q->order?->id,
        'sent_at'          => $q->sent_at?->format('d/m/Y H:i') ?? '',
        'items'            => $q->items->map(fn($i) => [
            'id'          => $i->id,
            'description' => $i->description,
            // Dinero y porcentaje: string canonico. Cantidad: entero (no es
            // dinero, y el servidor la valida como integer).
            'price'       => \App\Support\LineMath::canon((string) $i->price),
            'quantity'    => (int) $i->quantity,
            'discount'    => \App\Support\LineMath::canon((string) ($i->discount ?? 0)),
        ])->toArray(),
    ])) }},

    clientes: {{ Js::from($clients->map(fn($c) => [
        'id'    => $c->id,
        'name'  => $c->name,
        'phone' => $c->phone ?? '',
        'email' => $c->email ?? '',
        // La cartera no guarda documento fiscal: ese dato solo vive en el
        // documento. La empresa si, y sirve para distinguir homonimos.
        'dir'   => $c->direccion ?? '',
        'empresa' => $c->empresa ?? '',
    ])) }},

    products: {{ Js::from($products->map(fn($p) => [
        'id'    => $p->id,
        'name'  => $p->name,
        'price' => \App\Support\LineMath::canon((string) $p->price),
        'sku'   => $p->sku ?? '',
        // Imagen principal del catalogo y una linea secundaria con lo que el
        // producto ya tiene guardado (categoria o SKU). Nada inventado.
        'img'   => optional($p->images->firstWhere('is_main', 1) ?: $p->images->first())->url,
        // Linea secundaria: lo primero que el producto tenga guardado.
        'sub'   => $p->category->name
                   ?? (filled($p->description) ? \Illuminate\Support\Str::limit(strip_tags($p->description), 60) : null)
                   ?? ($p->sku ? 'SKU: '.$p->sku : ''),
    ])) }},
    paymentMethods:    {{ Illuminate\Support\Js::from($paymentMethods) }},
    paymentConditions: {{ Illuminate\Support\Js::from($paymentConditions) }},

    // Capacidades resueltas en el servidor (QuoteAbilities): la vista no
    // vuelve a deducir permisos. 'convertir' ya incluye el modulo de pedidos.
    puede: {{ Illuminate\Support\Js::from($puede) }},
    // Deep link: id que venia en la URL, para abrirlo al montar.
    cotizacionInicial: {{ $cotizacionInicial ? (int) $cotizacionInicial : 'null' }},

    search: '', filterStatus: '',
    panel: 'list',
    selected: null, creating: false,
    saving: false, sending: false,
    // Guardado discreto: la cabecera dice 'Guardado hace un momento' en vez
    // de tener un boton Guardar del mismo tamano que 'Enviar cotizacion'.
    // (Comillas SIMPLES: esto viaja dentro del atributo HTML x-data, y una
    //  comilla doble aqui lo cierra y vuelca todo el componente a pantalla.)
    guardadoEn: null, ahora: Date.now(),
    actividad: [], actividadCargando: false, actividadTodo: false,
    /* Cliente y Condiciones se CONSULTAN por defecto y se editan a peticion.
       Al crear no hay nada que leer, asi que nacen abiertos. */
    /* Estado de la grilla: en que linea esta el cursor y que sugiere el
       catalogo para lo que se esta escribiendo. */
    lineaFoco: null, sugerencias: [], guardandoCampo: false, temporizadorGuardado: null,
    clientesSugeridos: [],

    /* Dialogo de confirmacion unico. Dice QUE va a pasar y con que documento,
       que es lo que un cuadro del navegador no puede contar. */
    confirmar: { abierto:false, titulo:'', descripcion:'', boton:'', tono:'peligro', ocupado:false, accion:null },
    pedirConfirmacion(opciones) {
        this.confirmar = Object.assign(
            { abierto:true, titulo:'', descripcion:'', boton:'Confirmar', tono:'peligro', ocupado:false, accion:null },
            opciones
        );
    },
    async ejecutarConfirmacion() {
        if (this.confirmar.ocupado) return;
        const accion = this.confirmar.accion;
        this.confirmar.ocupado = true;
        try { if (accion) await accion(); }
        finally { this.confirmar.ocupado = false; this.confirmar.abierto = false; }
    },
    notasAbiertas: false, notasRecortadas: false, detalleTotales: false,
    filtrosAbiertos: false,
    /* Si la nota cabe entera, 'Ver mas' no pinta nada. Se mide el recorte
       real en vez de contar caracteres, que depende del ancho de la caja. */
    medirNotas() {
        const el = this.$refs.notas;
        this.notasRecortadas = !!el && !this.notasAbiertas && el.scrollHeight > el.clientHeight + 1;
    },
    /* Plegado de la lista. Se recuerda entre visitas: quien trabaja una
       cotizacion larga la pliega una vez, no en cada carga. */
    listaAbierta: window.innerWidth >= 1200 && localStorage.getItem('cot_lista') !== '0',
    setLista(v) { this.listaAbierta = v; localStorage.setItem('cot_lista', v ? '1' : '0'); },
    convirtiendo: false, modalConvertir: false, convertidoOrderId: null,
    catalogSearch: '', catalogOpen: false,
    portalUrl: '',

    form: {
        client_name:'', client_phone:'', client_email:'',
        client_doc_type:'', client_doc_number:'', client_address:'',
        notes:'', valid_until:'', status:'draft',
        payment_status:'pending', paid_amount:'',
        payment_method:'', payment_condition:'',
        items: []
    },
    payingStatus: false,

    get filtered() {
        /* BUSCAR POR LO QUE LA GENTE TECLEA.
           Buscaba solo por nombre de cliente y telefono: quien tenia el papel
           delante y escribia el numero (COT-00009) o el RUC no encontraba
           nada, y parecia que el buscador estaba roto. Ahora entra tambien el
           numero, el documento y el correo.

           Ademas `q.client_name.toLowerCase()` reventaba si el nombre venia
           vacio, y un error ahi no vacia una fila: tumba TODA la lista, que
           es como se ve un fallo total de la pantalla. */
        const t = (this.search || '').trim().toLowerCase();
        return this.quotes.filter(q => {
            const s = !t || [
                q.numero, q.client_name, q.client_phone,
                q.client_doc_number, q.client_email,
            ].some(v => String(v ?? '').toLowerCase().includes(t));
            const f = !this.filterStatus || q.status === this.filterStatus;
            return s && f;
        });
    },

    /* Aqui vivian los tres calculos de cobro de la cotizacion. Sumaban en el
       navegador lo que el cliente supuestamente debia: una sexta aritmetica
       de dinero paralela, y sobre un documento que no se cobra. El dinero de
       verdad lo lleva Cobranza sobre los pedidos.
       (Ojo: este objeto viaja dentro de un atributo HTML x-data, asi que aqui
       NO pueden aparecer comillas dobles ni siquiera en un comentario.) */


    // Un cambio del cliente (rechazo o comprobante subido) que el vendedor
    // todavia no vio en el panel — no cuenta la simple aceptacion porque esa
    // ya se nota sola por el color verde del estado.
    isUnseen(q) {
        const lastClientEvent = q.rejected_at || q.payment_proof_at;
        if (!lastClientEvent) return false;
        if (!q.seen_at) return true;
        return new Date(q.updated_at || 0) > new Date(q.seen_at);
    },

    get unseenCount() { return this.quotes.filter(q => this.isUnseen(q)).length; },

    duplicating: false,
    async duplicateQuote() {
        if (!this.selected || this.duplicating) return;
        this.duplicating = true;
        const res = await fetch('{{ $quotesApiBase }}/'+this.selected.id+'/duplicate', {
            method: 'POST', headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
        });
        this.duplicating = false;
        if (!res.ok) return;
        const data = await res.json();
        const q = data.quote;
        if (!q) return;
        /* Se parte de lo que devuelve el servidor y solo se normaliza lo
           que la plantilla necesita en un formato concreto. Antes se
           enumeraban los campos a mano y se quedaba fuera `numero`: la
           cotizacion recien creada aparecia en la lista SIN su codigo
           hasta recargar la pagina. */
        const row = { ...q,
            client_phone: q.client_phone||'', client_email: q.client_email||'',
            client_doc_type: q.client_doc_type||'', client_doc_number: q.client_doc_number||'',
            client_address: q.client_address||'', client_id: q.client_id||null,
            payment_status: q.payment_status||'pending', paid_amount: q.paid_amount??null,
            total: String(q.total ?? '0.00'), notes: q.notes||'',
            created_at: q.created_at||new Date().toLocaleDateString('es'),
            items: q.items||[] };
        this.quotes.unshift(row);
        this.select(row);
        window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Cotización duplicada como borrador', type: 'success' } }));
    },

    bulkIds: [],
    bulkRunning: false,
    get bulkActive() { return this.bulkIds.length > 0; },
    toggleBulk(id) { const i=this.bulkIds.indexOf(id); if(i>-1) this.bulkIds.splice(i,1); else this.bulkIds.push(id); },
    clearBulk() { this.bulkIds = []; },
    async bulkDelete() {
        if (!this.bulkIds.length || this.bulkRunning) return;
        const ids = [...this.bulkIds];
        this.pedirConfirmacion({
            titulo: 'Eliminar ' + ids.length + (ids.length === 1 ? ' cotización' : ' cotizaciones'),
            descripcion: 'Se eliminarán los documentos seleccionados y sus líneas. Esta acción no se puede deshacer.',
            boton: 'Eliminar ' + ids.length,
            accion: () => this.bulkDeleteConfirmado(ids),
        });
    },

    async bulkDeleteConfirmado(ids) {
        this.bulkRunning = true;
        for (const id of [...this.bulkIds]) {
            await fetch('{{ $quotesApiBase }}/'+id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        }
        this.quotes = this.quotes.filter(q => !this.bulkIds.includes(q.id));
        if (this.selected && this.bulkIds.includes(this.selected.id)) { this.selected = null; this.creating = false; }
        this.bulkRunning = false;
        this.clearBulk();
    },

    get filteredCatalog() {
        if (!this.catalogSearch) return this.products.slice(0,10);
        const q = this.catalogSearch.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku&&p.sku.toLowerCase().includes(q))).slice(0,10);
    },

    /* Espejo JS de App\Support\LineMath — MISMO contrato entero.
       BigInt obligatorio: cents*qty*(10000-bp) llega a 10^18 y excede
       Number.MAX_SAFE_INTEGER (2^53). Number solo al formatear centavos. */
    lmCanon(v) {
        // Canonizador por REGEX, sin redondeo: '10.005' NO se convierte en
        // '10.01' (eso evadia el rechazo de 3dp). Valido 0-2dp -> se rellena a
        // 2dp; invalido -> viaja tal cual y el servidor responde 422.
        const s = String(v ?? '0').trim();
        if (s === '') return '0.00';
        const m = s.match(/^(\d+)(?:\.(\d{1,2}))?$/);
        if (!m) return s;
        return m[1] + '.' + (m[2] || '').padEnd(2, '0');
    },
    lmCents(s) {
        s = String(s ?? '0').trim(); if (s === '') s = '0';
        if (!/^\d+(\.\d{1,2})?$/.test(s)) return null;      // invalido -> null (la UI marca, el server rechaza)
        const [e, d = ''] = s.split('.');
        return BigInt(e) * 100n + BigInt(d.padEnd(2, '0'));
    },
    lmLineCents(price, qty, disc) {
        const c = this.lmCents(price), bp = this.lmCents(disc); // bp = % con 2dp -> basis points
        // Cantidad: ENTERO 1..10000. 1.5 no se trunca a 1: es estado invalido
        // (la linea muestra em-dash y el servidor respondera 422).
        const qn = Number(qty);
        if (!Number.isInteger(qn) || qn < 1 || qn > 10000) return null;
        if (c === null || bp === null || bp > 10000n) return null;
        return (c * BigInt(qn) * (10000n - bp) + 5000n) / 10000n; // division BigInt = trunc; +5000 = half-up
    },
    lmFmt(cents) {
        if (cents === null) return '—';
        const neg = cents < 0n ? '-' : ''; const a = cents < 0n ? -cents : cents;
        return neg + (a / 100n) + '.' + String(a % 100n).padStart(2, '0');
    },
    lineTotal(i) { return this.lmLineCents(i.price, i.quantity, i.discount); },

    /* Centavos exactos (BigInt) del documento: la fuente para presentar. */
    get subtotalCents() { let c = 0n; for (const i of this.form.items) { const lc = this.lineTotal(i); if (lc !== null) c += lc; } return c; },
    /* subtotal/grandTotal se conservan como numeros SOLO para comparaciones
       logicas (>0). Nada de dinero se pinta desde ellos: la vista usa
       subtotalCents / grandTotalCents. */
    get subtotal() { return Number(this.subtotalCents) / 100; },
    get grandTotalCents() { return this.subtotalCents; },
    /* Bruto = suma de precio x cantidad SIN descuento; descuento = lo que se
       resta. `quotes` no guarda ni subtotal ni impuesto: el descuento vive por
       linea, asi que el resumen lo agrega aqui en centavos enteros. */
    get brutoCents() {
        let c = 0n;
        for (const i of this.form.items) {
            if (!i.description) continue;
            c += this.lmCentsDe(i.price) * BigInt(parseInt(i.quantity) || 0);
        }
        return c;
    },
    /* Un documento se edita mientras siga vivo. Convertido ya engendro un
       pedido: reescribirlo haria mentir a esa trazabilidad, y el servidor lo
       rechaza con 422 —asi que aqui tampoco se ofrece. */
    get editable() { return this.puede.editar && !this.esConvertida; },

    /* La ultima fila siempre esta vacia: es donde se escribe la siguiente
       linea. Sin esto habria que pulsar 'agregar' antes de cada producto. */
    asegurarFilaVacia() {
        const ultima = this.form.items[this.form.items.length - 1];
        if (!ultima || String(ultima.description || '').trim() !== '') {
            this.form.items.push({description:'', price:'', quantity:1, discount:0});
        }
    },

    /* Cartera del negocio: se busca por nombre, telefono o documento,
       que son las tres formas en que se identifica a alguien por telefono. */
    buscarCliente(valor) {
        const q = String(valor || '').trim().toLowerCase();
        if (q.length < 2) { this.clientesSugeridos = []; return; }
        this.clientesSugeridos = this.clientes.filter(c =>
            c.name.toLowerCase().includes(q) ||
            (c.phone || '').includes(q) ||
            (c.empresa || '').toLowerCase().includes(q)
        ).slice(0, 6);
    },

    /* Enlaza el documento con la ficha y trae sus datos. Sin esto el nombre
       era texto suelto y el cliente no acumulaba historial. */
    usarCliente(c) {
        this.form.client_id     = c.id;
        this.form.client_name   = c.name;
        this.form.client_phone  = c.phone || this.form.client_phone;
        this.form.client_email  = c.email || this.form.client_email;
        this.form.client_address = c.dir || this.form.client_address;
        this.clientesSugeridos = [];
        this.guardarCampo();
    },

    alEscribirLinea(i, valor) {
        this.lineaFoco = i;
        const q = String(valor || '').trim().toLowerCase();
        this.sugerencias = q.length < 2 ? [] : this.products
            .filter(pr => pr.name.toLowerCase().includes(q) || (pr.sku || '').toLowerCase().includes(q))
            .slice(0, 6);
        this.asegurarFilaVacia();
    },

    cerrarSugerencias() { setTimeout(() => { this.sugerencias = []; this.lineaFoco = null; }, 120); },

    /* Elegir del catalogo trae nombre y precio; la cantidad se respeta si ya
       se habia escrito. */
    usarProducto(i, pr) {
        this.form.items[i].description = pr.name;
        this.form.items[i].price = pr.price;
        if (!this.form.items[i].quantity) this.form.items[i].quantity = 1;
        this.sugerencias = []; this.lineaFoco = null;
        this.asegurarFilaVacia();
        this.guardarCampo();
        this.$nextTick(() => this.$refs['qty' + i]?.focus());
    },

    quitarLinea(i) {
        const linea = this.form.items[i];
        const quitar = () => {
            this.form.items.splice(i, 1);
            this.asegurarFilaVacia();
            this.guardarCampo();
        };
        // Una linea en blanco se va sin preguntar; una con importe, no.
        if (!linea || !String(linea.description || '').trim()) return quitar();
        this.pedirConfirmacion({
            titulo: 'Quitar esta línea',
            descripcion: '«' + linea.description + '» por ' + this.lmMoneda(this.lineTotal(linea))
                + ' saldrá de la cotización y el total se recalculará.',
            boton: 'Quitar línea',
            accion: quitar,
        });
    },

    /* Guardado al salir del campo, agrupando los cambios seguidos: escribir
       nombre y telefono no son dos guardados. Sin boton 'Guardar': la
       cabecera dice cuando fue el ultimo. */
    guardarCampo() {
        if (!this.editable || this.creating || !this.selected) return;
        clearTimeout(this.temporizadorGuardado);
        this.temporizadorGuardado = setTimeout(() => this.save(), 600);
    },

    /* La caja de notas crece con su contenido en vez de dejar un scroll de
       dos lineas. */
    autoAlto(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 260) + 'px';
    },

    get actividadVisible() { return this.actividadTodo ? this.actividad : this.actividad.slice(0, 4); },
    get descuentoCents() {
        const d = this.brutoCents - this.subtotalCents;
        return d > 0n ? d : 0n;
    },
    /* El PDF del documento, el mismo que se manda al cliente. */
    get pdfUrl() {
        return this.selected && !this.creating ? '{{ $quotesApiBase }}/' + this.selected.id + '/pdf' : '';
    },
    get igv()      { return this.subtotal * 0; },
    get grandTotal(){ return this.subtotal + this.igv; },

    /* Presentacion del dinero (F1c). Espejo de LineMath::present: agrupa los
       miles sobre el STRING exacto, sin float. El panel mostraba 'S/ 19345.50'
       mientras el portal ya decia 'S/ 19,345.50': el mismo importe se veia
       distinto segun quien lo mirara. */
    lmPresent(exacto) {
        const m = /^(-?)(\d+)\.(\d{2})$/.exec(String(exacto));
        if (!m) return String(exacto);
        return m[1] + m[2].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + m[3];
    },
    /* Importe canonico desde centavos BigInt, ya con separadores. */
    lmMoneda(cents) {
        const s = this.lmFmt(cents);
        return s === '—' ? s : '{{ $currency }} ' + this.lmPresent(s);
    },
    /* Centavos BigInt desde un decimal canonico. Frontera unica de entrada. */
    lmCentsDe(txt) {
        const m = /^(-?)(\d+)(?:\.(\d{1,2}))?$/.exec(String(txt ?? '').trim());
        if (!m) return 0n;
        const v = BigInt(m[2]) * 100n + BigInt((m[3] || '').padEnd(2, '0'));
        return m[1] === '-' ? -v : v;
    },
    /* Formateador unico del panel: acepta centavos BigInt o decimal canonico.
       No hay ruta float para el dinero (F1c, exigencia de auditoria). */
    fmt(n) {
        const cents = typeof n === 'bigint' ? n : this.lmCentsDe(n);
        return '{{ $currency }} ' + this.lmPresent(this.lmFmt(cents));
    },

    statusLabel: { draft:'Borrador', sent:'Enviada', accepted:'Aceptada', rejected:'Rechazada', converted:'Convertida' },

    /* Aqui vivia claseEstado(), que daba color a cada paso del stepper
       comparando posiciones dentro del flujo draft->sent->accepted. Como
       'rejected' esta FUERA de esa secuencia, la comparacion la marcaba como
       completada —Rechazada en verde estando la cotizacion en Borrador—.
       Con el estado como desplegable, el color lo da una clase por estado y
       esa comparacion ya no existe. */

    /* Una convertida es documento historico: ni se edita ni cambia de estado. */
    get esConvertida() { return this.form.status === 'converted'; },
    /* El boton solo aparece si el servidor lo permitiria: permiso + modulo
       (ya resueltos en QuoteAbilities) y estado aceptado. */
    get puedeConvertirAhora() {
        return this.puede.convertir && !this.creating && !!this.selected && this.form.status === 'accepted';
    },
    /* Pedido ya generado por esta cotizacion, si lo hay. */
    get pedidoDeEstaCotizacion() {
        return this.convertidoOrderId || (this.selected && this.selected.order_id) || null;
    },

    async convertir() {
        if (this.convirtiendo) return;              // doble clic: no dispara dos veces
        if (!this.selected) { this.error = 'Elige primero una cotización.'; return; }
        this.convirtiendo = true;
        try {
            const res = await fetch(this.urlConvertir(this.selected.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.error = data.message || 'No se pudo convertir la cotización.';
                this.modalConvertir = false;
                return;
            }
            this.convertidoOrderId = data.order_id;
            this.form.status = 'converted';
            const q = this.quotes.find(x => x.id === this.selected.id);
            if (q) { q.status = 'converted'; q.order_id = data.order_id;
                     q.pill_comercial = { label: 'Convertida', cls: 's-done', heredado: false }; }
            this.selected.status = 'converted';
            this.selected.order_id = data.order_id;
            // 'already' no es un error: es la respuesta correcta de un endpoint
            // idempotente cuando alguien reintenta.
            this.avisoConvertir = data.already
                ? 'Esta cotización ya estaba convertida en el pedido #' + data.order_id + '.'
                : 'Pedido #' + data.order_id + ' creado a partir de esta cotización.';
            this.modalConvertir = false;
        } catch (e) {
            this.error = 'Error de conexión al convertir.';
            this.modalConvertir = false;
        } finally {
            this.convirtiendo = false;
        }
    },
    avisoConvertir: '',
    error: '',

    urlConvertir(id) { return '{{ $urlConvertirPlantilla }}'.replace('__ID__', id); },
    urlDetalle(id)   { return '{{ $urlDetallePlantilla }}'.replace('__ID__', id); },
    urlPedido(id)    { return '{{ $esComercial ? route('bixosales.pedidos.show', ['order' => '__ID__']) : route('orders.show', ['order' => '__ID__']) }}'.replace('__ID__', id); },

    /* ---- Deep link e historial (paridad con Pedidos) ---------------------
       Abrir una cotizacion cambia la URL; Atras/Adelante restauran la
       seleccion. Antes, /cotizaciones/{id} devolvia JSON crudo al navegador. */
    sincronizarUrl(id) {
        const url = id ? this.urlDetalle(id) : '{{ $quotesApiBase }}';
        if (window.location.pathname === url) return;
        /* pushState puede lanzar (origen distinto, historial lleno). Si lo
           hacia a mitad de select(), la cotizacion se abria A MEDIAS: el
           titulo cambiaba y el formulario se quedaba con los datos de la
           anterior. La URL bonita es comodidad; abrir el documento es la
           funcion. Visto en la validacion visual. */
        try { history.pushState({ cotizacion: id || null }, '', url); }
        catch (e) { /* sin URL bonita, pero el documento se abre igual */ }
    },
    montarHistorial() {
        window.addEventListener('popstate', (e) => {
            const id = e.state?.cotizacion ?? null;
            if (!id) { this.cerrarDetalle(false); return; }
            const q = this.quotes.find(x => x.id === id);
            if (q) this.select(q, false);
        });
        if (this.cotizacionInicial) {
            const q = this.quotes.find(x => x.id === this.cotizacionInicial);
            if (q) { this.select(q, false); history.replaceState({ cotizacion: q.id }, '', window.location.pathname); }
        }
    },
    ultimaFilaId: null,

    cerrarDetalle(empujar = true) {
        const volverA = this.ultimaFilaId;
        this.selected = null; this.creating = false; this.panel = 'list';
        this.avisoConvertir = ''; this.error = '';
        if (empujar) this.sincronizarUrl(null);
        // Devolver el foco a la fila de origen: quien navega con teclado no
        // debe acabar al principio del documento. Se busca por dataset y no
        // con un selector de atributo, para no anidar comillas dentro de este
        // atributo x-data (una comilla suelta aqui parte TODO el componente).
        this.$nextTick(() => {
            const filas = Array.from(document.querySelectorAll('.q-item'));
            const fila = filas.find(e => Number(e.dataset.quoteId) === Number(volverA));
            if (fila) fila.focus();
        });
    },

    select(q, empujar = true) {
        this.ultimaFilaId = q.id;          // para devolver el foco al cerrar
        this.avisoConvertir = ''; this.error = '';
        this.convertidoOrderId = null;
        this.selected = {...q};
        if (empujar) this.sincronizarUrl(q.id);
        this.form = {
            client_name: q.client_name||'', client_phone: q.client_phone||'',
            client_email: q.client_email||'', client_doc_type: q.client_doc_type||'',
            client_doc_number: q.client_doc_number||'', client_address: q.client_address||'',
            client_id: q.client_id || null,
            notes: q.notes||'', valid_until: q.valid_until||'', status: q.status,
            payment_status: q.payment_status||'pending', paid_amount: q.paid_amount ?? '',
            payment_method: q.payment_method||'', payment_condition: q.payment_condition||'',
            items: q.items && q.items.length ? q.items.map(i=>({...i,discount:i.discount||0})) : [{description:'',price:'',quantity:1,discount:0}]
        };
        this.portalUrl = q.token ? '{{ url('/b/'.$project->slug.'/c/') }}/' + q.token : '';
        this.creating = false;
        this.guardadoEn = null;
        this.asegurarFilaVacia();
        this.cargarActividad(q.id);
        this.$nextTick(() => this.autoAlto(this.$refs.notas));
        if(window.innerWidth < 1024) { this.panel = 'detail'; window.scrollTo({top:0}); }
        if (this.isUnseen(q)) {
            q.seen_at = new Date().toISOString();
            fetch('{{ $quotesApiBase }}/'+q.id+'/seen', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        }
    },

    openNew() {
        this.selected = null; this.creating = true; this.portalUrl = '';
        this.actividad = []; this.guardadoEn = null;
        this.form = { client_name:'', client_phone:'', client_email:'', client_doc_type:'', client_doc_number:'', client_address:'', client_id:null, notes:'', valid_until:'', status:'draft', payment_status:'pending', paid_amount:'', payment_method:'', payment_condition:'', items:[{description:'',price:'',quantity:1,discount:0}] };
        if(window.innerWidth < 1024) { this.panel = 'detail'; window.scrollTo({top:0}); }
    },

    /* El catalogo se abre a peticion y con el foco puesto en el buscador:
       antes ocupaba una franja permanente aunque no se agregara nada. */
    abrirCatalogo() {
        if (!this.products.length) { this.addItem(); return; }
        this.catalogOpen = true; this.catalogSearch = '';
        this.$nextTick(() => this.$refs.catalogo?.focus());
    },

    addItem()     { this.form.items.push({description:'',price:'',quantity:1,discount:0}); this.$nextTick(()=>{ const inputs=document.querySelectorAll('.q-td-input.desc'); if(inputs.length) inputs[inputs.length-1].focus(); }); },
    removeItem(i) { if(this.form.items.length>1) this.form.items.splice(i,1); },

    addFromCatalog(p) {
        const existing = this.form.items.find(i => i.description === p.name);
        // parseFloat sobre CANTIDAD, no sobre dinero: aqui no hay importe.
        if (existing) { existing.quantity = (parseFloat(existing.quantity)||1) + 1; }
        else { this.form.items.push({description:p.name, price:p.price, quantity:1, discount:0}); }
        this.catalogSearch = ''; this.catalogOpen = false;
    },

    /* 'Y-m-d' es lo que exige <input type=date>; en lectura se dice
       29/08/2026, que es como se escribe una fecha aqui. */
    /* El descuento llega como decimal canonico ('0.00', '10.00'). Se decide
       y se presenta SOBRE EL STRING: convertirlo a Number aqui abriria una
       ruta numerica sobre un valor que el sistema trata como exacto, y el
       contrato de dinero de esta pantalla lo prohibe. */
    /* Indice del catalogo por nombre, para recuperar imagen y subtitulo de
       una linea. Se construye una vez: recorrerlo por cada celda de cada
       fila en cada repintado seria cuadratico. */
    get catalogoIndice() {
        const m = {};
        for (const p of this.products) m[(p.name || '').trim().toLowerCase()] = p;
        return m;
    },
    catalogoDe(desc) {
        return this.catalogoIndice[String(desc || '').trim().toLowerCase()] || null;
    },

    descLegible(d) {
        const t = String(d ?? '0').trim();
        return (t || '0.00') + '%';
    },

    fechaLarga(iso) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(iso || ''));
        return m ? m[3] + '/' + m[2] + '/' + m[1] : (iso || '');
    },

    get guardadoHace() {
        if (!this.guardadoEn) return '';
        const s = Math.max(0, Math.floor((this.ahora - this.guardadoEn) / 1000));
        if (s < 60) return 'hace un momento';
        const m = Math.floor(s / 60);
        if (m < 60) return m === 1 ? 'hace 1 minuto' : 'hace ' + m + ' minutos';
        const h = Math.floor(m / 60);
        return h === 1 ? 'hace 1 hora' : 'hace ' + h + ' horas';
    },

    /* La actividad se pide al abrir el documento: son pocos eventos y
       cambian cuando el cliente actua, asi que no sirve cachearla. */
    async cargarActividad(id) {
        this.actividad = []; this.actividadTodo = false;
        if (!id) return;
        this.actividadCargando = true;
        try {
            const r = await fetch('{{ $quotesApiBase }}/' + id + '/events', {headers:{'Accept':'application/json'}});
            if (r.ok) { this.actividad = (await r.json()).eventos || []; }
        } catch (e) { this.actividad = []; }
        this.actividadCargando = false;
    },

    async save() {
        if (!this.form.client_name.trim()) {
            bxAviso('Falta el nombre del cliente', 'warning');
            document.getElementById('cli-nombre')?.focus();
            return;
        }
        this.saving = true;
        const base = '{{ $quotesApiBase }}';
        const url  = this.creating ? base : base + '/' + this.selected.id;
        const method = this.creating ? 'POST' : (this.selected ? 'PUT' : 'POST');
        const body = {
            client_name: this.form.client_name, client_phone: this.form.client_phone,
            client_email: this.form.client_email, client_doc_type: this.form.client_doc_type,
            client_doc_number: this.form.client_doc_number, client_address: this.form.client_address,
            // El enlace con la ficha del cliente: sin esto el documento no
            // aparece en su historial ni suma a su deuda.
            client_id: this.form.client_id,
            notes: this.form.notes, valid_until: this.form.valid_until,
            payment_method: this.form.payment_method, payment_condition: this.form.payment_condition,
            status: this.form.status,
            items: this.form.items.filter(i=>i.description).map(i=>({
                description: i.description,
                // Contrato F1b: strings decimales canonicos de 2dp + entero.
                // El descuento VIAJA en el payload (antes se omitia y el
                // servidor guardaba 0: el total visual mentia).
                price:    this.lmCanon(i.price),
                // Sin truncado: 1.5 viaja tal cual y el servidor responde 422
                // (el espejo ya marca la linea como invalida). Solo el vacio
                // cae a 1 como comodidad de captura.
                quantity: i.quantity === '' || i.quantity === null ? 1 : Number(i.quantity),
                discount: this.lmCanon(i.discount ?? 0),
            })),
        };
        if (!this.creating) body.status = this.form.status;
        const fullUrl = this.creating ? base : base + '/' + this.selected.id + '/full';
        const res = await fetch(fullUrl, { method: this.creating ? 'POST' : 'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify(body) });
        if (!res.ok) {
            // 422 y compañia: mostrar el motivo y CONSERVAR el formulario.
            let msg = 'No se pudo guardar la cotización.';
            try { const err = await res.json(); msg = err.message || Object.values(err.errors||{}).flat()[0] || msg; } catch(e) {}
            this.saving = false;
            bxAviso(msg, 'error');
            return;
        }
        const data = await res.json();
        const q = data.quote;
        if (q) {
            const display = {...q, items: q.items||this.form.items, total:String(q.total ?? '0.00')};
            /* Se parte de lo que devuelve el servidor y solo se normaliza lo
               que la plantilla necesita en un formato concreto. Antes se
               enumeraban los campos a mano y se quedaba fuera `numero`: la
               cotizacion recien creada aparecia en la lista SIN su codigo
               hasta recargar la pagina. */
            const row = { ...q,
                client_phone: q.client_phone||'', client_email: q.client_email||'',
                client_doc_type: q.client_doc_type||'', client_doc_number: q.client_doc_number||'',
                client_address: q.client_address||'', client_id: q.client_id||null,
                payment_status: q.payment_status||'pending', paid_amount: q.paid_amount??null,
                total: String(q.total ?? '0.00'), notes: q.notes||'',
                created_at: q.created_at||new Date().toLocaleDateString('es'),
                items: q.items||[] };
            if (this.creating) { this.quotes.unshift(row); } else { const idx=this.quotes.findIndex(x=>x.id===q.id); if(idx>-1) this.quotes[idx]=row; }
            this.selected = row; this.creating = false;
            this.form.items = (q.items||[]).map(i=>({...i,discount:i.discount||0}));
            // Se marca el momento del guardado; el reloj de la cabecera lo
            // convierte en 'hace un momento' / 'hace 3 minutos'.
            this.guardadoEn = Date.now();
            this.asegurarFilaVacia();
            this.cargarActividad(this.selected.id);
        }
        this.saving = false;
    },

    del() {
        // Antes se salia en silencio: el boton parecia roto. Con el reordenado
        // de movil arreglado ya no se llega aqui sin cotizacion, pero si pasara
        // hay que decirlo en vez de no hacer nada.
        if (!this.selected) { this.error = 'Elige primero la cotización que quieres eliminar.'; return; }
        const q = this.selected;
        this.pedirConfirmacion({
            titulo: 'Eliminar ' + (q.numero || 'la cotización'),
            descripcion: 'Se eliminará el documento de ' + (q.client_name || 'este cliente')
                + ' por ' + this.fmt(q.total) + ' y sus líneas. Esta acción no se puede deshacer.',
            boton: 'Eliminar cotización',
            accion: async () => {
                const r = await fetch('{{ $quotesApiBase }}/'+q.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
                if (!r.ok) { this.error = 'No se pudo eliminar la cotización.'; return; }
                this.quotes = this.quotes.filter(x => x.id !== q.id);
                this.selected = null; this.creating = false; this.panel = 'list';
            },
        });
    },

    async setStatus(s) {
        this.form.status = s;
        if (!this.creating && this.selected) await this.save();
    },

    /* Aqui vivia el marcado de cobro: pasaba una COTIZACION a pagada, parcial
       o pendiente. No se cobra un presupuesto — se convierte en pedido y se
       cobra el pedido. */

    sendToClient() {
        if (!this.selected) return;
        // Un primer envio no necesita confirmacion; un REenvio si: el cliente
        // ya recibio ese enlace y va a volver a recibirlo.
        if (!this.selected.token) return this.enviarAlCliente();
        this.pedirConfirmacion({
            titulo: 'Reenviar al cliente',
            descripcion: 'Se generará de nuevo el enlace de ' + (this.selected.numero || 'la cotización')
                + ' y el documento quedará como Enviada. El cliente verá la versión actual.',
            boton: 'Reenviar',
            tono: 'principal',
            accion: () => this.enviarAlCliente(),
        });
    },

    async enviarAlCliente() {
        this.sending = true;
        const res = await fetch('{{ $quotesApiBase }}/'+this.selected.id+'/send', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        const data = await res.json();
        if (data.ok) {
            this.portalUrl = data.url;
            const idx = this.quotes.findIndex(q=>q.id===this.selected.id);
            if(idx>-1){ this.quotes[idx].token=data.quote.token; this.quotes[idx].status='sent'; }
            this.selected = {...this.selected, token:data.quote.token, status:'sent'};
            this.form.status = 'sent';
        }
        this.sending = false;
    },

    sendWhatsApp() {
        const link = this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/'+this.selected.token : '');
        if (!link) { bxAviso('Primero envía la cotización para generar su enlace', 'warning'); return; }
        const wa = '{{ preg_replace('/\D/','', $project->whatsapp ?? '') }}';
        if (!wa) { bxAviso('Configura el número de WhatsApp en Ajustes del negocio', 'warning'); return; }
        const msg = 'Hola ' + (this.selected?.client_name||'') + ', te comparto tu cotización: ' + link;
        window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg),'_blank');
    },

    copyLink() {
        const link = this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/'+this.selected.token : '');
        if (link) navigator.clipboard.writeText(link);
    },
}">

{{-- ══ SIDEBAR LISTA ══ --}}
<div class="q-sidebar" :class="[panel==='detail' ? 'hidden md:flex' : 'flex', listaAbierta ? '' : 'q-lista-plegada']">
    <div class="q-sidebar-titulo">
        <h2>Cotizaciones</h2>
        <button @click="openNew()" class="q-btn-new" x-show="puede.crear" title="Nueva cotización" aria-label="Nueva cotización">+</button>
    </div>
    <div class="q-sidebar-head">
        {{-- El marcador dice POR QUE se puede buscar: "Buscar cotizacion..."
             no insinuaba que el numero o el RUC sirvieran, y quien tenia el
             papel delante tecleaba el nombre a mano. --}}
        <input type="text" x-model="search" placeholder="Número, cliente, RUC/DNI o teléfono…" class="q-search">
        {{-- Atajo a los filtros: la referencia lo pone junto al buscador. --}}
        <button type="button" class="q-filtros-btn" @click="filtrosAbiertos = !filtrosAbiertos"
                :aria-expanded="filtrosAbiertos ? 'true' : 'false'" aria-label="Filtros" title="Filtros">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12M3 6h18M9 18h6"/></svg>
        </button>
    </div>
    {{-- Aqui vivian "POR COBRAR" y "COBRADO ESTE MES".
         No se cobra una cotizacion: es un documento pre-venta, sin efecto
         contable. La deuda nace del pedido y su comprobante, y se gestiona en
         Cobranza. Tenerlos aqui invitaba a registrar dinero contra un
         presupuesto — asi es como el "por cobrar" del negocio acababa
         incluyendo plata que nadie debe. --}}
    <div class="q-filters">
        <button @click="filterStatus=''" class="q-filter" :class="filterStatus==='' ? 'active':''">Todas</button>
        <button @click="filterStatus='draft'" class="q-filter" :class="filterStatus==='draft' ? 'active':''">Borrador</button>
        <button @click="filterStatus='sent'" class="q-filter" :class="filterStatus==='sent' ? 'active':''">Enviadas</button>
        <button @click="filterStatus='accepted'" class="q-filter" :class="filterStatus==='accepted' ? 'active':''">Aceptadas</button>
        {{-- El filtro "por cobrar" tambien sale: filtraba cotizaciones por un
             estado de cobro que no les corresponde. Lo que sí importa aqui es
             cuáles están aceptadas y aún sin convertir en pedido. --}}
        <button @click="filterStatus='converted'" class="q-filter" :class="filterStatus==='converted' ? 'active':''" x-show="filtrosAbiertos || filterStatus==='converted'" x-cloak>Convertidas</button>
    </div>
    {{-- El recuento aparece solo cuando hay un filtro puesto: con la lista
         completa a la vista no aporta nada. --}}
    <div class="q-lista-cuenta" x-show="search || filterStatus" x-cloak
         x-text="filtered.length + (filtered.length === 1 ? ' resultado' : ' resultados')"></div>

    {{-- Barra de selección múltiple --}}
    <div x-show="bulkActive" x-cloak style="display:flex;align-items:center;justify-content:space-between;padding:6px 12px;background:#eef2ff;border-bottom:1px solid #e0e7ff">
        <span style="font-size:11.5px;font-weight:700;color:#4338ca" x-text="bulkIds.length + ' seleccionada' + (bulkIds.length!==1?'s':'')"></span>
        <div style="display:flex;gap:8px;align-items:center">
            <button type="button" @click="bulkDelete()" :disabled="bulkRunning" style="font-size:11px;font-weight:700;color:#dc2626;border:none;background:none;cursor:pointer">Eliminar</button>
            <button type="button" @click="clearBulk()" style="font-size:11px;color:#6b7280;border:none;background:none;cursor:pointer">Cancelar</button>
        </div>
    </div>

    <div class="q-list">
        {{-- Estado vacio DISEÑADO (DoD), con salida segun el caso. --}}
        <div x-show="filtered.length === 0" x-cloak
             style="padding:48px 20px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px">
            <svg style="width:44px;height:44px;color:var(--borde, #e5e7eb)" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <template x-if="quotes.length === 0">
                <div>
                    <p style="font-weight:700;color:var(--texto, #111827)">Aún no hay cotizaciones</p>
                    <p style="font-size:13px;color:var(--texto-debil, var(--texto-debil, #5b6270));margin-top:2px">Crea la primera y envíasela a tu cliente.</p>
                    <button type="button" @click="openNew()" class="q-btn-new" style="margin-top:12px">+ Nueva cotización</button>
                </div>
            </template>
            <template x-if="quotes.length > 0">
                <div>
                    <p style="font-weight:700;color:var(--texto, #111827)">Ninguna coincide con la búsqueda</p>
                    <p style="font-size:13px;color:var(--texto-debil, var(--texto-debil, #5b6270));margin-top:2px">Prueba con otro texto o quita el filtro.</p>
                    <button type="button" @click="search=''; filterStatus='';" class="q-btn-new" style="margin-top:12px">Limpiar</button>
                </div>
            </template>
        </div>
        <template x-for="q in filtered" :key="q.id">
            <div class="q-item" :class="selected && selected.id===q.id ? 'active':''" :data-quote-id="q.id" tabindex="0" role="button" :aria-current="selected && selected.id===q.id ? 'true' : 'false'" @click="select(q)" @keydown.enter="select(q)" @keydown.space.prevent="select(q)">
                <div class="q-item-body">
                    {{-- El documento se identifica por su codigo; el cliente y
                         la fecha lo acompañan. --}}
                    <div class="q-item-fila1">
                        <span class="q-item-num">
                            <span x-show="isUnseen(q)" class="q-item-punto" title="Cambio nuevo del cliente"></span>
                            <span x-text="q.numero"></span>
                        </span>
                        <span class="q-item-total" x-text="fmt(q.total)"></span>
                    </div>
                    <div class="q-item-fila2">
                        <span class="q-item-cliente" x-text="q.client_name"></span>
                        <span :class="'qbadge qbadge-'+q.status" x-text="(q.pill_comercial||{}).label || q.status"></span>
                    </div>
                    <div class="q-item-fecha" x-text="q.created_at"></div>
                </div>
                <span class="q-item-check" :class="(bulkActive || bulkIds.includes(q.id)) ? 'q-check-visible' : ''" @click.stop="toggleBulk(q.id)">
                    <svg x-show="bulkIds.includes(q.id)" width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
        </template>
        <div x-show="filtered.length===0" style="padding:40px 0;text-align:center;font-size:13px;color:var(--texto-debil, #5b6270)">Sin cotizaciones</div>
    </div>

    {{-- Colapsar: leer una cotizacion larga no necesita ver las otras 40.
         La preferencia se recuerda; volver a abrirla queda siempre a mano
         en el borde izquierdo del documento. --}}
    <button type="button" class="q-colapsar" @click="setLista(false)">
        <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5"/></svg>
        Colapsar lista
    </button>
</div>

{{-- Con la lista plegada queda este rail para devolverla. --}}
<button type="button" class="q-rail" :class="listaAbierta ? 'q-rail-oculto' : ''" @click="setLista(true)"
        aria-label="Mostrar la lista de cotizaciones" title="Mostrar la lista">
    <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5"/></svg>
</button>

{{-- ══ ZONA CENTRAL ══ --}}
<div class="q-main" :class="panel==='list' ? 'hidden md:flex' : 'flex'">

    {{-- Back mobile --}}
    <button @click="panel='list'" class="q-volver" style="display:flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;color:#6b7280;border:none;background:#fff;border-bottom:1px solid #e5e7eb;width:100%;cursor:pointer">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Volver a la lista
    </button>

    {{-- Empty state --}}
    <div x-show="!selected && !creating" class="q-empty">
        <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p style="font-size:13px;color:var(--texto-debil, #5b6270)">Selecciona una cotización o crea una nueva</p>
        <button @click="openNew()" style="margin-top:4px;background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer">+ Nueva cotización</button>
    </div>

    <template x-if="selected || creating">
    {{-- Envoltorio del documento. Lleva clase porque en movil tambien tiene
         que dejar subir a sus hijos a la columna principal (ver q-doc). --}}
    <div class="q-doc" style="display:flex;flex-direction:column;height:100%;overflow:hidden">

        {{-- Header --}}
        <div class="q-main-head">
            <div class="q-head-id">
                {{-- El numero del documento, no su id interno: cada negocio
                     numera desde COT-00001. Las filas anteriores a la
                     numeracion caen al id para no quedarse sin nombre. --}}
                <div class="q-main-title" x-text="creating ? 'Nueva cotización' : (selected.numero || ('COT-' + String(selected.id).padStart(5,'0')))"></div>
            </div>

            {{-- Acciones principales: una primaria, una secundaria fuerte y el
                 resto plegado. Antes eran seis botones apilados en la columna
                 derecha, todos con el mismo peso: enviar al cliente —que es
                 para lo que existe una cotizacion— pesaba igual que exportar. --}}
            <div class="q-head-acciones">
                <button class="q-cta" x-show="!creating && puede.editar"
                        @click="sendToClient()" :disabled="sending || esConvertida"
                        :title="esConvertida ? 'Ya generó el pedido PED-' + pedidoDeEstaCotizacion + ': reenviarla rompería su trazabilidad. Duplícala para negociar de nuevo.' : ''">
                        <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                    <span x-text="sending ? 'Generando...' : (selected?.token ? 'Reenviar cotización' : 'Enviar cotización')"></span>
                </button>

                <template x-if="creating">
                    <button class="q-cta" @click="save()" :disabled="saving">
                        <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
                        <span x-text="saving ? 'Guardando...' : 'Crear cotización'"></span>
                    </button>
                </template>

                @if($project->whatsapp)
                <button class="q-cta q-cta-wa" x-show="!creating && (selected?.token || portalUrl)" @click="sendWhatsApp()">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                    WhatsApp
                </button>
                @endif

                {{-- 'Mas' recoge TODO lo que antes era una barra tecnica de
                     nueve botones al pie del documento. Aqui van las acciones
                     que no viven en el panel derecho: el enlace del cliente y
                     los formatos alternativos de impresion. --}}
                <template x-if="!creating">
                    <div x-data="{mas:false}" style="position:relative">
                        <button class="q-cta q-cta-ghost" @click="mas=!mas; if(mas) $nextTick(() => { const m=$el.parentElement.querySelector('.q-menu'); const r=$el.getBoundingClientRect(); m.classList.toggle('q-menu-arriba', window.innerHeight - r.bottom < m.offsetHeight + 16); })" @click.outside="mas=false" :aria-expanded="mas ? 'true':'false'">
                            Más
                            <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        {{-- Agrupado por a QUIEN sirve cada accion. Duplicar,
                             Exportar PDF y Eliminar NO estan aqui: viven en el
                             panel derecho y repetirlas obligaria a decidir dos
                             veces donde pulsar. --}}
                        <div x-show="mas" x-cloak class="q-menu">
                            <template x-if="selected?.token || portalUrl">
                                <div>
                                    <p class="q-menu-titulo">Cliente</p>
                                    <button type="button" @click="copyLink(); mas=false">Copiar enlace</button>
                                    <a :href="portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/'+(selected?.token||''))" target="_blank" rel="noopener" @click="mas=false">Ver portal del cliente</a>
                                </div>
                            </template>

                            <p class="q-menu-titulo">Documento</p>
                            <button type="button" @click="save(); mas=false" :disabled="saving || !puede.editar">
                                <span x-text="saving ? 'Guardando...' : 'Guardar cambios'"></span>
                            </button>

                            <p class="q-menu-titulo">Descargar</p>
                            <button type="button" onclick="exportQuoteImg()" @click="mas=false">Imagen para WhatsApp (PNG)</button>
                            <button type="button" onclick="exportBoletaPDF()" @click="mas=false">Resumen en una hoja (PDF)</button>

                            <p class="q-menu-titulo">Imprimir</p>
                            <button type="button" onclick="printTicket()" @click="mas=false">Ticket 58 mm</button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Fila 2: de quien es el documento y en que estado esta. --}}
            <div class="q-head-fila2">
                <div class="q-head-meta">
                    <template x-if="!creating && selected.client_name">
                        <span class="q-head-chip q-chip-cliente">
                            <svg class="q-ico-xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg>
                            <span x-text="selected.client_name"></span>
                        </span>
                    </template>
                    <template x-if="!creating">
                        <span class="q-head-chip">
                            <svg class="q-ico-xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                            <span x-text="selected.created_at"></span>
                        </span>
                    </template>
                    <template x-if="!creating && selected.autor">
                        <span class="q-head-chip" title="Quién creó esta cotización">
                            <svg class="q-ico-xs" viewBox="0 0 24 24" aria-hidden="true"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                            <span x-text="'Creada por ' + selected.autor"></span>
                        </span>
                    </template>
                    <template x-if="creating">
                        <span class="q-head-chip">Completa los datos y agrega productos</span>
                    </template>
                </div>
        <div class="q-head-sub" x-show="!creating" x-cloak>
            {{-- Convertida NO es un estado mas del desplegable: no se puede
                 volver de ella. Se presenta con la misma forma (mismo alto,
                 misma etiqueta 'Estado') para que la cabecera no cambie de
                 composicion segun la cotizacion, pero deshabilitada. --}}
            <label class="q-estado-wrap">
                <span class="q-estado-label">Estado:</span>
                <span class="q-estado-select q-estado-converted" x-show="esConvertida" title="Ya generó un pedido: su estado no puede cambiarse">Convertida</span>
                <span x-show="!esConvertida" style="display:contents">
                    {{-- :value y NO x-model. El desplegable no tiene opcion
                         'Convertida' —no se puede volver a ella—, asi que con
                         x-model el DOM escribia de vuelta en el modelo y una
                         cotizacion ACEPTADA se leia como convertida al venir
                         de ver una convertida. El flujo va en un solo
                         sentido: el modelo pinta el select, y el cambio del
                         usuario pasa por setStatus(), que valida. --}}
                    <select class="q-estado-select" :class="'q-estado-'+form.status"
                            :disabled="!puede.editar"
                            :value="form.status"
                            @change="setStatus($event.target.value)"
                            aria-label="Estado de la cotización">
                        <option value="draft">Borrador</option>
                        <option value="sent">Enviada</option>
                        <option value="accepted">Aceptada</option>
                        <option value="rejected">Rechazada</option>
                    </select>
                </span>
            </label>

            {{-- El pedido generado, como referencia compacta. Antes ocupaba
                 una franja entera bajo la cabecera para decir seis palabras. --}}
            <span class="q-head-ped" x-show="pedidoDeEstaCotizacion">
                    <span class="q-estado-label">Pedido generado:</span>
                    <a :href="urlPedido(pedidoDeEstaCotizacion)" class="q-ped-link" x-text="'PED-' + pedidoDeEstaCotizacion"></a>
                    <span aria-hidden="true">→</span>
                </span>

            <span class="q-guardado" x-show="guardadoEn" x-cloak>
                <svg class="q-ico-xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span x-text="'Guardado ' + guardadoHace"></span>
            </span>
            </div>
            </div>
        </div>

        {{-- Avisos de conversion y modal de confirmacion. La relacion con el
             PED y el boton de convertir salieron de aqui: el primero vive en
             la cabecera y el segundo en el panel derecho. --}}
        @include('quotes._acciones')

        <template x-if="form.status==='rejected' && selected?.reject_reason">
            <div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:8px 20px;font-size:12px;color:#991b1b">
                <b>Motivo del cliente:</b> <span x-text="selected.reject_reason"></span>
            </div>
        </template>

        <div class="q-main-body">

            {{-- DATOS DEL CLIENTE --}}
            <div class="q-section">
                <div class="q-section-head">
                    <span class="q-section-title"><svg class="q-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Cliente</span>
                    {{-- Estado del guardado del bloque, en vez de un boton
                         de edicion: se escribe encima y se guarda al salir
                         del campo. --}}
                    <span class="q-inline-nota" x-show="!editable" x-cloak
                          x-text="esConvertida ? 'Documento cerrado' : 'Solo lectura'"></span>
                </div>
                <div class="q-section-body">
                    <div class="q-lectura q-inline" :class="editable ? '' : 'q-inline-off'">
                        <div class="q-lectura-item" style="position:relative">
                            <label for="cli-nombre">Nombre</label>
                            <input id="cli-nombre" type="text" x-model="form.client_name"
                                   :readonly="!editable" placeholder="Nombre completo" autocomplete="off"
                                   @input="buscarCliente($event.target.value)"
                                   @focus="buscarCliente(form.client_name)"
                                   @blur="setTimeout(() => clientesSugeridos = [], 150)"
                                   @change="guardarCampo()">
                            {{-- Sugerencias de la cartera: elegir uno enlaza el
                                 documento con su ficha, que es lo que hace que
                                 el historial del cliente exista. --}}
                            <div class="q-sugerencias" x-show="clientesSugeridos.length" x-cloak>
                                <template x-for="c in clientesSugeridos" :key="c.id">
                                    <button type="button" class="q-sugerencia" @mousedown.prevent="usarCliente(c)">
                                        <span x-text="c.name"></span>
                                        <span class="q-sug-precio" x-text="c.phone || c.empresa || ''"></span>
                                    </button>
                                </template>
                            </div>
                            <span class="q-cliente-ficha" x-show="form.client_id" x-cloak
                                  title="Este documento está enlazado con la ficha del cliente">
                                <svg class="q-ico-xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                En la cartera
                            </span>
                        </div>
                        <div class="q-lectura-item">
                            <label for="cli-cel">Celular</label>
                            <input id="cli-cel" type="tel" x-model="form.client_phone" @change="guardarCampo()"
                                   :readonly="!editable" placeholder="—">
                        </div>
                        <div class="q-lectura-item">
                            <label for="cli-mail">Email</label>
                            <input id="cli-mail" type="email" x-model="form.client_email" @change="guardarCampo()"
                                   :readonly="!editable" placeholder="—">
                        </div>
                        <div class="q-lectura-item">
                            <label for="cli-doc">DNI / RUC</label>
                            <input id="cli-doc" type="text" x-model="form.client_doc_number" @change="guardarCampo()"
                                   :readonly="!editable" placeholder="—">
                        </div>
                        <div class="q-lectura-item" style="grid-column:1/-1">
                            <label for="cli-dir">Dirección</label>
                            <input id="cli-dir" type="text" x-model="form.client_address" @change="guardarCampo()"
                                   :readonly="!editable" placeholder="—">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLA DE PRODUCTOS --}}
            <div class="q-section">
                <div class="q-section-head">
                    <span class="q-section-title"><svg class="q-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Productos</span>
                    {{-- El buscador del catalogo era una franja punteada a
                         todo el ancho, siempre visible aunque no se estuviera
                         agregando nada. Ahora se abre desde esta accion y se
                         cierra sola al elegir o con Escape. --}}
                    <div class="q-agregar-wrap" x-show="puede.editar">
                        <button type="button" class="q-agregar" @click="abrirCatalogo()">
                            <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Agregar producto
                        </button>
                        @if($products->count())
                        <div class="q-catalog-pop" x-show="catalogOpen" x-cloak
                             @click.outside="catalogOpen=false;catalogSearch=''"
                             @keydown.escape="catalogOpen=false;catalogSearch=''">
                            <input type="text" x-model="catalogSearch" x-ref="catalogo"
                                   class="q-catalog-input" placeholder="Buscar en el catálogo...">
                            <div class="q-catalog-drop">
                                <template x-for="p in filteredCatalog" :key="p.id">
                                    <div class="q-catalog-item" @click="addFromCatalog(p)">
                                        <div>
                                            <div class="q-catalog-name" x-text="p.name"></div>
                                            <div class="q-catalog-sku" x-show="p.sku" x-text="'SKU: '+p.sku"></div>
                                        </div>
                                        <span class="q-catalog-price" x-text="fmt(p.price)"></span>
                                    </div>
                                </template>
                                <p class="q-catalog-vacio" x-show="!filteredCatalog.length">
                                    Sin resultados. Puedes escribir la línea a mano.
                                </p>
                            </div>
                            <button type="button" class="q-catalog-manual" @click="addItem(); catalogOpen=false; catalogSearch=''">
                                Escribir una línea libre
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="q-table-wrap q-tabla-lectura q-grid" :class="editable ? '' : 'q-inline-off'">
                    <table class="q-table">
                        <thead>
                            <tr>
                                <th style="width:40%">Producto / Descripción</th>
                                <th class="c" style="width:8%">Cant.</th>
                                <th class="r" style="width:16%">Precio unitario</th>
                                <th class="r" style="width:12%">Desc.</th>
                                <th class="r" style="width:16%">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, i) in form.items" :key="i">
                                <tr :class="!item.description ? 'q-fila-nueva' : ''">
                                    <td data-label="Producto">
                                        <div class="q-prod">
                                            {{-- Miniatura del catalogo cuando la linea corresponde a un
                                                 producto; si es un servicio escrito a mano, marcador. --}}
                                            <template x-if="catalogoDe(item.description)?.img">
                                                <img class="q-prod-img" :src="catalogoDe(item.description).img" :alt="item.description" loading="lazy">
                                            </template>
                                            <template x-if="!catalogoDe(item.description)?.img">
                                                <span class="q-prod-img q-prod-vacia" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                </span>
                                            </template>
                                            <div class="q-prod-txt">
                                                <input class="q-cel q-cel-desc" type="text" x-model="form.items[i].description"
                                                       :readonly="!editable" :placeholder="i === form.items.length - 1 ? 'Escribe o busca un producto…' : 'Descripción'"
                                                       @input="alEscribirLinea(i, $event.target.value)"
                                                       @focus="lineaFoco = i" @blur="cerrarSugerencias()"
                                                       @change="guardarCampo()"
                                                       @keydown.enter.prevent="$refs['qty'+i]?.focus()"
                                                       :x-ref="'desc'+i" autocomplete="off">
                                                <span class="q-prod-sub" x-show="catalogoDe(item.description)?.sub" x-text="catalogoDe(item.description)?.sub"></span>
                                                {{-- Sugerencias del catalogo bajo la celda: elegir uno
                                                     trae su nombre y su precio. --}}
                                                <div class="q-sugerencias" x-show="lineaFoco === i && sugerencias.length" x-cloak>
                                                    <template x-for="pr in sugerencias" :key="pr.id">
                                                        <button type="button" class="q-sugerencia" @mousedown.prevent="usarProducto(i, pr)">
                                                            <span x-text="pr.name"></span>
                                                            <span class="q-sug-precio" x-text="fmt(pr.price)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="c" data-label="Cant.">
                                        <input class="q-cel q-cel-num" type="number" min="1" max="10000" step="1" inputmode="numeric"
                                               x-model="form.items[i].quantity" :readonly="!editable" placeholder="1"
                                               @change="guardarCampo()" :x-ref="'qty'+i">
                                    </td>
                                    <td class="r" data-label="Precio">
                                        <input class="q-cel q-cel-num" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal"
                                               x-model="form.items[i].price" :readonly="!editable" placeholder="0.00"
                                               @change="guardarCampo()" :x-ref="'price'+i">
                                    </td>
                                    <td class="r" data-label="Desc.">
                                        {{-- Porcentaje por linea (quote_items.discount): viaja al pedido. --}}
                                        <input class="q-cel q-cel-num" type="number" min="0" max="100" step="0.01" inputmode="decimal"
                                               x-model="form.items[i].discount" :readonly="!editable" placeholder="0"
                                               aria-label="Descuento porcentual de la línea"
                                               @change="guardarCampo()"
                                               {{-- parseFloat solo para el aviso visual: no calcula ningun importe --}}
                                               :style="(parseFloat(form.items[i].discount)||0) > 100 || (parseFloat(form.items[i].discount)||0) < 0 ? 'color:#dc2626;font-weight:700' : ''">
                                    </td>
                                    <td class="r" data-label="Subtotal">
                                        <span class="q-td-sub" x-show="item.description" x-text="lmMoneda(lineTotal(item))"></span>
                                    </td>
                                    <td class="q-td-acciones" data-label="">
                                        <button type="button" class="q-fila-mas" x-show="editable && item.description"
                                                @click="quitarLinea(i)"
                                                aria-label="Quitar esta línea" title="Quitar esta línea">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="q-tabla-pie">
                        <span x-text="'Total de productos: ' + form.items.filter(x => x.description).length"></span>
                        <span class="q-pie-sub">Subtotal <strong x-text="fmt(grandTotalCents)"></strong></span>
                    </div>
                </div>
            </div>

            {{-- CONDICIONES --}}
            <div class="q-section">
                <div class="q-section-head">
                    <span class="q-section-title"><svg class="q-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>Condiciones</span>
                    <span class="q-inline-nota" x-show="!editable" x-cloak
                          x-text="esConvertida ? 'Documento cerrado' : 'Solo lectura'"></span>
                </div>
                <div class="q-section-body">
                    <div class="q-lectura q-inline" :class="editable ? '' : 'q-inline-off'">
                        <div class="q-lectura-item">
                            <label for="cond-hasta">Válida hasta</label>
                            <input id="cond-hasta" type="date" x-model="form.valid_until" @change="guardarCampo()"
                                   :readonly="!editable" :disabled="!editable">
                        </div>
                        <div class="q-lectura-item">
                            <label for="cond-metodo">Método de pago</label>
                            {{-- Con lista configurada se elige; sin ella se
                                 escribe, que es lo que hoy hace el negocio. --}}
                            <select id="cond-metodo" x-show="paymentMethods.length" x-model="form.payment_method"
                                    @change="guardarCampo()" :disabled="!editable">
                                <option value="">—</option>
                                <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                            <input x-show="!paymentMethods.length" type="text" x-model="form.payment_method"
                                   @change="guardarCampo()" :readonly="!editable" placeholder="—">
                        </div>
                        <div class="q-lectura-item">
                            <label for="cond-condicion">Condición de pago</label>
                            <select id="cond-condicion" x-show="paymentConditions.length" x-model="form.payment_condition"
                                    @change="guardarCampo()" :disabled="!editable">
                                <option value="">—</option>
                                <template x-for="c in paymentConditions" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                            <input x-show="!paymentConditions.length" type="text" x-model="form.payment_condition"
                                   @change="guardarCampo()" :readonly="!editable" placeholder="—">
                        </div>
                        <div class="q-lectura-item" style="grid-column:1/-1">
                            <label for="cond-notas">Notas internas</label>
                            <textarea id="cond-notas" x-ref="notas" rows="2" x-model="form.notes"
                                      @change="guardarCampo()" :readonly="!editable"
                                      placeholder="Observaciones, términos especiales..."
                                      @input="autoAlto($el)" x-effect="form.notes; $nextTick(() => autoAlto($refs.notas))"></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end q-main-body --}}
    </div>
    </template>
</div>

{{-- ══ PANEL DERECHO STICKY ══ --}}
{{-- En móvil solo se muestra junto al detalle (panel==='detail'); en desktop siempre que haya selección --}}
<div class="q-panel" x-show="(selected || creating) && (panel==='detail' || window.innerWidth>=1024)">

    {{-- Resumen financiero. El Total manda: es la cifra por la que se
         decide. No hay linea de IGV porque `quotes` no guarda impuesto —
         inventarle un 18% al documento seria decirle al cliente un precio
         que el sistema no ha calculado. El descuento SI existe, por linea,
         y se suma aqui. --}}
    <div class="q-resumen">
        <div class="q-resumen-head">
            <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
            Resumen
        </div>
        <div class="q-resumen-fila"><span>Subtotal</span><span x-text="fmt(brutoCents)"></span></div>
        <div class="q-resumen-fila" x-show="descuentoCents > 0n" x-cloak>
            <span>Descuento</span><span class="q-desc" x-text="'- ' + fmt(descuentoCents)"></span>
        </div>
        <div class="q-resumen-total">
            <span>Total</span>
            <strong x-text="fmt(grandTotalCents)"></strong>
        </div>
        <p class="q-resumen-nota" x-text="(n => n + (n === 1 ? ' producto' : ' productos'))(form.items.filter(i=>i.description).length)"></p>

        <button type="button" class="q-detalle-btn" @click="detalleTotales = !detalleTotales"
                :aria-expanded="detalleTotales ? 'true' : 'false'">
            <span x-text="detalleTotales ? 'Ocultar detalle' : 'Ver detalle de totales'"></span>
            <svg class="q-ico-sm" :style="detalleTotales ? 'transform:rotate(180deg)' : ''" viewBox="0 0 24 24" aria-hidden="true"><path d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        {{-- El detalle es el desglose por linea: es lo unico que hay que
             abrir, porque el documento no lleva impuesto. --}}
        <div class="q-detalle" x-show="detalleTotales" x-cloak>
            <template x-for="(item, i) in form.items.filter(x => x.description)" :key="'d'+i">
                <div class="q-detalle-fila">
                    <span x-text="item.description"></span>
                    <span x-text="lmMoneda(lineTotal(item))"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Siguiente paso: convertir. Una cotizacion aceptada no se cobra, se
         convierte en pedido; ahi nace la venta. --}}
    <div class="q-resumen" x-show="!creating && form.status==='accepted' && puede.convertir" x-cloak>
            <div class="q-resumen-head">Siguiente paso</div>
            <p style="font-size:12px;color:#5b6270;margin:0 0 10px">
                El cliente aceptó. Conviértela en pedido para registrar la venta y poder cobrarla.
            </p>
            <button type="button" class="q-cta" style="width:100%;justify-content:center"
                    @click="convertir()" :disabled="convirtiendo">
                <span x-text="convirtiendo ? 'Convirtiendo...' : 'Convertir en pedido'"></span>
            </button>
    </div>

    {{-- Acciones secundarias: presentes pero sin competir con "Enviar". --}}
    <template x-if="!creating">
        <div class="q-sec">
            <button type="button" class="q-sec-item" @click="duplicateQuote()" :disabled="duplicating">
                <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"/></svg>
                <span x-text="duplicating ? 'Duplicando...' : 'Duplicar cotización'"></span>
            </button>
            <a class="q-sec-item" :href="pdfUrl" target="_blank" rel="noopener" x-show="pdfUrl">
                <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75h6m-6 3h3M9.75 3.104A2.25 2.25 0 0 0 8.25 3H5.625c-.621 0-1.125.504-1.125 1.125v15.75c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V9.75a2.25 2.25 0 0 0-.659-1.591l-4.5-4.5A2.25 2.25 0 0 0 12.75 3H9.75Z"/></svg>
                Exportar PDF
            </a>
            <button type="button" class="q-sec-item q-sec-danger" @click="del()" x-show="puede.eliminar">
                <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                Eliminar cotización
            </button>
        </div>
    </template>

    {{-- Actividad real: sale de `order_events`, que ya registraba creacion,
         envio, aceptacion del cliente y conversion. Hasta ahora se escribia y
         no habia forma de leerla: solo existia endpoint para pedidos. --}}
    <template x-if="!creating">
        <div class="q-actividad">
            <div class="q-resumen-head">
                <svg class="q-ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Actividad
            </div>
            {{-- x-show y no x-if: anidados dentro del x-if del panel, los
                 template no se re-evaluaban y 'Cargando…' se quedaba fijo en
                 pantalla con la carga ya terminada (visto en la validacion).
                 x-show alterna visibilidad sin recrear el nodo. --}}
            <p class="q-actividad-vacio" x-show="actividadCargando">Cargando…</p>
            <p class="q-actividad-vacio" x-show="!actividadCargando && actividad.length === 0">Sin movimientos todavía.</p>
            {{-- Cuatro eventos: la actividad acompaña, no compite con el
                 Resumen. El resto se despliega a peticion. --}}
            {{-- Timeline: los hechos de un documento tienen orden, y una
                 lista de filas sueltas no lo dice. La linea une los puntos y
                 el ultimo no la continua. --}}
            <ol class="q-timeline">
                <template x-for="(ev, k) in actividadVisible" :key="k">
                    <li class="q-act-item" :class="'q-act-' + (ev.tono || 'neutro')">
                        <div class="q-act-cuerpo">
                            <p class="q-act-titulo" x-text="ev.titulo"></p>
                            <p class="q-act-detalle">
                                <span x-text="ev.detalle"></span>
                                <span class="q-act-quien" x-show="ev.quien" x-text="' · ' + ev.quien"></span>
                            </p>
                        </div>
                        <span class="q-act-hora" :title="ev.fecha" x-text="ev.hace"></span>
                    </li>
                </template>
            </ol>
            <button type="button" class="q-act-mas" x-show="actividad.length > 4" x-cloak
                    @click="actividadTodo = !actividadTodo"
                    x-text="actividadTodo ? 'Ver menos' : 'Ver toda la actividad →'"></button>
        </div>
    </template>


</div>

{{-- Confirmacion de acciones. Un solo dialogo para todas: dice que va a
     pasar y sobre que documento, atrapa el foco y se cierra con Escape. --}}
<template x-if="confirmar.abierto">
    <div class="q-modal-fondo" @keydown.escape.window="if(!confirmar.ocupado) confirmar.abierto=false"
         @click.self="if(!confirmar.ocupado) confirmar.abierto=false">
        <div class="q-modal" role="dialog" aria-modal="true"
             aria-labelledby="tituloConfirmar" aria-describedby="descConfirmar"
             x-trap.noscroll="confirmar.abierto">
            <h2 class="q-modal-titulo" id="tituloConfirmar" x-text="confirmar.titulo"></h2>
            <p class="q-modal-desc" id="descConfirmar" x-text="confirmar.descripcion"></p>
            <div class="q-modal-botones">
                <button type="button" class="q-btn-secundario" @click="confirmar.abierto=false"
                        :disabled="confirmar.ocupado">Cancelar</button>
                <button type="button" class="q-btn-primario"
                        :class="confirmar.tono === 'peligro' ? 'q-btn-peligro' : ''"
                        @click="ejecutarConfirmacion()" :disabled="confirmar.ocupado"
                        :aria-busy="confirmar.ocupado ? 'true' : 'false'" x-ref="confirmarAccion">
                    <span x-show="!confirmar.ocupado" x-text="confirmar.boton"></span>
                    <span x-show="confirmar.ocupado" x-cloak>Un momento…</span>
                </button>
            </div>
        </div>
    </div>
</template>

</div>

{{-- Scripts para exportar --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function lmExportCents(price, qty, disc) {
    const p = String(price ?? '0').trim(), d = String(disc ?? '0').trim();
    const rx = /^\d+(\.\d{1,2})?$/;
    if (!rx.test(p) || !rx.test(d)) return 0n;
    const toC = (s) => { const [e, f = ''] = s.split('.'); return BigInt(e) * 100n + BigInt(f.padEnd(2, '0')); };
    const q = BigInt(Math.max(1, parseInt(qty) || 1));
    return (toC(p) * q * (10000n - toC(d)) + 5000n) / 10000n;
}
function lmExportFmt(c) { return (c / 100n) + '.' + String(c % 100n).padStart(2, '0'); }
/* Centavos BigInt desde un decimal canonico "X.YY" (o "X"). Sin float. */
function lmExportCentsDe(txt) {
    const m = /^(-?)(\d+)(?:\.(\d{1,2}))?$/.exec(String(txt ?? '').trim());
    if (!m) return 0n;
    const dec = (m[3] || '').padEnd(2, '0');
    const v = BigInt(m[2]) * 100n + BigInt(dec);
    return m[1] === '-' ? -v : v;
}
/* Separadores de miles sobre el string exacto (espejo de LineMath::present). */
function lmExportPresent(exacto) {
    const m = /^(-?)(\d+)\.(\d{2})$/.exec(String(exacto));
    if (!m) return String(exacto);
    return m[1] + m[2].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + m[3];
}
/* Importe listo para el documento: moneda + miles, TODO desde BigInt. */
function lmExportMoneda(cur, cents) { return cur + ' ' + lmExportPresent(lmExportFmt(cents)); }
/* Escape de texto para los documentos generados.
   Los tres exportadores construyen HTML y lo entregan a document.write(): sin
   escapar, una descripcion o un nombre de cliente con "<img onerror=...>" se
   ejecutaria en una ventana del mismo origen. Se escapa TODO dato dinamico,
   tambien el que hoy parece inofensivo, porque lo escribe el usuario. */
function esc(v) {
    return String(v ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function buildQuoteHtml() {
    const qWrap = document.querySelector('.q-wrap');
    const al = qWrap ? (window.Alpine?.$data(qWrap) ?? qWrap.__x?.$data) : null;
    const q = al?.selected;
    const form = al?.form;
    if (!q) return null;
    const cur = '{{ $project->setting('currency_symbol','S/') }}';
    const logo = '{{ $project->logo_url ?? '' }}';
    const biz = '{{ $project->business_name ?? $project->name }}';
    const items = (form?.items || []).filter(i => i.description);
    const subtotalCents = items.reduce((s,i) => s + lmExportCents(i.price, i.quantity, i.discount), 0n);
    // Sin IGV inventado: el documento comercial usa el total persistido de la
    // cotizacion. El calculo fiscal real llega en su fase; mientras tanto un
    // exportador no puede afirmar un impuesto que el editor no calcula.
    const igv = 0;
    // El total del documento es la SUMA EXACTA de sus lineas, la misma que
    // persiste el servidor (LineMath::sum). Antes se leia el total del registro
    // seleccionado desde una funcion suelta: dependia del scope global y ademas
    // volvia a pasar por float.
    const totalCents = subtotalCents;
    const fmt = c => lmExportMoneda(cur, typeof c === 'bigint' ? c : lmExportCentsDe(c));
    const rows = items.map(i => {
        const sub = lmExportCents(i.price, i.quantity, i.discount);
        return `<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;font-size:13px">${esc(i.description)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:center;font-size:13px">${esc(i.quantity)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px">${fmt(i.price)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px;font-weight:600">${fmt(sub)}</td>
        </tr>`;
    }).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>body{font-family:Arial,sans-serif;margin:0;padding:32px;color:#111827;background:#fff}
    *{box-sizing:border-box}</style></head><body>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #6366f1">
        <div>${logo ? `<img src="${esc(logo)}" style="height:48px;object-fit:contain">` : ''}<h2 style="margin:8px 0 0;font-size:18px;color:#111827">${esc(biz)}</h2></div>
        <div style="text-align:right">
            <div style="font-size:22px;font-weight:800;color:#6366f1">COTIZACIÓN</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">#${esc(q.id)} · ${esc(q.created_at || new Date().toLocaleDateString('es'))}</div>
            ${q.valid_until ? `<div style="font-size:11px;color:var(--texto-debil, #5b6270)">Válida hasta: ${esc(q.valid_until)}</div>` : ''}
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
        <div style="background:#f9fafb;border-radius:8px;padding:14px">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px">Cliente</div>
            ${q.client_name ? `<div style="font-size:14px;font-weight:600">${esc(q.client_name)}</div>` : ''}
            ${q.client_phone ? `<div style="font-size:12px;color:#6b7280">Tel: ${esc(q.client_phone)}</div>` : ''}
            ${q.client_doc_number ? `<div style="font-size:12px;color:#6b7280">${esc(q.client_doc_type||'Doc')}: ${esc(q.client_doc_number)}</div>` : ''}
            ${q.client_address ? `<div style="font-size:12px;color:#6b7280">${esc(q.client_address)}</div>` : ''}
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:14px">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px">Condiciones</div>
            ${form?.payment_method ? `<div style="font-size:12px">Pago: ${esc(form.payment_method)}</div>` : ''}
            ${form?.payment_condition ? `<div style="font-size:12px">Condición: ${esc(form.payment_condition)}</div>` : ''}
            ${form?.notes ? `<div style="font-size:12px;color:#6b7280;margin-top:4px">${esc(form.notes)}</div>` : ''}
        </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <thead><tr style="background:#6366f1">
            <th style="padding:10px;text-align:left;color:#fff;font-size:11px;font-weight:600;border-radius:6px 0 0 0">Descripción</th>
            <th style="padding:10px;text-align:center;color:#fff;font-size:11px;font-weight:600">Cant.</th>
            <th style="padding:10px;text-align:right;color:#fff;font-size:11px;font-weight:600">Precio</th>
            <th style="padding:10px;text-align:right;color:#fff;font-size:11px;font-weight:600;border-radius:0 6px 0 0">Subtotal</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table>
    <div style="display:flex;justify-content:flex-end">
        <div style="min-width:220px">
            <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px;color:#6b7280"><span>Subtotal</span><span>${fmt(subtotalCents)}</span></div>
            
            <div style="display:flex;justify-content:space-between;padding:10px 0 4px;font-size:18px;font-weight:800;border-top:2px solid #6366f1;margin-top:6px;color:#6366f1"><span>Total</span><span>${fmt(totalCents)}</span></div>
        </div>
    </div>
    </body></html>`;
}

// ── Helpers genéricos: renderizan un HTML off-screen y lo exportan ──
async function renderOffscreen(html, widthPx) {
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:' + widthPx + 'px;height:1px;border:none';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(html);
    iframe.contentDocument.close();
    await new Promise(r => setTimeout(r, 600));
    const canvas = await html2canvas(iframe.contentDocument.body, {scale:2, useCORS:true, backgroundColor:'#fff', width:widthPx});
    document.body.removeChild(iframe);
    return canvas;
}

function currentQuoteId() {
    const raiz = document.querySelector('.q-wrap');
    const al = raiz ? (window.Alpine?.$data(raiz) ?? raiz.__x?.$data) : null;
    return al?.selected?.id || 'export';
}

/* Nombre del archivo descargado: numero del documento y cliente.
   Se limpia lo que un sistema de archivos no admite (/ \ : * ? " < > |) y
   se recortan los acentos, que en Windows viajan mal entre equipos. */
function nombreArchivoCotizacion(prefijo) {
    const raiz = document.querySelector('.q-wrap');
    const al = raiz ? (window.Alpine?.$data(raiz) ?? raiz.__x?.$data) : null;
    const q = al?.selected;
    if (!q) return prefijo + '-export';

    const limpia = (t) => String(t || '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[\/:*?"<>|]+/g, ' ')
        .replace(/\s+/g, ' ').trim().slice(0, 40)
        // Windows no admite un nombre que acabe en punto, y 'S.A.C.' lo deja.
        .replace(/[.\s]+$/, '');

    const numero  = limpia(q.numero || ('COT-' + String(q.id).padStart(5, '0')));
    const cliente = limpia(q.client_name);

    return [numero, cliente, prefijo === 'cotizacion' ? '' : prefijo]
        .filter(Boolean).join(' - ');
}

async function htmlToPdf(html, widthPx, filenamePrefix) {
    if (!html) { bxAviso('Abre una cotización antes de exportarla', 'warning'); return; }
    const { jsPDF } = window.jspdf;
    const canvas = await renderOffscreen(html, widthPx);
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
    const pW = pdf.internal.pageSize.getWidth();
    const pH = pdf.internal.pageSize.getHeight();
    const ratio = canvas.width / canvas.height;
    const imgH = pW / ratio;
    if (imgH <= pH) {
        pdf.addImage(imgData, 'PNG', 0, 0, pW, imgH);
    } else {
        let yPos = 0, remaining = canvas.height;
        while (remaining > 0) {
            const sliceH = Math.min(remaining, Math.floor(canvas.width * pH / pW));
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width = canvas.width; sliceCanvas.height = sliceH;
            sliceCanvas.getContext('2d').drawImage(canvas, 0, yPos, canvas.width, sliceH, 0, 0, canvas.width, sliceH);
            if (yPos > 0) pdf.addPage();
            pdf.addImage(sliceCanvas.toDataURL('image/png'), 'PNG', 0, 0, pW, sliceH * pW / canvas.width);
            yPos += sliceH; remaining -= sliceH;
        }
    }
    pdf.save(nombreArchivoCotizacion(filenamePrefix) + '.pdf');
}

async function htmlToImg(html, widthPx, filenamePrefix) {
    if (!html) { bxAviso('Abre una cotización antes de exportarla', 'warning'); return; }
    const canvas = await renderOffscreen(html, widthPx);
    const link = document.createElement('a');
    link.download = nombreArchivoCotizacion(filenamePrefix) + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

async function exportQuotePDF() { await htmlToPdf(buildQuoteHtml(), 800, 'cotizacion'); }
async function exportQuoteImg() { await htmlToImg(buildQuoteHtml(), 800, 'cotizacion'); }
async function exportBoletaPDF() { await htmlToPdf(buildBoletaHtml(), 420, 'resumen'); }
async function exportBoletaImg() { await htmlToImg(buildBoletaHtml(), 420, 'resumen'); }

// ── Resumen en una hoja: la cotizacion condensada para imprimir o adjuntar.
// Se llamaba 'boleta' y se titulaba BOLETA DE VENTA, que en Peru es un
// comprobante SUNAT: prometia algo fiscal que no ocurre, y ademas se
// contradecia con el aviso de su propio pie. El comprobante de verdad se
// emite desde Facturacion. ──
function buildBoletaHtml() {
    const qWrap = document.querySelector('.q-wrap');
    const al = qWrap ? (window.Alpine?.$data(qWrap) ?? qWrap.__x?.$data) : null;
    const q = al?.selected;
    const form = al?.form;
    if (!q) return null;
    const cur = '{{ $project->setting('currency_symbol','S/') }}';
    const biz = '{{ $project->business_name ?? $project->name }}';
    const ruc = '{{ $project->setting('ruc','') }}';
    const items = (form?.items || []).filter(i => i.description);
    const subtotalCents = items.reduce((s,i) => s + lmExportCents(i.price, i.quantity, i.discount), 0n);
    // Sin IGV inventado: el documento comercial usa el total persistido de la
    // cotizacion. El calculo fiscal real llega en su fase; mientras tanto un
    // exportador no puede afirmar un impuesto que el editor no calcula.
    const igv = 0;
    // El total del documento es la SUMA EXACTA de sus lineas, la misma que
    // persiste el servidor (LineMath::sum). Antes se leia el total del registro
    // seleccionado desde una funcion suelta: dependia del scope global y ademas
    // volvia a pasar por float.
    const totalCents = subtotalCents;
    const fmt = c => lmExportMoneda(cur, typeof c === 'bigint' ? c : lmExportCentsDe(c));
    const rows = items.map(i => {
        const sub = lmExportCents(i.price, i.quantity, i.discount);
        return `<tr>
            <td style="padding:5px 0;border-bottom:1px solid #e5e7eb;font-size:11.5px">${esc(i.description)}<br><span style="color:var(--texto-debil, #5b6270);font-size:10px">${esc(i.quantity)} x ${fmt(i.price)}</span></td>
            <td style="padding:5px 0;border-bottom:1px solid #e5e7eb;text-align:right;font-size:11.5px;font-weight:600;vertical-align:top">${fmt(sub)}</td>
        </tr>`;
    }).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>body{font-family:Arial,sans-serif;margin:0;padding:20px;color:#111827;background:#fff}*{box-sizing:border-box}</style></head><body>
    <div style="text-align:center;margin-bottom:14px;padding-bottom:12px;border-bottom:2px dashed #d1d5db">
        <div style="font-size:15px;font-weight:800">${esc(biz)}</div>
        ${ruc ? `<div style="font-size:10.5px;color:#6b7280">RUC ${esc(ruc)}</div>` : ''}
        <div style="font-size:13px;font-weight:700;margin-top:8px;letter-spacing:.5px">COTIZACIÓN</div>
        <div style="font-size:10.5px;color:#6b7280">N° ${esc(q.numero || ('COT-' + String(q.id).padStart(5,'0')))} · ${esc(q.created_at || new Date().toLocaleDateString('es'))}</div>
    </div>
    <div style="font-size:11.5px;margin-bottom:10px">
        ${q.client_name ? `<div><b>Cliente:</b> ${esc(q.client_name)}</div>` : ''}
        ${q.client_doc_number ? `<div><b>${esc(q.client_doc_type||'Doc')}:</b> ${esc(q.client_doc_number)}</div>` : ''}
        ${q.client_address ? `<div style="color:#6b7280">${esc(q.client_address)}</div>` : ''}
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px">
        <thead><tr><th style="text-align:left;padding-bottom:5px;border-bottom:2px solid #111827;font-size:10px;text-transform:uppercase">Descripción</th><th style="text-align:right;padding-bottom:5px;border-bottom:2px solid #111827;font-size:10px;text-transform:uppercase">Importe</th></tr></thead>
        <tbody>${rows}</tbody>
    </table>
    <div style="margin-top:8px">
        <div style="display:flex;justify-content:space-between;font-size:11.5px;color:#6b7280;padding:2px 0"><span>Subtotal</span><span>${fmt(subtotalCents)}</span></div>
        
        <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:800;padding-top:6px;margin-top:4px;border-top:2px solid #111827"><span>TOTAL</span><span>${fmt(totalCents)}</span></div>
    </div>
    ${form?.payment_condition ? `<div style="font-size:10.5px;color:#6b7280;margin-top:10px">Condición de pago: ${esc(form.payment_condition)}</div>` : ''}
    <div style="text-align:center;font-size:9px;color:var(--texto-debil, #5b6270);margin-top:18px;padding-top:10px;border-top:1px dashed #d1d5db">
        Documento referencial — no es un comprobante de pago electrónico.<br>El comprobante oficial se emite al confirmar la venta.
    </div>
    </body></html>`;
}

// ── Ticket 58 mm: recibo angosto para impresora térmica. Usa el
// diálogo de impresión del navegador en vez de generar un PDF: así el
// usuario elige directo su impresora de tickets y evita los problemas de
// nitidez de convertir a imagen a un ancho tan chico. ──
function buildTicketHtml() {
    const qWrap = document.querySelector('.q-wrap');
    const al = qWrap ? (window.Alpine?.$data(qWrap) ?? qWrap.__x?.$data) : null;
    const q = al?.selected;
    const form = al?.form;
    if (!q) return null;
    const cur = '{{ $project->setting('currency_symbol','S/') }}';
    const biz = '{{ $project->business_name ?? $project->name }}';
    const items = (form?.items || []).filter(i => i.description);
    const subtotalCents = items.reduce((s,i) => s + lmExportCents(i.price, i.quantity, i.discount), 0n);
    // Sin IGV inventado: el documento comercial usa el total persistido de la
    // cotizacion. El calculo fiscal real llega en su fase; mientras tanto un
    // exportador no puede afirmar un impuesto que el editor no calcula.
    const igv = 0;
    // El total del documento es la SUMA EXACTA de sus lineas, la misma que
    // persiste el servidor (LineMath::sum). Antes se leia el total del registro
    // seleccionado desde una funcion suelta: dependia del scope global y ademas
    // volvia a pasar por float.
    const totalCents = subtotalCents;
    const fmt = c => lmExportMoneda(cur, typeof c === 'bigint' ? c : lmExportCentsDe(c));
    const rows = items.map(i => {
        const sub = lmExportCents(i.price, i.quantity, i.discount);
        return `<div style="margin-bottom:4px">
            <div>${esc(i.description)}</div>
            <div style="display:flex;justify-content:space-between;color:#444"><span>${esc(i.quantity)} x ${fmt(i.price)}</span><span>${fmt(sub)}</span></div>
        </div>`;
    }).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        @page { size: 58mm auto; margin: 2mm; }
        body{font-family:'Courier New',monospace;margin:0;padding:6px;color:#111;width:52mm;font-size:11px}
        *{box-sizing:border-box}
        .dash{border-top:1px dashed #333;margin:6px 0}
        .center{text-align:center}
    </style></head><body>
    <div class="center" style="font-weight:700;font-size:13px">${esc(biz)}</div>
    <div class="center" style="font-size:10px;color:#444">${esc(q.created_at || new Date().toLocaleDateString('es'))} · ${esc(q.numero || ('COT-' + String(q.id).padStart(5,'0')))}</div>
    ${q.client_name ? `<div class="center" style="font-size:10px;margin-top:3px">${esc(q.client_name)}</div>` : ''}
    <div class="dash"></div>
    ${rows}
    <div class="dash"></div>
    <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>${fmt(subtotalCents)}</span></div>
    
    <div class="dash"></div>
    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:13px"><span>TOTAL</span><span>${fmt(totalCents)}</span></div>
    <div class="center" style="font-size:9px;color:#666;margin-top:10px">Documento referencial, no es<br>comprobante de pago electrónico.</div>
    <div class="center" style="font-size:10px;margin-top:6px">¡Gracias por su preferencia!</div>
    </body></html>`;
}

function printTicket() {
    const html = buildTicketHtml();
    if (!html) { bxAviso('Abre una cotización antes de exportarla', 'warning'); return; }
    const w = window.open('', '_blank', 'width=350,height=600');
    w.document.open(); w.document.write(html); w.document.close();
    // document.write() en una ventana nueva puede disparar "load" antes de
    // que alcancemos a engancharnos, o nunca si ya se disparo; con la bandera
    // solo se imprime una vez sin importar cual de los dos dispare primero.
    let printed = false;
    const doPrint = () => { if (printed) return; printed = true; w.focus(); w.print(); };
    w.onload = doPrint;
    setTimeout(doPrint, 400);
}
</script>
</x-portal-layout>
