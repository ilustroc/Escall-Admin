<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: { type: Object, default: null },
    accept: { type: String, default: '.xlsx' },
    maxMb: { type: Number, default: 50 },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change']);
const input = ref(null);
const dragging = ref(false);

function choose(file) {
    if (!file) return;
    emit('update:modelValue', file);
    emit('change', file);
}

function drop(event) {
    dragging.value = false;
    choose(event.dataTransfer.files?.[0]);
}

function formatBytes(bytes) {
    if (!bytes) return '0 KB';
    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}
</script>

<template>
    <div>
        <button
            type="button"
            class="group flex w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition"
            :class="[
                dragging ? 'border-escall-500 bg-escall-50' : 'border-slate-300 bg-white hover:border-escall-400 hover:bg-escall-50/40',
                error ? 'border-[#F04438]' : '',
            ]"
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="drop"
        >
            <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-escall-50 text-escall-600 transition group-hover:bg-escall-100">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
                </svg>
            </span>
            <template v-if="modelValue">
                <span class="text-sm font-bold text-[#172033]">{{ modelValue.name }}</span>
                <span class="mt-1 text-xs text-slate-500">{{ formatBytes(modelValue.size) }}</span>
                <span class="mt-3 text-xs font-semibold text-escall-600">Cambiar archivo</span>
            </template>
            <template v-else>
                <span class="text-sm font-bold text-[#172033]">Arrastra tu archivo XLSX aquí</span>
                <span class="mt-1 text-xs text-slate-500">o haz clic para seleccionarlo · máximo {{ maxMb }} MB</span>
            </template>
        </button>
        <input ref="input" type="file" class="hidden" :accept="accept" @change="choose($event.target.files?.[0])">
        <p v-if="error" class="mt-2 text-xs text-[#F04438]">{{ error }}</p>
    </div>
</template>
