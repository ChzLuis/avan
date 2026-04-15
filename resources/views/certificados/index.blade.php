<x-app-layout>
<x-slot name="slot">

{{-- Generador PDF — fuera del x-data para que el parser HTML no lo rompa --}}
<script>
window._certGen = {
    buildHtml(c) {
        const verUrl = '{{ url("/cert") }}/' + c.codigo;
        const qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(verUrl);

        const css = [
            "*{box-sizing:border-box;margin:0;padding:0;}",
            "body{font-family:Georgia,serif;background:#f5f3ee;-webkit-print-color-adjust:exact;print-color-adjust:exact;}",
            "@page{size:A4 landscape;margin:0;}",
            ".cert{width:297mm;height:210mm;background:#fff;position:relative;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:0 60px;}",
            "@media print{body{background:#fff;}}",
            ".border-outer{position:absolute;inset:12px;border:3px solid #1D4E3A;pointer-events:none;}",
            ".border-inner{position:absolute;inset:18px;border:1px solid #97C459;pointer-events:none;}",
            ".corner{position:absolute;width:40px;height:40px;}",
            ".c-tl{top:8px;left:8px;border-top:4px solid #1D9E75;border-left:4px solid #1D9E75;}",
            ".c-tr{top:8px;right:8px;border-top:4px solid #1D9E75;border-right:4px solid #1D9E75;}",
            ".c-bl{bottom:8px;left:8px;border-bottom:4px solid #1D9E75;border-left:4px solid #1D9E75;}",
            ".c-br{bottom:8px;right:8px;border-bottom:4px solid #1D9E75;border-right:4px solid #1D9E75;}",
            ".bg-logo{position:absolute;font-size:220px;font-weight:900;color:rgba(29,158,117,.04);top:50%;left:50%;transform:translate(-50%,-50%);letter-spacing:-10px;user-select:none;}",
            ".header{text-align:center;margin-bottom:18px;position:relative;z-index:2;}",
            ".inst{font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.15em;margin-bottom:6px;}",
            ".titulo{font-size:38px;font-weight:900;color:#0f1117;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;}",
            ".titulo em{color:#1D9E75;font-style:normal;}",
            ".subtitulo{font-size:12px;color:#666;letter-spacing:.06em;}",
            ".divider{width:80px;height:3px;background:linear-gradient(90deg,#1D9E75,#97C459);border-radius:2px;margin:14px auto;}",
            ".otorgado{font-size:12px;color:#888;text-align:center;margin-bottom:6px;position:relative;z-index:2;}",
            ".nombre{font-size:42px;font-weight:700;color:#0f1117;text-align:center;font-style:italic;margin-bottom:14px;position:relative;z-index:2;line-height:1.1;}",
            ".descripcion{font-size:13px;color:#444;text-align:center;max-width:520px;line-height:1.7;margin-bottom:18px;position:relative;z-index:2;}",
            ".descripcion strong{color:#0f1117;}",
            ".meta-row{display:flex;align-items:flex-end;justify-content:space-between;width:100%;position:relative;z-index:2;gap:20px;}",
            ".firma-block{text-align:center;flex:1;}",
            ".firma-line{border-bottom:1.5px solid #1a1a1a;height:36px;margin-bottom:6px;max-width:180px;margin-left:auto;margin-right:auto;}",
            ".firma-nombre{font-size:12px;font-weight:700;color:#0f1117;}",
            ".firma-cargo{font-size:10px;color:#888;}",
            ".cert-info{text-align:center;flex:0 0 auto;}",
            ".cert-code{font-size:10px;color:#888;font-family:monospace;margin-bottom:6px;}",
            ".cert-fecha{font-size:10px;color:#aaa;}",
            ".qr-block{text-align:center;flex:0 0 auto;}",
            ".qr-block img{width:80px;height:80px;border:1px solid #e5e5e5;border-radius:6px;}",
            ".qr-label{font-size:9px;color:#aaa;margin-top:4px;}",
            ".badge-row{display:flex;gap:12px;justify-content:center;margin-bottom:16px;position:relative;z-index:2;}",
            ".badge{display:inline-flex;align-items:center;gap:5px;background:#EAF3DE;border:1px solid #97C459;border-radius:20px;padding:4px 12px;font-size:10px;font-weight:700;color:#0F6E56;}",
        ].join('');

        const nivel  = c.curso_nivel  ? '<div class="badge">⭐ ' + c.curso_nivel + '</div>'  : '';
        const horas  = c.curso_horas  ? '<div class="badge">🕐 ' + c.curso_horas + '</div>'  : '';
        const inst   = c.institucion  || 'BIXO by AVAN';
        const instr  = c.instructor   || 'Representante';
        const fecha  = new Date(c.fecha_emision + 'T12:00:00').toLocaleDateString('es-PE', {day:'numeric',month:'long',year:'numeric'});

        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            + '<title>Certificado — ' + c.alumno_nombre + '</title>'
            + '<link href="https://fonts.googleapis.com/css2?family=Georgia&family=Inter:wght@400;700;900&display=swap" rel="stylesheet">'
            + '<style>' + css + '</style>'
            + '</head><body>'
            + '<div class="cert">'
            + '<div class="border-outer"></div>'
            + '<div class="border-inner"></div>'
            + '<div class="corner c-tl"></div><div class="corner c-tr"></div>'
            + '<div class="corner c-bl"></div><div class="corner c-br"></div>'
            + '<div class="bg-logo">BIXO</div>'
            + '<div class="header">'
            + '<div class="inst">' + inst + '</div>'
            + '<div class="titulo">Certi<em>ficado</em></div>'
            + '<div class="subtitulo">de participación y aprobación</div>'
            + '</div>'
            + '<div class="divider"></div>'
            + '<div class="otorgado">Otorgado a</div>'
            + '<div class="nombre">' + c.alumno_nombre + '</div>'
            + (c.alumno_dni ? '<div style="font-size:11px;color:#aaa;text-align:center;margin-bottom:10px;position:relative;z-index:2;">DNI: ' + c.alumno_dni + '</div>' : '')
            + '<div class="descripcion">Por haber completado satisfactoriamente el programa <strong>' + c.curso_nombre + '</strong></div>'
            + ((nivel || horas) ? '<div class="badge-row">' + nivel + horas + '</div>' : '')
            + '<div class="meta-row">'
            + '<div class="firma-block"><div class="firma-line"></div><div class="firma-nombre">' + instr + '</div><div class="firma-cargo">Instructor / Director</div></div>'
            + '<div class="cert-info"><div class="cert-code">Código: ' + c.codigo + '</div><div class="cert-fecha">Emitido: ' + fecha + '</div></div>'
            + '<div class="qr-block"><img src="' + qrUrl + '" alt="QR"><div class="qr-label">Verificar en línea</div></div>'
            + '</div>'
            + '</div>'
            + '</body></html>';
    },

    print(c) {
        const w = window.open('', '_blank');
        w.document.write(this.buildHtml(c));
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 900);
    }
};
</script>

<div class="flex flex-1 overflow-hidden" x-data="certApp()" x-init="init()">

    {{-- PANEL IZQUIERDO --}}
    <div class="w-72 flex-shrink-0 border-r flex flex-col overflow-hidden bg-white"
         :class="{'hidden md:flex': panel==='detail', 'flex': panel==='list'}">

        <div class="p-4 border-b flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-0.5">Certificados</p>
                <p class="text-xs text-gray-400" x-text="certs.length + ' emitidos'"></p>
            </div>
            <button @click="openNew()" class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg" style="background:rgba(29,158,117,.12);color:#1D9E75;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nuevo
            </button>
        </div>

        <div class="px-3 py-2 border-b">
            <input x-model="search" type="text" placeholder="Buscar alumno o curso..."
                   class="w-full text-xs rounded-lg px-3 py-2 border outline-none bg-gray-50 border-gray-200 focus:border-green-400">
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="filtered.length === 0">
                <div class="text-center py-10 text-xs text-gray-400">Sin certificados aún</div>
            </template>
            <template x-for="c in filtered" :key="c.id">
                <button @click="select(c)" class="w-full text-left px-4 py-3 border-b hover:bg-gray-50 transition-colors"
                        :class="selected?.id===c.id ? 'bg-green-50 border-l-2 border-l-green-500' : ''">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="c.alumno_nombre"></p>
                        <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0 font-medium"
                              :class="c.estado==='emitido' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                              x-text="c.estado==='emitido' ? '✓ Válido' : '✗ Revocado'"></span>
                    </div>
                    <p class="text-xs text-gray-400 truncate" x-text="c.curso_nombre"></p>
                    <p class="text-xs text-gray-300 mt-0.5 font-mono" x-text="c.codigo"></p>
                </button>
            </template>
        </div>
    </div>

    {{-- PANEL DERECHO --}}
    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{'hidden md:flex': panel==='list', 'flex': panel==='detail'}">

        {{-- TOPBAR --}}
        <div class="flex items-center justify-between px-5 py-3 border-b flex-shrink-0 bg-white">
            <div class="flex items-center gap-3">
                <button class="md:hidden text-gray-400" @click="panel='list'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div>
                    <p class="text-sm font-bold text-gray-800" x-show="creating">Nuevo certificado</p>
                    <p class="text-sm font-bold text-gray-800" x-show="!creating && selected" x-text="selected?.codigo + ' — ' + selected?.alumno_nombre"></p>
                    <p class="text-sm font-bold text-gray-400" x-show="!creating && !selected">Selecciona un certificado</p>
                </div>
            </div>
            <div class="flex items-center gap-2" x-show="creating || selected">
                <button @click="copyLink()" x-show="!creating && selected" title="Copiar link de verificación"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border hover:bg-gray-50 text-gray-500 border-gray-200">
                    🔗 Link
                </button>
                <button @click="copyWA()" x-show="!creating && selected" title="Copiar mensaje WhatsApp"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border hover:bg-gray-50 text-green-600 border-gray-200">
                    💬 WA
                </button>
                <button @click="printCert()" x-show="!creating && selected" title="Ver / Imprimir PDF"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border hover:bg-gray-50 text-gray-500 border-gray-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    PDF
                </button>
                <button @click="del()" x-show="!creating && selected"
                        class="text-xs px-2.5 py-1.5 rounded-lg border hover:bg-red-50 border-gray-200 text-red-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button @click="save()" :disabled="saving" class="flex items-center gap-1.5 text-xs font-bold px-4 py-1.5 rounded-lg" style="background:#1D9E75;color:#fff;" :style="saving?'opacity:.6':''">
                    <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                </button>
            </div>
        </div>

        {{-- VACÍO --}}
        <div x-show="!creating && !selected" class="flex-1 flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background:rgba(29,158,117,.1);">
                    <span class="text-3xl">📜</span>
                </div>
                <p class="text-sm font-semibold text-gray-500">Ningún certificado seleccionado</p>
                <p class="text-xs text-gray-400 mt-1 mb-4">Elige uno de la lista o emite uno nuevo</p>
                <button @click="openNew()" class="text-xs font-bold px-4 py-2 rounded-lg" style="background:#1D9E75;color:#fff;">+ Emitir certificado</button>
            </div>
        </div>

        {{-- FORMULARIO --}}
        <div x-show="creating || selected" class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-2xl mx-auto py-6 px-6 space-y-4">

                {{-- ALUMNO --}}
                <div class="rounded-xl border bg-white overflow-hidden">
                    <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:#1D9E75;color:#fff;">1</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Datos del alumno</p>
                            <p class="text-xs text-gray-400">El nombre aparece en el certificado tal como lo escribas</p>
                        </div>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Nombre completo <span class="text-red-400">*</span></label>
                            <input x-model="form.alumno_nombre" type="text" placeholder="Ej: María García López" class="inp w-full text-base font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">DNI</label>
                            <input x-model="form.alumno_dni" type="text" placeholder="12345678" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Email</label>
                            <input x-model="form.alumno_email" type="email" placeholder="correo@ejemplo.com" class="inp w-full">
                        </div>
                    </div>
                </div>

                {{-- CURSO --}}
                <div class="rounded-xl border bg-white overflow-hidden">
                    <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:#EF9F27;color:#fff;">2</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Datos del curso</p>
                        </div>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Nombre del curso / programa <span class="text-red-400">*</span></label>
                            <input x-model="form.curso_nombre" type="text" placeholder="Ej: Operador de Computadora con Microsoft Office" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Duración</label>
                            <input x-model="form.curso_horas" type="text" placeholder="Ej: 40 horas" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nivel</label>
                            <input x-model="form.curso_nivel" type="text" placeholder="Básico / Avanzado" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Instructor</label>
                            <input x-model="form.instructor" type="text" placeholder="Nombre del instructor" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Institución</label>
                            <input x-model="form.institucion" type="text" placeholder="Nombre de tu centro / academia" class="inp w-full">
                        </div>
                    </div>
                </div>

                {{-- FECHAS --}}
                <div class="rounded-xl border bg-white overflow-hidden">
                    <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:#534AB7;color:#fff;">3</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Fechas y estado</p>
                        </div>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Fecha de emisión <span class="text-red-400">*</span></label>
                            <input x-model="form.fecha_emision" type="date" class="inp w-full">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Vence (opcional)</label>
                            <input x-model="form.fecha_vencimiento" type="date" class="inp w-full">
                        </div>
                        <div x-show="!creating">
                            <label class="block text-xs text-gray-500 mb-1">Estado</label>
                            <select x-model="form.estado" class="inp w-full">
                                <option value="emitido">✓ Emitido / Válido</option>
                                <option value="revocado">✗ Revocado</option>
                            </select>
                        </div>
                        <div x-show="!creating">
                            <label class="block text-xs text-gray-500 mb-1">Código único</label>
                            <input :value="selected?.codigo" type="text" readonly class="inp w-full bg-gray-50 font-mono text-xs text-gray-500">
                        </div>
                    </div>
                </div>

                {{-- VISTA PREVIA LINK --}}
                <div x-show="!creating && selected" class="rounded-xl border bg-white p-4">
                    <p class="text-xs font-semibold text-gray-600 mb-2">Link de verificación pública</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-mono break-all text-gray-600"
                              x-text="'{{ url('/cert') }}/' + (selected?.codigo || '')"></code>
                        <button @click="copyLink()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs text-gray-600 whitespace-nowrap">Copiar</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">Cualquier persona puede abrir este link para verificar la autenticidad del certificado.</p>
                </div>

                {{-- BOTONES --}}
                <div class="flex gap-2 pb-4">
                    <button @click="copyWA()" x-show="!creating && selected"
                            class="flex-1 flex items-center justify-center gap-2 text-xs font-semibold py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-600">
                        💬 Enviar por WhatsApp
                    </button>
                    <button @click="printCert()" x-show="!creating && selected"
                            class="flex-1 flex items-center justify-center gap-2 text-xs font-semibold py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-600">
                        📄 Ver / Descargar PDF
                    </button>
                    <button @click="save()" :disabled="saving"
                            class="flex items-center justify-center gap-2 text-xs font-bold px-6 py-3 rounded-xl" style="background:#1D9E75;color:#fff;" :style="saving?'opacity:.6':''">
                        <span x-text="saving ? 'Guardando...' : (creating ? 'Emitir certificado' : 'Guardar cambios')"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.inp { background:#fff; border:1px solid #e5e7eb; color:#374151; border-radius:8px; padding:8px 12px; font-size:13px; outline:none; transition:border-color .15s; width:100%; }
.inp:focus { border-color:#1D9E75; }
.inp::placeholder { color:#9ca3af; }
.inp:read-only { cursor:default; }
</style>

@php
$certsJs = $certificados->map(fn($c) => [
    'id'               => $c->id,
    'codigo'           => $c->codigo,
    'alumno_nombre'    => $c->alumno_nombre,
    'alumno_dni'       => $c->alumno_dni ?? '',
    'alumno_email'     => $c->alumno_email ?? '',
    'curso_nombre'     => $c->curso_nombre,
    'curso_horas'      => $c->curso_horas ?? '',
    'curso_nivel'      => $c->curso_nivel ?? '',
    'fecha_emision'    => $c->fecha_emision->format('Y-m-d'),
    'fecha_vencimiento'=> $c->fecha_vencimiento?->format('Y-m-d') ?? '',
    'instructor'       => $c->instructor ?? '',
    'institucion'      => $c->institucion ?? '',
    'estado'           => $c->estado,
])->values()->all();
@endphp

<script>
function certApp() {
    return {
        certs:    @json($certsJs),
        panel:    'list',
        selected: null,
        creating: false,
        saving:   false,
        search:   '',

        form: {
            alumno_nombre:'', alumno_dni:'', alumno_email:'',
            curso_nombre:'', curso_horas:'', curso_nivel:'',
            fecha_emision: new Date().toISOString().slice(0,10),
            fecha_vencimiento:'',
            instructor:'', institucion:'', estado:'emitido',
        },

        init() {
            if (this.certs.length > 0) this.select(this.certs[0]);
        },

        get filtered() {
            if (!this.search) return this.certs;
            const s = this.search.toLowerCase();
            return this.certs.filter(c =>
                c.alumno_nombre.toLowerCase().includes(s) ||
                c.curso_nombre.toLowerCase().includes(s) ||
                c.codigo.toLowerCase().includes(s)
            );
        },

        select(c) {
            this.selected = {...c};
            this.form     = {...c};
            this.creating = false;
            if (window.innerWidth < 768) this.panel = 'detail';
        },

        openNew() {
            this.selected = null;
            this.creating = true;
            this.form = {
                alumno_nombre:'', alumno_dni:'', alumno_email:'',
                curso_nombre:'', curso_horas:'', curso_nivel:'',
                fecha_emision: new Date().toISOString().slice(0,10),
                fecha_vencimiento:'',
                instructor:'', institucion:'', estado:'emitido',
            };
            if (window.innerWidth < 768) this.panel = 'detail';
        },

        async save() {
            if (!this.form.alumno_nombre || !this.form.curso_nombre || !this.form.fecha_emision) {
                alert('Completa: nombre del alumno, curso y fecha de emisión.');
                return;
            }
            this.saving = true;
            const base   = '{{ route("certificados.store") }}';
            const url    = this.creating ? base : base + '/' + this.selected.id;
            const method = this.creating ? 'POST' : 'PUT';
            const res    = await fetch(url, {
                method,
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                body: JSON.stringify(this.form)
            });
            const data = await res.json();
            if (this.creating) {
                this.certs.unshift(data.certificado);
            } else {
                const idx = this.certs.findIndex(c => c.id === data.certificado.id);
                if (idx > -1) this.certs[idx] = data.certificado;
            }
            this.selected = data.certificado;
            this.form     = {...data.certificado};
            this.creating = false;
            this.saving   = false;
        },

        async del() {
            if (!confirm('¿Eliminar este certificado? Esta acción no se puede deshacer.')) return;
            const base = '{{ route("certificados.store") }}';
            await fetch(base + '/' + this.selected.id, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
            });
            this.certs    = this.certs.filter(c => c.id !== this.selected.id);
            this.selected = null;
            this.creating = false;
        },

        printCert() {
            window._certGen.print(this.selected);
        },

        copyLink() {
            const url = '{{ url("/cert") }}/' + this.selected.codigo;
            navigator.clipboard.writeText(url).then(() => alert('Link copiado.'));
        },

        copyWA() {
            const c   = this.selected;
            const url = '{{ url("/cert") }}/' + c.codigo;
            const msg = '📜 *Certificado Digital — ' + c.curso_nombre + '*\n\n'
                + 'Hola ' + c.alumno_nombre + ' 👋\n\n'
                + 'Tu certificado ha sido emitido exitosamente.\n\n'
                + '✅ *Curso:* ' + c.curso_nombre + '\n'
                + (c.curso_horas  ? '🕐 *Duración:* ' + c.curso_horas + '\n' : '')
                + (c.curso_nivel  ? '⭐ *Nivel:* '    + c.curso_nivel  + '\n' : '')
                + '📅 *Fecha de emisión:* ' + c.fecha_emision + '\n\n'
                + '🔗 *Verifica tu certificado aquí:*\n' + url + '\n\n'
                + '_El código ' + c.codigo + ' garantiza la autenticidad de este documento._';
            navigator.clipboard.writeText(msg).then(() => alert('Mensaje copiado. Pégalo en WhatsApp.'));
        },
    };
}
</script>

</x-slot>
</x-app-layout>
