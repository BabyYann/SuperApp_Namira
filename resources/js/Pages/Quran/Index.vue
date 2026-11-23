<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import QuranDialog from './Components/QuranDialog.vue';
import QuranConfirmDialog from './Components/QuranConfirmDialog.vue';
import QuranCollapse from './Components/QuranCollapse.vue';
import {
    PlusIcon,
    ClockIcon,
    PencilSquareIcon,
    TrashIcon,
    UserPlusIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    SparklesIcon,
    ChevronDownIcon,
    AcademicCapIcon,
    MapPinIcon,
    UserIcon,
    AdjustmentsHorizontalIcon,
    EllipsisHorizontalIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    canManage: Boolean,
    allowSelfPlotting: {
        type: Boolean,
        default: true,
    },
    isGlobalAdmin: Boolean,
    levels: Array,
    sessions: Array,
    groups: Array,
    teachers: Array,
    classrooms: Array,
    allUnitStudents: Array,
    levelDistribution: Array,
    stats: Object,
    filters: Object,
});

// ==================== FILTERS (DEBOUNCED REAL-TIME) ====================
const sessionFilter = ref(props.filters?.session_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');
const isFiltering = ref(false);
const showMobileFilterSheet = ref(false);

const activeFilterCount = computed(() => {
    let n = 0;
    if (sessionFilter.value !== 'all') n++;
    if (levelFilter.value !== 'all') n++;
    return n;
});

const hasActiveFilter = computed(() => {
    return activeFilterCount.value > 0 || searchQuery.value.trim() !== '';
});

const activeSessionLabel = computed(() => {
    if (sessionFilter.value === 'all') return null;
    if (sessionFilter.value === 'none') return 'Tanpa Sesi';
    return props.sessions?.find(s => String(s.id) === String(sessionFilter.value))?.name || null;
});

const activeLevelLabel = computed(() => {
    if (levelFilter.value === 'all') return null;
    return props.levels?.find(l => String(l.id) === String(levelFilter.value))?.name || null;
});

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('quran.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        session_id: sessionFilter.value,
        level_id: levelFilter.value,
        search: searchQuery.value.trim() || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { isFiltering.value = false; },
    });
};

const debouncedSearch = useDebounceFn(applyFilters, 300);
watch(searchQuery, () => debouncedSearch());

const selectLevelFilter = (id) => {
    levelFilter.value = String(levelFilter.value) === String(id) ? 'all' : id;
    applyFilters();
};

const selectSessionFilter = (id) => {
    sessionFilter.value = String(sessionFilter.value) === String(id) ? 'all' : id;
    applyFilters();
};

const resetFilters = () => {
    sessionFilter.value = 'all';
    levelFilter.value = 'all';
    searchQuery.value = '';
    applyFilters();
};

// Metrics helpers
const plottedPercent = computed(() => {
    if (!props.stats?.total_students) return 0;
    return Math.min(100, Math.round((props.stats.plotted_students / props.stats.total_students) * 100));
});

const assessedPercent = computed(() => {
    if (!props.stats?.plotted_students) return 0;
    return Math.min(100, Math.round((props.stats.assessed_students / props.stats.plotted_students) * 100));
});

// Expand members per group card
const expandedGroupIds = ref(new Set());
const toggleGroupExpand = (id) => {
    const next = new Set(expandedGroupIds.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    expandedGroupIds.value = next;
};

// Mobile Group Action Sheet (safe thumb overflow menu)
const actionSheetGroup = ref(null);

// ==================== CONFIRM DIALOG ====================
const confirmState = ref({
    show: false,
    title: '',
    message: '',
    confirmLabel: 'Hapus',
    processing: false,
    onConfirm: null,
});

const openConfirm = ({ title, message, confirmLabel = 'Hapus', onConfirm }) => {
    confirmState.value = { show: true, title, message, confirmLabel, processing: false, onConfirm };
};

const runConfirm = () => {
    if (!confirmState.value.onConfirm) return;
    confirmState.value.processing = true;
    confirmState.value.onConfirm(() => {
        confirmState.value.processing = false;
        confirmState.value.show = false;
    });
};

// ==================== SESSIONS DRAWER & MODAL ====================
const showSessionsDrawer = ref(false);
const showSessionModal = ref(false);
const editingSession = ref(null);
const sessionForm = useForm({
    unit_id: props.unit.id,
    name: '',
    start_time: '07:30',
    end_time: '08:30',
    days: 'Senin - Jumat',
    target_note: '',
    sort_order: 1,
});

const openCreateSession = () => {
    editingSession.value = null;
    sessionForm.reset();
    sessionForm.unit_id = props.unit.id;
    sessionForm.days = 'Senin - Jumat';
    sessionForm.sort_order = (props.sessions?.length || 0) + 1;
    showSessionModal.value = true;
};

const openEditSession = (sess) => {
    editingSession.value = sess;
    sessionForm.unit_id = props.unit.id;
    sessionForm.name = sess.name;
    sessionForm.start_time = sess.start_time || '';
    sessionForm.end_time = sess.end_time || '';
    sessionForm.days = sess.days || '';
    sessionForm.target_note = sess.target_note || '';
    sessionForm.sort_order = sess.sort_order || 1;
    showSessionModal.value = true;
};

const submitSession = () => {
    if (editingSession.value) {
        sessionForm.put(route('quran.sessions.update', editingSession.value.id), {
            preserveScroll: true,
            onSuccess: () => { showSessionModal.value = false; },
        });
    } else {
        sessionForm.post(route('quran.sessions.store'), {
            preserveScroll: true,
            onSuccess: () => { showSessionModal.value = false; },
        });
    }
};

const askDeleteSession = (sess) => {
    openConfirm({
        title: `Hapus "${sess.name}"?`,
        message: 'Kelompok yang memakai sesi ini tetap aman dan tidak akan terhapus.',
        confirmLabel: 'Hapus Sesi',
        onConfirm: (done) => {
            router.delete(route('quran.sessions.destroy', sess.id), {
                data: { unit_id: props.unit.id },
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
};

const seedingSessions = ref(false);
const seedSessions = () => {
    seedingSessions.value = true;
    router.post(route('quran.sessions.seed'), { unit_id: props.unit.id }, {
        preserveScroll: true,
        onFinish: () => { seedingSessions.value = false; },
    });
};

// ==================== GROUP MODAL ====================
const showGroupModal = ref(false);
const editingGroup = ref(null);
const groupForm = useForm({
    unit_id: props.unit.id,
    academic_year_id: props.activeYear.id,
    level_id: '',
    session_id: '',
    name: '',
    teacher_id: '',
    assistant_teacher_id: '',
    room_name: '',
    max_quota: 20,
});

const openCreateGroup = () => {
    editingGroup.value = null;
    groupForm.reset();
    groupForm.unit_id = props.unit.id;
    groupForm.academic_year_id = props.activeYear.id;
    groupForm.level_id = props.levels?.[0]?.id || '';
    groupForm.session_id = props.sessions?.[0]?.id || '';
    groupForm.max_quota = 20;
    showGroupModal.value = true;
};

const openEditGroup = (group) => {
    editingGroup.value = group;
    groupForm.unit_id = props.unit.id;
    groupForm.academic_year_id = props.activeYear.id;
    groupForm.level_id = group.level_id;
    groupForm.session_id = group.session_id || '';
    groupForm.name = group.name;
    groupForm.teacher_id = group.teacher_id || '';
    groupForm.assistant_teacher_id = group.assistant_teacher_id || '';
    groupForm.room_name = group.room_name || '';
    groupForm.max_quota = group.max_quota || '';
    showGroupModal.value = true;
};

const autoSuggestGroupName = () => {
    if (editingGroup.value || groupForm.name) return;
    const lvl = props.levels.find(l => String(l.id) === String(groupForm.level_id));
    const t = props.teachers.find(u => String(u.id) === String(groupForm.teacher_id));
    if (lvl && t) {
        const firstName = t.name.split(',')[0].split(' ').slice(0, 2).join(' ');
        groupForm.name = `${lvl.name} - ${firstName}`;
    } else if (lvl) {
        groupForm.name = `${lvl.name} A`;
    }
};

const submitGroup = () => {
    if (editingGroup.value) {
        groupForm.put(route('quran.groups.update', editingGroup.value.id), {
            preserveScroll: true,
            onSuccess: () => { showGroupModal.value = false; },
        });
    } else {
        groupForm.post(route('quran.groups.store'), {
            preserveScroll: true,
            onSuccess: () => { showGroupModal.value = false; },
        });
    }
};

const askDeleteGroup = (group) => {
    openConfirm({
        title: `Hapus "${group.name}"?`,
        message: 'Kelompok beserta daftar plotting siswa di dalamnya akan dihapus dari tahun ajaran ini.',
        confirmLabel: 'Hapus Kelompok',
        onConfirm: (done) => {
            router.delete(route('quran.groups.destroy', group.id), {
                data: { unit_id: props.unit.id },
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
};

// ==================== BULK ENROLLMENT DRAWER ====================
const showEnrollDrawer = ref(false);
const enrollTargetGroup = ref(null);
const enrollSearch = ref('');
const enrollClassroomFilter = ref('all');
const enrollOnlyUnplotted = ref(true);
const enrollSelectedIds = ref([]);

const enrollForm = useForm({
    unit_id: props.unit.id,
    student_ids: [],
    started_at: new Date().toISOString().slice(0, 10),
});

const openEnrollDrawer = (group) => {
    enrollTargetGroup.value = group;
    enrollSearch.value = '';
    enrollClassroomFilter.value = 'all';
    enrollOnlyUnplotted.value = true;
    enrollSelectedIds.value = [];
    enrollForm.started_at = new Date().toISOString().slice(0, 10);
    showEnrollDrawer.value = true;
};

const filteredEnrollCandidates = computed(() => {
    const q = enrollSearch.value.trim().toLowerCase();
    const existingIds = new Set((enrollTargetGroup.value?.members || []).map(m => m.student_id));

    return (props.allUnitStudents || []).filter(st => {
        if (existingIds.has(st.id)) return false;
        if (enrollOnlyUnplotted.value && st.is_plotted) return false;
        if (enrollClassroomFilter.value !== 'all' && String(st.classroom_id) !== String(enrollClassroomFilter.value)) return false;
        if (q && !(st.full_name?.toLowerCase().includes(q) || st.nis?.toLowerCase().includes(q))) return false;
        return true;
    });
});

const selectableEnrollCandidates = computed(() =>
    filteredEnrollCandidates.value.filter(st => !st.locked_by_other)
);

const toggleSelectAllCandidates = (e) => {
    enrollSelectedIds.value = e.target.checked
        ? selectableEnrollCandidates.value.map(s => s.id)
        : [];
};

const submitEnroll = () => {
    if (!enrollTargetGroup.value || enrollSelectedIds.value.length === 0) return;
    enrollForm.unit_id = props.unit.id;
    enrollForm.student_ids = enrollSelectedIds.value;
    enrollForm.post(route('quran.groups.enroll', enrollTargetGroup.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEnrollDrawer.value = false;
            enrollSelectedIds.value = [];
        },
    });
};

const askRemoveMember = (member, groupName) => {
    openConfirm({
        title: `Keluarkan ${member.full_name}?`,
        message: `Siswa akan dikeluarkan dari kelompok "${groupName}". Anda dapat memplot ulang siswa ini kapan saja.`,
        confirmLabel: 'Keluarkan',
        onConfirm: (done) => {
            router.delete(route('quran.enrollments.destroy', member.enrollment_id), {
                data: { unit_id: props.unit.id },
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
};
</script>

<template>
    <Head title="Kelompok & Sesi — Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-1 sm:py-2 pb-28 md:pb-2 space-y-4">
            <QuranNav
                active-tab="index"
                page-title="Kelompok & Sesi Mengaji"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            >
                <!-- Desktop Actions (100% Preserved) -->
                <template #actions>
                    <button
                        type="button"
                        @click="showSessionsDrawer = true"
                        class="h-8 px-3 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 hover:border-slate-300 shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Sesi</span>
                        <span class="px-1.5 py-px rounded-md bg-slate-100 text-[11px] font-semibold text-slate-600 tabular-nums">
                            {{ sessions.length }}
                        </span>
                    </button>

                    <button
                        v-if="canManage"
                        type="button"
                        @click="openCreateGroup"
                        class="h-8 px-3.5 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] shadow-[0_1px_2px_rgba(0,121,107,0.25)] transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Kelompok Baru</span>
                    </button>
                </template>

                <!-- Mobile Native Compact Actions -->
                <template #mobile-actions>
                    <button
                        type="button"
                        @click="showSessionsDrawer = true"
                        class="h-9 px-2.5 rounded-xl text-xs font-medium text-slate-700 bg-white border border-slate-200/90 active:scale-95 transition-all inline-flex items-center gap-1"
                        title="Sesi Waktu"
                    >
                        <ClockIcon class="w-4 h-4 text-slate-500" />
                        <span class="tabular-nums">{{ sessions.length }}</span>
                    </button>
                    <button
                        v-if="canManage"
                        type="button"
                        @click="openCreateGroup"
                        class="h-9 px-3 rounded-xl text-xs font-semibold text-white bg-[#00796B] active:scale-95 transition-all inline-flex items-center gap-1 shadow-xs"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Baru</span>
                    </button>
                </template>
            </QuranNav>

            <!-- ==================== DESKTOP METRICS STRIP (100% PRESERVED) ==================== -->
            <div class="hidden md:grid bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] grid-cols-4 divide-x divide-slate-200/70 overflow-hidden">
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">Siswa Terplot</span>
                        <span class="text-[11px] font-medium tabular-nums" :class="stats.unplotted_students > 0 ? 'text-amber-600' : 'text-[#00796B]'">
                            {{ plottedPercent }}%
                        </span>
                    </div>
                    <div class="mt-1 flex items-baseline gap-1.5 tabular-nums">
                        <span class="text-xl font-semibold text-slate-900 tracking-tight">{{ stats.plotted_students }}</span>
                        <span class="text-xs text-slate-400">/ {{ stats.total_students }}</span>
                    </div>
                    <div class="mt-2 h-1 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-[#00796B] transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                            :style="{ width: `${plottedPercent}%` }"
                        />
                    </div>
                </div>

                <div class="px-4 py-3">
                    <div class="text-xs text-slate-500 font-medium">Kelompok Aktif</div>
                    <div class="mt-1 flex items-baseline gap-1.5 tabular-nums">
                        <span class="text-xl font-semibold text-slate-900 tracking-tight">{{ stats.total_groups }}</span>
                        <span class="text-xs text-slate-400">halaqah</span>
                    </div>
                    <div class="mt-1.5 text-[11px] text-slate-400">
                        {{ sessions.length }} sesi jadwal aktif
                    </div>
                </div>

                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">Sudah Dinilai</span>
                        <span class="text-[11px] font-medium text-[#00796B] tabular-nums">{{ assessedPercent }}%</span>
                    </div>
                    <div class="mt-1 flex items-baseline gap-1.5 tabular-nums">
                        <span class="text-xl font-semibold text-slate-900 tracking-tight">{{ stats.assessed_students }}</span>
                        <span class="text-xs text-slate-400">siswa</span>
                    </div>
                    <div class="mt-1.5 text-[11px] text-slate-400">
                        Sem. {{ activeYear.semester === 'ganjil' ? 'Ganjil' : 'Genap' }} {{ activeYear.name }}
                    </div>
                </div>

                <div class="px-4 py-3">
                    <div class="text-xs text-slate-500 font-medium">Naik Jilid</div>
                    <div class="mt-1 flex items-baseline gap-1.5 tabular-nums">
                        <span class="text-xl font-semibold text-slate-900 tracking-tight">{{ stats.promotions_semester }}</span>
                        <span class="text-xs text-slate-400">kenaikan</span>
                    </div>
                    <div class="mt-1.5 text-[11px] text-slate-400">
                        Tercatat semester ini
                    </div>
                </div>
            </div>

            <!-- ==================== MOBILE COMPACT GLANCEABLE STATS BAR (< md) ==================== -->
            <div class="md:hidden bg-white rounded-xl border border-slate-200/80 px-3 py-2.5 space-y-2">
                <div class="grid grid-cols-4 divide-x divide-slate-100 text-center">
                    <div class="px-1">
                        <div class="text-sm font-semibold text-slate-900 tabular-nums leading-none">{{ stats.plotted_students }}<span class="text-[10px] font-normal text-slate-400">/{{ stats.total_students }}</span></div>
                        <div class="text-[10px] text-slate-500 mt-1">Terplot</div>
                    </div>
                    <div class="px-1">
                        <div class="text-sm font-semibold text-slate-900 tabular-nums leading-none">{{ stats.total_groups }}</div>
                        <div class="text-[10px] text-slate-500 mt-1">Kelompok</div>
                    </div>
                    <div class="px-1">
                        <div class="text-sm font-semibold text-[#00796B] tabular-nums leading-none">{{ stats.assessed_students }}</div>
                        <div class="text-[10px] text-slate-500 mt-1">Dinilai</div>
                    </div>
                    <div class="px-1">
                        <div class="text-sm font-semibold text-slate-900 tabular-nums leading-none">{{ stats.promotions_semester }}</div>
                        <div class="text-[10px] text-slate-500 mt-1">Naik</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-[#00796B] transition-all duration-500" :style="{ width: `${plottedPercent}%` }" />
                    </div>
                    <span class="text-[10px] font-medium text-slate-500 tabular-nums shrink-0">{{ plottedPercent }}% terplot</span>
                </div>
            </div>

            <!-- Quiet inline hint only when sessions are empty -->
            <div
                v-if="canManage && sessions.length === 0"
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-3.5 py-2.5 rounded-xl bg-teal-50/60 border border-teal-200/70 text-xs"
            >
                <div class="flex items-center gap-2 text-teal-900">
                    <SparklesIcon class="w-4 h-4 text-[#00796B] shrink-0" />
                    <span>Belum ada sesi waktu mengaji. Isi 3 sesi default (Pagi, Menengah, Siang)?</span>
                </div>
                <button
                    type="button"
                    :disabled="seedingSessions"
                    @click="seedSessions"
                    class="h-8 md:h-7 px-3 rounded-lg bg-white border border-teal-300/80 text-[#00796B] font-medium hover:bg-teal-50 transition-colors shrink-0 disabled:opacity-50"
                >
                    {{ seedingSessions ? 'Menyiapkan...' : 'Isi 3 Sesi Default' }}
                </button>
            </div>

            <!-- ==================== DESKTOP FILTER BAR + JILID RAIL (100% PRESERVED) ==================== -->
            <div class="hidden md:block space-y-2.5">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 min-w-0">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama kelompok, guru pengampu, atau ruangan..."
                            class="w-full h-9 pl-9 pr-8 text-[13px] bg-white border border-slate-200/90 rounded-lg text-slate-800 placeholder:text-slate-400 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                        />
                        <span
                            v-if="isFiltering"
                            class="w-3.5 h-3.5 border-2 border-slate-200 border-t-[#00796B] rounded-full animate-spin absolute right-3 top-1/2 -translate-y-1/2"
                        />
                        <button
                            v-else-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="p-0.5 text-slate-400 hover:text-slate-600 absolute right-2.5 top-1/2 -translate-y-1/2"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <select
                            v-model="sessionFilter"
                            @change="applyFilters"
                            class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                        >
                            <option value="all">Semua Sesi</option>
                            <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                            <option value="none">Tanpa Sesi</option>
                        </select>

                        <button
                            v-if="hasActiveFilter"
                            type="button"
                            @click="resetFilters"
                            class="h-9 px-2.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-0.5">
                    <button
                        type="button"
                        @click="selectLevelFilter('all')"
                        class="h-7 px-2.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all shrink-0 border"
                        :class="levelFilter === 'all'
                            ? 'bg-slate-900 text-white border-slate-900'
                            : 'bg-white text-slate-600 border-slate-200/90 hover:border-slate-300 hover:text-slate-900'"
                    >
                        Semua Jilid
                    </button>
                    <button
                        v-for="lvl in levelDistribution"
                        :key="lvl.id"
                        type="button"
                        @click="selectLevelFilter(lvl.id)"
                        class="h-7 px-2.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all shrink-0 border inline-flex items-center gap-1.5"
                        :class="String(levelFilter) === String(lvl.id)
                            ? 'bg-[#00796B] text-white border-[#00796B]'
                            : 'bg-white text-slate-600 border-slate-200/90 hover:border-slate-300 hover:text-slate-900'"
                    >
                        <span>{{ lvl.name }}</span>
                        <span
                            class="text-[10px] font-semibold tabular-nums px-1 rounded"
                            :class="String(levelFilter) === String(lvl.id) ? 'bg-white/20 text-white' : 'text-slate-400'"
                        >
                            {{ lvl.count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ==================== MOBILE SEARCH + FILTER SHEET TRIGGER (< md) ==================== -->
            <div class="md:hidden space-y-2">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 min-w-0">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kelompok atau pengampu..."
                            class="w-full h-10 pl-9 pr-8 text-[13px] bg-white border border-slate-200/90 rounded-xl text-slate-800 placeholder:text-slate-400 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                        />
                        <span
                            v-if="isFiltering"
                            class="w-3.5 h-3.5 border-2 border-slate-200 border-t-[#00796B] rounded-full animate-spin absolute right-3 top-1/2 -translate-y-1/2"
                        />
                        <button
                            v-else-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="p-1 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2"
                        >
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="showMobileFilterSheet = true"
                        class="relative h-10 px-3 rounded-xl border text-xs font-medium inline-flex items-center gap-1.5 shrink-0 active:scale-95 transition-all"
                        :class="activeFilterCount > 0 ? 'bg-teal-50 border-[#00796B]/40 text-[#00796B]' : 'bg-white border-slate-200/90 text-slate-700'"
                    >
                        <AdjustmentsHorizontalIcon class="w-4 h-4" />
                        <span>Filter</span>
                        <span v-if="activeFilterCount > 0" class="w-4 h-4 rounded-full bg-[#00796B] text-white text-[10px] font-semibold flex items-center justify-center tabular-nums">
                            {{ activeFilterCount }}
                        </span>
                    </button>
                </div>

                <!-- Active Filter Removable Chips -->
                <div v-if="activeSessionLabel || activeLevelLabel" class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-if="activeLevelLabel"
                        type="button"
                        @click="selectLevelFilter('all')"
                        class="inline-flex items-center gap-1 h-7 px-2.5 rounded-full bg-teal-50 text-[#00796B] border border-teal-200/80 text-[11px] font-medium active:scale-95"
                    >
                        <span>{{ activeLevelLabel }}</span>
                        <XMarkIcon class="w-3 h-3" />
                    </button>
                    <button
                        v-if="activeSessionLabel"
                        type="button"
                        @click="selectSessionFilter('all')"
                        class="inline-flex items-center gap-1 h-7 px-2.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-medium active:scale-95"
                    >
                        <span>{{ activeSessionLabel }}</span>
                        <XMarkIcon class="w-3 h-3" />
                    </button>
                </div>
            </div>

            <!-- ==================== DESKTOP GROUPS GRID (100% PRESERVED) ==================== -->
            <div v-if="groups.length > 0" class="hidden md:grid grid-cols-1 lg:grid-cols-2 gap-3.5">
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] hover:border-slate-300/90 transition-colors overflow-hidden flex flex-col"
                >
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70">
                                        {{ group.level?.name }}
                                    </span>
                                    <span v-if="group.session" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600">
                                        <ClockIcon class="w-3 h-3 text-slate-400" />
                                        {{ group.session.name }}
                                        <span v-if="group.session.start_time" class="text-slate-400 tabular-nums">{{ group.session.start_time }}–{{ group.session.end_time }}</span>
                                    </span>
                                    <span v-if="group.room_name" class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                                        <MapPinIcon class="w-3 h-3 text-slate-400" />
                                        {{ group.room_name }}
                                    </span>
                                </div>

                                <h3 class="text-[15px] font-semibold text-slate-900 mt-2 truncate">
                                    {{ group.name }}
                                </h3>

                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1">
                                        <UserIcon class="w-3.5 h-3.5 text-slate-400" />
                                        <span class="font-medium text-slate-700">{{ group.teacher?.name || '-' }}</span>
                                    </span>
                                    <span v-if="group.assistant_teacher" class="text-slate-400">
                                        Asisten: <span class="text-slate-600">{{ group.assistant_teacher.name }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0 pl-2">
                                <div class="text-lg font-semibold text-slate-900 leading-none tabular-nums">
                                    {{ group.members_count }}
                                    <span class="text-[11px] font-normal text-slate-400">siswa</span>
                                </div>
                                <div
                                    class="mt-1.5 text-[11px] font-medium tabular-nums"
                                    :class="group.assessed_count === group.members_count && group.members_count > 0 ? 'text-[#00796B]' : 'text-slate-500'"
                                >
                                    {{ group.assessed_count }}/{{ group.members_count }} dinilai
                                </div>
                                <div class="mt-1 w-16 h-1 rounded-full bg-slate-100 overflow-hidden ml-auto">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="group.assessed_count === group.members_count && group.members_count > 0 ? 'bg-[#00796B]' : 'bg-amber-400'"
                                        :style="{ width: `${group.members_count ? Math.round((group.assessed_count / group.members_count) * 100) : 0}%` }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 mt-3.5 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="toggleGroupExpand(group.id)"
                                class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                <ChevronDownIcon
                                    class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
                                    :class="expandedGroupIds.has(group.id) ? 'rotate-180 text-[#00796B]' : ''"
                                />
                                <span>{{ expandedGroupIds.has(group.id) ? 'Sembunyikan' : `Anggota (${group.members_count})` }}</span>
                            </button>

                            <div class="flex items-center gap-1.5">
                                <button
                                    v-if="group.can_plot"
                                    type="button"
                                    @click="openEnrollDrawer(group)"
                                    class="h-7 px-2.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors inline-flex items-center gap-1"
                                >
                                    <UserPlusIcon class="w-3.5 h-3.5 text-slate-500" />
                                    <span>Plotting</span>
                                </button>
                                <Link
                                    :href="route('quran.assessments.index', { group_id: group.id, unit_id: isGlobalAdmin ? unit.id : undefined, academic_year_id: activeYear.id })"
                                    class="h-7 px-2.5 rounded-lg text-xs font-medium text-[#00796B] bg-teal-50/80 border border-teal-200/70 hover:bg-teal-100/70 transition-colors inline-flex items-center gap-1"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                    <span>Input Nilai</span>
                                </Link>
                                <button
                                    v-if="canManage"
                                    type="button"
                                    @click="openEditGroup(group)"
                                    class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex items-center justify-center"
                                    title="Edit Kelompok"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    v-if="canManage"
                                    type="button"
                                    @click="askDeleteGroup(group)"
                                    class="h-7 w-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors inline-flex items-center justify-center"
                                    title="Hapus Kelompok"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <QuranCollapse :open="expandedGroupIds.has(group.id)">
                        <div class="bg-slate-50/70 border-t border-slate-100 px-4 py-2.5 max-h-64 overflow-y-auto">
                            <div v-if="group.members.length === 0" class="py-4 text-center text-xs text-slate-400">
                                <template v-if="group.can_plot">
                                    Belum ada siswa di kelompok ini. Klik <strong class="font-medium text-slate-600">Plotting</strong> untuk menambahkan.
                                </template>
                                <template v-else>
                                    Belum ada siswa di kelompok ini.
                                </template>
                            </div>
                            <div v-else class="divide-y divide-slate-200/60">
                                <div
                                    v-for="(m, idx) in group.members"
                                    :key="m.enrollment_id"
                                    class="py-2 flex items-center justify-between gap-2 text-xs"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-5 text-[11px] text-slate-400 tabular-nums text-right shrink-0">{{ idx + 1 }}.</span>
                                        <div class="min-w-0">
                                            <div class="font-medium text-slate-800 truncate">{{ m.full_name }}</div>
                                            <div class="text-[11px] text-slate-400">
                                                Kelas {{ m.classroom_name }}
                                                <span v-if="m.nis" class="tabular-nums"> · {{ m.nis }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span
                                            class="inline-flex items-center gap-1 text-[11px] font-medium"
                                            :class="m.is_assessed ? 'text-[#00796B]' : 'text-slate-400'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="m.is_assessed ? 'bg-[#00796B]' : 'bg-slate-300'" />
                                            {{ m.is_assessed ? 'Dinilai' : 'Belum' }}
                                        </span>
                                        <button
                                            v-if="group.can_plot"
                                            type="button"
                                            @click="askRemoveMember(m, group.name)"
                                            class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors"
                                            title="Keluarkan dari kelompok"
                                        >
                                            <XMarkIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </QuranCollapse>
                </div>
            </div>

            <!-- ==================== MOBILE NATIVE GROUP CARDS (< md) ==================== -->
            <div v-if="groups.length > 0" class="md:hidden space-y-3">
                <div
                    v-for="group in groups"
                    :key="'mg-' + group.id"
                    class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden"
                >
                    <div class="p-4 space-y-3">
                        <!-- Top meta + safe overflow menu button -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70">
                                        {{ group.level?.name }}
                                    </span>
                                    <span v-if="group.session" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600">
                                        <ClockIcon class="w-3 h-3 text-slate-400" />
                                        {{ group.session.name }}
                                    </span>
                                </div>
                                <h3 class="text-[15px] font-semibold text-slate-900 mt-1.5 leading-snug">
                                    {{ group.name }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ group.teacher?.name || '-' }}
                                    <span v-if="group.room_name"> · {{ group.room_name }}</span>
                                </p>
                            </div>

                            <button
                                v-if="canManage"
                                type="button"
                                @click="actionSheetGroup = group"
                                class="w-9 h-9 -mr-1.5 -mt-1 rounded-xl text-slate-400 hover:text-slate-700 active:bg-slate-100 flex items-center justify-center shrink-0 transition-colors"
                                aria-label="Menu Kelompok"
                            >
                                <EllipsisHorizontalIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Progress strip -->
                        <div class="flex items-center gap-2.5">
                            <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="group.assessed_count === group.members_count && group.members_count > 0 ? 'bg-[#00796B]' : 'bg-amber-400'"
                                    :style="{ width: `${group.members_count ? Math.round((group.assessed_count / group.members_count) * 100) : 0}%` }"
                                />
                            </div>
                            <span class="text-[11px] font-medium text-slate-500 tabular-nums shrink-0">
                                {{ group.assessed_count }}/{{ group.members_count }} dinilai
                            </span>
                        </div>

                        <!-- Thumb-friendly 40px primary actions -->
                        <div class="grid grid-cols-2 gap-2 pt-0.5">
                            <button
                                v-if="group.can_plot"
                                type="button"
                                @click="openEnrollDrawer(group)"
                                class="h-10 rounded-xl text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200/90 active:scale-[0.98] transition-all inline-flex items-center justify-center gap-1.5"
                            >
                                <UserPlusIcon class="w-4 h-4 text-slate-500" />
                                <span>Plotting</span>
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="toggleGroupExpand(group.id)"
                                class="h-10 rounded-xl text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200/90 active:scale-[0.98] transition-all inline-flex items-center justify-center gap-1.5"
                            >
                                <span>Anggota ({{ group.members_count }})</span>
                                <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="expandedGroupIds.has(group.id) ? 'rotate-180' : ''" />
                            </button>

                            <Link
                                :href="route('quran.assessments.index', { group_id: group.id, unit_id: isGlobalAdmin ? unit.id : undefined, academic_year_id: activeYear.id })"
                                class="h-10 rounded-xl text-xs font-semibold text-white bg-[#00796B] active:scale-[0.98] transition-all inline-flex items-center justify-center gap-1.5 shadow-xs"
                            >
                                <PencilSquareIcon class="w-4 h-4" />
                                <span>Input Nilai</span>
                            </Link>
                        </div>

                        <!-- Secondary Expand Row when group.can_plot -->
                        <button
                            v-if="group.can_plot"
                            type="button"
                            @click="toggleGroupExpand(group.id)"
                            class="w-full pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-slate-500 active:text-slate-900"
                        >
                            <span>{{ expandedGroupIds.has(group.id) ? 'Sembunyikan daftar siswa' : `Lihat ${group.members_count} siswa terdaftar` }}</span>
                            <ChevronDownIcon
                                class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                :class="expandedGroupIds.has(group.id) ? 'rotate-180 text-[#00796B]' : ''"
                            />
                        </button>
                    </div>

                    <QuranCollapse :open="expandedGroupIds.has(group.id)">
                        <div class="bg-slate-50/80 border-t border-slate-100 px-4 py-2 max-h-64 overflow-y-auto divide-y divide-slate-200/60">
                            <div v-if="group.members.length === 0" class="py-4 text-center text-xs text-slate-400">
                                Belum ada siswa di kelompok ini.
                            </div>
                            <div
                                v-for="(m, idx) in group.members"
                                :key="'mm-' + m.enrollment_id"
                                class="py-2.5 flex items-center justify-between gap-2 text-xs"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-4 text-[11px] text-slate-400 tabular-nums shrink-0">{{ idx + 1 }}.</span>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-800 truncate">{{ m.full_name }}</div>
                                        <div class="text-[11px] text-slate-400">Kelas {{ m.classroom_name }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="w-2 h-2 rounded-full" :class="m.is_assessed ? 'bg-[#00796B]' : 'bg-slate-300'" />
                                    <button
                                        v-if="group.can_plot"
                                        type="button"
                                        @click="askRemoveMember(m, group.name)"
                                        class="w-8 h-8 rounded-lg text-slate-400 active:bg-rose-50 active:text-rose-600 inline-flex items-center justify-center"
                                        aria-label="Keluarkan siswa"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </QuranCollapse>
                </div>
            </div>

            <!-- Calm Empty State -->
            <div v-else class="bg-white rounded-xl border border-slate-200/80 p-10 text-center">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <AcademicCapIcon class="w-5 h-5" />
                </div>
                <h3 class="text-sm font-semibold text-slate-800">
                    {{ hasActiveFilter ? 'Tidak ada kelompok yang cocok' : 'Belum ada kelompok mengaji' }}
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">
                    {{ hasActiveFilter
                        ? 'Coba ubah kata kunci atau hapus filter sesi & jilid yang sedang aktif.'
                        : (canManage ? 'Buat kelompok halaqah berdasarkan jilid, sesi waktu, dan guru pengampu untuk mulai plotting siswa.' : 'Anda belum ditugaskan sebagai pengampu di tahun ajaran ini.') }}
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <button
                        v-if="hasActiveFilter"
                        type="button"
                        @click="resetFilters"
                        class="h-9 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        Reset Filter
                    </button>
                    <button
                        v-else-if="canManage"
                        type="button"
                        @click="openCreateGroup"
                        class="h-9 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] transition-colors inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Buat Kelompok Pertama</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== MOBILE FILTER BOTTOM SHEET ==================== -->
        <QuranDialog
            :show="showMobileFilterSheet"
            variant="sheet"
            title="Filter Kelompok"
            subtitle="Saring daftar kelompok berdasarkan jilid atau sesi."
            @close="showMobileFilterSheet = false"
        >
            <div class="p-4 space-y-5">
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Jilid / Tingkat</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            @click="levelFilter = 'all'"
                            class="h-9 px-3 rounded-xl text-xs font-medium border transition-colors"
                            :class="levelFilter === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200'"
                        >
                            Semua Jilid
                        </button>
                        <button
                            v-for="lvl in levelDistribution"
                            :key="'mf-' + lvl.id"
                            type="button"
                            @click="levelFilter = String(levelFilter) === String(lvl.id) ? 'all' : lvl.id"
                            class="h-9 px-3 rounded-xl text-xs font-medium border transition-colors inline-flex items-center gap-1.5"
                            :class="String(levelFilter) === String(lvl.id) ? 'bg-[#00796B] text-white border-[#00796B]' : 'bg-white text-slate-600 border-slate-200'"
                        >
                            <span>{{ lvl.name }}</span>
                            <span class="text-[10px] opacity-75 tabular-nums">{{ lvl.count }}</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Sesi Waktu</label>
                    <select v-model="sessionFilter" class="w-full h-11 rounded-xl border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Semua Sesi</option>
                        <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}–{{ s.end_time }})</option>
                        <option value="none">Tanpa Sesi</option>
                    </select>
                </div>
            </div>

            <template #footer>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="() => { resetFilters(); showMobileFilterSheet = false; }"
                        class="h-10 rounded-xl text-xs font-medium text-slate-700 bg-white border border-slate-200"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="() => { applyFilters(); showMobileFilterSheet = false; }"
                        class="h-10 rounded-xl text-xs font-semibold text-white bg-[#00796B]"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </template>
        </QuranDialog>

        <!-- ==================== MOBILE GROUP ACTION SHEET (SAFE OVERFLOW) ==================== -->
        <QuranDialog
            :show="!!actionSheetGroup"
            variant="sheet"
            :title="actionSheetGroup?.name || ''"
            :subtitle="`${actionSheetGroup?.level?.name || ''} · ${actionSheetGroup?.teacher?.name || '-'}`"
            @close="actionSheetGroup = null"
        >
            <div v-if="actionSheetGroup" class="p-3 space-y-1">
                <button
                    type="button"
                    @click="() => { const g = actionSheetGroup; actionSheetGroup = null; openEnrollDrawer(g); }"
                    class="w-full h-11 px-3.5 rounded-xl flex items-center gap-3 text-sm font-medium text-slate-800 active:bg-slate-100 transition-colors"
                >
                    <UserPlusIcon class="w-5 h-5 text-[#00796B]" />
                    <span>Plotting Siswa ke Kelompok</span>
                </button>
                <button
                    type="button"
                    @click="() => { const g = actionSheetGroup; actionSheetGroup = null; openEditGroup(g); }"
                    class="w-full h-11 px-3.5 rounded-xl flex items-center gap-3 text-sm font-medium text-slate-800 active:bg-slate-100 transition-colors"
                >
                    <PencilSquareIcon class="w-5 h-5 text-slate-500" />
                    <span>Edit Info Kelompok</span>
                </button>
                <div class="border-t border-slate-100 my-1" />
                <button
                    type="button"
                    @click="() => { const g = actionSheetGroup; actionSheetGroup = null; askDeleteGroup(g); }"
                    class="w-full h-11 px-3.5 rounded-xl flex items-center gap-3 text-sm font-medium text-rose-600 active:bg-rose-50 transition-colors"
                >
                    <TrashIcon class="w-5 h-5" />
                    <span>Hapus Kelompok</span>
                </button>
            </div>
        </QuranDialog>

        <!-- ==================== DRAWER: KELOLA SESI WAKTU ==================== -->
        <QuranDialog
            :show="showSessionsDrawer"
            variant="drawer"
            max-width="md"
            title="Sesi Waktu Mengaji"
            subtitle="Bagi sesi agar 1 guru dapat mengampu lintas kelas/jam."
            @close="showSessionsDrawer = false"
        >
            <div class="p-5 space-y-3">
                <div v-if="sessions.length === 0" class="rounded-xl border border-dashed border-slate-200 p-6 text-center">
                    <ClockIcon class="w-7 h-7 text-slate-300 mx-auto mb-2" />
                    <p class="text-xs text-slate-500">Belum ada sesi waktu mengaji.</p>
                    <button
                        v-if="canManage"
                        type="button"
                        :disabled="seedingSessions"
                        @click="seedSessions"
                        class="mt-3 h-9 md:h-8 px-3 rounded-xl md:rounded-lg text-xs font-medium text-[#00796B] bg-teal-50 border border-teal-200/80 hover:bg-teal-100/70 transition-colors inline-flex items-center gap-1.5"
                    >
                        <SparklesIcon class="w-3.5 h-3.5" />
                        <span>Isi 3 Sesi Default</span>
                    </button>
                </div>

                <div
                    v-for="sess in sessions"
                    :key="sess.id"
                    class="p-3.5 rounded-xl border transition-colors flex items-start justify-between gap-2.5"
                    :class="String(sessionFilter) === String(sess.id) ? 'bg-teal-50/50 border-teal-300' : 'bg-white border-slate-200/80 hover:border-slate-300'"
                >
                    <button
                        type="button"
                        @click="() => { selectSessionFilter(sess.id); showSessionsDrawer = false; }"
                        class="text-left flex-1 min-w-0"
                    >
                        <div class="text-sm font-semibold text-slate-900">{{ sess.name }}</div>
                        <div class="text-xs text-[#00796B] font-medium mt-0.5 tabular-nums">
                            {{ sess.start_time || '--:--' }} – {{ sess.end_time || '--:--' }}
                            <span v-if="sess.days" class="text-slate-400 font-normal"> · {{ sess.days }}</span>
                        </div>
                        <div v-if="sess.target_note" class="text-[11px] text-slate-500 mt-1">
                            Target: {{ sess.target_note }}
                        </div>
                    </button>

                    <div v-if="canManage" class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            @click="openEditSession(sess)"
                            class="w-8 h-8 md:w-7 md:h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex items-center justify-center"
                            title="Edit Sesi"
                        >
                            <PencilSquareIcon class="w-4 h-4 md:w-3.5 md:h-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="askDeleteSession(sess)"
                            class="w-8 h-8 md:w-7 md:h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors inline-flex items-center justify-center"
                            title="Hapus Sesi"
                        >
                            <TrashIcon class="w-4 h-4 md:w-3.5 md:h-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <template v-if="canManage" #footer>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">Ketuk sesi untuk memfilter.</span>
                    <button
                        type="button"
                        @click="openCreateSession"
                        class="h-10 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] transition-colors inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Tambah Sesi</span>
                    </button>
                </div>
            </template>
        </QuranDialog>

        <!-- ==================== MODAL / SHEET: TAMBAH / EDIT SESI ==================== -->
        <QuranDialog
            :show="showSessionModal"
            variant="modal"
            max-width="md"
            :title="editingSession ? 'Edit Sesi Waktu' : 'Tambah Sesi Waktu'"
            @close="showSessionModal = false"
        >
            <form id="quran-session-form" @submit.prevent="submitSession" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Sesi <span class="text-rose-500">*</span></label>
                    <input v-model="sessionForm.name" required type="text" placeholder="Contoh: Sesi 1 (Pagi)" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Jam Mulai</label>
                        <input v-model="sessionForm.start_time" type="time" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Jam Selesai</label>
                        <input v-model="sessionForm.end_time" type="time" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Hari Pelaksanaan</label>
                    <input v-model="sessionForm.days" type="text" placeholder="Senin - Jumat" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Keterangan Target Kelas</label>
                    <input v-model="sessionForm.target_note" type="text" placeholder="Contoh: Kelas 1 & 2" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showSessionModal = false" class="h-10 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" form="quran-session-form" :disabled="sessionForm.processing" class="h-10 md:h-8 px-4 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-50 transition-colors">
                    {{ sessionForm.processing ? 'Menyimpan...' : 'Simpan Sesi' }}
                </button>
            </template>
        </QuranDialog>

        <!-- ==================== MODAL / SHEET: TAMBAH / EDIT KELOMPOK ==================== -->
        <QuranDialog
            :show="showGroupModal"
            variant="modal"
            max-width="lg"
            :title="editingGroup ? 'Edit Kelompok Mengaji' : 'Kelompok / Halaqah Baru'"
            subtitle="Tentukan jilid, sesi waktu, dan guru pengampu."
            @close="showGroupModal = false"
        >
            <form id="quran-group-form" @submit.prevent="submitGroup" class="space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Jilid / Tingkat <span class="text-rose-500">*</span></label>
                        <select v-model="groupForm.level_id" @change="autoSuggestGroupName" required class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                            <option value="">Pilih Jilid</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Sesi Waktu</label>
                        <select v-model="groupForm.session_id" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                            <option value="">Tanpa Sesi Khusus</option>
                            <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}–{{ s.end_time }})</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Guru Pengampu Utama <span class="text-rose-500">*</span></label>
                    <select v-model="groupForm.teacher_id" @change="autoSuggestGroupName" required class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="">Pilih Guru Pengampu</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">
                            {{ t.name }} — {{ t.role_label }}
                        </option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Guru Al-Qur'an &amp; Koordinator ditampilkan paling atas.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kelompok <span class="text-rose-500">*</span></label>
                    <input v-model="groupForm.name" required type="text" placeholder="Contoh: Jilid 5 A - Ustz. Nur Halimah" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Guru Asisten (Opsional)</label>
                    <select v-model="groupForm.assistant_teacher_id" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="">Tidak Ada Asisten</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">
                            {{ t.name }} — {{ t.role_label }}
                        </option>
                    </select>
                    <p v-if="groupForm.errors.assistant_teacher_id" class="text-xs text-rose-600 mt-1">{{ groupForm.errors.assistant_teacher_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Ruangan / Lokasi</label>
                        <input v-model="groupForm.room_name" type="text" placeholder="Masjid / R. 3A" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Kuota Maks.</label>
                        <input v-model="groupForm.max_quota" type="number" min="1" max="200" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm tabular-nums focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showGroupModal = false" class="h-10 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" form="quran-group-form" :disabled="groupForm.processing" class="h-10 md:h-8 px-4 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-50 transition-colors">
                    {{ groupForm.processing ? 'Menyimpan...' : (editingGroup ? 'Simpan Perubahan' : 'Buat Kelompok') }}
                </button>
            </template>
        </QuranDialog>

        <!-- ==================== DRAWER: BULK PLOTTING SISWA ==================== -->
        <QuranDialog
            :show="showEnrollDrawer && !!enrollTargetGroup"
            variant="drawer"
            max-width="xl"
            :title="`Plotting Siswa — ${enrollTargetGroup?.name || ''}`"
            :subtitle="`${enrollTargetGroup?.level?.name || ''} · Pilih siswa lintas kelas formal.`"
            @close="showEnrollDrawer = false"
        >
            <div class="flex flex-col h-full">
                <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-xs border-b border-slate-200/80 px-4 sm:px-5 py-3 space-y-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative flex-1 min-w-[160px]">
                            <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="enrollSearch"
                                type="text"
                                placeholder="Cari nama atau NIS..."
                                class="w-full h-9 md:h-8 pl-8 pr-3 text-xs bg-white border border-slate-200 rounded-xl md:rounded-lg focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                            />
                        </div>
                        <select v-model="enrollClassroomFilter" class="h-9 md:h-8 px-2.5 text-xs bg-white border border-slate-200 rounded-xl md:rounded-lg text-slate-700 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                            <option value="all">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                        </select>
                        <label class="inline-flex items-center gap-1.5 h-9 md:h-8 px-2.5 rounded-xl md:rounded-lg bg-white border border-slate-200 text-xs text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" v-model="enrollOnlyUnplotted" class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30" />
                            <span>Belum diplot</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="inline-flex items-center gap-2 text-slate-600 font-medium cursor-pointer select-none">
                            <input
                                type="checkbox"
                                @change="toggleSelectAllCandidates"
                                :disabled="selectableEnrollCandidates.length === 0"
                                :checked="selectableEnrollCandidates.length > 0 && enrollSelectedIds.length === selectableEnrollCandidates.length"
                                class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30 disabled:opacity-40"
                            />
                            <span>Pilih semua ({{ selectableEnrollCandidates.length }})</span>
                        </label>
                        <span class="font-semibold text-[#00796B] tabular-nums">{{ enrollSelectedIds.length }} dipilih</span>
                    </div>

                    <div
                        v-if="!canManage && !enrollOnlyUnplotted"
                        class="flex items-center gap-1.5 text-[11px] text-slate-500 bg-white border border-slate-200/80 rounded-lg px-2.5 py-1.5"
                    >
                        <LockClosedIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span>Siswa yang sudah berada di kelompok pengampu lain hanya tampil sebagai info dan tidak dapat ditarik langsung.</span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 px-3 py-1">
                    <div v-if="filteredEnrollCandidates.length === 0" class="py-12 px-4 text-center space-y-2">
                        <p class="text-xs text-slate-400">Tidak ada kandidat siswa sesuai filter.</p>
                        <button
                            v-if="enrollOnlyUnplotted && enrollSearch.trim()"
                            type="button"
                            @click="enrollOnlyUnplotted = false"
                            class="inline-flex items-center h-7 px-3 rounded-lg text-[11px] font-medium text-[#00796B] bg-teal-50 border border-teal-200/80 hover:bg-teal-100/70 transition-colors"
                        >
                            Cek juga siswa yang sudah diplot
                        </button>
                    </div>
                    <label
                        v-for="st in filteredEnrollCandidates"
                        :key="st.id"
                        class="flex items-center justify-between gap-3 px-2.5 py-3 md:py-2.5 rounded-lg transition-colors"
                        :class="st.locked_by_other
                            ? 'opacity-60 bg-slate-50/70 cursor-not-allowed'
                            : 'hover:bg-slate-50 active:bg-slate-100 cursor-pointer'"
                        :title="st.locked_by_other ? `Sudah terdaftar di ${st.current_group_name || 'kelompok pengampu lain'}` : ''"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <input
                                type="checkbox"
                                :value="st.id"
                                v-model="enrollSelectedIds"
                                :disabled="st.locked_by_other"
                                class="w-4 h-4 rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30 disabled:opacity-40 disabled:cursor-not-allowed"
                            />
                            <div class="min-w-0">
                                <div class="text-[13px] font-medium text-slate-800 truncate">{{ st.full_name }}</div>
                                <div class="text-[11px] text-slate-400">
                                    Kelas {{ st.classroom_name }}
                                    <span v-if="st.nis" class="tabular-nums"> · {{ st.nis }}</span>
                                </div>
                            </div>
                        </div>
                        <span
                            v-if="!st.is_plotted"
                            class="px-2 py-0.5 rounded-md text-[10px] font-medium shrink-0 border bg-slate-50 text-slate-500 border-slate-200/80"
                        >
                            Belum diplot
                        </span>
                        <span
                            v-else-if="st.locked_by_other"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium shrink-0 max-w-[175px] truncate border bg-slate-100 text-slate-600 border-slate-200"
                        >
                            <LockClosedIcon class="w-3 h-3 text-slate-400 shrink-0" />
                            <span class="truncate">{{ st.current_group_name || 'Kelompok lain' }}</span>
                        </span>
                        <span
                            v-else-if="st.is_in_my_group"
                            class="px-2 py-0.5 rounded-md text-[10px] font-medium shrink-0 max-w-[175px] truncate border bg-teal-50 text-[#00796B] border-teal-200/80"
                        >
                            Pindah · {{ st.current_group_name }}
                        </span>
                        <span
                            v-else
                            class="px-2 py-0.5 rounded-md text-[10px] font-medium shrink-0 max-w-[175px] truncate border bg-amber-50 text-amber-700 border-amber-200/80"
                        >
                            Pindah · {{ st.current_group_name || 'Akan dipindah' }}
                        </span>
                    </label>
                </div>
            </div>

            <template #footer>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-xs">
                        <label class="text-slate-500 font-medium shrink-0">Tgl Mulai:</label>
                        <input v-model="enrollForm.started_at" type="date" class="h-9 md:h-8 rounded-lg border-slate-200 text-xs tabular-nums focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="showEnrollDrawer = false" class="h-10 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitEnroll"
                            :disabled="enrollSelectedIds.length === 0 || enrollForm.processing"
                            class="h-10 md:h-8 px-4 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-40 transition-colors"
                        >
                            {{ enrollForm.processing ? 'Memproses...' : `Masukkan ${enrollSelectedIds.length} Siswa` }}
                        </button>
                    </div>
                </div>
            </template>
        </QuranDialog>

        <!-- ==================== IN-APP CONFIRM DIALOG ==================== -->
        <QuranConfirmDialog
            :show="confirmState.show"
            :title="confirmState.title"
            :message="confirmState.message"
            :confirm-label="confirmState.confirmLabel"
            :processing="confirmState.processing"
            @close="confirmState.show = false"
            @confirm="runConfirm"
        />
    </AuthenticatedLayout>
</template>
