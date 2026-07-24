<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, default: null },
    method: { type: String, default: 'get' },
    variant: { type: String, default: 'primary' },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const classes = computed(() => {
    const variants = {
        primary: 'bg-escall-600 text-white hover:bg-escall-700 border-escall-600',
        secondary: 'bg-white text-[#172033] hover:bg-slate-50 border-slate-300',
        danger: 'bg-[#F04438] text-white hover:bg-red-700 border-[#F04438]',
        ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 border-transparent',
        success: 'bg-[#12B76A] text-white hover:bg-emerald-700 border-[#12B76A]',
    };
    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2.5 text-sm',
        lg: 'px-5 py-3 text-sm',
    };

    return [
        'inline-flex items-center justify-center gap-2 rounded-lg border font-semibold shadow-sm transition focus-visible:ring-2 focus-visible:ring-escall-500 disabled:pointer-events-none disabled:opacity-50',
        variants[props.variant] ?? variants.primary,
        sizes[props.size] ?? sizes.md,
    ];
});
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        :as="method === 'get' ? 'a' : 'button'"
        :class="classes"
        :disabled="disabled || loading"
    >
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
        </svg>
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :class="classes"
        :disabled="disabled || loading"
    >
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
        </svg>
        <slot />
    </button>
</template>
