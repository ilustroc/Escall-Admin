<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    min: { type: [String, Number], default: undefined },
    max: { type: [String, Number], default: undefined },
    step: { type: [String, Number], default: undefined },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1.5 block text-xs font-semibold text-slate-700">{{ label }}</span>
        <input
            :value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :min="min"
            :max="max"
            :step="step"
            class="w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-[#172033] shadow-sm outline-none transition placeholder:text-slate-400 focus:border-escall-500 focus:ring-2 focus:ring-escall-100 disabled:bg-slate-100"
            :class="error ? 'border-[#F04438]' : 'border-slate-300'"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <span v-if="error" class="mt-1 block text-xs text-[#F04438]">{{ error }}</span>
        <span v-else-if="hint" class="mt-1 block text-xs text-slate-500">{{ hint }}</span>
    </label>
</template>
