<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    CameraIcon,
    PhoneIcon,
    EyeIcon,
    EyeSlashIcon,
    InformationCircleIcon,
    DocumentCheckIcon
} from '@heroicons/vue/24/outline';

import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    unit: Object,
    setting: Object,
    totalApplicants: Number,
});

const currentStep = ref(1);
const isCompressing = ref(false);
const showPassword = ref(false);
const paymentPreview = ref(null);

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
    is_inden: true,
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
    city: 'Kota Probolinggo',
    postal_code: '',

    // Step 2: Unggah Bukti Pembayaran Inden (Sudah Bayar)
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
            form.previous_school = 'TK Namira';
        }
    } else if (val === 'eksternal_umum') {
        if (form.previous_school === 'TK Namira') {
            form.previous_school = '';
        }
    }
};

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

const submitErrorBanner = ref('');

const submitRegistration = () => {
    submitErrorBanner.value = '';
    if (!form.registration_payment_proof) {
        alert('Mohon lampirkan foto kuitansi, struk transfer, atau bukti pembayaran pendaftaran inden Anda.');
        return;
    }

    const removeExceptionListener = router.on('exception', (event) => {
        event.preventDefault();
        submitErrorBanner.value = 'Koneksi internet terputus atau jaringan sedang tidak stabil. Mohon periksa sinyal internet/Wi-Fi Anda lalu klik tombol "Kirim Pendaftaran & Masuk Portal" sekali lagi.';
        alert(submitErrorBanner.value);
    });

    form.post(route('spmb.store.sd-kota'), {
        forceFormData: true,
        preserveScroll: false,
        onError: (errors) => {
            const errorKeys = Object.keys(errors || {});
            const hasStep1Error = errorKeys.some((k) => k !== 'registration_payment_proof');
            if (hasStep1Error) {
                currentStep.value = 1;
            }
            const firstMessage = errorKeys.length > 0 ? errors[errorKeys[0]] : 'Mohon periksa kembali isian formulir Anda.';
            submitErrorBanner.value = firstMessage;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onFinish: () => {
            removeExceptionListener();
        },
    });
};
</script>

<template>
    <Head title="Formulir Khusus Pendaftar Inden - SD Namira Kota Probolinggo" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Institutional Header Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <Link :href="route('login')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-emerald-800 transition">
                    <span>Sudah Punya Akun? Login Portal</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <img 
                        src="/images/landing/logo-sd.webp" 
                        alt="Logo SD Namira Kota Probolinggo" 
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain" 
                        onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';" 
                    />
                    <div class="text-right">
                        <span class="text-[10px] text-emerald-700 font-bold uppercase block leading-tight">Jalur Khusus Inden (SD-02)</span>
                        <strong class="text-xs sm:text-sm font-black text-slate-900 block leading-tight">SD Namira Kota Probolinggo</strong>
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
                            alt="Logo SD Namira Kota Probolinggo" 
                            class="w-full h-full object-contain" 
                            onerror="this.onerror=null; this.src='/images/landing/logo-sd.png';" 
                        />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs mb-1.5">
                            <span class="font-bold text-emerald-900 tracking-wide uppercase">SD Namira Kota Probolinggo</span>
                            <span class="text-slate-300 hidden sm:inline">&bull;</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-50 text-amber-800 text-[11px] font-bold border border-amber-300/80">
                                Link Khusus Pendaftar Inden (SD-02)
                            </span>
                            <span class="text-slate-500 text-xs font-semibold">
                                TA {{ setting?.academic_year || '2027/2028' }}
                            </span>
                        </div>
                        
                        <h1 class="text-lg sm:text-2xl font-black text-slate-900 leading-tight">
                            Formulir Kelanjutan Pendaftaran Inden
                        </h1>
                        
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Khusus bagi Bapak/Ibu Orang Tua calon murid baru <strong>SD Namira Kota Probolinggo</strong> yang telah terdaftar inden dan melakukan pembayaran. Silakan lengkapi biodata dan unggah bukti bayar untuk masuk ke Portal Calon Siswa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress (2 Steps: Biodata + Unggah Bukti Bayar) -->
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
                        <span class="truncate">2. Unggah Bukti Bayar</span>
                    </button>
                </div>
            </div>

            <!-- Flash / Network Error Alert -->
            <div v-if="$page.props.flash?.error || submitErrorBanner" class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-950 font-semibold">
                {{ $page.props.flash?.error || submitErrorBanner }}
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
                        <strong class="font-bold text-emerald-900 block mb-0.5">Informasi Pemberkasan &amp; Tahapan Seleksi:</strong>
                        <p class="text-emerald-800 leading-relaxed text-[11px] sm:text-xs">
                            Pada halaman ini Bapak/Ibu cukup mengisi <strong>Biodata Calon Murid</strong> dan melampirkan <strong>Bukti Pembayaran Inden</strong> yang telah dilakukan. Dokumen persyaratan (Pas Foto 3x4, Kartu Keluarga, Akta Kelahiran, dan KTP Orang Tua) dapat diunggah menyusul secara fleksibel setelah masuk ke Portal Calon Siswa.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Data Calon Murid -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Calon Murid SD Namira Kota Probolinggo</h2>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">* Kolom wajib</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        
                        <!-- Kategori Asal Sekolah -->
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
                            <label class="block font-bold text-slate-700 mb-1">NIK Anak (16 Digit - Opsional)</label>
                            <input 
                                v-model="form.nik" 
                                type="text" 
                                inputmode="numeric"
                                maxlength="16"
                                placeholder="3513xxxxxxxxxxxx"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs font-mono focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Asal TK / RA / KB</label>
                            <input 
                                v-model="form.previous_school" 
                                type="text" 
                                placeholder="Contoh: TK Namira Dringu"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Anak Ke-</label>
                            <input 
                                v-model="form.child_order" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dari Jumlah Bersaudara</label>
                            <input 
                                v-model="form.total_siblings" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Catatan Khusus Kesehatan / Alergi / Kebutuhan Anak (Jika Ada)</label>
                            <input 
                                v-model="form.special_notes" 
                                type="text" 
                                placeholder="Kosongkan jika tidak ada"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Data Orang Tua / Wali -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Bagian 2</span>
                        <h2 class="text-sm font-bold text-slate-900">Data Orang Tua / Wali Murid</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ayah *</label>
                            <input 
                                v-model="form.father_name" 
                                type="text" 
                                placeholder="Nama lengkap Ayah kandung/wali"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
                            <input 
                                v-model="form.father_job" 
                                type="text" 
                                placeholder="Contoh: Wiraswasta / ASN / Karyawan Swasta"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ibu *</label>
                            <input 
                                v-model="form.mother_name" 
                                type="text" 
                                placeholder="Nama lengkap Ibu kandung/wali"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ibu</label>
                            <input 
                                v-model="form.mother_job" 
                                type="text" 
                                placeholder="Contoh: Ibu Rumah Tangga / Guru / Wiraswasta"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bagian 3: Akun Portal & Alamat Domisili -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Bagian 3</span>
                        <h2 class="text-sm font-bold text-slate-900">Akun Login Portal &amp; Alamat Domisili</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Nomor WhatsApp dan Kata Sandi ini digunakan untuk login ke Portal Calon Siswa (unggah berkas, cetak kartu peserta, jadwal observasi, &amp; hasil seleksi).
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Utama (Sebagai Username Login) *</label>
                            <div class="relative">
                                <PhoneIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                                <input 
                                    v-model="form.parent_phone" 
                                    type="tel" 
                                    inputmode="tel"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Buat Kata Sandi Akun *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    placeholder="Minimal 6 karakter"
                                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:outline-none transition"
                                    required
                                />
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                                >
                                    <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                    <EyeIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Ulangi Kata Sandi *</label>
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
                        <span>Lanjut ke Langkah 2 (Unggah Bukti Bayar)</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                    </button>
                </div>

            </div>

            <!-- ================= STEP 2: UNGGAH BUKTI PEMBAYARAN INDEN ================= -->
            <div v-show="currentStep === 2" class="space-y-4 sm:space-y-5">
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Unggah Bukti Pembayaran Pendaftaran Inden</h2>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold text-[11px] border border-emerald-200">
                            SD Namira Kota (SD-02)
                        </span>
                    </div>

                    <!-- Inden Notice Card (No QRIS needed, just upload existing payment receipt) -->
                    <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-start gap-3 text-xs text-emerald-950">
                        <DocumentCheckIcon class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <strong class="font-bold block text-emerald-900">Konfirmasi Bukti Pembayaran Pendaftar Inden:</strong>
                            <p class="text-[11px] sm:text-xs text-emerald-800 leading-relaxed">
                                Karena Bapak/Ibu telah terdaftar pada jalur inden <strong>SD Namira Kota Probolinggo</strong> dan telah melakukan pembayaran sebelumnya, Bapak/Ibu <strong>tidak perlu melakukan pembayaran ulang</strong>. Cukup unggah foto kuitansi, struk transfer, atau <em>screenshot</em> bukti pembayaran pendaftaran Anda di bawah ini.
                            </p>
                        </div>
                    </div>

                    <!-- Upload Box (With Clean Thumbnail Preview) -->
                    <div class="space-y-2.5 pt-1">
                        <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                            Lampiran Foto Kuitansi / Bukti Transfer Pendaftaran Inden *
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Format file yang didukung: Foto (JPG, PNG) atau dokumen PDF.
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
                                
                                <label for="sd_kota_payment_proof_input" class="inline-block mt-2.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition shadow-2xs">
                                    Ganti File Bukti Bayar
                                </label>
                            </div>
                        </div>

                        <!-- Dropzone Empty State -->
                        <div v-else class="border-2 border-dashed border-slate-300 rounded-xl p-6 sm:p-8 text-center hover:border-emerald-600 transition bg-slate-50/50">
                            <label for="sd_kota_payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-2.5">
                                    <CameraIcon class="w-5 h-5" />
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-emerald-800 hover:underline">
                                    Pilih Foto Kuitansi / Bukti Pembayaran Inden
                                </span>
                                <span class="text-[11px] text-slate-400 mt-1">
                                    Maksimal ukuran file 10MB (JPG, PNG, PDF)
                                </span>
                            </label>
                        </div>

                        <input 
                            type="file" 
                            id="sd_kota_payment_proof_input"
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

                <!-- Inline Error Feedback Right Above Submit Button (Visible Immediately on Mobile) -->
                <div v-if="$page.props.flash?.error || submitErrorBanner || form.errors.registration_payment_proof" class="p-3.5 rounded-xl bg-rose-50 border border-rose-300 text-xs text-rose-900 font-semibold">
                    {{ form.errors.registration_payment_proof || $page.props.flash?.error || submitErrorBanner }}
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

    </div>
</template>
