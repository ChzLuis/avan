{{-- Estilos de la portada "tipo app" de BIXO. La ESTRUCTURA viene de la
     referencia del usuario (app de SUNAT): saludo y estado arriba, una fila
     por area, tarjetas de una sola accion, barra inferior. La CARA es la
     nuestra: la paleta del panel (azul #2563EB, fondo #F8F9FB, Inter),
     botones pildora, iconos en circulo tintado y la tarjeta de estado
     flotando sobre la ola en vez de una carita.
     Van en la vista y no en Tailwind porque el CSS compilado de produccion
     no incluye estas clases. Los comparten la portada movil de Sales y la
     portada de Facturacion. --}}
<style>
    :root{--sn-azul:#2563EB;--sn-azul-d:#1D4ED8;--sn-navy:#1E3A8A;--sn-tinta:#111827;--sn-muted:#6B7280;--sn-borde:#E5E8EF;--sn-fondo:#F8F9FB;--sn-suave:#EFF6FF;--sn-rojo:#EF4444;--sn-ambar:#F59E0B;--sn-verde:#10B981}
    .sn{background:var(--sn-fondo);min-height:100%;padding-bottom:96px;font-family:Inter,system-ui,sans-serif;color:var(--sn-tinta);
        max-width:100%;overflow-x:clip}
    .sn-in{max-width:1100px;margin:0 auto;min-width:0}

    /* ── Saludo sobre la ola de la marca ───────────────────────── */
    .sn-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,var(--sn-navy) 0%,var(--sn-azul-d) 55%,var(--sn-azul) 100%);color:#fff;padding:22px 20px 58px}
    .sn-hero::before{content:"";position:absolute;right:-60px;top:-70px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.08)}
    .sn-hero::after{content:"";position:absolute;left:-40px;bottom:10px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.06)}
    .sn-hero .saludo{position:relative;z-index:1}
    .sn-hero .saludo small{display:block;font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;opacity:.8}
    .sn-hero .saludo h1{margin:4px 0 0;font-size:28px;font-weight:800;letter-spacing:-.02em;line-height:1.15}
    .sn-hero .saludo p{margin:6px 0 0;font-size:13px;opacity:.85}
    .sn-hero svg.ola{position:absolute;left:0;right:0;bottom:-1px;width:100%;height:46px;display:block}

    /* La tarjeta de estado flota sobre la ola: es lo primero que se lee. */
    .sn-estado{margin:-34px 16px 0;position:relative;z-index:2;background:#fff;border:1px solid var(--sn-borde);border-radius:18px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 8px 24px rgba(30,58,138,.12)}
    .sn-estado .punto{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;flex-shrink:0;background:var(--sn-suave);color:var(--sn-azul)}
    .sn-estado .punto svg{width:24px;height:24px}
    .sn-estado.mal .punto{background:#FEF2F2;color:var(--sn-rojo)}
    .sn-estado.ok .punto{background:#ECFDF5;color:var(--sn-verde)}
    .sn-estado h2{margin:0;font-size:17px;font-weight:800;letter-spacing:-.01em;color:var(--sn-tinta)}
    .sn-estado p{margin:3px 0 0;font-size:13px;color:var(--sn-muted);line-height:1.35}

    /* ── Filas y tarjetas ──────────────────────────────────────── */
    .sn-fila{padding:22px 0 0 16px}
    .sn-fila-cab{display:flex;align-items:center;justify-content:space-between;padding-right:12px;margin-bottom:12px}
    .sn-fila-cab h2{margin:0;font-size:16px;font-weight:700;color:var(--sn-tinta);letter-spacing:-.01em}
    .sn-flechas{display:none;gap:6px}
    .sn-flechas button{width:30px;height:30px;border:1px solid var(--sn-borde);background:#fff;color:var(--sn-muted);font-size:20px;line-height:1;cursor:pointer;border-radius:50%;display:grid;place-items:center;padding:0 0 2px}
    .sn-flechas button:hover{color:var(--sn-azul);border-color:#BFDBFE;background:var(--sn-suave)}
    .sn-carril{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:0 16px 6px 0;scrollbar-width:none}
    .sn-carril::-webkit-scrollbar{display:none}
    .sn-card{flex:0 0 clamp(200px,63vw,250px);scroll-snap-align:start;background:#fff;border:1px solid var(--sn-borde);border-radius:18px;padding:16px 14px 14px;display:flex;flex-direction:column;min-height:158px;box-shadow:0 1px 3px rgba(17,24,39,.04)}
    .sn-card .ico{width:40px;height:40px;border-radius:12px;background:var(--sn-suave);color:var(--sn-azul);display:grid;place-items:center;margin-bottom:12px}
    .sn-card .ico svg{width:22px;height:22px}
    .sn-card h3{margin:0;font-size:15px;font-weight:700;color:var(--sn-tinta);line-height:1.3;letter-spacing:-.01em}
    .sn-card .aire{flex:1;min-height:14px}
    .sn-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;align-self:flex-start;min-height:44px;padding:0 18px;font-size:14px;font-weight:700;color:#fff;background:var(--sn-azul);border:0;border-radius:999px;text-decoration:none;cursor:pointer;white-space:nowrap;transition:background .15s}
    .sn-btn:hover{background:var(--sn-azul-d)}
    .sn-btn.sec{background:var(--sn-suave);color:var(--sn-azul-d)}
    .sn-btn.sec:hover{background:#DBEAFE}
    /* Pendientes: franja de color a la izquierda, no toda la tarjeta gritando. */
    .sn-card.pend{border-left:4px solid var(--sn-rojo)}
    .sn-card.pend .ico{background:#FEF2F2;color:var(--sn-rojo)}
    .sn-card.pend .det{margin:8px 0 0;font-size:12px;color:var(--sn-muted);line-height:1.4}
    .sn-card.pend .sn-btn{background:var(--sn-rojo)}
    .sn-card.pend .sn-btn:hover{background:#DC2626}
    .sn-card.pend.amb{border-left-color:var(--sn-ambar)}.sn-card.pend.amb .ico{background:#FFFBEB;color:#D97706}.sn-card.pend.amb .sn-btn{background:#D97706}
    .sn-card.pend.gris{border-left-color:#9CA3AF}.sn-card.pend.gris .ico{background:#F3F4F6;color:#4B5563}.sn-card.pend.gris .sn-btn{background:#4B5563}
    .sn-card .cifra{font-size:24px;font-weight:800;color:var(--sn-tinta);letter-spacing:-.02em;margin-top:4px}
    .sn-card .sub{margin:4px 0 0;font-size:12px;color:var(--sn-muted)}
    .sn-mini{display:flex;flex-direction:column;gap:8px;margin-top:auto;padding-top:12px}
    .sn-mini .sn-btn{align-self:flex-start;padding:8px 14px}
    .sn-mini input{flex:1;min-width:0;padding:9px 11px;font-size:16px;border:1px solid var(--sn-borde);border-radius:10px;background:var(--sn-fondo);min-height:44px}
    .sn-mini input:focus{outline:2px solid var(--sn-azul);outline-offset:-1px;border-color:transparent;background:#fff}
    .sn-res{font-size:12px;margin-top:8px;line-height:1.4}

    /* ── Barra inferior (solo movil) ───────────────────────────── */
    .sn-tabs{position:fixed;left:0;right:0;bottom:0;z-index:60;display:flex;gap:8px;padding:8px 12px calc(8px + env(safe-area-inset-bottom));background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid var(--sn-borde)}
    .sn-tab{flex:1;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;gap:10px;font-size:15px;font-weight:700;color:var(--sn-muted);text-decoration:none;background:transparent}
    .sn-tab svg{width:24px;height:24px;color:currentColor}
    .sn-tab.act{background:var(--sn-suave);color:var(--sn-azul-d)}

    @media (min-width:768px){
        .sn-flechas{display:flex}
        .sn-mini{flex-direction:row}
        .sn-mini input{font-size:14px}
        .sn-tabs{display:none}
        .sn{padding-bottom:32px}
        .sn-hero{padding:28px 24px 64px;border-radius:0 0 24px 24px}
        .sn-hero .saludo h1{font-size:30px}
        .sn-estado{margin:-34px 24px 0}
        .sn-fila{padding-left:24px}
        .sn-carril{padding-right:24px}
        .sn-fila-cab{padding-right:24px}
    }
</style>
