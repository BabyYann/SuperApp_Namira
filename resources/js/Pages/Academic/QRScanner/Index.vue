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
const manualNis = ref('');
const cameras = ref([]);
const selectedCameraId = ref('');
const fileInputRef = ref(null);
const debugLogs = ref([]);        // Live Debug Console Log for user troubleshooting
let html5QrCode = null;
let quaggaRunning = false;

const addLog = (msg, type = 'info') => {
    const time = new Date().toLocaleTimeString('id-ID');
    debugLogs.value.unshift({ time, msg, type });
    if (debugLogs.value.length > 20) debugLogs.value.pop();
};

// ─── Play Audio Beep ───
const playBeep = (type = 'success') => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type === 'success' ? 'sine' : 'sawtooth';
        osc.frequency.setValueAtTime(type === 'success' ? 880 : 300, ctx.currentTime); // 880Hz pitch for success
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + (type === 'success' ? 0.15 : 0.4));
    } catch (e) {
        console.log('Audio Context not allowed or supported', e);
    }
};

// ─── Load Cameras on Mount ───
onMounted(async () => {
    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        const deviceList = await Html5Qrcode.getCameras();
        if (deviceList && deviceList.length > 0) {
            cameras.value = deviceList;
            selectedCameraId.value = deviceList[0].id;
            addLog(`Terdeteksi ${deviceList.length} kamera. Kamera utama: ${deviceList[0].label || 'Default'}`);
        } else {
            addLog('Tidak terdeteksi kamera di perangkat ini. Gunakan USB Scanner Gun atau Input Manual.', 'warn');
        }
    } catch (err) {
        console.warn('Could not enumerate camera devices:', err);
        addLog('Gagal memuat daftar kamera. Izinkan akses kamera di browser.', 'error');
    }
});

// ─── Start Scanner (QR Code Only) ───
const startScanner = async () => {
    const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
    
    html5QrCode = new Html5Qrcode('qr-reader', { verbose: false });
    
    scanning.value = true;
    addLog('Mengaktifkan kamera scanner QR Code...');

    // Determine camera source
    const cameraConfig = selectedCameraId.value 
        ? { deviceId: { exact: selectedCameraId.value } }
        : { facingMode: 'environment' };

    const formatsToSupport = [
        Html5QrcodeSupportedFormats.QR_CODE,
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39,
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
    ];

    try {
        await html5QrCode.start(
            cameraConfig,
            { 
                fps: 15,
                formatsToSupport,
                qrbox: (viewfinderWidth, viewfinderHeight) => ({
                    width: Math.min(viewfinderWidth * 0.8, 300),
                    height: Math.min(viewfinderHeight * 0.8, 300)
                }),
                aspectRatio: 1.0,
                videoConstraints: {
                    width: { min: 640, ideal: 1280, max: 1920 },
                    height: { min: 480, ideal: 720, max: 1080 },
                    focusMode: 'continuous'
                }
            },
            onScanSuccess,
            () => {} // ignore per-frame parse errors
        );

        addLog('🎥 Kamera QR Code Aktif! Arahkan QR Code belakang kartu ke kamera.', 'success');
    } catch (err) {
        scanning.value = false;
        addLog(`❌ Gagal membuka kamera: ${err?.message || err}`, 'error');
        alert('Tidak bisa mengakses kamera. Pastikan izin kamera sudah diberikan di browser.');
        console.error(err);
    }
};

// ─── Stop Scanner ───
const stopScanner = async () => {
    if (html5QrCode && scanning.value) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            console.warn('Error stopping scanner:', e);
        }
        scanning.value = false;
    }
    addLog('Kamera dihentikan.');
};

// ─── High-Performance Multi-Tier Scanner Engine Pipeline ───
// Pipeline: Native BarcodeDetector -> ZXing (@zxing/library) -> Quagga2 (Fallback)
const mobileVideoRef = ref(null);
const mobileCanvasRef = ref(null);
let mobileStream = null;
let jsqrAnimFrame = null;
let lastScanTimestamp = 0;
let isDecodeBusy = false; // Mutex Lock to prevent overlapping decode threads

// Dynamic Tracking Frame State
const trackFrame = ref({ x: 15, y: 15, w: 70, h: 70, state: 'idle', visible: false });

// Engines
let barcodeDetector = null;
let zxingReader = null;
let quaggaModule = null;

const STRICT_BARCODE_FORMATS = [
    'qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'data_matrix'
];

// Initialize Native BarcodeDetector with Strict Format Filtering
const initBarcodeDetector = async () => {
    if (typeof BarcodeDetector === 'undefined') return;
    try {
        const supported = await BarcodeDetector.getSupportedFormats();
        const validFormats = STRICT_BARCODE_FORMATS.filter(f => supported.includes(f));
        if (validFormats.length > 0) {
            barcodeDetector = new BarcodeDetector({ formats: validFormats });
            addLog(`BarcodeDetector aktif (Format: ${validFormats.join(', ')})`, 'success');
        }
    } catch (e) {
        barcodeDetector = null;
    }
};

// Initialize ZXing MultiFormatReader
const initZXingReader = async () => {
    try {
        const { BrowserMultiFormatReader } = await import('@zxing/library');
        zxingReader = new BrowserMultiFormatReader();
        addLog('ZXing Barcode Engine siap (Primary 1D Code128/39)', 'success');
    } catch (e) {
        console.warn('ZXing init error:', e);
    }
};

// ─── Camera Start with High Resolution Constraints ───
const startScannerMobile = async () => {
    try {
        const videoConstraints = selectedCameraId.value
            ? { deviceId: { exact: selectedCameraId.value }, width: { min: 1280, ideal: 1920 }, height: { min: 720, ideal: 1080 } }
            : { facingMode: 'environment', width: { min: 1280, ideal: 1920 }, height: { min: 720, ideal: 1080 }, focusMode: 'continuous' };

        mobileStream = await navigator.mediaDevices.getUserMedia({ video: videoConstraints });
        mobileVideoRef.value.srcObject = mobileStream;
        await mobileVideoRef.value.play();
        scanning.value = true;
        isDecodeBusy = false;

        await initBarcodeDetector();
        await initZXingReader();

        addLog('🎥 Kamera Resolusi Tinggi Aktif. Pipeline 3-Tier Siap!', 'success');
        scannerPipelineLoop();
    } catch (err) {
        scanning.value = false;
        addLog(`❌ Gagal membuka kamera HP: ${err?.message || err}`, 'error');
        alert('Tidak bisa mengakses kamera HP. Pastikan izin kamera sudah diberikan.');
    }
};

const stopScannerMobile = () => {
    if (jsqrAnimFrame) { cancelAnimationFrame(jsqrAnimFrame); jsqrAnimFrame = null; }
    if (mobileStream) { mobileStream.getTracks().forEach(t => t.stop()); mobileStream = null; }
    scanning.value = false;
    isDecodeBusy = false;
    trackFrame.value = { x: 15, y: 15, w: 70, h: 70, state: 'idle', visible: false };
    addLog('Kamera mobile dihentikan.');
};

// Helper: update tracking frame dari corner points / bounding boxes
const updateTrackFrame = (pts, dw, dh) => {
    const pad = 16;
    const minX = Math.max(0, Math.min(...pts.map(p => p.x)) - pad);
    const maxX = Math.min(dw, Math.max(...pts.map(p => p.x)) + pad);
    const minY = Math.max(0, Math.min(...pts.map(p => p.y)) - pad);
    const maxY = Math.min(dh, Math.max(...pts.map(p => p.y)) + pad);
    trackFrame.value = {
        x: (minX / dw) * 100,
        y: (minY / dh) * 100,
        w: ((maxX - minX) / dw) * 100,
        h: ((maxY - minY) / dh) * 100,
        state: isProcessing.value ? 'locked' : 'detected',
        visible: true,
    };
};

// ─── 3-Tier Pipeline Loop (180ms Throttle + Mutex Lock) ───
const scannerPipelineLoop = async () => {
    if (!scanning.value || !mobileVideoRef.value || !mobileCanvasRef.value) return;

    const video = mobileVideoRef.value;
    const canvas = mobileCanvasRef.value;

    if (video.readyState === video.HAVE_ENOUGH_DATA && !isDecodeBusy) {
        const now = performance.now();

        // Throttle 180ms (~5-6 fps) - Mencegah CPU overheat / freeze
        if (now - lastScanTimestamp > 180) {
            lastScanTimestamp = now;
            isDecodeBusy = true; // Set Mutex Lock

            try {
                const dw = video.parentElement?.clientWidth || 360;
                const dh = video.parentElement?.clientHeight || 360;

                canvas.width = dw;
                canvas.height = dh;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                const vw = video.videoWidth, vh = video.videoHeight;
                let sx = 0, sy = 0, sw = vw, sh = vh;
                if (vw / vh > dw / dh) { sw = vh * (dw / dh); sx = (vw - sw) / 2; }
                else { sh = vw * (dh / dw); sy = (vh - sh) / 2; }
                ctx.drawImage(video, sx, sy, sw, sh, 0, 0, dw, dh);

                let codeFound = false;

                // ── TIER 1: Native BarcodeDetector (QR & Native Browser) ──
                if (barcodeDetector) {
                    try {
                        const results = await barcodeDetector.detect(video);
                        if (results.length > 0) {
                            const r = results[0];
                            const scaleX = dw / (vw > vh * (dw / dh) ? vh * (dw / dh) : vw);
                            const scaleY = dh / (vw / vh > dw / dh ? vh : vw * (dh / dw));
                            const offX = (dw - vw * scaleX) / 2;
                            const offY = (dh - vh * scaleY) / 2;

                            const pts = (r.cornerPoints || []).map(p => ({ x: p.x * scaleX + offX, y: p.y * scaleY + offY }));
                            if (pts.length >= 2) updateTrackFrame(pts, dw, dh);

                            codeFound = true;
                            if (!isProcessing.value && r.rawValue) {
                                addLog(`🎯 BarcodeDetector [${r.format}]: "${r.rawValue}"`, 'success');
                                onScanSuccess(r.rawValue);
                            }
                        }
                    } catch (e) {}
                }

                // ── TIER 2: ZXing Engine (Primary 1D Code128 / Code39 & QR) ──
                if (!codeFound && zxingReader) {
                    try {
                        const result = zxingReader.decodeFromCanvas(canvas);
                        if (result && result.getText()) {
                            const text = result.getText();
                            const pts = (result.getResultPoints() || []).map(p => ({ x: p.getX(), y: p.getY() }));
                            if (pts.length >= 2) updateTrackFrame(pts, dw, dh);

                            codeFound = true;
                            if (!isProcessing.value) {
                                addLog(`🎯 ZXing [${result.getBarcodeFormat()}]: "${text}"`, 'success');
                                onScanSuccess(text);
                            }
                        }
                    } catch (e) {}
                }

                // ── TIER 2.5: jsQR Fallback (Khusus QR Code) ──
                if (!codeFound) {
                    try {
                        const imageData = ctx.getImageData(0, 0, dw, dh);
                        const jsQR = (await import('jsqr')).default;
                        const code = jsQR(imageData.data, dw, dh, { inversionAttempts: 'dontInvert' });
                        if (code && code.data) {
                            codeFound = true;
                            const loc = code.location;
                            updateTrackFrame([loc.topLeftCorner, loc.topRightCorner, loc.bottomLeftCorner, loc.bottomRightCorner], dw, dh);
                            if (!isProcessing.value) {
                                addLog(`🎯 jsQR Code: "${code.data}"`, 'success');
                                onScanSuccess(code.data);
                            }
                        }
                    } catch (e) {}
                }

                // ── TIER 3: Quagga2 Fallback (PNG Lossless & Small Patch Locator) ──
                if (!codeFound && !isProcessing.value) {
                    try {
                        if (!quaggaModule) {
                            quaggaModule = (await import('@ericblade/quagga2')).default;
                        }
                        // PERBAIKAN: Gunakan image/png (Lossless) agar garis barcode tajam
                        const pngDataUrl = canvas.toDataURL('image/png');

                        quaggaModule.decodeSingle({
                            src: pngDataUrl,
                            numOfWorkers: 0,
                            inputStream: { size: Math.max(dw, dh) },
                            // PERBAIKAN: Locator config untuk barcode kecil/garis tipis
                            locator: {
                                patchSize: "small",
                                halfSample: false
                            },
                            decoder: {
                                readers: ["code_128_reader", "code_39_reader", "ean_reader", "upc_reader"]
                            },
                            locate: true
                        }, (res) => {
                            if (res && res.codeResult && res.codeResult.code) {
                                const barcode = res.codeResult.code;
                                addLog(`🎯 Quagga2 Fallback [${res.codeResult.format}]: "${barcode}"`, 'success');
                                if (res.boxes && res.boxes.length > 0) {
                                    const box = res.boxes.find(b => b !== res.box) || res.box;
                                    if (box) {
                                        const pts = box.map(p => ({ x: p[0], y: p[1] }));
                                        updateTrackFrame(pts, dw, dh);
                                    }
                                }
                                if (!isProcessing.value) {
                                    onScanSuccess(barcode);
                                }
                            } else if (!codeFound) {
                                trackFrame.value = { x: 15, y: 15, w: 70, h: 70, state: 'idle', visible: false };
                            }
                            isDecodeBusy = false; // Unlock Mutex on Async Finish
                        });
                    } catch (e) {
                        isDecodeBusy = false;
                    }
                } else {
                    if (!codeFound) {
                        trackFrame.value = { x: 15, y: 15, w: 70, h: 70, state: 'idle', visible: false };
                    }
                    isDecodeBusy = false; // Unlock Mutex
                }
            } catch (e) {
                isDecodeBusy = false; // Unlock Mutex on Error
            }
        }
    }
    jsqrAnimFrame = requestAnimationFrame(scannerPipelineLoop);
};

const submitManualNis = () => {
    if (!manualNis.value) return;
    addLog(`⌨️ Input Manual/USB: "${manualNis.value}"`);
    onScanSuccess(manualNis.value);
    manualNis.value = '';
};

// ─── Scan from Image File Upload (Dual Pass: QR + Barcode) ───
const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    addLog(`📁 Memproses foto: ${file.name}...`);

    let decoded = false;

    // Pass 1: QR Code Scanner
    try {
        const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
        const formatsToSupport = [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
        ];
        
        const fileScanner = new Html5Qrcode('file-qr-reader', { formatsToSupport, verbose: false });
        const decodedText = await fileScanner.scanFile(file, true);
        if (decodedText) {
            decoded = true;
            addLog(`🔍 QR Foto terbaca: "${decodedText}"`, 'success');
            onScanSuccess(decodedText);
        }
    } catch (err) {
        // Fallback to Pass 2: Quagga 1D Barcode decoder
    }

    if (!decoded) {
        // Pass 2: Quagga 1D Barcode Single File Decoder
        try {
            const Quagga = (await import('@ericblade/quagga2')).default;
            const imgUrl = URL.createObjectURL(file);
            
            Quagga.decodeSingle({
                src: imgUrl,
                numOfWorkers: 0,
                inputStream: { size: 800 },
                decoder: {
                    readers: ["code_128_reader", "code_39_reader", "ean_reader", "upc_reader"]
                }
            }, (result) => {
                URL.revokeObjectURL(imgUrl);
                if (result && result.codeResult && result.codeResult.code) {
                    addLog(`🔍 Kode Batang Foto terbaca: "${result.codeResult.code}"`, 'success');
                    onScanSuccess(result.codeResult.code);
                } else {
                    playBeep('error');
                    addLog(`❌ Foto tidak terbaca: Tidak ditemukan Kode Batang / QR yang jelas.`, 'error');
                    alert('Tidak dapat membaca Kode Batang atau QR Code dari foto ini. Pastikan foto cukup jelas dan tidak buram.');
                }
            });
        } catch (qErr) {
            playBeep('error');
            addLog(`❌ Gagal membaca file foto.`, 'error');
            alert('Gagal memproses file foto.');
        }
    }

    if (event.target) event.target.value = '';
};

import axios from 'axios';

// ─── Handle Scan Result ───
const onScanSuccess = async (decodedText) => {
    addLog(`🎯 Teks Terdeteksi: "${decodedText}"`);

    // Ekstrak NIS: buang prefix non-numerik jika ada (misal "NIS." atau "NIS: ")
    const nis = decodedText.replace(/[^0-9]/g, '');
    if (!nis) {
        addLog(`⚠️ Teks "${decodedText}" tidak mengandung angka NIS.`, 'warn');
        return;
    }

    // Cegah double-scan NIS yang sama dalam 3 detik
    if (nis === lastScanned.value || isProcessing.value) {
        addLog(`⏳ NIS ${nis} diabaikan (double scan lock active).`, 'warn');
        return;
    }

    isProcessing.value = true;
    lastScanned.value = nis;

    // Auto-reset double-scan lock setelah 3 detik
    clearTimeout(lastScannedTimer.value);
    lastScannedTimer.value = setTimeout(() => {
        lastScanned.value = '';
    }, 3000);

    addLog(`🚀 Memverifikasi NIS ${nis} ke server...`);

    try {
        const response = await axios.post(route('yayasan.student-checkin.scan'), { nis });
        const data = response.data;

        if (data && data.success) {
            // Sukses check-in
            playBeep('success');
            addLog(`✅ HADIR: ${data.student.full_name} (${data.student.classroom})`, 'success');
            showFeedback('success', data.student, data.message, data.status);
            checkins.value.unshift({
                id: Date.now(),
                student_name: data.student.full_name,
                nis: data.student.nis,
                classroom: data.student.classroom,
                checkin_time: data.student.checkin_time,
                status: data.student.status,
            });
        }
    } catch (err) {
        playBeep('error');
        const status = err.response?.status;
        const data = err.response?.data || {};

        if (status === 409) {
            // Sudah check-in
            addLog(`⚠️ DUPLIKAT: ${data.message}`, 'warn');
            showFeedback('duplicate', data.student, data.message, null);
        } else if (status === 404) {
            // NIS tidak ditemukan
            addLog(`❌ GAGAL: ${data.message}`, 'error');
            showFeedback('error', null, data.message, null);
        } else {
            const msg = data.message || err.message || 'Terjadi kesalahan server.';
            addLog(`❌ SERVER ERROR: ${msg}`, 'error');
            showFeedback('error', null, msg, null);
        }
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
    stopScannerMobile();
    clearTimeout(feedbackTimer.value);
    clearTimeout(lastScannedTimer.value);
});
</script>

<template>
    <Head title="QR Scanner Absensi Gerbang" />
    <AuthenticatedLayout>
        <template #header>
            <div class="hidden md:flex items-center justify-between">
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

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- 📱 MOBILE PWA VIEW (block md:hidden) — Premium Dark Scanner -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="block md:hidden -mx-4 -mt-4">

            <!-- ── Header Card Gradient ── -->
            <div class="bg-gradient-to-br from-[#009688] to-[#0f172a] px-4 pt-5 pb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-[10px] font-extrabold tracking-widest uppercase text-teal-300">Scanner Absensi</p>
                        <h1 class="text-xl font-black text-white leading-tight">Gerbang Masuk</h1>
                        <p class="text-xs text-teal-200/80 mt-0.5">{{ todayDate }}</p>
                    </div>
                    <!-- Live / Idle Badge -->
                    <div
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black"
                        :class="scanning ? 'bg-red-500/30 text-red-200 border border-red-400/40' : 'bg-white/10 text-white/60 border border-white/20'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="scanning ? 'bg-red-400 animate-pulse' : 'bg-white/30'"></span>
                        {{ scanning ? 'LIVE' : 'SIAGA' }}
                    </div>
                </div>

                <!-- Quick Stats Bar -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-3 py-2.5 text-center">
                        <p class="text-2xl font-black text-white leading-none">
                            {{ hadir_count + checkins.filter(c => c.status === 'hadir').length - todayCheckins.filter(c => c.status === 'hadir').length }}
                        </p>
                        <p class="text-[10px] text-emerald-300 font-bold mt-0.5">Hadir</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-3 py-2.5 text-center">
                        <p class="text-2xl font-black text-white leading-none">
                            {{ terlambat_count + checkins.filter(c => c.status === 'terlambat').length - todayCheckins.filter(c => c.status === 'terlambat').length }}
                        </p>
                        <p class="text-[10px] text-amber-300 font-bold mt-0.5">Terlambat</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-3 py-2.5 text-center">
                        <p class="text-xs font-black text-white leading-none pt-1">{{ deadline }}</p>
                        <p class="text-[10px] text-slate-300 font-bold mt-0.5">Batas WIB</p>
                    </div>
                </div>
            </div>

            <!-- ── Scanner Viewport — jsQR Smart Tracking ── -->
            <div class="bg-slate-950 relative">
                <!-- Camera Feed Container -->
                <div class="relative w-full" style="aspect-ratio: 1/1; max-height: 80vw; overflow: hidden;">

                    <!-- Native Video Element -->
                    <video
                        ref="mobileVideoRef"
                        class="w-full h-full object-cover"
                        playsinline
                        muted
                        autoplay
                        :class="scanning ? 'opacity-100' : 'opacity-0'"
                        style="display: block;"
                    ></video>

                    <!-- Hidden Canvas for jsQR Processing -->
                    <canvas ref="mobileCanvasRef" class="hidden"></canvas>

                    <!-- ── Idle Placeholder ── -->
                    <div v-if="!scanning" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 z-10">
                        <div class="relative w-48 h-48 mb-6">
                            <!-- Idle pulsing corner frame -->
                            <div class="absolute top-0 left-0 w-9 h-9 border-t-[3px] border-l-[3px] border-teal-400 rounded-tl-xl idle-corner"></div>
                            <div class="absolute top-0 right-0 w-9 h-9 border-t-[3px] border-r-[3px] border-teal-400 rounded-tr-xl idle-corner"></div>
                            <div class="absolute bottom-0 left-0 w-9 h-9 border-b-[3px] border-l-[3px] border-teal-400 rounded-bl-xl idle-corner"></div>
                            <div class="absolute bottom-0 right-0 w-9 h-9 border-b-[3px] border-r-[3px] border-teal-400 rounded-br-xl idle-corner"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <CameraIcon class="w-16 h-16 text-slate-600" />
                            </div>
                        </div>
                        <p class="text-sm text-slate-400 font-semibold text-center px-8 leading-relaxed">
                            Tekan <span class="text-teal-400 font-black">Mulai Scan</span> untuk mengaktifkan kamera
                        </p>
                    </div>

                    <!-- ── Smart Tracking Frame Overlay ── -->
                    <div v-if="scanning" class="absolute inset-0 pointer-events-none z-20">

                        <!-- Teal corner guides (always shown, subtle) -->
                        <div class="absolute top-3 left-3 w-8 h-8 border-t-[3px] border-l-[3px] border-teal-500/60 rounded-tl-xl"></div>
                        <div class="absolute top-3 right-3 w-8 h-8 border-t-[3px] border-r-[3px] border-teal-500/60 rounded-tr-xl"></div>
                        <div class="absolute bottom-3 left-3 w-8 h-8 border-b-[3px] border-l-[3px] border-teal-500/60 rounded-bl-xl"></div>
                        <div class="absolute bottom-3 right-3 w-8 h-8 border-b-[3px] border-r-[3px] border-teal-500/60 rounded-br-xl"></div>

                        <!-- ✨ Smart Adaptive Tracking Frame -->
                        <div
                            class="track-frame"
                            :class="[
                                trackFrame.visible ? 'opacity-100' : 'opacity-0',
                                trackFrame.state === 'detected' ? 'frame-detected' : '',
                                trackFrame.state === 'locked' ? 'frame-locked' : '',
                            ]"
                            :style="{
                                left: trackFrame.x + '%',
                                top: trackFrame.y + '%',
                                width: trackFrame.w + '%',
                                height: trackFrame.h + '%',
                            }"
                        >
                            <!-- Corner accents on the tracking frame -->
                            <span class="frame-corner frame-tl"></span>
                            <span class="frame-corner frame-tr"></span>
                            <span class="frame-corner frame-bl"></span>
                            <span class="frame-corner frame-br"></span>
                        </div>

                        <!-- Scan-line (only when no QR detected) -->
                        <div v-if="!trackFrame.visible" class="scan-line"></div>
                    </div>

                    <!-- ── Processing Overlay ── -->
                    <div v-if="isProcessing" class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center z-30">
                        <svg class="animate-spin w-10 h-10 text-teal-400 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-teal-300 font-black text-sm">Memverifikasi...</p>
                    </div>
                </div>

                <!-- Camera Controls -->
                <div class="px-4 py-4 space-y-3">
                    <!-- Camera Select -->
                    <select
                        v-if="cameras.length > 1"
                        v-model="selectedCameraId"
                        class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-xs font-semibold rounded-xl px-3 py-2.5 focus:ring-teal-500 focus:border-teal-500"
                    >
                        <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                            {{ cam.label || 'Kamera (' + cam.id.substring(0, 8) + '...)' }}
                        </option>
                    </select>

                    <!-- Start / Stop Button -->
                    <button
                        v-if="!scanning"
                        @click="startScannerMobile"
                        class="w-full py-4 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-400 hover:to-teal-500 text-white font-black rounded-2xl text-sm uppercase tracking-widest transition-all active:scale-95 shadow-xl shadow-teal-900/50 flex items-center justify-center gap-2.5"
                    >
                        <CameraIcon class="w-5 h-5" />
                        Mulai Scan Kamera
                    </button>
                    <button
                        v-else
                        @click="stopScannerMobile"
                        class="w-full py-4 bg-red-600 hover:bg-red-500 text-white font-black rounded-2xl text-sm uppercase tracking-widest transition-all active:scale-95 flex items-center justify-center gap-2.5"
                    >
                        <XCircleIcon class="w-5 h-5" />
                        Hentikan Scanner
                    </button>

                    <!-- Upload Photo -->
                    <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleFileUpload" />
                    <button
                        type="button"
                        @click="fileInputRef.click()"
                        class="w-full py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition"
                    >
                        <CameraIcon class="w-4 h-4 text-slate-400" />
                        Scan dari Foto Kartu
                    </button>
                </div>
            </div>

            <!-- ── Manual Input / USB Scanner ── -->
            <div class="bg-white mx-4 mt-4 rounded-2xl p-4 shadow-sm border border-slate-100">
                <label class="block text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">
                    Input Manual / USB Scanner Gun
                </label>
                <div class="flex gap-2">
                    <input
                        v-model="manualNis"
                        @keyup.enter="submitManualNis"
                        type="text"
                        inputmode="numeric"
                        placeholder="Ketik NIS siswa (5251564)..."
                        class="flex-1 h-12 px-3 text-sm border border-slate-200 rounded-xl focus:ring-teal-500 focus:border-teal-500 font-medium text-slate-800"
                    />
                    <button
                        @click="submitManualNis"
                        class="px-5 h-12 bg-teal-600 hover:bg-teal-700 text-white font-black text-sm rounded-xl shadow-sm transition active:scale-95"
                    >
                        <ArrowPathIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- ── Live Checkin Log ── -->
            <div class="mx-4 mt-4 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-black text-slate-800 flex items-center gap-2 text-sm">
                        <ClipboardDocumentCheckIcon class="w-4 h-4 text-slate-500" />
                        Log Kehadiran Hari Ini
                    </h2>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">{{ checkins.length }} Siswa</span>
                </div>

                <!-- Empty State -->
                <div v-if="checkins.length === 0" class="bg-white rounded-2xl border border-slate-100 py-10 text-center text-slate-400 shadow-sm">
                    <ClipboardDocumentCheckIcon class="w-10 h-10 mx-auto mb-2 opacity-40" />
                    <p class="font-bold text-sm">Belum ada siswa absen</p>
                    <p class="text-xs text-slate-300 mt-1">Mulai scan untuk mencatat kehadiran</p>
                </div>

                <!-- List -->
                <div v-else class="space-y-2">
                    <div
                        v-for="(c, idx) in checkins"
                        :key="c.id"
                        class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3 flex items-center gap-3 transition-all"
                        :class="idx === 0 && c.id > 1000000000 ? 'border-emerald-200 bg-emerald-50/50 ring-1 ring-emerald-200' : ''"
                    >
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-black text-white shrink-0"
                            :class="c.status === 'hadir' ? 'bg-gradient-to-br from-teal-500 to-emerald-600' : 'bg-gradient-to-br from-amber-400 to-orange-500'"
                        >
                            {{ c.student_name?.charAt(0) ?? '?' }}
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="font-extrabold text-slate-800 text-sm truncate">{{ c.student_name }}</p>
                            <p class="text-xs text-slate-400">{{ c.classroom }} · {{ c.nis }}</p>
                        </div>
                        <!-- Time + Status -->
                        <div class="text-right shrink-0">
                            <p class="text-sm font-black text-slate-800">{{ c.checkin_time }}</p>
                            <span
                                class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full mt-0.5"
                                :class="c.status === 'hadir' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="c.status === 'hadir' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                {{ c.status === 'hadir' ? 'Hadir' : 'Terlambat' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden file-qr-reader div needed by html5-qrcode -->
            <div id="file-qr-reader" class="hidden"></div>
        </div>
        <!-- END MOBILE VIEW -->

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- 🖥️ DESKTOP VIEW (hidden md:block) — Original Untouched -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="hidden md:block max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- LEFT: Scanner Area -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Camera Box -->
                    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                        <!-- Scanner Viewport -->
                        <div class="relative bg-slate-900 aspect-square">
                            <div id="qr-reader" class="w-full h-full"></div>

                            <!-- Corner Guides (decorative) -->
                            <div v-if="!scanning" class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-white bg-slate-900 z-10">
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

                        <!-- Control Buttons & Camera Select -->
                        <div class="p-4 space-y-3">
                            <!-- Camera Select Dropdown if multiple devices -->
                            <div v-if="cameras.length > 1" class="space-y-1">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pilih Kamera:</label>
                                <select 
                                    v-model="selectedCameraId"
                                    class="w-full text-xs font-semibold border-slate-200 rounded-xl focus:ring-teal-500 focus:border-teal-500 py-2 px-3 text-slate-700 bg-slate-50"
                                >
                                    <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                                        {{ cam.label || 'Kamera (' + cam.id.substring(0, 8) + '...)' }}
                                    </option>
                                </select>
                            </div>

                            <button
                                v-if="!scanning"
                                @click="startScanner"
                                class="w-full py-3 bg-namira-teal hover:bg-teal-700 text-white font-extrabold rounded-2xl text-sm uppercase tracking-wider transition-all active:scale-95 shadow-lg shadow-teal-200 flex items-center justify-center gap-2"
                            >
                                <CameraIcon class="w-5 h-5" />
                                Mulai Scan Kamera
                            </button>
                            <button
                                v-else
                                @click="stopScanner"
                                class="w-full py-3 bg-red-500 hover:bg-red-600 text-white font-extrabold rounded-2xl text-sm uppercase tracking-wider transition-all active:scale-95 flex items-center justify-center gap-2"
                            >
                                <XCircleIcon class="w-5 h-5" />
                                Hentikan Scanner
                            </button>

                            <!-- Upload Image Button for Testing -->
                            <div class="pt-1">
                                <input 
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleFileUpload"
                                />
                                <button
                                    type="button"
                                    @click="fileInputRef.click()"
                                    class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition"
                                >
                                    <span>📸 Scan dari File Foto Kartu</span>
                                </button>
                            </div>

                            <!-- Hidden element for file scanning -->
                            <div id="file-qr-reader" class="hidden"></div>
                        </div>
                    </div>

                    <!-- USB Barcode Scanner Gun / Manual Input -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Input Manual / USB Scanner Gun
                        </label>
                        <div class="flex gap-2">
                            <input 
                                v-model="manualNis"
                                @keyup.enter="submitManualNis"
                                type="text"
                                placeholder="Scan Barcode / Ketik NIS (5251564)..."
                                class="flex-1 h-11 px-3 text-sm border-slate-200 rounded-xl focus:ring-teal-500 focus:border-teal-500 font-medium text-slate-800"
                            />
                            <button 
                                @click="submitManualNis"
                                class="px-4 h-11 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm transition"
                            >
                                Submit
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5 font-medium">
                            Mendukung Scanner Gun USB (auto-enter) & Kode Batang / QR Card Humas.
                        </p>
                    </div>

                    <!-- Instructions Card -->
                    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4 text-sm text-teal-800">
                        <h4 class="font-bold mb-2 flex items-center gap-1.5">
                            <ClipboardDocumentCheckIcon class="w-4 h-4 text-teal-700" />
                            Cara Penggunaan:
                        </h4>
                        <ol class="space-y-1 list-decimal list-inside text-xs leading-relaxed">
                            <li>Klik "Mulai Scan Kamera" atau gunakan tombol "Scan dari File Foto Kartu"</li>
                            <li>Arahkan kartu ID siswa (Barcode depan / QR Code belakang)</li>
                            <li>Atau gunakan <b>USB Scanner Gun / Ketik NIS (5251564)</b> pada kotak di atas</li>
                        </ol>
                        <p class="mt-3 text-[11px] text-teal-600 font-medium">
                            Info: QR Code (belakang kartu) & Kode Batang (depan kartu) — keduanya didukung!
                        </p>
                    </div>

                    <!-- Live Debug Console Log -->
                    <div class="bg-slate-900 text-slate-200 rounded-2xl p-4 shadow-sm text-xs font-mono">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-2">
                            <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Log Aktivitas Scanner</span>
                            <button @click="debugLogs = []" class="text-[10px] text-slate-500 hover:text-slate-300">Bersihkan</button>
                        </div>
                        <div class="h-32 overflow-y-auto space-y-1 font-mono text-[11px] leading-tight">
                            <div v-if="debugLogs.length === 0" class="text-slate-600 italic">Belum ada aktivitas. Silakan mulai scanner...</div>
                            <div v-for="(log, idx) in debugLogs" :key="idx" class="flex gap-2">
                                <span class="text-slate-500 select-none">[{{ log.time }}]</span>
                                <span :class="{
                                    'text-emerald-400': log.type === 'success',
                                    'text-amber-400': log.type === 'warn',
                                    'text-rose-400': log.type === 'error',
                                    'text-slate-300': log.type === 'info'
                                }">{{ log.msg }}</span>
                            </div>
                        </div>
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
        </div><!-- END DESKTOP VIEW -->

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
#qr-reader video,
#qr-reader-mobile video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
#qr-reader__scan_region,
#qr-reader-mobile__scan_region {
    min-height: unset !important;
}
#qr-reader__dashboard,
#qr-reader-mobile__dashboard {
    display: none !important;
}

/* Idle corner pulse */
@keyframes idle-pulse {
    0%, 100% { opacity: 0.7; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.08); }
}
.idle-corner { animation: idle-pulse 2s ease-in-out infinite; }

/* Scan-line sweep */
@keyframes scan-sweep {
    0%   { top: 8%; opacity: 1; }
    90%  { top: 90%; opacity: 1; }
    100% { top: 90%; opacity: 0; }
}
.scan-line {
    position: absolute;
    left: 10%; right: 10%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #2dd4bf, #5eead4, #2dd4bf, transparent);
    border-radius: 99px;
    box-shadow: 0 0 10px 3px rgba(45, 212, 191, 0.5);
    animation: scan-sweep 2.2s ease-in-out infinite;
    pointer-events: none;
}

/* ✨ Smart Tracking Frame */
.track-frame {
    position: absolute;
    border: 2.5px solid rgba(255, 255, 255, 0.85);
    border-radius: 10px;
    transition:
        left   0.12s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        top    0.12s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        width  0.12s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        height 0.12s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        border-color 0.1s ease,
        opacity 0.15s ease;
    pointer-events: none;
}
.track-frame.frame-detected {
    border-color: #facc15;
    box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.25), inset 0 0 0 1px rgba(250,204,21,0.1);
}
.track-frame.frame-locked {
    border-color: #4ade80;
    box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.35), inset 0 0 0 1px rgba(74,222,128,0.15);
}

/* Frame corner accents */
.frame-corner {
    position: absolute;
    width: 14px; height: 14px;
    border-color: inherit;
    border-style: solid;
    border-width: 0;
}
.frame-tl { top: -1px; left: -1px; border-top-width: 3px; border-left-width: 3px; border-radius: 4px 0 0 0; }
.frame-tr { top: -1px; right: -1px; border-top-width: 3px; border-right-width: 3px; border-radius: 0 4px 0 0; }
.frame-bl { bottom: -1px; left: -1px; border-bottom-width: 3px; border-left-width: 3px; border-radius: 0 0 0 4px; }
.frame-br { bottom: -1px; right: -1px; border-bottom-width: 3px; border-right-width: 3px; border-radius: 0 0 4px 0; }
</style>
