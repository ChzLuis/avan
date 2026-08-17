{{-- Encabezado opcional ANTES de una sección del Inicio (texto o imagen).
     Estilos: simple | dot (título con punto de color) | band (banda de color
     con icono) | strip (franja discreta). Claves: intro_{key}_* .
     Recibe $introKey (clave nativa); hereda $settings, $color, $assetUrl. --}}
@php
    $inStyle = in_array(($settings["intro_{$introKey}_style"] ?? 'none'), ['none', 'simple', 'dot', 'band', 'strip'], true)
        ? ($settings["intro_{$introKey}_style"] ?? 'none') : 'none';
    $inTitle = trim($settings["intro_{$introKey}_title"] ?? '');
    $inSub = trim($settings["intro_{$introKey}_subtitle"] ?? '');
    $inImg = $assetUrl($settings["intro_{$introKey}_image"] ?? null);
    $inBg = $color($settings["intro_{$introKey}_bg"] ?? null, $inStyle === 'strip' ? '#e5e7eb' : '#f8c821');
    $inInk = $color($settings["intro_{$introKey}_ink"] ?? null, '#111827');
@endphp
@if($inStyle !== 'none' && ($inTitle !== '' || $inSub !== '' || $inImg))
<div class="sec-intro sec-intro--{{ $inStyle }}" style="--si-bg:{{ $inBg }};--si-ink:{{ $inInk }}">
    @if($inImg)<img class="sec-intro-img" src="{{ $inImg }}" alt="{{ $inTitle ?: 'Sección' }}" loading="lazy">@endif
    @if($inTitle !== '')
    <div class="sec-intro-row">
        @if($inStyle === 'band')<span class="sec-intro-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 4.5h2l2 10.5h10l2-8H7"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="16.5" cy="19" r="1.4"/></svg></span>@endif
        <h2 class="sec-intro-title">{{ $inTitle }}@if($inStyle === 'dot')<span class="sec-intro-dot">.</span>@endif</h2>
    </div>
    @endif
    @if($inSub !== '')<p class="sec-intro-sub">{{ $inSub }}</p>@endif
</div>
@endif
