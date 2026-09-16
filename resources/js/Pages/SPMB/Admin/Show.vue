<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
    PhotoIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applicant: Object,
    setting: Object,
});

// Modals / Sections state
const showScheduleModal = ref(false);
const showEvaluationModal = ref(false);
const showVaModal = ref(false);
const showPaymentModal = ref(false);

// Form: Atur Jadwal
const scheduleForm = useForm({
    observation_date: props.applicant.observation_date || '',
    observation_time: props.applicant.observation_time || '08:00 - 10:00 WIB',
    observation_location: props.applicant.observation_location || 'Ruang Konseling & Bermain SD Namira',
    psychotest_date: props.applicant.psychotest_date || '',
    psychotest_time: props.applicant.psychotest_time || '08:00 - 11:30 WIB',
    psychotest_location: props.applicant.psychotest_location || 'Aula Pertemuan SD Namira',
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
    admission_payment_status: 'partial', // partial or paid
});

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
    if (confirm(`Pindahkan calon siswa ${props.applicant.full_name} menjadi Siswa Aktif resmi SD Namira?`)) {
        router.post(route('spmb.admin.enroll-student', props.applicant.id));
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
                                :class="applicant.category === 'internal_tk' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                            >
                                {{ applicant.category === 'internal_tk' ? 'Alumni TK Namira' : 'Umum' }}
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
                        @click="showPaymentModal = true"
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
                </div>
            </div>

            <!-- Grid 2 Kolom: Detail Biodata & Dokumen -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Biodata Lengkap (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Biodata Calon Siswa -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                        <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                            A. Identitas Calon Siswa
                        </h3>

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
                        <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                            B. Data Orang Tua & Domisili
                        </h3>

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
                                <strong class="text-emerald-700 dark:text-emerald-400 block mb-1.5 font-bold">1. Observasi Dasar Anak</strong>
                                <p class="text-slate-700 dark:text-slate-300">Tgl: <strong>{{ applicant.observation_date || 'Belum diatur' }}</strong></p>
                                <p class="text-slate-700 dark:text-slate-300">Jam: {{ applicant.observation_time || '-' }}</p>
                                <p class="text-slate-700 dark:text-slate-300">Ruang: {{ applicant.observation_location || '-' }}</p>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <strong class="text-amber-700 dark:text-amber-400 block mb-1.5 font-bold">2. Psikotes Calon Siswa</strong>
                                <p class="text-slate-700 dark:text-slate-300">Tgl: <strong>{{ applicant.psychotest_date || 'Belum diatur' }}</strong></p>
                                <p class="text-slate-700 dark:text-slate-300">Jam: {{ applicant.psychotest_time || '-' }}</p>
                                <p class="text-slate-700 dark:text-slate-300">Ruang: {{ applicant.psychotest_location || '-' }}</p>
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
                            <span class="font-bold text-slate-700 dark:text-slate-300 block mb-2">1. Pas Foto Latar Merah</span>
                            <div class="w-24 h-32 rounded-xl bg-slate-200 dark:bg-slate-700 overflow-hidden border">
                                <img v-if="applicant.photo_url" :src="applicant.photo_url" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">Belum ada</div>
                            </div>
                        </div>

                        <!-- 2. Kartu Keluarga -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">2. Kartu Keluarga (KK)</span>
                            <a v-if="applicant.family_card_url" :href="applicant.family_card_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic">Belum ada</span>
                        </div>

                        <!-- 3. Akta Lahir -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">3. Akta Kelahiran</span>
                            <a v-if="applicant.birth_cert_url" :href="applicant.birth_cert_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic">Belum ada</span>
                        </div>

                        <!-- 4. KTP Ortu -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                            <span class="font-bold text-slate-700 dark:text-slate-300">4. KTP Orang Tua</span>
                            <a v-if="applicant.parent_id_card_url" :href="applicant.parent_id_card_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                Buka File &rarr;
                            </a>
                            <span v-else class="text-slate-400 italic">Belum ada</span>
                        </div>

                        <!-- 5. Bukti QRIS Pendaftaran -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-700 dark:text-slate-300">5. Bukti Bayar QRIS</span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="applicant.registration_payment_verified_at ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ applicant.registration_payment_verified_at ? 'Valid' : 'Menunggu Cek' }}
                                </span>
                            </div>
                            <a v-if="applicant.registration_payment_proof_url" :href="applicant.registration_payment_proof_url" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline block text-center py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 mt-2">
                                Lihat Bukti Transfer QRIS &rarr;
                            </a>
                        </div>

                        <!-- 6. Bukti Bayar Daftar Ulang -->
                        <div v-if="applicant.re_registration_payment_proof_url" class="p-3.5 rounded-2xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-xs">
                            <span class="font-bold text-teal-800 dark:text-teal-300 block mb-1">Bukti Bayar Daftar Ulang VA</span>
                            <a :href="applicant.re_registration_payment_proof_url" target="_blank" class="text-teal-700 dark:text-teal-400 font-bold hover:underline block py-1">
                                Periksa Bukti Transfer VA &rarr;
                            </a>
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
                            <textarea v-model="evaluationForm.evaluation_notes" rows="3" placeholder="Catatan kesiapan belajar anak..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-white"></textarea>
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

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status Pembayaran</label>
                            <select v-model="paymentForm.admission_payment_status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                                <option value="partial">Pembayaran Termin 1 (Minimal 60%)</option>
                                <option value="paid">Lunas 100% (Tuntas)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nominal yang Sudah Masuk (Rp)</label>
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

        </div>
    </AuthenticatedLayout>
</template>
