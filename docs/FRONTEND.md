# Frontend Vue e Inertia

## Inicio de la aplicación

`resources/js/app.js` ejecuta siempre `createInertiaApp`. No existe detección de páginas Blade, fallback legacy ni listeners DOM para montar otra interfaz. Vite compila una única entrada: `resources/js/app.js`, que importa Tailwind desde `resources/css/app.css`.

## Layouts

- `AppLayout.vue`: único layout autenticado. Incluye logo, sidebar colapsable, navegación, cabecera, usuario, logout, breadcrumbs, overlay móvil, toasts y slot principal.
- `GuestLayout.vue`: layout no autenticado para login y futuras páginas públicas.

No hay layout ni sidebar separado para Expertis.

## Navegación

`resources/js/Config/navigation.js` es la fuente única del menú. Agrupa Principal, Operativo y Analítica, y enlaza cargas, listas, Expertis, tablas y reportes. Los iconos provienen de `@heroicons/vue`.

## Componentes

Se mantienen y reutilizan los componentes existentes de botones, inputs, selects, modales, paginación, tablas, toasts, confirmación, archivos e importaciones. Se agregaron:

- `PageHeader`, `Breadcrumbs`, `FilterPanel`;
- `SearchInput`, `DateRangeFilter`, `FormField`, `TextArea`, `CurrencyInput`;
- `DataTable`, `TableSkeleton`;
- `Alert`, `Dropdown`, `Tabs`, `Drawer`;
- `MetricCard`, `ExportButton`, `ImportStatusBadge`.

Los componentes concentran alturas, bordes, radios, colores y estados para evitar bloques Tailwind repetidos.

## Filtros y paginación

Los filtros viajan por query string. `useFilters` elimina valores vacíos y usa `router.get` con `preserveState`, `preserveScroll` y `replace`. `useDebouncedSearch` aplica una espera de 400 ms en búsquedas. Los paginadores conservan el query string generado por Laravel.

Las exportaciones construyen su URL con los mismos filtros activos. Las tablas legacy usan paginación y ordenamiento server-side con opciones de 25, 50, 100 y 250 filas.

## Formularios y feedback

Los formularios de escritura usan `useForm`; los errores 422 se muestran junto a cada campo. El middleware Inertia comparte `success`, `warning`, `error` e `info`. `AppToast` presenta estos mensajes en todas las páginas.

Las acciones destructivas usan `ConfirmDialog` y `useConfirm`; no se usa `confirm()` ni `alert()` nativo.

## Diseño responsive

El sistema conserva la identidad ESCALL (`#073DC7`, `#155EEF`, `#07142B`, `#EEF4FF`, `#F5F7FB`). El sidebar tiene overlay móvil y modo colapsado en escritorio. Los grids se adaptan por breakpoint y cada tabla controla su propio scroll horizontal, evitando overflow general.
