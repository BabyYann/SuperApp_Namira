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
    SparklesIcon
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

// Helper for calculated date of the selected day in current week
const getDateForDay = (dayName) => {
    const dayMap = {
        'Senin': 1,
        'Selasa': 2,
        'Rabu': 3,
        'Kamis': 4,
        'Jumat': 5,
        'Sabtu': 6,
        'Minggu': 0
    };
    const targetDay = dayMap[dayName] ?? 1;
    const now = dayjs();
    const currentDay = now.day();
    const diff = targetDay - (currentDay === 0 ? 7 : currentDay);
    return now.add(diff, 'day').format('DD MMM YYYY');
};

const getClassBadge = (item) => {
    const raw = item.classroom?.name || selectedClassroomName.value || '';
    return raw.replace(/^Kelas\s+/i, '').trim() || raw;
};

const getRoomName = (item) => {
    if (item.room) return item.room;
    if (item.classroom?.room) return item.classroom.room;
    const subName = (item.subject?.name || '').toLowerCase();
    if (subName.includes('komputer') || subName.includes('informatika') || subName.includes('it')) {
        return 'Ruang Lab Komputer';
    }
    if (subName.includes('ipa') || subName.includes('sains') || subName.includes('biologi') || subName.includes('fisika')) {
        return 'Ruang Lab IPA';
    }
    if (subName.includes('olahraga') || subName.includes('penjas') || subName.includes('pjok')) {
        return 'Lapangan Olahraga';
    }
    if (subName.includes('agama') || subName.includes('tahfidz') || subName.includes('qur\'an')) {
        return 'Ruang Musholla / Kelas';
    }
    const cName = getClassBadge(item);
    return cName ? `Ruang Kelas ${cName}` : 'Ruang Kelas';
};

const getMateriText = (item) => {
    if (item.materi) return item.materi;
    if (item.subject?.description) return item.subject.description;
    const subName = (item.subject?.name || '').toLowerCase();
    if (subName.includes('komputer') || subName.includes('informatika')) {
        return 'Pengenalan Perangkat Komputer';
    }
    if (subName.includes('matematika')) {
        return 'Operasi Hitung Bilangan & Logika';
    }
    if (subName.includes('indonesia')) {
        return 'Membaca Pemahaman & Menulis';
    }
    if (subName.includes('inggris')) {
        return 'Daily Vocabulary & Conversation';
    }
    return `Pengenalan Materi ${item.subject?.name || 'Pembelajaran'}`;
};
</script>

<template>
    <Head title="Jadwal Pelajaran" />

    <component :is="isMobile ? MobileAppShell : AuthenticatedLayout">
        
        <!-- HEADER DESKTOP ONLY -->
        <template #header v-if="!isMobile">
            <div class="flex flex-col gap-4 print:hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">
                            Jadwal Pelajaran
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ canManage ? 'Atur jadwal pelajaran per kelas dan guru pengampu.' : 'Lihat jadwal mengajar Anda di sekolah.' }}
                        </p>
                    </div>

                    <div v-if="canManage && selectedClassroomId" class="flex items-center gap-2">
                        <button 
                            @click="openAddModal(null)"
                            type="button"
                            class="px-4 py-2 bg-[#0B7068] hover:bg-[#095c55] active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm flex items-center gap-1.5 transition-all"
                        >
                            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>Tambah Jadwal</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <div class="max-w-7xl mx-auto px-4 py-2 sm:py-6 pb-28 sm:pb-8 w-full min-w-0 box-border space-y-4">

            <!-- 📱 MOBILE VIEW (MATCHING MOCKUP DESIGN PRECISELY) -->
            <div class="block md:hidden space-y-3.5">
                
                <!-- 1. HERO BANNER CARD (GRADIENT + 3D CALENDAR SVG + STATS ROW) -->
                <div class="bg-gradient-to-br from-[#0B7068] via-[#085a53] to-[#0f172a] rounded-3xl p-5 text-white shadow-xl relative overflow-hidden">
                    <!-- Subtle background glow -->
                    <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex items-start justify-between relative z-10">
                        <div class="flex-1 pr-2 min-w-0">
                            <!-- Top Pill Badge -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-white/95 text-[10px] font-bold uppercase tracking-wider border border-white/15 shadow-2xs">
                                <BookOpenIcon class="w-3.5 h-3.5 stroke-[2.2]" />
                                <span class="truncate">
                                    {{ viewMode === 'personal' && isTeacher ? 'JADWAL MENGAJAR SAYA' : (selectedClassroomName ? `JADWAL KELAS ${selectedClassroomName}` : 'JADWAL PELAJARAN') }}
                                </span>
                            </div>

                            <!-- Main Title -->
                            <h1 class="text-2xl font-black text-white tracking-tight leading-tight mt-2.5">
                                Jadwal Pelajaran
                            </h1>

                            <!-- Subtitle -->
                            <p class="text-xs text-white/80 mt-1 max-w-[210px] leading-relaxed">
                                Lihat jadwal, kelas, dan mata pelajaran yang saya ampu.
                            </p>
                        </div>

                        <!-- 3D Calendar Illustration (Matching Reference Image) -->
                        <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                            <!-- Subtle circular background glow & orbit rings -->
                            <div class="absolute -right-1 -top-1 w-20 h-20 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
                            <div class="absolute right-6 top-1 w-8 h-8 rounded-full border border-white/15 pointer-events-none"></div>
                            <div class="absolute right-0 bottom-4 w-12 h-12 rounded-full border border-white/10 pointer-events-none"></div>
                            
                            <!-- Calendar Graphic -->
                            <svg class="w-20 h-20 drop-shadow-md relative z-10" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                        </div>
                    </div>

                    <!-- Stats Row Inside Banner -->
                    <div class="grid grid-cols-2 gap-2.5 mt-4 pt-1 relative z-10">
                        <!-- Stat 1: Total Jam Minggu Ini -->
                        <div class="bg-white/10 backdrop-blur-xs rounded-2xl p-2.5 sm:p-3 border border-white/10 flex items-center gap-2.5 shadow-2xs">
                            <div class="w-9 h-9 rounded-xl bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <ClockIcon class="w-5 h-5 stroke-[2]" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[9px] font-bold text-white/70 uppercase tracking-wider block leading-tight">
                                    TOTAL JAM MINGGU INI
                                </span>
                                <span class="text-sm sm:text-base font-black text-white leading-tight mt-0.5 block truncate">
                                    {{ totalScheduleCount }} Sesi
                                </span>
                            </div>
                        </div>

                        <!-- Stat 2: Jadwal Hari Ini -->
                        <div class="bg-white/10 backdrop-blur-xs rounded-2xl p-2.5 sm:p-3 border border-white/10 flex items-center gap-2.5 shadow-2xs">
                            <div class="w-9 h-9 rounded-xl bg-white/15 text-white flex items-center justify-center shrink-0">
                                <CalendarIcon class="w-5 h-5 stroke-[2]" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[9px] font-bold text-white/70 uppercase tracking-wider block leading-tight">
                                    JADWAL HARI INI
                                </span>
                                <span class="text-sm sm:text-base font-black text-white leading-tight mt-0.5 block truncate">
                                    {{ activeDayScheduleCount }} Sesi <span class="text-xs font-normal text-white/70">({{ selectedMobileDay }})</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SEGMENTED NAVIGATION TABS: Jadwal Saya vs Cari Kelas -->
                <div class="bg-slate-100/90 p-1.5 rounded-2xl flex items-center gap-1.5">
                    <button 
                        @click="viewMode = 'personal'"
                        type="button"
                        :class="[
                            'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            viewMode === 'personal' 
                                ? 'bg-[#0B7068] text-white shadow-xs' 
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        <CalendarIcon class="w-4 h-4 stroke-[2.2]" />
                        <span>Jadwal Saya</span>
                    </button>

                    <button 
                        @click="viewMode = 'class'"
                        type="button"
                        :class="[
                            'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer',
                            viewMode === 'class' 
                                ? 'bg-[#0B7068] text-white shadow-xs' 
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        <MagnifyingGlassIcon class="w-4 h-4 stroke-[2.2]" />
                        <span>Cari Kelas</span>
                    </button>
                </div>

                <!-- Class Selector Filter (Visible when Cari Kelas tab is active or canManage) -->
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

                <!-- 3. DAY CHIPS / HORIZONTAL PILL FILTER (Senin 4, Selasa 2, Rabu 2, Kamis 1, ...) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none pt-0.5">
                    <button 
                        v-for="day in days" 
                        :key="day"
                        @click="selectedMobileDay = day"
                        type="button"
                        :class="[
                            'px-3.5 py-2 rounded-2xl text-xs font-bold shrink-0 transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer',
                            selectedMobileDay === day 
                                ? 'bg-[#0B7068] text-white shadow-xs' 
                                : 'bg-white text-slate-700 border border-slate-200/90 shadow-2xs hover:border-slate-300'
                        ]"
                    >
                        <span>{{ day }}</span>
                        <span 
                            :class="[
                                'px-1.5 py-0.5 rounded-full text-[10px] font-black min-w-4 text-center leading-none',
                                selectedMobileDay === day 
                                    ? 'bg-teal-700/80 text-teal-100' 
                                    : 'bg-slate-100 text-slate-500'
                            ]"
                        >
                            {{ sortedSchedules(day).length }}
                        </span>
                    </button>
                </div>

                <!-- 4. SECTION SUBHEADER (Jadwal Kamis + Sesi Mengajar + Date Dropdown Pill) -->
                <div class="flex items-center justify-between pt-1 mb-1">
                    <div>
                        <h3 class="font-black text-slate-900 text-base leading-tight">
                            Jadwal {{ selectedMobileDay }}
                        </h3>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">
                            {{ sortedSchedules(selectedMobileDay).length }} sesi mengajar
                        </p>
                    </div>

                    <!-- Date Pill Dropdown (07 Nov 2024 ⌄) -->
                    <div class="bg-white border border-slate-200/80 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs flex items-center gap-1.5">
                        <CalendarIcon class="w-3.5 h-3.5 text-slate-400 stroke-[2]" />
                        <span>{{ getDateForDay(selectedMobileDay) }}</span>
                        <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 stroke-[2]" />
                    </div>
                </div>

                <!-- 5. SCHEDULE ITEM CARDS LIST (PIXEL-PERFECT MATCH TO REFERENCE IMAGE) -->
                <div class="space-y-3">
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
                    <div v-else class="space-y-3">
                        <div 
                            v-for="item in sortedSchedules(selectedMobileDay)" 
                            :key="item.id"
                            class="bg-white rounded-2xl p-4 border border-slate-100 shadow-2xs hover:border-slate-200 transition-all flex items-center justify-between gap-3 relative"
                        >
                            <!-- Android Vertical Time Pillar (Soft Mint Green) -->
                            <div class="bg-emerald-50 text-emerald-800 rounded-2xl p-2.5 sm:p-3 text-center shrink-0 min-w-[70px] sm:min-w-[76px] flex flex-col items-center justify-center">
                                <span class="text-xs font-black text-emerald-800 leading-none">
                                    {{ item.start_time.substring(0, 5) }}
                                </span>
                                <div class="w-3 h-0.5 bg-emerald-300 rounded-full my-1"></div>
                                <span class="text-xs font-black text-emerald-800 leading-none">
                                    {{ item.end_time.substring(0, 5) }}
                                </span>
                            </div>

                            <!-- Middle Schedule Details -->
                            <div class="flex-1 min-w-0 space-y-1">
                                <!-- Row 1: Subject Name + Class Badge -->
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-bold text-slate-900 text-sm leading-snug truncate">
                                        {{ item.subject?.name || 'Mata Pelajaran' }}
                                    </h4>
                                    <!-- Amber Class Badge (e.g. 4A) -->
                                    <span class="bg-amber-50 text-amber-800 border border-amber-200/60 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                        {{ getClassBadge(item) }}
                                    </span>
                                </div>

                                <!-- Row 2: Class Name -->
                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <UserGroupIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">Kelas {{ item.classroom?.name || selectedClassroomName }}</span>
                                </div>

                                <!-- Row 3: Room Name -->
                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <MapPinIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">{{ getRoomName(item) }}</span>
                                </div>

                                <!-- Row 4: Material / Materi -->
                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium truncate">
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">Materi: {{ getMateriText(item) }}</span>
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
                            <ChevronRightIcon class="w-4 h-4 text-slate-300 shrink-0" />
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
            <div class="hidden md:block space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row items-center gap-4 print:hidden">
                    <!-- View Toggle for Teachers -->
                    <div v-if="isTeacher" class="bg-gray-100 p-1 rounded-2xl flex items-center h-[46px]">
                        <button 
                            @click="viewMode = 'personal'"
                            class="px-4 py-2 text-xs font-bold rounded-xl transition-all h-full flex items-center"
                            :class="viewMode === 'personal' ? 'bg-white text-namira-teal shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            Jadwal Saya
                        </button>
                        <button 
                            @click="viewMode = 'class'"
                            class="px-4 py-2 text-xs font-bold rounded-xl transition-all h-full flex items-center"
                            :class="viewMode === 'class' ? 'bg-white text-namira-teal shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            Cari Kelas
                        </button>
                    </div>

                    <!-- Class Selector -->
                    <div v-if="canManage || viewMode === 'class'" class="relative flex-1 w-full md:w-auto">
                        <select 
                            v-model="selectedClassroom" 
                            class="appearance-none bg-white border border-gray-200 text-gray-700 text-sm rounded-2xl focus:ring-[#0B7068] focus:border-[#0B7068] block w-full pl-4 pr-10 py-2.5 shadow-2xs hover:bg-slate-50 transition-all cursor-pointer h-[46px]"
                        >
                            <option :value="undefined" disabled>-- Pilih Kelas --</option>
                            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                Kelas {{ classroom.name }}
                            </option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <ChevronDownIcon class="h-4 w-4" />
                        </div>
                    </div>

                    <!-- Actions (For Admin / Yayasan / Koordinator Kurikulum) -->
                    <template v-if="canManage">
                        <!-- Smart Tools Dropdown -->
                        <Dropdown align="right" width="48" v-if="selectedClassroomId">
                            <template #trigger>
                                <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-2xl font-bold hover:bg-slate-50 flex items-center gap-2 transition-all shadow-2xs h-[46px]">
                                    <span>Aksi</span>
                                    <BoltIcon class="h-4 w-4" />
                                </button>
                            </template>
                            <template #content>
                                <button @click="openCloneModal" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 gap-2">
                                    <DocumentDuplicateIcon class="w-4 h-4" />
                                    Clone Jadwal
                                </button>
                                <button @click="printSchedule" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 gap-2">
                                    <PrinterIcon class="w-4 h-4" />
                                    Cetak PDF
                                </button>
                                <div class="border-t border-gray-100"></div>
                                <button @click="resetSchedule" class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 gap-2">
                                    <TrashIcon class="w-4 h-4" />
                                    Reset Jadwal
                                </button>
                            </template>
                        </Dropdown>

                        <button 
                            @click="openAddModal(null)"
                            class="px-6 py-2.5 bg-[#0B7068] text-white rounded-2xl font-bold shadow-lg shadow-teal-900/20 hover:bg-[#095c55] transition-all flex items-center gap-2 transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed h-[46px]"
                            :class="{'opacity-50 cursor-not-allowed': !selectedClassroomId}"
                            :disabled="!selectedClassroomId"
                        >
                            <PlusIcon class="w-5 h-5 stroke-[2.5]" />
                            <span>Tambah</span>
                        </button>
                    </template>
                    
                    <!-- Print Button for Teacher -->
                    <button 
                        v-if="isTeacher && selectedClassroomId && viewMode === 'class'"
                        @click="printSchedule"
                        class="px-4 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-2xl font-bold hover:bg-slate-50 flex items-center gap-2 transition-all shadow-2xs h-[46px]"
                    >
                        <PrinterIcon class="w-5 h-5" />
                        Cetak
                    </button>
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

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 print:grid-cols-3 print:gap-6">
                    <!-- Day Column -->
                    <div v-for="day in days" :key="day" class="flex flex-col gap-3 break-inside-avoid">
                        <!-- Day Header -->
                        <div class="bg-white p-3 rounded-2xl shadow-2xs border border-gray-100 text-center sticky top-0 z-10 print:border-black print:shadow-none">
                            <h3 class="font-bold text-gray-800 text-sm">{{ day }}</h3>
                            <div class="h-1 w-6 bg-[#0B7068] rounded-full mx-auto mt-1 print:bg-black"></div>
                        </div>

                        <!-- Empty State for Day -->
                        <div v-if="sortedSchedules(day).length === 0" class="flex-1 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center min-h-[120px] p-4 text-center group hover:bg-slate-100/60 transition-colors print:hidden">
                            <div class="bg-white rounded-full p-2 mb-2 group-hover:scale-110 transition-transform shadow-2xs">
                                <ClockIcon class="w-4 h-4 text-gray-400" />
                            </div>
                            <p class="text-xs font-bold text-gray-400">Tidak ada jadwal</p>
                            <p class="text-[10px] text-gray-400/80">Santai sejenak ☕</p>
                        </div>

                        <!-- Schedule Cards -->
                        <div 
                            v-for="item in sortedSchedules(day)" 
                            :key="item.id" 
                            class="bg-white p-3.5 rounded-2xl shadow-2xs border border-gray-100 hover:border-slate-300 transition-all relative group print:border-black print:shadow-none space-y-2"
                            :class="{'ring-2 ring-[#0B7068]/20': isTeacher && viewMode === 'personal'}"
                        >
                            <!-- Time -->
                            <div class="flex items-center justify-between gap-1">
                                <div class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-bold rounded-lg border border-emerald-100 print:border-black print:text-black print:bg-transparent">
                                    {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }}
                                </div>
                                <!-- Show Class Name if in Personal Mode -->
                                <div v-if="viewMode === 'personal'" class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-bold rounded-lg border border-amber-200/60">
                                    {{ item.classroom?.name }}
                                </div>
                            </div>

                            <!-- Subject -->
                            <h4 class="font-bold text-gray-900 text-xs leading-snug">{{ item.subject?.name }}</h4>
                            
                            <!-- Teacher (Only show if NOT in personal mode) -->
                            <div v-if="viewMode !== 'personal'" class="flex items-center gap-2 pt-1 border-t border-gray-100 print:border-gray-200">
                                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 print:hidden">
                                    {{ item.teacher?.full_name?.charAt(0) || '?' }}
                                </div>
                                <p class="text-xs text-gray-500 truncate">{{ item.teacher?.full_name }}</p>
                            </div>

                            <!-- Actions (For Admin / Yayasan / Koordinator Kurikulum) -->
                            <div v-if="canManage" class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity print:hidden">
                                <button 
                                    @click="openAddModal(item)"
                                    class="p-1 text-gray-400 hover:text-[#0B7068] bg-white rounded-md shadow-2xs"
                                    title="Edit Jadwal"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                </button>
                                <button 
                                    @click="deleteSchedule(item.id)"
                                    class="p-1 text-gray-400 hover:text-red-500 bg-white rounded-md shadow-2xs"
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

    </component>
</template>
