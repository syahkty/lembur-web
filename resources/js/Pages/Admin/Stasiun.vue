<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { confirmDialog } from '@/utils/swal';
import { runTourIfRequested } from '@/utils/tour';

const props = defineProps({
    stasions: Array,
});

onMounted(() => {
    runTourIfRequested('stasiun', 'admin');
});

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);

const form = useForm({
    nama: '',
    kode: '',
});

function openCreate() {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(stasiun) {
    editMode.value = true;
    editId.value = stasiun.id;
    form.nama = stasiun.nama;
    form.kode = stasiun.kode;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editMode.value) {
        form.patch(route('admin.stasiun.update', editId.value), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.stasiun.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

async function hapus(id) {
    const ok = await confirmDialog({
        title: 'Hapus stasiun ini?',
        text: 'Data stasiun yang dihapus tidak dapat dikembalikan.',
        confirmText: 'Ya, hapus',
        danger: true,
    });
    if (ok) router.delete(route('admin.stasiun.destroy', id));
}
</script>

<template>
    <Head title="Kelola Stasiun" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Stasiun Pabrik</h2>
                <button
                    id="tour-stasiun-tambah"
                    @click="openCreate"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold transition"
                >
                    + Tambah Stasiun
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div id="tour-stasiun-tabel" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Stasiun</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(s, index) in stasions" :key="s.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-mono font-medium text-blue-700">{{ s.kode }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ s.nama }}</td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex gap-3">
                                            <button @click="openEdit(s)" class="text-blue-600 hover:text-blue-900 text-xs font-bold">Edit</button>
                                            <button @click="hapus(s.id)" class="text-red-600 hover:text-red-900 text-xs font-bold">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="stasions.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada data stasiun.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Stasiun -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ editMode ? 'Edit Stasiun' : 'Tambah Stasiun Baru' }}
                </h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Stasiun</label>
                        <input type="text" v-model="form.nama" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Contoh: Sterilizer (Perebusan)" required>
                        <p v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode</label>
                        <input type="text" v-model="form.kode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm uppercase" placeholder="Contoh: STR" maxlength="10" required>
                        <p v-if="form.errors.kode" class="text-red-500 text-xs mt-1">{{ form.errors.kode }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold text-sm transition" :disabled="form.processing">
                            {{ editMode ? 'Simpan' : 'Tambah' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
