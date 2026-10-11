<script setup>
import { computed, ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { confirmDialog, toastError, toastSuccess } from '@/utils/swal';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);
const userRole = computed(() => user.value?.role);

const roleLabel = computed(() => ({
    admin: 'Admin',
    mandor: 'Mandor',
    karyawan: 'Karyawan',
}[userRole.value] ?? userRole.value));

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join(''),
);

// Tampilkan flash message dari server sebagai SweetAlert toast
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toastSuccess(flash.success);
        if (flash?.error) toastError(flash.error);
    },
    { immediate: true, deep: true },
);

async function logout() {
    const ok = await confirmDialog({
        title: 'Keluar dari SILEKA?',
        text: 'Anda perlu login kembali untuk mengakses sistem.',
        confirmText: 'Ya, keluar',
        icon: 'question',
    });
    if (ok) router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <nav class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <!-- Brand -->
                        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2.5">
                            <ApplicationLogo class="h-9 w-9" />
                            <div class="hidden leading-tight md:block">
                                <div class="text-[15px] font-extrabold tracking-tight text-slate-900">SILEKA</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Lembur Karyawan</div>
                            </div>
                        </Link>

                        <!-- Navigation Links -->
                        <div class="hidden h-full sm:ms-8 sm:flex sm:items-center sm:gap-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>

                            <!-- Khusus Mandor: Buat SPL -->
                            <NavLink
                                v-if="userRole === 'mandor'"
                                :href="route('lembur.create')"
                                :active="route().current('lembur.create')"
                            >
                                Buat SPL
                            </NavLink>

                            <NavLink
                                v-if="userRole === 'karyawan'"
                                :href="route('lembur.riwayat')"
                                :active="route().current('lembur.riwayat')"
                            >
                                Riwayat Lembur
                            </NavLink>

                            <NavLink
                                v-if="userRole === 'admin'"
                                :href="route('admin.karyawan')"
                                :active="route().current('admin.karyawan')"
                            >
                                Kelola Karyawan
                            </NavLink>

                            <NavLink
                                v-if="userRole === 'admin'"
                                :href="route('admin.stasiun')"
                                :active="route().current('admin.stasiun')"
                            >
                                Kelola Stasiun
                            </NavLink>

                            <NavLink
                                v-if="userRole !== 'karyawan'"
                                :href="route('lembur.print')"
                                :active="route().current('lembur.print')"
                            >
                                Cetak SPL
                            </NavLink>

                            <NavLink
                                v-if="userRole !== 'karyawan'"
                                :href="route('lembur.lrlk')"
                                :active="route().current('lembur.lrlk') || route().current('lembur.lrlk.print')"
                            >
                                LRLK (Realisasi)
                            </NavLink>

                            <a
                                href="/panduan"
                                target="_blank"
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                            >
                                Panduan
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </a>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="hidden sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    id="user-menu-button"
                                    class="flex items-center gap-3 rounded-xl border border-transparent py-1.5 pl-1.5 pr-3 transition hover:border-slate-200 hover:bg-slate-50 focus:outline-none"
                                >
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-800">
                                        {{ initials }}
                                    </span>
                                    <span class="text-left leading-tight">
                                        <span class="block max-w-[140px] truncate text-sm font-semibold text-slate-800">{{ user.name }}</span>
                                        <span class="block text-[11px] font-medium text-slate-500">{{ roleLabel }}</span>
                                    </span>
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="border-b border-slate-100 px-4 py-2.5">
                                    <div class="truncate text-xs text-slate-500">{{ user.email }}</div>
                                </div>
                                <DropdownLink :href="route('profile.edit')">Profil Saya</DropdownLink>
                                <button
                                    type="button"
                                    @click="logout"
                                    class="block w-full px-4 py-2 text-start text-sm font-medium text-red-600 transition hover:bg-red-50"
                                >
                                    Keluar
                                </button>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="border-t border-slate-100 sm:hidden">
                <div class="space-y-1 px-3 py-3">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole === 'mandor'" :href="route('lembur.create')" :active="route().current('lembur.create')">Buat SPL</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole === 'karyawan'" :href="route('lembur.riwayat')" :active="route().current('lembur.riwayat')">Riwayat Lembur</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole === 'admin'" :href="route('admin.karyawan')" :active="route().current('admin.karyawan')">Kelola Karyawan</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole === 'admin'" :href="route('admin.stasiun')" :active="route().current('admin.stasiun')">Kelola Stasiun</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole !== 'karyawan'" :href="route('lembur.print')" :active="route().current('lembur.print')">Cetak SPL</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="userRole !== 'karyawan'" :href="route('lembur.lrlk')" :active="route().current('lembur.lrlk') || route().current('lembur.lrlk.print')">LRLK (Realisasi)</ResponsiveNavLink>
                    <a href="/panduan" target="_blank" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-600 hover:bg-slate-50">Panduan ↗</a>
                </div>

                <div class="border-t border-slate-100 px-4 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-sm font-bold text-emerald-800">{{ initials }}</span>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">{{ user.name }}</div>
                            <div class="text-xs text-slate-500">{{ roleLabel }} · {{ user.email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profil Saya</ResponsiveNavLink>
                        <button type="button" @click="logout" class="block w-full rounded-lg px-3 py-2 text-start text-base font-medium text-red-600 hover:bg-red-50">Keluar</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
