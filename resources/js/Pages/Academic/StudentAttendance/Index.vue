<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    ClipboardDocumentCheckIcon, ChartBarIcon, BuildingOfficeIcon, 
    AcademicCapIcon, UserGroupIcon, UserIcon, ArrowDownTrayIcon,
    CheckCircleIcon, ClockIcon, CalendarDaysIcon, ChevronRightIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classrooms: Array,
    user_is_homeroom: Boolean,
    initialTab: {
        type: String,
        default: 'daily',
    },
    recapData: Array,
    recapStats: Object,
    dates: Array,
    selectedClassroom: Object,
    filters: Object,
    monthName: String,
    daysInMonth: Number,
});

// Active Tab ('daily' | 'recap')
const activeTab = ref(props.filters?.tab || props.initialTab || 'daily');

const setTab = (tab) => {
    activeTab.value = tab;
    router.get(route('yayasan.student-attendance.index'), {
        tab: tab,
        classroom_id: selectedClassroomId.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Recap Filters
const selectedClassroomId = ref(props.filters?.classroom_id || (props.classrooms?.[0]?.id ?? ''));
const selectedMonth = ref(props.filters?.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year || new Date().getFullYear());

const months = [
    { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' }, { value: 4, label: 'April' },
    { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' }, { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const years = [currentYear - 1, currentYear, currentYear + 1];

const applyRecapFilter = () => {
    if (!selectedClassroomId.value) return;
    router.get(route('yayasan.student-attendance.index'), {
        tab: 'recap',
        classroom_id: selectedClassroomId.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedClassroomId, selectedMonth, selectedYear], () => {
    if (activeTab.value === 'recap') {
        applyRecapFilter();
    }
});

const exportRecap = () => {
    if (!selectedClassroomId.value) return;
    window.location.href = route('yayasan.student-attendance.export', {
        classroom_id: selectedClassroomId.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    });
};

const getStatusColor = (percentage) => {
    if (percentage >= 90) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if (percentage >= 75) return 'text-amber-700 bg-amber-50 border-amber-200';
    return 'text-rose-700 bg-rose-50 border-rose-200';
};

const getStatusBadge = (status) => {
    const map = {
        'H': 'bg-emerald-500 text-white',
        'S': 'bg-sky-500 text-white',
        'I': 'bg-amber-500 text-white',
        'A': 'bg-rose-500 text-white',
    };
    return map[status] || 'bg-slate-100 text-slate-300';
};

// Formatted today string
const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});
</script>

<template>
    <Head title="Presensi Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Presensi Siswa
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Kelola kehadiran harian kelas dan rekapitulasi kehadiran bulanan siswa.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-4 md:py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            
            <!-- MAIN TOP NAVIGATION TABS: Presensi Harian vs Rekap Bulanan -->
            <div class="flex items-center justify-center p-1 bg-slate-100 rounded-2xl max-w-md mx-auto border border-slate-200 shadow-xs">
                <button
                    @click="setTab('daily')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                    :class="activeTab === 'daily' ? 'bg-[#00584b] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ClipboardDocumentCheckIcon class="w-4 h-4" />
                    <span>Presensi Harian</span>
                </button>

                <button
                    @click="setTab('recap')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
                    :class="activeTab === 'recap' ? 'bg-[#00584b] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ChartBarIcon class="w-4 h-4" />
                    <span>Rekap Bulanan</span>
                </button>
            </div>

            <!-- ==================================================== -->
            <!-- TAB 1: PRESENSI HARIAN (PILIH KELAS & INPUT HARIAN) -->
            <!-- ==================================================== -->
            <div v-if="activeTab === 'daily'" class="space-y-4">
                
                <!-- Info Header Banner -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#00584b] flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Hari Ini</span>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">{{ todayFormatted }}</h3>
                            <p class="text-xs text-gray-500">Pilih kelas di bawah ini untuk mencatat kehadiran harian siswa.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                            <BuildingOfficeIcon class="w-4 h-4 text-[#00584b]" />
                            <span>{{ classrooms.length }} Kelas Tersedia</span>
                        </span>
                    </div>
                </div>

                <!-- Grid of Classrooms -->
                <div v-if="classrooms.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    <div 
                        v-for="classroom in classrooms" 
                        :key="'cls-' + classroom.id" 
                        class="bg-white rounded-3xl border border-gray-100 shadow-xs hover:shadow-md hover:border-emerald-200 transition-all p-5 sm:p-6 flex flex-col justify-between"
                    >
                        <!-- Top Class Header -->
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="px-3 py-1 bg-teal-50 text-[#00584b] rounded-full text-xs font-bold border border-teal-100">
                                    {{ classroom.unit?.name || 'Unit Sekolah' }}
                                </span>
                                <div class="w-9 h-9 rounded-2xl bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <AcademicCapIcon class="w-5 h-5 text-[#00584b]" />
                                </div>
                            </div>

                            <h3 class="text-xl font-black text-gray-900 tracking-tight">
                                {{ classroom.name }}
                            </h3>

                            <!-- Details Info -->
                            <div class="mt-3 space-y-1.5 text-xs text-gray-600">
                                <div class="flex items-center gap-2">
                                    <UserIcon class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="truncate">
                                        Wali Kelas: 
                                        <strong class="text-gray-800">
                                            {{ classroom.homeroom_teacher?.full_name || classroom.homeroom_teacher?.user?.name || 'Belum Ditentukan' }}
                                        </strong>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <UserGroupIcon class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span>
                                        Total Siswa: 
                                        <strong class="text-gray-800">{{ classroom.students_count || 0 }} Siswa</strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Attendance Status Today -->
                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <div v-if="classroom.has_attendance_today" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-800 rounded-full text-xs font-semibold border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Sudah Diabsen ({{ classroom.today_attendance_count }} Siswa)</span>
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-semibold border border-amber-200">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Belum Diabsen Hari Ini</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-5 pt-2">
                            <Link 
                                :href="route('yayasan.student-attendance.show', classroom.id)"
                                class="w-full py-3 bg-[#00584b] hover:bg-[#00473c] text-white font-bold text-xs sm:text-sm rounded-2xl shadow-xs text-center flex items-center justify-center gap-2 transition-all active:scale-95 cursor-pointer"
                            >
                                <span>Input Presensi Kelas</span>
                                <ChevronRightIcon class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-white rounded-3xl border border-gray-100 shadow-xs flex flex-col items-center justify-center p-6">
                    <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center text-[#00584b] mb-4">
                        <AcademicCapIcon class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Tidak Ada Kelas Tersedia</h3>
                    <p class="text-xs sm:text-sm text-gray-500 max-w-sm">
                        Anda belum ditugaskan sebagai Wali Kelas atau tidak memiliki izin akses kelas pada unit ini.
                    </p>
                </div>

            </div>

            <!-- ==================================================== -->
            <!-- TAB 2: REKAP BULANAN (MATRIKS KEHADIRAN & EXPORT EXCEL) -->
            <!-- ==================================================== -->
            <div v-else-if="activeTab === 'recap'" class="space-y-4">

                <!-- Filter & Action Card -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-4 sm:p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Filters Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1 max-w-2xl">
                            <!-- Select Kelas -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                    Kelas
                                </label>
                                <select 
                                    v-model="selectedClassroomId"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold p-2.5 focus:border-[#00584b] focus:ring-[#00584b] cursor-pointer"
                                >
                                    <option value="" disabled>-- Pilih Kelas --</option>
                                    <option v-for="c in classrooms" :key="'opt-' + c.id" :value="c.id">
                                        {{ c.name }} ({{ c.unit?.name || 'Unit' }})
                                    </option>
                                </select>
                            </div>

                            <!-- Select Bulan -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                    Bulan
                                </label>
                                <select 
                                    v-model="selectedMonth"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold p-2.5 focus:border-[#00584b] focus:ring-[#00584b] cursor-pointer"
                                >
                                    <option v-for="m in months" :key="'m-' + m.value" :value="m.value">
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Select Tahun -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                    Tahun
                                </label>
                                <select 
                                    v-model="selectedYear"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold p-2.5 focus:border-[#00584b] focus:ring-[#00584b] cursor-pointer"
                                >
                                    <option v-for="y in years" :key="'y-' + y" :value="y">
                                        {{ y }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Export Button -->
                        <div class="flex items-end">
                            <button
                                @click="exportRecap"
                                :disabled="!selectedClassroomId"
                                type="button"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <ArrowDownTrayIcon class="w-4 h-4" />
                                <span>Export Excel</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics Bar for Selected Class -->
                <div v-if="selectedClassroom && recapStats" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Total Hadir (H)</span>
                            <h4 class="text-xl font-black text-emerald-600">{{ recapStats.H }}</h4>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-xs">
                            H
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Total Sakit (S)</span>
                            <h4 class="text-xl font-black text-sky-600">{{ recapStats.S }}</h4>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-sky-50 text-sky-700 font-bold flex items-center justify-center text-xs">
                            S
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Total Izin (I)</span>
                            <h4 class="text-xl font-black text-amber-600">{{ recapStats.I }}</h4>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-700 font-bold flex items-center justify-center text-xs">
                            I
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Total Alpha (A)</span>
                            <h4 class="text-xl font-black text-rose-600">{{ recapStats.A }}</h4>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-700 font-bold flex items-center justify-center text-xs">
                            A
                        </div>
                    </div>
                </div>

                <!-- Monthly Attendance Matrix Table -->
                <div v-if="selectedClassroom && recapData && recapData.length > 0" class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">
                                Matriks Kehadiran: {{ selectedClassroom.name }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                Periode: {{ monthName }} {{ filters.year }} ({{ daysInMonth }} Hari)
                            </p>
                        </div>

                        <!-- Legend Status -->
                        <div class="hidden sm:flex items-center gap-3 text-xs font-semibold text-gray-600">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Hadir (H)
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-sky-500"></span> Sakit (S)
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span> Izin (I)
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-rose-500"></span> Alpha (A)
                            </span>
                        </div>
                    </div>

                    <!-- Horizontal Scrollable Matrix Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-3 w-10 text-center sticky left-0 bg-slate-50 z-10 border-r border-slate-200 shadow-xs">
                                        No
                                    </th>
                                    <th class="py-3 px-3 min-w-[180px] sticky left-10 bg-slate-50 z-10 border-r border-slate-200 shadow-xs">
                                        Nama Siswa
                                    </th>
                                    <!-- Days 1..N -->
                                    <th 
                                        v-for="d in dates" 
                                        :key="'th-d-' + d.day"
                                        class="py-2 px-1 text-center w-7 border-r border-slate-200/70"
                                        :class="d.isHoliday ? 'bg-slate-200/60 text-slate-400' : ''"
                                        :title="d.date"
                                    >
                                        <div class="text-[9px] font-medium leading-none">{{ d.dayName }}</div>
                                        <div class="text-[11px] font-bold mt-0.5 leading-none">{{ d.day }}</div>
                                    </th>
                                    <!-- Summary Header -->
                                    <th class="py-3 px-2 text-center bg-emerald-50 text-emerald-800 font-bold border-l border-slate-200">H</th>
                                    <th class="py-3 px-2 text-center bg-sky-50 text-sky-800 font-bold">S</th>
                                    <th class="py-3 px-2 text-center bg-amber-50 text-amber-800 font-bold">I</th>
                                    <th class="py-3 px-2 text-center bg-rose-50 text-rose-800 font-bold">A</th>
                                    <th class="py-3 px-3 text-center bg-slate-100 text-slate-800 font-bold">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr 
                                    v-for="(row, idx) in recapData" 
                                    :key="'row-' + row.student.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <!-- Col No -->
                                    <td class="py-2.5 px-3 text-center sticky left-0 bg-white hover:bg-slate-50 z-10 font-bold text-slate-400 border-r border-slate-100">
                                        {{ idx + 1 }}
                                    </td>
                                    <!-- Col Student Name -->
                                    <td class="py-2.5 px-3 sticky left-10 bg-white hover:bg-slate-50 z-10 font-semibold text-gray-900 border-r border-slate-100">
                                        <div class="truncate max-w-[170px]" :title="row.student.full_name">
                                            {{ row.student.full_name }}
                                        </div>
                                    </td>
                                    <!-- Daily Statuses -->
                                    <td 
                                        v-for="d in dates" 
                                        :key="'cell-' + row.student.id + '-' + d.day"
                                        class="py-1 px-0.5 text-center border-r border-slate-100"
                                        :class="d.isHoliday ? 'bg-slate-100/50' : ''"
                                    >
                                        <span 
                                            v-if="row.daily[d.day]"
                                            class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-black"
                                            :class="getStatusBadge(row.daily[d.day])"
                                        >
                                            {{ row.daily[d.day] }}
                                        </span>
                                        <span v-else class="text-slate-300 text-[10px]">-</span>
                                    </td>
                                    <!-- Totals -->
                                    <td class="py-2.5 px-2 text-center font-bold text-emerald-700 bg-emerald-50/40 border-l border-slate-200">
                                        {{ row.summary.H }}
                                    </td>
                                    <td class="py-2.5 px-2 text-center font-bold text-sky-700 bg-sky-50/40">
                                        {{ row.summary.S }}
                                    </td>
                                    <td class="py-2.5 px-2 text-center font-bold text-amber-700 bg-amber-50/40">
                                        {{ row.summary.I }}
                                    </td>
                                    <td class="py-2.5 px-2 text-center font-bold text-rose-700 bg-rose-50/40">
                                        {{ row.summary.A }}
                                    </td>
                                    <!-- Percentage -->
                                    <td class="py-2.5 px-2 text-center">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border"
                                            :class="getStatusColor(row.percentage)"
                                        >
                                            {{ row.percentage }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State When No Class or No Student -->
                <div v-else class="text-center py-16 bg-white rounded-3xl border border-gray-100 shadow-xs flex flex-col items-center justify-center p-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3">
                        <ChartBarIcon class="w-8 h-8" />
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        {{ selectedClassroomId ? 'Belum Ada Data Siswa di Kelas Ini' : 'Pilih Kelas Terlebih Dahulu' }}
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-500 max-w-sm">
                        {{ selectedClassroomId ? 'Tidak ditemukan catatan siswa pada kelas terpilih untuk periode ini.' : 'Pilih salah satu kelas melalui dropdown filter di atas untuk menampilkan matriks rekap kehadiran.' }}
                    </p>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
