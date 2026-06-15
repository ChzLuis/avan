<!-- NAVIGATION ORBITAL: Sistema de Navegación -->
<div class="orbital-navigation" id="orbitalNav">
  <!-- Back Button (siempre visible) -->
  <button class="orbital-back" id="orbitalBack" style="display:none;" onclick="exitModule()">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    <span class="back-label">Atrás</span>
  </button>

  <!-- Context Path (shows current location) -->
  <div class="orbital-context" id="orbitalContext">
    <div class="context-path">
      <span class="context-segment" onclick="exitModule()">
        {{ Auth::user()->active_project->name ?? 'Inicio' }}
      </span>
      <span class="context-separator">/</span>
      <span class="context-segment active" id="contextModule">—</span>
    </div>
  </div>

  <!-- Module Navigation (appears when inside a module) -->
  <div class="orbital-modules" id="orbitalModules" style="display:none;">
    <!-- Inventory Module -->
    <div class="module-nav-section" id="moduleNavInventory" style="display:none;">
      <div class="module-nav-title">
        <span class="module-icon">📦</span>
        <span>Inventario</span>
      </div>
      <div class="module-nav-links">
        <a href="#" class="module-nav-link active">Productos</a>
        <a href="#" class="module-nav-link">Categorías</a>
        <a href="#" class="module-nav-link">Transferencias</a>
        <a href="#" class="module-nav-link">Reportes</a>
      </div>
    </div>

    <!-- Sales Module -->
    <div class="module-nav-section" id="moduleNavSales" style="display:none;">
      <div class="module-nav-title">
        <span class="module-icon">💰</span>
        <span>Ventas</span>
      </div>
      <div class="module-nav-links">
        <a href="#" class="module-nav-link active">Órdenes</a>
        <a href="#" class="module-nav-link">Cotizaciones</a>
        <a href="#" class="module-nav-link">Clientes</a>
        <a href="#" class="module-nav-link">Reportes</a>
      </div>
    </div>

    <!-- Clients Module -->
    <div class="module-nav-section" id="moduleNavClients" style="display:none;">
      <div class="module-nav-title">
        <span class="module-icon">👥</span>
        <span>Clientes</span>
      </div>
      <div class="module-nav-links">
        <a href="#" class="module-nav-link active">Listado</a>
        <a href="#" class="module-nav-link">Segmentos</a>
        <a href="#" class="module-nav-link">Historial</a>
        <a href="#" class="module-nav-link">Reportes</a>
      </div>
    </div>

    <!-- Reports Module -->
    <div class="module-nav-section" id="moduleNavReports" style="display:none;">
      <div class="module-nav-title">
        <span class="module-icon">📊</span>
        <span>Reportes</span>
      </div>
      <div class="module-nav-links">
        <a href="#" class="module-nav-link active">Dashboard</a>
        <a href="#" class="module-nav-link">Ventas</a>
        <a href="#" class="module-nav-link">Inventario</a>
        <a href="#" class="module-nav-link">Clientes</a>
      </div>
    </div>

    <!-- Settings Module -->
    <div class="module-nav-section" id="moduleNavSettings" style="display:none;">
      <div class="module-nav-title">
        <span class="module-icon">⚙️</span>
        <span>Configuración</span>
      </div>
      <div class="module-nav-links">
        <a href="#" class="module-nav-link active">General</a>
        <a href="#" class="module-nav-link">Usuarios</a>
        <a href="#" class="module-nav-link">Módulos</a>
        <a href="#" class="module-nav-link">API</a>
      </div>
    </div>
  </div>
</div>

<style>
  .orbital-navigation {
    display: flex;
    align-items: center;
    height: 48px;
    padding: 0 var(--space-20);
    background: var(--color-bg-primary);
    border-bottom: 1px solid var(--color-border-primary);
    position: sticky;
    top: 64px;
    z-index: var(--z-sticky);
    gap: var(--space-16);
  }

  /* ════════════════════════════════════════════════════════════════
     BACK BUTTON
     ════════════════════════════════════════════════════════════════ */

  .orbital-back {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    padding: var(--space-8) var(--space-12);
    background: transparent;
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-md);
    color: var(--color-text-secondary);
    font: 600 13px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
    flex-shrink: 0;
  }

  .orbital-back:hover {
    background: var(--color-bg-secondary);
    border-color: var(--color-border-secondary);
    color: var(--color-text-primary);
  }

  .back-label {
    display: none;
  }

  @media (min-width: 640px) {
    .back-label {
      display: inline;
    }
  }

  /* ════════════════════════════════════════════════════════════════
     CONTEXT PATH
     ════════════════════════════════════════════════════════════════ */

  .orbital-context {
    flex: 1;
    min-width: 0;
  }

  .context-path {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 600 13px var(--font-family);
    color: var(--color-text-secondary);
  }

  .context-segment {
    padding: var(--space-4) var(--space-8);
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .context-segment:hover:not(.active) {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  .context-segment.active {
    color: var(--color-text-primary);
    font-weight: 700;
  }

  .context-separator {
    color: var(--color-border-primary);
    user-select: none;
  }

  /* ════════════════════════════════════════════════════════════════
     MODULE NAVIGATION
     ════════════════════════════════════════════════════════════════ */

  .orbital-modules {
    display: none;
    flex: 1;
    align-items: center;
    gap: var(--space-32);
    margin-left: auto;
  }

  .orbital-modules.active {
    display: flex;
  }

  .module-nav-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .module-nav-title {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 700 12px var(--font-family);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
  }

  .module-icon {
    font-size: 16px;
  }

  .module-nav-links {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  .module-nav-link {
    padding: var(--space-8) var(--space-12);
    font: 600 12px var(--font-family);
    color: var(--color-text-secondary);
    text-decoration: none;
    border-radius: var(--border-radius-sm);
    transition: all var(--duration-quick) var(--ease-smooth);
    white-space: nowrap;
  }

  .module-nav-link:hover {
    color: var(--color-text-primary);
    background: var(--color-bg-secondary);
  }

  .module-nav-link.active {
    color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.1);
    border-left: 2px solid var(--color-accent-primary);
    padding-left: 10px;
  }

  /* ════════════════════════════════════════════════════════════════
     RESPONSIVE
     ════════════════════════════════════════════════════════════════ */

  @media (max-width: 768px) {
    .orbital-modules {
      display: none !important;
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      flex-direction: column;
      padding: var(--space-16);
      background: var(--color-bg-primary);
      border-bottom: 1px solid var(--color-border-primary);
      box-shadow: var(--shadow-light);
      z-index: var(--z-dropdown);
      margin-left: 0;
      gap: var(--space-16);
    }

    .orbital-modules.active {
      display: flex;
    }

    .module-nav-section {
      flex-direction: row;
      align-items: center;
      gap: var(--space-12);
    }

    .module-nav-links {
      flex-direction: row;
      gap: var(--space-8);
    }
  }

  @media (max-width: 480px) {
    .orbital-navigation {
      padding: 0 var(--space-12);
      height: 44px;
    }

    .orbital-back {
      padding: var(--space-8);
    }

    .context-path {
      font-size: 12px;
    }

    .module-nav-title {
      font-size: 11px;
    }

    .module-nav-link {
      font-size: 11px;
      padding: var(--space-6) var(--space-10);
    }
  }
</style>

<script>
  function enterModule(moduleName, moduleId) {
    // Mostrar back button
    document.getElementById('orbitalBack').style.display = 'flex';

    // Actualizar context path
    document.getElementById('contextModule').textContent = moduleName;

    // Mostrar módulo de navegación
    document.getElementById('orbitalModules').classList.add('active');

    // Mostrar solo el nav del módulo actual
    hideAllModuleNavs();
    document.getElementById('moduleNav' + moduleId).style.display = 'block';

    // Animar entrada
    document.body.classList.add('module-open');
    animateModuleEnter();
  }

  function exitModule() {
    // Ocultar back button
    document.getElementById('orbitalBack').style.display = 'none';

    // Limpiar context path
    document.getElementById('contextModule').textContent = '—';

    // Ocultar módulo de navegación
    document.getElementById('orbitalModules').classList.remove('active');

    // Animar salida
    document.body.classList.remove('module-open');
  }

  function hideAllModuleNavs() {
    document.querySelectorAll('.module-nav-section').forEach(section => {
      section.style.display = 'none';
    });
  }

  function animateModuleEnter() {
    const mainContent = document.querySelector('.module-content');
    if (mainContent) {
      mainContent.classList.add('bixo-enter');
    }
  }

  // Keyboard shortcut: ESC to exit module
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('orbitalBack').style.display !== 'none') {
      exitModule();
    }
  });
</script>
