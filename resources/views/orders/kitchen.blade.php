<x-portal-layout layout="comercial" :project="$project" pageTitle="Vista Cocina">
<div class="kitch-wrap" x-data="kitchenBoard()" x-init="init()" x-cloak>

    {{-- Header --}}
    <div class="kitch-header">
        <div class="kitch-header-left">
            <div class="kitch-title-row">
                <span class="kitch-icon">🍳</span>
                <h1 class="kitch-title">Vista de Cocina</h1>
                <span class="kitch-live-dot"></span>
                <span class="kitch-live-txt">En vivo</span>
            </div>
            <p class="kitch-subtitle">{{ $project->name }} — actualización automática cada 30 seg</p>
        </div>
        <div class="kitch-header-right">
            <div class="kitch-counter kitch-counter--new" x-text="countByStatus('pending') + ' Nuevos'"></div>
            <div class="kitch-counter kitch-counter--cooking" x-text="countByStatus('cooking') + ' En preparación'"></div>
            <div class="kitch-counter kitch-counter--ready" x-text="countByStatus('ready') + ' Listos'"></div>
        </div>
    </div>

    {{-- Columnas Kanban --}}
    <div class="kitch-board">

        {{-- NUEVO --}}
        <div class="kitch-col">
            <div class="kitch-col-head kitch-col-head--new">
                <span>🟡 Nuevo</span>
                <span class="kitch-col-count" x-text="countByStatus('pending')"></span>
            </div>
            <div class="kitch-col-body">
                <template x-for="o in byStatus('pending')" :key="o.id">
                    <div class="kitch-card kitch-card--new">
                        <div class="kitch-card-top">
                            <div class="kitch-order-num">#<span x-text="o.id"></span></div>
                            <div class="kitch-time" x-text="timeAgo(o.created_at)"></div>
                        </div>
                        <div class="kitch-table-row" x-show="o.table_number">
                            🪑 Mesa <strong x-text="o.table_number"></strong>
                        </div>
                        <div class="kitch-client" x-text="o.client_name"></div>
                        <ul class="kitch-items">
                            <template x-for="item in o.items" :key="item.name">
                                <li>
                                    <span class="kitch-qty" x-text="item.quantity + 'x'"></span>
                                    <span x-text="item.name"></span>
                                </li>
                            </template>
                        </ul>
                        <div class="kitch-notes" x-show="o.notes" x-text="'📝 ' + o.notes"></div>
                        <button class="kitch-btn kitch-btn--start" @click="advance(o, 'cooking')">
                            🔥 Iniciar preparación
                        </button>
                    </div>
                </template>
                <div class="kitch-empty" x-show="countByStatus('pending') === 0">Sin pedidos nuevos</div>
            </div>
        </div>

        {{-- EN PREPARACIÓN --}}
        <div class="kitch-col">
            <div class="kitch-col-head kitch-col-head--cooking">
                <span>🔵 Preparando</span>
                <span class="kitch-col-count" x-text="countByStatus('cooking')"></span>
            </div>
            <div class="kitch-col-body">
                <template x-for="o in byStatus('cooking')" :key="o.id">
                    <div class="kitch-card kitch-card--cooking">
                        <div class="kitch-card-top">
                            <div class="kitch-order-num">#<span x-text="o.id"></span></div>
                            <div class="kitch-timer" x-text="elapsedSince(o.kitchen_at)"></div>
                        </div>
                        <div class="kitch-table-row" x-show="o.table_number">
                            🪑 Mesa <strong x-text="o.table_number"></strong>
                        </div>
                        <div class="kitch-client" x-text="o.client_name"></div>
                        <ul class="kitch-items">
                            <template x-for="item in o.items" :key="item.name">
                                <li>
                                    <span class="kitch-qty" x-text="item.quantity + 'x'"></span>
                                    <span x-text="item.name"></span>
                                </li>
                            </template>
                        </ul>
                        <div class="kitch-notes" x-show="o.notes" x-text="'📝 ' + o.notes"></div>
                        <button class="kitch-btn kitch-btn--ready" @click="advance(o, 'ready')">
                            ✅ Marcar listo
                        </button>
                    </div>
                </template>
                <div class="kitch-empty" x-show="countByStatus('cooking') === 0">Nada en preparación</div>
            </div>
        </div>

        {{-- LISTO --}}
        <div class="kitch-col">
            <div class="kitch-col-head kitch-col-head--ready">
                <span>🟢 Listo para servir</span>
                <span class="kitch-col-count" x-text="countByStatus('ready')"></span>
            </div>
            <div class="kitch-col-body">
                <template x-for="o in byStatus('ready')" :key="o.id">
                    <div class="kitch-card kitch-card--ready">
                        <div class="kitch-card-top">
                            <div class="kitch-order-num">#<span x-text="o.id"></span></div>
                            <div class="kitch-time kitch-time--ready" x-text="elapsedSince(o.ready_at) + ' esperando'"></div>
                        </div>
                        <div class="kitch-table-row" x-show="o.table_number">
                            🪑 Mesa <strong x-text="o.table_number"></strong>
                        </div>
                        <div class="kitch-client" x-text="o.client_name"></div>
                        <ul class="kitch-items">
                            <template x-for="item in o.items" :key="item.name">
                                <li>
                                    <span class="kitch-qty" x-text="item.quantity + 'x'"></span>
                                    <span x-text="item.name"></span>
                                </li>
                            </template>
                        </ul>
                        <button class="kitch-btn kitch-btn--served" @click="advance(o, 'served')">
                            🍽 Entregado
                        </button>
                    </div>
                </template>
                <div class="kitch-empty" x-show="countByStatus('ready') === 0">Sin pedidos listos</div>
            </div>
        </div>

    </div>
</div>

<style>
[x-cloak]{display:none!important}

.kitch-wrap {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #0F1117;
    color: #F9FAFB;
    font-family: 'Inter', sans-serif;
    overflow: hidden;
}

/* Header */
.kitch-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: #1A1D23;
    border-bottom: 1px solid #2D2F36;
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 12px;
}
.kitch-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.kitch-icon { font-size: 22px; }
.kitch-title { font-size: 20px; font-weight: 800; margin: 0; color: #F9FAFB; }
.kitch-live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,.3);
    animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,.3); }
    50%       { box-shadow: 0 0 0 6px rgba(16,185,129,.1); }
}
.kitch-live-txt { font-size: 11px; font-weight: 600; color: #10B981; text-transform: uppercase; letter-spacing: .1em; }
.kitch-subtitle { font-size: 12px; color: #8590A2; margin: 4px 0 0; }
.kitch-header-right { display: flex; gap: 10px; flex-wrap: wrap; }
.kitch-counter {
    font-size: 12px; font-weight: 700;
    padding: 6px 14px; border-radius: 20px;
}
.kitch-counter--new     { background: #422006; color: #FDE68A; }
.kitch-counter--cooking { background: #1E3A5F; color: #93C5FD; }
.kitch-counter--ready   { background: #052E16; color: #86EFAC; }

/* Board */
.kitch-board {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 20px;
    flex: 1;
    overflow: hidden;
}
@media (max-width: 900px) {
    .kitch-board { grid-template-columns: 1fr; overflow-y: auto; }
}

.kitch-col {
    display: flex;
    flex-direction: column;
    background: #1A1D23;
    border-radius: 12px;
    overflow: hidden;
    min-height: 0;
}
.kitch-col-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}
.kitch-col-head--new     { background: #292019; color: #FDE68A; }
.kitch-col-head--cooking { background: #162033; color: #93C5FD; }
.kitch-col-head--ready   { background: #071A10; color: #86EFAC; }
.kitch-col-count {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.15);
    font-size: 12px; font-weight: 800;
}

.kitch-col-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.kitch-col-body::-webkit-scrollbar { width: 4px; }
.kitch-col-body::-webkit-scrollbar-thumb { background: #2D2F36; border-radius: 4px; }

/* Card */
.kitch-card {
    background: #23262F;
    border-radius: 10px;
    padding: 14px;
    border-left: 4px solid transparent;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.kitch-card--new     { border-left-color: #F59E0B; }
.kitch-card--cooking { border-left-color: #3B82F6; animation: kitch-pulse 3s ease infinite; }
.kitch-card--ready   { border-left-color: #10B981; }

@keyframes kitch-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); }
    50%       { box-shadow: 0 0 0 4px rgba(59,130,246,.2); }
}

.kitch-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kitch-order-num { font-size: 15px; font-weight: 800; color: #F9FAFB; }
.kitch-time      { font-size: 11px; color: #8590A2; }
.kitch-time--ready { color: #F87171; font-weight: 600; }
.kitch-timer     { font-size: 12px; font-weight: 700; color: #60A5FA; }
.kitch-table-row { font-size: 13px; color: #D1D5DB; }
.kitch-client    { font-size: 13px; font-weight: 600; color: #E5E7EB; }

.kitch-items {
    list-style: none;
    margin: 0;
    padding: 8px 10px;
    background: #1A1D23;
    border-radius: 7px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.kitch-items li {
    display: flex;
    gap: 8px;
    font-size: 13px;
    color: #D1D5DB;
}
.kitch-qty {
    font-weight: 800;
    color: #A78BFA;
    min-width: 28px;
}
.kitch-notes { font-size: 12px; color: #9CA3AF; font-style: italic; }

.kitch-btn {
    width: 100%;
    padding: 9px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    margin-top: 4px;
}
.kitch-btn:hover  { opacity: .85; transform: translateY(-1px); }
.kitch-btn:active { transform: translateY(0); }
.kitch-btn--start  { background: #D97706; color: #fff; }
.kitch-btn--ready  { background: #2563EB; color: #fff; }
.kitch-btn--served { background: #059669; color: #fff; }

.kitch-empty {
    text-align: center;
    padding: 32px 16px;
    color: #4B5563;
    font-size: 13px;
}
</style>

<script>
function kitchenBoard() {
    return {
        orders: @json($ordersJson),
        ticker: 0,

        init() {
            // Refresca los timers cada 30 seg y recarga pedidos
            setInterval(() => { this.ticker++; }, 30000);
            setInterval(() => this.reload(), 30000);
        },

        byStatus(status) {
            return this.orders.filter(o => o.kitchen_status === status);
        },

        countByStatus(status) {
            return this.orders.filter(o => o.kitchen_status === status).length;
        },

        timeAgo(iso) {
            const diff = Math.floor((Date.now() - new Date(iso)) / 60000);
            if (diff < 1) return 'Ahora';
            if (diff === 1) return 'Hace 1 min';
            return `Hace ${diff} min`;
        },

        elapsedSince(iso) {
            if (!iso) return '—';
            const diff = Math.floor((Date.now() - new Date(iso)) / 60000);
            if (diff < 1) return '< 1 min';
            return `${diff} min`;
        },

        async advance(order, newStatus) {
            const res = await fetch(`/bixosales/pedidos/${order.id}/kitchen`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ kitchen_status: newStatus }),
            });
            if (res.ok) {
                const data = await res.json();
                const idx = this.orders.findIndex(o => o.id === order.id);
                if (idx >= 0) {
                    this.orders[idx].kitchen_status = data.kitchen_status;
                    if (newStatus === 'cooking') this.orders[idx].kitchen_at = new Date().toISOString();
                    if (newStatus === 'ready')   this.orders[idx].ready_at   = new Date().toISOString();
                    if (newStatus === 'served')  this.orders.splice(idx, 1);
                }
            }
        },

        async reload() {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                // Solo recargamos si la página sigue disponible
                if (res.ok) window.location.reload();
            } catch(e) {}
        },
    }
}
</script>
</x-portal-layout>
