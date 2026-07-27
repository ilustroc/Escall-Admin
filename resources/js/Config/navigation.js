import {
    ArrowUpTrayIcon,
    ClipboardDocumentListIcon,
    DocumentChartBarIcon,
    HomeIcon,
    QueueListIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';

export const navigation = [
    {
        label: 'Principal',
        items: [
            { label: 'Dashboard', href: '/', icon: HomeIcon, exact: true },
        ],
    },
    {
        label: 'Operativo',
        items: [
            {
                label: 'Cargas',
                icon: ArrowUpTrayIcon,
                match: [
                    '/cargas',
                    '/expertis/importaciones/asignaciones',
                    '/expertis/importaciones/gestiones',
                    '/expertis/importaciones/pagos',
                ],
                children: [
                    { label: 'Gestiones por SP', href: '/cargas/sp' },
                    { label: 'Cartera', href: '/cargas/data' },
                    { label: 'Pagos', href: '/cargas/pagos' },
                    { label: 'Expertis Asignaciones', href: '/expertis/importaciones/asignaciones' },
                    { label: 'Expertis Gestiones', href: '/expertis/importaciones/gestiones' },
                    { label: 'Expertis Pagos', href: '/expertis/importaciones/pagos' },
                ],
            },
            { label: 'Listas', href: '/listas', icon: QueueListIcon },
            {
                label: 'Expertis',
                icon: Squares2X2Icon,
                match: ['/expertis'],
                children: [
                    { label: 'Dashboard Expertis', href: '/expertis', exact: true },
                    { label: 'Historial', href: '/expertis/importaciones' },
                    { label: 'Asignaciones', href: '/expertis/asignaciones' },
                    { label: 'Gestiones', href: '/expertis/gestiones' },
                    { label: 'Pagos', href: '/expertis/pagos' },
                    { label: 'Tipificaciones', href: '/expertis/tipificaciones' },
                ],
            },
        ],
    },
    {
        label: 'Analítica',
        items: [
            {
                label: 'Tablas',
                icon: ClipboardDocumentListIcon,
                match: ['/tablas'],
                children: [
                    { label: 'Gestiones', href: '/tablas?tab=gestiones' },
                    { label: 'Pagos', href: '/tablas?tab=pagos' },
                    { label: 'Cartera / Data', href: '/tablas?tab=data' },
                    { label: 'Gestiones Expertis', href: '/expertis/gestiones' },
                    { label: 'Pagos Expertis', href: '/expertis/pagos' },
                ],
            },
            {
                label: 'Reportes',
                icon: DocumentChartBarIcon,
                match: ['/reportes', '/expertis/reportes'],
                children: [
                    { label: 'General', href: '/reportes', exact: true },
                    { label: 'Impulse', href: '/reportes/impulse' },
                    { label: 'KP Invest', href: '/reportes/kp-invest' },
                    { label: 'Carteras', href: '/reportes/carteras' },
                    { label: 'Expertis', href: '/expertis/reportes' },
                ],
            },
        ],
    },
];
