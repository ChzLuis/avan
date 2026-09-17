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

Estructura creada 2026-09-17. **Las carpetas están vacías a propósito**: el
código se muda módulo a módulo, en el orden del plan, y solo con el árbol de
git limpio y la suite explicada. Hasta entonces el código sigue en
`app/Http/Controllers/`, `app/Models/`, etc.

El namespace ya resuelve: `App\Modules\Crm\Controllers\...` carga sin tocar
`composer.json` (PSR-4 `App\` → `app/`).
