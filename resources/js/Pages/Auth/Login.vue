<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toastError, toastSuccess } from '@/utils/swal';

const props = defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

if (props.status) toastSuccess(props.status);

watch(
    () => form.errors.email,
    (msg) => {
        if (msg) toastError('Email atau password salah.');
    },
);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk" />

    <div class="min-h-screen grid lg:grid-cols-2 bg-white">
        <!-- Panel kiri: branding -->
        <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-800 p-12 text-white">
            <!-- dekorasi -->
            <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-teal-300/10 blur-3xl"></div>
            <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.07]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="32" height="32" patternUnits="userSpaceOnUse">
                        <path d="M32 0H0V32" fill="none" stroke="white" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>

            <div class="relative flex items-center gap-3">
                <ApplicationLogo class="h-11 w-11 drop-shadow" />
                <div class="leading-tight">
                    <div class="text-xl font-extrabold tracking-tight">SILEKA</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-200">Sistem Informasi Lembur Karyawan</div>
                </div>
            </div>

            <div class="relative max-w-md">
                <h2 class="text-4xl font-extrabold leading-tight tracking-tight">
                    Pencatatan lembur pabrik,<br />
                    <span class="text-emerald-200">tanpa tumpukan kertas.</span>
                </h2>
                <p class="mt-5 text-emerald-50/80 leading-relaxed">
                    Mandor menyusun SPL regu shift, karyawan memvalidasi dari HP, dan admin langsung menerima rekap otomatis — tanpa ketik ulang ke Excel.
                </p>

                <ul class="mt-8 space-y-3 text-sm">
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        SPL otomatis per regu shift
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        Validasi lembur mandiri dari smartphone
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        Riwayat & rekap tersimpan rapi di sistem
                    </li>
                </ul>
            </div>

            <div class="relative text-xs text-emerald-100/60">
                © {{ new Date().getFullYear() }} SILEKA · Pabrik Kelapa Sawit
            </div>
        </aside>

        <!-- Panel kanan: form -->
        <main class="flex items-center justify-center px-6 py-12 sm:px-12 bg-slate-50 lg:bg-white">
            <div class="w-full max-w-sm">
                <!-- logo mobile -->
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <ApplicationLogo class="h-10 w-10" />
                    <div class="leading-tight">
                        <div class="text-lg font-extrabold tracking-tight text-slate-900">SILEKA</div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Sistem Informasi Lembur</div>
                    </div>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Selamat datang kembali</h1>
                <p class="mt-1.5 text-sm text-slate-500">Masuk menggunakan akun yang diberikan admin.</p>

                <form @submit.prevent="submit" class="mt-8 space-y-5">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                            </span>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@perusahaan.com"
                                class="block w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15"
                                :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-500/15': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                            >
                                Lupa password?
                            </Link>
                        </div>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            </span>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="block w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-11 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15"
                            />
                            <button
                                type="button"
                                id="toggle-password"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            >
                                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex cursor-pointer select-none items-center gap-2.5">
                        <input
                            type="checkbox"
                            v-model="form.remember"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/30"
                        />
                        <span class="text-sm text-slate-600">Ingat saya di perangkat ini</span>
                    </label>

                    <button
                        type="submit"
                        id="btn-login"
                        :disabled="form.processing"
                        class="group flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/25 transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg>
                        <span>{{ form.processing ? 'Memproses…' : 'Masuk' }}</span>
                        <svg v-if="!form.processing" class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </button>
                </form>

                <p class="mt-10 text-center text-xs text-slate-400">
                    Belum punya akun? Hubungi admin personalia pabrik.
                </p>
            </div>
        </main>
    </div>
</template>
