<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <Link href="/" class="flex items-center justify-center gap-3 mb-8">
                <ApplicationLogo class="h-11 w-11" />
                <div class="leading-tight">
                    <div class="text-xl font-extrabold tracking-tight text-slate-900">SILEKA</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Sistem Informasi Lembur</div>
                </div>
            </Link>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-200/60 px-7 py-8">
                <slot />
            </div>
        </div>
    </div>
</template>
