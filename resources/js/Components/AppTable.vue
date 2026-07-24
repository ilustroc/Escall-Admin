<script setup>
defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
        <div class="scrollbar-thin overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-slate-500"
                            :class="column.class"
                        >
                            <slot :name="`head-${column.key}`" :column="column">{{ column.label }}</slot>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template v-for="(row, rowIndex) in rows" :key="row[rowKey] ?? rowIndex">
                        <tr class="hover:bg-escall-50/40">
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                class="whitespace-nowrap px-4 py-3 text-slate-700"
                                :class="column.cellClass"
                            >
                                <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                    {{ row[column.key] ?? '—' }}
                                </slot>
                            </td>
                        </tr>
                        <slot name="after-row" :row="row" :colspan="columns.length" />
                    </template>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length" class="px-4 py-12 text-center text-sm text-slate-500">
                            <slot name="empty">No hay registros para mostrar.</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
