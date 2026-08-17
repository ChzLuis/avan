# DESIGN.md — Sistema visual del mini-ERP (UX0)

> Salida del carril **UX0 · Fundamentos** ([bixo-ux-roadmap.md](bixo-ux-roadmap.md)).
> Consolida lo que YA existe — no es un rebrand. Baseline medida el 2026-08-16
> (`qa-avan/resultados/ux0/baseline.json` + capturas por área y viewport).

---

## 1. Principios

**SaaS ERP operacional, no landing.** Prioridades en orden: claridad ·
densidad controlada · jerarquía · velocidad · confianza.

- El movimiento comunica estado (150-300 ms) y respeta `prefers-reduced-motion`.
- El estado **nunca** se comunica solo por color: siempre etiqueta + color.
- Vocabulario visible **en español**; las claves internas (`draft`, `pending`,
  `whatsapp`) viven en atributos, jamás como texto.
- Los emojis son **contenido** (mensajes al cliente), no iconografía. Iconos =
  SVG inline con `stroke:currentColor`.

## 2. Tokens (viven en `resources/css/app.css` — fuente única)

| Grupo | Tokens |
|---|---|
| Superficie | `--superficie` #fff · `--superficie-2` #f8f9fb |
| Borde | `--borde` #e5e7eb · `--borde-suave` #f3f4f6 |
| Texto | `--texto` #111827 · `--texto-debil` **#5b6270** (5.9:1 AA; antes #6b7280 daba 4.39) |
| Acento | `--acento` #6366f1 · `--acento-oscuro` #4f46e5 · `--acento-fuerte` #4338ca · `--acento-suave` #e0e7ff · `--foco` #4338ca |
| Semánticos | `--exito-fuerte/suave` · `--peligro-fuerte/suave` · `--aviso-fuerte/suave` |
| Espaciado 4/8 | `--esp-1..6` (4, 8, 12, 16, 24, 32) |
| Radio | `--radio-1` 8 · `--radio-2` 12 · `--radio-pill` 999 |
| Táctil | `--tactil-min` **44px** · `--tactil-gap` **8px** |
| Z | `--z-drawer` 40 · `--z-modal` 60 · `--z-toast` 80 |

Regla: **cero hex nuevos en vistas**. Al tocar un área, sus colores migran a
tokens (así se drenan los 73 hex heredados sin big-bang).

## 3. Catálogo de componentes existentes

| Componente | Dónde vive | Estado |
|---|---|---|
| Badge de estado (`.status-pill`, `.q-badge-*`) | orders/quotes | etiqueta+color, canónico |
| Stepper de estado (botones) | quotes | accesible (`aria-pressed`), Rechazada roja |
| Drawer enlazable (`#ped-drawer`) | orders | deep link + foco gestionado |
| Maestro-detalle con historial | quotes | `pushState`/`popstate`, foco de retorno |
| Modal de confirmación | quotes/_acciones | `role=dialog` + `x-trap` + Escape |
| Fichas móviles (tabla→cards ≤1024) | quotes | `data-label` por celda |
| Toolbar con chips de filtro | orders/quotes/clients | 44px |
| Botón de icono (`.q-icon-btn`, `.cli-icon-btn`) | quotes/clients | 44×44 |
| Avisos (`role=status`/`role=alert`) | quotes | aria-live |

## 4. Reglas de dinero (contrato duro, con tests)

- El dinero **jamás** pasa por float: serialización PHP en string canónico
  (`LineMath::canon`), aritmética en **centavos BigInt**, presentación con
  separadores sobre el string (`LineMath::present` / `lmPresent` /
  `odPresent`).
- `toFixed`/`parseFloat`/`Number()` sobre importes: **prohibidos** (búsqueda
  negativa en tests). `Number()` solo para cantidad entera, centavos exactos o
  identificadores.
- Bordes canónicos de regresión: `33.33×3@10 = 89.99` · `3,869.10×5 = 19,345.50`.

## 5. Trampas registradas (aprendidas con sangre)

1. `<template x-if>` exige **un elemento raíz**: con texto suelto, Alpine hace
   `content.firstElementChild` → null y revienta (`_x_dataStack`).
2. Comillas dobles dentro del atributo `x-data` **parten el componente** (el
   HTML corta el atributo). Validar con `node --check` sobre el atributo.
3. `??` liga más débil que `||`: `a ?? null || b ?? null` accede `b` sin
   protección. Paréntesis siempre.
4. El `.hidden` de Tailwind pierde contra un `<style>` inline por orden de
   fuente a igual especificidad.
5. `fullPage`/cambiar viewport a mitad de test re-renderiza y **falsea
   capturas**: viewport fijado antes de navegar.
6. Comentarios Blade dentro de `@php` o `<style>` rompen todas las tiendas.
7. Un conteo de texto no es contrato: las sondas comparan **exacto** (crudo
   `whatsapp` ≠ etiqueta `WhatsApp`) y las aserciones son semánticas.

## 6. Baseline medida (2026-08-16, local = producción en lo desplegado)

Métrica por área (peor viewport): controles del **shell** excluidos del juicio
de módulo (15 controles <44 constantes en todas las páginas = deuda **UX1**).

| Área | táctiles <44 propios | emojis | crudas | overflow | Estado |
|---|---|---|---|---|---|
| Cotizaciones | **0** | 0 | 0 | 0 | **CONFORME** |
| Pedidos | **0** | 0 | 0 | 0 | **CONFORME** |
| Clientes | **0** | 0 | 0 | 0 | **CONFORME** |
| Portal de cotización | **0** | 0 | 0 | 0 | **CONFORME** |
| Portal home | **0** | 0 | 0 | 0 | métricas core en 0 (barrido 2026-08-16) |
| Dashboard | **0** | 0 | 0 | 0 | métricas core en 0 |
| Cocina | **0** | **0** | 0 | 0 | métricas core en 0 (antes 12 táctiles + 16 emojis) |
| Facturas | **0** | 0 | 0 | 0 | métricas core en 0 |
| Reportes | **0** | 0 | 0 | 0 | métricas core en 0 |
| **Shell (nav)** | **0 en TODA página** | 0 | 0 | 0 | **UX1 EJECUTADO** (antes 15) |
| POS / Caja | sin medir (403: el rol baseline no tiene `pos.*`/`caja.*`) | | | | pendiente de rol |

*"Métricas core en 0" = táctil 44/8, emojis, claves crudas y overflow
verificados en 3 viewports; para declararlas CONFORMES faltan del DoD los
estados vacío/carga diseñados, contraste medido formalmente y recorrido de
teclado por área.*

### Indicadores (fórmulas de §1.1 del roadmap maestro)

- **Funcional**: 3 de 12 cerrados (Base, Pedidos, Cotizaciones) = **25 %**
- **Visual/UX**: 4 de 12 áreas conformes = **33 %** *(primera medición real:
  deja de ser NO MEDIDO)*
- **Global ponderado** (60/40): **28 %**
