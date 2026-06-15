<!-- SCREEN 1 v2: NEGOCIO COMO ESPACIO FÍSICO -->
<div class="bixo-negocio-centro">
  <!-- NO HAY NAVBAR, NO HAY SIDEBAR -->
  <!-- EL ESPACIO ES COMPLETAMENTE DEL NEGOCIO -->

  <div class="negocio-viewport">
    <!-- NEGOCIO: PRESENCIA FÍSICA, NO NAVEGABLE -->
    <div class="negocio-presencia">
      <div class="negocio-header-hero">
        <div class="negocio-nombre-grande">Ferretería GABDE</div>
        <div class="negocio-estado-vivo">
          <span class="estado-indicador"></span>
          Activo • Última venta hace 23 minutos
        </div>
      </div>

      <!-- PULSO DEL NEGOCIO: No es KPI, es el corazón del negocio -->
      <div class="negocio-pulse-container">
        <div class="pulse-beat">
          <span class="pulse-number">$2,450</span>
          <span class="pulse-label">Hoy</span>
        </div>
        <div class="pulse-beat">
          <span class="pulse-number">12</span>
          <span class="pulse-label">Órdenes</span>
        </div>
        <div class="pulse-beat">
          <span class="pulse-number">3</span>
          <span class="pulse-label">Clientes nuevos</span>
        </div>
      </div>
    </div>

    <!-- SEPARADOR: TRANSICIÓN VISUAL CLARA -->
    <div class="negocio-horizon"></div>

    <!-- PREGUNTA CENTRAL: Qué necesitas hacer -->
    <div class="negocio-question">
      ¿Qué necesitas hacer con GABDE ahora?
    </div>

    <!-- CAPACIDADES: NO COMO CARDS, COMO DESTINOS REALES -->
    <div class="capacidades-destinos">
      <!-- OPERAR -->
      <div class="capacidad-destino" onclick="enterCapacity('operar')">
        <div class="destino-numero">1</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Operar</h2>
          <p class="destino-subtitulo">Gestiona tu inventario, compras y proveedores</p>
        </div>
        <div class="destino-visual">⚙️</div>
      </div>

      <!-- VENDER -->
      <div class="capacidad-destino" onclick="enterCapacity('vender')">
        <div class="destino-numero">2</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Vender</h2>
          <p class="destino-subtitulo">Órdenes, cotizaciones y canales de venta</p>
        </div>
        <div class="destino-visual">💰</div>
      </div>

      <!-- CLIENTES -->
      <div class="capacidad-destino" onclick="enterCapacity('clientes')">
        <div class="destino-numero">3</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Clientes</h2>
          <p class="destino-subtitulo">Base de datos, historial y comunicación</p>
        </div>
        <div class="destino-visual">👥</div>
      </div>

      <!-- CRECER -->
      <div class="capacidad-destino" onclick="enterCapacity('crecer')">
        <div class="destino-numero">4</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Crecer</h2>
          <p class="destino-subtitulo">Marketing, automación e integraciones</p>
        </div>
        <div class="destino-visual">📈</div>
      </div>

      <!-- FINANZAS -->
      <div class="capacidad-destino" onclick="enterCapacity('finanzas')">
        <div class="destino-numero">5</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Finanzas</h2>
          <p class="destino-subtitulo">Facturación, reportes y análisis</p>
        </div>
        <div class="destino-visual">📊</div>
      </div>

      <!-- CONFIGURAR -->
      <div class="capacidad-destino" onclick="enterCapacity('config')">
        <div class="destino-numero">6</div>
        <div class="destino-content">
          <h2 class="destino-titulo">Configurar</h2>
          <p class="destino-subtitulo">Sistema, usuarios y módulos</p>
        </div>
        <div class="destino-visual">⚡</div>
      </div>
    </div>

    <!-- FOOTER MÍNIMO: Solo cambio de negocio -->
    <div class="negocio-footer">
      <button class="btn-cambiar-negocio" onclick="openBusinessSwitcher()">
        Cambiar negocio
      </button>
    </div>
  </div>
</div>

<style>
  .bixo-negocio-centro {
    width: 100%;
    height: 100vh;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    overflow: hidden;
  }

  .negocio-viewport {
    width: 100%;
    max-width: 1000px;
    padding: 80px 40px;
    display: flex;
    flex-direction: column;
    gap: 60px;
    height: 100%;
    overflow-y: auto;
  }

  /* ════════════════════════════════════════════════════════════════
     NEGOCIO: PRESENCIA FÍSICA
     ════════════════════════════════════════════════════════════════ */

  .negocio-presencia {
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 40px;
    padding: 40px 0;
  }

  .negocio-header-hero {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .negocio-nombre-grande {
    font-size: 56px;
    font-weight: 700;
    color: #1a1d23;
    line-height: 1.1;
    letter-spacing: -0.02em;
    margin: 0;
  }

  .negocio-estado-vivo {
    font-size: 16px;
    color: #565d73;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
  }

  .estado-indicador {
    width: 10px;
    height: 10px;
    background: #059669;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.5;
    }
  }

  /* PULSE: Latido del negocio -->
  .negocio-pulse-container {
    display: flex;
    justify-content: center;
    gap: 60px;
    padding: 40px;
    background: rgba(79, 70, 229, 0.05);
    border-radius: 12px;
  }

  .pulse-beat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
  }

  .pulse-number {
    font-size: 32px;
    font-weight: 700;
    color: #1a1d23;
  }

  .pulse-label {
    font-size: 12px;
    color: #8a90a3;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* HORIZON: Separación clara de contextos -->
  .negocio-horizon {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e0e3eb, transparent);
    margin: 40px 0;
  }

  /* PREGUNTA: Centro del pensamiento -->
  .negocio-question {
    font-size: 20px;
    font-weight: 600;
    color: #565d73;
    text-align: center;
  }

  /* ════════════════════════════════════════════════════════════════
     CAPACIDADES: DESTINOS, NO CARDS
     ════════════════════════════════════════════════════════════════ */

  .capacidades-destinos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
  }

  .capacidad-destino {
    display: grid;
    grid-template-columns: 60px 1fr 80px;
    gap: 24px;
    align-items: center;
    padding: 32px;
    background: var(--color-bg-primary);
    border: 1px solid #e0e3eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 300ms cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
    overflow: hidden;
  }

  .capacidad-destino::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #6366f1, #8b5cf6);
    transform: scaleY(0);
    transform-origin: top;
    transition: transform 150ms ease;
  }

  .capacidad-destino:hover {
    border-color: #6366f1;
    background: rgba(79, 70, 229, 0.04);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    transform: translateY(-4px);
  }

  .capacidad-destino:hover::before {
    transform: scaleY(1);
  }

  .destino-numero {
    font-size: 14px;
    font-weight: 700;
    color: #6366f1;
    opacity: 0.6;
    text-align: center;
  }

  .destino-content {
    text-align: left;
    min-width: 0;
  }

  .destino-titulo {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1a1d23;
  }

  .destino-subtitulo {
    margin: 8px 0 0 0;
    font-size: 13px;
    color: #8a90a3;
  }

  .destino-visual {
    font-size: 40px;
    text-align: center;
  }

  /* ════════════════════════════════════════════════════════════════
     FOOTER: Mínimo
     ════════════════════════════════════════════════════════════════ */

  .negocio-footer {
    padding-top: 40px;
    border-top: 1px solid #e0e3eb;
    text-align: center;
  }

  .btn-cambiar-negocio {
    padding: 12px 24px;
    background: transparent;
    border: 1px solid #e0e3eb;
    border-radius: 8px;
    color: #565d73;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 150ms ease;
  }

  .btn-cambiar-negocio:hover {
    background: #f8f9fb;
    border-color: #cbd5e1;
    color: #1a1d23;
  }

  @media (max-width: 768px) {
    .negocio-viewport {
      padding: 60px 20px;
      gap: 40px;
    }

    .negocio-nombre-grande {
      font-size: 36px;
    }

    .negocio-pulse-container {
      gap: 40px;
      flex-direction: column;
    }

    .capacidades-destinos {
      grid-template-columns: 1fr;
    }

    .capacidad-destino {
      grid-template-columns: 1fr;
      gap: 16px;
    }

    .destino-numero {
      display: none;
    }

    .destino-visual {
      text-align: right;
    }
  }
</style>

<script>
  function enterCapacity(capacity) {
    console.log('Entering capacity:', capacity);
  }

  function openBusinessSwitcher() {
    console.log('Opening business switcher');
  }
</script>
