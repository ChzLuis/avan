<x-guest-layout>
<div class="text-center py-8">
    <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
    </div>
    <h2 class="text-lg font-semibold text-gray-800 mb-2">Sin acceso a negocios</h2>
    <p class="text-sm text-gray-500 mb-6">
        Tu cuenta aún no está asignada a ningún negocio.<br>
        Contacta al administrador para que te agregue.
    </p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition">
            Cerrar sesión
        </button>
    </form>
</div>
</x-guest-layout>
