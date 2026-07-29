# Arquitectura Expertis

## Alcance

Expertis mantiene sus datos aislados del flujo operativo actual, pero comparte la misma arquitectura Vue, Inertia, Controllers, FormRequests, Services y Queries. Conserva la autenticación por sesión y no escribe en `data`, `gestiones`, `pagos` ni en las tablas de tipificación actuales.

## Capas

- `app/Http/Controllers/Expertis`: endpoints autenticados, paginación, descargas y respuestas Inertia.
- `app/Http/Requests/Expertis`: validación de XLSX y CRUD de tipificaciones.
- `app/Services/Expertis`:
  - `ExpertisSpreadsheetService`: lectura streaming con OpenSpout;
  - `NormalizadorExpertisService`: encabezados, documentos, fechas, horas, montos y hashes;
  - importadores de gestiones y pagos: lotes, transacciones y auditoría;
  - `ExpertisImportTemplateService`: plantillas XLSX oficiales para ambas importaciones;
  - registro manual de pagos: la misma normalización, hash y trazabilidad que el XLSX;
  - `ExpertisImportWorkflowService`: preview tradicional de gestiones/pagos y despacho
    asíncrono de asignaciones;
  - `AsignacionExpertisPreparationService`: lectura única del XLSX y bloques JSONL;
  - `AsignacionExpertisImportService`: upsert desde bloques preparados;
- `app/Jobs/Expertis`: preparación e importación en la cola `expertis`;
- `app/Queries/Expertis`:
  - `ExpertisReportQuery`: filtros, `UNICO`, pagos válidos y agregaciones;
  - `ExpertisDashboardQuery` e `ExpertisImportHistoryQuery`: indicadores e historial.
- `app/Models`: relaciones de importaciones, errores, tipificaciones, gestiones y pagos.
- `resources/js`: layout, componentes y páginas Vue 3.

## Tablas

### `importaciones_expertis`

Audita usuario, tipo, archivo, SHA-256, estado, contadores, rango de fechas, resumen y
error general. También conserva `heartbeat_at`, `queued_at`, fase, intentos y progreso. La
restricción única `tipo + hash_archivo` impide procesar dos veces el mismo archivo.

### `errores_importacion_expertis`

Guarda fila, campo, mensaje y valores originales. Se elimina en cascada únicamente cuando se revierte o elimina su importación.

### `tipificaciones_expertis`

Catálogo independiente con categoría de gestión, peso, observación y estado activo.

### `gestiones_expertis`

Conserva el código visible, la clave normalizada, datos de gestión, tipificación, monto y `hash_fila` único.

### `pagos_expertis`

Conserva cuenta visible, clave normalizada, monto y `hash_fila` único. Un pago no contiene un identificador de transacción provisto por Expertis.

## Normalización

- Los encabezados ignoran mayúsculas, tildes, guiones bajos y espacios repetidos.
- En asignaciones, el documento se mantiene como texto; los DNI numéricos de hasta
  ocho dígitos se completan con ceros a la izquierda.
- La clave de cruce elimina ceros iniciales solo de la parte documental.
- Teléfonos conservan únicamente dígitos.
- Fechas aceptan valores de Excel, `dd/mm/yyyy`, `yyyy-mm-dd` y esos mismos formatos con hora.
- Montos aceptan símbolos de moneda y separadores decimales comunes.
- Textos de clasificación se almacenan en mayúsculas.
- El literal `NULL` en un campo opcional se almacena como valor nulo.

## Duplicados

### Archivo

Se calcula SHA-256 del XLSX original. La combinación de tipo y hash es única.
Solo los estados completados bloquean el mismo hash. Un intento fallido puede reintentarse y
reutiliza la misma fila de auditoría, mientras que un proceso activo reciente queda protegido
contra ejecuciones concurrentes.

### Gestión

El SHA-256 estable usa:

- código normalizado;
- teléfono;
- fecha y hora;
- asesor;
- campaña;
- nivel 1 y nivel 2;
- medio de gestión.

Si faltan hora u otros campos críticos, también incorpora la observación normalizada. No usa importación, timestamps ni número de fila.

Cuando el hash ya existe:

- completa nombre, compromiso, monto, observación, asesor, equipo, campaña o medio si el valor nuevo aporta información;
- cuenta como actualizada si cambió;
- cuenta como duplicada si no cambió.

Los lotes usan transacciones cortas, `insertOrIgnore` y `upsert`. Nunca se aplica `delete + insert`.

### Pago

El hash usa cuenta normalizada, fecha, monto a dos decimales, ejecutivo, tipo de acuerdo y recaudo.

Dos pagos completamente idénticos son indistinguibles porque el archivo no incluye un identificador único de transacción. El segundo se contabiliza como duplicado.

El registro manual usa exactamente esa misma regla. Cada intento queda en
`importaciones_expertis` con usuario, fecha y `origen = manual`, pero sin crear un archivo
ficticio ni habilitar una descarga inexistente.

## Peso y `UNICO`

El peso procede de `tipificaciones_expertis`. Una tipificación desconocida se importa con relación nula, se muestra sin peso y se ordena internamente como `999999`.

La consulta aplica:

```sql
ROW_NUMBER() OVER (
    PARTITION BY codigo_normalizado
    ORDER BY
        COALESCE(peso, 999999) ASC,
        fecha_hora DESC,
        id DESC
)
```

Solo `ranking_unico = 1` expone `UNICO = 1`. Los KPI de gestiones usan registros únicos por defecto. Cambiar un peso modifica el resultado en la siguiente consulta, sin recalcular ni persistir banderas.

## Pagos y estado

La unión se realiza por:

```text
gestiones_expertis.codigo_normalizado
= pagos_expertis.cuenta_normalizada
```

Un pago es válido para una gestión únicamente si `pago.fecha >= gestion.fecha_llamada`.

- `Fecha_Pagada`: primera fecha válida.
- `Monto_pagado`: suma de pagos válidos.
- `PAGO`: monto pagado mayor que cero.
- `NO PAGO`: sin pago real válido.

PPC, PPM, RPP u otra promesa no se convierten por sí mismas en pago.

## PS-Proyectado

1. Usa `gestiones_expertis.monto` cuando es mayor que cero.
2. Para `VLL` con monto cero, busca `MONTO TOTAL NEGOCIADO` en la observación.
3. En cualquier otro caso usa cero.

El normalizador tolera `S/`, `S/.`, espacios, punto o coma decimal y texto posterior separado por `/`.

## Seguridad y almacenamiento

- Todas las rutas están bajo `auth` y CSRF.
- La petición comprueba extensión, MIME y estructura ZIP interna del XLSX.
- Los archivos se guardan bajo `storage/app/private/expertis`.
- Al promover un XLSX desde la vista previa se intenta moverlo y, si el sistema operativo
  bloquea temporalmente el renombrado, se usa copia verificada por SHA-256 antes de procesarlo.
- Las descargas pasan por un controlador autenticado; nunca exponen la ruta física.
- Las plantillas XLSX se generan bajo demanda y son verificadas con el mismo lector del importador.
- Las consultas usan Eloquent o Query Builder parametrizado.

## Asignaciones mensuales

`asignaciones_expertis` conserva cada cartera mensual sin reemplazar periodos anteriores.
La clave única es `periodo + empresa + codigo_normalizado`; `hash_fila` está indexado, pero
no es único. Los índices `codigo_normalizado + periodo`, `documento + periodo`,
`periodo + tipo_cartera` y `periodo + departamento` soportan el listado y el futuro cruce
con pagos.

El código se calcula exclusivamente en `AsignacionExpertisDataMapper` como
`DOCUMENTO normalizado + "-" + TIPO DE CARTERA normalizado`. El documento admite DNI,
RUC y carnet de extranjería de 6 a 20 caracteres alfanuméricos. Esta regla no se aplica a
otros módulos. Si el XLSX incluye un código diferente, la fila queda registrada como
error. El encabezado legacy `DNI` sigue siendo compatible.

La columna física también se llama `documento`. La migración
`2026_07_29_000001_replace_dni_with_documento_in_asignaciones_expertis_table`
elimina previamente todas las asignaciones y su historial de importación para evitar que
los hashes antiguos bloqueen una carga nueva. También descarta jobs pendientes o fallidos
de asignaciones. Gestiones, pagos y los demás jobs no se eliminan.

La preparación se ejecuta fuera de HTTP mediante Laravel Queue. Abre la primera hoja con
OpenSpout una sola vez, confirma un único periodo `YYYYMM` y empresa `EXPERTIS`, conserva
solo las primeras 20 filas válidas y escribe JSON Lines privados en
`storage/app/private/expertis/prepared/{id}`. Cada bloque usa
`EXPERTIS_IMPORT_CHUNK_SIZE` (1000 por defecto).

Después de la confirmación, un segundo Job lee únicamente esos bloques y usa transacciones
cortas y upsert masivo:

- clave nueva: insertada;
- misma clave y mismo hash: duplicada;
- misma clave y hash distinto: actualizada;
- clave repetida con datos diferentes dentro del archivo: error.

No se elimina el periodo ni se borran clientes ausentes en una reimportación. El listado
`/expertis/asignaciones` selecciona el último periodo disponible y pagina 50 filas. La
exportación usa un cursor y OpenSpout, por lo que no carga aproximadamente 40 000 registros
en memoria. La plantilla se genera bajo demanda con tres registros ficticios.

Los estados de asignaciones son `validando`, `listo_para_importar`, `en_cola`,
`procesando`, `completado`, `completado_con_errores`, `fallido` y `duplicado`. La interfaz
consulta el estado cada tres segundos y muestra progreso y heartbeat. Un fallo conserva el
XLSX y los bloques completos para poder reintentar.

Operación segura:

```bash
php artisan expertis:imports:recover-stale --minutes=15 --dry-run
php artisan expertis:imports:recover-stale --minutes=15
php artisan expertis:prepared:cleanup --days=7 --dry-run
```

La recuperación marca como fallida una ejecución sin actividad; no borra filas ya
insertadas. El reintento prefiere bloques completos y solo vuelve al XLSX si faltan.

La futura relación con pagos queda preparada así:

```text
pagos_expertis.cuenta_normalizada
= asignaciones_expertis.codigo_normalizado

asignaciones_expertis.periodo
= DATE_FORMAT(pagos_expertis.fecha, '%Y%m')

asignaciones_expertis.empresa
= 'EXPERTIS'
```
- Las excepciones internas se registran y se muestra un mensaje genérico.
