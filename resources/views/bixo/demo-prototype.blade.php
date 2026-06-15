<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXO - Design Prototype Demo</title>
    <style>
        :root {
            --color-bg-primary: #ffffff;
            --color-bg-secondary: #f8f9fb;
            --color-bg-tertiary: #f1f3f7;
            --color-text-primary: #1a1d23;
            --color-text-secondary: #565d73;
            --color-text-tertiary: #8a90a3;
            --color-text-quaternary: #c4c9d8;
            --color-border-primary: #e0e3eb;
            --color-border-secondary: #ececf1;
            --color-border-tertiary: #f5f5f8;
            --color-accent-primary: #4f46e5;
            --color-accent-primary-hover: #4338ca;
            --space-8: 8px;
            --space-12: 12px;
            --space-16: 16px;
            --space-20: 20px;
            --space-32: 32px;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            --duration-quick: 150ms;
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --z-sticky: 30;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f8f9fb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .navbar-figma {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .navbar-figma-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 100%;
            gap: 20px;
        }

        .navbar-logo-group {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon-box {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .logo-text-box {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .logo-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .logo-subtitle {
            font-size: 9px;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
        }

        .demo-container {
            margin-top: 64px;
            padding: 20px;
            display: flex;
            gap: 20px;
        }

        .demo-sidebar {
            width: 280px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 84px;
        }

        .demo-sidebar h3 {
            margin: 0 0 16px 0;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        .demo-screens {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .demo-screen-btn {
            padding: 12px 16px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            color: #475569;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
        }

        .demo-screen-btn:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #1e293b;
        }

        .demo-screen-btn.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-color: #6366f1;
            color: #6366f1;
            font-weight: 600;
        }

        .demo-content {
            flex: 1;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .demo-screen-content {
            width: 100%;
            min-height: 800px;
            border: none;
            display: block;
            overflow: auto;
        }

        .demo-info {
            padding: 20px;
            background: #f8f9fb;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }

        .screen-label {
            display: inline-block;
            padding: 4px 12px;
            background: #eef2ff;
            color: #6366f1;
            border-radius: 4px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        @media (max-width: 1024px) {
            .demo-container {
                flex-direction: column;
            }

            .demo-sidebar {
                width: 100%;
                position: static;
            }

            .demo-screens {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .demo-screen-btn {
                flex: 1;
                min-width: 150px;
            }
        }

        @media (max-width: 768px) {
            .demo-iframe {
                height: 600px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar-figma">
        <div class="navbar-figma-container">
            <a href="#" class="navbar-logo-group">
                <div class="logo-icon-box">B</div>
                <div class="logo-text-box">
                    <span class="logo-title">BIXO</span>
                    <span class="logo-subtitle">BUSINESS OS</span>
                </div>
            </a>
            <h2 style="margin: 0; flex: 1; text-align: center; font-size: 16px; color: #1e293b;">Design System Prototype</h2>
            <div style="width: 32px;"></div>
        </div>
    </nav>

    <!-- Demo Container -->
    <div class="demo-container">
        <!-- Sidebar: Screen Selector -->
        <div class="demo-sidebar">
            <h3>Screens</h3>
            <div class="demo-screens">
                <button class="demo-screen-btn active" onclick="showScreen('screen-1', this)">
                    1️⃣ Business Center
                </button>
                <button class="demo-screen-btn" onclick="showScreen('screen-2', this)">
                    2️⃣ Capability: Operar
                </button>
                <button class="demo-screen-btn" onclick="showScreen('screen-3', this)">
                    3️⃣ Tool: Inventario
                </button>
                <button class="demo-screen-btn" onclick="showScreen('screen-4', this)">
                    4️⃣ Object: Producto
                </button>
                <button class="demo-screen-btn" onclick="showScreen('screen-5', this)">
                    5️⃣ Business Switch
                </button>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

            <h3>Información</h3>
            <p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                Navega entre los 5 screens para ver cómo funciona la navegación espacial y contextual de BIXO.
            </p>
        </div>

        <!-- Main Content: Screen Display -->
        <div class="demo-content">
            <div id="screen-1" class="screen-wrapper" style="display: block;">
                <div style="padding: 20px; background: #f8f9fb; border-bottom: 1px solid #e2e8f0;">
                    <span class="screen-label">SCREEN 1</span>
                    <h3 style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">El Negocio es el Centro</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">La pantalla principal muestra el negocio como protagonista, con 5 capacidades principales.</p>
                </div>
                <div class="demo-screen-content">@include('bixo.screen-1-business-center')</div>
            </div>

            <div id="screen-2" class="screen-wrapper" style="display: none;">
                <div style="padding: 20px; background: #f8f9fb; border-bottom: 1px solid #e2e8f0;">
                    <span class="screen-label">SCREEN 2</span>
                    <h3 style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">Profundidad: Capacidad Operar</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Dentro de una capacidad, ves sus herramientas. Contexto visible en breadcrumb.</p>
                </div>
                <div class="demo-screen-content">@include('bixo.screen-2-capability-operar')</div>
            </div>

            <div id="screen-3" class="screen-wrapper" style="display: none;">
                <div style="padding: 20px; background: #f8f9fb; border-bottom: 1px solid #e2e8f0;">
                    <span class="screen-label">SCREEN 3</span>
                    <h3 style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">Contenido: Herramienta Inventario</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">El contenido es protagonista. Navegación contextual se adapta (Filtrar, Ver).</p>
                </div>
                <div class="demo-screen-content">@include('bixo.screen-3-tool-inventario')</div>
            </div>

            <div id="screen-4" class="screen-wrapper" style="display: none;">
                <div style="padding: 20px; background: #f8f9fb; border-bottom: 1px solid #e2e8f0;">
                    <span class="screen-label">SCREEN 4</span>
                    <h3 style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">Deep Focus: Objeto Producto</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Cuando haces clic en un item, todo cambia. Focus total en ese objeto.</p>
                </div>
                <div class="demo-screen-content">@include('bixo.screen-4-object-producto')</div>
            </div>

            <div id="screen-5" class="screen-wrapper" style="display: none;">
                <div style="padding: 20px; background: #f8f9fb; border-bottom: 1px solid #e2e8f0;">
                    <span class="screen-label">SCREEN 5</span>
                    <h3 style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">Cambio Consciente: Business Switch</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Cambiar de negocio es un acto consciente, no un dropdown perdido.</p>
                </div>
                <div class="demo-screen-content">@include('bixo.screen-5-business-switch')</div>
            </div>

            <div class="demo-info">
                <strong>Instrucciones:</strong> Haz clic en cualquier capability, herramienta u objeto para navegar. Usa el botón "Atrás" para volver. Presiona ESC para salir de cualquier vista.
            </div>
        </div>
    </div>

    <script>
        function showScreen(screenId, button) {
            // Hide all screens
            document.querySelectorAll('.screen-wrapper').forEach(screen => {
                screen.style.display = 'none';
            });

            // Remove active class from all buttons
            document.querySelectorAll('.demo-screen-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected screen
            document.getElementById(screenId).style.display = 'block';

            // Add active class to clicked button
            button.classList.add('active');
        }

        // Keyboard shortcut: Press number to go to screen
        document.addEventListener('keydown', (e) => {
            const screens = ['screen-1', 'screen-2', 'screen-3', 'screen-4', 'screen-5'];
            const num = parseInt(e.key);
            if (num >= 1 && num <= 5) {
                const screenId = screens[num - 1];
                const button = Array.from(document.querySelectorAll('.demo-screen-btn'))[num - 1];
                showScreen(screenId, button);
            }
        });
    </script>
</body>
</html>
