<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    CameraIcon,
    QrCodeIcon,
    PhoneIcon,
    EyeIcon,
    EyeSlashIcon,
    ClipboardDocumentCheckIcon,
    ClipboardIcon,
    InformationCircleIcon,
    ArrowDownTrayIcon,
    MagnifyingGlassPlusIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    unit: Object,
    setting: Object,
    totalApplicants: Number,
});

const currentStep = ref(1);
const isCompressing = ref(false);
const isCopied = ref(false);
const showPassword = ref(false);
const paymentPreview = ref(null);
const showQrisModal = ref(false);

const qrisImageUrl = computed(() => {
    return props.setting?.qris_image_url || '/images/spmb/qris-sd-namira.jpg';
});

const form = useForm({
    // Step 1: Biodata Calon Murid
    full_name: '',
    nickname: '',
    gender: 'L',
    nik: '',
    nisn: '',
    birth_place: '',
    birth_date: '',
    religion: 'Islam',
    child_order: 1,
    total_siblings: 1,
    is_inden: false,
    category: 'eksternal_umum',
    previous_school: '',
    special_notes: '',

    // Data Orang Tua / Wali
    father_name: '',
    father_nik: '',
    father_phone: '',
    father_job: '',
    father_education: '',

    mother_name: '',
    mother_nik: '',
    mother_phone: '',
    mother_job: '',
    mother_education: '',

    parent_phone: '',
    password: '',
    password_confirmation: '',

    address: '',
    rt: '',
    rw: '',
    village: '',
    district: '',
    city: 'Probolinggo',
    postal_code: '',

    // Step 2: Bukti Pembayaran Pendaftaran (Wajib Fase Awal)
    registration_payment_proof: null,
});

const fileNamePaymentProof = ref('');

const onPaymentProofChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.type && file.type.startsWith('image/')) {
        try {
            isCompressing.value = true;
            const compressed = await compressImageFile(file, 1280, 0.75);
            form.registration_payment_proof = compressed;
            fileNamePaymentProof.value = compressed.name;
            paymentPreview.value = URL.createObjectURL(compressed);
        } catch (err) {
            console.error('Kompresi bukti pembayaran gagal:', err);
            form.registration_payment_proof = file;
            fileNamePaymentProof.value = file.name;
            paymentPreview.value = URL.createObjectURL(file);
        } finally {
            isCompressing.value = false;
        }
    } else {
        form.registration_payment_proof = file;
        fileNamePaymentProof.value = file.name;
        paymentPreview.value = null;
    }
};

const onCategoryChange = (val) => {
    if (val === 'internal_tk') {
        if (!form.previous_school || form.previous_school === '') {
            form.previous_school = 'TK Namira Kota Probolinggo';
        }
    } else if (val === 'eksternal_umum') {
        if (form.previous_school === 'TK Namira Kota Probolinggo') {
            form.previous_school = '';
        }
    }
};

const copyAccountNumber = () => {
    const acc = props.setting?.bank_account_number || '0291008899';
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(acc).then(() => {
            isCopied.value = true;
            setTimeout(() => { isCopied.value = false; }, 2500);
        });
    }
};

const formattedFee = computed(() => {
    const fee = props.setting?.registration_fee || 250000;
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(fee);
});

const validateStep1 = () => {
    if (!form.full_name || !form.birth_place || !form.birth_date || !form.gender) {
        alert('Mohon lengkapi data wajib calon murid (Nama Lengkap, Tempat & Tanggal Lahir, Jenis Kelamin).');
        return false;
    }
    if (!form.father_name && !form.mother_name) {
        alert('Mohon isi nama orang tua (Ayah atau Ibu).');
        return false;
    }
    if (!form.parent_phone) {
        alert('Mohon isi Nomor WhatsApp aktif sebagai akun login Anda.');
        return false;
    }
    if (!form.password || form.password.length < 6) {
        alert('Kata sandi akun minimal 6 karakter.');
        return false;
    }
    if (form.password !== form.password_confirmation) {
        alert('Konfirmasi kata sandi tidak cocok.');
        return false;
    }
    if (!form.address) {
        alert('Mohon isi alamat domisili tempat tinggal.');
        return false;
    }
    return true;
};

const goToStep2 = () => {
    if (validateStep1()) {
        currentStep.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitRegistration = () => {
    if (!form.registration_payment_proof) {
        alert('Mohon lampirkan foto struk transfer atau screenshot bukti pembayaran QRIS pendaftaran.');
        return;
    }

    form.post(route('spmb.store.sd'), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.parent_phone) {
                currentStep.value = 1;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};
</script>

<template>
    <Head title="Formulir Pendaftaran Siswa Baru - SD Namira Kota Probolinggo" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Institutional Header Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-emerald-800 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Pilihan Unit SPMB</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <img 
                        src="/images/landing/logo-sd.webp" 
                        alt="Logo SD Namira" 
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain" 
                        onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';" 
                    />
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 font-medium block leading-tight">SPMB SD Namira</span>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 block leading-tight">Kota Probolinggo</strong>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Form Container -->
        <div class="max-w-4xl mx-auto px-3.5 sm:px-4 pt-4 sm:pt-6">
            
            <!-- Institutional Title Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 mb-4 sm:mb-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-3.5 sm:gap-5 text-center sm:text-left">
                    <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-xl bg-emerald-50/60 border border-emerald-200/80 p-2 flex items-center justify-center shrink-0">
                        <img 
                            src="/images/landing/logo-sd.webp" 
                            alt="Logo SD Namira" 
                            class="w-full h-full object-contain" 
                            onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';" 
                        />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs mb-1.5">
                            <span class="font-bold text-emerald-900 tracking-wide uppercase">SD Namira Kota Probolinggo</span>
                            <span class="text-slate-300 hidden sm:inline">&bull;</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 text-[11px] font-semibold border border-emerald-200/60">
                                Gelombang Umum &amp; Inden
                            </span>
                            <span class="text-slate-500 text-xs">
                                TA {{ setting?.academic_year || '2026/2027' }}
                            </span>
                        </div>
                        
                        <h1 class="text-lg sm:text-2xl font-black text-slate-900 leading-tight">
                            Formulir Pendaftaran Calon Murid Baru
                        </h1>
                        
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Lengkapi biodata calon murid dan konfirmasi pembayaran formulir. Berkas dokumen persyaratan dapat diunggah menyusul secara fleksibel di Portal Calon Siswa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress (2 Steps, Clean & Mobile Friendly) -->
            <div class="bg-white border border-slate-200 rounded-xl p-2.5 sm:p-3 mb-4 sm:mb-5 shadow-2xs">
                <div class="grid grid-cols-2 gap-2 max-w-md mx-auto">
                    <!-- Step 1 Button -->
                    <button 
                        type="button" 
                        class="flex items-center justify-center gap-2 py-2 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-bold transition select-none"
                        :class="currentStep === 1 
                            ? 'bg-emerald-50 border border-emerald-600 text-emerald-950 shadow-2xs' 
                            : 'bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900'"
                        @click="currentStep = 1"
                    >
                        <span 
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                            :class="currentStep >= 1 ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 1" class="w-3.5 h-3.5" />
                            <span v-else>1</span>
                        </span>
                        <span class="truncate">1. Biodata Murid</span>
                    </button>

                    <!-- Step 2 Button -->
                    <button 
                        type="button" 
                        class="flex items-center justify-center gap-2 py-2 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-bold transition select-none"
                        :class="currentStep === 2 
                            ? 'bg-emerald-50 border border-emerald-600 text-emerald-950 shadow-2xs' 
                            : 'bg-slate-50 border border-slate-200 text-slate-500 hover:text-slate-800'"
                        @click="goToStep2"
                    >
                        <span 
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                            :class="currentStep === 2 ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            2
                        </span>
                        <span class="truncate">2. QRIS &amp; Bukti Bayar</span>
                    </button>
                </div>
            </div>

            <!-- General Alert / Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <strong class="block font-bold">Mohon periksa kembali kolom formulir berikut:</strong>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- ================= STEP 1: BIODATA ================= -->
            <div v-show="currentStep === 1" class="space-y-4 sm:space-y-5">
                
                <!-- Notice Banner: Deferred Supporting Documents -->
                <div class="bg-emerald-50/80 border border-emerald-200 rounded-xl p-3.5 sm:p-4 flex items-start gap-3 text-xs text-emerald-950">
                    <InformationCircleIcon class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-bold text-emerald-900 block mb-0.5">Kemudahan Pendaftaran (Unggah Berkas Fleksibel):</strong>
                        <p class="text-emerald-800 leading-relaxed text-[11px] sm:text-xs">
                            Pada tahap ini Anda cukup mengisi <strong>Biodata</strong> dan mengunggah <strong>Bukti Pembayaran Pendaftaran</strong>. Dokumen persyaratan (Pas Foto 3x4, Kartu Keluarga, Akta Kelahiran, dan KTP Orang Tua) <strong>dapat diunggah menyusul kapan saja</strong> setelah Anda masuk ke Portal Calon Siswa.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Data Calon Murid -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Calon Murid SD Namira</h2>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">* Kolom wajib</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        
                        <!-- Pilihan Jalur Pendaftaran (Umum vs Inden) -->
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-2">Status Pendaftaran *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                                <label 
                                    class="flex items-start gap-3 p-3 sm:p-3.5 rounded-xl border cursor-pointer transition select-none"
                                    :class="!form.is_inden 
                                        ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-500' 
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input 
                                        type="radio" 
                                        name="is_inden" 
                                        :value="false" 
                                        v-model="form.is_inden" 
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Pendaftar Baru (Jalur Umum)</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Pendaftaran reguler siswa baru Tahun Ajaran {{ setting?.academic_year || '2026/2027' }}</div>
                                    </div>
                                </label>

                                <label 
                                    class="flex items-start gap-3 p-3 sm:p-3.5 rounded-xl border cursor-pointer transition select-none"
                                    :class="form.is_inden 
                                        ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-500' 
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input 
                                        type="radio" 
                                        name="is_inden" 
                                        :value="true" 
                                        v-model="form.is_inden" 
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Terdaftar di List Inden</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Pilih ini jika nama ananda/orang tua sudah tercatat di daftar Inden sebelumnya</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Kategori Asal Sekolah (Internal TK Namira vs Eksternal) -->
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-2">Kategori Asal Sekolah *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                                <label 
                                    class="flex items-start gap-3 p-3 sm:p-3.5 rounded-xl border cursor-pointer transition select-none"
                                    :class="form.category === 'internal_tk' 
                                        ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-500' 
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input 
                                        type="radio" 
                                        name="category" 
                                        value="internal_tk" 
                                        v-model="form.category" 
                                        @change="onCategoryChange('internal_tk')"
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Alumni TK / KB Namira</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Calon murid lulusan internal TK / KB Namira</div>
                                    </div>
                                </label>

                                <label 
                                    class="flex items-start gap-3 p-3 sm:p-3.5 rounded-xl border cursor-pointer transition select-none"
                                    :class="form.category === 'eksternal_umum' 
                                        ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-500' 
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input 
                                        type="radio" 
                                        name="category" 
                                        value="eksternal_umum" 
                                        v-model="form.category" 
                                        @change="onCategoryChange('eksternal_umum')"
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Luar TK Namira (Umum)</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Calon murid dari TK / RA umum lainnya</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Calon Murid *</label>
                            <input 
                                v-model="form.full_name" 
                                type="text" 
                                autocomplete="name"
                                placeholder="Sesuai Akta Kelahiran / Kartu Keluarga"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Panggilan</label>
                            <input 
                                v-model="form.nickname" 
                                type="text" 
                                placeholder="Contoh: Zaki"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <!-- Gender Selector -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label 
                                    class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none text-center"
                                    :class="form.gender === 'L' 
                                        ? 'bg-emerald-700 border-emerald-700 text-white shadow-xs' 
                                        : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.gender" value="L" class="hidden" />
                                    <span>Laki-laki</span>
                                </label>
                                <label 
                                    class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none text-center"
                                    :class="form.gender === 'P' 
                                        ? 'bg-emerald-700 border-emerald-700 text-white shadow-xs' 
                                        : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.gender" value="P" class="hidden" />
                                    <span>Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempat Lahir *</label>
                            <input 
                                v-model="form.birth_place" 
                                type="text" 
                                placeholder="Contoh: Probolinggo"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir *</label>
                            <input 
                                v-model="form.birth_date" 
                                type="date" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NIK Calon Murid (16 Digit)</label>
                            <input 
                                v-model="form.nik" 
                                type="tel" 
                                inputmode="numeric"
                                maxlength="16"
                                placeholder="Sesuai Kartu Keluarga (Opsional)"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Asal Sekolah TK / RA / KB</label>
                            <input 
                                v-model="form.previous_school" 
                                type="text" 
                                placeholder="Contoh: TK Namira / TK ABA 1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Anak ke-</label>
                            <input 
                                v-model="form.child_order" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dari jumlah bersaudara</label>
                            <input 
                                v-model="form.total_siblings" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Catatan Khusus / Riwayat Kesehatan / Tumbuh Kembang (Opsional)</label>
                            <textarea 
                                v-model="form.special_notes" 
                                rows="2"
                                placeholder="Misal: Alergi makanan tertentu, catatan khusus pendampingan, dll."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Data Orang Tua & Akun Login -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Bagian 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Orang Tua &amp; Akun Portal Calon Siswa</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ayah</label>
                            <input 
                                v-model="form.father_name" 
                                type="text" 
                                placeholder="Nama lengkap Ayah kandung"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
                            <input 
                                v-model="form.father_job" 
                                type="text" 
                                placeholder="Contoh: Pegawai / Wiraswasta"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ibu</label>
                            <input 
                                v-model="form.mother_name" 
                                type="text" 
                                placeholder="Nama lengkap Ibu kandung"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ibu</label>
                            <input 
                                v-model="form.mother_job" 
                                type="text" 
                                placeholder="Contoh: Ibu Rumah Tangga / Guru"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <!-- WhatsApp Login Account -->
                        <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                            <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Aktif (Username Login Portal) *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.parent_phone" 
                                    type="tel" 
                                    inputmode="tel"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition font-mono"
                                    required
                                />
                                <PhoneIcon class="w-4 h-4 text-slate-400 absolute right-3.5 top-3" />
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Nomor WhatsApp ini digunakan sebagai akun login Anda untuk melengkapi berkas, memantau jadwal observasi/psikotes, dan melihat pengumuman.
                            </p>
                        </div>

                        <!-- Password with Toggle Visibility -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kata Sandi Akun (Password) *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    placeholder="Minimal 6 karakter"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                    required
                                />
                                <button 
                                    type="button" 
                                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                    <EyeIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                            <input 
                                v-model="form.password_confirmation" 
                                :type="showPassword ? 'text' : 'password'" 
                                placeholder="Ketik ulang kata sandi"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal Lengkap *</label>
                            <textarea 
                                v-model="form.address" 
                                rows="2"
                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                required
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Navigation Action -->
                <div class="pt-2">
                    <button 
                        type="button" 
                        @click="goToStep2"
                        class="w-full sm:w-auto sm:ml-auto flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold py-3.5 px-7 rounded-xl text-xs sm:text-sm transition shadow-2xs"
                    >
                        <span>Lanjut ke Langkah 2 (QRIS &amp; Pembayaran)</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                    </button>
                </div>

            </div>

            <!-- ================= STEP 2: QRIS & PEMBAYARAN BUKTI DAFTAR ================= -->
            <div v-show="currentStep === 2" class="space-y-4 sm:space-y-5">
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Pembayaran Biaya Pendaftaran ({{ formattedFee }})</h2>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-black text-xs border border-emerald-200">
                            {{ formattedFee }}
                        </span>
                    </div>

                    <!-- Responsive Grid: QRIS Card + Bank Transfer Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 items-start">
                        
                        <!-- 1. Official QRIS Card (Bank Jatim JConnect) -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 sm:p-5 text-center space-y-3">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-[10px] font-extrabold uppercase tracking-wider">
                                <QrCodeIcon class="w-3.5 h-3.5 text-emerald-700" />
                                <span>QRIS Resmi Bank Jatim</span>
                            </div>

                            <div>
                                <h3 class="text-sm font-black text-slate-900">SD NAMIRA PENDAFTARAN</h3>
                                <p class="text-[11px] font-mono text-slate-500 mt-0.5">NMID : ID1026552922373 &bull; A01</p>
                            </div>

                            <!-- QRIS Image Container (Clickable to Enlarge) -->
                            <div 
                                class="relative group max-w-[260px] mx-auto bg-white rounded-xl border border-slate-300 p-2 shadow-xs cursor-pointer overflow-hidden"
                                @click="showQrisModal = true"
                            >
                                <img 
                                    :src="qrisImageUrl" 
                                    alt="QRIS SD Namira Pendaftaran" 
                                    class="w-full h-auto object-contain rounded-lg"
                                />
                                <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-center gap-1 text-[11px] font-bold text-emerald-700">
                                    <MagnifyingGlassPlusIcon class="w-4 h-4" />
                                    <span>Ketuk untuk Memperbesar QRIS</span>
                                </div>
                            </div>

                            <!-- Quick Download QRIS Button for Mobile Banking Users -->
                            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                                <a 
                                    :href="qrisImageUrl" 
                                    download="QRIS-SD-NAMIRA-PENDAFTARAN.jpg"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold transition shadow-2xs"
                                >
                                    <ArrowDownTrayIcon class="w-3.5 h-3.5 text-emerald-700" />
                                    <span>Simpan Gambar QRIS ke HP</span>
                                </a>
                            </div>

                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Bisa di-scan dari seluruh Mobile Banking (Bank Jatim, BCA, Mandiri, BRI, BNI, BSI) &amp; E-Wallet (GoPay, OVO, Dana, ShopeePay).
                            </p>
                        </div>

                        <!-- 2. Alternative Bank Transfer & Instructions -->
                        <div class="space-y-4 text-xs">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">
                                    Atau Transfer Rekening Bank:
                                </span>

                                <div class="space-y-2 text-slate-700">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Bank Tujuan:</span>
                                        <strong class="text-slate-900 font-bold">{{ setting?.bank_name || 'Bank Jatim' }}</strong>
                                    </div>

                                    <!-- Interactive Copy Account Number -->
                                    <div class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-slate-200">
                                        <div>
                                            <span class="text-[10px] text-slate-400 font-bold block uppercase">Nomor Rekening:</span>
                                            <strong class="font-mono text-sm sm:text-base text-emerald-900 font-bold">
                                                {{ setting?.bank_account_number || '0291008899' }}
                                            </strong>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="copyAccountNumber"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition"
                                            :class="isCopied ? 'bg-emerald-600 text-white' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800'"
                                        >
                                            <ClipboardDocumentCheckIcon v-if="isCopied" class="w-3.5 h-3.5" />
                                            <ClipboardIcon v-else class="w-3.5 h-3.5" />
                                            <span>{{ isCopied ? 'Tersalin!' : 'Salin Rekening' }}</span>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Atas Nama:</span>
                                        <strong class="text-slate-900 font-bold text-right">{{ setting?.bank_account_holder || 'NAMIRA SCHOOL KOTA PROBOLINGGO' }}</strong>
                                    </div>

                                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/80">
                                        <span class="text-slate-500 font-medium">Nominal Biaya Pendaftaran:</span>
                                        <strong class="text-emerald-800 font-black text-sm">{{ formattedFee }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Guide Box -->
                            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-950 space-y-1.5">
                                <strong class="font-bold block text-xs">Panduan Cepat Pembayaran via HP:</strong>
                                <ol class="list-decimal pl-4 space-y-1 text-[11px] text-slate-700 leading-relaxed">
                                    <li>Klik tombol <strong>Simpan Gambar QRIS ke HP</strong> di samping (atau salin nomor rekening Bank Jatim).</li>
                                    <li>Buka aplikasi m-Banking / E-Wallet Anda, pilih menu <strong>QRIS</strong> lalu ambil gambar dari galeri.</li>
                                    <li>Pastikan nama tujuan adalah <strong>SD NAMIRA PENDAFTARAN</strong> dan masukkan nominal <strong>{{ formattedFee }}</strong>.</li>
                                    <li>Simpan <em>screenshot</em> bukti pembayaran berhasil, lalu unggah pada kotak di bawah ini.</li>
                                </ol>
                            </div>
                        </div>

                    </div>

                    <!-- Upload Box (With Clean Thumbnail Preview) -->
                    <div class="space-y-2.5 pt-2 border-t border-slate-100">
                        <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                            Unggah Bukti Pembayaran QRIS / Struk Transfer *
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Lampirkan screenshot pembayaran QRIS, bukti m-Banking, atau foto struk ATM (JPG, PNG, atau PDF).
                        </p>

                        <!-- Selected Preview Card -->
                        <div v-if="paymentPreview || fileNamePaymentProof" class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 flex flex-col sm:flex-row items-center gap-4">
                            <img v-if="paymentPreview" :src="paymentPreview" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg border border-slate-300 shadow-2xs shrink-0 bg-white" alt="Pratinjau Bukti Bayar" />
                            <div class="flex-1 text-center sm:text-left">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold mb-1">
                                    <CheckCircleIcon class="w-3 h-3" /> Bukti Pembayaran Siap Dikirim
                                </span>
                                <strong class="block text-xs font-mono text-slate-900 break-all">{{ fileNamePaymentProof }}</strong>
                                <p class="text-[10px] text-slate-500 mt-1">Ukuran berkas telah dioptimasi otomatis agar cepat terkirim.</p>
                                
                                <label for="sd_payment_proof_input" class="inline-block mt-2.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition shadow-2xs">
                                    Ganti File Bukti Bayar
                                </label>
                            </div>
                        </div>

                        <!-- Dropzone Empty State -->
                        <div v-else class="border-2 border-dashed border-slate-300 rounded-xl p-6 sm:p-8 text-center hover:border-emerald-600 transition bg-slate-50/50">
                            <label for="sd_payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-2.5">
                                    <CameraIcon class="w-5 h-5" />
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-emerald-800 hover:underline">
                                    Pilih Screenshot QRIS atau Foto Struk Transfer
                                </span>
                                <span class="text-[11px] text-slate-400 mt-1">
                                    Maksimal ukuran file 10MB (JPG, PNG, PDF)
                                </span>
                            </label>
                        </div>

                        <input 
                            type="file" 
                            id="sd_payment_proof_input"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="hidden"
                            @change="onPaymentProofChange"
                        />

                        <div v-if="isCompressing" class="text-center py-2 text-xs font-bold text-emerald-700 flex items-center justify-center gap-2">
                            <span class="w-3 h-3 rounded-full border-2 border-emerald-600 border-t-transparent animate-spin"></span>
                            <span>Mengoptimasi gambar bukti pembayaran...</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="currentStep = 1"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold py-3 px-5 rounded-xl text-xs transition border border-slate-300"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Biodata</span>
                    </button>

                    <button 
                        type="button" 
                        :disabled="form.processing || isCompressing"
                        @click="submitRegistration"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold py-3.5 px-8 rounded-xl text-xs sm:text-sm transition shadow-2xs disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : 'Kirim Pendaftaran & Masuk Portal' }}</span>
                    </button>
                </div>

            </div>

        </div>

        <!-- Modal Perbesar QRIS -->
        <div v-if="showQrisModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs" @click.self="showQrisModal = false">
            <div class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-5 shadow-2xl space-y-3 text-center relative">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="text-left">
                        <strong class="text-xs sm:text-sm font-black text-slate-900 block">QRIS SD NAMIRA PENDAFTARAN</strong>
                        <span class="text-[11px] font-mono text-slate-500">NMID : ID1026552922373</span>
                    </div>
                    <button type="button" @click="showQrisModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="bg-slate-50 p-2 rounded-xl border border-slate-200">
                    <img :src="qrisImageUrl" alt="QRIS SD Namira Pendaftaran" class="w-full h-auto max-h-[70vh] object-contain mx-auto rounded-lg" />
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <a 
                        :href="qrisImageUrl" 
                        download="QRIS-SD-NAMIRA-PENDAFTARAN.jpg"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold transition"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4 text-amber-300" />
                        <span>Simpan QRIS</span>
                    </a>
                    <button 
                        type="button" 
                        @click="showQrisModal = false"
                        class="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
