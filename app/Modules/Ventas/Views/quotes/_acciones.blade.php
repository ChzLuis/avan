{{--
    Acciones de conversión de una cotización (F1c).

    Es el único parcial que se extrae del monolito: aporta una unidad nueva y
    cohesiva (botón + confirmación + avisos + relación con el pedido). Lista y
    detalle NO se trocearon: comparten el mismo scope de Alpine, así que
    separarlos no daría aislamiento real y sí añadiría riesgo sobre 1173 líneas
    con tres exportadores dentro. La decisión queda documentada en el
    expediente F1c.

    Todo lo que se ve aquí ya está garantizado por el servidor: `puede.convertir`
    viene de QuoteAbilities (permiso A|B + módulo de pedidos) y el estado
    `accepted` lo revalida `convert()`. La UI no es la barrera.
--}}

{{-- La relacion con el pedido ya NO se pinta aqui: era una franja entera
     bajo la cabecera para decir 'Convertida a PED-34'. Ahora es un chip en
     la propia cabecera, junto al estado. --}}

{{-- Aviso de resultado. 'already' se comunica como información, no como error --}}
<div x-show="avisoConvertir" x-cloak class="q-aviso-ok" role="status" aria-live="polite">
    <span x-text="avisoConvertir"></span>
    <template x-if="pedidoDeEstaCotizacion">
        <a :href="urlPedido(pedidoDeEstaCotizacion)" class="q-rel-link">Ver pedido</a>
    </template>
</div>

<div x-show="error" x-cloak class="q-aviso-error" role="alert">
    <span x-text="error"></span>
    <button type="button" class="q-aviso-cerrar" @click="error=''" aria-label="Descartar el error">✕</button>
</div>

{{-- El boton de convertir vive en el panel derecho, bajo el Resumen: es el
     siguiente paso comercial, no una accion suelta a media pantalla. Aqui se
     quedan el aviso y la confirmacion, que son transversales. --}}

{{-- Confirmación: dice QUÉ va a pasar, no "¿estás seguro?" --}}
<template x-if="modalConvertir">
    <div class="q-modal-fondo" @keydown.escape.window="if(!convirtiendo) modalConvertir=false"
         @click.self="if(!convirtiendo) modalConvertir=false">
        <div class="q-modal" role="dialog" aria-modal="true"
             aria-labelledby="tituloConvertir" aria-describedby="descConvertir"
             x-trap.noscroll="modalConvertir">
            <h2 class="q-modal-titulo" id="tituloConvertir">Convertir en pedido</h2>
            <p class="q-modal-desc" id="descConvertir">
                Se creará un pedido con
                <strong x-text="form.items.filter(i=>i.description).length"></strong>
                <span x-text="form.items.filter(i=>i.description).length === 1 ? 'línea' : 'líneas'"></span>
                por <strong x-text="lmMoneda(subtotalCents)"></strong>.
                Esta cotización quedará como <strong>Convertida</strong> y ya no podrá editarse
                ni cambiar de estado.
            </p>
            <div class="q-modal-botones">
                <button type="button" class="q-btn-secundario" @click="modalConvertir=false"
                        :disabled="convirtiendo">Cancelar</button>
                <button type="button" class="q-btn-primario" @click="convertir()"
                        :disabled="convirtiendo" :aria-busy="convirtiendo ? 'true' : 'false'"
                        x-ref="confirmarConvertir">
                    <span x-show="!convirtiendo">Crear el pedido</span>
                    <span x-show="convirtiendo" x-cloak>Creando…</span>
                </button>
            </div>
        </div>
    </div>
</template>
