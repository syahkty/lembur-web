<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    lemburs: Array,
    userRole: String,
    tanggal: String,
});

const selectedTanggal = ref(props.tanggal);
const showTolakModal = ref(false);
const tolakLemburId = ref(null);
const alasanRevisi = ref('');

function filterTanggal() {
    router.get(route('dashboard'), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function openTolakModal(lemburId) {
    tolakLemburId.value = lemburId;
    alasanRevisi.value = '';
    showTolakModal.value = true;
}

function submitTolak() {
    router.patch(route('lembur.tolak', tolakLemburId.value), {
        alasan_revisi: alasanRevisi.value,
    }, {
        onSuccess: () => {
            showTolakModal.value = false;
        }
    });
}

function hitungDurasi(jamMulai, jamSelesai) {
    if (!jamMulai || !jamSelesai) return '-';
    const [h1, m1] = jamMulai.split(':').map(Number);
    const [h2, m2] = jamSelesai.split(':').map(Number);
    let diff = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (diff < 0) diff += 24 * 60;
    const jam = Math.floor(diff / 60);
    const menit = diff % 60;
    return menit > 0 ? `${jam}j ${menit}m` : `${jam} jam`;
}

function confirmHapusLembur(lemburId) {
    if (confirm('Apakah Anda yakin ingin menghapus / membatalkan lembur ini?')) {
        router.delete(route('lembur.destroy', lemburId));
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Dashboard Sistem Lembur
                </h2>
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-600">Tanggal:</label>
                    <input
                        type="date"
                        v-model="selectedTanggal"
                        @change="filterTanggal"
                        class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <Link
                        v-if="userRole === 'mandor'"
                        :href="route('lembur.create')"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold transition"
                    >
                        + Buat SPL
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        SPL Tanggal: {{ selectedTanggal }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Employe</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Karyawan</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stasiun</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis Pekerjaan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Lembur</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keperluan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ index + 1 }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ lembur.user?.no_employe || '-' }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">{{ lembur.user?.name }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ lembur.user?.stasiun?.nama || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ lembur.keterangan }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ lembur.jam_mulai ? (lembur.jam_mulai.slice(0,5) + ' - ' + lembur.jam_selesai.slice(0,5)) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ lembur.jam_mulai ? hitungDurasi(lembur.jam_mulai, lembur.jam_selesai) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ lembur.keperluan || '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <span :class="{
                                            'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                            'bg-yellow-100 text-yellow-800': lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur'),
                                            'bg-green-100 text-green-800': lembur.status === 'disetujui',
                                            'bg-red-100 text-red-800': lembur.status === 'ditolak',
                                            'bg-amber-100 text-amber-800': lembur.kategori === 'cuti' || lembur.status === 'cuti',
                                            'bg-gray-200 text-gray-800': lembur.kategori === 'hk' || lembur.status === 'hk',
                                            'bg-purple-100 text-purple-800': lembur.kategori === 'izin' || lembur.status === 'izin',
                                        }">
                                            {{ (lembur.kategori === 'cuti' ? 'CUTI' : (lembur.kategori === 'hk' ? 'HK' : lembur.status))?.toUpperCase() }}
                                        </span>
                                        <!-- Tampilkan alasan revisi jika ditolak -->
                                        <p v-if="lembur.status === 'ditolak' && lembur.alasan_revisi" class="text-xs text-red-600 mt-1 max-w-xs">
                                            Alasan: {{ lembur.alasan_revisi }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <!-- Karyawan: ACC / Tolak (hanya jika lembur) -->
                                        <div v-if="userRole === 'karyawan' && lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur')" class="flex gap-2">
                                            <Link
                                                :href="route('lembur.acc', lembur.id)"
                                                method="patch"
                                                as="button"
                                                class="bg-green-500 text-white px-3 py-1 rounded text-xs font-bold hover:bg-green-600 transition"
                                            >
                                                ACC
                                            </Link>
                                            <button
                                                @click="openTolakModal(lembur.id)"
                                                class="bg-red-500 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-600 transition"
                                            >
                                                Tolak
                                            </button>
                                        </div>
                                        <!-- Mandor / Admin: Edit & Hapus -->
                                        <div v-if="userRole === 'mandor' || userRole === 'admin'" class="flex items-center gap-2">
                                            <Link
                                                :href="route('lembur.edit', lembur.id)"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded transition"
                                                :class="lembur.status === 'ditolak' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                            >
                                                {{ lembur.status === 'ditolak' ? '✏️ Revisi' : '✏️ Edit' }}
                                            </Link>
                                            <button
                                                @click="confirmHapusLembur(lembur.id)"
                                                class="inline-flex items-center px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition"
                                                title="Hapus baris lembur ini"
                                            >
                                                🗑️ Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="lemburs.length === 0">
                                    <td :colspan="userRole !== 'karyawan' ? 10 : 7" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Belum ada SPL untuk tanggal ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tolak + Alasan Revisi -->
        <div v-if="showTolakModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tolak SPL</h3>
                <p class="text-sm text-gray-600 mb-3">Jelaskan alasan penolakan atau revisi yang diinginkan:</p>
                <textarea
                    v-model="alasanRevisi"
                    rows="4"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                    placeholder="Contoh: Jam lembur tidak sesuai, seharusnya 14:00 - 17:00"
                ></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button
                        @click="showTolakModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitTolak"
                        :disabled="!alasanRevisi.trim()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Kirim Penolakan
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>