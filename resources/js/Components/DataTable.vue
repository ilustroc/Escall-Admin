<script setup>
import AppTable from './AppTable.vue';
defineProps({ columns: { type: Array, required: true }, rows: { type: Array, default: () => [] }, rowKey: { type: String, default: 'id' }, loading: { type: Boolean, default: false } });
</script>

<template>
    <div v-if="loading" class="space-y-2 rounded-xl border border-slate-200 bg-white p-4">
        <div v-for="index in 6" :key="index" class="skeleton h-10" />
    </div>
    <AppTable v-else :columns="columns" :rows="rows" :row-key="rowKey">
        <template v-for="column in columns" #[`cell-${column.key}`]="slotProps">
            <slot :name="`cell-${column.key}`" v-bind="slotProps">{{ slotProps.value ?? '—' }}</slot>
        </template>
        <template #empty><slot name="empty">No hay registros para mostrar.</slot></template>
    </AppTable>
</template>
