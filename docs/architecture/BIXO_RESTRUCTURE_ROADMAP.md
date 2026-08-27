# Roadmap incremental de reestructuración BIXO

## Principios de ejecución

- Cambios pequeños, reversibles y cubiertos por pruebas.
- Primero seguridad y contratos; después carpetas y apariencia.
- Una URL y pantalla canónica por entidad.
- Compatibilidad temporal explícita, con fecha/condición de retiro.
- Mantener separadas las cinco capas definidas en `BIXO_TARGET_ARCHITECTURE.md`: plataforma, productos comerciales, dominios técnicos, experiencias y roles.

## Fase 0 — auditoría y línea base

Entregables: auditoría AS-IS, ownership, inventario de rutas, tests existentes y baseline.  
Estado: iniciado. La matriz priorizada y ownership están documentados.

Gate de salida: suite ejecutable en el entorno y lista de fallos preexistentes diferenciada de regresiones. En esta máquina `php` no está en PATH; usar el binario XAMPP.

## Fase 1 — seguridad multiempresa uniforme

1. Clasificar cada modelo como global o tenant-owned.
2. Aplicar scope consistente a modelos tenant-owned y mantener escape explícito `allProjects()` sólo para sistema/control.
3. Probar aislamiento de lecturas, escrituras, relaciones y APIs.
4. Revisar tokens, firmas, rate limiting y replay de webhooks.

Riesgo: consultas de sistema que dependían de acceso global implícito. Mitigación: usar `allProjects()` de forma visible y testeada.

## Fase 2 — separar BIXO Control

1. Mantener tenants, módulos, licencias, usuarios globales, métricas, logs, demos y configuración global.
2. Mover imports y turnos al application plane del tenant.
3. Diseñar impersonación con motivo, duración, banner visible y `AccessEvent`.
4. Dejar redirects temporales; no duplicar CRUD.

## Fase 3 — acceso efectivo y App Shell

1. Centralizar la decisión: miembro/owner + capability entitlement + permiso.
2. Hacer que middleware y navegación consuman el mismo evaluador.
3. Consolidar `layouts/app`, shell comercial, bots y facturación.
4. Ocultar módulos sin entitlement y acciones sin permiso.

Workspace se implementa como composición y navegación. No se crearán modelos o controladores `Workspace*` para duplicar capacidades existentes.

## Fase 4 — productos comerciales y entitlements

No reemplazar `modules`. Agregar una capa de productos/planes que agrupe capacidades:

- Sales → clients, quotes, orders, pos.
- Commerce → store, pages y builder.
- Operations → inventory, proveedores, logistics.
- Finance → payments, receivables, invoices.
- ARIN → bots, comunicaciones, copilot.

El esquema exacto se decide tras inventariar billing actual. La evaluación debe conservar capacidades legacy durante la transición.

## Fase 5 — casos de uso canónicos

Extraer servicios de aplicación sólo donde hoy hay múltiples escritores:

- Crear/convertir cotización.
- Crear/cancelar pedido.
- Registrar/revertir pago.
- Reservar/mover/devolver stock.
- Alta/actualización de cliente.

Los controllers web, POS, Commerce, bot y API llaman al mismo caso de uso.

## Fase 6 — Customer 360 como read model

1. Crear una ficha canónica de cliente agregando relaciones existentes.
2. Detectar documentos sin `client_id`, referencias por teléfono y duplicados.
3. No crear una tabla `customer_360`; la primera versión será lectura agregada.
4. Añadir enlaces desde cotizaciones, pedidos, pagos, facturas y conversaciones hacia la misma ficha.

## Fase 7 — consolidación por dominio

- Catalog y CRM: una ficha canónica y enlaces desde consumidores.
- Finance: completar migración desde campos legacy al ledger sin borrar compatibilidad.
- Operations: órdenes de compra y fulfillment sólo cuando existan requisitos reales.
- Commerce: checkout previo opcional; `Order` sigue siendo el pedido definitivo.
- Automation: contratos versionados para leer Core y ejecutar casos de uso.

## Fase 8 — atribución, experiencias y señales

1. Instrumentar Campaign, TrackedLink y AttributionTouch antes de presentar métricas de QR.
2. Tratar Growth como composición, no como propietario.
3. Introducir Pulse con reglas deterministas, evidencia y acciones autorizadas.
4. Añadir IA sólo para explicación y orquestación; no para inventar el hecho base.

## Fase 9 — limpieza

Medir rutas legacy, agregar avisos, migrar enlaces, mantener redirects, retirar código y permisos antiguos únicamente cuando no haya consumidores. Ejecutar regresión funcional y visual antes de cada retiro.

## Próximos tres incrementos recomendados

1. Completar inventario de todos los modelos tenant-owned y cerrar scopes faltantes.
2. Crear un evaluador único de acceso y migrar primero una sección pequeña (Proveedores) como patrón.
3. Mover Turnos fuera de Control con redirect compatible y auditoría de acceso.
