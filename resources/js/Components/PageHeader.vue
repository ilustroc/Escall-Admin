<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <h2 class="text-xl font-bold text-[#172033]">{{ title }}</h2>
            <p v-if="description" class="mt-1 max-w-3xl text-sm leading-6 text-[#667085]">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap gap-2"><slot name="actions" /></div>
    </div>
</template>
