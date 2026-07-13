<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ClipboardDocumentListIcon, EyeIcon, PaperClipIcon, ArrowDownTrayIcon,
    CheckCircleIcon, ClockIcon, ExclamationTriangleIcon, UserGroupIcon,
    CalendarDaysIcon, ChartBarIcon, DocumentTextIcon, XMarkIcon, ArrowPathIcon,
    FunnelIcon, AcademicCapIcon, BriefcaseIcon, PlusCircleIcon, UsersIcon, CalendarIcon
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

// --- INDIVIDUAL HISTORY DETAIL DRAWER ---
const showDetailDrawer = ref(false);
const loadingDetail = ref(false);
const selectedEmployee = ref(null);
const employeeHistoryData = ref([]);
const employeeStats = ref(null);
const detailMonth = ref(parseInt(props.filters.month) || dayjs().month() + 1);
const detailYear = ref(parseInt(props.filters.year) || dayjs().year());

const openEmployeeDetail = async (employee) => {
    selectedEmployee.value = employee;
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
    } catch (error) {
        console.error('Failed to fetch employee history:', error);
    } finally {
        loadingDetail.value = false;
    }
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

// --- DATA HELPERS ---
const months = [
    { id: 1, name: 'Januari' }, { id: 2, name: 'Februari' }, { id: 3, name: 'Maret' },
    { id: 4, name: 'April' }, { id: 5, name: 'Mei' }, { id: 6, name: 'Juni' },
    { id: 7, name: 'Juli' }, { id: 8, name: 'Agustus' }, { id: 9, name: 'September' },
    { id: 10, name: 'Oktober' }, { id: 11, name: 'November' }, { id: 12, name: 'Desember' },
];

const statusConfig = {
    'present': { bg: 'bg-emerald-50 text-emerald-700 border-emerald-100', label: 'Hadir WFO' },
    'late': { bg: 'bg-rose-50 text-rose-700 border-rose-100', label: 'Terlambat' },
    'business_trip': { bg: 'bg-indigo-50 text-indigo-700 border-indigo-100', label: 'Dinas Luar' },
    'sick': { bg: 'bg-blue-50 text-blue-700 border-blue-100', label: 'Sakit' },
    'permit': { bg: 'bg-amber-50 text-amber-700 border-amber-100', label: 'Izin' },
    'cuti': { bg: 'bg-violet-50 text-violet-700 border-violet-100', label: 'Cuti' },
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

            <!-- Summary Cards (Lucide & Heroicons, No Emojis, clean border style) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
                <!-- Total Pegawai -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-gray-400">
                        <span class="text-[10px] font-bold uppercase tracking-wider">Total Karyawan</span>
                        <UsersIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2">{{ stats.total_employees }}</div>
                </div>

                <!-- Hari Kerja -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-gray-400">
                        <span class="text-[10px] font-bold uppercase tracking-wider">Hari Kerja</span>
                        <CalendarIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2">{{ workDays }} <span class="text-xs font-semibold text-gray-400">Hari</span></div>
                </div>

                <!-- Hadir -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-emerald-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Hadir</span>
                        <CheckCircleIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-emerald-600 mt-2">{{ stats.hadir }}</div>
                </div>

                <!-- Terlambat -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-rose-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Terlambat</span>
                        <ClockIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-rose-600 mt-2">{{ stats.terlambat }}</div>
                </div>

                <!-- Izin -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-amber-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Izin</span>
                        <CalendarDaysIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-amber-600 mt-2">{{ stats.izin }}</div>
                </div>

                <!-- Sakit -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-blue-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Sakit</span>
                        <PlusCircleIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-2">{{ stats.sakit }}</div>
                </div>

                <!-- Cuti -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-150 flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-violet-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Cuti</span>
                        <BriefcaseIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-violet-600 mt-2">{{ stats.cuti }}</div>
                </div>

                <!-- Avg Attendance -->
                <div class="bg-teal-50 border border-teal-150 p-4 rounded-2xl shadow-sm flex flex-col justify-between h-24">
                    <div class="flex items-center justify-between text-teal-600">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700">Rata-rata Hadir</span>
                        <ChartBarIcon class="w-4 h-4" />
                    </div>
                    <div class="text-2xl font-black text-teal-700 mt-2">{{ stats.avg_attendance }}%</div>
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

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm text-left">
                        <thead class="bg-slate-50 text-gray-700 uppercase text-xs font-bold border-b border-gray-150">
                            <tr>
                                <th class="px-5 py-4 text-center">No</th>
                                <th class="px-5 py-4">Karyawan / Guru</th>
                                <th class="px-5 py-4">NIP/NUPTK</th>
                                <th class="px-5 py-4">Jabatan</th>
                                <th class="px-5 py-4">Mata Pelajaran</th>
                                <th class="px-5 py-4">Unit Sekolah</th>
                                <th class="px-4 py-4 text-center">Hadir</th>
                                <th class="px-4 py-4 text-center">Telat</th>
                                <th class="px-4 py-4 text-center">Izin</th>
                                <th class="px-4 py-4 text-center">Sakit</th>
                                <th class="px-4 py-4 text-center">Cuti</th>
                                <th class="px-4 py-4 text-center">Alpha</th>
                                <th class="px-5 py-4 text-center">% Hadir</th>
                                <th class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="(row, idx) in recapData" :key="row.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5 text-center text-gray-400 font-semibold">{{ idx + 1 }}</td>
                                
                                <!-- Photo & Name -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img v-if="row.photo" :src="row.photo" class="w-9 h-9 rounded-full object-cover border border-gray-100 shadow-sm" alt="Profile" />
                                        <div v-else class="w-9 h-9 rounded-full bg-slate-100 text-gray-400 flex items-center justify-center font-bold text-xs">
                                            {{ row.name.charAt(0) }}
                                        </div>
                                        <div class="font-extrabold text-gray-800 leading-snug">{{ row.name }}</div>
                                    </div>
                                </td>
                                
                                <!-- NIP -->
                                <td class="px-5 py-3.5 font-mono text-xs text-gray-600">{{ row.nip }}</td>
                                
                                <!-- Jabatan -->
                                <td class="px-5 py-3.5 font-semibold text-gray-700">{{ row.jabatan }}</td>
                                
                                <!-- Subjects -->
                                <td class="px-5 py-3.5 text-gray-500 text-xs truncate max-w-[150px]" :title="row.subjects">{{ row.subjects }}</td>
                                
                                <!-- Unit -->
                                <td class="px-5 py-3.5 text-gray-500 text-xs font-semibold">{{ row.unit_name }}</td>
                                
                                <!-- Present Counts -->
                                <td class="px-4 py-3.5 text-center font-bold text-emerald-600">{{ row.hadir }}</td>
                                <td class="px-4 py-3.5 text-center font-bold text-rose-600">{{ row.terlambat }}</td>
                                <td class="px-4 py-3.5 text-center font-semibold text-amber-600">{{ row.izin }}</td>
                                <td class="px-4 py-3.5 text-center font-semibold text-blue-600">{{ row.sakit }}</td>
                                <td class="px-4 py-3.5 text-center font-semibold text-violet-600">{{ row.cuti }}</td>
                                <td class="px-4 py-3.5 text-center font-bold text-red-600">{{ row.alpha }}</td>
                                
                                <!-- Percentage -->
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-xs font-black inline-block', getPercentageClass(row.percentage)]">
                                        {{ row.percentage }}%
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-center">
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
            </div>

            <!-- Slide-over Drawer for Individual Employee History (Untouched drawer logic with style alignment) -->
            <div v-if="showDetailDrawer" class="fixed inset-0 overflow-hidden z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
                <div class="absolute inset-0 overflow-hidden">
                    <div @click="showDetailDrawer = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>

                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div class="pointer-events-auto w-screen max-w-2xl transform bg-white shadow-2xl transition-all duration-300 ease-in-out flex flex-col">
                            
                            <!-- Drawer Header -->
                            <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img v-if="selectedEmployee?.photo" :src="selectedEmployee.photo" class="w-12 h-12 rounded-full object-cover border border-slate-200 shadow-sm" alt="Profile" />
                                    <div v-else class="w-12 h-12 rounded-full bg-slate-100 text-gray-400 flex items-center justify-center font-bold text-lg">
                                        {{ selectedEmployee?.user_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-extrabold text-gray-900 leading-tight">
                                            {{ selectedEmployee?.user_name }}
                                        </h2>
                                        <p class="text-xs text-gray-400 capitalize mt-0.5">
                                            {{ selectedEmployee?.role.replace('_', ' ') }}
                                        </p>
                                    </div>
                                </div>
                                <button @click="showDetailDrawer = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-slate-100 rounded-xl transition-all cursor-pointer">
                                    <XMarkIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <!-- Drawer Body -->
                            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 flex flex-wrap gap-4 items-end justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-[250px]">
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Bulan</label>
                                            <select v-model="detailMonth" @change="fetchEmployeeHistory" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 h-10 px-3 focus:border-namira-teal focus:ring focus:ring-namira-teal/20">
                                                <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                                            </select>
                                        </div>
                                        
                                        <div class="w-24">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tahun</label>
                                            <input type="number" v-model="detailYear" @change="fetchEmployeeHistory" class="w-full bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 h-10 px-3 focus:border-namira-teal focus:ring focus:ring-namira-teal/20 text-center" />
                                        </div>
                                    </div>

                                    <button 
                                        @click="downloadIndividualPdf"
                                        :disabled="loadingDetail || !employeeStats || downloadingIndividualPdf"
                                        class="flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-400 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-md active:scale-95 disabled:opacity-50 h-10 cursor-pointer text-xs"
                                    >
                                        <NamiraLoader :visible="downloadingIndividualPdf" variant="button" />
                                        <DocumentTextIcon v-if="!downloadingIndividualPdf" class="w-4 h-4" />
                                        <span>{{ downloadingIndividualPdf ? 'Memproses...' : 'Cetak PDF' }}</span>
                                    </button>
                                </div>

                                <div v-if="loadingDetail" class="min-h-[300px] flex items-center justify-center relative">
                                    <NamiraLoader :visible="true" variant="card" text="Mengambil data riwayat..." />
                                </div>

                                <div v-else class="space-y-6">
                                    <div v-if="employeeStats" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-4 text-center">
                                            <div class="text-2xl font-black text-emerald-600">{{ employeeStats.present }}</div>
                                            <div class="text-[10px] font-bold text-emerald-500 uppercase mt-0.5">Hadir</div>
                                        </div>
                                        <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-4 text-center">
                                            <div class="text-2xl font-black text-rose-600">{{ employeeStats.late }}</div>
                                            <div class="text-[10px] font-bold text-rose-500 uppercase mt-0.5">Terlambat</div>
                                        </div>
                                        <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 text-center">
                                            <div class="text-2xl font-black text-blue-600">
                                                {{ employeeStats.sick + employeeStats.permit + employeeStats.business_trip }}
                                            </div>
                                            <div class="text-[10px] font-bold text-blue-500 uppercase mt-0.5">Izin/Sakit/Dinas</div>
                                        </div>
                                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-center col-span-2 sm:col-span-3">
                                            <div class="flex items-center justify-around">
                                                <div>
                                                    <div class="text-2xl font-black text-slate-700">{{ employeeStats.total_late_minutes }}m</div>
                                                    <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Total Telat</div>
                                                </div>
                                                <div class="h-8 w-px bg-slate-200"></div>
                                                <div>
                                                    <div class="text-2xl font-black text-namira-teal">{{ employeeStats.attendance_percentage }}%</div>
                                                    <div class="text-[10px] font-bold text-namira-teal/70 uppercase mt-0.5">Tingkat Disiplin</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <h3 class="text-sm font-extrabold text-gray-900">Catatan Kehadiran Bulan Ini</h3>
                                        
                                        <div v-if="employeeHistoryData.length === 0" class="py-12 border border-dashed border-gray-150 rounded-2xl text-center bg-slate-50/50">
                                            <ClipboardDocumentListIcon class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                            <p class="text-xs text-gray-400 font-bold">Tidak ada log kehadiran di bulan ini</p>
                                        </div>
                                        
                                        <div v-else class="overflow-x-auto rounded-2xl border border-slate-150 shadow-sm bg-white max-h-[350px] overflow-y-auto">
                                            <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                                <thead class="bg-slate-50 text-gray-600 font-bold border-b border-slate-100 sticky top-0 z-10">
                                                    <tr>
                                                        <th class="px-4 py-3">Tanggal</th>
                                                        <th class="px-4 py-3">Masuk</th>
                                                        <th class="px-4 py-3">Pulang</th>
                                                        <th class="px-4 py-3">Telat</th>
                                                        <th class="px-4 py-3">Status</th>
                                                        <th class="px-4 py-3">Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <tr v-for="att in employeeHistoryData" :key="att.id" class="hover:bg-slate-50/30 transition-colors">
                                                        <td class="px-4 py-3 font-medium text-gray-900">{{ dayjs(att.date).format('DD MMM YYYY') }}</td>
                                                        <td class="px-4 py-3 font-bold text-gray-700">{{ att.check_in_time ? att.check_in_time.substring(0, 5) : '-' }}</td>
                                                        <td class="px-4 py-3 font-bold text-gray-700">{{ att.check_out_time ? att.check_out_time.substring(0, 5) : '-' }}</td>
                                                        <td class="px-4 py-3">
                                                            <span v-if="att.late_minutes > 0" class="text-rose-600 font-bold">+{{ att.late_minutes }}m</span>
                                                            <span v-else class="text-gray-400">-</span>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span :class="['px-2 py-0.5 rounded text-[10px] font-bold inline-block', getStatusClass(att.status)]">
                                                                {{ getStatusLabel(att.status) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-500 italic max-w-[150px] truncate" :title="att.note || ''">{{ att.note || '-' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
