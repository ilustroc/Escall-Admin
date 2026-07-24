<script setup>
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
defineProps({
    show: { type: Boolean, default: false },
    message: { type: String, default: 'Procesando…' },
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-[90] flex items-center justify-center bg-[#07142B]/65 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
                    <ArrowPathIcon class="mx-auto h-10 w-10 animate-spin text-escall-600" />
                    <p class="mt-4 text-sm font-bold text-[#172033]">{{ message }}</p>
                    <p class="mt-1 text-xs text-slate-500">No cierres esta ventana hasta recibir el resumen.</p>
                    <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full w-1/2 animate-pulse rounded-full bg-escall-500" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
