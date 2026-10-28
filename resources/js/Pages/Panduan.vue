<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = usePage().props.auth.user;
const role = computed(() => user.role);

const signerRoles = ['asisten', 'askep', 'ktu', 'administratur', 'hrga'];
const isSigner = computed(() => signerRoles.includes(role.value));
</script>

<template>
    <Head title="Panduan Penggunaan" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                Panduan Penggunaan SILEKA
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Panduan interaktif yang disesuaikan khusus untuk akses Anda sebagai <span class="font-bold uppercase">{{ role }}</span>.
            </p>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- PANDUAN ADMIN -->
                <div v-if="role === 'admin'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold text-amber-600 mb-4 border-b pb-2">Fitur Administrator</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">1. Manajemen Master Data</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Gunakan menu <strong>Karyawan</strong> dan <strong>Stasiun</strong> untuk mengelola data induk. Anda wajib mengatur <strong>Gaji Pokok</strong> dan <strong>Tarif Lembur</strong> agar perhitungan *payroll* berjalan otomatis.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">2. Memantau SPL & LRLK Harian</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Anda tidak perlu lagi mengetik ulang kertas lembur ke Excel. Buka menu <strong>Dokumen (Cetak)</strong> untuk melihat rekapitulasi data lembur dari seluruh Mandor yang sudah di-ACC Karyawan secara *real-time*.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">3. Pelacakan Riwayat & Komplain</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Jika ada komplain selisih gaji, gunakan filter tanggal pada menu Dokumen. Anda bisa langsung melihat jam aktual dan alasan revisi tanpa harus membongkar tumpukan kertas.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">4. Cetak Dokumen Resmi</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                SILEKA didesain *paperless*. Namun, jika atasan meminta bukti fisik, klik <strong>Preview SPL / LRLK</strong> untuk mencetak format standar (PROS-HCM-02) yang sudah dilengkapi tanda tangan digital.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PANDUAN MANDOR -->
                <div v-if="role === 'mandor'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold text-blue-600 mb-4 border-b pb-2">Fitur Mandor (Pengawas Shift)</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">1. Buat SPL (Preset Cepat)</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Buka menu <strong>Buat SPL</strong>. Anggota regu Anda akan muncul otomatis. Gunakan menu <strong>Form Isi Cepat (Preset)</strong> di bagian atas untuk mengisi jam mulai, jam selesai, dan tugas serentak dengan 1 klik.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">2. Lembur 2 Sesi (Malam)</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Jika ada anggota regu yang lembur lanjut malam, centang opsi <strong>Aktifkan Sesi 2 Kolektif</strong> pada preset atas, atau klik tombol tambah sesi pada masing-masing kartu karyawan.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">3. Status Tidak Lembur</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Untuk anggota yang Izin, Sakit, Cuti, atau sekadar Hari Kerja normal (HK), klik tombol <strong>TIDAK LEMBUR (⚪)</strong> pada namanya, lalu pilih alasan cepatnya.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">4. Tambah Pengganti</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Jika ada karyawan *shift* lain yang menggantikan anggota Anda hari ini, gunakan tombol <strong>➕ Tambah Pengganti</strong> di samping kotak pencarian sebelum menyimpan SPL.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PANDUAN KARYAWAN -->
                <div v-if="role === 'karyawan'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold text-emerald-600 mb-4 border-b pb-2">Fitur Karyawan (Operator Lapangan)</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">1. Cek Instruksi Lembur</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Buka aplikasi SILEKA dari HP Anda setelah selesai bekerja. Di <strong>Dashboard</strong>, Anda akan melihat instruksi jam lembur dan tugas yang dibuatkan oleh Mandor.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">2. Validasi & Tanda Tangan (ACC)</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Jika jam kerja sudah benar, klik <strong>ACC</strong>. Anda akan diminta mencoret tanda tangan di layar HP. Centang "Simpan Permanen" agar besok tidak perlu coret-coret lagi.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">3. Tolak & Revisi Jam</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Jika jam yang diajukan Mandor berbeda dengan fakta Anda bekerja (misal Anda pulang lebih malam), klik <strong>Tolak</strong> dan ketik alasan revisi agar jam diperbaiki oleh Mandor.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">4. Pantau Riwayat Lembur</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Buka menu <strong>Riwayat</strong> untuk melihat transparansi total akumulasi jam lembur Anda yang telah sah (di-ACC) dalam bulan berjalan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PANDUAN PEJABAT PENANDATANGAN -->
                <div v-if="isSigner" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold text-indigo-600 mb-4 border-b pb-2">Fitur Pejabat Penandatangan</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">1. Pengesahan Dokumen</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Buka menu <strong>Dokumen Lembur</strong>. Anda dapat mereview data lembur per tanggal. Untuk mengesahkan dokumen secara digital, klik tombol <strong>Tanda Tangan (✍️)</strong>.
                            </p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm mb-2">2. Simpan TTD Default</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Saat mencoret tanda tangan, pastikan mencentang "Simpan sebagai tanda tangan default" agar tanda tangan Anda otomatis tertempel pada pencetakan dokumen di hari-hari berikutnya.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>