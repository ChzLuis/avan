{{-- Navegación lateral por etapas (única navegación principal) --}}
<nav class="bxb-stages" aria-label="Etapas del constructor">
    <ol>
        <template x-for="(s, i) in stageList" :key="s.key">
            <li>
                <button type="button" class="bxb-stage-item" :class="[stage===s.key&&'is-active']" :data-state="s.state"
                        @click="stage=s.key; highlightForStage()" :aria-current="stage===s.key ? 'step' : false">
                    <span class="bxb-stage-n" x-text="s.state==='complete' ? '✓' : (i+1)"></span>
                    <span class="bxb-stage-copy">
                        <strong x-text="s.label"></strong>
                        <small>
                            <template x-if="s.state==='complete'"><span>Completa</span></template>
                            <template x-if="s.state!=='complete'">
                                <span><span x-text="s.percent+'%'"></span> · <span x-text="s.pending_count"></span> pend. · ~<span x-text="s.remaining_minutes"></span> min</span>
                            </template>
                        </small>
                    </span>
                    <span class="bxb-stage-dot" :data-sev="s.max_severity" aria-hidden="true"></span>
                </button>
            </li>
        </template>
    </ol>
    <div class="bxb-stages-foot">
        <button type="button" class="bxb-stage-item" :class="stage==='advanced'&&'is-active'" @click="stage='advanced'">
            <span class="bxb-stage-n">⚙</span>
            <span class="bxb-stage-copy"><strong>Ajustes avanzados</strong><small>SEO, copiar tienda, técnico</small></span>
        </button>
    </div>
</nav>
