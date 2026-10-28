<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { confirmDialog } from '@/utils/swal';
import { runTourIfRequested } from '@/utils/tour';

const props = defineProps({
    karyawanList: Array,
    stasions: Array,
    mandors: Array,
});

onMounted(() => {
    runTourIfRequested('akun', 'admin');
});

// Tab Aktif: 1 (Shift 1), 2 (Shift 2), 'none' (Belum ada shift), 'all' (Semua)
const activeTab = ref(1);
const searchQuery = ref('');

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    no_employe: '',
    role: 'karyawan',
    stasiun_id: '',
    shift: 1,
    mandor_id: '',
    gaji_pokok: '',
    status_pernikahan: 'TK',
    jatah_beras_kg: 15,
    harga_beras_per_kg: 14000,
    tarif_lembur_per_jam: '',
});

// Pilihan status pernikahan & jatah beras standar PT Letawa / perkebunan sawit
const statusPernikahanOptions = [
    { value: 'TK', label: 'TK - Tidak Kawin / Sendiri (15 Kg)', kg: 15 },
    { value: 'K/0', label: 'K/0 - Kawin tanpa anak (24 Kg: 15kg + 9kg)', kg: 24 },
    { value: 'K/1', label: 'K/1 - Kawin + 1 anak (31.5 Kg: 15kg + 9kg + 7.5kg)', kg: 31.5 },
    { value: 'K/2', label: 'K/2 - Kawin + 2 anak (39 Kg: 15kg + 9kg + 15kg)', kg: 39 },
    { value: 'K/3', label: 'K/3 - Kawin + 3 anak (46.5 Kg: 15kg + 9kg + 22.5kg)', kg: 46.5 },
    { value: 'custom', label: 'Kustom - Input Kg Beras Bebas', kg: null },
];

function onStatusPernikahanChange() {
    const matched = statusPernikahanOptions.find(o => o.value === form.status_pernikahan);
    if (matched && matched.kg !== null) {
        form.jatah_beras_kg = matched.kg;
    }
}

const computedTunjanganBeras = computed(() => {
    const kg = Number(form.jatah_beras_kg) || 0;
    const harga = Number(form.harga_beras_per_kg) || 0;
    return Math.round(kg * harga);
});

const computedTotalDasarUpah = computed(() => {
    const gaji = Number(form.gaji_pokok) || 0;
    return gaji + computedTunjanganBeras.value;
});

const computedUpahSejam = computed(() => {
    if (form.tarif_lembur_per_jam && Number(form.tarif_lembur_per_jam) > 0) {
        return Number(form.tarif_lembur_per_jam);
    }
    return computedTotalDasarUpah.value > 0 ? Math.round(computedTotalDasarUpah.value / 173) : 0;
});

const signerRoles = ['asisten', 'askep', 'ktu', 'administratur', 'hrga'];
const isSignerRole = computed(() => signerRoles.includes(form.role));

// Hitung jumlah karyawan per shift & penandatangan
const countShift1 = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && k.shift == 1).length);
const countShift2 = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && k.shift == 2).length);
const countNoShift = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && !k.shift).length);
const countSigner = computed(() => props.karyawanList.filter(k => signerRoles.includes(k.role)).length);
const countAll = computed(() => props.karyawanList.length);

// Filter karyawan berdasarkan tab & pencarian
const filteredList = computed(() => {
    let list = props.karyawanList;

    if (activeTab.value === 1) {
        list = list.filter(k => k.role === 'karyawan' && k.shift == 1);
    } else if (activeTab.value === 2) {
        list = list.filter(k => k.role === 'karyawan' && k.shift == 2);
    } else if (activeTab.value === 'none') {
        list = list.filter(k => k.role === 'karyawan' && !k.shift);
    } else if (activeTab.value === 'signer') {
        list = list.filter(k => signerRoles.includes(k.role));
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(k =>
            k.name.toLowerCase().includes(q) ||
            (k.no_employe && k.no_employe.toLowerCase().includes(q)) ||
            (k.stasiun?.nama && k.stasiun.nama.toLowerCase().includes(q)) ||
            (k.role && k.role.toLowerCase().includes(q))
        );
    }

    return list;
});

// Ubah shift karyawan secara langsung dari tabel
function ubahShiftLangsung(karyawan, targetShift) {
    const shiftVal = targetShift ? Number(targetShift) : null;
    router.patch(route('admin.akun.update', karyawan.id), {
        shift: shiftVal,
    }, {
        preserveScroll: true,
    });
}

// Buka Modal Tambah Karyawan (otomatis set shift / role sesuai tab aktif)
function openCreate() {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.status_pernikahan = 'TK';
    form.jatah_beras_kg = 15;
    form.harga_beras_per_kg = 14000;
    form.clearErrors();

    // Set default shift / role sesuai tab yang sedang dibuka
    if (activeTab.value === 1 || activeTab.value === 2) {
        form.role = 'karyawan';
        form.shift = activeTab.value;
        const matchedMandor = props.mandors.find(m => m.shift == activeTab.value);
        if (matchedMandor) {
            form.mandor_id = matchedMandor.id;
        }
    } else if (activeTab.value === 'signer') {
        form.role = 'asisten';
        form.shift = '';
        form.mandor_id = '';
        form.stasiun_id = '';
        form.gaji_pokok = '';
        form.jatah_beras_kg = 0;
        form.harga_beras_per_kg = 0;
        form.tarif_lembur_per_jam = '';
    } else {
        form.role = 'karyawan';
    }

    showModal.value = true;
}

// Buka Modal Edit Karyawan
function openEdit(karyawan) {
    editMode.value = true;
    editId.value = karyawan.id;
    form.name = karyawan.name;
    form.email = karyawan.email;
    form.password = '';
    form.no_employe = karyawan.no_employe || '';
    form.role = karyawan.role;
    form.stasiun_id = karyawan.stasiun_id || '';
    form.shift = karyawan.shift || '';
    form.mandor_id = karyawan.mandor_id || '';
    form.gaji_pokok = karyawan.gaji_pokok || '';
    form.status_pernikahan = karyawan.status_pernikahan || 'TK';
    form.jatah_beras_kg = (karyawan.jatah_beras_kg !== null && karyawan.jatah_beras_kg !== undefined) ? karyawan.jatah_beras_kg : (signerRoles.includes(karyawan.role) ? 0 : 15);
    form.harga_beras_per_kg = (karyawan.harga_beras_per_kg !== null && karyawan.harga_beras_per_kg !== undefined) ? karyawan.harga_beras_per_kg : (signerRoles.includes(karyawan.role) ? 0 : 14000);
    form.tarif_lembur_per_jam = karyawan.tarif_lembur_per_jam || '';
    form.clearErrors();
    showModal.value = true;
}

// Saat shift di modal diubah, otomatis pasangkan mandornya
function onModalShiftChange() {
    if (form.shift) {
        const matchedMandor = props.mandors.find(m => m.shift == form.shift);
        if (matchedMandor) {
            form.mandor_id = matchedMandor.id;
        }
    }
}

function submit() {
    if (isSignerRole.value) {
        form.stasiun_id = '';
        form.shift = '';
        form.mandor_id = '';
        form.gaji_pokok = 0;
        form.status_pernikahan = 'TK';
        form.jatah_beras_kg = 0;
        form.harga_beras_per_kg = 0;
        form.tarif_lembur_per_jam = 0;
    }

    if (editMode.value) {
        form.patch(route('admin.akun.update', editId.value), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.akun.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

async function hapus(id) {
    const ok = await confirmDialog({
        title: 'Hapus user ini?',
        text: 'Akun beserta aksesnya akan dihapus permanen.',
        confirmText: 'Ya, hapus',
        danger: true,
    });
    if (ok) router.delete(route('admin.akun.destroy', id));
}

function roleBadgeClass(role) {
    return {
        'bg-purple-100 text-purple-700': role === 'admin',
        'bg-blue-100 text-blue-700': role === 'mandor',
        'bg-green-100 text-green-700': role === 'karyawan',
        'bg-sky-100 text-sky-700': role === 'asisten',
        'bg-indigo-100 text-indigo-700': role === 'askep',
        'bg-violet-100 text-violet-700': role === 'ktu',
        'bg-emerald-100 text-emerald-700': role === 'administratur',
        'bg-rose-100 text-rose-700': role === 'hrga',
    };
}
</script>

<template>
    <Head title="Kelola Akun" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Kelola Akun & Pembagian Shift
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pisahkan dan atur karyawan per Shift 1 (Mandor A) atau Shift 2 (Mandor B) secara langsung.
                    </p>
                </div>
                <button
                    id="tour-akun-tambah"
                    @click="openCreate"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-xs font-bold transition shadow flex items-center gap-1.5 self-start sm:self-auto"
                >
                    <span>+</span> Tambah Akun
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

                <!-- Navigation Tabs Shift -->
                <div id="tour-akun-tabs" class="flex flex-wrap items-center justify-between gap-3 bg-white p-2 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex flex-wrap gap-1.5">
                        <!-- Tab Shift 1 -->
                        <button
                            type="button"
                            @click="activeTab = 1"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2"
                            :class="activeTab === 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <span>🔵 Shift 1 (Mandor A)</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                :class="activeTab === 1 ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ countShift1 }}
                            </span>
                        </button>

                        <!-- Tab Shift 2 -->
                        <button
                            type="button"
                            @click="activeTab = 2"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2"
                            :class="activeTab === 2 ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <span>🟢 Shift 2 (Mandor B)</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                :class="activeTab === 2 ? 'bg-emerald-700 text-white' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ countShift2 }}
                            </span>
                        </button>

                        <!-- Tab Belum Ada Shift -->
                        <button
                            type="button"
                            @click="activeTab = 'none'"
                            class="px-3 py-2 rounded-lg text-xs font-medium transition flex items-center gap-1.5"
                            :class="activeTab === 'none' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <span>⚪ Belum Ada Shift</span>
                            <span
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="activeTab === 'none' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ countNoShift }}
                            </span>
                        </button>

                        <!-- Tab Penandatangan Dokumen -->
                        <button
                            type="button"
                            @click="activeTab = 'signer'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                            :class="activeTab === 'signer' ? 'bg-sky-700 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <span>✍️ Penandatangan Dokumen</span>
                            <span
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="activeTab === 'signer' ? 'bg-sky-800 text-white' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ countSigner }}
                            </span>
                        </button>

                        <!-- Tab Semua Karyawan -->
                        <button
                            type="button"
                            @click="activeTab = 'all'"
                            class="px-3 py-2 rounded-lg text-xs font-medium transition flex items-center gap-1.5"
                            :class="activeTab === 'all' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <span>📋 Semua User</span>
                            <span
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="activeTab === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ countAll }}
                            </span>
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="w-full sm:w-64">
                        <input
                            id="tour-akun-search"
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama, No. Employe, stasiun..."
                            class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5"
                        />
                    </div>
                </div>

                <!-- Info Banner Shift Aktif -->
                <div
                    v-if="activeTab === 1 || activeTab === 2"
                    class="p-3 rounded-lg border text-xs flex items-center justify-between"
                    :class="activeTab === 1 ? 'bg-blue-50/70 border-blue-200 text-blue-900' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900'"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ activeTab === 1 ? '🔵' : '🟢' }}</span>
                        <span>
                            Menampilkan anggota <strong>Shift {{ activeTab }}</strong> (Di bawah tanggung jawab <strong>Mandor {{ activeTab === 1 ? 'A' : 'B' }}</strong>).
                            Untuk memindahkan karyawan ke shift lain, cukup ubah pilihan di kolom <strong>Shift</strong>.
                        </span>
                    </div>
                </div>

                <!-- Info Banner Penandatangan Dokumen -->
                <div
                    v-if="activeTab === 'signer'"
                    class="p-3 rounded-lg border text-xs flex items-center justify-between bg-sky-50/80 border-sky-200 text-sky-950"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">✍️</span>
                        <span>
                            Menampilkan seluruh akun <strong>Penandatangan Dokumen</strong> (Asisten, Askep, KTU, Administratur, HRGA). Akun ini khusus digunakan untuk verifikasi dan menandatangani dokumen SPL & LRLK secara digital.
                        </span>
                    </div>
                </div>

                <!-- Tabel Data Karyawan -->
                <div id="tour-akun-tabel" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-100 font-semibold text-gray-600 uppercase">
                                <tr>
                                    <th class="px-3 py-3 text-center w-10">No</th>
                                    <th class="px-3 py-3 text-left w-24">No. Employe</th>
                                    <th class="px-3 py-3 text-left">Nama</th>
                                    <th class="px-3 py-3 text-left">Email</th>
                                    <th class="px-3 py-3 text-left">Stasiun Pabrik</th>
                                    <th class="px-3 py-3 text-left w-36">Upah Lembur</th>
                                    <th class="px-3 py-3 text-left w-44">Pilih Shift (Langsung)</th>
                                    <th class="px-3 py-3 text-left">Mandor</th>
                                    <th class="px-3 py-3 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="(k, index) in filteredList" :key="k.id" class="hover:bg-gray-50/80 transition">
                                    <td class="px-3 py-2.5 text-center text-gray-500 font-medium">{{ index + 1 }}</td>
                                    <td class="px-3 py-2.5 font-mono text-gray-700 font-semibold">{{ k.no_employe || '-' }}</td>
                                    <td class="px-3 py-2.5 font-bold text-gray-900">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ k.name }}</span>
                                            <span v-if="k.role !== 'karyawan'" class="text-[10px] px-1.5 py-0.2 rounded font-semibold" :class="roleBadgeClass(k.role)">
                                                {{ k.role }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-600 font-mono text-[11px]">{{ k.email }}</td>
                                    <td class="px-3 py-2.5">
                                        <span v-if="signerRoles.includes(k.role)" class="text-gray-400 font-mono text-[11px]">—</span>
                                        <span v-else-if="k.stasiun" class="bg-blue-50 text-blue-800 border border-blue-200 px-2 py-0.5 rounded text-[11px] font-medium">
                                            {{ k.stasiun.nama }}
                                        </span>
                                        <span v-else class="text-gray-400 italic">- Belum diatur -</span>
                                    </td>

                                    <!-- Upah & Tarif Lembur -->
                                    <td class="px-3 py-2.5">
                                        <span v-if="signerRoles.includes(k.role)" class="text-gray-400 font-mono text-[11px]">—</span>
                                        <div v-else-if="k.gaji_pokok || k.tarif_lembur_per_jam">
                                            <div class="font-bold text-slate-900 text-[11px]">
                                                Rp {{ Math.round(k.upah_sejam || 0).toLocaleString('id-ID') }}/jam
                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                Gaji: Rp {{ Number(k.gaji_pokok || 0).toLocaleString('id-ID') }}
                                            </div>
                                            <div class="text-[9px] text-emerald-700 font-medium">
                                                Beras: {{ k.status_pernikahan || 'TK' }} ({{ k.jatah_beras_kg || 15 }}kg)
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-400 italic text-[11px]">- Belum diset -</span>
                                    </td>

                                    <!-- Pilih Shift Langsung Dari Tabel -->
                                    <td class="px-3 py-2.5">
                                        <div v-if="k.role === 'karyawan'" class="flex items-center gap-1.5">
                                            <select
                                                :value="k.shift || ''"
                                                @change="ubahShiftLangsung(k, $event.target.value)"
                                                class="w-full text-xs font-semibold rounded-lg border-gray-300 py-1 transition focus:ring-blue-500"
                                                :class="{
                                                    'bg-blue-50 text-blue-800 border-blue-300': k.shift == 1,
                                                    'bg-emerald-50 text-emerald-800 border-emerald-300': k.shift == 2,
                                                    'bg-gray-100 text-gray-500 border-gray-300': !k.shift,
                                                }"
                                            >
                                                <option value="">— Belum Ada Shift —</option>
                                                <option value="1">🔵 Shift 1 (Mandor A)</option>
                                                <option value="2">🟢 Shift 2 (Mandor B)</option>
                                            </select>
                                        </div>
                                        <div v-else class="text-gray-400 font-mono text-center">
                                            —
                                        </div>
                                    </td>

                                    <!-- Mandor -->
                                    <td class="px-3 py-2.5 text-gray-700">
                                        <span v-if="signerRoles.includes(k.role)" class="text-gray-400 font-mono text-[11px]">—</span>
                                        <span v-else-if="k.mandor_user" class="font-medium text-gray-800">
                                            {{ k.mandor_user.name }}
                                        </span>
                                        <span v-else class="text-gray-400 italic">-</span>
                                    </td>

                                    <!-- Tombol Aksi -->
                                    <td class="px-3 py-2.5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="openEdit(k)"
                                                class="px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold transition"
                                            >
                                                ✏️ Edit
                                            </button>
                                            <button
                                                @click="hapus(k.id)"
                                                class="px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 text-xs font-semibold transition"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredList.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Tidak ada karyawan ditemukan di kategori ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Tambah / Edit Karyawan -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4 pb-2 border-b">
                    <h3 class="text-base font-bold text-gray-900">
                        {{ editMode ? 'Edit Data User' : (isSignerRole ? 'Tambah Akun Penandatangan Dokumen' : 'Tambah Karyawan Baru') }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                            placeholder="Nama lengkap..."
                            required
                        >
                        <p v-if="form.errors.name" class="text-red-500 text-[11px] mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">No. Employe</label>
                            <input
                                type="text"
                                v-model="form.no_employe"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-mono"
                                placeholder="Misal: 214771"
                            >
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Role Akun</label>
                            <select
                                v-model="form.role"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-medium"
                            >
                                <optgroup label="Operator Lapangan">
                                    <option value="karyawan">👷 Karyawan</option>
                                    <option value="mandor">🔧 Mandor</option>
                                    <option value="admin">🛡️ Admin</option>
                                </optgroup>
                                <optgroup label="Penandatangan Dokumen">
                                    <option value="asisten">📋 Asisten</option>
                                    <option value="askep">📌 Asisten Kepala (Askep)</option>
                                    <option value="ktu">🗂️ KTU</option>
                                    <option value="administratur">🏛️ Administratur</option>
                                    <option value="hrga">👔 Staff HRGA</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Email Login</label>
                        <input
                            type="email"
                            v-model="form.email"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                            placeholder="email@letawa.com"
                            required
                        >
                        <p v-if="form.errors.email" class="text-red-500 text-[11px] mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Password {{ editMode ? '(kosongkan jika tidak ingin diubah)' : '' }}
                        </label>
                        <input
                            type="password"
                            v-model="form.password"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                            placeholder="Password login..."
                            :required="!editMode"
                        >
                        <p v-if="form.errors.password" class="text-red-500 text-[11px] mt-1">{{ form.errors.password }}</p>
                    </div>


                    <div v-if="!isSignerRole">
                        <label class="block font-medium text-gray-700 mb-1">Stasiun Pabrik</label>
                        <select
                            v-model="form.stasiun_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                        >
                            <option value="">— Pilih Stasiun —</option>
                            <option v-for="s in stasions" :key="s.id" :value="s.id">{{ s.nama }}</option>
                        </select>
                    </div>

                    <!-- Pembagian Shift & Mandor -->
                    <div v-if="!isSignerRole" class="bg-gray-50 border p-3 rounded-lg space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-800 mb-1">Shift Kerja</label>
                                <select
                                    v-model="form.shift"
                                    @change="onModalShiftChange"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold"
                                >
                                    <option value="">— Belum Ada Shift —</option>
                                    <option value="1">🔵 Shift 1 (Mandor A)</option>
                                    <option value="2">🟢 Shift 2 (Mandor B)</option>
                                </select>
                            </div>

                            <div v-if="form.role === 'karyawan'">
                                <label class="block font-bold text-gray-800 mb-1">Mandor Penanggung Jawab</label>
                                <select
                                    v-model="form.mandor_id"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                                >
                                    <option value="">— Pilih Mandor —</option>
                                    <option v-for="m in mandors" :key="m.id" :value="m.id">
                                        {{ m.name }} (Shift {{ m.shift || '-' }})
                                    </option>
                                </select>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 italic">
                            * Memilih Shift 1 akan otomatis menetapkan Mandor A, dan Shift 2 akan otomatis menetapkan Mandor B.
                        </p>
                    </div>

                    <!-- Variabel Upah Lembur Karyawan (PT Letawa & PP 35/2021) -->
                    <div v-if="!isSignerRole" class="bg-emerald-50/70 border border-emerald-200 p-3.5 rounded-xl space-y-3">
                        <div class="font-bold text-emerald-950 text-xs flex items-center justify-between">
                            <span class="flex items-center gap-1.5">🌾 Komponen Upah & Catu Beras (PT Letawa)</span>
                            <span class="text-[10px] bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded font-bold">PP 35/2021</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Gaji Pokok / Sebulan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.gaji_pokok"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                placeholder="Contoh: 3500000"
                                min="0"
                            />
                        </div>

                        <!-- Status Pernikahan & Catu Beras Standar PT Letawa -->
                        <div class="bg-white p-3 rounded-lg border border-emerald-100 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-emerald-900">Jatah Catu Beras Karyawan</span>
                                <span class="text-[10px] text-emerald-600 italic">* Standar perkebunan kelapa sawit</span>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Status Pernikahan / Tanggungan</label>
                                <select
                                    v-model="form.status_pernikahan"
                                    @change="onStatusPernikahanChange"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                >
                                    <option v-for="opt in statusPernikahanOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-slate-700 mb-1">Jatah Beras (Kg)</label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        v-model="form.jatah_beras_kg"
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                        placeholder="15"
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-700 mb-1">Harga Beras per Kg (Rp)</label>
                                    <input
                                        type="number"
                                        v-model="form.harga_beras_per_kg"
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                        placeholder="14000"
                                        min="0"
                                    />
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded flex items-center justify-between">
                                <span>Nilai Tunjangan Catu Beras:</span>
                                <span class="font-bold text-emerald-800">
                                    {{ form.jatah_beras_kg || 0 }} kg × Rp {{ Number(form.harga_beras_per_kg || 0).toLocaleString('id-ID') }} = Rp {{ computedTunjanganBeras.toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tarif Kustom / Jam (Opsional)</label>
                            <input
                                type="number"
                                v-model="form.tarif_lembur_per_jam"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                placeholder="Kosongkan jika pakai rumus: (Gaji Pokok + Beras) / 173"
                                min="0"
                            />
                        </div>

                        <!-- Live Preview Perhitungan Lembur -->
                        <div class="text-[11px] text-emerald-950 bg-white/90 p-3 rounded-xl border border-emerald-200 space-y-1.5">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total Dasar Upah (Gaji + Beras):</span>
                                <span class="font-semibold text-slate-800">Rp {{ computedTotalDasarUpah.toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-emerald-100">
                                <span class="font-bold text-emerald-900">Upah Sejam Lembur (÷ 173):</span>
                                <strong class="text-emerald-800 text-sm">
                                    Rp {{ computedUpahSejam.toLocaleString('id-ID') }} / jam
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 font-bold text-xs shadow transition"
                            :disabled="form.processing"
                        >
                            {{ editMode ? 'Simpan Perubahan' : (isSignerRole ? 'Tambah Akun Penandatangan' : 'Tambah Karyawan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
