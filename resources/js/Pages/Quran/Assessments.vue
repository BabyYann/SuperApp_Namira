<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';

defineOptions({ layout: AuthenticatedLayout });
const isDesktop = useMediaQuery('(min-width: 768px)');
import QuranDialog from './Components/QuranDialog.vue';
import QuranConfirmDialog from './Components/QuranConfirmDialog.vue';
import QuranCollapse from './Components/QuranCollapse.vue';
import {
    CheckIcon,
    InformationCircleIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpDownIcon,
    LockClosedIcon,
    PencilSquareIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    semester: String,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    isLocked: Boolean,
    roleFilter: String,
    usesGradeRubrics: { type: Boolean, default: false },
    groups: Array,
    selectedGroup: Object,
    rubrics: Object,
    levelRubricsByLevel: { type: Object, default: () => ({}) },
    gradeRubricsByGrade: { type: Object, default: () => ({}) },
    students: Array,
});

const grades = ['-', 'A', 'B', 'C', 'D'];
const showLegendMap = ref({});
const isSaving = ref(false);
const localEntries = ref({});
const initialSnapshot = ref({});

const cloneEntries = (students) => {
    const map = {};
    (students || []).forEach(st => {
        map[st.student_id] = {
            grades: { ...(st.grades || {}) },
            teacher_note: st.teacher_note || '',
        };
    });
    return map;
};

const hydrateLocal = () => {
    localEntries.value = cloneEntries(props.students);
    initialSnapshot.value = JSON.parse(JSON.stringify(localEntries.value));
};

watch(() => props.students, hydrateLocal, { immediate: true });

const dirtyStudentIds = computed(() => {
    const dirty = new Set();
    for (const [sid, cur] of Object.entries(localEntries.value)) {
        const orig = initialSnapshot.value[sid];
        if (!orig) continue;
        if (cur.teacher_note !== orig.teacher_note || JSON.stringify(cur.grades) !== JSON.stringify(orig.grades)) {
            dirty.add(Number(sid));
        }
    }
    return dirty;
});

const isDirty = computed(() => dirtyStudentIds.value.size > 0);

const discardChanges = () => {
    localEntries.value = JSON.parse(JSON.stringify(initialSnapshot.value));
};

const shouldPassUnitId = computed(() => props.isGlobalAdmin || (props.eligibleUnits?.length || 0) > 1);

const getLevelRubrics = (levelId) => {
    if (levelId !== null && levelId !== undefined && props.levelRubricsByLevel?.[String(levelId)]) {
        return props.levelRubricsByLevel[String(levelId)];
    }
    return props.rubrics || { materi: [], surah_pendek: [], doa_harian: [], hadist: [] };
};

const getStudentRubricIds = (st) => {
    const lvlRub = getLevelRubrics(st?.level_id);
    const ids = [
        ...(lvlRub.materi || []).map(i => String(i.id)),
        ...(lvlRub.surah_pendek || []).map(i => String(i.id)),
    ];
    if (!props.usesGradeRubrics) {
        ids.push(...(lvlRub.hadist || []).map(i => String(i.id)));
        ids.push(...(lvlRub.doa_harian || []).map(i => String(i.id)));
    } else if (st?.grade_level) {
        const gRub = props.gradeRubricsByGrade?.[String(st.grade_level)] || {};
        ['hadist', 'doa_harian', 'niat_sholat', 'bacaan_sholat'].forEach(cat => {
            (gRub[cat] || []).forEach(i => ids.push(String(i.id)));
        });
    }
    return ids;
};

const studentProgress = (st) => {
    const ids = getStudentRubricIds(st);
    const total = ids.length;
    const entryGrades = localEntries.value[st.student_id]?.grades || {};
    const filled = ids.filter(id => entryGrades[id] && entryGrades[id] !== '-').length;
    return {
        filled,
        total,
        percent: total > 0 ? Math.round((filled / total) * 100) : 0,
    };
};

const studentSections = computed(() => {
    const list = props.students || [];
    const isInclusive = Boolean(props.selectedGroup?.is_inclusive);

    if (!props.usesGradeRubrics && !isInclusive) {
        const lvlRub = getLevelRubrics(props.selectedGroup?.level_id);
        return [{
            key: 'all',
            levelLabel: props.selectedGroup?.level_name || '-',
            gradeLabel: null,
            students: list,
            materi: lvlRub.materi || [],
            surah: lvlRub.surah_pendek || [],
            doa: lvlRub.doa_harian || [],
            hadist: lvlRub.hadist || [],
            niatSholat: [],
            bacaanSholat: [],
        }];
    }

    const grouped = new Map();
    list.forEach(st => {
        const lvlKey = isInclusive ? String(st.level_id ?? 'none') : String(props.selectedGroup?.level_id ?? 'single');
        const gKey = props.usesGradeRubrics ? (st.grade_level ? String(st.grade_level) : 'other') : 'all';
        const compositeKey = `${lvlKey}::${gKey}`;
        if (!grouped.has(compositeKey)) {
            grouped.set(compositeKey, {
                key: compositeKey,
                levelId: st.level_id ?? props.selectedGroup?.level_id,
                levelName: st.level_name || props.selectedGroup?.level_name || '-',
                levelSort: st.level_sort_order ?? 999,
                gKey,
                students: [],
            });
        }
        grouped.get(compositeKey).students.push(st);
    });

    const sortedBuckets = Array.from(grouped.values()).sort((a, b) => {
        if (isInclusive && a.levelSort !== b.levelSort) {
            return a.levelSort - b.levelSort;
        }
        if (a.gKey === b.gKey) return 0;
        if (a.gKey === 'other' || a.gKey === 'all') return 1;
        if (b.gKey === 'other' || b.gKey === 'all') return -1;
        return Number(a.gKey) - Number(b.gKey);
    });

    return sortedBuckets.map(bucket => {
        const lvlRub = getLevelRubrics(bucket.levelId);
        const gRub = props.usesGradeRubrics && bucket.gKey !== 'all'
            ? (props.gradeRubricsByGrade?.[bucket.gKey] || {})
            : {};

        return {
            key: bucket.key,
            levelLabel: bucket.levelName,
            gradeLabel: props.usesGradeRubrics
                ? (bucket.gKey === 'other' ? 'Tanpa Tingkat Kelas' : `Kelas ${bucket.gKey}`)
                : null,
            students: bucket.students,
            materi: lvlRub.materi || [],
            surah: lvlRub.surah_pendek || [],
            doa: props.usesGradeRubrics ? (gRub.doa_harian || []) : (lvlRub.doa_harian || []),
            hadist: props.usesGradeRubrics ? (gRub.hadist || []) : (lvlRub.hadist || []),
            niatSholat: props.usesGradeRubrics ? (gRub.niat_sholat || []) : [],
            bacaanSholat: props.usesGradeRubrics ? (gRub.bacaan_sholat || []) : [],
        };
    });
});

const sectionColCount = (sec) => {
    return sec.materi.length + sec.surah.length + sec.doa.length + sec.hadist.length + sec.niatSholat.length + sec.bacaanSholat.length;
};

const toggleLegend = (key) => {
    showLegendMap.value = { ...showLegendMap.value, [key]: !showLegendMap.value[key] };
};

const gradeSelectClass = (grade) => {
    if (grade === 'A') return 'text-emerald-700 bg-emerald-50/90 border-emerald-200 font-semibold';
    if (grade === 'B') return 'text-sky-700 bg-sky-50/90 border-sky-200 font-semibold';
    if (grade === 'C') return 'text-amber-700 bg-amber-50/90 border-amber-200 font-semibold';
    if (grade === 'D') return 'text-rose-700 bg-rose-50/90 border-rose-200 font-semibold';
    return 'text-slate-400 bg-white border-slate-200 hover:border-slate-300';
};

const gradePillActiveClass = (grade) => {
    if (grade === 'A') return 'bg-emerald-600 text-white border-emerald-600 shadow-xs';
    if (grade === 'B') return 'bg-sky-600 text-white border-sky-600 shadow-xs';
    if (grade === 'C') return 'bg-amber-500 text-white border-amber-500 shadow-xs';
    if (grade === 'D') return 'bg-rose-600 text-white border-rose-600 shadow-xs';
    return 'bg-slate-700 text-white border-slate-700';
};

// Single-key fast grading on desktop select focus
const onGradeKeydown = (e, studentId, itemId) => {
    const key = e.key.toUpperCase();
    if (['A', 'B', 'C', 'D'].includes(key)) {
        e.preventDefault();
        localEntries.value[studentId].grades[String(itemId)] = key;
    } else if (e.key === '-' || e.key === 'Backspace' || e.key === 'Delete') {
        e.preventDefault();
        localEntries.value[studentId].grades[String(itemId)] = '-';
    }
};

// Confirm navigation if dirty
const pendingNav = ref(null);
const showLeaveConfirm = ref(false);
const showMobileGroupSheet = ref(false);

const executeNavigate = (overrides = {}) => {
    router.get(route('quran.assessments.index'), {
        unit_id: shouldPassUnitId.value ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        semester: overrides.semester ?? props.semester,
        role_filter: overrides.role_filter ?? props.roleFilter,
        group_id: overrides.group_id !== undefined ? overrides.group_id : props.selectedGroup?.id,
    }, { preserveScroll: true });
};

const navigateWith = (overrides = {}) => {
    if (isDirty.value) {
        pendingNav.value = overrides;
        showLeaveConfirm.value = true;
        return;
    }
    executeNavigate(overrides);
};

const confirmLeave = () => {
    showLeaveConfirm.value = false;
    const overrides = pendingNav.value || {};
    pendingNav.value = null;
    executeNavigate(overrides);
};

const saveAllScores = () => {
    if (!props.selectedGroup || props.students.length === 0 || props.isLocked) return;
    isSaving.value = true;
    const entries = props.students.map(st => ({
        student_id: st.student_id,
        grades: localEntries.value[st.student_id]?.grades || {},
        teacher_note: localEntries.value[st.student_id]?.teacher_note || '',
    }));

    router.post(route('quran.assessments.store'), {
        unit_id: props.unit.id,
        group_id: props.selectedGroup.id,
        academic_year_id: props.activeYear.id,
        semester: props.semester,
        entries,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            initialSnapshot.value = JSON.parse(JSON.stringify(localEntries.value));
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// ==================== MOBILE BOTTOM SHEET (TACTILE 1-TAP + QUICK-FILL + JUMP + NEXT/PREV) ====================
const mobileStudentIndex = ref(-1);
const showMobileSheet = ref(false);

const mobileStudent = computed(() => {
    if (mobileStudentIndex.value < 0) return null;
    return props.students?.[mobileStudentIndex.value] || null;
});

const mobileStudentProgress = computed(() => {
    if (!mobileStudent.value) return { filled: 0, total: 0, percent: 0 };
    return studentProgress(mobileStudent.value);
});

const mobileRubricBlocks = computed(() => {
    const st = mobileStudent.value;
    if (!st) return [];

    const lvlRub = getLevelRubrics(st.level_id);
    const levelLabel = st.level_name || props.selectedGroup?.level_name || '-';
    const blocks = [];
    if (lvlRub.materi?.length) {
        blocks.push({ key: 'materi', shortLabel: 'Materi', prefix: 'M', title: `Materi Pokok (${levelLabel})`, dot: 'bg-[#00796B]', items: lvlRub.materi });
    }
    if (lvlRub.surah_pendek?.length) {
        blocks.push({ key: 'surah', shortLabel: 'Surah', prefix: 'S', title: `Surah Pendek (${levelLabel})`, dot: 'bg-amber-500', items: lvlRub.surah_pendek });
    }

    if (!props.usesGradeRubrics) {
        if (lvlRub.hadist?.length) {
            blocks.push({ key: 'hadist', shortLabel: 'Hadist', prefix: 'H', title: 'Hadist', dot: 'bg-violet-500', items: lvlRub.hadist });
        }
        if (lvlRub.doa_harian?.length) {
            blocks.push({ key: 'doa', shortLabel: 'Doa', prefix: 'D', title: 'Doa Harian', dot: 'bg-sky-500', items: lvlRub.doa_harian });
        }
    } else {
        const gKey = st.grade_level ? String(st.grade_level) : null;
        const gRub = gKey ? (props.gradeRubricsByGrade?.[gKey] || {}) : {};
        const gradeSuffix = gKey ? ` (Kelas ${gKey})` : '';
        if (gRub.hadist?.length) blocks.push({ key: 'hadist', shortLabel: 'Hadist', prefix: 'H', title: `Hadist${gradeSuffix}`, dot: 'bg-violet-500', items: gRub.hadist });
        if (gRub.doa_harian?.length) blocks.push({ key: 'doa', shortLabel: 'Doa', prefix: 'D', title: `Doa Harian${gradeSuffix}`, dot: 'bg-sky-500', items: gRub.doa_harian });
        if (gRub.niat_sholat?.length) blocks.push({ key: 'niat', shortLabel: 'Niat', prefix: 'N', title: `Niat Sholat & Bersuci${gradeSuffix}`, dot: 'bg-teal-600', items: gRub.niat_sholat });
        if (gRub.bacaan_sholat?.length) blocks.push({ key: 'bacaan', shortLabel: 'Bacaan', prefix: 'B', title: `Bacaan Sholat${gradeSuffix}`, dot: 'bg-indigo-500', items: gRub.bacaan_sholat });
    }
    return blocks;
});

const openMobileInput = (idx) => {
    mobileStudentIndex.value = idx;
    showMobileSheet.value = true;
};

const closeMobileInput = () => {
    showMobileSheet.value = false;
};

const setMobileGrade = (studentId, itemId, g) => {
    const cur = localEntries.value[studentId].grades[String(itemId)];
    localEntries.value[studentId].grades[String(itemId)] = cur === g ? '-' : g;
};

const fillCategoryGrades = (studentId, items, grade) => {
    items.forEach(item => {
        localEntries.value[studentId].grades[String(item.id)] = grade;
    });
};

const scrollToSheetSection = (sectionId) => {
    const el = document.getElementById(`q-sheet-sec-${sectionId}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const selectedGroupStats = computed(() => {
    const g = (props.groups || []).find(x => x.id === props.selectedGroup?.id);
    return g || { filled_count: 0, members_count: props.students?.length || 0 };
});

const handleBeforeUnload = (e) => {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
onUnmounted(() => window.removeEventListener('beforeunload', handleBeforeUnload));
</script>

<template>
    <div>
        <Head title="Input Nilai — Program Al-Qur'an" />

        <div class="max-w-7xl mx-auto py-1 sm:py-2 pb-40 md:pb-24 space-y-4">
            <QuranNav
                active-tab="assessments"
                page-title="Input Nilai Al-Qur'an"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            >
                <!-- Desktop Header CTA (100% Preserved) -->
                <template v-if="!isLocked && selectedGroup && students.length > 0" #actions>
                    <button
                        type="button"
                        @click="saveAllScores"
                        :disabled="isSaving || !isDirty"
                        class="h-8 px-3.5 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-40 shadow-[0_1px_2px_rgba(0,121,107,0.25)] transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <span v-if="isSaving" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <CheckIcon v-else class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Nilai' }}</span>
                    </button>
                </template>

                <!-- Mobile Header Semester Toggle -->
                <template v-if="!isLocked" #mobile-actions>
                    <div class="inline-flex items-center p-0.5 rounded-xl bg-slate-200/70 gap-0.5 h-9">
                        <button
                            type="button"
                            @click="navigateWith({ semester: 'ganjil' })"
                            class="h-8 px-2.5 rounded-lg text-[11px] font-semibold transition-all"
                            :class="semester === 'ganjil' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600'"
                        >
                            Ganjil
                        </button>
                        <button
                            type="button"
                            @click="navigateWith({ semester: 'genap' })"
                            class="h-8 px-2.5 rounded-lg text-[11px] font-semibold transition-all"
                            :class="semester === 'genap' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600'"
                        >
                            Genap
                        </button>
                    </div>
                </template>
            </QuranNav>

            <!-- Locked Notice -->
            <div v-if="isLocked" class="bg-white rounded-xl border border-amber-200/90 p-8 text-center">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center mx-auto mb-3">
                    <LockClosedIcon class="w-5 h-5" />
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Periode Input Nilai Ditutup</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
                    Koordinator Al-Qur'an / Admin sedang mengunci pengisian nilai. Hubungi koordinator jika perlu membuka kembali periode penilaian.
                </p>
            </div>

            <template v-else>
                <!-- ==================== DESKTOP FILTER + GROUP STRIP (100% PRESERVED) ==================== -->
                <div v-if="isDesktop" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="inline-flex items-center p-1 rounded-lg bg-slate-200/60 gap-0.5">
                            <button
                                v-for="opt in [
                                    { key: 'all', label: 'Semua' },
                                    { key: 'main', label: 'Pengampu Utama' },
                                    { key: 'assistant', label: 'Asisten' },
                                ]"
                                :key="opt.key"
                                type="button"
                                @click="navigateWith({ role_filter: opt.key, group_id: null })"
                                class="h-7 px-3 rounded-md text-xs font-medium transition-all"
                                :class="roleFilter === opt.key ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>

                        <div class="inline-flex items-center p-1 rounded-lg bg-slate-200/60 gap-0.5">
                            <button
                                type="button"
                                @click="navigateWith({ semester: 'ganjil' })"
                                class="h-7 px-3 rounded-md text-xs font-medium transition-all"
                                :class="semester === 'ganjil' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Sem. Ganjil
                            </button>
                            <button
                                type="button"
                                @click="navigateWith({ semester: 'genap' })"
                                class="h-7 px-3 rounded-md text-xs font-medium transition-all"
                                :class="semester === 'genap' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Sem. Genap
                            </button>
                        </div>
                    </div>

                    <div v-if="groups.length > 0" class="flex flex-nowrap overflow-x-auto gap-2 pb-1 scrollbar-none">
                        <button
                            v-for="g in groups"
                            :key="g.id"
                            type="button"
                            @click="navigateWith({ group_id: g.id })"
                            class="px-3.5 py-2.5 rounded-xl border text-left transition-all shrink-0 min-w-[175px] max-w-[230px]"
                            :class="selectedGroup && selectedGroup.id === g.id
                                ? 'bg-white border-[#00796B] ring-2 ring-[#00796B]/12 shadow-xs'
                                : 'bg-white/70 border-slate-200/80 hover:bg-white hover:border-slate-300'"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md border"
                                    :class="selectedGroup && selectedGroup.id === g.id
                                        ? 'bg-teal-50 text-[#00796B] border-teal-200/80'
                                        : 'bg-slate-100 text-slate-600 border-transparent'"
                                >
                                    {{ g.level_name }}
                                </span>
                                <span
                                    class="text-[11px] font-medium tabular-nums"
                                    :class="g.filled_count === g.members_count && g.members_count > 0 ? 'text-[#00796B]' : 'text-slate-400'"
                                >
                                    {{ g.filled_count }}/{{ g.members_count }}
                                </span>
                            </div>
                            <div class="text-xs font-semibold text-slate-900 mt-1.5 truncate">{{ g.name }}</div>
                            <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ g.teacher_name }}</div>
                        </button>
                    </div>
                </div>

                <!-- ==================== MOBILE COMPACT GROUP SWITCHER CARD (< md) ==================== -->
                <div v-else-if="groups.length > 0 && selectedGroup">
                    <button
                        type="button"
                        @click="showMobileGroupSheet = true"
                        class="w-full bg-white rounded-2xl border border-slate-200/90 p-3.5 text-left shadow-[0_1px_2px_rgba(15,23,42,0.04)] active:scale-[0.99] transition-all space-y-2"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70 shrink-0">
                                    {{ selectedGroup.level_name }}
                                </span>
                                <span class="text-sm font-semibold text-slate-900 truncate">{{ selectedGroup.name }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1 text-[11px] font-medium text-[#00796B] shrink-0">
                                <span v-if="groups.length > 1">Ganti</span>
                                <ChevronUpDownIcon class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                            <span class="truncate">{{ selectedGroup.teacher_name }}<span v-if="selectedGroup.session_name"> · {{ selectedGroup.session_name }}</span></span>
                            <span class="font-semibold tabular-nums shrink-0 text-[#00796B]">{{ selectedGroupStats.filled_count }}/{{ selectedGroupStats.members_count }}</span>
                        </div>
                    </button>
                </div>

                <!-- Selected Group Workspace -->
                <div v-if="selectedGroup" class="space-y-4">
                    <!-- Desktop Context Strip (100% Preserved) -->
                    <div class="hidden md:flex bg-white rounded-xl border border-slate-200/80 px-4 py-3 items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-semibold text-slate-900">{{ selectedGroup.name }}</h2>
                                <span
                                    class="px-2 py-0.5 rounded-md text-[11px] font-medium border"
                                    :class="selectedGroup.is_inclusive
                                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200/80'
                                        : 'bg-teal-50 text-[#00796B] border-teal-200/70'"
                                >
                                    {{ selectedGroup.level_name }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ selectedGroup.teacher_name }}
                                    <span v-if="selectedGroup.session_name"> · {{ selectedGroup.session_name }}</span>
                                </span>
                            </div>
                            <p v-if="selectedGroup.is_inclusive" class="text-[11px] text-slate-400 mt-1">
                                Kelompok Inklusi Multi-Jilid: tabel otomatis dikelompokkan berdasarkan jilid masing-masing siswa{{ usesGradeRubrics ? ' dan kelas formalnya' : '' }}.
                            </p>
                            <p v-else-if="usesGradeRubrics" class="text-[11px] text-slate-400 mt-1">
                                Materi &amp; Surah mengikuti {{ selectedGroup.level_name }}, sedangkan Hadist / Doa / Niat / Bacaan Sholat otomatis mengikuti kelas formal siswa.
                            </p>
                        </div>

                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400 shrink-0">
                            <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">A</kbd>
                            <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">B</kbd>
                            <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">C</kbd>
                            <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">D</kbd>
                            <span>ketik cepat di sel</span>
                        </div>
                    </div>

                    <div v-if="students.length === 0" class="bg-white rounded-xl border border-slate-200/80 p-10 text-center text-xs text-slate-400">
                        Belum ada siswa aktif di kelompok {{ selectedGroup.name }}.
                    </div>

                    <!-- ==================== DESKTOP MATRIX SUB-TABLES (100% PRESERVED) ==================== -->
                    <div v-else-if="isDesktop" class="space-y-4">
                        <div
                            v-for="sec in studentSections"
                            :key="sec.key"
                            class="bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden"
                        >
                            <div class="px-4 py-2.5 bg-slate-50/70 border-b border-slate-200/70 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span v-if="selectedGroup.is_inclusive" class="px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-[#00796B] border border-teal-200/80">
                                        {{ sec.levelLabel }}
                                    </span>
                                    <span v-if="sec.gradeLabel" class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-900 text-white">
                                        {{ sec.gradeLabel }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-600 tabular-nums">{{ sec.students.length }} siswa</span>
                                    <span v-if="sec.gradeLabel" class="text-[11px] text-slate-400">
                                        · {{ sec.levelLabel }} + Materi {{ sec.gradeLabel }}
                                    </span>
                                </div>

                                <button
                                    v-if="sectionColCount(sec) > 0"
                                    type="button"
                                    @click="toggleLegend(sec.key)"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                                >
                                    <InformationCircleIcon class="w-4 h-4 text-[#00796B]" />
                                    <span>Keterangan {{ sectionColCount(sec) }} kolom</span>
                                    <ChevronDownIcon
                                        class="w-3.5 h-3.5 transition-transform duration-200"
                                        :class="showLegendMap[sec.key] ? 'rotate-180 text-[#00796B]' : ''"
                                    />
                                </button>
                            </div>

                            <QuranCollapse :open="!!showLegendMap[sec.key]">
                                <div class="px-4 py-3 bg-white border-b border-slate-100 grid grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                                    <div v-if="sec.materi.length" class="space-y-1">
                                        <div class="font-semibold text-[#00796B] text-[11px]">Materi Pokok ({{ sec.levelLabel }})</div>
                                        <div v-for="(item, idx) in sec.materi" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">M{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.surah.length" class="space-y-1">
                                        <div class="font-semibold text-amber-700 text-[11px]">Surah Pendek ({{ sec.levelLabel }})</div>
                                        <div v-for="(item, idx) in sec.surah" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">S{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.hadist.length" class="space-y-1">
                                        <div class="font-semibold text-violet-700 text-[11px]">Hadist {{ sec.gradeLabel ? `(${sec.gradeLabel})` : '' }}</div>
                                        <div v-for="(item, idx) in sec.hadist" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">H{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.doa.length" class="space-y-1">
                                        <div class="font-semibold text-sky-700 text-[11px]">Doa Harian {{ sec.gradeLabel ? `(${sec.gradeLabel})` : '' }}</div>
                                        <div v-for="(item, idx) in sec.doa" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">D{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.niatSholat.length" class="space-y-1">
                                        <div class="font-semibold text-teal-700 text-[11px]">Niat Sholat ({{ sec.gradeLabel }})</div>
                                        <div v-for="(item, idx) in sec.niatSholat" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">N{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.bacaanSholat.length" class="space-y-1">
                                        <div class="font-semibold text-indigo-700 text-[11px]">Bacaan Sholat ({{ sec.gradeLabel }})</div>
                                        <div v-for="(item, idx) in sec.bacaanSholat" :key="item.id" class="text-slate-600 leading-snug"><span class="font-semibold text-slate-800 mr-1">B{{ idx + 1 }}</span>{{ item.title }}</div>
                                    </div>
                                </div>
                            </QuranCollapse>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm whitespace-nowrap border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200/70 bg-white text-[11px] font-semibold">
                                            <th class="px-4 py-2.5 text-left text-slate-700 sticky left-0 bg-white border-r border-slate-200/70 z-10 min-w-[210px]">
                                                Siswa
                                            </th>
                                            <th v-if="sec.materi.length" :colspan="sec.materi.length" class="px-2 py-2 text-center text-[#00796B] border-l border-slate-200/70 border-t-2 border-t-[#00796B] bg-slate-50/40">Materi Pokok</th>
                                            <th v-if="sec.surah.length" :colspan="sec.surah.length" class="px-2 py-2 text-center text-amber-700 border-l border-slate-200/70 border-t-2 border-t-amber-400 bg-slate-50/40">Surah Pendek</th>
                                            <th v-if="sec.hadist.length" :colspan="sec.hadist.length" class="px-2 py-2 text-center text-violet-700 border-l border-slate-200/70 border-t-2 border-t-violet-400 bg-slate-50/40">Hadist</th>
                                            <th v-if="sec.doa.length" :colspan="sec.doa.length" class="px-2 py-2 text-center text-sky-700 border-l border-slate-200/70 border-t-2 border-t-sky-400 bg-slate-50/40">Doa Harian</th>
                                            <th v-if="sec.niatSholat.length" :colspan="sec.niatSholat.length" class="px-2 py-2 text-center text-teal-700 border-l border-slate-200/70 border-t-2 border-t-teal-500 bg-slate-50/40">Niat Sholat</th>
                                            <th v-if="sec.bacaanSholat.length" :colspan="sec.bacaanSholat.length" class="px-2 py-2 text-center text-indigo-700 border-l border-slate-200/70 border-t-2 border-t-indigo-400 bg-slate-50/40">Bacaan Sholat</th>
                                        </tr>
                                        <tr class="bg-slate-50/60 border-b border-slate-200/80 text-[11px] font-medium text-slate-500">
                                            <th class="px-4 py-1.5 sticky left-0 bg-slate-50 border-r border-slate-200/70 z-10"></th>
                                            <th v-for="(item, idx) in sec.materi" :key="'hm'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">M{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.surah" :key="'hs'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">S{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.hadist" :key="'hh'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">H{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.doa" :key="'hd'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">D{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.niatSholat" :key="'hn'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">N{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.bacaanSholat" :key="'hb'+item.id" class="px-1.5 py-1.5 text-center border-l border-slate-100 w-[68px] cursor-help" :title="item.title">B{{ idx + 1 }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template v-for="st in sec.students" :key="st.student_id">
                                            <tr
                                                v-if="localEntries[st.student_id]"
                                                class="transition-colors hover:bg-slate-50/70"
                                            >
                                                <td
                                                    class="px-4 py-2.5 sticky left-0 bg-white border-r border-slate-200/70 z-10 transition-colors"
                                                    :class="dirtyStudentIds.has(st.student_id) ? 'shadow-[inset_3px_0_0_#00796B]' : ''"
                                                >
                                                    <div class="font-medium text-slate-900 text-[13px] leading-snug">{{ st.full_name }}</div>
                                                    <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-400">
                                                        <span>Kelas {{ st.classroom_name }}</span>
                                                        <span v-if="st.previous_level_mismatch" class="px-1.5 py-px rounded bg-amber-50 text-amber-700 border border-amber-200/70 text-[10px] font-medium">Naik Jilid</span>
                                                    </div>
                                                </td>

                                                <td v-for="item in sec.materi" :key="'m'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.surah" :key="'s'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.hadist" :key="'h'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.doa" :key="'d'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.niatSholat" :key="'n'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.bacaanSholat" :key="'b'+st.student_id+item.id" class="px-1 py-2 text-center border-l border-slate-100">
                                                    <select
                                                        v-model="localEntries[st.student_id].grades[String(item.id)]"
                                                        @keydown="(e) => onGradeKeydown(e, st.student_id, item.id)"
                                                        :title="item.title"
                                                        :class="['w-14 h-8 rounded-lg border text-center text-xs transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#00796B]/25', gradeSelectClass(localEntries[st.student_id].grades[String(item.id)])]"
                                                    >
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                            </tr>

                                            <tr v-if="localEntries[st.student_id]" class="bg-slate-50/40 border-b border-slate-200/70">
                                                <td
                                                    class="px-4 py-1.5 sticky left-0 bg-slate-50/90 border-r border-slate-200/70 z-10"
                                                    :class="dirtyStudentIds.has(st.student_id) ? 'shadow-[inset_3px_0_0_#00796B]' : ''"
                                                >
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                                                        <PencilSquareIcon class="w-3 h-3" />
                                                        Catatan rapor
                                                    </span>
                                                </td>
                                                <td :colspan="Math.max(1, sectionColCount(sec))" class="px-2.5 py-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            v-model="localEntries[st.student_id].teacher_note"
                                                            type="text"
                                                            maxlength="300"
                                                            placeholder="Tulis catatan guru Al-Qur'an (opsional)..."
                                                            class="w-full h-7 text-xs bg-transparent hover:bg-white focus:bg-white border border-transparent hover:border-slate-200 focus:border-[#00796B] rounded-md px-2.5 text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-[#00796B]/15 transition-colors"
                                                        />
                                                        <span class="text-[10px] text-slate-400 tabular-nums shrink-0">{{ (localEntries[st.student_id].teacher_note || '').length }}/300</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== MOBILE NATIVE STUDENT PROGRESS LIST (< md) ==================== -->
                    <div v-else class="space-y-2">
                        <div class="flex items-center justify-between text-[11px] text-slate-500 px-0.5">
                            <span class="font-medium tabular-nums">{{ students.length }} siswa · Ketuk nama untuk input nilai</span>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 overflow-hidden shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
                            <button
                                v-for="(st, idx) in students"
                                :key="'mob-' + st.student_id"
                                type="button"
                                @click="openMobileInput(idx)"
                                class="w-full px-3.5 py-3 flex items-center justify-between gap-3 text-left active:bg-slate-50 transition-colors"
                                :class="dirtyStudentIds.has(st.student_id) ? 'shadow-[inset_3px_0_0_#00796B]' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div
                                        class="w-9 h-9 rounded-full font-semibold text-xs flex items-center justify-center shrink-0"
                                        :class="studentProgress(st).percent === 100 ? 'bg-teal-50 text-[#00796B] border border-teal-200/70' : 'bg-slate-100 text-slate-600'"
                                    >
                                        <CheckIcon v-if="studentProgress(st).percent === 100 && !dirtyStudentIds.has(st.student_id)" class="w-4 h-4 stroke-[2.5]" />
                                        <span v-else>{{ st.full_name?.charAt(0) }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-semibold text-slate-900 truncate">{{ st.full_name }}</span>
                                            <span
                                                v-if="selectedGroup?.is_inclusive && st.level_name"
                                                class="px-1.5 py-px rounded text-[10px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70 shrink-0"
                                            >
                                                {{ st.level_name }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[11px] text-slate-500 shrink-0">Kls {{ st.classroom_name }}</span>
                                            <div class="flex-1 max-w-[90px] h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="studentProgress(st).percent === 100 ? 'bg-[#00796B]' : 'bg-amber-400'"
                                                    :style="{ width: `${studentProgress(st).percent}%` }"
                                                />
                                            </div>
                                            <span class="text-[10px] text-slate-400 tabular-nums">{{ studentProgress(st).filled }}/{{ studentProgress(st).total }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span
                                        v-if="dirtyStudentIds.has(st.student_id)"
                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200/80"
                                    >
                                        Diubah
                                    </span>
                                    <ChevronRightIcon class="w-4 h-4 text-slate-400" />
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- No Groups Empty State -->
                <div v-else class="bg-white rounded-xl border border-slate-200/80 p-10 text-center">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <AcademicCapIcon class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-semibold text-slate-800">Belum ada kelompok untuk filter ini</p>
                    <p class="text-xs text-slate-500 mt-1">Pastikan kelompok sudah dibuat dan Anda ditugaskan sebagai pengampu utama atau asisten.</p>
                </div>
            </template>
        </div>

        <!-- ==================== MOBILE GROUP PICKER BOTTOM SHEET ==================== -->
        <QuranDialog
            :show="showMobileGroupSheet"
            variant="sheet"
            title="Pilih Kelompok Mengaji"
            subtitle="Saring peran pengampu dan pilih kelompok untuk dinilai."
            @close="showMobileGroupSheet = false"
        >
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-3 p-1 rounded-xl bg-slate-100 gap-1">
                    <button
                        v-for="opt in [
                            { key: 'all', label: 'Semua' },
                            { key: 'main', label: 'Utama' },
                            { key: 'assistant', label: 'Asisten' },
                        ]"
                        :key="opt.key"
                        type="button"
                        @click="() => { showMobileGroupSheet = false; navigateWith({ role_filter: opt.key, group_id: null }); }"
                        class="h-9 rounded-lg text-xs font-semibold transition-all"
                        :class="roleFilter === opt.key ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 overflow-hidden bg-white">
                    <button
                        v-for="g in groups"
                        :key="'mgp-' + g.id"
                        type="button"
                        @click="() => { showMobileGroupSheet = false; navigateWith({ group_id: g.id }); }"
                        class="w-full px-3.5 py-3 flex items-center justify-between gap-3 text-left transition-colors active:bg-slate-50"
                        :class="selectedGroup && selectedGroup.id === g.id ? 'bg-teal-50/40' : ''"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70">{{ g.level_name }}</span>
                                <span class="text-sm font-semibold text-slate-900 truncate">{{ g.name }}</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5 truncate">{{ g.teacher_name }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs font-semibold tabular-nums" :class="g.filled_count === g.members_count && g.members_count > 0 ? 'text-[#00796B]' : 'text-slate-400'">
                                {{ g.filled_count }}/{{ g.members_count }}
                            </span>
                            <CheckIcon v-if="selectedGroup && selectedGroup.id === g.id" class="w-4 h-4 text-[#00796B]" />
                        </div>
                    </button>
                </div>
            </div>
        </QuranDialog>

        <!-- ==================== FLOATING DIRTY SAVE BAR (CLEARS MOBILE FAB!) ==================== -->
        <Teleport to="body">
            <Transition name="q-float">
                <div
                    v-if="isDirty && !isLocked && !showMobileSheet"
                    class="fixed bottom-[94px] md:bottom-5 inset-x-3 md:inset-x-4 z-[90] flex justify-center pointer-events-none"
                >
                    <div class="pointer-events-auto flex items-center gap-2.5 md:gap-3 px-3.5 md:px-4 py-2.5 rounded-2xl md:rounded-xl bg-slate-900 text-white shadow-xl shadow-slate-900/30 border border-slate-700/80 max-w-md w-full sm:w-auto">
                        <span class="relative flex h-2 w-2 shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75" />
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400" />
                        </span>
                        <span class="text-xs font-medium text-slate-100 flex-1 tabular-nums">
                            {{ dirtyStudentIds.size }} siswa diubah
                        </span>
                        <button
                            type="button"
                            @click="discardChanges"
                            :disabled="isSaving"
                            class="h-8 md:h-7 px-2.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="saveAllScores"
                            :disabled="isSaving"
                            class="h-8 md:h-7 px-3.5 rounded-lg text-xs font-semibold bg-[#00796B] hover:bg-[#00897B] text-white transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                        >
                            <span v-if="isSaving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ==================== MOBILE TACTILE 1-TAP GRADING BOTTOM SHEET ==================== -->
        <QuranDialog
            :show="showMobileSheet && !!mobileStudent && !!localEntries[mobileStudent?.student_id]"
            variant="sheet"
            :title="mobileStudent?.full_name || ''"
            :subtitle="`Kelas ${mobileStudent?.classroom_name || ''} · ${mobileStudent?.level_name || selectedGroup?.level_name || ''} (${mobileStudentIndex + 1}/${students.length})`"
            @close="closeMobileInput"
        >
            <div v-if="mobileStudent && localEntries[mobileStudent.student_id]">
                <!-- Sticky Progress + Category Jump Bar inside Sheet -->
                <div class="sticky top-0 z-10 bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 pt-2.5 pb-2.5 space-y-2">
                    <div class="flex items-center gap-2.5">
                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-[#00796B] transition-all duration-300"
                                :style="{ width: `${mobileStudentProgress.percent}%` }"
                            />
                        </div>
                        <span class="text-[11px] font-semibold text-slate-600 tabular-nums shrink-0">
                            {{ mobileStudentProgress.filled }}/{{ mobileStudentProgress.total }} terisi
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none">
                        <button
                            v-for="block in mobileRubricBlocks"
                            :key="'jump-' + block.key"
                            type="button"
                            @click="scrollToSheetSection(block.key)"
                            class="h-7 px-2.5 rounded-full bg-slate-100 active:bg-slate-200 text-slate-700 text-[11px] font-medium whitespace-nowrap shrink-0 inline-flex items-center gap-1.5"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="block.dot" />
                            <span>{{ block.shortLabel }}</span>
                        </button>
                        <button
                            type="button"
                            @click="scrollToSheetSection('catatan')"
                            class="h-7 px-2.5 rounded-full bg-slate-100 active:bg-slate-200 text-slate-700 text-[11px] font-medium whitespace-nowrap shrink-0"
                        >
                            Catatan
                        </button>
                    </div>
                </div>

                <!-- Rubric Sections -->
                <div class="p-4 space-y-5">
                    <div
                        v-for="block in mobileRubricBlocks"
                        :id="`q-sheet-sec-${block.key}`"
                        :key="block.key"
                        class="space-y-2 scroll-mt-24"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-800 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" :class="block.dot" />
                                <span class="truncate">{{ block.title }}</span>
                            </div>
                            <!-- Quick Fill All A / B per Category -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    @click="fillCategoryGrades(mobileStudent.student_id, block.items, 'A')"
                                    class="h-6 px-2 rounded-md bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[10px] font-semibold active:scale-95 transition-transform"
                                >
                                    Semua A
                                </button>
                                <button
                                    type="button"
                                    @click="fillCategoryGrades(mobileStudent.student_id, block.items, 'B')"
                                    class="h-6 px-2 rounded-md bg-sky-50 border border-sky-200/80 text-sky-700 text-[10px] font-semibold active:scale-95 transition-transform"
                                >
                                    Semua B
                                </button>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 bg-white overflow-hidden">
                            <div
                                v-for="(item, idx) in block.items"
                                :key="`${block.key}-${item.id}`"
                                class="p-3 space-y-2.5"
                            >
                                <div class="text-xs font-medium text-slate-800 leading-snug">
                                    <span class="text-slate-400 font-semibold mr-1">{{ block.prefix }}{{ idx + 1 }}.</span>
                                    {{ item.title }}
                                </div>
                                <div class="grid grid-cols-5 gap-1.5">
                                    <button
                                        v-for="g in grades"
                                        :key="g"
                                        type="button"
                                        @click="setMobileGrade(mobileStudent.student_id, item.id, g)"
                                        class="h-10 rounded-xl text-xs font-semibold border transition-all active:scale-95"
                                        :class="localEntries[mobileStudent.student_id].grades[String(item.id)] === g
                                            ? gradePillActiveClass(g)
                                            : 'bg-slate-50 text-slate-500 border-slate-200/80'"
                                    >
                                        {{ g }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="q-sheet-sec-catatan" class="scroll-mt-24">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Guru Al-Qur'an</label>
                        <textarea
                            v-model="localEntries[mobileStudent.student_id].teacher_note"
                            rows="3"
                            maxlength="300"
                            placeholder="Tulis catatan guru untuk rapor (opsional)..."
                            class="w-full rounded-xl border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                        />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="mobileStudentIndex <= 0"
                            @click="mobileStudentIndex--"
                            class="h-10 w-10 rounded-xl border border-slate-200 bg-white text-slate-600 active:bg-slate-50 disabled:opacity-30 inline-flex items-center justify-center transition-colors"
                            aria-label="Siswa sebelumnya"
                        >
                            <ChevronLeftIcon class="w-5 h-5" />
                        </button>
                        <button
                            type="button"
                            :disabled="mobileStudentIndex >= students.length - 1"
                            @click="mobileStudentIndex++"
                            class="h-10 w-10 rounded-xl border border-slate-200 bg-white text-slate-600 active:bg-slate-50 disabled:opacity-30 inline-flex items-center justify-center transition-colors"
                            aria-label="Siswa berikutnya"
                        >
                            <ChevronRightIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <button type="button" @click="closeMobileInput" class="h-10 px-3.5 rounded-xl text-xs font-medium text-slate-600 bg-white border border-slate-200">
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="() => { closeMobileInput(); saveAllScores(); }"
                            :disabled="isSaving"
                            class="h-10 px-4 rounded-xl text-xs font-semibold text-white bg-[#00796B] active:scale-[0.98] transition-all"
                        >
                            Simpan Semua
                        </button>
                    </div>
                </div>
            </template>
        </QuranDialog>

        <!-- Confirm Navigation when Unsaved -->
        <QuranConfirmDialog
            :show="showLeaveConfirm"
            tone="warning"
            title="Perubahan nilai belum disimpan"
            message="Anda memiliki perubahan nilai yang belum disimpan. Yakin ingin pindah kelompok atau filter sekarang?"
            confirm-label="Pindah Tanpa Simpan"
            cancel-label="Tetap di Sini"
            @close="showLeaveConfirm = false"
            @confirm="confirmLeave"
        />
    </div>
</template>

<style scoped>
.q-float-enter-active {
    transition: opacity 220ms cubic-bezier(0.16, 1, 0.3, 1), transform 240ms cubic-bezier(0.16, 1, 0.3, 1);
}
.q-float-leave-active {
    transition: opacity 150ms ease-in, transform 150ms ease-in;
}
.q-float-enter-from,
.q-float-leave-to {
    opacity: 0;
    transform: translateY(16px);
}
</style>
