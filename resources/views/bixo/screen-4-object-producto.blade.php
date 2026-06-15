<!-- SCREEN 4: OBJECT PRODUCTO - Deep Focus, Single Thing -->
<div class="bixo-screen object-producto" id="screen4">
  <!-- CONTEXT BAR: Minimal, respectful -->
  <div class="context-bar minimal">
    <button class="back-button" onclick="closeDetail()">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>
    <div class="breadcrumb">
      <span class="breadcrumb-item" onclick="closeDetail()">Inventario</span>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-item active">Tubo PVC 3"</span>
    </div>
    <div class="context-spacer"></div>
    <div class="object-actions-top">
      <button class="action-btn-icon">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
      </button>
      <button class="action-btn-icon">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- CONTENT: Full focus on the object -->
  <div class="detail-content">
    <!-- Left Column: Main Data -->
    <div class="detail-main">
      <!-- Product Header -->
      <div class="product-header-detail">
        <div class="product-image-placeholder">
          📦
        </div>
        <div class="product-identity">
          <h1 class="product-name-detail">Tubo PVC 3"</h1>
          <p class="product-sku-detail">SKU-00128</p>
          <div class="product-badges-detail">
            <span class="badge-status">Activo</span>
            <span class="badge-category">Plomería</span>
          </div>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="detail-section">
        <h3 class="section-title">Estado Actual</h3>
        <div class="metrics-grid">
          <div class="metric-item">
            <p class="metric-label">Stock Disponible</p>
            <p class="metric-value critical">8 unidades</p>
            <p class="metric-hint">Stock bajo - Reabastecer pronto</p>
          </div>
          <div class="metric-item">
            <p class="metric-label">Precio Venta</p>
            <p class="metric-value">$5.50</p>
            <p class="metric-hint">Precio público</p>
          </div>
          <div class="metric-item">
            <p class="metric-label">Costo Base</p>
            <p class="metric-value">$3.20</p>
            <p class="metric-hint">Último comprado</p>
          </div>
          <div class="metric-item">
            <p class="metric-label">Margen</p>
            <p class="metric-value">71.9%</p>
            <p class="metric-hint">Ganancia aproximada</p>
          </div>
        </div>
      </div>

      <!-- Detailed Information -->
      <div class="detail-section">
        <h3 class="section-title">Información</h3>
        <div class="detail-fields">
          <div class="detail-field">
            <label class="field-label">Descripción</label>
            <p class="field-value">Tubería de PVC de 3 pulgadas, rigida, para sistemas de agua fría y descarga. Cumple normas ISO.</p>
          </div>
          <div class="field-row">
            <div class="detail-field">
              <label class="field-label">Unidad</label>
              <p class="field-value">Metros</p>
            </div>
            <div class="detail-field">
              <label class="field-label">Peso</label>
              <p class="field-value">1.2 kg/m</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="detail-section">
        <h3 class="section-title">Actividad Reciente</h3>
        <div class="activity-list">
          <div class="activity-row">
            <span class="activity-icon">📤</span>
            <div class="activity-detail">
              <p class="activity-text">Venta: -6 unidades</p>
              <p class="activity-time">Hace 2 horas</p>
            </div>
          </div>
          <div class="activity-row">
            <span class="activity-icon">📥</span>
            <div class="activity-detail">
              <p class="activity-text">Compra: +50 unidades</p>
              <p class="activity-time">Hace 5 días</p>
            </div>
          </div>
          <div class="activity-row">
            <span class="activity-icon">✏️</span>
            <div class="activity-detail">
              <p class="activity-text">Actualización de precio</p>
              <p class="activity-time">Hace 3 semanas</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Quick Actions -->
    <div class="detail-sidebar">
      <!-- Actions -->
      <div class="detail-section sticky-top">
        <h3 class="section-title">Acciones</h3>
        <div class="actions-list">
          <button class="action-full primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar compra
          </button>
          <button class="action-full secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            Registrar ajuste
          </button>
          <button class="action-full secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Ver historial completo
          </button>
        </div>
      </div>

      <!-- Related -->
      <div class="detail-section">
        <h3 class="section-title">Relacionado</h3>
        <div class="related-list">
          <div class="related-item">
            <p class="related-label">Categoría</p>
            <p class="related-value">Plomería</p>
          </div>
          <div class="related-item">
            <p class="related-label">Proveedor Principal</p>
            <p class="related-value">Tuberías LATINOAMÉRICA</p>
          </div>
          <div class="related-item">
            <p class="related-label">Código de Barras</p>
            <p class="related-value mono">7891234567890</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .object-producto {
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fb 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    animation: slideInFromRight var(--duration-long) var(--ease-anticipatory);
  }

  /* ════════════════════════════════════════════════════════════════
     CONTEXT BAR (Minimal)
     ════════════════════════════════════════════════════════════════ */

  .context-bar.minimal {
    display: flex;
    align-items: center;
    gap: var(--space-16);
    padding: var(--space-12) var(--space-32);
    background: var(--color-bg-primary);
    border-bottom: 1px solid var(--color-border-tertiary);
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
    flex-shrink: 0;
  }

  .back-button:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    font: 600 13px var(--font-family);
    flex: 1;
  }

  .breadcrumb-item {
    padding: var(--space-4) var(--space-8);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .breadcrumb-item.active {
    color: var(--color-text-primary);
    font-weight: 700;
  }

  .context-spacer {
    flex: 1;
  }

  .object-actions-top {
    display: flex;
    gap: var(--space-8);
  }

  .action-btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-md);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .action-btn-icon:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  /* ════════════════════════════════════════════════════════════════
     DETAIL CONTENT: Two-Column Layout
     ════════════════════════════════════════════════════════════════ */

  .detail-content {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: var(--space-32);
    padding: var(--space-32);
    max-width: 1200px;
  }

  .detail-main {
    display: flex;
    flex-direction: column;
    gap: var(--space-32);
  }

  /* Product Header */
  .product-header-detail {
    display: flex;
    gap: var(--space-32);
    padding: var(--space-32);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 12px;
  }

  .product-image-placeholder {
    width: 120px;
    height: 120px;
    background: var(--color-bg-secondary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    flex-shrink: 0;
  }

  .product-identity {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-12);
    justify-content: center;
  }

  .product-name-detail {
    margin: 0;
    font: var(--font-h1);
    color: var(--color-text-primary);
  }

  .product-sku-detail {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .product-badges-detail {
    display: flex;
    gap: var(--space-8);
  }

  .badge-status,
  .badge-category {
    display: inline-block;
    padding: var(--space-6) var(--space-12);
    background: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    border-radius: var(--border-radius-sm);
    font: var(--font-caption);
    white-space: nowrap;
  }

  /* Sections */
  .detail-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-16);
  }

  .section-title {
    margin: 0;
    font: 600 16px var(--font-family);
    color: var(--color-text-primary);
  }

  /* Metrics Grid */
  .metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--space-16);
  }

  .metric-item {
    padding: var(--space-16);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .metric-label {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .metric-value {
    margin: 0;
    font: 700 20px var(--font-family);
    color: var(--color-text-primary);
  }

  .metric-value.critical {
    color: var(--color-error);
  }

  .metric-hint {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  /* Detail Fields */
  .detail-fields {
    display: flex;
    flex-direction: column;
    gap: var(--space-16);
  }

  .field-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-16);
  }

  .detail-field {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
    padding: var(--space-16);
    background: var(--color-bg-secondary);
    border-radius: 8px;
  }

  .field-label {
    font: var(--font-caption);
    color: var(--color-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
  }

  .field-value {
    margin: 0;
    font: 600 14px var(--font-family);
    color: var(--color-text-primary);
  }

  /* Activity List */
  .activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-12);
  }

  .activity-row {
    display: flex;
    gap: var(--space-12);
    padding: var(--space-12);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-tertiary);
    border-radius: 6px;
  }

  .activity-icon {
    font-size: 20px;
    flex-shrink: 0;
  }

  .activity-detail {
    flex: 1;
    min-width: 0;
  }

  .activity-text {
    margin: 0;
    font: 600 13px var(--font-family);
    color: var(--color-text-primary);
  }

  .activity-time {
    margin: var(--space-4) 0 0 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  /* ════════════════════════════════════════════════════════════════
     SIDEBAR
     ════════════════════════════════════════════════════════════════ */

  .detail-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--space-32);
  }

  .sticky-top {
    position: sticky;
    top: 128px;
  }

  .actions-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .action-full {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-8);
    padding: var(--space-12) var(--space-16);
    border: none;
    border-radius: var(--border-radius-md);
    font: 600 12px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .action-full.primary {
    background: var(--color-accent-primary);
    color: white;
  }

  .action-full.primary:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  .action-full.secondary {
    background: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border-primary);
  }

  .action-full.secondary:hover {
    background: var(--color-bg-tertiary);
    color: var(--color-text-primary);
    border-color: var(--color-border-secondary);
  }

  .related-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-12);
  }

  .related-item {
    padding: var(--space-12);
    background: var(--color-bg-secondary);
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  .related-label {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .related-value {
    margin: 0;
    font: 600 13px var(--font-family);
    color: var(--color-text-primary);
  }

  .related-value.mono {
    font-family: var(--font-family-mono);
    font-size: 11px;
  }

  @media (max-width: 1024px) {
    .detail-content {
      grid-template-columns: 1fr;
      gap: var(--space-20);
      padding: var(--space-20);
    }

    .detail-sidebar {
      flex-direction: row;
      gap: var(--space-20);
    }

    .sticky-top {
      position: relative;
      top: auto;
    }
  }

  @media (max-width: 768px) {
    .detail-content {
      padding: var(--space-16);
    }

    .product-header-detail {
      flex-direction: column;
      padding: var(--space-20);
      gap: var(--space-16);
    }

    .product-image-placeholder {
      width: 100%;
      height: 200px;
    }

    .field-row {
      grid-template-columns: 1fr;
    }

    .detail-sidebar {
      flex-direction: column;
    }
  }
</style>

<script>
  function closeDetail() {
    console.log('Closing detail');
  }
</script>
