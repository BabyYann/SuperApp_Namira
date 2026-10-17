<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, 
    FunnelIcon, 
    Cog6ToothIcon, 
    CheckCircleIcon,
    ClockIcon,
    CalendarDaysIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    UserGroupIcon,
    ChatBubbleLeftRightIcon,
    DocumentCheckIcon,
    CreditCardIcon,
    AcademicCapIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applicants: Object,
    stats: Object,
    filters: Object,
    unit: Object,
});

const isSmp = computed(() => {
    const name = (props.unit?.name || '').toLowerCase();
    const code = (props.unit?.code || '').toUpperCase();
    return code === 'SMP' || name.includes('smp');
});

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || 'all');
const status = ref(props.filters?.status || 'all');

// Quick Proof Modal
const showProofModal = ref(false);
const activeProofApplicant = ref(null);

const openProofModal = (app) => {
    activeProofApplicant.value = app;
    showProofModal.value = true;
};

// Quick Verify QRIS Registration
const quickVerifyQris = (app) => {
    if (confirm(`Verifikasi pembayaran biaya pendaftaran untuk ${app.full_name}?`)) {
        router.post(route('spmb.admin.verify-payment', app.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (activeProofApplicant.value?.id === app.id) {
                    activeProofApplicant.value.registration_payment_verified_at = new Date().toISOString();
                }
            }
        });
    }
};

// Quick Admission Payment Modal
const showQuickPaymentModal = ref(false);
const quickPaymentApp = ref(null);
const quickPaymentForm = useForm({
    paid_admission_amount: 0,
    admission_payment_status: 'partial',
});

const openQuickPaymentModal = (app, preferredStatus = null) => {
    quickPaymentApp.value = app;
    const isPaid = preferredStatus === 'paid' || app.status === 'partial_paid';
    quickPaymentForm.admission_payment_status = isPaid ? 'paid' : 'partial';
    quickPaymentForm.paid_admission_amount = isPaid 
        ? (app.total_admission_fee || 8500000)
        : (app.min_down_payment || ((app.total_admission_fee || 8500000) * 0.6));
    showQuickPaymentModal.value = true;
};

const setPresetQuickAmount = (type) => {
    if (!quickPaymentApp.value) return;
    if (type === 'partial') {
        quickPaymentForm.admission_payment_status = 'partial';
        quickPaymentForm.paid_admission_amount = quickPaymentApp.value.min_down_payment || ((quickPaymentApp.value.total_admission_fee || 8500000) * 0.6);
    } else {
        quickPaymentForm.admission_payment_status = 'paid';
        quickPaymentForm.paid_admission_amount = quickPaymentApp.value.total_admission_fee || 8500000;
    }
};

const submitQuickPayment = () => {
    if (!quickPaymentApp.value) return;
    quickPaymentForm.post(route('spmb.admin.verify-admission-payment', quickPaymentApp.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showQuickPaymentModal.value = false;
            showProofModal.value = false;
        }
    });
};

// Quick Enroll Student
const quickEnroll = (app) => {
    const unitName = app.unit?.name || 'Sekolah Namira';
    if (confirm(`Resmikan calon siswa ${app.full_name} menjadi Siswa Aktif ${unitName}?`)) {
        router.post(route('spmb.admin.enroll-student', app.id), {}, {
            preserveScroll: true,
        });
    }
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

let searchTimeout = null;

const applyFilters = () => {
    router.get(route('spmb.admin.index'), {
        search: search.value || undefined,
        category: category.value !== 'all' ? category.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([category, status], () => {
    applyFilters();
});

const getStatusBadgeClass = (s) => {
    return {
        'submitted': 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        'verified': 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-300 dark:border-blue-700',
        'scheduled': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700',
        'evaluated': 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-300 dark:border-purple-700',
        'accepted': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
        'reserve': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-300 border-yellow-300 dark:border-yellow-700',
        'rejected': 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-300 dark:border-rose-700',
        'partial_paid': 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border-teal-300 dark:border-teal-700',
        'fully_paid': 'bg-green-100 text-green-800 dark:bg-green-950/60 dark:text-green-300 border-green-300 dark:border-green-700',
        'enrolled': 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/80 dark:text-emerald-200 border-emerald-400 font-bold',
    }[s] || 'bg-slate-100 text-slate-800 border-slate-300';
};
</script>

<template>
    <Head :title="`Data Pendaftar SPMB Inden ${unit?.name || 'Namira'}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Top Header & Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <UserGroupIcon class="w-7 h-7 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Penerimaan Siswa Baru (SPMB Inden)</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola seleksi pendaftar, verifikasi pembayaran QRIS, jadwal observasi & psikotes, serta penetapan siswa aktif.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link 
                        :href="route('spmb.admin.settings')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition border border-slate-300 dark:border-slate-700"
                    >
                        <Cog6ToothIcon class="w-4 h-4 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Pengaturan & Panitia</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase">Total Pendaftar</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white block mt-1">{{ stats.total }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block uppercase">{{ isSmp ? 'Alumni SD Namira' : 'Alumni TK Namira' }}</span>
                    <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300 block mt-1">{{ stats.internal_tk || stats.internal }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 block uppercase">Pendaftar Umum</span>
                    <span class="text-2xl font-black text-blue-700 dark:text-blue-300 block mt-1">{{ stats.external_umum }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 block uppercase">Menunggu Cek QRIS</span>
                    <span class="text-2xl font-black text-amber-700 dark:text-amber-300 block mt-1">{{ stats.submitted }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 block uppercase">Lolos Seleksi</span>
                    <span class="text-2xl font-black text-purple-700 dark:text-purple-300 block mt-1">{{ stats.accepted }}</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block uppercase">Resmi Siswa Aktif</span>
                    <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300 block mt-1">{{ stats.enrolled }}</span>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari nama siswa, no. reg, no WA..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <!-- Filter Kategori Asal -->
                    <select 
                        v-model="category"
                        class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Kategori Asal</option>
                        <option value="internal">{{ isSmp ? 'Khusus Alumni SD Namira' : 'Khusus Alumni TK Namira' }}</option>
                        <option value="eksternal_umum">Pendaftar Luar (Umum)</option>
                    </select>

                    <!-- Filter Status -->
                    <select 
                        v-model="status"
                        class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Status</option>
                        <option value="submitted">Menunggu Verifikasi QRIS</option>
                        <option value="verified">Terverifikasi (Siap Jadwal)</option>
                        <option value="scheduled">Jadwal Diterbitkan</option>
                        <option value="accepted">Diterima (Lulus)</option>
                        <option value="reserve">Cadangan</option>
                        <option value="rejected">Belum Diterima</option>
                        <option value="partial_paid">Termin 60%</option>
                        <option value="fully_paid">Lunas 100%</option>
                        <option value="enrolled">Resmi Siswa</option>
                    </select>
                </div>
            </div>

            <!-- Applicants Table -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Calon Siswa</th>
                                <th class="py-3.5 px-4">No. Registrasi</th>
                                <th class="py-3.5 px-4">Asal Sekolah</th>
                                <th class="py-3.5 px-4">Orang Tua / Kontak</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Jadwal Observasi & Psikotes</th>
                                <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr 
                                v-for="app in applicants.data" 
                                :key="app.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <!-- Siswa -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-11 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700">
                                            <img v-if="app.photo_url" :src="app.photo_url" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-slate-400">Foto</div>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white block">{{ app.full_name }}</span>
                                            <span class="text-[11px] text-slate-400 block">
                                                {{ app.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}, {{ app.birth_place }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Reg Number -->
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ app.registration_number }}
                                    </span>
                                </td>

                                <!-- Asal Sekolah -->
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 block">
                                        {{ app.previous_school || '-' }}
                                    </span>
                                    <span 
                                        class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold mt-0.5"
                                        :class="['internal_tk', 'internal_sd', 'internal'].includes(app.category) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                                    >
                                        {{ ['internal_tk', 'internal_sd', 'internal'].includes(app.category) ? (isSmp ? 'SD Namira' : 'TK Namira') : 'Umum' }}
                                    </span>
                                </td>

                                <!-- Ortu & Kontak -->
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-900 dark:text-white font-medium block">
                                        {{ app.father_name || app.mother_name }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-mono block">
                                        {{ app.parent_phone }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                                        :class="getStatusBadgeClass(app.status)"
                                    >
                                        {{ app.status_label }}
                                    </span>
                                </td>

                                <!-- Jadwal -->
                                <td class="py-3.5 px-4">
                                    <div v-if="app.observation_date" class="text-[11px] space-y-0.5">
                                        <div class="text-slate-700 dark:text-slate-300 font-medium">
                                            Obs: {{ app.observation_date }} ({{ app.observation_time || '-' }})
                                        </div>
                                        <div v-if="app.psychotest_date" class="text-amber-600 dark:text-amber-400 font-medium">
                                            Psi: {{ app.psychotest_date }} ({{ app.psychotest_time || '-' }})
                                        </div>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Belum dijadwalkan</span>
                                </td>

                                <!-- Aksi Cepat -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5 flex-wrap max-w-[240px]">
                                        <!-- 1. Tombol Quick View Bukti Transfer -->
                                        <button 
                                            v-if="app.registration_payment_proof_url || app.re_registration_payment_proof_url || app.settlement_payment_proof_url"
                                            @click="openProofModal(app)"
                                            type="button"
                                            title="Periksa Seluruh Bukti Transfer (Pendaftaran, Termin 1, Pelunasan)"
                                            class="p-2 rounded-xl border transition shadow-xs relative"
                                            :class="!app.registration_payment_verified_at || (app.re_registration_payment_proof_url && !app.admission_payment_verified_at) || (app.settlement_payment_proof_url && !app.settlement_payment_verified_at)
                                                ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-700'
                                                : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'"
                                        >
                                            <DocumentCheckIcon class="w-4 h-4" />
                                            <span 
                                                v-if="!app.registration_payment_verified_at || (app.re_registration_payment_proof_url && !app.admission_payment_verified_at) || (app.settlement_payment_proof_url && !app.settlement_payment_verified_at)"
                                                class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"
                                            ></span>
                                        </button>

                                        <!-- 2. WhatsApp Icon Button -->
                                        <a 
                                            :href="app.wa_link"
                                            target="_blank"
                                            title="Chat WhatsApp Orang Tua"
                                            class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 transition shadow-xs"
                                        >
                                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        </a>

                                        <!-- 3. Tombol Kontekstual Berdasarkan Tahapan -->
                                        <!-- Verifikasi QRIS jika status submitted -->
                                        <button 
                                            v-if="app.status === 'submitted'"
                                            @click="quickVerifyQris(app)"
                                            type="button"
                                            title="Verifikasi Cepat Biaya Pendaftaran"
                                            class="px-2.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold transition shadow-xs inline-flex items-center gap-1"
                                        >
                                            <CheckCircleIcon class="w-3.5 h-3.5" />
                                            <span>Verif QRIS</span>
                                        </button>

                                        <!-- Verifikasi VA jika accepted / partial_paid dan ada bukti -->
                                        <button 
                                            v-else-if="['accepted', 'partial_paid'].includes(app.status) && (app.re_registration_payment_proof_url || app.settlement_payment_proof_url)"
                                            @click="openQuickPaymentModal(app)"
                                            type="button"
                                            title="Verifikasi Pembayaran Daftar Ulang VA"
                                            class="px-2.5 py-1.5 rounded-xl bg-teal-700 hover:bg-teal-600 text-white text-[11px] font-bold transition shadow-xs inline-flex items-center gap-1"
                                        >
                                            <CreditCardIcon class="w-3.5 h-3.5" />
                                            <span>{{ app.status === 'partial_paid' ? 'Verif Pelunasan' : 'Verif Termin 1' }}</span>
                                        </button>

                                        <!-- Jadikan Siswa jika fully_paid -->
                                        <button 
                                            v-else-if="['fully_paid'].includes(app.status)"
                                            @click="quickEnroll(app)"
                                            type="button"
                                            title="Pindahkan Resmi ke Siswa Aktif"
                                            class="px-2.5 py-1.5 rounded-xl bg-[#064e3b] hover:bg-emerald-800 text-white text-[11px] font-bold transition shadow-xs inline-flex items-center gap-1"
                                        >
                                            <AcademicCapIcon class="w-3.5 h-3.5 text-[#fbbf24]" />
                                            <span>Jadikan Siswa</span>
                                        </button>

                                        <!-- 4. Cetak Kartu Peserta Cepat -->
                                        <a 
                                            :href="route('spmb.applicant.print-card', app.id)"
                                            target="_blank"
                                            title="Cetak Kartu Tanda Peserta (PDF)"
                                            class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 transition shadow-xs"
                                        >
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                        </a>

                                        <!-- 5. Detail Button -->
                                        <Link 
                                            :href="route('spmb.admin.show', app.id)"
                                            title="Buka Lembar Detail Lengkap Calon Siswa"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold hover:bg-slate-800 transition shadow-xs"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5" />
                                            <span>Periksa</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="applicants.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    Tidak ada data calon siswa yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="applicants.total > applicants.per_page" class="p-4 border-t border-slate-200 dark:border-slate-800">
                    <Pagination :links="applicants.links" />
                </div>
            </div>

            <!-- ================= MODAL QUICK: BUKTI PEMBAYARAN ================= -->
            <div v-if="showProofModal && activeProofApplicant" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 font-mono">{{ activeProofApplicant.registration_number }}</span>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ activeProofApplicant.full_name }}</h3>
                            <p class="text-xs text-slate-500">Kumpulan berkas bukti transfer pendaftaran & daftar ulang.</p>
                        </div>
                        <button type="button" @click="showProofModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- 1. Bukti Pendaftaran QRIS -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-1">
                                <strong class="text-slate-800 dark:text-slate-200">1. Bukti Pembayaran Pendaftaran (Awal)</strong>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeProofApplicant.registration_payment_verified_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                    {{ activeProofApplicant.registration_payment_verified_at ? 'Valid' : 'Menunggu Cek' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <a v-if="activeProofApplicant.registration_payment_proof_url" :href="activeProofApplicant.registration_payment_proof_url" target="_blank" class="font-bold text-teal-700 dark:text-teal-400 hover:underline">
                                    Lihat File Bukti Registrasi &rarr;
                                </a>
                                <span v-else class="text-slate-400 italic">Belum ada</span>

                                <button 
                                    v-if="!activeProofApplicant.registration_payment_verified_at"
                                    type="button"
                                    @click="quickVerifyQris(activeProofApplicant)"
                                    class="px-3 py-1 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold shadow-xs"
                                >
                                    Verifikasi Sekarang
                                </button>
                            </div>
                        </div>

                        <!-- 2. Bukti Daftar Ulang Termin 1 -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-1">
                                <strong class="text-slate-800 dark:text-slate-200">2. Bukti Daftar Ulang: Termin 1 (DP 60%)</strong>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeProofApplicant.admission_payment_verified_at ? 'bg-emerald-100 text-emerald-800' : (activeProofApplicant.re_registration_payment_proof_url ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600')">
                                    {{ activeProofApplicant.admission_payment_verified_at ? 'Terverifikasi' : (activeProofApplicant.re_registration_payment_proof_url ? 'Menunggu Cek' : 'Belum Ada') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <a v-if="activeProofApplicant.re_registration_payment_proof_url" :href="activeProofApplicant.re_registration_payment_proof_url" target="_blank" class="font-bold text-teal-700 dark:text-teal-400 hover:underline">
                                    Lihat File Termin 1 &rarr;
                                </a>
                                <span v-else class="text-slate-400 italic">Belum diunggah ortu</span>

                                <button 
                                    v-if="activeProofApplicant.re_registration_payment_proof_url && !activeProofApplicant.admission_payment_verified_at"
                                    type="button"
                                    @click="openQuickPaymentModal(activeProofApplicant, 'partial')"
                                    class="px-3 py-1 rounded-lg bg-teal-700 hover:bg-teal-600 text-white text-[11px] font-bold shadow-xs"
                                >
                                    Verifikasi VA
                                </button>
                            </div>
                        </div>

                        <!-- 3. Bukti Daftar Ulang Termin 2 Pelunasan -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-1">
                                <strong class="text-slate-800 dark:text-slate-200">3. Bukti Daftar Ulang: Termin 2 (Pelunasan 100%)</strong>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeProofApplicant.settlement_payment_verified_at || activeProofApplicant.status === 'fully_paid' ? 'bg-emerald-100 text-emerald-800' : (activeProofApplicant.settlement_payment_proof_url ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600')">
                                    {{ activeProofApplicant.settlement_payment_verified_at || activeProofApplicant.status === 'fully_paid' ? 'Lunas 100%' : (activeProofApplicant.settlement_payment_proof_url ? 'Menunggu Cek' : 'Belum Ada') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <a v-if="activeProofApplicant.settlement_payment_proof_url" :href="activeProofApplicant.settlement_payment_proof_url" target="_blank" class="font-bold text-indigo-700 dark:text-indigo-400 hover:underline">
                                    Lihat File Pelunasan &rarr;
                                </a>
                                <span v-else class="text-slate-400 italic">{{ activeProofApplicant.status === 'partial_paid' ? 'Menunggu ortu transfer sisa' : 'Belum tahap pelunasan' }}</span>

                                <button 
                                    v-if="activeProofApplicant.settlement_payment_proof_url && activeProofApplicant.status !== 'fully_paid'"
                                    type="button"
                                    @click="openQuickPaymentModal(activeProofApplicant, 'paid')"
                                    class="px-3 py-1 rounded-lg bg-indigo-700 hover:bg-indigo-600 text-white text-[11px] font-bold shadow-xs"
                                >
                                    Verifikasi Pelunasan
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showProofModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-800">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL QUICK: VERIFIKASI VA ================= -->
            <div v-if="showQuickPaymentModal && quickPaymentApp" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Verifikasi Cepat Daftar Ulang VA</h3>
                    <p class="text-xs text-slate-500">Calon Siswa: <strong class="text-slate-800 dark:text-slate-200">{{ quickPaymentApp.full_name }}</strong> ({{ quickPaymentApp.registration_number }})</p>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status Pembayaran</label>
                            <select v-model="quickPaymentForm.admission_payment_status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs">
                                <option value="partial">Pembayaran Termin 1 (Minimal 60%)</option>
                                <option value="paid">Lunas 100% (Tuntas)</option>
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-medium text-slate-700 dark:text-slate-300">Nominal Masuk (Rp)</label>
                                <div class="flex items-center gap-1.5 text-[10px]">
                                    <button type="button" @click="setPresetQuickAmount('partial')" class="text-teal-700 dark:text-teal-400 font-bold hover:underline">Preset DP 60%</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="setPresetQuickAmount('paid')" class="text-emerald-700 dark:text-emerald-400 font-bold hover:underline">Preset Lunas 100%</button>
                                </div>
                            </div>
                            <input v-model="quickPaymentForm.paid_admission_amount" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-emerald-600">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showQuickPaymentModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700">
                            Batal
                        </button>
                        <button type="button" @click="submitQuickPayment" class="px-5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500 shadow">
                            Konfirmasi Pembayaran
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
