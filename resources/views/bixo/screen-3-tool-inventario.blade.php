<!-- SCREEN 3: TOOL INVENTARIO - Content is King -->
<div class="bixo-screen tool-inventario" id="screen3">
  <!-- CONTEXT BAR: Shows where you are, not distracting -->
  <div class="context-bar">
    <button class="back-button" onclick="exitTool()">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>
    <div class="breadcrumb">
      <span class="breadcrumb-item" onclick="exitTool()">Operar</span>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-item active">Inventario</span>
    </div>
    <div class="context-spacer"></div>
    <div class="context-actions">
      <button class="context-action-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filtrar
      </button>
      <button class="context-action-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Ver
      </button>
    </div>
  </div>

  <!-- CONTENT AREA -->
  <div class="content-area">
    <!-- Tool Header -->
    <div class="tool-header">
      <div class="tool-header-left">
        <h1 class="tool-title">Productos</h1>
        <p class="tool-stat">234 productos en catálogo • 12 con stock bajo</p>
      </div>
      <button class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo producto
      </button>
    </div>

    <!-- Products Table/Grid -->
    <div class="products-container">
      <!-- Product Item 1 -->
      <div class="product-row" onclick="selectProduct(1)">
        <div class="product-checkbox">
          <input type="checkbox" />
        </div>
        <div class="product-cell product-name">
          <div class="product-name-content">
            <h4 class="product-title">Tubo PVC 3"</h4>
            <p class="product-sku">SKU-00128</p>
          </div>
        </div>
        <div class="product-cell product-category">
          <span class="category-badge">Plomería</span>
        </div>
        <div class="product-cell product-stock">
          <div class="stock-indicator low">
            <span class="stock-number">8</span>
            <span class="stock-label">unidades</span>
          </div>
        </div>
        <div class="product-cell product-price">
          <span class="price">$5.50</span>
        </div>
        <div class="product-cell product-actions">
          <button class="product-action-menu" onclick="event.stopPropagation()">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Product Item 2 -->
      <div class="product-row" onclick="selectProduct(2)">
        <div class="product-checkbox">
          <input type="checkbox" />
        </div>
        <div class="product-cell product-name">
          <div class="product-name-content">
            <h4 class="product-title">Cemento Gris 50kg</h4>
            <p class="product-sku">SKU-00245</p>
          </div>
        </div>
        <div class="product-cell product-category">
          <span class="category-badge">Construcción</span>
        </div>
        <div class="product-cell product-stock">
          <div class="stock-indicator high">
            <span class="stock-number">156</span>
            <span class="stock-label">sacos</span>
          </div>
        </div>
        <div class="product-cell product-price">
          <span class="price">$8.20</span>
        </div>
        <div class="product-cell product-actions">
          <button class="product-action-menu" onclick="event.stopPropagation()">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Product Item 3 -->
      <div class="product-row" onclick="selectProduct(3)">
        <div class="product-checkbox">
          <input type="checkbox" />
        </div>
        <div class="product-cell product-name">
          <div class="product-name-content">
            <h4 class="product-title">Herramienta Multiuso</h4>
            <p class="product-sku">SKU-00312</p>
          </div>
        </div>
        <div class="product-cell product-category">
          <span class="category-badge">Herramientas</span>
        </div>
        <div class="product-cell product-stock">
          <div class="stock-indicator warning">
            <span class="stock-number">24</span>
            <span class="stock-label">unidades</span>
          </div>
        </div>
        <div class="product-cell product-price">
          <span class="price">$45.00</span>
        </div>
        <div class="product-cell product-actions">
          <button class="product-action-menu" onclick="event.stopPropagation()">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .tool-inventario {
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
    padding: var(--space-12) var(--space-32);
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
  }

  .breadcrumb-item {
    padding: var(--space-4) var(--space-8);
    border-radius: var(--border-radius-sm);
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

  .context-actions {
    display: flex;
    gap: var(--space-8);
  }

  .context-action-btn {
    display: flex;
    align-items: center;
    gap: var(--space-6);
    padding: var(--space-8) var(--space-12);
    background: transparent;
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-md);
    color: var(--color-text-secondary);
    font: 600 12px var(--font-family);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .context-action-btn:hover {
    background: var(--color-bg-secondary);
    border-color: var(--color-border-secondary);
    color: var(--color-text-primary);
  }

  /* ════════════════════════════════════════════════════════════════
     CONTENT AREA
     ════════════════════════════════════════════════════════════════ */

  .content-area {
    flex: 1;
    padding: var(--space-32);
    display: flex;
    flex-direction: column;
    gap: var(--space-32);
  }

  .tool-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-32);
  }

  .tool-header-left {
    flex: 1;
  }

  .tool-title {
    margin: 0;
    font: var(--font-h2);
    color: var(--color-text-primary);
  }

  .tool-stat {
    margin: var(--space-8) 0 0 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .btn-primary {
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
    white-space: nowrap;
  }

  .btn-primary:hover {
    background: var(--color-accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
  }

  /* ════════════════════════════════════════════════════════════════
     PRODUCTS TABLE
     ════════════════════════════════════════════════════════════════ */

  .products-container {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
  }

  .product-row {
    display: grid;
    grid-template-columns: 40px 1fr 140px 140px 100px 50px;
    gap: var(--space-16);
    align-items: center;
    padding: var(--space-16) var(--space-20);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border-primary);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .product-row:hover {
    border-color: var(--color-accent-primary);
    background: rgba(79, 70, 229, 0.02);
    box-shadow: var(--shadow-light);
  }

  .product-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--color-accent-primary);
  }

  .product-cell {
    min-width: 0;
  }

  .product-name-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  .product-title {
    margin: 0;
    font: 600 14px var(--font-family);
    color: var(--color-text-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .product-sku {
    margin: 0;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .category-badge {
    display: inline-block;
    padding: var(--space-4) var(--space-8);
    background: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    border-radius: var(--border-radius-sm);
    font: var(--font-caption);
    white-space: nowrap;
  }

  .stock-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-8);
    border-radius: 6px;
  }

  .stock-indicator.high {
    background: rgba(5, 150, 105, 0.1);
  }

  .stock-indicator.warning {
    background: rgba(217, 119, 6, 0.1);
  }

  .stock-indicator.low {
    background: rgba(239, 68, 68, 0.1);
  }

  .stock-number {
    display: block;
    font: 700 16px var(--font-family);
    color: var(--color-text-primary);
  }

  .stock-label {
    display: block;
    font: var(--font-caption);
    color: var(--color-text-tertiary);
  }

  .price {
    font: 600 14px var(--font-family);
    color: var(--color-text-primary);
  }

  .product-action-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    border-radius: var(--border-radius-md);
    color: var(--color-text-tertiary);
    cursor: pointer;
    transition: all var(--duration-quick) var(--ease-smooth);
  }

  .product-action-menu:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  @media (max-width: 1024px) {
    .product-row {
      grid-template-columns: 40px 1fr 100px 100px 40px;
    }

    .product-category {
      display: none;
    }
  }

  @media (max-width: 768px) {
    .content-area {
      padding: var(--space-20);
    }

    .tool-header {
      flex-direction: column;
      align-items: flex-start;
      gap: var(--space-16);
    }

    .btn-primary {
      width: 100%;
      justify-content: center;
    }

    .context-bar {
      padding: var(--space-12) var(--space-16);
    }

    .context-actions {
      display: none;
    }

    .product-row {
      grid-template-columns: 40px 1fr 40px;
      gap: var(--space-12);
    }

    .product-category,
    .product-stock,
    .product-price {
      display: none;
    }
  }

  @media (max-width: 480px) {
    .product-checkbox {
      display: none;
    }

    .product-row {
      grid-template-columns: 1fr 40px;
    }
  }
</style>

<script>
  function selectProduct(productId) {
    console.log('Selecting product:', productId);
  }

  function exitTool() {
    console.log('Exiting tool');
  }
</script>
