<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../Components/AppButton.vue';
import AppInput from '../../Components/AppInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar sesión" />
    <GuestLayout title="Bienvenido">
        <form class="space-y-5" @submit.prevent="submit">
            <AppInput v-model="form.email" type="email" label="Correo electrónico" placeholder="nombre@escall.pe" :error="form.errors.email" />
            <AppInput v-model="form.password" type="password" label="Contraseña" placeholder="••••••••" :error="form.errors.password" />
            <label class="flex items-center gap-2 text-sm text-[#667085]">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-[#073DC7] focus:ring-[#155EEF]">
                Recordar sesión
            </label>
            <AppButton type="submit" class="w-full" :loading="form.processing">
                <LockClosedIcon class="h-4 w-4" />
                Iniciar sesión
            </AppButton>
        </form>
    </GuestLayout>
</template>
