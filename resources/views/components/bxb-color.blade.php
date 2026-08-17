@props(['clave', 'defecto' => '#2563eb', 'etiqueta' => 'Color'])
{{-- ═══ Campo de color de una sola pieza ═══
     Antes cada color eran TRES elementos en la misma fila: el selector nativo,
     un <code> que solo mostraba el valor y un botón "Auto". Ocupaba media fila,
     no se podía pegar un código de marca (el <code> no es editable) y obligaba a
     buscar el tono a ojo en el selector.

     Ahora es un campo donde se escribe o se pega el código, con la muestra a la
     izquierda que abre el selector para quien prefiera elegirlo mirando. Vacío
     es automático, así el botón "Auto" sobra.

     Acepta `#183060`, `183060` y `#183`: cuando se copia un color de una guía de
     marca no siempre viene con almohadilla. Si lo escrito no es un color válido
     NO se guarda, para no dejar el ajuste con basura. --}}
<span class="bxb-color-one" x-data="{
    norm(v){
        v = String(v || '').trim().replace(/^#/, '');
        if (/^[0-9a-fA-F]{3}$/.test(v)) v = v.split('').map(c => c + c).join('');
        return /^[0-9a-fA-F]{6}$/.test(v) ? '#' + v.toLowerCase() : null;
    },
    escribir(v){
        if (String(v || '').trim() === '') { setSetting('{{ $clave }}', ''); return; }
        const c = this.norm(v);
        if (c) setSetting('{{ $clave }}', c);
    }
}">
    {{-- `@change`, NO `@input`: el selector nativo dispara `input` mientras
         mueves el cursor por la paleta, así que con solo abrirlo y cerrarlo ya
         guardaba un color que nadie eligió. Pasó de verdad: aparecieron dos
         ajustes en azul marino que el cliente no había tocado, y al publicar le
         habrían cambiado la tienda. `change` solo dispara al confirmar. --}}
    <input type="color" class="bxb-color-dot"
           :value="settings.{{ $clave }} || '{{ $defecto }}'"
           @change="setSetting('{{ $clave }}', $event.target.value)"
           aria-label="Elegir {{ $etiqueta }}">
    <input type="text" class="bxb-color-hex" maxlength="7" spellcheck="false"
           placeholder="automático"
           :value="settings.{{ $clave }} || ''"
           @change="escribir($event.target.value)"
           @blur="$event.target.value = settings.{{ $clave }} || ''"
           aria-label="{{ $etiqueta }} en código hexadecimal">
</span>
