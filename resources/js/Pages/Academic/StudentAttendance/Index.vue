<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { 
    ClipboardDocumentCheckIcon, ChartBarIcon, BuildingOfficeIcon, 
    AcademicCapIcon, UserGroupIcon, UserIcon, ArrowDownTrayIcon,
    CheckCircleIcon, ClockIcon, CalendarDaysIcon, ChevronRightIcon,
    ChevronDownIcon, EllipsisVerticalIcon, ArrowRightIcon,
    MagnifyingGlassIcon, XMarkIcon, ExclamationTriangleIcon, EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    classrooms: Array,
    user_is_homeroom: Boolean,
    initialTab: {
        type: String,
        default: 'daily',
    },
    selectedDate: String,
    formattedDate: String,
    shortDate: String,
    dailyStats: Object,
    levels: Array,
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
        date: currentDate.value,
        classroom_id: selectedClassroomId.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Daily View State
const currentDate = ref(props.selectedDate || props.filters?.date || new Date().toISOString().split('T')[0]);
const searchQuery = ref('');
const selectedLevel = ref(null);
const activeMenuId = ref(null);
const dateInputRef = ref(null);

const openDatePicker = () => {
    if (dateInputRef.value?.showPicker) {
        dateInputRef.value.showPicker();
    } else {
        dateInputRef.value?.focus();
        dateInputRef.value?.click();
    }
};

const onDateChange = (e) => {
    const newDate = e.target.value;
    if (!newDate || newDate === currentDate.value) return;
    currentDate.value = newDate;
    router.get(route('yayasan.student-attendance.index'), {
        tab: 'daily',
        date: newDate,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Available levels extracted from classrooms or props
const availableLevels = computed(() => {
    if (props.levels && props.levels.length > 0) {
        return props.levels;
    }
    const extracted = (props.classrooms || [])
        .map(c => c.level || (c.name ? parseInt(c.name) : null))
        .filter(lvl => !isNaN(lvl) && lvl !== null);
    return Array.from(new Set(extracted)).sort((a, b) => a - b);
});

// Filtered Classrooms for Daily View
const filteredClassrooms = computed(() => {
    let list = props.classrooms || [];

    // Filter by level
    if (selectedLevel.value !== null) {
        list = list.filter(c => {
            if (c.level !== undefined && c.level !== null) {
                return Number(c.level) === Number(selectedLevel.value);
            }
            return String(c.name).startsWith(String(selectedLevel.value));
        });
    }

    // Filter by search query (classroom name or teacher name)
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(c => {
            const nameMatch = c.name && c.name.toLowerCase().includes(q);
            const teacherMatch = c.homeroom_teacher_name && c.homeroom_teacher_name.toLowerCase().includes(q);
            return nameMatch || teacherMatch;
        });
    }

    return list;
});

const toggleMenu = (id) => {
    activeMenuId.value = activeMenuId.value === id ? null : id;
};

const closeMenuOnClickOutside = () => {
    activeMenuId.value = null;
};

onMounted(() => {
    window.addEventListener('click', closeMenuOnClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('click', closeMenuOnClickOutside);
});

const goToRecap = (classroomId) => {
    activeMenuId.value = null;
    selectedClassroomId.value = classroomId;
    setTab('recap');
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
</script>

<template>
    <Head title="Presensi Kelas" />

    <AuthenticatedLayout>
        <div class="py-3 sm:py-6 max-w-7xl mx-auto space-y-4">
            
            <!-- MAIN TOP NAVIGATION TABS: Presensi Harian vs Rekap Bulanan -->
            <div class="flex items-center justify-center p-1 bg-slate-100 rounded-2xl max-w-xs mx-auto border border-slate-200/80 shadow-2xs">
                <button
                    @click="setTab('daily')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeTab === 'daily' ? 'bg-[#00584b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ClipboardDocumentCheckIcon class="w-3.5 h-3.5" />
                    <span>Presensi Harian</span>
                </button>

                <button
                    @click="setTab('recap')"
                    type="button"
                    class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeTab === 'recap' ? 'bg-[#00584b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ChartBarIcon class="w-3.5 h-3.5" />
                    <span>Rekap Bulanan</span>
                </button>
            </div>

            <!-- ==================================================== -->
            <!-- TAB 1: PRESENSI KELAS (REDESIGNED MATCHING REFERENCE) -->
            <!-- ==================================================== -->
            <div v-if="activeTab === 'daily'" class="space-y-4">
                
                <!-- 1. Page Header: Title + Subtitle (Left) & Date Dropdown Pill (Right) -->
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                            Presensi Kelas
                        </h1>
                        <p class="text-xs sm:text-sm font-semibold text-slate-400 mt-0.5 truncate">
                            {{ formattedDate || 'Hari Ini' }}
                        </p>
                    </div>

                    <!-- Date Selector Pill Button with Native Picker Overlay -->
                    <div class="relative shrink-0">
                        <button 
                            @click="openDatePicker"
                            type="button" 
                            class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-3.5 py-2 bg-white border border-slate-200/90 rounded-2xl text-xs sm:text-sm font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition active:scale-95 cursor-pointer"
                        >
                            <CalendarDaysIcon class="w-4 h-4 text-slate-500 shrink-0" />
                            <span>{{ shortDate || currentDate }}</span>
                            <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 shrink-0 stroke-[2.5]" />
                        </button>
                        <input 
                            ref="dateInputRef" 
                            type="date" 
                            :value="currentDate" 
                            @change="onDateChange"
                            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full pointer-events-auto" 
                        />
                    </div>
                </div>

                <!-- 2. Stat Cards Row (3 Cards: Kelas | Selesai | Belum) -->
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3.5">
                    <!-- Stat Card 1: Kelas -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-3 sm:p-3.5 shadow-2xs flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <UserGroupIcon class="w-5 h-5 stroke-[1.8]" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-base sm:text-xl font-black text-slate-900 leading-none">
                                {{ dailyStats?.total_classes ?? classrooms.length }}
                            </p>
                            <p class="text-[11px] sm:text-xs font-semibold text-slate-400 mt-1">Kelas</p>
                        </div>
                    </div>

                    <!-- Stat Card 2: Selesai -->
                    <div class="bg-white rounded-2xl border border-emerald-100/70 p-3 sm:p-3.5 shadow-2xs flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <CheckCircleIcon class="w-5 h-5 stroke-[1.8]" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-base sm:text-xl font-black text-slate-900 leading-none">
                                {{ dailyStats?.completed_classes ?? 0 }}
                            </p>
                            <p class="text-[11px] sm:text-xs font-semibold text-slate-400 mt-1">Selesai</p>
                        </div>
                    </div>

                    <!-- Stat Card 3: Belum -->
                    <div class="bg-white rounded-2xl border border-amber-100/70 p-3 sm:p-3.5 shadow-2xs flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <ClockIcon class="w-5 h-5 stroke-[1.8]" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-base sm:text-xl font-black text-slate-900 leading-none">
                                {{ dailyStats?.pending_classes ?? 0 }}
                            </p>
                            <p class="text-[11px] sm:text-xs font-semibold text-slate-400 mt-1">Belum</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Search Bar -->
                <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input 
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kelas atau wali kelas..."
                        class="w-full pl-10 pr-9 py-2.5 bg-white border border-slate-200/90 rounded-2xl text-xs sm:text-sm font-semibold focus:ring-[#00584b] focus:border-[#00584b] shadow-2xs placeholder:text-slate-400 placeholder:font-normal"
                    />
                    <button 
                        v-if="searchQuery" 
                        @click="searchQuery = ''"
                        type="button" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition"
                        title="Hapus pencarian"
                    >
                        <XMarkIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- 4. Horizontal Level Filter Pills -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 text-xs">
                    <button 
                        @click="selectedLevel = null"
                        type="button"
                        class="px-4 py-2 rounded-xl font-bold text-xs shrink-0 transition-all border shadow-2xs active:scale-95"
                        :class="selectedLevel === null 
                            ? 'bg-[#00584b] text-white border-[#00584b]' 
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="lvl in availableLevels"
                        :key="'lvl-' + lvl"
                        @click="selectedLevel = (selectedLevel === lvl ? null : lvl)"
                        type="button"
                        class="px-4 py-2 rounded-xl font-bold text-xs shrink-0 transition-all border shadow-2xs active:scale-95"
                        :class="selectedLevel === lvl 
                            ? 'bg-[#00584b] text-white border-[#00584b]' 
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    >
                        Kelas {{ lvl }}
                    </button>
                </div>

                <!-- 5. 2-Column Class Cards Grid (Matching Reference Exact) -->
                <div v-if="filteredClassrooms.length > 0" class="grid grid-cols-2 gap-2.5 sm:gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 pt-1">
                    <div 
                        v-for="classroom in filteredClassrooms" 
                        :key="'cls-' + classroom.id" 
                        class="bg-white rounded-2xl border border-slate-100 p-3 sm:p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative group"
                    >
                        <!-- Top Row: Class Name, Homeroom Teacher, 3-dots Menu -->
                        <div>
                            <div class="flex items-start justify-between gap-1">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                                        {{ classroom.name }}
                                    </h3>
                                    <p class="text-[11px] sm:text-xs text-slate-600 font-medium truncate mt-0.5" :title="classroom.homeroom_teacher_name">
                                        {{ classroom.homeroom_teacher_name }}
                                    </p>
                                </div>

                                <!-- 3-dots Options Menu Dropdown -->
                                <div class="relative shrink-0">
                                    <button 
                                        @click.stop="toggleMenu(classroom.id)"
                                        type="button"
                                        class="p-1 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition active:scale-95 cursor-pointer"
                                        title="Pilihan menu"
                                    >
                                        <EllipsisVerticalIcon class="w-4 h-4 stroke-[2]" />
                                    </button>

                                    <!-- Dropdown Popover -->
                                    <div 
                                        v-if="activeMenuId === classroom.id" 
                                        @click.stop 
                                        class="absolute right-0 top-7 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-30 text-xs animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <Link 
                                            :href="route('yayasan.student-attendance.show', classroom.id) + '?date=' + currentDate"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 font-semibold"
                                        >
                                            <ClipboardDocumentCheckIcon class="w-4 h-4 text-teal-600" />
                                            <span>{{ classroom.can_edit ? 'Input / Cek Presensi' : 'Lihat Kehadiran' }}</span>
                                        </Link>
                                        <button 
                                            @click="goToRecap(classroom.id)"
                                            type="button"
                                            class="w-full flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 font-semibold text-left cursor-pointer"
                                        >
                                            <ChartBarIcon class="w-4 h-4 text-indigo-600" />
                                            <span>Rekap Bulanan</span>
                                        </button>
                                        <Link 
                                            :href="route('yayasan.students.index', { classroom_id: classroom.id })"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 font-semibold"
                                        >
                                            <UserGroupIcon class="w-4 h-4 text-sky-600" />
                                            <span>Data Siswa Kelas</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle Section: Student Count + Attendance Percentage + Progress Bar -->
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] text-slate-500 font-semibold">
                                    <div class="flex items-center gap-1 min-w-0">
                                        <UserGroupIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                        <span class="truncate">{{ classroom.students_count || 0 }} siswa</span>
                                    </div>
                                    <span class="font-bold text-slate-600 shrink-0">
                                        {{ classroom.attendance_percentage || 0 }}%
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="classroom.attendance_percentage === 100 ? 'bg-emerald-500' : 'bg-teal-600'"
                                        :style="{ width: `${classroom.attendance_percentage || 0}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Status Pill (Belum presensi / Selesai presensi / Sebagian) -->
                            <div class="mt-2.5">
                                <div 
                                    v-if="classroom.is_complete"
                                    class="py-1 px-2 rounded-lg bg-emerald-50 text-emerald-800 text-[10px] sm:text-[11px] font-bold text-center border border-emerald-200/60 flex items-center justify-center gap-1.5"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span class="truncate">Selesai presensi</span>
                                </div>
                                <div 
                                    v-else-if="classroom.has_attendance_today"
                                    class="py-1 px-2 rounded-lg bg-sky-50 text-sky-800 text-[10px] sm:text-[11px] font-bold text-center border border-sky-200/60 flex items-center justify-center gap-1.5"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                    <span class="truncate">Sebagian ({{ classroom.attendance_count }}/{{ classroom.students_count }})</span>
                                </div>
                                <div 
                                    v-else
                                    class="py-1 px-2 rounded-lg bg-amber-50 text-amber-800 text-[10px] sm:text-[11px] font-bold text-center border border-amber-200/60 flex items-center justify-center gap-1.5"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span class="truncate">Belum presensi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button: Full Width Teal Button -->
                        <div class="mt-3">
                            <Link 
                                :href="route('yayasan.student-attendance.show', classroom.id) + '?date=' + currentDate"
                                class="w-full py-2 bg-[#00584b] hover:bg-[#00473c] text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 shadow-2xs active:scale-95 transition cursor-pointer"
                            >
                                <span class="truncate">{{ classroom.can_edit ? 'Input Presensi' : 'Lihat Detail' }}</span>
                                <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5] shrink-0" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-[#00584b] flex items-center justify-center mx-auto">
                        <AcademicCapIcon class="w-6 h-6 stroke-[2]" />
                    </div>
                    <p class="font-black text-sm text-slate-800">Tidak ada kelas yang cocok</p>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto">
                        {{ searchQuery ? 'Tidak ada kelas atau wali kelas yang sesuai dengan pencarian.' : 'Data kelas belum tersedia pada filter ini.' }}
                    </p>
                </div>

            </div>

            <!-- ==================================================== -->
            <!-- TAB 2: REKAP BULANAN (MATRIKS KEHADIRAN & EXPORT EXCEL) -->
            <!-- ==================================================== -->
            <div v-else-if="activeTab === 'recap'" class="space-y-4">

                <!-- Ultra Compact Filter & Action Bar -->
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs p-3 sm:p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                        <!-- Kelas Selector (Flexible) -->
                        <div class="flex-1 min-w-0">
                            <label class="block sm:hidden text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                Pilih Kelas
                            </label>
                            <div class="relative">
                                <select 
                                    v-model="selectedClassroomId"
                                    class="w-full bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs sm:text-sm font-bold text-slate-800 py-2 sm:py-2.5 pl-3 pr-8 focus:border-[#00584b] focus:ring-[#00584b] transition-colors cursor-pointer truncate"
                                >
                                    <option value="" disabled>-- Pilih Kelas --</option>
                                    <option v-for="c in classrooms" :key="'opt-' + c.id" :value="c.id">
                                        {{ c.name }} ({{ c.unit?.name || 'Unit' }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Controls Group: Bulan, Tahun, Export side-by-side -->
                        <div class="grid grid-cols-12 gap-2 sm:flex sm:items-center sm:gap-2.5 shrink-0">
                            <!-- Select Bulan -->
                            <div class="col-span-5 sm:w-36">
                                <select 
                                    v-model="selectedMonth"
                                    class="w-full bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs sm:text-sm font-bold text-slate-800 py-2 sm:py-2.5 px-3 focus:border-[#00584b] focus:ring-[#00584b] transition-colors cursor-pointer"
                                >
                                    <option v-for="m in months" :key="'m-' + m.value" :value="m.value">
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Select Tahun -->
                            <div class="col-span-3 sm:w-28">
                                <select 
                                    v-model="selectedYear"
                                    class="w-full bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs sm:text-sm font-bold text-slate-800 py-2 sm:py-2.5 px-3 focus:border-[#00584b] focus:ring-[#00584b] transition-colors cursor-pointer"
                                >
                                    <option v-for="y in years" :key="'y-' + y" :value="y">
                                        {{ y }}
                                    </option>
                                </select>
                            </div>

                            <!-- Export Excel Button -->
                            <div class="col-span-4 sm:w-auto">
                                <button
                                    @click="exportRecap"
                                    :disabled="!selectedClassroomId"
                                    type="button"
                                    title="Export Excel Rekap Kehadiran"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
                                >
                                    <ArrowDownTrayIcon class="w-4 h-4 shrink-0" />
                                    <span class="truncate">Export</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics Bar for Selected Class (Compact 4-column) -->
                <div v-if="selectedClassroom && recapStats" class="grid grid-cols-4 gap-2 sm:gap-3">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-2.5 sm:p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase text-gray-400 tracking-wider">Hadir</span>
                            <h4 class="text-base sm:text-xl font-black text-emerald-600 leading-tight">{{ recapStats.H }}</h4>
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-xs shrink-0">
                            H
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-2.5 sm:p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase text-gray-400 tracking-wider">Sakit</span>
                            <h4 class="text-base sm:text-xl font-black text-sky-600 leading-tight">{{ recapStats.S }}</h4>
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-sky-50 text-sky-700 font-bold flex items-center justify-center text-xs shrink-0">
                            S
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-2.5 sm:p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase text-gray-400 tracking-wider">Izin</span>
                            <h4 class="text-base sm:text-xl font-black text-amber-600 leading-tight">{{ recapStats.I }}</h4>
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-amber-50 text-amber-700 font-bold flex items-center justify-center text-xs shrink-0">
                            I
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-2.5 sm:p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase text-gray-400 tracking-wider">Alpha</span>
                            <h4 class="text-base sm:text-xl font-black text-rose-600 leading-tight">{{ recapStats.A }}</h4>
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-rose-50 text-rose-700 font-bold flex items-center justify-center text-xs shrink-0">
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
