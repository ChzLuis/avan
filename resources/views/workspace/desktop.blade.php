<div class="bixo-workspace">
  <!-- BUSINESS HEADER -->
  <div class="business-header">
    <div class="business-identity">
      <div class="business-avatar">{{ Auth::user()->active_project->name[0] ?? 'B' }}</div>
      <div class="business-info">
        <h1 class="business-name">{{ Auth::user()->active_project->name ?? 'Mi Negocio' }}</h1>
        <p class="business-status">
          <span class="status-dot {{ Auth::user()->active_project->is_active ? 'active' : 'inactive' }}"></span>
          {{ Auth::user()->active_project->is_active ? 'Activo' : 'Suspendido' }} • Última actividad hace 2h
        </p>
      </div>
    </div>
    <button class="business-switch-btn">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"/>
      </svg>
      <span>Cambiar negocio</span>
    </button>
  </div>

  <!-- TODAY SNAPSHOT -->
  <div class="today-section">
    <h2 class="section-title">Hoy</h2>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon">💰</div>
        <div class="kpi-content">
          <p class="kpi-value">$2,450</p>
          <p class="kpi-label">Ventas</p>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">📦</div>
        <div class="kpi-content">
          <p class="kpi-value">12</p>
          <p class="kpi-label">Órdenes</p>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">👥</div>
        <div class="kpi-content">
          <p class="kpi-value">3</p>
          <p class="kpi-label">Clientes nuevos</p>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">✅</div>
        <div class="kpi-content">
          <p class="kpi-value">OK</p>
          <p class="kpi-label">Inventario</p>
        </div>
      </div>
    </div>
  </div>

  <!-- QUICK ACCESS -->
  <div class="quick-access-section">
    <h2 class="section-title">Acceso rápido</h2>
    <div class="quick-access-grid">
      <button class="quick-access-btn" data-module="inventory">
        <div class="quick-icon">📦</div>
        <span class="quick-label">Inventario</span>
        <span class="quick-count">234 productos</span>
      </button>
      <button class="quick-access-btn" data-module="sales">
        <div class="quick-icon">💰</div>
        <span class="quick-label">Ventas</span>
        <span class="quick-count">12 hoy</span>
      </button>
      <button class="quick-access-btn" data-module="clients">
        <div class="quick-icon">👥</div>
        <span class="quick-label">Clientes</span>
        <span class="quick-count">245 activos</span>
      </button>
      <button class="quick-access-btn" data-module="reports">
        <div class="quick-icon">📊</div>
        <span class="quick-label">Reportes</span>
        <span class="quick-count">Mensual</span>
      </button>
      <button class="quick-access-btn" data-module="settings">
        <div class="quick-icon">⚙️</div>
        <span class="quick-label">Configuración</span>
        <span class="quick-count">Sistema</span>
      </button>
    </div>
  </div>

  <!-- RECENT ACTIVITY -->
  <div class="activity-section">
    <h2 class="section-title">Actividad reciente</h2>
    <div class="activity-list">
      <div class="activity-item">
        <div class="activity-icon">📝</div>
        <div class="activity-content">
          <p class="activity-title">Orden #1234 creada</p>
          <p class="activity-time">Hace 15 minutos</p>
        </div>
      </div>
      <div class="activity-item">
        <div class="activity-icon">💳</div>
        <div class="activity-content">
          <p class="activity-title">Pago recibido - $580</p>
          <p class="activity-time">Hace 45 minutos</p>
        </div>
      </div>
      <div class="activity-item">
        <div class="activity-icon">⚠️</div>
        <div class="activity-content">
          <p class="activity-title">Stock bajo: Tubo PVC 3"</p>
          <p class="activity-time">Hace 2 horas</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .bixo-workspace {
    display: flex;
    flex-direction: column;
    gap: var(--space-52);
    padding: var(--space-32);
    max-width: 1200px;
    margin: 0 auto;
  }

  /* ════════════════════════════════════════════════════════════════
     BUSINESS HEADER: El Negocio es el Protagonista
     ════════════════════════════════════════════════════════════════ */

  .business-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-32);
    background: linear-gradient(135deg, var(--color-bg-secondary) 0%, var(--color-bg-tertiary) 100%);
    border: 1px solid var(--color-border-primary);
    border-radius: 12px;
    gap: var(--space-20);
  }

  .business-identity {
    display: flex;
    align-items: center;
    gap: var(--space-20);
    flex: 1;
  }

  .business-avatar {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .business-info {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .business-name {
    margin: 0;
    font: var(--font-h1);
    color: var(--color-text-primary);
  }

  .business-status {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    display: flex;
    align-items: center;
    gap: var(--space-8);
  }

  .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    display: inline-block;
  }

  .status-dot.active {
    background: var(--color-success);
  }

  .status-dot.inactive {
    background: var(--color-error);
  }

  .business-switch-btn {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    padding: var(--space-12) var(--space-20);
    background: var(--color-accent-primary);
    color: white;
    border: none;
    border-radius: var(--border-radius-md);
    font: 600 13px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
    flex-shrink: 0;
  }

  .business-switch-btn:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  /* ════════════════════════════════════════════════════════════════
     TODAY SECTION: KPI Rápida
     ════════════════════════════════════════════════════════════════ */

  .today-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-20);
  }

  .section-title {
    margin: 0;
    font: var(--font-h2);
    color: var(--color-text-primary);
  }

  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-20);
  }

  .kpi-card {
    display: flex;
    align-items: center;
    gap: var(--space-20);
    padding: var(--space-20);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-lg);
    transition: all var(--duration-medium) var(--ease-anticipatory);
  }

  .kpi-card:hover {
    border-color: var(--color-border-secondary);
    box-shadow: var(--shadow-light);
    transform: translateY(-2px);
  }

  .kpi-icon {
    font-size: 32px;
    flex-shrink: 0;
  }

  .kpi-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .kpi-value {
    margin: 0;
    font: 700 20px var(--font-family);
    color: var(--color-text-primary);
  }

  .kpi-label {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* ════════════════════════════════════════════════════════════════
     QUICK ACCESS: Entrada a Módulos
     ════════════════════════════════════════════════════════════════ */

  .quick-access-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-20);
  }

  .quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: var(--space-16);
  }

  .quick-access-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-12);
    padding: var(--space-20);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-lg);
    cursor: pointer;
    transition: all var(--duration-medium) var(--ease-anticipatory);
    text-decoration: none;
  }

  .quick-access-btn:hover {
    border-color: var(--color-accent-primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    transform: translateY(-4px);
  }

  .quick-icon {
    font-size: 36px;
  }

  .quick-label {
    display: block;
    margin: 0;
    font: 600 14px var(--font-family);
    color: var(--color-text-primary);
  }

  .quick-count {
    display: block;
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  /* ════════════════════════════════════════════════════════════════
     ACTIVITY SECTION: Lo Reciente
     ════════════════════════════════════════════════════════════════ */

  .activity-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-20);
  }

  .activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-12);
  }

  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-16);
    padding: var(--space-16);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-tertiary);
    border-radius: var(--border-radius-md);
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .activity-item:hover {
    background: var(--color-bg-secondary);
    border-color: var(--color-border-primary);
  }

  .activity-icon {
    font-size: 24px;
    flex-shrink: 0;
  }

  .activity-content {
    flex: 1;
    min-width: 0;
  }

  .activity-title {
    margin: 0;
    font: 600 14px var(--font-family);
    color: var(--color-text-primary);
  }

  .activity-time {
    margin: var(--space-4) 0 0 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  /* ════════════════════════════════════════════════════════════════
     RESPONSIVE
     ════════════════════════════════════════════════════════════════ */

  @media (max-width: 768px) {
    .bixo-workspace {
      gap: var(--space-32);
      padding: var(--space-20);
    }

    .business-header {
      flex-direction: column;
      align-items: flex-start;
      padding: var(--space-20);
    }

    .business-switch-btn {
      width: 100%;
      justify-content: center;
    }

    .quick-access-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 480px) {
    .kpi-grid {
      grid-template-columns: 1fr;
    }

    .quick-access-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
