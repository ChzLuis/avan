# scripts/modulos — mudanza de módulos a app/Modules/

Un script por módulo movido, como implementación de referencia para el siguiente.
Plan y orden: `docs/architecture/BIXO_MODULARIZACION_PLAN.md`.

Qué hace cada script:
1. `git mv` de controladores, modelos y vistas (conserva el historial).
2. Reemplazos de namespace y de `use`, **cada uno con el número exacto de
   apariciones esperado**: si no cuadra, se detiene antes de escribir ese
   archivo. Así un rename a medias no llega nunca al árbol.
3. Registro de lo que haga falta (el provider de vistas ya es genérico).

Por qué Python y no `sed`: los namespaces llevan barras invertidas y la capa del
shell del agente se las come; el script las construye con `chr(92)`.

Después de correrlo: `php -l` de todo lo tocado, `artisan route:list` sobre las
rutas del módulo, la misma batería de tests de antes de mover (debe dar el mismo
número), y un test propio del módulo que pinte sus pantallas con `assertViewIs`
y vigile la frontera (ver `tests/Feature/PersonasModuloTest.php`).

Para desplegar un módulo movido a ARIN hace falta, además de subir los archivos:
borrar los viejos y `composer dump-autoload -o` (ARIN tiene el classmap
optimizado). `deploy.py` no hace ninguna de las dos todavía.

## Desde Finanzas: plan generado + aplicador genérico

Para módulos grandes el script a mano no escala. El método actual:

1. `python scripts/modulos/plan_<modulo>.py` **lee el código** y escribe
   `plan_<modulo>.json`: movimientos, reemplazos con su conteo exacto (acotado
   por carácter de identificador), `use` que hay que insertar, renombrados de
   clase y una lista de "sospechosos" (nombres de vista sueltos) para revisar a
   ojo. `plan_crmbots.py` es la versión más completa (dos destinos a la vez, el
   namespace se deriva de la ruta destino de cada movimiento).
2. `python scripts/modulos/mover_modulo.py scripts/modulos/plan_<modulo>.json --dry`
   verifica TODOS los conteos sin tocar nada; sin `--dry` aplica (git mv,
   reemplazos, inserts, borra carpetas vacías).
3. `php -l`, `route:list` (0 rutas viejas), misma batería de tests que antes,
   guardián del módulo, quitar imports que el generador insertó para clases
   que solo se usan por FQCN.

Huecos que ya tiene resueltos el generador (no volver a caer): vistas elegidas
por ternario, `extends Controller` sin importar, renombrar la declaración
`class X extends` al renombrar el archivo (el ancla `class X ` falla con el
conteo acotado), tests que leen una vista **por ruta de disco**.

Novedades de `plan_tienda.py` (módulo 6): literales de vista fuera de `view()`
clasificados por contexto porque `public.*` es a la vez nombre de vista y de
ruta (se imprimen todas las decisiones y los "ruta" que no son ruta real);
renombrado de clase por nombre corto con el carácter previo (no pisa el FQCN
nuevo, que termina igual); componentes anónimos con prefijo `components.<x>`
para los `@include` por nombre; solo se renombran literales con punto (un
`'promotions'` suelto es una tabla, no una vista). Tras aplicar, correr
`python scripts/modulos/limpiar_imports.py scripts/modulos/plan_X.json` para quitar los
`use` que sobran de clases usadas solo por FQCN.

## Estado final (2026-09-17): mudanza completa

Generadores en orden de madurez: `plan_ventas.py` es la versión de
referencia (incluye el paso 3b: archivos de Core que comparten el namespace
viejo del movido y lo usan por nombre corto sin `use`; y la reescritura
automática de `resource_path('views/...')` en tests). `plan_client.py` y
`plan_operaciones.py` derivan de él. Los `use` de los pasos 3 y 3b se
deduplican (un `use` doble es fatal). Un guardián que concatene el prefijo
(`'mod::' . $vista`) hace que el generador doble el prefijo: los guardianes
llevan el nombre completo.

Los planes `plan_*.json` quedan como registro de qué se movió y qué se
reemplazó en cada paso; no se reaplican.
