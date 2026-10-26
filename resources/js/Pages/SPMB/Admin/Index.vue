<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
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
    XMarkIcon,
    TrashIcon,
    PencilSquareIcon,
    KeyIcon
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
    const unitName = app.unit?.name || (isSmp.value ? 'SMP Namira' : 'SD Namira');
    if (confirm(`Resmikan calon siswa ${app.full_name} menjadi Siswa Aktif ${unitName}?`)) {
        router.post(route('spmb.admin.enroll-student', app.id), {}, {
            preserveScroll: true,
        });
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

const deleteApplicant = (app) => {
    if (confirm(`PERINGATAN: Apakah Anda yakin ingin MENGHAPUS PERMANEN data calon siswa ${app.full_name} (${app.registration_number})?\n\nTindakan ini akan menghapus data pendaftar dan seluruh berkas/bukti transfer yang tersimpan.`)) {
        router.delete(route('spmb.admin.destroy', app.id), {
            preserveScroll: true,
        });
    }
};

// Quick Edit Biodata Modal from Table
const showEditBiodataModal = ref(false);
const activeEditApp = ref(null);
const editBiodataForm = useForm({
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
    category: 'external_umum',
    previous_school: '',
    special_notes: '',
    father_name: '',
    father_job: '',
    mother_name: '',
    mother_job: '',
    parent_phone: '',
    address: '',
    photo: null,
});

const openEditBiodataModal = (app) => {
    activeEditApp.value = app;
    editBiodataForm.full_name = app.full_name || '';
    editBiodataForm.nickname = app.nickname || '';
    editBiodataForm.gender = app.gender || 'L';
    editBiodataForm.nik = app.nik || '';
    editBiodataForm.nisn = app.nisn || '';
    editBiodataForm.birth_place = app.birth_place || '';
    editBiodataForm.birth_date = app.birth_date ? String(app.birth_date).substring(0, 10) : '';
    editBiodataForm.religion = app.religion || 'Islam';
    editBiodataForm.child_order = app.child_order || 1;
    editBiodataForm.total_siblings = app.total_siblings || 1;
    editBiodataForm.category = app.category || 'external_umum';
    editBiodataForm.previous_school = app.previous_school || '';
    editBiodataForm.special_notes = app.special_notes || '';
    editBiodataForm.father_name = app.father_name || '';
    editBiodataForm.father_job = app.father_job || '';
    editBiodataForm.mother_name = app.mother_name || '';
    editBiodataForm.mother_job = app.mother_job || '';
    editBiodataForm.parent_phone = app.parent_phone || '';
    editBiodataForm.address = app.address || '';
    editBiodataForm.photo = null;
    editBiodataForm.clearErrors();
    showEditBiodataModal.value = true;
};

const submitEditBiodata = () => {
    if (!activeEditApp.value) return;
    editBiodataForm.post(route('spmb.admin.update-biodata', activeEditApp.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showEditBiodataModal.value = false;
            editBiodataForm.photo = null;
        },
    });
};

const formatRupiah = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

const formatScheduleDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(d);
    } catch (e) {
        return dateStr;
    }
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
        <div class="space-y-3 md:space-y-6">
            
            <!-- 📱 MOBILE COMPACT TOP BAR (md:hidden) -->
            <div class="flex md:hidden items-center justify-between gap-2 bg-white dark:bg-slate-900 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                        <UserGroupIcon class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-xs font-black text-slate-900 dark:text-white tracking-tight truncate">
                                SPMB Inden {{ unit?.code || '' }}
                            </h1>
                            <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[9px] font-black">
                                {{ stats.total }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 truncate leading-none mt-0.5">
                            Seleksi, QRIS & jadwal observasi
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <a 
                        :href="route('spmb.admin.export', {
                            unit_id: unit?.id,
                            search: search || undefined,
                            category: category !== 'all' ? category : undefined,
                            status: status !== 'all' ? status : undefined,
                        })"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-extrabold transition shadow-2xs"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white" />
                        <span>CSV</span>
                    </a>

                    <Link 
                        :href="route('spmb.admin.settings')"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-[10px] font-extrabold border border-slate-200 dark:border-slate-700"
                    >
                        <Cog6ToothIcon class="w-3.5 h-3.5 text-emerald-700" />
                        <span>Atur</span>
                    </Link>
                </div>
            </div>

            <!-- 🖥️ DESKTOP Top Header & Action Buttons (hidden md:flex) -->
            <div class="hidden md:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <UserGroupIcon class="w-7 h-7 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Penerimaan Siswa Baru (SPMB Inden)</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola seleksi pendaftar, verifikasi pembayaran QRIS, jadwal observasi & psikotes, serta penetapan siswa aktif.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a 
                        :href="route('spmb.admin.export', {
                            unit_id: unit?.id,
                            search: search || undefined,
                            category: category !== 'all' ? category : undefined,
                            status: status !== 'all' ? status : undefined,
                        })"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4 text-white" />
                        <span>Export Excel / CSV</span>
                    </a>

                    <Link 
                        :href="route('spmb.admin.settings')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition border border-slate-300 dark:border-slate-700"
                    >
                        <Cog6ToothIcon class="w-4 h-4 text-[#064e3b] dark:text-[#fbbf24]" />
                        <span>Pengaturan & Panitia</span>
                    </Link>
                </div>
            </div>

            <!-- 📱 MOBILE ULTRA-COMPACT STATS STRIP (6 Kolom Klik-Filter dalam 1 Baris, md:hidden) -->
            <div class="grid md:hidden grid-cols-6 gap-1 bg-white dark:bg-slate-900 p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs text-center">
                <button 
                    type="button"
                    @click="category = 'all'; status = 'all'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="category === 'all' && status === 'all' ? 'bg-slate-900 text-white' : 'hover:bg-slate-50 text-slate-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.total }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5 opacity-80">Total</span>
                </button>

                <button 
                    type="button"
                    @click="category = category === 'internal' ? 'all' : 'internal'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="category === 'internal' ? 'bg-emerald-600 text-white' : 'hover:bg-emerald-50 text-emerald-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.internal_tk || stats.internal }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">{{ isSmp ? 'Alumni' : 'TK' }}</span>
                </button>

                <button 
                    type="button"
                    @click="category = category === 'eksternal_umum' ? 'all' : 'eksternal_umum'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="category === 'eksternal_umum' ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 text-blue-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.external_umum }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">Umum</span>
                </button>

                <button 
                    type="button"
                    @click="status = status === 'submitted' ? 'all' : 'submitted'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="status === 'submitted' ? 'bg-amber-500 text-white' : 'hover:bg-amber-50 text-amber-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.submitted }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">QRIS</span>
                </button>

                <button 
                    type="button"
                    @click="status = status === 'accepted' ? 'all' : 'accepted'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="status === 'accepted' ? 'bg-purple-600 text-white' : 'hover:bg-purple-50 text-purple-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.accepted }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">Lolos</span>
                </button>

                <button 
                    type="button"
                    @click="status = status === 'enrolled' ? 'all' : 'enrolled'"
                    class="py-1 px-0.5 rounded-lg transition"
                    :class="status === 'enrolled' ? 'bg-emerald-700 text-white' : 'hover:bg-emerald-50 text-emerald-700'"
                >
                    <span class="text-xs font-black block leading-none">{{ stats.enrolled }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter block mt-0.5">Aktif</span>
                </button>
            </div>

            <!-- 🖥️ DESKTOP Stats Bar (hidden md:grid) -->
            <div class="hidden md:grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
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

            <!-- Filters & Search Toolbar (Compact di Mobile, Normal di Desktop) -->
            <div class="bg-white dark:bg-slate-900 p-2.5 md:p-4 rounded-xl md:rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs md:shadow-sm flex flex-col md:flex-row items-center justify-between gap-2 md:gap-3">
                <div class="relative w-full md:w-80">
                    <MagnifyingGlassIcon class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari nama siswa, no. reg, no WA..."
                        class="w-full pl-8 pr-3 py-1.5 md:py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg md:rounded-xl text-[11px] md:text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                </div>

                <div class="grid grid-cols-2 md:flex items-center gap-1.5 md:gap-2.5 w-full md:w-auto">
                    <!-- Filter Kategori Asal -->
                    <select 
                        v-model="category"
                        class="w-full min-w-0 truncate bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg md:rounded-xl px-2.5 md:px-3 py-1.5 md:py-2 text-[11px] md:text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Kategori</option>
                        <option value="internal">{{ isSmp ? 'Alumni SD Namira' : 'Alumni TK Namira' }}</option>
                        <option value="eksternal_umum">Pendaftar Umum</option>
                    </select>

                    <!-- Filter Status -->
                    <select 
                        v-model="status"
                        class="w-full min-w-0 truncate bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg md:rounded-xl px-2.5 md:px-3 py-1.5 md:py-2 text-[11px] md:text-xs text-slate-800 dark:text-slate-200 focus:ring-emerald-500"
                    >
                        <option value="all">Semua Status</option>
                        <option value="submitted">Cek QRIS</option>
                        <option value="verified">Terverifikasi</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="accepted">Diterima (Lulus)</option>
                        <option value="reserve">Cadangan</option>
                        <option value="rejected">Belum Diterima</option>
                        <option value="partial_paid">Termin 60%</option>
                        <option value="fully_paid">Lunas 100%</option>
                        <option value="enrolled">Resmi Siswa</option>
                    </select>
                </div>
            </div>

            <!-- 📱 MOBILE PWA ULTRA-COMPACT LIST (block md:hidden) -->
            <div class="block md:hidden space-y-2">
                <div 
                    v-for="app in applicants.data" 
                    :key="`mobile-${app.id}`"
                    class="bg-white dark:bg-slate-900 rounded-xl p-2.5 border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-1.5"
                >
                    <!-- Baris 1: Avatar Kecil + Identitas Ringkas + Status Badge -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <div class="w-8 h-9 rounded-lg bg-emerald-50 dark:bg-slate-800 overflow-hidden shrink-0 border border-emerald-200/70 dark:border-slate-700 flex items-center justify-center">
                                <img v-if="app.photo_url" :src="app.photo_url" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] font-black text-emerald-700 dark:text-emerald-400">
                                    {{ (app.full_name || 'S').charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ app.registration_number }}
                                    </span>
                                    <span 
                                        class="px-1.5 py-0.2 rounded text-[8px] font-extrabold uppercase"
                                        :class="['internal_tk', 'internal_sd', 'internal'].includes(app.category) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ ['internal_tk', 'internal_sd', 'internal'].includes(app.category) ? (isSmp ? 'SD Namira' : 'TK Namira') : 'Umum' }}
                                    </span>
                                </div>
                                <h4 class="font-extrabold text-xs text-slate-900 dark:text-white leading-tight truncate mt-0.5">
                                    {{ app.full_name }}
                                </h4>
                                <p class="text-[10px] text-slate-500 truncate leading-tight">
                                    {{ app.gender === 'L' ? 'L' : 'P' }} • {{ app.previous_school || '-' }}
                                </p>
                            </div>
                        </div>

                        <span 
                            class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold border leading-none"
                            :class="getStatusBadgeClass(app.status)"
                        >
                            {{ app.status_label }}
                        </span>
                    </div>

                    <!-- Baris 2: Info Ortu/Jadwal di Kiri + 1 Baris Action Buttons di Kanan (Tanpa Turun Baris!) -->
                    <div class="flex items-center justify-between gap-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-800">
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate">
                                {{ app.father_name || app.mother_name || '-' }}
                                <span class="font-mono font-normal text-slate-400">• {{ app.parent_phone }}</span>
                            </p>
                            <p class="text-[9px] text-slate-400 truncate mt-0.5">
                                <template v-if="app.observation_date">
                                    Obs: <span class="text-slate-600 font-semibold">{{ formatScheduleDate(app.observation_date) }}</span>
                                    <template v-if="app.psychotest_date"> • Psi: <span class="text-amber-600 font-semibold">{{ formatScheduleDate(app.psychotest_date) }}</span></template>
                                </template>
                                <template v-else>Belum dijadwalkan</template>
                            </p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <!-- Detail -->
                            <Link 
                                :href="route('spmb.admin.show', app.id)"
                                class="h-7 px-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold inline-flex items-center gap-1 shadow-2xs"
                            >
                                <EyeIcon class="w-3.5 h-3.5" />
                                <span>Detail</span>
                            </Link>

                            <!-- Edit Biodata -->
                            <button 
                                @click="openEditBiodataModal(app)"
                                type="button"
                                title="Edit Biodata"
                                class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                            </button>

                            <!-- Bukti Transfer -->
                            <button 
                                v-if="app.registration_payment_proof_url || app.re_registration_payment_proof_url || app.settlement_payment_proof_url"
                                @click="openProofModal(app)"
                                type="button"
                                title="Cek Bukti Transfer"
                                class="w-7 h-7 rounded-lg border flex items-center justify-center relative"
                                :class="!app.registration_payment_verified_at || (app.re_registration_payment_proof_url && !app.admission_payment_verified_at) || (app.settlement_payment_proof_url && !app.settlement_payment_verified_at)
                                    ? 'bg-amber-50 text-amber-700 border-amber-300'
                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                            >
                                <DocumentCheckIcon class="w-3.5 h-3.5" />
                                <span 
                                    v-if="!app.registration_payment_verified_at || (app.re_registration_payment_proof_url && !app.admission_payment_verified_at) || (app.settlement_payment_proof_url && !app.settlement_payment_verified_at)"
                                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-amber-500 animate-pulse"
                                ></span>
                            </button>

                            <!-- WA Ortu -->
                            <a 
                                :href="app.wa_link"
                                target="_blank"
                                title="WA Orang Tua"
                                class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center"
                            >
                                <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />
                            </a>

                            <!-- Kartu PDF -->
                            <a 
                                :href="route('spmb.applicant.print-card', app.id)"
                                target="_blank"
                                title="Kartu Peserta PDF"
                                class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-300 flex items-center justify-center"
                            >
                                <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                            </a>

                            <!-- Hapus -->
                            <button 
                                v-if="canDelete"
                                @click="deleteApplicant(app)"
                                type="button"
                                title="Hapus"
                                class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="applicants.data.length === 0" class="bg-white rounded-xl p-4 text-center text-slate-400 text-xs border border-slate-200">
                    Tidak ada data calon siswa yang sesuai dengan filter pencarian.
                </div>

                <div v-if="applicants.total > applicants.per_page" class="pt-1 flex justify-center">
                    <Pagination :links="applicants.links" />
                </div>
            </div>

            <!-- 🖥️ DESKTOP APPLICANTS TABLE (hidden md:block) -->
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
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
                                            Obs: {{ formatScheduleDate(app.observation_date) }} ({{ app.observation_time || '-' }})
                                        </div>
                                        <div v-if="app.psychotest_date" class="text-amber-600 dark:text-amber-400 font-medium">
                                            Psi: {{ formatScheduleDate(app.psychotest_date) }} ({{ app.psychotest_time || '-' }})
                                        </div>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Belum dijadwalkan</span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                                        <!-- 1. Detail Button (Periksa) -->
                                        <Link 
                                            :href="route('spmb.admin.show', app.id)"
                                            title="Buka Lembar Detail Calon Siswa"
                                            class="w-8.5 h-8.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 flex items-center justify-center transition shadow-xs"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                        </Link>

                                        <!-- 1b. Quick Edit Biodata Button -->
                                        <button 
                                            @click="openEditBiodataModal(app)"
                                            type="button"
                                            title="Edit Biodata Calon Siswa & Orang Tua"
                                            class="w-8.5 h-8.5 rounded-xl bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 flex items-center justify-center transition shadow-xs"
                                        >
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </button>

                                        <!-- 2. Bukti Transfer Modal -->
                                        <button 
                                            v-if="app.registration_payment_proof_url || app.re_registration_payment_proof_url || app.settlement_payment_proof_url"
                                            @click="openProofModal(app)"
                                            type="button"
                                            title="Periksa Seluruh Bukti Transfer (Pendaftaran, Termin 1, Pelunasan)"
                                            class="w-8.5 h-8.5 rounded-xl border flex items-center justify-center transition shadow-xs relative"
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

                                        <!-- 3. WhatsApp Orang Tua -->
                                        <a 
                                            :href="app.wa_link"
                                            target="_blank"
                                            title="Hubungi WhatsApp Orang Tua"
                                            class="w-8.5 h-8.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center transition shadow-xs"
                                        >
                                            <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        </a>

                                        <!-- 4. Cetak Kartu Peserta (PDF) -->
                                        <a 
                                            :href="route('spmb.applicant.print-card', app.id)"
                                            target="_blank"
                                            title="Cetak Kartu Tanda Peserta (PDF)"
                                            class="w-8.5 h-8.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 flex items-center justify-center transition shadow-xs"
                                        >
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                        </a>

                                        <!-- 5. Resmikan Menjadi Siswa Aktif (Quick Action jika Lunas 100%) -->
                                        <button 
                                            v-if="app.status === 'fully_paid'"
                                            @click="quickEnroll(app)"
                                            type="button"
                                            title="Pindahkan & Resmikan Calon Siswa Ini Menjadi Siswa Aktif"
                                            class="w-8.5 h-8.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white flex items-center justify-center transition shadow-xs"
                                        >
                                            <AcademicCapIcon class="w-4 h-4 text-amber-300" />
                                        </button>
                                        <span 
                                            v-else-if="app.status === 'enrolled'"
                                            title="Sudah Diresmikan Sebagai Siswa Aktif"
                                            class="w-8.5 h-8.5 rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center cursor-default"
                                        >
                                            <AcademicCapIcon class="w-4 h-4" />
                                        </span>

                                        <!-- 6. Hapus Data Calon Siswa (Super Admin & Admin) -->
                                        <button 
                                            v-if="canDelete"
                                            @click="deleteApplicant(app)"
                                            type="button"
                                            title="Hapus Data Calon Siswa (Permanen)"
                                            class="w-8.5 h-8.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 flex items-center justify-center transition shadow-xs"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
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

                        <!-- 4. Resmikan Jadi Siswa jika Lunas 100% -->
                        <div v-if="activeProofApplicant.status === 'fully_paid'" class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
                            <div>
                                <strong class="text-emerald-900 dark:text-emerald-200 block text-xs">Biaya Daftar Ulang Lunas (100%)</strong>
                                <span class="text-[11px] text-emerald-700 dark:text-emerald-400">Calon santri siap diresmikan ke data siswa aktif.</span>
                            </div>
                            <button 
                                type="button" 
                                @click="quickEnroll(activeProofApplicant)" 
                                class="px-3.5 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-bold shadow-xs inline-flex items-center gap-1.5"
                            >
                                <AcademicCapIcon class="w-3.5 h-3.5" />
                                <span>Resmikan Jadi Siswa</span>
                            </button>
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

            <!-- ================= MODAL QUICK: EDIT BIODATA CALON SISWA ================= -->
            <div v-if="showEditBiodataModal && activeEditApp" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm overflow-y-auto">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 my-8 max-h-[90vh] overflow-y-auto space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 font-mono">{{ activeEditApp.registration_number }}</span>
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

        </div>
    </AuthenticatedLayout>
</template>
