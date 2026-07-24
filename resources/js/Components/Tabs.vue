<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ items: { type: Array, required: true }, active: { type: String, required: true } });
</script>

<template>
    <nav class="mb-5 flex gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1">
        <Link v-for="item in items" :key="item.value" :href="item.href" preserve-state preserve-scroll class="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold" :class="active === item.value ? 'bg-white text-[#073DC7] shadow-sm' : 'text-slate-500 hover:text-slate-800'">{{ item.label }}</Link>
    </nav>
</template>
