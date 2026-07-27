# ESCALL Admin

Panel administrativo de ESCALL Perú construido con Laravel 10, Vue 3, Inertia 2 y Tailwind
CSS 3. Toda la interfaz administrativa usa el mismo frontend SPA, layout y navegación.
Blade se conserva únicamente como documento raíz de Inertia y para documentos no
interactivos, como correos.

## Módulos

- autenticación por sesiones Laravel;
- dashboard unificado con indicadores operativos y Expertis;
- carga de gestiones mediante el procedimiento almacenado remoto;
- importación por lotes de cartera DATA en CSV/XLSX;
- registro de pagos con snapshot de cuenta;
- listas editables, filtros, paginación y exportación;
- tablas analíticas de gestiones, pagos y cartera;
- reportes Impulse, KP Invest, carteras y TEC Center;
- Expertis:
  - importación XLSX de asignaciones mensuales, gestiones y pagos;
  - upsert de asignaciones por periodo, empresa y código;
  - detección de archivos y filas duplicadas;
  - historial y errores de importación;
  - tipificaciones y pesos;
  - tablas paginadas, filtros y exportaciones;
  - reportes y dashboard con datos reales.

Los registros Expertis viven exclusivamente en `asignaciones_expertis`,
`gestiones_expertis` y `pagos_expertis`. No se mezclan con `data`, `gestiones` ni `pagos`,
pero ambos grupos de módulos comparten la misma arquitectura.

## Arquitectura

El backend separa:

- `Controllers`: orquestación HTTP e Inertia;
- `FormRequests`: autorización y validación;
- `Services`: casos de uso con efectos secundarios;
- `Queries`: lecturas, filtros, agregaciones y paginación;
- `Exports`: descargas en streaming o por lotes;
- `Models`: relaciones, casts y persistencia.

El frontend inicia siempre desde `resources/js/app.js`. `AppLayout.vue` es el único layout
autenticado y `GuestLayout.vue` se usa para login. La navegación está centralizada en
`resources/js/Config/navigation.js`.

Consulta [Arquitectura](docs/ARCHITECTURE.md), [Frontend](docs/FRONTEND.md) y
[Backend](docs/BACKEND.md).

## Requisitos

- PHP 8.2 o superior compatible con Laravel 10.
- MariaDB 10.6 / MySQL.
- Composer 2.
- Node.js 18 o superior.
- Extensiones PHP habituales de Laravel, `zip`, `xml`, `mbstring` y `fileinfo`.

## Instalación local

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
npm run build
php artisan optimize:clear
php artisan storage:link
```

Configura las conexiones `mysql` y `sp` en `.env`. No ejecutes migraciones generales en
una base preexistente sin revisar [la guía de despliegue](docs/DEPLOY_EXPERTIS.md).

Variables Expertis:

```dotenv
EXPERTIS_IMPORT_MAX_MB=50
EXPERTIS_IMPORT_CHUNK_SIZE=1000
EXPERTIS_GUARDAR_ARCHIVO_ORIGINAL=true
```

La vista previa de asignaciones recorre el XLSX completo por streaming, conserva solo 20
filas para pantalla y valida un único periodo `YYYYMM`. La carga usa lotes de 1000 por
defecto y upsert por `periodo + empresa + codigo_normalizado`.

## Verificación

```bash
composer validate --strict
php artisan test
php artisan route:list
php artisan migrate --pretend --path=database/migrations/2026_07_26_000001_create_asignaciones_expertis_table.php
npm run build
npm audit --omit=dev
```

Las pruebas usan SQLite en memoria y crean el esquema mínimo requerido. No consultan la
base real, no ejecutan `migrate:fresh` y no necesitan seeders productivos. El benchmark
aislado de 40 000 filas se ejecuta con:

```powershell
$env:RUN_LARGE_IMPORT='1'
php artisan test --group=large-import
Remove-Item Env:RUN_LARGE_IMPORT
```

## Documentación adicional

- [Arquitectura y reglas Expertis](docs/EXPERTIS.md)
- [Formatos de importación](docs/IMPORT_FORMATS.md)
- [Despliegue seguro](docs/DEPLOY_EXPERTIS.md)

Los XLSX ficticios de gestiones y pagos en `storage/app/examples` se regeneran con:

```bash
php scripts/generate_expertis_examples.php
```

La plantilla de asignaciones se genera bajo demanda desde la pantalla de carga y contiene
tres registros completamente ficticios.
