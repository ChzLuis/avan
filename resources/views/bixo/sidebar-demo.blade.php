<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXO Sidebar Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f8f9fb;
        }

        .container {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="container">
        @include('layouts.sidebar-bixo')

        <div style="flex: 1; margin-left: 280px; padding: 40px;">
            <h1>BIXO Sidebar</h1>
            <p>El sidebar ahora está en la izquierda exactamente como en tu imagen.</p>
            <p>Contiene:</p>
            <ul style="margin-left: 20px; margin-top: 20px;">
                <li>Logo BIXO</li>
                <li>Selector de negocio (Ferretería GABDE)</li>
                <li>Búsqueda</li>
                <li>Menú por secciones (Panel, Catálogo, Comunicaciones, Logística)</li>
                <li>BIXO Copilot</li>
                <li>Usuario (Footer)</li>
            </ul>
        </div>
    </div>
</body>
</html>
