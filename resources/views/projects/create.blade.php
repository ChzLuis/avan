<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo negocio — BIXO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full max-w-lg p-8">
        <div class="mb-6">
            <a href="{{ route('workspace') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Volver a mis negocios</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Crear nuevo negocio</h1>
        </div>
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label">Nombre *</label>
                <input type="text" name="name" class="input @error('name') border-red-400 @enderror"
                       value="{{ old('name') }}" placeholder="Mi Tienda, Barberia Juan..." required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Categoria / Rubro</label>
                <input type="text" name="category" class="input" value="{{ old('category') }}" placeholder="Tienda, Restaurante...">
            </div>
            <div>
                <label class="label">Descripcion</label>
                <textarea name="description" class="input resize-none" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Telefono</label>
                    <input type="text" name="phone" class="input" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="label">WhatsApp</label>
                    <input type="text" name="whatsapp" class="input" value="{{ old('whatsapp') }}">
                </div>
            </div>
            <div>
                <label class="label">Direccion</label>
                <input type="text" name="address" class="input" value="{{ old('address') }}">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary flex-1">Crear negocio</button>
                <a href="{{ route('workspace') }}" class="btn-secondary flex-1 text-center">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
