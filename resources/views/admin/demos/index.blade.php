<x-admin-layout>
<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Demos solicitadas</h1>
      <p class="text-sm text-gray-500">{{ $demos->total() }} demos registradas</p>
    </div>
    <div class="flex gap-2 text-sm">
      <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
        {{ $demos->where('status','active')->count() }} activas
      </span>
      <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full font-medium">
        {{ $demos->where('status','expired')->count() }} expiradas
      </span>
    </div>
  </div>

  @if(session('success'))
  <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
    {{ session('success') }}
  </div>
  @endif

  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
          <th class="text-left px-4 py-3 font-600 text-gray-600">#</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Negocio</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Rubro</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Contacto</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Estado</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Expira</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Módulos</th>
          <th class="text-left px-4 py-3 font-600 text-gray-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($demos as $demo)
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 text-gray-400">{{ $demo->id }}</td>
          <td class="px-4 py-3">
            <div class="font-600 text-gray-900">{{ $demo->business_name }}</div>
            @if($demo->project)
            <a href="{{ route('dashboard', ['project' => $demo->project->id]) }}"
               class="text-xs text-indigo-600 hover:underline" target="_blank">
              Ver proyecto →
            </a>
            @endif
          </td>
          <td class="px-4 py-3">
            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full text-xs font-medium capitalize">
              {{ $demo->rubro }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="text-gray-900">{{ $demo->contact_name }}</div>
            <div class="text-gray-400 text-xs">{{ $demo->email }}</div>
            @if($demo->phone)<div class="text-gray-400 text-xs">{{ $demo->phone }}</div>@endif
          </td>
          <td class="px-4 py-3">
            @php
              $colors = ['active'=>'green','pending'=>'amber','expired'=>'gray','cancelled'=>'red'];
              $labels = ['active'=>'Activa','pending'=>'Pendiente','expired'=>'Expirada','cancelled'=>'Cancelada'];
              $c = $colors[$demo->status] ?? 'gray';
            @endphp
            <span class="bg-{{ $c }}-100 text-{{ $c }}-700 px-2 py-0.5 rounded-full text-xs font-medium">
              {{ $labels[$demo->status] ?? $demo->status }}
            </span>
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">
            @if($demo->expires_at)
              {{ $demo->expires_at->format('d/m/Y') }}
              @if($demo->expires_at->isPast())
                <span class="text-red-500">(vencida)</span>
              @elseif($demo->expires_at->diffInDays() <= 3)
                <span class="text-amber-500">({{ $demo->expires_at->diffInDays() }}d)</span>
              @endif
            @else —
            @endif
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">
            {{ count($demo->features_selected ?? []) }} módulos
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              @if(in_array($demo->status, ['active','pending']))
              <form action="{{ route('admin.demos.cancel', $demo) }}" method="POST" onsubmit="return confirm('¿Cancelar esta demo?')">
                @csrf
                <button class="text-xs text-red-500 hover:text-red-700 font-medium">Cancelar</button>
              </form>
              @endif
              @if(in_array($demo->status, ['active','expired']))
              <form action="{{ route('admin.demos.extend', $demo) }}" method="POST">
                @csrf
                <button class="text-xs text-indigo-500 hover:text-indigo-700 font-medium">+14 días</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" class="px-4 py-10 text-center text-gray-400">No hay demos registradas aún</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $demos->links() }}</div>
</div>
</x-admin-layout>
