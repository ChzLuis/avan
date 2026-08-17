# Roadmap visual / UX — mini-ERP

> Plan visual propio, enlazado desde [bixo-mini-erp-roadmap.md](bixo-mini-erp-roadmap.md) §1.1.
> **Nada implementado**: este documento planifica; no autoriza código.
> Regla que lo gobierna: **auditar antes de cambiar**. No se aplica una paleta
> ni una tipografía "bonita" sobre un producto que aún no hemos inventariado.

---

## 0. Qué es este producto (y qué NO es)

Un **SaaS ERP operacional**: la gente lo usa muchas horas, repite las mismas
tareas y necesita ver mucha información sin perderse. **No es una landing.**
Las prioridades, en orden: **claridad · densidad controlada · jerarquía ·
velocidad · confianza**. Lo bonito que estorbe a la operación es un defecto.

Consecuencias prácticas:

- El movimiento se usa **solo para comunicar estado** (algo apareció, algo se
  guardó), 150–300 ms, y respeta `prefers-reduced-motion`. Nada decorativo.
- La densidad es alta pero **controlada**: filas de 44–48 px, no de 64.
- **Sin rebrand no autorizado.** Consolidar tokens ≠ cambiar la identidad.

---

## 1. Inventario de la identidad actual (medido, 2026-08-15)

Antes de proponer nada, esto es lo que hay:

| Medida | Valor | Lectura |
|---|---|---|
| Variables CSS en `app.css` | **16** (8 conceptos: `--color-primary`, `--color-primary-dark`, 4 de sidebar, `--sidebar-width`, `--list-width`) | **existe una base mínima**, no partimos de cero |
| Colores hex distintos en Cotizaciones + Pedidos | **73** | la paleta real vive hardcodeada en las vistas |
| Hex hardcodeados en `quotes/index.blade.php` | **224** | y 102 en `layouts/app`, 98 en `orders/index`, 44 en `sidebar-bixo` |
| Emojis en vistas Blade | **1075 en 111 archivos**, 158 distintos | se usan como iconografía estructural |
| Emojis en el núcleo comercial | `quotes/index` **9** (📦 👤 📋 💰 🔍 🔗 ✓ ✕) · `orders/index` **18** · `_drawer` **9** | encabezados de sección con emoji |
| SVG inline en Cotizaciones + Pedidos | **30** | conviven dos sistemas de iconos |
| Portal público | **0 emojis** | ya está limpio; es la referencia a seguir |
| Tamaño de las vistas núcleo | `quotes/index` **1173** líneas · `orders/index` **751** · `_drawer` **358** | monolitos difíciles de mantener |

**Conclusión del inventario**: el problema no es "falta diseño", es que hay
**tres sistemas conviviendo** (tokens CSS, hex hardcodeado, emoji + SVG). UX0
no inventa un sistema nuevo: **consolida el que ya existe** y retira los otros
dos.

---

## 2. Carriles

### UX0 · Fundamentos — *prerrequisito normal, con excepción controlada*

> UX0 precede a los demás carriles como norma. **Excepción acordada**: F1c
> (UX2) avanza en paralelo porque ya está auditado y corrige un estado falso
> visible hoy a cinco cotizaciones convertidas; a cambio, aporta sus tokens
> a `app.css` en vez de crear deuda. UX0 formal se ejecuta inmediatamente
> después de cerrar Cotizaciones.

- Inventario de identidad (**ya hecho**, §1) y decisión explícita de qué se
  conserva.
- **Tokens semánticos** (no "azul-500" sino `--superficie`, `--borde`,
  `--texto-débil`, `--peligro`, `--éxito`) de color, tipografía, espaciado,
  radio, sombra y `z-index`. Escala **4/8**.
- **Iconografía**: un solo sistema **SVG**. Los 1075 emojis dejan de ser
  estructura (pueden sobrevivir en contenido del usuario). Se migran por área,
  no de golpe.
- Catálogo de componentes y patrones **que ya existen**, con su nombre y dónde
  se usan: botón, campo, tabla, ficha, badge de estado, drawer, modal, chip de
  filtro, estado vacío, esqueleto de carga, toast.
- **Baseline visual**: capturas de cada área en 390/768/1440 y las métricas del
  DoD. Sin esto, el indicador visual del maestro sigue siendo **NO MEDIDO**.
- Salida: **[DESIGN.md](DESIGN.md)** + tokens en `app.css` + baseline
  (`qa-avan/resultados/ux0/`). **Cerrado 2026-08-16.**

### UX1 · Shell y navegación
Sidebar/topbar/drawer global, navegación móvil, estados activos, deep links,
foco y preservación de contexto al navegar. Es lo que envuelve a todo lo demás.

### UX2 · Núcleo comercial — **frente activo**
**Pedidos · Cotizaciones · Clientes.**
**Primer hito formal: [F1c v2](modulo1-f1c-expediente.md)** — Cotizaciones.
F1c no es solo estética: cierra el **ciclo de vida** del documento (guards de
servidor, portal de convertida, deep links, accesibilidad) y por eso encabeza
este carril. Después: revisión de Pedidos contra el DoD (hoy PARCIAL: su
drawer traía el `.toFixed` sobre string y labels crudos) y Clientes.

### UX3 · Núcleo financiero
CxC, Pagos y Facturación. Depende de que existan funcionalmente (F2–F4).

### UX4 · Operaciones
Entregas, Inventario/Kardex y flujos por rubro (`orders/kitchen` tiene 40 hex
propios: candidato claro a tokens).

### UX5 · Analítica
Dashboard, KPIs, tablas y visualización de datos. Reglas propias de gráficos:
legibilidad sin color como único canal, cifras alineadas, sin 3D decorativo.

### UX6 · Superficies públicas
Portal de cotización, catálogo y documentos (PDF/ticket/boleta). Ojo: **el
portal ya es el más limpio** (0 emojis, 0 cortes en móvil); sirve de patrón.

### UX7 · QA visual continuo — *transversal, no final*
Baseline, before/after, regresión visual, contraste y dispositivos. **Se
ejecuta en cada fase**, no al terminar el proyecto. Incluye la regla aprendida
en F1b: las capturas se toman con el viewport fijado antes de navegar, y
`fullPage` no se usa para medir, porque re-renderiza el componente.

---

## 3. Definición de Terminado (DoD) visual

Un área **no** está visualmente cerrada hasta cumplir **todo** esto:

1. Responsive verificado en **320, 375, 390, 768, 1024, 1440** y **móvil
   apaisado**.
2. **Cero** overflow horizontal y cero scroll anidado incómodo.
3. Controles táctiles **44×44 px** con **≥ 8 px** de separación; excepciones
   documentadas una a una.
4. **Contraste AA** (4.5:1 texto, 3:1 componentes), foco visible, recorrido por
   teclado, nombres accesibles y `prefers-reduced-motion`.
5. Estados **vacío, carga, error, éxito y deshabilitado** diseñados, no
   improvisados.
6. Navegación **Atrás/Adelante** y **deep link** donde haya maestro-detalle.
7. **Dinero, fechas y vocabulario consistentes** en todas las superficies
   (panel, portal, documentos y exportadores).
8. Iconografía **SVG coherente**; sin emojis como iconos estructurales.
9. Capturas **before/after** en desktop y móvil, **revisión humana** y consola
   y logs limpios.
10. **Sin regresión funcional** ni pérdida de permisos o aislamiento
    multiproyecto.

Los tests verdes **no** sustituyen el punto 9: hay que mirar.

---

## 4. Estado y siguiente paso

| Carril | Estado | Bloqueado por |
|---|---|---|
| UX0 Fundamentos | **CERRADO (2026-08-16)**: DESIGN.md + tokens + baseline de 8 áreas × 3 viewports | — |
| UX1 Shell | **EJECUTADO (2026-08-16)**: nav/topbar/panel a 44, buscador móvil, cierre 44 — 0 controles <44 en producción | — |
| **UX2 Núcleo comercial** | **COMPLETO (2026-08-16): Cotizaciones, Pedidos y Clientes conformes** | — |
| UX3 Financiero | NO INICIADO | F2–F4 funcionales |
| UX4 Operaciones | **Cocina con métricas core en 0** (12 táctiles y 16 emojis eliminados); resto pendiente | — |
| UX5 Analítica | NO INICIADO | UX0 |
| UX6 Públicas | PARCIAL (portal medido) | UX0 |
| UX7 QA visual | **TRANSVERSAL, activo** | — |

**Tensión que declaro**: UX0 debería preceder a UX2, pero F1c ya está
auditado, medido y a punto de autorizarse. Propongo **no bloquear F1c**: que
extraiga sus tokens a `app.css` en lugar de añadir más hex hardcodeado, de
modo que **sea la primera aportación a UX0** en vez de deuda nueva. Formalizar
UX0 completo justo después, con la baseline de las 12 áreas.
