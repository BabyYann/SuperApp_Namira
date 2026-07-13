<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    CameraIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    XCircleIcon, 
    ArrowPathIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';

// ─── Props ───
const props = defineProps({
    todayCheckins: Array,
    todayDate: String,
    deadline: String,
    hadir_count: Number,
    terlambat_count: Number,
});

// ─── State ───
const scanning = ref(false);
const feedback = ref(null);       // { type: 'success'|'error'|'duplicate', student, message }
const feedbackTimer = ref(null);
const scannerRef = ref(null);
const lastScanned = ref('');      // Mencegah double-scan dalam 3 detik
const lastScannedTimer = ref(null);
const checkins = ref([...props.todayCheckins]);
const isProcessing = ref(false);  // Prevents double-submit during API call
let html5QrCode = null;

// ─── Start Scanner ───
const startScanner = async () => {
    const { Html5Qrcode } = await import('html5-qrcode');
    html5QrCode = new Html5Qrcode('qr-reader');
    scanning.value = true;

    try {
        await html5QrCode.start(
            { facingMode: 'environment' }, // Kamera belakang
            { fps: 10, qrbox: { width: 280, height: 280 } },
            onScanSuccess,
            null // Abaikan error partial scan
        );
    } catch (err) {
        scanning.value = false;
        alert('Tidak bisa mengakses kamera. Pastikan izin kamera sudah diberikan di browser.');
        console.error(err);
    }
};

// ─── Stop Scanner ───
const stopScanner = async () => {
    if (html5QrCode && scanning.value) {
        await html5QrCode.stop();
        scanning.value = false;
    }
};

// ─── Handle Scan Result ───
const onScanSuccess = async (decodedText) => {
    // Ekstrak NIS: buang prefix non-numerik jika ada (misal "NIS.")
    const nis = decodedText.replace(/[^0-9]/g, '');
    if (!nis) return;

    // Cegah double-scan NIS yang sama dalam 3 detik
    if (nis === lastScanned.value || isProcessing.value) return;

    isProcessing.value = true;
    lastScanned.value = nis;

    // Auto-reset double-scan lock setelah 3 detik
    clearTimeout(lastScannedTimer.value);
    lastScannedTimer.value = setTimeout(() => {
        lastScanned.value = '';
    }, 3000);

    try {
        const response = await fetch(route('yayasan.student-checkin.scan'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ nis }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Sukses check-in
            showFeedback('success', data.student, data.message, data.status);
            checkins.value.unshift({
                id: Date.now(),
                student_name: data.student.full_name,
                nis: data.student.nis,
                classroom: data.student.classroom,
                checkin_time: data.student.checkin_time,
                status: data.student.status,
            });
        } else if (response.status === 409) {
            // Sudah check-in
            showFeedback('duplicate', data.student, data.message, null);
        } else if (response.status === 404) {
            // NIS tidak ditemukan
            showFeedback('error', null, data.message, null);
        } else {
            showFeedback('error', null, 'Terjadi kesalahan. Coba lagi.', null);
        }
    } catch (err) {
        showFeedback('error', null, 'Koneksi gagal. Periksa jaringan.', null);
        console.error(err);
    } finally {
        isProcessing.value = false;
    }
};

// ─── Show Feedback Popup ───
const showFeedback = (type, student, message, status) => {
    feedback.value = { type, student, message, status };
    clearTimeout(feedbackTimer.value);
    // Auto-dismiss setelah 3 detik untuk sukses, 5 detik untuk error
    const duration = type === 'success' ? 3000 : 5000;
    feedbackTimer.value = setTimeout(() => {
        feedback.value = null;
    }, duration);
};

const dismissFeedback = () => {
    feedback.value = null;
    clearTimeout(feedbackTimer.value);
};

onUnmounted(() => {
    stopScanner();
    clearTimeout(feedbackTimer.value);
    clearTimeout(lastScannedTimer.value);
});
</script>

<template>
    <Head title="QR Scanner Absensi Gerbang" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <CameraIcon class="w-6 h-6 text-slate-600" />
                        Scanner Absensi Gerbang
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ todayDate }}</p>
                </div>
                <div class="flex gap-3 text-sm font-bold">
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-700 rounded-full">
                        <CheckCircleIcon class="w-4 h-4 text-green-600" /> Hadir: {{ hadir_count + checkins.filter(c => c.status === 'hadir').length - todayCheckins.filter(c => c.status === 'hadir').length }}
                    </span>
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full">
                        <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" /> Terlambat: {{ terlambat_count + checkins.filter(c => c.status === 'terlambat').length - todayCheckins.filter(c => c.status === 'terlambat').length }}
                    </span>
                    <span class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-full">
                        Batas: {{ deadline }} WIB
                    </span>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- LEFT: Scanner Area -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Camera Box -->
                    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                        <!-- Scanner Viewport -->
                        <div class="relative bg-slate-900 aspect-square">
                            <div id="qr-reader" class="w-full h-full"></div>

                            <!-- Corner Guides (decorative) -->
                            <div v-if="!scanning" class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-white">
                                <CameraIcon class="w-16 h-16 text-slate-500" />
                                <p class="text-sm font-medium text-slate-300 text-center px-8">
                                    Tekan tombol di bawah untuk<br>mengaktifkan kamera scanner
                                </p>
                            </div>

                            <!-- Active Scan Indicator -->
                            <div v-if="scanning" class="absolute top-3 right-3 flex items-center gap-2 bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                LIVE
                            </div>

                            <!-- Processing Overlay -->
                            <div v-if="isProcessing" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <div class="text-white text-sm font-bold flex items-center gap-2">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Memproses...
                                </div>
                            </div>
                        </div>

                        <!-- Control Button -->
                        <div class="p-4">
                            <button
                                v-if="!scanning"
                                @click="startScanner"
                                class="w-full py-3 bg-namira-teal hover:bg-teal-700 text-white font-extrabold rounded-2xl text-sm uppercase tracking-wider transition-all active:scale-95 shadow-lg shadow-teal-200 flex items-center justify-center gap-2"
                            >
                                <CameraIcon class="w-5 h-5" />
                                Mulai Scan Kartu
                            </button>
                            <button
                                v-else
                                @click="stopScanner"
                                class="w-full py-3 bg-red-500 hover:bg-red-600 text-white font-extrabold rounded-2xl text-sm uppercase tracking-wider transition-all active:scale-95 flex items-center justify-center gap-2"
                            >
                                <XCircleIcon class="w-5 h-5" />
                                Hentikan Scanner
                            </button>
                        </div>
                    </div>

                    <!-- Instructions Card -->
                    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4 text-sm text-teal-800">
                        <h4 class="font-bold mb-2 flex items-center gap-1.5">
                            <ClipboardDocumentCheckIcon class="w-4 h-4 text-teal-700" />
                            Cara Penggunaan:
                        </h4>
                        <ol class="space-y-1 list-decimal list-inside text-xs leading-relaxed">
                            <li>Klik "Mulai Scan Kartu" dan izinkan akses kamera</li>
                            <li>Arahkan kartu ID siswa ke kamera (depan atau belakang)</li>
                            <li>Sistem memproses otomatis — tunggu feedback</li>
                            <li>Lanjutkan ke siswa berikutnya</li>
                        </ol>
                        <p class="mt-3 text-[11px] text-teal-600 font-medium">
                            Info: QR Code (belakang kartu) atau Barcode (depan kartu) — keduanya didukung!
                        </p>
                    </div>
                </div>

                <!-- RIGHT: Checkin Log -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="font-black text-slate-800 flex items-center gap-2">
                                <ClipboardDocumentCheckIcon class="w-5 h-5 text-slate-500" />
                                Log Kehadiran Hari Ini
                            </h2>
                            <span class="text-xs text-slate-500 font-bold">{{ checkins.length }} Siswa</span>
                        </div>
                        <div class="overflow-y-auto max-h-[520px]">
                            <!-- Empty State -->
                            <div v-if="checkins.length === 0" class="py-16 text-center text-slate-400">
                                <ClipboardDocumentCheckIcon class="w-12 h-12 mx-auto mb-3 opacity-45" />
                                <p class="font-bold text-sm">Belum ada siswa yang absen hari ini</p>
                                <p class="text-slate-300 text-xs mt-1">Mulai scan untuk mencatat kehadiran</p>
                            </div>
                            <!-- List -->
                            <div v-else>
                                <div
                                    v-for="(c, idx) in checkins"
                                    :key="c.id"
                                    class="flex items-center gap-4 px-6 py-3.5 border-b border-slate-50 hover:bg-slate-50/50 transition-colors"
                                    :class="idx === 0 && c.id > 1000000000 ? 'bg-green-50/70 animate-pulse-once' : ''"
                                >
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-sm font-black text-slate-600 shrink-0">
                                        {{ c.student_name?.charAt(0) ?? '?' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-800 text-sm truncate">{{ c.student_name }}</p>
                                        <p class="text-xs text-slate-400">{{ c.classroom }} · NIS: {{ c.nis }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-black text-slate-700">{{ c.checkin_time }}</p>
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-black px-2 py-0.5 rounded-full"
                                            :class="c.status === 'hadir' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="c.status === 'hadir' ? 'bg-green-500' : 'bg-amber-500'"></span>
                                            {{ c.status === 'hadir' ? 'Hadir' : 'Terlambat' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Feedback Popup ─── -->
        <Teleport to="body">
            <Transition name="popup">
                <div
                    v-if="feedback"
                    class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/40 backdrop-blur-sm"
                    @click.self="dismissFeedback"
                >
                    <div
                        class="relative w-full max-w-sm rounded-[2rem] p-8 text-center shadow-2xl"
                        :class="{
                            'bg-white border-4 border-green-400': feedback.type === 'success',
                            'bg-white border-4 border-amber-400': feedback.type === 'duplicate',
                            'bg-white border-4 border-red-400': feedback.type === 'error',
                        }"
                    >
                        <!-- Icon -->
                        <div class="flex justify-center mb-4">
                            <div v-if="feedback.type === 'success' && feedback.status === 'hadir'" class="p-3 bg-green-50 text-green-600 rounded-full">
                                <CheckCircleIcon class="w-14 h-14" />
                            </div>
                            <div v-else-if="feedback.type === 'success' && feedback.status === 'terlambat'" class="p-3 bg-amber-50 text-amber-600 rounded-full">
                                <ExclamationTriangleIcon class="w-14 h-14" />
                            </div>
                            <div v-else-if="feedback.type === 'duplicate'" class="p-3 bg-blue-50 text-blue-600 rounded-full animate-spin">
                                <ArrowPathIcon class="w-14 h-14" />
                            </div>
                            <div v-else class="p-3 bg-red-50 text-red-600 rounded-full">
                                <XCircleIcon class="w-14 h-14" />
                            </div>
                        </div>

                        <!-- Student Photo / Initial -->
                        <div v-if="feedback.student" class="mb-4">
                            <div v-if="feedback.student.photo_url" class="w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-slate-200 shadow-lg mb-3">
                                <img :src="feedback.student.photo_url" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-24 h-24 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-3xl font-black text-slate-600 border-4 border-slate-200 shadow-lg mb-3">
                                {{ feedback.student.full_name?.charAt(0) ?? '?' }}
                            </div>
                            <h2 class="text-xl font-black text-slate-900 leading-tight">{{ feedback.student.full_name }}</h2>
                            <p class="text-sm text-slate-500 font-medium mt-0.5">{{ feedback.student.classroom }} · NIS: {{ feedback.student.nis }}</p>
                            <p v-if="feedback.status" class="mt-2.5 text-sm font-black inline-flex items-center gap-1.5 px-3 py-1 rounded-full"
                                :class="feedback.status === 'hadir' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                            >
                                <span class="w-2 h-2 rounded-full" :class="feedback.status === 'hadir' ? 'bg-green-500' : 'bg-amber-500'"></span>
                                {{ feedback.status === 'hadir' ? 'Hadir Tepat Waktu' : 'Terlambat' }}
                            </p>
                            <p v-if="feedback.student.checkin_time" class="text-2xl font-black text-slate-800 mt-2">
                                {{ feedback.student.checkin_time }}
                            </p>
                        </div>

                        <p class="text-sm text-slate-600 mt-2 leading-relaxed">{{ feedback.message }}</p>

                        <button @click="dismissFeedback" class="mt-6 px-8 py-2.5 bg-slate-900 text-white text-xs font-black rounded-full uppercase tracking-wider hover:bg-slate-700 transition-colors">
                            OK, Lanjutkan
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.popup-enter-active, .popup-leave-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.popup-enter-from, .popup-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

@keyframes pulse-once {
    0%, 100% { background-color: rgb(240 253 244 / 0.7); }
    50% { background-color: rgb(187 247 208 / 0.7); }
}
.animate-pulse-once {
    animation: pulse-once 1.5s ease-in-out 2;
}

/* Override html5-qrcode default styles */
#qr-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
#qr-reader__scan_region {
    min-height: unset !important;
}
#qr-reader__dashboard {
    display: none !important;
}
</style>
