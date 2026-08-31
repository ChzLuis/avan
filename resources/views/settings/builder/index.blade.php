<x-app-layout>
@include('settings.builder.styles')

{{-- ═══ CONSTRUCTOR — shell de 4 zonas (B1) ═══ --}}
<div class="bx-builder" x-data="builderApp(@js([
        'project' => $project->name,
        'slug' => $project->slug,
        'csrf' => csrf_token(),
        'progress' => $progress,
        'urls' => [
            'progress' => route('settings.builder.progress'),
            'checklist' => route('settings.builder.checklist'),
            'copySources' => route('settings.builder.copy.sources'),
            'copy' => route('settings.builder.copy'),
            'draftSettings' => route('settings.builder.draft.settings'),
            'designPreset' => route('settings.builder.design-preset'),
            'publish' => route('settings.builder.publish'),
            'preview' => route('settings.builder.preview'),
            'classic' => route('settings.design'),
            'stores' => url('/bixoadmin'),
            'public' => url('/'.$project->slug),
        ],
        'settings' => $settingsDraft,
        'hasDrafts' => $hasDrafts,
        'presets' => $presets,
        'templates' => $templates,
        'palettes' => [
            ['name' => 'Índigo', 'p' => '#4f46e5', 's' => '#0f172a'],
            ['name' => 'Azul', 'p' => '#2563eb', 's' => '#0f172a'],
            ['name' => 'Esmeralda', 'p' => '#059669', 's' => '#064e3b'],
            ['name' => 'Rosa', 'p' => '#db2777', 's' => '#1f2937'],
            ['name' => 'Naranja', 'p' => '#ea580c', 's' => '#1c1917'],
            ['name' => 'Violeta', 'p' => '#7c3aed', 's' => '#1e1b4b'],
        ],
        'uploadUrl' => route('settings.upload-logo'),
        'blocks' => $blocks,
        'storeCategories' => $storeCategories,
        'catIcons' => collect($settingsDraft)->filter(fn ($v, $k) => str_starts_with($k, 'caticon_') && filled($v))
            ->mapWithKeys(fn ($v, $k) => [(int) str_replace('caticon_', '', $k) => $v])->all(),
        'iconSearchUrl' => route('settings.builder.icons.search'),
        'iconAssignUrl' => route('settings.builder.icons.assign'),
        'categoryPhotoUrl' => route('settings.builder.category-photo'),
        'saleProducts' => $saleProducts,
        'allProductsLite' => $allProductsLite,
        'profiles' => $catalogProfiles,
        'profilesEnabled' => $profilesEnabled,
        'orphanPolicy' => $orphanPolicy,
        'profileUrls' => [
            'feature' => route('settings.catalog-profiles.feature'),
            'store' => route('settings.catalog-profiles.store'),
            'update' => route('settings.catalog-profiles.update', ['id' => 987654321]),
            'destroy' => route('settings.catalog-profiles.destroy', ['id' => 987654321]),
        ],
        'sectionStateUrl' => route('settings.experience.home.state', ['component' => '__C__']),
        'sectionContentUrl' => route('settings.experience.home.save', ['component' => '__C__']),
        'metricsUrl' => route('settings.builder.metrics'),
        'catalogListUrl' => route('settings.builder.catalog.list'),
        'catalogBulkUrl' => route('settings.builder.catalog.bulk'),
    ]))" @keydown.window.ctrl.z.prevent="undo()" @keydown.window.ctrl.y.prevent="redo()">

    @include('settings.builder.topbar')

    <div class="bxb-body">
        @include('settings.builder.stage-nav')

        {{-- Panel central: SOLO la tarea actual --}}
        <main class="bxb-panel" aria-live="polite">
            <template x-if="stage==='business'">
                @include('settings.builder.stages.business')
            </template>

            {{-- Apariencia concentra plantilla, marca, cabecera y navegación.
                 x-show conserva los formularios de menú que enlazan listeners al cargar. --}}
            <div x-show="stage==='appearance'" x-cloak x-data="{ appearanceArea: 'brand' }">
                <nav class="bxb-local-nav" aria-label="Secciones de Apariencia">
                    <button type="button" :class="appearanceArea==='brand'&&'is-active'" @click="appearanceArea='brand'">Plantilla y marca</button>
                    <button type="button" :class="appearanceArea==='header'&&'is-active'" @click="appearanceArea='header'">Encabezado y navegación</button>
                </nav>
                <div x-show="appearanceArea==='brand'">
                    @include('settings.builder.stages.appearance')
                </div>
                <div x-show="appearanceArea==='header'" x-cloak>
                    @include('settings.builder.stages.header')
                </div>
            </div>

            <template x-if="stage==='home'">
                @include('settings.builder.stages.home')
            </template>

            <template x-if="stage==='catalog'">
                @include('settings.builder.stages.catalog')
            </template>

            {{-- x-show (no x-if): los formularios embebidos enlazan listeners en DOMContentLoaded. --}}
            <div x-show="stage==='pages'" x-cloak>
                @include('settings.builder.stages.pages')
            </div>

            <template x-if="stage==='sales'">
                @include('settings.builder.stages.sales')
            </template>

            <div x-show="stage==='legal'" x-cloak>
                @include('settings.builder.stages.legal')
            </div>

            <template x-if="stage==='advanced'">
                @include('settings.builder.advanced')
            </template>

            <template x-if="stage==='publish'">
                <section class="bxb-stage" x-init="loadChecklist()">
                    <h2>Revisar y publicar</h2>
                    <p class="bxb-stage-sub">Control de calidad automático. Nada de esto te impide publicar: son avisos para mejorar tu tienda.</p>

                    <div class="bxb-card bxb-check" data-sev="critical" x-show="(checklist.attention||[]).length" x-cloak>
                        <strong class="bxb-card-title bxb-danger">⚠ Atención importante</strong>
                        <ul class="bxb-checklist" role="list">
                            <template x-for="itm in checklist.attention" :key="itm.code">
                                <li><span x-text="itm.message + (itm.count?(' ('+itm.count+')'):'')"></span>
                                    <button type="button" class="bxb-btn" @click="goFix(itm)">Corregir</button></li>
                            </template>
                        </ul>
                    </div>

                    <div class="bxb-card bxb-check" data-sev="warning" x-show="(checklist.recommendation||[]).length" x-cloak>
                        <strong class="bxb-card-title" style="color:var(--dz-warn)">Mejoras recomendadas</strong>
                        <ul class="bxb-checklist" role="list">
                            <template x-for="itm in checklist.recommendation" :key="itm.code">
                                <li><span x-text="itm.message + (itm.count?(' ('+itm.count+')'):'')"></span>
                                    <button type="button" class="bxb-btn" @click="goFix(itm)">Corregir</button></li>
                            </template>
                        </ul>
                    </div>

                    <div class="bxb-card bxb-check" data-sev="recommendation" x-show="(checklist.info||[]).length" x-cloak>
                        <strong class="bxb-card-title" style="color:#a16207">💡 Información</strong>
                        <ul class="bxb-checklist" role="list">
                            <template x-for="itm in checklist.info" :key="itm.code">
                                <li><span x-text="itm.message + (itm.count?(' ('+itm.count+')'):'')"></span>
                                    <button type="button" class="bxb-btn" @click="goFix(itm)">Mejorar</button></li>
                            </template>
                        </ul>
                    </div>

                    <div class="bxb-card" x-show="checklist.can_publish" x-cloak>
                        <strong class="bxb-card-title" style="color:var(--dz-ok)">✓ Todo listo para publicar</strong>
                        <p class="bxb-note"><span x-text="(checklist.complete||[]).length"></span> validaciones superadas. Al publicar, tus clientes verán la última versión del borrador.</p>
                        <button type="button" class="bxb-btn bxb-btn-publish" style="margin-left:0;min-height:48px" @click="publish(false)" :disabled="publishing">
                            <span x-show="!publishing">Publicar tienda</span><span x-show="publishing" x-cloak>Publicando…</span>
                        </button>
                    </div>
                </section>
            </template>

            <div class="bxb-panel-foot">
                <button type="button" class="bxb-btn" x-show="stageIndex>0" @click="goStage(-1)">← Atrás</button>
                <span style="flex:1"></span>
                <button type="button" class="bxb-btn bxb-btn-primary" x-show="stage!=='publish'" @click="goStage(1)">Continuar →</button>
            </div>
        </main>

        @include('settings.builder.preview')
    </div>

    {{-- Éxito de publicación --}}
    <div class="bxb-modal" x-show="publishSuccess" x-cloak role="dialog" aria-modal="true" aria-label="Tienda publicada">
        <div class="bxb-modal-box">
            <h3>🎉 ¡Tienda publicada!</h3>
            <p>Versión <strong x-text="publishSuccess && publishSuccess.version"></strong> · <span x-text="publishSuccess && new Date(publishSuccess.published_at).toLocaleString()"></span></p>
            <div class="bxb-modal-actions">
                <a class="bxb-btn bxb-btn-primary" :href="urls.public" target="_blank" rel="noopener">Abrir tienda ↗</a>
                <button type="button" class="bxb-btn" @click="navigator.clipboard && navigator.clipboard.writeText(urls.public)">Copiar enlace</button>
                <a class="bxb-btn" :href="'https://wa.me/?text='+encodeURIComponent('Mira mi tienda: '+urls.public)" target="_blank" rel="noopener">Compartir por WhatsApp</a>
                <button type="button" class="bxb-btn" @click="publishSuccess=null">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Biblioteca de iconos (Iconify) --}}
    <div class="bxb-modal" x-show="iconPicker" x-cloak role="dialog" aria-modal="true" aria-label="Elegir icono">
        <div class="bxb-modal-box">
            <h3>Icono para <em x-text="iconPicker && iconPicker.name"></em></h3>
            <label class="bxb-field">Buscar en la biblioteca (+200.000 iconos)
                <input type="search" placeholder="Ej: laptop, comida, ropa, herramienta…" x-model="iconQuery" @input.debounce.400ms="searchIcons()">
            </label>
            <div class="bxb-icon-grid" role="listbox">
                <template x-for="ic in iconResults" :key="ic">
                    <button type="button" role="option" :aria-label="ic" :title="ic" @click="assignIcon(ic)">
                        <img :src="'https://api.iconify.design/'+ic.replace(':','/')+'.svg?height=26'" :alt="ic" loading="lazy">
                    </button>
                </template>
            </div>
            <p class="bxb-note" x-show="iconQuery.length>1 && !iconResults.length">Sin resultados; prueba en inglés (dog, tools, shirt…).</p>
            <p class="bxb-note" x-show="iconAssignError" x-cloak x-text="iconAssignError" role="alert"></p>
            <div class="bxb-modal-actions"><button type="button" class="bxb-btn" @click="iconPicker=null">Cerrar</button></div>
        </div>
    </div>

    {{-- Confirmación de advertencias --}}
    <div class="bxb-modal" x-show="warningsToConfirm" x-cloak role="dialog" aria-modal="true" aria-label="Advertencias antes de publicar">
        <div class="bxb-modal-box">
            <h3>Puedes publicar, pero revisa esto:</h3>
            <ul class="bxb-pending">
                <template x-for="w in (warningsToConfirm||[])" :key="w.code"><li x-text="w.message"></li></template>
            </ul>
            <div class="bxb-modal-actions">
                <button type="button" class="bxb-btn bxb-btn-primary" @click="publish(true)">Publicar de todos modos</button>
                <button type="button" class="bxb-btn" @click="warningsToConfirm=null">Corregir primero</button>
            </div>
        </div>
    </div>
</div>

@include('settings.builder.script')
@include('settings.builder.partials.image-template-script')
</x-app-layout>
