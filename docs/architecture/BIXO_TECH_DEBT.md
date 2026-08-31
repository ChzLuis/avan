# Registro de deuda tecnica

Ultima actualizacion: 2026-08-27

| ID | Sev. | Dominio | Descripcion | Fase objetivo | Estado |
|---|---|---|---|---|---|
| TD-001 | HIGH | Tenancy | `HasProjectScope` es fail-open (sin sesion no filtra) | 1 | **CERRADA 2026-08-27** |
| TD-002 | HIGH | Tenancy | Cero tests de aislamiento por entidad | 1 | ABIERTA |
| TD-003 | MEDIUM | Catalog | `products.brand_catalog_id` — el nombre engañaba: es FK a `catalog_values` (lista tipo brand), con migracion y consumidores. Dueño canonico: Catalog | 2 | **CERRADA 2026-08-27 (falsa alarma documentada)** |
| TD-004 | MEDIUM | Identity | 9 permisos legacy verbo-recurso conviven con `dominio.accion`; `project.can` acepta `A\|B` como puente | 2 | ABIERTA |
| TD-005 | MEDIUM | Plataforma | 3 sesiones de proyecto (`active_project_id`, `comercial_project_id`, `facturacion_auth.{slug}`) | 3-4 | ABIERTA |
| TD-006 | MEDIUM | Fiscal | Casos no cubiertos: credito con cuotas, lineas exoneradas, detracciones, resumen diario de bajas de boletas | segun demanda | ABIERTA |
| TD-007 | LOW | Vistas | `/f/{slug}`: 13 vistas / 4.038 lineas duplicando pantallas de sales | 5 | ABIERTA |
| TD-008 | LOW | Repo | 30+ archivos de trabajo ajeno sin commitear (procesamiento de imagenes); ya desplegado quirurgicamente | otra sesion | ABIERTA |
| TD-010 | LOW | Automation | Dos generaciones de flujos de bot — MEDIDO 2026-08-27: la vieja (`bot_flows`/`bot_states`/`bot_transitions`) esta VACIA en produccion; los 12 flujos vivos son `bot_builder_flows`. La generacion vieja y su editor en BotStatusController quedan DEPRECATE: retirar en Fase 5 tras verificar consumidores | 5 | DEGRADADA (deprecar, no refactorizar) |
| TD-011 | MEDIUM | Catalog | `ImportLog::create` corria contra una tabla inexistente y un `catch {}` vacio se lo tragaba: la auditoria de importaciones se perdia en silencio desde siempre | 2 | **CERRADA 2026-08-27** (migracion `2026_08_27_200000` en local y produccion) |
| TD-009 | LOW | Infra | Renovacion de certbot no cubria `arindg.com`: el vhost de n8n proxyaba el desafio ACME al puerto 5678. Bloque 80 con excepcion .well-known + cert HTTP-01 propio; dry-run OK 2026-08-30 | inmediata | CERRADA 2026-08-30 |
| TD-012 | HIGH | Tenancy | **WaBotController cross-tenant** (RISK-008): rutas publicas `/wa/*` con token compartido + `Order::allProjects()` sin filtro; mutable IDOR | inmediata | ABIERTA |
| TD-013 | HIGH | Tenancy | **Fix de rifa incompleto** (RISK-009): nuevoManual/eliminar/editar/recordar sin filtro project_id en BotInstance | inmediata | ABIERTA |
| TD-014 | HIGH | Finance | **Deuda con 3 fuentes** (RISK-010): PaymentController escribe payment_status sin asiento en el Ledger; Cobranza y 360 divergen. Unificar en Ledger | inmediata | ABIERTA |
| TD-015 | HIGH | Inventory | **Stock de variante fuera del Kardex** (RISK-011): `product_variants.stock` con `decrement()` directo. Enrutar por InventoryLedger o unificar existencias a nivel variante | proxima | ABIERTA |
| TD-016 | MEDIUM | Fiscal | **Numeracion fuera de transaccion** (RISK-012): QuoteController:836, InvoiceController:214 llaman nextCorrelativo crudo. Usar siempre Invoice::emitirNumero | proxima | ABIERTA |
| TD-017 | MEDIUM | Entitlements | **/bixosales sin `module:`** (RISK-013): el App Shell nuevo no exigia entitlement | proxima | **CERRADA 2026-08-28** (middleware `comercial.module` gatea orders/clients/invoices/quotes/catalog/logistics; backfill de logistics para tecsist; `ComercialEntitlementTest` 5/5). Queda unificar `ModulosPortal` con project_modules como fuente unica de visibilidad — TD menor) |
| TD-018 | MEDIUM | CRM | **client_id no se enlaza** en checkout web (PublicController:806) ni bot (BotWebhookController:207); Customer 360 con 3 de 5 fuentes vacias (Invoice/GuiaRemision.client_id, SalesInteraction sin escritor); Customer360Test da falso positivo | proxima | ABIERTA |
| TD-019 | MEDIUM | Sales | 7 de 9 creadores de Order no descuentan stock (solo POS y checkout web); centralizar el descuento en un caso de uso de Operations | proxima | ABIERTA |
| TD-020 | LOW | Pricing | Seleccion de precio divergente por canal (POS reseller/sugerido; web ignora wholesale; bot expone wholesale). Falta un PriceResolver unico | segun demanda | ABIERTA |
| TD-021 | LOW | Operations | Purchasing no modelado: la compra es solo motivo `'compra'` en el Kardex, sin cabecera/lineas/vinculo a Proveedor | segun demanda | ABIERTA |
| TD-022 | LOW | Plataforma | No existe entidad Plan/suscripcion de tenant; "licencia" = asiento por usuario. Impersonacion vive en /bixoadmin (deberia /admin); tenant puede auto-editar sus modulos (web.php:98) | segun demanda | ABIERTA |
| TD-023 | MEDIUM | Workspace | **Unificacion visual fase 2** | tras variantes | **CERRADA 2026-08-30 (por otra via)**: el salto de menu dentro de /bixoadmin se resolvio sirviendo TODA su configuracion con el shell del panel — NO habia que re-parentar nada al shell comercial ni esperar a variantes. Un intento de delegar `AppLayout` al shell comercial se REVIRTIO el mismo dia: llevaba al panel el encabezado y las alertas de ventas y quitaba el selector de negocio. Regla: cada cara conserva su diseno. `ShellUnicoWorkspaceTest` 11/11 |
| TD-024 | LOW | Control | Bloques del sidebar objetivo del Control SIN backend (Productos y planes, Add-ons, Feature flags como sistema, Plataforma/health, Webhooks, Integraciones globales, Historial de soporte): no se inventaron CRUDs en la reestructuracion 2026-08-30; se construyen cuando exista el dominio real | segun demanda | ABIERTA |
| TD-025 | MEDIUM | Workspace | Aplicacion fina de capacidades: bloquear el cambio de motor DENTRO de la etapa Apariencia con `cap_builder_avanzado` y separar la pantalla SEO en basico/avanzado con `cap_seo_avanzado`. El gate (`Capacidades` + middleware `capacidad`) ya existe y protege plantillas/diseño legacy | proxima sesion | ABIERTA |

Regla: la deuda se registra aqui, no en comentarios sueltos. Al cerrarla,
anotar commit y test que la cubre.
