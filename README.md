# ESCALL Admin

Panel administrativo de ESCALL Perú construido sobre Laravel 10. La interfaz principal y el módulo Expertis usan Vue 3 + Inertia, mientras los módulos Blade existentes continúan operativos durante la migración progresiva.

## Módulos

- Autenticación propia con sesiones Laravel.
- Carga legacy de gestiones mediante procedimiento almacenado.
- Carga de cartera y pagos actuales.
- Reportes legacy para Impulse, KP Invest y carteras.
- Expertis:
  - importación XLSX de gestiones y pagos;
  - detección de archivos y filas duplicadas;
  - historial y errores de importación;
  - tipificaciones y pesos;
  - tablas paginadas, filtros y exports;
  - reportes y dashboard con datos reales.

Los registros Expertis viven exclusivamente en `gestiones_expertis` y `pagos_expertis`. No se mezclan con `gestiones` ni `pagos`.

## Requisitos

- PHP 8.2 o superior compatible con Laravel 10.
- MariaDB 10.6 / MySQL.
- Composer 2.
- Node.js 18 o superior para compilar assets.
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

Configura las conexiones `mysql` y `sp` en `.env`. No uses una migración general en una base legacy sin revisar primero [la guía de despliegue](docs/DEPLOY_EXPERTIS.md).

Variables Expertis:

```dotenv
EXPERTIS_IMPORT_MAX_MB=50
EXPERTIS_IMPORT_CHUNK_SIZE=1000
EXPERTIS_GUARDAR_ARCHIVO_ORIGINAL=true
```

## Verificación

```bash
composer validate
php artisan test
php artisan route:list
php artisan migrate --pretend
npm run build
```

Las pruebas usan SQLite en memoria y crean únicamente el esquema mínimo requerido; no ejecutan `migrate:fresh`.

## Documentación

- [Arquitectura y reglas Expertis](docs/EXPERTIS.md)
- [Formatos de importación](docs/IMPORT_FORMATS.md)
- [Despliegue seguro](docs/DEPLOY_EXPERTIS.md)

Los XLSX ficticios se encuentran en `storage/app/examples`. Pueden regenerarse con:

```bash
php scripts/generate_expertis_examples.php
```
