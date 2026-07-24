<script setup>
import { computed } from 'vue';
const props = defineProps({ tone: { type: String, default: 'info' }, title: { type: String, default: '' } });
const classes = computed(() => ({
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    error: 'border-red-200 bg-red-50 text-red-800',
    info: 'border-blue-200 bg-blue-50 text-blue-800',
}[props.tone]));
</script>

<template><div class="rounded-xl border p-4 text-sm" :class="classes"><strong v-if="title" class="mb-1 block">{{ title }}</strong><slot /></div></template>
