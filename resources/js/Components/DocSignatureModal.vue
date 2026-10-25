<script setup>
import { ref, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { alertError, showSuccessAlert } from '@/utils/swal';

const props = defineProps({
    show: Boolean,
    tanggal: String,
    savedSignature: String,
    userName: String,
    userJabatan: String,
});

const emit = defineEmits(['close', 'saved']);

const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const useSaved = ref(!!props.savedSignature);
const simpanPermanen = ref(true);
const isSubmitting = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal) {
        useSaved.value = !!props.savedSignature;
        hasDrawn.value = false;
        nextTick(() => {
            if (!useSaved.value) initCanvas();
        });
    }
});

function initCanvas() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * 2;
    canvas.height = rect.height * 2;
    ctx.scale(2, 2);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';
    ctx.lineWidth = 2.5;
    ctx.clearRect(0, 0, rect.width, rect.height);
}

function getPos(e) {
    const canvas = canvasRef.value;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: clientX - rect.left, y: clientY - rect.top };
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
    nextTick(() => initCanvas());
}

function submitSignature() {
    let signatureData = null;

    if (useSaved.value && props.savedSignature) {
        signatureData = props.savedSignature;
    } else {
        if (!hasDrawn.value) {
            alertError('Tanda Tangan Belum Ada', 'Silakan coretkan tanda tangan Anda pada kotak yang tersedia.');
            return;
        }
        const canvas = canvasRef.value;
        signatureData = canvas.toDataURL('image/png');
    }

    isSubmitting.value = true;

    router.post(route('dokumen.ttd'), {
        tanda_tangan: signatureData,
        tanggal: props.tanggal,
        simpan_permanen: simpanPermanen.value,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            emit('saved', signatureData);
            emit('close');
            showSuccessAlert('Tanda Tangan Tersimpan', 'Tanda tangan Anda akan muncul di dokumen cetak.');
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

            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg">✍️</span>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 leading-tight">
                            Tanda Tangan Digital Dokumen
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ userName }} · {{ userJabatan }} · {{ tanggal }}
                        </p>
                    </div>
                </div>
                <button
                    @click="emit('close')"
                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center text-sm font-bold transition"
                >✕</button>
            </div>

            <!-- Body -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs">

                <!-- Info Box -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-3.5">
                    <div class="font-bold text-blue-950 text-xs mb-1">
                        📋 Persetujuan Dokumen Lembur
                    </div>
                    <p class="text-[11px] text-blue-800">
                        Tanda tangan ini akan ditampilkan di kolom <strong>{{ userJabatan }}</strong>
                        pada dokumen SPL dan LRLK untuk tanggal <strong>{{ tanggal }}</strong>.
                    </p>
                </div>

                <!-- Tanda Tangan -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                            Tanda Tangan Anda:
                        </label>
                        <button
                            v-if="savedSignature && !useSaved"
                            type="button"
                            @click="useSaved = true"
                            class="text-[11px] font-bold text-blue-700 hover:underline"
                        >
                            ← Pakai TTD Tersimpan
                        </button>
                    </div>

                    <!-- TTD Tersimpan -->
                    <div v-if="useSaved && savedSignature" class="bg-slate-50 border-2 border-blue-500 rounded-2xl p-4 text-center">
                        <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-200">
                            <span class="text-xs font-bold text-blue-900 flex items-center gap-1">
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
                            <img :src="savedSignature" alt="TTD Tersimpan" class="max-h-full object-contain" />
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2">
                            Tanda tangan ini akan ditampilkan di dokumen cetak.
                        </p>
                    </div>

                    <!-- Canvas TTD Baru -->
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
                                ✍️ Coretkan tanda tangan Anda di sini
                            </div>

                            <div class="flex items-center justify-between mt-2 pt-1 border-t border-slate-200">
                                <span class="text-[10px] text-slate-500">Gunakan jari atau mouse</span>
                                <button
                                    type="button"
                                    @click="clearCanvas"
                                    class="text-[11px] font-bold text-rose-600 hover:text-rose-700 px-2 py-1 rounded hover:bg-rose-50 transition"
                                >
                                    🔄 Bersihkan
                                </button>
                            </div>
                        </div>

                        <!-- Opsi Simpan Permanen -->
                        <div class="mt-2.5 flex items-start gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                            <input
                                type="checkbox"
                                id="chkSimpanPermanen"
                                v-model="simpanPermanen"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer"
                            />
                            <label for="chkSimpanPermanen" class="text-[11px] text-slate-700 cursor-pointer font-medium leading-snug">
                                <strong>Simpan sebagai tanda tangan default</strong> — tidak perlu coret lagi untuk dokumen berikutnya.
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
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
                    @click="submitSignature"
                    class="px-5 py-2.5 text-xs font-extrabold text-white bg-blue-700 hover:bg-blue-800 rounded-xl shadow-md shadow-blue-700/20 transition flex items-center gap-2 disabled:opacity-50"
                    :disabled="isSubmitting"
                >
                    <span v-if="isSubmitting">Menyimpan...</span>
                    <span v-else>✓ Simpan Tanda Tangan</span>
                </button>
            </div>

        </div>
    </div>
</template>
