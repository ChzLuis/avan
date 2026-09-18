# Catalogo

**Qué va aquí:** productos y todo lo suyo (`Product`, variantes, atributos, imágenes, plantillas de imagen, etiquetas, unidades de medida), categorías, servicios, listas de catálogo con sus valores (marcas, etc.), combos, el registro de importaciones (`ImportLog`) y los **conectores de catálogo** (`Conectores/`: contratos, DTOs, sincronización y proveedores como SISKOTE; antes `app/Catalog`), con su `CatalogServiceProvider` y los comandos/jobs de sincronización e imágenes.

**Qué NO va aquí:** el stock y sus movimientos (Inventario: `InventoryLedger` es el único escritor), los precios de revendedor (`ResellerPrice`, Ventas), cómo se pinta el catálogo en la tienda (Tienda: `StoreCatalogProfile`, `CatalogQueryService`), el procesador de imágenes (`App\Support\Imagen`, `ImageVariants`: Core, lo usan varios módulos), `BusquedaGlobal` (Core), WooSync (sincroniza **pedidos**, no productos: Ventas).

**Estado:** movido el 2026-09-17 (módulo 7/8).

```
Controllers/   Product, Category, Service, ProductVariant, CatalogIntegration,
               CatalogList, Combo, ProductImageTemplate
Models/        Product, ProductAttribute, ProductAttributeValue, ProductImage, ProductImageTemplate,
               ProductVariant, Category, Service, CatalogList, CatalogValue, CatalogIntegration,
               CatalogIntegrationItem, CatalogSyncRun, ImportLog, Combo, ComboItem
Support/       EtiquetasProducto, UnidadesMedida, SquareImage
Conectores/    Contracts/, DTOs/, Enums/, Exceptions/, Providers/{Siskote,Fake}, Registry/, Sync/
CatalogServiceProvider.php   (registrado en bootstrap/providers.php; registra los conectores)
Commands/      SyncCatalogCommand (catalog:sync), GenerarSkuProductos (bixo:generar-sku),
               ImportarImagenesExternas (imagenes:importar-externas), RegenerarImagenes (imagenes:regenerar)
Jobs/          GenerarImagenesProducto, RunCatalogSync
Views/         catalog/{products,categories,services,integrations,reviews}, catalogs/, combos/,
               components/etiquetas-producto
```

`app/Catalog` pasó a `Conectores/` porque `Catalogo\Catalog` no dice nada y lo
que hay dentro son los conectores con proveedores externos (SISKOTE, el falso
de pruebas) y el motor de sincronización. Mismo nombre de clase. Para añadir
un proveedor: ver `docs/` de conectores y `CatalogServiceProvider::boot()`.

**Trampa de nombres:** `catalog.*` es sobre todo nombre de **permiso**
(`catalog.ver`, `catalog.editar`, `catalog.importar`...) y de claves del
checklist (`catalog.no_products`); las vistas son `catalogo::catalog.*`. El
generador solo renombró literales que coinciden con una vista real.

**Quién lo usa desde fuera:** casi todo. `Project` (relaciones), `OrderItem`,
`Appointment` (servicios), `ResellerPrice`, POS, pedidos, cotizaciones,
checkout de Tienda, `CatalogQueryService`, el bot (FlowRunner), inventario
(kardex), reportes. Es el dominio más consumido: por eso va casi al final.
Todos importan por `App\Modules\Catalogo\Models\...`.

**Vista `catalog/reviews`:** la pinta `ProductController` (moderación de
reseñas) aunque el modelo `Review` es de Tienda. Se queda con quien la pinta.

**Red:** `tests/Feature/CatalogoModuloTest` (5): productos/categorías/servicios,
listas/combos/integraciones con su vista, registro de conectores cargado,
comandos registrados y guardián de frontera. El catálogo lo cubren sus tests
propios (`CatalogoProductosValidacionTest`, `ProductVariantsTest`,
`EtiquetasProductoTest`, `Siskote*`, `CatalogSync*`, `ImportacionTest`...).

**Para desplegar a ARIN:** junto con los módulos 1–6. Además de lo habitual,
`bootstrap/providers.php` cambia (el provider se movió) y la cola debe estar
vacía: `RunCatalogSync` y `GenerarImagenesProducto` se encolan por nombre de
clase.
