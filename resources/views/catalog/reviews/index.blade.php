<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $base = url('/' . project()->id . '/catalog');
@endphp

<div
    x-data="{
        reviews: {{ json_encode($reviews) }},
        filter: 'all',
        get filtered() {
            if (this.filter === 'pending')  return this.reviews.filter(r => !r.is_approved);
            if (this.filter === 'approved') return this.reviews.filter(r =>  r.is_approved);
            return this.reviews;
        },
        async toggle(r) {
            const res  = await fetch('{{ $base }}/reviews/' + r.id + '/approve', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.ok) r.is_approved = data.is_approved;
        },
        async destroy(r) {
            if (!confirm('¿Eliminar esta reseña?')) return;
            const res = await fetch('{{ $base }}/reviews/' + r.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.ok) this.reviews = this.reviews.filter(x => x.id !== r.id);
        },
        stars(n) { return '★'.repeat(n) + '☆'.repeat(5 - n); }
    }"
    class="p-6"
>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-800">Reseñas de productos</h1>
        <div class="flex gap-2 text-sm">
            <button @click="filter='all'"
                :class="filter==='all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-300'"
                class="px-3 py-1.5 rounded-lg font-medium transition">Todas</button>
            <button @click="filter='pending'"
                :class="filter==='pending' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-300'"
                class="px-3 py-1.5 rounded-lg font-medium transition">Pendientes</button>
            <button @click="filter='approved'"
                :class="filter==='approved' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-300'"
                class="px-3 py-1.5 rounded-lg font-medium transition">Aprobadas</button>
        </div>
    </div>

    <!-- Empty state -->
    <div x-show="filtered.length === 0" class="text-center py-16 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z"/>
        </svg>
        <p>No hay reseñas en este filtro</p>
    </div>

    <!-- Reviews list -->
    <div class="space-y-3">
        <template x-for="r in filtered" :key="r.id">
            <div class="bg-white border border-gray-200 rounded-xl p-4 flex gap-4 items-start">
                <!-- Avatar -->
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                    <span class="text-indigo-600 font-semibold text-sm" x-text="r.author_name.charAt(0).toUpperCase()"></span>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="font-medium text-gray-800 text-sm" x-text="r.author_name"></span>
                        <span class="text-amber-400 text-sm tracking-tight" x-text="stars(r.rating)"></span>
                        <span class="text-xs text-gray-400" x-text="r.created_at"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                            :class="r.is_approved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            x-text="r.is_approved ? 'Aprobada' : 'Pendiente'"></span>
                    </div>
                    <p class="text-xs text-indigo-500 mb-1">
                        Producto: <span x-text="r.product"></span>
                    </p>
                    <p class="text-sm text-gray-600" x-text="r.comment || '(sin comentario)'"></p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 shrink-0">
                    <button @click="toggle(r)"
                        :title="r.is_approved ? 'Desaprobar' : 'Aprobar'"
                        class="p-2 rounded-lg transition"
                        :class="r.is_approved ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'">
                        <svg x-show="!r.is_approved" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="r.is_approved" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <button @click="destroy(r)"
                        title="Eliminar"
                        class="p-2 rounded-lg text-red-400 hover:bg-red-50 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

</x-slot>
</x-app-layout>
