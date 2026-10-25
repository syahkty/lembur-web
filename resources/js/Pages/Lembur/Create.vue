<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { showConfirmDialog, showSuccessAlert, showErrorAlert } from '@/utils/swal';

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

// Preset Sesi 2 (Lembur Lanjutan / Malam)
const presetAktifSesi2 = ref(false);
const presetJamMulai2 = ref('21:00');
const presetJamSelesai2 = ref('02:00');
const presetPekerjaan2 = ref('');

// Shortcut Preset Populer (Hanya 2 Sesuai Permintaan)
const presetShortcuts = [
    {
        nama: '16:00 - 19:00',
        s1_start: '16:00',
        s1_end: '19:00',
        s2_active: false,
        pekerjaan: 'Lanjut Proses',
    },
    {
        nama: '04:00 - 07:00',
        s1_start: '04:00',
        s1_end: '07:00',
        s2_active: false,
        pekerjaan: 'Lanjut Proses',
    },
];

function applyShortcut(sc) {
    presetJamMulai.value = sc.s1_start;
    presetJamSelesai.value = sc.s1_end;
    presetPekerjaan.value = sc.pekerjaan;
    presetAktifSesi2.value = sc.s2_active;
    if (sc.s2_active) {
        presetJamMulai2.value = sc.s2_start;
        presetJamSelesai2.value = sc.s2_end;
    }
}

// Filter & Pencarian (Sangat membantu mandor di HP)
const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'lembur', 'tidak_lembur'

// Modal tambah karyawan manual (pengganti sementara) — hanya untuk mandor
const showTambahManual = ref(false);
const namaManual = ref('');
const noEmployeManual = ref('');
const stasiunManual = ref('');
const nextTempId = ref(-1);

function tambahKaryawanManual() {
    if (!namaManual.value.trim()) return;
    const stasiunObj = props.stasions.find(s => s.id == stasiunManual.value);
    items.value.unshift({
        user_id: nextTempId.value--,
        name: namaManual.value.trim(),
        no_employe: noEmployeManual.value.trim() || '-',
        stasiun_nama: stasiunObj ? stasiunObj.nama : 'Pabrik (Pengganti)',
        shift: Number(selectedShift.value) || 1,
        sertakan: true,
        lembur: true,
        keterangan: presetPekerjaan.value || 'Lanjut Proses',
        jam_mulai: presetJamMulai.value || '16:00',
        jam_selesai: presetJamSelesai.value || '19:00',
        has_sesi_2: presetAktifSesi2.value,
        jam_mulai_2: presetAktifSesi2.value ? presetJamMulai2.value : '',
        jam_selesai_2: presetAktifSesi2.value ? presetJamSelesai2.value : '',
        keterangan_2: presetAktifSesi2.value ? (presetPekerjaan2.value || presetPekerjaan.value) : '',
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
    showSuccessAlert('Karyawan Pengganti Ditambahkan', 'Data siap disertakan dalam SPL hari ini.');
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
        lembur: true, // true = Lembur, false = Tidak Lembur
        keterangan: presetPekerjaan.value,
        jam_mulai: presetJamMulai.value,
        jam_selesai: presetJamSelesai.value,
        has_sesi_2: false,
        jam_mulai_2: '',
        jam_selesai_2: '',
        keterangan_2: '',
        keperluan: '',
        is_manual: false,
    }))
);

// Filter berdasarkan shift & search query
const shiftFilteredItems = computed(() => {
    if (selectedShift.value === 'all') return items.value;
    return items.value.filter(item => item.shift === Number(selectedShift.value));
});

const filteredItems = computed(() => {
    return shiftFilteredItems.value.filter(item => {
        // Filter Status
        if (statusFilter.value === 'lembur' && !item.lembur) return false;
        if (statusFilter.value === 'tidak_lembur' && item.lembur) return false;

        // Pencarian Nama / No Employe / Stasiun
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            const matchName = item.name.toLowerCase().includes(q);
            const matchNo = item.no_employe.toLowerCase().includes(q);
            const matchStasiun = (item.stasiun_nama || '').toLowerCase().includes(q);
            return matchName || matchNo || matchStasiun;
        }

        return true;
    });
});

// Hitungan statistik real-time
const totalShiftCount = computed(() => shiftFilteredItems.value.length);
const countLembur = computed(() => shiftFilteredItems.value.filter(i => i.sertakan && i.lembur).length);
const countTidakLembur = computed(() => shiftFilteredItems.value.filter(i => i.sertakan && !i.lembur).length);

// Quick Chips untuk mempermudah mandor di HP
const quickPekerjaanChips = ['Lanjut Proses', 'Cleaning Pabrik', 'Bongkar Muat', 'Maintenance Mesin'];
const quickAlasanTidakLembur = ['Cuti', 'HK', 'Sakit', 'Izin', 'Off / Libur'];
const quickTimePresets = [
    { label: '16:00 - 19:00 (3j)', start: '16:00', end: '19:00' },
    { label: '04:00 - 07:00 (3j)', start: '04:00', end: '07:00' },
];
const quickTimePresets2 = [
    { label: '21:00 - 02:00 (5j)', start: '21:00', end: '02:00' },
    { label: '23:00 - 02:00 (3j)', start: '23:00', end: '02:00' },
    { label: '19:00 - 22:00 (3j)', start: '19:00', end: '22:00' },
    { label: '22:00 - 04:00 (6j)', start: '22:00', end: '04:00' },
];

function hitungJam(mulai, selesai) {
    if (!mulai || !selesai) return 0;
    const [h1, m1] = mulai.split(':').map(Number);
    const [h2, m2] = selesai.split(':').map(Number);
    let diff = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (diff < 0) diff += 24 * 60;
    return Math.round((diff / 60) * 10) / 10;
}

function hitungTotalJamItem(item) {
    if (!item.lembur) return 0;
    const s1 = hitungJam(item.jam_mulai, item.jam_selesai);
    const s2 = item.has_sesi_2 ? hitungJam(item.jam_mulai_2, item.jam_selesai_2) : 0;
    return Math.round((s1 + s2) * 10) / 10;
}

function setQuickTime(item, start, end) {
    item.jam_mulai = start;
    item.jam_selesai = end;
}

function setQuickTime2(item, start, end) {
    item.jam_mulai_2 = start;
    item.jam_selesai_2 = end;
}

function tambahSesi2(item) {
    item.has_sesi_2 = true;
    item.jam_mulai_2 = presetJamMulai2.value || '21:00';
    item.jam_selesai_2 = presetJamSelesai2.value || '02:00';
    item.keterangan_2 = item.keterangan || presetPekerjaan.value;
}

function hapusSesi2(item) {
    item.has_sesi_2 = false;
    item.jam_mulai_2 = '';
    item.jam_selesai_2 = '';
    item.keterangan_2 = '';
}

// Handler toggle Lembur / Tidak Lembur
function onToggleLembur(item, isLembur) {
    item.lembur = isLembur;
    if (isLembur) {
        if (!item.keterangan || quickAlasanTidakLembur.includes(item.keterangan)) {
            item.keterangan = presetPekerjaan.value || 'Lanjut Proses';
        }
        item.jam_mulai = presetJamMulai.value || '16:00';
        item.jam_selesai = presetJamSelesai.value || '19:00';
        if (presetAktifSesi2.value) {
            item.has_sesi_2 = true;
            item.jam_mulai_2 = presetJamMulai2.value || '21:00';
            item.jam_selesai_2 = presetJamSelesai2.value || '02:00';
            item.keterangan_2 = presetPekerjaan2.value || item.keterangan;
        }
    } else {
        item.jam_mulai = '';
        item.jam_selesai = '';
        item.has_sesi_2 = false;
        item.jam_mulai_2 = '';
        item.jam_selesai_2 = '';
        item.keterangan_2 = '';
        if (item.keterangan === 'Lanjut Proses' || item.keterangan === presetPekerjaan.value) {
            item.keterangan = '';
        }
    }
}

// Terapkan preset ke seluruh yang lembur
function terapkanPreset() {
    shiftFilteredItems.value.forEach(item => {
        if (item.lembur) {
            item.jam_mulai = presetJamMulai.value;
            item.jam_selesai = presetJamSelesai.value;
            item.keterangan = presetPekerjaan.value;
            item.has_sesi_2 = presetAktifSesi2.value;
            if (presetAktifSesi2.value) {
                item.jam_mulai_2 = presetJamMulai2.value;
                item.jam_selesai_2 = presetJamSelesai2.value;
                item.keterangan_2 = presetPekerjaan2.value || presetPekerjaan.value;
            } else {
                item.jam_mulai_2 = '';
                item.jam_selesai_2 = '';
                item.keterangan_2 = '';
            }
        }
    });
    const infoSesi = presetAktifSesi2.value ? '2 Sesi (Siang & Malam)' : '1 Sesi Standar';
    showSuccessAlert('Preset Diterapkan', `Jam dan pekerjaan (${infoSesi}) diperbarui untuk seluruh karyawan lembur.`);
}

const isSubmitting = ref(false);

async function submit() {
    const lemburCount = shiftFilteredItems.value.filter(i => i.sertakan && i.lembur).length;
    const tidakLemburCount = shiftFilteredItems.value.filter(i => i.sertakan && !i.lembur).length;

    const confirmed = await showConfirmDialog({
        title: 'Terbitkan SPL Hari Ini?',
        text: `Shift ${selectedShift.value === 'all' ? '1 & 2' : selectedShift.value}: ${lemburCount} Karyawan Lembur, ${tidakLemburCount} Tidak Lembur. Pastikan data sudah benar.`,
        confirmText: 'Ya, Terbitkan SPL',
        cancelText: 'Periksa Lagi',
    });

    if (!confirmed) return;

    isSubmitting.value = true;
    router.post(route('lembur.store'), {
        tanggal: tanggal.value,
        items: shiftFilteredItems.value.map(item => ({
            user_id: item.user_id,
            sertakan: item.sertakan,
            kategori: item.lembur ? 'lembur' : 'tidak_lembur',
            keterangan: item.keterangan,
            jam_mulai: item.lembur ? (item.jam_mulai || null) : null,
            jam_selesai: item.lembur ? (item.jam_selesai || null) : null,
            jam_mulai_2: (item.lembur && item.has_sesi_2) ? (item.jam_mulai_2 || null) : null,
            jam_selesai_2: (item.lembur && item.has_sesi_2) ? (item.jam_selesai_2 || null) : null,
            keterangan_2: (item.lembur && item.has_sesi_2) ? (item.keterangan_2 || null) : null,
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
    <Head title="Buat SPL" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Form Surat Perintah Lembur (SPL) Harian
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Format resmi PT. Letawa. Cukup tandai <span class="font-semibold text-emerald-700">Lembur</span> atau <span class="font-semibold text-slate-600">Tidak Lembur</span> untuk anggota shift.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('dashboard')" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 bg-white shadow-sm hover:bg-slate-50 transition">
                        ← Kembali
                    </a>
                </div>
            </div>
        </template>

        <div class="py-4 md:py-6 pb-24 md:pb-12">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 md:space-y-6">

                <!-- Panel Info Tanggal & Shift Mandor -->
                <div class="bg-white p-4 md:p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Tanggal SPL
                            </label>
                            <input
                                type="date"
                                v-model="tanggal"
                                class="w-full rounded-xl border-slate-300 shadow-sm text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600"
                                required
                            />
                        </div>

                        <!-- Shift Kerja -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Shift Kerja
                            </label>
                            <div v-if="userRole === 'mandor'" class="bg-emerald-50 border border-emerald-200 px-3.5 py-2.5 rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-950">
                                    Shift {{ userShift || selectedShift }} (Regu Anda)
                                </span>
                                <span class="text-[10px] bg-emerald-700 text-white px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">
                                    Mandor {{ (userShift || selectedShift) === 1 ? 'A' : 'B' }}
                                </span>
                            </div>
                            <div v-else class="flex gap-1.5 bg-slate-100 p-1 rounded-xl">
                                <button type="button" @click="selectedShift = 1"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-lg transition text-center"
                                    :class="selectedShift === 1 ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200'">
                                    Shift 1
                                </button>
                                <button type="button" @click="selectedShift = 2"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-lg transition text-center"
                                    :class="selectedShift === 2 ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200'">
                                    Shift 2
                                </button>
                                <button type="button" @click="selectedShift = 'all'"
                                    class="flex-1 py-1.5 text-xs font-bold rounded-lg transition text-center"
                                    :class="selectedShift === 'all' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200'">
                                    Semua
                                </button>
                            </div>
                        </div>

                        <!-- Bulk Preset Jam & Pekerjaan (Desktop & Mobile) -->
                        <div class="md:col-span-2 bg-slate-50 border border-slate-200 p-3.5 rounded-2xl space-y-2.5">
                            <!-- Header Preset & Tombol Terapkan -->
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-emerald-700 font-extrabold text-sm">⚡</span>
                                    <span class="text-xs font-extrabold text-slate-800">Form Isi Cepat (Preset Kolektif):</span>
                                </div>
                                <button type="button" @click="terapkanPreset"
                                    class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold px-3.5 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1.5">
                                    <span>⚡</span> Terapkan ke Semua Lembur
                                </button>
                            </div>

                            <!-- Pilihan Shortcut Cepat -->
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-[11px]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider shrink-0">Pilihan Cepat:</span>
                                <button
                                    v-for="sc in presetShortcuts"
                                    :key="sc.nama"
                                    type="button"
                                    @click="applyShortcut(sc)"
                                    class="px-2.5 py-1 rounded-lg font-bold border transition whitespace-nowrap text-[10.5px]"
                                    :class="presetJamMulai === sc.s1_start && presetJamSelesai === sc.s1_end && presetAktifSesi2 === sc.s2_active
                                        ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm'
                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'"
                                >
                                    {{ sc.nama }}
                                </button>
                            </div>

                            <!-- Input Sesi 1 -->
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/80">
                                <div class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                    <span>🔹 Sesi 1 (Wajib Jika Lembur)</span>
                                    <span class="text-emerald-700 font-bold font-mono">{{ hitungJam(presetJamMulai, presetJamSelesai) }} Jam</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jam Mulai Sesi 1</label>
                                        <input type="time" v-model="presetJamMulai" class="w-full text-xs font-bold rounded-lg border-slate-300 py-1.5 focus:border-emerald-600 focus:ring-emerald-600" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jam Selesai Sesi 1</label>
                                        <input type="time" v-model="presetJamSelesai" class="w-full text-xs font-bold rounded-lg border-slate-300 py-1.5 focus:border-emerald-600 focus:ring-emerald-600" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jenis Pekerjaan</label>
                                        <input type="text" v-model="presetPekerjaan" class="w-full text-xs font-medium rounded-lg border-slate-300 py-1.5 focus:border-emerald-600 focus:ring-emerald-600" placeholder="Lanjut Proses" />
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle & Input Sesi 2 -->
                            <div class="bg-blue-50/60 p-2.5 rounded-xl border border-blue-200/80 transition-all">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="presetAktifSesi2"
                                            class="rounded border-blue-300 text-blue-700 focus:ring-blue-600 w-4 h-4 cursor-pointer"
                                        />
                                        <span class="text-xs font-extrabold text-blue-950">
                                            🌙 Aktifkan Sesi 2 Kolektif (Lembur Lanjutan / Malam)
                                        </span>
                                    </label>
                                    <span v-if="presetAktifSesi2" class="text-[10px] font-extrabold text-blue-800 bg-blue-100 px-2 py-0.5 rounded-md border border-blue-300">
                                        + {{ hitungJam(presetJamMulai2, presetJamSelesai2) }} Jam Sesi 2
                                        (Total: {{ Math.round((hitungJam(presetJamMulai, presetJamSelesai) + hitungJam(presetJamMulai2, presetJamSelesai2)) * 10) / 10 }} Jam)
                                    </span>
                                </div>

                                <div v-if="presetAktifSesi2" class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2 pt-2 border-t border-blue-200/60">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-blue-800 mb-0.5">Jam Mulai Sesi 2</label>
                                        <input type="time" v-model="presetJamMulai2" class="w-full text-xs font-bold rounded-lg border-blue-300 py-1.5 focus:border-blue-600 focus:ring-blue-600 bg-white" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-blue-800 mb-0.5">Jam Selesai Sesi 2</label>
                                        <input type="time" v-model="presetJamSelesai2" class="w-full text-xs font-bold rounded-lg border-blue-300 py-1.5 focus:border-blue-600 focus:ring-blue-600 bg-white" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-blue-800 mb-0.5">Pekerjaan Sesi 2 (Opsional)</label>
                                        <input type="text" v-model="presetPekerjaan2" class="w-full text-xs font-medium rounded-lg border-blue-300 py-1.5 focus:border-blue-600 focus:ring-blue-600 bg-white" placeholder="Sama dg Sesi 1 jika kosong" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bar Status & Pencarian Cepat di Smartphone -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <!-- Status Filter Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                        <button
                            type="button"
                            @click="statusFilter = 'all'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                            :class="statusFilter === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            Semua ({{ totalShiftCount }})
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'lembur'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1"
                            :class="statusFilter === 'lembur' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                        >
                            <span>🟢</span> Lembur ({{ countLembur }})
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'tidak_lembur'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1"
                            :class="statusFilter === 'tidak_lembur' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
                        >
                            <span>⚪</span> Tidak ({{ countTidakLembur }})
                        </button>
                    </div>

                    <!-- Search Input & Tambah Pengganti -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400 text-xs">🔍</span>
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari nama karyawan..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-300 focus:border-emerald-600 focus:ring-emerald-600"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs"
                            >
                                ✕
                            </button>
                        </div>

                        <button
                            v-if="userRole === 'mandor'"
                            type="button"
                            @click="showTambahManual = true"
                            class="flex items-center gap-1.5 text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-xl shadow-sm transition whitespace-nowrap"
                        >
                            <span>➕</span> <span class="hidden sm:inline">Tambah</span> Pengganti
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- ========================================== -->
                    <!-- TAMPILAN MOBILE: KARTU RAMAH SENTUHAN (HP) -->
                    <!-- ========================================== -->
                    <div class="block md:hidden space-y-3">
                        <div
                            v-for="(item, idx) in filteredItems"
                            :key="item.user_id"
                            class="bg-white rounded-2xl border p-4 shadow-sm transition-all"
                            :class="{
                                'border-emerald-300 ring-1 ring-emerald-200 bg-emerald-50/20': item.lembur && item.sertakan,
                                'border-amber-200 bg-amber-50/20': !item.lembur && item.sertakan,
                                'border-slate-200 opacity-60 bg-slate-50': !item.sertakan,
                            }"
                        >
                            <!-- Header Kartu: Nama, No ID, Sertakan -->
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-start gap-2.5">
                                    <div
                                        class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0"
                                        :class="item.lembur ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                                    >
                                        {{ idx + 1 }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm leading-snug">
                                            {{ item.name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="font-mono text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                                {{ item.no_employe }}
                                            </span>
                                            <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded">
                                                {{ item.stasiun_nama }}
                                            </span>
                                            <span v-if="item.is_manual" class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">
                                                Pengganti
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-slate-600 shrink-0">
                                    <input
                                        type="checkbox"
                                        v-model="item.sertakan"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                    />
                                    <span class="text-[11px]">Ikut</span>
                                </label>
                            </div>

                            <!-- Tombol Besar Toggle: Lembur vs Tidak Lembur -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <button
                                    type="button"
                                    @click="onToggleLembur(item, true)"
                                    class="py-2.5 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition"
                                    :class="item.lembur
                                        ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20 ring-2 ring-emerald-600'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                >
                                    <span>🟢</span> LEMBUR
                                </button>
                                <button
                                    type="button"
                                    @click="onToggleLembur(item, false)"
                                    class="py-2.5 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition"
                                    :class="!item.lembur
                                        ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20 ring-2 ring-amber-500'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                >
                                    <span>⚪</span> TIDAK LEMBUR
                                </button>
                            </div>

                            <!-- KONDISI A: JIKA LEMBUR -->
                            <div v-if="item.lembur" class="space-y-3 pt-1 border-t border-slate-100">
                                <!-- SESI 1: Jam Mulai & Jam Selesai -->
                                <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="text-[11px] font-extrabold text-slate-700">🔹 Sesi 1 (Jam Lembur):</label>
                                        <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md">
                                            {{ hitungJam(item.jam_mulai, item.jam_selesai) }} Jam
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <input
                                                type="time"
                                                v-model="item.jam_mulai"
                                                class="w-full text-xs font-bold rounded-xl border-slate-300 py-2 focus:border-emerald-600 focus:ring-emerald-600 bg-white"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <input
                                                type="time"
                                                v-model="item.jam_selesai"
                                                class="w-full text-xs font-bold rounded-xl border-slate-300 py-2 focus:border-emerald-600 focus:ring-emerald-600 bg-white"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <!-- Quick Time Chips Sesi 1 -->
                                    <div class="flex gap-1.5 mt-1.5 overflow-x-auto pb-0.5">
                                        <button
                                            v-for="qt in quickTimePresets"
                                            :key="qt.label"
                                            type="button"
                                            @click="setQuickTime(item, qt.start, qt.end)"
                                            class="text-[10px] font-semibold px-2 py-0.5 bg-white text-slate-700 hover:bg-emerald-100 hover:text-emerald-800 rounded-md border border-slate-200 transition whitespace-nowrap"
                                        >
                                            {{ qt.label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- SESI 2 (Jika Ada / Tambah) -->
                                <div v-if="item.has_sesi_2" class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-200 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-extrabold text-blue-950 flex items-center gap-1">
                                            <span>🌙</span> Sesi 2 (Lanjutan / Malam):
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold text-blue-800 bg-blue-100 px-1.5 py-0.5 rounded">
                                                {{ hitungJam(item.jam_mulai_2, item.jam_selesai_2) }} Jam
                                            </span>
                                            <button
                                                type="button"
                                                @click="hapusSesi2(item)"
                                                class="text-[10px] font-bold text-rose-600 hover:text-rose-800 bg-white px-2 py-0.5 rounded border border-rose-200"
                                            >
                                                ✕ Hapus Sesi 2
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-blue-800 mb-0.5">Mulai Sesi 2</label>
                                            <input
                                                type="time"
                                                v-model="item.jam_mulai_2"
                                                class="w-full text-xs font-bold rounded-xl border-blue-300 py-1.5 focus:border-blue-600 focus:ring-blue-600 bg-white"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-blue-800 mb-0.5">Selesai Sesi 2</label>
                                            <input
                                                type="time"
                                                v-model="item.jam_selesai_2"
                                                class="w-full text-xs font-bold rounded-xl border-blue-300 py-1.5 focus:border-blue-600 focus:ring-blue-600 bg-white"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <!-- Quick Time Chips Sesi 2 -->
                                    <div class="flex gap-1.5 overflow-x-auto pb-0.5">
                                        <button
                                            v-for="qt in quickTimePresets2"
                                            :key="qt.label"
                                            type="button"
                                            @click="setQuickTime2(item, qt.start, qt.end)"
                                            class="text-[10px] font-semibold px-2 py-0.5 bg-white text-blue-800 hover:bg-blue-100 rounded-md border border-blue-200 transition whitespace-nowrap"
                                        >
                                            {{ qt.label }}
                                        </button>
                                    </div>
                                    <!-- Total Badge Gabungan -->
                                    <div class="pt-1 border-t border-blue-200/60 flex items-center justify-between text-[11px] font-extrabold text-blue-950">
                                        <span>Total Lembur Hari Ini:</span>
                                        <span class="text-xs bg-emerald-700 text-white px-2.5 py-0.5 rounded-lg shadow-sm">
                                            {{ hitungTotalJamItem(item) }} Jam
                                        </span>
                                    </div>
                                </div>

                                <div v-else>
                                    <button
                                        type="button"
                                        @click="tambahSesi2(item)"
                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold text-blue-700 bg-blue-50/70 hover:bg-blue-100 border border-dashed border-blue-300 transition flex items-center justify-center gap-1.5"
                                    >
                                        <span>🌙</span> Tambah Lembur Sesi 2 (Malam/Lanjutan)
                                    </button>
                                </div>

                                <!-- Jenis Pekerjaan -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Jenis Pekerjaan:</label>
                                    <input
                                        type="text"
                                        v-model="item.keterangan"
                                        class="w-full text-xs rounded-xl border-slate-300 py-2 focus:border-emerald-600 focus:ring-emerald-600 font-medium"
                                        placeholder="Misal: Lanjut Proses, Cleaning..."
                                        required
                                    />
                                    <!-- Quick Chips Pekerjaan -->
                                    <div class="flex gap-1.5 mt-1.5 overflow-x-auto pb-0.5">
                                        <button
                                            v-for="chip in quickPekerjaanChips"
                                            :key="chip"
                                            type="button"
                                            @click="item.keterangan = chip"
                                            class="text-[10px] font-medium px-2 py-0.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-md transition whitespace-nowrap"
                                        >
                                            {{ chip }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- KONDISI B: JIKA TIDAK LEMBUR -->
                            <div v-else class="space-y-2 pt-1 border-t border-slate-100">
                                <div>
                                    <label class="block text-[11px] font-bold text-amber-900 mb-1.5">
                                        Alasan Tidak Lembur (Pilih Cepat atau Ketik):
                                    </label>
                                    <!-- Tombol Pilihan Cepat: Cuti, HK, Sakit, Izin, Libur -->
                                    <div class="flex gap-1.5 flex-wrap mb-2">
                                        <button
                                            v-for="alasan in quickAlasanTidakLembur"
                                            :key="alasan"
                                            type="button"
                                            @click="item.keterangan = alasan"
                                            class="text-xs font-bold px-3 py-1.5 rounded-lg transition"
                                            :class="item.keterangan === alasan
                                                ? 'bg-amber-600 text-white shadow-sm'
                                                : 'bg-amber-100 text-amber-900 hover:bg-amber-200'"
                                        >
                                            {{ alasan }}
                                        </button>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="item.keterangan"
                                        class="w-full text-xs rounded-xl border-amber-300 bg-amber-50/50 py-2 focus:border-amber-600 focus:ring-amber-600"
                                        placeholder="Ketik keterangan alasan..."
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Catatan Tambahan (Opsional) -->
                            <div class="mt-2.5 pt-2 border-t border-slate-100">
                                <input
                                    type="text"
                                    v-model="item.keperluan"
                                    class="w-full text-[11px] rounded-lg border-slate-200 py-1 text-slate-600 placeholder-slate-400 focus:border-emerald-600 focus:ring-emerald-600"
                                    placeholder="Catatan tambahan (opsional)..."
                                />
                            </div>
                        </div>

                        <div v-if="filteredItems.length === 0" class="bg-white rounded-2xl p-8 text-center text-slate-400 text-xs border border-slate-200">
                            Tidak ada karyawan yang cocok dengan pencarian / filter ini.
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAMPILAN DESKTOP: TABEL PROFESIONAL ELEGAN     -->
                    <!-- ============================================== -->
                    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-extrabold text-slate-900">
                                    Anggota Regu Shift {{ selectedShift === 'all' ? '1 & 2' : selectedShift }}
                                </span>
                                <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-0.5 rounded-full font-bold">
                                    {{ filteredItems.length }} Orang
                                </span>
                            </div>
                            <div class="text-xs text-slate-500">
                                Tandai status per karyawan: <strong class="text-emerald-700">Lembur</strong> atau <strong class="text-slate-600">Tidak Lembur</strong>.
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
                                    <tr>
                                        <th class="px-3 py-3 text-center w-10">No</th>
                                        <th class="px-3 py-3 text-left w-24">No Employe</th>
                                        <th class="px-3 py-3 text-left">Nama Karyawan</th>
                                        <th class="px-3 py-3 text-left">Stasiun</th>
                                        <th class="px-3 py-3 text-center w-40">Status</th>
                                        <th class="px-3 py-3 text-left w-56">Jenis Pekerjaan / Keterangan</th>
                                        <th class="px-3 py-3 text-left w-72">Jam Lembur (Sesi 1 & 2)</th>
                                        <th class="px-3 py-3 text-left">Catatan</th>
                                        <th class="px-3 py-3 text-center w-14">Ikut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr
                                        v-for="(item, idx) in filteredItems"
                                        :key="item.user_id"
                                        :class="{
                                            'bg-emerald-50/20': item.lembur && item.sertakan,
                                            'bg-amber-50/30': !item.lembur && item.sertakan,
                                            'opacity-50 bg-slate-50': !item.sertakan,
                                            'ring-1 ring-inset ring-emerald-300': item.is_manual,
                                        }"
                                    >
                                        <!-- No -->
                                        <td class="px-3 py-2.5 text-center text-slate-500 font-semibold">
                                            {{ idx + 1 }}
                                            <span v-if="item.is_manual" class="block text-[9px] text-emerald-700 font-bold leading-none mt-0.5">✦ Baru</span>
                                        </td>

                                        <!-- No Employe -->
                                        <td class="px-3 py-2.5 text-slate-700 font-mono text-[11px] font-semibold">{{ item.no_employe }}</td>

                                        <!-- Nama -->
                                        <td class="px-3 py-2.5 font-bold text-slate-900 whitespace-nowrap">{{ item.name }}</td>

                                        <!-- Stasiun -->
                                        <td class="px-3 py-2.5 text-slate-600 whitespace-nowrap">
                                            <span class="bg-blue-50 text-blue-800 px-2.5 py-1 rounded-md text-[11px] font-semibold border border-blue-100">
                                                {{ item.stasiun_nama }}
                                            </span>
                                        </td>

                                        <!-- Toggle Status: Lembur / Tidak Lembur -->
                                        <td class="px-3 py-2.5">
                                            <div class="flex gap-1 bg-slate-100 p-0.5 rounded-xl border border-slate-200">
                                                <button
                                                    type="button"
                                                    @click="onToggleLembur(item, true)"
                                                    class="flex-1 py-1 text-[11px] font-bold rounded-lg transition text-center"
                                                    :class="item.lembur ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-200'"
                                                >
                                                    🟢 Lembur
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="onToggleLembur(item, false)"
                                                    class="flex-1 py-1 text-[11px] font-bold rounded-lg transition text-center"
                                                    :class="!item.lembur ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-200'"
                                                >
                                                    ⚪ Tidak
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Jenis Pekerjaan / Keterangan -->
                                        <td class="px-3 py-2.5">
                                            <input
                                                type="text"
                                                v-model="item.keterangan"
                                                class="w-full text-xs rounded-lg border-slate-300 py-1.5 focus:border-emerald-600 focus:ring-emerald-600"
                                                :class="!item.lembur ? 'bg-amber-50 border-amber-300 focus:border-amber-600 focus:ring-amber-500' : ''"
                                                :placeholder="item.lembur ? 'Lanjut proses...' : 'Cuti / HK / Izin / Sakit...'"
                                                required
                                            />
                                        </td>

                                        <!-- Jam Lembur -->
                                        <td class="px-3 py-2.5">
                                            <div v-if="item.lembur" class="space-y-1.5">
                                                <!-- Sesi 1 -->
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] font-bold text-slate-500 w-11 shrink-0">Sesi 1:</span>
                                                    <input type="time" v-model="item.jam_mulai" class="w-20 text-xs font-semibold rounded-lg border-slate-300 py-1 focus:border-emerald-600 focus:ring-emerald-600" required />
                                                    <span class="text-slate-400 font-bold">-</span>
                                                    <input type="time" v-model="item.jam_selesai" class="w-20 text-xs font-semibold rounded-lg border-slate-300 py-1 focus:border-emerald-600 focus:ring-emerald-600" required />
                                                    <span class="text-[10px] text-emerald-800 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0">
                                                        {{ hitungJam(item.jam_mulai, item.jam_selesai) }}j
                                                    </span>
                                                </div>

                                                <!-- Sesi 2 (Jika Ada) -->
                                                <div v-if="item.has_sesi_2" class="flex items-center gap-1.5 bg-blue-50/70 p-1 rounded-lg border border-blue-200">
                                                    <span class="text-[10px] font-bold text-blue-800 w-11 shrink-0">Sesi 2:</span>
                                                    <input type="time" v-model="item.jam_mulai_2" class="w-20 text-xs font-semibold rounded-lg border-blue-300 py-1 focus:border-blue-600 focus:ring-blue-600 bg-white" required />
                                                    <span class="text-slate-400 font-bold">-</span>
                                                    <input type="time" v-model="item.jam_selesai_2" class="w-20 text-xs font-semibold rounded-lg border-blue-300 py-1 focus:border-blue-600 focus:ring-blue-600 bg-white" required />
                                                    <span class="text-[10px] text-blue-800 font-bold bg-blue-100 px-1.5 py-0.5 rounded shrink-0">
                                                        {{ hitungJam(item.jam_mulai_2, item.jam_selesai_2) }}j
                                                    </span>
                                                    <button type="button" @click="hapusSesi2(item)" title="Hapus Sesi 2" class="text-rose-500 hover:text-rose-700 font-bold text-xs px-1">
                                                        ✕
                                                    </button>
                                                </div>

                                                <!-- Tombol Tambah Sesi 2 & Total Jam Badge -->
                                                <div class="flex items-center justify-between pt-0.5">
                                                    <button
                                                        v-if="!item.has_sesi_2"
                                                        type="button"
                                                        @click="tambahSesi2(item)"
                                                        class="text-[10px] font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-2 py-0.5 rounded-md transition"
                                                    >
                                                        ➕ Tambah Sesi 2
                                                    </button>
                                                    <div v-if="item.has_sesi_2" class="text-[10px] font-black text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-300">
                                                        Total: {{ hitungTotalJamItem(item) }} Jam
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-slate-400 italic text-center py-1 text-[11px]">
                                                — Tidak Lembur —
                                            </div>
                                        </td>

                                        <!-- Catatan Tambahan -->
                                        <td class="px-3 py-2.5">
                                            <input
                                                type="text"
                                                v-model="item.keperluan"
                                                class="w-full text-xs rounded-lg border-slate-300 py-1.5 focus:border-emerald-600 focus:ring-emerald-600"
                                                placeholder="Opsional..."
                                            />
                                        </td>

                                        <!-- Sertakan -->
                                        <td class="px-3 py-2.5 text-center">
                                            <input
                                                type="checkbox"
                                                v-model="item.sertakan"
                                                class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Action Bar Desktop -->
                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-xs text-slate-500">
                                Total: <strong class="text-emerald-700">{{ countLembur }} Lembur</strong> · <strong class="text-slate-600">{{ countTidakLembur }} Tidak Lembur</strong>
                            </span>
                            <div class="flex items-center gap-3">
                                <a :href="route('dashboard')"
                                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition">
                                    Batal
                                </a>
                                <button type="submit"
                                    class="bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md transition flex items-center gap-2"
                                    :disabled="isSubmitting">
                                    <span v-if="isSubmitting">Menyimpan...</span>
                                    <span v-else>📋 Terbitkan SPL Hari Ini</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- FLOATING STICKY ACTION BAR UNTUK SMARTPHONE    -->
                    <!-- ============================================== -->
                    <div class="block md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 shadow-2xl">
                        <div class="max-w-md mx-auto flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-bold text-slate-800">
                                    <span class="text-emerald-700">{{ countLembur }} Lembur</span> · <span class="text-slate-600">{{ countTidakLembur }} Tidak</span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Shift {{ selectedShift }} ({{ shiftFilteredItems.length }} Karyawan)
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-emerald-700/20 transition flex items-center gap-2"
                                :disabled="isSubmitting"
                            >
                                <span v-if="isSubmitting">Menyimpan...</span>
                                <span v-else>📋 Terbitkan SPL</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <!-- Modal Tambah Karyawan Pengganti Manual -->
        <div v-if="showTambahManual" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">➕ Tambah Karyawan Pengganti</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Untuk karyawan baru atau pengganti sementara yang belum terdaftar.</p>
                    </div>
                    <button @click="showTambahManual = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
                </div>

                <div class="space-y-3.5 text-sm">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            v-model="namaManual"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                            placeholder="Nama karyawan pengganti..."
                            @keyup.enter="tambahKaryawanManual"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">No. Employe</label>
                            <input
                                type="text"
                                v-model="noEmployeManual"
                                class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600"
                                placeholder="Opsional..."
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Stasiun Pabrik</label>
                            <select v-model="stasiunManual" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="">— Pilih Stasiun —</option>
                                <option v-for="s in stasions" :key="s.id" :value="s.id">{{ s.nama }}</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3 leading-relaxed">
                        ⚠️ Data ini dicatat di SPL hari ini. Untuk akun permanen, minta Admin mendaftarkan di menu Kelola Karyawan.
                    </p>
                </div>

                <div class="flex justify-end gap-2.5 mt-5 pt-3 border-t border-slate-100">
                    <button type="button" @click="showTambahManual = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="button" @click="tambahKaryawanManual" :disabled="!namaManual.trim()"
                        class="px-5 py-2 text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                        ✓ Tambahkan ke Daftar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>