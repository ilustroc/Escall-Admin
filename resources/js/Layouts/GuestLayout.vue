<script setup>
defineProps({
    title: { type: String, default: 'Acceso administrativo' },
});
</script>

<template>
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#07142B] px-4 py-10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(21,94,239,.38),_transparent_38%)]" />
        <section class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl sm:p-9">
            <div class="mb-8 text-center">
                <span class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white p-2 shadow-lg ring-1 ring-slate-100">
                    <img :src="'/img/logotipo-escallperu.png'" alt="ESCALL Perú" class="max-h-full object-contain">
                </span>
                <h1 class="text-2xl font-bold text-[#172033]">{{ title }}</h1>
                <p class="mt-2 text-sm text-[#667085]">Ingresa con tus credenciales para continuar.</p>
            </div>
            <slot />
        </section>
    </main>
</template>
