# Comparación de consultas del Store Builder — Fase 1A

Fecha: 2026-07-29. Medición con `DB::listen()` sobre un checkout limpio y una base SQLite temporal con datos ficticios. Los tiempos son orientativos de una ejecución local con cachés frías; no son un benchmark.

| Escenario | Antes | Después | Duplicadas antes | Duplicadas después |
|---|---:|---:|---:|---:|
| Diseñador — Plantillas | 35 | 28 | 24 | 7 |
| Diseñador — Constructor | 193 | 26 | 182 | 7 |
| Ecommerce — Inicio | 39 | 15 | 14 | 0 |
| Ecommerce — Tienda | 39 | 15 | 14 | 0 |
| Direct — Inicio | 26 | 15 | 3 | 0 |
| Direct — Tienda | 26 | 15 | 3 | 0 |
| CompuTienda — Inicio | 27 | 15 | 4 | 0 |
| CompuTienda — Tienda | 27 | 15 | 4 | 0 |

La reducción total de las ocho muestras es de 412 a 144 consultas (−268; 65,0 %). Las vistas públicas oficiales eliminan todas las consultas duplicadas registradas. Las siete duplicadas restantes del Diseñador corresponden a ocho comprobaciones repetidas de módulos del shell administrativo, fuera del contrato de datos del Store Builder.

## Qué se eliminó

- Lecturas por campo mediante `Project::setting()` en el Diseñador y QR.
- Nuevos `pluck()` de `project_settings` desde Blade.
- Cargas de productos, servicios y categorías desde los componentes Blade.
- Consulta de testimonios desde Ecommerce.
- Reconsultas de navegación/páginas cuando las relaciones ya están cargadas.
- Doble preparación independiente del contexto y de las variables heredadas.

## Cargas actuales por contexto

- Ajustes: una colección indexada.
- Secciones: una carga por contexto/página.
- Menús e items: eager loading de raíces e hijos.
- Páginas: una carga.
- Pop-up: una carga.
- Plantilla: una resolución.
- Catálogo: solo cuando la vista lo solicita; imágenes y relaciones requeridas se precargan.

No se añadió caché persistente. El aislamiento entre proyectos está cubierto por pruebas y cada construcción usa exclusivamente el proyecto recibido.
