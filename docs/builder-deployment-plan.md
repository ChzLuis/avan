# Constructor guiado — Plan y registro de despliegue

## Procedimiento del proyecto (existente, verificado)
- Subida SFTP con `deploy_merge.py` (LOCAL = worktree avan-prodsnap; verifica `php -l` en el servidor tras subir) y `deploy.py --run` para comandos remotos.
- Servidor: CloudPanel, PHP-FPM 8.1-8.5 (**OPcache requiere `systemctl reload phpX.Y-fpm` tras cambiar PHP**).
- Caches Laravel: `php artisan view:clear` (+ `route:clear` si cambian rutas, `cache:clear` si aplica).
- Backup DB: `mysqldump` antes de migrar.

## Pasos de este despliegue
1. ✅ Pruebas locales: suite completa 289 tests (16E/16F = línea base preexistente, 0 regresiones) + suite Builder 29/29.
2. Backup de BD (`mysqldump arindg > backup_builder_YYYYmmdd_HHMM.sql.gz`).
3. Subir los archivos de la rama (app/, routes/, resources/views/{builder,templates,layouts}, migración).
4. `php artisan migrate --force` (solo crea `builder_drafts` y `store_publications`; reversible con `down()`).
5. `php artisan route:clear && php artisan view:clear`.
6. Reload PHP-FPM (8.1→8.5).
7. Health: `/login` 200, panel 302.
8. Builder: `/bixoadmin/settings/builder` → 404 con flag apagado (por defecto en todas las tiendas).
9. Tiendas públicas: 7/7 en 200 + snapshots normalizados == MANIFEST base (byte-idéntico tras normalizar).
10. Registrar resultado abajo.

## Activación (post-deploy, por tienda)
```bash
php artisan tinker --execute="\App\Storefront\BuilderAccess::setMode(\App\Models\Project::find(ID), 'beta');"
```
`beta` = solo dueño/superadmin. Rollback inmediato: `'disabled'` (no toca datos).

## Rollback del despliegue
- Código: restaurar desde `_release_backups/<stamp>/files/` (el deploy respalda cada archivo antes de escribir) + reload FPM.
- Migración: `php artisan migrate:rollback --step=1` (solo dropea las 2 tablas nuevas).
- Publicaciones hechas con el builder: `BuilderDraftService::rollback($project, $version)` restaura el estado público previo.

## Registro de ejecución — 04/08/2026
1. Suite 289 tests (16E/16F = base) + Builder 29/29 ✓
2. Backup `/root/backup_builder_20260804_1424.sql.gz` (111K, Dump completed) ✓
3. 27 archivos subidos (deploy_merge, sintaxis OK) ✓
4. `migrate --force` → tablas `builder_drafts` y `store_publications` confirmadas en MySQL ✓
5. route:clear + view:clear ✓
6. Reload FPM 8.1-8.5 ✓
7. login 200 ✓
8. builder 302 anónimo (404 autenticado con flag off, cubierto por tests) ✓
9. 7/7 tiendas 200 · snapshots normalizados **14/14 idénticos** a la base · log sin errores ✓
10. TECSIST (proyecto 18) → `builder_mode=beta` ✓
