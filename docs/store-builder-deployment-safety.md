# Seguridad de despliegue, respaldo y rollback

Este documento prepara comandos y controles para una fase futura. **No se ejecutó ningún respaldo, restauración, migración, rollback, limpieza de caché ni operación sobre producción.** No contiene rutas, usuarios ni contraseñas reales.

## Condiciones previas obligatorias

1. Confirmar proyecto, servidor, ambiente y ventana de mantenimiento.
2. Resolver o preservar explícitamente el trabajo no confirmado del repositorio.
3. Registrar el commit desplegable y generar un tag o rama de recuperación.
4. Verificar espacio libre en `<BACKUP_PATH>`.
5. Obtener las credenciales mediante un mecanismo seguro; no escribir contraseñas en el comando ni en el historial.
6. Crear respaldo de base y archivos antes de ejecutar migraciones.
7. Probar la restauración en un ambiente aislado.
8. Ejecutar pruebas y vista previa antes de habilitar cambios públicos.

## Respaldo de base de datos

Ejemplo MySQL/MariaDB, preparado pero no ejecutado:

```powershell
mysqldump --single-transaction --routines --triggers --events --databases <DATABASE_NAME> > <BACKUP_PATH>\database.sql
```

La autenticación debe solicitarse de forma interactiva o provenir de un archivo de opciones protegido. Antes de aceptar el respaldo:

```powershell
Get-Item -LiteralPath <BACKUP_PATH>\database.sql
Get-FileHash -Algorithm SHA256 -LiteralPath <BACKUP_PATH>\database.sql
```

Validar además que el archivo contenga cabecera, selección de base y sentencias de creación/datos esperadas, sin imprimir secretos.

## Respaldo de archivos

Preparación genérica sin asumir la herramienta disponible en producción:

```powershell
Compress-Archive -LiteralPath <PROJECT_PATH> -DestinationPath <BACKUP_PATH>\project-files.zip
Get-FileHash -Algorithm SHA256 -LiteralPath <BACKUP_PATH>\project-files.zip
```

Si el proyecto incluye archivos subidos fuera de `<PROJECT_PATH>`, deben inventariarse y respaldarse por separado. No incluir el archivo `.env` en artefactos compartidos; si se respalda, debe cifrarse y mantenerse con acceso restringido.

## Restauración de archivos

La restauración debe hacerse en un directorio temporal, validarse y luego intercambiarse de manera controlada:

```powershell
Expand-Archive -LiteralPath <BACKUP_PATH>\project-files.zip -DestinationPath <RESTORE_PATH>
Get-FileHash -Algorithm SHA256 -LiteralPath <BACKUP_PATH>\project-files.zip
```

No sobrescribir directamente `<PROJECT_PATH>` hasta comprobar permisos, enlaces de storage, `.env`, dependencias y contenido del archivo restaurado.

## Restauración de base de datos

Preparación MySQL/MariaDB:

```powershell
mysql <DATABASE_NAME> < <BACKUP_PATH>\database.sql
```

Debe realizarse primero en una base aislada. En producción requiere ventana aprobada, respaldo reciente y verificación posterior de conteos, claves foráneas y acceso por proyecto.

## Rollback futuro de migraciones

Antes de migrar:

```powershell
php artisan migrate:status
php artisan migrate --pretend
```

Después de autorización y respaldo, el rollback debe limitarse al batch o número conocido de pasos:

```powershell
php artisan migrate:rollback --step=<N>
```

No usar `migrate:fresh`, `db:wipe` ni un rollback sin confirmar el batch. Las migraciones de datos necesitan un plan inverso explícito; revertir el esquema no garantiza recuperar datos eliminados o transformados.

## Limpieza y reconstrucción de caché

Después de un despliegue autorizado:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Si alguna ruta utiliza closures, `route:cache` debe validarse antes. En la línea base actual, config y rutas no estaban cacheadas; eventos y vistas sí.

## Recuperación del commit base

Commit registrado: `e788ee3`. Como el árbol actual está muy modificado, no se debe ejecutar `reset --hard` ni `checkout --`.

Recuperación segura en un clon o worktree nuevo:

```powershell
git clone <REPOSITORY_URL> <RECOVERY_PATH>
git -C <RECOVERY_PATH> switch --detach e788ee3
git -C <RECOVERY_PATH> switch -c recovery/store-builder-e788ee3
```

Si se trabaja en el repositorio actual, primero debe existir respaldo y una decisión explícita sobre los cambios sin seguimiento. No asumir que `git stash` captura todo sin verificar opciones y tamaño.

## Secuencia de despliegue propuesta

1. Congelar y registrar la versión candidata.
2. Ejecutar pruebas unitarias/feature en un clon limpio.
3. Respaldar base y archivos; verificar hashes.
4. Activar modo mantenimiento si corresponde.
5. Desplegar código sin sobrescribir `.env` ni contenido persistente.
6. Instalar dependencias con lockfiles.
7. Ejecutar `migrate --pretend`, revisar y luego migrar con autorización.
8. Limpiar/reconstruir cachés.
9. Ejecutar smoke tests por dominio y por proyecto.
10. Validar Inicio, Tienda, Nosotros, Contacto, Blog, menú, footer, QR y cambio de plantilla.
11. Habilitar vista pública solo después de validar la vista previa.
12. Ante fallo, retirar tráfico, restaurar archivos/base según el alcance y documentar el incidente.

## Criterios de rollback

Iniciar rollback si existe cualquiera de estos casos:

- HTTP 500 en una plantilla productiva.
- Pérdida de aislamiento entre proyectos.
- cambio de plantilla que no corresponde al proyecto.
- menú, carrito o checkout inaccesible.
- migración incompleta o datos inconsistentes.
- aumento severo de consultas o tiempos de respuesta.
- fallo en rutas públicas, dominios personalizados o almacenamiento de imágenes.
