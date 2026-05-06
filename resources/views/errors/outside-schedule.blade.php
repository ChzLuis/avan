<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso restringido</title>
    @vite(['resources/css/app.css'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center font-sans">
    <div class="text-center max-w-sm px-6">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">Fuera de horario</h1>
        <p class="text-gray-500 text-sm mb-1">Tu acceso está habilitado entre:</p>
        <p class="text-lg font-semibold text-gray-800">{{ $work_start }} — {{ $work_end }}</p>
        <p class="text-gray-400 text-xs mt-4">Si crees que hay un error, contacta a tu administrador.</p>
    </div>
</body>
</html>
