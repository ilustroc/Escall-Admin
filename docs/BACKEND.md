# Convenciones backend

## Controllers

Los controladores sólo coordinan HTTP:

- reciben un `FormRequest`;
- invocan una Query o Service;
- retornan Inertia, redirect, JSON auxiliar o descarga.

No contienen SQL complejo, mapeos XLSX, loops de importación ni reglas extensas. Los errores recuperables de importación se registran y se convierten en flash `error`.

## Form Requests

Cada módulo tiene Requests en su propia carpeta: `Auth`, `Cargas`, `Listas`, `Tablas`, `Reportes` y `Expertis`. Los defaults de filtros se normalizan en `prepareForValidation`; fechas, formatos, tamaños y valores permitidos se validan antes del controlador.

## Services

Los Services representan operaciones con efectos secundarios:

- `AuthService`: login, regeneración e invalidación de sesión;
- `DataImportService`: importación CSV/XLSX transaccional;
- `GestionesSpService`: SP remoto, streaming, reemplazo transaccional y lotes;
- `RegistrarPagoService`: snapshot y rango de capital;
- Services de listas: actualización y eliminación;
- Services Expertis: preview/workflow, importadores, archivos privados y CSV de errores.

Cada Service tiene una responsabilidad concreta; no existe un Service genérico de aplicación.

## Queries

Las Queries implementan lecturas:

- `DashboardQuery`;
- `PagosLookupQuery`;
- `ListasQuery`;
- `GestionesTableQuery`, `PagosTableQuery`, `DataTableQuery`;
- `ReporteImpulseQuery`, `ReporteKpInvestQuery`, `ReporteCarterasQuery`;
- `ExpertisDashboardQuery`, `ExpertisReportQuery`, `ExpertisImportHistoryQuery`.

Seleccionan columnas necesarias, validan ordenamientos mediante listas permitidas y devuelven Builders, paginadores, colecciones pequeñas o resúmenes. `ExpertisReportQueryService` fue reemplazado por la convención Query compartida.

## Models y Support

Los Models conservan tabla, clave, fillable, casts y relaciones. La lógica de importación o reporte no vive en ellos. `CapitalRange` centraliza las etiquetas de rango que comparten gestiones y pagos.

## Exports

Pantalla y descarga reutilizan Queries. CSV usa respuesta streaming; los XLSX grandes usan OpenSpout, cursores o chunks. Los archivos Expertis originales permanecen en almacenamiento privado y sólo se descargan por rutas autenticadas.

## Errores y nombres

- Flash: `success`, `warning`, `error`, `info`.
- Clases y métodos describen el caso de uso en español cuando corresponden al dominio existente.
- Controllers terminan en `Controller`, Requests en `Request`, lecturas en `Query` y operaciones en `Service`.
- Las excepciones técnicas se registran; el usuario recibe un mensaje accionable sin detalles sensibles.
