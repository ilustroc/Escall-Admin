<script setup>
defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Todos' },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1.5 block text-xs font-semibold text-slate-700">{{ label }}</span>
        <select
            :value="modelValue"
            :disabled="disabled"
            class="w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-[#172033] shadow-sm outline-none transition focus:border-escall-500 focus:ring-2 focus:ring-escall-100 disabled:bg-slate-100"
            :class="error ? 'border-[#F04438]' : 'border-slate-300'"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="">{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="typeof option === 'object' ? option.value : option"
                :value="typeof option === 'object' ? option.value : option"
            >
                {{ typeof option === 'object' ? option.label : option }}
            </option>
        </select>
        <span v-if="error" class="mt-1 block text-xs text-[#F04438]">{{ error }}</span>
    </label>
</template>
