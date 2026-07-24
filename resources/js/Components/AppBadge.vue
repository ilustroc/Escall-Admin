<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'neutral' },
});

const classes = computed(() => ({
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    warning: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    danger: 'bg-red-50 text-red-700 ring-red-600/20',
    info: 'bg-escall-50 text-escall-700 ring-escall-600/20',
    neutral: 'bg-slate-100 text-slate-600 ring-slate-500/20',
}[props.tone] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20'));
</script>

<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset', classes]">
        <slot />
    </span>
</template>
