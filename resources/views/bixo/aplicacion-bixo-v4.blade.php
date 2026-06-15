<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXO – Sistema Operativo para Negocios</title>
    <style>
        :root {
            --bixo-primary: #3348FF;
            --bixo-light: #4F5DFF;
            --grafito-dark: #1E293B;
            --grafito: #FFFFFF;
            --grafito-light: #F8FAFC;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --text-tertiary: #94A3B8;
            --border-subtle: rgba(0, 0, 0, 0.08);
            --status-active: #00B26B;
            --status-pending: #F59E0B;
            --status-error: #EF4444;
            --status-info: #3B82F6;
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
            background: #FFFFFF;
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
           PAGE CONTENT
           ════════════════════════════════════════════════════════════════ */

        .page-content {
            flex: 1;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            gap: 48px;
            overflow-y: auto;
            background: #FFFFFF;
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
            background: var(--bixo-primary);
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
            background: var(--bixo-light);
        }

        /* ════════════════════════════════════════════════════════════════
           FIRMA 1: NEGOCIO (Bloque tipográfico con separadores)
           ════════════════════════════════════════════════════════════════ */

        .business-block {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .business-section {
            padding: 24px 0;
            border-bottom: 1px solid var(--border-subtle);
        }

        .business-section:last-child {
            border-bottom: none;
        }

        .business-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .business-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: -0.01em;
        }

        .business-slug {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .business-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            font-size: 12px;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--status-active);
        }

        .status-indicator.pending {
            background: var(--status-pending);
        }

        .status-indicator.error {
            background: var(--status-error);
        }

        .business-activity {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* FIRMA 2: MÉTRICAS (Columnas tipográficas) */

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            padding-top: 24px;
        }

        .metric {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .metric-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* ════════════════════════════════════════════════════════════════
           LAYOUT: Sidebar + Form
           ════════════════════════════════════════════════════════════════ */

        .content-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 48px;
        }

        /* ════════════════════════════════════════════════════════════════
           FIRMA 4: FORMULARIOS (Notion-style)
           ════════════════════════════════════════════════════════════════ */

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 48px;
        }

        .tabs {
            display: flex;
            gap: 32px;
            border-bottom: 1px solid var(--border-subtle);
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
            color: var(--bixo-primary);
            border-bottom-color: var(--bixo-primary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 24px;
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
            gap: 8px;
        }

        .form-field-label {
            font-size: 12px;
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 12px 0;
            border: none;
            border-bottom: 1px solid var(--border-subtle);
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
            border-bottom-color: var(--bixo-primary);
        }

        .form-input::placeholder,
        .form-select::placeholder {
            color: var(--text-tertiary);
        }

        .form-textarea {
            resize: none;
            min-height: 80px;
        }

        .form-field + .form-field {
            margin-top: 32px;
        }

        .form-group + .form-group {
            margin-top: 48px;
        }

        /* ════════════════════════════════════════════════════════════════
           FIRMA 5: ESTADOS (Sistema consistente)
           ════════════════════════════════════════════════════════════════ */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge.active {
            background: rgba(0, 178, 107, 0.15);
            color: var(--status-active);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.15);
            color: var(--status-pending);
        }

        .status-badge.error {
            background: rgba(239, 68, 68, 0.15);
            color: var(--status-error);
        }

        .status-badge.info {
            background: rgba(59, 130, 246, 0.15);
            color: var(--status-info);
        }

        /* ════════════════════════════════════════════════════════════════
           RESPONSIVE
           ════════════════════════════════════════════════════════════════ */

        @media (max-width: 1024px) {
            main {
                margin-left: 0;
                width: 100%;
            }

            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-content {
                padding: 32px;
                gap: 32px;
            }
        }

        @media (max-width: 640px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        /* SCROLLBAR */

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

        /* ════════════════════════════════════════════════════════════════
           SIDEBAR: Exacto al standalone.html
           ════════════════════════════════════════════════════════════════ */

        .sidebar-bixo {
            width: 220px;
            height: 100vh;
            background: #FFFFFF;
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            font-family: inherit;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            overflow: hidden;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-subtle);
            flex-shrink: 0;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--bixo-primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 18px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .logo-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .logo-subtitle {
            font-size: 9px;
            color: var(--text-tertiary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .collapse-btn {
            width: 20px;
            height: 20px;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
        }

        .business-selector {
            padding: 12px 12px;
            border-bottom: 1px solid var(--border-subtle);
            flex-shrink: 0;
        }

        .business-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--grafito-light);
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }

        .business-avatar {
            width: 36px;
            height: 36px;
            background: var(--bixo-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .business-info {
            flex: 1;
            min-width: 0;
        }

        .business-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .business-type {
            font-size: 12px;
            color: var(--text-tertiary);
            margin-top: 2px;
        }

        .business-menu-btn {
            width: 20px;
            height: 20px;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-search {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            margin: 12px 12px;
            background: var(--grafito-light);
            border: 1px solid var(--border-subtle);
            border-radius: 6px;
            flex-shrink: 0;
        }

        .search-icon {
            width: 16px;
            height: 16px;
            color: var(--text-tertiary);
            flex-shrink: 0;
        }

        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            font-size: 13px;
            color: var(--text-primary);
        }

        .search-input::placeholder {
            color: var(--text-tertiary);
        }

        .search-shortcut {
            font-size: 10px;
            color: var(--text-tertiary);
            background: white;
            padding: 2px 4px;
            border-radius: 3px;
            border: 1px solid var(--border-subtle);
            flex-shrink: 0;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        .menu-section {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 12px;
        }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 0 8px;
        }

        .item-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .menu-item:hover {
            background: var(--grafito-light);
            color: var(--text-primary);
        }

        .menu-item.active {
            background: rgba(51, 72, 255, 0.1);
            color: var(--bixo-primary);
        }

        .item-badge {
            margin-left: auto;
            background: var(--bixo-primary);
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 700;
        }

        .copilot-section {
            padding: 0 12px 12px 12px;
            flex-shrink: 0;
        }

        .copilot-card {
            display: flex;
            gap: 10px;
            padding: 12px;
            background: var(--grafito-light);
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            margin-bottom: 8px;
        }

        .copilot-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .copilot-content {
            flex: 1;
        }

        .copilot-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .copilot-desc {
            font-size: 11px;
            color: var(--text-tertiary);
            margin-top: 2px;
            line-height: 1.3;
        }

        .copilot-btn {
            width: 100%;
            padding: 10px;
            background: #1E293B;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copilot-btn:hover {
            background: #0F172A;
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border-subtle);
            flex-shrink: 0;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--grafito-light);
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--bixo-primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 11px;
            color: var(--text-tertiary);
            margin-top: 2px;
        }

        .user-menu-btn {
            width: 20px;
            height: 20px;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 16px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR BIXO: Exacto al standalone.html -->
    <aside class="sidebar-bixo">
        <!-- HEADER: Logo BIXO -->
        <div class="sidebar-header">
            <div class="logo-group">
                <div class="logo-icon">B</div>
                <div class="logo-text">
                    <div class="logo-title">BIXO</div>
                    <div class="logo-subtitle">BUSINESS OS</div>
                </div>
            </div>
            <button class="collapse-btn">‹</button>
        </div>

        <!-- BUSINESS SELECTOR -->
        <div class="business-selector">
            <div class="business-card">
                <div class="business-avatar">FE</div>
                <div class="business-info">
                    <div class="business-name">Ferretería GABDE</div>
                    <div class="business-type">Espacio de trabajo</div>
                </div>
                <button class="business-menu-btn">∴</button>
            </div>
        </div>

        <!-- SEARCH -->
        <div class="sidebar-search">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" placeholder="Buscar o ir a..." class="search-input" />
            <span class="search-shortcut">⌘K</span>
        </div>

        <!-- MENU -->
        <div class="sidebar-menu">
            <!-- PANEL -->
            <div class="menu-section">
                <div class="section-label">PANEL</div>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 16l4-4m0 0l4 4m-4-4v4"/>
                    </svg>
                    <span>Inicio</span>
                </a>
                <a href="#" class="menu-item active">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Negocios</span>
                    <span class="item-badge">5</span>
                </a>
            </div>

            <!-- CATÁLOGO -->
            <div class="menu-section">
                <div class="section-label">CATÁLOGO</div>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m0 0v10l8 4"/>
                    </svg>
                    <span>Productos</span>
                </a>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span>Categorías</span>
                </a>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 105.646 5.646"/>
                    </svg>
                    <span>Inventario</span>
                </a>
            </div>

            <!-- COMUNICACIONES -->
            <div class="menu-section">
                <div class="section-label">COMUNICACIONES</div>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Canales WhatsApp</span>
                </a>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    <span>Campañas</span>
                </a>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Plantillas</span>
                </a>
            </div>

            <!-- LOGÍSTICA -->
            <div class="menu-section">
                <div class="section-label">LOGÍSTICA</div>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 001-1v-3.5a1 1 0 011-1H20a1 1 0 011 1v3.5a1 1 0 01-1 1m-13 0h6"/>
                    </svg>
                    <span>Envíos</span>
                </a>
                <a href="#" class="menu-item">
                    <svg class="item-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span>Zonas de reparto</span>
                </a>
            </div>

            <!-- BIXO COPILOT -->
            <div class="copilot-section">
                <div class="copilot-card">
                    <div class="copilot-icon">✨</div>
                    <div class="copilot-content">
                        <div class="copilot-title">BIXO Copilot</div>
                        <div class="copilot-desc">Automatiza catálogo y respuestas de WhatsApp con IA.</div>
                    </div>
                </div>
                <button class="copilot-btn">Activar</button>
            </div>
        </div>

        <!-- FOOTER: Usuario -->
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">AD</div>
                <div class="user-info">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Superadmin</div>
                </div>
                <button class="user-menu-btn">›</button>
            </div>
        </div>
    </aside>

    <main>
        <!-- NAVBAR: Mínimo (solo branding opcional) -->
        <!-- Navbar eliminado: el contenido ocupa todo el espacio -->

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

            <!-- FIRMA 1: BLOQUE DEL NEGOCIO (Separadores horizontales) -->
            <div class="business-block">
                <div class="business-section">
                    <div class="business-header">
                        <div class="business-name">Ferretería GABDE</div>
                        <div class="business-slug">ferreteria-demo</div>
                    </div>
                    <div class="business-status">
                        <span class="status-indicator"></span>
                        <span>Activo</span>
                    </div>
                    <div class="business-activity">Última actividad hace 3 horas</div>
                </div>

                <!-- FIRMA 2: MÉTRICAS (Columnas tipográficas) -->
                <div class="business-section">
                    <div class="metrics-grid">
                        <div class="metric">
                            <div class="metric-value">248</div>
                            <div class="metric-label">Productos</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">1,320</div>
                            <div class="metric-label">Ventas</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">S/84.2K</div>
                            <div class="metric-label">Ingresos</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">3h</div>
                            <div class="metric-label">Actividad</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FIRMA 4: FORMULARIOS -->
            <div class="content-layout">
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

                        <!-- FIRMA 5: ESTADOS (Sistema consistente) -->
                        <div class="form-group">
                            <h3 class="form-group-title">Estado del negocio</h3>
                            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                                <span class="status-badge active">● Activo</span>
                                <span class="status-badge pending">◐ Pendiente</span>
                                <span class="status-badge error">✕ Error</span>
                                <span class="status-badge info">ⓘ Información</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
