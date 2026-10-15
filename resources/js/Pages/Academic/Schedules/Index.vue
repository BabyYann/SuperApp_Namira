<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileAppShell from '@/Layouts/MobileAppShell.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { 
    ChevronDownIcon, PlusIcon, BoltIcon, PrinterIcon, 
    ArrowPathIcon as RefreshIcon, TrashIcon, DocumentDuplicateIcon,
    CalendarIcon, PencilSquareIcon, ClockIcon, ExclamationTriangleIcon, 
    ChevronUpDownIcon, ChevronRightIcon, MagnifyingGlassIcon, BookOpenIcon,
    UserGroupIcon, MapPinIcon, DocumentTextIcon, UserIcon, XMarkIcon,
    SparklesIcon, ChevronLeftIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    classrooms: Array,
    selectedClassroomId: Number,
    schedule: Object, // Grouped by day
    personalSchedule: Object, // Grouped by day (for teachers)
    isTeacher: Boolean,
    canManage: Boolean,
    subjects: Array,
    teachers: Array,
});

const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false);
const updateMobile = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 768;
    }
};

onMounted(() => {
    updateMobile();
    window.addEventListener('resize', updateMobile);
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateMobile);
    }
});

const cloneForm = useForm({
    classroom_id: props.selectedClassroomId || '',
    from_day: '',
    to_day: '',
});

const selectedClassroom = ref(props.selectedClassroomId);
const showAddModal = ref(false);
const showCloneModal = ref(false);
const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const isEditing = ref(false);
const editingId = ref(null);
const isJointClass = ref(false);
const selectedJointClassrooms = ref([]);

const form = useForm({
    classroom_id: props.selectedClassroomId || '',
    classroom_ids: [],
    subject_id: '',
    teacher_id: '',
    day: '',
    start_time: '',
    end_time: '',
});

// Auto-select current day of week for mobile PWA
const currentDayIndex = new Date().getDay(); // 0 = Sun, 1 = Mon ...
const dayMapNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const todayName = dayMapNames[currentDayIndex];
const selectedMobileDay = ref(days.includes(todayName) ? todayName : 'Senin');

// View Mode: 'personal' (Jadwal Saya) or 'class' (Cari Kelas)
const viewMode = ref(props.isTeacher ? 'personal' : 'class');

watch(selectedClassroom, (newVal) => {
    if (newVal) {
        router.get(route('yayasan.schedules.index'), { classroom_id: newVal }, { preserveState: true, preserveScroll: true });
        form.classroom_id = newVal;
        cloneForm.classroom_id = newVal;
        viewMode.value = 'class';
    }
});

const selectedClassroomName = computed(() => {
    const found = props.classrooms?.find(c => c.id === props.selectedClassroomId || c.id === selectedClassroom.value);
    return found ? found.name : '';
});

const openAddModal = (item = null) => {
    if (!props.selectedClassroomId && !selectedClassroom.value) {
        alert('Pilih kelas terlebih dahulu!');
        return;
    }
    
    form.clearErrors();

    if (item && item.id) {
        // Edit Mode
        isEditing.value = true;
        editingId.value = item.id;
        
        const sTime = item.start_time ? item.start_time.substring(0, 5) : '';
        const eTime = item.end_time ? item.end_time.substring(0, 5) : '';
        
        const matchedClassrooms = [];
        const dayItems = getSchedulesForDay(item.day) || [];
        dayItems.forEach(s => {
            const itemSTime = s.start_time ? s.start_time.substring(0, 5) : '';
            const itemETime = s.end_time ? s.end_time.substring(0, 5) : '';
            if (s.teacher_id === item.teacher_id && s.subject_id === item.subject_id && itemSTime === sTime && itemETime === eTime) {
                if (s.classroom_id && !matchedClassrooms.includes(s.classroom_id)) {
                    matchedClassrooms.push(s.classroom_id);
                }
            }
        });

        if (!matchedClassrooms.includes(item.classroom_id)) {
            matchedClassrooms.push(item.classroom_id);
        }

        isJointClass.value = matchedClassrooms.length > 1;
        selectedJointClassrooms.value = matchedClassrooms;
        form.classroom_id = item.classroom_id;
        form.classroom_ids = matchedClassrooms;
        form.subject_id = item.subject_id;
        form.teacher_id = item.teacher_id;
        form.day = item.day;
        form.start_time = sTime;
        form.end_time = eTime;
    } else {
        // Create Mode
        isEditing.value = false;
        editingId.value = null;
        isJointClass.value = false;
        const currentCid = props.selectedClassroomId || selectedClassroom.value;
        selectedJointClassrooms.value = currentCid ? [currentCid] : [];
        form.reset('subject_id', 'teacher_id', 'day', 'start_time', 'end_time');
        form.classroom_id = currentCid;
        form.classroom_ids = currentCid ? [currentCid] : [];
        form.day = selectedMobileDay.value;
    }
    showAddModal.value = true;
};

const openAddModalForDay = (targetDay) => {
    openAddModal(null);
    if (targetDay) {
        form.day = targetDay;
    }
};

const closeModal = () => {
    showAddModal.value = false;
    isEditing.value = false;
    editingId.value = null;
    isJointClass.value = false;
    form.clearErrors();
};

const openCloneModal = () => {
    if (!props.selectedClassroomId && !selectedClassroom.value) {
        alert('Pilih kelas terlebih dahulu!');
        return;
    }
    cloneForm.reset('from_day', 'to_day');
    cloneForm.clearErrors();
    showCloneModal.value = true;
};

const submitSchedule = () => {
    const targetCid = props.selectedClassroomId || selectedClassroom.value;
    if (isEditing.value && editingId.value) {
        if (isJointClass.value) {
            form.classroom_ids = selectedJointClassrooms.value;
            form.classroom_id = null;
        } else {
            form.classroom_id = form.classroom_id || targetCid;
            form.classroom_ids = [form.classroom_id];
        }

        form.put(route('yayasan.schedules.update', editingId.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                 closeModal();
            },
            onError: () => {
                 showAddModal.value = true;
            }
        });
    } else {
        if (isJointClass.value) {
            form.classroom_ids = selectedJointClassrooms.value;
            form.classroom_id = null;
        } else {
            form.classroom_id = targetCid;
            form.classroom_ids = [targetCid];
        }

        form.post(route('yayasan.schedules.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                 closeModal();
            },
            onError: () => {
                showAddModal.value = true;
            }
        });
    }
};

const submitClone = () => {
    cloneForm.post(route('yayasan.schedules.clone'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
             showCloneModal.value = false;
             cloneForm.reset('from_day', 'to_day');
        }
    });
};

const resetSchedule = () => {
    const targetCid = props.selectedClassroomId || selectedClassroom.value;
    if (!targetCid) return;
    if (confirm('Yakin ingin MENGHAPUS SEMUA jadwal kelas ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.post(route('yayasan.schedules.reset'), { classroom_id: targetCid }, { preserveScroll: true });
    }
};

const deleteSchedule = (id) => {
    if (confirm('Hapus jadwal ini?')) {
        router.delete(route('yayasan.schedules.destroy', id), { preserveScroll: true });
    }
};

const printSchedule = () => {
    const targetCid = props.selectedClassroomId || selectedClassroom.value;
    if (!targetCid) return;
    const url = route('yayasan.schedules.export-pdf', { classroom_id: targetCid });
    window.open(url, '_blank');
};

watch(() => props.selectedClassroomId, (newVal) => {
    if (newVal !== selectedClassroom.value) {
        selectedClassroom.value = newVal;
    }
});

const activeSchedule = computed(() => {
    if (viewMode.value === 'personal' && props.isTeacher) {
        return props.personalSchedule || {};
    }
    return props.schedule || {};
});

const getSchedulesForDay = (day) => {
    return activeSchedule.value[day] || [];
};

// Sort schedules by start_time
const sortedSchedules = (day) => {
    const list = getSchedulesForDay(day);
    if (!list || list.length === 0) return [];
    
    return [...list].sort((a, b) => {
        const timeA = a.start_time || '00:00';
        const timeB = b.start_time || '00:00';
        return timeA.localeCompare(timeB);
    });
};

const totalScheduleCount = computed(() => {
    let count = 0;
    days.forEach(day => {
        count += (sortedSchedules(day) || []).length;
    });
    return count;
});

const activeDayScheduleCount = computed(() => {
    return (sortedSchedules(selectedMobileDay.value) || []).length;
});

// Interactive Calendar State & Methods
const currentSelectedDate = ref(
    todayName === 'Minggu'
        ? dayjs().add(1, 'day').format('YYYY-MM-DD')
        : dayjs().format('YYYY-MM-DD')
);
const calendarViewingMonth = ref(dayjs());
const showCalendarModal = ref(false);
const calendarWeekDays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const selectedDateFormatted = computed(() => {
    return dayjs(currentSelectedDate.value).format('D MMM YYYY');
});

const viewingMonthTitle = computed(() => {
    return calendarViewingMonth.value.format('MMMM YYYY');
});

const openCalendarModal = () => {
    calendarViewingMonth.value = dayjs(currentSelectedDate.value);
    showCalendarModal.value = true;
};

const closeCalendarModal = () => {
    showCalendarModal.value = false;
};

const prevMonth = () => {
    calendarViewingMonth.value = calendarViewingMonth.value.subtract(1, 'month');
};

const nextMonth = () => {
    calendarViewingMonth.value = calendarViewingMonth.value.add(1, 'month');
};

const selectDate = (dateObj) => {
    const d = dayjs(dateObj);
    currentSelectedDate.value = d.format('YYYY-MM-DD');
    calendarViewingMonth.value = d;
    
    // Determine Indonesian day name
    const dayOfWeekIndex = d.day(); // 0 = Sun, 1 = Mon ...
    const dayName = dayMapNames[dayOfWeekIndex];
    selectedMobileDay.value = dayName;
    
    showCalendarModal.value = false;
};

const jumpToToday = () => {
    selectDate(dayjs());
};

const selectDay = (day) => {
    selectedMobileDay.value = day;
    
    const dayMap = {
        'Minggu': 0,
        'Senin': 1,
        'Selasa': 2,
        'Rabu': 3,
        'Kamis': 4,
        'Jumat': 5,
        'Sabtu': 6
    };
    const targetDayIndex = dayMap[day] ?? 1;
    const ref = dayjs(currentSelectedDate.value);
    const currentDayIndex = ref.day();
    const diff = targetDayIndex - currentDayIndex;
    currentSelectedDate.value = ref.add(diff, 'day').format('YYYY-MM-DD');
};

const onDateInput = (event) => {
    const val = event?.target?.value;
    if (!val) return;
    const d = dayjs(val);
    if (!d.isValid()) return;
    
    currentSelectedDate.value = d.format('YYYY-MM-DD');
    calendarViewingMonth.value = d;
    
    const dayOfWeekIndex = d.day(); // 0 = Sun, 1 = Mon ...
    const dayName = dayMapNames[dayOfWeekIndex];
    selectedMobileDay.value = dayName;
};

const calendarDays = computed(() => {
    const startOfMonth = calendarViewingMonth.value.startOf('month');
    const endOfMonth = calendarViewingMonth.value.endOf('month');
    
    const startDay = startOfMonth.day(); // 0 (Sun) to 6 (Sat)
    const endDay = endOfMonth.day();
    
    const startGrid = startOfMonth.subtract(startDay, 'day');
    const endGrid = endOfMonth.add(6 - endDay, 'day');
    
    const daysArr = [];
    let curr = startGrid;
    
    while (curr.isBefore(endGrid) || curr.isSame(endGrid, 'day')) {
        const isCurrentMonth = curr.month() === calendarViewingMonth.value.month();
        const dateStr = curr.format('YYYY-MM-DD');
        const isSelected = dateStr === currentSelectedDate.value;
        const isToday = dateStr === dayjs().format('YYYY-MM-DD');
        
        const dayName = dayMapNames[curr.day()];
        const scheduleList = sortedSchedules(dayName);
        const hasSchedule = scheduleList && scheduleList.length > 0;
        
        daysArr.push({
            date: curr,
            dateStr,
            dayNumber: curr.date(),
            dayName,
            isCurrentMonth,
            isSelected,
            isToday,
            hasSchedule,
            scheduleCount: scheduleList ? scheduleList.length : 0
        });
        
        curr = curr.add(1, 'day');
    }
    
    return daysArr;
});

// Helper for calculated date of the selected day in current week
const getDateForDay = (dayName) => {
    return selectedDateFormatted.value;
};

const getClassBadge = (item) => {
    const raw = item.classroom?.name || selectedClassroomName.value || '';
    return raw.replace(/^Kelas\s+/i, '').trim() || raw;
};

const getRoomName = (item) => {
    if (item.room) return item.room;
    if (item.classroom?.room) return item.classroom.room;
    if (item.classroom?.name) return `Ruang Kelas ${getClassBadge(item)}`;
    return null;
};

const getMateriText = (item) => {
    if (item.materi) return item.materi;
    if (item.notes) return item.notes;
    if (item.subject?.description) return item.subject.description;
    return null;
};
</script>

<template>
    <Head title="Jadwal Pelajaran" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        
        <!-- HEADER DESKTOP ONLY -->
        <template #header v-if="!isMobile">
            <div class="flex items-center justify-between w-full max-w-7xl mx-auto print:hidden">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">
                            Jadwal Pelajaran
                        </h2>
                        <span 
                            v-if="selectedClassroomName" 
                            class="px-2.5 py-0.5 bg-teal-50 text-[#0B7068] border border-teal-200/80 rounded-full text-xs font-extrabold"
                        >
                            Kelas {{ selectedClassroomName }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ canManage ? 'Atur jadwal pelajaran per kelas dan guru pengampu.' : 'Lihat jadwal mengajar Anda di sekolah.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Desktop Calendar / Date Button -->
                    <button 
                        @click="openCalendarModal"
                        type="button"
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-bold text-xs shadow-2xs flex items-center gap-2 transition-all cursor-pointer h-[42px]"
                        title="Buka Kalender"
                    >
                        <CalendarIcon class="w-4 h-4 text-[#0B7068] stroke-[2.2]" />
                        <span>{{ selectedDateFormatted }}</span>
                        <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400" />
                    </button>

                    <!-- Add Schedule Button (Single primary CTA on desktop) -->
                    <button 
                        v-if="canManage"
                        @click="openAddModal(null)"
                        type="button"
                        class="px-4 py-2 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm shadow-teal-900/10 flex items-center gap-2 transition-all cursor-pointer h-[42px]"
                        :class="{'opacity-75': !selectedClassroomId && !selectedClassroom}"
                        :title="!selectedClassroomId && !selectedClassroom ? 'Pilih kelas terlebih dahulu' : 'Tambah Jadwal Baru'"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah Jadwal</span>
                    </button>
                </div>
            </div>
        </template>
        
        <div class="max-w-7xl mx-auto px-4 py-2 sm:py-6 pb-28 sm:pb-8 w-full min-w-0 box-border space-y-3.5">

            <!-- 📱 MOBILE VIEW (PIXEL-PERFECT REFINEMENT) -->
            <div class="block md:hidden space-y-3">
                
                <!-- 1. COMPACT HERO BANNER CARD -->
                <div class="bg-gradient-to-br from-[#0B7068] via-[#085a53] to-[#0f172a] rounded-3xl p-4 sm:p-5 text-white shadow-xl relative overflow-hidden">
                    <!-- Subtle background glow -->
                    <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex items-center justify-between gap-2 relative z-10">
                        <div class="flex-1 min-w-0 pr-1">
                            <!-- Main Title (Clean & Dominant Single Line) -->
                            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-tight whitespace-nowrap">
                                Jadwal Pelajaran
                            </h1>

                            <!-- Subtitle -->
                            <p class="text-[11px] sm:text-xs text-white/80 mt-1 max-w-[210px] sm:max-w-xs leading-normal">
                                Lihat jadwal, kelas, dan mata pelajaran yang saya ampu.
                            </p>
                        </div>

                        <!-- 3D Calendar Illustration (Interactive Button) -->
                        <button 
                            @click="openCalendarModal"
                            type="button"
                            class="relative w-20 h-20 sm:w-24 sm:h-24 shrink-0 flex items-center justify-center cursor-pointer active:scale-95 transition-transform group"
                            title="Klik untuk buka kalender"
                        >
                            <!-- Glow circle -->
                            <div class="absolute inset-0 bg-white/10 group-hover:bg-white/20 rounded-full blur-lg transition-colors pointer-events-none"></div>
                            
                            <!-- Calendar Graphic -->
                            <svg class="w-18 h-18 sm:w-22 sm:h-22 drop-shadow-md relative z-10 transition-transform group-hover:scale-105" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Calendar Body (White) -->
                                <rect x="15" y="20" width="70" height="65" rx="14" fill="#FFFFFF" />
                                <!-- Calendar Header (Cyan / Mint) -->
                                <path d="M15 33C15 25.82 20.82 20 28 20H72C79.18 20 85 25.82 85 33V34H15V33Z" fill="#5EEAD4" />
                                <!-- Metal Binder Rings -->
                                <rect x="27" y="14" width="6" height="12" rx="3" fill="#0F766E" />
                                <rect x="67" y="14" width="6" height="12" rx="3" fill="#0F766E" />
                                <!-- Calendar Day Grid Dots -->
                                <rect x="25" y="44" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="41" y="44" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="57" y="44" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="25" y="56" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="41" y="56" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="57" y="56" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="25" y="68" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <rect x="41" y="68" width="8" height="6" rx="2" fill="#2DD4BF" opacity="0.65" />
                                <!-- Mint Clock Badge (Bottom Right) -->
                                <circle cx="76" cy="74" r="16" fill="#5EEAD4" stroke="#FFFFFF" stroke-width="3" />
                                <path d="M76 66V74H82" stroke="#0F766E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <!-- Stats Row Inside Banner (Compact 2-columns with No Cut-off) -->
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 mt-3 pt-1 relative z-10">
                        <!-- Stat 1: Total Jam Minggu Ini -->
                        <div class="bg-white/10 backdrop-blur-xs rounded-2xl p-2.5 border border-white/10 flex items-center gap-2 shadow-2xs min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <ClockIcon class="w-4 h-4 stroke-[2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-[8px] sm:text-[9px] font-bold text-white/70 uppercase tracking-wider block leading-tight truncate">
                                    TOTAL MINGGU INI
                                </span>
                                <span class="text-xs sm:text-sm font-black text-white leading-tight mt-0.5 block whitespace-nowrap">
                                    {{ totalScheduleCount }} Sesi
                                </span>
                            </div>
                        </div>

                        <!-- Stat 2: Jadwal Hari Ini (Interactive Quick Jump to Today) -->
                        <button 
                            @click="jumpToToday"
                            type="button"
                            class="bg-white/10 hover:bg-white/15 active:scale-98 transition-all backdrop-blur-xs rounded-2xl p-2.5 border border-white/10 flex items-center gap-2 shadow-2xs min-w-0 text-left cursor-pointer group"
                            title="Klik untuk lihat jadwal hari ini"
                        >
                            <div class="w-8 h-8 rounded-xl bg-white/15 group-hover:bg-white/25 text-white flex items-center justify-center shrink-0 transition-colors">
                                <CalendarIcon class="w-4 h-4 stroke-[2]" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-[8px] sm:text-[9px] font-bold text-white/70 uppercase tracking-wider block leading-tight truncate">
                                    JADWAL HARI INI
                                </span>
                                <div class="flex items-baseline gap-1 mt-0.5 whitespace-nowrap">
                                    <span class="text-xs sm:text-sm font-black text-white leading-tight">
                                        {{ activeDayScheduleCount }} Sesi
                                    </span>
                                    <span class="text-[10px] sm:text-xs font-normal text-white/75">
                                        ({{ selectedMobileDay }})
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. SEGMENTED NAVIGATION TABS (50%-50% Grid, Centered) -->
                <div class="bg-slate-100/90 p-1 rounded-2xl grid grid-cols-2 gap-1 w-full box-border">
                    <button 
                        @click="viewMode = 'personal'"
                        type="button"
                        :class="[
                            'py-2 px-3 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            viewMode === 'personal' 
                                ? 'bg-[#0B7068] text-white shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 bg-transparent'
                        ]"
                    >
                        <CalendarIcon class="w-4 h-4 stroke-[2.2]" />
                        <span>Jadwal Saya</span>
                    </button>

                    <button 
                        @click="viewMode = 'class'"
                        type="button"
                        :class="[
                            'py-2 px-3 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            viewMode === 'class' 
                                ? 'bg-[#0B7068] text-white shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 bg-transparent'
                        ]"
                    >
                        <MagnifyingGlassIcon class="w-4 h-4 stroke-[2.2]" />
                        <span>Cari Kelas</span>
                    </button>
                </div>

                <!-- Class Selector Filter (Visible when Cari Kelas is active or canManage) -->
                <div v-if="viewMode === 'class' || canManage" class="relative">
                    <select 
                        v-model="selectedClassroom" 
                        class="w-full bg-white border border-slate-200/90 rounded-2xl text-xs font-semibold pl-4 pr-9 py-2.5 focus:ring-1 focus:ring-[#0B7068] focus:border-[#0B7068] shadow-2xs appearance-none cursor-pointer"
                    >
                        <option :value="undefined">-- Pilih Kelas --</option>
                        <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                            Kelas {{ classroom.name }}
                        </option>
                    </select>
                    <ChevronDownIcon class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none" />
                </div>

                <!-- 3. DAY CHIPS / HORIZONTAL PILL FILTER (Edge-to-Edge Scrollable) -->
                <div class="relative -mx-4 px-4 overflow-x-auto pb-1 scrollbar-none pt-0.5">
                    <div class="flex items-center gap-1.5 sm:gap-2 w-max min-w-full">
                        <button 
                            v-for="day in days" 
                            :key="day"
                            @click="selectDay(day)"
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer',
                                selectedMobileDay === day 
                                    ? 'bg-[#0B7068] text-white shadow-xs' 
                                    : 'bg-white text-slate-700 border border-slate-200/90 shadow-2xs hover:border-slate-300'
                            ]"
                        >
                            <span>{{ day }}</span>
                            <span 
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-black min-w-4 text-center leading-none',
                                    selectedMobileDay === day 
                                        ? 'bg-teal-700/80 text-teal-100' 
                                        : 'bg-slate-100 text-slate-500'
                                ]"
                            >
                                {{ sortedSchedules(day).length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 4. SECTION SUBHEADER (Responsive Header + Dynamic Date Pill) -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 mb-1">
                    <div>
                        <h3 class="font-black text-slate-900 text-sm sm:text-base leading-tight">
                            Jadwal {{ selectedMobileDay }}
                        </h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium mt-0.5">
                            {{ sortedSchedules(selectedMobileDay).length }} sesi mengajar
                        </p>
                    </div>

                    <!-- Date Pill Button (Dual: Native Mobile Date Picker + Modal Trigger) -->
                    <div class="relative inline-flex items-center">
                        <button 
                            @click="openCalendarModal"
                            type="button"
                            class="bg-white hover:bg-teal-50/40 border border-slate-200/90 hover:border-teal-400 active:scale-95 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer shrink-0"
                            title="Pilih Tanggal di Kalender"
                        >
                            <CalendarIcon class="w-3.5 h-3.5 text-[#0B7068] stroke-[2.2]" />
                            <span>{{ selectedDateFormatted }}</span>
                            <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 stroke-[2]" />
                        </button>
                        <input 
                            type="date" 
                            :value="currentSelectedDate"
                            @input="onDateInput"
                            @change="onDateInput"
                            class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10"
                            title="Pilih Tanggal"
                        />
                    </div>
                </div>

                <!-- 5. SCHEDULE ITEM CARDS LIST (Responsive & No premature truncation) -->
                <div class="space-y-2.5">
                    <!-- If Class view and no class selected -->
                    <div v-if="!selectedClassroomId && viewMode === 'class'" class="bg-white rounded-3xl p-8 text-center border border-slate-100 shadow-2xs space-y-2">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-teal-50 text-[#0B7068] mb-1">
                            <CalendarIcon class="w-7 h-7 stroke-[1.8]" />
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">Pilih Kelas Terlebih Dahulu</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">
                            Silakan pilih kelas pada pilihan di atas untuk menampilkan jadwal pelajaran minggu ini.
                        </p>
                    </div>

                    <!-- Empty State for Day -->
                    <div v-else-if="sortedSchedules(selectedMobileDay).length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-100 shadow-2xs space-y-2">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-teal-50 text-[#0B7068] mb-1">
                            <ClockIcon class="w-7 h-7 stroke-[1.8]" />
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">Tidak Ada Jadwal di Hari {{ selectedMobileDay }}</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">
                            Belum ada mata pelajaran yang dijadwalkan pada hari ini.
                        </p>
                        <div v-if="canManage && (selectedClassroomId || selectedClassroom)" class="pt-2">
                            <button 
                                @click="openAddModal(null)" 
                                type="button"
                                class="px-3.5 py-1.5 bg-[#0B7068] text-white rounded-xl text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                            >
                                <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Tambah Jadwal Hari Ini</span>
                            </button>
                        </div>
                    </div>

                    <!-- Schedule Cards List -->
                    <div v-else class="space-y-2.5">
                        <div 
                            v-for="item in sortedSchedules(selectedMobileDay)" 
                            :key="item.id"
                            class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-100 shadow-2xs hover:border-slate-200 transition-all flex items-center justify-between gap-3 relative"
                        >
                            <!-- Time Pillar (Soft Mint Green) -->
                            <div class="bg-emerald-50 text-emerald-800 rounded-xl p-2 sm:p-2.5 text-center shrink-0 w-[62px] sm:w-[68px] flex flex-col items-center justify-center">
                                <span class="text-[11px] sm:text-xs font-black text-emerald-800 leading-none">
                                    {{ item.start_time.substring(0, 5) }}
                                </span>
                                <div class="w-2.5 h-0.5 bg-emerald-300 rounded-full my-1"></div>
                                <span class="text-[11px] sm:text-xs font-black text-emerald-800 leading-none">
                                    {{ item.end_time.substring(0, 5) }}
                                </span>
                            </div>

                            <!-- Middle Schedule Details -->
                            <div class="flex-1 min-w-0 space-y-1">
                                <!-- Row 1: Subject Name + Class Badge -->
                                <div class="flex items-start justify-between gap-1.5">
                                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm leading-snug break-words flex-1 min-w-0">
                                        {{ item.subject?.name || 'Mata Pelajaran' }}
                                    </h4>
                                    <!-- Amber Class Badge (e.g. 4A) -->
                                    <span 
                                        v-if="getClassBadge(item)" 
                                        class="bg-amber-50 text-amber-800 border border-amber-200/60 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 ml-1 self-start"
                                    >
                                        {{ getClassBadge(item) }}
                                    </span>
                                </div>

                                <!-- Row 2: Class Name -->
                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <UserGroupIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">Kelas {{ item.classroom?.name || selectedClassroomName }}</span>
                                </div>

                                <!-- Row 3: Room Name -->
                                <div v-if="getRoomName(item)" class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <MapPinIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">{{ getRoomName(item) }}</span>
                                </div>

                                <!-- Row 4: Material / Materi (Hanya tampil jika ada data riil, tanpa teks dummy) -->
                                <div v-if="getMateriText(item)" class="flex items-start gap-1.5 text-xs text-slate-500 font-medium">
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                                    <span class="break-words leading-tight">Materi: {{ getMateriText(item) }}</span>
                                </div>

                                <!-- Teacher Info (Only in Class view) -->
                                <div v-if="viewMode !== 'personal' && item.teacher?.full_name" class="flex items-center gap-1.5 text-xs text-slate-600 font-medium pt-0.5">
                                    <UserIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">Guru: {{ item.teacher.full_name }}</span>
                                </div>

                                <!-- Action Buttons (For Admin / Kurikulum) -->
                                <div v-if="canManage" class="flex items-center gap-2 pt-1.5">
                                    <button 
                                        @click="openAddModal(item)" 
                                        type="button"
                                        class="px-2.5 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors"
                                    >
                                        <PencilSquareIcon class="w-3.5 h-3.5" />
                                        <span>Edit</span>
                                    </button>
                                    <button 
                                        @click="deleteSchedule(item.id)" 
                                        type="button"
                                        class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors"
                                    >
                                        <TrashIcon class="w-3.5 h-3.5" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Right Chevron Icon -->
                            <ChevronRightIcon class="w-4 h-4 text-slate-300 shrink-0 self-center" />
                        </div>
                    </div>
                </div>

                <!-- Admin Action Button on Mobile if Selected Classroom -->
                <div v-if="canManage && (selectedClassroomId || selectedClassroom)" class="pt-2">
                    <button 
                        @click="openAddModal(null)"
                        type="button"
                        class="w-full py-3 bg-[#0B7068] hover:bg-[#095c55] active:scale-98 text-white rounded-2xl font-bold text-xs shadow-sm flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah Jadwal Baru</span>
                    </button>
                </div>
            </div>
            <!-- END MOBILE VIEW -->

            <!-- 🖥️ DESKTOP VIEW (hidden md:block) -->
            <div class="hidden md:block space-y-5">
                <!-- Toolbar Card -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-2xs flex flex-wrap items-center justify-between gap-3 print:hidden">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- View Toggle for Teachers -->
                        <div v-if="isTeacher" class="bg-slate-100 p-1 rounded-xl flex items-center h-[42px]">
                            <button 
                                @click="viewMode = 'personal'"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all h-full flex items-center gap-1.5 cursor-pointer"
                                :class="viewMode === 'personal' ? 'bg-white text-[#0B7068] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <UserIcon class="w-3.5 h-3.5" />
                                <span>Jadwal Saya</span>
                            </button>
                            <button 
                                @click="viewMode = 'class'"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all h-full flex items-center gap-1.5 cursor-pointer"
                                :class="viewMode === 'class' ? 'bg-white text-[#0B7068] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <UserGroupIcon class="w-3.5 h-3.5" />
                                <span>Cari Kelas</span>
                            </button>
                        </div>

                        <!-- Class Selector (Controlled width, not stretching infinitely!) -->
                        <div v-if="canManage || viewMode === 'class'" class="relative w-72 md:w-80">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#0B7068]">
                                <UserGroupIcon class="h-4 w-4 stroke-[2]" />
                            </div>
                            <select 
                                v-model="selectedClassroom" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] block w-full pl-10 pr-9 py-2 shadow-2xs hover:bg-slate-100/70 transition-all cursor-pointer h-[42px]"
                            >
                                <option :value="undefined" disabled>-- Pilih Kelas --</option>
                                <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                    Kelas {{ classroom.name }}
                                </option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <ChevronDownIcon class="h-4 w-4" />
                            </div>
                        </div>

                        <!-- Session Count Info -->
                        <div v-if="selectedClassroomId" class="hidden lg:flex items-center gap-2 text-xs text-slate-500 font-medium pl-2 border-l border-slate-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Total <strong class="text-slate-800 font-black">{{ totalScheduleCount }} Sesi</strong> terdata minggu ini</span>
                        </div>
                    </div>

                    <!-- Right Toolbar Controls: Actions / Print -->
                    <div class="flex items-center gap-2">
                        <!-- Smart Tools Dropdown (Aksi) -->
                        <Dropdown align="right" width="48" v-if="canManage && selectedClassroomId">
                            <template #trigger>
                                <button class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-xs hover:bg-slate-50 flex items-center gap-2 transition-all shadow-2xs h-[42px] cursor-pointer">
                                    <BoltIcon class="h-4 w-4 text-amber-500" />
                                    <span>Aksi</span>
                                    <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400" />
                                </button>
                            </template>
                            <template #content>
                                <button @click="openCloneModal" class="flex w-full items-center px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 gap-2.5 transition-colors cursor-pointer">
                                    <DocumentDuplicateIcon class="w-4 h-4 text-slate-400" />
                                    <span>Clone Jadwal</span>
                                </button>
                                <button @click="printSchedule" class="flex w-full items-center px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 gap-2.5 transition-colors cursor-pointer">
                                    <PrinterIcon class="w-4 h-4 text-slate-400" />
                                    <span>Cetak PDF</span>
                                </button>
                                <div class="border-t border-slate-100 my-1"></div>
                                <button @click="resetSchedule" class="flex w-full items-center px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 gap-2.5 transition-colors cursor-pointer">
                                    <TrashIcon class="w-4 h-4 text-red-500" />
                                    <span>Reset Jadwal</span>
                                </button>
                            </template>
                        </Dropdown>

                        <!-- Print Button for Teacher -->
                        <button 
                            v-if="isTeacher && selectedClassroomId && viewMode === 'class'"
                            @click="printSchedule"
                            class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-xs hover:bg-slate-50 flex items-center gap-2 transition-all shadow-2xs h-[42px] cursor-pointer"
                        >
                            <PrinterIcon class="w-4 h-4 text-slate-500" />
                            <span>Cetak PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Print Header -->
                <div class="hidden print:block text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Jadwal Pelajaran</h1>
                    <p class="text-xl text-gray-600 mt-2">
                        {{ viewMode === 'personal' && isTeacher ? 'Jadwal Mengajar Saya' : (classrooms.find(c => c.id === selectedClassroomId)?.name || 'Kelas') }}
                    </p>
                </div>

                <div v-if="!selectedClassroomId && viewMode === 'class'" class="flex flex-col items-center justify-center min-h-[400px] text-center p-8 bg-white rounded-3xl border border-slate-100 shadow-2xs">
                    <div class="bg-teal-50 p-6 rounded-3xl shadow-sm mb-4">
                        <CalendarIcon class="w-14 h-14 text-[#0B7068]" />
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1">Pilih Kelas Terlebih Dahulu</h3>
                    <p class="text-xs text-gray-400 max-w-md mx-auto">Silakan pilih kelas pada menu dropdown di atas untuk menampilkan jadwal pelajaran minggu ini.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 print:grid-cols-3 print:gap-6">
                    <!-- Day Column -->
                    <div v-for="day in days" :key="day" class="flex flex-col gap-3 break-inside-avoid">
                        <!-- Day Header -->
                        <div 
                            class="p-3 rounded-2xl shadow-2xs border text-center sticky top-0 z-10 transition-all print:border-black print:shadow-none"
                            :class="day === todayName 
                                ? 'bg-gradient-to-b from-teal-50/90 to-white border-teal-300 ring-1 ring-teal-500/20' 
                                : 'bg-white border-slate-100'"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 
                                        class="font-black text-sm tracking-tight truncate"
                                        :class="day === todayName ? 'text-[#0B7068]' : 'text-slate-800'"
                                    >
                                        {{ day }}
                                    </h3>
                                    <span 
                                        v-if="day === todayName" 
                                        class="px-1.5 py-0.5 bg-[#0B7068] text-white text-[9px] font-black rounded-full shrink-0 uppercase tracking-wider"
                                    >
                                        Hari Ini
                                    </span>
                                </div>
                                <span class="text-[10px] font-black text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-md shrink-0">
                                    {{ sortedSchedules(day).length }}
                                </span>
                            </div>
                            <div 
                                class="h-1 rounded-full mx-auto print:bg-black transition-colors"
                                :class="day === todayName ? 'w-10 bg-[#0B7068]' : 'w-6 bg-slate-200'"
                            ></div>
                        </div>

                        <!-- Empty State for Day -->
                        <div 
                            v-if="sortedSchedules(day).length === 0" 
                            class="flex-1 bg-slate-50/70 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center min-h-[140px] p-4 text-center group hover:bg-slate-100/60 hover:border-slate-300 transition-all print:hidden"
                        >
                            <div class="bg-white rounded-full p-2 mb-2 group-hover:scale-110 transition-transform shadow-2xs text-slate-400">
                                <ClockIcon class="w-4 h-4" />
                            </div>
                            <p class="text-xs font-bold text-slate-400">Tidak ada jadwal</p>
                            <p class="text-[10px] text-slate-400/80 mt-0.5">Santai sejenak ☕</p>
                            
                            <!-- Quick add button on hover if canManage -->
                            <button 
                                v-if="canManage && (selectedClassroomId || selectedClassroom)"
                                @click="openAddModalForDay(day)"
                                type="button"
                                class="mt-3 opacity-0 group-hover:opacity-100 px-2.5 py-1 bg-white hover:bg-teal-50 border border-slate-200 hover:border-teal-200 text-teal-800 rounded-lg text-[10px] font-bold shadow-2xs transition-all flex items-center gap-1 cursor-pointer"
                            >
                                <PlusIcon class="w-3 h-3 stroke-[2.5]" />
                                <span>Tambah</span>
                            </button>
                        </div>

                        <!-- Schedule Cards -->
                        <div 
                            v-for="item in sortedSchedules(day)" 
                            :key="item.id" 
                            class="bg-white p-3.5 rounded-2xl shadow-2xs border border-slate-100 hover:border-teal-200 hover:shadow-md transition-all relative group print:border-black print:shadow-none space-y-2 border-l-[3px] border-l-[#0B7068]"
                            :class="{'ring-2 ring-[#0B7068]/20': isTeacher && viewMode === 'personal'}"
                        >
                            <!-- Time & Badges -->
                            <div class="flex items-center justify-between gap-1">
                                <div class="px-2 py-0.5 bg-teal-50/90 text-teal-800 text-[10px] font-bold rounded-lg border border-teal-100/80 flex items-center gap-1 font-mono">
                                    <ClockIcon class="w-3 h-3 text-[#0B7068]" />
                                    <span>{{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }}</span>
                                </div>
                                <!-- Show Class Name if in Personal Mode -->
                                <div v-if="viewMode === 'personal'" class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-bold rounded-lg border border-amber-200/60 truncate max-w-[80px]">
                                    {{ item.classroom?.name }}
                                </div>
                            </div>

                            <!-- Subject -->
                            <h4 class="font-bold text-slate-900 text-xs leading-snug line-clamp-2">
                                {{ item.subject?.name }}
                            </h4>
                            
                            <!-- Teacher (Only show if NOT in personal mode) -->
                            <div v-if="viewMode !== 'personal'" class="flex items-center gap-2 pt-1 border-t border-slate-100 print:border-gray-200">
                                <div class="shrink-0 w-5 h-5 rounded-full bg-teal-50 border border-teal-100 flex items-center justify-center text-[10px] font-black text-[#0B7068] print:hidden">
                                    {{ item.teacher?.full_name?.charAt(0) || '?' }}
                                </div>
                                <p class="text-xs text-slate-500 truncate" :title="item.teacher?.full_name">
                                    {{ item.teacher?.full_name }}
                                </p>
                            </div>

                            <!-- Actions (For Admin / Yayasan / Koordinator Kurikulum) -->
                            <div v-if="canManage" class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity print:hidden">
                                <button 
                                    @click="openAddModal(item)"
                                    class="p-1 text-slate-400 hover:text-[#0B7068] hover:bg-teal-50 bg-white rounded-lg shadow-2xs border border-slate-100 transition-colors cursor-pointer"
                                    title="Edit Jadwal"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                </button>
                                <button 
                                    @click="deleteSchedule(item.id)"
                                    class="p-1 text-slate-400 hover:text-red-500 hover:bg-red-50 bg-white rounded-lg shadow-2xs border border-slate-100 transition-colors cursor-pointer"
                                    title="Hapus Jadwal"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END DESKTOP VIEW -->
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="showAddModal" @close="closeModal" max-width="md">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-bold text-gray-900">
                        {{ isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}
                    </h2>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>
                
                <div class="space-y-4">
                    <!-- Sesi Kelas Gabungan Toggle (Create & Edit Modes) -->
                    <div class="p-3.5 bg-teal-50/70 rounded-2xl border border-teal-100/90 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-black text-teal-900 block">Sesi Kelas Gabungan (Multi-Class)</span>
                            <span class="text-[11px] text-teal-700 font-medium">Contoh: PJOK / Pramuka 1 guru mengajar beberapa kelas sekaligus</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-3 shrink-0">
                            <input type="checkbox" v-model="isJointClass" class="sr-only peer">
                            <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0B7068]"></div>
                        </label>
                    </div>

                    <!-- Multi-Class Checkboxes Grid if Joint Class -->
                    <div v-if="isJointClass" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <InputLabel value="Centang Kelas yang Digabungkan *" class="text-xs font-extrabold text-slate-700" />
                            <span class="text-[10px] font-black text-teal-700 bg-teal-100/80 px-2 py-0.5 rounded-full">{{ selectedJointClassrooms.length }} Kelas Terpilih</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <label 
                                v-for="classroom in classrooms" 
                                :key="'joint-' + classroom.id" 
                                class="flex items-center gap-2 p-2 bg-white rounded-xl border cursor-pointer transition text-xs font-semibold select-none shadow-xs"
                                :class="selectedJointClassrooms.includes(classroom.id) ? 'border-teal-500 bg-teal-50/40 text-teal-900 ring-1 ring-teal-500/20' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="classroom.id" 
                                    v-model="selectedJointClassrooms" 
                                    class="rounded border-slate-300 text-[#0B7068] focus:ring-[#0B7068] w-4 h-4 cursor-pointer"
                                >
                                <span class="truncate">Kelas {{ classroom.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.classroom_id" class="mt-1" />
                    </div>

                    <!-- Day -->
                    <div>
                        <InputLabel for="day" value="Hari" />
                        <select id="day" v-model="form.day" class="w-full mt-1 h-11 px-4 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold">
                            <option value="">-- Pilih Hari --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <InputError :message="form.errors.day" class="mt-1" />
                    </div>

                    <!-- Time Range -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="start_time" value="Jam Mulai" />
                            <input type="time" id="start_time" v-model="form.start_time" class="w-full mt-1 h-11 px-3 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold" />
                        </div>
                        <div>
                            <InputLabel for="end_time" value="Jam Selesai" />
                            <input type="time" id="end_time" v-model="form.end_time" class="w-full mt-1 h-11 px-3 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold" />
                            <InputError :message="form.errors.end_time" class="mt-1" />
                        </div>
                    </div>

                    <!-- Subject -->
                    <div>
                        <InputLabel for="subject" value="Mata Pelajaran" />
                        <select id="subject" v-model="form.subject_id" class="w-full mt-1 h-11 px-4 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold">
                            <option value="">-- Pilih Mapel --</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                {{ subject.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subject_id" class="mt-1" />
                    </div>

                    <!-- Teacher -->
                    <div>
                        <InputLabel for="teacher" value="Guru Pengampu" />
                        <select id="teacher" v-model="form.teacher_id" class="w-full mt-1 h-11 px-4 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold">
                            <option value="">-- Pilih Guru --</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                {{ teacher.full_name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.teacher_id" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2.5 pt-2 border-t border-gray-100">
                    <button 
                        @click="closeModal" 
                        type="button" 
                        class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="submitSchedule" 
                        :disabled="form.processing"
                        type="button" 
                        class="px-4 py-2 bg-[#0B7068] text-white hover:bg-[#095c55] rounded-xl text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Clone Modal (Only for Admin) -->
        <Modal :show="showCloneModal" @close="showCloneModal = false" max-width="sm">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-bold text-gray-900">Clone Jadwal (Copy-Paste)</h2>
                    <button @click="showCloneModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Salin semua jadwal dari satu hari ke hari lain. Jadwal yang bentrok akan diabaikan secara otomatis.
                </p>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="from_day" value="Dari Hari" />
                        <select id="from_day" v-model="cloneForm.from_day" class="w-full mt-1 h-11 px-4 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold">
                            <option value="">-- Pilih Hari Asal --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <InputError :message="cloneForm.errors.from_day" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="to_day" value="Ke Hari" />
                        <select id="to_day" v-model="cloneForm.to_day" class="w-full mt-1 h-11 px-4 border-gray-200 rounded-xl focus:ring-[#0B7068] focus:border-[#0B7068] text-xs font-semibold">
                            <option value="">-- Pilih Hari Tujuan --</option>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <InputError :message="cloneForm.errors.to_day" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2.5 pt-2 border-t border-gray-100">
                    <button 
                        @click="showCloneModal = false" 
                        type="button" 
                        class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="submitClone" 
                        :disabled="cloneForm.processing"
                        type="button" 
                        class="px-4 py-2 bg-[#0B7068] text-white hover:bg-[#095c55] rounded-xl text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        Clone Sekarang
                    </button>
                </div>
            </div>
        </Modal>

        <!-- 📅 CALENDAR MODAL (Interactive Date Picker for Jadwal Pelajaran) -->
        <Modal :show="showCalendarModal" @close="closeCalendarModal" max-width="sm">
            <div class="w-[calc(100vw-2rem)] max-w-sm mx-auto p-4 sm:p-5 space-y-3.5">
                
                <!-- Header: Title + Close Button -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-[#0B7068] flex items-center justify-center">
                            <CalendarIcon class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Pilih Tanggal Jadwal</h3>
                            <p class="text-[10px] text-slate-400">Pilih tanggal untuk melihat jadwal mengajar</p>
                        </div>
                    </div>
                    <button 
                        @click="closeCalendarModal" 
                        type="button"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Month Navigation (Prev / Month-Year / Next) -->
                <div class="flex items-center justify-between px-1">
                    <button 
                        @click="prevMonth" 
                        type="button"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-600 active:scale-95 transition-all cursor-pointer"
                        title="Bulan Sebelumnya"
                    >
                        <ChevronLeftIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                    <span class="text-xs sm:text-sm font-black text-slate-800 capitalize tracking-tight">
                        {{ viewingMonthTitle }}
                    </span>
                    <button 
                        @click="nextMonth" 
                        type="button"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-600 active:scale-95 transition-all cursor-pointer"
                        title="Bulan Berikutnya"
                    >
                        <ChevronRightIcon class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- Day of Week Headers (Min, Sen, Sel, Rab, Kam, Jum, Sab) -->
                <div class="grid grid-cols-7 gap-1 text-center">
                    <span 
                        v-for="(wDay, idx) in calendarWeekDays" 
                        :key="wDay" 
                        :class="[
                            'text-[10px] font-black py-1 select-none',
                            idx === 0 ? 'text-rose-500' : 'text-slate-500'
                        ]"
                    >
                        {{ wDay }}
                    </span>
                </div>

                <!-- Days Grid (7 columns) -->
                <div class="grid grid-cols-7 gap-1 text-center">
                    <button 
                        v-for="item in calendarDays" 
                        :key="item.dateStr"
                        @click="selectDate(item.date)"
                        type="button"
                        :class="[
                            'relative h-9 sm:h-10 w-full rounded-xl flex flex-col items-center justify-center text-xs font-semibold transition-all cursor-pointer active:scale-90',
                            item.isSelected 
                                ? 'bg-[#0B7068] text-white font-bold shadow-sm ring-2 ring-[#0B7068]/30 z-10' 
                                : item.isToday
                                    ? 'bg-teal-50 text-[#0B7068] font-bold border border-[#0B7068]/40 hover:bg-teal-100/70'
                                    : item.isCurrentMonth
                                        ? 'text-slate-800 hover:bg-slate-100'
                                        : 'text-slate-300 hover:bg-slate-50'
                        ]"
                    >
                        <!-- Day Number -->
                        <span class="leading-none">{{ item.dayNumber }}</span>

                        <!-- Schedule indicator dot (If that weekday has sessions) -->
                        <div v-if="item.hasSchedule && item.isCurrentMonth" class="absolute bottom-1 flex items-center justify-center">
                            <span 
                                :class="[
                                    'w-1.5 h-1.5 rounded-full',
                                    item.isSelected ? 'bg-white' : 'bg-[#0B7068]'
                                ]"
                            ></span>
                        </div>
                    </button>
                </div>

                <!-- Legend / Info Row -->
                <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 px-1 border-t border-slate-100">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#0B7068]"></span>
                        <span>Ada Jadwal</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full border border-[#0B7068]/60 bg-teal-50"></span>
                        <span>Hari Ini</span>
                    </div>
                </div>

                <!-- Quick Actions Footer -->
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                    <button 
                        @click="jumpToToday" 
                        type="button" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 active:scale-95 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                        <CalendarIcon class="w-3.5 h-3.5 text-[#0B7068]" />
                        <span>Hari Ini</span>
                    </button>

                    <button 
                        @click="closeCalendarModal" 
                        type="button" 
                        class="px-4 py-1.5 bg-[#0B7068] hover:bg-[#095c55] text-white active:scale-95 rounded-xl text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                    >
                        Selesai
                    </button>
                </div>

            </div>
        </Modal>

    </component>
</template>
