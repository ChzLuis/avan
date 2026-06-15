<!-- SCREEN 1: BUSINESS CENTER - El Negocio es el Centro de Gravedad -->
<div class="bixo-screen business-center" id="screen1">
  <!-- Background Gradient (Subtle, no competing) -->
  <div class="screen-bg"></div>

  <!-- CONTENT: Centered, breathing space -->
  <div class="screen-container">
    <!-- Business Card: Central, commanding presence -->
    <div class="business-card-center">
      <div class="business-header-center">
        <div class="business-avatar-large">F</div>
        <div class="business-meta">
          <h1 class="business-name-center">Ferretería GABDE</h1>
          <p class="business-status-center">
            <span class="dot active"></span>
            Activo • Última venta hace 23 min
          </p>
        </div>
      </div>

      <!-- Quick Metrics (Not KPI dashboard, just context) -->
      <div class="business-pulse">
        <div class="pulse-item">
          <span class="pulse-icon">💰</span>
          <span class="pulse-value">$2,450</span>
          <span class="pulse-label">Hoy</span>
        </div>
        <div class="pulse-separator"></div>
        <div class="pulse-item">
          <span class="pulse-icon">📦</span>
          <span class="pulse-value">12</span>
          <span class="pulse-label">Órdenes</span>
        </div>
        <div class="pulse-separator"></div>
        <div class="pulse-item">
          <span class="pulse-icon">👥</span>
          <span class="pulse-value">3</span>
          <span class="pulse-label">Nuevos</span>
        </div>
      </div>
    </div>

    <!-- Capabilities Layer (Not modules, not buttons) -->
    <div class="capabilities-layer">
      <div class="capabilities-intro">
        <p class="intro-text">¿Qué necesitas hacer hoy?</p>
      </div>

      <div class="capabilities-grid">
        <!-- Capability 1: Operar -->
        <button class="capability-card" onclick="enterCapability('operar', event)">
          <div class="capability-icon">⚙️</div>
          <div class="capability-content">
            <h3 class="capability-title">Operar</h3>
            <p class="capability-description">Gestiona inventario, compras y proveedores</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>

        <!-- Capability 2: Vender -->
        <button class="capability-card" onclick="enterCapability('vender', event)">
          <div class="capability-icon">💰</div>
          <div class="capability-content">
            <h3 class="capability-title">Vender</h3>
            <p class="capability-description">Órdenes, cotizaciones y clientes</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>

        <!-- Capability 3: Clientes -->
        <button class="capability-card" onclick="enterCapability('clientes', event)">
          <div class="capability-icon">👥</div>
          <div class="capability-content">
            <h3 class="capability-title">Clientes</h3>
            <p class="capability-description">CRM, historial y comunicación</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>

        <!-- Capability 4: Finanzas -->
        <button class="capability-card" onclick="enterCapability('finanzas', event)">
          <div class="capability-icon">📊</div>
          <div class="capability-content">
            <h3 class="capability-title">Finanzas</h3>
            <p class="capability-description">Facturación, reportes y análisis</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>

        <!-- Capability 5: Crecer -->
        <button class="capability-card" onclick="enterCapability('crecer', event)">
          <div class="capability-icon">📈</div>
          <div class="capability-content">
            <h3 class="capability-title">Crecer</h3>
            <p class="capability-description">Marketing, automación e integraciones</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>

        <!-- Capability 6: Configuración -->
        <button class="capability-card" onclick="enterCapability('config', event)">
          <div class="capability-icon">⚡</div>
          <div class="capability-content">
            <h3 class="capability-title">Configuración</h3>
            <p class="capability-description">Sistema, usuarios y módulos</p>
          </div>
          <div class="capability-arrow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>
      </div>
    </div>

    <!-- Bottom: Business Switcher & Settings -->
    <div class="screen-footer">
      <button class="footer-btn-secondary" onclick="openBusinessSwitcher()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"/>
        </svg>
        Cambiar negocio
      </button>
      <button class="footer-btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        </svg>
        Configuración
      </button>
    </div>
  </div>
</div>

<style>
  .bixo-screen {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
  }

  .business-center {
    background: linear-gradient(135deg, #f8f9fb 0%, #f1f3f7 100%);
  }

  .screen-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
  }

  .screen-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--space-52);
    gap: var(--space-52);
    z-index: 1;
    position: relative;
    max-width: 1000px;
    margin: 0 auto;
  }

  /* ════════════════════════════════════════════════════════════════
     BUSINESS CARD: Central Presence
     ════════════════════════════════════════════════════════════════ */

  .business-card-center {
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 16px;
    padding: var(--space-32);
    width: 100%;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: all var(--duration-medium) var(--ease-anticipatory);
  }

  .business-card-center:hover {
    border-color: var(--color-border-secondary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  }

  .business-header-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-16);
    margin-bottom: var(--space-32);
  }

  .business-avatar-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
    font-weight: 700;
  }

  .business-name-center {
    margin: 0;
    font: var(--font-h1);
    color: var(--color-text-primary);
  }

  .business-status-center {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-8);
  }

  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    display: inline-block;
  }

  .dot.active {
    background: var(--color-success);
  }

  /* ════════════════════════════════════════════════════════════════
     PULSE: Live Context
     ════════════════════════════════════════════════════════════════ */

  .business-pulse {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-20);
    padding: var(--space-20);
    background: var(--color-bg-secondary);
    border-radius: 12px;
  }

  .pulse-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-8);
  }

  .pulse-icon {
    font-size: 20px;
  }

  .pulse-value {
    font: 700 18px var(--font-family);
    color: var(--color-text-primary);
  }

  .pulse-label {
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .pulse-separator {
    width: 1px;
    height: 40px;
    background: var(--color-border-tertiary);
  }

  /* ════════════════════════════════════════════════════════════════
     CAPABILITIES LAYER
     ════════════════════════════════════════════════════════════════ */

  .capabilities-layer {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: var(--space-32);
  }

  .capabilities-intro {
    text-align: center;
  }

  .intro-text {
    margin: 0;
    font: var(--font-h2);
    color: var(--color-text-secondary);
  }

  .capabilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-20);
  }

  .capability-card {
    display: flex;
    align-items: center;
    gap: var(--space-16);
    padding: var(--space-20);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 12px;
    cursor: pointer;
    transition: all var(--duration-medium) var(--ease-anticipatory);
    text-align: left;
    position: relative;
    overflow: hidden;
  }

  .capability-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-accent-primary);
    transform: scaleY(0);
    transform-origin: top;
    transition: transform var(--duration-quick) var(--ease-smooth);
  }

  .capability-card:hover {
    border-color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.03);
    transform: translateX(8px);
    box-shadow: var(--shadow-light);
  }

  .capability-card:hover::before {
    transform: scaleY(1);
  }

  .capability-icon {
    font-size: 32px;
    flex-shrink: 0;
  }

  .capability-content {
    flex: 1;
    min-width: 0;
  }

  .capability-title {
    margin: 0;
    font: 600 16px var(--font-family);
    color: var(--color-text-primary);
  }

  .capability-description {
    margin: var(--space-8) 0 0 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .capability-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--color-bg-secondary);
    border-radius: 6px;
    color: var(--color-text-tertiary);
    transition: all var(--duration-quick) var(--ease-smooth);
    flex-shrink: 0;
  }

  .capability-card:hover .capability-arrow {
    background: var(--color-accent-primary);
    color: white;
    transform: translateX(4px);
  }

  /* ════════════════════════════════════════════════════════════════
     FOOTER
     ════════════════════════════════════════════════════════════════ */

  .screen-footer {
    display: flex;
    gap: var(--space-12);
    padding: var(--space-32);
    border-top: 1px solid var(--color-border-primary);
    justify-content: center;
    background: var(--color-bg-primary);
  }

  .footer-btn-secondary {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    padding: var(--space-12) var(--space-20);
    background: var(--color-bg-secondary);
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-md);
    color: var(--color-text-secondary);
    font: 600 13px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .footer-btn-secondary:hover {
    background: var(--color-bg-tertiary);
    border-color: var(--color-border-secondary);
    color: var(--color-text-primary);
  }

  @media (max-width: 768px) {
    .screen-container {
      padding: var(--space-32) var(--space-20);
      gap: var(--space-32);
    }

    .capabilities-grid {
      grid-template-columns: 1fr;
    }

    .business-card-center {
      padding: var(--space-20);
    }

    .screen-footer {
      flex-direction: column;
      padding: var(--space-20);
    }

    .footer-btn-secondary {
      width: 100%;
      justify-content: center;
    }
  }

  @media (max-width: 480px) {
    .screen-container {
      padding: var(--space-20);
      gap: var(--space-20);
    }

    .business-avatar-large {
      width: 64px;
      height: 64px;
      font-size: 28px;
    }

    .business-name-center {
      font-size: 24px;
    }

    .capability-card {
      padding: var(--space-16);
      gap: var(--space-12);
    }

    .capability-icon {
      font-size: 24px;
    }
  }
</style>

<script>
  function enterCapability(capability, event) {
    event.preventDefault();
    // Transición a siguiente pantalla
    console.log('Entering capability:', capability);
    // Aquí irá la lógica de navegación
  }

  function openBusinessSwitcher() {
    console.log('Opening business switcher');
    // Aquí irá la lógica de modal
  }
</script>
