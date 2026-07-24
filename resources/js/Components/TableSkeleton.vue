<script setup>
defineProps({ rows: { type: Number, default: 6 }, columns: { type: Number, default: 5 } });
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-4">
        <div v-for="row in rows" :key="row" class="mb-3 grid gap-3 last:mb-0" :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }">
            <span v-for="column in columns" :key="column" class="skeleton h-8" />
        </div>
    </div>
</template>
