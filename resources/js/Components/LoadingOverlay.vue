<script setup>
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
                    <svg class="mx-auto h-10 w-10 animate-spin text-escall-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-20" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                        <path fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                    </svg>
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
