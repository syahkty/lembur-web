<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    items: Array,
    tanggal: String,
    totalJamDibayar: Number,
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
const divisi = ref('PKS');
const departemen = ref('Pabrik');
const periode = ref('');

// Penandatangan default sesuai foto fisik
const namaDisetujui = ref('Randy H. Prabowo');
const jabatanDisetujui = ref('Administratur');

const namaDiketahui = ref('Slamet Atokah');
const jabatanDiketahui = ref('KTU');

const namaDibuat = ref('Tommy M.');
const jabatanDibuat = ref('Kabag HRGA');

function filterTanggal() {
    const target = props.previewMode ? 'dokumen.preview.lrlk' : 'lembur.lrlk.print';
    router.get(route(target), { tanggal: selectedTanggal.value }, { preserveState: true });
}

function cetakDokumen() {
    window.print();
}

// Format tanggal dd/mm/yyyy sesuai lembar fisik (contoh: 05/09/2026)
function formatTanggalFisik(tgl) {
    if (!tgl) return '';
    const d = new Date(tgl);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// Buat baris pas 35 baris seperti blangko asli fisik
const MAX_BARIS = 35;
const paddedItems = computed(() => {
    const list = [...props.items];
    const sisa = MAX_BARIS - list.length;
    for (let i = 0; i < sisa; i++) {
        list.push({
            no: list.length + 1,
            no_employe: '',
            nama: '',
            dept: '',
            tgl: '',
            jenis_pekerjaan: '',
            jam_dibayar: '',
            jumlah_rp: '',
            hasil_kerja: '',
            is_empty: true,
        });
    }
    return list;
});
</script>

<template>
    <Head :title="previewMode ? 'Preview LRLK' : 'Cetak LRLK'" />

    <!-- Floating Action Toolbar (Hanya muncul di layar, 100% hilang saat cetak/print) -->
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
            <a :href="route('dokumen.preview.spl', { tanggal: selectedTanggal })" class="text-emerald-400 hover:text-emerald-300 px-2 py-1 rounded hover:bg-slate-800 transition font-bold">
                📄 Preview SPL
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
            <a :href="route('lembur.print', { tanggal: selectedTanggal })" class="text-emerald-400 hover:text-emerald-300 px-2 py-1 rounded hover:bg-slate-800 transition font-bold">
                📄 Cetak SPL
            </a>
            <button
                @click="cetakDokumen"
                class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-1.5 rounded-full font-bold shadow transition flex items-center gap-1.5"
            >
                <span>🖨️</span> Cetak
            </button>
        </template>
    </div>

    <!-- Tampilan Murni Kertas A4 Sesuai Foto Fisik -->
    <!-- Mode preview: kertas disembunyikan saat Ctrl+P agar tidak bisa dicetak -->
    <div
        class="paper-wrapper min-h-screen bg-slate-200 py-6 px-4 print:p-0 print:m-0 print:bg-white flex justify-center"
        :class="{ 'preview-locked': previewMode }"
    >
        <div class="print-area bg-white w-full max-w-[210mm] p-6 shadow-xl print:shadow-none print:p-0 print:w-full print:max-w-none text-black box-border" style="font-family: Arial, Helvetica, sans-serif;">

            <!-- Header & Kop Dokumen -->
            <div class="relative mb-2">
                <!-- Kanan atas: Box PROS-HCM-02 -->
                <div class="absolute right-0 top-0">
                    <span class="border border-black px-2 py-0.5 text-[10px] font-bold tracking-wider">
                        PROS-HCM-02
                    </span>
                </div>

                <!-- Tengah: Judul Laporan -->
                <div class="text-center pt-1">
                    <h1 class="text-sm font-bold tracking-wide uppercase">
                        LAPORAN REALISASI KERJA LEMBUR
                    </h1>
                    <div class="text-[11px] mt-0.5">
                        <span>Periode : </span>
                        <input
                            type="text"
                            v-model="periode"
                            placeholder="........................................"
                            class="no-print-border text-[11px] border-b border-dotted border-black p-0 focus:outline-none w-48 text-center"
                        />
                    </div>
                </div>

                <!-- Kiri: Info Perusahaan & Departemen -->
                <div class="text-[11px] leading-tight mt-1">
                    <p class="font-bold text-xs mb-1">PT. LETAWA</p>
                    <table class="text-[11px] leading-tight">
                        <tr>
                            <td class="pr-2 py-0.5 w-24">Divisi</td>
                            <td class="pr-2 py-0.5">:</td>
                            <td class="py-0.5">
                                <input
                                    type="text"
                                    v-model="divisi"
                                    class="no-print-border text-[11px] p-0 w-32 focus:outline-none"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td class="pr-2 py-0.5">Departemen</td>
                            <td class="pr-2 py-0.5">:</td>
                            <td class="py-0.5 font-semibold">
                                <input
                                    type="text"
                                    v-model="departemen"
                                    class="no-print-border text-[11px] p-0 w-32 focus:outline-none font-semibold"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td class="pr-2 py-0.5">Tanggal</td>
                            <td class="pr-2 py-0.5">:</td>
                            <td class="py-0.5 font-bold">{{ formatTanggalFisik(tanggal) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tabel Realisasi 9 Kolom (Persis seperti di Foto) -->
            <table class="w-full border-collapse border border-black text-[10px] leading-tight">
                <thead>
                    <tr class="bg-gray-100 print:bg-transparent text-center font-bold">
                        <th class="border border-black px-1 py-1 w-6 text-center">No</th>
                        <th class="border border-black px-1 py-1 w-20 text-center">No. Employe</th>
                        <th class="border border-black px-1 py-1 text-center w-36">Nama Karyawan</th>
                        <th class="border border-black px-1 py-1 w-14 text-center">Dept</th>
                        <th class="border border-black px-1 py-1 w-14 text-center">Tgl</th>
                        <th class="border border-black px-1.5 py-1 text-center">Jenis Pekerjaan</th>
                        <th class="border border-black px-1 py-1 w-16 text-center leading-tight">
                            Jml Jam<br />Dibayar
                        </th>
                        <th class="border border-black px-1 py-1 w-16 text-center leading-tight">
                            Jumlah<br />( Rp )
                        </th>
                        <th class="border border-black px-1 py-1 w-20 text-center">Hasil Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in paddedItems"
                        :key="item.no"
                        class="h-[18px]"
                    >
                        <!-- No -->
                        <td class="border border-black px-1 py-0 text-center text-[10px]">
                            {{ item.no }}
                        </td>

                        <!-- No. Employe -->
                        <td class="border border-black px-1 py-0 font-mono text-[9.5px]">
                            {{ item.no_employe }}
                        </td>

                        <!-- Nama Karyawan -->
                        <td class="border border-black px-1 py-0 uppercase truncate max-w-[150px] font-semibold text-[10px]">
                            {{ item.nama }}
                        </td>

                        <!-- Dept -->
                        <td class="border border-black px-1 py-0 text-center text-[10px]">
                            {{ item.dept }}
                        </td>

                        <!-- Tgl -->
                        <td class="border border-black px-1 py-0 text-center text-[9.5px] font-mono">
                            {{ item.tgl }}
                        </td>

                        <!-- Jenis Pekerjaan -->
                        <td class="border border-black px-1 py-0 text-[9.5px] truncate max-w-[160px]">
                            {{ item.jenis_pekerjaan }}
                        </td>

                        <!-- Jml Jam Dibayar -->
                        <td class="border border-black px-1 py-0 text-center font-bold text-[10px]">
                            {{ item.jam_dibayar }}
                        </td>

                        <!-- Jumlah (Rp) -->
                        <td class="border border-black px-1 py-0 text-center text-[9.5px]">
                            {{ item.jumlah_rp }}
                        </td>

                        <!-- Hasil Kerja -->
                        <td class="border border-black px-1 py-0 text-[9.5px]">
                            {{ item.hasil_kerja }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Kotak Tanda Tangan (3 Kolom Persis di Bawah Kanan Tabel Foto) -->
            <div class="mt-2 flex justify-end">
                <table class="border-collapse border border-black text-[10px] text-center w-80">
                    <thead>
                        <tr class="bg-gray-50 print:bg-transparent font-bold">
                            <th class="border border-black px-2 py-0.5 w-1/3">Disetujui</th>
                            <th class="border border-black px-2 py-0.5 w-1/3">Diketahui</th>
                            <th class="border border-black px-2 py-0.5 w-1/3">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Space untuk Tanda Tangan Digital / Basah -->
                        <tr class="h-14">
                            <!-- Disetujui: Administratur -->
                            <td class="border border-black align-bottom pb-1 relative">
                                <img
                                    v-if="docSignatures?.administratur?.tanda_tangan"
                                    :src="docSignatures.administratur.tanda_tangan"
                                    alt="TTD Administratur"
                                    class="absolute inset-0 max-h-10 max-w-full object-contain m-auto"
                                />
                                <div class="font-bold text-center w-full text-[9.5px]">
                                    {{ docSignatures?.administratur?.nama || namaDisetujui }}
                                </div>
                                <div class="text-[8.5px] text-gray-700 leading-none mt-0.5">
                                    {{ docSignatures?.administratur?.jabatan || jabatanDisetujui }}
                                </div>
                            </td>
                            <!-- Diketahui: KTU (hanya TTD milik KTU) -->
                            <td class="border border-black align-bottom pb-1 relative">
                                <img
                                    v-if="docSignatures?.ktu?.tanda_tangan"
                                    :src="docSignatures.ktu.tanda_tangan"
                                    alt="TTD KTU"
                                    class="absolute inset-0 max-h-10 max-w-full object-contain m-auto"
                                />
                                <div class="font-bold text-center w-full text-[9.5px]">
                                    {{ docSignatures?.ktu?.nama || namaDiketahui }}
                                </div>
                                <div class="text-[8.5px] text-gray-700 leading-none mt-0.5">
                                    {{ docSignatures?.ktu?.jabatan || jabatanDiketahui }}
                                </div>
                            </td>
                            <!-- Dibuat: Asisten (hanya TTD milik Asisten) -->
                            <td class="border border-black align-bottom pb-1 relative">
                                <img
                                    v-if="docSignatures?.asisten?.tanda_tangan"
                                    :src="docSignatures.asisten.tanda_tangan"
                                    alt="TTD Asisten"
                                    class="absolute inset-0 max-h-10 max-w-full object-contain m-auto"
                                />
                                <div class="font-bold text-center w-full text-[9.5px]">
                                    {{ docSignatures?.asisten?.nama || namaDibuat }}
                                </div>
                                <div class="text-[8.5px] text-gray-700 leading-none mt-0.5">
                                    {{ docSignatures?.asisten?.jabatan || jabatanDibuat }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .no-print-border {
        border: none !important;
        background: transparent !important;
    }
    .paper-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
    }
    /* Mode preview penandatangan: dokumen tidak boleh dicetak */
    .paper-wrapper.preview-locked {
        display: none !important;
    }
    .print-area {
        box-shadow: none !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    @page {
        size: A4 portrait;
        margin: 8mm 8mm 6mm 8mm;
    }
}
</style>
