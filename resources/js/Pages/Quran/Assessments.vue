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
    groups: Array,
    selectedGroup: Object,
    rubrics: Object,
    students: Array,
});

const grades = ['-', 'A', 'B', 'C', 'D'];
const showLegend = ref(false);
const isSaving = ref(false);
const isDirty = ref(false);
const isInitializing = ref(false);

// Editable local state keyed by student_id
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
const doaList = computed(() => props.rubrics?.doa_harian || []);
const hadistList = computed(() => props.rubrics?.hadist || []);
const totalColumns = computed(() => materiList.value.length + surahList.value.length + doaList.value.length + hadistList.value.length);

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
                <!-- Filter Bar: Semester & Role Pengampu -->
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
                <div v-if="selectedGroup" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <!-- Top Action Header -->
                    <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
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
                        </div>

                        <div class="flex items-center gap-3">
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

                    <!-- Expandable Legend Rubrik -->
                    <div v-if="totalColumns > 0" class="px-4 sm:px-5 py-3 bg-white border-b border-slate-100">
                        <button @click="showLegend = !showLegend" class="w-full flex items-center justify-between text-xs font-bold text-slate-600">
                            <span class="flex items-center gap-1.5 uppercase tracking-wider text-[11px] text-slate-500">
                                <InformationCircleIcon class="w-4 h-4 text-emerald-600" />
                                Lihat Keterangan Kolom / Rubrik ({{ totalColumns }} indikator)
                            </span>
                            <span class="text-emerald-700 flex items-center gap-1">
                                {{ showLegend ? 'Sembunyikan' : 'Tampilkan' }}
                                <component :is="showLegend ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                            </span>
                        </button>
                        <div v-show="showLegend" class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                            <div v-if="materiList.length">
                                <div class="font-extrabold text-emerald-700 uppercase text-[10px] mb-1">Materi Pokok</div>
                                <div v-for="(item, idx) in materiList" :key="item.id" class="text-slate-600">
                                    <strong class="text-emerald-700">M{{ idx + 1 }}:</strong> {{ item.title }}
                                </div>
                            </div>
                            <div v-if="surahList.length">
                                <div class="font-extrabold text-amber-700 uppercase text-[10px] mb-1">Surah Pendek</div>
                                <div v-for="(item, idx) in surahList" :key="item.id" class="text-slate-600">
                                    <strong class="text-amber-600">S{{ idx + 1 }}:</strong> {{ item.title }}
                                </div>
                            </div>
                            <div v-if="doaList.length">
                                <div class="font-extrabold text-blue-700 uppercase text-[10px] mb-1">Doa Harian</div>
                                <div v-for="(item, idx) in doaList" :key="item.id" class="text-slate-600">
                                    <strong class="text-blue-600">D{{ idx + 1 }}:</strong> {{ item.title }}
                                </div>
                            </div>
                            <div v-if="hadistList.length">
                                <div class="font-extrabold text-purple-700 uppercase text-[10px] mb-1">Hadist</div>
                                <div v-for="(item, idx) in hadistList" :key="item.id" class="text-slate-600">
                                    <strong class="text-purple-600">H{{ idx + 1 }}:</strong> {{ item.title }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Students -->
                    <div v-if="students.length === 0" class="p-12 text-center text-sm text-slate-400 italic">
                        Belum ada siswa aktif di kelompok {{ selectedGroup.name }}.
                    </div>

                    <template v-else>
                        <!-- DESKTOP MATRIX VIEW -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-sm whitespace-nowrap">
                                <thead>
                                    <tr class="bg-white border-b-2 border-slate-100">
                                        <th class="px-4 py-3 text-left font-extrabold text-slate-800 sticky left-0 bg-white shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] z-10 min-w-[200px]">
                                            Nama Siswa
                                        </th>
                                        <th v-if="materiList.length" :colspan="materiList.length" class="px-2 py-2 text-center text-[10px] font-black text-emerald-700 uppercase tracking-wider border-l-2 border-emerald-200 bg-emerald-50/50">
                                            Materi Pokok
                                        </th>
                                        <th v-if="surahList.length" :colspan="surahList.length" class="px-2 py-2 text-center text-[10px] font-black text-amber-700 uppercase tracking-wider border-l-2 border-amber-200 bg-amber-50/50">
                                            Surah Pendek
                                        </th>
                                        <th v-if="doaList.length" :colspan="doaList.length" class="px-2 py-2 text-center text-[10px] font-black text-blue-700 uppercase tracking-wider border-l-2 border-blue-200 bg-blue-50/50">
                                            Doa Harian
                                        </th>
                                        <th v-if="hadistList.length" :colspan="hadistList.length" class="px-2 py-2 text-center text-[10px] font-black text-purple-700 uppercase tracking-wider border-l-2 border-purple-200 bg-purple-50/50">
                                            Hadist
                                        </th>
                                    </tr>
                                    <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-bold text-slate-500">
                                        <th class="px-4 py-2 sticky left-0 bg-slate-50/90 z-10"></th>
                                        <th v-for="(item, idx) in materiList" :key="'hm'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">M{{ idx + 1 }}</th>
                                        <th v-for="(item, idx) in surahList" :key="'hs'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">S{{ idx + 1 }}</th>
                                        <th v-for="(item, idx) in doaList" :key="'hd'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">D{{ idx + 1 }}</th>
                                        <th v-for="(item, idx) in hadistList" :key="'hh'+item.id" class="px-2 py-2 text-center border-l border-slate-100 w-20 cursor-help" :title="item.title">H{{ idx + 1 }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template v-for="st in students" :key="st.student_id">
                                        <tr v-if="localEntries[st.student_id]" class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 sticky left-0 bg-white shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] z-10">
                                                <div class="font-bold text-slate-800 text-xs">{{ st.full_name }}</div>
                                                <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-400">
                                                    <span>Kls {{ st.classroom_name }}</span>
                                                    <span v-if="st.previous_level_mismatch" class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold" title="Siswa naik jilid setelah nilai sebelumnya, simpan nilai untuk memperbarui ke jilid terbaru">
                                                        Naik Jilid
                                                    </span>
                                                </div>
                                            </td>
                                            <td v-for="item in materiList" :key="'m'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer focus:ring-emerald-500', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                </select>
                                            </td>
                                            <td v-for="item in surahList" :key="'s'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer focus:ring-amber-500', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                </select>
                                            </td>
                                            <td v-for="item in doaList" :key="'d'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer focus:ring-blue-500', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                </select>
                                            </td>
                                            <td v-for="item in hadistList" :key="'h'+st.student_id+item.id" class="px-1.5 py-2 text-center border-l border-slate-100">
                                                <select v-model="localEntries[st.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-xs cursor-pointer focus:ring-purple-500', getGradeColor(localEntries[st.student_id].grades[String(item.id)])]">
                                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <!-- Catatan Guru Row -->
                                        <tr v-if="localEntries[st.student_id]" class="bg-emerald-50/30 border-b border-slate-200/70">
                                            <td class="px-4 py-2 sticky left-0 bg-emerald-50/60 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] z-10">
                                                <div class="flex items-center gap-1 text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">
                                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                                    <span>Catatan Guru</span>
                                                </div>
                                            </td>
                                            <td :colspan="Math.max(1, totalColumns)" class="px-3 py-2">
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

                        <!-- MOBILE CARD VIEW -->
                        <div class="block md:hidden divide-y divide-slate-100">
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
                    </template>
                </div>

                <!-- No Groups Assigned -->
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
                        <div v-if="materiList.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Materi Pokok</div>
                            <div v-for="(item, idx) in materiList" :key="'mm'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-emerald-700 mr-1">M{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="surahList.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-amber-700">Hafalan Surah Pendek</div>
                            <div v-for="(item, idx) in surahList" :key="'ms'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-amber-600 mr-1">S{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="doaList.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-blue-700">Hafalan Doa Harian</div>
                            <div v-for="(item, idx) in doaList" :key="'md'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-blue-600 mr-1">D{{ idx+1 }}</strong>{{ item.title }}</div>
                                <select v-model="localEntries[mobileStudent.student_id].grades[String(item.id)]" :class="['w-16 py-1.5 rounded-lg border text-center text-sm shrink-0', getGradeColor(localEntries[mobileStudent.student_id].grades[String(item.id)])]">
                                    <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="hadistList.length" class="space-y-2">
                            <div class="text-xs font-extrabold uppercase tracking-wider text-purple-700">Hafalan Hadist</div>
                            <div v-for="(item, idx) in hadistList" :key="'mh'+item.id" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="text-xs font-semibold text-slate-700"><strong class="text-purple-600 mr-1">H{{ idx+1 }}</strong>{{ item.title }}</div>
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
