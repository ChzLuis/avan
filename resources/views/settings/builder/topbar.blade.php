{{-- Barra superior fija del Constructor --}}
<header class="bxb-topbar">
    <a class="bxb-back" :href="urls.stores" aria-label="Regresar a tiendas">←<span class="bxb-hide-sm"> Tiendas</span></a>
    <a class="bxb-link bxb-hide-sm" href="{{ route('design-templates.index') }}" title="Guardar este diseño como plantilla reutilizable o aplicar una guardada">🗂 Mis plantillas</a>
    <strong class="bxb-name" x-text="project"></strong>

    <div class="bxb-progressbar" role="progressbar" :aria-valuenow="progress.percent" aria-valuemin="0" aria-valuemax="100" :aria-label="'Progreso '+progress.percent+'%'">
        <div class="bxb-progressbar-fill" :style="'width:'+progress.percent+'%'"></div>
    </div>
    <span class="bxb-percent" x-text="progress.percent+'%'"></span>

    <button type="button" class="bxb-draft-chip" x-show="pendingDrafts" x-cloak @click="stage='publish'" title="Tienes cambios que tus clientes aún no ven">
        ● Borrador sin publicar — <u>publicar ahora</u>
    </button>
    <span class="bxb-save" :data-state="saveState" role="status">
        <span class="bxb-save-dot" aria-hidden="true"></span>
        <span x-text="saveLabel"></span>
        <small class="bxb-hide-sm" x-show="lastSavedAt" x-text="'· '+lastSavedAt"></small>
        <button type="button" class="bxb-link" x-show="saveState==='error'" @click="flushQueue()">Reintentar</button>
    </span>

    <span class="bxb-undo" role="group" aria-label="Deshacer y rehacer">
        <button type="button" @click="undo()" :disabled="!canUndo" aria-label="Deshacer" title="Deshacer (Ctrl+Z)"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg></button>
        <button type="button" @click="redo()" :disabled="!canRedo" aria-label="Rehacer" title="Rehacer (Ctrl+Y)"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg></button>
    </span>

    <span class="bxb-device" role="group" aria-label="Dispositivo de la vista previa">
        <button type="button" :class="device==='desktop'&&'on'" @click="setDevice('desktop')" aria-label="Vista escritorio"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M9 21h6M12 17v4"/></svg></button>
        <button type="button" :class="device==='mobile'&&'on'" @click="setDevice('mobile')" aria-label="Vista móvil"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="7.5" y="2.5" width="9" height="19" rx="2"/><path d="M11 18h2"/></svg></button>
    </span>

    <button type="button" class="bxb-btn bxb-hide-md" @click="expertMode=!expertMode" :aria-pressed="expertMode.toString()" x-text="expertMode?'Modo experto':'Modo rápido'"></button>
    <a class="bxb-btn bxb-hide-md" :href="urls.preview+'?view=home'" target="_blank" rel="noopener">Vista previa ↗</a>

    <button type="button" class="bxb-btn bxb-btn-publish" @click="publish(false)" :disabled="publishing || progress.criticals>0"
            :title="progress.criticals>0 ? (progress.criticals+' pendientes críticos') : 'Publicar los cambios'">
        <span x-show="!publishing">Publicar<template x-if="progress.criticals>0"><span class="bxb-badge" x-text="progress.criticals"></span></template></span>
        <span x-show="publishing" x-cloak>Publicando…</span>
    </button>
</header>
