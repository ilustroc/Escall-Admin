<script setup>
import AppInput from './AppInput.vue';
defineProps({ modelValue: { type: [String, Number], default: '' }, label: { type: String, default: 'Monto' }, error: { type: String, default: '' } });
defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-[34px] text-sm text-slate-500">S/</span>
        <AppInput :model-value="modelValue" type="number" min="0" step="0.01" :label="label" :error="error" class="[&_input]:pl-9" @update:model-value="$emit('update:modelValue', $event)" />
    </div>
</template>
