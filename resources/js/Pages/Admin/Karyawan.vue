<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    karyawanList: Array,
    stasions: Array,
    mandors: Array,
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
});

// Hitung jumlah karyawan per shift
const countShift1 = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && k.shift == 1).length);
const countShift2 = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && k.shift == 2).length);
const countNoShift = computed(() => props.karyawanList.filter(k => k.role === 'karyawan' && !k.shift).length);
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
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(k =>
            k.name.toLowerCase().includes(q) ||
            (k.no_employe && k.no_employe.toLowerCase().includes(q)) ||
            (k.stasiun?.nama && k.stasiun.nama.toLowerCase().includes(q))
        );
    }

    return list;
});

// Ubah shift karyawan secara langsung dari tabel
function ubahShiftLangsung(karyawan, targetShift) {
    const shiftVal = targetShift ? Number(targetShift) : null;
    router.patch(route('admin.karyawan.update', karyawan.id), {
        shift: shiftVal,
    }, {
        preserveScroll: true,
    });
}

// Buka Modal Tambah Karyawan (otomatis set shift sesuai tab aktif)
function openCreate() {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();

    // Set default shift sesuai tab yang sedang dibuka
    if (activeTab.value === 1 || activeTab.value === 2) {
        form.shift = activeTab.value;
        const matchedMandor = props.mandors.find(m => m.shift == activeTab.value);
        if (matchedMandor) {
            form.mandor_id = matchedMandor.id;
        }
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
    if (editMode.value) {
        form.patch(route('admin.karyawan.update', editId.value), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.karyawan.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

function hapus(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        router.delete(route('admin.karyawan.destroy', id));
    }
}

function roleBadgeClass(role) {
    return {
        'bg-purple-100 text-purple-700': role === 'admin',
        'bg-blue-100 text-blue-700': role === 'mandor',
        'bg-green-100 text-green-700': role === 'karyawan',
    };
}
</script>

<template>
    <Head title="Kelola Karyawan" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Kelola Karyawan & Pembagian Shift
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pisahkan dan atur karyawan per Shift 1 (Mandor A) atau Shift 2 (Mandor B) secara langsung.
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-xs font-bold transition shadow flex items-center gap-1.5 self-start sm:self-auto"
                >
                    <span>+</span> Tambah Karyawan
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

                <!-- Navigation Tabs Shift -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-2 rounded-xl shadow-sm border border-gray-200">
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

                <!-- Tabel Data Karyawan -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-100 font-semibold text-gray-600 uppercase">
                                <tr>
                                    <th class="px-3 py-3 text-center w-10">No</th>
                                    <th class="px-3 py-3 text-left w-24">No. Employe</th>
                                    <th class="px-3 py-3 text-left">Nama</th>
                                    <th class="px-3 py-3 text-left">Email</th>
                                    <th class="px-3 py-3 text-left">Stasiun Pabrik</th>
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
                                            <span v-if="k.role !== 'karyawan'" class="text-[10px] px-1.5 py-0.2 rounded" :class="roleBadgeClass(k.role)">
                                                {{ k.role }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-600 font-mono text-[11px]">{{ k.email }}</td>
                                    <td class="px-3 py-2.5">
                                        <span v-if="k.stasiun" class="bg-blue-50 text-blue-800 border border-blue-200 px-2 py-0.5 rounded text-[11px] font-medium">
                                            {{ k.stasiun.nama }}
                                        </span>
                                        <span v-else class="text-gray-400 italic">- Belum diatur -</span>
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
                                        <div v-else class="text-gray-400 italic">
                                            -
                                        </div>
                                    </td>

                                    <!-- Mandor -->
                                    <td class="px-3 py-2.5 text-gray-700">
                                        <span v-if="k.mandor_user" class="font-medium text-gray-800">
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
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
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
                        {{ editMode ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru' }}
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
                            placeholder="Nama karyawan..."
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
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                            >
                                <option value="karyawan">Karyawan</option>
                                <option value="mandor">Mandor</option>
                                <option value="admin">Admin</option>
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

                    <div>
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
                    <div class="bg-gray-50 border p-3 rounded-lg space-y-3">
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
                            {{ editMode ? 'Simpan Perubahan' : 'Tambah Karyawan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
