<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar negocio — BIXO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full max-w-lg p-8">
        <div class="mb-6">
            <a href="{{ route('workspace') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Volver a mis negocios</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Editar negocio</h1>
        </div>
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="label">Nombre *</label>
                <input type="text" name="name" class="input" value="{{ old('name', $project->name) }}" required>
            </div>
            <div>
                <label class="label">Categoria</label>
                <input type="text" name="category" class="input" value="{{ old('category', $project->category) }}">
            </div>
            <div>
                <label class="label">Descripcion</label>
                <textarea name="description" class="input resize-none" rows="3">{{ old('description', $project->description) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Telefono</label>
                    <input type="text" name="phone" class="input" value="{{ old('phone', $project->phone) }}">
                </div>
                <div>
                    <label class="label">WhatsApp</label>
                    <input type="text" name="whatsapp" class="input" value="{{ old('whatsapp', $project->whatsapp) }}">
                </div>
            </div>
            <div>
                <label class="label">Direccion</label>
                <input type="text" name="address" class="input" value="{{ old('address', $project->address) }}">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ $project->is_active ? 'checked' : '' }}>
                <label for="is_active" class="text-sm text-gray-700">Negocio activo</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary flex-1">Guardar cambios</button>
                <a href="{{ route('workspace') }}" class="btn-secondary flex-1 text-center">Cancelar</a>
            </div>
        </form>
        <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-4"
              onsubmit="return confirm('Eliminar este negocio? Esta accion no se puede deshacer.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger w-full">Eliminar negocio</button>
        </form>
    </div>
</div>
</body>
</html>
