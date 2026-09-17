<x-admin-layout title="Auditoría de accesos">

<div style="max-width:1100px;margin:0 auto;padding:24px;">
    <div style="margin-bottom:20px;">
        <h1 style="font-size:22px;font-weight:800;color:#111827;">Auditoría de accesos</h1>
        <p style="font-size:13px;color:#6b7280;margin-top:4px;">
            Impersonaciones, cambios de perfiles y accesos sensibles. Solo lectura:
            el Control observa, no edita datos del tenant.
        </p>
    </div>

    {{-- Filtro rápido por acción --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        @foreach([null => 'Todo', 'impersonate' => 'Impersonaciones', 'impersonate_end' => 'Salidas', 'permissions_changed' => 'Permisos', 'profile_assigned' => 'Perfiles'] as $accion => $texto)
        <a href="{{ route('admin.audit', array_filter(['accion' => $accion])) }}"
           style="font-size:12px;font-weight:600;padding:5px 12px;border-radius:99px;text-decoration:none;
                  {{ request('accion') === $accion || (!$accion && !request('accion'))
                      ? 'background:#4f46e5;color:#fff;' : 'background:#f3f4f6;color:#374151;' }}">
            {{ $texto }}
        </a>
        @endforeach
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f9fafb;text-align:left;">
                    <th style="padding:10px 14px;font-weight:700;color:#374151;">Cuándo</th>
                    <th style="padding:10px 14px;font-weight:700;color:#374151;">Qué pasó</th>
                    <th style="padding:10px 14px;font-weight:700;color:#374151;">Empresa</th>
                    <th style="padding:10px 14px;font-weight:700;color:#374151;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventos as $e)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:10px 14px;color:#6b7280;white-space:nowrap;">{{ $e->created_at?->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 14px;color:#111827;">{{ $e->label }}</td>
                    <td style="padding:10px 14px;color:#6b7280;">{{ $e->project_id ?? '—' }}</td>
                    <td style="padding:10px 14px;color:#9ca3af;font-family:monospace;">{{ $e->ip ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:24px;text-align:center;color:#9ca3af;">Sin eventos registrados todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-top:16px;">{{ $eventos->links() }}</div>
</div>

</x-admin-layout>
