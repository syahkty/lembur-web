<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { alertError, showSuccessAlert } from '@/utils/swal';

const props = defineProps({
    show: Boolean,
    lembur: Object,
    userSavedSignature: String,
});

const emit = defineEmits(['close', 'approved']);

const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const useSaved = ref(!!props.userSavedSignature);
const simpanPermanen = ref(true);

// Foto Dokumentasi
const fotoFile = ref(null);
const fotoPreview = ref(null);
const fileInputRef = ref(null);

const isSubmitting = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal) {
        useSaved.value = !!props.userSavedSignature;
        hasDrawn.value = false;
        fotoFile.value = null;
        fotoPreview.value = null;
        nextTick(() => {
            if (!useSaved.value) {
                initCanvas();
            }
        });
    }
});

function initCanvas() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Sesuaikan resolusi internal canvas dengan ukuran tampilannya
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * 2;
    canvas.height = rect.height * 2;
    ctx.scale(2, 2);

    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a'; // Slate-900
    ctx.lineWidth = 2.5;

    // Bersihkan latar belakang menjadi transparan
    ctx.clearRect(0, 0, rect.width, rect.height);
}

function getPos(e) {
    const canvas = canvasRef.value;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
}

function startDrawing(e) {
    if (useSaved.value) return;
    e.preventDefault();
    isDrawing.value = true;
    hasDrawn.value = true;
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
}

function draw(e) {
    if (!isDrawing.value || useSaved.value) return;
    e.preventDefault();
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
}

function stopDrawing(e) {
    if (!isDrawing.value) return;
    if (e) e.preventDefault();
    isDrawing.value = false;
}

function clearCanvas() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);
    hasDrawn.value = false;
}

function switchToNewSignature() {
    useSaved.value = false;
    nextTick(() => {
        initCanvas();
    });
}

// Kompres gambar otomatis di sisi client (browser) agar hemat kuota, upload super cepat (<300KB), dan bebas error 413
function compressImage(file, maxDimension, quality, callback) {
    const reader = new FileReader();
    reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
            let width = img.width;
            let height = img.height;

            if (width > maxDimension || height > maxDimension) {
                if (width > height) {
                    height = Math.round((height * maxDimension) / width);
                    width = maxDimension;
                } else {
                    width = Math.round((width * maxDimension) / height);
                    height = maxDimension;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
            callback(compressedDataUrl);
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Otomatis kompres foto resolusi kamera HP tinggi (10MB+) menjadi ~200KB yang tajam & ringan
    compressImage(file, 1280, 0.8, (compressedDataUrl) => {
        fotoFile.value = file;
        fotoPreview.value = compressedDataUrl;
    });
}

function removeFoto() {
    fotoFile.value = null;
    fotoPreview.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

function submitApproval() {
    let signatureData = null;

    if (useSaved.value && props.userSavedSignature) {
        signatureData = props.userSavedSignature;
    } else {
        if (!hasDrawn.value) {
            alertError('Tanda Tangan Belum Ada', 'Silakan coret tanda tangan Anda pada kotak yang disediakan terlebih dahulu.');
            return;
        }
        const canvas = canvasRef.value;
        signatureData = canvas.toDataURL('image/png');
    }

    isSubmitting.value = true;

    // Kirim data persetujuan ke server
    router.patch(route('lembur.acc', props.lembur.id), {
        tanda_tangan: signatureData,
        simpan_permanen: simpanPermanen.value,
        foto_dokumentasi: fotoPreview.value || null,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            emit('approved');
            emit('close');
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[92vh]">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg">
                        ✍️
                    </span>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 leading-tight">
                            Tanda Tangan Digital & Persetujuan SPL
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ lembur?.tanggal }} · {{ lembur?.jam_mulai }} - {{ lembur?.jam_selesai }} ({{ lembur?.durasi_jam }} Jam)
                        </p>
                    </div>
                </div>
                <button
                    @click="emit('close')"
                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center text-sm font-bold transition"
                >
                    ✕
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs">

                <!-- Ringkasan Instruksi Lembur -->
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-3.5">
                    <div class="font-bold text-emerald-950 text-xs mb-1">
                        Pekerjaan: {{ lembur?.keterangan || 'Lanjut Proses' }}
                    </div>
                    <div class="text-[11px] text-emerald-800 flex items-center justify-between">
                        <span>Mandor: <strong>{{ lembur?.mandor?.name || 'Mandor Shift' }}</strong></span>
                        <span class="font-bold bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-md">
                            {{ lembur?.durasi_jam }} Jam Kerja
                        </span>
                    </div>
                </div>

                <!-- SEKSI 1: TANDA TANGAN DIGITAL -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                            <span>1.</span> Tanda Tangan Anda (ACC):
                        </label>
                        <button
                            v-if="userSavedSignature && !useSaved"
                            type="button"
                            @click="useSaved = true"
                            class="text-[11px] font-bold text-emerald-700 hover:underline"
                        >
                            ← Pakai TTD Tersimpan
                        </button>
                    </div>

                    <!-- Pilihan A: Gunakan Tanda Tangan yang Sudah Tersimpan -->
                    <div v-if="useSaved && userSavedSignature" class="bg-slate-50 border-2 border-emerald-500 rounded-2xl p-4 text-center">
                        <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-200">
                            <span class="text-xs font-bold text-emerald-900 flex items-center gap-1">
                                <span>✓</span> Tanda Tangan Tersimpan Terpilih
                            </span>
                            <button
                                type="button"
                                @click="switchToNewSignature"
                                class="text-[11px] font-bold text-slate-600 hover:text-slate-900 px-2 py-1 rounded bg-slate-200 hover:bg-slate-300 transition"
                            >
                                ✏️ Coret TTD Baru
                            </button>
                        </div>
                        <div class="bg-white rounded-xl p-2 border border-slate-200 flex items-center justify-center h-28">
                            <img :src="userSavedSignature" alt="Tanda Tangan Tersimpan" class="max-h-full object-contain" />
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2">
                            Tanda tangan ini akan otomatis ditempelkan pada SPL Anda.
                        </p>
                    </div>

                    <!-- Pilihan B: Canvas Coret Tangan Baru di HP -->
                    <div v-else>
                        <div class="border-2 border-dashed border-slate-300 rounded-2xl p-2 bg-slate-50/50 relative">
                            <canvas
                                ref="canvasRef"
                                @mousedown="startDrawing"
                                @mousemove="draw"
                                @mouseup="stopDrawing"
                                @mouseleave="stopDrawing"
                                @touchstart="startDrawing"
                                @touchmove="draw"
                                @touchend="stopDrawing"
                                class="w-full h-36 bg-white rounded-xl shadow-inner cursor-crosshair touch-none"
                            ></canvas>

                            <div v-if="!hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 font-semibold text-xs">
                                ✍️ Coretkan tanda tangan Anda di sini (layar HP/mouse)
                            </div>

                            <div class="flex items-center justify-between mt-2 pt-1 border-t border-slate-200">
                                <span class="text-[10px] text-slate-500">
                                    Gunakan jari di layar sentuh HP
                                </span>
                                <button
                                    type="button"
                                    @click="clearCanvas"
                                    class="text-[11px] font-bold text-rose-600 hover:text-rose-700 px-2 py-1 rounded hover:bg-rose-50 transition"
                                >
                                    🔄 Bersihkan / Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Opsi Simpan Tanda Tangan Permanen -->
                        <div class="mt-2.5 flex items-start gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                            <input
                                type="checkbox"
                                id="chkSimpanTtd"
                                v-model="simpanPermanen"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 mt-0.5 cursor-pointer"
                            />
                            <label for="chkSimpanTtd" class="text-[11px] text-slate-700 cursor-pointer font-medium leading-snug">
                                <strong>Simpan tanda tangan ini</strong> agar saat lembur berikutnya Anda tidak perlu coret-coret lagi, cukup sekali klik.
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SEKSI 2: FOTO DOKUMENTASI (UNTUK MONITORING MANDOR) -->
                <div>
                    <label class="font-bold text-slate-800 text-xs block mb-1.5 flex items-center gap-1.5">
                        <span>2.</span> Foto Dokumentasi Kerja (Opsional untuk Monitoring Mandor):
                    </label>

                    <div v-if="fotoPreview" class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 flex items-center justify-center max-h-48">
                        <img :src="fotoPreview" alt="Dokumentasi Kerja" class="max-h-48 w-full object-contain" />
                        <button
                            type="button"
                            @click="removeFoto"
                            class="absolute top-2 right-2 bg-rose-600 hover:bg-rose-700 text-white p-1.5 rounded-full shadow-lg text-xs font-bold transition"
                            title="Hapus foto"
                        >
                            ✕
                        </button>
                    </div>

                    <div v-else class="border border-slate-200 rounded-2xl p-3 bg-slate-50 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📷</span>
                            <div>
                                <div class="font-bold text-slate-700 text-[11px]">Unggah Foto Saat Kerja / Hasil</div>
                                <div class="text-[10px] text-slate-400">Bisa foto langsung lewat kamera HP atau galeri</div>
                            </div>
                        </div>
                        <label class="bg-white hover:bg-slate-100 text-slate-800 font-bold px-3 py-1.5 rounded-xl border border-slate-300 shadow-sm cursor-pointer transition text-[11px] whitespace-nowrap">
                            <span>Pilih / Foto</span>
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                @change="onFileChange"
                                class="hidden"
                            />
                        </label>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between gap-3">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition"
                    :disabled="isSubmitting"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="submitApproval"
                    class="px-5 py-2.5 text-xs font-extrabold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl shadow-md shadow-emerald-700/20 transition flex items-center gap-2 disabled:opacity-50"
                    :disabled="isSubmitting"
                >
                    <span v-if="isSubmitting">Memproses...</span>
                    <span v-else>✓ ACC & Tanda Tangani SPL</span>
                </button>
            </div>

        </div>
    </div>
</template>
