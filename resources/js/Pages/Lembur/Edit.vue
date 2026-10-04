<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    lembur: Object,
    userRole: String,
});

const isRevisi = computed(() => props.lembur.status === 'ditolak');

const form = useForm({
    tanggal: props.lembur.tanggal || '',
    kategori: (props.lembur.kategori === 'lembur' || (!props.lembur.kategori && props.lembur.jam_mulai)) ? 'lembur' : 'tidak_lembur',
    jam_mulai: props.lembur.jam_mulai?.slice(0, 5) || '',
    jam_selesai: props.lembur.jam_selesai?.slice(0, 5) || '',
    keterangan: props.lembur.keterangan || '',
    keperluan: props.lembur.keperluan || '',
});

function onKategoriChange(val) {
    form.kategori = val;
    if (val === 'tidak_lembur') {
        form.jam_mulai = '';
        form.jam_selesai = '';
        if (form.keterangan === 'Lanjut Proses') {
            form.keterangan = '';
        }
    } else {
        if (!form.keterangan) {
            form.keterangan = 'Lanjut Proses';
        }
        if (!form.jam_mulai) form.jam_mulai = '16:00';
        if (!form.jam_selesai) form.jam_selesai = '19:00';
    }
}

function submit() {
    form.patch(route('lembur.update', props.lembur.id));
}
</script>

<template>
    <Head :title="isRevisi ? 'Revisi SPL' : 'Edit SPL'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ isRevisi ? 'Revisi SPL (Sebelumnya Ditolak)' : 'Edit SPL' }}
                </h2>
                <a :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Info Karyawan -->
                    <div class="bg-gray-50 border rounded-md p-4 mb-6">
                        <h4 class="font-medium text-gray-900 mb-2">Informasi Karyawan</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Nama Karyawan:</span>
                            <span class="font-semibold text-gray-800">{{ lembur.user?.name }}</span>
                            <span class="text-gray-500">No Employe:</span>
                            <span>{{ lembur.user?.no_employe || '-' }}</span>
                            <span class="text-gray-500">Stasiun:</span>
                            <span class="font-medium">{{ lembur.user?.stasiun?.nama || '-' }}</span>
                            <span class="text-gray-500">Status Saat Ini:</span>
                            <span class="font-semibold" :class="{
                                'text-yellow-600': lembur.status === 'pending',
                                'text-green-600': lembur.status === 'disetujui',
                                'text-red-600': lembur.status === 'ditolak',
                                'text-amber-700': lembur.kategori === 'cuti',
                                'text-gray-700': lembur.kategori === 'hk',
                            }">{{ (lembur.kategori === 'cuti' ? 'CUTI' : (lembur.kategori === 'hk' ? 'HK' : lembur.status))?.toUpperCase() }}</span>
                        </div>
                    </div>

                    <!-- Alasan Penolakan Jika Ada -->
                    <div v-if="lembur.alasan_revisi" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                        <h4 class="font-medium text-red-800 mb-1 flex items-center gap-1.5">
                            <span>⚠️</span> Alasan Penolakan dari Karyawan:
                        </h4>
                        <p class="text-sm text-red-700 italic bg-white p-2 rounded border border-red-100 mt-1">
                            "{{ lembur.alasan_revisi }}"
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                                <input
                                    type="date"
                                    v-model="form.tanggal"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status Lembur</label>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="onKategoriChange('lembur')"
                                        class="flex-1 py-2 px-3 text-xs font-bold rounded-lg border transition text-center"
                                        :class="form.kategori === 'lembur' ? 'bg-green-600 text-white border-green-600 shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'"
                                    >
                                        🟢 Lembur
                                    </button>
                                    <button
                                        type="button"
                                        @click="onKategoriChange('tidak_lembur')"
                                        class="flex-1 py-2 px-3 text-xs font-bold rounded-lg border transition text-center"
                                        :class="form.kategori === 'tidak_lembur' ? 'bg-gray-700 text-white border-gray-700 shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-300'"
                                    >
                                        ⚪ Tidak Lembur
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.kategori === 'lembur'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                                <input
                                    type="time"
                                    v-model="form.jam_mulai"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                                <input
                                    type="time"
                                    v-model="form.jam_selesai"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                />
                            </div>
                        </div>
                        <div v-else class="p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                            ℹ️ <strong>Status Tidak Lembur:</strong> Jam lembur otomatis dinonaktifkan (menjadi -). Silakan tuliskan alasan ketidakhadiran lembur pada kolom di bawah.
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ form.kategori === 'lembur' ? 'Jenis Pekerjaan Lembur' : 'Alasan Tidak Lembur' }}
                            </label>
                            <textarea
                                v-model="form.keterangan"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :placeholder="form.kategori === 'lembur' ? 'Contoh: Lanjut proses, standby genset, dll' : 'Tuliskan alasannya, contoh: Cuti, HK, Sakit, Izin, Pulang Jam 15:00...'"
                                required
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keperluan / Keterangan</label>
                            <textarea
                                v-model="form.keperluan"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Opsional (misal: pulang jam 15:00, ganti shift, dll)"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <a
                                :href="route('dashboard')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition"
                            >
                                Batal
                            </a>
                            <button
                                type="submit"
                                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold transition"
                                :disabled="form.processing"
                            >
                                {{ isRevisi ? 'Simpan & Kirim Revisi' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
