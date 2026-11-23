<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';

defineOptions({ layout: AuthenticatedLayout });
import QuranDialog from './Components/QuranDialog.vue';
import QuranConfirmDialog from './Components/QuranConfirmDialog.vue';
import QuranCollapse from './Components/QuranCollapse.vue';
import {
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    CheckIcon,
    XMarkIcon,
    BookOpenIcon,
    AcademicCapIcon,
    InformationCircleIcon,
    ChevronUpDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    levels: Array,
    availableGrades: { type: Array, default: () => ['1', '2', '3', '4', '5', '6'] },
    gradeRubrics: { type: Object, default: () => ({}) },
    usesGradeRubrics: { type: Boolean, default: false },
    categories: Object,
});

const scopeTab = ref('level'); // 'level' | 'grade'
const selectedLevelId = ref(props.levels?.[0]?.id || null);
const selectedGrade = ref(props.availableGrades?.[0] || '1');
const showGradeGuide = ref(false);
const showMobilePickerSheet = ref(false);

const selectedLevel = computed(() => {
    return (props.levels || []).find(l => l.id === selectedLevelId.value) || props.levels?.[0] || null;
});

const levelItemsByCategory = (cat) => {
    if (!selectedLevel.value) return [];
    return (selectedLevel.value.rubric_items || []).filter(i => i.category === cat);
};

const gradeItemsByCategory = (cat) => {
    const items = props.gradeRubrics?.[String(selectedGrade.value)] || [];
    return items.filter(i => i.category === cat);
};

// Add Level Modal
const showLevelModal = ref(false);
const levelForm = useForm({
    unit_id: props.unit.id,
    name: '',
    code: '',
    sort_order: (props.levels?.length || 0) + 1,
});

const openLevelModal = () => {
    levelForm.reset();
    levelForm.unit_id = props.unit.id;
    levelForm.sort_order = (props.levels?.length || 0) + 1;
    showLevelModal.value = true;
};

const submitLevel = () => {
    levelForm.post(route('quran.rubrics.levels.store'), {
        preserveScroll: true,
        onSuccess: () => { showLevelModal.value = false; },
    });
};

// Quick add inputs
const newTitles = ref({
    materi: '',
    surah_pendek: '',
    doa_harian: '',
    hadist: '',
    niat_sholat: '',
    bacaan_sholat: '',
});

const addingCategory = ref(null);

const addLevelItem = (category) => {
    const title = (newTitles.value[category] || '').trim();
    if (!title || !selectedLevel.value) return;

    addingCategory.value = category;
    router.post(route('quran.rubrics.items.store'), {
        unit_id: props.unit.id,
        scope: 'level',
        level_id: selectedLevel.value.id,
        category,
        title,
    }, {
        preserveScroll: true,
        onSuccess: () => { newTitles.value[category] = ''; },
        onFinish: () => { addingCategory.value = null; },
    });
};

const addGradeItem = (category) => {
    const title = (newTitles.value[category] || '').trim();
    if (!title || !selectedGrade.value) return;

    addingCategory.value = category;
    router.post(route('quran.rubrics.items.store'), {
        unit_id: props.unit.id,
        scope: 'grade',
        grade_level: String(selectedGrade.value),
        category,
        title,
    }, {
        preserveScroll: true,
        onSuccess: () => { newTitles.value[category] = ''; },
        onFinish: () => { addingCategory.value = null; },
    });
};

// Inline edit rubric item
const editingItemId = ref(null);
const editingTitle = ref('');

const startEditItem = (item) => {
    editingItemId.value = item.id;
    editingTitle.value = item.title;
};

const saveEditItem = (item) => {
    const title = editingTitle.value.trim();
    if (!title) return;
    router.put(route('quran.rubrics.items.update', item.id), {
        unit_id: props.unit.id,
        title,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingItemId.value = null; },
    });
};

// Confirm Delete Dialog
const deleteTarget = ref(null);
const deletingItem = ref(false);

const askDeleteItem = (item) => {
    deleteTarget.value = item;
};

const confirmDeleteItem = () => {
    if (!deleteTarget.value) return;
    deletingItem.value = true;
    router.delete(route('quran.rubrics.items.destroy', deleteTarget.value.id), {
        data: { unit_id: props.unit.id },
        preserveScroll: true,
        onFinish: () => {
            deletingItem.value = false;
            deleteTarget.value = null;
        },
    });
};

const levelSections = computed(() => {
    const base = [
        { key: 'materi', title: 'Materi Pokok', subtitle: 'Standar penilaian bacaan & teori per jilid', dot: 'bg-[#00796B]' },
        { key: 'surah_pendek', title: 'Surah Pendek', subtitle: 'Hafalan surat Al-Qur\'an per jilid', dot: 'bg-amber-500' },
    ];
    if (!props.usesGradeRubrics) {
        base.push(
            { key: 'doa_harian', title: 'Doa Harian', subtitle: 'Hafalan doa sehari-hari per jilid', dot: 'bg-sky-500' },
            { key: 'hadist', title: 'Hadist', subtitle: 'Hafalan hadist pilihan per jilid', dot: 'bg-violet-500' }
        );
    }
    return base;
});

const gradeSections = [
    { key: 'hadist', title: 'Hadist', subtitle: 'Hafalan hadist sesuai tingkat kelas formal', dot: 'bg-violet-500' },
    { key: 'doa_harian', title: 'Doa Harian', subtitle: 'Hafalan doa sehari-hari (Kelas 1–3)', dot: 'bg-sky-500' },
    { key: 'niat_sholat', title: 'Niat Sholat & Bersuci', subtitle: 'Niat sholat wajib/sunnah, wudhu & bersuci (Kelas 4–5)', dot: 'bg-teal-600' },
    { key: 'bacaan_sholat', title: 'Bacaan Sholat', subtitle: 'Bacaan gerakan sholat lengkap (Kelas 6)', dot: 'bg-indigo-500' },
];
</script>

<template>
    <div>
        <Head title="Rubrik Materi — Program Al-Qur'an" />

        <div class="max-w-7xl mx-auto py-1 sm:py-2 pb-28 md:pb-2 space-y-4">
            <QuranNav
                active-tab="rubrics"
                page-title="Master Jilid & Rubrik Materi"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            >
                <!-- Desktop Header Actions (100% Preserved) -->
                <template #actions>
                    <button
                        type="button"
                        @click="showGradeGuide = !showGradeGuide"
                        class="h-8 px-3 rounded-lg text-xs font-medium border transition-colors inline-flex items-center gap-1.5"
                        :class="showGradeGuide ? 'bg-teal-50 text-[#00796B] border-teal-200' : 'bg-white text-slate-600 border-slate-200/90 hover:bg-slate-50'"
                    >
                        <InformationCircleIcon class="w-3.5 h-3.5" />
                        <span>Standar Nilai</span>
                    </button>

                    <button
                        v-if="canManage"
                        type="button"
                        @click="openLevelModal"
                        class="h-8 px-3.5 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] shadow-[0_1px_2px_rgba(0,121,107,0.25)] transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Jilid Baru</span>
                    </button>
                </template>

                <!-- Mobile Header Actions -->
                <template #mobile-actions>
                    <button
                        type="button"
                        @click="showGradeGuide = !showGradeGuide"
                        class="w-9 h-9 rounded-xl border inline-flex items-center justify-center active:scale-95 transition-all"
                        :class="showGradeGuide ? 'bg-teal-50 text-[#00796B] border-teal-200' : 'bg-white text-slate-600 border-slate-200/90'"
                        aria-label="Standar Nilai"
                    >
                        <InformationCircleIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="canManage"
                        type="button"
                        @click="openLevelModal"
                        class="h-9 px-3 rounded-xl text-xs font-semibold text-white bg-[#00796B] active:scale-95 transition-all inline-flex items-center gap-1 shadow-xs"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Jilid</span>
                    </button>
                </template>
            </QuranNav>

            <!-- Collapsible Grade Standard Reference -->
            <QuranCollapse :open="showGradeGuide">
                <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 mb-1">
                    <div class="text-xs font-semibold text-slate-700 mb-2.5">Standar Predikat Metode Ummi</div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-emerald-50/60 border border-emerald-200/60">
                            <span class="w-7 h-7 rounded-md bg-emerald-600 text-white text-xs font-semibold flex items-center justify-center shrink-0">A</span>
                            <div class="text-xs leading-tight"><div class="font-semibold text-slate-800 tabular-nums">91 – 95</div><div class="text-[11px] text-slate-500">Sangat Lancar</div></div>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-sky-50/60 border border-sky-200/60">
                            <span class="w-7 h-7 rounded-md bg-sky-600 text-white text-xs font-semibold flex items-center justify-center shrink-0">B</span>
                            <div class="text-xs leading-tight"><div class="font-semibold text-slate-800 tabular-nums">81 – 90</div><div class="text-[11px] text-slate-500">Lancar / Tartil</div></div>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-amber-50/60 border border-amber-200/60">
                            <span class="w-7 h-7 rounded-md bg-amber-500 text-white text-xs font-semibold flex items-center justify-center shrink-0">C</span>
                            <div class="text-xs leading-tight"><div class="font-semibold text-slate-800 tabular-nums">71 – 80</div><div class="text-[11px] text-slate-500">Kurang Lancar</div></div>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-rose-50/60 border border-rose-200/60">
                            <span class="w-7 h-7 rounded-md bg-rose-600 text-white text-xs font-semibold flex items-center justify-center shrink-0">D</span>
                            <div class="text-xs leading-tight"><div class="font-semibold text-slate-800 tabular-nums">65 – 70</div><div class="text-[11px] text-slate-500">Belum Lancar</div></div>
                        </div>
                    </div>
                </div>
            </QuranCollapse>

            <!-- Scope Segmented Switcher (Full-width on mobile, inline on desktop) -->
            <div class="grid grid-cols-2 md:inline-flex items-center p-1 rounded-xl md:rounded-lg bg-slate-200/60 gap-1 md:gap-0.5">
                <button
                    type="button"
                    @click="scopeTab = 'level'"
                    class="h-9 md:h-8 px-3 md:px-3.5 rounded-lg md:rounded-md text-xs font-medium inline-flex items-center justify-center gap-1.5 transition-all"
                    :class="scopeTab === 'level' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <BookOpenIcon class="w-3.5 h-3.5 shrink-0" :class="scopeTab === 'level' ? 'text-[#00796B]' : 'text-slate-400'" />
                    <span class="md:hidden">Per Jilid</span>
                    <span class="hidden md:inline">Per Jilid (Materi &amp; Surah)</span>
                </button>
                <button
                    type="button"
                    @click="scopeTab = 'grade'"
                    class="h-9 md:h-8 px-3 md:px-3.5 rounded-lg md:rounded-md text-xs font-medium inline-flex items-center justify-center gap-1.5 transition-all"
                    :class="scopeTab === 'grade' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <AcademicCapIcon class="w-3.5 h-3.5 shrink-0" :class="scopeTab === 'grade' ? 'text-[#00796B]' : 'text-slate-400'" />
                    <span class="md:hidden">Per Kelas</span>
                    <span class="hidden md:inline">Per Kelas (Hadist, Doa &amp; Ibadah)</span>
                </button>
            </div>

            <!-- ==================== MASTER-DETAIL WORKSPACE ==================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <!-- Left Rail: Jilid / Kelas Selector -->
                <aside class="lg:col-span-3">
                    <!-- Desktop Vertical Rail (100% Preserved) -->
                    <div class="hidden lg:block bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-1.5 space-y-0.5">
                        <template v-if="scopeTab === 'level'">
                            <button
                                v-for="lvl in levels"
                                :key="lvl.id"
                                type="button"
                                @click="selectedLevelId = lvl.id"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors text-left"
                                :class="selectedLevel && selectedLevel.id === lvl.id
                                    ? 'bg-teal-50/80 text-[#00796B]'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                            >
                                <span class="truncate">
                                    <span class="text-slate-400 tabular-nums mr-1.5">{{ lvl.sort_order }}.</span>
                                    {{ lvl.name }}
                                </span>
                                <span
                                    class="text-[11px] tabular-nums px-1.5 py-px rounded-md"
                                    :class="selectedLevel && selectedLevel.id === lvl.id ? 'bg-white text-[#00796B] font-semibold' : 'text-slate-400'"
                                >
                                    {{ (lvl.rubric_items || []).length }}
                                </span>
                            </button>
                        </template>

                        <template v-else>
                            <button
                                v-for="g in availableGrades"
                                :key="g"
                                type="button"
                                @click="selectedGrade = g"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors text-left"
                                :class="String(selectedGrade) === String(g)
                                    ? 'bg-teal-50/80 text-[#00796B]'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                            >
                                <span>Kelas {{ g }}</span>
                                <span
                                    class="text-[11px] tabular-nums px-1.5 py-px rounded-md"
                                    :class="String(selectedGrade) === String(g) ? 'bg-white text-[#00796B] font-semibold' : 'text-slate-400'"
                                >
                                    {{ (gradeRubrics[String(g)] || []).length }}
                                </span>
                            </button>
                        </template>
                    </div>

                    <!-- Tablet Horizontal Pill Strip (`md` only) -->
                    <div class="hidden md:flex lg:hidden items-center gap-1.5 overflow-x-auto scrollbar-none pb-1">
                        <template v-if="scopeTab === 'level'">
                            <button
                                v-for="lvl in levels"
                                :key="'tl-'+lvl.id"
                                type="button"
                                @click="selectedLevelId = lvl.id"
                                class="h-8 px-3 rounded-lg text-xs font-medium border whitespace-nowrap shrink-0 transition-colors"
                                :class="selectedLevel && selectedLevel.id === lvl.id
                                    ? 'bg-[#00796B] text-white border-[#00796B]'
                                    : 'bg-white text-slate-600 border-slate-200/90'"
                            >
                                {{ lvl.sort_order }}. {{ lvl.name }}
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-for="g in availableGrades"
                                :key="'tg-'+g"
                                type="button"
                                @click="selectedGrade = g"
                                class="h-8 px-3 rounded-lg text-xs font-medium border whitespace-nowrap shrink-0 transition-colors inline-flex items-center gap-1.5"
                                :class="String(selectedGrade) === String(g)
                                    ? 'bg-[#00796B] text-white border-[#00796B]'
                                    : 'bg-white text-slate-600 border-slate-200/90'"
                            >
                                <span>Kelas {{ g }}</span>
                                <span class="text-[10px] opacity-75 tabular-nums">{{ (gradeRubrics[String(g)] || []).length }}</span>
                            </button>
                        </template>
                    </div>

                    <!-- Mobile Tappable Selector Card (< md) -->
                    <button
                        type="button"
                        @click="showMobilePickerSheet = true"
                        class="md:hidden w-full bg-white rounded-2xl border border-slate-200/90 px-4 py-3 flex items-center justify-between gap-3 active:scale-[0.99] transition-all shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-50 text-[#00796B] border border-teal-200/70 shrink-0">
                                {{ scopeTab === 'level' ? `#${selectedLevel?.sort_order || 1}` : `Kelas ${selectedGrade}` }}
                            </span>
                            <span class="text-sm font-semibold text-slate-900 truncate">
                                {{ scopeTab === 'level' ? (selectedLevel?.name || 'Pilih Jilid') : `Rubrik Kelas ${selectedGrade}` }}
                            </span>
                        </div>
                        <div class="inline-flex items-center gap-1 text-xs font-medium text-[#00796B] shrink-0">
                            <span class="tabular-nums text-slate-400">
                                {{ scopeTab === 'level' ? (selectedLevel?.rubric_items || []).length : (gradeRubrics[String(selectedGrade)] || []).length }} item
                            </span>
                            <ChevronUpDownIcon class="w-4 h-4" />
                        </div>
                    </button>
                </aside>

                <!-- Right Detail Workspace -->
                <div class="lg:col-span-9">
                    <Transition name="q-panel" mode="out-in">
                        <!-- Level Rubrics -->
                        <div
                            v-if="scopeTab === 'level' && selectedLevel"
                            :key="`lvl-${selectedLevel.id}`"
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div
                                v-for="sec in levelSections"
                                :key="sec.key"
                                class="bg-white rounded-2xl md:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] flex flex-col overflow-hidden"
                            >
                                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="sec.dot" />
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-slate-900 truncate">{{ sec.title }}</h3>
                                            <p class="text-[11px] text-slate-400 truncate">{{ selectedLevel.name }} · {{ sec.subtitle }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-slate-400 tabular-nums shrink-0">{{ levelItemsByCategory(sec.key).length }}</span>
                                </div>

                                <ul class="divide-y divide-slate-100 flex-1 px-2 py-1">
                                    <li
                                        v-for="(item, idx) in levelItemsByCategory(sec.key)"
                                        :key="item.id"
                                        class="group flex items-center justify-between gap-2 px-2 py-2.5 md:py-2 rounded-lg hover:bg-slate-50/80 transition-colors"
                                    >
                                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                            <span class="text-[11px] text-slate-400 tabular-nums w-5 text-right shrink-0">{{ idx + 1 }}.</span>
                                            <template v-if="editingItemId === item.id">
                                                <input
                                                    v-model="editingTitle"
                                                    @keyup.enter="saveEditItem(item)"
                                                    @keyup.esc="editingItemId = null"
                                                    type="text"
                                                    class="flex-1 h-9 md:h-7 text-xs rounded-lg md:rounded-md border-[#00796B] px-2.5 md:px-2 focus:ring-2 focus:ring-[#00796B]/15"
                                                />
                                                <button type="button" @click="saveEditItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-[#00796B] hover:bg-teal-50 rounded-lg inline-flex items-center justify-center"><CheckIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                                <button type="button" @click="editingItemId = null" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:bg-slate-200 rounded-lg inline-flex items-center justify-center"><XMarkIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                            </template>
                                            <span v-else class="text-xs font-medium text-slate-700 leading-snug">{{ item.title }}</span>
                                        </div>
                                        <div v-if="canManage && editingItemId !== item.id" class="flex items-center gap-0.5 shrink-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                            <button type="button" @click="startEditItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg md:rounded-md inline-flex items-center justify-center" title="Edit"><PencilSquareIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                            <button type="button" @click="askDeleteItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg md:rounded-md inline-flex items-center justify-center" title="Hapus"><TrashIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                        </div>
                                    </li>
                                    <li v-if="levelItemsByCategory(sec.key).length === 0" class="py-6 text-center text-xs text-slate-400">
                                        Belum ada item {{ sec.title.toLowerCase() }}.
                                    </li>
                                </ul>

                                <form v-if="canManage" @submit.prevent="addLevelItem(sec.key)" class="p-3 bg-slate-50/50 border-t border-slate-100 flex gap-2">
                                    <input
                                        v-model="newTitles[sec.key]"
                                        type="text"
                                        :placeholder="`Tambah ${sec.title.toLowerCase()}...`"
                                        class="flex-1 h-10 md:h-8 text-xs rounded-xl md:rounded-lg bg-white border-slate-200 px-3 md:px-2.5 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="addingCategory === sec.key || !(newTitles[sec.key] || '').trim()"
                                        class="h-10 md:h-8 px-3.5 md:px-3 rounded-xl md:rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium disabled:opacity-40 transition-colors inline-flex items-center gap-1 shrink-0"
                                    >
                                        <PlusIcon class="w-3.5 h-3.5" />
                                        <span>Tambah</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Grade Rubrics -->
                        <div
                            v-else-if="scopeTab === 'grade'"
                            :key="`grade-${selectedGrade}`"
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div
                                v-for="sec in gradeSections"
                                :key="'g-'+sec.key"
                                class="bg-white rounded-2xl md:rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] flex flex-col overflow-hidden"
                            >
                                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="sec.dot" />
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-slate-900 truncate">{{ sec.title }}</h3>
                                            <p class="text-[11px] text-slate-400 truncate">Kelas {{ selectedGrade }} · {{ sec.subtitle }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-slate-400 tabular-nums shrink-0">{{ gradeItemsByCategory(sec.key).length }}</span>
                                </div>

                                <ul class="divide-y divide-slate-100 flex-1 px-2 py-1">
                                    <li
                                        v-for="(item, idx) in gradeItemsByCategory(sec.key)"
                                        :key="item.id"
                                        class="group flex items-center justify-between gap-2 px-2 py-2.5 md:py-2 rounded-lg hover:bg-slate-50/80 transition-colors"
                                    >
                                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                            <span class="text-[11px] text-slate-400 tabular-nums w-5 text-right shrink-0">{{ idx + 1 }}.</span>
                                            <template v-if="editingItemId === item.id">
                                                <input
                                                    v-model="editingTitle"
                                                    @keyup.enter="saveEditItem(item)"
                                                    @keyup.esc="editingItemId = null"
                                                    type="text"
                                                    class="flex-1 h-9 md:h-7 text-xs rounded-lg md:rounded-md border-[#00796B] px-2.5 md:px-2 focus:ring-2 focus:ring-[#00796B]/15"
                                                />
                                                <button type="button" @click="saveEditItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-[#00796B] hover:bg-teal-50 rounded-lg inline-flex items-center justify-center"><CheckIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                                <button type="button" @click="editingItemId = null" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:bg-slate-200 rounded-lg inline-flex items-center justify-center"><XMarkIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                            </template>
                                            <span v-else class="text-xs font-medium text-slate-700 leading-snug">{{ item.title }}</span>
                                        </div>
                                        <div v-if="canManage && editingItemId !== item.id" class="flex items-center gap-0.5 shrink-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                            <button type="button" @click="startEditItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg md:rounded-md inline-flex items-center justify-center" title="Edit"><PencilSquareIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                            <button type="button" @click="askDeleteItem(item)" class="w-8 h-8 md:w-auto md:h-auto p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg md:rounded-md inline-flex items-center justify-center" title="Hapus"><TrashIcon class="w-4 h-4 md:w-3.5 md:h-3.5" /></button>
                                        </div>
                                    </li>
                                    <li v-if="gradeItemsByCategory(sec.key).length === 0" class="py-6 text-center text-xs text-slate-400">
                                        Tidak ada item {{ sec.title.toLowerCase() }} di Kelas {{ selectedGrade }}.
                                    </li>
                                </ul>

                                <form v-if="canManage" @submit.prevent="addGradeItem(sec.key)" class="p-3 bg-slate-50/50 border-t border-slate-100 flex gap-2">
                                    <input
                                        v-model="newTitles[sec.key]"
                                        type="text"
                                        :placeholder="`Tambah ${sec.title.toLowerCase()} Kelas ${selectedGrade}...`"
                                        class="flex-1 h-10 md:h-8 text-xs rounded-xl md:rounded-lg bg-white border-slate-200 px-3 md:px-2.5 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="addingCategory === sec.key || !(newTitles[sec.key] || '').trim()"
                                        class="h-10 md:h-8 px-3.5 md:px-3 rounded-xl md:rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium disabled:opacity-40 transition-colors inline-flex items-center gap-1 shrink-0"
                                    >
                                        <PlusIcon class="w-3.5 h-3.5" />
                                        <span>Tambah</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- ==================== MOBILE JILID / KELAS PICKER BOTTOM SHEET ==================== -->
        <QuranDialog
            :show="showMobilePickerSheet"
            variant="sheet"
            :title="scopeTab === 'level' ? 'Pilih Jilid / Jenjang' : 'Pilih Kelas Formal'"
            :subtitle="scopeTab === 'level' ? 'Lihat & atur rubrik Materi Pokok dan Surah Pendek.' : 'Lihat & atur rubrik Hadist, Doa, Niat, dan Bacaan Sholat.'"
            @close="showMobilePickerSheet = false"
        >
            <div class="p-4">
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 overflow-hidden bg-white">
                    <template v-if="scopeTab === 'level'">
                        <button
                            v-for="lvl in levels"
                            :key="'mp-' + lvl.id"
                            type="button"
                            @click="() => { selectedLevelId = lvl.id; showMobilePickerSheet = false; }"
                            class="w-full px-4 py-3 flex items-center justify-between text-left text-sm active:bg-slate-50 transition-colors"
                            :class="selectedLevel && selectedLevel.id === lvl.id ? 'bg-teal-50/50 text-[#00796B] font-semibold' : 'text-slate-700 font-medium'"
                        >
                            <span>
                                <span class="text-slate-400 tabular-nums mr-2">{{ lvl.sort_order }}.</span>
                                {{ lvl.name }}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 tabular-nums">{{ (lvl.rubric_items || []).length }} item</span>
                                <CheckIcon v-if="selectedLevel && selectedLevel.id === lvl.id" class="w-4 h-4 text-[#00796B]" />
                            </div>
                        </button>
                    </template>
                    <template v-else>
                        <button
                            v-for="g in availableGrades"
                            :key="'mpg-' + g"
                            type="button"
                            @click="() => { selectedGrade = g; showMobilePickerSheet = false; }"
                            class="w-full px-4 py-3 flex items-center justify-between text-left text-sm active:bg-slate-50 transition-colors"
                            :class="String(selectedGrade) === String(g) ? 'bg-teal-50/50 text-[#00796B] font-semibold' : 'text-slate-700 font-medium'"
                        >
                            <span>Kelas {{ g }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 tabular-nums">{{ (gradeRubrics[String(g)] || []).length }} item</span>
                                <CheckIcon v-if="String(selectedGrade) === String(g)" class="w-4 h-4 text-[#00796B]" />
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </QuranDialog>

        <!-- Modal / Sheet: Tambah Jilid Baru -->
        <QuranDialog
            :show="showLevelModal"
            variant="modal"
            max-width="md"
            title="Tambah Jilid / Tingkat Baru"
            subtitle="Jenjang baru akan tampil di pilihan kelompok dan rubrik."
            @close="showLevelModal = false"
        >
            <form id="quran-level-form" @submit.prevent="submitLevel" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Jilid / Tingkat <span class="text-rose-500">*</span></label>
                    <input v-model="levelForm.name" required type="text" placeholder="Contoh: Pra-TK / Tahfidz Juz 29" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    <p v-if="levelForm.errors.name" class="text-xs text-rose-600 mt-1">{{ levelForm.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Kode Singkat</label>
                        <input v-model="levelForm.code" type="text" placeholder="MQ / J1" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Urutan Jenjang</label>
                        <input v-model="levelForm.sort_order" type="number" min="1" max="99" class="w-full h-10 md:h-9 rounded-xl md:rounded-lg border-slate-200 text-sm tabular-nums focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showLevelModal = false" class="h-10 md:h-8 px-4 md:px-3.5 rounded-xl md:rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" form="quran-level-form" :disabled="levelForm.processing" class="h-10 md:h-8 px-4 rounded-xl md:rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-50 transition-colors">
                    {{ levelForm.processing ? 'Menyimpan...' : 'Simpan Jilid' }}
                </button>
            </template>
        </QuranDialog>

        <!-- Confirm Delete Rubric Item -->
        <QuranConfirmDialog
            :show="!!deleteTarget"
            title="Hapus item rubrik?"
            :message="deleteTarget ? `Item &quot;${deleteTarget.title}&quot; akan dihapus dari daftar rubrik penilaian.` : ''"
            confirm-label="Hapus Item"
            :processing="deletingItem"
            @close="deleteTarget = null"
            @confirm="confirmDeleteItem"
        />
    </div>
</template>

<style scoped>
.q-panel-enter-active {
    transition: opacity 180ms cubic-bezier(0.16, 1, 0.3, 1), transform 200ms cubic-bezier(0.16, 1, 0.3, 1);
}
.q-panel-leave-active {
    transition: opacity 110ms ease-in;
}
.q-panel-enter-from {
    opacity: 0;
    transform: translateY(4px);
}
.q-panel-leave-to {
    opacity: 0;
}
</style>
