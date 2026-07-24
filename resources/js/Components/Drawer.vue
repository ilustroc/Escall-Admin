<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';
defineProps({ open: { type: Boolean, default: false }, title: { type: String, default: '' } });
defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[70]">
            <button class="absolute inset-0 bg-[#07142B]/50" aria-label="Cerrar" @click="$emit('close')" />
            <aside class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                <header class="mb-6 flex items-center justify-between"><h2 class="text-lg font-bold">{{ title }}</h2><button class="rounded-lg p-2 hover:bg-slate-100" @click="$emit('close')"><XMarkIcon class="h-5 w-5" /></button></header>
                <slot />
            </aside>
        </div>
    </Teleport>
</template>
