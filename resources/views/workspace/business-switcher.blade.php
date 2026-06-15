<div class="business-switcher-overlay" id="businessSwitcherOverlay">
  <div class="business-switcher-modal">
    <!-- Header -->
    <div class="switcher-header">
      <h2 class="switcher-title">Mis Negocios</h2>
      <button class="switcher-close-btn" onclick="document.getElementById('businessSwitcherOverlay').style.display='none'">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Business List -->
    <div class="switcher-content">
      @foreach(Auth::user()->projects as $project)
      <button class="business-switcher-card {{ Auth::user()->active_project_id === $project->id ? 'active' : '' }}"
              onclick="switchBusiness({{ $project->id }})">
        <div class="switcher-card-avatar">{{ $project->name[0] ?? 'B' }}</div>
        <div class="switcher-card-info">
          <h3 class="switcher-card-name">{{ $project->name }}</h3>
          <p class="switcher-card-category">{{ $project->category ?? 'Sin categoría' }}</p>
          <div class="switcher-card-meta">
            <span class="switcher-meta-item">
              <span class="meta-icon">📦</span>
              {{ $project->products_count ?? 0 }} productos
            </span>
            <span class="switcher-meta-item">
              <span class="meta-icon">👥</span>
              {{ $project->clients_count ?? 0 }} clientes
            </span>
          </div>
        </div>
        <div class="switcher-card-status">
          <span class="switcher-status {{ $project->is_active ? 'active' : 'inactive' }}">
            {{ $project->is_active ? 'Activo' : 'Inactivo' }}
          </span>
        </div>
        @if(Auth::user()->active_project_id === $project->id)
        <div class="switcher-card-check">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
        @endif
      </button>
      @endforeach
    </div>

    <!-- Create New -->
    <div class="switcher-footer">
      <button class="switcher-create-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Crear nuevo negocio</span>
      </button>
    </div>
  </div>
</div>

<style>
  .business-switcher-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: var(--z-modal);
    align-items: center;
    justify-content: center;
    animation: fadeIn var(--duration-quick) var(--ease-smooth);
  }

  .business-switcher-overlay.active {
    display: flex;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .business-switcher-modal {
    background: var(--color-bg-primary);
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-depth);
    animation: slideUp var(--duration-medium) var(--ease-anticipatory);
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(40px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Header */
  .switcher-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-32);
    border-bottom: 1px solid var(--color-border-tertiary);
  }

  .switcher-title {
    margin: 0;
    font: var(--font-h2);
    color: var(--color-text-primary);
  }

  .switcher-close-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: var(--border-radius-md);
    color: var(--color-text-tertiary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .switcher-close-btn:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  /* Content */
  .switcher-content {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-20);
    display: flex;
    flex-direction: column;
    gap: var(--space-16);
  }

  .business-switcher-card {
    display: flex;
    align-items: center;
    gap: var(--space-16);
    padding: var(--space-20);
    background: var(--color-bg-secondary);
    border: 2px solid transparent;
    border-radius: var(--border-radius-lg);
    cursor: pointer;
    transition: all var(--duration-medium) var(--ease-smooth);
    position: relative;
    text-align: left;
  }

  .business-switcher-card:hover {
    background: var(--color-bg-tertiary);
    border-color: var(--color-border-primary);
  }

  .business-switcher-card.active {
    border-color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.08);
  }

  .switcher-card-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .switcher-card-info {
    flex: 1;
    min-width: 0;
  }

  .switcher-card-name {
    margin: 0;
    font: 600 16px var(--font-family);
    color: var(--color-text-primary);
  }

  .switcher-card-category {
    margin: var(--space-4) 0 var(--space-8) 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .switcher-card-meta {
    display: flex;
    gap: var(--space-16);
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .switcher-meta-item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
  }

  .meta-icon {
    display: inline-block;
  }

  .switcher-card-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--space-8);
    flex-shrink: 0;
  }

  .switcher-status {
    display: inline-block;
    padding: var(--space-8) var(--space-12);
    font: var(--font-micro);
    border-radius: var(--border-radius-sm);
    background: var(--color-bg-tertiary);
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .switcher-status.active {
    background: rgba(5, 150, 105, 0.1);
    color: var(--color-success);
  }

  .switcher-status.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: var(--color-error);
  }

  .switcher-card-check {
    width: 24px;
    height: 24px;
    background: var(--color-accent-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
  }

  /* Footer */
  .switcher-footer {
    padding: var(--space-20);
    border-top: 1px solid var(--color-border-tertiary);
  }

  .switcher-create-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-8);
    padding: var(--space-16) var(--space-20);
    background: var(--color-accent-primary);
    color: white;
    border: none;
    border-radius: var(--border-radius-md);
    font: 600 14px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .switcher-create-btn:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  /* Scrollbar */
  .switcher-content::-webkit-scrollbar {
    width: 6px;
  }

  .switcher-content::-webkit-scrollbar-track {
    background: transparent;
  }

  .switcher-content::-webkit-scrollbar-thumb {
    background: var(--color-border-primary);
    border-radius: 3px;
  }

  .switcher-content::-webkit-scrollbar-thumb:hover {
    background: var(--color-border-secondary);
  }

  @media (max-width: 480px) {
    .business-switcher-modal {
      width: 95%;
      max-height: 90vh;
    }

    .switcher-header {
      padding: var(--space-20);
    }

    .switcher-content {
      padding: var(--space-16);
    }

    .business-switcher-card {
      flex-direction: column;
      align-items: flex-start;
      padding: var(--space-16);
    }

    .switcher-card-status {
      align-items: flex-start;
      flex-direction: row;
      gap: var(--space-8);
    }

    .switcher-card-meta {
      flex-wrap: wrap;
    }
  }
</style>

<script>
  function openBusinessSwitcher() {
    document.getElementById('businessSwitcherOverlay').classList.add('active');
  }

  function switchBusiness(projectId) {
    // Aquí irá la lógica para cambiar el negocio activo
    console.log('Switching to project:', projectId);
    // window.location.href = `/workspace/${projectId}`;
  }
</script>
