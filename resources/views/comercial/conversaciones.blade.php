<x-portal-layout layout="comercial" :project="$project" pageTitle="Conversaciones Bot">

<div style="display:flex;height:100%;overflow:hidden;">

{{-- ── Lista sesiones ── --}}
<div style="width:340px;flex-shrink:0;border-right:1px solid #E5E8EF;display:flex;flex-direction:column;background:#fff;">

    {{-- Header --}}
    <div style="padding:12px 14px;border-bottom:1px solid #E5E8EF;flex-shrink:0;">
        <h1 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 8px;">Conversaciones Bot</h1>

        {{-- Búsqueda --}}
        <form method="GET" style="display:flex;gap:6px;margin-bottom:8px;">
            <input type="hidden" name="estado" value="{{ $estado }}">
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Buscar nombre, teléfono..."
                   style="flex:1;font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;color:#374151;">
            <button type="submit" style="font-size:12px;background:#7C3AED;color:#fff;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;">🔍</button>
        </form>

        {{-- Tabs estado --}}
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            @foreach(['todos'=>'Todos','completado'=>'Completados','en_proceso'=>'En proceso','inactivo'=>'Inactivos'] as $key=>$label)
            <a href="?estado={{ $key }}&buscar={{ $buscar }}"
               style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:99px;border:1px solid;text-decoration:none;white-space:nowrap;
                      {{ $estado===$key ? 'background:#7C3AED;color:#fff;border-color:#7C3AED;' : 'background:#fff;color:#6B7280;border-color:#E5E8EF;' }}">
                {{ $label }}
                <span style="font-size:9px;margin-left:2px;">{{ $cnts[$key] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Lista --}}
    <div style="flex:1;overflow-y:auto;" id="lista-sesiones">
        @forelse($sesiones as $s)
        @php
            $fecha = $s->last_activity_at
                ? \Carbon\Carbon::parse($s->last_activity_at)->timezone('America/Lima')->diffForHumans()
                : '—';
            $nombreDisplay = $s->nombre ?: $s->wa_number;
            $iniciales = strtoupper(substr($nombreDisplay, 0, 2));
            $estadoColor = match($s->current_state) {
                'confirmacion_final' => '#DCFCE7',
                'menu_principal', 'inicio' => '#F3F4F6',
                default => '#FEF9C3',
            };
            $estadoTexto = match($s->current_state) {
                'confirmacion_final' => '✅ Completado',
                'menu_principal'     => '📋 Menú',
                'inicio'             => '👋 Inicio',
                'comprobante_recibido' => '🧾 Comprobante',
                'pedir_comprobante'  => '⏳ Esperando comprobante',
                'confirmacion_pago'  => '💳 Confirmando pago',
                default => '💬 ' . str_replace('_', ' ', $s->current_state),
            };
        @endphp
        <div onclick="abrirSesion({{ $s->id }}, '{{ addslashes($nombreDisplay) }}', '{{ $s->wa_number }}')"
             id="ses-{{ $s->id }}"
             style="padding:10px 14px;border-bottom:1px solid #F3F4F6;cursor:pointer;display:flex;gap:10px;align-items:center;transition:background .1s;"
             onmouseover="this.style.background='#F8F9FB'" onmouseout="if(sesActiva!=={{ $s->id }})this.style.background='#fff'">

            {{-- Avatar --}}
            <div style="width:38px;height:38px;border-radius:50%;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="font-size:13px;font-weight:700;color:#7C3AED;">{{ $iniciales }}</span>
            </div>

            {{-- Info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;">
                    <span style="font-size:12px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">
                        {{ $nombreDisplay }}
                    </span>
                    <span style="font-size:10px;color:#9CA3AF;white-space:nowrap;margin-left:4px;">{{ $fecha }}</span>
                </div>
                <div style="font-size:10px;color:#6B7280;margin-top:1px;">{{ $s->wa_number }}</div>
                <div style="margin-top:3px;">
                    <span style="font-size:9px;font-weight:600;padding:1px 6px;border-radius:99px;background:{{ $estadoColor }};color:#374151;">
                        {{ $estadoTexto }}
                    </span>
                    @if($s->rifa)
                    <span style="font-size:9px;color:#9CA3AF;margin-left:4px;">{{ $s->rifa }}</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:40px 20px;color:#9CA3AF;">
            <div style="font-size:36px;margin-bottom:8px;">💬</div>
            <p style="font-size:12px;">Sin conversaciones</p>
        </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($sesiones->hasPages())
    <div style="padding:8px 14px;border-top:1px solid #E5E8EF;display:flex;gap:6px;justify-content:center;">
        @if($sesiones->onFirstPage())
        <span style="font-size:11px;color:#D1D5DB;padding:4px 10px;">← Anterior</span>
        @else
        <a href="{{ $sesiones->previousPageUrl() }}&estado={{ $estado }}&buscar={{ $buscar }}"
           style="font-size:11px;color:#7C3AED;padding:4px 10px;text-decoration:none;border:1px solid #E5E8EF;border-radius:6px;">← Anterior</a>
        @endif
        <span style="font-size:11px;color:#6B7280;padding:4px 6px;">{{ $sesiones->currentPage() }} / {{ $sesiones->lastPage() }}</span>
        @if($sesiones->hasMorePages())
        <a href="{{ $sesiones->nextPageUrl() }}&estado={{ $estado }}&buscar={{ $buscar }}"
           style="font-size:11px;color:#7C3AED;padding:4px 10px;text-decoration:none;border:1px solid #E5E8EF;border-radius:6px;">Siguiente →</a>
        @else
        <span style="font-size:11px;color:#D1D5DB;padding:4px 10px;">Siguiente →</span>
        @endif
    </div>
    @endif
</div>

{{-- ── Panel detalle ── --}}
<div style="flex:1;display:flex;flex-direction:column;background:#F0F2F5;min-width:0;">

    {{-- Estado vacío --}}
    <div id="ses-vacia" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;color:#9CA3AF;">
        <div style="font-size:60px;">💬</div>
        <p style="font-size:14px;font-weight:500;">Selecciona una conversación</p>
    </div>

    {{-- Header detalle --}}
    <div id="det-header" style="display:none;padding:10px 16px;background:#fff;border-bottom:1px solid #E5E8EF;flex-shrink:0;align-items:center;gap:10px;">
        <div id="det-avatar" style="width:36px;height:36px;border-radius:50%;background:#EDE9FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span id="det-iniciales" style="font-size:12px;font-weight:700;color:#7C3AED;"></span>
        </div>
        <div style="flex:1;">
            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;" id="det-nombre"></p>
            <p style="font-size:11px;color:#9CA3AF;margin:0;" id="det-wa"></p>
        </div>
        <a id="det-wa-link" href="#" target="_blank"
           style="font-size:11px;font-weight:600;background:#DCFCE7;color:#15803D;padding:5px 12px;border-radius:8px;text-decoration:none;">
            💬 WhatsApp
        </a>
    </div>

    {{-- Datos del usuario --}}
    <div id="det-datos" style="display:none;padding:12px 16px;background:#fff;border-bottom:1px solid #E5E8EF;flex-shrink:0;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;" id="det-campos"></div>
    </div>

    {{-- Contenedor scroll --}}
    <div id="det-scroll" style="flex:1;overflow-y:auto;padding:16px;display:none;flex-direction:column;gap:6px;">
        <div id="det-estado-info" style="text-align:center;padding:20px;color:#9CA3AF;font-size:12px;"></div>
    </div>

    {{-- Spinner --}}
    <div id="det-loading" style="display:none;flex:1;align-items:center;justify-content:center;">
        <div style="width:28px;height:28px;border:3px solid #E5E8EF;border-top-color:#7C3AED;border-radius:50%;animation:spin .7s linear infinite;"></div>
    </div>
</div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.chip {
    display:inline-flex;flex-direction:column;
    background:#F9FAFB;border:1px solid #E5E8EF;
    border-radius:8px;padding:6px 10px;min-width:100px;
}
.chip-label { font-size:9px;color:#9CA3AF;font-weight:600;text-transform:uppercase;margin-bottom:2px; }
.chip-val   { font-size:12px;color:#111827;font-weight:600; }
.burbuja-entrada {
    align-self:flex-start;background:#fff;border-radius:0 12px 12px 12px;
    padding:8px 12px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,.08);
}
.burbuja-salida {
    align-self:flex-end;background:#EDE9FE;border-radius:12px 0 12px 12px;
    padding:8px 12px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,.08);
}
.burbuja-texto { font-size:12px;color:#111827;line-height:1.5;white-space:pre-wrap;word-break:break-word; }
.burbuja-hora  { font-size:10px;color:#9CA3AF;margin-top:3px;text-align:right; }
</style>

<script>
let sesActiva = null;

async function abrirSesion(id, nombre, waNumber) {
    sesActiva = id;

    document.querySelectorAll('[id^="ses-"]').forEach(el => el.style.background = '#fff');
    const el = document.getElementById('ses-' + id);
    if (el) el.style.background = '#F5F3FF';

    document.getElementById('ses-vacia').style.display    = 'none';
    document.getElementById('det-header').style.display   = 'none';
    document.getElementById('det-datos').style.display    = 'none';
    document.getElementById('det-scroll').style.display   = 'none';
    document.getElementById('det-loading').style.display  = 'flex';

    const res  = await fetch('{{ url("/bixosales/conversaciones") }}/' + id + '/mensajes');
    const data = await res.json();

    document.getElementById('det-loading').style.display = 'none';
    if (!data.ok) return;

    const d = data.data || {};

    // Header
    const iniciales = nombre.substring(0, 2).toUpperCase();
    document.getElementById('det-iniciales').textContent = iniciales;
    document.getElementById('det-nombre').textContent    = nombre;
    document.getElementById('det-wa').textContent        = waNumber;
    document.getElementById('det-wa-link').href          = 'https://wa.me/' + waNumber;
    document.getElementById('det-header').style.display  = 'flex';

    // Chips de datos
    const campos = document.getElementById('det-campos');
    campos.innerHTML = '';
    const info = [
        ['Nombre',   d.nombre],
        ['Celular',  d.celular],
        ['Email',    d.email],
        ['Ciudad',   d.ciudad],
        ['Rifa',     d.rifaNombre],
        ['Total',    d.rifaTotal ? 'S/ ' + d.rifaTotal : null],
        ['Tickets',  d.rifaTickets],
        ['Estado',   data.sesion?.current_state?.replace(/_/g,' ')],
    ];
    info.forEach(([label, val]) => {
        if (!val) return;
        campos.innerHTML += `<div class="chip"><span class="chip-label">${label}</span><span class="chip-val">${escHtml(String(val))}</span></div>`;
    });
    document.getElementById('det-datos').style.display = campos.innerHTML ? 'block' : 'none';

    // Mensajes o info de estado
    const scroll = document.getElementById('det-scroll');
    scroll.style.display = 'flex';
    scroll.innerHTML = '';

    if (data.mensajes && data.mensajes.length) {
        data.mensajes.forEach(m => {
            const esSalida = m.direccion === 'saliente';
            const hora = new Date(m.created_at).toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit', timeZone:'America/Lima' });
            const div = document.createElement('div');
            div.className = esSalida ? 'burbuja-salida' : 'burbuja-entrada';
            div.innerHTML = `<div class="burbuja-texto">${escHtml(m.contenido)}</div><div class="burbuja-hora">${hora}</div>`;
            scroll.appendChild(div);
        });
    } else {
        // Sin mensajes guardados — mostrar resumen del estado
        const s = data.sesion;
        const estadoLabel = (s?.current_state || '').replace(/_/g, ' ');
        scroll.innerHTML = `
            <div style="text-align:center;padding:30px;color:#9CA3AF;">
                <div style="font-size:40px;margin-bottom:8px;">📊</div>
                <p style="font-size:13px;font-weight:600;color:#374151;">Estado del flujo</p>
                <p style="font-size:12px;margin-top:4px;">${escHtml(estadoLabel)}</p>
                <p style="font-size:11px;margin-top:12px;color:#D1D5DB;">Los mensajes de este canal no se almacenan en el sistema aún.</p>
            </div>`;
    }

    scroll.scrollTop = scroll.scrollHeight;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

</x-portal-layout>
