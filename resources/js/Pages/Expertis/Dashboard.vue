<script setup>
import { computed } from 'vue';
import AppBadge from '../../Components/AppBadge.vue';
import AppButton from '../../Components/AppButton.vue';
import EmptyState from '../../Components/EmptyState.vue';
import StatCard from '../../Components/StatCard.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    expertis: { type: Object, required: true },
    legacy: { type: Object, default: null },
});

const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
const maxGestiones = computed(() => Math.max(1, ...props.expertis.gestiones_por_dia.map((item) => Number(item.total || 0))));
const maxPagos = computed(() => Math.max(1, ...props.expertis.pagos_por_dia.map((item) => Number(item.total || 0))));

function tone(status) {
    if (status === 'completado') return 'success';
    if (status === 'completado_con_errores' || status === 'duplicado') return 'warning';
    if (status === 'fallido') return 'danger';
    return 'info';
}
</script>

<template>
    <AppLayout
        title="Dashboard"
        subtitle="Actividad real de gestiones, pagos e importaciones ESCALL Perú."
        :breadcrumbs="['Principal', 'Dashboard']"
    >
        <EmptyState
            v-if="!expertis.disponible"
            title="Expertis aún no está desplegado"
            description="El panel principal sigue disponible. Ejecuta las migraciones dirigidas y el seeder para habilitar sus indicadores."
        >
            <AppButton href="/cargas" variant="secondary">Ir a cargas actuales</AppButton>
        </EmptyState>

        <template v-else>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Gestiones Expertis hoy" :value="expertis.metricas.gestiones_hoy" tone="info" />
                <StatCard label="Gestiones Expertis del mes" :value="expertis.metricas.gestiones_mes" />
                <StatCard label="Pagos Expertis hoy" :value="expertis.metricas.pagos_hoy" tone="success" />
                <StatCard label="Recaudo Expertis del mes" :value="money(expertis.metricas.recaudo_mes)" tone="success" />
                <StatCard
                    label="Última carga de gestiones"
                    :value="expertis.metricas.ultima_gestion ? `#${expertis.metricas.ultima_gestion.id}` : '—'"
                    :helper="expertis.metricas.ultima_gestion?.nombre_original"
                    tone="info"
                />
                <StatCard
                    label="Última carga de pagos"
                    :value="expertis.metricas.ultimo_pago ? `#${expertis.metricas.ultimo_pago.id}` : '—'"
                    :helper="expertis.metricas.ultimo_pago?.nombre_original"
                    tone="success"
                />
                <StatCard label="Duplicados última carga" :value="expertis.metricas.duplicados_ultima" tone="warning" />
                <StatCard label="Archivos con errores" :value="expertis.metricas.archivos_con_errores" tone="danger" />
            </div>

            <div v-if="legacy" class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-[#172033]">Operación actual</h2>
                        <p class="mt-1 text-xs text-slate-500">Indicadores operativos integrados en la arquitectura unificada.</p>
                    </div>
                    <div class="flex flex-wrap gap-5 text-xs">
                        <span>Gestiones hoy: <strong class="text-[#172033]">{{ legacy.gestiones_hoy }}</strong></span>
                        <span>Pagos hoy: <strong class="text-[#12B76A]">{{ money(legacy.pagos_hoy) }}</strong></span>
                        <span>Última carga: <strong class="text-[#172033]">{{ legacy.ultima_carga ? new Date(legacy.ultima_carga).toLocaleString('es-PE') : '—' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div><h2 class="text-sm font-bold text-[#172033]">Gestiones por día</h2><p class="mt-1 text-[11px] text-slate-500">Últimos 30 días</p></div>
                        <AppButton href="/expertis/gestiones" variant="ghost" size="sm">Ver tabla</AppButton>
                    </div>
                    <div class="scrollbar-thin mt-5 flex h-52 items-end gap-2 overflow-x-auto border-b border-slate-200 pb-1">
                        <div v-for="item in expertis.gestiones_por_dia" :key="item.fecha" class="group flex min-w-7 flex-1 flex-col items-center justify-end">
                            <span class="mb-1 hidden text-[9px] font-bold group-hover:block">{{ item.total }}</span>
                            <div class="w-full max-w-9 rounded-t-md bg-escall-500" :style="{ height: `${Math.max(4, (Number(item.total) / maxGestiones) * 165)}px` }" />
                            <span class="mt-2 -rotate-45 text-[8px] text-slate-400">{{ String(item.fecha).slice(5) }}</span>
                        </div>
                        <p v-if="!expertis.gestiones_por_dia.length" class="m-auto text-xs text-slate-400">Sin gestiones en el periodo.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div><h2 class="text-sm font-bold text-[#172033]">Pagos por día</h2><p class="mt-1 text-[11px] text-slate-500">Recaudo de los últimos 30 días</p></div>
                        <AppButton href="/expertis/pagos" variant="ghost" size="sm">Ver tabla</AppButton>
                    </div>
                    <div class="scrollbar-thin mt-5 flex h-52 items-end gap-2 overflow-x-auto border-b border-slate-200 pb-1">
                        <div v-for="item in expertis.pagos_por_dia" :key="item.fecha" class="group flex min-w-7 flex-1 flex-col items-center justify-end">
                            <span class="mb-1 hidden text-[9px] font-bold group-hover:block">{{ money(item.total) }}</span>
                            <div class="w-full max-w-9 rounded-t-md bg-[#12B76A]" :style="{ height: `${Math.max(4, (Number(item.total) / maxPagos) * 165)}px` }" />
                            <span class="mt-2 -rotate-45 text-[8px] text-slate-400">{{ String(item.fecha).slice(5) }}</span>
                        </div>
                        <p v-if="!expertis.pagos_por_dia.length" class="m-auto text-xs text-slate-400">Sin pagos en el periodo.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <h2 class="text-sm font-bold text-[#172033]">Top asesores del mes</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="(item, index) in expertis.top_asesores" :key="item.etiqueta" class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-escall-50 text-[11px] font-bold text-escall-600">{{ index + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-600">{{ item.etiqueta }}</span>
                            <strong class="text-sm text-[#172033]">{{ item.total }}</strong>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <h2 class="text-sm font-bold text-[#172033]">Top tipificaciones del mes</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div v-for="item in expertis.top_tipificaciones" :key="item.etiqueta" class="rounded-xl bg-slate-50 p-3">
                            <AppBadge tone="info">{{ item.etiqueta }}</AppBadge>
                            <p class="mt-2 text-xl font-bold text-[#172033]">{{ item.total }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card xl:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-[#172033]">Actividad reciente</h2>
                        <AppButton href="/expertis/importaciones" variant="ghost" size="sm">Historial completo</AppButton>
                    </div>
                    <div class="mt-4 divide-y divide-slate-100">
                        <div v-for="item in expertis.actividad" :key="item.id" class="flex flex-wrap items-center gap-3 py-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-escall-50 text-xs font-bold text-escall-600">{{ item.tipo === 'pagos' ? 'P' : 'G' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-[#172033]">{{ item.nombre_original }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ item.usuario?.name ?? 'Sistema' }} · {{ new Date(item.created_at).toLocaleString('es-PE') }}</p>
                            </div>
                            <AppBadge :tone="tone(item.estado)">{{ item.estado.replaceAll('_', ' ') }}</AppBadge>
                            <span class="text-xs text-slate-500">{{ item.filas_insertadas }} nuevas · {{ item.filas_duplicadas }} duplicadas</span>
                        </div>
                        <p v-if="!expertis.actividad.length" class="py-10 text-center text-xs text-slate-400">Aún no hay importaciones Expertis.</p>
                    </div>
                </section>
            </div>
        </template>
    </AppLayout>
</template>
