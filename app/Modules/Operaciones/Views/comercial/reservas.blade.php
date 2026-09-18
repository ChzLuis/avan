<x-portal-layout layout="comercial" :project="$project" pageTitle="Reservas">
<div x-data="reservasPage()" x-init="init()" style="display:flex;flex-direction:column;height:100%;background:#F8F9FB;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Reservas</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="'Mostrando ' + reservasFiltradas.length + ' reservas'"></p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <div style="display:flex;border:1px solid #E5E8EF;border-radius:9px;overflow:hidden;">
            <button @click="vista='lista'"
                    :style="vista==='lista'?'background:#111827;color:#fff;':'background:#fff;color:#6B7280;'"
                    style="padding:6px 14px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .12s;">
                Lista
            </button>
            <button @click="vista='calendario'"
                    :style="vista==='calendario'?'background:#111827;color:#fff;':'background:#fff;color:#6B7280;'"
                    style="padding:6px 14px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .12s;">
                Calendario
            </button>
        </div>
        <button @click="abrirModal()"
                style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:#2563EB;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Reserva
        </button>
    </div>
</div>

{{-- Filtros --}}
<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 16px;background:#fff;border-bottom:1px solid #E5E8EF;flex-shrink:0;">
    <input x-model="buscar" type="text" placeholder="Buscar cliente..."
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 12px;outline:none;width:180px;font-family:inherit;"
           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
    <input x-model="filtroFecha" type="date"
           style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 12px;outline:none;font-family:inherit;"
           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
    <div style="display:flex;gap:4px;flex-wrap:wrap;">
        <template x-for="est in ['all','pending','confirmed','waiting','attended','cancelled']" :key="est">
            <button @click="filtroEstado=est"
                    :style="estadoBtnStyle(est)"
                    style="padding:4px 12px;border-radius:99px;font-size:11px;font-weight:600;border:1px solid;cursor:pointer;transition:all .12s;"
                    x-text="est==='all'?'Todas':estadoLabel(est)">
            </button>
        </template>
    </div>
    <button @click="filtroFecha='';buscar='';filtroEstado='all'"
            style="font-size:11px;color:#9CA3AF;background:none;border:none;cursor:pointer;margin-left:auto;"
            onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">
        Limpiar filtros
    </button>
</div>

{{-- Vista Lista --}}
<div x-show="vista==='lista'" style="flex:1;overflow:auto;">
    <div x-show="reservasFiltradas.length===0"
         style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:#9CA3AF;">
        <svg style="width:40px;height:40px;margin-bottom:10px;opacity:.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p style="font-size:13px;">No hay reservas para los filtros seleccionados</p>
    </div>

    <template x-for="grupo in reservasPorFecha" :key="grupo.fecha">
        <div style="margin-bottom:6px;">
            {{-- Encabezado de fecha --}}
            <div style="position:sticky;top:0;z-index:10;background:#F0F2F5;padding:7px 16px;border-bottom:1px solid #E5E8EF;display:flex;align-items:center;gap:8px;">
                <span style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.06em;"
                      x-text="formatFechaGrupo(grupo.fecha)"></span>
                <span style="font-size:10px;color:#9CA3AF;"
                      x-text="grupo.items.length + ' reserva' + (grupo.items.length>1?'s':'')"></span>
            </div>

            <template x-for="r in grupo.items" :key="r.id">
                <div @click="abrirDetalle(r)"
                     style="margin:8px 12px;background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:14px;display:flex;align-items:flex-start;gap:14px;cursor:pointer;transition:border-color .12s,box-shadow .12s;"
                     onmouseover="this.style.borderColor='#2563EB';this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)'"
                     onmouseout="this.style.borderColor='#E5E8EF';this.style.boxShadow='none'">

                    {{-- Hora --}}
                    <div style="text-align:center;width:50px;flex-shrink:0;">
                        <div style="font-size:16px;font-weight:800;color:#111827;" x-text="r.start_time?.substring(0,5)"></div>
                        <div style="font-size:10px;color:#9CA3AF;" x-text="r.end_time?.substring(0,5)"></div>
                    </div>

                    {{-- Separador vertical --}}
                    <div :style="'width:3px;border-radius:99px;align-self:stretch;flex-shrink:0;' + estadoBarStyle(r.status)"></div>

                    {{-- Info --}}
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-size:14px;font-weight:700;color:#111827;" x-text="r.client_name"></span>
                            <span :style="estadoPillStyle(r.status)"
                                  style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;border:1px solid;"
                                  x-text="estadoLabel(r.status)"></span>
                            <span x-show="r.occasion"
                                  style="font-size:10px;color:#D97706;background:#FFFBEB;padding:2px 8px;border-radius:99px;border:1px solid #FDE68A;"
                                  x-text="r.occasion"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;margin-top:4px;flex-wrap:wrap;">
                            <span x-show="r.guests" style="font-size:12px;color:#6B7280;"
                                  x-text="r.guests + ' persona' + (r.guests>1?'s':'')"></span>
                            <span x-show="r.zone" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#6B7280;">
                                <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span x-text="r.zone"></span>
                            </span>
                            <span x-show="r.table_number" style="font-size:12px;color:#6B7280;">
                                Mesa <span x-text="r.table_number"></span>
                            </span>
                            <span x-show="r.client_phone" style="font-size:12px;color:#6B7280;" x-text="r.client_phone"></span>
                        </div>
                        <p x-show="r.notes" style="font-size:11px;color:#9CA3AF;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                           x-text="r.notes"></p>
                    </div>

                    {{-- Acciones rápidas --}}
                    <div style="display:flex;gap:5px;flex-shrink:0;" @click.stop>
                        <button x-show="r.status==='pending'" @click.stop="cambiarEstado(r,'confirmed')"
                                style="font-size:11px;font-weight:600;background:#DCFCE7;color:#16A34A;padding:5px 10px;border-radius:7px;border:none;cursor:pointer;">
                            Confirmar
                        </button>
                        <button x-show="r.status==='confirmed'" @click.stop="cambiarEstado(r,'attended')"
                                style="font-size:11px;font-weight:600;background:#DBEAFE;color:#2563EB;padding:5px 10px;border-radius:7px;border:none;cursor:pointer;">
                            Atendida
                        </button>
                        <button x-show="!['attended','cancelled'].includes(r.status)" @click.stop="cambiarEstado(r,'cancelled')"
                                style="font-size:11px;font-weight:600;background:#FEE2E2;color:#EF4444;padding:5px 10px;border-radius:7px;border:none;cursor:pointer;">
                            Cancelar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

{{-- Vista Calendario --}}
<div x-show="vista==='calendario'" style="flex:1;overflow:auto;padding:16px;">
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:14px;overflow:hidden;">
        {{-- Nav mes --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #E5E8EF;">
            <button @click="mesCal--" style="padding:6px;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;line-height:0;">
                <svg style="width:16px;height:16px;color:#6B7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span style="font-size:14px;font-weight:700;color:#111827;" x-text="nombreMesCal"></span>
            <button @click="mesCal++" style="padding:6px;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;line-height:0;">
                <svg style="width:16px;height:16px;color:#6B7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        {{-- Días semana --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);border-bottom:1px solid #E5E8EF;">
            <template x-for="d in ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']" :key="d">
                <div style="padding:8px 0;text-align:center;font-size:11px;font-weight:600;color:#9CA3AF;" x-text="d"></div>
            </template>
        </div>
        {{-- Celdas --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);">
            <template x-for="(celda, i) in celdasCal" :key="i">
                <div :style="celda.fueraMes?'background:#F8F9FB;':'background:#fff;'"
                     style="min-height:80px;border-right:1px solid #F0F2F5;border-bottom:1px solid #F0F2F5;padding:5px;">
                    <div :style="celda.esHoy?'background:#2563EB;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;margin:0 auto 2px;':'color:#374151;text-align:center;margin-bottom:2px;'"
                         style="font-size:11px;font-weight:600;"
                         x-text="celda.dia"></div>
                    <template x-for="r in celda.reservas" :key="r.id">
                        <div @click="abrirDetalle(r)"
                             :style="estadoPillStyle(r.status)"
                             style="font-size:10px;font-weight:500;padding:2px 4px;border-radius:5px;border:1px solid;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;"
                             x-text="r.start_time?.substring(0,5) + ' ' + r.client_name">
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Modal Nueva/Editar Reserva --}}
<div x-show="modal" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="cerrarModal()"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:500px;overflow-y:auto;max-height:90vh;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:15px;font-weight:700;color:#111827;" x-text="form.id ? 'Editar Reserva' : 'Nueva Reserva'"></p>
            <button @click="cerrarModal()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Cliente *</label>
                    <input x-model="form.client_name" type="text" placeholder="Nombre del cliente"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Teléfono</label>
                    <input x-model="form.client_phone" type="tel" placeholder="999 999 999"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Fecha *</label>
                    <input x-model="form.date" type="date"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Hora inicio *</label>
                    <input x-model="form.start_time" type="time"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Hora fin</label>
                    <input x-model="form.end_time" type="time"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Personas</label>
                    <input x-model.number="form.guests" type="number" min="1" max="99" placeholder="2"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Zona</label>
                    <select x-model="form.zone"
                            style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;background:#fff;font-family:inherit;">
                        <option value="">-- Zona --</option>
                        @foreach($zones as $z)
                        <option value="{{ $z }}">{{ $z }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Mesa</label>
                    <select x-model="form.table_number"
                            style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;background:#fff;font-family:inherit;">
                        <option value="">-- Mesa --</option>
                        @for($i=1; $i<=$tableCount; $i++)
                        <option value="{{ $i }}">Mesa {{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Ocasión</label>
                <select x-model="form.occasion"
                        style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;background:#fff;font-family:inherit;">
                    <option value="">-- Ninguna --</option>
                    <option>Cumpleaños</option>
                    <option>Aniversario</option>
                    <option>Reunión de negocios</option>
                    <option>Despedida de soltero/a</option>
                    <option>Romántica</option>
                    <option>Familiar</option>
                    <option>Otro</option>
                </select>
            </div>
            <div x-show="form.id">
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Estado</label>
                <select x-model="form.status"
                        style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;background:#fff;font-family:inherit;">
                    <option value="pending">Pendiente</option>
                    <option value="confirmed">Confirmada</option>
                    <option value="waiting">En espera</option>
                    <option value="attended">Atendida</option>
                    <option value="cancelled">Cancelada</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Notas</label>
                <textarea x-model="form.notes" rows="2" placeholder="Preferencias, alergias, peticiones especiales..."
                          style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;resize:none;font-family:inherit;"></textarea>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;align-items:center;">
            <button x-show="form.id" @click="eliminar()"
                    style="font-size:12px;color:#EF4444;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;margin-right:auto;"
                    onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='none'">
                Eliminar
            </button>
            <button @click="cerrarModal()"
                    style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">
                Cancelar
            </button>
            <button @click="guardar()" :disabled="guardando"
                    style="padding:8px 20px;font-size:13px;font-weight:600;background:#2563EB;color:#fff;border:none;border-radius:8px;cursor:pointer;"
                    x-text="guardando ? 'Guardando...' : (form.id ? 'Actualizar' : 'Crear Reserva')">
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div x-show="toast.show" x-cloak x-transition
     :style="toast.type==='error'?'background:#EF4444;':'background:#10B981;'"
     style="position:fixed;bottom:20px;right:20px;color:#fff;font-size:13px;font-weight:500;padding:10px 18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:9999;"
     x-text="toast.msg">
</div>

</div>

@push('scripts')
<script>
function reservasPage() {
    const hoy = '{{ $today }}';

    return {
        reservas: @json($reservasJson),
        buscar: '',
        filtroFecha: hoy,
        filtroEstado: 'all',
        vista: 'lista',
        modal: false,
        guardando: false,
        toast: { show: false, msg: '', type: 'ok' },
        form: {},
        mesCal: 0,

        init() {},

        get reservasFiltradas() {
            return this.reservas.filter(r => {
                const matchBuscar = !this.buscar || r.client_name.toLowerCase().includes(this.buscar.toLowerCase()) || (r.client_phone||'').includes(this.buscar);
                const matchFecha  = !this.filtroFecha || r.date === this.filtroFecha;
                const matchEst    = this.filtroEstado === 'all' || r.status === this.filtroEstado;
                return matchBuscar && matchFecha && matchEst;
            }).sort((a, b) => (a.date + a.start_time).localeCompare(b.date + b.start_time));
        },

        get reservasPorFecha() {
            const grupos = {};
            this.reservasFiltradas.forEach(r => {
                if (!grupos[r.date]) grupos[r.date] = [];
                grupos[r.date].push(r);
            });
            return Object.entries(grupos).map(([fecha, items]) => ({ fecha, items }));
        },

        get celdasCal() {
            const ahora   = new Date();
            const año     = ahora.getFullYear();
            const mes     = ahora.getMonth() + this.mesCal;
            const primero = new Date(año, mes, 1);
            const ultimo  = new Date(año, mes + 1, 0);
            const celdas  = [];
            for (let i = 0; i < primero.getDay(); i++) {
                celdas.push({ dia: '', fueraMes: true, esHoy: false, reservas: [] });
            }
            for (let d = 1; d <= ultimo.getDate(); d++) {
                const fecha    = `${año}-${String(mes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const esHoy    = fecha === hoy;
                const reservas = this.reservas.filter(r => r.date === fecha);
                celdas.push({ dia: d, fecha, fueraMes: false, esHoy, reservas });
            }
            return celdas;
        },

        get nombreMesCal() {
            const ahora = new Date();
            const d = new Date(ahora.getFullYear(), ahora.getMonth() + this.mesCal, 1);
            return d.toLocaleDateString('es', { month: 'long', year: 'numeric' });
        },

        formatFechaGrupo(fecha) {
            const label = new Date(fecha + 'T00:00:00').toLocaleDateString('es', { weekday:'long', day:'numeric', month:'long' });
            return fecha === hoy ? 'Hoy — ' + label : label;
        },

        estadoLabel(s) {
            return { pending:'Pendiente', confirmed:'Confirmada', waiting:'En espera', attended:'Atendida', cancelled:'Cancelada', all:'Todas' }[s] || s;
        },

        estadoPillStyle(s) {
            const m = {
                pending:   'color:#D97706;background:#FFFBEB;border-color:#FDE68A;',
                confirmed: 'color:#16A34A;background:#DCFCE7;border-color:#BBF7D0;',
                waiting:   'color:#2563EB;background:#DBEAFE;border-color:#BFDBFE;',
                attended:  'color:#6B7280;background:#F3F4F6;border-color:#E5E7EB;',
                cancelled: 'color:#EF4444;background:#FEE2E2;border-color:#FECACA;',
            };
            return m[s] || 'color:#6B7280;background:#F3F4F6;border-color:#E5E7EB;';
        },

        estadoBarStyle(s) {
            const m = {
                pending:   'background:#F59E0B;',
                confirmed: 'background:#10B981;',
                waiting:   'background:#2563EB;',
                attended:  'background:#9CA3AF;',
                cancelled: 'background:#EF4444;',
            };
            return m[s] || 'background:#E5E8EF;';
        },

        estadoBtnStyle(s) {
            const active = this.filtroEstado === s;
            const m = {
                all:       active ? 'background:#111827;color:#fff;border-color:#111827;' : 'background:#fff;color:#6B7280;border-color:#E5E8EF;',
                pending:   active ? 'background:#F59E0B;color:#fff;border-color:#F59E0B;' : 'background:#fff;color:#D97706;border-color:#FDE68A;',
                confirmed: active ? 'background:#10B981;color:#fff;border-color:#10B981;' : 'background:#fff;color:#16A34A;border-color:#BBF7D0;',
                waiting:   active ? 'background:#2563EB;color:#fff;border-color:#2563EB;' : 'background:#fff;color:#2563EB;border-color:#BFDBFE;',
                attended:  active ? 'background:#6B7280;color:#fff;border-color:#6B7280;' : 'background:#fff;color:#6B7280;border-color:#E5E8EF;',
                cancelled: active ? 'background:#EF4444;color:#fff;border-color:#EF4444;' : 'background:#fff;color:#EF4444;border-color:#FECACA;',
            };
            return m[s] || m.all;
        },

        abrirModal(r = null) {
            this.form = r ? { ...r } : {
                id: null, client_name: '', client_phone: '', date: hoy,
                start_time: '13:00', end_time: '14:30', guests: 2,
                zone: '', table_number: '', occasion: '', status: 'pending', notes: ''
            };
            this.modal = true;
        },
        abrirDetalle(r) { this.abrirModal(r); },
        cerrarModal() { this.modal = false; },

        async guardar() {
            if (!this.form.client_name || !this.form.date || !this.form.start_time) {
                this.showToast('Completa los campos requeridos', 'error'); return;
            }
            this.guardando = true;
            try {
                const url    = this.form.id ? `/bixosales/reservas/${this.form.id}` : '/bixosales/reservas';
                const method = this.form.id ? 'PUT' : 'POST';
                const resp   = await fetch(url, {
                    method,
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(this.form)
                });
                const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Error');
                if (this.form.id) {
                    const idx = this.reservas.findIndex(r => r.id === this.form.id);
                    if (idx >= 0) this.reservas.splice(idx, 1, data.reserva);
                } else {
                    this.reservas.push(data.reserva);
                }
                this.cerrarModal();
                this.showToast(this.form.id ? 'Reserva actualizada' : 'Reserva creada');
            } catch(e) {
                this.showToast(e.message, 'error');
            } finally {
                this.guardando = false;
            }
        },

        async cambiarEstado(r, nuevoEstado) {
            const resp = await fetch(`/bixosales/reservas/${r.id}`, {
                method: 'PUT',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ status: nuevoEstado })
            });
            const data = await resp.json();
            if (data.ok) {
                const idx = this.reservas.findIndex(x => x.id === r.id);
                if (idx >= 0) this.reservas.splice(idx, 1, data.reserva);
                this.showToast('Estado actualizado');
            }
        },

        async eliminar() {
            if (! await bxConfirmar({ descripcion: '¿Eliminar esta reserva?' })) return;
            const resp = await fetch(`/bixosales/reservas/${this.form.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
            if ((await resp.json()).ok) {
                this.reservas = this.reservas.filter(r => r.id !== this.form.id);
                this.cerrarModal();
                this.showToast('Reserva eliminada');
            }
        },

        showToast(msg, type = 'ok') {
            this.toast = { show: true, msg, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endpush
</x-portal-layout>
