# Arquitectura de la aplicación

## Visión general

ESCALL Admin usa una arquitectura única para los módulos operativos y Expertis:

```text
Navegador
  → ruta autenticada
  → FormRequest
  → Controller
  → Query (lectura) o Service (caso de uso)
  → Inertia / JSON auxiliar / descarga
  → página Vue dentro de AppLayout
```

`resources/views/app.blade.php` sólo contiene el documento HTML base, `@vite`, `@inertiaHead` y `@inertia`. No contiene navegación ni contenido de módulos. Los correos siguen siendo documentos Blade porque no forman parte de la interfaz administrativa.

## Organización por capas

- `routes/*.php`: rutas separadas por módulo, cargadas desde `routes/web.php`.
- `app/Http/Controllers`: recibe Requests y devuelve Inertia, redirects, JSON auxiliar o archivos.
- `app/Http/Requests`: normaliza y valida la entrada.
- `app/Queries`: lecturas reutilizables, indicadores, filtros y paginación server-side.
- `app/Services`: autenticación, importaciones, escritura, archivos y workflows.
- `app/Exports`: formatos de exportación y streaming.
- `app/Models`: mapeo de tablas, casts y relaciones.
- `app/Support`: utilidades pequeñas tipadas, como rangos de capital.
- `resources/js`: frontend Vue compartido.

No se usa Repository Pattern. Las Queries trabajan con Eloquent o Query Builder y los Services implementan casos de uso concretos.

## Rutas e Inertia

`routes/web.php` carga `auth.php`, `dashboard.php`, `cargas.php`, `listas.php`, `tablas.php`, `reportes.php` y `expertis.php`. Todas las pantallas autenticadas están dentro de middleware `auth`; login usa `guest`.

Las URLs y nombres existentes se conservaron. Los endpoints auxiliares de lookup y preview pueden responder JSON. Las exportaciones devuelven archivos. Todas las demás rutas de pantalla terminan en `Inertia::render()`.

## Importaciones

### DATA

CSV usa lectura streaming; XLSX usa lectura por chunks. Ambos normalizan encabezados, requieren `codigo` y `dni`, escriben mediante `upsert` por lotes y ejecutan la escritura dentro de una transacción. Los archivos CSV temporales se eliminan en `finally`.

### Gestiones por SP

`GestionesSpService` mantiene la conexión `sp`, la llamada a `sp_gestiones`, consultas unbuffered, consumo de rowsets, normalización y lotes de 2.000 filas.

La importación conserva la regla histórica de reemplazar el rango: elimina las gestiones locales del rango y vuelve a insertarlas. Ahora ambas operaciones viven en la misma transacción local; si falla la lectura o inserción, el borrado se revierte.

### Expertis

`ExpertisImportWorkflowService` centraliza preview, token temporal, archivo privado, expiración y selección del importador. Los importadores específicos conservan hash de archivo/fila, deduplicación, enriquecimiento, auditoría y errores.

## Reportes y exportaciones

Los reportes Impulse, KP Invest, carteras y Expertis comparten sus filtros entre pantalla y exportación mediante Queries. Los XLSX grandes usan cursores, chunks u OpenSpout para evitar materializar todo el conjunto en memoria.

## Pruebas y seguridad de datos

Las pruebas construyen tablas SQLite en memoria y nunca usan la conexión productiva. El procedimiento almacenado se simula en pruebas funcionales. La validación de despliegue usa `php artisan migrate --pretend`; no se debe ejecutar `migrate:fresh`, `db:wipe`, `DROP TABLE` ni `TRUNCATE` sobre el entorno productivo.
