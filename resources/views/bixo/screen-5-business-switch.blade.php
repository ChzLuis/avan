<!-- SCREEN 5: BUSINESS SWITCH - Conscious Context Change -->
<div class="bixo-screen business-switch" id="screen5">
  <!-- Background Overlay -->
  <div class="switch-overlay"></div>

  <!-- Switch Modal -->
  <div class="switch-modal">
    <!-- Header -->
    <div class="switch-header">
      <h2 class="switch-title">Cambiar Negocio</h2>
      <button class="switch-close" onclick="closeSwitch()">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Search (Optional but useful) -->
    <div class="switch-search">
      <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input type="text" placeholder="Buscar negocio..." class="switch-input" />
    </div>

    <!-- Business List -->
    <div class="switch-businesses">
      <!-- Current Business (Active) -->
      <div class="business-item active">
        <div class="item-avatar">F</div>
        <div class="item-content">
          <h3 class="item-name">Ferretería GABDE</h3>
          <p class="item-meta">Ferretería • Activo desde 8 meses</p>
          <div class="item-stats">
            <span>234 productos</span>
            <span>•</span>
            <span>$2,450 hoy</span>
          </div>
        </div>
        <div class="item-badge">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
      </div>

      <!-- Other Businesses -->
      <button class="business-item" onclick="switchToNegocio('licoreria')">
        <div class="item-avatar" style="background: linear-gradient(135deg, #ec4899 0%, #f97316 100%);">L</div>
        <div class="item-content">
          <h3 class="item-name">Licorería Central</h3>
          <p class="item-meta">Licores • Activo desde 5 meses</p>
          <div class="item-stats">
            <span>156 productos</span>
            <span>•</span>
            <span>Última venta hace 3h</span>
          </div>
        </div>
        <svg class="item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>

      <button class="business-item" onclick="switchToNegocio('bodega')">
        <div class="item-avatar" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">B</div>
        <div class="item-content">
          <h3 class="item-name">Bodega Express</h3>
          <p class="item-meta">Abarrotes • Activo desde 2 meses</p>
          <div class="item-stats">
            <span>512 productos</span>
            <span>•</span>
            <span>$1,200 hoy</span>
          </div>
        </div>
        <svg class="item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>

      <button class="business-item" onclick="switchToNegocio('taller')">
        <div class="item-avatar" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">T</div>
        <div class="item-content">
          <h3 class="item-name">Taller Mecánico "Juan"</h3>
          <p class="item-meta">Servicios • Activo desde 1 año</p>
          <div class="item-stats">
            <span>78 clientes</span>
            <span>•</span>
            <span>2 citas hoy</span>
          </div>
        </div>
        <svg class="item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>

      <button class="business-item suspended" onclick="switchToNegocio('boutique')">
        <div class="item-avatar" style="background: linear-gradient(135deg, #a855f7 0%, #d946ef 100%);">B</div>
        <div class="item-content">
          <h3 class="item-name">Boutique Luisa</h3>
          <p class="item-meta">Ropa • Suspendido (no pagado)</p>
          <div class="item-stats">
            <span>234 productos</span>
            <span>•</span>
            <span>Suspendido</span>
          </div>
        </div>
        <div class="item-status">Suspendido</div>
      </button>
    </div>

    <!-- Footer: Create New -->
    <div class="switch-footer">
      <button class="btn-create-new">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Crear nuevo negocio
      </button>
    </div>
  </div>
</div>

<style>
  .business-switch {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: var(--z-modal);
    padding: var(--space-20);
    animation: fadeIn var(--duration-quick) var(--ease-smooth);
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .switch-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn var(--duration-quick) var(--ease-smooth);
  }

  /* ════════════════════════════════════════════════════════════════
     MODAL
     ════════════════════════════════════════════════════════════════ */

  .switch-modal {
    position: relative;
    z-index: 1;
    background: var(--color-bg-primary);
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 85vh;
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
  .switch-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-32);
    border-bottom: 1px solid var(--color-border-primary);
  }

  .switch-title {
    margin: 0;
    font: var(--font-h2);
    color: var(--color-text-primary);
  }

  .switch-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: transparent;
    border: none;
    border-radius: var(--border-radius-md);
    color: var(--color-text-tertiary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .switch-close:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  /* Search -->
  .switch-search {
    display: flex;
    align-items: center;
    padding: var(--space-20);
    background: var(--color-bg-secondary);
    border-bottom: 1px solid var(--color-border-primary);
    position: relative;
    gap: var(--space-12);
  }

  .search-icon {
    width: 18px;
    height: 18px;
    color: var(--color-text-tertiary);
    flex-shrink: 0;
  }

  .switch-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font: var(--font-body);
    color: var(--color-text-primary);
  }

  .switch-input::placeholder {
    color: var(--color-text-quaternary);
  }

  /* Business List -->
  .switch-businesses {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-16);
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .business-item {
    display: flex;
    align-items: center;
    gap: var(--space-16);
    padding: var(--space-20);
    background: var(--color-bg-secondary);
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all var(--duration-medium) var(--ease-smooth);
    text-align: left;
    position: relative;
  }

  .business-item:hover:not(.suspended) {
    background: var(--color-bg-tertiary);
    border-color: var(--color-border-secondary);
    transform: translateX(4px);
  }

  .business-item.active {
    border-color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.08);
  }

  .business-item.suspended {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .item-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font: 700 24px var(--font-family);
    flex-shrink: 0;
  }

  .item-content {
    flex: 1;
    min-width: 0;
  }

  .item-name {
    margin: 0;
    font: 600 16px var(--font-family);
    color: var(--color-text-primary);
  }

  .item-meta {
    margin: var(--space-4) 0 var(--space-8) 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .item-stats {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 11px var(--font-family);
    color: var(--color-text-tertiary);
  }

  .item-badge {
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

  .item-arrow {
    width: 20px;
    height: 20px;
    color: var(--color-text-tertiary);
    transition: all var(--duration-quick) var(--ease-smooth);
    flex-shrink: 0;
  }

  .business-item:hover:not(.suspended) .item-arrow {
    color: var(--color-accent-primary);
    transform: translateX(4px);
  }

  .item-status {
    padding: var(--space-6) var(--space-10);
    background: rgba(239, 68, 68, 0.1);
    color: var(--color-error);
    border-radius: var(--border-radius-sm);
    font: var(--font-micro);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
  }

  /* Scrollbar -->
  .switch-businesses::-webkit-scrollbar {
    width: 6px;
  }

  .switch-businesses::-webkit-scrollbar-track {
    background: transparent;
  }

  .switch-businesses::-webkit-scrollbar-thumb {
    background: var(--color-border-primary);
    border-radius: 3px;
  }

  .switch-businesses::-webkit-scrollbar-thumb:hover {
    background: var(--color-border-secondary);
  }

  /* Footer -->
  .switch-footer {
    padding: var(--space-20);
    border-top: 1px solid var(--color-border-primary);
  }

  .btn-create-new {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-8);
    padding: var(--space-14) var(--space-20);
    background: var(--color-accent-primary);
    color: white;
    border: none;
    border-radius: var(--border-radius-md);
    font: 600 14px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .btn-create-new:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  @media (max-width: 480px) {
    .switch-modal {
      max-width: 100%;
      border-radius: 12px;
      max-height: 90vh;
    }

    .switch-header {
      padding: var(--space-20);
    }

    .switch-search {
      padding: var(--space-16);
    }

    .switch-businesses {
      padding: var(--space-12);
      gap: var(--space-6);
    }

    .business-item {
      padding: var(--space-16);
      gap: var(--space-12);
    }

    .item-avatar {
      width: 48px;
      height: 48px;
      font-size: 20px;
    }

    .item-meta {
      font-size: 11px;
    }

    .item-stats {
      font-size: 10px;
    }
  }
</style>

<script>
  function closeSwitch() {
    console.log('Closing business switch');
  }

  function switchToNegocio(negocioId) {
    console.log('Switching to:', negocioId);
    // Aquí irá la transición al nuevo negocio
    // Con fade out y fade in + repaint de datos
  }
</script>
