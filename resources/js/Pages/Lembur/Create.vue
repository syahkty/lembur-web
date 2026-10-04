<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    karyawanList: Array,
    stasions: Array,
    userRole: String,
    userShift: Number,
});

// Form Header
const tanggal = ref(new Date().toISOString().split('T')[0]);
const selectedShift = ref(props.userRole === 'mandor' && props.userShift ? props.userShift : 1);

// Quick Preset Settings
const presetJamMulai = ref('16:00');
const presetJamSelesai = ref('19:00');
const presetPekerjaan = ref('Lanjut Proses');

// Modal tambah karyawan manual (pengganti sementara) — hanya untuk mandor
const showTambahManual = ref(false);
const namaManual = ref('');
const noEmployeManual = ref('');
const stasiunManual = ref('');
const nextTempId = ref(-1);

function tambahKaryawanManual() {
    if (!namaManual.value.trim()) return;
    items.value.push({
        user_id: nextTempId.value--,
        name: namaManual.value.trim(),
        no_employe: noEmployeManual.value.trim() || '-',
        stasiun_nama: props.stasions.find(s => s.id == stasiunManual.value)?.nama || '(Manual)',
        shift: Number(selectedShift.value) || 1,
        sertakan: true,
        lembur: true,
        keterangan: presetPekerjaan.value || 'Lanjut Proses',
        jam_mulai: presetJamMulai.value || '16:00',
        jam_selesai: presetJamSelesai.value || '19:00',
        keperluan: '',
        is_manual: true,
        nama_manual: namaManual.value.trim(),
        no_employe_manual: noEmployeManual.value.trim() || '',
        stasiun_id_manual: stasiunManual.value || null,
    });
    namaManual.value = '';
    noEmployeManual.value = '';
    stasiunManual.value = '';
    showTambahManual.value = false;
}

// Inisialisasi daftar item lembur
const items = ref(
    props.karyawanList.map(k => ({
        user_id: k.id,
        name: k.name,
        no_employe: k.no_employe || '-',
        stasiun_nama: k.stasiun?.nama || '-',
        shift: k.shift || 1,
        sertakan: true,
        lembur: true,       // true = Lembur, false = Tidak Lembur
        keterangan: presetPekerjaan.value,
        jam_mulai: presetJamMulai.value,
        jam_selesai: presetJamSelesai.value,
        keperluan: '',
        is_manual: false,
    }))
);

// Filter berdasarkan shift
const filteredItems = computed(() => {
    if (selectedShift.value === 'all') return items.value;
    return items.value.filter(item => item.shift === Number(selectedShift.value));
});

// Handler toggle Lembur / Tidak Lembur
function onToggleLembur(item) {
    if (item.lembur) {
        item.keterangan = presetPekerjaan.value || 'Lanjut Proses';
        item.jam_mulai = presetJamMulai.value || '16:00';
        item.jam_selesai = presetJamSelesai.value || '19:00';
    } else {
        item.jam_mulai = '';
        item.jam_selesai = '';
        // Kosongkan keterangan agar mandor bisa mengisi sendiri alasannya
        item.keterangan = '';
    }
}

// Terapkan preset ke seluruh yang lembur
function terapkanPreset() {
    filteredItems.value.forEach(item => {
        if (item.lembur) {
            item.jam_mulai = presetJamMulai.value;
            item.jam_selesai = presetJamSelesai.value;
            item.keterangan = presetPekerjaan.value;
        }
    });
}

const isSubmitting = ref(false);

function submit() {
    isSubmitting.value = true;
    router.post(route('lembur.store'), {
        tanggal: tanggal.value,
        items: filteredItems.value.map(item => ({
            user_id: item.user_id,
            sertakan: item.sertakan,
            kategori: item.lembur ? 'lembur' : 'tidak_lembur',
            keterangan: item.keterangan,
            jam_mulai: item.lembur ? (item.jam_mulai || null) : null,
            jam_selesai: item.lembur ? (item.jam_selesai || null) : null,
            keperluan: item.keperluan || null,
            is_manual: item.is_manual || false,
            nama_manual: item.is_manual ? item.nama_manual : null,
            no_employe_manual: item.is_manual ? item.no_employe_manual : null,
            stasiun_id_manual: item.is_manual ? item.stasiun_id_manual : null,
        })),
    }, {
        onFinish: () => { isSubmitting.value = false; }
    });
}
</script>

<template>
    <Head title="Buat SPL Harian" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Form Surat Perintah Lembur (SPL) Harian
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Tandai status setiap karyawan: <strong>Lembur</strong> atau <strong>Tidak Lembur</strong>. Untuk yang tidak lembur, isi keterangan alasannya.
                    </p>
                </div>
                <a :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Panel Pengaturan Shift & Preset Kolektif -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Tanggal SPL
                            </label>
                            <input
                                type="date"
                                v-model="tanggal"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                        </div>

                        <!-- Shift Kerja -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Shift Kerja
                            </label>
                            <div v-if="userRole === 'mandor'" class="bg-blue-50 border border-blue-200 px-3 py-2 rounded-lg flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-900">
                                    Shift {{ userShift || selectedShift }} (Tim Anda)
                                </span>
                                <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded font-bold uppercase">
                                    Mandor {{ (userShift || selectedShift) === 1 ? 'A' : 'B' }}
                                </span>
                            </div>
                            <div v-else class="flex gap-1.5 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="selectedShift = 1"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-md transition text-center"
                                    :class="selectedShift === 1 ? 'bg-blue-600 text-white shadow' : 'text-gray-700 hover:bg-gray-200'">
                                    Shift 1 (Mandor A)
                                </button>
                                <button type="button" @click="selectedShift = 2"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-md transition text-center"
                                    :class="selectedShift === 2 ? 'bg-blue-600 text-white shadow' : 'text-gray-700 hover:bg-gray-200'">
                                    Shift 2 (Mandor B)
                                </button>
                                <button type="button" @click="selectedShift = 'all'"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-md transition text-center"
                                    :class="selectedShift === 'all' ? 'bg-blue-600 text-white shadow' : 'text-gray-700 hover:bg-gray-200'">
                                    Semua
                                </button>
                            </div>
                        </div>

                        <!-- Preset Jam & Pekerjaan -->
                        <div class="md:col-span-2 bg-blue-50/70 border border-blue-200 p-3 rounded-lg">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-blue-900">⚡ Pengaturan Cepat Lembur (Bulk Preset):</span>
                                <button type="button" @click="terapkanPreset"
                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold px-2.5 py-1 rounded shadow-sm transition">
                                    Terapkan ke Semua Lembur
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-600">Jam Mulai</label>
                                    <input type="time" v-model="presetJamMulai" class="w-full text-xs rounded border-gray-300 py-1" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-600">Jam Selesai</label>
                                    <input type="time" v-model="presetJamSelesai" class="w-full text-xs rounded border-gray-300 py-1" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-600">Jenis Pekerjaan</label>
                                    <input type="text" v-model="presetPekerjaan" class="w-full text-xs rounded border-gray-300 py-1" placeholder="Lanjut Proses" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Tabel Daftar Karyawan Shift -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-gray-800">
                                Anggota Tim Shift {{ selectedShift === 'all' ? '1 & 2' : selectedShift }}
                            </span>
                            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-medium">
                                {{ filteredItems.length }} Karyawan
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 hidden sm:block">
                                Klik <strong class="text-green-700">Lembur</strong> atau <strong class="text-gray-600">Tidak</strong> per karyawan.
                            </span>
                            <!-- Tombol Tambah Karyawan Manual — hanya mandor -->
                            <button
                                v-if="userRole === 'mandor'"
                                type="button"
                                @click="showTambahManual = true"
                                class="flex items-center gap-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg shadow-sm transition"
                            >
                                ➕ Tambah Pengganti
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-100 text-gray-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-3 py-2.5 text-center w-8">No</th>
                                    <th class="px-3 py-2.5 text-left w-24">No Employe</th>
                                    <th class="px-3 py-2.5 text-left">Nama Karyawan</th>
                                    <th class="px-3 py-2.5 text-left">Stasiun</th>
                                    <th class="px-3 py-2.5 text-center w-36">Status</th>
                                    <th class="px-3 py-2.5 text-left w-52">Jenis Pekerjaan / Keterangan</th>
                                    <th class="px-3 py-2.5 text-left w-44">Jam Lembur</th>
                                    <th class="px-3 py-2.5 text-left">Catatan</th>
                                    <th class="px-3 py-2.5 text-center w-12">Ikut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="(item, idx) in filteredItems"
                                    :key="item.user_id"
                                    :class="{
                                        'bg-green-50/30': item.lembur,
                                        'bg-gray-50/80': !item.lembur,
                                        'ring-1 ring-inset ring-emerald-300': item.is_manual,
                                    }"
                                >
                                    <!-- No -->
                                    <td class="px-3 py-2 text-center text-gray-500 font-medium">
                                        {{ idx + 1 }}
                                        <span v-if="item.is_manual" class="block text-[9px] text-emerald-600 font-bold leading-none mt-0.5">✦ Pengganti</span>
                                    </td>

                                    <!-- No Employe -->
                                    <td class="px-3 py-2 text-gray-700 font-mono text-[11px]">{{ item.no_employe }}</td>

                                    <!-- Nama -->
                                    <td class="px-3 py-2 font-semibold text-gray-900 whitespace-nowrap">{{ item.name }}</td>

                                    <!-- Stasiun -->
                                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                        <span class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded text-[11px] font-medium border border-blue-100">
                                            {{ item.stasiun_nama }}
                                        </span>
                                    </td>

                                    <!-- Toggle Status: Lembur / Tidak Lembur -->
                                    <td class="px-3 py-2">
                                        <div class="flex gap-1 bg-gray-100 p-0.5 rounded-lg">
                                            <button
                                                type="button"
                                                @click="item.lembur = true; onToggleLembur(item)"
                                                class="flex-1 py-1 text-[11px] font-bold rounded-md transition text-center"
                                                :class="item.lembur ? 'bg-green-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-200'"
                                            >
                                                🟢 Lembur
                                            </button>
                                            <button
                                                type="button"
                                                @click="item.lembur = false; onToggleLembur(item)"
                                                class="flex-1 py-1 text-[11px] font-bold rounded-md transition text-center"
                                                :class="!item.lembur ? 'bg-gray-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-200'"
                                            >
                                                ⚪ Tidak
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Jenis Pekerjaan / Keterangan -->
                                    <td class="px-3 py-2">
                                        <input
                                            type="text"
                                            v-model="item.keterangan"
                                            class="w-full text-xs rounded border-gray-300 py-1"
                                            :class="!item.lembur ? 'bg-yellow-50 border-yellow-300 focus:border-yellow-500 focus:ring-yellow-400' : ''"
                                            :placeholder="item.lembur ? 'Lanjut proses...' : 'Cuti / HK / Izin / Sakit...'"
                                            required
                                        />
                                        <p v-if="!item.lembur" class="text-[10px] text-yellow-700 mt-0.5 leading-none">
                                            ↑ Rinci alasan tidak lembur
                                        </p>
                                    </td>

                                    <!-- Jam Lembur -->
                                    <td class="px-3 py-2">
                                        <div v-if="item.lembur" class="flex items-center gap-1">
                                            <input type="time" v-model="item.jam_mulai" class="w-20 text-xs rounded border-gray-300 py-1" required />
                                            <span class="text-gray-400">-</span>
                                            <input type="time" v-model="item.jam_selesai" class="w-20 text-xs rounded border-gray-300 py-1" required />
                                        </div>
                                        <div v-else class="text-gray-400 italic text-center py-1 text-[11px]">
                                            — Tidak Ada Lembur —
                                        </div>
                                    </td>

                                    <!-- Catatan Tambahan -->
                                    <td class="px-3 py-2">
                                        <input
                                            type="text"
                                            v-model="item.keperluan"
                                            class="w-full text-xs rounded border-gray-300 py-1"
                                            placeholder="Opsional..."
                                        />
                                    </td>

                                    <!-- Sertakan -->
                                    <td class="px-3 py-2 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="item.sertakan"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4 cursor-pointer"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Action Bar -->
                    <div class="p-4 bg-gray-50 border-t flex items-center justify-between">
                        <span class="text-xs text-gray-500">Pastikan data sudah sesuai sebelum diterbitkan.</span>
                        <div class="flex items-center gap-3">
                            <a :href="route('dashboard')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold text-sm shadow-md transition flex items-center gap-2"
                                :disabled="isSubmitting">
                                <span v-if="isSubmitting">Menyimpan...</span>
                                <span v-else>📋 Terbitkan SPL Hari Ini</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <!-- Modal Tambah Karyawan Pengganti Manual -->
        <div v-if="showTambahManual" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-4 pb-2 border-b">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">➕ Tambah Karyawan Pengganti</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Untuk karyawan baru / pengganti sementara yang belum terdaftar.</p>
                    </div>
                    <button @click="showTambahManual = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">✕</button>
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            v-model="namaManual"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            placeholder="Nama karyawan pengganti..."
                            @keyup.enter="tambahKaryawanManual"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">No. Employe</label>
                            <input
                                type="text"
                                v-model="noEmployeManual"
                                class="w-full rounded-lg border-gray-300 text-sm font-mono focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Opsional..."
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Stasiun Pabrik</label>
                            <select v-model="stasiunManual" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— Pilih Stasiun —</option>
                                <option v-for="s in stasions" :key="s.id" :value="s.id">{{ s.nama }}</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        ⚠️ Karyawan ini hanya dicatat sebagai keterangan di SPL. Untuk akun permanen, minta admin mendaftarkan di halaman Kelola Karyawan.
                    </p>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3 border-t">
                    <button type="button" @click="showTambahManual = false"
                        class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="button" @click="tambahKaryawanManual" :disabled="!namaManual.trim()"
                        class="px-5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                        ✓ Tambahkan ke Daftar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>