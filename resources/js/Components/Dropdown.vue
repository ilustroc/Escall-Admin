<script setup>
import { ref } from 'vue';
const open = ref(false);
</script>

<template>
    <div class="relative">
        <button type="button" @click="open = !open"><slot name="trigger" /></button>
        <div v-if="open" class="absolute right-0 z-20 mt-2 min-w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl" @click="open = false"><slot /></div>
    </div>
</template>
