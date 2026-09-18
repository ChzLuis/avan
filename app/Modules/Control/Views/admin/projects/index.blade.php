<x-admin-layout title="Negocios">

<div class="projects-container" x-data="{ nuevo: {{ $errors->any() ? 'true' : 'false' }} }">
    <!-- Header -->
    <div class="projects-header">
        <div class="header-content">
            <h1 class="header-title">Negocios</h1>
            <p class="header-subtitle">{{ $projects->count() }} negocio{{ $projects->count() !== 1 ? 's' : '' }} en tu organización</p>
        </div>
        <button type="button" class="btn-create-project" @click="nuevo = true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Nuevo negocio</span>
        </button>
    </div>

    <!-- Projects Grid -->
    <div class="projects-grid">
        @forelse($projects as $project)
        <div class="project-card" data-project-id="{{ $project->id }}">
            <!-- Card Header -->
            <div class="card-header">
                <div class="card-title-group">
                    <div class="project-avatar">{{ $project->name[0] ?? 'P' }}</div>
                    <div class="project-info">
                        <h3 class="project-name">{{ $project->name }}</h3>
                        <p class="project-slug">/{{ $project->slug }}</p>
                    </div>
                </div>
                <div class="card-status" :class="{ 'status-active': {{ $project->is_active ? 'true' : 'false' }} }">
                    <span class="status-dot"></span>
                    <span class="status-text">{{ $project->is_active ? 'Activo' : 'Suspendido' }}</span>
                </div>
            </div>

            <!-- Card Body: Stats -->
            <div class="card-stats">
                <div class="stat-item">
                    <span class="stat-icon">📦</span>
                    <div class="stat-content">
                        <p class="stat-value">{{ $project->orders_count }}</p>
                        <p class="stat-label">Órdenes</p>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">👥</span>
                    <div class="stat-content">
                        <p class="stat-value">{{ $project->clients_count }}</p>
                        <p class="stat-label">Clientes</p>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">📄</span>
                    <div class="stat-content">
                        <p class="stat-value">{{ $project->invoices_count }}</p>
                        <p class="stat-label">Facturas</p>
                    </div>
                </div>
            </div>

            <!-- Productos contratados (Productos::contratados) -->
            <div style="display:flex;flex-wrap:wrap;gap:6px;padding:0 20px 12px;">
                @forelse($contratados[$project->id] ?? [] as $clave)
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;background:rgba(16,185,129,.15);color:#6ee7b7;">{{ $productos[$clave]['nombre'] }}</span>
                @empty
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;background:rgba(255,255,255,.06);color:#9ca3af;">Sin producto completo</span>
                @endforelse
            </div>

            <!-- Card Footer: Owner -->
            <div class="card-footer">
                <div class="owner-info">
                    <p class="owner-label">Propietario</p>
                    <p class="owner-name">{{ $project->owner->name ?? '—' }}</p>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="card-actions">
                <a href="{{ route('admin.projects.show', $project) }}" class="action-btn action-primary">
                    <span>Ver detalles</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('admin.projects.toggle', $project) }}" class="action-form"
                      data-bx-titulo="{{ $project->is_active ? 'Suspender '.$project->name : 'Activar '.$project->name }}"
                      data-bx-confirmar="{{ $project->is_active
                          ? 'Su panel y su tienda dejarán de estar disponibles hasta que lo actives de nuevo.'
                          : 'Su panel y su tienda vuelven a quedar disponibles.' }}"
                      data-bx-boton="{{ $project->is_active ? 'Suspender' : 'Activar' }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="action-btn action-secondary">
                        {{ $project->is_active ? 'Suspender' : 'Activar' }}
                    </button>
                </form>
                {{-- Soporte entra al Workspace del tenant SIEMPRE por la vía
                     auditada (ADR-003): deja rastro impersonate en AccessEvent. --}}
                <form method="POST" action="{{ url('/bixoadmin/entrar-como/'.$project->id) }}" class="action-form">
                    @csrf
                    <button type="submit" class="action-btn action-secondary" title="Entrar al Workspace de esta empresa dejando rastro de auditoría">
                        Entrar como
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3 class="empty-title">Sin negocios aún</h3>
            <p class="empty-text">Crea tu primer negocio para comenzar</p>
            <button type="button" class="btn-empty-create" @click="nuevo = true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Crear negocio</span>
            </button>
        </div>
        @endforelse
    </div>
</div>

<style>
    .projects-container {
        padding: 32px 24px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Header */
    .projects-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 24px;
    }

    .header-content {
        flex: 1;
    }

    .header-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        margin: 0;
    }

    .header-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 6px 0 0 0;
        font-weight: 500;
    }

    .btn-create-project {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        flex-shrink: 0;
    }

    .btn-create-project:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }

    /* Grid */
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 20px;
    }

    /* Project Card */
    .project-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .project-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-4px);
    }

    /* Card Header */
    .card-header {
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .card-title-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }

    .project-avatar {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .project-info {
        min-width: 0;
    }

    .project-name {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .project-slug {
        font-size: 12px;
        color: #94a3b8;
        margin: 4px 0 0 0;
    }

    .card-status {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f8fafc;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .card-status.status-active {
        background: rgba(16, 185, 129, 0.1);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #94a3b8;
    }

    .card-status.status-active .status-dot {
        background: #10b981;
    }

    .status-text {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-status.status-active .status-text {
        color: #10b981;
    }

    /* Card Stats */
    .card-stats {
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        border-bottom: 1px solid #f1f5f9;
        flex: 1;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        background: #f8fafc;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .stat-item:hover {
        background: #f1f5f9;
    }

    .stat-icon {
        font-size: 18px;
        flex-shrink: 0;
    }

    .stat-content {
        min-width: 0;
    }

    .stat-value {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .stat-label {
        font-size: 11px;
        color: #94a3b8;
        margin: 2px 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Card Footer */
    .card-footer {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .owner-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .owner-label {
        font-size: 11px;
        color: #94a3b8;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .owner-name {
        font-size: 13px;
        color: #1e293b;
        margin: 0;
        font-weight: 500;
    }

    /* Card Actions */
    .card-actions {
        padding: 12px 20px;
        display: flex;
        gap: 8px;
    }

    .action-form {
        flex: 1;
        margin: 0;
    }

    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
    }

    .action-primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
    }

    .action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .action-secondary {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .action-secondary:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        padding: 60px 40px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 8px 0;
    }

    .empty-text {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 24px 0;
    }

    .btn-empty-create {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-empty-create:hover {
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .projects-grid {
            grid-template-columns: 1fr;
        }

        .projects-header {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-create-project {
            width: 100%;
            justify-content: center;
        }

        .card-stats {
            grid-template-columns: 1fr 1fr 1fr;
        }
    }
</style>


    {{-- Alta de negocio con su producto inicial (Productos::DEFINICIONES) --}}
    <div x-show="nuevo" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.6)" @keydown.escape.window="nuevo=false">
        <div class="w-full max-w-lg rounded-2xl border p-6" style="background:#0f172a; border-color:rgba(255,255,255,0.1);" @click.outside="nuevo=false">
            <h3 class="text-base font-semibold text-white mb-1">Nuevo negocio</h3>
            <p class="text-xs text-gray-500 mb-4">Elige con qué producto nace. Después puedes activarle más desde su ficha.</p>
            @if($errors->any())
                <div class="mb-3 p-3 rounded-xl text-xs" style="background:rgba(239,68,68,.12);color:#fca5a5;">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.projects.crear') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Nombre del negocio</label>
                    <input name="name" value="{{ old('name') }}" required maxlength="100" class="w-full px-3 py-2 rounded-xl text-sm text-white border" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.12);">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Contacto</label>
                        <input name="contacto" value="{{ old('contacto') }}" required maxlength="100" class="w-full px-3 py-2 rounded-xl text-sm text-white border" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.12);">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Correo del dueño</label>
                        <input name="email" type="email" value="{{ old('email') }}" required maxlength="150" class="w-full px-3 py-2 rounded-xl text-sm text-white border" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.12);">
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Producto inicial</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($productos as $clave => $producto)
                        <label class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer" style="border-color:rgba(255,255,255,.12);">
                            <input type="radio" name="producto" value="{{ $clave }}" {{ old('producto', 'crm') === $clave ? 'checked' : '' }} class="mt-0.5 accent-indigo-500">
                            <span>
                                <span class="block text-sm text-white">{{ $producto['nombre'] }}</span>
                                <span class="block text-[11px] text-gray-500">{{ $producto['descripcion'] }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <p class="text-[11px] text-gray-500">Si el correo ya tiene usuario, el negocio se le asigna. Si no, se crea y la contraseña se muestra una sola vez.</p>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="nuevo=false" class="px-4 py-2 rounded-xl text-sm text-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-medium text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">Crear negocio</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
