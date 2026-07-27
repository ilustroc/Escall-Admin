# Formatos de importación Expertis

Usa archivos `.xlsx`. La primera fila no vacía de la primera hoja se interpreta como encabezado.
Las pantallas de **Expertis Gestiones** y **Expertis Pagos** incluyen el botón
**Descargar plantilla**, que entrega un XLSX válido con todos los encabezados y una fila de ejemplo.

## Gestiones

Columnas admitidas:

| Encabezado | Obligatoria | Ejemplo |
|---|---:|---|
| Canal Gestión | No | CALL |
| Canal Asignación | No | DIGITAL |
| DNI | Sí | 00123456 |
| Nombre Cliente | No | CLIENTE FICTICIO |
| Cartera | Sí | OH |
| Asesor | No | ANA PEREZ |
| Equipo | No | EQUIPO NORTE |
| Teléfono | No | 999111222 |
| Fecha Llamada | Sí | 21/07/2026 |
| Campaña | No | JULIO 2026 |
| Hora | No | 10:15:00 |
| Nivel 1 | No | CONTACTO |
| Nivel 2 | Sí | PPC |
| Fecha Compromiso | No | 25/07/2026 |
| Monto | No | S/ 250.00 |
| Observación | No | CLIENTE ACEPTA SEGUIMIENTO |
| Medio Gestión | No | LLAMADA |

También se aceptan variantes sin tildes, en mayúsculas/minúsculas, con guiones bajos o espacios repetidos.
Este listado coincide con el archivo operativo recibido de Expertis/JZG; las 17 columnas se
conservan en `gestiones_expertis`.

## Pagos

| Encabezado | Obligatoria | Ejemplo |
|---|---:|---|
| FECHA | Sí | 22/07/2026 |
| CUENTA | Sí | 00123456-OH |
| MONTO | Sí | S/ 250.00 |
| EJECUTIVO | No | ANA PEREZ |
| TIPO DE ACUERDO | No | CUOTA |
| RECAUDO | No | BANCO |

Los pagos también pueden registrarse individualmente desde **Cargas → Expertis Pagos →
Registrar pago manual**. El formulario solicita los mismos datos y exige la cuenta en formato
`DNI-CARTERA`.

## Fechas y horas

Fechas:

- fecha real de Excel;
- `dd/mm/yyyy`;
- `yyyy-mm-dd`.
- cualquiera de los formatos anteriores seguido de `HH:mm` o `HH:mm:ss`, por ejemplo
  `1/01/2025 00:00`.

Horas:

- hora real de Excel;
- `HH:mm`;
- `HH:mm:ss`.

Si la hora está vacía, `fecha_hora` usa `00:00:00`.
El literal textual `NULL` se interpreta como un valor vacío en campos opcionales.

## Montos

Ejemplos válidos:

- `200`
- `200.00`
- `S/ 200.00`
- `1,350.50`
- `1.350,50`

Los pagos deben ser mayores que cero. Una gestión sin monto se almacena con cero.

## Validación previa

Gestiones y pagos:

1. valida tamaño, extensión y contenido;
2. detecta encabezados;
3. muestra las columnas faltantes;
4. presenta las primeras 20 filas con fechas y horas ya convertidas a texto legible;
5. solicita confirmación.

Un archivo con columnas obligatorias faltantes no obtiene token de importación y no escribe registros.

Asignaciones usa un flujo asíncrono: **Subir y validar** guarda el XLSX privado y abre el
detalle. Allí se muestra el progreso de la validación y, cuando queda
`listo_para_importar`, aparecen las primeras 20 filas válidas y el botón
**Confirmar importación**. El navegador puede cerrarse durante ambas fases.

## Errores frecuentes

- Guardar CSV con extensión `.xlsx`: el contenido será rechazado.
- Usar una plantilla distinta: descarga la plantilla oficial desde la misma pantalla de carga.
- DNI convertido por Excel a notación científica: formatea la columna como texto antes de exportar.
- Fecha como texto no reconocible: usa uno de los formatos indicados.
- Pago con monto cero o negativo: la fila se registra como error.
- Tipificación nueva: la gestión se importa, pero queda sin peso y aparece en el resumen de no reconocidas.
- Reenviar el mismo archivo: se identifica por SHA-256 y no se reprocesa.
- Reintentar un archivo cuyo intento anterior falló: reutiliza la auditoría fallida y vuelve a
  procesarlo; solo una importación completada se anuncia como duplicada.
- Repetir un pago idéntico: se ignora porque el proveedor no entrega un ID transaccional que permita distinguirlo.
- Cuenta manual sin cartera: usa siempre `DNI-CARTERA`, por ejemplo `47752785-QAPAQ`.

## Ejemplos

- `storage/app/examples/gestiones_expertis_ejemplo.xlsx`
- `storage/app/examples/pagos_expertis_ejemplo.xlsx`

Todos los datos son ficticios.

## Asignaciones

El archivo mensual requiere estos 26 encabezados:

| Encabezado | Valor obligatorio | Regla |
|---|---:|---|
| PERIODO | Sí | `YYYYMM`, un único periodo por archivo |
| EMPRESA | Sí | Debe ser `EXPERTIS` |
| DNI | Sí | Ocho dígitos; conserva ceros iniciales |
| TITULAR | Sí | Máximo 255 caracteres |
| CODIGO | No | El encabezado debe existir; el backend siempre lo calcula |
| TIPO DE CARTERA | Sí | Forma el código junto con el DNI |
| COSECHA | No | Texto opcional |
| SUB COSECHA | No | Texto opcional |
| PRODUCTO | No | Texto opcional |
| SUB_PRODUCTO | No | Texto opcional |
| HISTORICO | No | Texto opcional |
| DEPARTAMENTO | No | Texto opcional |
| DEUDA TOTAL | Sí | Monto con punto o coma decimal |
| DEUDA CAPITAL | Sí | Monto con punto o coma decimal |
| CAMPAÑA | No | Monto opcional |
| % | No | Decimal sin multiplicar ni dividir |
| AÑO_NACIMIENTO | No | Fecha Excel, `dd/mm/yyyy` o `yyyy-mm-dd` |
| EDAD | No | Entero entre 0 y 120 |
| ENTIDADES | No | Entero no negativo |
| NEGOCIO | No | Texto opcional |
| SUELDO | No | Monto opcional |
| SITUACION_LABORAL | No | Texto opcional |
| AÑO_LABORAL | No | Entero entre 1900 y 2100 |
| SEXO | No | `M` o `F` |
| RANGO_SUELDO | No | Texto opcional |
| AÑO_CASTIGO | No | Entero entre 1900 y 2100 |

En campos opcionales, `-`, vacío y `NULL` se guardan como null. La regla
`CODIGO = DNI-TIPO DE CARTERA` pertenece solo a Expertis; un código enviado que no coincida
se registra como error.

Un Job examina la hoja completa por streaming una sola vez, devuelve periodo, empresa y
total, y muestra como máximo 20 filas válidas. Las filas normalizadas se guardan en bloques
JSONL privados de 1000; el Job de importación consume esos bloques sin volver a abrir el
XLSX. Al reimportar un periodo, los registros idénticos se cuentan como duplicados y los
modificados se actualizan; no se borra el periodo completo.

`AÑO_LABORAL` y `AÑO_CASTIGO` aceptan vacío, `-`, `NULL` o un año entre 1900 y 2100. Un
valor como `1`, `5`, `NO` o `SIN DATO` se registra como error de esa fila; no se transforma
automáticamente y no detiene las demás filas válidas.

La plantilla oficial se descarga desde **Cargas → Expertis Asignaciones** e incluye tres
registros ficticios con DNI `00000001`, `00000002` y `00000003`.
