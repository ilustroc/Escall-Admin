<script setup>
import { computed } from 'vue';
import StatCard from './StatCard.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
});

const period = computed(() => (
    props.summary.resumen?.periodo
    ?? props.summary.periodo
    ?? null
));

const progressTotal = computed(() => Number(
    props.summary.progreso_total
    ?? props.summary.progreso?.total
    ?? 0,
));

const progressCurrent = computed(() => Number(
    props.summary.progreso_actual
    ?? props.summary.progreso?.actual
    ?? 0,
));

const progressPercent = computed(() => {
    if (props.summary.progreso?.porcentaje != null) {
        return Math.min(100, Number(props.summary.progreso.porcentaje));
    }

    if (progressTotal.value <= 0) {
        return 0;
    }

    return Math.min(
        100,
        (progressCurrent.value / progressTotal.value) * 100,
    );
});

const stats = computed(() => [
    {
        label: 'Total',
        value:
            props.summary.total_filas
            ?? props.summary.total
            ?? 0,
        tone: 'default',
    },
    {
        label: 'Insertadas',
        value:
            props.summary.filas_insertadas
            ?? props.summary.insertadas
            ?? 0,
        tone: 'success',
    },
    {
        label: 'Actualizadas',
        value:
            props.summary.filas_actualizadas
            ?? props.summary.actualizadas
            ?? 0,
        tone: 'info',
    },
    {
        label: 'Duplicadas',
        value:
            props.summary.filas_duplicadas
            ?? props.summary.duplicadas
            ?? 0,
        tone: 'warning',
    },
    {
        label: 'Errores',
        value:
            props.summary.filas_error
            ?? props.summary.errores
            ?? 0,
        tone: Number(
            props.summary.filas_error
            ?? props.summary.errores
            ?? 0,
        ) > 0
            ? 'danger'
            : 'default',
    },
]);

function formatNumber(value) {
    const number = Number(value ?? 0);

    if (!Number.isFinite(number)) {
        return '0';
    }

    return new Intl.NumberFormat('es-PE', {
        maximumFractionDigits: 0,
    }).format(number);
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    /*
     * Extraemos directamente YYYY-MM-DD para impedir que
     * una conversión UTC cambie el día en la zona de Lima.
     */
    const match = String(value).match(
        /^(\d{4})-(\d{2})-(\d{2})/,
    );

    if (match) {
        const [, year, month, day] = match;

        return `${day}/${month}/${year}`;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'America/Lima',
    }).format(date);
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? String(value)
        : new Intl.DateTimeFormat('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: 'America/Lima',
        }).format(date);
}

function elapsedTime(start, end) {
    if (!start) {
        return '—';
    }

    const startDate = new Date(start);
    const endDate = end ? new Date(end) : new Date();
    if (
        Number.isNaN(startDate.getTime())
        || Number.isNaN(endDate.getTime())
    ) {
        return '—';
    }

    const seconds = Math.max(
        0,
        Math.round((endDate - startDate) / 1000),
    );

    return seconds < 60
        ? `${seconds}s`
        : `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
}
</script>

<template>
    <section class="min-w-0">
        <div
            v-if="summary.fase || progressTotal > 0"
            class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
        >
            <div class="flex items-center justify-between gap-3 text-xs">
                <span class="font-semibold capitalize text-slate-700">
                    {{ String(summary.fase ?? 'procesando').replaceAll('_', ' ') }}
                </span>
                <span class="font-bold text-[#073DC7]">
                    {{ progressPercent.toFixed(1) }}%
                </span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full bg-[#073DC7] transition-all duration-300"
                    :style="{ width: `${progressPercent}%` }"
                />
            </div>
            <p class="mt-2 text-[11px] text-slate-500">
                {{ formatNumber(progressCurrent) }} de
                {{ formatNumber(progressTotal) }} filas
            </p>
        </div>

        <div
            v-if="period"
            class="mb-3 rounded-xl border border-blue-100 bg-[#EEF4FF] px-4 py-3"
        >
            <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-[#073DC7]">
                Periodo
            </p>
            <p class="mt-1 text-base font-bold text-[#172033]">
                {{ period }}
            </p>
        </div>

        <!--
            Este resumen está diseñado para el panel lateral.
            Siempre usa dos columnas para evitar que las tarjetas
            y sus números sobresalgan.
        -->
        <div class="grid min-w-0 grid-cols-2 gap-3">
            <StatCard
                v-for="(stat, index) in stats"
                :key="stat.label"
                class="min-w-0 overflow-hidden"
                :class="
                    index === stats.length - 1
                        ? 'col-span-2'
                        : ''
                "
                :label="stat.label"
                :value="formatNumber(stat.value)"
                :tone="stat.tone"
            />
        </div>

        <!-- Rango detectado -->
        <div
            v-if="summary.fecha_minima || summary.fecha_maxima"
            class="mt-4 min-w-0 rounded-xl border border-blue-100 bg-[#EEF4FF] px-4 py-3"
        >
            <p
                class="text-[10px] font-bold uppercase tracking-[0.08em] text-[#073DC7]"
            >
                Rango detectado
            </p>

            <div
                class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-slate-700"
            >
                <span>
                    {{ formatDate(summary.fecha_minima) }}
                </span>

                <span class="text-slate-400">
                    —
                </span>

                <span>
                    {{ formatDate(summary.fecha_maxima) }}
                </span>
            </div>
        </div>

        <dl
            v-if="summary.heartbeat_at || summary.iniciado_at"
            class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs sm:grid-cols-2"
        >
            <div>
                <dt class="font-semibold text-slate-500">
                    Último heartbeat
                </dt>
                <dd class="mt-1 text-slate-700">
                    {{ formatDateTime(summary.heartbeat_at) }}
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">
                    Tiempo transcurrido
                </dt>
                <dd class="mt-1 text-slate-700">
                    {{
                        elapsedTime(
                            summary.iniciado_at,
                            summary.finalizado_at,
                        )
                    }}
                </dd>
            </div>
        </dl>
    </section>
</template>
