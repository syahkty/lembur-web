<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SignatureModal from '@/Components/SignatureModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { confirmDialog } from '@/utils/swal';

const props = defineProps({
    lemburs: Array,
    userRole: String,
    userSavedSignature: String,
    tanggal: String,
});

const selectedTanggal = ref(props.tanggal);
const showTolakModal = ref(false);
const tolakLemburId = ref(null);
const alasanRevisi = ref('');

// Signature Modal State
const showSignatureModal = ref(false);
const activeLembur = ref(null);

// Foto / TTD Viewer Modal State
const showMediaViewer = ref(false);
const mediaViewerUrl = ref('');
const mediaViewerTitle = ref('');

function filterTanggal() {
    router.get(route('dashboard'), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function openSignatureModal(lembur) {
    activeLembur.value = lembur;
    showSignatureModal.value = true;
}

function openMediaViewer(url, title) {
    mediaViewerUrl.value = url;
    mediaViewerTitle.value = title;
    showMediaViewer.value = true;
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

async function confirmHapusLembur(lemburId) {
    const ok = await confirmDialog({
        title: 'Hapus data lembur?',
        text: 'Baris SPL ini akan dibatalkan dan dihapus.',
        confirmText: 'Ya, hapus',
        danger: true,
    });
    if (ok) router.delete(route('lembur.destroy', lemburId), { preserveScroll: true });
}

function formatTanggalIndo(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Dashboard Sistem Lembur
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau instruksi kerja lembur harian pabrik kelapa sawit PT. Letawa
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <label class="text-xs font-semibold text-slate-600">Tanggal:</label>
                        <input
                            type="date"
                            v-model="selectedTanggal"
                            @change="filterTanggal"
                            class="text-xs border-0 p-0 text-slate-800 font-semibold focus:ring-0 cursor-pointer"
                        />
                    </div>

                    <Link
                        v-if="userRole === 'mandor'"
                        :href="route('lembur.create')"
                        class="bg-emerald-700 text-white px-4 py-2 rounded-xl hover:bg-emerald-800 text-xs font-bold transition shadow-sm shadow-emerald-700/20 flex items-center gap-1.5"
                    >
                        <span>➕</span> Buat SPL
                    </Link>

                    <Link
                        v-if="userRole !== 'karyawan'"
                        :href="route('lembur.lrlk', { tanggal: selectedTanggal })"
                        class="bg-white text-slate-700 border border-slate-200 px-3.5 py-2 rounded-xl hover:bg-slate-50 text-xs font-semibold transition shadow-sm flex items-center gap-1.5"
                    >
                        <span>📑</span> LRLK (Realisasi)
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Header Card Data -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Daftar SPL Tanggal: {{ formatTanggalIndo(selectedTanggal) }}
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Total {{ lemburs.length }} data tercatat pada sistem
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-800 rounded-lg">
                                {{ lemburs.filter(l => l.status === 'disetujui').length }} Disetujui
                            </span>
                            <span v-if="userRole === 'karyawan' && userSavedSignature" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                ✍️ TTD Tersimpan Aktif
                            </span>
                        </div>
                    </div>

                    <!-- Tampilan Tabel (Desktop & Tablet) -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">No</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left w-24">No Employe</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left">Nama Karyawan</th>
                                    <th v-if="userRole !== 'karyawan'" class="px-4 py-3 text-left w-28">Stasiun</th>
                                    <th class="px-4 py-3 text-left">Jenis Pekerjaan</th>
                                    <th class="px-4 py-3 text-center w-28">Waktu Lembur</th>
                                    <th class="px-4 py-3 text-center w-20">Durasi</th>
                                    <th class="px-4 py-3 text-left">Catatan</th>
                                    <th class="px-4 py-3 text-center w-24">Status</th>
                                    <th class="px-4 py-3 text-center w-28">Bukti / TTD</th>
                                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 text-center text-slate-400 font-semibold">{{ index + 1 }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 font-mono text-[11px] font-semibold text-slate-700">{{ lembur.user?.no_employe || '-' }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">{{ lembur.user?.name }}</td>
                                    <td v-if="userRole !== 'karyawan'" class="px-4 py-3 whitespace-nowrap text-slate-600">
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
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-bold text-emerald-700">
                                        <span v-if="lembur.durasi_jam">{{ lembur.durasi_jam }} Jam</span>
                                        <span v-else-if="lembur.jam_mulai">{{ hitungDurasi(lembur.jam_mulai, lembur.jam_selesai) }}</span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-[11px]">{{ lembur.keperluan || '-' }}</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span :class="{
                                            'px-2.5 py-0.5 inline-flex text-[11px] font-bold rounded-full': true,
                                            'bg-amber-100 text-amber-800': lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur'),
                                            'bg-emerald-100 text-emerald-800': lembur.status === 'disetujui',
                                            'bg-red-100 text-red-800': lembur.status === 'ditolak',
                                            'bg-amber-50 text-amber-900 border border-amber-200': lembur.kategori === 'cuti' || lembur.status === 'cuti',
                                            'bg-slate-100 text-slate-800 border border-slate-200': lembur.kategori === 'hk' || lembur.status === 'hk',
                                            'bg-purple-100 text-purple-800': lembur.kategori === 'izin' || lembur.status === 'izin',
                                        }">
                                            {{ (lembur.kategori === 'cuti' ? 'CUTI' : (lembur.kategori === 'hk' ? 'HK' : lembur.status))?.toUpperCase() }}
                                        </span>
                                        <p v-if="lembur.status === 'ditolak' && lembur.alasan_revisi" class="text-[10px] text-red-600 mt-1 max-w-xs text-left">
                                            Alasan: {{ lembur.alasan_revisi }}
                                        </p>
                                    </td>

                                    <!-- Kolom Bukti: TTD & Foto Dokumentasi (Untuk Mandor Monitoring) -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <!-- Tanda Tangan -->
                                            <button
                                                v-if="lembur.tanda_tangan"
                                                type="button"
                                                @click="openMediaViewer(lembur.tanda_tangan, `Tanda Tangan: ${lembur.user?.name}`)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 text-[10px] font-bold transition"
                                                title="Lihat Tanda Tangan Digital"
                                            >
                                                <span>✍️</span> TTD
                                            </button>

                                            <!-- Foto Dokumentasi Kerja (Monitoring Mandor) -->
                                            <button
                                                v-if="lembur.foto_dokumentasi"
                                                type="button"
                                                @click="openMediaViewer(lembur.foto_dokumentasi, `Dokumentasi Kerja: ${lembur.user?.name} (${lembur.keterangan})`)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-800 hover:bg-blue-100 border border-blue-200 text-[10px] font-bold transition"
                                                title="Lihat Foto Dokumentasi Mandor"
                                            >
                                                <span>📷</span> Foto
                                            </button>

                                            <span v-if="!lembur.tanda_tangan && !lembur.foto_dokumentasi" class="text-slate-300 text-[11px]">-</span>
                                        </div>
                                    </td>

                                    <!-- Kolom Aksi -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <!-- Karyawan: Tanda Tangan & ACC / Tolak -->
                                        <div v-if="userRole === 'karyawan' && lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur')" class="flex gap-1.5 justify-center">
                                            <button
                                                type="button"
                                                @click="openSignatureModal(lembur)"
                                                class="bg-emerald-700 text-white px-2.5 py-1 rounded-lg text-xs font-bold hover:bg-emerald-800 shadow-sm transition flex items-center gap-1"
                                            >
                                                <span>✍️</span> ACC & TTD
                                            </button>
                                            <button
                                                type="button"
                                                @click="openTolakModal(lembur.id)"
                                                class="bg-red-50 text-red-700 border border-red-200 px-2 py-1 rounded-lg text-xs font-bold hover:bg-red-100 transition"
                                            >
                                                ✕ Tolak
                                            </button>
                                        </div>

                                        <!-- Mandor / Admin: Edit & Hapus -->
                                        <div v-if="userRole === 'mandor' || userRole === 'admin'" class="flex items-center gap-1.5 justify-center">
                                            <Link
                                                :href="route('lembur.edit', lembur.id)"
                                                class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg transition"
                                                :class="lembur.status === 'ditolak' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            >
                                                {{ lembur.status === 'ditolak' ? '✏️ Revisi' : '✏️ Edit' }}
                                            </Link>
                                            <button
                                                type="button"
                                                @click="confirmHapusLembur(lembur.id)"
                                                class="inline-flex items-center px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                                title="Hapus baris lembur ini"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="lemburs.length === 0">
                                    <td :colspan="userRole !== 'karyawan' ? 11 : 8" class="px-4 py-12 text-center text-sm text-slate-400">
                                        Belum ada SPL untuk tanggal ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tampilan Kartu Khusus HP (Mobile Responsive View) -->
                    <div class="sm:hidden divide-y divide-slate-100 bg-white">
                        <div
                            v-for="(lembur, index) in lemburs"
                            :key="lembur.id"
                            class="p-4 space-y-2.5 hover:bg-slate-50 transition"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-400">#{{ index + 1 }}</span>
                                        <span v-if="userRole !== 'karyawan'" class="font-bold text-slate-900 text-sm">
                                            {{ lembur.user?.name }}
                                        </span>
                                        <span v-if="userRole === 'karyawan'" class="font-bold text-slate-900 text-sm">
                                            {{ lembur.keterangan }}
                                        </span>
                                    </div>
                                    <div v-if="userRole !== 'karyawan'" class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 font-mono">
                                        <span>ID: {{ lembur.user?.no_employe || '-' }}</span>
                                        <span>·</span>
                                        <span class="font-sans bg-slate-100 px-1.5 py-0.2 rounded text-slate-700">
                                            {{ lembur.user?.stasiun?.nama || '-' }}
                                        </span>
                                    </div>
                                </div>

                                <span :class="{
                                    'px-2 py-0.5 text-[10px] font-bold rounded-full': true,
                                    'bg-amber-100 text-amber-800': lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur'),
                                    'bg-emerald-100 text-emerald-800': lembur.status === 'disetujui',
                                    'bg-red-100 text-red-800': lembur.status === 'ditolak',
                                    'bg-amber-50 text-amber-900 border border-amber-200': lembur.kategori === 'cuti' || lembur.status === 'cuti',
                                    'bg-slate-100 text-slate-800': lembur.kategori === 'hk' || lembur.status === 'hk',
                                    'bg-purple-100 text-purple-800': lembur.kategori === 'izin' || lembur.status === 'izin',
                                }">
                                    {{ (lembur.kategori === 'cuti' ? 'CUTI' : (lembur.kategori === 'hk' ? 'HK' : lembur.status))?.toUpperCase() }}
                                </span>
                            </div>

                            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Tugas:</span>
                                    <span class="font-semibold text-slate-800">{{ lembur.keterangan }}</span>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span class="text-slate-500">Waktu:</span>
                                    <div class="text-right">
                                        <div class="font-mono font-bold text-slate-800">
                                            {{ lembur.jam_mulai ? (lembur.jam_mulai.slice(0,5) + ' - ' + lembur.jam_selesai.slice(0,5)) : '-' }}
                                        </div>
                                        <div v-if="lembur.jam_mulai_2 && lembur.jam_selesai_2" class="font-mono text-[10px] text-blue-700 font-bold">
                                            + {{ lembur.jam_mulai_2.slice(0,5) }} - {{ lembur.jam_selesai_2.slice(0,5) }}
                                        </div>
                                        <div class="text-[10px] text-emerald-700 font-black">
                                            Total: {{ lembur.durasi_jam || hitungDurasi(lembur.jam_mulai, lembur.jam_selesai) }} Jam
                                        </div>
                                    </div>
                                </div>
                                <div v-if="lembur.keperluan" class="flex justify-between">
                                    <span class="text-slate-500">Catatan:</span>
                                    <span class="text-slate-700 italic">{{ lembur.keperluan }}</span>
                                </div>
                            </div>

                            <!-- Bukti TTD & Foto di Mobile -->
                            <div v-if="lembur.tanda_tangan || lembur.foto_dokumentasi" class="flex items-center gap-2 pt-1">
                                <button
                                    v-if="lembur.tanda_tangan"
                                    type="button"
                                    @click="openMediaViewer(lembur.tanda_tangan, `Tanda Tangan: ${lembur.user?.name}`)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                                >
                                    <span>✍️</span> Lihat TTD
                                </button>
                                <button
                                    v-if="lembur.foto_dokumentasi"
                                    type="button"
                                    @click="openMediaViewer(lembur.foto_dokumentasi, `Dokumentasi: ${lembur.user?.name}`)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold"
                                >
                                    <span>📷</span> Lihat Foto Mandor
                                </button>
                            </div>

                            <p v-if="lembur.status === 'ditolak' && lembur.alasan_revisi" class="text-xs text-red-600 bg-red-50 p-2 rounded-lg border border-red-200">
                                <strong>Alasan Penolakan:</strong> {{ lembur.alasan_revisi }}
                            </p>

                            <!-- Aksi di Layar HP -->
                            <div class="flex items-center justify-end gap-2 pt-1">
                                <!-- Karyawan ACC/Tolak -->
                                <div v-if="userRole === 'karyawan' && lembur.status === 'pending' && (!lembur.kategori || lembur.kategori === 'lembur')" class="flex gap-2 w-full">
                                    <button
                                        type="button"
                                        @click="openSignatureModal(lembur)"
                                        class="flex-1 bg-emerald-700 text-white py-2 rounded-xl text-xs font-bold text-center shadow-md shadow-emerald-700/20 flex items-center justify-center gap-1.5"
                                    >
                                        <span>✍️</span> ACC & Tanda Tangani
                                    </button>
                                    <button
                                        type="button"
                                        @click="openTolakModal(lembur.id)"
                                        class="flex-1 bg-red-50 text-red-700 border border-red-200 py-2 rounded-xl text-xs font-bold text-center"
                                    >
                                        ✕ Tolak
                                    </button>
                                </div>

                                <!-- Mandor/Admin Edit/Hapus -->
                                <div v-if="userRole === 'mandor' || userRole === 'admin'" class="flex gap-2">
                                    <Link
                                        :href="route('lembur.edit', lembur.id)"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                    >
                                        ✏️ Edit
                                    </Link>
                                    <button
                                        type="button"
                                        @click="confirmHapusLembur(lembur.id)"
                                        class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition"
                                    >
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="lemburs.length === 0" class="p-8 text-center text-sm text-slate-400">
                            Belum ada SPL untuk tanggal ini.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Signature Pad Karyawan -->
        <SignatureModal
            :show="showSignatureModal"
            :lembur="activeLembur"
            :user-saved-signature="userSavedSignature"
            @close="showSignatureModal = false"
        />

        <!-- Modal Media Viewer (TTD / Foto Dokumentasi Full Size untuk Mandor) -->
        <div v-if="showMediaViewer" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h4 class="font-bold text-sm text-slate-900">{{ mediaViewerTitle }}</h4>
                    <button @click="showMediaViewer = false" class="text-slate-400 hover:text-slate-700 font-bold text-base">✕</button>
                </div>
                <div class="p-4 bg-slate-950 flex items-center justify-center min-h-60 max-h-[70vh]">
                    <img :src="mediaViewerUrl" :alt="mediaViewerTitle" class="max-h-[65vh] max-w-full object-contain rounded-lg" />
                </div>
                <div class="p-3 bg-white text-right border-t border-slate-100">
                    <button
                        @click="showMediaViewer = false"
                        class="px-4 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Alasan Tolak -->
        <div v-if="showTolakModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
                <h3 class="text-base font-bold text-slate-900 mb-1">✕ Tolak / Minta Revisi Lembur</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Tuliskan alasan ketidaksesuaian jam kerja agar mandor dapat memperbaikinya.
                </p>

                <textarea
                    v-model="alasanRevisi"
                    rows="3"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    placeholder="Contoh: Jam selesai seharusnya pukul 20:00, bukan 19:00..."
                    required
                ></textarea>

                <div class="flex justify-end gap-2 mt-4">
                    <button
                        type="button"
                        @click="showTolakModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitTolak"
                        :disabled="!alasanRevisi.trim()"
                        class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition disabled:opacity-50"
                    >
                        Kirim Penolakan
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>