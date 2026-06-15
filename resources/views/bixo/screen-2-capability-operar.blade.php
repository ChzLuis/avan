<!-- SCREEN 2: CAPABILITY OPERAR - Profundidad Visual -->
<div class="bixo-screen capability-operar" id="screen2">
  <!-- NAVIGATION CONTEXT: Breadcrumb + Back -->
  <div class="context-bar">
    <button class="back-button" onclick="exitCapability()">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>
    <div class="breadcrumb">
      <span class="breadcrumb-item" onclick="exitCapability()">Ferretería GABDE</span>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-item active">Operar</span>
    </div>
  </div>

  <!-- CAPABILITY HEADER -->
  <div class="capability-header">
    <div class="capability-hero">
      <div class="capability-icon-large">⚙️</div>
      <div class="capability-info">
        <h1 class="capability-title">Operar</h1>
        <p class="capability-subtitle">Gestiona todo lo relacionado con tu inventario, compras y proveedores</p>
      </div>
    </div>
  </div>

  <!-- TOOLS LAYER (Not modules, but capabilities nested) -->
  <div class="tools-layer">
    <div class="tools-label">Herramientas</div>

    <div class="tools-grid">
      <!-- Tool 1: Inventario -->
      <button class="tool-card" onclick="enterTool('inventory', event)">
        <div class="tool-badge">🔥</div>
        <div class="tool-content">
          <h3 class="tool-title">Inventario</h3>
          <p class="tool-description">Productos, categorías y stock</p>
          <div class="tool-meta">
            <span class="meta-item">234 productos</span>
            <span class="meta-dot">•</span>
            <span class="meta-item">Stock bajo: 12</span>
          </div>
        </div>
        <div class="tool-arrow">→</div>
      </button>

      <!-- Tool 2: Compras -->
      <button class="tool-card" onclick="enterTool('compras', event)">
        <div class="tool-badge">📥</div>
        <div class="tool-content">
          <h3 class="tool-title">Compras</h3>
          <p class="tool-description">Órdenes de compra y recepción</p>
          <div class="tool-meta">
            <span class="meta-item">5 pendientes</span>
            <span class="meta-dot">•</span>
            <span class="meta-item">$8,450 en curso</span>
          </div>
        </div>
        <div class="tool-arrow">→</div>
      </button>

      <!-- Tool 3: Proveedores -->
      <button class="tool-card" onclick="enterTool('proveedores', event)">
        <div class="tool-badge">🤝</div>
        <div class="tool-content">
          <h3 class="tool-title">Proveedores</h3>
          <p class="tool-description">Base de datos y condiciones</p>
          <div class="tool-meta">
            <span class="meta-item">24 activos</span>
            <span class="meta-dot">•</span>
            <span class="meta-item">15 favoritos</span>
          </div>
        </div>
        <div class="tool-arrow">→</div>
      </button>

      <!-- Tool 4: Movimientos -->
      <button class="tool-card" onclick="enterTool('movimientos', event)">
        <div class="tool-badge">↔️</div>
        <div class="tool-content">
          <h3 class="tool-title">Movimientos</h3>
          <p class="tool-description">Transferencias y ajustes</p>
          <div class="tool-meta">
            <span class="meta-item">Hoy: 3</span>
            <span class="meta-dot">•</span>
            <span class="meta-item">Esta semana: 18</span>
          </div>
        </div>
        <div class="tool-arrow">→</div>
      </button>
    </div>
  </div>

  <!-- ACTIONS: Contextual, not intrusive -->
  <div class="capability-actions">
    <button class="action-btn primary">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nuevo producto
    </button>
    <button class="action-btn secondary">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      Reportes
    </button>
  </div>
</div>

<style>
  .capability-operar {
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fb 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    animation: slideInFromRight var(--duration-long) var(--ease-anticipatory);
  }

  @keyframes slideInFromRight {
    from {
      opacity: 0;
      transform: translateX(100px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* ════════════════════════════════════════════════════════════════
     CONTEXT BAR
     ════════════════════════════════════════════════════════════════ */

  .context-bar {
    display: flex;
    align-items: center;
    gap: var(--space-16);
    padding: var(--space-16) var(--space-32);
    background: var(--color-bg-primary);
    border-bottom: 1px solid var(--color-border-primary);
    position: sticky;
    top: 64px;
    z-index: var(--z-sticky);
  }

  .back-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-md);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .back-button:hover {
    background: var(--color-bg-secondary);
    border-color: var(--color-border-secondary);
    color: var(--color-text-primary);
  }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 600 13px var(--font-family);
  }

  .breadcrumb-item {
    padding: var(--space-4) var(--space-8);
    border-radius: var(--border-radius-sm);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .breadcrumb-item:hover {
    color: var(--color-text-primary);
    background: var(--color-bg-secondary);
  }

  .breadcrumb-item.active {
    color: var(--color-text-primary);
    font-weight: 700;
  }

  .breadcrumb-separator {
    color: var(--color-border-primary);
    user-select: none;
  }

  /* ════════════════════════════════════════════════════════════════
     CAPABILITY HEADER
     ════════════════════════════════════════════════════════════════ */

  .capability-header {
    padding: var(--space-52) var(--space-32);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    border-bottom: 1px solid var(--color-border-tertiary);
  }

  .capability-hero {
    display: flex;
    align-items: center;
    gap: var(--space-32);
    max-width: 900px;
  }

  .capability-icon-large {
    font-size: 64px;
    flex-shrink: 0;
  }

  .capability-info {
    flex: 1;
  }

  .capability-title {
    margin: 0;
    font: var(--font-h1);
    color: var(--color-text-primary);
  }

  .capability-subtitle {
    margin: var(--space-12) 0 0 0;
    font: var(--font-body);
    color: var(--color-text-secondary);
    max-width: 500px;
  }

  /* ════════════════════════════════════════════════════════════════
     TOOLS LAYER
     ════════════════════════════════════════════════════════════════ */

  .tools-layer {
    padding: var(--space-52) var(--space-32);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-32);
  }

  .tools-label {
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-20);
  }

  .tool-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-16);
    padding: var(--space-24);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 12px;
    cursor: pointer;
    transition: all var(--duration-medium) var(--ease-anticipatory);
    text-align: left;
    position: relative;
    overflow: hidden;
  }

  .tool-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--color-accent-primary), transparent);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform var(--duration-quick) var(--ease-smooth);
  }

  .tool-card:hover {
    border-color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.02);
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
  }

  .tool-card:hover::before {
    transform: scaleX(1);
  }

  .tool-badge {
    font-size: 32px;
    display: inline-block;
  }

  .tool-content {
    flex: 1;
  }

  .tool-title {
    margin: 0;
    font: 600 16px var(--font-family);
    color: var(--color-text-primary);
  }

  .tool-description {
    margin: var(--space-8) 0 var(--space-12) 0;
    font: var(--font-caption);
    color: var(--color-text-secondary);
  }

  .tool-meta {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 11px var(--font-family);
    color: var(--color-text-tertiary);
  }

  .meta-item {
    display: inline-block;
  }

  .meta-dot {
    display: inline-block;
    opacity: 0.5;
  }

  .tool-arrow {
    position: absolute;
    top: var(--space-20);
    right: var(--space-20);
    color: var(--color-text-tertiary);
    font-weight: 600;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .tool-card:hover .tool-arrow {
    color: var(--color-accent-primary);
    transform: translateX(4px);
  }

  /* ════════════════════════════════════════════════════════════════
     ACTIONS
     ════════════════════════════════════════════════════════════════ */

  .capability-actions {
    display: flex;
    gap: var(--space-12);
    padding: var(--space-32);
    background: var(--color-bg-primary);
    border-top: 1px solid var(--color-border-primary);
    justify-content: flex-start;
  }

  .action-btn {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    padding: var(--space-12) var(--space-20);
    border: none;
    border-radius: var(--border-radius-md);
    font: 600 13px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .action-btn.primary {
    background: var(--color-accent-primary);
    color: white;
  }

  .action-btn.primary:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  .action-btn.secondary {
    background: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border-primary);
  }

  .action-btn.secondary:hover {
    background: var(--color-bg-tertiary);
    color: var(--color-text-primary);
    border-color: var(--color-border-secondary);
  }

  @media (max-width: 768px) {
    .capability-hero {
      flex-direction: column;
      gap: var(--space-20);
    }

    .capability-header {
      padding: var(--space-32) var(--space-20);
    }

    .tools-layer {
      padding: var(--space-32) var(--space-20);
    }

    .tools-grid {
      grid-template-columns: 1fr;
    }

    .capability-actions {
      flex-direction: column;
      padding: var(--space-20);
    }

    .action-btn {
      width: 100%;
      justify-content: center;
    }
  }

  @media (max-width: 480px) {
    .capability-icon-large {
      font-size: 48px;
    }

    .capability-title {
      font-size: 24px;
    }

    .context-bar {
      padding: var(--space-12) var(--space-16);
      gap: var(--space-8);
    }

    .tool-card {
      padding: var(--space-16);
    }
  }
</style>

<script>
  function enterTool(tool, event) {
    event.preventDefault();
    console.log('Entering tool:', tool);
  }

  function exitCapability() {
    console.log('Exiting capability');
  }
</script>
