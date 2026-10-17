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
        alert('Mohon lampirkan foto struk transfer atau bukti bayar pendaftaran.');
        return;
    }

    form.post(route('spmb.store.smp'), {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};
</script>

<template>
    <Head title="Formulir Pendaftaran Siswa Baru - SMP Namira Kota Probolinggo" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-blue-100 selection:text-blue-900">
        
        <!-- Institutional Header Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-800 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Pilihan Unit SPMB</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <img 
                        src="/images/landing/logo-smp.webp" 
                        alt="Logo SMP Namira" 
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain" 
                        onerror="this.onerror=null; this.src='/images/landing/logo-smp.png';" 
                    />
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 font-medium block leading-tight">SPMB SMP Namira</span>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 block leading-tight">Kota Probolinggo</strong>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Form Container -->
        <div class="max-w-4xl mx-auto px-3.5 sm:px-4 pt-4 sm:pt-6">
            
            <!-- Professional Institutional Title Card (Clean & Formal, No AI Slop, No Fee Banner) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 mb-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-slate-50 border border-slate-200 p-2 flex items-center justify-center shrink-0">
                        <img 
                            src="/images/landing/logo-smp.webp" 
                            alt="Logo SMP Namira" 
                            class="w-full h-full object-contain" 
                            onerror="this.onerror=null; this.src='/images/landing/logo-smp.png';" 
                        />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs mb-1.5">
                            <span class="font-bold text-blue-900 tracking-wide uppercase">SMP Namira Kota Probolinggo</span>
                            <span class="text-slate-300 hidden sm:inline">&bull;</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-800 text-[11px] font-semibold border border-blue-200/60">
                                Jalur Inden
                            </span>
                            <span class="text-slate-500 text-xs">
                                TA {{ setting?.academic_year || '2026/2027' }}
                            </span>
                        </div>
                        
                        <h1 class="text-lg sm:text-2xl font-black text-slate-900 leading-tight">
                            Formulir Pendaftaran Calon Murid
                        </h1>
                        
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Khusus calon murid yang telah terdaftar pada data inden. Lengkapi biodata calon murid dan konfirmasi pembayaran formulir.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress (Clean & Understated) -->
            <div class="bg-white border border-slate-200 rounded-xl p-3 mb-5 shadow-2xs">
                <div class="grid grid-cols-2 gap-2 max-w-md mx-auto">
                    <!-- Step 1 Button -->
                    <button 
                        type="button" 
                        class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition select-none"
                        :class="currentStep === 1 
                            ? 'bg-blue-50 border border-blue-600 text-blue-950 shadow-2xs' 
                            : 'bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900'"
                        @click="currentStep = 1"
                    >
                        <span 
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold"
                            :class="currentStep >= 1 ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 1" class="w-3.5 h-3.5" />
                            <span v-else>1</span>
                        </span>
                        <span>1. Biodata Calon Murid</span>
                    </button>

                    <!-- Step 2 Button -->
                    <button 
                        type="button" 
                        class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition select-none"
                        :class="currentStep === 2 
                            ? 'bg-blue-50 border border-blue-600 text-blue-950 shadow-2xs' 
                            : 'bg-slate-50 border border-slate-200 text-slate-500 hover:text-slate-800'"
                        @click="goToStep2"
                    >
                        <span 
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold"
                            :class="currentStep === 2 ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            2
                        </span>
                        <span>2. Bukti Pembayaran</span>
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

            <!-- STEP 1: BIODATA -->
            <div v-show="currentStep === 1" class="space-y-5">
                
                <!-- Notice Banner: Deferred Supporting Documents -->
                <div class="bg-blue-50/70 border border-blue-200/80 rounded-xl p-4 flex items-start gap-3 text-xs text-blue-950">
                    <InformationCircleIcon class="w-5 h-5 text-blue-700 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-bold text-blue-900 block mb-0.5">Ketentuan Pendaftaran Tahap Awal:</strong>
                        <p class="text-blue-800 leading-relaxed text-[11px] sm:text-xs">
                            Formulir ini hanya memerlukan pengisian biodata dan bukti pembayaran pendaftaran. Berkas fisik pendukung (seperti Kartu Keluarga, Akta Kelahiran, dan Ijazah/SKL SD) <strong>tidak perlu diunggah sekarang</strong> dan diserahkan saat wawancara komitmen orang tua sesuai jadwal panitia.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Data Calon Murid -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Calon Murid</h2>
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
                                placeholder="Sesuai Akta Kelahiran / Kartu Keluarga"
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

                        <!-- Professional Gender Selector (No Emojis) -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label 
                                    class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none text-center"
                                    :class="form.gender === 'L' 
                                        ? 'bg-blue-700 border-blue-700 text-white shadow-xs' 
                                        : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.gender" value="L" class="hidden" />
                                    <span>Laki-laki</span>
                                </label>
                                <label 
                                    class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none text-center"
                                    :class="form.gender === 'P' 
                                        ? 'bg-blue-700 border-blue-700 text-white shadow-xs' 
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
                                placeholder="Misal: Riwayat alergi, hafalan Al-Qur'an (Juz 30), dll."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Data Orang Tua & Akun Login -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Bagian 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Orang Tua & Akun Portal Calon Murid</h2>
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
                                placeholder="Contoh: Pegawai / Wiraswasta"
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
                                placeholder="Contoh: Ibu Rumah Tangga / Guru"
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
                                Nomor WhatsApp ini menjadi akun login Anda untuk melihat pengumuman seleksi dan mengunduh berkas pendaftaran.
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
                        class="w-full sm:w-auto sm:ml-auto flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3.5 px-7 rounded-xl text-xs sm:text-sm transition shadow-2xs"
                    >
                        <span>Lanjut ke Langkah 2 (Pembayaran)</span>
                        <ArrowRightIcon class="w-4 h-4 text-white" />
                    </button>
                </div>

            </div>

            <!-- STEP 2: PEMBAYARAN BUKTI DAFTAR -->
            <div v-show="currentStep === 2" class="space-y-5">
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Pembayaran Pendaftaran</h2>
                        </div>
                    </div>

                    <!-- Payment Information Card (No Price Tag / Pure Banking Information) -->
                    <div class="p-4 sm:p-5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                        <div class="space-y-2 text-slate-700">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Bank Tujuan:</span>
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
                                <span class="text-slate-500 font-medium">Atas Nama:</span>
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
                            <div class="bg-white p-3 rounded-xl border border-slate-300 inline-block shadow-2xs">
                                <img :src="setting.qris_image_url" alt="QRIS Pendaftaran" class="w-44 h-44 object-contain" />
                            </div>
                        </div>
                    </div>

                    <!-- Upload Box (With Clean Thumbnail Preview) -->
                    <div class="space-y-2.5">
                        <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                            Unggah Bukti Transfer / Pembayaran Pendaftaran *
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Foto struk ATM, bukti m-Banking, atau screenshot pembayaran QRIS (JPG, PNG, atau PDF).
                        </p>

                        <!-- Selected Preview Card -->
                        <div v-if="paymentPreview" class="rounded-xl border border-blue-200 bg-blue-50/40 p-4 flex flex-col sm:flex-row items-center gap-4">
                            <img :src="paymentPreview" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg border border-slate-300 shadow-2xs shrink-0 bg-white" alt="Pratinjau Bukti Bayar" />
                            <div class="flex-1 text-center sm:text-left">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold mb-1">
                                    <CheckCircleIcon class="w-3 h-3" /> Berkas Terpilih
                                </span>
                                <strong class="block text-xs font-mono text-slate-900 break-all">{{ fileNamePaymentProof }}</strong>
                                <p class="text-[10px] text-slate-500 mt-1">Ukuran berkas telah dioptimasi otomatis.</p>
                                
                                <label for="payment_proof_input" class="inline-block mt-2.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition shadow-2xs">
                                    Ganti Berkas
                                </label>
                            </div>
                        </div>

                        <!-- Dropzone Empty State -->
                        <div v-else class="border-2 border-dashed border-slate-300 rounded-xl p-6 sm:p-8 text-center hover:border-blue-600 transition bg-slate-50/50">
                            <input 
                                type="file" 
                                id="payment_proof_input"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                class="hidden"
                                @change="onPaymentProofChange"
                            />
                            <label for="payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-2.5">
                                    <CameraIcon class="w-5 h-5" />
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-blue-700 hover:underline">
                                    Pilih Foto Struk atau Dokumen Bukti Bayar
                                </span>
                                <span class="text-[11px] text-slate-400 mt-1">
                                    Maksimal ukuran file 10MB (JPG, PNG, PDF)
                                </span>
                            </label>
                        </div>

                        <div v-if="isCompressing" class="text-center py-2 text-xs font-bold text-blue-700 flex items-center justify-center gap-2">
                            <span class="w-3 h-3 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
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
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-3.5 px-8 rounded-xl text-xs sm:text-sm transition shadow-2xs disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-white" />
                        <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : 'Kirim Pendaftaran Sekarang' }}</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</template>
