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
    UserIcon,
    HomeIcon,
    InformationCircleIcon
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
    <Head title="Formulir Pendaftaran Siswa Baru - SD Namira" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-emerald-700 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span>Kembali ke Pilihan Unit</span>
                </Link>

                <div class="text-right">
                    <span class="text-[11px] text-slate-500 font-medium block">Pendaftaran Siswa Baru</span>
                    <strong class="text-xs font-bold text-emerald-900 block">SD NAMIRA</strong>
                </div>
            </div>
        </header>

        <!-- Main Form Container -->
        <main class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex-1">
            
            <!-- Page Title Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block">Jalur Inden TA {{ setting?.academic_year || '2026/2027' }}</span>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">Formulir Registrasi Calon Siswa Baru</h1>
                        <p class="text-xs text-slate-500 mt-1">Lengkapi data diri calon siswa, data orang tua, unggah berkas, dan konfirmasi pembayaran.</p>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl px-4 py-2 text-right shrink-0">
                        <span class="text-[10px] uppercase font-bold text-emerald-800 block">Biaya Formulir</span>
                        <span class="text-base font-black text-emerald-700 block">{{ formattedFee }}</span>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress Bar -->
            <div class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 mb-6 shadow-xs">
                <div class="grid grid-cols-3 gap-2 sm:gap-4">
                    
                    <!-- Step 1 -->
                    <div 
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold transition"
                        :class="currentStep === 1 
                            ? 'bg-emerald-50 border-emerald-500 text-emerald-900' 
                            : (currentStep > 1 ? 'bg-slate-50 border-slate-200 text-emerald-700' : 'bg-white border-slate-200 text-slate-400')"
                    >
                        <div 
                            class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-[11px]"
                            :class="currentStep >= 1 ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 1" class="w-4 h-4" />
                            <span v-else>1</span>
                        </div>
                        <span class="truncate">Biodata Lengkap</span>
                    </div>

                    <!-- Step 2 -->
                    <div 
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold transition"
                        :class="currentStep === 2 
                            ? 'bg-emerald-50 border-emerald-500 text-emerald-900' 
                            : (currentStep > 2 ? 'bg-slate-50 border-slate-200 text-emerald-700' : 'bg-white border-slate-200 text-slate-400')"
                    >
                        <div 
                            class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-[11px]"
                            :class="currentStep >= 2 ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 2" class="w-4 h-4" />
                            <span v-else>2</span>
                        </div>
                        <span class="truncate">Unggah Berkas</span>
                    </div>

                    <!-- Step 3 -->
                    <div 
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold transition"
                        :class="currentStep === 3 
                            ? 'bg-emerald-50 border-emerald-500 text-emerald-900' 
                            : 'bg-white border-slate-200 text-slate-400'"
                    >
                        <div 
                            class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-[11px]"
                            :class="currentStep === 3 ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <span>3</span>
                        </div>
                        <span class="truncate">QRIS & Bukti</span>
                    </div>

                </div>
            </div>

            <!-- Form Content Box -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                
                <!-- ================= STEP 1: BIODATA ================= -->
                <div v-show="currentStep === 1" class="space-y-6">
                    
                    <!-- Section A: Calon Siswa -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-800 pb-2 mb-4 border-b border-slate-100 flex items-center gap-2">
                            <UserIcon class="w-4 h-4 text-emerald-700" />
                            <span>A. Data Calon Peserta Didik</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Kategori Asal Calon Siswa <span class="text-rose-600">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label 
                                        class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs"
                                        :class="form.category === 'internal_tk' ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600'"
                                    >
                                        <input type="radio" v-model="form.category" value="internal_tk" class="text-emerald-700 focus:ring-emerald-600">
                                        <span>Alumni TK / KB Namira (Internal)</span>
                                    </label>
                                    <label 
                                        class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs"
                                        :class="form.category === 'eksternal_umum' ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600'"
                                    >
                                        <input type="radio" v-model="form.category" value="eksternal_umum" class="text-emerald-700 focus:ring-emerald-600">
                                        <span>Luar TK Namira (Pendaftar Umum)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Anak <span class="text-rose-600">*</span></label>
                                <input v-model="form.full_name" type="text" placeholder="Sesuai dengan Akta Kelahiran" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Panggilan</label>
                                <input v-model="form.nickname" type="text" placeholder="Contoh: Zaki" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-600">*</span></label>
                                <select v-model="form.gender" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 focus:bg-white focus:border-emerald-600 text-xs">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tempat Lahir <span class="text-rose-600">*</span></label>
                                <input v-model="form.birth_place" type="text" placeholder="Kota Kelahiran" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Lahir <span class="text-rose-600">*</span></label>
                                <input v-model="form.birth_date" type="date" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">NIK Anak (16 Digit)</label>
                                <input v-model="form.nik" type="text" maxlength="16" placeholder="Lihat di Kartu Keluarga" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs font-mono">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Asal Sekolah TK / RA</label>
                                <input v-model="form.previous_school" type="text" placeholder="Contoh: TK Namira / TK Dharma Wanita" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Orang Tua & Akses Akun -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-800 pb-2 mb-4 border-b border-slate-100 flex items-center gap-2">
                            <HomeIcon class="w-4 h-4 text-emerald-700" />
                            <span>B. Data Orang Tua & Akun Login</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Ayah <span class="text-rose-600">*</span></label>
                                <input v-model="form.father_name" type="text" placeholder="Nama Ayah Kandung" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pekerjaan Ayah</label>
                                <input v-model="form.father_job" type="text" placeholder="PNS / Swasta / Wiraswasta" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Ibu <span class="text-rose-600">*</span></label>
                                <input v-model="form.mother_name" type="text" placeholder="Nama Ibu Kandung" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pekerjaan Ibu</label>
                                <input v-model="form.mother_job" type="text" placeholder="Ibu Rumah Tangga / PNS / dll" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs">
                            </div>

                            <!-- Akun Login Box -->
                            <div class="sm:col-span-2 p-4 rounded-xl bg-emerald-50/80 border border-emerald-200 space-y-3">
                                <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
                                    <PhoneIcon class="w-4 h-4 text-emerald-700" />
                                    <span>Akun Login Pemantauan Pendaftaran</span>
                                </div>
                                <p class="text-[11px] text-emerald-800 leading-relaxed">
                                    Nomor WhatsApp ini digunakan sebagai username login Anda untuk melihat pengumuman, jadwal observasi/psikotes, dan mengunduh kartu tanda peserta.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">No. WhatsApp Aktif <span class="text-rose-600">*</span></label>
                                        <input v-model="form.parent_phone" type="tel" placeholder="082332922521" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900 text-xs focus:border-emerald-600 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Kata Sandi Akun <span class="text-rose-600">*</span></label>
                                        <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900 text-xs focus:border-emerald-600">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Ulangi Kata Sandi <span class="text-rose-600">*</span></label>
                                        <input v-model="form.password_confirmation" type="password" placeholder="Konfirmasi sandi" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900 text-xs focus:border-emerald-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Alamat Lengkap -->
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Alamat Domisili Lengkap <span class="text-rose-600">*</span></label>
                                <textarea v-model="form.address" rows="2" placeholder="Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota" class="w-full bg-slate-50/50 border border-slate-300 rounded-xl px-3.5 py-2 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 text-xs"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= STEP 2: UPLOAD BERKAS ================= -->
                <div v-show="currentStep === 2" class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-800 pb-2 mb-2 border-b border-slate-100 flex items-center gap-2">
                            <DocumentTextIcon class="w-4 h-4 text-emerald-700" />
                            <span>Unggah Dokumen Persyaratan Pendaftaran</span>
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Pastikan foto atau dokumen yang diunggah terbaca dengan jelas. Format yang didukung: JPG, PNG, atau PDF (Maksimal 5MB per dokumen).
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        
                        <!-- 1. Pas Foto Latar Merah -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">1. Pas Foto Anak (Latar Belakang Merah) <span class="text-rose-600">*</span></span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Ukuran foto 3x4 rapi, pakaian sopan / seragam TK.</span>
                            </div>

                            <div class="mt-4 flex items-center gap-4">
                                <div class="w-20 h-24 rounded-xl border border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden shrink-0">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                    <CameraIcon v-else class="w-7 h-7 text-slate-400" />
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-xs font-bold text-slate-700 transition">
                                    <span>Pilih File Foto</span>
                                    <input type="file" accept="image/*" class="hidden" @change="onPhotoChange">
                                </label>
                            </div>
                        </div>

                        <!-- 2. Kartu Keluarga -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">2. Kartu Keluarga (KK) <span class="text-rose-600">*</span></span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Foto atau scan lembar Kartu Keluarga asli.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.family_card" class="text-xs text-emerald-700 font-bold mb-2 truncate">
                                    ✓ {{ fileNames.family_card }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-xs font-bold text-slate-700 transition">
                                    <span>Pilih File KK</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'family_card')">
                                </label>
                            </div>
                        </div>

                        <!-- 3. Akta Kelahiran -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">3. Akta Kelahiran Anak <span class="text-rose-600">*</span></span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Foto atau scan Akta Kelahiran asli yang jelas.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.birth_cert" class="text-xs text-emerald-700 font-bold mb-2 truncate">
                                    ✓ {{ fileNames.birth_cert }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-xs font-bold text-slate-700 transition">
                                    <span>Pilih File Akta</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'birth_cert')">
                                </label>
                            </div>
                        </div>

                        <!-- 4. KTP Orang Tua -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">4. KTP Orang Tua (Ayah / Ibu) <span class="text-rose-600">*</span></span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Foto atau scan KTP Ayah atau Ibu yang masih berlaku.</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="fileNames.parent_id_card" class="text-xs text-emerald-700 font-bold mb-2 truncate">
                                    ✓ {{ fileNames.parent_id_card }}
                                </div>
                                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-xs font-bold text-slate-700 transition">
                                    <span>Pilih File KTP</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'parent_id_card')">
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ================= STEP 3: QRIS & BUKTI BAYAR ================= -->
                <div v-show="currentStep === 3" class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-800 pb-2 mb-2 border-b border-slate-100 flex items-center gap-2">
                            <QrCodeIcon class="w-4 h-4 text-emerald-700" />
                            <span>Pembayaran Biaya Formulir Pendaftaran</span>
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Biaya registrasi formulir sebesar <strong>{{ formattedFee }}</strong> dibayarkan menggunakan kode QRIS statis resmi Bank Jatim di bawah ini.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        
                        <!-- QRIS Presentation Card -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-center shadow-xs">
                            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider mb-3">
                                QRIS STATIS BANK JATIM
                            </span>
                            <h4 class="font-extrabold text-sm text-slate-900">SD NAMIRA PROBOLINGGO</h4>
                            <p class="text-[11px] text-slate-500 mb-4">NMID: ID1020021188998</p>

                            <!-- QRIS Image Box -->
                            <div class="w-56 h-56 mx-auto bg-white rounded-xl border border-slate-300 flex items-center justify-center p-2 mb-4 shadow-inner">
                                <img 
                                    v-if="setting?.qris_image_url" 
                                    :src="setting.qris_image_url" 
                                    alt="QRIS Bank Jatim"
                                    class="w-full h-full object-contain"
                                >
                                <div v-else class="text-center p-4">
                                    <QrCodeIcon class="w-16 h-16 text-slate-400 mx-auto mb-2" />
                                    <span class="text-[11px] text-slate-500 font-semibold block">Scan via BCA, Mandiri, BRI, Bank Jatim, GoPay, Dana, OVO</span>
                                </div>
                            </div>

                            <div class="text-xs font-semibold text-slate-700">
                                Nominal Transfer: <span class="text-emerald-700 font-black text-sm">{{ formattedFee }}</span>
                            </div>
                        </div>

                        <!-- Guide & Upload Form -->
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 space-y-2">
                                <strong class="text-amber-950 font-bold block">Petunjuk Pembayaran:</strong>
                                <ol class="list-decimal pl-4 space-y-1 text-slate-700 text-xs">
                                    <li>Buka aplikasi Mobile Banking atau E-Wallet apa saja di ponsel Anda.</li>
                                    <li>Pilih menu <strong>Scan QR / QRIS</strong>.</li>
                                    <li>Arahkan kamera ke kode QRIS di samping.</li>
                                    <li>Pastikan nama merchant tertera <strong>SD NAMIRA</strong>.</li>
                                    <li>Masukkan nominal tepat <strong>{{ formattedFee }}</strong> lalu selesaikan pembayaran.</li>
                                    <li>Simpan tangkapan layar (screenshot) bukti transfer sukses.</li>
                                </ol>
                            </div>

                            <!-- Upload Proof -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                <label class="block text-xs font-bold text-slate-800">
                                    Unggah Bukti Transfer / Screenshot QRIS <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500 block">Format: JPG, PNG, atau PDF (Maksimal 5MB)</span>

                                <div v-if="fileNames.registration_payment_proof" class="text-xs text-emerald-700 font-bold py-1 truncate">
                                    ✓ {{ fileNames.registration_payment_proof }}
                                </div>

                                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold transition shadow-xs">
                                    <DocumentTextIcon class="w-4 h-4 text-amber-300" />
                                    <span>Pilih File Bukti Pembayaran</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange($event, 'registration_payment_proof')">
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Navigation Buttons -->
                <div class="flex items-center justify-between pt-6 mt-8 border-t border-slate-200">
                    <button 
                        v-if="currentStep > 1"
                        type="button" 
                        @click="prevStep"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-200"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali</span>
                    </button>
                    <div v-else></div>

                    <button 
                        v-if="currentStep < 3"
                        type="button" 
                        @click="nextStep"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold transition shadow-xs"
                    >
                        <span>Lanjut ke Langkah {{ currentStep + 1 }}</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-300" />
                    </button>

                    <button 
                        v-else
                        type="button" 
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold transition shadow-sm disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-amber-300" />
                        <span>{{ form.processing ? 'Mengirim Data...' : 'Kirim Pendaftaran & Buat Akun' }}</span>
                    </button>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-4xl mx-auto px-4">
                <p>&copy; {{ new Date().getFullYear() }} Panitia SPMB SD Namira. Seluruh data pendaftaran dijaga kerahasiaannya.</p>
            </div>
        </footer>

    </div>
</template>
