<x-admin-layout title="Configuración">

    <div class="mb-6">
        <h2 class="text-lg font-bold text-white">Configuración del sistema</h2>
        <p class="text-sm text-gray-500 mt-0.5">Ajustes globales de la plataforma BIXO</p>
    </div>

    <div class="max-w-lg rounded-2xl border p-6" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Nombre de la aplicación</label>
                <input type="text" name="app_name" value="{{ config('app.name') }}"
                       placeholder="BIXO"
                       class="w-full px-4 py-2.5 rounded-xl text-sm text-gray-300 outline-none transition-colors"
                       style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs text-gray-600 mt-1">Aparece en el título del navegador y notificaciones del sistema.</p>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-medium text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    Guardar configuración
                </button>
            </div>
        </form>
    </div>

    {{-- Info del sistema --}}
    <div class="max-w-lg mt-5 rounded-2xl border p-5" style="background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.06);">
        <h3 class="text-sm font-semibold text-white mb-3">Info del sistema</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Laravel</span>
                <span class="text-gray-300">{{ app()->version() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">PHP</span>
                <span class="text-gray-300">{{ PHP_VERSION }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Entorno</span>
                <span class="text-gray-300">{{ config('app.env') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">URL</span>
                <span class="text-gray-300">{{ config('app.url') }}</span>
            </div>
        </div>
    </div>

</x-admin-layout>
