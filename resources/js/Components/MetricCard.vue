<script setup>
import StatCard from './StatCard.vue';
defineProps({ label: { type: String, required: true }, value: { type: [String, Number], default: 0 }, hint: { type: String, default: '' }, tone: { type: String, default: 'blue' } });
</script>

<template><StatCard :label="label" :value="value" :helper="hint" :tone="tone"><template v-if="$slots.icon" #icon><slot name="icon" /></template></StatCard></template>
