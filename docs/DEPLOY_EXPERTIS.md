# Despliegue seguro de Expertis

La base productiva contiene tablas creadas manualmente y puede tener migraciones legacy pendientes. No ejecutes `migrate:fresh`, `db:wipe` ni una migración general sin una auditoría previa.

## 1. Respaldo obligatorio

Antes de desplegar:

1. activa una ventana de mantenimiento;
2. genera un respaldo completo de MariaDB;
3. valida que el archivo pueda restaurarse;
4. conserva una copia fuera del servidor.

Ejemplo, adaptando credenciales fuera del historial del shell:

```bash
mysqldump --single-transaction --routines --triggers NOMBRE_BD > escall_pre_expertis.sql
```

## 2. Instalar código y dependencias

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan storage:link
```

`storage:link` expone únicamente `storage/app/public`. Los originales Expertis permanecen bajo `storage/app/private`.

Configura:

```dotenv
QUEUE_CONNECTION=database
EXPERTIS_IMPORT_MAX_MB=50
EXPERTIS_IMPORT_CHUNK_SIZE=1000
EXPERTIS_GUARDAR_ARCHIVO_ORIGINAL=true
EXPERTIS_QUEUE=expertis
EXPERTIS_JOB_TIMEOUT=1800
EXPERTIS_QUEUE_RETRY_AFTER=2100
EXPERTIS_STALE_MINUTES=15
```

`EXPERTIS_QUEUE_RETRY_AFTER` debe ser mayor que el timeout del Job.

## 3. Auditar migraciones

```bash
php artisan migrate:status
```

Confirma manualmente que existan `users`, `data`, `gestiones`, `pagos` y las tablas de tipificación legacy. La FK `importaciones_expertis.user_id` requiere `users`.

Revisa el SQL de cada migración nueva:

```bash
php artisan migrate --pretend --path=database/migrations/2026_07_26_000001_create_asignaciones_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_23_000001_create_importaciones_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_23_000002_create_tipificaciones_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_23_000003_create_gestiones_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_23_000004_create_pagos_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_23_000005_create_errores_importacion_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_27_000001_add_queue_tracking_to_importaciones_expertis_table.php
php artisan migrate --pretend --path=database/migrations/2026_07_27_000002_create_queue_tables.php
```

No se modifica la migración antigua `2026_01_05_220129_create_pagos_table.php`, porque algunos entornos ya tienen `pagos` manual y otros la registraron como ejecutada.

## 4. Ejecutar solo migraciones Expertis

Respeta el orden:

```bash
php artisan migrate --force --path=database/migrations/2026_07_23_000001_create_importaciones_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_23_000002_create_tipificaciones_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_23_000003_create_gestiones_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_23_000004_create_pagos_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_23_000005_create_errores_importacion_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_26_000001_create_asignaciones_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_27_000001_add_queue_tracking_to_importaciones_expertis_table.php
php artisan migrate --force --path=database/migrations/2026_07_27_000002_create_queue_tables.php
```

La migración de asignaciones se ejecuta solo después de revisar el SQL de `--pretend`.
Si las cinco tablas Expertis base ya existen, el único comando nuevo de producción es:

```bash
php artisan migrate --force --path=database/migrations/2026_07_26_000001_create_asignaciones_expertis_table.php
```

Carga el catálogo idempotente:

```bash
php artisan db:seed --force --class=TipificacionesExpertisSeeder
```

## 5. Permisos

El usuario del servidor web necesita escritura:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Ajusta `www-data` al usuario real del hosting. Verifica en especial la creación de:

```text
storage/app/private/expertis/gestiones/YYYY/MM
storage/app/private/expertis/pagos/YYYY/MM
storage/app/private/expertis/asignaciones/YYYY/MM
storage/app/private/expertis/imports/asignaciones
storage/app/private/expertis/prepared
```

## 6. Worker de asignaciones

Después de migrar, mantén un worker permanente. Con Supervisor usa el ejecutable PHP y la
ruta del proyecto que existan realmente en el servidor:

```bash
php artisan queue:work database --queue=expertis --sleep=1 --timeout=1800 --tries=1
```

Reinícialo después de cada despliegue:

```bash
php artisan queue:restart
```

Si cPanel no ofrece Supervisor, configura un Cron Job por minuto con esta plantilla; no
copies rutas ficticias:

```text
* * * * * RUTA_PHP RUTA_PROYECTO/artisan queue:work database --queue=expertis --once --timeout=1800 --tries=1
```

Descubre `RUTA_PHP` y `RUTA_PROYECTO` desde el propio hosting. Comprueba periódicamente:

```bash
php artisan expertis:imports:recover-stale --minutes=15 --dry-run
php artisan expertis:prepared:cleanup --days=7 --dry-run
```

## 7. Hosting sin Node.js

Compila en una máquina compatible:

```bash
npm ci
npm run build
```

Despliega el directorio generado `public/build` junto al código. No ejecutes npm en producción. El manifiesto y los nombres hash deben pertenecer al mismo commit desplegado.

## 8. Verificación posterior

```bash
php artisan migrate:status
php artisan route:list --name=expertis
php artisan optimize:clear
```

Comprueba con un usuario autenticado:

1. dashboard y login;
2. vista previa de ambos XLSX ficticios;
3. importación de gestiones;
4. reimportación del mismo archivo como duplicado;
5. importación de pagos;
6. filtros, `UNICO`, estado y exports;
7. descarga autenticada del original y de errores;
8. carga por SP y reportes legacy.
9. subida de una asignación, progreso hasta `listo_para_importar` y confirmación.
10. worker activo, heartbeat y reintento desde el detalle.

## 9. Rollback Expertis

El rollback elimina datos Expertis. Antes de ejecutarlo, toma otro respaldo y detén importaciones. Revierte en orden inverso:

```bash
php artisan migrate:rollback --force --path=database/migrations/2026_07_26_000001_create_asignaciones_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_27_000002_create_queue_tables.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_27_000001_add_queue_tracking_to_importaciones_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_23_000005_create_errores_importacion_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_23_000004_create_pagos_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_23_000003_create_gestiones_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_23_000002_create_tipificaciones_expertis_table.php
php artisan migrate:rollback --force --path=database/migrations/2026_07_23_000001_create_importaciones_expertis_table.php
```

No incluyas migraciones legacy en estos comandos. Restaurar el respaldo es el plan de recuperación si el rollback no corresponde al estado manual real de producción.
