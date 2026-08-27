# BIXO HANDOFF

> Este archivo es el punto de entrada de cada sesion. Leelo antes de tocar codigo.

Ultima actualizacion: 2026-08-27
Branch: `refactor/store-builder-canonical-context`
Ultimo commit revisado: `2a64c15`

## Fase actual

FASE 0 — Auditoria AS-IS. **Completada** en lo que respecta a medicion; los
documentos de memoria persistente quedan creados.

## Ultima tarea terminada

Fase 1 casi completa: contrato de scope para las 14 entidades nucleares,
2 huecos mas cerrados (CatalogValue con fachada, BotTransition ajena),
auditoria de allProjects() en jobs limpia, y TD-010 descubierta y registrada
(dos generaciones de flujos de bot). Antes: **RISK-001 cerrado**: `HasProjectScope` en `RifaVenta`, lecturas
multi-proyecto con `allProjects()` explicito, unicidad global de
`order_number`, y `tests/Feature/AislamientoTenantTest.php` como plantilla
de TD-002. Antes de eso: auditoria medida del repositorio: rutas por portal, aislamiento multiempresa
por metodo, generaciones de permisos, entidades competidoras y volumen real
de negocio. Resultados en `BIXO_CURRENT_STATE.md` y `BIXO_RISKS.md`.

## Tarea en progreso

Ninguna. Fase 0 cerrada a la espera de decision sobre el orden de la Fase 1.

## Siguiente tarea

Reestructura ejecutada hasta F8 (ADR-010). Queda la limpieza diferida:
borrar vistas f-slug tras un ciclo, retirar lectores de
`comercial_project_id`, mapa de navegacion y apagado del panel raiz.
F9 (checkout) y F11 (portal cliente) pasan al backlog de producto;
10/12/13 esperan volumen (ADR-005). Antes: Fase 2 COMPLETA (queda el retiro final de
nombres legacy tras un ciclo de uso). Sigue Fase 3: sesion unica de
proyecto (TD-005) e impersonacion auditada. Antes: Fase 1 COMPLETA. Sigue Fase 2: TD-003 (brand_catalog_id huerfano),
retiro final de nombres legacy de TD-004 tras un ciclo de uso, TD-010
(dos generaciones de flujos de bot), y sacar del control plane la
operacion de tenant (AdminImport/AdminTurnos). Antes era: decidir TD-001 — unica tarea de la
Fase 1 que queda, y es decision de diseño, no mecanica. Con eso la Fase 1
cierra y sigue la Fase 2 (ownership: TD-003 brand_catalog_id, TD-004 permisos
legacy, TD-010 dos generaciones de flujos de bot). Antes era: **RISK-001** — RifaVenta sin comprobacion de tenant (5 metodos). Es el unico
hallazgo de acceso cruzado confirmado y no depende de ninguna reestructuracion:
se corrige solo, con test de aislamiento. Ver `docs/security/BIXO_TENANT_ISOLATION.md`.

## Archivos actualmente involucrados

Ninguno en edicion. La auditoria no modifico codigo de aplicacion.

## Tests

- Suite completa: **874 passed** (3.375 assertions) al commit `2a64c15`.
- Tests de aislamiento multiempresa por entidad: **no existen todavia** (deuda TD-002).

## Problemas conocidos

- Certificado SSL de `arindg.com` **vencido el 2026-08-27**; existe `arindg-wildcard`
  valido 68 dias que ya cubre el dominio. No es un tema de arquitectura pero
  afecta a produccion.
- 59 archivos modificados sin commitear en el arbol de trabajo, varios de otra
  sesion (procesamiento de imagenes). No commitear a ciegas.

## Decisiones pendientes

1. Orden de la Fase 1: corregir RISK-001 antes o despues de unificar sesiones.
2. Si `/r/{slug}` (revendedores) sigue vivo comercialmente.
3. Nombre en castellano para la experiencia "Growth".

## NO TOCAR

- `whatsbot/`, `.env`, `storage/`, `vendor/`, `node_modules/`.
- Pedidos 20, 21 y 22.
- `resources/views/public/templates/computienda.blade.php` sin comparar antes
  contra produccion: el archivo tiene deriva y trabajo de otra sesion.

## Contexto necesario para continuar

Leer en este orden: `BIXO_CURRENT_STATE.md`, `BIXO_MASTER_CHECKLIST.md`,
`BIXO_DECISIONS.md`, `MODULE_OWNERSHIP.md`, `BIXO_RESTRUCTURE_ROADMAP.md`.
Los cuatro documentos de arquitectura preexistentes (`BIXO_PLATFORM_AUDIT.md`,
`BIXO_TARGET_ARCHITECTURE.md`, `BIXO_RESTRUCTURE_ROADMAP.md`,
`MODULE_OWNERSHIP.md`) son de una sesion anterior y siguen vigentes: esta
auditoria los complementa con evidencia medida, no los sustituye.
