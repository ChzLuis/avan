# app/Modules — un dominio, una carpeta

Cada carpeta es un **dominio** del negocio y contiene todo lo que le pertenece:
controladores, modelos, soporte y vistas. Los productos comerciales (BIXO
Sales, Commerce, Operations, Finance, ARIN) **agrupan** dominios; no son
carpetas por sí mismos, porque un producto usa varios dominios y un dominio
sirve a varios productos.

Quién es dueño de cada entidad: `docs/architecture/MODULE_OWNERSHIP.md`.
Cómo y en qué orden se mueve el código: `docs/architecture/BIXO_MODULARIZACION_PLAN.md`.

## Reglas

1. **Una capacidad, un propietario.** Antes de crear un modelo, controlador o
   tabla, busca si ya existe en algún módulo. Si existe, se reutiliza.
2. **Los nombres dicen qué hace la cosa**, no quién la encargó ni con qué
   tecnología se hizo. Excepción: las integraciones llevan el nombre del
   proveedor (`WooSync`, `ApisPeru`), porque ahí el proveedor es la función.
3. **Bots, IA y automatizaciones viven solo en `Crm/` y `Bots/`.** Si un archivo
   fuera de ahí menciona `graph.facebook`, `WaCanal`, `FlowRunner` o un
   proveedor de IA, está mal colocado. Los demás dominios anuncian hechos
   («pedido enviado»); el CRM decide si eso genera un mensaje.
4. **Tenant primero.** Toda entidad de negocio filtra por `project_id`.
5. Un módulo puede llamar a los casos de uso públicos de otro; **no** duplica
   sus tablas ni su vocabulario de estados.

## Estado

Estructura creada 2026-09-17. Mudanza en curso, un módulo por commit, en el
orden del plan y solo con el árbol de git limpio y la suite explicada:

| # | Módulo | Estado | Guardián |
|---|--------|--------|----------|
| 1 | Personas | movido 2026-09-17 | `PersonasModuloTest` |
| 2 | Control | movido 2026-09-17 | `ControlModuloTest` |
| 3 | Inventario | movido 2026-09-17 | `InventarioModuloTest` |
| 4 | Finanzas | movido 2026-09-17 | `FinanzasModuloTest` |
| 5 | Crm + Bots | movidos 2026-09-17 | `CrmModuloTest`, `BotsModuloTest` |
| 6 | Tienda | movido 2026-09-17 | `TiendaModuloTest` |
| 7 | Catalogo | movido 2026-09-17 (incluye `ImportLog` y los conectores) | `CatalogoModuloTest` |
| 8 | Ventas | movido 2026-09-17 | `VentasModuloTest` |
| + | Client → Crm | movido 2026-09-17 (MODULE_OWNERSHIP: el cliente es del CRM) | `CrmModuloTest` |
| + | Operaciones | movido 2026-09-17 (agenda, mesas, reservas, delivery, mapa, lavandería) | `OperacionesModuloTest` |

**Mudanza completa (2026-09-17).** En Core quedan solo el shell y lo
transversal: autenticación, Workspace, Ajustes (`SettingsController`, con el
CRUD de cupones como deuda), Proyectos, Perfil, Sedes, la cara del portal
comercial (`Comercial\{Auth,Dashboard}`, `comercial/{layouts,login,dashboard,
panel,monitoreo,caja}`), `Project`/`User`/`Employee`/`Module`/`Sede`,
`Imagen/`, `LineMath`, `RichText`, `ConsultaDocumento`, `BusquedaGlobal`,
`AvisosPortal`, `ModulosPortal`, `components/doc/*`. `ExternalRequest` e
`InternalRequest` no los usa nadie: candidatos a retirar.

Lo movido **no está desplegado**: ARIN tiene el classmap optimizado. Receta
para desplegar todo junto (una sola ventana, con las tiendas vigiladas):

1. Cola vacía (`jobs` = 0): los jobs se encolan por nombre de clase.
2. Subir `app/Modules/**`, `app/` restante, `resources/views/**`, `routes/`,
   `bootstrap/providers.php`, `tests/`.
3. **Borrar en ARIN** todos los archivos viejos (la lista exacta: `git diff
   --name-status --diff-filter=RD <commit-anterior>..HEAD | grep -v Modules`).
4. `composer dump-autoload -o`, `php artisan view:clear`, `route:cache`,
   `config:cache`.
5. `curl` a la portada y a un producto de 2 tiendas, `/bixoadmin/settings/
   builder`, `/bixosales/pedidos`, `/bixocrm`, `/api/whatsapp/webhook` (403).
6. `deploy.py` no hace ni el borrado ni el dump-autoload: añadirle un modo
   `--modulos` antes de este despliegue.

El namespace ya resuelve: `App\Modules\Crm\Controllers\...` carga sin tocar
`composer.json` (PSR-4 `App\` → `app/`).
