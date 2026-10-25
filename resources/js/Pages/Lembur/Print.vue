<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    lemburs: Array,
    tanggal: String,
    userRole: String,
    docSignatures: {
        type: Object,
        default: () => ({}),
    },
    // true = mode preview untuk penandatangan (tanpa opsi cetak)
    previewMode: {
        type: Boolean,
        default: false,
    },
});

const selectedTanggal = ref(props.tanggal);

function filterTanggal() {
    const target = props.previewMode ? 'dokumen.preview.spl' : 'lembur.print';
    router.get(route(target), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function cetakDokumen() {
    window.print();
}

// Format tanggal dd/mm/yyyy sesuai lembar fisik (contoh: 04/09/2026)
function formatTanggalFisik(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// Buat 36 baris (18 pasang kotak TTD) agar pas persis 1 halaman A4 Portrait seperti formulir fisik
const MAX_BARIS = 36;
const paddedLemburs = computed(() => {
    const list = [...props.lemburs];
    const sisa = MAX_BARIS - list.length;
    for (let i = 0; i < sisa; i++) {
        list.push({
            id: 'empty-' + (list.length + 1),
            user: { no_employe: '', name: '' },
            keterangan: '',
            jam_mulai: '',
            jam_selesai: '',
            keperluan: '',
            tanda_tangan: null,
            is_empty: true,
        });
    }
    return list;
});
</script>

<template>
    <Head :title="previewMode ? 'Preview SPL' : 'Cetak'" />

    <!-- Floating Action Toolbar (Hanya melayang di layar, 100% hilang saat dicetak) -->
    <div class="no-print fixed bottom-6 right-6 z-50 flex items-center gap-2.5 bg-slate-900/95 backdrop-blur-md text-white px-4 py-2.5 rounded-full shadow-2xl border border-slate-700 text-xs">
        <div class="flex items-center gap-2 pr-2 border-r border-slate-700">
            <span class="text-[11px] text-slate-300 font-semibold">Tgl:</span>
            <input
                type="date"
                v-model="selectedTanggal"
                @change="filterTanggal"
                class="bg-slate-800 text-white text-xs border border-slate-600 rounded px-2 py-1 focus:ring-1 focus:ring-emerald-400"
            />
        </div>
        <!-- Mode Preview (Penandatangan): tanpa tombol cetak -->
        <template v-if="previewMode">
            <a :href="route('dokumen.index', { tanggal: selectedTanggal })" class="text-slate-300 hover:text-white px-2 py-1 rounded hover:bg-slate-800 transition font-medium">
                ← Kembali
            </a>
            <a :href="route('dokumen.preview.lrlk', { tanggal: selectedTanggal })" class="text-emerald-400 hover:text-emerald-300 px-2 py-1 rounded hover:bg-slate-800 transition font-bold">
                📊 Preview LRLK
            </a>
            <span class="bg-amber-500/20 text-amber-300 border border-amber-500/40 px-3 py-1 rounded-full font-bold">
                👁️ Mode Preview
            </span>
        </template>

        <!-- Mode Cetak (Admin) -->
        <template v-else>
            <a :href="route('dashboard')" class="text-slate-300 hover:text-white px-2 py-1 rounded hover:bg-slate-800 transition font-medium">
                ← Dashboard
            </a>
            <a :href="route('lembur.lrlk.print', { tanggal: selectedTanggal })" class="text-emerald-400 hover:text-emerald-300 px-2 py-1 rounded hover:bg-slate-800 transition font-bold">
                📊 Cetak LRLK
            </a>
            <button
                @click="cetakDokumen"
                class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-1.5 rounded-full font-bold shadow transition flex items-center gap-1.5"
            >
                <span>🖨️</span> Cetak
            </button>
        </template>
    </div>

    <!-- Tampilan Murni Kertas Lembur Sesuai Foto Fisik PROS-HCM-02 -->
    <!-- Mode preview: kertas disembunyikan saat Ctrl+P agar tidak bisa dicetak -->
    <div
        class="paper-wrapper min-h-screen bg-slate-200 py-6 px-4 print:p-0 print:m-0 print:bg-white flex justify-center"
        :class="{ 'preview-locked': previewMode }"
    >
        <div class="print-area bg-white w-full max-w-[210mm] p-6 shadow-xl print:shadow-none print:p-0 print:w-full print:max-w-none text-black box-border" style="font-family: Arial, Helvetica, sans-serif;">

            <!-- Header & Nomor Form PROS-HCM-02 -->
            <div class="relative mb-2">
                <!-- Kanan atas: Box PROS-HCM-02 -->
                <div class="absolute right-0 top-0">
                    <span class="border border-black px-2 py-0.5 text-[10px] font-bold tracking-wider">
                        PROS-HCM-02
                    </span>
                </div>

                <!-- Tengah: Judul SPL -->
                <div class="text-center pt-1">
                    <h1 class="text-sm font-bold tracking-wider uppercase">
                        SURAT PERINTAH KERJA LEMBUR
                    </h1>
                </div>

                <!-- Kiri: Info Perusahaan, Departemen, & Tanggal -->
                <div class="text-[11px] leading-tight mt-1">
                    <p class="font-bold text-xs mb-1">PT. LETAWA</p>
                    <table class="text-[11px] leading-tight">
                        <tr>
                            <td class="pr-2 py-0.5 w-24">Departemen</td>
                            <td class="pr-2 py-0.5">:</td>
                            <td class="py-0.5 font-semibold">Pabrik</td>
                        </tr>
                        <tr>
                            <td class="pr-2 py-0.5">Tanggal</td>
                            <td class="pr-2 py-0.5">:</td>
                            <td class="py-0.5 font-bold">{{ formatTanggalFisik(tanggal) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tabel SPL Persis Foto Fisik (7 Kolom dengan Tanda Tangan 2 Sub-kolom) -->
            <table class="w-full border-collapse border border-black text-[10px] leading-tight">
                <thead>
                    <tr class="bg-gray-100 print:bg-transparent font-bold text-center">
                        <th class="border border-black px-1 py-1 text-center w-7 text-[10px]">No</th>
                        <th class="border border-black px-1 py-1 text-center w-20 text-[10px]">No_Employe</th>
                        <th class="border border-black px-1.5 py-1 text-center w-36 text-[10px]">Nama Karyawan</th>
                        <th class="border border-black px-1.5 py-1 text-center text-[10px]">Jenis Pekerjaan</th>
                        <th class="border border-black px-1 py-1 text-center w-32 text-[9.5px]">Rencana Waktu Lembur</th>
                        <th class="border border-black px-1 py-1 text-center w-16 text-[10px]">Keperluan</th>
                        <th colspan="2" class="border border-black px-1 py-1 text-center w-28 text-[10px]">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(lembur, index) in paddedLemburs"
                        :key="lembur.id || index"
                        class="h-[18px]"
                    >
                        <!-- No -->
                        <td class="border border-black px-1 py-0 text-center text-[10px]">
                            {{ index + 1 }}
                        </td>

                        <!-- No_Employe -->
                        <td class="border border-black px-1 py-0 font-mono text-[9.5px] text-center">
                            {{ lembur.user?.no_employe || '' }}
                        </td>

                        <!-- Nama Karyawan -->
                        <td class="border border-black px-1.5 py-0 uppercase truncate max-w-[140px] text-[10px]">
                            {{ lembur.user?.name || '' }}
                        </td>

                        <!-- Jenis Pekerjaan -->
                        <td class="border border-black px-1.5 py-0 text-[9px] truncate max-w-[130px]">
                            <span v-if="lembur.keterangan">
                                {{ lembur.keterangan }}<span v-if="lembur.keterangan_2 && lembur.keterangan_2 !== lembur.keterangan" class="text-black/75"> / {{ lembur.keterangan_2 }}</span>
                            </span>
                        </td>

                        <!-- Rencana Waktu Lembur (Tetap 1 Baris: Sesi 1 | Sesi 2) -->
                        <td class="border border-black px-1 py-0 text-center text-[8.5px] font-mono whitespace-nowrap">
                            <span v-if="lembur.jam_mulai && lembur.jam_selesai">
                                {{ lembur.jam_mulai?.slice(0,5) }} - {{ lembur.jam_selesai?.slice(0,5) }}<template v-if="lembur.jam_mulai_2 && lembur.jam_selesai_2"> | {{ lembur.jam_mulai_2?.slice(0,5) }} - {{ lembur.jam_selesai_2?.slice(0,5) }}</template>
                            </span>
                            <span v-else-if="lembur.is_empty"></span>
                            <span v-else>-</span>
                        </td>

                        <!-- Keperluan -->
                        <td class="border border-black px-1 py-0 text-[9.5px] text-center">
                            {{ lembur.keperluan || '' }}
                        </td>

                        <!-- Tanda Tangan: 2 sub-kolom dengan rowspan="2" untuk setiap pasangan baris ganjil & genap -->
                        <template v-if="index % 2 === 0">
                            <!-- Kolom Kiri: untuk Karyawan Ganjil (No. 1, 3, 5, dst) -->
                            <td
                                rowspan="2"
                                class="border border-black p-0 w-14 relative align-top text-left bg-white"
                            >
                                <div class="relative w-full h-[36px] flex items-center justify-center">
                                    <span class="absolute top-0.5 left-1 text-[8.5px] font-bold text-black leading-none">
                                        {{ index + 1 }}
                                    </span>
                                    <img
                                        v-if="lembur.tanda_tangan"
                                        :src="lembur.tanda_tangan"
                                        alt="TTD"
                                        class="max-h-[30px] max-w-[48px] object-contain"
                                    />
                                </div>
                            </td>

                            <!-- Kolom Kanan: untuk Karyawan Genap (No. 2, 4, 6, dst) -->
                            <td
                                rowspan="2"
                                class="border border-black p-0 w-14 relative align-top text-left bg-white"
                            >
                                <div class="relative w-full h-[36px] flex items-center justify-center">
                                    <span class="absolute top-0.5 left-1 text-[8.5px] font-bold text-black leading-none">
                                        {{ index + 2 }}
                                    </span>
                                    <img
                                        v-if="paddedLemburs[index + 1]?.tanda_tangan"
                                        :src="paddedLemburs[index + 1].tanda_tangan"
                                        alt="TTD"
                                        class="max-h-[30px] max-w-[48px] object-contain"
                                    />
                                </div>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>

            <!-- Keterangan & Tempat TTD (Samping-sampingan di bawah tabel) -->
            <div class="mt-2.5 flex justify-between items-start text-xs pb-4 print:pb-0">
                <!-- Keterangan di sebelah kiri -->
                <div class="text-[10px] leading-relaxed text-gray-800">
                    <p class="font-bold text-black">Keterangan :</p>
                    <p>- Form ini harap diberikan ke HR Operation / HRGA</p>
                    <p>- Selambat-lambatnya 1 (satu) hari sebelum kerja lembur</p>
                    <p>- Tembusan ini untuk Dept. GA atau Security</p>
                </div>

                <!-- TTD di sebelah kanan di samping keterangan -->
                <div>
                    <table class="border-collapse border border-black text-center text-xs w-48">
                        <thead>
                            <tr class="bg-gray-50 print:bg-transparent font-bold">
                                <th class="border border-black px-2 py-0.5 font-normal text-[10px] w-1/2">Mengetahui</th>
                                <th class="border border-black px-2 py-0.5 font-normal text-[10px] w-1/2">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="h-12">
                                <td class="border border-black relative">
                                    <img
                                        v-if="docSignatures?.askep?.tanda_tangan"
                                        :src="docSignatures.askep.tanda_tangan"
                                        alt="TTD Askep"
                                        class="absolute inset-0 max-h-10 max-w-full object-contain m-auto"
                                    />
                                </td>
                                <td class="border border-black relative">
                                    <img
                                        v-if="docSignatures?.asisten?.tanda_tangan"
                                        :src="docSignatures.asisten.tanda_tangan"
                                        alt="TTD Asisten"
                                        class="absolute inset-0 max-h-10 max-w-full object-contain m-auto"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-black px-1 py-0.5 text-[9.5px]">Askep</td>
                                <td class="border border-black px-1 py-0.5 text-[9.5px]">Asisten</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    /* Sembunyikan elemen non-cetak */
    .no-print,
    .no-print * {
        display: none !important;
    }

    /* Reset body dan background */
    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .paper-wrapper {
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
        display: block !important;
    }

    .print-area {
        max-width: 100% !important;
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* Mode preview penandatangan: dokumen tidak boleh dicetak */
    .paper-wrapper.preview-locked {
        display: none !important;
    }

    /* Pengaturan kertas A4 Portrait */
    @page {
        size: A4 portrait;
        margin: 8mm 8mm 6mm 8mm;
    }

    tr {
        page-break-inside: avoid;
    }
}
</style>
