# Fuentes canónicas de lectura del Store Builder

Fecha: 2026-07-29. Alcance: Fase 1A, exclusivamente lectura y preparación de datos.

## Propiedad por tipo de dato

| Dato | Fuente canónica actual | Compatibilidad conservada |
|---|---|---|
| Marca, colores, tipografías, header, footer, WhatsApp, catálogo y checkout | `project_settings` | aliases de claves y ajustes de `ProjectTemplate` |
| Plantilla efectiva | `project_settings.catalog_template` | `ProjectTemplate` es compatibilidad/modelo futuro, no propietario canónico |
| Secciones de Inicio | `store_sections` | defaults de `StorefrontSections` |
| Menús, items, jerarquía, orden y visibilidad | `store_menus` y `store_menu_items` | menú primario sintético cuando todavía no existe uno persistido |
| Nosotros, Contacto y páginas personalizadas | `store_pages` | fallback seguro vacío; Blog permanece fuera de esta fase |
| Pop-up | `store_popups` | `null` cuando no existe uno aplicable |
| Catálogo de plantillas soportadas | `CatalogTemplates` | las vistas productivas heredadas siguen disponibles por su flujo anterior |
| Categorías, productos, servicios, valoraciones y testimonios | modelos de catálogo del proyecto | conversión de servicios a productos compatible con el comportamiento previo |
| URLs públicas | rutas Laravel y dominio/slug efectivos del proyecto | fallback técnico a rutas con slug |

Las únicas plantillas seleccionables y adaptadas al contexto canónico en esta fase son `ecommerce`, `direct` y `computienda`.

## Precedencia única

1. Valor almacenado en la fuente canónica.
2. Alias heredado, solo si el canónico está ausente.
3. Ajuste compatible de `ProjectTemplate`, cuando corresponde.
4. Default de `CatalogTemplates`, `StorefrontSections` o del soporte del componente.
5. Fallback técnico seguro (`null`, colección vacía o valor estable documentado).

`null` se considera ausencia. `false`, `0`, `"0"` y la cadena vacía son valores explícitos y no permiten que un alias los sobrescriba.

## Aliases conservados

| Clave canónica | Alias heredado |
|---|---|
| `header_logo_url` | `logo_url` |
| `header_logo_height` | `logo_height` |
| `quote_wa_msg` | `whatsapp_msg` |
| `font_title` | `font` |
| `font_body` | `font` |
| `quote_whatsapp` | `whatsapp` |
| `btn_cart_text` | `cart_button_text` |
| `btn_checkout_text` | `checkout_button_text` |

El uso de aliases y fallbacks queda en `StorefrontContext::legacyFallbacksUsed()` para pruebas y futura migración. No se serializa al frontend, no genera escrituras y no registra información sensible.

## Contrato de lectura

`StorefrontContextBuilder::forProject()` construye una instancia aislada por proyecto. `StorefrontContext` expone datos de solo lectura para proyecto, plantilla/tema, ajustes globales, secciones, menús, páginas, pop-up, catálogo, URLs, capacidades, vista pública y menú efectivo. Los accesos `setting`, `section`, `menu`, `page`, `popup`, `template` y `publicUrl` aplican la misma resolución en el Diseñador y en las tres plantillas oficiales.

Las variables heredadas que todavía necesitan los Blade se derivan de esta misma instancia. Las plantillas no deciden precedencias ni consultan la base de datos.

## Escrituras pendientes

La Fase 1A no modifica `updateDesign`, aplicación de plantilla, borradores, publicación de secciones, menús, páginas, pop-up, seeders ni tablas. La unificación de esas escrituras, la limpieza de aliases y cualquier migración pertenecen a una Fase 1B o posterior con autorización separada.
