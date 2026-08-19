<x-admin-layout title="Negocios">

<div class="projects-container">
    <!-- Header -->
    <div class="projects-header">
        <div class="header-content">
            <h1 class="header-title">Negocios</h1>
            <p class="header-subtitle">{{ $projects->count() }} negocio{{ $projects->count() !== 1 ? 's' : '' }} en tu organización</p>
        </div>
        <button class="btn-create-project">
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
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3 class="empty-title">Sin negocios aún</h3>
            <p class="empty-text">Crea tu primer negocio para comenzar</p>
            <button class="btn-empty-create">
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

</x-admin-layout>
