<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import StatCard from '../../../Components/StatCard.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    tipo: { type: String, required: true },
    filtros: { type: Object, default: () => ({}) },
    reporte: { type: Object, required: true },
    opciones: { type: Object, required: true },
});

const filters = reactive({
    tipo: props.tipo,
    fecha_inicio: props.filtros.fecha_inicio ?? '',
    fecha_fin: props.filtros.fecha_fin ?? '',
    cartera: props.filtros.cartera ?? '',
    asesor: props.filtros.asesor ?? '',
    nivel_2: props.filtros.nivel_2 ?? '',
    estado: props.filtros.estado ?? '',
    ejecutivo: props.filtros.ejecutivo ?? '',
    recaudo: props.filtros.recaudo ?? '',
});

const isGestiones = computed(() => filters.tipo === 'gestiones');
const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
const exportBase = computed(() => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '') params.set(key, value);
    });
    const resource = isGestiones.value ? 'gestiones' : 'pagos';
    return `/expertis/reportes/${resource}/export?${params}`;
});
const evolution = computed(() => props.reporte.evolucion_diaria ?? props.reporte.por_dia ?? []);
const maxEvolution = computed(() => Math.max(1, ...evolution.value.map((item) => Number(item.total || item.monto || 0))));

function applyFilters() {
    router.get('/expertis/reportes', filters, { preserveState: true, replace: true });
}

function changeType(type) {
    filters.tipo = type;
    applyFilters();
}
</script>

<template>
    <AppLayout
        title="Reporte Expertis"
        subtitle="Indicadores y exportaciones calculados con los mismos filtros de las tablas."
        :breadcrumbs="['Analítica', 'Reportes', 'Expertis']"
    >
        <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <button class="rounded-lg px-4 py-2 text-sm font-bold" :class="isGestiones ? 'bg-escall-600 text-white' : 'text-slate-600 hover:bg-slate-50'" @click="changeType('gestiones')">Gestiones</button>
            <button class="rounded-lg px-4 py-2 text-sm font-bold" :class="!isGestiones ? 'bg-escall-600 text-white' : 'text-slate-600 hover:bg-slate-50'" @click="changeType('pagos')">Pagos</button>
        </div>

        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <AppInput v-model="filters.fecha_inicio" type="date" label="Fecha inicio" />
                <AppInput v-model="filters.fecha_fin" type="date" label="Fecha fin" />
                <template v-if="isGestiones">
                    <AppSelect v-model="filters.cartera" label="Cartera" :options="opciones.carteras" />
                    <AppSelect v-model="filters.asesor" label="Asesor" :options="opciones.asesores" />
                    <AppSelect v-model="filters.nivel_2" label="Tipificación" :options="opciones.niveles_2" />
                    <AppSelect v-model="filters.estado" label="Estado" :options="['PAGO', 'NO PAGO']" />
                </template>
                <template v-else>
                    <AppSelect v-model="filters.ejecutivo" label="Ejecutivo" :options="opciones.ejecutivos" />
                    <AppSelect v-model="filters.recaudo" label="Recaudo" :options="opciones.recaudos" />
                </template>
            </div>
            <div class="mt-4 flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-4">
                <AppButton @click="applyFilters">Actualizar reporte</AppButton>
                <div class="flex gap-2">
                    <a :href="`${exportBase}&formato=csv`" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">CSV</a>
                    <a :href="`${exportBase}&formato=xlsx`" class="rounded-lg bg-escall-600 px-3 py-2 text-xs font-bold text-white hover:bg-escall-700">Exportar XLSX</a>
                </div>
            </div>
        </section>

        <div v-if="isGestiones" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total de gestiones" :value="reporte.totales.total_gestiones" tone="info" />
            <StatCard label="Clientes únicos" :value="reporte.totales.clientes_unicos" />
            <StatCard label="Contactos efectivos" :value="reporte.totales.contactos_efectivos" tone="success" />
            <StatCard label="No contactos" :value="reporte.totales.no_contactos" tone="warning" />
            <StatCard label="Promesas" :value="reporte.totales.promesas" tone="info" />
            <StatCard label="Clientes con pago" :value="reporte.totales.clientes_con_pago" tone="success" />
            <StatCard label="Monto proyectado" :value="money(reporte.totales.monto_proyectado)" />
            <StatCard label="Monto pagado" :value="money(reporte.totales.monto_pagado)" tone="success" />
            <StatCard label="Conversión" :value="`${reporte.totales.conversion}%`" tone="info" />
        </div>
        <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Monto total" :value="money(reporte.totales.monto_total)" tone="success" />
            <StatCard label="Cantidad de pagos" :value="reporte.totales.cantidad_pagos" tone="info" />
            <StatCard label="Clientes únicos" :value="reporte.totales.clientes_unicos" />
            <StatCard label="Pago promedio" :value="money(reporte.totales.pago_promedio)" tone="warning" />
            <StatCard label="Con gestión previa" :value="reporte.totales.con_gestion_previa" tone="success" />
            <StatCard label="Sin gestión previa" :value="reporte.totales.sin_gestion_previa" tone="warning" />
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="text-sm font-bold text-[#172033]">{{ isGestiones ? 'Resultados por asesor' : 'Pagos por ejecutivo' }}</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="item in (isGestiones ? reporte.por_asesor : reporte.por_ejecutivo)" :key="item.etiqueta" class="flex items-center justify-between gap-4">
                        <span class="truncate text-xs font-semibold text-slate-600">{{ item.etiqueta }}</span>
                        <span class="text-xs font-bold text-[#172033]">{{ item.total }} · {{ money(item.monto_pagado ?? item.monto) }}</span>
                    </div>
                    <p v-if="!(isGestiones ? reporte.por_asesor : reporte.por_ejecutivo)?.length" class="py-8 text-center text-xs text-slate-400">Sin datos.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="text-sm font-bold text-[#172033]">{{ isGestiones ? 'Resultados por cartera' : 'Pagos por cartera relacionada' }}</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="item in reporte.por_cartera" :key="item.etiqueta" class="flex items-center justify-between gap-4">
                        <span class="truncate text-xs font-semibold text-slate-600">{{ item.etiqueta }}</span>
                        <span class="text-xs font-bold text-[#172033]">{{ item.total }} · {{ money(item.monto_pagado ?? item.monto) }}</span>
                    </div>
                </div>
            </section>

            <section v-if="isGestiones" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card xl:col-span-2">
                <h2 class="text-sm font-bold text-[#172033]">Resultados por tipificación</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="item in reporte.por_tipificacion" :key="item.etiqueta" class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs font-bold text-slate-600">{{ item.etiqueta }}</p>
                        <p class="mt-1 text-lg font-bold text-[#172033]">{{ item.total }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card xl:col-span-2">
                <h2 class="text-sm font-bold text-[#172033]">Evolución diaria</h2>
                <div class="scrollbar-thin mt-5 flex h-48 items-end gap-2 overflow-x-auto border-b border-slate-200 pb-1">
                    <div v-for="item in evolution" :key="item.fecha" class="group flex min-w-8 flex-1 flex-col items-center justify-end">
                        <span class="mb-1 hidden text-[9px] font-bold text-slate-600 group-hover:block">{{ item.total }}</span>
                        <div class="w-full max-w-10 rounded-t-md bg-escall-500" :style="{ height: `${Math.max(4, (Number(item.total || item.monto || 0) / maxEvolution) * 150)}px` }" />
                        <span class="mt-2 -rotate-45 whitespace-nowrap text-[8px] text-slate-400">{{ String(item.fecha).slice(5) }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
