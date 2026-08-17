{{-- Campos de filtro, compartidos por la barra de escritorio y la hoja móvil:
     una sola definición para que no se desincronicen. --}}

<select x-model="f.comercial" aria-label="Estado comercial" class="pf-select">
    <option value="">Comercial: todos</option>
    @foreach (\App\Support\OrderStatus::opcionesComercial() as $valor => $etiqueta)
        <option value="{{ $valor }}">{{ $etiqueta }}</option>
    @endforeach
</select>

<select x-model="f.pago" aria-label="Estado de pago" class="pf-select">
    <option value="">Pago: todos</option>
    @foreach (\App\Support\OrderStatus::opcionesPago() as $valor => $etiqueta)
        <option value="{{ $valor }}">{{ $etiqueta }}</option>
    @endforeach
</select>

{{-- Operación solo existe si el proyecto tiene un flujo real. En TECSIST
     delivery_status está vacío en 21 de 21: el filtro no se pinta en absoluto,
     ni siquiera vacío. --}}
@if (!empty($flujoOperativo))
    <select x-model="f.operacion" aria-label="Preparación o entrega" class="pf-select">
        <option value="">Operación: todas</option>
        @foreach ($flujoOperativo as $estado)
            <option value="{{ $estado['key'] }}">{{ $estado['label'] }}</option>
        @endforeach
    </select>
@endif

@if ($salesChannels->isNotEmpty())
    <select x-model="f.canal" aria-label="Canal de venta" class="pf-select">
        <option value="">Canal: todos</option>
        @foreach ($salesChannels as $canal)
            <option value="{{ $canal }}">{{ $canal }}</option>
        @endforeach
    </select>
@endif

<select x-model="f.fecha" aria-label="Rango de fechas" class="pf-select">
    <option value="">Fecha: todas</option>
    <option value="hoy">Hoy</option>
    <option value="7">Últimos 7 días</option>
    <option value="30">Últimos 30 días</option>
</select>

<template x-if="responsables.length">
    <select x-model="f.responsable" aria-label="Responsable" class="pf-select">
        <option value="">Responsable: todos</option>
        <template x-for="r in responsables" :key="r">
            <option :value="r" x-text="r"></option>
        </template>
    </select>
</template>

<template x-if="filtrosActivos > 0">
    <button @click="limpiarFiltros()"
            class="hidden md:inline-flex text-[11px] px-2 py-1 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition flex-shrink-0">
        <span x-text="filtrosActivos"></span>&nbsp;filtro<span x-text="filtrosActivos===1?'':'s'"></span>&nbsp;&times;
    </button>
</template>
