# BIXO — guia de entrada para agentes

Plataforma SaaS multiempresa (Laravel + Blade + Alpine + Spatie teams por
`project_id`). Empresa: Eskala. Producto: BIXO. Produccion: VPS ARIN
(`deploy.py`, con puerta de deriva).

## Lee esto antes de tocar codigo

1. `docs/architecture/BIXO_HANDOFF.md` — donde quedo la ultima sesion
2. `docs/architecture/BIXO_CURRENT_STATE.md` — estado y cifras medidas
3. `docs/architecture/BIXO_MASTER_CHECKLIST.md` — que esta hecho y que sigue
4. `docs/architecture/BIXO_DECISIONS.md` — decisiones que no se revierten en silencio
5. `docs/architecture/MODULE_OWNERSHIP.md` — dueño de cada entidad
6. `docs/architecture/BIXO_RESTRUCTURE_ROADMAP.md` — fases

## Reglas que no se rompen

- **Una capacidad, un propietario.** Antes de crear un modelo/controlador/tabla,
  consulta `MODULE_OWNERSHIP.md`. Si la entidad existe, reutilizala.
- **Tenant primero.** Toda entidad de negocio filtra por `project_id`; los
  modelos nucleares usan `HasProjectScope`. Nunca asumas que ocultar un menu
  es seguridad. Matriz: `docs/security/BIXO_TENANT_ISOLATION.md`.
- **Permisos por verbo:** `*.ver` jamas autoriza escribir. Escrituras de
  ajustes van con `settings.*|manage-settings`.
- **No commitear trabajo ajeno:** el arbol suele tener archivos de otras
  sesiones sin commitear; commitea solo lo tuyo, por ruta explicita.
- **No tocar:** `whatsbot/`, `.env`, `storage/`, `vendor/`, pedidos 20/21/22.
- Comentarios Blade dentro de `@php` o `<style>` rompen las tiendas (500).
- Clases Tailwind nuevas requieren `npm run build`; verifica contra
  `public/build/assets/app-*.css` antes de usarlas.

## Comandos

- Tests: `c:/xampp/php/php.exe artisan test` (874 en verde es la linea base)
- Deploy: `python deploy.py <archivos>` (exit 3 = deriva detectada: comparar, no forzar)
- SQL produccion: `python deploy.py --sql "..."`

## Idioma

Todo en español: commits, comentarios, documentacion y respuestas.
