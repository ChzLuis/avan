<x-portal-layout layout="comercial" :project="$project" pageTitle="Inventario">
<div style="flex:1;overflow-y:auto;padding:16px;background:#F8F9FB;" x-data="inventario()">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div>
            <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Inventario</h1>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="products.length + ' productos'"></p>
        </div>
        <div style="display:flex;gap:6px;">
            <button @click="filter='all'"
                    :style="filter==='all'?'background:#111827;color:#fff;border-color:#111827;':'background:#fff;color:#6B7280;border-color:#E5E8EF;'"
                    style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid;cursor:pointer;transition:all .12s;">
                Todos
            </button>
            <button @click="filter='low'"
                    :style="filter==='low'?'background:#F59E0B;color:#fff;border-color:#F59E0B;':'background:#fff;color:#D97706;border-color:#FDE68A;'"
                    style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid;cursor:pointer;transition:all .12s;">
                Stock bajo
            </button>
            <button @click="filter='out'"
                    :style="filter==='out'?'background:#EF4444;color:#fff;border-color:#EF4444;':'background:#fff;color:#EF4444;border-color:#FECACA;'"
                    style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid;cursor:pointer;transition:all .12s;">
                Agotados
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;">
            <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Total productos</p>
            <p style="font-size:28px;font-weight:800;color:#111827;margin:0;" x-text="products.length"></p>
        </div>
        <div style="background:#fff;border:1px solid #FDE68A;border-radius:12px;padding:14px;">
            <p style="font-size:10px;font-weight:700;color:#D97706;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Stock bajo</p>
            <p style="font-size:28px;font-weight:800;color:#D97706;margin:0;" x-text="lowCount"></p>
        </div>
        <div style="background:#fff;border:1px solid #FECACA;border-radius:12px;padding:14px;">
            <p style="font-size:10px;font-weight:700;color:#EF4444;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Agotados</p>
            <p style="font-size:28px;font-weight:800;color:#EF4444;margin:0;" x-text="outCount"></p>
        </div>
    </div>

    {{-- Buscador --}}
    <div style="margin-bottom:12px;">
        <input x-model="search" type="text" placeholder="Buscar producto..."
               style="width:280px;font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 14px;outline:none;font-family:inherit;"
               onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
    </div>

    {{-- Tabla --}}
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;overflow:hidden;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <thead>
                <tr style="background:#F8F9FB;border-bottom:1px solid #E5E8EF;">
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Producto</th>
                    <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Categoría</th>
                    <th style="padding:10px 14px;text-align:right;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Precio</th>
                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Stock</th>
                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Mín.</th>
                    <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="p in filtered" :key="p.id">
                    <tr style="border-bottom:1px solid #F3F4F6;"
                        onmouseover="this.style.background='#F8F9FB'" onmouseout="this.style.background='#fff'">
                        <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#111827;" x-text="p.name"></td>
                        <td style="padding:10px 14px;color:#9CA3AF;" x-text="p.category"></td>
                        <td style="padding:10px 14px;text-align:right;font-weight:700;color:#374151;" x-text="'S/ ' + p.price.toFixed(2)"></td>
                        <td style="padding:10px 14px;text-align:center;">
                            <span style="font-size:18px;font-weight:800;"
                                  :style="status(p)==='out'?'color:#EF4444;':status(p)==='low'?'color:#F59E0B;':'color:#111827;'"
                                  x-text="p.stock !== null ? p.stock : '—'"></span>
                        </td>
                        <td style="padding:10px 14px;text-align:center;color:#9CA3AF;" x-text="p.stock_min || '—'"></td>
                        <td style="padding:10px 14px;text-align:center;">
                            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;border:1px solid;"
                                  :style="{
                                    out:  'background:#FEE2E2;color:#EF4444;border-color:#FECACA;',
                                    low:  'background:#FFFBEB;color:#D97706;border-color:#FDE68A;',
                                    ok:   'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;',
                                    none: 'background:#F3F4F6;color:#9CA3AF;border-color:#E5E7EB;',
                                  }[status(p)]"
                                  x-text="{out:'Agotado', low:'Stock bajo', ok:'En stock', none:'Sin control'}[status(p)]">
                            </span>
                        </td>
                    </tr>
                </template>
                <template x-if="filtered.length === 0">
                    <tr>
                        <td colspan="6" style="padding:48px 20px;text-align:center;color:#9CA3AF;font-size:13px;">Sin productos</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function inventario() {
    const products = @json($products);
    return {
        products,
        filter: 'all',
        search: '',
        status(p) {
            if (p.stock === null || p.stock === undefined) return 'none';
            if (p.stock <= 0) return 'out';
            if (p.stock_min && p.stock <= p.stock_min) return 'low';
            return 'ok';
        },
        get lowCount() { return this.products.filter(p => this.status(p) === 'low').length; },
        get outCount()  { return this.products.filter(p => this.status(p) === 'out').length; },
        get filtered() {
            return this.products.filter(p => {
                if (this.filter === 'low' && this.status(p) !== 'low') return false;
                if (this.filter === 'out' && this.status(p) !== 'out') return false;
                if (this.search && !p.name.toLowerCase().includes(this.search.toLowerCase())) return false;
                return true;
            });
        },
    };
}
</script>
@endpush
</x-portal-layout>
