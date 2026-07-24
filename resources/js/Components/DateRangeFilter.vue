<script setup>
import AppInput from './AppInput.vue';
defineProps({ from: { type: String, default: '' }, to: { type: String, default: '' }, errors: { type: Object, default: () => ({}) } });
defineEmits(['update:from', 'update:to']);
</script>

<template>
    <AppInput :model-value="from" type="date" label="Desde" :error="errors.from || errors.fi" @update:model-value="$emit('update:from', $event)" />
    <AppInput :model-value="to" type="date" label="Hasta" :error="errors.to || errors.ff" @update:model-value="$emit('update:to', $event)" />
</template>
