<x-admin-layout title="Gestión de Turnos — {{ $project->name }}">
<div style="display:flex;flex-direction:column;gap:20px;" x-data="turnosPage()">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <a href="{{ route('admin.projects.show', $project) }}"
                   style="color:#6366f1;font-size:12px;text-decoration:none;"
                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                    ← {{ $project->name }}
                </a>
            </div>
            <h1 style="font-size:20px;font-weight:800;color:#f1f5f9;margin:0;">Gestión de Turnos</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Define el horario semanal de cada empleado</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:12px;color:#64748b;">{{ $employees->count() }} empleados activos</span>
            <button @click="showBulk=true"
                    style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:9px;border:none;background:#6366f1;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Asignación masiva
            </button>
        </div>
    </div>

    {{-- Indicador de hoy --}}
    @php
        $hoy = (now()->dayOfWeek + 6) % 7; // 0=Lun, 6=Dom
        $ahora = now()->format('H:i');
    @endphp
    <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;background:#0f172a;border:1px solid #1e293b;border-radius:10px;font-size:12px;">
        <div style="width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;animation:pulse 2s infinite;"></div>
        <span style="color:#94a3b8;">Hoy es <strong style="color:#f1f5f9;">{{ $days[$hoy] }}</strong> — {{ $ahora }}</span>
        <span style="color:#4b5563;">·</span>
        <span style="color:#64748b;">Los empleados resaltados en verde están en turno activo ahora mismo</span>
    </div>

    @if($employees->isEmpty())
    <div style="text-align:center;padding:60px 20px;background:#0f172a;border:1px solid #1e293b;border-radius:12px;">
        <p style="font-size:36px;margin:0 0 12px;">👥</p>
        <p style="font-size:14px;color:#64748b;margin:0;">No hay empleados activos en este proyecto.</p>
        <a href="{{ route('admin.projects.show', $project) }}"
           style="display:inline-block;margin-top:16px;color:#6366f1;font-size:13px;text-decoration:underline;">
            Ir al proyecto para agregar empleados
        </a>
    </div>
    @else

    {{-- Grilla empleados --}}
    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach($employees as $emp)
        @php
            $empSchedules = $scheduleMap[$emp->id] ?? [];
            // ¿Está en turno ahora?
            $sHoy = $empSchedules[$hoy] ?? null;
            $enTurnoAhora = $sHoy && $sHoy->is_active
                && $ahora >= substr($sHoy->start_time, 0, 5)
                && $ahora <= substr($sHoy->end_time, 0, 5);
        @endphp

        <div style="background:#0f172a;border:1px solid {{ $enTurnoAhora ? '#16a34a' : '#1e293b' }};border-radius:12px;overflow:hidden;transition:border-color .2s;">

            {{-- Cabecera empleado --}}
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #1e293b;cursor:pointer;"
                 @click="toggle({{ $emp->id }})">
                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;
                     background:{{ $enTurnoAhora ? '#14532d' : '#1e2d3d' }};color:{{ $enTurnoAhora ? '#4ade80' : '#60a5fa' }};">
                    {{ strtoupper(substr($emp->name, 0, 2)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <p style="font-size:13px;font-weight:700;color:#f1f5f9;margin:0;">{{ $emp->name }}</p>
                        @if($enTurnoAhora)
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:#14532d;color:#4ade80;">EN TURNO</span>
                        @endif
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:2px 0 0;">{{ $emp->role ?? 'Sin rol' }}{{ $emp->area ? ' · '.$emp->area : '' }}</p>
                </div>
                {{-- Resumen semana --}}
                <div style="display:flex;gap:3px;align-items:center;flex-shrink:0;">
                    @foreach($days as $d => $nombre)
                    @php $s = $empSchedules[$d] ?? null; @endphp
                    <div title="{{ $nombre }}{{ $s && $s->is_active ? ': '.substr($s->start_time,0,5).'–'.substr($s->end_time,0,5) : ': Libre' }}"
                         style="width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;
                         background:{{ $s && $s->is_active ? ($d==$hoy && $enTurnoAhora ? '#14532d' : '#1e3a5f') : '#1a1f2e' }};
                         color:{{ $s && $s->is_active ? ($d==$hoy && $enTurnoAhora ? '#4ade80' : '#60a5fa') : '#374151' }};
                         border:1px solid {{ $s && $s->is_active ? ($d==$hoy && $enTurnoAhora ? '#166534' : '#1e3a8a') : 'transparent' }};">
                        {{ substr($nombre, 0, 1) }}
                    </div>
                    @endforeach
                </div>
                <svg :style="open.includes({{ $emp->id }}) ? 'transform:rotate(180deg)' : ''"
                     style="width:16px;height:16px;color:#475569;flex-shrink:0;transition:transform .2s;"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>

            {{-- Formulario horario (expandible) --}}
            <div x-show="open.includes({{ $emp->id }})" x-cloak style="padding:16px;">
                <form method="POST" action="{{ route('admin.turnos.save-employee', [$project, $emp]) }}">
                    @csrf

                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-bottom:14px;">
                        @foreach($days as $d => $nombre)
                        @php $s = $empSchedules[$d] ?? null; $activo = $s && $s->is_active; @endphp
                        <div style="display:flex;flex-direction:column;gap:5px;"
                             x-data="{ on: {{ $activo ? 'true' : 'false' }} }">
                            {{-- Día header --}}
                            <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;">
                                <span style="font-size:11px;font-weight:700;color:{{ $d==$hoy ? '#818cf8' : '#94a3b8' }};">
                                    {{ $nombre }}
                                </span>
                                <input type="checkbox" name="schedules[{{ $d }}][active]" value="1"
                                       {{ $activo ? 'checked' : '' }}
                                       x-model="on" style="accent-color:#6366f1;width:14px;height:14px;cursor:pointer;">
                            </label>
                            {{-- Hora inicio --}}
                            <input type="time" name="schedules[{{ $d }}][start]"
                                   value="{{ $s ? substr($s->start_time,0,5) : '08:00' }}"
                                   :disabled="!on"
                                   style="width:100%;font-size:11px;border-radius:6px;padding:5px 6px;font-family:inherit;
                                          background:#0a0f1a;border:1px solid #1e293b;color:#e2e8f0;outline:none;"
                                   :style="!on ? 'opacity:.35;pointer-events:none;' : ''">
                            {{-- Hora fin --}}
                            <input type="time" name="schedules[{{ $d }}][end]"
                                   value="{{ $s ? substr($s->end_time,0,5) : '17:00' }}"
                                   :disabled="!on"
                                   style="width:100%;font-size:11px;border-radius:6px;padding:5px 6px;font-family:inherit;
                                          background:#0a0f1a;border:1px solid #1e293b;color:#e2e8f0;outline:none;"
                                   :style="!on ? 'opacity:.35;pointer-events:none;' : ''">
                        </div>
                        @endforeach
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" @click="close({{ $emp->id }})"
                                style="padding:7px 14px;border-radius:8px;border:1px solid #1e293b;background:transparent;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;">
                            Cancelar
                        </button>
                        <button type="submit"
                                style="padding:7px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;font-size:12px;font-weight:600;cursor:pointer;">
                            Guardar horario
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal asignación masiva --}}
    <div x-show="showBulk" x-cloak
         style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);backdrop-filter:blur(3px);">
        <div style="background:#0f172a;border:1px solid #1e293b;border-radius:16px;width:640px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(0,0,0,.6);">

            <div style="padding:18px 20px;border-bottom:1px solid #1e293b;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="font-size:15px;font-weight:700;color:#f1f5f9;margin:0;">Asignación masiva de turno</p>
                    <p style="font-size:11px;color:#64748b;margin:3px 0 0;">Aplica el mismo horario a varios empleados a la vez</p>
                </div>
                <button @click="showBulk=false" style="background:none;border:none;color:#475569;cursor:pointer;padding:4px;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div style="overflow-y:auto;flex:1;">
                <form method="POST" action="{{ route('admin.turnos.save-bulk', $project) }}" id="bulkForm">
                    @csrf

                    {{-- Selección empleados --}}
                    <div style="padding:16px 20px;border-bottom:1px solid #1e293b;">
                        <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                            Empleados a actualizar
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($employees as $emp)
                            <label style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-radius:8px;border:1px solid #1e293b;cursor:pointer;font-size:12px;color:#94a3b8;"
                                   x-bind:style="bulkSel.includes({{ $emp->id }}) ? 'background:#1e2d3d;border-color:#3b82f6;color:#60a5fa;' : ''">
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                       x-model="bulkSel" :value="{{ $emp->id }}"
                                       style="accent-color:#6366f1;" @click.stop>
                                {{ $emp->name }}
                            </label>
                            @endforeach
                        </div>
                        <div style="display:flex;gap:10px;margin-top:8px;">
                            <button type="button" @click="bulkSel={{ $employees->pluck('id') }}"
                                    style="font-size:11px;color:#6366f1;background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;">
                                Seleccionar todos
                            </button>
                            <button type="button" @click="bulkSel=[]"
                                    style="font-size:11px;color:#64748b;background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    {{-- Horario a aplicar --}}
                    <div style="padding:16px 20px;">
                        <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 12px;">
                            Horario a asignar
                        </p>
                        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;">
                            @foreach($days as $d => $nombre)
                            <div style="display:flex;flex-direction:column;gap:5px;" x-data="{ bon: false }">
                                <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;">
                                    <span style="font-size:11px;font-weight:700;color:{{ $d==$hoy ? '#818cf8' : '#94a3b8' }};">{{ $nombre }}</span>
                                    <input type="checkbox" name="schedules[{{ $d }}][active]" value="1"
                                           x-model="bon" style="accent-color:#6366f1;width:14px;height:14px;cursor:pointer;">
                                </label>
                                <input type="time" name="schedules[{{ $d }}][start]" value="08:00"
                                       :disabled="!bon"
                                       style="width:100%;font-size:11px;border-radius:6px;padding:5px 6px;font-family:inherit;background:#0a0f1a;border:1px solid #1e293b;color:#e2e8f0;outline:none;"
                                       :style="!bon ? 'opacity:.35;pointer-events:none;' : ''">
                                <input type="time" name="schedules[{{ $d }}][end]" value="17:00"
                                       :disabled="!bon"
                                       style="width:100%;font-size:11px;border-radius:6px;padding:5px 6px;font-family:inherit;background:#0a0f1a;border:1px solid #1e293b;color:#e2e8f0;outline:none;"
                                       :style="!bon ? 'opacity:.35;pointer-events:none;' : ''">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            <div style="padding:14px 20px;border-top:1px solid #1e293b;display:flex;justify-content:flex-end;gap:8px;">
                <button @click="showBulk=false"
                        style="padding:8px 16px;border-radius:9px;border:1px solid #1e293b;background:transparent;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">
                    Cancelar
                </button>
                <button @click="submitBulk()"
                        :disabled="bulkSel.length===0"
                        style="padding:8px 18px;border-radius:9px;border:none;background:#6366f1;color:#fff;font-size:13px;font-weight:600;cursor:pointer;"
                        :style="bulkSel.length===0 ? 'opacity:.4;cursor:not-allowed;' : ''">
                    Aplicar a <span x-text="bulkSel.length"></span> empleado(s)
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function turnosPage() {
    return {
        open: [],
        showBulk: false,
        bulkSel: [],
        toggle(id) {
            const i = this.open.indexOf(id);
            if (i === -1) this.open.push(id);
            else this.open.splice(i, 1);
        },
        close(id) {
            this.open = this.open.filter(x => x !== id);
        },
        submitBulk() {
            if (this.bulkSel.length === 0) return;
            document.getElementById('bulkForm').submit();
        }
    };
}
</script>
@endpush
</x-admin-layout>
