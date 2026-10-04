<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    lemburs: Array,
    tanggal: String,
    userRole: String,
});

const selectedTanggal = ref(props.tanggal);

function filterTanggal() {
    router.get(route('lembur.print'), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function cetakDokumen() {
    window.print();
}

function formatTanggal(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

<template>
    <Head title="Cetak SPL" />

    <!-- Floating Action Toolbar (Hanya melayang di layar, 100% hilang saat dicetak) -->
    <div class="no-print fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-gray-900/90 backdrop-blur-md text-white px-4 py-2 rounded-full shadow-2xl border border-gray-700 text-sm">
        <div class="flex items-center gap-2 pr-2 border-r border-gray-600">
            <span class="text-xs text-gray-300">Tgl:</span>
            <input
                type="date"
                v-model="selectedTanggal"
                @change="filterTanggal"
                class="bg-gray-800 text-white text-xs border border-gray-600 rounded px-2 py-1 focus:ring-1 focus:ring-blue-400"
            />
        </div>
        <a :href="route('dashboard')" class="text-gray-300 hover:text-white px-2 py-1 rounded hover:bg-gray-800 transition text-xs font-medium">
            ← Kembali
        </a>
        <button
            @click="cetakDokumen"
            class="bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-1.5 rounded-full text-xs font-semibold shadow transition flex items-center gap-1.5"
        >
            <span>🖨️</span> Cetak Dokumen
        </button>
    </div>

    <!-- Tampilan Murni Kertas Lembur -->
    <div class="paper-wrapper min-h-screen bg-gray-100 py-6 px-4 print:p-0 print:m-0 print:bg-white flex justify-center">
        <div class="print-area bg-white w-full max-w-[210mm] p-8 shadow-md print:shadow-none print:p-0 print:w-full print:max-w-none text-black box-border" style="font-family: 'Times New Roman', Times, serif;">

            <!-- Header & Nomor Form -->
            <div class="relative mb-3">
                <div class="text-center pt-2">
                    <h1 class="text-base font-bold tracking-widest uppercase" style="letter-spacing: 2px;">
                        SURAT PERINTAH KERJA LEMBUR
                    </h1>
                </div>
                <div class="absolute right-0 top-0">
                    <span class="border border-black px-2 py-0.5 text-xs font-semibold">
                        PROS-HCM-02
                    </span>
                </div>
            </div>

            <!-- Info Perusahaan -->
            <div class="mb-3 text-xs leading-tight">
                <p class="font-bold mb-1">PT. __________________</p>
                <table class="text-xs">
                    <tr>
                        <td class="pr-2 py-0.5">Divisi / Departemen</td>
                        <td class="pr-2 py-0.5">:</td>
                        <td class="py-0.5">__________________</td>
                    </tr>
                    <tr>
                        <td class="pr-2 py-0.5">Tanggal</td>
                        <td class="pr-2 py-0.5">:</td>
                        <td class="py-0.5 font-medium">{{ formatTanggal(tanggal) }}</td>
                    </tr>
                </table>
            </div>

            <!-- Tabel SPL -->
            <table class="w-full border-collapse border border-black text-xs">
                <thead>
                    <tr class="bg-gray-50 print:bg-transparent">
                        <th class="border border-black px-1.5 py-1 text-center w-8 text-[11px]">No</th>
                        <th class="border border-black px-1.5 py-1 text-center w-24 text-[11px]">No. Employe</th>
                        <th class="border border-black px-1.5 py-1 text-center text-[11px]">Nama Karyawan</th>
                        <th class="border border-black px-1.5 py-1 text-center text-[11px]">Jenis Pekerjaan</th>
                        <th class="border border-black px-1.5 py-1 text-center w-28 text-[11px]">Rencana Waktu Lembur</th>
                        <th class="border border-black px-1.5 py-1 text-center text-[11px]">Keperluan</th>
                        <th class="border border-black px-1.5 py-1 text-center w-16 text-[11px]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(lembur, index) in lemburs" :key="lembur.id" class="h-6">
                        <td class="border border-black px-1 py-0.5 text-center text-[11px]">{{ index + 1 }}</td>
                        <td class="border border-black px-1 py-0.5 text-center text-[11px]">{{ lembur.user?.no_employe || '-' }}</td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px] font-medium">{{ lembur.user?.name }}</td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px]">{{ lembur.keterangan }}</td>
                        <td class="border border-black px-1 py-0.5 text-center text-[11px]">
                            <span v-if="lembur.jam_mulai && lembur.jam_selesai">
                                {{ lembur.jam_mulai?.slice(0,5) }} - {{ lembur.jam_selesai?.slice(0,5) }}
                            </span>
                            <span v-else class="text-gray-500">-</span>
                        </td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px]">{{ lembur.keperluan || '' }}</td>
                        <td class="border border-black px-1 py-0.5 text-center text-[10px]">
                            <span v-if="lembur.kategori === 'cuti' || lembur.keterangan === 'Cuti'" class="text-gray-700 font-bold">CUTI</span>
                            <span v-else-if="lembur.kategori === 'hk' || lembur.keterangan === 'HK'" class="text-gray-700 font-bold">HK</span>
                            <span v-else-if="lembur.kategori === 'izin'" class="text-purple-700 font-bold">IZIN</span>
                            <span v-else-if="lembur.status === 'disetujui'" class="text-green-700 font-bold">ACC</span>
                            <span v-else-if="lembur.status === 'ditolak'" class="text-red-700 font-bold">TOLAK</span>
                            <span v-else class="text-yellow-700 font-semibold">PENDING</span>
                        </td>
                    </tr>
                    <!-- Baris kosong agar format kertas penuh dan rapi (pas 1 halaman A4) -->
                    <tr v-for="i in Math.max(0, 26 - lemburs.length)" :key="'empty-' + i" class="h-6">
                        <td class="border border-black px-1 py-0.5 text-center text-[11px] text-gray-300">{{ lemburs.length + i }}</td>
                        <td class="border border-black px-1 py-0.5 text-[11px]">&nbsp;</td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px]">&nbsp;</td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px]">&nbsp;</td>
                        <td class="border border-black px-1 py-0.5 text-[11px]">&nbsp;</td>
                        <td class="border border-black px-1.5 py-0.5 text-[11px]">&nbsp;</td>
                        <td class="border border-black px-1 py-0.5 text-[11px]">&nbsp;</td>
                    </tr>
                </tbody>
            </table>

            <!-- Keterangan & Tempat TTD (Samping-sampingan di bawah tabel) -->
            <div class="mt-3 flex justify-between items-start text-xs pb-6 print:pb-0">
                <!-- Keterangan di sebelah kiri -->
                <div class="text-[11px] leading-relaxed text-gray-800">
                    <p class="font-semibold text-black">Keterangan :</p>
                    <p>- Form ini harap diberikan ke HR Operation / HRGA</p>
                    <p>- Selambat-lambatnya 1 (satu) hari sebelum kerja lembur</p>
                    <p>- Tembusan ini untuk Dept. GA atau Security</p>
                </div>

                <!-- TTD di sebelah kanan di samping keterangan, ukuran kecil dan rapi -->
                <div>
                    <table class="border-collapse border border-black text-center text-xs w-52">
                        <thead>
                            <tr>
                                <th class="border border-black px-2 py-0.5 font-normal text-[11px] w-26">Mengetahui</th>
                                <th class="border border-black px-2 py-0.5 font-normal text-[11px] w-26">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-black h-12"></td>
                                <td class="border border-black h-12"></td>
                            </tr>
                            <tr>
                                <td class="border border-black px-1 py-0.5 text-[10px]">Askep</td>
                                <td class="border border-black px-1 py-0.5 text-[10px]">Asisten</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
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

    /* Pengaturan kertas A4 Portrait */
    @page {
        size: A4 portrait;
        margin: 8mm 10mm;
    }

    tr {
        page-break-inside: avoid;
    }
}
</style>
