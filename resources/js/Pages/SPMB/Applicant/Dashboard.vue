<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    CalendarDaysIcon, 
    ArrowDownTrayIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    CreditCardIcon,
    ArrowRightOnRectangleIcon,
    UserIcon,
    MapPinIcon,
    PhoneIcon,
    DocumentTextIcon,
    PencilSquareIcon,
    PhotoIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    applicant: Object,
    setting: Object,
    isAnnouncementReleased: {
        type: Boolean,
        default: false,
    },
});

const isSmp = computed(() => {
    const unitName = (props.applicant?.unit?.name || '').toLowerCase();
    const unitCode = props.applicant?.unit?.code || '';
    return unitCode === 'SMP' || unitName.includes('smp');
});

const unitDisplayName = computed(() => {
    if (props.applicant?.unit?.name) {
        return props.applicant.unit.name;
    }
    return isSmp.value ? 'SMP Namira Kota Probolinggo' : 'SD Namira Kota Probolinggo';
});

const formatDate = (dateStr) => {
    if (!dateStr) return null;
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(d);
    } catch (e) {
        return dateStr;
    }
};

const uploadForm = useForm({
    payment_proof: null,
    payment_type: 'termin_1',
});

const handleUploadProof = async (e, type = 'termin_1') => {
    const file = e.target.files[0];
    if (!file) return;

    let uploadFile = file;
    if (file.type && file.type.startsWith('image/')) {
        try {
            uploadFile = await compressImageFile(file, 1280, 0.75);
        } catch (err) {
            console.error('Kompresi bukti daftar ulang gagal:', err);
        }
    }

    uploadForm.payment_proof = uploadFile;
    uploadForm.payment_type = type;
    uploadForm.post(route('spmb.applicant.upload-proof', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => {
            alert(type === 'termin_2' ? 'Bukti transfer pelunasan berhasil diunggah.' : 'Bukti transfer daftar ulang Termin 1 berhasil diunggah.');
        }
    });
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

// Self-service Edit Biodata & Pas Foto
const showEditModal = ref(false);
const editForm = useForm({
    full_name: props.applicant.full_name || '',
    nickname: props.applicant.nickname || '',
    gender: props.applicant.gender || 'L',
    nik: props.applicant.nik || '',
    nisn: props.applicant.nisn || '',
    birth_place: props.applicant.birth_place || '',
    birth_date: props.applicant.birth_date ? String(props.applicant.birth_date).substring(0, 10) : '',
    religion: props.applicant.religion || 'Islam',
    child_order: props.applicant.child_order || 1,
    total_siblings: props.applicant.total_siblings || 1,
    category: props.applicant.category || 'external_umum',
    previous_school: props.applicant.previous_school || '',
    special_notes: props.applicant.special_notes || '',
    father_name: props.applicant.father_name || '',
    father_nik: props.applicant.father_nik || '',
    father_phone: props.applicant.father_phone || '',
    father_job: props.applicant.father_job || '',
    father_education: props.applicant.father_education || '',
    mother_name: props.applicant.mother_name || '',
    mother_nik: props.applicant.mother_nik || '',
    mother_phone: props.applicant.mother_phone || '',
    mother_job: props.applicant.mother_job || '',
    mother_education: props.applicant.mother_education || '',
    parent_phone: props.applicant.parent_phone || '',
    address: props.applicant.address || '',
    rt: props.applicant.rt || '',
    rw: props.applicant.rw || '',
    village: props.applicant.village || '',
    district: props.applicant.district || '',
    city: props.applicant.city || '',
    postal_code: props.applicant.postal_code || '',
    photo: null,
});

const handlePhotoSelect = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    let uploadFile = file;
    if (file.type && file.type.startsWith('image/')) {
        try {
            uploadFile = await compressImageFile(file, 900, 0.8);
        } catch (err) {
            console.error('Kompresi foto gagal:', err);
        }
    }
    editForm.photo = uploadFile;
};

const submitEditProfile = () => {
    editForm.post(route('spmb.applicant.update-profile', props.applicant.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.photo = null;
        },
    });
};

// Ganti Bukti Transfer Biaya Formulir Awal (Sebelum diverifikasi Admin)
const regProofForm = useForm({
    registration_payment_proof: null,
});

const handleReplaceRegistrationProof = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    let uploadFile = file;
    if (file.type && file.type.startsWith('image/')) {
        try {
            uploadFile = await compressImageFile(file, 1280, 0.75);
        } catch (err) {
            console.error('Kompresi bukti pendaftaran gagal:', err);
        }
    }

    regProofForm.registration_payment_proof = uploadFile;
    regProofForm.post(route('spmb.applicant.upload-registration-proof', props.applicant.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            alert('Bukti transfer biaya pendaftaran berhasil diperbarui.');
        },
    });
};
</script>

<template>
    <Head :title="`Portal Calon Siswa - SPMB ${unitDisplayName}`" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- Top Institutional App Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border border-slate-200 p-1 flex items-center justify-center shrink-0 shadow-xs">
                        <img 
                            :src="isSmp ? '/images/landing/logo-smp.webp' : '/images/landing/logo-sd.webp'" 
                            :alt="`Logo ${unitDisplayName}`" 
                            class="w-full h-full object-contain"
                            :onerror="`this.onerror=null; this.src='${isSmp ? '/images/landing/logo-smp.png' : '/images/landing/logo-sd.png'}';`"
                        />
                    </div>
                    <div>
                        <span class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Portal Calon Siswa SPMB</span>
                        <h1 class="text-xs sm:text-sm font-black text-slate-900 leading-tight uppercase">{{ unitDisplayName }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a 
                        :href="`https://wa.me/${setting?.contact_whatsapp || '6282332922521'}?text=Assalamu'alaikum%20Panitia%20SPMB,%20saya%20orang%20tua%20dari%20${applicant.full_name}%20(Reg:%20${applicant.registration_number})`"
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition border border-emerald-200"
                    >
                        <PhoneIcon class="w-3.5 h-3.5 text-emerald-700" />
                        <span>Bantuan Panitia</span>
                    </a>

                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-700 border border-slate-200 text-slate-700 text-xs font-bold transition"
                    >
                        <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                        <span>Keluar</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Portal Container -->
        <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-6 sm:py-8 flex-1 space-y-5 sm:space-y-6">
            
            <!-- Announcement Gating Banner -->
            <div v-if="!isAnnouncementReleased" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-4 sm:p-5 shadow-xs flex items-start gap-3.5 sm:gap-4">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <ClockIcon class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-blue-950">Tahap Seleksi & Observasi Sedang Berlangsung</h3>
                    <p class="text-[11px] sm:text-xs text-blue-800/80 mt-1 leading-relaxed">
                        Pengumuman hasil seleksi dan observasi penerimaan dijadwalkan pada 
                        <strong class="font-bold text-blue-950">{{ setting?.announcement_date ? formatDate(setting.announcement_date) : (isSmp ? 'Jumat, 13 November 2026' : 'Sesuai Jadwal Panitia') }}</strong>. 
                        Silakan unduh Kartu Peserta di bawah dan hadir tepat waktu mengikuti kegiatan tatap muka di sekolah.
                    </p>
                </div>
            </div>

            <!-- Applicant Profile Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-7 shadow-xs">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 sm:gap-6">
                    <div class="flex items-start sm:items-center gap-3.5 sm:gap-4">
                        <!-- Pas Foto 3x4 -->
                        <div class="w-16 h-20 sm:w-20 sm:h-24 rounded-xl bg-slate-100 border border-slate-300 overflow-hidden shrink-0 shadow-xs">
                            <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover" alt="Pas Foto">
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-xs font-bold">3x4</div>
                        </div>

                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold mb-1">
                                <span class="font-mono">{{ applicant.registration_number }}</span>
                            </div>
                            <h2 class="text-lg sm:text-2xl font-black text-slate-900 leading-tight">
                                {{ applicant.full_name }}
                            </h2>
                            <p class="text-xs text-slate-600 mt-1">
                                Asal Sekolah: <strong>{{ applicant.previous_school || '-' }}</strong> 
                                ({{ applicant.category_label || (['internal_tk', 'internal_sd', 'internal'].includes(applicant.category) ? (isSmp ? 'Alumni SD Namira' : 'Alumni TK Namira') : 'Pendaftar Umum') }})
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Orang Tua: {{ applicant.father_name || applicant.mother_name }} | No. WA: {{ applicant.parent_phone }}
                            </p>
                            <div v-if="applicant.status !== 'enrolled'" class="mt-2.5 flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    @click="showEditModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-[11px] font-bold transition"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5 text-emerald-700" />
                                    <span>Edit Biodata & Pas Foto</span>
                                </button>

                                <label
                                    v-if="!applicant.registration_payment_verified_at"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 text-[11px] font-bold transition"
                                >
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-amber-700" />
                                    <span>{{ regProofForm.processing ? 'Mengunggah...' : 'Ganti Bukti Bayar Formulir' }}</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="handleReplaceRegistrationProof">
                                </label>
                                <a
                                    v-if="applicant.registration_payment_proof_url && !applicant.registration_payment_verified_at"
                                    :href="applicant.registration_payment_proof_url"
                                    target="_blank"
                                    class="text-[11px] font-bold text-slate-500 hover:text-emerald-700 underline"
                                >
                                    Lihat Bukti Saat Ini
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Status Highlight -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 sm:p-4 text-left sm:text-right shrink-0 w-full sm:w-auto sm:min-w-[200px]">
                        <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 block uppercase">Status Pendaftaran:</span>
                        <strong class="text-sm font-extrabold text-emerald-700 block mt-0.5">
                            {{ !isAnnouncementReleased && ['accepted', 'reserve', 'rejected'].includes(applicant.status) ? 'Menunggu Pengumuman' : applicant.status_label }}
                        </strong>
                        <span class="text-[10px] text-slate-400 block mt-1">Jalur Inden TA {{ setting?.academic_year || '2026/2027' }}</span>
                    </div>
                </div>
            </div>

            <!-- Milestone Progress Tracker -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-5 flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-emerald-700" />
                    <span>Perjalanan Pendaftaran Peserta Didik Baru</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 text-center">
                    
                    <!-- 1. Form & Berkas -->
                    <div class="p-3 rounded-xl border bg-emerald-50 border-emerald-300 text-emerald-900 text-xs">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <strong class="block text-[11px]">1. Formulir</strong>
                        <span class="text-[10px] text-emerald-700 font-semibold block mt-0.5">Selesai</span>
                    </div>

                    <!-- 2. QRIS -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="applicant.registration_payment_verified_at 
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon v-if="applicant.registration_payment_verified_at" class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <ClockIcon v-else class="w-5 h-5 text-amber-600 mx-auto mb-1" />
                        <strong class="block text-[11px]">2. Cek Biaya</strong>
                        <span class="text-[10px] font-semibold block mt-0.5" :class="applicant.registration_payment_verified_at ? 'text-emerald-700' : 'text-amber-600'">
                            {{ applicant.registration_payment_verified_at ? 'Terverifikasi' : 'Menunggu Cek' }}
                        </span>
                    </div>

                    <!-- 3. Jadwal Seleksi -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="applicant.observation_date 
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon v-if="applicant.observation_date" class="w-5 h-5 text-emerald-700 mx-auto mb-1" />
                        <CalendarDaysIcon v-else class="w-5 h-5 text-slate-400 mx-auto mb-1" />
                        <strong class="block text-[11px]">{{ isSmp ? '3. Seleksi & Tes' : '3. Jadwal Tes' }}</strong>
                        <span class="text-[10px] font-semibold block mt-0.5" :class="applicant.observation_date ? 'text-emerald-700' : 'text-slate-400'">
                            {{ applicant.observation_date ? 'Diterbitkan' : (isSmp ? '07 Nov 2026' : 'Belum') }}
                        </span>
                    </div>

                    <!-- 4. Pengumuman / Keputusan -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) || (isAnnouncementReleased && applicant.evaluation_result)
                            ? (applicant.evaluation_result === 'rejected' ? 'bg-rose-50 border-rose-300 text-rose-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900') 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckBadgeIcon v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) || (isAnnouncementReleased && applicant.evaluation_result === 'accepted')" class="w-5 h-5 mx-auto mb-1 text-emerald-700" />
                        <ClockIcon v-else class="w-5 h-5 mx-auto mb-1 text-amber-600" />
                        <strong class="block text-[11px]">4. Keputusan</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ ['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) || (isAnnouncementReleased && applicant.evaluation_result === 'accepted') 
                                ? 'Diterima' 
                                : (isAnnouncementReleased && applicant.evaluation_result === 'reserve' 
                                    ? 'Cadangan' 
                                    : (isAnnouncementReleased && applicant.evaluation_result === 'rejected' ? 'Belum' : 'Menunggu')) }}
                        </span>
                    </div>

                    <!-- 5. Termin 60% -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CreditCardIcon class="w-5 h-5 mx-auto mb-1" :class="['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-700' : 'text-slate-400'" />
                        <strong class="block text-[11px]">5. Termin 60%</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ ['partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'Lunas 60%' : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 6. Lunas 100% -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition"
                        :class="['fully_paid', 'enrolled'].includes(applicant.status)
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <CheckCircleIcon class="w-5 h-5 mx-auto mb-1" :class="['fully_paid', 'enrolled'].includes(applicant.status) ? 'text-emerald-700' : 'text-slate-400'" />
                        <strong class="block text-[11px]">6. Lunas 100%</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ ['fully_paid', 'enrolled'].includes(applicant.status) ? 'Lunas Tuntas' : 'Menunggu' }}
                        </span>
                    </div>

                    <!-- 7. Siswa Aktif -->
                    <div 
                        class="p-3 rounded-xl border text-xs transition col-span-2 sm:col-span-1"
                        :class="applicant.status === 'enrolled'
                            ? 'bg-emerald-100 border-emerald-400 text-emerald-900 font-bold' 
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <AcademicCapIcon class="w-5 h-5 mx-auto mb-1" :class="applicant.status === 'enrolled' ? 'text-emerald-800' : 'text-slate-400'" />
                        <strong class="block text-[11px]">7. Siswa Aktif</strong>
                        <span class="text-[10px] font-semibold block mt-0.5">
                            {{ applicant.status === 'enrolled' ? 'Resmi Siswa' : (applicant.status === 'fully_paid' ? 'Menunggu Penetapan' : 'Tahap Akhir') }}
                        </span>
                    </div>

                </div>
            </div>

            <!-- Documents Download Center -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- 1. Kartu Peserta -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold mb-3">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Kartu Tanda Peserta SPMB</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Cetak dan bawa kartu ini saat hadir untuk mengikuti seleksi tatap muka di {{ unitDisplayName }}.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            :href="route('spmb.applicant.print-card', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#064e3b] hover:bg-emerald-800 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-amber-300" />
                            <span>Unduh Kartu Peserta (PDF)</span>
                        </a>
                    </div>
                </div>

                <!-- 2. Surat Keputusan Diterima -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold mb-3">
                            <CheckBadgeIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Surat Keterangan Diterima</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Surat keputusan resmi berkop sekolah bagi ananda yang dinyatakan lulus seleksi.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            v-if="isAnnouncementReleased && ['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)"
                            :href="route('spmb.applicant.print-skl', applicant.id)" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white" />
                            <span>Unduh Surat Diterima (PDF)</span>
                        </a>
                        <button 
                            v-else 
                            disabled 
                            class="w-full inline-flex items-center justify-center gap-1 bg-slate-100 text-slate-400 font-bold py-2 px-3 rounded-xl text-xs cursor-not-allowed"
                        >
                            <span>{{ !isAnnouncementReleased ? 'Tersedia Saat Pengumuman' : 'Belum Tersedia' }}</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Rapor Hasil Observasi -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold mb-3">
                            <AcademicCapIcon class="w-5 h-5" />
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">{{ isSmp ? 'Hasil Seleksi & Observasi' : 'Hasil Observasi & Psikotes' }}</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Rangkuman evaluasi hasil seleksi dan catatan kesiapan belajar dari tim penguji.
                        </p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a 
                            v-if="isAnnouncementReleased && applicant.evaluation_document_url"
                            :href="applicant.evaluation_document_url" 
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-3 rounded-xl text-xs transition"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white" />
                            <span>Buka Dokumen Hasil (PDF)</span>
                        </a>
                        <div v-else class="text-center py-1 text-xs text-slate-400 italic">
                            {{ !isAnnouncementReleased ? 'Hasil resmi belum dirilis' : (applicant.evaluation_notes ? applicant.evaluation_notes : 'Dokumen belum diunggah') }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- Two Offline Schedule Cards -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center gap-2">
                    <CalendarDaysIcon class="w-4 h-4 text-emerald-700" />
                    <span>{{ isSmp ? 'Jadwal Seleksi Akademik & Observasi Calon Murid' : 'Jadwal Kegiatan Seleksi Tatap Muka di Sekolah' }}</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Agenda 1 -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 border-l-4 border-l-emerald-600 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <strong class="text-emerald-900 font-bold">{{ isSmp ? '1. Seleksi Akademik (B. Indonesia & Matematika)' : '1. Agenda Observasi Dasar' }}</strong>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Offline</span>
                        </div>
                        <div class="space-y-1 text-slate-600 pt-1">
                            <div>Hari/Tgl: <strong class="text-slate-900">{{ applicant.observation_date ? formatDate(applicant.observation_date) : (isSmp ? 'Sabtu, 07 November 2026' : 'Menunggu Penetapan') }}</strong></div>
                            <div>Waktu: <strong class="text-slate-900">{{ applicant.observation_time || (isSmp ? '08:00 - 10:00 WIB' : 'Akan diinfokan via WA') }}</strong></div>
                            <div>Lokasi: <strong class="text-slate-900">{{ applicant.observation_location || (isSmp ? 'Gedung SMP Namira Kota Probolinggo' : 'Gedung SD Namira') }}</strong></div>
                        </div>
                    </div>

                    <!-- Agenda 2 -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 border-l-4 border-l-amber-600 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <strong class="text-amber-900 font-bold">{{ isSmp ? '2. Observasi Calon Murid (Wawancara & Mengaji)' : '2. Agenda Psikotes Calon Siswa' }}</strong>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Offline</span>
                        </div>
                        <div class="space-y-1 text-slate-600 pt-1">
                            <div>Hari/Tgl: <strong class="text-slate-900">{{ applicant.psychotest_date ? formatDate(applicant.psychotest_date) : (applicant.observation_date ? formatDate(applicant.observation_date) : (isSmp ? 'Sabtu, 07 November 2026' : 'Menunggu Penetapan')) }}</strong></div>
                            <div>Waktu: <strong class="text-slate-900">{{ applicant.psychotest_time || (isSmp ? '10:00 - 12:00 WIB' : 'Akan diinfokan via WA') }}</strong></div>
                            <div>Lokasi: <strong class="text-slate-900">{{ applicant.psychotest_location || (isSmp ? 'Gedung SMP Namira Kota Probolinggo' : 'Gedung SD Namira') }}</strong></div>
                        </div>
                    </div>
                </div>

                <div v-if="applicant.schedule_notes" class="mt-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900">
                    <strong>Catatan Panitia:</strong> {{ applicant.schedule_notes }}
                </div>
            </div>

            <!-- Virtual Account & Re-registration Section -->
            <div v-if="isAnnouncementReleased && ['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)" class="bg-white border-2 border-emerald-600/70 rounded-2xl p-6 sm:p-7 shadow-sm space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center">
                        <CreditCardIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Ketentuan Daftar Ulang (Virtual Account Bank Jatim)</h3>
                        <p class="text-xs text-slate-500">Selamat! Ananda dinyatakan diterima. Silakan selesaikan pembayaran daftar ulang.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Banking Receipt Card -->
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <span class="text-[11px] font-bold text-slate-500 block uppercase">Nomor Virtual Account (VA) Bank Jatim:</span>
                        <div class="text-xl sm:text-2xl font-black text-emerald-800 font-mono tracking-wider">
                            {{ applicant.virtual_account_number || 'Sedang Diterbitkan oleh Keuangan' }}
                        </div>

                        <div class="space-y-1.5 pt-3 border-t border-slate-200 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span>Total Biaya Masuk:</span>
                                <strong class="text-slate-900">{{ formatRupiah(applicant.total_admission_fee) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Termin 1 (Minimal 60%):</span>
                                <strong class="text-amber-700">{{ formatRupiah(applicant.min_down_payment) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Sudah Masuk:</span>
                                <strong class="text-emerald-700">{{ formatRupiah(applicant.paid_admission_amount) }}</strong>
                            </div>
                            <div v-if="applicant.down_payment_deadline" class="flex justify-between text-rose-600">
                                <span>Batas Bayar Termin 1:</span>
                                <strong>{{ applicant.down_payment_deadline }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Proof Box -->
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Konfirmasi Bukti Transfer VA</span>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Setelah transfer ke nomor Virtual Account di samping, silakan unggah bukti transfer per termin agar panitia memvalidasi pendaftaran Ananda.
                            </p>
                        </div>

                        <div class="mt-4 space-y-3">
                            <!-- Box Termin 1: DP Minimal 60% -->
                            <div class="p-3.5 rounded-xl border" :class="applicant.admission_payment_verified_at ? 'bg-emerald-50 border-emerald-200' : (applicant.re_registration_payment_proof ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200')">
                                <div class="flex items-center justify-between mb-1">
                                    <strong class="text-xs font-bold" :class="applicant.admission_payment_verified_at ? 'text-emerald-900' : 'text-slate-800'">
                                        1. Bukti Pembayaran Termin 1 (DP 60%)
                                    </strong>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="applicant.admission_payment_verified_at ? 'bg-emerald-100 text-emerald-800' : (applicant.re_registration_payment_proof ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500')">
                                        {{ applicant.admission_payment_verified_at ? 'Terverifikasi' : (applicant.re_registration_payment_proof ? 'Menunggu Cek' : 'Belum Ada') }}
                                    </span>
                                </div>
                                <div v-if="applicant.re_registration_payment_proof_url" class="flex items-center justify-between text-[11px] pt-1 mb-2">
                                    <span class="text-slate-500">Berkas terunggah:</span>
                                    <a :href="applicant.re_registration_payment_proof_url" target="_blank" class="font-bold text-teal-700 hover:underline">
                                        Lihat Bukti Termin 1 &rarr;
                                    </a>
                                </div>
                                <label v-if="!applicant.admission_payment_verified_at" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#064e3b] hover:bg-emerald-800 text-white text-[11px] font-bold transition">
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-amber-300" />
                                    <span>{{ applicant.re_registration_payment_proof ? 'Ganti Bukti Termin 1' : 'Unggah Bukti Termin 1' }}</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="(e) => handleUploadProof(e, 'termin_1')">
                                </label>
                            </div>

                            <!-- Box Termin 2: Pelunasan 100% -->
                            <div v-if="applicant.status === 'partial_paid' || applicant.settlement_payment_proof" class="p-3.5 rounded-xl border" :class="applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'bg-emerald-50 border-emerald-200' : (applicant.settlement_payment_proof ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200')">
                                <div class="flex items-center justify-between mb-1">
                                    <strong class="text-xs font-bold" :class="applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'text-emerald-900' : 'text-slate-800'">
                                        2. Bukti Pelunasan (Termin 2 - 100%)
                                    </strong>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'bg-emerald-100 text-emerald-800' : (applicant.settlement_payment_proof ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800')">
                                        {{ applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'Lunas 100%' : (applicant.settlement_payment_proof ? 'Menunggu Cek' : 'Siap Diunggah') }}
                                    </span>
                                </div>
                                <div v-if="applicant.settlement_payment_proof_url" class="flex items-center justify-between text-[11px] pt-1 mb-2">
                                    <span class="text-slate-500">Berkas pelunasan:</span>
                                    <a :href="applicant.settlement_payment_proof_url" target="_blank" class="font-bold text-indigo-700 hover:underline">
                                        Lihat Bukti Pelunasan &rarr;
                                    </a>
                                </div>
                                <label v-if="applicant.status === 'partial_paid' || !applicant.settlement_payment_verified_at" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-700 hover:bg-indigo-800 text-white text-[11px] font-bold transition">
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-amber-300" />
                                    <span>{{ applicant.settlement_payment_proof ? 'Ganti Bukti Pelunasan' : 'Unggah Bukti Pelunasan (Termin 2)' }}</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="(e) => handleUploadProof(e, 'termin_2')">
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-6xl mx-auto px-4">
                <p>&copy; {{ new Date().getFullYear() }} Yayasan Namira Kota Probolinggo. Bantuan SPMB: {{ setting?.contact_whatsapp || '082332922521' }}</p>
            </div>
        </footer>

        <!-- Modal Edit Biodata & Pas Foto -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 my-8 max-h-[90vh] overflow-y-auto space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Perbarui Biodata Calon Siswa</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Periksa dan perbaiki penulisan nama, asal sekolah, data orang tua, atau unggah pas foto 3x4.</p>
                    </div>
                    <button type="button" @click="showEditModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEditProfile" class="space-y-5 text-xs">
                    <!-- A. Identitas Calon Siswa -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-emerald-800 uppercase tracking-wider text-[11px]">A. Identitas Calon Siswa</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Sesuai Akta *</label>
                                <input v-model="editForm.full_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                <p v-if="editForm.errors.full_name" class="text-rose-600 text-[11px] mt-1">{{ editForm.errors.full_name }}</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Panggilan</label>
                                <input v-model="editForm.nickname" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                                <select v-model="editForm.gender" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tempat Lahir *</label>
                                <input v-model="editForm.birth_place" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Lahir *</label>
                                <input v-model="editForm.birth_date" type="date" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">NIK Anak</label>
                                <input v-model="editForm.nik" type="text" maxlength="20" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">NISN (Opsional)</label>
                                <input v-model="editForm.nisn" type="text" maxlength="20" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori Pendaftar *</label>
                                <select v-model="editForm.category" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                    <option :value="isSmp ? 'internal_sd' : 'internal_tk'">{{ isSmp ? 'Alumni SD Namira (Internal)' : 'Alumni TK Namira (Internal)' }}</option>
                                    <option value="external_umum">Pendaftar Umum (Eksternal)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Asal Sekolah ({{ isSmp ? 'SD/MI' : 'TK/RA' }})</label>
                                <input v-model="editForm.previous_school" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Anak Ke-</label>
                                <input v-model="editForm.child_order" type="number" min="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jumlah Saudara Kandung</label>
                                <input v-model="editForm.total_siblings" type="number" min="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Pas Foto 3x4 (Opsional / Ganti Foto)</label>
                                <input type="file" accept=".jpg,.jpeg,.png" @change="handlePhotoSelect" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100">
                            </div>
                        </div>
                    </div>

                    <!-- B. Data Orang Tua & Domisili -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <h4 class="font-bold text-emerald-800 uppercase tracking-wider text-[11px]">B. Data Orang Tua & Alamat</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Ayah Kandung *</label>
                                <input v-model="editForm.father_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pekerjaan Ayah</label>
                                <input v-model="editForm.father_job" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Ibu Kandung *</label>
                                <input v-model="editForm.mother_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pekerjaan Ibu</label>
                                <input v-model="editForm.mother_job" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Nomor WhatsApp Aktif (Digunakan untuk Login) *</label>
                                <input v-model="editForm.parent_phone" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                                <p v-if="editForm.errors.parent_phone" class="text-rose-600 text-[11px] mt-1">{{ editForm.errors.parent_phone }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap Domisili *</label>
                                <textarea v-model="editForm.address" rows="2" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Catatan Khusus / Riwayat Kesehatan (Opsional)</label>
                                <input v-model="editForm.special_notes" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="px-5 py-2 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold shadow transition disabled:opacity-50">
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Biodata' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>
