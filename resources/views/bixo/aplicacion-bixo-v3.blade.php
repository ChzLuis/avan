<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXO – Sistema Operativo para Negocios</title>
    <style>
        :root {
            --grafito-dark: #13161B;
            --grafito: #1B1F27;
            --accent: #6D5DF6;
            --accent-light: #8B7BFF;
            --text-primary: #F7F8FA;
            --text-secondary: #8B92A7;
            --text-tertiary: #6B7280;
            --surface-hover: #242A35;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: var(--grafito-dark);
            color: var(--text-primary);
        }

        main {
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 220px);
        }

        /* ════════════════════════════════════════════════════════════════
           NAVBAR: Ultra minimalista
           ════════════════════════════════════════════════════════════════ */

        .navbar {
            background: var(--grafito);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            flex-shrink: 0;
            gap: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .navbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .navbar-breadcrumb strong {
            color: var(--text-primary);
            font-weight: 500;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .navbar-search {
            padding: 6px 12px;
            border: none;
            background: var(--surface-hover);
            border-radius: 5px;
            font-size: 12px;
            width: 160px;
            color: var(--text-primary);
            font-family: inherit;
        }

        .navbar-search::placeholder {
            color: var(--text-secondary);
        }

        .navbar-btn {
            width: 28px;
            height: 28px;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .navbar-btn:hover {
            color: var(--text-primary);
        }

        /* ════════════════════════════════════════════════════════════════
           PAGE CONTENT: Espacioso, sin decoración
           ════════════════════════════════════════════════════════════════ */

        .page-content {
            flex: 1;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            gap: 48px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 40px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.1;
            letter-spacing: -0.01em;
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 12px 0 0 0;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-primary:hover {
            background: var(--accent-light);
        }

        /* ════════════════════════════════════════════════════════════════
           LAYOUT: Sidebar + Main (sin grillas visuales)
           ════════════════════════════════════════════════════════════════ */

        .business-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 48px;
        }

        /* ════════════════════════════════════════════════════════════════
           BUSINESS CARD: Minimalista (solo tipografía)
           ════════════════════════════════════════════════════════════════ */

        .business-card {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .business-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .business-avatar {
            width: 56px;
            height: 56px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .business-info h2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .business-info p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 4px 0 0 0;
        }

        /* STATS: Tipografía pura, sin cajas */

        .business-stats {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 16px;
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ════════════════════════════════════════════════════════════════
           FORM: Ultra limpio (solo líneas, tipografía)
           ════════════════════════════════════════════════════════════════ */

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 48px;
        }

        /* TABS: Sin cajas, solo línea inferior */

        .tabs {
            display: flex;
            gap: 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 16px;
            margin-bottom: 32px;
        }

        .tab {
            padding: 0;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            position: relative;
            top: 16px;
        }

        .tab:hover {
            color: var(--text-primary);
        }

        .tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* FORM GROUPS: Solo estructura de tipografía */

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 0;
            padding-bottom: 8px;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            background: transparent;
            font-size: 14px;
            color: var(--text-primary);
            font-family: inherit;
            transition: border-color 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-bottom-color: var(--accent);
        }

        .form-input::placeholder,
        .form-select::placeholder {
            color: var(--text-tertiary);
        }

        .form-textarea {
            resize: none;
            min-height: 60px;
        }

        /* FIELD SPACING: Grupos verticales claros */

        .form-field + .form-field {
            margin-top: 28px;
        }

        .form-group + .form-group {
            margin-top: 48px;
        }

        /* ════════════════════════════════════════════════════════════════
           RESPONSIVE
           ════════════════════════════════════════════════════════════════ */

        @media (max-width: 1024px) {
            main {
                margin-left: 0;
                width: 100%;
            }

            .business-layout {
                grid-template-columns: 1fr;
            }

            .page-content {
                padding: 32px;
                gap: 32px;
            }
        }

        /* ════════════════════════════════════════════════════════════════
           SCROLLBAR: Invisible pero funcional
           ════════════════════════════════════════════════════════════════ */

        .page-content::-webkit-scrollbar {
            width: 6px;
        }

        .page-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .page-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .page-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    @include('layouts.sidebar-bixo-v2')

    <main>
        <!-- NAVBAR -->
        <div class="navbar">
            <div class="navbar-left">
                <div class="navbar-breadcrumb">
                    <span>Configuración</span>
                    <span>/</span>
                    <strong>Negocios</strong>
                </div>
            </div>
            <div class="navbar-right">
                <input type="text" placeholder="Buscar..." class="navbar-search">
                <button class="navbar-btn">🔔</button>
                <button class="navbar-btn">⚙️</button>
                <button class="navbar-btn">👤</button>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="page-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Negocios</h1>
                    <p class="page-subtitle">5 negocios registrados en tu organización</p>
                </div>
                <button class="btn-primary">+ Nuevo negocio</button>
            </div>

            <!-- LAYOUT: Sidebar + Form (sin cajas visuales) -->
            <div class="business-layout">
                <!-- LEFT: Business Card (minimalista) -->
                <div class="business-card">
                    <div class="business-header">
                        <div class="business-avatar">FE</div>
                        <div class="business-info">
                            <h2>Ferretería GABDE</h2>
                            <p>/ferreteria-demo</p>
                        </div>
                    </div>

                    <div class="business-stats">
                        <div class="stat-row">
                            <span class="stat-label">Productos</span>
                            <span class="stat-value">248</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Órdenes</span>
                            <span class="stat-value">1,320</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Ingresos</span>
                            <span class="stat-value">S/ 84.2k</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Última edición</span>
                            <span class="stat-value">Hace 3h</span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Form (solo tipografía y líneas) -->
                <div class="form-section">
                    <!-- TABS -->
                    <div>
                        <div class="tabs">
                            <button class="tab active">Datos</button>
                            <button class="tab">Adicionales</button>
                            <button class="tab">Envío</button>
                            <button class="tab">Cupones</button>
                            <button class="tab">Facturación</button>
                            <button class="tab">WhatsApp</button>
                        </div>

                        <!-- FORM CONTENT -->
                        <div class="form-group">
                            <h3 class="form-group-title">Identidad</h3>

                            <div class="form-field">
                                <label class="form-field-label">Nombre del negocio</label>
                                <input type="text" value="Ferretería GABDE" class="form-input">
                            </div>

                            <div class="form-field">
                                <label class="form-field-label">URL pública (slug)</label>
                                <input type="text" value="ferreteria-demo" class="form-input">
                            </div>

                            <div class="form-field">
                                <label class="form-field-label">Categoría / Rubro</label>
                                <select class="form-select">
                                    <option>Ferretería</option>
                                    <option>Tienda</option>
                                    <option>Restaurante</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <h3 class="form-group-title">Dominio</h3>

                            <div class="form-field">
                                <label class="form-field-label">Dominio personalizado</label>
                                <input type="text" placeholder="gestion.suegocio.com" class="form-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <h3 class="form-group-title">Descripción</h3>

                            <div class="form-field">
                                <label class="form-field-label">Describe brevemente este negocio</label>
                                <textarea class="form-textarea" placeholder="Aparece en buscadores y en la cabecera de la tienda"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
