<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxWidth: { type: String, default: 'max-w-2xl' },
});

defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <button class="absolute inset-0 bg-[#07142B]/60 backdrop-blur-sm" aria-label="Cerrar" @click="$emit('close')" />
            <section :class="['relative max-h-[90vh] w-full overflow-y-auto rounded-2xl bg-white shadow-2xl', maxWidth]">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-[#172033]">{{ title }}</h2>
                    <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar" @click="$emit('close')">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>
                </header>
                <div class="p-5"><slot /></div>
                <footer v-if="$slots.footer" class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                    <slot name="footer" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>
