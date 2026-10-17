<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    ArrowLeftIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    CameraIcon,
    DocumentTextIcon,
    QrCodeIcon,
    PhoneIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    ClipboardDocumentCheckIcon,
    ClipboardIcon,
    UserIcon,
    HomeIcon,
    SparklesIcon,
    InformationCircleIcon
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

const form = useForm({
    // Step 1: Biodata Santri
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

const copyAccountNumber = () => {
    const acc = props.setting?.bank_account_number || '0291008899';
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(acc).then(() => {
            isCopied.value = true;
            setTimeout(() => { isCopied.value = false; }, 2500);
        });
    }
};

const validateStep1 = () => {
    if (!form.full_name || !form.birth_place || !form.birth_date || !form.gender) {
        alert('Mohon lengkapi data wajib calon santri (Nama Lengkap, Tempat & Tanggal Lahir, Jenis Kelamin).');
        return false;
    }
    if (!form.previous_school) {
        alert('Mohon isi Asal Sekolah Dasar (SD/MI).');
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
        alert('Mohon lampirkan foto struk transfer atau bukti bayar QRIS pendaftaran.');
        return;
    }

    form.post(route('spmb.store.smp'), {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Formulir Pendaftaran Siswa Baru - SMP Namira Kota Probolinggo" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-blue-100 selection:text-blue-900">
        
        <!-- Institutional Sticky Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-4xl mx-auto px-4 py-2.5 sm:py-3 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-blue-700 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Pilihan Unit SPMB</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <img 
                        src="/images/landing/logo-smp.webp" 
                        alt="Logo SMP Namira" 
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-xs" 
                        onerror="this.onerror=null; this.src='/images/landing/logo-smp.png';" 
                    />
                    <div class="text-right">
                        <span class="text-[9px] sm:text-[10px] text-blue-700 font-extrabold uppercase tracking-wider block leading-tight">SPMB Inden 2026/2027</span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 block leading-tight">SMP NAMIRA PROBOLINGGO</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Form Wrapper -->
        <div class="max-w-4xl mx-auto px-3.5 sm:px-4 pt-4 sm:pt-6">
            
            <!-- Hero Title Card with Prominent SMP Logo -->
            <div class="bg-gradient-to-br from-[#0f2757] via-[#1e3a8a] to-[#1e1b4b] text-white rounded-3xl p-5 sm:p-7 shadow-md border border-blue-800/40 mb-5 relative overflow-hidden">
                <!-- Background Accent Circles -->
                <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-blue-500/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-1/3 -top-10 w-32 h-32 rounded-full bg-amber-400/10 blur-xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 relative z-10 text-center sm:text-left">
                    <!-- Prominent Logo Box -->
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white p-2 border-2 border-blue-200/40 flex items-center justify-center shrink-0 shadow-md">
                        <img 
                            src="/images/landing/logo-smp.webp" 
                            alt="Logo Resmi SMP Namira" 
                            class="w-full h-full object-contain filter drop-shadow-xs"
                            onerror="this.onerror=null; this.src='/images/landing/logo-smp.png';"
                        />
                    </div>

                    <div class="flex-1">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-900/80 border border-blue-400/30 text-amber-300 text-[11px] font-bold mb-2 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Jalur Inden TA {{ setting?.academic_year || '2026/2027' }}</span>
                        </div>
                        <h1 class="text-lg sm:text-2xl font-black text-white leading-tight">
                            Formulir Pendaftaran Calon Murid
                        </h1>
                        <p class="text-xs sm:text-[13px] text-blue-100/90 mt-1.5 leading-relaxed max-w-xl">
                            Khusus calon murid yang sudah tercatat pada data inden SMP Namira Kota Probolinggo.
                        </p>
                    </div>

                    <!-- Fee Badge -->
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-4 py-2.5 text-center sm:text-right shrink-0 w-full sm:w-auto">
                        <span class="text-[10px] uppercase font-bold text-blue-200 block tracking-wider">Biaya Formulir</span>
                        <span class="text-base sm:text-lg font-black text-amber-300 block">
                            {{ setting?.registration_fee ? formatRupiah(setting.registration_fee) : 'Rp 250.000' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress Bar (Mobile Friendly) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-2.5 sm:p-4 mb-5 shadow-xs">
                <div class="grid grid-cols-2 gap-2 sm:gap-4">
                    
                    <!-- Step 1 Tab -->
                    <div 
                        class="flex items-center gap-2.5 p-2 sm:p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                        :class="currentStep === 1 
                            ? 'bg-blue-50 border-blue-600 text-blue-950 shadow-xs' 
                            : (currentStep > 1 ? 'bg-slate-50 border-slate-200 text-blue-700' : 'bg-white border-slate-200 text-slate-400')"
                        @click="currentStep = 1"
                    >
                        <div 
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold"
                            :class="currentStep >= 1 ? 'bg-blue-700 text-white shadow-xs' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 1" class="w-4 h-4" />
                            <span v-else>1</span>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block sm:hidden">Langkah 1</span>
                            <span class="truncate block text-xs">Biodata Lengkap</span>
                        </div>
                    </div>

                    <!-- Step 2 Tab -->
                    <div 
                        class="flex items-center gap-2.5 p-2 sm:p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                        :class="currentStep === 2 
                            ? 'bg-blue-50 border-blue-600 text-blue-950 shadow-xs' 
                            : 'bg-white border-slate-200 text-slate-400'"
                        @click="goToStep2"
                    >
                        <div 
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold"
                            :class="currentStep === 2 ? 'bg-blue-700 text-white shadow-xs' : 'bg-slate-200 text-slate-500'"
                        >
                            <span>2</span>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block sm:hidden">Langkah 2</span>
                            <span class="truncate block text-xs">Bukti Pembayaran</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- General Alert / Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <strong class="block font-bold">Mohon periksa kembali kolom formulir berikut:</strong>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- STEP 1: BIODATA -->
            <div v-show="currentStep === 1" class="space-y-5">
                
                <!-- Notice Banner: Deferred Supporting Documents -->
                <div class="bg-blue-50/90 border border-blue-200/90 rounded-2xl p-4 sm:p-5 flex items-start gap-3 text-xs text-blue-950 shadow-xs">
                    <InformationCircleIcon class="w-5 h-5 text-blue-700 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-extrabold block text-blue-900 mb-0.5">Kemudahan Pendaftaran Tahap Awal:</strong>
                        <p class="text-blue-800 leading-relaxed text-[11px] sm:text-xs">
                            Formulir ini hanya membutuhkan kelengkapan biodata dan unggahan bukti pembayaran biaya pendaftaran. Berkas fisik pendukung (seperti Kartu Keluarga, Akta Kelahiran, dan Ijazah/SKL SD) <strong>tidak perlu diunggah sekarang</strong> dan diserahkan saat sesi wawancara komitmen orang tua sesuai jadwal panitia.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Data Calon Murid -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-black text-slate-900">Data Calon Murid / Santri</h2>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">* Kolom wajib</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Calon Murid *</label>
                            <input 
                                v-model="form.full_name" 
                                type="text" 
                                autocomplete="name"
                                placeholder="Contoh: Muhammad Rayhan Pratama"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Panggilan</label>
                            <input 
                                v-model="form.nickname" 
                                type="text" 
                                placeholder="Contoh: Rayhan"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <!-- Touch-friendly Gender Selector -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label 
                                    class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border cursor-pointer font-bold text-xs transition"
                                    :class="form.gender === 'L' ? 'bg-blue-50 border-blue-600 text-blue-900 shadow-xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                >
                                    <input type="radio" v-model="form.gender" value="L" class="hidden" />
                                    <span>👦 Laki-laki</span>
                                </label>
                                <label 
                                    class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border cursor-pointer font-bold text-xs transition"
                                    :class="form.gender === 'P' ? 'bg-blue-50 border-blue-600 text-blue-900 shadow-xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                >
                                    <input type="radio" v-model="form.gender" value="P" class="hidden" />
                                    <span>👧 Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempat Lahir *</label>
                            <input 
                                v-model="form.birth_place" 
                                type="text" 
                                placeholder="Contoh: Probolinggo"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir *</label>
                            <input 
                                v-model="form.birth_date" 
                                type="date" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
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
                                placeholder="Sesuai Kartu Keluarga"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NISN (Nomor Induk Siswa Nasional)</label>
                            <input 
                                v-model="form.nisn" 
                                type="tel" 
                                inputmode="numeric"
                                maxlength="20"
                                placeholder="Nomor NISN dari SD/MI"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Asal Sekolah Dasar (SD/MI) *</label>
                            <input 
                                v-model="form.previous_school" 
                                type="text" 
                                placeholder="Contoh: SD Namira Kota Probolinggo / SDN Sukabumi 1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Anak ke-</label>
                            <input 
                                v-model="form.child_order" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dari jumlah bersaudara</label>
                            <input 
                                v-model="form.total_siblings" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Catatan Khusus / Riwayat Kesehatan / Prestasi</label>
                            <textarea 
                                v-model="form.special_notes" 
                                rows="2"
                                placeholder="Misal: Riwayat alergi makanan, hafalan Al-Qur'an (Juz 30), prestasi lomba, dll."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Data Orang Tua & Akun Login -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 2</span>
                            <h2 class="text-sm font-black text-slate-900">Data Orang Tua & Akun Portal Calon Santri</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Ayah</label>
                            <input 
                                v-model="form.father_name" 
                                type="text" 
                                placeholder="Nama lengkap Ayah"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
                            <input 
                                v-model="form.father_job" 
                                type="text" 
                                placeholder="Contoh: PNS / Karyawan Swasta / Wiraswasta"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Ibu</label>
                            <input 
                                v-model="form.mother_name" 
                                type="text" 
                                placeholder="Nama lengkap Ibu"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ibu</label>
                            <input 
                                v-model="form.mother_job" 
                                type="text" 
                                placeholder="Contoh: Ibu Rumah Tangga / Guru / Dokter"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            />
                        </div>

                        <!-- WhatsApp Login Account -->
                        <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                            <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Aktif (Akun Utama Login) *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.parent_phone" 
                                    type="tel" 
                                    inputmode="tel"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                                    required
                                />
                                <PhoneIcon class="w-4 h-4 text-slate-400 absolute right-3.5 top-3" />
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Nomor ini digunakan sebagai <strong>identitas login</strong> Anda untuk memantau status seleksi dan mengunduh Kartu Peserta.
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
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
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
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal Lengkap *</label>
                            <textarea 
                                v-model="form.address" 
                                rows="2"
                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
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
                        class="w-full sm:w-auto sm:ml-auto flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3.5 px-7 rounded-2xl text-xs sm:text-sm transition shadow-sm"
                    >
                        <span>Lanjut ke Langkah 2 (Pembayaran)</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                    </button>
                </div>

            </div>

            <!-- STEP 2: PEMBAYARAN BUKTI DAFTAR -->
            <div v-show="currentStep === 2" class="space-y-5">
                
                <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-black text-slate-900">Pembayaran Biaya Pendaftaran SMP</h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-800 text-[10px] font-bold">
                            Fase Awal
                        </span>
                    </div>

                    <!-- Payment Details Card -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-b from-slate-50 to-blue-50/30 border border-slate-200 text-xs space-y-3.5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <span class="text-slate-600 font-medium">Biaya Formulir & Seleksi:</span>
                            <strong class="text-base sm:text-lg font-black text-blue-700">
                                {{ setting?.registration_fee ? formatRupiah(setting.registration_fee) : 'Rp 250.000' }}
                            </strong>
                        </div>

                        <div class="space-y-2 text-slate-700">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Bank Tujuan:</span>
                                <strong class="text-slate-900 font-bold">{{ setting?.bank_name || 'Bank Jatim' }}</strong>
                            </div>

                            <!-- Interactive Copy Account Number -->
                            <div class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-slate-200">
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Nomor Rekening:</span>
                                    <strong class="font-mono text-sm sm:text-base text-blue-900 font-bold">
                                        {{ setting?.bank_account_number || '0291008899' }}
                                    </strong>
                                </div>
                                <button 
                                    type="button" 
                                    @click="copyAccountNumber"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition"
                                    :class="isCopied ? 'bg-emerald-600 text-white' : 'bg-blue-50 hover:bg-blue-100 text-blue-700'"
                                >
                                    <ClipboardDocumentCheckIcon v-if="isCopied" class="w-3.5 h-3.5" />
                                    <ClipboardIcon v-else class="w-3.5 h-3.5" />
                                    <span>{{ isCopied ? 'Tersalin!' : 'Salin Rekening' }}</span>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Atas Nama:</span>
                                <strong class="text-slate-900 font-bold">{{ setting?.bank_account_holder || 'YAYASAN NAMIRA PROBOLINGGO' }}</strong>
                            </div>

                            <div v-if="setting?.contact_whatsapp" class="pt-1 text-[11px] text-slate-500 border-t border-slate-100 flex items-center justify-between">
                                <span>Konfirmasi Panitia:</span>
                                <a :href="`https://wa.me/${setting.contact_whatsapp}`" target="_blank" class="text-blue-700 font-bold hover:underline">
                                    {{ setting.contact_whatsapp }}
                                </a>
                            </div>
                        </div>

                        <!-- QRIS Option -->
                        <div v-if="setting?.qris_image_url" class="pt-3 border-t border-slate-200 flex flex-col items-center">
                            <span class="text-[11px] font-bold text-slate-600 uppercase mb-2 flex items-center gap-1.5">
                                <QrCodeIcon class="w-4 h-4 text-blue-700" />
                                <span>Atau Scan QRIS Pendaftaran:</span>
                            </span>
                            <div class="bg-white p-3 rounded-2xl border border-slate-300 shadow-xs inline-block">
                                <img :src="setting.qris_image_url" alt="QRIS Pendaftaran" class="w-44 h-44 object-contain" />
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1.5">Mendukung BCA, Mandiri, BRI, BNI, GoPay, OVO, ShopeePay, Dana</span>
                        </div>
                    </div>

                    <!-- Upload Box (Mobile Optimized with Photo Preview) -->
                    <div class="space-y-2.5">
                        <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                            Unggah Bukti Transfer / Pembayaran Pendaftaran *
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Foto struk ATM, bukti m-Banking, atau screenshot pembayaran QRIS (JPG, PNG, atau PDF).
                        </p>

                        <!-- Selected Preview Card -->
                        <div v-if="paymentPreview" class="relative rounded-2xl border border-blue-200 bg-blue-50/50 p-4 flex flex-col sm:flex-row items-center gap-4">
                            <img :src="paymentPreview" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-xl border border-slate-300 shadow-xs shrink-0 bg-white" alt="Pratinjau Bukti Bayar" />
                            <div class="flex-1 text-center sm:text-left">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold mb-1">
                                    <CheckCircleIcon class="w-3 h-3" /> Berkas Terpilih
                                </span>
                                <strong class="block text-xs font-mono text-slate-900 break-all">{{ fileNamePaymentProof }}</strong>
                                <p class="text-[10px] text-slate-500 mt-1">Ukuran berkas telah dioptimasi otomatis agar hemat kuota.</p>
                                
                                <label for="payment_proof_input" class="inline-block mt-2.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition shadow-2xs">
                                    Ganti Berkas
                                </label>
                            </div>
                        </div>

                        <!-- Dropzone Empty State -->
                        <div v-else class="border-2 border-dashed border-slate-300 rounded-2xl p-6 sm:p-8 text-center hover:border-blue-600 transition bg-slate-50/50">
                            <input 
                                type="file" 
                                id="payment_proof_input"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                class="hidden"
                                @change="onPaymentProofChange"
                            />
                            <label for="payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center mb-2.5">
                                    <CameraIcon class="w-6 h-6" />
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-blue-700 hover:underline">
                                    Ambil Foto Struk atau Pilih dari Galeri HP
                                </span>
                                <span class="text-[11px] text-slate-400 mt-1">
                                    Maksimal ukuran file 10MB
                                </span>
                            </label>
                        </div>

                        <div v-if="isCompressing" class="text-center py-2 text-xs font-bold text-blue-700 flex items-center justify-center gap-2">
                            <span class="w-3 h-3 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
                            <span>Mengoptimasi gambar bukti transfer...</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls (Mobile Stacked) -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="currentStep = 1"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold py-3 px-5 rounded-2xl text-xs transition border border-slate-300"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Biodata</span>
                    </button>

                    <button 
                        type="button" 
                        :disabled="form.processing || isCompressing"
                        @click="submitRegistration"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3.5 px-8 rounded-2xl text-xs sm:text-sm transition shadow-sm disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : 'Kirim Pendaftaran Sekarang' }}</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</template>
