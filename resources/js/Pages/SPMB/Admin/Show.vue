<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarDaysIcon,
    ClockIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    CreditCardIcon,
    AcademicCapIcon,
    ChatBubbleLeftRightIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentCheckIcon,
    PhotoIcon,
    TrashIcon,
    PencilSquareIcon,
    KeyIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applicant: Object,
    setting: Object,
});

const isSmp = computed(() => {
    const unitName = (props.applicant?.unit?.name || '').toLowerCase();
    const unitCode = props.applicant?.unit?.code || '';
    return unitCode === 'SMP' || unitName.includes('smp');
});

// Modals / Sections state
const showScheduleModal = ref(false);
const showEvaluationModal = ref(false);
const showVaModal = ref(false);
const showPaymentModal = ref(false);
const showEditBiodataModal = ref(false);
const showResetPasswordModal = ref(false);

// Form: Edit Biodata oleh Admin / Panitia
const editBiodataForm = useForm({
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

const submitEditBiodata = () => {
    editBiodataForm.post(route('spmb.admin.update-biodata', props.applicant.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showEditBiodataModal.value = false;
            editBiodataForm.photo = null;
        },
    });
};

// Form: Reset Password Akun Orang Tua
const resetPasswordForm = useForm({
    new_password: props.applicant.parent_phone || '',
});

const submitResetPassword = () => {
    resetPasswordForm.post(route('spmb.admin.reset-password', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => {
            showResetPasswordModal.value = false;
        },
    });
};

// Form: Atur Jadwal
const scheduleForm = useForm({
    observation_date: props.applicant.observation_date || '',
    observation_time: props.applicant.observation_time || '08:00 - 10:00 WIB',
    observation_location: props.applicant.observation_location || (isSmp.value ? 'Gedung SMP Namira Kota Probolinggo' : 'Ruang Konseling & Bermain SD Namira'),
    psychotest_date: props.applicant.psychotest_date || '',
    psychotest_time: props.applicant.psychotest_time || (isSmp.value ? '10:00 - 12:00 WIB' : '08:00 - 11:30 WIB'),
    psychotest_location: props.applicant.psychotest_location || (isSmp.value ? 'Gedung SMP Namira Kota Probolinggo' : 'Aula Pertemuan SD Namira'),
    schedule_notes: props.applicant.schedule_notes || '',
});

const submitSchedule = () => {
    scheduleForm.post(route('spmb.admin.set-schedule', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => showScheduleModal.value = false,
    });
};

// Form: Evaluasi & Hasil
const evaluationForm = useForm({
    evaluation_result: props.applicant.evaluation_result || 'accepted',
    evaluation_notes: props.applicant.evaluation_notes || '',
    evaluation_document: null,
    academic_score: props.applicant.academic_score_details?.academic_score ?? props.applicant.academic_score_details?.bahasa_indonesia ?? '',
    wawancara_notes: props.applicant.academic_score_details?.wawancara ?? '',
    mengaji_notes: props.applicant.academic_score_details?.mengaji ?? '',
});

const submitEvaluation = () => {
    evaluationForm.post(route('spmb.admin.save-evaluation', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => showEvaluationModal.value = false,
    });
};

// Form: Virtual Account Bank Jatim
const vaForm = useForm({
    virtual_account_number: props.applicant.virtual_account_number || '',
    total_admission_fee: props.applicant.total_admission_fee || (props.setting?.admission_fee_total || 8500000),
    min_down_payment: props.applicant.min_down_payment || ((props.setting?.admission_fee_total || 8500000) * 0.6),
    down_payment_deadline: props.applicant.down_payment_deadline || '',
    full_payment_deadline: props.applicant.full_payment_deadline || '',
});

const submitVa = () => {
    vaForm.post(route('spmb.admin.set-va', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => showVaModal.value = false,
    });
};

// Form: Verifikasi Bayar Daftar Ulang
const paymentForm = useForm({
    paid_admission_amount: props.applicant.paid_admission_amount || (props.applicant.min_down_payment || 0),
    admission_payment_status: props.applicant.status === 'partial_paid' ? 'paid' : 'partial', // partial or paid
});

const openPaymentModal = (preferredStatus = null) => {
    const isPaid = preferredStatus === 'paid' || props.applicant.status === 'partial_paid';
    paymentForm.admission_payment_status = isPaid ? 'paid' : 'partial';
    paymentForm.paid_admission_amount = isPaid 
        ? (props.applicant.total_admission_fee || (props.setting?.admission_fee_total || 8500000))
        : (props.applicant.min_down_payment || ((props.setting?.admission_fee_total || 8500000) * 0.6));
    showPaymentModal.value = true;
};

const setPresetAmount = (type) => {
    if (type === 'partial') {
        paymentForm.admission_payment_status = 'partial';
        paymentForm.paid_admission_amount = props.applicant.min_down_payment || ((props.setting?.admission_fee_total || 8500000) * 0.6);
    } else {
        paymentForm.admission_payment_status = 'paid';
        paymentForm.paid_admission_amount = props.applicant.total_admission_fee || (props.setting?.admission_fee_total || 8500000);
    }
};

const submitPayment = () => {
    paymentForm.post(route('spmb.admin.verify-admission-payment', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => showPaymentModal.value = false,
    });
};

// Verifikasi QRIS Biaya Pendaftaran
const verifyQrisPayment = () => {
    if (confirm(`Verifikasi pembayaran QRIS pendaftaran untuk ${props.applicant.full_name}?`)) {
        router.post(route('spmb.admin.verify-payment', props.applicant.id));
    }
};

// Konversi ke Siswa Aktif
const enrollStudent = () => {
    const unitName = props.applicant.unit?.name || (isSmp.value ? 'SMP Namira' : 'SD Namira');
    if (confirm(`Pindahkan calon siswa ${props.applicant.full_name} menjadi Siswa Aktif resmi ${unitName}?`)) {
        router.post(route('spmb.admin.enroll-student', props.applicant.id));
    }
};

const page = usePage();
const canDelete = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    const role = page.props.auth?.user?.role;
    return roles.includes('super_admin_yayasan') || 
           roles.includes('admin_yayasan') || 
           roles.includes('admin_unit') || 
           ['super_admin_yayasan', 'admin_yayasan', 'admin_unit'].includes(role);
});

const deleteApplicant = () => {
    if (confirm(`PERINGATAN: Apakah Anda yakin ingin MENGHAPUS PERMANEN data calon siswa ${props.applicant.full_name} (${props.applicant.registration_number})?\n\nTindakan ini akan menghapus data pendaftaran dan seluruh berkas file yang tersimpan.`)) {
        router.delete(route('spmb.admin.destroy', props.applicant.id));
    }
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head :title="`Detail Pendaftar - ${applicant.full_name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6 max-w-6xl mx-auto">
            
            <!-- Breadcrumbs & Back -->
            <div class="flex items-center justify-between">
                <Link 
                    :href="route('spmb.admin.index')"
                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 transition"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span>Kembali ke Daftar Calon Siswa</span>
                </Link>

                <div class="flex items-center gap-2">
                    <a 
                        :href="applicant.wa_link"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow"
                    >
                        <ChatBubbleLeftRightIcon class="w-4 h-4" />
                        <span>Chat WhatsApp Ortu</span>
                    </a>

                    <a 
                        :href="route('spmb.applicant.print-card', applicant.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold transition border border-slate-300 dark:border-slate-700"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                        <span>Kartu Peserta (PDF)</span>
                    </a>
                </div>
            </div>

            <!-- Header Card with Status & Action Highlights -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-20 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 overflow-hidden shrink-0">
                        <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400">Foto</div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono font-bold text-xs text-emerald-700 dark:text-emerald-400">
                                {{ applicant.registration_number }}
                            </span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="['internal_tk', 'internal_sd', 'internal'].includes(applicant.category) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                            >
                                {{ applicant.category_label || (['internal_tk', 'internal_sd', 'internal'].includes(applicant.category) ? (isSmp ? 'Alumni SD Namira' : 'Alumni TK Namira') : 'Umum') }}
                            </span>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">
                            {{ applicant.full_name }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Wali: <strong class="text-slate-700 dark:text-slate-300">{{ applicant.father_name || applicant.mother_name }}</strong> 
                            ({{ applicant.parent_phone }})
                        </p>
                    </div>
                </div>

                <!-- Action Toolbar for Panitia -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- 1. Tombol Verifikasi QRIS -->
                    <button 
                        v-if="!applicant.registration_payment_verified_at"
                        @click="verifyQrisPayment"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow"
                    >
                        <CheckCircleIcon class="w-4 h-4" />
                        <span>Verifikasi QRIS Pendaftaran</span>
                    </button>

                    <!-- 2. Tombol Atur Jadwal -->
                    <button 
                        @click="showScheduleModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow"
                    >
                        <CalendarDaysIcon class="w-4 h-4" />
                        <span>{{ applicant.observation_date ? 'Ubah Jadwal Seleksi' : 'Atur Jadwal Seleksi' }}</span>
                    </button>

                    <!-- 3. Tombol Evaluasi / Hasil -->
                    <button 
                        @click="showEvaluationModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-700 hover:bg-purple-600 text-white text-xs font-bold shadow-xs transition"
                    >
                        <ClipboardDocumentCheckIcon class="w-4 h-4" />
                        <span>Input Hasil Evaluasi</span>
                    </button>

                    <!-- 4. Tombol Virtual Account -->
                    <button 
                        v-if="['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status)"
                        @click="showVaModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-600 text-white text-xs font-bold shadow-xs transition"
                    >
                        <CreditCardIcon class="w-4 h-4" />
                        <span>Nomor VA Bank Jatim</span>
                    </button>

                    <!-- 5. Tombol Verifikasi Bayar Daftar Ulang -->
                    <button 
                        v-if="['accepted', 'partial_paid'].includes(applicant.status)"
                        @click="openPaymentModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-bold shadow-xs transition"
                    >
                        <CreditCardIcon class="w-4 h-4" />
                        <span>Verifikasi Daftar Ulang</span>
                    </button>

                    <!-- 6. Tombol Konversi Siswa Resmi -->
                    <button 
                        v-if="['accepted', 'partial_paid', 'fully_paid'].includes(applicant.status) && applicant.status !== 'enrolled'"
                        @click="enrollStudent"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition"
                    >
                        <AcademicCapIcon class="w-4 h-4 text-[#fbbf24]" />
                        <span>Pindahkan ke Data Siswa Aktif</span>
                    </button>

                    <!-- 7. Tombol Edit Biodata -->
                    <button 
                        @click="showEditBiodataModal = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 text-xs font-bold shadow-xs transition"
                    >
                        <PencilSquareIcon class="w-4 h-4 text-emerald-600" />
                        <span>Edit Biodata</span>
                    </button>

                    <!-- 8. Tombol Reset Password Ortu -->
                    <button 
                        @click="showResetPasswordModal = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 text-xs font-bold shadow-xs transition"
                    >
                        <KeyIcon class="w-4 h-4" />
                        <span>Reset Password Ortu</span>
                    </button>

                    <!-- 9. Tombol Hapus Data Pendaftar (Super Admin & Admin) -->
                    <button 
                        v-if="canDelete"
                        @click="deleteApplicant"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs transition"
                        title="Hapus Data Calon Siswa Secara Permanen"
                    >
                        <TrashIcon class="w-4 h-4" />
                        <span>Hapus Pendaftar</span>
                    </button>
                </div>
            </div>

            <!-- Grid 2 Kolom: Detail Biodata & Dokumen -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Biodata Lengkap (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Biodata Calon Siswa -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                A. Identitas Calon Siswa
                            </h3>
                            <button
                                type="button"
                                @click="showEditBiodataModal = true"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold transition"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Edit Biodata</span>
                            </button>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                            <div>
                                <dt class="text-slate-400">Nama Lengkap</dt>
                                <dd class="font-bold text-slate-800 dark:text-slate-200">{{ applicant.full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Nama Panggilan</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.nickname || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Jenis Kelamin</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Tempat, Tanggal Lahir</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.birth_place }}, {{ applicant.birth_date || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">NIK Calon Siswa</dt>
                                <dd class="font-mono text-slate-800 dark:text-slate-200">{{ applicant.nik || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Asal TK / Sekolah</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.previous_school || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Anak Ke / Jumlah Saudara</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">Anak ke-{{ applicant.child_order || 1 }} dari {{ applicant.total_siblings || 1 }} bersaudara</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-slate-400">Catatan Khusus / Riwayat</dt>
                                <dd class="text-slate-700 dark:text-slate-300 italic">{{ applicant.special_notes || 'Tidak ada catatan khusus.' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Biodata Orang Tua -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                B. Data Orang Tua & Domisili
                            </h3>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="showResetPasswordModal = true"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-[11px] font-bold transition"
                                >
                                    <KeyIcon class="w-3.5 h-3.5" />
                                    <span>Reset Password</span>
                                </button>
                                <button
                                    type="button"
                                    @click="showEditBiodataModal = true"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold transition"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                    <span>Edit Data Ortu</span>
                                </button>
                            </div>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                            <div>
                                <dt class="text-slate-400">Nama Ayah</dt>
                                <dd class="font-bold text-slate-800 dark:text-slate-200">{{ applicant.father_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Pekerjaan Ayah</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.father_job || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Nama Ibu</dt>
                                <dd class="font-bold text-slate-800 dark:text-slate-200">{{ applicant.mother_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Pekerjaan Ibu</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ applicant.mother_job || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Nomor WhatsApp Akun</dt>
                                <dd class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ applicant.parent_phone }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Alamat Lengkap</dt>
                                <dd class="text-slate-800 dark:text-slate-200">{{ applicant.address }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Dua Agenda Offline Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-800">
                            C. Jadwal Agenda Offline
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <strong class="text-emerald-700 dark:text-emerald-400 block mb-1.5 font-bold">{{ isSmp ? '1. Seleksi Akademik (B. Indonesia & MTK)' : '1. Observasi Dasar Anak' }}</strong>
                                <p class="text-slate-700 dark:text-slate-300">Tgl: <strong>{{ applicant.observation_date || 'Belum diatur' }}</strong></p>
                                <p class="text-slate-700 dark:text-slate-300">Jam: {{ applicant.observation_time || '-' }}</p>
                                <p class="text-slate-700 dark:text-slate-300">Ruang: {{ applicant.observation_location || '-' }}</p>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <strong class="text-amber-700 dark:text-amber-400 block mb-1.5 font-bold">{{ isSmp ? '2. Observasi Calon Murid (Wawancara & Mengaji)' : '2. Psikotes Calon Siswa' }}</strong>
                                <p class="text-slate-700 dark:text-slate-300">Tgl: <strong>{{ applicant.psychotest_date || 'Belum diatur' }}</strong></p>
                                <p class="text-slate-700 dark:text-slate-300">Jam: {{ applicant.psychotest_time || '-' }}</p>
                                <p class="text-slate-700 dark:text-slate-300">Ruang: {{ applicant.psychotest_location || '-' }}</p>
                            </div>
                        </div>

                        <!-- Nilai Seleksi Akademik & Observasi jika ada -->
                        <div v-if="applicant.academic_score_details" class="p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-300 space-y-1.5">
                            <strong class="block font-bold">Hasil Penilaian Seleksi & Observasi:</strong>
                            <div class="text-[11px]">
                                Nilai Tes Akademik: <strong>{{ applicant.academic_score_details.academic_score ?? applicant.academic_score_details.bahasa_indonesia ?? '-' }}</strong>
                            </div>
                            <div v-if="applicant.academic_score_details.wawancara" class="text-[11px]">
                                Observasi Wawancara: <em>{{ applicant.academic_score_details.wawancara }}</em>
                            </div>
                            <div v-if="applicant.academic_score_details.mengaji" class="text-[11px]">
                                Observasi Mengaji: <em>{{ applicant.academic_score_details.mengaji }}</em>
                            </div>
                        </div>

                        <div v-if="applicant.evaluation_notes" class="p-3.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-xs text-purple-900 dark:text-purple-300">
                            <strong>Catatan Evaluasi / Observasi:</strong> {{ applicant.evaluation_notes }}
                        </div>
                    </div>

                </div>

                <!-- Kolom Kanan: Berkas Dokumen Upload (1 col) -->
                <div class="space-y-6">
                    
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-800">
                            Berkas Persyaratan
                        </h3>

                        <!-- 1. Pas Foto -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300 block mb-2">1. Pas Foto Calon Siswa</span>
                            <div class="w-24 h-32 rounded-xl bg-slate-200 dark:bg-slate-700 overflow-hidden border">
                                <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-center px-2 text-[10px]">{{ isSmp ? 'Menyusul (Tahap Lanjutan)' : 'Belum ada' }}</div>
                            </div>
                        </div>

                        <!-- 2. Kartu Keluarga -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">2. Kartu Keluarga (KK)</span>
                            <a v-if="applicant.family_card_url" :href="applicant.family_card_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic text-[11px]">{{ isSmp ? 'Menyusul (Wawancara Ortu)' : 'Belum ada' }}</span>
                        </div>

                        <!-- 3. Akta Lahir -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">3. Akta Kelahiran</span>
                            <a v-if="applicant.birth_cert_url" :href="applicant.birth_cert_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic text-[11px]">{{ isSmp ? 'Menyusul (Wawancara Ortu)' : 'Belum ada' }}</span>
                        </div>

                        <!-- 4. KTP Ortu -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">4. KTP Orang Tua</span>
                            <a v-if="applicant.parent_id_card_url" :href="applicant.parent_id_card_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic text-[11px]">{{ isSmp ? 'Menyusul (Wawancara Ortu)' : 'Belum ada' }}</span>
                        </div>

                        <!-- 5. Bukti QRIS / Transfer Pendaftaran Awal -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-700 dark:text-slate-300">5. Bukti Bayar Pendaftaran (Awal)</span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="applicant.registration_payment_verified_at ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ applicant.registration_payment_verified_at ? 'Valid' : 'Menunggu Cek' }}
                                </span>
                            </div>
                            <a v-if="applicant.registration_payment_proof_url" :href="applicant.registration_payment_proof_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline block text-center py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 mt-2">
                                Lihat Bukti Transfer Registrasi &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic block mt-1">Belum ada berkas</span>
                        </div>

                        <!-- 6. Bukti Bayar Daftar Ulang: Termin 1 (DP Minimal 60%) -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-700 dark:text-slate-300">6. Bukti Daftar Ulang: Termin 1 (DP 60%)</span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="applicant.admission_payment_verified_at ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : (applicant.re_registration_payment_proof_url ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300')"
                                >
                                    {{ applicant.admission_payment_verified_at ? 'Terverifikasi' : (applicant.re_registration_payment_proof_url ? 'Menunggu Cek' : 'Belum Ada') }}
                                </span>
                            </div>
                            <a v-if="applicant.re_registration_payment_proof_url" :href="applicant.re_registration_payment_proof_url" target="_blank" class="text-teal-700 dark:text-teal-400 font-bold hover:underline block text-center py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 mt-2">
                                Periksa Bukti Termin 1 &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic block mt-1">{{ ['accepted', 'partial_paid', 'fully_paid', 'enrolled'].includes(applicant.status) ? 'Menunggu upload dari orang tua' : 'Belum tahap daftar ulang' }}</span>
                        </div>

                        <!-- 7. Bukti Bayar Daftar Ulang: Termin 2 (Pelunasan 100%) -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-700 dark:text-slate-300">7. Bukti Pelunasan: Termin 2 (100%)</span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : (applicant.settlement_payment_proof_url ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300')"
                                >
                                    {{ applicant.settlement_payment_verified_at || applicant.status === 'fully_paid' ? 'Lunas 100%' : (applicant.settlement_payment_proof_url ? 'Menunggu Cek' : 'Belum Ada') }}
                                </span>
                            </div>
                            <a v-if="applicant.settlement_payment_proof_url" :href="applicant.settlement_payment_proof_url" target="_blank" class="text-indigo-700 dark:text-indigo-400 font-bold hover:underline block text-center py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 mt-2">
                                Periksa Bukti Pelunasan &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic block mt-1">
                                {{ applicant.status === 'partial_paid' ? 'Menunggu pelunasan dari orang tua' : (applicant.status === 'fully_paid' ? 'Lunas sekaligus pada pembayaran awal' : 'Belum tahap pelunasan') }}
                            </span>
                        </div>

                        <!-- Riwayat Histori Transaksi Pembayaran -->
                        <div v-if="applicant.payment_history && applicant.payment_history.length > 0" class="p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs space-y-2">
                            <span class="font-bold text-emerald-950 dark:text-emerald-300 block">Riwayat Transaksi Angsuran:</span>
                            <div v-for="(hist, idx) in applicant.payment_history" :key="idx" class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-emerald-100 dark:border-slate-700 text-[11px] space-y-0.5">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="text-slate-800 dark:text-slate-200">{{ hist.title }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="hist.status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                        {{ hist.status === 'verified' ? 'Valid' : 'Pending' }}
                                    </span>
                                </div>
                                <div class="text-slate-500 text-[10px]">Waktu Unggah: {{ hist.uploaded_at ? new Date(hist.uploaded_at).toLocaleString('id-ID') : '-' }}</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ================= MODAL 1: ATUR JADWAL ================= -->
            <div v-if="showScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Atur Jadwal Observasi & Psikotes</h3>
                    
                    <div class="space-y-3 text-xs">
                        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 space-y-2">
                            <strong class="text-emerald-800 dark:text-emerald-300 block">1. Jadwal Observasi Dasar</strong>
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model="scheduleForm.observation_date" type="date" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                                <input v-model="scheduleForm.observation_time" type="text" placeholder="Jam pelaksanaan" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                            </div>
                            <input v-model="scheduleForm.observation_location" type="text" placeholder="Ruangan / Lokasi" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                        </div>

                        <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 space-y-2">
                            <strong class="text-amber-800 dark:text-amber-300 block">2. Jadwal Psikotes Anak</strong>
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model="scheduleForm.psychotest_date" type="date" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                                <input v-model="scheduleForm.psychotest_time" type="text" placeholder="Jam pelaksanaan" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                            </div>
                            <input v-model="scheduleForm.psychotest_location" type="text" placeholder="Ruangan / Lokasi" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">Catatan Tambahan untuk Orang Tua</label>
                            <textarea v-model="scheduleForm.schedule_notes" rows="2" placeholder="Contoh: Harap membawa pensil warna dan berpakaian rapi" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-2 text-xs"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showScheduleModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                            Batal
                        </button>
                        <button type="button" @click="submitSchedule" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-500 shadow">
                            Simpan & Terbitkan Jadwal
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL 2: INPUT EVALUASI ================= -->
            <div v-if="showEvaluationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Input Hasil Seleksi & Evaluasi</h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Hasil Keputusan Seleksi</label>
                            <select v-model="evaluationForm.evaluation_result" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                                <option value="accepted">★ Diterima (Lulus)</option>
                                <option value="reserve">Cadangan</option>
                                <option value="rejected">Belum Diterima</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Catatan Hasil Observasi / Psikolog</label>
                            <textarea v-model="evaluationForm.evaluation_notes" rows="2" placeholder="Catatan umum kesiapan belajar anak..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-white"></textarea>
                        </div>

                        <!-- Nilai Seleksi Akademik & Observasi (Khusus SMP / Opsional) -->
                        <div class="p-3 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2.5">
                            <span class="block font-bold text-indigo-950 dark:text-indigo-300 text-xs">Penilaian Seleksi Akademik & Observasi:</span>
                            
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Nilai Tes Akademik (0-100)</label>
                                <input v-model="evaluationForm.academic_score" type="number" min="0" max="100" placeholder="Masukkan skor tunggal (0-100)..." class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Catatan Observasi Wawancara</label>
                                <input v-model="evaluationForm.wawancara_notes" type="text" placeholder="Catatan karakter & kesiapan..." class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Catatan Observasi Mengaji</label>
                                <input v-model="evaluationForm.mengaji_notes" type="text" placeholder="Kelancaran membaca Al-Qur'an..." class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Unggah Dokumen Rapor / Hasil Evaluasi (PDF)</label>
                            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="evaluationForm.evaluation_document = $event.target.files[0]" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showEvaluationModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                            Batal
                        </button>
                        <button type="button" @click="submitEvaluation" class="px-5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-500 shadow">
                            Simpan Hasil Evaluasi
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL 3: VIRTUAL ACCOUNT ================= -->
            <div v-if="showVaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Nomor Virtual Account Bank Jatim & Ketentuan</h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor Virtual Account (VA) Bank Jatim</label>
                            <input v-model="vaForm.virtual_account_number" type="text" placeholder="Contoh: 9881029100889901" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Total Biaya Masuk (Rp)</label>
                                <input v-model="vaForm.total_admission_fee" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Termin 1 - Min 60% (Rp)</label>
                                <input v-model="vaForm.min_down_payment" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Batas Bayar Termin 1</label>
                                <input v-model="vaForm.down_payment_deadline" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Batas Pelunasan 100%</label>
                                <input v-model="vaForm.full_payment_deadline" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showVaModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                            Batal
                        </button>
                        <button type="button" @click="submitVa" class="px-5 py-2 rounded-xl bg-teal-600 text-white text-xs font-bold hover:bg-teal-500 shadow">
                            Simpan Data VA
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL 4: VERIFIKASI DAFTAR ULANG ================= -->
            <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Verifikasi Pembayaran Daftar Ulang</h3>

                    <!-- Tautan Cek Bukti yang Terunggah -->
                    <div class="space-y-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block">Bukti Transfer yang Tersedia:</span>
                        <div class="flex flex-col gap-1.5 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span>1. Termin 1 (DP 60%):</span>
                                <a v-if="applicant.re_registration_payment_proof_url" :href="applicant.re_registration_payment_proof_url" target="_blank" class="font-bold text-teal-600 hover:underline">
                                    Buka File &rarr;
                                </a>
                                <span v-else class="text-slate-400 italic">Belum ada</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>2. Termin 2 (Pelunasan):</span>
                                <a v-if="applicant.settlement_payment_proof_url" :href="applicant.settlement_payment_proof_url" target="_blank" class="font-bold text-indigo-600 hover:underline">
                                    Buka File &rarr;
                                </a>
                                <span v-else class="text-slate-400 italic">Belum ada</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status Pembayaran</label>
                            <select v-model="paymentForm.admission_payment_status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                                <option value="partial">Pembayaran Termin 1 (Minimal 60%)</option>
                                <option value="paid">Lunas 100% (Tuntas)</option>
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-medium text-slate-700 dark:text-slate-300">Nominal Masuk (Rp)</label>
                                <div class="flex items-center gap-1.5 text-[10px]">
                                    <button type="button" @click="setPresetAmount('partial')" class="text-teal-700 dark:text-teal-400 font-bold hover:underline">Preset DP 60%</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="setPresetAmount('paid')" class="text-emerald-700 dark:text-emerald-400 font-bold hover:underline">Preset Lunas 100%</button>
                                </div>
                            </div>
                            <input v-model="paymentForm.paid_admission_amount" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-emerald-600">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showPaymentModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                            Batal
                        </button>
                        <button type="button" @click="submitPayment" class="px-5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500 shadow">
                            Konfirmasi Pembayaran
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL 5: EDIT BIODATA CALON SISWA ================= -->
            <div v-if="showEditBiodataModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm overflow-y-auto">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 my-8 max-h-[90vh] overflow-y-auto space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Biodata & Data Orang Tua</h3>
                            <p class="text-xs text-slate-500">Perbaiki kesalahan ketik biodata, kategori pendaftar, atau nomor WhatsApp akun tanpa menghapus data.</p>
                        </div>
                        <button type="button" @click="showEditBiodataModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEditBiodata" class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Calon Siswa *</label>
                                <input v-model="editBiodataForm.full_name" type="text" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                <input v-model="editBiodataForm.nickname" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin *</label>
                                <select v-model="editBiodataForm.gender" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir *</label>
                                <input v-model="editBiodataForm.birth_place" type="text" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir *</label>
                                <input v-model="editBiodataForm.birth_date" type="date" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Anak</label>
                                <input v-model="editBiodataForm.nik" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NISN</label>
                                <input v-model="editBiodataForm.nisn" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Pendaftar *</label>
                                <select v-model="editBiodataForm.category" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                                    <option :value="isSmp ? 'internal_sd' : 'internal_tk'">{{ isSmp ? 'Alumni SD Namira (Internal)' : 'Alumni TK Namira (Internal)' }}</option>
                                    <option value="external_umum">Pendaftar Umum (Eksternal)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Asal Sekolah</label>
                                <input v-model="editBiodataForm.previous_school" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ayah *</label>
                                <input v-model="editBiodataForm.father_name" type="text" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan Ayah</label>
                                <input v-model="editBiodataForm.father_job" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ibu *</label>
                                <input v-model="editBiodataForm.mother_name" type="text" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan Ibu</label>
                                <input v-model="editBiodataForm.mother_job" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp Akun Ortu (Login) *</label>
                                <input v-model="editBiodataForm.parent_phone" type="text" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                <p v-if="editBiodataForm.errors.parent_phone" class="text-rose-600 text-[11px] mt-1">{{ editBiodataForm.errors.parent_phone }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap *</label>
                                <textarea v-model="editBiodataForm.address" rows="2" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-white"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ganti Pas Foto 3x4 (Opsional)</label>
                                <input type="file" accept=".jpg,.jpeg,.png" @change="editBiodataForm.photo = $event.target.files[0]" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showEditBiodataModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                                Batal
                            </button>
                            <button type="submit" :disabled="editBiodataForm.processing" class="px-5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500 shadow">
                                {{ editBiodataForm.processing ? 'Menyimpan...' : 'Simpan Biodata' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================= MODAL 6: RESET PASSWORD ORANG TUA ================= -->
            <div v-if="showResetPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Reset Password Akun Orang Tua</h3>
                        <button type="button" @click="showResetPasswordModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Atur ulang password login Portal SPMB untuk orang tua dari <strong>{{ applicant.full_name }}</strong> (No. WA Login: <span class="font-mono font-bold text-emerald-600">{{ applicant.parent_phone }}</span>).
                    </p>

                    <form @submit.prevent="submitResetPassword" class="space-y-3 text-xs">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-semibold text-slate-700 dark:text-slate-300">Password Baru (Minimal 6 Karakter)</label>
                                <button
                                    type="button"
                                    @click="resetPasswordForm.new_password = applicant.parent_phone"
                                    class="text-[11px] font-bold text-emerald-600 hover:underline"
                                >
                                    Samakan dengan No. WA
                                </button>
                            </div>
                            <input
                                v-model="resetPasswordForm.new_password"
                                type="text"
                                required
                                minlength="6"
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-900 dark:text-white"
                            >
                            <p v-if="resetPasswordForm.errors.new_password" class="text-rose-600 text-[11px] mt-1">{{ resetPasswordForm.errors.new_password }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showResetPasswordModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                                Batal
                            </button>
                            <button type="submit" :disabled="resetPasswordForm.processing" class="px-5 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-500 shadow">
                                {{ resetPasswordForm.processing ? 'Mereset...' : 'Simpan Password Baru' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
