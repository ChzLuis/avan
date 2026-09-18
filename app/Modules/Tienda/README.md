# Tienda

**Qué va aquí:** la tienda pública que ve el cliente (catálogo, producto, carrito y checkout, reservas, páginas institucionales, blog, contacto, libro de reclamaciones), el **Constructor** (`StoreBuilderController` + `Storefront/*`: borradores, historial, checklist de publicación, auditoría de controles), plantillas de diseño (`DesignTemplate*`, `ProjectTemplate`), menús y navegación (`StoreMenu*`, `StorefrontNavigation`), secciones de inicio (`StoreSection`, `StorefrontSections`, `HomePresets`), cabeceras/pies/carritos por variante (`StorefrontLayoutPacks`, `HeaderPresets`), promociones, cupones, reseñas, perfiles de catálogo (`StoreCatalogProfile`) y los mensajes de contacto.

**Qué NO va aquí:** el pedido definitivo (el checkout convierte al `Order` de Ventas; no hay tabla paralela), el catálogo (lee `Product`/`Category` de Catalogo), el servicio de imágenes (`App\Support\Imagen`, `ImageVariants`: Core, lo usan rutas y middleware), `DetectCustomDomain` (middleware de Core que despacha a `TiendaPublicaController` por clase) y `SettingsController` (Core; hoy contiene el CRUD de cupones: deuda, ver abajo). `AbandonedCart` se queda en Core: lo escribe el checkout y lo lee el CRM.

**Estado:** movido el 2026-09-17 (módulo 6/8; el más grande en vistas: 81 movimientos, 527 reemplazos).

```
Controllers/   TiendaPublicaController (antes PublicController), StoreBuilder, StoreExperience,
               StoreNavigation, StorePage, DesignTemplate, Promotion, Complaint, CatalogProfile
Models/        Coupon, Promotion, Review, StoreCatalogProfile, StoreMenu, StoreMenuItem, StorePage,
               StorePopup, StoreSection, DesignTemplate, DesignTemplateVersion, ProjectTemplate,
               ContactMessage, Complaint
Support/       StorefrontLayoutPacks, StorefrontNavigation, StorefrontSections, StorefrontTheme,
               StorefrontThemePresets, HeaderPresets, DesignerIcons, ContenidoEjemplo,
               CatalogTemplates, CategoryIcons, IconosCategoria
Storefront/    los 24 servicios del Constructor (antes app/Storefront; mismo nombre de clase)
Commands/      AuditarConstructor (bixo:auditar-constructor), ConsolidarMotoresTienda
               (storefront:consolidate-engines), FusionarModelos (productos:fusionar-modelos)
Views/         public/, storefront/, promotions/, complaints/, layouts/storefront,
               settings/builder/, settings/design-templates, settings/partials/{catalog-profiles,
               store-navigation-builder, store-menu-item, institutional-pages},
               components/{storefront/, computienda/, store-menu, public-popup,
               public-store-runtime, storefront-home-sections, storefront-home-skins,
               storefront-motion, storefront-shapes}
```

`PublicController` se renombró a **`TiendaPublicaController`**: "Public" no decía
qué hace; es la tienda que ve el público.

**Vistas: dos trampas propias de este módulo.**
1. Los nombres de vista `public.*` **chocan con los nombres de ruta** `public.*`
   (`route('public.catalog')` sigue igual; `view('public.catalog')` pasó a
   `tienda::public.catalog`). Al añadir código, no confundirlos.
2. Varios parciales se eligen por **nombre construido** y con `view()->exists()`
   de guarda: `StorefrontLayoutPacks` (`"tienda::storefront.partials.{$slot}.{$variant}"`),
   `HeaderPresets` (`'tienda::storefront.partials.nav.'`), la plantilla
   `computienda` y `TiendaPublicaController` (arreglo de plantillas). Si el
   prefijo falta, no hay error: la tienda sale **sin cabecera**. `TiendaModuloTest`
   comprueba que los parciales existan bajo el prefijo.

Los componentes anónimos (`<x-storefront-motion>`, `<x-store-menu>`,
`<x-storefront.header>`...) **no cambian de tag**: el provider registra
`Views/components` como ruta de componentes. Solo cambia quien los incluía
por nombre de vista (`@include('tienda::components.public-popup')`).

`ConstructorAuditor` recorre las vistas por ruta de disco: el editor oficial es
`app/Modules/Tienda/Views/settings/builder` y los consumidores salen de
recorrer `app/` (que ya incluye las vistas del módulo). Dos tests leen vistas
por ruta de disco (`BloquesInicioTest`, `ConstructorCapacidadesRescatadasTest`).

**Quién lo usa desde fuera:** `DetectCustomDomain` (Core) despacha por clase a
`TiendaPublicaController`/`StorePageController`; `Project` y `Product` (Core)
tienen relaciones a los modelos de aquí; `SettingsController` (Core) gestiona
cupones y consulta `StorefrontContextBuilder`; `ProjectController` crea
empresas a partir de `ProjectTemplate`; Catalogo usa `StoreCatalogProfile`;
`Bots/` y `ProjectContext` usan `StorefrontNavigation`; `Control/` consulta
`StorefrontSections`; `LectorComprobantes` no.

**Deudas conocidas:**
- El CRUD de cupones (26 rutas `bixoadmin/coupons*`) vive en `SettingsController`
  (Core). Debería ser un `CouponController` de este módulo.
- `resources/views/settings/{design,designer,store-experience,partials/home-*}`
  son el Diseño clásico ya retirado (redirigen al Constructor): se quedan en
  Core hasta borrarlos; `design.blade.php` incluye 3 parciales de aquí por
  `tienda::`.
- Las 3 plantillas muertas del arreglo (`editorial`, `luxe`, `bistro`) no tienen
  vista; el `exists()` las salta.

**Red:** `tests/Feature/TiendaModuloTest` (6): tienda pública, Constructor,
promociones/reclamaciones/plantillas con su vista, parciales construidos
existentes, comandos registrados y guardián de frontera. La tienda la cubren
~50 tests propios (`Storefront*`, `Bloque*`, `Constructor*`, `Builder*`...).

**Para desplegar a ARIN:** junto con los módulos 1–5. Este módulo toca las
tiendas en producción: `view:clear` + caché de rutas obligatorios además de
borrar viejos y `composer dump-autoload -o`; comprobar con curl la portada,
un producto y `/bixoadmin/settings/builder` de 2 tiendas antes de dar por
bueno.
