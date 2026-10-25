<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    lembur: Object,
    userRole: String,
});

const isRevisi = computed(() => props.lembur.status === 'ditolak');
const isDisetujui = computed(() => props.lembur.status === 'disetujui');

const form = useForm({
    tanggal: props.lembur.tanggal || '',
    kategori: (props.lembur.kategori === 'lembur' || (!props.lembur.kategori && props.lembur.jam_mulai)) ? 'lembur' : 'tidak_lembur',
    jam_mulai: props.lembur.jam_mulai?.slice(0, 5) || '',
    jam_selesai: props.lembur.jam_selesai?.slice(0, 5) || '',
    has_sesi_2: !!(props.lembur.jam_mulai_2 && props.lembur.jam_selesai_2),
    jam_mulai_2: props.lembur.jam_mulai_2?.slice(0, 5) || '',
    jam_selesai_2: props.lembur.jam_selesai_2?.slice(0, 5) || '',
    keterangan: props.lembur.keterangan || '',
    keterangan_2: props.lembur.keterangan_2 || '',
    keperluan: props.lembur.keperluan || '',
});

function hitungJam(mulai, selesai) {
    if (!mulai || !selesai) return 0;
    const [h1, m1] = mulai.split(':').map(Number);
    const [h2, m2] = selesai.split(':').map(Number);
    let diff = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (diff < 0) diff += 24 * 60;
    return Math.round((diff / 60) * 10) / 10;
}

const totalJamFisik = computed(() => {
    if (form.kategori !== 'lembur') return 0;
    const s1 = hitungJam(form.jam_mulai, form.jam_selesai);
    const s2 = form.has_sesi_2 ? hitungJam(form.jam_mulai_2, form.jam_selesai_2) : 0;
    return Math.round((s1 + s2) * 10) / 10;
});

function toggleSesi2() {
    form.has_sesi_2 = !form.has_sesi_2;
    if (form.has_sesi_2) {
        if (!form.jam_mulai_2) form.jam_mulai_2 = '21:00';
        if (!form.jam_selesai_2) form.jam_selesai_2 = '02:00';
    } else {
        form.jam_mulai_2 = '';
        form.jam_selesai_2 = '';
        form.keterangan_2 = '';
    }
}

function onKategoriChange(val) {
    form.kategori = val;
    if (val === 'tidak_lembur') {
        form.jam_mulai = '';
        form.jam_selesai = '';
        form.has_sesi_2 = false;
        form.jam_mulai_2 = '';
        form.jam_selesai_2 = '';
        form.keterangan_2 = '';
        if (form.keterangan === 'Lanjut Proses') {
            form.keterangan = '';
        }
    } else {
        if (!form.keterangan) {
            form.keterangan = 'Lanjut Proses';
        }
        if (!form.jam_mulai) form.jam_mulai = '16:00';
        if (!form.jam_selesai) form.jam_selesai = '19:00';
    }
}

function submit() {
    if (isDisetujui.value) return;
    form.patch(route('lembur.update', props.lembur.id));
}
</script>

<template>
    <Head :title="isRevisi ? 'Revisi SPL' : 'Edit SPL'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        {{ isRevisi ? 'Revisi SPL (Sebelumnya Ditolak)' : 'Edit SPL' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Perbarui rincian lembur karyawan.</p>
                </div>
                <a :href="route('dashboard')" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 bg-white shadow-sm hover:bg-slate-50 transition">
                    ← Kembali
                </a>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-200 p-6 space-y-6">

                    <!-- Peringatan jika sudah disetujui (ACC) -->
                    <div v-if="isDisetujui" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h4 class="font-bold text-xs text-amber-800 flex items-center gap-1.5">
                            <span>🔒</span> Lembur Terkunci (Sudah di-ACC)
                        </h4>
                        <p class="text-xs text-amber-700 mt-1">
                            Lembur ini telah disetujui (ACC) oleh karyawan dan tidak dapat diubah oleh mandor. Jika ada kesalahan data, minta karyawan yang bersangkutan untuk membatalkan ACC terlebih dahulu di dashboard.
                        </p>
                    </div>

                    <!-- Info Karyawan -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-3">Informasi Karyawan</h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <span class="text-slate-500 font-medium">Nama Karyawan:</span>
                            <span class="font-bold text-slate-900">{{ lembur.user?.name }}</span>
                            <span class="text-slate-500 font-medium">No Employe:</span>
                            <span class="font-mono text-slate-700 font-semibold">{{ lembur.user?.no_employe || '-' }}</span>
                            <span class="text-slate-500 font-medium">Stasiun:</span>
                            <span class="font-semibold text-slate-800">{{ lembur.user?.stasiun?.nama || '-' }}</span>
                            <span class="text-slate-500 font-medium">Status Saat Ini:</span>
                            <span class="font-bold" :class="{
                                'text-amber-600': lembur.status === 'pending',
                                'text-emerald-700': lembur.status === 'disetujui',
                                'text-rose-600': lembur.status === 'ditolak',
                            }">{{ (lembur.kategori === 'cuti' ? 'CUTI' : (lembur.kategori === 'hk' ? 'HK' : lembur.status))?.toUpperCase() }}</span>
                        </div>
                    </div>

                    <!-- Alasan Penolakan Jika Ada -->
                    <div v-if="lembur.alasan_revisi" class="bg-rose-50 border border-rose-200 rounded-xl p-4">
                        <h4 class="font-bold text-xs text-rose-800 mb-1 flex items-center gap-1.5">
                            <span>⚠️</span> Catatan Penolakan dari Karyawan:
                        </h4>
                        <p class="text-xs text-rose-700 italic bg-white p-2.5 rounded-lg border border-rose-100 mt-1.5">
                            "{{ lembur.alasan_revisi }}"
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal</label>
                                <input
                                    type="date"
                                    v-model="form.tanggal"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-semibold"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Keikutsertaan</label>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="onKategoriChange('lembur')"
                                        class="flex-1 py-2 px-3 text-xs font-bold rounded-xl border transition text-center"
                                        :class="form.kategori === 'lembur' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border-slate-200'"
                                    >
                                        🟢 Lembur
                                    </button>
                                    <button
                                        type="button"
                                        @click="onKategoriChange('tidak_lembur')"
                                        class="flex-1 py-2 px-3 text-xs font-bold rounded-xl border transition text-center"
                                        :class="form.kategori === 'tidak_lembur' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border-slate-200'"
                                    >
                                        ⚪ Tidak Lembur
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.kategori === 'lembur'" class="space-y-3">
                            <!-- Sesi 1 -->
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-slate-700">🔹 Jam Lembur Sesi 1</label>
                                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md">
                                        {{ hitungJam(form.jam_mulai, form.jam_selesai) }} Jam
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Mulai Sesi 1</label>
                                        <input
                                            type="time"
                                            v-model="form.jam_mulai"
                                            class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-bold bg-white"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Selesai Sesi 1</label>
                                        <input
                                            type="time"
                                            v-model="form.jam_selesai"
                                            class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs font-bold bg-white"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Sesi 2 (Opsional) -->
                            <div v-if="form.has_sesi_2" class="bg-blue-50/70 p-3.5 rounded-xl border border-blue-200 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-blue-950 flex items-center gap-1.5">
                                        <span>🌙</span> Jam Lembur Sesi 2 (Malam / Lanjutan)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-blue-800 bg-blue-100 px-2 py-0.5 rounded-md">
                                            {{ hitungJam(form.jam_mulai_2, form.jam_selesai_2) }} Jam
                                        </span>
                                        <button
                                            type="button"
                                            @click="toggleSesi2"
                                            class="text-[10px] font-bold text-rose-600 hover:text-rose-800 bg-white px-2 py-0.5 rounded-md border border-rose-200"
                                        >
                                            ✕ Hapus Sesi 2
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-blue-800 mb-1">Mulai Sesi 2</label>
                                        <input
                                            type="time"
                                            v-model="form.jam_mulai_2"
                                            class="w-full rounded-xl border-blue-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-xs font-bold bg-white"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-blue-800 mb-1">Selesai Sesi 2</label>
                                        <input
                                            type="time"
                                            v-model="form.jam_selesai_2"
                                            class="w-full rounded-xl border-blue-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-xs font-bold bg-white"
                                            required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-blue-800 mb-1">Pekerjaan Sesi 2 (Opsional jika beda dg Sesi 1)</label>
                                    <input
                                        type="text"
                                        v-model="form.keterangan_2"
                                        class="w-full rounded-xl border-blue-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-xs bg-white"
                                        placeholder="Contoh: Bongkar Muat TBS..."
                                    />
                                </div>
                            </div>

                            <div v-else>
                                <button
                                    type="button"
                                    @click="toggleSesi2"
                                    class="w-full py-2 px-3 rounded-xl text-xs font-bold text-blue-700 bg-blue-50/70 hover:bg-blue-100 border border-dashed border-blue-300 transition flex items-center justify-center gap-1.5"
                                >
                                    <span>🌙</span> Tambah Lembur Sesi 2 (Lembur Malam / Lanjutan)
                                </button>
                            </div>

                            <!-- Total Jam Fisik Akumulasi -->
                            <div class="bg-emerald-50/80 p-2.5 rounded-xl border border-emerald-200 flex items-center justify-between text-xs font-extrabold text-emerald-950">
                                <span>Total Durasi Lembur Nyata:</span>
                                <span class="bg-emerald-700 text-white px-3 py-1 rounded-lg text-xs shadow-sm">
                                    {{ totalJamFisik }} Jam
                                </span>
                            </div>
                        </div>
                        <div v-else class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 leading-relaxed">
                            ℹ️ <strong>Status Tidak Lembur:</strong> Jam lembur otomatis dinonaktifkan (menjadi -). Silakan tuliskan alasan ketidakhadiran lembur pada kolom di bawah.
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                {{ form.kategori === 'lembur' ? 'Jenis Pekerjaan Lembur' : 'Alasan Tidak Lembur' }}
                            </label>
                            <textarea
                                v-model="form.keterangan"
                                rows="2"
                                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs"
                                :placeholder="form.kategori === 'lembur' ? 'Contoh: Lanjut proses, standby genset, dll' : 'Tuliskan alasannya, contoh: Cuti, HK, Sakit, Izin...'"
                                required
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                            <textarea
                                v-model="form.keperluan"
                                rows="2"
                                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 text-xs"
                                placeholder="Opsional (misal: pulang jam 15:00, ganti shift, dll)"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                            <a
                                :href="route('dashboard')"
                                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition"
                            >
                                Batal
                            </a>
                            <button
                                type="submit"
                                class="bg-emerald-700 text-white px-5 py-2 rounded-xl hover:bg-emerald-800 font-bold text-xs shadow-md shadow-emerald-700/20 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="form.processing || isDisetujui"
                            >
                                {{ isDisetujui ? '🔒 Terkunci (Sudah di-ACC)' : (isRevisi ? 'Simpan & Kirim Revisi' : 'Simpan Perubahan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
