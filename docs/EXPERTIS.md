# Arquitectura Expertis

## Alcance

Expertis es un módulo aislado del flujo operativo legacy. Conserva la autenticación por sesión y se integra mediante páginas Inertia, pero no escribe en `data`, `gestiones`, `pagos` ni en las tablas de tipificación actuales.

## Capas

- `app/Http/Controllers/Expertis`: endpoints autenticados, paginación, descargas y respuestas Inertia.
- `app/Http/Requests/Expertis`: validación de XLSX y CRUD de tipificaciones.
- `app/Services/Expertis`:
  - `ExpertisSpreadsheetService`: lectura streaming con OpenSpout;
  - `NormalizadorExpertisService`: encabezados, documentos, fechas, horas, montos y hashes;
  - importadores de gestiones y pagos: lotes, transacciones y auditoría;
  - `ExpertisReportQueryService`: filtros, `UNICO`, pagos válidos y agregaciones.
- `app/Models`: relaciones de importaciones, errores, tipificaciones, gestiones y pagos.
- `resources/js`: layout, componentes y páginas Vue 3.

## Tablas

### `importaciones_expertis`

Audita usuario, tipo, archivo, SHA-256, estado, contadores, rango de fechas, resumen y error general. La restricción única `tipo + hash_archivo` impide procesar dos veces el mismo archivo.

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
- El DNI visible se mantiene como texto y se completa hasta ocho dígitos.
- La clave de cruce elimina ceros iniciales solo de la parte documental.
- Teléfonos conservan únicamente dígitos.
- Fechas aceptan valores de Excel, `dd/mm/yyyy` y `yyyy-mm-dd`.
- Montos aceptan símbolos de moneda y separadores decimales comunes.
- Textos de clasificación se almacenan en mayúsculas.

## Duplicados

### Archivo

Se calcula SHA-256 del XLSX original. La combinación de tipo y hash es única.

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
- Las descargas pasan por un controlador autenticado; nunca exponen la ruta física.
- Las consultas usan Eloquent o Query Builder parametrizado.
- Las excepciones internas se registran y se muestra un mensaje genérico.
