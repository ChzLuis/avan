<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXO - Aplicación Completa</title>
    <style>
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
            background: #ffffff;
            color: #1a1d23;
        }

        main {
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 220px);
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #f0f1f5;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            flex-shrink: 0;
            gap: 40px;
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
            color: #8a90a3;
        }

        .navbar-breadcrumb strong {
            color: #1a1d23;
            font-weight: 500;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-search {
            padding: 6px 10px;
            border: 1px solid #f0f1f5;
            border-radius: 5px;
            font-size: 12px;
            width: 160px;
            color: #1a1d23;
            background: #fafbfc;
            font-family: inherit;
        }

        .navbar-search::placeholder {
            color: #cbd5e1;
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
            color: #565d73;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .navbar-btn:hover {
            color: #1a1d23;
        }

        .page-content {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 40px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 40px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0;
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 13px;
            color: #8a90a3;
            margin: 8px 0 0 0;
        }

        .btn-primary {
            background: #5b51e0;
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
            background: #4c47d0;
        }

        .business-section {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }

        .business-card {
            padding: 24px;
            background: #f8f9fb;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .business-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .business-avatar {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #5b51e0 0%, #8b5cf6 100%);
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
            color: #1a1d23;
            margin: 0;
        }

        .business-info p {
            font-size: 11px;
            color: #8a90a3;
            margin: 2px 0 0 0;
        }

        .business-stats {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            font-size: 11px;
            color: #8a90a3;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stat-value {
            font-size: 13px;
            font-weight: 700;
            color: #1a1d23;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #1a1d23;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .form-field-label {
            font-size: 12px;
            color: #8a90a3;
        }

        .form-input {
            padding: 0;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            background: transparent;
            font-size: 14px;
            color: #1a1d23;
            font-family: inherit;
            padding-bottom: 6px;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-bottom-color: #5b51e0;
        }

        .form-input::placeholder {
            color: #cbd5e1;
        }

        .form-select {
            padding: 0;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            background: transparent;
            font-size: 14px;
            color: #1a1d23;
            font-family: inherit;
            padding-bottom: 6px;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-bottom-color: #5b51e0;
        }

        .tabs {
            display: flex;
            gap: 32px;
            border-bottom: 1px solid #f0f1f5;
            margin-bottom: 32px;
        }

        .tab {
            padding: 12px 0;
            border: none;
            background: transparent;
            color: #8a90a3;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .tab:hover {
            color: #1a1d23;
        }

        .tab.active {
            color: #5b51e0;
            border-bottom-color: #5b51e0;
        }

        @media (max-width: 1024px) {
            main {
                margin-left: 0;
                width: 100%;
            }

            .business-section {
                grid-template-columns: 1fr;
            }
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

            <!-- BUSINESS SECTION: Sidebar + Form -->
            <div class="business-section">
                <!-- SIDEBAR: Business Card -->
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

                <!-- MAIN: Form Content -->
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

                        <!-- FORM FIELDS -->
                        <div class="form-group">
                            <h3 class="form-label">Identidad</h3>
                            <div class="form-field">
                                <label class="form-field-label">Nombre del negocio</label>
                                <input type="text" value="Ferretería GABDE" class="form-input">
                            </div>
                            <div class="form-field" style="margin-top: 24px;">
                                <label class="form-field-label">URL pública (slug)</label>
                                <input type="text" value="ferreteria-demo" class="form-input">
                            </div>
                            <div class="form-field" style="margin-top: 24px;">
                                <label class="form-field-label">Categoría / Rubro</label>
                                <select class="form-select">
                                    <option>Ferretería</option>
                                    <option>Tienda</option>
                                    <option>Restaurante</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 40px;">
                            <h3 class="form-label">Dominio</h3>
                            <div class="form-field">
                                <label class="form-field-label">Dominio personalizado</label>
                                <input type="text" placeholder="gestion.suegocio.com" class="form-input">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 40px;">
                            <h3 class="form-label">Descripción</h3>
                            <div class="form-field">
                                <label class="form-field-label">Describe brevemente este negocio</label>
                                <textarea style="padding: 0; border: none; border-bottom: 1px solid #e5e7eb; background: transparent; font-size: 14px; color: #1a1d23; font-family: inherit; padding-bottom: 6px; resize: none; min-height: 60px;" placeholder="Aparece en buscadores y en la cabecera de la tienda"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
