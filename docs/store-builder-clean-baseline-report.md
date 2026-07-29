# Reporte del checkout limpio del Store Builder

Fecha de ejecución: 2026-07-29. Estado: **ROJO**.

## Identidad y commits

- Rama: `stabilize/store-builder-baseline`.
- Base original: `e788ee3`.
- HEAD inicial del Paso 6: `8b22f36f6b7795dd0c7fff8479261d29fcd8f8f4`.
- Commit de código candidato probado: `c38f991f45c66d11da2cfc6080842da0da25a60c`.
- Worktree: `C:\Users\luich\AppData\Local\Temp\avan-store-builder-clean-20260729-004753`.
- Estado inicial del worktree: vacío (`git status --porcelain` sin salida), 557 archivos rastreados.

Commits creados en el Paso 6:

| Commit | Propósito |
|---|---|
| `732268b` | Rastrear 67 archivos requeridos de Store Builder y compatibilidad |
| `b3904cd` | Ignorar artefactos locales generados |
| `37422e9` | Rastrear la vista heredada `tecnologia` descubierta por el checkout limpio |
| `c38f991` | Rastrear la vista heredada `lavanderia` descubierta por el checkout limpio |

No se creó el tag `store-builder-baseline-green-2026-07-29`: el criterio “sin errores JavaScript” no se cumple.

## Instalación desde cero

| Operación | Resultado | Duración observada |
|---|---|---:|
| `composer install --no-interaction --prefer-dist --no-progress` desde `composer.lock` | PASS; 119 instalaciones, 0 actualizaciones, 0 eliminaciones | 95.6 s |
| `npm ci --no-audit --no-fund` desde `package-lock.json` | PASS; 160 paquetes | incluido en 50.2 s con build |
| `npm run build` | PASS; manifest y CSS/JS generados | 4.62 s de Vite |

Advertencias no bloqueantes: Composer PHAR de desarrollo antiguo y `@import` CSS posterior a directivas Tailwind. No se ejecutó `composer update` ni `npm update`.

## Laravel y pruebas

- Laravel: `12.54.1`.
- `route:list`: PASS, 481 rutas.
- `view:clear`: PASS.
- `view:cache`: PASS.
- Entorno de pruebas: `APP_ENV=testing`, SQLite en memoria, cache/sesiones array, cola sync y correo array.

| Suite | Casos | Aserciones | Fallos |
|---|---:|---:|---:|
| `CatalogTemplatesManifestTest.php` | 4 | 26 | 0 |
| `PublicTemplateRuntimeTest.php` | 5 | 201 | 0 |
| `GlobalTemplateSettingsTest.php` | 2 | 35 | 0 |
| `StorefrontHomepageBuilderTest.php` | 9 | 109 | 0 |
| `StorefrontStructureV2Test.php` | 17 | 304 | 0 |
| `AdminResponsiveLayoutTest.php` | 4 | 44 | 0 |
| **Total** | **41** | **719** | **0** |

Las 32 advertencias que PHPUnit presenta corresponden a deprecaciones por anotaciones DocBlock del propio conjunto; no son fallos. La primera ejecución accidental con una clave de testing de longitud inválida se descartó como error de preparación y se repitió sin tocar código ni pruebas.

## Navegador limpio

Se creó exclusivamente una base SQLite temporal en `C:\Users\luich\AppData\Local\Temp\avan-store-builder-browser-20260729.sqlite`, se ejecutaron migraciones allí y se generaron un usuario, un proyecto, tres categorías y doce productos ficticios. No se copiaron `.env` ni credenciales, y no se accedió a datos reales.

Auditoría Chrome headless sobre 21 combinaciones:

- Login administrativo: HTTP 200 y autenticación correcta.
- Configuración, Plantillas y Constructor: HTTP 200 en 375×812, 768×1024 y 1440×1100.
- Drawer móvil: abre, bloquea scroll, muestra overlay, cierra y restaura foco.
- Aplicación de `computienda`, `ecommerce` y `direct`: tres respuestas HTTP 200 con clave/tema/URL pública correctos.
- Inicio público de cada plantilla: HTTP 200 en los tres breakpoints; marcador productivo correcto (`professionalStore()`, `ecStore()`, `store()`).
- Tienda pública de cada plantilla: HTTP 200.
- Overflow horizontal: 0 fallos.
- Recursos HTTP indispensables 404: 0.
- Imágenes rotas visibles: 0; los placeholders controlados no se contaron como imagen rota.
- Error JavaScript público: 0.
- Error JavaScript administrativo: 9/9 páginas.

### Bloqueante reproducido

```text
Uncaught TypeError: Cannot read properties of undefined (reading 'title')
at Proxy.open
at window.__confirm
```

Origen rastreado: `resources/views/layouts/app.blade.php`, modal global de confirmación. La expresión `x-init="window.__confirm = (opts) => open(opts)"` retorna una función; Alpine la ejecuta sin argumentos durante la inicialización y `open(opts)` intenta leer `opts.title`.

El árbol original contiene una modificación local amplia de ese layout que, entre muchos cambios ajenos, reemplaza el modal. No se incorporó porque seleccionar ese hunk alteraría funcionalidad y el Paso 6 ordena detenerse/documentar ante un defecto nuevo.

## Estado final del worktree

Después de Composer, npm, build, caché de vistas, pruebas, servidor temporal y navegador:

- `git status --short`: vacío.
- `git diff --check`: sin salida.
- Dependencias, build, cachés y sesiones: correctamente ignorados.
- Archivos fuente modificados por las herramientas: 0.
- Dependencia de archivos copiados manualmente: no.

## Seguridad y alcance

- Migraciones ejecutadas: únicamente sobre SQLite temporal.
- Migraciones sobre base real: ninguna.
- Datos reales modificados: ninguno.
- Archivos sin seguimiento eliminados: ninguno.
- Push: no.
- Tag: no.
- Despliegue: no.
- Fase 1: no iniciada.

## Dictamen

El repositorio ya demuestra instalación limpia, build, rutas, vistas y 41 pruebas con 719 aserciones. Sin embargo, el requisito explícito de navegador “sin errores JavaScript” falla en el shell administrativo. Por la regla del Paso 6, el estado es **ROJO** y **NO APTO** para autorizar la Fase 1.

Recomendación: aprobar un paso correctivo mínimo y separado para el modal global; después recrear otro worktree desde el nuevo HEAD, repetir instalación, seis suites y 21 comprobaciones de navegador. Solo si queda sin errores se debe crear el tag local green.
