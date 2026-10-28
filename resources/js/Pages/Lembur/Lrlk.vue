<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { runTourIfRequested } from '@/utils/tour';

const props = defineProps({
    items: Array,
    tanggal: String,
    totalJamDibayar: Number,
    totalUpahLembur: Number,
    userRole: String,
});

const selectedTanggal = ref(props.tanggal);

// Cetak SPL & LRLK hanya untuk admin
const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin');
const currentRole = computed(() => props.userRole || usePage().props.auth?.user?.role);

onMounted(() => {
    runTourIfRequested('lrlk', currentRole.value);
});

function filterTanggal() {
    router.get(route('lembur.lrlk'), { tanggal: selectedTanggal.value }, { preserveState: true });
}

const countLembur = computed(() => props.items.filter(i => i.jam_dibayar !== '-').length);
const countTotal = computed(() => props.items.length);

function formatRupiah(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function formatTanggalIndo(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

<template>
    <Head title="LRLK (Realisasi)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                            PROS-HCM-02
                        </span>
                        <h2 class="font-bold text-xl text-slate-900 leading-tight">
                            Laporan Realisasi Kerja Lembur (LRLK)
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Otomatis terisi dari data Surat Perintah Kerja Lembur (SPL) tanggal terpilih.
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div id="tour-lrlk-filter" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <label class="text-xs font-semibold text-slate-600">Tanggal:</label>
                        <input
                            type="date"
                            v-model="selectedTanggal"
                            @change="filterTanggal"
                            class="text-xs border-0 p-0 text-slate-800 font-semibold focus:ring-0 cursor-pointer"
                        />
                    </div>

                    <Link
                        :href="route('dashboard', { tanggal: selectedTanggal })"
                        class="bg-white text-slate-700 border border-slate-200 px-3.5 py-2 rounded-xl hover:bg-slate-50 text-xs font-semibold transition shadow-sm flex items-center gap-1.5"
                    >
                        <span>📄</span> Kembali ke SPL
                    </Link>

                    <template v-if="isAdmin">
                        <Link
                            id="tour-lrlk-cetak"
                            :href="route('lembur.lrlk.print', { tanggal: selectedTanggal })"
                            class="bg-emerald-700 text-white px-4 py-2 rounded-xl hover:bg-emerald-800 text-xs font-bold transition shadow-sm shadow-emerald-700/20 flex items-center gap-1.5"
                        >
                            <span>🖨️</span> Cetak LRLK
                        </Link>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Ringkasan Statistik -->
                <div id="tour-lrlk-stats" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold">
                            ⏱️
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ totalJamDibayar }} Jam</div>
                            <div class="text-xs font-medium text-slate-500">Total Jam Sesuai SPL (Tanpa Pengali)</div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl font-bold">
                            👥
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ countLembur }} / {{ countTotal }}</div>
                            <div class="text-xs font-medium text-slate-500">Karyawan Lembur / Total Regu</div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl font-bold">
                            📅
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900">{{ formatTanggalIndo(selectedTanggal) }}</div>
                            <div class="text-xs font-medium text-slate-500">Departemen Pabrik · PT. Letawa</div>
                        </div>
                    </div>
                </div>


                <!-- Tabel LRLK -->
                <div id="tour-lrlk-tabel" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm">
                                Tabel Realisasi Kerja Lembur — {{ formatTanggalIndo(selectedTanggal) }}
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Sesuai format formulir fisik PT. Letawa (Kode Dokumen PROS-HCM-02)
                            </p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">
                            {{ items.length }} Data Tercatat
                        </span>
                    </div>

                    <!-- Tabel Desktop -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-3 py-3 text-center w-10">No</th>
                                    <th class="px-3 py-3 text-left w-24">No. Employe</th>
                                    <th class="px-3 py-3 text-left">Nama Karyawan</th>
                                    <th class="px-3 py-3 text-center w-20">Dept</th>
                                    <th class="px-3 py-3 text-center w-20">Tgl</th>
                                    <th class="px-3 py-3 text-left">Jenis Pekerjaan</th>
                                    <th class="px-3 py-3 text-center w-28 bg-emerald-50/50 text-emerald-900 font-extrabold">Jml Jam Dibayar</th>
                                    <th class="px-3 py-3 text-center w-28 bg-emerald-50/50 text-emerald-900 font-extrabold">Jumlah (Rp)</th>
                                    <th class="px-3 py-3 text-left w-32">Hasil Kerja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(item, idx) in items"
                                    :key="item.id || idx"
                                    class="hover:bg-slate-50/70 transition"
                                    :class="{ 'bg-slate-50/40': item.jam_dibayar === '-' }"
                                >
                                    <td class="px-3 py-2.5 text-center text-slate-400 font-semibold">{{ item.no }}</td>
                                    <td class="px-3 py-2.5 font-mono text-[11px] text-slate-700 font-semibold">{{ item.no_employe }}</td>
                                    <td class="px-3 py-2.5 font-bold text-slate-900 whitespace-nowrap">{{ item.nama }}</td>
                                    <td class="px-3 py-2.5 text-center text-slate-600">{{ item.dept }}</td>
                                    <td class="px-3 py-2.5 text-center text-slate-600 font-mono text-[11px]">{{ item.tgl }}</td>
                                    <td class="px-3 py-2.5 text-slate-800">
                                        <span :class="{ 'text-slate-400 italic': item.jam_dibayar === '-' }">
                                            {{ item.jenis_pekerjaan }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-extrabold text-sm"
                                        :class="item.jam_dibayar === '-' ? 'text-slate-300' : 'text-emerald-700 bg-emerald-50/30'">
                                        <div>{{ item.jam_dibayar }}</div>
                                        <div v-if="item.has_sesi_2" class="text-[9px] text-blue-700 font-semibold leading-tight">
                                            ({{ item.durasi_sesi_1 }} + {{ item.durasi_sesi_2 }})
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold text-slate-800 text-[11px]">
                                        {{ item.jumlah_rp }}
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-600 text-[11px]">
                                        <span v-if="item.foto_dokumentasi" class="inline-flex items-center gap-1 font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                            📷 Ada Foto
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                                        Belum ada data lembur (SPL) yang tercatat pada tanggal {{ formatTanggalIndo(selectedTanggal) }}.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tampilan Card Mobile (HP / Smartphone) -->
                    <div class="block md:hidden divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in items"
                            :key="item.id || idx"
                            class="p-4 space-y-2 transition"
                            :class="item.jam_dibayar !== '-' ? 'bg-white' : 'bg-slate-50/60'"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ item.no }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ item.nama }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">{{ item.no_employe }}</span>
                                            <span class="text-[10px] font-semibold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">{{ item.dept }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-lg text-xs font-black tracking-wide"
                                        :class="item.jam_dibayar !== '-' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400'"
                                    >
                                        {{ item.jam_dibayar !== '-' ? `${item.jam_dibayar} Jam` : 'Tidak Lembur' }}
                                    </span>
                                    <div v-if="item.jumlah_rp && item.jumlah_rp !== '-'" class="font-black text-xs text-emerald-800 mt-0.5">
                                        {{ item.jumlah_rp }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs bg-slate-50 rounded-xl p-2.5 border border-slate-100 flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Pekerjaan:</span>
                                <span class="font-bold text-slate-800" :class="{ 'text-slate-400 italic': item.jam_dibayar === '-' }">
                                    {{ item.jenis_pekerjaan }}
                                </span>
                            </div>
                        </div>

                        <div v-if="items.length === 0" class="p-8 text-center text-slate-400 text-xs">
                            Belum ada data lembur (SPL) yang tercatat pada tanggal {{ formatTanggalIndo(selectedTanggal) }}.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
