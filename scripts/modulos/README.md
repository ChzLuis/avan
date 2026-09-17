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
