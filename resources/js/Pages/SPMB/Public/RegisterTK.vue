<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    CameraIcon,
    QrCodeIcon,
    PhoneIcon,
    EyeIcon,
    EyeSlashIcon,
    InformationCircleIcon,
    LockClosedIcon,
    ClipboardDocumentCheckIcon,
    ClipboardIcon,
    BuildingLibraryIcon,
} from '@heroicons/vue/24/outline';

import { compressImageFile } from '@/Utils/imageCompressor';
import AcademicYearSelector from '@/Components/SPMB/AcademicYearSelector.vue';

const props = defineProps({
    unit: Object,
    setting: Object,
    tkUnits: {
        type: Array,
        default: () => [],
    },
    academicYearOptions: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: String,
        default: '',
    },
    totalApplicants: Number,
    isLockedForPublic: {
        type: Boolean,
        default: false,
    },
});

const currentStep = ref(1);
const isCompressing = ref(false);
const showPassword = ref(false);
const paymentPreview = ref(null);
const fileNamePaymentProof = ref('');
const isCopied = ref(false);
const submitErrorBanner = ref('');

const form = useForm({
    unit_id: props.unit?.id || props.tkUnits?.[0]?.id || '',
    academic_year: props.selectedAcademicYear || props.setting?.academic_year || '2027/2028',

    // Step 1: Biodata Calon Murid Usia Dini
    full_name: '',
    nickname: '',
    gender: 'L',
    nik: '',
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

    // Step 2: Bukti Pembayaran
    registration_payment_proof: null,
});

const activeUnitEntry = computed(() => {
    return (props.tkUnits || []).find((u) => Number(u.id) === Number(form.unit_id)) || null;
});

const activeSetting = computed(() => activeUnitEntry.value?.setting || props.setting || {});
const activeYearOptions = computed(() => activeUnitEntry.value?.academic_year_options || props.academicYearOptions || []);
const activeUnitName = computed(() => activeUnitEntry.value?.name || props.unit?.name || 'TK / KB Namira');
const activeIsLockedForPublic = computed(() => {
    if (activeUnitEntry.value) {
        return !activeUnitEntry.value.is_open_for_public;
    }
    return props.isLockedForPublic;
});

watch(() => form.unit_id, () => {
    const opts = activeYearOptions.value;
    if (opts.length > 0 && !opts.some((o) => o.value === form.academic_year)) {
        form.academic_year = opts[0].value;
    }
});

const selectedYearOption = computed(() => {
    return activeYearOptions.value.find((opt) => opt.value === form.academic_year) || null;
});

const isFutureIndenSelected = computed(() => Boolean(selectedYearOption.value?.is_future_inden));

const formattedFee = computed(() => {
    const fee = activeSetting.value?.registration_fee || 250000;
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(fee);
});

const copyAccountNumber = () => {
    const acc = activeSetting.value?.bank_account_number;
    if (acc && navigator?.clipboard) {
        navigator.clipboard.writeText(acc).then(() => {
            isCopied.value = true;
            setTimeout(() => { isCopied.value = false; }, 2500);
        });
    }
};

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

const validateStep1 = () => {
    if (!form.unit_id) {
        alert('Mohon pilih unit TK / KB Namira tujuan terlebih dahulu.');
        return false;
    }
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
    submitErrorBanner.value = '';
    if (!form.registration_payment_proof) {
        alert('Mohon lampirkan foto bukti transfer atau kuitansi pendaftaran.');
        return;
    }

    const removeExceptionListener = router.on('exception', (event) => {
        event.preventDefault();
        submitErrorBanner.value = 'Koneksi internet terputus atau jaringan sedang tidak stabil. Silakan coba kirim ulang.';
        alert(submitErrorBanner.value);
    });

    form.post(route('spmb.store.tk'), {
        forceFormData: true,
        preserveScroll: false,
        onError: (errors) => {
            const errorKeys = Object.keys(errors || {});
            const hasStep1Error = errorKeys.some((k) => k !== 'registration_payment_proof');
            if (hasStep1Error) {
                currentStep.value = 1;
            }
            submitErrorBanner.value = errorKeys.length > 0 ? errors[errorKeys[0]] : 'Mohon periksa kembali isian formulir Anda.';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onFinish: () => {
            removeExceptionListener();
        },
    });
};
</script>

<template>
    <Head :title="`Formulir Pendaftaran - ${activeUnitName}`" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20 selection:bg-amber-100 selection:text-amber-900">
        <!-- Admin Preview Banner (When TK/KB unit is still locked for public) -->
        <div v-if="activeIsLockedForPublic" class="bg-amber-400 text-slate-950 font-bold px-4 py-2.5 text-center text-xs flex flex-wrap items-center justify-center gap-2 border-b border-amber-500/40">
            <LockClosedIcon class="w-4 h-4 shrink-0" />
            <span>MODE PRATINJAU ADMINISTRATOR: Pendaftaran {{ activeUnitName }} masih DIKUNCI untuk publik. Untuk membukanya, aktifkan "Pendaftaran Dibuka untuk Umum" di Pengaturan SPMB.</span>
        </div>

        <!-- Header Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <Link :href="route('spmb.index')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-amber-800 transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Pilihan Unit SPMB</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <img
                        src="/images/landing/logo-tk.webp"
                        alt="Logo TK & KB Namira"
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain"
                        onerror="this.onerror=null; this.src='/images/landing/logo-tk.png';"
                    />
                    <div class="text-right">
                        <span class="text-[10px] text-amber-700 font-bold uppercase block leading-tight">SPMB Pendidikan Usia Dini</span>
                        <strong class="text-xs sm:text-sm font-black text-slate-900 block leading-tight">{{ activeUnitName }}</strong>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto px-3.5 sm:px-4 pt-4 sm:pt-6">
            <!-- Title Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 mb-4 sm:mb-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-3.5 sm:gap-5 text-center sm:text-left">
                    <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-xl bg-amber-50/70 border border-amber-200/80 p-2 flex items-center justify-center shrink-0">
                        <img
                            src="/images/landing/logo-tk.webp"
                            alt="Logo TK & KB Namira"
                            class="w-full h-full object-contain"
                            onerror="this.onerror=null; this.src='/images/landing/logo-tk.png';"
                        />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs mb-1.5">
                            <span class="font-bold text-amber-900 tracking-wide uppercase">{{ activeUnitName }}</span>
                            <span class="text-slate-300 hidden sm:inline">&bull;</span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border"
                                :class="isFutureIndenSelected
                                    ? 'bg-purple-50 text-purple-800 border-purple-200/80'
                                    : 'bg-amber-50 text-amber-800 border-amber-200/80'"
                            >
                                {{ isFutureIndenSelected ? 'Jalur Inden Tahun Mendatang' : 'Pendaftaran Murid Baru' }}
                            </span>
                            <span class="text-slate-600 text-xs font-bold">
                                TA {{ form.academic_year || activeSetting?.academic_year || '2027/2028' }}
                            </span>
                        </div>

                        <h1 class="text-lg sm:text-2xl font-black text-slate-900 leading-tight">
                            Formulir Pendaftaran TK &amp; KB Namira
                        </h1>

                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Pilih lokasi satuan pendidikan (Kraksaan / Dringu) dan tahun ajaran masuk ananda. Dokumen pendukung dapat dilengkapi menyusul di Portal Calon Siswa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stepper -->
            <div class="bg-white border border-slate-200 rounded-xl p-2.5 sm:p-3 mb-4 sm:mb-5 shadow-2xs">
                <div class="grid grid-cols-2 gap-2 max-w-md mx-auto">
                    <button
                        type="button"
                        class="flex items-center justify-center gap-2 py-2 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-bold transition select-none"
                        :class="currentStep === 1
                            ? 'bg-amber-50 border border-amber-600 text-amber-950 shadow-2xs'
                            : 'bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900'"
                        @click="currentStep = 1"
                    >
                        <span
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                            :class="currentStep >= 1 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            <CheckCircleIcon v-if="currentStep > 1" class="w-3.5 h-3.5" />
                            <span v-else>1</span>
                        </span>
                        <span class="truncate">1. Biodata Murid</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center justify-center gap-2 py-2 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-bold transition select-none"
                        :class="currentStep === 2
                            ? 'bg-amber-50 border border-amber-600 text-amber-950 shadow-2xs'
                            : 'bg-slate-50 border border-slate-200 text-slate-500 hover:text-slate-800'"
                        @click="goToStep2"
                    >
                        <span
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                            :class="currentStep === 2 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-500'"
                        >
                            2
                        </span>
                        <span class="truncate">2. Bukti Pembayaran</span>
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <div v-if="$page.props.flash?.error || submitErrorBanner" class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-950 font-semibold">
                {{ $page.props.flash?.error || submitErrorBanner }}
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                <strong class="block font-bold">Mohon periksa kembali kolom formulir berikut:</strong>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- ================= STEP 1 ================= -->
            <div v-show="currentStep === 1" class="space-y-4 sm:space-y-5">
                <!-- Unit / Campus Selector -->
                <div v-if="tkUnits && tkUnits.length > 0" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-3">
                    <div class="border-b border-slate-100 pb-2.5">
                        <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                            <BuildingLibraryIcon class="w-3.5 h-3.5" />
                            <span>Satuan Pendidikan Tujuan</span>
                        </span>
                        <h2 class="text-sm font-bold text-slate-900 mt-0.5">Pilih Unit TK / KB Namira *</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button
                            v-for="u in tkUnits"
                            :key="u.id"
                            type="button"
                            @click="form.unit_id = u.id"
                            class="text-left p-3.5 rounded-xl border transition flex items-center justify-between gap-2 cursor-pointer"
                            :class="Number(form.unit_id) === Number(u.id)
                                ? 'border-amber-600 bg-amber-50/70 ring-1 ring-amber-500'
                                : 'border-slate-200 bg-white hover:bg-slate-50'"
                        >
                            <div>
                                <div class="font-bold text-xs sm:text-sm text-slate-900">{{ u.name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                    Kode: {{ u.code }}
                                    <span v-if="!u.is_open_for_public" class="ml-1.5 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-sans font-bold text-[10px]">
                                        Pratinjau Admin
                                    </span>
                                </div>
                            </div>
                            <CheckCircleIcon v-if="Number(form.unit_id) === Number(u.id)" class="w-5 h-5 text-amber-700 shrink-0" />
                        </button>
                    </div>
                </div>

                <!-- Academic Year Selector -->
                <AcademicYearSelector
                    v-model="form.academic_year"
                    :options="activeYearOptions"
                    accent="amber"
                    :unit-label="activeUnitName"
                    :error="form.errors.academic_year"
                />

                <!-- Info Banner -->
                <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3.5 sm:p-4 flex items-start gap-3 text-xs text-amber-950">
                    <InformationCircleIcon class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-bold text-amber-900 block mb-0.5">Unggah Berkas Fleksibel:</strong>
                        <p class="text-amber-800 leading-relaxed text-[11px] sm:text-xs">
                            Cukup lengkapi biodata calon murid dan bukti pembayaran pendaftaran terlebih dahulu. Pas Foto, Kartu Keluarga, Akta Kelahiran, dan KTP Orang Tua dapat dilengkapi kapan saja melalui Portal Calon Siswa.
                        </p>
                    </div>
                </div>

                <!-- Bagian 1: Biodata Calon Murid -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Bagian 1</span>
                            <h2 class="text-sm font-bold text-slate-900">Data Calon Murid {{ activeUnitName }}</h2>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">* Kolom wajib</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-2">Kategori Pendaftar *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <label
                                    class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition select-none"
                                    :class="form.category === 'internal_kb'
                                        ? 'border-amber-600 bg-amber-50/60 ring-1 ring-amber-500'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input type="radio" name="category" value="internal_kb" v-model="form.category" class="mt-0.5 text-amber-600 focus:ring-amber-500" />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Internal KB / Daycare Namira</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Melanjutkan dari KB / Daycare Namira</div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition select-none"
                                    :class="form.category === 'eksternal_umum'
                                        ? 'border-amber-600 bg-amber-50/60 ring-1 ring-amber-500'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input type="radio" name="category" value="eksternal_umum" v-model="form.category" class="mt-0.5 text-amber-600 focus:ring-amber-500" />
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm text-slate-900">Pendaftar Umum</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Pendaftar baru dari masyarakat umum</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Calon Murid *</label>
                            <input v-model="form.full_name" type="text" placeholder="Sesuai Akta Kelahiran / Kartu Keluarga" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Panggilan</label>
                            <input v-model="form.nickname" type="text" placeholder="Contoh: Aisyah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none" :class="form.gender === 'L' ? 'bg-amber-700 border-amber-700 text-white' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" v-model="form.gender" value="L" class="hidden" />
                                    <span>Laki-laki</span>
                                </label>
                                <label class="flex items-center justify-center py-2.5 px-3 rounded-xl border cursor-pointer font-bold text-xs transition select-none" :class="form.gender === 'P' ? 'bg-amber-700 border-amber-700 text-white' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" v-model="form.gender" value="P" class="hidden" />
                                    <span>Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempat Lahir *</label>
                            <input v-model="form.birth_place" type="text" placeholder="Contoh: Probolinggo" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir *</label>
                            <input v-model="form.birth_date" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NIK Anak (16 Digit - Opsional)</label>
                            <input v-model="form.nik" type="tel" inputmode="numeric" maxlength="16" placeholder="Sesuai Kartu Keluarga" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs font-mono focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Asal PAUD / KB / Daycare (Jika Ada)</label>
                            <input v-model="form.previous_school" type="text" placeholder="Kosongkan jika belum sekolah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Anak ke-</label>
                            <input v-model="form.child_order" type="number" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dari jumlah bersaudara</label>
                            <input v-model="form.total_siblings" type="number" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Catatan Khusus / Riwayat Kesehatan / Tumbuh Kembang (Opsional)</label>
                            <textarea v-model="form.special_notes" rows="2" placeholder="Misal: Alergi makanan tertentu atau catatan pendampingan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Orang Tua & Akun Portal -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Bagian 2</span>
                        <h2 class="text-sm font-bold text-slate-900">Data Orang Tua &amp; Akun Portal Calon Siswa</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ayah</label>
                            <input v-model="form.father_name" type="text" placeholder="Nama lengkap Ayah kandung" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ayah</label>
                            <input v-model="form.father_job" type="text" placeholder="Contoh: Pegawai / Wiraswasta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Ibu</label>
                            <input v-model="form.mother_name" type="text" placeholder="Nama lengkap Ibu kandung" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pekerjaan Ibu</label>
                            <input v-model="form.mother_job" type="text" placeholder="Contoh: Ibu Rumah Tangga / Guru" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" />
                        </div>

                        <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                            <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Aktif (Username Login Portal) *</label>
                            <div class="relative">
                                <input v-model="form.parent_phone" type="tel" inputmode="tel" placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs font-mono focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                                <PhoneIcon class="w-4 h-4 text-slate-400 absolute right-3.5 top-3" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kata Sandi Akun *</label>
                            <div class="relative">
                                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                                <button type="button" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600" @click="showPassword = !showPassword">
                                    <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                    <EyeIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                            <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Ketik ulang kata sandi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal Lengkap *</label>
                            <textarea v-model="form.address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm sm:text-xs focus:ring-2 focus:ring-amber-600 focus:border-amber-600 focus:outline-none transition" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="button"
                        @click="goToStep2"
                        class="w-full sm:w-auto sm:ml-auto flex items-center justify-center gap-2 bg-amber-700 hover:bg-amber-800 text-white font-bold py-3.5 px-7 rounded-xl text-xs sm:text-sm transition shadow-2xs"
                    >
                        <span>Lanjut ke Langkah 2 (Pembayaran)</span>
                        <ArrowRightIcon class="w-4 h-4 text-amber-200" />
                    </button>
                </div>
            </div>

            <!-- ================= STEP 2 ================= -->
            <div v-show="currentStep === 2" class="space-y-4 sm:space-y-5">
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Langkah 2</span>
                            <h2 class="text-sm font-bold text-slate-900">Pembayaran Pendaftaran {{ activeUnitName }}</h2>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 font-black text-xs border border-amber-200">
                            {{ formattedFee }}
                        </span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Biaya Formulir Pendaftaran:</span>
                            <strong class="text-amber-800 font-black text-sm">{{ formattedFee }}</strong>
                        </div>
                        <div v-if="activeSetting?.bank_account_number" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Bank Tujuan:</span>
                                <strong class="text-slate-900 font-bold">{{ activeSetting.bank_name || 'Bank Jatim' }}</strong>
                            </div>
                            <div class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-slate-200">
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Nomor Rekening:</span>
                                    <strong class="font-mono text-sm text-amber-900 font-bold">{{ activeSetting.bank_account_number }}</strong>
                                </div>
                                <button
                                    type="button"
                                    @click="copyAccountNumber"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition"
                                    :class="isCopied ? 'bg-emerald-600 text-white' : 'bg-amber-50 hover:bg-amber-100 text-amber-900'"
                                >
                                    <ClipboardDocumentCheckIcon v-if="isCopied" class="w-3.5 h-3.5" />
                                    <ClipboardIcon v-else class="w-3.5 h-3.5" />
                                    <span>{{ isCopied ? 'Tersalin!' : 'Salin' }}</span>
                                </button>
                            </div>
                            <div v-if="activeSetting?.bank_account_holder" class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Atas Nama:</span>
                                <strong class="text-slate-900 font-bold">{{ activeSetting.bank_account_holder }}</strong>
                            </div>
                        </div>

                        <div v-if="activeSetting?.qris_image_url" class="pt-3 border-t border-slate-200 flex flex-col items-center">
                            <span class="text-[11px] font-bold text-slate-600 uppercase mb-2 flex items-center gap-1.5">
                                <QrCodeIcon class="w-4 h-4 text-amber-700" />
                                <span>Scan QRIS Resmi {{ activeUnitName }}:</span>
                            </span>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-300 inline-block shadow-2xs">
                                <img :src="activeSetting.qris_image_url" alt="QRIS Pendaftaran TK/KB" class="w-44 h-44 object-contain" />
                            </div>
                        </div>

                        <div v-if="!activeSetting?.bank_account_number && !activeSetting?.qris_image_url" class="text-[11px] text-slate-600 leading-relaxed">
                            Silakan konfirmasi nomor rekening / QRIS resmi ke Panitia SPMB {{ activeUnitName }}
                            (<strong class="font-mono">{{ activeSetting?.contact_whatsapp || '082332922521' }}</strong>), lalu unggah bukti transfer atau kuitansi pembayaran di bawah ini.
                        </div>
                    </div>

                    <!-- Upload Box -->
                    <div class="space-y-2.5">
                        <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                            Unggah Bukti Pembayaran / Struk Transfer *
                        </label>

                        <div v-if="paymentPreview || fileNamePaymentProof" class="rounded-xl border border-amber-200 bg-amber-50/40 p-4 flex flex-col sm:flex-row items-center gap-4">
                            <img v-if="paymentPreview" :src="paymentPreview" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg border border-slate-300 shadow-2xs shrink-0 bg-white" alt="Pratinjau Bukti Bayar" />
                            <div class="flex-1 text-center sm:text-left">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold mb-1">
                                    <CheckCircleIcon class="w-3 h-3" /> Bukti Pembayaran Siap Dikirim
                                </span>
                                <strong class="block text-xs font-mono text-slate-900 break-all">{{ fileNamePaymentProof }}</strong>
                                <label for="tk_payment_proof_input" class="inline-block mt-2.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition shadow-2xs">
                                    Ganti File Bukti Bayar
                                </label>
                            </div>
                        </div>

                        <div v-else class="border-2 border-dashed border-slate-300 rounded-xl p-6 sm:p-8 text-center hover:border-amber-600 transition bg-slate-50/50">
                            <label for="tk_payment_proof_input" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-2.5">
                                    <CameraIcon class="w-5 h-5" />
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-amber-800 hover:underline">
                                    Pilih Foto Bukti Transfer / Kuitansi Pembayaran
                                </span>
                                <span class="text-[11px] text-slate-400 mt-1">
                                    Maksimal ukuran file 10MB (JPG, PNG, WEBP, PDF)
                                </span>
                            </label>
                        </div>

                        <input
                            type="file"
                            id="tk_payment_proof_input"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="hidden"
                            @change="onPaymentProofChange"
                        />

                        <div v-if="isCompressing" class="text-center py-2 text-xs font-bold text-amber-700 flex items-center justify-center gap-2">
                            <span class="w-3 h-3 rounded-full border-2 border-amber-600 border-t-transparent animate-spin"></span>
                            <span>Mengoptimasi gambar bukti pembayaran...</span>
                        </div>
                    </div>
                </div>

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
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-700 hover:bg-amber-800 text-white font-bold py-3.5 px-8 rounded-xl text-xs sm:text-sm transition shadow-2xs disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-amber-200" />
                        <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : 'Kirim Pendaftaran & Masuk Portal' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
