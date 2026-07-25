<script setup>
import { computed } from 'vue';
import StatCard from './StatCard.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
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
</script>

<template>
    <section class="min-w-0">
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
    </section>
</template>