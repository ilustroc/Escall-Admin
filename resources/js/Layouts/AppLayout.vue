<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppToast from '../Components/AppToast.vue';

defineProps({
    title: { type: String, default: 'Panel de control' },
    subtitle: { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const sidebarOpen = ref(false);
const expanded = ref({
    cargas: page.url.startsWith('/cargas') || page.url.startsWith('/expertis/importaciones'),
    expertis: page.url.startsWith('/expertis'),
    tablas: page.url.startsWith('/tablas') || page.url.startsWith('/expertis/gestiones') || page.url.startsWith('/expertis/pagos'),
});

const user = computed(() => page.props.auth?.user ?? {});
const initials = computed(() => (user.value.name || 'A')
    .split(' ')
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase());

const toast = computed(() => {
    if (page.props.flash?.error) return { message: page.props.flash.error, tone: 'error' };
    if (page.props.flash?.warning) return { message: page.props.flash.warning, tone: 'warning' };
    return { message: page.props.flash?.success ?? '', tone: 'success' };
});

function active(path, exact = false) {
    return exact ? page.url.split('?')[0] === path : page.url.startsWith(path);
}

function closeMobile() {
    sidebarOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-[#F5F7FB]">
        <button
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-[#07142B]/60 lg:hidden"
            aria-label="Cerrar menú"
            @click="sidebarOpen = false"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[272px] flex-col bg-[#07142B] text-white shadow-2xl transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-20 items-center border-b border-white/10 px-6">
                <Link href="/" class="flex items-center gap-3" @click="closeMobile">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-1.5">
                        <img :src="'/img/logotipo-escallperu.png'" alt="ESCALL Perú" class="max-h-full max-w-full object-contain">
                    </span>
                    <span>
                        <strong class="block text-sm tracking-wide">ESCALL PERÚ</strong>
                        <small class="text-[10px] uppercase tracking-[0.2em] text-blue-200">Administración</small>
                    </span>
                </Link>
            </div>

            <nav class="scrollbar-thin flex-1 space-y-6 overflow-y-auto px-4 py-5">
                <section>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Principal</p>
                    <Link
                        href="/"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                        :class="active('/', true) ? 'bg-[#155EEF] text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6" />
                        </svg>
                        Dashboard
                    </Link>
                </section>

                <section>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Operativo</p>
                    <button
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                        @click="expanded.cargas = !expanded.cargas"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="1.8" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
                            </svg>
                            Cargas
                        </span>
                        <span :class="['transition', expanded.cargas ? 'rotate-90' : '']">›</span>
                    </button>
                    <div v-show="expanded.cargas" class="mt-1 space-y-1 border-l border-white/10 pl-4 ml-5">
                        <a href="/cargas?tab=gestiones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white">Gestiones por SP</a>
                        <a href="/cargas?tab=data" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white">Cartera</a>
                        <a href="/cargas?tab=pagos" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white">Pagos actuales</a>
                        <Link
                            href="/expertis/importaciones/gestiones"
                            class="block rounded-lg px-3 py-2 text-xs"
                            :class="active('/expertis/importaciones/gestiones') ? 'bg-[#155EEF] text-white' : 'text-slate-400 hover:bg-white/10 hover:text-white'"
                            @click="closeMobile"
                        >Expertis · Gestiones</Link>
                        <Link
                            href="/expertis/importaciones/pagos"
                            class="block rounded-lg px-3 py-2 text-xs"
                            :class="active('/expertis/importaciones/pagos') ? 'bg-[#155EEF] text-white' : 'text-slate-400 hover:bg-white/10 hover:text-white'"
                            @click="closeMobile"
                        >Expertis · Pagos</Link>
                    </div>

                    <button
                        class="mt-1 flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                        @click="expanded.expertis = !expanded.expertis"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="1.8" d="M5 5h14v14H5zM9 9h6v6H9z" />
                            </svg>
                            Expertis
                        </span>
                        <span :class="['transition', expanded.expertis ? 'rotate-90' : '']">›</span>
                    </button>
                    <div v-show="expanded.expertis" class="mt-1 space-y-1 border-l border-white/10 pl-4 ml-5">
                        <Link href="/expertis" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Resumen</Link>
                        <Link href="/expertis/importaciones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Historial</Link>
                        <Link href="/expertis/gestiones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Gestiones</Link>
                        <Link href="/expertis/pagos" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Pagos</Link>
                        <Link href="/expertis/tipificaciones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Tipificaciones</Link>
                    </div>
                </section>

                <section>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Analítica</p>
                    <button
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                        @click="expanded.tablas = !expanded.tablas"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="1.8" d="M4 5h16M4 12h16M4 19h16M9 5v14" />
                            </svg>
                            Tablas
                        </span>
                        <span :class="['transition', expanded.tablas ? 'rotate-90' : '']">›</span>
                    </button>
                    <div v-show="expanded.tablas" class="mt-1 space-y-1 border-l border-white/10 pl-4 ml-5">
                        <a href="/tablas?tab=gestiones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white">Gestiones actuales</a>
                        <a href="/tablas?tab=pagos" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white">Pagos actuales</a>
                        <Link href="/expertis/gestiones" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Gestiones Expertis</Link>
                        <Link href="/expertis/pagos" class="block rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-white/10 hover:text-white" @click="closeMobile">Pagos Expertis</Link>
                    </div>
                    <a href="/reportes" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="1.8" d="M5 19V9m7 10V5m7 14v-7" />
                        </svg>
                        Reportes actuales
                    </a>
                    <Link href="/expertis/reportes" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white" @click="closeMobile">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="1.8" d="M4 19h16M6 16l4-5 3 3 5-7" />
                        </svg>
                        Reporte Expertis
                    </Link>
                </section>
            </nav>

            <div class="border-t border-white/10 p-4">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/10 hover:text-white"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-width="1.8" d="M14 8V5H5v14h9v-3m-3-4h10m0 0-3-3m3 3-3 3" />
                    </svg>
                    Cerrar sesión
                </Link>
            </div>
        </aside>

        <div class="lg:pl-[272px]">
            <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur md:px-7">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="rounded-xl p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" @click="sidebarOpen = true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <div v-if="breadcrumbs.length" class="mb-0.5 hidden items-center gap-1 text-[11px] text-slate-400 sm:flex">
                            <template v-for="(crumb, index) in breadcrumbs" :key="crumb">
                                <span v-if="index">/</span><span>{{ crumb }}</span>
                            </template>
                        </div>
                        <h1 class="truncate text-lg font-bold text-[#172033]">{{ title }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-bold text-[#172033]">{{ user.name || 'Administrador' }}</p>
                        <p class="text-[11px] text-slate-500">{{ user.email }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-escall-50 text-sm font-bold text-escall-700 ring-1 ring-escall-100">{{ initials }}</span>
                </div>
            </header>

            <main class="p-4 md:p-7">
                <div class="mx-auto max-w-[1680px]">
                    <div v-if="subtitle" class="mb-5">
                        <p class="max-w-3xl text-sm leading-6 text-slate-500">{{ subtitle }}</p>
                    </div>
                    <slot />
                </div>
            </main>
        </div>

        <AppToast :message="toast.message" :tone="toast.tone" />
    </div>
</template>
