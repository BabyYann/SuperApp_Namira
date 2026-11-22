<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import QuranDialog from './Components/QuranDialog.vue';
import axios from 'axios';
import {
    MagnifyingGlassIcon,
    ArrowUpCircleIcon,
    ClockIcon,
    XMarkIcon,
    ArrowsRightLeftIcon,
    CheckBadgeIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    unit: Object,
    eligibleUnits: Array,
    academicYears: Array,
    activeYear: Object,
    canManage: Boolean,
    isGlobalAdmin: Boolean,
    levels: Array,
    groups: Array,
    classrooms: Array,
    students: Array,
    filters: Object,
});

const classroomFilter = ref(props.filters?.classroom_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const groupFilter = ref(props.filters?.group_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');
const isFiltering = ref(false);

const hasActiveFilter = computed(() => {
    return classroomFilter.value !== 'all' || levelFilter.value !== 'all' || groupFilter.value !== 'all' || searchQuery.value.trim() !== '';
});

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('quran.students.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        classroom_id: classroomFilter.value,
        level_id: levelFilter.value,
        group_id: groupFilter.value,
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

const resetFilters = () => {
    classroomFilter.value = 'all';
    levelFilter.value = 'all';
    groupFilter.value = 'all';
    searchQuery.value = '';
    applyFilters();
};

// ==================== PROMOTE / TRANSFER MODAL ====================
const showPromoteModal = ref(false);
const targetStudent = ref(null);
const selectedTargetLevelId = ref('all');

const promoteForm = useForm({
    unit_id: props.unit.id,
    academic_year_id: props.activeYear.id,
    target_group_id: '',
    change_type: 'promoted',
    changed_at: new Date().toISOString().slice(0, 10),
    exam_score: '',
    notes: '',
});

const availableTargetGroups = computed(() => {
    return (props.groups || []).filter(g => {
        if (targetStudent.value && g.id === targetStudent.value.group_id) return false;
        if (selectedTargetLevelId.value !== 'all' && String(g.level_id) !== String(selectedTargetLevelId.value)) return false;
        return true;
    });
});

const openPromoteModal = (student) => {
    targetStudent.value = student;
    promoteForm.reset();
    promoteForm.unit_id = props.unit.id;
    promoteForm.academic_year_id = props.activeYear.id;
    promoteForm.changed_at = new Date().toISOString().slice(0, 10);

    if (!student.level_id) {
        promoteForm.change_type = 'initial';
        selectedTargetLevelId.value = 'all';
    } else {
        promoteForm.change_type = 'promoted';
        const sortedLevels = [...props.levels].sort((a, b) => a.sort_order - b.sort_order);
        const curIdx = sortedLevels.findIndex(l => l.id === student.level_id);
        const nextLevel = curIdx >= 0 && curIdx + 1 < sortedLevels.length ? sortedLevels[curIdx + 1] : null;
        selectedTargetLevelId.value = nextLevel ? nextLevel.id : 'all';
    }

    const candidates = availableTargetGroups.value;
    promoteForm.target_group_id = candidates.length === 1 ? candidates[0].id : '';
    showPromoteModal.value = true;
};

const onChangeTypeSwitch = (type) => {
    promoteForm.change_type = type;
    if (type === 'transferred' && targetStudent.value?.level_id) {
        selectedTargetLevelId.value = targetStudent.value.level_id;
    } else if (type === 'promoted' && targetStudent.value?.level_id) {
        const sortedLevels = [...props.levels].sort((a, b) => a.sort_order - b.sort_order);
        const curIdx = sortedLevels.findIndex(l => l.id === targetStudent.value.level_id);
        const nextLevel = curIdx >= 0 && curIdx + 1 < sortedLevels.length ? sortedLevels[curIdx + 1] : null;
        selectedTargetLevelId.value = nextLevel ? nextLevel.id : 'all';
    }
    promoteForm.target_group_id = '';
};

const submitPromote = () => {
    if (!targetStudent.value) return;
    promoteForm.post(route('quran.students.promote', targetStudent.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPromoteModal.value = false;
            targetStudent.value = null;
        },
    });
};

// ==================== HISTORY SLIDE-OVER DRAWER ====================
const showHistoryDrawer = ref(false);
const loadingHistory = ref(false);
const historyStudent = ref(null);
const historyRows = ref([]);

const openHistory = async (student) => {
    showHistoryDrawer.value = true;
    loadingHistory.value = true;
    historyStudent.value = student;
    historyRows.value = [];

    try {
        const res = await axios.get(route('quran.students.history', student.id), {
            params: { unit_id: props.isGlobalAdmin ? props.unit.id : undefined },
        });
        historyRows.value = res.data.histories || [];
    } catch (err) {
        console.error('Failed to load quran history:', err);
    } finally {
        loadingHistory.value = false;
    }
};

const changeTypeMeta = (type) => {
    if (type === 'promoted') return { text: 'Naik Jilid', dot: 'bg-[#00796B]', badge: 'bg-teal-50 text-[#00796B] border-teal-200/80' };
    if (type === 'transferred') return { text: 'Pindah Kelompok', dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700 border-amber-200/80' };
    return { text: 'Penempatan Awal', dot: 'bg-sky-500', badge: 'bg-sky-50 text-sky-700 border-sky-200/80' };
};
</script>

<template>
    <Head title="Tracking & Naik Jilid — Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-1 sm:py-2 space-y-4">
            <QuranNav
                active-tab="students"
                page-title="Tracking & Kenaikan Jilid"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            />

            <!-- Clean Filter Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama siswa, NIS, atau NISN..."
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

                <select v-model="classroomFilter" @change="applyFilters" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                    <option value="all">Semua Kelas</option>
                    <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                </select>

                <select v-model="levelFilter" @change="applyFilters" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                    <option value="all">Semua Jilid</option>
                    <option value="unplotted">Belum Diplot</option>
                    <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>

                <select v-model="groupFilter" @change="applyFilters" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 hover:border-slate-300 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                    <option value="all">Semua Kelompok</option>
                    <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
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

            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden">
                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between text-xs">
                    <span class="font-medium text-slate-600 tabular-nums">{{ students.length }} siswa</span>
                    <span class="text-slate-400">Riwayat kenaikan jilid tercatat otomatis setiap perpindahan</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-medium text-slate-500">
                                <th class="px-4 py-2.5">Siswa</th>
                                <th class="px-4 py-2.5">Jilid Saat Ini</th>
                                <th class="px-4 py-2.5">Kelompok &amp; Sesi</th>
                                <th class="px-4 py-2.5">Pengampu</th>
                                <th class="px-4 py-2.5 text-center">Progres</th>
                                <th class="px-4 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-[13px]">
                            <tr v-if="students.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-xs text-slate-400">
                                    Tidak ada data siswa sesuai filter.
                                </td>
                            </tr>
                            <tr v-for="st in students" :key="st.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 text-[11px] font-semibold flex items-center justify-center shrink-0">
                                            {{ st.full_name?.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-slate-900 truncate">{{ st.full_name }}</div>
                                            <div class="text-[11px] text-slate-400">
                                                Kelas {{ st.classroom_name }}<span v-if="st.nis" class="tabular-nums"> · {{ st.nis }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border"
                                        :class="st.level_id ? 'bg-teal-50 text-[#00796B] border-teal-200/70' : 'bg-slate-50 text-slate-400 border-slate-200/80'"
                                    >
                                        {{ st.level_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="text-xs font-medium text-slate-700">{{ st.group_name }}</div>
                                    <div v-if="st.session_name !== '-'" class="text-[11px] text-slate-400">{{ st.session_name }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-600">
                                    {{ st.teacher_name }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span v-if="st.promotions_count > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/70 tabular-nums">
                                        <CheckBadgeIcon class="w-3.5 h-3.5" />
                                        Naik {{ st.promotions_count }}×
                                    </span>
                                    <span v-else class="text-xs text-slate-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            v-if="st.can_promote"
                                            type="button"
                                            @click="openPromoteModal(st)"
                                            class="h-7 px-2.5 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] transition-colors inline-flex items-center gap-1"
                                        >
                                            <ArrowUpCircleIcon class="w-3.5 h-3.5" />
                                            <span>{{ st.level_id ? 'Naik / Pindah' : 'Plot' }}</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openHistory(st)"
                                            class="h-7 px-2.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-900 transition-colors inline-flex items-center gap-1"
                                        >
                                            <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                                            <span>Riwayat</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Card List -->
            <div class="md:hidden bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100 overflow-hidden">
                <div v-if="students.length === 0" class="px-4 py-10 text-center text-xs text-slate-400">
                    Tidak ada data siswa sesuai filter.
                </div>
                <div v-for="st in students" :key="'m-'+st.id" class="p-3.5 space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">{{ st.full_name }}</div>
                            <div class="text-xs text-slate-500">Kelas {{ st.classroom_name }} · {{ st.teacher_name }}</div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-md text-[11px] font-medium border shrink-0"
                            :class="st.level_id ? 'bg-teal-50 text-[#00796B] border-teal-200/70' : 'bg-slate-50 text-slate-400 border-slate-200/80'"
                        >
                            {{ st.level_name }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="text-slate-500 truncate">{{ st.group_name }}</span>
                        <span v-if="st.promotions_count > 0" class="text-[11px] font-medium text-[#00796B] shrink-0">Naik {{ st.promotions_count }}×</span>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            v-if="st.can_promote"
                            type="button"
                            @click="openPromoteModal(st)"
                            class="flex-1 h-8 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] transition-colors inline-flex items-center justify-center gap-1"
                        >
                            <ArrowUpCircleIcon class="w-3.5 h-3.5" />
                            <span>{{ st.level_id ? 'Naik / Pindah' : 'Plot Kelompok' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="openHistory(st)"
                            class="flex-1 h-8 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors inline-flex items-center justify-center gap-1"
                        >
                            <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Riwayat</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL: NAIK JILID / PINDAH KELOMPOK ==================== -->
        <QuranDialog
            :show="showPromoteModal && !!targetStudent"
            variant="modal"
            max-width="lg"
            :title="targetStudent?.full_name || ''"
            :subtitle="`Kelas ${targetStudent?.classroom_name || ''} · Posisi saat ini: ${targetStudent?.level_name || '-'}`"
            @close="showPromoteModal = false"
        >
            <form id="quran-promote-form" @submit.prevent="submitPromote" class="space-y-4">
                <!-- Segmented Switch -->
                <div v-if="targetStudent?.level_id" class="grid grid-cols-2 p-1 rounded-lg bg-slate-100 gap-1">
                    <button
                        type="button"
                        @click="onChangeTypeSwitch('promoted')"
                        class="h-8 rounded-md text-xs font-medium flex items-center justify-center gap-1.5 transition-all"
                        :class="promoteForm.change_type === 'promoted' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    >
                        <ArrowUpCircleIcon class="w-4 h-4" />
                        <span>Naik Jilid</span>
                    </button>
                    <button
                        type="button"
                        @click="onChangeTypeSwitch('transferred')"
                        class="h-8 rounded-md text-xs font-medium flex items-center justify-center gap-1.5 transition-all"
                        :class="promoteForm.change_type === 'transferred' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    >
                        <ArrowsRightLeftIcon class="w-4 h-4" />
                        <span>Pindah Kelompok</span>
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Filter Jilid Tujuan</label>
                    <select v-model="selectedTargetLevelId" @change="promoteForm.target_group_id = ''" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Tampilkan Semua Jilid</option>
                        <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Kelompok &amp; Pengampu Tujuan <span class="text-rose-500">*</span></label>
                    <select v-model="promoteForm.target_group_id" required class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="">Pilih Kelompok Tujuan</option>
                        <option v-for="g in availableTargetGroups" :key="g.id" :value="g.id">
                            [{{ g.level_name }}] {{ g.name }} — {{ g.teacher_name }}{{ g.session_name ? ` (${g.session_name})` : '' }}
                        </option>
                    </select>
                    <p v-if="availableTargetGroups.length === 0" class="text-xs text-amber-600 mt-1.5">
                        Belum ada kelompok di jilid tujuan ini. Buat kelompoknya terlebih dahulu di tab Kelompok &amp; Sesi.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                        <input v-model="promoteForm.changed_at" required type="date" class="w-full h-9 rounded-lg border-slate-200 text-sm tabular-nums focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nilai Tes (Opsional)</label>
                        <input v-model="promoteForm.exam_score" type="text" placeholder="Contoh: A / 92" class="w-full h-9 rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Catatan (Opsional)</label>
                    <textarea v-model="promoteForm.notes" rows="2" placeholder="Contoh: Lulus tes naik jilid dengan lancar" class="w-full rounded-lg border-slate-200 text-sm focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15" />
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showPromoteModal = false" class="h-8 px-3.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" form="quran-promote-form" :disabled="promoteForm.processing || !promoteForm.target_group_id" class="h-8 px-4 rounded-lg text-xs font-medium text-white bg-[#00796B] hover:bg-[#00695C] disabled:opacity-40 transition-colors">
                    {{ promoteForm.processing ? 'Menyimpan...' : 'Simpan & Catat Riwayat' }}
                </button>
            </template>
        </QuranDialog>

        <!-- ==================== DRAWER: TIMELINE RIWAYAT JILID ==================== -->
        <QuranDialog
            :show="showHistoryDrawer && !!historyStudent"
            variant="drawer"
            max-width="lg"
            :title="`Riwayat — ${historyStudent?.full_name || ''}`"
            :subtitle="`Kelas ${historyStudent?.classroom_name || ''} · Posisi aktif: ${historyStudent?.level_name || '-'}`"
            @close="showHistoryDrawer = false"
        >
            <div class="p-5">
                <!-- Skeleton Loading Shimmer -->
                <div v-if="loadingHistory" class="space-y-4 animate-pulse">
                    <div v-for="n in 3" :key="n" class="flex gap-3">
                        <div class="w-3 h-3 rounded-full bg-slate-200 mt-1.5 shrink-0" />
                        <div class="flex-1 rounded-xl border border-slate-100 p-3.5 space-y-2">
                            <div class="h-3 w-24 bg-slate-200 rounded" />
                            <div class="h-4 w-44 bg-slate-200 rounded" />
                            <div class="h-3 w-56 bg-slate-100 rounded" />
                        </div>
                    </div>
                </div>

                <div v-else-if="historyRows.length === 0" class="py-12 text-center">
                    <ClockIcon class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                    <p class="text-xs text-slate-500">Belum ada riwayat kenaikan atau perpindahan jilid.</p>
                </div>

                <ol v-else class="relative border-l border-slate-200 ml-2 space-y-4">
                    <li v-for="h in historyRows" :key="h.id" class="ml-5 relative">
                        <span
                            class="absolute -left-[25px] top-1.5 w-2.5 h-2.5 rounded-full ring-4 ring-white"
                            :class="changeTypeMeta(h.change_type).dot"
                        />
                        <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 space-y-1.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium border" :class="changeTypeMeta(h.change_type).badge">
                                    {{ changeTypeMeta(h.change_type).text }}
                                </span>
                                <span class="text-[11px] text-slate-400 tabular-nums">{{ h.changed_at }} · {{ h.academic_year }}</span>
                            </div>

                            <div class="text-sm font-semibold text-slate-900 flex items-center gap-1.5">
                                <template v-if="h.from_level">
                                    <span class="text-slate-400 font-normal">{{ h.from_level }}</span>
                                    <ArrowRightIcon class="w-3.5 h-3.5 text-[#00796B]" />
                                    <span>{{ h.to_level }}</span>
                                </template>
                                <span v-else>{{ h.to_level }}</span>
                            </div>

                            <div class="text-xs text-slate-600 space-y-0.5">
                                <div v-if="h.to_group">
                                    Kelompok: <span class="font-medium text-slate-800">{{ h.to_group }}</span>
                                    <span v-if="h.to_teacher" class="text-slate-400"> ({{ h.to_teacher }})</span>
                                </div>
                                <div v-if="h.from_group" class="text-slate-400">
                                    Sebelumnya: {{ h.from_group }}<span v-if="h.from_teacher"> ({{ h.from_teacher }})</span>
                                </div>
                                <div v-if="h.exam_score" class="text-[#00796B] font-medium">Nilai Tes: {{ h.exam_score }}</div>
                                <div v-if="h.notes" class="text-slate-500 italic">"{{ h.notes }}"</div>
                                <div class="text-[11px] text-slate-400 pt-1">Dicatat oleh {{ h.recorded_by }}</div>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </QuranDialog>
    </AuthenticatedLayout>
</template>
