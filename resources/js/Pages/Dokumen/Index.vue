<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DocSignatureModal from '@/Components/DocSignatureModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    lemburs: Array,
    lrlkItems: Array,
    tanggal: String,
    totalJamDibayar: Number,
    userRole: String,
    userName: String,
    userJabatan: String,
    savedSignature: String,
    docSignatures: Object,
});

const selectedTanggal = ref(props.tanggal);
const activeTab = ref('spl'); // 'spl' | 'lrlk'
const showSignatureModal = ref(false);
const currentSavedSignature = ref(props.savedSignature);

function filterTanggal() {
    router.get(route('dokumen.index'), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function onSignatureSaved(sigData) {
    currentSavedSignature.value = sigData;
}

// Format tanggal indo
function formatTanggalIndo(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

// Hitung durasi jam
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

// Badge warna status
const statusClass = (lembur) => ({
    'px-2.5 py-0.5 inline-flex text-[11px] font-bold rounded-full': true,
    'bg-amber-100 text-amber-800': lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur'),
    'bg-emerald-100 text-emerald-800': lembur.status === 'disetujui',
    'bg-red-100 text-red-800': lembur.status === 'ditolak',
    'bg-amber-50 text-amber-900 border border-amber-200': lembur.kategori === 'cuti' || lembur.status === 'cuti',
    'bg-slate-100 text-slate-800 border border-slate-200': lembur.kategori === 'hk' || lembur.status === 'hk',
    'bg-purple-100 text-purple-800': lembur.kategori === 'izin' || lembur.status === 'izin',
});

const statusLabel = (lembur) => {
    if (lembur.kategori === 'cuti') return 'CUTI';
    if (lembur.kategori === 'hk') return 'HK';
    return (lembur.status ?? '-').toUpperCase();
};

// Warna tema per role
const roleTheme = computed(() => ({
    asisten:       { bg: 'bg-blue-600',    text: 'text-blue-700',    light: 'bg-blue-50',    border: 'border-blue-200' },
    askep:         { bg: 'bg-indigo-600',  text: 'text-indigo-700',  light: 'bg-indigo-50',  border: 'border-indigo-200' },
    ktu:           { bg: 'bg-violet-600',  text: 'text-violet-700',  light: 'bg-violet-50',  border: 'border-violet-200' },
    administratur: { bg: 'bg-emerald-700', text: 'text-emerald-700', light: 'bg-emerald-50', border: 'border-emerald-200' },
    hrga:          { bg: 'bg-rose-600',    text: 'text-rose-700',    light: 'bg-rose-50',    border: 'border-rose-200' },
}[props.userRole] ?? { bg: 'bg-slate-600', text: 'text-slate-700', light: 'bg-slate-50', border: 'border-slate-200' }));
</script>

<template>
    <Head title="Dokumen Lembur" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Dokumen Lembur
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tampilan SPL &amp; LRLK · {{ userJabatan }} — {{ userName }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Filter Tanggal -->
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <label class="text-xs font-semibold text-slate-600">Tanggal:</label>
                        <input
                            type="date"
                            v-model="selectedTanggal"
                            @change="filterTanggal"
                            class="text-xs border-0 p-0 text-slate-800 font-semibold focus:ring-0 cursor-pointer"
                        />
                    </div>

                    <!-- Tombol Tanda Tangan -->
                    <button
                        type="button"
                        @click="showSignatureModal = true"
                        :class="[roleTheme.bg, 'text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5 hover:opacity-90']"
                    >
                        <span>✍️</span>
                        {{ currentSavedSignature ? 'Perbarui TTD' : 'Tanda Tangan' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Status Tanda Tangan -->
                <div v-if="currentSavedSignature"
                    :class="['flex items-center gap-3 px-4 py-3 rounded-2xl border', roleTheme.light, roleTheme.border]"
                >
                    <div class="flex-shrink-0">
                        <img :src="currentSavedSignature" alt="TTD" class="h-10 object-contain bg-white border border-slate-200 rounded-lg px-2" />
                    </div>
                    <div>
                        <p :class="['text-xs font-bold', roleTheme.text]">✓ Tanda Tangan Aktif — {{ userJabatan }}</p>
                        <p class="text-[11px] text-slate-500">Tanda tangan Anda akan muncul di kolom <strong>{{ userJabatan }}</strong> pada dokumen cetak SPL &amp; LRLK.</p>
                    </div>
                </div>
                <div v-else class="flex items-center gap-3 px-4 py-3 rounded-2xl border bg-amber-50 border-amber-200">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <p class="text-xs font-bold text-amber-800">Tanda tangan belum disimpan</p>
                        <p class="text-[11px] text-amber-700">Klik tombol <strong>Tanda Tangan</strong> di atas untuk menandatangani dokumen lembur hari ini.</p>
                    </div>
                </div>

                <!-- Tab Switcher -->
                <div class="flex bg-slate-100 rounded-2xl p-1 gap-1 w-fit">
                    <button
                        @click="activeTab = 'spl'"
                        :class="[
                            'px-5 py-2 text-xs font-bold rounded-xl transition',
                            activeTab === 'spl' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        📋 SPL (Surat Perintah Lembur)
                    </button>
                    <button
                        @click="activeTab = 'lrlk'"
                        :class="[
                            'px-5 py-2 text-xs font-bold rounded-xl transition',
                            activeTab === 'lrlk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        📊 LRLK (Laporan Realisasi)
                    </button>
                </div>

                <!-- ==== TAB SPL ==== -->
                <div v-if="activeTab === 'spl'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Surat Perintah Kerja Lembur — {{ formatTanggalIndo(tanggal) }}
                            </h3>
                            <p class="text-[11px] text-slate-500">Total {{ lemburs.length }} data tercatat</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-800 rounded-lg">
                                {{ lemburs.filter(l => l.status === 'disetujui').length }} Disetujui
                            </span>
                            <a
                                :href="route('dokumen.preview.spl', { tanggal })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl shadow-sm transition"
                            >
                                👁️ Preview SPL
                            </a>
                        </div>
                    </div>

                    <!-- Tabel Desktop -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">No</th>
                                    <th class="px-4 py-3 text-left w-24">No Employe</th>
                                    <th class="px-4 py-3 text-left">Nama Karyawan</th>
                                    <th class="px-4 py-3 text-left w-28">Stasiun</th>
                                    <th class="px-4 py-3 text-left">Jenis Pekerjaan</th>
                                    <th class="px-4 py-3 text-center w-28">Waktu Lembur</th>
                                    <th class="px-4 py-3 text-center w-20">Durasi</th>
                                    <th class="px-4 py-3 text-center w-24">Status</th>
                                    <th class="px-4 py-3 text-center w-20">TTD</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 text-center text-slate-400 font-semibold">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 font-mono text-[11px] font-semibold text-slate-700">{{ lembur.user?.no_employe || '-' }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">{{ lembur.user?.name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-medium">
                                            {{ lembur.user?.stasiun?.nama || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 font-medium">{{ lembur.keterangan }}</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-mono text-slate-700 text-[11px]">
                                        <div v-if="lembur.jam_mulai">
                                            <div>{{ lembur.jam_mulai.slice(0,5) }} - {{ lembur.jam_selesai.slice(0,5) }}</div>
                                            <div v-if="lembur.jam_mulai_2 && lembur.jam_selesai_2" class="text-[10px] text-blue-700 font-bold">
                                                + {{ lembur.jam_mulai_2.slice(0,5) }} - {{ lembur.jam_selesai_2.slice(0,5) }}
                                            </div>
                                        </div>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-700">
                                        <span v-if="lembur.durasi_jam">{{ lembur.durasi_jam }} Jam</span>
                                        <span v-else-if="lembur.jam_mulai">{{ hitungDurasi(lembur.jam_mulai, lembur.jam_selesai) }}</span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="statusClass(lembur)">{{ statusLabel(lembur) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <img v-if="lembur.tanda_tangan" :src="lembur.tanda_tangan" alt="TTD" class="h-6 mx-auto object-contain" />
                                        <span v-else class="text-slate-300 text-[11px]">-</span>
                                    </td>
                                </tr>
                                <tr v-if="lemburs.length === 0">
                                    <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Belum ada SPL untuk tanggal ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Kartu Mobile -->
                    <div class="sm:hidden divide-y divide-slate-100">
                        <div v-for="(lembur, index) in lemburs" :key="lembur.id" class="p-4 space-y-2 hover:bg-slate-50">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-400">#{{ index + 1 }}</span>
                                        <span class="font-bold text-slate-900 text-sm">{{ lembur.user?.name }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                        {{ lembur.user?.no_employe || '-' }} ·
                                        <span class="font-sans">{{ lembur.user?.stasiun?.nama || '-' }}</span>
                                    </div>
                                </div>
                                <span :class="statusClass(lembur)">{{ statusLabel(lembur) }}</span>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-2.5 text-xs space-y-1 border border-slate-100">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pekerjaan:</span>
                                    <span class="font-semibold">{{ lembur.keterangan }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Waktu:</span>
                                    <span class="font-mono font-bold">
                                        {{ lembur.jam_mulai ? `${lembur.jam_mulai.slice(0,5)} - ${lembur.jam_selesai.slice(0,5)}` : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="lemburs.length === 0" class="p-8 text-center text-sm text-slate-400">
                            Belum ada SPL untuk tanggal ini.
                        </div>
                    </div>
                </div>

                <!-- ==== TAB LRLK ==== -->
                <div v-if="activeTab === 'lrlk'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Laporan Realisasi Kerja Lembur — {{ formatTanggalIndo(tanggal) }}
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Total Jam Dibayar: <strong class="text-emerald-700">{{ totalJamDibayar }} jam</strong>
                            </p>
                        </div>
                        <a
                            :href="route('dokumen.preview.lrlk', { tanggal })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl shadow-sm transition"
                        >
                            👁️ Preview LRLK
                        </a>
                    </div>

                    <!-- Tabel LRLK Desktop -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-3 py-3 text-center w-8">No</th>
                                    <th class="px-3 py-3 text-left w-24">No Employe</th>
                                    <th class="px-3 py-3 text-left">Nama</th>
                                    <th class="px-3 py-3 text-center w-16">Dept</th>
                                    <th class="px-3 py-3 text-center w-16">Tgl</th>
                                    <th class="px-3 py-3 text-left">Jenis Pekerjaan</th>
                                    <th class="px-3 py-3 text-center w-20">Jam Dibayar</th>
                                    <th class="px-3 py-3 text-center w-20">Hasil Kerja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="item in lrlkItems" :key="item.id" class="hover:bg-slate-50/80 transition">
                                    <td class="px-3 py-2.5 text-center text-slate-400 font-semibold">{{ item.no }}</td>
                                    <td class="px-3 py-2.5 font-mono text-[11px] text-slate-700">{{ item.no_employe }}</td>
                                    <td class="px-3 py-2.5 font-bold text-slate-900 whitespace-nowrap">{{ item.nama }}</td>
                                    <td class="px-3 py-2.5 text-center text-slate-600">{{ item.dept }}</td>
                                    <td class="px-3 py-2.5 text-center font-mono text-slate-700">{{ item.tgl }}</td>
                                    <td class="px-3 py-2.5 text-slate-800">{{ item.jenis_pekerjaan }}</td>
                                    <td class="px-3 py-2.5 text-center font-bold text-emerald-700">{{ item.jam_dibayar }}</td>
                                    <td class="px-3 py-2.5 text-center text-slate-600 text-[11px]">{{ item.hasil_kerja }}</td>
                                </tr>
                                <tr v-if="lrlkItems.length === 0">
                                    <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Belum ada data LRLK untuk tanggal ini.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="lrlkItems.length > 0">
                                <tr class="bg-emerald-50 font-bold text-xs">
                                    <td colspan="6" class="px-3 py-2 text-right text-emerald-900">TOTAL JAM DIBAYAR:</td>
                                    <td class="px-3 py-2 text-center text-emerald-800">{{ totalJamDibayar }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- LRLK Mobile -->
                    <div class="sm:hidden divide-y divide-slate-100">
                        <div v-for="item in lrlkItems" :key="item.no" class="p-4 space-y-1.5 hover:bg-slate-50">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-400">#{{ item.no }}</span>
                                    <span class="ml-2 font-bold text-slate-900">{{ item.nama }}</span>
                                </div>
                                <span class="font-bold text-emerald-700 text-xs">{{ item.jam_dibayar }} Jam</span>
                            </div>
                            <p class="text-[11px] text-slate-600">{{ item.jenis_pekerjaan }}</p>
                        </div>
                        <div v-if="lrlkItems.length === 0" class="p-8 text-center text-sm text-slate-400">
                            Belum ada data LRLK untuk tanggal ini.
                        </div>
                    </div>
                </div>

                <!-- Info Tanda Tangan Dokumen -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h4 class="text-sm font-bold text-slate-900 mb-3">✍️ Status Tanda Tangan Dokumen</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                        <div
                            v-for="(sig, role) in docSignatures"
                            :key="role"
                            class="flex flex-col items-center gap-1.5 p-3 rounded-xl border"
                            :class="sig.tanda_tangan ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'"
                        >
                            <div class="w-full h-10 flex items-center justify-center bg-white rounded-lg border border-slate-100">
                                <img v-if="sig.tanda_tangan" :src="sig.tanda_tangan" alt="TTD" class="max-h-8 max-w-full object-contain" />
                                <span v-else class="text-slate-300 text-xs">Belum TTD</span>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold" :class="sig.tanda_tangan ? 'text-emerald-800' : 'text-slate-500'">
                                    {{ sig.jabatan }}
                                </p>
                                <p class="text-[9px] text-slate-400 truncate max-w-[80px]">{{ sig.nama }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Tanda Tangan -->
        <DocSignatureModal
            :show="showSignatureModal"
            :tanggal="tanggal"
            :saved-signature="currentSavedSignature"
            :user-name="userName"
            :user-jabatan="userJabatan"
            @close="showSignatureModal = false"
            @saved="onSignatureSaved"
        />

    </AuthenticatedLayout>
</template>
