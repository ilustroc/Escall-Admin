<script setup>
defineProps({ modelValue: { type: String, default: '' }, label: { type: String, default: '' }, error: { type: String, default: '' }, rows: { type: Number, default: 3 } });
defineEmits(['update:modelValue']);
</script>

<template>
    <FormField :label="label" :error="error">
        <textarea :value="modelValue" :rows="rows" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#155EEF] focus:ring-2 focus:ring-blue-100" @input="$emit('update:modelValue', $event.target.value)" />
    </FormField>
</template>

<script>
import FormField from './FormField.vue';
export default { components: { FormField } };
</script>
