<x-portal-layout layout="comercial" :project="$project" pageTitle="Tickets Manuales">

<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;">

{{-- ── Header ── --}}
<div style="padding:12px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">

    {{-- Fila 1: título + KPIs --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div>
            <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Tickets Manuales</h1>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">WordPress · {{ $project->name }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Total tickets</p>
                <p style="font-size:16px;font-weight:800;color:#7C3AED;margin:0;">{{ $stats['total_tickets'] ?? $total }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Clientes</p>
                <p style="font-size:16px;font-weight:800;color:#111827;margin:0;">{{ $stats['total_clientes'] ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Fila 2: Búsqueda + DNI rápido --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <form method="GET" action="{{ route('bixosales.tickets.wp') }}" style="display:flex;gap:8px;align-items:center;" onsubmit="mostrarLoaderForm(this)">
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, DNI, teléfono o ticket..."
                   style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 12px;outline:none;width:280px;font-family:inherit;color:#374151;">
            <button type="submit" id="btn-buscar-form"
                    style="font-size:12px;background:#7C3AED;color:#fff;padding:5px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:5px;">
                Buscar
            </button>
            @if($buscar)
            <a href="{{ route('bixosales.tickets.wp') }}"
               style="font-size:11px;color:#9CA3AF;text-decoration:none;">✕ Limpiar</a>
            @endif
        </form>

        {{-- Búsqueda rápida por DNI --}}
        <div style="display:flex;gap:8px;align-items:center;margin-left:8px;padding-left:12px;border-left:1px solid #E5E8EF;">
            <input type="text" id="dni-rapido" placeholder="DNI exacto..." maxlength="8"
                   style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 10px;outline:none;width:130px;font-family:inherit;color:#374151;">
            <button onclick="buscarDni()"
                    style="font-size:12px;background:#F3F4F6;color:#374151;padding:5px 12px;border-radius:8px;border:1px solid #E5E8EF;cursor:pointer;font-weight:600;">
                🔍 DNI
            </button>
        </div>
    </div>
</div>

{{-- ── Resultado búsqueda DNI (modal inline) ── --}}
<div id="dni-result" style="display:none;margin:8px 16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px 16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <span style="font-size:12px;font-weight:700;color:#15803D;" id="dni-result-titulo">—</span>
        <button onclick="cerrarDni()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:14px;">✕</button>
    </div>
    <div id="dni-result-body"></div>
</div>

{{-- ── Lista ── --}}
<div style="flex:1;overflow-y:auto;padding:12px;background:#F8F9FB;">

    @if(empty($tickets))
    <div style="text-align:center;padding:60px 20px;color:#9CA3AF;">
        <div style="font-size:40px;margin-bottom:10px;">🎟</div>
        <p style="font-size:13px;">No hay tickets {{ $buscar ? 'para "' . $buscar . '"' : 'registrados' }}</p>
    </div>
    @else

    <div style="display:flex;flex-direction:column;gap:4px;">
    @foreach($tickets as $t)
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;
                transition:box-shadow .12s,border-color .12s;"
         onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.07)';this.style.borderColor='#7C3AED';"
         onmouseout="this.style.boxShadow='none';this.style.borderColor='#E5E8EF';">

        {{-- Badge ticket --}}
        <div style="width:auto;min-width:70px;padding:0 8px;height:36px;border-radius:8px;background:#F5F3FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span style="font-size:11px;font-weight:800;color:#7C3AED;">{{ $t['codigo'] }}</span>
        </div>

        {{-- Info --}}
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:#111827;">{{ trim(($t['nombres'] ?? '') . ' ' . ($t['apellidos'] ?? '')) ?: '—' }}</span>
                <span style="font-size:11px;color:#9CA3AF;">· DNI {{ $t['dni'] ?? '—' }}</span>
                @if(!empty($t['telefono']))
                <span style="font-size:11px;color:#9CA3AF;">· {{ $t['telefono'] }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap;">
                @if(!empty($t['ciudad']))
                <span style="font-size:11px;color:#6B7280;">📍 {{ $t['ciudad'] }}</span>
                @endif
                @if(!empty($t['punto_venta']))
                <span style="font-size:11px;color:#6B7280;">· 🏪 {{ $t['punto_venta'] }}</span>
                @endif
                <span style="font-size:10px;color:#9CA3AF;">
                    {{ \Carbon\Carbon::parse($t['created_at'])->timezone('America/Lima')->locale('es')->diffForHumans() }}
                </span>
            </div>
        </div>

        {{-- Estado --}}
        <span style="font-size:10px;font-weight:600;padding:3px 10px;border-radius:99px;border:1px solid;white-space:nowrap;
              {{ ($t['estado'] ?? '') === 'activo' ? 'background:#DCFCE7;color:#15803D;border-color:#BBF7D0;' : 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;' }}">
            {{ $t['estado'] ?? 'activo' }}
        </span>

        {{-- WA --}}
        @if(!empty($t['telefono']))
        @php $wa = preg_replace('/\D/', '', $t['telefono']); @endphp
        <a href="https://wa.me/51{{ $wa }}" target="_blank"
           style="font-size:18px;text-decoration:none;opacity:.7;" title="WhatsApp"
           onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.7'">💬</a>
        @endif

        {{-- Eliminar --}}
        <button onclick="eliminarTicketWP('{{ $t['codigo'] }}', this)"
                style="font-size:11px;background:none;border:none;cursor:pointer;color:#D1D5DB;padding:2px 4px;"
                title="Eliminar de WordPress"
                onmouseover="this.style.color='#EF4444'" onmouseout="this.style.color='#D1D5DB'">🗑️</button>
    </div>
    @endforeach
    </div>

    {{-- Paginación --}}
    <div style="display:flex;justify-content:center;gap:8px;margin-top:16px;align-items:center;">
        @if($offset > 0)
        <a href="{{ request()->fullUrlWithQuery(['offset' => max(0, $offset - $limit)]) }}"
           style="font-size:12px;padding:5px 14px;border-radius:8px;border:1px solid #E5E8EF;background:#fff;color:#374151;text-decoration:none;">← Anterior</a>
        @endif
        <span style="font-size:11px;color:#9CA3AF;">
            Mostrando {{ $offset + 1 }}–{{ min($offset + $limit, $total) }} de {{ $total }} tickets
        </span>
        @if($offset + $limit < $total)
        <a href="{{ request()->fullUrlWithQuery(['offset' => $offset + $limit]) }}"
           style="font-size:12px;padding:5px 14px;border-radius:8px;border:1px solid #E5E8EF;background:#fff;color:#374151;text-decoration:none;">Siguiente →</a>
        @endif
    </div>

    @endif
</div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.bixo-spinner {
    width:14px;height:14px;border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff;border-radius:50%;
    animation:spin .7s linear infinite;display:inline-block;
}
.bixo-spinner-purple {
    width:14px;height:14px;border:2px solid rgba(124,58,237,.2);
    border-top-color:#7C3AED;border-radius:50%;
    animation:spin .7s linear infinite;display:inline-block;
}
</style>
<script>
function mostrarLoaderForm(form) {
    const btn = document.getElementById('btn-buscar-form');
    btn.innerHTML = '<span class="bixo-spinner"></span> Buscando...';
    btn.disabled = true;
}

async function buscarDni() {
    const dni = document.getElementById('dni-rapido').value.trim();
    if (!dni) return;

    const btnDni = document.querySelector('[onclick="buscarDni()"]');
    const originalHtml = btnDni.innerHTML;
    btnDni.innerHTML = '<span class="bixo-spinner-purple"></span>';
    btnDni.disabled = true;

    const box  = document.getElementById('dni-result');
    const tit  = document.getElementById('dni-result-titulo');
    const body = document.getElementById('dni-result-body');

    box.style.display = 'block';
    tit.textContent   = 'Buscando DNI ' + dni + '...';
    body.innerHTML    = '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;"><span class="bixo-spinner-purple"></span><span style="font-size:12px;color:#6B7280;">Consultando WordPress...</span></div>';

    try {
        const res  = await fetch('{{ route("bixosales.tickets.wp.buscar") }}?dni=' + encodeURIComponent(dni));
        const data = await res.json();

        if (!data.ok || !data.tickets.length) {
            tit.textContent = 'DNI ' + dni + ' — sin resultados';
            body.innerHTML  = '<p style="font-size:12px;color:#6B7280;margin:0;">No se encontró ningún ticket para este DNI.</p>';
        } else {
            const t = data.tickets[0];
            const nombre = ((t.nombres || '') + ' ' + (t.apellidos || '')).trim();
            tit.textContent = nombre + ' · DNI ' + dni + ' · ' + data.total + ' ticket' + (data.total > 1 ? 's' : '');
            body.innerHTML = data.tickets.map(tk => `
                <div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #D1FAE5;">
                    <span style="font-size:11px;font-weight:800;color:#7C3AED;min-width:80px;">${tk.codigo}</span>
                    <span style="font-size:11px;color:#374151;">${((tk.nombres||'')+' '+(tk.apellidos||'')).trim()}</span>
                    <span style="font-size:11px;color:#6B7280;">${tk.ciudad || ''}</span>
                    ${tk.telefono ? `<a href="https://wa.me/51${tk.telefono.replace(/\D/g,'')}" target="_blank" style="font-size:16px;text-decoration:none;margin-left:auto;">💬</a>` : '<span style="margin-left:auto;"></span>'}
                </div>
            `).join('');
        }
    } catch(e) {
        tit.textContent = 'Error al consultar';
        body.innerHTML  = '<p style="font-size:12px;color:#DC2626;margin:0;">No se pudo conectar con WordPress.</p>';
    }

    btnDni.innerHTML = originalHtml;
    btnDni.disabled  = false;
}

function cerrarDni() {
    document.getElementById('dni-result').style.display = 'none';
    document.getElementById('dni-rapido').value = '';
}

async function eliminarTicketWP(codigo, btn) {
    if (!confirm('¿Eliminar ' + codigo + ' de WordPress?')) return;
    btn.disabled = true;
    btn.textContent = '⏳';
    try {
        const res  = await fetch('{{ route("bixosales.tickets.wp.eliminar") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo })
        });
        const data = await res.json();
        if (data.ok) {
            btn.closest('div[style*="border-radius:10px"]').remove();
        } else {
            alert('Error: ' + (data.error || 'no se pudo eliminar'));
            btn.disabled = false;
            btn.textContent = '🗑️';
        }
    } catch(e) {
        alert('Error de conexión');
        btn.disabled = false;
        btn.textContent = '🗑️';
    }
}

document.getElementById('dni-rapido').addEventListener('keydown', e => {
    if (e.key === 'Enter') buscarDni();
});
</script>
</x-portal-layout>
