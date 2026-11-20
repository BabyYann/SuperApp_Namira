<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    PlusIcon,
    ClockIcon,
    UserGroupIcon,
    PencilSquareIcon,
    TrashIcon,
    UserPlusIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    SparklesIcon,
    CheckCircleIcon,
    ArrowUpCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    canManage: Boolean,
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

// Filters
const sessionFilter = ref(props.filters?.session_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');

const applyFilters = () => {
    router.get(route('quran.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        session_id: sessionFilter.value,
        level_id: levelFilter.value,
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const selectLevelFilter = (id) => {
    levelFilter.value = String(levelFilter.value) === String(id) ? 'all' : id;
    applyFilters();
};

// Expand members per group card
const expandedGroupIds = ref(new Set());
const toggleGroupExpand = (id) => {
    if (expandedGroupIds.value.has(id)) expandedGroupIds.value.delete(id);
    else expandedGroupIds.value.add(id);
};

// ==================== SESSION MODAL ====================
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

const deleteSession = (sess) => {
    if (!confirm(`Hapus sesi "${sess.name}"? Kelompok pada sesi ini tidak akan terhapus.`)) return;
    router.delete(route('quran.sessions.destroy', sess.id), {
        data: { unit_id: props.unit.id },
        preserveScroll: true,
    });
};

const seedSessions = () => {
    router.post(route('quran.sessions.seed'), { unit_id: props.unit.id }, { preserveScroll: true });
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

const deleteGroup = (group) => {
    if (!confirm(`Hapus kelompok "${group.name}"?`)) return;
    router.delete(route('quran.groups.destroy', group.id), {
        data: { unit_id: props.unit.id },
        preserveScroll: true,
    });
};

// ==================== QUICK BULK ENROLL MODAL ====================
const showEnrollModal = ref(false);
const enrollTargetGroup = ref(null);
const enrollClassroomFilter = ref('all');
const enrollOnlyUnplotted = ref(true);
const enrollSearch = ref('');
const enrollSelectedIds = ref([]);

const enrollForm = useForm({
    unit_id: props.unit.id,
    student_ids: [],
    started_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

const openEnrollModal = (group) => {
    enrollTargetGroup.value = group;
    enrollClassroomFilter.value = 'all';
    enrollOnlyUnplotted.value = true;
    enrollSearch.value = '';
    enrollSelectedIds.value = [];
    enrollForm.started_at = new Date().toISOString().slice(0, 10);
    enrollForm.notes = '';
    showEnrollModal.value = true;
};

const existingMemberIdsInTarget = computed(() => {
    if (!enrollTargetGroup.value) return new Set();
    return new Set((enrollTargetGroup.value.members || []).map(m => m.student_id));
});

const filteredEnrollCandidates = computed(() => {
    const q = enrollSearch.value.trim().toLowerCase();
    return (props.allUnitStudents || []).filter(st => {
        if (existingMemberIdsInTarget.value.has(st.id)) return false;
        if (enrollOnlyUnplotted.value && st.is_plotted) return false;
        if (enrollClassroomFilter.value !== 'all' && String(st.classroom_id) !== String(enrollClassroomFilter.value)) return false;
        if (q && !st.full_name.toLowerCase().includes(q) && !(st.nis || '').toLowerCase().includes(q)) return false;
        return true;
    });
});

const toggleSelectAllCandidates = (e) => {
    if (e.target.checked) {
        enrollSelectedIds.value = filteredEnrollCandidates.value.map(s => s.id);
    } else {
        enrollSelectedIds.value = [];
    }
};

const submitEnroll = () => {
    if (!enrollTargetGroup.value || enrollSelectedIds.value.length === 0) return;
    enrollForm.unit_id = props.unit.id;
    enrollForm.student_ids = enrollSelectedIds.value;
    enrollForm.post(route('quran.groups.enroll', enrollTargetGroup.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEnrollModal.value = false;
            enrollSelectedIds.value = [];
        },
    });
};

const removeMember = (member, groupName) => {
    if (!confirm(`Keluarkan "${member.full_name}" dari kelompok ${groupName}?`)) return;
    router.delete(route('quran.enrollments.destroy', member.enrollment_id), {
        data: { unit_id: props.unit.id },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Program Al-Qur'an (Ummi) - Kelompok & Sesi" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <QuranNav
                active-tab="index"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Siswa Terplot</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ stats.plotted_students }}</span>
                        <span class="text-xs font-semibold text-slate-400">/ {{ stats.total_students }} siswa</span>
                    </div>
                    <div class="mt-1 text-[11px] font-semibold" :class="stats.unplotted_students > 0 ? 'text-amber-600' : 'text-emerald-600'">
                        {{ stats.unplotted_students > 0 ? `${stats.unplotted_students} belum masuk kelompok` : 'Semua siswa sudah terplot' }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kelompok Aktif</div>
                    <div class="mt-1 text-2xl font-black text-emerald-700">{{ stats.total_groups }}</div>
                    <div class="mt-1 text-[11px] text-slate-500 font-medium">{{ sessions.length }} sesi jadwal tersedia</div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sudah Dinilai</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-teal-700">{{ stats.assessed_students }}</span>
                        <span class="text-xs text-slate-400 font-semibold">siswa</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 font-medium">
                        Semester {{ activeYear.semester === 'ganjil' ? 'Ganjil' : 'Genap' }} {{ activeYear.name }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Naik Jilid Sem. Ini</div>
                    <div class="mt-1 text-2xl font-black text-amber-600">{{ stats.promotions_semester }}</div>
                    <div class="mt-1 text-[11px] text-slate-500 font-medium">Tercatat di log riwayat</div>
                </div>
            </div>

            <!-- Distribusi Jilid Quick Filter Pills -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Sebaran Siswa per Jilid (Klik untuk filter)</span>
                    <button v-if="levelFilter !== 'all'" @click="selectLevelFilter('all')" class="text-xs font-bold text-emerald-700 hover:underline">
                        Tampilkan Semua
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="lvl in levelDistribution"
                        :key="lvl.id"
                        @click="selectLevelFilter(lvl.id)"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all"
                        :class="String(levelFilter) === String(lvl.id)
                            ? 'bg-[#1b4332] text-white border-[#1b4332] shadow-sm'
                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-emerald-50 hover:border-emerald-200'"
                    >
                        <span>{{ lvl.name }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black"
                              :class="String(levelFilter) === String(lvl.id) ? 'bg-amber-400 text-[#1b4332]' : 'bg-white text-slate-600 border border-slate-200'">
                            {{ lvl.count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Sesi Waktu Mengaji -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-amber-50 text-amber-600">
                            <ClockIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-800">Sesi Waktu Mengaji</h2>
                            <p class="text-xs text-slate-500">Atur pembagian sesi (mis. Pagi Kls 1–2, Menengah Kls 3–4, Siang Kls 5–6) agar 1 guru dapat mengampu lintas sesi.</p>
                        </div>
                    </div>
                    <div v-if="canManage" class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="sessions.length === 0"
                            @click="seedSessions"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition flex items-center gap-1.5"
                        >
                            <SparklesIcon class="w-4 h-4" />
                            <span>Isi 3 Sesi Default</span>
                        </button>
                        <button
                            @click="openCreateSession"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 text-white hover:bg-slate-900 transition flex items-center gap-1.5"
                        >
                            <PlusIcon class="w-4 h-4" />
                            <span>Tambah Sesi</span>
                        </button>
                    </div>
                </div>

                <div v-if="sessions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                        v-for="sess in sessions"
                        :key="sess.id"
                        class="p-3.5 rounded-xl border transition-all flex items-start justify-between gap-2"
                        :class="String(sessionFilter) === String(sess.id) ? 'bg-emerald-50/70 border-emerald-300' : 'bg-slate-50/70 border-slate-200/80'"
                    >
                        <div class="cursor-pointer flex-1" @click="() => { sessionFilter = String(sessionFilter) === String(sess.id) ? 'all' : sess.id; applyFilters(); }">
                            <div class="font-extrabold text-sm text-slate-800">{{ sess.name }}</div>
                            <div class="text-xs font-semibold text-emerald-700 mt-0.5">
                                {{ sess.start_time || '--:--' }} – {{ sess.end_time || '--:--' }}
                                <span v-if="sess.days" class="text-slate-400 font-normal"> • {{ sess.days }}</span>
                            </div>
                            <div v-if="sess.target_note" class="text-[11px] text-slate-500 mt-1">Target: {{ sess.target_note }}</div>
                        </div>
                        <div v-if="canManage" class="flex items-center gap-1 shrink-0">
                            <button @click="openEditSession(sess)" class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50" title="Edit Sesi">
                                <PencilSquareIcon class="w-4 h-4" />
                            </button>
                            <button @click="deleteSession(sess)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50" title="Hapus Sesi">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 italic py-2">
                    Belum ada sesi waktu. Anda tetap bisa langsung membuat kelompok tanpa sesi atau klik "Isi 3 Sesi Default".
                </div>
            </div>

            <!-- Toolbar Kelompok -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative flex-1 min-w-[200px]">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari nama kelompok, pengampu, ruang..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>
                    <select v-model="sessionFilter" @change="applyFilters" class="py-2.5 px-3 text-sm bg-white border border-slate-200 rounded-xl font-medium text-slate-700">
                        <option value="all">Semua Sesi</option>
                        <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        <option value="none">Tanpa Sesi</option>
                    </select>
                    <select v-model="levelFilter" @change="applyFilters" class="py-2.5 px-3 text-sm bg-white border border-slate-200 rounded-xl font-medium text-slate-700">
                        <option value="all">Semua Jilid</option>
                        <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>

                <button
                    v-if="canManage"
                    @click="openCreateGroup"
                    class="px-5 py-2.5 rounded-xl bg-[#1b4332] text-white text-sm font-bold hover:bg-[#143325] shadow-md shadow-emerald-950/15 transition flex items-center justify-center gap-2 shrink-0"
                >
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Kelompok / Halaqah</span>
                </button>
            </div>

            <!-- Daftar Kelompok / Halaqah -->
            <div v-if="groups.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden flex flex-col"
                >
                    <div class="p-4 sm:p-5 border-b border-slate-100">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-[#1b4332] text-white">
                                        {{ group.level?.name }}
                                    </span>
                                    <span v-if="group.session" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ group.session.name }} ({{ group.session.start_time }}–{{ group.session.end_time }})
                                    </span>
                                    <span v-if="group.room_name" class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                        {{ group.room_name }}
                                    </span>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-800">{{ group.name }}</h3>
                                <div class="mt-1.5 text-xs text-slate-600 space-y-0.5">
                                    <div>
                                        <span class="text-slate-400">Pengampu Utama:</span>
                                        <strong class="ml-1 text-slate-800">{{ group.teacher?.name || '-' }}</strong>
                                    </div>
                                    <div v-if="group.assistant_teacher">
                                        <span class="text-slate-400">Asisten:</span>
                                        <span class="ml-1 font-semibold text-slate-700">{{ group.assistant_teacher.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-xl font-black text-emerald-700 leading-none">{{ group.members_count }}</div>
                                <div class="text-[10px] font-bold uppercase text-slate-400 mt-0.5">Siswa</div>
                                <div class="mt-1.5 text-[11px] font-bold" :class="group.assessed_count === group.members_count && group.members_count > 0 ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ group.assessed_count }}/{{ group.members_count }} dinilai
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="flex flex-wrap items-center justify-between gap-2 mt-4 pt-3 border-t border-slate-100">
                            <button
                                @click="toggleGroupExpand(group.id)"
                                class="text-xs font-bold text-slate-600 hover:text-emerald-700 flex items-center gap-1"
                            >
                                <component :is="expandedGroupIds.has(group.id) ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                                <span>{{ expandedGroupIds.has(group.id) ? 'Sembunyikan Siswa' : `Lihat ${group.members_count} Siswa` }}</span>
                            </button>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    v-if="canManage"
                                    @click="openEnrollModal(group)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1"
                                >
                                    <UserPlusIcon class="w-3.5 h-3.5" />
                                    <span>Plotting Siswa</span>
                                </button>
                                <Link
                                    :href="route('quran.assessments.index', { group_id: group.id, unit_id: isGlobalAdmin ? unit.id : undefined, academic_year_id: activeYear.id })"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition flex items-center gap-1"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                    <span>Input Nilai</span>
                                </Link>
                                <button v-if="canManage" @click="openEditGroup(group)" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100" title="Edit Kelompok">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                                <button v-if="canManage" @click="deleteGroup(group)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50" title="Hapus Kelompok">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Student List -->
                    <div v-if="expandedGroupIds.has(group.id)" class="bg-slate-50/70 p-3 sm:p-4 max-h-72 overflow-y-auto">
                        <div v-if="group.members.length === 0" class="text-center py-4 text-xs text-slate-400 italic">
                            Belum ada siswa di kelompok ini. Klik "Plotting Siswa" untuk memasukkan siswa.
                        </div>
                        <div v-else class="divide-y divide-slate-200/60">
                            <div
                                v-for="(m, idx) in group.members"
                                :key="m.enrollment_id"
                                class="py-2 flex items-center justify-between gap-2 text-xs"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-5 text-slate-400 font-bold text-right shrink-0">{{ idx + 1 }}.</span>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 truncate">{{ m.full_name }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            Kelas {{ m.classroom_name }}
                                            <span v-if="m.nis"> • {{ m.nis }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span v-if="m.is_assessed" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Dinilai</span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-500">Belum</span>
                                    <button
                                        v-if="canManage"
                                        @click="removeMember(m, group.name)"
                                        class="p-1 text-slate-400 hover:text-red-600 rounded"
                                        title="Keluarkan dari kelompok"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-200">
                <AcademicCapIcon class="w-14 h-14 text-slate-300 mx-auto mb-3" />
                <h3 class="text-base font-extrabold text-slate-700">Belum Ada Kelompok Mengaji</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                    {{ canManage ? 'Buat kelompok / halaqah berdasarkan Jilid, Sesi Waktu, dan Guru Pengampu untuk mulai plotting siswa.' : 'Anda belum ditugaskan sebagai pengampu pada kelompok Al-Qur\'an mana pun di tahun ajaran ini.' }}
                </p>
                <button
                    v-if="canManage"
                    @click="openCreateGroup"
                    class="mt-4 px-5 py-2.5 rounded-xl bg-[#1b4332] text-white text-xs font-bold hover:bg-[#143325] inline-flex items-center gap-1.5"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span>Buat Kelompok Pertama</span>
                </button>
            </div>
        </div>

        <!-- Modal: Tambah / Edit Sesi -->
        <Teleport to="body">
            <div v-if="showSessionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSessionModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-lg font-extrabold text-slate-800">{{ editingSession ? 'Edit Sesi Waktu' : 'Tambah Sesi Waktu' }}</h3>
                        <button @click="showSessionModal = false" class="p-1 text-slate-400 hover:text-slate-600"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitSession" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Sesi *</label>
                            <input v-model="sessionForm.name" required type="text" placeholder="Contoh: Sesi 1 (Pagi)" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai</label>
                                <input v-model="sessionForm.start_time" type="time" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai</label>
                                <input v-model="sessionForm.end_time" type="time" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Hari Pelaksanaan</label>
                            <input v-model="sessionForm.days" type="text" placeholder="Senin - Jumat" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan Target Kelas</label>
                            <input v-model="sessionForm.target_note" type="text" placeholder="Contoh: Kelas 1 & 2" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="showSessionModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="sessionForm.processing" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1b4332] text-white hover:bg-[#143325] disabled:opacity-50">
                                Simpan Sesi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal: Tambah / Edit Kelompok -->
        <Teleport to="body">
            <div v-if="showGroupModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showGroupModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-lg font-extrabold text-slate-800">{{ editingGroup ? 'Edit Kelompok Mengaji' : 'Tambah Kelompok / Halaqah' }}</h3>
                        <button @click="showGroupModal = false" class="p-1 text-slate-400 hover:text-slate-600"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitGroup" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jilid / Tingkat *</label>
                                <select v-model="groupForm.level_id" @change="autoSuggestGroupName" required class="w-full rounded-xl border-slate-200 text-sm">
                                    <option value="">-- Pilih Jilid --</option>
                                    <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Sesi Waktu</label>
                                <select v-model="groupForm.session_id" class="w-full rounded-xl border-slate-200 text-sm">
                                    <option value="">-- Tanpa Sesi Khusus --</option>
                                    <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}–{{ s.end_time }})</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Guru Al-Qur'an (Pengampu Utama) *</label>
                            <select v-model="groupForm.teacher_id" @change="autoSuggestGroupName" required class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">
                                    {{ t.name }} — [{{ t.role_label }}]
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Guru berlabel <strong>Guru Al-Qur'an</strong> &amp; <strong>Koordinator Al-Qur'an</strong> tampil di urutan teratas.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kelompok / Halaqah *</label>
                            <input v-model="groupForm.name" required type="text" placeholder="Contoh: Jilid 5 A - Ustz. Fatimah" class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Guru Asisten / Pendamping (Opsional)</label>
                            <select v-model="groupForm.assistant_teacher_id" class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="">-- Tidak Ada Asisten --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">
                                    {{ t.name }} — [{{ t.role_label }}]
                                </option>
                            </select>
                            <p v-if="groupForm.errors.assistant_teacher_id" class="text-xs text-red-600 mt-1">{{ groupForm.errors.assistant_teacher_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan / Lokasi</label>
                                <input v-model="groupForm.room_name" type="text" placeholder="Misal: Masjid / R. 3A" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Target Kuota (Opsional)</label>
                                <input v-model="groupForm.max_quota" type="number" min="1" max="200" class="w-full rounded-xl border-slate-200 text-sm" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="showGroupModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="groupForm.processing" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1b4332] text-white hover:bg-[#143325] disabled:opacity-50">
                                {{ editingGroup ? 'Simpan Perubahan' : 'Buat Kelompok' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal: Quick Bulk Plotting Siswa -->
        <Teleport to="body">
            <div v-if="showEnrollModal && enrollTargetGroup" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEnrollModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col z-10 overflow-hidden">
                    <div class="px-6 py-4 bg-[#1b4332] text-white flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">Plotting Cepat Siswa</div>
                            <h3 class="text-base font-extrabold">{{ enrollTargetGroup.name }} ({{ enrollTargetGroup.level?.name }})</h3>
                        </div>
                        <button @click="showEnrollModal = false" class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20"><XMarkIcon class="w-5 h-5" /></button>
                    </div>

                    <div class="p-4 border-b border-slate-100 bg-slate-50 space-y-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <input
                                v-model="enrollSearch"
                                type="text"
                                placeholder="Cari nama siswa / NIS..."
                                class="flex-1 min-w-[180px] rounded-xl border-slate-200 text-sm py-2"
                            />
                            <select v-model="enrollClassroomFilter" class="rounded-xl border-slate-200 text-sm py-2">
                                <option value="all">Semua Kelas Formal</option>
                                <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                            </select>
                            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer bg-white px-3 py-2 rounded-xl border border-slate-200">
                                <input type="checkbox" v-model="enrollOnlyUnplotted" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span>Hanya yg belum diplot</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <label class="inline-flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    @change="toggleSelectAllCandidates"
                                    :checked="filteredEnrollCandidates.length > 0 && enrollSelectedIds.length === filteredEnrollCandidates.length"
                                    class="rounded text-emerald-600 focus:ring-emerald-500"
                                >
                                <span>Pilih Semua ({{ filteredEnrollCandidates.length }} siswa tampil)</span>
                            </label>
                            <span class="font-extrabold text-emerald-700">{{ enrollSelectedIds.length }} dipilih</span>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2">
                        <div v-if="filteredEnrollCandidates.length === 0" class="py-10 text-center text-xs text-slate-400 italic">
                            Tidak ada kandidat siswa sesuai filter.
                        </div>
                        <label
                            v-for="st in filteredEnrollCandidates"
                            :key="st.id"
                            class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50/50 cursor-pointer"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <input
                                    type="checkbox"
                                    :value="st.id"
                                    v-model="enrollSelectedIds"
                                    class="rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-800 truncate">{{ st.full_name }}</div>
                                    <div class="text-xs text-slate-500">Kelas {{ st.classroom_name }} <span v-if="st.nis">• {{ st.nis }}</span></div>
                                </div>
                            </div>
                            <span v-if="st.is_plotted" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                                Akan dipindah
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 shrink-0">
                                Belum diplot
                            </span>
                        </label>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-xs">
                            <label class="font-bold text-slate-600">Tgl Mulai:</label>
                            <input v-model="enrollForm.started_at" type="date" class="rounded-lg border-slate-200 text-xs py-1.5" />
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="showEnrollModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button
                                type="button"
                                @click="submitEnroll"
                                :disabled="enrollSelectedIds.length === 0 || enrollForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1b4332] text-white hover:bg-[#143325] disabled:opacity-50"
                            >
                                Masukkan {{ enrollSelectedIds.length }} Siswa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
