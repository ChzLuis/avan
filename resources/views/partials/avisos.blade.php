{{--
    Avisos y confirmaciones del producto: una sola fuente para todo el panel.

    Cada pantalla usaba los dialogos del navegador. Salen con el
    aspecto del sistema operativo, encabezados con "arindg.com dice", bloquean
    la pagina entera y no se recorren con teclado como el resto de la
    interfaz. Ademas el toast global solo vivia en el layout de
    administracion, asi que el panel comercial no tenia donde avisar y solo le
    quedaba el alert.

    API para las vistas:

        bxAviso('Guardado', 'success')     // success | error | warning | info

        bxConfirmar({
            titulo: 'Eliminar cotizacion',
            descripcion: 'Se eliminara COT-00033 de Pool Espinoza por S/ 1,266.',
            boton: 'Eliminar',
            tono: 'peligro',               // peligro | principal
            accion: async () => { ... },
        })

    `bxConfirmar` devuelve una promesa con true/false, asi que tambien sirve
    como reemplazo directo de `confirm()`:

        if (! await bxConfirmar({ descripcion: 'Se perderan los cambios.' })) return;
--}}
<div x-data="bxAvisosGlobales()" x-init="montar()">

    {{-- Avisos efimeros --}}
    <div class="bx-toasts" aria-live="polite" aria-atomic="false">
        <template x-for="t in toasts" :key="t.id">
            <div class="bx-toast" :class="'bx-toast-' + t.tipo">
                <span class="bx-toast-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" x-show="t.tipo==='success'"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <svg viewBox="0 0 24 24" x-show="t.tipo==='error'"><path d="M12 9v3.75m0 3.75h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <svg viewBox="0 0 24 24" x-show="t.tipo==='warning'"><path d="M12 9v3.75m0 3.75h.01M10.34 3.94l-8.1 14.02A1.75 1.75 0 0 0 3.76 20.5h16.48a1.75 1.75 0 0 0 1.52-2.54l-8.1-14.02a1.75 1.75 0 0 0-3.32 0Z"/></svg>
                    <svg viewBox="0 0 24 24" x-show="t.tipo==='info'"><path d="M11.25 11.25h1.5v5.25m-.75-9h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <span class="bx-toast-txt" x-text="t.msg"></span>
                <button type="button" class="bx-toast-x" @click="quitar(t.id)" aria-label="Cerrar aviso">&times;</button>
            </div>
        </template>
    </div>

    {{-- Confirmacion de acciones --}}
    <template x-if="dialogo.abierto">
        <div class="bx-modal-fondo" @keydown.escape.window="if(!dialogo.ocupado) cerrar(false)"
             @click.self="if(!dialogo.ocupado) cerrar(false)">
            <div class="bx-modal" role="dialog" aria-modal="true"
                 aria-labelledby="bxDlgTitulo" aria-describedby="bxDlgDesc"
                 x-trap.noscroll="dialogo.abierto">
                <h2 class="bx-modal-titulo" id="bxDlgTitulo" x-text="dialogo.titulo"></h2>
                <p class="bx-modal-desc" id="bxDlgDesc" x-text="dialogo.descripcion"></p>

                {{-- Dialogo con respuesta: sustituye al prompt() del navegador
                     (motivo de un rechazo, copiar un enlace a mano, escribir el
                     identificador para confirmar un borrado peligroso). --}}
                <template x-if="dialogo.entrada">
                    <div class="bx-campo">
                        <label class="bx-campo-lbl" for="bxDlgEntrada" x-text="dialogo.entrada.etiqueta || ''"></label>
                        <input id="bxDlgEntrada" type="text" class="bx-campo-input"
                               x-model="dialogo.valor" :placeholder="dialogo.entrada.marcador || ''"
                               @keydown.enter.prevent="aceptar()" x-ref="bxEntrada"
                               :readonly="dialogo.entrada.soloLectura === true"
                               @focus="if (dialogo.entrada.soloLectura) $el.select()">
                    </div>
                </template>

                <div class="bx-modal-botones">
                    {{-- Un dialogo solo informativo (copiar un enlace a mano) pasa
                         cancelar vacio: ahi no hay nada que cancelar. --}}
                    <button type="button" class="bx-btn-sec" @click="cerrar(false)" x-show="dialogo.cancelar !== ''"
                            :disabled="dialogo.ocupado" x-text="dialogo.cancelar"></button>
                    <button type="button" class="bx-btn-pri"
                            :class="dialogo.tono === 'peligro' ? 'bx-btn-peligro' : ''"
                            @click="aceptar()" :disabled="dialogo.ocupado || !respuestaValida"
                            :aria-busy="dialogo.ocupado ? 'true' : 'false'" x-ref="bxAceptar">
                        <span x-show="!dialogo.ocupado" x-text="dialogo.boton"></span>
                        <span x-show="dialogo.ocupado" x-cloak>Un momento...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
.bx-toasts { position:fixed; top:70px; right:16px; z-index:10000; display:flex; flex-direction:column; gap:8px; pointer-events:none; max-width:min(380px, 92vw); }
.bx-toast { pointer-events:auto; display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:12px; border:1px solid; background:#fff; box-shadow:0 10px 30px rgba(15,23,42,.10), 0 2px 8px rgba(15,23,42,.04); font-size:13.5px; line-height:1.45; }
.bx-toast-icono svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.75; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
.bx-toast-txt { flex:1; min-width:0; color:#111827; }
.bx-toast-x { border:none; background:none; font-size:18px; line-height:1; color:#94a3b8; cursor:pointer; padding:0 2px; }
.bx-toast-x:hover { color:#475569; }
.bx-toast-success { border-color:#BBF7D0; background:#F0FDF4; color:#15803D; }
.bx-toast-error   { border-color:#FECACA; background:#FEF2F2; color:#B91C1C; }
.bx-toast-warning { border-color:#FDE68A; background:#FFFBEB; color:#B45309; }
.bx-toast-info    { border-color:#BFDBFE; background:#EFF6FF; color:#1D4ED8; }

.bx-modal-fondo { position:fixed; inset:0; z-index:10001; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; padding:20px; }
.bx-modal { width:min(440px, 100%); background:#fff; border-radius:14px; padding:22px; box-shadow:0 20px 60px rgba(15,23,42,.22); }
.bx-modal-titulo { margin:0 0 8px; font-size:17px; font-weight:700; color:#111827; }
.bx-modal-desc { margin:0 0 20px; font-size:14px; line-height:1.5; color:#475569; }
.bx-campo { margin:-8px 0 20px; }
.bx-campo-lbl { display:block; font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; }
.bx-campo-input { width:100%; min-height:40px; padding:0 12px; border:1px solid #E5E7EB; border-radius:9px; font-size:14px; color:#111827; outline:none; }
.bx-campo-input:focus { border-color:#4F46E5; box-shadow:0 0 0 3px rgba(79,70,229,.10); }
.bx-modal-botones { display:flex; justify-content:flex-end; gap:10px; }
.bx-btn-sec, .bx-btn-pri { min-height:40px; padding:0 16px; border-radius:9px; font-size:13.5px; font-weight:600; cursor:pointer; }
.bx-btn-sec { border:1px solid #E5E7EB; background:#fff; color:#374151; }
.bx-btn-sec:hover:not(:disabled) { background:#F8FAFC; }
.bx-btn-pri { border:none; background:#4F46E5; color:#fff; }
.bx-btn-pri:hover:not(:disabled) { background:#4338CA; }
.bx-btn-peligro { background:#DC2626; }
.bx-btn-peligro:hover:not(:disabled) { background:#B91C1C; }
.bx-btn-sec:disabled, .bx-btn-pri:disabled { opacity:.55; cursor:not-allowed; }
</style>

<script>
function bxAvisosGlobales() {
    return {
        toasts: [],
        dialogo: { abierto:false, titulo:'', descripcion:'', boton:'Confirmar', cancelar:'Cancelar', tono:'peligro', ocupado:false, accion:null, resolver:null, entrada:null, valor:'' },

        /* Con campo obligatorio o con texto exacto que teclear (borrados
           peligrosos), el boton no se habilita hasta que la respuesta sirve. */
        get respuestaValida() {
            const e = this.dialogo.entrada;
            if (!e || e.soloLectura) return true;
            const v = (this.dialogo.valor || '').trim();
            if (e.debeCoincidir) return v === e.debeCoincidir;
            return e.requerido ? v.length > 0 : true;
        },

        montar() {
            /* Se expone en `window` para que cualquier vista lo use sin
               depender del scope de Alpine en el que se encuentre. */
            window.bxAviso = (msg, tipo) => this.avisar(msg, tipo || 'success');
            window.bxConfirmar = (opciones) => this.confirmar(opciones);
            /* El evento que ya emitian algunas vistas sigue funcionando. */
            window.addEventListener('app-toast', (e) => this.avisar(e.detail && e.detail.msg, (e.detail && e.detail.type) || 'success'));
            this.interceptarFormularios();
        },

        /* Formularios que antes llevaban `onsubmit="return confirm(...)"`.
           Ahora declaran `data-bx-confirmar="mensaje"` y el envio se detiene
           hasta que la persona confirma: el formulario no lleva JavaScript
           propio y el aviso es el mismo de todo el panel. */
        interceptarFormularios() {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form || !form.matches('[data-bx-confirmar]') || form.dataset.bxConfirmado === '1') {
                    return;
                }
                e.preventDefault();
                this.confirmar({
                    titulo: form.dataset.bxTitulo || 'Confirmar accion',
                    descripcion: form.dataset.bxConfirmar,
                    boton: form.dataset.bxBoton || 'Continuar',
                }).then((ok) => {
                    if (!ok) return;
                    form.dataset.bxConfirmado = '1';
                    form.submit();
                });
            }, true);
        },

        avisar(msg, tipo) {
            if (!msg) return;
            /* ALIAS EN CASTELLANO. Medio proyecto llama con 'exito' —y es
               natural, todo el codigo esta en español— pero las clases y los
               iconos solo existen para los cuatro nombres en ingles. El aviso
               mas importante del modulo ("Guia emitida", "Nota emitida",
               "Aceptada por SUNAT") salia como un rectangulo blanco, sin color
               ni icono: nadie lo leia como una confirmacion. Se traduce aqui,
               en un solo sitio, y cubre tambien las vistas que vengan. */
            tipo = ({ exito: 'success', correcto: 'success', ok: 'success',
                      aviso: 'warning', advertencia: 'warning',
                      informacion: 'info', info: 'info' })[tipo] || tipo;
            tipo = ['success', 'error', 'warning', 'info'].includes(tipo) ? tipo : 'success';
            const id = Date.now() + Math.round(performance.now());
            this.toasts.push({ id: id, msg: String(msg), tipo: tipo });
            /* Un error se queda mas tiempo: suele haber que leerlo dos veces. */
            setTimeout(() => this.quitar(id), tipo === 'error' ? 7000 : 4000);
        },

        quitar(id) { this.toasts = this.toasts.filter(t => t.id !== id); },

        confirmar(opciones) {
            return new Promise((resolve) => {
                this.dialogo = Object.assign(
                    { abierto:true, titulo:'Confirmar accion', descripcion:'', boton:'Confirmar',
                      cancelar:'Cancelar', tono:'peligro', ocupado:false, accion:null,
                      entrada:null, valor:(opciones && opciones.entrada && opciones.entrada.valor) || '' },
                    opciones || {},
                    { resolver: resolve }
                );
                if (opciones && opciones.entrada && opciones.entrada.valor) {
                    this.dialogo.valor = opciones.entrada.valor;
                }
                this.$nextTick(() => {
                    const foco = this.dialogo.entrada ? this.$refs.bxEntrada : this.$refs.bxAceptar;
                    if (foco) foco.focus();
                });
            });
        },

        async aceptar() {
            if (this.dialogo.ocupado) return;
            const accion = this.dialogo.accion;
            if (!this.respuestaValida) return;
            if (!accion) { this.cerrar(true); return; }
            this.dialogo.ocupado = true;
            try { await accion(); this.cerrar(true); }
            catch (e) {
                this.dialogo.ocupado = false;
                this.avisar((e && e.message) || 'No se pudo completar la accion.', 'error');
            }
        },

        cerrar(resultado) {
            const resolver = this.dialogo.resolver;
            const conCampo = !!this.dialogo.entrada;
            const valor    = this.dialogo.valor;
            this.dialogo.abierto = false;
            this.dialogo.ocupado = false;
            /* Con campo devuelve lo escrito (o null si se cancela), como hacia
               `prompt`. Sin campo, true/false como `confirm`. */
            if (resolver) resolver(conCampo ? (resultado ? valor : null) : !!resultado);
        },
    };
}
</script>
