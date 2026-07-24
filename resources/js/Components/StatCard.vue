<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    helper: { type: String, default: '' },
    tone: { type: String, default: 'neutral' },
    prefix: { type: String, default: '' },
});

const color = computed(() => ({
    success: 'bg-emerald-50 text-[#12B76A]',
    warning: 'bg-amber-50 text-[#F79009]',
    danger: 'bg-red-50 text-[#F04438]',
    info: 'bg-escall-50 text-escall-600',
    neutral: 'bg-slate-100 text-slate-600',
}[props.tone] ?? 'bg-slate-100 text-slate-600'));
</script>

<template>
    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-slate-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-[#172033]">{{ prefix }}{{ value }}</p>
                <p v-if="helper" class="mt-1 text-xs text-slate-500">{{ helper }}</p>
            </div>
            <span :class="['flex h-9 w-9 items-center justify-center rounded-xl text-sm font-bold', color]">
                <slot name="icon">↗</slot>
            </span>
        </div>
    </article>
</template>
