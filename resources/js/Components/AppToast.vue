<script setup>
import { computed, ref, watch } from 'vue';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    message: { type: String, default: '' },
    tone: { type: String, default: 'success' },
});

const visible = ref(Boolean(props.message));
let timer;
const icon = computed(() => ({
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    error: XCircleIcon,
    info: InformationCircleIcon,
}[props.tone] ?? InformationCircleIcon));
const color = computed(() => ({
    success: 'bg-[#12B76A]',
    warning: 'bg-[#F79009]',
    error: 'bg-[#F04438]',
    info: 'bg-[#155EEF]',
}[props.tone] ?? 'bg-[#155EEF]'));

watch(() => props.message, (value) => {
    clearTimeout(timer);
    visible.value = Boolean(value);
    if (value) timer = setTimeout(() => { visible.value = false; }, 6000);
}, { immediate: true });
</script>

<template>
    <Transition enter-active-class="transition duration-200" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-150" leave-to-class="translate-y-2 opacity-0">
        <div
            v-if="visible && message"
            class="fixed bottom-5 right-5 z-[80] flex max-w-sm items-start gap-3 rounded-xl border bg-white p-4 shadow-2xl"
            :class="{
                'border-emerald-200': tone === 'success',
                'border-amber-200': tone === 'warning',
                'border-red-200': tone === 'error',
                'border-blue-200': tone === 'info',
            }"
            role="status"
        >
            <span
                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                :class="color"
            ><component :is="icon" class="h-4 w-4" /></span>
            <p class="text-sm font-medium text-[#172033]">{{ message }}</p>
            <button class="ml-auto text-slate-400 hover:text-slate-700" aria-label="Cerrar" @click="visible = false"><XMarkIcon class="h-4 w-4" /></button>
        </div>
    </Transition>
</template>
