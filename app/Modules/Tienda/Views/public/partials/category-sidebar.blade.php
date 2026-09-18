{{--
    Sidebar de categorías jerárquico (padre → hijos).
    Variables requeridas: $categories (colección con children cargados), $settings
    Variable Alpine requerida: filterCat (string con el id de categoría activa)
--}}

@php
    $totalProducts = $categories->sum(fn($c) =>
        $c->products->count() + $c->children->sum(fn($s) => $s->products->count())
    );
@endphp

<aside class="w-[220px] flex-shrink-0 hidden lg:block">
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

    {{-- Header --}}
    <div class="btn-p px-4 py-3 flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <span class="font-bold text-sm">CATEGORÍAS</span>
    </div>

    <nav class="p-2" aria-label="Categorías del catálogo">

      {{-- Todos --}}
      <button @click="filterCat=''; filterParent=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='' ? 'active' : ''"
              class="sidebar-cat text-gray-700 font-medium">
        <svg class="w-4 h-4 flex-shrink-0 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        <span>Todo el catálogo</span>
        <span class="ml-auto text-xs opacity-50 font-normal">{{ $totalProducts }}</span>
      </button>

      {{-- Árbol padre → hijos --}}
      @foreach($categories as $cat)
        @php
          $catTotal = $cat->products->count() + $cat->children->sum(fn($s) => $s->products->count());
          $hasChildren = $cat->children->isNotEmpty();
          $childIds = $cat->children->pluck('id')->map(fn($id) => "'".$id."'")->join(',');
        @endphp

        {{-- Categoría padre --}}
        <div x-data="{ open: false }">
          <div class="flex items-center group">

            {{-- Toggle expand si tiene hijos --}}
            @if($hasChildren)
            <button @click="open=!open"
                    class="w-6 h-8 flex items-center justify-center flex-shrink-0 text-gray-300 hover:text-gray-500 transition">
              <svg class="w-3 h-3 transition-transform duration-150" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
            @else
            <div class="w-6"></div>
            @endif

            {{-- Botón padre --}}
            <button @click="filterCat='{{ $cat->id }}'; filterParent='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                    :class="filterParent==='{{ $cat->id }}' ? 'active' : ''"
                    class="sidebar-cat flex-1 text-gray-700 font-semibold text-xs uppercase tracking-wide px-2">
              <span class="flex-1 truncate">{{ $cat->name }}</span>
              <span class="text-xs opacity-50 font-normal flex-shrink-0">{{ $catTotal }}</span>
            </button>
          </div>

          {{-- Hijos --}}
          @if($hasChildren)
          <div x-show="open" x-collapse class="pl-6">
            @foreach($cat->children as $sub)
            <button @click="filterCat='{{ $sub->id }}'; filterParent='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                    :class="filterCat==='{{ $sub->id }}' ? 'active' : ''"
                    class="sidebar-cat text-gray-500 text-sm pl-2">
              <span class="w-1.5 h-1.5 rounded-full bg-current opacity-40 flex-shrink-0"></span>
              <span class="flex-1 truncate">{{ $sub->name }}</span>
              <span class="text-xs opacity-40 font-normal flex-shrink-0">{{ $sub->products->count() }}</span>
            </button>
            @endforeach
          </div>
          @endif

        </div>
      @endforeach

    </nav>
  </div>
</aside>
