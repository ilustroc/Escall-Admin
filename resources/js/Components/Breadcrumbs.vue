<script setup>
defineProps({ items: { type: Array, default: () => [] } });
</script>

<template>
    <nav v-if="items.length" class="mb-0.5 hidden items-center gap-1 text-[11px] text-slate-400 sm:flex" aria-label="Migas de pan">
        <template v-for="(item, index) in items" :key="typeof item === 'string' ? item : item.label">
            <span v-if="index">/</span>
            <span>{{ typeof item === 'string' ? item : item.label }}</span>
        </template>
    </nav>
</template>
