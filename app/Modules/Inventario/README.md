# Inventario

**Qué va aquí:** `InventoryMovement` (kardex), `InventoryLedger` (**escritor único del stock**: nadie escribe `products.stock` directo), `Proveedor`, y sus pantallas (inventario, kardex por producto, proveedores con importación/exportación).

**Qué NO va aquí:** `Product` y `products.stock` son de Catálogo (Inventario los mueve a través del ledger). `ImportLog` tampoco: registra importaciones de productos y servicios y solo lo usan los controladores de catálogo → va con `Catalogo/`. Compras futuras entrarán aquí.

**Estado:** movido el 2026-09-17 (módulo 3/8).

```
Controllers/  InventoryController, ProveedorController
Models/       InventoryMovement, Proveedor
Support/      InventoryLedger
Views/        inventory/{index,kardex}, company/proveedores  -> view('inventario::inventory.index')
```

Rutas: `/bixoadmin/inventario*` (gate `module:inventory|catalog` — no existe un
módulo propio de inventario, se entra con el de catálogo) y
`/bixoadmin/company/proveedores*` (`can:proveedores.*`).

**Quién lo usa desde fuera** (todos por FQCN, sin lógica propia de stock):
`Catalog/ProductController` (ajustes y conteos), `OrderController` (salidas y
devoluciones), `PosController` (venta), `PublicController` (checkout de la
tienda), `Catalog/Sync/CatalogSyncManager` (sincronización de proveedor),
`Control/AdminImportController` (importación masiva), `Support/BusquedaGlobal`
(proveedores), `Product::movimientos()`, `Project::proveedores()`.

**Red:** `tests/Feature/InventarioModuloTest` (3): pantallas con su vista y
guardián de frontera. El contrato del ledger lo cubre `InventoryLedgerTest`.

**Para desplegar a ARIN:** archivos nuevos + borrar los viejos
(`InventoryController.php`, `ProveedorController.php`, `InventoryMovement.php`,
`Proveedor.php`, `Support/InventoryLedger.php`, `resources/views/inventory/`,
`resources/views/company/proveedores.blade.php`) + `composer dump-autoload -o`
+ caché de rutas. Junto con Personas y Control.
