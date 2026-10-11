<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    lemburs: Array,
    bulan: String,
    totalJamNyata: Number,
    totalJamDibayar: Number,
    totalUpahLembur: Number,
    userProfile: Object,
    userRole: String,
});

const selectedBulan = ref(props.bulan);

// Modal Viewer untuk Foto Dokumentasi & TTD
const showMediaModal = ref(false);
const mediaModalUrl = ref('');
const mediaModalTitle = ref('');

function openMedia(url, title) {
    mediaModalUrl.value = url;
    mediaModalTitle.value = title;
    showMediaModal.value = true;
}

function filterBulan() {
    router.get(route('lembur.riwayat'), { bulan: selectedBulan.value }, { preserveState: true });
}

const namaBulan = computed(() => {
    const [y, m] = selectedBulan.value.split('-');
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${months[parseInt(m) - 1]} ${y}`;
});

function formatRupiah(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function formatTanggalIndo(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

<template>
    <Head title="Riwayat Lembur & Upah" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Riwayat Lembur & Rincian Upah
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Perhitungan upah lembur resmi berdasarkan standar ketenagakerjaan Indonesia (PP No. 35/2021)
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm text-xs">
                        <label class="font-semibold text-slate-600">Pilih Bulan:</label>
                        <input
                            type="month"
                            v-model="selectedBulan"
                            @change="filterBulan"
                            class="border-0 p-0 text-slate-800 font-bold focus:ring-0 cursor-pointer text-xs"
                        />
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Card Profil & Variabel Upah dari Admin -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-lg">
                            💰
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 text-base">{{ userProfile?.name }}</h3>
                                <span class="font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded text-slate-600">
                                    No. {{ userProfile?.no_employe || '-' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Nilai variabel upah ditetapkan oleh HR / Admin PT. Letawa
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 border-t md:border-t-0 pt-3 md:pt-0">
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span class="text-[10px] text-slate-500 font-semibold block uppercase tracking-wider">Gaji Pokok</span>
                            <span class="text-xs font-bold text-slate-800">
                                {{ userProfile?.gaji_pokok ? formatRupiah(userProfile.gaji_pokok) : 'Belum diatur' }}
                            </span>
                        </div>

                        <div class="bg-amber-50/70 p-2.5 rounded-xl border border-amber-200">
                            <span class="text-[10px] text-amber-800 font-semibold block uppercase tracking-wider">Catu Beras</span>
                            <span class="text-xs font-bold text-amber-950 block">
                                {{ formatRupiah(userProfile?.tunjangan_beras) }}
                            </span>
                            <span class="text-[9px] text-amber-700 block font-medium">
                                {{ userProfile?.status_pernikahan || 'TK' }} ({{ userProfile?.jatah_beras_kg || 15 }} kg @ {{ formatRupiah(userProfile?.harga_beras_per_kg || 14000) }})
                            </span>
                        </div>

                        <div class="bg-emerald-50/70 p-2.5 rounded-xl border border-emerald-100">
                            <span class="text-[10px] text-emerald-700 font-semibold block uppercase tracking-wider">Upah Sejam (÷ 173)</span>
                            <span class="text-xs font-black text-emerald-900 block">
                                {{ userProfile?.upah_sejam ? formatRupiah(userProfile.upah_sejam) + '/jam' : 'Belum diatur' }}
                            </span>
                            <span class="text-[9px] text-emerald-600 block">
                                Dasar: {{ formatRupiah(userProfile?.total_dasar_upah) }}
                            </span>
                        </div>

                        <div class="col-span-2 sm:col-span-1 bg-slate-50 p-2.5 rounded-xl border border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-semibold block uppercase tracking-wider">TTD Saya</span>
                                <span class="text-xs font-semibold" :class="userProfile?.tanda_tangan ? 'text-emerald-700' : 'text-slate-400'">
                                    {{ userProfile?.tanda_tangan ? 'Tersimpan ✓' : 'Belum ada' }}
                                </span>
                            </div>
                            <button
                                v-if="userProfile?.tanda_tangan"
                                type="button"
                                @click="openMedia(userProfile.tanda_tangan, 'Tanda Tangan Digital Saya')"
                                class="text-[10px] font-bold text-emerald-700 hover:underline bg-white px-2 py-1 rounded border border-emerald-200"
                            >
                                Lihat TTD
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Statistik Bulan Ini -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <!-- Total Rupiah Upah Lembur -->
                <div class="bg-gradient-to-br from-emerald-800 to-emerald-950 text-white p-5 rounded-2xl shadow-lg shadow-emerald-900/20 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl font-bold">
                        💵
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-emerald-200 uppercase tracking-wider">Total Upah Lembur</div>
                        <div class="text-2xl font-black tracking-tight text-white mt-0.5">
                            {{ formatRupiah(totalUpahLembur) }}
                        </div>
                        <div class="text-[10px] text-emerald-300 mt-0.5">Estimasi Pendapatan {{ namaBulan }}</div>
                    </div>
                </div>

                <!-- Total Jam Dibayar (PP 35/2021) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl font-bold">
                        ⏱️
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Jam Dibayar (PP 35)</div>
                        <div class="text-2xl font-extrabold text-slate-900 mt-0.5">
                            {{ totalJamDibayar }} <span class="text-xs font-semibold text-slate-500">Jam</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Setelah pengali lembur</div>
                    </div>
                </div>

                <!-- Total Jam Nyata Fisik -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-2xl font-bold">
                        ⏳
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Durasi Kerja Fisik</div>
                        <div class="text-2xl font-extrabold text-slate-900 mt-0.5">
                            {{ totalJamNyata }} <span class="text-xs font-semibold text-slate-500">Jam</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Waktu aktual di lapangan</div>
                    </div>
                </div>

                <!-- Jumlah Hari Lembur -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-2xl font-bold">
                        📅
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Hari Kerja Lembur</div>
                        <div class="text-2xl font-extrabold text-slate-900 mt-0.5">
                            {{ lemburs.length }} <span class="text-xs font-semibold text-slate-500">Hari</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Status lembur disetujui</div>
                    </div>
                </div>
            </div>

            <!-- Banner Aturan Rumus Lembur Indonesia (PP 35/2021 & Standar PT Letawa) -->
            <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-md flex items-start gap-3.5">
                <span class="text-xl">📜</span>
                <div class="text-xs leading-relaxed space-y-1">
                    <div class="font-bold text-emerald-400 text-sm">
                        Ketentuan Perhitungan Upah Lembur (Standar PT Letawa & PP No. 35/2021):
                    </div>
                    <p class="text-slate-300">
                        • <strong>Dasar Upah Lembur Sebulan</strong>: Dihitung dari <strong>Gaji Pokok + Tunjangan Catu Beras</strong> (Jatah Kg Beras berdasarkan tanggungan × Harga Beras/Kg). Tarif per jam = <strong>(Gaji Pokok + Catu Beras) ÷ 173</strong>.
                    </p>
                    <p class="text-slate-300">
                        • <strong>Hari Kerja Biasa (Senin s.d. Sabtu)</strong>: Jam ke-1 dihitung <strong>1,5x</strong> upah sejam, dan setiap jam berikutnya dihitung <strong>2,0x</strong> upah sejam.
                    </p>
                    <p class="text-slate-300">
                        • <strong>Hari Jumat</strong>: Mengikuti penyesuaian jam kerja standar mingguan pabrik (total 40 jam kerja per minggu).
                    </p>
                    <p class="text-slate-300">
                        • <strong>Hari Istirahat / Libur Resmi (Minggu)</strong>: 7 jam pertama dibayar <strong>2,0x</strong>, jam ke-8 dibayar <strong>3,0x</strong>, jam ke-9 dan seterusnya dibayar <strong>4,0x</strong> upah sejam.
                    </p>
                </div>
            </div>

            <!-- Tabel Riwayat Lembur & Upah -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Rincian Lembur Bulan: {{ namaBulan }}
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Menampilkan seluruh instruksi lembur yang telah ditandatangani dan disetujui
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">
                        {{ lemburs.length }} Catatan Lembur
                    </span>
                </div>

                <!-- Tampilan Tabel Desktop -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-center w-10">No</th>
                                <th class="px-4 py-3 text-left w-36">Hari & Tanggal</th>
                                <th class="px-4 py-3 text-center w-28">Waktu Kerja</th>
                                <th class="px-4 py-3 text-center w-24">Durasi Fisik</th>
                                <th class="px-4 py-3 text-center w-28 bg-emerald-50/50 text-emerald-950 font-extrabold">Jam Dibayar (PP 35)</th>
                                <th class="px-4 py-3 text-right w-32 bg-emerald-50/50 text-emerald-950 font-extrabold">Upah Lembur (Rp)</th>
                                <th class="px-4 py-3 text-left">Jenis Pekerjaan</th>
                                <th class="px-4 py-3 text-center w-28">Bukti / TTD</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3 text-center text-slate-400 font-semibold">{{ index + 1 }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">
                                        <span class="text-emerald-700">{{ lembur.hari_nama }},</span> {{ formatTanggalIndo(lembur.tanggal) }}
                                    </div>
                                    <div v-if="lembur.hari_nama === 'Minggu'" class="text-[10px] text-purple-700 font-semibold">
                                        Libur Mingguan (2x - 4x)
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap font-mono text-slate-700 text-[11px]">
                                    <div v-if="lembur.jam_mulai">
                                        <div>{{ lembur.jam_mulai.slice(0,5) }} - {{ lembur.jam_selesai.slice(0,5) }}</div>
                                        <div v-if="lembur.jam_mulai_2 && lembur.jam_selesai_2" class="text-[10px] text-blue-700 font-bold">
                                            + {{ lembur.jam_mulai_2.slice(0,5) }} - {{ lembur.jam_selesai_2.slice(0,5) }}
                                        </div>
                                    </div>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap font-semibold text-slate-700">
                                    <div class="font-bold text-slate-900">{{ lembur.durasi_jam }} jam</div>
                                    <div v-if="lembur.has_sesi_2" class="text-[9.5px] text-blue-700 font-medium">
                                        ({{ lembur.durasi_sesi_1 }}j + {{ lembur.durasi_sesi_2 }}j)
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap font-bold text-emerald-800 bg-emerald-50/30">
                                    {{ lembur.jam_lembur_dibayar }} jam
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-black text-sm text-emerald-800 bg-emerald-50/30">
                                    {{ formatRupiah(lembur.upah_lembur) }}
                                </td>
                                <td class="px-4 py-3 text-slate-800">
                                    <div class="font-semibold">{{ lembur.keterangan }}</div>
                                    <div v-if="lembur.keperluan" class="text-[11px] text-slate-400 italic">
                                        {{ lembur.keperluan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            v-if="lembur.tanda_tangan"
                                            type="button"
                                            @click="openMedia(lembur.tanda_tangan, `Tanda Tangan Digital Tanggal ${lembur.tanggal}`)"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 text-[10px] font-bold transition"
                                            title="Lihat Tanda Tangan"
                                        >
                                            ✍️ TTD
                                        </button>
                                        <button
                                            v-if="lembur.foto_dokumentasi"
                                            type="button"
                                            @click="openMedia(lembur.foto_dokumentasi, `Dokumentasi Kerja Tanggal ${lembur.tanggal}`)"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-800 hover:bg-blue-100 border border-blue-200 text-[10px] font-bold transition"
                                            title="Lihat Foto Dokumentasi"
                                        >
                                            📷 Foto
                                        </button>
                                        <span v-if="!lembur.tanda_tangan && !lembur.foto_dokumentasi" class="text-slate-300">-</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="lemburs.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                                    Belum ada catatan lembur yang disetujui pada bulan {{ namaBulan }}.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="lemburs.length > 0" class="bg-slate-50 font-bold border-t border-slate-200 text-slate-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3.5 text-right uppercase tracking-wider text-xs">
                                    TOTAL BULAN {{ namaBulan }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs">{{ totalJamNyata }} jam</td>
                                <td class="px-4 py-3.5 text-center text-xs text-emerald-800 font-extrabold">{{ totalJamDibayar }} jam</td>
                                <td class="px-4 py-3.5 text-right text-sm text-emerald-800 font-black">{{ formatRupiah(totalUpahLembur) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Tampilan Kartu Khusus HP (Mobile View) -->
                <div class="block md:hidden divide-y divide-slate-100 bg-white">
                    <div
                        v-for="(lembur, index) in lemburs"
                        :key="lembur.id"
                        class="p-4 space-y-2.5"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-extrabold text-slate-900 text-sm">
                                    <span class="text-emerald-700">{{ lembur.hari_nama }},</span> {{ formatTanggalIndo(lembur.tanggal) }}
                                </div>
                                <div class="text-[11px] text-slate-600 mt-0.5">
                                    <span>{{ lembur.jam_mulai?.slice(0,5) }} - {{ lembur.jam_selesai?.slice(0,5) }}</span>
                                    <span v-if="lembur.jam_mulai_2 && lembur.jam_selesai_2" class="text-blue-700 font-bold ml-1">
                                        + {{ lembur.jam_mulai_2?.slice(0,5) }} - {{ lembur.jam_selesai_2?.slice(0,5) }}
                                    </span>
                                    <span class="text-slate-500 ml-1">({{ lembur.durasi_jam }} jam fisik)</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-emerald-800 text-sm">
                                    {{ formatRupiah(lembur.upah_lembur) }}
                                </div>
                                <div class="text-[10px] text-emerald-700 font-bold">
                                    {{ lembur.jam_lembur_dibayar }} Jam Bayar
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs">
                            <span class="text-slate-500 font-medium">Pekerjaan:</span>
                            <span class="font-bold text-slate-800 ml-1">{{ lembur.keterangan }}</span>
                            <div v-if="lembur.keperluan" class="text-[11px] text-slate-400 italic mt-0.5">
                                {{ lembur.keperluan }}
                            </div>
                        </div>

                        <div v-if="lembur.tanda_tangan || lembur.foto_dokumentasi" class="flex items-center gap-2 pt-1">
                            <button
                                v-if="lembur.tanda_tangan"
                                type="button"
                                @click="openMedia(lembur.tanda_tangan, `Tanda Tangan Digital Tanggal ${lembur.tanggal}`)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                            >
                                ✍️ Lihat TTD
                            </button>
                            <button
                                v-if="lembur.foto_dokumentasi"
                                type="button"
                                @click="openMedia(lembur.foto_dokumentasi, `Dokumentasi Kerja Tanggal ${lembur.tanggal}`)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold"
                            >
                                📷 Lihat Foto
                            </button>
                        </div>
                    </div>

                    <div v-if="lemburs.length === 0" class="p-8 text-center text-sm text-slate-400">
                        Belum ada riwayat lembur yang disetujui pada bulan {{ namaBulan }}.
                    </div>
                </div>

            </div>

        </div>

        <!-- Modal Media Viewer (TTD / Foto Dokumentasi Full Size) -->
        <div v-if="showMediaModal" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h4 class="font-bold text-sm text-slate-900">{{ mediaModalTitle }}</h4>
                    <button @click="showMediaModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-base">✕</button>
                </div>
                <div class="p-5 bg-slate-100 flex items-center justify-center min-h-60 max-h-[70vh]">
                    <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200/80 max-w-full flex items-center justify-center">
                        <img :src="mediaModalUrl" :alt="mediaModalTitle" class="max-h-[58vh] max-w-full object-contain rounded-lg" />
                    </div>
                </div>
                <div class="p-3 bg-white text-right border-t border-slate-100">
                    <button
                        @click="showMediaModal = false"
                        class="px-4 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
