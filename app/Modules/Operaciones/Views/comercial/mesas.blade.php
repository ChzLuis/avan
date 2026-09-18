<x-portal-layout layout="comercial" :project="$project" pageTitle="Mesas">
<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;background:#F0F2F8;font-family:-apple-system,BlinkMacSystemFont,'Inter','Segoe UI',sans-serif;"
     x-data="mesasBoard()" x-init="init()">

{{-- ══════════════ TOP BAR ══════════════ --}}
<div style="background:#fff;border-bottom:1px solid #E5E8EF;padding:10px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0;flex-wrap:wrap;">

  {{-- Score AVAN --}}
  <div style="display:flex;align-items:center;gap:7px;padding:0 12px 0 0;border-right:1px solid #E5E8EF;flex-shrink:0;">
    <div :style="'width:36px;height:36px;border-radius:50%;border:3px solid ' + scoreColor + ';display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:' + scoreColor">
      <span x-text="score"></span>
    </div>
    <div>
      <div style="font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#9CA3AF;">BIXO Score</div>
      <div style="font-size:10px;font-weight:800;" :style="'color:' + scoreColor" x-text="scoreLabel"></div>
    </div>
  </div>

  {{-- Sectores --}}
  <div style="display:flex;gap:5px;flex-wrap:wrap;">
    <button @click="sector='todos'"
            :style="sector==='todos'?'background:#1D4ED8;color:#fff;':'background:#EEF2FF;color:#3730A3;'"
            style="padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;border:none;cursor:pointer;transition:all .12s;">
      Todos
    </button>
    <template x-for="sec in sectores" :key="sec">
      <button @click="sector=sec"
              :style="sector===sec?'background:#1D4ED8;color:#fff;':'background:#EEF2FF;color:#3730A3;'"
              style="padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;border:none;cursor:pointer;transition:all .12s;"
              x-text="sec">
      </button>
    </template>
  </div>

  {{-- Búsqueda única (eliminado el duplicado de abajo) --}}
  <div style="display:flex;align-items:center;gap:6px;background:#F3F4F6;border-radius:10px;padding:6px 11px;min-width:180px;">
    <svg style="width:13px;height:13px;color:#9CA3AF;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
    </svg>
    <input x-model="buscar" type="text" placeholder="Buscar mesa, cliente..."
           style="background:none;border:none;outline:none;font-size:12px;color:#374151;width:100%;font-family:inherit;">
    <button x-show="buscar" @click="buscar=''" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:14px;padding:0;">✕</button>
  </div>

  {{-- Leyenda --}}
  <div style="display:flex;align-items:center;gap:10px;font-size:11px;color:#6B7280;margin-left:auto;flex-wrap:wrap;">
    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:50%;background:#10B981;display:inline-block;"></span> Libre</span>
    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:50%;background:#F59E0B;display:inline-block;"></span> Con pedido</span>
    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:50%;background:#F97316;display:inline-block;"></span> En cocina</span>
    <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:50%;background:#3B82F6;display:inline-block;"></span> Listo</span>
    <span style="display:flex;align-items:center;gap:4px;color:#EF4444;font-weight:700;">
      <span style="width:7px;height:7px;border-radius:50%;background:#EF4444;display:inline-block;animation:pulse-alerta 1s infinite;"></span> Alerta
    </span>
    <span style="display:flex;align-items:center;gap:4px;color:#10B981;font-weight:700;">
      <span style="width:7px;height:7px;border-radius:50%;background:#10B981;display:inline-block;animation:pulse-vivo 2s infinite;"></span> En vivo
    </span>
  </div>

  {{-- Botón unir mesas --}}
  <button @click="modoUnion=!modoUnion; seleccionUnion=[]"
          :style="modoUnion ? 'background:#7C3AED;color:#fff;border-color:#7C3AED;' : 'background:#F5F3FF;color:#5B21B6;'"
          style="padding:6px 13px;border-radius:10px;font-size:11px;font-weight:700;border:2px dashed #7C3AED;cursor:pointer;transition:all .12s;display:flex;align-items:center;gap:5px;flex-shrink:0;">
    ⊞ <span x-text="modoUnion ? 'Cancelar unión' : 'Unir mesas'"></span>
  </button>
</div>

{{-- Banner modo unión --}}
<div x-show="modoUnion" style="background:#7C3AED;color:#fff;padding:8px 16px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;flex-shrink:0;">
  ⊞ Modo Unión — seleccioná las mesas que querés combinar
  <template x-if="seleccionUnion.length >= 2">
    <button @click="confirmarUnion()"
            style="margin-left:8px;background:#fff;color:#7C3AED;padding:3px 12px;border-radius:6px;font-weight:800;border:none;cursor:pointer;">
      Unir (<span x-text="seleccionUnion.length"></span> mesas) →
    </button>
  </template>
  <span x-show="seleccionUnion.length < 2" style="opacity:.7;" x-text="'Seleccionadas: ' + seleccionUnion.length + ' (necesitás al menos 2)'"></span>
</div>

{{-- ══════════════ GRID MESAS ══════════════ --}}
<div style="flex:1;overflow-y:auto;padding:14px;">
  <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));">
    <template x-for="mesa in mesasFiltradas" :key="mesa.numero">
      <div @click="modoUnion ? toggleUnion(mesa) : seleccionar(mesa)"
           :style="cardStyle(mesa)"
           style="border-radius:16px;padding:13px 12px 10px;cursor:pointer;transition:all .18s;position:relative;min-height:128px;display:flex;flex-direction:column;gap:5px;overflow:visible;">

        {{-- Parpadeo alerta (mesa con alerta activa, timer > 45m sin cocina) --}}
        <template x-if="tieneAlerta(mesa)">
          <div style="position:absolute;inset:0;border-radius:14px;border:2px solid #EF4444;animation:ring-alerta 1.2s ease-in-out infinite;pointer-events:none;z-index:2;"></div>
        </template>

        {{-- Badge unión seleccionada --}}
        <template x-if="modoUnion && seleccionUnion.includes(mesa.numero)">
          <div style="position:absolute;top:-7px;right:-7px;width:22px;height:22px;background:#7C3AED;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.2);z-index:10;">
            ✓
          </div>
        </template>

        {{-- Badge cantidad pedidos --}}
        <template x-if="cuentaPedidos(mesa) > 0">
          <div style="position:absolute;top:-7px;left:50%;transform:translateX(-50%);font-size:9px;font-weight:900;padding:2px 8px;border-radius:20px;box-shadow:0 2px 6px rgba(0,0,0,.18);white-space:nowrap;z-index:10;"
               :style="badgePedidoStyle(mesa)"
               x-text="cuentaPedidos(mesa) + (cuentaPedidos(mesa)===1?' pedido':' pedidos')">
          </div>
        </template>

        {{-- Ícono de alerta activa --}}
        <template x-if="tieneAlerta(mesa)">
          <div style="position:absolute;top:8px;right:8px;width:20px;height:20px;background:#EF4444;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;z-index:5;animation:pulse-alerta 1s infinite;">⚠</div>
        </template>

        {{-- Número y sector --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;">
          <div>
            <div style="font-size:22px;font-weight:900;line-height:1;letter-spacing:-1px;" x-text="mesa.numero"></div>
            <div style="font-size:10px;font-weight:600;opacity:.6;margin-top:1px;" x-text="mesa.sector"></div>
          </div>
          <div :style="'width:10px;height:10px;border-radius:50%;background:'+dotColor(mesa)+';box-shadow:0 0 0 3px '+dotColor(mesa)+'35;margin-top:4px;flex-shrink:0;'"
               :class="estadoMesa(mesa)!=='libre'?'dot-animated':''"></div>
        </div>

        {{-- Timer con color urgencia real --}}
        <template x-if="tiempoMesa(mesa)">
          <div style="display:flex;align-items:center;gap:4px;">
            <span style="font-size:11px;opacity:.5;">⏱</span>
            <span style="font-size:13px;font-weight:800;font-variant-numeric:tabular-nums;"
                  :style="urgenciaColor(mesa)"
                  x-text="tiempoMesa(mesa)"></span>
          </div>
        </template>

        {{-- Monto --}}
        <template x-if="totalMesaCard(mesa) > 0">
          <div style="font-size:15px;font-weight:900;" x-text="'S/ ' + totalMesaCard(mesa).toFixed(2)"></div>
        </template>

        {{-- Cliente --}}
        <template x-if="clienteMesa(mesa)">
          <div style="font-size:10px;font-weight:600;opacity:.7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="'👤 ' + clienteMesa(mesa)"></div>
        </template>

        {{-- Mozo asignado (iniciales) --}}
        <template x-if="mozoMesa(mesa)">
          <div style="position:absolute;bottom:34px;right:10px;width:22px;height:22px;border-radius:50%;background:#1D4ED8;color:#fff;font-size:8px;font-weight:900;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.2);"
               :title="mozoMesa(mesa)"
               x-text="iniciales(mozoMesa(mesa))">
          </div>
        </template>

        {{-- Acciones rápidas hover --}}
        <div class="mesa-quick-actions"
             x-show="estadoMesa(mesa) !== 'libre'"
             style="position:absolute;inset:0;border-radius:14px;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;gap:8px;opacity:0;transition:opacity .15s;z-index:20;pointer-events:none;">
          <a :href="'{{ route('bixosales.pos') }}?mesa=' + mesa.numero + '&precuenta=1'"
             style="padding:6px 10px;background:#fff;border-radius:8px;font-size:10px;font-weight:800;color:#111827;text-decoration:none;pointer-events:auto;"
             @click.stop>📄 Cuenta</a>
          <button @click.stop="cambiarMesa(mesa)"
                  style="padding:6px 10px;background:#3B82F6;border-radius:8px;font-size:10px;font-weight:800;color:#fff;border:none;cursor:pointer;">🔄 Cambiar</button>
          <button @click.stop="cobrarDirecto(mesa)"
                  style="padding:6px 10px;background:#059669;border-radius:8px;font-size:10px;font-weight:800;color:#fff;border:none;cursor:pointer;">💸 Cobrar</button>
        </div>

        {{-- Estado label --}}
        <div style="margin-top:auto;">
          <span :style="estadoBadgeStyle(mesa)"
                style="font-size:9px;font-weight:800;padding:3px 9px;border-radius:20px;letter-spacing:.4px;display:inline-block;"
                x-text="estadoLabel(mesa).toUpperCase()"></span>
        </div>

        {{-- Indicador "próxima rotación" (>60 min) --}}
        <template x-if="minutosMesa(mesa) > 60 && estadoMesa(mesa) !== 'libre'">
          <div style="position:absolute;bottom:5px;right:5px;width:7px;height:7px;border-radius:50%;background:#A855F7;" title="Mesa puede liberar pronto"></div>
        </template>

        {{-- Mesas unidas badge --}}
        <template x-if="mesasUnidas(mesa).length > 0">
          <div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);background:#7C3AED;color:#fff;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,.2);z-index:5;"
               x-text="'⊞ ' + mesasUnidas(mesa).join(', ')">
          </div>
        </template>
      </div>
    </template>
  </div>

  <div x-show="mesasFiltradas.length===0"
       style="text-align:center;padding:60px 20px;color:#9CA3AF;">
    <p style="font-size:13px;font-weight:500;">No hay mesas en este sector</p>
  </div>
</div>

{{-- ══════════════ STATS BAR ══════════════ --}}
<div style="border-top:1px solid #E5E8EF;background:#fff;padding:8px 16px;display:flex;align-items:center;gap:0;font-size:11px;color:#6B7280;flex-shrink:0;flex-wrap:wrap;gap:0;">
  <span style="padding:0 12px 0 0;border-right:1px solid #E5E8EF;">
    Libres: <strong style="color:#059669;" x-text="stats.libres"></strong>
  </span>
  <span style="padding:0 12px;border-right:1px solid #E5E8EF;">
    Con pedido: <strong style="color:#D97706;" x-text="stats.ocupadas"></strong>
  </span>
  <span style="padding:0 12px;border-right:1px solid #E5E8EF;">
    En cocina: <strong style="color:#EA580C;" x-text="stats.enCocina"></strong>
  </span>
  <span style="padding:0 12px;border-right:1px solid #E5E8EF;">
    Listos: <strong style="color:#2563EB;" x-text="stats.listas"></strong>
  </span>
  <span x-show="stats.alertas > 0" style="padding:0 12px;border-right:1px solid #E5E8EF;">
    Alertas: <strong style="color:#EF4444;" x-text="stats.alertas"></strong>
  </span>
  <span style="margin-left:auto;padding-left:12px;">
    Consumo total: <strong style="color:#111827;" x-text="'S/ ' + stats.totalConsumo.toFixed(2)"></strong>
  </span>
  <span style="padding-left:12px;">
    Total pedidos: <strong style="color:#111827;" x-text="stats.totalPedidos"></strong>
  </span>

  {{-- Lista de espera --}}
  <button @click="showListaEspera=true"
          style="margin-left:12px;padding:4px 10px;border-radius:8px;border:1px solid #E5E8EF;background:#F9FAFB;color:#374151;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .12s;"
          :style="listaEspera.length>0?'border-color:#F59E0B;color:#D97706;background:#FFFBEB;':''">
    🕐 Lista de espera <span x-show="listaEspera.length>0" style="background:#F59E0B;color:#fff;font-size:9px;font-weight:900;padding:1px 5px;border-radius:10px;" x-text="listaEspera.length"></span>
  </button>
</div>

{{-- ══════════════ PANEL DETALLE MESA ══════════════ --}}
<div x-show="mesaActiva && !modoUnion"
     style="position:fixed;inset:0;z-index:40;background:rgba(0,0,0,.4);"
     @click.self="mesaActiva=null">
  <div :style="mesaActiva ? 'transform:translateX(0)' : 'transform:translateX(100%)'"
       style="position:absolute;right:0;top:0;bottom:0;width:390px;background:#fff;transition:transform .25s;display:flex;flex-direction:column;overflow:hidden;box-shadow:-8px 0 32px rgba(0,0,0,.2);">

    <template x-if="mesaActiva">
      <div style="display:flex;flex-direction:column;height:100%;">

        {{-- Header --}}
        <div :style="'background:' + bgMesa(mesaActiva) + ';'" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
          <div style="flex:1;">
            <div style="color:#fff;font-weight:900;font-size:20px;line-height:1;">MESA <span x-text="mesaActiva.numero"></span></div>
            <div style="color:rgba(255,255,255,.8);font-size:12px;margin-top:3px;" x-text="mesaActiva.sector + ' · ' + estadoLabel(mesaActiva)"></div>
            <template x-if="tiempoMesa(mesaActiva)">
              <div style="color:#fff;font-size:13px;font-weight:800;margin-top:4px;display:flex;align-items:center;gap:4px;">
                <span>⏱</span>
                <span x-text="tiempoMesa(mesaActiva) + ' en mesa'"></span>
                <template x-if="tieneAlerta(mesaActiva)">
                  <span style="background:rgba(0,0,0,.2);padding:2px 8px;border-radius:99px;font-size:10px;animation:pulse-alerta 1s infinite;">⚠ ALERTA</span>
                </template>
              </div>
            </template>
            {{-- Mozo en header panel --}}
            <template x-if="mozoMesa(mesaActiva)">
              <div style="color:rgba(255,255,255,.85);font-size:11px;margin-top:4px;display:flex;align-items:center;gap:4px;">
                <span>👨‍🍳</span>
                <span x-text="'Mozo: ' + mozoMesa(mesaActiva)"></span>
              </div>
            </template>
          </div>
          <button @click="mesaActiva=null"
                  style="width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,.2);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0;">✕</button>
        </div>

        {{-- Si hay mesas unidas --}}
        <template x-if="mesasUnidas(mesaActiva).length > 0">
          <div style="background:#EDE9FE;padding:8px 16px;font-size:11px;color:#5B21B6;font-weight:600;display:flex;align-items:center;gap:6px;">
            ⊞ Unida con: <span x-text="mesasUnidas(mesaActiva).join(', ')"></span>
            <button @click="desunirMesa(mesaActiva)" style="margin-left:auto;font-size:10px;color:#7C3AED;background:none;border:none;cursor:pointer;font-weight:800;">Desunir</button>
          </div>
        </template>

        {{-- Asignar mozo --}}
        <div style="padding:8px 14px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:8px;">
          <span style="font-size:10px;font-weight:700;color:#9CA3AF;flex-shrink:0;">MOZO:</span>
          <input type="text" x-model="mozoInput" placeholder="Nombre del mozo..."
                 @keyup.enter="asignarMozo()"
                 style="flex:1;font-size:11px;border:1px solid #E5E8EF;border-radius:7px;padding:5px 9px;outline:none;color:#374151;font-family:inherit;">
          <button @click="asignarMozo()"
                  style="padding:5px 10px;background:#2563EB;color:#fff;font-size:10px;font-weight:700;border-radius:7px;border:none;cursor:pointer;">OK</button>
        </div>

        {{-- Pedidos --}}
        <div style="flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;">
          <template x-if="pedidosMesa.length === 0">
            <div style="text-align:center;padding:48px 20px;color:#9CA3AF;">
              <p style="font-size:14px;font-weight:600;color:#6B7280;">Mesa libre</p>
              <p style="font-size:11px;margin-top:4px;">Sin pedidos activos</p>
              <a :href="'{{ route('bixosales.pos') }}?mesa=' + mesaActiva.numero"
                 style="display:inline-block;margin-top:14px;padding:9px 22px;background:#2563EB;color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                + Nuevo pedido
              </a>
            </div>
          </template>

          <template x-for="p in pedidosMesa" :key="p.id">
            <div style="background:#fff;border:1px solid #E5E8EF;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #F3F4F6;background:#FAFBFC;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:12px;font-weight:800;color:#374151;">#<span x-text="p.id"></span></span>
                  <span :style="badgeKitchenStyle(p.kitchen_status)"
                        style="font-size:10px;font-weight:700;padding:2px 9px;border-radius:99px;"
                        x-text="kitchenLabel(p.kitchen_status)">
                  </span>
                  <template x-if="p.client_name && p.client_name !== 'Cliente general'">
                    <span style="font-size:10px;color:#6B7280;">👤 <span x-text="p.client_name"></span></span>
                  </template>
                </div>
                <span style="font-size:11px;color:#9CA3AF;font-weight:500;" x-text="timeAgo(p.created_at)"></span>
              </div>

              <div style="padding:10px 14px;display:flex;flex-direction:column;gap:4px;">
                <template x-for="item in p.items" :key="item.id">
                  <div style="display:flex;justify-content:space-between;font-size:12px;">
                    <span style="color:#374151;"><strong x-text="item.quantity + '×'"></strong> <span x-text="item.name"></span></span>
                    <span style="color:#6B7280;font-weight:600;" x-text="'S/ ' + (item.price * item.quantity).toFixed(2)"></span>
                  </div>
                </template>
              </div>

              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #F3F4F6;background:#FAFBFC;">
                <span style="font-size:16px;font-weight:900;color:#111827;" x-text="'S/ ' + parseFloat(p.total).toFixed(2)"></span>
                <div style="display:flex;gap:6px;">
                  <template x-if="p.kitchen_status === 'pending'">
                    <button @click="avanzarKitchen(p, 'cooking')"
                            style="padding:5px 12px;background:#F97316;color:#fff;font-size:11px;font-weight:700;border-radius:8px;border:none;cursor:pointer;">
                      🔥 Iniciar
                    </button>
                  </template>
                  <template x-if="p.kitchen_status === 'cooking'">
                    <button @click="avanzarKitchen(p, 'ready')"
                            style="padding:5px 12px;background:#3B82F6;color:#fff;font-size:11px;font-weight:700;border-radius:8px;border:none;cursor:pointer;">
                      ✅ Listo
                    </button>
                  </template>
                  <template x-if="p.kitchen_status === 'ready'">
                    <button @click="avanzarKitchen(p, 'served')"
                            style="padding:5px 12px;background:#10B981;color:#fff;font-size:11px;font-weight:700;border-radius:8px;border:none;cursor:pointer;">
                      🍽 Entregado
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </template>
        </div>

        {{-- Footer panel --}}
        <div style="border-top:1px solid #E5E8EF;background:#FAFBFC;padding:14px 16px;flex-shrink:0;display:flex;flex-direction:column;gap:8px;">
          <template x-if="pedidosMesa.length > 0">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:13px;color:#6B7280;font-weight:500;">Total mesa</span>
              <span style="font-size:22px;font-weight:900;color:#111827;" x-text="'S/ ' + totalMesaPanel.toFixed(2)"></span>
            </div>
          </template>
          <a :href="'{{ route('bixosales.pos') }}?mesa=' + mesaActiva.numero"
             style="display:flex;align-items:center;justify-content:center;gap:6px;padding:11px;background:#2563EB;color:#fff;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;">
            + Agregar pedido
          </a>
          <template x-if="pedidosMesa.length > 0">
            <button @click="cobrarMesa()"
                    style="display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:#059669;color:#fff;border-radius:12px;font-size:13px;font-weight:700;border:none;cursor:pointer;">
              💳 Cobrar mesa — S/ <span x-text="totalMesaPanel.toFixed(2)"></span>
            </button>
          </template>
        </div>
      </div>
    </template>
  </div>
</div>

{{-- ══ MODAL LISTA DE ESPERA ══ --}}
<div x-show="showListaEspera"
     style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
     @click.self="showListaEspera=false">
  <div style="background:#fff;border-radius:18px;padding:22px;width:340px;max-width:92vw;box-shadow:0 24px 64px rgba(0,0,0,.25);">
    <div style="font-size:15px;font-weight:800;color:#111827;margin-bottom:14px;">🕐 Lista de espera</div>

    {{-- Agregar a la espera --}}
    <div style="display:flex;gap:6px;margin-bottom:14px;">
      <input type="text" x-model="esperaInput" placeholder="Nombre del cliente..."
             @keyup.enter="agregarEspera()"
             style="flex:1;font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:7px 10px;outline:none;font-family:inherit;">
      <button @click="agregarEspera()"
              style="padding:7px 13px;background:#2563EB;color:#fff;font-size:12px;font-weight:700;border-radius:8px;border:none;cursor:pointer;">+</button>
    </div>

    {{-- Lista --}}
    <div style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;">
      <template x-if="listaEspera.length === 0">
        <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:20px 0;">Sin clientes en espera</p>
      </template>
      <template x-for="(e, i) in listaEspera" :key="i">
        <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#F9FAFB;border-radius:10px;border:1px solid #E5E8EF;">
          <div style="width:24px;height:24px;border-radius:50%;background:#1D4ED8;color:#fff;font-size:10px;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0;" x-text="i+1"></div>
          <div style="flex:1;">
            <div style="font-size:12px;font-weight:700;color:#111827;" x-text="e.nombre"></div>
            <div style="font-size:10px;color:#9CA3AF;" x-text="'Esperando ' + tiempoEspera(e.at) + ' min'"></div>
          </div>
          <button @click="llamarCliente(i)"
                  style="padding:3px 8px;background:#059669;color:#fff;font-size:10px;font-weight:700;border-radius:6px;border:none;cursor:pointer;">📞 Llamar</button>
          <button @click="listaEspera.splice(i,1)"
                  style="padding:3px 7px;background:#F3F4F6;color:#9CA3AF;font-size:11px;border-radius:6px;border:none;cursor:pointer;">✕</button>
        </div>
      </template>
    </div>

    <button @click="showListaEspera=false"
            style="width:100%;margin-top:14px;padding:9px;background:#F3F4F6;color:#374151;font-size:12px;font-weight:600;border-radius:10px;border:none;cursor:pointer;">Cerrar</button>
  </div>
</div>

{{-- Modal QR --}}
<div x-show="qrModal" x-cloak
     style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
     @click.self="qrModal=false">
  <div style="background:#fff;border-radius:20px;padding:24px;width:280px;display:flex;flex-direction:column;align-items:center;gap:16px;box-shadow:0 24px 64px rgba(0,0,0,.25);">
    <p style="font-size:14px;font-weight:700;color:#111827;">QR — Mesa <span x-text="qrMesa"></span></p>
    <img :src="qrSrc" style="width:180px;height:180px;border-radius:12px;" alt="QR">
    <div style="display:flex;gap:8px;width:100%;">
      <a :href="qrSrc" :download="'mesa-'+qrMesa+'.png'"
         style="flex:1;text-align:center;padding:8px;background:#2563EB;color:#fff;font-size:12px;font-weight:700;border-radius:10px;text-decoration:none;">
        Descargar
      </a>
      <button @click="qrModal=false"
              style="flex:1;padding:8px;background:#F3F4F6;color:#374151;font-size:12px;font-weight:600;border-radius:10px;border:none;cursor:pointer;">
        Cerrar
      </button>
    </div>
  </div>
</div>

{{-- Modal cambio de mesa --}}
<div x-show="showCambioMesa"
     style="position:fixed;inset:0;z-index:55;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
     @click.self="showCambioMesa=false">
  <div style="background:#fff;border-radius:18px;padding:22px;width:320px;max-width:92vw;box-shadow:0 24px 64px rgba(0,0,0,.25);">
    <div style="font-size:14px;font-weight:800;color:#111827;margin-bottom:12px;">🔄 Cambiar mesa — <span x-text="mesaCambioOrigen?.numero"></span></div>
    <div style="font-size:11px;color:#6B7280;margin-bottom:10px;">Seleccioná la mesa de destino:</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;max-height:200px;overflow-y:auto;">
      <template x-for="m in mesasLibres" :key="m.numero">
        <button @click="confirmarCambio(m)"
                style="padding:10px 4px;border-radius:8px;border:1px solid #10B981;background:#F0FDF4;color:#064E3B;font-size:12px;font-weight:800;cursor:pointer;">
          <span x-text="m.numero"></span>
        </button>
      </template>
    </div>
    <button @click="showCambioMesa=false"
            style="width:100%;margin-top:12px;padding:8px;background:#F3F4F6;color:#374151;font-size:12px;font-weight:600;border-radius:10px;border:none;cursor:pointer;">Cancelar</button>
  </div>
</div>

<style>
@keyframes pulse-vivo    { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
@keyframes pulse-alerta  { 0%,100%{opacity:1} 50%{opacity:.35} }
@keyframes ring-alerta   { 0%,100%{opacity:.7;transform:scale(1)} 50%{opacity:1;transform:scale(1.012)} }
.dot-animated            { animation:pulse-vivo 2s infinite; }

/* Acciones hover sobre tarjeta de mesa */
.mesa-card-wrap:hover .mesa-quick-actions { opacity:1 !important; pointer-events:auto !important; }
/* Alternativa: agregar grupo hover directo en el div de la card */
div[x-data] div[style*="border-radius:16px"]:hover > .mesa-quick-actions { opacity:1 !important; pointer-events:auto !important; }
</style>

<script>
const MESAS_DATA  = @json($mesasData);
const CATALOG_URL = @json($catalogUrl);
const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]').content;

function mesasBoard() {
    return {
        mesas: [], pedidos: [], sectores: [],
        sector: 'todos', buscar: '',
        mesaActiva: null,
        modoUnion: false,
        seleccionUnion: [],
        unionesActivas: {},
        qrModal: false, qrMesa: null, qrSrc: '',
        ticker: 0,
        // Lista de espera
        showListaEspera: false,
        listaEspera: [],
        esperaInput: '',
        // Mozo por mesa (se guarda en el servidor: lo ven todas las tablets)
        mozos: {},
        mozoInput: '',
        // Cambio de mesa
        showCambioMesa: false,
        mesaCambioOrigen: null,

        init() {
            this.mesas    = MESAS_DATA.mesas;
            this.sectores = MESAS_DATA.sectores;
            this.pedidos  = MESAS_DATA.pedidos;
            /* Del SERVIDOR. Antes esto salia de `localStorage`, o sea de UN
               navegador: la tablet de la puerta apuntaba a alguien en la lista
               de espera y la de la barra no lo veia, y todo se perdia al
               limpiar el navegador. Ahora llega resuelto desde el proyecto y
               las dos tablets arrancan viendo lo mismo. */
            const SALON = @json($salon ?? ['mozos' => [], 'uniones' => [], 'espera' => []]);
            this.unionesActivas = SALON.uniones || {};
            this.mozos         = SALON.mozos   || {};
            this.listaEspera   = Array.isArray(SALON.espera) ? SALON.espera : [];
            setInterval(() => this.reload(), 20000);
            setInterval(() => this.ticker++, 1000);
        },

        // ── Score AVAN ─────────────────────────────────────────────────────────
        get score() {
            this.ticker;
            const total = this.mesas.length;
            if (!total) return 100;
            const alertas = this.mesas.filter(m => this.tieneAlerta(m)).length;
            const muyDemorados = this.mesas.filter(m => this.minutosMesa(m) > 60 && this.estadoMesa(m) !== 'libre').length;
            const pen = Math.round((alertas / total) * 40 + (muyDemorados / total) * 20);
            return Math.max(0, 100 - pen);
        },
        get scoreColor() {
            const s = this.score;
            return s >= 80 ? '#059669' : s >= 60 ? '#D97706' : '#EF4444';
        },
        get scoreLabel() {
            const s = this.score;
            return s >= 80 ? 'Excelente' : s >= 60 ? 'Con alertas' : 'Crítico';
        },

        // ── Filtro ─────────────────────────────────────────────────────────────
        get mesasFiltradas() {
            const esclavas = Object.values(this.unionesActivas).flat();
            return this.mesas.filter(m => {
                const enSec  = this.sector === 'todos' || m.sector === this.sector;
                const enBus  = !this.buscar || String(m.numero).toLowerCase().includes(this.buscar.toLowerCase())
                                           || (this.clienteMesa(m)||'').toLowerCase().includes(this.buscar.toLowerCase());
                const noEsc  = this.modoUnion || !esclavas.includes(String(m.numero));
                return enSec && enBus && noEsc;
            });
        },

        // ── Estado de la mesa ──────────────────────────────────────────────────
        estadoMesa(mesa) {
            const nums = [String(mesa.numero), ...(this.unionesActivas[String(mesa.numero)] || [])];
            const ps   = this.pedidos.filter(p => nums.includes(String(p.table_number)));
            if (!ps.length) return 'libre';
            if (ps.some(p => p.kitchen_status === 'ready'))   return 'ready';
            if (ps.some(p => p.kitchen_status === 'cooking')) return 'cooking';
            return 'pedido';
        },

        // ── Alerta activa ──────────────────────────────────────────────────────
        tieneAlerta(mesa) {
            this.ticker;
            const st  = this.estadoMesa(mesa);
            const min = this.minutosMesa(mesa);
            // Alerta si lleva >45 min sin que esté en ready
            if (st === 'pedido' && min >= 45) return true;
            // Alerta si lleva >30 min en cocina (preparación larga)
            if (st === 'cooking' && min >= 30) return true;
            return false;
        },

        // ── Estilos ────────────────────────────────────────────────────────────
        dotColor(mesa) {
            if (this.tieneAlerta(mesa)) return '#EF4444';
            return { libre:'#10B981', pedido:'#F59E0B', cooking:'#F97316', ready:'#3B82F6' }[this.estadoMesa(mesa)];
        },

        bgMesa(mesa) {
            if (this.tieneAlerta(mesa)) return '#EF4444';
            return { libre:'#10B981', pedido:'#F59E0B', cooking:'#F97316', ready:'#3B82F6' }[this.estadoMesa(mesa)];
        },

        cardStyle(mesa) {
            const paleta = {
                libre:   { bg:'#F0FDF4', border:'#A7F3D0', text:'#064E3B' },
                pedido:  { bg:'#FFFBEB', border:'#FCD34D', text:'#92400E' },
                cooking: { bg:'#FFF7ED', border:'#FDBA74', text:'#7C2D12' },
                ready:   { bg:'#EFF6FF', border:'#93C5FD', text:'#1E40AF' },
            };
            const alerta = this.tieneAlerta(mesa);
            const st  = alerta ? { bg:'#FEF2F2', border:'#FCA5A5', text:'#7F1D1D' } : paleta[this.estadoMesa(mesa)];
            const activo  = this.mesaActiva?.numero === mesa.numero;
            const enUnion = this.modoUnion && this.seleccionUnion.includes(mesa.numero);
            let sombra = activo ? `box-shadow:0 0 0 3px ${st.border};` : '';
            if (enUnion) sombra = `box-shadow:0 0 0 3px #7C3AED;`;
            return `background:${st.bg};border:2px solid ${st.border};color:${st.text};${sombra}`;
        },

        badgePedidoStyle(mesa) {
            const st = this.estadoMesa(mesa);
            if (st === 'ready')   return 'background:#2563EB;color:#fff;';
            if (st === 'cooking') return 'background:#EA580C;color:#fff;';
            return 'background:#D97706;color:#fff;';
        },

        urgenciaColor(mesa) {
            this.ticker;
            const min = this.minutosMesa(mesa);
            if (min >= 45) return 'color:#EF4444;font-size:14px;';
            if (min >= 20) return 'color:#F97316;';
            return 'color:#374151;';
        },

        estadoBadgeStyle(mesa) {
            if (this.tieneAlerta(mesa)) return 'background:#FEE2E2;color:#991B1B;';
            const paleta = {
                libre:   'background:#D1FAE5;color:#065F46;',
                pedido:  'background:#FEF08A;color:#713F12;',
                cooking: 'background:#FED7AA;color:#7C2D12;',
                ready:   'background:#BFDBFE;color:#1E3A8A;',
            };
            return paleta[this.estadoMesa(mesa)] || paleta.libre;
        },

        // ── Timers ────────────────────────────────────────────────────────────
        minutosMesa(mesa) {
            this.ticker;
            const ps = this.pedidosMesaNumero(mesa);
            if (!ps.length) return 0;
            // Usa el pedido más antiguo de ESTA mesa específica
            const mas_antiguo = ps.sort((a,b) => new Date(a.created_at)-new Date(b.created_at))[0];
            return Math.floor((Date.now() - new Date(mas_antiguo.created_at)) / 60000);
        },

        tiempoMesa(mesa) {
            this.ticker;
            const ps = this.pedidosMesaNumero(mesa);
            if (!ps.length) return null;
            const min = this.minutosMesa(mesa);
            if (min < 1)  return '< 1m';
            if (min < 60) return min + 'm';
            return Math.floor(min/60) + 'h ' + (min%60) + 'm';
        },

        // ── Pedidos de mesa ────────────────────────────────────────────────────
        pedidosMesaNumero(mesa) {
            const nums = [String(mesa.numero), ...(this.unionesActivas[String(mesa.numero)] || [])];
            return this.pedidos.filter(p => nums.includes(String(p.table_number)));
        },

        cuentaPedidos(mesa) { return this.pedidosMesaNumero(mesa).length; },

        totalMesaCard(mesa) {
            return this.pedidosMesaNumero(mesa).reduce((s,p) => s + parseFloat(p.total||0), 0);
        },

        clienteMesa(mesa) {
            const ps = this.pedidosMesaNumero(mesa);
            const cl = ps.find(p => p.client_name && p.client_name !== 'Cliente general');
            return cl?.client_name || null;
        },

        estadoLabel(mesa) {
            if (this.tieneAlerta(mesa)) return '⚠ Alerta';
            return { libre:'Libre', pedido:'Con pedido', cooking:'En cocina', ready:'Listo ✓' }[this.estadoMesa(mesa)] || '';
        },

        // ── Mozos ──────────────────────────────────────────────────────────────
        mozoMesa(mesa) {
            return this.mozos[String(mesa.numero)] || null;
        },

        iniciales(nombre) {
            if (!nombre) return '';
            return nombre.trim().split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
        },

        asignarMozo() {
            if (!this.mesaActiva || !this.mozoInput.trim()) return;
            this.mozos[String(this.mesaActiva.numero)] = this.mozoInput.trim();
            this.guardarSalon();
            this.mozos = { ...this.mozos };
            this.mozoInput = '';
        },

        // ── Lista de espera ────────────────────────────────────────────────────
        agregarEspera() {
            if (!this.esperaInput.trim()) return;
            this.listaEspera.push({ nombre: this.esperaInput.trim(), at: Date.now() });
            this.guardarSalon();
            this.esperaInput = '';
        },

        tiempoEspera(at) {
            return Math.floor((Date.now() - at) / 60000);
        },

        llamarCliente(i) {
            const c = this.listaEspera[i];
            bxAviso('Llamar a ' + c.nombre + ' — espera ' + this.tiempoEspera(c.at) + ' min', 'error');
        },

        // ── Cambio de mesa ─────────────────────────────────────────────────────
        get mesasLibres() {
            return this.mesas.filter(m => this.estadoMesa(m) === 'libre');
        },

        cambiarMesa(mesa) {
            this.mesaCambioOrigen = mesa;
            this.showCambioMesa   = true;
        },

        async confirmarCambio(destino) {
            if (!this.mesaCambioOrigen) return;
            const origen = this.mesaCambioOrigen;
            const ps = this.pedidosMesaNumero(origen);
            // Actualiza localmente los pedidos
            ps.forEach(p => { p.table_number = String(destino.numero); });
            this.pedidos = [...this.pedidos];
            this.showCambioMesa   = false;
            this.mesaCambioOrigen = null;
        },

        cobrarDirecto(mesa) {
            window.location.href = '{{ route('bixosales.pos') }}?mesa=' + mesa.numero;
        },

        // ── Panel detalle ──────────────────────────────────────────────────────
        get pedidosMesa() {
            if (!this.mesaActiva) return [];
            return this.pedidosMesaNumero(this.mesaActiva);
        },

        get totalMesaPanel() {
            return this.pedidosMesa.reduce((s,p) => s + parseFloat(p.total||0), 0);
        },

        seleccionar(mesa) {
            this.mesaActiva = this.mesaActiva?.numero === mesa.numero ? null : mesa;
            if (this.mesaActiva) {
                this.mozoInput = this.mozos[String(mesa.numero)] || '';
            }
        },

        // ── Stats ──────────────────────────────────────────────────────────────
        get stats() {
            const vis = this.mesasFiltradas;
            return {
                libres:       vis.filter(m => this.estadoMesa(m) === 'libre').length,
                ocupadas:     vis.filter(m => this.estadoMesa(m) === 'pedido').length,
                enCocina:     vis.filter(m => this.estadoMesa(m) === 'cooking').length,
                listas:       vis.filter(m => this.estadoMesa(m) === 'ready').length,
                alertas:      vis.filter(m => this.tieneAlerta(m)).length,
                totalPedidos: this.pedidos.length,
                totalConsumo: this.pedidos.reduce((s,p) => s + parseFloat(p.total||0), 0),
            };
        },

        // ── Unir mesas ─────────────────────────────────────────────────────────
        mesasUnidas(mesa) { return this.unionesActivas[String(mesa.numero)] || []; },

        toggleUnion(mesa) {
            const n = mesa.numero;
            const idx = this.seleccionUnion.indexOf(n);
            if (idx >= 0) this.seleccionUnion.splice(idx, 1);
            else this.seleccionUnion.push(n);
        },

        confirmarUnion() {
            if (this.seleccionUnion.length < 2) return;
            const principal = this.seleccionUnion[0];
            const esclavas  = this.seleccionUnion.slice(1).map(String);
            Object.keys(this.unionesActivas).forEach(k => {
                this.unionesActivas[k] = this.unionesActivas[k].filter(m => !esclavas.includes(String(m)));
            });
            this.unionesActivas[String(principal)] = [
                ...(this.unionesActivas[String(principal)] || []), ...esclavas,
            ];
            this.persistirUniones();
            this.modoUnion = false;
            this.seleccionUnion = [];
        },

        desunirMesa(mesa) {
            delete this.unionesActivas[String(mesa.numero)];
            this.persistirUniones();
        },

        persistirUniones() {
            this.guardarSalon();
            this.unionesActivas = { ...this.unionesActivas };
        },

        // ── Cocina ─────────────────────────────────────────────────────────────
        timeAgo(iso) {
            const min = Math.floor((Date.now() - new Date(iso)) / 60000);
            if (min < 1) return 'Ahora';
            if (min < 60) return 'Hace ' + min + ' min';
            return 'Hace ' + Math.floor(min/60) + 'h';
        },

        kitchenLabel(s) {
            return { pending:'Nuevo', cooking:'Preparando', ready:'Listo', served:'Entregado' }[s] || s;
        },

        badgeKitchenStyle(s) {
            return {
                pending:  'background:#FEF3C7;color:#92400E;',
                cooking:  'background:#FED7AA;color:#7C2D12;',
                ready:    'background:#D1FAE5;color:#065F46;',
                served:   'background:#F3F4F6;color:#6B7280;',
            }[s] || '';
        },

        async avanzarKitchen(pedido, newStatus) {
            const res = await fetch(`/bixosales/pedidos/${pedido.id}/kitchen`, {
                method: 'PATCH',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept':'application/json' },
                body: JSON.stringify({ kitchen_status: newStatus }),
            });
            if (res.ok) {
                const idx = this.pedidos.findIndex(p => p.id === pedido.id);
                if (idx >= 0) {
                    this.pedidos[idx].kitchen_status = newStatus;
                    if (newStatus === 'served') this.pedidos.splice(idx, 1);
                    this.pedidos = [...this.pedidos];
                }
            } else {
                /* Un 403 dejaba el pedido donde estaba sin decir nada: el mozo
                   pulsaba otra vez creyendo que no habia registrado. */
                const d = await res.json().catch(() => ({}));
                bxAviso(d.message || 'No se pudo cambiar el estado del pedido.', 'error');
            }
        },

        cobrarMesa() {
            if (this.mesaActiva) {
                window.location.href = '{{ route('bixosales.pos') }}?mesa=' + this.mesaActiva.numero;
            }
        },

        verQr(mesa) {
            const url = CATALOG_URL + '?mesa=' + mesa.numero;
            this.qrMesa = mesa.numero;
            /* QR LOCAL. Se pedia a api.qrserver.com, lo que mandaba a un
               tercero la URL del catalogo del negocio y dejaba el QR roto —sin
               explicacion— si el servicio caia o el local no tenia internet.
               `createDataURL` devuelve una imagen incrustada, asi que el <img>
               y el enlace de descarga siguen funcionando igual. */
            try {
                const qr = qrcode(0, 'M');
                qr.addData(url);
                qr.make();
                this.qrSrc = qr.createDataURL(8, 8);
            } catch (e) {
                this.qrSrc = '';
                bxAviso('No se pudo generar el QR de la mesa.', 'error');
            }
            this.qrModal = true;
        },

        /* Guarda el estado del salon en el servidor. Se manda entero —son
           tres objetos pequenos— para no tener tres rutas; si falla, se avisa
           y no se pierde en silencio lo que el mozo acaba de apuntar. */
        async guardarSalon() {
            try {
                const res = await fetch(@js(route('bixosales.mesas.estado')), {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept':'application/json' },
                    body: JSON.stringify({
                        mozos:   this.mozos,
                        uniones: this.unionesActivas,
                        espera:  this.listaEspera,
                    }),
                });
                if (! res.ok) {
                    const d = await res.json().catch(() => ({}));
                    bxAviso(d.message || 'No se pudo guardar el estado del salón.', 'error');
                }
            } catch (e) {
                bxAviso('Sin conexión: el cambio no se guardó para las demás tablets.', 'error');
            }
        },

        async reload() {
            try {
                const res = await fetch('/bixosales/mesas/data', { headers:{ 'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN } });
                if (res.ok) {
                    const d = await res.json();
                    this.pedidos = d.pedidos;
                    if (this.mesaActiva) this.mesaActiva = { ...this.mesaActiva };
                }
            } catch(e) {}
        },
    };
}

// Hover en tarjetas para mostrar acciones rápidas
document.addEventListener('alpine:init', () => {});
document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('mouseover', e => {
        const card = e.target.closest('[style*="border-radius:16px"]');
        if (card) {
            const qa = card.querySelector('.mesa-quick-actions');
            if (qa) { qa.style.opacity='1'; qa.style.pointerEvents='auto'; }
        }
    });
    document.body.addEventListener('mouseout', e => {
        const card = e.target.closest('[style*="border-radius:16px"]');
        if (card && !card.contains(e.relatedTarget)) {
            const qa = card.querySelector('.mesa-quick-actions');
            if (qa) { qa.style.opacity='0'; qa.style.pointerEvents='none'; }
        }
    });
});
</script>
</div>
{{-- El mismo dibujante de QR que usan comprobantes y guias. --}}
<script>{!! file_get_contents(public_path('js/qrcode.min.js')) !!}</script>
</x-portal-layout>
