<script setup>
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import AppButton from './AppButton.vue';
defineProps({ href: { type: String, required: true }, label: { type: String, default: 'Exportar' } });
</script>

<template><AppButton :href="href" variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" />{{ label }}</AppButton></template>
