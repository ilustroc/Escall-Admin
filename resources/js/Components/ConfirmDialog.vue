<script setup>
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar acción' },
    message: { type: String, default: '¿Deseas continuar?' },
    confirmText: { type: String, default: 'Confirmar' },
    tone: { type: String, default: 'primary' },
    loading: { type: Boolean, default: false },
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <AppModal :open="open" :title="title" max-width="max-w-md" @close="$emit('close')">
        <p class="text-sm leading-6 text-slate-600">{{ message }}</p>
        <template #footer>
            <AppButton variant="secondary" @click="$emit('close')">Cancelar</AppButton>
            <AppButton :variant="tone" :loading="loading" @click="$emit('confirm')">{{ confirmText }}</AppButton>
        </template>
    </AppModal>
</template>
