<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    ClipboardDocumentListIcon, EyeIcon, PaperClipIcon, ArrowDownTrayIcon,
    CheckCircleIcon, ClockIcon, ExclamationTriangleIcon, UserGroupIcon,
    CalendarDaysIcon, ChartBarIcon, DocumentTextIcon, XMarkIcon, ArrowPathIcon,
    FunnelIcon, AcademicCapIcon, BriefcaseIcon, PlusCircleIcon, UsersIcon, CalendarIcon,
    ChevronLeftIcon, ChevronRightIcon, CameraIcon, PencilSquareIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import axios from 'axios';
import NamiraLoader from '@/Components/NamiraLoader.vue';

dayjs.locale('id');

const props = defineProps({
    units: Array,
    subjects: Array,
    recapData: Array,
    stats: Object,
    filters: Object,
    workDays: Number,
});

// --- FILTER STATE ---
const month = ref(props.filters.month);
const year = ref(props.filters.year);
const unitId = ref(props.filters.unit_id);
const search = ref(props.filters.search);
const subjectId = ref(props.filters.subject_id);
const attendanceStatus = ref(props.filters.attendance_status);

// --- PAGINATION STATE ---
const perPage = ref(10);
const currentPage = ref(1);
const perPageOptions = [5, 10, 15, 20, 25, 50, 100];

// Reset page to 1 when filters or perPage change
watch([month, year, unitId, search, subjectId, attendanceStatus, perPage, () => props.recapData], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(props.recapData.length / perPage.value) || 1;
});

const paginatedRecapData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return props.recapData.slice(start, start + perPage.value);
});

const paginationInfo = computed(() => {
    const total = props.recapData.length;
    if (total === 0) return 'Menampilkan 0 data';
    const from = (currentPage.value - 1) * perPage.value + 1;
    const to = Math.min(currentPage.value * perPage.value, total);
    return `Menampilkan ${from} - ${to} dari ${total} data`;
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const applyFilters = () => {
    router.get(route('yayasan.attendance-data.index'), {
        month: month.value,
        year: year.value,
        unit_id: unitId.value,
        search: search.value,
        subject_id: subjectId.value,
        attendance_status: attendanceStatus.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    month.value = new Date().getMonth() + 1;
    year.value = new Date().getFullYear();
    unitId.value = 'all';
    search.value = '';
    subjectId.value = '';
    attendanceStatus.value = '';
    applyFilters();
};

const refreshData = () => {
    applyFilters();
};

// --- EXPORTS ---
const downloadingCsv = ref(false);
const downloadReport = () => {
    downloadingCsv.value = true;
    const url = route('yayasan.attendance-data.export', {
        month: month.value,
        year: year.value,
        unit_id: unitId.value,
        search: search.value,
        subject_id: subjectId.value,
        attendance_status: attendanceStatus.value
    });
    window.location.href = url;
    setTimeout(() => { downloadingCsv.value = false; }, 3000);
};

const downloadingPdf = ref(false);
const downloadPdf = () => {
    downloadingPdf.value = true;
    const url = route('yayasan.attendance-data.export-pdf', {
        month: month.value,
        year: year.value,
        unit_id: unitId.value,
        search: search.value,
        subject_id: subjectId.value,
        attendance_status: attendanceStatus.value
    });
    window.location.href = url;
    setTimeout(() => { downloadingPdf.value = false; }, 3000);
};

// --- INDIVIDUAL HISTORY DETAIL MODAL ---
const showDetailDrawer = ref(false);
const detailViewMode = ref('table'); // 'table' | 'correction' | 'activity'
const loadingDetail = ref(false);
const selectedEmployee = ref(null);
const employeeHistoryData = ref([]);
const employeeActivityLogs = ref([]);
const employeeStats = ref(null);
const detailMonth = ref(parseInt(props.filters.month) || dayjs().month() + 1);
const detailYear = ref(parseInt(props.filters.year) || dayjs().year());
const photoPreviewModalOpen = ref(false);
const selectedAttendanceForPhoto = ref(null);

const openPhotoPreview = (att) => {
    selectedAttendanceForPhoto.value = att;
    photoPreviewModalOpen.value = true;
};

const openEmployeeDetail = async (employee) => {
    selectedEmployee.value = employee;
    detailViewMode.value = 'table';
    showDetailDrawer.value = true;
    await fetchEmployeeHistory();
};

const fetchEmployeeHistory = async () => {
    if (!selectedEmployee.value) return;
    loadingDetail.value = true;
    try {
        const response = await axios.get(route('yayasan.attendance-data.employee-history', selectedEmployee.value.id), {
            params: {
                month: detailMonth.value,
                year: detailYear.value,
            }
        });
        employeeHistoryData.value = response.data.attendances;
        employeeStats.value = response.data.stats;
        employeeActivityLogs.value = response.data.activity_logs || [];
    } catch (error) {
        console.error('Failed to fetch employee history:', error);
    } finally {
        loadingDetail.value = false;
    }
};

const openCorrectionForDate = (date) => {
    manualForm.clearErrors();
    manualForm.user_id = selectedEmployee.value?.id || '';
    manualForm.date = dayjs(date).format('YYYY-MM-DD');
    manualForm.status = 'present';
    manualForm.check_in_time = '07:00';
    manualForm.check_out_time = '15:30';
    manualForm.late_minutes = 0;
    manualForm.note = '';
    detailViewMode.value = 'correction';
};

const downloadingIndividualPdf = ref(false);
const downloadIndividualPdf = () => {
    if (!selectedEmployee.value) return;
    downloadingIndividualPdf.value = true;
    const url = route('yayasan.attendance-data.export-individual-pdf', selectedEmployee.value.id) + 
                `?month=${detailMonth.value}&year=${detailYear.value}`;
    window.location.href = url;
    setTimeout(() => { downloadingIndividualPdf.value = false; }, 3000);
};

// --- AUTH ROLE CHECK ---
const page = usePage();
const isSuperAdmin = computed(() => {
    const roles = page.props.auth?.user?.roles?.map(r => typeof r === 'string' ? r : r.name) || [];
    return roles.includes('super_admin_yayasan');
});

// --- MANUAL ATTENDANCE ENTRY (SUPER ADMIN OVERRIDE) ---
const showManualModal = ref(false);
const manualForm = useForm({
    user_id: '',
    date: dayjs().format('YYYY-MM-DD'),
    status: 'present',
    check_in_time: '07:00',
    check_out_time: '15:30',
    late_minutes: 0,
    note: '',
});

const openManualEntryModal = (employee = null, prefillDate = null) => {
    manualForm.clearErrors();
    if (employee) {
        manualForm.user_id = employee.id;
    } else {
        manualForm.user_id = props.recapData.length > 0 ? props.recapData[0].id : '';
    }
    
    if (prefillDate) {
        manualForm.date = dayjs(prefillDate).format('YYYY-MM-DD');
    } else {
        manualForm.date = dayjs().format('YYYY-MM-DD');
    }
    
    manualForm.status = 'present';
    manualForm.check_in_time = '07:00';
    manualForm.check_out_time = '15:30';
    manualForm.late_minutes = 0;
    manualForm.note = '';
    showManualModal.value = true;
};

const submitManualEntry = () => {
    manualForm.post(route('yayasan.attendance-data.manual-entry'), {
        preserveScroll: true,
        onSuccess: () => {
            showManualModal.value = false;
            manualForm.reset();
            if (showDetailDrawer.value && selectedEmployee.value) {
                detailViewMode.value = 'table';
                fetchEmployeeHistory();
            }
        }
    });
};

// --- DATA HELPERS ---
const months = [
    { id: 1, name: 'Januari' }, { id: 2, name: 'Februari' }, { id: 3, name: 'Maret' },
    { id: 4, name: 'April' }, { id: 5, name: 'Mei' }, { id: 6, name: 'Juni' },
    { id: 7, name: 'Juli' }, { id: 8, name: 'Agustus' }, { id: 9, name: 'September' },
    { id: 10, name: 'Oktober' }, { id: 11, name: 'November' }, { id: 12, name: 'Desember' },
];

const statusConfig = {
    'present': { bg: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'Hadir WFO' },
    'late': { bg: 'bg-rose-50 text-rose-700 border-rose-200', label: 'Terlambat' },
    'business_trip': { bg: 'bg-indigo-50 text-indigo-700 border-indigo-200', label: 'Dinas Luar' },
    'sick': { bg: 'bg-blue-50 text-blue-700 border-blue-200', label: 'Sakit' },
    'permit': { bg: 'bg-amber-50 text-amber-700 border-amber-200', label: 'Izin' },
    'cuti': { bg: 'bg-violet-50 text-violet-700 border-violet-200', label: 'Cuti' },
    'alpha': { bg: 'bg-rose-100 text-rose-800 border-rose-300 font-extrabold', label: 'Alpha' },
    'weekend': { bg: 'bg-slate-100 text-slate-500 border-slate-200', label: 'Libur Minggu' },
    'holiday': { bg: 'bg-amber-100/80 text-amber-900 border-amber-300 font-bold', label: 'Libur Resmi' },
    'upcoming': { bg: 'bg-slate-50 text-slate-400 border-slate-200', label: 'Belum Berlangsung' },
    'not_recorded': { bg: 'bg-gray-50 text-gray-400 border-gray-150', label: 'Belum Absen' },
};

const getStatusClass = (status) => {
    const config = statusConfig[status] || statusConfig['not_recorded'];
    return `${config.bg} border`;
};

const getStatusLabel = (status) => {
    return statusConfig[status]?.label || status;
};

const getPercentageClass = (pct) => {
    if (pct >= 90) return 'text-emerald-600 bg-emerald-50 border border-emerald-100';
    if (pct >= 75) return 'text-amber-600 bg-amber-50 border border-amber-100';
    return 'text-rose-600 bg-rose-50 border border-rose-100';
};
</script>

<template>
    <Head title="Rekap Kehadiran" />

    <AuthenticatedLayout>
        <div class="space-y-8 pb-12">
            
            <!-- Breadcrumbs & Header Section -->
            <div class="border-b border-gray-100 pb-6">
                <!-- Breadcrumbs -->
                <nav class="flex text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 gap-2 items-center">
                    <span>Master Data</span>
                    <span>/</span>
                    <span>Data Absensi Karyawan</span>
                    <span>/</span>
                    <span class="text-namira-teal font-bold">Rekap Kehadiran</span>
                </nav>
                
                <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
                    Rekap Kehadiran Karyawan
                </h1>
                <p class="text-gray-500 mt-1.5 text-sm font-medium">
                    Laporan akumulasi kehadiran, keterlambatan, izin, sakit, cuti, dan alpha pegawai per periode.
                </p>
            </div>

            <!-- Summary Cards (Clean border style, responsive 11-column layout) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-11 gap-3">
                <!-- Total Pegawai -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-gray-400">
                        <span class="text-[9px] font-bold uppercase tracking-wider">Total Karyawan</span>
                        <UsersIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ stats.total_employees }}</div>
                </div>

                <!-- Hari Kerja -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-gray-400">
                        <span class="text-[9px] font-bold uppercase tracking-wider">Hari Kerja</span>
                        <CalendarIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ workDays }} <span class="text-xs font-semibold text-gray-400">Hari</span></div>
                </div>

                <!-- Hadir WFO -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-emerald-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Hadir WFO</span>
                        <CheckCircleIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">{{ stats.hadir }}</div>
                </div>

                <!-- Dinas Luar -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-indigo-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Dinas Luar</span>
                        <BriefcaseIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-indigo-600 mt-1">{{ stats.dinas_luar || 0 }}</div>
                </div>

                <!-- Terlambat -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-rose-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Terlambat</span>
                        <ClockIcon class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-rose-600">{{ stats.terlambat }}</div>
                        <div class="text-[10px] font-bold text-rose-400">{{ stats.late_minutes || 0 }}m akumulasi</div>
                    </div>
                </div>

                <!-- Izin -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-amber-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Izin</span>
                        <CalendarDaysIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-amber-600 mt-1">{{ stats.izin }}</div>
                </div>

                <!-- Sakit -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-blue-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Sakit</span>
                        <PlusCircleIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-1">{{ stats.sakit }}</div>
                </div>

                <!-- Cuti -->
                <div class="bg-white p-3.5 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-violet-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Cuti</span>
                        <AcademicCapIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-violet-600 mt-1">{{ stats.cuti }}</div>
                </div>

                <!-- Pending -->
                <div class="bg-amber-50/50 border border-amber-200 p-3.5 rounded-2xl shadow-sm flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-amber-700">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-amber-700">Pending</span>
                        <ExclamationTriangleIcon class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-amber-800">{{ stats.pending || 0 }}</div>
                        <div class="text-[9px] font-semibold text-amber-600 leading-tight">Menunggu verifikasi</div>
                    </div>
                </div>

                <!-- Alpha -->
                <div class="bg-red-50/40 border border-red-150 p-3.5 rounded-2xl shadow-sm flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-red-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-red-700">Alpha</span>
                        <XMarkIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-red-600 mt-1">{{ stats.alpha || 0 }}</div>
                </div>

                <!-- Avg Attendance -->
                <div class="bg-teal-50 border border-teal-150 p-3.5 rounded-2xl shadow-sm flex flex-col justify-between min-h-[96px]">
                    <div class="flex items-center justify-between text-teal-600">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-teal-700">Rata-rata</span>
                        <ChartBarIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-teal-700 mt-1">{{ stats.avg_attendance }}%</div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2 text-gray-700 font-bold border-b border-gray-100 pb-3">
                    <FunnelIcon class="w-5 h-5 text-gray-400" />
                    <span>Panel Penyaringan</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                    <!-- Periode: Bulan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Bulan</label>
                        <select v-model="month" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20">
                            <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <!-- Periode: Tahun -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Tahun</label>
                        <input v-model="year" type="number" min="2020" max="2035" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center" />
                    </div>

                    <!-- Unit Kerja -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Unit Sekolah</label>
                        <select v-model="unitId" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20">
                            <option value="all">Semua Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                    </div>

                    <!-- Nama Guru / NIP search -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama / NIP</label>
                        <input v-model="search" type="text" placeholder="Cari..." class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20" />
                    </div>

                    <!-- Mata Pelajaran -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Mata Pelajaran</label>
                        <select v-model="subjectId" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20">
                            <option value="">Semua Mapel</option>
                            <option v-for="subj in subjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
                        </select>
                    </div>

                    <!-- Status Kehadiran -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Tingkat Hadir</label>
                        <select v-model="attendanceStatus" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20">
                            <option value="">Semua</option>
                            <option value="excellent">Sangat Baik (≥90%)</option>
                            <option value="good">Baik (75% - 89%)</option>
                            <option value="poor">Kurang (&lt;75%)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <button 
                        @click="resetFilters" 
                        class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Reset Filter
                    </button>
                    <button 
                        @click="applyFilters" 
                        class="px-6 py-2 bg-namira-teal text-white rounded-xl text-sm font-bold hover:bg-teal-600 shadow transition-colors cursor-pointer"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>

            <!-- Table Roster -->
            <div class="bg-white rounded-3xl border border-gray-150 overflow-hidden shadow-sm">
                <!-- Table Header with title and buttons -->
                <div class="px-6 py-5 border-b border-gray-150 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">Daftar Rekap Kehadiran</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Akumulasi presensi, absensi, dan persentase kehadiran guru dan staf.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            v-if="isSuperAdmin"
                            @click="openManualEntryModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-namira-teal text-white rounded-xl font-bold text-xs shadow-sm hover:bg-teal-600 transition-all cursor-pointer active:scale-95"
                        >
                            <PlusCircleIcon class="w-4 h-4" />
                            <span>Input Presensi Manual</span>
                        </button>

                        <button 
                            @click="refreshData"
                            class="p-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-500 shadow-sm transition-all hover:shadow cursor-pointer"
                            title="Segarkan Data"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                        </button>
                        
                        <button 
                            @click="downloadReport" 
                            :disabled="downloadingCsv"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold text-xs shadow-sm hover:bg-gray-50 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <NamiraLoader :visible="downloadingCsv" variant="button" />
                            <ArrowDownTrayIcon v-if="!downloadingCsv" class="w-3.5 h-3.5 text-gray-500" />
                            <span>Export CSV</span>
                        </button>
                        
                        <button 
                            @click="downloadPdf" 
                            :disabled="downloadingPdf"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-xs shadow-sm hover:bg-rose-700 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <NamiraLoader :visible="downloadingPdf" variant="button" />
                            <DocumentTextIcon v-if="!downloadingPdf" class="w-3.5 h-3.5" />
                            <span>Export PDF (A4)</span>
                        </button>
                    </div>
                </div>
                <!-- Empty State -->
                <div v-if="recapData.length === 0" class="py-20 text-center max-w-md mx-auto space-y-4">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 border border-gray-100 text-gray-400 mb-2">
                        <ClipboardDocumentListIcon class="w-10 h-10" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 leading-tight">Data Rekapitulasi Kosong</h3>
                    <p class="text-gray-500 text-sm">
                        Tidak ada data guru atau karyawan yang sesuai dengan kombinasi penyaringan yang Anda lakukan saat ini.
                    </p>
                    <button @click="resetFilters" class="px-4 py-2 bg-namira-teal text-white font-bold rounded-xl text-xs hover:bg-teal-600 transition-colors">
                        Kembalikan Filter Default
                    </button>
                </div>

                <!-- Table Content (Desktop View) -->
                <div v-else class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm text-left">
                        <thead class="bg-slate-50 text-gray-700 uppercase text-xs font-bold border-b border-gray-150">
                            <tr>
                                <th class="px-4 py-4 text-center">No</th>
                                <th class="px-4 py-4">Karyawan / Guru</th>
                                <th class="px-4 py-4">NIP/NUPTK</th>
                                <th class="px-4 py-4">Jabatan</th>
                                <th class="px-4 py-4">Mata Pelajaran</th>
                                <th class="px-4 py-4">Unit Sekolah</th>
                                <th class="px-3 py-4 text-center">WFO</th>
                                <th class="px-3 py-4 text-center">Dinas</th>
                                <th class="px-3 py-4 text-center">Telat</th>
                                <th class="px-3 py-4 text-center">Izin</th>
                                <th class="px-3 py-4 text-center">Sakit</th>
                                <th class="px-3 py-4 text-center">Cuti</th>
                                <th class="px-3 py-4 text-center">Pending</th>
                                <th class="px-3 py-4 text-center">Alpha</th>
                                <th class="px-4 py-4 text-center">% Hadir</th>
                                <th class="px-4 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="(row, idx) in paginatedRecapData" :key="row.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3.5 text-center text-gray-400 font-semibold">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                
                                <!-- Photo & Name -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img v-if="row.photo" :src="row.photo" class="w-9 h-9 rounded-full object-cover border border-gray-100 shadow-sm" alt="Profile" />
                                        <div v-else class="w-9 h-9 rounded-full bg-slate-100 text-gray-400 flex items-center justify-center font-bold text-xs">
                                            {{ row.name.charAt(0) }}
                                        </div>
                                        <div class="font-extrabold text-gray-800 leading-snug">{{ row.name }}</div>
                                    </div>
                                </td>
                                
                                <!-- NIP -->
                                <td class="px-4 py-3.5 font-mono text-xs text-gray-600">{{ row.nip }}</td>
                                
                                <!-- Jabatan -->
                                <td class="px-4 py-3.5 font-semibold text-gray-700">{{ row.jabatan }}</td>
                                
                                <!-- Subjects -->
                                <td class="px-4 py-3.5 text-gray-500 text-xs truncate max-w-[150px]" :title="row.subjects">{{ row.subjects }}</td>
                                
                                <!-- Unit -->
                                <td class="px-4 py-3.5 text-gray-500 text-xs font-semibold">{{ row.unit_name }}</td>
                                
                                <!-- Present Counts -->
                                <td class="px-3 py-3.5 text-center font-bold text-emerald-600">{{ row.hadir }}</td>
                                <td class="px-3 py-3.5 text-center font-bold text-indigo-600">{{ row.dinas_luar || 0 }}</td>
                                <td class="px-3 py-3.5 text-center">
                                    <div class="font-bold text-rose-600 leading-tight">{{ row.terlambat }}</div>
                                    <div v-if="row.late_minutes > 0" class="text-[10px] text-rose-400 font-semibold mt-0.5">{{ row.late_minutes }}m</div>
                                </td>
                                <td class="px-3 py-3.5 text-center font-semibold text-amber-600">{{ row.izin }}</td>
                                <td class="px-3 py-3.5 text-center font-semibold text-blue-600">{{ row.sakit }}</td>
                                <td class="px-3 py-3.5 text-center font-semibold text-violet-600">{{ row.cuti }}</td>
                                <td class="px-3 py-3.5 text-center">
                                    <span v-if="(row.pending || 0) > 0" class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold inline-block">
                                        {{ row.pending }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="px-3 py-3.5 text-center font-bold">
                                    <span v-if="row.alpha > 0" class="px-2 py-0.5 rounded-lg bg-red-50 text-red-600 border border-red-200 text-xs font-bold inline-block">
                                        {{ row.alpha }}
                                    </span>
                                    <span v-else class="text-gray-300">0</span>
                                </td>
                                
                                <!-- Percentage -->
                                <td class="px-4 py-3.5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-xs font-black inline-block', getPercentageClass(row.percentage)]">
                                        {{ row.percentage }}%
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-center">
                                    <button 
                                        @click="openEmployeeDetail(row)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-namira-teal/10 hover:bg-namira-teal/20 text-namira-teal rounded-xl transition-all font-bold text-xs cursor-pointer shadow-sm"
                                        title="Detail Kehadiran Bulanan"
                                    >
                                        <ChartBarIcon class="w-4 h-4" />
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List View (Android / Mobile Native View) -->
                <div v-if="recapData.length > 0" class="block md:hidden divide-y divide-gray-150">
                    <div 
                        v-for="(row, idx) in paginatedRecapData" 
                        :key="'mobile-' + row.id" 
                        class="p-4 bg-white hover:bg-slate-50/50 transition-colors space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img v-if="row.photo" :src="row.photo" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm" alt="Profile" />
                                <div v-else class="w-10 h-10 rounded-full bg-slate-100 text-gray-500 font-bold flex items-center justify-center text-sm shadow-xs">
                                    {{ row.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-extrabold text-gray-900 text-sm leading-snug">{{ row.name }}</div>
                                    <div class="text-[11px] text-gray-500 font-mono">NIP: {{ row.nip || '-' }}</div>
                                    <div class="text-[11px] text-teal-700 font-semibold">{{ row.unit_name }} • {{ row.jabatan }}</div>
                                </div>
                            </div>
                            <span :class="['px-2.5 py-1 rounded-full text-xs font-black shrink-0', getPercentageClass(row.percentage)]">
                                {{ row.percentage }}%
                            </span>
                        </div>

                        <!-- Stats Grid inside Mobile Card -->
                        <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5 bg-slate-50 p-2.5 rounded-2xl border border-gray-150 text-center text-[11px]">
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">WFO</span>
                                <span class="font-extrabold text-emerald-600 text-xs">{{ row.hadir }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Dinas</span>
                                <span class="font-extrabold text-indigo-600 text-xs">{{ row.dinas_luar || 0 }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Telat</span>
                                <span class="font-extrabold text-rose-600 text-xs">{{ row.terlambat }}</span>
                                <span v-if="row.late_minutes > 0" class="text-[9px] text-rose-400 font-semibold block">({{ row.late_minutes }}m)</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Izin</span>
                                <span class="font-semibold text-amber-600 text-xs">{{ row.izin }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Sakit</span>
                                <span class="font-semibold text-blue-600 text-xs">{{ row.sakit }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Cuti</span>
                                <span class="font-semibold text-violet-600 text-xs">{{ row.cuti }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Pending</span>
                                <span class="font-bold text-amber-700 text-xs">{{ row.pending || 0 }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 block uppercase">Alpha</span>
                                <span class="font-extrabold text-red-600 text-xs">{{ row.alpha }}</span>
                            </div>
                        </div>

                        <!-- Mobile Action Button -->
                        <div class="flex justify-end pt-1">
                            <button 
                                @click="openEmployeeDetail(row)"
                                class="w-full py-2 bg-namira-teal/10 hover:bg-namira-teal/20 text-namira-teal rounded-xl font-extrabold text-xs flex items-center justify-center gap-1.5 transition-all active:scale-95"
                            >
                                <ChartBarIcon class="w-4 h-4" />
                                <span>Lihat Detail Kehadiran Bulanan</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Footer Pagination Bar -->
                <div v-if="recapData.length > 0" class="px-6 py-4 border-t border-gray-150 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-xs text-gray-600 font-medium">
                        <div class="flex items-center gap-2">
                            <span>Tampilkan</span>
                            <select 
                                v-model="perPage" 
                                class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-lg px-2.5 py-1.5 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 shadow-sm cursor-pointer"
                            >
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <span>data per halaman</span>
                        </div>
                        <span class="text-gray-300">|</span>
                        <span>{{ paginationInfo }}</span>
                    </div>

                    <!-- Page Navigation Buttons -->
                    <div class="flex items-center gap-1.5">
                        <button 
                            @click="goToPage(currentPage - 1)" 
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 hover:bg-white disabled:opacity-40 disabled:hover:bg-transparent transition-all shadow-sm flex items-center gap-1 cursor-pointer"
                        >
                            <ChevronLeftIcon class="w-3.5 h-3.5" />
                            <span>Sebelumnya</span>
                        </button>

                        <div class="flex items-center gap-1 px-1">
                            <button 
                                v-for="p in totalPages" 
                                :key="p" 
                                @click="goToPage(p)"
                                :class="[
                                    'w-8 h-8 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center justify-center',
                                    currentPage === p 
                                        ? 'bg-namira-teal text-white shadow-sm' 
                                        : 'text-gray-600 hover:bg-gray-100'
                                ]"
                            >
                                {{ p }}
                            </button>
                        </div>

                        <button 
                            @click="goToPage(currentPage + 1)" 
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 hover:bg-white disabled:opacity-40 disabled:hover:bg-transparent transition-all shadow-sm flex items-center gap-1 cursor-pointer"
                        >
                            <span>Selanjutnya</span>
                            <ChevronRightIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Kehadiran Individu (Center Modal Luas max-w-5xl) -->
            <div 
                v-if="showDetailDrawer" 
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/65 backdrop-blur-sm overflow-y-auto animate-fade-in"
                @click.self="showDetailDrawer = false"
            >
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-100 my-auto animate-fade-in-up">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-5 bg-slate-50 border-b border-slate-150 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <!-- Employee Identity -->
                        <div class="flex items-center gap-3.5 min-w-0">
                            <img v-if="selectedEmployee?.photo" :src="selectedEmployee.photo" class="w-13 h-13 rounded-2xl object-cover border-2 border-white shadow-md shrink-0" alt="Profile" />
                            <div v-else class="w-13 h-13 rounded-2xl bg-namira-teal/10 text-namira-teal flex items-center justify-center font-black text-xl border-2 border-white shadow-md shrink-0">
                                {{ selectedEmployee?.user_name?.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight leading-tight truncate">
                                        {{ selectedEmployee?.user_name }}
                                    </h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-namira-teal border border-teal-200 uppercase tracking-wider">
                                        {{ selectedEmployee?.role?.replace('_', ' ') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 font-medium mt-1 truncate">
                                    NIP/NUPTK: <span class="font-mono text-gray-700 font-semibold">{{ selectedEmployee?.nip || '-' }}</span> • Unit: <span class="text-gray-700 font-semibold">{{ selectedEmployee?.unit_name || '-' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Header Controls (Bulan, Tahun, Cetak PDF, Tutup) -->
                        <div class="flex items-center gap-2 flex-wrap justify-end">
                            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-2xl px-2.5 py-1 shadow-2xs">
                                <select 
                                    v-model="detailMonth" 
                                    @change="fetchEmployeeHistory" 
                                    class="bg-transparent border-0 text-xs font-bold text-gray-700 focus:ring-0 p-1 cursor-pointer"
                                >
                                    <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <span class="text-gray-300">/</span>
                                <input 
                                    type="number" 
                                    v-model="detailYear" 
                                    @change="fetchEmployeeHistory" 
                                    class="bg-transparent border-0 text-xs font-bold text-gray-700 focus:ring-0 p-1 w-16 text-center" 
                                />
                            </div>

                            <button 
                                @click="downloadIndividualPdf"
                                :disabled="loadingDetail || !employeeStats || downloadingIndividualPdf"
                                class="inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-400 text-white font-bold py-2 px-3.5 rounded-xl transition shadow-xs active:scale-95 disabled:opacity-50 cursor-pointer text-xs shrink-0"
                                title="Unduh Rapor Kehadiran PDF"
                            >
                                <NamiraLoader :visible="downloadingIndividualPdf" variant="button" />
                                <DocumentTextIcon v-if="!downloadingIndividualPdf" class="w-4 h-4" />
                                <span>{{ downloadingIndividualPdf ? 'Memproses...' : 'Cetak PDF' }}</span>
                            </button>

                            <button 
                                @click="showDetailDrawer = false" 
                                class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-slate-100 text-gray-400 hover:text-gray-700 flex items-center justify-center transition shadow-2xs cursor-pointer shrink-0"
                                title="Tutup"
                            >
                                <XMarkIcon class="w-5 h-5 stroke-[2]" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 bg-slate-50/30">
                        
                        <!-- Statistics Summary Cards -->
                        <div v-if="employeeStats" class="space-y-3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
                                <div class="bg-white border border-emerald-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-emerald-600">{{ employeeStats.present }}</div>
                                    <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mt-0.5">Hadir WFO</div>
                                </div>
                                <div class="bg-white border border-indigo-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-indigo-600">{{ employeeStats.business_trip || 0 }}</div>
                                    <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider mt-0.5">Dinas Luar</div>
                                </div>
                                <div class="bg-white border border-rose-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-rose-600">{{ employeeStats.late }}</div>
                                    <div class="text-[10px] font-bold text-rose-700 uppercase tracking-wider mt-0.5">Terlambat</div>
                                </div>
                                <div class="bg-white border border-amber-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-amber-600">{{ employeeStats.permit }}</div>
                                    <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mt-0.5">Izin</div>
                                </div>
                                <div class="bg-white border border-blue-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-blue-600">{{ employeeStats.sick }}</div>
                                    <div class="text-[10px] font-bold text-blue-700 uppercase tracking-wider mt-0.5">Sakit</div>
                                </div>
                                <div class="bg-white border border-violet-100 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-violet-600">{{ employeeStats.cuti || 0 }}</div>
                                    <div class="text-[10px] font-bold text-violet-700 uppercase tracking-wider mt-0.5">Cuti</div>
                                </div>
                                <div class="bg-white border border-amber-200 rounded-2xl p-3 text-center shadow-2xs">
                                    <div class="text-xl font-black text-amber-700">{{ employeeStats.pending || 0 }}</div>
                                    <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mt-0.5">Pending</div>
                                </div>
                                <div class="bg-white border border-rose-300 rounded-2xl p-3 text-center shadow-2xs bg-rose-50/20">
                                    <div class="text-xl font-black text-rose-600">{{ employeeStats.alpha || 0 }}</div>
                                    <div class="text-[10px] font-bold text-rose-700 uppercase tracking-wider mt-0.5">Alpha</div>
                                </div>
                            </div>

                            <!-- Highlights Bar: Efektif, Telat, Persentase -->
                            <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-2xs">
                                <div class="flex items-center justify-around divide-x divide-slate-100">
                                    <div class="text-center px-4">
                                        <div class="text-lg font-black text-slate-800">{{ employeeStats.work_days || 0 }} Hari</div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Hari Kerja Efektif</div>
                                    </div>
                                    <div class="text-center px-4">
                                        <div class="text-lg font-black text-rose-600">{{ employeeStats.total_late_minutes || 0 }}m</div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Akumulasi Telat</div>
                                    </div>
                                    <div class="text-center px-4">
                                        <div class="text-lg font-black text-namira-teal">{{ employeeStats.attendance_percentage }}%</div>
                                        <div class="text-[10px] font-bold text-namira-teal/80 uppercase tracking-wider mt-0.5">Persentase Hadir</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Segmented Navigation Tabs inside Modal -->
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 gap-3 flex-wrap">
                            <div class="bg-slate-100 p-1 rounded-2xl flex items-center gap-1">
                                <button
                                    @click="detailViewMode = 'table'"
                                    type="button"
                                    :class="[
                                        'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer',
                                        detailViewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'
                                    ]"
                                >
                                    <CalendarDaysIcon class="w-4 h-4" />
                                    <span>Catatan Kehadiran Harian</span>
                                    <span v-if="employeeHistoryData.length > 0" class="px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded-full text-[10px]">
                                        {{ employeeHistoryData.length }}
                                    </span>
                                </button>

                                <button
                                    v-if="employeeActivityLogs && employeeActivityLogs.length > 0"
                                    @click="detailViewMode = 'activity'"
                                    type="button"
                                    :class="[
                                        'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer',
                                        detailViewMode === 'activity' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'
                                    ]"
                                >
                                    <ClipboardDocumentListIcon class="w-4 h-4" />
                                    <span>Log Giat / Tugas</span>
                                    <span class="px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded-full text-[10px]">
                                        {{ employeeActivityLogs.length }}
                                    </span>
                                </button>
                            </div>

                            <button
                                v-if="isSuperAdmin && detailViewMode !== 'correction'"
                                @click="openCorrectionForDate(dayjs().format('YYYY-MM-DD'))"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-namira-teal hover:bg-teal-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer"
                            >
                                <PlusCircleIcon class="w-4 h-4" />
                                <span>Input Presensi Manual</span>
                            </button>
                            <button
                                v-else-if="isSuperAdmin && detailViewMode === 'correction'"
                                @click="detailViewMode = 'table'"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
                            >
                                <span>← Kembali ke Tabel Kehadiran</span>
                            </button>
                        </div>

                        <!-- TAB 1: CATATAN KEHADIRAN HARIAN (TABEL LEGA) -->
                        <div v-if="detailViewMode === 'table'" class="space-y-4">
                            <div v-if="loadingDetail" class="min-h-[280px] flex items-center justify-center">
                                <NamiraLoader :visible="true" variant="card" text="Memuat catatan harian..." />
                            </div>

                            <div v-else-if="employeeHistoryData.length === 0" class="py-12 border border-dashed border-gray-200 rounded-3xl text-center bg-white shadow-2xs">
                                <CalendarDaysIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                                <p class="text-xs text-gray-500 font-bold">Tidak ada data kehadiran untuk bulan ini</p>
                            </div>

                            <!-- The Full Chronological Table -->
                            <div v-else class="overflow-x-auto rounded-3xl border border-slate-200 shadow-xs bg-white">
                                <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                    <thead class="bg-slate-50/80 text-gray-600 font-bold border-b border-slate-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 text-center w-12">No</th>
                                            <th class="px-4 py-3 min-w-[150px]">Tanggal & Hari</th>
                                            <th class="px-4 py-3 text-center min-w-[80px]">Masuk</th>
                                            <th class="px-4 py-3 text-center min-w-[80px]">Pulang</th>
                                            <th class="px-4 py-3 text-center min-w-[80px]">Telat</th>
                                            <th class="px-4 py-3 min-w-[130px]">Status</th>
                                            <th class="px-4 py-3 text-center min-w-[90px]">Bukti</th>
                                            <th class="px-4 py-3 min-w-[200px]">Catatan / Keterangan</th>
                                            <th v-if="isSuperAdmin" class="px-4 py-3 text-center min-w-[100px]">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr 
                                            v-for="(att, idx) in employeeHistoryData" 
                                            :key="att.date || idx"
                                            :class="[
                                                'transition-colors',
                                                att.status === 'alpha' 
                                                    ? 'bg-rose-50/40 hover:bg-rose-50/70' 
                                                    : att.status === 'weekend' 
                                                        ? 'bg-slate-50/40 text-slate-400' 
                                                        : att.status === 'holiday' 
                                                            ? 'bg-amber-50/30' 
                                                            : 'hover:bg-slate-50/60'
                                            ]"
                                        >
                                            <!-- No -->
                                            <td class="px-4 py-3 text-center text-slate-400 font-mono text-[11px]">
                                                {{ idx + 1 }}
                                            </td>

                                            <!-- Tanggal & Hari -->
                                            <td class="px-4 py-3 font-medium">
                                                <div class="text-slate-900 font-bold">
                                                    {{ att.date_formatted || dayjs(att.date).format('DD MMM YYYY') }}
                                                </div>
                                                <div class="text-[11px] text-slate-400">
                                                    {{ att.day_name || dayjs(att.date).format('dddd') }}
                                                </div>
                                            </td>

                                            <!-- Jam Masuk -->
                                            <td class="px-4 py-3 text-center font-mono font-bold">
                                                <span v-if="att.check_in_time" class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100">
                                                    {{ att.check_in_time.substring(0, 5) }}
                                                </span>
                                                <span v-else class="text-slate-300">-</span>
                                            </td>

                                            <!-- Jam Pulang -->
                                            <td class="px-4 py-3 text-center font-mono font-bold">
                                                <span v-if="att.check_out_time" class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                                    {{ att.check_out_time.substring(0, 5) }}
                                                </span>
                                                <span v-else class="text-slate-300">-</span>
                                            </td>

                                            <!-- Telat -->
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="att.late_minutes > 0" class="text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100">
                                                    +{{ att.late_minutes }}m
                                                </span>
                                                <span v-else class="text-slate-300">-</span>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-4 py-3">
                                                <div class="flex flex-col gap-1 items-start">
                                                    <span :class="['px-2.5 py-0.5 rounded-lg text-[10px] font-bold inline-block border shadow-2xs', getStatusClass(att.status)]">
                                                        {{ getStatusLabel(att.status) }}
                                                    </span>
                                                    <span v-if="att.approval_status === 'pending'" class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                        Menunggu Verifikasi
                                                    </span>
                                                    <span v-else-if="att.approval_status === 'rejected'" class="text-[9px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">
                                                        Ditolak
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Bukti / Foto -->
                                            <td class="px-4 py-3 text-center">
                                                <button 
                                                    v-if="att.check_in_photo || att.check_out_photo"
                                                    @click="openPhotoPreview(att)"
                                                    type="button"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 text-[11px] font-bold transition active:scale-95 cursor-pointer shadow-2xs"
                                                    title="Lihat Foto Selfie"
                                                >
                                                    <CameraIcon class="w-3.5 h-3.5" />
                                                    <span>Foto</span>
                                                </button>
                                                <a 
                                                    v-else-if="att.permit_file" 
                                                    :href="`/storage/${att.permit_file}`" 
                                                    target="_blank" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-[11px] font-bold shadow-2xs"
                                                    title="Lihat Surat Lampiran"
                                                >
                                                    <PaperClipIcon class="w-3.5 h-3.5" />
                                                    <span>Dokumen</span>
                                                </a>
                                                <span v-else class="text-slate-300 font-mono text-[10px]">-</span>
                                            </td>

                                            <!-- Catatan / Keterangan -->
                                            <td class="px-4 py-3 text-slate-600 text-xs leading-relaxed" :title="att.note || ''">
                                                {{ att.note || '-' }}
                                            </td>

                                            <!-- Aksi (Khusus Super Admin) -->
                                            <td v-if="isSuperAdmin" class="px-4 py-3 text-center">
                                                <button 
                                                    v-if="att.status !== 'weekend' && att.status !== 'holiday' && att.status !== 'upcoming'"
                                                    @click="openCorrectionForDate(att.date)"
                                                    type="button"
                                                    :class="[
                                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold cursor-pointer transition active:scale-95 shadow-2xs',
                                                        att.status === 'alpha' 
                                                            ? 'bg-rose-600 hover:bg-rose-700 text-white' 
                                                            : 'bg-teal-50 text-namira-teal hover:bg-teal-100 border border-teal-200'
                                                    ]"
                                                    :title="att.status === 'alpha' ? 'Input kehadiran untuk tanggal Alpha ini' : 'Koreksi data presensi tanggal ini'"
                                                >
                                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                                    <span>{{ att.status === 'alpha' ? 'Input Hadir' : 'Koreksi' }}</span>
                                                </button>
                                                <span v-else class="text-slate-300">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: LOG GIAT / TUGAS -->
                        <div v-else-if="detailViewMode === 'activity'" class="space-y-3">
                            <div v-if="!employeeActivityLogs || employeeActivityLogs.length === 0" class="py-12 border border-dashed border-gray-200 rounded-3xl text-center bg-white shadow-2xs">
                                <ClipboardDocumentListIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                                <p class="text-xs text-gray-500 font-bold">Tidak ada log aktivitas tercatat untuk bulan ini</p>
                            </div>
                            <div v-else class="space-y-2.5">
                                <div 
                                    v-for="act in employeeActivityLogs" 
                                    :key="act.id" 
                                    class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">{{ act.title || act.activity_name }}</h4>
                                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ act.description || act.notes }}</p>
                                    </div>
                                    <span class="text-xs text-slate-400 font-mono shrink-0">
                                        {{ dayjs(act.activity_date).format('DD MMM YYYY') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: FORM KOREKSI TERINTEGRASI (TANPA MODAL BERTUMPUK) -->
                        <div v-else-if="detailViewMode === 'correction'" class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs space-y-5">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 leading-tight">
                                        Formulir Koreksi Presensi: {{ selectedEmployee?.user_name }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Sebagai Super Admin, kehadiran yang Anda simpan akan berstatus disetujui resmi dan menghapus penalti Alpha.
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-900 font-bold rounded-xl text-xs border border-amber-300">
                                    Khusus Super Admin
                                </span>
                            </div>

                            <form @submit.prevent="submitManualEntry" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Tanggal Presensi -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                            Tanggal Presensi <span class="text-rose-500">*</span>
                                        </label>
                                        <input 
                                            v-model="manualForm.date" 
                                            type="date" 
                                            :max="dayjs().format('YYYY-MM-DD')" 
                                            class="w-full bg-slate-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20"
                                            required 
                                        />
                                        <span v-if="manualForm.errors.date" class="text-[11px] text-rose-500 font-semibold mt-1 block">
                                            {{ manualForm.errors.date }}
                                        </span>
                                    </div>

                                    <!-- Status Kehadiran -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                            Status Kehadiran <span class="text-rose-500">*</span>
                                        </label>
                                        <select 
                                            v-model="manualForm.status" 
                                            class="w-full bg-slate-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20"
                                            required
                                        >
                                            <option value="present">🏢 Hadir WFO</option>
                                            <option value="business_trip">✈️ Dinas Luar</option>
                                            <option value="permit">📝 Izin</option>
                                            <option value="sick">🩺 Sakit</option>
                                            <option value="cuti">🏖️ Cuti</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Jam Masuk & Jam Pulang -->
                                <div v-if="['present', 'business_trip'].includes(manualForm.status)" class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam Masuk</label>
                                        <input 
                                            v-model="manualForm.check_in_time" 
                                            type="time" 
                                            class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center font-mono font-bold" 
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam Pulang</label>
                                        <input 
                                            v-model="manualForm.check_out_time" 
                                            type="time" 
                                            class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center font-mono font-bold" 
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Telat (Menit)</label>
                                        <input 
                                            v-model="manualForm.late_minutes" 
                                            type="number" 
                                            min="0"
                                            class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center font-bold" 
                                            placeholder="0"
                                        />
                                    </div>
                                </div>

                                <!-- Catatan Koreksi -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                        Catatan / Alasan Koreksi <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea 
                                        v-model="manualForm.note" 
                                        rows="3" 
                                        class="w-full bg-slate-50 border border-gray-200 rounded-xl text-xs p-3 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 resize-none font-medium"
                                        placeholder="Contoh: Guru hadir mengajar di sekolah, lupa absen karena kendala HP mati..."
                                        required
                                    ></textarea>
                                    <span v-if="manualForm.errors.note" class="text-[11px] text-rose-500 font-semibold mt-1 block">
                                        {{ manualForm.errors.note }}
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                                    <button 
                                        type="button" 
                                        @click="detailViewMode = 'table'" 
                                        class="px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-xs font-bold hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="submit" 
                                        :disabled="manualForm.processing"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-namira-teal hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition active:scale-95 disabled:opacity-50 cursor-pointer"
                                    >
                                        <NamiraLoader :visible="manualForm.processing" variant="button" />
                                        <CheckCircleIcon v-if="!manualForm.processing" class="w-4 h-4" />
                                        <span>{{ manualForm.processing ? 'Menyimpan...' : 'Simpan & Terapkan Koreksi' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Photo Preview Modal -->
            <div 
                v-if="photoPreviewModalOpen" 
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/70 p-4 backdrop-blur-xs animate-in fade-in duration-150" 
                @click.self="photoPreviewModalOpen = false"
            >
                <div class="bg-white rounded-3xl max-w-sm w-full overflow-hidden shadow-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h4 class="font-black text-slate-900 text-sm leading-tight">{{ selectedEmployee?.name }}</h4>
                            <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                {{ dayjs(selectedAttendanceForPhoto?.date).format('DD MMMM YYYY') }} • Masuk {{ selectedAttendanceForPhoto?.check_in_time ? selectedAttendanceForPhoto.check_in_time.substring(0, 5) : '-' }}
                            </p>
                        </div>
                        <button @click="photoPreviewModalOpen = false" class="p-1 rounded-full text-slate-400 hover:text-slate-600 transition">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Photo Image Preview (Masuk / Pulang) -->
                    <div class="space-y-3">
                        <div v-if="selectedAttendanceForPhoto?.check_in_photo" class="space-y-1">
                            <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                <CameraIcon class="w-3.5 h-3.5 text-teal-600" />
                                Foto Selfie Masuk ({{ selectedAttendanceForPhoto?.check_in_time ? selectedAttendanceForPhoto.check_in_time.substring(0, 5) : '-' }})
                            </span>
                            <div class="rounded-2xl overflow-hidden aspect-3/4 bg-slate-100 border border-slate-200 shadow-inner relative flex items-center justify-center">
                                <img 
                                    :src="`/storage/${selectedAttendanceForPhoto.check_in_photo}`" 
                                    :alt="selectedEmployee?.name"
                                    class="w-full h-full object-cover" 
                                />
                            </div>
                        </div>

                        <div v-if="selectedAttendanceForPhoto?.check_out_photo" class="space-y-1">
                            <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                <CameraIcon class="w-3.5 h-3.5 text-indigo-600" />
                                Foto Selfie Pulang ({{ selectedAttendanceForPhoto?.check_out_time ? selectedAttendanceForPhoto.check_out_time.substring(0, 5) : '-' }})
                            </span>
                            <div class="rounded-2xl overflow-hidden aspect-3/4 bg-slate-100 border border-slate-200 shadow-inner relative flex items-center justify-center">
                                <img 
                                    :src="`/storage/${selectedAttendanceForPhoto.check_out_photo}`" 
                                    :alt="selectedEmployee?.name"
                                    class="w-full h-full object-cover" 
                                />
                            </div>
                        </div>

                        <div v-if="!selectedAttendanceForPhoto?.check_in_photo && !selectedAttendanceForPhoto?.check_out_photo" class="py-10 text-center text-slate-400 text-xs font-bold">
                            Foto tidak tersedia
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <span class="font-bold text-teal-700 flex items-center gap-1">
                            <CheckCircleIcon class="w-4 h-4" />
                            <span>Verifikasi Kehadiran</span>
                        </span>
                        <a 
                            v-if="selectedAttendanceForPhoto?.check_in_photo"
                            :href="`/storage/${selectedAttendanceForPhoto.check_in_photo}`" 
                            target="_blank" 
                            class="text-teal-700 font-black hover:underline"
                        >
                            Buka Foto Asli ↗
                        </a>
                    </div>
                </div>
            </div>

            <!-- Manual Attendance Entry Modal (Super Admin Exclusive) -->
            <div 
                v-if="showManualModal" 
                class="fixed inset-0 z-60 flex items-center justify-center bg-black/70 p-4 backdrop-blur-xs animate-in fade-in duration-150" 
                @click.self="showManualModal = false"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl p-6 space-y-5">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-namira-teal/10 text-namira-teal flex items-center justify-center font-bold">
                                <PlusCircleIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-black text-slate-900 text-base leading-tight">Input Presensi Manual</h4>
                                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                    Koreksi kehadiran pegawai yang lupa absen / kendala teknis (Super Admin)
                                </p>
                            </div>
                        </div>
                        <button @click="showManualModal = false" class="p-1.5 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Form Body -->
                    <form @submit.prevent="submitManualEntry" class="space-y-4">
                        <!-- Pegawai / Guru Selection -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Pilih Karyawan / Guru <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                v-model="manualForm.user_id" 
                                class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20"
                                required
                            >
                                <option value="" disabled>-- Pilih Pegawai --</option>
                                <option v-for="emp in recapData" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} ({{ emp.nip || '-' }}) - {{ emp.unit_name }}
                                </option>
                            </select>
                            <span v-if="manualForm.errors.user_id" class="text-[11px] text-rose-500 font-semibold mt-1 block">
                                {{ manualForm.errors.user_id }}
                            </span>
                        </div>

                        <!-- Tanggal & Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Tanggal Presensi <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="manualForm.date" 
                                    type="date" 
                                    :max="dayjs().format('YYYY-MM-DD')" 
                                    class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20"
                                    required 
                                />
                                <span v-if="manualForm.errors.date" class="text-[11px] text-rose-500 font-semibold mt-1 block">
                                    {{ manualForm.errors.date }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Status Kehadiran <span class="text-rose-500">*</span>
                                </label>
                                <select 
                                    v-model="manualForm.status" 
                                    class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20"
                                    required
                                >
                                    <option value="present">Hadir WFO</option>
                                    <option value="business_trip">Dinas Luar</option>
                                    <option value="permit">Izin</option>
                                    <option value="sick">Sakit</option>
                                    <option value="cuti">Cuti</option>
                                </select>
                            </div>
                        </div>

                        <!-- Jam Masuk & Jam Pulang (Only if present / business_trip) -->
                        <div v-if="['present', 'business_trip'].includes(manualForm.status)" class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam Masuk</label>
                                <input 
                                    v-model="manualForm.check_in_time" 
                                    type="time" 
                                    class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center" 
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam Pulang</label>
                                <input 
                                    v-model="manualForm.check_out_time" 
                                    type="time" 
                                    class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center" 
                                />
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Telat (Menit)</label>
                                <input 
                                    v-model="manualForm.late_minutes" 
                                    type="number" 
                                    min="0"
                                    class="w-full bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center" 
                                    placeholder="0"
                                />
                            </div>
                        </div>

                        <!-- Catatan / Alasan Koreksi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Alasan / Keterangan Koreksi <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                v-model="manualForm.note" 
                                rows="3" 
                                class="w-full bg-white border border-gray-200 rounded-xl text-xs p-3 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 resize-none font-medium"
                                placeholder="Jelaskan alasan koreksi (misal: Guru hadir di sekolah namun kendala jaringan/lupa absen)..."
                                required
                            ></textarea>
                            <span v-if="manualForm.errors.note" class="text-[11px] text-rose-500 font-semibold mt-1 block">
                                {{ manualForm.errors.note }}
                            </span>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showManualModal = false" 
                                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="manualForm.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-namira-teal hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition active:scale-95 disabled:opacity-50 cursor-pointer"
                            >
                                <NamiraLoader :visible="manualForm.processing" variant="button" />
                                <CheckCircleIcon v-if="!manualForm.processing" class="w-4 h-4" />
                                <span>{{ manualForm.processing ? 'Menyimpan...' : 'Simpan & Setujui Presensi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
