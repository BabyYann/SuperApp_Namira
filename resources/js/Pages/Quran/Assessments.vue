<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    CheckCircleIcon,
    InformationCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ChevronRightIcon,
    XMarkIcon,
    LockClosedIcon,
    PencilSquareIcon,
    AcademicCapIcon,
    ExclamationTriangleIcon,
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
    gradeRubricsByGrade: { type: Object, default: () => ({}) },
    students: Array,
});

const grades = ['-', 'A', 'B', 'C', 'D'];
const showLegendMap = ref({});
const isSaving = ref(false);
const isDirty = ref(false);
const isInitializing = ref(false);

const localEntries = ref({});

const hydrateLocal = () => {
    isInitializing.value = true;
    const map = {};
    (props.students || []).forEach(st => {
        map[st.student_id] = {
            grades: { ...(st.grades || {}) },
            teacher_note: st.teacher_note || '',
        };
    });
    localEntries.value = map;
    setTimeout(() => {
        isInitializing.value = false;
        isDirty.value = false;
    }, 50);
};

watch(() => props.students, hydrateLocal, { immediate: true, deep: true });
watch(localEntries, () => {
    if (!isInitializing.value) isDirty.value = true;
}, { deep: true });

const materiList = computed(() => props.rubrics?.materi || []);
const surahList = computed(() => props.rubrics?.surah_pendek || []);
const fallbackDoaList = computed(() => props.rubrics?.doa_harian || []);
const fallbackHadistList = computed(() => props.rubrics?.hadist || []);

// Build clean per-grade sub-tables when usesGradeRubrics is enabled so mixed-class groups have zero empty/wrong columns!
const studentSections = computed(() => {
    const list = props.students || [];
    if (!props.usesGradeRubrics) {
        return [{
            key: 'all',
            gradeLabel: null,
            students: list,
            materi: materiList.value,
            surah: surahList.value,
            doa: fallbackDoaList.value,
            hadist: fallbackHadistList.value,
            niatSholat: [],
            bacaanSholat: [],
        }];
    }

    const grouped = new Map();
    list.forEach(st => {
        const gKey = st.grade_level ? String(st.grade_level) : 'other';
        if (!grouped.has(gKey)) grouped.set(gKey, []);
        grouped.get(gKey).push(st);
    });

    const sortedKeys = Array.from(grouped.keys()).sort((a, b) => {
        if (a === 'other') return 1;
        if (b === 'other') return -1;
        return Number(a) - Number(b);
    });

    return sortedKeys.map(gKey => {
        const gRub = props.gradeRubricsByGrade?.[gKey] || {};
        return {
            key: gKey,
            gradeLabel: gKey === 'other' ? 'Tanpa Tingkat Kelas' : `Kelas ${gKey}`,
            students: grouped.get(gKey),
            materi: materiList.value,
            surah: surahList.value,
            doa: gRub.doa_harian || [],
            hadist: gRub.hadist || [],
            niatSholat: gRub.niat_sholat || [],
            bacaanSholat: gRub.bacaan_sholat || [],
        };
    });
});

const sectionColCount = (sec) => {
    return sec.materi.length + sec.surah.length + sec.doa.length + sec.hadist.length + sec.niatSholat.length + sec.bacaanSholat.length;
};

const toggleLegend = (key) => {
    showLegendMap.value[key] = !showLegendMap.value[key];
};

const getGradeColor = (grade) => {
    if (grade === 'A') return 'text-emerald-700 bg-emerald-50 border-emerald-300 font-extrabold';
    if (grade === 'B') return 'text-blue-700 bg-blue-50 border-blue-300 font-extrabold';
    if (grade === 'C') return 'text-amber-700 bg-amber-50 border-amber-300 font-extrabold';
    if (grade === 'D') return 'text-red-700 bg-red-50 border-red-300 font-extrabold';
    return 'text-slate-400 bg-white border-slate-200';
};

const navigateWith = (overrides = {}) => {
    if (isDirty.value && !confirm('Perubahan nilai belum disimpan. Yakin ingin pindah kelompok/filter?')) {
        return;
    }
    router.get(route('quran.assessments.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        semester: overrides.semester ?? props.semester,
        role_filter: overrides.role_filter ?? props.roleFilter,
        group_id: overrides.group_id !== undefined ? overrides.group_id : props.selectedGroup?.id,
    }, { preserveScroll: true });
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
            isDirty.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Mobile bottom-sheet drawer
const mobileStudent = ref(null);
const showMobileDrawer = ref(false);

const mobileRubrics = computed(() => {
    if (!mobileStudent.value) {
        return { materi: [], surah: [], doa: [], hadist: [], niatSholat: [], bacaanSholat: [] };
    }
    if (!props.usesGradeRubrics) {
        return {
            materi: materiList.value,
            surah: surahList.value,
            doa: fallbackDoaList.value,
            hadist: fallbackHadistList.value,
            niatSholat: [],
            bacaanSholat: [],
        };
    }
    const gKey = mobileStudent.value.grade_level ? String(mobileStudent.value.grade_level) : null;
    const gRub = gKey ? (props.gradeRubricsByGrade?.[gKey] || {}) : {};
    return {
        materi: materiList.value,
        surah: surahList.value,
        doa: gRub.doa_harian || [],
        hadist: gRub.hadist || [],
        niatSholat: gRub.niat_sholat || [],
        bacaanSholat: gRub.bacaan_sholat || [],
    };
});

const openMobileInput = (st) => {
    mobileStudent.value = st;
    showMobileDrawer.value = true;
};

const closeMobileInput = () => {
    showMobileDrawer.value = false;
    mobileStudent.value = null;
};

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
    <Head title="Input Nilai Al-Qur'an (Ummi)" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto pb-24">
            <QuranNav
                active-tab="assessments"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- Feature Locked Banner -->
            <div v-if="isLocked" class="bg-amber-50 border-2 border-amber-200 rounded-3xl p-8 text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-3">
                    <LockClosedIcon class="w-7 h-7" />
                </div>
                <h3 class="text-lg font-extrabold text-amber-900">Periode Input Nilai Sedang Ditutup</h3>
                <p class="text-sm text-amber-800/80 max-w-md mx-auto mt-1">
                    Koordinator Al-Qur'an / Admin sedang mengunci pengisian nilai. Hubungi koordinator apabila perlu membuka kembali periode penilaian.
                </p>
            </div>

            <template v-else>
                <!-- Filter Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex flex-wrap items-center gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                        <button
                            v-for="opt in [
                                { key: 'all', label: 'Semua Kelompok' },
                                { key: 'main', label: 'Pengampu Utama' },
                                { key: 'assistant', label: 'Asisten' },
                            ]"
                            :key="opt.key"
                            @click="navigateWith({ role_filter: opt.key, group_id: null })"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition"
                            :class="roleFilter === opt.key ? 'bg-[#1b4332] text-white shadow' : 'text-slate-600 hover:bg-slate-50'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                        <span class="text-[11px] font-bold text-slate-400 pl-2 uppercase">Semester:</span>
                        <button
                            @click="navigateWith({ semester: 'ganjil' })"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition"
                            :class="semester === 'ganjil' ? 'bg-amber-500 text-white shadow' : 'text-slate-600 hover:bg-slate-50'"
                        >
                            Ganjil
                        </button>
                        <button
                            @click="navigateWith({ semester: 'genap' })"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition"
                            :class="semester === 'genap' ? 'bg-amber-500 text-white shadow' : 'text-slate-600 hover:bg-slate-50'"
                        >
                            Genap
                        </button>
                    </div>
                </div>

                <!-- Tab Pemilihan Kelompok -->
                <div v-if="groups.length > 0" class="flex flex-nowrap overflow-x-auto gap-2 pb-2 mb-4 scrollbar-none">
                    <button
                        v-for="g in groups"
                        :key="g.id"
                        @click="navigateWith({ group_id: g.id })"
                        class="px-4 py-2.5 rounded-2xl border text-left transition shrink-0 min-w-[170px]"
                        :class="selectedGroup && selectedGroup.id === g.id
                            ? 'bg-white border-emerald-600 ring-2 ring-emerald-500/20 shadow-sm'
                            : 'bg-slate-50/80 border-slate-200 text-slate-600 hover:bg-white'"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full"
                                  :class="selectedGroup && selectedGroup.id === g.id ? 'bg-[#1b4332] text-white' : 'bg-slate-200 text-slate-700'">
                                {{ g.level_name }}
                            </span>
                            <span class="text-[11px] font-extrabold"
                                  :class="g.filled_count === g.members_count && g.members_count > 0 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ g.filled_count }}/{{ g.members_count }}
                            </span>
                        </div>
                        <div class="text-xs font-extrabold text-slate-800 mt-1 truncate">{{ g.name }}</div>
                        <div class="text-[10px] text-slate-500 truncate">{{ g.teacher_name }}</div>
                    </button>
                </div>

                <!-- Konten Input Nilai Kelompok Terpilih -->
                <div v-if="selectedGroup" class="space-y-5">
                    <!-- Sticky Action Bar -->
                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-800 flex items-center gap-2">
                                <AcademicCapIcon class="w-5 h-5 text-emerald-700" />
                                <span>{{ selectedGroup.name }} — {{ selectedGroup.level_name }}</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Pengampu: <strong>{{ selectedGroup.teacher_name }}</strong>
                                <span v-if="selectedGroup.session_name"> • {{ selectedGroup.session_name }}</span>
                                • TA {{ activeYear.name }} Semester {{ semester === 'ganjil' ? 'Ganjil' : 'Genap' }}
                            </p>
                            <p v-if="usesGradeRubrics" class="text-[11px] text-emerald-700 font-semibold mt-1">
                                Materi &amp; Surah mengikuti {{ selectedGroup.level_name }}, sedangkan Hadist/Doa/Niat/Bacaan Sholat otomatis mengikuti Kelas Formal masing-masing siswa.
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span v-if="isDirty" class="text-xs font-bold text-amber-600 flex items-center gap-1">
                                <ExclamationTriangleIcon class="w-4 h-4" />
                                Belum disimpan
                            </span>
                            <button
                                @click="saveAllScores"
                                :disabled="isSaving || students.length === 0"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#1b4332] text-white text-sm font-bold hover:bg-[#143325] shadow-md shadow-emerald-950/15 disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <CheckCircleIcon class="w-5 h-5" />
                                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="students.length === 0" class="bg-white rounded-3xl p-12 text-center text-sm text-slate-400 italic border border-slate-200">
                        Belum ada siswa aktif di kelompok {{ selectedGroup.name }}.
                    </div>

                    <!-- DESKTOP SUB-TABLES (Grouped cleanly by Formal Grade when usesGradeRubrics=true) -->
                    <div v-else class="hidden md:block space-y-5">
                        <div
                            v-for="sec in studentSections"
                            :key="sec.key"
                            class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden"
                        >
                            <!-- Sub-table Header (shown when grade-scoped) -->
                            <div v-if="sec.gradeLabel" class="px-5 py-3 bg-emerald-50/70 border-b border-emerald-200/70 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-[#1b4332] text-white">{{ sec.gradeLabel }}</span>
                                    <span class="text-xs font-bold text-emerald-900">{{ sec.students.length }} Siswa</span>
                                </div>
                                <span class="text-[11px] text-emerald-700 font-semibold">
                                    {{ selectedGroup.level_name }} + Materi {{ sec.gradeLabel }}
                                </span>
                            </div>

                            <!-- Expandable Legend per Section -->
                            <div v-if="sectionColCount(sec) > 0" class="px-5 py-2.5 bg-white border-b border-slate-100">
                                <button @click="toggleLegend(sec.key)" class="w-full flex items-center justify-between text-xs font-bold text-slate-600">
                                    <span class="flex items-center gap-1.5 uppercase tracking-wider text-[10px] text-slate-500">
                                        <InformationCircleIcon class="w-4 h-4 text-emerald-600" />
                                        Keterangan Kolom / Rubrik ({{ sectionColCount(sec) }} indikator)
                                    </span>
                                    <span class="text-emerald-700 flex items-center gap-1 text-xs">
                                        {{ showLegendMap[sec.key] ? 'Sembunyikan' : 'Tampilkan' }}
                                        <component :is="showLegendMap[sec.key] ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                                    </span>
                                </button>
                                <div v-show="showLegendMap[sec.key]" class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                    <div v-if="sec.materi.length">
                                        <div class="font-extrabold text-emerald-700 uppercase text-[10px] mb-1">Materi Pokok ({{ selectedGroup.level_name }})</div>
                                        <div v-for="(item, idx) in sec.materi" :key="item.id" class="text-slate-600"><strong class="text-emerald-700">M{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.surah.length">
                                        <div class="font-extrabold text-amber-700 uppercase text-[10px] mb-1">Surah Pendek ({{ selectedGroup.level_name }})</div>
                                        <div v-for="(item, idx) in sec.surah" :key="item.id" class="text-slate-600"><strong class="text-amber-600">S{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.hadist.length">
                                        <div class="font-extrabold text-purple-700 uppercase text-[10px] mb-1">Hadist {{ sec.gradeLabel ? `(${sec.gradeLabel})` : '' }}</div>
                                        <div v-for="(item, idx) in sec.hadist" :key="item.id" class="text-slate-600"><strong class="text-purple-600">H{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.doa.length">
                                        <div class="font-extrabold text-blue-700 uppercase text-[10px] mb-1">Doa Harian {{ sec.gradeLabel ? `(${sec.gradeLabel})` : '' }}</div>
                                        <div v-for="(item, idx) in sec.doa" :key="item.id" class="text-slate-600"><strong class="text-blue-600">D{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.niatSholat.length">
                                        <div class="font-extrabold text-teal-700 uppercase text-[10px] mb-1">Niat Sholat ({{ sec.gradeLabel }})</div>
                                        <div v-for="(item, idx) in sec.niatSholat" :key="item.id" class="text-slate-600"><strong class="text-teal-600">N{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                    <div v-if="sec.bacaanSholat.length">
                                        <div class="font-extrabold text-indigo-700 uppercase text-[10px] mb-1">Bacaan Sholat ({{ sec.gradeLabel }})</div>
                                        <div v-for="(item, idx) in sec.bacaanSholat" :key="item.id" class="text-slate-600"><strong class="text-indigo-600">B{{ idx + 1 }}:</strong> {{ item.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Matrix Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm whitespace-nowrap">
                                    <thead>
                                        <tr class="bg-white border-b-2 border-slate-100">
                                            <th class="px-4 py-3 text-left font-extrabold text-slate-800 sticky left-0 bg-white shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] z-10 min-w-[200px]">
                                                Nama Siswa
                                            </th>
                                            <th v-if="sec.materi.length" :colspan="sec.materi.length" class="px-2 py-2 text-center text-[10px] font-black text-emerald-700 uppercase tracking-wider border-l-2 border-emerald-200 bg-emerald-50/50">Materi Pokok</th>
                                            <th v-if="sec.surah.length" :colspan="sec.surah.length" class="px-2 py-2 text-center text-[10px] font-black text-amber-700 uppercase tracking-wider border-l-2 border-amber-200 bg-amber-50/50">Surah Pendek</th>
                                            <th v-if="sec.hadist.length" :colspan="sec.hadist.length" class="px-2 py-2 text-center text-[10px] font-black text-purple-700 uppercase tracking-wider border-l-2 border-purple-200 bg-purple-50/50">Hadist</th>
                                            <th v-if="sec.doa.length" :colspan="sec.doa.length" class="px-2 py-2 text-center text-[10px] font-black text-blue-700 uppercase tracking-wider border-l-2 border-blue-200 bg-blue-50/50">Doa Harian</th>
                                            <th v-if="sec.niatSholat.length" :colspan="sec.niatSholat.length" class="px-2 py-2 text-center text-[10px] font-black text-teal-700 uppercase tracking-wider border-l-2 border-teal-200 bg-teal-50/50">Niat Sholat</th>
                                            <th v-if="sec.bacaanSholat.length" :colspan="sec.bacaanSholat.length" class="px-2 py-2 text-center text-[10px] font-black text-indigo-700 uppercase tracking-wider border-l-2 border-indigo-200 bg-indigo-50/50">Bacaan Sholat</th>
                                        </tr>
                                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-bold text-slate-500">
                                            <th class="px-4 py-2 sticky left-0 bg-slate-50/90 z-10"></th>
                                            <th v-for="(item, idx) in sec.materi" :key="'hm'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">M{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.surah" :key="'hs'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">S{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.hadist" :key="'hh'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">H{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.doa" :key="'hd'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">D{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.niatSholat" :key="'hn'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">N{{ idx + 1 }}</th>
                                            <th v-for="(item, idx) in sec.bacaanSholat" :key="'hb'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">B{{ idx + 1 }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template v-for="st in sec.students" :key="st.student_id">
                                            <tr v-if="localEntries[st.student_id]" class="hover:bg-slate-50/80">
                                                <td class="px-4 py-3 sticky left-0 bg-white shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] z-10">
                                                    <div class="font-bold text-slate-800 text-xs">{{ st.full_name }}</div>
                                                    <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-400">
                                                        <span>Kls {{ st.classroom_name }}</span>
                                                        <span v-if="st.previous_level_mismatch" class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">Naik Jilid</span>
                                                    </div>
                                                </td>
                                                <td v-for="item in sec.materi" :key="'m'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.surah" :key="'s'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.hadist" :key="'h'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.doa" :key="'d'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.niatSholat" :key="'n'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                                <td v-for="item in sec.bacaanSholat" :key="'b'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                    <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr v-if="localEntries[st.student_id]" class="bg-emerald-50/30 border-b border-slate-200/70">
                                                <td class="px-4 py-2 sticky left-0 bg-emerald-50/60 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] z-10">
                                                    <div class="flex items-center gap-1 text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">
                                                        <PencilSquareIcon class="w-3.5 h-3.5" />
                                                        <span>Catatan Guru</span>
                                                    </div>
                                                </td>
                                                <td :colspan="Math.max(1, sectionColCount(sec))" class="px-3 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            v-model="localEntries[st.student_id].teacher_note"
                                                            type="text"
                                                            maxlength="300"
                                                            placeholder="Tulis catatan guru Al-Qur'an untuk rapor siswa ini (opsional, maks 300 karakter)..."
                                                            class="w-full text-xs bg-white border-emerald-200 rounded-lg py-1.5 px-3 focus:ring-emerald-500 focus:border-emerald-500"
                                                        />
                                                        <span class="text-[10px] text-slate-400 shrink-0">{{ (localEntries[st.student_id].teacher_note || '').length }}/300</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- MOBILE CARD LIST -->
                    <div v-if="students.length > 0" class="block md:hidden bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
                        <div
                            v-for="st in students"
                            :key="'mob-'+st.student_id"
                            @click="openMobileInput(st)"
                            class="p-4 flex items-center justify-between gap-3 active:bg-slate-50 cursor-pointer"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 font-black text-sm flex items-center justify-center shrink-0">
                                    {{ st.full_name?.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-800 truncate">{{ st.full_name }}</div>
                                    <div class="text-xs text-slate-500">Kelas {{ st.classroom_name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-xl"
                                      :class="st.has_saved_assessment ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ st.has_saved_assessment ? 'Edit Nilai' : 'Isi Nilai' }}
                                </span>
                                <ChevronRightIcon class="w-4 h-4 text-slate-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Groups -->
                <div v-else class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-200">
                    <p class="text-sm font-bold text-slate-600">Tidak ada kelompok yang tersedia untuk filter ini.</p>
                    <p class="text-xs text-slate-400 mt-1">Pastikan kelompok sudah dibuat dan Anda ditugaskan sebagai pengampu utama atau asisten.</p>
                </div>
            </template>
        </div>

        <!-- Mobile Bottom-Sheet Drawer -->
        <Teleport to="body">
            <div v-if="showMobileDrawer && mobileStudent && localEntries[mobileStudent.student_id]" class="fixed inset-0 z-[100]">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeMobileInput"></div>
                <div class="fixed inset-x-0 bottom-0 max-h-[88vh] bg-white rounded-t-3xl shadow-2xl flex flex-col overflow-hidden">
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h4 class="font-extrabold text-slate-800 text-base">{{ mobileStudent.full_name }}</h4>
                            <p class="text-xs text-slate-500">Kelas {{ mobileStudent.classroom_name }} • {{ selectedGroup?.level_name }}</p>
                        </div>
                        <button @click="closeMobileInput" class="p-1.5 rounded-full bg-slate-200/70 text-slate-600"><XMarkIcon class="w-5 h-5" /></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-5 space-y-5">
                        <div v-if="mobileRubrics.materi.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Materi Pokok ({{ selectedGroup?.level_name }})</div>
                            <div v-for="(item, idx) in mobileRubrics.materi" :key="'mm'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-emerald-700 mr-1">M{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="mobileRubrics.surah.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-amber-700">Surah Pendek ({{ selectedGroup?.level_name }})</div>
                            <div v-for="(item, idx) in mobileRubrics.surah" :key="'ms'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-amber-600 mr-1">S{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="mobileRubrics.hadist.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-purple-700">Hadist {{ mobileStudent.grade_level ? `(Kelas ${mobileStudent.grade_level})` : '' }}</div>
                            <div v-for="(item, idx) in mobileRubrics.hadist" :key="'mh'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-purple-600 mr-1">H{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="mobileRubrics.doa.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-blue-700">Doa Harian {{ mobileStudent.grade_level ? `(Kelas ${mobileStudent.grade_level})` : '' }}</div>
                            <div v-for="(item, idx) in mobileRubrics.doa" :key="'md'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-blue-600 mr-1">D{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="mobileRubrics.niatSholat.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-teal-700">Niat Sholat &amp; Bersuci (Kelas {{ mobileStudent.grade_level }})</div>
                            <div v-for="(item, idx) in mobileRubrics.niatSholat" :key="'mn'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-teal-600 mr-1">N{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="mobileRubrics.bacaanSholat.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-indigo-700">Bacaan Sholat (Kelas {{ mobileStudent.grade_level }})</div>
                            <div v-for="(item, idx) in mobileRubrics.bacaanSholat" :key="'mb'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-indigo-600 mr-1">B{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-emerald-700 mb-1.5">Catatan Guru Al-Qur'an</label>
                            <textarea v-model="localEntries[mobileStudent.student_id].teacher_note" rows="3" maxlength="300" placeholder="Tulis catatan guru (opsional)..." class="w-full rounded-xl border-emerald-200 text-sm"></textarea>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-white flex gap-2">
                        <button @click="closeMobileInput" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 font-bold text-sm">Selesai</button>
                        <button @click="() => { closeMobileInput(); saveAllScores(); }" class="flex-1 py-3 rounded-xl bg-[#1b4332] text-white font-bold text-sm">Simpan Sekarang</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
