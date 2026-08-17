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
/* ── Reset & Base ── */
.q-wrap { display:flex; height:calc(100vh - 56px); overflow:hidden; background:#f8f9fb; font-family:inherit; }

/* ── Sidebar lista ── */
.q-sidebar { width:300px; flex-shrink:0; display:flex; flex-direction:column; background:#fff; border-right:1px solid #e5e7eb; }
.q-sidebar-head { padding:10px 12px; border-bottom:1px solid #e5e7eb; display:flex; gap:8px; align-items:center; }
.q-search { flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:6px 10px 6px 30px; font-size:12px; outline:none; background:#f8f9fb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%239ca3af' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 8px center; }
.q-search:focus { border-color:#6366f1; background-color:#fff; }
.q-btn-new { background:#4f46e5; color:#fff; border:none; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap; }
.q-btn-new:hover { background:#4338ca; }
.q-filters { display:flex; gap:4px; padding:8px 12px; border-bottom:1px solid #f3f4f6; overflow-x:auto; }
.q-filter { border:none; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:500; cursor:pointer; background:#f3f4f6; color:#5b6270; white-space:nowrap; }
.q-filter.active { background:#eef2ff; color:#4338ca; }
.q-list { overflow-y:auto; flex:1; }
.q-item { padding:10px 14px; border-bottom:1px solid #f3f4f6; cursor:pointer; display:flex; align-items:center; gap:10px; transition:background .1s; }
.q-item:hover { background:#f8f9fb; }
.q-item.active { background:#eef2ff; border-left:3px solid #6366f1; }
.q-item-body { flex:1; min-width:0; }
.q-item-name { font-size:13px; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.q-item-meta { font-size:11px; color:var(--texto-debil, #5b6270); margin-top:1px; display:flex; gap:6px; align-items:center; }
.q-item-total { font-size:12px; font-weight:700; color:#374151; flex-shrink:0; }

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
.q-main { flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0; }
.q-main-head { padding:12px 20px; border-bottom:1px solid #e5e7eb; background:#fff; display:flex; align-items:center; gap:12px; flex-shrink:0; }
.q-main-title { font-size:15px; font-weight:700; color:#111827; }
.q-main-sub { font-size:12px; color:var(--texto-debil, #5b6270); }
.q-main-body { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px; }

/* ── Tabla de productos ── */
.q-table-wrap { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.q-table { width:100%; border-collapse:collapse; }
.q-table th { font-size:11px; font-weight:600; color:#5b6270; text-transform:uppercase; letter-spacing:.4px; padding:8px 12px; background:#f9fafb; border-bottom:1px solid #e5e7eb; text-align:left; }
.q-table th.r, .q-table td.r { text-align:right; }
.q-table td { padding:6px 8px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.q-table tr:last-child td { border-bottom:none; }
.q-table tr:hover td { background:#fafafa; }
.q-td-input { border:1px solid transparent; border-radius:6px; padding:5px 8px; font-size:13px; width:100%; background:transparent; outline:none; color:#111827; transition:border .15s,background .15s; min-width:0; }
.q-td-input:focus { border-color:#6366f1; background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-td-input.desc { min-width:180px; }
.q-td-input.num  { width:72px; text-align:right; }
.q-td-sub { font-size:13px; font-weight:600; color:#374151; white-space:nowrap; text-align:right; }
.q-add-row { width:100%; border:none; background:none; padding:8px 12px; font-size:12px; color:#6366f1; cursor:pointer; text-align:left; display:flex; align-items:center; gap:6px; }
.q-add-row:hover { background:#f5f3ff; }

/* ── Buscador catálogo ── */
.q-catalog-wrap { position:relative; }
.q-catalog-input { border:1.5px dashed #c7d2fe; border-radius:8px; padding:7px 12px 7px 32px; font-size:12px; width:100%; outline:none; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%236366f1' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; color:#374151; }
.q-catalog-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-catalog-drop { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.1); z-index:100; overflow:hidden; max-height:220px; overflow-y:auto; }
.q-catalog-item { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; gap:8px; }
.q-catalog-item:hover { background:#f5f3ff; }
.q-catalog-item:last-child { border-bottom:none; }
.q-catalog-name { font-size:12px; font-weight:500; color:#111827; }
.q-catalog-sku  { font-size:10px; color:var(--texto-debil, #5b6270); }
.q-catalog-price{ font-size:12px; font-weight:700; color:#6366f1; flex-shrink:0; }

/* ── Datos cliente ── */
.q-client-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.q-field label { font-size:11px; font-weight:600; color:#5b6270; display:block; margin-bottom:3px; }
.q-field input, .q-field select, .q-field textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:7px 10px; font-size:13px; outline:none; color:#111827; background:#fff; transition:border .15s; }
.q-field input:focus, .q-field select:focus, .q-field textarea:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-section { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.q-section-head { padding:10px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
.q-section-title { font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.4px; }
.q-section-body { padding:14px 16px; }

/* ── Panel derecho ── */
.q-panel { width:240px; flex-shrink:0; display:flex; flex-direction:column; gap:var(--tactil-gap, 8px); padding:16px 14px; overflow-y:auto; background:#f8f9fb; border-left:1px solid #e5e7eb; }
.q-summary { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:14px; }
.q-summary-row { display:flex; justify-content:space-between; font-size:12px; color:#5b6270; padding:3px 0; }
.q-summary-total { display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#111827; padding-top:8px; margin-top:6px; border-top:2px solid #e5e7eb; }
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

/* ── Estado timeline ── */
.q-status-bar { display:flex; gap:4px; align-items:center; }
.q-status-step { flex:1; text-align:center; font-size:10px; font-weight:600; padding:4px 2px; border-radius:6px; cursor:pointer; border:1.5px solid transparent; transition:all .15s; }
.q-status-step.done   { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
.q-status-step.active { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }
/* Rechazada como estado ACTUAL: negativo inequivoco, nunca verde ni azul. */
.q-status-step.rechazada { background:var(--peligro-suave, #fee2e2); color:var(--peligro-fuerte, #b91c1c); border-color:#fecaca; font-weight:800; }
.q-status-step.idle   { background:#f3f4f6; color:var(--texto-debil, #5b6270); }

/* ── Portal minimalista ── */
.q-portal { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.q-portal-label { font-size:11px; font-weight:700; color:#166534; flex:1; min-width:80px; }
.q-portal-btns { display:flex; gap:4px; }
.q-portal-btn { border:1px solid #bbf7d0; background:#fff; border-radius:6px; padding:4px 8px; font-size:11px; font-weight:600; color:#374151; cursor:pointer; display:flex; align-items:center; gap:3px; }
.q-portal-btn:hover { background:#dcfce7; }

/* ── Empty state ── */
.q-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:#d1d5db; }

/* ── Mobile: una vista a la vez (lista O detalle) y el panel de acciones
      apilado DEBAJO del detalle — antes estaba display:none y desde el
      celular no se podía guardar, cobrar ni exportar una cotización. ── */
@media(max-width:1023px){
  .q-wrap { height:auto; min-height:calc(100vh - 56px); flex-direction:column; overflow:visible; }
  .q-sidebar { width:100%; border-right:none; }
  .q-main { min-height:0; }
  .q-main-body { overflow:visible; }
  .q-main-head { flex-wrap:wrap; }
  .q-status-bar { min-width:0 !important; width:100%; }
  .q-panel { width:100%; border-left:none; border-top:2px solid #e5e7eb; order:3; }
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
.q-status-step { min-height:44px; padding:var(--esp-2, 8px) var(--esp-1, 4px); border:1.5px solid transparent; background:none; font:inherit; font-size:11px; font-weight:600; }
.q-status-step:disabled { cursor:not-allowed; opacity:.55; }
.q-status-bar  { gap:var(--esp-2, 8px); }
.q-filter      { min-height:44px; padding:var(--esp-2, 8px) var(--esp-3, 12px); }
.q-filters     { gap:var(--esp-2, 8px); }
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
@media (max-width:1024px) {
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
@media (max-width:1024px) {
  .q-table td { flex-wrap:wrap; }
  .q-table td::before { flex:1 1 auto; min-width:0; }
  .q-table td .q-td-input { flex:0 0 auto; max-width:100%; width:auto; min-width:0; }
  .q-table-wrap, .q-section, .q-main-body { min-width:0; max-width:100%; overflow-x:clip; }
}
@media (max-width:360px) {
  .q-table td { flex-direction:column; align-items:stretch; gap:4px; }
  .q-table td .q-td-input { width:100%; max-width:100%; }
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
.q-section-head, .q-summary-actions, .q-portal-actions, .q-acciones-barra { gap:var(--tactil-gap, 8px); }

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

/* 14. Los cuatro estados SIN recorte en estrecho (hallazgo: 'Rechazada'
       cortada a 320 por el min-width:300px inline compitiendo con el rail).
       Rejilla 2x2 con objetivos de 44 px y separacion de 8 px. */
@media (max-width:640px) {
  .q-status-bar { min-width:0 !important; width:100%;
    display:grid; grid-template-columns:1fr 1fr; gap:var(--tactil-gap, 8px); }
  .q-status-step { width:100%; }
}
@media (min-width:641px) and (max-width:1023px) {
  .q-status-bar { min-width:0 !important; flex-wrap:wrap; }
}

/* 7. Sin animación para quien la ha desactivado en su sistema. */
@media (prefers-reduced-motion: reduce) {
  .q-wrap *, .q-modal, .q-modal-fondo { transition:none !important; animation:none !important; }
}
</style>

<div class="q-wrap" x-init="montarHistorial()" x-data="{
    quotes: {{ Js::from($quotes->map(fn($q) => [
        'id'               => $q->id,
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

    products: {{ Js::from($products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'price'=>\App\Support\LineMath::canon((string) $p->price),'sku'=>$p->sku??''])) }},
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
        return this.quotes.filter(q => {
            const s = !this.search || q.client_name.toLowerCase().includes(this.search.toLowerCase()) || (q.client_phone||'').includes(this.search);
            const f = this.filterStatus === 'por_cobrar'
                ? (q.status === 'accepted' && (q.payment_status||'pending') !== 'paid')
                : (!this.filterStatus || q.status === this.filterStatus);
            return s && f;
        });
    },

    get porCobrarCount() {
        return this.quotes.filter(q => q.status === 'accepted' && (q.payment_status||'pending') !== 'paid').length;
    },

    /* KPIs en CENTAVOS BigInt: sumar decenas de importes en float acumula
       error justo en el numero que el vendedor usa para cobrar. */
    get porCobrarCents() {
        return this.quotes
            .filter(q => q.status === 'accepted' && (q.payment_status||'pending') !== 'paid')
            .reduce((c,q) => c + this.lmCentsDe(q.total) - (q.payment_status==='partial' ? this.lmCentsDe(q.paid_amount) : 0n), 0n);
    },
    get porCobrarTotal() { return this.porCobrarCents; },

    get cobradoMesTotal() {
        const now = new Date(); const y = now.getFullYear(), m = now.getMonth();
        return this.quotes.filter(q => q.payment_status === 'paid' && q.updated_at && (d => d.getFullYear()===y && d.getMonth()===m)(new Date(q.updated_at)))
            .reduce((c,q) => c + this.lmCentsDe(q.total), 0n);
    },

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

    clientHistory(q) {
        if (!q) return [];
        return this.quotes.filter(o => o.id !== q.id && (
            (q.client_id && o.client_id === q.client_id) ||
            (q.client_phone && o.client_phone === q.client_phone)
        )).slice(0, 5);
    },

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
        const row = { id:q.id, client_name:q.client_name, client_phone:q.client_phone||'', client_email:q.client_email||'', client_doc_type:q.client_doc_type||'', client_doc_number:q.client_doc_number||'', client_address:q.client_address||'', client_id:q.client_id||null, status:q.status, payment_status:q.payment_status||'pending', paid_amount:q.paid_amount??null, payment_proof_url:'', payment_proof_at:'', reject_reason:'', rejected_at:'', seen_at:'', updated_at:q.updated_at||'', total:String(q.total ?? '0.00'), notes:q.notes||'', valid_until:q.valid_until||'', payment_method:q.payment_method||'', payment_condition:q.payment_condition||'', created_at:q.created_at||new Date().toLocaleDateString('es'), token:'', sent_at:'', items:q.items||[] };
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
        if (!confirm('¿Eliminar ' + this.bulkIds.length + ' cotización(es)? Esta acción no se puede deshacer.')) return;
        this.bulkRunning = true;
        for (const id of [...this.bulkIds]) {
            await fetch('{{ $quotesApiBase }}/'+id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        }
        this.quotes = this.quotes.filter(q => !this.bulkIds.includes(q.id));
        if (this.selected && this.bulkIds.includes(this.selected.id)) { this.selected = null; this.creating = false; }
        this.bulkRunning = false;
        this.clearBulk();
    },

    sendPaymentReminder() {
        if (!this.selected) return;
        const clientWa = (this.selected.client_phone || '').replace(/\D/g, '');
        if (!clientWa) { alert('Esta cotización no tiene un teléfono de cliente registrado'); return; }
        const faltaCents = this.subtotalCents - (this.selected.payment_status==='partial' ? this.lmCentsDe(this.selected.paid_amount) : 0n);
        const falta = faltaCents;
        const msg = 'Hola ' + (this.selected.client_name||'') + ', te recordamos que tienes un saldo pendiente de ' + this.fmt(falta) + ' por la cotización #' + this.selected.id + '. ¡Gracias!';
        window.open('https://wa.me/'+clientWa+'?text='+encodeURIComponent(msg),'_blank');
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

    /* Clase visual de cada paso del estado. Semantica EXPLICITA (F1c):
       'rejected' esta FUERA de la secuencia draft->sent->accepted, asi que
       jamas puede salir 'done'. El bug: indexOf('rejected') sobre el flujo
       positivo da -1, y 0 > -1 marcaba Rechazada como completada estando la
       cotizacion en Borrador — un estado de negocio falso en pantalla. */
    claseEstado(clave) {
        const flujo = ['draft', 'sent', 'accepted'];
        if (this.form.status === clave) return clave === 'rejected' ? 'rechazada' : 'active';
        if (clave === 'rejected') return 'idle';
        const actual = flujo.indexOf(this.form.status), paso = flujo.indexOf(clave);
        return (actual > -1 && paso > -1 && actual > paso) ? 'done' : 'idle';
    },

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
        if (window.location.pathname !== url) history.pushState({ cotizacion: id || null }, '', url);
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
            notes: q.notes||'', valid_until: q.valid_until||'', status: q.status,
            payment_status: q.payment_status||'pending', paid_amount: q.paid_amount ?? '',
            payment_method: q.payment_method||'', payment_condition: q.payment_condition||'',
            items: q.items && q.items.length ? q.items.map(i=>({...i,discount:i.discount||0})) : [{description:'',price:'',quantity:1,discount:0}]
        };
        this.portalUrl = q.token ? '{{ url('/b/'.$project->slug.'/c/') }}/' + q.token : '';
        this.creating = false;
        if(window.innerWidth < 1024) { this.panel = 'detail'; window.scrollTo({top:0}); }
        if (this.isUnseen(q)) {
            q.seen_at = new Date().toISOString();
            fetch('{{ $quotesApiBase }}/'+q.id+'/seen', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        }
    },

    openNew() {
        this.selected = null; this.creating = true; this.portalUrl = '';
        this.form = { client_name:'', client_phone:'', client_email:'', client_doc_type:'', client_doc_number:'', client_address:'', notes:'', valid_until:'', status:'draft', payment_status:'pending', paid_amount:'', payment_method:'', payment_condition:'', items:[{description:'',price:'',quantity:1,discount:0}] };
        if(window.innerWidth < 1024) { this.panel = 'detail'; window.scrollTo({top:0}); }
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

    async save() {
        if (!this.form.client_name.trim()) { alert('Ingresa el nombre del cliente'); return; }
        this.saving = true;
        const base = '{{ $quotesApiBase }}';
        const url  = this.creating ? base : base + '/' + this.selected.id;
        const method = this.creating ? 'POST' : (this.selected ? 'PUT' : 'POST');
        const body = {
            client_name: this.form.client_name, client_phone: this.form.client_phone,
            client_email: this.form.client_email, client_doc_type: this.form.client_doc_type,
            client_doc_number: this.form.client_doc_number, client_address: this.form.client_address,
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
            alert(msg);
            return;
        }
        const data = await res.json();
        const q = data.quote;
        if (q) {
            const display = {...q, items: q.items||this.form.items, total:String(q.total ?? '0.00')};
            const row = { id:q.id, client_name:q.client_name, client_phone:q.client_phone||'', client_email:q.client_email||'', client_doc_type:q.client_doc_type||'', client_doc_number:q.client_doc_number||'', client_address:q.client_address||'', client_id:q.client_id||null, status:q.status, payment_status:q.payment_status||'pending', paid_amount:q.paid_amount??null, payment_proof_url:q.payment_proof_url||'', payment_proof_at:q.payment_proof_at||'', reject_reason:q.reject_reason||'', rejected_at:q.rejected_at||'', seen_at:q.seen_at||'', updated_at:q.updated_at||'', total:String(q.total ?? '0.00'), notes:q.notes||'', valid_until:q.valid_until||'', payment_method:q.payment_method||'', payment_condition:q.payment_condition||'', created_at:q.created_at||new Date().toLocaleDateString('es'), token:q.token||'', sent_at:q.sent_at||'', items:q.items||[] };
            if (this.creating) { this.quotes.unshift(row); } else { const idx=this.quotes.findIndex(x=>x.id===q.id); if(idx>-1) this.quotes[idx]=row; }
            this.selected = row; this.creating = false;
            this.form.items = (q.items||[]).map(i=>({...i,discount:i.discount||0}));
        }
        this.saving = false;
    },

    async del() {
        if(!confirm('¿Eliminar esta cotización?')) return;
        await fetch('{{ $quotesApiBase }}/'+this.selected.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        this.quotes = this.quotes.filter(q=>q.id!==this.selected.id);
        this.selected = null; this.creating = false; this.panel = 'list';
    },

    async setStatus(s) {
        this.form.status = s;
        if (!this.creating && this.selected) await this.save();
    },

    async setPaymentStatus(s) {
        if (this.creating || !this.selected || this.payingStatus) return;
        this.form.payment_status = s;
        this.payingStatus = true;
        const res = await fetch('{{ $quotesApiBase }}/'+this.selected.id, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body: JSON.stringify({ status: this.form.status, payment_status: s, paid_amount: s === 'partial' ? this.lmFmt(this.lmCentsDe(this.form.paid_amount)) : null }),
        });
        this.payingStatus = false;
        if (!res.ok) return;
        const data = await res.json();
        const q = data.quote;
        if (q) {
            this.selected.payment_status = q.payment_status;
            this.selected.paid_amount = q.paid_amount;
            const idx = this.quotes.findIndex(x=>x.id===q.id);
            if (idx>-1) { this.quotes[idx].payment_status = q.payment_status; this.quotes[idx].paid_amount = q.paid_amount; }
        }
    },

    async sendToClient() {
        if (!this.selected) return;
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
        if (!link) { alert('Primero genera el enlace del portal'); return; }
        const wa = '{{ preg_replace('/\D/','', $project->whatsapp ?? '') }}';
        if (!wa) { alert('Configura el número de WhatsApp en ajustes'); return; }
        const msg = 'Hola ' + (this.selected?.client_name||'') + ', te comparto tu cotización: ' + link;
        window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg),'_blank');
    },

    copyLink() {
        const link = this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/'+this.selected.token : '');
        if (link) navigator.clipboard.writeText(link);
    },
}">

{{-- ══ SIDEBAR LISTA ══ --}}
<div class="q-sidebar" :class="panel==='detail' ? 'hidden md:flex' : 'flex'">
    <div class="q-sidebar-head">
        <input type="text" x-model="search" placeholder="Buscar cliente..." class="q-search">
        <button @click="openNew()" class="q-btn-new">+ Nueva</button>
    </div>
    <div x-show="porCobrarTotal>0 || cobradoMesTotal>0" style="display:flex;gap:8px;padding:8px 12px;border-bottom:1px solid #f3f4f6">
        <div style="flex:1;background:#fef3c7;border-radius:8px;padding:6px 10px">
            <div style="font-size:9.5px;font-weight:700;color:#b45309;text-transform:uppercase">Por cobrar</div>
            <div style="font-size:13px;font-weight:800;color:#92400e" x-text="fmt(porCobrarTotal)"></div>
        </div>
        <div style="flex:1;background:#dcfce7;border-radius:8px;padding:6px 10px">
            <div style="font-size:9.5px;font-weight:700;color:#15803d;text-transform:uppercase">Cobrado este mes</div>
            <div style="font-size:13px;font-weight:800;color:#166534" x-text="fmt(cobradoMesTotal)"></div>
        </div>
    </div>
    <div class="q-filters">
        <button @click="filterStatus=''" class="q-filter" :class="filterStatus==='' ? 'active':''">Todas</button>
        <button @click="filterStatus='draft'" class="q-filter" :class="filterStatus==='draft' ? 'active':''">Borrador</button>
        <button @click="filterStatus='sent'" class="q-filter" :class="filterStatus==='sent' ? 'active':''">Enviadas</button>
        <button @click="filterStatus='accepted'" class="q-filter" :class="filterStatus==='accepted' ? 'active':''">Aceptadas</button>
        <button @click="filterStatus='por_cobrar'" class="q-filter" :class="filterStatus==='por_cobrar' ? 'active':''" x-show="porCobrarCount>0">
            💰 Por cobrar (<span x-text="porCobrarCount"></span>)
        </button>
    </div>

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
                <span @click.stop="toggleBulk(q.id)"
                      style="width:15px;height:15px;border-radius:4px;border:1.5px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center;cursor:pointer"
                      :style="bulkIds.includes(q.id) ? 'background:#6366f1;border-color:#6366f1' : 'background:#fff'">
                    <svg x-show="bulkIds.includes(q.id)" width="9" height="9" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div class="q-item-body">
                    <div class="q-item-name" style="display:flex;align-items:center;gap:5px">
                        <span x-show="isUnseen(q)" style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0" title="Cambio nuevo del cliente"></span>
                        <span x-text="q.client_name"></span>
                    </div>
                    <div class="q-item-meta">
                        <span x-text="q.created_at"></span>
                        <span x-show="q.client_phone" x-text="'· '+q.client_phone"></span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                    <span class="q-item-total" x-text="fmt(q.total)"></span>
                    <span :class="'qbadge qbadge-'+q.status" x-text="(q.pill_comercial||{}).label || q.status"></span>
                    <span x-show="q.vencida" class="qbadge" style="background:#fef3c7;color:#92400e" title="Su fecha de vigencia pasó">Vencida</span>
                    <span x-show="q.status==='accepted'" :class="'pbadge pbadge-'+(q.payment_status||'pending')" x-text="{pending:'Por cobrar',partial:'Pago parcial',paid:'Pagado',refunded:'Reembolsado'}[q.payment_status||'pending']"></span>
                </div>
            </div>
        </template>
        <div x-show="filtered.length===0" style="padding:40px 0;text-align:center;font-size:13px;color:var(--texto-debil, #5b6270)">Sin cotizaciones</div>
    </div>
</div>

{{-- ══ ZONA CENTRAL ══ --}}
<div class="q-main" :class="panel==='list' ? 'hidden md:flex' : 'flex'">

    {{-- Back mobile --}}
    <button @click="panel='list'" class="md:hidden" style="display:flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;color:#6b7280;border:none;background:#fff;border-bottom:1px solid #e5e7eb;width:100%;cursor:pointer">
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
    <div style="display:flex;flex-direction:column;height:100%;overflow:hidden">

        {{-- Header --}}
        <div class="q-main-head">
            <div style="flex:1">
                <div class="q-main-title" x-text="creating ? 'Nueva cotización' : 'Cotización #' + selected.id"></div>
                <div class="q-main-sub" x-text="creating ? 'Completa los datos y agrega productos' : selected.created_at + (selected.client_name ? ' · ' + selected.client_name : '')"></div>
            </div>
            {{-- Estado del documento.
                 F1c: aqui habia un bug real, no solo cosmetico. Alpine itera un
                 objeto como (valor, clave), pero el codigo trataba el primero
                 como clave: pintaba x-text="label" (= 'draft') y llamaba a
                 setStatus('Borrador'), un estado que el validador rechaza. Por
                 eso se veian las claves crudas y ningun paso salia activo. --}}
            <template x-if="!creating && esConvertida">
                <div class="q-status-bar" style="min-width:300px">
                    <span class="q-badge-convertida" title="Ya generó un pedido: su estado no puede cambiarse">Convertida en pedido</span>
                </div>
            </template>
            <template x-if="!creating && !esConvertida">
            <div class="q-status-bar" style="min-width:300px" role="group" aria-label="Estado de la cotización">
                <template x-for="(etiqueta, clave) in {draft:'Borrador',sent:'Enviada',accepted:'Aceptada',rejected:'Rechazada'}" :key="clave">
                    <button type="button"
                         class="q-status-step"
                         :class="claseEstado(clave)"
                         :disabled="!puede.editar"
                         :aria-pressed="form.status===clave ? 'true' : 'false'"
                         @click="setStatus(clave)"
                         x-text="etiqueta"></button>
                </template>
            </div>
            </template>
        </div>

        {{-- Conversión en pedido, relación con el PED y avisos (F1c) --}}
        <div class="q-acciones-barra">
            @include('quotes._acciones')
        </div>

        <template x-if="form.status==='rejected' && selected?.reject_reason">
            <div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:8px 20px;font-size:12px;color:#991b1b">
                <b>Motivo del cliente:</b> <span x-text="selected.reject_reason"></span>
            </div>
        </template>

        <div class="q-main-body">

            {{-- TABLA DE PRODUCTOS --}}
            <div class="q-section">
                <div class="q-section-head">
                    <span class="q-section-title"><svg class="q-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Productos</span>
                    <button @click="addItem()" style="font-size:12px;color:#6366f1;font-weight:600;border:none;background:none;cursor:pointer">+ Agregar línea</button>
                </div>

                {{-- Buscador catálogo --}}
                @if($products->count())
                <div style="padding:10px 14px;border-bottom:1px solid #f3f4f6">
                    <div class="q-catalog-wrap">
                        <input type="text" x-model="catalogSearch"
                               @focus="catalogOpen=true" @input="catalogOpen=true"
                               @keydown.escape="catalogOpen=false;catalogSearch=''"
                               class="q-catalog-input" placeholder="Buscar producto del catálogo para agregar...">
                        <div class="q-catalog-drop" x-show="catalogOpen && filteredCatalog.length" @click.outside="catalogOpen=false;catalogSearch=''">
                            <template x-for="p in filteredCatalog" :key="p.id">
                                <div class="q-catalog-item" @click="addFromCatalog(p)">
                                    <div>
                                        <div class="q-catalog-name" x-text="p.name"></div>
                                        <div class="q-catalog-sku" x-show="p.sku" x-text="'SKU: '+p.sku"></div>
                                    </div>
                                    <span class="q-catalog-price" x-text="fmt(p.price)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                <div class="q-table-wrap" style="border-radius:0;border:none">
                    <table class="q-table">
                        <thead>
                            <tr>
                                <th style="width:40%">Producto / Descripción</th>
                                <th class="r" style="width:10%">Cant.</th>
                                <th class="r" style="width:14%">Precio</th>
                                <th class="r" style="width:10%">Desc.%</th>
                                <th class="r" style="width:14%">Subtotal</th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, i) in form.items" :key="i">
                                <tr>
                                    <td data-label="Producto"><input class="q-td-input desc" x-model="form.items[i].description" placeholder="Descripción del producto"
                                               @keydown.tab.prevent="$event.shiftKey ? (i>0?$refs['qty'+(i-1)].focus():null) : $refs['qty'+i].focus()"></td>
                                    <td data-label="Cant."><input class="q-td-input num" type="number" x-model="form.items[i].quantity" :x-ref="'qty'+i" min="1" max="10000" step="1" inputmode="numeric" placeholder="1"
                                               @keydown.tab.prevent="$refs['price'+i].focus()"></td>
                                    <td data-label="Precio"><input class="q-td-input num" type="number" x-model="form.items[i].price" :x-ref="'price'+i" @keydown.tab.prevent="$refs['disc'+i]?.focus()" min="0" max="99999999.99" step="0.01" inputmode="decimal" placeholder="0.00"
                                               ></td>
                                    <td data-label="Desc.%">{{-- F1b: el descuento ya persiste (quote_items.discount) y viaja al pedido --}}<input class="q-td-input num" type="number" x-model="form.items[i].discount" :x-ref="'disc'+i" min="0" max="100" step="0.01" placeholder="0" inputmode="decimal" aria-label="Descuento porcentual de la línea" {{-- parseFloat solo para el borde rojo de validacion visual: no calcula ningun importe --}} :style="(parseFloat(form.items[i].discount)||0) > 100 || (parseFloat(form.items[i].discount)||0) < 0 ? 'border-color:#dc2626;background:#fef2f2' : ''"></td>
                                    <td class="r" data-label="Subtotal"><span class="q-td-sub" x-text="lmMoneda(lineTotal(item))"></span></td>
                                    <td style="text-align:center" data-label="">
                                        <button @click="removeItem(i)" x-show="form.items.length>1" class="q-icon-btn" title="Eliminar línea" aria-label="Eliminar esta línea">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button class="q-add-row" @click="addItem()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Agregar línea
                    </button>
                </div>
            </div>

            {{-- DATOS DEL CLIENTE --}}
            <div class="q-section">
                <div class="q-section-head"><span class="q-section-title"><svg class="q-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Cliente</span></div>
                <div class="q-section-body">
                    <div class="q-client-grid">
                        <div class="q-field"><label>Nombre *</label><input type="text" x-model="form.client_name" placeholder="Nombre completo"></div>
                        <div class="q-field"><label>Celular</label><input type="tel" x-model="form.client_phone" placeholder="999 999 999"></div>
                        <div class="q-field"><label>Email</label><input type="email" x-model="form.client_email" placeholder="cliente@email.com"></div>
                        <div class="q-field"><label>DNI / RUC</label><input type="text" x-model="form.client_doc_number" placeholder="12345678"></div>
                        <div class="q-field" style="grid-column:1/-1"><label>Dirección</label><input type="text" x-model="form.client_address" placeholder="Av. Principal 123, Lima"></div>
                    </div>

                    {{-- Historial: otras cotizaciones del mismo cliente --}}
                    <template x-if="!creating && selected && clientHistory(selected).length">
                        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6">
                            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">
                                Otras cotizaciones de este cliente (<span x-text="clientHistory(selected).length"></span>)
                            </div>
                            <div style="display:flex;flex-direction:column;gap:4px">
                                <template x-for="h in clientHistory(selected)" :key="h.id">
                                    <button type="button" @click="select(h)"
                                            style="display:flex;justify-content:space-between;align-items:center;padding:6px 8px;border:1px solid #f3f4f6;border-radius:6px;background:#fff;cursor:pointer;text-align:left">
                                        <span style="font-size:11.5px;color:#374151">#<span x-text="h.id"></span> · <span x-text="h.created_at"></span></span>
                                        <span style="display:flex;align-items:center;gap:6px">
                                            <span style="font-size:11.5px;font-weight:700;color:#374151" x-text="fmt(h.total)"></span>
                                            <span :class="'qbadge qbadge-'+h.status" x-text="{draft:'Borrador',sent:'Enviada',accepted:'Aceptada',rejected:'Rechazada',converted:'Convertida'}[h.status]"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- CONDICIONES --}}
            <div class="q-section">
                <div class="q-section-head"><span class="q-section-title">📋 Condiciones</span></div>
                <div class="q-section-body">
                    <div class="q-client-grid">
                        <div class="q-field"><label>Válida hasta</label><input type="date" x-model="form.valid_until"></div>
                        <div class="q-field" x-show="paymentMethods.length">
                            <label>Método de pago</label>
                            <select x-model="form.payment_method"><option value="">—</option>
                                <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div class="q-field" x-show="paymentConditions.length">
                            <label>Condición de pago</label>
                            <select x-model="form.payment_condition"><option value="">—</option>
                                <template x-for="c in paymentConditions" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                        </div>
                        <div class="q-field" style="grid-column:1/-1"><label>Notas internas</label><textarea x-model="form.notes" rows="2" placeholder="Observaciones, términos especiales..."></textarea></div>
                    </div>
                </div>
            </div>

            {{-- PORTAL CLIENTE (solo si tiene token) --}}
            <template x-if="!creating && (selected?.token || portalUrl)">
                <div class="q-portal">
                    <span class="q-portal-label">🔗 Portal cliente ✓</span>
                    <div class="q-portal-btns">
                        <button class="q-portal-btn" @click="copyLink()" title="Copiar enlace">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            Copiar
                        </button>
                        <a class="q-portal-btn" :href="portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/'+selected.token)" target="_blank">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                            Abrir
                        </a>
                        @if($project->whatsapp)
                        <button class="q-portal-btn" @click="sendWhatsApp()" style="background:#25d366;color:#fff;border-color:#25d366">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                            WA
                        </button>
                        @endif
                        <button class="q-portal-btn" @click="exportMenu=!exportMenu" style="position:relative" x-data="{exportMenu:false}" @click.outside="exportMenu=false">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                            Exportar
                            <div x-show="exportMenu" x-cloak style="position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,.1);z-index:200;min-width:130px;overflow:hidden">
                                <button onclick="exportQuotePDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    PDF
                                </button>
                                <button onclick="exportQuoteImg()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    Imagen PNG
                                </button>
                                <div style="border-top:1px solid #f3f4f6;margin:4px 0"></div>
                                <button onclick="exportBoletaPDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/></svg>
                                    Formato Boleta
                                </button>
                                <button onclick="printTicket()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                                    Formato Ticket
                                </button>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

        </div>{{-- end q-main-body --}}
    </div>
    </template>
</div>

{{-- ══ PANEL DERECHO STICKY ══ --}}
{{-- En móvil solo se muestra junto al detalle (panel==='detail'); en desktop siempre que haya selección --}}
<div class="q-panel" x-show="(selected || creating) && (panel==='detail' || window.innerWidth>=1024)">

    {{-- Resumen financiero --}}
    <div class="q-summary">
        <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Resumen</div>
        <div class="q-summary-row"><span>Subtotal</span><span x-text="fmt(subtotalCents)"></span></div>
        <div class="q-summary-row" x-show="igv>0"><span>IGV (18%)</span><span x-text="fmt(0n)"></span></div>
        <div class="q-summary-total"><span>Total</span><span x-text="fmt(grandTotalCents)"></span></div>
        <div style="font-size:11px;color:var(--texto-debil, #5b6270);margin-top:6px" x-text="form.items.filter(i=>i.description).length + ' producto(s)'"></div>
    </div>

    {{-- Pago (cuenta por cobrar) — solo una vez aceptada --}}
    <template x-if="!creating && form.status==='accepted'">
        <div class="q-summary">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">💰 Cobro</div>

            {{-- Comprobante subido por el cliente --}}
            <template x-if="selected?.payment_proof_url">
                <div style="display:flex;gap:8px;align-items:center;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:8px;margin-bottom:10px">
                    <a :href="selected.payment_proof_url" target="_blank">
                        <img :src="selected.payment_proof_url" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0">
                    </a>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:11px;font-weight:700;color:#374151">Comprobante recibido</div>
                        <div style="font-size:10px;color:var(--texto-debil, #5b6270)" x-text="selected.payment_proof_at"></div>
                    </div>
                    <button type="button" x-show="form.payment_status!=='paid'" @click="setPaymentStatus('paid')" :disabled="payingStatus"
                            style="border:none;background:#15803d;color:#fff;border-radius:6px;padding:5px 8px;font-size:10px;font-weight:700;cursor:pointer;flex-shrink:0">Aprobar</button>
                </div>
            </template>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:5px">
                <button type="button" @click="setPaymentStatus('pending')" :disabled="payingStatus"
                        :style="form.payment_status==='pending' ? 'background:#b45309;color:#fff;border-color:#b45309' : 'background:#fff;color:#6b7280;border-color:#e5e7eb'"
                        style="border-radius:7px;border:1px solid;font-size:10.5px;font-weight:700;padding:6px 2px;cursor:pointer">Pendiente</button>
                <button type="button" @click="setPaymentStatus('partial')" :disabled="payingStatus"
                        :style="form.payment_status==='partial' ? 'background:#a16207;color:#fff;border-color:#a16207' : 'background:#fff;color:#6b7280;border-color:#e5e7eb'"
                        style="border-radius:7px;border:1px solid;font-size:10.5px;font-weight:700;padding:6px 2px;cursor:pointer">Parcial</button>
                <button type="button" @click="setPaymentStatus('paid')" :disabled="payingStatus"
                        :style="form.payment_status==='paid' ? 'background:#15803d;color:#fff;border-color:#15803d' : 'background:#fff;color:#6b7280;border-color:#e5e7eb'"
                        style="border-radius:7px;border:1px solid;font-size:10.5px;font-weight:700;padding:6px 2px;cursor:pointer">Pagado</button>
            </div>
            <template x-if="form.payment_status==='partial'">
                <div style="margin-top:8px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Monto abonado</label>
                    <div style="display:flex;gap:6px">
                        <input type="number" x-model="form.paid_amount" step="0.01" min="0" placeholder="0.00"
                               style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:6px 8px;font-size:12px;outline:none"
                               @keydown.enter="setPaymentStatus('partial')">
                        <button type="button" @click="setPaymentStatus('partial')" :disabled="payingStatus"
                                style="border:none;background:#6366f1;color:#fff;border-radius:8px;padding:6px 10px;font-size:11px;font-weight:600;cursor:pointer">Guardar</button>
                    </div>
                    <div style="font-size:11px;color:var(--texto-debil, #5b6270);margin-top:5px" x-show="form.paid_amount">
                        Falta <span x-text="fmt(subtotalCents - lmCentsDe(form.paid_amount) > 0n ? subtotalCents - lmCentsDe(form.paid_amount) : 0n)"></span>
                    </div>
                </div>
            </template>
            <button type="button" x-show="form.payment_status!=='paid' && selected?.client_phone" @click="sendPaymentReminder()"
                    style="width:100%;margin-top:10px;border:1px solid #bbf7d0;background:#fff;color:#166534;border-radius:8px;padding:7px;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px">
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                Recordatorio de pago
            </button>
        </div>
    </template>

    {{-- Acciones --}}
    <div class="q-actions">
        <button class="q-btn q-btn-primary" @click="save()" :disabled="saving">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
        </button>

        <template x-if="!creating">
            <button class="q-btn q-btn-outline" @click="sendToClient()" :disabled="sending">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span x-text="sending ? 'Generando...' : (selected?.token ? 'Reenviar enlace' : 'Generar enlace')"></span>
            </button>
        </template>

        <template x-if="!creating">
            <button class="q-btn q-btn-outline" @click="duplicateQuote()" :disabled="duplicating">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span x-text="duplicating ? 'Duplicando...' : 'Duplicar'"></span>
            </button>
        </template>

        @if($project->whatsapp)
        <template x-if="!creating && (selected?.token || portalUrl)">
            <button class="q-btn q-btn-green" @click="sendWhatsApp()">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                WhatsApp
            </button>
        </template>
        @endif

        <template x-if="!creating">
            <div x-data="{exportMenu:false}" style="position:relative">
                <button class="q-btn q-btn-outline" @click="exportMenu=!exportMenu" @click.outside="exportMenu=false">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Exportar
                </button>
                <div x-show="exportMenu" x-cloak style="position:absolute;bottom:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,.12);z-index:200;overflow:hidden">
                    <button onclick="exportQuotePDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        PDF
                    </button>
                    <button onclick="exportQuoteImg()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Imagen PNG
                    </button>
                    <div style="border-top:1px solid #f3f4f6;margin:4px 0"></div>
                    <button onclick="exportBoletaPDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/></svg>
                        Formato Boleta
                    </button>
                    <button onclick="printTicket()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                        Formato Ticket
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!creating">
            <button class="q-btn q-btn-outline" @click="del()" style="color:#dc2626;border-color:#fee2e2" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff'">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                Eliminar
            </button>
        </template>
    </div>

</div>

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
    const qWrap = document.querySelector('[x-data]');
    const al = qWrap ? qWrap.__x?.$data : null;
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
    const al = document.querySelector('[x-data]').__x?.$data;
    return al?.selected?.id || 'export';
}

async function htmlToPdf(html, widthPx, filenamePrefix) {
    if (!html) { alert('No hay cotización seleccionada'); return; }
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
    pdf.save(filenamePrefix + '-' + currentQuoteId() + '.pdf');
}

async function htmlToImg(html, widthPx, filenamePrefix) {
    if (!html) { alert('No hay cotización seleccionada'); return; }
    const canvas = await renderOffscreen(html, widthPx);
    const link = document.createElement('a');
    link.download = filenamePrefix + '-' + currentQuoteId() + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

async function exportQuotePDF() { await htmlToPdf(buildQuoteHtml(), 800, 'cotizacion'); }
async function exportQuoteImg() { await htmlToImg(buildQuoteHtml(), 800, 'cotizacion'); }
async function exportBoletaPDF() { await htmlToPdf(buildBoletaHtml(), 420, 'boleta'); }
async function exportBoletaImg() { await htmlToImg(buildBoletaHtml(), 420, 'boleta'); }

// ── Formato Boleta: documento compacto tipo comprobante, para imprimir o
// adjuntar. NO es un comprobante SUNAT real (eso se emite desde Facturación) —
// por eso lleva el aviso al pie. ──
function buildBoletaHtml() {
    const qWrap = document.querySelector('[x-data]');
    const al = qWrap ? qWrap.__x?.$data : null;
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
        <div style="font-size:13px;font-weight:700;margin-top:8px;letter-spacing:.5px">BOLETA DE VENTA</div>
        <div style="font-size:10.5px;color:#6b7280">N° COT-${esc(String(q.id).padStart(6,'0'))} · ${esc(q.created_at || new Date().toLocaleDateString('es'))}</div>
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

// ── Formato Ticket: recibo angosto para impresora térmica (58mm). Usa el
// diálogo de impresión del navegador en vez de generar un PDF: así el
// usuario elige directo su impresora de tickets y evita los problemas de
// nitidez de convertir a imagen a un ancho tan chico. ──
function buildTicketHtml() {
    const qWrap = document.querySelector('[x-data]');
    const al = qWrap ? qWrap.__x?.$data : null;
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
    <div class="center" style="font-size:10px;color:#444">${esc(q.created_at || new Date().toLocaleDateString('es'))} · COT-${esc(String(q.id).padStart(6,'0'))}</div>
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
    if (!html) { alert('No hay cotización seleccionada'); return; }
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
