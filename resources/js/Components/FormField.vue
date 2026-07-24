<script setup>
defineProps({ label: { type: String, default: '' }, error: { type: String, default: '' }, hint: { type: String, default: '' } });
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1.5 block text-xs font-semibold text-slate-700">{{ label }}</span>
        <slot />
        <span v-if="error" class="mt-1 block text-xs text-[#F04438]">{{ error }}</span>
        <span v-else-if="hint" class="mt-1 block text-xs text-slate-500">{{ hint }}</span>
    </label>
</template>
