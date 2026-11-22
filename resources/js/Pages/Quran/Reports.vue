<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuranNav from './Components/QuranNav.vue';
import {
    PrinterIcon,
    DocumentArrowDownIcon,
    MagnifyingGlassIcon,
    LockClosedIcon,
    XMarkIcon,
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
    groups: Array,
    levels: Array,
    classrooms: Array,
    teachers: Array,
    students: Array,
    filters: Object,
});

const groupFilter = ref(props.filters?.group_id ?? 'all');
const levelFilter = ref(props.filters?.level_id ?? 'all');
const teacherFilter = ref(props.filters?.teacher_id ?? 'all');
const classroomFilter = ref(props.filters?.classroom_id ?? 'all');
const searchQuery = ref(props.filters?.search ?? '');
const onlyReady = ref(false);
const selectedIds = ref([]);
const isFiltering = ref(false);

const hasActiveFilter = computed(() => {
    return groupFilter.value !== 'all' || levelFilter.value !== 'all' || teacherFilter.value !== 'all' || classroomFilter.value !== 'all' || searchQuery.value.trim() !== '' || onlyReady.value;
});

const applyFilters = (semOverride = null) => {
    isFiltering.value = true;
    router.get(route('quran.reports.index'), {
        unit_id: props.isGlobalAdmin ? props.unit.id : undefined,
        academic_year_id: props.activeYear.id,
        semester: semOverride ?? props.semester,
        group_id: groupFilter.value,
        level_id: levelFilter.value,
        teacher_id: teacherFilter.value,
        classroom_id: classroomFilter.value,
        search: searchQuery.value.trim() || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { isFiltering.value = false; },
    });
};

const debouncedSearch = useDebounceFn(() => applyFilters(), 300);
watch(searchQuery, () => debouncedSearch());

const resetFilters = () => {
    groupFilter.value = 'all';
    levelFilter.value = 'all';
    teacherFilter.value = 'all';
    classroomFilter.value = 'all';
    searchQuery.value = '';
    onlyReady.value = false;
    applyFilters();
};

const visibleStudents = computed(() => {
    if (!onlyReady.value) return props.students || [];
    return (props.students || []).filter(s => s.is_ready);
});

const readyCount = computed(() => (props.students || []).filter(s => s.is_ready).length);

const toggleSelectAll = (e) => {
    selectedIds.value = e.target.checked
        ? visibleStudents.value.map(s => s.student_id)
        : [];
};

const openReportUrl = (routeName, ids) => {
    if (!ids || ids.length === 0) return;
    const params = new URLSearchParams();
    if (props.isGlobalAdmin) params.set('unit_id', props.unit.id);
    params.set('academic_year_id', props.activeYear.id);
    params.set('semester', props.semester);
    params.set('student_ids', ids.join(','));
    window.open(`${route(routeName)}?${params.toString()}`, '_blank');
};
</script>

<template>
    <Head title="Cetak Rapor — Program Al-Qur'an" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-1 sm:py-2 pb-24 space-y-4">
            <QuranNav
                active-tab="reports"
                page-title="Cetak Rapor Ummi (F4)"
                :unit="unit"
                :eligible-units="eligibleUnits"
                :academic-years="academicYears"
                :active-year="activeYear"
                :can-manage="canManage"
                :is-global-admin="isGlobalAdmin"
            >
                <template v-if="!isLocked" #actions>
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-200/60 gap-0.5 h-8">
                        <button
                            type="button"
                            @click="applyFilters('ganjil')"
                            class="h-7 px-2.5 rounded-md text-xs font-medium transition-all"
                            :class="semester === 'ganjil' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Ganjil
                        </button>
                        <button
                            type="button"
                            @click="applyFilters('genap')"
                            class="h-7 px-2.5 rounded-md text-xs font-medium transition-all"
                            :class="semester === 'genap' ? 'bg-white text-[#00796B] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
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
                <h3 class="text-sm font-semibold text-slate-900">Fitur Cetak Rapor Sedang Ditutup</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
                    Koordinator Al-Qur'an / Admin belum membuka akses cetak rapor untuk guru.
                </p>
            </div>

            <template v-else>
                <!-- Filter Bar -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama siswa atau NISN..."
                            class="w-full h-9 pl-9 pr-8 text-[13px] bg-white border border-slate-200/90 rounded-lg text-slate-800 placeholder:text-slate-400 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15"
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

                    <select v-model="groupFilter" @change="applyFilters()" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Semua Kelompok</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.level_name }})</option>
                    </select>

                    <select v-model="levelFilter" @change="applyFilters()" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Semua Jilid</option>
                        <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>

                    <select v-if="canManage" v-model="teacherFilter" @change="applyFilters()" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Semua Pengampu</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>

                    <select v-model="classroomFilter" @change="applyFilters()" class="h-9 px-3 text-xs font-medium bg-white border border-slate-200/90 rounded-lg text-slate-700 focus:border-[#00796B] focus:ring-2 focus:ring-[#00796B]/15">
                        <option value="all">Semua Kelas</option>
                        <option v-for="c in classrooms" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                    </select>

                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg bg-white border border-slate-200/90 text-xs font-medium text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" v-model="onlyReady" class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30" />
                        <span>Siap cetak ({{ readyCount }})</span>
                    </label>

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
                        <span class="font-medium text-slate-600 tabular-nums">{{ visibleStudents.length }} siswa tampil</span>
                        <span class="text-slate-400">Centang beberapa siswa untuk cetak / unduh PDF massal F4</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-medium text-slate-500">
                                    <th class="px-4 py-2.5 w-10">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll"
                                            :checked="visibleStudents.length > 0 && selectedIds.length === visibleStudents.length"
                                            class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30"
                                        />
                                    </th>
                                    <th class="px-4 py-2.5">Siswa</th>
                                    <th class="px-4 py-2.5">Jilid Rapor</th>
                                    <th class="px-4 py-2.5">Kelompok &amp; Pengampu</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Cetak Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-[13px]">
                                <tr v-if="visibleStudents.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-xs text-slate-400">
                                        Belum ada siswa yang sesuai filter.
                                    </td>
                                </tr>
                                <tr
                                    v-for="st in visibleStudents"
                                    :key="st.student_id"
                                    class="transition-colors"
                                    :class="selectedIds.includes(st.student_id) ? 'bg-teal-50/35' : 'hover:bg-slate-50/70'"
                                >
                                    <td class="px-4 py-2.5">
                                        <input
                                            type="checkbox"
                                            :value="st.student_id"
                                            v-model="selectedIds"
                                            class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30"
                                        />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-slate-900">{{ st.full_name }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            Kelas {{ st.classroom_name }}<span v-if="st.nisn" class="tabular-nums"> · NISN {{ st.nisn }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-teal-50 text-[#00796B] border border-teal-200/70">
                                            {{ st.level_name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs">
                                        <div class="font-medium text-slate-700">{{ st.group_name }}</div>
                                        <div class="text-slate-400">{{ st.teacher_name }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span v-if="st.is_ready" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#00796B] tabular-nums">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#00796B]" />
                                            Siap ({{ st.assessed_count }})
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300" />
                                            Belum dinilai
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openReportUrl('quran.reports.print', [st.student_id])"
                                                class="h-7 px-2.5 rounded-lg text-xs font-medium text-[#00796B] bg-teal-50/80 border border-teal-200/70 hover:bg-teal-100/70 transition-colors inline-flex items-center gap-1"
                                            >
                                                <PrinterIcon class="w-3.5 h-3.5" />
                                                <span>Print</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openReportUrl('quran.reports.pdf', [st.student_id])"
                                                class="h-7 px-2.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors inline-flex items-center gap-1"
                                            >
                                                <DocumentArrowDownIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>PDF</span>
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
                    <div class="px-3.5 py-2.5 bg-slate-50/60 flex items-center justify-between text-xs">
                        <label class="inline-flex items-center gap-2 font-medium text-slate-700">
                            <input
                                type="checkbox"
                                @change="toggleSelectAll"
                                :checked="visibleStudents.length > 0 && selectedIds.length === visibleStudents.length"
                                class="rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30"
                            />
                            <span>Pilih semua ({{ visibleStudents.length }})</span>
                        </label>
                        <span v-if="selectedIds.length" class="font-semibold text-[#00796B] tabular-nums">{{ selectedIds.length }} dipilih</span>
                    </div>

                    <div v-if="visibleStudents.length === 0" class="px-4 py-10 text-center text-xs text-slate-400">
                        Belum ada siswa yang sesuai filter.
                    </div>

                    <div v-for="st in visibleStudents" :key="'mr-'+st.student_id" class="p-3.5 space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <input
                                type="checkbox"
                                :value="st.student_id"
                                v-model="selectedIds"
                                class="mt-0.5 rounded border-slate-300 text-[#00796B] focus:ring-[#00796B]/30"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-slate-900 truncate">{{ st.full_name }}</span>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-teal-50 text-[#00796B] border border-teal-200/70 shrink-0">{{ st.level_name }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">Kelas {{ st.classroom_name }} · {{ st.group_name }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 pl-6">
                            <span class="text-[11px] font-medium" :class="st.is_ready ? 'text-[#00796B]' : 'text-slate-400'">
                                {{ st.is_ready ? `Siap (${st.assessed_count})` : 'Belum dinilai' }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="openReportUrl('quran.reports.print', [st.student_id])" class="h-7 px-2.5 rounded-lg text-xs font-medium text-[#00796B] bg-teal-50 border border-teal-200/70 inline-flex items-center gap-1">
                                    <PrinterIcon class="w-3.5 h-3.5" /> Print
                                </button>
                                <button type="button" @click="openReportUrl('quran.reports.pdf', [st.student_id])" class="h-7 px-2.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 inline-flex items-center gap-1">
                                    <DocumentArrowDownIcon class="w-3.5 h-3.5" /> PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ==================== FLOATING BULK PRINT BAR ==================== -->
        <Teleport to="body">
            <Transition name="q-float">
                <div
                    v-if="selectedIds.length > 0 && !isLocked"
                    class="fixed bottom-20 md:bottom-5 inset-x-4 z-[90] flex justify-center pointer-events-none"
                >
                    <div class="pointer-events-auto flex flex-wrap items-center gap-2.5 px-4 py-2.5 rounded-xl bg-slate-900 text-white shadow-xl shadow-slate-900/25 border border-slate-700/80">
                        <span class="text-xs font-medium text-slate-200 tabular-nums pr-1">
                            {{ selectedIds.length }} siswa dipilih
                        </span>
                        <button
                            type="button"
                            @click="selectedIds = []"
                            class="h-7 px-2.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="openReportUrl('quran.reports.print', selectedIds)"
                            class="h-7 px-3 rounded-lg text-xs font-medium bg-[#00796B] hover:bg-[#00897B] text-white transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                        >
                            <PrinterIcon class="w-3.5 h-3.5" />
                            <span>Print Massal F4</span>
                        </button>
                        <button
                            type="button"
                            @click="openReportUrl('quran.reports.pdf', selectedIds)"
                            class="h-7 px-3 rounded-lg text-xs font-medium bg-white text-slate-900 hover:bg-slate-100 transition-all active:scale-[0.98] inline-flex items-center gap-1.5"
                        >
                            <DocumentArrowDownIcon class="w-3.5 h-3.5" />
                            <span>Unduh PDF</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
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
