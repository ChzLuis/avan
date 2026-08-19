<x-portal-layout layout="comercial" :project="$project" pageTitle="Caja">
<div x-data="cajaPage()" x-init="init()" style="display:flex;flex-direction:column;height:100%;background:#F8F9FB;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Caja / Tesorería</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="caja ? 'Abierta desde ' + caja.opened_at : 'Sin caja abierta'"></p>
    </div>
    <div style="display:flex;gap:8px;">
        <template x-if="!caja">
            <button @click="modalAbrir=true"
                    style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:#10B981;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Abrir Caja
            </button>
        </template>
        <template x-if="caja">
            <div style="display:flex;gap:8px;">
                <button @click="modalMovimiento=true"
                        style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:#F59E0B;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Movimiento
                </button>
                <button @click="modalCerrar=true"
                        style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:#EF4444;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cerrar Caja
                </button>
            </div>
        </template>
    </div>
</div>

{{-- Sin caja --}}
<template x-if="!caja">
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;color:#9CA3AF;">
        <div style="width:72px;height:72px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;">
            <svg style="width:32px;height:32px;opacity:.4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div style="text-align:center;">
            <p style="font-size:15px;font-weight:600;color:#374151;">No hay caja abierta</p>
            <p style="font-size:13px;color:#9CA3AF;margin-top:4px;">Abre la caja para registrar movimientos y ventas</p>
        </div>
        <button @click="modalAbrir=true"
                style="padding:10px 24px;background:#10B981;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
            Abrir Caja Ahora
        </button>
    </div>
</template>

{{-- Caja Abierta --}}
<template x-if="caja">
    <div style="flex:1;overflow:auto;display:flex;flex-direction:row;gap:0;min-height:0;">

        {{-- Panel izquierdo --}}
        <div style="flex:1;padding:20px;overflow:auto;display:flex;flex-direction:column;gap:16px;">

            {{-- 4 KPIs --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
                <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;text-align:center;">
                    <p style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Apertura</p>
                    <p style="font-size:18px;font-weight:800;color:#111827;" x-text="'S/ ' + caja.monto_apertura.toFixed(2)"></p>
                </div>
                <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:14px;text-align:center;">
                    <p style="font-size:11px;color:#059669;margin-bottom:4px;">Ventas</p>
                    <p style="font-size:18px;font-weight:800;color:#065F46;" x-text="'S/ ' + caja.total_ventas.toFixed(2)"></p>
                </div>
                <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:14px;text-align:center;">
                    <p style="font-size:11px;color:#2563EB;margin-bottom:4px;">Ingresos</p>
                    <p style="font-size:18px;font-weight:800;color:#1E40AF;" x-text="'S/ ' + caja.total_ingresos.toFixed(2)"></p>
                </div>
                <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px;text-align:center;">
                    <p style="font-size:11px;color:#EF4444;margin-bottom:4px;">Egresos</p>
                    <p style="font-size:18px;font-weight:800;color:#991B1B;" x-text="'S/ ' + caja.total_egresos.toFixed(2)"></p>
                </div>
            </div>

            {{-- Saldo esperado --}}
            <div style="background:linear-gradient(135deg,#1D4ED8,#2563EB);border-radius:14px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="font-size:11px;color:rgba(255,255,255,.7);font-weight:500;">Saldo esperado en caja</p>
                    <p style="font-size:32px;font-weight:900;color:#fff;margin-top:4px;" x-text="'S/ ' + caja.saldo_esperado.toFixed(2)"></p>
                </div>
                <svg style="width:48px;height:48px;opacity:.2;" fill="none" stroke="#fff" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>

            {{-- Movimientos --}}
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <p style="font-size:13px;font-weight:700;color:#111827;">Movimientos</p>
                    <span style="font-size:11px;color:#9CA3AF;" x-text="caja.movimientos.length + ' registros'"></span>
                </div>
                <div x-show="caja.movimientos.length === 0"
                     style="text-align:center;padding:24px;background:#fff;border:1px solid #E5E8EF;border-radius:12px;font-size:13px;color:#9CA3AF;">
                    Sin movimientos registrados aún
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <template x-for="m in [...caja.movimientos].reverse()" :key="m.id">
                        <div style="background:#fff;border:1px solid #E5E8EF;border-radius:10px;display:flex;align-items:center;padding:10px 14px;gap:10px;">
                            <div :style="m.tipo==='egreso' ? 'background:#FEE2E2;color:#EF4444;' : 'background:#ECFDF5;color:#10B981;'"
                                 style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <template x-if="m.tipo==='egreso'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </template>
                                    <template x-if="m.tipo!=='egreso'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </template>
                                </svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:500;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="m.concepto"></p>
                                <p style="font-size:11px;color:#9CA3AF;">
                                    <span x-text="m.created_at"></span>
                                    <span x-show="m.metodo_pago" x-text="' · ' + m.metodo_pago"></span>
                                </p>
                            </div>
                            <div :style="m.tipo==='egreso' ? 'color:#EF4444;' : 'color:#10B981;'"
                                 style="font-weight:700;font-size:14px;flex-shrink:0;"
                                 x-text="(m.tipo==='egreso'?'- ':'+ ') + 'S/ ' + m.monto.toFixed(2)">
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Panel derecho: historial --}}
        <div style="width:260px;flex-shrink:0;background:#fff;border-left:1px solid #E5E8EF;padding:16px;overflow:auto;">
            <p style="font-size:12px;font-weight:700;color:#374151;margin-bottom:12px;">Cierres anteriores</p>
            <div x-show="historial.length===0" style="font-size:12px;color:#9CA3AF;text-align:center;padding:24px 0;">Sin historial</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <template x-for="h in historial" :key="h.id">
                    <div style="background:#F8F9FB;border:1px solid #E5E8EF;border-radius:10px;padding:10px 12px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
                            <span style="font-size:12px;font-weight:600;color:#374151;" x-text="h.opened_at"></span>
                            <span :style="h.diferencia>=0?'color:#10B981;':'color:#EF4444;'"
                                  style="font-size:12px;font-weight:700;"
                                  x-text="(h.diferencia>=0?'+':'')+'S/ '+h.diferencia.toFixed(2)"></span>
                        </div>
                        <div style="font-size:11px;color:#6B7280;display:flex;justify-content:space-between;">
                            <span x-text="h.user_name"></span>
                            <span x-text="h.duracion"></span>
                        </div>
                        <div style="font-size:10px;color:#9CA3AF;display:flex;justify-content:space-between;margin-top:4px;">
                            <span>Cierre: S/ <span x-text="h.monto_cierre.toFixed(2)"></span></span>
                            <span>Esp: S/ <span x-text="h.monto_esperado.toFixed(2)"></span></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

{{-- ── MODALES ── --}}
{{-- Abrir Caja --}}
<div x-show="modalAbrir" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="modalAbrir=false"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:380px;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;">
            <p style="font-size:15px;font-weight:700;color:#111827;">Apertura de Caja</p>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Monto inicial (S/)</label>
                <input x-model.number="abrirForm.monto" type="number" min="0" step="0.50" placeholder="0.00"
                       style="width:100%;font-size:24px;font-weight:800;border:2px solid #E5E8EF;border-radius:10px;padding:12px 16px;text-align:center;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#10B981'" onblur="this.style.borderColor='#E5E8EF'">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Notas</label>
                <textarea x-model="abrirForm.notas" rows="2" placeholder="Observaciones..."
                          style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;resize:none;font-family:inherit;"></textarea>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;justify-content:flex-end;">
            <button @click="modalAbrir=false" style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button @click="abrirCaja()" style="padding:8px 20px;font-size:13px;font-weight:600;background:#10B981;color:#fff;border:none;border-radius:8px;cursor:pointer;">Abrir Caja</button>
        </div>
    </div>
</div>

{{-- Movimiento --}}
<div x-show="modalMovimiento" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="modalMovimiento=false"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:380px;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;">
            <p style="font-size:15px;font-weight:700;color:#111827;">Registrar Movimiento</p>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <button @click="movForm.tipo='ingreso'"
                        :style="movForm.tipo==='ingreso' ? 'background:#10B981;color:#fff;border-color:#10B981;' : 'background:#fff;color:#374151;border-color:#E5E8EF;'"
                        style="padding:9px;border-radius:8px;font-size:13px;font-weight:600;border:2px solid;cursor:pointer;transition:all .12s;">
                    Ingreso
                </button>
                <button @click="movForm.tipo='egreso'"
                        :style="movForm.tipo==='egreso' ? 'background:#EF4444;color:#fff;border-color:#EF4444;' : 'background:#fff;color:#374151;border-color:#E5E8EF;'"
                        style="padding:9px;border-radius:8px;font-size:13px;font-weight:600;border:2px solid;cursor:pointer;transition:all .12s;">
                    Egreso
                </button>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Concepto *</label>
                <input x-model="movForm.concepto" type="text" placeholder="Ej: pago proveedor, propina..."
                       style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#F59E0B'" onblur="this.style.borderColor='#E5E8EF'">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Monto (S/) *</label>
                <input x-model.number="movForm.monto" type="number" min="0.01" step="0.50" placeholder="0.00"
                       style="width:100%;font-size:22px;font-weight:800;border:2px solid #E5E8EF;border-radius:10px;padding:10px 16px;text-align:center;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#F59E0B'" onblur="this.style.borderColor='#E5E8EF'">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Método de pago</label>
                <select x-model="movForm.metodo_pago"
                        style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;background:#fff;">
                    <option value="">-- Opcional --</option>
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm }}">{{ $pm }}</option>
                    @endforeach
                    <option value="Efectivo">Efectivo</option>
                    <option value="Yape">Yape</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;justify-content:flex-end;">
            <button @click="modalMovimiento=false" style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button @click="registrarMovimiento()" style="padding:8px 20px;font-size:13px;font-weight:600;background:#F59E0B;color:#fff;border:none;border-radius:8px;cursor:pointer;">Registrar</button>
        </div>
    </div>
</div>

{{-- Cerrar Caja --}}
<div x-show="modalCerrar" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="modalCerrar=false"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:380px;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;">
            <p style="font-size:15px;font-weight:700;color:#111827;">Arqueo y Cierre de Caja</p>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div style="background:#F8F9FB;border:1px solid #E5E8EF;border-radius:10px;padding:12px;display:flex;flex-direction:column;gap:6px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#6B7280;">Apertura:</span>
                    <span style="font-weight:600;" x-text="'S/ ' + (caja?.monto_apertura||0).toFixed(2)"></span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#6B7280;">Ventas:</span>
                    <span style="font-weight:600;color:#10B981;" x-text="'+ S/ ' + (caja?.total_ventas||0).toFixed(2)"></span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#6B7280;">Ingresos:</span>
                    <span style="font-weight:600;color:#2563EB;" x-text="'+ S/ ' + (caja?.total_ingresos||0).toFixed(2)"></span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#6B7280;">Egresos:</span>
                    <span style="font-weight:600;color:#EF4444;" x-text="'- S/ ' + (caja?.total_egresos||0).toFixed(2)"></span>
                </div>
                <div style="border-top:1px solid #E5E8EF;padding-top:6px;display:flex;justify-content:space-between;font-weight:800;">
                    <span>Esperado:</span>
                    <span x-text="'S/ ' + (caja?.saldo_esperado||0).toFixed(2)"></span>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Efectivo contado (S/) *</label>
                <input x-model.number="cerrarForm.monto" type="number" min="0" step="0.50" placeholder="0.00"
                       style="width:100%;font-size:24px;font-weight:800;border:2px solid #E5E8EF;border-radius:10px;padding:12px 16px;text-align:center;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#EF4444'" onblur="this.style.borderColor='#E5E8EF'">
            </div>
            <div x-show="cerrarForm.monto !== null" style="text-align:center;">
                <span style="font-size:12px;color:#6B7280;">Diferencia: </span>
                <span :style="(cerrarForm.monto-(caja?.saldo_esperado||0))>=0?'color:#10B981;':'color:#EF4444;'"
                      style="font-weight:800;font-size:16px;"
                      x-text="((cerrarForm.monto-(caja?.saldo_esperado||0))>=0?'+':'')+' S/ '+((cerrarForm.monto||0)-(caja?.saldo_esperado||0)).toFixed(2)">
                </span>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Notas de cierre</label>
                <textarea x-model="cerrarForm.notas" rows="2"
                          style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;resize:none;font-family:inherit;"></textarea>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;justify-content:flex-end;">
            <button @click="modalCerrar=false" style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button @click="cerrarCaja()" style="padding:8px 20px;font-size:13px;font-weight:600;background:#EF4444;color:#fff;border:none;border-radius:8px;cursor:pointer;">Cerrar Caja</button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div x-show="toast.show" x-cloak x-transition
     :style="toast.type==='error' ? 'background:#EF4444;' : 'background:#10B981;'"
     style="position:fixed;bottom:20px;right:20px;color:#fff;font-size:13px;font-weight:500;padding:10px 18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:9999;"
     x-text="toast.msg">
</div>

</div>

@push('scripts')
<script>
function cajaPage() {
    return {
        caja: @json($cajaJson),
        historial: @json($historialJson),
        modalAbrir: false, modalMovimiento: false, modalCerrar: false,
        abrirForm: { monto: 0, notas: '' },
        movForm: { tipo: 'ingreso', concepto: '', monto: null, metodo_pago: '' },
        cerrarForm: { monto: null, notas: '' },
        toast: { show: false, msg: '', type: 'ok' },
        init() {},

        async abrirCaja() {
            const resp = await fetch('/bixosales/caja/abrir', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ monto_apertura: this.abrirForm.monto, notas_apertura: this.abrirForm.notas })
            });
            const data = await resp.json();
            if (!resp.ok) { this.showToast(data.message||'Error','error'); return; }
            this.caja = data.caja; this.modalAbrir = false;
            this.showToast('Caja abierta correctamente');
        },

        async registrarMovimiento() {
            if (!this.movForm.concepto || !this.movForm.monto) { this.showToast('Completa los campos','error'); return; }
            const resp = await fetch(`/bixosales/caja/${this.caja.id}/movimiento`, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(this.movForm)
            });
            const data = await resp.json();
            if (data.ok) {
                const m = data.movimiento;
                this.caja.movimientos.push({ id:m.id, tipo:m.tipo, concepto:m.concepto, monto:parseFloat(m.monto), metodo_pago:m.metodo_pago, created_at:new Date(m.created_at).toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}) });
                if (m.tipo==='ingreso') this.caja.total_ingresos += parseFloat(m.monto);
                if (m.tipo==='egreso')  this.caja.total_egresos  += parseFloat(m.monto);
                this.caja.saldo_esperado = this.caja.monto_apertura + this.caja.total_ventas + this.caja.total_ingresos - this.caja.total_egresos;
                this.modalMovimiento = false;
                this.movForm = { tipo:'ingreso', concepto:'', monto:null, metodo_pago:'' };
                this.showToast('Movimiento registrado');
            }
        },

        async cerrarCaja() {
            if (this.cerrarForm.monto===null) { this.showToast('Ingresa el monto contado','error'); return; }
            if (! await bxConfirmar({ descripcion: '¿Confirmar cierre de caja?' })) return;
            const resp = await fetch(`/bixosales/caja/${this.caja.id}/cerrar`, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ monto_cierre: this.cerrarForm.monto, notas_cierre: this.cerrarForm.notas })
            });
            const data = await resp.json();
            if (data.ok) { this.caja=null; this.modalCerrar=false; this.showToast('Caja cerrada'); setTimeout(()=>location.reload(),1500); }
        },

        showToast(msg, type='ok') {
            this.toast = { show:true, msg, type };
            setTimeout(()=>{ this.toast.show=false; }, 3000);
        },
    };
}
</script>
@endpush
</x-portal-layout>
