<script setup>
import AppBadge from './AppBadge.vue';
const props = defineProps({ status: { type: String, default: '' } });
const tones = { completado: 'success', procesado: 'success', error: 'danger', fallido: 'danger', pendiente: 'warning', procesando: 'info' };
</script>

<template><AppBadge :tone="tones[status.toLowerCase()] || 'neutral'">{{ status || 'Sin estado' }}</AppBadge></template>
