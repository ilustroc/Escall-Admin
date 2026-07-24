<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

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
        <ArrowPathIcon v-if="loading" class="h-4 w-4 animate-spin" />
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :class="classes"
        :disabled="disabled || loading"
    >
        <ArrowPathIcon v-if="loading" class="h-4 w-4 animate-spin" />
        <slot />
    </button>
</template>
