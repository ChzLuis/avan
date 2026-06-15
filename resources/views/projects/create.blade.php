@extends('layouts.app')

@section('page-title', 'Nuevo negocio')

@section('content')
<div class="prj-create-wrap" x-data="industryPicker()" x-cloak>

    {{-- PASO 1: Elige tu industria --}}
    <div x-show="step === 1">
        <div class="prj-step-header">
            <div class="prj-step-badge">Paso 1 de 2</div>
            <h1 class="prj-step-title">¿Qué tipo de negocio tienes?</h1>
            <p class="prj-step-sub">Selecciona tu industria para ver los módulos recomendados</p>
        </div>

        <div class="prj-industries-grid">
            @foreach($industries as $key => $ind)
            <button
                class="prj-ind-card"
                :class="selected === '{{ $key }}' ? 'is-selected' : ''"
                @click="select('{{ $key }}')"
            >
                <div class="prj-ind-emoji">{{ $ind['emoji'] }}</div>
                <div class="prj-ind-name">{{ $ind['name'] }}</div>
            </button>
            @endforeach
        </div>

        {{-- Panel de detalle (aparece al seleccionar) --}}
        <div class="prj-ind-detail" x-show="selected !== null" x-transition>
            <template x-if="selected !== null">
                <div>
                    <div class="prj-detail-top">
                        <span class="prj-detail-emoji" x-text="industries[selected].emoji"></span>
                        <div>
                            <div class="prj-detail-title" x-text="industries[selected].name"></div>
                            <div class="prj-detail-tagline" x-text="industries[selected].tagline"></div>
                        </div>
                    </div>

                    <div class="prj-detail-cols">
                        {{-- Problemas --}}
                        <div class="prj-detail-col">
                            <div class="prj-detail-col-head">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Problemas comunes
                            </div>
                            <ul class="prj-problems-list">
                                <template x-for="p in industries[selected].problems" :key="p">
                                    <li x-text="p"></li>
                                </template>
                            </ul>
                        </div>

                        {{-- Módulos --}}
                        <div class="prj-detail-col">
                            <div class="prj-detail-col-head">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                Módulos incluidos
                            </div>
                            <div class="prj-modules-list">
                                <template x-for="(items, mod) in industries[selected].modules" :key="mod">
                                    <div class="prj-module-row">
                                        <div class="prj-module-name" x-text="mod"></div>
                                        <div class="prj-module-subs">
                                            <template x-for="s in items" :key="s">
                                                <span class="prj-module-tag" x-text="s"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="prj-detail-actions">
                        <button class="prj-btn-continue" @click="step = 2">
                            Continuar con este tipo
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- PASO 2: Datos del negocio --}}
    <div x-show="step === 2">
        <div class="prj-step-header">
            <button class="prj-back-btn" @click="step = 1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Cambiar industria
            </button>
            <div class="prj-step-badge">Paso 2 de 2</div>
            <h1 class="prj-step-title">Datos de tu negocio</h1>
            <p class="prj-step-sub">
                Industria seleccionada:
                <strong x-text="selected ? industries[selected].emoji + ' ' + industries[selected].name : ''"></strong>
            </p>
        </div>

        <form method="POST" action="{{ route('projects.store') }}" class="prj-form">
            @csrf
            <input type="hidden" name="category" :value="selected">

            <div class="prj-form-group">
                <label class="prj-form-label">Nombre del negocio <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="prj-form-input"
                       placeholder="Ej: Restaurante El Buen Sabor"
                       required maxlength="100"
                       value="{{ old('name') }}">
                @error('name')
                    <p class="prj-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="prj-form-row">
                <div class="prj-form-group">
                    <label class="prj-form-label">Teléfono</label>
                    <input type="text" name="phone" class="prj-form-input"
                           placeholder="+51 999 999 999" maxlength="30"
                           value="{{ old('phone') }}">
                </div>
                <div class="prj-form-group">
                    <label class="prj-form-label">WhatsApp</label>
                    <input type="text" name="whatsapp" class="prj-form-input"
                           placeholder="+51 999 999 999" maxlength="30"
                           value="{{ old('whatsapp') }}">
                </div>
            </div>

            <div class="prj-form-group">
                <label class="prj-form-label">Dirección</label>
                <input type="text" name="address" class="prj-form-input"
                       placeholder="Av. Principal 123, Lima" maxlength="200"
                       value="{{ old('address') }}">
            </div>

            <div class="prj-form-group">
                <label class="prj-form-label">Descripción breve</label>
                <textarea name="description" class="prj-form-input prj-form-textarea"
                          placeholder="Describe brevemente tu negocio..." maxlength="500">{{ old('description') }}</textarea>
            </div>

            <div class="prj-form-actions">
                <button type="button" class="prj-btn-secondary" @click="step = 1">Atrás</button>
                <button type="submit" class="prj-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                    Crear negocio
                </button>
            </div>
        </form>
    </div>

</div>

<style>
[x-cloak] { display:none !important; }

.prj-create-wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 32px 24px 64px;
}

/* ── Header de paso ── */
.prj-step-header {
    margin-bottom: 28px;
}
.prj-step-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7C3AED;
    background: #EDE9FE;
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 10px;
}
.prj-step-title {
    font-size: 24px;
    font-weight: 800;
    color: #1A1D23;
    letter-spacing: -.3px;
    margin: 0 0 6px;
}
.prj-step-sub {
    font-size: 14px;
    color: #44546F;
    margin: 0;
}

/* ── Grid de industrias ── */
.prj-industries-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.prj-ind-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 12px 14px;
    background: #fff;
    border: 2px solid #E1E4E8;
    border-radius: 12px;
    cursor: pointer;
    transition: border-color .15s, transform .1s, box-shadow .15s;
    text-align: center;
}
.prj-ind-card:hover {
    border-color: #C4B5FD;
    box-shadow: 0 2px 12px rgba(124,58,237,.1);
    transform: translateY(-1px);
}
.prj-ind-card.is-selected {
    border-color: #7C3AED;
    background: #EDE9FE;
    box-shadow: 0 0 0 3px rgba(124,58,237,.15);
}
.prj-ind-emoji {
    font-size: 32px;
    line-height: 1;
}
.prj-ind-name {
    font-size: 12px;
    font-weight: 600;
    color: #1A1D23;
    line-height: 1.3;
}

/* ── Panel de detalle ── */
.prj-ind-detail {
    background: #fff;
    border: 1px solid #E1E4E8;
    border-left: 4px solid #7C3AED;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 8px;
}
.prj-detail-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.prj-detail-emoji {
    font-size: 40px;
    line-height: 1;
    flex-shrink: 0;
}
.prj-detail-title {
    font-size: 18px;
    font-weight: 800;
    color: #1A1D23;
    letter-spacing: -.2px;
}
.prj-detail-tagline {
    font-size: 13px;
    color: #44546F;
    margin-top: 2px;
}
.prj-detail-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 20px;
}
@media (max-width: 600px) {
    .prj-detail-cols { grid-template-columns: 1fr; }
    .prj-industries-grid { grid-template-columns: repeat(3, 1fr); }
}
.prj-detail-col-head {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #8590A2;
    margin-bottom: 10px;
}
.prj-problems-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.prj-problems-list li {
    font-size: 13px;
    color: #44546F;
    padding-left: 18px;
    position: relative;
}
.prj-problems-list li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #EF4444;
    font-weight: 900;
}
.prj-modules-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.prj-module-row {}
.prj-module-name {
    font-size: 12px;
    font-weight: 700;
    color: #7C3AED;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.prj-module-subs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.prj-module-tag {
    font-size: 11px;
    background: #F4F5F7;
    color: #44546F;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 500;
}
.prj-detail-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 16px;
    border-top: 1px solid #E1E4E8;
}
.prj-btn-continue {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: #7C3AED;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.prj-btn-continue:hover { background: #6D28D9; }

/* ── Botón volver ── */
.prj-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #44546F;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    margin-bottom: 12px;
    transition: color .15s;
}
.prj-back-btn:hover { color: #7C3AED; }

/* ── Formulario paso 2 ── */
.prj-form {
    background: #fff;
    border: 1px solid #E1E4E8;
    border-radius: 12px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.prj-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 600px) {
    .prj-form-row { grid-template-columns: 1fr; }
}
.prj-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.prj-form-label {
    font-size: 13px;
    font-weight: 600;
    color: #1A1D23;
}
.prj-form-input {
    border: 1.5px solid #E1E4E8;
    border-radius: 8px;
    padding: 9px 13px;
    font-size: 14px;
    color: #1A1D23;
    background: #FAFAFA;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
    width: 100%;
    font-family: inherit;
}
.prj-form-input:focus {
    border-color: #7C3AED;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
    background: #fff;
}
.prj-form-textarea {
    resize: vertical;
    min-height: 80px;
}
.prj-form-error {
    font-size: 12px;
    color: #EF4444;
    margin: 0;
}
.prj-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 8px;
    border-top: 1px solid #E1E4E8;
}
.prj-btn-secondary {
    padding: 9px 20px;
    border: 1.5px solid #E1E4E8;
    border-radius: 8px;
    background: #fff;
    color: #44546F;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}
.prj-btn-secondary:hover { border-color: #7C3AED; color: #7C3AED; }
.prj-btn-primary {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 22px;
    background: #7C3AED;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.prj-btn-primary:hover { background: #6D28D9; }
</style>

<script>
function industryPicker() {
    return {
        step: 1,
        selected: null,
        industries: @json($industries),
        select(key) {
            this.selected = key;
        }
    }
}
</script>
@endsection
