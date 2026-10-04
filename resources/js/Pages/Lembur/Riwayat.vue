<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    lemburs: Array,
    bulan: String,
    totalJam: Number,
    userRole: String,
});

const selectedBulan = ref(props.bulan);

function filterBulan() {
    router.get(route('lembur.riwayat'), { bulan: selectedBulan.value }, { preserveState: true });
}

// Kelompokkan per tanggal
const lemburPerTanggal = computed(() => {
    const grouped = {};
    props.lemburs.forEach(l => {
        if (!grouped[l.tanggal]) {
            grouped[l.tanggal] = { items: [], totalMenit: 0 };
        }
        grouped[l.tanggal].items.push(l);
        grouped[l.tanggal].totalMenit += l.durasi_jam * 60;
    });
    return grouped;
});

const namaBulan = computed(() => {
    const [y, m] = selectedBulan.value.split('-');
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${months[parseInt(m) - 1]} ${y}`;
});
</script>

<template>
    <Head title="Riwayat Lembur" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Lembur Saya</h2>
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-600">Bulan:</label>
                    <input
                        type="month"
                        v-model="selectedBulan"
                        @change="filterBulan"
                        class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <!-- Summary Card -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-blue-600 font-medium">Bulan</p>
                            <p class="text-lg font-bold text-blue-800">{{ namaBulan }}</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-green-600 font-medium">Total Lembur</p>
                            <p class="text-2xl font-bold text-green-800">{{ lemburs.length }} hari</p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-purple-600 font-medium">Total Jam</p>
                            <p class="text-2xl font-bold text-purple-800">{{ totalJam }} jam</p>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Riwayat</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis Pekerjaan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keperluan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ lembur.tanggal }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ lembur.jam_mulai?.slice(0,5) }} - {{ lembur.jam_selesai?.slice(0,5) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-blue-700">{{ lembur.durasi_jam }} jam</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ lembur.keterangan }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ lembur.keperluan || '-' }}</td>
                                </tr>
                                <tr v-if="lemburs.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Belum ada riwayat lembur untuk bulan ini.
                                    </td>
                                </tr>
                                <!-- Total -->
                                <tr v-if="lemburs.length > 0" class="bg-gray-100 font-bold">
                                    <td colspan="3" class="px-4 py-3 text-sm text-right text-gray-700">TOTAL</td>
                                    <td class="px-4 py-3 text-sm text-blue-800">{{ totalJam }} jam</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
