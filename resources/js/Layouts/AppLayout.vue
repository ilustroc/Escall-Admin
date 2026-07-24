<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    ChevronDownIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import AppToast from '../Components/AppToast.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import { navigation } from '../Config/navigation';

defineProps({
    title: { type: String, default: 'Panel de control' },
    subtitle: { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const expanded = reactive({});
const path = computed(() => page.url.split('?')[0]);
const user = computed(() => page.props.auth?.user ?? {});
const initials = computed(() => (user.value.name || 'A')
    .split(' ')
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase());
const toast = computed(() => {
    const flash = page.props.flash ?? {};
    for (const tone of ['error', 'warning', 'info', 'success']) {
        if (flash[tone]) return { message: flash[tone], tone };
    }
    return { message: '', tone: 'success' };
});

function isActive(item) {
    if (item.href?.includes('?')) return page.url.startsWith(item.href);
    if (item.exact) return path.value === item.href?.split('?')[0];
    if (item.href) return path.value.startsWith(item.href.split('?')[0]);
    return (item.match ?? []).some((prefix) => path.value.startsWith(prefix));
}

function isExpanded(item) {
    return expanded[item.label] ?? isActive(item);
}

function toggle(item) {
    expanded[item.label] = !isExpanded(item);
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
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-[#07142B] text-white shadow-2xl transition-all duration-300 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'w-20' : 'w-[272px]',
            ]"
        >
            <div
                class="flex h-20 items-center border-b border-white/10 px-4"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
                <Link
                    href="/"
                    class="flex min-w-0 items-center justify-center overflow-hidden"
                    @click="closeMobile">
                    <img
                        :src="'/img/logotipo-escallperu.png'"
                        alt="ESCALL Perú"
                        class="w-auto object-contain transition-all duration-300"
                        :class="sidebarCollapsed
                            ? 'h-11 max-w-12'
                            : 'h-14 max-w-[205px]'">
                </Link>

                <button
                    v-if="!sidebarCollapsed"
                    type="button"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Cerrar menú"
                    @click="closeMobile"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <nav class="scrollbar-thin flex-1 space-y-5 overflow-y-auto px-3 py-5">
                <section v-for="section in navigation" :key="section.label">
                    <p v-if="!sidebarCollapsed" class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                        {{ section.label }}
                    </p>
                    <div class="space-y-1">
                        <template v-for="item in section.items" :key="item.label">
                            <Link
                                v-if="!item.children"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                                :class="isActive(item) ? 'bg-[#155EEF] text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                                :title="sidebarCollapsed ? item.label : undefined"
                                @click="closeMobile"
                            >
                                <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                <span v-if="!sidebarCollapsed">{{ item.label }}</span>
                            </Link>
                            <div v-else>
                                <button
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                                    :class="isActive(item) ? 'text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                                    :title="sidebarCollapsed ? item.label : undefined"
                                    @click="toggle(item)"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    <span v-if="!sidebarCollapsed" class="flex-1 text-left">{{ item.label }}</span>
                                    <ChevronDownIcon v-if="!sidebarCollapsed" class="h-4 w-4 transition" :class="isExpanded(item) && 'rotate-180'" />
                                </button>
                                <div v-if="!sidebarCollapsed && isExpanded(item)" class="ml-5 mt-1 space-y-1 border-l border-white/10 pl-4">
                                    <Link
                                        v-for="child in item.children"
                                        :key="child.label"
                                        :href="child.href"
                                        class="block rounded-lg px-3 py-2 text-xs transition"
                                        :class="isActive(child) ? 'bg-[#155EEF] text-white' : 'text-slate-400 hover:bg-white/10 hover:text-white'"
                                        @click="closeMobile"
                                    >
                                        {{ child.label }}
                                    </Link>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </nav>

            <div class="border-t border-white/10 p-3">
                <Link href="/logout" method="post" as="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/10 hover:text-white">
                    <ArrowRightOnRectangleIcon class="h-5 w-5 shrink-0" />
                    <span v-if="!sidebarCollapsed">Cerrar sesión</span>
                </Link>
            </div>
        </aside>

        <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-[272px]'">
            <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur md:px-7">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="rounded-xl p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" @click="sidebarOpen = true">
                        <Bars3Icon class="h-6 w-6" />
                    </button>
                    <button class="hidden rounded-xl p-2 text-slate-500 hover:bg-slate-100 lg:block" aria-label="Contraer menú" @click="sidebarCollapsed = !sidebarCollapsed">
                        <Bars3Icon class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        <Breadcrumbs :items="breadcrumbs" />
                        <h1 class="truncate text-lg font-bold text-[#172033]">{{ title }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-bold text-[#172033]">{{ user.name || 'Administrador' }}</p>
                        <p class="text-[11px] text-slate-500">{{ user.email }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EEF4FF] text-sm font-bold text-[#073DC7] ring-1 ring-blue-100">{{ initials }}</span>
                </div>
            </header>

            <main class="p-4 md:p-7">
                <div class="mx-auto max-w-[1680px]">
                    <p v-if="subtitle" class="mb-5 max-w-3xl text-sm leading-6 text-slate-500">{{ subtitle }}</p>
                    <slot />
                </div>
            </main>
        </div>

        <AppToast :message="toast.message" :tone="toast.tone" />
    </div>
</template>
