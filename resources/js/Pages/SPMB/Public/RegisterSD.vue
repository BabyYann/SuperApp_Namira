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
    InformationCircleIcon,
    SparklesIcon,
    PhoneIcon,
    LockClosedIcon,
    UserIcon,
    HomeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    setting: Object,
    totalApplicants: Number,
});

const currentStep = ref(1);

const form = useForm({
    // Step 1: Biodata Anak
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
    category: 'eksternal_umum', // internal_tk, eksternal_umum
    previous_school: '',
    special_notes: '',

    // Data Orang Tua
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

    // Step 2: Berkas Upload
    photo: null,
    family_card: null,
    birth_cert: null,
    parent_id_card: null,

    // Step 3: Bukti Bayar QRIS
    registration_payment_proof: null,
});

// File Previews
const photoPreview = ref(null);
const fileNames = ref({
    family_card: '',
    birth_cert: '',
    parent_id_card: '',
    registration_payment_proof: ''
});

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const onFileChange = (e, field) => {
    const file = e.target.files[0];
    if (file) {
        form[field] = file;
        fileNames.value[field] = file.name;
    }
};

const validateStep1 = () => {
    if (!form.full_name || !form.birth_place || !form.birth_date || !form.gender) {
        alert('Mohon lengkapi data wajib anak (Nama Lengkap, Tempat & Tanggal Lahir, Jenis Kelamin).');
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
        alert('Mohon isi alamat tempat tinggal.');
        return false;
    }
    return true;
};

const validateStep2 = () => {
    if (!form.photo) {
        alert('Pas foto anak dengan latar merah wajib diunggah.');
        return false;
    }
    if (!form.family_card) {
        alert('Kartu Keluarga (KK) wajib diunggah.');
        return false;
    }
    if (!form.birth_cert) {
        alert('Akta Kelahiran wajib diunggah.');
        return false;
    }
    if (!form.parent_id_card) {
        alert('KTP Orang Tua wajib diunggah.');
        return false;
    }
    return true;
};

const nextStep = () => {
    if (currentStep.value === 1 && !validateStep1()) return;
    if (currentStep.value === 2 && !validateStep2()) return;
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitForm = () => {
    if (!form.registration_payment_proof) {
        alert('Bukti pembayaran QRIS wajib diunggah sebelum mengirim formulir.');
        return;
    }

    form.post(route('spmb.store.sd'), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.parent_phone) {
                alert(errors.parent_phone);
                currentStep.value = 1;
            } else {
                alert('Terdapat kesalahan pengisian. Mohon periksa kembali formulir Anda.');
            }
        }
    });
};

const formattedFee = computed(() => {
    const fee = props.setting?.registration_fee || 250000;
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(fee);
});
</script>

<template>
    <Head title="Formulir Pendaftaran SPMB Inden - SD Namira" />

    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex flex-col justify-between selection:bg-[#fbbf24] selection:text-[#064e3b]">
        <!-- Top Navigation -->
        <header class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-5 flex items-center justify-between border-b border-slate-800">
            <Link :href="route('spmb.index')" class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-emerald-400 hover:text-emerald-300 transition">
                <ArrowLeftIcon class="w-4 h-4" />
                <span>Pilih Unit Lain</span>
            </Link>

            <div class="text-right">
                <span class="text-xs text-slate-400 block">Jalur Inden TA {{ setting?.academic_year || '2026/2027' }}</span>
                <span class="text-sm font-bold text-white block">SD IT NAMIRA</span>
            </div>
        </header>

        <!-- Form Wizard Container -->
        <main class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex-1">
            
            <!-- Wizard Title & Subtitle -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold mb-3">
                    <SparklesIcon class="w-3.5 h-3.5 text-amber-400" />
                    <span>Penerimaan Murid Baru SD Namira</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Formulir Registrasi Siswa Baru
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto mt-2">
                    Lengkapi biodata, unggah berkas, dan konfirmasi pembayaran formulir. Akun login Anda akan dibuat otomatis setelah pengiriman.
                </p>
            </div>

            <!-- Stepper Indicators -->
            <div class="grid grid-cols-3 gap-2 sm:gap-4 max-w-2xl mx-auto mb-8">
                <!-- Step 1 Indicator -->
                <div 
                    class="flex items-center gap-2.5 p-3 rounded-2xl border transition text-xs font-semibold"
                    :class="currentStep === 1 
                        ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300' 
                        : (currentStep > 1 ? 'bg-slate-900 border-emerald-800 text-emerald-400' : 'bg-slate-900/50 border-slate-800 text-slate-500')"
                >
                    <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" :class="currentStep >= 1 ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400'">
                        <CheckCircleIcon v-if="currentStep > 1" class="w-4 h-4" />
                        <span v-else>1</span>
                    </div>
                    <span class="truncate">Biodata</span>
                </div>

                <!-- Step 2 Indicator -->
                <div 
                    class="flex items-center gap-2.5 p-3 rounded-2xl border transition text-xs font-semibold"
                    :class="currentStep === 2 
                        ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300' 
                        : (currentStep > 2 ? 'bg-slate-900 border-emerald-800 text-emerald-400' : 'bg-slate-900/50 border-slate-800 text-slate-500')"
                >
                    <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" :class="currentStep >= 2 ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400'">
                        <CheckCircleIcon v-if="currentStep > 2" class="w-4 h-4" />
                        <span v-else>2</span>
                    </div>
                    <span class="truncate">Upload Berkas</span>
                </div>

                <!-- Step 3 Indicator -->
                <div 
                    class="flex items-center gap-2.5 p-3 rounded-2xl border transition text-xs font-semibold"
                    :class="currentStep === 3 
                        ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300' 
                        : 'bg-slate-900/50 border-slate-800 text-slate-500'"
                >
                    <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" :class="currentStep === 3 ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400'">
                        <span>3</span>
                    </div>
                    <span class="truncate">QRIS & Bukti</span>
                </div>
            </div>

            <!-- Form Content Box -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-2xl">
                
                <!-- ================= STEP 1: BIODATA ================= -->
                <div v-show="currentStep === 1" class="space-y-6">
                    
                    <!-- Section A: Calon Siswa -->
                    <div>
                        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-slate-800 text-emerald-400 font-bold text-sm">
                            <UserIcon class="w-4 h-4" />
                            <span>A. IDENTITAS CALON SISWA</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="sm:col-span-2">
                                <label class="block font-medium text-slate-300 mb-1">Kategori Asal Calon Siswa <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label 
                                        class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                                        :class="form.category === 'internal_tk' ? 'bg-emerald-900/40 border-emerald-500 text-emerald-300' : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                                    >
                                        <input type="radio" v-model="form.category" value="internal_tk" class="text-emerald-600 focus:ring-emerald-500">
                                        <span class="font-semibold">Alumni TK Namira (Internal)</span>
                                    </label>
                                    <label 
                                        class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                                        :class="form.category === 'eksternal_umum' ? 'bg-emerald-900/40 border-emerald-500 text-emerald-300' : 'bg-slate-800/40 border-slate-700 text-slate-400'"
                                    >
                                        <input type="radio" v-model="form.category" value="eksternal_umum" class="text-emerald-600 focus:ring-emerald-500">
                                        <span class="font-semibold">Luar TK Namira (Umum)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-medium text-slate-300 mb-1">Nama Lengkap Anak <span class="text-rose-500">*</span></label>
                                <input v-model="form.full_name" type="text" placeholder="Sesuai Akta Kelahiran" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Nama Panggilan</label>
                                <input v-model="form.nickname" type="text" placeholder="Contoh: Zaki" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                <select v-model="form.gender" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
                                <input v-model="form.birth_place" type="text" placeholder="Kota Kelahiran" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
                                <input v-model="form.birth_date" type="date" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">NIK Anak (16 Digit)</label>
                                <input v-model="form.nik" type="text" maxlength="16" placeholder="Lihat di Kartu Keluarga" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Asal Sekolah TK / RA</label>
                                <input v-model="form.previous_school" type="text" placeholder="Contoh: TK Namira / TK Dharma Wanita" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Orang Tua & Akses Akun -->
                    <div>
                        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-slate-800 text-emerald-400 font-bold text-sm">
                            <LockClosedIcon class="w-4 h-4" />
                            <span>B. DATA ORANG TUA & AKUN LOGIN DASHBOARD</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Nama Lengkap Ayah <span class="text-rose-500">*</span></label>
                                <input v-model="form.father_name" type="text" placeholder="Nama Ayah" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Pekerjaan Ayah</label>
                                <input v-model="form.father_job" type="text" placeholder="PNS / Wiraswasta / Karyawan" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Nama Lengkap Ibu <span class="text-rose-500">*</span></label>
                                <input v-model="form.mother_name" type="text" placeholder="Nama Ibu" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Pekerjaan Ibu</label>
                                <input v-model="form.mother_job" type="text" placeholder="Ibu Rumah Tangga / PNS / dll" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <!-- Akun Login: No WA & Password -->
                            <div class="sm:col-span-2 p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/40 space-y-4">
                                <div class="flex items-center gap-2 text-emerald-300 font-bold">
                                    <PhoneIcon class="w-4 h-4" />
                                    <span>Akun Login Dashboard Calon Siswa</span>
                                </div>
                                <p class="text-[11px] text-slate-300">
                                    Nomor WhatsApp ini akan menjadi identitas login Anda untuk memantau status, melihat jadwal observasi/psikotes, dan mengunduh kartu peserta.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-medium text-slate-300 mb-1">No. WhatsApp Aktif <span class="text-rose-500">*</span></label>
                                        <input v-model="form.parent_phone" type="tel" placeholder="Contoh: 082332922521" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white placeholder-slate-500 focus:border-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block font-medium text-slate-300 mb-1">Kata Sandi Akun <span class="text-rose-500">*</span></label>
                                        <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white placeholder-slate-500 focus:border-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block font-medium text-slate-300 mb-1">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                                        <input v-model="form.password_confirmation" type="password" placeholder="Ulangi kata sandi" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white placeholder-slate-500 focus:border-emerald-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div class="sm:col-span-2">
                                <label class="block font-medium text-slate-300 mb-1">Alamat Tempat Tinggal Lengkap <span class="text-rose-500">*</span></label>
                                <textarea v-model="form.address" rows="2" placeholder="Nama Jalan, No. Rumah, RT/RW, Dusun" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3.5 py-2 text-white placeholder-slate-500 focus:border-emerald-500"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= STEP 2: UPLOAD BERKAS ================= -->
                <div v-show="currentStep === 2" class="space-y-6">
                    <div class="flex items-center gap-2 pb-3 mb-2 border-b border-slate-800 text-emerald-400 font-bold text-sm">
                        <DocumentTextIcon class="w-4 h-4" />
                        <span>UNGGAH DOKUMEN PERSYARATAN</span>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed">
                        Pastikan dokumen yang diunggah terbaca jelas. Format yang didukung: JPG, PNG, atau PDF (Maksimal 5MB per file).
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- 1. Pas Foto Latar Merah -->
                        <div class="p-4 rounded-2xl bg-slate-800/40 border border-slate-700 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-white block">1. Pas Foto Anak (Latar Merah) <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Wajib berlatar belakang merah, ukuran 3x4 formal rapi.</span>
                            </div>

                            <div class="mt-4 flex items-center gap-4">
                                <div class="w-20 h-24 rounded-xl border border-dashed border-slate-600 bg-slate-900 flex items-center justify-center overflow-hidden shrink-0">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                    <CameraIcon v-else class="w-7 h-7 text-slate-600" />
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-semibold text-white transition">
                                    <span>Pilih Foto</span>
                                    <input type="file" accept="image/*" class="hidden" @change="onPhotoChange">
                                </label>
                            </div>
                        </div>

                        <!-- 2. Kartu Keluarga -->
                        <div class="p-4 rounded-2xl bg-slate-800/40 border border-slate-700 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-white block">2. Kartu Keluarga (KK) <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Foto atau scan lembar Kartu Keluarga asli/legalisir.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.family_card" class="text-xs text-emerald-400 font-semibold mb-2 truncate">
                                    ✓ {{ fileNames.family_card }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-semibold text-white transition">
                                    <span>Unggah KK</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'family_card')">
                                </label>
                            </div>
                        </div>

                        <!-- 3. Akta Kelahiran -->
                        <div class="p-4 rounded-2xl bg-slate-800/40 border border-slate-700 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-white block">3. Akta Kelahiran <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Foto atau scan Akta Kelahiran anak yang jelas.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.birth_cert" class="text-xs text-emerald-400 font-semibold mb-2 truncate">
                                    ✓ {{ fileNames.birth_cert }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-semibold text-white transition">
                                    <span>Unggah Akta Lahir</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'birth_cert')">
                                </label>
                            </div>
                        </div>

                        <!-- 4. KTP Orang Tua -->
                        <div class="p-4 rounded-2xl bg-slate-800/40 border border-slate-700 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-white block">4. KTP Orang Tua (Ayah / Ibu) <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Foto atau scan KTP Ayah atau Ibu yang masih berlaku.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.parent_id_card" class="text-xs text-emerald-400 font-semibold mb-2 truncate">
                                    ✓ {{ fileNames.parent_id_card }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-semibold text-white transition">
                                    <span>Unggah KTP</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'parent_id_card')">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= STEP 3: QRIS & BUKTI BAYAR ================= -->
                <div v-show="currentStep === 3" class="space-y-6">
                    <div class="flex items-center gap-2 pb-3 mb-2 border-b border-slate-800 text-emerald-400 font-bold text-sm">
                        <QrCodeIcon class="w-4 h-4" />
                        <span>BIAYA PENDAFTARAN & PEMBAYARAN QRIS</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <!-- QRIS Code Card -->
                        <div class="p-6 rounded-3xl bg-white text-slate-900 text-center shadow-xl">
                            <div class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold mb-3">
                                QRIS STATIS BANK JATIM
                            </div>
                            <h3 class="font-bold text-base text-slate-900">SD IT NAMIRA PROBOLINGGO</h3>
                            <p class="text-xs text-slate-500 mb-4">NMID: ID1020021188998</p>

                            <!-- QRIS Image / Placeholder -->
                            <div class="w-56 h-56 mx-auto bg-slate-100 rounded-2xl border-2 border-dashed border-slate-300 flex items-center justify-center p-2 mb-4">
                                <img 
                                    v-if="setting?.qris_image_url" 
                                    :src="setting.qris_image_url" 
                                    alt="QRIS Bank Jatim"
                                    class="w-full h-full object-contain"
                                >
                                <div v-else class="text-center p-4">
                                    <QrCodeIcon class="w-16 h-16 text-slate-400 mx-auto mb-2" />
                                    <span class="text-[11px] text-slate-500 font-semibold block">Scan via BCA, Mandiri, BRI, GoPay, OVO, Dana, ShopeePay</span>
                                </div>
                            </div>

                            <div class="text-xs font-bold text-slate-700">
                                Nominal Pembayaran: <span class="text-emerald-700 text-base">{{ formattedFee }}</span>
                            </div>
                        </div>

                        <!-- Instruksi & Upload Bukti -->
                        <div class="space-y-4">
                            <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/30 text-xs text-slate-300 space-y-2">
                                <strong class="text-emerald-300 block font-semibold">Cara Pembayaran QRIS:</strong>
                                <ol class="list-decimal pl-4 space-y-1 text-slate-300">
                                    <li>Buka aplikasi Mobile Banking (BCA, Mandiri, BNI, BRI, Bank Jatim) atau E-Wallet apa saja.</li>
                                    <li>Pilih menu <strong>Scan QR / QRIS</strong>.</li>
                                    <li>Arahkan kamera ke kode QRIS di samping atau transfer tepat sebesar <strong>{{ formattedFee }}</strong>.</li>
                                    <li>Simpan tangkapan layar (screenshot) / resi bukti pembayaran.</li>
                                </ol>
                            </div>

                            <!-- Upload Form -->
                            <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700">
                                <label class="block text-xs font-bold text-white mb-1">
                                    Unggah Bukti Transfer / Screenshot QRIS <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-400 block mb-3">Format: JPG, PNG, atau PDF (Maksimal 5MB)</span>

                                <div v-if="fileNames.registration_payment_proof" class="text-xs text-emerald-400 font-bold mb-3 truncate">
                                    ✓ {{ fileNames.registration_payment_proof }}
                                </div>

                                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-700 border border-emerald-500/40 text-xs font-bold text-white transition">
                                    <DocumentTextIcon class="w-4 h-4 text-[#fbbf24]" />
                                    <span>Pilih File Bukti Pembayaran</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'registration_payment_proof')">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between pt-6 mt-8 border-t border-slate-800">
                    <button 
                        v-if="currentStep > 1"
                        type="button" 
                        @click="prevStep"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition border border-slate-700"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Sebelumnya</span>
                    </button>
                    <div v-else></div>

                    <button 
                        v-if="currentStep < 3"
                        type="button" 
                        @click="nextStep"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-700 text-white text-xs font-bold transition shadow-lg shadow-emerald-950/60 border border-emerald-500/30"
                    >
                        <span>Lanjut ke Langkah {{ currentStep + 1 }}</span>
                        <ArrowRightIcon class="w-4 h-4 text-[#fbbf24]" />
                    </button>

                    <button 
                        v-else
                        type="button" 
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-7 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-extrabold transition shadow-xl shadow-emerald-950/80 border border-emerald-400/40 disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-5 h-5 text-[#fbbf24]" />
                        <span>{{ form.processing ? 'Mengirim Data...' : 'Kirim Pendaftaran & Buat Akun' }}</span>
                    </button>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 text-center text-xs text-slate-500 border-t border-slate-900">
            <p>&copy; {{ new Date().getFullYear() }} Panitia SPMB SD Namira. Seluruh data pendaftaran dijaga kerahasiaannya.</p>
        </footer>
    </div>
</template>
