<template>
    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><slot /></div>
        <div v-if="$slots.actions" class="mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4"><slot name="actions" /></div>
    </section>
</template>
