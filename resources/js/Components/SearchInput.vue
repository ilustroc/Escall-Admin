<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
defineProps({ modelValue: { type: String, default: '' }, placeholder: { type: String, default: 'Buscar…' } });
defineEmits(['update:modelValue']);
</script>

<template>
    <label class="relative block">
        <span class="sr-only">Buscar</span>
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input :value="modelValue" :placeholder="placeholder" class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm shadow-sm focus:border-[#155EEF] focus:ring-2 focus:ring-blue-100" @input="$emit('update:modelValue', $event.target.value)">
    </label>
</template>
