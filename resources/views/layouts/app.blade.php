<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($activeProject) ? $activeProject->name . ' â€” ' : '' }}BIXO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
    // Guard global: un error puntual no debe dejar la página en blanco.
    window.addEventListener('unhandledrejection', function (e) {
        console.warn('[BIXO] Promesa rechazada contenida:', e.reason);
    });

    // Keep-alive de CSRF: refresca el token cada 15 min para evitar el error
    // 419 "Page Expired" en formularios que quedan abiertos mucho tiempo.
    (function () {
        function refreshCsrf() {
            fetch('/csrf-token', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d || !d.token) return;
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', d.token);
                    // Actualiza el hidden _token de todos los formularios abiertos
                    document.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = d.token; });
                })
                .catch(function () {});
        }
        setInterval(refreshCsrf, 15 * 60 * 1000); // cada 15 minutos
        // También al volver a la pestaña tras estar inactiva
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') refreshCsrf();
        });
    })();
    </script>
    <script>
    // Componente Alpine reutilizable: arrastrar y soltar archivos de imagen
    // sobre cualquier zona de subida. Uso: x-data="imageDropzone(files => miFuncion(files))"
    // en el mismo elemento (o uno ancestro) que ya tiene el <input type="file">/<label>.
    // No reemplaza el click-para-elegir existente, solo suma el arrastre.
    document.addEventListener('alpine:init', () => {
        window.imageDropzone = function (onFiles, opts = {}) {
            const accept = opts.accept || 'image/';
            return {
                isDragging: false,
                _dragDepth: 0,
                onDragEnter(e) {
                    if (!Array.from(e.dataTransfer?.types || []).includes('Files')) return;
                    this._dragDepth++;
                    this.isDragging = true;
                },
                onDragLeave() {
                    this._dragDepth = Math.max(0, this._dragDepth - 1);
                    if (this._dragDepth === 0) this.isDragging = false;
                },
                onDrop(e) {
                    this._dragDepth = 0;
                    this.isDragging = false;
                    const files = Array.from(e.dataTransfer?.files || []).filter(f => f.type.startsWith(accept));
                    if (files.length) onFiles(files);
                },
            };
        };
    });
    </script>
    <style>
        [x-cloak]{display:none!important}

        /* ═══════════════════════════════════════════════════════
           BIXO DESIGN SYSTEM — Sidebar empresarial
           Filosofía: Aranda Service Desk. No Bootstrap. No SaaS.
        ═══════════════════════════════════════════════════════ */
        :root {
            --sb-bg:        #FFFFFF;
            --sb-bg-hover:  #F4F5F7;
            --sb-border:    #E1E4E8;
            --sb-purple:    #5B21B6;
            --sb-purple-lt: #EDE9FE;
            --sb-coral:     #7C3AED;
            --sb-text-1:    #1A1D23;
            --sb-text-2:    #44546F;
            --sb-text-3:    #8590A2;
            --sb-font:      'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            /* Firma BIXO — barra izquierda permanente */
            --sb-stripe:    #7C3AED;
        }

        /* ── SHELL ── */
        .sb-bixo {
            font-family: var(--sb-font);
            background: var(--sb-bg);
            border-right: 1px solid var(--sb-border);
            display: flex;
            flex-direction: column;
            transition: width .22s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
            min-height: 0;
            position: relative;         /* ancla para la flecha flotante */
        }

        /* Firma permanente: barra izquierda morada en TODO el sidebar */
        .sb-bixo::before {
            display: none;
        }

        /* ── HEADER del sidebar: oculto siempre (el header superior ya muestra la marca BIXO) ── */
        .sb-header { display: none !important; }
        .sb-logo {
            width: 30px; height: 30px;
            border-radius: 7px; flex-shrink: 0;
            background-image: url('/img/bixo-logo.jpg');
            background-size: 132%;
            background-position: 50% 38%;
            background-repeat: no-repeat;
            font-size: 0; /* oculta la 'B' de texto de respaldo */
        }
        .sb-brand-name { font-size:13px; font-weight:800; color:var(--sb-text-1); line-height:1; }
        .sb-brand-sub  { font-size:9px;  font-weight:700; color:var(--sb-purple); text-transform:uppercase; letter-spacing:.1em; margin-top:2px; }

        /* ── TOGGLE DESKTOP ── */
        /* Flecha circular flotante sobre la línea divisoria (borde derecho interno) */
        .sb-toggle-btn {
            display: none;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 60px;
            right: 4px;                 /* pegada al borde derecho, dentro del sidebar */
            width: 24px;
            height: 24px;
            border: 1px solid var(--sb-border);
            border-radius: 50%;
            background: #fff;
            cursor: pointer;
            color: var(--sb-text-3);
            z-index: 60;
            box-shadow: 0 1px 3px rgba(16,24,40,.1);
            transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
        }
        @media (min-width:768px) { .sb-toggle-btn { display:flex; } }
        .sb-toggle-btn:hover {
            background: var(--sb-purple, #7C3AED);
            color: #fff;
            border-color: var(--sb-purple, #7C3AED);
            box-shadow: 0 2px 8px rgba(124,58,237,.35);
        }

        /* ── NAV SCROLL ── */
        .sb-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
        }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: var(--sb-border); border-radius: 2px; }

        /* ═══════════════════════════════════════════════
           BLOQUE DE MÓDULO — corazón del diseño BIXO
           Igual que los paneles de Aranda
        ═══════════════════════════════════════════════ */
        .sb-module { border-bottom: 1px solid var(--sb-border); }

        /* Encabezado del módulo (clickeable para expandir) */
        .sb-module-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            height: 42px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            font-family: var(--sb-font);
            text-decoration: none;
            transition: background .12s;
            position: relative;
        }
        .sb-module-head:hover { background: var(--sb-bg-hover); }

        /* Módulo ACTIVO — bloque destacado tipo Aranda */
        .sb-module.is-active > .sb-module-head {
            background: var(--sb-purple-lt);
            border-left: 4px solid var(--sb-purple);
        }
        .sb-module.is-active > .sb-module-head .sb-mod-icon { color: var(--sb-purple); }
        .sb-module.is-active > .sb-module-head .sb-mod-title { color: var(--sb-purple); font-weight: 700; }

        .sb-mod-icon {
            width: 16px; height: 16px;
            color: var(--sb-text-3);
            flex-shrink: 0;
            transition: color .12s;
        }

        .sb-mod-title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--sb-text-1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            text-align: left;
            letter-spacing: -.1px;
        }

        .sb-mod-arrow {
            width: 12px; height: 12px;
            color: var(--sb-text-3);
            flex-shrink: 0;
            transition: transform .2s;
        }
        .sb-module.is-open > .sb-module-head .sb-mod-arrow { transform: rotate(90deg); }

        /* Tooltip colapsado */
        .sb-mod-tip {
            position: absolute;
            left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
            background: #1E2028; color: #F1F3F7;
            font-size: 12px; font-weight: 500;
            padding: 5px 10px; border-radius: 6px;
            white-space: nowrap; pointer-events: none;
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
            opacity: 0; transition: opacity .1s; z-index: 200;
        }
        .sb-module-head:hover .sb-mod-tip { opacity: 1; }

        /* ── SUB-ITEMS dentro del módulo ── */
        .sb-sub-list { background: #FAFAFA; }

        .sb-sub-item {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 0 14px 0 40px;
            height: 36px;
            font-size: 12.5px;
            font-weight: 400;
            color: var(--sb-text-2);
            text-decoration: none;
            white-space: nowrap;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-family: var(--sb-font);
            transition: background .1s, color .1s;
            position: relative;
        }
        .sb-sub-item:hover { background: var(--sb-bg-hover); color: var(--sb-text-1); }
        .sb-sub-item.active {
            color: var(--sb-purple);
            font-weight: 600;
            background: var(--sb-purple-lt);
        }
        .sb-sub-item.active::before {
            content: '';
            position: absolute; left: 26px; top: 50%; transform: translateY(-50%);
            width: 4px; height: 4px; border-radius: 50%;
            background: var(--sb-purple);
        }

        /* ── SECTION LABEL (entre módulos, tipo "Accesos directos" de Aranda) ── */
        .sb-section-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--sb-purple);
            text-transform: uppercase;
            letter-spacing: .1em;
            padding: 14px 14px 5px;
            font-family: var(--sb-font);
        }

        /* ── COPILOT ── */
        .sb-copilot {
            margin: 10px;
            padding: 12px 13px;
            border-radius: 8px;
            background: linear-gradient(135deg, #EDE9FE 0%, #F5F3FF 100%);
            border: 1px solid #DDD6FE;
            flex-shrink: 0;
        }
        .sb-copilot-header { display:flex; align-items:center; gap:7px; margin-bottom:5px; }
        .sb-copilot-title  { font-size:12px; font-weight:700; color:var(--sb-text-1); font-family:var(--sb-font); }
        .sb-copilot-desc   { font-size:11px; color:#6D28D9; line-height:1.45; margin:0 0 9px; font-family:var(--sb-font); }
        .sb-copilot-btn {
            width:100%; padding:8px;
            background: var(--sb-purple); color:#fff;
            border:none; border-radius:7px; font-size:12px; font-weight:700;
            cursor:pointer; font-family:var(--sb-font); transition:background .15s;
        }
        .sb-copilot-btn:hover { background:#4C1D95; }

        /* ── USER FOOTER ── */
        .sb-user-footer {
            flex-shrink: 0;
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px;
            border-top: 1px solid var(--sb-border);
            background: #FAFAFA;
        }
        .sb-user-avatar {
            width: 30px; height: 30px; border-radius: 7px;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:12px; font-weight:700; flex-shrink:0;
        }
        .sb-user-name  { font-size:12px; font-weight:600; color:var(--sb-text-1); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.2; }
        .sb-user-email { font-size:10px; color:var(--sb-text-3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:1px; }
        .sb-logout-btn {
            margin-left:auto; width:26px; height:26px; border:none; background:none;
            color:var(--sb-text-3); cursor:pointer; display:flex; align-items:center;
            justify-content:center; border-radius:6px; flex-shrink:0;
            transition:background .15s, color .15s;
        }
        .sb-logout-btn:hover { background:#FEE2E2; color:#EF4444; }

        /* ── MOBILE ── */
        @media (max-width:767px) {
            .mob-bottom-nav {
                display:flex!important; position:fixed!important;
                bottom:0!important; left:0!important; right:0!important;
                background:#fff!important; border-top:1px solid var(--sb-border)!important;
                z-index:50!important; padding:4px 0 env(safe-area-inset-bottom,4px)!important;
            }
            .mob-bottom-nav a {
                flex:1!important; display:flex!important; flex-direction:column!important;
                align-items:center!important; gap:2px!important; padding:6px 4px!important;
                font-size:10px!important; font-weight:500!important; color:var(--sb-text-3)!important;
                text-decoration:none!important; transition:color .15s!important;
            }
            .mob-bottom-nav a.active, .mob-bottom-nav a:hover { color:var(--sb-purple)!important; }
            .mob-main { padding-bottom:60px!important; }
        }
        @media (min-width:768px) { .mob-bottom-nav { display:none!important; } }

        /* ── BIXO HEADER (top bar) ── */
        .bx-header {
            display:flex; align-items:center; gap:4px; height:48px; min-height:48px;
            width:100%; padding:0 16px; background:#ffffff;
            border-bottom:2px solid #7C3AED; flex-shrink:0; z-index:50;
            font-family:var(--sb-font);
        }
        .bx-hdr-logo { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .bx-hdr-logo-mark {
            width:30px; height:30px; border-radius:7px; flex-shrink:0;
            background-image:url('/img/bixo-logo.jpg');
            /* zoom + centrado-arriba: recorta el borde blanco y la marca de agua (esquina inferior-derecha) */
            background-size:132%;
            background-position:50% 38%;
            background-repeat:no-repeat;
            box-shadow:0 0 0 1px rgba(0,0,0,.04);
        }
        .bx-hdr-logo-txt { font-size:14px; font-weight:800; color:#111827; letter-spacing:.02em; }
        .bx-hdr-sep { width:1px; height:22px; background:#E5E7EB; margin:0 8px; flex-shrink:0; }
        .bx-hdr-pagetitle { font-size:13px; font-weight:600; color:#374151; white-space:nowrap; }
        .bx-hdr-action-item { position:relative; }
        .bx-hdr-icon-btn {
            position:relative; width:34px; height:34px; border:none; background:transparent;
            cursor:pointer; border-radius:8px; display:flex; align-items:center; justify-content:center;
            color:#6B7280; transition:background .15s, color .15s;
        }
        .bx-hdr-icon-btn:hover { background:#F3F4F6; color:#111827; }
        .bx-hdr-badge {
            position:absolute; top:5px; right:5px; min-width:15px; height:15px;
            background:#EF4444; color:#fff; font-size:9px; font-weight:700;
            border-radius:999px; display:flex; align-items:center; justify-content:center; padding:0 3px;
        }
        .bx-hdr-dropdown {
            position:absolute; top:calc(100% + 8px); right:0; background:#fff;
            border:1px solid #E5E7EB; border-radius:12px;
            box-shadow:0 8px 30px rgba(0,0,0,.12); z-index:9998; overflow:hidden;
        }
        .bx-hdr-dd-head {
            display:flex; align-items:center; justify-content:space-between;
            padding:10px 14px; border-bottom:1px solid #F3F4F6;
            font-size:11px; font-weight:700; color:#6B7280;
            text-transform:uppercase; letter-spacing:.06em;
        }
        .bx-hdr-dd-back { font-size:11px; color:#6366f1; font-weight:600; background:none; border:none; cursor:pointer; }
        .bx-hdr-dd-new  { font-size:11px; color:#6366f1; font-weight:600; text-decoration:none; }
        .bx-hdr-dd-body { max-height:280px; overflow-y:auto; }
        .bx-hdr-dd-empty { padding:20px; text-align:center; font-size:12px; color:#9CA3AF; }
        .bx-hdr-dd-row {
            display:flex; align-items:center; gap:10px; padding:10px 14px;
            width:100%; border:none; background:none; cursor:pointer;
            text-align:left; text-decoration:none; transition:background .12s;
        }
        .bx-hdr-dd-row:hover { background:#F9FAFB; }
        .bx-dd-active { background:#F5F3FF; }
        .bx-hdr-dd-ok   { width:20px; height:20px; border-radius:50%; background:#DCFCE7; color:#16a34a; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .bx-hdr-dd-warn { width:20px; height:20px; border-radius:50%; background:#FEF3C7; color:#d97706; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .bx-hdr-dd-tag  { font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; }
        .bx-tag-blue   { background:#DBEAFE; color:#1d4ed8; }
        .bx-tag-purple { background:#EDE9FE; color:#7c3aed; }
        .bx-hdr-dd-date { font-size:10px; color:#9CA3AF; }
        .bx-hdr-dd-file { font-size:11px; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
        .bx-hdr-dd-detail-head { padding:10px 14px; background:#F9FAFB; border-bottom:1px solid #F3F4F6; }
        .bx-hdr-dd-proj-av { width:28px; height:28px; border-radius:7px; color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .bx-hdr-project-btn {
            display:flex; align-items:center; gap:7px; height:32px; padding:0 10px;
            border:1px solid #E5E7EB; border-radius:8px; background:#F9FAFB;
            cursor:pointer; font-family:var(--sb-font); color:#374151;
            transition:border-color .15s, background .15s;
        }
        .bx-hdr-project-btn:hover { border-color:#d1d5db; background:#F3F4F6; }
        .bx-hdr-project-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .bx-hdr-project-name { font-size:12px; font-weight:600; color:#111827; max-width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .bx-hdr-nav-link {
            display:flex; align-items:center; gap:5px; font-size:12px; font-weight:600;
            color:#374151; text-decoration:none; padding:6px 10px; border-radius:7px;
            white-space:nowrap; transition:background .15s, color .15s;
        }
        .bx-hdr-nav-link:hover { background:#F3F4F6; color:#111827; }
        .bx-hdr-user { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .bx-hdr-user-av {
            width:28px; height:28px; border-radius:50%; color:#fff;
            font-size:11px; font-weight:700;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .bx-hdr-user-name { font-size:12px; font-weight:600; color:#111827; white-space:nowrap; }
        .bx-hdr-logout {
            display:flex; align-items:center; gap:5px; font-size:12px; font-weight:600;
            color:#EF4444; background:none; border:none; cursor:pointer;
            padding:5px 8px; border-radius:7px; font-family:var(--sb-font);
            transition:background .15s;
        }
        .bx-hdr-logout:hover { background:#FEF2F2; }

        .sb-mobile-header { display:none; }
        .admin-menu-trigger {
            display:none;
            width:44px;
            height:44px;
            flex:0 0 44px;
            align-items:center;
            justify-content:center;
            border:0;
            border-radius:9px;
            background:transparent;
            color:#374151;
            cursor:pointer;
        }
        .admin-menu-trigger:hover { background:#F3F4F6; color:#111827; }
        .admin-menu-trigger:focus-visible,
        .sb-mobile-close:focus-visible,
        .sb-toggle-btn:focus-visible {
            outline:3px solid rgba(79,70,229,.35);
            outline-offset:2px;
        }
        body.admin-sidebar-open { overflow:hidden; }
        [data-admin-shell],
        [data-admin-main],
        .mob-main,
        .mob-main > * { min-width:0; max-width:100%; }

        @media (max-width:767px) {
            .admin-menu-trigger { display:flex; }
            .bx-header { gap:6px; padding:0 10px; }
            .bx-hdr-logo-txt,
            .bx-hdr-sep,
            .bx-hdr-pagetitle,
            .bx-hdr-nav-link,
            .bx-hdr-user-name,
            .bx-hdr-logout { display:none; }
            .sb-bixo {
                position:fixed;
                inset:0 auto 0 0;
                width:min(20rem, calc(100vw - 3rem));
                max-width:calc(100vw - 3rem);
                height:100dvh !important;
                transform:translateX(-100%);
                box-shadow:0 24px 64px rgba(15,23,42,.24);
            }
            .sb-bixo.is-mobile-open { transform:translateX(0); }
            .sb-mobile-header {
                display:flex;
                min-height:56px;
                align-items:center;
                gap:10px;
                padding:8px 10px 8px 14px;
                border-bottom:1px solid var(--sb-border);
                flex-shrink:0;
            }
            .sb-mobile-title { min-width:0; flex:1; font-size:13px; font-weight:800; color:var(--sb-text-1); }
            .sb-mobile-close {
                display:flex;
                width:44px;
                height:44px;
                align-items:center;
                justify-content:center;
                border:0;
                border-radius:9px;
                background:transparent;
                color:var(--sb-text-2);
                cursor:pointer;
            }
            .sb-mobile-close:hover { background:var(--sb-bg-hover); color:var(--sb-text-1); }
        }

        @media (prefers-reduced-motion:reduce) {
            .sb-bixo,
            .sb-toggle-btn,
            .admin-menu-trigger,
            .sb-mobile-close { transition:none !important; }
        }
    </style>
</head>

<body class="font-sans antialiased overflow-hidden" style="background:#f1f5f9;display:flex;flex-direction:column;height:100vh;"
      x-data="{
          open: localStorage.getItem('sb_open') !== null
                    ? localStorage.getItem('sb_open') !== 'false'
                    : window.innerWidth >= 1280,
          sidebarOpen: false,
          sidebarTrigger: null,
          isMob(){ return window.innerWidth < 768 },
          toggleSidebar(){
              if(this.isMob()){
                  this.sidebarOpen ? this.closeSidebar() : this.openSidebar();
              }
              else { this.open = !this.open; localStorage.setItem('sb_open', this.open) }
          },
          openSidebar(){
              if(!this.isMob()) return;
              this.sidebarTrigger = this.$refs.sidebarTrigger;
              this.sidebarOpen = true;
              this.syncBodyLock();
              this.$nextTick(() => this.$refs.sidebarClose?.focus());
          },
          closeSidebar(restoreFocus = true){
              if(!this.sidebarOpen) return;
              this.sidebarOpen = false;
              this.syncBodyLock();
              if(restoreFocus){
                  this.$nextTick(() => (this.sidebarTrigger || this.$refs.sidebarTrigger)?.focus());
              }
          },
          syncBodyLock(){
              document.body.classList.toggle('admin-sidebar-open', this.sidebarOpen && this.isMob());
          },
          handleResize(){
              if(!this.isMob()){
                  this.closeSidebar(false);
                  document.body.classList.remove('admin-sidebar-open');
                  if(localStorage.getItem('sb_open') === null){
                      this.open = window.innerWidth >= 1280;
                  }
              }
          },
          init(){ this.handleResize(); }
      }"
      @resize.window.debounce.150ms="handleResize()"
      @keydown.escape.window="closeSidebar()">

{{-- Overlay mobile --}}
<div x-show="sidebarOpen && isMob()" x-cloak @click="closeSidebar()"
     data-mobile-sidebar-overlay aria-hidden="true"
     class="fixed inset-0 bg-black/60 z-40 md:hidden backdrop-blur-sm"></div>


    @php
        $apName       = $activeProject->name ?? 'Negocio';
        $apInits      = strtoupper(substr($apName,0,2));
        $hPalette     = ['#1d4ed8','#6d28d9','#0e7490','#047857','#b45309','#be123c','#be185d'];
        $apBg         = $hPalette[abs(crc32($apName)) % count($hPalette)];
        $uName2       = auth()->user()->name ?? 'Usuario';
        $uEmail2      = auth()->user()->email ?? '';
        $uInit2       = strtoupper(substr($uName2,0,1));
        $uBg2         = $hPalette[abs(crc32($uName2)) % count($hPalette)];
        $userProjects = \App\Models\Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
            ->get();
    @endphp

    <header class="bx-header">

        <button type="button"
                x-ref="sidebarTrigger"
                @click="openSidebar()"
                class="admin-menu-trigger"
                data-mobile-sidebar-trigger
                aria-label="Abrir navegación principal"
                aria-controls="admin-sidebar"
                :aria-expanded="sidebarOpen.toString()">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- LOGO BIXO --}}
        <div class="bx-hdr-logo">
            <div class="bx-hdr-logo-mark">B</div>
            <span class="bx-hdr-logo-txt">BIXO</span>
        </div>

        <div class="bx-hdr-sep"></div>

        {{-- Titulo pagina --}}
        <div class="bx-hdr-pagetitle">@yield('page-title', 'Panel')</div>

        <div style="flex:1"></div>

        {{-- Importaciones --}}
        <div x-data="{
                nd: false, logs: [], loading: false, selected: null, loaded: false,
                open() { this.nd = !this.nd; if (this.nd && !this.loaded) this.load(); },
                load() {
                    this.loading = true;
                    fetch('{{ route('notifications.imports') }}').then(r=>r.json()).then(d=>{this.logs=d.logs;this.loaded=true;}).finally(()=>this.loading=false);
                }
             }" class="bx-hdr-action-item">
            <button @click="open()" class="bx-hdr-icon-btn" title="Importaciones">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                <span x-show="logs.length > 0" x-cloak class="bx-hdr-badge" x-text="logs.length"></span>
            </button>
            <div x-show="nd" @click.outside="nd=false; selected=null" x-cloak class="bx-hdr-dropdown" style="width:300px">
                <div class="bx-hdr-dd-head">
                    <span>Importaciones</span>
                    <button x-show="selected" @click="selected=null" class="bx-hdr-dd-back">← Volver</button>
                </div>
                <div x-show="!selected" class="bx-hdr-dd-body">
                    <div x-show="loading" class="bx-hdr-dd-empty">Cargando...</div>
                    <div x-show="!loading && logs.length===0" class="bx-hdr-dd-empty">Sin importaciones</div>
                    <template x-for="log in logs" :key="log.id">
                        <button @click="selected=log" class="bx-hdr-dd-row">
                            <span x-show="!log.has_errors" class="bx-hdr-dd-ok">✓</span>
                            <span x-show="log.has_errors" class="bx-hdr-dd-warn">!</span>
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:6px">
                                    <span class="bx-hdr-dd-tag" :class="log.type==='products'?'bx-tag-blue':'bx-tag-purple'" x-text="log.type==='products'?'Productos':'Servicios'"></span>
                                    <span class="bx-hdr-dd-date" x-text="log.date_diff"></span>
                                </div>
                                <p class="bx-hdr-dd-file" x-text="log.filename"></p>
                                <div style="display:flex;gap:8px;margin-top:2px">
                                    <span style="font-size:10px;color:#16a34a;font-weight:600" x-text="'+'+log.created+' creados'"></span>
                                    <span style="font-size:10px;color:#2563eb;font-weight:600" x-text="log.updated+' actualizados'"></span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
                <div x-show="selected" class="bx-hdr-dd-body">
                    <div class="bx-hdr-dd-detail-head">
                        <p style="font-size:12px;font-weight:600;color:#1f2937" x-text="selected?.filename"></p>
                        <p style="font-size:10px;color:#9ca3af;margin-top:2px" x-text="selected?.date"></p>
                    </div>
                    <div x-show="!selected?.has_errors" class="bx-hdr-dd-empty" style="color:#16a34a">✓ Sin errores</div>
                    <div x-show="selected?.has_errors" style="padding:8px">
                        <template x-for="(err, idx) in (selected?.errors ?? [])" :key="idx">
                            <div style="font-size:11px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:6px 8px;margin-bottom:4px" x-text="err"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notificaciones generales --}}
        <div class="bx-hdr-action-item">
            <button class="bx-hdr-icon-btn" title="Notificaciones">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>
        </div>

        <div class="bx-hdr-sep"></div>

        {{-- Proyecto activo --}}
        <div x-data="{ pd: false }" class="bx-hdr-action-item">
            <button @click="pd=!pd" class="bx-hdr-project-btn">
                <div class="bx-hdr-project-dot" style="background:{{ $apBg }}"></div>
                <span class="bx-hdr-project-name">{{ $apName }}</span>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="pd" @click.outside="pd=false" x-cloak class="bx-hdr-dropdown" style="right:0;width:240px">
                <div class="bx-hdr-dd-head">
                    <span>Mis negocios</span>
                    <a href="{{ route('projects.create') }}" @click="pd=false" class="bx-hdr-dd-new">+ Nuevo</a>
                </div>
                <div class="bx-hdr-dd-body">
                    @foreach($userProjects as $p)
                    @php
                        $pBg2  = $hPalette[abs(crc32($p->name)) % count($hPalette)];
                        $pIns2 = strtoupper(substr($p->name,0,2));
                        $isCur2 = isset($activeProject) && $activeProject->id===$p->id;
                    @endphp
                    <a href="{{ route('workspace.select', $p) }}" @click="pd=false"
                       class="bx-hdr-dd-row {{ $isCur2 ? 'bx-dd-active' : '' }}">
                        <div class="bx-hdr-dd-proj-av" style="background:{{ $pBg2 }}">{{ $pIns2 }}</div>
                        <div style="flex:1;min-width:0">
                            <p style="font-size:12px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $p->name }}</p>
                            <p style="font-size:10px;color:#9ca3af">{{ $p->category ?? 'Negocio' }}</p>
                        </div>
                        @if($isCur2)
                        <svg width="13" height="13" fill="#7c3aed" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bx-hdr-sep"></div>

        {{-- Inicio --}}
        <a href="{{ url('/panel') }}" class="bx-hdr-nav-link">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Inicio
        </a>

        {{-- Usuario + Salir --}}
        <div class="bx-hdr-user">
            <div class="bx-hdr-user-av" style="background:{{ $uBg2 }}">{{ $uInit2 }}</div>
            <span class="bx-hdr-user-name">{{ explode(' ', $uName2)[0] }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="bx-hdr-logout">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Salir
                </button>
            </form>
        </div>

    </header>

<div class="flex flex-1 overflow-hidden min-w-0 w-full" data-admin-shell>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SIDEBAR - BIXO CUSTOM
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<aside id="admin-sidebar"
       x-ref="sidebarDrawer"
       aria-label="Navegación principal"
       :aria-hidden="isMob() ? (!sidebarOpen).toString() : 'false'"
       :inert="isMob() && !sidebarOpen"
       @click="if (isMob() && $event.target.closest('a')) closeSidebar(false)"
       class="sb-bixo flex-shrink-0 flex flex-col z-50 md:relative md:translate-x-0"
       style="height:100%;"
       :class="{
           'is-mobile-open': sidebarOpen && isMob(),
           'w-[240px]': open && !isMob(),
           'w-[52px]':  !open && !isMob(),
           'w-0':       isMob()
       }">

    <div class="sb-mobile-header">
        <div class="sb-logo" aria-hidden="true">B</div>
        <div class="sb-mobile-title">Navegación</div>
        <button type="button" x-ref="sidebarClose" @click="closeSidebar()"
                class="sb-mobile-close" data-mobile-sidebar-close
                aria-label="Cerrar navegación principal">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Mobile header --}}
    <div class="sb-header">
        <div class="sb-logo">B</div>
        <div x-show="sidebarOpen" x-cloak>
            <div class="sb-brand-name">BIXO</div>
            <div class="sb-brand-sub">Business OS</div>
        </div>
    </div>

    {{-- Toggle desktop: ícono discreto (sin texto, con tooltip) --}}
    <button @click="open=!open" class="sb-toggle-btn"
            :title="open ? 'Colapsar menú' : 'Expandir menú'"
            :aria-label="open ? 'Colapsar menú' : 'Expandir menú'">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             :class="open ? '' : 'rotate-180'" style="transition:transform .2s">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    @php
        $pid            = $activeProject->id ?? null;
        $authUser       = auth()->user();
        $isOwnerOrSuper = $authUser?->is_superadmin || ($activeProject && $activeProject->owner_id === $authUser?->id);
        $sCfgActivo     = request()->routeIs('settings*') || request()->routeIs('roles.*') || request()->routeIs('catalogs*') || request()->routeIs('projects.panel*');
        $sEmpActivo     = request()->routeIs('agenda*') || request()->routeIs('hr.*') || request()->routeIs('sedes.*') || request()->routeIs('proveedores.*') || request()->routeIs('groups.*');
        $sCatActivo     = request()->routeIs('catalog') || request()->routeIs('products.*') || request()->routeIs('services.*') || request()->routeIs('categories.*') || request()->routeIs('reviews.*');
        $sCrmActivo     = request()->routeIs('clients') || request()->routeIs('clients.*') || request()->routeIs('bot-flows.*') || request()->routeIs('bixocrm.*');
        $sComActivo     = request()->routeIs('bixosales.pos*') || request()->routeIs('bixosales.pedidos*') || request()->routeIs('bixosales.cotizaciones*') || request()->routeIs('bixosales.facturas*') || request()->routeIs('bixosales.rifas*') || request()->routeIs('proposals*');
        $sLogActivo     = request()->routeIs('bixosales.reportes*') || request()->routeIs('bots*');

        $negCat  = $activeProject->category ?? 'default';
        $sbLabels = match(true) {
            $negCat === 'restaurante' => [
                'catalogo'   => 'Menú',
                'productos'  => 'Platos',
                'servicios'  => null,           // ocultar — restaurante no tiene "servicios"
                'categorias' => 'Categorías',
                'pedidos'    => 'Pedidos',
                'pos'        => 'Caja / POS',
                'agenda'     => 'Reservas de mesa',
                'clientes'   => 'Clientes',
                'empleados'  => 'Personal',
                'empresa'    => 'Mi Restaurante',
                'cotizaciones' => null,         // ocultar
                'rifas'      => null,           // ocultar
            ],
            in_array($negCat, ['peluqueria','salon_belleza']) => [
                'catalogo'   => 'Servicios',
                'productos'  => null,           // ocultar — no venden productos físicos por defecto
                'servicios'  => 'Tratamientos',
                'categorias' => 'Categorías',
                'pedidos'    => 'Atenciones',
                'pos'        => 'Cobrar',
                'agenda'     => 'Citas',
                'clientes'   => 'Clientes',
                'empleados'  => 'Estilistas',
                'empresa'    => 'Mi Salón',
                'cotizaciones' => null,
                'rifas'      => null,
            ],
            $negCat === 'clinica' => [
                'catalogo'   => 'Servicios',
                'productos'  => null,
                'servicios'  => 'Consultas',
                'categorias' => 'Especialidades',
                'pedidos'    => 'Atenciones',
                'pos'        => 'Cobrar',
                'agenda'     => 'Citas médicas',
                'clientes'   => 'Pacientes',
                'empleados'  => 'Médicos',
                'empresa'    => 'Mi Clínica',
                'cotizaciones' => 'Presupuestos',
                'rifas'      => null,
            ],
            $negCat === 'veterinaria' => [
                'catalogo'   => 'Catálogo',
                'productos'  => 'Productos',
                'servicios'  => 'Consultas',
                'categorias' => 'Categorías',
                'pedidos'    => 'Atenciones',
                'pos'        => 'Cobrar',
                'agenda'     => 'Citas',
                'clientes'   => 'Dueños',
                'empleados'  => 'Veterinarios',
                'empresa'    => 'Mi Veterinaria',
                'cotizaciones' => null,
                'rifas'      => null,
            ],
            $negCat === 'gimnasio' => [
                'catalogo'   => 'Planes',
                'productos'  => 'Membresías',
                'servicios'  => 'Clases',
                'categorias' => 'Categorías',
                'pedidos'    => 'Cobros',
                'pos'        => 'Cobrar',
                'agenda'     => 'Clases',
                'clientes'   => 'Miembros',
                'empleados'  => 'Instructores',
                'empresa'    => 'Mi Gimnasio',
                'cotizaciones' => null,
                'rifas'      => null,
            ],
            $negCat === 'taller' => [
                'catalogo'   => 'Catálogo',
                'productos'  => 'Repuestos',
                'servicios'  => 'Servicios',
                'categorias' => 'Categorías',
                'pedidos'    => 'Órdenes de trabajo',
                'pos'        => 'Cobrar',
                'agenda'     => 'Ingresos',
                'clientes'   => 'Clientes',
                'empleados'  => 'Técnicos',
                'empresa'    => 'Mi Taller',
                'cotizaciones' => 'Presupuestos',
                'rifas'      => null,
            ],
            $negCat === 'farmacia' => [
                'catalogo'   => 'Catálogo',
                'productos'  => 'Medicamentos',
                'servicios'  => 'Servicios',   // farmacias ofrecen servicios (inyectables, control, etc.)
                'categorias' => 'Categorías',
                'pedidos'    => 'Pedidos',
                'pos'        => 'Caja / POS',
                'agenda'     => 'Agenda',
                'clientes'   => 'Clientes',
                'empleados'  => 'Personal',
                'empresa'    => 'Mi Farmacia',
                'cotizaciones' => null,
                'rifas'      => null,
            ],
            $negCat === 'retail' => [
                'catalogo'   => 'Catálogo',
                'productos'  => 'Productos',
                'servicios'  => 'Servicios',   // tiendas también pueden vender servicios (instalación, soporte, etc.)
                'categorias' => 'Categorías',
                'pedidos'    => 'Pedidos',
                'pos'        => 'Punto de venta',
                'agenda'     => null,
                'clientes'   => 'Clientes',
                'empleados'  => 'Usuarios',
                'empresa'    => 'Mi Tienda',
                'cotizaciones' => 'Cotizaciones',
                'rifas'      => null,
            ],
            $negCat === 'educacion' => [
                'catalogo'   => 'Cursos',
                'productos'  => 'Materiales',
                'servicios'  => 'Programas',
                'categorias' => 'Áreas',
                'pedidos'    => 'Matrículas',
                'pos'        => 'Cobrar',
                'agenda'     => 'Horarios',
                'clientes'   => 'Alumnos',
                'empleados'  => 'Docentes',
                'empresa'    => 'Mi Academia',
                'cotizaciones' => 'Proformas',
                'rifas'      => null,
            ],
            default => [
                'catalogo'   => 'Catálogo',
                'productos'  => 'Productos',
                'servicios'  => 'Servicios',
                'categorias' => 'Categorías',
                'pedidos'    => 'Pedidos',
                'pos'        => 'Punto de venta',
                'agenda'     => 'Agenda',
                'clientes'   => 'Clientes',
                'empleados'  => 'Usuarios',
                'empresa'    => 'Mi Empresa',
                'cotizaciones' => 'Cotizaciones',
                'rifas'      => 'Rifas',
            ],
        };
    @endphp

    {{-- NAV --}}
    <nav class="sb-nav"
         x-data="{
             sec: {
                 cfg: {{ $sCfgActivo ? 'true' : 'false' }},
                 emp: {{ $sEmpActivo ? 'true' : 'false' }},
                 cat: {{ $sCatActivo ? 'true' : 'false' }},
                 crm: {{ $sCrmActivo ? 'true' : 'false' }},
                 com: {{ $sComActivo ? 'true' : 'false' }},
                 log: {{ $sLogActivo ? 'true' : 'false' }},
             },
             toggle(k){
                 const abrir = !this.sec[k];
                 // Acordeón: cerrar todos los grupos y abrir solo el seleccionado
                 Object.keys(this.sec).forEach(key => this.sec[key] = false);
                 this.sec[k] = abrir;
             }
         }">

        {{-- ══ BLOQUE: CONFIGURACIÓN ══ --}}
        @php
        // Constructor guiado: entrada única cuando el flag del proyecto lo permite.
        $cfgItems = [
            ['l'=>'Negocio',   'h'=>$pid?route('settings'):'#',           'r'=>'settings_only',    'i'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'perm'=>'settings.negocio'],
            ['l'=>'SEO',       'h'=>$pid?route('settings.seo'):'#',       'r'=>'settings.seo',     'i'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'perm'=>null],
            // Constructor: única pantalla de diseño (la antigua "Diseño" redirige aquí).
            ['l'=>'Constructor', 'h'=>$pid?route('settings.builder'):'#', 'r'=>'settings.builder', 'i'=>'M11 4a1 1 0 011-1h0a1 1 0 011 1v1.07A7.002 7.002 0 0119 12v1h1a1 1 0 011 1v2a1 1 0 01-1 1h-1.07A7.002 7.002 0 0113 20.93V22a1 1 0 01-1 1h0a1 1 0 01-1-1v-1.07A7.002 7.002 0 015 17H4a1 1 0 01-1-1v-2a1 1 0 011-1h1v-1a7.002 7.002 0 016-6.93V4z', 'perm'=>'settings.diseno'],
            ['l'=>'Pagos',     'h'=>$pid?route('settings.payments'):'#',  'r'=>'settings.payments','i'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'perm'=>'settings.pagos'],
            ['l'=>'Módulos',   'h'=>$pid?route('settings.modules'):'#',   'r'=>'settings.modules', 'i'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'perm'=>null],
            ['l'=>'QR',        'h'=>$pid?route('settings.qr'):'#',        'r'=>'settings.qr',      'i'=>'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', 'perm'=>null],
            ['l'=>'Roles',     'h'=>$pid?route('roles.index'):'#',        'r'=>'roles.index',      'i'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', 'perm'=>null],
            ['l'=>'Catálogos', 'h'=>$pid?route('catalogs.index'):'#',     'r'=>'catalogs.index',   'i'=>'M4 6h16M4 10h16M4 14h16M4 18h16', 'perm'=>'settings.catalogos'],
            ['l'=>'Canales WA','h'=>$pid?route('bots.index'):'#',         'r'=>'bots.index',       'i'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 11.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'perm'=>null],
        ];
        // Flujo de estados: para rubros que lo soportan (lavandería, restaurante, taller, etc.)
        $activeProj = app()->bound('active_project') ? app('active_project') : null;
        if ($pid && \App\Support\OrderFlow::supportsFlow(optional($activeProj)->category)) {
            $cfgItems[] = ['l'=>'Flujo de estados', 'h'=>route('settings').'?s=flujo', 'r'=>'settings_flujo', 'i'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'perm'=>null];
        }
        // Items sin permiso específico ('perm'=>null) que igual deben verse siempre
        $cfgSiempreVisible = ['QR', 'SEO'];
        $cfgVisible = array_filter($cfgItems, fn($i) =>
            $isOwnerOrSuper
            || in_array($i['l'], $cfgSiempreVisible, true)
            || ($i['perm'] && $authUser?->can($i['perm']))
        );
        @endphp

        @if(count($cfgVisible) > 0)
        <div class="sb-module {{ $sCfgActivo ? 'is-active is-open' : '' }}" :class="sec.cfg ? 'is-open' : ''">
            <button @click="toggle('cfg')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>Configuración</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>Configuración</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.cfg" x-collapse>
            @foreach($cfgVisible as $item)
            @php
                $ia = match($item['r']) {
                    'settings_only'     => request()->routeIs('settings') && !request()->routeIs('settings.*') && request('s') !== 'flujo',
                    'settings_flujo'    => request()->routeIs('settings') && request('s') === 'flujo',
                    'settings.seo'      => request()->routeIs('settings.seo*'),
                    'settings.design'   => request()->routeIs('settings.design*'),
                    'settings.payments' => request()->routeIs('settings.payments*'),
                    'settings.modules'  => request()->routeIs('settings.modules*'),
                    'settings.qr'       => request()->routeIs('settings.qr*'),
                    'catalogs.index'    => request()->routeIs('catalogs*'),
                    default             => request()->routeIs($item['r'].'*'),
                };
            @endphp
            <a href="{{ $item['h'] }}" class="sb-sub-item {{ $ia ? 'active' : '' }} {{ $item['h']==='#' ? 'opacity-40 pointer-events-none' : '' }}">
                {{ $item['l'] }}
            </a>
            @endforeach
            </div>
        </div>
        @endif

        {{-- ══ BLOQUE: MI EMPRESA ══ --}}
        <div class="sb-module {{ $sEmpActivo ? 'is-active is-open' : '' }}" :class="sec.emp ? 'is-open' : ''">
            <button @click="toggle('emp')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>{{ $sbLabels['empresa'] }}</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>{{ $sbLabels['empresa'] }}</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.emp" x-collapse>
            {{-- Clientes se movió al módulo CRM (arriba) --}}
            @if(false)
                <a href="{{ $pid?route('clients'):'#' }}" class="sb-sub-item {{ request()->routeIs('clients*') ? 'active' : '' }}">{{ $sbLabels['clientes'] }}</a>
                <a href="{{ $pid?route('groups.index',['type'=>'client']):'#' }}" class="sb-sub-item {{ (request()->routeIs('groups.*') && request()->get('type','client')==='client') ? 'active' : '' }}">Segmentos</a>
            @endif
            @if($activeProject && $activeProject->hasModule('agenda') && $sbLabels['agenda'] !== null && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('agenda.ver')))
                <a href="{{ $pid?route('agenda'):'#' }}" class="sb-sub-item {{ request()->routeIs('agenda*') ? 'active' : '' }}">{{ $sbLabels['agenda'] }}</a>
            @endif
            @if($activeProject && $activeProject->hasModule('hr') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('hr.ver')))
                <a href="{{ $pid?route('hr.employees.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('hr.*') ? 'active' : '' }}">{{ $sbLabels['empleados'] }}</a>
                <a href="{{ $pid?route('groups.index',['type'=>'employee']):'#' }}" class="sb-sub-item {{ (request()->routeIs('groups.*') && request()->get('type')==='employee') ? 'active' : '' }}">Áreas</a>
            @endif
            @if($isOwnerOrSuper)
                <a href="{{ $pid?route('sedes.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('sedes.*') ? 'active' : '' }}">Sucursales</a>
                <a href="{{ $pid?route('proveedores.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">Aliados</a>
            @endif
            </div>
        </div>

        {{-- ══ BLOQUE: CATÁLOGO ══ --}}
        @if($activeProject && $activeProject->hasModule('catalog') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('catalog.ver')))
        <div class="sb-module {{ $sCatActivo ? 'is-active is-open' : '' }}" :class="sec.cat ? 'is-open' : ''">
            <button @click="toggle('cat')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>{{ $sbLabels['catalogo'] }}</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>{{ $sbLabels['catalogo'] }}</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.cat" x-collapse>
                @if($sbLabels['productos'] !== null)
                <a href="{{ $pid?route('products.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('products.*') ? 'active' : '' }}">{{ $sbLabels['productos'] }}</a>
                @endif
                @if($sbLabels['servicios'] !== null)
                <a href="{{ $pid?route('services.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('services.*') ? 'active' : '' }}">{{ $sbLabels['servicios'] }}</a>
                @endif
                <a href="{{ $pid?route('categories.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">{{ $sbLabels['categorias'] }}</a>
                {{-- Reseñas: oculto — el controlador aún no está implementado --}}
                {{-- Combos y Promociones: solo owner/superadmin --}}
                @if($isOwnerOrSuper)
                <a href="{{ $pid?route('combos.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('combos.*') ? 'active' : '' }}">Combos</a>
                <a href="{{ $pid?route('promotions.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('promotions.*') ? 'active' : '' }}">Promociones</a>
                @endif
                @canany(['catalog-integrations.view'])
                <a href="{{ $pid?route('catalog-integrations.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('catalog-integrations.*') ? 'active' : '' }}">Conectar catálogo (API)</a>
                @endcanany
            </div>
        </div>
        @endif

        {{-- ══ BLOQUE: CRM — solo owner/superadmin ══ --}}
        @if($isOwnerOrSuper)
        <div class="sb-module {{ $sCrmActivo ? 'is-active is-open' : '' }}" :class="sec.crm ? 'is-open' : ''">
            <button @click="toggle('crm')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a3 3 0 10-2.5-4.5"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>CRM</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>CRM</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.crm" x-collapse>
                @if($pid)
                    <a href="{{ route('dashboard.comercial') }}" class="sb-sub-item {{ request()->routeIs('dashboard.comercial') ? 'active' : '' }}" style="display:flex;align-items:center;gap:6px;">
                        📊 Dashboard
                    </a>
                    <a href="{{ route('copilot.index') }}" class="sb-sub-item {{ request()->routeIs('copilot.*') ? 'active' : '' }}" style="display:flex;align-items:center;gap:6px;">
                        ✨ Copilot
                        <span style="margin-left:auto;font-size:8px;font-weight:800;background:#7c3aed;color:#fff;padding:1px 5px;border-radius:99px;">IA</span>
                    </a>
                    <a href="{{ route('bixocrm.bandeja') }}" class="sb-sub-item {{ request()->routeIs('bixocrm.bandeja') ? 'active' : '' }}">Conversaciones</a>
                    <a href="{{ route('clients') }}" class="sb-sub-item {{ request()->routeIs('clients') ? 'active' : '' }}">Clientes / Leads</a>
                    <a href="{{ route('clients.pipeline') }}" class="sb-sub-item {{ request()->routeIs('clients.pipeline') ? 'active' : '' }}">Pipeline de ventas</a>
                    <a href="{{ route('bot-flows.index') }}" class="sb-sub-item {{ request()->routeIs('bot-flows.*') ? 'active' : '' }}">Bots</a>
                @endif
            </div>
        </div>
        @endif

        {{-- ══ BLOQUE: COMERCIAL — solo owner/superadmin ══ --}}
        @if($isOwnerOrSuper)
        <div class="sb-module {{ $sComActivo ? 'is-active is-open' : '' }}" :class="sec.com ? 'is-open' : ''">
            <button @click="toggle('com')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>Comercial</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>Comercial</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.com" x-collapse>
            @if($pid)
                <a href="{{ route('bixosales.mapa.index') }}"
                   class="sb-sub-item {{ request()->routeIs('bixosales.mapa*') ? 'active' : '' }}"
                   style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:13px;">🗺️</span> Mapa Operativo
                    <span style="margin-left:auto;font-size:8px;font-weight:800;background:#3b82f6;color:#fff;padding:1px 5px;border-radius:99px;letter-spacing:.3px;">NUEVO</span>
                </a>
            @endif
            @if($activeProject && $activeProject->hasModule('pos') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('pos.ver')))
                <a href="{{ $pid?route('bixosales.pos'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.pos*') ? 'active' : '' }}">{{ $sbLabels['pos'] }}</a>
            @endif
            @if($activeProject && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('pos.usar')))
                <a href="{{ $pid?route('bixosales.reseller.precios'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.reseller.*') ? 'active' : '' }}">🏷️ Mis precios y catálogo</a>
            @endif
            @if($activeProject && $activeProject->hasModule('orders') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('orders.ver')))
                <a href="{{ $pid?route('bixosales.pedidos'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.pedidos*') ? 'active' : '' }}">{{ $sbLabels['pedidos'] }}</a>
                @if(in_array($negCat, ['restaurante','cafeteria']))
                <a href="{{ $pid?route('bixosales.cocina'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.cocina*') ? 'active' : '' }}">🍳 Vista Cocina</a>
                @endif
            @endif
            @if(auth()->user()?->is_superadmin || $activeProject?->owner_id===auth()->id() || auth()->user()?->can('invoices.ver'))
                <a href="{{ $pid?route('bixosales.facturas'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.facturas*') ? 'active' : '' }}">Facturas</a>
                @if($sbLabels['cotizaciones'] !== null)
                <a href="{{ $pid?route('bixosales.cotizaciones'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.cotizaciones*') ? 'active' : '' }}">{{ $sbLabels['cotizaciones'] }}</a>
                @endif
            @endif
            @if($activeProject && $activeProject->hasModule('rifas') && $isOwnerOrSuper && $sbLabels['rifas'] !== null)
                <a href="{{ $pid?route('bixosales.rifas'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.rifas*') ? 'active' : '' }}">{{ $sbLabels['rifas'] }}</a>
            @endif
            </div>
        </div>
        @endif

        {{-- ══ BLOQUE: LOGÍSTICA — solo owner/superadmin ══ --}}
        @if($isOwnerOrSuper)
        <div class="sb-module {{ $sLogActivo ? 'is-active is-open' : '' }}" :class="sec.log ? 'is-open' : ''">
            <button @click="toggle('log')" class="sb-module-head">
                <svg class="sb-mod-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
                <span class="sb-mod-title" x-show="open || sidebarOpen" x-cloak>Logística</span>
                <svg class="sb-mod-arrow" x-show="open || sidebarOpen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="sb-mod-tip" x-show="!open && !sidebarOpen" x-cloak>Logística</span>
            </button>
            <div class="sb-sub-list" x-show="(open || sidebarOpen) && sec.log" x-collapse>
            @if($isOwnerOrSuper)
                <a href="{{ $pid?route('bixosales.reportes.ventas.general'):'#' }}" class="sb-sub-item {{ request()->routeIs('bixosales.reportes*') ? 'active' : '' }}">Reportes</a>
            @endif
            @if($activeProject && $activeProject->hasModule('bots') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id()))
                <a href="{{ $pid?route('bots.index'):'#' }}" class="sb-sub-item {{ request()->routeIs('bots*') ? 'active' : '' }}">Bots WhatsApp</a>
            @endif
            </div>
        </div>
        @endif

    </nav>

    {{-- COPILOT --}}
    <div class="sb-copilot" x-show="open || sidebarOpen" x-cloak>
        <div class="sb-copilot-header">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#7C3AED" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="sb-copilot-title">BIXO Copilot</span>
        </div>
        <p class="sb-copilot-desc">Automatiza catálogo y respuestas de WhatsApp con IA.</p>
        <button class="sb-copilot-btn">Activar</button>
    </div>

    {{-- USER FOOTER --}}
    <div class="sb-user-footer">
        <div class="sb-user-avatar" style="background:{{ $uBg2 }}">{{ $uInit2 }}</div>
        <div style="flex:1;min-width:0;" x-show="open || sidebarOpen" x-cloak>
            <div class="sb-user-name">{{ $uName2 }}</div>
            <div class="sb-user-email">{{ $uEmail2 }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" x-show="open || sidebarOpen" x-cloak>
            @csrf
            <button type="submit" class="sb-logout-btn" title="Cerrar sesión">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>
</aside>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MAIN
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="flex flex-col flex-1 overflow-hidden min-w-0">

    {{-- â”€â”€ Header â”€â”€ --}}

    {{-- â”€â”€ Toast global â”€â”€ --}}
    <div x-data="{
            toasts: [],
            add(msg, type='success') {
                const id = Date.now();
                this.toasts.push({ id, msg, type });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
         }"
         x-init="window.addEventListener('app-toast', e => add(e.detail.msg, e.detail.type || 'success'))"
         class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium border max-w-xs"
                 :class="{
                    'bg-green-50 border-green-200 text-green-800': t.type==='success',
                    'bg-red-50 border-red-200 text-red-800': t.type==='error',
                    'bg-amber-50 border-amber-200 text-amber-800': t.type==='warning',
                    'bg-blue-50 border-blue-200 text-blue-800': t.type==='info',
                 }">
                {{-- Ã­cono --}}
                <svg x-show="t.type==='success'" class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="t.type==='error'" class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="t.type==='warning'" class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <svg x-show="t.type==='info'" class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="t.msg" class="flex-1"></span>
                <button @click="remove(t.id)" class="opacity-50 hover:opacity-100 transition ml-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

        {{-- â”€â”€ Modal de confirmaciÃ³n global â”€â”€ --}}
    <div x-data="{
            show: false,
            title: '',
            msg: '',
            confirmLabel: 'Eliminar',
            cancelLabel: 'Cancelar',
            confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
            _resolve: null,
            _confirmHandler: null,
            _previousFocus: null,
            _previousOverflow: '',
            _focusFrame: null,
            _focusAttempts: 0,
            _maxFocusAttempts: 4,
            _userInteracted: false,
            init() {
                this._confirmHandler = (opts) => this.open(opts);
                window.__confirm = this._confirmHandler;
            },
            destroy() {
                if (window.__confirm === this._confirmHandler) {
                    delete window.__confirm;
                }
                this.cancelPendingFocus();
                if (this._resolve) this._settle(false, false);
            },
            open(opts) {
                const options = opts !== null && typeof opts === 'object' && !Array.isArray(opts)
                    ? opts
                    : null;
                if (!options) return Promise.resolve(false);

                if (this._resolve) this._settle(false, false);

                const title = typeof options.title === 'string' ? options.title.trim() : '';
                const message = typeof options.msg === 'string'
                    ? options.msg
                    : (typeof options.message === 'string' ? options.message : '');
                const confirmText = typeof options.confirmLabel === 'string'
                    ? options.confirmLabel.trim()
                    : (typeof options.confirmText === 'string' ? options.confirmText.trim() : '');
                const cancelText = typeof options.cancelLabel === 'string'
                    ? options.cancelLabel.trim()
                    : (typeof options.cancelText === 'string' ? options.cancelText.trim() : '');
                const confirmClass = typeof options.confirmClass === 'string'
                    ? options.confirmClass.trim()
                    : '';

                this.title        = title || 'Â¿Confirmar acciÃ³n?';
                this.msg          = message;
                this.confirmLabel = confirmText || 'Confirmar';
                this.cancelLabel  = cancelText || 'Cancelar';
                this.confirmClass = confirmClass || 'bg-red-600 hover:bg-red-700 text-white';
                this._previousFocus = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
                this._previousOverflow = document.body.style.overflow;
                this.cancelPendingFocus();
                this._focusAttempts = 0;
                this._userInteracted = false;
                document.body.style.overflow = 'hidden';
                this.show = true;
                this.$nextTick(() => this.focusInitial());
                return new Promise(r => this._resolve = r);
            },
            focusInitial() {
                if (!this.show || this._userInteracted) return false;

                this._focusAttempts += 1;
                const dialog = this.$refs.dialog;
                const cancelButton = this.$refs.cancelButton;
                const modal = this.$root;
                const rect = cancelButton?.getBoundingClientRect();
                const style = cancelButton ? getComputedStyle(cancelButton) : null;
                const canFocus = !!(
                    dialog && cancelButton && !cancelButton.disabled && !cancelButton.hidden &&
                    !cancelButton.closest('[inert]') && rect && rect.width > 0 && rect.height > 0 &&
                    style?.display !== 'none' && style?.visibility !== 'hidden' &&
                    getComputedStyle(modal).display !== 'none'
                );

                if (canFocus) {
                    try {
                        cancelButton.focus({ preventScroll: true });
                    } catch (error) {
                        cancelButton.focus();
                    }
                    if (dialog.contains(document.activeElement)) return true;
                }

                if (this.show && !this._userInteracted && this._focusAttempts < this._maxFocusAttempts) {
                    this._focusFrame = requestAnimationFrame(() => {
                        this._focusFrame = null;
                        this.focusInitial();
                    });
                }
                return false;
            },
            cancelPendingFocus() {
                if (this._focusFrame !== null) {
                    cancelAnimationFrame(this._focusFrame);
                    this._focusFrame = null;
                }
            },
            markInteracted() {
                this._userInteracted = true;
                this.cancelPendingFocus();
            },
            _settle(value, restoreFocus = true) {
                const resolve = this._resolve;
                this._resolve = null;
                this.cancelPendingFocus();
                this._userInteracted = true;
                this.show = false;
                document.body.style.overflow = this._previousOverflow;
                const previousFocus = this._previousFocus;
                this._previousFocus = null;
                if (restoreFocus && previousFocus) {
                    this.$nextTick(() => previousFocus.focus());
                }
                if (resolve) resolve(value);
            },
            confirm() { this._settle(true); },
            cancel() { this._settle(false); },
            trapFocus(event) {
                this.markInteracted();
                const controls = [this.$refs.cancelButton, this.$refs.confirmButton]
                    .filter(control => control && !control.disabled);
                if (!controls.length) return;
                const current = controls.indexOf(document.activeElement);
                const next = event.shiftKey
                    ? (current <= 0 ? controls.length - 1 : current - 1)
                    : (current === controls.length - 1 ? 0 : current + 1);
                controls[next].focus();
            }
         }"
         x-show="show" x-cloak
         data-global-confirm-modal
         role="dialog" aria-modal="true"
         aria-labelledby="global-confirm-title"
         aria-describedby="global-confirm-message"
         @keydown.escape.window="show && cancel()"
         @keydown.tab.prevent="show && trapFocus($event)"
         class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="cancel()"></div>
        <div x-ref="dialog" @pointerdown="markInteracted()"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="global-confirm-title" class="text-sm font-semibold text-gray-900" x-text="title"></h3>
                    <p id="global-confirm-message" class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="msg"></p>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" x-ref="cancelButton" @click="cancel()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                        x-text="cancelLabel"></button>
                <button type="button" x-ref="confirmButton" @click="confirm()"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition"
                        :class="confirmClass"
                        x-text="confirmLabel">
                </button>
            </div>
        </div>
    </div>

    {{-- Flash messages (sesiÃ³n) --}}
    @if(session('error'))
    <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,5000)" x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm shadow-xl flex items-start gap-2">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if(session('success'))
    <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm shadow-xl flex items-start gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Content --}}
    <div class="flex flex-1 min-h-0 mob-main" style="overflow:hidden;align-items:stretch">
        {{ $slot }}
    </div>
</div>

</div>

{{-- CHG-92608: Control de cierre de sesión por inactividad --}}
<script>
(function() {
    var LIMIT_S = 30 * 60, WARN_S = 3 * 60, WARN_AT = LIMIT_S - WARN_S;
    var LOGOUT  = {!! json_encode(route('logout')) !!};
    var TOKEN   = function() { return (document.querySelector('meta[name="csrf-token"]')||{}).content || ''; };
    var elapsed = 0, phase = 'idle', tick = null, autoOut = null;
    var elWarn, elExpired, elCd, elBar;

    function buildModals() {
        /* --- modal preventivo --- */
        elWarn = document.createElement('div');
        elWarn.id = 'sw-warn';
        elWarn.setAttribute('style',
            'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
            'z-index:2147483646;background:rgba(0,0,0,.5);' +
            'align-items:center;justify-content:center;');
        elWarn.innerHTML =
            '<div style=”background:#fff;border-radius:16px;padding:28px 32px;width:380px;max-width:92vw;' +
            'box-shadow:0 20px 60px rgba(0,0,0,.3);”>' +
              '<div style=”display:flex;align-items:center;gap:12px;margin-bottom:16px;”>' +
                '<div style=”width:40px;height:40px;min-width:40px;border-radius:50%;background:#FEF3C7;' +
                'display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1;”>⏰</div>' +
                '<div>' +
                  '<p style=”font-size:14px;font-weight:700;color:#111827;margin:0;”>Sesión por expirar</p>' +
                  '<p style=”font-size:12px;color:#9CA3AF;margin:2px 0 0;”>Tu sesión cerrará en ' +
                    '<strong id=”sw-cd” style=”color:#D97706;”></strong>' +
                  '</p>' +
                '</div>' +
              '</div>' +
              '<div style=”background:#F3F4F6;border-radius:99px;height:4px;margin-bottom:20px;overflow:hidden;”>' +
                '<div id=”sw-bar” style=”width:100%;background:#F59E0B;height:100%;border-radius:99px;transition:width 1s linear;”></div>' +
              '</div>' +
              '<div style=”display:flex;gap:8px;”>' +
                '<button onclick=”swLogout()” style=”flex:1;padding:9px;border-radius:9px;border:1px solid #E5E7EB;' +
                'background:#fff;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;”>Cerrar sesión</button>' +
                '<button onclick=”swKeep()” style=”flex:2;padding:9px;border-radius:9px;border:none;' +
                'background:#2563EB;color:#fff;font-size:13px;font-weight:600;cursor:pointer;”>Continuar trabajando</button>' +
              '</div>' +
            '</div>';
        document.body.appendChild(elWarn);
        elCd  = document.getElementById('sw-cd');
        elBar = document.getElementById('sw-bar');

        /* --- modal definitivo --- */
        elExpired = document.createElement('div');
        elExpired.id = 'sw-expired';
        elExpired.setAttribute('style',
            'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
            'z-index:2147483647;background:rgba(0,0,0,.7);' +
            'align-items:center;justify-content:center;');
        elExpired.innerHTML =
            '<div style=”background:#fff;border-radius:16px;padding:32px;width:360px;max-width:92vw;' +
            'text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4);”>' +
              '<div style=”width:52px;height:52px;min-width:52px;border-radius:50%;background:#FEE2E2;' +
              'display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;line-height:1;”>🔒</div>' +
              '<p style=”font-size:16px;font-weight:700;color:#111827;margin:0 0 8px;”>Sesión cerrada</p>' +
              '<p style=”font-size:13px;color:#6B7280;margin:0 0 24px;”>Tu sesión expiró por inactividad.</p>' +
              '<button onclick=”swLogout()” style=”width:100%;padding:10px;border-radius:10px;border:none;' +
              'background:#2563EB;color:#fff;font-size:14px;font-weight:600;cursor:pointer;”>Iniciar sesión</button>' +
            '</div>';
        document.body.appendChild(elExpired);
    }

    function fmtCd(s) { var m=Math.floor(s/60),r=s%60; return m>0?m+' min '+r+' s':r+' s'; }

    function startTick() {
        clearInterval(tick);
        tick = setInterval(function() {
            elapsed++;
            if (elapsed >= LIMIT_S) {
                phase = 'expired'; clearInterval(tick);
                elWarn.style.display = 'none';
                elExpired.style.display = 'flex';
                autoOut = setTimeout(swLogout, 8000);
            } else if (elapsed >= WARN_AT) {
                if (phase !== 'warn') { phase = 'warn'; elWarn.style.display = 'flex'; }
                var cd = LIMIT_S - elapsed;
                if (elCd)  elCd.textContent = fmtCd(cd);
                if (elBar) elBar.style.width = Math.round(cd/WARN_S*100) + '%';
            }
        }, 1000);
    }

    window.swKeep = function() {
        fetch('/ping-session', { method:'POST', headers:{ 'X-CSRF-TOKEN': TOKEN(), 'Content-Type':'application/json' } }).catch(function(){});
        elapsed = 0; phase = 'idle';
        elWarn.style.display = 'none';
        if (elBar) elBar.style.width = '100%';
        startTick();
    };

    window.swLogout = function() {
        clearInterval(tick); clearTimeout(autoOut);
        var f = document.createElement('form'); f.method='POST'; f.action=LOGOUT;
        var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value=TOKEN();
        f.appendChild(t); document.body.appendChild(f); f.submit();
    };

    document.addEventListener('DOMContentLoaded', function() {
        buildModals();
        ['mousemove','keydown','click','scroll','touchstart'].forEach(function(ev) {
            document.addEventListener(ev, function() {
                if (phase === 'expired' || phase === 'warn') return;
                elapsed = 0;
            }, { passive: true });
        });
        startTick();
    });
})();
</script>

</body>
</html>

