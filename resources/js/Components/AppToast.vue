<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    message: { type: String, default: '' },
    tone: { type: String, default: 'success' },
});

const visible = ref(Boolean(props.message));
let timer;

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
            }"
            role="status"
        >
            <span
                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                :class="tone === 'success' ? 'bg-[#12B76A]' : tone === 'warning' ? 'bg-[#F79009]' : 'bg-[#F04438]'"
            >{{ tone === 'success' ? '✓' : tone === 'warning' ? '!' : '×' }}</span>
            <p class="text-sm font-medium text-[#172033]">{{ message }}</p>
            <button class="ml-auto text-slate-400 hover:text-slate-700" @click="visible = false">×</button>
        </div>
    </Transition>
</template>
