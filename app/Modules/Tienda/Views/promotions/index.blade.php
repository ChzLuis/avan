{{-- Layout de componente: layouts.app no tiene @yield('content'); con @extends salia 500 --}}
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Promociones</h2></x-slot>
<div class="p-6 max-w-5xl mx-auto" x-data="promoPage()" x-cloak>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Promociones</h1>
            <p class="text-sm text-gray-500 mt-0.5">Descuentos, cupones y ofertas por tiempo</p>
        </div>
        <button @click="openNew()" class="flex items-center gap-2 bg-violet-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-violet-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Nueva promoción
        </button>
    </div>

    <div class="space-y-3" x-show="promos.length > 0">
        <template x-for="p in promos" :key="p.id">
            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 flex items-center gap-4 shadow-sm">
                {{-- Ícono tipo --}}
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 text-lg"
                     :class="p.type==='percentage' ? 'bg-violet-100' : 'bg-amber-100'">
                    <span x-text="p.type==='percentage' ? '%' : 'S/'"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900 text-sm" x-text="p.name"></span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                              :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                              x-text="p.is_active ? 'Activa' : 'Inactiva'"></span>
                        <span x-show="p.coupon_code" class="text-xs bg-blue-50 text-blue-700 font-mono px-2 py-0.5 rounded" x-text="p.coupon_code"></span>
                    </div>
                    <div class="flex gap-4 mt-1 text-xs text-gray-500">
                        <span x-text="p.type==='percentage' ? p.value+'% de descuento' : 'S/ '+Number(p.value).toFixed(2)+' de descuento'"></span>
                        <span x-show="p.min_order" x-text="'Mínimo S/ '+Number(p.min_order).toFixed(2)"></span>
                        <span x-show="p.ends_at" x-text="'Hasta '+formatDate(p.ends_at)"></span>
                        <span x-show="p.max_uses" x-text="p.uses_count+'/'+p.max_uses+' usos'"></span>
                    </div>
                </div>
                <div class="flex gap-1">
                    <button @click="openEdit(p)" class="p-2 text-gray-400 hover:text-violet-600 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="togglePromo(p)" class="p-2 text-gray-400 hover:text-amber-500 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 12.728M9 9a3 3 0 014.243 4.243"/></svg>
                    </button>
                    <button @click="deletePromo(p)" class="p-2 text-gray-400 hover:text-red-500 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="promos.length === 0" class="text-center py-20 text-gray-400">
        <div class="text-5xl mb-3">🏷️</div>
        <p class="font-semibold text-gray-600">Sin promociones activas</p>
        <p class="text-sm mt-1">Crea cupones y descuentos para impulsar tus ventas</p>
    </div>

    {{-- MODAL --}}
    <div x-show="modal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-5 border-b">
                <h2 class="font-bold text-gray-900" x-text="editing ? 'Editar promoción' : 'Nueva promoción'"></h2>
                <button @click="modal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre *</label>
                    <input type="text" x-model="form.name" placeholder="Ej: Descuento fin de semana"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Producto *</label>
                    {{-- Una promocion B2B es de un producto concreto: la tienda pinta su
                         foto, marca y boton de cotizar. Sin duplicar el articulo. --}}
                    <select x-model="form.applies_to_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">Selecciona un producto</option>
                        @foreach($productos as $pr)
                        <option value="{{ $pr['id'] }}">{{ $pr['name'] }}@if($pr['sku']) · {{ $pr['sku'] }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Etiqueta</label>
                        <input type="text" x-model="form.label" maxlength="40" placeholder="Precio por volumen"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Imagen (URL, opcional)</label>
                        <input type="text" x-model="form.image_url" placeholder="Vacío = foto del producto"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                        <select x-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="percentage">Porcentaje (%)</option>
                            <option value="fixed">Monto fijo (S/)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" x-text="form.type==='percentage' ? 'Descuento %' : 'Descuento S/'"></label>
                        <input type="number" x-model="form.value" step="0.01" min="0" placeholder="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Código de cupón</label>
                    <input type="text" x-model="form.coupon_code" placeholder="Ej: VERANO20" maxlength="30"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pedido mínimo (S/)</label>
                        <input type="number" x-model="form.min_order" step="0.01" min="0" placeholder="0.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Máx. usos</label>
                        <input type="number" x-model="form.max_uses" min="1" placeholder="Sin límite"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Inicio</label>
                        <input type="date" x-model="form.starts_at"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Vence</label>
                        <input type="date" x-model="form.ends_at"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.is_active" id="is_active_promo" class="w-4 h-4 rounded text-violet-600">
                    <label for="is_active_promo" class="text-sm text-gray-700">Activa desde ahora</label>
                </div>
                <div x-show="error" class="text-red-500 text-sm" x-text="error"></div>
            </div>
            <div class="flex justify-end gap-2 p-5 border-t">
                <button @click="modal=false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="save()" :disabled="saving"
                        class="px-5 py-2 text-sm bg-violet-600 text-white font-semibold rounded-lg hover:bg-violet-700 disabled:opacity-50 transition">
                    <span x-text="saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear')"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function promoPage() {
    return {
        promos: @json($promotionsJson),
        modal:false, editing:null, saving:false, error:'',
        form:{ name:'',type:'percentage',applies_to:'product',applies_to_id:'',label:'',image_url:'',value:'',coupon_code:'',min_order:'',max_uses:'',is_active:true,starts_at:'',ends_at:'' },

        openNew() {
            this.editing=null;
            this.form={ name:'',type:'percentage',value:'',coupon_code:'',min_order:'',max_uses:'',is_active:true,starts_at:'',ends_at:'' };
            this.error=''; this.modal=true;
        },
        openEdit(p) {
            this.editing=p.id;
            this.form={ name:p.name,type:p.type,value:p.value,coupon_code:p.coupon_code||'',min_order:p.min_order||'',max_uses:p.max_uses||'',is_active:p.is_active,applies_to:p.applies_to||'product',applies_to_id:p.applies_to_id||'',label:p.label||'',image_url:p.image_url||'',starts_at:p.starts_at||'',ends_at:p.ends_at||'' };
            this.error=''; this.modal=true;
        },
        async save() {
            this.error='';
            if(!this.form.name){ this.error='El nombre es requerido'; return; }
            if(this.form.applies_to==='product' && !this.form.applies_to_id){ this.error='Elige el producto de la promoción'; return; }
            this.saving=true;
            const url=this.editing ? `/bixoadmin/promotions/${this.editing}` : '/bixoadmin/promotions';
            const method=this.editing ? 'PUT' : 'POST';
            try {
                const res=await fetch(url,{method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:JSON.stringify(this.form)});
                const data=await res.json();
                if(data.ok){
                    if(this.editing){ const i=this.promos.findIndex(x=>x.id===this.editing); if(i>=0) this.promos[i]=data.promotion; }
                    else this.promos.unshift(data.promotion);
                    this.modal=false;
                } else this.error=data.message||'Error al guardar';
            } catch(e){ this.error='Error de conexión'; }
            this.saving=false;
        },
        async togglePromo(p) {
            const res=await fetch(`/bixoadmin/promotions/${p.id}/toggle`,{method:'PATCH',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
            const data=await res.json();
            if(data.ok){ const i=this.promos.findIndex(x=>x.id===p.id); if(i>=0) this.promos[i].is_active=data.is_active; }
        },
        async deletePromo(p) {
            if (! await bxConfirmar({ descripcion: `¿Eliminar "${p.name}"?` })) return;
            const res=await fetch(`/bixoadmin/promotions/${p.id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
            if((await res.json()).ok) this.promos=this.promos.filter(x=>x.id!==p.id);
        },
        formatDate(d){ if(!d) return ''; return new Date(d).toLocaleDateString('es-PE'); },
    }
}
</script>
</x-app-layout>
