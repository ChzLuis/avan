{{-- ESTILOS DE LAS PANTALLAS DE CONSULTA (.ce-*).
     Un solo sitio para "Comprobantes emitidos" y "Histórico de guías": las dos
     llevaban estas mismas 109 líneas copiadas LITERALMENTE, comentarios
     incluidos, así que cualquier ajuste había que hacerlo dos veces y era
     cuestión de tiempo que divergieran. Si una pantalla necesita algo propio,
     lo añade en su propio <style>, después de incluir este. --}}
<style>
    .ce-wrap{padding:20px;max-width:1200px;margin:0 auto;width:100%}
    .ce-head{margin-bottom:16px}
    .ce-head h1{margin:0;font-size:20px;font-weight:700;color:#111827;letter-spacing:-.01em}
    .ce-head p{margin:4px 0 0;font-size:13px;color:#6b7280}
    /* El buscador manda: es lo único a lo que se viene aquí. */
    .ce-buscar{position:relative;margin-bottom:10px}
    .ce-buscar svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:#9ca3af;pointer-events:none}
    .ce-buscar input{width:100%;padding:11px 14px 11px 40px;font-size:14px;color:#111827;background:#fff;border:1px solid #e5e7eb;border-radius:10px}
    .ce-buscar input:focus{outline:2px solid #6366f1;outline-offset:-1px;border-color:transparent}
    .ce-filtros{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px}
    .ce-filtros select,.ce-filtros input[type=date]{padding:8px 11px;font-size:13px;color:#374151;background:#fff;border:1px solid #e5e7eb;border-radius:8px}
    .ce-filtros .ce-sep{font-size:12px;color:#9ca3af}
    .ce-btn{padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:0;border-radius:8px;cursor:pointer}
    .ce-btn:hover{background:#4338ca}
    .ce-btn-ghost{padding:8px 14px;font-size:13px;font-weight:600;color:#4b5563;background:#fff;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none}
    .ce-tabla{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    .ce-scroll{overflow-x:auto}
    .ce-tabla table{width:100%;border-collapse:collapse;font-size:13px}
    .ce-tabla th{padding:10px 14px;text-align:left;font-weight:600;color:#6b7280;background:#f9fafb;border-bottom:1px solid #e5e7eb;white-space:nowrap}
    .ce-tabla td{padding:11px 14px;color:#374151;border-bottom:1px solid #f3f4f6}
    .ce-tabla tr:last-child td{border-bottom:0}
    .ce-tabla tr:hover td{background:#f9fafb}
    .ce-num{font-weight:600;color:#111827;white-space:nowrap}
    .ce-monto{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .ce-chip{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap}
    .ce-acciones{text-align:right;white-space:nowrap}
    .ce-accion{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;color:#4f46e5;background:#eef2ff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none}
    .ce-accion:hover{background:#e0e7ff}
    .ce-accion svg{width:15px;height:15px}
    .ce-vacio{padding:44px 20px;text-align:center;color:#9ca3af;font-size:14px}
    .ce-accion-ghost{color:#4b5563;background:#f3f4f6;border:0;cursor:pointer;font-family:inherit}
    .ce-accion-ghost:hover{background:#e5e7eb}
    /* Declarar es una accion distinta de mirar: verde, para no confundirla
       con Ver ni con PDF cuando se busca a las apuradas. */
    .ce-accion-sunat{color:#065f46;background:#d1fae5;border:0;cursor:pointer;font-family:inherit}
    .ce-accion-sunat:hover{background:#a7f3d0}
    .ce-accion-sunat[disabled]{opacity:.6;cursor:not-allowed}
    .ce-envio{display:inline-flex;margin:0}
    .ce-aviso{margin:0 0 14px;padding:10px 14px;border-radius:10px;background:#d1fae5;color:#065f46;font-size:13px;font-weight:600}
    /* Creacion: dato de apoyo, en gris. Si no coincide con la emision se
       destaca, porque esa diferencia es justo lo que se viene a mirar. */
    .ce-periodos{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
    .ce-chip-periodo{padding:5px 12px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;
                     color:#4b5563;font-size:12px;font-weight:600;text-decoration:none}
    .ce-chip-periodo:hover{background:#f9fafb}
    .ce-chip-periodo.es-activo{background:#4f46e5;border-color:#4f46e5;color:#fff}
    .ce-creada{color:#6b7280}
    .ce-creada.ce-desfase{color:#92400e;background:#fef3c7;border-radius:6px;padding:1px 6px;font-weight:600;cursor:help}
    /* En pantallas medianas la creacion es lo primero que sobra: la emision
       es la que manda en un comprobante. */
    @media (max-width:1100px){ .ce-col-creacion{display:none} }
    .ce-acciones{display:flex;gap:6px;justify-content:flex-end}
    /* Visor: se mira el comprobante sin perder la búsqueda de detrás. */
    /* El visor tapa TODO el panel, barra lateral incluida: con z-index 60 se
       colaba por debajo del encabezado (100) y del sidebar (101) y el
       comprobante salia cortado y descentrado. Mismo nivel que los modales
       del layout. */
    .ce-visor{position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;padding:24px}
    .ce-visor-fondo{position:absolute;inset:0;background:rgba(15,23,42,.55)}
    .ce-visor-caja{position:relative;display:flex;flex-direction:column;width:min(940px,100%);height:min(88vh,100%);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 20px 50px rgba(15,23,42,.3)}
    .ce-visor-cab{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827}
    .ce-visor-acc{display:flex;align-items:center;gap:8px}
    .ce-visor-x{width:34px;height:34px;font-size:22px;line-height:1;color:#6b7280;background:transparent;border:0;border-radius:8px;cursor:pointer}
    .ce-visor-x:hover{background:#f3f4f6;color:#111827}
    .ce-visor iframe{flex:1;width:100%;border:0;background:#f8fafc}
    .ce-pag{margin-top:14px}

    /* ── MÓVIL ────────────────────────────────────────────────────────────
       Una tabla de 6 columnas en 390 px no se lee ni con scroll lateral: se
       pierde la referencia de qué columna es cada dato. Cada comprobante
       pasa a ser una tarjeta con sus datos etiquetados, que es como se
       consulta de pie en el mostrador. */
    @media (max-width: 720px) {
        .ce-wrap{padding:14px}
        .ce-head h1{font-size:18px}

        /* Filtros: cada uno a su ancho, sin apretarse en una línea. */
        .ce-filtros{gap:8px}
        .ce-filtros select{flex:1 1 100%}
        .ce-filtros input[type=date]{flex:1 1 calc(50% - 16px)}
        .ce-filtros .ce-sep{display:none}
        .ce-filtros .ce-btn,.ce-filtros .ce-btn-ghost{flex:1 1 auto;text-align:center;min-height:44px;line-height:26px}

        /* Campos a 16px: por debajo, iOS hace zoom al tocarlos y descuadra. */
        .ce-buscar input,.ce-filtros select,.ce-filtros input[type=date]{font-size:16px;min-height:46px}

        .ce-tabla{border:0;background:transparent;border-radius:0}
        .ce-scroll{overflow:visible}
        .ce-tabla thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
        .ce-tabla tr{display:block;margin-bottom:10px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
        .ce-tabla td{display:flex;justify-content:space-between;gap:12px;align-items:baseline;padding:4px 0;border:0}
        .ce-tabla tr:hover td{background:transparent}
        /* La etiqueta la pone el CSS: sin cabecera, el dato solo no dice nada. */
        .ce-tabla td::before{content:attr(data-col);flex:0 0 auto;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.03em}
        .ce-tabla td.ce-num{padding-bottom:8px;margin-bottom:4px;border-bottom:1px solid #f3f4f6;font-size:15px}
        .ce-tabla td.ce-monto{text-align:right;font-size:15px;font-weight:700;color:#111827}
        .ce-visor{padding:0}
        .ce-visor-caja{width:100%;height:100%;border-radius:0}
        .ce-tabla td.ce-acciones{margin-top:10px;padding-top:10px;border-top:1px solid #f3f4f6;display:flex;gap:8px}
        .ce-accion{flex:1}
        .ce-tabla td.ce-acciones::before{content:''}
        .ce-accion{width:100%;justify-content:center;min-height:44px;font-size:14px}
        /* En movil el flex-1 lo lleva el form, no el boton que va dentro. */
        .ce-envio{flex:1}
        .ce-tabla td.ce-vacio{display:block;text-align:center}
        .ce-tabla td.ce-vacio::before{content:''}
    }
</style>
